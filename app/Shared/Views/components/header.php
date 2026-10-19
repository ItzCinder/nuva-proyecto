<?php
$publicHeader = empty($_SESSION['authenticated']);
$headerPage = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'index';
?>
<a class="logo__container" href="?page=index" aria-label="Nuva, ir al inicio">
    <img class="logo__img" src="/public/assets/logo/nuva-main.svg" alt="Nuva">
</a>
<?php if ($publicHeader): ?>
<nav class="header__public-nav" aria-label="Navegación pública">
    <a href="?page=explore" <?= in_array($headerPage, ['competitions', 'competitions-all', 'tournament'], true) ? 'aria-current="page"' : '' ?>>Explorar Nuva</a>
    <a href="?page=guide" <?= $headerPage === 'guide' ? 'aria-current="page"' : '' ?>>Cómo funciona</a>
    <a href="?page=about" <?= $headerPage === 'about' ? 'aria-current="page"' : '' ?>>Sobre nosotros</a>
</nav>
<?php if ($headerPage !== 'login' && $headerPage !== 'loginauth'): ?>
<div class="header__actions">
    <a class="header__login" href="?page=login">
        <i data-lucide="log-in" aria-hidden="true">
        </i>Iniciar sesión</a>
</div>
<?php endif; ?>
<?php endif; ?>
