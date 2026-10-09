<nav class="collection-tabs" aria-label="Mis listados">
    <a href="?page=profile&amp;collection=tournaments#collections" class="collection-tabs__link <?= $collectionType === 'tournaments' ? 'is-active' : '' ?>" <?= $collectionType === 'tournaments' ? 'aria-current="page"' : '' ?>>
        <i data-lucide="trophy" aria-hidden="true"></i> Mis torneos
    </a>
    <a href="?page=profile&amp;collection=environments#collections" class="collection-tabs__link <?= $collectionType === 'environments' ? 'is-active' : '' ?>" <?= $collectionType === 'environments' ? 'aria-current="page"' : '' ?>>
        <i data-lucide="folders" aria-hidden="true"></i> Mis entornos
    </a>
</nav>
