// ================================================================
// INITIALISATION MONGODB — Vite & Gourmand
// Galeries des menus, avis clients et statistiques du tableau de bord.
//
// À lancer APRÈS l'import de database/schema.sql (MariaDB) :
//   mongosh "<chaîne de connexion>" --file database/mongodb-init.js
//
// Comme schema.sql, ce script REPART DE ZÉRO : les 3 collections sont
// supprimées puis recréées, pour rester alignées sur les données SQL
// (menus 1 à 9, client 3, commandes 1001 à 1005).
// ================================================================

const dbName = "viteetgourmand";
const database = db.getSiblingDB(dbName);

// Identifiants SQL fixés par schema.sql
const CLIENT_USER_ID = 3; // user@viteetgourmand.com

// ----------------------------------------------------------------
// Collections et index (recréés à chaque lancement)
// ----------------------------------------------------------------
const collections = ["menu_images", "comments", "menu_statistics"];

for (const name of collections) {
  try {
    database.getCollection(name).drop();
  } catch (e) {
    // Si la collection n'existe pas encore, on ignore l'erreur
  }
  database.createCollection(name);
}

// une seule image par position dans un menu
database.menu_images.createIndex(
  { menuId: 1, position: 1 },
  { name: "menu_images_menu_position_unique", unique: true }
);

// un seul avis par commande et par client
database.comments.createIndex(
  { userId: 1, orderId: 1 },
  { name: "comments_user_order_unique", unique: true }
);

database.comments.createIndex(
  { isValidated: 1, createdAt: -1 },
  { name: "comments_validation_createdAt" }
);

database.comments.createIndex(
  { menuId: 1, createdAt: -1 },
  { name: "comments_menu_createdAt" }
);

// une seule ligne de statistiques par menu et par période
database.menu_statistics.createIndex(
  { menuId: 1, periodIdentifier: 1 },
  { name: "menu_statistics_menu_period_unique", unique: true }
);

// ----------------------------------------------------------------
// Galeries : [menuId, position, url, texte alternatif]
// ----------------------------------------------------------------
const menuImages = [
  [1, 1, "https://images.unsplash.com/photo-1547592180-85f173990554?w=1200&auto=format&fit=crop", "Velouté de légumes et formule solo classique"],
  [1, 2, "https://images.unsplash.com/photo-1543353071-873f17a7a088?w=1200&auto=format&fit=crop", "Steak haché et accompagnement"],
  [2, 1, "https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop", "Assiette végétale"],
  [2, 2, "https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=1200&auto=format&fit=crop", "Curry végétal et riz"],
  [3, 1, "https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=1200&auto=format&fit=crop", "Menu enfant"],
  [3, 2, "https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=1200&auto=format&fit=crop", "Dessert enfant"],
  [4, 1, "https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&auto=format&fit=crop", "Menu duo convivial"],
  [4, 2, "https://images.unsplash.com/photo-1504754524776-8f4f37790ca0?w=1200&auto=format&fit=crop", "Plat familial"],
  [5, 1, "https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=1200&auto=format&fit=crop", "Menu végétal pour trois"],
  [5, 2, "https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop", "Assiette végétarienne"],
  [6, 1, "https://images.unsplash.com/photo-1547592180-85f173990554?w=1200&auto=format&fit=crop", "Menu familial traditionnel"],
  [6, 2, "https://images.unsplash.com/photo-1543353071-873f17a7a088?w=1200&auto=format&fit=crop", "Plat familial généreux"],
  [7, 1, "https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=1200&auto=format&fit=crop", "Poisson et garniture"],
  [7, 2, "https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1200&auto=format&fit=crop", "Dessert gourmand"],
  [8, 1, "https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200&auto=format&fit=crop", "Menu végétal élégant"],
  [8, 2, "https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop", "Composition végétale"],
  [9, 1, "https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&auto=format&fit=crop", "Réception gastronomique"],
  [9, 2, "https://images.unsplash.com/photo-1547592180-85f173990554?w=1200&auto=format&fit=crop", "Présentation événementielle"]
];

const now = new Date();

database.menu_images.insertMany(
  menuImages.map(([menuId, position, url, altText]) => ({
    menuId,
    position,
    url,
    altText,
    source: "Unsplash",
    updatedAt: now
  }))
);

// ----------------------------------------------------------------
// Avis et statistiques des commandes terminées 1001 à 1005
// du client 3 (voir la fin de database/schema.sql).
// ----------------------------------------------------------------

// [orderId, menuId, titre du menu, total de la commande, note, commentaire, date]
const demoOrders = [
  [1001, 1, "Formule Solo Classique", 16.90, 5, "Formule simple, portions correctes et commande facile pour une personne.", "2026-09-02T12:15:00Z"],
  [1002, 2, "Formule Solo Végétale", 17.90, 5, "Très bonne formule végétale, fraîche et suffisamment copieuse.", "2026-09-05T18:30:00Z"],
  [1003, 4, "Menu Duo Convivial", 34.90, 4, "Le menu duo fonctionne bien pour un repas familial sans complication.", "2026-09-08T19:10:00Z"],
  [1004, 7, "Menu Terroir & Mer", 49.90, 5, "Très bonne présentation et poisson bien préparé. Prestation soignée.", "2026-09-10T20:05:00Z"],
  [1005, 9, "Menu Réception Signature", 69.90, 5, "Nous avons choisi le menu réception pour un petit événement professionnel. Très pratique.", "2026-09-12T19:45:00Z"]
];

database.comments.insertMany(
  demoOrders.map(([orderId, menuId, , , rating, comment, date]) => ({
    userId: CLIENT_USER_ID,
    menuId,
    orderId,
    rating,
    comment,
    isValidated: true,
    createdAt: new Date(date),
    updatedAt: now
  }))
);

// Statistiques "all_time" lues par le tableau de bord : même calcul que
// MenuStatisticsService::aggregateAndStore() (commandes terminées uniquement).
const statistics = new Map();

for (const [, menuId, menuTitle, total] of demoOrders) {
  const current = statistics.get(menuId) || { menuTitle, orderCount: 0, revenue: 0 };
  current.orderCount += 1;
  current.revenue = Math.round((current.revenue + total) * 100) / 100;
  statistics.set(menuId, current);
}

database.menu_statistics.insertMany(
  [...statistics].map(([menuId, stat]) => ({
    menuId,
    menuTitle: stat.menuTitle,
    periodStart: null,
    periodEnd: null,
    periodIdentifier: "all_time",
    orderCount: stat.orderCount,
    revenue: stat.revenue,
    updatedAt: now
  }))
);

print("MongoDB Vite & Gourmand : collections recréées.");
print("Images : " + database.menu_images.countDocuments());
print("Avis validés (client SQL #" + CLIENT_USER_ID + ") : " + database.comments.countDocuments());
print("Statistiques all_time : " + database.menu_statistics.countDocuments() + " menus");
