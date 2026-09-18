<?php
/** Prueba HTTP real. Requiere Apache y la instalación de demostración. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('curl')) { fwrite(STDERR, "Activa curl en el PHP de XAMPP para esta prueba.\n"); exit(1); }

final class NavegadorPrueba
{
    private $curl;
    private $base;
    public function __construct($base)
    {
        $this->base = rtrim($base, '/').'/index.php/';
        $this->curl = curl_init();
        curl_setopt_array($this->curl, array(
            CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_FOLLOWLOCATION => FALSE,
            CURLOPT_COOKIEFILE => '', CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15
        ));
    }
    public function pedir($ruta, $datos = NULL)
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->base.$ruta);
        if ($datos === NULL) { curl_setopt($this->curl, CURLOPT_HTTPGET, TRUE); }
        else { curl_setopt($this->curl, CURLOPT_POST, TRUE); curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($datos)); }
        $body = curl_exec($this->curl);
        if ($body === FALSE) { throw new RuntimeException(curl_error($this->curl)); }
        if (strpos($body, 'A PHP Error was encountered') !== FALSE || strpos($body, 'Fatal error:') !== FALSE) {
            throw new RuntimeException('La respuesta contiene un error de PHP. Revisa el navegador.');
        }
        return array('status' => curl_getinfo($this->curl, CURLINFO_HTTP_CODE), 'body' => $body,
            'redirect' => curl_getinfo($this->curl, CURLINFO_REDIRECT_URL));
    }
    public function sesion()
    {
        foreach (curl_getinfo($this->curl, CURLINFO_COOKIELIST) as $cookie) {
            $partes = explode("\t", $cookie);
            if (($partes[5] ?? '') === 'gestion_session') { return $partes[6]; }
        }
        return '';
    }
    public function login($correo, $password, $extra = array())
    {
        $form = $this->pedir('login');
        return $this->pedir('login', array_merge(array(
            'correo' => $correo, 'password' => $password, 'gestion_csrf' => self::token($form['body'])
        ), $extra));
    }
    public static function token($html)
    {
        if (!preg_match('/name="gestion_csrf" value="([^"]+)"/', $html, $m)) {
            throw new RuntimeException('No se encontró el token CSRF. Revisa la instalación y la URL.');
        }
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
}
function comprobar($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK - $mensaje\n";
}
function redirige($respuesta, $destino)
{
    return in_array($respuesta['status'], array(302,303,307), TRUE)
        && str_ends_with(parse_url($respuesta['redirect'], PHP_URL_PATH) ?: '', '/'.$destino);
}

try {
    echo "URL base [http://localhost/gestion-negocios/]: ";
    $base = trim(fgets(STDIN)) ?: 'http://localhost/gestion-negocios/';
    echo "Contraseña generada para ana@aurora.test (se verá al escribir): "; $aPass = trim(fgets(STDIN));
    echo "Contraseña generada para bruno@roble.test (se verá al escribir): "; $bPass = trim(fgets(STDIN));
    $a = new NavegadorPrueba($base); $b = new NavegadorPrueba($base); $anon = new NavegadorPrueba($base);
    comprobar(redirige($anon->pedir('inicio'), 'login'), 'Inicio privado exige sesión');
    comprobar(redirige($anon->pedir('productos'), 'login'), 'Listado privado exige sesión');
    comprobar(redirige($anon->pedir('productos/1'), 'login'), 'Detalle privado exige sesión');
    $sinCsrf = $anon->pedir('login', array('correo'=>'ana@aurora.test','password'=>$aPass));
    comprobar($sinCsrf['status'] === 403, 'Login sin CSRF rechazado');
    $respuestaIncorrecta = $anon->login(
        'ana@aurora.test',
        'incorrecta'
    );

    echo 'HTTP de contraseña incorrecta: '
        . $respuestaIncorrecta['status']
        . PHP_EOL;

    comprobar(
        $respuestaIncorrecta['status'] === 401,
        'Contraseña incorrecta rechazada'
    );
    comprobar(redirige($anon->pedir('productos'), 'login'), 'Fallo de login no abre una sesión autenticada');
    $a->pedir('login'); $antes = $a->sesion();
    echo 'La contraseña coincide: '
        . ($aPass === 'Aurora-Prueba-2026!' ? 'SI' : 'NO')
        . PHP_EOL;
    $respuestaA = $a->login(
        'ana@aurora.test',
        $aPass,
        array('empresa_id' => 2)
    );

    echo "HTTP del login A: ".$respuestaA['status'].PHP_EOL;
    echo "Destino del login A: ".$respuestaA['redirect'].PHP_EOL;

    comprobar(
        redirige($respuestaA, 'inicio'),
        'Login A válido; empresa enviada por cliente ignorada'
    );
    comprobar($antes !== '' && $a->sesion() !== $antes, 'Identificador de sesión renovado al ingresar');
    $lista = $a->pedir('productos?empresa_id=2');
    comprobar($lista['status'] === 200 && str_contains($lista['body'], 'Cuaderno Aurora')
        && !str_contains($lista['body'], 'Martillo Roble'), 'A solo ve productos de A aunque manipule empresa_id');
    comprobar($a->pedir('productos/1')['status'] === 200, 'A puede consultar su producto');
    comprobar($a->pedir('productos/3')['status'] === 404, 'A no puede consultar un producto de B por ID');
    comprobar($a->pedir('productos/ver/3')['status'] === 404, 'Ruta directa del controlador también aísla empresas');
    comprobar($a->pedir('productos/99999')['status'] === 404, 'ID inexistente responde igual que ID ajeno');
    comprobar(redirige($b->login('bruno@roble.test', $bPass), 'inicio'), 'Login B válido');
    $listaB = $b->pedir('productos');
    comprobar($listaB['status'] === 200 && str_contains($listaB['body'], 'Martillo Roble')
        && !str_contains($listaB['body'], 'Cuaderno Aurora'), 'B solo ve productos de B');
    comprobar($b->pedir('productos/1')['status'] === 404, 'B no puede consultar un producto de A');
    comprobar($b->pedir('productos/3')['status'] === 200, 'B puede consultar su producto');
    comprobar($a->pedir('salir')['status'] === 405, 'Logout por GET rechazado');
    comprobar($a->pedir('salir', array())['status'] === 403, 'Logout sin CSRF rechazado');
    $pagina = $a->pedir('productos');
    comprobar(redirige($a->pedir('salir', array('gestion_csrf'=>NavegadorPrueba::token($pagina['body']))), 'login'), 'Logout válido');
    comprobar(redirige($a->pedir('productos'), 'login'), 'Sesión cerrada no permite volver al listado');
    comprobar($b->pedir('productos')['status'] === 200, 'Cerrar A no cierra la sesión independiente de B');
    $correoLimite = 'prueba-'.bin2hex(random_bytes(5)).'@example.test';
    for ($i=0; $i<5; $i++) {
        comprobar($anon->login($correoLimite, 'incorrecta')['status'] === 401, 'Intento fallido '.($i+1).' registrado');
    }
    comprobar($anon->login($correoLimite, 'incorrecta')['status'] === 429, 'Sexto intento bloqueado');
    $nuevo = new NavegadorPrueba($base);
    comprobar($nuevo->login($correoLimite, 'incorrecta')['status'] === 429, 'Borrar cookies no elimina el límite por correo');
    echo "\nTodas las pruebas HTTP finalizaron correctamente.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "\nFALLO: ".$e->getMessage()."\nSi ejecutaste varias veces, espera 15 minutos por el límite por IP.\n"); exit(1);
}
