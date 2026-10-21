<?php
// Recibe los entornos consultados por el controlador, sin datos de demostración.

$environmentQuery = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$environmentMatches = [];
foreach ($environmentItems ?? [] as $environmentItem) {
    if ($environmentQuery === '' || mb_stripos($environmentItem['name'], $environmentQuery) !== false) {
        $environmentMatches[] = $environmentItem;
    }
}

$environmentVisible = ($collectionAll ?? false) ? $environmentMatches : array_slice($environmentMatches, 0, 3);
?>
<section class="collection" aria-labelledby="environment-list-title">
    <div class="environment-heading">
        <div>
            <p class="environment-eyebrow">TU ESPACIO EN NUVA</p>
            <h1 id="environment-list-title" class="section-title">Mis entornos</h1>
            <p class="environment-muted">Crea un entorno o entra a uno del que ya seas miembro.</p>
        </div>
        <a class="nuva-btn" href="?page=environment-create">
            <i data-lucide="plus" aria-hidden="true">
            </i> Crear entorno</a>
    </div>
    <form class="environment-search" method="get" action="<?= ($collectionPage ?? '') === 'profile' ? '#collections' : '' ?>">
        <input type="hidden" name="page" value="<?= nuva_escape($collectionPage ?? 'my-environments') ?>">
        <?php if (($collectionPage ?? '') === 'profile'): ?>
        <input type="hidden" name="collection" value="environments">
        <?php endif; ?>
        <label class="environment-field" for="environment-search">Buscar por nombre
            <input id="environment-search" type="search" name="q" placeholder="Buscar entorno" value="<?= nuva_escape($environmentQuery) ?>">
        </label>
        <button type="submit" class="environment-button">Buscar</button>
        <?php if ($environmentQuery !== ''): ?>
        <a class="nuva-anchor" href="?page=<?= nuva_escape($collectionPage ?? 'my-environments') ?>&amp;collection=environments">Limpiar</a>
        <?php endif; ?>
    </form>
    <?php if (!empty($environmentError)): ?>
    <p class="environment-notice environment-notice--error" role="alert"><?= nuva_escape($environmentError) ?></p>
    <?php else: ?>
    <p class="environment-muted" role="status">Mostrando <?= count($environmentVisible) ?> de <?= count($environmentMatches) ?> entornos</p>
    <?php if ($environmentVisible): ?>
    <div class="environment-grid">
        <?php foreach ($environmentVisible as $environmentItem): ?>
        <a class="nuva-card environment-card environment-card--link" href="?page=environment&amp;id=<?= (int) $environmentItem['id'] ?>" aria-label="Abrir entorno <?= nuva_escape($environmentItem['name']) ?>">
            <div class="environment-card__top">
                <span class="environment-mark" aria-hidden="true">
                    <?php $pictureWorkspace = $environmentItem; require __DIR__ . '/picture.php'; ?>
                </span>
                <span class="nuva-badge environment-role"><?= nuva_escape($environmentItem['role_name']) ?></span>
            </div>
            <h2><?= nuva_escape($environmentItem['name']) ?></h2>
            <p class="environment-muted"><?= nuva_escape($environmentItem['description'] ?: 'Torneos y miembros de este entorno.') ?></p>
            <div class="environment-card__bottom">
                <span class="environment-muted"><?= (int) $environmentItem['member_count'] ?> miembros</span>
                <span class="environment-card__arrow" aria-hidden="true">→</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="collection__empty nuva-card">
        <i data-lucide="folders" aria-hidden="true">
        </i>
        <h2 class="section-title"><?= $environmentQuery !== '' ? 'No encontramos entornos con ese nombre' : 'Todavía no perteneces a un entorno' ?></h2>
        <p><?= $environmentQuery !== '' ? 'Prueba con otra búsqueda.' : 'Crea tu propio entorno o acepta una invitación de tu organización.' ?></p>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php if (!($collectionAll ?? false)): ?>
    <a class="nuva-anchor environment-all" href="?page=my-environments&amp;q=<?= nuva_escape(rawurlencode($environmentQuery)) ?>">Ver todos los entornos →</a>
    <?php endif; ?>
    <p class="environment-muted">¿Buscas otra comunidad? <a class="nuva-anchor" href="?page=explore&amp;collection=environments">Explorar entornos y unirte por código →</a>
    </p>
</section>
