  // VITE GOURMAND - BASE DE DONNÉES

const PRIX_PLAT_ALA_CARTE = 16.50;
  rapide
  const PRIX_DIGESTIF = 8.00;
  
ici
  const lesMenus = [
  {
  id: 1,
nom: "Menu Prestige",
  categorie: "premium",
  prix: 49.50,
personnesMin: 10,
image: "https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=500",
description: "L'excellence gastronomique pour vos plus grands événements. Une sélection noble et raffinée.",
tags: ["Luxe", "Gastronomie"],
plats: {
  entrees: ["Foie gras de canard mi-cuit maison", "Saint-Jacques snackées au beurre d'agrumes"],
  plats: ["Filet de bœuf Rossini sauce truffe", "Homard bleu grillé aux petits légumes"],
  desserts: ["Sphère chocolat grand cru", "Paris-Brest revisité à la pistache", "Millefeuille vanille bourbon"]
}
  },
  {
  id: 2,
nom: "Menu Végétarien Délice",
  categorie: "vegetarien",
  prix: 35.00,
personnesMin: 8,
  image: "https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=500",
description: "Une explosion de saveurs végétales, créative et respectueuse des saisons.",
tags: ["Végétarien", "Sain"],
plats: {
entrees: ["Gaspacho Andalou tomates anciennes", "Tartare d'avocat et mangue aux herbes"],
plats: ["Risotto crémeux aux cèpes et parmesan", "Tarte fine aux légumes du soleil confits"],
desserts: ["Pavlova aux fruits exotiques", "Moelleux au chocolat vegan"]
  }
},
{
  id: 3,
  nom: "Menu Cocktail Chic",
  categorie: "premium",
prix: 42.00,
personnesMin: 15,
image: "https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=500",
  description: "Format dînatoire élégant, idéal pour favoriser les échanges et la convivialité.",
  tags: ["Cocktail", "Business"],
  plats: {
entrees: ["Mini burgers foie gras figue", "Verrines saumon gravlax aneth"],
plats: ["Brochettes yakitori poulet", "Gambas tempura sauce aigre-douce"],
desserts: ["Assortiment de macarons", "Mini éclairs (chocolat, café, vanille)"]
}
},
{
id: 4,
  nom: "Menu Enfant Gourmand",
  categorie: "enfant",
prix: 18.00,
personnesMin: 5,
image: "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500",
  description: "Des plats plaisirs, équilibrés et adaptés aux goûts des plus jeunes.",
  tags: ["Enfants", "Classique"],
plats: {
  entrees: ["Roulés de jambon fromage", "Velouté de potimarron doux", "Taboulé coloré"],
plats: ["Nuggets de poulet maison & frites", "Pâtes bolognaise (bœuf France)", "Filet de poisson pané & purée"],
  desserts: ["Brownie chocolat noix", "Coupe de glaces artisanales", "Salade de fruits frais"]
}
},
  {
  id: 5,
nom: "Sélection Digestifs",
categorie: "digestif",
  prix: PRIX_DIGESTIF,
  personnesMin: 1,
  image: "https://images.unsplash.com/photo-1608547003828-9ea280ee8a5f?w=500",
  description: "Pour clore votre repas sur une note chaleureuse.",
  tags: ["Fin de repas"],
  plats: {
boissons: ["Cognac VSOP", "Armagnac", "Get 27", "Limoncello artisanal", "Calvados"]
  }
  }
];

const platsALaCarte = {
  classiques: [
"Pièce du boucher (200g) & Frites maison",
  "Moules Marinières & Frites",
"Burger du Chef (Cantal, Bacon, Oignons confits)",
  "Escalope de Veau Milanaise & Linguine",
  "Dos de Cabillaud rôti & Légumes vapeur",
  "Magret de Canard miel-épices & Écrasé de pommes de terre"
],
vegan: [
  "Buddha Bowl complet (Quinoa, Avocat, Pois chiches)",
  "Curry Vert de Légumes & Riz Basmati",
"Burger Vegan (Steak de lentilles) & Frites",
"Wok de Tofu mariné & Nouilles sautées",
"Lasagnes Végétales aux épinards"
],
sansPorc: [
  "Couscous Royal (Merguez Bœuf, Poulet, Agneau)",
  "Tajine de Poulet aux citrons confits",
"Brochettes d'Agneau marinées & Semoule",
  "Pavé de Saumon grillé & Haricots verts",
"Poulet Basquaise & Riz Pilaf"
]
};
  
const lesBoissons = {
  nonAlcoolisees: {
sodas: ["Coca-Cola", "Coca Zéro", "Orangina", "Ice Tea Pêche"],
  jusAndSirops: ["Jus d'Orange pressé", "Jus de Pomme Bio", "Sirop à l'eau (Menthe, Grenadine)"]
}
};
  
const lesAvis = [
{ id: 1, auteur: "Jean Dupont", note: 5, commentaire: "Exceptionnel ! Le Menu Prestige a bluffé tous nos invités.", date: "15/01/2026" },
  { id: 2, auteur: "Sophie Martin", note: 4, commentaire: "Très bon rapport qualité/prix. Livraison ponctuelle.", date: "10/01/2026" },
{ id: 3, auteur: "Lucas Bernard", note: 5, commentaire: "Les plats vegan sont incroyables, merci pour cette découverte.", date: "05/01/2026" }
];
  
const mesCommandes = [
{ id: "CMD-2026-001", titreMenu: "Menu Prestige", date: "20/02/2026", prixTotal: 742.50, statut: "Validée" },
  { id: "CMD-2026-002", titreMenu: "À la carte", date: "12/01/2026", prixTotal: 82.50, statut: "Livrée" }
  ];