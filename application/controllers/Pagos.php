<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pagos extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requerir_permiso('pedidos.ver');
        $this->requerir_permiso('ventas.ver');
        $this->load->model('Pedido_model', 'pedidos_model');
        $this->load->model('Pago_model', 'pagos_model');
        $this->load->helper(array('url','form'));
    }

    public function index()
    {
        $this->solo_metodo('GET');
        $estado = $this->input->get('estado');
        if (!is_string($estado) || !in_array($estado,array('pendiente','parcial','pagado','todos'),TRUE)) { $estado='todos'; }
        $pagina = $this->input->get('pagina');
        $pagina = is_string($pagina) && ctype_digit($pagina) ? max(1,min(100000,(int)$pagina)) : 1;
        try {
            $pedidos = $this->pagos_model->listar($estado,$pagina);
        } catch (Throwable $e) {
            log_message('error', 'Consulta de pagos: '.$e->getMessage());
            show_error('No se pudo abrir pagos. Ejecuta la actualización de la base de datos indicada en LEEME_PAGOS.md.',503);
            return;
        }
        $siguiente = count($pedidos)>30;
        $this->load->view('pagos/index', array('usuario'=>$this->usuario,'pedidos'=>array_slice($pedidos,0,30),'estado'=>$estado,'pagina'=>$pagina,'siguiente'=>$siguiente));
    }

    private function pedido($id)
    {
        if (!is_string($id) || !ctype_digit($id) || strlen($id)>10 || (int)$id<1) { show_404(); exit; }
        $pedido = $this->pedidos_model->buscar((int)$id);
        if (!$pedido) { show_404(); exit; }
        return $pedido;
    }

    public function ver($id = NULL)
    {
        $this->solo_metodo('GET');
        $pedido = $this->pedido($id);
        try { $datos = $this->pagos_model->resumen((int)$id); }
        catch (Throwable $e) {
            log_message('error', 'Consulta de cuentas: '.$e->getMessage());
            show_error('No se pudieron consultar las cuentas. Revisa la actualización de la base de datos.',503);
            return;
        }
        $datos['usuario']=$this->usuario;
        $datos['pedido']=$pedido;
        $datos['error']=$this->session->flashdata('error_pago');
        $datos['mensaje']=$this->session->flashdata('mensaje_pago');
        $datos['clave']=bin2hex(random_bytes(32));
        $this->load->view('pagos/detalle',$datos);
    }

    private function resultado($id,$error,$mensaje)
    {
        $this->session->set_flashdata($error===NULL ? 'mensaje_pago' : 'error_pago', $error===NULL ? $mensaje : $error);
        redirect('pagos/'.(int)$id,'location',303);
    }

    public function preparar($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('ventas.cobrar');
        $pedido=$this->pedido($id);
        $this->resultado($id,$this->pagos_model->preparar((int)$pedido['id']),'La cuenta está preparada. Puedes cobrarla completa o separar productos antes del primer pago.');
    }

    public function separar($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('ventas.cobrar');
        $this->pedido($id);
        $this->resultado($id,$this->pagos_model->separar((int)$id,$this->input->post('nombre'),$this->input->post('cantidades')),'Cuenta separada. Los productos restantes siguen en la cuenta general.');
    }

    public function reiniciar($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('ventas.cobrar');
        $this->pedido($id);
        $error=$this->input->post('confirmar')==='1' ? $this->pagos_model->reiniciar((int)$id) : 'Marca la confirmación para volver a organizar las cuentas.';
        $this->resultado($id,$error,'Se quitó la distribución. Prepara otra vez la cuenta para organizarla.');
    }

    public function registrar($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('ventas.cobrar');
        $this->pedido($id);
        $cuenta=$this->input->post('cuenta_id');
        if (!is_string($cuenta) || !ctype_digit($cuenta) || strlen($cuenta)>10 || (int)$cuenta<1) {
            $this->resultado($id,'Selecciona una cuenta válida.','');
            return;
        }
        $datos=array();
        foreach (array('efectivo','credito','debito','recibido','clave') as $campo) { $datos[$campo]=$this->input->post($campo); }
        $this->resultado($id,$this->pagos_model->registrar((int)$id,(int)$cuenta,$datos),'Pago registrado. Consulta el historial y el saldo actualizado.');
    }
}
