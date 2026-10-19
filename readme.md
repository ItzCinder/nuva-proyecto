# Nuva


## Instalación con Docker

```bash
sh docker/preparar.sh
# Completa los datos de Google en .env.docker.
docker compose --env-file .env.docker up -d --build
```

Abre `http://localhost:8080`. Se crean Apache/PHP y MariaDB como servicios separados. El primer inicio incluye 64 personas ficticias, 6 entornos y 54 torneos: 18 de cada formato, con resultados, fechas y estadísticas. La carga no se duplica al reiniciar y no cambia el esquema actual.

Consulta [despliegue y respaldos](docs/despliegue.md) para publicar mediante el Apache del servidor, configurar Google, consultar registros y usar una instalación directa sobre Apache y MariaDB. No se necesita XAMPP.

## Instalación sin contenedores

1. Instala las dependencias con `composer install --no-dev`.
2. Importa `nuvabd.sql` únicamente en una base nueva. Sobre una base existente, aplica las migraciones pendientes.
3. Copia `.env.example` a `.env` y completa la conexión a MariaDB y los datos de Google. Usa una cuenta de base de datos con permisos limitados a NuvaBD.
4. Publica la carpeta `public` desde Apache y configura el alias `/public/`, como explica la guía de despliegue.
5. Registra en Google Cloud la misma dirección de `GOOGLE_REDIRECT_URI`. En un dominio con HTTPS, establece `SESSION_SECURE_COOKIE=1`.

La foto de perfil usa la URL HTTPS que entrega Google. No requiere crear carpetas de subidas ni permisos de escritura sobre el proyecto.

## Notas

- La configuración se carga con `nuva_load_environment()`: admite `.env` o variables del servidor/contenedor.
- `.env` y `.env.docker` son privados y no deben versionarse.
- La sesión guarda el identificador del usuario; el perfil se consulta desde `users`.
- [Seguridad aplicada](docs/seguridad.md): entradas numéricas, consultas preparadas, sesiones, archivos privados y registros.
- [Guía para leer el código](docs/guia-del-codigo.md): funciones, variables y recorrido MVC.

## Unidad y tipo de dato de estadísticas (RF-016)

La tabla `member_stat_types` incluye estos campos:

- `unit`: unidad del dato, por ejemplo `s`, `m`, `kg` o `%`. Es opcional; utiliza `NULL` cuando no corresponda una unidad.
- `data_type`: `integer` para conteos enteros o `decimal` para mediciones con fracciones. El valor predeterminado es `integer`, compatible con los registros existentes. La primera versión admite estadísticas numéricas.

Los valores de `member_stat_values` y `tournament_participant_stat_values` se almacenan como `DECIMAL(20,8)` para conservar hasta ocho decimales. Por ejemplo, una estadística de tiempo puede definir `unit = 's'`, `data_type = 'decimal'` y almacenar `12.345`.

Para instalaciones nuevas, importa `nuvabd.sql`. Para una base existente, ejecuta una sola vez `database/migrations/001_stat_unit_and_data_type.sql` en lugar de volver a importar el esquema. La migración conserva los valores anteriores, establece el tipo `integer` y deja la unidad en `NULL` sin inferirla.

La carga de estadísticas valida que los valores de tipo `integer` no tengan fracciones y conserva la definición utilizada por los registros históricos.

## Listados de la interfaz

- Competiciones muestra hasta tres tarjetas; `?page=competitions-all` abre el listado completo y conserva los filtros.
- En Cuenta, las pestañas Mis torneos y Mis entornos incluyen filtros y enlaces a `?page=my-tournaments` y `?page=my-environments`. Estos listados requieren sesión.
- Los filtros se envían por GET y funcionan sin JavaScript. Permiten buscar por nombre o disciplina y combinar categoría, disciplina, estado y formato; los entornos tienen búsqueda por nombre.
- Las tarjetas de competiciones y Mis torneos se consultan desde la base de datos. Las tarjetas antiguas codificadas en PHP se retiraron.
- `collection.php`, `collection-tabs.php` y `competition-card.php` reutilizan los listados y tarjetas dentro de la estructura PHP existente.

## Gestión de entornos

- Desde **Entornos** se puede crear un espacio, consultar los espacios propios y abrir una invitación.
- El creador se convierte en dueño. Los permisos y secciones dependen del rol dentro de cada entorno: dueño, administrador general, organizador o participante.
- La gestión del entorno incluye información básica, miembros y roles, invitaciones, módulos y auditoría. También permite crear torneos en los módulos habilitados.
- En una base existente, ejecuta `database/migrations/002_environments_and_roles.sql`. En una instalación nueva, `nuvabd.sql` ya incluye los cambios.
- Consulta `docs/entornos.md` para conocer los permisos, fuentes de la tercera entrega, instalación y pruebas.

## Si falla el acceso con Google

- Google y el guardado de la cuenta son pasos distintos. Si la base de datos no está disponible, comprueba que MariaDB esté iniciado y que exista la base indicada por `DB_NAME`, con la tabla `users`.
- Para una base nueva, importa `nuvabd.sql`; no lo vuelvas a importar sobre una base con datos.
- Después de corregir el problema, vuelve a `?page=login` y utiliza **Continuar con Google**. La URL de callback contiene un código de un solo uso y no debe reutilizarse.
- El callback muestra los errores recuperables en la pantalla de acceso y registra el tipo de fallo en el log de PHP, sin guardar códigos de Google ni tokens.
- El acceso real debe comenzar en la dirección configurada en `GOOGLE_REDIRECT_URI` (en esta instalación, `http://localhost`).

## Torneos y datos de demostración

- Para actualizar una base existente, ejecuta `database/migrations/003_tournaments.sql`. Las instalaciones nuevas ya tienen esas columnas en `nuvabd.sql`.
- `php demostracion/cargar.php --owner-id=TU_ID` agrega la demostración completa de seis entornos y 54 torneos, y completa los ejemplos anteriores reconocidos con estadísticas, planteles y relatos de sus actividades. No duplica los datos al repetirlo. Consulta `demostracion/README.md`.
- `database/borrar_base_de_datos.php` elimina toda NuvaBD únicamente con el argumento de confirmación indicado en el propio archivo. Nunca forma parte de la carga de ejemplo.
- Consulta `docs/torneos.md` para conocer las pantallas, formatos, permisos, calendario y fuentes documentales.

### Inicio y guía

El Home y la portada pública consultan torneos y entornos reales: sus cifras y accesos por categoría no usan valores de relleno. `?page=guide` ofrece una guía de uso dentro de Nuva sobre primeros pasos, roles, torneos, formatos y visibilidad. Las tarjetas de Competiciones y Mis torneos comparten un diseño responsive y enlazan a cada torneo.

### Ajustes de cuenta

En `?page=settings`, cada persona puede editar su nombre visible, sincronizar de nuevo la foto de su cuenta de Google y cerrar sesión. El nombre elegido en Nuva se conserva al volver a entrar con Google. La foto usa la URL HTTPS de `picture` que devuelve Google; ya no se descarga en `public/assets/uploads/users`, porque esa carpeta puede no ser escribible para Apache. Si la imagen falla, se muestra el icono de Nuva. Las cuentas que ya tenían la foto vacía deben usar **Sincronizar foto de Google** una vez para obtenerla.

### Funcionamiento de los torneos

Aplicar `database/migrations/004_competition_engine.sql` sobre bases existentes, después de las migraciones anteriores. Incluye rondas, encuentros, resultados y reglas configurables. Para instalaciones nuevas ya está integrado en `nuvabd.sql`.

Uso y criterios: [docs/torneos.md](docs/torneos.md).

Ejemplos con enfrentamientos y resultados: `php demostracion/cargar.php --owner-id=ID`. Incluye los tres formatos y los estados planificado, en curso y finalizado.

### Módulos MVC y estadísticas

La aplicación está organizada en los doce módulos de `app/Modules`, con recursos propios en `public/modules`. Ver [organización](docs/modulos.md) y [uso de estadísticas](docs/estadisticas.md). Esta integración de estadísticas no requiere cambios del esquema SQL.

En un entorno, abre **Estadísticas y plantillas** para habilitar el catálogo. En un torneo, abre **Estadísticas** para seleccionar opciones, registrar pruebas guiadas y consultar indicadores. Para ejemplos: `php demostracion/cargar.php --owner-id=ID`.

Para recorrer el código paso a paso y localizar dónde cambiar cada pantalla, lee [la guía del código](docs/guia-del-codigo.md).

## Suricata en AlmaLinux

La carpeta [suricata-almalinux](suricata-almalinux/README.md) es independiente de Nuva. Incluye el instalador, la configuración de IDS/IPS y una [guía manual con comandos de copia](suricata-almalinux/INSTALACION-MANUAL.md) para un servidor con Apache y MariaDB. Crear estos archivos no instala Suricata en el servidor.

## Información pública y equipo

El pie de cada pantalla enlaza a la guía, Sobre nosotros, Condiciones de uso y Copyright. Las tres páginas informativas se pueden abrir sin iniciar sesión: `?page=about`, `?page=terms` y `?page=copyright`.

Las páginas viven en `app/Modules/ConsultaPublica/Views/information`; su controlador es `InformationController.php`. Los nombres y las rutas de las fotos se editan en `ConsultaPublica/Models/Team.php`. Se muestran únicamente nombres y fotos, sin cargos ni biografías individuales. Las imágenes se sirven desde `public/assets/team`, sin depender de enlaces privados de Drive. La foto de Jean corresponde al archivo actualizado «Jean» de la carpeta del equipo.

La guía de estadísticas se encuentra en `ConsultaPublica/Views/partials/statistics-guide.php`. Los enlaces a un apartado desplegable lo abren automáticamente. Condiciones y Copyright contienen las reglas de uso, visibilidad de mediciones y créditos de los componentes externos; no incorporan pagos ni un nuevo paso de aceptación al iniciar sesión.


Las fotos de los entornos se cargan al crearlos o desde **Información del entorno**. Se guardan en `workspaces.picture` y en `public/assets/uploads/environments`, con JPG, PNG o WebP de hasta 2 MB. Es posible reemplazarlas o quitarlas. Docker conserva esta carpeta en un volumen y el respaldo incluye sus imágenes.

La carpeta [demostracion](demostracion/README.md) reúne el cargador completo, sus datos y los créditos de los escudos. Los cargadores antiguos de `database/demo` son accesos de compatibilidad al mismo script.
