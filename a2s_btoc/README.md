# a2s_btoc - CRM & Call Center Frontend

Interface frontend B2C pour le système A2S Dolibarr, permettant la gestion manuelle des leads, des commandes et des interactions clients (Social, Call Center).

## 🚀 Technologies

- **React 18** avec **TypeScript**
- **Vite** pour le build et le développement
- **Tailwind CSS** pour le stylage
- **Zustand** pour la gestion d'état global
- **Dolibarr API** (via Express) pour l'authentification et l'ERP

## 📖 Documentation

Pour une vue d'ensemble détaillée de la conception du projet, veuillez consulter les documents suivants :

- [Conception Détaillée du Frontend](./docs/CONCEPTION_DETAIL_FRONT.md)
- [Guide de l'Authentification Dolibarr](./docs/AUTH_REFACTOR.md)
- [Guide du Centre d'Appel](./docs/CALL_CENTER_GUIDE.md)
- [Installation Utilisateur](./docs/USER_SETUP.md)

## 🛠️ Installation

1. Installez les dépendances :
   ```bash
   npm install
   ```

2. Configurez les variables d'environnement (copiez `.env.example` vers `.env`) :
   ```bash
   cp .env.example .env
   ```

3. Lancez le serveur de développement :
   ```bash
   npm run dev
   ```

## 🏗️ Structure du Code

- `src/components/`: Composants UI organisés par fonctionnalité.
- `src/store/`: Magasins d'état Zustand (auth, contacts, commandes, etc.).
- `src/services/`: Services de communication API (Axios, Auth).
- `src/types/`: Définitions TypeScript globales.
- `server/`: Serveur Node.js pour la gestion des webhooks.

## 📦 Docker

L'application peut être conteneurisée à l'aide du Dockerfile inclus :

```bash
docker build -t a2s-btoc-front .
docker run -p 8083:80 a2s-btoc-front
```

## 📄 Licence

Propriété de A2S.
