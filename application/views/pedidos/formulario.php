<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Nuevo pedido','seccion'=>'pedidos','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url(ui_ruta_retorno('pedidos'))) ?>">← Volver a pedidos</a></p>
<div class="encabezado-formulario"><p class="sobretitulo">PEDIDOS</p><h1>Nuevo pedido</h1><p class="descripcion">Primero identifica el pedido. Después podrás agregar los productos y confirmarlo.</p></div>
<section class="recuadro panel-formulario">
<?php if (validation_errors()): ?><div class="error" role="alert"><?= validation_errors() ?></div><?php endif; ?>
<?= form_open('pedidos/crear',array('class'=>'formulario-ui','data-ui-form'=>'')) ?>
<div class="campo"><label for="referencia">Referencia del pedido <span aria-hidden="true">*</span></label><input id="referencia" name="referencia" maxlength="100" required placeholder="Ej. Mesa 5, Cliente López u Orden 123" value="<?= html_escape(set_value('referencia')) ?>" aria-describedby="ayuda-referencia"><p id="ayuda-referencia" class="ayuda-campo">Usa una referencia que el equipo pueda reconocer fácilmente.</p></div>
<div class="acciones-formulario"><a class="boton boton-secundario" href="<?= html_escape(site_url(ui_ruta_retorno('pedidos'))) ?>">Cancelar</a><button type="submit">Crear pedido</button></div>
<?= form_close() ?></section>
<?php $this->load->view('plantilla/pie'); ?>
