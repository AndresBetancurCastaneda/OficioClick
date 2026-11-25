# OficioClick

Plataforma web para publicar, buscar, reservar y calificar servicios por horas en Medellín y su área metropolitana. Es un monolito PHP con páginas renderizadas en el servidor; no expone una API REST independiente.

## Funcionalidades

- Registro e inicio de sesión para clientes y trabajadores.
- Catálogo por categoría, texto, rango de precios, calificación mínima y disponibilidad.
- Detalle de servicio, reseñas y estados vacíos.
- Reservas de clientes; gestión de servicios y solicitudes por trabajadores.
- Perfil propio para clientes y trabajadores.
- Administración de cuentas, estados, reservas, servicios y reseñas; registro de auditoría y exportación CSV.
- Diseño adaptable a móvil y menú de navegación.

La recuperación de contraseña no está disponible en esta versión.

## Tecnologías

PHP 8.2, Apache, MySQL 8.0, MySQLi, HTML, CSS, JavaScript y Docker Compose. XAMPP puede usarse para desarrollo local sin Docker.

## Requisitos

- Docker Desktop con Docker Compose, o XAMPP con PHP 8.2+, Apache y MySQL 8.0.
- Git para clonar el repositorio.

## Inicio con Docker

Desde la raíz del repositorio:

1. Copia `.env.example` a `.env` y configura contraseñas locales. No subas `.env` a Git.
2. Ejecuta `docker compose up --build -d`.
3. Abre `http://localhost:8080` (o el puerto definido en `APP_PORT`).
4. Consulta los logs con `docker compose logs -f web`.
5. Detén los servicios con `docker compose down`.

MySQL carga `database/schema.sql` únicamente al inicializar un volumen nuevo. `docker compose down` conserva el volumen y los datos; `docker compose down -v` elimina el volumen de la base de datos. Haz un respaldo antes de eliminarlo.

## Inicio con XAMPP

1. Coloca el proyecto en `htdocs/OficioClick` e inicia Apache y MySQL.
2. Importa `database/schema.sql` desde phpMyAdmin en una base desechable o nueva.
3. Si la configuración local no usa `127.0.0.1:3306`, el usuario `root` y contraseña vacía, configura `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` y `DB_NAME` en el entorno de Apache antes de iniciarlo.

La aplicación comprueba la conexión y la tabla `usuario`; no crea la base ni importa el esquema automáticamente.

## Base de datos y migraciones

En una instalación nueva, `database/schema.sql` crea las tablas y cuentas iniciales. Para una base existente:

1. Haz un respaldo verificado.
2. Ejecuta en orden `database/migrations/20261113_add_data_quality_checks.sql` y `database/migrations/20261118_complete_requirement_gaps.sql`.
3. Verifica la aplicación antes de continuar usando la base.

En Docker, copia y aplica cada archivo en ese orden:

```powershell
docker compose cp database\migrations\20261113_add_data_quality_checks.sql db:/tmp/20261113_add_data_quality_checks.sql
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /tmp/20261113_add_data_quality_checks.sql'
docker compose cp database\migrations\20261118_complete_requirement_gaps.sql db:/tmp/20261118_complete_requirement_gaps.sql
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /tmp/20261118_complete_requirement_gaps.sql'
```

Las migraciones son manuales y no se ejecutan al iniciar los contenedores. La segunda conserva referencias de calificaciones antiguas en `calificacion_legacy_refs` antes de retirar columnas redundantes.
En XAMPP, selecciona la base en phpMyAdmin y ejecuta el contenido de esos mismos archivos, en el mismo orden.

## Cuentas de desarrollo

El esquema incluye estas cuentas iniciales; la contraseña de desarrollo es `12345678a`:

| Rol | Correo |
|---|---|
| Administrador | `admin@oficioclick.com` |
| Trabajador | `carlos@oficioclick.com` |
| Cliente | `ana@oficioclick.com` |

Son credenciales locales conocidas. Cámbialas y reemplaza los datos de ejemplo antes de exponer una instalación a Internet.

## Pruebas y QA

Con Docker iniciado y `.env` configurado:

```powershell
docker compose config --quiet
docker compose exec -T web php tests/requirements_smoke.php
docker compose exec -T web php tests/security_helpers_smoke.php
docker compose exec -T web php tests/p0_p1_smoke.php
powershell -File scripts\qa_backup_restore.ps1 -DatabaseName oficioclick_backup_restore_local
```

El chequeo de PHP usado por CI es:

```powershell
docker compose exec -T web sh -lc 'find app public views tests -name "*.php" -print | while IFS= read -r file; do php -l "/var/www/html/$file" || exit 1; done'
```

GitHub Actions también valida Compose, construye e inicia los contenedores, ejecuta las pruebas de requisitos, helpers y prioridad, verifica respaldo/restauración y consulta las páginas HTTP principales.

El script de respaldo/restauración crea una base desechable con el prefijo `oficioclick_backup_restore_`, verifica los conteos antes y después y la elimina. Usa un nombre que no exista; no apunta a la base de desarrollo.

Las pruebas de carga opcionales consultan páginas HTTP y comparan p95 con 2 segundos y errores con 1%:

```powershell
python scripts\load_check.py http://localhost:8080
powershell -File scripts\load_check.ps1 -BaseUrl http://localhost:8080 -RequestCount 200
```

Estas pruebas son una comprobación local breve, no certifican 50 usuarios concurrentes sostenidos ni disponibilidad durante 30 días.

## Datos sintéticos

Los fixtures son datos ficticios y no demuestran participación real ni resultados de aceptación:

- `tests/fixtures/seed_pilot_100.sql` reemplaza usuarios, servicios, reservas, calificaciones y auditoría; úsalo únicamente en una base desechable.
- `tests/fixtures/append_pilot_100.sql` agrega los registros faltantes sin borrar los existentes. Respalda primero.
- `tests/fixtures/normalize_pilot_display.sql` normaliza registros antiguos de demostración con marcadores visibles; respalda primero.
- `tests/fixtures/seed_data001.sql` y `seed_data002.sql` amplían datos para QA. `seed_data002.sql` presupone el esquema y los datos DATA-001.
- `tests/fixtures/verify_pilot_100.sql` consulta los conteos del conjunto de demostración.

Los datos de demostración usan correos reservados `.test` y documentos `SIM-` para identificarlos como sintéticos. Los fixtures destructivos solo deben importarse en bases aisladas.

## Documentación

- [Manual de usuario](docs/manuales/Manual_de_Usuario_OficioClick_ADSO_v1.0.docx)
- [Guion de UAT](docs/qa/UAT-GUION.md)
- Diagramas de arquitectura y datos: `docs/arquitectura/diagramas/`

El guion UAT define preparación, cuentas de ensayo y evidencia. Los documentos fuente mantienen diferencias sobre si el registro exige ciudad y si debe iniciar sesión automáticamente; la implementación actual exige ciudad e inicia sesión después del registro. La recuperación de contraseña aún no está implementada.

## Estructura

| Ruta | Contenido |
|---|---|
| `app/` | Configuración, inicio de sesión de aplicación y helpers |
| `database/` | Esquema y migraciones manuales |
| `public/` | Punto de entrada y archivos CSS/JavaScript |
| `views/` | Vistas públicas y paneles por rol |
| `tests/` | Pruebas de requisitos, helpers y flujos prioritarios |
| `tests/fixtures/` | Datos de QA sintéticos |
| `scripts/` | Validación de carga y respaldo/restauración |
| `docs/` | Manuales, guion UAT y diagramas |

## Evidencia pendiente

El código y las pruebas locales no sustituyen evidencias de disponibilidad durante 30 días, sesiones de usabilidad con participantes, compatibilidad física entre navegadores ni aprobación formal Go/No-Go. Registra esas validaciones por separado antes de declarar los objetivos cumplidos.
