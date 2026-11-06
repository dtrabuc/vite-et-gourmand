window.VgApi = {
  async get(path) {
    const response = await fetch(path, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin'
    });

    const text = await response.text();
    let payload = null;

    try {
      payload = text ? JSON.parse(text) : null;
    } catch {
      throw new Error('Le serveur a renvoyé une réponse invalide.');
    }

    if (!response.ok || !payload?.success) {
      throw new Error(payload?.error || 'La requête n’a pas abouti.');
    }

    return payload.data;
  }
};