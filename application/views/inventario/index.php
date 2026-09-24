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
            <button type="submit" , class="boton-salir">
                Salir
            </button>
            <?= form_close() ?>
        </nav>

        <main class="contenido">
    <h1>Inventario</h1>

    <?php if ($usuario->puede('inventario.gestionar')): ?>
        <p>
            <a href="<?= html_escape(site_url('inventario/crear')) ?>">
                Agregar insumo
            </a>
        </p>
    <?php endif; ?>

    <div class="tabla">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Cantidad actual</th>
                    <th>Stock mínimo</th>
                    <th>Unidad</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($insumos as $item): ?>
                    <tr>
                        <td><?= html_escape($item['nombre']) ?></td>
                        <td><?= html_escape($item['cantidad_actual']) ?></td>
                        <td><?= html_escape($item['stock_minimo']) ?></td>
                        <td><?= html_escape($item['unidad_medida']) ?></td>
                        <td>
                            <?php if ($usuario->puede('inventario.gestionar')): ?>
                                <a href="<?= html_escape(
                                    site_url('inventario/'.$item['id'].'/editar')
                                ) ?>">
                                    Editar
                                </a>
                            <?php else: ?>
                                Solo consulta
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($insumos)): ?>
                    <tr>
                        <td colspan="5">
                            Todavía no hay insumos registrados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

    </div>

    <footer>
        Proyecto de gestión de negocios
    </footer>

</body>

</html>