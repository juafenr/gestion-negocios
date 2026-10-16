<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Inventario', 'seccion' => 'inventario', 'usuario' => $usuario)); ?>

<p>
    <a class="boton boton-secundario" href="<?= html_escape(site_url('inventario')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/volver.svg')) ?>" alt="" width="22" height="22"><span>Volver al inventario</span></a>
</p>

<section class="recuadro formulario-inventario">

    <h1>
        <?= $insumo === NULL
            ? 'Nuevo insumo'
            : 'Editar insumo'
        ?>
    </h1>

    <?php if (validation_errors()): ?>

        <div class="error" role="alert">
            <?= validation_errors() ?>
        </div>

    <?php endif; ?>

    <?= form_open(
        $accion,
        array('class' => 'formulario')
    ) ?>

    <label for="nombre">
        Nombre
    </label>

    <input
        id="nombre"
        name="nombre"
        type="text"
        maxlength="150"
        required
        value="<?= html_escape(
                    set_value(
                        'nombre',
                        $insumo['nombre'] ?? ''
                    )
                ) ?>">

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
        value="<?= html_escape(
                    set_value(
                        'cantidad_actual',
                        $insumo['cantidad_actual'] ?? '0'
                    )
                ) ?>">

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
        value="<?= html_escape(
                    set_value(
                        'stock_minimo',
                        $insumo['stock_minimo'] ?? '0'
                    )
                ) ?>">

    <label for="unidad_medida">
        Unidad de medida
    </label>

    <?php
    $unidad_actual = set_value(
        'unidad_medida',
        $insumo['unidad_medida'] ?? 'unidad'
    );
    ?>

    <select
        id="unidad_medida"
        name="unidad_medida"
        required>

        <option
            value="unidad"
            <?= $unidad_actual === 'unidad'
                ? 'selected'
                : ''
            ?>>
            Unidad
        </option>

        <option
            value="kg"
            <?= $unidad_actual === 'kg'
                ? 'selected'
                : ''
            ?>>
            Kilogramos
        </option>

        <option
            value="g"
            <?= $unidad_actual === 'g'
                ? 'selected'
                : ''
            ?>>
            Gramos
        </option>

        <option
            value="mg"
            <?= $unidad_actual === 'mg'
                ? 'selected'
                : ''
            ?>>
            Miligramos
        </option>

        <option
            value="l"
            <?= $unidad_actual === 'l'
                ? 'selected'
                : ''
            ?>>
            Litros
        </option>

        <option
            value="ml"
            <?= $unidad_actual === 'ml'
                ? 'selected'
                : ''
            ?>>
            Mililitros
        </option>

    </select>

    <button type="submit"><img class="icono" src="<?= html_escape(base_url('assets/iconos/guardar.svg')) ?>" alt="" width="22" height="22"><span>Guardar insumo</span></button>

    <?= form_close() ?>

</section>

<?php $this->load->view('plantilla/pie'); ?>
