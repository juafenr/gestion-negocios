-- Agrega la tabla de insumos a instalaciones existentes.
-- Ejecutar una sola vez, únicamente si la tabla no existe.

CREATE TABLE insumos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    cantidad_actual DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0,
    unidad_medida VARCHAR(20) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,

    KEY ix_insumo_empresa (empresa_id),

    CONSTRAINT fk_insumo_empresa
        FOREIGN KEY (empresa_id)
        REFERENCES empresas(id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;