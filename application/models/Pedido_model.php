<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pedido_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function crear($referencia, $usuario_id)
    {
        $empresa_id = $this->empresa_id();

        $consulta = $this->db
            ->where('id', $usuario_id)
            ->where('empresa_id', $empresa_id)
            ->where('activo', 1)
            ->get('usuarios');

        if (!$consulta) {
            show_error('No se pudo verificar el usuario.', 503);
        }

        if (!$consulta->row_array()) {
            show_error('Usuario no autorizado.', 403);
        }

        $guardado = $this->db->insert('pedidos', array(
            'empresa_id' => $empresa_id,
            'usuario_id' => $usuario_id,
            'referencia' => $referencia,
            'estado' => 'borrador',
            'estado_pago' => 'pendiente',
            'total' => '0.00'
        ));

        if (!$guardado) {
            show_error('No se pudo guardar el pedido.', 503);
        }

        return (int) $this->db->insert_id();
    }

    public function listar()
    {
        $consulta = $this->db
            ->where('empresa_id', $this->empresa_id())
            ->order_by('id', 'DESC')
            ->limit(50)
            ->get('pedidos');

        if (!$consulta) {
            show_error('No se pudieron consultar los pedidos.', 503);
        }

        return $consulta->result_array();
    }
        
    public function buscar($id)
    {
        $consulta = $this->db
            ->where('id', (int) $id)
            ->where('empresa_id', $this->empresa_id())
            ->get('pedidos');

        if (!$consulta) {
            show_error('No se pudo consultar el pedido.', 503);
        }

        return $consulta->row_array();
    }

    public function detalles($pedido_id)
    {
        $consulta = $this->db
            ->select('id, producto_id, producto_nombre, cantidad, precio_unitario')
            ->where('pedido_id', (int) $pedido_id)
            ->where('empresa_id', $this->empresa_id())
            ->order_by('id', 'ASC')
            ->get('pedido_detalles');

        if (!$consulta) {
            show_error('No se pudieron consultar los productos del pedido.', 503);
        }

        return $consulta->result_array();
    }
}