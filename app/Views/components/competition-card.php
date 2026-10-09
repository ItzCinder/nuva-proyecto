<article class="comp__card nuva-card">
    <div class="comp__card-logo">
        <i class="comp__card-icon" data-lucide="<?= $escape($item['icon']) ?>" aria-hidden="true"></i>
    </div>
    <div class="comp__card-content">
        <div class="comp__card-header">
            <span class="comp__card-sport-tag nuva-badge"><?= $escape($item['sport']) ?></span>
            <span class="comp__card-status comp__card-status-dot"><?= $escape($item['status']) ?></span>
        </div>
        <h3 class="comp__card-title"><?= $escape($item['title']) ?></h3>
        <div class="comp__card-meta">
            <span class="comp__card-item"><i class="comp__card-icon" data-lucide="users" aria-hidden="true"></i><?= (int) $item['players'] ?> Jugadores</span>
            <span class="comp__card-item"><i class="comp__card-icon" data-lucide="trophy" aria-hidden="true"></i><?= $escape($item['format']) ?></span>
        </div>
        <div class="comp__card-meta">
            <span class="comp__card-item"><i class="comp__card-icon" data-lucide="calendar-days" aria-hidden="true"></i><?= $escape($item['dates']) ?></span>
        </div>
    </div>
</article>
