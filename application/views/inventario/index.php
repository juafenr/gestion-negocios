<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Inventario', 'seccion' => 'inventario', 'usuario' => $usuario)); ?>

<div class="titulo-acciones"><div><p class="sobretitulo">EXISTENCIAS</p><h1>Inventario</h1><p class="descripcion">Consulta tus insumos y revisa lo que necesita reposición.</p></div><?php if ($usuario->puede('inventario.gestionar')): ?>
    <p>
        <a class="boton" href="<?= html_escape(site_url('inventario/crear')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/agregar.svg')) ?>" alt="" width="22" height="22"><span>Nuevo insumo</span></a>
    </p>
<?php endif; ?></div>

<!-- Resumen general del inventario -->
<div class="resumen-inventario">

    <div class="resumen-tarjeta">
        <span class="resumen-etiqueta"><img class="icono" src="<?= html_escape(base_url('assets/iconos/inventario.svg')) ?>" alt="" width="22" height="22">Total de insumos</span>

        <strong>
            <?= (int) $resumen['total'] ?>
        </strong>
    </div>

    <div class="resumen-tarjeta">
        <span class="resumen-etiqueta"><img class="icono" src="<?= html_escape(base_url('assets/iconos/correcto.svg')) ?>" alt="" width="22" height="22">Stock normal</span>

        <strong class="resumen-normal">
            <?= (int) $resumen['normales'] ?>
        </strong>
    </div>

    <div class="resumen-tarjeta">
        <span class="resumen-etiqueta"><img class="icono" src="<?= html_escape(base_url('assets/iconos/alerta.svg')) ?>" alt="" width="22" height="22">Stock bajo</span>

        <strong class="resumen-bajo">
            <?= (int) $resumen['bajos'] ?>
        </strong>
    </div>

</div>

<!-- Búsqueda y filtros del inventario -->
<form action="<?= html_escape(site_url('inventario')) ?>" method="get" class="filtros-inventario">
    <input
        type="search"
        id="busqueda"
        name="busqueda"
        maxlength="150"
        placeholder="Buscar insumo..."
        aria-label="Buscar insumo"
        value="<?= html_escape($filtros['busqueda']) ?>">

    <select
        id="estado"
        name="estado"
        aria-label="Filtrar por estado">
        <option value="">Todos los estados</option>

        <option
            value="bajo"
            <?= $filtros['estado'] === 'bajo'
                ? 'selected' : '' ?>>
            Stock bajo
        </option>

        <option
            value="normal"
            <?= $filtros['estado'] === 'normal'
                ? 'selected' : '' ?>>
            Stock normal
        </option>
    </select>

    <select
        id="unidad"
        name="unidad"
        aria-label="Filtrar por unidad">
        <option value="">Todas las unidades</option>

        <?php
        $unidades = array(
            'unidad' => 'Unidad',
            'kg' => 'Kilogramos',
            'g' => 'Gramos',
            'mg' => 'Miligramos',
            'l' => 'Litros',
            'ml' => 'Mililitros'
        );
        ?>

        <?php foreach ($unidades as $valor => $nombre): ?>

            <option
                value="<?= html_escape($valor) ?>"
                <?= $filtros['unidad'] === $valor
                    ? 'selected' : '' ?>>
                <?= html_escape($nombre) ?>
            </option>

        <?php endforeach; ?>
    </select>

    <select
        id="orden"
        name="orden"
        aria-label="Ordenar inventario">
        <option
            value="nombre_asc"
            <?= $filtros['orden'] === 'nombre_asc'
                ? 'selected' : '' ?>>
            Nombre A-Z
        </option>

        <option
            value="nombre_desc"
            <?= $filtros['orden'] === 'nombre_desc'
                ? 'selected' : '' ?>>
            Nombre Z-A
        </option>

        <option
            value="cantidad_asc"
            <?= $filtros['orden'] === 'cantidad_asc'
                ? 'selected' : '' ?>>
            Menor cantidad
        </option>

        <option
            value="cantidad_desc"
            <?= $filtros['orden'] === 'cantidad_desc'
                ? 'selected' : '' ?>>
            Mayor cantidad
        </option>

        <option
            value="minimo_asc"
            <?= $filtros['orden'] === 'minimo_asc'
                ? 'selected' : '' ?>>
            Menor stock mínimo
        </option>

        <option
            value="minimo_desc"
            <?= $filtros['orden'] === 'minimo_desc'
                ? 'selected' : '' ?>>
            Mayor stock mínimo
        </option>

        <option
            value="stock_bajo"
            <?= $filtros['orden'] === 'stock_bajo'
                ? 'selected' : '' ?>>
            Stock bajo primero
        </option>
    </select>

    <button type="submit"><img class="icono" src="<?= html_escape(base_url('assets/iconos/buscar.svg')) ?>" alt="" width="22" height="22"><span>Filtrar</span></button>

    <a class="boton boton-secundario limpiar-filtros"
        href="<?= html_escape(site_url('inventario')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/limpiar.svg')) ?>" alt="" width="22" height="22"><span>Limpiar</span></a>

</form>



<div class="tabla" role="region" aria-label="Listado de insumos" tabindex="0">

    <table class="tabla-inventario">

        <thead>
            <tr>
                <th>Nombre</th>
                <th>Cantidad actual</th>
                <th>Stock mínimo</th>
                <th>Unidad</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($insumos as $item): ?>

                <tr>
                    <td>
                        <?= html_escape($item['nombre']) ?>
                    </td>

                    <td>
                        <?= html_escape($item['cantidad_actual']) ?>
                    </td>

                    <td>
                        <?= html_escape($item['stock_minimo']) ?>
                    </td>

                    <td>
                        <?= html_escape($item['unidad_medida']) ?>
                    </td>

                    <?php $cantidadActual = (float) $item['cantidad_actual'];
                    $stockMinimo = (float) $item['stock_minimo'];
                    $stockBajo = $cantidadActual <= $stockMinimo;
                    ?>

                    <td>
                        <?php if ($stockBajo): ?>

                            <span class="estado-stock estado-bajo">
                                Stock bajo
                            </span>

                        <?php else: ?>

                            <span class="estado-stock estado-normal">
                                Normal
                            </span>

                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($usuario->puede('inventario.gestionar')): ?>

                            <a class="boton boton-secundario boton-compacto" href="<?= html_escape(
                                            site_url(
                                                'inventario/' .
                                                    $item['id'] .
                                                    '/editar'
                                            )
                                        ) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/editar.svg')) ?>" alt="" width="22" height="22"><span>Editar</span></a>

                        <?php else: ?>

                            Solo consulta

                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>

            <?php if (empty($insumos)): ?>
                <tr>
                    <td colspan="6">
                        <?php if (
                            $filtros['busqueda'] !== '' ||
                            $filtros['estado'] !== '' ||
                            $filtros['unidad'] !== ''
                        ): ?>

                            No se encontraron insumos que coincidan
                            con los filtros seleccionados.

                        <?php else: ?>

                            Todavía no hay insumos registrados.

                        <?php endif; ?>
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php $this->load->view('plantilla/pie'); ?>
