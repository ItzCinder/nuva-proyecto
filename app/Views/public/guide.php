<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="/public/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/global.css?v=3">
    <link rel="stylesheet" href="/public/css/components/header.css?v=4">
    <link rel="stylesheet" href="/public/css/components/tabbar.css?v=4">
    <link rel="stylesheet" href="/public/css/guide.css?v=2">
    <script src="/public/js/layout.js" defer></script>
    <title>Nuva - Guía de uso</title>
</head>
<body>
    <header class="header"><?php require __DIR__ . '/../components/header.php'; ?></header>
    <main class="main guide-main">
        <a class="nuva-anchor guide-back" href="?page=account">← Volver al inicio</a>
        <section class="guide-hero" aria-labelledby="guide-title">
            <span class="guide-eyebrow">GUÍA DE NUVA</span>
            <h1 id="guide-title">De una comunidad a su primer torneo</h1>
            <p>Esta guía explica lo que puedes hacer hoy en Nuva: crear un entorno, organizar a sus miembros, configurar torneos y consultarlos. Incluye encuentros, resultados y clasificaciones.</p>
            <div class="guide-actions"><a class="nuva-btn" href="?page=environment-create">Crear un entorno</a><a class="guide-secondary" href="?page=competitions-all">Explorar torneos públicos</a></div>
        </section>

        <nav class="guide-toc nuva-card" aria-label="Secciones de la guía">
            <strong>En esta guía</strong>
            <div><a href="#primeros-pasos">Primeros pasos</a><a href="#entornos">Entornos y roles</a><a href="#torneos">Torneos</a><a href="#formatos">Formatos</a><a href="#visibilidad">Acceso y calendario</a><a href="#preguntas">Preguntas frecuentes</a></div>
        </nav>

        <section class="guide-section" id="primeros-pasos" aria-labelledby="steps-title">
            <div class="guide-section__heading"><span>01</span><div><h2 id="steps-title">Primeros pasos</h2><p>Un entorno es la base de cualquier torneo.</p></div></div>
            <ol class="guide-steps">
                <li class="nuva-card"><strong>Entra con Google</strong><p>La cuenta te permite crear entornos, aceptar invitaciones y participar en los torneos a los que te inscriban.</p></li>
                <li class="nuva-card"><strong>Crea tu entorno</strong><p>Indica un nombre y, si quieres, una descripción. Quedas como dueño y puedes invitar a otras personas.</p></li>
                <li class="nuva-card"><strong>Invita y asigna roles</strong><p>Comparte el código o enlace de invitación. Cada persona acepta su ingreso y luego puedes darle el rol que corresponda.</p></li>
                <li class="nuva-card"><strong>Prepara el torneo</strong><p>Elige disciplina, formato, modalidad, cupo y fechas. Asígnale un organizador y decide si será público.</p></li>
            </ol>
            <div class="guide-inline-actions"><a href="?page=my-environments">Ver mis entornos →</a><a href="?page=explore#invitation">Tengo una invitación →</a></div>
        </section>

        <section class="guide-section" id="entornos" aria-labelledby="environment-title">
            <div class="guide-section__heading"><span>02</span><div><h2 id="environment-title">Entornos y roles</h2><p>Los permisos dependen del rol que tienes en cada entorno.</p></div></div>
            <div class="guide-grid guide-grid--roles">
                <article class="nuva-card"><h3>Dueño</h3><p>Crea el entorno y administra su información, miembros, invitaciones, módulos y torneos. Puede designar administradores.</p></article>
                <article class="nuva-card"><h3>Administrador General</h3><p>Ayuda con la gestión del entorno y de todos sus torneos. Puede asignar organizadores y participantes.</p></article>
                <article class="nuva-card"><h3>Organizador</h3><p>Gestiona los torneos que le asignaron e inscribe miembros del entorno en ellos.</p></article>
                <article class="nuva-card"><h3>Participante</h3><p>Ve los torneos donde está inscrito. También puede explorar los que estén publicados.</p></article>
            </div>
            <p class="guide-note"><i data-lucide="info" aria-hidden="true"></i> Entrar a un entorno no te inscribe automáticamente en sus torneos. Las invitaciones incorporan al entorno como participante; un administrador puede cambiar el rol después.</p>
        </section>

        <section class="guide-section" id="torneos" aria-labelledby="tournament-title">
            <div class="guide-section__heading"><span>03</span><div><h2 id="tournament-title">Crear y configurar torneos</h2><p>El dueño o un administrador los crea desde un entorno.</p></div></div>
            <div class="guide-panel nuva-card">
                <h3>Datos que eliges al crear uno</h3>
                <ul>
                    <li><strong>Disciplina:</strong> selecciona una opción frecuente de deporte tradicional, mental o eSports. Si no aparece, escribe otra; se agrupa bajo «Otros» en los filtros.</li>
                    <li><strong>Formato:</strong> Liga, Sistema Suizo o Eliminación Directa, según los módulos activos del entorno.</li>
                    <li><strong>Modalidad y cupo:</strong> competición individual o por equipos; en equipos también defines integrantes por plantilla.</li>
                    <li><strong>Fechas y visibilidad:</strong> inicio y fin del torneo, estado y publicación para visitantes.</li>
                    <li><strong>Organizador:</strong> una persona que ya tenga el rol Organizador en ese entorno.</li>
                </ul>
                <p>El dueño, el administrador y el organizador asignado pueden inscribir miembros del entorno. La modalidad y el formato se mantienen una vez que existen inscripciones.</p>
            </div>
            <div class="guide-inline-actions"><a href="?page=my-environments">Abrir mis entornos →</a><a href="?page=my-tournaments">Ver mis torneos →</a></div>
        </section>

        <section class="guide-section" id="formatos" aria-labelledby="formats-title">
            <div class="guide-section__heading"><span>04</span><div><h2 id="formats-title">Formatos de competición</h2><p>La configuración y la vista interior cambian según el formato elegido.</p></div></div>
            <div class="guide-grid guide-grid--formats">
                <article class="nuva-card"><i data-lucide="list-ordered" aria-hidden="true"></i><h3>Liga</h3><p>Configura una o dos vueltas. Genera jornadas de todos contra todos y actualiza la tabla al guardar resultados.</p></article>
                <article class="nuva-card"><i data-lucide="git-merge" aria-hidden="true"></i><h3>Sistema Suizo</h3><p>Indica entre 2 y 12 rondas. Genera cada ronda según la clasificación, evitando repetir rivales.</p></article>
                <article class="nuva-card"><i data-lucide="workflow" aria-hidden="true"></i><h3>Eliminación Directa</h3><p>Puede incluir partido por el tercer puesto. Los ganadores avanzan por la llave hasta la final.</p></article>
            </div>
            <p class="guide-note"><i data-lucide="clock-3" aria-hidden="true"></i> Desde Encuentros puedes generar cruces, programar fechas, registrar resultados y cerrar rondas. Las clasificaciones se recalculan al corregir resultados. Las reglas de marcador y puntos se adaptan a cada torneo.</p>
        </section>

        <section class="guide-section" id="visibilidad" aria-labelledby="visibility-title">
            <div class="guide-section__heading"><span>05</span><div><h2 id="visibility-title">Visibilidad y calendario</h2><p>Elige quién puede encontrar cada torneo.</p></div></div>
            <div class="guide-grid">
                <article class="nuva-card"><h3>Torneo publicado</h3><p>Aparece en Explorar, en la pestaña Competiciones. Cualquier visitante puede abrir su información, aunque no pertenezca al entorno.</p></article>
                <article class="nuva-card"><h3>Torneo privado</h3><p>Lo ven el dueño, los administradores, el organizador asignado y los participantes inscritos.</p></article>
                <article class="nuva-card"><h3>Calendario</h3><p>La vista del torneo marca su período y permite descargar un archivo <code>.ics</code> para añadirlo a una aplicación de calendario.</p></article>
            </div>
        </section>

        <section class="guide-section" id="preguntas" aria-labelledby="faq-title">
            <div class="guide-section__heading"><span>06</span><div><h2 id="faq-title">Preguntas frecuentes</h2><p>Respuestas rápidas para comenzar.</p></div></div>
            <div class="guide-faq">
                <details class="nuva-card"><summary>¿Puedo crear un entorno sin pagar?</summary><p>Sí. Nuva no tiene pagos, planes ni suscripciones integrados.</p></details>
                <details class="nuva-card"><summary>¿Por qué no veo un torneo privado?</summary><p>Debes pertenecer al entorno y tener acceso por tu rol o estar inscrito en ese torneo. Pide al responsable que revise tu inscripción.</p></details>
                <details class="nuva-card"><summary>¿Qué hago si mi disciplina no está en la lista?</summary><p>Selecciona «Otra disciplina», escribe su nombre y el torneo aparecerá en la categoría «Otros».</p></details>
                <details class="nuva-card"><summary>¿Cómo entro a un entorno con un código?</summary><p>Abre Explorar, busca «Unirte por código», revisa el entorno y acepta la invitación.</p></details>
            </div>
            <div class="guide-end"><strong>¿Listo para empezar?</strong><p>Puedes crear tu entorno o explorar un torneo público para ver cómo se organiza.</p><div class="guide-actions"><a class="nuva-btn" href="?page=environment-create">Crear entorno</a><a class="guide-secondary" href="?page=competitions-all">Ver torneos</a></div></div>
        </section>
    </main>
    <nav class="tab-bar"><?php require __DIR__ . '/../components/tabbar.php'; ?></nav>
</body>
</html>
