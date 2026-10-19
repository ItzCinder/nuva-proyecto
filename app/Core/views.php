<?php

function nuva_view_path(string $view): string
{
    static $views = [
        'login' => 'app/Modules/Usuarios/Views/login.php',
        'account/profile' => 'app/Modules/Usuarios/Views/profile.php',
        'account/home' => 'app/Modules/ConsultaPublica/Views/home.php',
        'account/settings' => 'app/Modules/Usuarios/Views/settings.php',
        'components/collection-tabs' => 'app/Shared/Views/components/collection-tabs.php',
        'components/collection' => 'app/Shared/Views/components/collection.php',
        'components/footer' => 'app/Shared/Views/components/footer.php',
        'components/header' => 'app/Shared/Views/components/header.php',
        'components/tabbar' => 'app/Shared/Views/components/tabbar.php',
        'components/competition-card' => 'app/Modules/ConsultaPublica/Views/components/competition-card.php',
        'public/competitions' => 'app/Modules/ConsultaPublica/Views/competitions.php',
        'public/guide' => 'app/Modules/ConsultaPublica/Views/guide.php',
        'public/information' => 'app/Modules/ConsultaPublica/Views/information.php',
        'public/home-public' => 'app/Modules/ConsultaPublica/Views/home-public.php',
        'public/explore' => 'app/Modules/ConsultaPublica/Views/explore.php',
        'environments/page' => 'app/Modules/Administracion/Views/page.php',
        'environments/list' => 'app/Modules/Administracion/Views/list.php',
        'environments/collection' => 'app/Modules/Administracion/Views/collection.php',
        'environments/information-fields' => 'app/Modules/Administracion/Views/information-fields.php',
        'environments/create' => 'app/Modules/Administracion/Views/create.php',
        'environments/invitation' => 'app/Modules/Administracion/Views/invitation.php',
        'environments/detail' => 'app/Modules/Administracion/Views/detail.php',
        'environments/members' => 'app/Modules/Participantes/Views/workspace-members.php',
        'environments/invitations' => 'app/Modules/Administracion/Views/invitations.php',
        'environments/audit' => 'app/Modules/Auditoria/Views/audit.php',
        'tournaments/page' => 'app/Modules/Torneos/Views/page.php',
        'tournaments/form' => 'app/Modules/Torneos/Views/form.php',
        'tournaments/detail' => 'app/Modules/Torneos/Views/detail.php',
        'tournaments/competition' => 'app/Modules/Resultados/Views/competition.php',
        'tournaments/schedule' => 'app/Modules/Calendarios/Views/schedule.php',
        'statistics/page' => 'app/Modules/Estadisticas/Views/page.php',
        'statistics/error' => 'app/Modules/Estadisticas/Views/error.php',
        'statistics/templates' => 'app/Modules/Estadisticas/Views/templates.php',
        'statistics/environment' => 'app/Modules/Estadisticas/Views/environment.php',
        'statistics/tournament' => 'app/Modules/Estadisticas/Views/tournament.php',
        'statistics/trial-form' => 'app/Modules/Estadisticas/Views/trial-form.php',
        'statistics/manual-form' => 'app/Modules/Estadisticas/Views/manual-form.php',
        'statistics/records' => 'app/Modules/Estadisticas/Views/records.php',
    ];
    if (!isset($views[$view])) {
        throw new RuntimeException('Vista desconocida: ' . $view);
    }

    return dirname(__DIR__, 2) . '/' . $views[$view];
}
function nuva_asset(string $path): string
{
    static $assets = [
        '/public/css/account.css' => '/public/modules/ConsultaPublica/css/account.css',
        '/public/css/environments.css' => '/public/modules/Administracion/css/environments.css',
        '/public/css/tournaments.css' => '/public/modules/Torneos/css/tournaments.css',
        '/public/css/index.css' => '/public/modules/ConsultaPublica/css/index.css',
        '/public/css/guide.css' => '/public/modules/ConsultaPublica/css/guide.css',
        '/public/css/explore.css' => '/public/modules/ConsultaPublica/css/explore.css',
        '/public/css/statistics.css' => '/public/modules/Estadisticas/css/statistics.css',
        '/public/css/account/profile.css' => '/public/modules/Usuarios/css/profile.css',
        '/public/css/components/collection.css' => '/public/shared/css/collection.css',
        '/public/css/components/header.css' => '/public/shared/css/header.css',
        '/public/css/components/tabbar.css' => '/public/shared/css/tabbar.css',
        '/public/css/public/home-public.css' => '/public/modules/ConsultaPublica/css/home-public.css',
        '/public/css/public/competitions.css' => '/public/modules/ConsultaPublica/css/competitions.css',
        '/public/js/environments.js' => '/public/modules/Administracion/js/environments.js',
        '/public/js/tournaments.js' => '/public/modules/Torneos/js/tournaments.js',
        '/public/js/statistics.js' => '/public/modules/Estadisticas/js/statistics.js',
        '/public/js/layout.js' => '/public/shared/js/layout.js',
    ];
    $parts = explode('?', $path, 2);
    return ($assets[$parts[0]] ?? $parts[0]) . (isset($parts[1]) ? '?' . $parts[1] : '');
}

// Cargar solo las vistas que figuran en el listado de este archivo.
function nuva_render_view(string $view, array $data = []): void
{
    $file = nuva_view_path($view);
    if (!is_file($file)) {
        http_response_code(404);
        echo 'No encontramos esa página.';
        return;
    }
    extract($data, EXTR_SKIP);
    require $file;
}
