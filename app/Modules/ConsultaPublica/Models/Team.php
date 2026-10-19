<?php

namespace App\Modules\ConsultaPublica\Models;

class Team
{
    // Nombres y fotos de la presentación del equipo Kervox en Google Drive.
    // El contenido es público y no necesita consultar cuentas de usuarios.
    public static function members(): array
    {
        return [
            ['name' => 'Jean La Cruz', 'photo' => '/public/assets/team/jean-la-cruz.jpg'],
            ['name' => 'Paul Miyashiro', 'photo' => '/public/assets/team/paul-miyashiro.png'],
            ['name' => 'Kevin Susundey', 'photo' => '/public/assets/team/kevin-susundey.png'],
        ];
    }
}
