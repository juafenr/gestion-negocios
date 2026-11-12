<?php defined('BASEPATH') OR exit('No direct script access allowed');
$filtro_valores = array_intersect_key($filtro_valores, $filtro_campos);
$fi_activos = array();
foreach ($filtro_campos as $fi_nombre => $fi_campo) {
    $fi_valor = (string) ($filtro_valores[$fi_nombre] ?? '');
    if ($fi_valor !== (string) ($fi_campo['defecto'] ?? '') && $fi_valor !== '' && empty($fi_campo['obligatorio'])) {
        $fi_activos[$fi_nombre] = $fi_campo['opciones'][$fi_valor] ?? $fi_valor;
    }
}
?>
<form method="get" action="<?= html_escape(site_url($filtro_ruta)) ?>" class="panel-filtros" data-ui-form>
    <div class="cabecera-filtros"><h2>Filtrar resultados</h2><p>Combina los criterios y aplica los filtros.</p></div>
    <fieldset class="campos-filtros"><legend class="solo-lectores">Filtros del listado</legend>
    <?php foreach ($filtro_campos as $fi_nombre => $fi_campo):
        $fi_id = 'filtro-'.$fi_nombre;
        $fi_valor = (string) ($filtro_valores[$fi_nombre] ?? ($fi_campo['defecto'] ?? ''));
    ?>
        <div class="campo <?= ($fi_campo['tipo'] ?? '') === 'search' ? 'campo-busqueda' : '' ?>">
            <label for="<?= html_escape($fi_id) ?>"><?= html_escape($fi_campo['etiqueta']) ?></label>
            <?php if (isset($fi_campo['opciones'])): ?>
            <select id="<?= html_escape($fi_id) ?>" name="<?= html_escape($fi_nombre) ?>">
                <?php foreach ($fi_campo['opciones'] as $fi_clave => $fi_etiqueta): ?><option value="<?= html_escape((string) $fi_clave) ?>" <?= (string) $fi_clave === $fi_valor ? 'selected' : '' ?>><?= html_escape($fi_etiqueta) ?></option><?php endforeach; ?>
            </select>
            <?php else: ?>
            <input id="<?= html_escape($fi_id) ?>" name="<?= html_escape($fi_nombre) ?>" type="<?= ($fi_campo['tipo'] ?? '') === 'date' ? 'date' : 'search' ?>" value="<?= html_escape($fi_valor) ?>"
                <?= ($fi_campo['tipo'] ?? '') === 'date' ? 'min="1000-01-01" max="9999-12-31"' : 'maxlength="150"' ?>
                <?= !empty($fi_campo['obligatorio']) ? 'required' : '' ?>
                <?php if (!empty($fi_campo['ejemplo'])): ?>placeholder="<?= html_escape($fi_campo['ejemplo']) ?>"<?php endif; ?>>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
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
