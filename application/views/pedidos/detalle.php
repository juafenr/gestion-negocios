<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle del pedido</title>
    <link rel="stylesheet"
          href="<?= html_escape(base_url('assets/app.css')) ?>">
</head>
<body>
<header class="cabecera">
    <strong><?= html_escape($usuario->empresaNombre()) ?></strong>
    <span><?= html_escape($usuario->nombre()) ?></span>
</header>

<main class="pagina-prueba">
    <a href="<?= html_escape(site_url('pedidos')) ?>">
        Volver a pedidos
    </a>

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
                            <?= html_escape($detalle['producto_nombre']) ?>
                        </td>
                        <td><?= (int) $detalle['cantidad'] ?></td>
                        <td>
                            Q <?= number_format(
                                (float) $detalle['precio_unitario'], 2
                            ) ?>
                        </td>
                        <td>
                            Q <?= number_format(
                                (float) $detalle['precio_unitario']
                                * (int) $detalle['cantidad'], 2
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$detalles): ?>
                    <tr>
                        <td colspan="4">
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
</main>
</body>
</html>