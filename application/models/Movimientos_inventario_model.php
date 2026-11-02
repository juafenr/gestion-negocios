
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Movimientos_inventario_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(array('Validacion_negocio', 'Dinero'));
    }

    public function registrar($insumoId, $tipo, $cantidad, $motivo, $usuarioId)
    {
        $empresaId = $this->empresa_id();

        if (!in_array($tipo, array('entrada', 'salida', 'ajuste'), TRUE)) {
            return FALSE;
        }

        try {
            $insumoId = Validacion_negocio::id($insumoId, 'Insumo');
            $usuarioId = Validacion_negocio::id($usuarioId, 'Usuario');
            $motivo = Validacion_negocio::texto($motivo, 'Motivo', 255);
            $cantidad = Validacion_negocio::decimal(
                $cantidad,
                'Cantidad',
                $tipo === 'ajuste'
            );
        } catch (InvalidArgumentException $e) {
            return FALSE;
        }

        $unidades = Dinero::centavos($cantidad);

        $this->db->trans_begin();

        $consulta = $this->db->query(
            'SELECT id, cantidad_actual
             FROM insumos
             WHERE id = ?
               AND empresa_id = ?
               AND activo = 1
             FOR UPDATE',
            array($insumoId, $empresaId)
        );

        $insumo = $consulta ? $consulta->row_array() : NULL;

        if (!$insumo) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $anterior = Dinero::centavos($insumo['cantidad_actual']);

        switch ($tipo) {
            case 'entrada':
                $nueva = $anterior + $unidades;
                break;

            case 'salida':
                $nueva = $anterior - $unidades;
                break;

            case 'ajuste':
                $nueva = $unidades;
                break;
        }

        if ($nueva < 0 || $nueva > 9999999999) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $actualizado = $this->db
            ->where('id', $insumoId)
            ->where('empresa_id', $empresaId)
            ->update('insumos', array(
                'cantidad_actual' => Dinero::decimal($nueva)
            ));

        if (!$actualizado) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $registrado = $this->db->insert(
            'movimientos_inventario',
            array(
                'empresa_id' => $empresaId,
                'insumo_id' => $insumoId,
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'cantidad_anterior' => Dinero::decimal($anterior),
                'cantidad_nueva' => Dinero::decimal($nueva),
                'motivo' => $motivo
            )
        );

        if (!$registrado || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }

        if (!$this->db->trans_commit()) {
            return FALSE;
        }

        return TRUE;
    }

    public function historial($insumoId, $limite = 20, $offset = 0)
    {
        $limite = max(1, min(100, (int) $limite));
        $offset = max(0, (int) $offset);

        $consulta = $this->db
            ->select('m.*, u.nombre AS responsable')
            ->from('movimientos_inventario m')
            ->join(
                'usuarios u',
                'u.id = m.usuario_id
                 AND u.empresa_id = m.empresa_id'
            )
            ->where('m.empresa_id', $this->empresa_id())
            ->where('m.insumo_id', (int) $insumoId)
            ->order_by('m.id', 'DESC')
            ->limit($limite, $offset)
            ->get();

        if (!$consulta) {
            show_error(
                'No se pudo obtener el historial de movimientos.',
                503
            );
        }

        return $consulta->result_array();
    }

    public function contar_historial($insumoId)
    {
        $consulta = $this->db
            ->select('COUNT(*) AS total', FALSE)
            ->from('movimientos_inventario')
            ->where('empresa_id', $this->empresa_id())
            ->where('insumo_id', (int) $insumoId)
            ->get();

        if (!$consulta) {
            show_error(
                'No se pudo contar el historial de movimientos.',
                503
            );
        }

        $resultado = $consulta->row_array();

        return (int) $resultado['total'];
    }
}
