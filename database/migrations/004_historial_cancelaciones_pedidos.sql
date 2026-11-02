CREATE TABLE IF NOT EXISTS pedido_cancelaciones (
    pedido_id INT UNSIGNED NOT NULL PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    motivo VARCHAR(500) NOT NULL,
    estado_anterior VARCHAR(20) NOT NULL,
    cancelado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_cancelacion_empresa (empresa_id, pedido_id),

    CONSTRAINT fk_cancelacion_pedido_empresa
        FOREIGN KEY (empresa_id, pedido_id)
        REFERENCES pedidos(empresa_id, id),

    CONSTRAINT fk_cancelacion_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


START TRANSACTION;

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

COMMIT;