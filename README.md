# Gestión de negocios

Proyecto académico en PHP y CodeIgniter 3, con base MySQL/MariaDB compartida y datos separados por empresa.

La guía de esta entrega está en **[LEEME_PAGOS.md](LEEME_PAGOS.md)**: instalación nueva, actualización de bases existentes, pagos mixtos, cuentas separadas, propina automática del 10% y pruebas.

- Nueva instalación: configurar `application/config/local.php` y ejecutar `php tools/instalar.php` en una base vacía.
- Base existente: respaldo, diagnóstico con `php tools/actualizar.php`, actualización con `php tools/actualizar.php --aplicar` y nuevo diagnóstico.
- No importar el esquema completo encima de una base que ya tiene datos.
- Las contraseñas de prueba las imprime el instalador; cada instalación genera sus propias credenciales.

Requisitos de esta entrega: PHP 8.2 de 64 bits, mysqli y mbstring, Apache y MySQL/MariaDB con InnoDB. El cobro es un registro interno: no procesa tarjetas ni realiza operaciones bancarias.
