<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Ventas','seccion'=>'ventas','usuario'=>$usuario));
$url=function($p,$d) use ($desde,$hasta) { return site_url('ventas').'?'.http_build_query(array('desde'=>$desde,'hasta'=>$hasta,'pagina'=>$p,'pagina_dias'=>$d)); }; ?>
<p class="sobretitulo">VENTAS COMPLETADAS</p><h1>Historial de ventas</h1>
<p class="descripcion">Una venta por pedido pagado. El consumo y la propina se muestran por separado.</p>
<form method="get" action="<?= html_escape(site_url('ventas')) ?>" class="recuadro filtros-pagos">
<label for="desde">Desde</label><input id="desde" name="desde" type="date" value="<?= html_escape($desde) ?>" required>
<label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="<?= html_escape($hasta) ?>" required>
<button type="submit">Consultar</button></form>
<div class="resumen-pagos">
<div class="recuadro"><span>Pedidos vendidos</span><strong><?= (int)$resumen['cantidad'] ?></strong></div>
<div class="recuadro"><span>Ventas sin propina</span><strong><?= negocio_moneda($resumen['consumo']) ?></strong></div>
<div class="recuadro"><span>Propinas</span><strong><?= negocio_moneda($resumen['propina']) ?></strong></div>
<div class="recuadro"><span>Total cobrado</span><strong><?= negocio_moneda($resumen['total']) ?></strong></div>
</div>
<p>Efectivo aplicado: <strong><?= negocio_moneda($resumen['efectivo']) ?></strong> · Crédito: <strong><?= negocio_moneda($resumen['credito']) ?></strong> · Débito: <strong><?= negocio_moneda($resumen['debito']) ?></strong></p>
<p class="nota">El período corresponde a la fecha en que se completó el pago. Los abonos de pedidos todavía pendientes no son ventas completadas ni forman parte de este resumen.</p>
<div class="tabla"><table><thead><tr><th>Pedido vendido</th><th>Fecha de venta</th><th>Referencia</th><th>Consumo</th><th>Propina</th><th>Total</th><th>Acción</th></tr></thead><tbody>
<?php foreach ($ventas as $v): ?><tr><td>#<?= (int)$v['pedido_numero'] ?></td><td><?= html_escape($v['fecha']) ?></td><td><?= html_escape($v['referencia']) ?></td>
<td><?= negocio_moneda($v['subtotal']) ?></td><td><?= negocio_moneda($v['propina']) ?></td><td><?= negocio_moneda($v['total']) ?></td>
<td><a class="boton boton-secundario boton-compacto" href="<?= html_escape(site_url('ventas/'.(int)$v['id'])) ?>">Ver venta</a></td></tr><?php endforeach; ?>
<?php if (!$ventas): ?><tr><td colspan="7">No hay ventas completadas en este período.</td></tr><?php endif; ?></tbody></table></div>
<nav class="paginacion-pagos" aria-label="Paginación de ventas">
<?php if ($pagina>1): ?><a class="boton boton-secundario" href="<?= html_escape($url($pagina-1,$pagina_dias)) ?>">Anterior</a><?php endif; ?>
<span>Página <?= $pagina ?> de <?= $paginas ?></span>
<?php if ($pagina<$paginas): ?><a class="boton boton-secundario" href="<?= html_escape($url($pagina+1,$pagina_dias)) ?>">Siguiente</a><?php endif; ?></nav>
<h2 class="subtitulo-linea">Resumen por fecha</h2>
<div class="tabla"><table><thead><tr><th>Fecha</th><th>Ventas</th><th>Consumo</th><th>Propina</th><th>Total cobrado</th></tr></thead><tbody>
<?php foreach ($dias as $d): ?><tr><td><?= html_escape($d['dia']) ?></td><td><?= (int)$d['cantidad'] ?></td><td><?= negocio_moneda($d['consumo']) ?></td><td><?= negocio_moneda($d['propina']) ?></td><td><?= negocio_moneda($d['total']) ?></td></tr><?php endforeach; ?>
<?php if (!$dias): ?><tr><td colspan="5">No hay ventas para resumir.</td></tr><?php endif; ?></tbody></table></div>
<nav class="paginacion-pagos" aria-label="Paginación del resumen diario">
<?php if ($pagina_dias>1): ?><a class="boton boton-secundario" href="<?= html_escape($url($pagina,$pagina_dias-1)) ?>">Fechas anteriores en la lista</a><?php endif; ?>
<span>Página de fechas <?= $pagina_dias ?> de <?= $paginas_dias ?></span>
<?php if ($pagina_dias<$paginas_dias): ?><a class="boton boton-secundario" href="<?= html_escape($url($pagina,$pagina_dias+1)) ?>">Más fechas</a><?php endif; ?></nav>
<?php $this->load->view('plantilla/pie'); ?>
