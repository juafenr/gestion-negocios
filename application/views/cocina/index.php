<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Cocina', 'seccion' => 'cocina', 'usuario' => $usuario)); ?>

<p class="sobretitulo">PREPARACIÓN DE PEDIDOS</p>
<h1>Cocina</h1>
<p class="descripcion">
    Pedidos confirmados de <?= html_escape($usuario->empresaNombre()) ?>,
    ordenados del más antiguo al más reciente.
</p>

<?php if ($mensaje = $this->session->flashdata('mensaje_cocina')): ?>
    <div class="aviso" role="status">
        <?= html_escape($mensaje) ?>
    </div>
<?php endif; ?>

<?php if (!empty($pedidos)): ?>
    <div class="cola-cocina">
        <?php foreach ($pedidos as $pedido): ?>
            <article class="pedido-cocina">
                <div class="pedido-cocina-encabezado">
                    <div>
                        <h2>Pedido #<?= (int) $pedido['numero'] ?></h2>
                        <p><?= html_escape($pedido['referencia']) ?></p>
                    </div>

                    <span class="estado estado-<?= html_escape($pedido['estado']) ?>">
                        <?= html_escape(ucfirst($pedido['estado'])) ?>
                    </span>
                </div>

                <p>
                    <strong>Confirmado:</strong>
                    <?= html_escape($pedido['confirmado_en']) ?>
                </p>

                <h3>Productos</h3>
                <ul class="productos-cocina">
                    <?php foreach ($pedido['detalles'] as $detalle): ?>
                        <li>
                            <strong><?= (int) $detalle['cantidad'] ?> ×</strong>
                            <?= html_escape($detalle['producto_nombre']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php
                $siguientes = array(
                    'pendiente' => array('preparacion', 'Iniciar preparación'),
                    'preparacion' => array('listo', 'Marcar como listo'),
                    'listo' => array('entregado', 'Marcar como entregado')
                );
                $siguiente = $siguientes[$pedido['estado']];
                ?>

                <?= form_open(
                    'cocina/pedidos/' . (int) $pedido['id'] . '/estado',
                    array('class' => 'accion-cocina')
                ) ?>
                    <input
                        type="hidden"
                        name="estado"
                        value="<?= html_escape($siguiente[0]) ?>"
                    >
                    <button type="submit">
                        <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22">
                        <span><?= html_escape($siguiente[1]) ?></span>
                    </button>
                <?= form_close() ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <section class="recuadro aviso" role="status">
        <h2>No hay pedidos en espera</h2>
        <p>Los pedidos aparecerán aquí después de ser confirmados.</p>
    </section>
<?php endif; ?>

<?php $this->load->view('plantilla/pie'); ?>
