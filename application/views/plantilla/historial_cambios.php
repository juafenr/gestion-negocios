<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->library('Edicion_segura');
$pagina_cambios = $this->input->get('pagina_cambios');
$pagina_cambios = is_string($pagina_cambios) && ctype_digit($pagina_cambios) ? max(1,min(100000,(int)$pagina_cambios)) : 1;
$cambios = $this->edicion_segura->historial($historial_entidad,$historial_id,$pagina_cambios);
$hay_mas = count($cambios) > 20;
$cambios = array_slice($cambios,0,20);
$etiquetas = array('precio'=>'Precio','disponible'=>'Disponibilidad de venta','fecha_vencimiento'=>'Fecha de vencimiento');
$formato_cambio = function($campo,$valor) {
    if ($campo === 'disponible') { return (string)$valor === '1' ? 'Disponible' : 'No disponible'; }
    if ($valor === NULL || $valor === '') { return 'Sin vencimiento'; }
    return $campo === 'precio' ? 'Q '.number_format((float)$valor,2) : $valor;
};
?>
<section class="recuadro historial-cambios" aria-labelledby="titulo-cambios">
    <h2 id="titulo-cambios">Historial de cambios</h2>
    <p class="ayuda-campo">Cambios registrados desde la incorporación de este historial. Los historiales anteriores se conservan.</p>
    <div class="tabla"><table>
        <thead><tr><th>Fecha</th><th>Dato modificado</th><th>Anterior</th><th>Nuevo</th><th>Responsable</th></tr></thead>
        <tbody>
        <?php foreach ($cambios as $cambio): ?>
            <tr><td><?= html_escape($cambio['cambiado_en']) ?></td><td><?= html_escape($etiquetas[$cambio['campo']] ?? $cambio['campo']) ?></td><td><?= html_escape($formato_cambio($cambio['campo'],$cambio['valor_anterior'])) ?></td><td><?= html_escape($formato_cambio($cambio['campo'],$cambio['valor_nuevo'])) ?></td><td><?= html_escape($cambio['responsable'] ?? 'Usuario no disponible') ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$cambios): ?><tr><td colspan="5">Todavía no hay cambios registrados.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <nav class="paginacion" aria-label="Paginación del historial de cambios">
    <?php $query_cambios=$this->input->get(); $query_cambios=is_array($query_cambios)?array_filter($query_cambios,'is_string'):array(); ?>
    <?php if ($pagina_cambios > 1): $query_cambios['pagina_cambios']=$pagina_cambios-1; ?><a href="<?= html_escape(current_url().'?'.http_build_query($query_cambios)) ?>">Anterior</a><?php endif; ?>
    <span>Página <?= $pagina_cambios ?></span>
    <?php if ($hay_mas): $query_cambios['pagina_cambios']=$pagina_cambios+1; ?><a href="<?= html_escape(current_url().'?'.http_build_query($query_cambios)) ?>">Siguiente</a><?php endif; ?>
    </nav>
</section>
