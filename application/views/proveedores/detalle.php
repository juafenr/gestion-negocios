<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Detalle del proveedor',
    'seccion' => 'proveedores',
    'usuario' => $usuario
));

$puedeGestionar = $usuario->puede('proveedores.gestionar');
?>

<div class="titulo-acciones">
    <div>
        <p class="sobretitulo">PROVEEDORES</p>
        <h1><?= html_escape($proveedor['nombre']) ?></h1>
        <p class="descripcion">
            Consulta los datos y los insumos asociados al proveedor.
        </p>
    </div>

    <div class="acciones-cabecera">
    <a class="boton boton-secundario"
        href="<?= html_escape(site_url('proveedores')) ?>">
        Volver
    </a>

    <a class="boton boton-secundario"
        href="<?= html_escape(
                    site_url(
                        'compras/proveedor/' .
                            (int) $proveedor['id'] .
                            '/historial'
                    )
                ) ?>">
        Historial de compras
    </a>
    </div>
</div>

<?php if ($mensaje): ?>
    <p role="status"><?= html_escape($mensaje) ?></p>
<?php endif; ?>

<section class="recuadro">
    <h2>Información del proveedor</h2>

    <p>
        <strong>Contacto:</strong>
        <?= html_escape($proveedor['contacto']) ?>
    </p>

    <p>
        <strong>Teléfono:</strong>
        <?= html_escape($proveedor['telefono']) ?>
    </p>

    <p>
        <strong>Correo:</strong>
        <?= html_escape($proveedor['correo']) ?>
    </p>

    <p>
        <strong>Dirección:</strong>
        <?= html_escape($proveedor['direccion']) ?>
    </p>

    <p>
        <strong>Estado:</strong>
        <?= (int) $proveedor['activo'] === 1
            ? 'Activo' : 'Inactivo' ?>
    </p>
</section>

<section class="recuadro">
    <h2>Insumos asociados</h2>

    <div class="tabla"
        role="region"
        aria-label="Insumos asociados al proveedor"
        tabindex="0">

        <table>
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th>Unidad</th>
                    <th>Existencia actual</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($insumos as $insumo): ?>
                    <tr>
                        <td><?= html_escape($insumo['nombre']) ?></td>
                        <td><?= html_escape($insumo['unidad_medida']) ?></td>
                        <td><?= html_escape($insumo['cantidad_actual']) ?></td>

                        <td>
                            <?php if ($puedeGestionar): ?>

                                <?= form_open(
                                    'proveedores/' .
                                        (int) $proveedor['id'] .
                                        '/desasociar'
                                ) ?>

                                <input
                                    type="hidden"
                                    name="insumo_id"
                                    value="<?= (int) $insumo['id'] ?>">

                                <button
                                    type="submit"
                                    class="boton boton-secundario boton-compacto">
                                    Desasociar
                                </button>

                                <?= form_close() ?>

                            <?php else: ?>
                                Solo consulta
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($insumos)): ?>
                    <tr>
                        <td colspan="4">
                            Este proveedor todavía no tiene insumos asociados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (
    $puedeGestionar &&
    (int) $proveedor['activo'] === 1
): ?>

    <section class="recuadro">
        <h2>Asociar insumo</h2>

        <?= form_open(
            'proveedores/' .
                (int) $proveedor['id'] .
                '/asociar',
            array('class' => 'formulario')
        ) ?>

        <label for="insumo_id">
            Seleccionar insumo sin proveedor
        </label>

        <select
            id="insumo_id"
            name="insumo_id"
            required>
            <option value="">Seleccionar insumo...</option>

            <?php foreach ($disponibles as $insumo): ?>
                <option value="<?= (int) $insumo['id'] ?>">
                    <?= html_escape(
                        $insumo['nombre'] .
                            ' (' . $insumo['unidad_medida'] . ')'
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button
            type="submit"
            <?= empty($disponibles) ? 'disabled' : '' ?>>
            Asociar insumo
        </button>

        <?= form_close() ?>

        <?php if (empty($disponibles)): ?>
            <p class="descripcion">
                No hay insumos activos sin proveedor.
            </p>
        <?php endif; ?>
    </section>

<?php endif; ?>

<?php $this->load->view('plantilla/pie'); ?>
