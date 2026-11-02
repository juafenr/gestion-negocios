-- Ejecutar después de crear pedido_eventos (migraciones 004 y 005 de esta rama).
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
