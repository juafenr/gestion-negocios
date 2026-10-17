<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pago_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('Dinero');
    }

    private function consultar($sql, $valores = array())
    {
        $resultado = $this->db->query($sql, $valores);
        if ($resultado === FALSE) {
            throw new RuntimeException('No se pudo consultar el registro de pagos.');
        }
        return $resultado;
    }

    public function listar($estado, $pagina)
    {
        if (!in_array($estado,array('todos','pendiente','parcial','pagado'),TRUE)) { $estado='todos'; }
        $pagina=max(1,min(100000,(int)$pagina));
        $filtro = $estado === 'todos' ? '' : ' AND p.estado_pago = ?';
        $valores = array($this->empresa_id());
        if ($estado !== 'todos') { $valores[] = $estado; }
        $valores[] = ($pagina - 1) * 30;
        return $this->consultar(
            "SELECT p.*, COALESCE((SELECT SUM(g.total) FROM pagos g
              WHERE g.empresa_id=p.empresa_id AND g.pedido_id=p.id),0) AS abonado
             FROM pedidos p WHERE p.empresa_id=?
             AND p.estado IN ('pendiente','en_preparacion','listo','entregado')"
             . $filtro . ' ORDER BY p.id DESC LIMIT 31 OFFSET ?', $valores
        )->result_array();
    }

    public function resumen($pedido_id)
    {
        $empresa = $this->empresa_id();
        $cuentas = $this->consultar(
            'SELECT c.*, COALESCE((SELECT SUM(g.total) FROM pagos g
             WHERE g.empresa_id=c.empresa_id AND g.pedido_id=c.pedido_id AND g.cuenta_id=c.id),0) AS abonado
             FROM pago_cuentas c WHERE c.empresa_id=? AND c.pedido_id=?
             ORDER BY c.es_principal DESC, c.id', array($empresa, $pedido_id)
        )->result_array();
        $detalles = $this->consultar(
            'SELECT a.*, d.producto_nombre, d.precio_unitario
             FROM pago_cuenta_detalles a JOIN pedido_detalles d
             ON d.empresa_id=a.empresa_id AND d.pedido_id=a.pedido_id AND d.id=a.detalle_id
             WHERE a.empresa_id=? AND a.pedido_id=? ORDER BY a.id', array($empresa, $pedido_id)
        )->result_array();
        $pagos = $this->consultar(
            'SELECT g.*, u.nombre AS usuario_nombre, c.nombre AS cuenta_nombre
             FROM pagos g JOIN usuarios u ON u.empresa_id=g.empresa_id AND u.id=g.usuario_id
             JOIN pago_cuentas c ON c.empresa_id=g.empresa_id AND c.pedido_id=g.pedido_id AND c.id=g.cuenta_id
             WHERE g.empresa_id=? AND g.pedido_id=? ORDER BY g.id DESC', array($empresa, $pedido_id)
        )->result_array();
        $abonado = 0;
        foreach ($pagos as $pago) { $abonado += Dinero::centavos($pago['total']); }
        return array('cuentas'=>$cuentas, 'reparto'=>$detalles, 'pagos'=>$pagos, 'abonado'=>$abonado);
    }

    /** Todas las escrituras comparten el bloqueo usado al confirmar/cancelar. */
    private function transaccion($pedido_id, $accion)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try {
            $empresa = $this->empresa_id();
            $usuario = $this->autenticacion->usuario_actual();
            if (!$usuario || !$usuario->puede('ventas.cobrar')) {
                throw new InvalidArgumentException('No tienes permiso para registrar pagos.');
            }
            if (!$this->db->trans_begin()) { throw new RuntimeException('Transacción no disponible.'); }
            $pedido = $this->consultar('SELECT * FROM pedidos WHERE empresa_id=? AND id=? FOR UPDATE', array($empresa,$pedido_id))->row_array();
            if (!$pedido) { throw new InvalidArgumentException('Pedido no encontrado.'); }
            if (!in_array($pedido['estado'], array('pendiente','en_preparacion','listo','entregado'), TRUE)) {
                throw new InvalidArgumentException('Primero confirma el pedido. Los pedidos cancelados no admiten pagos.');
            }
            // Revalidar el usuario y su permiso en la base, sin confiar en el formulario.
            $autorizado = $this->consultar(
                "SELECT u.id FROM usuarios u JOIN empresas e ON e.id=u.empresa_id
                 JOIN rol_permisos rp ON rp.rol_id=u.rol_id JOIN permisos p ON p.id=rp.permiso_id
                 WHERE u.id=? AND u.empresa_id=? AND u.activo=1 AND e.activa=1 AND p.codigo='ventas.cobrar' LIMIT 1",
                array($usuario->id(),$empresa))->row_array();
            if (!$autorizado) { throw new InvalidArgumentException('No tienes permiso para registrar pagos.'); }
            $accion($pedido, $empresa, $usuario->id());
            if (!$this->db->trans_status() || !$this->db->trans_commit()) { throw new RuntimeException('No se completó la operación.'); }
            return NULL;
        } catch (InvalidArgumentException $e) {
            $this->db->trans_rollback();
            return $e->getMessage();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'Fallo de pagos: '.$e->getMessage());
            return 'No se guardó la operación. Revisa las migraciones e inténtalo nuevamente.';
        } finally {
            $this->db->db_debug = $debug;
        }
    }

    private function sin_pagos($pedido, $empresa)
    {
        $pago = $this->consultar('SELECT id FROM pagos WHERE empresa_id=? AND pedido_id=? LIMIT 1', array($empresa,$pedido['id']))->row_array();
        if ($pago || $pedido['estado_pago'] !== 'pendiente') {
            throw new InvalidArgumentException('Las cuentas solo se pueden organizar antes de registrar el primer pago.');
        }
    }

    public function preparar($pedido_id)
    {
        return $this->transaccion($pedido_id, function ($pedido, $empresa) {
            // Preparar dos veces no crea cuentas duplicadas.
            $actual = $this->consultar('SELECT id FROM pago_cuentas WHERE empresa_id=? AND pedido_id=? LIMIT 1', array($empresa,$pedido['id']))->row_array();
            if ($actual) { return; }
            $this->sin_pagos($pedido,$empresa);
            $detalles = $this->consultar('SELECT * FROM pedido_detalles WHERE empresa_id=? AND pedido_id=? ORDER BY id', array($empresa,$pedido['id']))->result_array();
            $total = 0;
            foreach ($detalles as $d) {
                if ((int)$d['cantidad'] < 1 || (int)$d['cantidad'] > 999) { throw new InvalidArgumentException('Revisa las cantidades del pedido.'); }
                $total += (int)$d['cantidad'] * Dinero::centavos($d['precio_unitario']);
            }
            if (!$detalles || $total !== Dinero::centavos($pedido['total'])) {
                throw new InvalidArgumentException('El total del pedido no coincide con sus productos. Revisa el pedido antes de cobrar.');
            }
            $this->consultar('INSERT INTO pago_cuentas (empresa_id,pedido_id,nombre,es_principal,total) VALUES (?,?,?,1,?)', array($empresa,$pedido['id'],'Cuenta general',Dinero::decimal($total)));
            $cuenta = $this->db->insert_id();
            foreach ($detalles as $d) {
                $this->consultar('INSERT INTO pago_cuenta_detalles (empresa_id,pedido_id,cuenta_id,detalle_id,cantidad) VALUES (?,?,?,?,?)', array($empresa,$pedido['id'],$cuenta,$d['id'],$d['cantidad']));
            }
            Dinero::decimal($total + Dinero::propina($total));
            $this->repartir_propina($pedido['id'], $empresa);
            if ($total === 0) {
                $this->consultar("UPDATE pedidos SET estado_pago='pagado' WHERE empresa_id=? AND id=?", array($empresa,$pedido['id']));
            }
        });
    }

    public function separar($pedido_id, $nombre, $cantidades)
    {
        if (!is_string($nombre) || trim($nombre)==='' || mb_strlen(trim($nombre),'UTF-8')>80 || !is_array($cantidades) || count($cantidades)>500) {
            return 'Escribe un nombre de hasta 80 caracteres y selecciona los productos.';
        }
        $seleccion = array();
        foreach ($cantidades as $id=>$cantidad) {
            if (!ctype_digit((string)$id) || strlen((string)$id)>10 || (int)$id<1 || !is_string($cantidad) || !preg_match('/^[0-9]{1,3}$/D',$cantidad)) {
                return 'Las cantidades para separar deben ser números enteros entre 0 y 999.';
            }
            if ((int)$cantidad>0) { $seleccion[(int)$id]=(int)$cantidad; }
        }
        if (!$seleccion) { return 'Selecciona al menos una unidad para la cuenta.'; }
        return $this->transaccion($pedido_id, function ($pedido,$empresa) use ($nombre,$seleccion) {
            $this->sin_pagos($pedido,$empresa);
            $cuenta = $this->consultar('SELECT * FROM pago_cuentas WHERE empresa_id=? AND pedido_id=? AND es_principal=1', array($empresa,$pedido['id']))->row_array();
            if (!$cuenta) { throw new InvalidArgumentException('Primero prepara las cuentas del pedido.'); }
            $existente = $this->consultar('SELECT id FROM pago_cuentas WHERE empresa_id=? AND pedido_id=? AND nombre=?', array($empresa,$pedido['id'],trim($nombre)))->row_array();
            if ($existente) { throw new InvalidArgumentException('Ya existe una cuenta con ese nombre.'); }
            $lineas = array(); $total = 0;
            foreach ($seleccion as $id=>$cantidad) {
                $linea = $this->consultar(
                    'SELECT a.*, d.precio_unitario FROM pago_cuenta_detalles a JOIN pedido_detalles d
                     ON d.empresa_id=a.empresa_id AND d.pedido_id=a.pedido_id AND d.id=a.detalle_id
                     WHERE a.empresa_id=? AND a.pedido_id=? AND a.cuenta_id=? AND a.detalle_id=?',
                    array($empresa,$pedido['id'],$cuenta['id'],$id))->row_array();
                if (!$linea || $cantidad>(int)$linea['cantidad']) { throw new InvalidArgumentException('Una cantidad supera lo que queda en la cuenta general. Recarga la página.'); }
                $lineas[] = array($linea,$cantidad);
                $total += $cantidad * Dinero::centavos($linea['precio_unitario']);
            }
            $restante = Dinero::centavos($cuenta['total'])-$total;
            $this->consultar('INSERT INTO pago_cuentas (empresa_id,pedido_id,nombre,total) VALUES (?,?,?,?)', array($empresa,$pedido['id'],trim($nombre),Dinero::decimal($total)));
            $nueva = $this->db->insert_id();
            foreach ($lineas as $par) {
                list($linea,$cantidad)=$par;
                $this->consultar('INSERT INTO pago_cuenta_detalles (empresa_id,pedido_id,cuenta_id,detalle_id,cantidad) VALUES (?,?,?,?,?)', array($empresa,$pedido['id'],$nueva,$linea['detalle_id'],$cantidad));
                if ($cantidad===(int)$linea['cantidad']) {
                    $this->consultar('DELETE FROM pago_cuenta_detalles WHERE id=? AND empresa_id=?', array($linea['id'],$empresa));
                } else {
                    $this->consultar('UPDATE pago_cuenta_detalles SET cantidad=cantidad-? WHERE id=? AND empresa_id=?', array($cantidad,$linea['id'],$empresa));
                }
            }
            $this->consultar('UPDATE pago_cuentas SET total=? WHERE id=? AND empresa_id=?', array(Dinero::decimal($restante),$cuenta['id'],$empresa));
            $this->repartir_propina($pedido['id'], $empresa);
        });
    }

    private function repartir_propina($pedido_id, $empresa)
    {
        $cuentas = $this->consultar('SELECT id,total FROM pago_cuentas WHERE empresa_id=? AND pedido_id=? ORDER BY id', array($empresa,$pedido_id))->result_array();
        $subtotales = array();
        foreach ($cuentas as $c) { $subtotales[(int)$c['id']] = Dinero::centavos($c['total']); }
        foreach (Dinero::repartirPropina($subtotales) as $id=>$propina) {
            $this->consultar('UPDATE pago_cuentas SET propina=? WHERE empresa_id=? AND pedido_id=? AND id=?', array(Dinero::decimal($propina),$empresa,$pedido_id,$id));
        }
    }

    public function reiniciar($pedido_id)
    {
        return $this->transaccion($pedido_id, function ($pedido,$empresa) {
            $this->sin_pagos($pedido,$empresa);
            $this->consultar('DELETE FROM pago_cuenta_detalles WHERE empresa_id=? AND pedido_id=?', array($empresa,$pedido['id']));
            $this->consultar('DELETE FROM pago_cuentas WHERE empresa_id=? AND pedido_id=?', array($empresa,$pedido['id']));
        });
    }

    public function registrar($pedido_id, $cuenta_id, $datos)
    {
        try {
            $montos = array();
            foreach (array('efectivo','credito','debito','recibido') as $campo) {
                $montos[$campo] = Dinero::centavos($datos[$campo] ?? '');
            }
            $total = $montos['efectivo']+$montos['credito']+$montos['debito'];
            Dinero::decimal($total);
            if ($total<=0) { return 'Ingresa un monto mayor que cero en al menos un método de pago.'; }
            if ($montos['recibido'] < $montos['efectivo'] || ($montos['efectivo']===0 && $montos['recibido']!==0)) {
                return 'El efectivo recibido debe cubrir lo aplicado. Si no hay pago en efectivo, escribe 0 en recibido.';
            }
            $clave = $datos['clave'] ?? '';
            if (!is_string($clave) || !preg_match('/^[a-f0-9]{64}$/D',$clave)) { return 'Recarga el formulario de pago e inténtalo nuevamente.'; }
        } catch (InvalidArgumentException $e) { return $e->getMessage(); }
        return $this->transaccion($pedido_id, function ($pedido,$empresa,$usuario) use ($cuenta_id,$montos,$total,$clave) {
            // Una clave identifica un único envío, incluso ante reintentos de red.
            $previo = $this->consultar('SELECT * FROM pagos WHERE empresa_id=? AND clave=?', array($empresa,$clave))->row_array();
            if ($previo) {
                if ((int)$previo['pedido_id']!==(int)$pedido['id'] || (int)$previo['cuenta_id']!==$cuenta_id || (int)$previo['usuario_id']!==$usuario) {
                    throw new InvalidArgumentException('Este formulario ya se utilizó. Recarga la página.');
                }
                foreach ($montos as $campo=>$valor) {
                    if (Dinero::centavos($previo[$campo])!==$valor) { throw new InvalidArgumentException('El envío ya fue registrado con otros montos. Revisa el historial.'); }
                }
                return;
            }
            $cuenta = $this->consultar('SELECT * FROM pago_cuentas WHERE empresa_id=? AND pedido_id=? AND id=?', array($empresa,$pedido['id'],$cuenta_id))->row_array();
            if (!$cuenta) { throw new InvalidArgumentException('La cuenta no pertenece a este pedido.'); }
            $sumas = $this->consultar('SELECT COALESCE(SUM(total),0) AS total, COALESCE(SUM(propina),0) AS propina FROM pago_cuentas WHERE empresa_id=? AND pedido_id=?', array($empresa,$pedido['id']))->row_array();
            if (Dinero::centavos($sumas['total'])!==Dinero::centavos($pedido['total']) || Dinero::centavos($sumas['propina'])!==Dinero::propina(Dinero::centavos($pedido['total']))) { throw new InvalidArgumentException('Las cuentas no coinciden con el total del pedido.'); }
            $sumas = $this->consultar('SELECT COALESCE(SUM(total),0) AS total, COALESCE(SUM(CASE WHEN cuenta_id=? THEN total ELSE 0 END),0) AS cuenta FROM pagos WHERE empresa_id=? AND pedido_id=?', array($cuenta_id,$empresa,$pedido['id']))->row_array();
            $abonado = Dinero::centavos($sumas['total']);
            $consumo = Dinero::centavos($cuenta['total']);
            $pagado_cuenta = Dinero::centavos($sumas['cuenta']);
            $saldo = $consumo + Dinero::centavos($cuenta['propina']) - $pagado_cuenta;
            $total_pedido = Dinero::centavos($pedido['total']) + Dinero::propina(Dinero::centavos($pedido['total']));
            // Los abonos cubren primero el consumo; después la propina.
            $propina_pagada = max(0,$pagado_cuenta+$total-$consumo)-max(0,$pagado_cuenta-$consumo);
            if ($pedido['estado_pago']==='pagado' || $total>$saldo || $total>$total_pedido-$abonado) {
                throw new InvalidArgumentException('El pago supera el saldo disponible o la cuenta ya está pagada. Recarga y revisa el saldo.');
            }
            $this->consultar(
                'INSERT INTO pagos (empresa_id,pedido_id,cuenta_id,usuario_id,clave,efectivo,credito,debito,recibido,cambio,propina,total) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                array($empresa,$pedido['id'],$cuenta_id,$usuario,$clave,Dinero::decimal($montos['efectivo']),Dinero::decimal($montos['credito']),Dinero::decimal($montos['debito']),Dinero::decimal($montos['recibido']),Dinero::decimal($montos['recibido']-$montos['efectivo']),Dinero::decimal($propina_pagada),Dinero::decimal($total)));
            $estado = $abonado+$total===$total_pedido ? 'pagado' : 'parcial';
            $this->consultar('UPDATE pedidos SET estado_pago=? WHERE empresa_id=? AND id=?', array($estado,$empresa,$pedido['id']));
        });
    }
}
