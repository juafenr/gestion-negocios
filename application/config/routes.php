<?php
defined('BASEPATH') or exit('No direct script access allowed');
$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['login'] = 'auth/index';
$route['salir'] = 'auth/salir';
$route['productos'] = 'productos/index';
$route['productos/(:num)'] = 'productos/ver/$1';
$route['productos/(:num)/disponibilidad'] = 'productos/disponibilidad/$1';
$route['usuarios'] = 'usuarios/index';
$route['usuarios/crear'] = 'usuarios/crear';
$route['usuarios/(:num)/editar'] = 'usuarios/editar/$1';
$route['usuarios/(:num)/estado'] = 'usuarios/estado/$1';

$route['inicio'] = 'inicio/index';
$route['sesion/formulario'] = 'inicio/formulario';
$route['inventario'] = 'inventario/index';
$route['inventario/crear'] = 'inventario/crear';
$route['inventario/(:num)/editar'] = 'inventario/editar/$1';

$route['cocina'] = 'cocina/index';
$route['cocina/pedidos/(:num)/estado'] = 'cocina/actualizar_estado/$1';

$route['pedidos'] = 'pedidos/index';
$route['pedidos/crear'] = 'pedidos/crear';
$route['pedidos/(:num)/confirmar'] = 'pedidos/confirmar/$1';
$route['pedidos/(:num)'] = 'pedidos/ver/$1';
$route['pedidos/confirmar/(:num)'] = 'pedidos/confirmar/$1';
$route['pedidos/cancelar/(:num)'] = 'pedidos/cancelar/$1';

$route['inventario/exportar'] = 'inventario/exportar';
$route['inventario/(:num)/movimiento'] = 'inventario/movimiento/$1';
$route['inventario/(:num)/historial'] = 'inventario/historial/$1';
$route['inventario/(:num)/estado'] = 'inventario/estado/$1';
$route['inventario/sugerencias'] = 'inventario/sugerencias';
$route['pagos'] = 'pagos/index';
$route['pagos/(:num)'] = 'pagos/ver/$1';
$route['pagos/preparar/(:num)'] = 'pagos/preparar/$1';
$route['pagos/separar/(:num)'] = 'pagos/separar/$1';
$route['pagos/reiniciar/(:num)'] = 'pagos/reiniciar/$1';
$route['pagos/registrar/(:num)'] = 'pagos/registrar/$1';

// Ventas y documentos de compra (RF de Juan Fernando).
$route['ventas'] = 'ventas/index';
$route['ventas/(:num)'] = 'ventas/ver/$1';
$route['compras'] = 'compras/index';
$route['compras/seleccionar'] = 'compras/seleccionar';
$route['compras/agregar'] = 'compras/agregar';
$route['compras/vaciar'] = 'compras/vaciar';
$route['compras/generar'] = 'compras/generar';
$route['compras/crear'] = 'compras/crear';
$route['compras/proveedor_nuevo'] = 'compras/proveedor_nuevo';
$route['compras/(:num)'] = 'compras/ver/$1';
$route['compras/(:num)/editar'] = 'compras/editar/$1';
$route['compras/(:num)/confirmar'] = 'compras/confirmar/$1';
$route['compras/(:num)/documento'] = 'compras/documento/$1';
$route['compras/(:num)/exportar'] = 'compras/exportar/$1';

$route['proveedores/crear'] = 'compras/proveedor_nuevo';
$route['proveedores'] = 'proveedores/index';
$route['proveedores/(:num)/editar'] = 'proveedores/editar/$1';
$route['proveedores/(:num)/asociar'] = 'proveedores/asociar/$1';
$route['proveedores/(:num)/desasociar'] = 'proveedores/desasociar/$1';
$route['proveedores/(:num)'] = 'proveedores/ver/$1';

$route['compras/(:num)/estado'] = 'compras/cambiar_estado/$1';
$route['compras/proveedor/(:num)/historial'] = 'compras/historial_proveedor/$1';
$route['compras/(:num)/recepcion'] = 'compras/recepcion/$1';
