FROM node:20-alpine AS base

# Répertoire de travail dans le conteneur
WORKDIR /usr/src/app

# Installation des dépendances en mode production
COPY package*.json ./
RUN npm ci --omit=dev

# Copie du code source
COPY src ./src

# Variables d'environnement par défaut (modifiable via docker run)
ENV NODE_ENV=production \
    PORT=3000

# Port exposé par l'application Express
EXPOSE 3000

# Lancement de l'application
CMD ["node", "src/app.js"]
