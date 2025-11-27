#!/bin/bash

echo "=== Starting SIMBAR Application ==="

# Clear caches
echo "Clearing caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Check if DATABASE_URL is set
if [ ! -z "$DATABASE_URL" ]; then
    echo "DATABASE_URL detected, using PostgreSQL"
    export DB_CONNECTION=pgsql
    
    # Wait for database to be ready
    echo "Waiting for database..."
    sleep 3
    
    # Run migrations
    echo "Running migrations..."
    php artisan migrate --force 2>&1 || echo "Migration warning (may already exist)"
    
    # Run seeders
    echo "Running seeders..."
    php artisan db:seed --force 2>&1 || echo "Seeder warning (may already exist)"
else
    echo "No DATABASE_URL, using SQLite"
    export DB_CONNECTION=sqlite
    
    # Create SQLite database if not exists
    touch database/database.sqlite 2>/dev/null || true
    
    # Run migrations for SQLite
    php artisan migrate --force 2>&1 || true
    php artisan db:seed --force 2>&1 || true
fi

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force 2>/dev/null || true
fi

# Start PHP server
echo "Starting server on port ${PORT:-8080}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
