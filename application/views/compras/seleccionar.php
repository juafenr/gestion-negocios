<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Preparar compra', 'seccion' => 'compras', 'usuario' => $usuario)); ?>

<p class="sobretitulo">SOLICITUD DE COMPRA</p>
<h1>Seleccionar insumos</h1>
<p class="descripcion">Indica cuánto necesitas comprar de cada insumo. Se creará un borrador independiente por proveedor.</p>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('compras')) ?>">Ver solicitudes</a></p>

<?php if (!empty($mensaje)): ?>
    <p class="recuadro aviso" role="status"><?= html_escape($mensaje) ?></p>
<?php endif; ?>
<?php if (!empty($creadas)): ?>
    <ul>
        <?php foreach ($creadas as $creada): ?>
            <li><a href="<?= html_escape(site_url('compras/' . (int) $creada['id'])) ?>">Revisar borrador de <?= html_escape($creada['proveedor']) ?></a></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <p class="error" role="alert"><?= html_escape($error) ?></p>
<?php endif; ?>

<?php if ($insumos): ?>
    <?= form_open('compras/seleccionar', array('class' => 'formulario recuadro')) ?>
        <div class="tabla">
            <table>
                <thead><tr><th>Insumo</th><th class="tabla-numero">Existencia</th><th class="tabla-numero">Mínimo</th><th class="tabla-numero">Cantidad a comprar</th></tr></thead>
                <tbody>
                    <?php foreach ($insumos as $insumo): ?>
                        <tr>
                            <td><?= html_escape($insumo['nombre']) ?></td>
                            <td class="tabla-numero"><?= html_escape($insumo['cantidad_actual']) ?> <?= html_escape($insumo['unidad_medida']) ?></td>
                            <td class="tabla-numero"><?= html_escape($insumo['stock_minimo']) ?> <?= html_escape($insumo['unidad_medida']) ?></td>
                            <td class="tabla-numero">
                                <label for="cantidad-<?= (int) $insumo['id'] ?>">Cantidad de <?= html_escape($insumo['nombre']) ?></label>
                                <input id="cantidad-<?= (int) $insumo['id'] ?>" name="cantidad[<?= (int) $insumo['id'] ?>]" type="number" min="0.01" max="99999999.99" step="0.01" value="<?= html_escape($valores[(int) $insumo['id']] ?? '') ?>" inputmode="decimal">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="nota">Deja vacíos los insumos que no necesitas. La cantidad debe ser mayor que cero y puede tener hasta dos decimales.</p>
        <button type="submit">Revisar selección</button>
    <?= form_close() ?>
<?php else: ?>
    <p class="recuadro">No hay insumos activos disponibles para preparar una compra.</p>
<?php endif; ?>

<?php if ($seleccion): ?>
    <?php
    $nombres_proveedores = array();
    foreach ($proveedores as $proveedor) {
        $nombres_proveedores[(int) $proveedor['id']] = $proveedor['nombre'];
    }
    $faltan_proveedores = FALSE;
    $grupos = array();
    foreach ($seleccion as $linea) {
        $proveedor_id = $linea['proveedor_id'];
        if ($proveedor_id === NULL || !isset($nombres_proveedores[(int) $proveedor_id])) {
            $faltan_proveedores = TRUE;
            continue;
        }
        $grupos[(int) $proveedor_id][] = $linea;
    }
    ?>
    <section class="recuadro" aria-labelledby="resumen-compra">
        <h2 id="resumen-compra">Selección revisada</h2>
        <div class="tabla">
            <table>
                <thead><tr><th>Insumo</th><th class="tabla-numero">Cantidad solicitada</th><th>Proveedor</th></tr></thead>
                <tbody>
                    <?php foreach ($seleccion as $linea): ?>
                        <tr><td><?= html_escape($linea['nombre']) ?></td><td class="tabla-numero"><?= html_escape($linea['cantidad_solicitada']) ?> <?= html_escape($linea['unidad_medida']) ?></td><td><?= html_escape($nombres_proveedores[(int) $linea['proveedor_id']] ?? 'Sin proveedor activo asociado') ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($faltan_proveedores): ?>
            <p class="nota">Asocia un proveedor activo a cada insumo pendiente antes de generar solicitudes.</p>
        <?php else: ?>
            <p class="nota">Se crearán <?= count($grupos) ?> <?= count($grupos) === 1 ? 'solicitud' : 'solicitudes' ?> en estado borrador.</p>
            <?= form_open('compras/generar', array('class' => 'formulario')) ?>
                <label for="fecha-compra">Fecha de solicitud</label>
                <input id="fecha-compra" name="fecha" type="date" value="<?= html_escape(date('Y-m-d')) ?>" required>
                <label for="observaciones-compra">Observaciones para los proveedores</label>
                <textarea id="observaciones-compra" name="observaciones" maxlength="1000"></textarea>
                <button type="submit">Generar borradores por proveedor</button>
            <?= form_close() ?>
        <?php endif; ?>
        <?= form_open('compras/vaciar') ?>
            <button type="submit" class="boton-secundario">Vaciar selección</button>
        <?= form_close() ?>
    </section>
<?php endif; ?>

<?php $this->load->view('plantilla/pie'); ?>
