# Vite & Gourmand — synthèse technique V1 et plan de refactorisation (48 h)

Date de l'analyse : 17 septembre 2026  
Périmètre : code actuellement présent, `CLAUDE.MD`, `docs/enoncer_ecf.md`, `docs/RAPPORT-V4.MD` et `docs/RAPPORT-V5.MD`.

## Conclusion

Le projet est une reprise en cours de migration d'une ancienne arborescence `src/` vers `App/`. La cible est compatible avec l'ECF : PHP vanilla, MVC maison, Composer/PSR-4, MariaDB pour le relationnel et MongoDB pour les avis/statistiques. En revanche, il ne faut pas le présenter comme fonctionnel à ce stade : les écrans sont vides, les données et le code divergent, certains parcours produiront des erreurs d'exécution, les services de données ne répondent pas avec la configuration actuelle et aucun test métier n'est disponible.

La priorité des 48 heures est de livrer un noyau ECF fiable et démontrable, pas d'ajouter Redis, des statistiques avancées ou des fonctionnalités secondaires.

## Sources et état documentaire

| Source | Apport | Écart constaté |
| --- | --- | --- |
| `CLAUDE.MD` | Règles de l'architecture cible, sécurité et séparation MariaDB/MongoDB. | Il cite encore `src/` comme architecture active, alors que l'application chargée est dans `App/`. |
| `docs/enoncer_ecf.md` | Cahier des charges : menus, filtres dynamiques, compte, commande, rôles, avis et documentation de déploiement. | Plusieurs exigences ne sont pas encore implémentées ou démontrables. |
| `docs/RAPPORT-V4.MD` | Historique de fonctionnalités annoncées. | Son affirmation qu'une URI MongoDB remplace l'extension PHP MongoDB est techniquement fausse : le pilote PHP reste requis. |
| `docs/RAPPORT-V5.MD` | Historique de migration de `src/` vers `App/`. | La migration est incomplète : fichiers de sauvegarde conservés, scripts Composer et éléments de code non alignés. |

Les rapports V4/V5 sont donc utiles comme journal de travail, mais ne constituent pas une preuve de fonctionnement. Cette synthèse privilégie les constats vérifiés sur le dépôt.

## Architecture réellement chargée

```text
public/index.php
  -> vendor/autoload.php (PSR-4 App\\)
  -> config/app.php + config/routes.php
  -> App\\Core\\Router
  -> App\\Controller
  -> App\\Service
  -> App\\Repository
  -> MariaDB / MongoDB
  -> App\\View (layouts et pages)
```

Points positifs :

- Le contrôleur frontal est bien `public/index.php` et Composer déclare `App\\` vers `App/`.
- Les couches Controller, Service, Repository, Entity et Middleware existent.
- Les accès MariaDB des repositories utilisent des requêtes préparées ; `password_hash()` et `password_verify()` sont employés.
- La configuration PDO active les exceptions, le jeu de caractères `utf8mb4` et désactive les préparations émulées.
- PHP WSL est en version 8.4.25 et les extensions `PDO`, `pdo_mysql` et `mongodb` sont chargées. Les clients `mariadb` et `mysql` WSL sont installés.
- Tous les fichiers PHP applicatifs ont passé `php -l` lors de cette analyse.

## Anomalies prioritaires vérifiées

### Bloquantes

1. **Couche de vues vide.** Les 13 fichiers sous `App/View/` (pages et layouts) ont une taille nulle. Toutes les pages rendues sont donc vides, alors que l'ECF attend une interface complète (accueil, catalogue, détails, commande, profil et espaces de rôle).

2. **Schéma MariaDB incompatible avec le code.** `database.sql` ne crée que `users`, `menus`, `menu_items` et `orders`. Le code utilise pourtant notamment `users.failed_attempts`, `locked_until`, `reset_token_hash`, `reset_token_expires_at`, `orders.order_date`, `delivery_cost`, `menu_price`, `total_price` et la table `order_status_history`. Une installation depuis le script SQL ne peut donc pas faire fonctionner inscription, authentification renforcée, création de commande ni suivi.

3. **Accès MongoDB cassé dans les avis.** `App/Repository/CommentRepository.php` importe `App\\Core\\Database\\Database`, classe inexistante ; la classe réelle est `App\\Core\\Database`. Il force aussi la base `viteetgourmand`, différente du défaut configuré `vitegourmand`. Enfin, l'identifiant MongoDB est transformé en entier pour la mise à jour alors qu'un `ObjectId` est attendu.

4. **Paramètres de routes incompatibles avec les signatures.** Le routeur transmet chaque variable d'URL comme argument scalaire. `PublicController::getMenuById`, `OrderController::confirmation` et `OrderController::updateStatus` attendent un tableau : une URL telle que `/menu/1` déclenchera un `TypeError` au lieu de recevoir l'identifiant.

5. **Connexions locales non validées.** Un essai en lecture seule via le PHP WSL et les variables du `.env` a échoué pour MariaDB (`PDOException`) et MongoDB (`MongoDB\\Driver\\Exception\\InvalidArgumentException`). Aucun mot de passe n'est reproduit ici. Le binaire MongoDB Windows (`mongod.exe` ou `mongosh.exe`) n'est pas présent dans les emplacements standards inspectés ; il n'est donc pas possible d'attester une communication WSL vers MongoDB Windows.

### Importantes avant démonstration ECF

6. **Scripts de qualité obsolètes.** `composer cs` et `composer cs-fix` ciblent `src/`, répertoire absent (déplacé en `src.backup`). La commande échoue. `composer test -- --list-tests` ne découvre aucun test applicatif.

7. **Routage et middleware à clarifier.** Toutes les routes reçoivent seulement `Security`; les protections Auth/Admin sont recodées dans les contrôleurs. Plusieurs routes POST dites « Temporary » doublent PUT/PATCH/DELETE. Cette responsabilité doit être centralisée dans les routes et le routeur.

8. **CSRF et limitation de débit incomplets.** Le middleware contrôle un jeton, mais les vues vides ne le génèrent ni ne l'envoient. Le limiteur repère `/login`, `/register`, etc., alors que ses règles sont nommées `login`, `register`, `password` : les clés ne correspondent pas et la règle ne s'applique pas.

9. **Commande non atomique et règles métier incomplètes.** La création diminue le stock après l'insertion sans transaction ni contrôle du nombre de lignes modifiées. Le prix de livraison est toujours 5 EUR, sans calcul Bordeaux/0,59 EUR par kilomètre prévu dans l'énoncé. Les transitions de statut ne sont pas validées.

10. **Catalogue et données métier incomplets.** Le schéma ne porte ni régime, ni galerie/images, ni relation réutilisable d'un plat entre plusieurs menus. Le filtre serveur ne couvre ni fourchette de prix ni régime. L'endpoint de catalogue classe des menus selon des champs `category`, `type` ou `alcool` absents du schéma.

11. **Fonctions ECF absentes ou seulement simulées.** Contact, mentions légales, CGV, horaires configurables, détail de menu, modification/annulation de commande, dépôt d'avis après commande terminée, email réellement envoyé et droits Employé ne sont pas démontrables dans le code et les vues actuelles.

12. **Dette de dépôt.** `src.backup/` et des fichiers `*.backup` / `*.redis_backup` sont toujours versionnés ou présents. Ils doivent être isolés du code actif après comparaison, sans suppression précipitée.

## Écarts aux règles de la stack

- La direction MVC est la bonne, mais les contrôleurs instancient directement services et repositories. Pour le périmètre ECF, une fabrique de services simple dans le bootstrap suffira ; aucun conteneur complexe n'est nécessaire.
- MariaDB doit rester la source des utilisateurs, menus, plats, commandes et relations. MongoDB doit être limité aux avis et statistiques. Le repository d'avis enrichit actuellement chaque avis par une requête MariaDB (risque N+1) : mieux vaut enregistrer un pseudonyme figé dans l'avis ou charger les utilisateurs par lot.
- Redis est optionnel et non requis par l'énoncé. Son intégration doit être désactivable ; elle ne doit jamais retarder les corrections bloquantes.
- Les rôles définis dans le SQL sont `user`, `employee`, `admin`, alors que la convention de `CLAUDE.MD` est `USER`, `EMPLOYEE`, `ADMIN`. Choisir une seule convention (recommandation : valeurs minuscules en base, constantes PHP explicites) puis l'appliquer partout.

## Plan d'action exécutable en 48 heures

Le plan suppose une base de travail stable et des décisions fonctionnelles limitées au cahier des charges. Toute étape finit par un contrôle enregistré dans le README de déploiement ou une checklist de recette.

| Créneau | Objectif et livrables | Critère de sortie |
| --- | --- | --- |
| H0–H3 | Créer une branche de refactorisation, inventorier les modifications non committées, fixer `composer.json` (`App/`), ajouter `.env.example` sans secret et une commande `composer test`. Définir une convention de rôles et de réponses HTTP. | `composer dump-autoload`, `composer cs` et la découverte PHPUnit ciblent les chemins actifs. |
| H3–H8 | Réconcilier MariaDB : produire une migration idempotente ou régénérer `database.sql` avec toutes les colonnes réellement utilisées, `order_status_history`, contraintes et index. Ajouter les entités/champs manquants ou retirer les usages non retenus. | Import sur une base de développement WSL puis inscription, connexion et création d'une commande sans erreur SQL. |
| H8–H11 | Corriger le noyau HTTP : router, paramètres d'URL typés, 404/405, méthode `_method`, middleware déclaratifs Auth/Admin, CSRF généré et vérifié, limiteur corrigé. | Tests manuels documentés des routes publiques, invité, utilisateur et administrateur ; 403 CSRF vérifié. |
| H11–H15 | Stabiliser MongoDB : installer/démarrer MongoDB Windows si absent, l'exposer explicitement à WSL (hôte/IP et pare-feu), corriger le repository, créer les index `comments(isValidated, createdAt)` et valider le ping. Prévoir un message d'erreur propre si Mongo est indisponible. | PHP WSL exécute un `ping`, crée un avis, le relit et le modère sur le binaire MongoDB Windows. |
| H15–H22 | Implémenter les vues HTML minimales mais complètes : layout, accueil, catalogue avec filtres JS sans rechargement, détail menu, inscription/connexion, formulaire de commande avec token CSRF. Échapper toutes les sorties HTML. | Navigation publique utile, filtres dynamiques visibles, commande préremplie uniquement pour un utilisateur connecté. |
| H22–H28 | Fiabiliser le métier commandes : validation date/heure/adresse, calcul de prix et remise, politique de livraison explicitement testée, transaction SQL pour stock + commande + historique, transitions de statut autorisées, annulation avant acceptation. | Aucun stock négatif sous deux tentatives concurrentes ; historique visible et droits appliqués côté serveur. |
| H28–H34 | Compléter les fonctions de jury : profil, commandes utilisateur, administration/Employé des menus-plats-horaires, modération d'avis, mentions légales, CGV et contact. Écarter du périmètre toute fonction non achevable (newsletter, fidélité, multilingue). | Checklist de l'énoncé couverte, avec une page ou un parcours fonctionnel pour chaque exigence retenue. |
| H34–H40 | Écrire les tests prioritaires PHPUnit : prix/remise, droits, mots de passe, transitions de commande, repository SQL et avis Mongo. Ajouter un jeu de données de démonstration reproductible. | `composer test` vert avec au moins les règles métier critiques couvertes. |
| H40–H45 | Assainir le dépôt : comparer les sauvegardes, déplacer celles à conserver hors du code actif ou les archiver après validation, retirer les références à `src`, vérifier qu'aucun secret n'est suivi. Ne supprimer qu'après recherche des références et validation Git. | Recherche `rg 'Src\\|src/' App config public composer.json` sans référence active ; arborescence compréhensible au jury. |
| H45–H48 | Recette finale et dossier jury : démarrage WSL, import MariaDB, démarrage MongoDB Windows, variables d'environnement, comptes de démo, tests des quatre rôles, captures et limites connues. | Une personne peut installer puis présenter le projet en suivant uniquement la documentation. |

## Pré-requis d'environnement à résoudre au début

1. Garder PHP, Composer et MariaDB dans WSL Ubuntu 24.04 ; démarrer MariaDB et corriger les variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` sans inscrire de secret dans Git.
2. Installer MongoDB Community Server côté Windows si `mongod.exe` est réellement absent, lancer son service, autoriser la connexion depuis WSL dans le pare-feu Windows et renseigner l'hôte joignable par WSL dans `MONGO_CONNECTION_STRING` ou les variables `MONGO_*`.
3. Utiliser le pilote PHP MongoDB déjà chargé dans WSL. Une chaîne de connexion ne remplace pas ce pilote.
4. Documenter la commande de vérification non destructive : ping MongoDB depuis PHP WSL et `SELECT 1` via PDO MariaDB WSL. Ne jamais documenter de mot de passe.

## Fichiers de données révisés

- `database.sql` est désormais un schéma MariaDB lisible dans phpMyAdmin, destiné à une base vierge et sans instruction de suppression. Il couvre les comptes, menus, galerie, plats réutilisables, allergènes, commandes, historique, horaires et demandes de contact.
- `docs/mongodb-comments.seed.json` contient seulement des avis, au format JSON standard importable avec MongoDB Compass ou `mongoimport --jsonArray`.
- `docs/MONGODB_IMPORT.md` décrit la séparation SQL/NoSQL et les index MongoDB nécessaires aux avis et aux statistiques.

## Décisions de périmètre recommandées

- Garder un MVC maison simple, sans Symfony ni autre framework.
- Garder Redis et les statistiques avancées hors du chemin critique ; les rendre facultatifs ou les reporter après la soutenance.
- Prioriser les parcours démontrables : visiteur → inscription → connexion → filtre/détail menu → commande → gestion de statut → avis → modération.
- Conserver `src.backup/` seulement tant que la comparaison n'est pas terminée ; il ne doit plus influencer l'autoload, les scripts ni la démonstration.
