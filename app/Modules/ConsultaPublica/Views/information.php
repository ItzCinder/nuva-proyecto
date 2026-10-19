<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nuva - <?= nuva_escape($title) ?></title>
        <link rel="stylesheet" href="/public/css/bootstrap.min.css">
        <link rel="stylesheet" href="/public/css/global.css?v=5">
        <link rel="stylesheet" href="/public/shared/css/header.css?v=4">
        <link rel="stylesheet" href="/public/shared/css/tabbar.css?v=4">
        <link rel="stylesheet" href="/public/modules/ConsultaPublica/css/information.css?v=1">
        <script src="https://unpkg.com/lucide@latest"></script>
        <script src="/public/shared/js/layout.js?v=3" defer></script>
    </head>
    <body>
        <header class="header">
            <?php require nuva_view_path('components/header'); ?>
        </header>
        <main class="main information-main">
            <div class="information-content">
                <a class="nuva-anchor nuva-back" href="?page=index">Volver al inicio</a>
                <nav class="information-nav" aria-label="Información de Nuva">
                    <a href="?page=about" <?= $informationPage === 'about' ? 'aria-current="page"' : '' ?>>Sobre nosotros</a>
                    <a href="?page=terms" <?= $informationPage === 'terms' ? 'aria-current="page"' : '' ?>>Condiciones de uso</a>
                    <a href="?page=copyright" <?= $informationPage === 'copyright' ? 'aria-current="page"' : '' ?>>Copyright</a>
                </nav>
                <?php require $contentFile; ?>
            </div>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
        <nav class="tab-bar">
            <?php require nuva_view_path('components/tabbar'); ?>
        </nav>
    </body>
</html>
