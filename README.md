# OficioClick

Plataforma web para contratar servicios por horas en Medellín y su área metropolitana.

## Stack
- PHP
- HTML5
- CSS3
- JavaScript
- MySQL
- Apache/XAMPP

## Estructura del proyecto
- `public/` – punto de entrada web y assets
- `app/` – configuración, helpers y arranque de la aplicación
- `database/` – script SQL con la estructura inicial y datos semilla
- `views/` – vistas de cada pantalla principal

## Requisitos locales
1. Instalar XAMPP con Apache y MySQL activos.
2. Ubicar este proyecto dentro de `htdocs/OficioClick`.
3. Crear la base de datos con la configuración mostrada en `app/config.php` o modificarla si tienes otra credencial.

## Base de datos
1. Inicia MySQL desde XAMPP.
2. Importa el archivo `database/schema.sql` en phpMyAdmin o desde línea de comandos.
3. La aplicación intenta crear la base de datos y tablas automáticamente si la conexión está disponible.

## Acceso rápido de pruebas
Usuarios de ejemplo incluidos en el script SQL:
- Admin: `admin@oficioclick.com` / `12345678a`
- Trabajador: `carlos@oficioclick.com` / `12345678a`
- Cliente: `ana@oficioclick.com` / `12345678a`

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
