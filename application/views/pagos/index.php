<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Pagos','seccion'=>'pagos','usuario'=>$usuario)); ?>
<p class="sobretitulo">CONTROL DE COBROS</p>
<h1>Pagos</h1>
<p class="descripcion">Cobra pedidos confirmados y consulta sus saldos en quetzales.</p>
<?php $this->load->view('plantilla/filtros', array('filtro_ruta'=>'pagos', 'filtro_campos'=>array(
'estado'=>array('etiqueta'=>'Estado de pago','defecto'=>'todos','opciones'=>array('todos'=>'Todos los estados','pendiente'=>'Sin pagos','parcial'=>'Pago parcial','pagado'=>'Pagado'))
), 'filtro_valores'=>array('estado'=>$estado), 'filtro_extras'=>array())); ?>
<?php $this->load->view('plantilla/resultados', array('resultado_total'=>NULL, 'resultado_mostrados'=>count($pedidos), 'resultado_pagina'=>$pagina)); ?>
<div class="tabla"><table>
<thead><tr><th>Pedido</th><th>Referencia</th><th>Preparación</th><th>Consumo</th><th>Propina</th><th>Total a pagar</th><th>Pagado</th><th>Saldo</th><th>Estado de pago</th><th>Acción</th></tr></thead>
<tbody>
<?php
$estados_pago = array(
    'pagado' => array('clase' => 'pago-completo', 'texto' => 'Pagado'),
    'parcial' => array('clase' => 'pago-parcial', 'texto' => 'Pago parcial'),
    'pendiente' => array('clase' => 'pago-pendiente', 'texto' => 'Sin pagos')
);
foreach ($pedidos as $pedido):
    $estado_pago = $estados_pago[$pedido['estado_pago']] ?? array('clase' => 'pago-desconocido', 'texto' => 'Por revisar');
?>
<tr>
    <td>#<?= (int)$pedido['numero'] ?></td><td><?= html_escape($pedido['referencia']) ?></td>
    <td><?= html_escape(str_replace('_',' ',$pedido['estado'])) ?></td>
    <td><?= Dinero::mostrar(Dinero::centavos($pedido['total'])) ?></td>
    <td><?= Dinero::mostrar(Dinero::propina(Dinero::centavos($pedido['total']), (int)$pedido['propina_porcentaje'])) ?></td>
    <td><?= Dinero::mostrar(Dinero::centavos($pedido['total'])+Dinero::propina(Dinero::centavos($pedido['total']), (int)$pedido['propina_porcentaje'])) ?></td>
    <td><?= Dinero::mostrar(Dinero::centavos($pedido['abonado'])) ?></td>
    <td><?= Dinero::mostrar(max(0,Dinero::centavos($pedido['total'])+Dinero::propina(Dinero::centavos($pedido['total']), (int)$pedido['propina_porcentaje'])-Dinero::centavos($pedido['abonado']))) ?></td>
    <td><span class="estado-pago <?= html_escape($estado_pago['clase']) ?>"><?= html_escape($estado_pago['texto']) ?></span></td>
    <td><a class="boton boton-secundario boton-compacto" href="<?= html_escape(site_url('pagos/'.(int)$pedido['id'])) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pagos.svg')) ?>" alt="">Ver cuentas</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$pedidos): ?><tr><td colspan="10"><?php $this->load->view('plantilla/vacio',array('vacio_titulo'=>'No hay pedidos confirmados con ese estado de pago.','vacio_descripcion'=>'Selecciona otro estado para consultar los cobros disponibles.','vacio_url'=>$estado!=='todos'?site_url('pagos'):NULL,'vacio_accion'=>'Limpiar filtros')); ?></td></tr><?php endif; ?>
</tbody></table></div>
<?php $this->load->view('plantilla/paginacion', array('ruta_paginacion'=>'pagos','parametros_paginacion'=>array('estado'=>$estado),'pagina_actual'=>$pagina,'cantidad_paginas'=>NULL,'hay_siguiente'=>$siguiente)); ?>
<?php $this->load->view('plantilla/pie'); ?>
