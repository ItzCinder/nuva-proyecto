<?php

$tab = ($_GET['collection'] ?? '') === 'environments' ? 'environments' : 'competitions';
$searchQuery = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Nuva - <?= nuva_escape($publicEnvironment['name'] ?? 'Explorar') ?></title>
        <script src="https://unpkg.com/lucide@latest">
        </script>
        <script src="/public/shared/js/layout.js?v=3" defer>
        </script>
        <?php foreach (['bootstrap.min.css','global.css?v=5','components/header.css?v=4','components/tabbar.css?v=4','public/competitions.css?v=5','components/collection.css?v=4','environments.css?v=6','explore.css?v=1'] as $css): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars(nuva_asset('/public/css/' . $css), ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    </head>
    <body>
        <header class="header">
            <?php require __DIR__ . '/../../../Shared/Views/components/header.php'; ?>
        </header>
        <main class="main">
            <div class="explore-layout">
                <?php if ($publicEnvironment): ?>
                <a class="nuva-anchor nuva-back" href="?page=explore&amp;collection=environments">Entornos públicos</a>
                <header class="explore-heading">
                    <p class="environment-eyebrow">ENTORNO PÚBLICO</p>
                    <div class="environment-identity">
                        <span class="environment-mark environment-mark--large"><?php $pictureWorkspace = $publicEnvironment; require __DIR__ . '/../../Administracion/Views/picture.php'; ?></span>
                        <h1><?= nuva_escape($publicEnvironment['name']) ?></h1>
                    </div>
                    <p class="environment-muted"><?= nuva_escape($publicEnvironment['description']) ?></p>
                </header>
                <?php elseif (!$exploreError): ?>
                <header class="explore-heading">
                    <p class="environment-eyebrow">DESCUBRE NUVA</p>
                    <h1>Explorar</h1>
                    <p class="environment-muted">Encuentra comunidades y competiciones públicas. Tus entornos y torneos están en Cuenta.</p>
                    <a class="nuva-anchor" href="#invitation">Tengo un código de invitación →</a>
                </header>
                <nav class="collection-tabs" aria-label="Explorar la plataforma">
                    <a class="collection-tabs__link <?= $tab === 'competitions' ? 'is-active' : '' ?>" <?= $tab === 'competitions' ? 'aria-current="page"' : '' ?> href="?page=explore">
                        <i data-lucide="trophy" aria-hidden="true">
                        </i> Competiciones</a>
                    <a class="collection-tabs__link <?= $tab === 'environments' ? 'is-active' : '' ?>" <?= $tab === 'environments' ? 'aria-current="page"' : '' ?> href="?page=explore&amp;collection=environments">
                        <i data-lucide="folders" aria-hidden="true">
                        </i> Entornos</a>
                </nav>
                <?php endif; ?>
                <?php if ($exploreError): ?>
                <p class="environment-notice environment-notice--error" role="alert"><?= nuva_escape($exploreError) ?></p>
                <?php elseif ($publicEnvironment || $tab === 'competitions'):
                    if ($publicEnvironment) {
                        $collectionPage = 'public-environment';
                    }
                    require __DIR__ . '/../../../Shared/Views/components/collection.php';
                else:
                    $matches = [];
                    foreach ($publicEnvironments as $environment) {
                        $text = $environment['name'] . ' ' . $environment['description'];
                        if ($searchQuery === '' || mb_stripos($text, $searchQuery) !== false) {
                            $matches[] = $environment;
                        }
                    } ?>
                <section>
                    <h2 class="section-title">Entornos públicos</h2>
                    <p class="environment-muted">Comunidades con al menos una competición publicada.</p>
                    <form method="get" class="environment-search">
                        <input type="hidden" name="page" value="explore">
                        <input type="hidden" name="collection" value="environments">
                        <label class="environment-field">Buscar por nombre o descripción<input name="q" type="search" value="<?= nuva_escape($searchQuery) ?>" placeholder="Buscar comunidad">
                        </label>
                        <button class="environment-button">Buscar</button>
                        <?php if ($searchQuery !== ''): ?>
                        <a class="nuva-anchor" href="?page=explore&amp;collection=environments">Limpiar</a>
                        <?php endif; ?>
                    </form>
                    <p class="environment-muted" role="status"><?= count($matches) ?> entornos encontrados</p>
                    <div class="environment-grid">
                        <?php foreach ($matches as $item): ?>
                        <a class="nuva-card environment-card environment-card--link" href="?page=public-environment&amp;id=<?= (int)$item['id'] ?>" aria-label="Ver entorno <?= nuva_escape($item['name']) ?>">
                            <div class="environment-card__top">
                                <span class="environment-mark" aria-hidden="true">
                                    <?php $pictureWorkspace = $item; require __DIR__ . '/../../Administracion/Views/picture.php'; ?>
                                </span>
                                <span class="nuva-badge environment-role">Público</span>
                            </div>
                            <h2><?= nuva_escape($item['name']) ?></h2>
                            <p class="environment-muted"><?= nuva_escape($item['description'] ?: 'Una comunidad de competiciones en Nuva.') ?></p>
                            <div class="environment-card__bottom">
                                <span class="environment-muted"><?= (int)$item['public_count'] ?> competiciones públicas</span>
                                <span class="environment-card__arrow" aria-hidden="true">→</span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!$matches): ?>
                    <p class="collection__empty nuva-card">No encontramos entornos públicos con esta búsqueda.</p>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
                <section class="nuva-card explore-invitation" id="invitation">
                    <div>
                        <h2 class="section-title">Unirte por código</h2>
                        <p class="environment-muted">¿Te invitaron a un entorno? Revisa el código y confirma tu incorporación<?= empty($_SESSION['authenticated']) ? ' al iniciar sesión' : '' ?>.</p>
                    </div>
                    <form method="get" class="environment-search">
                        <input type="hidden" name="page" value="environment-invitation">
                        <label class="environment-field" for="explore-invitation-code">Código de invitación<input id="explore-invitation-code" name="code" required minlength="8" maxlength="48" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="Ej.: K4T7X9MP">
                        </label>
                        <button class="environment-button">Revisar invitación</button>
                    </form>
                </section>
            </div>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
        <nav class="tab-bar">
            <?php require __DIR__ . '/../../../Shared/Views/components/tabbar.php'; ?>
        </nav>
    </body>
</html>
