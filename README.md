# Vite & Gourmand — ECF

Application de commande de menus traiteur développée en PHP Vanilla selon une architecture MVC.

## Pré-requis

- PHP 8+
- Composer
- MariaDB
- MongoDB
- Extension PHP MongoDB
- Serveur Apache pour le déploiement classique

## Installation locale

1. Cloner le dépôt.
2. Installer les dépendances :

```bash
composer install
```

3. Créer la base MariaDB et importer `database.sql`.
4. Configurer les variables d'environnement utilisées par `config/app.php` et l'envoi d'emails.
5. Vérifier la connexion MongoDB et importer les données prévues dans `docs/mongodb-comments.seed.json`.
6. Lancer le serveur de développement depuis la racine :

```bash
php -S 127.0.0.1:8080 -t public
```

Si le port 8080 est occupé :

```bash
php -S 127.0.0.1:8888 -t public
```

## Apache

Le dossier public du virtual host doit pointer vers `public/`.

Le fichier `public/.htaccess` redirige les requêtes qui ne correspondent pas à un fichier ou dossier existant vers `public/index.php`.

## Architecture

- `App/Controller` : contrôleurs
- `App/Entity` : entités
- `App/Repository` : accès MariaDB / MongoDB
- `App/Service` : logique métier
- `App/Middleware` : sécurité et contrôle d'accès
- `App/View` : vues PHP
- `public` : point d'entrée et ressources publiques
- `config` : configuration et routes

## Filtrage des menus

Le filtrage est exécuté côté serveur par PHP. Le JavaScript de `public/assets/js/script.js` ne contient pas les règles métier : il envoie les filtres et met à jour l'affichage sans rechargement, conformément à l'ECF.

## Vérification syntaxique

Un workflow GitHub Actions est présent dans `.github/workflows/php-check.yml`. En environnement local, le contrôle peut également être lancé avec :

```bash
find App config public -type f -name "*.php" -print0 | xargs -0 -n1 php -l
```

## Documentation ECF

- `docs/enoncer_ecf.md` : énoncé de référence
- `docs/RAPPORT-V4.MD` : historique
- `docs/RAPPORT-V6.MD` : état de la refactorisation actuelle
- `database.sql` : schéma et données relationnelles
