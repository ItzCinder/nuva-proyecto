<a class="nuva-anchor collection__back" href="?page=my-environments">← Mis entornos</a>
<?php if (!$invitation): ?>
    <section class="nuva-card environment-panel"><h1>Invitación no disponible</h1><p class="environment-muted">El código no es válido o la invitación fue revocada. Solicita una nueva a la organización.</p></section>
<?php else: ?>
    <section class="nuva-card environment-panel environment-invitation">
        <span class="environment-mark"><i data-lucide="mail-open" aria-hidden="true"></i></span>
        <p class="environment-eyebrow">TE INVITARON A UN ENTORNO</p>
        <h1><?= $escape($invitation['name']) ?></h1>
        <p class="environment-muted"><?= $escape($invitation['description']) ?></p>
        <?php if ($alreadyMember): ?>
            <p>Ya perteneces a este entorno.</p>
            <a class="nuva-btn" href="?page=environment&amp;id=<?= (int) $invitation['id'] ?>">Abrir entorno</a>
        <?php else: ?>
            <p>Al aceptar, te incorporarás como <strong>participante</strong>. Los organizadores autorizados podrán inscribirte en torneos de este entorno.</p>
            <p class="environment-muted">Esta invitación no te inscribe automáticamente en un torneo. Puedes abandonar el entorno desde su resumen.</p>
            <form method="post" class="environment-form">
                <input type="hidden" name="action" value="join">
                <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                <label class="environment-check"><input type="checkbox" name="accept" value="1" required> Reconozco la organización y acepto incorporarme al entorno.</label>
                <div class="environment-actions"><button type="submit" class="nuva-btn">Aceptar invitación</button><a class="nuva-anchor" href="?page=my-environments">Cancelar</a></div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>
