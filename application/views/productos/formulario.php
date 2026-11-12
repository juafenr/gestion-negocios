<?php
$this->load->view('plantilla/cabecera', array(
    'titulo' => $titulo,
    'seccion' => 'productos',
    'usuario' => $usuario
));
?>

<p>
    <a
        class="boton boton-secundario"
        href="<?= html_escape(site_url('productos')) ?>">
        <img
            class="icono"
            src="<?= html_escape(base_url('assets/iconos/volver.svg')) ?>"
            alt=""
            width="22"
            height="22">
        <span>Volver a productos</span>
    </a>
</p>

<section class="recuadro formulario-inventario">

    <h1><?= html_escape($titulo) ?></h1>

    <?php if (!empty($error)): ?>
        <div class="error" role="alert">
            <?= html_escape($error) ?>
        </div>
    <?php endif; ?>

    <?= form_open($accion, array('class' => 'formulario')) ?>

    <label for="nombre">Nombre del producto o platillo</label>

    <input
        type="text"
        id="nombre"
        name="nombre"
        maxlength="150"
        required
        value="<?= html_escape($producto['nombre'] ?? '') ?>">

    <label for="sku">Código</label>

    <input
        type="text"
        id="sku"
        name="sku"
        maxlength="40"
        required
        aria-describedby="ayuda-codigo"
        value="<?= html_escape($producto['sku'] ?? '') ?>">

    <p id="ayuda-codigo">
        El código debe ser único dentro de tu empresa.
    </p>

    <label for="descripcion">Descripción (opcional)</label>

    <textarea
        id="descripcion"
        name="descripcion"
        rows="4"
        maxlength="1000"
    ><?= html_escape($producto['descripcion'] ?? '') ?></textarea>

    <label for="precio">Precio de venta en quetzales</label>

    <input
        type="number"
        id="precio"
        name="precio"
        min="0"
        max="99999999.99"
        step="0.01"
        required
        value="<?= html_escape($producto['precio'] ?? '') ?>">

    <?php if (!empty($producto['id'])): ?>
        <p>
            Si modificas el precio, se registrarán el valor anterior,
            el nuevo, la fecha y tu usuario.
            Los precios guardados en pedidos anteriores se conservan.
        </p>
    <?php endif; ?>

    <label for="disponible">Disponibilidad</label>

    <?php
    $disponibilidad_actual = (string) ($producto['disponible'] ?? '');
    ?>

    <select id="disponible" name="disponible" required>
        <option
            value=""
            <?= $disponibilidad_actual === '' ? 'selected' : '' ?>>
            Selecciona una opción
        </option>

        <option
            value="1"
            <?= $disponibilidad_actual === '1' ? 'selected' : '' ?>>
            Disponible
        </option>

        <option
            value="0"
            <?= $disponibilidad_actual === '0' ? 'selected' : '' ?>>
            No disponible
        </option>
    </select>

    <button type="submit">
        <img
            class="icono"
            src="<?= html_escape(base_url('assets/iconos/guardar.svg')) ?>"
            alt=""
            width="22"
            height="22">
        <span>Guardar producto</span>
    </button>

    <?= form_close() ?>

</section>

<?php $this->load->view('plantilla/pie'); ?>