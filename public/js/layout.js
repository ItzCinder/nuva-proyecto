document.addEventListener("DOMContentLoaded", () => {
    if (window.lucide && typeof window.lucide.createIcons === "function") {
        window.lucide.createIcons();
    }

    const currentPage = new URLSearchParams(window.location.search).get("page") || "index";

    document.querySelectorAll(".tab-bar__link").forEach((link) => {
        const href = link.getAttribute("href");
        const linkPage = href
            ? new URL(href, window.location.origin).searchParams.get("page") || "index"
            : "";

        link.classList.toggle("is-active", linkPage === currentPage);
    });
});