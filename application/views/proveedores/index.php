<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Proveedores',
    'seccion' => 'proveedores',
    'usuario' => $usuario
));

$puedeGestionar = $usuario->puede('proveedores.gestionar');
?>

<!-- Título y acciones principales -->

<div class="titulo-acciones">

    <div>
        <p class="sobretitulo">ABASTECIMIENTO</p>

        <h1>Proveedores</h1>

        <p class="descripcion">
            Consulta y administra los proveedores registrados
            y los insumos que suministran.
        </p>
    </div>

    <?php if ($puedeGestionar): ?>

        <a
            class="boton"
            href="<?= html_escape(
                        site_url('compras/proveedor_nuevo')
                    ) ?>">
            <img
                class="icono"
                src="<?= html_escape(
                            base_url('assets/iconos/agregar.svg')
                        ) ?>"
                alt=""
                width="22"
                height="22">

            <span>Nuevo proveedor</span>
        </a>

    <?php endif; ?>

</div>


<!-- Mensajes de confirmación -->

<?php if (!empty($mensaje)): ?>

    <p role="status">
        <?= html_escape($mensaje) ?>
    </p>

<?php endif; ?>


<!-- Búsqueda de proveedores -->

<form
    action="<?= html_escape(site_url('proveedores')) ?>"
    method="get"
    class="filtros-inventario">

    <input
        type="search"
        id="busqueda"
        name="busqueda"
        maxlength="150"
        placeholder="Buscar proveedor..."
        aria-label="Buscar proveedor"
        value="<?= html_escape($busqueda) ?>">

    <button type="submit">

        <img
            class="icono"
            src="<?= html_escape(
                        base_url('assets/iconos/buscar.svg')
                    ) ?>"
            alt=""
            width="22"
            height="22">

        <span>Buscar</span>

    </button>

    <a
        class="boton boton-secundario limpiar-filtros"
        href="<?= html_escape(site_url('proveedores')) ?>">
        <span>Limpiar</span>
    </a>

</form>


<!-- Listado de proveedores -->

<div
    class="tabla"
    role="region"
    aria-label="Listado de proveedores"
    tabindex="0">

    <table class="tabla-proveedores">

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

                <?php
                $proveedorId = (int) $proveedor['id'];
                $activo = (int) $proveedor['activo'] === 1;
                ?>

                <tr>

                    <!-- Nombre -->

                    <td>
                        <?= html_escape($proveedor['nombre']) ?>
                    </td>


                    <!-- Persona de contacto -->

                    <td>
                        <?= html_escape($proveedor['contacto']) ?>
                    </td>


                    <!-- Teléfono -->

                    <td>
                        <?= html_escape($proveedor['telefono']) ?>
                    </td>


                    <!-- Correo electrónico -->

                    <td>
                        <?= html_escape($proveedor['correo']) ?>
                    </td>


                    <!-- Estado -->

                    <td>

                        <?php if ($activo): ?>

                            <span class="estado-stock estado-normal">
                                Activo
                            </span>

                        <?php else: ?>

                            <span class="estado-stock estado-bajo">
                                Inactivo
                            </span>

                        <?php endif; ?>

                    </td>


                    <!-- Acciones -->

                    <td class="acciones-inventario-fila">

                        <div
                            class="acciones-insumo"
                            role="group"
                            aria-label="Acciones de <?= html_escape(
                                                        $proveedor['nombre']
                                                    ) ?>">

                            <!-- Consultar insumos asociados -->

                            <a
                                class="boton boton-secundario boton-compacto"
                                href="<?= html_escape(
                                            site_url(
                                                'proveedores/' . $proveedorId
                                            )
                                        ) ?>">
                                <img
                                    class="icono"
                                    src="<?= html_escape(
                                                base_url(
                                                    'assets/iconos/inventario.svg'
                                                )
                                            ) ?>"
                                    alt=""
                                    width="22"
                                    height="22">

                                <span>Ver insumos</span>
                            </a>


                            <!-- Editar proveedor -->

                            <?php if ($puedeGestionar): ?>

                                <a
                                    class="boton boton-secundario boton-compacto"
                                    href="<?= html_escape(
                                                site_url(
                                                    'proveedores/' .
                                                        $proveedorId .
                                                        '/editar'
                                                )
                                            ) ?>">
                                    <img
                                        class="icono"
                                        src="<?= html_escape(
                                                    base_url(
                                                        'assets/iconos/editar.svg'
                                                    )
                                                ) ?>"
                                        alt=""
                                        width="22"
                                        height="22">

                                    <span>Editar</span>
                                </a>

                            <?php endif; ?>

                        </div>

                    </td>

                </tr>

            <?php endforeach; ?>


            <!-- Sin resultados -->

            <?php if (empty($proveedores)): ?>

                <tr>

                    <td colspan="6">

                        <?php if ($busqueda !== ''): ?>

                            No se encontraron proveedores que coincidan
                            con la búsqueda.

                        <?php else: ?>

                            Todavía no hay proveedores registrados.

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php $this->load->view('plantilla/pie'); ?>