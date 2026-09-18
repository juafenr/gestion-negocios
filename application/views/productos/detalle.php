<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Detalle de prueba</title><link rel="stylesheet" href="<?= html_escape(base_url('assets/app.css')) ?>"></head>
<body><header class="cabecera"><strong><?= html_escape($usuario->empresaNombre()) ?></strong></header>
<main class="pagina-prueba"><a href="<?= html_escape(site_url('productos')) ?>">Volver a productos de prueba</a>
<section class="recuadro"><h1><?= html_escape($producto['nombre']) ?></h1><p>Código: <?= html_escape($producto['sku']) ?></p><p>Precio: Q <?= number_format((float)$producto['precio'],2) ?></p></section>
<?= form_open('salir') ?><button type="submit" class="boton-salir">Cerrar sesión</button><?= form_close() ?>
</main></body></html>
