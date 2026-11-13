<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Configuracion extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requerir_permiso('usuarios.gestionar');
        $this->load->model('Empresa_model', 'empresa_model');
        $this->load->helper(array('url', 'form'));
    }

    public function propina()
    {
        $error = '';
        if ($this->input->method(TRUE) === 'POST') {
            $error = $this->empresa_model->cambiar_propina($this->input->post('porcentaje')) ?? '';
            if ($error === '') {
                $this->session->set_flashdata('mensaje_configuracion', 'Propina actualizada. Solo los pedidos nuevos usarán este porcentaje.');
                redirect('configuracion/propina', 'location', 303);
                return;
            }
            $this->output->set_status_header(422);
        } else {
            $this->solo_metodo('GET');
        }
        $this->load->view('configuracion/propina', array(
            'usuario' => $this->usuario,
            'porcentaje' => $this->empresa_model->propina_porcentaje(),
            'mensaje' => $this->session->flashdata('mensaje_configuracion'),
            'error' => $error
        ));
    }
}
