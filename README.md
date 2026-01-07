# A2S Dolibarr Local Dev Environment

Ce projet fournit un environnement **Docker Compose** pour exécuter **Dolibarr** en local avec **MySQL**, **phpMyAdmin**, **API Express** et le **Frontend BToC**.

### Services inclus
- **db** : MySQL (base de données auto importée depuis `./mysql_init/`)
- **app** : Dolibarr (serveur Apache intégré, port 8080)
- **phpmyadmin** : interface phpMyAdmin (port 8081)
- **api-express** : Proxy API Node (port 3000)
- **btoc-front** : Frontend React CRM (port 8083)

### Pré-requis
- Docker
- Docker Compose

### Démarrage Rapide

Pour lancer tout l'écosystème avec une seule commande :

```bash
./docker-start.sh
```

Ce script va :
1. Créer un fichier `.env` par défaut s'il n'existe pas.
2. Construire et lancer tous les services Docker.
3. Afficher les URLs d'accès.

### Modules Dolibarr Personnalisés (BToC)

Deux modules ont été créés pour isoler les données BToC des données BToB :
- **BTOC Access Management** (`btocaccess`) : Gère les droits d'accès spécifiques pour le frontend BToC.
- **BTOC Statistics** (`btocstats`) : Enregistre et analyse les statistiques d'utilisation du frontend BToC (pages vues, connexions, etc.) dans une table dédiée (`llx_btoc_stats`).

### Arborescence des répertoires

```text
├── app/              # Dolibarr (htdocs, scripts)
├── Api Express/      # Proxy API Node
├── a2s_btoc/         # Frontend React BToC
├── conf/             # Configuration Dolibarr
├── documents/        # Fichiers Dolibarr (uploads, etc.)
├── mysql_init/       # Initialisation MySQL
├── .env              # Variables d'environnement globales
└── docker-compose.yml
```

### Accès aux services
- Dolibarr : [http://localhost:8080](http://localhost:8080) (admin/admin)
- BToC Front : [http://localhost:8083](http://localhost:8083)
- API Express : [http://localhost:3000](http://localhost:3000)
- PhpMyAdmin : [http://localhost:8081](http://localhost:8081)
