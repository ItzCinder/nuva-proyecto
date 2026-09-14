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
