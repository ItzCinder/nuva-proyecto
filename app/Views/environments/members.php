<?php use app\Models\Role; ?>
<div class="environment-heading">
    <div><h2 class="section-title">Miembros y roles</h2><p class="environment-muted">Los roles se aplican solo a este entorno. Únicamente el dueño asigna y retira administradores generales.</p></div>
    <a class="nuva-btn" href="<?= $escape($environmentUrl . '&section=invitations') ?>">Invitar miembros</a>
</div>
<div class="environment-members">
    <?php foreach ($members as $member): ?>
        <?php
        $isOwner = (int) $member['id'] === (int) $workspace['owner_id'];
        $memberRole = $isOwner ? Role::OWNER : $member['role_name'];
        $canManage = !$isOwner && ($memberRole !== Role::ADMIN || Role::can($role, 'admins.manage'));
        ?>
        <article class="nuva-card environment-member">
            <div class="environment-member__identity">
                <span class="environment-avatar" aria-hidden="true"><?= $escape(mb_substr($member['name'], 0, 1)) ?></span>
                <div><h3><?= $escape($member['name']) ?><?= (int) $member['id'] === $userId ? ' (tú)' : '' ?></h3><p class="environment-muted"><?= $escape($member['email']) ?></p><span class="nuva-badge environment-role"><?= $escape($memberRole) ?></span></div>
            </div>
            <?php if ($canManage): ?>
                <form method="post" class="environment-role-form">
                    <input type="hidden" name="action" value="role"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                    <label class="environment-field" for="role-<?= (int) $member['id'] ?>">Rol de <?= $escape($member['name']) ?>
                        <select id="role-<?= (int) $member['id'] ?>" name="role">
                            <?php foreach ([Role::PARTICIPANT, Role::ORGANIZER, Role::ADMIN] as $option): ?>
                                <?php if ($option !== Role::ADMIN || Role::can($role, 'admins.manage')): ?>
                                    <option value="<?= $escape($option) ?>" <?= $memberRole === $option ? 'selected' : '' ?>><?= $escape($option) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="environment-button" type="submit">Guardar rol</button>
                </form>
                <details class="environment-remove">
                    <summary>Retirar miembro</summary>
                    <form method="post" class="environment-form">
                        <input type="hidden" name="action" value="remove"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                        <label class="environment-check"><input type="checkbox" name="confirm" value="1" required> Confirmo que quiero retirar a <?= $escape($member['name']) ?> del entorno.</label>
                        <button class="environment-button environment-button--danger" type="submit">Confirmar baja</button>
                    </form>
                </details>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<details class="nuva-card environment-panel environment-role-guide">
    <summary>Qué puede hacer cada rol</summary>
    <dl><?php foreach (Role::descriptions() as $name => $description): ?><dt><?= $escape($name) ?></dt><dd class="environment-muted"><?= $escape($description) ?></dd><?php endforeach; ?></dl>
</details>
