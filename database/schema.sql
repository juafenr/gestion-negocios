-- Esquema inicial: ejecutar solo sobre una base vacía. Sin DROP ni credenciales.
CREATE TABLE
    empresas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(120) NOT NULL,
        activa TINYINT (1) NOT NULL DEFAULT 1
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
    roles (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(40) NOT NULL,
        nombre VARCHAR(80) NOT NULL,
        descripcion VARCHAR(255) NOT NULL,
        UNIQUE KEY uq_rol_codigo (codigo)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
    permisos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(80) NOT NULL,
        nombre VARCHAR(120) NOT NULL,
        descripcion VARCHAR(255) NOT NULL,
        UNIQUE KEY uq_permiso_codigo (codigo)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
    rol_permisos (
        rol_id INT UNSIGNED NOT NULL,
        permiso_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (rol_id, permiso_id),
        CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE CASCADE,
        CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (permiso_id) REFERENCES permisos (id) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
    usuarios (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT UNSIGNED NOT NULL,
        rol_id INT UNSIGNED NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        correo VARCHAR(190) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        activo TINYINT (1) NOT NULL DEFAULT 1,
        UNIQUE KEY uq_usuario_correo (correo),
        KEY ix_usuario_empresa (empresa_id),
        KEY ix_usuario_rol (rol_id),
        CONSTRAINT fk_usuario_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id),
        CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id) REFERENCES roles (id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
    productos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT UNSIGNED NOT NULL,
        sku VARCHAR(40) NOT NULL,
        nombre VARCHAR(150) NOT NULL,
        precio DECIMAL(10, 2) UNSIGNED NOT NULL,
        UNIQUE KEY uq_producto_empresa_sku (empresa_id, sku),
        CONSTRAINT fk_producto_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
    login_intentos (
        clave CHAR(64) CHARACTER
        SET
            ascii COLLATE ascii_bin PRIMARY KEY,
            intentos BIGINT UNSIGNED NOT NULL,
            inicio BIGINT UNSIGNED NOT NULL,
            KEY ix_intento_inicio (inicio)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
    insumos (
        id TNT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT UNSIGNED NOT NULL,
        nombre VARCHAR(150) NOT NULL,
        cantidad_actual DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0,
        stock_minimo DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0,
        unidad_medida VARCHAR(20) NOT NULL,
        activo TINYINT (1) NOT NULL DEFAULT 1,
        KEY ix_insumo_empresa (empresa_id),
        CONSTRAINT fk_insumo_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Catalogos RBAC compartidos. Los datos de negocio siguen aislados por empresa_id.
INSERT INTO
    roles (codigo, nombre, descripcion)
VALUES
    (
        'administrador',
        'Administrador',
        'Administra unicamente los recursos de su empresa.'
    ),
    (
        'cajero',
        'Cajero',
        'Registra pedidos, ventas y cobros de su empresa.'
    ),
    (
        'cocinero',
        'Cocinero',
        'Consulta y actualiza la preparacion de pedidos de su empresa.'
    );

INSERT INTO
    permisos (codigo, nombre, descripcion)
VALUES
    (
        'inicio.ver',
        'Ver inicio',
        'Permite acceder a la pagina principal.'
    ),
    (
        'usuarios.ver',
        'Ver usuarios',
        'Permite consultar usuarios de la empresa.'
    ),
    (
        'usuarios.gestionar',
        'Gestionar usuarios',
        'Permite crear, editar y desactivar usuarios de la empresa.'
    ),
    (
        'productos.ver',
        'Ver productos',
        'Permite consultar productos o platillos.'
    ),
    (
        'productos.gestionar',
        'Gestionar productos',
        'Permite crear y modificar productos o platillos.'
    ),
    (
        'inventario.ver',
        'Ver inventario',
        'Permite consultar las existencias de la empresa.'
    ),
    (
        'inventario.gestionar',
        'Gestionar inventario',
        'Permite registrar entradas, salidas y ajustes.'
    ),
    (
        'proveedores.ver',
        'Ver proveedores',
        'Permite consultar proveedores de la empresa.'
    ),
    (
        'proveedores.gestionar',
        'Gestionar proveedores',
        'Permite registrar y modificar proveedores y compras.'
    ),
    (
        'pedidos.ver',
        'Ver pedidos',
        'Permite consultar pedidos de la empresa.'
    ),
    (
        'pedidos.crear',
        'Crear pedidos',
        'Permite registrar y modificar pedidos abiertos.'
    ),
    (
        'pedidos.confirmar',
        'Confirmar pedidos',
        'Permite enviar pedidos a preparacion.'
    ),
    (
        'pedidos.actualizar_cocina',
        'Actualizar cocina',
        'Permite actualizar el estado de preparacion.'
    ),
    (
        'ventas.ver',
        'Ver ventas',
        'Permite consultar las ventas de la empresa.'
    ),
    (
        'ventas.cobrar',
        'Registrar cobros',
        'Permite cobrar pedidos.'
    ),
    (
        'reportes.ver',
        'Ver reportes',
        'Permite consultar reportes administrativos.'
    ),
    (
        'empresa.configurar',
        'Configurar empresa',
        'Permite modificar la configuracion de la empresa.'
    );

-- Administrador recibe todos los permisos del prototipo.
INSERT INTO
    rol_permisos (rol_id, permiso_id)
SELECT
    r.id,
    p.id
FROM
    roles r
    CROSS JOIN permisos p
WHERE
    r.codigo = 'administrador';

INSERT INTO
    rol_permisos (rol_id, permiso_id)
SELECT
    r.id,
    p.id
FROM
    roles r
    CROSS JOIN permisos p
WHERE
    r.codigo = 'cajero'
    AND p.codigo IN (
        'inicio.ver',
        'productos.ver',
        'pedidos.ver',
        'pedidos.crear',
        'pedidos.confirmar',
        'ventas.ver',
        'ventas.cobrar'
    );

INSERT INTO
    rol_permisos (rol_id, permiso_id)
SELECT
    r.id,
    p.id
FROM
    roles r
    CROSS JOIN permisos p
WHERE
    r.codigo = 'cocinero'
    AND p.codigo IN (
        'inicio.ver',
        'productos.ver',
        'pedidos.ver',
        'pedidos.actualizar_cocina'
    );