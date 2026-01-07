#!/bin/bash

# Check if .env exists
if [ ! -f .env ]; then
    echo "Creating .env from template..."
    cat <<EOT > .env
DOLIBARR_VERSION=22.0.1
MYSQL_VERSION=latest
MYSQL_DATABASE=dolibarr
MYSQL_USER=dolibarr
MYSQL_PASSWORD=dolibarr
MYSQL_ROOT_PASSWORD=root
DOLI_ADMIN_LOGIN=admin
DOLI_ADMIN_PASSWORD=admin
VITE_API_BASE_URL=http://localhost:3000/api
EOT
fi

# Build and start services
echo "Starting services..."
docker compose up --build -d

echo "---------------------------------------------------"
echo "Project started successfully!"
echo "Dolibarr: http://localhost:8080"
echo "API Express: http://localhost:3000"
echo "BToC Front: http://localhost:8083"
echo "phpMyAdmin: http://localhost:8081"
echo "---------------------------------------------------"
echo "Note: On first run, Dolibarr might take a minute to initialize."
