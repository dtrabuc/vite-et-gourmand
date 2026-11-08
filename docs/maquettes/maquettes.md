# Vite & Gourmand — Maquettes (wireframes et mockups)

> Document **modifiable** : chaque écran a sa fiche (accès, zones, contenus, champs, actions). Modifiez le texte librement ; les images sont dans `wireframes/` et `mockups/`, et les PDF complets sont `wireframes.pdf` et `mockups.pdf`.

**Formats** : iPhone 14 (390 px de large) et MacBook Air 13" (1280 px de large).

**Charte** : couleur principale bordeaux `#8B1E2D`, fond clair `#F8F9FA`, texte `#212529` ; police système (Segoe UI, Roboto, Helvetica, Arial) ; boutons arrondis ; cartes à coins arrondis et ombre légère ; Bootstrap 5.3.

**Éléments communs à tous les écrans**

- **En-tête** : logo « Vite & Gourmand », menu (Accueil, Menus, Contact, puis Connexion / Créer un compte, ou Commander / Mes commandes / Mon compte une fois connecté), bouton « Accessibilité ». Sur mobile, le menu passe dans un bouton burger.
- **Pied de page** (bordeaux) : présentation, horaires d'ouverture, liens (menus, contact, mentions légales, CGV, confidentialité, devis), boutons « Espace client » et « Espace admin ».
- **Responsive** : sur mobile, les colonnes s'empilent, les tableaux deviennent des cartes, la barre de l'espace équipe défile horizontalement.

## Sommaire

1. [Accueil](#01-accueil)
2. [Menus](#02-menus)
3. [Détail menu](#03-detail-menu)
4. [Inscription](#04-inscription)
5. [Connexion](#05-connexion)
6. [Contact](#06-contact)
7. [Commander](#07-commander)
8. [Mes commandes](#08-mes-commandes)
9. [Profil](#09-profil)
10. [Admin - Tableau de bord](#10-admin-tableau-de-bord)
11. [Admin - Commandes](#11-admin-commandes)

---

## 01 Accueil

- **URL** : `/`
- **Accès** : Tout visiteur

### Zones de l'écran (de haut en bas)

1. **Des réceptions soignées, un choix clair, une commande simple.**
   - Texte : Mariages, cocktails, repas d'entreprise, anniversaires ou déjeuners d'équipe : des formules adaptées, du service sur place à la livraison.
   - Cartes : 1
   - Actions : [Découvrir les menus] [Se connecter] [Demander un devis]
2. **Pourquoi nous choisir ?**
3. **Julie et José, 25 ans de métier à Bordeaux**
   - Texte : Depuis 25 ans, Vite & Gourmand accompagne les Bordelais pour leurs repas de fête, de Noël à Pâques, et pour tous leurs évènements, avec des menus qui évoluent au fil des saisons.
   - Sous-titres : Julie · José
   - Cartes : 3
4. **Une grande réception ou un événement sur devis ?**
   - Actions : [Demander un devis]
5. **Menus à la une**
   - Texte : Les formules disponibles actuellement.
   - Sous-titres : Menu Réception Signature · Menu Végétal Élégance · Menu Terroir & Mer
   - Cartes : 3
   - Images : 3
   - Actions : [Tout voir] [Voir le détail] [Se connecter pour commander]
6. **Avis clients**
   - Texte : Quelques retours après des prestations.
   - Cartes : 3

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Accueil](wireframes/iphone-14/01-accueil.png) | ![Mockup iPhone 14 — Accueil](mockups/iphone-14/01-accueil.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Accueil](wireframes/macbook-air-13/01-accueil.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Accueil](mockups/macbook-air-13/01-accueil.png)

---

## 02 Menus

- **URL** : `/menus`
- **Accès** : Tout visiteur

### Zones de l'écran (de haut en bas)

1. **Nos menus et formules**
   - Texte : Des propositions pour les réceptions privées et les événements professionnels.
2. **Choisir une formule**
   - Sous-titres : Menu Réception Signature · Menu Végétal Élégance · Menu Terroir & Mer · Menu Familial Tradition · Menu Trio Végétal · Menu Duo Convivial · Formule Enfant Gourmande · Formule Solo Végétale
   - Cartes : 10
   - Images : 9
   - Champs : Thème (select) ; Régime (select) ; Prix min. (number) ; Prix max. (number) ; Personnes min. (number)
   - Actions : [Filtrer] [Réinitialiser] [Voir le détail] [Se connecter pour commander]

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Menus](wireframes/iphone-14/02-menus.png) | ![Mockup iPhone 14 — Menus](mockups/iphone-14/02-menus.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Menus](wireframes/macbook-air-13/02-menus.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Menus](mockups/macbook-air-13/02-menus.png)

---

## 03 Détail menu

- **URL** : `/menus/1`
- **Accès** : Tout visiteur

### Zones de l'écran (de haut en bas)

1. **Formule Solo Classique**
   - Texte : Entrées, plats, desserts, conditions de réservation et minimum de convives.
   - Cartes : 1
   - Images : 1
   - Actions : [Image précédente] [Image suivante]
2. **Formule Solo Classique**
   - Sous-titres : Composition du menu · Entrée · Plat / accompagnement · Dessert · Conditions de commande · Allergènes présents dans le menu
   - Actions : [Retour aux menus] [Connectez-vous pour commander] [Créer un compte]

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Détail menu](wireframes/iphone-14/03-detail-menu.png) | ![Mockup iPhone 14 — Détail menu](mockups/iphone-14/03-detail-menu.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Détail menu](wireframes/macbook-air-13/03-detail-menu.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Détail menu](mockups/macbook-air-13/03-detail-menu.png)

---

## 04 Inscription

- **URL** : `/register`
- **Accès** : Visiteur non connecté

### Zones de l'écran (de haut en bas)

1. **Créer votre compte**
   - Champs : Prénom (text, obligatoire) ; Nom (text, obligatoire) ; Téléphone (tel, obligatoire) ; Adresse email (email, obligatoire) ; Adresse postale (textarea, obligatoire) ; Me proposer un mot de passe aléatoire sécurisé (checkbox) ; Mot de passe (password, obligatoire) ; Confirmation (password, obligatoire) ; J’ai lu la politique de confidentialité et j’accepte les conditions générales de vente. (checkbox, obligatoire)
   - Actions : [Afficher] [Générer un autre] [Copier] [Créer mon compte]

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Inscription](wireframes/iphone-14/04-inscription.png) | ![Mockup iPhone 14 — Inscription](mockups/iphone-14/04-inscription.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Inscription](wireframes/macbook-air-13/04-inscription.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Inscription](mockups/macbook-air-13/04-inscription.png)

---

## 05 Connexion

- **URL** : `/login`
- **Accès** : Visiteur non connecté

### Zones de l'écran (de haut en bas)

1. **Connexion**
   - Champs : Adresse email (email, obligatoire) ; Mot de passe (password, obligatoire)
   - Actions : [Afficher] [Se connecter]

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Connexion](wireframes/iphone-14/05-connexion.png) | ![Mockup iPhone 14 — Connexion](mockups/iphone-14/05-connexion.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Connexion](wireframes/macbook-air-13/05-connexion.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Connexion](mockups/macbook-air-13/05-connexion.png)

---

## 06 Contact

- **URL** : `/contact`
- **Accès** : Tout visiteur

### Zones de l'écran (de haut en bas)

1. **Contact**
   - Champs : Titre (text, obligatoire) ; Email (email, obligatoire) ; Description (textarea, obligatoire)
   - Actions : [Envoyer]

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Contact](wireframes/iphone-14/06-contact.png) | ![Mockup iPhone 14 — Contact](mockups/iphone-14/06-contact.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Contact](wireframes/macbook-air-13/06-contact.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Contact](mockups/macbook-air-13/06-contact.png)

---

## 07 Commander

- **URL** : `/orders/new?menu=1`
- **Accès** : Client connecté

### Zones de l'écran (de haut en bas)

1. **Nouvelle commande**
   - Cartes : 1
2. **Vos informations**
   - Champs : Nom (text) ; Prénom (text) ; Adresse e-mail (email)
3. **1. Préparer la commande**
   - Actions : [Indiquer / modifier]

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Commander](wireframes/iphone-14/07-commander.png) | ![Mockup iPhone 14 — Commander](mockups/iphone-14/07-commander.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Commander](wireframes/macbook-air-13/07-commander.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Commander](mockups/macbook-air-13/07-commander.png)

---

## 08 Mes commandes

- **URL** : `/orders`
- **Accès** : Client connecté

### Zones de l'écran (de haut en bas)

1. **Mes commandes**
   - Texte : Suivez vos prestations, leurs statuts et leurs dates.
   - Cartes : 1
   - Actions : [Nouvelle commande]
2. **Commande … (bloc répété ×5, un par élément)**
   - Sous-titres : Suivi
   - Cartes : 1

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Mes commandes](wireframes/iphone-14/08-mes-commandes.png) | ![Mockup iPhone 14 — Mes commandes](mockups/iphone-14/08-mes-commandes.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Mes commandes](wireframes/macbook-air-13/08-mes-commandes.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Mes commandes](mockups/macbook-air-13/08-mes-commandes.png)

---

## 09 Profil

- **URL** : `/profile`
- **Accès** : Client connecté

### Zones de l'écran (de haut en bas)

1. **Mon profil**
   - Cartes : 1
   - Champs : Prénom (text, obligatoire) ; Nom (text, obligatoire) ; Email (email, obligatoire) ; Téléphone (tel, obligatoire) ; GSM (tel, obligatoire) ; Adresse (textarea, obligatoire)
   - Actions : [Enregistrer]
2. **Changer mon mot de passe**
   - Champs : Mot de passe actuel (password, obligatoire) ; Nouveau mot de passe (password, obligatoire) ; Confirmation (password, obligatoire)
   - Actions : [Afficher] [Modifier le mot de passe]

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Profil](wireframes/iphone-14/09-profil.png) | ![Mockup iPhone 14 — Profil](mockups/iphone-14/09-profil.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Profil](wireframes/macbook-air-13/09-profil.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Profil](mockups/macbook-air-13/09-profil.png)

---

## 10 Admin - Tableau de bord

- **URL** : `/admin/dashboard`
- **Accès** : Administrateur

### Zones de l'écran (de haut en bas)

1. **Tableau de bord**
   - Cartes : 5
2. **Comparaison graphique des commandes par menu**

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Admin - Tableau de bord](wireframes/iphone-14/10-admin-tableau-de-bord.png) | ![Mockup iPhone 14 — Admin - Tableau de bord](mockups/iphone-14/10-admin-tableau-de-bord.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Admin - Tableau de bord](wireframes/macbook-air-13/10-admin-tableau-de-bord.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Admin - Tableau de bord](mockups/macbook-air-13/10-admin-tableau-de-bord.png)

---

## 11 Admin - Commandes

- **URL** : `/admin/orders`
- **Accès** : Employé ou administrateur

### Zones de l'écran (de haut en bas)

1. **Gestion des commandes**
   - Texte : Suivi des commandes clients et des transitions de prestation.
   - Cartes : 2
   - Champs : Client (text) ; Statut (select)
   - Actions : [Filtrer]
2. **Commande … (bloc répété ×5, un par élément)**
   - Cartes : 1

### Notes de conception

- _À compléter : intentions, règles métier, évolutions prévues._

### Aperçus

| Wireframe iPhone 14 | Mockup iPhone 14 |
|---|---|
| ![Wireframe iPhone 14 — Admin - Commandes](wireframes/iphone-14/11-admin-commandes.png) | ![Mockup iPhone 14 — Admin - Commandes](mockups/iphone-14/11-admin-commandes.png) |

**Wireframe MacBook Air 13"**

![Wireframe MacBook Air — Admin - Commandes](wireframes/macbook-air-13/11-admin-commandes.png)

**Mockup MacBook Air 13"**

![Mockup MacBook Air — Admin - Commandes](mockups/macbook-air-13/11-admin-commandes.png)
