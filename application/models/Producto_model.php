<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Producto_model extends MY_Model
{
    public function __construct() { parent::__construct(); $this->load->database(); $this->load->library('Validacion_negocio'); }

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
    public function crear_desde_insumo($insumo_id, $sku, $precio)
{
    try {
        $insumo_id=Validacion_negocio::id($insumo_id,'Insumo');
        $sku=Validacion_negocio::texto($sku,'Código',40);
        $precio=Validacion_negocio::decimal($precio,'Precio');
    } catch (InvalidArgumentException $e) { return $e->getMessage(); }

    $empresa_id = $this->empresa_id();
    $sku = trim($sku);
    $debug_anterior = $this->db->db_debug;
    $this->db->db_debug = FALSE;

    $ejecutar = function ($sql, $valores) {
        $resultado = $this->db->query($sql, $valores);

        if ($resultado === FALSE) {
            throw new RuntimeException('Error de base de datos.');
        }

        return $resultado;
    };

    try {
        if (!$this->db->trans_begin()) {
            throw new RuntimeException('No se pudo iniciar el registro.');
        }

        // Bloquear el insumo durante la creación del vínculo.
        $insumo = $ejecutar(
            'SELECT id, nombre, activo
             FROM insumos
             WHERE id = ? AND empresa_id = ?
             FOR UPDATE',
            array((int) $insumo_id, $empresa_id)
        )->row_array();

        if (!$insumo || (int) $insumo['activo'] !== 1) {
            throw new InvalidArgumentException(
                'El artículo no está disponible en tu empresa.'
            );
        }

        $vinculado = $ejecutar(
            'SELECT id
             FROM productos
             WHERE empresa_id = ? AND insumo_id = ?
             LIMIT 1',
            array($empresa_id, (int) $insumo_id)
        )->row_array();

        if ($vinculado) {
            throw new InvalidArgumentException(
                'Este artículo ya tiene un producto asociado.'
            );
        }

        $codigo_existente = $ejecutar(
            'SELECT id
             FROM productos
             WHERE empresa_id = ? AND sku = ?
             LIMIT 1',
            array($empresa_id, $sku)
        )->row_array();

        if ($codigo_existente) {
            throw new InvalidArgumentException(
                'Ya existe un producto con ese código en tu empresa.'
            );
        }

        $ejecutar(
            'INSERT INTO productos
                (empresa_id, insumo_id, sku, nombre, precio)
             VALUES (?, ?, ?, ?, ?)',
            array(
                $empresa_id,
                (int) $insumo_id,
                $sku,
                $insumo['nombre'],
                $precio
            )
        );

        if (!$this->db->trans_status() || !$this->db->trans_commit()) {
            throw new RuntimeException('No se pudo completar el registro.');
        }

        return NULL;
    } catch (InvalidArgumentException $error) {
        $this->db->trans_rollback();
        return $error->getMessage();
    } catch (Exception $error) {
        $this->db->trans_rollback();

        log_message(
            'error',
            'Fallo al crear un producto asociado al inventario.'
        );

        return 'No se pudo registrar el producto. Verifica que el código no esté repetido e intenta nuevamente.';
    } finally {
        $this->db->db_debug = $debug_anterior;
    }
}
}
