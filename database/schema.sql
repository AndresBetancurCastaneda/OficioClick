SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `oficioclick` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `oficioclick`;

CREATE TABLE IF NOT EXISTS `rol` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rol_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categoria` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(80) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categoria_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ciudad` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ciudad_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuario` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rol_id` INT UNSIGNED NOT NULL,
  `ciudad_id` INT UNSIGNED DEFAULT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(40) DEFAULT NULL,
  `documento_identidad` VARCHAR(30) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `estado` ENUM('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_email` (`email`),
  UNIQUE KEY `uq_usuario_documento` (`documento_identidad`),
  KEY `idx_usuario_rol` (`rol_id`),
  KEY `idx_usuario_ciudad` (`ciudad_id`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`),
  CONSTRAINT `fk_usuario_ciudad` FOREIGN KEY (`ciudad_id`) REFERENCES `ciudad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_action` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(60) NOT NULL,
  `entity` VARCHAR(60) NOT NULL,
  `entity_id` INT UNSIGNED DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_action_admin_date` (`admin_id`, `created_at`),
  KEY `idx_admin_action_entity` (`entity`, `entity_id`),
  CONSTRAINT `fk_admin_action_admin` FOREIGN KEY (`admin_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `servicio` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `trabajador_id` INT UNSIGNED NOT NULL,
  `categoria_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `precio` DECIMAL(10,2) NOT NULL,
  `estado` ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  `disponible` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_servicio_trabajador` (`trabajador_id`),
  KEY `idx_servicio_categoria` (`categoria_id`),
  CONSTRAINT `chk_servicio_precio` CHECK (`precio` > 0),
  CONSTRAINT `fk_servicio_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `fk_servicio_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reserva` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cliente_id` INT UNSIGNED NOT NULL,
  `servicio_id` INT UNSIGNED NOT NULL,
  `fecha` DATE NOT NULL,
  `hora` TIME NOT NULL,
  `direccion_atencion` VARCHAR(255) DEFAULT NULL,
  `estado` ENUM('pendiente','confirmada','finalizada','cancelada') NOT NULL DEFAULT 'pendiente',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reserva_cliente` (`cliente_id`),
  KEY `idx_reserva_servicio` (`servicio_id`),
  CONSTRAINT `fk_reserva_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `fk_reserva_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicio` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `calificacion` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reserva_id` INT UNSIGNED NOT NULL,
  `estrellas` TINYINT UNSIGNED NOT NULL,
  `comentario` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_calificacion_reserva` (`reserva_id`),
  CONSTRAINT `chk_calificacion_estrellas` CHECK (`estrellas` BETWEEN 1 AND 5),
  CONSTRAINT `fk_calificacion_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ciudad` (`nombre`) VALUES
  ('Medellín'), ('Bello'), ('Envigado'), ('Itagüí'), ('Sabaneta'),
  ('La Estrella'), ('Copacabana'), ('Girardota'), ('Barbosa'), ('Caldas')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

INSERT INTO `rol` (`nombre`) VALUES ('Administrador')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);
INSERT INTO `rol` (`nombre`) VALUES ('Trabajador')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);
INSERT INTO `rol` (`nombre`) VALUES ('Cliente')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

INSERT INTO `categoria` (`nombre`) VALUES ('Plomería')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);
INSERT INTO `categoria` (`nombre`) VALUES ('Electricidad')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);
INSERT INTO `categoria` (`nombre`) VALUES ('Obra blanca')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);
INSERT INTO `categoria` (`nombre`) VALUES ('Aseo general')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

INSERT INTO `usuario` (`rol_id`, `ciudad_id`, `nombre`, `apellido`, `email`, `telefono`, `documento_identidad`, `direccion`, `password_hash`, `estado`)
SELECT r.id, c.id, 'Admin', 'OficioClick', 'admin@oficioclick.com', '3000000000', '1000000001', 'Medellín', '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'activo'
FROM `rol` r INNER JOIN `ciudad` c ON c.nombre = 'Medellín'
WHERE r.nombre = 'Administrador'
AND NOT EXISTS (SELECT 1 FROM `usuario` WHERE `email` = 'admin@oficioclick.com');

INSERT INTO `usuario` (`rol_id`, `ciudad_id`, `nombre`, `apellido`, `email`, `telefono`, `documento_identidad`, `direccion`, `password_hash`, `estado`)
SELECT r.id, c.id, 'Carlos', 'Pérez', 'carlos@oficioclick.com', '3001112233', '1000000040', 'Bello', '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'activo'
FROM `rol` r INNER JOIN `ciudad` c ON c.nombre = 'Bello'
WHERE r.nombre = 'Trabajador'
AND NOT EXISTS (SELECT 1 FROM `usuario` WHERE `email` = 'carlos@oficioclick.com');

INSERT INTO `usuario` (`rol_id`, `ciudad_id`, `nombre`, `apellido`, `email`, `telefono`, `documento_identidad`, `direccion`, `password_hash`, `estado`)
SELECT r.id, c.id, 'María', 'Gómez', 'maria@oficioclick.com', '3001112234', '1000000041', 'Envigado', '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'activo'
FROM `rol` r INNER JOIN `ciudad` c ON c.nombre = 'Envigado'
WHERE r.nombre = 'Trabajador'
AND NOT EXISTS (SELECT 1 FROM `usuario` WHERE `email` = 'maria@oficioclick.com');

INSERT INTO `usuario` (`rol_id`, `ciudad_id`, `nombre`, `apellido`, `email`, `telefono`, `documento_identidad`, `direccion`, `password_hash`, `estado`)
SELECT r.id, c.id, 'Ana', 'Ríos', 'ana@oficioclick.com', '3002223344', '1000000042', 'Medellín', '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'activo'
FROM `rol` r INNER JOIN `ciudad` c ON c.nombre = 'Medellín'
WHERE r.nombre = 'Cliente'
AND NOT EXISTS (SELECT 1 FROM `usuario` WHERE `email` = 'ana@oficioclick.com');

INSERT INTO `usuario` (`rol_id`, `ciudad_id`, `nombre`, `apellido`, `email`, `telefono`, `documento_identidad`, `direccion`, `password_hash`, `estado`)
SELECT r.id, c.id, 'Luis', 'Torres', 'luis@oficioclick.com', '3002223345', '1000000043', 'Itagüí', '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'activo'
FROM `rol` r INNER JOIN `ciudad` c ON c.nombre = 'Itagüí'
WHERE r.nombre = 'Cliente'
AND NOT EXISTS (SELECT 1 FROM `usuario` WHERE `email` = 'luis@oficioclick.com');

INSERT INTO `servicio` (`trabajador_id`, `categoria_id`, `titulo`, `descripcion`, `precio`, `estado`, `disponible`)
SELECT u.id, c.id, 'Reparación de fugas', 'Solución rápida de fugas en cocina, baño y tuberías.', 12000, 'activo', 1
FROM `usuario` u
JOIN `categoria` c ON c.nombre = 'Plomería'
WHERE u.email = 'carlos@oficioclick.com'
AND NOT EXISTS (SELECT 1 FROM `servicio` WHERE `titulo` = 'Reparación de fugas');

INSERT INTO `servicio` (`trabajador_id`, `categoria_id`, `titulo`, `descripcion`, `precio`, `estado`, `disponible`)
SELECT u.id, c.id, 'Instalación eléctrica residencial', 'Instalación y revisión de luminarias, enchufes y circuitos.', 15000, 'activo', 1
FROM `usuario` u
JOIN `categoria` c ON c.nombre = 'Electricidad'
WHERE u.email = 'maria@oficioclick.com'
AND NOT EXISTS (SELECT 1 FROM `servicio` WHERE `titulo` = 'Instalación eléctrica residencial');

INSERT INTO `servicio` (`trabajador_id`, `categoria_id`, `titulo`, `descripcion`, `precio`, `estado`, `disponible`)
SELECT u.id, c.id, 'Pintura y reparación de paredes', 'Pintura interior, empastes y acabados para hogares.', 9000, 'activo', 1
FROM `usuario` u
JOIN `categoria` c ON c.nombre = 'Obra blanca'
WHERE u.email = 'carlos@oficioclick.com'
AND NOT EXISTS (SELECT 1 FROM `servicio` WHERE `titulo` = 'Pintura y reparación de paredes');

INSERT INTO `servicio` (`trabajador_id`, `categoria_id`, `titulo`, `descripcion`, `precio`, `estado`, `disponible`)
SELECT u.id, c.id, 'Limpieza profunda del hogar', 'Aseo general y detalles para vivienda y oficinas.', 11000, 'activo', 1
FROM `usuario` u
JOIN `categoria` c ON c.nombre = 'Aseo general'
WHERE u.email = 'maria@oficioclick.com'
AND NOT EXISTS (SELECT 1 FROM `servicio` WHERE `titulo` = 'Limpieza profunda del hogar');

INSERT INTO `reserva` (`cliente_id`, `servicio_id`, `fecha`, `hora`, `estado`)
SELECT c.id, s.id, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), '09:00:00', 'pendiente'
FROM `usuario` c
JOIN `servicio` s ON s.titulo = 'Reparación de fugas'
WHERE c.email = 'ana@oficioclick.com'
AND NOT EXISTS (SELECT 1 FROM `reserva` WHERE `servicio_id` = s.id AND `cliente_id` = c.id);

INSERT INTO `reserva` (`cliente_id`, `servicio_id`, `fecha`, `hora`, `estado`)
SELECT c.id, s.id, DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), '15:30:00', 'finalizada'
FROM `usuario` c
JOIN `servicio` s ON s.titulo = 'Instalación eléctrica residencial'
WHERE c.email = 'luis@oficioclick.com'
AND NOT EXISTS (SELECT 1 FROM `reserva` WHERE `servicio_id` = s.id AND `cliente_id` = c.id);

INSERT INTO `calificacion` (`reserva_id`, `estrellas`, `comentario`)
SELECT r.id, 5, 'Servicio muy puntual y bien realizado.'
FROM `reserva` r
WHERE r.estado = 'finalizada'
AND NOT EXISTS (SELECT 1 FROM `calificacion` WHERE `reserva_id` = r.id);
