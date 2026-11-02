<?php
$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Habilitar artículo para venta',
    'seccion' => 'inventario',
    'usuario' => $usuario
));
?>

<p>
    <a
        class="boton boton-secundario"
        href="<?= html_escape(site_url('inventario')) ?>">
        <img
            class="icono"
            src="<?= html_escape(base_url('assets/iconos/volver.svg')) ?>"
            alt=""
            width="22"
            height="22">
        <span>Volver al inventario</span>
    </a>
</p>

<section class="recuadro formulario-inventario">

    <h1>Habilitar artículo para venta</h1>

    <p>
        <strong>Artículo:</strong>
        <?= html_escape($insumo['nombre']) ?>
    </p>

    <p>
        <strong>Existencia actual:</strong>
        <?= html_escape($insumo['cantidad_actual']) ?>
        <?= html_escape($insumo['unidad_medida']) ?>
    </p>

    <p>
        Indica un código y un precio de venta para que este
        artículo esté disponible al agregar productos a un pedido.
    </p>

    <?php if (!empty($error)): ?>
        <div class="error" role="alert">
            <?= html_escape($error) ?>
        </div>
    <?php endif; ?>

    <?= form_open(
        'inventario/vender/' . (int) $insumo['id'],
        array('class' => 'formulario')
    ) ?>

    <label for="sku">Código del producto</label>

    <input
        type="text"
        id="sku"
        name="sku"
        maxlength="40"
        placeholder="Ejemplo: ART-001"
        required
        value="<?= html_escape($sku) ?>">

    <label for="precio">
        Precio de venta en Q por
        <?= html_escape($insumo['unidad_medida']) ?>
    </label>

    <input
        type="number"
        id="precio"
        name="precio"
        min="0"
        max="99999999.99"
        step="0.01"
        placeholder="Ejemplo: 18.50"
        required
        value="<?= html_escape($precio) ?>">

    <p>
        Actualmente, los pedidos admiten cantidades enteras.
        Por ejemplo, si la unidad es kg, una cantidad de 2
        representa 2 kg.
    </p>

    <button type="submit">
        <img
            class="icono"
            src="<?= html_escape(base_url('assets/iconos/guardar.svg')) ?>"
            alt=""
            width="22"
            height="22">
        <span>Habilitar para venta</span>
    </button>

    <?= form_close() ?>

</section>

<?php $this->load->view('plantilla/pie'); ?>