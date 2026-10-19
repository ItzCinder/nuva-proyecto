<?php  ?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Nuva - Estadísticas</title>
        <link rel="stylesheet" href="/public/css/global.css?v=5">
    </head>
    <body>
        <main style="padding:3rem">
            <h1>Estadísticas</h1>
            <p role="alert"><?= nuva_escape($error) ?></p>
            <a href="?page=explore">Volver a explorar</a>
            <?php require nuva_view_path('components/footer'); ?>
        </main>
    </body>
</html>
