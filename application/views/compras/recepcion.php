<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Recepción de compra',
    'seccion' => 'compras',
    'usuario' => $usuario
));
?>

<div class="titulo-acciones">
    <div>
        <p class="sobretitulo">COMPRAS</p>

        <h1>
            Recibir solicitud #<?= (int) $solicitud['numero'] ?>
        </h1>

        <p class="descripcion">
            Registra las cantidades recibidas del proveedor.
            Puedes realizar recepciones parciales.
        </p>
    </div>

    <a
        class="boton boton-secundario"
        href="<?= html_escape(
                    site_url('compras/' . (int) $solicitud['id'])
                ) ?>">
        Volver
    </a>
</div>

<?php
$error = $this->session->flashdata('error_compra');
?>

<?php if ($error): ?>
    <p class="error" role="alert">
        <?= html_escape($error) ?>
    </p>
<?php endif; ?>

<section class="recuadro">

    <h2>
        Proveedor:
        <?= html_escape($solicitud['proveedor_nombre']) ?>
    </h2>

    <p>
        Estado:
        <strong><?= html_escape($solicitud['estado']) ?></strong>
    </p>

    <?= form_open(
        'compras/' . (int) $solicitud['id'] . '/recepcion', array('class'=>'formulario-ui','data-ui-form'=>'')
    ) ?>

    <input
        type="hidden"
        name="version"
        value="<?= (int) $solicitud['version'] ?>">

    <input
        type="hidden"
        name="clave"
        value="<?= html_escape($clave) ?>">

    <div class="tabla"
        role="region"
        aria-label="Cantidades de recepción"
        tabindex="0">

        <table>
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th>Solicitado</th>
                    <th>Recibido</th>
                    <th>Pendiente</th>
                    <th>Recibir ahora</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($lineas as $linea): ?>

                    <?php
                    $solicitado = (float) $linea['cantidad'];
                    $recibido = (float) $linea['recibido'];
                    $pendiente = max(
                        0,
                        round($solicitado - $recibido, 2)
                    );
                    ?>

                    <tr>
                        <td>
                            <?= html_escape(
                                $linea['insumo_nombre']
                            ) ?>
                        </td>

                        <td>
                            <?= number_format($solicitado, 2) ?>
                        </td>

                        <td>
                            <?= number_format($recibido, 2) ?>
                        </td>

                        <td>
                            <?= number_format($pendiente, 2) ?>
                            <?= html_escape(
                                $linea['unidad_medida']
                            ) ?>
                        </td>

                        <td>
                            <input
                                type="number"
                                name="cantidades[<?= (int) $linea['id'] ?>]"
                                value="0"
                                min="0"
                                max="<?= html_escape(
                                            number_format(
                                                $pendiente,
                                                2,
                                                '.',
                                                ''
                                            )
                                        ) ?>"
                                step="0.01"
                                required
                                <?= $pendiente <= 0
                                    ? 'readonly' : '' ?>>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <p class="descripcion">
        Ingresa cero para los insumos que todavía
        no hayas recibido. No puedes recibir más
        de la cantidad pendiente.
    </p>

    <div class="acciones-formulario"><a class="boton boton-secundario" href="<?= html_escape(site_url('compras/'.(int)$solicitud['id'])) ?>">Cancelar</a><button type="submit">
        Registrar recepción
    </button></div>

    <?= form_close() ?>

</section>

<?php $this->load->view('plantilla/pie'); ?>
