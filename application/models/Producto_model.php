<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Producto_model extends MY_Model
{
    public function __construct() { parent::__construct(); $this->load->database(); }

    public function listar()
    {
        $q = $this->db->select('id, nombre, sku, precio')->from('productos')
            ->where('empresa_id', $this->empresa_id())->order_by('id')->get();
        if (!$q) { show_error('No se pudieron consultar los productos.', 503); }
        return $q->result_array();
    }
    public function buscar($id)
    {
        $q = $this->db->select('id, nombre, sku, precio')->from('productos')
            ->where('empresa_id', $this->empresa_id())->where('id', (int) $id)->limit(1)->get();
        if (!$q) { show_error('No se pudo consultar el producto.', 503); }
        return $q->row_array() ?: NULL;
    }
}
