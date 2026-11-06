// Demande de confirmation avant une action sensible (bouton portant data-confirm).
(() => {
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-confirm]').forEach((button) => {
      button.addEventListener('click', (event) => {
        const message = button.dataset.confirm;
        if (message && !window.confirm(message)) event.preventDefault();
      });
    });
  });
})();
