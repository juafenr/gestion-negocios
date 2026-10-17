<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cocina extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Pedido_model', 'pedidos_model');
    }

    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('pedidos.actualizar_cocina');

        $this->load->view('cocina/index', array(
            'usuario' => $this->usuario,
            'pedidos' => $this->pedidos_model->listar_cocina()
        ));
    }

    public function actualizar_estado($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('pedidos.actualizar_cocina');

        if (
            !is_string($id)
            || !ctype_digit($id)
            || strlen($id) > 10
            || (int) $id < 1
        ) {
            show_404();
            return;
        }

        if (!$this->pedidos_model->buscar($id)) {
            show_404();
            return;
        }

        $estado = $this->input->post('estado', TRUE);

        if (!is_string($estado)) {
            show_error('Selecciona un estado válido.', 400);
            return;
        }

        $error = $this->pedidos_model->actualizar_estado_cocina(
            (int) $id,
            $estado,
            $this->usuario->id(),
            $this->usuario->rol()
        );

        if ($error !== NULL) {
            show_error(html_escape($error), 400);
            return;
        }

        $this->session->set_flashdata(
            'mensaje_cocina',
            'Estado del pedido actualizado correctamente.'
        );

        redirect('cocina');
    }
}
