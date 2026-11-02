<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Documento compartido de compras. La recepción y agrupación automática se integran sobre este contrato. */
class Solicitud_compra_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('Validacion_negocio');
    }
    private function q($sql, $datos = array())
    {
        $r = $this->db->query($sql, $datos);
        if (!$r) {
            throw new RuntimeException('Error al consultar compras.');
        }
        return $r;
    }
    private function operacion($permiso, $accion)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try {
            $empresa = $this->empresa_id();
            $usuario = $this->autenticacion->usuario_actual();
            if (!$usuario || !$usuario->puede($permiso)) {
                throw new InvalidArgumentException('No tienes permiso para esta operación.');
            }
            if (!$this->db->trans_begin()) {
                throw new RuntimeException('No se pudo iniciar la transacción.');
            }
            $autorizado = $this->q('SELECT u.id FROM usuarios u JOIN empresas e ON e.id=u.empresa_id
                JOIN rol_permisos rp ON rp.rol_id=u.rol_id JOIN permisos p ON p.id=rp.permiso_id
                WHERE u.id=? AND u.empresa_id=? AND u.activo=1 AND e.activa=1 AND p.codigo=? LIMIT 1', array($usuario->id(), $empresa, $permiso))->row_array();
            if (!$autorizado) {
                throw new InvalidArgumentException('Tu usuario no está autorizado.');
            }
            $id = $accion($empresa, $usuario->id());
            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('No se completó la operación.');
            }
            return array('id' => $id, 'error' => NULL);
        } catch (InvalidArgumentException $e) {
            $this->db->trans_rollback();
            return array('id' => NULL, 'error' => $e->getMessage());
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'Compras: ' . $e->getMessage());
            return array('id' => NULL, 'error' => 'No se guardaron los cambios. Revisa las migraciones y vuelve a intentarlo.');
        } finally {
            $this->db->db_debug = $debug;
        }
    }
    public function proveedores()
    {
        return $this->q('SELECT id,nombre FROM proveedores WHERE empresa_id=? AND activo=1 ORDER BY nombre', array($this->empresa_id()))->result_array();
    }
    public function insumos()
    {
        return $this->q('SELECT id,nombre,unidad_medida,proveedor_id FROM insumos WHERE empresa_id=? AND activo=1 ORDER BY nombre', array($this->empresa_id()))->result_array();
    }
    public function listar($pagina)
    {
        $empresa = $this->empresa_id();
        $n = $this->q('SELECT COUNT(*) cantidad FROM solicitudes_compra WHERE empresa_id=?', array($empresa))->row_array();
        $paginas = max(1, (int)ceil((int)$n['cantidad'] / 25));
        $pagina = max(1, min((int)$pagina, $paginas));
        $filas = $this->q('SELECT * FROM solicitudes_compra WHERE empresa_id=? ORDER BY numero DESC LIMIT 25 OFFSET ?', array($empresa, ($pagina - 1) * 25))->result_array();
        return array('solicitudes' => $filas, 'pagina' => $pagina, 'paginas' => $paginas);
    }
    public function buscar($id)
    {
        return $this->q('SELECT * FROM solicitudes_compra WHERE empresa_id=? AND id=?', array($this->empresa_id(), $id))->row_array();
    }
    public function detalles($id)
    {
        return $this->q('SELECT * FROM solicitud_compra_detalles WHERE empresa_id=? AND solicitud_id=? ORDER BY id', array($this->empresa_id(), $id))->result_array();
    }
    private function validar(array $datos)
    {
        $d = array(
            'proveedor_id' => Validacion_negocio::id($datos['proveedor_id'] ?? NULL, 'Proveedor'),
            'fecha' => Validacion_negocio::fecha($datos['fecha'] ?? NULL),
            'observaciones' => Validacion_negocio::texto($datos['observaciones'] ?? '', 'Observaciones', 1000, FALSE)
        );
        if (!isset($datos['lineas']) || !is_array($datos['lineas']) || count($datos['lineas']) < 1 || count($datos['lineas']) > 100) {
            throw new InvalidArgumentException('Agrega entre 1 y 100 insumos a la solicitud.');
        }
        $d['lineas'] = array();
        foreach ($datos['lineas'] as $linea) {
            if (!is_array($linea)) {
                throw new InvalidArgumentException('Formato de líneas inválido.');
            }
            $id = Validacion_negocio::id($linea['insumo_id'] ?? NULL, 'Insumo');
            if (isset($d['lineas'][$id])) {
                throw new InvalidArgumentException('Un insumo está repetido. Usa una sola línea con su cantidad total.');
            }
            $d['lineas'][$id] = Validacion_negocio::decimal($linea['cantidad'] ?? NULL, 'Cantidad solicitada', FALSE);
        }
        ksort($d['lineas']);
        return $d;
    }
    private function comprobar_referencias($empresa, $d)
    {
        $proveedor = $this->q('SELECT * FROM proveedores WHERE empresa_id=? AND id=? AND activo=1 FOR UPDATE', array($empresa, $d['proveedor_id']))->row_array();
        if (!$proveedor) {
            throw new InvalidArgumentException('El proveedor no está disponible en tu empresa.');
        }
        $lineas = array();
        foreach ($d['lineas'] as $id => $cantidad) {
            $insumo = $this->q('SELECT id,nombre,unidad_medida,proveedor_id FROM insumos WHERE empresa_id=? AND id=? AND activo=1 FOR UPDATE', array($empresa, $id))->row_array();
            if (!$insumo) {
                throw new InvalidArgumentException('Uno de los insumos no está disponible en tu empresa.');
            }
            if ($insumo['proveedor_id'] !== NULL && (int)$insumo['proveedor_id'] !== $d['proveedor_id']) {
                throw new InvalidArgumentException('Un insumo está asociado con otro proveedor. Sepáralo en otra solicitud.');
            }
            $insumo['cantidad'] = $cantidad;
            $lineas[] = $insumo;
        }
        return array($proveedor, $lineas);
    }
    private function guardar_lineas($empresa, $solicitud, $lineas)
    {
        foreach ($lineas as $l) {
            $this->q(
                'INSERT INTO solicitud_compra_detalles (empresa_id,solicitud_id,insumo_id,insumo_nombre,unidad_medida,cantidad) VALUES (?,?,?,?,?,?)',
                array($empresa, $solicitud, $l['id'], $l['nombre'], $l['unidad_medida'], $l['cantidad'])
            );
        }
    }
    private function evento($empresa, $id, $usuario, $accion, $anterior, $nuevo)
    {
        $this->q('INSERT INTO solicitud_compra_eventos (empresa_id,solicitud_id,usuario_id,accion,estado_anterior,estado_nuevo) VALUES (?,?,?,?,?,?)', array($empresa, $id, $usuario, $accion, $anterior, $nuevo));
    }
    /** Entrada de integración: Emmanuel puede crear una solicitud por proveedor con este método. */
    public function crear_borrador(array $datos, $clave)
    {
        return $this->operacion('compras.gestionar', function ($empresa, $usuario) use ($datos, $clave) {
            $d = $this->validar($datos);
            $clave = Validacion_negocio::clave($clave);
            $existente = $this->q('SELECT id FROM solicitudes_compra WHERE empresa_id=? AND clave=?', array($empresa, $clave))->row_array();
            if ($existente) {
                throw new InvalidArgumentException('Este formulario ya creó una solicitud. Revisa el listado antes de repetir.');
            }
            list($p, $lineas) = $this->comprobar_referencias($empresa, $d);
            $this->q('UPDATE empresas SET ultimo_numero_compra=ultimo_numero_compra+1 WHERE id=?', array($empresa));
            $numero = $this->q('SELECT ultimo_numero_compra FROM empresas WHERE id=?', array($empresa))->row_array()['ultimo_numero_compra'];
            $this->q('INSERT INTO solicitudes_compra (empresa_id,numero,proveedor_id,proveedor_nombre,proveedor_contacto,proveedor_telefono,proveedor_correo,proveedor_direccion,fecha,observaciones,clave,creado_por,actualizado_por)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', array($empresa, $numero, $p['id'], $p['nombre'], $p['contacto'], $p['telefono'], $p['correo'], $p['direccion'], $d['fecha'], $d['observaciones'], $clave, $usuario, $usuario));
            $id = (int)$this->db->insert_id();
            $this->guardar_lineas($empresa, $id, $lineas);
            $this->evento($empresa, $id, $usuario, 'crear', NULL, 'borrador');
            return $id;
        });
    }
    public function editar_borrador($id, $version, array $datos)
    {
        return $this->operacion('compras.gestionar', function ($empresa, $usuario) use ($id, $version, $datos) {
            $version = Validacion_negocio::id($version, 'Versión');
            $d = $this->validar($datos);
            $s = $this->q('SELECT * FROM solicitudes_compra WHERE empresa_id=? AND id=? FOR UPDATE', array($empresa, $id))->row_array();
            if (!$s || $s['estado'] !== 'borrador') {
                throw new InvalidArgumentException('Solo se pueden editar borradores de tu empresa.');
            }
            if ((int)$s['version'] !== $version) {
                throw new InvalidArgumentException('Otra persona modificó la solicitud. Vuelve al listado y abre la versión actual.');
            }
            list($p, $lineas) = $this->comprobar_referencias($empresa, $d);
            $recibido = $this->q('SELECT id FROM solicitud_compra_detalles WHERE empresa_id=? AND solicitud_id=? AND recibido>0 LIMIT 1', array($empresa, $id))->row_array();
            if ($recibido) {
                throw new InvalidArgumentException('Una solicitud con recepción no puede modificarse.');
            }
            $this->q(
                'UPDATE solicitudes_compra SET proveedor_id=?,proveedor_nombre=?,proveedor_contacto=?,proveedor_telefono=?,proveedor_correo=?,proveedor_direccion=?,fecha=?,observaciones=?,version=version+1,actualizado_por=?,actualizado_en=CURRENT_TIMESTAMP WHERE empresa_id=? AND id=?',
                array($p['id'], $p['nombre'], $p['contacto'], $p['telefono'], $p['correo'], $p['direccion'], $d['fecha'], $d['observaciones'], $usuario, $empresa, $id)
            );
            $this->q('DELETE FROM solicitud_compra_detalles WHERE empresa_id=? AND solicitud_id=?', array($empresa, $id));
            $this->guardar_lineas($empresa, $id, $lineas);
            $this->evento($empresa, $id, $usuario, 'editar', 'borrador', 'borrador');
            return $id;
        });
    }
    public function confirmar($id, $version)
    {
        return $this->operacion('compras.confirmar', function ($empresa, $usuario) use ($id, $version) {
            $version = Validacion_negocio::id($version, 'Versión');
            $s = $this->q('SELECT * FROM solicitudes_compra WHERE empresa_id=? AND id=? FOR UPDATE', array($empresa, $id))->row_array();
            if (!$s || $s['estado'] !== 'borrador') {
                throw new InvalidArgumentException('Solo se pueden confirmar borradores de tu empresa.');
            }
            if ((int)$s['version'] !== $version) {
                throw new InvalidArgumentException('El documento cambió después de abrirlo. Revísalo otra vez antes de confirmar.');
            }
            $detalles = $this->detalles($id);
            $d = $this->validar(array('proveedor_id' => $s['proveedor_id'], 'fecha' => $s['fecha'], 'observaciones' => $s['observaciones'], 'lineas' => $detalles));
            list($proveedor, $lineas) = $this->comprobar_referencias($empresa, $d);
            $unidades = array();
            foreach ($lineas as $l) {
                $unidades[(int)$l['id']] = $l['unidad_medida'];
            }
            foreach ($detalles as $l) {
                if ($unidades[(int)$l['insumo_id']] !== $l['unidad_medida'] || (float)$l['recibido'] > 0) {
                    throw new InvalidArgumentException('Las unidades cambiaron o ya hay una recepción. Revisa el borrador.');
                }
            }
            $this->q("UPDATE solicitudes_compra SET estado='confirmada',version=version+1,confirmado_por=?,actualizado_por=?,confirmado_en=CURRENT_TIMESTAMP,actualizado_en=CURRENT_TIMESTAMP WHERE empresa_id=? AND id=?", array($usuario, $usuario, $empresa, $id));
            $this->evento($empresa, $id, $usuario, 'confirmar', 'borrador', 'confirmada');
            return $id;
        });
    }
    /** Alta mínima para poder probar documentos antes de integrar el módulo de Andrés. */
    public function crear_proveedor(array $datos)
    {
        return $this->operacion('proveedores.gestionar', function ($empresa, $usuario) use ($datos) {
            $d = array();
            foreach (array('nombre' => 150, 'contacto' => 100, 'telefono' => 30, 'correo' => 190, 'direccion' => 255) as $campo => $limite) {
                $d[$campo] = Validacion_negocio::texto($datos[$campo] ?? '', ucfirst($campo), $limite, $campo === 'nombre');
            }
            if ($d['correo'] !== '' && !filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Correo del proveedor no válido.');
            }
            $existe = $this->q('SELECT id FROM proveedores WHERE empresa_id=? AND nombre=?', array($empresa, $d['nombre']))->row_array();
            if ($existe) {
                throw new InvalidArgumentException('Ya existe un proveedor con ese nombre en tu empresa.');
            }
            $this->q('INSERT INTO proveedores (empresa_id,nombre,contacto,telefono,correo,direccion) VALUES (?,?,?,?,?,?)', array($empresa, $d['nombre'], $d['contacto'], $d['telefono'], $d['correo'], $d['direccion']));
            return (int)$this->db->insert_id();
        });
    }

    public function cambiar_estado($id, $version, $nuevoEstado, $motivo = '')
    {
        return $this->operacion(
            'compras.confirmar',
            function ($empresa, $usuario) use (
                $id,
                $version,
                $nuevoEstado,
                $motivo
            ) {
                $id = Validacion_negocio::id($id, 'Solicitud');
                $version = Validacion_negocio::id($version, 'Versión');

                $transiciones = array(
                    'borrador' => array('cancelada'),
                    'confirmada' => array('enviada', 'cancelada'),
                    'enviada' => array('cancelada')
                );

                if (!in_array(
                    $nuevoEstado,
                    array('enviada', 'cancelada'),
                    TRUE
                )) {
                    throw new InvalidArgumentException(
                        'Estado de solicitud no permitido.'
                    );
                }

                if ($nuevoEstado === 'cancelada') {

                    $motivo = Validacion_negocio::texto(
                        $motivo,
                        'Motivo',
                        1000
                    );

                    if (trim($motivo) === '') {
                        throw new InvalidArgumentException(
                            'Debes indicar el motivo de cancelación.'
                        );
                    }
                } else {

                    // Enviar una solicitud no requiere motivo.
                    $motivo = '';
                }

                $solicitud = $this->q(
                    'SELECT *
                 FROM solicitudes_compra
                 WHERE empresa_id = ? AND id = ?
                 FOR UPDATE',
                    array($empresa, $id)
                )->row_array();

                if (!$solicitud) {
                    throw new InvalidArgumentException(
                        'Solicitud no encontrada.'
                    );
                }

                if ((int) $solicitud['version'] !== $version) {
                    throw new InvalidArgumentException(
                        'La solicitud fue modificada. Recarga la página.'
                    );
                }

                $anterior = $solicitud['estado'];

                if (
                    !isset($transiciones[$anterior]) ||
                    !in_array(
                        $nuevoEstado,
                        $transiciones[$anterior],
                        TRUE
                    )
                ) {
                    throw new InvalidArgumentException(
                        'La transición de estado no está permitida.'
                    );
                }

                $recibido = $this->q(
                    'SELECT id
                 FROM solicitud_compra_detalles
                 WHERE empresa_id = ?
                   AND solicitud_id = ?
                   AND recibido > 0
                 LIMIT 1',
                    array($empresa, $id)
                )->row_array();

                if ($recibido) {
                    throw new InvalidArgumentException(
                        'No se puede cancelar o reenviar una solicitud ' .
                            'que ya tiene recepciones.'
                    );
                }

                $this->q(
                    'UPDATE solicitudes_compra
                 SET estado = ?,
                     version = version + 1,
                     actualizado_por = ?,
                     actualizado_en = CURRENT_TIMESTAMP
                 WHERE empresa_id = ? AND id = ?',
                    array($nuevoEstado, $usuario, $empresa, $id)
                );

                $this->q(
                    'INSERT INTO solicitud_compra_eventos
                 (empresa_id, solicitud_id, usuario_id,
                  accion, estado_anterior, estado_nuevo, comentario)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                    array(
                        $empresa,
                        $id,
                        $usuario,
                        $nuevoEstado === 'enviada' ? 'enviar' : 'cancelar',
                        $anterior,
                        $nuevoEstado,
                        $motivo
                    )
                );

                return $id;
            }
        );
    }

    public function historial_proveedor($proveedorId, $pagina = 1)
    {
        $empresa = $this->empresa_id();
        $proveedorId = (int) $proveedorId;

        $proveedor = $this->q(
            'SELECT id, nombre
         FROM proveedores
         WHERE empresa_id = ? AND id = ?',
            array($empresa, $proveedorId)
        )->row_array();

        if (!$proveedor) {
            return NULL;
        }

        $total = $this->q(
            'SELECT COUNT(*) AS total
         FROM solicitudes_compra
         WHERE empresa_id = ? AND proveedor_id = ?',
            array($empresa, $proveedorId)
        )->row_array();

        $porPagina = 20;
        $paginas = max(
            1,
            (int) ceil((int) $total['total'] / $porPagina)
        );

        $pagina = max(1, min((int) $pagina, $paginas));
        $offset = ($pagina - 1) * $porPagina;

        $solicitudes = $this->q(
            'SELECT id, numero, fecha, estado, creado_en
         FROM solicitudes_compra
         WHERE empresa_id = ? AND proveedor_id = ?
         ORDER BY id DESC
         LIMIT 20 OFFSET ?',
            array($empresa, $proveedorId, $offset)
        )->result_array();

        return array(
            'proveedor' => $proveedor,
            'solicitudes' => $solicitudes,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'total' => (int) $total['total']
        );
    }

    public function recibir($id, $version, $clave, array $cantidades)
    {
        return $this->operacion(
            'compras.confirmar',
            function ($empresa, $usuario) use (
                $id,
                $version,
                $clave,
                $cantidades
            ) {
                $id = Validacion_negocio::id($id, 'Solicitud');
                $version = Validacion_negocio::id($version, 'Versión');
                $clave = Validacion_negocio::clave($clave);

                if (empty($cantidades) || count($cantidades) > 100) {
                    throw new InvalidArgumentException(
                        'No se recibieron cantidades válidas.'
                    );
                }

                // Bloquear la solicitud para serializar recepciones.
                $solicitud = $this->q(
                    'SELECT id, estado, version
                 FROM solicitudes_compra
                 WHERE empresa_id = ? AND id = ?
                 FOR UPDATE',
                    array($empresa, $id)
                )->row_array();

                if (!$solicitud) {
                    throw new InvalidArgumentException(
                        'Solicitud no encontrada.'
                    );
                }

                // La misma clave nunca debe incrementar
                // nuevamente las existencias.
                $existente = $this->q(
                    'SELECT id, solicitud_id
                 FROM recepciones_compra
                 WHERE empresa_id = ? AND clave = ?',
                    array($empresa, $clave)
                )->row_array();

                if ($existente) {
                    if ((int) $existente['solicitud_id'] !== $id) {
                        throw new InvalidArgumentException(
                            'La clave ya pertenece a otra recepción.'
                        );
                    }

                    return $id;
                }

                if ($solicitud['estado'] !== 'enviada') {
                    throw new InvalidArgumentException(
                        'Solo se pueden recibir solicitudes enviadas.'
                    );
                }

                if ((int) $solicitud['version'] !== $version) {
                    throw new InvalidArgumentException(
                        'La solicitud cambió. Recarga el formulario.'
                    );
                }

                $lineas = $this->q(
                    'SELECT id, insumo_id, cantidad, recibido
                 FROM solicitud_compra_detalles
                 WHERE empresa_id = ? AND solicitud_id = ?
                 ORDER BY id
                 FOR UPDATE',
                    array($empresa, $id)
                )->result_array();

                if (!$lineas) {
                    throw new InvalidArgumentException(
                        'La solicitud no tiene insumos.'
                    );
                }

                // Convertir importes decimales a centésimas
                // para evitar errores de punto flotante.
                $aCent = function ($valor) {
                    $partes = explode('.', (string) $valor);
                    return ((int) $partes[0]) * 100
                        + (int) str_pad(
                            $partes[1] ?? '',
                            2,
                            '0'
                        );
                };

                $aDecimal = function ($valor) {
                    return intdiv($valor, 100) . '.'
                        . str_pad(
                            (string) ($valor % 100),
                            2,
                            '0',
                            STR_PAD_LEFT
                        );
                };

                $lineasPorId = array();

                foreach ($lineas as $linea) {
                    $lineasPorId[(int) $linea['id']] = $linea;
                }

                $recepciones = array();

                foreach ($cantidades as $detalleId => $cantidad) {

                    $detalleId = Validacion_negocio::id(
                        $detalleId,
                        'Detalle'
                    );

                    if (!isset($lineasPorId[$detalleId])) {
                        throw new InvalidArgumentException(
                            'El detalle no pertenece a la solicitud.'
                        );
                    }

                    $cantidad = Validacion_negocio::decimal(
                        $cantidad,
                        'Cantidad recibida',
                        TRUE
                    );

                    $recibir = $aCent($cantidad);

                    $linea = $lineasPorId[$detalleId];

                    $solicitado = $aCent($linea['cantidad']);
                    $recibido = $aCent($linea['recibido']);
                    $pendiente = $solicitado - $recibido;

                    if ($recibir > $pendiente) {
                        throw new InvalidArgumentException(
                            'La cantidad supera lo pendiente.'
                        );
                    }

                    if ($recibir > 0) {
                        $recepciones[$detalleId] = array(
                            'linea' => $linea,
                            'cantidad' => $recibir
                        );
                    }
                }

                if (!$recepciones) {
                    throw new InvalidArgumentException(
                        'Indica al menos una cantidad mayor que cero.'
                    );
                }

                // Cabecera de recepción.
                $this->q(
                    'INSERT INTO recepciones_compra
                 (empresa_id, solicitud_id, usuario_id, clave)
                 VALUES (?, ?, ?, ?)',
                    array($empresa, $id, $usuario, $clave)
                );

                $recepcionId = (int) $this->db->insert_id();

                // Procesar insumos en orden estable.
                ksort($recepciones);

                foreach ($recepciones as $detalleId => $datos) {

                    $linea = $datos['linea'];
                    $cantidad = $datos['cantidad'];
                    $insumoId = (int) $linea['insumo_id'];

                    $insumo = $this->q(
                        'SELECT id, cantidad_actual
                     FROM insumos
                     WHERE empresa_id = ?
                       AND id = ?
                       AND activo = 1
                     FOR UPDATE',
                        array($empresa, $insumoId)
                    )->row_array();

                    if (!$insumo) {
                        throw new InvalidArgumentException(
                            'Uno de los insumos ya no está activo.'
                        );
                    }

                    $anterior = $aCent(
                        $insumo['cantidad_actual']
                    );

                    $nuevo = $anterior + $cantidad;

                    if ($nuevo > 9999999999) {
                        throw new InvalidArgumentException(
                            'La existencia supera el máximo permitido.'
                        );
                    }

                    // Actualizar inventario.
                    $this->q(
                        'UPDATE insumos
                     SET cantidad_actual = ?
                     WHERE empresa_id = ? AND id = ?',
                        array(
                            $aDecimal($nuevo),
                            $empresa,
                            $insumoId
                        )
                    );

                    // Actualizar cantidad recibida.
                    $this->q(
                        'UPDATE solicitud_compra_detalles
                     SET recibido = recibido + ?
                     WHERE empresa_id = ?
                       AND solicitud_id = ?
                       AND id = ?',
                        array(
                            $aDecimal($cantidad),
                            $empresa,
                            $id,
                            $detalleId
                        )
                    );

                    // Registrar movimiento de inventario.
                    $this->q(
                        'INSERT INTO movimientos_inventario
                     (empresa_id, insumo_id, usuario_id,
                      tipo, cantidad, cantidad_anterior,
                      cantidad_nueva, motivo)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        array(
                            $empresa,
                            $insumoId,
                            $usuario,
                            'entrada',
                            $aDecimal($cantidad),
                            $aDecimal($anterior),
                            $aDecimal($nuevo),
                            'Recepción de compra #' . $id
                        )
                    );

                    $movimientoId = (int) $this->db->insert_id();

                    // Relacionar recepción y movimiento.
                    $this->q(
                        'INSERT INTO recepcion_compra_detalles
                     (empresa_id, recepcion_id, solicitud_id,
                      detalle_id, cantidad, movimiento_id)
                     VALUES (?, ?, ?, ?, ?, ?)',
                        array(
                            $empresa,
                            $recepcionId,
                            $id,
                            $detalleId,
                            $aDecimal($cantidad),
                            $movimientoId
                        )
                    );
                }

                // Comprobar si quedan cantidades pendientes.
                $pendientes = $this->q(
                    'SELECT COUNT(*) AS total
                 FROM solicitud_compra_detalles
                 WHERE empresa_id = ?
                   AND solicitud_id = ?
                   AND recibido < cantidad',
                    array($empresa, $id)
                )->row_array();

                $completa = (int) $pendientes['total'] === 0;

                $nuevoEstado = $completa
                    ? 'recibida'
                    : 'enviada';

                $this->q(
                    'UPDATE solicitudes_compra
                 SET estado = ?,
                     version = version + 1,
                     actualizado_por = ?,
                     actualizado_en = CURRENT_TIMESTAMP
                 WHERE empresa_id = ? AND id = ?',
                    array($nuevoEstado, $usuario, $empresa, $id)
                );

                // Historial de la solicitud.
                $this->q(
                    'INSERT INTO solicitud_compra_eventos
                 (empresa_id, solicitud_id, usuario_id,
                  accion, estado_anterior, estado_nuevo,
                  comentario)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                    array(
                        $empresa,
                        $id,
                        $usuario,
                        $completa
                            ? 'recepcion_total'
                            : 'recepcion_parcial',
                        'enviada',
                        $nuevoEstado,
                        'Recepción #' . $recepcionId
                    )
                );

                return $id;
            }
        );
    }
}
