<?php if ($page['pages'] < 2) {
    return;
} ?>
<nav class="statistics-pagination" aria-label="Páginas de resultados">
    <?php if ($page['current'] > 1): ?>
    <a class="environment-button" href="<?= nuva_escape(nuva_statistics_link($params, ['p' => $page['current'] - 1])) ?>#statistics-browser">← Anterior</a>
    <?php endif; ?>
    <span>Página <?= $page['current'] ?> de <?= $page['pages'] ?> · <?= $page['total'] ?> registros</span>
    <?php if ($page['current'] < $page['pages']): ?>
    <a class="environment-button" href="<?= nuva_escape(nuva_statistics_link($params, ['p' => $page['current'] + 1])) ?>#statistics-browser">Siguiente →</a>
    <?php endif; ?>
</nav>
