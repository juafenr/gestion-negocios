<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Lecturas agregadas: empresa autenticada y permisos antes de consultar cada módulo. */
class Estadistica_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public static function dias_validos($valor)
    {
        return is_string($valor) && in_array($valor, array('7', '30', '90'), TRUE) ? (int) $valor : 30;
    }

    private function filas($sql, $parametros)
    {
        $resultado = $this->db->query($sql, $parametros);
        if (!$resultado) { throw new RuntimeException('No se pudieron consultar las estadísticas.'); }
        return $resultado->result_array();
    }

    public function panel($dias)
    {
        if (!in_array($dias, array(7, 30, 90), TRUE)) { throw new InvalidArgumentException('Período inválido.'); }
        $empresa = $this->empresa_id();
        $ci =& get_instance();
        $usuario = $ci->autenticacion->usuario_actual();
        $hoy = new DateTimeImmutable('today');
        $desde = $hoy->modify('-'.($dias - 1).' days');
        // Intervalo semiabierto: incluye hoy completo, excluye mañana a las 00:00.
        $parametros = array($empresa, $desde->format('Y-m-d'), $hoy->modify('+1 day')->format('Y-m-d'));
        $panel = array('dias'=>$dias, 'desde'=>$desde->format('d/m/Y'), 'hasta'=>$hoy->format('d/m/Y'));

        if ($usuario->puede('ventas.historial')) {
            // ventas contiene una fila por pedido completamente cobrado. No sumar pagos aquí.
            $totales = $this->filas('SELECT COUNT(*) cantidad, COALESCE(SUM(subtotal),0) consumo,
                COALESCE(SUM(propina),0) propina,
                CAST(ROUND(COALESCE(AVG(subtotal),0),2) AS DECIMAL(20,2)) promedio
                FROM ventas WHERE empresa_id=? AND fecha>=? AND fecha<?', $parametros);
            $panel['ventas'] = $totales[0];
            $filas = $this->filas('SELECT DATE(fecha) dia, SUM(subtotal) consumo, COUNT(*) cantidad
                FROM ventas WHERE empresa_id=? AND fecha>=? AND fecha<?
                GROUP BY DATE(fecha) ORDER BY dia', $parametros);
            $por_dia = array_column($filas, NULL, 'dia');
            $panel['serie'] = array();
            for ($i=0; $i<$dias; $i++) {
                $dia = $desde->modify('+'.$i.' days')->format('Y-m-d');
                $panel['serie'][] = $por_dia[$dia] ?? array('dia'=>$dia, 'consumo'=>'0.00', 'cantidad'=>0);
            }
            $panel['productos'] = $this->filas('SELECT pr.nombre, SUM(d.cantidad) cantidad
                FROM ventas v
                INNER JOIN pedido_detalles d ON d.pedido_id=v.pedido_id AND d.empresa_id=v.empresa_id
                INNER JOIN productos pr ON pr.id=d.producto_id AND pr.empresa_id=d.empresa_id
                WHERE v.empresa_id=? AND v.fecha>=? AND v.fecha<?
                GROUP BY pr.id, pr.nombre ORDER BY cantidad DESC, pr.id ASC LIMIT 5', $parametros);
        }
        if ($usuario->puede('ventas.historial')) {
            // LEFT JOIN conserva productos disponibles que no tuvieron ninguna venta.
            $panel['menos_vendidos'] = $this->filas('SELECT pr.nombre, COALESCE(vendido.cantidad,0) cantidad
                FROM productos pr
                LEFT JOIN (
                    SELECT d.producto_id, d.empresa_id, SUM(d.cantidad) cantidad
                    FROM ventas v
                    INNER JOIN pedido_detalles d ON d.pedido_id=v.pedido_id AND d.empresa_id=v.empresa_id
                    WHERE v.empresa_id=? AND v.fecha>=? AND v.fecha<?
                    GROUP BY d.producto_id, d.empresa_id
                ) vendido ON vendido.producto_id=pr.id AND vendido.empresa_id=pr.empresa_id
                WHERE pr.empresa_id=? AND pr.disponible=1
                ORDER BY cantidad ASC, pr.id ASC LIMIT 5', array_merge($parametros, array($empresa)));
        }
        if ($usuario->puede('reportes.ver') && $usuario->puede('pedidos.ver')) {
            // Despachar = registrar el paso a entregado, no crear el pedido ni cobrarlo.
            // Un pedido cuenta una sola vez: se usa su primer evento de entrega.
            $panel['despachos'] = $this->filas("SELECT u.nombre, COUNT(*) cantidad
                FROM pedido_eventos e
                INNER JOIN usuarios u ON u.id=e.usuario_id AND u.empresa_id=e.empresa_id
                INNER JOIN pedidos p ON p.id=e.pedido_id AND p.empresa_id=e.empresa_id
                WHERE e.empresa_id=? AND e.creado_en>=? AND e.creado_en<? AND e.estado_nuevo='entregado'
                AND NOT EXISTS (
                    SELECT 1 FROM pedido_eventos anterior
                    WHERE anterior.empresa_id=e.empresa_id AND anterior.pedido_id=e.pedido_id
                    AND anterior.estado_nuevo='entregado'
                    AND (anterior.creado_en<e.creado_en OR (anterior.creado_en=e.creado_en AND anterior.id<e.id))
                )
                GROUP BY u.id, u.nombre ORDER BY cantidad DESC, u.id ASC LIMIT 5", $parametros);
        }
        if ($usuario->puede('ventas.ver') && $usuario->puede('pedidos.ver')) {
            // Incluye abonos y propinas. Efectivo aplicado, nunca recibido (que contiene el cambio).
            $filas = $this->filas('SELECT COALESCE(SUM(efectivo),0) efectivo,
                COALESCE(SUM(credito),0) credito, COALESCE(SUM(debito),0) debito,
                COALESCE(SUM(efectivo+credito+debito),0) total
                FROM pagos WHERE empresa_id=? AND creado_en>=? AND creado_en<?', $parametros);
            $panel['cobros'] = $filas[0];
        }
        if ($usuario->puede('inventario.ver')) {
            $filas = $this->filas('SELECT COUNT(*) total,
                COALESCE(SUM(CASE WHEN cantidad_actual<=0 THEN 1 ELSE 0 END),0) agotado,
                COALESCE(SUM(CASE WHEN cantidad_actual>0 AND cantidad_actual<=stock_minimo THEN 1 ELSE 0 END),0) bajo,
                COALESCE(SUM(CASE WHEN cantidad_actual>stock_minimo THEN 1 ELSE 0 END),0) normal
                FROM insumos WHERE empresa_id=? AND activo=1', array($empresa));
            $panel['inventario'] = $filas[0];
        }
        if ($usuario->puede('pedidos.ver')) {
            // Carga de trabajo actual: no mezcla borradores, entregados ni cancelaciones.
            $filas = $this->filas("SELECT estado, COUNT(*) cantidad FROM pedidos
                WHERE empresa_id=? AND estado IN ('pendiente','en_preparacion','listo') GROUP BY estado", array($empresa));
            $por_estado = array_column($filas, 'cantidad', 'estado');
            $panel['pedidos'] = array();
            foreach (array('pendiente'=>'Pendientes', 'en_preparacion'=>'En preparación', 'listo'=>'Listos') as $codigo=>$nombre) {
                $panel['pedidos'][] = array('nombre'=>$nombre, 'cantidad'=>(int) ($por_estado[$codigo] ?? 0));
            }
        }
        return $panel;
    }
}
