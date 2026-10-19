<?php

// Las clases antiguas mantienen sus nombres, pero sus archivos viven en módulos.
function nuva_load_class(string $class): void
{
    $legacyClasses = [
        'router' => 'Core/Router.php',
        'database' => 'Core/Database.php',
        'app\\models\\model' => 'Core/Model.php',
        'app\\services\\competitionrules' => 'Modules/Resultados/Services/CompetitionRules.php',
        'app\\controllers\\homecontroller' => 'Modules/ConsultaPublica/Controllers/HomeController.php',
        'app\\controllers\\explorecontroller' => 'Modules/ConsultaPublica/Controllers/ExploreController.php',
        'app\\controllers\\accountcontroller' => 'Modules/Usuarios/Controllers/AccountController.php',
        'app\\controllers\\googleauthcontroller' => 'Modules/Usuarios/Controllers/GoogleAuthController.php',
        'app\\models\\user' => 'Modules/Usuarios/Models/User.php',
        'app\\services\\googleauthservice' => 'Modules/Usuarios/Services/GoogleAuthService.php',
        'app\\services\\userservice' => 'Modules/Usuarios/Services/UserService.php',
        'app\\controllers\\workspacecontroller' => 'Modules/Administracion/Controllers/WorkspaceController.php',
        'app\\models\\workspace' => 'Modules/Administracion/Models/Workspace.php',
        'app\\models\\role' => 'Modules/Administracion/Models/Role.php',
        'app\\controllers\\tournamentcontroller' => 'Modules/Torneos/Controllers/TournamentController.php',
        'app\\models\\tournament' => 'Modules/Torneos/Models/Tournament.php',
        'app\\models\\competition' => 'Modules/Torneos/Models/Competition.php',
    ];
    $key = strtolower($class);
    if (isset($legacyClasses[$key])) {
        require_once __DIR__ . '/' . $legacyClasses[$key];
        return;
    }

    // Las clases nuevas indican su carpeta: App\Modules\Liga\Models\League, por ejemplo.
    if (str_starts_with($key, 'app\\modules\\')) {
        $relativeClass = substr($class, 4);
        $path = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
}

spl_autoload_register('nuva_load_class');
require_once __DIR__ . '/Core/views.php';
require_once __DIR__ . '/Core/helpers.php';
require_once __DIR__ . '/Core/security.php';
require_once __DIR__ . '/Core/config.php';
require_once __DIR__ . '/Modules/Estadisticas/Services/DisplayHelpers.php';
