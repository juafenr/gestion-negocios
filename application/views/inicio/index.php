<?php
$this->load->view('plantilla/cabecera', array('titulo'=>'Inicio','seccion'=>'inicio','usuario'=>$usuario));
$inicio_accesos = ui_accesos_inicio($usuario);
$indicadores_rol = array('administrador'=>array('ventas','reponer','ultimos_dias'),
    'cajero'=>array('por_cobrar'), 'cocinero'=>array('cocina'));
if (isset($indicadores_rol[$usuario->rol()])) {
    $resumen = array_intersect_key($resumen, array_fill_keys($indicadores_rol[$usuario->rol()], TRUE));
}
$orientacion = array('administrador'=>'Revisa inventario, compras y ventas de tu empresa.',
    'cajero'=>'Gestiona tus pedidos y los cobros pendientes.', 'cocinero'=>'Consulta la cola y continúa con la preparación de los pedidos.');
?>
<p class="sobretitulo">TU ESPACIO DE TRABAJO</p>
<h1>Hola, <?= html_escape($usuario->nombre()) ?></h1>
<p class="descripcion"><?= html_escape($orientacion[$usuario->rol()] ?? 'Consulta los módulos disponibles para tu cuenta.') ?></p>
<?php if ($aviso_resumen !== ''): ?><p class="aviso" role="status"><?= html_escape($aviso_resumen) ?></p><?php endif; ?>
<section aria-label="Accesos principales" class="accesos-principales">
    <h2>Tu trabajo de hoy</h2>
    <?php $this->load->view('plantilla/accesos_inicio',array('lista_accesos'=>$inicio_accesos['principales'])); ?>
</section>
<?php if ($resumen): ?>
<section aria-labelledby="titulo-resumen">
    <h2 id="titulo-resumen">Resumen de tu empresa</h2>
    <div class="resumen-pagos">
        <?php if (isset($resumen['ventas'])): ?>
        <div class="recuadro indicador-inicio">
            <span class="indicador-icono" aria-hidden="true"><img class="icono" src="<?= html_escape(base_url('assets/iconos/ventas.svg')) ?>" alt=""></span>
            <span>Ventas de hoy sin propina</span>
            <strong><?= negocio_moneda($resumen['ventas']['consumo']) ?></strong>
            <p><?= (int) $resumen['ventas']['cantidad'] ?> <?= (int) $resumen['ventas']['cantidad'] === 1 ? 'pedido pagado' : 'pedidos pagados' ?> · Propinas: <?= negocio_moneda($resumen['ventas']['propina']) ?></p>
            <a href="<?= html_escape(site_url('ventas').'?'.http_build_query(array('desde'=>date('Y-m-d'), 'hasta'=>date('Y-m-d')))) ?>">Consultar ventas de hoy</a>
        </div>
        <?php endif; ?>
        <?php if (isset($resumen['cocina'])): ?>
        <div class="recuadro indicador-inicio"><span class="indicador-icono" aria-hidden="true"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pedidos.svg')) ?>" alt=""></span><span>Pedidos en cocina</span><strong><?= $resumen['cocina'] ?></strong><p>Pendientes, en preparación o listos.</p><a href="<?= html_escape(site_url('cocina')) ?>">Consultar cocina</a></div>
        <?php endif; ?>
        <?php if (isset($resumen['por_cobrar'])): ?>
        <div class="recuadro indicador-inicio"><span class="indicador-icono" aria-hidden="true"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pagos.svg')) ?>" alt=""></span><span>Pedidos por cobrar</span><strong><?= $resumen['por_cobrar'] ?></strong><p>Confirmados con pago pendiente o parcial.</p><a href="<?= html_escape(site_url('pagos')) ?>">Consultar pagos</a></div>
        <?php endif; ?>
        <?php if (isset($resumen['reponer'])): ?>
        <div class="recuadro indicador-inicio"><span class="indicador-icono" aria-hidden="true"><img class="icono" src="<?= html_escape(base_url('assets/iconos/inventario.svg')) ?>" alt=""></span><span>Insumos por reponer</span><strong><?= $resumen['reponer'] ?></strong><p>Activos, agotados o en el mínimo de existencias.</p><a href="<?= html_escape(site_url('inventario')) ?>">Revisar inventario</a></div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($aviso_estadisticas !== ''): ?>
    <p class="aviso" role="status">
        <?= html_escape($aviso_estadisticas) ?>
    </p>
<?php endif; ?>

<?php $this->load->view(
    'inicio/estadisticas',
    array('panel' => $estadisticas)
); ?>
<?php if ($inicio_accesos['otros']): ?>
<details class="otros-accesos"><summary>Otros accesos disponibles</summary>
    <?php $this->load->view('plantilla/accesos_inicio',array('lista_accesos'=>$inicio_accesos['otros'])); ?>
</details>
<?php endif; ?>
<?php if (!$inicio_accesos['principales'] && !$inicio_accesos['otros']): ?><p class="recuadro aviso">Tu cuenta todavía no tiene módulos asignados. Consulta al administrador de tu empresa.</p><?php endif; ?>
<?php $this->load->view('plantilla/pie'); ?>
