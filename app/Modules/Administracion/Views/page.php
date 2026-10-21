<?php
use app\Models\Role;

?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="referrer" content="same-origin">
        <title>Nuva - <?= nuva_escape($title) ?></title>
        <script src="https://unpkg.com/lucide@latest">
        </script>
        <link href="/public/css/bootstrap.min.css" rel="stylesheet">
        <link href="/public/css/global.css?v=5" rel="stylesheet">
        <link href="/public/shared/css/header.css?v=4" rel="stylesheet">
        <link href="/public/shared/css/tabbar.css?v=4" rel="stylesheet">
        <link href="/public/shared/css/collection.css?v=4" rel="stylesheet">
        <link href="/public/modules/ConsultaPublica/css/competitions.css?v=6" rel="stylesheet">
        <link href="/public/modules/Administracion/css/environments.css?v=7" rel="stylesheet">
        <script src="/public/shared/js/layout.js?v=3" defer>
        </script>
    </head>
    <body>
        <header class="header">
            <?php require __DIR__ . '/../../../Shared/Views/components/header.php'; ?>
        </header>
        <main class="main">
            <div class="environments">
                <?php if ($error !== ''): ?>
                <p class="environment-notice environment-notice--error" role="alert"><?= nuva_escape($error) ?></p>
                <?php endif; ?>
                <?php if ($flash !== ''): ?>
                <p class="environment-notice" role="status"><?= nuva_escape($flash) ?></p>
                <?php endif; ?>
                <?php if ($screen === 'error'): ?>
                <a class="nuva-anchor" href="?page=my-environments">Volver a mis entornos</a>
                <?php else: ?>
                <?php require __DIR__ . '/' . $screen . '.php'; ?>
                <?php endif; ?>
            </div>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
        <nav class="tab-bar">
            <?php require __DIR__ . '/../../../Shared/Views/components/tabbar.php'; ?>
        </nav>
    </body>
</html>
