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

        public function agregar_producto($pedido_id, $producto_id, $cantidad)
    {
        if ($cantidad < 1 || $cantidad > 999) {
            return 'La cantidad debe estar entre 1 y 999.';
        }

        $empresa_id = $this->empresa_id();
        $debug_anterior = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        // Ejecuta consultas y detecta fallos para deshacer la operación.
        $ejecutar = function ($sql, $valores) {
            $resultado = $this->db->query($sql, $valores);

            if ($resultado === FALSE) {
                throw new RuntimeException('Error de base de datos.');
            }

            return $resultado;
        };

        try {
            if (!$this->db->trans_begin()) {
                throw new RuntimeException('No se pudo iniciar la operación.');
            }

            // Bloquear el pedido mientras se modifican sus productos.
            $pedido = $ejecutar(
                'SELECT id, estado
                 FROM pedidos
                 WHERE id = ? AND empresa_id = ?
                 FOR UPDATE',
                array($pedido_id, $empresa_id)
            )->row_array();

            if (!$pedido) {
                throw new InvalidArgumentException('Pedido no encontrado.');
            }

            if ($pedido['estado'] !== 'borrador') {
                throw new InvalidArgumentException(
                    'Solo puedes modificar pedidos en borrador.'
                );
            }

            $producto = $ejecutar(
                'SELECT id, nombre, precio
                 FROM productos
                 WHERE id = ? AND empresa_id = ?',
                array($producto_id, $empresa_id)
            )->row_array();

            if (!$producto) {
                throw new InvalidArgumentException(
                    'El producto no está disponible para tu empresa.'
                );
            }

            $detalle = $ejecutar(
                'SELECT id, cantidad
                 FROM pedido_detalles
                 WHERE pedido_id = ?
                   AND empresa_id = ?
                   AND producto_id = ?',
                array($pedido_id, $empresa_id, $producto_id)
            )->row_array();

            if ($detalle) {
                $nueva_cantidad = (int) $detalle['cantidad'] + $cantidad;

                if ($nueva_cantidad > 999) {
                    throw new InvalidArgumentException(
                        'El máximo por producto es de 999 unidades.'
                    );
                }

                // Mantener el precio con el que se agregó originalmente.
                $ejecutar(
                    'UPDATE pedido_detalles
                     SET cantidad = ?
                     WHERE id = ? AND empresa_id = ?',
                    array($nueva_cantidad, $detalle['id'], $empresa_id)
                );
            } else {
                $ejecutar(
                    'INSERT INTO pedido_detalles
                     (empresa_id, pedido_id, producto_id,
                      producto_nombre, cantidad, precio_unitario)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    array(
                        $empresa_id,
                        $pedido_id,
                        $producto_id,
                        $producto['nombre'],
                        $cantidad,
                        $producto['precio']
                    )
                );
            }

            // Calcular con DECIMAL en la base de datos.
            $resumen = $ejecutar(
                'SELECT
                    COALESCE(SUM(cantidad * precio_unitario), 0) AS total,
                    COALESCE(SUM(cantidad * precio_unitario), 0)
                        > 9999999999.99 AS excede
                 FROM pedido_detalles
                 WHERE pedido_id = ? AND empresa_id = ?',
                array($pedido_id, $empresa_id)
            )->row_array();

            if ($resumen['excede']) {
                throw new InvalidArgumentException(
                    'El total supera el límite permitido para un pedido.'
                );
            }

            $ejecutar(
                'UPDATE pedidos
                 SET total = ?
                 WHERE id = ? AND empresa_id = ?',
                array($resumen['total'], $pedido_id, $empresa_id)
            );

            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('No se pudo completar la operación.');
            }

            return NULL;
        } catch (InvalidArgumentException $error) {
            $this->db->trans_rollback();
            return $error->getMessage();
        } catch (Exception $error) {
            $this->db->trans_rollback();
            log_message('error', 'Fallo al agregar un producto al pedido.');

            return 'No se pudo guardar el producto. Intenta nuevamente.';
        } finally {
            $this->db->db_debug = $debug_anterior;
        }
    }

        public function modificar_detalle($pedido_id, $detalle_id, $cantidad)
    {
        // NULL significa quitar el producto del pedido.
        if ($cantidad !== NULL && ($cantidad < 1 || $cantidad > 999)) {
            return 'La cantidad debe estar entre 1 y 999.';
        }

        $empresa_id = $this->empresa_id();
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
                throw new RuntimeException('No se pudo iniciar la operación.');
            }

            $pedido = $ejecutar(
                'SELECT id, estado
                 FROM pedidos
                 WHERE id = ? AND empresa_id = ?
                 FOR UPDATE',
                array($pedido_id, $empresa_id)
            )->row_array();

            if (!$pedido) {
                throw new InvalidArgumentException('Pedido no encontrado.');
            }

            if ($pedido['estado'] !== 'borrador') {
                throw new InvalidArgumentException(
                    'Solo puedes modificar pedidos en borrador.'
                );
            }

            $detalle = $ejecutar(
                'SELECT id
                 FROM pedido_detalles
                 WHERE id = ?
                   AND pedido_id = ?
                   AND empresa_id = ?',
                array($detalle_id, $pedido_id, $empresa_id)
            )->row_array();

            if (!$detalle) {
                throw new InvalidArgumentException(
                    'El producto no se encuentra en este pedido.'
                );
            }

            if ($cantidad === NULL) {
                $ejecutar(
                    'DELETE FROM pedido_detalles
                     WHERE id = ?
                       AND pedido_id = ?
                       AND empresa_id = ?',
                    array($detalle_id, $pedido_id, $empresa_id)
                );
            } else {
                $ejecutar(
                    'UPDATE pedido_detalles
                     SET cantidad = ?
                     WHERE id = ?
                       AND pedido_id = ?
                       AND empresa_id = ?',
                    array($cantidad, $detalle_id, $pedido_id, $empresa_id)
                );
            }

            $resumen = $ejecutar(
                'SELECT
                    COALESCE(SUM(cantidad * precio_unitario), 0) AS total,
                    COALESCE(SUM(cantidad * precio_unitario), 0)
                        > 9999999999.99 AS excede
                 FROM pedido_detalles
                 WHERE pedido_id = ? AND empresa_id = ?',
                array($pedido_id, $empresa_id)
            )->row_array();

            if ($resumen['excede']) {
                throw new InvalidArgumentException(
                    'El total supera el límite permitido para un pedido.'
                );
            }

            $ejecutar(
                'UPDATE pedidos
                 SET total = ?
                 WHERE id = ? AND empresa_id = ?',
                array($resumen['total'], $pedido_id, $empresa_id)
            );

            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('No se pudo completar la operación.');
            }

            return NULL;
        } catch (InvalidArgumentException $error) {
            $this->db->trans_rollback();
            return $error->getMessage();
        } catch (Exception $error) {
            $this->db->trans_rollback();
            log_message('error', 'Fallo al modificar el detalle del pedido.');

            return 'No se pudo modificar el pedido. Intenta nuevamente.';
        } finally {
            $this->db->db_debug = $debug_anterior;
        }
    }
}