<?php
namespace App\Controllers;

use app\Models\Tournament;
use app\Models\User;
use app\Models\Workspace;

class HomeController
{
    public function handle(bool $authenticated, callable $render): void
    {
        $data = ['publicTournaments' => [], 'myTournaments' => [], 'myEnvironments' => [], 'homeError' => ''];
        try {
            $tournaments = new Tournament();
            $data['publicTournaments'] = $tournaments->publicList();
            if ($authenticated) {
                $userId = (int)($_SESSION['user_id'] ?? 0);
                if (!$userId || !($data['user'] = User::findById($userId))) {
                    $render('login');
                    return;
                }
                $data['myTournaments'] = $tournaments->myList($userId);
                $data['myEnvironments'] = (new Workspace())->allForUser($userId);
            }
        } catch (\Throwable $e) {
            error_log('Inicio de Nuva: ' . $e->getMessage());
            $data['homeError'] = 'No pudimos actualizar los datos del inicio. Inténtalo de nuevo más tarde.';
        }
        $render($authenticated ? 'account/home' : 'public/home-public', $data);
    }
}
