<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Proveedor_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('Validacion_negocio');
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
        try {
            $id = Validacion_negocio::id($id);
            $datos = Validacion_negocio::proveedor($datos);
        } catch (InvalidArgumentException $e) { return FALSE; }

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

    public function insumos_asociados($proveedorId)
    {
        $consulta = $this->db
            ->select('id, nombre, unidad_medida, cantidad_actual, activo')
            ->from('insumos')
            ->where('empresa_id', $this->empresa_id())
            ->where('proveedor_id', (int) $proveedorId)
            ->order_by('nombre', 'ASC')
            ->get();

        if (!$consulta) {
            show_error('No se pudieron consultar los insumos.', 503);
        }

        return $consulta->result_array();
    }

    public function insumos_disponibles()
    {
        $consulta = $this->db
            ->select('id, nombre, unidad_medida')
            ->from('insumos')
            ->where('empresa_id', $this->empresa_id())
            ->where('activo', 1)
            ->where('proveedor_id IS NULL', NULL, FALSE)
            ->order_by('nombre', 'ASC')
            ->get();

        if (!$consulta) {
            show_error('No se pudieron consultar los insumos disponibles.', 503);
        }

        return $consulta->result_array();
    }

    public function asociar_insumo($proveedorId, $insumoId)
    {
        try {
            $proveedorId = Validacion_negocio::id($proveedorId, 'Proveedor');
            $insumoId = Validacion_negocio::id($insumoId, 'Insumo');
        } catch (InvalidArgumentException $e) { return FALSE; }
        $empresaId = $this->empresa_id();

        $this->db->trans_begin();

        $proveedor = $this->db->query(
            'SELECT id
         FROM proveedores
         WHERE id = ? AND empresa_id = ? AND activo = 1
         FOR UPDATE',
            array((int) $proveedorId, $empresaId)
        );

        $insumo = $this->db->query(
            'SELECT id, proveedor_id
         FROM insumos
         WHERE id = ? AND empresa_id = ? AND activo = 1
         FOR UPDATE',
            array((int) $insumoId, $empresaId)
        );

        if (
            !$proveedor ||
            !$proveedor->row_array() ||
            !$insumo
        ) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $fila = $insumo->row_array();

        if (!$fila || $fila['proveedor_id'] !== NULL) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $actualizado = $this->db
            ->where('id', (int) $insumoId)
            ->where('empresa_id', $empresaId)
            ->where('proveedor_id IS NULL', NULL, FALSE)
            ->update('insumos', array(
                'proveedor_id' => (int) $proveedorId
            ));

        if (
            !$actualizado ||
            $this->db->affected_rows() !== 1 ||
            $this->db->trans_status() === FALSE
        ) {
            $this->db->trans_rollback();
            return FALSE;
        }

        return $this->db->trans_commit();
    }

    public function desasociar_insumo($proveedorId, $insumoId)
    {
        try {
            $proveedorId = Validacion_negocio::id($proveedorId, 'Proveedor');
            $insumoId = Validacion_negocio::id($insumoId, 'Insumo');
        } catch (InvalidArgumentException $e) { return FALSE; }
        $empresaId = $this->empresa_id();

        $this->db->trans_begin();

        $consulta = $this->db->query(
            'SELECT id
         FROM insumos
         WHERE id = ?
           AND empresa_id = ?
           AND proveedor_id = ?
         FOR UPDATE',
            array(
                (int) $insumoId,
                $empresaId,
                (int) $proveedorId
            )
        );

        if (!$consulta || !$consulta->row_array()) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $actualizado = $this->db
            ->where('id', (int) $insumoId)
            ->where('empresa_id', $empresaId)
            ->where('proveedor_id', (int) $proveedorId)
            ->update('insumos', array(
                'proveedor_id' => NULL
            ));

        if (
            !$actualizado ||
            $this->db->affected_rows() !== 1 ||
            $this->db->trans_status() === FALSE
        ) {
            $this->db->trans_rollback();
            return FALSE;
        }

        return $this->db->trans_commit();
    }
}
