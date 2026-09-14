<?php

declare(strict_types=1);
$sessionLifetime = 60 * 60 * 24 * 30; // 30 Dias

ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
/*
    Establecer cookies de sesion
*/
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    /*
        La condicion HTTPS sirve para evitar que la cookie viaje por conexiones no seguras (HTTP)
    */
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$env = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW);

if ($env === false) {
    throw new RuntimeException('No se pudo cargar el archivo .env');
}

foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
}

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
        /*
        Si no esta autenticado
        */
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
        /*
        Eliminar cookies
        */
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'] ?? '',
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);

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
        if (!$isAuthenticated()) {
            $render('login');
            break;
        }
        $render('account/profile');
        break;

        /*
        Para pruebas, recibir la llamada de Google Auth.
        */
    case 'google-callback':
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/app/Services/GoogleAuthService.php';

        if (empty($_GET['error'])) {
            $state = $_GET['state'] ?? null;
            $sessionState = $_SESSION['oauth2state'] ?? null;

            if (!is_string($state) || !is_string($sessionState) || !hash_equals($sessionState, $state)) {
                unset($_SESSION['oauth2state']);
                http_response_code(400);
                echo 'Estado OAuth inválido';
                exit;
            }
        }

        if (!empty($_GET['error'])) {
            unset($_SESSION['oauth2state']);
            header('Location: ?page=login');
            exit;
        }

        if (empty($_GET['code']) || !is_string($_GET['code'])) {
            http_response_code(400);
            echo 'Código de autorización no encontrado';
            exit;
        }

        $service = new \App\Services\GoogleAuthService();
        $userInfo = $service->getUserInfoFromCode($_GET['code']);

        if (empty($userInfo['google_id']) || empty($userInfo['email'])) {
            http_response_code(502);
            echo 'Google no devolvió los datos necesarios del usuario';
            exit;
        }

        unset($_SESSION['oauth2state']);
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['user'] = $userInfo;

        header('Location: ?page=index');
        exit;

        /*
        Enrutamiento para loguearte con Google.
        */
    case 'google-login':
        if (!$isAuthenticated()) {
            require_once dirname(__DIR__) . '/vendor/autoload.php';
            require_once dirname(__DIR__) . '/app/Services/GoogleAuthService.php';

            $service = new \App\Services\GoogleAuthService();

            header('Location: ' . $service->getAuthUrl());
            exit;  
        }
        $render('account/profile');
        break;
        

    default:
        http_response_code(404);
        echo '<a href="?page=login">Login</a>';
        break;
}
