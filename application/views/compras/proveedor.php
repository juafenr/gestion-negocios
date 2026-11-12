<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Registrar proveedor','seccion'=>'compras','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('compras')) ?>">← Volver a solicitudes</a></p>
<div class="encabezado-formulario"><p class="sobretitulo">PROVEEDORES</p><h1>Registrar proveedor</h1><p class="descripcion">Guarda la información de quien suministra los insumos de tu negocio.</p></div>
<?php $this->load->view('plantilla/proveedor_formulario',array('proveedor_form_datos'=>$datos,'proveedor_form_accion'=>'compras/proveedor_nuevo','proveedor_form_cancelar'=>'compras','proveedor_form_guardar'=>'Guardar proveedor','proveedor_form_error'=>$error)); ?>
<?php $this->load->view('plantilla/pie'); ?>
