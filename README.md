# Vite & Gourmand — ECF Studi (DWWM)

Application web de commande de menus traiteur pour « Vite & Gourmand », une entreprise **fictive** de Bordeaux (Julie et José, 25 ans de métier), réalisée pour l'Évaluation en Cours de Formation du titre Développeur Web et Web Mobile.

Les visiteurs consultent et filtrent les menus. Les clients commandent (livraison, retrait ou sur place), suivent et modifient leurs commandes, puis laissent un avis. L'équipe gère les commandes, les menus, les plats, les horaires et les avis. L'administrateur a en plus les employés, le chiffre d'affaires et les statistiques.

## 1. Stack

| Couche | Choix |
|---|---|
| Back | PHP 8.2+ sans framework, MVC maison (routeur, conteneur d'injection, middlewares) |
| Base relationnelle | MariaDB, accès en PDO avec requêtes préparées |
| Base NoSQL | MongoDB (extension `mongodb` + bibliothèque `mongodb/mongodb`) |
| Front | HTML/PHP, Bootstrap 5, JavaScript natif (sans bundler ni npm) |
| Dépendances Composer | `mongodb/mongodb`, `giggsey/libphonenumber-for-php` (téléphones), `phpmailer/phpmailer` (SMTP) |
| Bibliothèques front | Bootstrap CSS 5.3.8 (thème Litera) servi en local, découpé en feuilles par composant dans `public/assets/css/` ; Bootstrap JS 5.3.8 chargé depuis le CDN jsDelivr avec contrôle d'intégrité (SRI) ; intl-tel-input 29.5.3 (champs téléphone) en local dans `public/assets/vendor/` |
| Accessibilité | Lien « Aller au contenu », panneau « Accessibilité » (taille du texte, contraste renforcé, texte aéré, liens soulignés, animations réduites, réglages gardés dans le navigateur), infobulles d'aide au survol, au clavier et au toucher |

## 2. Prérequis

- **PHP 8.2 ou plus récent** (testé en local avec PHP 8.3 sous Linux et PHP 8.5 sous WAMP ; PHP 8.3 en production chez Alwaysdata).
- **Extensions PHP** : `pdo_mysql`, `mongodb` (1.21 ou plus), `mbstring`, `openssl` (SMTP en TLS et appels HTTPS à Google), `ctype`, `filter`, `hash` et `iconv` (demandées par les dépendances). Vérifier avec `php -m`.
- `allow_url_fopen = On` dans `php.ini` : les appels à l'API Google passent par `file_get_contents`.
- **Composer 2**.
- **MariaDB** (10.x ou 11.x) et **MongoDB** (6 ou plus) avec `mongosh`.
- **Node.js 20.11 ou plus récent**, seulement pour le test de bout en bout (`scripts/simulate-registration.mjs`) et le script de connexion SSH (`connect-ssh.js`). L'application elle-même n'utilise ni Node ni npm.
- Une connexion Internet pour les photos de démonstration (Unsplash) et l'API Google Maps et pour Bootstrap JS (CDN jsDelivr).

### Activer l'extension `mongodb` sous Windows

1. Repérer la version de PHP, son architecture et le mode thread safe (TS) ou non (NTS) : `php -v` (par exemple `PHP 8.5.0 … ZTS … x64` = thread safe, 64 bits).
2. Télécharger la DLL correspondante (`php_mongodb.dll`, version 2.1 ou plus) depuis les releases du dépôt GitHub `mongodb/mongo-php-driver`.
3. La copier dans le dossier `ext` de PHP (sous WAMP : `C:\wamp64\bin\php\php8.x.x\ext\`).
4. Ajouter `extension=mongodb` dans le `php.ini` utilisé par la ligne de commande (`php --ini` indique lequel) et, sous WAMP, dans celui d'Apache.
5. Vérifier : `php -m` doit lister `mongodb`.

## 3. Installation

```bash
composer install
cp .env.example .env          # PowerShell : Copy-Item .env.example .env
```

Renseigner ensuite `.env` :

| Variable | Rôle | Exemple en local |
|---|---|---|
| `APP_ENV` | Environnement | `development` |
| `APP_DEBUG` | `true` affiche les erreurs PHP (développement uniquement) | `false` |
| `APP_URL` | Adresse du site, utilisée dans les liens des e-mails (mot de passe oublié…) | `http://localhost:8000` |
| `GOOGLE_MAPS_API_KEY` | Clé Google (API Places pour l'autocomplétion, API Distance Matrix pour les frais de livraison). Sans clé, les livraisons hors Bordeaux sont refusées. | votre clé |
| `HOSTING_NAME`, `HOSTING_ADDRESS`, `HOSTING_WEBSITE` | Hébergeur affiché dans les mentions légales | vide en local |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Connexion MariaDB | `127.0.0.1`, `3306`, `viteetgourmand`, `root`, vide |
| `MONGO_CONNECTION_STRING` | URI MongoDB complète (prioritaire sur les variables suivantes) | voir la section 4 |
| `MONGO_DATABASE`, `MONGO_HOST`, `MONGO_PORT`, `MONGO_USERNAME`, `MONGO_PASSWORD`, `MONGO_AUTH_SOURCE` | Connexion MongoDB, utilisée si l'URI est vide | `viteetgourmand`, `127.0.0.1`, `27017`… |
| `SESSION_NAME`, `SESSION_LIFETIME`, `SESSION_SECURE` | Cookie de session ; durée glissante et expiration après inactivité, en minutes. `Secure` est aussi activé automatiquement si `APP_URL` est en HTTPS. | `viteetgourmand_session`, `30`, `false` |
| `PASSWORD_HASH_COST` | Coût bcrypt des mots de passe | `12` |
| `MAIL_DRIVER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` | Serveur SMTP (PHPMailer). Sans `MAIL_HOST`, aucun e-mail ne part (l'erreur est écrite dans le journal PHP). `MAIL_ENCRYPTION` : `tls`, `ssl` ou `none`. | Mailpit : `smtp`, `127.0.0.1`, `1025`, vide, vide, `none` |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Expéditeur des e-mails | `noreply@viteetgourmand.local` |
| `MAIL_TO_ADDRESS` | Adresse de l'entreprise (contact, nouvelles commandes, devis) | `contact@viteetgourmand.local` |
| `DEMO_*_EMAIL`, `DEMO_*_PASSWORD` | Identifiants des comptes de démonstration (voir la section 6) | |
| `SSH_HOST`, `SSH_USERNAME`, `SSH_PASSWORD` | Accès SSH à l'hébergement, lus par `connect-ssh.js` (voir la section 8) | vide en local |

**Inscription :** le formulaire ne demande pas de mot de passe. Le compte est créé actif, avec un mot de passe provisoire aléatoire envoyé par e-mail. À la première connexion, le client est redirigé vers son profil et doit choisir un nouveau mot de passe avant d'accéder au reste de son espace. Si l'e-mail ne peut pas partir, le compte est supprimé et un message d'erreur s'affiche.

Sur une base installée avant le 4 novembre 2026, exécutez une fois `database/migrations/2026-11-04-temporary-password.sql` (voir la section 8).

Pour voir les e-mails en local sans vrai serveur, on peut utiliser Mailpit (SMTP sur le port 1025, interface sur http://localhost:8025). En ligne, renseignez le serveur SMTP de l'hébergeur. Les messages du formulaire de contact partent à `MAIL_TO_ADDRESS`, avec l'adresse du visiteur en « Répondre à » : l'équipe y répond depuis sa messagerie (webmail de l'hébergeur).

## 4. Import des bases (dans cet ordre)

### 4.1 MariaDB : `schema.sql`

`database/schema.sql` est le **fichier unique de référence** : il contient la structure **et** les données de démonstration (3 comptes, horaires, allergènes, 32 plats, 9 menus et leurs compositions, 5 commandes terminées n° 1001 à 1005).

**Attention : l'import supprime et recrée toutes les tables de la base `viteetgourmand`.** Toutes les données existantes sont perdues : sauvegardez d'abord si besoin (phpMyAdmin > Exporter).

**Avec phpMyAdmin** : onglet « Importer », choisir `database/schema.sql`, jeu de caractères `utf-8`, puis « Importer ». Le fichier crée la base `viteetgourmand` s'il le faut.

**En ligne de commande** :

```bash
mysql -u root -p --default-character-set=utf8mb4 < database/schema.sql
```

Sous WAMP, si `mysql` n'est pas dans le PATH (PowerShell, à adapter à votre version) :

```powershell
Get-Content database/schema.sql -Raw -Encoding UTF8 | & "C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mysql.exe" -u root -p --default-character-set=utf8mb4
```

### 4.2 MongoDB : `mongodb-init.js`

À lancer **après** `schema.sql`. Comme lui, le script repart de zéro : il **supprime puis recrée** les collections `menu_images`, `comments` et `menu_statistics` et leurs index, puis charge les galeries (18 images), les avis (5) et les statistiques du tableau de bord, alignés sur les données SQL (client n° 3, commandes 1001 à 1005).

**Avec authentification** (configuration de `.env.example`) : créer une fois l'utilisateur applicatif, en se connectant avec un compte administrateur MongoDB :

```javascript
// dans mongosh, connecté en administrateur
use viteetgourmand
db.createUser({ user: "vgt_app", pwd: passwordPrompt(), roles: [{ role: "readWrite", db: "viteetgourmand" }] })
```

`passwordPrompt()` demande le mot de passe au clavier, sans l'écrire dans la commande. Reportez-le ensuite dans `MONGO_PASSWORD` et `MONGO_CONNECTION_STRING` du `.env`, puis lancez le script avec ce compte (`mongosh` demande le mot de passe) :

```bash
mongosh "mongodb://127.0.0.1:27017/viteetgourmand?authSource=viteetgourmand" -u vgt_app -p --file database/mongodb-init.js
```

**Sans authentification** (MongoDB local sans contrôle d'accès) : mettre `MONGO_CONNECTION_STRING=mongodb://127.0.0.1:27017/viteetgourmand` dans `.env`, puis :

```bash
mongosh "mongodb://127.0.0.1:27017/viteetgourmand" --file database/mongodb-init.js
```

## 5. Lancer le serveur en local

Depuis la racine du projet, avec le serveur intégré de PHP (le dossier `public/` est la racine web ; les fichiers CSS, JS et images sont servis directement, tout le reste passe par `public/index.php`) :

```bash
php -S localhost:8000 -t public
```

Si `php` n'est pas dans le PATH (PowerShell, WAMP) :

```powershell
& "C:\wamp64\bin\php\php8.5.0\php.exe" -S localhost:8000 -t public
```

Puis ouvrir **http://localhost:8000** (arrêt : `Ctrl+C`). Penser à mettre `APP_URL=http://localhost:8000` dans `.env`.

Avec Apache (WAMP), faire pointer le DocumentRoot sur `public/` : le fichier `public/.htaccess` redirige les URL vers `index.php` (module `mod_rewrite` requis) et sert les `.mjs` en JavaScript.

## 6. Comptes de démonstration

Trois comptes sont créés par `database/schema.sql` : un administrateur, un employé (connexion sur `/admin/login`) et un client (connexion sur `/login`).

Leurs identifiants ne sont **pas** dans ce README : ils sont dans le `.env` (non versionné), dans les variables `DEMO_ADMIN_EMAIL` / `DEMO_ADMIN_PASSWORD`, `DEMO_EMPLOYEE_EMAIL` / `DEMO_EMPLOYEE_PASSWORD` et `DEMO_USER_EMAIL` / `DEMO_USER_PASSWORD` (modèle dans `.env.example`).

### Changer, vérifier ou synchroniser un mot de passe

Ne modifiez jamais un mot de passe directement dans phpMyAdmin : la colonne `password` attend un hash bcrypt, et un mot de passe en clair est refusé à la connexion. Utilisez l'outil fourni (saisie masquée par des `*`) :

```bash
php scripts/set-password.php <email> --check   # teste un mot de passe, sans rien modifier
php scripts/set-password.php <email>           # change le mot de passe et déverrouille le compte
php scripts/set-password.php --sync            # applique les mots de passe DEMO_* du .env
php scripts/set-password.php --pull            # recopie dans schema.sql les hashes actuels de MariaDB
```

Pour un compte de démonstration, un changement met à jour **en même temps** la base MariaDB, le hash dans `database/schema.sql` (un réimport garde donc le bon mot de passe) et la variable `DEMO_*_PASSWORD` du `.env`. Si vous modifiez le `.env` à la main, lancez `--sync` pour reporter les mots de passe dans MariaDB et dans `schema.sql`.

Un mot de passe changé **depuis le site** (profil, « Changer mon mot de passe ») est enregistré dans MariaDB : c'est l'Update du CRUD. `database/schema.sql` est le script d'initialisation, l'application ne le réécrit pas. Pour y reporter l'état actuel des comptes de démonstration, lancez `--pull`, puis mettez à jour à la main le mot de passe correspondant dans le `.env` (un hash ne permet pas de retrouver le mot de passe).

## 7. Arborescence

```text
App/
├── Controller/    requêtes HTTP → services → vues (Public, Auth, Order, Admin, Dish, MenuComposition, Address, Quote, Contact, Notification, Error)
├── Core/          Application, Router, Container, Config (+ functions.php), Database, View, Session, Response, Request, Labels, UploadFile, Exception/
├── Entity/        Menu, Order, User
├── Middleware/    Security (en-têtes, CSRF, limitation de débit), Auth (dont le changement de mot de passe imposé), Staff, Admin, Guest
├── Repository/    accès MariaDB (utilisateurs, menus, plats, commandes, horaires, devis, notifications) et MongoDB (images, avis, statistiques)
├── Service/       règles métier (authentification, commandes, livraison, menus, plats, devis, e-mails, téléphone, mots de passe, limitation de débit, cache…)
└── View/          gabarits PHP (layout, home, auth, order, admin, contact, quote, notification, errors)
config/            app.php (configuration lue dans .env) et routes.php
database/          schema.sql (structure + données), shema_deployement.sql (variante historique), migrations/, mongodb-init.js
docs/conception/   MCD, diagramme de séquence de l'inscription, diagramme de cas d'utilisation (.drawio, .lucid, .png, .svg)
scripts/           set-password.php (mots de passe en ligne de commande), simulate-registration.mjs (test de bout en bout)
connect-ssh.js     connexion SSH à l'hébergement, identifiants lus dans .env
public/            racine web : index.php, .htaccess, assets/ (css découpé par composant, js, vendor/intl-tel-input)
storage/           créé à l'exécution : cache et limitation de débit (ignoré par git)
public/uploads/    créé à l'exécution : images ajoutées aux galeries (ignoré par git)
```

## 8. Tests et déploiement

### Test de bout en bout de l'inscription

```bash
node scripts/simulate-registration.mjs             # SMTP local de capture, aucun e-mail envoyé
node scripts/simulate-registration.mjs --ethereal  # boîte Ethereal jetable, e-mails relus en IMAP
```

Le script lance l'application depuis une copie temporaire du projet, sur la base MariaDB **locale** du `.env`. Il contrôle par HTTP, avec cookies et jetons CSRF :
- l'inscription, la réception du mot de passe provisoire et la première connexion ;
- le changement de mot de passe imposé ;
- les liens de réinitialisation : falsifiés, expirés ou déjà utilisés refusés, lien annulé par une nouvelle demande, par un changement de mot de passe ou sur un compte désactivé ;
- l'état réel de la base à chaque étape.

Les comptes de test (`codex-sim-…@example.invalid`) sont supprimés à la fin, même en cas d'échec. Les validations JavaScript du navigateur ne sont pas couvertes.

### Déploiement (Alwaysdata)

Le site est hébergé chez Alwaysdata : https://dylantrabuc.alwaysdata.net, avec le projet dans `~/www/vite-et-gourmand`.

1. **Sauvegarder** la base (`mysqldump`) et les fichiers remplacés, hors du dossier web.
2. **Envoyer** les fichiers modifiés en FTPS. Ne jamais écraser le `.env` distant, ni `storage/` ou `public/uploads/`.
3. **Appliquer les migrations** de `database/migrations/` qui ne le sont pas encore, une seule fois chacune.
4. **Vérifier** les pages principales, puis faire une inscription réelle avec une boîte de test.

**Ne jamais importer `schema.sql` ni `shema_deployement.sql` sur une base en service :** ces fichiers suppriment et recréent toutes les tables.

`connect-ssh.js` ouvre une connexion SSH avec `SSH_HOST`, `SSH_USERNAME` et `SSH_PASSWORD` du `.env` (le mot de passe passe par `SSH_ASKPASS`, jamais par la ligne de commande) :

```bash
node connect-ssh.js              # teste la connexion
node connect-ssh.js "commande"   # exécute une commande sur le serveur
```

En production, le SMTP de l'hébergeur (`MAIL_HOST`, port 587, `MAIL_ENCRYPTION=tls`) est utilisé sans identifiants, car les envois partent d'un site hébergé chez Alwaysdata.

## 9. Documents de conception

`docs/conception/` contient trois diagrammes, chacun en `.drawio`, `.lucid`, `.png` et `.svg` :
- **MCD** (`mcd.*`) : les tables MariaDB et leurs relations, avec les collections MongoDB dans un encadré ;
- **Séquence** (`sequence-inscription.*`) : l'inscription, l'envoi du mot de passe provisoire et la première connexion ;
- **Cas d'utilisation** (`cas-utilisation.*`) : les actions du visiteur, du client, de l'employé et de l'administrateur.

Les `.drawio` s'ouvrent dans draw.io ou diagrams.net, et s'importent dans Lucidchart. Les `.lucid` utilisent le format d'import standard de Lucidchart.

## 10. Fonctionnalités par rôle

**Visiteur**
- Accueil : présentation de l'entreprise et de l'équipe, avis validés, horaires.
- Catalogue des menus avec filtres sans rechargement (prix, fourchette de prix, thème, régime, nombre de personnes).
- Détail d'un menu : galerie d'images, plats avec leurs allergènes, conditions.
- Contact, demande de devis (facultative), inscription avec mot de passe provisoire envoyé par e-mail, mot de passe oublié, mentions légales, CGV, politique de confidentialité.

**Client** (en plus)
- Commande : menu pré-sélectionné, créneaux selon les horaires, prix détaillé (remise de 10 % dès le minimum + 5 personnes, livraison offerte dans Bordeaux, sinon 5 € + 0,59 €/km calculés par le serveur).
- Suivi des commandes avec l'historique, modification (tout sauf le menu) et annulation tant que la commande n'est pas acceptée.
- Avis (note de 1 à 5 et commentaire) une fois la commande terminée ; profil, changement de mot de passe (obligatoire à la première connexion), notifications.

**Employé**
- Commandes : filtres, changement de statut (mode de contact obligatoire, motif en cas d'annulation), modification après contact client (historisée), prêt de matériel.
- Menus (y compris épuisés), composition à partir des plats, galerie d'images ; CRUD des plats et de leurs allergènes.
- Horaires, avis à valider ou refuser, clients, devis.

**Administrateur** (en plus de tout ce que fait l'employé)
- Création d'employés (e-mail d'information sans le mot de passe), désactivation et réactivation.
- Tableau de bord : graphique des commandes par menu (données MongoDB) ; chiffre d'affaires par menu avec filtres menu et période.

## 11. Choix techniques : MariaDB et MongoDB

**MariaDB** contient les données structurées et liées entre elles, qui ont besoin d'intégrité (clés étrangères, transactions) : comptes, menus, plats, allergènes et leurs tables de liaison, commandes et historique des statuts, horaires, devis, notifications. Une commande est enregistrée dans une transaction : création, décrémentation du stock et première ligne d'historique, tout ou rien.

**MongoDB** contient des documents indépendants, lus tels quels :
- `menu_images` : la galerie de chaque menu (URL, texte alternatif, ordre), dont le nombre d'éléments varie ;
- `comments` : les avis clients et leur état de modération ;
- `menu_statistics` : les agrégats « commandes et CA par menu » affichés dans le graphique du tableau de bord (l'énoncé demande que ces données viennent d'une base NoSQL). Ils sont recalculés à partir des commandes terminées de MariaDB, le même critère que la page CA.

## 12. Sécurité en bref

- **Requêtes préparées** partout (PDO, préparations natives) ; aucune valeur saisie n'est concaténée dans le SQL.
- **CSRF** : jeton vérifié sur toutes les requêtes POST (middleware `Security`).
- **Échappement** de toutes les sorties dans les vues (`$escape()` = `htmlspecialchars`) ; en-têtes de sécurité (CSP sans script inline, seul `cdn.jsdelivr.net` est autorisé en plus du site ; `X-Frame-Options`, `nosniff`…). Bootstrap JS est vérifié par son empreinte SRI.
- **Mots de passe** : bcrypt (coût `PASSWORD_HASH_COST`), politique de robustesse unique, blocage 15 minutes après 5 échecs, limitation des tentatives par adresse IP ; lien de réinitialisation à usage unique valable 1 heure, stocké haché, annulé par une nouvelle demande ou un changement de mot de passe, refusé sur un compte désactivé ; une réinitialisation lève le blocage du compte. Mot de passe provisoire à changer obligatoirement à la première connexion.
- **Validation côté serveur** de toutes les saisies (le JavaScript ne fait qu'aider) ; prix, remises et frais de livraison recalculés par le serveur.
- **Téléphones** : validés avec libphonenumber (France, Espagne, Belgique, Royaume-Uni, Italie) et stockés au format international **E.164** (`+33612345678`).
- **Session** : cookie persistant renouvelé à l'activité, `HttpOnly`, `SameSite=Lax` et `Secure` en HTTPS ; expiration après 30 minutes d'inactivité par défaut ; identifiant régénéré à la connexion. `APP_DEBUG=false` par défaut (pas de trace d'erreur affichée).
- **Fichiers envoyés** (galerie) : type vérifié sur le contenu (JPEG, PNG, WebP), 5 Mo maximum, nom aléatoire ; le cache n'accepte aucun objet désérialisé.

## 13. Secrets et fichiers non versionnés

Aucun mot de passe ni clé n'est versionné. Ils vivent uniquement dans le `.env` (ignoré par git, voir `.gitignore`) : base MariaDB, MongoDB, SMTP, clé Google Maps, comptes de démonstration (`DEMO_*`) et accès SSH (`SSH_*`). `.env.example` en donne la liste, avec des valeurs vides. Les mots de passe des comptes sont stockés en base sous forme de hash bcrypt uniquement.
