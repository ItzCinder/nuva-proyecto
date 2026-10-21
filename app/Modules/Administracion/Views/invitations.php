<div class="environment-heading">
    <div>
        <h2 class="section-title">Invitaciones al entorno</h2>
        <p class="environment-muted">Quien reciba la invitación debe iniciar sesión y aceptarla para unirse como participante.</p>
    </div>
    <form method="post">
        <input type="hidden" name="action" value="invite">
        <input type="hidden" name="csrf_token" value="<?= nuva_escape($_SESSION['csrf_token']) ?>">
        <button class="nuva-btn" type="submit">Generar invitación</button>
    </form>
</div>
<?php if ($newCode !== ''): ?>
<section class="nuva-card environment-panel environment-form environment-invitation-result">
    <h3 class="section-title">Tu invitación está lista</h3>
    <p class="environment-muted">Comparte este código de 8 caracteres o copia el enlace. El código se muestra una sola vez.</p>
    <label class="environment-field" for="new-invitation-code">Código<input id="new-invitation-code" readonly value="<?= nuva_escape($newCode) ?>">
    </label>
    <label class="environment-field" for="new-invitation-link">Enlace<input id="new-invitation-link" readonly data-invitation-link value="<?= nuva_escape('?page=environment-invitation&code=' . $newCode) ?>">
    </label>
    <div class="environment-actions">
        <button type="button" class="environment-button" data-copy-invitation>Copiar enlace</button>
        <a class="nuva-anchor" href="<?= nuva_escape('?page=environment-invitation&code=' . $newCode) ?>">Revisar invitación</a>
        <span data-copy-status role="status">
        </span>
    </div>
</section>
<script src="/public/modules/Administracion/js/environments.js" defer>
</script>
<?php endif; ?>
<div class="environment-members">
    <?php if (!$invitations): ?>
    <div class="collection__empty nuva-card">
        <h3>No hay invitaciones</h3>
        <p>Crea una invitación para que otras personas se unan al entorno.</p>
    </div>
    <?php endif; ?>
    <?php foreach ($invitations as $invitation): ?>
    <article class="nuva-card environment-member">
        <div>
            <h3>Invitación #<?= (int) $invitation['id'] ?></h3>
            <p class="environment-muted">Creada por <?= nuva_escape($invitation['creator_name']) ?> · <?= nuva_escape(nuva_local_date($invitation['created_at'])) ?></p>
            <span class="nuva-badge environment-role"><?= $invitation['revoked_at'] ? 'Revocada' : 'Activa' ?></span>
        </div>
        <?php if (!$invitation['revoked_at']): ?>
        <form method="post">
            <input type="hidden" name="action" value="revoke">
            <input type="hidden" name="invitation_id" value="<?= (int) $invitation['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= nuva_escape($_SESSION['csrf_token']) ?>">
            <button class="environment-button environment-button--danger" type="submit">Revocar invitación</button>
        </form>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
</div>
