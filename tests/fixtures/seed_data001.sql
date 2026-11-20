SET NAMES utf8mb4;

-- Import only into a disposable QA database after database/schema.sql.
-- DATA-001 adds five users, six services and four reservations to the base seed.
INSERT INTO usuario
    (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado)
SELECT r.id, c.id, 'Diana', 'Prueba', 'data001.worker01@example.test', '3000000101', 'D1-00000101', 'Medellín',
       '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'activo'
FROM rol r
INNER JOIN ciudad c ON c.nombre = 'Medellín'
WHERE r.nombre = 'Trabajador'
  AND NOT EXISTS (SELECT 1 FROM usuario WHERE email = 'data001.worker01@example.test');

INSERT INTO usuario
    (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado)
SELECT r.id, c.id, 'Julián', 'Prueba', 'data001.worker02@example.test', '3000000102', 'D1-00000102', 'Bello',
       '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'activo'
FROM rol r
INNER JOIN ciudad c ON c.nombre = 'Bello'
WHERE r.nombre = 'Trabajador'
  AND NOT EXISTS (SELECT 1 FROM usuario WHERE email = 'data001.worker02@example.test');

INSERT INTO usuario
    (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado)
SELECT r.id, c.id, 'Sofía', 'Prueba', 'data001.client01@example.test', '3000000103', 'D1-00000103', 'Envigado',
       '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'activo'
FROM rol r
INNER JOIN ciudad c ON c.nombre = 'Envigado'
WHERE r.nombre = 'Cliente'
  AND NOT EXISTS (SELECT 1 FROM usuario WHERE email = 'data001.client01@example.test');

INSERT INTO usuario
    (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado)
SELECT r.id, c.id, 'Mateo', 'Prueba', 'data001.client02@example.test', '3000000104', 'D1-00000104', 'Itagüí',
       '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'activo'
FROM rol r
INNER JOIN ciudad c ON c.nombre = 'Itagüí'
WHERE r.nombre = 'Cliente'
  AND NOT EXISTS (SELECT 1 FROM usuario WHERE email = 'data001.client02@example.test');

INSERT INTO usuario
    (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado)
SELECT r.id, c.id, 'Valentina', 'Prueba', 'data001.client03@example.test', '3000000105', 'D1-00000105', 'Sabaneta',
       '$2y$10$.NbKWZsEOhGUMgrtq6HYCuH2OcSmTelpKdxrjp9EpHfeT1.M3f4pS', 'inactivo'
FROM rol r
INNER JOIN ciudad c ON c.nombre = 'Sabaneta'
WHERE r.nombre = 'Cliente'
  AND NOT EXISTS (SELECT 1 FROM usuario WHERE email = 'data001.client03@example.test');

INSERT INTO servicio (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible)
SELECT u.id, c.id, 'DATA-001 Reparación de grifería', 'Reparación de grifos y llaves de paso para pruebas QA.', 18000, 'activo', 1
FROM usuario u
INNER JOIN categoria c ON c.nombre = 'Plomería'
WHERE u.email = 'data001.worker01@example.test'
  AND NOT EXISTS (SELECT 1 FROM servicio WHERE titulo = 'DATA-001 Reparación de grifería');

INSERT INTO servicio (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible)
SELECT u.id, c.id, 'DATA-001 Revisión de tomacorrientes', 'Revisión eléctrica residencial para pruebas QA.', 22000, 'activo', 1
FROM usuario u
INNER JOIN categoria c ON c.nombre = 'Electricidad'
WHERE u.email = 'data001.worker02@example.test'
  AND NOT EXISTS (SELECT 1 FROM servicio WHERE titulo = 'DATA-001 Revisión de tomacorrientes');

INSERT INTO servicio (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible)
SELECT u.id, c.id, 'DATA-001 Pintura de habitación', 'Pintura interior para pruebas QA.', 25000, 'activo', 1
FROM usuario u
INNER JOIN categoria c ON c.nombre = 'Obra blanca'
WHERE u.email = 'data001.worker01@example.test'
  AND NOT EXISTS (SELECT 1 FROM servicio WHERE titulo = 'DATA-001 Pintura de habitación');

INSERT INTO servicio (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible)
SELECT u.id, c.id, 'DATA-001 Aseo de apartamento', 'Aseo general para pruebas QA.', 20000, 'activo', 1
FROM usuario u
INNER JOIN categoria c ON c.nombre = 'Aseo general'
WHERE u.email = 'data001.worker02@example.test'
  AND NOT EXISTS (SELECT 1 FROM servicio WHERE titulo = 'DATA-001 Aseo de apartamento');

INSERT INTO servicio (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible)
SELECT u.id, c.id, 'DATA-001 Instalación de lavaplatos', 'Instalación de lavaplatos para pruebas QA.', 30000, 'activo', 1
FROM usuario u
INNER JOIN categoria c ON c.nombre = 'Plomería'
WHERE u.email = 'data001.worker01@example.test'
  AND NOT EXISTS (SELECT 1 FROM servicio WHERE titulo = 'DATA-001 Instalación de lavaplatos');

INSERT INTO servicio (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible)
SELECT u.id, c.id, 'DATA-001 Mantenimiento de luminarias', 'Mantenimiento eléctrico para pruebas QA.', 24000, 'activo', 1
FROM usuario u
INNER JOIN categoria c ON c.nombre = 'Electricidad'
WHERE u.email = 'data001.worker02@example.test'
  AND NOT EXISTS (SELECT 1 FROM servicio WHERE titulo = 'DATA-001 Mantenimiento de luminarias');

INSERT INTO reserva (cliente_id, servicio_id, fecha, hora, direccion_atencion, estado)
SELECT client.id, service.id, DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY), '10:00:00', 'Envigado', 'confirmada'
FROM usuario client
CROSS JOIN servicio service
WHERE client.email = 'ana@oficioclick.com'
  AND service.titulo = 'DATA-001 Reparación de grifería'
  AND NOT EXISTS (
      SELECT 1 FROM reserva r
      WHERE r.cliente_id = client.id AND r.servicio_id = service.id
        AND r.fecha = DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY) AND r.hora = '10:00:00'
  );

INSERT INTO reserva (cliente_id, servicio_id, fecha, hora, direccion_atencion, estado)
SELECT client.id, service.id, DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), '11:00:00', 'Medellín', 'finalizada'
FROM usuario client
CROSS JOIN servicio service
WHERE client.email = 'luis@oficioclick.com'
  AND service.titulo = 'DATA-001 Revisión de tomacorrientes'
  AND NOT EXISTS (
      SELECT 1 FROM reserva r
      WHERE r.cliente_id = client.id AND r.servicio_id = service.id
        AND r.fecha = DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY) AND r.hora = '11:00:00'
  );

INSERT INTO reserva (cliente_id, servicio_id, fecha, hora, direccion_atencion, estado)
SELECT client.id, service.id, DATE_ADD(CURRENT_DATE, INTERVAL 12 DAY), '13:00:00', 'Bello', 'cancelada'
FROM usuario client
CROSS JOIN servicio service
WHERE client.email = 'data001.client01@example.test'
  AND service.titulo = 'DATA-001 Pintura de habitación'
  AND NOT EXISTS (
      SELECT 1 FROM reserva r
      WHERE r.cliente_id = client.id AND r.servicio_id = service.id
        AND r.fecha = DATE_ADD(CURRENT_DATE, INTERVAL 12 DAY) AND r.hora = '13:00:00'
  );

INSERT INTO reserva (cliente_id, servicio_id, fecha, hora, direccion_atencion, estado)
SELECT client.id, service.id, DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY), '14:00:00', 'Itagüí', 'pendiente'
FROM usuario client
CROSS JOIN servicio service
WHERE client.email = 'data001.client02@example.test'
  AND service.titulo = 'DATA-001 Aseo de apartamento'
  AND NOT EXISTS (
      SELECT 1 FROM reserva r
      WHERE r.cliente_id = client.id AND r.servicio_id = service.id
        AND r.fecha = DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY) AND r.hora = '14:00:00'
  );

INSERT INTO calificacion (reserva_id, estrellas, comentario)
SELECT r.id, 5, 'Calificación sintética para pruebas QA.'
FROM reserva r
INNER JOIN servicio s ON s.id = r.servicio_id
WHERE s.titulo = 'DATA-001 Revisión de tomacorrientes'
  AND r.estado = 'finalizada'
  AND NOT EXISTS (SELECT 1 FROM calificacion c WHERE c.reserva_id = r.id);

SELECT
    (SELECT COUNT(*) FROM usuario) AS usuarios,
    (SELECT COUNT(*) FROM servicio) AS servicios,
    (SELECT COUNT(*) FROM reserva) AS reservas;
