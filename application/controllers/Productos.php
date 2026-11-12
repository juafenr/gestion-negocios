<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Productos extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Producto_model', 'productos_model');
    }
    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('productos.ver');
        $busqueda = $this->input->get('busqueda') ?? '';
        $pagina = $this->input->get('pagina');
        $pagina = is_string($pagina) && ctype_digit($pagina) && strlen($pagina) <= 10 ? (int) $pagina : 1;
        try {
            $datos = $this->productos_model->paginar($busqueda, $pagina);
        } catch (InvalidArgumentException $e) {
            show_error(html_escape($e->getMessage()), 422);
            return;
        } catch (RuntimeException $e) {
            show_error('No se pudieron consultar los productos. Intenta nuevamente.', 503);
            return;
        }
        $datos['usuario'] = $this->usuario;
        $this->load->view('productos/index', $datos);
    }
    public function ver($id = NULL)
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('productos.ver');
        if (!is_string($id) || !ctype_digit($id) || strlen($id) > 10) { show_404(); }
        $producto = $this->productos_model->buscar($id);
        // La misma respuesta para un ID inexistente y un ID de otra empresa.
        if ($producto === NULL) { show_404(); }
        $this->load->view('productos/detalle', array('usuario' => $this->usuario, 'producto' => $producto));
    }
}
