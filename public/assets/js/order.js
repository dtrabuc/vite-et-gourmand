// Nouvelle commande : nombre de personnes, choix du menu en cartes, mode de
// prestation, créneau et récapitulatif. Tous les prix viennent du serveur.
(() => {
  const form = document.getElementById('orderFinalForm');
  if (!form) return;

  const peopleField = document.getElementById('number_of_people');
  const optionsBox = document.getElementById('menuOptions');
  const menuMessage = document.getElementById('menuAvailabilityMessage');
  const dateField = document.getElementById('delivery_date');
  const timeField = document.getElementById('delivery_time');
  const dateMessage = document.getElementById('dateAvailabilityMessage');
  const deliveryFields = document.getElementById('deliveryFields');
  const pickupNotice = document.getElementById('pickupLocationNotice');
  const submitMessage = document.getElementById('orderSubmitMessage');
  const summary = {
    menu: document.getElementById('orderSummaryMenu'),
    label: document.getElementById('orderMenuLabel'),
    price: document.getElementById('orderMenuPrice'),
    discountRow: document.getElementById('orderDiscountRow'),
    discount: document.getElementById('orderDiscount'),
    delivery: document.getElementById('orderDeliveryPrice'),
    distance: document.getElementById('orderDeliveryDistance'),
    total: document.getElementById('orderPreviewTotal'),
    message: document.getElementById('orderPriceMessage')
  };

  let menus = [];
  let selectedId = Number(form.dataset.oldMenuId || 0);
  let addressResult = null; // dernier résultat de vérification (address.js)
  const compoSection = document.getElementById('compositionSection');
  const compoBox = document.getElementById('menuComposition');
  const compoMessage = document.getElementById('compositionMessage');
  let composition = null;
  let compositionMenu = 0;
  // composition du menu choisi (composition.js) ; recréée quand le menu change
  const syncComposition = () => {
    const menu = selectedMenu();
    if (!menu || !compoBox || !window.VgComposition) {
      compoSection?.classList.add('d-none');
      composition = null;
      compositionMenu = 0;
      return;
    }
    compoSection.classList.remove('d-none');
    if (compositionMenu === Number(menu.id)) {
      composition?.setPeople(getPeople());
      return;
    }
    compositionMenu = Number(menu.id);
    composition = window.VgComposition.mount(compoBox, {
      menuId: menu.id,
      people: getPeople(),
      onChange: (list) => { if (compoMessage) compoMessage.textContent = list.length ? 'À répartir : ' + list.join(' ; ') : ''; }
    });
  };
  const manualBox = document.getElementById('address_manual');
  const acceptOutside = document.getElementById('accept_outside_zone');

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
    const value = Number(peopleField.value || 0);
    return Number.isInteger(value) && value >= 1 && value <= 999 ? value : 0;
  };
  const getServiceType = () => form.querySelector('input[name="service_type"]:checked')?.value || 'delivery';
  const fieldValue = (id) => document.getElementById(id)?.value?.trim() || '';
  const selectedMenu = () => menus.find((menu) => Number(menu.id) === selectedId && menu.available) || null;

  // ---------- récapitulatif ----------
  const resetSummary = (message) => {
    const menu = selectedMenu();
    summary.menu.textContent = menu ? menu.title : 'Aucun menu choisi';
    summary.label.textContent = 'Menu';
    summary.price.textContent = '—';
    summary.discountRow.hidden = true;
    summary.delivery.textContent = '—';
    summary.distance.textContent = '';
    summary.total.textContent = '—';
    summary.message.textContent = message || 'Choisissez un menu pour voir le total.';
  };

  let priceRequest = 0;
  const updatePrice = async () => {
    const menu = selectedMenu();
    const people = getPeople();
    const id = ++priceRequest;
    syncComposition();
    if (!menu || !people) {
      resetSummary();
      return;
    }

    const params = new URLSearchParams({
      menu_id: String(menu.id),
      number_of_people: String(people),
      service_type: getServiceType(),
      delivery_address: fieldValue('delivery_address'),
      delivery_postal_code: fieldValue('delivery_postal_code'),
      delivery_city: fieldValue('delivery_city'),
      address_manual: manualBox?.checked ? '1' : '',
      accept_outside_zone: acceptOutside?.checked ? '1' : ''
    });

    try {
      const data = await window.VgApi.get('/orders/price-preview?' + params.toString()) || {};
      if (id !== priceRequest) return;
      summary.menu.textContent = data.menu_title || menu.title;
      summary.label.textContent = people + ' × ' + formatPrice(data.per_person);
      summary.price.textContent = formatPrice(data.gross_price);
      summary.discountRow.hidden = !(data.discount_rate > 0);
      summary.discount.textContent = '−' + formatPrice(data.discount_amount);
      summary.delivery.textContent = getServiceType() !== 'delivery'
        ? 'aucune'
        : data.delivery_cost_pending
        ? 'à confirmer'
        : data.delivery_cost === null || data.delivery_cost === undefined
        ? 'à calculer'
        : (Number(data.delivery_cost) === 0 ? 'offerte' : formatPrice(data.delivery_cost));
      summary.distance.textContent = data.distance_km ? '(' + Number(data.distance_km).toLocaleString('fr-FR') + ' km)' : '';
      summary.total.textContent = data.ready && !data.delivery_cost_pending
        ? formatPrice(data.total_price)
        : formatPrice(data.menu_price) + ' + livraison';
      summary.message.textContent = data.message || 'Le total est recalculé par nos soins à la validation.';
    } catch (error) {
      if (id !== priceRequest) return;
      resetSummary(error?.message || 'Impossible de calculer le prix.');
    }
  };


  // ---------- menus en cartes ----------
  const renderOptions = (people) => {
    const byPrice = (a, b) => Number(a.price_per_person) - Number(b.price_per_person);
    const available = menus.filter((menu) => menu.available).sort(byPrice);
    const others = menus.filter((menu) => !menu.available);
    const card = (menu) => {
      const checked = Number(menu.id) === selectedId && menu.available;
      const discount = menu.discount_rate > 0
        ? '<span class="badge text-bg-success ms-1">−10 %</span>'
        : '';
      const price = menu.available
        ? '<strong>' + formatPrice(menu.menu_price) + '</strong>' + discount +
          '<span class="d-block small text-muted">' + people + ' × ' + formatPrice(menu.price_per_person) + '</span>'
        : '<span class="small">' + escapeHtml(menu.reason || 'Indisponible') + '</span>';
      return '<label class="menu-option' + (menu.available ? '' : ' menu-option--disabled') + '">' +
        '<input class="form-check-input" type="radio" name="menu_id" value="' + Number(menu.id) + '"' +
        (checked ? ' checked' : '') + (menu.available ? '' : ' disabled') + ' required>' +
        '<span class="menu-option__body"><span class="menu-option__title">' + escapeHtml(menu.title) + '</span>' +
        '<span class="small text-muted">' + formatPrice(menu.price_per_person) + ' / pers. · dès ' + Number(menu.min_people) + ' pers.</span></span>' +
        '<span class="menu-option__price">' + price + '</span></label>';
    };
    // pas de barrière : on montre ce qui est possible, le reste est replié
    optionsBox.innerHTML = available.map(card).join('') +
      (others.length
        ? '<details class="menu-options__others mt-2"><summary class="small">' + others.length + ' autre' + (others.length > 1 ? 's menus demandent' : ' menu demande') +
          ' plus de personnes</summary><div class="menu-options mt-2">' + others.map(card).join('') + '</div></details>'
        : '');
  };

  let menuRequest = 0;
  const loadMenus = async () => {
    const people = getPeople();
    form.querySelectorAll('[data-people]').forEach((b) => b.classList.toggle('is-active', Number(b.dataset.people) === people));
    const id = ++menuRequest;
    if (!people) {
      optionsBox.innerHTML = '';
      menuMessage.textContent = 'Indiquez le nombre de personnes (1 à 999) pour voir les menus.';
      menus = [];
      resetSummary();
      return;
    }

    menuMessage.textContent = 'Chargement des menus…';
    try {
      const data = await window.VgApi.get('/orders/menu-options?number_of_people=' + people) || {};
      if (id !== menuRequest) return;
      menus = Array.isArray(data.menus) ? data.menus : [];
      const available = menus.filter((menu) => menu.available).length;

      const previous = menus.find((menu) => Number(menu.id) === selectedId);
      if (previous && !previous.available) {
        menuMessage.textContent = '« ' + previous.title + ' » : ' + (previous.reason || 'indisponible') + ' Choisissez un autre menu ou augmentez le nombre de personnes.';
      } else {
        menuMessage.textContent = available
          ? available + ' menu' + (available > 1 ? 's' : '') + ' pour ' + people + ' personne' + (people > 1 ? 's' : '') + '. Prix total affiché, livraison en plus.'
          : 'Aucun menu disponible pour ' + people + ' personne' + (people > 1 ? 's' : '') + '.';
      }
      renderOptions(people);
      try { sessionStorage.setItem('vg_people', String(people)); } catch (_) { /* rien */ }
    } catch (error) {
      if (id !== menuRequest) return;
      menus = [];
      optionsBox.innerHTML = '';
      menuMessage.textContent = error?.message || 'Impossible de charger les menus.';
    }
    void updatePrice();
  };

  let peopleTimer = null;
  const loadMenusLater = () => {
    window.clearTimeout(peopleTimer);
    peopleTimer = window.setTimeout(() => void loadMenus(), 350);
  };

  // ---------- créneaux ----------
  const loadSlots = async () => {
    const date = dateField.value;
    const previous = timeField.value || timeField.dataset.old || '';
    timeField.innerHTML = '<option value="">Choisissez un créneau</option>';
    timeField.disabled = true;
    if (!date) {
      timeField.options[0].textContent = 'Choisissez d’abord une date';
      return;
    }

    try {
      const data = await window.VgApi.get('/orders/availability?date=' + encodeURIComponent(date)) || {};
      const slots = Array.isArray(data.slots) ? data.slots : [];
      slots.forEach((slot) => timeField.add(new Option(slot, slot, false, slot === previous.slice(0, 5))));
      timeField.disabled = slots.length === 0;
      if (data.open && slots.length > 0) {
        dateMessage.className = 'form-text text-success';
        dateMessage.textContent = data.day_label + ' : ouvert ' + (data.windows || []).map((w) => w.map((t) => String(t).slice(0, 5)).join('–')).join(' / ') + '.';
      } else {
        dateMessage.className = 'form-text text-danger';
        dateMessage.textContent = (data.day_label ? data.day_label + ' : ' : '') + 'fermé ou plus de créneau. Choisissez un autre jour.';
      }
    } catch (error) {
      dateMessage.className = 'form-text text-danger';
      dateMessage.textContent = error?.message || 'Impossible de vérifier la date.';
    }
  };

  // ---------- mode de prestation ----------
  const syncServiceType = () => {
    const isDelivery = getServiceType() === 'delivery';
    deliveryFields.classList.toggle('d-none', !isDelivery);
    pickupNotice?.classList.toggle('d-none', isDelivery);
    ['delivery_address', 'delivery_postal_code', 'delivery_city'].forEach((id) => {
      const field = document.getElementById(id);
      if (field) field.required = isDelivery;
    });
    void updatePrice();
  };

  // ---------- validation avant envoi : message clair au lieu d'un blocage muet ----------
  form.addEventListener('submit', (event) => {
    const problems = [];
    if (!getPeople()) problems.push('le nombre de personnes');
    if (!selectedMenu()) problems.push('un menu disponible');
    if (getServiceType() === 'delivery') {
      if (!fieldValue('delivery_address') || !fieldValue('delivery_postal_code') || !fieldValue('delivery_city')) {
        problems.push('l’adresse de livraison complète');
      } else if (addressResult && addressResult.status !== 'verified' && !manualBox?.checked) {
        problems.push('une adresse vérifiée (choisissez une suggestion ou cochez « Mon adresse est correcte »)');
      } else if (addressResult && addressResult.zone === 'outside' && !acceptOutside?.checked) {
        problems.push('votre choix pour l’adresse hors zone : retrait gratuit ou livraison payante');
      }
    }
    if (composition && composition.problems().length) problems.push('la répartition des convives dans « Composez votre menu »');
    const healthData = fieldValue('customization') !== '' || form.querySelector('input[name$="[without][]"]');
    if (healthData && !document.getElementById('allergy_consent')?.checked) problems.push('votre consentement pour les informations d’allergies');
    if (!dateField.value) problems.push('une date');
    if (!timeField.value) problems.push('une heure');
    if (!fieldValue('contact_phone')) problems.push('un téléphone');

    if (problems.length) {
      event.preventDefault();
      submitMessage.textContent = 'Il manque : ' + problems.join(', ') + '.';
      submitMessage.classList.remove('d-none');
      submitMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    submitMessage.classList.add('d-none');
  });

  // ---------- évènements ----------
  // le message « Il manque » disparaît dès que l'on complète le formulaire
  ['input', 'change'].forEach((type) => form.addEventListener(type, () => submitMessage.classList.add('d-none')));

  form.querySelectorAll('[data-step]').forEach((button) => {
    button.addEventListener('click', () => {
      peopleField.value = String(Math.min(999, Math.max(1, getPeople() + Number(button.dataset.step))));
      void loadMenus();
    });
  });
  peopleField.addEventListener('input', loadMenusLater);
  optionsBox.addEventListener('change', (event) => {
    if (event.target.name === 'menu_id') {
      selectedId = Number(event.target.value);
      void updatePrice();
    }
  });
  dateField.addEventListener('change', () => void loadSlots());
  document.addEventListener('vg:address-checked', (event) => {
    addressResult = event.detail;
    void updatePrice();
  });
  [manualBox, acceptOutside].forEach((box) => box?.addEventListener('change', () => void updatePrice()));
  form.querySelectorAll('[data-people]').forEach((button) => {
    button.addEventListener('click', () => {
      peopleField.value = button.dataset.people;
      void loadMenus();
    });
  });
  form.querySelectorAll('input[name="service_type"]').forEach((input) => input.addEventListener('change', syncServiceType));
  // le prix est recalculé après chaque vérification d'adresse (évènement vg:address-checked)

  // nombre de personnes saisi sur le catalogue si la page n'en propose pas
  if (!getPeople()) {
    let saved = null;
    try { saved = sessionStorage.getItem('vg_people'); } catch (_) { saved = null; }
    if (saved && Number(saved) >= 1) peopleField.value = saved;
  }

  syncServiceType();
  void loadMenus();
  if (dateField.value) void loadSlots();
})();
