<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Proveedor_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function buscar($id)
    {
        $consulta = $this->db
            ->where('id', (int) $id)
            ->where('empresa_id', $this->empresa_id())
            ->get('proveedores');

        if (!$consulta) {
            show_error(
                'No se pudo consultar el proveedor.',
                503
            );
        }

        return $consulta->row_array();
    }

    public function listar($busqueda = '')
    {
        $this->db
            ->select('id, nombre, contacto, telefono, correo, direccion, activo')
            ->from('proveedores')
            ->where('empresa_id', $this->empresa_id());

        if ($busqueda !== '') {
            $this->db->like('nombre', $busqueda);
        }

        $consulta = $this->db
            ->order_by('nombre', 'ASC')
            ->get();

        if (!$consulta) {
            show_error('No se pudieron consultar los proveedores.', 503);
        }

        return $consulta->result_array();
    }

    public function actualizar($id, array $datos)
    {
        $id = (int) $id;

        if ($id < 1) {
            return FALSE;
        }

        // Verificar que el proveedor pertenece a la empresa.
        $proveedor = $this->buscar($id);

        if (!$proveedor) {
            return FALSE;
        }

        return $this->db
            ->where('id', $id)
            ->where('empresa_id', $this->empresa_id())
            ->update('proveedores', array(
                'nombre' => $datos['nombre'],
                'contacto' => $datos['contacto'],
                'telefono' => $datos['telefono'],
                'correo' => $datos['correo'],
                'direccion' => $datos['direccion']
            ));
    }
    public function existe_nombre($nombre, $excluirId = NULL)
    {
        $this->db
            ->from('proveedores')
            ->where('empresa_id', $this->empresa_id())
            ->where('nombre', trim($nombre));

        if ($excluirId !== NULL) {
            $this->db->where('id !=', (int) $excluirId);
        }

        return $this->db->count_all_results() > 0;
    }
}
