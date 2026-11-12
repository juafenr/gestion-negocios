<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Productos', 'seccion' => 'productos', 'usuario' => $usuario)); ?>

<p class="sobretitulo">CATÁLOGO</p><h1>Productos</h1>

<p class="descripcion">Consulta los productos disponibles en el catálogo de tu empresa y sus precios.</p>

<?php $this->load->view('plantilla/filtros', array('filtro_ruta'=>'productos', 'filtro_campos'=>array(
'busqueda'=>array('tipo'=>'search','etiqueta'=>'Nombre o código','ejemplo'=>'Ej. Cuaderno o A-001')
), 'filtro_valores'=>array('busqueda'=>$busqueda), 'filtro_extras'=>array())); ?>
<?php $this->load->view('plantilla/resultados', array('resultado_total'=>$total, 'resultado_mostrados'=>count($productos), 'resultado_pagina'=>$pagina)); ?>


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
                    <td colspan="4"><?php $this->load->view('plantilla/vacio',array('vacio_titulo'=>$busqueda!==''?'No hay productos que coincidan con la búsqueda.':'Todavía no hay productos registrados.','vacio_descripcion'=>'Los productos disponibles en tu empresa aparecerán en este listado.','vacio_url'=>$busqueda!==''?site_url('productos'):NULL,'vacio_accion'=>'Limpiar filtros')); ?></td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php $this->load->view('plantilla/paginacion', array('ruta_paginacion'=>'productos','parametros_paginacion'=>array('busqueda'=>$busqueda),'pagina_actual'=>$pagina,'cantidad_paginas'=>$paginas,'hay_siguiente'=>FALSE)); ?>
<?php $this->load->view('plantilla/pie'); ?>
