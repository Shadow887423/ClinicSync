#!/bin/sh
set -e

echo "==> CLINICSYNC container initializing..."

# Ensure storage directories exist with correct permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate key if empty
if [ -z "$APP_KEY" ]; then
    echo "Notice: APP_KEY is empty. Generating temporary application key..."
    php artisan key:generate --force || true
fi

# Run SQLite database creation if sqlite connection is specified and file missing
if [ "$DB_CONNECTION" = "sqlite" ] && [ ! -f "$DB_DATABASE" ]; then
    echo "Creating SQLite database file at $DB_DATABASE..."
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
    chown www-data:www-data "$DB_DATABASE"
    chmod 664 "$DB_DATABASE"
fi

# Run database migrations if requested (default to true in production)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "==> Running database migrations..."
    php artisan migrate --force --isolated || echo "Migration skipped or failed"
fi

# Run seeders if requested
if [ "${RUN_SEEDER:-false}" = "true" ]; then
    echo "==> Seeding database with demo data..."
    php artisan db:seed --force || echo "Database seeding completed or skipped"
fi

# Optimize caches for production
if [ "$APP_ENV" = "production" ]; then
    echo "==> Caching Laravel configuration and routes..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "==> Starting web services (Nginx & PHP-FPM)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
