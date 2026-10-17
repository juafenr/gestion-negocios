<?php
defined('BASEPATH') or exit('No direct script access allowed');
$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['login'] = 'auth/index';
$route['salir'] = 'auth/salir';
$route['productos'] = 'productos/index';
$route['productos/(:num)'] = 'productos/ver/$1';

$route['inicio'] = 'inicio/index';
$route['inventario'] = 'inventario/index';
$route['inventario/crear'] = 'inventario/crear';
$route['inventario/(:num)/editar'] = 'inventario/editar/$1';

$route['pedidos'] = 'pedidos/index';
$route['pedidos/crear'] = 'pedidos/crear';
$route['pedidos/(:num)'] = 'pedidos/ver/$1';

$route['inventario/exportar'] = 'inventario/exportar';
$route['inventario/(:num)/movimiento'] = 'inventario/movimiento/$1';
$route['inventario/(:num)/historial'] = 'inventario/historial/$1';
$route['inventario/(:num)/estado'] = 'inventario/estado/$1';
$route['pagos'] = 'pagos/index';
$route['pagos/(:num)'] = 'pagos/ver/$1';
$route['pagos/preparar/(:num)'] = 'pagos/preparar/$1';
$route['pagos/separar/(:num)'] = 'pagos/separar/$1';
$route['pagos/reiniciar/(:num)'] = 'pagos/reiniciar/$1';
$route['pagos/registrar/(:num)'] = 'pagos/registrar/$1';
