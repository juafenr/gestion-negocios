<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Nuevo pedido - Gestión de negocios</title>

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


            <section class="recuadro formulario">

                <h1>Nuevo pedido</h1>


                <?php if (validation_errors()): ?>

                    <div class="error">
                        <?= validation_errors() ?>
                    </div>

                <?php endif; ?>


                <?= form_open('pedidos/crear') ?>


                <label for="referencia">
                    Referencia del pedido
                </label>

                <input
                    id="referencia"
                    name="referencia"
                    type="text"
                    maxlength="100"
                    required
                    placeholder="Ej. Mesa 5, Cliente López, Orden 123"
                    value="<?= html_escape(
                                set_value('referencia')
                            ) ?>">


                <button type="submit">
                    Crear pedido
                </button>


                <?= form_close() ?>

            </section>

        </main>

    </div>


    <footer>
        Proyecto de gestión de negocios
    </footer>

</body>

</html>