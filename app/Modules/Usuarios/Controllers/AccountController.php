<?php

namespace App\Controllers;

use app\Models\Tournament;
use app\Models\User;
use app\Models\Workspace;

class AccountController
{
    public function handle(string $page, callable $render): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (empty($_SESSION['authenticated']) || !ctype_digit((string)$userId) || (int)$userId < 1) {
            header('Location: ?page=login', true, 303);
            return;
        }

        $user = User::findById((int)$userId);
        if (!$user) {
            $_SESSION = [];
            session_destroy();
            header('Location: ?page=login', true, 303);
            return;
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $error = '';
        $nameValue = $user->getName();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                nuva_security_event('csrf_rejected');
                http_response_code(403);
                $error = 'El formulario venció. Recarga la página e inténtalo de nuevo.';
            } elseif ($page === 'logout') {
                $this->logout();
                return;
            } elseif (($_POST['action'] ?? '') === 'name') {
                $nameValue = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
                if (mb_strlen($nameValue) < 2 || mb_strlen($nameValue) > 80 || preg_match('/[\x00-\x1F\x7F]/u', $nameValue)) {
                    http_response_code(422);
                    $error = 'Escribe un nombre de entre 2 y 80 caracteres, sin saltos de línea.';
                } elseif (!$user->updateDisplayName($nameValue)) {
                    http_response_code(503);
                    $error = 'No se pudo guardar el nombre. Inténtalo de nuevo.';
                } else {
                    nuva_security_event('profile_name_updated');
                    $_SESSION['profile_flash'] = 'Tu nombre se actualizó.';
                    header('Location: ?page=settings', true, 303);
                    return;
                }
            } else {
                http_response_code(400);
                $error = 'Acción desconocida.';
            }
        } elseif ($page === 'logout') {
            header('Location: ?page=settings', true, 303);
            return;
        }

        $flash = $_SESSION['profile_flash'] ?? '';
        $oauthError = $_SESSION['profile_error'] ?? '';
        unset($_SESSION['profile_flash'], $_SESSION['profile_error']);
        $viewData = [
            'user' => $user,
            'nameValue' => $nameValue,
            'profileError' => $error ?: $oauthError,
            'profileFlash' => $flash,
        ];
        if ($page === 'settings') {
            $render('account/settings', $viewData);
            return;
        }

        $environmentItems = [];
        $environmentError = '';
        if (($_GET['collection'] ?? '') === 'environments') {
            try {
                $environmentItems = (new Workspace())->allForUser((int)$userId);
            } catch (\Throwable $exception) {
                nuva_log_error('Listado de entornos', $exception);
                $environmentError = 'No se pudieron cargar tus entornos. Inténtalo de nuevo.';
            }
        }

        $render('account/profile', $viewData + [
            'collectionAll' => true,
            'environmentItems' => $environmentItems,
            'environmentError' => $environmentError,
            'tournamentItems' => (new Tournament())->myList((int)$userId),
        ]);
    }

    private function logout(): void
    {
        nuva_security_event('logout');
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
        header('Location: ?page=login', true, 303);
    }
}
