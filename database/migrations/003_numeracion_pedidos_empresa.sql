-- Numeración visible de pedidos independiente para cada empresa.
-- Ejecutar UNA SOLA VEZ sobre una base existente con el módulo de pedidos.
-- Hacer un respaldo y no utilizar el sistema durante la actualización.
-- No ejecutar si estos cambios ya se aplicaron manualmente o si la base
-- se instaló con el schema.sql actualizado que acompaña este archivo.
-- Los IDs, las relaciones y los datos de los pedidos se conservan.

ALTER TABLE empresas
ADD COLUMN ultimo_numero_pedido INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE pedidos
ADD COLUMN numero INT UNSIGNED NULL AFTER empresa_id;

-- Asignar 1, 2, 3... a los pedidos existentes de cada empresa por orden de ID.
-- La tabla temporal permite actualizar sin consultar la misma tabla de destino
-- en una subconsulta y funciona sin depender de funciones de ventana.
CREATE TEMPORARY TABLE tmp_numeracion_pedidos AS
SELECT
    p.id,
    COUNT(anterior.id) AS numero
FROM pedidos p
JOIN pedidos anterior
    ON anterior.empresa_id = p.empresa_id
    AND anterior.id <= p.id
GROUP BY p.id;

UPDATE pedidos p
JOIN tmp_numeracion_pedidos n ON n.id = p.id
SET p.numero = n.numero;

-- Continuar la secuencia desde el último número existente.
UPDATE empresas e
LEFT JOIN (
    SELECT empresa_id, MAX(numero) AS ultimo
    FROM pedidos
    GROUP BY empresa_id
) resumen ON resumen.empresa_id = e.id
SET e.ultimo_numero_pedido = COALESCE(resumen.ultimo, 0);

DROP TEMPORARY TABLE tmp_numeracion_pedidos;

-- El número solo puede repetirse entre empresas diferentes.
ALTER TABLE pedidos
MODIFY COLUMN numero INT UNSIGNED NOT NULL,
ADD UNIQUE KEY uq_pedido_empresa_numero (empresa_id, numero);
