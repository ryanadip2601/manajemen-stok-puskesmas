#!/bin/bash

echo "=== Starting Laravel Application ==="

# Set default DB connection to pgsql if DATABASE_URL exists
if [ ! -z "$DATABASE_URL" ]; then
    export DB_CONNECTION=pgsql
    echo "DATABASE_URL detected, using PostgreSQL"
fi

# Clear config cache
php artisan config:clear

# Run migrations
echo "Running migrations..."
php artisan migrate --force || true

# Run seeders
echo "Running seeders..."
php artisan db:seed --force || true

# Start PHP server
echo "Starting server on port ${PORT:-8080}..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
