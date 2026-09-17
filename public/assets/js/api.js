/**
 * Client HTTP minimal : aucune donnée métier n'est conservée dans le navigateur.
 * Les prix, droits, stock et création de commande sont vérifiés en PHP.
 */
window.VgApi = {
  async get(path) {
    const response = await fetch(path, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin'
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok || !payload?.success) {
      throw new Error(payload?.error || 'La requête n’a pas abouti.');
    }

    return payload.data;
  },

  csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }
};
