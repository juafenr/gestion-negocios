<?php
$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Revisar solicitud',
    'seccion' => 'compras',
    'usuario' => $usuario
));

$s = $solicitud;
?>

<p>
    <a class="boton boton-secundario"
        href="<?= html_escape(site_url(ui_ruta_retorno('compras'))) ?>">
        Volver a solicitudes
    </a>
</p>

<?php if (!empty($error)): ?>
    <p class="error" role="alert">
        <?= html_escape($error) ?>
    </p>
<?php endif; ?>

<?php
$mensaje = $this->session->flashdata('mensaje_compra');
$errorEstado = $this->session->flashdata('error_compra');
?>



<?php if ($errorEstado): ?>
    <p class="error" role="alert">
        <?= html_escape($errorEstado) ?>
    </p>
<?php endif; ?>


<section class="recuadro">

    <h1>
        Solicitud #<?= (int) $s['numero'] ?>
    </h1>

    <p>
        Fecha:
        <strong><?= html_escape($s['fecha']) ?></strong>

        · Estado:
        <?= ui_estado($s['estado'], 'compra') ?>
    </p>


    <!-- Información del proveedor -->

    <h2>
        <?= html_escape($s['proveedor_nombre']) ?>
    </h2>

    <?php foreach (
        array(
            'contacto' => 'Contacto',
            'telefono' => 'Teléfono',
            'correo' => 'Correo',
            'direccion' => 'Dirección'
        ) as $campo => $etiqueta
    ): ?>

        <?php if ($s['proveedor_' . $campo] !== ''): ?>

            <p>
                <?= $etiqueta ?>:
                <?= html_escape($s['proveedor_' . $campo]) ?>
            </p>

        <?php endif; ?>

    <?php endforeach; ?>


    <!-- Insumos solicitados -->

    <div class="tabla">

        <table>

            <thead>
                <tr>
                    <th>Insumo</th>
                    <th class="tabla-numero">Cantidad solicitada</th>
                    <th class="tabla-numero">Recibido</th>
                    <th class="tabla-numero">Pendiente</th>
                    <th>Unidad</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($lineas as $l): ?>

                    <tr>
                        <?php
                        $solicitado = (float) $l['cantidad'];
                        $recibido = (float) $l['recibido'];
                        $pendiente = max(0, round($solicitado - $recibido, 2));
                        ?>

                        <td>
                            <?= html_escape($l['insumo_nombre']) ?>
                        </td>

                        <td class="tabla-numero">
                            <?= number_format($solicitado, 2) ?>
                        </td>

                        <td class="tabla-numero">
                            <?= number_format($recibido, 2) ?>
                        </td>

                        <td class="tabla-numero">
                            <?= number_format($pendiente, 2) ?>
                        </td>

                        <td>
                            <?= html_escape($l['unidad_medida']) ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if (empty($lineas)): ?>

                    <tr>
                        <td colspan="3">
                            La solicitud no tiene insumos registrados.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- Observaciones -->

    <h2 class="subtitulo-linea">
        Observaciones
    </h2>

    <p>
        <?= nl2br(html_escape(
            $s['observaciones'] !== ''
                ? $s['observaciones']
                : 'Sin observaciones'
        )) ?>
    </p>


    <!-- Acciones existentes -->

    <div class="acciones-documento">

        <?php if (
            $s['estado'] === 'borrador' &&
            $usuario->puede('compras.gestionar')
        ): ?>

            <a class="boton"
                href="<?= html_escape(
                            site_url(
                                'compras/' .
                                    (int) $s['id'] .
                                    '/editar'
                            )
                        ) ?>">

                <img
                    class="icono"
                    src="<?= html_escape(
                                base_url('assets/iconos/editar.svg')
                            ) ?>"
                    alt="">

                Editar borrador
            </a>

        <?php endif; ?>


        <?php if ($usuario->puede('compras.exportar')): ?>

            <a class="boton boton-secundario"
                href="<?= html_escape(
                            site_url(
                                'compras/' .
                                    (int) $s['id'] .
                                    '/documento'
                            )
                        ) ?>">
                Copiar o descargar
            </a>

        <?php endif; ?>

    </div>


    <!-- Confirmación existente -->

    <?php if (
        $s['estado'] === 'borrador' &&
        $usuario->puede('compras.confirmar')
    ): ?>

        <h2 class="subtitulo-linea">
            Confirmar documento
        </h2>

        <?= form_open(
            'compras/' .
                (int) $s['id'] .
                '/confirmar'
        ) ?>

        <input
            type="hidden"
            name="version"
            value="<?= (int) $s['version'] ?>">

        <p>
            <label>
                <input
                    type="checkbox"
                    name="revisada"
                    value="1"
                    required>

                Revisé el proveedor, las cantidades
                y las observaciones. Al confirmar,
                el documento dejará de ser editable.
            </label>
        </p>

        <button type="submit">
            Confirmar solicitud
        </button>

        <?= form_close() ?>

    <?php endif; ?>


    <!-- RF-57: Marcar solicitud como enviada -->

    <?php if (
        $s['estado'] === 'confirmada' &&
        $usuario->puede('compras.confirmar')
    ): ?>

        <h2 class="subtitulo-linea">
            Enviar solicitud
        </h2>

        <p class="descripcion">
            Marca la solicitud como enviada cuando
            haya sido remitida al proveedor.
        </p>

        <?= form_open(
            'compras/' .
                (int) $s['id'] .
                '/estado'
        ) ?>

        <input
            type="hidden"
            name="version"
            value="<?= (int) $s['version'] ?>">

        <input
            type="hidden"
            name="estado"
            value="enviada">

        <button type="submit">
            Marcar como enviada
        </button>

        <?= form_close() ?>

    <?php endif; ?>


    <!-- RF-57: Cancelación de solicitud -->

    <?php if (
        in_array(
            $s['estado'],
            array('borrador', 'confirmada', 'enviada'),
            TRUE
        ) &&
        $usuario->puede('compras.confirmar')
    ): ?>

        <h2 class="subtitulo-linea">
            Cancelar solicitud
        </h2>

        <p class="descripcion">
            Indica el motivo de cancelación.
            Esta acción quedará registrada
            en el historial de la solicitud.
        </p>

        <?= form_open(
            'compras/' .
                (int) $s['id'] .
                '/estado'
        ) ?>

        <input
            type="hidden"
            name="version"
            value="<?= (int) $s['version'] ?>">

        <input
            type="hidden"
            name="estado"
            value="cancelada">

        <p>
            <label for="motivo_cancelacion">
                Motivo de cancelación
            </label>
        </p>

        <p>
            <textarea
                id="motivo_cancelacion"
                name="motivo"
                maxlength="1000"
                rows="3"
                required
                placeholder="Escribe el motivo de cancelación..."></textarea>
        </p>

        <button
            type="submit"
            class="boton boton-secundario">
            Cancelar solicitud
        </button>

        <?= form_close() ?>

    <?php endif; ?>

    <!-- RF-59 y RF-60: Recepción de compras -->

    <?php if (
        $s['estado'] === 'enviada' &&
        $usuario->puede('compras.confirmar')
    ): ?>

        <h2 class="subtitulo-linea">
            Recepción de mercancía
        </h2>

        <p class="descripcion">
            Registra una recepción total o parcial
            de los insumos solicitados.
        </p>

        <a
            class="boton"
            href="<?= html_escape(
                        site_url(
                            'compras/' .
                                (int) $s['id'] .
                                '/recepcion'
                        )
                    ) ?>">
            Registrar recepción
        </a>

    <?php endif; ?>


    <!-- Información de confirmación -->

    <?php if (!empty($s['confirmado_en'])): ?>

        <p>
            Confirmada el
            <?= html_escape($s['confirmado_en']) ?>.
        </p>

    <?php endif; ?>


    <!-- Información de estados finales -->

    <?php if ($s['estado'] === 'enviada'): ?>

        <p class="descripcion">
            La solicitud fue enviada al proveedor
            y está pendiente de recepción.
        </p>

    <?php elseif ($s['estado'] === 'cancelada'): ?>

        <p class="descripcion">
            Esta solicitud fue cancelada.
            No se permiten nuevas modificaciones.
        </p>

    <?php elseif ($s['estado'] === 'recibida'): ?>

        <p class="descripcion">
            Esta solicitud ha sido recibida.
        </p>

    <?php endif; ?>

</section>

<?php $this->load->view('plantilla/pie'); ?>