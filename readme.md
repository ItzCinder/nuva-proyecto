# Nuva

Nuva es una aplicación web para gestionar entornos deportivos, miembros,
roles, torneos, competiciones y estadísticas. Está construida con PHP,
Apache y MariaDB, y utiliza Google para el inicio de sesión.

## Configuración con Docker

### 1. Crear la configuración

Desde la carpeta principal del proyecto, ejecuta:

```bash
sh docker/preparar.sh
```

El comando crea `.env.docker` y genera contraseñas seguras para Nuva y para
la cuenta administradora de MariaDB. El archivo no debe publicarse ni
compartirse.

Internamente, `preparar.sh` hace lo siguiente:

1. Comprueba si `.env.docker` ya existe. Si existe, conserva su configuración.
2. Genera dos contraseñas aleatorias diferentes con `openssl`.
3. Copia `.env.docker.example` como `.env.docker`.
4. Escribe las contraseñas generadas en la nueva configuración.

Para usar inicio de sesión con Google, completa en `.env.docker`:

```dotenv
GOOGLE_CLIENT_ID=tu-client-id
GOOGLE_CLIENT_SECRET=tu-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8080/public/index.php?page=google-callback
```

`NUVA_HTTP_PORT` permite cambiar el puerto local. Por ejemplo,
`NUVA_HTTP_PORT=8081` hará que Nuva esté disponible en
`http://localhost:8081`.

### 2. Crear e iniciar los contenedores

```bash
docker compose --env-file .env.docker up -d --build
```

Este comando:

1. Construye el contenedor web con PHP 8.2 y Apache.
2. Crea el contenedor de MariaDB.
3. Inicializa la estructura de la aplicación en una base nueva.
4. Crea la red interna entre la aplicación y MariaDB.
5. Inicia ambos servicios en segundo plano.

Abre la aplicación en:

```text
http://localhost:8080
```

La aplicación espera a que MariaDB esté disponible antes de iniciar el
servicio web.

El puerto se publica solamente en `127.0.0.1`. Esto permite colocar un
Apache o un proxy inverso delante del contenedor y publicar Nuva mediante
HTTPS.

Cuando se utiliza HTTPS, configura en `.env.docker`:

```dotenv
GOOGLE_REDIRECT_URI=https://tu-dominio.example/public/index.php?page=google-callback
SESSION_SECURE_COOKIE=1
```

La dirección configurada en Google Cloud debe ser exactamente igual a
`GOOGLE_REDIRECT_URI`.

## Qué ocurre al levantar Docker

Cuando ejecutas `docker compose ... up -d --build`, Compose lee
`compose.yaml` y realiza este proceso:

1. Construye la imagen del servicio web usando `docker/Dockerfile`.
2. Descarga la imagen oficial de MariaDB si todavía no está disponible.
3. Crea los contenedores `web` y `database`.
4. Crea una red para que ambos servicios se comuniquen de forma privada.
5. Crea los volúmenes persistentes de Nuva.
6. Inicia primero MariaDB y espera a que pase su comprobación de salud.
7. Inicia el contenedor web cuando la base de datos está preparada.

El `Dockerfile` prepara la imagen web de forma sencilla:

1. Parte de PHP 8.2 con Apache sobre Debian.
2. Instala las extensiones PHP que Nuva necesita para MariaDB, textos y Google.
3. Instala las dependencias de PHP con Composer.
4. Copia el código de la aplicación al contenedor.
5. Copia las configuraciones de Apache, PHP y el script de inicio.
6. Crea las carpetas de sesiones e imágenes y asigna sus permisos.
7. Define `start.sh` como el proceso inicial del contenedor.

Al comenzar el contenedor web, `start.sh` prepara los permisos, carga los
datos de ejemplo si `LOAD_DEMO_DATA=1` y finalmente inicia Apache. El
contenedor de MariaDB utiliza `nuvabd.sql` y los archivos de configuración de
`docker/mariadb` durante su primera inicialización.

## Volúmenes de Docker

Los volúmenes mantienen los datos aunque los contenedores se detengan o se
reconstruyan.

| Volumen | Ubicación dentro del contenedor | Para qué sirve |
|---|---|---|
| `database` | `/var/lib/mysql` | Conserva los usuarios, entornos, torneos y demás información de MariaDB. |
| `sessions` | `/var/lib/php/sessions` | Conserva las sesiones de inicio de sesión de PHP. |
| `environment-pictures` | `/var/www/nuva/public/assets/uploads/environments` | Conserva las imágenes subidas para los entornos. |

También existen estos montajes de archivos, que no son volúmenes de datos:

- `nuvabd.sql` se monta como archivo de inicialización de MariaDB. Solo se
  utiliza automáticamente cuando el volumen `database` está vacío.
- `docker/mariadb/20-permissions.sql` configura los permisos iniciales de la
  base.
- `docker/mariadb/nuva.cnf` aplica la configuración de MariaDB.

## Configuración opcional

En `.env.docker` también puedes ajustar:

```dotenv
LOAD_DEMO_DATA=1
SESSION_SECURE_COOKIE=0
```

`LOAD_DEMO_DATA=1` habilita la carga inicial de información de ejemplo.
Puedes cambiarlo a `0` si quieres iniciar con una instalación vacía.
`SESSION_SECURE_COOKIE` debe valer `1` cuando Nuva se publica mediante HTTPS.

Si cambiaste las credenciales de MariaDB después de crear el volumen
`database`, las nuevas credenciales no se aplican automáticamente. Debes
restaurar la configuración original o eliminar el volumen y crear la
instalación de nuevo, teniendo en cuenta que eliminarlo borra los datos.
