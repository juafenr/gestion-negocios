<?php $nuevo = $insumo === NULL; $this->load->view('plantilla/cabecera',array('titulo'=>$nuevo?'Nuevo insumo':'Editar insumo','seccion'=>'inventario','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('inventario')) ?>">← Volver al inventario</a></p>
<div class="encabezado-formulario"><p class="sobretitulo">INVENTARIO</p><h1><?= $nuevo ? 'Nuevo insumo' : 'Editar insumo' ?></h1><p class="descripcion">Registra el artículo y define cuándo necesita reposición.</p></div>
<section class="recuadro panel-formulario">
<?php if (validation_errors()): ?><div class="error" role="alert"><?= validation_errors() ?></div><?php endif; ?>
<?= form_open($accion,array('class'=>'formulario-ui','data-ui-form'=>'')) ?>
<p class="ayuda-obligatorios">Los campos con asterisco (*) son obligatorios.</p>
<fieldset class="seccion-formulario"><legend>Información del insumo</legend><div class="campos-formulario">
    <div class="campo campo-completo"><label for="nombre">Nombre <span aria-hidden="true">*</span></label><input id="nombre" name="nombre" maxlength="150" required value="<?= html_escape(set_value('nombre',$insumo['nombre']??'')) ?>"></div>
    <div class="campo"><label for="unidad_medida">Unidad de medida <span aria-hidden="true">*</span></label><select id="unidad_medida" name="unidad_medida" required><?php $unidad_actual=set_value('unidad_medida',$insumo['unidad_medida']??'unidad'); foreach(array('unidad'=>'Unidad','kg'=>'Kilogramos','g'=>'Gramos','mg'=>'Miligramos','l'=>'Litros','ml'=>'Mililitros') as $valor=>$etiqueta): ?><option value="<?= $valor ?>" <?= $unidad_actual===$valor?'selected':'' ?>><?= $etiqueta ?></option><?php endforeach; ?></select></div>
    <div class="campo"><label for="fecha_vencimiento">Fecha de vencimiento <span class="opcional">Opcional</span></label><input id="fecha_vencimiento" name="fecha_vencimiento" type="date" value="<?= html_escape(set_value('fecha_vencimiento',$insumo['fecha_vencimiento']??'')) ?>"></div>
</div></fieldset>
<fieldset class="seccion-formulario"><legend>Control de existencias</legend>
<?php if (!$nuevo): ?><div class="contexto-formulario"><strong>Existencia actual: <?= html_escape($insumo['cantidad_actual'].' '.$insumo['unidad_medida']) ?></strong><p>Para cambiarla utiliza Registrar movimiento desde el inventario.</p></div><?php endif; ?>
<div class="campos-formulario">
    <?php if ($nuevo): ?><div class="campo"><label for="cantidad_actual">Existencia inicial <span aria-hidden="true">*</span></label><input id="cantidad_actual" name="cantidad_actual" type="number" min="0" max="99999999.99" step="0.01" inputmode="decimal" required value="<?= html_escape(set_value('cantidad_actual','0')) ?>" aria-describedby="ayuda-inicial"><p id="ayuda-inicial" class="ayuda-campo">Cantidad disponible al registrar el insumo. Puedes empezar en cero.</p></div><?php endif; ?>
    <div class="campo"><label for="stock_minimo">Stock mínimo <span aria-hidden="true">*</span></label><input id="stock_minimo" name="stock_minimo" type="number" min="0" max="99999999.99" step="0.01" inputmode="decimal" required value="<?= html_escape(set_value('stock_minimo',$insumo['stock_minimo']??'0')) ?>" aria-describedby="ayuda-minimo"><p id="ayuda-minimo" class="ayuda-campo">El insumo se mostrará para reposición al llegar a este mínimo o quedar por debajo.</p></div>
</div></fieldset>
<div class="acciones-formulario"><a class="boton boton-secundario" href="<?= html_escape(site_url('inventario')) ?>">Cancelar</a><button type="submit">Guardar insumo</button></div>
<?= form_close() ?></section>
<?php $this->load->view('plantilla/pie'); ?>
