<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="/public/css/bootstrap.min.css" rel="stylesheet">
    <script src="/public/js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="/public/css/global.css?v=2">
    <link rel="stylesheet" href="/public/css/components/header.css?v=2">
    <link rel="stylesheet" href="/public/css/components/tabbar.css?v=2">
    <link rel="stylesheet" href="/public/css/public/competitions.css?v=2">
    <link rel="stylesheet" href="/public/css/components/collection.css">
    <script src="/public/js/layout.js" defer></script>
    <title>Nuva - <?= htmlspecialchars($collectionTitle ?? 'Competiciones', ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <!-- Header inyectado mediante fetch -->
    <header class="header">
        <?php require __DIR__ . '/../components/header.php'; ?>
    </header>
    <main class="main">
        <?php
        $collectionType = $collectionType ?? 'competitions';
        $collectionPage = $collectionPage ?? 'competitions';
        require __DIR__ . '/../components/collection.php';
        ?>
    </main>

    <!-- Navbar (tabbar) inyectado mediante fetch -->
    <nav class="tab-bar">
        <?php require __DIR__ . '/../components/tabbar.php'; ?>
    </nav>
</body>
</html>