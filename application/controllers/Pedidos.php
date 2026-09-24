<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pedidos extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Pedido_model', 'pedidos_model');
        $this->load->helper(array('url', 'form'));
    }

    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('pedidos.ver');

        $this->mostrar();
    }

    public function crear()
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('pedidos.ver');
        $this->requerir_permiso('pedidos.crear');

        $referencia = $this->input->post('referencia');

        if (!is_string($referencia)) {
            $this->mostrar('Escribe una referencia válida.');
            return;
        }

        $referencia = trim($referencia);

        if ($referencia === '' || mb_strlen($referencia, 'UTF-8') > 150) {
            $this->mostrar(
                'La referencia debe tener entre 1 y 150 caracteres.',
                $referencia
            );
            return;
        }

        $usuario_id = (int) $this->session->userdata('usuario_id');

        $id = $this->pedidos_model->crear($referencia, $usuario_id);

        $this->session->set_flashdata(
            'mensaje_pedido',
            'Pedido #' . $id . ' creado en borrador.'
        );

        redirect('pedidos');
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