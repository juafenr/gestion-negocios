<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Pedidos', 'seccion' => 'pedidos', 'usuario' => $usuario)); ?>

<?php $puede_editar = $pedido['estado'] === 'borrador' && $usuario->puede('pedidos.crear'); ?>

<?php if ($mensaje = $this->session->flashdata('mensaje_pedido')): ?>
    <div class="aviso" role="status">
        <?= html_escape($mensaje) ?>
    </div>
<?php endif; ?>

<p>
    <a class="boton boton-secundario" href="<?= html_escape(site_url('pedidos')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/volver.svg')) ?>" alt="" width="22" height="22"><span>Volver a pedidos</span></a>
</p>

<section class="recuadro">
    <h1>Pedido #<?= (int) $pedido['numero'] ?></h1>

    <p>
        <strong>Referencia:</strong>
        <?= html_escape($pedido['referencia']) ?>
    </p>

    <p>
        <strong>Estado:</strong>
        <?= html_escape($pedido['estado']) ?>
    </p>

    <p>
        <strong>Pago:</strong>
        <?= html_escape($pedido['estado_pago']) ?>
    </p>

    <?php if ($puede_editar): ?>
        <h2 class="subtitulo-linea">Agregar producto</h2>

        <?php if (!empty($productos)): ?>
            <?= form_open(
                'pedidos/agregar/' . (int) $pedido['id'], array('class' => 'agregar-producto')
            ) ?>

                <p>
                    <label for="producto_id">Producto</label>

                    <select
                        id="producto_id"
                        name="producto_id"
                        required
                    >
                        <option value="">
                            Selecciona un producto
                        </option>

                        <?php foreach ($productos as $producto): ?>
                            <option
                                value="<?= (int) $producto['id'] ?>"
                            >
                                <?= html_escape($producto['nombre']) ?>
                                — Q <?= number_format(
                                    (float) $producto['precio'],
                                    2
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <label for="cantidad">Cantidad</label>

                    <input
                        type="number"
                        id="cantidad"
                        name="cantidad"
                        min="1"
                        max="999"
                        step="1"
                        value="1"
                        required
                    >
                </p>

                <button type="submit"><img class="icono" src="<?= html_escape(base_url('assets/iconos/agregar.svg')) ?>" alt="" width="22" height="22"><span>Agregar al pedido</span></button>

            <?= form_close() ?>
        <?php else: ?>
            <p>
                No hay productos registrados para esta empresa.
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <h2 class="subtitulo-linea">Productos del pedido</h2>

    <div class="tabla">
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td>
                            <?= html_escape(
                                $detalle['producto_nombre']
                            ) ?>
                        </td>

                        <td>
                            <?php if ($puede_editar): ?>
                                <?= form_open(
                                    'pedidos/actualizar/'
                                    . (int) $pedido['id']
                                    . '/'
                                    . (int) $detalle['id'], array('class' => 'accion-detalle')
                                ) ?>

                                    <input
                                        type="number"
                                        name="cantidad"
                                        min="1"
                                        max="999"
                                        step="1"
                                        required
                                        aria-label="Cantidad de <?= html_escape(
                                            $detalle['producto_nombre']
                                        ) ?>"
                                        value="<?= (int) $detalle['cantidad'] ?>"
                                    >

                                    <button type="submit" class="boton-compacto boton-secundario"><img class="icono" src="<?= html_escape(base_url('assets/iconos/guardar.svg')) ?>" alt="" width="22" height="22"><span>Guardar</span></button>

                                <?= form_close() ?>
                            <?php else: ?>
                                <?= (int) $detalle['cantidad'] ?>
                            <?php endif; ?>
                        </td>

                        <td>
                            Q <?= number_format(
                                (float) $detalle['precio_unitario'],
                                2
                            ) ?>
                        </td>

                        <td>
                            Q <?= number_format(
                                (float) $detalle['precio_unitario']
                                * (int) $detalle['cantidad'],
                                2
                            ) ?>
                        </td>

                        <td>
                            <?php if ($puede_editar): ?>
                                <?= form_open(
                                    'pedidos/quitar/'
                                    . (int) $pedido['id']
                                    . '/'
                                    . (int) $detalle['id'], array('class' => 'accion-detalle')
                                ) ?>

                                    <button type="submit" class="boton-compacto boton-quitar"><img class="icono" src="<?= html_escape(base_url('assets/iconos/quitar.svg')) ?>" alt="" width="22" height="22"><span>Quitar</span></button>

                                <?= form_close() ?>
                            <?php else: ?>
                                Sin acciones disponibles
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($detalles)): ?>
                    <tr>
                        <td colspan="5">
                            Este pedido todavía no tiene productos.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p class="total-pedido"><strong>Total del pedido</strong>
        Q <?= number_format((float) $pedido['total'], 2) ?>
    </p>

    <?php if (
        $pedido['estado'] === 'borrador'
        && $usuario->puede('pedidos.confirmar')
    ): ?>
        <?php if (!empty($detalles)): ?>
            <p>
                Al confirmar, los productos ya no podrán modificarse y el
                pedido aparecerá en cocina.
            </p>

            <?= form_open('pedidos/' . (int) $pedido['id'] . '/confirmar') ?>
                <button type="submit">
                    <img class="icono" src="<?= html_escape(base_url('assets/iconos/correcto.svg')) ?>" alt="" width="22" height="22">
                    <span>Confirmar y enviar a cocina</span>
                </button>
            <?= form_close() ?>
        <?php else: ?>
            <p>Agrega al menos un producto antes de confirmar el pedido.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($pedido['confirmado_en'])): ?>
        <p>
            <strong>Confirmado:</strong>
            <?= html_escape($pedido['confirmado_en']) ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($eventos)): ?>
        <h2 class="subtitulo-linea">Historial de estados</h2>

        <div class="tabla">
            <table class="tabla-historial-pedido">
                <thead>
                    <tr>
                        <th>Cambio</th>
                        <th>Responsable</th>
                        <th>Rol</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $evento): ?>
                        <tr>
                            <td>
                                <?= html_escape(ucfirst($evento['estado_anterior'])) ?>
                                →
                                <?= html_escape(ucfirst($evento['estado_nuevo'])) ?>
                            </td>
                            <td><?= html_escape($evento['usuario_nombre']) ?></td>
                            <td><?= html_escape(ucfirst($evento['rol_codigo'])) ?></td>
                            <td><?= html_escape($evento['creado_en']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php $this->load->view('plantilla/pie'); ?>
