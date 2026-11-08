# Retours de test utilisateurs — 8 novembre 2026

Deux personnes de l'entourage ont testé le site en production (testeuse A sur mobile, testeur B sur ordinateur). Les photos et captures d'origine restent hors du dépôt (dossier `feedback/`, ignoré par Git).

## Ce qu'ils ont remonté

| # | Testeur | Retour | Cause trouvée |
|---|---|---|---|
| 1 | B | « C'est pas encore trop clair ton site. » On ne sait pas à quoi on a droit ni comment le prix est calculé. | Le prix affiché (ex. 69,90 €) était le prix pour le minimum de personnes, pas un prix par personne, sans explication. |
| 2 | B | 15 personnes demandées, menu à 69,90 € affiché « en vert » mais impossible à valider. | Parcours en deux temps (fenêtre de confirmation puis liste déroulante) et envoi bloqué sans message quand un champ manquait. |
| 3 | B | Menu Réception Signature : « il manque les garnitures ». | La description annonçait deux garnitures, la composition n'en avait qu'une, classée parmi les plats. |
| 4 | B | Menu Familial : il manque un accompagnement (une salade), « les frites avec les lasagnes c'est hard ». | Composition peu cohérente ; les cartes n'affichaient que les deux premiers plats. |
| 5 | B | Impossible de se faire livrer à Saubens (Haute-Garonne). | Non confirmée : le calcul de distance a échoué. Les journaux de production restent à consulter. |
| 6 | A | Mail de confirmation : « Prix du menu : 314.1 € », date brute « 2026-11-08 10:59:59 ». | Montants et dates non mis en forme dans le mail. |

Autre incohérence relevée pendant l'analyse : par personne, le menu « premium » Signature (11,65 €) était le moins cher de la carte, et les noms « Solo / Duo / Trio » ne correspondaient plus au nombre de convives réellement commandé.

## Deuxième passe (même jour), après relecture par le porteur du projet

La première correction ne suffisait pas : « lasagnes + frites = nul », pas d'apéritif ni de digestif, un budget encore trop présent, une commande dont on ne savait pas si elle était validée, et toujours pas de solution pour Saubens.

### Composition des menus, revue un par un

| Menu | Pour chaque convive | Prix / pers. | Dès |
|---|---|---|---|
| Formule Enfant | Jus de pomme pétillant et gressins · carottes râpées · saucisse grillée, purée · compote (sans alcool, sans digestif) | 12,90 € | 1 |
| Formule Bistrot | Kir ou cocktail sans alcool, gougères · œuf mayonnaise · steak haché sauce échalote, frites maison · crème caramel · café | 18,90 € | 1 |
| Formule Végétale | Cocktail sans alcool, toasts de houmous · carottes râpées · dahl de lentilles, riz basmati · salade de fruits · infusion (100 % végétal) | 19,90 € | 1 |
| Menu Familial | Kir ou cocktail, gougères · tomates mozzarella · **lasagnes, salade verte** · mousse au chocolat · café | 20,90 € | 3 |
| Menu Convivial | Kir ou cocktail, gougères · crudités · poulet rôti, purée, haricots verts · tarte aux pommes · café | 21,90 € | 2 |
| Menu Végétarien du Marché | Kir ou cocktail, gougères · salade de lentilles · galette végétale, haricots verts · panna cotta · café ou infusion | 22,90 € | 3 |
| Menu Végétal Élégance | Cocktail sans alcool, toasts de houmous · velouté · curry de légumes, riz basmati · salade de fruits · infusion (100 % végétal) | 25,90 € | 2 |
| Menu Marée de Printemps | Crémant ou cocktail · tarte fine aux légumes · saumon rôti, riz basmati, haricots verts · fromage blanc aux fruits rouges · café ou Armagnac | 28,90 € | 2 |
| Menu Réception Signature | Crémant ou cocktail · tarte fine · volaille farcie **ou** bœuf mijoté, gratin dauphinois, légumes rôtis · fondant au chocolat · café ou Armagnac | 34,90 € | 6 |
| Menu de Noël (nouveau) | Crémant ou cocktail · terrine de foie gras · chapon aux marrons, gratin dauphinois, haricots verts · bûche glacée · café ou Armagnac | 36,90 € | 4 |

Règles suivies : un accompagnement qui va avec le plat ; pas deux fois le même ingrédient (le dahl ne suit plus une salade de lentilles) ; entrée de saison (plus de tomates mozzarella à Noël) ; apéritif toujours proposé sans alcool ; pas d'alcool dans le menu enfant.

### Parcours

- **Nombre de personnes d'abord**, avec choix rapide (1, 2, 4, 6, 10, 15, 20). Tous les menus possibles s'affichent avec leur total ; ceux qui demandent plus de monde sont repliés, jamais bloquants. Le budget n'est plus qu'un filtre facultatif.
- **Validation visible** : page « Commande envoyée ! » avec suivi en 5 étapes, e-mail de confirmation, puis notification et e-mail à chaque changement d'état.
- **Notifications** : cloche avec compteur dans la barre de navigation, bulles en direct.
- **Adresses** : vérifiées sur OpenStreetMap (Photon pour la recherche, OSRM pour la distance), sans clé d'API. Bordeaux : gratuit. Jusqu'à 25 km : 5 € + 0,59 €/km. Au-delà, par exemple Saubens (216 km à vol d'oiseau, 260 km par la route) : retrait gratuit ou livraison hors zone acceptée (≈ 158 €). Adresse introuvable ou service indisponible : saisie manuelle, les frais étant alors calculés depuis la commune ou confirmés par l'équipe.

## Troisième passe : choix dans les menus, délai de modification, sécurité

- **Composer son menu** (prix inchangé) : répartir les convives entre les options (ex. 10 kirs, 5 cocktails), remplacer un plat par un autre de la même catégorie compatible avec le menu, demander une variante « sans gluten » pour quelques convives, et noter des allergies (consentement obligatoire, données de santé).
- **Délai de modification** : en plus de la règle de l'énoncé (commande pas encore acceptée), 15 min si la prestation a lieu le jour même, 24 h si c'est le lendemain, 48 h au-delà, jamais après la prestation. Le délai exact est affiché.
- **Mail de bienvenue** : vérifié, envoyé à chaque inscription.
- **Sécurité** : aucune injection SQL possible (requêtes préparées natives), contrôle d'accès vérifié route par route (134 contrôles), pages Confidentialité et CGV restaurées, export et suppression du compte depuis le profil.

## Première passe (remplacée en partie par la deuxième)

S'inspirer des applis de livraison (Uber Eats, Deliveroo) : le prix est connu dès la première page.

- **Catalogue `/menus`** : sélecteur « Pour combien de personnes ? » (− / +) en haut de page, puces de thème et de régime, budget par personne. Chaque carte affiche le prix par personne, « dès N pers. », ce qui est **inclus pour chaque convive** (1 entrée, 1 plat, les accompagnements, 1 dessert) et le **total** pour le nombre choisi, avec la remise. Les menus dont le minimum est trop élevé passent en fin de liste, grisés. Un encadré explique le calcul du prix.
- **Fiche menu** : encadré prix avec calculateur du total.
- **Commande** : plus de fenêtre de confirmation. Le nombre de personnes est repris du catalogue, les menus sont des cartes avec leur total, un récapitulatif reste visible (personnes × prix, remise, livraison, total), et un message « Il manque : … » remplace le blocage muet. Testé : 15 personnes, menu Signature, commande validée.
- **Données** : catégorie « Accompagnement », compositions corrigées (Signature : gratin dauphinois et légumes rôtis ; Familial : salade verte au lieu des frites), noms sans « Solo / Duo / Trio », prix par personne de 11,90 € à 29,90 €, croissants avec la gamme.
- **Mails** : montants « 314,10 € », dates en toutes lettres, détail « 15 × 29,90 €, remise de 10 % incluse ».
- **Livraison impossible** : message plus clair, qui propose le retrait.

## Reste à faire

- Mettre en production (sauvegarde, envoi, `php scripts/update-menus.php` sur le serveur) et vérifier que l'hébergeur autorise les appels sortants vers photon.komoot.io et router.project-osrm.org.
- Importer les maquettes dans Figma quand la limite d'appels sera levée (HTML prêts dans `docs/maquettes`).
- Faire retester par les mêmes personnes.
