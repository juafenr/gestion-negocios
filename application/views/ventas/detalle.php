<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Detalle de venta','seccion'=>'ventas','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('ventas')) ?>">Volver a ventas</a></p>
<section class="recuadro"><h1>Venta del pedido #<?= (int)$venta['pedido_numero'] ?></h1>
<p><?= html_escape($venta['referencia']) ?> · <?= html_escape($venta['fecha']) ?></p>
<p>Registró el cierre: <?= html_escape($venta['responsable'] ?? 'No consta en el registro histórico') ?></p>
<div class="tabla"><table><thead><tr><th>Producto</th><th class="tabla-numero">Cantidad</th><th class="tabla-numero">Precio unitario</th><th class="tabla-numero">Subtotal</th></tr></thead><tbody>
<?php foreach ($detalles as $d): ?><tr><td><?= html_escape($d['producto_nombre']) ?></td><td class="tabla-numero"><?= (int)$d['cantidad'] ?></td><td class="tabla-numero"><?= negocio_moneda($d['precio_unitario']) ?></td><td class="tabla-numero"><?= negocio_moneda($d['subtotal']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<p class="total-pedido">Consumo <?= negocio_moneda($venta['subtotal']) ?> · Propina <?= negocio_moneda($venta['propina']) ?> · Total <?= negocio_moneda($venta['total']) ?></p>
<p>Efectivo aplicado: <?= negocio_moneda($venta['efectivo']) ?> · Crédito: <?= negocio_moneda($venta['credito']) ?> · Débito: <?= negocio_moneda($venta['debito']) ?></p>
<?php if ($usuario->puede('pedidos.ver')): ?><a class="boton boton-secundario" href="<?= html_escape(site_url('pedidos/'.(int)$venta['pedido_id'])) ?>">Ver pedido</a><?php endif; ?>
<?php if ($usuario->puede('pedidos.ver') && $usuario->puede('ventas.ver')): ?><a class="boton boton-secundario" href="<?= html_escape(site_url('pagos/'.(int)$venta['pedido_id'])) ?>">Ver pagos</a><?php endif; ?>
</section><?php $this->load->view('plantilla/pie'); ?>
