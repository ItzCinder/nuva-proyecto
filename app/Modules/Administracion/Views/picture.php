<?php
// La ruta guardada se escapa; las subidas propias solo admiten imágenes comprobadas.
$environmentPicture = $pictureWorkspace['picture'] ?? '';
?>
<?php if ($environmentPicture !== ''): ?>
    <img class="environment-picture" src="<?= nuva_escape($environmentPicture) ?>" alt="" loading="lazy" decoding="async">
<?php else: ?>
    <i data-lucide="folders" aria-hidden="true"></i>
<?php endif; ?>
