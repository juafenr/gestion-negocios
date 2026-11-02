-- Idempotente. Ejecutar con la aplicación detenida después de crear ventas.
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
