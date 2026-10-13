<?php
use app\Models\Tournament;
use app\Models\Role;
$escape = static fn ($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$local = static function ($value, $format='d/m/Y H:i'): string {
    if (!$value || str_starts_with((string)$value,'0000-00-00')) return 'Sin definir';
    return (new DateTimeImmutable($value,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Montevideo'))->format($format);
};
$input = static fn ($key, $default='') => is_string($_POST[$key] ?? null) ? $_POST[$key] : $default;
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nuva - <?= $escape($title) ?></title>
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="/public/css/bootstrap.min.css">
<link rel="stylesheet" href="/public/css/global.css?v=3"><link rel="stylesheet" href="/public/css/components/header.css?v=4"><link rel="stylesheet" href="/public/css/components/tabbar.css?v=4">
<link rel="stylesheet" href="/public/css/environments.css?v=3"><link rel="stylesheet" href="/public/css/tournaments.css?v=3">
<script src="/public/js/layout.js" defer></script><script src="/public/js/tournaments.js?v=4" defer></script></head>
<body><header class="header"><?php require __DIR__ . '/../components/header.php'; ?></header>
<main class="main"><div class="tournament-layout">
<?php if ($error): ?><p class="environment-notice environment-notice--error" role="alert"><?= $escape($error) ?></p><?php endif; ?>
<?php if ($flash): ?><p class="environment-notice" role="status"><?= $escape($flash) ?></p><?php endif; ?>
<?php if ($mode==='error'): ?><a class="nuva-anchor" href="?page=competitions">Ver competiciones</a>
<?php elseif ($mode==='detail'): require __DIR__ . '/detail.php';
else: require __DIR__ . '/form.php'; endif; ?>
</div></main><nav class="tab-bar"><?php require __DIR__ . '/../components/tabbar.php'; ?></nav></body></html>
