// Al cerrar un apartado largo, el navegador puede dejar el siguiente fuera de vista.
function positionOpenGuideChapter() {
    const chapter = document.querySelector('.guide-chapter[open]');
    const main = document.querySelector('.guide-main');
    if (!chapter || !main) {
        return;
    }

    const chapterTop = chapter.getBoundingClientRect().top;
    const mainTop = main.getBoundingClientRect().top;
    main.scrollTo({top: main.scrollTop + chapterTop - mainTop - 16, behavior: 'instant'});
}

function prepareGuideChapter(event) {
    const chapter = event.currentTarget.parentElement;
    if (!chapter.open) {
        // Esperar a que el navegador abra este apartado y cierre el anterior.
        window.requestAnimationFrame(positionOpenGuideChapter);
    }
}

for (const summary of document.querySelectorAll('.guide-chapter>summary')) {
    summary.addEventListener('click', prepareGuideChapter);
}
