<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Pedidos', 'seccion' => 'pedidos', 'usuario' => $usuario)); ?>

<p>
    <a class="boton boton-secundario" href="<?= html_escape(site_url('pedidos')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/volver.svg')) ?>" alt="" width="22" height="22"><span>Volver a pedidos</span></a>
</p>

<section class="recuadro formulario formulario-pedido">

    <h1>Nuevo pedido</h1><p class="descripcion">Indica una mesa, un cliente o una referencia. Después podrás agregar los productos.</p>

    <?php if (validation_errors()): ?>

        <div class="error" role="alert">
            <?= validation_errors() ?>
        </div>

    <?php endif; ?>

    <?= form_open('pedidos/crear') ?>

    <label for="referencia">
        Referencia del pedido
    </label>

    <input
        id="referencia"
        name="referencia"
        type="text"
        maxlength="100"
        required
        placeholder="Ej. Mesa 5, Cliente López, Orden 123"
        value="<?= html_escape(
                    set_value('referencia')
                ) ?>">

    <button type="submit"><img class="icono" src="<?= html_escape(base_url('assets/iconos/agregar.svg')) ?>" alt="" width="22" height="22"><span>Crear pedido</span></button>

    <?= form_close() ?>

</section>

<?php $this->load->view('plantilla/pie'); ?>
