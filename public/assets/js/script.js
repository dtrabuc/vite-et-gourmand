(() => {
  const euro = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
  }[character]));

  const renderMenus = (menus) => menus.map((menu) => `
    <div class="col-md-6 col-lg-4" data-menu-card>
      <article class="card h-100 border-0 shadow-sm"><div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between gap-2"><span class="badge bg-primary-subtle text-primary">${escapeHtml(menu.theme)}</span><strong>${euro.format(Number(menu.base_price))}</strong></div>
        <h2 class="h4 text-primary mt-3">${escapeHtml(menu.title)}</h2>
        <p class="text-muted flex-grow-1">${escapeHtml(menu.description)}</p>
        <p class="small">Minimum : ${Number(menu.min_people)} personnes · Stock : ${Number(menu.available_stock)}</p>
        <a class="btn btn-outline-primary" href="/menus/${Number(menu.id)}">Voir le détail</a>
      </div></article>
    </div>`).join('') || '<p class="text-muted">Aucun menu ne correspond à ces critères.</p>';

  document.addEventListener('DOMContentLoaded', () => {
    const toggler = document.querySelector('.navbar-toggler');
    const navigation = document.querySelector(toggler?.dataset.bsTarget || '');
    toggler?.addEventListener('click', () => navigation?.classList.toggle('show'));

    const form = document.querySelector('#menuFilters');
    const grid = document.querySelector('#menuGrid');
    const status = document.querySelector('#menuFilterStatus');
    if (!form || !grid) return;

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const params = new URLSearchParams(new FormData(form));
      [...params.entries()].filter(([, value]) => value === '').forEach(([key]) => params.delete(key));
      status?.classList.remove('visually-hidden');
      if (status) status.textContent = 'Filtrage en cours…';

      try {
        const menus = await window.VgApi.get(`${form.dataset.filterEndpoint}?${params.toString()}`);
        grid.innerHTML = renderMenus(menus);
        if (status) status.textContent = `${menus.length} menu(s) trouvé(s).`;
      } catch (error) {
        if (status) status.textContent = error.message;
      }
    });
  });
})();
