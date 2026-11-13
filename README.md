# OficioClick

Plataforma web para contratar servicios por horas en Medellín y su área metropolitana.

## Stack
- PHP
- HTML5
- CSS3
- JavaScript
- Apache
- MySQL
- Docker Compose (despliegue reproducible)
- XAMPP (alternativa de desarrollo local)

## Estructura del proyecto
- `public/` – punto de entrada web y assets
- `app/` – configuración, helpers y arranque de la aplicación
- `database/` – script SQL con la estructura inicial y datos semilla
- `views/` – vistas de cada pantalla principal

## Ejecución con Docker
Requisitos: Docker Desktop con Docker Compose habilitado.

1. Copia `.env.example` a `.env` y reemplaza las contraseñas de desarrollo.
2. Desde la raíz del proyecto, ejecuta `docker compose up --build -d`.
3. Abre `http://localhost:8080` (o el puerto configurado en `APP_PORT`).
4. Consulta los registros con `docker compose logs -f web`.
5. Detén los contenedores con `docker compose down`.

MySQL inicializa `database/schema.sql` la primera vez que se crea el volumen. Los datos se conservan en el volumen `db_data`. El script no se vuelve a ejecutar automáticamente cuando el volumen ya contiene datos; respalda los datos antes de aplicar cambios de esquema.

## Ejecución local con XAMPP
1. Instala XAMPP con Apache y MySQL activos.
2. Ubica este proyecto dentro de `htdocs/OficioClick`.
3. Importa `database/schema.sql` desde phpMyAdmin.
4. Si tus credenciales difieren de las predeterminadas, define `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` y `DB_NAME` en el entorno de Apache antes de iniciarlo.

La aplicación verifica que la base de datos y la tabla `usuario` estén disponibles; no crea bases de datos ni importa el esquema automáticamente.

Para una base ya existente, ejecuta una sola vez `database/migrations/20261113_add_data_quality_checks.sql` después de respaldarla. El script agrega restricciones para tarifas mayores a cero y calificaciones de 1 a 5, corrige textos semilla conocidos con codificación incorrecta y consolida la categoría de plomería mal codificada, reasignando sus servicios a la categoría correcta antes de retirar el duplicado exacto. `schema.sql` configura explícitamente UTF-8 para instalaciones nuevas.

## Acceso rápido de pruebas
Usuarios de ejemplo incluidos en el script SQL:
- Admin: `admin@oficioclick.com` / `12345678a`
- Trabajador: `carlos@oficioclick.com` / `12345678a`
- Cliente: `ana@oficioclick.com` / `12345678a`

Estas credenciales son únicamente para desarrollo local. Cámbialas y reemplaza los datos semilla antes de exponer una instalación a Internet.

## Páginas principales
- Inicio: `index.php?page=home`
- Registro: `index.php?page=register`
- Login: `index.php?page=login`
- Catálogo: `index.php?page=catalog`
- Cliente: `index.php?page=client-dashboard`
- Trabajador: `index.php?page=worker-dashboard`
- Administrador: `index.php?page=admin-dashboard`

## Observación
La aplicación respeta los módulos previstos en la documentación: autenticación, publicación de servicios, búsqueda, reservas, calificaciones y administración.
