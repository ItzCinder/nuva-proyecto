<?php
use app\Models\Tournament;
use app\Models\Role;

?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <script src="/public/shared/js/numeric-inputs.js?v=1" defer></script>
        <title>Nuva - <?= nuva_escape($title) ?></title>
        <script src="https://unpkg.com/lucide@latest">
        </script>
        <link rel="stylesheet" href="/public/css/bootstrap.min.css">
        <link rel="stylesheet" href="/public/css/global.css?v=5">
        <link rel="stylesheet" href="/public/shared/css/header.css?v=4">
        <link rel="stylesheet" href="/public/shared/css/tabbar.css?v=4">
        <link rel="stylesheet" href="/public/modules/Administracion/css/environments.css?v=7">
        <link rel="stylesheet" href="/public/modules/Torneos/css/tournaments.css?v=4">
        <script src="/public/shared/js/layout.js?v=3" defer>
        </script>
        <script src="/public/modules/Torneos/js/tournaments.js?v=6" defer>
        </script>
        <script src="/public/modules/Calendarios/js/calendar.js?v=2" defer>
        </script>
        <link rel="stylesheet" href="/public/modules/Resultados/css/results.css?v=1">
        <link rel="stylesheet" href="/public/modules/Calendarios/css/calendar.css?v=1">
    </head>
    <body>
        <header class="header">
            <?php require __DIR__ . '/../../../Shared/Views/components/header.php'; ?>
        </header>
        <main class="main">
            <div class="tournament-layout">
                <?php if ($error): ?>
                <p class="environment-notice environment-notice--error" role="alert"><?= nuva_escape($error) ?></p>
                <?php endif; ?>
                <?php if ($flash): ?>
                <p class="environment-notice" role="status"><?= nuva_escape($flash) ?></p>
                <?php endif; ?>
                <?php if ($mode === 'error'): ?>
                <a class="nuva-anchor" href="?page=competitions">Ver competiciones</a>
                <?php elseif ($mode === 'detail'): require __DIR__ . '/detail.php';
                else: require __DIR__ . '/form.php'; endif; ?>
            </div>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
        <nav class="tab-bar">
            <?php require __DIR__ . '/../../../Shared/Views/components/tabbar.php'; ?>
        </nav>
    </body>
</html>
