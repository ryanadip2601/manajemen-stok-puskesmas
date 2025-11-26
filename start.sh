#!/bin/bash

# Run migrations (ignore errors if tables exist)
php artisan migrate --force || true

# Run seeders (ignore errors if data exists)  
php artisan db:seed --force || true

# Start PHP server
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
