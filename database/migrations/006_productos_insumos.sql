-- Permite comprobar que el insumo y el producto
-- pertenecen a la misma empresa.
ALTER TABLE insumos
ADD UNIQUE KEY uq_insumo_empresa_id (empresa_id, id);

-- Los productos existentes quedan sin vínculo al inventario.
ALTER TABLE productos
ADD COLUMN insumo_id INT UNSIGNED NULL,
ADD UNIQUE KEY uq_producto_empresa_insumo (empresa_id, insumo_id),
ADD CONSTRAINT fk_producto_insumo_empresa
    FOREIGN KEY (empresa_id, insumo_id)
    REFERENCES insumos (empresa_id, id);