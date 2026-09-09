<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="/public/css/bootstrap.min.css" rel="stylesheet">
    <script src="/public/js/bootstrap.bundle.min.js" defer></script>
    <script src="/public/js/layout.js" defer></script>
    <link rel="stylesheet" href="/public/css/global.css">
    <link rel="stylesheet" href="/public/css/components/header.css">
    <link rel="stylesheet" href="/public/css/components/tabbar.css">
    <link rel="stylesheet" href="/public/css/account.css">
    
    <title>Nuva - Iniciar sesión</title>
</head>
<body>
    <header class="header">
        <?php require __DIR__ . '/components/header.php'; ?>
    </header>

    <main class="main">
        <div class="login__container">
            <div class="login__logo-container">
                <img class="login__logo-nuva" src="/public/assets/logo/icon-main.svg" alt="Nuva">
                <img class="login__logo-nuva__wordmark" src="/public/assets/logo/wordmark-main-on-light.svg" alt="Nuva">
            </div>
            <span>Inicia sesión para gestionar tu perfil y competiciones.</span>
            <img class="login__google-test" src="/public/assets/test.png" alt="">
        </div>
        <a href="/public/index.php?page=google-login">
            Continuar con Google
        </a>
    </main>

    <?php require __DIR__ . '/components/tabbar.php'; ?>
</body>
</html>