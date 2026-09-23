
# Módulo de Inventario

## Descripción

El módulo de inventario permite administrar los insumos pertenecientes a cada restaurante registrado en la plataforma.

Su objetivo principal es mantener un registro actualizado de las existencias disponibles y permitir que los usuarios autorizados puedan consultar y modificar manualmente las cantidades de los insumos.

Esta primera implementación mantiene un alcance simplificado. El inventario no se actualiza automáticamente a partir de pedidos, recetas o compras; las existencias son administradas manualmente.

---

## Funcionalidades implementadas

Actualmente, el módulo permite:

- Consultar los insumos registrados.
- Registrar nuevos insumos.
- Editar insumos existentes.
- Registrar la cantidad actual de cada insumo.
- Definir un nivel mínimo de stock.
- Definir la unidad de medida de cada insumo.
- Identificar visualmente los insumos con stock bajo.
- Restringir el acceso mediante el sistema de roles y permisos.
- Mantener separados los inventarios de diferentes empresas.

---

## Arquitectura

El módulo sigue el patrón **Modelo-Vista-Controlador (MVC)** utilizado por CodeIgniter.

```text
Usuario
   │
   ▼
Inventario.php
Controller
   │
   ▼
Insumo_model.php
Model
   │
   ▼
MySQL / MariaDB
   │
   ▼
Tabla insumos
```

Las vistas son responsables de presentar la información y recopilar los datos ingresados por el usuario.

El controlador recibe las solicitudes, valida los permisos y los datos proporcionados, y coordina las operaciones del módulo.

El modelo se encarga del acceso y persistencia de los datos relacionados con los insumos.

---

## Archivos creados

### Controller

```text
application/controllers/Inventario.php
```

Responsabilidades:

- Mostrar el inventario.
- Mostrar el formulario para registrar insumos.
- Procesar el registro de nuevos insumos.
- Mostrar el formulario de edición.
- Procesar modificaciones.
- Validar los datos ingresados.
- Verificar los permisos del usuario.

---

### Model

```text
application/models/Insumo_model.php
```

Responsabilidades:

- Consultar los insumos de la empresa actual.
- Buscar un insumo por su identificador.
- Registrar nuevos insumos.
- Actualizar insumos existentes.
- Restringir las consultas mediante `empresa_id`.

---

### Views

```text
application/views/inventario/index.php
```

Muestra la lista de insumos registrados, sus existencias, stock mínimo, unidad de medida y estado.

```text
application/views/inventario/formulario.php
```

Formulario reutilizado para registrar y editar insumos.

---

## Archivos modificados

### Rutas

```text
application/config/routes.php
```

Se agregaron las rutas correspondientes al módulo:

```php
$route['inventario'] = 'inventario/index';
$route['inventario/crear'] = 'inventario/crear';
$route['inventario/(:num)/editar'] = 'inventario/editar/$1';
```

---

### Menú principal

```text
application/views/inicio/index.php
```

Se agregó el acceso al módulo de inventario.

El enlace únicamente se muestra cuando el usuario posee el permiso:

```text
inventario.ver
```

---

### Estilos

```text
assets/app.css
```

Se agregaron estilos para:

- Tabla de inventario.
- Botón para agregar insumos.
- Formularios.
- Indicadores de stock.
- Adaptación básica para pantallas pequeñas.

Los estilos mantienen la apariencia general existente de la plataforma.

---

### Base de datos

```text
database/schema.sql
```

Se agregó la tabla `insumos`.

---

## Modelo de datos

La tabla utilizada por el módulo es:

```sql
CREATE TABLE insumos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    cantidad_actual DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
    unidad_medida VARCHAR(20) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,

    KEY ix_insumo_empresa (empresa_id),

    CONSTRAINT fk_insumo_empresa
        FOREIGN KEY (empresa_id)
        REFERENCES empresas(id)
);
```

### Campos

| Campo               | Descripción                                             |
| ------------------- | -------------------------------------------------------- |
| `id`              | Identificador único del insumo.                         |
| `empresa_id`      | Empresa o restaurante propietario del insumo.            |
| `nombre`          | Nombre del insumo.                                       |
| `cantidad_actual` | Cantidad disponible actualmente.                         |
| `stock_minimo`    | Cantidad mínima recomendada.                            |
| `unidad_medida`   | Unidad utilizada para representar la existencia.         |
| `activo`          | Indica si el insumo permanece activo dentro del sistema. |

---

## Unidades de medida

Actualmente se permiten las siguientes unidades:

| Valor      | Unidad     |
| ---------- | ---------- |
| `unidad` | Unidad     |
| `kg`     | Kilogramos |
| `g`      | Gramos     |
| `L`      | Litros     |
| `mL`     | Mililitros |

En esta versión no se realizan conversiones automáticas entre unidades.

---

## Control de stock

Un insumo se considera con stock bajo cuando:

```text
cantidad_actual <= stock_minimo
```

Por ejemplo:

```text
Insumo: Pollo
Cantidad actual: 2 kg
Stock mínimo: 4 kg

Estado: STOCK BAJO
```

Cuando la cantidad disponible supera el mínimo:

```text
Insumo: Tomate
Cantidad actual: 10 kg
Stock mínimo: 3 kg

Estado: NORMAL
```

---

## Roles y permisos

El módulo utiliza el sistema RBAC existente en la aplicación.

Se utilizan los siguientes permisos:

### `inventario.ver`

Permite consultar el inventario.

El Controller valida este permiso mediante:

```php
$this->requerir_permiso('inventario.ver');
```

### `inventario.gestionar`

Permite registrar y modificar insumos.

```php
$this->requerir_permiso('inventario.gestionar');
```

Las vistas también utilizan los permisos para determinar qué opciones deben mostrarse:

```php
$usuario->puede('inventario.gestionar')
```

La validación realizada en la vista se utiliza únicamente para controlar la interfaz. La autorización de las operaciones se realiza en el Controller.

---

## Aislamiento entre empresas

El módulo mantiene el aislamiento de información existente en la plataforma.

Cada insumo pertenece a una empresa mediante:

```text
empresa_id
```

Las consultas realizadas por `Insumo_model` utilizan:

```php
$this->empresa_id()
```

Por ejemplo:

```php
$this->db
    ->from('insumos')
    ->where('empresa_id', $this->empresa_id());
```

Esto evita que un usuario pueda consultar o modificar directamente los insumos pertenecientes a otra empresa.

Conceptualmente:

```text
Plataforma
│
├── Empresa A
│   ├── Insumo A1
│   ├── Insumo A2
│   └── Insumo A3
│
└── Empresa B
    ├── Insumo B1
    └── Insumo B2
```

Los usuarios únicamente pueden operar sobre la información asociada con su empresa.

---

## Validaciones

Los formularios validan:

### Nombre

- Obligatorio.
- Máximo de 150 caracteres.

### Cantidad actual

- Obligatoria.
- Debe ser numérica.
- No puede ser negativa.

### Stock mínimo

- Obligatorio.
- Debe ser numérico.
- No puede ser negativo.

### Unidad de medida

- Obligatoria.
- Debe pertenecer al conjunto de unidades admitidas.

---

## Flujo para consultar inventario

```text
Usuario
   │
   ▼
GET /inventario
   │
   ▼
Inventario::index()
   │
   ├── Verifica inventario.ver
   │
   ▼
Insumo_model::listar()
   │
   ├── Filtra por empresa_id
   │
   ▼
Base de datos
   │
   ▼
inventario/index.php
   │
   ▼
Usuario
```

---

## Flujo para registrar un insumo

```text
Usuario
   │
   ▼
GET /inventario/crear
   │
   ├── Verifica inventario.gestionar
   │
   ▼
Formulario
   │
   │ POST
   ▼
Inventario::crear()
   │
   ├── Verifica permiso
   ├── Valida información
   │
   ▼
Insumo_model::crear()
   │
   ▼
Base de datos
   │
   ▼
Redirección a /inventario
```

---

## Seguridad

El módulo aprovecha los mecanismos de seguridad existentes en la aplicación:

- Autenticación obligatoria mediante `Protected_Controller`.
- Autorización basada en permisos.
- Separación de datos mediante `empresa_id`.
- Validación de solicitudes HTTP.
- Validación de datos del formulario.
- Escape de información mostrada mediante `html_escape()`.
- Protección CSRF proporcionada por la configuración existente de CodeIgniter.

La visibilidad de un botón o enlace no se considera un mecanismo de seguridad. Los permisos se verifican nuevamente en el Controller antes de realizar operaciones protegidas.

---

## Alcance actual

Esta primera versión utiliza un modelo de inventario simplificado.

Las cantidades son modificadas manualmente por usuarios autorizados.

Actualmente no se incluye:

- Descuento automático de insumos por pedidos.
- Recetas asociadas con productos.
- Conversión automática entre unidades.
- Registro histórico de movimientos.
- Integración automática con compras.
- Gestión de desperdicios o mermas.
- Notificaciones automáticas.

Estas funcionalidades podrán considerarse en futuras iteraciones.

---

## Mejoras futuras

Las siguientes mejoras pueden implementarse posteriormente:

- Historial de movimientos de inventario.
- Registro de entradas, salidas y ajustes.
- Motivo de cada modificación.
- Usuario responsable de cada movimiento.
- Fecha y hora de movimientos.
- Búsqueda y filtrado de insumos.
- Categorías de insumos.
- Gestión de proveedores.
- Integración con compras.
- Reportes de inventario.
- Alertas más avanzadas.
- Registro de productos próximos a vencer.

Una futura implementación de movimientos permitiría mantener trazabilidad sobre las modificaciones de existencias sin reemplazar directamente la cantidad anterior.

---

## Estado

**Estado actual:** Implementación inicial.

Funcionalidades disponibles:

- [X] Listado de insumos.
- [X] Registro de insumos.
- [X] Edición de insumos.
- [X] Cantidades manuales.
- [X] Stock mínimo.
- [X] Indicador de stock bajo.
- [X] Control de acceso mediante permisos.
- [X] Aislamiento por empresa.
- [ ] Historial de movimientos.
- [ ] Entradas y salidas independientes.
- [ ] Integración con proveedores.
- [ ] Reportes de inventario.
