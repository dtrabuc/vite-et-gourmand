window.API_CONFIG = {
  baseUrl: localStorage.getItem('vg_api_base_url') || `${window.location.origin}/api`,
  preferMock: localStorage.getItem('vg_front_demo') !== '0'
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

window.VG_DEMO_DATA = window.VG_DEMO_DATA || {
  catalog: {
    menus: [
      {
        id: 'menu-prestige',
        nom: 'Menu Prestige',
        description: 'Une formule haut de gamme pensee pour les receptions elegantes, mariages et soirees d entreprise.',
        prix: 49.5,
        categorie: 'premium',
        personnesMin: 10,
        tags: ['Mariage', 'Signature'],
        conditions: 'Reservation conseillee au moins 7 jours a l avance. Service sur place possible selon disponibilite.',
        image: 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&auto=format&fit=crop',
        plats: {
          entrees: ['Foie gras maison et chutney de figues', 'Saint-Jacques snackees et beurre d agrumes'],
          plats: ['Filet de boeuf sauce truffee', 'Homard grille et legumes de saison'],
          desserts: ['Sphere chocolat grand cru', 'Paris-Brest pistache', 'Millefeuille vanille bourbon']
        }
      },
      {
        id: 'menu-vegetarien-delice',
        nom: 'Menu Vegetarien Delice',
        description: 'Des assiettes gourmandes et saisonnieres pour un repas vegetarien aussi genereux qu elegant.',
        prix: 35,
        categorie: 'vegetarien',
        personnesMin: 8,
        tags: ['Vegetarien', 'Saisonnier'],
        conditions: 'Commande confirmee 5 jours avant la prestation. Options sans lactose sur demande.',
        image: 'https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop',
        plats: {
          entrees: ['Gaspacho de tomates anciennes', 'Tartare avocat mangue et herbes fraiches'],
          plats: ['Risotto cremeux aux cepes et parmesan', 'Tarte fine aux legumes du soleil'],
          desserts: ['Pavlova aux fruits exotiques', 'Moelleux chocolat et fruits rouges']
        }
      },
      {
        id: 'menu-cocktail-chic',
        nom: 'Menu Cocktail Chic',
        description: 'Ideal pour les lancements, cocktails dinatoires et evenements ou le service doit rester fluide.',
        prix: 42,
        categorie: 'premium',
        personnesMin: 15,
        tags: ['Cocktail', 'Entreprise'],
        conditions: 'Commande recommandee 10 jours avant. Vaisselle premium et personnel de service en option.',
        image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1200&auto=format&fit=crop',
        plats: {
          entrees: ['Mini burgers foie gras et figue', 'Verrines saumon gravlax et aneth'],
          plats: ['Brochettes yakitori de poulet', 'Gambas tempura sauce aigre-douce'],
          desserts: ['Assortiment de macarons', 'Mini eclairs chocolat, cafe et vanille']
        }
      },
      {
        id: 'menu-enfant-gourmand',
        nom: 'Menu Enfant Gourmand',
        description: 'Une formule rassurante et equilibree pour les anniversaires, receptions familiales et baptêmes.',
        prix: 18,
        categorie: 'enfant',
        personnesMin: 5,
        tags: ['Famille', 'Enfants'],
        conditions: 'Reservation conseillee 3 jours avant. Couverts et serviettes inclus pour les petits convives.',
        image: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=1200&auto=format&fit=crop',
        plats: {
          entrees: ['Roule jambon fromage', 'Veloute doux de potimarron', 'Taboule colore'],
          plats: ['Nuggets de poulet maison et frites', 'Pates bolognaise', 'Filet de poisson pane et puree'],
          desserts: ['Brownie chocolat noix', 'Coupe de glaces artisanales', 'Salade de fruits frais']
        }
      }
    ],
    aLaCarte: {
      classiques: [
        {
          id: 'plat-piece-boucher',
          nom: 'Piece du boucher et frites maison',
          description: 'Une assiette genereuse pour les dejeuners et prestations de groupe sans prise de tete.',
          prix: 16.5,
          personnesMin: 1,
          image: 'https://images.unsplash.com/photo-1558030006-450675393462?w=1200&auto=format&fit=crop'
        },
        {
          id: 'plat-burger-chef',
          nom: 'Burger du Chef',
          description: 'Pain brioche, cantal, bacon, oignons confits et potatoes croustillantes.',
          prix: 16.5,
          personnesMin: 1,
          image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=1200&auto=format&fit=crop'
        }
      ],
      vegan: [
        {
          id: 'plat-buddha-bowl',
          nom: 'Buddha Bowl complet',
          description: 'Quinoa, avocat, pois chiches et legumes croquants pour une option fraiche et complete.',
          prix: 16.5,
          personnesMin: 1,
          image: 'https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop'
        },
        {
          id: 'plat-wok-tofu',
          nom: 'Wok de tofu marine',
          description: 'Nouilles sautees, legumes de saison et marinade parfumee au gingembre.',
          prix: 16.5,
          personnesMin: 1,
          image: 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=1200&auto=format&fit=crop'
        }
      ],
      sansPorc: [
        {
          id: 'plat-couscous-royal',
          nom: 'Couscous Royal',
          description: 'Semoule parfumee, legumes mijotes, poulet et merguez boeuf.',
          prix: 16.5,
          personnesMin: 1,
          image: 'https://images.unsplash.com/photo-1547592180-85f173990554?w=1200&auto=format&fit=crop'
        },
        {
          id: 'plat-saumon-grille',
          nom: 'Pave de saumon grille',
          description: 'Haricots verts, citron confit et sauce legere aux herbes.',
          prix: 16.5,
          personnesMin: 1,
          image: 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=1200&auto=format&fit=crop'
        }
      ]
    },
    boissons: {
      sans: ['Eaux aromatisees', 'Jus de pomme artisanal', 'Jus d orange presse', 'Citronnade maison', 'The glace peche'],
      avec: ['Vin rouge', 'Vin blanc', 'Champagne', 'Biere artisanale']
    },
    digestifs: ['Cognac VSOP', 'Armagnac', 'Limoncello artisanal', 'Calvados', 'Get 27']
  },
  users: [
    {
      id: 'user-demo',
      username: 'clientdemo',
      email: 'client@vite-gourmand.local',
      password: 'Client123!',
      firstName: 'Dylan',
      lastName: 'Martin',
      role: 'user'
    },
    {
      id: 'user-admin',
      username: 'admin',
      email: 'admin@test.com',
      password: 'Admin1234!',
      firstName: 'Julie',
      lastName: 'Bernard',
      role: 'admin'
    },
    {
      id: 'user-amelie',
      username: 'amelie',
      email: 'amelie@vite-gourmand.local',
      password: 'Amelie123!',
      firstName: 'Amelie',
      lastName: 'Roux',
      role: 'user'
    }
  ],
  reviews: [
    { id: 'review-1', auteur: 'Jean Dupont', note: 5, commentaire: 'Service impeccable, buffet tres apprecie et equipe ponctuelle du debut a la fin.' },
    { id: 'review-2', auteur: 'Sophie Martin', note: 4, commentaire: 'Nous avons choisi le Menu Cocktail Chic pour un lancement client, tout etait fluide et tres bon.' },
    { id: 'review-3', auteur: 'Lucas Bernard', note: 5, commentaire: 'Les options vegetariennes ont vraiment fait la difference pour notre reception familiale.' }
  ]
};

const VG_DEMO_DB_KEY = 'vg_demo_db';

const deepClone = (value) => JSON.parse(JSON.stringify(value));

function toPublicUser(user) {
  if (!user) return null;
  const { password, ...safeUser } = user;
  return safeUser;
}

function getCatalogProduct(productId) {
  const catalog = window.VG_DEMO_DATA.catalog;
  return [
    ...catalog.menus,
    ...Object.values(catalog.aLaCarte).flat()
  ].find((item) => item.id === productId) || null;
}

function buildInitialOrders() {
  return [
    {
      id: 'order-1',
      orderNumber: 'VG-2026-001',
      userId: 'user-demo',
      createdAt: '2026-04-08T10:30:00.000Z',
      totalAmount: 445.5,
      status: 'Confirmee',
      items: [
        {
          productId: 'menu-prestige',
          quantity: 10,
          product: { name: 'Menu Prestige' }
        }
      ],
      shippingAddress: {
        street: '18 quai des Chartrons',
        city: 'Bordeaux',
        zipCode: '33000',
        country: 'France',
        date: '2026-05-12',
        time: '19:30',
        deliveryFee: 0
      },
      orderDetails: {
        serviceType: 'menu',
        modeService: 'surPlace',
        guestCount: 10,
        entree: 'Foie gras maison et chutney de figues',
        plat: 'Filet de boeuf sauce truffee',
        dessert: 'Sphere chocolat grand cru',
        boisson: 'Champagne',
        digestif: 'Cognac VSOP',
        baseUnitPrice: 49.5,
        discounts: ['10% (total > 100 EUR)', '25% (> 6 personnes)']
      }
    },
    {
      id: 'order-2',
      orderNumber: 'VG-2026-002',
      userId: 'user-amelie',
      createdAt: '2026-04-11T08:45:00.000Z',
      totalAmount: 203,
      status: 'En attente',
      items: [
        {
          productId: 'menu-vegetarien-delice',
          quantity: 6,
          product: { name: 'Menu Vegetarien Delice' }
        }
      ],
      shippingAddress: {
        street: '4 avenue Thiers',
        city: 'Merignac',
        zipCode: '33700',
        country: 'France',
        date: '2026-05-03',
        time: '12:00',
        deliveryFee: 5
      },
      orderDetails: {
        serviceType: 'menu',
        modeService: 'livraison',
        guestCount: 6,
        entree: 'Tartare avocat mangue et herbes fraiches',
        plat: 'Risotto cremeux aux cepes et parmesan',
        dessert: 'Pavlova aux fruits exotiques',
        boisson: 'Jus d orange presse',
        digestif: '',
        baseUnitPrice: 35,
        discounts: ['10% (total > 100 EUR)']
      }
    }
  ];
}

function buildInitialLogs() {
  return [
    {
      id: 'log-1',
      createdAt: '2026-04-12T09:10:00.000Z',
      level: 'info',
      action: 'catalog_loaded',
      message: 'Chargement de la carte publique.'
    },
    {
      id: 'log-2',
      createdAt: '2026-04-12T10:30:00.000Z',
      level: 'info',
      action: 'order_created',
      message: 'La commande VG-2026-001 a ete creee.'
    },
    {
      id: 'log-3',
      createdAt: '2026-04-13T08:45:00.000Z',
      level: 'warning',
      action: 'delivery_zone',
      message: 'Une commande hors Bordeaux a applique des frais de livraison.'
    }
  ];
}

function buildInitialDb() {
  return {
    users: deepClone(window.VG_DEMO_DATA.users),
    reviews: deepClone(window.VG_DEMO_DATA.reviews),
    orders: buildInitialOrders(),
    logs: buildInitialLogs()
  };
}

function getDemoDb() {
  const existing = window.storage.get(VG_DEMO_DB_KEY);
  if (existing) return existing;
  const initialDb = buildInitialDb();
  window.storage.set(VG_DEMO_DB_KEY, initialDb);
  return initialDb;
}

function saveDemoDb(db) {
  window.storage.set(VG_DEMO_DB_KEY, db);
}

function createToken(user) {
  return `demo-token-${user.role}-${user.id}`;
}

function getSessionUser(db = getDemoDb()) {
  const sessionUser = window.storage.get('vg_user');
  if (!sessionUser || !localStorage.getItem('vg_token')) return null;
  return db.users.find((user) => user.id === sessionUser.id) || null;
}

function pushLog(db, level, action, message) {
  db.logs.unshift({
    id: `log-${Date.now()}`,
    createdAt: new Date().toISOString(),
    level,
    action,
    message
  });
  db.logs = db.logs.slice(0, 20);
}

function createOrderNumber(db) {
  const index = db.orders.length + 1;
  return `VG-${new Date().getFullYear()}-${String(index).padStart(3, '0')}`;
}

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
  supportsMock(path) {
    const cleanPath = String(path || '').split('?')[0];
    return [
      '/public/catalog',
      '/public/reviews',
      '/auth/login',
      '/auth/register',
      '/auth/profile',
      '/orders',
      '/admin/login',
      '/admin/dashboard'
    ].includes(cleanPath);
  },
  async mockRequest(path, options = {}) {
    const cleanPath = String(path || '').split('?')[0];
    const method = String(options.method || 'GET').toUpperCase();
    const body = options.body ? JSON.parse(options.body) : {};
    const db = getDemoDb();
    const currentUser = getSessionUser(db);

    if (cleanPath === '/public/catalog' && method === 'GET') {
      return { success: true, data: deepClone(window.VG_DEMO_DATA.catalog) };
    }

    if (cleanPath === '/public/reviews' && method === 'GET') {
      return {
        success: true,
        data: {
          avis: db.reviews.map((review) => ({
            auteur: review.auteur,
            note: review.note,
            commentaire: review.commentaire
          }))
        }
      };
    }

    if (cleanPath === '/auth/login' && method === 'POST') {
      const user = db.users.find((item) => item.email === body.email && item.password === body.password);
      if (!user) throw new Error('Identifiants invalides.');
      pushLog(db, 'info', 'login', `Connexion de ${user.email}.`);
      saveDemoDb(db);
      return { success: true, data: { token: createToken(user), user: toPublicUser(user) } };
    }

    if (cleanPath === '/auth/register' && method === 'POST') {
      if (!body.email || !body.password || !body.username) {
        throw new Error('Merci de remplir les champs obligatoires.');
      }
      if (db.users.some((item) => item.email === body.email)) {
        throw new Error('Cet email est deja utilise.');
      }
      if (db.users.some((item) => item.username === body.username)) {
        throw new Error('Ce nom d utilisateur est deja pris.');
      }
      const user = {
        id: `user-${Date.now()}`,
        username: body.username,
        email: body.email,
        password: body.password,
        firstName: body.firstName || '',
        lastName: body.lastName || '',
        role: 'user'
      };
      db.users.push(user);
      pushLog(db, 'info', 'register', `Creation du compte ${user.email}.`);
      saveDemoDb(db);
      return { success: true, data: { token: createToken(user), user: toPublicUser(user) } };
    }

    if (cleanPath === '/auth/profile' && method === 'GET') {
      if (!currentUser) throw new Error('Connexion requise.');
      return { success: true, data: { user: toPublicUser(currentUser) } };
    }

    if (cleanPath === '/orders' && method === 'GET') {
      if (!currentUser) throw new Error('Connexion requise.');
      const orders = db.orders.filter((order) => order.userId === currentUser.id);
      return { success: true, data: { orders: deepClone(orders) } };
    }

    if (cleanPath === '/orders' && method === 'POST') {
      if (!currentUser) throw new Error('Connexion requise.');

      const product = getCatalogProduct(body.items?.[0]?.productId);
      const guestCount = Number(body.orderDetails?.guestCount || body.items?.[0]?.quantity || 1);
      const baseUnitPrice = Number(body.orderDetails?.baseUnitPrice || product?.prix || 0);
      const deliveryFee = Number(body.shippingAddress?.deliveryFee || 0);
      const totalAmount = Number((baseUnitPrice * guestCount + deliveryFee).toFixed(2));
      const order = {
        id: `order-${Date.now()}`,
        orderNumber: createOrderNumber(db),
        userId: currentUser.id,
        createdAt: new Date().toISOString(),
        totalAmount,
        status: 'En attente',
        items: [
          {
            productId: body.items?.[0]?.productId,
            quantity: guestCount,
            product: {
              name: product?.nom || (body.orderDetails?.serviceType === 'plat' ? 'Formule a la carte' : 'Menu')
            }
          }
        ],
        shippingAddress: body.shippingAddress || {},
        orderDetails: body.orderDetails || {}
      };

      db.orders.unshift(order);
      pushLog(db, 'info', 'order_created', `Nouvelle commande ${order.orderNumber}.`);
      saveDemoDb(db);

      return { success: true, data: { order: deepClone(order) } };
    }

    if (cleanPath === '/admin/login' && method === 'POST') {
      const user = db.users.find((item) => item.email === body.email && item.password === body.password && item.role === 'admin');
      if (!user) throw new Error('Acces administrateur refuse.');
      pushLog(db, 'info', 'admin_login', `Connexion admin de ${user.email}.`);
      saveDemoDb(db);
      return { success: true, data: { token: createToken(user), user: toPublicUser(user) } };
    }

    if (cleanPath === '/admin/dashboard' && method === 'GET') {
      if (!currentUser || currentUser.role !== 'admin') throw new Error('Acces administrateur requis.');
      const stats = {
        usersCount: db.users.filter((user) => user.role === 'user').length,
        menusCount: window.VG_DEMO_DATA.catalog.menus.length,
        ordersCount: db.orders.length,
        pendingOrders: db.orders.filter((order) => order.status === 'En attente').length,
        visibleReviews: db.reviews.length,
        notificationsCount: db.logs.length
      };

      const recentOrders = db.orders.slice(0, 5).map((order) => {
        const customer = db.users.find((user) => user.id === order.userId);
        return {
          ...order,
          customer: customer ? {
            firstName: customer.firstName,
            lastName: customer.lastName,
            email: customer.email
          } : null
        };
      });

      const latestReviews = db.reviews.slice(0, 5).map((review) => ({
        username: review.auteur,
        rating: review.note,
        content: review.commentaire
      }));

      const latestLogs = db.logs.slice(0, 8);

      return {
        success: true,
        data: {
          stats,
          recentOrders,
          latestReviews,
          latestLogs
        }
      };
    }

    throw new Error('Cette action n est pas disponible en mode frontend seul.');
  },
  async request(path, options = {}) {
    if (window.API_CONFIG.preferMock && this.supportsMock(path)) {
      return this.mockRequest(path, options);
    }

    const headers = {
      'Content-Type': 'application/json',
      ...(options.headers || {})
    };

    if (this.token) {
      headers.Authorization = `Bearer ${this.token}`;
    }

    try {
      const response = await fetch(`${window.API_CONFIG.baseUrl}${path}`, {
        ...options,
        headers
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok) {
        if (response.status === 404 && this.supportsMock(path)) {
          return this.mockRequest(path, options);
        }
        throw new Error(data.message || 'Erreur API');
      }
      return data;
    } catch (error) {
      if (this.supportsMock(path)) {
        return this.mockRequest(path, options);
      }
      throw error;
    }
  }
};
