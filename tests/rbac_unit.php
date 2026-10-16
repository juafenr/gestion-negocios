<?php
/** Prueba aislada del objeto de identidad RBAC. No requiere Apache ni MySQL. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASEPATH', __DIR__.'/../system/');
define('APPPATH', __DIR__.'/../application/');
require_once APPPATH.'libraries/UsuarioAutenticado.php';

function comprobar($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK - $mensaje\n";
}

try {
    $usuario = new UsuarioAutenticado(array(
        'id' => 7,
        'empresa_id' => 3,
        'nombre' => 'Usuario de prueba',
        'empresa_nombre' => 'Restaurante de prueba',
        'rol_codigo' => 'cajero',
        'rol_nombre' => 'Cajero',
        'permisos' => array('inicio.ver', 'pedidos.crear', 'ventas.cobrar')
    ));

    comprobar($usuario->id() === 7, 'Conserva el ID del usuario');
    comprobar($usuario->empresaId() === 3, 'Conserva la empresa del usuario');
    comprobar($usuario->rol() === 'cajero', 'Expone el código estable del rol');
    comprobar($usuario->rolNombre() === 'Cajero', 'Expone el nombre visible del rol');
    comprobar($usuario->puede('pedidos.crear'), 'Reconoce un permiso asignado');
    comprobar(!$usuario->puede('inventario.gestionar'), 'Niega por defecto un permiso no asignado');
    comprobar(!$usuario->puede(NULL), 'Rechaza permisos con formato inválido');
    comprobar($usuario->permisos() === array('inicio.ver', 'pedidos.crear', 'ventas.cobrar'),
        'Expone la lista de permisos sin alterarla');

    echo "\nTodas las pruebas unitarias de RBAC finalizaron correctamente.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "\nFALLO: ".$e->getMessage()."\n");
    exit(1);
}
