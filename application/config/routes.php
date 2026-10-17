<?php
defined('BASEPATH') or exit('No direct script access allowed');
$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['login'] = 'auth/index';
$route['salir'] = 'auth/salir';
$route['productos'] = 'productos/index';
$route['productos/(:num)'] = 'productos/ver/$1';
$route['usuarios'] = 'usuarios/index';
$route['usuarios/crear'] = 'usuarios/crear';
$route['usuarios/(:num)/editar'] = 'usuarios/editar/$1';
$route['usuarios/(:num)/estado'] = 'usuarios/estado/$1';

$route['inicio'] = 'inicio/index';
$route['inventario'] = 'inventario/index';
$route['inventario/crear'] = 'inventario/crear';
$route['inventario/(:num)/editar'] = 'inventario/editar/$1';

$route['cocina'] = 'cocina/index';
$route['cocina/pedidos/(:num)/estado'] = 'cocina/actualizar_estado/$1';

$route['pedidos'] = 'pedidos/index';
$route['pedidos/crear'] = 'pedidos/crear';
$route['pedidos/(:num)/confirmar'] = 'pedidos/confirmar/$1';
$route['pedidos/(:num)'] = 'pedidos/ver/$1';
