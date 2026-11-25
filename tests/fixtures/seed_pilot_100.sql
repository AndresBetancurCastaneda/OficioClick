-- DESTRUCTIVE: use only in a disposable database; replaces user, service, reservation, review, and audit data.
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
  'Admin', 'OficioClick',
  'admin@example.test',
  '3000000000',
  'SIM-ADMIN-001',
  'Medellín',
  '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS',
  'activo'
WHERE NOT EXISTS (
  SELECT 1 FROM usuario WHERE email = 'admin@example.test'
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
  CONCAT('trabajador.', LPAD(n.n, 3, '0'), '@example.test'),
  CONCAT('300', LPAD(n.n, 7, '0')),
  CONCAT('SIM-W-', LPAD(n.n, 4, '0')),
  CONCAT('Dirección de prueba ', LPAD(n.n, 3, '0')),
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
  CONCAT('cliente.', LPAD(n.n - 20, 3, '0'), '@example.test'),
  CONCAT('310', LPAD(n.n - 20, 7, '0')),
  CONCAT('SIM-C-', LPAD(n.n - 20, 4, '0')),
  CONCAT('Dirección de prueba ', LPAD(n.n - 20, 3, '0')),
  '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS',
  'activo'
FROM pilot_numbers n
WHERE n.n BETWEEN 21 AND 99;

INSERT INTO servicio
  (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible)
SELECT
  w.id,
  c.id,
  CASE MOD(n.n - 1, 4)
    WHEN 0 THEN CASE FLOOR((n.n - 1) / 4)
      WHEN 0 THEN 'Reparación de fugas en baños'
      WHEN 1 THEN 'Cambio de grifería de cocina'
      WHEN 2 THEN 'Mantenimiento de tuberías'
      WHEN 3 THEN 'Instalación de sanitarios'
      WHEN 4 THEN 'Destape de desagües'
      WHEN 5 THEN 'Revisión de presión de agua'
      WHEN 6 THEN 'Reparación de duchas'
      WHEN 7 THEN 'Instalación de lavaplatos'
      WHEN 8 THEN 'Mantenimiento de tanques de agua'
      WHEN 9 THEN 'Cambio de sifones'
      WHEN 10 THEN 'Instalación de llaves de paso'
      WHEN 11 THEN 'Mantenimiento de bombas de agua'
      ELSE 'Inspección de redes de desagüe'
    END
    WHEN 1 THEN CASE FLOOR((n.n - 1) / 4)
      WHEN 0 THEN 'Instalación de lámparas y luminarias'
      WHEN 1 THEN 'Revisión de tomacorrientes'
      WHEN 2 THEN 'Cambio de cableado residencial'
      WHEN 3 THEN 'Instalación de ventiladores'
      WHEN 4 THEN 'Mantenimiento de tableros eléctricos'
      WHEN 5 THEN 'Reparación de fallas eléctricas'
      WHEN 6 THEN 'Instalación de interruptores'
      WHEN 7 THEN 'Revisión de puesta a tierra'
      WHEN 8 THEN 'Instalación de puntos eléctricos'
      WHEN 9 THEN 'Cambio de breakers'
      WHEN 10 THEN 'Automatización de iluminación'
      WHEN 11 THEN 'Instalación de sensores de movimiento'
      ELSE 'Mantenimiento de sistemas de respaldo eléctrico'
    END
    WHEN 2 THEN CASE FLOOR((n.n - 1) / 4)
      WHEN 0 THEN 'Pintura interior de habitaciones'
      WHEN 1 THEN 'Resane y pintura de paredes'
      WHEN 2 THEN 'Pintura de techos'
      WHEN 3 THEN 'Acabados para obra blanca'
      WHEN 4 THEN 'Reparación de grietas'
      WHEN 5 THEN 'Pintura de puertas y marcos'
      WHEN 6 THEN 'Instalación de papel tapiz'
      WHEN 7 THEN 'Estuco y acabado de muros'
      WHEN 8 THEN 'Pintura de fachadas'
      WHEN 9 THEN 'Mantenimiento de drywall'
      WHEN 10 THEN 'Impermeabilización de paredes'
      WHEN 11 THEN 'Mantenimiento de enchapes y pisos'
      ELSE 'Restauración de acabados decorativos'
    END
    ELSE CASE FLOOR((n.n - 1) / 4)
      WHEN 0 THEN 'Limpieza profunda de apartamentos'
      WHEN 1 THEN 'Aseo general de vivienda'
      WHEN 2 THEN 'Limpieza de cocina y baños'
      WHEN 3 THEN 'Limpieza para entrega de inmueble'
      WHEN 4 THEN 'Limpieza de ventanas y balcones'
      WHEN 5 THEN 'Desinfección de espacios'
      WHEN 6 THEN 'Limpieza de muebles y tapicería'
      WHEN 7 THEN 'Aseo antes y después de mudanza'
      WHEN 8 THEN 'Limpieza de oficina'
      WHEN 9 THEN 'Limpieza y mantenimiento de pisos'
      WHEN 10 THEN 'Limpieza de nevera y horno'
      WHEN 11 THEN 'Limpieza de patios y terrazas'
      ELSE 'Organización y aseo del hogar'
    END
  END,
  CASE MOD(n.n - 1, 4)
    WHEN 0 THEN 'Revisión y mantenimiento de instalaciones hidráulicas residenciales.'
    WHEN 1 THEN 'Instalación y mantenimiento de redes eléctricas residenciales.'
    WHEN 2 THEN 'Reparación y acabado de superficies para viviendas y locales.'
    ELSE 'Limpieza detallada de espacios residenciales y comerciales.'
  END,
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
  CONCAT('Dirección de prueba ', LPAD(n.n, 3, '0')),
  CASE
    WHEN n.n <= 20 THEN 'finalizada'
    WHEN n.n <= 25 THEN 'pendiente'
    ELSE 'confirmada'
  END
FROM pilot_numbers n
INNER JOIN usuario client
  ON client.email = CONCAT('cliente.', LPAD(n.n, 3, '0'), '@example.test')
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
       'El servicio fue puntual y el trabajo quedó bien realizado.'
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
