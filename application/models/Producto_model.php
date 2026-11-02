<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Producto_model extends MY_Model
{
    public function __construct() { parent::__construct(); $this->load->database(); $this->load->library('Validacion_negocio'); }

    /**
     * Productos que pueden seleccionarse en un pedido.
     */
    public function listar()
    {
        $consulta = $this->db
            ->select(
                'p.id, p.nombre, p.sku, p.precio,
                p.descripcion, p.disponible, p.insumo_id'
            )
            ->from('productos p')
            ->join(
                'insumos i',
                'i.id = p.insumo_id AND i.empresa_id = p.empresa_id',
                'left'
            )
            ->where('p.empresa_id', $this->empresa_id())
            ->where('p.disponible', 1)
            ->group_start()
                ->where('p.insumo_id IS NULL', NULL, FALSE)
                ->or_where('i.activo', 1)
            ->group_end()
            ->order_by('p.nombre', 'ASC')
            ->order_by('p.id', 'ASC')
            ->get();

        if (!$consulta) {
            show_error('No se pudieron consultar los productos.', 503);
            return array();
        }

        return $consulta->result_array();
    }

    /**
     * Catálogo administrativo: incluye productos disponibles
     * y no disponibles, según los filtros.
     */
    public function listar_catalogo($busqueda = '', $disponibilidad = '')
    {
        if (!is_string($busqueda)) {
            $busqueda = '';
        }

        $busqueda = mb_substr(trim($busqueda), 0, 150, 'UTF-8');

        $this->db
            ->select(
                'id, nombre, sku, precio,
                descripcion, disponible, insumo_id'
            )
            ->from('productos')
            ->where('empresa_id', $this->empresa_id());

        if ($busqueda !== '') {
            // Agrupar los OR mantiene la separación por empresa.
            $this->db
                ->group_start()
                    ->like('nombre', $busqueda)
                    ->or_like('sku', $busqueda)
                    ->or_like('descripcion', $busqueda)
                ->group_end();
        }

        if (in_array($disponibilidad, array('0', '1'), TRUE)) {
            $this->db->where(
                'disponible',
                (int) $disponibilidad
            );
        }

        $consulta = $this->db
            ->order_by('nombre', 'ASC')
            ->order_by('id', 'ASC')
            ->get();

        if (!$consulta) {
            show_error('No se pudo consultar el catálogo.', 503);
            return array();
        }

        return $consulta->result_array();
    }

    /**
     * Consulta un producto de la empresa, aunque no esté disponible,
     * para permitir ver su detalle y editarlo.
     */
    public function buscar($id)
    {
        $consulta = $this->db
            ->select(
                'id, nombre, sku, precio,
                descripcion, disponible, insumo_id'
            )
            ->from('productos')
            ->where('empresa_id', $this->empresa_id())
            ->where('id', (int) $id)
            ->limit(1)
            ->get();

        if (!$consulta) {
            show_error('No se pudo consultar el producto.', 503);
            return NULL;
        }

        return $consulta->row_array() ?: NULL;
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
    public function guardar_catalogo(array $datos, $id = NULL)
    {
        // Validar antes de comenzar la transacción.
        try {
            if ($id !== NULL) {
                $id = Validacion_negocio::id($id, 'Producto');
            }

            $nombre = Validacion_negocio::texto(
                $datos['nombre'] ?? NULL,
                'Nombre',
                150
            );

            $sku = Validacion_negocio::texto(
                $datos['sku'] ?? NULL,
                'Código',
                40
            );

            $descripcion = Validacion_negocio::texto(
                $datos['descripcion'] ?? '',
                'Descripción',
                1000,
                FALSE
            );

            $precio = Validacion_negocio::decimal(
                $datos['precio'] ?? NULL,
                'Precio'
            );

            $disponible = $datos['disponible'] ?? NULL;

            if (!in_array($disponible, array('0', '1'), TRUE)) {
                throw new InvalidArgumentException(
                    'Selecciona la disponibilidad del producto.'
                );
            }
        } catch (InvalidArgumentException $error) {
            return array(
                'id' => NULL,
                'error' => $error->getMessage()
            );
        }

        // Obtener al responsable desde la sesión.
        $ci =& get_instance();
        $ci->load->library('Autenticacion');
        $usuario = $ci->autenticacion->usuario_actual();

        if (!$usuario || !$usuario->puede('productos.gestionar')) {
            return array(
                'id' => NULL,
                'error' => 'No tienes permiso para gestionar productos.'
            );
        }

        $empresa_id = $this->empresa_id();
        $debug_anterior = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $iniciada = FALSE;

        $ejecutar = function ($sql, $valores) {
            $resultado = $this->db->query($sql, $valores);

            if ($resultado === FALSE) {
                throw new RuntimeException(
                    'No se pudo completar la consulta.'
                );
            }

            return $resultado;
        };

        try {
            if (!$this->db->trans_begin()) {
                throw new RuntimeException(
                    'No se pudo iniciar la operación.'
                );
            }

            $iniciada = TRUE;

            // Revalidar usuario, empresa y permiso.
            $autorizado = $ejecutar(
                'SELECT u.id
                FROM usuarios u
                JOIN empresas e ON e.id = u.empresa_id
                JOIN rol_permisos rp ON rp.rol_id = u.rol_id
                JOIN permisos p ON p.id = rp.permiso_id
                WHERE u.id = ?
                AND u.empresa_id = ?
                AND u.activo = 1
                AND e.activa = 1
                AND p.codigo = ?
                LIMIT 1',
                array(
                    $usuario->id(),
                    $empresa_id,
                    'productos.gestionar'
                )
            )->row_array();

            if (!$autorizado) {
                throw new InvalidArgumentException(
                    'No tienes permiso para gestionar productos.'
                );
            }

            $anterior = NULL;

            if ($id !== NULL) {
                // Bloquear el producto mientras se edita.
                $anterior = $ejecutar(
                    'SELECT id, precio
                    FROM productos
                    WHERE id = ? AND empresa_id = ?
                    FOR UPDATE',
                    array($id, $empresa_id)
                )->row_array();

                if (!$anterior) {
                    throw new InvalidArgumentException(
                        'Producto no encontrado.'
                    );
                }
            }

            $sql_codigo =
                'SELECT id
                FROM productos
                WHERE empresa_id = ? AND sku = ?';

            $valores_codigo = array($empresa_id, $sku);

            if ($id !== NULL) {
                $sql_codigo .= ' AND id <> ?';
                $valores_codigo[] = $id;
            }

            $sql_codigo .= ' LIMIT 1';

            $repetido = $ejecutar(
                $sql_codigo,
                $valores_codigo
            )->row_array();

            if ($repetido) {
                throw new InvalidArgumentException(
                    'Ya existe un producto con ese código en tu empresa.'
                );
            }

            if ($id === NULL) {
                // Un producto general puede crearse sin insumo asociado.
                $ejecutar(
                    'INSERT INTO productos
                        (empresa_id, sku, nombre, descripcion,
                        precio, disponible)
                    VALUES (?, ?, ?, ?, ?, ?)',
                    array(
                        $empresa_id,
                        $sku,
                        $nombre,
                        $descripcion,
                        $precio,
                        (int) $disponible
                    )
                );

                $id = (int) $this->db->insert_id();
            } else {
                $ejecutar(
                    'UPDATE productos
                    SET sku = ?,
                        nombre = ?,
                        descripcion = ?,
                        precio = ?,
                        disponible = ?
                    WHERE id = ? AND empresa_id = ?',
                    array(
                        $sku,
                        $nombre,
                        $descripcion,
                        $precio,
                        (int) $disponible,
                        $id,
                        $empresa_id
                    )
                );

                // Comparar importes normalizados, sin usar float.
                $precio_anterior = Validacion_negocio::decimal(
                    $anterior['precio'],
                    'Precio anterior'
                );

                if ($precio_anterior !== $precio) {
                    $ejecutar(
                        'INSERT INTO producto_precio_historial
                            (empresa_id, producto_id, usuario_id,
                            precio_anterior, precio_nuevo)
                        VALUES (?, ?, ?, ?, ?)',
                        array(
                            $empresa_id,
                            $id,
                            $usuario->id(),
                            $precio_anterior,
                            $precio
                        )
                    );
                }
            }

            if (
                !$this->db->trans_status()
                || !$this->db->trans_commit()
            ) {
                throw new RuntimeException(
                    'No se pudo completar el registro.'
                );
            }

            $iniciada = FALSE;

            return array(
                'id' => $id,
                'error' => NULL
            );
        } catch (InvalidArgumentException $error) {
            if ($iniciada) {
                $this->db->trans_rollback();
            }

            return array(
                'id' => NULL,
                'error' => $error->getMessage()
            );
        } catch (Throwable $error) {
            if ($iniciada) {
                $this->db->trans_rollback();
            }

            log_message(
                'error',
                'Fallo al guardar el catálogo: ' . $error->getMessage()
            );

            return array(
                'id' => NULL,
                'error' => 'No se pudo guardar el producto. Revisa que el código esté disponible y que la migración del catálogo esté aplicada.'
            );
        } finally {
            $this->db->db_debug = $debug_anterior;
        }
    }

    public function historial_precios($producto_id, $pagina = 1)
    {
        $producto_id = Validacion_negocio::id(
            $producto_id,
            'Producto'
        );

        $pagina = max(1, min(100000, (int) $pagina));
        $por_pagina = 20;

        $consulta = $this->db
            ->select(
                'h.id, h.precio_anterior, h.precio_nuevo,
                h.cambiado_en, u.nombre AS responsable'
            )
            ->from('producto_precio_historial h')
            ->join(
                'usuarios u',
                'u.id = h.usuario_id AND u.empresa_id = h.empresa_id',
                'left'
            )
            ->where('h.empresa_id', $this->empresa_id())
            ->where('h.producto_id', $producto_id)
            ->order_by('h.cambiado_en', 'DESC')
            ->order_by('h.id', 'DESC')
            ->limit(
                $por_pagina + 1,
                ($pagina - 1) * $por_pagina
            )
            ->get();

        if (!$consulta) {
            show_error(
                'No se pudo consultar el historial de precios.',
                503
            );
            return array();
        }

        return $consulta->result_array();
    }
}