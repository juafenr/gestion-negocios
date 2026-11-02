# Estado de los requisitos de Emmanuel en la rama integrada

**Corte:** 2 de noviembre de 2026 (Guatemala). **Rama de trabajo:** `RBAC-implementacion-emmanuel`. La integración del push `upstream/main` `3f7c318` quedó en el commit `fa0b26c` y se publicó en `backup/RBAC-implementacion-emmanuel`. Este estado describe la rama personal; el trabajo aún no está en `main` del equipo.

| RF | Alcance implementado en esta rama | Verificación |
|---|---|---|
| RF-01 | Inicio de sesión con nombre de usuario y contraseña; el correo también se admite como identificador. | `tests/usuarios_http.php` comprueba el acceso de cuentas creadas y renombradas. |
| RF-03 | Alta administrativa de usuarios de la empresa autenticada. | `tests/usuarios_http.php`. |
| RF-04 | Asignación y edición de los roles administrador, cajero y cocinero, con protección del rol propio. | `tests/usuarios_http.php` y `tests/rbac_database.php`, actualizada para los 23 permisos integrados. |
| RF-06 | Activación y desactivación de usuarios, sin permitir desactivar la cuenta propia. | `tests/usuarios_http.php`. |
| RF-20 | Cola de pedidos confirmados y activos para cocina, separada por empresa. | `tests/cocina_http.php`. |
| RF-22 | Transiciones válidas `pendiente` → `en_preparacion` → `listo` → `entregado`, con eventos y responsables. | `tests/cocina_http.php`; el estado coincide con pagos y cancelaciones. |
| RF-50 | Selección de insumos activos directamente desde el resumen de Inventario y desde Compras. | `tests/compras_seleccion_http.php` comprueba envío desde Inventario, CSRF y aislamiento por empresa. |
| RF-51 | Cantidad positiva por insumo, hasta dos decimales, conservada en la sesión y en el detalle del borrador. | `tests/compras_seleccion_http.php`. |
| RF-52 | Agrupación automática de los insumos seleccionados por `insumos.proveedor_id`; los no asociados quedan pendientes. | `tests/compras_seleccion_http.php` crea dos grupos de proveedores distintos. |
| RF-53 | Un borrador independiente por proveedor mediante `Solicitud_compra_model::crear_borrador()`, con claves estables por grupo y manejo de fallos parciales. | `tests/compras_seleccion_http.php` comprueba dos documentos, fallo posterior y reintento sin duplicar el primero. |

## Dependencias pendientes del equipo

- **Andrés — RF-49:** falta una pantalla para asignar y editar `insumos.proveedor_id`. La columna, la clave foránea y la creación mínima de proveedores ya están integradas. RF-52 y RF-53 funcionan cuando la asociación existe; su recorrido normal desde la interfaz queda pendiente de ese módulo. Los insumos sin proveedor activo no se asignan a uno inventado ni se guardan automáticamente.
- **Integración en `main`:** los diez RF están publicados en la rama personal. Antes de incorporarlos a la rama del equipo hay que revisar el diff y repetir el recorrido conjunto con las entregas posteriores de Andrés, Juanfer y Manuel. Los permisos de ventas y compras ya se comprobaron en la base local.
- **Manuel — RF-57 a RF-60:** estados posteriores y recepción de compras. Dependen de los documentos creados, pero no bloquean RF-50 a RF-53.

La base local actualizada informó `Sin cambios pendientes` en `tools/actualizar.php`. Las pruebas citadas pasaron con datos temporales que se eliminaron al terminar.
