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

    <div class="acciones-cabecera">
    <a
        class="boton boton-secundario"
        href="<?= html_escape(
                    site_url('inventario/sugerencias')
                ) ?>">
        <img
            class="icono"
            src="<?= html_escape(
                        base_url('assets/iconos/alerta.svg')
                    ) ?>"
            alt=""
            width="22"
            height="22">
        <span>Sugerencias de compra</span>
    </a>
    <?php if ($usuario->puede('inventario.gestionar')): ?>
        <a class="boton" href="<?= html_escape(site_url('inventario/crear')) ?>">
            <img class="icono" src="<?= html_escape(base_url('assets/iconos/agregar.svg')) ?>" alt="" width="22" height="22">
            <span>Nuevo insumo</span>
        </a>
    <?php endif; ?>
    </div>
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

    <div class="resumen-tarjeta">

        <span class="resumen-etiqueta">
            <img
                class="icono"
                src="<?= html_escape(base_url('assets/iconos/alerta.svg')) ?>"
                alt=""
                width="22"
                height="22">
            Agotados
        </span>

        <strong class="resumen-agotado">
            <?= (int) $resumen['agotados'] ?>
        </strong>

    </div>

</div>

<?php if ($usuario->puede('compras.gestionar') && $usuario->puede('compras.ver')): ?>
    <p><a class="boton boton-secundario" href="<?= html_escape(site_url('compras/seleccionar')) ?>">Preparar solicitud de compra</a></p>
<?php endif; ?>

<!-- Búsqueda y filtros del inventario -->

<?php $this->load->view('plantilla/filtros', array('filtro_ruta'=>'inventario', 'filtro_campos'=>array(
'busqueda'=>array('tipo'=>'search','etiqueta'=>'Nombre del insumo','ejemplo'=>'Ej. Harina'),
'estado'=>array('etiqueta'=>'Existencias','opciones'=>array(''=>'Todos los estados','normal'=>'Stock normal','bajo'=>'Stock bajo','agotado'=>'Agotado')),
'unidad'=>array('etiqueta'=>'Unidad de medida','opciones'=>array(''=>'Todas las unidades','unidad'=>'Unidad','kg'=>'Kilogramos','g'=>'Gramos','mg'=>'Miligramos','l'=>'Litros','ml'=>'Mililitros')),
'orden'=>array('etiqueta'=>'Ordenar por','defecto'=>'nombre_asc','opciones'=>array('nombre_asc'=>'Nombre A-Z','nombre_desc'=>'Nombre Z-A','cantidad_asc'=>'Menor cantidad','cantidad_desc'=>'Mayor cantidad','minimo_asc'=>'Menor stock mínimo','minimo_desc'=>'Mayor stock mínimo','stock_bajo'=>'Stock bajo primero')),
'activo'=>array('etiqueta'=>'Disponibilidad','defecto'=>'activos','opciones'=>array('activos'=>'Insumos activos','inactivos'=>'Insumos inactivos'))
), 'filtro_valores'=>$filtros, 'filtro_extras'=>array(array('ruta'=>'inventario/exportar','etiqueta'=>'Exportar activos en CSV','ayuda'=>'Descarga todos los insumos activos, sin aplicar estos filtros.')))); ?>


<!-- Tabla de inventario -->

<?php $this->load->view('plantilla/resultados', array('resultado_total'=>$total, 'resultado_mostrados'=>count($insumos), 'resultado_pagina'=>$pagina)); ?>
<div class="tabla" role="region" aria-label="Listado de insumos" tabindex="0">

    <table class="tabla-inventario">

        <thead>
            <tr>
                <th>Nombre</th>
                <th class="tabla-numero">Cantidad actual</th>
                <th class="tabla-numero">Stock mínimo</th>
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
                $agotado = $cantidadActual <= 0;
                $stockBajo = !$agotado && $cantidadActual <= $stockMinimo;

                $idInsumo = (int) $item['id'];
                $activoInsumo = (int) $item['activo'] === 1;
                $puedeGestionar = $usuario->puede('inventario.gestionar');
                ?>

                <tr>

                    <td>
                        <?php if (!empty($item['imagen'])): ?><img class="imagen-miniatura" src="<?= html_escape(site_url('inventario/'.$idInsumo.'/imagen')) ?>" alt="" width="48" height="48"><?php else: ?><span class="imagen-miniatura imagen-miniatura-vacia" aria-hidden="true">—</span><?php endif; ?>
                        <?= html_escape($item['nombre']) ?>
                    </td>

                    <td class="tabla-numero">
                        <?= html_escape($item['cantidad_actual']) ?>
                    </td>

                    <td class="tabla-numero">
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

                        <?php elseif ($agotado): ?>

                            <span class="estado-stock estado-agotado">
                                Agotado
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


                    <!-- Acciones -->

                    <td class="acciones-inventario-fila"><details class="acciones-desplegables"><summary aria-label="Acciones de <?= html_escape($item['nombre']) ?>">Acciones <span aria-hidden="true">⌄</span></summary>

                        <div
                            class="acciones-insumo"
                            role="group"
                            aria-label="Acciones de <?= html_escape($item['nombre']) ?>">

                            <!-- Habilitar para venta -->

                            <?php if (
                                $activoInsumo &&
                                $usuario->puede('inventario.ver') &&
                                $usuario->puede('productos.gestionar')
                            ): ?>

                                <a
                                    class="boton boton-secundario boton-compacto accion-venta"
                                    href="<?= html_escape(
                                                site_url('inventario/vender/' . $idInsumo)
                                            ) ?>">
                                    <img
                                        class="icono"
                                        src="<?= html_escape(
                                                    base_url('assets/iconos/productos.svg')
                                                ) ?>"
                                        alt=""
                                        width="22"
                                        height="22">
                                    <span>Habilitar para venta</span>
                                </a>

                            <?php endif; ?>


                            <!-- Editar y registrar movimiento -->

                            <?php if ($puedeGestionar && $activoInsumo): ?>

                                <a
                                    class="boton boton-secundario boton-compacto"
                                    href="<?= html_escape(
                                                site_url('inventario/' . $idInsumo . '/editar')
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

                                <a
                                    class="boton boton-secundario boton-compacto"
                                    href="<?= html_escape(
                                                site_url('inventario/' . $idInsumo . '/movimiento')
                                            ) ?>">
                                    <img
                                        class="icono"
                                        src="<?= html_escape(
                                                    base_url('assets/iconos/movimiento.svg')
                                                ) ?>"
                                        alt=""
                                        width="22"
                                        height="22">
                                    <span>Movimiento</span>
                                </a>

                            <?php endif; ?>

                            <?php if ($activoInsumo && $usuario->puede('compras.gestionar') && $usuario->puede('compras.ver')): ?>
                                <?= form_open('compras/agregar', array('class' => 'form-compra-insumo')) ?>
                                    <input type="hidden" name="insumo_id" value="<?= $idInsumo ?>">
                                    <label for="compra-cantidad-<?= $idInsumo ?>">Cantidad a comprar de <?= html_escape($item['nombre']) ?></label>
                                    <input id="compra-cantidad-<?= $idInsumo ?>" name="cantidad" type="number" min="0.01" max="99999999.99" step="0.01" inputmode="decimal" required>
                                    <button type="submit" class="boton boton-secundario boton-compacto">Agregar a compra</button>
                                <?= form_close() ?>
                            <?php endif; ?>


                            <!-- Historial -->

                            <a
                                class="boton boton-secundario boton-compacto"
                                href="<?= html_escape(
                                            site_url('inventario/' . $idInsumo . '/historial')
                                        ) ?>">
                                <img
                                    class="icono"
                                    src="<?= html_escape(
                                                base_url('assets/iconos/historial.svg')
                                            ) ?>"
                                    alt=""
                                    width="22"
                                    height="22">
                                <span>Historial</span>
                            </a>


                            <!-- Activar o desactivar -->

                            <?php if ($puedeGestionar): ?>

                                <?= form_open(
                                    'inventario/' . $idInsumo . '/estado',
                                    array('class' => 'form-estado-insumo')
                                ) ?>

                                <button
                                    type="submit"
                                    class="boton boton-secundario boton-compacto">
                                    <img
                                        class="icono"
                                        src="<?= html_escape(
                                                    base_url('assets/iconos/activar.svg')
                                                ) ?>"
                                        alt=""
                                        width="22"
                                        height="22">

                                    <span>
                                        <?= $activoInsumo
                                            ? 'Desactivar'
                                            : 'Reactivar' ?>
                                    </span>
                                </button>

                                <?= form_close() ?>

                            <?php endif; ?>

                        </div>

                    </details></td>



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
                    <td colspan="7"><?php $this->load->view('plantilla/vacio',array('vacio_titulo'=>'No se encontraron insumos para mostrar.','vacio_descripcion'=>'Prueba otra búsqueda o revisa los filtros de existencias y disponibilidad.','vacio_url'=>site_url('inventario'),'vacio_accion'=>'Limpiar filtros')); ?></td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>


<!-- Paginación -->

<?php $this->load->view('plantilla/paginacion', array('ruta_paginacion'=>'inventario','parametros_paginacion'=>array_intersect_key($filtros,array_flip(array('busqueda','estado','unidad','orden','activo'))),'pagina_actual'=>$pagina,'cantidad_paginas'=>$paginas,'hay_siguiente'=>FALSE)); ?>

<?php $this->load->view('plantilla/pie'); ?>
