<?php
use App\Modules\Estadisticas\Services\Physics;

$rules = [
    'latest' => 'El valor más reciente',
    'sum' => 'Sumar los valores',
    'average' => 'Calcular el promedio',
    'min' => 'Mostrar el menor',
    'max' => 'Mostrar el mayor'
];
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <script src="/public/shared/js/numeric-inputs.js?v=1" defer></script>
        <title>Nuva - Estadísticas</title>
        <script src="https://unpkg.com/lucide@latest">
        </script>
        <script src="/public/shared/js/layout.js?v=3" defer>
        </script>
        <script src="/public/modules/Estadisticas/js/statistics.js?v=8" defer>
        </script>
        <?php foreach (['bootstrap.min.css','global.css?v=5','components/header.css?v=4','components/tabbar.css?v=4','environments.css?v=6','tournaments.css?v=4','statistics.css?v=7'] as $css): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars(nuva_asset('/public/css/' . $css), ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    </head>
    <body>
        <header class="header">
            <?php require nuva_view_path('components/header'); ?>
        </header>
        <main class="main">
            <div class="statistics-layout">
                <?php if ($flash): ?>
                <p class="environment-notice" role="status"><?= nuva_escape($flash) ?></p>
                <?php endif; ?>
                <?php if ($error): ?>
                <p class="environment-notice environment-notice--error" role="alert"><?= nuva_escape($error) ?></p>
                <?php endif; ?>
                <a class="nuva-back nuva-anchor" href="?page=<?= $environmentMode ? 'environment' : 'tournament' ?>&amp;id=<?= (int)($environmentMode ? $workspace['id'] : $tournament['id']) ?>"><?= nuva_escape($environmentMode ? $workspace['name'] : $tournament['name']) ?></a>
                <header class="tournament-heading">
                    <p class="environment-eyebrow"><?= $environmentMode ? 'CATÁLOGO DEL ENTORNO' : 'ESTADÍSTICAS DEL TORNEO' ?></p>
                    <h1><?= $environmentMode ? 'Estadísticas y plantillas' : 'Conoce cada prueba' ?></h1>
                    <p class="environment-muted"><?= $environmentMode ? 'Habilita las plantillas y crea estadísticas para tu comunidad. Cada torneo elige cuáles utilizar.' : 'Registra medidas y consulta sus resultados. No modifican la clasificación deportiva.' ?></p>
                    <a class="nuva-anchor" href="?page=guide#estadisticas" target="_blank" rel="noopener" aria-label="Guía de estadísticas (abre otra pestaña)">Guía de estadísticas ↗</a>
                </header>
                <?php if (!$environmentMode): ?>
                <nav class="environment-tabs" aria-label="Secciones del torneo">
                    <?php foreach ([
                        'overview' => 'Resumen',
                        'participants' => 'Participantes',
                        'matches' => 'Encuentros',
                        'calendar' => 'Calendario',
                        'standings' => 'Clasificación',
                        'results' => 'Resultados'
                    ] as $key => $label): ?>
                    <a href="?page=tournament&amp;id=<?= (int)$tournament['id'] ?>&amp;section=<?= $key ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                    <a class="is-active" aria-current="page" href="?page=tournament-statistics&amp;id=<?= (int)$tournament['id'] ?>">Estadísticas</a>
                </nav>
                <?php endif; ?>
                <?php if ($environmentMode): require __DIR__ . '/environment.php';
                    require __DIR__ . '/templates.php';
                else: require __DIR__ . '/tournament.php'; endif; ?>
            </div>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
        <nav class="tab-bar">
            <?php require nuva_view_path('components/tabbar'); ?>
        </nav>
    </body>
</html>
