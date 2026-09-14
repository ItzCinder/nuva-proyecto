# Nuva


## Instalación

1. Instala las dependencias de Composer:

```bash
composer install
```

2. Revisa el archivo `.env` en la raíz del proyecto y completa estos campos:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/public/index.php?page=google-callback
```

3. Configura en Google Cloud la misma URL de redirección que pongas en `GOOGLE_REDIRECT_URI`.

## Notas

- El archivo `.env` se carga desde `public/index.php`.
- Si cambias el dominio o la ruta del proyecto, actualiza `GOOGLE_REDIRECT_URI`.
