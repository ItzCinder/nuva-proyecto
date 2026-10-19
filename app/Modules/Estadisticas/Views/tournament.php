<?php
use App\Modules\Estadisticas\Services\Physics;
$config = $configuration['options'];
$activeTemplates = [];
$activeDefinitions = [];
if ($manager) {
    foreach (Physics::TEMPLATES as $code => $info) {
        if (!empty($config['physics:' . $code]['load']) && !empty($options['physics:' . $code]['enabled'])) {
            $activeTemplates[] = $code;
        }
    }

    foreach ($definitions as $definition) {
        $optionCode = 'manual:' . $definition['id'];
        if (!empty($config[$optionCode]['load']) && !empty($options[$optionCode]['enabled'])) {
            $activeDefinitions[] = $definition;
        }
    }
}
$statisticsTask = 'browse';
$entryType = $activeTemplates ? 'trial' : 'manual';
if ($manager) {
    $requestedTask = $_GET['task'] ?? '';
    if (in_array($requestedTask, ['register', 'configure'], true)) {
        $statisticsTask = $requestedTask;
    }
    if (($_GET['entry'] ?? '') === 'manual') {
        $entryType = 'manual';
    }
    $postedAction = $_POST['action'] ?? '';
    if ($edit || $manualEdit || in_array($postedAction, ['stat_trial', 'stat_manual'], true)) {
        $statisticsTask = 'register';
        $entryType = ($manualEdit || $postedAction === 'stat_manual') ? 'manual' : 'trial';
    } elseif ($postedAction === 'stat_configuration') {
        $statisticsTask = 'configure';
    }
}
?>
<?php if ($manager): ?>
<nav class="statistics-view-nav" aria-label="Acciones de estadísticas">
    <?php foreach (['browse' => 'Consultar', 'register' => 'Registrar datos', 'configure' => 'Configurar'] as $task => $label): ?>
    <a href="?page=tournament-statistics&amp;id=<?= (int)$tournament['id'] ?>&amp;task=<?= $task ?>" <?= $statisticsTask === $task ? 'aria-current="page"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
<?php if ($statisticsTask === 'configure'): ?>
    <?php require __DIR__ . '/configuration.php'; ?>
<?php elseif ($statisticsTask === 'register'): ?>
    <section class="statistics-entry-choice" aria-label="Tipo de registro">
        <h2 class="section-title">¿Qué vas a registrar?</h2>
        <p class="environment-muted">Prueba física: escribe medidas y Nuva calcula. Estadística manual: escribe un valor, como asistencias o faltas.</p>
        <div class="environment-actions">
            <?php if ($activeTemplates): ?>
            <a class="environment-button" <?= $entryType === 'trial' ? 'aria-current="page"' : '' ?> href="?page=tournament-statistics&amp;id=<?= (int)$tournament['id'] ?>&amp;task=register&amp;entry=trial">Prueba física</a>
            <?php endif; ?>
            <?php if ($activeDefinitions): ?>
            <a class="environment-button" <?= $entryType === 'manual' ? 'aria-current="page"' : '' ?> href="?page=tournament-statistics&amp;id=<?= (int)$tournament['id'] ?>&amp;task=register&amp;entry=manual">Estadística manual</a>
            <?php endif; ?>
        </div>
    </section>
    <?php if ($entryType === 'trial' && $activeTemplates): require __DIR__ . '/trial-form.php';
    elseif ($entryType === 'manual' && $activeDefinitions): require __DIR__ . '/manual-form.php';
    else: ?>
<p class="environment-muted">Para añadir una prueba, activa una opción en el entorno y marca Permitir registrar en este torneo.</p>
<?php endif; ?>
<?php else: ?>
    <?php require __DIR__ . '/records.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/templates.php'; ?>
