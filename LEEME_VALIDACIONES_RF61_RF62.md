# RF-61 y RF-62 — Juan Fernando Santos Mérida

Actualización sobre `gestion-negocios(10).zip`, recibida el 2 de noviembre de 2026.
Solo se ajustan las validaciones y su aceptación; se conservan los módulos del equipo.

## Instalar

1. Guarda un respaldo y confirma tus cambios pendientes en tu rama.
2. Copia el contenido de `gestion-negocios` de este ZIP sobre tu proyecto.
3. Conserva tu carpeta `.git` y `application/config/local.php`: no vienen incluidos.
4. Estas correcciones no agregan tablas ni requieren una migración nueva. La base debe estar actualizada a la versión que ya utilizabas con el ZIP 10.

## Qué se corrigió

- Usuarios: validación compartida también en el modelo; nombre y usuario obligatorios, correo y rol válidos. La contraseña se valida antes de bcrypt y rechaza solo espacios, bytes nulos y longitudes fuera de 8–72 bytes. No se modifican las contraseñas existentes.
- Proveedores: alta y edición usan las mismas reglas. Arreglos inesperados, texto demasiado largo, nombre vacío y correo inválido se rechazan antes de guardar. Los contactos siguen siendo opcionales y se conserva el texto escrito al mostrar errores.
- Inventario: los errores de formulario responden HTTP 422; el nombre y la fecha de vencimiento pasan por las reglas compartidas. Los filtros con arreglos dejan de provocar advertencias. Cero es válido para existencia inicial, mínimo y ajuste; no para entradas/salidas.
- Productos: se conserva la validación estricta del código/precio al habilitar un insumo para venta y se devuelve 422 ante errores. El ZIP base no tiene alta/edición general de productos; no se implementa aquí una función asignada a otro integrante.
- Pedidos: referencia validada antes de consultar el modelo; espacios solos y formatos inválidos devuelven un error de entrada, no un error de instalación.
- Pagos: nombres de cuentas pasan por la validación compartida; se rechaza repetir un mismo detalle con identificadores equivalentes como `1` y `01`. Se conservan cálculo en centavos, límites, control del saldo, permisos e idempotencia.
- Compras agrupadas: formato decimal estricto y rechazo de identificadores equivalentes duplicados. Una casilla vacía sigue significando que el insumo no está seleccionado.
- Recepciones: validación de todos los identificadores y cantidades, incluso en reintentos. Rechaza detalles duplicados y unidades diferentes de las solicitadas antes de aumentar el inventario. Mantiene el control de versiones, saldo pendiente e idempotencia. Si cambió la unidad, hay que revisar/restaurar la unidad correcta: no se convierte automáticamente de kg a g.
- Pruebas y matriz: se extienden las pruebas unitarias/de modelos y se agrega aceptación HTTP sobre una copia temporal con dos empresas y tres roles.

## Ejecutar la aceptación con XAMPP

Activa **MySQL**. En PowerShell, dentro de `C:\xampp\htdocs\gestion-negocios`:

```powershell
& "C:\xampp\php\php.exe" tests\ejecutar_validaciones.php
```

No necesitas detener Apache: la prueba inicia su propio servidor PHP en un puerto local libre. Se requiere PHP 8.2 de 64 bits con `curl`, `mysqli`, `mbstring`, `proc_open` y permiso para crear/eliminar bases temporales. La conexión se toma de tu `local.php`.

El ejecutor comprueba sintaxis y corre:

1. `tests/pagos_unit.php`.
2. `tests/validacion_negocio_unit.php`.
3. `tests/rf_juan_integracion.php` (base temporal `gn_pago_test_<azar>`).
4. `tests/validaciones_http.php` (base temporal y copia del código `gn_validacion_test_<azar>`).

Las pruebas **no usan la base del negocio para insertar, actualizar ni borrar datos**. Crean sus propias empresas, usuarios, pedidos y compras; eliminan las bases temporales al terminar. No imprimen las contraseñas generadas. No interrumpas el proceso mientras está ejecutándose. Si se interrumpe externamente, puede quedar una base temporal identificada por esos prefijos.

La prueba HTTP envía solicitudes reales a CodeIgniter, obtiene tokens CSRF nuevos, comprueba sesión/roles/empresa y compara los registros antes/después de cada operación rechazada. Incluye casos válidos para comprobar que los formularios siguen permitiendo trabajar.

Un resultado correcto termina con:

```
APROBADO: sintaxis, reglas, integración de modelos y aceptación HTTP.
```

Evidencias generadas en `test-results` (carpeta ignorada por Git):

- `validaciones-ejecucion.log`: salida de las comprobaciones.
- `validaciones-http.json`: casos aprobados, fecha y resultado real.
- `validaciones-servidor.log`: diagnóstico del servidor si falla la prueba HTTP.

Si dice **FALLO**, no marques la aceptación como aprobada: revisa la última prueba del registro. Algunas pruebas de modelos provocan un error de base intencional para verificar rollback; el criterio es el código de salida y el resultado final del ejecutor.

## Estado real de la entrega

En el entorno donde se preparó este ZIP no hay PHP/MySQL ejecutables: se revisaron cambios, referencias, estructura del esquema y delimitadores de PHP, pero **no se ejecutó esta nueva suite**. Las pruebas anteriores reportadas por el equipo corresponden a su versión previa y no certifican automáticamente estos ajustes.

La implementación y los casos de aceptación quedan preparados. Para cerrar RF-61/RF-62 sobre esta versión, ejecutar la suite en XAMPP, guardar el resultado y completar el registro en `docs/VALIDACION_RF_JUAN.md`. La prueba HTTP no sustituye una revisión visual de mensajes y navegación en el navegador.

## Coordinación

No necesitas que un compañero implemente algo antes de aplicar estos cambios sobre el ZIP 10. Ya incluye usuarios, cocina, proveedores y recepción. Si posteriormente se agregan formularios o se cambian modelos, su autor debe aplicar las mismas reglas y volver a ejecutar la aceptación; no se declara validado código que aún no existe.
