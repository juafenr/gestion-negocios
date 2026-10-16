CREATE TABLE pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    referencia VARCHAR(150) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'borrador',
    estado_pago VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    total DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmado_en DATETIME NULL,

    UNIQUE KEY uq_pedido_empresa_id (empresa_id, id),
    KEY ix_pedido_empresa_fecha (empresa_id, creado_en),

    CONSTRAINT fk_pedido_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas(id),

    CONSTRAINT fk_pedido_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE pedido_detalles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    producto_nombre VARCHAR(150) NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    precio_unitario DECIMAL(10,2) UNSIGNED NOT NULL,

    UNIQUE KEY uq_detalle_producto (pedido_id, producto_id),

    CONSTRAINT fk_detalle_pedido_empresa
        FOREIGN KEY (empresa_id, pedido_id)
        REFERENCES pedidos(empresa_id, id),

    CONSTRAINT fk_detalle_producto
        FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;