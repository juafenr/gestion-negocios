<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$base = rtrim((string) config_item('base_url'), '/').'/';
$index = trim((string) config_item('index_page'), '/');
$inicio = $base.($index !== '' ? $index.'/' : '').'inicio';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?> · Gestión de negocios</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($base.'assets/app.css?v=20261112-forms', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="pagina-error">
    <main class="recuadro caja-error">
        <span class="marca-icono"><img class="icono" src="<?= htmlspecialchars($base.'assets/iconos/negocio.svg', ENT_QUOTES, 'UTF-8') ?>" alt="" width="24" height="24"></span>
        <p class="sobretitulo">GESTIÓN DE NEGOCIOS</p>
        <h1><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="descripcion"><?= $message ?></div>
        <a class="boton" href="<?= htmlspecialchars($inicio, ENT_QUOTES, 'UTF-8') ?>">Ir al inicio</a>
    </main>
</body>
</html>
