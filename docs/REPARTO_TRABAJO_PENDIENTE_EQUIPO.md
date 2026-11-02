# Propuesta de reparto del trabajo funcional pendiente

**Corte:** 1 de noviembre de 2026. **Base:** `juafenr/gestion-negocios`, `main` en `a138cff`, y avances de Emmanuel en `RBAC-implementacion-emmanuel` hasta `c0ec388`. **Alcance:** los 10 requisitos parciales y 25 pendientes del [estado de los 63 requisitos](ESTADO_REQUISITOS_FUNCIONALES_MAIN.md). Es una propuesta de responsabilidades para que el equipo la acuerde; no atribuye trabajo futuro como ya realizado.

## Criterio de reparto

La carga se reparte por **esfuerzo aproximado y dependencias**, no por número de RF. Se aprovecha el trabajo ya realizado: Emmanuel tiene usuarios y cocina en su rama; Andrés avanzó confirmación/cancelación de pedidos; Manuel, inventario; Juan Fernando, pagos. Cada bloque incluye modelo, interfaz, permisos, migración cuando corresponda y una prueba de aceptación del flujo. Las estimaciones son orientativas, no un compromiso de horas.

| Responsable | Trabajo asignado | RF principales | Esfuerzo estimado |
|---|---|---|---:|
| **Emmanuel Alejandro Montúfar Pinto** | Integrar su gestión de usuarios y cocina en `main`, resolver el identificador de acceso y crear la base de solicitudes de compra: seleccionar insumos, indicar cantidades, agrupar por proveedor y generar una solicitud por grupo. | 01, 03, 04, 06, 20, 22, 50–53 | **24–36 h** |
| **Andrés Martínez Torres** | Completar catálogo de productos, disponibilidad y búsqueda; registrar proveedores y asociarlos a insumos; auditar cambios de precio. | 07–10, 47–49, 63 | **24–36 h** |
| **Juan Fernando Santos Mérida** | Crear venta única al completar el pago, historial y resumen de ventas; completar datos y salida de solicitudes de compra; coordinar la validación transversal de campos y valores. | 33–35, 54–56, 61–62 | **24–36 h** |
| **Manuel Alexander Tziboy Catún** | Completar historial de pedidos y de inventario, distinguir y filtrar agotados, sugerir compra; implementar estados e historial de solicitudes y recepción total/parcial con movimientos de inventario. | 18, 43–46, 57–60 | **24–36 h** |

**Los 35 RF parciales o pendientes tienen un responsable principal.** RF-61 y RF-62 requieren que cada persona valide sus propios formularios, modelos y rutas; Juan Fernando coordina la revisión final y cubre los huecos de los módulos ya existentes.

## Entregables concretos por persona

### Emmanuel — usuarios, cocina e inicio de compras

1. Integrar RF-03, RF-04 y RF-06 desde su rama, conservando los cambios más recientes de `main`. Verificar alta, edición, rol, activación, desactivación y aislamiento por empresa.
2. Integrar RF-20 y RF-22. Conciliar los nombres de estado de cocina: la rama usa `preparacion` y `main` ya emplea `en_preparacion` en pagos/cancelación. Acordar un único valor y migrar los datos existentes si hace falta; comprobar que el cobro funciona en todos los estados permitidos.
3. Resolver RF-01: implementar un nombre de usuario único para acceso **o** documentar la aprobación del equipo para usar correo como identificador y actualizar el requisito. No marcarlo cumplido sin esa decisión.
4. Implementar RF-50–53: selección de insumos, cantidad deseada y agrupación por proveedor, con una solicitud independiente para cada proveedor. Coordinar el esquema con Andrés y Juan Fernando antes de escribir las migraciones de compras.

### Andrés — productos y proveedores

1. Completar RF-07–10: alta general de productos/platillos, descripción, edición de precio y nombre, disponibilidad y búsqueda. Un producto no disponible debe rechazarse también si se envía su ID por POST al pedido.
2. Implementar RF-47–49: proveedor y contactos por empresa, listado/edición y asociación con insumos. Definir qué ocurre con insumos sin proveedor para que el flujo de compras no los mezcle ni los pierda.
3. Completar RF-63: registrar cada cambio de precio con actor, fecha, producto y valores anterior/nuevo. Comprobar que la auditoría existente de cancelaciones y movimientos sigue consultable.

### Juan Fernando — ventas, documento de compra y validación

1. Implementar RF-33–35 sobre su módulo de pagos: una sola venta por pedido pagado, historial para administrador y resumen por fecha. La creación de venta debe ocurrir en la misma transacción que cierra el cobro, incluida la ruta de pago parcial que termina de cubrir el saldo.
2. Implementar RF-54–56 con Emmanuel: número, fecha, proveedor, líneas y observaciones de solicitud; revisión/edición del borrador y vista copiable o exportable para el proveedor. Emmanuel prepara la creación agrupada; Juan Fernando toma la persistencia documental y la salida.
3. Coordinar RF-61–62: lista de campos obligatorios y rechazos de negativos/rangos, con pruebas en productos, compras, ventas y los módulos existentes. Cada responsable corrige las validaciones de su propio módulo.

### Manuel — inventario, historial y recepción de compras

1. Completar RF-18: historial de pedidos paginado o filtrable, sin perder los pedidos anteriores al límite actual de 50.
2. Completar RF-43–45: mostrar «normal», «bajo» y «agotado» por separado, filtrar agotados y paginar el historial de movimientos para consultar registros anteriores al límite de 100.
3. Implementar RF-46: sugerencia de compra con fórmula explícita y cantidades no negativas; prioridad 3, después de los bloqueos de prioridad 1 y 2.
4. Implementar RF-57–60: transiciones válidas de solicitudes, historial por proveedor, recepción que aumenta existencias una sola vez y recepción parcial por línea. Usar los movimientos de inventario existentes y una transacción para que solicitud y stock queden sincronizados.

## Orden de integración propuesto

1. **Acordar contratos compartidos:** nombre del estado de cocina, identificador de acceso, tablas de proveedores/solicitudes y regla de venta. Documentar estas decisiones antes de desarrollar flujos que dependan de ellas.
2. **Integrar los avances existentes de Emmanuel** en una rama de integración actualizada con `main`, con pruebas de usuarios, cocina, cancelación y pagos. No mezclar las migraciones `004` y `005` de ambas ramas solo por su número: tienen propósitos diferentes.
3. **Andrés entrega productos y proveedores.** La asociación insumo–proveedor desbloquea la agrupación de solicitudes. El catálogo puede avanzar en paralelo con la integración.
4. **Emmanuel y Juan Fernando construyen compras** sobre un esquema compartido: Emmanuel crea y agrupa; Juan Fernando completa el documento, revisión y exportación.
5. **Manuel agrega estados y recepción** cuando la solicitud ya tenga cabecera y detalles. En paralelo puede completar los historiales y filtros de inventario/pedidos.
6. **Juan Fernando integra ventas** sobre pagos y coordina una prueba final de validación. El equipo ejecuta un recorrido conjunto: producto → pedido → cocina → pago → venta y proveedor → solicitud → recepción → movimiento.

Cada bloque se entrega mediante PR pequeño y revisable, con migración aplicable tanto en instalación nueva como existente, pruebas del flujo y evidencia de que no rompe permisos ni datos de otra empresa. El esfuerzo se vuelve a ajustar si la integración revela conflictos mayores.
