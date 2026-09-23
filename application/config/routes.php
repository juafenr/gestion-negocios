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
