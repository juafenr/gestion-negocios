<?php
/** Prueba HTTP de proveedores, selección y recepción con datos temporales. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('curl') || !extension_loaded('mysqli')) {
    fwrite(STDERR, "PHP necesita curl y mysqli.\n"); exit(1);
}
define('BASEPATH', __DIR__.'/../system/');
$config = require __DIR__.'/../application/config/local.php';

final class ClienteCompras
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
            CURLOPT_INTERFACE => '127.0.0.2',
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

    public function login($identificador, $password)
    {
        $pagina = $this->pedir('login');
        return $this->pedir('login', array(
            'identificador' => $identificador,
            'password' => $password,
            'gestion_csrf' => self::token($pagina['body'])
        ));
    }

    public static function token($html)
    {
        if (!preg_match('/name="gestion_csrf" value="([^"]+)"/', $html, $m)) {
            throw new RuntimeException('No se encontró el token CSRF.');
        }
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
}

function comprobar_compra($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK - $mensaje\n";
}

$db = NULL;
$usuarios = array();
$insumos = array();
$proveedores = array();
$prefijo = 'prueba-compra-'.bin2hex(random_bytes(5));
$password = 'Prueba-'.bin2hex(random_bytes(8));
$codigoSalida = 0;
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'],
        $config['db_name'], (int) $config['db_port']);
    $db->set_charset('utf8mb4');
    $empresas = $db->query('SELECT id FROM empresas ORDER BY id LIMIT 2')->fetch_all(MYSQLI_ASSOC);
    if (count($empresas) < 2) { throw new RuntimeException('Se requieren dos empresas.'); }
    $empresaA = (int) $empresas[0]['id'];
    $empresaB = (int) $empresas[1]['id'];
    $roles = array();
    foreach ($db->query('SELECT id, codigo FROM roles') as $rol) { $roles[$rol['codigo']] = (int) $rol['id']; }
    $hash = password_hash($password, PASSWORD_BCRYPT, array('cost' => 10));
    $insertarUsuario = $db->prepare('INSERT INTO usuarios
        (empresa_id, rol_id, nombre, nombre_usuario, correo, password_hash)
        VALUES (?, ?, ?, ?, ?, ?)');
    $cuentas = array(
        array($empresaA, $roles['administrador'], 'Admin compra A', $prefijo.'-a'),
        array($empresaB, $roles['administrador'], 'Admin compra B', $prefijo.'-b'),
        array($empresaA, $roles['cajero'], 'Cajero compra A', $prefijo.'-c')
    );
    foreach ($cuentas as $indice => $cuenta) {
        [$empresa, $rol, $nombre, $nombreUsuario] = $cuenta;
        $correo = $prefijo.'-'.$indice.'@example.test';
        $insertarUsuario->bind_param('iissss', $empresa, $rol, $nombre, $nombreUsuario, $correo, $hash);
        $insertarUsuario->execute();
        $usuarios[] = (int) $db->insert_id;
    }

    $insertarInsumo = $db->prepare('INSERT INTO insumos
        (empresa_id, nombre, cantidad_actual, stock_minimo, unidad_medida, activo)
        VALUES (?, ?, 0, 2, ?, ?)');
    $datosInsumos = array(
        array($empresaA, $prefijo.' harina A', 'kg', 1),
        array($empresaB, $prefijo.' harina B', 'kg', 1),
        array($empresaA, $prefijo.' inactivo A', 'unidad', 0)
    );
    foreach ($datosInsumos as $dato) {
        [$empresa, $nombre, $unidad, $activo] = $dato;
        $insertarInsumo->bind_param('issi', $empresa, $nombre, $unidad, $activo);
        $insertarInsumo->execute();
        $insumos[] = (int) $db->insert_id;
    }

    $anonimo = new ClienteCompras($config['base_url']);
    comprobar_compra($anonimo->pedir('compras/seleccionar')['status'] === 307, 'Compras exige sesión');
    $cajero = new ClienteCompras($config['base_url']);
    comprobar_compra($cajero->login($cuentas[2][3], $password)['status'] === 303, 'Cajero inicia sesión');
    comprobar_compra($cajero->pedir('compras/seleccionar')['status'] === 403, 'Cajero no puede preparar compras');

    $adminA = new ClienteCompras($config['base_url']);
    comprobar_compra($adminA->login($cuentas[0][3], $password)['status'] === 303, 'Administrador A inicia sesión');
    $paginaA = $adminA->pedir('compras/seleccionar');
    comprobar_compra($paginaA['status'] === 200
        && str_contains($paginaA['body'], $datosInsumos[0][1])
        && !str_contains($paginaA['body'], $datosInsumos[1][1])
        && !str_contains($paginaA['body'], $datosInsumos[2][1]),
        'La selección muestra solo insumos activos de la empresa');
    comprobar_compra($adminA->pedir('compras/seleccionar', array('cantidad' => array($insumos[0] => '1')))['status'] === 403,
        'La selección exige CSRF');

    $paginaA = $adminA->pedir('compras/seleccionar');
    $ajeno = $adminA->pedir('compras/seleccionar', array(
        'cantidad' => array($insumos[1] => '1'),
        'gestion_csrf' => ClienteCompras::token($paginaA['body'])
    ));
    comprobar_compra($ajeno['status'] === 422, 'Un insumo de otra empresa se rechaza');

    $paginaA = $adminA->pedir('compras/seleccionar');
    $invalido = $adminA->pedir('compras/seleccionar', array(
        'cantidad' => array($insumos[0] => '-1'),
        'gestion_csrf' => ClienteCompras::token($paginaA['body'])
    ));
    comprobar_compra($invalido['status'] === 422, 'Una cantidad negativa se rechaza');

    $paginaA = $adminA->pedir('compras/seleccionar');
    $valido = $adminA->pedir('compras/seleccionar', array(
        'cantidad' => array($insumos[0] => '1.25'),
        'gestion_csrf' => ClienteCompras::token($paginaA['body'])
    ));
    comprobar_compra($valido['status'] === 303, 'La selección válida se guarda en la sesión');
    $revisada = $adminA->pedir('compras/seleccionar');
    comprobar_compra($revisada['status'] === 200
        && str_contains($revisada['body'], 'Selección revisada')
        && str_contains($revisada['body'], '1.25 kg')
        && !str_contains($revisada['body'], $datosInsumos[1][1]),
        'La selección permanece al volver a abrir la página sin mezclar empresas');

    $invalidoPosterior = $adminA->pedir('compras/seleccionar', array(
        'cantidad' => array($insumos[0] => '0'),
        'gestion_csrf' => ClienteCompras::token($revisada['body'])
    ));
    comprobar_compra($invalidoPosterior['status'] === 422
        && str_contains($adminA->pedir('compras/seleccionar')['body'], '1.25 kg'),
        'Un envío inválido conserva la última selección válida');

    $otraSesion = new ClienteCompras($config['base_url']);
    comprobar_compra($otraSesion->login($cuentas[0][3], $password)['status'] === 303
        && !str_contains($otraSesion->pedir('compras/seleccionar')['body'], 'Selección revisada'),
        'Otra sesión no recibe la selección guardada');

    $paginaConSeleccion = $adminA->pedir('compras/seleccionar');
    $vaciada = $adminA->pedir('compras/vaciar', array(
        'gestion_csrf' => ClienteCompras::token($paginaConSeleccion['body'])
    ));
    comprobar_compra($vaciada['status'] === 303
        && !str_contains($adminA->pedir('compras/seleccionar')['body'], 'Selección revisada'),
        'Vaciar elimina la selección de la sesión');

    $resumen = $adminA->pedir('inventario');
    comprobar_compra($resumen['status'] === 200
        && str_contains($resumen['body'], 'compras/agregar')
        && str_contains($resumen['body'], 'Cantidad a comprar de'),
        'El resumen de inventario permite elegir un insumo y su cantidad');
    $ajenoDesdeResumen = $adminA->pedir('compras/agregar', array(
        'insumo_id' => (string) $insumos[1],
        'cantidad' => '1',
        'gestion_csrf' => ClienteCompras::token($resumen['body'])
    ));
    comprobar_compra($ajenoDesdeResumen['status'] === 404,
        'No se puede agregar desde inventario un insumo de otra empresa');
    $resumen = $adminA->pedir('inventario');
    $agregado = $adminA->pedir('compras/agregar', array(
        'insumo_id' => (string) $insumos[0],
        'cantidad' => '1.25',
        'gestion_csrf' => ClienteCompras::token($resumen['body'])
    ));
    comprobar_compra($agregado['status'] === 303
        && str_contains($adminA->pedir('compras/seleccionar')['body'], '1.25 kg'),
        'Un insumo del resumen pasa a la selección de compra');
    $paginaConSeleccion = $adminA->pedir('compras/seleccionar');
    $adminA->pedir('compras/vaciar', array(
        'gestion_csrf' => ClienteCompras::token($paginaConSeleccion['body'])
    ));

    $adminB = new ClienteCompras($config['base_url']);
    comprobar_compra($adminB->login($cuentas[1][3], $password)['status'] === 303, 'Administrador B inicia sesión');
    $paginaB = $adminB->pedir('compras/seleccionar');
    comprobar_compra($paginaB['status'] === 200
        && str_contains($paginaB['body'], $datosInsumos[1][1])
        && !str_contains($paginaB['body'], $datosInsumos[0][1]),
        'La empresa B ve su propio inventario');

    $db->query("INSERT INTO proveedores (empresa_id,nombre) VALUES ($empresaA,'$prefijo proveedor 1')");
    $proveedores[] = (int) $db->insert_id;
    $db->query("INSERT INTO proveedores (empresa_id,nombre) VALUES ($empresaA,'$prefijo proveedor 2')");
    $proveedores[] = (int) $db->insert_id;
    comprobar_compra($adminB->pedir('proveedores/'.$proveedores[0].'/editar')['status'] === 404,
        'Otra empresa no puede editar el proveedor');
    $edicionProveedor = $adminA->pedir('proveedores/'.$proveedores[0].'/editar');
    comprobar_compra($edicionProveedor['status'] === 200, 'Abre el formulario de edición del proveedor');
    $nombreEditado = $prefijo.' proveedor editado';
    $guardadoProveedor = $adminA->pedir('proveedores/'.$proveedores[0].'/editar', array(
        'nombre' => $nombreEditado,
        'contacto' => 'Contacto de prueba',
        'telefono' => '12345678',
        'correo' => 'compras@prueba.test',
        'direccion' => 'Dirección de prueba',
        'gestion_csrf' => ClienteCompras::token($edicionProveedor['body'])
    ));
    comprobar_compra($guardadoProveedor['status'] === 303
        && $db->query('SELECT nombre FROM proveedores WHERE id='.$proveedores[0])->fetch_assoc()['nombre'] === $nombreEditado,
        'Guarda la edición del proveedor');
    $edicionProveedor = $adminA->pedir('proveedores/'.$proveedores[0].'/editar');
    $duplicadoProveedor = $adminA->pedir('proveedores/'.$proveedores[0].'/editar', array(
        'nombre' => $prefijo.' proveedor 2',
        'contacto' => '', 'telefono' => '', 'correo' => '', 'direccion' => '',
        'gestion_csrf' => ClienteCompras::token($edicionProveedor['body'])
    ));
    comprobar_compra($duplicadoProveedor['status'] === 422
        && str_contains($duplicadoProveedor['body'], 'Ya existe otro proveedor')
        && $db->query('SELECT nombre FROM proveedores WHERE id='.$proveedores[0])->fetch_assoc()['nombre'] === $nombreEditado,
        'Rechaza el nombre duplicado sin alterar el proveedor');
    comprobar_compra($adminB->pedir('proveedores/'.$proveedores[0])['status'] === 404,
        'Otra empresa no puede consultar el proveedor');
    $paginaB = $adminB->pedir('compras/seleccionar');
    comprobar_compra($adminB->pedir('proveedores/'.$proveedores[0].'/asociar', array(
        'insumo_id' => (string) $insumos[1],
        'gestion_csrf' => ClienteCompras::token($paginaB['body'])
    ))['status'] === 422
        && $db->query('SELECT proveedor_id FROM insumos WHERE id='.$insumos[1])->fetch_assoc()['proveedor_id'] === NULL,
        'Otra empresa no puede asociar insumos al proveedor');
    $proveedorA = $adminA->pedir('proveedores/'.$proveedores[0]);
    comprobar_compra($proveedorA['status'] === 200 && str_contains($proveedorA['body'], $datosInsumos[0][1]),
        'El proveedor muestra los insumos disponibles para asociar');
    $asociacion = $adminA->pedir('proveedores/'.$proveedores[0].'/asociar', array(
        'insumo_id' => (string) $insumos[0],
        'gestion_csrf' => ClienteCompras::token($proveedorA['body'])
    ));
    comprobar_compra($asociacion['status'] === 303
        && (int)$db->query('SELECT proveedor_id FROM insumos WHERE id='.$insumos[0])->fetch_assoc()['proveedor_id'] === $proveedores[0],
        'RF-49 asocia el insumo desde la interfaz de proveedores');
    $db->query("INSERT INTO insumos (empresa_id,nombre,cantidad_actual,stock_minimo,unidad_medida,proveedor_id)
        VALUES ($empresaA,'$prefijo segundo insumo',0,2,'unidad',".$proveedores[1].')');
    $segundoInsumo = (int) $db->insert_id;
    $insumos[] = $segundoInsumo;

    $paginaA = $adminA->pedir('compras/seleccionar');
    $seleccionDos = $adminA->pedir('compras/seleccionar', array(
        'cantidad' => array($insumos[0] => '2.50', $segundoInsumo => '3'),
        'gestion_csrf' => ClienteCompras::token($paginaA['body'])
    ));
    comprobar_compra($seleccionDos['status'] === 303, 'Selecciona insumos de dos proveedores');
    $revisada = $adminA->pedir('compras/seleccionar');
    comprobar_compra($revisada['status'] === 200
        && str_contains($revisada['body'], 'Se crearán 2 solicitudes'),
        'La revisión agrupa por proveedor');
    $generada = $adminA->pedir('compras/generar', array(
        'fecha' => date('Y-m-d'),
        'observaciones' => $prefijo,
        'gestion_csrf' => ClienteCompras::token($revisada['body'])
    ));
    $filas = $db->query("SELECT proveedor_id, numero FROM solicitudes_compra WHERE empresa_id=$empresaA AND observaciones='$prefijo'")->fetch_all(MYSQLI_ASSOC);
    comprobar_compra($generada['status'] === 303 && count($filas) === 2
        && (int) $filas[0]['proveedor_id'] !== (int) $filas[1]['proveedor_id'],
        'Genera una solicitud independiente por proveedor');
    comprobar_compra(str_contains($adminA->pedir('compras')['body'], '2 solicitudes creadas.'),
        'El listado muestra el resultado de la agrupación');

    $solicitudId = (int) $db->query("SELECT id FROM solicitudes_compra WHERE observaciones='$prefijo' AND proveedor_id=".$proveedores[0])->fetch_assoc()['id'];
    comprobar_compra($adminB->pedir('compras/'.$solicitudId)['status'] === 404
        && $adminB->pedir('compras/'.$solicitudId.'/recepcion')['status'] === 404,
        'Otra empresa no puede consultar ni recibir la solicitud');
    $detalleCompra = $adminA->pedir('compras/'.$solicitudId);
    comprobar_compra($detalleCompra['status'] === 200 && str_contains($detalleCompra['body'], 'Confirmar documento'),
        'Abre el borrador generado desde la selección');
    $confirmada = $adminA->pedir('compras/'.$solicitudId.'/confirmar', array(
        'version' => '1', 'revisada' => '1',
        'gestion_csrf' => ClienteCompras::token($detalleCompra['body'])
    ));
    comprobar_compra($confirmada['status'] === 303
        && $db->query('SELECT estado FROM solicitudes_compra WHERE id='.$solicitudId)->fetch_assoc()['estado'] === 'confirmada',
        'Confirma el borrador desde la interfaz');
    $detalleCompra = $adminA->pedir('compras/'.$solicitudId);
    $enviada = $adminA->pedir('compras/'.$solicitudId.'/estado', array(
        'version' => '2', 'estado' => 'enviada',
        'gestion_csrf' => ClienteCompras::token($detalleCompra['body'])
    ));
    comprobar_compra($enviada['status'] === 303
        && $db->query('SELECT estado FROM solicitudes_compra WHERE id='.$solicitudId)->fetch_assoc()['estado'] === 'enviada',
        'Marca la solicitud como enviada desde la interfaz');
    $detalleId = (int) $db->query('SELECT id FROM solicitud_compra_detalles WHERE solicitud_id='.$solicitudId)->fetch_assoc()['id'];
    $formRecepcion = $adminA->pedir('compras/'.$solicitudId.'/recepcion');
    comprobar_compra($formRecepcion['status'] === 200
        && preg_match('/name="clave"\s+value="([a-f0-9]{64})"/', $formRecepcion['body'], $claveEncontrada) === 1,
        'Abre el formulario de recepción con clave de operación');
    comprobar_compra($adminA->pedir('compras/'.$solicitudId.'/recepcion', array(
        'version' => '3', 'clave' => bin2hex(random_bytes(32)),
        'cantidades' => array($detalleId => '1.00')
    ))['status'] === 403, 'La recepción exige CSRF');
    $formRecepcion = $adminA->pedir('compras/'.$solicitudId.'/recepcion');
    $demasiado = $adminA->pedir('compras/'.$solicitudId.'/recepcion', array(
        'version' => '3', 'clave' => bin2hex(random_bytes(32)),
        'cantidades' => array($detalleId => '3.00'),
        'gestion_csrf' => ClienteCompras::token($formRecepcion['body'])
    ));
    comprobar_compra($demasiado['status'] === 303
        && $db->query('SELECT cantidad_actual FROM insumos WHERE id='.$insumos[0])->fetch_assoc()['cantidad_actual'] === '0.00'
        && (int)$db->query('SELECT COUNT(*) total FROM recepciones_compra WHERE solicitud_id='.$solicitudId)->fetch_assoc()['total'] === 0,
        'Rechaza recibir más de lo solicitado sin cambiar stock');
    $formRecepcion = $adminA->pedir('compras/'.$solicitudId.'/recepcion');
    $claveRecepcion = $claveEncontrada[1];
    $recepcionParcial = $adminA->pedir('compras/'.$solicitudId.'/recepcion', array(
        'version' => '3', 'clave' => $claveRecepcion,
        'cantidades' => array($detalleId => '1.00'),
        'gestion_csrf' => ClienteCompras::token($formRecepcion['body'])
    ));
    comprobar_compra($recepcionParcial['status'] === 303
        && $db->query('SELECT cantidad_actual FROM insumos WHERE id='.$insumos[0])->fetch_assoc()['cantidad_actual'] === '1.00'
        && $db->query('SELECT estado FROM solicitudes_compra WHERE id='.$solicitudId)->fetch_assoc()['estado'] === 'enviada',
        'La recepción parcial desde HTTP aumenta stock y mantiene la solicitud abierta');
    $detalleCompra = $adminA->pedir('compras/'.$solicitudId);
    $reenvio = $adminA->pedir('compras/'.$solicitudId.'/recepcion', array(
        'version' => '3', 'clave' => $claveRecepcion,
        'cantidades' => array($detalleId => '1.00'),
        'gestion_csrf' => ClienteCompras::token($detalleCompra['body'])
    ));
    comprobar_compra($reenvio['status'] === 303
        && (int)$db->query('SELECT COUNT(*) total FROM recepciones_compra WHERE solicitud_id='.$solicitudId)->fetch_assoc()['total'] === 1
        && $db->query('SELECT cantidad_actual FROM insumos WHERE id='.$insumos[0])->fetch_assoc()['cantidad_actual'] === '1.00',
        'Reenviar la recepción desde HTTP no duplica stock');
    $formRecepcion = $adminA->pedir('compras/'.$solicitudId.'/recepcion');
    comprobar_compra($formRecepcion['status'] === 200, 'La solicitud permite continuar una recepción parcial');
    $recepcionFinal = $adminA->pedir('compras/'.$solicitudId.'/recepcion', array(
        'version' => '4', 'clave' => bin2hex(random_bytes(32)),
        'cantidades' => array($detalleId => '1.50'),
        'gestion_csrf' => ClienteCompras::token($formRecepcion['body'])
    ));
    comprobar_compra($recepcionFinal['status'] === 303
        && $db->query('SELECT estado FROM solicitudes_compra WHERE id='.$solicitudId)->fetch_assoc()['estado'] === 'recibida'
        && $db->query('SELECT cantidad_actual FROM insumos WHERE id='.$insumos[0])->fetch_assoc()['cantidad_actual'] === '2.50',
        'La recepción final desde HTTP cierra la solicitud y actualiza stock');
    comprobar_compra($adminA->pedir('compras/'.$solicitudId.'/recepcion')['status'] === 409,
        'Una solicitud recibida ya no muestra el formulario de recepción');

    $paginaA = $adminA->pedir('compras/seleccionar');
    $adminA->pedir('compras/seleccionar', array(
        'cantidad' => array($insumos[0] => '1', $segundoInsumo => '1'),
        'gestion_csrf' => ClienteCompras::token($paginaA['body'])
    ));
    $revisada = $adminA->pedir('compras/seleccionar');
    $db->query('UPDATE proveedores SET activo=0 WHERE id='.$proveedores[1]);
    $parcial = $adminA->pedir('compras/generar', array(
        'fecha' => date('Y-m-d'),
        'observaciones' => $prefijo.'-parcial',
        'gestion_csrf' => ClienteCompras::token($revisada['body'])
    ));
    $pendiente = $adminA->pedir('compras/seleccionar');
    $cantidadParcial = (int) $db->query("SELECT COUNT(*) total FROM solicitudes_compra WHERE observaciones='$prefijo-parcial'")->fetch_assoc()['total'];
    comprobar_compra($parcial['status'] === 303 && $cantidadParcial === 1
        && str_contains($pendiente['body'], '1 solicitud creada.')
        && str_contains($pendiente['body'], 'Revisar borrador de')
        && str_contains($pendiente['body'], 'Sin proveedor activo asociado'),
        'Un fallo posterior muestra el borrador creado y conserva el insumo pendiente');
    $db->query('UPDATE proveedores SET activo=1 WHERE id='.$proveedores[1]);
    $pendiente = $adminA->pedir('compras/seleccionar');
    $reintento = $adminA->pedir('compras/generar', array(
        'fecha' => date('Y-m-d'),
        'observaciones' => $prefijo.'-parcial',
        'gestion_csrf' => ClienteCompras::token($pendiente['body'])
    ));
    $filasParciales = $db->query("SELECT proveedor_id FROM solicitudes_compra WHERE observaciones='$prefijo-parcial'")->fetch_all(MYSQLI_ASSOC);
    comprobar_compra($reintento['status'] === 303 && count($filasParciales) === 2
        && (int) $filasParciales[0]['proveedor_id'] !== (int) $filasParciales[1]['proveedor_id'],
        'El reintento crea solo el grupo pendiente, sin duplicar el primero');
    echo "Pruebas HTTP de selección de compras completadas.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "FALLO: ".$error->getMessage()."\n");
    $codigoSalida = 1;
} finally {
    if ($db instanceof mysqli) {
        $idsCompra = $db->query("SELECT id FROM solicitudes_compra WHERE observaciones LIKE '$prefijo%'")->fetch_all(MYSQLI_ASSOC);
        if ($idsCompra) {
            $ids = implode(',', array_map(static fn($fila) => (int) $fila['id'], $idsCompra));
            $movimientosRecepcion = $db->query('SELECT movimiento_id FROM recepcion_compra_detalles WHERE solicitud_id IN ('.$ids.')')->fetch_all(MYSQLI_ASSOC);
            $db->query('DELETE FROM recepcion_compra_detalles WHERE solicitud_id IN ('.$ids.')');
            $db->query('DELETE FROM recepciones_compra WHERE solicitud_id IN ('.$ids.')');
            if ($movimientosRecepcion) {
                $movimientoIds = implode(',', array_map(static fn($fila) => (int) $fila['movimiento_id'], $movimientosRecepcion));
                $db->query('DELETE FROM movimientos_inventario WHERE id IN ('.$movimientoIds.')');
            }
            $db->query('DELETE FROM solicitud_compra_eventos WHERE solicitud_id IN ('.$ids.')');
            $db->query('DELETE FROM solicitud_compra_detalles WHERE solicitud_id IN ('.$ids.')');
            $db->query('DELETE FROM solicitudes_compra WHERE id IN ('.$ids.')');
        }
        if ($insumos) { $db->query('DELETE FROM insumos WHERE id IN ('.implode(',', $insumos).')'); }
        if ($proveedores) { $db->query('DELETE FROM proveedores WHERE id IN ('.implode(',', $proveedores).')'); }
        if ($usuarios) { $db->query('DELETE FROM usuarios WHERE id IN ('.implode(',', $usuarios).')'); }
        $restantes = (int)$db->query("SELECT COUNT(*) total FROM solicitudes_compra WHERE observaciones LIKE '$prefijo%'")->fetch_assoc()['total'];
        $restantes += (int)$db->query("SELECT COUNT(*) total FROM proveedores WHERE nombre LIKE '$prefijo%'")->fetch_assoc()['total'];
        $restantes += (int)$db->query("SELECT COUNT(*) total FROM insumos WHERE nombre LIKE '$prefijo%'")->fetch_assoc()['total'];
        if ($restantes === 0) { echo "Datos temporales eliminados.\n"; }
        else { fwrite(STDERR, "FALLO: quedaron datos temporales.\n"); $codigoSalida = 1; }
    }
}
exit($codigoSalida);
