# Conception — Vite & Gourmand

| Diagramme | Fichiers | Contenu |
|---|---|---|
| Diagramme de classes UML | `diagramme-classes.*` | Entités MariaDB avec attributs **et méthodes**, documents MongoDB, services applicatifs (prix, composition, livraison OpenStreetMap, délai de modification, notifications, e-mails, RGPD, sécurité) |
| Modèle conceptuel de données (Merise) | `mcd.*` | Entités, associations et cardinalités de la base relationnelle ; MongoDB en encadré |
| Cas d'utilisation | `cas-utilisation.*` | Visiteur, client, employé, administrateur, service de cartographie et serveur e-mail |
| Séquence : inscription | `sequence-inscription.*` | Mot de passe choisi ou généré, contrôles, mail de bienvenue, connexion |
| Séquence : commande | `sequence-commande.*` | Nombre de personnes, composition du menu, vérification de l'adresse, validation, notifications, délai de modification |

Formats : `.drawio` (modifiable dans draw.io / diagrams.net), `.svg` (vectoriel) et `.png`.

Les trois formats sont générés à partir de `outils/diagrams.py` (même mise en page partout) :

```bash
python3 docs/conception/outils/diagrams.py docs/conception
```

Le PNG est ensuite exporté depuis le SVG (navigateur ou draw.io).
