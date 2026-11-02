<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Venta_model extends MY_Model
{
    public function __construct() { parent::__construct(); $this->load->database(); }
    private function consultar($sql,$valores)
    {
        $q=$this->db->query($sql,$valores);
        if (!$q) { throw new RuntimeException('No se pudieron consultar las ventas.'); }
        return $q;
    }
    public function informe($desde,$hasta,$pagina)
    {
        $this->load->library('Validacion_negocio');
        $desde=Validacion_negocio::fecha($desde,'Desde');$hasta=Validacion_negocio::fecha($hasta,'Hasta');
        if ($desde>$hasta) { throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la final.'); }
        $fin=(new DateTimeImmutable($hasta))->modify('+1 day')->format('Y-m-d');
        $valores=array($this->empresa_id(),$desde,$fin);
        $where=' WHERE empresa_id=? AND fecha>=? AND fecha<?';
        $resumen=$this->consultar('SELECT COUNT(*) cantidad, COALESCE(SUM(subtotal),0) consumo, COALESCE(SUM(propina),0) propina,
            COALESCE(SUM(total),0) total, COALESCE(SUM(efectivo),0) efectivo, COALESCE(SUM(credito),0) credito,
            COALESCE(SUM(debito),0) debito FROM ventas'.$where,$valores)->row_array();
        $paginas=max(1,(int)ceil((int)$resumen['cantidad']/25));$pagina=max(1,min((int)$pagina,$paginas));
        $filas=$this->consultar('SELECT * FROM ventas'.$where.' ORDER BY fecha DESC,id DESC LIMIT 25 OFFSET ?',array_merge($valores,array(($pagina-1)*25)))->result_array();
        // El resumen por día tiene paginación independiente para no truncar períodos largos.
        return array('resumen'=>$resumen,'ventas'=>$filas,'pagina'=>$pagina,'paginas'=>$paginas);
    }
    public function dias($desde,$hasta,$pagina)
    {
        $this->load->library('Validacion_negocio');
        $desde=Validacion_negocio::fecha($desde);$hasta=Validacion_negocio::fecha($hasta);
        if ($desde>$hasta) { throw new InvalidArgumentException('Rango de fechas inválido.'); }
        $fin=(new DateTimeImmutable($hasta))->modify('+1 day')->format('Y-m-d');
        $v=array($this->empresa_id(),$desde,$fin);
        $where=' WHERE empresa_id=? AND fecha>=? AND fecha<?';
        $n=$this->consultar('SELECT COUNT(DISTINCT DATE(fecha)) cantidad FROM ventas'.$where,$v)->row_array();
        $paginas=max(1,(int)ceil((int)$n['cantidad']/31));$pagina=max(1,min((int)$pagina,$paginas));
        $filas=$this->consultar('SELECT DATE(fecha) dia,COUNT(*) cantidad,SUM(subtotal) consumo,SUM(propina) propina,SUM(total) total
             FROM ventas'.$where.' GROUP BY DATE(fecha) ORDER BY dia DESC LIMIT 31 OFFSET ?',array_merge($v,array(($pagina-1)*31)))->result_array();
        return array('dias'=>$filas,'pagina_dias'=>$pagina,'paginas_dias'=>$paginas);
    }
    public function buscar($id)
    {
        return $this->consultar('SELECT v.*,u.nombre responsable FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id AND u.empresa_id=v.empresa_id
            WHERE v.empresa_id=? AND v.id=?',array($this->empresa_id(),$id))->row_array();
    }
    public function detalles($pedido)
    {
        return $this->consultar('SELECT producto_nombre,cantidad,precio_unitario,cantidad*precio_unitario subtotal FROM pedido_detalles
             WHERE empresa_id=? AND pedido_id=? ORDER BY id',array($this->empresa_id(),$pedido))->result_array();
    }
}
