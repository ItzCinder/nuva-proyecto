<?php
$scoreDescription = 'Gana el valor mayor';
if ($tournament['score_order'] === 'lower') {
    $scoreDescription = 'Gana el valor menor';
}

$roundsClass = 'competition-rounds';
if ($section === 'standings') {
    $roundsClass .= ' competition-bracket';
}

// Agrupar primero los encuentros. El HTML solo recorre los de cada ronda.
$matchesByRound = [];
$shownMatches = 0;
foreach ($state['games'] as $match) {
    if ($section === 'results' && $match['status'] !== 'Finalizado') {
        continue;
    }
    $matchesByRound[(int) $match['round_no']][] = $match;
    $shownMatches++;
}
?>
<p class="environment-muted">Marcador: <?= nuva_escape($tournament['score_label']) ?> · <?= $scoreDescription ?>. Los desempates o decisiones especiales se explican en cada encuentro.</p>
<?php if ($section === 'matches'): ?>
<p class="environment-muted">
    Los resultados guardados se reflejan en la clasificación y, si el torneo es público, en su consulta. Cierra cada ronda cuando estén completos.
    <?php if ($canManage): ?>
    Reabre una ronda para corregirla; si cambia cruces posteriores, primero anula esos resultados.
    <?php endif; ?>
</p>
<?php endif; ?>
<div class="<?= $roundsClass ?>">
    <?php foreach ($state['rounds'] as $round): ?>
        <?php
        $roundNumber = (int) $round['number'];
        $roundMatches = $matchesByRound[$roundNumber] ?? [];
        if (!$roundMatches) {
            continue;
        }

        $roundAction = 'close_round';
        $roundButton = 'Cerrar ronda';
        if ($round['status'] === 'Cerrada') {
            $roundAction = 'reopen_round';
            $roundButton = 'Reabrir ronda';
        }
        ?>
    <section class="nuva-card tournament-panel competition-round">
        <div class="tournament-title-row">
            <h2 class="section-title"><?= nuva_escape($round['name']) ?></h2>
            <span class="environment-muted"><?= nuva_escape($round['status']) ?></span>
        </div>
        <?php if ($canManage && $section === 'matches'): ?>
        <div class="competition-actions">
            <form method="post">
                <?php nuva_csrf_field(); ?>
                <input type="hidden" name="action" value="<?= $roundAction ?>">
                <input type="hidden" name="round_no" value="<?= $roundNumber ?>">
                <button class="environment-button"><?= $roundButton ?></button>
            </form>
            <?php if ($tournament['format_code'] === 'swiss' && $roundNumber === count($state['rounds']) && $roundNumber > 1): ?>
            <form method="post" data-confirm="¿Retirar esta ronda para permitir corregir la anterior? Se conservará el registro de auditoría.">
                <?php nuva_csrf_field(); ?>
                <input type="hidden" name="action" value="discard_round">
                <input type="hidden" name="round_no" value="<?= $roundNumber ?>">
                <button class="environment-button">Retirar ronda</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="competition-games">
            <?php foreach ($roundMatches as $match): ?>
                <?php require __DIR__ . '/match.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</div>
<?php if ($shownMatches === 0): ?>
<p class="tournament-empty">Todavía no hay resultados registrados.</p>
<?php endif; ?>
<?php if ($canManage && $section === 'matches' && $tournament['format_code'] === 'swiss' && count($state['rounds']) < (int) $tournament['swiss_rounds']): ?>
<form method="post" class="competition-actions">
    <?php nuva_csrf_field(); ?>
    <input type="hidden" name="action" value="next_round">
    <button class="nuva-btn">Generar siguiente ronda</button>
    <span class="environment-muted">Cierra la ronda actual primero. Se buscarán rivales sin repetir.</span>
</form>
<?php endif; ?>
