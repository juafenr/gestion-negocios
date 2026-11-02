<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var UsuarioAutenticado $usuario */
/** @var array $insumo */
/** @var array $movimientos */
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1">
    <title>Historial de inventario</title>
    <link rel="stylesheet"
        href="<?= html_escape(base_url('assets/app.css')) ?>">
</head>

<body>

    <header class="cabecera">
        <div>
            <strong>
                <?= html_escape($usuario->empresaNombre()) ?>
            </strong>
            <p>Gestión de negocios</p>
        </div>
        <span><?= html_escape($usuario->nombre()) ?></span>
    </header>

    <div class="estructura">

        <nav class="menu" aria-label="Menú principal">
            <a href="<?= html_escape(site_url('inicio')) ?>">
                Inicio
            </a>

            <?php if ($usuario->puede('productos.ver')): ?>
                <a href="<?= html_escape(site_url('productos')) ?>">
                    Productos de prueba
                </a>
            <?php endif; ?>

            <a class="seleccionado"
                href="<?= html_escape(site_url('inventario')) ?>">
                Inventario
            </a>

            <?php if ($usuario->puede('pedidos.ver')): ?>
                <a href="<?= html_escape(site_url('pedidos')) ?>">
                    Pedidos
                </a>
            <?php endif; ?>

            <?= form_open('salir') ?>
            <button type="submit" class="boton-salir">
                Cerrar sesión
            </button>
            <?= form_close() ?>
        </nav>

        <main class="contenido">

            <p>
                <a href="<?= html_escape(site_url('inventario')) ?>">
                    Volver al inventario
                </a>
            </p>

            <h1>
                Historial: <?= html_escape($insumo['nombre']) ?>
            </h1>

            <?php
            $mensaje = $this->session
                ->flashdata('mensaje_inventario');
            ?>

            <?php if ($mensaje): ?>
                <p><?= html_escape($mensaje) ?></p>
            <?php endif; ?>

            <div class="tabla">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Anterior</th>
                            <th>Nueva</th>
                            <th>Motivo</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($movimientos as $movimiento): ?>
                            <tr>
                                <td>
                                    <?= html_escape($movimiento['fecha']) ?>
                                </td>
                                <td>
                                    <?= html_escape($movimiento['tipo']) ?>
                                </td>
                                <td>
                                    <?= html_escape($movimiento['cantidad']) ?>
                                </td>
                                <td>
                                    <?= html_escape(
                                        $movimiento['cantidad_anterior']
                                    ) ?>
                                </td>
                                <td>
                                    <?= html_escape(
                                        $movimiento['cantidad_nueva']
                                    ) ?>
                                </td>
                                <td>
                                    <?= html_escape($movimiento['motivo']) ?>
                                </td>
                                <td>
                                    <?= html_escape($movimiento['responsable']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($movimientos)): ?>
                            <tr>
                                <td colspan="7">
                                    No hay movimientos registrados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <footer>Proyecto de gestión de negocios</footer>

</body>

</html>