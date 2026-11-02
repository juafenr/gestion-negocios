<?php
$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Editar proveedor',
    'seccion' => 'proveedores',
    'usuario' => $usuario
));
?>

<div class="titulo-acciones">
    <div>
        <p class="sobretitulo">PROVEEDORES</p>
        <h1>Editar proveedor</h1>
        <p class="descripcion">
            Modifica la información del proveedor.
        </p>
    </div>

    <a class="boton boton-secundario"
        href="<?= html_escape(site_url('proveedores')) ?>">
        Volver
    </a>
</div>

<section class="recuadro formulario formulario-inventario">

    <?php if ($error): ?>
        <p class="error" role="alert">
            <?= html_escape($error) ?>
        </p>
    <?php endif; ?>

    <?php if (validation_errors()): ?>
        <div class="error" role="alert">
            <?= validation_errors() ?>
        </div>
    <?php endif; ?>

    <?= form_open(
        'proveedores/' . (int) $proveedor['id'] . '/editar'
    ) ?>

    <?php
    $campos = array(
        'nombre' => array('Nombre *', 150),
        'contacto' => array('Contacto', 100),
        'telefono' => array('Teléfono', 30),
        'correo' => array('Correo', 190),
        'direccion' => array('Dirección', 255)
    );
    ?>

    <?php foreach ($campos as $campo => $info): ?>

        <label for="<?= html_escape($campo) ?>">
            <?= html_escape($info[0]) ?>
        </label>

        <input
            id="<?= html_escape($campo) ?>"
            name="<?= html_escape($campo) ?>"
            type="<?= $campo === 'correo' ? 'email' : 'text' ?>"
            maxlength="<?= (int) $info[1] ?>"
            value="<?= html_escape(
                        set_value($campo, $proveedor[$campo])
                    ) ?>"
            <?= $campo === 'nombre' ? 'required' : '' ?>>

    <?php endforeach; ?>

    <button type="submit">
        Guardar cambios
    </button>

    <?= form_close() ?>

</section>

<?php $this->load->view('plantilla/pie'); ?>