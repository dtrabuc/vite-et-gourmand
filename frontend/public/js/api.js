window.API_CONFIG = {
  baseUrl: localStorage.getItem('vg_api_base_url') || `${window.location.origin}/api`
};

window.storage = {
  get(key, fallback = null) {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch {
      return fallback;
    }
  },
  set(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
  },
  remove(key) {
    localStorage.removeItem(key);
  }
};

window.apiClient = {
  get token() {
    return localStorage.getItem('vg_token');
  },
  setSession({ token, user }) {
    if (token) localStorage.setItem('vg_token', token);
    if (user) window.storage.set('vg_user', user);
  },
  clearAuth() {
    localStorage.removeItem('vg_token');
    localStorage.removeItem('vg_user');
  },
  async request(path, options = {}) {
    const headers = {
      'Content-Type': 'application/json',
      ...(options.headers || {})
    };

    if (this.token) {
      headers.Authorization = `Bearer ${this.token}`;
    }

    const response = await fetch(`${window.API_CONFIG.baseUrl}${path}`, {
      ...options,
      headers
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.message || 'Erreur API');
    }
    return data;
  }
};
