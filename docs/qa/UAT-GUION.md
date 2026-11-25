# Guion UAT — OficioClick

## Propósito

Validar los flujos del MVP en un ambiente aislado. Las cuentas que genera el fixture son sintéticas y no representan participantes ni resultados reales de aceptación.

## Preparación segura

- Usa una instancia y una base exclusivas para UAT; no cargues el fixture en la base de desarrollo con información que quieras conservar.
- En Docker, copia `.env.example` a `.env.uat`, configura `APP_PORT=18081` y contraseñas locales, y levanta un proyecto Compose separado:

  ```powershell
  docker compose --env-file .env.uat -p oficioclick-uat up --build -d
  ```

- El fixture `tests/fixtures/seed_pilot_100.sql` elimina los registros de usuario, servicio, reserva, calificación y auditoría de la base seleccionada antes de generarlos. Impórtalo solo en la base desechable del proyecto UAT:

  ```powershell
  docker compose --env-file .env.uat -p oficioclick-uat cp tests\fixtures\seed_pilot_100.sql db:/tmp/seed_pilot_100.sql
  docker compose --env-file .env.uat -p oficioclick-uat exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /tmp/seed_pilot_100.sql'
  ```

- Abre `http://localhost:18081`. Al finalizar, `docker compose --env-file .env.uat -p oficioclick-uat down` detiene los servicios y conserva los datos; `down -v` elimina el volumen UAT y sus datos.
- No uses información personal real en capturas, comentarios, cuentas ni reportes.

## Cuentas y datos de ensayo

La contraseña de desarrollo de las cuentas del fixture es `12345678a`.

| Rol | Cuenta de ejemplo |
|---|---|
| Administrador | `admin@example.test` |
| Trabajador | `trabajador.001@example.test` |
| Cliente | `cliente.001@example.test` |

El conjunto contiene 100 cuentas (1 administrador, 20 trabajadores y 79 clientes), 50 servicios, 30 reservas asociadas a 30 clientes y 30 servicios distintos, y 20 calificaciones con promedio 3,60. Los correos `.test` y documentos `SIM-` identifican datos sintéticos.

Para una sesión con participantes reales, crea cuentas temporales por separado, informa el propósito de la prueba y registra la evidencia sin datos personales. No presentes los conteos del fixture como resultados de UAT.

## Casos

1. Registro de usuario.
2. Inicio de sesión por rol; verificar “Credenciales inválidas.” con credenciales no válidas.
3. Confirmar que la recuperación de contraseña se identifica como no disponible en esta versión.
4. Búsqueda por categoría, texto, rango de precio, calificación mínima y disponibilidad; revisar el estado vacío.
5. Solicitud de reserva.
6. Confirmación de reserva por trabajador.
7. Finalización de reserva.
8. Calificación de 1 a 5 estrellas.
9. Actualización del perfil propio por Cliente y Trabajador; comprobar correo y documento duplicados.
10. Creación administrativa de una cuenta Cliente/Trabajador y verificación de la entrada de auditoría.
11. Gestión administrativa de rol/estado, filtros de reservas y exportación de reporte CSV.
12. Uso móvil y navegación por teclado, incluido el menú y la visualización completa de los filtros.

## Evidencia mínima

- Fecha, versión y commit evaluados.
- Tarea y tiempo empleado.
- Resultado: éxito o falla.
- Observación anonimizada y defectos registrados por ID.
- Capturas sin datos personales.

## Criterios de aceptación

- Al menos 80% de participantes completa una reserva en menos de 3 minutos.
- No quedan defectos Bloqueantes o Críticos abiertos.
- Las acciones permitidas por rol se validan también en el servidor.
- La disponibilidad durante 30 días y la compatibilidad entre navegadores requieren mediciones y evidencias propias; este guion no las sustituye.
