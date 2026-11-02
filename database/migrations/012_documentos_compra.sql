-- Base compartida de compras. Ejecutar una sola vez después de 011_ventas.sql.
ALTER TABLE empresas ADD ultimo_numero_compra INT UNSIGNED NOT NULL DEFAULT 0;
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
    CONSTRAINT fk_proveedor_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id)
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
    CONSTRAINT fk_solicitud_proveedor FOREIGN KEY (empresa_id, proveedor_id) REFERENCES proveedores (empresa_id, id),
    CONSTRAINT fk_solicitud_creador FOREIGN KEY (empresa_id, creado_por) REFERENCES usuarios (empresa_id, id),
    CONSTRAINT fk_solicitud_editor FOREIGN KEY (empresa_id, actualizado_por) REFERENCES usuarios (empresa_id, id),
    CONSTRAINT fk_solicitud_confirmador FOREIGN KEY (empresa_id, confirmado_por) REFERENCES usuarios (empresa_id, id)
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
    CONSTRAINT fk_linea_solicitud FOREIGN KEY (empresa_id, solicitud_id) REFERENCES solicitudes_compra (empresa_id, id),
    CONSTRAINT fk_linea_insumo FOREIGN KEY (empresa_id, insumo_id) REFERENCES insumos (empresa_id, id)
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
    CONSTRAINT fk_evento_solicitud FOREIGN KEY (empresa_id, solicitud_id) REFERENCES solicitudes_compra (empresa_id, id),
    CONSTRAINT fk_compra_evento_usuario FOREIGN KEY (empresa_id, usuario_id) REFERENCES usuarios (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE insumos ADD proveedor_id INT UNSIGNED NULL, ADD CONSTRAINT fk_insumo_proveedor FOREIGN KEY (empresa_id,proveedor_id) REFERENCES proveedores(empresa_id,id);
INSERT INTO permisos (codigo,nombre,descripcion) VALUES
('compras.ver','Consultar solicitudes','Ver documentos de compra de la empresa.'),
('compras.gestionar','Editar solicitudes','Crear y editar borradores de compra.'),
('compras.confirmar','Confirmar solicitudes','Confirmar documentos revisados.'),
('compras.exportar','Exportar solicitudes','Copiar o descargar documentos para proveedores.');
INSERT INTO rol_permisos (rol_id,permiso_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permisos p WHERE r.codigo='administrador' AND p.codigo IN ('compras.ver','compras.gestionar','compras.confirmar','compras.exportar');
