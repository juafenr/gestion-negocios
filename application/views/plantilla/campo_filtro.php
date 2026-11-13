<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fi_id = 'filtro-'.$fi_nombre;
$fi_valor = (string) ($filtro_valores[$fi_nombre] ?? ($fi_campo['defecto'] ?? ''));
?>
<div class="campo <?= ($fi_campo['tipo'] ?? '') === 'search' ? 'campo-busqueda' : '' ?>">
    <label for="<?= html_escape($fi_id) ?>"><?= html_escape($fi_campo['etiqueta']) ?></label>
    <?php if (isset($fi_campo['opciones'])): ?>
    <select id="<?= html_escape($fi_id) ?>" name="<?= html_escape($fi_nombre) ?>">
        <?php foreach ($fi_campo['opciones'] as $fi_clave=>$fi_etiqueta): ?>
        <option value="<?= html_escape((string)$fi_clave) ?>" <?= (string)$fi_clave === $fi_valor ? 'selected' : '' ?>><?= html_escape($fi_etiqueta) ?></option>
        <?php endforeach; ?>
    </select>
    <?php else: ?>
    <input id="<?= html_escape($fi_id) ?>" name="<?= html_escape($fi_nombre) ?>" type="<?= ($fi_campo['tipo'] ?? '') === 'date' ? 'date' : 'search' ?>" value="<?= html_escape($fi_valor) ?>"
        <?= ($fi_campo['tipo'] ?? '') === 'date' ? 'min="1000-01-01" max="9999-12-31"' : 'maxlength="150"' ?>
        <?= !empty($fi_campo['obligatorio']) ? 'required' : '' ?>
        <?php if (!empty($fi_campo['ejemplo'])): ?>placeholder="<?= html_escape($fi_campo['ejemplo']) ?>"<?php endif; ?>>
    <?php endif; ?>
</div>
