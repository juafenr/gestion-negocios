<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedidos</title>
    <link rel="stylesheet"
          href="<?= html_escape(base_url('assets/app.css')) ?>">
</head>
<body>
<header class="cabecera">
    <strong><?= html_escape($usuario->empresaNombre()) ?></strong>
    <span><?= html_escape($usuario->nombre()) ?></span>
</header>

<main class="pagina-prueba">
    <a href="<?= html_escape(site_url('inicio')) ?>">Volver al inicio</a>

    <h1>Pedidos</h1>

    <?php if ($mensaje): ?>
        <p role="status"><?= html_escape($mensaje) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p role="alert"><?= html_escape($error) ?></p>
    <?php endif; ?>

    <?php if ($usuario->puede('pedidos.crear')): ?>
        <h2>Crear pedido</h2>

        <?= form_open('pedidos/crear') ?>
            <label for="referencia">Mesa, cliente o referencia</label>
            <input
                type="text"
                id="referencia"
                name="referencia"
                maxlength="150"
                required
                placeholder="Ejemplo: Mesa 4"
                value="<?= html_escape($referencia) ?>"
            >
            <button type="submit">Crear pedido</button>
        <?= form_close() ?>
    <?php endif; ?>

    <h2>Últimos 50 pedidos</h2>

    <div class="tabla">
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Referencia</th>
                    <th>Estado</th>
                    <th>Pago</th>
                    <th>Total</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td><?= (int) $pedido['id'] ?></td>
                        <td><?= html_escape($pedido['referencia']) ?></td>
                        <td><?= html_escape($pedido['estado']) ?></td>
                        <td><?= html_escape($pedido['estado_pago']) ?></td>
                        <td>
                            Q <?= number_format((float) $pedido['total'], 2) ?>
                        </td>
                        <td><?= html_escape($pedido['creado_en']) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$pedidos): ?>
                    <tr>
                        <td colspan="6">Todavía no hay pedidos.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>