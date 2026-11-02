-- Permitir también los ajustes que ya utiliza el módulo de inventario.
ALTER TABLE movimientos_inventario
MODIFY COLUMN tipo ENUM('entrada', 'salida', 'ajuste') NOT NULL;

-- Relacionar los movimientos automáticos con su pedido.
-- Los movimientos existentes y manuales conservan NULL.
ALTER TABLE movimientos_inventario
ADD COLUMN pedido_id INT UNSIGNED NULL AFTER usuario_id,

ADD KEY ix_movimiento_empresa_pedido (empresa_id, pedido_id),

ADD CONSTRAINT fk_movimiento_pedido_empresa
    FOREIGN KEY (empresa_id, pedido_id)
    REFERENCES pedidos (empresa_id, id),

ADD UNIQUE KEY uq_movimiento_pedido_insumo_tipo
    (empresa_id, pedido_id, insumo_id, tipo);