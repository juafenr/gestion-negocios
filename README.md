# Gestión de negocios

Aplicación web académica para administrar la operación de pequeños negocios. Reúne catálogo de productos, pedidos, cocina, cobros, ventas, inventario, proveedores y compras en una instalación con datos separados por empresa.

Está desarrollada con **PHP 8.2**, **CodeIgniter 3.1.13** y **MySQL/MariaDB**. El cobro es un registro interno: la aplicación no procesa tarjetas ni se conecta a bancos.

## Funcionalidades

| Área | Funciones principales |
| --- | --- |
| Acceso | Inicio de sesión por nombre de usuario o correo; roles de administrador, cajero y cocinero; permisos por acción y separación de datos por empresa. |
| Productos y pedidos | Alta, edición, búsqueda y disponibilidad de productos; pedidos con productos, cantidades, confirmación, historial y cancelación con motivo. |
| Cocina y pagos | Cola de pedidos y cambios de estado; cuentas separadas, abonos, pagos mixtos, cambio en efectivo y propina configurable por empresa (0 % inicial o 10 %). |
| Ventas | Registro único al completar el cobro, historial y resumen por fecha. |
| Inventario | Insumos, existencias, mínimos, entradas, salidas, ajustes, alertas e historial de movimientos. |
| Proveedores y compras | Proveedores asociados a insumos, solicitudes agrupadas, revisión, exportación TXT, estados y recepción parcial o completa con actualización de existencias. |

Las rutas privadas comprueban sesión, permisos y empresa en el servidor. Los formularios usan protección CSRF y validación de datos; el historial conserva cancelaciones, movimientos de inventario y cambios de precio.

El administrador ajusta la propina en **Configuración**. El cambio se aplica a pedidos nuevos; cada pedido conserva su porcentaje para que los cobros históricos no varíen. En **Pedidos → Pedido rápido**, el cajero puede crear y confirmar un pedido de un producto en una pantalla y pasar directamente al cobro. Los pedidos con varios productos siguen usando el flujo normal.

## Requisitos

- PHP **8.2.35 x64** recomendado para la instalación local del equipo. Si Apache carga PHP como módulo en Windows, se necesita la compilación **Thread Safe**. Extensiones `mysqli`, `mbstring`, `curl` y `openssl`; las pruebas HTTP necesitan además `proc_open`.
- Apache 2.4 y MySQL/MariaDB con tablas InnoDB.
- Una base de datos local a la que el usuario configurado pueda crear tablas. Para las pruebas integradas se necesitan permisos de crear y eliminar **bases temporales**.
- XAMPP es la instalación local utilizada por el equipo. Las rutas de ejemplo siguientes suponen `C:\xampp\htdocs\gestion-negocios`.

## Instalación nueva

1. Copia este repositorio en `C:\xampp\htdocs\gestion-negocios` y enciende **MariaDB/MySQL** desde XAMPP.
2. Copia el archivo de ejemplo y ajusta `base_url`, host, puerto, nombre de base y credenciales de tu entorno:

   ```powershell
   Set-Location 'C:\xampp\htdocs\gestion-negocios'
   Copy-Item 'application\config\local.example.php' 'application\config\local.php'
   ```

   `application/config/local.php` está excluido de Git. No publiques su contenido ni las contraseñas que genere el instalador.
3. Usa una base **vacía** y ejecuta desde la raíz del proyecto:

   ```powershell
   & 'C:\xampp\php\php.exe' tools\instalar.php
   ```

   El instalador crea el esquema, dos empresas de demostración y cuentas para los tres roles. Muestra contraseñas aleatorias **una sola vez**: guárdalas fuera del repositorio. Si la base ya contiene tablas, se detiene sin borrarlas.
4. Enciende **Apache** y abre `http://localhost/gestion-negocios/index.php/login` (o la dirección que configuraste en `base_url`).

## Actualizar una instalación existente

1. Respalda la base de datos y conserva `application/config/local.php`.
2. Actualiza los archivos del proyecto. Detén Apache y deja MariaDB/MySQL encendido.
3. Ejecuta el diagnóstico, aplica los cambios pendientes y repite el diagnóstico:

   ```powershell
   Set-Location 'C:\xampp\htdocs\gestion-negocios'
   & 'C:\xampp\php\php.exe' tools\actualizar.php
   & 'C:\xampp\php\php.exe' tools\actualizar.php --aplicar
   & 'C:\xampp\php\php.exe' tools\actualizar.php
   ```

   El resultado final debe decir **«Sin cambios pendientes»**. Si el primer diagnóstico ya lo dice, omite `--aplicar`.
4. Inicia Apache y comprueba el acceso y los módulos que utilizas.

**No importes `database/schema.sql` sobre una base que contiene datos.** El instalador usa ese esquema para bases nuevas; `tools/actualizar.php` adapta las existentes. Las operaciones DDL de MySQL no forman una única transacción, por lo que debes conservar el respaldo hasta completar la revisión.

## Pruebas

Con MariaDB/MySQL activo y `local.php` configurado, ejecuta:

```powershell
Set-Location 'C:\xampp\htdocs\gestion-negocios'
& 'C:\xampp\php\php.exe' tests\ejecutar_validaciones.php
```

La suite comprueba sintaxis PHP, reglas de negocio, integración de modelos y solicitudes HTTP reales. Usa bases y una copia del código temporales; guarda el resultado en `test-results/`, carpeta ignorada por Git. El cierre correcto muestra **`APROBADO: sintaxis, reglas, integración de modelos y aceptación HTTP.`** Si la ejecución se interrumpe, revisa si quedó alguna base temporal antes de repetirla.

Para revisar otros recorridos HTTP sobre la instalación local con Apache activo:

```powershell
& 'C:\xampp\php\php.exe' tests\usuarios_http.php
& 'C:\xampp\php\php.exe' tests\cocina_http.php
& 'C:\xampp\php\php.exe' tests\compras_seleccion_http.php
```

Estas pruebas crean registros de comprobación y los eliminan al finalizar correctamente. Revisa su salida si alguna falla. La suite automatizada no sustituye una revisión visual de los formularios y mensajes con los usuarios del proyecto.

## Desarrollo en equipo

Trabaja los cambios en una rama, revisa el diff y ejecuta las pruebas relacionadas antes de integrarlos. Mantén fuera de Git `application/config/local.php`, contraseñas, respaldos de bases de datos y los archivos de `test-results/`. Cuando agregues un módulo o una regla nueva, actualiza su prueba y estas instrucciones si cambia la instalación o el uso.

## Estructura del repositorio

| Ruta | Contenido |
| --- | --- |
| `application/controllers/`, `models/`, `views/` | Controladores, reglas de persistencia e interfaz de CodeIgniter. |
| `application/config/local.example.php` | Plantilla de configuración local. |
| `assets/` | Estilos y JavaScript de la interfaz. |
| `database/schema.sql`, `database/migrations/` | Esquema de instalación nueva e historial SQL. |
| `tools/instalar.php`, `tools/actualizar.php` | Instalación y actualización de la base. |
| `tests/` | Pruebas unitarias, de integración y HTTP. |
| `system/` | Código de CodeIgniter 3.1.13. |

## Seguridad y alcance

- Usa una instalación de prueba para las cuentas que crea `tools/instalar.php`; cambia el esquema de acceso y las credenciales antes de cualquier despliegue público.
- No introduzcas números de tarjeta, CVV ni PIN en los formularios de cobro: la aplicación solo registra métodos e importes.
- Mantén PHP y el servidor actualizados. PHP 8.2 recibe [soporte de seguridad hasta el 31 de diciembre de 2026](https://www.php.net/supported-versions.php); después habrá que migrar a una rama compatible y con soporte.

El archivo [`license.txt`](license.txt) contiene la licencia MIT de **CodeIgniter Foundation** para el framework incluido. No establece por sí solo una licencia para el código original de este proyecto académico.
