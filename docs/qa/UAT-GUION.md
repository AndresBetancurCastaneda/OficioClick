# Guion UAT – OficioClick

## Propósito

Validar con participantes controlados los flujos reales del MVP. Las cuentas ficticias pueden usarse para el ensayo; los resultados deben distinguir datos sintéticos de participantes reales.

## Datos y ambiente

- Ambiente: AMB-UAT.
- Datos: `tests/fixtures/seed_pilot_100.sql`.
- Puerto Docker: `18081` o puerto configurado.
- No usar datos personales reales.

## Participantes

- 30 clientes ficticios para reserva.
- 20 trabajadores ficticios para publicación y gestión.
- 1 administrador ficticio.
- Participantes reales opcionales de usabilidad.

## Casos

1. Registro de usuario.
2. Inicio de sesión por rol; verificar “Credenciales inválidas.” con credenciales no válidas.
3. Confirmar que la recuperación de contraseña se identifica como no disponible en esta versión.
4. Búsqueda por categoría, texto, rango de precio, calificación mínima y disponibilidad; revisar el estado vacío.
5. Solicitud de reserva.
6. Confirmación de reserva por trabajador.
7. Finalización de reserva.
8. Calificación 1–5.
9. Gestión administrativa de usuario.
10. Filtros de reservas y exportación de reporte CSV.
11. Uso móvil y navegación por teclado, incluido el menú y la visualización completa de los filtros.

## Evidencia mínima

- Capturas sin datos personales.
- Fecha, versión y commit.
- Tiempo de cada tarea.
- Éxito o falla.
- Observación del participante.
- Defectos registrados por ID.

## Criterio

- 80% o más completa la reserva en menos de 3 minutos.
- Ningún defecto Bloqueante o Crítico abierto.
- Las acciones por rol coinciden con el servidor.
