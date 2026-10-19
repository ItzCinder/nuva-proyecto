<?php use App\Modules\Estadisticas\Services\Physics;

?>
<details class="nuva-card tournament-panel">
    <summary class="section-title">Ayuda: ¿para qué sirve cada opción?</summary>
    <p class="environment-muted">No necesitas conocer las fórmulas para cargar una prueba. Elige lo que quieres saber y sigue las indicaciones.</p>
    <div class="statistics-guides">
        <?php foreach (Physics::TEMPLATES as $code => $template): ?>
        <article class="statistics-guide">
            <h3><?= nuva_escape($template['name']) ?></h3>
            <p><?= nuva_escape($template['purpose']) ?></p>
            <p>
                <strong>Por ejemplo:</strong> <?= nuva_escape($template['example']) ?></p>
            <p><?= nuva_escape($template['meaning']) ?></p>
            <details>
                <summary>Cuándo usarla</summary>
                <p><?= nuva_escape($template['conditions']) ?></p>
            </details>
            <details>
                <summary>Ver las fórmulas</summary>
                <p class="environment-muted"><?= [
                    'motion' => 'Rapidez media = distancia / tiempo. Si sale desde el reposo y la aceleración es constante: aceleración = 2 × distancia / tiempo²; rapidez final = 2 × distancia / tiempo.',
                    'dynamics' => 'Fuerza neta = masa × aceleración. La masa son los kilos que indica la balanza.',
                    'energy' => 'Energía cinética = masa × rapidez² / 2. Cambio de energía = energía final − inicial. Trabajo de una fuerza constante = fuerza × desplazamiento × coseno del ángulo.'
                ][$code] ?></p>
            </details>
        </article>
        <?php endforeach; ?>
    </div>
</details>
