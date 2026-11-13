<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Inventario extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('Edicion_segura');
        $this->load->library('Validacion_negocio');

        $this->load->model('Insumo_model', 'insumos_model');
        $this->load->model('Movimientos_inventario_model', 'movimientos_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('inventario.ver');

        try {
            $f = array();
            foreach (array('busqueda'=>150, 'estado'=>20, 'unidad'=>20, 'orden'=>30, 'activo'=>20) as $campo=>$limite) {
                $f[$campo] = Validacion_negocio::texto($this->input->get($campo) ?? '', $campo, $limite, FALSE);
            }
        } catch (InvalidArgumentException $e) { show_error(html_escape($e->getMessage()), 422); return; }
        $busqueda=$f['busqueda']; $estado=$f['estado']; $unidad=$f['unidad']; $orden=$f['orden'];
        $activo=$f['activo']==='inactivos' ? 'inactivos' : 'activos';

        if (!in_array(
            $estado,
            array('', 'normal', 'bajo', 'agotado'),
            TRUE
        )) {
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

        $insumos = $this->insumos_model->listar($filtros);
        $productos_venta = array();
        if ($this->usuario->puede('productos.gestionar')) {
            $this->load->model('Producto_model', 'productos_model');
            $productos_venta = $this->productos_model->por_insumos(array_column($insumos, 'id'));
        }
        $this->load->view('inventario/index', array(
            'usuario' => $this->usuario,
            'insumos' => $insumos,
            'productos_venta' => $productos_venta,
            'filtros' => $filtros,
            'resumen' => $this->insumos_model->resumen(),
            'total' => $total,
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
        $this->configurar_validacion(TRUE);

        if ($this->form_validation->run() === FALSE) {
            $this->output->set_status_header(422);
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

        $this->session->set_flashdata('mensaje_inventario', 'Insumo registrado correctamente.');
        redirect(ui_ruta_retorno('inventario'));
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
            $this->output->set_status_header(422);
            $this->load->view('inventario/formulario', array(
                'usuario' => $this->usuario,
                'insumo' => $insumo,
                'accion' => 'inventario/' . $id . '/editar'
            ));

            return;
        }

        $datos = $this->datos_formulario($insumo);

        if ($this->insumos_model->existe_nombre($datos['nombre'], $id)) {
            show_error('Ya existe un insumo con ese nombre en tu empresa.', 409);
        }

        $guardado = $this->edicion_segura->guardar('insumos',$id,function() use ($id,$datos) {
            return $this->insumos_model->actualizar($id,$datos);
        });
        if (!$guardado) {
            $this->output->set_status_header($this->edicion_segura->error !== '' ? $this->edicion_segura->status : 503);
            $this->load->view('inventario/formulario',array('usuario'=>$this->usuario,'insumo'=>$insumo,
                'accion'=>'inventario/'.$id.'/editar','error_edicion'=>$this->edicion_segura->error ?: 'No se pudo actualizar el insumo.'));
            return;
        }

        $this->session->set_flashdata('mensaje_inventario', 'Insumo actualizado correctamente.');
        redirect(ui_ruta_retorno('inventario'));
    }

    private function configurar_validacion($nuevo = FALSE)
    {
        foreach (array('nombre', 'stock_minimo', 'unidad_medida', 'fecha_vencimiento', 'cantidad_actual') as $campo) {
            $valor = $this->input->post($campo);
            if ($valor !== NULL && !is_string($valor)) {
                show_error('Formato inválido en ' . html_escape($campo) . '.', 422);
            }
        }
        $this->form_validation->set_rules('nombre', 'Nombre', 'trim|required|max_length[150]|callback_nombre_valido');
        $this->form_validation->set_rules('stock_minimo', 'Stock mínimo', 'required|callback_cantidad_valida');
        $this->form_validation->set_rules('unidad_medida', 'Unidad de medida', 'required|in_list[kg,g,mg,l,ml,unidad]');
        $this->form_validation->set_rules('fecha_vencimiento', 'Fecha de vencimiento', 'callback_fecha_valida');

        if ($nuevo) {
            $this->form_validation->set_rules(
                'cantidad_actual',
                'Cantidad actual',
                'required|callback_cantidad_valida'
            );
        }
    }

    public function cantidad_valida($valor)
    {
        try {
            Validacion_negocio::decimal($valor, 'Cantidad');
            return TRUE;
        } catch (InvalidArgumentException $e) {
            $this->form_validation->set_message('cantidad_valida', '{field}: usa un número de 0 a 99999999.99, con hasta dos decimales.');
            return FALSE;
        }
    }

    private function datos_formulario($insumo = NULL)
    {
        $fecha = $this->input->post('fecha_vencimiento', TRUE);
        // Omitir el campo conserva la fecha; enviarlo vacío la elimina.
        if ($insumo !== NULL && $fecha === NULL) {
            $fecha = $insumo['fecha_vencimiento'];
        }
        return array(
            'nombre' => trim($this->input->post('nombre', TRUE)),
            'cantidad_actual' => $this->input->post('cantidad_actual', TRUE),
            'stock_minimo' => $this->input->post('stock_minimo', TRUE),
            'unidad_medida' => $this->input->post('unidad_medida', TRUE),
            'fecha_vencimiento' => $fecha ?: NULL
        );
    }

    public function nombre_valido($valor)
    {
        try { Validacion_negocio::texto($valor, 'Nombre', 150); return TRUE; }
        catch (InvalidArgumentException $e) { $this->form_validation->set_message('nombre_valido', $e->getMessage()); return FALSE; }
    }
    public function fecha_valida($valor)
    {
        try { Validacion_negocio::fecha($valor, 'Vencimiento', TRUE); return TRUE; }
        catch (InvalidArgumentException $e) { $this->form_validation->set_message('fecha_valida', $e->getMessage()); return FALSE; }
    }

    public function movimiento($id = NULL)
    {
        $this->requerir_permiso('inventario.gestionar');
        if (!is_string($id) || !ctype_digit($id) || strlen($id)>10) { show_404(); return; }
        $insumo = $this->insumos_model->buscar($id);
        if (!$insumo) { show_404(); return; }
        $entrada = array('tipo'=>'entrada','cantidad'=>'','motivo'=>'');
        $error = '';
        if ($this->input->method(TRUE) !== 'GET') {
            $this->solo_metodo('POST');
            $tipo = $this->input->post('tipo');
            $cantidad = $this->input->post('cantidad');
            $motivo = $this->input->post('motivo');
            foreach (array('tipo'=>$tipo,'cantidad'=>$cantidad,'motivo'=>$motivo) as $campo=>$valor) {
                $entrada[$campo] = is_string($valor) ? $valor : '';
            }
            if (!is_string($motivo)) { $error = 'El motivo debe ser texto.'; }
            else {
                $motivo = trim($motivo);
                if (!in_array($tipo,array('entrada','salida','ajuste'),TRUE)
                    || !is_string($cantidad) || !preg_match('/^\d{1,8}(\.\d{1,2})?$/D',$cantidad)
                    || ($tipo!=='ajuste' && (float)$cantidad<=0) || $motivo==='' || mb_strlen($motivo,'UTF-8')>255) {
                    $error = 'Revisa el tipo, la cantidad y el motivo del movimiento.';
                } elseif (!$this->movimientos_model->registrar((int)$id,$tipo,$cantidad,$motivo,$this->usuario->id())) {
                    $error = 'No se pudo registrar el movimiento. Verifica las existencias y la cantidad indicada.';
                } else {
                    $this->session->set_flashdata('mensaje_inventario','Movimiento registrado correctamente.');
                    redirect('inventario/'.$id.'/historial');
                    return;
                }
            }
            $this->output->set_status_header(422);
        }
        $this->load->view('inventario/movimiento',array('usuario'=>$this->usuario,'insumo'=>$insumo,
            'entrada'=>$entrada,'error_movimiento'=>$error));
    }

    public function historial($id = NULL)
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('inventario.ver');

        if (
            !is_string($id) ||
            !ctype_digit($id) ||
            strlen($id) > 10 ||
            (int) $id < 1
        ) {
            show_404();
            return;
        }

        $insumo = $this->insumos_model
            ->buscar_incluyendo_inactivos($id);

        if (!$insumo) {
            show_404();
            return;
        }

        $porPagina = 20;

        $total = $this->movimientos_model
            ->contar_historial($id);

        $paginas = max(
            1,
            (int) ceil($total / $porPagina)
        );

        $paginaSolicitada = $this->input->get('pagina', TRUE);

        $pagina = filter_var(
            $paginaSolicitada,
            FILTER_VALIDATE_INT,
            array(
                'options' => array(
                    'min_range' => 1
                )
            )
        );

        if ($pagina === FALSE || $pagina === NULL) {
            $pagina = 1;
        }

        $pagina = min($pagina, $paginas);

        $offset = ($pagina - 1) * $porPagina;

        $movimientos = $this->movimientos_model->historial(
            $id,
            $porPagina,
            $offset
        );

        $this->load->view('inventario/historial', array(
            'usuario' => $this->usuario,
            'insumo' => $insumo,
            'movimientos' => $movimientos,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas
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

        $this->session->set_flashdata('mensaje_inventario', ($nuevoEstado ? 'Insumo reactivado: ' : 'Insumo desactivado: ').$insumo['nombre'].'.');
        redirect(ui_ruta_retorno('inventario'));
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
            $this->output->set_status_header(422);
            $this->load->view('inventario/vender', $datos);
            return;
        }

        $this->session->set_flashdata(
            'mensaje_inventario',
            'Artículo habilitado para venta. Ya está disponible en pedidos.'
        );

        redirect(ui_ruta_retorno('inventario'));
    }

    public function sugerencias()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('inventario.ver');

        $this->load->view('inventario/sugerencias', array(
            'usuario' => $this->usuario,
            'insumos' => $this->insumos_model->sugerencias_compra()
        ));
    }
}
