#!/bin/bash

# Configuration des permissions pour les répertoires de Dolibarr
chown -R www-data:www-data /var/www/html
chown -R www-data:www-data /var/www/documents
chown -R www-data:www-data /var/www/htdocs/conf

# Attendre que la base de données soit prête
sleep 5

echo "Startup script finished."
