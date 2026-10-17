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
<h2>Accesos rápidos</h2>
<div class="accesos">
    <?php if ($usuario->puede('pedidos.actualizar_cocina')): ?>
        <a class="acceso" href="<?= html_escape(site_url('cocina')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/correcto.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Cocina</h3><p>Consulta los pedidos confirmados y sus productos.</p>
            <span class="acceso-abrir">Abrir cocina <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
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
