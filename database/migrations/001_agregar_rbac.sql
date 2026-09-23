-- Migracion unica para instalaciones creadas antes de RBAC.
-- No ejecutar sobre una instalacion nueva: schema.sql ya contiene estas estructuras.
-- No contiene DROP ni elimina usuarios existentes.

CREATE TABLE roles (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 codigo VARCHAR(40) NOT NULL,
 nombre VARCHAR(80) NOT NULL,
 descripcion VARCHAR(255) NOT NULL,
 UNIQUE KEY uq_rol_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permisos (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 codigo VARCHAR(80) NOT NULL,
 nombre VARCHAR(120) NOT NULL,
 descripcion VARCHAR(255) NOT NULL,
 UNIQUE KEY uq_permiso_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rol_permisos (
 rol_id INT UNSIGNED NOT NULL,
 permiso_id INT UNSIGNED NOT NULL,
 PRIMARY KEY (rol_id, permiso_id),
 CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
 CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (codigo, nombre, descripcion) VALUES
 ('administrador', 'Administrador', 'Administra unicamente los recursos de su empresa.'),
 ('cajero', 'Cajero', 'Registra pedidos, ventas y cobros de su empresa.'),
 ('cocinero', 'Cocinero', 'Consulta y actualiza la preparacion de pedidos de su empresa.');

INSERT INTO permisos (codigo, nombre, descripcion) VALUES
 ('inicio.ver', 'Ver inicio', 'Permite acceder a la pagina principal.'),
 ('usuarios.ver', 'Ver usuarios', 'Permite consultar usuarios de la empresa.'),
 ('usuarios.gestionar', 'Gestionar usuarios', 'Permite crear, editar y desactivar usuarios de la empresa.'),
 ('productos.ver', 'Ver productos', 'Permite consultar productos o platillos.'),
 ('productos.gestionar', 'Gestionar productos', 'Permite crear y modificar productos o platillos.'),
 ('inventario.ver', 'Ver inventario', 'Permite consultar las existencias de la empresa.'),
 ('inventario.gestionar', 'Gestionar inventario', 'Permite registrar entradas, salidas y ajustes.'),
 ('proveedores.ver', 'Ver proveedores', 'Permite consultar proveedores de la empresa.'),
 ('proveedores.gestionar', 'Gestionar proveedores', 'Permite registrar y modificar proveedores y compras.'),
 ('pedidos.ver', 'Ver pedidos', 'Permite consultar pedidos de la empresa.'),
 ('pedidos.crear', 'Crear pedidos', 'Permite registrar y modificar pedidos abiertos.'),
 ('pedidos.confirmar', 'Confirmar pedidos', 'Permite enviar pedidos a preparacion.'),
 ('pedidos.actualizar_cocina', 'Actualizar cocina', 'Permite actualizar el estado de preparacion.'),
 ('ventas.ver', 'Ver ventas', 'Permite consultar las ventas de la empresa.'),
 ('ventas.cobrar', 'Registrar cobros', 'Permite cobrar pedidos.'),
 ('reportes.ver', 'Ver reportes', 'Permite consultar reportes administrativos.'),
 ('empresa.configurar', 'Configurar empresa', 'Permite modificar la configuracion de la empresa.');

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permisos p
WHERE r.codigo = 'administrador';

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permisos p
WHERE r.codigo = 'cajero'
 AND p.codigo IN ('inicio.ver', 'productos.ver', 'pedidos.ver', 'pedidos.crear',
                  'pedidos.confirmar', 'ventas.ver', 'ventas.cobrar');

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permisos p
WHERE r.codigo = 'cocinero'
 AND p.codigo IN ('inicio.ver', 'productos.ver', 'pedidos.ver', 'pedidos.actualizar_cocina');

-- La columna inicia como nullable para poder asignar un rol a las filas existentes.
ALTER TABLE usuarios ADD COLUMN rol_id INT UNSIGNED NULL AFTER empresa_id;

-- Los usuarios anteriores conservan el acceso como administradores de su propia empresa.
UPDATE usuarios u
JOIN roles r ON r.codigo = 'administrador'
SET u.rol_id = r.id
WHERE u.rol_id IS NULL;

ALTER TABLE usuarios
 MODIFY COLUMN rol_id INT UNSIGNED NOT NULL,
 ADD KEY ix_usuario_rol (rol_id),
 ADD CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id) REFERENCES roles(id);
