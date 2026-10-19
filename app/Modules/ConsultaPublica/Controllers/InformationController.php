<?php

namespace App\Modules\ConsultaPublica\Controllers;

use App\Modules\ConsultaPublica\Models\Team;

class InformationController
{
    public function handle(string $page, callable $render): void
    {
        $data = ['informationPage' => $page];

        // Solo estos archivos forman parte de las páginas informativas.
        switch ($page) {
            case 'about':
                $data['title'] = 'Sobre nosotros';
                $data['contentFile'] = __DIR__ . '/../Views/information/about.php';
                $data['members'] = Team::members();
                break;
            case 'terms':
                $data['title'] = 'Condiciones de uso';
                $data['contentFile'] = __DIR__ . '/../Views/information/terms.php';
                break;
            case 'copyright':
                $data['title'] = 'Copyright y créditos';
                $data['contentFile'] = __DIR__ . '/../Views/information/copyright.php';
                break;
            default:
                http_response_code(404);
                return;
        }

        $render('public/information', $data);
    }
}
