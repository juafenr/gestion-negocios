<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Base común: sesión, cabeceras y servicio de autenticación. */
class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->set_header('Cache-Control: no-store, private');
        $this->output->set_header('X-Content-Type-Options: nosniff');
        $this->output->set_header('X-Frame-Options: DENY');
        $this->output->set_header('Referrer-Policy: same-origin');
        $this->output->set_header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
        $this->load->library('session');
        $this->load->library('Autenticacion');
    }

    protected function solo_metodo($metodo)
    {
        if ($this->input->method(TRUE) !== $metodo) {
            $this->output->set_header('Allow: '.$metodo);
            show_error('Método no permitido.', 405);
        }
    }
}

/** Todas las pantallas privadas deben heredar de esta clase. */
class Protected_Controller extends MY_Controller
{
    protected $usuario;

    public function __construct()
    {
        parent::__construct();
        $this->usuario = $this->autenticacion->usuario_actual();
        if ($this->usuario === NULL) {
            redirect('login');
        }
    }

    /** Detiene la acción si el rol actual no posee la capacidad solicitada. */
    protected function requerir_permiso($permiso)
    {
        if (!$this->usuario->puede($permiso)) {
            show_error('No tienes permiso para realizar esta acción.', 403, 'Acceso denegado');
        }
    }
}
