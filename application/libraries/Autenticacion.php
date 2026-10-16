<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'libraries/UsuarioAutenticado.php';

class Autenticacion
{
    private $ci;
    private $resuelto = FALSE;
    private $usuario = NULL;
    // Hash válido usado solo para igualar el trabajo cuando el correo no existe.
    private const HASH_FICTICIO = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->model('Usuario_model', 'usuarios');
        $this->ci->load->model('Intento_model', 'intentos');
    }

    public function ingresar($correo, $password)
    {
        if (!$this->ci->intentos->reservar($correo, $this->ci->input->ip_address())) {
            return 'limite';
        }
        $fila = $this->ci->usuarios->por_correo($correo);
        $valido = password_verify($password, $fila ? $fila['password_hash'] : self::HASH_FICTICIO);
        if (!$fila || !$valido) { return 'invalido'; }
        $this->ci->session->sess_regenerate(TRUE);
        $this->ci->session->set_userdata(array(
            'usuario_id' => (int) $fila['id'], 'ingreso_en' => time(), 'ultima_actividad' => time()
        ));
        $this->ci->intentos->limpiar_correo($correo);
        $this->usuario = new UsuarioAutenticado($fila);
        $this->resuelto = TRUE;
        return 'ok';
    }

    public function usuario_actual()
    {
        if ($this->resuelto) { return $this->usuario; }
        $this->resuelto = TRUE;
        $id = $this->ci->session->userdata('usuario_id');
        if (!$id) { return NULL; }
        $ultima = (int) $this->ci->session->userdata('ultima_actividad');
        $inicio = (int) $this->ci->session->userdata('ingreso_en');
        if (time() - $ultima >= 1800 || time() - $inicio >= 28800) { $this->salir(); return NULL; }
        // Revalidar usuario y empresa activos en cada petición; no confiar en un tenant almacenado por el cliente.
        $fila = $this->ci->usuarios->activo_por_id($id);
        if (!$fila) { $this->salir(); return NULL; }
        $this->usuario = new UsuarioAutenticado($fila);
        $this->ci->session->set_userdata('ultima_actividad', time());
        return $this->usuario;
    }

    public function salir()
    {
        $this->usuario = NULL;
        $this->resuelto = TRUE;
        $this->ci->session->sess_destroy();
    }
}
