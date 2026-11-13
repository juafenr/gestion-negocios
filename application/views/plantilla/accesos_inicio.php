<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="accesos">
<?php foreach ($lista_accesos as $ruta=>$acceso): ?>
    <a class="acceso" href="<?= html_escape(site_url($ruta)) ?>">
        <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/'.$acceso[2])) ?>" alt="" width="22" height="22"></span>
        <h3><?= html_escape($acceso[0]) ?></h3>
        <p><?= html_escape($acceso[1]) ?></p>
        <span class="acceso-abrir">Abrir <?= html_escape(mb_strtolower($acceso[0])) ?></span>
    </a>
<?php endforeach; ?>
</div>
