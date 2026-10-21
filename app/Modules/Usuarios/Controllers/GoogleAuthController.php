<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\GoogleAuthService;
use App\Services\UserService;
use app\Models\User;

class GoogleAuthController
{
    public function login(): void
    {
        $service = new GoogleAuthService();

        header('Location: ' . $service->getAuthUrl());
        exit;
    }

    public function refreshPhoto(): void
    {
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['authenticated']) ||
            !ctype_digit((string)($_SESSION['user_id'] ?? '')) ||
            !is_string($_SESSION['csrf_token'] ?? null) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            echo 'Solicitud inválida. Vuelve a tu cuenta e inténtalo de nuevo.';
            return;
        }

        $_SESSION['google_photo_refresh_user_id'] = (int)$_SESSION['user_id'];
        $this->login();
    }

    public function callback(): void
    {
        if (!empty($_GET['error'])) {
            $this->loginError('No se completó el acceso con Google.');
        }

        $state = $_GET['state'] ?? null;
        $sessionState = $_SESSION['oauth2state'] ?? null;

        if (
            !is_string($state) ||
            !is_string($sessionState) ||
            !hash_equals($sessionState, $state)
        ) {
            $this->loginError('La sesión de acceso venció. Vuelve a continuar con Google.');
        }

        $code = $_GET['code'] ?? null;

        if (!is_string($code) || $code === '') {
            $this->loginError('No se recibió la autorización. Vuelve a continuar con Google.');
        }

        // Comprobar la base antes de utilizar el código de un solo uso de Google.
        try {
            \Database::getConnection();
        } catch (\PDOException $exception) {
            error_log('GoogleAuth: conexión a la base fallida; SQLSTATE ' . $exception->getCode());
            $this->loginError('La base de datos de Nuva no está disponible. Inténtalo de nuevo cuando el servicio esté funcionando.');
        }

        try {
            $googleService = new GoogleAuthService();
            $userInfo = $googleService->getUserInfoFromCode($code);
        } catch (\Throwable $exception) {
            error_log('GoogleAuth: autorización fallida; ' . get_class($exception));
            $this->loginError('No se pudo completar el acceso con Google. Vuelve a intentarlo desde el botón de acceso.');
        }

        if (empty($userInfo['google_id']) || empty($userInfo['email'])) {
            $this->loginError('Google no devolvió los datos necesarios de tu cuenta. Vuelve a intentarlo.');
        }

        $refreshId = (int)($_SESSION['google_photo_refresh_user_id'] ?? 0);
        if ($refreshId) {
            $current = User::findById($refreshId);
            if (!$current || $current->getGoogleId() !== $userInfo['google_id']) {
                $this->loginError('Selecciona la misma cuenta de Google vinculada a este perfil de Nuva.');
            }
        }

        try {
            $userService = new UserService();
            $user = $userService->findOrCreateFromGoogle($userInfo);
        } catch (\Throwable $exception) {
            error_log('GoogleAuth: no se pudo guardar el usuario; ' . get_class($exception) . '; código ' . $exception->getCode());
            $this->loginError('No se pudo guardar tu cuenta en Nuva. Inténtalo de nuevo o consulta con la administración.');
        }

        unset($_SESSION['oauth2state']);
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        nuva_security_event('login_google_success');

        if ($refreshId) {
            unset($_SESSION['google_photo_refresh_user_id']);
            if ($user->getPictureUrl()) {
                $_SESSION['profile_flash'] = 'Tu foto de Google se actualizó.';
            } else {
                $_SESSION['profile_error'] = 'Google no proporcionó una foto para esta cuenta.';
            }

            header('Location: ?page=settings', true, 303);
            exit;
        }

        $invitation = $_SESSION['pending_invitation'] ?? '';
        if (is_string($invitation) && preg_match('/^(?:[A-HJ-NP-Z2-9]{8}|[a-f0-9]{48})$/', $invitation)) {
            header('Location: ?page=environment-invitation&code=' . $invitation);
        } else {
            header('Location: ?page=index');
        }
        exit;
    }

    private function loginError(string $message): void
    {
        nuva_security_event('login_google_failed');
        unset($_SESSION['oauth2state']);
        if (isset($_SESSION['google_photo_refresh_user_id'])) {
            unset($_SESSION['google_photo_refresh_user_id']);
            $_SESSION['profile_error'] = $message;
            header('Location: ?page=settings', true, 303);
            exit;
        }

        $_SESSION['login_error'] = $message;
        header('Location: ?page=login', true, 303);
        exit;
    }
}
