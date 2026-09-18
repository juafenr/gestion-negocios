<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prueba de separación de datos</title><link rel="stylesheet" href="<?= html_escape(base_url('assets/app.css')) ?>"></head>
<body><header class="cabecera"><strong><?= html_escape($usuario->empresaNombre()) ?></strong><span><?= html_escape($usuario->nombre()) ?></span></header>
<main class="pagina-prueba"><a href="<?= html_escape(site_url('inicio')) ?>">Volver al inicio</a>
<h1>Productos de prueba</h1><p>Esta página comprueba la separación de datos entre empresas. No es el módulo de inventario.</p>
<div class="tabla"><table><thead><tr><th>Producto</th><th>Código</th><th>Precio</th><th>Detalle</th></tr></thead><tbody>
<?php foreach ($productos as $producto): ?>
<tr><td><?= html_escape($producto['nombre']) ?></td><td><?= html_escape($producto['sku']) ?></td><td>Q <?= number_format((float)$producto['precio'],2) ?></td><td><a href="<?= html_escape(site_url('productos/'.$producto['id'])) ?>">Ver detalle</a></td></tr>
<?php endforeach; ?>
<?php if (!$productos): ?><tr><td colspan="4">No hay productos de prueba.</td></tr><?php endif; ?>
</tbody></table></div>
<?= form_open('salir') ?><button type="submit" class="boton-salir">Cerrar sesión</button><?= form_close() ?>
</main></body></html>
