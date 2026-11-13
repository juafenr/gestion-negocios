<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Sugerencias de compra',
    'seccion' => 'inventario',
    'usuario' => $usuario
));
?>

<div class="titulo-acciones">
    <div>
        <p class="sobretitulo">REABASTECIMIENTO</p>
        <h1>Sugerencias de compra</h1>
        <p class="descripcion">
            Consulta las cantidades recomendadas para
            alcanzar el stock mínimo de cada insumo.
        </p>
    </div>

    <a
        class="boton boton-secundario"
        href="<?= html_escape(site_url('inventario')) ?>">
        Volver al inventario
    </a>
</div>

<div
    class="tabla"
    role="region"
    aria-label="Sugerencias de compra"
    tabindex="0">
    <table>
        <thead>
            <tr>
                <th>Insumo</th>
                <th class="tabla-numero">Cantidad actual</th>
                <th class="tabla-numero">Stock mínimo</th>
                <th>Unidad</th>
                <th class="tabla-numero">Cantidad sugerida</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($insumos as $insumo): ?>
                <tr>
                    <td>
                        <?= html_escape($insumo['nombre']) ?>
                    </td>

                    <td class="tabla-numero">
                        <?= number_format(
                            (float) $insumo['cantidad_actual'],
                            2
                        ) ?>
                    </td>

                    <td class="tabla-numero">
                        <?= number_format(
                            (float) $insumo['stock_minimo'],
                            2
                        ) ?>
                    </td>

                    <td>
                        <?= html_escape($insumo['unidad_medida']) ?>
                    </td>

                    <td class="tabla-numero">
                        <strong>
                            <?= number_format(
                                (float) $insumo['cantidad_sugerida'],
                                2
                            ) ?>
                        </strong>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($insumos)): ?>
                <tr>
                    <td colspan="5">
                        No hay insumos que requieran reposición.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->load->view('plantilla/pie'); ?>