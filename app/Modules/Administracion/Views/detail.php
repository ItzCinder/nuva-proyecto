<?php
use app\Models\Role;

$id = (int) $workspace['id'];
$role = $workspace['role_name'];
$environmentUrl = '?page=environment&id=' . $id;
?>
<a class="nuva-anchor collection__back nuva-back" href="?page=my-environments">Mis entornos</a>
<header class="environment-heading environment-heading--detail">
    <div class="environment-identity">
        <span class="environment-mark environment-mark--large">
            <?php $pictureWorkspace = $workspace; require __DIR__ . '/picture.php'; ?>
        </span>
        <div>
            <p class="environment-eyebrow">ENTORNO #<?= $id ?></p>
            <h1><?= nuva_escape($workspace['name']) ?></h1>
            <p class="environment-muted"><?= nuva_escape($workspace['description'] ?: 'Torneos y miembros de este entorno.') ?></p>
        </div>
    </div>
    <span class="nuva-badge environment-role"><?= nuva_escape($role) ?></span>
</header>
<nav class="environment-tabs" aria-label="Secciones del entorno">
    <?php foreach ($sections as $key => [$label, $permission]): ?>
    <?php if (Role::can($role, $permission)): ?>
    <a class="<?= $section === $key ? 'is-active' : '' ?>" href="<?= nuva_escape($environmentUrl . '&section=' . $key) ?>" <?= $section === $key ? 'aria-current="page"' : '' ?>><?= nuva_escape($label) ?></a>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php if (in_array($role, [Role::OWNER,Role::ADMIN], true)): ?>
    <a href="?page=environment-statistics&amp;id=<?= $id ?>">Estadísticas y plantillas</a>
    <?php endif; ?>
</nav>
<?php if ($section === 'overview'): ?>
<div class="environment-columns">
    <section class="nuva-card environment-panel">
        <h2 class="section-title">Tu rol en el entorno</h2>
        <p><?= nuva_escape(Role::descriptions()[$role] ?? 'Puedes ver la información disponible para tu rol.') ?></p>
        <p class="environment-muted">Dueño: <?= nuva_escape($workspace['owner_name']) ?></p>
        <p class="environment-muted">Creado el <?= nuva_escape(nuva_local_date($workspace['created_at'])) ?></p>
        <a class="nuva-anchor" href="<?= nuva_escape($environmentUrl . '&section=tournaments') ?>"><?= $role === Role::ORGANIZER ? 'Ver torneos asignados' : ($role === Role::PARTICIPANT ? 'Ver mis inscripciones' : 'Ver torneos del entorno') ?> →</a>
    </section>
    <section class="nuva-card environment-panel">
        <h2 class="section-title"><?= Role::can($role, 'members.manage') ? 'Organiza tu grupo' : 'Un espacio para competir' ?></h2>
        <?php if (Role::can($role, 'members.manage')): ?>
        <p class="environment-muted">Invita a otras personas con un código o enlace. Cuando se unan, podrás cambiar sus roles.</p>
        <div class="environment-actions">
            <a class="nuva-btn" href="<?= nuva_escape($environmentUrl . '&section=invitations') ?>">Invitar miembros</a>
            <a class="nuva-anchor" href="<?= nuva_escape($environmentUrl . '&section=members') ?>">Gestionar roles</a>
        </div>
        <?php else: ?>
        <p class="environment-muted">Unirte al entorno no te inscribe en sus torneos. La organización debe inscribirte en cada uno.</p>
        <p class="environment-muted">Tu rol puede ser distinto en otros entornos.</p>
        <?php endif; ?>
    </section>
</div>
<?php if (Role::can($role, 'environment.leave')): ?>
<details class="nuva-card environment-panel environment-leave">
    <summary>Abandonar este entorno</summary>
    <p class="environment-muted">Dejarás de ser miembro y perderás el acceso al entorno. Tu cuenta y tus registros anteriores se conservarán.</p>
    <form method="post" class="environment-form">
        <input type="hidden" name="action" value="leave">
        <input type="hidden" name="csrf_token" value="<?= nuva_escape($_SESSION['csrf_token']) ?>">
        <label class="environment-check">
            <input type="checkbox" name="confirm" value="1" required> Confirmo que quiero abandonar <?= nuva_escape($workspace['name']) ?>.</label>
        <button class="environment-button environment-button--danger" type="submit">Abandonar entorno</button>
    </form>
</details>
<?php endif; ?>
<?php elseif ($section === 'tournaments'): ?>
<section class="nuva-card environment-panel">
    <h2 class="section-title"><?= $role === Role::ORGANIZER ? 'Mis torneos asignados' : ($role === Role::PARTICIPANT ? 'Mis torneos inscritos' : 'Torneos del entorno') ?></h2>
    <?php if (in_array($role, [Role::OWNER, Role::ADMIN], true)): ?>
    <p>
        <a class="nuva-btn" href="?page=tournament-create&amp;workspace_id=<?= $id ?>">Crear torneo</a>
    </p>
    <?php endif; ?>
    <?php if (!$tournaments): ?>
    <p class="environment-muted">No hay torneos disponibles para tu rol en este entorno.</p>
    <?php endif; ?>
    <div class="comp__section environment-tournaments">
        <?php foreach ($tournamentCards as $item): ?>
            <?php require nuva_view_path('components/competition-card'); ?>
        <?php endforeach; ?>
    </div>
</section>
<?php elseif ($section === 'settings'): ?>
<form method="post" enctype="multipart/form-data" class="nuva-card environment-panel environment-form">
    <h2 class="section-title">Información del entorno</h2>
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="csrf_token" value="<?= nuva_escape($_SESSION['csrf_token']) ?>">
    <?php require __DIR__ . '/information-fields.php'; ?>
    <button class="nuva-btn" type="submit">Guardar información</button>
</form>
<?php elseif ($section === 'members'): ?>
<?php require __DIR__ . '/../../Participantes/Views/workspace-members.php'; ?>
<?php elseif ($section === 'modules'): ?>
<form method="post" class="nuva-card environment-panel environment-form">
    <h2 class="section-title">Módulos de competición</h2>
    <p class="environment-muted">Elige qué formatos se podrán usar al crear torneos. Los torneos ya creados conservarán sus resultados.</p>
    <input type="hidden" name="action" value="modules">
    <input type="hidden" name="csrf_token" value="<?= nuva_escape($_SESSION['csrf_token']) ?>">
    <?php foreach ($modules as $module): ?>
    <label class="environment-module nuva-card">
        <input type="checkbox" name="modules[]" value="<?= (int) $module['id'] ?>" <?= (int) $module['is_active'] ? 'checked' : '' ?>>
        <span>
            <strong><?= nuva_escape($module['name']) ?></strong>
            <span class="environment-muted"><?= nuva_escape($module['description']) ?></span>
        </span>
    </label>
    <?php endforeach; ?>
    <button class="nuva-btn" type="submit">Guardar módulos</button>
</form>
<?php elseif ($section === 'invitations'): ?>
<?php require __DIR__ . '/invitations.php'; ?>
<?php elseif ($section === 'audit'): ?>
<?php require __DIR__ . '/../../Auditoria/Views/audit.php'; ?>
<?php endif; ?>
