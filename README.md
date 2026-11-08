# Vite & Gourmand — ECF Studi (DWWM)

> English documentation: [docs/documentation-en.md](docs/documentation-en.md)

Application web de commande de menus traiteur pour « Vite & Gourmand », une entreprise **fictive** de Bordeaux, réalisée pour l'ECF du titre Développeur Web et Web Mobile.

Les visiteurs consultent et filtrent les menus. Les clients commandent, suivent leurs commandes et laissent un avis. Les employés gèrent les commandes, les menus, les plats, les horaires et les avis. L'administrateur gère en plus les employés et consulte le chiffre d'affaires et les statistiques.

En ligne : https://dylantrabuc.alwaysdata.net

## 1. Stack

| Couche | Choix |
|---|---|
| Back | PHP 8.2+, MVC maison sans framework (routeur, conteneur, middlewares) |
| Bases | MariaDB (PDO, requêtes préparées) et MongoDB |
| Front | HTML/PHP, Bootstrap 5.3, JavaScript natif (sans npm) |
| Dépendances | `mongodb/mongodb`, `phpmailer/phpmailer`, `giggsey/libphonenumber-for-php` |
| Conteneurs | Docker Compose : PHP 8.2 + Apache, MariaDB 11.4, MongoDB 7, Mailpit |

## 2. Lancer le projet en local avec Docker (recommandé)

Seul **Docker** (avec Compose v2) est nécessaire : PHP, Composer et les bases sont dans les conteneurs.

```bash
git clone -b dev https://github.com/dtrabuc/vite-et-gourmand.git
cd vite-et-gourmand
docker compose up -d --build
```

Au premier démarrage, MariaDB importe `database/schema.sql` et MongoDB exécute `database/mongodb-init.js`. Attendre une trentaine de secondes, puis ouvrir :

| Service | Adresse |
|---|---|
| Site | http://localhost:8080 |
| Boîte mail de test (Mailpit) | http://localhost:8025 |

Comptes de démonstration (Docker uniquement) :

| Rôle | Page de connexion | E-mail | Mot de passe |
|---|---|---|---|
| Client | `/login` | `user@viteetgourmand.com` | `Client!Docker2026` |
| Employé | `/admin/login` | `employee@viteetgourmand.com` | `Employe!Docker2026` |
| Admin | `/admin/login` | `admin@viteetgourmand.com` | `Admin!Docker2026` |

Commandes utiles :

```bash
docker compose ps              # état des services
docker compose logs app        # journaux de l'application
docker compose stop            # arrêter (données conservées)
docker compose down -v         # tout supprimer, bases comprises
```

Ports déjà utilisés : `VG_HTTP_PORT=8090 VG_MAIL_PORT=8026 docker compose up -d --build`.
Le guide complet, avec les problèmes fréquents, est dans [`docker-setup.txt`](docker-setup.txt).

Les adresses de livraison sont vérifiées avec OpenStreetMap (Photon pour la recherche, OSRM pour la distance par la route), sans clé d'API. Zone de livraison : Bordeaux (gratuit) et 25 km autour ; au-delà, retrait ou livraison hors zone acceptée par le client. Si le service est injoignable, la saisie manuelle reste possible.

## 3. Lancer le projet sans Docker

Prérequis : PHP 8.2+ avec `pdo_mysql`, `mongodb` (1.21+), `mbstring`, `openssl` ; Composer 2 ; MariaDB ; MongoDB avec `mongosh`.

```bash
composer install
cp .env.example .env           # puis renseigner la base, MongoDB et le SMTP

mysql -u root -p -e "CREATE DATABASE viteetgourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p viteetgourmand < database/schema.sql
mongosh "mongodb://127.0.0.1:27017/viteetgourmand" --file database/mongodb-init.js

php scripts/set-password.php --sync    # applique les mots de passe DEMO_* du .env
php -S localhost:8000 -t public
```

Le site est alors sur http://localhost:8000. Avec Apache, la racine web doit pointer sur `public/` (`mod_rewrite` requis).

Les variables du `.env` sont décrites dans `.env.example`. Sans `MAIL_HOST`, aucun e-mail ne part.

## 4. Base de données

- `database/schema.sql` est le **fichier SQL unique** : structure complète et données de démonstration (3 comptes, 46 plats, 10 menus, 5 commandes). Il n'y a pas de migrations séparées.
- Il ne contient ni `CREATE DATABASE` ni `USE` : il s'importe dans la base choisie.
- **Il supprime et recrée toutes les tables** : ne jamais l'importer sur une base à conserver.
- `database/mongodb-init.js` recrée les collections MongoDB : `menu_images`, `comments` et `menu_statistics`.

**Pourquoi deux bases ?** MariaDB stocke les données liées qui ont besoin d'intégrité : comptes, menus, plats, commandes et historique. Une commande est enregistrée dans une transaction. MongoDB stocke des documents indépendants : galeries d'images, avis et statistiques du tableau de bord, comme le demande l'énoncé.

## 5. Fonctionnalités

- **Visiteur** : menus affichés avec leur prix par personne et le total pour le nombre de convives choisi, filtres sans rechargement, détail d'un menu (galerie, plats, allergènes), contact, devis, inscription.
- **Inscription** : mot de passe libre ou généré aléatoirement (case à cocher), bouton « Afficher » pour voir les caractères, mail de bienvenue.
- **Client** : commande en partant du nombre de personnes, adresse vérifiée sur OpenStreetMap (Bordeaux et 25 km, au-delà retrait ou livraison hors zone acceptée), page de confirmation avec suivi, notifications (cloche et e-mails à chaque étape), suivi, modification et annulation tant que la commande est en attente, avis, profil.
- **Employé** : commandes et statuts, menus, plats, galeries, horaires, modération des avis, clients, devis.
- **Administrateur** : employés, chiffre d'affaires, graphique des commandes par menu.

## 6. Sécurité

- Requêtes préparées partout, échappement de toutes les sorties.
- Jeton CSRF sur chaque formulaire, en-têtes de sécurité (CSP sans script inline).
- Mots de passe hachés en bcrypt, politique de robustesse, blocage après 5 échecs et limitation de débit par IP.
- Lien de réinitialisation à usage unique, valable 1 heure, stocké haché.
- Session `HttpOnly`, `SameSite=Lax`, expiration après 30 minutes d'inactivité.
- Images envoyées vérifiées sur leur contenu, avec un nom aléatoire.
- Aucun secret versionné : tout est dans `.env`, qui est ignoré par Git.

## 7. Arborescence

```text
App/            Controller, Core, Entity, Middleware, Repository, Service, View
config/         configuration et routes
database/       schema.sql, mongodb-init.js
docker/         configuration Apache/PHP du conteneur
docs/           documentation en anglais et diagrammes de conception
public/         racine web (index.php, assets)
scripts/        set-password.php (mots de passe en ligne de commande)
```

## 8. Mise en production (Alwaysdata)

1. Sauvegarder la base et les fichiers remplacés.
2. Envoyer les fichiers modifiés en FTPS, sans écraser `.env`, `storage/` ni `public/uploads/`.
3. Adapter la structure de la base par des `ALTER TABLE` testés sur une copie.
4. Vérifier le site, puis tester une inscription réelle.

## 9. Documentation

- [`docs/documentation-en.md`](docs/documentation-en.md) : documentation technique en anglais.
- `docs/conception/` : diagramme de classes UML (attributs et méthodes), MCD, cas d'utilisation, séquences d'inscription et de commande (`.drawio`, `.png`, `.svg`), générés par `docs/conception/outils/diagrams.py`.
- `docs/maquettes/` : wireframes et mockups des 12 écrans (dont commande et confirmation) en iPhone 14 et MacBook Air 13" : PNG, `wireframes.pdf`, `mockups.pdf` et `maquettes.md` (fiches modifiables).
- `docs/gestion-projet/` : backlog Scrum (user stories, sprints, Definition of Done) et script `trello-import.mjs` qui crée le tableau Trello.
- `docs/README-fr.docx` : ce README au format Word.
