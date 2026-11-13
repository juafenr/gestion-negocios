<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($titulo) ?> - Gestión de negocios</title>
    <meta name="theme-color" content="#ce530c">
    <link rel="stylesheet" href="<?= html_escape(base_url('assets/app.css?v=20261112-botones')) ?>">
    <script src="<?= html_escape(base_url('assets/interfaz.js?v=20261112')) ?>" defer></script>
    <script src="<?= html_escape(base_url('assets/formularios.js?v=20261112')) ?>" defer></script>
</head>

<body>
    <a class="saltar-contenido" href="#contenido">Ir al contenido</a>
    <header class="cabecera">
        <button id="abrir-menu" class="abrir-menu boton-secundario" type="button" aria-label="Abrir menú principal" aria-controls="menu-principal" aria-expanded="false" hidden>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="marca">
            <span class="marca-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/negocio.svg')) ?>" alt="" width="22" height="22"></span>
            <div>
                <strong><?= html_escape($usuario->empresaNombre()) ?></strong>
                <p>Gestión de negocios</p>
            </div>
        </div>
        <div class="contexto-pagina"><span>Espacio de trabajo /</span><strong><?= html_escape($titulo) ?></strong></div>
        <div class="cuenta" aria-label="<?= html_escape($usuario->nombre().' · '.$usuario->rolNombre()) ?>">
            <img class="icono" src="<?= html_escape(base_url('assets/iconos/usuario.svg')) ?>" alt="" width="22" height="22">
            <div><strong><?= html_escape($usuario->nombre()) ?></strong><span><?= html_escape($usuario->rolNombre()) ?></span></div>
        </div>
    </header>
    <div class="estructura">
        <nav id="menu-principal" class="menu" aria-label="Menú principal">
            <p class="menu-titulo">MENÚ PRINCIPAL</p>
            <a class="<?= $seccion === 'inicio' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('inicio')) ?>" <?= $seccion === 'inicio' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/inicio.svg')) ?>" alt="" width="22" height="22"><span>Inicio</span></a>
            <?php if ($usuario->puede('productos.ver')): ?>
                <a class="<?= $seccion === 'productos' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('productos')) ?>" <?= $seccion === 'productos' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/productos.svg')) ?>" alt="" width="22" height="22"><span>Productos</span></a>
            <?php endif; ?>
            <?php if ($usuario->puede('inventario.ver')): ?>
                <a class="<?= $seccion === 'inventario' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('inventario')) ?>" <?= $seccion === 'inventario' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/inventario.svg')) ?>" alt="" width="22" height="22"><span>Inventario</span></a>
            <?php endif; ?>
            <?php if ($usuario->puede('pedidos.ver')): ?>
                <a class="<?= $seccion === 'pedidos' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('pedidos')) ?>" <?= $seccion === 'pedidos' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/pedidos.svg')) ?>" alt="" width="22" height="22"><span>Pedidos</span></a>
            <?php endif; ?>
            <?php if ($usuario->puede('pedidos.actualizar_cocina')): ?>
                <a class="<?= $seccion === 'cocina' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('cocina')) ?>" <?= $seccion === 'cocina' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/correcto.svg')) ?>" alt="" width="22" height="22"><span>Cocina</span></a>
            <?php endif; ?>
            <?php if ($usuario->puede('ventas.ver') && $usuario->puede('pedidos.ver')): ?>
                <a class="<?= $seccion === 'pagos' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('pagos')) ?>" <?= $seccion === 'pagos' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/pagos.svg')) ?>" alt="" width="22" height="22"><span>Pagos</span></a>
            <?php endif; ?>
            <?php if ($usuario->puede('usuarios.ver')): ?>
                <a class="<?= $seccion === 'usuarios' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('usuarios')) ?>" <?= $seccion === 'usuarios' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/usuario.svg')) ?>" alt="" width="22" height="22"><span>Usuarios</span></a>
            <?php endif; ?>
            <?php if ($usuario->puede('usuarios.gestionar')): ?>
                <a class="<?= $seccion === 'configuracion' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('configuracion/propina')) ?>" <?= $seccion === 'configuracion' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/pagos.svg')) ?>" alt="" width="22" height="22"><span>Configuración</span></a>
            <?php endif; ?>
            <?php if ($usuario->puede('ventas.historial')): ?>
                <a class="<?= $seccion === 'ventas' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('ventas')) ?>" <?= $seccion === 'ventas' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/ventas.svg')) ?>" alt="" width="22" height="22"><span>Ventas</span></a>
            <?php endif; ?>
            <?php if ($usuario->puede('compras.ver')): ?>
                <a class="<?= $seccion === 'compras' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('compras')) ?>" <?= $seccion === 'compras' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/compras.svg')) ?>" alt="" width="22" height="22"><span>Compras</span></a>
            <?php endif; ?>
            <?php if ($usuario->puede('compras.ver')): ?>
                <a
                    class="<?= $seccion === 'proveedores' ? 'seleccionado' : '' ?>"
                    href="<?= html_escape(site_url('proveedores')) ?>"
                    <?= $seccion === 'proveedores' ? 'aria-current="page"' : '' ?>>
                    <img
                        class="icono"
                        src="<?= html_escape(base_url('assets/iconos/compras.svg')) ?>"
                        alt=""
                        width="22"
                        height="22">
                    <span>Proveedores</span>
                </a>
            <?php endif; ?>
            <?= form_open('salir', array('class' => 'formulario-salir')) ?>
            <button type="submit" class="boton-salir" title="Cerrar tu sesión"><img class="icono" src="<?= html_escape(base_url('assets/iconos/salir.svg')) ?>" alt="" width="22" height="22"><span>Cerrar sesión</span></button>
            <?= form_close() ?>
        </nav>
        <button id="velo-menu" class="velo-menu" type="button" aria-label="Cerrar menú principal" tabindex="-1" hidden></button>
        <main id="contenido" class="contenido" tabindex="-1">
