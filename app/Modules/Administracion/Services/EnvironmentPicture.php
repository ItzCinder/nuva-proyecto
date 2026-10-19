<?php

namespace App\Modules\Administracion\Services;

class EnvironmentPicture
{
    public const MAX_BYTES = 2097152;
    private const PUBLIC_FOLDER = '/public/assets/uploads/environments/';

    public static function save(array $file): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if (!is_int($error) || $error !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('No se pudo recibir la foto. Elige una imagen JPG, PNG o WebP de hasta 2 MB.');
        }

        $temporary = $file['tmp_name'] ?? '';
        $size = $file['size'] ?? 0;
        if (!is_string($temporary) || !is_int($size) || $size <= 0 || $size > self::MAX_BYTES || !is_uploaded_file($temporary)) {
            throw new \InvalidArgumentException('La foto debe ser una imagen de hasta 2 MB.');
        }

        // Comprobar el contenido real: cambiar la extensión no convierte un archivo en imagen.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $dimensions = @getimagesize($temporary);
        if (!isset($extensions[$mime]) || !$dimensions || $dimensions['mime'] !== $mime) {
            throw new \InvalidArgumentException('Usa una imagen JPG, PNG o WebP válida. No se permiten SVG ni archivos ejecutables.');
        }
        if ($dimensions[0] > 6000 || $dimensions[1] > 6000 || $dimensions[0] * $dimensions[1] > 16000000) {
            throw new \InvalidArgumentException('La imagen es demasiado grande. Usa una foto de hasta 6000 píxeles por lado y 16 millones de píxeles en total.');
        }

        $root = dirname(__DIR__, 4);
        $directory = $root . self::PUBLIC_FOLDER;
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new \InvalidArgumentException('La carpeta de fotos no tiene permisos de escritura. Revisa la instalación de Nuva.');
        }

        $path = self::PUBLIC_FOLDER . bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($temporary, $root . $path)) {
            throw new \InvalidArgumentException('No se pudo guardar la foto. Inténtalo de nuevo.');
        }
        chmod($root . $path, 0644);
        return $path;
    }

    public static function remove(string $path): void
    {
        // Solo borrar subidas propias, nunca imágenes de demostración ni archivos ajenos.
        if (!preg_match('~^/public/assets/uploads/environments/[a-f0-9]{40}\.(jpg|png|webp)$~D', $path)) {
            return;
        }
        $file = dirname(__DIR__, 4) . $path;
        if (is_file($file) && !unlink($file)) {
            error_log('[Nuva fotos] No se pudo retirar una foto de entorno en desuso.');
        }
    }
}
