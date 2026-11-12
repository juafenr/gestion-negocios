<?php defined('BASEPATH') OR exit('No direct script access allowed'); $re_total=(int)($resultado_total ?? $resultado_mostrados); ?>
<div class="resumen-listado" role="status">
    <p><strong><?= $re_total ?></strong> <?= isset($resultado_total) ? ($re_total===1?'resultado encontrado':'resultados encontrados') : ($re_total===1?'resultado en esta página':'resultados en esta página') ?></p>
    <span><?= (int) $resultado_mostrados ?> <?= (int)$resultado_mostrados===1?'visible':'visibles' ?><?php if (isset($resultado_pagina)): ?> · Página <?= (int) $resultado_pagina ?><?php endif; ?></span>
</div>
