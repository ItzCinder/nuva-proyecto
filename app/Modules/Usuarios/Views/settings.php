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
        <link rel="stylesheet" href="/public/modules/Usuarios/css/profile.css?v=6">
        <script src="/public/shared/js/layout.js?v=3" defer>
        </script>
        <title>Nuva - Ajustes</title>
    </head>
    <body>
        <header class="header">
            <?php require __DIR__ . '/../../../Shared/Views/components/header.php'; ?>
        </header>
        <main class="main settings-page">
            <a class="nuva-anchor settings-page__back nuva-back" href="?page=profile">Volver a mi cuenta</a>
            <section id="settings" class="profile-settings profile-settings--page nuva-card" aria-labelledby="settings-title">
                <div class="profile-settings__heading">
                    <div>
                        <span class="profile-settings__eyebrow">TU CUENTA</span>
                        <h1 id="settings-title" class="section-title">Ajustes</h1>
                        <p>Elige cómo se muestra tu perfil en Nuva.</p>
                    </div>
                    <i data-lucide="settings-2" aria-hidden="true">
                    </i>
                </div>
                <?php if (!empty($profileFlash)): ?>
                <p class="profile-settings__notice profile-settings__notice--success" role="status"><?= htmlspecialchars($profileFlash, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if (!empty($profileError)): ?>
                <p class="profile-settings__notice profile-settings__notice--error" role="alert"><?= htmlspecialchars($profileError, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <div class="profile-settings__grid">
                    <div class="profile-settings__panel">
                        <h2>Nombre visible</h2>
                        <p>Este nombre se muestra en tus entornos y torneos. No modifica tu cuenta de Google.</p>
                        <form method="post" action="?page=settings" class="profile-settings__form">
                            <input type="hidden" name="action" value="name">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <label for="profile-name">Tu nombre</label>
                            <input id="profile-name" name="name" type="text" minlength="2" maxlength="80" required autocomplete="name" value="<?= htmlspecialchars($nameValue ?? $user->getName(), ENT_QUOTES, 'UTF-8') ?>">
                            <button class="nuva-btn" type="submit">Guardar nombre</button>
                        </form>
                    </div>
                    <div class="profile-settings__panel">
                        <h2>Foto de Google</h2>
                        <p><?= $user->getPictureUrl() ? 'Usamos tu foto de Google. Si la cambiaste, pulsa el botón para actualizarla aquí.' : 'Todavía no se cargó tu foto de Google. Puedes intentar actualizarla.' ?></p>
                        <form method="post" action="?page=google-photo-refresh" class="profile-settings__form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <button class="profile-settings__secondary" type="submit">
                                <i data-lucide="refresh-cw" aria-hidden="true">
                                </i> Actualizar foto de Google</button>
                        </form>
                    </div>
                </div>
                <div class="profile-settings__footer">
                    <div>
                        <h2>Cerrar sesión</h2>
                        <p>Sal de tu cuenta en este navegador.</p>
                    </div>
                    <form method="post" action="?page=logout">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <button class="profile-settings__logout" type="submit">
                            <i data-lucide="log-out" aria-hidden="true">
                            </i> Cerrar sesión</button>
                    </form>
                </div>
            </section>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
        <nav class="tab-bar">
            <?php require __DIR__ . '/../../../Shared/Views/components/tabbar.php'; ?>
        </nav>
    </body>
</html>
