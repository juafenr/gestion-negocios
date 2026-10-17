CREATE TABLE pedido_eventos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(20) NOT NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_evento_pedido_fecha (empresa_id, pedido_id, creado_en),
    KEY ix_evento_usuario_fecha (empresa_id, usuario_id, creado_en),

    CONSTRAINT fk_evento_pedido_empresa
        FOREIGN KEY (empresa_id, pedido_id)
        REFERENCES pedidos (empresa_id, id),

    CONSTRAINT fk_evento_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
