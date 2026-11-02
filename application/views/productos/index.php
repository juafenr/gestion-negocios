<?php
$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Productos',
    'seccion' => 'productos',
    'usuario' => $usuario
));

$puede_gestionar = $usuario->puede('productos.gestionar');
$hay_filtros = $busqueda !== '' || $disponibilidad !== '';
?>

<div class="titulo-acciones">
    <div>
        <p class="sobretitulo">CATÁLOGO</p>
        <h1>Productos</h1>

        <p class="descripcion">
            Consulta los productos y platillos de tu empresa,
            sus precios y disponibilidad.
        </p>
    </div>

    <?php if ($puede_gestionar): ?>
        <p>
            <a
                class="boton"
                href="<?= html_escape(site_url('productos/crear')) ?>">
                <img
                    class="icono"
                    src="<?= html_escape(
                        base_url('assets/iconos/agregar.svg')
                    ) ?>"
                    alt=""
                    width="22"
                    height="22">
                <span>Nuevo producto</span>
            </a>
        </p>
    <?php endif; ?>
</div>

<?php if (!empty($mensaje)): ?>
    <p role="status">
        <?= html_escape($mensaje) ?>
    </p>
<?php endif; ?>

<section class="recuadro">
    <h2>Buscar productos</h2>

    <form
        action="<?= html_escape(site_url('productos')) ?>"
        method="get"
        class="formulario">

        <label for="busqueda">
            Nombre, código o descripción
        </label>

        <input
            type="search"
            id="busqueda"
            name="busqueda"
            maxlength="150"
            placeholder="Escribe el producto que buscas"
            value="<?= html_escape($busqueda) ?>">

        <label for="disponibilidad">Disponibilidad del producto</label>

        <select id="disponibilidad" name="disponibilidad">
            <option
                value=""
                <?= $disponibilidad === '' ? 'selected' : '' ?>>
                Todos
            </option>

            <option
                value="1"
                <?= $disponibilidad === '1' ? 'selected' : '' ?>>
                Disponibles
            </option>

            <option
                value="0"
                <?= $disponibilidad === '0' ? 'selected' : '' ?>>
                No disponibles
            </option>
        </select>

        <button type="submit">
            <img
                class="icono"
                src="<?= html_escape(
                    base_url('assets/iconos/buscar.svg')
                ) ?>"
                alt=""
                width="22"
                height="22">
            <span>Filtrar</span>
        </button>

        <a
            class="boton boton-secundario"
            href="<?= html_escape(site_url('productos')) ?>">
            Limpiar filtros
        </a>
    </form>
</section>

<p role="status">
    <strong><?= count($productos) ?></strong>
    <?= count($productos) === 1
        ? 'producto encontrado.'
        : 'productos encontrados.' ?>
</p>

<div
    class="tabla"
    role="region"
    aria-label="Catálogo de productos"
    tabindex="0">

    <table>
        <thead>
            <tr>
                <th scope="col">Producto</th>
                <th scope="col">Código</th>
                <th scope="col">Descripción</th>
                <th scope="col">Precio</th>
                <th scope="col">Disponibilidad</th>
                <th scope="col">Acciones</th>
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
                        <?php if ($producto['descripcion'] !== ''): ?>
                            <?= nl2br(
                                html_escape($producto['descripcion'])
                            ) ?>
                        <?php else: ?>
                            Sin descripción
                        <?php endif; ?>
                    </td>

                    <td>
                        Q <?= number_format(
                            (float) $producto['precio'],
                            2
                        ) ?>
                    </td>

                    <td>
                        <?= (int) $producto['disponible'] === 1
                            ? 'Disponible'
                            : 'No disponible' ?>
                    </td>

                    <td>
                        <a
                            class="boton boton-secundario boton-compacto"
                            href="<?= html_escape(
                                site_url(
                                    'productos/' . (int) $producto['id']
                                )
                            ) ?>">
                            <img
                                class="icono"
                                src="<?= html_escape(
                                    base_url('assets/iconos/ver.svg')
                                ) ?>"
                                alt=""
                                width="22"
                                height="22">
                            <span>Ver detalle</span>
                        </a>

                        <?php if ($puede_gestionar): ?>
                            <a
                                class="boton boton-secundario boton-compacto"
                                href="<?= html_escape(
                                    site_url(
                                        'productos/editar/'
                                        . (int) $producto['id']
                                    )
                                ) ?>">
                                <img
                                    class="icono"
                                    src="<?= html_escape(
                                        base_url('assets/iconos/editar.svg')
                                    ) ?>"
                                    alt=""
                                    width="22"
                                    height="22">
                                <span>Editar</span>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($productos)): ?>
                <tr>
                    <td colspan="6">
                        <?= $hay_filtros
                            ? 'No se encontraron productos con esos filtros.'
                            : 'Todavía no hay productos registrados.' ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->load->view('plantilla/pie'); ?>