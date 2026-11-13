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
        'application/libraries/Edicion_segura.php','application/views/plantilla/historial_cambios.php','application/core/MY_Controller.php','tests/interfaz_unit.php','application/views/plantilla/disponibilidad_venta.php','application/config/routes.php','application/views/inventario/formulario.php',
        'application/helpers/interfaz_helper.php','application/views/plantilla/notificaciones.php',
        'application/views/plantilla/filtros.php','application/views/plantilla/campo_filtro.php',
        'application/views/plantilla/accesos_inicio.php','application/views/plantilla/actividad_ventas.php','application/views/inicio/index.php',
        'application/controllers/Productos.php','application/models/Producto_model.php',
        'application/views/productos/index.php','application/views/productos/formulario.php','application/views/productos/detalle.php',
        'application/controllers/Usuarios.php','application/controllers/Proveedores.php','application/controllers/Inventario.php','application/controllers/Pedidos.php','application/controllers/Compras.php',
        'application/models/Usuario_model.php','application/models/Proveedor_model.php','application/models/Solicitud_compra_model.php','application/models/Pago_model.php',
        'application/views/proveedores/editar.php','tests/validacion_negocio_unit.php','tests/rf_juan_integracion.php','tests/validaciones_http.php',
        'tests/migracion_catalogo_propina.php','application/libraries/Imagen_catalogo.php','tools/cargar_demo_catalogo.php') as $archivo) {
        ejecutar_validacion(array('-l',$archivo),'Sintaxis: '.$archivo,$log,$root);
    }
    foreach (array('interfaz_unit.php','pagos_unit.php','validacion_negocio_unit.php','rf_juan_integracion.php','validaciones_http.php','migracion_catalogo_propina.php') as $prueba) {
        ejecutar_validacion(array('tests/'.$prueba),'Prueba: '.$prueba,$log,$root);
    }
    echo "\nAPROBADO: sintaxis, reglas, integración de modelos y aceptación HTTP.\n";
    echo "Evidencias: test-results/validaciones-ejecucion.log y test-results/validaciones-http.json\n";
    echo "Conserva el registro de test-results como evidencia de la validación.\n";
} catch (Throwable $e) { fwrite(STDERR,'FALLO: '.$e->getMessage()."\n");exit(1); }
