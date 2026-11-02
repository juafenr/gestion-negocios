<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Revisar solicitud','seccion'=>'compras','usuario'=>$usuario));$s=$solicitud; ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('compras')) ?>">Volver a solicitudes</a></p>
<?php if ($error): ?><p class="error" role="alert"><?= html_escape($error) ?></p><?php endif; ?>
<section class="recuadro"><h1>Solicitud #<?= (int)$s['numero'] ?></h1>
<p>Fecha: <strong><?= html_escape($s['fecha']) ?></strong> · Estado: <span class="estado"><?= html_escape($s['estado']) ?></span></p>
<h2><?= html_escape($s['proveedor_nombre']) ?></h2>
<?php foreach (array('contacto'=>'Contacto','telefono'=>'Teléfono','correo'=>'Correo','direccion'=>'Dirección') as $campo=>$etiqueta): ?>
<?php if ($s['proveedor_'.$campo]!==''): ?><p><?= $etiqueta ?>: <?= html_escape($s['proveedor_'.$campo]) ?></p><?php endif; ?><?php endforeach; ?>
<div class="tabla"><table><thead><tr><th>Insumo</th><th>Cantidad solicitada</th><th>Unidad</th></tr></thead><tbody>
<?php foreach ($lineas as $l): ?><tr><td><?= html_escape($l['insumo_nombre']) ?></td><td><?= html_escape($l['cantidad']) ?></td><td><?= html_escape($l['unidad_medida']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<h2 class="subtitulo-linea">Observaciones</h2><p><?= nl2br(html_escape($s['observaciones']!=='' ? $s['observaciones'] : 'Sin observaciones')) ?></p>
<div class="acciones-documento">
<?php if ($s['estado']==='borrador' && $usuario->puede('compras.gestionar')): ?><a class="boton" href="<?= html_escape(site_url('compras/'.(int)$s['id'].'/editar')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/editar.svg')) ?>" alt="">Editar borrador</a><?php endif; ?>
<?php if ($usuario->puede('compras.exportar')): ?><a class="boton boton-secundario" href="<?= html_escape(site_url('compras/'.(int)$s['id'].'/documento')) ?>">Copiar o descargar</a><?php endif; ?></div>
<?php if ($s['estado']==='borrador' && $usuario->puede('compras.confirmar')): ?>
<h2 class="subtitulo-linea">Confirmar documento</h2>
<?= form_open('compras/'.(int)$s['id'].'/confirmar') ?><input type="hidden" name="version" value="<?= (int)$s['version'] ?>">
<p><label><input type="checkbox" name="revisada" value="1" required> Revisé el proveedor, las cantidades y las observaciones. Al confirmar, el documento dejará de ser editable.</label></p>
<button type="submit">Confirmar solicitud</button><?= form_close() ?>
<?php endif; ?>
<?php if ($s['confirmado_en']): ?><p>Confirmada el <?= html_escape($s['confirmado_en']) ?>.</p><?php endif; ?>
</section><?php $this->load->view('plantilla/pie'); ?>
