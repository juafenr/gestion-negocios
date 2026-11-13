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


<?php $this->load->view('plantilla/filtros', array('filtro_ruta'=>'productos', 'filtro_campos'=>array(
'busqueda'=>array('tipo'=>'search','etiqueta'=>'Nombre, código o descripción','ejemplo'=>'Ej. Cuaderno o A-001'),
'disponibilidad'=>array('etiqueta'=>'Disponibilidad','opciones'=>array(''=>'Todos los productos','1'=>'Disponible','0'=>'No disponible'))
), 'filtro_valores'=>array('busqueda'=>$busqueda,'disponibilidad'=>$disponibilidad), 'filtro_extras'=>array())); ?>
<?php $this->load->view('plantilla/resultados', array('resultado_total'=>$total, 'resultado_mostrados'=>count($productos), 'resultado_pagina'=>$pagina)); ?>


<div class="tabla">

    <table>
        <thead>
            <tr>
                <th scope="col">Producto</th>
                <th scope="col">Código</th>
                <th scope="col">Descripción</th>
                <th class="tabla-numero" scope="col">Precio</th>
                <th scope="col">Disponibilidad</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($productos as $producto): ?>
                <tr>
                    <td>
                        <?php if (!empty($producto['imagen'])): ?><img class="imagen-miniatura" src="<?= html_escape(site_url('productos/'.(int)$producto['id'].'/imagen')) ?>" alt="" width="48" height="48"><?php else: ?><span class="imagen-miniatura imagen-miniatura-vacia" aria-hidden="true">—</span><?php endif; ?>
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

                    <td class="tabla-numero">
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
                            <?php $this->load->view('plantilla/disponibilidad_venta', array('venta_producto'=>$producto, 'venta_origen'=>'productos', 'venta_reactivar'=>$producto['insumo_id'] === NULL || (int)$producto['insumo_activo'] === 1)); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($productos)): ?>
                <tr>
                    <td colspan="6"><?php $this->load->view('plantilla/vacio',array('vacio_titulo'=>$hay_filtros?'No hay productos que coincidan con la búsqueda.':'Todavía no hay productos registrados.','vacio_descripcion'=>'Revisa la búsqueda y la disponibilidad seleccionada.','vacio_url'=>$hay_filtros?site_url('productos'):NULL,'vacio_accion'=>'Limpiar filtros')); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->load->view('plantilla/paginacion', array('ruta_paginacion'=>'productos','parametros_paginacion'=>array('busqueda'=>$busqueda,'disponibilidad'=>$disponibilidad),'pagina_actual'=>$pagina,'cantidad_paginas'=>$paginas,'hay_siguiente'=>FALSE)); ?>
<?php $this->load->view('plantilla/pie'); ?>
