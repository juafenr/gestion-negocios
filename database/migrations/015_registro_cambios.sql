CREATE TABLE IF NOT EXISTS registro_cambios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    entidad VARCHAR(20) NOT NULL,
    registro_id INT UNSIGNED NOT NULL,
    campo VARCHAR(40) NOT NULL,
    valor_anterior TEXT NULL,
    valor_nuevo TEXT NULL,
    cambiado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_cambios_registro (empresa_id, entidad, registro_id, id),
    CONSTRAINT fk_cambios_usuario FOREIGN KEY (empresa_id, usuario_id) REFERENCES usuarios (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
