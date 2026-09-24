<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Inventario</title>
    <link rel="stylesheet" href="<?= html_escape(base_url('assets/app.css')) ?>">
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

            <a class="seleccionado" href="<?= html_escape(site_url('inventario')) ?>">
                Inventario
            </a>

            <?= form_open('salir') ?>
            <button type="submit"  class="boton-salir">
                Salir
            </button>
            <?= form_close() ?>
        </nav>

        <main class="contenido">
            <p>
                <a href="<?= html_escape(site_url('inventario')) ?>">
                    Volver al inventario
                </a>
            </p>

            <section class="recuadro formulario-inventario">
                <h1>
                    <?= $insumo === NULL ? "Agregar insumo" : "Editar insumo" ?>
                </h1>

                <?php if (validation_errors()) : ?>
                    <div class="error">
                        <?= validation_errors() ?>
                    </div>
                <?php endif; ?>

                <?= form_open($accion, array('class' => 'formulario')) ?>

                <label for="nombre">
                    Nombre
                </label>

                <input
                    id="nombre"
                    name="nombre"
                    type="text"
                    maxlength="150"
                    required
                    value="<?= html_escape(set_value('nombre', $insumo['nombre'] ?? '')) ?>">

                <label for="cantidad_actual">
                    Cantidad actual
                </label>

                <input
                    id="cantidad_actual"
                    name="cantidad_actual"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                    value="<?= html_escape(set_value('cantidad_actual', $insumo['cantidad_actual'] ?? '0')) ?>">

                <label for="stock_minimo">
                    Stock mínimo
                </label>

                <input
                    id="stock_minimo"
                    name="stock_minimo"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                    value="<?= html_escape(set_value('stock_minimo', $insumo['stock_minimo'] ?? '0')) ?>">

                <label for="unidad_medida">
                    Unidad de medida
                </label>

                <?php $unidad_actual = set_value('unidad_medida', $insumo['unidad_medida'] ?? 'unidad'); ?>

                <select id="unidad_medida" name="unidad_medida" required>
                    <option value="unidad" <?= $unidad_actual === 'unidad' ? 'selected' : '' ?>>
                        Unidad
                    </option>

                    <option value="kg" <?= $unidad_actual === 'kg' ? 'selected' : '' ?>>
                        Kilogramos
                    </option>

                    <option value="g" <?= $unidad_actual === 'g' ? 'selected' : '' ?>>
                        Gramos
                    </option>

                    <option value="l" <?= $unidad_actual === 'l' ? 'selected' : '' ?>>
                        Litros
                    </option>

                    <option value="ml" <?= $unidad_actual === 'ml' ? 'selected' : '' ?>>
                        Mililitros
                    </option>
                </select>

                <button type="submit">
                    Guardar
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