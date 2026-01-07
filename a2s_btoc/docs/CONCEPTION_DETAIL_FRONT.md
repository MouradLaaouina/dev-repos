# Conception Détaillée du Frontend B2C (a2s_btoc)

Ce document fournit une description technique complète de l'architecture, du design et du fonctionnement du frontend B2C du système A2S Dolibarr.

## 1. Architecture Technique

Le frontend est une Application à Page Unique (SPA) moderne construite avec les technologies suivantes :

- **Framework** : React 18
- **Langage** : TypeScript (mode strict)
- **Outil de Build** : Vite
- **Stylisation** : Tailwind CSS
- **Icônes** : Lucide React
- **Gestion d'État** : Zustand
- **Routage** : React Router 6
- **Appels API** : Axios
- **Backend Unique** : Dolibarr ERP (via API Express Proxy)

## 2. Structure du Projet

```text
a2s_btoc/
├── docs/               # Documentation technique
├── public/             # Assets statiques
├── scripts/            # Scripts utilitaires
├── server/             # Serveur Node.js pour webhooks
├── src/
│   ├── components/     # Composants React organisés par domaine
│   ├── config/         # Configuration de l'application
│   ├── data/           # Données statiques et constantes
│   ├── services/       # Services de communication API (Dolibarr)
│   ├── store/          # Magasins d'état Zustand
│   ├── types/          # Définitions des types TypeScript
│   ├── utils/          # Fonctions utilitaires
│   ├── App.tsx         # Point d'entrée des routes
│   └── main.tsx        # Point d'entrée de l'application
```

## 3. Système d'Authentification

L'authentification est gérée par Dolibarr via une API Express.

- **Mécanisme** : Basé sur un jeton (Stocké dans le localStorage sous la clé `dolibarr_token`).
- **Service** : `authService.ts` gère les appels de connexion (login), déconnexion (logout) et récupération de l'utilisateur actuel.
- **Store** : `authStore.ts` maintient l'état global de l'utilisateur.
- **Rôles** : `admin`, `agent`, `superviseur`, `confirmation`, `dispatching`, `callcenter`.
- **Équipes** : Basées sur le `code_agence` de Dolibarr.

## 4. Gestion de l'État (Stores Zustand)

L'application utilise plusieurs magasins Zustand pour une gestion granulaire :

- **authStore** : Session utilisateur et permissions.
- **contactStore** : Gestion des tiers (Third Parties) Dolibarr.
- **orderStore** : Cycle de vie des commandes clients Dolibarr.
- **dashboardStore** : Agrégation des données pour les graphiques.
- **callCenterStore** : Gestion des leads et interactions via l'Agenda Dolibarr.
- **messageStore** : Gestion des messages sociaux (via Agenda Dolibarr).
- **productStore** : Liste des produits et marques depuis Dolibarr.

## 5. Services et Communication API

- **apiClient.ts** : Instance Axios configurée avec des intercepteurs pour injecter automatiquement le jeton `DOLAPIKEY`.
- **contactService.ts**, **orderService.ts**, **agendaService.ts** : Services spécialisés mappant les objets Dolibarr aux types internes de l'application.

## 6. Routage et Navigation

Le routage est géré par `react-router-dom`. Le layout principal (`Layout.tsx`) inclut une barre latérale de navigation dont les éléments s'affichent dynamiquement selon les droits de l'utilisateur.

## 7. Fonctionnalités Clés

### CRM & Leads
Gestion complète du cycle de vie d'un prospect via les `Tiers` Dolibarr. Les métadonnées spécifiques sont stockées dans des `extrafields`.

### Tableau de Bord & Analytics
Visualisations riches utilisant `chart.js` basées sur les données extraites en temps réel de Dolibarr.

### Centre d'Appel
Module spécialisé utilisant l'Agenda de Dolibarr pour tracer les appels, les rappels et la satisfaction client.

## 8. Intégrations Externes

- **Dolibarr** : Unique source de vérité pour les données et l'ERP.
- **API Express** : Couche intermédiaire facilitant les appels REST à Dolibarr.
- **Meta Graph API / WhatsApp API** : Sources de webhooks pour la capture de leads.

## 9. Déploiement et CI/CD

- **Hébergement Frontend** : Netlify.
- **Conteneurisation** : Dockerfile disponible.
- **Variables d'Environnement Clés** :
  - `VITE_API_BASE_URL` : URL de l'API Express Dolibarr.
