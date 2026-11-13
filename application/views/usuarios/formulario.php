<?php $this->load->view('plantilla/cabecera', array('titulo'=>$titulo,'seccion'=>'usuarios','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url(ui_ruta_retorno('usuarios'))) ?>">← Volver a usuarios</a></p>
<div class="encabezado-formulario"><p class="sobretitulo">ADMINISTRACIÓN</p><h1><?= html_escape($titulo) ?></h1><p class="descripcion">Define los datos de acceso y los permisos de esta cuenta.</p></div>
<section class="recuadro panel-formulario">
<?php if ($datos['error'] !== ''): ?><div class="error" role="alert"><?= html_escape($datos['error']) ?></div><?php endif; ?>
<?= form_open($accion,array('class'=>'formulario-ui','data-ui-form'=>'')) ?>
<?php if (!$creando && preg_match('~/([0-9]+)/editar$~',$accion,$cuenta_edicion)): ?><?= ui_campo_edicion('usuarios',$cuenta_edicion[1]) ?><?php endif; ?>
<p class="ayuda-obligatorios">Los campos con asterisco (*) son obligatorios.</p>
<fieldset class="seccion-formulario"><legend>Datos de la persona</legend><div class="campos-formulario">
    <div class="campo"><label for="nombre">Nombre completo <span aria-hidden="true">*</span></label><input id="nombre" name="nombre" maxlength="100" required autocomplete="name" value="<?= html_escape($datos['nombre']) ?>"></div>
    <div class="campo"><label for="correo">Correo electrónico <span aria-hidden="true">*</span></label><input id="correo" name="correo" type="email" maxlength="190" required autocomplete="email" value="<?= html_escape($datos['correo']) ?>" <?= $creando ? '' : 'readonly aria-describedby="correo-ayuda"' ?>><?php if (!$creando): ?><p id="correo-ayuda" class="ayuda-campo">El correo de una cuenta existente no se modifica desde este formulario.</p><?php endif; ?></div>
</div></fieldset>
<fieldset class="seccion-formulario"><legend>Acceso y permisos</legend><div class="campos-formulario">
    <div class="campo"><label for="nombre_usuario">Nombre de usuario <span aria-hidden="true">*</span></label><input id="nombre_usuario" name="nombre_usuario" maxlength="40" minlength="3" pattern="[a-z][a-z0-9._-]{2,39}" required autocomplete="off" value="<?= html_escape($datos['nombre_usuario']) ?>" aria-describedby="ayuda-usuario"><p id="ayuda-usuario" class="ayuda-campo">Entre 3 y 40 caracteres. Empieza con una letra; puedes usar números, punto, guion o guion bajo.</p></div>
    <div class="campo"><label for="rol_id">Rol <span aria-hidden="true">*</span></label><select id="rol_id" name="rol_id" <?= $rol_editable ? 'required' : 'disabled aria-describedby="nota-rol-propio"' ?>><option value="">Selecciona un rol</option><?php foreach ($roles as $rol): ?><option value="<?= (int)$rol['id'] ?>" <?= (string)$datos['rol_id']===(string)$rol['id'] ? 'selected' : '' ?>><?= html_escape($rol['nombre']) ?></option><?php endforeach; ?></select>
    <?php if (!$rol_editable): ?><input type="hidden" name="rol_id" value="<?= html_escape($datos['rol_id']) ?>"><p id="nota-rol-propio" class="ayuda-campo">No puedes cambiar el rol de la cuenta que estás utilizando. Puedes editar tu nombre y gestionar los roles de otras cuentas.</p><?php endif; ?></div>
    <?php if ($creando): ?><div class="campo campo-completo"><label for="password">Contraseña temporal <span aria-hidden="true">*</span></label><input id="password" name="password" type="password" minlength="8" maxlength="72" required autocomplete="new-password" aria-describedby="ayuda-password"><p id="ayuda-password" class="ayuda-campo">Usa al menos 8 caracteres y compártela de forma privada con la persona que usará esta cuenta.</p></div><?php endif; ?>
</div></fieldset>
<div class="acciones-formulario"><a class="boton boton-secundario" href="<?= html_escape(site_url(ui_ruta_retorno('usuarios'))) ?>">Cancelar</a><button type="submit"><?= $creando ? 'Crear usuario' : 'Guardar cambios' ?></button></div>
<?= form_close() ?>
</section>
<?php $this->load->view('plantilla/pie'); ?>
