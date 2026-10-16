<?php
// Ejecutar exclusivamente desde terminal. No es una ruta de instalación web.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASEPATH', __DIR__.'/../system/');
$root = dirname(__DIR__);
$archivo = $root.'/application/config/local.php';
if (!is_file($archivo)) { fwrite(STDERR, "Primero copia local.example.php a local.php y configura la conexión.\n"); exit(1); }
if (!extension_loaded('mysqli')) { fwrite(STDERR, "PHP necesita la extensión mysqli. Usa el PHP de XAMPP.\n"); exit(1); }
$c = require $archivo;
if (!preg_match('/^[a-zA-Z0-9_]+$/D', $c['db_name'])) { fwrite(STDERR, "Nombre de base inválido.\n"); exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli($c['db_host'], $c['db_user'], $c['db_pass'], '', (int)$c['db_port']);
    $db->set_charset('utf8mb4');
    $db->query('CREATE DATABASE IF NOT EXISTS `'.$c['db_name'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $db->select_db($c['db_name']);
    if ($db->query('SHOW TABLES')->num_rows > 0) {
        fwrite(STDERR, "La base ya contiene tablas. Instalación cancelada: no se ha borrado nada.\n"); exit(1);
    }
    $db->multi_query(file_get_contents($root.'/database/schema.sql'));
    do { if ($r = $db->store_result()) { $r->free(); } } while ($db->more_results() && $db->next_result());
    $cuentas = array(
        array(1, 'administrador', 'Ana Aurora', 'ana@aurora.test'),
        array(1, 'cajero', 'Carlos Caja', 'cajero@aurora.test'),
        array(1, 'cocinero', 'Carmen Cocina', 'cocina@aurora.test'),
        array(2, 'administrador', 'Bruno Roble', 'bruno@roble.test'),
        array(2, 'cajero', 'Beatriz Caja', 'cajero@roble.test'),
        array(2, 'cocinero', 'Benito Cocina', 'cocina@roble.test')
    );
    $db->begin_transaction();
    $db->query("INSERT INTO empresas (id,nombre) VALUES (1,'Papelería Aurora'),(2,'Ferretería Roble')");
    $s = $db->prepare('INSERT INTO usuarios (empresa_id,rol_id,nombre,correo,password_hash) VALUES (?,?,?,?,?)');
    $roles = array();
    $resultadoRoles = $db->query('SELECT id, codigo FROM roles');
    while ($rol = $resultadoRoles->fetch_assoc()) { $roles[$rol['codigo']] = (int) $rol['id']; }
    $credenciales = array();
    foreach ($cuentas as $cuenta) {
        [$empresa,$rolCodigo,$nombre,$correo] = $cuenta;
        $rolId = $roles[$rolCodigo];
        $password = bin2hex(random_bytes(10));
        $hash = password_hash($password, PASSWORD_BCRYPT, array('cost' => 10));
        $s->bind_param('iisss',$empresa,$rolId,$nombre,$correo,$hash); $s->execute();
        $credenciales[] = array($correo, $rolCodigo, $password);
    }
    $db->query("INSERT INTO productos (id,empresa_id,sku,nombre,precio) VALUES
        (1,1,'A-001','Cuaderno Aurora',18.50),(2,1,'A-002','Lápices Aurora',12.00),
        (3,2,'B-001','Martillo Roble',65.00),(4,2,'B-002','Tornillos Roble',8.00)");
    $db->commit();
    echo "Instalación terminada. Guarda estas contraseñas de PRUEBA; no se guardan en texto plano.\n\n";
    foreach ($credenciales as $credencial) {
        [$correo,$rol,$password] = $credencial;
        echo "$correo ($rol)\nContraseña: $password\n\n";
    }
    echo "Abre ".$c['base_url']."index.php/login\n";
} catch (Throwable $e) {
    fwrite(STDERR, "No se completó la instalación: ".$e->getMessage()."\nRevisa conexión, permisos y tablas antes de repetir.\n"); exit(1);
}
