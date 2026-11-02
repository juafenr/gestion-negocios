<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Proveedores extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model(
            'Proveedor_model',
            'proveedores_model'
        );

        $this->load->library('form_validation');
        $this->load->helper(array('url', 'form'));
    }

    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('compras.ver');

        $busqueda = trim(
            (string) $this->input->get('busqueda', TRUE)
        );

        $busqueda = mb_substr($busqueda, 0, 150, 'UTF-8');

        $this->load->view('proveedores/index', array(
            'usuario' => $this->usuario,
            'proveedores' => $this->proveedores_model->listar($busqueda),
            'busqueda' => $busqueda,
            'mensaje' => $this->session->flashdata('mensaje_proveedor')
        ));
    }

    public function editar($id = NULL)
    {
        $this->requerir_permiso('proveedores.gestionar');

        if (
            !is_string($id) ||
            !ctype_digit($id) ||
            strlen($id) > 10 ||
            (int) $id < 1
        ) {
            show_404();
            return;
        }

        $proveedor = $this->proveedores_model->buscar($id);

        if (!$proveedor) {
            show_404();
            return;
        }

        $error = '';

        if ($this->input->method(TRUE) !== 'GET') {
            $this->solo_metodo('POST');

            $this->form_validation->set_rules(
                'nombre',
                'Nombre',
                'trim|required|max_length[150]'
            );

            $this->form_validation->set_rules(
                'contacto',
                'Contacto',
                'trim|max_length[100]'
            );

            $this->form_validation->set_rules(
                'telefono',
                'Teléfono',
                'trim|max_length[30]'
            );

            $this->form_validation->set_rules(
                'correo',
                'Correo',
                'trim|valid_email|max_length[190]'
            );

            $this->form_validation->set_rules(
                'direccion',
                'Dirección',
                'trim|max_length[255]'
            );

            if ($this->form_validation->run()) {

                $datos = array();

                foreach (
                    array('nombre', 'contacto', 'telefono', 'correo', 'direccion')
                    as $campo
                ) {
                    $datos[$campo] = trim(
                        (string) $this->input->post($campo, TRUE)
                    );
                }

                if ($this->proveedores_model->existe_nombre(
                    $datos['nombre'],
                    $id
                )) {
                    $error = 'Ya existe otro proveedor con ese nombre.';
                } else {

                    if ($this->proveedores_model->actualizar($id, $datos)) {

                        $this->session->set_flashdata(
                            'mensaje_proveedor',
                            'Proveedor actualizado correctamente.'
                        );

                        redirect('proveedores', 'location', 303);
                        return;
                    }

                    $error = 'No se pudo actualizar el proveedor.';
                }

                if ($this->proveedores_model->actualizar($id, $datos)) {
                    $this->session->set_flashdata(
                        'mensaje_proveedor',
                        'Proveedor actualizado correctamente.'
                    );

                    redirect('proveedores', 'location', 303);
                    return;
                }

                $error = 'No se pudo actualizar el proveedor. '
                    . 'Comprueba que el nombre no esté duplicado.';
            }
        }

        $this->load->view('proveedores/editar', array(
            'usuario' => $this->usuario,
            'proveedor' => $proveedor,
            'error' => $error
        ));
    }

    public function ver($id = NULL)
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('compras.ver');

        if (
            !is_string($id) ||
            !ctype_digit($id) ||
            strlen($id) > 10 ||
            (int) $id < 1
        ) {
            show_404();
            return;
        }

        $proveedor = $this->proveedores_model->buscar($id);

        if (!$proveedor) {
            show_404();
            return;
        }

        $this->load->view('proveedores/detalle', array(
            'usuario' => $this->usuario,
            'proveedor' => $proveedor,
            'insumos' => $this->proveedores_model
                ->insumos_asociados($id),
            'disponibles' => $this->proveedores_model
                ->insumos_disponibles(),
            'mensaje' => $this->session
                ->flashdata('mensaje_proveedor')
        ));
    }

    public function asociar($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('proveedores.gestionar');

        if (
            !is_string($id) ||
            !ctype_digit($id) ||
            strlen($id) > 10 ||
            (int) $id < 1
        ) {
            show_404();
            return;
        }

        $insumoId = $this->input->post('insumo_id', TRUE);

        if (
            !is_string($insumoId) ||
            !ctype_digit($insumoId) ||
            strlen($insumoId) > 10 ||
            (int) $insumoId < 1
        ) {
            show_error('Insumo inválido.', 422);
            return;
        }

        if (!$this->proveedores_model->asociar_insumo(
            $id,
            $insumoId
        )) {
            show_error(
                'No se pudo asociar el insumo. '
                    . 'Verifica que esté activo y sin proveedor.',
                422
            );
            return;
        }

        $this->session->set_flashdata(
            'mensaje_proveedor',
            'Insumo asociado correctamente.'
        );

        redirect('proveedores/' . $id, 'location', 303);
    }

    public function desasociar($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('proveedores.gestionar');

        if (
            !is_string($id) ||
            !ctype_digit($id) ||
            strlen($id) > 10 ||
            (int) $id < 1
        ) {
            show_404();
            return;
        }

        $insumoId = $this->input->post('insumo_id', TRUE);

        if (
            !is_string($insumoId) ||
            !ctype_digit($insumoId) ||
            strlen($insumoId) > 10 ||
            (int) $insumoId < 1
        ) {
            show_error('Insumo inválido.', 422);
            return;
        }

        if (!$this->proveedores_model->desasociar_insumo(
            $id,
            $insumoId
        )) {
            show_error(
                'No se pudo retirar la asociación.',
                422
            );
            return;
        }

        $this->session->set_flashdata(
            'mensaje_proveedor',
            'Asociación retirada correctamente.'
        );

        redirect('proveedores/' . $id, 'location', 303);
    }
}
