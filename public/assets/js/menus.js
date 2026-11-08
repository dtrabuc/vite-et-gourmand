// Catalogue des menus : nombre de personnes, thèmes, régimes et budget filtrés
// sans recharger la page. Les prix et totaux viennent du serveur (/menus/filter).
(() => {
  const form = document.getElementById('menuFilters');
  const grid = document.getElementById('menuGrid');
  const status = document.getElementById('menuFilterStatus');
  if (!form || !grid) return;

  const peopleField = document.getElementById('filterPeople');
  const loggedIn = form.dataset.loggedIn === '1';
  const STORAGE_KEY = 'vg_people';
  let fallbackImages = {};
  try { fallbackImages = JSON.parse(form.dataset.fallbackImages || '{}'); } catch (_) { fallbackImages = {}; }

  const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  const formatPrice = (value) => Number(value || 0).toLocaleString('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }) + ' €';

  const getPeople = () => {
    const value = Number(peopleField?.value || 0);
    return Number.isInteger(value) && value >= 1 && value <= 999 ? value : null;
  };

  const renderImage = (menu) => {
    const images = Array.isArray(menu.images) ? menu.images : [];
    const image = images.find((item) => String(item?.url || '').trim()) || fallbackImages[String(menu.id)] || fallbackImages['1'];
    const url = String(image?.url || '').trim();
    if (!url) return '';
    return '<img src="' + escapeHtml(url) + '" class="card-img-top menu-card__image" alt="' +
      escapeHtml(image?.alt_text || menu.title) + '" width="1200" height="800" loading="lazy" decoding="async">';
  };

  const renderIncluded = (menu) => {
    const groups = Array.isArray(menu.composition) ? menu.composition : [];
    return '<div class="menu-card__included mb-3"><p class="menu-card__label mb-1">Inclus pour chaque convive</p>' +
      '<ul class="list-unstyled mb-0">' +
      groups.map((group) => '<li><span class="menu-card__count">' + escapeHtml(group.summary) + '</span> ' +
        '<span class="text-muted">: ' + escapeHtml(group.names.join(group.key === 'side' ? ', ' : ' ou ')) + '</span></li>').join('') +
      '</ul></div>';
  };

  const renderTotal = (menu, people) => {
    const quote = menu.quote;
    let html = '';
    if (!quote) {
      html = '<span class="text-muted small">Indiquez le nombre de personnes en haut de page pour voir le total.</span>';
    } else if (!quote.orderable) {
      html = '<span class="small fw-semibold">Disponible à partir de ' + Number(menu.min_people) + ' personnes.</span>';
    } else {
      html = '<div class="d-flex justify-content-between align-items-baseline gap-2">' +
        '<span class="small">Total pour ' + people + ' pers.</span><span>' +
        (quote.discount_rate > 0 ? '<s class="text-muted small">' + formatPrice(quote.gross) + '</s> ' : '') +
        '<strong class="fs-5">' + formatPrice(quote.total) + '</strong></span></div>' +
        (quote.discount_rate > 0
          ? '<span class="badge text-bg-success">−10 % appliqués</span>'
          : '<span class="small text-muted">−10 % à partir de ' + Number(menu.discount_from) + ' personnes.</span>');
    }
    const stock = Number(menu.available_stock || 0);
    if (stock <= 5) {
      html += '<span class="d-block small text-danger mt-1">Plus que ' + stock + ' commande' + (stock > 1 ? 's possibles' : ' possible') + '.</span>';
    }
    return '<div class="menu-card__total mt-auto mb-3">' + html + '</div>';
  };

  const renderButton = (menu, people) => {
    const orderable = !menu.quote || menu.quote.orderable;
    const orderUrl = '/orders/new?menu=' + Number(menu.id) + (people && orderable ? '&people=' + people : '');
    if (!orderable) {
      return '<a class="btn btn-outline-primary" href="/menus/' + Number(menu.id) + '">Voir le détail</a>';
    }
    if (loggedIn) {
      return '<a class="btn btn-primary" href="' + escapeHtml(orderUrl) + '">Commander' + (people ? ' pour ' + people + ' pers.' : '') + '</a>';
    }
    return '<a class="btn btn-primary" href="/login?redirect=' + encodeURIComponent(orderUrl) + '">Se connecter pour commander</a>';
  };

  const renderCard = (menu, people) => {
    const orderable = !menu.quote || menu.quote.orderable;
    return '<div class="col-sm-6 col-lg-4" data-menu-card>' +
      '<article class="card menu-card h-100 border-0 shadow-sm overflow-hidden' + (orderable ? '' : ' menu-card--unavailable') + '">' +
      '<div class="menu-card__media">' + renderImage(menu) +
      '<span class="badge menu-card__theme">' + escapeHtml(menu.theme) + '</span></div>' +
      '<div class="card-body d-flex flex-column">' +
      '<h3 class="h5 mb-1"><a class="menu-card__title" href="/menus/' + Number(menu.id) + (people ? '?people=' + people : '') + '">' + escapeHtml(menu.title) + '</a></h3>' +
      '<p class="menu-card__price mb-2"><strong>' + formatPrice(menu.price_per_person) + '</strong> / pers. ' +
      '<span class="text-muted">· dès ' + Number(menu.min_people) + ' pers. · ' + escapeHtml(menu.dietary_regime_label || '') + '</span></p>' +
      '<p class="menu-card__desc text-muted small mb-3">' + escapeHtml(menu.description) + '</p>' +
      renderIncluded(menu) + renderTotal(menu, people) +
      '<div class="d-grid gap-2">' + renderButton(menu, people) + '</div>' +
      '</div></article></div>';
  };

  const renderMenus = (menus, people) => {
    if (!Array.isArray(menus) || menus.length === 0) {
      grid.innerHTML = '<div class="col-12"><div class="alert alert-warning">Aucun menu ne correspond à ces critères. ' +
        '<button type="button" class="btn btn-link p-0 align-baseline" data-reset>Tout effacer</button></div></div>';
      return 0;
    }
    // menus commandables d'abord, ceux dont le minimum est trop haut ensuite
    // pas de barrière : les menus possibles d'abord, ceux qui demandent plus de monde repliés
    const possible = menus.filter((menu) => !menu.quote || menu.quote.orderable);
    const others = menus.filter((menu) => menu.quote && !menu.quote.orderable);
    grid.innerHTML = possible.map((menu) => renderCard(menu, people)).join('') +
      (others.length
        ? '<div class="col-12"><details class="menu-others"><summary>' + others.length + ' autre' + (others.length > 1 ? 's menus' : ' menu') +
          ' à partir de plus de ' + people + ' personne' + (people > 1 ? 's' : '') + '</summary>' +
          '<div class="row g-4 mt-1">' + others.map((menu) => renderCard(menu, people)).join('') + '</div></details></div>'
        : '');
    return possible.length;
  };

  const buildQuery = () => {
    const params = new URLSearchParams();
    new FormData(form).forEach((value, key) => {
      if (String(value).trim() !== '') params.set(key, String(value).trim());
    });
    return params;
  };

  let requestId = 0;
  const load = async () => {
    const people = getPeople();
    form.querySelectorAll('[data-people]').forEach((b) => b.classList.toggle('is-active', Number(b.dataset.people) === people));
    const params = buildQuery();
    const id = ++requestId;

    // garde le nombre de personnes pour la fiche menu et la commande
    try {
      if (people) sessionStorage.setItem(STORAGE_KEY, String(people));
      else sessionStorage.removeItem(STORAGE_KEY);
    } catch (_) { /* stockage indisponible */ }
    const url = new URL(window.location.href);
    if (people) url.searchParams.set('people', String(people)); else url.searchParams.delete('people');
    window.history.replaceState(null, '', url);

    grid.setAttribute('aria-busy', 'true');
    try {
      const endpoint = form.dataset.filterEndpoint || '/menus/filter';
      const menus = await window.VgApi.get(endpoint + (params.toString() ? '?' + params.toString() : ''));
      if (id !== requestId) return;
      const orderable = renderMenus(menus, people);
      if (status) {
        const total = Array.isArray(menus) ? menus.length : 0;
        status.textContent = total + ' menu' + (total > 1 ? 's' : '') +
          (people ? ' · prix pour ' + people + ' personne' + (people > 1 ? 's' : '') +
            (orderable < total ? ' · ' + (total - orderable) + ' demande' + (total - orderable > 1 ? 'nt' : '') + ' plus de convives' : '') : '');
      }
    } catch (error) {
      if (id !== requestId) return;
      grid.innerHTML = '<div class="col-12"><div class="alert alert-danger">' +
        escapeHtml(error?.message || 'Impossible de charger les menus.') + '</div></div>';
    } finally {
      if (id === requestId) grid.removeAttribute('aria-busy');
    }
  };

  let timer = null;
  const loadLater = () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => void load(), 350);
  };

  form.querySelectorAll('[data-step]').forEach((button) => {
    button.addEventListener('click', () => {
      const current = getPeople() || 0;
      const next = Math.min(999, Math.max(1, current + Number(button.dataset.step)));
      peopleField.value = String(next);
      loadLater();
    });
  });

  peopleField?.addEventListener('input', loadLater);
  form.querySelectorAll('[data-people]').forEach((button) => {
    button.addEventListener('click', () => {
      peopleField.value = button.dataset.people;
      void load();
    });
  });

  // un seul régime à la fois (cases à cocher pour pouvoir tout décocher)
  form.querySelectorAll('input[name="dietary_regime"]').forEach((box) => {
    box.addEventListener('change', () => {
      if (box.checked) {
        form.querySelectorAll('input[name="dietary_regime"]').forEach((other) => { if (other !== box) other.checked = false; });
      }
      void load();
    });
  });
  form.querySelectorAll('input[name="theme"]').forEach((radio) => radio.addEventListener('change', () => void load()));

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    void load();
  });

  // « Tout effacer » vide les filtres mais garde le nombre de personnes
  form.addEventListener('reset', () => {
    const people = peopleField?.value || '';
    window.setTimeout(() => {
      if (peopleField) peopleField.value = people;
      void load();
    }, 0);
  });
  grid.addEventListener('click', (event) => {
    if (event.target.closest('[data-reset]')) form.reset();
  });

  // nombre de personnes déjà saisi plus tôt dans la visite
  if (!getPeople()) {
    let saved = null;
    try { saved = sessionStorage.getItem(STORAGE_KEY); } catch (_) { saved = null; }
    if (saved && Number(saved) >= 1) {
      peopleField.value = saved;
      void load();
    }
  }
})();
