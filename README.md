# Gestión de negocios

**Entrega actual:** [Requisitos de Juan Fernando: ventas, documentos de compra y validación](LEEME_JUAN_RF.md).


Proyecto académico en PHP y CodeIgniter 3, con base MySQL/MariaDB compartida y datos separados por empresa.

La guía de esta entrega está en **[LEEME_PAGOS.md](LEEME_PAGOS.md)**: instalación nueva, actualización de bases existentes, pagos mixtos, cuentas separadas, propina automática del 10% y pruebas.

- Nueva instalación: configurar `application/config/local.php` y ejecutar `php tools/instalar.php` en una base vacía.
- Base existente: respaldo, diagnóstico con `php tools/actualizar.php`, actualización con `php tools/actualizar.php --aplicar` y nuevo diagnóstico.
- No importar el esquema completo encima de una base que ya tiene datos.
- Las contraseñas de prueba las imprime el instalador; cada instalación genera sus propias credenciales.
- El acceso acepta nombre de usuario o correo. En bases actualizadas, revisa los nombres iniciales `usuario<ID>` en el listado de Usuarios.

Requisitos de esta entrega: PHP 8.2 de 64 bits, mysqli y mbstring, Apache y MySQL/MariaDB con InnoDB. El cobro es un registro interno: no procesa tarjetas ni realiza operaciones bancarias.

## Validaciones RF-61 y RF-62

Ver `LEEME_VALIDACIONES_RF61_RF62.md`. No hay migraciones nuevas para estas correcciones.
Ejecutar `php tests/ejecutar_validaciones.php` con MySQL activo para obtener evidencia de aceptación sobre bases temporales.

## Actualización del catálogo de productos

Después de integrar el catálogo desde `main`, las bases anteriores necesitan descripción,
disponibilidad e historial de precios (migración 014). Esto también es necesario para Pedidos.
Con un respaldo SQL verificado, detén Apache, mantén MariaDB activo y ejecuta desde la raíz
del proyecto `php tools/actualizar.php`, seguido de `php tools/actualizar.php --aplicar`.
Repite el diagnóstico hasta obtener «Sin cambios pendientes» y vuelve a iniciar Apache.
No ejecutes el instalador ni importes el esquema completo sobre datos existentes.

`php tests/validaciones_http.php` comprueba además los filtros y la paginación del catálogo,
el aislamiento por empresa, los permisos, los rechazos sin cambios de datos y el historial
de precios sin alterar precios de pedidos anteriores. Crea y elimina una base temporal;
requiere permisos CREATE/DROP DATABASE en el servidor configurado en `local.php`.
