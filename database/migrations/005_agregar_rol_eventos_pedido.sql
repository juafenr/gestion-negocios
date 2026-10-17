ALTER TABLE pedido_eventos
    ADD COLUMN rol_codigo VARCHAR(50) NULL AFTER usuario_id;

UPDATE pedido_eventos evento
INNER JOIN usuarios usuario ON usuario.id = evento.usuario_id
INNER JOIN roles rol ON rol.id = usuario.rol_id
SET evento.rol_codigo = rol.codigo
WHERE evento.rol_codigo IS NULL;

ALTER TABLE pedido_eventos
    MODIFY COLUMN rol_codigo VARCHAR(50) NOT NULL;
