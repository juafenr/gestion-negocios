<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Pedidos', 'seccion' => 'pedidos', 'usuario' => $usuario)); ?>

<?php $puede_editar = $pedido['estado'] === 'borrador' && $usuario->puede('pedidos.crear'); ?>

<p>
    <a class="boton boton-secundario" href="<?= html_escape(site_url('pedidos')) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/volver.svg')) ?>" alt="" width="22" height="22"><span>Volver a pedidos</span></a>
</p>

<?php if (!empty($mensaje)): ?>
    <p role="status">
        <?= html_escape($mensaje) ?>
    </p>
<?php endif; ?>

<?php if (!empty($error_pedido)): ?>
    <div class="error" role="alert">
        <?= html_escape($error_pedido) ?>
    </div>
<?php endif; ?>

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

    <?php if ($usuario->puede('ventas.ver') && $usuario->puede('pedidos.ver') && $pedido['estado'] !== 'borrador'): ?>
        <p><a class="boton" href="<?= html_escape(site_url('pagos/'.(int)$pedido['id'])) ?>"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pagos.svg')) ?>" alt="">Pagos y cuentas</a></p>
    <?php endif; ?>

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

    <p class="total-pedido"><strong>Consumo del pedido</strong>
        Q <?= number_format((float) $pedido['total'], 2) ?>
    </p>
                    
    <p>Propina (10%): <strong><?= Dinero::mostrar(Dinero::propina(Dinero::centavos($pedido['total']))) ?></strong> ·
    Total con propina: <strong><?= Dinero::mostrar(Dinero::centavos($pedido['total'])+Dinero::propina(Dinero::centavos($pedido['total']))) ?></strong></p>

    <?php if ($pedido['estado'] === 'borrador'
        && $usuario->puede('pedidos.confirmar')
    ): ?>
        <?php if (!empty($detalles)): ?>
            <p>
                Al confirmar, ya no podrás modificar los productos.
                El pedido puede quedar pendiente de pago.
            </p>

            <?= form_open('pedidos/confirmar/' . (int) $pedido['id']) ?>
                <button type="submit">
                    Confirmar pedido
                </button>
            <?= form_close() ?>
        <?php else: ?>
            <p>Agrega al menos un producto para confirmar el pedido.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($pedido['confirmado_en'])): ?>
        <p>
            <strong>Fecha de confirmación:</strong>
            <?= html_escape($pedido['confirmado_en']) ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($cancelacion)): ?>

    <h2 class="subtitulo-linea">Registro de cancelación</h2>

    <p>
        <strong>Motivo:</strong>
        <?= nl2br(html_escape($cancelacion['motivo'])) ?>
    </p>

    <p>
        <strong>Autorizado por:</strong>
        <?= html_escape(
            $cancelacion['autorizado_por'] ?? 'Usuario no disponible'
        ) ?>
    </p>

    <p>
        <strong>Fecha de cancelación:</strong>
        <?= html_escape($cancelacion['cancelado_en']) ?>
    </p>

    <p>
        <strong>Estado anterior:</strong>
        <?= html_escape($cancelacion['estado_anterior']) ?>
    </p>

    <p>
        Este pedido está cancelado. Sus productos y total
        se conservan como registro histórico.
    </p>

<?php elseif (
    $usuario->puede('pedidos.cancelar')
    && in_array(
        $pedido['estado'],
        array('borrador', 'pendiente', 'en_preparacion', 'listo'),
        TRUE
    )
    && $pedido['estado_pago'] === 'pendiente'
): ?>

    <h2 class="subtitulo-linea">Cancelar pedido</h2>

    <p>
        La cancelación quedará autorizada con tu usuario.
        Después no podrás modificar este pedido.
    </p>

    <?= form_open('pedidos/cancelar/' . (int) $pedido['id']) ?>

        <p>
            <label for="motivo">Motivo de cancelación</label>
        </p>

        <p>
            <textarea
                id="motivo"
                name="motivo"
                rows="4"
                cols="35"
                maxlength="500"
                required
            ><?= html_escape($motivo_anterior ?? '') ?></textarea>
        </p>

        <p>
            <label>
                <input
                    type="checkbox"
                    name="confirmar_cancelacion"
                    value="1"
                    required
                >
                Autorizo cancelar este pedido.
            </label>
        </p>

        <button type="submit" class="boton-quitar">
            Cancelar pedido
        </button>

    <?= form_close() ?>

<?php endif; ?>
</section>

<?php $this->load->view('plantilla/pie'); ?>
