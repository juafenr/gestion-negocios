-- Esquema completo con catálogo, usuarios, cocina, pagos, ventas y compras.
-- SOLO para una base VACÍA.
-- Para una base existente: tools/actualizar.php.

CREATE TABLE empresas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    ultimo_numero_pedido INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_numero_compra INT UNSIGNED NOT NULL DEFAULT 0,
    activa TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT fk_rol_permiso_rol
        FOREIGN KEY (rol_id) REFERENCES roles (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_rol_permiso_permiso
        FOREIGN KEY (permiso_id) REFERENCES permisos (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    rol_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    nombre_usuario VARCHAR(40) NOT NULL,
    correo VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_usuario_empresa_id (empresa_id, id),
    UNIQUE KEY uq_usuario_nombre_usuario (nombre_usuario),
    UNIQUE KEY uq_usuario_correo (correo),
    KEY ix_usuario_empresa (empresa_id),
    KEY ix_usuario_rol (rol_id),
    CONSTRAINT fk_usuario_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas (id),
    CONSTRAINT fk_usuario_rol
        FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE proveedores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    contacto VARCHAR(100) NOT NULL DEFAULT '',
    telefono VARCHAR(30) NOT NULL DEFAULT '',
    correo VARCHAR(190) NOT NULL DEFAULT '',
    direccion VARCHAR(255) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_proveedor_empresa_id (empresa_id, id),
    UNIQUE KEY uq_proveedor_nombre (empresa_id, nombre),
    CONSTRAINT fk_proveedor_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE insumos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    cantidad_actual DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    unidad_medida VARCHAR(20) NOT NULL,
    fecha_vencimiento DATE NULL,
    proveedor_id INT UNSIGNED NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_insumo_empresa_nombre (empresa_id, nombre),
    UNIQUE KEY uq_insumo_empresa_id (empresa_id, id),
    KEY ix_insumo_empresa (empresa_id),
    CONSTRAINT fk_insumo_proveedor
        FOREIGN KEY (empresa_id, proveedor_id)
        REFERENCES proveedores (empresa_id, id),
    CONSTRAINT fk_insumo_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo de productos.
CREATE TABLE productos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    insumo_id INT UNSIGNED NULL,
    sku VARCHAR(40) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(1000) NOT NULL DEFAULT '',
    precio DECIMAL(10,2) UNSIGNED NOT NULL,
    disponible TINYINT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_producto_empresa_id (empresa_id, id),
    UNIQUE KEY uq_producto_empresa_insumo (empresa_id, insumo_id),
    UNIQUE KEY uq_producto_empresa_sku (empresa_id, sku),
    CONSTRAINT fk_producto_insumo_empresa
        FOREIGN KEY (empresa_id, insumo_id)
        REFERENCES insumos (empresa_id, id),
    CONSTRAINT fk_producto_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auditoría de cambios de precio.
CREATE TABLE producto_precio_historial (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    precio_anterior DECIMAL(10,2) UNSIGNED NOT NULL,
    precio_nuevo DECIMAL(10,2) UNSIGNED NOT NULL,
    cambiado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_precio_historial_producto (
        empresa_id,
        producto_id,
        cambiado_en
    ),
    CONSTRAINT fk_precio_historial_producto
        FOREIGN KEY (empresa_id, producto_id)
        REFERENCES productos (empresa_id, id),
    CONSTRAINT fk_precio_historial_usuario
        FOREIGN KEY (empresa_id, usuario_id)
        REFERENCES usuarios (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_intentos (
    clave CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    intentos BIGINT UNSIGNED NOT NULL,
    inicio BIGINT UNSIGNED NOT NULL,
    KEY ix_intento_inicio (inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    numero INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    referencia VARCHAR(150) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'borrador',
    estado_pago VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    total DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmado_en DATETIME NULL,
    UNIQUE KEY uq_pedido_empresa_numero (empresa_id, numero),
    UNIQUE KEY uq_pedido_empresa_id (empresa_id, id),
    KEY ix_pedido_empresa_fecha (empresa_id, creado_en),
    CONSTRAINT fk_pedido_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas (id),
    CONSTRAINT fk_pedido_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedido_detalles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    producto_nombre VARCHAR(150) NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    precio_unitario DECIMAL(10,2) UNSIGNED NOT NULL,
    UNIQUE KEY uq_detalle_empresa_pedido_id (empresa_id, pedido_id, id),
    UNIQUE KEY uq_detalle_producto (pedido_id, producto_id),
    CONSTRAINT fk_detalle_pedido_empresa
        FOREIGN KEY (empresa_id, pedido_id)
        REFERENCES pedidos (empresa_id, id),
    CONSTRAINT fk_detalle_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedido_cancelaciones (
    pedido_id INT UNSIGNED NOT NULL PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    motivo VARCHAR(500) NOT NULL,
    estado_anterior VARCHAR(20) NOT NULL,
    cancelado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_cancelacion_empresa (empresa_id, pedido_id),
    CONSTRAINT fk_cancelacion_pedido_empresa
        FOREIGN KEY (empresa_id, pedido_id)
        REFERENCES pedidos (empresa_id, id),
    CONSTRAINT fk_cancelacion_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedido_eventos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    rol_codigo VARCHAR(50) NOT NULL,
    estado_anterior VARCHAR(20) NOT NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_evento_pedido_fecha (empresa_id, pedido_id, creado_en),
    KEY ix_evento_usuario_fecha (empresa_id, usuario_id, creado_en),
    CONSTRAINT fk_evento_pedido_empresa
        FOREIGN KEY (empresa_id, pedido_id)
        REFERENCES pedidos (empresa_id, id),
        CONSTRAINT fk_pedido_eventos_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimientos_inventario (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id INT UNSIGNED NOT NULL,
    insumo_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NULL,
    tipo ENUM('entrada','salida','ajuste') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    cantidad_anterior DECIMAL(10,2) NOT NULL,
    cantidad_nueva DECIMAL(10,2) NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_movimiento_empresa_pedido (empresa_id, pedido_id),
    UNIQUE KEY uq_movimiento_pedido_insumo_tipo (
        empresa_id,
        pedido_id,
        insumo_id,
        tipo
    ),
    INDEX idx_movimiento_empresa_insumo (empresa_id, insumo_id),
    CONSTRAINT fk_movimiento_pedido_empresa
        FOREIGN KEY (empresa_id, pedido_id)
        REFERENCES pedidos (empresa_id, id),
    CONSTRAINT fk_movimiento_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas (id),
    CONSTRAINT fk_movimiento_insumo
        FOREIGN KEY (insumo_id) REFERENCES insumos (id),
    CONSTRAINT fk_movimiento_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pago_cuentas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    es_principal TINYINT UNSIGNED NULL,
    propina DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    total DECIMAL(12,2) UNSIGNED NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cuenta_empresa_pedido_id (empresa_id, pedido_id, id),
    UNIQUE KEY uq_cuenta_nombre (empresa_id, pedido_id, nombre),
    UNIQUE KEY uq_cuenta_principal (empresa_id, pedido_id, es_principal),
    CONSTRAINT fk_cuenta_pedido
        FOREIGN KEY (empresa_id, pedido_id)
        REFERENCES pedidos (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pago_cuenta_detalles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NOT NULL,
    cuenta_id INT UNSIGNED NOT NULL,
    detalle_id INT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_cuenta_detalle (empresa_id, cuenta_id, detalle_id),
    CONSTRAINT fk_reparto_cuenta
        FOREIGN KEY (empresa_id, pedido_id, cuenta_id)
        REFERENCES pago_cuentas (empresa_id, pedido_id, id),
    CONSTRAINT fk_reparto_detalle
        FOREIGN KEY (empresa_id, pedido_id, detalle_id)
        REFERENCES pedido_detalles (empresa_id, pedido_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pagos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NOT NULL,
    cuenta_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    clave CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    efectivo DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    credito DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    debito DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    recibido DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    cambio DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    propina DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    total DECIMAL(12,2) UNSIGNED NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pago_clave (empresa_id, clave),
    KEY ix_pago_empresa_fecha (empresa_id, creado_en),
    CONSTRAINT fk_pago_cuenta
        FOREIGN KEY (empresa_id, pedido_id, cuenta_id)
        REFERENCES pago_cuentas (empresa_id, pedido_id, id),
    CONSTRAINT fk_pago_usuario
        FOREIGN KEY (empresa_id, usuario_id)
        REFERENCES usuarios (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogos RBAC compartidos.
-- Los datos de negocio permanecen aislados por empresa_id.

INSERT INTO roles (codigo, nombre, descripcion) VALUES
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
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.codigo = 'administrador';

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.codigo = 'cajero'
  AND p.codigo IN (
      'inicio.ver',
      'productos.ver',
      'pedidos.ver',
      'pedidos.crear',
      'pedidos.confirmar',
      'ventas.ver',
      'ventas.cobrar'
  );

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.codigo = 'cocinero'
  AND p.codigo IN (
      'inicio.ver',
      'productos.ver',
      'pedidos.ver',
      'pedidos.actualizar_cocina'
  );

INSERT INTO permisos (codigo, nombre, descripcion)
VALUES (
    'pedidos.cancelar',
    'Cancelar pedidos',
    'Autoriza cancelar pedidos de su empresa y registrar el motivo.'
)
ON DUPLICATE KEY UPDATE codigo = VALUES(codigo);

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.codigo = 'administrador'
  AND p.codigo = 'pedidos.cancelar'
  AND NOT EXISTS (
      SELECT 1
      FROM rol_permisos rp
      WHERE rp.rol_id = r.id
        AND rp.permiso_id = p.id
  );

-- Ventas y documentos de compra.

CREATE TABLE ventas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NOT NULL,
    pedido_numero INT UNSIGNED NOT NULL,
    referencia VARCHAR(150) NOT NULL,
    usuario_id INT UNSIGNED NULL,
    subtotal DECIMAL(12,2) UNSIGNED NOT NULL,
    propina DECIMAL(12,2) UNSIGNED NOT NULL,
    total DECIMAL(12,2) UNSIGNED NOT NULL,
    efectivo DECIMAL(12,2) UNSIGNED NOT NULL,
    credito DECIMAL(12,2) UNSIGNED NOT NULL,
    debito DECIMAL(12,2) UNSIGNED NOT NULL,
    fecha DATETIME NOT NULL,
    origen VARCHAR(20) NOT NULL DEFAULT 'cobro',
    UNIQUE KEY uq_venta_pedido (empresa_id, pedido_id),
    KEY ix_venta_fecha (empresa_id, fecha, id),
    CONSTRAINT fk_venta_pedido
        FOREIGN KEY (empresa_id, pedido_id)
        REFERENCES pedidos (empresa_id, id),
    CONSTRAINT fk_venta_usuario
        FOREIGN KEY (empresa_id, usuario_id)
        REFERENCES usuarios (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitudes_compra (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    numero INT UNSIGNED NOT NULL,
    proveedor_id INT UNSIGNED NOT NULL,
    proveedor_nombre VARCHAR(150) NOT NULL,
    proveedor_contacto VARCHAR(100) NOT NULL DEFAULT '',
    proveedor_telefono VARCHAR(30) NOT NULL DEFAULT '',
    proveedor_correo VARCHAR(190) NOT NULL DEFAULT '',
    proveedor_direccion VARCHAR(255) NOT NULL DEFAULT '',
    fecha DATE NOT NULL,
    observaciones VARCHAR(1000) NOT NULL DEFAULT '',
    estado VARCHAR(20) NOT NULL DEFAULT 'borrador',
    version INT UNSIGNED NOT NULL DEFAULT 1,
    clave CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    creado_por INT UNSIGNED NOT NULL,
    actualizado_por INT UNSIGNED NOT NULL,
    confirmado_por INT UNSIGNED NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmado_en DATETIME NULL,
    UNIQUE KEY uq_solicitud_empresa_id (empresa_id, id),
    UNIQUE KEY uq_solicitud_numero (empresa_id, numero),
    UNIQUE KEY uq_solicitud_clave (empresa_id, clave),
    KEY ix_solicitud_proveedor (empresa_id, proveedor_id, fecha),
    CONSTRAINT fk_solicitud_proveedor
        FOREIGN KEY (empresa_id, proveedor_id)
        REFERENCES proveedores (empresa_id, id),
    CONSTRAINT fk_solicitud_creador
        FOREIGN KEY (empresa_id, creado_por)
        REFERENCES usuarios (empresa_id, id),
    CONSTRAINT fk_solicitud_editor
        FOREIGN KEY (empresa_id, actualizado_por)
        REFERENCES usuarios (empresa_id, id),
    CONSTRAINT fk_solicitud_confirmador
        FOREIGN KEY (empresa_id, confirmado_por)
        REFERENCES usuarios (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitud_compra_detalles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    solicitud_id INT UNSIGNED NOT NULL,
    insumo_id INT UNSIGNED NOT NULL,
    insumo_nombre VARCHAR(150) NOT NULL,
    unidad_medida VARCHAR(20) NOT NULL,
    cantidad DECIMAL(10,2) UNSIGNED NOT NULL,
    recibido DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_solicitud_insumo (empresa_id, solicitud_id, insumo_id),
    UNIQUE KEY uq_solicitud_detalle_id (empresa_id, solicitud_id, id),
    CONSTRAINT fk_linea_solicitud
        FOREIGN KEY (empresa_id, solicitud_id)
        REFERENCES solicitudes_compra (empresa_id, id),
    CONSTRAINT fk_linea_insumo
        FOREIGN KEY (empresa_id, insumo_id)
        REFERENCES insumos (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitud_compra_eventos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    solicitud_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    accion VARCHAR(30) NOT NULL,
    estado_anterior VARCHAR(20) NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    comentario VARCHAR(1000) NOT NULL DEFAULT '',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_evento_solicitud (empresa_id, solicitud_id, id),
    CONSTRAINT fk_evento_solicitud
        FOREIGN KEY (empresa_id, solicitud_id)
        REFERENCES solicitudes_compra (empresa_id, id),
    CONSTRAINT fk_compra_evento_usuario
        FOREIGN KEY (empresa_id, usuario_id)
        REFERENCES usuarios (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recepciones_compra (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id INT UNSIGNED NOT NULL,
    solicitud_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    clave CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones VARCHAR(500) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY uq_recepcion_clave (empresa_id, clave),
    UNIQUE KEY uq_recepcion_empresa_id (empresa_id, id),
    KEY idx_recepcion_solicitud (empresa_id, solicitud_id, fecha),
    CONSTRAINT fk_recepcion_solicitud
        FOREIGN KEY (empresa_id, solicitud_id)
        REFERENCES solicitudes_compra (empresa_id, id),
    CONSTRAINT fk_recepcion_usuario
        FOREIGN KEY (empresa_id, usuario_id)
        REFERENCES usuarios (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recepcion_compra_detalles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id INT UNSIGNED NOT NULL,
    recepcion_id BIGINT UNSIGNED NOT NULL,
    solicitud_id INT UNSIGNED NOT NULL,
    detalle_id INT UNSIGNED NOT NULL,
    cantidad DECIMAL(10,2) UNSIGNED NOT NULL,
    movimiento_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recepcion_detalle (empresa_id, recepcion_id, detalle_id),
    UNIQUE KEY uq_recepcion_movimiento (movimiento_id),
    KEY idx_recepcion_detalle_solicitud (empresa_id, solicitud_id, detalle_id),
    CONSTRAINT fk_recepcion_detalle_cabecera
        FOREIGN KEY (empresa_id, recepcion_id)
        REFERENCES recepciones_compra (empresa_id, id),
    CONSTRAINT fk_recepcion_detalle_solicitud
        FOREIGN KEY (empresa_id, solicitud_id, detalle_id)
        REFERENCES solicitud_compra_detalles (empresa_id, solicitud_id, id),
    CONSTRAINT fk_recepcion_detalle_movimiento
        FOREIGN KEY (movimiento_id)
        REFERENCES movimientos_inventario (id),
    CONSTRAINT chk_recepcion_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permisos (codigo, nombre, descripcion) VALUES
(
    'ventas.historial',
    'Historial de ventas',
    'Consulta administrativa de ventas y resumen por fecha.'
);

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.codigo = 'administrador'
  AND p.codigo = 'ventas.historial';

INSERT INTO permisos (codigo, nombre, descripcion) VALUES
('compras.ver', 'Consultar solicitudes', 'Ver documentos de compra de la empresa.'),
('compras.gestionar', 'Editar solicitudes', 'Crear y editar borradores de compra.'),
('compras.confirmar', 'Confirmar solicitudes', 'Confirmar documentos revisados.'),
('compras.exportar', 'Exportar solicitudes', 'Copiar o descargar documentos para proveedores.');

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.codigo = 'administrador'
  AND p.codigo IN (
      'compras.ver',
      'compras.gestionar',
      'compras.confirmar',
      'compras.exportar'
  );