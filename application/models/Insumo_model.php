<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Insumo_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function listar($filtros = array())
    {
        $this->db
            ->select('id, nombre, cantidad_actual, stock_minimo, unidad_medida, fecha_vencimiento, activo')
            ->from('insumos')
            ->where('empresa_id', $this->empresa_id())
            ->where(
                'activo',
                isset($filtros['activo']) &&
                    $filtros['activo'] === 'inactivos'
                    ? 0 : 1
            );

        if (!empty($filtros['busqueda'])) {
            $this->db->like('nombre', $filtros['busqueda']);
        }

        if (!empty($filtros['estado'])) {

            if ($filtros['estado'] === 'bajo') {
                $this->db->where(
                    'cantidad_actual <= stock_minimo',
                    NULL,
                    FALSE
                );
            }

            if ($filtros['estado'] === 'normal') {
                $this->db->where(
                    'cantidad_actual > stock_minimo',
                    NULL,
                    FALSE
                );
            }
        }

        if (!empty($filtros['unidad'])) {
            $this->db->where(
                'unidad_medida',
                $filtros['unidad']
            );
        }

        $orden = isset($filtros['orden'])
            ? $filtros['orden']
            : 'nombre_asc';

        switch ($orden) {

            case 'nombre_desc':
                $this->db->order_by('nombre', 'DESC');
                break;

            case 'cantidad_asc':
                $this->db->order_by('cantidad_actual', 'ASC');
                $this->db->order_by('nombre', 'ASC');
                break;

            case 'cantidad_desc':
                $this->db->order_by('cantidad_actual', 'DESC');
                $this->db->order_by('nombre', 'ASC');
                break;

            case 'minimo_asc':
                $this->db->order_by('stock_minimo', 'ASC');
                $this->db->order_by('nombre', 'ASC');
                break;

            case 'minimo_desc':
                $this->db->order_by('stock_minimo', 'DESC');
                $this->db->order_by('nombre', 'ASC');
                break;

            case 'stock_bajo':
                $this->db->order_by(
                    '(cantidad_actual <= stock_minimo)',
                    'DESC',
                    FALSE
                );
                $this->db->order_by('nombre', 'ASC');
                break;

            case 'nombre_asc':
            default:
                $this->db->order_by('nombre', 'ASC');
                break;
        }

        if (isset($filtros['limite'])) {
            $this->db->limit(
                (int) $filtros['limite'],
                (int) $filtros['offset']
            );
        }

        $consulta = $this->db->get();

        if (!$consulta) {
            show_error(
                'No se pudo consultar el inventario.',
                503
            );
        }

        return $consulta->result_array();
    }

    public function resumen()
    {
        $consulta = $this->db->select("COUNT(*) AS total, COALESCE(SUM(CASE WHEN cantidad_actual > stock_minimo THEN 1 ELSE 0 END), 0) AS normales, COALESCE(SUM(CASE WHEN cantidad_actual <= stock_minimo THEN 1 ELSE 0 END), 0) AS bajos", FALSE)
            ->from('insumos')->where('empresa_id', $this->empresa_id())->where('activo', 1)->get();

        if (!$consulta) {
            show_error('No se pudo consultar el resumen del inventario.', 503);
        }

        return $consulta->row_array();
    }

    public function buscar($id)
    {
        $q = $this->db
            ->select('id, nombre, cantidad_actual, stock_minimo, unidad_medida, activo')
            ->from('insumos')
            ->where('empresa_id', $this->empresa_id())
            ->where('id', (int) $id)
            ->where('activo', 1)
            ->limit(1)
            ->get();

        if (!$q) {
            show_error('No se pudo consultar el insumo.', 503);
        }

        return $q->row_array() ?: NULL;
    }

    public function crear(array $datos, $usuarioId)
    {
        $empresaId = $this->empresa_id();

        $this->db->trans_begin();

        $guardado = $this->db->insert(
            'insumos',
            array(
                'empresa_id' => $empresaId,
                'nombre' => $datos['nombre'],
                'cantidad_actual' => $datos['cantidad_actual'],
                'stock_minimo' => $datos['stock_minimo'],
                'unidad_medida' => $datos['unidad_medida'],
                'fecha_vencimiento' =>
                $datos['fecha_vencimiento'],
                'activo' => 1
            )
        );

        if (!$guardado) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $insumoId = (int) $this->db->insert_id();

        if ((float) $datos['cantidad_actual'] > 0) {

            $guardado = $this->db->insert(
                'movimientos_inventario',
                array(
                    'empresa_id' => $empresaId,
                    'insumo_id' => $insumoId,
                    'usuario_id' => (int) $usuarioId,
                    'tipo' => 'entrada',
                    'cantidad' => $datos['cantidad_actual'],
                    'cantidad_anterior' => 0,
                    'cantidad_nueva' =>
                    $datos['cantidad_actual'],
                    'motivo' => 'Existencia inicial'
                )
            );
        }

        if (
            !$guardado ||
            $this->db->trans_status() === FALSE
        ) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();

        return TRUE;
    }

    public function actualizar($id, array $datos)
    {
        return $this->db
            ->where('id', (int) $id)
            ->where('empresa_id', $this->empresa_id())
            ->where('activo', 1)
            ->update('insumos', array(
                'nombre' => $datos['nombre'],
                'stock_minimo' => $datos['stock_minimo'],
                'unidad_medida' => $datos['unidad_medida'],
                'fecha_vencimiento' =>
                $datos['fecha_vencimiento']
            ));
    }

    public function buscar_incluyendo_inactivos($id)
    {
        $consulta = $this->db->from('insumos')
            ->where('id', (int) $id)
            ->where('empresa_id', $this->empresa_id())
            ->limit(1)
            ->get();

        return $consulta ? ($consulta->row_array() ?: NULL) : NULL;
    }

    public function existe_nombre($nombre, $excluirId = NULL)
    {
        $this->db->from('insumos')
            ->where('empresa_id', $this->empresa_id())
            ->where('nombre', trim($nombre));

        if ($excluirId !== NULL) {
            $this->db->where('id !=', (int) $excluirId);
        }

        return $this->db->count_all_results() > 0;
    }

    public function cambiar_estado($id, $activo)
    {
        return $this->db->where('empresa_id', $this->empresa_id())
            ->where('id', (int) $id)
            ->update('insumos', array('activo' => $activo ? 1 : 0));
    }

    public function contar($filtros = array())
    {
        $this->db
            ->from('insumos')
            ->where('empresa_id', $this->empresa_id())
            ->where(
                'activo',
                isset($filtros['activo']) &&
                    $filtros['activo'] === 'inactivos'
                    ? 0 : 1
            );

        if (!empty($filtros['busqueda'])) {
            $this->db->like('nombre', $filtros['busqueda']);
        }

        if (!empty($filtros['unidad'])) {
            $this->db->where('unidad_medida', $filtros['unidad']);
        }

        if (!empty($filtros['estado'])) {
            if ($filtros['estado'] === 'bajo') {
                $this->db->where('cantidad_actual <= stock_minimo', NULL, FALSE);
            } elseif ($filtros['estado'] === 'normal') {
                $this->db->where('cantidad_actual > stock_minimo', NULL, FALSE);
            }
        }

        return $this->db->count_all_results();
    }
}
