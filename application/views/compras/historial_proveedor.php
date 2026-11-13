<?php
$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Historial de compras',
    'seccion' => 'compras',
    'usuario' => $usuario
));
?>

<div class="titulo-acciones">
    <div>
        <p class="sobretitulo">COMPRAS</p>

        <h1>
            Historial: <?= html_escape($proveedor['nombre']) ?>
        </h1>

        <p class="descripcion">
            Solicitudes registradas: <?= (int) $total ?>
        </p>
    </div>

    <a class="boton boton-secundario"
        href="<?= html_escape(site_url('proveedores')) ?>">
        Volver a proveedores
    </a>
</div>

<div class="tabla"
    role="region"
    aria-label="Historial de solicitudes"
    tabindex="0">

    <table>
        <thead>
            <tr>
                <th>Número</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($solicitudes as $solicitud): ?>
                <tr>
                    <td>
                        #<?= (int) $solicitud['numero'] ?>
                    </td>

                    <td>
                        <?= html_escape($solicitud['fecha']) ?>
                    </td>

                    <td>
                        <?= ui_estado($solicitud['estado'], 'compra') ?>
                    </td>

                    <td>
                        <a class="boton boton-secundario boton-compacto"
                            href="<?= html_escape(
                                        site_url(
                                            'compras/' . (int) $solicitud['id']
                                        )
                                    ) ?>">
                            Ver solicitud
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($solicitudes)): ?>
                <tr>
                    <td colspan="4">
                        Este proveedor no tiene solicitudes registradas.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->load->view('plantilla/paginacion',array('ruta_paginacion'=>'compras/proveedor/'.(int)$proveedor['id'].'/historial','parametros_paginacion'=>array(),'pagina_actual'=>$pagina,'cantidad_paginas'=>$paginas,'hay_siguiente'=>FALSE)); ?>
<?php $this->load->view('plantilla/pie'); ?>
