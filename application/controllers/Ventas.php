<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Ventas extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();$this->requerir_permiso('ventas.historial');
        $this->load->model('Venta_model','ventas_model');$this->load->library('Validacion_negocio');$this->load->helper('negocio');
    }
    public function index()
    {
        $this->solo_metodo('GET');
        $desde=$this->input->get('desde') ?? date('Y-m-01');$hasta=$this->input->get('hasta') ?? date('Y-m-d');
        $pagina=$this->input->get('pagina');$pagina_dias=$this->input->get('pagina_dias');
        try {
            $desde=Validacion_negocio::fecha($desde,'Desde');$hasta=Validacion_negocio::fecha($hasta,'Hasta');
            $pagina=is_string($pagina) && ctype_digit($pagina) ? (int)$pagina : 1;
            $pagina_dias=is_string($pagina_dias) && ctype_digit($pagina_dias) ? (int)$pagina_dias : 1;
            $datos=array_merge($this->ventas_model->informe($desde,$hasta,$pagina),$this->ventas_model->dias($desde,$hasta,$pagina_dias));
            $datos['desde']=$desde;$datos['hasta']=$hasta;$datos['usuario']=$this->usuario;
            $this->load->view('ventas/index',$datos);
        } catch (InvalidArgumentException $e) { show_error(html_escape($e->getMessage()),422); }
        catch (Throwable $e) { log_message('error','Ventas: '.$e->getMessage());show_error('No se pudieron consultar las ventas. Revisa las migraciones.',503); }
    }
    public function ver($id=NULL)
    {
        $this->solo_metodo('GET');
        try { $id=Validacion_negocio::id($id); } catch (InvalidArgumentException $e) { show_404();return; }
        $venta=$this->ventas_model->buscar($id);if (!$venta) { show_404();return; }
        $this->load->view('ventas/detalle',array('usuario'=>$this->usuario,'venta'=>$venta,'detalles'=>$this->ventas_model->detalles($venta['pedido_id'])));
    }
}
