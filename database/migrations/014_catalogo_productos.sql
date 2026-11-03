-- Ejecutar una sola vez después de las migraciones 001 a 013.
-- No repetir si las columnas y la tabla del catálogo ya existen.

ALTER TABLE productos
ADD COLUMN descripcion VARCHAR(1000) NOT NULL DEFAULT '',
ADD COLUMN disponible TINYINT UNSIGNED NOT NULL DEFAULT 1,
ADD UNIQUE KEY uq_producto_empresa_id (empresa_id, id);

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

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;