<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Heredar de Protected_Controller exige iniciar sesión antes de entrar aquí.
class Inicio extends Protected_Controller
{
    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('inicio.ver');
        $this->load->model('Inicio_model', 'inicio_model');
        $this->load->helper('negocio');
        $resumen = array();
        $aviso = '';
        try {
            $resumen = $this->inicio_model->resumen();
        } catch (RuntimeException $e) {
            log_message('error', 'No se pudo cargar el resumen de inicio.');
            $aviso = 'El resumen no está disponible por el momento. Puedes seguir usando los accesos rápidos.';
        }
        $this->load->view('inicio/index', array(
            'usuario' => $this->usuario, 'resumen' => $resumen, 'aviso_resumen' => $aviso
        ));
    }
}
