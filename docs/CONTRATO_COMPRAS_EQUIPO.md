# Contrato de integración: documentos de compra

Propuesta concreta implementada sobre el ZIP 9. Antes de fusionar ramas, comparar este contrato con los esquemas que haya desarrollado cada compañero. No supone que esos esquemas ya sean compatibles.

## Tablas y propiedad de los datos

- `proveedores`: `id`, `empresa_id`, `nombre` (150), `contacto` (100), `telefono` (30), `correo` (190), `direccion` (255), `activo`. Nombre único dentro de cada empresa. Se incluye alta mínima desde compras; Andrés desarrolla el resto de su gestión.
- `insumos.proveedor_id`: nullable, referencia compuesta con `empresa_id`. Andrés incorpora su asignación/edición. Los insumos sin proveedor asociado pueden añadirse manualmente a un documento de un proveedor seleccionado. La agrupación automática de Emmanuel deberá pedir una asociación o reportarlos como pendientes; no agruparlos bajo un proveedor inventado.
- `empresas.ultimo_numero_compra`: contador bloqueado al crear documentos. No usar `MAX(numero)+1` sin bloqueo.
- `solicitudes_compra`: número, fecha y proveedor; copia del nombre/contacto/teléfono/correo/dirección; observaciones; estado; versión de edición; clave de envío; actores y fechas. `UNIQUE (empresa_id,numero)` conserva numeración independiente; `UNIQUE (empresa_id,clave)` evita duplicados al reenviar un formulario.
- `solicitud_compra_detalles`: insumo, nombre y unidad copiados, cantidad solicitada `DECIMAL(10,2)`, `recibido` inicial 0. Una línea por insumo y solicitud. FK compuestas impiden referenciar otra empresa.
- `solicitud_compra_eventos`: actor, acción, estado anterior/nuevo y fecha. Esta entrega registra crear/editar/confirmar; sirve como base para transiciones posteriores.

No borrar físicamente documentos, proveedores referenciados ni líneas después de confirmar. Mantener los datos históricos. El proveedor y el insumo se consultan por la empresa autenticada, nunca por una empresa enviada en POST.

## API para Emmanuel

Cargar el modelo con `Solicitud_compra_model` (se puede asignar alias `compras_model`). Ejemplo de entrada:

```php
$datos = array(
    'proveedor_id' => 12,
    'fecha' => '2026-11-02',
    'observaciones' => 'Entregar por la mañana',
    'lineas' => array(
        array('insumo_id' => 7, 'cantidad' => '2.50'),
        array('insumo_id' => 9, 'cantidad' => '4.00')
    )
);
$resultado = $this->compras_model->crear_borrador(
    $datos,
    bin2hex(random_bytes(32))
);
// $resultado['error'] === NULL indica éxito.
// $resultado['id'] es el ID interno para la ruta compras/<id>.
```

La empresa y el usuario salen de la sesión. Requiere `compras.gestionar`; vuelve a comprobar permiso/actividad en la base. Cantidades como cadenas decimales positivas, hasta 8 dígitos enteros y 2 decimales; entre 1 y 100 líneas, sin duplicar insumos. Si un insumo tiene proveedor asociado, debe coincidir con el documento.

El método abre y confirma SU PROPIA transacción. No envolver múltiples llamadas sin revisar el manejo de transacciones anidadas de CI3. Para crear varios grupos se puede mostrar el resultado de cada grupo y usar una clave distinta y estable por grupo; conservar las claves al reintentar. Si se necesita que todos los grupos se creen o fallen juntos, refactorizar de forma coordinada el servicio, no simular atomicidad con commits independientes. No invocar el servicio desde rutas sin autorización.

La vista nueva manual sirve para probar RF-54–56; no implementa la agrupación automática de RF-52 ni la selección desde inventario de RF-50.

### Integración en la rama de Emmanuel

La pantalla de Inventario permite enviar un insumo activo y su cantidad a `compras/seleccionar`. La selección se conserva en la sesión del usuario, se vuelve a validar contra su empresa y muestra el proveedor asociado a cada insumo. `compras/generar` agrupa por `insumos.proveedor_id` y crea un borrador por grupo mediante `Solicitud_compra_model::crear_borrador()`. Cada grupo usa una clave estable durante los reintentos de esa selección.

Si falla un grupo, los documentos creados antes del fallo permanecen guardados y se muestran con enlaces para revisarlos. La selección conserva solo las líneas pendientes para reintentar. Los insumos sin proveedor activo asociado no se agrupan ni se guardan automáticamente. La pantalla de proveedores incorporada desde `upstream/main` `521767c` permite asociarlos y se verificó mediante una prueba HTTP.

## Edición y confirmación (RF-55)

`editar_borrador($id,$version,$datos)` requiere estado `borrador` y la versión que leyó el usuario. Bloquea la cabecera, valida proveedor e insumos, reemplaza las líneas en una transacción y aumenta la versión. Los datos del proveedor y los nombres/unidades se actualizan al guardar explícitamente la edición.

`confirmar($id,$version)` exige permiso `compras.confirmar`, versión vigente, líneas válidas y proveedor/insumos activos. Rechaza un cambio de unidad ocurrido después de guardar el borrador. Cambia a `confirmada`, aumenta la versión y registra quién confirmó. Conserva la copia documental que fue revisada. No mueve existencias.

Las rutas POST requieren CSRF. Los controles de la vista ayudan al usuario, pero no reemplazan estas comprobaciones del servidor.

## Continuación de Manuel

Los estados `enviada`, `recibida`, `cancelada` y la recepción parcial de RF-57–60 ya llegaron desde `upstream/main` `521767c`. Las transiciones se validan en el servidor. La prueba de integración comprueba recepción parcial, reintento idempotente y recepción completa.

La recepción bloquea la solicitud y sus líneas; comprueba estado y empresa; valida que la cantidad adicional no exceda `cantidad - recibido`; actualiza recibido y existencias e inserta movimientos/eventos en la misma transacción. La clave de recepción evita duplicar stock al reenviar y rechaza la misma clave si se reutiliza con otras cantidades. Las tablas `recepciones_compra` y `recepcion_compra_detalles` están en `013_recepciones_compra.sql`, en `schema.sql` y en el actualizador local.

No habilitar edición de detalles que ya tengan `recibido > 0`. El documento conserva su cantidad solicitada original.

## Exportación

RF-56 ofrece texto copiable y archivo TXT en UTF-8. Las salidas incluyen número, empresa, estado, fecha, copia de datos del proveedor, líneas y observaciones. Un borrador lleva advertencia explícita de no confirmado. Descargar/copiar no cambia a `enviada` ni constituye un envío real.

## Ventas y cocina

`ventas` tiene una restricción única `(empresa_id,pedido_id)`. La inserción se hace desde `Pago_model` bajo el bloqueo del pedido y dentro de la misma transacción que cierra el saldo. No añadir otra inserción independiente en controladores de cocina, pedidos o cobros.

Estado de cocina acordado en el código base: `en_preparacion`. Si una rama usa `preparacion`, deberá adaptarlo tanto en código como en datos para que cobro y cancelación conserven su comportamiento. Pagar no cambia el estado operativo.
