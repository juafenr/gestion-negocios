<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Borrador de compra','seccion'=>'compras','usuario'=>$usuario));
if (!$lineas) { $lineas=array(array('insumo_id'=>'','cantidad'=>'')); } ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('compras')) ?>">Volver a solicitudes</a></p>
<section class="recuadro"><h1>Borrador de compra</h1>
<p class="descripcion">Selecciona un proveedor y los insumos que solicitarás. Podrás revisar el documento antes de confirmarlo.</p>
<?php if ($error): ?><p class="error" role="alert"><?= html_escape($error) ?></p><?php endif; ?>
<?php if (!$proveedores || !$insumos): ?><p class="aviso">Necesitas al menos un proveedor activo y un insumo activo de tu empresa.</p><?php endif; ?>
<?= form_open($accion,array('id'=>'formulario-compra','class'=>'formulario-ui','data-ui-form'=>'')) ?>
<?php if (isset($datos['version'])): ?><input type="hidden" name="version" value="<?= (int)$datos['version'] ?>"><?php else: ?><input type="hidden" name="clave" value="<?= html_escape($datos['clave']) ?>"><?php endif; ?>
<p class="ayuda-obligatorios">Los campos marcados con * son obligatorios.</p><div class="campos-formulario">
<p class="campo"><label for="proveedor_id">Proveedor *</label><select name="proveedor_id" id="proveedor_id" required><option value="">Selecciona un proveedor</option>
<?php foreach ($proveedores as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (string)$datos['proveedor_id']===(string)$p['id'] ? 'selected' : '' ?>><?= html_escape($p['nombre']) ?></option><?php endforeach; ?></select></p>
<p class="campo"><label for="fecha">Fecha de solicitud *</label><input id="fecha" name="fecha" type="date" value="<?= html_escape($datos['fecha']) ?>" required></p></div>
<h2 class="subtitulo-linea">Insumos solicitados</h2>
<p class="nota">Las cantidades deben ser mayores que cero y tener hasta dos decimales. Si un insumo ya tiene proveedor asociado, debe coincidir con el seleccionado.</p>
<div class="tabla"><table><thead><tr><th>Insumo y unidad *</th><th>Cantidad *</th><th>Acción</th></tr></thead><tbody id="lineas-compra">
<?php foreach ($lineas as $linea): ?>
<tr><td><select name="insumos[]" aria-label="Insumo"><option value="">Selecciona un insumo</option>
<?php foreach ($insumos as $i): ?><option value="<?= (int)$i['id'] ?>" <?= (string)$linea['insumo_id']===(string)$i['id'] ? 'selected' : '' ?>><?= html_escape($i['nombre'].' ('.$i['unidad_medida'].')') ?></option><?php endforeach; ?></select></td>
<td><input name="cantidades[]" type="number" min="0.01" max="99999999.99" step="0.01" value="<?= html_escape($linea['cantidad']) ?>" aria-label="Cantidad solicitada"></td>
<td><button type="button" class="boton-secundario quitar-linea" hidden><img class="icono" src="<?= html_escape(base_url('assets/iconos/quitar.svg')) ?>" alt="">Quitar</button></td></tr>
<?php endforeach; ?></tbody></table></div>
<p><button id="agregar-linea" type="button" class="boton-secundario" hidden><img class="icono" src="<?= html_escape(base_url('assets/iconos/agregar.svg')) ?>" alt="">Agregar insumo</button></p>
<noscript><p>Sin JavaScript puedes guardar una línea y volver a editarla. Activa JavaScript para agregar o quitar más líneas.</p></noscript>
<p class="campo"><label for="observaciones">Observaciones</label><textarea id="observaciones" name="observaciones" rows="4" maxlength="1000" class="texto-compra"><?= html_escape($datos['observaciones']) ?></textarea></p>
<div class="acciones-formulario"><a class="boton boton-secundario" href="<?= html_escape(site_url('compras')) ?>">Cancelar</a><button type="submit"><img class="icono" src="<?= html_escape(base_url('assets/iconos/guardar.svg')) ?>" alt="">Guardar borrador</button></div>
<?= form_close() ?></section>
<script src="<?= html_escape(base_url('assets/compras.js')) ?>" defer></script>
<?php $this->load->view('plantilla/pie'); ?>
