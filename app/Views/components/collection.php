<?php
if ($collectionType === 'environments') {
    require __DIR__ . '/../environments/collection.php';
    return;
}
// Variables de entrada: $collectionType, $collectionPage y $collectionAll.
// El alcance local permite reutilizar el listado sin contaminar la vista padre.
(static function (string $type, string $page, bool $showAll, array $rows): void {
    $items = [];
    foreach ($rows as $row) {
        $date = static function ($value): string {
            if (!$value || str_starts_with((string)$value, '0000-00-00')) return 'Sin fecha';
            return (new DateTimeImmutable($value, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Montevideo'))->format('d/m/Y');
        };
        $items[] = [
            'id' => (int)$row['id'], 'title' => $row['name'], 'sport' => $row['discipline'],
            'category' => \app\Models\Tournament::CATEGORIES[$row['discipline_category']] ?? 'Otros',
            'status' => $row['status'], 'format' => $row['format_name'],
            'players' => (int)($row['has_team'] ? $row['team_count'] : $row['member_count']),
            'unit' => $row['has_team'] ? 'Equipos' : 'Participantes',
            'dates' => $date($row['start_date']) . ' — ' . $date($row['end_date']),
            'icon' => $row['discipline_category'] === 'mental' ? 'chess-king' : ($row['discipline_category'] === 'esports' ? 'gamepad-2' : 'trophy'),
        ];
    }
    $isEnvironment = false;
    $title = ['competitions' => 'Competiciones públicas', 'tournaments' => 'Mis torneos', 'environments' => 'Mis entornos'][$type];
    $allPage = ['competitions' => 'competitions-all', 'tournaments' => 'my-tournaments', 'environments' => 'my-environments'][$type];
    $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $input = static fn (string $key): string => is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
    $normalize = static fn (string $value): string => strtolower(strtr($value, ['Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ü'=>'u', 'Ñ'=>'n', 'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u', 'ñ'=>'n']));
    $filters = ['q' => $input('q')];
    $options = [];
    if (!$isEnvironment) {
        foreach (['category' => 'Categoría', 'sport' => 'Disciplina', 'status' => 'Estado', 'format' => 'Formato'] as $key => $label) {
            $values = $key === 'category'
                ? array_values(\app\Models\Tournament::CATEGORIES)
                : array_values(array_unique(array_column($items, $key)));
            $options[$key] = ['label' => $label, 'values' => $values];
            $filters[$key] = in_array($input($key), $values, true) ? $input($key) : '';
        }
    }
    $activeFilters = array_filter($filters, static fn (string $value): bool => $value !== '');
    $matches = array_values(array_filter($items, static function (array $item) use ($filters, $normalize): bool {
        if ($filters['q'] !== '' && !str_contains($normalize($item['title'] . ' ' . ($item['sport'] ?? '')), $normalize($filters['q']))) {
            return false;
        }
        foreach (['category', 'sport', 'status', 'format'] as $key) {
            if (($filters[$key] ?? '') !== '' && ($item[$key] ?? '') !== $filters[$key]) {
                return false;
            }
        }
        return true;
    }));
    $visibleItems = $showAll ? $matches : array_slice($matches, 0, 3);
    $baseQuery = ['page' => $page];
    if (in_array($page,['profile','explore'],true)) {
        $baseQuery['collection'] = $type;
    }
    $url = static fn (array $query): string => '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    if ($page === 'public-environment') $baseQuery['id'] = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
    $resetUrl = $url($baseQuery) . ($page === 'profile' ? '#collections' : '');
    $allUrl = $url(['page' => $allPage] + $activeFilters);
    $backUrl = $type === 'competitions' ? '?page=explore' : '?page=profile&collection=' . $type . '#collections';
?>
<section class="comp collection" aria-labelledby="collection-title">
    <?php if ($showAll && $page !== 'explore' && $page !== 'profile'): ?>
        <a class="nuva-anchor collection__back" href="<?= $escape($backUrl) ?>">← <?= $type === 'competitions' ? 'Volver a competiciones' : 'Volver a mi cuenta' ?></a>
    <?php endif; ?>
    <div class="collection__heading">
        <h2 id="collection-title" class="section-title"><?= $showAll && $type === 'competitions' ? 'Todas las competiciones' : $escape($title) ?></h2>
        <?php if (!$showAll): ?>
            <a class="nuva-anchor" href="<?= $escape($allUrl) ?>" aria-label="Ver todo: <?= $escape($title) ?>">Ver todo <span aria-hidden="true">→</span></a>
        <?php endif; ?>
    </div>
    <details class="collection__filters nuva-card" <?= $activeFilters ? 'open' : '' ?>>
        <summary><i data-lucide="sliders-horizontal" aria-hidden="true"></i> Filtros<?= $activeFilters ? ' · ' . count($activeFilters) : '' ?></summary>
        <form class="collection__form" method="get" action="<?= $page === 'profile' ? '#collections' : '' ?>">
            <?php foreach ($baseQuery as $key => $value): ?>
                <input type="hidden" name="<?= $escape($key) ?>" value="<?= $escape($value) ?>">
            <?php endforeach; ?>
            <div class="collection__field collection__field--search">
                <label for="collection-search">Buscar por nombre<?= $isEnvironment ? '' : ' o deporte' ?></label>
                <input id="collection-search" type="search" name="q" value="<?= $escape($filters['q']) ?>" placeholder="<?= $isEnvironment ? 'Buscar entorno' : 'Buscar competición' ?>">
            </div>
            <?php foreach ($options as $key => $option): ?>
                <div class="collection__field">
                    <label for="filter-<?= $escape($key) ?>"><?= $escape($option['label']) ?></label>
                    <select id="filter-<?= $escape($key) ?>" name="<?= $escape($key) ?>">
                        <option value="">Todos</option>
                        <?php foreach ($option['values'] as $value): ?>
                            <option value="<?= $escape($value) ?>" <?= $filters[$key] === $value ? 'selected' : '' ?>><?= $escape($value) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
            <div class="collection__actions">
                <button class="nuva-btn" type="submit">Aplicar filtros</button>
                <a class="nuva-anchor" href="<?= $escape($resetUrl) ?>">Limpiar filtros</a>
            </div>
        </form>
    </details>
    <p class="collection__count" role="status">Mostrando <?= count($visibleItems) ?> de <?= count($matches) ?> resultados</p>
    <?php if ($visibleItems): ?>
        <div class="comp__section">
            <?php foreach ($visibleItems as $item): ?>
                <?php require __DIR__ . '/competition-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="collection__empty nuva-card">
            <i data-lucide="<?= $isEnvironment ? 'folders' : 'search' ?>" aria-hidden="true"></i>
            <h3><?= $activeFilters ? 'No hay resultados con estos filtros' : 'Todavía no hay torneos para mostrar' ?></h3>
            <p><?= $activeFilters ? 'Prueba otra búsqueda o limpia los filtros.' : 'Los torneos aparecerán aquí cuando estén disponibles.' ?></p>
            <?php if ($activeFilters): ?><a class="nuva-anchor" href="<?= $escape($resetUrl) ?>">Limpiar filtros</a><?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php })($collectionType, $collectionPage, $collectionAll ?? false, $tournamentItems ?? []); ?>
