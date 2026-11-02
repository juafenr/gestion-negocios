# RF-61 y RF-62: validación y aceptación

Estado: implementación y revisión estática; ejecución PHP/MySQL y pruebas de navegador pendientes en el equipo. La coordinación transversal incluye esta matriz; no declara validados módulos que todavía no están integrados.

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

## Trabajo de validación pendiente tras integrar otras ramas

- Andrés: aplicar las reglas al alta general/edición/disponibilidad de productos, proveedor completo y asociación con insumos. Probar que un producto no disponible se rechaza en POST del pedido y auditar precios.
- Emmanuel: validar registro/roles de usuarios, las transiciones de cocina y la creación agrupada. Probar cantidades incompletas, insumos sin proveedor y reintentos de grupos.
- Manuel: validar recepción total/parcial, saldos por recibir, estados e idempotencia de movimientos.
- Juan Fernando coordina el recorrido integrado final y actualiza esta matriz con resultados reales. No marcar RF-61/62 como verificados de extremo a extremo hasta probar esas incorporaciones.
