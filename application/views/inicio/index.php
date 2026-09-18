<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio - Gestión de negocios</title>
    <link rel="stylesheet" href="<?= html_escape(base_url('assets/app.css')) ?>">
</head>
<body>
    <header class="cabecera">
        <div>
            <strong><?= html_escape($usuario->empresaNombre()) ?></strong>
            <p>Gestión de negocios</p>
        </div>
        <span>Usuario: <?= html_escape($usuario->nombre()) ?></span>
    </header>

    <div class="estructura">
        <nav class="menu" aria-label="Menú principal">
            <a class="seleccionado" href="<?= html_escape(site_url('inicio')) ?>" aria-current="page">Inicio</a>
            <?= form_open('salir') ?>
                <button type="submit" class="boton-salir">Cerrar sesión</button>
            <?= form_close() ?>
        </nav>

        <main class="contenido">
            <h1>Inicio</h1>
            <section class="recuadro">
                <h2>Bienvenido, <?= html_escape($usuario->nombre()) ?></h2>
                <p>Has iniciado sesión en <strong><?= html_escape($usuario->empresaNombre()) ?></strong>.</p>
                <p>Desde aquí podrás acceder a las opciones de tu empresa conforme se agreguen al sistema.</p>
            </section>
            <section class="recuadro aviso">
                <h2>Sistema en desarrollo</h2>
                <p>Por ahora está disponible el inicio y cierre de sesión. Los módulos se agregarán en las siguientes entregas.</p>
            </section>
        </main>
    </div>
    <footer>Proyecto de gestión de negocios</footer>
</body>
</html>
