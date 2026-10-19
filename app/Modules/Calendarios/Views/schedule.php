<?php
use App\Modules\Calendarios\Services\Schedule;

$scheduled = Schedule::datedMatches($competition['games']);
$events = [];
foreach ($scheduled as $match) {
    $events[] = [
        'id' => $match['id'],
        'day' => nuva_local_date($match['scheduled_at'], 'Y-m-d'),
        'at' => $match['scheduled_at'],
        'title' => $match['name_1'] . ' — ' . $match['name_2'],
        'status' => $match['status'],
    ];
}
?>
<section class="nuva-card tournament-panel tournament-agenda">
    <h2 class="section-title">Agenda de encuentros</h2>
    <?php if (!$scheduled): ?>
    <p class="environment-muted">Aún no hay encuentros con fecha asignada.</p>
    <?php endif; ?>
    <?php foreach ($scheduled as $match): ?>
    <div class="tournament-roster">
        <strong><?= nuva_escape($match['name_1'] . ' — ' . $match['name_2']) ?></strong>
        <span><?= nuva_escape(nuva_local_date($match['scheduled_at'])) ?> · <?= nuva_escape($match['round_name'] . ' · ' . $match['status']) ?></span>
    </div>
    <?php endforeach; ?>
</section>
<script type="application/json" data-calendar-events>
    <?= json_encode($events, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>
