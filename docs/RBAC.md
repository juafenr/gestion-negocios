# Guía para utilizar roles y permisos

El sistema utiliza RBAC para decidir qué acciones puede realizar cada usuario. El aislamiento por `empresa_id` continúa siendo obligatorio y ningún rol, incluido `administrador`, puede omitirlo.

## Proteger una acción

Los controladores privados deben heredar de `Protected_Controller` y declarar el permiso requerido:

```php
class Inventario extends Protected_Controller
{
    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('inventario.ver');
        // Continuar con el caso de uso.
    }

    public function ajustar()
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('inventario.gestionar');
        // Validar y guardar el ajuste.
    }
}
```

Un usuario sin el permiso recibe HTTP 403. Un visitante sin sesión es redirigido al login.

## Adaptar la navegación

Las vistas pueden ocultar opciones no disponibles:

```php
<?php if ($usuario->puede('inventario.ver')): ?>
    <a href="<?= html_escape(site_url('inventario')) ?>">Inventario</a>
<?php endif; ?>
```

Ocultar un enlace no protege la acción. El controlador siempre debe llamar también a `requerir_permiso()`.

## Consultar datos

Los modelos con datos de una empresa deben heredar de `MY_Model` y añadir el filtro en cada consulta:

```php
->where('empresa_id', $this->empresa_id())
```

No se debe aceptar `empresa_id` desde formularios, URL o JavaScript como fuente de autoridad.

## Separación de responsabilidades

- `UsuarioAutenticado::puede()` responde si el rol posee una capacidad.
- `Protected_Controller::requerir_permiso()` protege la acción HTTP.
- `MY_Model::empresa_id()` proporciona la empresa autenticada para filtrar datos.
- Cada módulo valida además sus reglas de negocio, por ejemplo las transiciones válidas de un pedido.

## Permisos disponibles

- `inicio.ver`
- `usuarios.ver`
- `usuarios.gestionar`
- `productos.ver`
- `productos.gestionar`
- `inventario.ver`
- `inventario.gestionar`
- `proveedores.ver`
- `proveedores.gestionar`
- `pedidos.ver`
- `pedidos.crear`
- `pedidos.confirmar`
- `pedidos.actualizar_cocina`
- `ventas.ver`
- `ventas.cobrar`
- `reportes.ver`
- `empresa.configurar`

Si un módulo necesita una capacidad nueva, se debe agregar al catálogo, asignarla explícitamente a los roles correspondientes y crear pruebas para accesos permitidos y denegados.
