function createNuvaIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
}

// Desplazar solo el contenido: el encabezado y el menú deben quedarse visibles.
function alignContentAnchor() {
    const targetId = window.location.hash.slice(1);
    const target = document.getElementById(targetId);
    const main = document.querySelector('.main');
    if (!target || !main || !main.contains(target)) {
        return;
    }

    // Un enlace de ayuda puede apuntar a un apartado plegado de la guía.
    const chapter = target.closest('details');
    if (chapter) {
        chapter.open = true;
    }

    window.scrollTo(0, 0);
    const targetTop = target.getBoundingClientRect().top;
    const mainTop = main.getBoundingClientRect().top;
    main.scrollTo({top: main.scrollTop + targetTop - mainTop - 16});
}

function alignAnchorAfterLayout() {
    window.requestAnimationFrame(alignContentAnchor);
}

document.addEventListener('DOMContentLoaded', createNuvaIcons);
window.addEventListener('load', alignAnchorAfterLayout);
window.addEventListener('hashchange', alignAnchorAfterLayout);
