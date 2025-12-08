FROM node:20-alpine AS build

WORKDIR /usr/src/app

# Installation des dépendances
COPY package*.json ./
RUN npm ci

# Copie du code source et build de l'application
COPY . .
RUN npm run build

# Étape finale : serveur Nginx pour distribuer les assets statiques
FROM nginx:1.27-alpine

# Configuration spécifique pour les SPA (fallback vers index.html)
RUN rm /etc/nginx/conf.d/default.conf
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Copie des fichiers générés
COPY --from=build /usr/src/app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
