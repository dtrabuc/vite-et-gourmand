# Vite & Gourmand — ECF DWWM

Projet étudiant à défendre devant un jury. Reprise d'un existant : on refactorise, on ne repart jamais de zéro.

## Stack imposée
PHP Vanilla + Composer (PSR-4) · MVC maison inspiré de Symfony · PDO/MariaDB · MongoDB · HTML/CSS/JS vanilla.
Interdit : Symfony, Laravel, Slim, CodeIgniter, tout framework MVC.

## Architecture
- Point d'entrée unique `public/index.php`, doit tourner avec `php -S 127.0.0.1:8080 -t public`
- `src/` : Controller, Entity, Repository, Service, Middleware, Router, Security, Database, Http, Validation
- Contrôleurs fins (requête → service → vue). Métier dans les Services. SQL/Mongo uniquement dans les Repositories.
- `public/.htaccess` pour Apache ; `src/`, `config/`, `templates/`, `vendor/`, `.env` jamais exposés
- MariaDB = users, menus, plats, commandes, relations. MongoDB = avis/statistiques. Pas de mélange.

## Règles de code
- Simple, typé, lisible. Pas de sur-ingénierie, pas de pattern décoratif, pas de pseudo-framework de 200 classes.
- Commentaires sobres, en français, style étudiant sérieux : `// On récupère le menu avant de calculer le prix`.
  Jamais d'emoji, de superlatif, de ton marketing, ni de commentaire ligne par ligne.
- Requêtes préparées PDO. `password_hash()` / `password_verify()`. CSRF, XSS, échappement HTML, contrôle des rôles côté serveur uniquement.
- Rôles : USER, EMPLOYEE, ADMIN. Aucun moyen de créer un ADMIN depuis le front.

## Environnement
WSL2 / Ubuntu 24.04 sous Windows 11. Vérifier les extensions `pdo_mysql` et `mongodb` avant de coder dessus.

## Interdits absolus
- Ne jamais annoncer qu'un test passe sans l'avoir lancé.
- Ne jamais masquer une erreur en changeant le message affiché.
- Ne jamais supprimer un fichier sans avoir vérifié ses références (grep).
- Ne jamais documenter une fonctionnalité qui n'existe pas.