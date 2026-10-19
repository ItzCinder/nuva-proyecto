<?php
use App\Modules\ConsultaPublica\Services\TournamentCollection;

if ($collectionType === 'environments') {
    require __DIR__ . '/../../../Modules/Administracion/Views/collection.php';
    return;
}

// La vista muestra los datos; el servicio prepara tarjetas y filtros.
$collection = TournamentCollection::build(
    $tournamentItems ?? [],
    $collectionType,
    $collectionPage,
    $collectionAll ?? false,
    $_GET
);
?>
<section class="comp collection" aria-labelledby="collection-title">
    <?php if ($collection['showAll'] && $collection['page'] !== 'explore' && $collection['page'] !== 'profile' && $collection['page'] !== 'public-environment'): ?>
    <a class="nuva-back nuva-anchor collection__back" href="<?= nuva_escape($collection['backUrl']) ?>"><?= $collection['type'] === 'competitions' ? 'Volver a competiciones' : 'Volver a mi cuenta' ?></a>
    <?php endif; ?>
    <div class="collection__heading">
        <h2 id="collection-title" class="section-title"><?= $collection['showAll'] && $collection['type'] === 'competitions' ? 'Todas las competiciones' : nuva_escape($collection['title']) ?></h2>
        <?php if (!$collection['showAll']): ?>
        <a class="nuva-anchor" href="<?= nuva_escape($collection['allUrl']) ?>" aria-label="Ver todo: <?= nuva_escape($collection['title']) ?>">Ver todo <span aria-hidden="true">→</span>
        </a>
        <?php endif; ?>
    </div>
    <details class="collection__filters nuva-card" <?= $collection['activeFilters'] ? 'open' : '' ?>>
        <summary>
            <i data-lucide="sliders-horizontal" aria-hidden="true">
            </i> Filtros<?= $collection['activeFilters'] ? ' · ' . count($collection['activeFilters']) : '' ?></summary>
        <form class="collection__form" method="get" action="<?= $collection['page'] === 'profile' ? '#collections' : '' ?>">
            <?php foreach ($collection['baseQuery'] as $key => $value): ?>
            <input type="hidden" name="<?= nuva_escape($key) ?>" value="<?= nuva_escape($value) ?>">
            <?php endforeach; ?>
            <div class="collection__field collection__field--search">
                <label for="collection-search">Buscar por nombre o deporte</label>
                <input id="collection-search" type="search" name="q" value="<?= nuva_escape($collection['filters']['q']) ?>" placeholder="Buscar competición">
            </div>
            <?php foreach ($collection['options'] as $key => $option): ?>
            <div class="collection__field">
                <label for="filter-<?= nuva_escape($key) ?>"><?= nuva_escape($option['label']) ?></label>
                <select id="filter-<?= nuva_escape($key) ?>" name="<?= nuva_escape($key) ?>">
                    <option value="">Todos</option>
                    <?php foreach ($option['values'] as $value): ?>
                    <option value="<?= nuva_escape($value) ?>" <?= $collection['filters'][$key] === $value ? 'selected' : '' ?>><?= nuva_escape($value) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
            <div class="collection__actions">
                <button class="nuva-btn" type="submit">Aplicar filtros</button>
                <a class="nuva-anchor" href="<?= nuva_escape($collection['resetUrl']) ?>">Limpiar filtros</a>
            </div>
        </form>
    </details>
    <p class="collection__count" role="status">Mostrando <?= count($collection['visibleItems']) ?> de <?= count($collection['matches']) ?> resultados</p>
    <?php if ($collection['visibleItems']): ?>
    <div class="comp__section">
        <?php foreach ($collection['visibleItems'] as $item): ?>
        <?php require __DIR__ . '/../../../Modules/ConsultaPublica/Views/components/competition-card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="collection__empty nuva-card">
        <i data-lucide="search" aria-hidden="true">
        </i>
        <h3><?= $collection['activeFilters'] ? 'No hay resultados con estos filtros' : 'Todavía no hay torneos para mostrar' ?></h3>
        <p><?= $collection['activeFilters'] ? 'Prueba otra búsqueda o limpia los filtros.' : 'Los torneos aparecerán aquí cuando estén disponibles.' ?></p>
        <?php if ($collection['activeFilters']): ?>
        <a class="nuva-anchor" href="<?= nuva_escape($collection['resetUrl']) ?>">Limpiar filtros</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>
