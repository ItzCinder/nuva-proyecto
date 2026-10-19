<?php

namespace App\Controllers;

use app\Models\Tournament;
use app\Models\User;
use app\Models\Workspace;

class HomeController
{
    public function handle(bool $authenticated, callable $render): void
    {
        $data = [
            'publicTournaments' => [],
            'myTournaments' => [],
            'myEnvironments' => [],
            'homeError' => ''
        ];
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
        } catch (\Throwable $exception) {
            nuva_log_error('Inicio de Nuva', $exception);
            $data['homeError'] = 'No pudimos actualizar los datos del inicio. Inténtalo de nuevo más tarde.';
        }

        $render($authenticated ? 'account/home' : 'public/home-public', $data);
    }
    public static function compareFeatured(array $firstTournament, array $secondTournament): int
    {
        $firstIsActive = $firstTournament['status'] === 'En curso';
        $secondIsActive = $secondTournament['status'] === 'En curso';
        if ($firstIsActive && !$secondIsActive) {
            return -1;
        }
        if (!$firstIsActive && $secondIsActive) {
            return 1;
        }
        if ($firstIsActive) {
            return strcmp($firstTournament['end_date'], $secondTournament['end_date']);
        }
        return strcmp($firstTournament['start_date'], $secondTournament['start_date']);
    }
}
