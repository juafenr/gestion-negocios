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
            ->select('id, nombre, cantidad_actual, stock_minimo, unidad_medida, activo')
            ->from('insumos')
            ->where('empresa_id', $this->empresa_id())
            ->where('activo', 1);

        // Búsqueda por nombre
        if (!empty($filtros['busqueda'])) {
            $this->db->like('nombre', $filtros['busqueda']);
        }

        // Filtro por estado
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

        // Filtro por unidad
        if (!empty($filtros['unidad'])) {
            $this->db->where(
                'unidad_medida',
                $filtros['unidad']
            );
        }

        // Ordenamiento
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
                // Priorizar insumos con stock bajo
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

    public function crear(array $datos)
    {
        return $this->db->insert('insumos', array(
            'empresa_id' => $this->empresa_id(),
            'nombre' => $datos['nombre'],
            'cantidad_actual' => $datos['cantidad_actual'],
            'stock_minimo' => $datos['stock_minimo'],
            'unidad_medida' => $datos['unidad_medida'],
            'activo' => 1
        ));
    }

    public function actualizar($id, array $datos)
    {
        return $this->db->where('empresa_id', $this->empresa_id())
            ->where('id', (int) $id)
            ->update('insumos', array(
                'nombre' => $datos['nombre'],
                'cantidad_actual' => $datos['cantidad_actual'],
                'stock_minimo' => $datos['stock_minimo'],
                'unidad_medida' => $datos['unidad_medida']
            ));
    }
}
