<?php defined('BASEPATH') OR exit('No direct script access allowed');
$en_venta = (int)$venta_producto['disponible'] === 1;
$etiqueta_venta = $en_venta ? 'Desactivar venta' : 'Reactivar venta';
?>
<?php if (!$en_venta && !$venta_reactivar): ?>
    <button type="button" class="boton boton-secundario boton-compacto" disabled title="Reactiva primero el insumo asociado.">Reactivar venta</button>
<?php else: ?>
<?= form_open('productos/'.(int)$venta_producto['id'].'/disponibilidad', array(
    'class'=>'form-disponibilidad-venta',
    'data-confirmar'=>html_escape(($en_venta ? '¿Desactivar la venta de «' : '¿Reactivar la venta de «').$venta_producto['nombre'].'»? '.
        ($en_venta ? 'No estará disponible para agregar ni confirmar pedidos mientras esté desactivada. Se conservarán sus datos y los pedidos ya confirmados.' : 'Volverá a estar disponible para nuevos pedidos.'))
)) ?>
    <input type="hidden" name="disponible" value="<?= $en_venta ? '0' : '1' ?>">
    <input type="hidden" name="origen" value="<?= html_escape($venta_origen) ?>">
    <button type="submit" class="boton boton-secundario boton-compacto <?= $venta_origen === 'inventario' ? 'accion-venta' : '' ?>">
        <img class="icono" src="<?= html_escape(base_url('assets/iconos/productos.svg')) ?>" alt="" width="22" height="22">
        <span><?= $etiqueta_venta ?></span>
    </button>
<?= form_close() ?>
<?php endif; ?>
