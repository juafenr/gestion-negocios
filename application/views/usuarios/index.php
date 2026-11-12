<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Usuarios', 'seccion' => 'usuarios', 'usuario' => $usuario)); ?>
<div class="titulo-acciones">
    <div>
        <p class="sobretitulo">ADMINISTRACIÓN</p>
        <h1>Usuarios</h1>
        <p class="descripcion">Estas cuentas pertenecen únicamente a tu empresa.</p>
    </div>
    <?php if ($usuario->puede('usuarios.gestionar')): ?>
        <a class="boton-enlace" href="<?= html_escape(site_url('usuarios/crear')) ?>">Crear usuario</a>
    <?php endif; ?>
</div>
<?php $this->load->view('plantilla/filtros',array('filtro_ruta'=>'usuarios','filtro_campos'=>array('busqueda'=>array('tipo'=>'search','etiqueta'=>'Nombre, usuario o correo','ejemplo'=>'Ej. Ana'),
'estado'=>array('etiqueta'=>'Estado de la cuenta','opciones'=>array(''=>'Todos los estados','activos'=>'Activos','inactivos'=>'Inactivos')),
'rol'=>array('etiqueta'=>'Rol','opciones'=>array(''=>'Todos los roles','administrador'=>'Administrador','cajero'=>'Cajero','cocinero'=>'Cocinero'))),'filtro_valores'=>$filtros,'filtro_extras'=>array())); ?>
<?php $this->load->view('plantilla/resultados', array('resultado_total'=>count($usuarios), 'resultado_mostrados'=>count($usuarios), 'resultado_pagina'=>NULL)); ?>
<div class="tabla">
    <table>
        <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($usuarios as $cuenta): ?>
            <tr>
                <td><?= html_escape($cuenta['nombre']) ?><?= (int) $cuenta['id'] === $usuario->id() ? ' (tú)' : '' ?></td>
                <td><?= html_escape($cuenta['nombre_usuario']) ?></td>
                <td><?= html_escape($cuenta['correo']) ?></td>
                <td><?= html_escape($cuenta['rol_nombre']) ?></td>
                <td><span class="estado <?= $cuenta['activo'] ? 'activo' : 'inactivo' ?>"><?= $cuenta['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="acciones">
                    <?php if ($usuario->puede('usuarios.gestionar')): ?>
                        <a class="boton boton-secundario boton-compacto" href="<?= html_escape(site_url('usuarios/' . $cuenta['id'] . '/editar')) ?>">Editar</a>
                        <?php if ((int) $cuenta['id'] !== $usuario->id()): ?>
                            <?= form_open('usuarios/' . $cuenta['id'] . '/estado') ?>
                                <button type="submit" class="boton-secundario"><?= $cuenta['activo'] ? 'Desactivar' : 'Activar' ?></button>
                            <?= form_close() ?>
                        <?php endif; ?>
                    <?php else: ?>Solo lectura<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$usuarios): ?><tr><td colspan="6"><?php $this->load->view('plantilla/vacio',array('vacio_titulo'=>array_filter($filtros)?'No hay usuarios con estos filtros.':'No hay usuarios registrados.','vacio_descripcion'=>'Prueba otro nombre, estado o rol. Solo se muestran cuentas de tu empresa.','vacio_url'=>array_filter($filtros)?site_url('usuarios'):($usuario->puede('usuarios.gestionar')?site_url('usuarios/crear'):NULL),'vacio_accion'=>array_filter($filtros)?'Limpiar filtros':'Crear usuario')); ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->load->view('plantilla/pie'); ?>
