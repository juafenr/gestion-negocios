<?php
/** Verifica la matriz RBAC instalada. Requiere MySQL y application/config/local.php. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASEPATH', __DIR__.'/../system/');
$archivo = __DIR__.'/../application/config/local.php';
if (!is_file($archivo)) { fwrite(STDERR, "Falta application/config/local.php.\n"); exit(1); }
if (!extension_loaded('mysqli')) { fwrite(STDERR, "PHP necesita la extensión mysqli.\n"); exit(1); }

function comprobar($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK - $mensaje\n";
}

function permisos_de(mysqli $db, $rol)
{
    $s = $db->prepare('SELECT p.codigo FROM permisos p '
        .'JOIN rol_permisos rp ON rp.permiso_id = p.id '
        .'JOIN roles r ON r.id = rp.rol_id WHERE r.codigo = ? ORDER BY p.codigo');
    $s->bind_param('s', $rol);
    $s->execute();
    return array_column($s->get_result()->fetch_all(MYSQLI_ASSOC), 'codigo');
}

try {
    $c = require $archivo;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli($c['db_host'], $c['db_user'], $c['db_pass'], $c['db_name'], (int) $c['db_port']);
    $db->set_charset('utf8mb4');

    $roles = array_column($db->query('SELECT codigo FROM roles ORDER BY codigo')->fetch_all(MYSQLI_ASSOC), 'codigo');
    comprobar($roles === array('administrador', 'cajero', 'cocinero'), 'Existen exactamente los tres roles del prototipo');

    $totalPermisos = (int) $db->query('SELECT COUNT(*) total FROM permisos')->fetch_assoc()['total'];
    comprobar($totalPermisos === 17, 'Existen los 17 permisos definidos');

    $administrador = permisos_de($db, 'administrador');
    comprobar(count($administrador) === $totalPermisos, 'Administrador recibe todos los permisos del prototipo');

    $cajero = permisos_de($db, 'cajero');
    comprobar($cajero === array('inicio.ver', 'pedidos.confirmar', 'pedidos.crear', 'pedidos.ver',
        'productos.ver', 'ventas.cobrar', 'ventas.ver'), 'Cajero recibe únicamente sus permisos');

    $cocinero = permisos_de($db, 'cocinero');
    comprobar($cocinero === array('inicio.ver', 'pedidos.actualizar_cocina', 'pedidos.ver', 'productos.ver'),
        'Cocinero recibe únicamente sus permisos');

    $sinRol = (int) $db->query('SELECT COUNT(*) total FROM usuarios WHERE rol_id IS NULL')->fetch_assoc()['total'];
    comprobar($sinRol === 0, 'Todos los usuarios tienen un rol');

    echo "\nLa matriz RBAC instalada es correcta.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "\nFALLO: ".$e->getMessage()."\n");
    exit(1);
}
