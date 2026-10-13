<?php
$state=$competition;
$hidden=function(string $action,?array $game=null,?int $round=null) use ($escape) {
    echo '<input type="hidden" name="csrf_token" value="'.$escape($_SESSION['csrf_token']).'"><input type="hidden" name="action" value="'.$escape($action).'">';
    if ($game) echo '<input type="hidden" name="match_id" value="'.(int)$game['id'].'"><input type="hidden" name="revision" value="'.(int)$game['revision'].'">';
    if ($round!==null) echo '<input type="hidden" name="round_no" value="'.$round.'">';
};
$number=static fn($v)=>$v===null?'—':rtrim(rtrim(number_format((float)$v,2,'.',''),'0'),'.');
?>
<?php if (!$state['rounds']): ?>
<section class="nuva-card tournament-panel"><h2 class="section-title">Preparar los enfrentamientos</h2><p class="environment-muted">Todavía no se generaron encuentros. Primero completa las inscripciones y revisa las reglas.</p>
<?php if ($canManage): ?><form method="post" data-confirm="Se sorteará el orden inicial y se cerrarán las inscripciones y las reglas. ¿Generar los encuentros?">
<?php $hidden('generate'); ?><button class="nuva-btn">Generar enfrentamientos</button></form><?php endif; ?></section>
<?php elseif ($section==='standings' && $t['format_code']!=='elimination'): ?>
<section class="nuva-card tournament-panel"><h2 class="section-title">Clasificación <?= $t['status']==='Finalizado'?'final':'actual' ?></h2>
<p class="environment-muted">Victoria: <?= $number($t['points_win']) ?> · <?= $t['allow_draws']?'Empate: '.$number($t['points_draw']):'Sin empates' ?> · Derrota: <?= $number($t['points_loss']) ?> puntos.</p>
<div class="competition-table-wrap"><table class="competition-table"><thead><tr><th scope="col">Pos.</th><th scope="col"><?= $t['has_team']?'Equipo':'Participante' ?></th><th scope="col">Puntos</th><th scope="col">Jugados</th><th scope="col">Victorias</th><th scope="col">Empates</th><th scope="col">Derrotas</th><th scope="col">Libres</th><th scope="col">Diferencia</th><?php if ($t['format_code']==='swiss'): ?><th scope="col">Buchholz</th><?php endif; ?></tr></thead><tbody>
<?php foreach ($state['standings'] as $i=>$row): ?><tr><td><?= $i+1 ?></td><th scope="row"><?= $escape($row['name']) ?></th><?php foreach (['points','played','wins','draws','losses','byes','diff'] as $key): ?><td><?= $number($row[$key]) ?></td><?php endforeach; ?><?php if ($t['format_code']==='swiss'): ?><td><?= $number($row['buchholz']) ?></td><?php endif; ?></tr><?php endforeach; ?>
</tbody></table></div><p class="environment-muted">Desempates: <?= $t['format_code']==='swiss'?'Buchholz (suma de puntos de los rivales), victorias y diferencia del marcador':'diferencia del marcador y victorias' ?>; finalmente, orden del sorteo inicial. La diferencia se invierte cuando gana el valor menor. Los resultados sin marcador no aportan diferencia.</p></section>
<?php else: ?>
<p class="environment-muted">Marcador: <?= $escape($t['score_label']) ?> · <?= $t['score_order']==='lower'?'Gana el valor menor':'Gana el valor mayor' ?>. Los desempates o decisiones especiales se explican en cada encuentro.</p>
<?php if ($section==='matches'): ?><p class="environment-muted">Los resultados guardados se reflejan en la clasificación y, si el torneo es público, en su consulta. Cierra cada ronda cuando estén completos. <?= $canManage?'Reabre una ronda para corregirla; si cambia cruces posteriores, primero anula esos resultados.':'' ?></p><?php endif; ?>
<div class="competition-rounds <?= $section==='standings'?'competition-bracket':'' ?>">
<?php $shown=0; foreach ($state['rounds'] as $round):
$games=array_values(array_filter($state['games'],static fn($g)=>(int)$g['round_no']===(int)$round['number'] && ($section!=='results' || $g['status']==='Finalizado')));
if (!$games) continue; $shown+=count($games); ?>
<section class="nuva-card tournament-panel competition-round"><div class="tournament-title-row"><h2 class="section-title"><?= $escape($round['name']) ?></h2><span class="environment-muted"><?= $escape($round['status']) ?></span></div>
<?php if ($canManage && $section==='matches'): ?><div class="competition-actions"><form method="post"><?php $hidden($round['status']==='Cerrada'?'reopen_round':'close_round',null,(int)$round['number']); ?><button class="environment-button"><?= $round['status']==='Cerrada'?'Reabrir ronda':'Cerrar ronda' ?></button></form>
<?php if ($t['format_code']==='swiss' && (int)$round['number']===count($state['rounds']) && (int)$round['number']>1): ?><form method="post" data-confirm="¿Retirar esta ronda para permitir corregir la anterior? Se conservará el registro de auditoría."><?php $hidden('discard_round',null,(int)$round['number']); ?><button class="environment-button">Retirar ronda</button></form><?php endif; ?></div><?php endif; ?>
<div class="competition-games"><?php foreach ($games as $g): ?>
<article class="competition-game"><div class="competition-game-label"><span><?= $escape($g['round_name']) ?> · #<?= (int)$g['id'] ?></span><span><?= $escape($g['status']) ?></span></div>
<div class="competition-rival <?= $g['winner_id'] && $g['winner_id']==$g['team_1_id']?'is-winner':'' ?>"><strong><?= $escape($g['name_1']) ?></strong><span><?= $number($g['score_1']) ?></span></div>
<div class="competition-rival <?= $g['winner_id'] && $g['winner_id']==$g['team_2_id']?'is-winner':'' ?>"><strong><?= $escape($g['name_2']) ?></strong><span><?= $number($g['score_2']) ?></span></div>
<?php if ($g['status']==='Finalizado'): ?><p class="competition-decision"><?= $g['winner_id']?'Ganador: '.$escape($g['winner_id']==$g['team_1_id']?$g['name_1']:$g['name_2']):'Empate' ?></p><?php endif; ?>
<?php if ($g['scheduled_at']): ?><p class="environment-muted"><?= $escape($local($g['scheduled_at'])) ?></p><?php endif; ?>
<?php if ($g['notes']): ?><p class="competition-notes"><?= $escape($g['notes']) ?></p><?php endif; ?>
<?php if ($canManage && $section==='matches' && $round['status']!=='Cerrada' && $g['status']!=='Libre'): ?>
<?php if ($g['team_1_id'] && $g['team_2_id']): ?><details><summary><?= $g['status']==='Finalizado'?'Corregir resultado':'Registrar resultado' ?></summary>
<form method="post" class="environment-form competition-result-form"><?php $hidden('save_result',$g); ?>
<div class="competition-score-inputs"><?php foreach ([1,2] as $side): ?><label class="environment-field"><?= $escape($t['score_label'].' · '.$g['name_'.$side]) ?><input type="number" name="score_<?= $side ?>" step="0.01" min="-999999.99" max="999999.99" value="<?= $escape($g['score_'.$side] ?? '') ?>"></label><?php endforeach; ?></div>
<label class="environment-field">Decisión<select name="outcome" required><option value="">Selecciona el resultado</option><?php foreach ([1,2] as $side): ?><option value="<?= $side ?>" <?= $g['winner_id'] && $g['winner_id']==$g['team_'.$side.'_id']?'selected':'' ?>>Gana <?= $escape($g['name_'.$side]) ?></option><?php endforeach; ?><?php if ($t['allow_draws'] && $t['format_code']!=='elimination'): ?><option value="draw" <?= $g['status']==='Finalizado' && !$g['winner_id']?'selected':'' ?>>Empate</option><?php endif; ?></select></label>
<label class="environment-field">Detalle o motivo<textarea name="notes" maxlength="500" rows="2" placeholder="Obligatorio en correcciones, decisiones sin marcador o desempates."><?= $escape($g['notes']) ?></textarea></label><button class="nuva-btn">Guardar resultado</button></form>
<?php if ($g['status']==='Finalizado'): ?><form method="post" class="environment-form competition-result-form" data-confirm="¿Anular este resultado? La clasificación se recalculará."><?php $hidden('clear_result',$g); ?><label class="environment-field">Motivo de anulación<input name="notes" required maxlength="500"></label><button class="environment-button">Anular resultado</button></form><?php endif; ?></details><?php endif; ?>
<?php if ($g['status']!=='Finalizado'): ?><details><summary>Fecha y disponibilidad</summary><form method="post" class="environment-form competition-result-form"><?php $hidden('schedule',$g); ?><label class="environment-field">Fecha y hora (Uruguay)<input type="datetime-local" name="scheduled_at" value="<?= $g['scheduled_at']?$escape($local($g['scheduled_at'],'Y-m-d\TH:i')):'' ?>"></label><label class="environment-check"><input type="checkbox" name="suspended" value="1" <?= $g['status']==='Suspendido'?'checked':'' ?>> Encuentro suspendido</label><button class="environment-button">Guardar fecha y estado</button></form></details><?php endif; ?>
<?php endif; ?></article><?php endforeach; ?></div></section><?php endforeach; ?>
</div>
<?php if (!$shown): ?><p class="tournament-empty">Todavía no hay resultados registrados.</p><?php endif; ?>
<?php if ($canManage && $section==='matches' && $t['format_code']==='swiss' && count($state['rounds'])<(int)$t['swiss_rounds']): ?><form method="post" class="competition-actions"><?php $hidden('next_round'); ?><button class="nuva-btn">Generar siguiente ronda</button><span class="environment-muted">Cierra la ronda actual primero. Se buscarán rivales sin repetir.</span></form><?php endif; ?>
<?php endif; ?>
