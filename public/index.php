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

/*
    Cargar y mostrar vista PHP.
    Permite cargar datos.
*/
$render = static function (
    string $view,
    array $data = []
) use ($viewsPath): void {
    $file = $viewsPath . '/' . ltrim($view, '/') . '.php';

    if (!is_file($file)) {
        http_response_code(404);
        echo 'View not found';
        return;
    }

    extract($data, EXTR_SKIP);
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
        require_once dirname(__DIR__) . '/app/Services/Database.php';
        require_once dirname(__DIR__) . '/app/Models/Model.php';
        require_once dirname(__DIR__) . '/app/Models/User.php';

        $userId = $_SESSION['user_id'] ?? null;

        /*
        Verificar si existe un usuario valido en la sesión.
        */
         if (!is_int($userId) && !ctype_digit((string) $userId)) {
            $_SESSION = [];
            session_destroy();
            header('Location: ?page=login');
            exit;
        }

        $user = \app\Models\User::findById((int) $userId);

        if ($user === null) {
            $_SESSION = [];
            session_destroy();
            header('Location: ?page=login');
            exit;
        }
        $render('account/profile', [
            'user' => $user,
        ]);
        break;

    case 'google-callback':
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/app/Services/Database.php';
        require_once dirname(__DIR__) . '/app/Services/GoogleAuthService.php';
        require_once dirname(__DIR__) . '/app/Services/UserService.php';
        require_once dirname(__DIR__) . '/app/Models/Model.php';
        require_once dirname(__DIR__) . '/app/Models/User.php';
        require_once dirname(__DIR__) . '/app/Controllers/GoogleAuthController.php';

        $controller = new \App\Controllers\GoogleAuthController();
        $controller->callback();
        break;

        /*
        Enrutamiento para loguearte con Google.
        */
    case 'google-login':
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/app/Services/GoogleAuthService.php';
        require_once dirname(__DIR__) . '/app/Controllers/GoogleAuthController.php';

        if (!$isAuthenticated()) {
            $controller = new \App\Controllers\GoogleAuthController();
            $controller->login();
        }

        $render('account/profile');
        break;
        

    default:
        http_response_code(404);
        echo '<a href="?page=login">Login</a>';
        break;
}
