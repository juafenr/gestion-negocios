-- Ejecutar una sola vez, después de 001 a 007. Para bases parcialmente
-- actualizadas, usar tools/actualizar.php en lugar de repetir los SQL.
ALTER TABLE usuarios ADD UNIQUE KEY uq_usuario_empresa_id (empresa_id, id);
ALTER TABLE pedido_detalles ADD UNIQUE KEY uq_detalle_empresa_pedido_id (empresa_id, pedido_id, id);

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
    CONSTRAINT fk_cuenta_pedido FOREIGN KEY (empresa_id, pedido_id) REFERENCES pedidos (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pago_cuenta_detalles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NOT NULL,
    cuenta_id INT UNSIGNED NOT NULL,
    detalle_id INT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_cuenta_detalle (empresa_id, cuenta_id, detalle_id),
    CONSTRAINT fk_reparto_cuenta FOREIGN KEY (empresa_id, pedido_id, cuenta_id) REFERENCES pago_cuentas (empresa_id, pedido_id, id),
    CONSTRAINT fk_reparto_detalle FOREIGN KEY (empresa_id, pedido_id, detalle_id) REFERENCES pedido_detalles (empresa_id, pedido_id, id)
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
    CONSTRAINT fk_pago_cuenta FOREIGN KEY (empresa_id, pedido_id, cuenta_id) REFERENCES pago_cuentas (empresa_id, pedido_id, id),
    CONSTRAINT fk_pago_usuario FOREIGN KEY (empresa_id, usuario_id) REFERENCES usuarios (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
