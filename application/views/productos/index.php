<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Productos', 'seccion' => 'productos', 'usuario' => $usuario)); ?>

<p class="sobretitulo">CATÁLOGO</p><h1>Productos</h1>

<p class="descripcion">Consulta los productos disponibles en el catálogo de tu empresa y sus precios.</p>

<div class="tabla">

    <table>

        <thead>
            <tr>
                <th>Producto</th>
                <th>Código</th>
                <th>Precio</th>
                <th>Detalle</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($productos as $producto): ?>

                <tr>
                    <td>
                        <?= html_escape($producto['nombre']) ?>
                    </td>

                    <td>
                        <?= html_escape($producto['sku']) ?>
                    </td>

                    <td>
                        Q <?= number_format(
                                (float) $producto['precio'],
                                2
                            ) ?>
                    </td>

                    <td>
                        <a class="boton boton-secundario boton-compacto" href="<?= html_escape(
                                        site_url(
                                            'productos/' .
                                                $producto['id']
                                        )
                                    ) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/ver.svg')) ?>" alt="" width="22" height="22"><span>Ver detalle</span></a>
                    </td>
                </tr>

            <?php endforeach; ?>

            <?php if (empty($productos)): ?>

                <tr>
                    <td colspan="4">
                        Todavía no hay productos registrados.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php $this->load->view('plantilla/pie'); ?>
