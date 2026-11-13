<?php $this->load->view('plantilla/cabecera', array('titulo'=>'Pedido rápido','seccion'=>'pedidos','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('pedidos')) ?>">← Volver a pedidos</a></p>
<h1>Pedido rápido</h1>
<p class="descripcion">Registra un producto, confirma el pedido y deja la cuenta preparada para cobrar. Para varios productos usa «Nuevo pedido».</p>
<?php if ($error): ?><p class="error" role="alert"><?= html_escape($error) ?></p><?php endif; ?>
<section class="recuadro panel-formulario">
<?= form_open('pedidos/rapido', array('class'=>'formulario-ui')) ?>
<p class="campo"><label for="referencia">Referencia (opcional)</label><input id="referencia" name="referencia" maxlength="100" placeholder="Mostrador, nombre o número" value="<?= html_escape(set_value('referencia')) ?>"></p>
<p class="campo"><label for="producto_id">Producto</label><select id="producto_id" name="producto_id" required>
<option value="">Selecciona un producto</option>
<?php foreach ($productos as $producto): ?><option value="<?= (int)$producto['id'] ?>" <?= set_value('producto_id')===(string)$producto['id']?'selected':'' ?>><?= html_escape($producto['nombre']) ?> — Q <?= number_format((float)$producto['precio'],2) ?></option><?php endforeach; ?>
</select></p>
<p class="campo"><label for="cantidad">Cantidad</label><input id="cantidad" name="cantidad" type="number" min="1" max="999" step="1" value="<?= html_escape(set_value('cantidad','1')) ?>" required></p>
<button type="submit">Confirmar y abrir cobro</button>
<?= form_close() ?>
</section>
<?php $this->load->view('plantilla/pie'); ?>
