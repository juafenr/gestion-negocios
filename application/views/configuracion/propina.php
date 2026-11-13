<?php $this->load->view('plantilla/cabecera', array('titulo'=>'Configuración de propina','seccion'=>'configuracion','usuario'=>$usuario)); ?>
<h1>Configuración de propina</h1>
<p class="descripcion">El porcentaje se aplica a los pedidos nuevos. Los pedidos existentes conservan el porcentaje con el que fueron creados.</p>
<?php if ($mensaje): ?><p class="aviso" role="status"><?= html_escape($mensaje) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error" role="alert"><?= html_escape($error) ?></p><?php endif; ?>
<section class="recuadro panel-formulario">
<?= form_open('configuracion/propina', array('class'=>'formulario-ui')) ?>
<p class="campo"><label for="porcentaje">Propina del negocio</label>
<select id="porcentaje" name="porcentaje" required>
<option value="0" <?= $porcentaje===0?'selected':'' ?>>0 % — sin propina automática</option>
<option value="10" <?= $porcentaje===10?'selected':'' ?>>10 %</option>
</select></p>
<button type="submit">Guardar configuración</button>
<?= form_close() ?>
</section>
<?php $this->load->view('plantilla/pie'); ?>
