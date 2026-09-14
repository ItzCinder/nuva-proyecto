<?php
// Aseguramos que $page exista
$pageParam = $_GET['page'] ?? 'index';
$activePage = is_string($pageParam) ? $pageParam : 'index';
?>
<nav class="tab-bar">
    <ul class="tab-bar__list">
        <li class="tab-bar__item">
            <!-- Marcamos como activo si es 'index' o 'account' -->
            <a class="tab-bar__link <?= in_array($activePage, ['index', 'account']) ? 'is-active' : '' ?>" href="?page=account">
                <i class="tab-bar__icon" data-lucide="house"></i>
                <span class="tab-bar__text">Home</span>
            </a>
        </li>

        <li class="tab-bar__item">
            <a class="tab-bar__link <?= $activePage === 'competitions' ? 'is-active' : '' ?>" href="?page=competitions">
                <i class="tab-bar__icon" data-lucide="trophy"></i>
                <span class="tab-bar__text">Competiciones</span>
            </a>
        </li>

        <li class="tab-bar__item">
            <a class="tab-bar__link <?= $activePage === 'profile' ? 'is-active' : '' ?>" href="?page=profile">
                <i class="tab-bar__icon" data-lucide="user"></i>
                <span class="tab-bar__text">Cuenta</span>
            </a>
        </li>
    </ul>
</nav>