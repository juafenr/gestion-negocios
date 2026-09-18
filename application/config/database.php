<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$local_file = APPPATH.'config/local.php';
if (!is_file($local_file)) {
    show_error('Falta application/config/local.php. Sigue INSTALACION_LOGIN.md.', 503);
}
$local = require $local_file;
$active_group = 'default';
$query_builder = TRUE;
$db['default'] = array(
    'dsn' => '', 'hostname' => $local['db_host'], 'port' => (int) $local['db_port'],
    'username' => $local['db_user'], 'password' => $local['db_pass'],
    'database' => $local['db_name'], 'dbdriver' => 'mysqli', 'dbprefix' => '',
    'pconnect' => FALSE, 'db_debug' => FALSE, 'cache_on' => FALSE, 'cachedir' => '',
    'char_set' => 'utf8mb4', 'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '', 'encrypt' => FALSE, 'compress' => FALSE,
    'stricton' => TRUE, 'failover' => array(), 'save_queries' => FALSE,
);
