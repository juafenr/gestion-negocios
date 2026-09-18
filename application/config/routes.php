<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['login'] = 'auth/index';
$route['salir'] = 'auth/salir';
$route['productos'] = 'productos/index';
$route['productos/(:num)'] = 'productos/ver/$1';

$route['inicio'] = 'inicio/index';
