<a class="nuva-anchor collection__back nuva-back" href="?page=my-environments">Mis entornos</a>
<div class="environment-heading">
    <div>
        <p class="environment-eyebrow">NUEVA COMUNIDAD</p>
        <h1>Crear entorno</h1>
        <p class="environment-muted">Crea un entorno para reunir a tu club, institución o grupo.</p>
    </div>
</div>
<div class="environment-columns">
    <form method="post" enctype="multipart/form-data" class="nuva-card environment-panel environment-form">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="csrf_token" value="<?= nuva_escape($_SESSION['csrf_token']) ?>">
        <?php require __DIR__ . '/information-fields.php'; ?>
        <div class="environment-actions">
            <button type="submit" class="nuva-btn">Crear entorno</button>
            <a class="nuva-anchor" href="?page=my-environments">Cancelar</a>
        </div>
    </form>
    <aside class="nuva-card environment-panel">
        <span class="environment-mark">
            <i data-lucide="shield-check" aria-hidden="true">
            </i>
        </span>
        <h2 class="section-title">Serás el dueño del entorno</h2>
        <p class="environment-muted">Podrás configurar el entorno, invitar miembros y asignar administradores generales.</p>
        <p class="environment-muted">Cada miembro tendrá un rol en este entorno, aunque tenga otro rol en los demás.</p>
    </aside>
</div>
