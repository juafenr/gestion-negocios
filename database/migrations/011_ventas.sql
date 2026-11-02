-- Base integrada: migraciones de pagos, cocina y nombre de usuario aplicadas. Ejecutar una sola vez.
CREATE TABLE ventas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NOT NULL,
    pedido_numero INT UNSIGNED NOT NULL,
    referencia VARCHAR(150) NOT NULL,
    usuario_id INT UNSIGNED NULL,
    subtotal DECIMAL(12,2) UNSIGNED NOT NULL,
    propina DECIMAL(12,2) UNSIGNED NOT NULL,
    total DECIMAL(12,2) UNSIGNED NOT NULL,
    efectivo DECIMAL(12,2) UNSIGNED NOT NULL,
    credito DECIMAL(12,2) UNSIGNED NOT NULL,
    debito DECIMAL(12,2) UNSIGNED NOT NULL,
    fecha DATETIME NOT NULL,
    origen VARCHAR(20) NOT NULL DEFAULT 'cobro',
    UNIQUE KEY uq_venta_pedido (empresa_id, pedido_id),
    KEY ix_venta_fecha (empresa_id, fecha, id),
    CONSTRAINT fk_venta_pedido FOREIGN KEY (empresa_id, pedido_id) REFERENCES pedidos (empresa_id, id),
    CONSTRAINT fk_venta_usuario FOREIGN KEY (empresa_id, usuario_id) REFERENCES usuarios (empresa_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permisos (codigo,nombre,descripcion) VALUES
('ventas.historial','Historial de ventas','Consulta administrativa de ventas y resumen por fecha.');
INSERT INTO rol_permisos (rol_id,permiso_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permisos p WHERE r.codigo='administrador' AND p.codigo='ventas.historial';

INSERT INTO ventas (empresa_id,pedido_id,pedido_numero,referencia,usuario_id,subtotal,propina,total,efectivo,credito,debito,fecha,origen)
SELECT p.empresa_id,p.id,p.numero,p.referencia,ultimo.usuario_id,p.total,
ROUND(p.total*0.10,2),p.total+ROUND(p.total*0.10,2),
COALESCE(g.efectivo,0),COALESCE(g.credito,0),COALESCE(g.debito,0),
COALESCE(g.fecha,p.confirmado_en,p.creado_en),'historico'
FROM pedidos p
LEFT JOIN (SELECT empresa_id,pedido_id,SUM(total) total,SUM(propina) propina,
 SUM(efectivo) efectivo,SUM(credito) credito,SUM(debito) debito,MAX(creado_en) fecha,MAX(id) ultimo_id
 FROM pagos GROUP BY empresa_id,pedido_id) g ON g.empresa_id=p.empresa_id AND g.pedido_id=p.id
LEFT JOIN pagos ultimo ON ultimo.id=g.ultimo_id AND ultimo.empresa_id=p.empresa_id
WHERE p.estado_pago='pagado' AND p.estado NOT IN ('borrador','cancelado')
AND COALESCE(g.total,0)=p.total+ROUND(p.total*0.10,2)
AND COALESCE(g.propina,0)=ROUND(p.total*0.10,2)
AND COALESCE(g.efectivo,0)+COALESCE(g.credito,0)+COALESCE(g.debito,0)=COALESCE(g.total,0)
AND NOT EXISTS (SELECT 1 FROM ventas v WHERE v.empresa_id=p.empresa_id AND v.pedido_id=p.id);
