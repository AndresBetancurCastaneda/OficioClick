SET NAMES utf8mb4;

CREATE TEMPORARY TABLE service_copy (
  n TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description VARCHAR(255) NOT NULL
) ENGINE=MEMORY;

INSERT INTO service_copy (n, title, description)
WITH RECURSIVE numbers(n) AS (
  SELECT 1
  UNION ALL
  SELECT n + 1 FROM numbers WHERE n < 50
)
SELECT
  n.n,
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
      ELSE 'Revisión de calentadores de agua'
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
      ELSE 'Diagnóstico de consumo eléctrico'
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
      ELSE 'Pintura de sala y comedor'
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
      ELSE 'Organización y aseo del hogar'
    END
  END,
  CASE MOD(n.n - 1, 4)
    WHEN 0 THEN 'Revisión y mantenimiento de instalaciones hidráulicas residenciales.'
    WHEN 1 THEN 'Instalación y mantenimiento de redes eléctricas residenciales.'
    WHEN 2 THEN 'Reparación y acabado de superficies para viviendas y locales.'
    ELSE 'Limpieza detallada de espacios residenciales y comerciales.'
  END
FROM numbers n;

START TRANSACTION;

UPDATE servicio s
INNER JOIN service_copy copy_data
  ON copy_data.n = CAST(SUBSTRING_INDEX(s.titulo, ' - Piloto ', -1) AS UNSIGNED)
SET s.titulo = copy_data.title,
    s.descripcion = copy_data.description
WHERE s.titulo LIKE '% - Piloto %';

UPDATE usuario
SET email = CONCAT(
  REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(nombre), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'), ' ', '.'),
  '.',
  REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(apellido), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'), ' ', '.'),
  '.',
  LPAD(id, 4, '0'),
  '@example.test'
)
WHERE email LIKE 'piloto.%@example.test'
   OR (email LIKE '% %' AND email LIKE '%@example.test');

UPDATE reserva
SET direccion_atencion = CONCAT(
  'Dirección de prueba ',
  LPAD(CAST(SUBSTRING_INDEX(direccion_atencion, ' ', -1) AS UNSIGNED), 3, '0')
)
WHERE direccion_atencion LIKE 'Dirección ficticia de prueba piloto %';

COMMIT;
DROP TEMPORARY TABLE service_copy;

SELECT
  (SELECT COUNT(*) FROM usuario) AS usuarios,
  (SELECT COUNT(*) FROM servicio) AS servicios,
  (SELECT COUNT(*) FROM reserva) AS reservas,
  (SELECT COUNT(*) FROM calificacion) AS calificaciones,
  (SELECT COUNT(*) FROM usuario WHERE email LIKE 'piloto.%@example.test') AS usuarios_pendientes_de_normalizar,
  (SELECT COUNT(*) FROM servicio WHERE titulo LIKE '% - Piloto %') AS servicios_pendientes_de_normalizar;
