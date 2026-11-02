<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pedidos extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('Dinero');

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

        if (!is_string($this->input->post('referencia'))) { show_error('La referencia es obligatoria y debe ser texto.',422);return; }

        $this->form_validation->set_rules(
            'referencia',
            'Referencia',
            'trim|required|max_length[100]'
        );

        if ($this->form_validation->run() === FALSE) {
            $this->output->set_status_header(422);

            $this->load->view('pedidos/formulario', array(
                'usuario' => $this->usuario
            ));

            return;
        }

        try { $referencia = Validacion_negocio::texto($this->input->post('referencia'), 'Referencia', 100); }
        catch (InvalidArgumentException $e) { show_error(html_escape($e->getMessage()), 422); return; }

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
        $estados = array(
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente de preparación',
            'en_preparacion' => 'En preparación',
            'listo' => 'Listo',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado'
        );

        $estados_pago = array(
            'pendiente' => 'Sin pagos',
            'parcial' => 'Pago parcial',
            'pagado' => 'Pagado'
        );

        $filtros = array();

        foreach (
            array('busqueda', 'estado', 'estado_pago', 'desde', 'hasta')
            as $campo
        ) {
            $valor = $this->input->get($campo);

            if ($valor !== NULL && !is_string($valor)) {
                show_error('Los filtros no son válidos.', 400);
                return;
            }

            $filtros[$campo] = $valor === NULL ? '' : trim($valor);
        }

        if (mb_strlen($filtros['busqueda'], 'UTF-8') > 150) {
            show_error(
                'La búsqueda debe tener como máximo 150 caracteres.',
                400
            );
            return;
        }

        if (
            $filtros['estado'] !== ''
            && !isset($estados[$filtros['estado']])
        ) {
            show_error('El estado del pedido no es válido.', 400);
            return;
        }

        if (
            $filtros['estado_pago'] !== ''
            && !isset($estados_pago[$filtros['estado_pago']])
        ) {
            show_error('El estado de pago no es válido.', 400);
            return;
        }

        foreach (array('desde', 'hasta') as $campo) {
            $valor = $filtros[$campo];

            if ($valor === '') {
                continue;
            }

            if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $valor)) {
                show_error('Usa fechas con formato año-mes-día.', 400);
                return;
            }

            $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

            if (
                !$fecha
                || $fecha->format('Y-m-d') !== $valor
                || $valor < '1000-01-01'
            ) {
                show_error('Selecciona una fecha válida.', 400);
                return;
            }
        }

        if (
            $filtros['desde'] !== ''
            && $filtros['hasta'] !== ''
            && $filtros['desde'] > $filtros['hasta']
        ) {
            show_error(
                'La fecha inicial no puede ser posterior a la fecha final.',
                400
            );
            return;
        }

        $valor_pagina = $this->input->get('pagina');
        $pagina = 1;

        if ($valor_pagina !== NULL) {
            if (
                !is_string($valor_pagina)
                || !ctype_digit($valor_pagina)
                || strlen($valor_pagina) > 9
                || (int) $valor_pagina < 1
            ) {
                show_error('El número de página no es válido.', 400);
                return;
            }

            $pagina = (int) $valor_pagina;
        }

        $por_pagina = 20;

        $total = $this->pedidos_model->contar_historial($filtros);

        $paginas = max(
            1,
            (int) ceil($total / $por_pagina)
        );

        $pagina = min($pagina, $paginas);
        $offset = ($pagina - 1) * $por_pagina;

        $pedidos = $this->pedidos_model->listar(
            $filtros,
            $por_pagina,
            $offset
        );

        $this->load->view('pedidos/index', array(
            'usuario' => $this->usuario,
            'pedidos' => $pedidos,
            'filtros' => $filtros,
            'estados' => $estados,
            'estados_pago' => $estados_pago,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
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
            'eventos' => $this->pedidos_model->eventos($id),
            'productos' => $this->productos_model->listar(),

            'cancelacion' => $this->pedidos_model->cancelacion($id),

            'mensaje' => $this->session->flashdata('mensaje_pedido'),
            'error_pedido' => $this->session->flashdata('error_pedido'),
            'motivo_anterior' => $this->session->flashdata(
                'motivo_cancelacion'
            )
        ));
    }
        public function agregar($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('pedidos.ver');
        $this->requerir_permiso('pedidos.crear');
        if ( !is_string($id) || !ctype_digit($id) || strlen($id) > 10 || (int) $id < 1) {
            show_404();
            return;
        }

        // Comprobar que el pedido pertenece a la empresa actual.
        if (!$this->pedidos_model->buscar($id)) {
            show_404();
            return;
        }

        $producto_id = $this->input->post('producto_id');
        $cantidad = $this->input->post('cantidad');

        if (
            !is_string($producto_id)
            || !ctype_digit($producto_id)
            || strlen($producto_id) > 10
            || (int) $producto_id < 1
        ) {
            show_error('Selecciona un producto válido.', 400);
            return;
        }

        if (
            !is_string($cantidad)
            || !ctype_digit($cantidad)
            || strlen($cantidad) > 3
            || (int) $cantidad < 1
            || (int) $cantidad > 999
        ) {
            show_error('La cantidad debe estar entre 1 y 999.', 400);
            return;
        }

        $error = $this->pedidos_model->agregar_producto(
            (int) $id,
            (int) $producto_id,
            (int) $cantidad
        );

        if ($error !== NULL) {
            show_error(html_escape($error), 400);
            return;
        }

        redirect('pedidos/' . (int) $id);
    }

        public function actualizar($id = NULL, $detalle_id = NULL)
    {
        $this->procesar_cambio_detalle($id, $detalle_id, FALSE);
    }

    public function quitar($id = NULL, $detalle_id = NULL)
    {
        $this->procesar_cambio_detalle($id, $detalle_id, TRUE);
    }

    private function procesar_cambio_detalle($id, $detalle_id, $quitar)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('pedidos.ver');
        $this->requerir_permiso('pedidos.crear');

        foreach (array($id, $detalle_id) as $valor) {
            if (
                !is_string($valor)
                || !ctype_digit($valor)
                || strlen($valor) > 10
                || (int) $valor < 1
            ) {
                show_404();
                return;
            }
        }

        if (!$this->pedidos_model->buscar($id)) {
            show_404();
            return;
        }

        $cantidad = NULL;

        if (!$quitar) {
            $valor = $this->input->post('cantidad');

            if (
                !is_string($valor)
                || !ctype_digit($valor)
                || strlen($valor) > 3
                || (int) $valor < 1
                || (int) $valor > 999
            ) {
                show_error('La cantidad debe estar entre 1 y 999.', 400);
                return;
            }

            $cantidad = (int) $valor;
        }

        $error = $this->pedidos_model->modificar_detalle(
            (int) $id,
            (int) $detalle_id,
            $cantidad
        );

        if ($error !== NULL) {
            show_error(html_escape($error), 400);
            return;
        }

        redirect('pedidos/' . (int) $id);
    }
        public function confirmar($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('pedidos.ver');
        $this->requerir_permiso('pedidos.confirmar');

        if (
            !is_string($id)
            || !ctype_digit($id)
            || strlen($id) > 10
            || (int) $id < 1
        ) {
            show_404();
            return;
        }

        // Solo permite acceder a pedidos de la empresa actual.
        if (!$this->pedidos_model->buscar($id)) {
            show_404();
            return;
        }

        $error = $this->pedidos_model->confirmar(
            (int) $id,
            $this->usuario->id(),
            $this->usuario->rol()
        );

        if ($error !== NULL) {
            show_error(html_escape($error), 400);
            return;
        }

        redirect('pedidos/' . (int) $id);
    }
        public function cancelar($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('pedidos.ver');
        $this->requerir_permiso('pedidos.cancelar');

        // Comprobar que el identificador es válido.
        if (
            !is_string($id)
            || !ctype_digit($id)
            || strlen($id) > 10
            || (int) $id < 1
        ) {
            show_404();
            return;
        }

        // El modelo busca únicamente dentro de la empresa actual.
        if (!$this->pedidos_model->buscar($id)) {
            show_404();
            return;
        }

        $motivo = $this->input->post('motivo');
        $confirmacion = $this->input->post('confirmar_cancelacion');

        if (
            !is_string($motivo)
            || trim($motivo) === ''
            || mb_strlen(trim($motivo), 'UTF-8') > 500
        ) {
            $error = 'Escribe un motivo de entre 1 y 500 caracteres.';
        } elseif ($confirmacion !== '1') {
            $error = 'Marca la casilla para autorizar la cancelación.';
        } else {
            $error = $this->pedidos_model->cancelar(
                (int) $id,
                trim($motivo)
            );
        }

        if ($error !== NULL) {
            $this->session->set_flashdata('error_pedido', $error);

            $this->session->set_flashdata(
                'motivo_cancelacion',
                is_string($motivo)
                    ? mb_substr($motivo, 0, 500, 'UTF-8')
                    : ''
            );
        } else {
            $this->session->set_flashdata(
                'mensaje_pedido',
                'Pedido cancelado. Se registraron el motivo y la autorización.'
            );
        }

        redirect('pedidos/' . (int) $id);
    }
}
