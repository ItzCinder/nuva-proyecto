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
        <link rel="stylesheet" href="/public/modules/ConsultaPublica/css/competitions.css?v=5">
        <link rel="stylesheet" href="/public/shared/css/collection.css?v=4">
        <script src="/public/shared/js/layout.js?v=3" defer>
        </script>
        <title>Nuva - <?= htmlspecialchars($collectionTitle ?? 'Competiciones', ENT_QUOTES, 'UTF-8') ?></title>
    </head>
    <body>
        <!-- Header inyectado mediante fetch -->
        <header class="header">
            <?php require __DIR__ . '/../../../Shared/Views/components/header.php'; ?>
        </header>
        <main class="main">
            <?php
        $collectionType = $collectionType ?? 'competitions';
        $collectionPage = $collectionPage ?? 'competitions';
        require __DIR__ . '/../../../Shared/Views/components/collection.php';
        ?>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
        <!-- Navbar (tabbar) inyectado mediante fetch -->
        <nav class="tab-bar">
            <?php require __DIR__ . '/../../../Shared/Views/components/tabbar.php'; ?>
        </nav>
    </body>
</html>
