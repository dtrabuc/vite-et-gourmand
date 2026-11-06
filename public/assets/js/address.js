(() => {
  const address = document.getElementById('delivery_address');
  const suggestions = document.getElementById('addressSuggestions');
  const postalCode = document.getElementById('delivery_postal_code');
  const city = document.getElementById('delivery_city');
  const hint = document.getElementById('addressHint');

  if (!address) return;

  let timer = null;
  let controller = null;

  const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  const hideSuggestions = () => {
    if (!suggestions) return;
    suggestions.classList.add('d-none');
    suggestions.innerHTML = '';
  };

  const renderSuggestions = (items) => {
    if (!suggestions) return;

    if (!items.length) {
      hideSuggestions();
      return;
    }

    suggestions.innerHTML = items.map((item, index) => {
      const label = escapeHtml(item.label || item.description || '');
      return '<button type="button" class="list-group-item list-group-item-action address-suggestion" data-index="' + index + '">' + label + '</button>';
    }).join('');
    suggestions.classList.remove('d-none');

    suggestions.querySelectorAll('.address-suggestion').forEach((button) => {
      button.addEventListener('click', () => {
        const item = items[Number(button.dataset.index)];
        if (!item) return;

        address.value = item.address || item.label || '';
        if (postalCode && item.postal_code) postalCode.value = item.postal_code;
        if (city && item.city) city.value = item.city;
        // Prévient le calcul du prix que l'adresse a changé.
        address.dispatchEvent(new Event('change', { bubbles: true }));

        if (hint) {
          hint.textContent = 'Adresse proposée par Google. Vous pouvez compléter avec résidence, bâtiment, étage ou appartement.';
        }

        hideSuggestions();
      });
    });
  };

  const fetchSuggestions = async () => {
    const query = address.value.trim().replace(/\s+/g, ' ');

    if (query.length < 4) {
      hideSuggestions();
      return;
    }

    controller?.abort();
    controller = new AbortController();

    try {
      const response = await fetch('/api/address/autocomplete?q=' + encodeURIComponent(query), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
        signal: controller.signal
      });

      const payload = await response.json();

      if (!response.ok || !payload?.success) {
        hideSuggestions();
        return;
      }

      renderSuggestions(Array.isArray(payload.data) ? payload.data : []);
    } catch (error) {
      if (error?.name !== 'AbortError') {
        hideSuggestions();
      }
    }
  };

  address.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(fetchSuggestions, 300);
  });

  address.addEventListener('blur', () => {
    setTimeout(hideSuggestions, 200);
    const value = address.value.trim().replace(/\s+/g, ' ');

    if (!value) return;

    if (hint && !hint.textContent.includes('Google')) {
      hint.textContent = 'Adresse conservée. Pour une résidence ou un appartement, ajoutez les précisions dans les instructions du livreur.';
    }
  });

  document.addEventListener('click', (event) => {
    if (event.target instanceof Node && suggestions && !suggestions.contains(event.target) && event.target !== address) {
      hideSuggestions();
    }
  });
})();