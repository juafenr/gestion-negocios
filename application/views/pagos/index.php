<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Pagos','seccion'=>'pagos','usuario'=>$usuario)); ?>
<p class="sobretitulo">CONTROL DE COBROS</p>
<h1>Pagos</h1>
<p class="descripcion">Cobra pedidos confirmados y consulta sus saldos en quetzales.</p>
<form action="<?= html_escape(site_url('pagos')) ?>" method="get" class="recuadro filtros-pagos">
    <label for="estado">Estado de pago</label>
    <select id="estado" name="estado">
        <?php foreach (array('todos'=>'Todos','pendiente'=>'Pendiente','parcial'=>'Pago parcial','pagado'=>'Pagado') as $valor=>$etiqueta): ?>
            <option value="<?= $valor ?>" <?= $estado===$valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filtrar</button>
</form>
<div class="tabla"><table>
<thead><tr><th>Pedido</th><th>Referencia</th><th>Preparación</th><th>Pago</th><th>Consumo</th><th>Propina 10%</th><th>Total a pagar</th><th>Pagado</th><th>Saldo</th><th>Acción</th></tr></thead>
<tbody>
<?php foreach ($pedidos as $pedido): ?>
<tr>
    <td>#<?= (int)$pedido['numero'] ?></td><td><?= html_escape($pedido['referencia']) ?></td>
    <td><?= html_escape(str_replace('_',' ',$pedido['estado'])) ?></td><td><?= html_escape($pedido['estado_pago']) ?></td>
    <td><?= Dinero::mostrar(Dinero::centavos($pedido['total'])) ?></td>
    <td><?= Dinero::mostrar(Dinero::propina(Dinero::centavos($pedido['total']))) ?></td>
    <td><?= Dinero::mostrar(Dinero::centavos($pedido['total'])+Dinero::propina(Dinero::centavos($pedido['total']))) ?></td>
    <td><?= Dinero::mostrar(Dinero::centavos($pedido['abonado'])) ?></td>
    <td><?= Dinero::mostrar(max(0,Dinero::centavos($pedido['total'])+Dinero::propina(Dinero::centavos($pedido['total']))-Dinero::centavos($pedido['abonado']))) ?></td>
    <td><a class="boton boton-secundario boton-compacto" href="<?= html_escape(site_url('pagos/'.(int)$pedido['id'])) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pagos.svg')) ?>" alt="">Ver cuentas</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$pedidos): ?><tr><td colspan="10">No hay pedidos confirmados con ese estado de pago.</td></tr><?php endif; ?>
</tbody></table></div>
<p class="paginacion-pagos">
<?php if ($pagina>1): ?><a class="boton boton-secundario" href="<?= html_escape(site_url('pagos').'?estado='.$estado.'&pagina='.($pagina-1)) ?>">Anterior</a><?php endif; ?>
<span>Página <?= (int)$pagina ?></span>
<?php if ($siguiente): ?><a class="boton boton-secundario" href="<?= html_escape(site_url('pagos').'?estado='.$estado.'&pagina='.($pagina+1)) ?>">Siguiente</a><?php endif; ?>
</p>
<?php $this->load->view('plantilla/pie'); ?>
