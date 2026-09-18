<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Gestión de negocios</title>
    <link rel="stylesheet" href="<?= html_escape(base_url('assets/app.css')) ?>">
    <script
        src="<?= html_escape(base_url('assets/login.js')) ?>"
        defer
    ></script>
</head>
<body>
    <header class="cabecera"><strong>Gestión de negocios</strong></header>
    <main class="caja-login">
        <h1>Iniciar sesión</h1>
        <p>Ingresa con el correo y la contraseña de tu cuenta.</p>
        <?php if ($error): ?>
            <p class="error" role="alert"><?= html_escape($error) ?></p>
        <?php endif; ?>

        <?= form_open('login', array('class' => 'formulario')) ?>
            <label for="correo">Correo electrónico</label>
            <input id="correo" name="correo" type="email" maxlength="190" autocomplete="username" required value="<?= html_escape($correo) ?>">

            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" maxlength="72" autocomplete="current-password" required>
            <button
                type="button"
                id="ver-password"
                aria-controls="password"
                aria-pressed="false"
            >
                Mostrar contraseña
            </button>

            <button type="submit">Ingresar</button>
        <?= form_close() ?>
        <p class="nota">Si necesitas una cuenta, consulta al administrador de tu empresa.</p>
    </main>
</body>
</html>
