# Nuva


## Instalación

1. Instala las dependencias de Composer:

```bash
composer install
```

2. Crea la base de datos importando `nuvabd.sql` desde phpMyAdmin o desde MySQL.

3. Revisa el archivo `.env` en la raíz del proyecto y completa estos campos:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/public/index.php?page=google-callback

DB_HOST=localhost
DB_PORT=3306
DB_NAME=NuvaBD
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

En una instalación estándar de XAMPP, el usuario `root` no tiene contraseña. Si tu instalación usa una contraseña, completa `DB_PASSWORD`.

4. Configura en Google Cloud la misma URL de redirección que pongas en `GOOGLE_REDIRECT_URI`.

5. Comprueba que exista la carpeta donde se guardan las fotos de perfil:

```text
public/assets/uploads/users/
```

La aplicación descarga la foto de Google y guarda en la base de datos únicamente la ruta local. Las imágenes de esa carpeta están excluidas de Git.

## Notas

- El archivo `.env` se carga desde `public/index.php`.
- Si cambias el dominio o la ruta del proyecto, actualiza `GOOGLE_REDIRECT_URI`.
- El archivo `.env` y las fotos subidas no deben versionarse.
- La sesión guarda el `user_id`; los datos del perfil se consultan desde la tabla `users`.

## Unidad y tipo de dato de estadísticas (RF-016)

La tabla `member_stat_types` incluye estos campos:

- `unit`: unidad del dato, por ejemplo `s`, `m`, `kg` o `%`. Es opcional; utiliza `NULL` cuando no corresponda una unidad.
- `data_type`: `integer` para conteos enteros o `decimal` para mediciones con fracciones. El valor predeterminado es `integer`, compatible con los registros existentes. La primera versión admite estadísticas numéricas.

Los valores de `member_stat_values` y `tournament_participant_stat_values` se almacenan como `DECIMAL(20,8)` para conservar hasta ocho decimales. Por ejemplo, una estadística de tiempo puede definir `unit = 's'`, `data_type = 'decimal'` y almacenar `12.345`.

Para instalaciones nuevas, importa `nuvabd.sql`. Para una base existente, ejecuta una sola vez `database/migrations/001_stat_unit_and_data_type.sql` en lugar de volver a importar el esquema. La migración conserva los valores anteriores, establece el tipo `integer` y deja la unidad en `NULL` sin inferirla.

Estos cambios corresponden al esquema de datos. Cuando se implemente la carga de estadísticas, deberá validar que los valores de tipo `integer` no tengan fracciones y respetar la definición utilizada para los registros históricos.

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

- Google y el guardado de la cuenta son pasos distintos. Si la base de datos no está disponible, inicia MySQL en XAMPP y comprueba que exista la base indicada por `DB_NAME`, con la tabla `users`.
- Para una base nueva, importa `nuvabd.sql`; no lo vuelvas a importar sobre una base con datos.
- Después de corregir el problema, vuelve a `?page=login` y utiliza **Continuar con Google**. La URL de callback contiene un código de un solo uso y no debe reutilizarse.
- El callback muestra los errores recuperables en la pantalla de acceso y registra el tipo de fallo en el log de PHP, sin guardar códigos de Google ni tokens.
- El acceso real debe comenzar en la dirección configurada en `GOOGLE_REDIRECT_URI` (en esta instalación, `http://localhost`). La vista temporal de pruebas usa cuentas ficticias.

## Torneos y datos de demostración

- Para actualizar una base existente, ejecuta `database/migrations/003_tournaments.sql`. Las instalaciones nuevas ya tienen esas columnas en `nuvabd.sql`.
- `database/demo/cargar_datos.php --owner-id=TU_ID` agrega tres entornos, nueve torneos y personas ficticias sin duplicarlos al repetirlo.
- `database/borrar_base_de_datos.php` elimina toda NuvaBD únicamente con el argumento de confirmación indicado en el propio archivo. Nunca forma parte de la carga de ejemplo.
- Consulta `docs/torneos.md` para conocer las pantallas, formatos, permisos, calendario y fuentes documentales.

### Inicio y guía

El Home y la portada pública consultan torneos y entornos reales: sus cifras y accesos por categoría no usan valores de relleno. `?page=guide` ofrece una guía de uso dentro de Nuva sobre primeros pasos, roles, torneos, formatos y visibilidad. Las tarjetas de Competiciones y Mis torneos comparten un diseño responsive y enlazan a cada torneo.

### Ajustes de cuenta

En `?page=profile`, cada persona puede editar su nombre visible, sincronizar de nuevo la foto de su cuenta de Google y cerrar sesión. El nombre elegido en Nuva se conserva al volver a entrar con Google. La foto usa la URL HTTPS de `picture` que devuelve Google; ya no se descarga en `public/assets/uploads/users`, porque esa carpeta puede no ser escribible para Apache. Si la imagen falla, se muestra el icono de Nuva. Las cuentas que ya tenían la foto vacía deben usar **Sincronizar foto de Google** una vez para obtenerla.

### Funcionamiento de los torneos

Aplicar `database/migrations/004_competition_engine.sql` sobre bases existentes, después de las migraciones anteriores. Incluye rondas, encuentros, resultados y reglas configurables. Para instalaciones nuevas ya está integrado en `nuvabd.sql`.

Uso y criterios: [docs/torneos.md](docs/torneos.md). Pruebas de integración: `/opt/lampp/bin/php tests/competition.php` (los datos temporales se revierten).

Ejemplos con enfrentamientos y resultados: `/opt/lampp/bin/php database/demo/cargar_competiciones.php --owner-id=ID`. Crea un entorno separado con seis torneos y evita duplicarlos al repetir el comando.
