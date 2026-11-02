# Estado de los requisitos de Emmanuel en la rama integrada

**Corte:** 2 de noviembre de 2026 (Guatemala). **Rama de trabajo:** `RBAC-implementacion-emmanuel`. El merge commit `d386214` integra `upstream/main` hasta `521767c` conservando el trabajo personal. Se publicó en la rama personal y en `upstream/main` mediante avance normal. No se creó pull request porque el conector de GitHub rechazó esa operación (403).

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

## Integración con el equipo

- **RF-49 disponible en el push del equipo:** la nueva pantalla de proveedores permite asociar insumos desde la interfaz. `tests/compras_seleccion_http.php` comprobó el aislamiento entre empresas y el recorrido desde esa pantalla hasta la agrupación y creación de dos borradores. Los insumos sin proveedor activo permanecen pendientes.
- **Integración en `main`:** los diez RF están incluidos en `upstream/main` desde `d386214`. Las pruebas de integración se ejecutaron en la fuente y el recorrido HTTP completo también pasó desde la copia XAMPP sincronizada.
- **Manuel — RF-57 a RF-60:** estados, historial y recepción de compras llegaron en el push nuevo. La prueba de integración comprueba recepción parcial, reintento idempotente, rechazo de la misma clave con otras cantidades, recepción completa y aislamiento por empresa.

La base local actualizada informó `Sin cambios pendientes` en `tools/actualizar.php`. Las pruebas citadas pasaron con datos temporales que se eliminaron al terminar. La prueba HTTP también recorrió edición de proveedor, confirmación y envío del borrador, recepción parcial y final, reintento sin duplicar stock, CSRF, cantidades excesivas y acceso entre empresas. Se agregaron las tablas de recepción al esquema y al actualizador y se renumeró su migración como `013_recepciones_compra.sql`.
