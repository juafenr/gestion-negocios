<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Productos extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Producto_model', 'productos_model');
        $this->load->helper(array('url', 'form'));
    }

    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('productos.ver');
        $busqueda = $this->input->get('busqueda') ?? '';
        $disponibilidad = $this->input->get('disponibilidad') ?? '';
        $pagina = $this->input->get('pagina');
        $pagina = is_string($pagina) && ctype_digit($pagina) && strlen($pagina) <= 10 ? (int) $pagina : 1;
        try {
            $datos = $this->productos_model->paginar($busqueda, $pagina, $disponibilidad);
        } catch (InvalidArgumentException $e) {
            show_error(html_escape($e->getMessage()), 422);
            return;
        } catch (RuntimeException $e) {
            show_error('No se pudieron consultar los productos. Intenta nuevamente.', 503);
            return;
        }
        $datos['usuario'] = $this->usuario;
        $datos['mensaje_producto'] = $this->session->flashdata('mensaje_producto');
        $this->load->view('productos/index', $datos);
    }

    public function ver($id = NULL)
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('productos.ver');

        $producto = $this->obtener_producto($id);

        $historial = array();
        $pagina = 1;
        $siguiente = FALSE;

        $puede_ver_historial = $this->usuario->puede(
            'productos.gestionar'
        );

        if ($puede_ver_historial) {
            $valor = $this->input->get('pagina');

            if ($valor !== NULL) {
                if (
                    !is_string($valor)
                    || !ctype_digit($valor)
                    || strlen($valor) > 6
                    || (int) $valor < 1
                    || (int) $valor > 100000
                ) {
                    show_error('El número de página no es válido.', 400);
                    return;
                }

                $pagina = (int) $valor;
            }

            $filas = $this->productos_model->historial_precios(
                $producto['id'],
                $pagina
            );

            $siguiente = count($filas) > 20;
            $historial = array_slice($filas, 0, 20);
        }

        $this->load->view('productos/detalle', array(
            'usuario' => $this->usuario,
            'producto' => $producto,
            'historial' => $historial,
            'pagina' => $pagina,
            'siguiente' => $siguiente,
            'puede_ver_historial' => $puede_ver_historial
        ));
    }

    public function crear()
    {
        $this->requerir_permiso('productos.ver');
        $this->requerir_permiso('productos.gestionar');

        $producto = array(
            'nombre' => '',
            'sku' => '',
            'descripcion' => '',
            'precio' => '',
            'disponible' => '1'
        );

        if ($this->input->method(TRUE) === 'GET') {
            $this->mostrar_formulario(
                $producto,
                'productos/crear',
                'Nuevo producto'
            );
            return;
        }

        $this->solo_metodo('POST');

        $datos = $this->datos_formulario();
        $resultado = $this->productos_model->guardar_catalogo($datos);

        if ($resultado['error'] !== NULL) {
            $this->mostrar_formulario(
                $this->valores_para_formulario($datos),
                'productos/crear',
                'Nuevo producto',
                $resultado['error']
            );
            return;
        }

        $this->session->set_flashdata(
            'mensaje_producto',
            'Producto creado correctamente.'
        );

        redirect('productos', 'location', 303);
    }

    public function editar($id = NULL)
    {
        $this->requerir_permiso('productos.ver');
        $this->requerir_permiso('productos.gestionar');

        $producto = $this->obtener_producto($id);
        $accion = 'productos/editar/' . (int) $producto['id'];

        if ($this->input->method(TRUE) === 'GET') {
            $this->mostrar_formulario(
                $producto,
                $accion,
                'Editar producto'
            );
            return;
        }

        $this->solo_metodo('POST');

        $datos = $this->datos_formulario();

        $resultado = $this->productos_model->guardar_catalogo(
            $datos,
            (int) $producto['id']
        );

        if ($resultado['error'] !== NULL) {
            $valores = $this->valores_para_formulario($datos);
            $valores['id'] = $producto['id'];

            $this->mostrar_formulario(
                $valores,
                $accion,
                'Editar producto',
                $resultado['error']
            );
            return;
        }

        $this->session->set_flashdata(
            'mensaje_producto',
            'Producto actualizado. Si cambió el precio, se registró en el historial.'
        );

        redirect('productos', 'location', 303);
    }

    private function obtener_producto($id)
    {
        if (
            !is_string($id)
            || !ctype_digit($id)
            || strlen($id) > 10
            || (int) $id < 1
            || (int) $id > 4294967295
        ) {
            show_404();
            exit;
        }

        $producto = $this->productos_model->buscar($id);

        // Misma respuesta para un producto inexistente
        // o perteneciente a otra empresa.
        if ($producto === NULL) {
            show_404();
            exit;
        }

        return $producto;
    }

    private function datos_formulario()
    {
        $datos = array();

        foreach (
            array('nombre', 'sku', 'descripcion', 'precio', 'disponible')
            as $campo
        ) {
            $datos[$campo] = $this->input->post($campo);
        }

        return $datos;
    }

    private function valores_para_formulario(array $datos)
    {
        // Conservar los textos después de una validación fallida.
        // El modelo recibe los valores originales para validarlos.
        foreach ($datos as $campo => $valor) {
            $datos[$campo] = is_string($valor) ? $valor : '';
        }

        return $datos;
    }

    private function mostrar_formulario(
        array $producto,
        $accion,
        $titulo,
        $error = ''
    ) {
        if ($error !== '') { $this->output->set_status_header(422); }
        $this->load->view('productos/formulario', array(
            'usuario' => $this->usuario,
            'producto' => $producto,
            'accion' => $accion,
            'titulo' => $titulo,
            'error' => $error
        ));
    }
}
