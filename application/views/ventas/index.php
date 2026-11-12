<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Ventas','seccion'=>'ventas','usuario'=>$usuario));
 ?>
<p class="sobretitulo">VENTAS COMPLETADAS</p><h1>Historial de ventas</h1>
<p class="descripcion">Una venta por pedido pagado. El consumo y la propina se muestran por separado.</p>
<?php $this->load->view('plantilla/filtros', array('filtro_ruta'=>'ventas', 'filtro_campos'=>array(
'desde'=>array('tipo'=>'date','etiqueta'=>'Desde','obligatorio'=>TRUE), 'hasta'=>array('tipo'=>'date','etiqueta'=>'Hasta','obligatorio'=>TRUE)
), 'filtro_valores'=>array('desde'=>$desde,'hasta'=>$hasta), 'filtro_extras'=>array())); ?>
<div class="resumen-pagos">
<div class="recuadro"><span>Pedidos vendidos</span><strong><?= (int)$resumen['cantidad'] ?></strong></div>
<div class="recuadro"><span>Ventas sin propina</span><strong><?= negocio_moneda($resumen['consumo']) ?></strong></div>
<div class="recuadro"><span>Propinas</span><strong><?= negocio_moneda($resumen['propina']) ?></strong></div>
<div class="recuadro"><span>Total cobrado</span><strong><?= negocio_moneda($resumen['total']) ?></strong></div>
</div>
<p>Efectivo aplicado: <strong><?= negocio_moneda($resumen['efectivo']) ?></strong> · Crédito: <strong><?= negocio_moneda($resumen['credito']) ?></strong> · Débito: <strong><?= negocio_moneda($resumen['debito']) ?></strong></p>
<p class="nota">El período corresponde a la fecha en que se completó el pago. Los abonos de pedidos todavía pendientes no son ventas completadas ni forman parte de este resumen.</p>
<?php $this->load->view('plantilla/resultados', array('resultado_total'=>(int)$resumen['cantidad'], 'resultado_mostrados'=>count($ventas), 'resultado_pagina'=>$pagina)); ?>
<div class="tabla"><table><thead><tr><th>Pedido vendido</th><th>Fecha de venta</th><th>Referencia</th><th>Consumo</th><th>Propina</th><th>Total</th><th>Acción</th></tr></thead><tbody>
<?php foreach ($ventas as $v): ?><tr><td>#<?= (int)$v['pedido_numero'] ?></td><td><?= html_escape($v['fecha']) ?></td><td><?= html_escape($v['referencia']) ?></td>
<td><?= negocio_moneda($v['subtotal']) ?></td><td><?= negocio_moneda($v['propina']) ?></td><td><?= negocio_moneda($v['total']) ?></td>
<td><a class="boton boton-secundario boton-compacto" href="<?= html_escape(site_url('ventas/'.(int)$v['id'])) ?>">Ver venta</a></td></tr><?php endforeach; ?>
<?php if (!$ventas): ?><tr><td colspan="7">No hay ventas completadas en este período.</td></tr><?php endif; ?></tbody></table></div>
<?php $this->load->view('plantilla/paginacion', array('ruta_paginacion'=>'ventas','parametros_paginacion'=>array('desde'=>$desde,'hasta'=>$hasta,'pagina_dias'=>$pagina_dias),'pagina_actual'=>$pagina,'cantidad_paginas'=>$paginas,'hay_siguiente'=>FALSE)); ?>
<h2 class="subtitulo-linea">Resumen por fecha</h2>
<div class="tabla"><table><thead><tr><th>Fecha</th><th>Ventas</th><th>Consumo</th><th>Propina</th><th>Total cobrado</th></tr></thead><tbody>
<?php foreach ($dias as $d): ?><tr><td><?= html_escape($d['dia']) ?></td><td><?= (int)$d['cantidad'] ?></td><td><?= negocio_moneda($d['consumo']) ?></td><td><?= negocio_moneda($d['propina']) ?></td><td><?= negocio_moneda($d['total']) ?></td></tr><?php endforeach; ?>
<?php if (!$dias): ?><tr><td colspan="5">No hay ventas para resumir.</td></tr><?php endif; ?></tbody></table></div>
<?php $this->load->view('plantilla/paginacion',array('ruta_paginacion'=>'ventas','parametros_paginacion'=>array('desde'=>$desde,'hasta'=>$hasta,'pagina'=>$pagina),'pagina_actual'=>$pagina_dias,'cantidad_paginas'=>$paginas_dias,'hay_siguiente'=>FALSE,'parametro_pagina'=>'pagina_dias','etiqueta_paginacion'=>'Páginas del resumen por fecha')); ?>
<?php $this->load->view('plantilla/pie'); ?>
