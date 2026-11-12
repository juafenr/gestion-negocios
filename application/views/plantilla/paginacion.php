<?php defined('BASEPATH') OR exit('No direct script access allowed');
$pa_actual = max(1, (int) $pagina_actual);
$pa_total = isset($cantidad_paginas) ? max(1, (int) $cantidad_paginas) : NULL;
$pa_clave = $parametro_pagina ?? 'pagina';
$pa_url = function ($numero) use ($ruta_paginacion, $parametros_paginacion, $pa_clave) {
    return site_url($ruta_paginacion).'?'.http_build_query(array_merge($parametros_paginacion, array($pa_clave=>(int) $numero)));
};
?>
<nav class="paginacion-listado" aria-label="<?= html_escape($etiqueta_paginacion ?? 'Paginación del listado') ?>">
    <span>Página <?= $pa_actual ?><?= $pa_total !== NULL ? ' de '.$pa_total : '' ?></span>
    <div class="paginas-listado">
        <?php if ($pa_actual > 1): ?><a class="boton boton-secundario boton-compacto" rel="prev" href="<?= html_escape($pa_url($pa_actual-1)) ?>">Anterior</a><?php endif; ?>
        <?php if ($pa_total !== NULL && $pa_total > 1):
            $pa_numeros = array_unique(array_merge(array(1), range(max(1,$pa_actual-2), min($pa_total,$pa_actual+2)), array($pa_total)));
            sort($pa_numeros); $pa_anterior = 0;
            foreach ($pa_numeros as $pa_numero):
                if ($pa_anterior && $pa_numero > $pa_anterior+1): ?><span aria-hidden="true">…</span><?php endif; ?>
                <?php if ($pa_numero === $pa_actual): ?><span class="pagina-actual" aria-current="page"><?= $pa_numero ?></span>
                <?php else: ?><a class="boton boton-secundario boton-compacto" href="<?= html_escape($pa_url($pa_numero)) ?>" aria-label="Ir a la página <?= $pa_numero ?>"><?= $pa_numero ?></a><?php endif; ?>
            <?php $pa_anterior = $pa_numero; endforeach; endif; ?>
        <?php if (($pa_total !== NULL && $pa_actual < $pa_total) || ($pa_total === NULL && !empty($hay_siguiente))): ?><a class="boton boton-secundario boton-compacto" rel="next" href="<?= html_escape($pa_url($pa_actual+1)) ?>">Siguiente</a><?php endif; ?>
    </div>
</nav>
