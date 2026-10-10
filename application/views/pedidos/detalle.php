<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle del pedido - Gestión de negocios</title>

    <link
        rel="stylesheet"
        href="<?= html_escape(base_url('assets/app.css')) ?>"
    >
</head>

<body>
    <?php
    $puede_editar = (
        $pedido['estado'] === 'borrador'
        && $usuario->puede('pedidos.crear')
    );
    ?>

    <header class="cabecera">
        <div>
            <strong>
                <?= html_escape($usuario->empresaNombre()) ?>
            </strong>
            <p>Gestión de negocios</p>
        </div>

        <span>
            Usuario: <?= html_escape($usuario->nombre()) ?>
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
                    aria-current="page"
                >
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
                <a href="<?= html_escape(site_url('pedidos')) ?>">
                    Volver a pedidos
                </a>
            </p>

            <section class="recuadro">
                <h1>Pedido #<?= (int) $pedido['id'] ?></h1>

                <p>
                    <strong>Referencia:</strong>
                    <?= html_escape($pedido['referencia']) ?>
                </p>

                <p>
                    <strong>Estado:</strong>
                    <?= html_escape($pedido['estado']) ?>
                </p>

                <p>
                    <strong>Pago:</strong>
                    <?= html_escape($pedido['estado_pago']) ?>
                </p>

                <?php if ($puede_editar): ?>
                    <h2>Agregar producto</h2>

                    <?php if (!empty($productos)): ?>
                        <?= form_open(
                            'pedidos/agregar/' . (int) $pedido['id']
                        ) ?>

                            <p>
                                <label for="producto_id">Producto</label>

                                <select
                                    id="producto_id"
                                    name="producto_id"
                                    required
                                >
                                    <option value="">
                                        Selecciona un producto
                                    </option>

                                    <?php foreach ($productos as $producto): ?>
                                        <option
                                            value="<?= (int) $producto['id'] ?>"
                                        >
                                            <?= html_escape($producto['nombre']) ?>
                                            — Q <?= number_format(
                                                (float) $producto['precio'],
                                                2
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>

                            <p>
                                <label for="cantidad">Cantidad</label>

                                <input
                                    type="number"
                                    id="cantidad"
                                    name="cantidad"
                                    min="1"
                                    max="999"
                                    step="1"
                                    value="1"
                                    required
                                >
                            </p>

                            <button type="submit">
                                Agregar al pedido
                            </button>

                        <?= form_close() ?>
                    <?php else: ?>
                        <p>
                            No hay productos registrados para esta empresa.
                        </p>
                    <?php endif; ?>
                <?php endif; ?>

                <h2>Productos del pedido</h2>

                <div class="tabla">
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio unitario</th>
                                <th>Subtotal</th>
                                <th>Acciones</th>
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
                                        <?php if ($puede_editar): ?>
                                            <?= form_open(
                                                'pedidos/actualizar/'
                                                . (int) $pedido['id']
                                                . '/'
                                                . (int) $detalle['id']
                                            ) ?>

                                                <input
                                                    type="number"
                                                    name="cantidad"
                                                    min="1"
                                                    max="999"
                                                    step="1"
                                                    required
                                                    aria-label="Cantidad de <?= html_escape(
                                                        $detalle['producto_nombre']
                                                    ) ?>"
                                                    value="<?= (int) $detalle['cantidad'] ?>"
                                                >

                                                <button type="submit">
                                                    Actualizar cantidad
                                                </button>

                                            <?= form_close() ?>
                                        <?php else: ?>
                                            <?= (int) $detalle['cantidad'] ?>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        Q <?= number_format(
                                            (float) $detalle['precio_unitario'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        Q <?= number_format(
                                            (float) $detalle['precio_unitario']
                                            * (int) $detalle['cantidad'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        <?php if ($puede_editar): ?>
                                            <?= form_open(
                                                'pedidos/quitar/'
                                                . (int) $pedido['id']
                                                . '/'
                                                . (int) $detalle['id']
                                            ) ?>

                                                <button type="submit">
                                                    Quitar
                                                </button>

                                            <?= form_close() ?>
                                        <?php else: ?>
                                            Sin acciones disponibles
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($detalles)): ?>
                                <tr>
                                    <td colspan="5">
                                        Este pedido todavía no tiene productos.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <p>
                    <strong>Total:</strong>
                    Q <?= number_format((float) $pedido['total'], 2) ?>
                </p>
            </section>
        </main>
    </div>

    <footer>
        Proyecto de gestión de negocios
    </footer>
</body>
</html>