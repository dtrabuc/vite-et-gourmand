class AdminInterface {
  constructor() {
    document.addEventListener("DOMContentLoaded", () => this.init());
  }

  get page() {
    return window.location.pathname;
  }

  init() {

    if (this.page === "/admin/login") {
      this.bindLogin();
      return;
    }

    if (this.page === "/admin/dashboard") {
      this.bindLogout();
      this.loadDashboard().catch((error) => {
        console.error(error);
        alert(error.message || "Accès refusé");
        window.location.href = "/admin/login";
      });
    }
  }

  /* =========================
      LOGIN ADMIN
  ========================= */

  bindLogin() {
    const form = document.getElementById("adminLoginForm");
    if (!form) return;

    form.addEventListener("submit", async (event) => {
      event.preventDefault();

      const email = document.getElementById("adminEmail")?.value.trim();
      const password = document.getElementById("adminPassword")?.value;

      if (!email || !password) {
        alert("Veuillez remplir tous les champs");
        return;
      }

      try {

        const response = await window.apiClient.request("/admin/login", {
          method: "POST",
          body: JSON.stringify({
            email,
            password
          })
        });

        if (!response?.data) {
          throw new Error("Réponse serveur invalide");
        }

        window.apiClient.setSession(response.data);

        window.location.href = "/admin/dashboard";

      } catch (error) {
        console.error(error);
        alert(error.message || "Identifiants incorrects");
      }
    });
  }

  /* =========================
      LOGOUT
  ========================= */

  bindLogout() {
    const btn = document.getElementById("adminLogoutBtn");

    if (!btn) return;

    btn.addEventListener("click", () => {
      window.apiClient.clearAuth();
      window.location.href = "/admin/login";
    });
  }

  /* =========================
      UTILS
  ========================= */

  formatPrix(value) {
    return Number(value || 0).toLocaleString("fr-FR", {
      style: "currency",
      currency: "EUR"
    });
  }

  /* =========================
      DASHBOARD
  ========================= */

  async loadDashboard() {

    const response = await window.apiClient.request("/admin/dashboard");

    const { stats, recentOrders, latestReviews, latestLogs } = response.data;

    this.renderStats(stats);
    this.renderOrders(recentOrders || []);
    this.renderReviews(latestReviews || []);
    this.renderLogs(latestLogs || []);
  }

  /* =========================
      STATS
  ========================= */

  renderStats(stats) {

    const target = document.getElementById("adminStats");

    if (!target) return;

    const cards = [
      ["Utilisateurs", stats.usersCount, "fa-users"],
      ["Menus", stats.menusCount, "fa-utensils"],
      ["Commandes", stats.ordersCount, "fa-bag-shopping"],
      ["Commandes en attente", stats.pendingOrders, "fa-hourglass-half"],
      ["Avis visibles", stats.visibleReviews, "fa-star"],
      ["Notifications", stats.notificationsCount, "fa-bell"]
    ];

    target.innerHTML = cards.map(([label, value, icon]) => `
      <div class="col-md-6 col-xl-4">
        <div class="card admin-stat-card shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="text-muted small text-uppercase mb-2">${label}</div>
                <div class="admin-stat-value">${value ?? 0}</div>
              </div>
              <i class="fas ${icon} text-primary fs-3"></i>
            </div>
          </div>
        </div>
      </div>
    `).join("");
  }

  /* =========================
      ORDERS
  ========================= */

  renderOrders(orders) {

    const tbody = document.getElementById("adminOrdersTable");

    if (!tbody) return;

    if (!orders.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Aucune commande récente</td></tr>`;
      return;
    }

    tbody.innerHTML = orders.map((order) => `
      <tr>
        <td>${order.orderNumber}</td>
        <td>
          ${order.customer?.firstName || ""} ${order.customer?.lastName || ""}
          <div class="small text-muted">${order.customer?.email || ""}</div>
        </td>
        <td>${this.formatPrix(order.totalAmount)}</td>
        <td><span class="badge bg-primary">${order.status}</span></td>
      </tr>
    `).join("");
  }

  /* =========================
      REVIEWS
  ========================= */

  renderReviews(reviews) {

    const container = document.getElementById("adminReviewsList");

    if (!container) return;

    if (!reviews.length) {
      container.innerHTML = `<div class="text-muted">Aucun avis récent</div>`;
      return;
    }

    container.innerHTML = reviews.map((review) => `
      <div class="review-snippet">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>${review.username}</strong>
          <span class="badge bg-light text-dark">${review.rating}/5</span>
        </div>
        <p class="mb-1 small">${review.content}</p>
      </div>
    `).join("");
  }

  /* =========================
      LOGS
  ========================= */

  renderLogs(logs) {

    const tbody = document.getElementById("adminLogsTable");

    if (!tbody) return;

    if (!logs.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Aucun log disponible</td></tr>`;
      return;
    }

    tbody.innerHTML = logs.map((log) => `
      <tr>
        <td>${new Date(log.createdAt).toLocaleString("fr-FR")}</td>
        <td><span class="badge bg-secondary">${log.level}</span></td>
        <td>${log.action || "—"}</td>
        <td>${log.message || ""}</td>
      </tr>
    `).join("");
  }
}

window.AdminInterface = new AdminInterface();