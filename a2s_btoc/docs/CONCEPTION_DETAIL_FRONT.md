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
- **Backend de Persistance** : Supabase (PostgreSQL, Realtime, Storage)
- **Backend d'Authentification/ERP** : API Express (Proxy Dolibarr)

## 2. Structure du Projet

```text
a2s_btoc/
├── docs/               # Documentation technique
├── public/             # Assets statiques
├── scripts/            # Scripts utilitaires (backfill, etc.)
├── server/             # Serveur Node.js pour webhooks et intégrations
├── src/
│   ├── components/     # Composants React organisés par domaine
│   ├── config/         # Configuration de l'application
│   ├── data/           # Données statiques et constantes
│   ├── lib/            # Initialisation des bibliothèques (Supabase client)
│   ├── services/       # Services de communication API
│   ├── store/          # Magasins d'état Zustand
│   ├── types/          # Définitions des types TypeScript
│   ├── utils/          # Fonctions utilitaires
│   ├── App.tsx         # Point d'entrée des routes
│   └── main.tsx        # Point d'entrée de l'application
└── supabase/           # Migrations et fonctions Edge Supabase
```

## 3. Système d'Authentification

L'authentification a été migrée de Supabase vers Dolibarr via une API Express.

- **Mécanisme** : Basé sur un jeton (Stocké dans le localStorage sous la clé `dolibarr_token`).
- **Service** : `authService.ts` gère les appels de connexion (login), déconnexion (logout) et récupération de l'utilisateur actuel.
- **Store** : `authStore.ts` maintient l'état global de l'utilisateur et l'état de chargement.
- **Rôles** : `admin`, `agent`, `superviseur`, `confirmation`, `dispatching`, `callcenter`.
- **Équipes** : Basées sur le `code_agence` de Dolibarr :
  - `000001` : Réseaux sociaux
  - `000002` : Centre d'appel
  - `000003` : WhatsApp

## 4. Gestion de l'État (Stores Zustand)

L'application utilise plusieurs magasins Zustand pour une gestion granulaire :

- **authStore** : Session utilisateur et permissions.
- **contactStore** : Gestion des leads et contacts CRM.
- **orderStore** : Cycle de vie des commandes (création, confirmation, suivi).
- **dashboardStore** : Agrégation des données pour les graphiques et statistiques.
- **callCenterStore** : Gestion des leads spécifiques au centre d'appel et journaux d'appels.
- **messageStore** : Gestion des messages provenant des réseaux sociaux.
- **productStore** : Liste des produits et marques.

## 5. Services et Communication API

- **apiClient.ts** : Instance Axios configurée avec des intercepteurs pour injecter automatiquement le jeton Dolibarr et gérer les erreurs d'authentification (401).
- **authService.ts** : Interface avec les points de terminaison `/api/auth/login` et `/api/auth/me`.
- **Supabase** : Utilisé directement via le client `supabase.ts` pour toutes les opérations CRUD sur les contacts, commandes, journaux d'appels, etc.

## 6. Routage et Navigation

Le routage est géré par `react-router-dom`. Le layout principal (`Layout.tsx`) inclut une barre latérale de navigation dont les éléments s'affichent dynamiquement selon les droits de l'utilisateur.

### Principales Routes :
- `/login` : Connexion utilisateur.
- `/dashboard/stats` : Vue d'ensemble statistique.
- `/dashboard/contacts/new|edit` : Formulaire de gestion des contacts.
- `/dashboard/clients` : Liste globale des clients/leads.
- `/dashboard/orders` : Gestion des commandes.
- `/dashboard/whatsapp` : Interface dédiée aux leads WhatsApp.
- `/dashboard/agent-dashboard` : Tableau de bord spécifique aux agents de vente.
- `/dashboard/call-center` : Interface dédiée au centre d'appel.

## 7. Fonctionnalités Clés

### CRM & Leads
Gestion complète du cycle de vie d'un prospect, de la source (Facebook, Ads, etc.) à la conversion en commande. Supporte le filtrage par agent, équipe, plateforme et statut.

### Flux de Données et Filtrage
L'application utilise une logique de filtrage basée sur le rôle et l'équipe (`code_agence`) de l'utilisateur :
- **Agents** : Voient généralement uniquement leurs propres données ou les données de leur équipe.
- **Administrateurs/Superviseurs** : Ont une vue globale sur toutes les agences et tous les agents.
- **Temps Réel** : Les abonnements Supabase Realtime sont utilisés pour mettre à jour les listes de contacts et de messages instantanément sans rechargement de page.

### Tableau de Bord & Analytics
Visualisations riches utilisant `chart.js` pour suivre :
- Performance par agent (taux de conversion, panier moyen).
- Volume par plateforme et par source (influenceurs, publicités).
- Évolution temporelle des commandes.
Le `dashboardStore` gère la récupération de données complexes via des requêtes Supabase optimisées et utilise un système de mise en cache pour améliorer les performances.

### Centre d'Appel
Module spécialisé pour l'équipe sortante :
- Intégration MicroSIP via les protocoles `sip:`.
- Suivi des tentatives d'appels et des niveaux de satisfaction.
- Planification des rappels.

### Intégration WhatsApp & Social
Récupération automatique des messages via des webhooks (gérés par le serveur Node.js et les fonctions Edge Supabase) et affichage dans le CRM pour une conversion rapide.

## 8. Intégrations Externes

- **Dolibarr** : Source de vérité pour les utilisateurs et destination finale des commandes confirmées.
- **Supabase** : Base de données opérationnelle en temps réel pour le suivi CRM.
- **Meta Graph API** : Pour la réception des webhooks Facebook et Instagram.
- **WhatsApp Business API** : Pour la communication client directe.
- **ATLAS** : Intégration prévue via les codes clients pour la synchronisation avec le système de gestion ATLAS.

## 9. Déploiement et CI/CD

- **Hébergement Frontend** : Netlify.
- **Conteneurisation** : Dockerfile disponible pour un déploiement sur infrastructure propre.
- **Variables d'Environnement Clés** :
  - `VITE_SUPABASE_URL` : URL de l'instance Supabase.
  - `VITE_SUPABASE_ANON_KEY` : Clé anonyme Supabase.
  - `VITE_API_BASE_URL` : URL de l'API Express Dolibarr.
