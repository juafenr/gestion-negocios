<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Solicitudes de compra','seccion'=>'compras','usuario'=>$usuario)); ?>
<div class="titulo-acciones"><div><p class="sobretitulo">DOCUMENTOS DE COMPRA</p><h1>Solicitudes de compra</h1><p class="descripcion">Prepara y revisa lo que necesitas solicitar a un proveedor.</p></div>
<?php if ($usuario->puede('compras.gestionar')): ?><a class="boton" href="<?= html_escape(site_url('compras/crear')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/agregar.svg')) ?>" alt="">Nueva solicitud</a><?php endif; ?></div>
<?php if ($usuario->puede('compras.gestionar') && $usuario->puede('inventario.ver')): ?><p><a class="boton boton-secundario" href="<?= html_escape(site_url('compras/seleccionar')) ?>">Seleccionar desde inventario</a></p><?php endif; ?>
<?php if ($mensaje): ?><p role="status" class="recuadro aviso"><?= html_escape($mensaje) ?></p><?php endif; ?>
<?php if (!empty($creadas)): ?><ul><?php foreach ($creadas as $creada): ?><li><a href="<?= html_escape(site_url('compras/'.(int)$creada['id'])) ?>">Revisar borrador de <?= html_escape($creada['proveedor']) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
<?php if ($usuario->puede('compras.gestionar') && $usuario->puede('proveedores.gestionar')): ?><p><a class="boton boton-secundario" href="<?= html_escape(site_url('compras/proveedor_nuevo')) ?>">Registrar proveedor</a></p><?php endif; ?>
<div class="tabla"><table><thead><tr><th>Número</th><th>Fecha</th><th>Proveedor</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php foreach ($solicitudes as $s): ?><tr><td>#<?= (int)$s['numero'] ?></td><td><?= html_escape($s['fecha']) ?></td><td><?= html_escape($s['proveedor_nombre']) ?></td><td><span class="estado"><?= html_escape($s['estado']) ?></span></td>
<td><a class="boton boton-secundario boton-compacto" href="<?= html_escape(site_url('compras/'.(int)$s['id'])) ?>">Revisar</a></td></tr><?php endforeach; ?>
<?php if (!$solicitudes): ?><tr><td colspan="5">Todavía no hay solicitudes de compra.</td></tr><?php endif; ?></tbody></table></div>
<nav class="paginacion-pagos" aria-label="Solicitudes">
<?php if ($pagina>1): ?><a class="boton boton-secundario" href="<?= html_escape(site_url('compras').'?pagina='.($pagina-1)) ?>">Anterior</a><?php endif; ?>
<span>Página <?= $pagina ?> de <?= $paginas ?></span>
<?php if ($pagina<$paginas): ?><a class="boton boton-secundario" href="<?= html_escape(site_url('compras').'?pagina='.($pagina+1)) ?>">Siguiente</a><?php endif; ?></nav>
<?php $this->load->view('plantilla/pie'); ?>
