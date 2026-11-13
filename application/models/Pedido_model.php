<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pedido_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('Validacion_negocio');
    }

    public function crear($referencia, $usuario_id)
    {
        try { $referencia=Validacion_negocio::texto($referencia,'Referencia',100); }
        catch (InvalidArgumentException $e) { show_error(html_escape($e->getMessage()),422);return 0; }
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

        // El número visible y el pedido se guardan en la misma transacción.
        $debug_anterior = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        try {
            if (!$this->db->trans_begin()) {
                throw new RuntimeException('No se pudo iniciar el registro.');
            }

            // UPDATE bloquea el contador de esta empresa hasta terminar.
            // Otra empresa puede seguir creando sus propios pedidos.
            $actualizado = $this->db->query(
                'UPDATE empresas
                 SET ultimo_numero_pedido = ultimo_numero_pedido + 1
                 WHERE id = ?',
                array($empresa_id)
            );

            if (!$actualizado || $this->db->affected_rows() !== 1) {
                throw new RuntimeException('No se pudo actualizar el contador.');
            }

            $consulta = $this->db
                ->select('ultimo_numero_pedido')
                ->where('id', $empresa_id)
                ->get('empresas');

            $empresa = $consulta ? $consulta->row_array() : NULL;

            if (!$empresa) {
                throw new RuntimeException('No se pudo consultar el contador.');
            }

            $guardado = $this->db->insert('pedidos', array(
                'empresa_id' => $empresa_id,
                'numero' => (int) $empresa['ultimo_numero_pedido'],
                'usuario_id' => $usuario_id,
                'referencia' => $referencia,
                'estado' => 'borrador',
                'estado_pago' => 'pendiente',
                'propina_porcentaje' => 0,
                'propina_tipo' => 'ninguna',
                'propina_valor' => '0.00',
                'propina_monto' => '0.00',
                'total' => '0.00'
            ));

            if (!$guardado) {
                throw new RuntimeException('No se pudo guardar el pedido.');
            }

            // Las rutas y los detalles siguen utilizando el ID interno.
            $pedido_id = (int) $this->db->insert_id();

            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('No se pudo completar el registro.');
            }

            return $pedido_id;
        } catch (Exception $error) {
            $this->db->trans_rollback();
            log_message('error', 'Fallo al crear el pedido con numeración por empresa.');
        } finally {
            $this->db->db_debug = $debug_anterior;
        }

        show_error('No se pudo guardar el pedido. Inténtalo de nuevo más tarde.', 503);
        return 0;
    }

    private function aplicar_filtros_historial(array $filtros)
    {
        $this->db
            ->from('pedidos')
            ->where('empresa_id', $this->empresa_id());

        if (
            isset($filtros['busqueda'])
            && is_string($filtros['busqueda'])
            && trim($filtros['busqueda']) !== ''
        ) {
            $busqueda = trim($filtros['busqueda']);

            // Agrupar las condiciones para mantener
            // la separación de datos por empresa.
            $this->db
                ->group_start()
                ->like('referencia', $busqueda);

            if (
                ctype_digit($busqueda)
                && strlen($busqueda) <= 10
            ) {
                $this->db->or_where('numero', $busqueda);
            }

            $this->db->group_end();
        }

        foreach (array('estado', 'estado_pago') as $campo) {
            if (
                isset($filtros[$campo])
                && is_string($filtros[$campo])
                && $filtros[$campo] !== ''
            ) {
                $this->db->where($campo, $filtros[$campo]);
            }
        }

        if (
            isset($filtros['desde'])
            && is_string($filtros['desde'])
            && $filtros['desde'] !== ''
        ) {
            $this->db->where(
                'creado_en >=',
                $filtros['desde'] . ' 00:00:00'
            );
        }

        if (
            isset($filtros['hasta'])
            && is_string($filtros['hasta'])
            && $filtros['hasta'] !== ''
        ) {
            $this->db->where(
                'creado_en <=',
                $filtros['hasta'] . ' 23:59:59'
            );
        }
    }

    public function contar_historial(array $filtros = array())
    {
        $this->aplicar_filtros_historial($filtros);

        $consulta = $this->db
            ->select('COUNT(*) AS total', FALSE)
            ->get();

        if (!$consulta) {
            show_error(
                'No se pudo contar el historial de pedidos.',
                503
            );
            return 0;
        }

        $fila = $consulta->row_array();

        return (int) $fila['total'];
    }

    public function listar(
        array $filtros = array(),
        $limite = 50,
        $offset = 0
    ) {
        $limite = max(1, min(100, (int) $limite));
        $offset = max(0, (int) $offset);

        $this->aplicar_filtros_historial($filtros);

        $consulta = $this->db
            ->select('pedidos.*')
            ->order_by('creado_en', 'DESC')
            ->order_by('id', 'DESC')
            ->limit($limite, $offset)
            ->get();

        if (!$consulta) {
            show_error(
                'No se pudieron consultar los pedidos.',
                503
            );
            return array();
        }

        return $consulta->result_array();
    }

    public function listar_cocina()
    {
        $consulta = $this->db
            ->select('id, numero, referencia, estado, confirmado_en')
            ->select('GREATEST(0, TIMESTAMPDIFF(MINUTE, confirmado_en, CURRENT_TIMESTAMP)) AS minutos_desde_confirmacion', FALSE)
            ->where('empresa_id', $this->empresa_id())
            ->where_in('estado', array('pendiente', 'en_preparacion', 'listo'))
            ->order_by('confirmado_en', 'ASC')
            ->order_by('id', 'ASC')
            ->get('pedidos');

        if (!$consulta) {
            show_error('No se pudo consultar la cola de cocina.', 503);
        }

        $pedidos = $consulta->result_array();

        if (empty($pedidos)) {
            return array();
        }

        $pedidos_por_id = array();
        $ids = array();

        foreach ($pedidos as $indice => $pedido) {
            $pedido['detalles'] = array();
            $pedidos[$indice] = $pedido;
            $pedidos_por_id[(int) $pedido['id']] = $indice;
            $ids[] = (int) $pedido['id'];
        }

        $consulta_detalles = $this->db
            ->select('pedido_id, producto_nombre, cantidad')
            ->where('empresa_id', $this->empresa_id())
            ->where_in('pedido_id', $ids)
            ->order_by('pedido_id', 'ASC')
            ->order_by('id', 'ASC')
            ->get('pedido_detalles');

        if (!$consulta_detalles) {
            show_error('No se pudieron consultar los productos de cocina.', 503);
        }

        foreach ($consulta_detalles->result_array() as $detalle) {
            $pedido_id = (int) $detalle['pedido_id'];

            if (isset($pedidos_por_id[$pedido_id])) {
                $indice = $pedidos_por_id[$pedido_id];
                $pedidos[$indice]['detalles'][] = $detalle;
            }
        }

        return $pedidos;
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

    public function eventos($pedido_id)
    {
        $consulta = $this->db
            ->select(
                'pedido_eventos.estado_anterior, '
                . 'pedido_eventos.estado_nuevo, '
                . 'pedido_eventos.rol_codigo, '
                . 'pedido_eventos.creado_en, '
                . 'usuarios.nombre AS usuario_nombre'
            )
            ->from('pedido_eventos')
            ->join('usuarios', 'usuarios.id = pedido_eventos.usuario_id')
            ->where('pedido_eventos.pedido_id', (int) $pedido_id)
            ->where('pedido_eventos.empresa_id', $this->empresa_id())
            ->order_by('pedido_eventos.id', 'ASC')
            ->get();

        if (!$consulta) {
            show_error('No se pudo consultar el historial del pedido.', 503);
        }

        return $consulta->result_array();
    }

    public function agregar_producto($pedido_id, $producto_id, $cantidad)
    {
        if (!is_int($cantidad) || $cantidad < 1 || $cantidad > 999) {
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
                'SELECT id, nombre, precio, disponible, insumo_id
                FROM productos
                WHERE id = ? AND empresa_id = ?
                FOR UPDATE',
                array($producto_id, $empresa_id)
            )->row_array();

            if (!$producto) {
                throw new InvalidArgumentException(
                    'El producto no está disponible para tu empresa.'
                );
            }

            if ((int) $producto['disponible'] !== 1) {
                throw new InvalidArgumentException(
                    'El producto está marcado como no disponible.'
                );
            }

            // Si está asociado al inventario, el insumo debe seguir activo.
            if ($producto['insumo_id'] !== NULL) {
                $insumo = $ejecutar(
                    'SELECT id, activo
                    FROM insumos
                    WHERE id = ? AND empresa_id = ?
                    FOR UPDATE',
                    array($producto['insumo_id'], $empresa_id)
                )->row_array();

                if (!$insumo || (int) $insumo['activo'] !== 1) {
                    throw new InvalidArgumentException(
                        'El artículo de inventario asociado no está disponible.'
                    );
                }
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
        if ($cantidad !== NULL && (!is_int($cantidad) || $cantidad < 1 || $cantidad > 999)) {
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
    public function confirmar($pedido_id, $usuario_id, $rol_codigo)
    {
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

            // Usa el mismo bloqueo que agregar y modificar productos.
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
                    'Solo puedes confirmar un pedido en borrador.'
                );
            }

            $resumen = $ejecutar(
                'SELECT
                    COUNT(*) AS productos,
                    COALESCE(SUM(cantidad * precio_unitario), 0) AS total,
                    COALESCE(SUM(cantidad * precio_unitario), 0)
                        > 9999999999.99 AS excede,
                    COALESCE(SUM(cantidad < 1 OR cantidad > 999), 0)
                        AS invalidos
                 FROM pedido_detalles
                 WHERE pedido_id = ? AND empresa_id = ?',
                array($pedido_id, $empresa_id)
            )->row_array();

            if ((int) $resumen['productos'] === 0) {
                throw new InvalidArgumentException(
                    'Agrega al menos un producto antes de confirmar.'
                );
            }

            if ((int) $resumen['invalidos'] > 0) {
                throw new InvalidArgumentException(
                    'Las cantidades deben estar entre 1 y 999.'
                );
            }

            if ($resumen['excede']) {
                throw new InvalidArgumentException(
                    'El total supera el límite permitido.'
                );
            }

            // Revisar nuevamente la disponibilidad:
            // un producto pudo deshabilitarse después de agregarlo.
            $lineas = $ejecutar(
                'SELECT producto_id
                FROM pedido_detalles
                WHERE pedido_id = ? AND empresa_id = ?
                ORDER BY producto_id',
                array($pedido_id, $empresa_id)
            )->result_array();

            foreach ($lineas as $linea) {
                $producto_actual = $ejecutar(
                    'SELECT id, nombre, disponible
                    FROM productos
                    WHERE id = ? AND empresa_id = ?
                    FOR UPDATE',
                    array($linea['producto_id'], $empresa_id)
                )->row_array();

                if (!$producto_actual) {
                    throw new InvalidArgumentException(
                        'Uno de los productos del pedido ya no está disponible para tu empresa.'
                    );
                }

                if ((int) $producto_actual['disponible'] !== 1) {
                    throw new InvalidArgumentException(
                        'No puedes confirmar: el producto "'
                        . $producto_actual['nombre']
                        . '" está marcado como no disponible. Quítalo del pedido para continuar.'
                    );
                }
            }

            // Comprobar existencias y registrar las salidas.
            // Si algo falla, la transacción deshace todo.
            $this->mover_inventario_pedido(
                $pedido_id,
                $empresa_id,
                FALSE
            );

            // "pendiente" significa pendiente de preparación.
            // El estado de pago no cambia.
            $ejecutar(
                'UPDATE pedidos
                 SET estado = ?,
                     total = ?,
                     confirmado_en = CURRENT_TIMESTAMP
                 WHERE id = ? AND empresa_id = ?',
                array(
                    'pendiente',
                    $resumen['total'],
                    $pedido_id,
                    $empresa_id
                )
            );

            $ejecutar(
                'INSERT INTO pedido_eventos
                 (empresa_id, pedido_id, usuario_id, rol_codigo,
                  estado_anterior, estado_nuevo)
                 VALUES (?, ?, ?, ?, ?, ?)',
                array($empresa_id, $pedido_id, $usuario_id, $rol_codigo,
                    'borrador', 'pendiente')
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
            log_message('error', 'Fallo al confirmar el pedido.');

            return 'No se pudo confirmar el pedido. Intenta nuevamente.';
        } finally {
            $this->db->db_debug = $debug_anterior;
        }
    }
        public function cancelar($pedido_id, $motivo)
    {
        // Validar el motivo también en el servidor.
        if (
            !is_string($motivo)
            || trim($motivo) === ''
            || mb_strlen(trim($motivo), 'UTF-8') > 500
        ) {
            return 'Escribe un motivo de entre 1 y 500 caracteres.';
        }

        $motivo = trim($motivo);

        // Obtener la identidad desde la sesión, no desde el formulario.
        $ci =& get_instance();
        $ci->load->library('Autenticacion');
        $usuario = $ci->autenticacion->usuario_actual();

        if (!$usuario || !$usuario->puede('pedidos.cancelar')) {
            return 'No tienes permiso para cancelar pedidos.';
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

            // Bloquear el pedido mientras se procesa la cancelación.
            $pedido = $ejecutar(
                'SELECT id, estado, estado_pago
                 FROM pedidos
                 WHERE id = ? AND empresa_id = ?
                 FOR UPDATE',
                array($pedido_id, $empresa_id)
            )->row_array();

            if (!$pedido) {
                throw new InvalidArgumentException('Pedido no encontrado.');
            }

            // Revalidar que el usuario sigue autorizado.
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
                    'pedidos.cancelar'
                )
            )->row_array();

            if (!$autorizado) {
                throw new InvalidArgumentException(
                    'No tienes permiso para cancelar pedidos.'
                );
            }

            $estados_permitidos = array(
                'borrador',
                'pendiente',
                'en_preparacion',
                'listo'
            );

            if (!in_array($pedido['estado'], $estados_permitidos, TRUE)) {
                throw new InvalidArgumentException(
                    'Este pedido ya fue cancelado, entregado o no admite cancelación.'
                );
            }

            $pago_registrado = $ejecutar(
                'SELECT id FROM pagos WHERE empresa_id = ? AND pedido_id = ? LIMIT 1',
                array($empresa_id, $pedido_id)
            )->row_array();

            if ($pago_registrado || $pedido['estado_pago'] !== 'pendiente') {
                throw new InvalidArgumentException(
                    'Un pedido con pagos requiere gestionar la devolución antes de cancelarlo.'
                );
            }

            // Devolver únicamente las existencias descontadas por este pedido.
            $this->mover_inventario_pedido(
                $pedido_id,
                $empresa_id,
                TRUE
            );

            // Registrar la autorización y el motivo.
            $ejecutar(
                'INSERT INTO pedido_cancelaciones
                 (pedido_id, empresa_id, usuario_id, motivo, estado_anterior)
                 VALUES (?, ?, ?, ?, ?)',
                array(
                    $pedido_id,
                    $empresa_id,
                    $usuario->id(),
                    $motivo,
                    $pedido['estado']
                )
            );

            // Conservar los productos y el total como registro histórico.
            $ejecutar(
                'UPDATE pedidos
                 SET estado = ?
                 WHERE id = ? AND empresa_id = ?',
                array('cancelado', $pedido_id, $empresa_id)
            );

            $ejecutar(
                'INSERT INTO pedido_eventos
                 (empresa_id, pedido_id, usuario_id, rol_codigo,
                  estado_anterior, estado_nuevo)
                 VALUES (?, ?, ?, ?, ?, ?)',
                array($empresa_id, $pedido_id, $usuario->id(), $usuario->rol(),
                    $pedido['estado'], 'cancelado')
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
            log_message('error', 'Fallo al cancelar el pedido.');

            return 'No se pudo cancelar el pedido. Inténtalo de nuevo más tarde.';
        } finally {
            $this->db->db_debug = $debug_anterior;
        }
    }
        public function cancelacion($pedido_id)
    {
        $consulta = $this->db
            ->select('c.*, u.nombre AS autorizado_por')
            ->from('pedido_cancelaciones c')
            ->join(
                'usuarios u',
                'u.id = c.usuario_id AND u.empresa_id = c.empresa_id',
                'left'
            )
            ->where('c.pedido_id', (int) $pedido_id)
            ->where('c.empresa_id', $this->empresa_id())
            ->get();

        if (!$consulta) {
            show_error('No se pudo consultar la cancelación.', 503);
        }

        return $consulta->row_array();
    }
    public function actualizar_estado_cocina(
        $pedido_id,
        $estado_nuevo,
        $usuario_id,
        $rol_codigo
    )
    {
        $transiciones = array(
            'pendiente' => 'en_preparacion',
            'en_preparacion' => 'listo',
            'listo' => 'entregado'
        );

        if (!in_array($estado_nuevo, array_values($transiciones), TRUE)) {
            return 'El estado solicitado no es válido.';
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

            $usuario = $ejecutar(
                'SELECT id
                 FROM usuarios
                 WHERE id = ? AND empresa_id = ? AND activo = 1',
                array($usuario_id, $empresa_id)
            )->row_array();

            if (!$usuario) {
                throw new InvalidArgumentException('Usuario no autorizado.');
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

            $estado_anterior = $pedido['estado'];

            if (
                !isset($transiciones[$estado_anterior])
                || $transiciones[$estado_anterior] !== $estado_nuevo
            ) {
                throw new InvalidArgumentException(
                    'La transición solicitada no está permitida.'
                );
            }

            $ejecutar(
                'UPDATE pedidos
                 SET estado = ?
                 WHERE id = ? AND empresa_id = ?',
                array($estado_nuevo, $pedido_id, $empresa_id)
            );

            $ejecutar(
                'INSERT INTO pedido_eventos
                 (empresa_id, pedido_id, usuario_id, rol_codigo,
                  estado_anterior, estado_nuevo)
                 VALUES (?, ?, ?, ?, ?, ?)',
                array(
                    $empresa_id,
                    $pedido_id,
                    $usuario_id,
                    $rol_codigo,
                    $estado_anterior,
                    $estado_nuevo
                )
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
            log_message('error', 'Fallo al actualizar el estado de cocina.');

            return 'No se pudo actualizar el pedido. Intenta nuevamente.';
        } finally {
            $this->db->db_debug = $debug_anterior;
        }
    }

    private function mover_inventario_pedido(
        $pedido_id,
        $empresa_id,
        $devolver = FALSE
    ) {
        // Esta función se ejecuta dentro de la transacción del pedido.
        // No inicia ni confirma una transacción propia.
        $ci =& get_instance();
        $ci->load->library('Autenticacion');
        $usuario = $ci->autenticacion->usuario_actual();

        $permiso = $devolver
            ? 'pedidos.cancelar'
            : 'pedidos.confirmar';

        if (
            !$usuario
            || (int) $usuario->empresaId() !== (int) $empresa_id
            || !$usuario->puede($permiso)
        ) {
            throw new InvalidArgumentException(
                'No tienes permiso para realizar esta operación.'
            );
        }

        $ejecutar = function ($sql, $valores) {
            $resultado = $this->db->query($sql, $valores);

            if ($resultado === FALSE) {
                throw new RuntimeException(
                    'No se pudo actualizar el inventario del pedido.'
                );
            }

            return $resultado;
        };

        if ($devolver) {
            // Devolver lo que realmente se descontó.
            // No utilizar el vínculo actual del producto:
            // podría haber cambiado después de confirmar.
            $lineas = $ejecutar(
                'SELECT insumo_id, cantidad
                FROM movimientos_inventario
                WHERE empresa_id = ?
                AND pedido_id = ?
                AND tipo = ?
                ORDER BY insumo_id',
                array($empresa_id, $pedido_id, 'salida')
            )->result_array();
        } else {
            // Los productos sin insumo asociado no descuentan inventario.
            $lineas = $ejecutar(
                'SELECT p.insumo_id, SUM(d.cantidad) AS cantidad
                FROM pedido_detalles d
                JOIN productos p
                ON p.id = d.producto_id
                AND p.empresa_id = d.empresa_id
                WHERE d.empresa_id = ?
                AND d.pedido_id = ?
                AND p.insumo_id IS NOT NULL
                GROUP BY p.insumo_id
                ORDER BY p.insumo_id',
                array($empresa_id, $pedido_id)
            )->result_array();
        }

        foreach ($lineas as $linea) {
            $insumo_id = (int) $linea['insumo_id'];
            $cantidad = $linea['cantidad'];

            // Bloquear los insumos siempre en el mismo orden.
            // También coordina con los movimientos manuales.
            $insumo = $ejecutar(
                'SELECT id, nombre, cantidad_actual, activo
                FROM insumos
                WHERE id = ? AND empresa_id = ?
                FOR UPDATE',
                array($insumo_id, $empresa_id)
            )->row_array();

            if (!$insumo) {
                throw new InvalidArgumentException(
                    'No se encontró un artículo del inventario del pedido.'
                );
            }

            if (!$devolver && (int) $insumo['activo'] !== 1) {
                throw new InvalidArgumentException(
                    'El artículo "' . $insumo['nombre'] . '" está inactivo.'
                );
            }

            // Evitar repetir el movimiento para este pedido e insumo.
            $tipo = $devolver ? 'entrada' : 'salida';

            $existente = $ejecutar(
                'SELECT id
                FROM movimientos_inventario
                WHERE empresa_id = ?
                AND pedido_id = ?
                AND insumo_id = ?
                AND tipo = ?
                LIMIT 1',
                array($empresa_id, $pedido_id, $insumo_id, $tipo)
            )->row_array();

            if ($existente) {
                throw new InvalidArgumentException(
                    'El movimiento de inventario de este pedido ya está registrado.'
                );
            }

            if ($devolver) {
                // Permitir la devolución aunque el insumo esté inactivo.
                // Calcular con DECIMAL directamente en la base de datos.
                $ejecutar(
                    'UPDATE insumos
                    SET cantidad_actual =
                        cantidad_actual + CAST(? AS DECIMAL(10,2))
                    WHERE id = ?
                    AND empresa_id = ?
                    AND cantidad_actual + CAST(? AS DECIMAL(10,2))
                        <= 99999999.99',
                    array(
                        $cantidad,
                        $insumo_id,
                        $empresa_id,
                        $cantidad
                    )
                );
            } else {
                $ejecutar(
                    'UPDATE insumos
                    SET cantidad_actual =
                        cantidad_actual - CAST(? AS DECIMAL(10,2))
                    WHERE id = ?
                    AND empresa_id = ?
                    AND activo = 1
                    AND cantidad_actual >= CAST(? AS DECIMAL(10,2))',
                    array(
                        $cantidad,
                        $insumo_id,
                        $empresa_id,
                        $cantidad
                    )
                );
            }

            if ($this->db->affected_rows() !== 1) {
                $mensaje = $devolver
                    ? 'La devolución supera la existencia máxima permitida para: '
                    : 'No hay existencias suficientes de: ';

                throw new InvalidArgumentException(
                    $mensaje . $insumo['nombre']
                );
            }

            $motivo = $devolver
                ? 'Devolución por cancelación del pedido ID '
                : 'Salida por confirmación del pedido ID ';

            // Guardar la existencia anterior y la nueva sin usar float.
            $ejecutar(
                'INSERT INTO movimientos_inventario
                    (empresa_id, insumo_id, usuario_id, pedido_id,
                    tipo, cantidad, cantidad_anterior,
                    cantidad_nueva, motivo)
                SELECT empresa_id, id, ?, ?, ?, ?, ?,
                        cantidad_actual, ?
                FROM insumos
                WHERE id = ? AND empresa_id = ?',
                array(
                    $usuario->id(),
                    $pedido_id,
                    $tipo,
                    $cantidad,
                    $insumo['cantidad_actual'],
                    $motivo . (int) $pedido_id,
                    $insumo_id,
                    $empresa_id
                )
            );
        }
    }
}
