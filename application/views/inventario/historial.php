<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Historial de inventario','seccion'=>'inventario','usuario'=>$usuario)); ?>


<p>
    <a class="boton boton-secundario" href="<?= html_escape(site_url('inventario')) ?>">
        Volver al inventario
    </a>
</p>

<h1>
    Historial: <?= html_escape($insumo['nombre']) ?>
</h1>

<p>
    Total de movimientos:
    <strong><?= (int) $total ?></strong>
</p>

<?php
$mensaje = $this->session
    ->flashdata('mensaje_inventario');
?>

<?php if ($mensaje): ?>
    <p role="status">
        <?= html_escape($mensaje) ?>
    </p>
<?php endif; ?>

<div class="tabla">

    <table>

        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th class="tabla-numero">Cantidad</th>
                <th class="tabla-numero">Anterior</th>
                <th class="tabla-numero">Nueva</th>
                <th>Motivo</th>
                <th>Responsable</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($movimientos as $movimiento): ?>

                <tr>

                    <td>
                        <?= html_escape(
                            $movimiento['fecha']
                        ) ?>
                    </td>

                    <td>
                        <?= html_escape(
                            $movimiento['tipo']
                        ) ?>
                    </td>

                    <td class="tabla-numero">
                        <?= html_escape(
                            $movimiento['cantidad']
                        ) ?>
                    </td>

                    <td class="tabla-numero">
                        <?= html_escape(
                            $movimiento['cantidad_anterior']
                        ) ?>
                    </td>

                    <td class="tabla-numero">
                        <?= html_escape(
                            $movimiento['cantidad_nueva']
                        ) ?>
                    </td>

                    <td>
                        <?= html_escape(
                            $movimiento['motivo']
                        ) ?>
                    </td>

                    <td>
                        <?= html_escape(
                            $movimiento['responsable']
                        ) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

            <?php if (empty($movimientos)): ?>

                <tr>
                    <td colspan="7">
                        No hay movimientos registrados.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<!-- Paginación del historial -->

<?php $this->load->view('plantilla/paginacion',array('ruta_paginacion'=>'inventario/'.(int)$insumo['id'].'/historial','parametros_paginacion'=>array(),'pagina_actual'=>$pagina,'cantidad_paginas'=>$paginas,'hay_siguiente'=>FALSE)); ?>
<?php $this->load->view('plantilla/pie'); ?>
