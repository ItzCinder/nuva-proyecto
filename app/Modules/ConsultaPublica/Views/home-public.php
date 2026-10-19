<?php
$published = $publicTournaments ?? [];
$workspaceCount = count(array_unique(array_column($published, 'workspace_id')));
$disciplineCount = count(array_unique(array_column($published, 'discipline')));
$categoryCounts = [
    'tradicional' => 0,
    'mental' => 0,
    'esports' => 0,
    'otros' => 0
];
foreach ($published as $tournament) {
    if (isset($categoryCounts[$tournament['discipline_category']])) {
        $categoryCounts[$tournament['discipline_category']]++;
    }
}
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
        <link rel="stylesheet" href="/public/modules/ConsultaPublica/css/home-public.css?v=6">
        <script src="/public/shared/js/layout.js?v=3" defer>
        </script>
        <title>Nuva - Inicio</title>
    </head>
    <body class="page-public">
        <a class="landing-skip" href="#contenido">Saltar al contenido</a>
        <header class="header">
            <?php require __DIR__ . '/../../../Shared/Views/components/header.php'; ?>
        </header>
        <main class="main" id="contenido">
            <section class="landing-hero" aria-labelledby="landing-title">
                <div class="landing-hero__layout">
                    <div class="landing-hero__content">
                        <span class="landing-hero__tag">Bienvenido a Nuva</span>
                        <h1 id="landing-title">Un lugar para organizar cada competición</h1>
                        <p class="landing-hero__text">Explora torneos públicos, fechas y participantes. Si tienes cuenta, crea un entorno y organiza los tuyos.</p>
                        <div class="landing-hero__links">
                            <a class="nuva-btn" href="?page=explore">Ver todos los torneos</a>
                            <a class="landing-text-link" href="?page=guide">Conocer cómo funciona Nuva →</a>
                        </div>
                    </div>
                    <div class="landing-search">
                        <span class="landing-search__eyebrow">
                            <i data-lucide="search" aria-hidden="true">
                            </i> Consulta pública</span>
                        <h2>Buscar un torneo</h2>
                        <p>Encuéntralo por nombre o disciplina.</p>
                        <form method="get" action="/public/index.php">
                            <input type="hidden" name="page" value="competitions-all">
                            <label for="landing-tournament-search">Nombre o disciplina</label>
                            <input id="landing-tournament-search" type="search" name="q" placeholder="Ej. ajedrez" autocomplete="off" required>
                            <button class="nuva-btn" type="submit">Buscar <i data-lucide="arrow-right" aria-hidden="true">
                                </i>
                            </button>
                        </form>
                        <small>La ficha muestra fechas y participantes. Los resultados aparecerán cuando se registren.</small>
                    </div>
                </div>
            </section>
            <?php if ($homeError ?? ''): ?>
            <p class="landing-error" role="alert"><?= htmlspecialchars($homeError, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <section class="landing-section--features" aria-labelledby="landing-features-title">
                <h2 id="landing-features-title" class="landing-section__title">Empieza por donde quieras</h2>
                <div class="landing-section__grid">
                    <a class="feature-card" href="?page=explore">
                        <span class="feature-card__icon">
                            <i data-lucide="trophy" aria-hidden="true">
                            </i>
                        </span>
                        <h3 class="feature-card__title">Explorar torneos</h3>
                        <p class="feature-card__text">Descubre competiciones públicas, sus disciplinas, formatos, participantes y fechas.</p>
                        <span class="landing-card-link">Ver competiciones →</span>
                    </a>
                    <a class="feature-card" href="?page=environment-create">
                        <span class="feature-card__icon">
                            <i data-lucide="folders" aria-hidden="true">
                            </i>
                        </span>
                        <h3 class="feature-card__title">Crear un entorno</h3>
                        <p class="feature-card__text">Abre un espacio para tus miembros, roles, invitaciones y torneos.</p>
                        <span class="landing-card-link">Crear entorno →</span>
                    </a>
                    <a class="feature-card" href="?page=guide">
                        <span class="feature-card__icon">
                            <i data-lucide="book-open" aria-hidden="true">
                            </i>
                        </span>
                        <h3 class="feature-card__title">Aprender a usar Nuva</h3>
                        <p class="feature-card__text">Encuentra primeros pasos, roles, formatos y ayuda para cargar e interpretar estadísticas.</p>
                        <span class="landing-card-link">Leer la guía →</span>
                    </a>
                </div>
            </section>
            <section class="landing-section--modules" aria-labelledby="landing-categories-title">
                <h2 id="landing-categories-title" class="landing-section__title">Explora por categoría</h2>
                <div class="landing-section__container">
                    <?php foreach ([
                        ['Deporte tradicional', 'tradicional', 'Fútbol, tenis, pádel y más.'],
                        ['Deporte mental', 'mental', 'Ajedrez, damas y Go.'],
                        ['eSports', 'esports', 'Competiciones de videojuegos.'],
                        ['Otros', 'otros', 'Disciplinas creadas por la comunidad.'],
                    ] as [$label, $key, $description]): ?>
                    <a class="module-card" href="?page=explore&amp;category=<?= rawurlencode($label) ?>">
                        <div class="module-card__body">
                            <h3 class="module-card__title"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="module-card__description"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <span class="landing-card-link"><?= $categoryCounts[$key] ?> <?= $categoryCounts[$key] === 1 ? 'torneo público' : 'torneos públicos' ?> →</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="landing-stats" aria-label="Actividad pública en Nuva">
                <article class="stats__card nuva-card">
                    <strong class="stats__qty"><?= count($published) ?></strong>
                    <span>Torneos públicos</span>
                </article>
                <article class="stats__card nuva-card">
                    <strong class="stats__qty"><?= $workspaceCount ?></strong>
                    <span>Entornos con torneos públicos</span>
                </article>
                <article class="stats__card nuva-card">
                    <strong class="stats__qty"><?= $disciplineCount ?></strong>
                    <span>Disciplinas publicadas</span>
                </article>
            </section>
            <section class="landing-cta-box">
                <h2>¿Quieres organizar tu comunidad?</h2>
                <p>Crea un entorno, invita a las personas adecuadas y configura tu primer torneo con el formato que necesites.</p>
                <a href="?page=google-login">Entrar con Google</a>
            </section>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
    </body>
</html>
