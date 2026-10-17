<?php
$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Inventario',
    'seccion' => 'inventario',
    'usuario' => $usuario
));
?>

<div class="titulo-acciones">
    <div>
        <p class="sobretitulo">EXISTENCIAS</p>
        <h1>Inventario</h1>
        <p class="descripcion">Consulta tus insumos y revisa lo que necesita reposición.</p>
    </div>

    <?php if ($usuario->puede('inventario.gestionar')): ?>
        <p>
            <a class="boton" href="<?= html_escape(site_url('inventario/crear')) ?>">
                <img class="icono" src="<?= html_escape(base_url('assets/iconos/agregar.svg')) ?>" alt="" width="22" height="22">
                <span>Nuevo insumo</span>
            </a>
        </p>
    <?php endif; ?>
</div>

<?php
$mensaje = $this->session->flashdata('mensaje_inventario');
?>

<?php if ($mensaje): ?>
    <p role="status"><?= html_escape($mensaje) ?></p>
<?php endif; ?>


<!-- Resumen general del inventario -->

<div class="resumen-inventario">

    <div class="resumen-tarjeta">
        <span class="resumen-etiqueta">
            <img class="icono" src="<?= html_escape(base_url('assets/iconos/inventario.svg')) ?>" alt="" width="22" height="22">
            Total de insumos
        </span>

        <strong>
            <?= (int) $resumen['total'] ?>
        </strong>
    </div>

    <div class="resumen-tarjeta">
        <span class="resumen-etiqueta">
            <img class="icono" src="<?= html_escape(base_url('assets/iconos/correcto.svg')) ?>" alt="" width="22" height="22">
            Stock normal
        </span>

        <strong class="resumen-normal">
            <?= (int) $resumen['normales'] ?>
        </strong>
    </div>

    <div class="resumen-tarjeta">
        <span class="resumen-etiqueta">
            <img class="icono" src="<?= html_escape(base_url('assets/iconos/alerta.svg')) ?>" alt="" width="22" height="22">
            Stock bajo
        </span>

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
            <?= $filtros['estado'] === 'bajo' ? 'selected' : '' ?>>
            Stock bajo
        </option>

        <option
            value="normal"
            <?= $filtros['estado'] === 'normal' ? 'selected' : '' ?>>
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
                <?= $filtros['unidad'] === $valor ? 'selected' : '' ?>>
                <?= html_escape($nombre) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select
        id="orden"
        name="orden"
        aria-label="Ordenar inventario">

        <option value="nombre_asc" <?= $filtros['orden'] === 'nombre_asc' ? 'selected' : '' ?>>
            Nombre A-Z
        </option>

        <option value="nombre_desc" <?= $filtros['orden'] === 'nombre_desc' ? 'selected' : '' ?>>
            Nombre Z-A
        </option>

        <option value="cantidad_asc" <?= $filtros['orden'] === 'cantidad_asc' ? 'selected' : '' ?>>
            Menor cantidad
        </option>

        <option value="cantidad_desc" <?= $filtros['orden'] === 'cantidad_desc' ? 'selected' : '' ?>>
            Mayor cantidad
        </option>

        <option value="minimo_asc" <?= $filtros['orden'] === 'minimo_asc' ? 'selected' : '' ?>>
            Menor stock mínimo
        </option>

        <option value="minimo_desc" <?= $filtros['orden'] === 'minimo_desc' ? 'selected' : '' ?>>
            Mayor stock mínimo
        </option>

        <option value="stock_bajo" <?= $filtros['orden'] === 'stock_bajo' ? 'selected' : '' ?>>
            Stock bajo primero
        </option>
    </select>

    <select
        name="activo"
        aria-label="Estado del insumo">

        <option
            value="activos"
            <?= ($filtros['activo'] ?? 'activos') === 'activos' ? 'selected' : '' ?>>
            Insumos activos
        </option>

        <option
            value="inactivos"
            <?= ($filtros['activo'] ?? 'activos') === 'inactivos' ? 'selected' : '' ?>>
            Insumos inactivos
        </option>
    </select>

    <button type="submit">
        <img class="icono" src="<?= html_escape(base_url('assets/iconos/buscar.svg')) ?>" alt="" width="22" height="22">
        <span>Filtrar</span>
    </button>

    <a class="boton boton-secundario limpiar-filtros"
        href="<?= html_escape(site_url('inventario')) ?>">
        <img class="icono" src="<?= html_escape(base_url('assets/iconos/limpiar.svg')) ?>" alt="" width="22" height="22">
        <span>Limpiar</span>
    </a>

    <a class="boton boton-secundario limpiar-filtros"
        href="<?= html_escape(site_url('inventario/exportar')) ?>">
        <img class="icono" src="<?= html_escape(base_url('assets/iconos/inventario.svg')) ?>" alt="" width="22" height="22">
        <span>Exportar CSV</span>
    </a>

</form>


<!-- Tabla de inventario -->

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
                <th>Vencimiento</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($insumos as $item): ?>

                <?php
                $cantidadActual = (float) $item['cantidad_actual'];
                $stockMinimo = (float) $item['stock_minimo'];
                $stockBajo = $cantidadActual <= $stockMinimo;

                $idInsumo = (int) $item['id'];
                $activoInsumo = (int) $item['activo'] === 1;
                $puedeGestionar = $usuario->puede('inventario.gestionar');
                ?>

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

                    <!-- Estado del insumo -->

                    <td>

                        <?php if (!$activoInsumo): ?>

                            <span class="estado-stock">
                                Inactivo
                            </span>

                        <?php elseif ($stockBajo): ?>

                            <span class="estado-stock estado-bajo">
                                Stock bajo
                            </span>

                        <?php else: ?>

                            <span class="estado-stock estado-normal">
                                Normal
                            </span>

                        <?php endif; ?>

                    </td>


                    <!-- Acciones -->

                    <td class="acciones-inventario-fila">
                        <div class="acciones-insumo" role="group" aria-label="Acciones de <?= html_escape($item['nombre']) ?>">
                        <?php if (
                            $activoInsumo
                            && $usuario->puede('inventario.ver')
                            && $usuario->puede('productos.gestionar')
                        ): ?>

                            <a
                                class="boton boton-secundario boton-compacto accion-venta"
                                href="<?= html_escape(
                                    site_url('inventario/vender/' . $idInsumo)
                                ) ?>">
                                <img class="icono" src="<?= html_escape(base_url('assets/iconos/productos.svg')) ?>" alt="" width="22" height="22">
                                <span>Habilitar para venta</span>
                            </a>

                        <?php endif; ?>

                        <?php if ($puedeGestionar && $activoInsumo): ?>

                            <a class="boton boton-secundario boton-compacto"
                                href="<?= html_escape(site_url('inventario/' . $idInsumo . '/editar')) ?>">
                                <img class="icono" src="<?= html_escape(base_url('assets/iconos/editar.svg')) ?>" alt="" width="22" height="22">
                                <span>Editar</span>
                            </a>

                            <a class="boton boton-secundario boton-compacto"
                                href="<?= html_escape(site_url('inventario/' . $idInsumo . '/movimiento')) ?>">
                                <img class="icono" src="<?= html_escape(base_url('assets/iconos/movimiento.svg')) ?>" alt="" width="22" height="22">
                                <span>Movimiento</span>
                            </a>

                        <?php endif; ?>

                        <a class="boton boton-secundario boton-compacto"
                            href="<?= html_escape(site_url('inventario/' . $idInsumo . '/historial')) ?>">
                            <img class="icono" src="<?= html_escape(base_url('assets/iconos/historial.svg')) ?>" alt="" width="22" height="22">
                                <span>Historial</span>
                        </a>

                        <?php if ($puedeGestionar): ?>

                            <?= form_open(
                                'inventario/' . $idInsumo . '/estado',
                                array('class' => 'form-estado-insumo')
                            ) ?>

                            <button type="submit" class="boton boton-secundario boton-compacto">
                                <img class="icono" src="<?= html_escape(base_url('assets/iconos/activar.svg')) ?>" alt="" width="22" height="22">
                                <span><?= $activoInsumo ? 'Desactivar' : 'Reactivar' ?></span>
                            </button>

                            <?= form_close() ?>

                        <?php endif; ?>

                        </div>
                    </td>


                    <!-- Fecha de vencimiento -->

                    <td>

                        <?php if (!empty($item['fecha_vencimiento'])): ?>

                            <?= html_escape($item['fecha_vencimiento']) ?>

                            <?php if ($item['fecha_vencimiento'] < date('Y-m-d')): ?>

                                <span class="estado-stock estado-bajo">
                                    Vencido
                                </span>

                            <?php endif; ?>

                        <?php else: ?>

                            Sin fecha

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endforeach; ?>


            <?php if (empty($insumos)): ?>

                <tr>
                    <td colspan="7">

                        <?php if (
                            $filtros['busqueda'] !== '' ||
                            $filtros['estado'] !== '' ||
                            $filtros['unidad'] !== ''
                        ): ?>

                            No se encontraron insumos que coincidan
                            con los filtros seleccionados.

                        <?php elseif (($filtros['activo'] ?? 'activos') === 'inactivos'): ?>

                            No hay insumos inactivos registrados.

                        <?php else: ?>

                            Todavía no hay insumos registrados.

                        <?php endif; ?>

                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>


<!-- Paginación -->

<?php if (isset($paginas, $pagina) && $paginas > 1): ?>

    <?php
    $parametros = array(
        'busqueda' => $filtros['busqueda'] ?? '',
        'estado' => $filtros['estado'] ?? '',
        'unidad' => $filtros['unidad'] ?? '',
        'orden' => $filtros['orden'] ?? 'nombre_asc',
        'activo' => $filtros['activo'] ?? 'activos'
    );
    ?>

    <nav class="paginacion-inventario" aria-label="Paginación del inventario">

        <?php if ($pagina > 1): ?>

            <?php
            $anterior = $parametros;
            $anterior['pagina'] = $pagina - 1;
            ?>

            <a class="boton boton-secundario boton-compacto"
                href="<?= html_escape(site_url('inventario') . '?' . http_build_query($anterior)) ?>">
                Anterior
            </a>

        <?php endif; ?>


        <?php for ($i = 1; $i <= $paginas; $i++): ?>

            <?php
            $parametros['pagina'] = $i;
            ?>

            <a class="boton boton-secundario boton-compacto"
                href="<?= html_escape(site_url('inventario') . '?' . http_build_query($parametros)) ?>"
                <?= $i === $pagina ? 'aria-current="page"' : '' ?>>
                <?= $i ?>
            </a>

        <?php endfor; ?>


        <?php if ($pagina < $paginas): ?>

            <?php
            $siguiente = $parametros;
            $siguiente['pagina'] = $pagina + 1;
            ?>

            <a class="boton boton-secundario boton-compacto"
                href="<?= html_escape(site_url('inventario') . '?' . http_build_query($siguiente)) ?>">
                Siguiente
            </a>

        <?php endif; ?>

    </nav>

<?php endif; ?>


<?php $this->load->view('plantilla/pie'); ?>