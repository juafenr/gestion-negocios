<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($titulo) ?> - Gestión de negocios</title>
    <link rel="stylesheet" href="<?= html_escape(base_url('assets/app.css?v=3')) ?>">
</head>
<body>
    <a class="saltar-contenido" href="#contenido">Ir al contenido</a>
    <header class="cabecera">
        <div class="marca">
            <span class="marca-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/negocio.svg')) ?>" alt="" width="22" height="22"></span>
            <div>
                <strong><?= html_escape($usuario->empresaNombre()) ?></strong>
                <p>Gestión de negocios</p>
            </div>
        </div>
        <div class="cuenta">
            <img class="icono" src="<?= html_escape(base_url('assets/iconos/usuario.svg')) ?>" alt="" width="22" height="22">
            <div><strong><?= html_escape($usuario->nombre()) ?></strong><span><?= html_escape($usuario->rolNombre()) ?></span></div>
        </div>
    </header>
    <div class="estructura">
        <nav class="menu" aria-label="Menú principal">
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
            <?php if ($usuario->puede('ventas.ver') && $usuario->puede('pedidos.ver')): ?>
                <a class="<?= $seccion === 'pagos' ? 'seleccionado' : '' ?>" href="<?= html_escape(site_url('pagos')) ?>" <?= $seccion === 'pagos' ? 'aria-current="page"' : '' ?>><img class="icono" src="<?= html_escape(base_url('assets/iconos/pagos.svg')) ?>" alt="" width="22" height="22"><span>Pagos</span></a>
            <?php endif; ?>
            <?= form_open('salir' , array('class' => 'formulario-salir')) ?>
                <button type="submit" class="boton-salir" title="Cerrar tu sesión"><img class="icono" src="<?= html_escape(base_url('assets/iconos/salir.svg')) ?>" alt="" width="22" height="22"><span>Cerrar sesión</span></button>
            <?= form_close() ?>
        </nav>
        <main id="contenido" class="contenido" tabindex="-1">
