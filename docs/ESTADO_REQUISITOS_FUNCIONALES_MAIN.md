# Estado de los requisitos funcionales en `main`

**Corte:** 1 de noviembre de 2026. **Repositorio del equipo:** `juafenr/gestion-negocios`, rama `main`, commit `a138cfff1c4fc9d87a92c2a7b9b1d9a82b47d400` (verificado en GitHub). **Fuente de requisitos:** segunda entrega, páginas 2–4; 63 requisitos. Los identificadores RF-01 a RF-63 proceden de la matriz de trazabilidad del proyecto.

Esta es una revisión rápida de la implementación en código y del esquema. **Implementado** indica que existe el flujo correspondiente; no certifica que funcione de extremo a extremo en la instalación del equipo. **Parcial** indica que existe una parte del flujo o una limitación relevante. **Pendiente** indica que no se encontró el flujo solicitado. No se ejecutaron pruebas ni se verificó la aplicación de migraciones en una base de datos.

| Estado | Cantidad |
|---|---:|
| Implementado en código | 28 |
| Parcial | 10 |
| Pendiente | 25 |
| **Total** | **63** |

## Implementados en código (28)

| ID | Prioridad | Requisito | Evidencia o alcance |
|---|---:|---|---|
| RF-02 | 1 | Cerrar sesión | `Auth::salir`. |
| RF-05 | 1 | Restringir funciones según rol | Permisos y comprobaciones en controladores protegidos. |
| RF-11 | 1 | Cajero crea pedido | `Pedidos::crear` y `Pedido_model::crear`. |
| RF-12 | 1 | Agregar productos y cantidades | Alta de detalles del pedido. |
| RF-13 | 1 | Modificar o quitar productos antes de confirmar | Edición de detalles limitada a borradores. |
| RF-14 | 1 | Calcular automáticamente el total del pedido | Recalculo del total al modificar detalles. |
| RF-15 | 1 | Confirmar un pedido aunque no esté pagado | `Pedidos::confirmar`; el estado de pago permanece independiente. |
| RF-16 | 1 | Guardar fecha, hora, productos, cantidades, total y responsable | Cabecera y detalles persistidos del pedido. |
| RF-17 | 1 | Identificar la cuenta por mesa, cliente o referencia | Campo de referencia del pedido. |
| RF-19 | 2 | Cancelar con autorización y motivo | Acción de cancelación y tabla `pedido_cancelaciones`; los pedidos con pagos se rechazan hasta gestionar devolución. |
| RF-21 | 1 | Mostrar productos y cantidades del pedido al cocinero | Detalle del pedido accesible con permiso de consulta. La cola específica de cocina sigue pendiente (RF-20). |
| RF-23 | 1 | Mantener independientes el estado operativo y el de pago | Columnas separadas; el cobro actualiza `estado_pago` sin avanzar cocina. |
| RF-24 | 1 | Iniciar el pedido como pendiente de pago | Valor inicial `estado_pago = pendiente`. |
| RF-25 | 1 | Mostrar cuentas pendientes al cajero | Listado de pagos filtrable por estado. |
| RF-26 | 1 | Mostrar total, monto pagado y saldo | Resumen de cuentas y abonos. |
| RF-27 | 1 | Registrar pagos de un pedido | `Pagos::registrar` y `Pago_model::registrar`. |
| RF-28 | 1 | Marcar pagado al completar el cobro | El modelo cambia `estado_pago` a `pagado` cuando se cubre el total. |
| RF-29 | 1 | Guardar método de pago | Importes separados para efectivo, crédito y débito. |
| RF-30 | 2 | Calcular cambio en efectivo | Se guarda efectivo recibido y cambio. |
| RF-31 | 2 | Guardar usuario, fecha y hora del cobro | Tabla `pagos` con `usuario_id` y `creado_en`. |
| RF-32 | 3 | Admitir pagos parciales y mostrar saldo restante | Estado `parcial`, múltiples abonos y saldo por cuenta. |
| RF-36 | 1 | Registrar insumos | Alta de insumos. |
| RF-37 | 1 | Mostrar cantidad disponible por insumo | `cantidad_actual` en el listado. |
| RF-38 | 1 | Aumentar o disminuir manualmente existencias | Entradas y salidas mediante movimientos. |
| RF-39 | 1 | Registrar fecha, cantidad, motivo y responsable del movimiento | Tabla y modelo de movimientos de inventario. |
| RF-40 | 2 | Definir cantidad mínima por insumo | `stock_minimo` editable. |
| RF-41 | 2 | Alertar cuando el stock llegue al mínimo o se agote | Indicador de stock bajo que incluye cero. |
| RF-42 | 2 | Mostrar resumen de existencias | Resumen y listado del inventario. |

## Parciales (10)

| ID | Prioridad | Requisito | Qué existe y qué falta |
|---|---:|---|---|
| RF-01 | 1 | Iniciar sesión con nombre de usuario y contraseña | Funciona con **correo** y contraseña; no con nombre de usuario. Se debe implementar el identificador pedido o acordar formalmente la equivalencia. |
| RF-04 | 1 | Asignar rol administrador, cajero o cocinero | Existen catálogo de roles y relación usuario–rol, pero `main` no tiene gestión administrativa para asignarlos. |
| RF-07 | 1 | Registrar productos o platillos | Se puede crear un producto desde un insumo; falta alta general de platillos/productos con sus campos. |
| RF-18 | 2 | Consultar historial de pedidos | El listado muestra solo los últimos 50, sin navegación completa ni filtros suficientes. |
| RF-43 | 2 | Clasificar inventario como disponible, bajo o agotado | Cero queda dentro de «bajo»; falta la categoría «agotado» diferenciada. |
| RF-44 | 2 | Filtrar insumos bajos o agotados | El filtro «bajo» incluye agotados; falta filtro independiente para agotados. |
| RF-45 | 2 | Consultar historial de movimientos | Existe historial por insumo, limitado a 100 registros y sin paginación para recuperar los anteriores. |
| RF-61 | 1 | Validar campos obligatorios antes de guardar | Hay validación en los módulos existentes; debe completarse y comprobarse en los flujos pendientes. |
| RF-62 | 1 | Rechazar precios, cantidades y pagos negativos | Hay validaciones en pagos, inventario y pedidos; falta cobertura uniforme, incluido el futuro catálogo general de productos. |
| RF-63 | 2 | Auditar cancelaciones, cambios de precio y movimientos | Cancelaciones y movimientos se registran; falta auditoría de cambios de precio. |

## Pendientes (25)

| ID | Prioridad | Requisito | Trabajo faltante |
|---|---:|---|---|
| RF-03 | 1 | Administrador registra usuarios | Integrar alta administrativa de usuarios en `main`. |
| RF-06 | 2 | Activar o desactivar usuarios | Integrar acción de cambio de estado en `main`. |
| RF-08 | 1 | Modificar nombre, descripción y precio de productos | Campos y formulario de edición; registrar cambios de precio. |
| RF-09 | 1 | Marcar productos disponibles o no disponibles | Estado de disponibilidad y validación al añadirlos a pedidos. |
| RF-10 | 2 | Buscar productos por nombre | Búsqueda del catálogo de productos. |
| RF-20 | 1 | Mostrar pedidos confirmados pendientes al cocinero | Cola específica de cocina filtrada por empresa y estado. |
| RF-22 | 1 | Cambiar entre pendiente, preparación, listo y entregado | Acciones y reglas de transición para cocina. |
| RF-33 | 1 | Registrar una venta por cada pedido pagado | Registro de venta único ligado al cierre del cobro. Un pago no equivale aún a una venta registrada. |
| RF-34 | 2 | Consultar historial de ventas | Módulo de consulta para administrador. |
| RF-35 | 2 | Mostrar resumen de ventas por fecha | Agregación de ventas pagadas por período. |
| RF-46 | 3 | Sugerir cantidad de compra | Regla de cálculo y presentación de la sugerencia. |
| RF-47 | 2 | Registrar proveedor y contactos | Tabla, modelo y formulario. |
| RF-48 | 2 | Modificar y consultar proveedor | Listado, búsqueda y edición. |
| RF-49 | 2 | Asociar insumo y proveedor | Relación y validación por empresa. |
| RF-50 | 2 | Seleccionar insumos del resumen para solicitud | Selección desde inventario. |
| RF-51 | 2 | Indicar cantidad deseada por insumo | Detalles de solicitud con cantidad y unidad. |
| RF-52 | 2 | Agrupar insumos automáticamente por proveedor | Agrupación según la asociación insumo–proveedor. |
| RF-53 | 2 | Crear solicitud independiente por proveedor | Cabecera y detalles para cada proveedor. |
| RF-54 | 2 | Guardar número, fecha, proveedor, líneas y observaciones | Datos completos de solicitud. |
| RF-55 | 2 | Revisar y modificar antes de confirmar | Borrador editable y confirmación. |
| RF-56 | 2 | Copiar o exportar resumen para proveedor | Salida copiable o descargable. |
| RF-57 | 2 | Manejar estados de solicitud de compra | Borrador, confirmada, enviada, recibida y cancelada. |
| RF-58 | 2 | Consultar historial de solicitudes por proveedor | Historial y filtros. |
| RF-59 | 2 | Aumentar existencias al recibir compra | Recepción y movimiento de stock en una transacción. |
| RF-60 | 3 | Permitir recepción parcial de compras | Cantidades recibidas por línea y saldo pendiente. |

## Cambios ya presentes en otra rama

La rama `RBAC-implementacion-emmanuel` contiene gestión de usuarios (RF-03, RF-04 y RF-06) y flujo de cocina (RF-20 y RF-22). **Esos requisitos siguen parciales o pendientes en la `main` del equipo hasta integrarlos y resolver compatibilidad con sus cambios de pedidos, pagos e inventario.**

RF-64 (envío automático) y RF-65 (descuento por recetas) aparecen en un listado separado, pero **no** entre los 63 requisitos confirmados de la segunda entrega; por eso no se incluyen en los conteos.

### Referencias de código

- `application/controllers/{Auth,Pedidos,Pagos,Inventario,Productos}.php`
- `application/models/{Pedido_model,Pago_model,Insumo_model,Movimientos_inventario_model,Producto_model,Usuario_model}.php`
- `database/migrations/004_historial_cancelaciones_pedidos.sql` a `008_pagos_cuentas.sql`
- `tests/pagos_unit.php` y `tests/pagos_integracion.php`

Las rutas anteriores corresponden al árbol de `upstream/main` en el commit indicado, no necesariamente a los archivos de la rama actualmente abierta en el directorio de trabajo.
