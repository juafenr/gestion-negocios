<?php
$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Proveedores',
    'seccion' => 'proveedores',
    'usuario' => $usuario
));
?>

<div class="titulo-acciones">
    <div>
        <p class="sobretitulo">ABASTECIMIENTO</p>
        <h1>Proveedores</h1>
        <p class="descripcion">
            Consulta y administra los proveedores registrados.
        </p>
    </div>

    <?php if (
        $usuario->puede('compras.gestionar') &&
        $usuario->puede('proveedores.gestionar')
    ): ?>
        <a class="boton"
            href="<?= html_escape(site_url('compras/proveedor_nuevo')) ?>">
            <img class="icono"
                src="<?= html_escape(base_url('assets/iconos/agregar.svg')) ?>"
                alt="" width="22" height="22">
            <span>Nuevo proveedor</span>
        </a>
    <?php endif; ?>
</div>

<?php if ($mensaje): ?>
    <p role="status"><?= html_escape($mensaje) ?></p>
<?php endif; ?>

<form action="<?= html_escape(site_url('proveedores')) ?>"
    method="get" class="filtros-inventario">

    <input type="search"
        name="busqueda"
        maxlength="150"
        placeholder="Buscar proveedor..."
        aria-label="Buscar proveedor"
        value="<?= html_escape($busqueda) ?>">

    <button type="submit">
        <img class="icono"
            src="<?= html_escape(base_url('assets/iconos/buscar.svg')) ?>"
            alt="" width="22" height="22">
        <span>Buscar</span>
    </button>

    <a class="boton boton-secundario limpiar-filtros"
        href="<?= html_escape(site_url('proveedores')) ?>">
        Limpiar
    </a>
</form>

<div class="tabla" role="region"
    aria-label="Listado de proveedores" tabindex="0">

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Contacto</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($proveedores as $proveedor): ?>
                <tr>
                    <td><?= html_escape($proveedor['nombre']) ?></td>
                    <td><?= html_escape($proveedor['contacto']) ?></td>
                    <td><?= html_escape($proveedor['telefono']) ?></td>
                    <td><?= html_escape($proveedor['correo']) ?></td>

                    <td>
                        <?= (int) $proveedor['activo'] === 1
                            ? 'Activo' : 'Inactivo' ?>
                    </td>

                    <td>
                        <?php if ($usuario->puede('proveedores.gestionar')): ?>
                            <a class="boton boton-secundario boton-compacto"
                                href="<?= html_escape(
                                            site_url(
                                                'proveedores/' .
                                                    (int) $proveedor['id'] .
                                                    '/editar'
                                            )
                                        ) ?>">
                                <img class="icono"
                                    src="<?= html_escape(base_url('assets/iconos/editar.svg')) ?>"
                                    alt="" width="22" height="22">
                                <span>Editar</span>
                            </a>
                        <?php else: ?>
                            Solo consulta
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($proveedores)): ?>
                <tr>
                    <td colspan="6">
                        No se encontraron proveedores.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->load->view('plantilla/pie'); ?>