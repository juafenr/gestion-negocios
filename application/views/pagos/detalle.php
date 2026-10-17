<?php
$this->load->view('plantilla/cabecera',array('titulo'=>'Cobrar pedido','seccion'=>'pagos','usuario'=>$usuario));
$consumo=Dinero::centavos($pedido['total']);
$propina=Dinero::propina($consumo);
$total=$consumo+$propina;
$saldo=max(0,$total-$abonado);
$habilitado=in_array($pedido['estado'],array('pendiente','en_preparacion','listo','entregado'),TRUE);
$puede_cobrar=$usuario->puede('ventas.cobrar') && $habilitado;
$organizar=$puede_cobrar && !$pagos && $pedido['estado_pago']==='pendiente';
$general=NULL;
foreach ($cuentas as $c) { if ((int)$c['es_principal']===1) { $general=$c; } }
$lineas_generales=array();
foreach ($reparto as $d) { if ($general && $d['cuenta_id']===$general['id']) { $lineas_generales[]=$d; } }
?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('pedidos/'.(int)$pedido['id'])) ?>">Volver al pedido</a>
<a class="boton boton-secundario" href="<?= html_escape(site_url('pagos')) ?>">Todos los cobros</a></p>
<h1>Pago del pedido #<?= (int)$pedido['numero'] ?></h1>
<p><?= html_escape($pedido['referencia']) ?> · Preparación: <?= html_escape(str_replace('_',' ',$pedido['estado'])) ?> · Pago: <strong><?= html_escape($pedido['estado_pago']) ?></strong></p>
<?php if ($error): ?><p class="error" role="alert"><?= html_escape($error) ?></p><?php endif; ?>
<?php if ($mensaje): ?><p class="recuadro aviso" role="status"><?= html_escape($mensaje) ?></p><?php endif; ?>
<p>Consumo: <strong><?= Dinero::mostrar($consumo) ?></strong> · Propina automática (10%): <strong><?= Dinero::mostrar($propina) ?></strong></p>
<div class="resumen-pagos">
    <div class="recuadro"><span>Total con propina</span><strong><?= Dinero::mostrar($total) ?></strong></div>
    <div class="recuadro"><span>Pagado</span><strong><?= Dinero::mostrar($abonado) ?></strong></div>
    <div class="recuadro saldo-pago"><span>Saldo pendiente</span><strong><?= Dinero::mostrar($saldo) ?></strong></div>
</div>
<?php if (!$habilitado): ?><p class="recuadro aviso">Solo se pueden cobrar pedidos confirmados. Un pedido cancelado no admite pagos.</p><?php endif; ?>
<?php if (!$cuentas && $organizar): ?>
<section class="recuadro"><h2>Preparar el cobro</h2>
<p>Todos los productos se colocarán en una cuenta general. Después podrás separar los productos de cada persona o cobrar todo junto.</p>
<?= form_open('pagos/preparar/'.(int)$pedido['id']) ?><button type="submit">Preparar cuenta</button><?= form_close() ?>
</section>
<?php endif; ?>
<?php if ($cuentas): ?>
<h2>Cuentas del pedido</h2>
<div class="cuentas-pagos">
<?php foreach ($cuentas as $cuenta): $restante=max(0,Dinero::centavos($cuenta['total'])+Dinero::centavos($cuenta['propina'])-Dinero::centavos($cuenta['abonado'])); ?>
<section class="recuadro">
    <h3><?= html_escape($cuenta['nombre']) ?></h3>
    <ul>
    <?php $tiene_lineas=FALSE; foreach ($reparto as $linea): if ($linea['cuenta_id']!==$cuenta['id']) { continue; } $tiene_lineas=TRUE; ?>
        <li><?= (int)$linea['cantidad'] ?> × <?= html_escape($linea['producto_nombre']) ?> — <?= Dinero::mostrar((int)$linea['cantidad']*Dinero::centavos($linea['precio_unitario'])) ?></li>
    <?php endforeach; ?>
    </ul>
    <?php if (!$tiene_lineas): ?><p class="descripcion">Todos los productos se asignaron a otras cuentas.</p><?php endif; ?>
    <p>Consumo: <strong><?= Dinero::mostrar(Dinero::centavos($cuenta['total'])) ?></strong> · Propina: <?= Dinero::mostrar(Dinero::centavos($cuenta['propina'])) ?></p>
    <p>Total con propina: <strong><?= Dinero::mostrar(Dinero::centavos($cuenta['total'])+Dinero::centavos($cuenta['propina'])) ?></strong> · Pagado: <?= Dinero::mostrar(Dinero::centavos($cuenta['abonado'])) ?></p>
    <p>Saldo: <strong><?= Dinero::mostrar($restante) ?></strong> <?= $restante===0 ? '— Saldada' : '' ?></p>
</section>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php if ($organizar && $general): ?>
<section class="recuadro">
<h2>Separar la cuenta de una persona</h2>
<p>Escribe su nombre y cuántas unidades de cada producto pagará. Puedes repetir este paso para varias personas; lo que no asignes queda en la cuenta general. La propina se reparte proporcionalmente al consumo; el redondeo puede ajustar un centavo entre cuentas.</p>
<?php if ($lineas_generales): ?>
<?= form_open('pagos/separar/'.(int)$pedido['id']) ?>
<p><label for="nombre_cuenta">Nombre de la cuenta</label><input type="text" name="nombre" id="nombre_cuenta" maxlength="80" placeholder="Ejemplo: Juan" required></p>
<div class="tabla"><table><thead><tr><th>Producto</th><th>Precio por unidad</th><th>Unidades restantes</th><th>Unidades para esta persona</th></tr></thead><tbody>
<?php foreach ($lineas_generales as $d): ?>
<tr><td><?= html_escape($d['producto_nombre']) ?></td><td><?= Dinero::mostrar(Dinero::centavos($d['precio_unitario'])) ?></td><td><?= (int)$d['cantidad'] ?></td><td><input class="cantidad-cuenta" type="number" name="cantidades[<?= (int)$d['detalle_id'] ?>]" value="0" min="0" max="<?= (int)$d['cantidad'] ?>" step="1" required aria-label="Unidades de <?= html_escape($d['producto_nombre']) ?>"></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<p><button type="submit">Crear cuenta separada</button></p>
<?= form_close() ?>
<?php else: ?><p>Ya se repartieron todos los productos.</p><?php endif; ?>
<details><summary>Volver a organizar las cuentas</summary>
<p>Esto quita la distribución actual. Los productos del pedido se conservan. Solo está disponible antes del primer pago.</p>
<?= form_open('pagos/reiniciar/'.(int)$pedido['id']) ?>
<p><label><input type="checkbox" name="confirmar" value="1" required> Quiero quitar esta distribución.</label></p>
<button type="submit" class="boton-secundario">Reiniciar distribución</button><?= form_close() ?>
</details>
</section>
<?php endif; ?>
<?php if ($puede_cobrar && $cuentas && $saldo>0 && $pedido['estado_pago']!=='pagado'): ?>
<section class="recuadro">
<h2>Registrar pago</h2>
<p>Indica cuánto se aplica por cada método. Para un pago mixto, completa más de uno. Puedes abonar una parte del saldo. Los abonos cubren primero el consumo y después la propina de la cuenta.</p>
<p class="aviso">Revisa la distribución antes de cobrar: después del primer pago no podrás cambiar las cuentas.</p>
<?= form_open('pagos/registrar/'.(int)$pedido['id'],array('id'=>'formulario-pago')) ?>
<input type="hidden" name="clave" value="<?= html_escape($clave) ?>">
<p><label for="cuenta_id">Cuenta que paga</label><select name="cuenta_id" id="cuenta_id" required>
<?php foreach ($cuentas as $cuenta): $restante=Dinero::centavos($cuenta['total'])+Dinero::centavos($cuenta['propina'])-Dinero::centavos($cuenta['abonado']); if ($restante<=0) { continue; } ?>
<option value="<?= (int)$cuenta['id'] ?>" data-saldo="<?= $restante ?>"><?= html_escape($cuenta['nombre']) ?> — saldo <?= Dinero::mostrar($restante) ?></option>
<?php endforeach; ?>
</select></p>
<div class="campos-pagos">
<p><label for="efectivo">Efectivo aplicado (Q)</label><input type="number" id="efectivo" name="efectivo" min="0" max="9999999999.99" step="0.01" value="0.00" required></p>
<p><label for="credito">Tarjeta de crédito (Q)</label><input type="number" id="credito" name="credito" min="0" max="9999999999.99" step="0.01" value="0.00" required></p>
<p><label for="debito">Tarjeta de débito (Q)</label><input type="number" id="debito" name="debito" min="0" max="9999999999.99" step="0.01" value="0.00" required></p>
<p><label for="recibido">Efectivo recibido (Q)</label><input type="number" id="recibido" name="recibido" min="0" max="9999999999.99" step="0.01" value="0.00" required><span class="descripcion">Lo que entrega el cliente en billetes o monedas.</span></p>
</div>
<p id="resumen-cobro" role="status" aria-live="polite">El cambio se calcula con el efectivo recibido menos el efectivo aplicado.</p>
<p class="descripcion">Ejemplo: consumo Q 100 + propina Q 10 = Q 110. Efectivo aplicado Q 40, tarjeta Q 70 y efectivo recibido Q 50: cambio Q 10.</p>
<p class="descripcion">Este registro no procesa tarjetas. No ingreses números de tarjeta, PIN ni códigos de seguridad.</p>
<button type="submit" id="registrar-pago"><img class="icono" src="<?= html_escape(base_url('assets/iconos/guardar.svg')) ?>" alt="">Registrar pago</button>
<?= form_close() ?>
</section>
<script src="<?= html_escape(base_url('assets/pagos.js')) ?>" defer></script>
<?php endif; ?>
<section class="recuadro"><h2>Historial de pagos</h2>
<?php if (!$pagos): ?><p>Todavía no hay pagos registrados.</p><?php else: ?>
<div class="tabla"><table>
<thead><tr><th>Fecha</th><th>Cuenta</th><th>Efectivo aplicado</th><th>Crédito</th><th>Débito</th><th>Efectivo recibido</th><th>Cambio</th><th>Propina incluida</th><th>Total aplicado</th><th>Registró</th></tr></thead>
<tbody><?php foreach ($pagos as $p): ?>
<tr><td><?= html_escape($p['creado_en']) ?></td><td><?= html_escape($p['cuenta_nombre']) ?></td>
<?php foreach (array('efectivo','credito','debito','recibido','cambio','propina','total') as $campo): ?><td><?= Dinero::mostrar(Dinero::centavos($p[$campo])) ?></td><?php endforeach; ?>
<td><?= html_escape($p['usuario_nombre']) ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<p class="descripcion">Los pagos registrados se conservan. Esta versión no incluye devoluciones ni anulación de cobros.</p>
<?php endif; ?>
</section>
<?php $this->load->view('plantilla/pie'); ?>
