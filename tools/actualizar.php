<?php
/** CLI: diagnostica por defecto. --aplicar modifica exclusivamente la base local configurada. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASEPATH', __DIR__.'/../system/');
$root=dirname(__DIR__);
$aplicar=in_array('--aplicar',$argv,TRUE);
if (count(array_diff(array_slice($argv,1),array('--aplicar')))) { fwrite(STDERR,"Uso: php tools/actualizar.php [--aplicar]\n"); exit(1); }
if (!extension_loaded('mysqli')) { fwrite(STDERR,"Usa PHP de XAMPP con mysqli.\n"); exit(1); }
if (!is_file($root.'/application/config/local.php')) { fwrite(STDERR,"Falta application/config/local.php. Copia local.example.php y configura tu conexión.\n"); exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class Actualizador
{
    private $db;
    private $aplicar;
    private $tablas=array();
    private $columnas=array();
    public $pendientes=0;
    public function __construct($db,$aplicar)
    {
        $this->db=$db; $this->aplicar=$aplicar;
        $r=$db->query('SHOW TABLES');
        while ($fila=$r->fetch_row()) { $this->tablas[$fila[0]]=TRUE; $this->recargar($fila[0]); }
    }
    private function recargar($tabla)
    {
        $this->columnas[$tabla]=array();
        foreach ($this->db->query('SHOW COLUMNS FROM `'.$tabla.'`') as $col) { $this->columnas[$tabla][$col['Field']]=$col; }
    }
    public function tabla($t) { return isset($this->tablas[$t]); }
    public function columna($t,$c) { return isset($this->columnas[$t][$c]); }
    public function tipo($t,$c) { return $this->columnas[$t][$c]['Type'] ?? ''; }
    public function filas($sql) { return $this->db->query($sql); }
    public function operacion($titulo,$sql)
    {
        $this->pendientes++;
        echo ($this->aplicar ? 'Aplicar: ' : 'Pendiente: ').$titulo."\n";
        if ($this->aplicar) { $this->db->query($sql); }
    }
    public function crear($t,$sql)
    {
        if ($this->tabla($t)) { return; }
        $this->operacion('crear tabla '.$t,$sql);
        if ($this->aplicar) { $this->tablas[$t]=TRUE; $this->recargar($t); }
    }
    public function agregar($t,$c,$definicion)
    {
        if (!$this->tabla($t) || $this->columna($t,$c)) { return; }
        $this->operacion('agregar '.$t.'.'.$c,'ALTER TABLE `'.$t.'` ADD COLUMN `'.$c.'` '.$definicion);
        if ($this->aplicar) { $this->recargar($t); }
    }
    public function indice($t,$nombre,$columnas,$unico,$definicion)
    {
        if (!$this->tabla($t)) { return; }
        foreach ($columnas as $col) { if (!$this->columna($t,$col)) { return; } }
        $indices=array();
        foreach ($this->db->query('SHOW INDEX FROM `'.$t.'`') as $i) {
            $indices[$i['Key_name']]['cols'][(int)$i['Seq_in_index']]=$i['Column_name'];
            $indices[$i['Key_name']]['unico']=!(int)$i['Non_unique'];
        }
        foreach ($indices as $name=>$i) {
            ksort($i['cols']);
            if (array_values($i['cols'])===$columnas && (!$unico || $i['unico'])) { return; }
            if ($name===$nombre) { throw new RuntimeException('El índice '.$nombre.' existe con otra definición. Revisión manual necesaria.'); }
        }
        if ($unico) {
            $cols='`'.implode('`,`',$columnas).'`';
            $where=implode(' AND ',array_map(function($c) { return '`'.$c.'` IS NOT NULL'; },$columnas));
            if ($this->db->query('SELECT 1 FROM `'.$t.'` WHERE '.$where.' GROUP BY '.$cols.' HAVING COUNT(*)>1 LIMIT 1')->num_rows) {
                throw new RuntimeException('Hay duplicados en '.$t.' ('.implode(', ',$columnas).'). Resuélvelos antes de crear '.$nombre.'. No se borraron registros.');
            }
        }
        $this->operacion('índice '.$t.'.'.$nombre,'ALTER TABLE `'.$t.'` ADD '.$definicion);
    }
    public function relacion($t,$nombre,$hijas,$padre,$padres,$definicion)
    {
        if (!$this->tabla($t) || !$this->tabla($padre)) { return; }
        foreach ($hijas as $c) { if (!$this->columna($t,$c)) { return; } }
        foreach ($padres as $c) { if (!$this->columna($padre,$c)) { return; } }
        // Nombres de tablas y campos proceden únicamente del schema.sql del proyecto.
        $s=$this->db->prepare('SELECT CONSTRAINT_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY ORDINAL_POSITION');
        $s->bind_param('s',$t);$s->execute();$existentes=array();
        foreach ($s->get_result() as $f) { $existentes[$f['CONSTRAINT_NAME']][]=$f; }
        foreach ($existentes as $n=>$campos) {
            if (array_column($campos,'COLUMN_NAME')===$hijas && array_column($campos,'REFERENCED_COLUMN_NAME')===$padres && $campos[0]['REFERENCED_TABLE_NAME']===$padre) { return; }
            if ($n===$nombre) { throw new RuntimeException('La relación '.$nombre.' tiene otra definición. Requiere revisión manual.'); }
        }
        $on=array();$where=array();
        foreach ($hijas as $i=>$c) { $on[]='h.`'.$c.'`=p.`'.$padres[$i].'`';$where[]='h.`'.$c.'` IS NOT NULL'; }
        if ($this->db->query('SELECT 1 FROM `'.$t.'` h LEFT JOIN `'.$padre.'` p ON '.implode(' AND ',$on).' WHERE '.implode(' AND ',$where).' AND p.`'.$padres[0].'` IS NULL LIMIT 1')->num_rows) {
            throw new RuntimeException('Hay registros sin referencia válida para '.$nombre.'. Corrige los datos antes de continuar.');
        }
        $this->operacion('relación '.$nombre,'ALTER TABLE `'.$t.'` ADD '.$definicion);
    }
}

try {
    $c=require $root.'/application/config/local.php';
    $db=new mysqli($c['db_host'],$c['db_user'],$c['db_pass'],$c['db_name'],(int)$c['db_port']);
    $db->set_charset('utf8mb4');
    $db->query("SET SESSION sql_mode='STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
    if ($aplicar) {
        $lock='gn_migracion_'.md5($c['db_name']);
        $st=$db->prepare('SELECT GET_LOCK(?,0) adquirido');$st->bind_param('s',$lock);$st->execute();
        if ((int)$st->get_result()->fetch_assoc()['adquirido']!==1) { throw new RuntimeException('Ya hay otra actualización en curso.'); }
    }
    $a=new Actualizador($db,$aplicar);
    foreach (array('empresas','usuarios','productos') as $base) {
        if (!$a->tabla($base)) { throw new RuntimeException('Falta la tabla base '.$base.'. Para una base nueva usa tools/instalar.php.'); }
    }
    // Un cobro antiguo sin detalle no permite deducir método, cuenta ni propina.
    if ($a->tabla('pedidos')) {
        if (!$a->tabla('pagos') && $db->query("SELECT id FROM pedidos WHERE estado_pago<>'pendiente' LIMIT 1")->num_rows) {
            throw new RuntimeException('Hay pedidos marcados con pagos anteriores sin historial. Revisa esos registros antes de migrar; no se inventarán cobros ni se reiniciarán sus estados.');
        }
        if ($a->tabla('pagos') && !$a->columna('pagos','propina') && $db->query('SELECT id FROM pagos LIMIT 1')->num_rows) {
            throw new RuntimeException('Hay pagos de otra versión sin desglose de propina. Se necesita conciliarlos antes de actualizar.');
        }
    }
    echo 'Base: '.$c['db_name']."\n";
    echo $aplicar ? "Actualización en curso. Conserva tu respaldo hasta probar el sistema.\n" : "Diagnóstico: no modifica la base.\n";
    $schema=file_get_contents($root.'/database/schema.sql');
    preg_match_all('/CREATE\\s+TABLE\\s+(\\w+)\\s*\\([\\s\\S]+?;/i',$schema,$matches,PREG_SET_ORDER);
    $creaciones=array();
    foreach ($matches as $m) { $creaciones[$m[1]]=$m[0]; }
    foreach ($db->query('SHOW TABLE STATUS') as $tabla) {
        if (isset($creaciones[$tabla['Name']]) && strcasecmp((string)$tabla['Engine'],'InnoDB')!==0) {
            throw new RuntimeException('La tabla '.$tabla['Name'].' necesita InnoDB para proteger las transacciones. Revisa su conversión antes de continuar.');
        }
    }
    // Crear primero catálogos básicos. Las columnas de tablas antiguas se agregan después.
    foreach (array('roles','permisos','rol_permisos','login_intentos') as $t) { $a->crear($t,$creaciones[$t]); }
    $roles=array('administrador'=>'Administrador','cajero'=>'Cajero','cocinero'=>'Cocinero');
    $nuevosRoles=array();
    foreach ($roles as $codigo=>$nombre) {
        if (!$a->tabla('roles')) { continue; }
        $q=$db->query("SELECT id FROM roles WHERE codigo='".$codigo."'");
        if (!$q->num_rows) { $a->operacion('rol '.$codigo,"INSERT INTO roles (codigo,nombre,descripcion) VALUES ('$codigo','$nombre','Rol del sistema')");$nuevosRoles[]=$codigo; }
    }
    $a->agregar('usuarios','rol_id','INT UNSIGNED NULL');
    if ($a->columna('usuarios','rol_id')) {
        if ($db->query('SELECT id FROM usuarios WHERE rol_id IS NULL LIMIT 1')->num_rows) {
            $a->operacion('asignar administrador a usuarios previos a RBAC (regla de migración 001)',"UPDATE usuarios SET rol_id=(SELECT id FROM roles WHERE codigo='administrador') WHERE rol_id IS NULL");
        }
        $nullable=$db->query("SHOW COLUMNS FROM usuarios LIKE 'rol_id'")->fetch_assoc();
        if ($nullable['Null']==='YES') { $a->operacion('usuarios.rol_id obligatorio','ALTER TABLE usuarios MODIFY rol_id INT UNSIGNED NOT NULL'); }
    }
    $a->agregar('empresas','ultimo_numero_pedido','INT UNSIGNED NOT NULL DEFAULT 0');
    $a->crear('insumos',$creaciones['insumos']);
    $a->agregar('insumos','fecha_vencimiento','DATE NULL');
    $a->agregar('productos','insumo_id','INT UNSIGNED NULL');
    $a->crear('pedidos',$creaciones['pedidos']);
    $a->agregar('pedidos','numero','INT UNSIGNED NULL AFTER empresa_id');
    if ($a->columna('pedidos','numero')) {
        $faltan=(int)$db->query('SELECT COUNT(*) n FROM pedidos WHERE numero IS NULL')->fetch_assoc()['n'];
        if ($faltan) {
            if ($db->query('SELECT id FROM pedidos WHERE numero IS NOT NULL LIMIT 1')->num_rows) {
                throw new RuntimeException('Numeración parcialmente asignada. Revisión manual necesaria para conservar los números ya utilizados.');
            }
            $a->operacion('numerar pedidos por empresa (solo si ninguno tiene número)',
                'UPDATE pedidos p JOIN (SELECT x.id, COUNT(y.id) numero FROM pedidos x JOIN pedidos y ON y.empresa_id=x.empresa_id AND y.id<=x.id GROUP BY x.id) n ON n.id=p.id SET p.numero=n.numero');
        }
        $nullable=$db->query("SHOW COLUMNS FROM pedidos LIKE 'numero'")->fetch_assoc();
        if ($nullable['Null']==='YES') { $a->operacion('pedido.numero obligatorio','ALTER TABLE pedidos MODIFY numero INT UNSIGNED NOT NULL'); }
        if ($a->columna('empresas','ultimo_numero_pedido') && $db->query('SELECT e.id FROM empresas e JOIN pedidos p ON p.empresa_id=e.id WHERE p.numero>e.ultimo_numero_pedido LIMIT 1')->num_rows) {
            $a->operacion('actualizar contadores sin reducir los ya usados','UPDATE empresas e JOIN (SELECT empresa_id,MAX(numero) ultimo FROM pedidos GROUP BY empresa_id) p ON p.empresa_id=e.id SET e.ultimo_numero_pedido=GREATEST(e.ultimo_numero_pedido,p.ultimo)');
        }
    }
    // Los índices padres deben existir antes de crear tablas que los referencian.
    $lista=function($txt) { return array_map('trim',explode(',',str_replace('`','',$txt))); };
    $indices=function($t,$sql) use ($a,$lista) {
        preg_match_all('/(UNIQUE\\s+KEY|KEY|INDEX)\\s+(\\w+)\\s*\\(([^)]+)\\)/i',$sql,$ims,PREG_SET_ORDER);
        foreach ($ims as $i) { $a->indice($t,$i[2],$lista($i[3]),stripos($i[1],'UNIQUE')===0,$i[0]); }
    };
    foreach (array('usuarios','insumos','productos','pedidos') as $t) { $indices($t,$creaciones[$t]); }
    foreach (array('pedido_detalles','pedido_cancelaciones','movimientos_inventario') as $t) { $a->crear($t,$creaciones[$t]); }
    $a->agregar('movimientos_inventario','pedido_id','INT UNSIGNED NULL AFTER usuario_id');
    if ($a->tabla('movimientos_inventario') && $a->tipo('movimientos_inventario','tipo')!=="enum('entrada','salida','ajuste')") {
        if ($db->query("SELECT id FROM movimientos_inventario WHERE tipo NOT IN ('entrada','salida','ajuste') LIMIT 1")->num_rows) { throw new RuntimeException('Hay tipos de movimiento desconocidos. Revísalos antes de continuar.'); }
        $a->operacion('permitir ajustes en inventario',"ALTER TABLE movimientos_inventario MODIFY tipo ENUM('entrada','salida','ajuste') NOT NULL");
    }
    $indices('pedido_detalles',$creaciones['pedido_detalles']);
    foreach (array('pago_cuentas','pago_cuenta_detalles','pagos') as $t) { $a->crear($t,$creaciones[$t]); }
    foreach (array('pago_cuentas','pagos') as $t) { $a->agregar($t,'propina','DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0'); }
    foreach ($creaciones as $t=>$sql) {
        $indices($t,$sql);
        preg_match_all('/CONSTRAINT\\s+(\\w+)\\s+FOREIGN\\s+KEY\\s*\\(([^)]+)\\)\\s+REFERENCES\\s+(\\w+)\\s*\\(([^)]+)\\)(?:\\s+ON\\s+DELETE\\s+CASCADE)?/i',$sql,$fks,PREG_SET_ORDER);
        foreach ($fks as $fk) { $a->relacion($t,$fk[1],$lista($fk[2]),$fk[3],$lista($fk[4]),$fk[0]); }
        if (!$a->tabla($t)) { continue; }
        // Detectar variantes no contempladas en lugar de declarar éxito engañoso.
        preg_match_all('/^\\s*(\\w+)\\s+(?:INT|BIGINT|VARCHAR|CHAR|TINYINT|DECIMAL|DATETIME|DATE|ENUM)\\b/im',$sql,$cols);
        foreach ($cols[1] as $col) {
            if (!$a->columna($t,$col)) {
                if ($aplicar) { throw new RuntimeException('Falta una columna no contemplada: '.$t.'.'.$col.'. Requiere revisión manual.'); }
                echo 'Comprobar después de aplicar: '.$t.'.'.$col."\n";
            }
        }
    }
    // Recuperar catálogos faltantes. No restablece permisos que alguien haya revocado.
    $permisos=array(
        'inicio.ver','usuarios.ver','usuarios.gestionar','productos.ver','productos.gestionar',
        'inventario.ver','inventario.gestionar','proveedores.ver','proveedores.gestionar',
        'pedidos.ver','pedidos.crear','pedidos.confirmar','pedidos.actualizar_cocina','pedidos.cancelar',
        'ventas.ver','ventas.cobrar','reportes.ver','empresa.configurar'
    );
    $cajero=array('inicio.ver','productos.ver','pedidos.ver','pedidos.crear','pedidos.confirmar','ventas.ver','ventas.cobrar');
    $cocinero=array('inicio.ver','productos.ver','pedidos.ver','pedidos.actualizar_cocina');
    if ($a->tabla('permisos') && $a->tabla('rol_permisos')) {
        foreach ($permisos as $codigo) {
            $nuevo=!$db->query("SELECT id FROM permisos WHERE codigo='$codigo'")->num_rows;
            if ($nuevo) { $a->operacion('permiso '.$codigo,"INSERT INTO permisos (codigo,nombre,descripcion) VALUES ('$codigo','$codigo','Permiso del sistema')"); }
            foreach ($roles as $rol=>$nombre) {
                $corresponde=$rol==='administrador' || ($rol==='cajero' && in_array($codigo,$cajero,TRUE)) || ($rol==='cocinero' && in_array($codigo,$cocinero,TRUE));
                if ($corresponde && ($nuevo || in_array($rol,$nuevosRoles,TRUE))) {
                    $a->operacion('asignar nuevo permiso '.$codigo.' a '.$rol,"INSERT IGNORE INTO rol_permisos (rol_id,permiso_id) SELECT r.id,p.id FROM roles r CROSS JOIN permisos p WHERE r.codigo='$rol' AND p.codigo='$codigo'");
                }
            }
        }
    }
    if ($aplicar) {
        echo "Actualización terminada. Ejecuta de nuevo sin --aplicar: debe indicar Sin cambios pendientes.\n";
    } elseif ($a->pendientes) {
        echo "\nHay cambios pendientes. Respalda la base, detén Apache (mantén MySQL activo) y ejecuta:\nphp tools/actualizar.php --aplicar\n";
        echo "El diagnóstico de una base antigua puede mostrar nuevas dependencias al aplicar; se comprueban antes de cada cambio.\n";
    } else { echo "Sin cambios pendientes. Estructura compatible con las migraciones 001 a 008.\n"; }
} catch (Throwable $e) {
    fwrite(STDERR,"DETENIDO: ".$e->getMessage()."\nNo se eliminan datos. Si estabas aplicando cambios, algunos DDL pueden haber quedado realizados; no son una transacción global. Corrige el problema y repite el diagnóstico.\n");
    exit(1);
}
