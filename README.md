# Gestión de negocios

Proyecto del curso de Programación Orientada a Objetos.

El sistema permite iniciar sesión, administrar insumos y crear y consultar pedidos en borrador. Cada empresa accede únicamente a sus propios datos.

## Entorno utilizado

- Windows con XAMPP.
- PHP 8.2.12.
- CodeIgniter 3.1.13.
- MariaDB incluida en XAMPP, cuyo panel identifica el servicio como MySQL.
- Git para descargar el repositorio.

## 1. Preparar el proyecto

Clonar el repositorio o descargar y descomprimir su código dentro de:

```text
C:\xampp\htdocs\gestion-negocios
```

Los archivos `index.php` y las carpetas `application` y `system` deben quedar directamente dentro de esa carpeta.

Abrir la carpeta del proyecto en Visual Studio Code.

## 2. Iniciar XAMPP

Iniciar los servicios Apache y MySQL desde el panel de XAMPP.

## 3. Configurar la conexión

Desde la terminal PowerShell de VS Code, situada en la carpeta del proyecto, ejecutar:

```powershell
Copy-Item application/config/local.example.php application/config/local.php
```

Si `local.php` ya existe, revisar su contenido sin sobrescribirlo.

Abrir `application/config/local.php` y ajustar:

- `base_url`: dirección local del proyecto.
- `db_host`: dirección del servidor de base de datos.
- `db_port`: puerto de la base de datos.
- `db_name`: nombre de una base nueva o vacía.
- `db_user`: usuario de conexión.
- `db_pass`: contraseña de conexión.

La configuración de ejemplo utiliza:

```text
Dirección: http://localhost/gestion-negocios/
Servidor: 127.0.0.1
Puerto: 3306
Base: gestion_negocios
Usuario: root
Contraseña: vacía
```

Estos valores deben adaptarse si la instalación local es diferente.

El archivo `local.php` contiene configuración privada y no debe subirse a GitHub.

## 4. Crear la base de datos

Ejecutar desde la carpeta del proyecto:

```powershell
& "C:\xampp\php\php.exe" tools/instalar.php
```

El instalador utiliza `database/schema.sql` para crear las tablas y agrega empresas, usuarios y productos ficticios de prueba.

El usuario de conexión necesita permisos para crear la base, las tablas e insertar los datos.

Si la base ya contiene tablas, el instalador se detiene sin borrarlas. No eliminar una base con trabajo existente para repetir la instalación.

Para una instalación nueva mediante este comando, no importar además los archivos de `database/migrations`, porque las estructuras ya estarán creadas.

## 5. Credenciales de prueba

El instalador muestra en la terminal una contraseña aleatoria para cada cuenta. Guardar esas contraseñas antes de cerrar la terminal.

| Empresa | Correo | Rol |
|---|---|---|
| Aurora | ana@aurora.test | Administrador |
| Aurora | cajero@aurora.test | Cajero |
| Aurora | cocina@aurora.test | Cocinero |
| Roble | bruno@roble.test | Administrador |
| Roble | cajero@roble.test | Cajero |
| Roble | cocina@roble.test | Cocinero |

Las contraseñas son distintas en cada instalación. Deben usarse las mostradas en la terminal, no las de otro integrante del equipo.

## 6. Abrir el sistema

Visitar:

http://localhost/gestion-negocios/index.php/login

Iniciar sesión con una cuenta y su contraseña generada.

No abrir `index.php` con doble clic ni utilizar Live Server: Apache debe ejecutar PHP.

## 7. Comprobaciones de la entrega

Con una cuenta administradora:

1. Iniciar sesión y comprobar el nombre de la empresa.
2. Entrar a Inventario, crear un insumo y editarlo.
3. Entrar a Pedidos, crear un borrador y abrir su detalle.
4. Iniciar sesión con la otra empresa en una ventana de incógnito.
5. Comprobar que no pueda consultar los insumos ni pedidos de la primera.
6. Cerrar sesión y comprobar que las páginas privadas vuelvan a exigir acceso.

El inventario comienza vacío. Los pedidos nuevos se crean en borrador, con pago pendiente y total cero.

Agregar productos al pedido y cobrarlo están pendientes en esta versión.

## 8. Prueba automática del login

Con Apache y MySQL iniciados:

```powershell
& "C:\xampp\php\php.exe" tests/login_tenant.php
```

Introducir la URL base y las contraseñas generadas para Ana y Bruno.

Esta prueba comprueba el login y el aislamiento de productos de prueba; no sustituye las comprobaciones manuales de Inventario y Pedidos.

Si aparece un código 429, se activó el límite de intentos. Esperar 15 minutos sin intentar iniciar sesión antes de repetir.

## Actualizaciones de una instalación existente

Descargar cambios desde GitHub no actualiza automáticamente la base de datos.

Las migraciones se aplican únicamente cuando la instalación existente necesita esas modificaciones. No volver a ejecutar migraciones cuyas estructuras ya existen.

## Alcance

Esta entrega es un prototipo académico para ejecución local. Los datos y las cuentas incluidos son ficticios.