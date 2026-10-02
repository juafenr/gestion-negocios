<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle de producto - Gestión de negocios</title>
    <link rel="stylesheet" href="<?= html_escape(base_url('assets/app.css')) ?>">
</head>

<body>

    <header class="cabecera">
        <div>
            <strong><?= html_escape($usuario->empresaNombre()) ?></strong>
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
                <a
                    class="seleccionado"
                    href="<?= html_escape(site_url('productos')) ?>"
                    aria-current="page">
                    Productos de prueba
                </a>
            <?php endif; ?>

            <?php if ($usuario->puede('inventario.ver')): ?>
                <a href="<?= html_escape(site_url('inventario')) ?>">
                    Inventario
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
                <a href="<?= html_escape(site_url('productos')) ?>">
                    Volver a productos de prueba
                </a>
            </p>

            <section class="recuadro">

                <h1>
                    <?= html_escape($producto['nombre']) ?>
                </h1>

                <p>
                    Código:
                    <?= html_escape($producto['sku']) ?>
                </p>

                <p>
                    Precio:
                    Q <?= number_format(
                            (float) $producto['precio'],
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