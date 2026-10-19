<?php

namespace App\Modules\ConsultaPublica\Services;

use app\Models\Tournament;

class TournamentCollection
{
    public static function items(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $icon = 'trophy';
            if ($row['discipline_category'] === 'mental') {
                $icon = 'chess-king';
            } elseif ($row['discipline_category'] === 'esports') {
                $icon = 'gamepad-2';
            }

            $items[] = [
                'id' => (int) $row['id'],
                'title' => $row['name'],
                'sport' => $row['discipline'],
                'category' => Tournament::CATEGORIES[$row['discipline_category']] ?? 'Otros',
                'status' => $row['status'],
                'format' => $row['format_name'],
                'players' => (int) ($row['has_team'] ? $row['team_count'] : $row['member_count']),
                'unit' => $row['has_team'] ? 'Equipos' : 'Participantes',
                'dates' => self::date($row['start_date']) . ' — ' . self::date($row['end_date']),
                'icon' => $icon,
                'picture' => $row['workspace_picture'] ?? '',
            ];
        }

        return $items;
    }

    public static function build(array $rows, string $type, string $page, bool $showAll, array $query): array
    {
        $items = self::items($rows);

        $filters = ['q' => self::queryText($query, 'q')];
        $options = [];
        $filterLabels = [
            'category' => 'Categoría',
            'sport' => 'Disciplina',
            'status' => 'Estado',
            'format' => 'Formato'
        ];
        foreach ($filterLabels as $key => $label) {
            $values = [];
            if ($key === 'category') {
                $values = array_values(Tournament::CATEGORIES);
            } else {
                foreach ($items as $item) {
                    if (!in_array($item[$key], $values, true)) {
                        $values[] = $item[$key];
                    }
                }
            }

            $options[$key] = ['label' => $label, 'values' => $values];
            $selectedValue = self::queryText($query, $key);
            $filters[$key] = '';
            if (in_array($selectedValue, $values, true)) {
                $filters[$key] = $selectedValue;
            }
        }

        $activeFilters = [];
        foreach ($filters as $key => $value) {
            if ($value !== '') {
                $activeFilters[$key] = $value;
            }
        }

        $matches = [];
        foreach ($items as $item) {
            if (self::matchesFilters($item, $filters)) {
                $matches[] = $item;
            }
        }

        $visibleItems = $matches;
        if (!$showAll) {
            $visibleItems = array_slice($matches, 0, 3);
        }

        $title = 'Mis torneos';
        $allPage = 'my-tournaments';
        $backUrl = '?page=profile&collection=' . $type . '#collections';
        if ($type === 'competitions') {
            $title = 'Competiciones públicas';
            $allPage = 'competitions-all';
            $backUrl = '?page=explore';
        }

        $baseQuery = ['page' => $page];
        if (in_array($page, ['profile', 'explore'], true)) {
            $baseQuery['collection'] = $type;
        }

        if ($page === 'public-environment') {
            $baseQuery['id'] = self::queryText($query, 'id');
        }

        $resetUrl = '?' . http_build_query($baseQuery, '', '&', PHP_QUERY_RFC3986);
        if ($page === 'profile') {
            $resetUrl .= '#collections';
        }

        return [
            'type' => $type,
            'page' => $page,
            'showAll' => $showAll,
            'title' => $title,
            'filters' => $filters,
            'options' => $options,
            'activeFilters' => $activeFilters,
            'matches' => $matches,
            'visibleItems' => $visibleItems,
            'baseQuery' => $baseQuery,
            'resetUrl' => $resetUrl,
            'allUrl' => '?' . http_build_query(['page' => $allPage] + $activeFilters, '', '&', PHP_QUERY_RFC3986),

            'backUrl' => $backUrl,
        ];
    }

    private static function matchesFilters(array $item, array $filters): bool
    {
        if ($filters['q'] !== '') {
            $searchText = nuva_search_text($item['title'] . ' ' . $item['sport']);
            $searchQuery = nuva_search_text($filters['q']);
            if (!str_contains($searchText, $searchQuery)) {
                return false;
            }
        }

        foreach (['category', 'sport', 'status', 'format'] as $key) {
            if ($filters[$key] !== '' && $item[$key] !== $filters[$key]) {
                return false;
            }
        }

        return true;
    }

    private static function queryText(array $query, string $key): string
    {
        if (!is_string($query[$key] ?? null)) {
            return '';
        }

        return trim($query[$key]);
    }

    private static function date($value): string
    {
        if (!$value || str_starts_with((string) $value, '0000-00-00')) {
            return 'Sin fecha';
        }

        $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        $date = $date->setTimezone(new \DateTimeZone('America/Montevideo'));
        return $date->format('d/m/Y');
    }
}
