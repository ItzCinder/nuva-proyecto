<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\GoogleAuthService;
use App\Services\UserService;

class GoogleAuthController
{
    public function login(): void
    {
        $service = new GoogleAuthService();

        header('Location: ' . $service->getAuthUrl());
        exit;
    }

    public function callback(): void
    {
        if (!empty($_GET['error'])) {
            unset($_SESSION['oauth2state']);
            header('Location: ?page=login');
            exit;
        }

        $state = $_GET['state'] ?? null;
        $sessionState = $_SESSION['oauth2state'] ?? null;

        if (
            !is_string($state) ||
            !is_string($sessionState) ||
            !hash_equals($sessionState, $state)
        ) {
            unset($_SESSION['oauth2state']);
            http_response_code(400);
            echo 'Estado OAuth inválido';
            exit;
        }

        $code = $_GET['code'] ?? null;

        if (!is_string($code) || $code === '') {
            http_response_code(400);
            echo 'Código de autorización no encontrado';
            exit;
        }

        $googleService = new GoogleAuthService();
        $userInfo = $googleService->getUserInfoFromCode($code);

        if (empty($userInfo['google_id']) || empty($userInfo['email'])) {
            http_response_code(502);
            echo 'Google no devolvió los datos necesarios del usuario';
            exit;
        }

        try {
            $userService = new UserService();
            $user = $userService->findOrCreateFromGoogle($userInfo);
        } catch (\Throwable) {
            http_response_code(500);
            echo 'No se pudo guardar el usuario';
            exit;
        }

        unset($_SESSION['oauth2state']);
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = $user->getId();

        header('Location: ?page=index');
        exit;
    }

}
