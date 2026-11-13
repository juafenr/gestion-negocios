<?php $this->load->view('plantilla/cabecera',array('titulo'=>$titulo,'seccion'=>'productos','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('productos')) ?>">← Volver a productos</a></p>
<div class="encabezado-formulario"><p class="sobretitulo">CATÁLOGO</p><h1><?= html_escape($titulo) ?></h1><p class="descripcion">Define los datos, el precio y la disponibilidad del producto en tu empresa.</p></div>
<section class="recuadro panel-formulario">
<?php if ($error !== ''): ?><div class="error" role="alert"><?= html_escape($error) ?></div><?php endif; ?>
<?= form_open($accion,array('class'=>'formulario-ui','data-ui-form'=>'')) ?>
<p class="ayuda-obligatorios">Los campos con asterisco (*) son obligatorios.</p>
<fieldset class="seccion-formulario"><legend>Información del producto</legend><div class="campos-formulario">
    <div class="campo campo-completo"><label for="nombre">Nombre del producto o platillo <span aria-hidden="true">*</span></label><input id="nombre" name="nombre" maxlength="150" required value="<?= html_escape($producto['nombre']??'') ?>"></div>
    <div class="campo"><label for="sku">Código <span aria-hidden="true">*</span></label><input id="sku" name="sku" maxlength="40" required aria-describedby="ayuda-codigo" value="<?= html_escape($producto['sku']??'') ?>"><p id="ayuda-codigo" class="ayuda-campo">El código debe ser único dentro de tu empresa.</p></div>
    <div class="campo"><label for="disponible">Disponibilidad <span aria-hidden="true">*</span></label><select id="disponible" name="disponible" required><option value="">Selecciona una opción</option><?php foreach(array('1'=>'Disponible','0'=>'No disponible') as $valor=>$etiqueta): ?><option value="<?= $valor ?>" <?= (string)($producto['disponible']??'')===(string)$valor?'selected':'' ?>><?= $etiqueta ?></option><?php endforeach; ?></select></div>
    <div class="campo campo-completo"><label for="descripcion">Descripción <span class="opcional">Opcional</span></label><textarea id="descripcion" name="descripcion" rows="4" maxlength="1000"><?= html_escape($producto['descripcion']??'') ?></textarea></div>
</div></fieldset>
<fieldset class="seccion-formulario"><legend>Precio de venta</legend><div class="campos-formulario">
    <div class="campo"><label for="precio">Precio en quetzales <span aria-hidden="true">*</span></label><input id="precio" name="precio" type="number" min="0" max="99999999.99" step="0.01" inputmode="decimal" required value="<?= html_escape($producto['precio']??'') ?>"></div>
</div>
<?php if (!empty($producto['id'])): ?><p class="ayuda-campo">Si cambias el precio, el valor anterior, el nuevo, la fecha y tu usuario quedarán en el historial. Los precios de pedidos anteriores se conservan.</p><?php endif; ?>
</fieldset>
<div class="acciones-formulario"><a class="boton boton-secundario" href="<?= html_escape(site_url('productos')) ?>">Cancelar</a><button type="submit">Guardar producto</button></div>
<?= form_close() ?></section>
<?php if (!empty($producto['id'])): ?>
<section class="recuadro panel-formulario"><h2>Imagen del producto</h2>
<?php if ($mensaje_imagen=$this->session->flashdata('mensaje_imagen')): ?><p class="aviso" role="status"><?= html_escape($mensaje_imagen) ?></p><?php endif; ?>
<?php if (!empty($producto['imagen'])): ?><img class="imagen-formulario" src="<?= html_escape(site_url('productos/'.(int)$producto['id'].'/imagen')) ?>" alt="Imagen actual de <?= html_escape($producto['nombre']) ?>" width="180" height="180"><?php else: ?><span class="imagen-placeholder imagen-formulario">Sin imagen</span><?php endif; ?>
<?= form_open_multipart('productos/'.(int)$producto['id'].'/imagen') ?>
<p class="campo"><label for="imagen_producto">Subir o reemplazar imagen</label><input id="imagen_producto" type="file" name="imagen" accept="image/jpeg,image/png,image/webp" required><span class="descripcion">JPG, PNG o WebP; máximo 3 MB.</span></p>
<button type="submit">Guardar imagen</button>
<?= form_close() ?>
<?php if (!empty($producto['imagen'])): ?><?= form_open('productos/'.(int)$producto['id'].'/imagen') ?><input type="hidden" name="quitar" value="1"><button type="submit" class="boton-secundario">Quitar imagen</button><?= form_close() ?><?php endif; ?>
</section>
<?php else: ?><p class="descripcion">Después de crear el producto podrás agregarle una imagen desde Editar.</p><?php endif; ?>
<?php $this->load->view('plantilla/pie'); ?>
