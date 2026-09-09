<?php

declare(strict_types=1);

session_start();

$viewsPath = dirname(__DIR__) . '/app/Views';

$render = static function (string $view) use ($viewsPath): void {
    $file = $viewsPath . '/' . ltrim($view, '/') . '.php';

    if (!is_file($file)) {
        http_response_code(404);
        echo 'View not found';
        return;
    }

    require $file;
};

/*
Para verificar si el usuario esta aunteticado.
Si no esta aunteticado lo manda al home-public.
*/
$isAuthenticated = static function (): bool {
    return !empty($_SESSION['authenticated']);
};

$page = $_GET['page'] ?? 'index';
$page = is_string($page) ? $page : 'index';

switch ($page) {
    case 'index':
        if (!$isAuthenticated()) {
            $render('public/home-public');
            break;
        }
        $render('account/home');
        break;

    case 'login':
        $render('login');
        break;

    case 'loginauth':
        $render('login');
        break;

    case 'logout':
        $_SESSION = [];
        session_destroy();
        header('Location: ?page=login');
        exit;

    case 'competitions':
        $render('public/competitions');
        break;

    case 'account':
        $render('account/home');
        break;

    case 'profile':
        $render('account/profile');
        break;

    default:
        http_response_code(404);
        echo '<a href="?page=login">Login</a>';
        break;
}