<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - Gestión de negocios</title>
    <link rel="stylesheet" href="<?= html_escape(base_url('assets/app.css?v=2')) ?>">
    <script src="<?= html_escape(base_url('assets/login.js?v=2')) ?>" defer></script>
</head>
<body class="pagina-login">
    <header class="cabecera">
        <div class="marca"><span class="marca-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/negocio.svg')) ?>" alt="" width="22" height="22"></span><div><strong>Gestión de negocios</strong><p>Tu empresa, en un solo lugar</p></div></div>
    </header>
    <main class="caja-login">
        <span class="login-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/candado.svg')) ?>" alt="" width="22" height="22"></span>
        <p class="sobretitulo">BIENVENIDO</p>
        <h1>Iniciar sesión</h1>
        <p class="descripcion">Ingresa con los datos de tu cuenta.</p>
        <?php if ($error): ?>
            <p class="error" role="alert"><?= html_escape($error) ?></p>
        <?php endif; ?>
        <?= form_open('login', array('class' => 'formulario')) ?>
            <label for="correo">Correo electrónico</label>
            <input id="correo" name="correo" type="email" maxlength="190" autocomplete="username" placeholder="tu@correo.com" required value="<?= html_escape($correo) ?>">
            <label for="password">Contraseña</label>
            <div class="campo-password">
                <input id="password" name="password" type="password" maxlength="72" autocomplete="current-password" required>
                <button type="button" id="ver-password" aria-label="Mostrar contraseña" title="Mostrar contraseña" aria-controls="password" aria-pressed="false">
                    <span class="mostrar-password"><img class="icono" src="<?= html_escape(base_url('assets/iconos/ver.svg')) ?>" alt="" width="22" height="22"></span>
                    <span class="ocultar-password" hidden><img class="icono" src="<?= html_escape(base_url('assets/iconos/ocultar.svg')) ?>" alt="" width="22" height="22"></span>
                </button>
            </div>
            <button type="submit" class="boton-ingresar"><img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"><span>Ingresar</span></button>
        <?= form_close() ?>
        <p class="nota">¿Necesitas una cuenta? Consulta al administrador de tu empresa.</p>
    </main>
    <footer>Gestión de negocios · Proyecto de programación orientada a objetos</footer>
</body>
</html>
