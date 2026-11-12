<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Inicio', 'seccion' => 'inicio', 'usuario' => $usuario)); ?>
<p class="sobretitulo">TU NEGOCIO</p>
<h1>Inicio</h1>
<section class="recuadro bienvenida">
    <div>
        <h2>Hola, <?= html_escape($usuario->nombre()) ?></h2>
        <p>Bienvenido a <strong><?= html_escape($usuario->empresaNombre()) ?></strong>.</p>
        <p class="descripcion">Elige una opción para empezar a trabajar.</p>
    </div>
    <span class="bienvenida-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/negocio.svg')) ?>" alt="" width="22" height="22"></span>
</section>
<?php if ($aviso_resumen !== ''): ?>
    <p class="aviso" role="status"><?= html_escape($aviso_resumen) ?></p>
<?php endif; ?>
<?php if ($resumen): ?>
<section aria-labelledby="titulo-resumen">
    <h2 id="titulo-resumen">Resumen de tu empresa</h2>
    <div class="resumen-pagos">
        <?php if (isset($resumen['ventas'])): ?>
        <div class="recuadro">
            <span>Ventas de hoy sin propina</span>
            <strong><?= negocio_moneda($resumen['ventas']['consumo']) ?></strong>
            <p><?= (int) $resumen['ventas']['cantidad'] ?> pedidos pagados · Propinas: <?= negocio_moneda($resumen['ventas']['propina']) ?></p>
            <a href="<?= html_escape(site_url('ventas').'?'.http_build_query(array('desde'=>date('Y-m-d'), 'hasta'=>date('Y-m-d')))) ?>">Consultar ventas de hoy</a>
        </div>
        <?php endif; ?>
        <?php if (isset($resumen['cocina'])): ?>
        <div class="recuadro"><span>Pedidos en cocina</span><strong><?= $resumen['cocina'] ?></strong><p>Pendientes, en preparación o listos.</p><a href="<?= html_escape(site_url('cocina')) ?>">Consultar cocina</a></div>
        <?php endif; ?>
        <?php if (isset($resumen['por_cobrar'])): ?>
        <div class="recuadro"><span>Pedidos por cobrar</span><strong><?= $resumen['por_cobrar'] ?></strong><p>Confirmados con pago pendiente o parcial.</p><a href="<?= html_escape(site_url('pagos')) ?>">Consultar pagos</a></div>
        <?php endif; ?>
        <?php if (isset($resumen['reponer'])): ?>
        <div class="recuadro"><span>Insumos por reponer</span><strong><?= $resumen['reponer'] ?></strong><p>Activos, agotados o en el mínimo de existencias.</p><a href="<?= html_escape(site_url('inventario')) ?>">Revisar inventario</a></div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<h2>Accesos rápidos</h2>
<div class="accesos">
    <?php if ($usuario->puede('pedidos.actualizar_cocina')): ?>
        <a class="acceso" href="<?= html_escape(site_url('cocina')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/correcto.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Cocina</h3><p>Consulta los pedidos confirmados y sus productos.</p>
            <span class="acceso-abrir">Abrir cocina <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
    <?php if ($usuario->puede('ventas.historial')): ?>
    <a class="acceso" href="<?= html_escape(site_url('ventas')) ?>"><span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/ventas.svg')) ?>" alt=""></span><h3>Ventas</h3><p>Consulta las ventas completadas y el resumen por fecha.</p><span class="acceso-abrir">Abrir ventas</span></a>
    <?php endif; ?>

    <?php if ($usuario->puede('compras.ver')): ?>
    <a class="acceso" href="<?= html_escape(site_url('compras')) ?>"><span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/compras.svg')) ?>" alt=""></span><h3>Compras</h3><p>Prepara, revisa y exporta solicitudes para proveedores.</p><span class="acceso-abrir">Abrir compras</span></a>
    <?php endif; ?>

    <?php if ($usuario->puede('ventas.ver') && $usuario->puede('pedidos.ver')): ?>
        <a class="acceso" href="<?= html_escape(site_url('pagos')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pagos.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Pagos</h3><p>Cobra pedidos, separa cuentas y consulta los saldos.</p>
            <span class="acceso-abrir">Abrir pagos <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
    <?php if ($usuario->puede('inventario.ver')): ?>
        <a class="acceso" href="<?= html_escape(site_url('inventario')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/inventario.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Inventario</h3><p>Revisa insumos, cantidades y stock mínimo.</p>
            <span class="acceso-abrir">Abrir inventario <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
    <?php if ($usuario->puede('pedidos.ver')): ?>
        <a class="acceso" href="<?= html_escape(site_url('pedidos')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pedidos.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Pedidos</h3><p>Consulta los pedidos y sus productos.</p>
            <span class="acceso-abrir">Abrir pedidos <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
    <?php if ($usuario->puede('productos.ver')): ?>
        <a class="acceso" href="<?= html_escape(site_url('productos')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/productos.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Productos</h3><p>Consulta el catálogo y los precios de tu empresa.</p>
            <span class="acceso-abrir">Ver productos <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
    <?php if ($usuario->puede('usuarios.ver')): ?>
        <a class="acceso" href="<?= html_escape(site_url('usuarios')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/usuario.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Usuarios</h3><p>Administra las cuentas y roles de tu empresa.</p>
            <span class="acceso-abrir">Ver usuarios <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
</div>
<?php if (!$usuario->puede('inventario.ver') && !$usuario->puede('pedidos.ver') && !$usuario->puede('productos.ver') && !$usuario->puede('usuarios.ver')): ?>
    <p class="recuadro aviso">Tu cuenta todavía no tiene módulos asignados. Consulta al administrador de tu empresa.</p>
<?php endif; ?>
<?php $this->load->view('plantilla/pie'); ?>
