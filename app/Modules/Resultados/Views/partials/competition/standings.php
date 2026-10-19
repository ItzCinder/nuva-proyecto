<?php
$heading = 'Clasificación actual';
if ($tournament['status'] === 'Finalizado') {
    $heading = 'Clasificación final';
}

$entrantLabel = 'Participante';
if ($tournament['has_team']) {
    $entrantLabel = 'Equipo';
}

$drawDescription = 'Sin empates';
if ($tournament['allow_draws']) {
    $drawDescription = 'Empate: ' . nuva_score($tournament['points_draw']);
}

$tiebreakDescription = 'diferencia del marcador y victorias';
if ($tournament['format_code'] === 'swiss') {
    $tiebreakDescription = 'Buchholz (suma de puntos de los rivales), victorias y diferencia del marcador';
}
?>
<section class="nuva-card tournament-panel">
    <h2 class="section-title"><?= $heading ?></h2>
    <p class="environment-muted">Victoria: <?= nuva_score($tournament['points_win']) ?> · <?= $drawDescription ?> · Derrota: <?= nuva_score($tournament['points_loss']) ?> puntos.</p>
    <div class="competition-table-wrap">
        <table class="competition-table">
            <thead>
                <tr>
                    <th scope="col">Pos.</th>
                    <th scope="col"><?= $entrantLabel ?></th>
                    <th scope="col">Puntos</th>
                    <th scope="col">Jugados</th>
                    <th scope="col">Victorias</th>
                    <th scope="col">Empates</th>
                    <th scope="col">Derrotas</th>
                    <th scope="col">Libres</th>
                    <th scope="col">Diferencia</th>
                    <?php if ($tournament['format_code'] === 'swiss'): ?>
                    <th scope="col">Buchholz</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($state['standings'] as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <th scope="row"><?= nuva_escape($row['name']) ?></th>
                    <td><?= nuva_score($row['points']) ?></td>
                    <td><?= nuva_score($row['played']) ?></td>
                    <td><?= nuva_score($row['wins']) ?></td>
                    <td><?= nuva_score($row['draws']) ?></td>
                    <td><?= nuva_score($row['losses']) ?></td>
                    <td><?= nuva_score($row['byes']) ?></td>
                    <td><?= nuva_score($row['diff']) ?></td>
                    <?php if ($tournament['format_code'] === 'swiss'): ?>
                    <td><?= nuva_score($row['buchholz']) ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="environment-muted">Desempates: <?= $tiebreakDescription ?>; finalmente, orden del sorteo inicial. La diferencia se invierte cuando gana el valor menor. Los resultados sin marcador no aportan diferencia.</p>
</section>
