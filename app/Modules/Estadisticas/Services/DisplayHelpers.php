<?php

function nuva_stat_number($value): string
{
    if ($value === null) {
        return 'No disponible';
    }
    if ($value != 0 && abs($value) < 0.00000001) {
        return str_replace('.', ',', sprintf('%.3g', $value));
    }
    $formatted = number_format((float) $value, 8, ',', '.');
    return rtrim(rtrim($formatted, '0'), ',');
}

function nuva_measurement(array $result): array
{
    $uncertainty = $result['uncertainty'];
    if ($uncertainty === null) {
        return [nuva_stat_number($result['value']), null];
    }
    if ($uncertainty == 0) {
        return [nuva_stat_number($result['value']), '0'];
    }

    // Redondear la medición y su margen al mismo lugar decimal.
    // Esta cuenta conserva dos cifras significativas en la incertidumbre.
    $decimalPlaces = 1 - (int) floor(log10(abs($uncertainty)));
    if ($decimalPlaces > 8) {
        return [nuva_stat_number($result['value']), nuva_stat_number($uncertainty)];
    }
    $roundedValue = round($result['value'], $decimalPlaces);
    $roundedUncertainty = round($uncertainty, $decimalPlaces);
    $visibleDecimals = max(0, $decimalPlaces);
    return [
        number_format($roundedValue, $visibleDecimals, ',', '.'),
        number_format($roundedUncertainty, $visibleDecimals, ',', '.'),
    ];
}

function nuva_trial_value(array $values, string $key, $default = ''): string
{
    if (isset($values[$key]) && is_scalar($values[$key])) {
        return (string) $values[$key];
    }
    return (string) $default;
}

// Todos los campos de mediciones usan el mismo aspecto y las mismas restricciones numéricas.
function nuva_measurement_field(array $values, string $key, string $label, string $unit, bool $required = false, string $hint = '', string $placeholder = ''): void
{
    $minimum = '-1000000';
    $maximum = '1000000';
    if (in_array($key, ['distance', 'time', 'segment_distance', 'segment_time', 'mass'], true)) {
        $minimum = '0.00000001';
    } elseif (str_starts_with($key, 'u_') || in_array($key, ['speed', 'initial_speed', 'work_distance'], true)) {
        $minimum = '0';
    } elseif ($key === 'angle') {
        $minimum = '-360';
        $maximum = '360';
    }
    require __DIR__ . '/../Views/partials/measurement-field.php';
}

function nuva_statistics_link(array $parameters, array $changes = []): string
{
    $query = array_replace($parameters, $changes);
    $visibleParameters = [];
    foreach ($query as $key => $value) {
        if ($value !== null && $value !== '') {
            $visibleParameters[$key] = $value;
        }
    }
    return '?' . http_build_query($visibleParameters);
}
