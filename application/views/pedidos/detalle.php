<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Detalle del pedido - Gestión de negocios</title>

    <link
        rel="stylesheet"
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
            Usuario:
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

                <a href="<?= html_escape(site_url('inventario')) ?>">
                    Inventario
                </a>

            <?php endif; ?>


            <?php if ($usuario->puede('pedidos.ver')): ?>

                <a
                    class="seleccionado"
                    href="<?= html_escape(site_url('pedidos')) ?>"
                    aria-current="page">
                    Pedidos
                </a>

            <?php endif; ?>


            <?= form_open('salir') ?>

            <button
                type="submit"
                class="boton-salir">
                Cerrar sesión
            </button>

            <?= form_close() ?>

        </nav>


        <main class="contenido">

            <p>

                <a href="<?= html_escape(site_url('pedidos')) ?>">
                    Volver a pedidos
                </a>

            </p>


            <section class="recuadro">

                <h1>
                    Pedido #<?= (int) $pedido['id'] ?>
                </h1>


                <p>
                    <strong>Referencia:</strong>

                    <?= html_escape(
                        $pedido['referencia']
                    ) ?>
                </p>


                <p>
                    <strong>Estado:</strong>

                    <?= html_escape(
                        $pedido['estado']
                    ) ?>
                </p>


                <p>
                    <strong>Pago:</strong>

                    <?= html_escape(
                        $pedido['estado_pago']
                    ) ?>
                </p>


                <h2>Productos del pedido</h2>


                <div class="tabla">

                    <table>

                        <thead>

                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio unitario</th>
                                <th>Subtotal</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($detalles as $detalle): ?>

                                <tr>

                                    <td>
                                        <?= html_escape(
                                            $detalle['producto_nombre']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= (int)
                                        $detalle['cantidad']
                                        ?>
                                    </td>


                                    <td>
                                        Q <?= number_format(
                                                (float)
                                                $detalle['precio_unitario'],
                                                2
                                            ) ?>
                                    </td>


                                    <td>
                                        Q <?= number_format(
                                                (float)
                                                $detalle['precio_unitario']
                                                    *
                                                    (int)
                                                    $detalle['cantidad'],
                                                2
                                            ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>


                            <?php if (empty($detalles)): ?>

                                <tr>

                                    <td colspan="4">
                                        Este pedido todavía no
                                        tiene productos.
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <p>
                    <strong>Total:</strong>

                    Q <?= number_format(
                            (float) $pedido['total'],
                            2
                        ) ?>
                </p>

            </section>

        </main>

    </div>


    <footer>
        Proyecto de gestión de negocios
    </footer>

</body>

</html>