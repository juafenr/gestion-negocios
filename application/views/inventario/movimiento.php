<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var UsuarioAutenticado $usuario */
/** @var array $insumo */
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1">
    <title>Movimiento de inventario</title>
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
        <span>
            <?= html_escape($usuario->nombre()) ?>
            (<?= html_escape($usuario->rolNombre()) ?>)
        </span>
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

            <?php if ($usuario->puede('inventario.ver')): ?>
                <a class="seleccionado"
                    href="<?= html_escape(site_url('inventario')) ?>">
                    Inventario
                </a>
            <?php endif; ?>

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

            <section class="recuadro formulario-inventario">

                <h1>Registrar movimiento</h1>

                <p>
                    <strong>Insumo:</strong>
                    <?= html_escape($insumo['nombre']) ?>
                </p>

                <p>
                    <strong>Existencia actual:</strong>
                    <?= html_escape($insumo['cantidad_actual']) ?>
                    <?= html_escape($insumo['unidad_medida']) ?>
                </p>

                <?= form_open(
                    'inventario/' . (int) $insumo['id'] . '/movimiento',
                    array('class' => 'formulario')
                ) ?>

                <label for="tipo">Tipo de movimiento</label>

                <select id="tipo" name="tipo" required>
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                    <option value="ajuste">Ajuste</option>
                </select>

                <p>
                    Entrada: suma existencias.
                    Salida: descuenta existencias.
                    Ajuste: establece la cantidad final.
                </p>

                <label for="cantidad">Cantidad</label>

                <input
                    type="number"
                    id="cantidad"
                    name="cantidad"
                    min="0"
                    max="99999999.99"
                    step="0.01"
                    required>

                <label for="motivo">Motivo</label>

                <input
                    type="text"
                    id="motivo"
                    name="motivo"
                    maxlength="255"
                    required
                    placeholder="Ej. Compra, consumo, conteo físico">

                <button type="submit">
                    Guardar movimiento
                </button>

                <?= form_close() ?>

            </section>

        </main>
    </div>

    <footer>Proyecto de gestión de negocios</footer>

</body>

</html>