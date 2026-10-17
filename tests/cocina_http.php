<?php
/** Prueba HTTP de cocina. Crea datos temporales y los elimina al finalizar. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('curl') || !extension_loaded('mysqli')) {
    fwrite(STDERR, "PHP necesita las extensiones curl y mysqli.\n"); exit(1);
}

define('BASEPATH', __DIR__.'/../system/');
$config = require __DIR__.'/../application/config/local.php';
$baseUrl = isset($argv[1]) ? $argv[1] : $config['base_url'];

final class ClienteCocina
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
$prefijo = 'prueba-cocina-'.bin2hex(random_bytes(5));
$password = 'Prueba-'.bin2hex(random_bytes(8));
$usuarios = array();
$productos = array();
$pedidos = array();

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli(
        $config['db_host'],
        $config['db_user'],
        $config['db_pass'],
        $config['db_name'],
        (int) $config['db_port']
    );
    $db->set_charset('utf8mb4');

    $empresas = $db->query('SELECT id FROM empresas ORDER BY id LIMIT 2')->fetch_all(MYSQLI_ASSOC);
    if (count($empresas) < 2) { throw new RuntimeException('La prueba requiere dos empresas.'); }
    $empresaA = (int) $empresas[0]['id'];
    $empresaB = (int) $empresas[1]['id'];

    $roles = array();
    foreach ($db->query('SELECT id, codigo FROM roles')->fetch_all(MYSQLI_ASSOC) as $rol) {
        $roles[$rol['codigo']] = (int) $rol['id'];
    }
    foreach (array('cajero', 'cocinero') as $codigo) {
        if (!isset($roles[$codigo])) { throw new RuntimeException('Falta el rol '.$codigo.'.'); }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, array('cost' => 10));
    $insertarUsuario = $db->prepare(
        'INSERT INTO usuarios (empresa_id, rol_id, nombre, correo, password_hash)
         VALUES (?, ?, ?, ?, ?)'
    );
    $cuentas = array(
        array($empresaA, $roles['cajero'], 'Cajero cocina temporal', $prefijo.'-cajero@example.test'),
        array($empresaA, $roles['cocinero'], 'Cocinero temporal A', $prefijo.'-cocinero-a@example.test'),
        array($empresaB, $roles['cocinero'], 'Cocinero temporal B', $prefijo.'-cocinero-b@example.test')
    );
    foreach ($cuentas as $cuenta) {
        [$empresaId, $rolId, $nombre, $correo] = $cuenta;
        $insertarUsuario->bind_param('iisss', $empresaId, $rolId, $nombre, $correo, $hash);
        $insertarUsuario->execute();
        $usuarios[] = (int) $db->insert_id;
    }
    [$cajeroId, $cocineroAId, $cocineroBId] = $usuarios;

    $insertarProducto = $db->prepare(
        'INSERT INTO productos (empresa_id, sku, nombre, precio) VALUES (?, ?, ?, 25.00)'
    );
    foreach (array($empresaA, $empresaB) as $indice => $empresaId) {
        $sku = strtoupper(substr($prefijo, -10)).'-'.$indice;
        $nombre = 'Producto cocina temporal '.($indice + 1);
        $insertarProducto->bind_param('iss', $empresaId, $sku, $nombre);
        $insertarProducto->execute();
        $productos[] = (int) $db->insert_id;
    }

    $numeroA = (int) $db->query('SELECT COALESCE(MAX(numero), 0) + 1 numero FROM pedidos WHERE empresa_id = '.$empresaA)->fetch_assoc()['numero'];
    $numeroB = (int) $db->query('SELECT COALESCE(MAX(numero), 0) + 1 numero FROM pedidos WHERE empresa_id = '.$empresaB)->fetch_assoc()['numero'];
    $insertarPedido = $db->prepare(
        'INSERT INTO pedidos (empresa_id, numero, usuario_id, referencia, estado, estado_pago, total)
         VALUES (?, ?, ?, ?, ?, ?, 25.00)'
    );
    $estado = 'borrador';
    $pago = 'pendiente';
    $referenciaA = $prefijo.' pedido A';
    $insertarPedido->bind_param('iiisss', $empresaA, $numeroA, $cajeroId, $referenciaA, $estado, $pago);
    $insertarPedido->execute();
    $pedidoA = (int) $db->insert_id;
    $pedidos[] = $pedidoA;

    $referenciaB = $prefijo.' pedido B';
    $insertarPedido->bind_param('iiisss', $empresaB, $numeroB, $cocineroBId, $referenciaB, $estado, $pago);
    $insertarPedido->execute();
    $pedidoB = (int) $db->insert_id;
    $pedidos[] = $pedidoB;

    $insertarDetalle = $db->prepare(
        'INSERT INTO pedido_detalles
         (empresa_id, pedido_id, producto_id, producto_nombre, cantidad, precio_unitario)
         VALUES (?, ?, ?, ?, 1, 25.00)'
    );
    $nombreA = 'Producto cocina temporal 1';
    $insertarDetalle->bind_param('iiis', $empresaA, $pedidoA, $productos[0], $nombreA);
    $insertarDetalle->execute();
    $nombreB = 'Producto cocina temporal 2';
    $insertarDetalle->bind_param('iiis', $empresaB, $pedidoB, $productos[1], $nombreB);
    $insertarDetalle->execute();

    $anonimo = new ClienteCocina($baseUrl);
    comprobar(redirige_a($anonimo->pedir('cocina'), 'login'), 'Cocina exige autenticación');

    $cajero = new ClienteCocina($baseUrl);
    comprobar(redirige_a($cajero->login($cuentas[0][3], $password), 'inicio'), 'El cajero temporal inicia sesión');
    comprobar($cajero->pedir('cocina')['status'] === 403, 'El cajero no puede abrir Cocina');

    $detalle = $cajero->pedir('pedidos/'.$pedidoA);
    $confirmado = $cajero->pedir('pedidos/'.$pedidoA.'/confirmar', array(
        'gestion_csrf' => ClienteCocina::token($detalle['body'])
    ));
    comprobar(redirige_a($confirmado, 'pedidos/'.$pedidoA), 'El cajero confirma el pedido');

    $cocineroA = new ClienteCocina($baseUrl);
    comprobar(redirige_a($cocineroA->login($cuentas[1][3], $password), 'inicio'), 'El cocinero A inicia sesión');
    $colaA = $cocineroA->pedir('cocina');
    comprobar(
        $colaA['status'] === 200
        && str_contains($colaA['body'], $referenciaA)
        && !str_contains($colaA['body'], $referenciaB),
        'La cola muestra únicamente pedidos confirmados de la empresa autenticada'
    );

    $cocineroB = new ClienteCocina($baseUrl);
    comprobar(redirige_a($cocineroB->login($cuentas[2][3], $password), 'inicio'), 'El cocinero B inicia sesión');
    $colaB = $cocineroB->pedir('cocina');
    $ajeno = $cocineroB->pedir('cocina/pedidos/'.$pedidoA.'/estado', array(
        'estado' => 'preparacion',
        'gestion_csrf' => ClienteCocina::token($colaB['body'])
    ));
    comprobar($ajeno['status'] === 404, 'Cambiar un pedido de otra empresa responde 404');

    $salto = $cocineroA->pedir('cocina/pedidos/'.$pedidoA.'/estado', array(
        'estado' => 'listo',
        'gestion_csrf' => ClienteCocina::token($colaA['body'])
    ));
    comprobar($salto['status'] === 400, 'Cocina rechaza saltar de pendiente a listo');

    $transiciones = array(
        'preparacion' => 'Inicia la preparación',
        'listo' => 'Marca el pedido como listo',
        'entregado' => 'Marca el pedido como entregado'
    );
    foreach ($transiciones as $estadoNuevo => $mensaje) {
        $pagina = $cocineroA->pedir('cocina');
        $respuesta = $cocineroA->pedir('cocina/pedidos/'.$pedidoA.'/estado', array(
            'estado' => $estadoNuevo,
            'gestion_csrf' => ClienteCocina::token($pagina['body'])
        ));
        comprobar(redirige_a($respuesta, 'cocina'), $mensaje);
    }

    $fila = $db->query('SELECT estado FROM pedidos WHERE id = '.$pedidoA)->fetch_assoc();
    comprobar($fila['estado'] === 'entregado', 'El pedido termina en estado entregado');
    $eventos = (int) $db->query('SELECT COUNT(*) total FROM pedido_eventos WHERE pedido_id = '.$pedidoA)->fetch_assoc()['total'];
    comprobar($eventos === 4, 'Se registran confirmación y tres transiciones con trazabilidad');
    comprobar(!str_contains($cocineroA->pedir('cocina')['body'], $referenciaA), 'El pedido entregado sale de la cola activa');

    $historial = $cajero->pedir('pedidos/'.$pedidoA);
    comprobar(
        $historial['status'] === 200
        && str_contains($historial['body'], 'Cajero cocina temporal')
        && str_contains($historial['body'], 'Cocinero temporal A')
        && str_contains($historial['body'], 'Borrador')
        && str_contains($historial['body'], 'Entregado'),
        'El detalle muestra estados, responsables y roles del historial'
    );

    echo "\nTodas las pruebas HTTP de cocina finalizaron correctamente.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "\nFALLO: ".$error->getMessage()."\n");
    $codigoSalida = 1;
} finally {
    if ($db instanceof mysqli) {
        if ($pedidos) {
            $ids = implode(',', array_map('intval', $pedidos));
            $db->query('DELETE FROM pedido_eventos WHERE pedido_id IN ('.$ids.')');
            $db->query('DELETE FROM pedido_detalles WHERE pedido_id IN ('.$ids.')');
            $db->query('DELETE FROM pedidos WHERE id IN ('.$ids.')');
        }
        if ($productos) {
            $db->query('DELETE FROM productos WHERE id IN ('.implode(',', array_map('intval', $productos)).')');
        }
        if ($usuarios) {
            $db->query('DELETE FROM usuarios WHERE id IN ('.implode(',', array_map('intval', $usuarios)).')');
        }
        echo "Datos temporales eliminados correctamente.\n";
    }
}

exit($codigoSalida ?? 0);
