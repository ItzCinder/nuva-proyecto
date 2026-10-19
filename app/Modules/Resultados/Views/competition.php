<?php
// Esta vista elige qué mostrar. Cada parte tiene su propio archivo.
$state = $competition;
$parts = __DIR__ . '/partials/competition';
?>
<?php if (!$state['rounds']): ?>
<section class="nuva-card tournament-panel">
    <h2 class="section-title">Preparar los enfrentamientos</h2>
    <p class="environment-muted">Todavía no se generaron encuentros. Primero completa las inscripciones y revisa las reglas.</p>
    <?php if ($canManage): ?>
    <form method="post" data-confirm="Se sorteará el orden inicial y se cerrarán las inscripciones y las reglas. ¿Generar los encuentros?">
        <?php nuva_csrf_field(); ?>
        <input type="hidden" name="action" value="generate">
        <button class="nuva-btn">Generar enfrentamientos</button>
    </form>
    <?php endif; ?>
</section>
<?php elseif ($section === 'standings' && $tournament['format_code'] !== 'elimination'): ?>
    <?php require $parts . '/standings.php'; ?>
<?php else: ?>
    <?php require $parts . '/rounds.php'; ?>
<?php endif; ?>
