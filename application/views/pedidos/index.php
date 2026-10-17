<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Pedidos', 'seccion' => 'pedidos', 'usuario' => $usuario)); ?>

<div class="titulo-acciones"><div><p class="sobretitulo">REGISTRO DE PEDIDOS</p><h1>Pedidos</h1><p class="descripcion">Consulta los pedidos y sus productos.</p></div><?php if ($usuario->puede('pedidos.crear')): ?>

    <p>
        <a class="boton" href="<?= html_escape(site_url('pedidos/crear')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/agregar.svg')) ?>" alt="" width="22" height="22"><span>Nuevo pedido</span></a>
    </p>

<?php endif; ?></div>





<div class="tabla">

    <table>

        <thead>

            <tr>
                <th>Número</th>
                <th>Referencia</th>
                <th>Estado</th>
                <th>Pago</th>
                <th>Consumo</th>
                <th>Detalle</th>
            </tr>

        </thead>

        <tbody>

            <?php foreach ($pedidos as $pedido): ?>

                <tr>

                    <td>
                        #<?= (int) $pedido['numero'] ?>
                    </td>

                    <td>
                        <?= html_escape(
                            $pedido['referencia']
                        ) ?>
                    </td>

                    <td>
                        <?= html_escape(
                            $pedido['estado']
                        ) ?>
                    </td>

                    <td>
                        <?= html_escape(
                            $pedido['estado_pago']
                        ) ?>
                    </td>

                    <td>
                        Q <?= number_format(
                                (float) $pedido['total'],
                                2
                            ) ?>
                    </td>

                    <td>

                        <a class="boton boton-secundario boton-compacto" href="<?= html_escape(
                                        site_url(
                                            'pedidos/' .
                                                $pedido['id']
                                        )
                                    ) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/ver.svg')) ?>" alt="" width="22" height="22"><span>Ver detalle</span></a>

                    </td>

                </tr>

            <?php endforeach; ?>

            <?php if (empty($pedidos)): ?>

                <tr>

                    <td colspan="6">
                        No hay pedidos registrados.
                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php $this->load->view('plantilla/pie'); ?>
