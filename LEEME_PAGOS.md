# Actualización: pagos, cuentas separadas y propina

Esta entrega parte de gestion-negocios(7).zip. Conserva CodeIgniter 3.1.13 y el diseño del proyecto. Usa PHP 8.2 de 64 bits con mysqli y mbstring, y MySQL/MariaDB con tablas InnoDB.

## 1. Reemplazar los archivos de tu proyecto

1. Guarda tus cambios actuales en tu rama o haz una copia de la carpeta del proyecto.
2. Respalda la base de datos: en phpMyAdmin selecciona **tu base → Exportar → SQL → Exportar**. Guarda ese archivo fuera del repositorio.
3. Detén **Apache** desde XAMPP mientras actualizas; deja **MySQL** encendido.
4. Descomprime el ZIP fuera de `htdocs`. Dentro hay una carpeta `gestion-negocios`.
5. Copia **su contenido** dentro de `C:\xampp\htdocs\gestion-negocios`, aceptando reemplazar los archivos correspondientes. No copies la carpeta como una subcarpeta adicional.
6. Conserva tu carpeta **`.git`** y **`application/config/local.php`**. El ZIP no incluye ninguno de los dos, para no reemplazar tu historial ni tu conexión.
7. Abre la terminal PowerShell de Visual Studio Code en `C:\xampp\htdocs\gestion-negocios`.

## 2. Actualizar una base que YA tiene datos

Primero revisa lo que falta, sin modificar la base:

```powershell
& "C:\xampp\php\php.exe" tools\actualizar.php
```

Si muestra cambios pendientes y ya tienes el respaldo:

```powershell
& "C:\xampp\php\php.exe" tools\actualizar.php --aplicar
```

Comprueba el resultado repitiendo:

```powershell
& "C:\xampp\php\php.exe" tools\actualizar.php
```

El resultado final esperado es **Sin cambios pendientes**. Enciende Apache, inicia sesión y entra a **Pagos**. Si el estilo no se actualiza, pulsa `Ctrl + F5`.

**No importes `schema.sql` encima de tu base actual. No ejecutes otra vez todas las migraciones a ciegas.** El actualizador consulta las estructuras existentes y agrega las que faltan. Conserva los números de pedido que ya estén asignados y nunca reduce el contador de una empresa. No cambia contraseñas.

La herramienta reconoce la estructura de este proyecto. Si hay duplicados de insumos, relaciones inválidas, columnas inesperadas, tablas que no son InnoDB o numeración parcialmente asignada, se detiene y muestra el problema. No borra registros para resolverlo. Los cambios DDL de MySQL no forman una sola transacción: algunos cambios pueden quedar aplicados si aparece un error; conserva el respaldo y vuelve a diagnosticar después de corregirlo.

Si existen pedidos marcados como pagados en una versión anterior, pero no tienen historial de cobros, se requiere revisar esos datos. La herramienta no inventa métodos de pago ni vuelve a ponerlos como pendientes.

Para bases anteriores a los roles, la migración 001 asignaba administrador a los usuarios existentes: el actualizador mantiene esa regla SOLO para usuarios cuyo rol está vacío. Revisa esos roles si aplicas una actualización tan antigua. No restablece permisos revocados en roles ya configurados.

## 3. Instalación nueva para un compañero o evaluador

Si no tiene una base anterior:

1. Copia `application/config/local.example.php` como `application/config/local.php`.
2. Configura `db_name`, usuario, contraseña y `base_url` para su computadora. Usa un nombre de base nueva.
3. Con MySQL activo, ejecuta:

```powershell
& "C:\xampp\php\php.exe" tools\instalar.php
```

El instalador usa el `database/schema.sql` **consolidado y actualizado**, crea empresas y usuarios de prueba e imprime sus contraseñas aleatorias. Debe guardar esas contraseñas. No tiene que importar las migraciones por separado después.

Si desea crear e importar todo manualmente, puede crear una base vacía en phpMyAdmin e importar `schema.sql`, pero ese archivo por sí solo crea estructura y permisos, no empresas ni usuarios de prueba. Para obtener las cuentas de prueba automáticamente conviene usar el instalador sobre una base vacía.

## 4. Migraciones incluidas

| Archivo | Qué agrega |
| --- | --- |
| `001_agregar_rbac.sql` | Roles y permisos. |
| `002_agregar_insumos.sql` | Insumos. |
| `002_crear_pedidos.sql` | Pedidos y sus productos. |
| `003_numeracion_pedidos_empresa.sql` | Numeración independiente por empresa. |
| `004_historial_cancelaciones_pedidos.sql` | Cancelaciones y su autorización. |
| `005_inventario_avanzado.sql` | Vencimiento, unicidad de insumos y movimientos. |
| `006_productos_insumos.sql` | Relación entre productos e insumos de la misma empresa. |
| `007_pedidos_movimientos_inventario.sql` | Movimientos vinculados a pedidos y ajustes. |
| `008_pagos_cuentas.sql` | Cuentas, distribución de productos, pagos y propinas. |

Se conservaron los nombres anteriores, incluidos los dos archivos 002, para no confundir el historial del equipo. El actualizador se guía por las tablas, columnas, índices y relaciones presentes, no por asumir que una migración ya se ejecutó por su nombre. Las migraciones SQL antiguas se conservan como historial y para aplicación manual controlada.

Si sabes que tu base está exactamente al día hasta 007, sin ninguna parte de 008 aplicada, puedes importar únicamente `008_pagos_cuentas.sql` desde phpMyAdmin. Para tu caso, con varios cambios del equipo, usa preferiblemente `tools/actualizar.php`.

## 5. Cómo cobrar

1. Crea un pedido, agrega sus productos y **confírmalo**.
2. Abre **Pagos y cuentas** desde el detalle del pedido, o entra a **Pagos** desde el menú.
3. Pulsa **Preparar cuenta**. Se crea una cuenta general con todo el consumo.
4. Si paga una sola persona, conserva esa cuenta. Si cada persona paga sus productos, usa **Separar la cuenta de una persona**: escribe un nombre y las unidades que le corresponden. Repite para las demás personas. Lo restante queda en la cuenta general.
5. Antes del primer cobro puedes reiniciar la distribución si te equivocaste. Después del primer pago se bloquea la redistribución.
6. En **Registrar pago**, selecciona la cuenta y completa los importes. Deja en `0.00` los métodos no utilizados.

### Propina automática del 10%

El consumo se conserva en `pedidos.total`. La propina se calcula aparte y se suma al importe que debe pagarse. En esta entrega se aplica automáticamente a todos los pedidos cobrados; no hay selector de porcentaje ni exención.

Por ejemplo: consumo Q 100.00 + propina Q 10.00 = **Q 110.00 por pagar**.

Si se reparten Q 60 de consumo a Juan y Q 40 a Ana, sus cuentas son Q 66 y Q 44. La propina se calcula sobre el total del pedido y se distribuye proporcionalmente; cuando hay fracciones de centavo, se asignan los centavos pendientes a las cuentas con mayor resto, con desempate por su ID. Así separar cuentas no aumenta ni reduce la propina total.

Los abonos cubren primero el consumo de la cuenta y luego su propina. El historial muestra cuánto de cada cobro corresponde a propina. El pedido queda pagado cuando se cubre el consumo **y** la propina de todas las cuentas.

### Métodos de pago

- **Efectivo:** escribe el importe aplicado a la cuenta y el efectivo recibido. El sistema calcula el cambio.
- **Crédito o débito:** escribe el importe en el método correspondiente. El efectivo aplicado y recibido quedan en cero.
- **Mixto:** completa dos o tres métodos. La suma es lo que se abona a esa cuenta.
- **Abonos:** puedes registrar menos que el saldo y completar después. El estado será `parcial` mientras falte dinero.

Ejemplo mixto: total con propina Q 110, efectivo aplicado Q 40, crédito Q 70, efectivo recibido Q 50. Se registran Q 110 y el cambio es Q 10.

El programa **solo registra** que se realizó el cobro: no se conecta a un banco ni valida tarjetas. No solicita ni almacena número de tarjeta, CVV o PIN.

Las cuentas separadas distribuyen **unidades enteras** de productos. Si varias personas comparten una misma unidad, pueden aportar montos parciales a la misma cuenta; esta versión no reparte fracciones de una unidad entre cuentas nominales.

### Estados y permisos

- El estado de pago pasa de `pendiente` a `parcial` o `pagado`.
- El estado operativo (`pendiente`, `en_preparacion`, `listo`, `entregado`) es independiente: pagar no implica que el pedido ya se entregó.
- El administrador y el cajero disponen de los permisos de cobro predeterminados. El cocinero no puede abrir ni registrar pagos.
- Todas las rutas vuelven a comprobar sesión, permisos y empresa. Los identificadores enviados por el navegador no autorizan por sí solos una operación.
- Un pedido con pagos no puede cancelarse. Esta versión no incluye devoluciones, anulación de pagos, facturación fiscal ni integración bancaria.

## 6. Pruebas con XAMPP

Estas pruebas se incluyen para ejecutarlas en tu equipo. No se ejecutaron aquí porque el entorno de preparación del ZIP no dispone de PHP ni MySQL.

Primero, pruebas de montos y redondeo, sin usar la base:

```powershell
& "C:\xampp\php\php.exe" tests\pagos_unit.php
```

Después, prueba del modelo con MySQL encendido:

```powershell
& "C:\xampp\php\php.exe" tests\pagos_integracion.php
```

La segunda prueba usa las credenciales de `local.php` para crear una base temporal llamada `gn_pago_test_...`; crea sus propios datos y elimina únicamente esa base al finalizar. No modifica la base del proyecto. Necesita permiso para crear y eliminar bases (el root local de XAMPP normalmente lo tiene). Si se interrumpe a la fuerza, esa base temporal puede quedar y se podrá eliminar después verificando su nombre.

Comprueba además estos casos desde el navegador:

| Caso | Resultado esperado |
| --- | --- |
| Consumo Q 100; efectivo aplicado Q 110; recibido Q 200 | Propina Q 10, cambio Q 90, estado pagado. |
| Consumo Q 100; efectivo Q 40, crédito Q 70; recibido Q 50 | Cambio Q 10; pagado. |
| Consumos Q 60 y Q 40 separados | Cuentas Q 66 y Q 44. El pedido queda parcial hasta saldar ambas. |
| Abono Q 30 en la cuenta Q 66 | Saldo Q 36; no permite reorganizar cuentas. |
| Intento de pagar Q 67 en una cuenta de Q 66 | Rechazado sin guardar cobro. |
| Recargar después de pagar | No duplica el cobro. |
| Dos cajeros intentan cobrar el mismo saldo | El bloqueo del pedido hace que el segundo vea el saldo actualizado y rechace el exceso. |
| Usuario de empresa B abre la URL de un pedido de A | No encuentra ese pedido; no revela las cuentas. |
| Cocinero abre `/index.php/pagos` directamente | Acceso denegado. |
| Petición de pago sin token CSRF válido | Rechazada. |
| Cancelar un pedido que ya tiene un abono | Rechazado. |
| Separar productos y luego reiniciar antes de pagar | Conserva productos del pedido y permite organizar otra vez. |

## 7. Para el equipo y Git

GitHub guarda el código y los archivos SQL; **no actualiza la base local** de cada integrante. Después de bajar esta entrega, cada compañero debe ejecutar el diagnóstico/actualización sobre su propia base, o usar el instalador si empieza desde cero.

Sube `schema.sql`, las migraciones, `tools/actualizar.php`, las pruebas y los archivos del módulo con tu código. No subas `local.php`, respaldos con datos reales ni contraseñas. No necesitas mover cambios de rama para probar el ZIP: puedes trabajar en tu rama `juanfer` y después hacer el pull request hacia `main`.
