<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Compras extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();$this->requerir_permiso('compras.ver');
        $this->load->model('Solicitud_compra_model','compras_model');$this->load->library('Validacion_negocio');$this->load->helper(array('form','url'));
    }
    private function solicitud($id)
    {
        try { $id=Validacion_negocio::id($id); } catch (InvalidArgumentException $e) { show_404();exit; }
        $s=$this->compras_model->buscar($id);if (!$s) { show_404();exit; }return $s;
    }
    public function index()
    {
        $this->solo_metodo('GET');$p=$this->input->get('pagina');
        $datos=$this->compras_model->listar(is_string($p) && ctype_digit($p) ? (int)$p : 1);
        $datos['usuario']=$this->usuario;$datos['mensaje']=$this->session->flashdata('mensaje_compra');
        $this->load->view('compras/index',$datos);
    }
    private function datos_post()
    {
        $ids=$this->input->post('insumos');$cantidades=$this->input->post('cantidades');
        if (!is_array($ids) || !is_array($cantidades) || count($ids)>100 || array_keys($ids)!==array_keys($cantidades)) {
            throw new InvalidArgumentException('Revisa las líneas de la solicitud (máximo 100).');
        }
        $lineas=array();
        foreach ($ids as $i=>$id) {
            if (!is_string($id) || !is_string($cantidades[$i])) { throw new InvalidArgumentException('Formato de línea inválido.'); }
            if ($id==='' && $cantidades[$i]==='') { continue; }
            $lineas[]=array('insumo_id'=>$id,'cantidad'=>$cantidades[$i]);
        }
        $datos=array();
        foreach (array('proveedor_id','fecha','observaciones') as $campo) {
            $v=$this->input->post($campo);if (!is_string($v)) { throw new InvalidArgumentException('Revisa el campo '.$campo.'.'); }$datos[$campo]=$v;
        }
        $datos['lineas']=$lineas;return $datos;
    }
    private function formulario($datos,$lineas,$accion,$error='')
    {
        $this->load->view('compras/formulario',array('usuario'=>$this->usuario,'datos'=>$datos,'lineas'=>$lineas,'accion'=>$accion,'error'=>$error,
            'proveedores'=>$this->compras_model->proveedores(),'insumos'=>$this->compras_model->insumos()));
    }
    public function crear()
    {
        $this->requerir_permiso('compras.gestionar');
        $datos=array('proveedor_id'=>'','fecha'=>date('Y-m-d'),'observaciones'=>'','clave'=>bin2hex(random_bytes(32)));
        $lineas=array(array('insumo_id'=>'','cantidad'=>''));$error='';
        if ($this->input->method(TRUE)!=='GET') {
            $this->solo_metodo('POST');
            try {
                $datos=$this->datos_post();$datos['clave']=Validacion_negocio::clave($this->input->post('clave'));$lineas=$datos['lineas'];
                $r=$this->compras_model->crear_borrador($datos,$datos['clave']);$error=$r['error'];
                if ($error===NULL) { redirect('compras/'.$r['id'],'location',303);return; }
            } catch (InvalidArgumentException $e) { $error=$e->getMessage(); }
            $datos['clave']=is_string($datos['clave'] ?? NULL) ? $datos['clave'] : bin2hex(random_bytes(32));
            $this->output->set_status_header(422);
        }
        $this->formulario($datos,$lineas,'compras/crear',$error);
    }
    public function editar($id=NULL)
    {
        $this->requerir_permiso('compras.gestionar');$s=$this->solicitud($id);
        if ($s['estado']!=='borrador') { show_error('Solo puedes editar borradores.',409);return; }
        $datos=$s;$lineas=$this->compras_model->detalles($s['id']);$error='';
        if ($this->input->method(TRUE)!=='GET') {
            $this->solo_metodo('POST');
            try {
                $datos=$this->datos_post();$datos['version']=Validacion_negocio::id($this->input->post('version'),'Versión');$lineas=$datos['lineas'];
                $r=$this->compras_model->editar_borrador((int)$s['id'],$datos['version'],$datos);$error=$r['error'];
                if ($error===NULL) { redirect('compras/'.$s['id'],'location',303);return; }
            } catch (InvalidArgumentException $e) { $error=$e->getMessage(); }
            $datos['version']=$datos['version'] ?? $s['version'];$this->output->set_status_header(422);
        }
        $this->formulario($datos,$lineas,'compras/'.$s['id'].'/editar',$error);
    }
    public function ver($id=NULL)
    {
        $this->solo_metodo('GET');$s=$this->solicitud($id);
        $this->load->view('compras/detalle',array('usuario'=>$this->usuario,'solicitud'=>$s,'lineas'=>$this->compras_model->detalles($s['id']),
            'error'=>$this->session->flashdata('error_compra')));
    }
    public function confirmar($id=NULL)
    {
        $this->solo_metodo('POST');$this->requerir_permiso('compras.confirmar');$s=$this->solicitud($id);
        $r=$this->input->post('revisada')==='1' ? $this->compras_model->confirmar((int)$s['id'],$this->input->post('version')) : array('error'=>'Marca la confirmación de revisión.');
        if ($r['error']!==NULL) { $this->session->set_flashdata('error_compra',$r['error']); }
        redirect('compras/'.$s['id'],'location',303);
    }
    private function texto_documento($s,$lineas)
    {
        $texto='SOLICITUD DE COMPRA #'.$s['numero']."\n".$this->usuario->empresaNombre()."\n";
        $texto.='Estado: '.$s['estado']."\nFecha: ".$s['fecha']."\nProveedor: ".$s['proveedor_nombre']."\n";
        foreach (array('contacto'=>'Contacto','telefono'=>'Teléfono','correo'=>'Correo','direccion'=>'Dirección') as $c=>$etiqueta) {
            if ($s['proveedor_'.$c]!=='') { $texto.=$etiqueta.': '.$s['proveedor_'.$c]."\n"; }
        }
        $texto.="\nINSUMOS SOLICITADOS\n";
        foreach ($lineas as $l) { $texto.=$l['cantidad'].' '.$l['unidad_medida'].' — '.$l['insumo_nombre']."\n"; }
        $texto.="\nObservaciones: ".($s['observaciones']!=='' ? $s['observaciones'] : 'Sin observaciones')."\n";
        if ($s['estado']==='borrador') { $texto.="\nBORRADOR: pendiente de revisión y confirmación.\n"; }
        return $texto;
    }
    public function documento($id=NULL)
    {
        $this->solo_metodo('GET');$this->requerir_permiso('compras.exportar');$s=$this->solicitud($id);
        $this->load->view('compras/documento',array('usuario'=>$this->usuario,'solicitud'=>$s,'texto'=>$this->texto_documento($s,$this->compras_model->detalles($s['id']))));
    }
    public function exportar($id=NULL)
    {
        $this->solo_metodo('GET');$this->requerir_permiso('compras.exportar');$s=$this->solicitud($id);
        $this->output->set_content_type('text/plain','utf-8')->set_header('Content-Disposition: attachment; filename="solicitud-'.(int)$s['numero'].'.txt"')
            ->set_output($this->texto_documento($s,$this->compras_model->detalles($s['id'])));
    }
    public function proveedor_nuevo()
    {
        $this->requerir_permiso('compras.gestionar');$this->requerir_permiso('proveedores.gestionar');
        $datos=array_fill_keys(array('nombre','contacto','telefono','correo','direccion'),'');$error='';
        if ($this->input->method(TRUE)!=='GET') {
            $this->solo_metodo('POST');$entrada=array();
            foreach ($datos as $k=>$v) { $entrada[$k]=$this->input->post($k);$datos[$k]=is_string($entrada[$k]) ? $entrada[$k] : ''; }
            $r=$this->compras_model->crear_proveedor($entrada);$error=$r['error'];
            if ($error===NULL) { $this->session->set_flashdata('mensaje_compra','Proveedor registrado. Ya puedes seleccionarlo al crear una solicitud.');redirect('compras','location',303);return; }
            $this->output->set_status_header(422);
        }
        $this->load->view('compras/proveedor',array('usuario'=>$this->usuario,'datos'=>$datos,'error'=>$error));
    }
}
