#!/bin/bash

# Wait for the database to be ready
wait-for-it.sh db:3306 --timeout=30 || exit 1

# Run the migrations
php artisan migrate

# Fetch articles from multiple sources
php artisan articles:fetch --source=newsapi
php artisan articles:fetch --source=guardian
php artisan articles:fetch --source=nytimes

# Start supervisord to manage processes
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
