<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pedidos extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model(
            'Pedido_model',
            'pedidos_model'
        );

        $this->load->helper(
            array('url', 'form')
        );

        $this->load->library(
            'form_validation'
        );
    }

    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('pedidos.ver');

        $this->mostrar();
    }

    public function crear()
    {
        $this->requerir_permiso('pedidos.crear');

        if ($this->input->method(TRUE) === 'GET') {

            $this->load->view('pedidos/formulario', array(
                'usuario' => $this->usuario
            ));

            return;
        }

        $this->solo_metodo('POST');

        $this->form_validation->set_rules(
            'referencia',
            'Referencia',
            'required|trim|max_length[100]'
        );

        if ($this->form_validation->run() === FALSE) {

            $this->load->view('pedidos/formulario', array(
                'usuario' => $this->usuario
            ));

            return;
        }

        $referencia = trim(
            $this->input->post('referencia', TRUE)
        );

        $usuarioId = $this->usuario->id();

        $pedidoId = $this->pedidos_model->crear(
            $referencia,
            $usuarioId
        );

        if (!$pedidoId) {
            show_error(
                'No se pudo registrar el pedido.',
                503
            );
        }

        redirect('pedidos/' . $pedidoId);
    }

    private function mostrar($error = '', $referencia = '')
    {
        $this->load->view('pedidos/index', array(
            'usuario' => $this->usuario,
            'pedidos' => $this->pedidos_model->listar(),
            'error' => $error,
            'referencia' => $referencia,
            'mensaje' => $this->session->flashdata('mensaje_pedido')
        ));
    }

    public function ver($id = NULL)
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('pedidos.ver');

        if (!is_string($id) || !ctype_digit($id) || strlen($id) > 10) {
            show_404();
            return;
        }

        $pedido = $this->pedidos_model->buscar($id);

        if (!$pedido) {
            show_404();
            return;
        }

        $this->load->model('Producto_model', 'productos_model');

        $this->load->view('pedidos/detalle', array(
            'usuario' => $this->usuario,
            'pedido' => $pedido,
            'detalles' => $this->pedidos_model->detalles($id),
            'productos' => $this->productos_model->listar()
        ));
    }
}
