<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Productos', 'seccion' => 'productos', 'usuario' => $usuario)); ?>

<p class="sobretitulo">CATÁLOGO</p><h1>Productos</h1>

<p class="descripcion">Consulta los productos disponibles en el catálogo de tu empresa y sus precios.</p>

<form method="get" action="<?= html_escape(site_url('productos')) ?>" class="recuadro filtros-pagos" role="search">
    <label for="busqueda-productos">Nombre o código</label>
    <input id="busqueda-productos" name="busqueda" type="search" maxlength="150" value="<?= html_escape($busqueda) ?>">
    <button type="submit">Buscar</button>
    <?php if ($busqueda !== ''): ?><a class="boton boton-secundario" href="<?= html_escape(site_url('productos')) ?>">Limpiar búsqueda</a><?php endif; ?>
</form>
<p role="status"><?= (int) $total ?> <?= $total === 1 ? 'producto encontrado' : 'productos encontrados' ?><?= $busqueda !== '' ? ' para «'.html_escape($busqueda).'»' : '' ?>.</p>

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
                        <?= $busqueda !== '' ? 'No hay productos que coincidan con la búsqueda.' : 'Todavía no hay productos registrados.' ?>
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<nav class="paginacion-pagos" aria-label="Paginación de productos">
    <?php if ($pagina > 1): ?>
        <a class="boton boton-secundario" rel="prev" href="<?= html_escape(site_url('productos').'?'.http_build_query(array('busqueda'=>$busqueda, 'pagina'=>$pagina-1))) ?>">Anterior</a>
    <?php endif; ?>
    <span>Página <?= (int) $pagina ?> de <?= (int) $paginas ?></span>
    <?php if ($pagina < $paginas): ?>
        <a class="boton boton-secundario" rel="next" href="<?= html_escape(site_url('productos').'?'.http_build_query(array('busqueda'=>$busqueda, 'pagina'=>$pagina+1))) ?>">Siguiente</a>
    <?php endif; ?>
</nav>
<?php $this->load->view('plantilla/pie'); ?>
