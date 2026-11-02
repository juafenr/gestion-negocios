-- RF-59 y RF-60
-- Recepciones totales y parciales de compras
-- Ejecutar una sola vez después de la migración 010.
CREATE TABLE
    recepciones_compra (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id INT UNSIGNED NOT NULL,
        solicitud_id INT UNSIGNED NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        -- Identificador único de la operación
        clave CHAR(64) CHARACTER
        SET
            ascii COLLATE ascii_bin NOT NULL,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            observaciones VARCHAR(500) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY uq_recepcion_clave (empresa_id, clave),
            UNIQUE KEY uq_recepcion_empresa_id (empresa_id, id),
            KEY idx_recepcion_solicitud (empresa_id, solicitud_id, fecha),
            CONSTRAINT fk_recepcion_solicitud FOREIGN KEY (empresa_id, solicitud_id) REFERENCES solicitudes_compra (empresa_id, id),
            CONSTRAINT fk_recepcion_usuario FOREIGN KEY (empresa_id, usuario_id) REFERENCES usuarios (empresa_id, id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
    recepcion_compra_detalles (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id INT UNSIGNED NOT NULL,
        recepcion_id BIGINT UNSIGNED NOT NULL,
        solicitud_id INT UNSIGNED NOT NULL,
        detalle_id INT UNSIGNED NOT NULL,
        cantidad DECIMAL(10, 2) UNSIGNED NOT NULL,
        movimiento_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_recepcion_detalle (empresa_id, recepcion_id, detalle_id),
        UNIQUE KEY uq_recepcion_movimiento (movimiento_id),
        KEY idx_recepcion_detalle_solicitud (empresa_id, solicitud_id, detalle_id),
        CONSTRAINT fk_recepcion_detalle_cabecera FOREIGN KEY (empresa_id, recepcion_id) REFERENCES recepciones_compra (empresa_id, id),
        CONSTRAINT fk_recepcion_detalle_solicitud FOREIGN KEY (empresa_id, solicitud_id, detalle_id) REFERENCES solicitud_compra_detalles (empresa_id, solicitud_id, id),
        CONSTRAINT fk_recepcion_detalle_movimiento FOREIGN KEY (movimiento_id) REFERENCES movimientos_inventario (id),
        CONSTRAINT chk_recepcion_cantidad CHECK (cantidad > 0)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;