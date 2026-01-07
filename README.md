# A2S Dolibarr Local Dev Environment

Ce projet fournit un environnement **Docker Compose** pour exécuter **Dolibarr** en local avec **MySQL** et **phpMyAdmin**.

### Services inclus
- **db** : MySQL (base de données auto importée dans `./mysql_init/`)
- **app** : Dolibarr (serveur Apache intégré, accessible sur [http://localhost:8080](http://localhost:8080))
- **phpmyadmin** : interface phpMyAdmin, accessible sur [http://localhost:8081](http://localhost:8081)

### Pré-requis
- Docker
- Docker Compose

### Importer le projet A2S Dolibarr depuis GitLab

Importer le dépôt Git ainsi que la création du dosser `./app/`

Via un **token** :

```bash
git clone --branch develop --single-branch https://oauth2:glpat-XXX@ns327060.ip-5-135-138.eu/gitlab/a2s/erp.git app
```

Via **ssh** :

```bash
git clone --branch develop --single-branch ssh://git@ns327060.ip-5-135-138.eu:2222/a2s/erp.git app
```

### Arborescence des répertoires

```text
├── app/              # répertoire de travail importé depuis GitLab
│   ├── htdocs/       
│   └── scripts/      
├── conf/             # configuration Dolibarr (montée dans /var/www/htdocs/conf/)
├── documents/        # fichiers générés (factures, devis, uploads…)
├── mysql_init/       # données MySQL auto importé
├── .env              # variables d'environnement
└── docker-compose.yml
```

### Variables d'environnement (.env)
DOLIBARR_VERSION=22.0.1
MYSQL_VERSION=latest
MYSQL_DATABASE=dolibarr
MYSQL_USER=dolibarr
MYSQL_PASSWORD=dolibarr
MYSQL_ROOT_PASSWORD=root
DOLI_ADMIN_LOGIN=admin
DOLI_ADMIN_PASSWORD=admin



### Importer le projet API Express A2S

Le service **api-express** s’exécute à partir d’un dossier externe au dépôt `a2s-local-dev`. Positionnez-vous dans le répertoire parent (par ex. `spec_and_doc_a2s/`) puis clonez le projet à côté :

Via un **token** :

```bash
git clone --branch devlop --single-branch https://oauth2:glpat-XXX@ns327060.ip-5-135-138.eu/gitlab/a2s/api-express.git "Api Express"
```

Via **ssh** :

```bash
git clone --branch devlop --single-branch ssh://git@ns327060.ip-5-135-138.eu:2222/a2s/api_express.git "Api Express"
```

Dans le dossier `Api Express/`, créer le fichier `.env` avec un `BASE_URL` pointant sur le service Dolibarr interne :

```dotenv
BASE_URL=http://app/api/index.php
PORT=3000
```

L’image Docker est construite automatiquement par `docker-compose.yml` grâce au `Dockerfile` fourni dans `Api Express/`.

### Importer le projet A2S BtoC Front

Le front **a2s_btoc** (CRM avec WhatsApp, Facebook, Instagram) doit également être cloné en dehors de `a2s-local-dev` :

Via un **token** :

```bash
git clone --branch main --single-branch https://oauth2:glpat-XXX@ns327060.ip-5-135-138.eu/gitlab/a2s/btoc-front.git a2s_btoc
```

Via **ssh** :

```bash
git clone --branch develop --single-branch ssh://git@ns327060.ip-5-135-138.eu:2222/a2s/btoc-front.git a2s_btoc
```

Configurer `a2s_btoc/.env` avec les variables d'environnement nécessaires :

```dotenv
VITE_SUPABASE_URL=https://your-project.supabase.co
VITE_SUPABASE_ANON_KEY=your-anon-key
```

Le `docker-compose.yml` construit automatiquement l'image du front BtoC (build Vite puis Nginx) à partir de ce dossier.

### Lancer l'écosystème complet

```bash
docker-compose up -d
```

### Accès aux services
- Dolibarr : [http://localhost:8080](http://localhost:8080) (login par défaut : `admin` / `admin`)
- PhpMyAdmin : [http://localhost:8081](http://localhost:8081) (`dolibarr` / `dolibarr` ou `root` / `root`)
- API Express : [http://localhost:3001](http://localhost:3001)
- Front BtoC : [http://localhost:8083](http://localhost:8083)
