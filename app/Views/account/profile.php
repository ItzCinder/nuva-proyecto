<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="/public/css/bootstrap.min.css" rel="stylesheet">
    <script src="/public/js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="/public/css/global.css?v=3">
    <link rel="stylesheet" href="/public/css/components/header.css?v=4">
    <link rel="stylesheet" href="/public/css/components/tabbar.css?v=4">
    <link rel="stylesheet" href="/public/css/account/profile.css?v=5">
    <link rel="stylesheet" href="/public/css/public/competitions.css?v=4">
    <link rel="stylesheet" href="/public/css/components/collection.css?v=3">
    <link rel="stylesheet" href="/public/css/environments.css?v=3">
    <script src="/public/js/layout.js" defer></script>
    <title>Nuva - Cuenta</title>
</head>
<body>
    <!-- Header inyectado mediante-->
    <header class="header">
        <?php require __DIR__ . '/../components/header.php'; ?>
    </header>
    
    <main class="main">
        <div class="profile">
            <div class="profile__avatar--container">
                <?php
                $pictureUrl = $user->getPictureUrl();

                if (!is_string($pictureUrl) || $pictureUrl === '') {
                    $pictureUrl = '/public/assets/logo/icon-main.svg';
                }
                ?>
                <img class="profile__avatar" src="<?= htmlspecialchars($pictureUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Foto de perfil" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='/public/assets/logo/icon-main.svg'"/>
            </div>
            <div class="profile__info">
                <h1 class="profile__name">
                    <?= htmlspecialchars($user->getName(), ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <a class="profile__email anchor-not-style" href="mailto:<?= htmlspecialchars($user->getEmail(), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($user->getEmail(), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
            <data class="profile__id nuva-badge" value="<?= (int) $user->getId() ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" width="14" height="14"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>
                ID · #<?= str_pad((string) $user->getId(), 6, '0', STR_PAD_LEFT) ?>
            </data>

        </div>
        <div class="nuva-separator"></div>
        <div id="collections" class="profile__collections">
            <?php
            $collectionType = ($_GET['collection'] ?? '') === 'environments' ? 'environments' : 'tournaments';
            $collectionPage = 'profile';
            require __DIR__ . '/../components/collection-tabs.php';
            require __DIR__ . '/../components/collection.php';
            ?>
        </div>
    </main>

    <!-- Navbar (tabbar) inyectado mediante -->
    <nav class="tab-bar">
        <?php require __DIR__ . '/../components/tabbar.php'; ?>
    </nav>
</body>
</html>
