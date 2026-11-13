<?php defined('BASEPATH') OR exit('No direct script access allowed');
$filtro_valores = array_intersect_key($filtro_valores, $filtro_campos);
$fi_activos = array();
foreach ($filtro_campos as $fi_nombre => $fi_campo) {
    $fi_valor = (string) ($filtro_valores[$fi_nombre] ?? '');
    if ($fi_valor !== (string) ($fi_campo['defecto'] ?? '') && $fi_valor !== '' && empty($fi_campo['obligatorio'])) {
        $fi_activos[$fi_nombre] = $fi_campo['opciones'][$fi_valor] ?? $fi_valor;
    }
}
$fi_principales = array_filter($filtro_campos, function ($campo) {
    return ($campo['tipo'] ?? '') === 'search' || !empty($campo['obligatorio']);
});
if (!$fi_principales && $filtro_campos) {
    $primero = array_key_first($filtro_campos);
    $fi_principales[$primero] = $filtro_campos[$primero];
}
$fi_adicionales = array_diff_key($filtro_campos, $fi_principales);
$fi_cantidad = count(array_intersect_key($fi_activos, $fi_adicionales));
?>
<form method="get" action="<?= html_escape(site_url($filtro_ruta)) ?>" class="panel-filtros" data-ui-form>
    <div class="cabecera-filtros"><h2>Filtrar resultados</h2><p>Combina los criterios y aplica los filtros.</p></div>
    <fieldset class="campos-filtros"><legend class="solo-lectores">Filtros del listado</legend>
    <?php foreach ($fi_principales as $fi_nombre=>$fi_campo): ?>
        <?php $this->load->view('plantilla/campo_filtro', array('fi_nombre'=>$fi_nombre,'fi_campo'=>$fi_campo,'filtro_valores'=>$filtro_valores)); ?>
    <?php endforeach; ?>
    <?php if ($fi_adicionales): ?>
        <details class="filtros-adicionales" open data-filtros-activos="<?= $fi_cantidad ?>">
            <summary>Más filtros<?php if ($fi_cantidad): ?> <span class="cantidad-filtros"><?= $fi_cantidad ?> <?= $fi_cantidad === 1 ? 'aplicado' : 'aplicados' ?></span><?php endif; ?><span class="flecha-filtros" aria-hidden="true">⌄</span></summary>
            <fieldset class="campos-filtros-extra"><legend class="solo-lectores">Criterios adicionales</legend>
            <?php foreach ($fi_adicionales as $fi_nombre=>$fi_campo): ?>
                <?php $this->load->view('plantilla/campo_filtro', array('fi_nombre'=>$fi_nombre,'fi_campo'=>$fi_campo,'filtro_valores'=>$filtro_valores)); ?>
            <?php endforeach; ?>
            </fieldset>
        </details>
    <?php endif; ?>
    </fieldset>
    <div class="acciones-filtros">
        <button type="submit"><img class="icono" src="<?= html_escape(base_url('assets/iconos/buscar.svg')) ?>" alt="">Aplicar filtros</button>
        <a class="boton boton-secundario" href="<?= html_escape(site_url($filtro_ruta)) ?>">Limpiar filtros</a>
        <?php foreach (($filtro_extras ?? array()) as $fi_extra): ?><a class="boton boton-secundario accion-filtro-extra" href="<?= html_escape(site_url($fi_extra['ruta'])) ?>" title="<?= html_escape($fi_extra['ayuda'] ?? '') ?>"><?= html_escape($fi_extra['etiqueta']) ?></a><?php endforeach; ?>
    </div>
    <?php if ($fi_activos): ?><div class="filtros-activos" aria-label="Filtros aplicados"><span>Aplicados:</span>
        <?php foreach ($fi_activos as $fi_nombre => $fi_texto):
            $fi_resto = $filtro_valores; unset($fi_resto[$fi_nombre], $fi_resto['pagina']);
        ?><a href="<?= html_escape(site_url($filtro_ruta).'?'.http_build_query($fi_resto)) ?>" aria-label="<?= html_escape('Quitar filtro '.$filtro_campos[$fi_nombre]['etiqueta'].': '.$fi_texto) ?>"><?= html_escape($filtro_campos[$fi_nombre]['etiqueta'].': '.$fi_texto) ?><span aria-hidden="true">×</span></a><?php endforeach; ?>
    </div><?php endif; ?>
</form>
