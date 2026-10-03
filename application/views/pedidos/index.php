<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Pedidos - Gestión de negocios</title>

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

            <h1>Pedidos</h1>

            <?php if ($usuario->puede('pedidos.crear')): ?>

                <p>
                    <a href="<?= html_escape(site_url('pedidos/crear')) ?>">
                        Agregar pedido
                    </a>
                </p>

            <?php endif; ?>

            <p>
                Consulta los pedidos registrados para la empresa.
            </p>


            <div class="tabla">

                <table>

                    <thead>

                        <tr>
                            <th>Número</th>
                            <th>Referencia</th>
                            <th>Estado</th>
                            <th>Pago</th>
                            <th>Total</th>
                            <th>Detalle</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($pedidos as $pedido): ?>

                            <tr>

                                <td>
                                    #<?= (int) $pedido['id'] ?>
                                </td>


                                <td>
                                    <?= html_escape(
                                        $pedido['referencia']
                                    ) ?>
                                </td>


                                <td>
                                    <?= html_escape(
                                        $pedido['estado']
                                    ) ?>
                                </td>


                                <td>
                                    <?= html_escape(
                                        $pedido['estado_pago']
                                    ) ?>
                                </td>


                                <td>
                                    Q <?= number_format(
                                            (float) $pedido['total'],
                                            2
                                        ) ?>
                                </td>


                                <td>

                                    <a href="<?= html_escape(
                                                    site_url(
                                                        'pedidos/' .
                                                            $pedido['id']
                                                    )
                                                ) ?>">
                                        Ver detalle
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                        <?php if (empty($pedidos)): ?>

                            <tr>

                                <td colspan="6">
                                    No hay pedidos registrados.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </main>

    </div>


    <footer>
        Proyecto de gestión de negocios
    </footer>

</body>

</html>