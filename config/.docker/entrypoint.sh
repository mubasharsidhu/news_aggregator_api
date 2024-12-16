#!/bin/bash

# Wait for the database to be ready
wait-for-it.sh db:3306 --timeout=30 || exit 1

# Determine the environment
if [ "$APP_ENV" = "production" ]; then
    echo "Production environment detected. Installing dependencies without dev packages..."
    composer install --no-dev --optimize-autoloader
else
    echo "Non-production environment detected. Installing all dependencies..."
    composer install
fi

echo "Running migrations for default environment..."
php artisan migrate

echo "Running scribe to generate docs..."
php artisan scribe:generate

# Start supervisord to manage processes
echo "Supervisor configuration not found. Starting Apache..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
