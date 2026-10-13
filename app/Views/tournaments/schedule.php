<section class="nuva-card tournament-panel tournament-agenda"><h2 class="section-title">Agenda de encuentros</h2>
<?php $scheduled=array_values(array_filter($competition['games'],static fn($g)=>!empty($g['scheduled_at']))); usort($scheduled,static fn($a,$b)=>strcmp($a['scheduled_at'],$b['scheduled_at'])); ?>
<?php if (!$scheduled): ?><p class="environment-muted">Aún no hay encuentros con fecha asignada.</p><?php endif; ?>
<?php foreach ($scheduled as $g): ?><div class="tournament-roster"><strong><?= $escape($g['name_1'].' — '.$g['name_2']) ?></strong><span><?= $escape($local($g['scheduled_at'])) ?> · <?= $escape($g['round_name'].' · '.$g['status']) ?></span></div><?php endforeach; ?>
</section>

<?php $events=array_map(static fn($g)=>['id'=>$g['id'],'day'=>$local($g['scheduled_at'],'Y-m-d'),'at'=>$g['scheduled_at'],'title'=>$g['name_1'].' — '.$g['name_2'],'status'=>$g['status']],$scheduled); ?>
<script type="application/json" data-calendar-events><?= json_encode($events,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
