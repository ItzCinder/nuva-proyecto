<header class="information-heading">
    <span class="information-eyebrow">EL EQUIPO DETRÁS DE NUVA</span>
    <h1>Somos Kervox</h1>
    <p>Creamos Nuva para organizar torneos, reunir a sus participantes y consultar los resultados.</p>
</header>
<section class="information-panel nuva-card" aria-labelledby="about-project">
    <h2 id="about-project">Sobre el proyecto</h2>
    <p>Nuva es nuestro proyecto de Bachillerato de Informática de UTU. Reúne entornos, roles, participantes, equipos, calendarios y resultados en una misma plataforma.</p>
    <p>Puedes organizar ligas, torneos de eliminación directa y de sistema suizo en distintas disciplinas. También puedes registrar estadísticas y pruebas físicas.</p>
    <a class="nuva-anchor" href="?page=guide">Conoce cómo funciona Nuva →</a>
</section>
<section aria-labelledby="about-team" class="team-section">
    <h2 id="about-team">Nuestro equipo</h2>
    <p class="information-muted">Las personas que construimos este proyecto.</p>
    <div class="team-grid">
        <?php foreach ($members as $member): ?>
        <article class="nuva-card team-card">
            <div class="team-photo">
                <img src="<?= nuva_escape($member['photo']) ?>" alt="<?= nuva_escape($member['name']) ?>" width="480" height="480" loading="lazy" decoding="async">
            </div>
            <h3><?= nuva_escape($member['name']) ?></h3>
        </article>
        <?php endforeach; ?>
    </div>
</section>
