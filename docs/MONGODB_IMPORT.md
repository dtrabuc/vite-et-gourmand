# Import MongoDB — avis et statistiques

MongoDB est volontairement limité aux données non relationnelles : les avis clients et les statistiques de menus. Les utilisateurs, menus, plats, commandes et leur historique restent dans MariaDB, conformément à l’énoncé ECF.

## Import visuel avec MongoDB Compass

1. Créer ou ouvrir la base `vitegourmand`.
2. Créer la collection `comments`.
3. Cliquer sur **Add Data** puis **Import File**.
4. Sélectionner `mongodb-comments.seed.json` et choisir le format **JSON**.
5. Créer l’index `{ "isValidated": 1, "createdAt": -1 }` dans l’onglet **Indexes** de la collection.

## Import en ligne de commande

Depuis un terminal ayant accès à `mongoimport` :

```bash
mongoimport --db vitegourmand --collection comments --file docs/mongodb-comments.seed.json --jsonArray
```

Puis, dans `mongosh` ou MongoDB Compass, créer les index utilisés par l’accueil, la modération et les statistiques :

```javascript
db.comments.createIndex({ isValidated: 1, createdAt: -1 });
db.comments.createIndex({ userId: 1, orderId: 1 }, { unique: true });
db.menu_statistics.createIndex({ menuId: 1, periodIdentifier: 1 }, { unique: true });
```

Pour les statistiques de démonstration, le fichier `mongodb-menu-statistics.seed.json` peut être importé dans la collection `menu_statistics` de la même base. Les valeurs initiales sont volontairement à zéro : les statistiques réelles sont calculées par l’application à partir des commandes terminées.

Le fichier est un tableau JSON standard, compatible avec l’import visuel et avec `mongoimport --jsonArray`. Les trois documents sont des données de démonstration ; les identifiants `userId`, `menuId` et `orderId` sont des références applicatives, sans clé étrangère MongoDB. Les comptes et commandes correspondants doivent être créés dans MariaDB pour tester le parcours complet.
