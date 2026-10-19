<?php
use App\Modules\Estadisticas\Services\Physics;
use App\Modules\Estadisticas\Services\StatisticsBrowser;

$browse = $browse ?? StatisticsBrowser::build($trials, $manualRecords, $_GET);
if ($history) {
    $browse['section'] = 'history';
}

$section = $browse['section'];
$params = [
    'page' => 'tournament-statistics',
    'id' => (int)$tournament['id'],
    'view' => $section,
    'origin' => $browse['origin'],
    'person' => $browse['person'] ?: null,
    'stat' => $browse['manualType'] ?: null
];
?>
<section id="statistics-browser" class="statistics-browser">
    <nav class="statistics-view-nav" aria-label="Consulta de estadísticas">
        <?php $views = ['summary' => 'Resumen','trials' => 'Pruebas'];
if ($manualRecords) {
    $views['manual'] = 'Manuales';
}
if ($history) {
    $views['history'] = 'Historial';
}
foreach ($views as $key => $label): ?>
        <a href="<?= nuva_escape(nuva_statistics_link($params, [
            'view' => $key,
            'history' => $key === 'history' ? ($history[0]['record_id'] ?? null) : null,
            'kind' => $key === 'history' && isset($history[0]['definition']) ? 'manual' : null
        ])) ?>#statistics-browser" <?= $section === $key ? 'aria-current="page"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if ($section !== 'history'): ?>
    <form method="get" action="#statistics-browser" class="statistics-filters">
        <input type="hidden" name="page" value="tournament-statistics">
        <input type="hidden" name="id" value="<?= (int)$tournament['id'] ?>">
        <input type="hidden" name="view" value="<?= nuva_escape($section) ?>">
        <?php
        $hiddenFilters = [];
        if ($section === 'manual') {
            $hiddenFilters = ['stat'];
        }
        ?>
        <?php foreach ($hiddenFilters as $key): ?>
        <input type="hidden" name="<?= $key ?>" value="<?= nuva_escape($params[$key] ?? '') ?>">
        <?php endforeach; ?>
        <label class="environment-field">Datos<select name="origin">
                <?php foreach (['real' => 'Datos reales','demo' => 'Ejemplos','all' => 'Reales y ejemplos'] as $key => $label): ?>
                <option value="<?= $key ?>" <?= $browse['origin'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if ($section !== 'manual' && $browse['people']): ?>
        <label class="environment-field">Participante<select name="person">
                <option value="">Todos los participantes</option>
                <?php foreach ($browse['people'] as $id => $name): ?>
                <option value="<?= (int)$id ?>" <?= $browse['person'] === $id ? 'selected' : '' ?>><?= nuva_escape($name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>
        <button class="environment-button">Aplicar filtros</button>
    </form>
    <?php endif; ?>
    <?php
// Cada pestaña se dibuja en su propio archivo.
if ($section === 'summary') {
    require __DIR__ . '/partials/summary.php';
} elseif ($section === 'trials') {
    require __DIR__ . '/partials/trials.php';

} elseif ($section === 'manual') {
    require __DIR__ . '/partials/manual.php';
} elseif ($section === 'history') {
    require __DIR__ . '/partials/history.php';
}
?>
</section>
