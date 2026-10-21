<?php

$publicTournaments = $publicTournaments ?? [];
$myTournaments = $myTournaments ?? [];
$myEnvironments = $myEnvironments ?? [];
$categoryLabels = [
    'tradicional' => 'Deporte tradicional',
    'mental' => 'Deporte mental',
    'esports' => 'eSports',
    'otros' => 'Otros'
];
$categoryIcons = [
    'tradicional' => 'volleyball',
    'mental' => 'chess-king',
    'esports' => 'gamepad-2',
    'otros' => 'shapes'
];
$categoryCounts = array_fill_keys(array_keys($categoryLabels), 0);
foreach ($publicTournaments as $tournament) {
    if (isset($categoryCounts[$tournament['discipline_category']])) {
        $categoryCounts[$tournament['discipline_category']]++;
    }
}

$featured = [];
foreach ($publicTournaments as $tournament) {
    if ($tournament['status'] !== 'Finalizado') {
        $featured[] = $tournament;
    }
}
usort($featured, [App\Controllers\HomeController::class, 'compareFeatured']);
$featured = array_slice($featured, 0, 3);
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://unpkg.com/lucide@latest">
        </script>
        <link href="/public/css/bootstrap.min.css" rel="stylesheet">
        <script src="/public/js/bootstrap.bundle.min.js" defer>
        </script>
        <link rel="stylesheet" href="/public/css/global.css?v=5">
        <link rel="stylesheet" href="/public/shared/css/header.css?v=4">
        <link rel="stylesheet" href="/public/shared/css/tabbar.css?v=4">
        <link rel="stylesheet" href="/public/modules/ConsultaPublica/css/index.css?v=4">
        <script src="/public/shared/js/layout.js?v=3" defer>
        </script>
        <title>Nuva - Inicio</title>
    </head>
    <body>
        <header class="header">
            <?php require __DIR__ . '/../../../Shared/Views/components/header.php'; ?>
        </header>
        <main class="main home-main">
            <section class="hero__container" aria-labelledby="home-title">
                <span class="home-eyebrow">TU INICIO EN NUVA</span>
                <h1 id="home-title" class="hero__title">Organiza y descubre competiciones</h1>
                <p class="hero__text">Crea torneos para tu club o grupo y consulta otras competiciones públicas.</p>
                <div class="home-actions">
                    <a class="hero__btn nuva-btn" href="?page=guide">Cómo usar Nuva <i data-lucide="arrow-right" aria-hidden="true">
                        </i>
                    </a>
                    <a class="home-link" href="?page=environment-create">Crear un entorno</a>
                </div>
            </section>
            <?php if ($homeError ?? ''): ?>
            <p class="home-notice" role="alert"><?= nuva_escape($homeError) ?></p>
            <?php endif; ?>
            <section class="home-section" aria-labelledby="home-summary-title">
                <div class="home-section__heading">
                    <h2 id="home-summary-title" class="section-title">Tu actividad</h2>
                    <a class="nuva-anchor" href="?page=profile">Ir a mi cuenta →</a>
                </div>
                <div class="stats__container">
                    <a class="stats__card nuva-card" href="?page=my-environments">
                        <span class="stats__qty"><?= count($myEnvironments) ?></span>
                        <span>Mis entornos</span>
                        <i data-lucide="arrow-up-right" aria-hidden="true">
                        </i>
                    </a>
                    <a class="stats__card nuva-card" href="?page=my-tournaments">
                        <span class="stats__qty"><?= count($myTournaments) ?></span>
                        <span>Mis torneos</span>
                        <i data-lucide="arrow-up-right" aria-hidden="true">
                        </i>
                    </a>
                    <a class="stats__card nuva-card" href="?page=explore">
                        <span class="stats__qty"><?= count($publicTournaments) ?></span>
                        <span>Torneos públicos</span>
                        <i data-lucide="arrow-up-right" aria-hidden="true">
                        </i>
                    </a>
                </div>
            </section>
            <section class="home-section modules" aria-labelledby="home-categories-title">
                <div class="home-section__heading">
                    <h2 id="home-categories-title" class="section-title">Explorar categorías</h2>
                    <a class="nuva-anchor" href="?page=explore">Ver todas →</a>
                </div>
                <div class="modules__list">
                    <?php foreach ($categoryLabels as $key => $label): ?>
                    <a class="modules__card nuva-card" href="?page=explore&amp;category=<?= rawurlencode($label) ?>">
                        <i class="modules__icon modules__icon--<?= nuva_escape($key) ?>" data-lucide="<?= nuva_escape($categoryIcons[$key]) ?>" aria-hidden="true">
                        </i>
                        <strong class="modules__type"><?= nuva_escape($label) ?></strong>
                        <span class="modules__count"><?= $categoryCounts[$key] ?> <?= $categoryCounts[$key] === 1 ? 'torneo público' : 'torneos públicos' ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="home-section" aria-labelledby="home-featured-title">
                <div class="home-section__heading">
                    <div>
                        <h2 id="home-featured-title" class="section-title">En juego y próximos</h2>
                        <p class="home-muted">Torneos públicos que puedes explorar ahora.</p>
                    </div>
                    <a class="nuva-anchor" href="?page=explore">Explorar competiciones →</a>
                </div>
                <?php if ($featured): ?>
                <div class="home-tournaments">
                    <?php foreach ($featured as $tournament): ?>
                    <a class="home-tournament nuva-card" href="?page=tournament&amp;id=<?= (int)$tournament['id'] ?>">
                        <span class="home-tournament__top">
                            <span class="nuva-badge"><?= nuva_escape($tournament['discipline']) ?></span>
                            <span><?= nuva_escape($tournament['status']) ?></span>
                        </span>
                        <strong><?= nuva_escape($tournament['name']) ?></strong>
                        <span class="home-muted"><?= nuva_escape($tournament['format_name']) ?> · <?= nuva_escape(nuva_local_date($tournament['start_date'], 'd/m/Y')) ?> – <?= nuva_escape(nuva_local_date($tournament['end_date'], 'd/m/Y')) ?></span>
                        <span class="home-tournament__open">Abrir torneo <i data-lucide="arrow-right" aria-hidden="true">
                            </i>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="home-empty nuva-card">
                    <p>Por ahora no hay torneos públicos en juego o próximos.</p>
                    <a class="nuva-anchor" href="?page=explore">Ver todos los torneos →</a>
                </div>
                <?php endif; ?>
            </section>
            <section class="home-section home-start nuva-card" aria-labelledby="home-start-title">
                <div>
                    <span class="home-eyebrow">PRIMEROS PASOS</span>
                    <h2 id="home-start-title" class="section-title">Organiza tu grupo</h2>
                    <p class="home-muted">Primero crea un entorno para tu club o grupo. Desde allí podrás invitar miembros y crear torneos.</p>
                </div>
                <div class="home-start__links">
                    <a class="nuva-btn" href="?page=environment-create">Crear entorno</a>
                    <a class="home-link" href="?page=guide#primeros-pasos">Leer la guía</a>
                </div>
            </section>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
        <nav class="tab-bar">
            <?php require __DIR__ . '/../../../Shared/Views/components/tabbar.php'; ?>
        </nav>
    </body>
</html>
