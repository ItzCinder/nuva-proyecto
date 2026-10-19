document.addEventListener("DOMContentLoaded", () => {
  const input = document.querySelector("[data-invitation-link]");
  const button = document.querySelector("[data-copy-invitation]");
  const status = document.querySelector("[data-copy-status]");
  if (!input || !button || !status) return;
  input.value = new URL(input.value, window.location.href).href;
  button.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(input.value);
      status.textContent = "Enlace copiado.";
    } catch {
      input.focus();
      input.select();
      status.textContent =
        "Copia el enlace seleccionado con el menú de tu dispositivo.";
    }
  });
});
