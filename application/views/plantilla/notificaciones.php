<?php defined('BASEPATH') OR exit('No direct script access allowed');
$notificaciones = array();
foreach (array('mensaje_exito','mensaje_usuario','mensaje_inventario','mensaje_producto','mensaje_proveedor',
    'mensaje_pedido','mensaje_cocina','mensaje_pago','mensaje_compra','mensaje_compra_seleccion') as $clave) {
    $texto = $this->session->flashdata($clave);
    if (is_string($texto) && $texto !== '') {
        // La selección de compras también comunica avisos y resultados parciales.
        $notificaciones[$texto] = in_array($clave,array('mensaje_compra','mensaje_compra_seleccion'),TRUE) ? 'informacion' : 'exito';
    }
}
foreach ($notificaciones as $texto=>$tipo): ?>
<div class="notificacion notificacion-<?= $tipo ?>" role="status" aria-live="polite" data-notificacion>
    <span class="notificacion-icono" aria-hidden="true"><?= $tipo === 'exito' ? '✓' : 'i' ?></span>
    <p><?= html_escape($texto) ?></p>
</div>
<?php endforeach; ?>
