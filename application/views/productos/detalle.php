<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Productos', 'seccion' => 'productos', 'usuario' => $usuario)); ?>

<p>
    <a class="boton boton-secundario" href="<?= html_escape(site_url('productos')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/volver.svg')) ?>" alt="" width="22" height="22"><span>Volver a productos</span></a>
</p>

<section class="recuadro">

    <h1>
        <?= html_escape($producto['nombre']) ?>
    </h1>

    <p>
        Código:
        <?= html_escape($producto['sku']) ?>
    </p>

    <p>
        Precio:
        Q <?= number_format(
                (float) $producto['precio'],
                2
            ) ?>
    </p>

</section>

<?php $this->load->view('plantilla/pie'); ?>
