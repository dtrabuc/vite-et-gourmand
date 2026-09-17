/* Les opérations d'administration sont rendues par PHP et protégées par session.
 * Ce fichier est réservé aux améliorations d'interface sans règle métier. */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((button) => {
    button.addEventListener('click', (event) => {
      if (!window.confirm(button.dataset.confirm)) event.preventDefault();
    });
  });
});
