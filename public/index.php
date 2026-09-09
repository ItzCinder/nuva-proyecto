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
    /*
    Borra las creedenciales de sesión.
    Despues lo vuelve a mandar para iniciar sesión.
    */
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

    case 'google-callback':
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/app/Services/GoogleAuthService.php';

        $service = new \App\Services\GoogleAuthService();

        if (empty($_GET['code']) || !is_string($_GET['code'])) {
            http_response_code(400);
            echo 'Código de autorización no encontrado';
            exit;
        }

        $userInfo = $service->getUserInfoFromCode($_GET['code']);

        echo '<pre>';
        print_r($userInfo);
        echo '</pre>';
        exit;

    case 'google-login':
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/app/Services/GoogleAuthService.php';

        $service = new \App\Services\GoogleAuthService();

        header('Location: ' . $service->getAuthUrl());
        exit;

    default:
        http_response_code(404);
        echo '<a href="?page=login">Login</a>';
        break;
}

$env = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW);

if ($env === false) {
    throw new RuntimeException('No se pudo cargar el archivo .env');
}

foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
}
