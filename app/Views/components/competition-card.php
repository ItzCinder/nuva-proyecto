<a class="comp__card nuva-card anchor-not-style" href="?page=tournament&amp;id=<?= (int)$item['id'] ?>">
    <div class="comp__card-top">
        <span class="comp__card-logo"><i data-lucide="<?= $escape($item['icon']) ?>" aria-hidden="true"></i></span>
        <span class="comp__card-status comp__card-status-dot <?= $item['status'] === 'En curso' ? '' : ($item['status'] === 'Finalizado' ? 'comp__card-status--finished' : 'comp__card-status--planned') ?>"><?= $escape($item['status']) ?></span>
    </div>
    <div class="comp__card-main">
        <span class="comp__card-sport-tag nuva-badge"><?= $escape($item['sport']) ?></span>
        <h3 class="comp__card-title"><?= $escape($item['title']) ?></h3>
    </div>
    <div class="comp__card-details">
        <span class="comp__card-item"><i data-lucide="users" aria-hidden="true"></i><span><?= (int)$item['players'] ?> <?= $escape($item['unit']) ?></span></span>
        <span class="comp__card-item"><i data-lucide="trophy" aria-hidden="true"></i><span><?= $escape($item['format']) ?></span></span>
        <span class="comp__card-item comp__card-item--date"><i data-lucide="calendar-days" aria-hidden="true"></i><span><?= $escape($item['dates']) ?></span></span>
    </div>
</a>
