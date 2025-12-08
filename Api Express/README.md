# API Express A2S

Application Express agissant comme passerelle entre l'ERP Dolibarr et les interfaces clientes A2S. Elle centralise l'authentification et expose un ensemble d'API REST pour les tiers, produits, commandes et données de configuration.

## Prérequis
- Node.js ≥ 20 (utilise l'option `--env-file`)
- npm (fourni avec Node.js)
- Accès à une instance Dolibarr exposant les routes utilisées par l'API

## Installation
1. Cloner ce dépôt ou récupérer le dossier `Api Express`.
2. Installer les dépendances :
   ```bash
   npm install
   ```

## Configuration
L'application lit ses variables d'environnement via un fichier `.env`. Créez-le à la racine du projet en vous appuyant sur l'exemple ci-dessous :

```dotenv
PORT=3001              # Port d'écoute de l'API Express (optionnel, 3000 par défaut)
BASE_URL=https://dolibarr.example.com/api/index.php
```

- `BASE_URL` doit pointer vers l'API Dolibarr cible.
- Les requêtes protégées exigent l'en-tête `DOLAPIKEY` contenant le token Dolibarr retourné par `/login`.

## Scripts NPM
- `npm start` : lance l'API en production (`node --env-file=.env src/app.js`).
- `npm run devstart` : lance l'API avec `nodemon` pour recharger automatiquement en développement.

## Lancement
```bash
npm run devstart        # Mode développement
# ou
npm start               # Mode production
```

Par défaut l'application écoute sur `http://localhost:3000` et accepte les origines front suivantes : `http://localhost:5173`, `http://127.0.0.1:5173`, `http://localhost:3000`.

## Structure du projet
```
src/
├── app.js                     # Point d'entrée Express et configuration CORS
├── router/                    # Définition des routes REST exposées
│   ├── AuthRouter.js
│   ├── OrderRouter.js
│   ├── ProductRouter.js
│   ├── ProposalRouter.js      # Réservé pour les devis/propositions
│   ├── SetupRouter.js
│   └── ThirdpartiesRouter.js
└── servcice/                  # Services d'appel Dolibarr (axios) et helpers de réponse
    ├── axiosService.js
    └── responseService.js
```

## Routes exposées
Toutes les routes ci-dessous sont préfixées par `/api` dans `src/app.js`.

### Authentification
| Méthode | Chemin | Description | Corps attendu |
|---------|--------|-------------|---------------|
| POST | `/auth/login` | Obtient un token Dolibarr | `{ "login": "user", "password": "secret" }` |

### Tiers (`/thirdparties`)
| Méthode | Chemin | Description | Paramètres |
|---------|--------|-------------|------------|
| GET | `/` | Liste paginée des tiers | `page` (obligatoire), `client_type` (filtre sur le type de client) |
| GET | `/:id` | Détails d'un tiers | `id` : identifiant du tiers |
| GET | `/:id/fixedamountdiscounts` | Liste des remises fixes associées | `id` : identifiant du tiers |

### Produits (`/products`)
| Méthode | Chemin | Description | Paramètres |
|---------|--------|-------------|------------|
| GET | `/` | Liste paginée des produits | `page` (obligatoire), `mode`, `category` |
| GET | `/:id` | Détails d'un produit | `id` : identifiant Dolibarr |
| GET | `/:id/stock` | Stock d'un produit | `id` : identifiant Dolibarr |
| GET | `/products/barcode/:barcode` | Recherche par code-barres | `barcode` : code-barres recherché |

### Commandes (`/orders`)
| Méthode | Chemin | Description | Paramètres |
|---------|--------|-------------|------------|
| GET | `/` | Liste paginée des commandes | `page` (obligatoire), `thirdparty_ids` |
| GET | `/:id` | Détails d'une commande | `id` : identifiant de la commande |

### Configuration (`/setup`)
| Méthode | Chemin | Description | Paramètres |
|---------|--------|-------------|------------|
| GET | `/` | Types de paiements (dictionnaire Dolibarr) | `page` |

## Notes complémentaires
- Les appels sortants sont gérés via `axios` et héritent du header `DOLAPIKEY` fourni par le client.
- La fonction `responseToDTO` constitue un point d'extension pour adapter la structure des réponses si nécessaire.
- `ProposalRouter.js` est présent mais n'est pas encore câblé dans `app.js`; ajoutez-le si l'API doit exposer les propositions.

## Tests
Aucun test automatisé n'est encore défini (`npm test` génère une erreur intentionnelle). Pensez à ajouter des tests de contract pour sécuriser l'intégration avec Dolibarr lors des évolutions futures.


## Tests API via Postman

Pour faciliter les tests de l’API Express (passerelle vers Dolibarr), un dossier **`postman/`** est inclus dans ce projet.  
Il contient une **collection Postman exportée au format JSON**, regroupant l’ensemble des endpoints disponibles.

Cette collection permet de :
- envoyer des requêtes authentifiées avec le header `DOLAPIKEY`
- interroger les tiers, produits, commandes, devis, etc.
- valider rapidement le fonctionnement de l’API et de sa connexion à Dolibarr

[`postman/README.md`](./postman/README.md)
