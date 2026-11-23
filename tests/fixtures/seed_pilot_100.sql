SET NAMES utf8mb4;

DELETE FROM calificacion;
DELETE FROM reserva;
DELETE FROM servicio;
DELETE FROM admin_action;
DELETE FROM usuario;

CREATE TEMPORARY TABLE pilot_numbers (
  n TINYINT UNSIGNED NOT NULL PRIMARY KEY
) ENGINE=MEMORY;

INSERT INTO pilot_numbers(n)
WITH RECURSIVE numbers(n) AS (
  SELECT 1
  UNION ALL
  SELECT n + 1 FROM numbers WHERE n < 100
)
SELECT n FROM numbers;

INSERT INTO usuario
  (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado)
SELECT
  (SELECT id FROM rol WHERE nombre = 'Administrador'),
  (SELECT id FROM ciudad WHERE nombre = 'Medellín'),
  'Admin', 'Piloto',
  'admin.piloto@example.test',
  '3000000000',
  'PILOT-ADMIN-001',
  'Medellín',
  '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS',
  'activo'
WHERE NOT EXISTS (
  SELECT 1 FROM usuario WHERE email = 'admin.piloto@example.test'
);

INSERT INTO usuario
  (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado)
SELECT
  (SELECT id FROM rol WHERE nombre = 'Trabajador'),
  (SELECT id FROM ciudad WHERE nombre =
    CASE MOD(n.n - 1, 10)
      WHEN 0 THEN 'Medellín'
      WHEN 1 THEN 'Bello'
      WHEN 2 THEN 'Envigado'
      WHEN 3 THEN 'Itagüí'
      WHEN 4 THEN 'Sabaneta'
      WHEN 5 THEN 'La Estrella'
      WHEN 6 THEN 'Copacabana'
      WHEN 7 THEN 'Girardota'
      WHEN 8 THEN 'Barbosa'
      ELSE 'Caldas'
    END),
  CASE MOD(n.n - 1, 20)
    WHEN 0 THEN 'Carlos'
    WHEN 1 THEN 'María'
    WHEN 2 THEN 'Andrés'
    WHEN 3 THEN 'Diana'
    WHEN 4 THEN 'Fernando'
    WHEN 5 THEN 'Liliana'
    WHEN 6 THEN 'Sebastián'
    WHEN 7 THEN 'Natalia'
    WHEN 8 THEN 'Miguel'
    WHEN 9 THEN 'Laura'
    WHEN 10 THEN 'Diego'
    WHEN 11 THEN 'Camila'
    WHEN 12 THEN 'Ricardo'
    WHEN 13 THEN 'Andrea'
    WHEN 14 THEN 'Samuel'
    WHEN 15 THEN 'Manuela'
    WHEN 16 THEN 'Felipe'
    WHEN 17 THEN 'Valentina'
    WHEN 18 THEN 'Julián'
    ELSE 'Paula'
  END,
  CASE MOD(n.n - 1, 20)
    WHEN 0 THEN 'Pérez'
    WHEN 1 THEN 'Gómez'
    WHEN 2 THEN 'Restrepo'
    WHEN 3 THEN 'Montoya'
    WHEN 4 THEN 'Rojas'
    WHEN 5 THEN 'Cano'
    WHEN 6 THEN 'Vargas'
    WHEN 7 THEN 'Herrera'
    WHEN 8 THEN 'Correa'
    WHEN 9 THEN 'Arango'
    WHEN 10 THEN 'Ramírez'
    WHEN 11 THEN 'Zuluaga'
    WHEN 12 THEN 'Jiménez'
    WHEN 13 THEN 'Palacio'
    WHEN 14 THEN 'Cárdenas'
    WHEN 15 THEN 'Giraldo'
    WHEN 16 THEN 'Serrano'
    WHEN 17 THEN 'Suárez'
    WHEN 18 THEN 'López'
    ELSE 'Mendoza'
  END,
  CONCAT('trabajador.piloto.', LPAD(n.n, 2, '0'), '@example.test'),
  CONCAT('300', LPAD(n.n, 7, '0')),
  CONCAT('TR-', LPAD(n.n, 4, '0')),
  CONCAT('Calle ficticia ', n.n),
  '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS',
  'activo'
FROM pilot_numbers n
WHERE n.n <= 20;

INSERT INTO usuario
  (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado)
SELECT
  (SELECT id FROM rol WHERE nombre = 'Cliente'),
  (SELECT id FROM ciudad WHERE nombre =
    CASE MOD(n.n - 1, 10)
      WHEN 0 THEN 'Medellín'
      WHEN 1 THEN 'Bello'
      WHEN 2 THEN 'Envigado'
      WHEN 3 THEN 'Itagüí'
      WHEN 4 THEN 'Sabaneta'
      WHEN 5 THEN 'La Estrella'
      WHEN 6 THEN 'Copacabana'
      WHEN 7 THEN 'Girardota'
      WHEN 8 THEN 'Barbosa'
      ELSE 'Caldas'
    END),
  CASE MOD(n.n - 20, 20)
    WHEN 0 THEN 'Ana'
    WHEN 1 THEN 'Luis'
    WHEN 2 THEN 'Camila'
    WHEN 3 THEN 'Juan'
    WHEN 4 THEN 'Valentina'
    WHEN 5 THEN 'Andrés'
    WHEN 6 THEN 'Mariana'
    WHEN 7 THEN 'Santiago'
    WHEN 8 THEN 'Laura'
    WHEN 9 THEN 'Diego'
    WHEN 10 THEN 'Isabella'
    WHEN 11 THEN 'Sebastián'
    WHEN 12 THEN 'Daniela'
    WHEN 13 THEN 'Mateo'
    WHEN 14 THEN 'Salomé'
    WHEN 15 THEN 'Samuel'
    WHEN 16 THEN 'Manuela'
    WHEN 17 THEN 'Juliana'
    WHEN 18 THEN 'Tomás'
    ELSE 'Natalia'
  END,
  CASE MOD(n.n - 20, 20)
    WHEN 0 THEN 'Ríos'
    WHEN 1 THEN 'Torres'
    WHEN 2 THEN 'Vargas'
    WHEN 3 THEN 'Martínez'
    WHEN 4 THEN 'Gómez'
    WHEN 5 THEN 'Ramírez'
    WHEN 6 THEN 'Díaz'
    WHEN 7 THEN 'Herrera'
    WHEN 8 THEN 'Muñoz'
    WHEN 9 THEN 'Castro'
    WHEN 10 THEN 'Ruiz'
    WHEN 11 THEN 'López'
    WHEN 12 THEN 'Moreno'
    WHEN 13 THEN 'Jiménez'
    WHEN 14 THEN 'Pérez'
    WHEN 15 THEN 'García'
    WHEN 16 THEN 'Rojas'
    WHEN 17 THEN 'Cárdenas'
    WHEN 18 THEN 'Silva'
    ELSE 'Ortiz'
  END,
  CONCAT('cliente.piloto.', LPAD(n.n - 20, 2, '0'), '@example.test'),
  CONCAT('310', LPAD(n.n - 20, 7, '0')),
  CONCAT('CL-', LPAD(n.n - 20, 4, '0')),
  CONCAT('Dirección ficticia ', n.n - 20),
  '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS',
  'activo'
FROM pilot_numbers n
WHERE n.n BETWEEN 21 AND 99;

INSERT INTO servicio
  (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible)
SELECT
  w.id,
  c.id,
  CONCAT(
    CASE MOD(n.n - 1, 4)
      WHEN 0 THEN 'Reparación de fugas'
      WHEN 1 THEN 'Instalación eléctrica residencial'
      WHEN 2 THEN 'Pintura y reparación de paredes'
      ELSE 'Limpieza profunda del hogar'
    END,
    ' - Piloto ',
    LPAD(n.n, 2, '0')
  ),
  'Servicio ficticio creado exclusivamente para pruebas controladas.',
  9000 + n.n * 137,
  'activo',
  1
FROM pilot_numbers n
INNER JOIN (
  SELECT ROW_NUMBER() OVER (ORDER BY u.id) rn, u.id
  FROM usuario u
  INNER JOIN rol r ON r.id=u.rol_id
  WHERE r.nombre='Trabajador'
) w ON w.rn = MOD(n.n - 1, 20) + 1
INNER JOIN categoria c ON c.id = MOD(n.n - 1, 4) + 1
WHERE n.n <= 50;

INSERT INTO reserva
  (cliente_id, servicio_id, fecha, hora, direccion_atencion, estado)
SELECT
  client.id,
  s.id,
  CASE
    WHEN n.n <= 20 THEN DATE_SUB(CURRENT_DATE, INTERVAL n.n + 1 DAY)
    WHEN n.n <= 25 THEN DATE_ADD(CURRENT_DATE, INTERVAL n.n - 19 DAY)
    ELSE DATE_ADD(CURRENT_DATE, INTERVAL n.n - 19 DAY)
  END,
  MAKETIME(8 + MOD(n.n - 1, 10), 0, 0),
  CONCAT('Dirección ficticia ', n.n),
  CASE
    WHEN n.n <= 20 THEN 'finalizada'
    WHEN n.n <= 25 THEN 'pendiente'
    ELSE 'confirmada'
  END
FROM pilot_numbers n
INNER JOIN usuario client
  ON client.email = CONCAT('cliente.piloto.', LPAD(n.n, 2, '0'), '@example.test')
INNER JOIN (
  SELECT ROW_NUMBER() OVER (ORDER BY id) rn, id
  FROM servicio
) s ON s.rn = MOD(n.n - 1, 50) + 1
WHERE n.n <= 30;

INSERT INTO calificacion(reserva_id, estrellas, comentario)
SELECT r.id,
       CASE
         WHEN rn <= 14 THEN 4
         WHEN rn <= 18 THEN 3
         ELSE 2
       END,
       'Calificación ficticia creada para el piloto académico.'
FROM (
  SELECT ROW_NUMBER() OVER (ORDER BY r.id) rn, r.id
  FROM reserva r
  WHERE r.estado = 'finalizada'
) rr
INNER JOIN reserva r ON r.id = rr.id
WHERE rn <= 20;

DROP TEMPORARY TABLE pilot_numbers;

SELECT
  (SELECT COUNT(*) FROM usuario) AS usuarios,
  (SELECT COUNT(*) FROM usuario u INNER JOIN rol r ON r.id=u.rol_id WHERE r.nombre='Cliente') AS clientes,
  (SELECT COUNT(*) FROM usuario u INNER JOIN rol r ON r.id=u.rol_id WHERE r.nombre='Trabajador') AS trabajadores,
  (SELECT COUNT(*) FROM servicio) AS servicios,
  (SELECT COUNT(*) FROM reserva) AS reservas,
  (SELECT COUNT(DISTINCT cliente_id) FROM reserva) AS clientes_distintos,
  (SELECT COUNT(DISTINCT servicio_id) FROM reserva) AS servicios_distintos,
  (SELECT COUNT(*) FROM reserva WHERE estado='finalizada') AS finalizadas,
  (SELECT COUNT(*) FROM calificacion) AS calificaciones,
  (SELECT ROUND(AVG(estrellas),2) FROM calificacion) AS promedio;
