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

// Hauteur de la barre de navigation collante : les blocs collants
// (filtres des menus, récapitulatif de commande) se placent juste dessous.
(() => {
  const nav = document.querySelector('nav.sticky-top');
  if (!nav) return;
  const update = () => document.documentElement.style.setProperty('--vg-nav-h', nav.offsetHeight + 'px');
  update();
  window.addEventListener('resize', update);
})();
