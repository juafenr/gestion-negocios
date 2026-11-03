<?php
/** Ejecutar: php tests/ejecutar_validaciones.php */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=dirname(__DIR__);
if (!function_exists('proc_open')) { fwrite(STDERR,"PHP CLI necesita proc_open habilitado.\n");exit(1); }
$dir=$root.'/test-results';
if (!is_dir($dir) && !mkdir($dir,0700,TRUE)) { fwrite(STDERR,"No se pudo crear test-results.\n");exit(1); }
$log=$dir.'/validaciones-ejecucion.log';file_put_contents($log,'Inicio: '.date(DATE_ATOM)."\n");
function ejecutar_validacion($argumentos,$nombre,$log,$root) {
    echo "\n$nombre\n";
    $p=proc_open(array_merge(array(PHP_BINARY),$argumentos),array(0=>array('pipe','r'),1=>array('file',$log,'a'),2=>array('file',$log,'a')),$pipes,$root);
    if (!is_resource($p)) { throw new RuntimeException('No se pudo iniciar '.$nombre); }
    fclose($pipes[0]);$codigo=proc_close($p);
    if ($codigo!==0) { throw new RuntimeException($nombre.' terminó con código '.$codigo.'. Revisa '.$log); }
    echo "OK\n";
}
try {
    foreach (array('application/libraries/Validacion_negocio.php',
        'application/controllers/Usuarios.php','application/controllers/Proveedores.php','application/controllers/Inventario.php','application/controllers/Pedidos.php','application/controllers/Compras.php',
        'application/models/Usuario_model.php','application/models/Proveedor_model.php','application/models/Solicitud_compra_model.php','application/models/Pago_model.php',
        'application/views/proveedores/editar.php','tests/validacion_negocio_unit.php','tests/rf_juan_integracion.php','tests/validaciones_http.php') as $archivo) {
        ejecutar_validacion(array('-l',$archivo),'Sintaxis: '.$archivo,$log,$root);
    }
    foreach (array('pagos_unit.php','validacion_negocio_unit.php','rf_juan_integracion.php','validaciones_http.php') as $prueba) {
        ejecutar_validacion(array('tests/'.$prueba),'Prueba: '.$prueba,$log,$root);
    }
    echo "\nAPROBADO: sintaxis, reglas, integración de modelos y aceptación HTTP.\n";
    echo "Evidencias: test-results/validaciones-ejecucion.log y test-results/validaciones-http.json\n";
    echo "Registra la fecha y este resultado en docs/VALIDACION_RF_JUAN.md.\n";
} catch (Throwable $e) { fwrite(STDERR,'FALLO: '.$e->getMessage()."\n");exit(1); }
