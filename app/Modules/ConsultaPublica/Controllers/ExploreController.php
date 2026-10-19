<?php

namespace App\Controllers;

use app\Models\Workspace;
use app\Models\Tournament;

class ExploreController
{
    public function handle(string $page, callable $render): void
    {
        $data = [
            'exploreError' => '',
            'publicEnvironment' => null,
            'publicEnvironments' => [],
            'tournamentItems' => [],

            'collectionType' => 'competitions',
            'collectionPage' => 'explore',
            'collectionAll' => true
        ];
        try {
            $spaces = new Workspace();
            $tournaments = new Tournament();
            $data['tournamentItems'] = $tournaments->publicList();
            if ($page === 'public-environment') {
                $raw = $_GET['id'] ?? '';
                $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id === false) {
                    $id = 0;
                }
                $data['publicEnvironment'] = $spaces->publicInfo($id);
                if (!$data['publicEnvironment']) {
                    http_response_code(404);
                    $data['exploreError'] = 'No encontramos ese entorno público.';
                }

                $environmentTournaments = [];
                foreach ($data['tournamentItems'] as $tournament) {
                    if ((int) $tournament['workspace_id'] === $id) {
                        $environmentTournaments[] = $tournament;
                    }
                }

                $data['tournamentItems'] = $environmentTournaments;
            } else {
                $data['publicEnvironments'] = $spaces->publicList();
            }
        } catch (\Throwable $exception) {
            nuva_log_error('Explorar', $exception);
            http_response_code(503);
            $data['exploreError'] = 'No se pudo cargar la información pública. Inténtalo de nuevo.';
        }

        $render('public/explore', $data);
    }
}
