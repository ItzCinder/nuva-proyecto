document.addEventListener("DOMContentLoaded", () => {
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
            })
            .catch((error) => console.error(error));
    };
    

});