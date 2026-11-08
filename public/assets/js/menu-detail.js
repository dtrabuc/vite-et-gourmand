// Fiche menu : total recalculé par le serveur quand on change le nombre de personnes.
(() => {
  const box = document.getElementById('menuQuote');
  if (!box) return;

  const field = document.getElementById('quotePeople');
  const minPeople = Number(box.dataset.minPeople || 1);
  const loggedIn = box.dataset.loggedIn === '1';
  const el = (name) => box.querySelector('[data-quote-' + name + ']');
  const formatPrice = (value) => Number(value || 0).toLocaleString('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }) + ' €';

  let requestId = 0;
  const update = async () => {
    const people = Number(field.value || 0);
    if (!Number.isInteger(people) || people < 1 || people > 999) return;
    const id = ++requestId;

    try {
      const quote = await window.VgApi.get(box.dataset.endpoint + '?people=' + people);
      if (id !== requestId) return;
      el('label').textContent = people + ' × ' + formatPrice(quote.per_person);
      el('gross').textContent = formatPrice(quote.gross);
      el('discount-row').hidden = !(quote.discount_rate > 0);
      el('discount').textContent = '−' + formatPrice(quote.discount_amount);
      el('total').textContent = formatPrice(quote.total);
      el('message').textContent = quote.orderable
        ? 'Livraison offerte dans Bordeaux, sinon 5 € + 0,59 €/km.'
        : 'Ce menu se commande à partir de ' + minPeople + ' personnes.';

      const orderUrl = '/orders/new?menu=' + box.dataset.menuId + '&people=' + Math.max(people, minPeople);
      const link = el('order');
      if (link) link.href = loggedIn ? orderUrl : '/login?redirect=' + encodeURIComponent(orderUrl);
      try { sessionStorage.setItem('vg_people', String(people)); } catch (_) { /* rien */ }
    } catch (error) {
      if (id === requestId) el('message').textContent = error?.message || 'Calcul impossible pour le moment.';
    }
  };

  let timer = null;
  field.addEventListener('input', () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => void update(), 300);
  });
  box.querySelectorAll('[data-step]').forEach((button) => {
    button.addEventListener('click', () => {
      field.value = String(Math.min(999, Math.max(1, Number(field.value || 0) + Number(button.dataset.step))));
      void update();
    });
  });

  // nombre de personnes saisi sur le catalogue
  if (!new URLSearchParams(window.location.search).has('people')) {
    let saved = null;
    try { saved = sessionStorage.getItem('vg_people'); } catch (_) { saved = null; }
    if (saved && Number(saved) >= 1 && saved !== field.value) {
      field.value = saved;
      void update();
    }
  }
})();
