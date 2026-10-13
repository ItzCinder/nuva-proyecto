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
    case 'account':
        require_once dirname(__DIR__) . '/app/Services/Database.php';
        require_once dirname(__DIR__) . '/app/Models/Model.php';
        require_once dirname(__DIR__) . '/app/Models/User.php';
        require_once dirname(__DIR__) . '/app/Models/Role.php';
        require_once dirname(__DIR__) . '/app/Models/Workspace.php';
        require_once dirname(__DIR__) . '/app/Models/Tournament.php';
        require_once dirname(__DIR__) . '/app/Controllers/HomeController.php';
        (new \App\Controllers\HomeController())->handle($isAuthenticated(), $render);
        break;

    case 'guide':
        $render('public/guide');
        break;

    case 'login':
        $render('login');
        break;

    case 'loginauth':
        $render('login');
        break;
    case 'logout':
    case 'profile':
    case 'settings':
        require_once dirname(__DIR__) . '/app/Services/Database.php';
        require_once dirname(__DIR__) . '/app/Models/Model.php';
        require_once dirname(__DIR__) . '/app/Models/User.php';
        require_once dirname(__DIR__) . '/app/Models/Role.php';
        require_once dirname(__DIR__) . '/app/Models/Workspace.php';
        require_once dirname(__DIR__) . '/app/Models/Tournament.php';
        require_once dirname(__DIR__) . '/app/Controllers/AccountController.php';
        (new \App\Controllers\AccountController())->handle($page, $render);
        break;

    case 'my-environments':
    case 'my-tournaments':
        $personalCollection=$page==='my-environments'?'environments':'tournaments';
        $personalQuery=$_GET; $personalQuery['page']='profile'; $personalQuery['collection']=$personalCollection;
        header('Location: ?'.http_build_query($personalQuery).'#collections',true,303);
        break;

    case 'explore':
    case 'competitions':
    case 'competitions-all':
    case 'public-environment':
        require_once dirname(__DIR__).'/app/Services/Database.php';
        require_once dirname(__DIR__).'/app/Models/Model.php';
        require_once dirname(__DIR__).'/app/Models/Role.php';
        require_once dirname(__DIR__).'/app/Models/Workspace.php';
        require_once dirname(__DIR__).'/app/Models/Tournament.php';
        require_once dirname(__DIR__).'/app/Controllers/ExploreController.php';
        (new \App\Controllers\ExploreController())->handle($page,$render);
        break;

    case 'tournament-create':
    case 'tournament':
    case 'tournament-edit':
        require_once dirname(__DIR__) . '/app/Services/Database.php';
        require_once dirname(__DIR__) . '/app/Models/Model.php';
        require_once dirname(__DIR__) . '/app/Models/Role.php';
        require_once dirname(__DIR__) . '/app/Models/Workspace.php';
        require_once dirname(__DIR__) . '/app/Models/Tournament.php';
        require_once dirname(__DIR__) . '/app/Controllers/TournamentController.php';
        (new \App\Controllers\TournamentController())->handle($page, $render);
        break;

    case 'environment-create':
    case 'environment':
    case 'environment-invitation':
        require_once dirname(__DIR__) . '/app/Services/Database.php';
        require_once dirname(__DIR__) . '/app/Models/Model.php';
        require_once dirname(__DIR__) . '/app/Models/User.php';
        require_once dirname(__DIR__) . '/app/Models/Role.php';
        require_once dirname(__DIR__) . '/app/Models/Workspace.php';
        require_once dirname(__DIR__) . '/app/Models/Tournament.php';
        require_once dirname(__DIR__) . '/app/Controllers/WorkspaceController.php';
        $controller = new \App\Controllers\WorkspaceController();
        $controller->handle($page, $render);
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

    case 'google-photo-refresh':
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/app/Services/GoogleAuthService.php';
        require_once dirname(__DIR__) . '/app/Controllers/GoogleAuthController.php';
        (new \App\Controllers\GoogleAuthController())->refreshPhoto();
        break;

        /*
        Enrutamiento para loguearte con Google.
        */
    case 'google-login':
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/app/Services/GoogleAuthService.php';
        require_once dirname(__DIR__) . '/app/Controllers/GoogleAuthController.php';

        if ($isAuthenticated()) {
            header('Location: ?page=profile', true, 303);
            break;
        }
        (new \App\Controllers\GoogleAuthController())->login();
        break;


    default:
        http_response_code(404);
        echo '<a href="?page=login">Login</a>';
        break;
}
