<?php
$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Detalle del producto',
    'seccion' => 'productos',
    'usuario' => $usuario
));
?>

<p>
    <a
        class="boton boton-secundario"
        href="<?= html_escape(site_url('productos')) ?>">
        Volver a productos
    </a>
</p>

<section class="recuadro">

    <h1><?= html_escape($producto['nombre']) ?></h1>

    <p>
        <strong>Código:</strong>
        <?= html_escape($producto['sku']) ?>
    </p>

    <p>
        <strong>Precio actual:</strong>
        Q <?= number_format((float) $producto['precio'], 2) ?>
    </p>

    <p>
        <strong>Disponibilidad:</strong>
        <?= (int) $producto['disponible'] === 1
            ? 'Disponible'
            : 'No disponible' ?>
    </p>

    <h2>Descripción</h2>

    <p>
        <?= $producto['descripcion'] !== ''
            ? nl2br(html_escape($producto['descripcion']))
            : 'Sin descripción.' ?>
    </p>

    <?php if ($usuario->puede('productos.gestionar')): ?>
        <p>
            <a
                class="boton"
                href="<?= html_escape(
                    site_url(
                        'productos/editar/' . (int) $producto['id']
                    )
                ) ?>">
                Editar producto
            </a>
        </p>
    <?php endif; ?>

</section>

<?php if ($puede_ver_historial): ?>

    <section class="recuadro">

        <h2>Historial de cambios de precio</h2>

        <p>
            Consulta los precios anteriores y quién realizó cada cambio.
        </p>

        <div
            class="tabla"
            role="region"
            aria-label="Historial de precios"
            tabindex="0">

            <table>
                <thead>
                    <tr>
                        <th scope="col">Fecha y hora</th>
                        <th scope="col">Responsable</th>
                        <th class="tabla-numero" scope="col">Precio anterior</th>
                        <th class="tabla-numero" scope="col">Precio nuevo</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($historial as $cambio): ?>
                        <tr>
                            <td>
                                <?= html_escape($cambio['cambiado_en']) ?>
                            </td>

                            <td>
                                <?= html_escape(
                                    $cambio['responsable'] ?? 'No disponible'
                                ) ?>
                            </td>

                            <td class="tabla-numero">
                                Q <?= number_format(
                                    (float) $cambio['precio_anterior'],
                                    2
                                ) ?>
                            </td>

                            <td class="tabla-numero">
                                Q <?= number_format(
                                    (float) $cambio['precio_nuevo'],
                                    2
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($historial)): ?>
                        <tr>
                            <td colspan="4">
                                <?= $pagina === 1
                                    ? 'No hay cambios de precio registrados.'
                                    : 'No hay registros en esta página.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>

        <p>Página <?= (int) $pagina ?></p>

        <?php if ($pagina > 1 || $siguiente): ?>
            <nav
                class="paginacion-inventario"
                aria-label="Páginas del historial de precios">

                <?php if ($pagina > 1): ?>
                    <a
                        class="boton boton-secundario boton-compacto"
                        href="<?= html_escape(
                            site_url(
                                'productos/' . (int) $producto['id']
                            ) . '?pagina=' . ($pagina - 1)
                        ) ?>">
                        Anterior
                    </a>
                <?php endif; ?>

                <?php if ($siguiente): ?>
                    <a
                        class="boton boton-secundario boton-compacto"
                        href="<?= html_escape(
                            site_url(
                                'productos/' . (int) $producto['id']
                            ) . '?pagina=' . ($pagina + 1)
                        ) ?>">
                        Siguiente
                    </a>
                <?php endif; ?>

            </nav>
        <?php endif; ?>

    </section>

<?php endif; ?>

<?php $this->load->view('plantilla/pie'); ?>