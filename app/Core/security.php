<?php

// Validar primero, convertir después. (int) '12abc' daría 12 sin avisar.
function nuva_integer($value, string $label, int $minimum = 1, int $maximum = 2147483647): int
{
    if (!is_string($value) && !is_int($value)) {
        throw new InvalidArgumentException($label . ': escribe un número entero.');
    }

    $text = trim((string) $value);
    if (!preg_match('/^\d+$/D', $text)) {
        throw new InvalidArgumentException($label . ': usa solo números enteros, sin letras ni decimales.');
    }

    $normalized = ltrim($text, '0');
    if ($normalized === '') {
        $normalized = '0';
    }
    $number = filter_var($normalized, FILTER_VALIDATE_INT);
    if ($number === false || $number < $minimum || $number > $maximum) {
        throw new InvalidArgumentException($label . ': debe estar entre ' . $minimum . ' y ' . $maximum . '.');
    }
    return $number;
}

// Comprobar la forma de las entradas antes de abrir una sesión o consultar la base.
function nuva_request_error(array $query, array $form): string
{
    foreach ($query as $value) {
        if (!is_string($value) || !nuva_valid_text($value, 2000)) {
            return 'La dirección contiene un dato inválido.';
        }
    }

    $listFields = ['modules', 'templates', 'manual', 'load', 'visible'];
    foreach ($form as $field => $value) {
        if (is_array($value)) {
            if (!in_array($field, $listFields, true) || !array_is_list($value) || count($value) > 100) {
                return 'La selección del formulario no es válida.';
            }
            foreach ($value as $selected) {
                if (!is_string($selected) || !nuva_valid_text($selected, 100)) {
                    return 'La selección del formulario no es válida.';
                }
            }
        } elseif (!is_string($value) || !nuva_valid_text($value, 10000)) {
            return 'El formulario contiene un dato inválido.';
        }
    }
    return '';
}

function nuva_valid_text(string $value, int $maximumLength): bool
{
    if (!mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $maximumLength) {
        return false;
    }
    // Los textos largos pueden tener saltos de línea y tabulaciones, pero no controles ocultos.
    return !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value);
}

// Registrar el hecho, nunca contraseñas, códigos de Google, cookies ni el contenido del formulario.
function nuva_security_event(string $event): void
{
    $userId = null;
    if (isset($_SESSION['user_id']) && is_scalar($_SESSION['user_id']) && ctype_digit((string) $_SESSION['user_id'])) {
        $userId = (int) $_SESSION['user_id'];
    }
    $entry = ['time' => gmdate('c'), 'event' => $event, 'user_id' => $userId];
    error_log('[Nuva seguridad] ' . json_encode($entry, JSON_UNESCAPED_UNICODE));
}

function nuva_log_error(string $area, Throwable $exception): void
{
    // Los mensajes de SQL o de proveedores externos pueden contener datos privados.
    error_log('[Nuva error] ' . json_encode([
        'time' => gmdate('c'),
        'area' => $area,
        'type' => get_class($exception),
        'code' => $exception->getCode(),
    ], JSON_UNESCAPED_UNICODE));
}

function nuva_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store');
}
