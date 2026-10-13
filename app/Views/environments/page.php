<?php
use app\Models\Role;
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$input = static fn (string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
$localDate = static function ($value): string {
    if (!$value) {
        return 'Sin definir';
    }
    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('America/Montevideo'))->format('d/m/Y H:i');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="same-origin">
    <title>Nuva - <?= $escape($title) ?></title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="/public/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/css/global.css?v=3" rel="stylesheet">
    <link href="/public/css/components/header.css?v=4" rel="stylesheet">
    <link href="/public/css/components/tabbar.css?v=4" rel="stylesheet">
    <link href="/public/css/components/collection.css?v=3" rel="stylesheet">
    <link href="/public/css/environments.css?v=3" rel="stylesheet">
    <script src="/public/js/layout.js" defer></script>
</head>
<body>
<header class="header"><?php require __DIR__ . '/../components/header.php'; ?></header>
<main class="main">
    <div class="environments">
        <?php if ($error !== ''): ?><p class="environment-notice environment-notice--error" role="alert"><?= $escape($error) ?></p><?php endif; ?>
        <?php if ($flash !== ''): ?><p class="environment-notice" role="status"><?= $escape($flash) ?></p><?php endif; ?>
        <?php if ($screen === 'error'): ?>
            <a class="nuva-anchor" href="?page=my-environments">Volver a mis entornos</a>
        <?php else: ?>
            <?php require __DIR__ . '/' . $screen . '.php'; ?>
        <?php endif; ?>
    </div>
</main>
<nav class="tab-bar"><?php require __DIR__ . '/../components/tabbar.php'; ?></nav>
</body>
</html>
