# RF-61 y RF-62: validación y aceptación

## Verificación local del 12 de noviembre de 2026

En `upstream/main` `80bd32b`, la suite `php tests/ejecutar_validaciones.php` terminó con
`APROBADO: sintaxis, reglas, integración de modelos y aceptación HTTP` usando PHP 8.2.12
y MariaDB 10.4.32 de XAMPP. La suite creó y eliminó sus bases temporales. El registro local
está en `test-results/validaciones-ejecucion.log` y los casos en
`test-results/validaciones-http.json` (archivos ignorados por Git).

La base local de demostración `gestion_rbac_visual_demo` recibió la migración del catálogo
tras un respaldo SQL; el diagnóstico posterior indicó `Sin cambios pendientes`. Una
revisión visual local del catálogo comprobó alta, edición, filtros, mensajes de error y
el historial de precios. También aprobaron `tests/cocina_http.php` y
`tests/compras_seleccion_http.php` desde la copia de XAMPP, con limpieza de sus datos
temporales. Sigue correspondiendo al equipo validar la experiencia con usuarios finales.

Estado histórico al 2026-11-02: reglas revisadas sobre `gestion-negocios(10).zip`, que ya incluía usuarios, cocina, proveedores, selección agrupada y recepciones. La suite todavía no se había ejecutado por falta de PHP/MySQL en ese entorno. Las pruebas previas del equipo se conservan como antecedente; la verificación local posterior se documenta arriba.

## Requisitos de Juan Fernando

| RF | Implementación entregada | Aceptación |
| --- | --- | --- |
| 33 | Venta única al completar todos los cobros; misma transacción que el cierre. Incluye importe cero. | Un abono no crea venta; el pago final crea una; reenvío no duplica; fallo al insertar venta deshace el último pago. |
| 34 | Historial y detalle con permiso `ventas.historial`, exclusivo del administrador por defecto. | Paginación recupera ventas antiguas; otra empresa no puede consultar IDs ajenos; cajero/cocinero no abren el historial sin permiso. |
| 35 | Filtro inclusivo por fechas, totales del período y resumen diario paginado. | Q 100 consumo + Q 10 propina son Q 110 cobrados; un período sin ventas devuelve cero; abonos aún abiertos no se cuentan como ventas. |
| 54 | Cabecera numerada por empresa, fecha, proveedor y copia de datos, líneas y observaciones. | Empresa A y B pueden tener solicitud #1; proveedor/insumo ajeno se rechazan; el documento conserva sus nombres históricos. |
| 55 | Revisión, edición del borrador, versión de edición y confirmación. | Una versión vieja no sobrescribe la nueva; después de confirmar, POST directo de edición falla; confirmar no altera stock. |
| 56 | Documento copiable y descarga TXT autorizada y aislada por empresa. | El TXT incluye todas las líneas/observaciones, incluso tildes y saltos de línea; texto con HTML no se ejecuta en la vista; otra empresa recibe 404. |
| 61 | Validación compartida de tipos, campos obligatorios, longitudes y fechas; refuerzo en módulos existentes. | Rechazar vacíos, espacios, arreglos en campos simples, fechas imposibles y cantidades omitidas. |
| 62 | Formato decimal estricto, límites y rechazo de negativos; cálculo exacto de movimientos. | Rechazar negativos, notación científica, coma decimal y más de dos decimales sin redondear silenciosamente. |

## Reglas por módulo

| Campo/operación | Regla del servidor |
| --- | --- |
| Insumo: nombre | Obligatorio tras quitar espacios, hasta 150 caracteres. |
| Insumo: cantidad inicial | Obligatoria al crear; 0 a 99999999.99, máximo dos decimales. |
| Stock mínimo | Obligatorio; mismo rango y precisión. |
| Unidad | Solo kg, g, mg, l, ml o unidad. |
| Vencimiento | Opcional; fecha real con formato YYYY-MM-DD. Se conserva al abrir edición. |
| Movimiento | Tipo entrada/salida/ajuste; motivo obligatorio hasta 255 caracteres. Entrada/salida mayores que cero; ajuste admite cero. Resultado de existencias dentro de rango, nunca negativo. |
| Producto desde insumo | Insumo activo de la empresa, SKU obligatorio hasta 40 caracteres, precio entre 0 y 99999999.99 con dos decimales. SKU e insumo no pueden duplicar vínculos existentes. |
| Pedido | Referencia obligatoria hasta 100 caracteres; cantidad entera entre 1 y 999. Solo borrador editable. |
| Pago | Efectivo/crédito/débito/recibido decimales no negativos; suma positiva y no mayor al saldo. Recibido cubre el efectivo aplicado; sin efectivo aplicado, recibido debe ser cero. |
| Venta | No hay formulario de importe: se deriva de los cobros confirmados. Rango de fechas válido, fecha inicial no posterior a la final. |
| Proveedor de apoyo | Nombre obligatorio hasta 150; contacto 100, teléfono 30, correo 190 con formato válido si se indica, dirección 255. Nombre único por empresa. |
| Solicitud | Proveedor activo y de la empresa; fecha válida; observaciones opcionales hasta 1000. |
| Líneas de solicitud | De 1 a 100, sin insumos repetidos; insumos activos y de la empresa; cantidad mayor que cero hasta 99999999.99. Si existe asociación con proveedor, debe coincidir. |

Los textos se escapan al mostrarlos; las consultas usan parámetros. Los campos empresa, usuario responsable, precio de pedido, totales, propina, saldo y números de documento no se aceptan como valores autoritativos enviados por el cliente.

## Pruebas incluidas

- `tests/pagos_unit.php`: importes y reparto de propinas.
- `tests/validacion_negocio_unit.php`: formatos, límites, obligatorios y fechas, sin base.
- `tests/rf_juan_integracion.php`: pagos, ventas, rollback, conciliación y documentos mediante los modelos reales y un adaptador mysqli, en una base temporal. No es una prueba completa de HTTP ni del controlador.

En navegador comprobar sesiones, CSRF, permisos de administrador/cajero/cocinero, empresas A/B, rutas de exportación y formularios con POST manipulado. Abrir dos pestañas del mismo borrador: guardar una, intentar guardar/confirmar la otra; debe pedir revisar la versión actual. Con dos cajeros sobre el mismo saldo, el bloqueo del pedido y la restricción única deben permitir solo una venta y rechazar el exceso de cobro.

## Cobertura transversal actualizada

| Módulo | Casos automatizados de RF-61/RF-62 | Evidencia prevista |
| --- | --- | --- |
| Login y acceso | Campos vacíos/anidados, sesión requerida, CSRF sin token, restricciones por rol. | `validaciones_http.php` |
| Usuarios | Campos obligatorios, arreglos, contraseña solo espacios/larga, correo inválido, rol inexistente, duplicados, alta válida y edición inválida. Nulos en contraseña se comprueban en la regla unitaria; CI limpia caracteres de control al recibir HTTP. | Unitarias + HTTP |
| Proveedores | Alta/edición con arreglos en cada campo, nombre vacío, correo inválido, contactos opcionales, asociación de insumo ajeno. | HTTP |
| Inventario | Campos faltantes/anidados; negativos, notación científica, coma, precisión y desbordamiento; fechas inválidas; existencia cero; movimiento con motivo vacío o salida excesiva. | Unitarias + HTTP |
| Productos disponibles en esta versión | Código obligatorio, precio no negativo y precio cero al habilitar un insumo. | HTTP |
| Pedidos | Referencia vacía, espacios o arreglo; cantidades negativas/cero/fraccionarias/excesivas; producto ajeno; creación y confirmación válidas. | HTTP |
| Cocina | Estado anidado, salto de transición rechazado sin cambios; transición válida. | HTTP |
| Pagos | Métodos omitidos/anidados/negativos/con precisión excesiva, recibido insuficiente, cobro superior al saldo, pago válido y repetido. | Unitarias, modelos y HTTP |
| Ventas | Fechas imposibles/rango invertido; venta única al completar cobro. | Modelos + HTTP |
| Documentos de compra | Campos faltantes, cantidades inválidas, proveedor/insumo ajeno, líneas duplicadas, versión desactualizada, revisión obligatoria. | Modelos + HTTP |
| Compras agrupadas | Negativos, arreglos, insumo ajeno, identificadores equivalentes duplicados; fecha inválida; generación válida y reintento. | HTTP |
| Estados y recepciones | Motivo de cancelación obligatorio; versión, cantidades inválidas/cero/exceso, duplicados, unidad distinta; recepción parcial/final y repetida sin duplicar stock. | Modelos + HTTP |

Las solicitudes inválidas se prueban comparando todos los registros de negocio antes y después; no basta con que la página muestre un mensaje. Los intentos de autenticación se excluyen de esa comparación porque son un registro de seguridad.

## Ejecutar y registrar aceptación

```powershell
& "C:\xampp\php\php.exe" tests\ejecutar_validaciones.php
```

Ver `LEEME_VALIDACIONES_RF61_RF62.md`. El ejecutor utiliza bases y una copia temporal; no cambia la base real. La ejecución HTTP no necesita Apache, pero sí MySQL.

| Evidencia | Estado de esta entrega | Responsable/fecha/resultado real |
| --- | --- | --- |
| Revisión estática de archivos y referencias | Realizada al preparar el ZIP; no sustituye PHP | 2026-11-02 |
| Sintaxis PHP y reglas unitarias | Pendiente de ejecutar en XAMPP | Completar con el registro |
| Integración de pagos/ventas/documentos/recepción | Suite ampliada; pendiente de ejecutar esta versión | Completar con el registro |
| Aceptación HTTP integrada | Suite preparada; pendiente de ejecutar | Adjuntar `test-results/validaciones-http.json` |
| Revisión visual de mensajes y navegación | Pendiente en navegador | Completar nombre y fecha |

**Criterio de cierre:** ejecutor en `APROBADO`, reporte sin fallos y revisión visual de los flujos utilizados. Si una prueba falla, corregir y repetir antes de marcar RF-61/RF-62 como aceptados. No se han inventado fechas ni resultados de pruebas.

## Límites y trabajo futuro

El módulo de productos de este ZIP permite consulta y alta desde un insumo; no incluye alta/edición general/disponibilidad. No se crea esa función dentro de esta corrección de Juanfer. Cuando su responsable la integre, debe validar sus nuevos formularios con las reglas compartidas y ampliar/volver a ejecutar la aceptación. Lo mismo aplica a cualquier función nueva de otras ramas.
