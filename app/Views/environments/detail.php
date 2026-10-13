<?php
use app\Models\Role;
$id = (int) $workspace['id'];
$role = $workspace['role_name'];
$environmentUrl = '?page=environment&id=' . $id;
?>
<a class="nuva-anchor collection__back" href="?page=my-environments">← Mis entornos</a>
<header class="environment-heading environment-heading--detail">
    <div class="environment-identity">
        <span class="environment-mark environment-mark--large"><i data-lucide="folders" aria-hidden="true"></i></span>
        <div><p class="environment-eyebrow">ENTORNO #<?= $id ?></p><h1><?= $escape($workspace['name']) ?></h1><p class="environment-muted"><?= $escape($workspace['description'] ?: 'Tu comunidad de competiciones en Nuva.') ?></p></div>
    </div>
    <span class="nuva-badge environment-role"><?= $escape($role) ?></span>
</header>
<nav class="environment-tabs" aria-label="Secciones del entorno">
    <?php foreach ($sections as $key => [$label, $permission]): ?>
        <?php if (Role::can($role, $permission)): ?>
            <a class="<?= $section === $key ? 'is-active' : '' ?>" href="<?= $escape($environmentUrl . '&section=' . $key) ?>" <?= $section === $key ? 'aria-current="page"' : '' ?>><?= $escape($label) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
<?php if ($section === 'overview'): ?>
    <div class="environment-columns">
        <section class="nuva-card environment-panel">
            <h2 class="section-title">Tu lugar en el entorno</h2>
            <p><?= $escape(Role::descriptions()[$role] ?? 'Consulta la información autorizada de este entorno.') ?></p>
            <p class="environment-muted">Dueño: <?= $escape($workspace['owner_name']) ?></p>
            <p class="environment-muted">Creado el <?= $escape($localDate($workspace['created_at'])) ?></p>
            <a class="nuva-anchor" href="<?= $escape($environmentUrl . '&section=tournaments') ?>"><?= $role === Role::ORGANIZER ? 'Ver torneos asignados' : ($role === Role::PARTICIPANT ? 'Ver mis inscripciones' : 'Ver torneos del entorno') ?> →</a>
        </section>
        <section class="nuva-card environment-panel">
            <h2 class="section-title"><?= Role::can($role, 'members.manage') ? 'Prepara tu comunidad' : 'Un espacio para competir' ?></h2>
            <?php if (Role::can($role, 'members.manage')): ?>
                <p class="environment-muted">Comparte una invitación para que cada persona decida incorporarse. Después podrás asignar los roles que necesites.</p>
                <div class="environment-actions"><a class="nuva-btn" href="<?= $escape($environmentUrl . '&section=invitations') ?>">Invitar miembros</a><a class="nuva-anchor" href="<?= $escape($environmentUrl . '&section=members') ?>">Gestionar roles</a></div>
            <?php else: ?>
                <p class="environment-muted">Pertenecer al entorno y estar inscrito en un torneo son pasos distintos. La organización gestiona las inscripciones.</p>
                <p class="environment-muted">Tus permisos se aplican únicamente a este entorno y a los torneos que te correspondan.</p>
            <?php endif; ?>
        </section>
    </div>
    <?php if (Role::can($role, 'environment.leave')): ?>
        <details class="nuva-card environment-panel environment-leave">
            <summary>Abandonar este entorno</summary>
            <p class="environment-muted">Perderás acceso al entorno. La organización ya no podrá gestionarte como miembro. Tu cuenta y los registros históricos se conservan.</p>
            <form method="post" class="environment-form">
                <input type="hidden" name="action" value="leave"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                <label class="environment-check"><input type="checkbox" name="confirm" value="1" required> Confirmo que quiero abandonar <?= $escape($workspace['name']) ?>.</label>
                <button class="environment-button environment-button--danger" type="submit">Abandonar entorno</button>
            </form>
        </details>
    <?php endif; ?>
<?php elseif ($section === 'tournaments'): ?>
    <section class="nuva-card environment-panel">
        <h2 class="section-title"><?= $role === Role::ORGANIZER ? 'Mis torneos asignados' : ($role === Role::PARTICIPANT ? 'Mis torneos inscritos' : 'Torneos del entorno') ?></h2>
        <?php if (in_array($role, [Role::OWNER, Role::ADMIN], true)): ?><p><a class="nuva-btn" href="?page=tournament-create&amp;workspace_id=<?= $id ?>">Crear torneo</a></p><?php endif; ?>
        <?php if (!$tournaments): ?><p class="environment-muted">No hay torneos disponibles para tu rol en este entorno.</p><?php endif; ?>
        <div class="environment-grid">
            <?php foreach ($tournaments as $tournament): ?>
                <article class="nuva-card environment-panel"><span class="nuva-badge environment-role"><?= $escape($tournament['format_name']) ?></span><h3><?= $escape($tournament['name']) ?></h3>
                    <p class="environment-muted"><?= $escape($tournament['discipline']) ?> · <?= $escape($tournament['status']) ?></p>
                    <p class="environment-muted"><?= $escape($localDate($tournament['start_date'])) ?> — <?= $escape($localDate($tournament['end_date'])) ?></p>
                    <a class="nuva-anchor" href="?page=tournament&amp;id=<?= (int)$tournament['id'] ?>">Abrir torneo →</a></article>
            <?php endforeach; ?>
        </div>
    </section>
<?php elseif ($section === 'settings'): ?>
    <form method="post" class="nuva-card environment-panel environment-form">
        <h2 class="section-title">Información del entorno</h2>
        <input type="hidden" name="action" value="update"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
        <?php require __DIR__ . '/information-fields.php'; ?>
        <button class="nuva-btn" type="submit">Guardar información</button>
    </form>
<?php elseif ($section === 'members'): ?>
    <?php require __DIR__ . '/members.php'; ?>
<?php elseif ($section === 'modules'): ?>
    <form method="post" class="nuva-card environment-panel environment-form">
        <h2 class="section-title">Módulos de competición</h2>
        <p class="environment-muted">Selecciona los formatos disponibles para nuevas competiciones. Los torneos y resultados existentes se conservan.</p>
        <input type="hidden" name="action" value="modules"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
        <?php foreach ($modules as $module): ?>
            <label class="environment-module nuva-card"><input type="checkbox" name="modules[]" value="<?= (int) $module['id'] ?>" <?= (int) $module['is_active'] ? 'checked' : '' ?>><span><strong><?= $escape($module['name']) ?></strong><span class="environment-muted"><?= $escape($module['description']) ?></span></span></label>
        <?php endforeach; ?>
        <button class="nuva-btn" type="submit">Guardar módulos</button>
    </form>
<?php elseif ($section === 'invitations'): ?>
    <?php require __DIR__ . '/invitations.php'; ?>
<?php elseif ($section === 'audit'): ?>
    <?php require __DIR__ . '/audit.php'; ?>
<?php endif; ?>
