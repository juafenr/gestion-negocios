<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Inventario extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Insumo_model', 'insumos_model');
        $this->load->model('Movimientos_inventario_model', 'movimientos_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('inventario.ver');

        $busqueda = trim(
            (string) $this->input->get('busqueda', TRUE)
        );
        $busqueda = mb_substr($busqueda, 0, 150, 'UTF-8');

        $estado = (string) $this->input->get('estado', TRUE);
        $unidad = (string) $this->input->get('unidad', TRUE);
        $orden = (string) $this->input->get('orden', TRUE);

        $activo = $this->input->get('activo', TRUE)
            === 'inactivos' ? 'inactivos' : 'activos';

        if (!in_array($estado, array('', 'bajo', 'normal'), TRUE)) {
            $estado = '';
        }

        if (!in_array(
            $unidad,
            array('', 'kg', 'g', 'mg', 'l', 'ml', 'unidad'),
            TRUE
        )) {
            $unidad = '';
        }

        $ordenes = array(
            'nombre_asc',
            'nombre_desc',
            'cantidad_asc',
            'cantidad_desc',
            'minimo_asc',
            'minimo_desc',
            'stock_bajo'
        );

        if (!in_array($orden, $ordenes, TRUE)) {
            $orden = 'nombre_asc';
        }

        $filtros = array(
            'busqueda' => $busqueda,
            'estado' => $estado,
            'unidad' => $unidad,
            'orden' => $orden,
            'activo' => $activo
        );

        $porPagina = 20;

        $total = $this->insumos_model->contar($filtros);

        $paginas = max(
            1,
            (int) ceil($total / $porPagina)
        );

        $pagina = max(
            1,
            (int) $this->input->get('pagina', TRUE)
        );

        $pagina = min($pagina, $paginas);

        $filtros['limite'] = $porPagina;
        $filtros['offset'] = ($pagina - 1) * $porPagina;

        $this->load->view('inventario/index', array(
            'usuario' => $this->usuario,
            'insumos' => $this->insumos_model->listar($filtros),
            'filtros' => $filtros,
            'resumen' => $this->insumos_model->resumen(),
            'pagina' => $pagina,
            'paginas' => $paginas
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

        if ($this->insumos_model->existe_nombre($datos['nombre'])) {
            show_error(
                'Ya existe un insumo con ese nombre en tu empresa.',
                409
            );
        }

        if (!$this->insumos_model->crear($datos, $this->usuario->id())) {
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

        if ($this->insumos_model->existe_nombre($datos['nombre'], $id)) {
            show_error('Ya existe un insumo con ese nombre en tu empresa.', 409);
        }

        if (!$this->insumos_model->actualizar($id, $datos)) {
            show_error('No se pudo actualizar el insumo.', 503);
        }

        redirect('inventario');
    }

    private function configurar_validacion()
    {
        $this->form_validation->set_rules('nombre', 'Nombre', 'required|max_length[150]');
        $this->form_validation->set_rules('stock_minimo', 'Stock mínimo', 'required|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('unidad_medida', 'Unidad de medida', 'required|in_list[kg,g,mg,l,ml,unidad]');
        $this->form_validation->set_rules('fecha_vencimiento', 'Fecha de vencimiento', 'callback_fecha_valida');

        if ($this->input->post('cantidad_actual') !== NULL) {
            $this->form_validation->set_rules(
                'cantidad_actual',
                'Cantidad actual',
                'required|numeric|greater_than_equal_to[0]|less_than_equal_to[99999999.99]'
            );
        }
    }

    private function datos_formulario()
    {
        return array(
            'nombre' => trim($this->input->post('nombre', TRUE)),
            'cantidad_actual' => $this->input->post('cantidad_actual', TRUE),
            'stock_minimo' => $this->input->post('stock_minimo', TRUE),
            'unidad_medida' => $this->input->post('unidad_medida', TRUE),
            'fecha_vencimiento' =>   $this->input->post('fecha_vencimiento', TRUE) ?: NULL
        );
    }

    public function fecha_valida($valor)
    {
        if ($valor === '' || $valor === NULL) {
            return TRUE;
        }

        $fecha = DateTime::createFromFormat('!Y-m-d', $valor);

        if ($fecha && $fecha->format('Y-m-d') === $valor) {
            return TRUE;
        }

        $this->form_validation->set_message('fecha_valida', 'La fecha de vencimiento no es válida.');

        return FALSE;
    }

    public function movimiento($id = NULL)
    {
        $this->requerir_permiso('inventario.gestionar');

        if (!is_string($id) || !ctype_digit($id) || strlen($id) > 10) {
            show_404();
            return;
        }

        $insumo = $this->insumos_model->buscar($id);

        if (!$insumo) {
            show_404();
            return;
        }

        if ($this->input->method(TRUE) === 'GET') {
            $this->load->view('inventario/movimiento', array(
                'usuario' => $this->usuario,
                'insumo' => $insumo
            ));
            return;
        }

        $this->solo_metodo('POST');

        $tipo = $this->input->post('tipo', TRUE);
        $cantidad = $this->input->post('cantidad', TRUE);
        $motivo = trim((string) $this->input->post('motivo', TRUE));

        if (
            !in_array(
                $tipo,
                array('entrada', 'salida', 'ajuste'),
                TRUE
            ) ||
            !is_string($cantidad) ||
            !preg_match(
                '/^\d{1,8}(\.\d{1,2})?$/',
                $cantidad
            ) ||
            ($tipo !== 'ajuste' && (float) $cantidad <= 0) ||
            $motivo === '' ||
            mb_strlen($motivo, 'UTF-8') > 255
        ) {
            show_error('Datos del movimiento inválidos.', 422);
            return;
        }

        $registrado = $this->movimientos_model->registrar((int) $id, $tipo, (float) $cantidad, $motivo, $this->usuario->id());

        if (!$registrado) {
            show_error('No se pudo registrar el movimiento. ' . 'Verifica las existencias.', 422);
            return;
        }

        $this->session->set_flashdata(
            'mensaje_inventario',
            'Movimiento registrado correctamente.'
        );

        redirect('inventario/' . $id . '/historial');
    }

    public function historial($id = NULL)
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('inventario.ver');

        if (!is_string($id) || !ctype_digit($id) || strlen($id) > 10) {
            show_404();
            return;
        }

        $insumo = $this->insumos_model->buscar_incluyendo_inactivos($id);

        if (!$insumo) {
            show_404();
            return;
        }

        $this->load->view('inventario/historial', array(
            'usuario' => $this->usuario,
            'insumo' => $insumo,
            'movimientos' =>
            $this->movimientos_model->historial($id)
        ));
    }

    public function estado($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('inventario.gestionar');

        if (!is_string($id) || !ctype_digit($id) || strlen($id) > 10) {
            show_404();
            return;
        }

        $insumo = $this->insumos_model->buscar_incluyendo_inactivos($id);

        if (!$insumo) {
            show_404();
            return;
        }

        $nuevoEstado = !$insumo['activo'];

        if (!$this->insumos_model->cambiar_estado($id, $nuevoEstado)) {
            show_error('No se pudo cambiar el estado del insumo.', 503);
            return;
        }

        redirect('inventario');
    }

    public function exportar()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('inventario.ver');

        $filas = $this->insumos_model->listar(array());

        $stream = fopen('php://temp', 'w+');

        // BOM para compatibilidad con Excel.
        fwrite($stream, "\xEF\xBB\xBF");

        fputcsv($stream, array(
            'Nombre',
            'Cantidad actual',
            'Stock mínimo',
            'Unidad',
            'Fecha de vencimiento'
        ));

        foreach ($filas as $fila) {
            $valores = array(
                $fila['nombre'],
                $fila['cantidad_actual'],
                $fila['stock_minimo'],
                $fila['unidad_medida'],
                $fila['fecha_vencimiento']
            );

            // Evitar que una hoja de cálculo interprete contenido ingresado por usuarios como formula.
            foreach ($valores as &$valor) {
                $valor = (string) $valor;

                if (preg_match('/^\s*[=+\-@]/u', $valor)) {
                    $valor = "'" . $valor;
                }
            }
            unset($valor);

            fputcsv($stream, $valores);
        }

        rewind($stream);
        $contenido = stream_get_contents($stream);
        fclose($stream);

        $this->output
            ->set_content_type('text/csv', 'UTF-8')
            ->set_header(
                'Content-Disposition: attachment; ' .
                    'filename="inventario.csv"'
            )
            ->set_output($contenido);
    }
    public function vender($id = NULL)
{
    $this->requerir_permiso('inventario.ver');
    $this->requerir_permiso('productos.gestionar');

    if (
        !is_string($id)
        || !ctype_digit($id)
        || strlen($id) > 10
        || (int) $id < 1
    ) {
        show_404();
        return;
    }

    $insumo = $this->insumos_model->buscar($id);

    if (!$insumo) {
        show_404();
        return;
    }

    $datos = array(
        'usuario' => $this->usuario,
        'insumo' => $insumo,
        'sku' => '',
        'precio' => '',
        'error' => ''
    );

    if ($this->input->method(TRUE) === 'GET') {
        $this->load->view('inventario/vender', $datos);
        return;
    }

    $this->solo_metodo('POST');

    $sku = $this->input->post('sku');
    $precio = $this->input->post('precio');

    // Conservar lo escrito si falla la validación.
    $datos['sku'] = is_string($sku) ? $sku : '';
    $datos['precio'] = is_string($precio) ? $precio : '';

    $this->load->model('Producto_model', 'productos_model');

    $error = $this->productos_model->crear_desde_insumo(
        $id,
        $sku,
        $precio
    );

    if ($error !== NULL) {
        $datos['error'] = $error;
        $this->load->view('inventario/vender', $datos);
        return;
    }

    $this->session->set_flashdata(
        'mensaje_inventario',
        'Artículo habilitado para venta. Ya está disponible en pedidos.'
    );

    redirect('inventario');
}
}
