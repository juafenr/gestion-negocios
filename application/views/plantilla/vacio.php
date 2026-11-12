<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="estado-vacio"><span aria-hidden="true">⌕</span><strong><?= html_escape($vacio_titulo) ?></strong><p><?= html_escape($vacio_descripcion) ?></p>
<?php if (!empty($vacio_url)): ?><a class="boton boton-secundario boton-compacto" href="<?= html_escape($vacio_url) ?>"><?= html_escape($vacio_accion) ?></a><?php endif; ?></div>
