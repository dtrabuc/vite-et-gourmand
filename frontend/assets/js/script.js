class ViteGourmand {
  constructor() {
    this.api = window.apiClient;
    this.catalog = null;
    this.menus = [];
    this.aLaCarte = {};
    this.boissons = { sans: [], avec: [] };
    this.digestifs = [];
    this.selectedMenu = null;
    this.serviceType = 'menu';
    document.addEventListener('DOMContentLoaded', () => this.init());
  }

  get page() {
    const bodyPage = document.body?.dataset?.page;
    if (bodyPage) return bodyPage;
    const fileName = window.location.pathname.split('/').pop() || 'index.html';
    return fileName.replace('.html', '') || 'index';
  }

  async init() {
    this.bindGlobalUi();

    switch (this.page) {
      case 'index':
        await this.initIndex();
        break;
      case 'menus':
        await this.initMenus();
        break;
      case 'detail-menus':
        await this.initDetailMenus();
        break;
      case 'commander':
        await this.initCommander();
        break;
      case 'recap-commande':
        await this.initRecap();
        break;
      case 'contact':
        this.initContact();
        break;
      case 'login':
        this.initLogin();
        break;
      case 'compte':
        await this.initCompte();
        break;
      default:
        break;
    }
  }

  bindGlobalUi() {
    document.getElementById('logoutBtn')?.addEventListener('click', () => this.logout());
  }

  formatPrix(value) {
    return Number(value || 0).toLocaleString('fr-FR', {
      style: 'currency',
      currency: 'EUR'
    });
  }

  go(path) {
    window.location.href = path;
  }

  getStoredUser() {
    return window.storage.get('vg_user');
  }

  setStoredUser(user) {
    window.storage.set('vg_user', user);
  }

  notify(message, type = 'warning') {
    const container = document.getElementById('notificationArea');
    if (!container) {
      alert(message);
      return;
    }

    container.innerHTML = `<div class="alert alert-${type} shadow-sm">${message}</div>`;
  }

  async loadCatalog(force = false) {
    if (this.catalog && !force) return this.catalog;

    const response = await this.api.request('/public/catalog');
    this.catalog = response.data;
    this.menus = response.data.menus || [];
    this.aLaCarte = response.data.aLaCarte || {};
    this.boissons = response.data.boissons || { sans: [], avec: [] };
    this.digestifs = response.data.digestifs || [];

    return this.catalog;
  }

  async initIndex() {
    const target = document.getElementById('avisClients');
    if (!target) return;

    const response = await this.api.request('/public/reviews');
    const avis = response.data.avis || [];

    target.innerHTML = avis.map((avisItem) => `
      <div class="col-md-4">
        <div class="card p-4 shadow-sm h-100 border-0">
          <div class="text-warning mb-3">${'★'.repeat(Math.max(0, Number(avisItem.note || 0)))}</div>
          <p class="fst-italic mb-3">"${avisItem.commentaire}"</p>
          <small class="text-muted fw-bold">- ${avisItem.auteur}</small>
        </div>
      </div>
    `).join('');
  }

  async initMenus() {
    await this.loadCatalog();
    this.renderMenus('all');
    this.renderALaCarte();

    document.querySelectorAll('.filtre').forEach((button) => {
      button.addEventListener('click', () => {
        document.querySelectorAll('.filtre').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
        this.renderMenus(button.dataset.categorie || 'all');
      });
    });
  }

  renderMenus(category) {
    const grid = document.getElementById('grilleMenus');
    if (!grid) return;

    const items = category === 'all'
      ? this.menus
      : this.menus.filter((menu) => menu.categorie === category);

    grid.innerHTML = items.map((menu) => `
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0">
          <img src="${menu.image}" class="card-img-top" alt="${menu.nom}" style="height:220px;object-fit:cover;">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between mb-2 gap-3">
              <h3 class="fw-bold text-primary mb-1 h5">${menu.nom}</h3>
              <span class="badge bg-primary align-self-start">${this.formatPrix(menu.prix)}</span>
            </div>
            <p class="text-muted small flex-grow-1 mb-3">${menu.description || ''}</p>
            <div class="bg-light p-3 rounded mb-3 border small text-muted">
              <p class="mb-1"><strong>Entrees :</strong> ${(menu.plats?.entrees || []).slice(0, 2).join(', ') || 'A definir'}</p>
              <p class="mb-1"><strong>Plats :</strong> ${(menu.plats?.plats || []).slice(0, 2).join(', ') || 'A definir'}</p>
              <p class="mb-0"><strong>Desserts :</strong> ${(menu.plats?.desserts || []).slice(0, 2).join(', ') || 'A definir'}</p>
            </div>
            <div class="mt-auto d-grid gap-2">
              <a href="./detail-menus.html?id=${menu.id}" class="btn btn-outline-primary">Voir le detail</a>
              <a href="./commander.html?menu=${menu.id}" class="btn btn-primary">Commander</a>
            </div>
          </div>
        </div>
      </div>
    `).join('') || '<div class="col-12"><div class="alert alert-warning">Aucun menu disponible pour cette categorie.</div></div>';
  }

  renderALaCarte() {
    const grid = document.getElementById('grilleCarte');
    if (!grid) return;

    const labels = {
      classiques: 'Les classiques',
      vegan: 'Cuisine vegetale',
      sansPorc: 'Sans porc'
    };

    grid.innerHTML = Object.entries(labels).map(([key, label]) => {
      const items = this.aLaCarte[key] || [];
      const list = items.map((item) => `<li class="mb-2"><i class="fas fa-utensils text-primary me-2 small"></i>${item.nom}</li>`).join('');

      return `
        <div class="col-md-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-primary text-white text-center fw-bold text-uppercase py-3">${label}</div>
            <div class="card-body">
              <ul class="list-unstyled mb-0 small text-muted">${list || '<li>Aucun plat</li>'}</ul>
            </div>
            <div class="card-footer bg-white border-0 text-center pb-3">
              <a href="./commander.html?service=plat&categorie=${key}" class="btn btn-sm btn-outline-secondary rounded-pill px-4">Choisir</a>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  async initDetailMenus() {
    await this.loadCatalog();

    const container = document.getElementById('detailMenus');
    if (!container) return;

    const menuId = new URLSearchParams(window.location.search).get('id');
    const menus = menuId ? this.menus.filter((menu) => menu.id === menuId) : this.menus;

    container.innerHTML = menus.map((menu) => `
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-lg border-0">
          <img src="${menu.image}" class="card-img-top" alt="${menu.nom}" style="height:250px;object-fit:cover;">
          <div class="card-body d-flex flex-column">
            <h3 class="card-title mb-2">${menu.nom}</h3>
            <div class="mb-2">${(menu.tags || []).map((tag) => `<span class="badge bg-info me-2">${tag}</span>`).join('')}</div>
            <p class="card-text text-muted small">${menu.description || ''}</p>
            <div class="mt-2 d-flex justify-content-between align-items-center">
              <strong style="color:#8b2635;font-size:1.2em;">${this.formatPrix(menu.prix)}</strong>
              <span class="small text-muted">Minimum ${menu.personnesMin} pers.</span>
            </div>
            <hr>
            <div class="row mt-3 small">
              <div class="col-12 col-md-4">
                <h6 class="fw-bold">Entrees</h6>
                <ul class="list-unstyled">${(menu.plats?.entrees || []).map((item) => `<li>${item}</li>`).join('')}</ul>
              </div>
              <div class="col-12 col-md-4">
                <h6 class="fw-bold">Plats</h6>
                <ul class="list-unstyled">${(menu.plats?.plats || []).map((item) => `<li>${item}</li>`).join('')}</ul>
              </div>
              <div class="col-12 col-md-4">
                <h6 class="fw-bold">Desserts</h6>
                <ul class="list-unstyled">${(menu.plats?.desserts || []).map((item) => `<li>${item}</li>`).join('')}</ul>
              </div>
            </div>
            <div class="alert alert-warning small mt-3">${menu.conditions || 'Conditions communiquees lors de la confirmation.'}</div>
            <div class="mt-auto pt-3">
              <a href="./commander.html?menu=${menu.id}" class="btn btn-primary w-100">Commander cette formule</a>
            </div>
          </div>
        </div>
      </div>
    `).join('') || '<div class="col-12"><div class="alert alert-warning">Menu introuvable.</div></div>';
  }

  async initCommander() {
    await this.loadCatalog();

    document.querySelectorAll('.service-selector').forEach((card) => {
      card.addEventListener('click', () => {
        this.serviceType = card.dataset.type || 'menu';
        this.showCommanderStep();
        this.populateSelectMenu();
      });
    });

    document.getElementById('selectMenu')?.addEventListener('change', (event) => {
      this.onOfferChange(event.target.value);
    });

    document.getElementById('btnVersRecap')?.addEventListener('click', () => this.goToRecap());

    document.querySelectorAll('input[name="typeBoisson"]').forEach((input) => {
      input.addEventListener('change', () => this.renderBoissons());
    });

    const params = new URLSearchParams(window.location.search);
    const presetMenu = params.get('menu');
    const presetService = params.get('service');
    const presetCategory = params.get('categorie');

    if (presetService === 'plat') {
      this.serviceType = 'plat';
    }

    if (presetMenu || presetService) {
      this.showCommanderStep();
      this.populateSelectMenu(presetCategory || '');
      if (presetMenu) {
        document.getElementById('selectMenu').value = presetMenu;
        this.onOfferChange(presetMenu);
      }
    }
  }

  showCommanderStep() {
    document.getElementById('etape0').style.display = 'none';
    document.getElementById('etape1').style.display = 'block';
  }

  populateSelectMenu(category = '') {
    const select = document.getElementById('selectMenu');
    const title = document.getElementById('titre-dynamique');
    if (!select || !title) return;

    select.innerHTML = '<option value="">-- Selectionnez une formule --</option>';

    if (this.serviceType === 'menu') {
      title.textContent = 'Votre menu';
      this.menus.forEach((menu) => {
        const option = document.createElement('option');
        option.value = menu.id;
        option.textContent = `${menu.nom} - ${this.formatPrix(menu.prix)}`;
        select.appendChild(option);
      });
      return;
    }

    title.textContent = 'Votre formule a la carte';
    const items = category
      ? (this.aLaCarte[category] || [])
      : Object.values(this.aLaCarte).flat();

    items.forEach((item) => {
      const option = document.createElement('option');
      option.value = item.id;
      option.textContent = `${item.nom} - ${this.formatPrix(item.prix)}`;
      select.appendChild(option);
    });
  }

  onOfferChange(value) {
    const offers = this.serviceType === 'menu'
      ? this.menus
      : Object.values(this.aLaCarte).flat();

    this.selectedMenu = offers.find((item) => item.id === value);
    if (!this.selectedMenu) return;

    document.getElementById('detailMenuSelectionne').style.display = 'block';
    document.getElementById('sectionPersonnalisation').style.display = 'block';
    document.getElementById('imageMenu').src = this.selectedMenu.image;
    document.getElementById('nomMenuAffiche').textContent = this.selectedMenu.nom;
    document.getElementById('descriptionMenu').textContent = this.selectedMenu.description || '';
    document.getElementById('prixMenuAffiche').textContent = this.formatPrix(this.selectedMenu.prix);

    if (this.serviceType === 'menu') {
      this.renderChoiceList('listEntrees', 'entree', this.selectedMenu.plats?.entrees || []);
      this.renderChoiceList('listPlats', 'plat', this.selectedMenu.plats?.plats || []);
      this.renderChoiceList('listDesserts', 'dessert', this.selectedMenu.plats?.desserts || []);
    } else {
      document.getElementById('listEntrees').innerHTML = '<p class="text-muted small mb-0">La formule a la carte n impose pas d entree.</p>';
      this.renderChoiceList('listPlats', 'plat', [this.selectedMenu.nom]);
      const desserts = this.menus
        .flatMap((menu) => menu.plats?.desserts || [])
        .filter((item, index, array) => array.indexOf(item) === index);
      this.renderChoiceList('listDesserts', 'dessert', desserts);
    }

    this.renderBoissons();
    this.renderDigestifs();
  }

  renderChoiceList(containerId, name, items) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!items.length) {
      container.innerHTML = '<p class="text-muted small mb-0">Aucune option disponible.</p>';
      return;
    }

    container.innerHTML = items.map((item, index) => `
      <div class="form-check mb-2">
        <input
          class="form-check-input"
          type="radio"
          name="${name}"
          id="${containerId}-${name}-${index}"
          value="${item}"
          ${index === 0 ? 'checked' : ''}
        >
        <label class="form-check-label small" for="${containerId}-${name}-${index}">${item}</label>
      </div>
    `).join('');
  }

  renderBoissons() {
    const type = document.querySelector('input[name="typeBoisson"]:checked')?.value || 'sans';
    this.renderChoiceList('listBoissons', 'boisson', this.boissons[type] || []);
  }

  renderDigestifs() {
    const select = document.getElementById('selectDigestif');
    if (!select) return;

    select.innerHTML = '<option value="">Aucun</option>';
    this.digestifs.forEach((item) => {
      const option = document.createElement('option');
      option.value = item;
      option.textContent = item;
      select.appendChild(option);
    });
  }

  initContact() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    form.addEventListener('submit', (event) => {
      event.preventDefault();

      const demandes = window.storage.get('vg_contact_requests', []);
      demandes.unshift({
        nom: document.getElementById('contactNom')?.value || '',
        email: document.getElementById('contactEmail')?.value || '',
        sujet: document.getElementById('contactSujet')?.value || '',
        message: document.getElementById('contactMessage')?.value || '',
        createdAt: new Date().toISOString()
      });

      window.storage.set('vg_contact_requests', demandes.slice(0, 20));
      form.reset();
      this.notify('Merci, votre demande a bien ete enregistree. Nous reviendrons vers vous sous 24 h ouvrables.', 'success');
    });
  }

  goToRecap() {
    if (!this.selectedMenu) {
      this.notify('Selectionne une formule avant de continuer.');
      return;
    }

    const draft = {
      productId: this.selectedMenu.id,
      serviceType: this.serviceType,
      menu: this.selectedMenu.nom,
      prix: Number(this.selectedMenu.prix),
      personnesMin: Number(this.selectedMenu.personnesMin || 1),
      entree: document.querySelector('input[name="entree"]:checked')?.value || '',
      plat: document.querySelector('input[name="plat"]:checked')?.value || this.selectedMenu.nom,
      dessert: document.querySelector('input[name="dessert"]:checked')?.value || '',
      boisson: document.querySelector('input[name="boisson"]:checked')?.value || '',
      digestif: document.getElementById('selectDigestif')?.value || ''
    };

    sessionStorage.setItem('vg_order_draft', JSON.stringify(draft));
    this.go('./recap-commande.html');
  }

  computeRecap(draft) {
    const convives = Number(document.getElementById('convives')?.value || 1);
    const modeService = document.querySelector('input[name="modeService"]:checked')?.value || 'surPlace';
    const city = document.getElementById('eventCity')?.value || 'Bordeaux';
    let unitPrice = Number(draft.prix || 0);
    let digestifLabel = draft.digestif || 'Aucun';
    const reductions = [];

    if (draft.digestif && modeService === 'surPlace') {
      unitPrice += 8;
    } else if (draft.digestif) {
      digestifLabel = 'Non inclus en livraison';
    }

    let total = unitPrice * convives;

    if (total > 100) {
      total *= 0.9;
      reductions.push('10% (commande > 100 EUR)');
    }

    if (convives > 6) {
      total *= 0.75;
      reductions.push('25% (plus de 6 convives)');
    }

    const deliveryFee = city.trim().toLowerCase() !== 'bordeaux' ? 5 : 0;
    total += deliveryFee;

    return {
      convives,
      modeService,
      city,
      digestifLabel,
      reductions,
      deliveryFee,
      total: Number(total.toFixed(2))
    };
  }

  async initRecap() {
    const draft = JSON.parse(sessionStorage.getItem('vg_order_draft') || 'null');
    if (!draft) {
      this.go('./commander.html');
      return;
    }

    const user = this.getStoredUser();
    if (user) {
      document.getElementById('firstName').value = user.firstName || '';
      document.getElementById('lastName').value = user.lastName || '';
    }

    const render = () => {
      const recap = this.computeRecap(draft);
      document.getElementById('affichageRecap').innerHTML = `
        <div class="mb-3">
          <h5 class="text-primary">${draft.menu}</h5>
          <small class="text-muted d-block">Entree : ${draft.entree || 'Non selectionnee'}</small>
          <small class="text-muted d-block">Plat : ${draft.plat || 'Non selectionne'}</small>
          <small class="text-muted d-block">Dessert : ${draft.dessert || 'Non selectionne'}</small>
          <small class="text-muted d-block">Boisson : ${draft.boisson || 'Non selectionnee'}</small>
          <small class="text-muted d-block">Digestif : ${recap.digestifLabel}</small>
          <small class="text-muted d-block">Mode : ${recap.modeService === 'surPlace' ? 'Service sur place' : 'Livraison / retrait'}</small>
          <small class="text-muted d-block">Convives : ${recap.convives}</small>
          <small class="text-muted d-block">Livraison : ${this.formatPrix(recap.deliveryFee)}</small>
        </div>
      `;

      document.getElementById('affichageRabais').innerHTML = recap.reductions.length
        ? `<div class="alert alert-success p-2 small mb-0"><i class="fas fa-tags me-1"></i>${recap.reductions.join(' + ')}</div>`
        : '<small class="text-muted">Aucune reduction automatique</small>';

      document.getElementById('totalFinal').textContent = this.formatPrix(recap.total);
      return recap;
    };

    ['convives', 'eventCity'].forEach((id) => {
      document.getElementById(id)?.addEventListener('input', render);
    });

    document.querySelectorAll('input[name="modeService"]').forEach((input) => {
      input.addEventListener('change', render);
    });

    render();

    document.getElementById('formFinal')?.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!this.api.token) {
        this.notify('Connecte-toi avant de confirmer une commande.');
        this.go('./login.html');
        return;
      }

      const recap = render();

      if (recap.convives < draft.personnesMin) {
        this.notify(`Le minimum requis est de ${draft.personnesMin} personnes.`, 'danger');
        return;
      }

      try {
        const response = await this.api.request('/orders', {
          method: 'POST',
          body: JSON.stringify({
            items: [{ productId: draft.productId, quantity: recap.convives }],
            shippingAddress: {
              street: document.getElementById('eventStreet').value,
              city: document.getElementById('eventCity').value,
              zipCode: document.getElementById('eventZip').value,
              country: 'France',
              date: document.getElementById('eventDate').value,
              time: document.getElementById('eventTime').value,
              deliveryFee: recap.deliveryFee
            },
            paymentMethod: 'on_quote',
            notes: `Commande web - ${draft.menu}`,
            orderDetails: {
              serviceType: draft.serviceType,
              modeService: recap.modeService,
              guestCount: recap.convives,
              entree: draft.entree,
              plat: draft.plat,
              dessert: draft.dessert,
              boisson: draft.boisson,
              digestif: draft.digestif,
              baseUnitPrice: draft.prix,
              discounts: recap.reductions
            }
          })
        });

        this.notify(`Commande confirmee : ${response.data.order.orderNumber}`, 'success');
        sessionStorage.removeItem('vg_order_draft');
        this.go('./compte.html');
      } catch (error) {
        this.notify(error.message, 'danger');
      }
    });
  }

  initLogin() {
    document.getElementById('formLogin')?.addEventListener('submit', async (event) => {
      event.preventDefault();

      try {
        const response = await this.api.request('/auth/login', {
          method: 'POST',
          body: JSON.stringify({
            email: document.getElementById('loginEmail').value,
            password: document.getElementById('loginPassword').value
          })
        });

        this.api.setSession(response.data);
        this.go(response.data.user.role === 'admin' ? './admin-dashboard.html' : './compte.html');
      } catch (error) {
        this.notify(error.message, 'danger');
      }
    });

    document.getElementById('formRegister')?.addEventListener('submit', async (event) => {
      event.preventDefault();

      const password = document.getElementById('registerPassword').value;
      const passwordConfirm = document.getElementById('registerPasswordConfirm').value;

      if (password !== passwordConfirm) {
        this.notify('Les mots de passe ne correspondent pas.', 'danger');
        return;
      }

      try {
        const response = await this.api.request('/auth/register', {
          method: 'POST',
          body: JSON.stringify({
            username: document.getElementById('registerUsername').value,
            email: document.getElementById('registerEmail').value,
            password,
            firstName: document.getElementById('registerFirstName').value,
            lastName: document.getElementById('registerLastName').value,
            role: 'user'
          })
        });

        this.api.setSession(response.data);
        this.go('./compte.html');
      } catch (error) {
        this.notify(error.message, 'danger');
      }
    });
  }

  statusBadgeClass(status) {
    return status === 'En attente' ? 'bg-warning text-dark' : 'bg-success';
  }

  async initCompte() {
    if (!this.api.token) {
      this.go('./login.html');
      return;
    }

    try {
      const profile = await this.api.request('/auth/profile');
      this.setStoredUser(profile.data.user);

      document.getElementById('compteNom').textContent = `${profile.data.user.firstName || ''} ${profile.data.user.lastName || ''}`.trim() || profile.data.user.username;
      document.getElementById('compteRole').textContent = profile.data.user.role === 'admin' ? 'Profil administrateur' : 'Espace client';

      const ordersResponse = await this.api.request('/orders');
      const orders = ordersResponse.data.orders || [];

      document.getElementById('tableCommandes').innerHTML = orders.map((order) => `
        <tr>
          <td>${order.orderNumber}</td>
          <td>${order.orderDetails?.serviceType === 'plat' ? 'A la carte' : (order.items?.[0]?.product?.name || 'Menu')}</td>
          <td>${new Date(order.createdAt).toLocaleDateString('fr-FR')}</td>
          <td>${this.formatPrix(order.totalAmount)}</td>
          <td><span class="badge ${this.statusBadgeClass(order.status)}">${order.status}</span></td>
        </tr>
      `).join('') || '<tr><td colspan="5" class="text-center text-muted">Aucune commande pour le moment.</td></tr>';
    } catch (error) {
      this.logout();
    }
  }

  logout() {
    this.api.clearAuth();
    this.go('./login.html');
  }
}

window.VG = new ViteGourmand();
