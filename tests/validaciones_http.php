<?php
/** RF-61/62: aceptación HTTP con CodeIgniter real, dos empresas y tres roles.
 * Usa una copia temporal de la aplicación y una base vacía de nombre aleatorio.
 * No modifica local.php ni la base del negocio. Requiere PHP CLI, curl, mysqli,
 * mbstring, proc_open y permiso CREATE/DROP DATABASE en el MySQL de local.php.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASEPATH', dirname(__DIR__).'/system/');
$root=dirname(__DIR__);$db=NULL;$creada=FALSE;$proceso=NULL;$temp=NULL;$base=NULL;$codigo=0;$pasadas=0;
$reporte=array('inicio'=>date(DATE_ATOM),'resultado'=>'FALLO','casos'=>array());
function exigir($ok,$caso) {
    global $pasadas,$reporte;
    $reporte['casos'][]=array('caso'=>$caso,'ok'=>(bool)$ok);
    if (!$ok) { throw new RuntimeException($caso); }
    $pasadas++;echo "OK: $caso\n";
}
function copiar_arbol($origen,$destino) {
    if (!mkdir($destino,0700,TRUE) && !is_dir($destino)) { throw new RuntimeException('No se pudo crear el entorno temporal.'); }
    foreach (new DirectoryIterator($origen) as $f) {
        if ($f->isDot() || $f->isLink() || in_array($f->getFilename(),array('local.php','development','logs','cache'),TRUE)) { continue; }
        $d=$destino.'/'.$f->getFilename();
        if ($f->isDir()) { copiar_arbol($f->getPathname(),$d); }
        elseif (!copy($f->getPathname(),$d)) { throw new RuntimeException('No se pudo copiar un archivo de la aplicación.'); }
    }
}
function borrar_arbol($dir) {
    if (!is_dir($dir)) { return; }
    foreach (new DirectoryIterator($dir) as $f) {
        if ($f->isDot()) { continue; }
        if ($f->isDir() && !$f->isLink()) { borrar_arbol($f->getPathname()); }
        else { unlink($f->getPathname()); }
    }
    for ($intento=0;$intento<10 && is_dir($dir);$intento++) {
        if (@rmdir($dir)) { break; }
        usleep(100000);
    }
}
function huella() {
    global $db;
    $datos=array();$tablas=$db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM);
    foreach ($tablas as $t) {
        // Intentos de acceso es un contador de autenticación, no un dato de negocio.
        if (strpos($t[0],'intento')!==FALSE) { continue; }
        $filas=$db->query('SELECT * FROM `'.$t[0].'`')->fetch_all(MYSQLI_ASSOC);
        $serializadas=array_map('serialize',$filas);sort($serializadas);
        $datos[$t[0]]=$serializadas;
    }
    ksort($datos);return hash('sha256',serialize($datos));
}
function fila($sql) { global $db;return $db->query($sql)->fetch_assoc(); }
final class ClienteValidacion {
    private $c;private $base;
    public function __construct($base) {
        $this->base=$base.'index.php/';$this->c=curl_init();
        curl_setopt_array($this->c,array(CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_FOLLOWLOCATION=>FALSE,
            CURLOPT_COOKIEFILE=>'',CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>15));
    }
    public function pedir($ruta,$datos=NULL) {
        curl_setopt($this->c,CURLOPT_URL,$this->base.$ruta);
        if ($datos===NULL) { curl_setopt($this->c,CURLOPT_HTTPGET,TRUE); }
        else { curl_setopt($this->c,CURLOPT_POST,TRUE);curl_setopt($this->c,CURLOPT_POSTFIELDS,http_build_query($datos)); }
        $body=curl_exec($this->c);
        if ($body===FALSE) { throw new RuntimeException('HTTP: '.curl_error($this->c)); }
        if (preg_match('/A PHP Error was encountered|Fatal error:|Parse error:|Uncaught .*Error|Severity: (Warning|Notice)/i',$body)) {
            throw new RuntimeException('Error PHP en '.$ruta.'. Consulta el registro temporal de la prueba.');
        }
        return array('status'=>(int)curl_getinfo($this->c,CURLINFO_HTTP_CODE),'body'=>$body,
            'destino'=>curl_getinfo($this->c,CURLINFO_REDIRECT_URL));
    }
    public static function campo($html,$nombre) {
        if (!preg_match('/<input\b[^>]*\bname="'.preg_quote($nombre,'/').'"[^>]*\bvalue="([^"]*)"/s',$html,$m)) {
            throw new RuntimeException('No se encontró el campo '.$nombre.'.');
        }
        return html_entity_decode($m[1],ENT_QUOTES,'UTF-8');
    }
    public function post($ruta,$datos,$formulario='inicio') {
        $form=$this->pedir($formulario);
        $ruta_formulario = NULL;
        if (preg_match('~^(inventario|usuarios|proveedores)/[0-9]+/editar$~',$ruta) || preg_match('~^productos/editar/[0-9]+$~',$ruta)) { $ruta_formulario=$ruta; }
        if ($ruta_formulario !== NULL && !array_key_exists('version_edicion',$datos)) {
            $edicion=$this->pedir($ruta_formulario);
            if ($edicion['status']===200) { $datos['version_edicion']=self::campo($edicion['body'],'version_edicion'); }
        }
        if ($form['status']!==200) { throw new RuntimeException('No abrió formulario '.$formulario.': HTTP '.$form['status']); }
        $form=$this->pedir($formulario);
        $datos['gestion_csrf']=self::campo($form['body'],'gestion_csrf');
        return $this->pedir($ruta,$datos);
    }
    public function post_archivo($ruta,$archivo,$formulario) {
        $form=$this->pedir($formulario);
        if ($form['status']!==200) { throw new RuntimeException('No abrió formulario '.$formulario.': HTTP '.$form['status']); }
        curl_setopt($this->c,CURLOPT_URL,$this->base.$ruta);
        curl_setopt($this->c,CURLOPT_POST,TRUE);
        curl_setopt($this->c,CURLOPT_POSTFIELDS,array(
            'gestion_csrf'=>self::campo($form['body'],'gestion_csrf'),
            'imagen'=>new CURLFile($archivo,'image/png','foto.png')
        ));
        $body=curl_exec($this->c);
        if ($body===FALSE) { throw new RuntimeException('HTTP: '.curl_error($this->c)); }
        if (preg_match('/A PHP Error was encountered|Fatal error:|Parse error:|Uncaught .*Error|Severity: (Warning|Notice)/i',$body)) {
            throw new RuntimeException('Error PHP en '.$ruta.'. Consulta el registro temporal de la prueba.');
        }
        return array('status'=>(int)curl_getinfo($this->c,CURLINFO_HTTP_CODE),'body'=>$body,
            'destino'=>curl_getinfo($this->c,CURLINFO_REDIRECT_URL));
    }
    public function seguir($r) {
        if (strpos($r['destino'],$this->base)!==0) { throw new RuntimeException('Redirección inesperada: '.$r['destino']); }
        return $this->pedir(substr($r['destino'],strlen($this->base)));
    }
    public function entrar($nombre,$password) {
        $r=$this->post('login',array('identificador'=>$nombre,'password'=>$password),'login');
        exigir(in_array($r['status'],array(302,303,307),TRUE) && strpos($r['destino'],'/inicio')!==FALSE,'Login '.$nombre);
    }
    public function invalido($ruta,$datos,$caso,$formulario='inicio') {
        $antes=huella();$r=$this->post($ruta,$datos,$formulario);
        $rechazado=in_array($r['status'],array(400,409,422),TRUE);
        if ($r['status']===303) {
            $pantalla=$this->seguir($r);
            $rechazado=$pantalla['status']===200 && preg_match('/class="[^"]*\berror\b/',$pantalla['body']);
        }
        exigir($rechazado && huella()===$antes,$caso.' (rechazo y base sin cambios)');
    }
    public function __destruct() { curl_close($this->c); }
}
try {
    foreach (array('curl','mysqli','mbstring') as $ext) {
        if (!extension_loaded($ext)) { throw new RuntimeException('Falta extensión '.$ext.'. Usa PHP de XAMPP.'); }
    }
    if (!function_exists('proc_open')) { throw new RuntimeException('Habilita proc_open en PHP CLI para iniciar el servidor temporal.'); }
    $config=require $root.'/application/config/local.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db=new mysqli($config['db_host'],$config['db_user'],$config['db_pass'],'',(int)$config['db_port']);
    $db->set_charset('utf8mb4');
    $base='gn_validacion_test_'.bin2hex(random_bytes(8));
    $db->query('CREATE DATABASE `'.$base.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$creada=TRUE;
    $db->select_db($base);$db->multi_query(file_get_contents($root.'/database/schema.sql'));
    do { if ($res=$db->store_result()) { $res->free(); } } while ($db->more_results() && $db->next_result());
    $db->query("INSERT INTO empresas (id,nombre) VALUES (1,'Prueba A'),(2,'Prueba B')");
    $password=bin2hex(random_bytes(12));$hash=password_hash($password,PASSWORD_BCRYPT);
    foreach (array(array(1,1,'administrador','admin_a'),array(2,2,'administrador','admin_b'),array(3,1,'cajero','cajero_a'),array(4,1,'cocinero','cocinero_a')) as $u) {
        $q=$db->prepare('INSERT INTO usuarios (id,empresa_id,rol_id,nombre,nombre_usuario,correo,password_hash) SELECT ?,?,id,?,?,?,? FROM roles WHERE codigo=?');
        $correo=$u[3].'@prueba.test';$q->bind_param('iisssss',$u[0],$u[1],$u[3],$u[3],$correo,$hash,$u[2]);$q->execute();
    }
    $db->query("INSERT INTO proveedores (id,empresa_id,nombre) VALUES (1,1,'Proveedor A'),(2,2,'Proveedor B')");
    $db->query("INSERT INTO insumos (id,empresa_id,nombre,cantidad_actual,stock_minimo,unidad_medida,proveedor_id) VALUES (1,1,'Harina',10,2,'kg',1),(2,1,'Sal',1,0,'kg',NULL),(3,2,'Insumo B',10,1,'kg',2)");
    $db->query("INSERT INTO productos (id,empresa_id,sku,nombre,precio) VALUES (1,1,'A','Producto A',10),(2,2,'B','Producto B',20)");
    $temp=sys_get_temp_dir().DIRECTORY_SEPARATOR.$base;
    mkdir($temp,0700);
    foreach (array('application','system','assets') as $dir) { copiar_arbol($root.'/'.$dir,$temp.'/'.$dir); }
    mkdir($temp.'/application/logs',0700);mkdir($temp.'/application/cache',0700);
    copy($root.'/index.php',$temp.'/index.php');
    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr);
    if (!$socket) { throw new RuntimeException('No hay puerto local disponible.'); }
    $direccion=stream_socket_get_name($socket,FALSE);fclose($socket);
    $url='http://'.$direccion.'/';$config['base_url']=$url;$config['db_name']=$base;$config['cookie_secure']=FALSE;
    file_put_contents($temp.'/application/config/local.php',"<?php\nreturn ".var_export($config,TRUE).";\n");
    // Las sesiones de la copia se eliminan junto con el resto del entorno temporal.
    mkdir($temp.'/sesiones',0700);
    file_put_contents($temp.'/application/config/config.php',"\n\$config['sess_save_path'] = ".var_export($temp.'/sesiones',TRUE).";\n",FILE_APPEND);
    $proceso=proc_open(array(PHP_BINARY,'-d','display_errors=1','-S',$direccion,'-t',$temp),
        array(0=>array('pipe','r'),1=>array('file',$temp.'/servidor.log','a'),2=>array('file',$temp.'/servidor.log','a')),$pipes,$temp);
    if (!is_resource($proceso)) { throw new RuntimeException('No se pudo iniciar PHP temporal.'); }
    fclose($pipes[0]);$listo=FALSE;
    for ($i=0;$i<50;$i++) {
        $s=@stream_socket_client('tcp://'.$direccion,$errno,$errstr,0.1);
        if ($s) { fclose($s);$listo=TRUE;break; } usleep(100000);
    }
    if (!$listo) { throw new RuntimeException('El servidor temporal no inició.'); }
    $a=new ClienteValidacion($url);$b=new ClienteValidacion($url);$caja=new ClienteValidacion($url);$cocina=new ClienteValidacion($url);
    $invitado=new ClienteValidacion($url);
    $r=$invitado->pedir('inventario');exigir(in_array($r['status'],array(302,303,307),TRUE) && strpos($r['destino'],'/login')!==FALSE,'Inventario exige sesión');
    foreach (array(array('identificador'=>array('admin_a'),'password'=>$password),array('identificador'=>'admin_a','password'=>array('x')),array('identificador'=>'','password'=>'')) as $mal) {
        $r=$invitado->post('login',$mal,'login');exigir($r['status']===422,'Login rechaza campos vacíos o anidados');
    }
    $a->entrar('admin_a',$password);$b->entrar('admin_b',$password);$caja->entrar('cajero_a',$password);$cocina->entrar('cocinero_a',$password);
    $foto=$temp.'/foto.png';
    file_put_contents($foto,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6a1sAAAAASUVORK5CYII='));
    exigir(strpos($a->pedir('inventario/1/editar')['body'],'Sin imagen')!==FALSE,'Insumo sin foto muestra marcador');
    $r=$a->post_archivo('inventario/1/imagen',$foto,'inventario/1/editar');
    $imagen_insumo=fila('SELECT imagen FROM insumos WHERE id=1')['imagen'];
    exigir($r['status']===303 && (bool)preg_match('/^[a-f0-9]{32}\.png$/',$imagen_insumo),'Subir foto opcional de insumo');
    $r=$a->pedir('inventario/1/imagen');
    exigir($r['status']===200 && substr($r['body'],0,8)==="\x89PNG\r\n\x1a\n",'Foto de insumo se entrega por ruta autenticada (HTTP '.$r['status'].', bytes '.bin2hex(substr($r['body'],0,8)).')');
    exigir($b->pedir('inventario/1/imagen')['status']===404,'Otra empresa no ve la foto de insumo');
    $r=$a->post('inventario/1/imagen',array('quitar'=>'1'),'inventario/1/editar');
    exigir($r['status']===303 && fila('SELECT imagen FROM insumos WHERE id=1')['imagen']===NULL,'Quitar foto del insumo restaura marcador');
    exigir($a->pedir('configuracion/propina')['status']===404,'La configuración global de propina ya no está disponible');
    foreach (array('usuarios','ventas','compras','proveedores/1/editar') as $ruta) {
        exigir($cocina->pedir($ruta)['status']===403,'Cocinero sin permiso: '.$ruta);
    }
    exigir($caja->pedir('usuarios')['status']===403,'Cajero no administra usuarios');
    $antes=huella();$r=$a->pedir('usuarios/crear',array('nombre'=>'Sin token'));exigir($r['status']===403 && huella()===$antes,'CSRF impide guardar sin token');
    foreach (array('inventario/1/editar','proveedores/1/editar') as $ruta) { exigir($b->pedir($ruta)['status']===404,'Empresa B no consulta '.$ruta); }

    $rol=(string)fila("SELECT id FROM roles WHERE codigo='cajero'")['id'];
    $usuario=array('nombre'=>'Nuevo usuario','nombre_usuario'=>'nuevo_usuario','correo'=>'nuevo@prueba.test','rol_id'=>$rol,'password'=>'Clave-prueba-123');
    foreach (array('nombre','nombre_usuario','correo','rol_id','password') as $campo) {
        $d=$usuario;unset($d[$campo]);$a->invalido('usuarios/crear',$d,'Usuario exige '.$campo);
        $d=$usuario;$d[$campo]=array('x');$a->invalido('usuarios/crear',$d,'Usuario rechaza arreglo en '.$campo);
    }
    foreach (array('        ',str_repeat('x',73)) as $pass) {
        $d=$usuario;$d['password']=$pass;$a->invalido('usuarios/crear',$d,'Contraseña inválida');
    }
    $d=$usuario;$d['rol_id']='999999';$a->invalido('usuarios/crear',$d,'Rol inexistente');
    $d=$usuario;$d['correo']='sin-arroba';$a->invalido('usuarios/crear',$d,'Correo inválido');
    $r=$a->post('usuarios/crear',$usuario);exigir(in_array($r['status'],array(302,303),TRUE) && (int)fila("SELECT COUNT(*) n FROM usuarios WHERE nombre_usuario='nuevo_usuario' AND empresa_id=1")['n']===1,'Registro válido de usuario');
    $uid=(int)fila("SELECT id FROM usuarios WHERE nombre_usuario='nuevo_usuario'")['id'];
    $a->invalido('usuarios/'.$uid.'/editar',array('nombre'=>' ','nombre_usuario'=>'nuevo_usuario','rol_id'=>$rol),'Edición exige nombre');
    $a->invalido('usuarios/crear',$usuario,'Usuario duplicado');

    $proveedor=array('nombre'=>'Proveedor válido','contacto'=>'','telefono'=>'','correo'=>'','direccion'=>'');
    foreach (array('nombre','contacto','telefono','correo','direccion') as $campo) {
        $d=$proveedor;$d[$campo]=array('x');$a->invalido('proveedores/1/editar',$d,'Edición de proveedor rechaza arreglo en '.$campo);
        $a->invalido('compras/proveedor_nuevo',$d,'Alta de proveedor rechaza arreglo en '.$campo);
    }
    $d=$proveedor;$d['nombre']=' ';$a->invalido('proveedores/1/editar',$d,'Proveedor exige nombre');
    $d=$proveedor;$d['correo']='correo-invalido';$a->invalido('proveedores/1/editar',$d,'Proveedor valida correo');
    $r=$a->post('proveedores/1/editar',$proveedor);exigir($r['status']===303 && fila('SELECT nombre FROM proveedores WHERE id=1')['nombre']==='Proveedor válido','Proveedor permite contactos opcionales vacíos');
    $a->invalido('proveedores/1/asociar',array('insumo_id'=>'3'),'Asociación rechaza insumo ajeno');

    $insumo=array('nombre'=>'Nuevo insumo','cantidad_actual'=>'0','stock_minimo'=>'0','unidad_medida'=>'unidad','fecha_vencimiento'=>'');
    foreach (array('nombre','cantidad_actual','stock_minimo','unidad_medida') as $campo) {
        $d=$insumo;unset($d[$campo]);$a->invalido('inventario/crear',$d,'Insumo exige '.$campo);
        $d=$insumo;$d[$campo]=array('1');$a->invalido('inventario/crear',$d,'Insumo rechaza arreglo en '.$campo);
    }
    foreach (array('-1','1e2','1,25','1.001','100000000') as $cantidad) {
        $d=$insumo;$d['cantidad_actual']=$cantidad;$a->invalido('inventario/crear',$d,'Existencia inicial inválida: '.$cantidad);
    }
    foreach (array('2026-02-30','0001-01-01','9999-12-31') as $fecha) {
        $d=$insumo;$d['fecha_vencimiento']=$fecha;$a->invalido('inventario/crear',$d,'Vencimiento inválido: '.$fecha);
    }
    $r=$a->post('inventario/crear',$insumo);exigir(in_array($r['status'],array(302,303),TRUE) && fila("SELECT cantidad_actual FROM insumos WHERE nombre='Nuevo insumo'")['cantidad_actual']==='0.00','Inventario acepta cero inicial');
    // El vencimiento debe sobrevivir al recorrido guardar -> lista -> volver a editar.
    $d=$insumo;$d['nombre']='Insumo con vencimiento';$d['fecha_vencimiento']='2028-02-29';
    $r=$a->post('inventario/crear',$d,'inventario/crear');
    exigir(in_array($r['status'],array(302,303),TRUE),'Crear insumo con fecha válida bisiesta');
    $fecha_id=(int)fila("SELECT id FROM insumos WHERE nombre='Insumo con vencimiento' AND empresa_id=1")['id'];
    $editar_fecha='inventario/'.$fecha_id.'/editar';
    $r=$a->pedir($editar_fecha);
    exigir($r['status']===200 && ClienteValidacion::campo($r['body'],'fecha_vencimiento')==='2028-02-29' && strpos($r['body'],'quitar_fecha_vencimiento')===FALSE,'Al reabrir la edición se carga el vencimiento sin casilla adicional');
    $d['stock_minimo']='2';unset($d['cantidad_actual']);
    $r=$a->post($editar_fecha,$d,$editar_fecha);$a->pedir('inventario');$r=$a->pedir($editar_fecha);
    exigir($r['status']===200 && ClienteValidacion::campo($r['body'],'fecha_vencimiento')==='2028-02-29','Editar otro dato y volver al menú conserva la fecha');
    unset($d['fecha_vencimiento']);$r=$a->post($editar_fecha,$d,$editar_fecha);
    exigir(in_array($r['status'],array(302,303),TRUE) && fila('SELECT fecha_vencimiento FROM insumos WHERE id='.$fecha_id)['fecha_vencimiento']==='2028-02-29','Omitir el campo no elimina la fecha existente');
    $d['fecha_vencimiento']='';$r=$a->post($editar_fecha,$d,$editar_fecha);
    exigir(in_array($r['status'],array(302,303),TRUE) && fila('SELECT fecha_vencimiento FROM insumos WHERE id='.$fecha_id)['fecha_vencimiento']===NULL,'Vaciar el campo y guardar elimina la fecha');
    $r=$a->pedir($editar_fecha);
    exigir($r['status']===200 && ClienteValidacion::campo($r['body'],'fecha_vencimiento')==='','Al volver a editar una fecha eliminada el campo sigue vacío');
    $d['fecha_vencimiento']='2029-03-15';$mal=$d;$mal['stock_minimo']='-1';$antes=huella();
    $r=$a->post($editar_fecha,$mal,$editar_fecha);
    exigir($r['status']===422 && huella()===$antes && ClienteValidacion::campo($r['body'],'fecha_vencimiento')==='2029-03-15','Un error en otro campo conserva la fecha escrita y no cambia la base');
    $r=$a->post($editar_fecha,$d,$editar_fecha);$r=$a->pedir($editar_fecha);
    exigir($r['status']===200 && ClienteValidacion::campo($r['body'],'fecha_vencimiento')==='2029-03-15','La fecha puede modificarse y permanece al volver a editar');
    foreach(array('2029-02-29',array('2029-03-15')) as $fecha_mal){$mal=$d;$mal['fecha_vencimiento']=$fecha_mal;$a->invalido($editar_fecha,$mal,'Editar rechaza vencimiento inválido');}
    $mal=$d;$mal['fecha_vencimiento']='';$mal['stock_minimo']='-1';$antes=huella();$r=$a->post($editar_fecha,$mal,$editar_fecha);
    exigir($r['status']===422 && huella()===$antes && ClienteValidacion::campo($r['body'],'fecha_vencimiento')==='','Vaciar la fecha conserva el campo vacío ante otro error sin modificar la base');
    $d['fecha_vencimiento']='';$r=$a->post($editar_fecha,$d,$editar_fecha);$r=$a->pedir($editar_fecha);
    exigir($r['status']===200 && ClienteValidacion::campo($r['body'],'fecha_vencimiento')==='' && fila('SELECT fecha_vencimiento FROM insumos WHERE id='.$fecha_id)['fecha_vencimiento']===NULL,'Guardar vacío tras corregir el error elimina la fecha sin casillas adicionales');
    $a->invalido('inventario/1/movimiento',array('tipo'=>'salida','cantidad'=>'11','motivo'=>'Prueba'),'Movimiento no deja existencia negativa');
    $a->invalido('inventario/1/movimiento',array('tipo'=>'entrada','cantidad'=>'1','motivo'=>' '),'Movimiento exige motivo');
    $a->invalido('inventario/vender/2',array('sku'=>'SAL','precio'=>'-1'),'Producto rechaza precio negativo');
    $a->invalido('inventario/vender/2',array('sku'=>'','precio'=>'1'),'Producto exige código');
    $r=$a->post('inventario/vender/2',array('sku'=>'SAL','precio'=>'0'));exigir(in_array($r['status'],array(302,303),TRUE) && fila("SELECT precio FROM productos WHERE sku='SAL'")['precio']==='0.00','Producto admite precio cero');

    foreach (array('', '   ', array('Mesa')) as $ref) { $a->invalido('pedidos/crear',array('referencia'=>$ref),'Pedido exige referencia válida'); }
    $r=$a->post('pedidos/crear',array('referencia'=>'Pedido aceptación'));exigir(in_array($r['status'],array(302,303),TRUE),'Crear pedido válido');
    $pid=(int)fila("SELECT id FROM pedidos WHERE referencia='Pedido aceptación'")['id'];
    foreach (array('-1','0','1.5','1000',array('1')) as $q) { $a->invalido('pedidos/agregar/'.$pid,array('producto_id'=>'1','cantidad'=>$q),'Pedido rechaza cantidad inválida'); }
    $a->invalido('pedidos/agregar/'.$pid,array('producto_id'=>'2','cantidad'=>'1'),'Pedido rechaza producto ajeno');
    $r=$a->post('pedidos/agregar/'.$pid,array('producto_id'=>'1','cantidad'=>'1'));exigir(in_array($r['status'],array(302,303),TRUE),'Agregar producto válido');
    $r=$a->post('pedidos/'.$pid.'/confirmar',array());exigir(in_array($r['status'],array(302,303),TRUE),'Confirmar pedido válido');
    $cocina->invalido('cocina/pedidos/'.$pid.'/estado',array('estado'=>array('listo')),'Cocina rechaza estado anidado');
    $cocina->invalido('cocina/pedidos/'.$pid.'/estado',array('estado'=>'entregado'),'Cocina no salta estados');
    $r=$cocina->post('cocina/pedidos/'.$pid.'/estado',array('estado'=>'en_preparacion'));exigir(in_array($r['status'],array(302,303),TRUE) && fila('SELECT estado FROM pedidos WHERE id='.$pid)['estado']==='en_preparacion','Cocina acepta transición válida');
    $r=$caja->pedir('pagos/'.$pid);
    exigir($r['status']===200 && strpos($r['body'],'¿Desea dejar propina?')!==FALSE,'El cobro pregunta por la propina');
    $antes=huella();$r=$caja->post('pagos/preparar/'.$pid,array());
    exigir($r['status']===303 && huella()===$antes,'Preparar sin responder la propina no cambia datos');
    $r=$caja->post('pagos/preparar/'.$pid,array('propina_tipo'=>'porcentaje','propina_porcentaje'=>'10'),'pagos/'.$pid);
    exigir($r['status']===303 && fila('SELECT propina_monto FROM pedidos WHERE id='.$pid)['propina_monto']==='1.00','El cliente acepta 10 % al cobrar');
    $caja->invalido('pagos/preparar/'.$pid,array('propina_tipo'=>'porcentaje','propina_porcentaje'=>'101'),'Porcentaje mayor que 100 se rechaza','pagos/'.$pid);
    $caja->invalido('pagos/preparar/'.$pid,array('propina_tipo'=>'monto','propina_monto'=>'-1'),'Monto negativo de propina se rechaza','pagos/'.$pid);
    $caja->invalido('pagos/preparar/'.$pid,array('propina_tipo'=>array('monto'),'propina_monto'=>'1'),'Tipo de propina anidado se rechaza','pagos/'.$pid);
    $r=$caja->post('pagos/preparar/'.$pid,array('propina_tipo'=>'monto','propina_monto'=>'7.50'),'pagos/'.$pid);
    exigir($r['status']===303 && fila('SELECT propina_monto FROM pedidos WHERE id='.$pid)['propina_monto']==='7.50','La propina admite un monto exacto');
    $r=$caja->post('pagos/preparar/'.$pid,array('propina_tipo'=>'porcentaje','propina_porcentaje'=>'12.5'),'pagos/'.$pid);
    exigir($r['status']===303 && fila('SELECT propina_monto FROM pedidos WHERE id='.$pid)['propina_monto']==='1.25','La propina admite porcentaje decimal');
    $r=$caja->post('pagos/preparar/'.$pid,array('propina_tipo'=>'porcentaje','propina_porcentaje'=>'10'),'pagos/'.$pid);
    exigir($r['status']===303 && fila('SELECT propina_monto FROM pedidos WHERE id='.$pid)['propina_monto']==='1.00','La elección puede cambiar antes del primer pago');
    $cuenta=(string)fila('SELECT id FROM pago_cuentas WHERE pedido_id='.$pid)['id'];
    $pago=array('cuenta_id'=>$cuenta,'efectivo'=>'11','credito'=>'0','debito'=>'0','recibido'=>'11','clave'=>bin2hex(random_bytes(32)));
    foreach (array('efectivo','credito','debito','recibido') as $campo) {
        foreach (array('-1','1.001',array('1'),NULL) as $monto) {
            $d=$pago;$d[$campo]=$monto;$caja->invalido('pagos/registrar/'.$pid,$d,'Pago rechaza '.$campo.' inválido');
        }
    }
    $d=$pago;$d['recibido']='10';$caja->invalido('pagos/registrar/'.$pid,$d,'Efectivo recibido insuficiente');
    $d=$pago;$d['efectivo']='12';$d['recibido']='12';$caja->invalido('pagos/registrar/'.$pid,$d,'Pago mayor al saldo');
    $r=$caja->post('pagos/registrar/'.$pid,$pago);exigir($r['status']===303 && fila('SELECT estado_pago FROM pedidos WHERE id='.$pid)['estado_pago']==='pagado','Pago válido completa pedido y venta');
    $antes=huella();$r=$caja->post('pagos/preparar/'.$pid,array('propina_tipo'=>'ninguna'),'pagos/'.$pid);
    exigir($r['status']===303 && huella()===$antes,'La propina no cambia después de registrar el pago');
    exigir((int)fila('SELECT propina_porcentaje FROM pedidos WHERE id='.$pid)['propina_porcentaje']===10,'El pago conserva la propina elegida');
    $r=$a->post('pedidos/rapido',array('referencia'=>'Prueba rápida','producto_id'=>'1','cantidad'=>'1'),'pedidos/rapido');
    $rapido=fila("SELECT id,estado,propina_porcentaje FROM pedidos WHERE referencia='Prueba rápida'");
    exigir($r['status']===303 && $rapido && $rapido['estado']==='pendiente' && (int)$rapido['propina_porcentaje']===0,'Pedido rápido confirma con propina de 0 %');
    exigir((int)fila('SELECT COUNT(*) n FROM pago_cuentas WHERE pedido_id='.(int)$rapido['id'])['n']===0,'Pedido rápido espera la respuesta sobre propina');
    $r=$a->post('pagos/preparar/'.$rapido['id'],array('propina_tipo'=>'ninguna'),'pagos/'.$rapido['id']);
    exigir($r['status']===303 && (int)fila('SELECT propina_elegida FROM pedidos WHERE id='.(int)$rapido['id'])['propina_elegida']===1,'El cliente declina propina al cobrar');
    $cuenta_rapida=(string)fila('SELECT id FROM pago_cuentas WHERE pedido_id='.(int)$rapido['id'])['id'];
    $importe_rapido=fila('SELECT total FROM pedidos WHERE id='.(int)$rapido['id'])['total'];
    $r=$a->post('pagos/registrar/'.$rapido['id'],array('cuenta_id'=>$cuenta_rapida,'efectivo'=>$importe_rapido,'credito'=>'0','debito'=>'0','recibido'=>$importe_rapido,'clave'=>bin2hex(random_bytes(32))));
    exigir($r['status']===303 && fila('SELECT estado_pago FROM pedidos WHERE id='.(int)$rapido['id'])['estado_pago']==='pagado','Pedido rápido se cobra sin propina');
    exigir(fila('SELECT propina,total FROM ventas WHERE pedido_id='.(int)$rapido['id'])['propina']==='0.00','Venta rápida conserva propina cero');
    $antes=huella();$caja->post('pagos/registrar/'.$pid,$pago);exigir(huella()===$antes,'Reintento de pago no duplica registros');
    exigir($a->pedir('ventas?desde=2026-02-30&hasta=2026-03-01')['status']===422,'Ventas rechaza fecha imposible');
    exigir($a->pedir('ventas?desde=2026-12-01&hasta=2026-01-01')['status']===422,'Ventas rechaza rango invertido');

    $sol=array('proveedor_id'=>'1','fecha'=>'2026-11-02','observaciones'=>'','insumos'=>array('1'),'cantidades'=>array('2.50'),'clave'=>bin2hex(random_bytes(32)));
    foreach (array('proveedor_id','fecha','insumos','cantidades','clave') as $campo) {
        $d=$sol;unset($d[$campo]);$a->invalido('compras/crear',$d,'Solicitud exige '.$campo);
    }
    foreach (array('-1','0','1.001',array('1')) as $q) { $d=$sol;$d['cantidades']=array($q);$a->invalido('compras/crear',$d,'Solicitud rechaza cantidad inválida'); }
    $d=$sol;$d['proveedor_id']='2';$a->invalido('compras/crear',$d,'Solicitud rechaza proveedor ajeno');
    $d=$sol;$d['insumos']=array('3');$a->invalido('compras/crear',$d,'Solicitud rechaza insumo ajeno');
    $d=$sol;$d['insumos']=array('1','1');$d['cantidades']=array('1','1');$a->invalido('compras/crear',$d,'Solicitud rechaza insumos duplicados');
    $r=$a->post('compras/crear',$sol);exigir($r['status']===303,'Solicitud válida con observación opcional');
    $sid=(int)fila('SELECT MAX(id) id FROM solicitudes_compra')['id'];
    $d=$sol;unset($d['clave']);$d['version']='2';$a->invalido('compras/'.$sid.'/editar',$d,'Edición rechaza versión ajena a la vigente');
    $a->invalido('compras/'.$sid.'/confirmar',array('version'=>'1'),'Confirmación exige revisión');
    $r=$a->post('compras/'.$sid.'/confirmar',array('version'=>'1','revisada'=>'1'));exigir($r['status']===303 && fila('SELECT estado FROM solicitudes_compra WHERE id='.$sid)['estado']==='confirmada','Confirmación válida');
    $a->invalido('compras/'.$sid.'/estado',array('version'=>'2','estado'=>'cancelada','motivo'=>' '),'Cancelar compra exige motivo');
    $r=$a->post('compras/'.$sid.'/estado',array('version'=>'2','estado'=>'enviada'));exigir($r['status']===303 && fila('SELECT estado FROM solicitudes_compra WHERE id='.$sid)['estado']==='enviada','Enviar compra válida');
    $detalle=(int)fila('SELECT id FROM solicitud_compra_detalles WHERE solicitud_id='.$sid)['id'];
    $recepcion=array('version'=>'3','clave'=>bin2hex(random_bytes(32)),'cantidades'=>array($detalle=>'1'));
    foreach (array('-1','0','3','1.001',array('1')) as $q) { $d=$recepcion;$d['cantidades']=array($detalle=>$q);$a->invalido('compras/'.$sid.'/recepcion',$d,'Recepción rechaza cantidad inválida o excedente'); }
    $d=$recepcion;$d['cantidades']=array($detalle=>'1','0'.$detalle=>'1');$a->invalido('compras/'.$sid.'/recepcion',$d,'Recepción rechaza duplicados normalizados');
    $db->query("UPDATE insumos SET unidad_medida='g' WHERE id=1");
    $a->invalido('compras/'.$sid.'/recepcion',$recepcion,'Recepción rechaza cambio de unidad');
    $db->query("UPDATE insumos SET unidad_medida='kg' WHERE id=1");
    $r=$a->post('compras/'.$sid.'/recepcion',$recepcion);exigir($r['status']===303 && fila('SELECT cantidad_actual FROM insumos WHERE id=1')['cantidad_actual']==='11.00','Recepción parcial válida');
    $antes=huella();$a->post('compras/'.$sid.'/recepcion',$recepcion);exigir(huella()===$antes,'Reintento de recepción no duplica stock');
    $d=$recepcion;$d['version']='4';$d['clave']=bin2hex(random_bytes(32));$d['cantidades']=array($detalle=>'1.50');
    $r=$a->post('compras/'.$sid.'/recepcion',$d);exigir($r['status']===303 && fila('SELECT estado FROM solicitudes_compra WHERE id='.$sid)['estado']==='recibida','Recepción final válida');
    exigir($b->pedir('compras/'.$sid.'/exportar')['status']===404,'Exportación aislada por empresa');
    foreach (array(array('cantidad'=>array(1=>'-1')),array('cantidad'=>array(1=>array('1'))),array('cantidad'=>array(3=>'1')),array('cantidad'=>array(1=>'1','01'=>'2'))) as $d) {
        $a->invalido('compras/seleccionar',$d,'Selección agrupada rechaza negativos, arreglos o empresa ajena');
    }
    $r=$a->post('compras/seleccionar',array('cantidad'=>array(1=>'1')));exigir(in_array($r['status'],array(302,303),TRUE),'Selección agrupada válida');
    $antes=huella();$r=$a->post('compras/generar',array('fecha'=>'2026-02-30','observaciones'=>''));
    $pantalla=$a->seguir($r);exigir(huella()===$antes && strpos($pantalla['body'],'no es válida')!==FALSE,'Generación agrupada rechaza fecha imposible');
    $r=$a->post('compras/generar',array('fecha'=>'2026-11-02','observaciones'=>''));exigir($r['status']===303 && (int)fila('SELECT COUNT(*) n FROM solicitudes_compra')['n']===2,'Generación agrupada válida');
    $antes=huella();$a->post('compras/generar',array('fecha'=>'2026-11-02','observaciones'=>''));exigir(huella()===$antes,'Reintento de generación no duplica documentos');
    // Inicio, catálogo y cocina: lecturas reales con paginación, permisos y dos empresas.
    for ($i=1;$i<=26;$i++) {
        $q=$db->prepare('INSERT INTO productos (empresa_id,sku,nombre,precio) VALUES (1,?,?,10)');
        $sku='busqueda-'.str_pad((string)$i,2,'0',STR_PAD_LEFT);$nombre='Producto paginado '.$sku;
        $q->bind_param('ss',$sku,$nombre);$q->execute();
    }
    $db->query("INSERT INTO productos (empresa_id,sku,nombre,precio) VALUES (2,'busqueda-ajena','Producto ajeno exclusivo',999)");
    $q=$db->prepare('INSERT INTO productos (empresa_id,sku,nombre,precio) VALUES (1,?,?,10)');
    $sku='literal';$nombre='Especial %_ <script>alert(1)</script>';$q->bind_param('ss',$sku,$nombre);$q->execute();
    $antes=huella();
    $r=$a->pedir('productos?busqueda=busqueda-');
    exigir($r['status']===200 && substr_count($r['body'],'Ver detalle')===25 && strpos($r['body'],'Página 1 de 2')!==FALSE,'Catálogo limita la primera página a 25 productos');
    exigir(strpos($r['body'],'Producto ajeno exclusivo')===FALSE,'Búsqueda por código mantiene el aislamiento por empresa');
    exigir(strpos($r['body'],'busqueda=busqueda-')!==FALSE,'Paginación conserva la búsqueda');
    $r=$a->pedir('productos?busqueda=busqueda-&pagina=9999999999');
    exigir($r['status']===200 && substr_count($r['body'],'Ver detalle')===1 && strpos($r['body'],'Página 2 de 2')!==FALSE,'Página fuera de rango muestra la última página válida');
    $r=$a->pedir('productos?busqueda=busqueda-&pagina=-1');
    exigir($r['status']===200 && strpos($r['body'],'Página 1 de 2')!==FALSE,'Página negativa vuelve a la primera página');
    $r=$a->pedir('productos?busqueda='.rawurlencode('%_'));
    exigir($r['status']===200 && substr_count($r['body'],'Ver detalle')===1 && strpos($r['body'],'&lt;script&gt;')!==FALSE && strpos($r['body'],'<script>')===FALSE,'Búsqueda literal de comodines y nombres escapados contra XSS');
    $r=$a->pedir('productos?busqueda='.rawurlencode("' OR 1=1 --"));
    exigir($r['status']===200 && substr_count($r['body'],'Ver detalle')===0,'La búsqueda no interpreta SQL');
    exigir($a->pedir('productos?busqueda[]=x')['status']===422,'Búsqueda rechaza arreglos');
    exigir($a->pedir('productos?busqueda='.str_repeat('a',151))['status']===422,'Búsqueda limita la longitud');
    $r=$b->pedir('productos?busqueda=busqueda-');
    exigir($r['status']===200 && substr_count($r['body'],'Ver detalle')===1 && strpos($r['body'],'Producto paginado')===FALSE,'Empresa B solo encuentra su catálogo');
    exigir(huella()===$antes,'Consultar el catálogo no modifica datos');

    $db->query("INSERT INTO pedidos (empresa_id,numero,usuario_id,referencia,estado,estado_pago,total,confirmado_en)
        VALUES (1,1001,1,'Cocina de prueba','pendiente','pendiente',100,DATE_SUB(NOW(),INTERVAL 12 MINUTE)),
               (1,1002,1,'Venta de hoy','entregado','pagado',100,NOW()),
               (2,1001,2,'Venta ajena','entregado','pagado',999,NOW())");
    $ayer=(new DateTimeImmutable('yesterday'))->format('Y-m-d');
    $q=$db->prepare('UPDATE ventas SET fecha=?');$q->bind_param('s',$ayer);$q->execute();
    $hoy=date('Y-m-d H:i:s');
    foreach (array(array(1,1002,1,'100.00','10.00','110.00'),array(2,1001,2,'999.00','99.90','1098.90')) as $v) {
        $q=$db->prepare('INSERT INTO ventas (empresa_id,pedido_id,pedido_numero,referencia,usuario_id,subtotal,propina,total,efectivo,credito,debito,fecha)
            SELECT empresa_id,id,numero,referencia,?,?,?,?,?,0,0,? FROM pedidos WHERE empresa_id=? AND numero=?');
        $q->bind_param('isssssii',$v[2],$v[3],$v[4],$v[5],$v[5],$hoy,$v[0],$v[1]);$q->execute();
    }
    $r=$a->pedir('inicio');
    exigir($r['status']===200 && strpos($r['body'],'Resumen de tu empresa')!==FALSE && strpos($r['body'],'Q 100.00')!==FALSE && strpos($r['body'],'Q 999.00')===FALSE,'Inicio muestra ventas propias sin mezclar empresas ni propina');
    exigir(strpos($r['body'],'El resumen no está disponible')===FALSE,'Las consultas de inicio se completan correctamente');
    exigir(substr_count($r['body'],'<circle cx=')===7 && strpos($r['body'],'Ver importes por día')!==FALSE,'La gráfica incluye siete días y una tabla con los importes exactos');
    $r=$b->pedir('inicio');
    exigir($r['status']===200 && strpos($r['body'],'Q 999.00')!==FALSE && strpos($r['body'],'Q 100.00')===FALSE,'Resumen de empresa B aislado');
    $r=$caja->pedir('inicio');
    exigir($r['status']===200 && strpos($r['body'],'Pedidos por cobrar')!==FALSE && strpos($r['body'],'Ventas de hoy sin propina')===FALSE,'Cajero consulta pendientes sin acceder al historial de ventas');
    $r=$cocina->pedir('inicio');
    exigir($r['status']===200 && strpos($r['body'],'Pedidos en cocina')!==FALSE && strpos($r['body'],'Pedidos por cobrar')===FALSE && strpos($r['body'],'Insumos por reponer')===FALSE,'Cocinero solo recibe indicadores autorizados');
    exigir(strpos($r['body'],'grafico-ventas')===FALSE,'La gráfica financiera no se envía a un rol sin acceso a ventas');
    $r=$cocina->pedir('cocina');
    exigir($r['status']===200 && strpos($r['body'],'Actualizar pedidos')!==FALSE && preg_match('/Tiempo desde la confirmación:<\/strong>\s+12 min/u',$r['body']),'Cocina muestra actualización manual y minutos desde confirmación');
    $r=$invitado->pedir('productos');
    exigir(in_array($r['status'],array(302,303,307),TRUE),'Catálogo exige autenticación');
    // Filtros compartidos: usuarios y compras, con datos de dos empresas.
    $db->query("INSERT INTO usuarios (empresa_id,rol_id,nombre,nombre_usuario,correo,password_hash,activo)
        SELECT 1,id,'Cuenta de soporte','soporte_inactivo','soporte@prueba.test','sin_login',0 FROM roles WHERE codigo='cajero'");
    $db->query("INSERT INTO usuarios (empresa_id,rol_id,nombre,nombre_usuario,correo,password_hash,activo)
        SELECT 2,id,'Cuenta de soporte ajena','soporte_ajeno','soporte-b@prueba.test','sin_login',0 FROM roles WHERE codigo='cajero'");
    $antes=huella();$r=$a->pedir('usuarios?busqueda=soporte');
    exigir($r['status']===200 && strpos($r['body'],'<td>soporte_inactivo</td>')!==FALSE && strpos($r['body'],'soporte_ajeno')===FALSE,'Búsqueda de usuarios aislada por empresa');
    $r=$a->pedir('usuarios?estado=inactivos&rol=cajero');
    exigir($r['status']===200 && strpos($r['body'],'<td>soporte_inactivo</td>')!==FALSE && strpos($r['body'],'<td>admin_a</td>')===FALSE,'Filtros de estado y rol se combinan');
    exigir(strpos($r['body'],'Quitar filtro')!==FALSE && strpos($r['body'],'rol=cajero')!==FALSE,'Los filtros activos permiten quitar un criterio conservando los demás');
    exigir($a->pedir('usuarios?busqueda[]=x')['status']===422,'Búsqueda de usuarios rechaza arreglos');
    exigir($a->pedir('usuarios?rol=inexistente')['status']===422,'Filtro de rol valida sus opciones');
    exigir($caja->pedir('usuarios?busqueda=soporte')['status']===403,'Los filtros no omiten los permisos de usuarios');
    exigir(huella()===$antes,'Filtrar usuarios no cambia los datos');
    for($i=1;$i<=26;$i++) {
        $q=$db->prepare("INSERT INTO solicitudes_compra (empresa_id,numero,proveedor_id,proveedor_nombre,fecha,clave,creado_por,actualizado_por)
            VALUES (1,?,1,'Verificación proveedor',?,?,1,1)");
        $numero=20000+$i;$fecha=date('Y-m-d');$clave=bin2hex(random_bytes(32));$q->bind_param('iss',$numero,$fecha,$clave);$q->execute();
    }
    $q=$db->prepare("INSERT INTO solicitudes_compra (empresa_id,numero,proveedor_id,proveedor_nombre,fecha,clave,creado_por,actualizado_por)
        VALUES (2,20001,2,'Verificación proveedor',?,?,2,2)");
    $clave=bin2hex(random_bytes(32));$q->bind_param('ss',$fecha,$clave);$q->execute();$solicitud_b=$db->insert_id;
    $antes=huella();$ruta='compras?busqueda='.rawurlencode('Verificación proveedor').'&estado=borrador';
    $r=$a->pedir($ruta);
    exigir($r['status']===200 && substr_count($r['body'],'>Revisar</a>')===25 && strpos($r['body'],'Página 1 de 2')!==FALSE,'Compras filtra y pagina 25 resultados de la empresa');
    exigir(strpos($r['body'],'estado=borrador&amp;pagina=2')!==FALSE,'La paginación de compras conserva los filtros');
    $r=$a->pedir($ruta.'&pagina=99999');
    exigir($r['status']===200 && substr_count($r['body'],'>Revisar</a>')===1 && strpos($r['body'],'Página 2 de 2')!==FALSE,'Compras ajusta una página fuera de rango al resultado filtrado');
    $r=$b->pedir($ruta);
    exigir($r['status']===200 && substr_count($r['body'],'>Revisar</a>')===1 && strpos($r['body'],'compras/'.$solicitud_b)!==FALSE,'Empresa B solo obtiene sus solicitudes filtradas');
    $r=$a->pedir('compras?busqueda='.rawurlencode("%_' OR 1=1 --"));
    exigir($r['status']===200 && substr_count($r['body'],'>Revisar</a>')===0,'Compras trata comodines y SQL como texto de búsqueda');
    exigir($a->pedir('compras?estado[]=borrador')['status']===422,'Estado de compra rechaza arreglos');
    exigir(huella()===$antes,'Filtrar compras no modifica datos');
    $antes=huella();$r=$a->post('inventario/1/movimiento',array('tipo'=>'salida','cantidad'=>'99999999.99','motivo'=>'Revisión de existencias'),'inventario/1/movimiento');
    exigir($r['status']===422 && ClienteValidacion::campo($r['body'],'cantidad')==='99999999.99'
        && ClienteValidacion::campo($r['body'],'motivo')==='Revisión de existencias' && huella()===$antes,'Movimiento rechazado conserva lo escrito y no cambia el inventario');
    $r=$a->pedir('inventario/1/historial');
    exigir($r['status']===200 && strpos($r['body'],'id="menu-principal"')!==FALSE && strpos($r['body'],'formularios.js')!==FALSE,'El historial de inventario usa la plantilla uniforme');
    // Regresión del merge: integrar catálogo, filtros, auditoría y pedidos.
    $q=$db->prepare('INSERT INTO productos (empresa_id,sku,nombre,descripcion,precio,disponible) VALUES (?, ?, ?, ?, ?, 0)');
    foreach(array(array(1,'Catálogo privado A','20.00'),array(2,'Catálogo privado B','999.00')) as $v) {
        $sku='CAT-MERGE';$descripcion='texto común prueba merge <script>alert(1)</script>';
        $q->bind_param('issss',$v[0],$sku,$v[1],$descripcion,$v[2]);$q->execute();
        if($v[0]===1){$catalogo_a=$db->insert_id;}
    }
    $db->query("UPDATE productos SET disponible=0 WHERE empresa_id=1 AND sku LIKE 'busqueda-%'");
    $r=$a->pedir('productos?busqueda=busqueda-&disponibilidad=0');
    exigir($r['status']===200 && substr_count($r['body'],'Ver detalle')===25 && strpos($r['body'],'No disponible')!==FALSE,'Catálogo paginado devuelve descripción y disponibilidad sin errores PHP');
    exigir(strpos($r['body'],'disponibilidad=0&amp;pagina=2')!==FALSE,'La paginación conserva el filtro de no disponibles');
    $r=$a->pedir('productos?busqueda=busqueda-&disponibilidad=0&pagina=2');
    exigir($r['status']===200 && substr_count($r['body'],'Ver detalle')===1,'Segunda página del catálogo filtrado');
    $r=$a->pedir('productos?busqueda='.rawurlencode('texto común prueba merge'));
    exigir($r['status']===200 && strpos($r['body'],'Catálogo privado A')!==FALSE && strpos($r['body'],'Catálogo privado B')===FALSE && strpos($r['body'],'&lt;script&gt;')!==FALSE,'Búsqueda por descripción aislada por empresa y escapada contra XSS');
    $r=$a->pedir('productos?busqueda=busqueda-&disponibilidad=1');
    exigir($r['status']===200 && substr_count($r['body'],'Ver detalle')===0 && strpos($r['body'],'colspan="6"')!==FALSE,'Filtro de disponibles y estado vacío cubren las seis columnas');
    exigir($a->pedir('productos?disponibilidad[]=0')['status']===422,'Disponibilidad rechaza arreglos');
    exigir($a->pedir('productos?disponibilidad=2')['status']===422,'Disponibilidad rechaza opciones desconocidas');
    $form=$a->pedir('productos/crear');
    exigir($form['status']===200 && strpos($form['body'],'formulario-ui')!==FALSE && strpos($form['body'],'Cancelar')!==FALSE,'El formulario integrado mantiene el diseño y las acciones comunes');
    $producto=array('nombre'=>'Producto del merge','sku'=>'NUEVO-MERGE','descripcion'=>'Descripción conservada','precio'=>'12.50','disponible'=>'1');
    $r=$a->post('productos/crear',$producto,'productos/crear');
    exigir($r['status']===303,'Crear producto desde el catálogo');
    $nuevo_producto=(int)fila("SELECT id FROM productos WHERE empresa_id=1 AND sku='NUEVO-MERGE'")['id'];
    $pantalla=$a->seguir($r);
    exigir(strpos($pantalla['body'],'Producto creado correctamente.')!==FALSE,'La lista muestra la confirmación de producto creado');
    exigir(strpos($a->pedir('productos/'.$nuevo_producto)['body'],'Sin imagen')!==FALSE,'Producto sin foto muestra marcador');
    $r=$a->post_archivo('productos/'.$nuevo_producto.'/imagen',$foto,'productos/editar/'.$nuevo_producto);
    $imagen_producto=fila('SELECT imagen FROM productos WHERE id='.$nuevo_producto)['imagen'];
    exigir($r['status']===303 && (bool)preg_match('/^[a-f0-9]{32}\.png$/',$imagen_producto),'Subir foto opcional de producto');
    $r=$a->pedir('productos/'.$nuevo_producto.'/imagen');
    exigir($r['status']===200 && substr($r['body'],0,8)==="\x89PNG\r\n\x1a\n",'Foto del producto se entrega por ruta autenticada');
    exigir($b->pedir('productos/'.$nuevo_producto.'/imagen')['status']===404,'Otra empresa no ve la foto del producto');
    $antes=huella();$r=$caja->post('productos/'.$nuevo_producto.'/imagen',array('quitar'=>'1'),'productos/'.$nuevo_producto);
    exigir($r['status']===403 && huella()===$antes,'Cajero no puede retirar foto del producto');
    $r=$a->post('productos/'.$nuevo_producto.'/imagen',array('quitar'=>'1'),'productos/editar/'.$nuevo_producto);
    exigir($r['status']===303 && fila('SELECT imagen FROM productos WHERE id='.$nuevo_producto)['imagen']===NULL,'Quitar foto del producto restaura marcador');
    $r=$a->post('pedidos/crear',array('referencia'=>'Pedido de precio histórico'));
    exigir(in_array($r['status'],array(302,303),TRUE),'Crear borrador para comprobar el catálogo integrado');
    $pedido_catalogo=(int)fila("SELECT id FROM pedidos WHERE referencia='Pedido de precio histórico'")['id'];
    $r=$a->post('pedidos/agregar/'.$pedido_catalogo,array('producto_id'=>(string)$nuevo_producto,'cantidad'=>'1'));
    exigir(in_array($r['status'],array(302,303),TRUE),'El producto nuevo puede agregarse a pedidos');
    $producto['precio']='14.75';$r=$a->post('productos/editar/'.$nuevo_producto,$producto,'productos/editar/'.$nuevo_producto);
    exigir($r['status']===303 && fila('SELECT precio FROM productos WHERE id='.$nuevo_producto)['precio']==='14.75','Editar producto y actualizar el precio');
    $cambio=fila('SELECT * FROM producto_precio_historial WHERE producto_id='.$nuevo_producto);
    exigir($cambio['precio_anterior']==='12.50' && $cambio['precio_nuevo']==='14.75' && (int)$cambio['usuario_id']===1,'El cambio de precio registra importes y responsable reales');
    exigir(fila('SELECT precio_unitario FROM pedido_detalles WHERE pedido_id='.$pedido_catalogo)['precio_unitario']==='12.50','Cambiar el catálogo conserva el precio de pedidos anteriores');
    $a->post('productos/editar/'.$nuevo_producto,$producto,'productos/editar/'.$nuevo_producto);
    exigir((int)fila('SELECT COUNT(*) n FROM producto_precio_historial WHERE producto_id='.$nuevo_producto)['n']===1,'Repetir el mismo precio no duplica la auditoría');
    $mal=$producto;$mal['precio']='-1';$antes=huella();$r=$a->post('productos/editar/'.$nuevo_producto,$mal,'productos/editar/'.$nuevo_producto);
    exigir($r['status']===422 && ClienteValidacion::campo($r['body'],'nombre')===$producto['nombre'] && huella()===$antes,'Precio inválido devuelve 422, conserva el formulario y no altera datos');
    $mal=$producto;$mal['sku']='CAT-MERGE';$a->invalido('productos/editar/'.$nuevo_producto,$mal,'El catálogo rechaza códigos duplicados','productos/editar/'.$nuevo_producto);
    $antes=huella();$r=$caja->post('productos/crear',$producto);
    exigir($r['status']===403 && huella()===$antes,'El cajero no puede crear productos');
    exigir($b->pedir('productos/editar/'.$nuevo_producto)['status']===404,'Otra empresa no puede abrir la edición del producto');
    $r=$b->post('productos/editar/'.$nuevo_producto,$producto);
    exigir($r['status']===404 && huella()===$antes,'Otra empresa no puede modificar el producto');
    $r=$a->pedir('productos/'.$nuevo_producto);
    exigir($r['status']===200 && strpos($r['body'],'14.75')!==FALSE && strpos($r['body'],'12.50')!==FALSE,'El detalle y el historial de precios se renderizan correctamente');
    $r=$caja->pedir('productos/'.$nuevo_producto);
    exigir($r['status']===200 && strpos($r['body'],'Historial de precios')===FALSE,'Un rol sin gestión no recibe el historial de precios');
    $a->invalido('pedidos/agregar/'.$pedido_catalogo,array('producto_id'=>(string)$catalogo_a,'cantidad'=>'1'),'Pedidos rechaza un producto no disponible');
    $producto['disponible']='0';$a->post('productos/editar/'.$nuevo_producto,$producto,'productos/editar/'.$nuevo_producto);
    $a->invalido('pedidos/'.$pedido_catalogo.'/confirmar',array(),'Confirmar revalida la disponibilidad del catálogo');
    $producto['disponible']='1';$a->post('productos/editar/'.$nuevo_producto,$producto,'productos/editar/'.$nuevo_producto);
    $r=$a->post('pedidos/'.$pedido_catalogo.'/confirmar',array());
    exigir(in_array($r['status'],array(302,303),TRUE) && fila('SELECT estado FROM pedidos WHERE id='.$pedido_catalogo)['estado']==='pendiente','Reactivar el producto permite confirmar el pedido conservando su precio');
    mkdir($temp.'/tools',0700);
    copy($root.'/tools/cargar_demo_catalogo.php',$temp.'/tools/cargar_demo_catalogo.php');
    $comando=array(PHP_BINARY,$temp.'/tools/cargar_demo_catalogo.php','1','--aplicar');
    $proceso_demo=proc_open($comando,array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$canales,$temp);
    fclose($canales[0]);$salida_demo=stream_get_contents($canales[1]);$error_demo=stream_get_contents($canales[2]);
    fclose($canales[1]);fclose($canales[2]);$codigo_demo=proc_close($proceso_demo);
    exigir($codigo_demo===0 && (int)fila("SELECT COUNT(*) n FROM productos WHERE empresa_id=1 AND sku LIKE 'DEMO-SHUCO-%'")['n']===2 &&
        (int)fila("SELECT COUNT(*) n FROM insumos WHERE empresa_id=1 AND nombre LIKE '%(demo)'")['n']===2,
        'El cargador de muestra añade dos productos y dos insumos: '.$error_demo);
    $demo_id=(int)fila("SELECT id FROM productos WHERE empresa_id=1 AND sku='DEMO-SHUCO-01'")['id'];
    exigir($a->pedir('productos/'.$demo_id.'/imagen')['status']===200,'La foto del shuco de muestra se puede ver');
    $proceso_demo=proc_open($comando,array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$canales,$temp);
    fclose($canales[0]);stream_get_contents($canales[1]);stream_get_contents($canales[2]);
    fclose($canales[1]);fclose($canales[2]);$codigo_demo=proc_close($proceso_demo);
    exigir($codigo_demo===0 && (int)fila("SELECT COUNT(*) n FROM productos WHERE empresa_id=1 AND sku LIKE 'DEMO-SHUCO-%'")['n']===2,'Repetir la carga no duplica los artículos');
    // Experiencia compartida: prioridades por rol, filtros, estados y avisos únicos.
    foreach (array(array($a,array('inventario','compras','ventas')),array($caja,array('pedidos','pagos')),array($cocina,array('cocina'))) as $caso) {
        $r=$caso[0]->pedir('inicio');
        preg_match('/<section aria-label="Accesos principales".*?<\/section>/s',$r['body'],$bloque);
        preg_match_all('/<a class="acceso" href="[^"]+index.php\/([^"]+)"/',$bloque[0]??'', $enlaces);
        exigir($r['status']===200 && $enlaces[1]===$caso[1],'El inicio prioriza únicamente los accesos permitidos del rol: '.implode(', ',$caso[1]));
    }
    $r=$a->pedir('usuarios?busqueda=soporte&estado=inactivos&rol=cajero');
    exigir($r['status']===200 && strpos($r['body'],'data-filtros-activos="2"')!==FALSE && strpos($r['body'],'Más filtros')!==FALSE,'Los filtros adicionales conservan los valores y cuentan los criterios activos');
    $r=$a->pedir('usuarios');
    exigir(strpos($r['body'],'data-filtros-activos="0"')!==FALSE,'Sin filtros secundarios activos el componente permite plegarlos en móvil');
    $r=$a->pedir('ventas');
    exigir($r['status']===200 && strpos($r['body'],'class="filtros-adicionales"')===FALSE,'Las dos fechas obligatorias de ventas permanecen visibles');
    $r=$a->pedir('compras/'.$sid);
    exigir($r['status']===200 && strpos($r['body'],'etiqueta-recibido')!==FALSE && strpos($r['body'],'Recibida')!==FALSE,'Compras presenta el estado recibido con texto y etiqueta consistente');
    $r=$a->pedir('pedidos/'.$pedido_catalogo);
    exigir($r['status']===200 && strpos($r['body'],'etiqueta-confirmado')!==FALSE && strpos($r['body'],'Confirmado')!==FALSE,'Pedidos presenta la confirmación con texto y etiqueta consistente');
    $cuenta_ux=array('nombre'=>'Cuenta "UX" <script>prueba</script>','nombre_usuario'=>'nuevo_usuario','rol_id'=>$rol);
    $r=$a->post('usuarios/'.$uid.'/editar',$cuenta_ux,'usuarios/'.$uid.'/editar');
    exigir(in_array($r['status'],array(302,303),TRUE),'Actualizar una cuenta para verificar la experiencia de confirmación');
    $r=$a->seguir($r);
    exigir(substr_count($r['body'],'data-notificacion')===1 && substr_count($r['body'],'Usuario actualizado correctamente.')===1 && strpos($r['body'],'data-notificacion')<strpos($r['body'],'<h1>'),'El éxito aparece una sola vez en la ubicación común antes del título');
    exigir(strpos($r['body'],'data-confirmar=')!==FALSE && strpos($r['body'],'&quot;UX&quot; &lt;script&gt;prueba&lt;/script&gt;')!==FALSE && strpos($r['body'],'<script>prueba</script>')===FALSE,'La confirmación identifica al usuario y escapa nombres con comillas o HTML');
    $r=$a->pedir('usuarios');exigir(strpos($r['body'],'Usuario actualizado correctamente.')===FALSE,'El aviso de éxito desaparece en la siguiente petición');
    $r=$a->post('usuarios/'.$uid.'/estado',array());$r=$a->seguir($r);
    exigir((int)fila('SELECT activo FROM usuarios WHERE id='.$uid)['activo']===0 && strpos($r['body'],'Usuario desactivado:')!==FALSE,'Desactivar conserva la validación del servidor y muestra un aviso claro');
    $a->post('usuarios/'.$uid.'/estado',array());
    $r=$a->pedir('inventario');
    exigir(strpos($r['body'],'data-confirmar=')!==FALSE && strpos($r['body'],'¿Desactivar el insumo «Harina»?')!==FALSE,'La confirmación de inventario identifica el insumo concreto');
    $r=$a->post('inventario/1/movimiento',array('tipo'=>'entrada','cantidad'=>'1','motivo'=>'Revisión de experiencia'),'inventario/1/movimiento');$r=$a->seguir($r);
    exigir($r['status']===200 && substr_count($r['body'],'data-notificacion')===1 && strpos($r['body'],'Movimiento registrado correctamente.')!==FALSE,'Movimiento registrado usa el mismo aviso único en el historial');
    // La venta es reversible sin duplicar el producto ni cambiar precios o existencias.
    $r=$a->post('inventario/crear',array('nombre'=>'Insumo de venta reversible','cantidad_actual'=>'7','stock_minimo'=>'1','unidad_medida'=>'unidad','fecha_vencimiento'=>''),'inventario/crear');
    exigir(in_array($r['status'],array(302,303),TRUE),'Crear insumo de aceptación para la venta reversible');
    $iid=(int)fila("SELECT id FROM insumos WHERE nombre='Insumo de venta reversible' AND empresa_id=1")['id'];
    $r=$a->post('inventario/vender/'.$iid,array('sku'=>'DISP-VENTA','precio'=>'15.25'),'inventario/vender/'.$iid);
    exigir(in_array($r['status'],array(302,303),TRUE),'Habilitar por primera vez un insumo para venta');
    $vp=fila('SELECT * FROM productos WHERE empresa_id=1 AND insumo_id='.$iid);$vid=(int)$vp['id'];
    $r=$a->pedir('inventario?busqueda=Insumo%20de%20venta%20reversible');
    exigir($r['status']===200 && strpos($r['body'],'Desactivar venta')!==FALSE && strpos($r['body'],'Habilitar para venta')===FALSE,'Inventario ofrece desactivar cuando el producto ya existe');
    $r=$a->post('pedidos/crear',array('referencia'=>'Pedido histórico de venta reversible'));
    $historico=(int)fila("SELECT id FROM pedidos WHERE referencia='Pedido histórico de venta reversible'")['id'];
    $a->post('pedidos/agregar/'.$historico,array('producto_id'=>(string)$vid,'cantidad'=>'2'));
    $r=$a->post('pedidos/'.$historico.'/confirmar',array());
    exigir(in_array($r['status'],array(302,303),TRUE),'Confirmar pedido previo a desactivar la venta');
    $pedido_previo=fila('SELECT * FROM pedidos WHERE id='.$historico);
    $detalle_previo=fila('SELECT * FROM pedido_detalles WHERE pedido_id='.$historico);
    $stock_previo=fila('SELECT * FROM insumos WHERE id='.$iid);
    $auditoria_previa=(int)fila('SELECT COUNT(*) n FROM producto_precio_historial WHERE producto_id='.$vid)['n'];
    $r=$a->post('pedidos/crear',array('referencia'=>'Borrador de venta reversible'));
    $borrador=(int)fila("SELECT id FROM pedidos WHERE referencia='Borrador de venta reversible'")['id'];
    $a->post('pedidos/agregar/'.$borrador,array('producto_id'=>(string)$vid,'cantidad'=>'1'));
    $ruta_venta='productos/'.$vid.'/disponibilidad';$desactivar=array('disponible'=>'0','origen'=>'inventario');
    $r=$a->post($ruta_venta,$desactivar);
    exigir($r['status']===303 && strpos($r['destino'],'/inventario')!==FALSE && fila('SELECT disponible FROM productos WHERE id='.$vid)['disponible']==='0','Desactivar venta regresa a inventario y cambia solo la disponibilidad');
    $r=$a->seguir($r);
    exigir(strpos($r['body'],'Venta desactivada: Insumo de venta reversible.')!==FALSE,'La desactivación muestra una confirmación uniforme');
    $actual=fila('SELECT * FROM productos WHERE id='.$vid);unset($actual['disponible'],$vp['disponible']);
    exigir($actual===$vp && fila('SELECT * FROM insumos WHERE id='.$iid)===$stock_previo && (int)fila('SELECT COUNT(*) n FROM producto_precio_historial WHERE producto_id='.$vid)['n']===$auditoria_previa,'Desactivar conserva el mismo producto, código, precio, historial y existencias');
    exigir(fila('SELECT * FROM pedidos WHERE id='.$historico)===$pedido_previo && fila('SELECT * FROM pedido_detalles WHERE pedido_id='.$historico)===$detalle_previo,'Los pedidos confirmados y sus importes permanecen intactos');
    $antes=huella();$r=$a->post($ruta_venta,$desactivar);
    exigir($r['status']===303 && huella()===$antes,'Repetir desactivar es idempotente y no reactiva el producto');
    $r=$a->pedir('inventario?busqueda=Insumo%20de%20venta%20reversible');
    exigir(strpos($r['body'],'Reactivar venta')!==FALSE && strpos($r['body'],'Habilitar para venta')===FALSE,'Inventario ofrece reactivar el producto existente');
    $r=$a->pedir('pedidos/'.$borrador);
    exigir(!preg_match('/<option\b[^>]*value="'.$vid.'"/',$r['body']),'Un producto desactivado ya no se ofrece al agregar al pedido');
    $a->invalido('pedidos/agregar/'.$borrador,array('producto_id'=>(string)$vid,'cantidad'=>'1'),'No se pueden agregar productos fuera de venta');
    $a->invalido('pedidos/'.$borrador.'/confirmar',array(),'Se revalida la disponibilidad al confirmar un borrador');
    exigir($a->pedir($ruta_venta)['status']===405,'Cambiar disponibilidad solo admite POST');
    $antes=huella();$r=$a->pedir($ruta_venta,array('disponible'=>'1'));
    exigir($r['status']===403 && huella()===$antes,'CSRF protege el cambio de disponibilidad');
    foreach(array(array(),array('disponible'=>'2'),array('disponible'=>array('1')),array('disponible'=>'1','origen'=>array('inventario')),array('disponible'=>'1','origen'=>'https://ejemplo.test')) as $mal) {
        $a->invalido($ruta_venta,$mal,'Disponibilidad rechaza valores inválidos y orígenes externos');
    }
    $antes=huella();$r=$caja->post($ruta_venta,array('disponible'=>'1'));
    exigir($r['status']===403 && huella()===$antes,'Cajero no puede cambiar la disponibilidad');
    $r=$b->post($ruta_venta,array('disponible'=>'1'));
    exigir($r['status']===404 && huella()===$antes,'Otra empresa no puede cambiar la disponibilidad del producto');
    $a->post('inventario/'.$iid.'/estado',array());
    $a->invalido($ruta_venta,array('disponible'=>'1'),'No se puede reactivar venta con el insumo inactivo');
    $r=$a->pedir('productos?busqueda=DISP-VENTA');
    exigir(strpos($r['body'],'disabled title="Reactiva primero el insumo asociado."')!==FALSE,'El catálogo indica que primero se debe reactivar el insumo');
    $a->post('inventario/'.$iid.'/estado',array());
    exigir(fila('SELECT disponible FROM productos WHERE id='.$vid)['disponible']==='0','Reactivar el insumo no reactiva automáticamente su venta');
    $r=$a->post($ruta_venta,array('disponible'=>'1','origen'=>'productos','precio'=>'999','sku'=>'NO-CAMBIAR'));
    exigir($r['status']===303 && strpos($r['destino'],'/productos')!==FALSE && fila('SELECT disponible FROM productos WHERE id='.$vid)['disponible']==='1','Se puede reactivar la venta también desde el catálogo');
    $actual=fila('SELECT * FROM productos WHERE id='.$vid);unset($actual['disponible']);
    exigir($actual===$vp && (int)fila('SELECT COUNT(*) n FROM productos WHERE empresa_id=1 AND insumo_id='.$iid)['n']===1,'Reactivar reutiliza el mismo producto e ignora cambios de precio o código no autorizados por este flujo');
    $antes=huella();$a->post($ruta_venta,array('disponible'=>'1'));
    exigir(huella()===$antes,'Repetir reactivar no duplica ni altera el producto');
    $r=$a->post('pedidos/'.$borrador.'/confirmar',array());
    exigir(in_array($r['status'],array(302,303),TRUE) && fila('SELECT estado FROM pedidos WHERE id='.$borrador)['estado']==='pendiente','El borrador vuelve a poder confirmarse al reactivar la venta');
    $r=$a->post('productos/'.$nuevo_producto.'/disponibilidad',array('disponible'=>'0'));
    exigir($r['status']===303 && fila('SELECT disponible FROM productos WHERE id='.$nuevo_producto)['disponible']==='0','También se puede desactivar un producto sin insumo asociado');
    $r=$a->post('productos/'.$nuevo_producto.'/disponibilidad',array('disponible'=>'1'));
    exigir($r['status']===303 && fila('SELECT disponible FROM productos WHERE id='.$nuevo_producto)['disponible']==='1','También se puede reactivar un producto sin insumo asociado');
    // Regresiones de filtros, concurrencia y auditoría con el mismo código HTTP de producción.
    $dfecha=fila('SELECT nombre, stock_minimo, unidad_medida, fecha_vencimiento FROM insumos WHERE id='.$fecha_id);
    $r=$a->pedir('inventario?busqueda=Insumo&estado=agotado&pagina=2');
    $r=$a->pedir($editar_fecha);
    exigir(strpos($r['body'],'inventario?busqueda=Insumo&amp;pagina=2&amp;estado=agotado')!==FALSE,'Volver a inventario conserva búsqueda, filtros y página');
    $vieja=ClienteValidacion::campo($r['body'],'version_edicion');
    $dfecha['fecha_vencimiento']='2030-05-10';
    $n=(int)fila("SELECT COUNT(*) n FROM registro_cambios WHERE entidad='insumos' AND registro_id=".$fecha_id)['n'];
    $r=$a->post($editar_fecha,$dfecha,$editar_fecha);
    exigir(in_array($r['status'],array(302,303),TRUE) && strpos($r['destino'],'busqueda=Insumo')!==FALSE && strpos($r['destino'],'pagina=2')!==FALSE,'Guardar conserva el contexto del listado');
    $h=fila("SELECT * FROM registro_cambios WHERE entidad='insumos' AND registro_id=".$fecha_id.' ORDER BY id DESC LIMIT 1');
    exigir($h['campo']==='fecha_vencimiento' && $h['valor_anterior']===NULL && $h['valor_nuevo']==='2030-05-10' && $h['usuario_id']==='1' && $h['empresa_id']==='1','Cambiar vencimiento registra responsable, empresa y ambos valores');
    $antes=huella();$obsoleta=$dfecha;$obsoleta['version_edicion']=$vieja;$obsoleta['nombre']='Texto pendiente en formulario antiguo';
    $r=$a->post($editar_fecha,$obsoleta,$editar_fecha);
    exigir($r['status']===409 && huella()===$antes && strpos($r['body'],'Texto pendiente en formulario antiguo')!==FALSE && strpos($r['body'],'Otra persona modificó')!==FALSE,'Una edición antigua de inventario se rechaza y conserva lo escrito');
    $r=$a->post($editar_fecha,$dfecha,$editar_fecha);
    exigir(in_array($r['status'],array(302,303),TRUE) && (int)fila("SELECT COUNT(*) n FROM registro_cambios WHERE entidad='insumos' AND registro_id=".$fecha_id)['n']===$n+1,'Guardar sin cambios no duplica el historial');
    $a->pedir('inventario');
    $r=$a->pedir('inventario/'.$fecha_id.'/historial');
    exigir(strpos($r['body'],'Historial de cambios')!==FALSE && strpos($r['body'],'2030-05-10')!==FALSE,'Inventario permite consultar su historial de vencimientos');
    $r=$b->pedir('inventario/'.$fecha_id.'/historial');exigir($r['status']===404,'Otra empresa no puede consultar ese historial');

    $a->pedir('productos?busqueda=DISP&disponibilidad=1&pagina=2');
    $editar_producto='productos/editar/'.$vid;$r=$a->pedir($editar_producto);
    exigir(strpos($r['body'],'productos?busqueda=DISP&amp;pagina=2&amp;disponibilidad=1')!==FALSE,'Volver a productos conserva búsqueda, página y disponibilidad');
    $vproducto=ClienteValidacion::campo($r['body'],'version_edicion');
    $dp=fila('SELECT nombre, sku, descripcion, precio, disponible FROM productos WHERE id='.$vid);$dp['precio']='19.75';
    $r=$a->post($editar_producto,$dp,$editar_producto);
    exigir($r['status']===303,'Guardar un precio con una versión vigente');
    $h=fila("SELECT * FROM registro_cambios WHERE entidad='productos' AND registro_id=".$vid." AND campo='precio' ORDER BY id DESC LIMIT 1");
    exigir($h['valor_anterior']==='15.25' && $h['valor_nuevo']==='19.75' && $h['usuario_id']==='1','Precio conserva la auditoría común y el historial de precios existente');
    $antes=huella();$dp['version_edicion']=$vproducto;$dp['precio']='25.00';$dp['nombre']='Producto pendiente de comparar';$r=$a->post($editar_producto,$dp,$editar_producto);
    exigir($r['status']===409 && huella()===$antes && strpos($r['body'],'Producto pendiente de comparar')!==FALSE,'Edición antigua de producto no sobrescribe precio ni auditoría');
    $antes=huella();$dp['version_edicion']='token-modificado';$r=$a->post($editar_producto,$dp,$editar_producto);
    exigir($r['status']===409 && huella()===$antes,'La firma de edición no puede falsificarse');
    $a->post('productos/'.$vid.'/disponibilidad',array('disponible'=>'0'));
    $h=fila("SELECT * FROM registro_cambios WHERE entidad='productos' AND registro_id=".$vid." AND campo='disponible' ORDER BY id DESC LIMIT 1");
    exigir($h['valor_anterior']==='1' && $h['valor_nuevo']==='0' && $h['usuario_id']==='1','Desactivar desde la acción rápida registra la disponibilidad');
    $r=$a->pedir('productos/'.$vid);exigir(strpos($r['body'],'Historial de cambios')!==FALSE && strpos($r['body'],'Disponibilidad de venta')!==FALSE,'El catálogo muestra el historial de disponibilidad y precio');
    $a->pedir('productos');

    $ruta_proveedor='proveedores/1/editar';$r=$a->pedir($ruta_proveedor);$vproveedor=ClienteValidacion::campo($r['body'],'version_edicion');
    $dprov=fila('SELECT nombre, contacto, telefono, correo, direccion FROM proveedores WHERE id=1');$dprov['contacto']='Contacto vigente';
    $r=$a->post($ruta_proveedor,$dprov,$ruta_proveedor);exigir($r['status']===303,'Proveedor admite una edición vigente');
    $antes=huella();$dprov['contacto']='Contacto obsoleto';$dprov['version_edicion']=$vproveedor;$r=$a->post($ruta_proveedor,$dprov,$ruta_proveedor);
    exigir($r['status']===409 && huella()===$antes && strpos($r['body'],'Contacto obsoleto')!==FALSE,'Proveedor rechaza una edición obsoleta y conserva el texto');
    $ruta_usuario='usuarios/3/editar';$r=$a->pedir($ruta_usuario);$vusuario=ClienteValidacion::campo($r['body'],'version_edicion');
    $du=fila('SELECT nombre, nombre_usuario, rol_id FROM usuarios WHERE id=3');$du['nombre']='Cuenta vigente';$r=$a->post($ruta_usuario,$du,$ruta_usuario);
    exigir(in_array($r['status'],array(302,303),TRUE),'Usuario admite una edición vigente');
    $antes=huella();$du['nombre']='Nombre pendiente de comparar';$du['version_edicion']=$vusuario;$r=$a->post($ruta_usuario,$du,$ruta_usuario);
    exigir($r['status']===409 && huella()===$antes && strpos($r['body'],'Nombre pendiente de comparar')!==FALSE,'Usuario rechaza una edición obsoleta y conserva el texto');


    $r=$invitado->pedir('sesion/formulario');
    exigir(in_array($r['status'],array(302,303,307),TRUE),'El token vigente exige una sesión autenticada');
    $antes=huella();$r=$a->pedir('sesion/formulario');$token_actual=json_decode($r['body'],TRUE);
    exigir($r['status']===200 && $token_actual['nombre']==='gestion_csrf' && preg_match('/^[a-f0-9]{32,128}$/D',$token_actual['token']) && huella()===$antes,'Preparar un formulario devuelve CSRF vigente sin cambiar datos');
    $r=$a->post('sesion/formulario',array());exigir($r['status']===405,'La preparación del formulario solo admite GET');
    $r=$a->pedir('sesion/formulario',array());exigir($r['status']===403,'La preparación mantiene la protección CSRF en solicitudes POST');
    $reporte['resultado']='APROBADO';echo "\nAPROBADO: $pasadas comprobaciones HTTP y de persistencia.\n";
} catch (Throwable $e) {
    $codigo=1;$reporte['error']=$e->getMessage();fwrite(STDERR,'FALLO: '.$e->getMessage()."\n");
} finally {
    if (is_resource($proceso)) { proc_terminate($proceso);proc_close($proceso); }
    $salida=$root.'/test-results';if (!is_dir($salida)) { mkdir($salida,0700,TRUE); }
    if ($codigo && $temp && is_file($temp.'/servidor.log')) { copy($temp.'/servidor.log',$salida.'/validaciones-servidor.log'); }
    if ($db && $creada && preg_match('/^gn_validacion_test_[a-f0-9]{16}$/D',$base)) {
        try { $db->query('DROP DATABASE `'.$base.'`'); }
        catch (Throwable $e) { $codigo=1;$reporte['limpieza_error']='No se pudo borrar la base temporal '.$base;fwrite(STDERR,$reporte['limpieza_error']."\n"); }
    }
    if ($temp && is_dir($temp) && preg_match('/^gn_validacion_test_[a-f0-9]{16}$/D',basename($temp))) { borrar_arbol($temp); }
    $reporte['fin']=date(DATE_ATOM);$reporte['total_aprobadas']=$pasadas;
    if ($codigo) { $reporte['resultado']='FALLO'; }
    file_put_contents($salida.'/validaciones-http.json',json_encode($reporte,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
    echo "Reporte: test-results/validaciones-http.json\n";
}
exit($codigo);
