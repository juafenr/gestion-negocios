<?php
/** Comprueba filtros, paginación y aislamiento del historial de pedidos. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('curl') || !extension_loaded('mysqli')) {
    fwrite(STDERR, "PHP necesita curl y mysqli.\n"); exit(1);
}
define('BASEPATH', __DIR__.'/../system/');
$config = require __DIR__.'/../application/config/local.php';

final class ClienteHistorial
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
        if ($datos === NULL) { curl_setopt($this->curl, CURLOPT_HTTPGET, TRUE); }
        else { curl_setopt($this->curl, CURLOPT_POST, TRUE); curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($datos)); }
        $body = curl_exec($this->curl);
        if ($body === FALSE) { throw new RuntimeException(curl_error($this->curl)); }
        if (str_contains($body, 'A PHP Error was encountered') || str_contains($body, 'Fatal error:')) {
            throw new RuntimeException('La respuesta contiene un error de PHP.');
        }
        return array('status' => curl_getinfo($this->curl, CURLINFO_HTTP_CODE),
            'body' => $body, 'redirect' => curl_getinfo($this->curl, CURLINFO_REDIRECT_URL));
    }
    public function login($usuario, $password)
    {
        $pagina = $this->pedir('login');
        if (!preg_match('/name="gestion_csrf" value="([^"]+)"/', $pagina['body'], $m)) {
            throw new RuntimeException('No se encontró CSRF.');
        }
        return $this->pedir('login', array('identificador' => $usuario,
            'password' => $password, 'gestion_csrf' => html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')));
    }
}

function comprobar_historial($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK - $mensaje\n";
}

$db = NULL;
$usuarios = array();
$pedidos = array();
$prefijo = 'prueba-historial-'.bin2hex(random_bytes(5));
$password = 'Prueba-'.bin2hex(random_bytes(8));
$salida = 0;
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'],
        $config['db_name'], (int) $config['db_port']);
    $db->set_charset('utf8mb4');
    $empresas = $db->query('SELECT id FROM empresas ORDER BY id LIMIT 2')->fetch_all(MYSQLI_ASSOC);
    if (count($empresas) < 2) { throw new RuntimeException('Se requieren dos empresas.'); }
    $empresaA = (int) $empresas[0]['id'];
    $empresaB = (int) $empresas[1]['id'];
    $rol = (int) $db->query("SELECT id FROM roles WHERE codigo='administrador'")->fetch_assoc()['id'];
    $hash = password_hash($password, PASSWORD_BCRYPT, array('cost' => 10));
    $insertarUsuario = $db->prepare('INSERT INTO usuarios
        (empresa_id, rol_id, nombre, nombre_usuario, correo, password_hash)
        VALUES (?, ?, ?, ?, ?, ?)');
    foreach (array($empresaA, $empresaB) as $indice => $empresa) {
        $nombre = 'Admin historial temporal';
        $usuario = $prefijo.'-'.$indice;
        $correo = $usuario.'@example.test';
        $insertarUsuario->bind_param('iissss', $empresa, $rol, $nombre, $usuario, $correo, $hash);
        $insertarUsuario->execute();
        $usuarios[] = (int) $db->insert_id;
    }
    $siguienteA = (int) $db->query('SELECT COALESCE(MAX(numero),0)+1 AS siguiente FROM pedidos WHERE empresa_id='.$empresaA)->fetch_assoc()['siguiente'];
    $siguienteB = (int) $db->query('SELECT COALESCE(MAX(numero),0)+1 AS siguiente FROM pedidos WHERE empresa_id='.$empresaB)->fetch_assoc()['siguiente'];
    $insertarPedido = $db->prepare('INSERT INTO pedidos
        (empresa_id, numero, usuario_id, referencia, estado, estado_pago, total)
        VALUES (?, ?, ?, ?, ?, ?, 0)');
    $estado = 'pendiente';
    $pago = 'pendiente';
    for ($i = 1; $i <= 21; $i++) {
        $numero = $siguienteA + $i - 1;
        $referencia = $prefijo.'_A'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        $insertarPedido->bind_param('iiisss', $empresaA, $numero, $usuarios[0], $referencia, $estado, $pago);
        $insertarPedido->execute();
        $pedidos[] = (int) $db->insert_id;
    }
    $referenciaB = $prefijo.'_B01';
    $insertarPedido->bind_param('iiisss', $empresaB, $siguienteB, $usuarios[1], $referenciaB, $estado, $pago);
    $insertarPedido->execute();
    $pedidos[] = (int) $db->insert_id;

    $clienteA = new ClienteHistorial($config['base_url']);
    comprobar_historial($clienteA->login($prefijo.'-0', $password)['status'] === 303,
        'Administrador A inicia sesión');
    $busqueda = 'pedidos?busqueda='.rawurlencode($prefijo);
    $primera = $clienteA->pedir($busqueda);
    comprobar_historial($primera['status'] === 200
        && str_contains($primera['body'], '21</strong>')
        && str_contains($primera['body'], 'Página 1 de 2')
        && !str_contains($primera['body'], $referenciaB),
        'Búsqueda cuenta solo los pedidos de la empresa A');
    $segunda = $clienteA->pedir($busqueda.'&pagina=2');
    comprobar_historial($segunda['status'] === 200
        && str_contains($segunda['body'], 'Página 2 de 2')
        && str_contains($segunda['body'], $prefijo.'_A01'),
        'La segunda página conserva pedidos anteriores');
    comprobar_historial($clienteA->pedir($busqueda.'&estado=cancelado')['status'] === 200
        && str_contains($clienteA->pedir($busqueda.'&estado=cancelado')['body'], '0</strong>'),
        'El filtro por estado reduce el historial');
    comprobar_historial($clienteA->pedir('pedidos?desde=2026-02-30')['status'] === 400,
        'Una fecha imposible se rechaza');
    comprobar_historial($clienteA->pedir('pedidos?estado=desconocido')['status'] === 400,
        'Un estado desconocido se rechaza');

    $clienteB = new ClienteHistorial($config['base_url']);
    comprobar_historial($clienteB->login($prefijo.'-1', $password)['status'] === 303,
        'Administrador B inicia sesión');
    $listaB = $clienteB->pedir($busqueda);
    comprobar_historial($listaB['status'] === 200
        && str_contains($listaB['body'], $referenciaB)
        && !str_contains($listaB['body'], $prefijo.'_A01'),
        'La empresa B no recibe pedidos de A');
    echo "Pruebas HTTP del historial completadas.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "FALLO: ".$error->getMessage()."\n");
    $salida = 1;
} finally {
    if ($db instanceof mysqli) {
        if ($pedidos) { $db->query('DELETE FROM pedidos WHERE id IN ('.implode(',', $pedidos).')'); }
        if ($usuarios) { $db->query('DELETE FROM usuarios WHERE id IN ('.implode(',', $usuarios).')'); }
        echo "Datos temporales eliminados.\n";
    }
}
exit($salida);
