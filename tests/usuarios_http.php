<?php
/** Prueba HTTP del módulo de usuarios. Crea datos temporales y los elimina al finalizar. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('curl') || !extension_loaded('mysqli')) {
    fwrite(STDERR, "PHP necesita las extensiones curl y mysqli.\n"); exit(1);
}

define('BASEPATH', __DIR__.'/../system/');
$config = require __DIR__.'/../application/config/local.php';

final class ClienteUsuarios
{
    private $curl;
    private $base;

    public function __construct($base)
    {
        $this->base = rtrim($base, '/').'/index.php/';
        $this->curl = curl_init();
        curl_setopt_array($this->curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => FALSE,
            CURLOPT_COOKIEFILE => '',
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15
        ));
    }

    public function pedir($ruta, $datos = NULL)
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->base.$ruta);
        if ($datos === NULL) {
            curl_setopt($this->curl, CURLOPT_HTTPGET, TRUE);
        } else {
            curl_setopt($this->curl, CURLOPT_POST, TRUE);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        $body = curl_exec($this->curl);
        if ($body === FALSE) { throw new RuntimeException(curl_error($this->curl)); }
        if (strpos($body, 'A PHP Error was encountered') !== FALSE || strpos($body, 'Fatal error:') !== FALSE) {
            throw new RuntimeException('La respuesta contiene un error de PHP.');
        }
        return array(
            'status' => curl_getinfo($this->curl, CURLINFO_HTTP_CODE),
            'body' => $body,
            'redirect' => curl_getinfo($this->curl, CURLINFO_REDIRECT_URL)
        );
    }

    public function login($correo, $password)
    {
        $formulario = $this->pedir('login');
        return $this->pedir('login', array(
            'correo' => $correo,
            'password' => $password,
            'gestion_csrf' => self::token($formulario['body'])
        ));
    }

    public static function token($html)
    {
        if (!preg_match('/name="gestion_csrf" value="([^"]+)"/', $html, $coincidencia)) {
            throw new RuntimeException('No se encontró un token CSRF en el formulario.');
        }
        return html_entity_decode($coincidencia[1], ENT_QUOTES, 'UTF-8');
    }
}

function comprobar($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK - $mensaje\n";
}

function redirige_a($respuesta, $destino)
{
    $ruta = parse_url($respuesta['redirect'], PHP_URL_PATH) ?: '';
    return in_array($respuesta['status'], array(302, 303, 307), TRUE)
        && str_ends_with($ruta, '/'.$destino);
}

$db = NULL;
$prefijo = 'prueba-usuarios-'.bin2hex(random_bytes(5));
$password = 'Prueba-'.bin2hex(random_bytes(8));
$idsTemporales = array();

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name'], (int) $config['db_port']);
    $db->set_charset('utf8mb4');

    $empresas = $db->query('SELECT id FROM empresas ORDER BY id LIMIT 2')->fetch_all(MYSQLI_ASSOC);
    if (count($empresas) < 2) { throw new RuntimeException('La prueba requiere al menos dos empresas.'); }
    $empresaA = (int) $empresas[0]['id'];
    $empresaB = (int) $empresas[1]['id'];
    $roles = array();
    foreach ($db->query('SELECT id, codigo FROM roles')->fetch_all(MYSQLI_ASSOC) as $rol) {
        $roles[$rol['codigo']] = (int) $rol['id'];
    }
    foreach (array('administrador', 'cajero', 'cocinero') as $codigo) {
        if (!isset($roles[$codigo])) { throw new RuntimeException('Falta el rol '.$codigo.'.'); }
    }

    $insertar = $db->prepare('INSERT INTO usuarios (empresa_id, rol_id, nombre, correo, password_hash) VALUES (?, ?, ?, ?, ?)');
    $hash = password_hash($password, PASSWORD_BCRYPT, array('cost' => 10));
    $cuentas = array(
        array($empresaA, $roles['administrador'], 'Administrador temporal', $prefijo.'-admin@example.test'),
        array($empresaA, $roles['cajero'], 'Cajero temporal', $prefijo.'-cajero@example.test'),
        array($empresaA, $roles['cocinero'], 'Cocinero temporal', $prefijo.'-cocinero@example.test'),
        array($empresaB, $roles['cajero'], 'Usuario empresa ajena', $prefijo.'-ajeno@example.test')
    );
    foreach ($cuentas as $cuenta) {
        [$empresaId, $rolId, $nombre, $correo] = $cuenta;
        $insertar->bind_param('iisss', $empresaId, $rolId, $nombre, $correo, $hash);
        $insertar->execute();
        $idsTemporales[] = $db->insert_id;
    }
    [$adminId, $cajeroId, $cocineroId, $ajenoId] = $idsTemporales;

    $anonimo = new ClienteUsuarios($config['base_url']);
    comprobar(redirige_a($anonimo->pedir('usuarios'), 'login'), 'El listado exige autenticación');

    $cajero = new ClienteUsuarios($config['base_url']);
    comprobar(redirige_a($cajero->login($cuentas[1][3], $password), 'inicio'), 'El cajero temporal inicia sesión');
    comprobar($cajero->pedir('usuarios')['status'] === 403, 'El cajero recibe 403 en Usuarios');

    $cocinero = new ClienteUsuarios($config['base_url']);
    comprobar(redirige_a($cocinero->login($cuentas[2][3], $password), 'inicio'), 'El cocinero temporal inicia sesión');
    comprobar($cocinero->pedir('usuarios')['status'] === 403, 'El cocinero recibe 403 en Usuarios');

    $admin = new ClienteUsuarios($config['base_url']);
    comprobar(redirige_a($admin->login($cuentas[0][3], $password), 'inicio'), 'El administrador temporal inicia sesión');
    $lista = $admin->pedir('usuarios');
    comprobar($lista['status'] === 200, 'El administrador abre el listado');
    comprobar(str_contains($lista['body'], 'Cajero temporal') && !str_contains($lista['body'], 'Usuario empresa ajena'),
        'El listado muestra solo usuarios de la empresa autenticada');
    comprobar($admin->pedir('usuarios/'.$ajenoId.'/editar')['status'] === 404,
        'Editar un usuario de otra empresa responde 404');

    comprobar($admin->pedir('usuarios/crear', array())['status'] === 403, 'Crear sin CSRF es rechazado');
    $formularioCrear = $admin->pedir('usuarios/crear');
    $correoNuevo = $prefijo.'-nuevo@example.test';
    $creado = $admin->pedir('usuarios/crear', array(
        'nombre' => 'Nuevo usuario temporal',
        'correo' => $correoNuevo,
        'rol_id' => $roles['cajero'],
        'password' => $password,
        'gestion_csrf' => ClienteUsuarios::token($formularioCrear['body'])
    ));
    comprobar(redirige_a($creado, 'usuarios'), 'El administrador crea un usuario');
    $nuevo = $db->query("SELECT id, empresa_id, rol_id, activo, password_hash FROM usuarios WHERE correo = '".$db->real_escape_string($correoNuevo)."'")->fetch_assoc();
    comprobar($nuevo && (int) $nuevo['empresa_id'] === $empresaA && password_verify($password, $nuevo['password_hash']),
        'La cuenta creada pertenece a la empresa correcta y almacena un hash válido');
    $nuevoId = (int) $nuevo['id'];
    $idsTemporales[] = $nuevoId;

    $formularioEditar = $admin->pedir('usuarios/'.$nuevoId.'/editar');
    $editado = $admin->pedir('usuarios/'.$nuevoId.'/editar', array(
        'nombre' => 'Usuario editado temporal',
        'rol_id' => $roles['cocinero'],
        'gestion_csrf' => ClienteUsuarios::token($formularioEditar['body'])
    ));
    comprobar(redirige_a($editado, 'usuarios'), 'El administrador edita nombre y rol');
    $filaEditada = $db->query('SELECT nombre, rol_id FROM usuarios WHERE id = '.$nuevoId)->fetch_assoc();
    comprobar($filaEditada['nombre'] === 'Usuario editado temporal' && (int) $filaEditada['rol_id'] === $roles['cocinero'],
        'La edición quedó almacenada');

    $formularioPropio = $admin->pedir('usuarios/'.$adminId.'/editar');
    comprobar((bool) preg_match('/<select\b[^>]*\bid="rol_id"[^>]*\bdisabled\b/', $formularioPropio['body'])
        && strpos($formularioPropio['body'], 'id="nota-rol-propio"') !== FALSE,
        'El formulario bloquea el rol propio y explica el motivo');
    comprobar(strpos($formularioPropio['body'], 'type="hidden" name="rol_id" value="'.$roles['administrador'].'"') !== FALSE,
        'El formulario conserva el rol propio al guardar otros datos');
    $cambioPropio = $admin->pedir('usuarios/'.$adminId.'/editar', array(
        'nombre' => 'Administrador temporal',
        'rol_id' => $roles['cajero'],
        'gestion_csrf' => ClienteUsuarios::token($formularioPropio['body'])
    ));
    comprobar($cambioPropio['status'] === 422, 'El administrador no puede cambiar su propio rol');
    $rolPropio = (int) $db->query('SELECT rol_id FROM usuarios WHERE id = '.$adminId)->fetch_assoc()['rol_id'];
    comprobar($rolPropio === $roles['administrador'], 'El intento manipulado no cambia el rol almacenado');
    $formularioPropio = $admin->pedir('usuarios/'.$adminId.'/editar');
    $nombrePropio = $admin->pedir('usuarios/'.$adminId.'/editar', array(
        'nombre' => 'Administrador con nombre actualizado',
        'rol_id' => $roles['administrador'],
        'gestion_csrf' => ClienteUsuarios::token($formularioPropio['body'])
    ));
    $filaPropia = $db->query('SELECT nombre, rol_id FROM usuarios WHERE id = '.$adminId)->fetch_assoc();
    comprobar(redirige_a($nombrePropio, 'usuarios')
        && $filaPropia['nombre'] === 'Administrador con nombre actualizado'
        && (int) $filaPropia['rol_id'] === $roles['administrador'],
        'El administrador puede editar su nombre conservando su rol');

    $lista = $admin->pedir('usuarios');
    $estadoPropio = $admin->pedir('usuarios/'.$adminId.'/estado', array(
        'gestion_csrf' => ClienteUsuarios::token($lista['body'])
    ));
    comprobar($estadoPropio['status'] === 409, 'El administrador no puede desactivar su propia cuenta');

    $lista = $admin->pedir('usuarios');
    $estado = $admin->pedir('usuarios/'.$nuevoId.'/estado', array(
        'gestion_csrf' => ClienteUsuarios::token($lista['body'])
    ));
    comprobar(redirige_a($estado, 'usuarios'), 'El administrador desactiva otra cuenta');
    $activo = (int) $db->query('SELECT activo FROM usuarios WHERE id = '.$nuevoId)->fetch_assoc()['activo'];
    comprobar($activo === 0, 'La desactivación quedó almacenada');

    $lista = $admin->pedir('usuarios');
    $estadoAjeno = $admin->pedir('usuarios/'.$ajenoId.'/estado', array(
        'gestion_csrf' => ClienteUsuarios::token($lista['body'])
    ));
    comprobar($estadoAjeno['status'] === 404, 'Cambiar el estado de un usuario de otra empresa responde 404');

    echo "\nTodas las pruebas HTTP de gestión de usuarios finalizaron correctamente.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "\nFALLO: ".$e->getMessage()."\n");
    $codigoSalida = 1;
} finally {
    if ($db instanceof mysqli && $idsTemporales) {
        $ids = implode(',', array_map('intval', array_unique($idsTemporales)));
        $db->query('DELETE FROM usuarios WHERE id IN ('.$ids.')');
        $restantes = (int) $db->query('SELECT COUNT(*) total FROM usuarios WHERE id IN ('.$ids.')')->fetch_assoc()['total'];
        if ($restantes === 0) { echo "Datos temporales eliminados correctamente.\n"; }
        else { fwrite(STDERR, "FALLO: quedaron datos temporales sin eliminar.\n"); $codigoSalida = 1; }
    }
}

exit($codigoSalida ?? 0);
