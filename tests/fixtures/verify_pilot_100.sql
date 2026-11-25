SELECT
  (SELECT COUNT(*) FROM usuario) AS usuarios,
  (SELECT COUNT(*) FROM usuario u INNER JOIN rol r ON r.id=u.rol_id WHERE r.nombre='Cliente') AS clientes,
  (SELECT COUNT(*) FROM usuario u INNER JOIN rol r ON r.id=u.rol_id WHERE r.nombre='Trabajador') AS trabajadores,
  (SELECT COUNT(*) FROM servicio) AS servicios,
  (SELECT COUNT(*) FROM reserva) AS reservas,
  (SELECT COUNT(DISTINCT cliente_id) FROM reserva) AS clientes_distintos,
  (SELECT COUNT(DISTINCT servicio_id) FROM reserva) AS servicios_distintos,
  (SELECT COUNT(*) FROM reserva WHERE estado='finalizada') AS reservas_finalizadas,
  (SELECT COUNT(*) FROM calificacion) AS calificaciones,
  (SELECT ROUND(AVG(estrellas),2) FROM calificacion) AS promedio;
