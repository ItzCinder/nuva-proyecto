document.addEventListener("DOMContentLoaded", () => {
    // Funcion que indica si el enlace esta activo segun si el URL actual es el mismo que en el href del enlace
    const setActiveLink = (containerElement) => {
        const currentPath = window.location.pathname.split("/").pop() || "index.html";

        const links = containerElement.querySelectorAll(".tab-bar__link");

        links.forEach((link) => {
            const href = link.getAttribute("href");
            if (href && href.includes(currentPath)) {
                link.classList.add("is-active");
            } else {
                link.classList.remove("is-active");
            }
        });
    };


    const loadComponent = (id, file) => {
        const container = document.getElementById(id);
        if (!container) return;

        fetch(file)
            .then((response) => {
                if (!response.ok) throw new Error(`Error al cargar, ${file}`);
                return response.text();
            })
            .then((data) => {
                container.innerHTML = data;

                // Aplicar la función de enlace activo
                setActiveLink(container);
            })
            .catch((error) => console.error(error));            
    };

    loadComponent("header", "/public/components/header.html");
    loadComponent("tab-bar", "/public/components/tabbar.html");
});