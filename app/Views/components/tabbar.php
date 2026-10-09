<?php
// Aseguramos que $page exista
$pageParam = $_GET['page'] ?? 'index';
$activePage = is_string($pageParam) ? $pageParam : 'index';
?>
<div class="tab-bar__content">
    <ul class="tab-bar__list">
        <li class="tab-bar__item">
            <!-- Marcamos como activo si es 'index' o 'account' -->
            <a class="tab-bar__link <?= in_array($activePage, ['index', 'account']) ? 'is-active' : '' ?>" <?= in_array($activePage, ['index', 'account']) ? 'aria-current="page"' : '' ?> href="?page=account">
                <i class="tab-bar__icon" data-lucide="house"></i>
                <span class="tab-bar__text">Home</span>
            </a>
        </li>

        <li class="tab-bar__item">
            <a class="tab-bar__link <?= in_array($activePage, ['competitions', 'competitions-all']) ? 'is-active' : '' ?>" <?= in_array($activePage, ['competitions', 'competitions-all']) ? 'aria-current="page"' : '' ?> href="?page=competitions">
                <i class="tab-bar__icon" data-lucide="trophy"></i>
                <span class="tab-bar__text">Competiciones</span>
            </a>
        </li>

        <li class="tab-bar__item">
            <a class="tab-bar__link <?= in_array($activePage, ['profile', 'login', 'my-tournaments', 'my-environments']) ? 'is-active' : '' ?>" <?= in_array($activePage, ['profile', 'login', 'my-tournaments', 'my-environments']) ? 'aria-current="page"' : '' ?> href="?page=profile">
                <i class="tab-bar__icon" data-lucide="user"></i>
                <span class="tab-bar__text">Cuenta</span>
            </a>
        </li>
    </ul>
</div>
