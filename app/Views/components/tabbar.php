<?php
$activePage=is_string($_GET['page'] ?? null)?$_GET['page']:'index';
$items=[
    ['index','house','Inicio',['index','account','guide']],
    ['explore','compass','Explorar',['explore','competitions','competitions-all','public-environment','environment-invitation','tournament','tournament-edit']],
    ['profile','user','Cuenta',['profile','login','my-environments','my-tournaments','environment','environment-create','tournament-create']],
    ['settings','settings-2','Ajustes',['settings','logout']],
];
?>
<div class="tab-bar__content"><ul class="tab-bar__list">
<?php foreach($items as [$target,$icon,$label,$pages]): $selected=in_array($activePage,$pages,true); ?>
<li class="tab-bar__item"><a class="tab-bar__link <?= $selected?'is-active':'' ?>" <?= $selected?'aria-current="page"':'' ?> href="?page=<?= $target ?>"><i class="tab-bar__icon" data-lucide="<?= $icon ?>" aria-hidden="true"></i><span class="tab-bar__text"><?= $label ?></span></a></li>
<?php endforeach; ?>
</ul></div>
