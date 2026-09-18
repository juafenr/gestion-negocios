<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// Copiar a local.php. Solo para XAMPP local; no subir local.php a Git.
return array(
    'base_url' => 'http://localhost/gestion-negocios/',
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'gestion_negocios',
    'db_user' => 'root',
    'db_pass' => '',
    'cookie_secure' => FALSE, // TRUE cuando se publique con HTTPS.
);
