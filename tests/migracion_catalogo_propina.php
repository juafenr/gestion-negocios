<?php
/** Prueba la actualización de una base anterior y de un DDL interrumpido. */
if (PHP_SAPI !== 'cli') { exit(1); }
define('BASEPATH', dirname(__DIR__).'/system/');
$root=dirname(__DIR__);
$config=require $root.'/application/config/local.php';
$temporal=sys_get_temp_dir().'/gn_migracion_'.bin2hex(random_bytes(6));
$bases=array();
$db=NULL;
$codigo=0;
function ejecutar_actualizador($root,$base,$config,$aplicar) {
    $config['db_name']=$base;
    file_put_contents($root.'/application/config/local.php',"<?php\nreturn ".var_export($config,TRUE).";\n");
    $comando=array(PHP_BINARY,$root.'/tools/actualizar.php');
    if ($aplicar) { $comando[]='--aplicar'; }
    $proceso=proc_open($comando,array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$canales,$root);
    if (!is_resource($proceso)) { throw new RuntimeException('No inició el actualizador.'); }
    fclose($canales[0]);$salida=stream_get_contents($canales[1]);$error=stream_get_contents($canales[2]);
    fclose($canales[1]);fclose($canales[2]);
    if (proc_close($proceso)!==0) { throw new RuntimeException('Actualizador: '.$error); }
    return $salida;
}
try {
    mkdir($temporal.'/application/config',0700,TRUE);
    mkdir($temporal.'/database/migrations',0700,TRUE);
    mkdir($temporal.'/tools',0700,TRUE);
    foreach (array('database/schema.sql','database/conciliar_ventas.sql',
        'database/migrations/015_registro_cambios.sql','tools/actualizar.php') as $archivo) {
        copy($root.'/'.$archivo,$temporal.'/'.$archivo);
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db=new mysqli($config['db_host'],$config['db_user'],$config['db_pass'],'',(int)$config['db_port']);
    $db->set_charset('utf8mb4');
    foreach (array(FALSE,TRUE) as $parcial) {
        $base='gn_migracion_test_'.bin2hex(random_bytes(6));$bases[]=$base;
        $db->query('CREATE DATABASE `'.$base.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $db->select_db($base);
        $db->multi_query(file_get_contents($root.'/database/schema.sql'));
        do { if ($r=$db->store_result()) { $r->free(); } } while ($db->more_results() && $db->next_result());
        $db->query('ALTER TABLE insumos DROP COLUMN imagen');
        $db->query('ALTER TABLE productos DROP COLUMN imagen');
        foreach (array('propina_monto','propina_valor','propina_tipo','propina_elegida') as $columna) {
            $db->query('ALTER TABLE pedidos DROP COLUMN `'.$columna.'`');
        }
        if ($parcial) { $db->query("ALTER TABLE pedidos ADD COLUMN propina_tipo VARCHAR(12) NOT NULL DEFAULT 'ninguna'"); }
        $db->query("INSERT INTO empresas (id,nombre) VALUES (1,'Prueba de migración')");
        $db->query("INSERT INTO usuarios (id,empresa_id,rol_id,nombre,nombre_usuario,correo,password_hash)
            SELECT 1,1,id,'Prueba','migracion','migracion@example.test','sin_login' FROM roles WHERE codigo='administrador'");
        $db->query("INSERT INTO pedidos (empresa_id,numero,usuario_id,referencia,estado,estado_pago,total,propina_porcentaje)
            VALUES (1,1,1,'Con propina','pendiente','parcial',100,10),
                   (1,2,1,'Sin propina','pendiente','parcial',20,0),
                   (1,3,1,'Sin cobrar','pendiente','pendiente',50,10)");
        $diagnostico=ejecutar_actualizador($temporal,$base,$config,FALSE);
        if (strpos($diagnostico,'agregar productos.imagen')===FALSE ||
            strpos($diagnostico,'agregar pedidos.propina_monto')===FALSE) {
            throw new RuntimeException('El diagnóstico omitió columnas antiguas.');
        }
        ejecutar_actualizador($temporal,$base,$config,TRUE);
        $datos=$db->query('SELECT numero,propina_elegida,propina_tipo,propina_valor,propina_monto FROM pedidos ORDER BY numero')->fetch_all(MYSQLI_ASSOC);
        if ($datos[0]['propina_elegida']!=='1' || $datos[0]['propina_tipo']!=='porcentaje' ||
            $datos[0]['propina_valor']!=='10.00' || $datos[0]['propina_monto']!=='10.00' ||
            $datos[1]['propina_elegida']!=='1' || $datos[1]['propina_monto']!=='0.00' ||
            $datos[2]['propina_elegida']!=='0' || $datos[2]['propina_monto']!=='0.00') {
            throw new RuntimeException('La migración cambió una propina histórica.');
        }
        foreach (array('insumos','productos') as $tabla) {
            if (!$db->query("SHOW COLUMNS FROM `$tabla` LIKE 'imagen'")->num_rows) {
                throw new RuntimeException('Falta columna de imagen en '.$tabla);
            }
        }
        if (strpos(ejecutar_actualizador($temporal,$base,$config,FALSE),'Sin cambios pendientes.')===FALSE) {
            throw new RuntimeException('La migración no quedó idempotente.');
        }
        echo 'OK: '.($parcial?'migración interrumpida':'base anterior').' conserva propinas y agrega imágenes.' . "\n";
    }
} catch (Throwable $e) { $codigo=1;fwrite(STDERR,'FALLO: '.$e->getMessage()."\n"); }
finally {
    if ($db) {
        foreach ($bases as $base) {
            if (preg_match('/^gn_migracion_test_[a-f0-9]{12}$/D',$base)) { $db->query('DROP DATABASE IF EXISTS `'.$base.'`'); }
        }
    }
    if (is_dir($temporal)) {
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporal,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $archivo) { $archivo->isDir() ? rmdir($archivo->getPathname()) : unlink($archivo->getPathname()); }
        rmdir($temporal);
    }
}
exit($codigo);
