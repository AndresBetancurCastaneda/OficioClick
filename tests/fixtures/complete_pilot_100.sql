DELETE FROM calificacion;
DELETE FROM reserva;

INSERT INTO servicio
  (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible)
SELECT
  w.id,
  c.id,
  CONCAT('Servicio adicional piloto ', LPAD(n.n, 2, '0')),
  'Servicio ficticio creado exclusivamente para pruebas controladas.',
  12000 + n.n * 100,
  'activo',
  1
FROM (
  SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
  UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
) n
INNER JOIN usuario w
  ON w.email = CONCAT('trabajador.piloto.', LPAD(MOD(n.n - 1, 20) + 1, 2, '0'), '@example.test')
INNER JOIN categoria c
  ON c.id = MOD(n.n - 1, 4) + 1
WHERE NOT EXISTS (
  SELECT 1 FROM servicio s
  WHERE s.titulo = CONCAT('Servicio adicional piloto ', LPAD(n.n, 2, '0'))
);

INSERT INTO reserva
  (cliente_id, servicio_id, fecha, hora, direccion_atencion, estado)
SELECT
  c.id,
  s.id,
  CASE
    WHEN n.n <= 20 THEN DATE_SUB(CURRENT_DATE, INTERVAL n.n + 1 DAY)
    WHEN n.n <= 25 THEN DATE_ADD(CURRENT_DATE, INTERVAL n.n - 19 DAY)
    ELSE DATE_ADD(CURRENT_DATE, INTERVAL n.n - 19 DAY)
  END,
  MAKETIME(8 + MOD(n.n - 1, 10), 0, 0),
  CONCAT('Dirección ficticia piloto ', n.n),
  CASE
    WHEN n.n <= 20 THEN 'finalizada'
    WHEN n.n <= 25 THEN 'pendiente'
    ELSE 'confirmada'
  END
FROM (
  SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
  UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
  UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29 UNION ALL SELECT 30
) n
INNER JOIN usuario c
  ON c.email = CONCAT('cliente.piloto.', LPAD(n.n, 2, '0'), '@example.test')
INNER JOIN servicio s
  ON s.id = (
    SELECT id
    FROM servicio
    ORDER BY id
    LIMIT 1
  ) + MOD(n.n - 1, 50);

INSERT INTO calificacion(reserva_id, estrellas, comentario)
SELECT r.id, 4, 'Calificación ficticia con valoración alta.'
FROM reserva r
WHERE r.estado = 'finalizada'
ORDER BY r.id
LIMIT 14;

INSERT INTO calificacion(reserva_id, estrellas, comentario)
SELECT r.id, 3, 'Calificación ficticia con valoración media.'
FROM reserva r
WHERE r.estado = 'finalizada'
  AND NOT EXISTS (
    SELECT 1 FROM calificacion c WHERE c.reserva_id = r.id
  )
ORDER BY r.id
LIMIT 4;

INSERT INTO calificacion(reserva_id, estrellas, comentario)
SELECT r.id, 2, 'Calificación ficticia con valoración regular.'
FROM reserva r
WHERE r.estado = 'finalizada'
  AND NOT EXISTS (
    SELECT 1 FROM calificacion c WHERE c.reserva_id = r.id
  )
ORDER BY r.id
LIMIT 2;
