-- Ejecutar después de 004_registrar_transiciones_pedido.sql y
-- 005_agregar_rol_eventos_pedido.sql. No sustituye las migraciones 004/005
-- de cancelaciones e inventario que provienen de upstream/main.
-- Conserva los pedidos y su historial al unificar el estado con pagos y cancelación.
UPDATE pedidos
SET estado = 'en_preparacion'
WHERE estado = 'preparacion';

UPDATE pedido_eventos
SET estado_anterior = 'en_preparacion'
WHERE estado_anterior = 'preparacion';

UPDATE pedido_eventos
SET estado_nuevo = 'en_preparacion'
WHERE estado_nuevo = 'preparacion';
