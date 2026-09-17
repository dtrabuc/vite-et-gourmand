# Migration du frontend historique vers le MVC

Le dossier externe `frontend-legacy` a été utilisé comme référence visuelle et fonctionnelle. Aucun fichier de l’application MVC ne le charge : le projet fonctionne uniquement avec `App/View` et `public/assets`.

## Éléments repris dans le MVC

| Ancien écran | Destination MVC | Comportement |
| --- | --- | --- |
| `index.html` | `App/View/home/index.php` | Accueil, menus à la une, avis validés. |
| `menus.html` et `detail-menus.html` | `App/View/home/menus.php`, `menu_detail.php` | Catalogue, filtres dynamiques, détail et accès à la commande. |
| `login.html`, `compte.html` | `App/View/auth/login.php`, `register.php`, `profile.php` | Connexion, inscription et profil par formulaires PHP sécurisés. |
| `commander.html`, `recap-commande.html` | `App/View/order/new.php`, `confirmation.php`, `index.php` | Commande et suivi ; le backend valide les données. |
| `admin-login.html`, `admin-dashboard.html` | `App/View/auth/admin_login.php`, `App/View/admin/dashboard.php` | Accès équipe et vue de synthèse. |

Les composants communs (navigation, pied de page, messages flash et jeton CSRF) sont dans `App/View/layout`.

## JavaScript conservé, mais assaini

Les fichiers `public/assets/js/api.js`, `script.js` et `admin.js` sont conservés et réécrits :

- aucune donnée utilisateur, commande, prix ou stock n'est stockée dans `localStorage` ou `sessionStorage` ;
- aucun compte de démonstration ou jeton frontend n'existe ;
- le filtre de catalogue appelle l'endpoint PHP `/menus/filter` sans rechargement ;
- les montants, rôles, stock, création de commande et transitions d'état doivent rester contrôlés côté PHP/MariaDB.

Les contenus non encore présents dans le MVC (contact, CGV et mentions légales, gestion complète des plats/images/horaires) sont à créer côté routes, contrôleurs, services et vues ; ils ne doivent pas être réintroduits comme logique JavaScript locale.
