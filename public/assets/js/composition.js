// Composer son menu : pour chaque catégorie (apéritif → digestif), répartir les
// convives entre les options, remplacer un plat par un autre de la même catégorie,
// ou demander une variante sans tel allergène. Le serveur revalide tout
// (OrderCompositionService) ; le prix ne change pas.
(() => {
  const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  // répartition au prorata, méthode du plus fort reste (même calcul que le serveur)
  const rescale = (quantities, from, to) => {
    if (from < 1 || from === to) return quantities.slice();
    const exact = quantities.map((q) => (q * to) / from);
    const result = exact.map(Math.floor);
    let missing = to - result.reduce((a, b) => a + b, 0);
    exact.map((v, i) => [v - Math.floor(v), i]).sort((a, b) => b[0] - a[0]).forEach(([, i]) => {
      if (missing-- > 0) result[i]++;
    });
    return result;
  };

  const split = (people, parts) => Array.from({ length: parts }, (_, i) => Math.floor(people / parts) + (i < people % parts ? 1 : 0));

  function mount(container, { menuId, people, items = null, onChange = () => {} }) {
    let categories = [];
    let rows = [];        // { key, slot, dish, quantity, without: [] }
    let currentPeople = people;
    const dishById = new Map();

    const groupKey = (row) => (row.key === 'side' ? 'side:' + row.slot : row.key);

    const defaults = () => {
      rows = [];
      categories.forEach((cat) => {
        const shares = cat.per_slot ? cat.slots.map(() => currentPeople) : split(currentPeople, cat.slots.length);
        cat.slots.forEach((slot, i) => rows.push({ key: cat.key, slot: slot.id, dish: slot.id, quantity: shares[i], without: [] }));
      });
    };

    const problems = () => {
      const list = [];
      categories.forEach((cat) => {
        if (cat.per_slot) {
          cat.slots.forEach((slot) => {
            const sum = rows.filter((r) => r.slot === slot.id).reduce((a, r) => a + r.quantity, 0);
            if (sum !== currentPeople) list.push(slot.name + ' : ' + sum + ' / ' + currentPeople);
          });
        } else {
          const sum = rows.filter((r) => r.key === cat.key).reduce((a, r) => a + r.quantity, 0);
          if (sum !== currentPeople) list.push(cat.label + ' : ' + sum + ' / ' + currentPeople);
        }
      });
      return list;
    };

    const sumBadge = (cat) => {
      const sums = cat.per_slot
        ? cat.slots.map((slot) => rows.filter((r) => r.slot === slot.id).reduce((a, r) => a + r.quantity, 0))
        : [rows.filter((r) => r.key === cat.key).reduce((a, r) => a + r.quantity, 0)];
      const ok = sums.every((s) => s === currentPeople);
      const text = cat.per_slot ? (ok ? 'servi à tous ✓' : 'à répartir') : sums[0] + ' / ' + currentPeople + (ok ? ' ✓' : '');
      return '<span class="compo-sum ' + (ok ? 'is-ok' : 'is-ko') + '">' + text + '</span>';
    };

    const rowHtml = (row, index, cat) => {
      const slot = cat.slots.find((s) => s.id === row.slot);
      const dish = dishById.get(row.dish) || slot;
      const choices = [slot, ...cat.alternatives];
      const options = choices.map((d) => '<option value="' + d.id + '"' + (d.id === row.dish ? ' selected' : '') + '>' +
        (d.id === slot.id ? '' : '↻ ') + escapeHtml(d.name) + '</option>').join('');
      const allergens = dish.allergens || [];
      const without = allergens.length
        ? '<details class="compo-without"' + (row.without.length ? ' open' : '') + '><summary>Sans allergène…</summary>' +
          allergens.map((a) => '<label class="form-check form-check-inline small mb-0"><input class="form-check-input" type="checkbox" data-without="' + index + '" value="' + a.id + '"' +
            (row.without.includes(a.id) ? ' checked' : '') + '> sans ' + escapeHtml(a.name.toLowerCase()) + '</label>').join('') + '</details>'
        : '<span class="small text-muted">Sans allergène majeur</span>';
      const isVariant = rows.findIndex((r) => r.slot === row.slot) !== index;
      return '<div class="compo-row' + (row.without.length ? ' is-variant' : '') + '">' +
        '<div class="compo-row__dish"><label class="visually-hidden" for="compo-dish-' + index + '">Plat</label>' +
        '<select class="form-select form-select-sm" id="compo-dish-' + index + '" data-dish="' + index + '">' + options + '</select>' +
        (allergens.length ? '<span class="small text-muted d-block mt-1">Allergènes : ' + escapeHtml(allergens.map((a) => a.name).join(', ')) + '</span>' : '') +
        '</div>' +
        '<div class="input-group input-group-sm compo-qty" title="Nombre de convives">' +
        '<button class="btn btn-outline-secondary" type="button" data-qty="' + index + '" data-step="-1" aria-label="Un de moins">−</button>' +
        '<input class="form-control text-center" type="number" min="0" max="999" value="' + row.quantity + '" data-qty-input="' + index + '" aria-label="Convives">' +
        '<button class="btn btn-outline-secondary" type="button" data-qty="' + index + '" data-step="1" aria-label="Un de plus">+</button></div>' +
        '<div class="compo-row__opts">' + without +
        '<div class="mt-1"><button type="button" class="btn btn-link btn-sm p-0" data-variant="' + index + '">+ variante pour quelques convives</button>' +
        (isVariant ? ' · <button type="button" class="btn btn-link btn-sm p-0 text-danger" data-remove="' + index + '">retirer</button>' : '') + '</div></div>' +
        '</div>';
    };

    const hidden = () => rows.map((row, i) =>
      '<input type="hidden" name="items[' + i + '][slot]" value="' + row.slot + '">' +
      '<input type="hidden" name="items[' + i + '][dish]" value="' + row.dish + '">' +
      '<input type="hidden" name="items[' + i + '][quantity]" value="' + row.quantity + '">' +
      row.without.map((a) => '<input type="hidden" name="items[' + i + '][without][]" value="' + a + '">').join('')
    ).join('');

    const render = () => {
      container.innerHTML = categories.map((cat) => {
        const indexes = rows.map((r, i) => [r, i]).filter(([r]) => r.key === cat.key);
        const hint = cat.per_slot
          ? 'Servis à chaque convive. Vous pouvez en remplacer ou demander une variante.'
          : (cat.slots.length > 1 ? 'Répartissez les convives entre les choix.' : 'Le même pour tous, ou remplacez-le / faites une variante.');
        return '<fieldset class="compo-cat"><legend class="compo-cat__head"><span>' + escapeHtml(cat.label) + '</span>' + sumBadge(cat) + '</legend>' +
          '<p class="small text-muted mb-2">' + hint + '</p>' +
          indexes.map(([row, i]) => rowHtml(row, i, cat)).join('') + '</fieldset>';
      }).join('') + '<div class="compo-hidden">' + hidden() + '</div>';
      onChange(problems());
    };

    container.addEventListener('click', (event) => {
      const t = event.target.closest('button');
      if (!t) return;
      if (t.dataset.qty !== undefined) {
        const row = rows[Number(t.dataset.qty)];
        row.quantity = Math.max(0, Math.min(999, row.quantity + Number(t.dataset.step)));
      } else if (t.dataset.variant !== undefined) {
        const source = rows[Number(t.dataset.variant)];
        const take = source.quantity > 0 ? 1 : 0;
        source.quantity -= take;
        rows.splice(Number(t.dataset.variant) + 1, 0, { ...source, quantity: take, without: [] });
      } else if (t.dataset.remove !== undefined) {
        const index = Number(t.dataset.remove);
        const removed = rows.splice(index, 1)[0];
        const first = rows.find((r) => r.slot === removed.slot);
        if (first) first.quantity += removed.quantity; // les convives reviennent sur la ligne principale
      } else {
        return;
      }
      render();
    });

    container.addEventListener('change', (event) => {
      const t = event.target;
      if (t.dataset.dish !== undefined) {
        const row = rows[Number(t.dataset.dish)];
        row.dish = Number(t.value);
        row.without = []; // allergènes propres au nouveau plat
      } else if (t.dataset.qtyInput !== undefined) {
        rows[Number(t.dataset.qtyInput)].quantity = Math.max(0, Math.min(999, Math.floor(Number(t.value) || 0)));
      } else if (t.dataset.without !== undefined) {
        const row = rows[Number(t.dataset.without)];
        const id = Number(t.value);
        row.without = t.checked ? [...new Set([...row.without, id])].sort((a, b) => a - b) : row.without.filter((a) => a !== id);
      } else {
        return;
      }
      render();
    });

    const setPeople = (people) => {
      if (!people || people === currentPeople) return;
      const groups = new Map();
      rows.forEach((row, i) => groups.set(groupKey(row), [...(groups.get(groupKey(row)) || []), i]));
      groups.forEach((indexes) => {
        const next = rescale(indexes.map((i) => rows[i].quantity), currentPeople, people);
        indexes.forEach((i, k) => { rows[i].quantity = next[k]; });
      });
      currentPeople = people;
      if (categories.length) render();
    };

    const load = async () => {
      container.innerHTML = '<p class="small text-muted">Chargement des choix du menu…</p>';
      try {
        const data = await window.VgApi.get('/orders/menu-composition?menu_id=' + encodeURIComponent(menuId));
        categories = data.categories || [];
        categories.forEach((cat) => [...cat.slots, ...cat.alternatives].forEach((d) => dishById.set(d.id, d)));
        if (Array.isArray(items) && items.length) {
          rows = items.map((it) => ({ key: it.category, slot: it.slot_dish_id, dish: it.dish_id, quantity: it.quantity, without: it.without_allergens || [] }));
        } else {
          defaults();
        }
        render();
      } catch (error) {
        container.innerHTML = '<p class="alert alert-warning small">' + escapeHtml(error?.message || 'Choix indisponibles.') + ' Le menu sera servi tel quel.</p>';
        categories = [];
        rows = [];
        onChange([]);
      }
    };

    void load();
    return { setPeople, problems };
  }

  window.VgComposition = { mount };

  // modification d'une commande existante (Mes commandes, espace équipe)
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-composition]').forEach((box) => {
      const form = box.closest('form');
      const peopleField = form?.querySelector('input[name="number_of_people"]');
      let items = [];
      try { items = JSON.parse(box.dataset.items || '[]'); } catch (_) { items = []; }
      const message = form?.querySelector('[data-composition-message]');
      const api = mount(box, {
        menuId: box.dataset.menuId,
        people: Number(peopleField?.value || 1),
        items,
        onChange: (list) => {
          if (message) message.textContent = list.length ? 'À répartir : ' + list.join(' ; ') : '';
        }
      });
      peopleField?.addEventListener('input', () => api.setPeople(Number(peopleField.value)));
      form?.addEventListener('submit', (event) => {
        const list = api.problems();
        if (list.length) {
          event.preventDefault();
          if (message) message.textContent = 'À répartir avant d’enregistrer : ' + list.join(' ; ');
        }
      });
    });
  });
})();
