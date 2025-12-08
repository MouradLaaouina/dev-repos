# A2S B2B Front

Interface commerciale B2B destinée aux équipes A2S. L'application est construite avec **React 19**, **Vite 7** et **Tailwind CSS** afin de proposer un workflow fluide pour la consultation clients, la préparation de devis et la création de commandes en mobilité.

## Fonctionnalités principales
- Authentification simulée côté front avec persistance locale (mode démo).
- Tableau de bord menant aux parcours "Nouvelle commande" et "Mes devis".
- Recherche de clients et affichage des informations clés.
- Construction de devis : sélection de produits, calcul des remises, choix des moyens de paiement et génération d'un récapitulatif.
- Lecture de code-barres via l'appareil photo (composant `BarcodeScanner`).
- Consultation d’une liste de devis mockés.

> Les services (`authService`, `productService`, `orderService`, `clientService`) s'appuient actuellement sur des données mockées (`src/services/mockData.js`). Ils constituent une base à remplacer par les appels vers l'API Express A2S.

## Prérequis
- Node.js ≥ 20
- npm (fourni avec Node.js)

## Installation locale
1. Installer les dépendances :
   ```bash
   npm install
   ```
2. Démarrer l'environnement de développement :
   ```bash
   npm run dev
   ```
   L'application est servie sur [http://localhost:5173](http://localhost:5173) par défaut.

## Configuration
Les variables d'environnement Vite utilisent le préfixe `VITE_`. Créez un fichier `.env` à la racine pour pointer vers l'API :

```dotenv
VITE_API_URL=http://localhost:3001/api
```

- `VITE_API_URL` : URL de base utilisée par `src/services/api.js` pour contacter l'API Express (fallback `http://localhost:3000/api`).

## Scripts NPM
- `npm run dev` : démarre Vite en mode développement avec HMR.
- `npm run build` : génère les fichiers statiques dans `dist/`.
- `npm run preview` : sert la version buildée sur `http://localhost:4173`.
- `npm run lint` : exécute ESLint sur le projet.

## Structure du projet
```
src/
├── App.jsx                      # Shell principal avec navigation entre pages
├── components/                  # Composants UI et pages métiers
│   └── pages/                   # Loading, Login, Menu, Recherche client, Formulaire devis...
├── contexts/                    # Contexte d'authentification
├── hooks/                       # Hooks maison (useAuth, useAuthContext)
├── services/                    # Accès API (mock), client, produits, commandes
└── utils/                       # Constantes et helpers
public/                          # Assets statiques servis tels quels
docker/nginx.conf                # Configuration Nginx pour la diffusion SPA
Dockerfile                       # Build multi-stage (Node -> Nginx)
```

## Construit & servir avec Docker
1. Vérifier la présence du fichier `.env` (voir section Configuration).
2. Construire l'image :
   ```bash
   docker build -t a2s-b2bfront .
   ```
3. Lancer le conteneur :
   ```bash
   docker run --rm -p 8082:80 --env-file .env a2s-b2bfront
   ```
   L'interface est accessible sur [http://localhost:8082](http://localhost:8082).

Le `Dockerfile` utilise une étape de build Node (installation via `npm ci`, `npm run build`) puis sert le bundle avec Nginx (`docker/nginx.conf` gère le fallback `index.html` pour la navigation SPA). La configuration `.dockerignore` exclut `node_modules` et `dist/` afin de garantir un build propre.

## Qualité & bonnes pratiques
- **Linting** : `npm run lint` (configuration `eslint.config.js`).
- **Styles** : Tailwind CSS (configuré via `@tailwindcss/vite`).
- **Mock vs API réelle** : remplacez progressivement les appels dans `src/services` par des requêtes Axios sur l'API Express. Conservez l'interface des services pour minimiser l'impact sur les pages.

## Ressources complémentaires
- [Vite Documentation](https://vitejs.dev/)
- [React 19](https://react.dev/)
- [Tailwind CSS](https://tailwindcss.com/)
