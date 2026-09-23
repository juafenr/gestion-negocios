<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Insumo_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function listar()
    {
        $q = $this->db->select('id, nombre, cantidad_actual, stock_minimo, unidad_medida, activo')
            ->from('insumos')
            ->where('empresa_id', $this->empresa_id())
            ->where('activo', 1)
            ->order_by('nombre', 'ASC')
            ->get();

        if (!$q) {
            show_error('No se pudo consultar el inventario', 503);
        }

        return $q->result_array();
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
