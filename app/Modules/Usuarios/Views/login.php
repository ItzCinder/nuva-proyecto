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
        <script src="/public/shared/js/layout.js?v=3" defer>
        </script>
        <link rel="stylesheet" href="/public/css/global.css?v=5">
        <link rel="stylesheet" href="/public/shared/css/header.css?v=4">
        <link rel="stylesheet" href="/public/shared/css/tabbar.css?v=4">
        <link rel="stylesheet" href="/public/modules/ConsultaPublica/css/account.css?v=3">
        <title>Nuva - Iniciar sesión</title>
    </head>
    <body>
        <header class="header">
            <?php require __DIR__ . '/../../../Shared/Views/components/header.php'; ?>
        </header>
        <main class="main d-flex flex-column gap-4">
            <?php if (!empty($_SESSION['login_error'])): ?>
            <p class="nuva-card p-4" role="alert"><?= htmlspecialchars($_SESSION['login_error'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>
            <div class="login__container">
                <div class="login__logo-container">
                    <img class="login__logo-nuva" src="/public/assets/logo/icon-main.svg" alt="Nuva">
                    <img class="login__logo-nuva__wordmark" src="/public/assets/logo/wordmark-main-on-light.svg" alt="Nuva">
                </div>
                <span>Inicia sesión para gestionar tu perfil y competiciones.</span>
                <?php if (!empty($_SESSION['pending_invitation'])): ?>
                <p>Después de iniciar sesión podrás revisar y aceptar la invitación al entorno.</p>
                <?php endif; ?>
            </div>
            <a class="login__google-btn" href="/public/index.php?page=google-login">
                <img class="login__google-icon" src="/public/assets/logo/google_logo.svg" alt="">
                <span>Continuar con Google</span>
            </a>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
        <nav class="tab-bar">
            <?php require __DIR__ . '/../../../Shared/Views/components/tabbar.php'; ?>
        </nav>
    </body>
</html>
