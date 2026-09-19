# Import MongoDB

Le projet utilise deux collections dans la base `vitegourmand` :

- `comments`
- `menu_statistics`

Le fichier `db_mongo.json` est en JSON Lines : une ligne = un document. Le champ `_collection` indique la collection prévue.

MongoDB Compass importe un fichier dans une collection à la fois. Pour utiliser ce fichier, garder les lignes de la collection voulue puis importer le fichier obtenu dans Compass.

Index utiles :

```javascript
db.comments.createIndex({ isValidated: 1, createdAt: -1 });
db.comments.createIndex({ userId: 1, orderId: 1 }, { unique: true });
db.menu_statistics.createIndex({ menuId: 1, periodIdentifier: 1 }, { unique: true });
```

Les statistiques de départ sont à zéro. Les avis sont des données de démonstration ; les identifiants renvoient aux données applicatives MariaDB.
