<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Inventario extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Insumo_model', 'insumos_model');
        $this->load->library('form_validation');
    }


    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('inventario.ver');

        $busqueda = trim((string) $this->input->get('busqueda', TRUE));
        $estado = (string) $this->input->get('estado', TRUE);
        $unidad = (string) $this->input->get('unidad', TRUE);

        $orden = (string) $this->input->get('orden', TRUE);

        $ordenesPermitidos = array(
            'nombre_asc',
            'nombre_desc',
            'cantidad_asc',
            'cantidad_desc',
            'minimo_asc',
            'minimo_desc',
            'stock_bajo'
        );

        if (!in_array($orden, $ordenesPermitidos, TRUE)) {
            $orden = 'nombre_asc';
        }

        $busqueda = mb_substr($busqueda, 0, 150, 'UTF-8');

        if (!in_array($estado, array('', 'bajo', 'normal'), TRUE)) {
            $estado = '';
        }

        $unidades = array('kg', 'g', 'mg', 'l', 'ml', 'unidad');

        if (!in_array($unidad, $unidades, TRUE)) {
            $unidad = '';
        }

        $filtros = array(
            'busqueda' => $busqueda,
            'estado' => $estado,
            'unidad' => $unidad,
            'orden' => $orden
        );

        $this->load->view('inventario/index', array(
            'usuario' => $this->usuario,
            'insumos' => $this->insumos_model->listar($filtros),
            'filtros' => $filtros,
            'resumen' => $this->insumos_model->resumen()
        ));
    }


    public function crear()
    {
        $this->requerir_permiso('inventario.gestionar');

        if ($this->input->method(TRUE) ===  'GET') {
            $this->load->view('inventario/formulario', array(
                'usuario' => $this->usuario,
                'insumo' => NULL,
                'accion' => 'inventario/crear'
            ));

            return;
        }

        $this->solo_metodo('POST');
        $this->configurar_validacion();

        if ($this->form_validation->run() === FALSE) {
            $this->load->view('inventario/formulario', array(
                'usuario' => $this->usuario,
                'insumo' => NULL,
                'accion' => 'inventario/crear'
            ));

            return;
        }

        $datos = $this->datos_formulario();

        if (!$this->insumos_model->crear($datos)) {
            show_error('No se pudo registrar el insumo.', 503);
        }

        redirect('inventario');
    }

    public function editar($id = NULL)
    {
        $this->requerir_permiso('inventario.gestionar');

        if (!is_string($id) || !ctype_digit($id) || strlen($id) > 10) {
            show_404();
        }

        $insumo = $this->insumos_model->buscar($id);

        if ($insumo === NULL) {
            show_404();
        }

        if ($this->input->method(TRUE) === 'GET') {
            $this->load->view('inventario/formulario', array(
                'usuario' => $this->usuario,
                'insumo' => $insumo,
                'accion' => 'inventario/' . $id . '/editar'
            ));

            return;
        }

        $this->solo_metodo('POST');
        $this->configurar_validacion();

        if ($this->form_validation->run() === FALSE) {
            $this->load->view('inventario/formulario', array(
                'usuario' => $this->usuario,
                'insumo' => $insumo,
                'accion' => 'inventario/' . $id . '/editar'
            ));

            return;
        }

        $datos = $this->datos_formulario();

        if (!$this->insumos_model->actualizar($id, $datos)) {
            show_error('No se pudo actualizar el insumo.', 503);
        }

        redirect('inventario');
    }

    private function configurar_validacion()
    {
        $this->form_validation->set_rules('nombre', 'Nombre', 'required|max_length[150]');
        $this->form_validation->set_rules('cantidad_actual', 'Cantidad actual', 'required|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('stock_minimo', 'Stock mínimo', 'required|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('unidad_medida', 'Unidad de medida', 'required|in_list[kg,g,mg,l,ml,unidad]');
    }

    private function datos_formulario()
    {
        return array(
            'nombre' => trim($this->input->post('nombre', TRUE)),
            'cantidad_actual' => $this->input->post('cantidad_actual', TRUE),
            'stock_minimo' => $this->input->post('stock_minimo', TRUE),
            'unidad_medida' => $this->input->post('unidad_medida', TRUE)
        );
    }
}
