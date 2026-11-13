<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Compras extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requerir_permiso('compras.ver');
        $this->load->model('Solicitud_compra_model', 'compras_model');
        $this->load->model('Insumo_model', 'insumos_model');
        $this->load->library('Validacion_negocio');
        $this->load->helper(array('form', 'url'));
    }
    private function solicitud($id)
    {
        try {
            $id = Validacion_negocio::id($id);
        } catch (InvalidArgumentException $e) {
            show_404();
            exit;
        }
        $s = $this->compras_model->buscar($id);
        if (!$s) {
            show_404();
            exit;
        }
        return $s;
    }
    public function index()
    {
        $this->solo_metodo('GET');
        $p = $this->input->get('pagina');
        try {
            $filtros = array(
                'busqueda'=>Validacion_negocio::texto($this->input->get('busqueda') ?? '', 'Búsqueda',150,FALSE),
                'estado'=>Validacion_negocio::texto($this->input->get('estado') ?? '', 'Estado',20,FALSE)
            );
            if (!in_array($filtros['estado'],array('','borrador','confirmada','enviada','recibida','cancelada'),TRUE)) {
                throw new InvalidArgumentException('Selecciona un estado válido para las compras.');
            }
            $datos = $this->compras_model->listar(is_string($p) && ctype_digit($p) && strlen($p)<=10 ? (int)$p : 1, $filtros);
        } catch (InvalidArgumentException $e) { show_error(html_escape($e->getMessage()),422); return; }
        $datos['filtros'] = $filtros;
        $datos['usuario'] = $this->usuario;
        $datos['mensaje'] = $this->session->flashdata('mensaje_compra');
        $datos['creadas'] = $this->session->flashdata('compras_creadas');
        $this->load->view('compras/index', $datos);
    }
    private function datos_post()
    {
        $ids = $this->input->post('insumos');
        $cantidades = $this->input->post('cantidades');
        if (!is_array($ids) || !is_array($cantidades) || count($ids) > 100 || array_keys($ids) !== array_keys($cantidades)) {
            throw new InvalidArgumentException('Revisa las líneas de la solicitud (máximo 100).');
        }
        $lineas = array();
        foreach ($ids as $i => $id) {
            if (!is_string($id) || !is_string($cantidades[$i])) {
                throw new InvalidArgumentException('Formato de línea inválido.');
            }
            if ($id === '' && $cantidades[$i] === '') {
                continue;
            }
            $lineas[] = array('insumo_id' => $id, 'cantidad' => $cantidades[$i]);
        }
        $datos = array();
        foreach (array('proveedor_id', 'fecha', 'observaciones') as $campo) {
            $v = $this->input->post($campo);
            if ($campo === 'observaciones' && $v === NULL) { $v = ''; }
            if (!is_string($v)) {
                throw new InvalidArgumentException('Revisa el campo ' . $campo . '.');
            }
            $datos[$campo] = $v;
        }
        $datos['lineas'] = $lineas;
        return $datos;
    }
    private function formulario($datos, $lineas, $accion, $error = '')
    {
        $this->load->view('compras/formulario', array(
            'usuario' => $this->usuario,
            'datos' => $datos,
            'lineas' => $lineas,
            'accion' => $accion,
            'error' => $error,
            'proveedores' => $this->compras_model->proveedores(),
            'insumos' => $this->compras_model->insumos()
        ));
    }
    public function crear()
    {
        $this->requerir_permiso('compras.gestionar');
        $datos = array('proveedor_id' => '', 'fecha' => date('Y-m-d'), 'observaciones' => '', 'clave' => bin2hex(random_bytes(32)));
        $lineas = array(array('insumo_id' => '', 'cantidad' => ''));
        $error = '';
        if ($this->input->method(TRUE) !== 'GET') {
            $this->solo_metodo('POST');
            try {
                $datos = $this->datos_post();
                $datos['clave'] = Validacion_negocio::clave($this->input->post('clave'));
                $lineas = $datos['lineas'];
                $r = $this->compras_model->crear_borrador($datos, $datos['clave']);
                $error = $r['error'];
                if ($error === NULL) {
                    $this->session->set_flashdata('mensaje_exito', 'Solicitud de compra creada.');
                    redirect('compras/' . $r['id'], 'location', 303);
                    return;
                }
            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
            $datos['clave'] = is_string($datos['clave'] ?? NULL) ? $datos['clave'] : bin2hex(random_bytes(32));
            $this->output->set_status_header(422);
        }
        $this->formulario($datos, $lineas, 'compras/crear', $error);
    }
    public function editar($id = NULL)
    {
        $this->requerir_permiso('compras.gestionar');
        $s = $this->solicitud($id);
        if ($s['estado'] !== 'borrador') {
            show_error('Solo puedes editar borradores.', 409);
            return;
        }
        $datos = $s;
        $lineas = $this->compras_model->detalles($s['id']);
        $error = '';
        if ($this->input->method(TRUE) !== 'GET') {
            $this->solo_metodo('POST');
            try {
                $datos = $this->datos_post();
                $datos['version'] = Validacion_negocio::id($this->input->post('version'), 'Versión');
                $lineas = $datos['lineas'];
                $r = $this->compras_model->editar_borrador((int)$s['id'], $datos['version'], $datos);
                $error = $r['error'];
                if ($error === NULL) {
                    $this->session->set_flashdata('mensaje_exito', 'Solicitud de compra actualizada.');
                    redirect('compras/' . $s['id'], 'location', 303);
                    return;
                }
            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
            $datos['version'] = $datos['version'] ?? $s['version'];
            $this->output->set_status_header(422);
        }
        $this->formulario($datos, $lineas, 'compras/' . $s['id'] . '/editar', $error);
    }
    public function ver($id = NULL)
    {
        $this->solo_metodo('GET');
        $s = $this->solicitud($id);
        $this->load->view('compras/detalle', array(
            'usuario' => $this->usuario,
            'solicitud' => $s,
            'lineas' => $this->compras_model->detalles($s['id']),
            'error' => $this->session->flashdata('error_compra')
        ));
    }
    public function confirmar($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('compras.confirmar');
        $s = $this->solicitud($id);
        $r = $this->input->post('revisada') === '1' ? $this->compras_model->confirmar((int)$s['id'], $this->input->post('version')) : array('error' => 'Marca la confirmación de revisión.');
        if ($r['error'] !== NULL) {
            $this->session->set_flashdata('error_compra', $r['error']);
        } else {
            $this->session->set_flashdata('mensaje_exito', 'Solicitud de compra confirmada.');
        }
        redirect('compras/' . $s['id'], 'location', 303);
    }
    private function texto_documento($s, $lineas)
    {
        $texto = 'SOLICITUD DE COMPRA #' . $s['numero'] . "\n" . $this->usuario->empresaNombre() . "\n";
        $texto .= 'Estado: ' . $s['estado'] . "\nFecha: " . $s['fecha'] . "\nProveedor: " . $s['proveedor_nombre'] . "\n";
        foreach (array('contacto' => 'Contacto', 'telefono' => 'Teléfono', 'correo' => 'Correo', 'direccion' => 'Dirección') as $c => $etiqueta) {
            if ($s['proveedor_' . $c] !== '') {
                $texto .= $etiqueta . ': ' . $s['proveedor_' . $c] . "\n";
            }
        }
        $texto .= "\nINSUMOS SOLICITADOS\n";
        foreach ($lineas as $l) {
            $texto .= $l['cantidad'] . ' ' . $l['unidad_medida'] . ' — ' . $l['insumo_nombre'] . "\n";
        }
        $texto .= "\nObservaciones: " . ($s['observaciones'] !== '' ? $s['observaciones'] : 'Sin observaciones') . "\n";
        if ($s['estado'] === 'borrador') {
            $texto .= "\nBORRADOR: pendiente de revisión y confirmación.\n";
        }
        return $texto;
    }
    public function documento($id = NULL)
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('compras.exportar');
        $s = $this->solicitud($id);
        $this->load->view('compras/documento', array('usuario' => $this->usuario, 'solicitud' => $s, 'texto' => $this->texto_documento($s, $this->compras_model->detalles($s['id']))));
    }
    public function exportar($id = NULL)
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('compras.exportar');
        $s = $this->solicitud($id);
        $this->output->set_content_type('text/plain', 'utf-8')->set_header('Content-Disposition: attachment; filename="solicitud-' . (int)$s['numero'] . '.txt"')
            ->set_output($this->texto_documento($s, $this->compras_model->detalles($s['id'])));
    }
    public function proveedor_nuevo()
    {
        $this->requerir_permiso('compras.gestionar');
        $this->requerir_permiso('proveedores.gestionar');
        $datos = array_fill_keys(array('nombre', 'contacto', 'telefono', 'correo', 'direccion'), '');
        $error = '';
        if ($this->input->method(TRUE) !== 'GET') {
            $this->solo_metodo('POST');
            $entrada = array();
            foreach ($datos as $k => $v) {
                $entrada[$k] = $this->input->post($k);
                $datos[$k] = is_string($entrada[$k]) ? $entrada[$k] : '';
            }
            $r = $this->compras_model->crear_proveedor($entrada);
            $error = $r['error'];
            if ($error === NULL) {
                $this->session->set_flashdata('mensaje_exito', 'Proveedor registrado. Ya puedes seleccionarlo al crear una solicitud.');
                redirect(ui_ruta_retorno('compras'), 'location', 303);
                return;
            }
            $this->output->set_status_header(422);
        }
        $this->load->view('compras/proveedor', array('usuario' => $this->usuario, 'datos' => $datos, 'error' => $error));
    }

    public function cambiar_estado($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('compras.confirmar');

        $solicitud = $this->solicitud($id);

        $version = $this->input->post('version', TRUE);
        $estado = $this->input->post('estado', TRUE);
        $motivo = $this->input->post('motivo', TRUE);

        if ($motivo === NULL) {
            $motivo = '';
        }

        $resultado = $this->compras_model->cambiar_estado(
            (int) $solicitud['id'],
            $version,
            $estado,
            $motivo
        );

        if ($resultado['error'] !== NULL) {
            $this->session->set_flashdata(
                'error_compra',
                $resultado['error']
            );
        } else {
            $this->session->set_flashdata(
                'mensaje_exito',
                'Estado de la solicitud actualizado.'
            );
        }

        redirect(
            'compras/' . (int) $solicitud['id'],
            'location',
            303
        );
    }

    public function historial_proveedor($id = NULL)
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('compras.ver');

        try {
            $id = Validacion_negocio::id($id, 'Proveedor');
        } catch (InvalidArgumentException $e) {
            show_404();
            return;
        }

        $pagina = $this->input->get('pagina', TRUE);

        $pagina = is_string($pagina) &&
            ctype_digit($pagina) &&
            (int) $pagina > 0
            ? (int) $pagina : 1;

        $datos = $this->compras_model
            ->historial_proveedor($id, $pagina);

        if (!$datos) {
            show_404();
            return;
        }

        $datos['usuario'] = $this->usuario;

        $this->load->view(
            'compras/historial_proveedor',
            $datos
        );
    }

    public function recepcion($id = NULL)
    {
        $this->requerir_permiso('compras.confirmar');

        $s = $this->solicitud($id);

        if ($this->input->method(TRUE) === 'GET') {

            if ($s['estado'] !== 'enviada') {
                show_error(
                    'La solicitud no está disponible para recepción.',
                    409
                );
                return;
            }

            $this->load->view('compras/recepcion', array(
                'usuario' => $this->usuario,
                'solicitud' => $s,
                'lineas' => $this->compras_model->detalles($s['id']),
                'clave' => bin2hex(random_bytes(32))
            ));

            return;
        }

        $this->solo_metodo('POST');

        $cantidades = $this->input->post('cantidades');

        if (!is_array($cantidades)) {
            show_error('Cantidades inválidas.', 422);
            return;
        }

        $resultado = $this->compras_model->recibir(
            (int) $s['id'],
            $this->input->post('version'),
            $this->input->post('clave'),
            $cantidades
        );

        if ($resultado['error'] !== NULL) {

            $this->session->set_flashdata(
                'error_compra',
                $resultado['error']
            );

            redirect(
                'compras/' . (int) $s['id'] . '/recepcion',
                'location',
                303
            );

            return;
        }

        $this->session->set_flashdata(
            'mensaje_exito',
            'Recepción registrada correctamente.'
        );

        redirect(
            'compras/' . (int) $s['id'],
            'location',
            303
        );
    }
    public function seleccionar()
    {
        $metodo = $this->input->method(TRUE);
        if (!in_array($metodo, array('GET', 'POST'), TRUE)) {
            $this->solo_metodo('GET');
        }
        $this->requerir_permiso('inventario.ver');
        $this->requerir_permiso('compras.gestionar');

        $insumos = $this->insumos_model->listar(array('orden' => 'nombre_asc'));
        $porId = array();
        foreach ($insumos as $insumo) {
            $porId[(int) $insumo['id']] = $insumo;
        }

        $error = '';
        $valores = array();
        $seleccion = array();
        if ($metodo === 'GET') {
            $guardada = $this->session->userdata('compra_seleccion');
            if (is_array($guardada) && count($guardada) <= 200) {
                $vigente = array();
                foreach ($guardada as $id => $cantidad) {
                    $idTexto = (string) $id;
                    if (!ctype_digit($idTexto) || strlen($idTexto) > 10
                        || (int) $idTexto < 1 || !isset($porId[(int) $idTexto])
                        || !is_string($cantidad)
                        || preg_match('/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/D', $cantidad) !== 1
                        || (float) $cantidad <= 0) {
                        continue;
                    }
                    $id = (int) $id;
                    $vigente[$id] = $cantidad;
                    $valores[$id] = $cantidad;
                    $linea = $porId[$id];
                    $linea['cantidad_solicitada'] = $cantidad;
                    $seleccion[] = $linea;
                }
                if ($vigente !== $guardada) {
                    $this->session->set_userdata('compra_seleccion', $vigente);
                }
            } elseif ($guardada !== NULL) {
                $this->session->unset_userdata('compra_seleccion');
                $this->session->unset_userdata('compra_seleccion_nonce');
            }
        } else {
            $cantidades = $this->input->post('cantidad', FALSE);
            $guardar = array();
            if (!is_array($cantidades) || count($cantidades) > 5000) {
                $error = 'La selección contiene demasiados insumos.';
            } else {
                foreach ($cantidades as $id => $cantidad) {
                    if (!is_string($id) && !is_int($id)) {
                        $error = 'La selección contiene un insumo inválido.';
                        break;
                    }
                    $idTexto = (string) $id;
                    if (!ctype_digit($idTexto) || strlen($idTexto) > 10
                        || (int) $idTexto < 1 || !isset($porId[(int) $idTexto])) {
                        $error = 'La selección contiene un insumo no disponible.';
                        break;
                    }
                    if (!is_string($cantidad) || is_array($cantidad)) {
                        $error = 'Indica una cantidad válida para cada insumo seleccionado.';
                        break;
                    }
                    try {
                        $idNormalizado = Validacion_negocio::id($idTexto, 'Insumo');
                        if (array_key_exists($idNormalizado, $valores)) {
                            throw new InvalidArgumentException('Un insumo aparece repetido en la selección.');
                        }
                        $valores[$idNormalizado] = $cantidad;
                        // Una casilla vacía significa que el insumo no fue seleccionado.
                        if ($cantidad === '') { continue; }
                        $cantidad = Validacion_negocio::decimal($cantidad, 'Cantidad solicitada', FALSE);
                    } catch (InvalidArgumentException $e) {
                        $error = $e->getMessage();
                        break;
                    }
                    $linea = $porId[(int) $idTexto];
                    $linea['cantidad_solicitada'] = $cantidad;
                    $seleccion[] = $linea;
                    $guardar[(int) $idTexto] = $cantidad;
                    if (count($seleccion) > 200) {
                        $error = 'Selecciona hasta 200 insumos por solicitud.';
                        break;
                    }
                }
                if ($error === '' && !$seleccion) {
                    $error = 'Indica la cantidad de al menos un insumo.';
                }
            }
            if ($error !== '') {
                $seleccion = array();
                $this->output->set_status_header(422);
            } else {
                $this->session->set_userdata('compra_seleccion', $guardar);
                $this->session->set_userdata('compra_seleccion_nonce', bin2hex(random_bytes(32)));
                redirect('compras/seleccionar');
                return;
            }
        }

        $this->load->view('compras/seleccionar', array(
            'usuario' => $this->usuario,
            'insumos' => $insumos,
            'valores' => $valores,
            'seleccion' => $seleccion,
            'error' => $error,
            'mensaje' => $this->session->flashdata('mensaje_compra_seleccion'),
            'creadas' => $this->session->flashdata('compras_creadas'),
            'proveedores' => $this->compras_model->proveedores()
        ));
    }

    public function agregar()
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('inventario.ver');
        $this->requerir_permiso('compras.gestionar');

        try {
            $id = Validacion_negocio::id($this->input->post('insumo_id'), 'Insumo');
            $cantidad = Validacion_negocio::decimal($this->input->post('cantidad'), 'Cantidad solicitada', FALSE);
        } catch (InvalidArgumentException $e) {
            show_error($e->getMessage(), 422);
            return;
        }

        if (!$this->insumos_model->buscar($id)) {
            show_404();
            return;
        }

        $seleccion = $this->session->userdata('compra_seleccion');
        if (!is_array($seleccion) || count($seleccion) > 200) {
            $seleccion = array();
        }
        if (!isset($seleccion[$id]) && count($seleccion) >= 200) {
            show_error('Selecciona hasta 200 insumos para preparar compras.', 422);
            return;
        }

        $seleccion[$id] = $cantidad;
        $this->session->set_userdata('compra_seleccion', $seleccion);
        $this->session->set_userdata('compra_seleccion_nonce', bin2hex(random_bytes(32)));
        redirect('compras/seleccionar', 'location', 303);
    }

    public function vaciar()
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('inventario.ver');
        $this->requerir_permiso('compras.gestionar');
        $this->session->unset_userdata('compra_seleccion');
        $this->session->unset_userdata('compra_seleccion_nonce');
        redirect('compras/seleccionar');
    }

    public function generar()
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('inventario.ver');
        $this->requerir_permiso('compras.gestionar');

        $guardada = $this->session->userdata('compra_seleccion');
        if (!is_array($guardada) || !$guardada || count($guardada) > 200) {
            $this->session->set_flashdata('mensaje_compra_seleccion', 'Selecciona insumos antes de generar solicitudes.');
            redirect('compras/seleccionar', 'location', 303);
            return;
        }

        try {
            $fecha = Validacion_negocio::fecha($this->input->post('fecha'));
            $observaciones = Validacion_negocio::texto($this->input->post('observaciones') ?? '', 'Observaciones', 1000, FALSE);
        } catch (InvalidArgumentException $e) {
            $this->session->set_flashdata('mensaje_compra_seleccion', $e->getMessage());
            redirect('compras/seleccionar', 'location', 303);
            return;
        }

        $insumos = $this->insumos_model->listar(array('orden' => 'nombre_asc'));
        $porId = array();
        foreach ($insumos as $insumo) {
            $porId[(int) $insumo['id']] = $insumo;
        }

        $grupos = array();
        foreach ($guardada as $id => $cantidad) {
            $idTexto = (string) $id;
            if (!ctype_digit($idTexto) || strlen($idTexto) > 10 || !isset($porId[(int) $idTexto])) {
                $this->session->set_flashdata('mensaje_compra_seleccion', 'Un insumo seleccionado ya no está disponible. Revisa la selección.');
                redirect('compras/seleccionar', 'location', 303);
                return;
            }
            try {
                $cantidad = Validacion_negocio::decimal($cantidad, 'Cantidad solicitada', FALSE);
            } catch (InvalidArgumentException $e) {
                $this->session->set_flashdata('mensaje_compra_seleccion', $e->getMessage());
                redirect('compras/seleccionar', 'location', 303);
                return;
            }
            $proveedor = $porId[(int) $idTexto]['proveedor_id'];
            if ($proveedor === NULL) {
                $this->session->set_flashdata('mensaje_compra_seleccion', 'Asocia un proveedor a cada insumo antes de generar solicitudes.');
                redirect('compras/seleccionar', 'location', 303);
                return;
            }
            $proveedor = (int) $proveedor;
            $grupos[$proveedor][] = array('insumo_id' => (int) $idTexto, 'cantidad' => $cantidad);
            if (count($grupos[$proveedor]) > 100) {
                $this->session->set_flashdata('mensaje_compra_seleccion', 'Hay más de 100 insumos del mismo proveedor. Reduce la selección.');
                redirect('compras/seleccionar', 'location', 303);
                return;
            }
        }

        ksort($grupos);
        $nombresProveedores = array();
        foreach ($this->compras_model->proveedores() as $proveedor) {
            $nombresProveedores[(int) $proveedor['id']] = $proveedor['nombre'];
        }
        $nonce = $this->session->userdata('compra_seleccion_nonce');
        if (!is_string($nonce) || preg_match('/^[a-f0-9]{64}$/D', $nonce) !== 1) {
            $nonce = bin2hex(random_bytes(32));
            $this->session->set_userdata('compra_seleccion_nonce', $nonce);
        }
        $creadas = 0;
        $documentosCreados = array();
        $error = '';
        foreach ($grupos as $proveedor => $lineas) {
            $resultado = $this->compras_model->crear_borrador(array(
                'proveedor_id' => $proveedor,
                'fecha' => $fecha,
                'observaciones' => $observaciones,
                'lineas' => $lineas
            ), hash('sha256', $nonce . ':' . $proveedor));
            if ($resultado['error'] !== NULL) {
                $error = $resultado['error'];
                break;
            }
            $creadas++;
            $documentosCreados[] = array(
                'id' => (int) $resultado['id'],
                'proveedor' => $nombresProveedores[$proveedor] ?? 'Proveedor ' . $proveedor
            );
            foreach ($lineas as $linea) {
                unset($guardada[$linea['insumo_id']]);
            }
            $this->session->set_userdata('compra_seleccion', $guardada);
        }

        if (!$guardada) {
            $this->session->unset_userdata('compra_seleccion_nonce');
        }

        $mensaje = $creadas . ($creadas === 1 ? ' solicitud creada.' : ' solicitudes creadas.');
        if ($error !== '') {
            $mensaje .= ' Las restantes siguen seleccionadas. ' . $error;
        }
        if ($documentosCreados) {
            $this->session->set_flashdata('compras_creadas', $documentosCreados);
        }
        $this->session->set_flashdata($guardada ? 'mensaje_compra_seleccion' : 'mensaje_compra', $mensaje);
        redirect($guardada ? 'compras/seleccionar' : 'compras', 'location', 303);
    }
}
