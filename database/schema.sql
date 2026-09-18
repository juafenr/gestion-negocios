-- Esquema inicial: ejecutar solo sobre una base vacía. Sin DROP ni credenciales.
CREATE TABLE empresas (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(120) NOT NULL,
 activa TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE usuarios (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 empresa_id INT UNSIGNED NOT NULL,
 nombre VARCHAR(100) NOT NULL,
 correo VARCHAR(190) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 activo TINYINT(1) NOT NULL DEFAULT 1,
 UNIQUE KEY uq_usuario_correo (correo),
 KEY ix_usuario_empresa (empresa_id),
 CONSTRAINT fk_usuario_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE productos (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 empresa_id INT UNSIGNED NOT NULL,
 sku VARCHAR(40) NOT NULL,
 nombre VARCHAR(150) NOT NULL,
 precio DECIMAL(10,2) UNSIGNED NOT NULL,
 UNIQUE KEY uq_producto_empresa_sku (empresa_id, sku),
 CONSTRAINT fk_producto_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE login_intentos (
 clave CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 intentos BIGINT UNSIGNED NOT NULL,
 inicio BIGINT UNSIGNED NOT NULL,
 KEY ix_intento_inicio (inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
