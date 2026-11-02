# Entrega de Juan Fernando Santos Mérida

Base: `gestion-negocios(9).zip` y reparto del 1 de noviembre de 2026. Implementación de RF-33–35, RF-54–56 y refuerzo de RF-61–62 en los módulos presentes. Incluye una base mínima de proveedores y creación manual de borradores para poder probar los documentos sin esperar los módulos completos del equipo.

## Qué puedes utilizar

- **Ventas:** cada pedido se convierte en una sola venta cuando queda completamente pagado, incluso si se pagó mediante abonos o cuentas separadas. El último cobro, el estado pagado y la venta se guardan en la misma transacción. Si falla la venta, también se deshace ese último cobro.
- **Historial administrativo:** listado paginado, filtro desde/hasta, detalle de la venta y resumen diario paginado. El resumen separa consumo, propina y total cobrado; muestra también efectivo aplicado, crédito y débito. El rango incluye ambos días completos. La fecha de venta es la del cierre del pago, no la de cada abono.
- **Compras:** número independiente por empresa, fecha, proveedor, insumos, cantidades, unidades y observaciones. Se permite revisar y editar únicamente el borrador. Confirmar bloquea su edición; no mueve inventario.
- **Documento:** texto copiable y descarga TXT con las líneas y los datos del proveedor. El borrador se identifica como tal. El sistema no envía el documento automáticamente.
- **Validación:** campos obligatorios, formatos, límites, cantidades positivas en compras, rechazo de negativos y de más de dos decimales; fechas válidas y controles de empresa y permisos. Los cambios del catálogo general y otros módulos aún ausentes deberán aplicar estas reglas al integrarse.

El permiso `ventas.historial` y los permisos `compras.ver`, `compras.gestionar`, `compras.confirmar`, `compras.exportar` se otorgan inicialmente solo al administrador. El cajero conserva el acceso a pagos. El cocinero no recibe acceso a estos módulos.

## Actualizar tu proyecto existente

1. Guarda tus cambios de Git y haz una copia del proyecto.
2. En phpMyAdmin selecciona tu base → **Exportar → SQL**. Guarda el respaldo fuera del repositorio.
3. Detén Apache y deja MySQL encendido.
4. Descomprime el ZIP fuera de `htdocs`. Copia el contenido de su carpeta `gestion-negocios` sobre `C:\xampp\htdocs\gestion-negocios`.
5. Conserva `.git` y `application/config/local.php`. El ZIP no los incluye.
6. Abre PowerShell en la carpeta del proyecto y ejecuta:

```powershell
& "C:\xampp\php\php.exe" tools\actualizar.php
& "C:\xampp\php\php.exe" tools\actualizar.php --aplicar
& "C:\xampp\php\php.exe" tools\actualizar.php
```

El diagnóstico final debe indicar **Sin cambios pendientes**. Lee además cualquier aviso sobre pedidos pagados que no pudieron conciliarse.

7. Enciende Apache, cierra e inicia sesión como administrador y pulsa `Ctrl + F5`. Deben aparecer **Ventas** y **Compras**.

**No importes `schema.sql` encima de tu base existente.** Las nuevas migraciones son `009_ventas.sql` y `010_documentos_compra.sql`; el actualizador ya contempla sus estructuras y no necesitas importarlas además. Si otro compañero ya utilizó esos números para otra migración, comparen el contenido antes de integrarlas; no reemplacen archivos únicamente por su número.

El actualizador no borra datos y conserva los contadores existentes. Las modificaciones de estructura de MySQL no se deshacen todas juntas: conserva el respaldo hasta probar la actualización. Si encuentra tablas de compras con una estructura diferente, debe revisarse la compatibilidad antes de continuar.

## Pedidos que ya estaban pagados

La actualización crea sus ventas históricas únicamente cuando la suma de los cobros coincide exactamente con consumo + propina y el desglose de métodos/propinas es consistente. Toma la fecha del último cobro. En pedidos de importe cero sin cobros usa la fecha de confirmación, o creación si no existe la anterior, y deja sin atribuir el usuario del cobro.

La conciliación se puede repetir sin duplicar ventas. No inventa dinero ni cambia pedidos a pagados. Si aparecen avisos, revisa esos registros: un estado `pagado` puesto manualmente no demuestra que existan cobros respaldándolo. La consulta histórica predeterminada muestra el mes actual; amplía el filtro para ver ventas de meses anteriores.

## Instalación nueva

Copia `application/config/local.example.php` a `application/config/local.php`, configura una base vacía y ejecuta:

```powershell
& "C:\xampp\php\php.exe" tools\instalar.php
```

El esquema completo incluye hasta 010. El instalador imprime las contraseñas aleatorias de las cuentas de prueba. No importes las migraciones por separado después. Los proveedores y solicitudes no se rellenan con datos ficticios automáticamente.

## Prueba rápida del flujo

**Ventas:** crea y confirma un pedido de Q 100 de consumo. Abona Q 30: todavía no debe aparecer una venta. Completa los Q 80 restantes (Q 110 incluyendo propina): debe aparecer una sola venta con consumo Q 100 y propina Q 10. Vuelve a consultar el pedido; el estado de cocina sigue siendo independiente.

**Compras:** registra un proveedor desde Compras → Registrar proveedor, crea al menos un insumo en Inventario y luego crea una solicitud manual. Revisa sus líneas, edita la cantidad y observaciones, guarda y confirma. Comprueba que después de confirmar ya no permite editar. Abre Copiar o descargar y revisa el TXT. Confirmar y exportar no aumentan las existencias ni indican que el proveedor haya recibido el documento.

Para cantidades compartidas, unidades o asociación insumo–proveedor, consulta `docs/CONTRATO_COMPRAS_EQUIPO.md`.

## ¿Tiene que trabajar alguien antes?

**No necesitas esperar a un compañero para instalar y probar esta entrega.** Ventas funciona sobre el módulo de pagos existente. Para documentos de compra se incluyó el mínimo necesario: registro de proveedor y creación manual de una solicitud para un solo proveedor.

Sí hay dependencias para completar el flujo automático del equipo:

| Compañero | Qué falta integrar | Cómo se conecta |
| --- | --- | --- |
| Andrés | Gestión completa de proveedores, asociación con insumos y catálogo general de productos. | Ampliar `proveedores` y usar `insumos.proveedor_id`, respetando la empresa. Aplicar la validación compartida a los formularios nuevos. |
| Emmanuel | Selección desde inventario y agrupación automática por proveedor (RF-50–53). | Crear cada documento con `Solicitud_compra_model::crear_borrador()`, usando el contrato documentado. No crear otra tabla paralela de solicitudes. |
| Manuel | Estados posteriores, historial por proveedor y recepción total/parcial (RF-57–60). | Extender `solicitudes_compra`, sus detalles y eventos; usar `recibido` por línea y mover stock en una transacción. |
| Emmanuel | Integración de cocina y usuarios desde su rama. | Conservar pagos/ventas y usar `en_preparacion`, que es el estado esperado en main. Si su rama tiene `preparacion`, debe conciliarlo y migrar los datos. |

**Comparte el contrato con el equipo antes de fusionar sus módulos de compras.** Esta entrega no incorpora ni sustituye el trabajo de sus ramas.

## Verificación disponible

Se revisó estáticamente el código, los recursos, las relaciones y el orden del esquema, y la sintaxis de JavaScript. **No se ejecutaron PHP ni MySQL en el entorno de preparación**, por lo que las pruebas de ejecución están pendientes en XAMPP.

Pruebas sin base de datos:

```powershell
& "C:\xampp\php\php.exe" tests\pagos_unit.php
& "C:\xampp\php\php.exe" tests\validacion_negocio_unit.php
```

Prueba de los modelos con datos aislados:

```powershell
& "C:\xampp\php\php.exe" tests\rf_juan_integracion.php
```

Usa las credenciales locales para crear una base temporal `gn_pago_test_...` y elimina únicamente esa base al terminar. No escribe en la base del proyecto. Requiere permiso CREATE/DROP DATABASE. Si se interrumpe a la fuerza, la base temporal puede quedar para limpieza manual.

La prueba comprueba pagos, venta única, rollback cuando no puede guardar la venta, conciliación histórica, separación por empresa, documentos editables/confirmados, rechazo de datos inválidos y copia estable de datos. No sustituye la comprobación de formularios, roles y CSRF en el navegador. La matriz de aceptación está en `docs/VALIDACION_RF_JUAN.md`.

Durante la prueba de integración se provoca deliberadamente un fallo al guardar una venta para comprobar el rollback; puede aparecer un mensaje de error del modelo seguido de la comprobación OK correspondiente. Si el proceso termina con FALLO, no integres la entrega hasta revisar ese resultado.
