<?php

namespace App\Modules\Estadisticas\Services;

use App\Modules\Estadisticas\Models\Statistics;

class StatisticsBrowser
{
    public static function build(array $trials, array $manual, array $query): array
    {
        // 1. Leer las opciones de la URL. Nunca aceptamos listas como valores de filtros.
        $section = self::queryText($query, 'view');
        if (!in_array($section, ['summary', 'trials', 'manual'], true)) {
            $section = 'summary';
            // Si solo hay registros manuales, abrirlos directamente en lugar de mostrar ceros.
            if (!$trials && $manual) {
                $section = 'manual';
            }
        }

        $hasReal = false;
        $hasDemo = false;
        foreach (array_merge($trials, $manual) as $record) {
            if ($record['demonstration']) {
                $hasDemo = true;
            } else {
                $hasReal = true;
            }
        }

        $origin = self::queryText($query, 'origin');
        if (!in_array($origin, ['real', 'demo', 'all'], true)) {
            $origin = 'real';
            if (!$hasReal && $hasDemo) {
                $origin = 'demo';
            }
        }

        // 2. Mostrar únicamente participantes de pruebas que ya puede consultar el usuario.
        $people = [];
        foreach ($trials as $trial) {
            $people[(int) $trial['participant_id']] = $trial['participant_name'];
        }

        asort($people);

        $person = (int) self::queryText($query, 'person');
        if (!isset($people[$person])) {
            $person = 0;
        }

        $filteredTrials = [];
        foreach ($trials as $trial) {
            if (!self::matchesOrigin($trial, $origin)) {
                continue;
            }

            if ($person !== 0 && (int) $trial['participant_id'] !== $person) {
                continue;
            }

            $filteredTrials[] = $trial;
        }

        $trials = $filteredTrials;
        usort($trials, [Statistics::class, 'newestFirst']);

        $filteredManual = [];
        foreach ($manual as $record) {
            if (self::matchesOrigin($record, $origin)) {
                $filteredManual[] = $record;
            }
        }

        $manual = $filteredManual;

        // 3. Elegir un registro para abrir su detalle.
        $selected = null;
        $requestedRecord = self::queryText($query, 'record');
        foreach ($trials as $trial) {
            if ($trial['record_id'] === $requestedRecord) {
                $selected = $trial;
            }
        }

        $manualTypes = [];
        foreach ($manual as $record) {
            $manualTypes[(int) $record['stat_type_id']] = $record['definition']['name'];
        }

        $manualType = (int) self::queryText($query, 'stat');
        if (!isset($manualTypes[$manualType])) {
            $manualType = array_key_first($manualTypes) ?? 0;
        }

        $selectedManualRows = [];
        $manualSelected = null;
        foreach ($manual as $record) {
            if ((int) $record['stat_type_id'] !== $manualType) {
                continue;
            }

            $selectedManualRows[] = $record;
            if ($record['record_id'] === $requestedRecord && self::queryText($query, 'kind') === 'manual') {
                $manualSelected = $record;
            }
        }

        return [
            'section' => $section,
            'origin' => $origin,
            'people' => $people,
            'person' => $person,
            'trials' => $trials,
            'manual' => $selectedManualRows,
            'selected' => $selected,
            'manualTypes' => $manualTypes,
            'manualType' => $manualType,
            'manualSelected' => $manualSelected,
        ];
    }

    private static function queryText(array $query, string $key): string
    {
        if (!isset($query[$key]) || !is_string($query[$key])) {
            return '';
        }

        return $query[$key];
    }

    private static function matchesOrigin(array $record, string $origin): bool
    {
        if ($origin === 'all') {
            return true;
        }

        if ($origin === 'demo') {
            return (bool) $record['demonstration'];
        }

        return !(bool) $record['demonstration'];
    }

    public static function page(array $rows, $requested, int $size = 10): array
    {
        $pages = max(1, (int) ceil(count($rows) / $size));
        $current = 1;
        if (is_string($requested) && ctype_digit($requested)) {
            $current = (int) $requested;
        }

        $current = max(1, min($pages, $current));
        $offset = ($current - 1) * $size;

        return [
            'rows' => array_slice($rows, $offset, $size),
            'current' => $current,
            'pages' => $pages,
            'total' => count($rows),
        ];
    }
}
