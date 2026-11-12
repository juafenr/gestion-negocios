<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="recuadro panel-formulario">
<?php if ($proveedor_form_error !== ''): ?><div class="error" role="alert"><?= html_escape($proveedor_form_error) ?></div><?php endif; ?>
<?= form_open($proveedor_form_accion,array('class'=>'formulario-ui','data-ui-form'=>'')) ?>
<p class="ayuda-obligatorios">Solo el nombre es obligatorio. Puedes completar los datos de contacto después.</p>
<fieldset class="seccion-formulario"><legend>Información del proveedor</legend><div class="campos-formulario">
<?php foreach(array('nombre'=>array('Nombre',150),'contacto'=>array('Persona de contacto',100),'telefono'=>array('Teléfono',30),'correo'=>array('Correo electrónico',190),'direccion'=>array('Dirección',255)) as $pr_campo=>$pr_info): ?>
    <div class="campo <?= $pr_campo==='direccion'?'campo-completo':'' ?>"><label for="<?= $pr_campo ?>"><?= $pr_info[0] ?> <?= $pr_campo==='nombre'?'<span aria-hidden="true">*</span>':'<span class="opcional">Opcional</span>' ?></label><input id="<?= $pr_campo ?>" name="<?= $pr_campo ?>" type="<?= $pr_campo==='correo'?'email':($pr_campo==='telefono'?'tel':'text') ?>" maxlength="<?= $pr_info[1] ?>" value="<?= html_escape($proveedor_form_datos[$pr_campo]??'') ?>" <?= $pr_campo==='nombre'?'required':'' ?>></div>
<?php endforeach; ?>
</div></fieldset>
<div class="acciones-formulario"><a class="boton boton-secundario" href="<?= html_escape(site_url($proveedor_form_cancelar)) ?>">Cancelar</a><button type="submit"><?= html_escape($proveedor_form_guardar) ?></button></div>
<?= form_close() ?></section>
