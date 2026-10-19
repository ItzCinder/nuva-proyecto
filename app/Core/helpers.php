<?php

// Preparar texto para mostrarlo en HTML sin interpretarlo como etiquetas.
function nuva_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Campo que permite comprobar que el formulario se envió desde esta sesión.
function nuva_csrf_field(): void
{
    $token = nuva_escape($_SESSION['csrf_token'] ?? '');
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// Mostrar el marcador con hasta dos decimales; un valor pendiente se muestra con un guion.
function nuva_score($value): string
{
    if ($value === null) {
        return '—';
    }

    $formatted = number_format((float) $value, 2, '.', '');
    // Por ejemplo, 3.00 se muestra como 3 y 3.50 como 3.5.
    return rtrim(rtrim($formatted, '0'), '.');
}

function nuva_search_text(string $value): string
{
    $letters = [
        'Á' => 'a',
        'É' => 'e',
        'Í' => 'i',
        'Ó' => 'o',
        'Ú' => 'u',
        'Ü' => 'u',
        'Ñ' => 'n',
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n',
    ];
    return strtolower(strtr($value, $letters));
}

function nuva_local_date($value, string $format = 'd/m/Y H:i'): string
{
    if (!$value || str_starts_with((string) $value, '0000-00-00')) {
        return 'Sin definir';
    }
    $date = new DateTimeImmutable($value, new DateTimeZone('UTC'));
    $date = $date->setTimezone(new DateTimeZone('America/Montevideo'));
    return $date->format($format);
}

function nuva_form_value(string $key, $default = ''): string
{
    if (isset($_POST[$key]) && is_string($_POST[$key])) {
        return $_POST[$key];
    }
    return (string) $default;
}
