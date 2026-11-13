# Estadísticas en Inicio

## Cómo instalar este paquete

1. Abre la carpeta local del proyecto (`C:\xampp\htdocs\gestion-negocios`).
2. Extrae el ZIP en una carpeta aparte. Copia **application**, **assets**, **docs** y **tests** a la raíz del proyecto y acepta reemplazar los archivos coincidentes. Combina las carpetas; no borres las carpetas originales.
3. No se incluye ni se reemplaza `.git`, `application/config/local.php` ni ninguna base de datos.
4. Inicia Apache y MySQL, inicia sesión y abre **Inicio**. Si el navegador conserva estilos anteriores, pulsa **Ctrl + F5**.

**Este cambio no requiere migraciones, importar SQL ni volver a instalar la base.** Requiere que ya funcionen los módulos de ventas, pagos, productos, inventario y pedidos de la versión del proyecto sobre la que se preparó el paquete.

## Qué muestra

- **El ritmo de tus ventas:** ventas completadas de los últimos 7, 30 o 90 días (30 por defecto), con el total sin propina, cantidad de pedidos pagados y promedio por pedido. La propina se presenta por separado. Los días sin ventas aparecen en cero. Se puede tocar un punto, recorrerlo con las flechas del teclado o abrir la tabla con valores exactos.
- **Productos más vendidos:** cinco productos con más unidades en esas ventas. Usa el nombre actual del catálogo. Los abonos, borradores y pedidos cancelados sin venta no entran en el ranking; pagar en varias partes no multiplica sus unidades.
- **Productos menos vendidos:** cinco productos o platillos disponibles hoy con menos unidades vendidas en el período. Incluye aquellos con cero ventas. No confunde un producto no vendido con uno fuera del catálogo disponible.
- **Quién despacha más:** cinco usuarios con más pedidos marcados como entregados en el período. Se basa en el historial `pedido_eventos`: cuenta al usuario que registró la entrega, no al creador del pedido ni al cajero. Cada pedido se atribuye una sola vez, a su primer evento de entrega. Incluye entregas pendientes de cobro; no exige que ya exista una venta. Pedidos antiguos sin ese evento no se atribuyen a ningún usuario.
- **¿Cómo te pagan?:** efectivo aplicado, crédito y débito registrados en el período. Incluye pagos parciales y propinas. No suma el efectivo recibido que luego se devuelve como cambio. Al separar cuentas, suma cada cobro una sola vez.
- **La salud de tu inventario:** número de insumos activos con stock suficiente, por reponer o agotados. Cuenta insumos, no mezcla cantidades de distintas unidades de medida. Un insumo con existencias iguales al mínimo aparece por reponer; uno en cero, agotado.
- **Pedidos en marcha:** pedidos pendientes, en preparación y listos. No incluye borradores, entregados ni cancelados.

Ventas, rankings de productos, cobros y despachos usan el período seleccionado, incluyendo hoy (aún en curso). Inventario y pedidos muestran el estado actual; el selector de días no cambia esos dos gráficos.

Los totales de ventas y cobros pueden ser diferentes: la venta se registra cuando termina de pagarse el pedido, mientras que cada abono tiene su propia fecha. Además, las ventas del gráfico excluyen propinas y los cobros sí las incluyen. Se conserva la misma referencia de fechas de PHP y de la base que utiliza el historial existente.

## Permisos y separación por empresa

El modelo obtiene la empresa del usuario autenticado, no de la URL ni de formularios. Todas las consultas y uniones se limitan a esa empresa. Los permisos se comprueban antes de ejecutar las consultas:

| Indicador | Permiso necesario |
| --- | --- |
| Ventas y productos más/menos vendidos | `ventas.historial` |
| Distribución de cobros | `ventas.ver` y `pedidos.ver` |
| Usuarios con más despachos | `reportes.ver` y `pedidos.ver` |
| Existencias | `inventario.ver` |
| Pedidos en marcha | `pedidos.ver` |

Con los permisos habituales, un cocinero verá los pedidos, pero no los montos de ventas o cobros. El administrador verá más indicadores. La comprobación no consiste solamente en ocultar gráficos en la vista: los datos de los módulos no permitidos tampoco se consultan ni se envían al navegador.

## Implementación

Se conserva la navegación, la bienvenida, los indicadores de hoy y los accesos rápidos. La sección amplía y reemplaza el anterior gráfico de siete días. No modifica operaciones de ventas, cobros, inventario ni pedidos.

`Estadistica_model` realiza consultas agregadas de solo lectura. La vista `inicio/estadisticas.php` dibuja SVG locales, sin CDN ni librerías externas. `estadisticas.css` está limitado a esta sección. `estadisticas.js` añade lectura de puntos con teclado y toque; los gráficos y sus tablas se renderizan incluso sin JavaScript. Los valores monetarios visibles conservan los DECIMAL devueltos por SQL; solo la geometría y los porcentajes usan números de punto flotante.

## Comprobación en XAMPP

Desde la terminal de PowerShell de VS Code, dentro del proyecto:

```powershell
& "C:\xampp\php\php.exe" tests/estadisticas_integracion.php
```

La prueba crea una **base temporal nueva** con un nombre `gn_est_test_...`, usa el esquema del proyecto y la elimina al terminar. Necesita permisos CREATE/DROP DATABASE de la cuenta local. No inserta ni modifica registros en `gestion_negocios`.

Comprueba separación entre empresas, permisos, límites de fechas, días vacíos, abonos, efectivo aplicado, rankings sin duplicar por pagos múltiples, productos con cero ventas, entregas por usuario sin duplicados, estados de inventario y pedidos. El resultado esperado es:

```text
APROBADO: estadísticas, separación por empresa, permisos y límites.
```

Comprueba también en el navegador:

1. Inicia sesión como administrador y compara el total de ventas con el historial para las mismas fechas (sin propinas).
2. Cambia entre 7, 30 y 90 días. Comprueba la tabla diaria y los puntos del gráfico.
3. Entra con otra empresa: sus cifras y productos deben ser distintos y corresponder a sus registros.
4. Entra como cocinero y como cajero: solo deben aparecer los indicadores de sus permisos.
5. Agrega un producto disponible sin ventas: debe entrar entre los menos vendidos, según los cinco primeros y el orden por identificador en caso de empate.
6. Marca un pedido listo como entregado desde Cocina: debe contarse en el ranking del usuario que hizo ese cambio. Crear o cobrar un pedido no cuenta como despacho.
7. Prueba una empresa sin registros y una ventana estrecha o un teléfono.

### Alcance de la verificación del paquete

Se revisaron los cambios y se comprobó la sintaxis de JavaScript. Se ejecutaron las ocho consultas agregadas con datos aislados en SQLite para comprobar filtros, uniones, fechas, sumas y casos vacíos. Esta comprobación no sustituye la ejecución con MySQL/MariaDB.

No se pudo ejecutar PHP/MySQL ni completar la revisión con un navegador en el entorno de preparación. La prueba PHP incluida queda pendiente de ejecución en XAMPP; no se presenta como aprobada.
