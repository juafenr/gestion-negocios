ALTER TABLE insumos
ADD COLUMN fecha_vencimiento DATE NULL AFTER unidad_medida;

ALTER TABLE insumos ADD UNIQUE KEY uq_insumo_empresa_nombre (empresa_id, nombre);

CREATE TABLE
    movimientos_inventario (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id INT UNSIGNED NOT NULL,
        insumo_id INT unsigned NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        tipo ENUM ('entrada', 'salida') NOT NULL,
        cantidad DECIMAL(10, 2) NOT NULL,
        cantidad_anterior DECIMAL(10, 2) NOT NULL,
        cantidad_nueva DECIMAL(10, 2) NOT NULL,
        motivo VARCHAR(255) NOT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_movimiento_empresa_insumo (empresa_id, insumo_id),
        FOREIGN KEY (empresa_id) REFERENCES empresas (id),
        FOREIGN KEY (insumo_id) REFERENCES insumos (id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;