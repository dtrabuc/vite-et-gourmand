// Notifications en direct : la cloche affiche le nombre de non lues et une
// bulle (toast) apparaît pour chaque nouvelle notification, toutes les 30 s.
(() => {
  const bell = document.getElementById('notificationBell');
  const count = document.getElementById('notificationCount');
  const area = document.getElementById('toastArea');
  if (!bell || !count || !area) return;

  const KEY = 'vg_notif_last';
  const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  const setCount = (unread) => {
    count.textContent = unread > 99 ? '99+' : String(unread);
    count.classList.toggle('d-none', unread < 1);
    bell.setAttribute('aria-label', 'Notifications' + (unread > 0 ? ' : ' + unread + ' non lue' + (unread > 1 ? 's' : '') : ''));
  };

  const toast = (item) => {
    const el = document.createElement('div');
    el.className = 'toast vg-toast';
    el.setAttribute('role', 'status');
    el.innerHTML = '<div class="toast-header"><strong class="me-auto">🔔 ' + escapeHtml(item.title) + '</strong>' +
      '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fermer"></button></div>' +
      '<div class="toast-body">' + escapeHtml(item.message) +
      '<div class="mt-2"><a class="btn btn-sm btn-primary" href="' + escapeHtml(item.link) + '">Voir</a></div></div>';
    area.appendChild(el);
    window.bootstrap.Toast.getOrCreateInstance(el, { delay: 9000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  };

  const poll = async () => {
    let last = 0;
    try { last = Number(sessionStorage.getItem(KEY) || 0); } catch (_) { last = 0; }
    try {
      const data = await window.VgApi.get('/notifications/feed?after=' + last);
      setCount(Number(data.unread || 0));
      // première visite de la session : on note la dernière sans tout réafficher
      if (last > 0) (data.items || []).slice(0, 3).reverse().forEach(toast);
      sessionStorage.setItem(KEY, String(data.last_id || last));
    } catch (_) { /* déconnecté ou hors ligne : on réessaiera */ }
  };

  void poll();
  window.setInterval(() => { if (!document.hidden) void poll(); }, 30000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) void poll(); });
})();
