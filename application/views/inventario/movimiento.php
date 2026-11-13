<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Registrar movimiento','seccion'=>'inventario','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url(ui_ruta_retorno('inventario'))) ?>">← Volver al inventario</a></p>
<div class="encabezado-formulario"><p class="sobretitulo">INVENTARIO</p><h1>Registrar movimiento</h1><p class="descripcion">Actualiza las existencias y deja registrado el motivo del cambio.</p></div>
<section class="recuadro panel-formulario">
<div class="contexto-formulario"><strong><?= html_escape($insumo['nombre']) ?></strong><p>Existencia actual: <?= html_escape($insumo['cantidad_actual'].' '.$insumo['unidad_medida']) ?></p></div>
<?php if (!empty($error_movimiento)): ?><div class="error" role="alert"><?= html_escape($error_movimiento) ?></div><?php endif; ?>
<?= form_open('inventario/'.(int)$insumo['id'].'/movimiento',array('class'=>'formulario-ui','data-ui-form'=>'')) ?>
<p class="ayuda-obligatorios">Todos los campos son obligatorios.</p>
<div class="campos-formulario">
    <div class="campo"><label for="tipo">Tipo de movimiento <span aria-hidden="true">*</span></label><select id="tipo" name="tipo" required aria-describedby="ayuda-tipo"><?php foreach(array('entrada'=>'Entrada','salida'=>'Salida','ajuste'=>'Ajuste') as $valor=>$etiqueta): ?><option value="<?= $valor ?>" <?= ($entrada['tipo']??'entrada')===$valor?'selected':'' ?>><?= $etiqueta ?></option><?php endforeach; ?></select><p id="ayuda-tipo" class="ayuda-campo">Entrada suma, salida descuenta y ajuste establece la cantidad final.</p></div>
    <div class="campo"><label for="cantidad">Cantidad (<?= html_escape($insumo['unidad_medida']) ?>) <span aria-hidden="true">*</span></label><input id="cantidad" name="cantidad" type="number" min="0" max="99999999.99" step="0.01" inputmode="decimal" required value="<?= html_escape($entrada['cantidad']??'') ?>" aria-describedby="ayuda-cantidad"><p id="ayuda-cantidad" class="ayuda-campo">Usa hasta dos decimales. El cero solo se admite en un ajuste.</p></div>
    <div class="campo campo-completo"><label for="motivo">Motivo <span aria-hidden="true">*</span></label><input id="motivo" name="motivo" maxlength="255" required placeholder="Ej. Compra, consumo o conteo físico" value="<?= html_escape($entrada['motivo']??'') ?>"></div>
</div>
<div class="acciones-formulario"><a class="boton boton-secundario" href="<?= html_escape(site_url(ui_ruta_retorno('inventario'))) ?>">Cancelar</a><button type="submit">Guardar movimiento</button></div>
<?= form_close() ?></section>
<?php $this->load->view('plantilla/pie'); ?>
