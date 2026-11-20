SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS ciudad (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ciudad_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ciudad (nombre) VALUES
  ('Medellín'), ('Bello'), ('Envigado'), ('Itagüí'), ('Sabaneta'),
  ('La Estrella'), ('Copacabana'), ('Girardota'), ('Barbosa'), ('Caldas')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

SET @city_column_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'ciudad_id'
);
SET @city_column_sql = IF(
  @city_column_exists = 0,
  'ALTER TABLE usuario ADD COLUMN ciudad_id INT UNSIGNED DEFAULT NULL AFTER rol_id',
  'SELECT 1'
);
PREPARE city_column_statement FROM @city_column_sql;
EXECUTE city_column_statement;
DEALLOCATE PREPARE city_column_statement;

SET @city_index_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND INDEX_NAME = 'idx_usuario_ciudad'
);
SET @city_index_sql = IF(
  @city_index_exists = 0,
  'ALTER TABLE usuario ADD INDEX idx_usuario_ciudad (ciudad_id)',
  'SELECT 1'
);
PREPARE city_index_statement FROM @city_index_sql;
EXECUTE city_index_statement;
DEALLOCATE PREPARE city_index_statement;

SET @city_fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuario'
    AND CONSTRAINT_NAME = 'fk_usuario_ciudad'
);
SET @city_fk_sql = IF(
  @city_fk_exists = 0,
  'ALTER TABLE usuario ADD CONSTRAINT fk_usuario_ciudad FOREIGN KEY (ciudad_id) REFERENCES ciudad (id)',
  'SELECT 1'
);
PREPARE city_fk_statement FROM @city_fk_sql;
EXECUTE city_fk_statement;
DEALLOCATE PREPARE city_fk_statement;

SET @address_column_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reserva' AND COLUMN_NAME = 'direccion_atencion'
);
SET @address_column_sql = IF(
  @address_column_exists = 0,
  'ALTER TABLE reserva ADD COLUMN direccion_atencion VARCHAR(255) DEFAULT NULL AFTER hora',
  'SELECT 1'
);
PREPARE address_column_statement FROM @address_column_sql;
EXECUTE address_column_statement;
DEALLOCATE PREPARE address_column_statement;

CREATE TABLE IF NOT EXISTS admin_action (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED NOT NULL,
  action VARCHAR(60) NOT NULL,
  entity VARCHAR(60) NOT NULL,
  entity_id INT UNSIGNED DEFAULT NULL,
  details TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_admin_action_admin_date (admin_id, created_at),
  KEY idx_admin_action_entity (entity, entity_id),
  CONSTRAINT fk_admin_action_admin FOREIGN KEY (admin_id) REFERENCES usuario (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calificacion_legacy_refs (
  calificacion_id INT UNSIGNED NOT NULL,
  cliente_id INT UNSIGNED DEFAULT NULL,
  trabajador_id INT UNSIGNED DEFAULT NULL,
  archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (calificacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @rating_client_column_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calificacion' AND COLUMN_NAME = 'cliente_id'
);
SET @rating_worker_column_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calificacion' AND COLUMN_NAME = 'trabajador_id'
);
SET @legacy_rating_columns = @rating_client_column_exists + @rating_worker_column_exists;
SET @archive_rating_refs_sql = IF(
  @legacy_rating_columns = 2,
  'INSERT IGNORE INTO calificacion_legacy_refs (calificacion_id, cliente_id, trabajador_id)
   SELECT id, cliente_id, trabajador_id FROM calificacion',
  'SELECT 1'
);
PREPARE archive_rating_refs_statement FROM @archive_rating_refs_sql;
EXECUTE archive_rating_refs_statement;
DEALLOCATE PREPARE archive_rating_refs_statement;

SET @rating_client_fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'calificacion'
    AND CONSTRAINT_NAME = 'fk_calificacion_cliente'
);
SET @rating_client_fk_sql = IF(
  @rating_client_fk_exists > 0,
  'ALTER TABLE calificacion DROP FOREIGN KEY fk_calificacion_cliente',
  'SELECT 1'
);
PREPARE rating_client_fk_statement FROM @rating_client_fk_sql;
EXECUTE rating_client_fk_statement;
DEALLOCATE PREPARE rating_client_fk_statement;

SET @rating_worker_fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'calificacion'
    AND CONSTRAINT_NAME = 'fk_calificacion_trabajador'
);
SET @rating_worker_fk_sql = IF(
  @rating_worker_fk_exists > 0,
  'ALTER TABLE calificacion DROP FOREIGN KEY fk_calificacion_trabajador',
  'SELECT 1'
);
PREPARE rating_worker_fk_statement FROM @rating_worker_fk_sql;
EXECUTE rating_worker_fk_statement;
DEALLOCATE PREPARE rating_worker_fk_statement;

SET @rating_client_index_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calificacion' AND INDEX_NAME = 'idx_calificacion_cliente'
);
SET @rating_client_index_sql = IF(
  @rating_client_index_exists > 0,
  'ALTER TABLE calificacion DROP INDEX idx_calificacion_cliente',
  'SELECT 1'
);
PREPARE rating_client_index_statement FROM @rating_client_index_sql;
EXECUTE rating_client_index_statement;
DEALLOCATE PREPARE rating_client_index_statement;

SET @rating_worker_index_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calificacion' AND INDEX_NAME = 'idx_calificacion_trabajador'
);
SET @rating_worker_index_sql = IF(
  @rating_worker_index_exists > 0,
  'ALTER TABLE calificacion DROP INDEX idx_calificacion_trabajador',
  'SELECT 1'
);
PREPARE rating_worker_index_statement FROM @rating_worker_index_sql;
EXECUTE rating_worker_index_statement;
DEALLOCATE PREPARE rating_worker_index_statement;

SET @rating_client_column_sql = IF(
  @rating_client_column_exists > 0,
  'ALTER TABLE calificacion DROP COLUMN cliente_id',
  'SELECT 1'
);
PREPARE rating_client_column_statement FROM @rating_client_column_sql;
EXECUTE rating_client_column_statement;
DEALLOCATE PREPARE rating_client_column_statement;

SET @rating_worker_column_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calificacion' AND COLUMN_NAME = 'trabajador_id'
);
SET @rating_worker_column_sql = IF(
  @rating_worker_column_exists > 0,
  'ALTER TABLE calificacion DROP COLUMN trabajador_id',
  'SELECT 1'
);
PREPARE rating_worker_column_statement FROM @rating_worker_column_sql;
EXECUTE rating_worker_column_statement;
DEALLOCATE PREPARE rating_worker_column_statement;
