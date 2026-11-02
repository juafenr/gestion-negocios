<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Registrar proveedor','seccion'=>'compras','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('compras')) ?>">Volver a solicitudes</a></p>
<section class="recuadro formulario formulario-inventario"><h1>Registrar proveedor</h1>
<?php if ($error): ?><p class="error" role="alert"><?= html_escape($error) ?></p><?php endif; ?>
<?= form_open('compras/proveedor_nuevo') ?>
<?php foreach (array('nombre'=>array('Nombre *',150),'contacto'=>array('Contacto',100),'telefono'=>array('Teléfono',30),'correo'=>array('Correo',190),'direccion'=>array('Dirección',255)) as $campo=>$info): ?>
<label for="<?= $campo ?>"><?= $info[0] ?></label><input id="<?= $campo ?>" name="<?= $campo ?>" type="<?= $campo==='correo' ? 'email' : 'text' ?>" maxlength="<?= $info[1] ?>" value="<?= html_escape($datos[$campo]) ?>" <?= $campo==='nombre' ? 'required' : '' ?>>
<?php endforeach; ?><button type="submit">Guardar proveedor</button><?= form_close() ?></section>
<?php $this->load->view('plantilla/pie'); ?>
