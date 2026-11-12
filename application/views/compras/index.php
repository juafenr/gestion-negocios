<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Solicitudes de compra','seccion'=>'compras','usuario'=>$usuario)); ?>
<div class="titulo-acciones"><div><p class="sobretitulo">DOCUMENTOS DE COMPRA</p><h1>Solicitudes de compra</h1><p class="descripcion">Prepara y revisa lo que necesitas solicitar a un proveedor.</p></div>
<div class="acciones-cabecera">
<?php if ($usuario->puede('compras.gestionar') && $usuario->puede('inventario.ver')): ?><a class="boton boton-secundario" href="<?= html_escape(site_url('compras/seleccionar')) ?>">Seleccionar desde inventario</a><?php endif; ?>
<?php if ($usuario->puede('compras.gestionar') && $usuario->puede('proveedores.gestionar')): ?><a class="boton boton-secundario" href="<?= html_escape(site_url('compras/proveedor_nuevo')) ?>">Registrar proveedor</a><?php endif; ?>
<?php if ($usuario->puede('compras.gestionar')): ?><a class="boton" href="<?= html_escape(site_url('compras/crear')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/agregar.svg')) ?>" alt="">Nueva solicitud</a><?php endif; ?>
</div></div>
<?php if ($mensaje): ?><p role="status" class="recuadro aviso"><?= html_escape($mensaje) ?></p><?php endif; ?>
<?php if (!empty($creadas)): ?><ul><?php foreach ($creadas as $creada): ?><li><a href="<?= html_escape(site_url('compras/'.(int)$creada['id'])) ?>">Revisar borrador de <?= html_escape($creada['proveedor']) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
<?php $this->load->view('plantilla/filtros',array('filtro_ruta'=>'compras','filtro_campos'=>array('busqueda'=>array('tipo'=>'search','etiqueta'=>'Proveedor o número','ejemplo'=>'Ej. Distribuidora o 12'),
'estado'=>array('etiqueta'=>'Estado de la solicitud','opciones'=>array(''=>'Todos los estados','borrador'=>'Borrador','confirmada'=>'Confirmada','enviada'=>'Enviada','recibida'=>'Recibida','cancelada'=>'Cancelada'))),'filtro_valores'=>$filtros,'filtro_extras'=>array())); ?>
<?php $this->load->view('plantilla/resultados',array('resultado_total'=>$total,'resultado_mostrados'=>count($solicitudes),'resultado_pagina'=>$pagina)); ?>
<div class="tabla"><table><thead><tr><th>Número</th><th>Fecha</th><th>Proveedor</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php foreach ($solicitudes as $s): ?><tr><td>#<?= (int)$s['numero'] ?></td><td><?= html_escape($s['fecha']) ?></td><td><?= html_escape($s['proveedor_nombre']) ?></td><td><span class="estado"><?= html_escape($s['estado']) ?></span></td>
<td><a class="boton boton-secundario boton-compacto" href="<?= html_escape(site_url('compras/'.(int)$s['id'])) ?>">Revisar</a></td></tr><?php endforeach; ?>
<?php if (!$solicitudes): ?><tr><td colspan="5"><?php $this->load->view('plantilla/vacio',array('vacio_titulo'=>array_filter($filtros)?'No hay solicitudes con estos filtros.':'Todavía no hay solicitudes de compra.','vacio_descripcion'=>'Revisa el proveedor y el estado seleccionado o prepara una nueva solicitud.','vacio_url'=>array_filter($filtros)?site_url('compras'):($usuario->puede('compras.gestionar')?site_url('compras/crear'):NULL),'vacio_accion'=>array_filter($filtros)?'Limpiar filtros':'Nueva solicitud')); ?></td></tr><?php endif; ?></tbody></table></div>
<?php $this->load->view('plantilla/paginacion',array('ruta_paginacion'=>'compras','parametros_paginacion'=>$filtros,'pagina_actual'=>$pagina,'cantidad_paginas'=>$paginas,'hay_siguiente'=>FALSE)); ?>
<?php $this->load->view('plantilla/pie'); ?>
