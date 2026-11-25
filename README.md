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
- `app/` – arranque, configuración y lógica compartida (`bootstrap.php`, `config.php`, `helpers.php`)
- `database/schema.sql` – esquema MySQL 8.0 y datos iniciales
- `database/migrations/` – migraciones manuales para bases existentes
- `public/` – punto de entrada `index.php` y assets CSS/JavaScript
- `views/` – pantallas públicas, paneles por rol y layout base
- `tests/` – verificaciones de requisitos y seguridad
- `tests/fixtures/` – datos sintéticos DATA-001 y DATA-002 para QA
- `docs/arquitectura/diagramas/` – diagramas Mermaid DIA-001 a DIA-006
- `Dockerfile`, `docker-compose.yml`, `.env.example` – imagen PHP/Apache, servicios y plantilla local de variables

La aplicación es un monolito PHP 8.2 con páginas renderizadas en el servidor, MySQL 8.0 y MySQLi; no expone una API REST independiente.

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
Si MySQL no está disponible al intentar iniciar sesión, la aplicación informa que no pudo conectarse con la base de datos en lugar de mostrar “Credenciales inválidas”.

Cuando el correo o la contraseña no coinciden, el mensaje mostrado es “Credenciales inválidas.”. La recuperación de contraseña no está disponible en esta versión y no se ofrece un enlace funcional para restablecerla.
El catálogo incluye filtros por categoría, texto del servicio, precio mínimo y máximo por hora, calificación mínima y disponibilidad. Los filtros se reorganizan para pantallas pequeñas.

Para una base ya existente, crea primero un respaldo y aplica las migraciones en orden: `database/migrations/20261113_add_data_quality_checks.sql` y luego `database/migrations/20261118_complete_requirement_gaps.sql`. La segunda agrega ciudades y dirección de atención, crea el registro de auditoría administrativa y normaliza las calificaciones: archiva los identificadores redundantes de esquemas antiguos en `calificacion_legacy_refs` antes de retirar esas columnas. Las migraciones no se ejecutan automáticamente. En una instalación nueva, `schema.sql` crea el esquema actualizado.

## Acceso rápido de pruebas
Usuarios de ejemplo incluidos en el script SQL:
- Admin: `admin@oficioclick.com` / `12345678a`
- Trabajador: `carlos@oficioclick.com` / `12345678a`
- Cliente: `ana@oficioclick.com` / `12345678a`

Estas credenciales son únicamente para desarrollo local. Cámbialas y reemplaza los datos semilla antes de exponer una instalación a Internet.
Las cuentas sintéticas adicionales de DATA-001 y DATA-002 usan la misma contraseña de desarrollo; por ejemplo, `data001.worker01@example.test` y `data002.user0005@example.test`.

## Páginas principales
- Inicio: `index.php?page=home`
- Registro: `index.php?page=register`
- Login: `index.php?page=login`
- Catálogo: `index.php?page=catalog`
- Cliente: `index.php?page=client-dashboard`
- Trabajador: `index.php?page=worker-dashboard`
- Administrador: `index.php?page=admin-dashboard`

## Análisis de las guías de referencia

Se revisaron las guías y cuadernos entregados de Ciencia de Datos con Python de las semanas 3, 4, 5 y 7, además de los materiales NoSQL de las semanas 2, 4, 5 y 7. El análisis consideró el contenido de los cuadernos, sus ejercicios y ejemplos, no solo sus títulos. También se revisaron dos archivos HTML de resultados Plotly junto con los materiales de datos relacionados; se trataron como productos de análisis y no como guías independientes. El texto del documento Word de apoyo pudo extraerse, pero sus metadatos de páginas no permitieron confirmar visualmente la paginación.

Los conceptos pertinentes para OficioClick son el uso de datos estructurados, los conteos y promedios descriptivos, la verificación de la calidad de datos y la comunicación de una métrica junto con su población y denominador. La aplicación ya usa MySQL relacional y calcula promedios de calificaciones; el esquema también restringe precios a valores positivos y calificaciones al rango de 1 a 5.

### Cambios de análisis de datos aplicados

- La portada consulta categorías y servicios en MySQL en vez de mantener una lista y un servicio destacado ficticios en el HTML.
- Los indicadores de Inicio y Administración se calculan directamente desde las tablas `servicio`, `reserva` y `calificacion`; no se almacenan ni se simulan.
- “Servicios disponibles” cuenta servicios con `estado = 'activo'` y `disponible = 1`.
- “Reservas registradas” cuenta todas las filas de `reserva`; el panel administrativo también muestra conteos separados por cada estado de reserva.
- El promedio mostrado es `AVG(calificacion.estrellas)` para reseñas asociadas a servicios activos y disponibles. Se muestra junto con el número de reseñas incluidas. Si no existen reseñas, la interfaz indica “Sin reseñas” en lugar de presentar un promedio de cero.
- Los errores al consultar las nuevas métricas se registran en el log de PHP y no se muestran como conteos iguales a cero.

### Decisiones sobre tecnologías y contenidos no implementados

No se agregó Python, Pandas ni otra dependencia de análisis a la aplicación. Para los conteos y promedios actuales, una consulta SQL sobre la base que ya utiliza el sistema es suficiente, directa de explicar y evita duplicar la lógica o sincronizar datos con otro proceso.

Tampoco se agregó MongoDB/NoSQL, aprendizaje automático, predicción, agrupamiento (clustering) ni nuevos gráficos. El proyecto no cuenta con un caso de uso ni con un volumen e historial de datos que justifiquen esas técnicas; agregarlas elevaría la complejidad sin mejorar las funciones centrales de publicación, búsqueda, reserva y calificación. Los cuadernos y resultados visuales de las guías quedan como referencia formativa, no como componentes de producción.

### Exportaciones CSV del panel administrativo

El panel de Administración permite descargar cuatro reportes, consultados al momento de la solicitud y limitados al rol Administrador:

- `oficioclick_resumen.csv`: servicios disponibles, reservas totales y por estado, promedio de calificaciones de servicios disponibles y cantidad de reseñas incluidas.
- `oficioclick_reservas.csv`: identificador, servicio, nombre del cliente, fecha/hora, estado y fecha de registro.
- `oficioclick_servicios.csv`: identificador, título, categoría, trabajador, tarifa, estado, disponibilidad y fecha de publicación.
- `oficioclick_usuarios.csv`: identificador, nombre, apellido, correo, rol, estado y fecha de registro. No incluye contraseñas, documentos de identidad ni direcciones.

Los archivos usan UTF-8 con BOM, encabezados y valores delimitados como CSV; los campos que podrían interpretarse como fórmulas por una hoja de cálculo se neutralizan al exportar. Los reportes son de solo lectura y no alteran los datos almacenados.

### Archivos ajustados y motivo

- `app/helpers.php`: agrega las consultas MySQL para las métricas y exportaciones; registra los errores y neutraliza celdas que podrían interpretarse como fórmulas. También devuelve `null` cuando no existe una calificación, en vez de convertir la ausencia de datos en un cero.
- `public/index.php`: carga categorías y métricas para Inicio, las métricas para Administración y protege la descarga CSV para el rol Administrador.
- `views/home.php`: reemplaza cifras, categorías y servicio destacado fijos por información de la base; presenta estados vacíos si aún no hay servicios o categorías.
- `views/admin-dashboard.php`: incorpora un resumen, conteos de reservas por estado y los enlaces de exportación CSV.
- `views/catalog.php` y `views/service-detail.php`: muestran explícitamente cuando un servicio todavía no tiene calificaciones.
- `README.md`: conserva las instrucciones existentes y agrega este análisis, las decisiones de implementación, las exclusiones y la verificación.

## Alcance funcional y verificación

La plataforma conserva su arquitectura PHP/MySQL y sus flujos existentes, y ahora incluye:

- Registro con ciudad del Valle de Aburrá y validación de datos requeridos.
- Búsqueda por categoría, texto, disponibilidad, rango de precios y calificación mínima, con paginación.
- Reserva por fecha, hora y dirección de atención; las reservas anteriores conservan la dirección como nula.
- Edición y activación/desactivación de servicios por el trabajador.
- Métricas administrativas de usuarios, servicios activos y reservas; filtros de reservas, gestión de servicios, moderación de reseñas y auditoría.
- Exportaciones CSV de solo lectura y sin contraseñas, documentos de identidad ni direcciones.

Las comprobaciones de aceptación que requieren un piloto real no se consideran aprobadas por ejecutar pruebas locales. Las metas de 100 usuarios, 50 servicios, 30 reservas, 20 calificaciones, 50 usuarios concurrentes, 99% de disponibilidad durante 30 días y 80% de éxito de usabilidad necesitan evidencias reales de prueba, seguimiento o validación con usuarios.

La verificación local de regresión de requisitos se ejecuta con `docker compose exec -T web php tests/requirements_smoke.php`. También se revisan los archivos PHP con `php -l` y la configuración con `docker compose config --quiet`.

Las verificaciones de seguridad sin dependencia de la base de datos se ejecutan con `docker compose exec -T web php tests/security_helpers_smoke.php`. Para ejecutar las verificaciones funcionales, debe estar disponible la base de datos del entorno de pruebas. Los fixtures de `tests/fixtures/` se cargan únicamente en una base desechable de QA; DATA-001 presupone el esquema y los datos base de `database/schema.sql`, y DATA-002 se carga después de DATA-001. No cargues estos fixtures en una instalación con datos de usuarios.

El puerto web publicado por Docker es `APP_PORT` (8080 por defecto); MySQL escucha en 3306 dentro de la red de Compose y no se publica al host. En XAMPP, Apache/MySQL usan los puertos definidos en esa instalación y el esquema debe importarse manualmente.

## QA piloto reproducible

- El fixture ficticio completo está en `tests/fixtures/seed_pilot_100.sql`.
- La verificación se encuentra en `tests/fixtures/verify_pilot_100.sql`.
- Las metas sintéticas son 100 usuarios, 50 servicios, 30 reservas, 20 calificaciones y promedio 3.60.
- El fixture no debe cargarse en la base real; úselo en una base desechable de QA.
- Las pruebas P0/P1 se ejecutan con `docker compose exec -T web php /var/www/html/tests/p0_p1_smoke.php`.
- La carga sintética se ejecuta con `python scripts/load_check.py http://localhost:8080`.
- La restauración desechable se ejecuta con `scripts/qa_backup_restore.ps1`.
- El guion UAT se encuentra en `docs/qa/UAT-GUION.md`.

### Observaciones de alineación con los documentos fuente

Los documentos académicos se auditaron sin modificarlos. Antes de declarar la trazabilidad documental cerrada, el equipo/instructor debe resolver estas diferencias:

- El documento de requisitos usa `RF-001` a `RF-008` y define `RNF-001` a `RNF-007`; el backlog alterna códigos sin ceros (`RF-01`) y afirma que existen ocho RNF.
- El backlog vincula la búsqueda de menos de 3 segundos con `RNF-05`, aunque el documento de requisitos asigna rendimiento a `RNF-001` y compatibilidad entre navegadores a `RNF-005`.
- La ciudad aparece como dato requerido en el backlog, pero no en las entradas de registro de la especificación detallada `RF-001`.
- La salida de registro en `RF-001` dice redirigir al inicio de sesión; el backlog y la aplicación describen una sesión iniciada y redirección al panel según el rol.
- La matriz de permisos menciona creación de usuarios por Administración y actualización del perfil propio para Cliente/Trabajador; esos flujos no están descritos en los requisitos funcionales detallados ni implementados como pantallas.
- La formulación registra metas piloto de 100 usuarios, 50 servicios, 30 reservas y 20 calificaciones. El estado actual de la base Docker es menor y no acredita resultados piloto; los objetivos de carga, disponibilidad, usabilidad y compatibilidad también requieren sus evidencias externas.
- La formulación marca pendientes las fechas de hitos y el representante funcional, y señala una diferencia entre un cronograma de 29 semanas y actividades documentadas en un periodo aproximado de 10 semanas. El plan de gestión conserva una lista de verificación con criterios pendientes de revisión.
- El esquema actual incluye ocho tablas (`rol`, `ciudad`, `categoria`, `usuario`, `admin_action`, `servicio`, `reserva` y `calificacion`): seis entidades funcionales originales y dos de soporte. El diagrama DIA-006 refleja el esquema real; no se eliminaron `ciudad` ni `admin_action` para forzar el conteo de seis.
- El plan define 26 casos de prueba, pero las pruebas de carga, disponibilidad durante 30 días, aceptación con usuarios, compatibilidad multi-navegador y recuperación requieren ambientes y evidencias que no se sustituyen con una revisión del código ni con pruebas locales.

Estas observaciones requieren una decisión del equipo/instructor; no se cambiaron los documentos académicos para evitar asumir una línea base aprobada.
