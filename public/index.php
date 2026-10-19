<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
nuva_load_environment();
nuva_security_headers();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, POST, HEAD');
    exit('Este método no está permitido.');
}
$photoForm = in_array($_GET['page'] ?? '', ['environment-create', 'environment'], true)
    && str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
$maximumBody = $photoForm ? 3145728 : 65536;
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $maximumBody) {
    nuva_security_event('request_too_large');
    http_response_code(413);
    exit($photoForm ? 'La foto y el formulario son demasiado grandes. Usa una imagen de hasta 2 MB.' : 'El formulario es demasiado grande. Reduce los detalles.');
}
$requestError = nuva_request_error($_GET, $_POST);
if ($requestError !== '') {
    nuva_security_event('invalid_request');
    http_response_code(400);
    exit($requestError);
}

// La cookie no puede leerse desde JavaScript y solo viaja por HTTPS cuando está habilitado.
$sessionLifetime = 60 * 60 * 24 * 30;
$secureCookie = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (($_ENV['SESSION_SECURE_COOKIE'] ?? '') === '1') {
    $secureCookie = true;
}
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
$authenticated = !empty($_SESSION['authenticated']);

// Cada caso indica qué controlador atiende la página solicitada.
$page = $_GET['page'] ?? 'index';
$page = is_string($page) ? $page : 'index';

switch ($page) {
    case 'index':
    case 'account':
        $controller = new \App\Controllers\HomeController();
        $controller->handle($authenticated, 'nuva_render_view');
        break;

    case 'environment-statistics':
    case 'tournament-statistics':
        $controller = new \App\Modules\Estadisticas\Controllers\StatisticsController();
        $controller->handle($page, 'nuva_render_view');
        break;

    case 'guide':
        nuva_render_view('public/guide');
        break;

    case 'about':
    case 'terms':
    case 'copyright':
        $controller = new \App\Modules\ConsultaPublica\Controllers\InformationController();
        $controller->handle($page, 'nuva_render_view');
        break;

    case 'login':
        nuva_render_view('login');
        break;

    case 'loginauth':
        nuva_render_view('login');
        break;
    case 'logout':
    case 'profile':
    case 'settings':
        $controller = new \App\Controllers\AccountController();
        $controller->handle($page, 'nuva_render_view');
        break;

    case 'my-environments':
    case 'my-tournaments':
        $personalCollection = 'tournaments';
        if ($page === 'my-environments') {
            $personalCollection = 'environments';
        }
        $personalQuery = $_GET;
        $personalQuery['page'] = 'profile';
        $personalQuery['collection'] = $personalCollection;
        header('Location: ?'.http_build_query($personalQuery).'#collections',true,303);
        break;

    case 'explore':
    case 'competitions':
    case 'competitions-all':
    case 'public-environment':
        $controller = new \App\Controllers\ExploreController();
        $controller->handle($page, 'nuva_render_view');
        break;

    case 'tournament-create':
    case 'tournament':
    case 'tournament-edit':
        $controller = new \App\Controllers\TournamentController();
        $controller->handle($page, 'nuva_render_view');
        break;

    case 'environment-create':
    case 'environment':
    case 'environment-invitation':
        $controller = new \App\Controllers\WorkspaceController();
        $controller->handle($page, 'nuva_render_view');
        break;

    case 'google-callback':
        require_once dirname(__DIR__) . '/vendor/autoload.php';

        $controller = new \App\Controllers\GoogleAuthController();
        $controller->callback();
        break;

    case 'google-photo-refresh':
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        $controller = new \App\Controllers\GoogleAuthController();
        $controller->refreshPhoto();
        break;

        /*
        Enrutamiento para loguearte con Google.
        */
    case 'google-login':
        require_once dirname(__DIR__) . '/vendor/autoload.php';

        if ($authenticated) {
            header('Location: ?page=profile', true, 303);
            break;
        }
        $controller = new \App\Controllers\GoogleAuthController();
        $controller->login();
        break;


    default:
        http_response_code(404);
        echo '<a href="?page=login">Login</a>';
        break;
}
