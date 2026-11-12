<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Indicadores de la empresa autenticada; cada consulta respeta los permisos del módulo. */
class Inicio_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function consultar($sql, $valores)
    {
        $consulta = $this->db->query($sql, $valores);
        if (!$consulta) { throw new RuntimeException('No se pudo consultar el resumen de inicio.'); }
        return $consulta->row_array();
    }

    public function resumen()
    {
        $empresa = $this->empresa_id();
        $ci =& get_instance();
        $usuario = $ci->autenticacion->usuario_actual();
        $resumen = array();

        if ($usuario->puede('ventas.historial')) {
            $hoy = new DateTimeImmutable('today');
            $resumen['ventas'] = $this->consultar(
                'SELECT COUNT(*) cantidad, COALESCE(SUM(subtotal),0) consumo,
                        COALESCE(SUM(propina),0) propina
                 FROM ventas WHERE empresa_id=? AND fecha>=? AND fecha<?',
                array($empresa, $hoy->format('Y-m-d'), $hoy->modify('+1 day')->format('Y-m-d'))
            );
            $inicio = $hoy->modify('-6 days');
            $consulta = $this->db->query(
                'SELECT DATE(fecha) dia, COALESCE(SUM(subtotal),0) consumo
                 FROM ventas WHERE empresa_id=? AND fecha>=? AND fecha<? GROUP BY DATE(fecha)',
                array($empresa, $inicio->format('Y-m-d'), $hoy->modify('+1 day')->format('Y-m-d'))
            );
            if (!$consulta) { throw new RuntimeException('No se pudo consultar la actividad semanal.'); }
            $por_dia = array_column($consulta->result_array(), 'consumo', 'dia');
            $resumen['ultimos_dias'] = array();
            for ($i = 0; $i < 7; $i++) {
                $dia = $inicio->modify('+'.$i.' days')->format('Y-m-d');
                $resumen['ultimos_dias'][] = array('dia'=>$dia, 'consumo'=>$por_dia[$dia] ?? '0.00');
            }
        }
        if ($usuario->puede('pedidos.actualizar_cocina')) {
            $fila = $this->consultar(
                "SELECT COUNT(*) cantidad FROM pedidos
                 WHERE empresa_id=? AND estado IN ('pendiente','en_preparacion','listo')",
                array($empresa)
            );
            $resumen['cocina'] = (int) $fila['cantidad'];
        }
        if ($usuario->puede('ventas.ver') && $usuario->puede('pedidos.ver')) {
            $fila = $this->consultar(
                "SELECT COUNT(*) cantidad FROM pedidos WHERE empresa_id=?
                 AND estado IN ('pendiente','en_preparacion','listo','entregado')
                 AND estado_pago IN ('pendiente','parcial')",
                array($empresa)
            );
            $resumen['por_cobrar'] = (int) $fila['cantidad'];
        }
        if ($usuario->puede('inventario.ver')) {
            $fila = $this->consultar(
                'SELECT COUNT(*) cantidad FROM insumos
                 WHERE empresa_id=? AND activo=1 AND cantidad_actual<=stock_minimo',
                array($empresa)
            );
            $resumen['reponer'] = (int) $fila['cantidad'];
        }
        return $resumen;
    }
}
