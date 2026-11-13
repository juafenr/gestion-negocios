<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Editar proveedor','seccion'=>'proveedores','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url(ui_ruta_retorno('proveedores'))) ?>">← Volver a proveedores</a></p>
<div class="encabezado-formulario"><p class="sobretitulo">PROVEEDORES</p><h1>Editar proveedor</h1><p class="descripcion">Actualiza los datos del proveedor y sus medios de contacto.</p></div>
<?php $this->load->view('plantilla/proveedor_formulario',array('proveedor_form_datos'=>$proveedor,'proveedor_form_accion'=>'proveedores/'.(int)$proveedor['id'].'/editar','proveedor_form_cancelar'=>ui_ruta_retorno('proveedores'),'proveedor_form_guardar'=>'Guardar cambios','proveedor_form_error'=>$error)); ?>
<?php $this->load->view('plantilla/pie'); ?>
