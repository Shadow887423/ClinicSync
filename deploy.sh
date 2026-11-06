#!/usr/bin/env bash
set -e

echo "==> Deploying CLINICSYNC..."

# Maintenance mode
php artisan down || true

# Pull latest code
# git pull origin main

# Install composer dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Run database migrations
php artisan migrate --force

# Clear and rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Exit maintenance mode
php artisan up

echo "==> Deployment completed successfully!"
