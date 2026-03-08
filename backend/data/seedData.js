module.exports = {
  ownerUser: {
    username: 'admin',
    email: 'admin@vite-gourmand.local',
    password: 'Admin123!',
    firstName: 'Julie',
    lastName: 'Admin',
    role: 'admin'
  },
  demoUser: {
    username: 'clientdemo',
    email: 'client@vite-gourmand.local',
    password: 'Client123!',
    firstName: 'Dylan',
    lastName: 'Client',
    role: 'user'
  },
  menus: [
    { name: 'Menu Prestige', description: 'L’excellence gastronomique pour vos grands événements. Menu noble et raffiné.', price: 49.5, stock: 60, category: 'premium', productType: 'menu', theme: 'evenement', regime: 'classique', imageUrl: 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', personsMin: 10, tags: ['Luxe', 'Gastronomie'], conditions: 'Commande minimum 7 jours à l’avance. Conservation au frais conseillée.', dishes: { entrees: ['Foie gras de canard mi-cuit maison', 'Saint-Jacques snackées au beurre d’agrumes'], plats: ['Filet de bœuf Rossini sauce truffe', 'Homard bleu grillé aux petits légumes'], desserts: ['Sphère chocolat grand cru', 'Paris-Brest revisité à la pistache', 'Millefeuille vanille bourbon'], boissons: ['Champagne', 'Vin blanc', 'Vin rouge'] } },
    { name: 'Menu Végétarien Délice', description: 'Une explosion de saveurs végétales créatives et respectueuses des saisons.', price: 35, stock: 80, category: 'vegetarien', productType: 'menu', theme: 'classique', regime: 'vegetarien', imageUrl: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', personsMin: 8, tags: ['Végétarien', 'Sain'], conditions: 'Commande 5 jours avant la prestation. Prévoir un espace réfrigéré.', dishes: { entrees: ['Gaspacho andalou tomates anciennes', 'Tartare d’avocat et mangue aux herbes'], plats: ['Risotto crémeux aux cèpes et parmesan', 'Tarte fine aux légumes du soleil confits'], desserts: ['Pavlova aux fruits exotiques', 'Moelleux au chocolat vegan'], boissons: ['Jus d’orange pressé', 'Jus de pomme bio', 'Eaux aromatisées'] } },
    { name: 'Menu Cocktail Chic', description: 'Format dînatoire élégant, parfait pour favoriser les échanges.', price: 42, stock: 100, category: 'premium', productType: 'menu', theme: 'evenement', regime: 'classique', imageUrl: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800', personsMin: 15, tags: ['Cocktail', 'Business'], conditions: 'Commande 10 jours avant. Vaisselle premium sur demande.', dishes: { entrees: ['Mini burgers foie gras figue', 'Verrines saumon gravlax aneth'], plats: ['Brochettes yakitori poulet', 'Gambas tempura sauce aigre-douce'], desserts: ['Assortiment de macarons', 'Mini éclairs chocolat, café, vanille'], boissons: ['Champagne', 'Cocktail sans alcool', 'Vin blanc sec'] } },
    { name: 'Menu Enfant Gourmand', description: 'Des plats plaisirs équilibrés adaptés aux goûts des plus jeunes.', price: 18, stock: 120, category: 'enfant', productType: 'menu', theme: 'evenement', regime: 'classique', imageUrl: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800', personsMin: 5, tags: ['Enfants', 'Classique'], conditions: 'Commande 3 jours avant. Serviettes et couverts inclus.', dishes: { entrees: ['Roulés de jambon fromage', 'Velouté de potimarron doux', 'Taboulé coloré'], plats: ['Nuggets de poulet maison & frites', 'Pâtes bolognaise', 'Filet de poisson pané & purée'], desserts: ['Brownie chocolat noix', 'Coupe de glaces artisanales', 'Salade de fruits frais'], boissons: ['Sirop grenadine', 'Jus de pomme bio', 'Eau'] } },
    { name: 'Pièce du boucher (200g) & Frites maison', description: 'Plat à la carte généreux et classique.', price: 16.5, stock: 50, category: 'classiques', productType: 'alacarte', theme: 'classique', regime: 'classique', imageUrl: 'https://images.unsplash.com/photo-1558030006-450675393462?w=800', personsMin: 1, tags: ['À la carte'], dishes: { plats: ['Pièce du boucher (200g) & Frites maison'] } },
    { name: 'Burger du Chef (Cantal, bacon, oignons confits)', description: 'Une valeur sûre pour les amateurs de burger gourmand.', price: 16.5, stock: 40, category: 'classiques', productType: 'alacarte', theme: 'classique', regime: 'classique', imageUrl: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800', personsMin: 1, tags: ['À la carte'], dishes: { plats: ['Burger du Chef (Cantal, bacon, oignons confits)'] } },
    { name: 'Buddha Bowl complet', description: 'Quinoa, avocat, pois chiches et légumes croquants.', price: 16.5, stock: 40, category: 'vegan', productType: 'alacarte', theme: 'classique', regime: 'vegan', imageUrl: 'https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=800', personsMin: 1, tags: ['À la carte', 'Vegan'], dishes: { plats: ['Buddha Bowl complet (Quinoa, avocat, pois chiches)'] } },
    { name: 'Wok de tofu mariné & nouilles sautées', description: 'Option végétale parfumée et généreuse.', price: 16.5, stock: 35, category: 'vegan', productType: 'alacarte', theme: 'classique', regime: 'vegan', imageUrl: 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=800', personsMin: 1, tags: ['À la carte', 'Vegan'], dishes: { plats: ['Wok de tofu mariné & nouilles sautées'] } },
    { name: 'Couscous Royal', description: 'Merguez bœuf, poulet, agneau et semoule parfumée.', price: 16.5, stock: 45, category: 'sansPorc', productType: 'alacarte', theme: 'evenement', regime: 'sansPorc', imageUrl: 'https://images.unsplash.com/photo-1547592180-85f173990554?w=800', personsMin: 1, tags: ['À la carte', 'Sans porc'], dishes: { plats: ['Couscous Royal (Merguez bœuf, poulet, agneau)'] } },
    { name: 'Pavé de saumon grillé & haricots verts', description: 'Une assiette légère et raffinée.', price: 16.5, stock: 30, category: 'sansPorc', productType: 'alacarte', theme: 'classique', regime: 'sansPorc', imageUrl: 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800', personsMin: 1, tags: ['À la carte', 'Sans porc'], dishes: { plats: ['Pavé de saumon grillé & haricots verts'] } },
    { name: 'Sélection Digestifs', description: 'Pour clore votre repas sur une note chaleureuse.', price: 8, stock: 200, category: 'digestif', productType: 'digestif', theme: 'classique', regime: 'classique', imageUrl: 'https://images.unsplash.com/photo-1608547003828-9ea280ee8a5f?w=800', personsMin: 1, tags: ['Fin de repas'], dishes: { boissons: ['Cognac VSOP', 'Armagnac', 'Get 27', 'Limoncello artisanal', 'Calvados'] } }
  ],
  beverages: {
    sans: ['Coca-Cola', 'Coca Zéro', 'Orangina', 'Ice Tea Pêche', 'Jus d’orange pressé', 'Jus de pomme bio', 'Sirop menthe', 'Sirop grenadine'],
    avec: ['Vin rouge', 'Vin blanc', 'Champagne', 'Bière']
  },
  reviews: [
    { username: 'Jean Dupont', rating: 5, content: 'Exceptionnel ! Le Menu Prestige a bluffé tous nos invités.' },
    { username: 'Sophie Martin', rating: 4, content: 'Très bon rapport qualité-prix. Livraison ponctuelle.' },
    { username: 'Lucas Bernard', rating: 5, content: 'Les plats végétariens sont incroyables, merci pour cette découverte.' }
  ]
};
