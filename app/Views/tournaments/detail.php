<?php
use app\Models\Tournament;
use app\Models\Role;
$t=$tournament;
$section=is_string($_GET['section'] ?? null) ? $_GET['section'] : 'overview';
$sections=['overview'=>'Resumen','participants'=>$t['has_team']?'Equipos y participantes':'Participantes','matches'=>'Encuentros','calendar'=>'Calendario','standings'=>$t['format_code']==='elimination'?'Llave':'Clasificación','results'=>'Resultados'];
if (!isset($sections[$section])) $section='overview';
$formatExplanations=[
    'league'=>'Todos contra todos. Se disputan '.((int)$t['league_legs']===2?'partidos de ida y vuelta':'partidos de una vuelta').' con una tabla de posiciones.',
    'swiss'=>'Rondas según el rendimiento acumulado. Se configuraron '.(int)$t['swiss_rounds'].' rondas; los rivales se definirán de una en una.',
    'elimination'=>'El ganador avanza en la llave. Si el cupo no es potencia de dos, habrá pases directos.'.($t['third_place']?' Se disputará el tercer puesto.':''),
];
$units=$t['has_team']?'equipos':'participantes';
$count=$t['has_team'] ? (int)$t['team_count'] : (int)$t['member_count'];
?>
<a class="nuva-anchor tournament-back" href="<?= $workspace ? '?page=environment&id='.(int)$t['workspace_id'].'&section=tournaments' : '?page=competitions' ?>">← <?= $workspace?'Torneos del entorno':'Competiciones' ?></a>
<header class="tournament-heading"><p class="environment-eyebrow"><?= $escape($t['workspace_name']) ?> · <?= $escape(Tournament::CATEGORIES[$t['discipline_category']] ?? 'Otros') ?></p>
<div class="tournament-title-row"><div><h1><?= $escape($t['name']) ?></h1><p class="environment-muted"><?= $escape($t['description'] ?: 'Una competición de '.$t['discipline'].' en Nuva.') ?></p></div>
<?php if ($canManage): ?><a class="environment-button" href="?page=tournament-edit&amp;id=<?= (int)$t['id'] ?>">Configurar torneo</a><?php endif; ?></div>
<div class="tournament-badges"><span class="nuva-badge environment-role"><?= $escape($t['status']) ?></span><span class="nuva-badge environment-role"><?= $escape($t['format_name']) ?></span><span class="nuva-badge environment-role"><?= $escape($t['discipline']) ?></span><?php if (!$t['published']): ?><span class="nuva-badge environment-role">Privado</span><?php endif; ?></div></header>
<nav class="environment-tabs tournament-tabs" aria-label="Secciones del torneo"><?php foreach ($sections as $key=>$label): ?><a href="?page=tournament&amp;id=<?= (int)$t['id'] ?>&amp;section=<?= $escape($key) ?>" class="<?= $section===$key?'is-active':'' ?>" <?= $section===$key?'aria-current="page"':'' ?>><?= $escape($label) ?></a><?php endforeach; ?></nav>
<?php if (!empty($competition['champion'])): ?><p class="environment-notice">🏆 Campeón: <strong><?= $escape($competition['champion']) ?></strong></p><?php endif; ?>
<?php if ($section==='overview'): ?>
<div class="tournament-grid"><section class="nuva-card tournament-panel"><h2 class="section-title">El torneo de un vistazo</h2><dl class="tournament-facts">
<div><dt>Formato</dt><dd><?= $escape($t['format_name']) ?></dd></div><div><dt>Modalidad</dt><dd><?= $t['has_team']?'Por equipos':'Individual' ?></dd></div>
<div><dt>Inscritos</dt><dd><?= $count ?> de <?= (int)$t['max_participants'] ?> <?= $units ?></dd></div>
<div><dt>Organizador</dt><dd><?= $escape($t['organizer_name'] ?: 'Sin asignar') ?></dd></div>
<div><dt>Inicio</dt><dd><?= $escape($local($t['start_date'])) ?></dd></div><div><dt>Finalización</dt><dd><?= $escape($local($t['end_date'])) ?></dd></div>
</dl></section><section class="nuva-card tournament-panel"><h2 class="section-title">Cómo se juega</h2><p class="environment-muted"><?= $escape($formatExplanations[$t['format_code']] ?? 'Formato de competición.') ?></p>
<p class="environment-muted">Marcador: <?= $escape($t['score_label']) ?>. <?= $t['score_order']==='lower'?'Gana el valor menor.':'Gana el valor mayor.' ?> <?= $t['allow_draws'] && $t['format_code']!=='elimination'?'Se admiten empates.':'Cada encuentro necesita un ganador.' ?></p>
<?php if ($t['format_code']!=='elimination'): ?><p class="environment-muted">Puntos por victoria / empate / derrota: <?= $escape($t['points_win'].' / '.$t['points_draw'].' / '.$t['points_loss']) ?>.</p><?php endif; ?>
<?php if ($t['has_team']): ?><p class="environment-muted">Cada equipo admite hasta <?= (int)$t['team_size'] ?> integrantes.</p><?php endif; ?>
<a class="nuva-anchor" href="?page=tournament&amp;id=<?= (int)$t['id'] ?>&amp;section=participants">Ver <?= $units ?> →</a></section></div>
<section class="nuva-card tournament-panel tournament-period"><div><h2 class="section-title">Duración del torneo</h2><p class="environment-muted"><?= $escape($local($t['start_date'],'d/m/Y')) ?> — <?= $escape($local($t['end_date'],'d/m/Y')) ?></p></div>
<a class="nuva-anchor" href="?page=tournament&amp;id=<?= (int)$t['id'] ?>&amp;section=calendar">Abrir calendario →</a></section>
<?php elseif ($section==='participants'): ?>
<?php if ($started): ?><p class="environment-notice">Las inscripciones están cerradas porque ya se generaron los encuentros.</p><?php endif; ?>
<div class="tournament-grid"><section class="nuva-card tournament-panel"><h2 class="section-title"><?= $t['has_team']?'Equipos':'Participantes' ?> · <?= $count ?>/<?= (int)$t['max_participants'] ?></h2>
<?php if ($t['has_team']): ?>
<?php if (!$teams): ?><p class="environment-muted">Todavía no hay equipos.</p><?php endif; ?>
<?php foreach ($teams as $team): ?><div class="tournament-roster"><strong><?= $escape($team['name']) ?></strong><span class="environment-muted"><?= (int)$team['member_count'] ?>/<?= (int)$t['team_size'] ?> integrantes</span>
<?php foreach ($participants as $person): ?><?php if ((int)$person['team_id']===(int)$team['id']): ?><span><?= $escape($person['name']) ?></span><?php endif; ?><?php endforeach; ?></div><?php endforeach; ?>
<?php else: ?><?php if (!$participants): ?><p class="environment-muted">Todavía no hay participantes inscritos.</p><?php endif; ?>
<?php foreach ($participants as $person): ?><div class="tournament-roster"><strong><?= $escape($person['name']) ?></strong></div><?php endforeach; ?><?php endif; ?></section>
<?php if ($canManage && !$started): ?><section class="nuva-card tournament-panel"><h2 class="section-title">Preparar inscripciones</h2>
<?php if ($t['has_team']): ?><form method="post" class="environment-form tournament-small-form"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="team">
<label class="environment-field">Nuevo equipo<input name="team_name" required maxlength="100" placeholder="Nombre del equipo"></label><button class="environment-button" type="submit">Crear equipo</button></form><?php endif; ?>
<form method="post" class="environment-form tournament-small-form"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="enroll">
<label class="environment-field">Miembro del entorno<select name="member_id" required><option value="">Selecciona una persona</option><?php foreach ($eligibleMembers as $person): ?>
<?php if (!in_array((int)$person['id'],array_map('intval',array_column($participants,'user_id')),true)): ?><option value="<?= (int)$person['id'] ?>"><?= $escape($person['name']) ?></option><?php endif; ?><?php endforeach; ?></select></label>
<?php if ($t['has_team']): ?><label class="environment-field">Equipo<select name="team_id" required><option value="">Selecciona un equipo</option><?php foreach ($teams as $team): ?><option value="<?= (int)$team['id'] ?>" <?= (int)$team['member_count'] >= (int)$t['team_size'] ? 'disabled':'' ?>><?= $escape($team['name']) ?> · <?= (int)$team['member_count'] ?>/<?= (int)$t['team_size'] ?></option><?php endforeach; ?></select></label><?php endif; ?>
<button class="nuva-btn" type="submit">Inscribir miembro</button></form><p class="environment-muted">Solo pueden inscribirse personas que ya pertenecen al entorno.</p></section><?php endif; ?></div>
<?php elseif ($section==='calendar'): ?>
<section class="nuva-card tournament-panel"><div class="tournament-title-row"><div><h2 class="section-title">Calendario del torneo</h2><p class="environment-muted">Se marca el período de <?= $escape($local($t['start_date'],'d/m/Y')) ?> a <?= $escape($local($t['end_date'],'d/m/Y')) ?>.</p></div><button type="button" class="environment-button" data-calendar-download>Agregar a mi calendario</button></div>
<div class="tournament-calendar" data-calendar data-id="<?= (int)$t['id'] ?>" data-title="<?= $escape($t['name']) ?>" data-start="<?= $escape($t['start_date']) ?>" data-end="<?= $escape($t['end_date']) ?>" data-start-day="<?= $escape($local($t['start_date'],'Y-m-d')) ?>" data-end-day="<?= $escape($local($t['end_date'],'Y-m-d')) ?>">
<div class="tournament-calendar-nav"><button type="button" class="environment-button" data-calendar-prev aria-label="Mes anterior">←</button><strong data-calendar-month></strong><button type="button" class="environment-button" data-calendar-next aria-label="Mes siguiente">→</button></div>
<div class="tournament-calendar-days" data-calendar-days></div></div><p class="environment-muted">Las fechas de los encuentros las establece el organizador dentro de este período.</p></section>
<?php require __DIR__.'/schedule.php'; ?>
<?php elseif (in_array($section,['matches','standings','results'],true)): require __DIR__.'/competition.php'; endif; ?>
