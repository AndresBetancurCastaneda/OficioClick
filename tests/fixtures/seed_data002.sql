SET NAMES utf8mb4;
SET SESSION cte_max_recursion_depth = 2500;

-- Import only into a disposable QA database after seed_data001.sql.
-- DATA-002 brings the dataset to 200 users, 1,000 services and 2,000 reservations.
CREATE TEMPORARY TABLE fixture_numbers (
    n INT NOT NULL PRIMARY KEY
) ENGINE=MEMORY;

INSERT INTO fixture_numbers (n)
SELECT 1 + ones.n + tens.n * 10 + hundreds.n * 100 + thousands.n * 1000
FROM (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ones
CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) tens
CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) hundreds
CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) thousands
WHERE 1 + ones.n + tens.n * 10 + hundreds.n * 100 + thousands.n * 1000 <= 2000;

INSERT IGNORE INTO usuario
    (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado)
SELECT r.id, c.id, 'Usuario', CONCAT('DATA002-', LPAD(n.n, 4, '0')),
       CONCAT('data002.user', LPAD(n.n, 4, '0'), '@example.test'),
       CONCAT('300', LPAD(n.n, 7, '0')), CONCAT('D2-', LPAD(n.n, 8, '0')),
       'Medellín', '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'activo'
FROM fixture_numbers n
INNER JOIN rol r ON r.nombre = IF(MOD(n.n, 5) = 0, 'Trabajador', 'Cliente')
INNER JOIN ciudad c ON c.nombre = 'Medellín'
WHERE n.n <= 190;

INSERT INTO servicio (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible)
SELECT worker.id, category.id,
       CONCAT('Servicio DATA-002 #', LPAD(n.n, 4, '0')),
       CONCAT('Descripción sintética para prueba de volumen ', n.n, '.'),
       10000 + MOD(n.n, 90000), 'activo', 1
FROM fixture_numbers n
INNER JOIN usuario worker
    ON worker.email = CONCAT('data002.user', LPAD(5 + MOD(n.n - 1, 38) * 5, 4, '0'), '@example.test')
INNER JOIN categoria category
    ON category.nombre = CASE MOD(n.n - 1, 4)
        WHEN 0 THEN 'Plomería'
        WHEN 1 THEN 'Electricidad'
        WHEN 2 THEN 'Obra blanca'
        ELSE 'Aseo general'
    END
WHERE n.n <= 990
  AND NOT EXISTS (
      SELECT 1 FROM servicio existing
      WHERE existing.titulo = CONCAT('Servicio DATA-002 #', LPAD(n.n, 4, '0'))
  );

INSERT INTO reserva (cliente_id, servicio_id, fecha, hora, direccion_atencion, estado)
SELECT client.id, service.id,
       CASE
           WHEN MOD(n.n, 4) = 2
               THEN DATE_SUB(CURRENT_DATE, INTERVAL (FLOOR((n.n - 1) / 38) + 1) DAY)
           ELSE DATE_ADD(CURRENT_DATE, INTERVAL FLOOR((n.n - 1) / 38) DAY)
       END,
       MAKETIME(8 + MOD(FLOOR((n.n - 1) / 38), 10), 0, 0),
       'Medellín',
       CASE MOD(n.n, 4)
           WHEN 0 THEN 'pendiente'
           WHEN 1 THEN 'confirmada'
           WHEN 2 THEN 'finalizada'
           ELSE 'cancelada'
       END
FROM fixture_numbers n
INNER JOIN usuario client
    ON client.email = CONCAT(
        'data002.user',
        LPAD(MOD(n.n - 1, 152) + 1 + FLOOR(MOD(n.n - 1, 152) / 4), 4, '0'),
        '@example.test'
    )
INNER JOIN servicio service
    ON service.titulo = CONCAT(
        'Servicio DATA-002 #',
        LPAD(
            MOD(n.n - 1, 38) + 1 + 38 * MOD(FLOOR((n.n - 1) / 38), 26),
            4,
            '0'
        )
    )
WHERE n.n <= 1994
  AND NOT EXISTS (
      SELECT 1 FROM reserva existing
      WHERE existing.cliente_id = client.id
        AND existing.servicio_id = service.id
        AND existing.fecha = CASE
            WHEN MOD(n.n, 4) = 2
                THEN DATE_SUB(CURRENT_DATE, INTERVAL (FLOOR((n.n - 1) / 38) + 1) DAY)
            ELSE DATE_ADD(CURRENT_DATE, INTERVAL FLOOR((n.n - 1) / 38) DAY)
        END
        AND existing.hora = MAKETIME(8 + MOD(FLOOR((n.n - 1) / 38), 10), 0, 0)
  );

INSERT INTO calificacion (reserva_id, estrellas, comentario)
SELECT r.id, 1 + MOD(r.id, 5), 'Reseña sintética DATA-002 para pruebas de volumen.'
FROM reserva r
INNER JOIN servicio s ON s.id = r.servicio_id
WHERE s.titulo LIKE 'Servicio DATA-002 #%'
  AND r.estado = 'finalizada'
  AND NOT EXISTS (SELECT 1 FROM calificacion c WHERE c.reserva_id = r.id);

DROP TEMPORARY TABLE fixture_numbers;

SELECT
    (SELECT COUNT(*) FROM usuario) AS usuarios,
    (SELECT COUNT(*) FROM servicio) AS servicios,
    (SELECT COUNT(*) FROM reserva) AS reservas;
