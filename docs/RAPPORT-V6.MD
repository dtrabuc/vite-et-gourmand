# Rapport de travail — Vite & Gourmand ECF — Version 6

## 1. Base de travail

Cette version reprend l'architecture MVC PHP Vanilla présente dans le projet et les exigences de `docs/enoncer_ecf.md`. Le rapport V4 est conservé comme historique et ne constitue pas une preuve de fonctionnement de chaque fonctionnalité.

Le principe retenu est de conserver le HTML/CSS déjà réalisé dans `App/View` et `public/assets/css`, tout en déplaçant les règles métier et l'accès aux données côté PHP.

## 2. Fonctionnalités ECF traitées

### Catalogue
- Catalogue public des menus.
- Détail complet d'un menu.
- Régime alimentaire.
- Stock disponible.
- Conditions visibles.
- Plats classés entrée / plat / dessert.
- Allergènes issus de la base relationnelle.
- Filtres prix, thème, régime et nombre minimum de personnes.
- Actualisation dynamique des résultats de filtrage sans rechargement grâce au JavaScript léger conservé dans `public/assets/js/script.js`.

### Authentification
- Inscription avec rôle utilisateur imposé côté serveur.
- Mot de passe de 10 caractères minimum avec majuscule, minuscule, chiffre et caractère spécial.
- Connexion.
- Déconnexion.
- Mot de passe oublié avec jeton temporaire et lien par email.
- Réinitialisation du mot de passe.
- Modification du profil.

### Commandes
- Création avec menu préselectionné.
- Contrôle du nombre minimum de personnes.
- Calcul de la livraison : Bordeaux sans frais ; hors Bordeaux, 5 € + 0,59 €/km.
- Remise de 10 % à partir de 5 personnes au-dessus du minimum du menu.
- Prix mémorisé dans la commande.
- Génération d'un numéro de commande.
- Historique des statuts avec date et heure.
- Annulation client uniquement avant le statut « accepté ».
- Modification client uniquement avant « accepté », sans changement de menu.
- Gestion du stock lors de la création et libération du stock en cas d'annulation.
- Email de confirmation.

### Espace utilisateur
- Liste des commandes personnelles.
- Détail et suivi des états.
- Dates et heures des changements de statut.
- Modification des informations personnelles.
- Modification de la commande avant acceptation.
- Avis après passage à « terminée », note de 1 à 5 et commentaire.
- Stockage des avis dans MongoDB.

### Espace employé / administrateur
- Middleware « Staff » pour les fonctionnalités communes employé / administrateur.
- Gestion des menus.
- Gestion des plats et association d'un même plat à plusieurs menus.
- Gestion des horaires.
- Filtrage des commandes par statut et client.
- Mise à jour des statuts dans l'ordre métier prévu.
- Obligation de renseigner le mode de contact avant une modification de commande par le personnel.
- Motif obligatoire et mode de contact obligatoire pour une annulation.
- Modération des avis.
- Création de comptes employés uniquement par l'administrateur.
- Le mot de passe fourni par l'administrateur n'est pas communiqué dans le mail de notification.
- Désactivation / réactivation des comptes employés.
- Interdiction de créer un compte administrateur depuis l'interface.
- Statistiques de commandes par menu et comparaison graphique.
- Chiffre d'affaires par menu avec filtres de menu et de période.

### Contact et informations légales
- Formulaire de contact : titre, description et email.
- Enregistrement de la demande dans MariaDB.
- Envoi de la demande vers l'adresse configurée.
- Contact présent dans la navigation.
- Horaires affichés dans le pied de page depuis MariaDB.
- Pages Mentions légales et CGV créées avec emplacements à compléter par les informations juridiques réelles de l'entreprise.

## 3. Architecture

Structure principale :
- `App/Controller`
- `App/Core`
- `App/Entity`
- `App/Middleware`
- `App/Repository`
- `App/Service`
- `App/View`
- `config`
- `public`

Les anciennes implémentations de démonstration JavaScript ne servent plus de source de vérité pour les règles métier.

## 4. Données

MariaDB conserve les utilisateurs, menus, plats, commandes, historique des statuts, horaires et messages de contact.

MongoDB conserve les avis et les statistiques non relationnelles prévues par l'ECF.

Les données de commande sont persistées afin que le prix historique ne dépende pas d'une modification ultérieure du menu.

## 5. Sécurité traitée

- Mots de passe avec `password_hash()`.
- Vérification avec `password_verify()`.
- Jetons de réinitialisation stockés sous forme hachée.
- Protection CSRF via le middleware Security.
- En-têtes de sécurité.
- Limitation des tentatives sur les routes d'authentification.
- Contrôle des rôles côté serveur.
- Les rôles publics ne peuvent pas être fournis par l'utilisateur lors de l'inscription.

## 6. Contrôle technique

Un workflow GitHub Actions `.github/workflows/php-check.yml` a été ajouté pour lancer les contrôles de syntaxe PHP sur `App`, `config` et `public`.

Le contrôle doit être exécuté sur GitHub ou dans l'environnement WSL avant la livraison finale.

## 7. Points restant à valider avant livraison ECF

- Exécution réelle de l'application sur l'environnement WSL avec MariaDB et MongoDB.
- Test de chaque parcours ECF avec données réelles.
- Vérification de la configuration mail du serveur de production.
- Remplacement des informations génériques des mentions légales et CGV par les informations officielles.
- Vérification RGAA.
- Déploiement en ligne et test de l'URL publique.
- Finalisation des livrables de conception et documentation demandés par l'ECF : MCD, diagramme de classes, cas d'utilisation, séquences, charte graphique, wireframes, manuel utilisateur et documentation de déploiement.

## 8. Règle de référence

En cas de contradiction entre une ancienne implémentation JavaScript, un ancien rapport et l'énoncé ECF, l'énoncé ECF constitue la référence fonctionnelle.
