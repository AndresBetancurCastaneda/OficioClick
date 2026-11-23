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
2. Inicio de sesión por rol.
3. Búsqueda por categoría y calificación.
4. Solicitud de reserva.
5. Confirmación de reserva por trabajador.
6. Finalización de reserva.
7. Calificación 1–5.
8. Gestión administrativa de usuario.
9. Exportación de reporte CSV.
10. Uso móvil y navegación por teclado.

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
