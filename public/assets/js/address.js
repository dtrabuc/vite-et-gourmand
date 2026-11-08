// Adresse de livraison (formulaire de commande) : suggestions OpenStreetMap,
// puis vérification par le serveur (zone de 25 km autour de Bordeaux et frais).
// Le résultat est diffusé par l'évènement « vg:address-checked » pour order.js.
(() => {
  const address = document.getElementById('delivery_address');
  const suggestions = document.getElementById('addressSuggestions');
  const postalCode = document.getElementById('delivery_postal_code');
  const city = document.getElementById('delivery_city');
  const statusBox = document.getElementById('addressStatus');
  const manualWrap = document.getElementById('addressManualWrap');
  const manualBox = document.getElementById('address_manual');
  const outsideChoice = document.getElementById('outsideZoneChoice');
  const outsideCost = document.getElementById('outsideZoneCost');
  const acceptOutside = document.getElementById('accept_outside_zone');
  if (!address || !postalCode || !city || !statusBox) return;

  let suggestTimer = null;
  let checkTimer = null;
  let suggestController = null;
  let checkId = 0;
  let items = [];
  let active = -1;

  const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };
  const formatPrice = (value) => Number(value || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';

  const showStatus = (type, message) => {
    statusBox.className = 'address-status address-status--' + type;
    statusBox.textContent = message;
  };

  const publish = (result) => document.dispatchEvent(new CustomEvent('vg:address-checked', { detail: result }));

  // ---------- suggestions ----------
  const hideSuggestions = () => {
    suggestions.classList.add('d-none');
    suggestions.innerHTML = '';
    address.setAttribute('aria-expanded', 'false');
    active = -1;
  };

  const choose = (item) => {
    address.value = item.street || address.value;
    postalCode.value = item.postal_code || '';
    city.value = item.city || '';
    hideSuggestions();
    void check();
  };

  const renderSuggestions = () => {
    if (!items.length) {
      hideSuggestions();
      return;
    }
    suggestions.innerHTML = items.map((item, index) =>
      '<button type="button" role="option" class="list-group-item list-group-item-action address-suggestion" data-index="' + index + '">' +
      escapeHtml(item.label) + (item.precision === 'city' ? ' <span class="small text-muted">(commune)</span>' : '') + '</button>'
    ).join('');
    suggestions.classList.remove('d-none');
    address.setAttribute('aria-expanded', 'true');
  };

  const fetchSuggestions = async () => {
    const query = [address.value, postalCode.value, city.value].map((v) => v.trim()).filter(Boolean).join(' ');
    if (address.value.trim().length < 4) {
      hideSuggestions();
      return;
    }
    suggestController?.abort();
    suggestController = new AbortController();
    try {
      const response = await fetch('/api/address/autocomplete?q=' + encodeURIComponent(query), {
        headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: suggestController.signal
      });
      const payload = await response.json();
      items = Array.isArray(payload?.data) ? payload.data : [];
      if (payload?.unavailable) {
        showStatus('warning', 'Suggestions indisponibles pour le moment : saisissez votre adresse complète, elle sera vérifiée.');
      }
      renderSuggestions();
    } catch (error) {
      if (error?.name !== 'AbortError') hideSuggestions();
    }
  };

  suggestions.addEventListener('click', (event) => {
    const button = event.target.closest('.address-suggestion');
    if (button) choose(items[Number(button.dataset.index)]);
  });

  address.addEventListener('keydown', (event) => {
    const buttons = suggestions.querySelectorAll('.address-suggestion');
    if (!buttons.length) return;
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      active = (active + (event.key === 'ArrowDown' ? 1 : -1) + buttons.length) % buttons.length;
      buttons.forEach((b, i) => b.classList.toggle('active', i === active));
    } else if (event.key === 'Enter' && active >= 0) {
      event.preventDefault();
      choose(items[active]);
    } else if (event.key === 'Escape') {
      hideSuggestions();
    }
  });

  // ---------- vérification ----------
  const check = async () => {
    const values = { address: address.value.trim(), postal_code: postalCode.value.trim(), city: city.value.trim() };
    const id = ++checkId;
    outsideChoice?.classList.add('d-none');

    if (!values.address || !values.postal_code || !values.city) {
      statusBox.className = 'address-status d-none';
      manualWrap?.classList.add('d-none');
      publish(null);
      return;
    }

    showStatus('pending', 'Vérification de l’adresse…');
    try {
      const result = await window.VgApi.get('/api/address/check?' + new URLSearchParams(values).toString());
      if (id !== checkId) return;

      const verified = result.status === 'verified';
      const type = !verified ? 'warning' : (result.zone === 'outside' ? 'warning' : 'success');
      showStatus(type, (verified ? '✓ ' : '⚠ ') + result.message);

      // l'adresse n'a pas pu être confirmée : saisie manuelle possible
      manualWrap?.classList.toggle('d-none', verified);
      if (verified && manualBox) manualBox.checked = false;

      if (result.zone === 'outside' && outsideChoice) {
        outsideCost.textContent = formatPrice(result.cost) + ' de livraison';
        outsideChoice.classList.remove('d-none');
      } else if (acceptOutside) {
        acceptOutside.checked = false;
      }
      publish(result);
    } catch (error) {
      if (id !== checkId) return;
      showStatus('warning', '⚠ ' + (error?.message || 'Vérification impossible.') + ' Vous pouvez confirmer votre adresse manuellement.');
      manualWrap?.classList.remove('d-none');
      publish({ status: 'unavailable' });
    }
  };

  const checkLater = () => {
    window.clearTimeout(checkTimer);
    checkTimer = window.setTimeout(() => void check(), 900);
  };

  address.addEventListener('input', () => {
    window.clearTimeout(suggestTimer);
    suggestTimer = window.setTimeout(fetchSuggestions, 350);
    checkLater();
  });
  [postalCode, city].forEach((field) => field.addEventListener('input', checkLater));
  address.addEventListener('blur', () => window.setTimeout(hideSuggestions, 200));
  document.addEventListener('click', (event) => {
    if (!suggestions.contains(event.target) && event.target !== address) hideSuggestions();
  });

  // accepter la livraison hors zone vaut confirmation de l'adresse (une seule case à cocher)
  acceptOutside?.addEventListener('change', () => {
    if (acceptOutside.checked && manualBox && !manualWrap.classList.contains('d-none')) {
      manualBox.checked = true;
      manualBox.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });

  // retrait choisi depuis le message hors zone
  document.getElementById('choosePickup')?.addEventListener('click', () => {
    const pickup = document.getElementById('service_type_pickup');
    if (pickup) {
      pickup.checked = true;
      pickup.dispatchEvent(new Event('change', { bubbles: true }));
      pickup.closest('section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });

  // adresse déjà remplie (profil ou retour d'erreur)
  if (address.value.trim() && postalCode.value.trim() && city.value.trim()) void check();
  window.VgAddress = { check };
})();
