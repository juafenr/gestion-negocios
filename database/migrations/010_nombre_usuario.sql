-- Ejecutar una vez en bases existentes. Los identificadores anteriores
-- quedan visibles y editables desde Usuarios después de actualizar el código.
ALTER TABLE usuarios ADD COLUMN nombre_usuario VARCHAR(40) NULL AFTER nombre;
UPDATE usuarios SET nombre_usuario = CONCAT('usuario', id) WHERE nombre_usuario IS NULL;
ALTER TABLE usuarios MODIFY nombre_usuario VARCHAR(40) NOT NULL;
ALTER TABLE usuarios ADD UNIQUE KEY uq_usuario_nombre_usuario (nombre_usuario);
