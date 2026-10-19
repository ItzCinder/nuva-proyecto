<?php
$firstClass = 'competition-rival';
$secondClass = 'competition-rival';
$decision = 'Empate';
$selectedOutcome = '';

if ($match['winner_id']) {
    if ($match['winner_id'] == $match['team_1_id']) {
        $firstClass .= ' is-winner';
        $decision = 'Ganador: ' . $match['name_1'];
        $selectedOutcome = '1';
    } else {
        $secondClass .= ' is-winner';
        $decision = 'Ganador: ' . $match['name_2'];
        $selectedOutcome = '2';
    }
} elseif ($match['status'] === 'Finalizado') {
    $selectedOutcome = 'draw';
}

$canEditMatch = false;
if ($canManage && $section === 'matches' && $round['status'] !== 'Cerrada' && $match['status'] !== 'Libre') {
    $canEditMatch = true;
}
?>
<article class="competition-game">
    <div class="competition-game-label">
        <span><?= nuva_escape($match['round_name']) ?> · #<?= (int) $match['id'] ?></span>
        <span><?= nuva_escape($match['status']) ?></span>
    </div>
    <div class="<?= $firstClass ?>">
        <strong><?= nuva_escape($match['name_1']) ?></strong>
        <span><?= nuva_score($match['score_1']) ?></span>
    </div>
    <div class="<?= $secondClass ?>">
        <strong><?= nuva_escape($match['name_2']) ?></strong>
        <span><?= nuva_score($match['score_2']) ?></span>
    </div>
    <?php if ($match['status'] === 'Finalizado'): ?>
    <p class="competition-decision"><?= nuva_escape($decision) ?></p>
    <?php endif; ?>
    <?php if ($match['scheduled_at']): ?>
    <p class="environment-muted"><?= nuva_escape(nuva_local_date($match['scheduled_at'])) ?></p>
    <?php endif; ?>
    <?php if ($match['notes']): ?>
    <p class="competition-notes"><?= nuva_escape($match['notes']) ?></p>
    <?php endif; ?>
    <?php if ($canEditMatch): ?>
        <?php if ($match['team_1_id'] && $match['team_2_id']): ?>
            <?php require __DIR__ . '/result-form.php'; ?>
        <?php endif; ?>
        <?php if ($match['status'] !== 'Finalizado'): ?>
            <?php require __DIR__ . '/schedule-form.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
</article>
