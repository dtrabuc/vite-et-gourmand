// Formulaires de modification d'une commande (espace client et espace équipe).
(() => {
  const forms = document.querySelectorAll('.order-edit-form');
  if (forms.length === 0) return;

  const todayParis = () => new Intl.DateTimeFormat('fr-CA', { timeZone: 'Europe/Paris' }).format(new Date());

  forms.forEach((form) => {
    const dateField = form.querySelector('.order-edit-date');
    const timeField = form.querySelector('.order-edit-time');
    const message = form.querySelector('.order-edit-time-message');
    const deliveryBlock = form.querySelector('.order-edit-delivery');

    // Créneaux calculés par le serveur à partir des horaires d'ouverture.
    if (dateField && timeField) {
      // La base stocke HH:MM:SS, les créneaux sont au format HH:MM.
      const currentTime = (timeField.dataset.currentTime || '').slice(0, 5);

      const populate = async () => {
        const selectedDate = dateField.value;
        timeField.innerHTML = '<option value="">Choisir</option>';
        timeField.disabled = true;
        if (message) message.textContent = '';
        if (!selectedDate) return;

        try {
          const data = await window.VgApi.get(
            '/orders/availability?date=' + encodeURIComponent(selectedDate)
          ) || {};
          const slots = Array.isArray(data.slots) ? data.slots : [];

          slots.forEach((slot) => {
            const option = document.createElement('option');
            option.value = slot;
            option.textContent = slot;
            if (slot === currentTime) option.selected = true;
            timeField.appendChild(option);
          });

          timeField.disabled = slots.length === 0;
          if (message && slots.length === 0) {
            message.textContent = (data.day_label ? data.day_label + ' : ' : '')
              + 'fermé ou plus aucun créneau disponible.';
          }
        } catch (error) {
          if (message) message.textContent = error?.message || 'Impossible de vérifier la date.';
        }
      };

      dateField.min = todayParis();
      dateField.addEventListener('change', () => void populate());
      void populate();
    }

    // Les champs d'adresse ne concernent que la livraison.
    const syncService = () => {
      const isDelivery = form.querySelector('.order-edit-service:checked')?.value === 'delivery';
      deliveryBlock?.classList.toggle('d-none', !isDelivery);

      ['delivery_address', 'delivery_postal_code', 'delivery_city'].forEach((name) => {
        const field = form.querySelector('[name="' + name + '"]');
        if (field) field.required = isDelivery;
      });
    };

    form.querySelectorAll('.order-edit-service').forEach((input) => {
      input.addEventListener('change', syncService);
    });
    syncService();
  });
})();
