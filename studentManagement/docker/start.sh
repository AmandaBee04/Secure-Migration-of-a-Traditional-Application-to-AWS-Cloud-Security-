#!/bin/sh
set -e

# Override .env with actual environment variables for ECS deployment
export APP_ENV="${APP_ENV:-production}"
export APP_KEY="${APP_KEY:-}"
export DB_HOST="${DB_HOST:-127.0.0.1}"
export DB_DATABASE="${DB_DATABASE:-laravel}"
export DB_USERNAME="${DB_USERNAME:-root}"
export DB_PASSWORD="${DB_PASSWORD:-}"

# Generate app key if not already set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --no-interaction --force
fi

# Wait for MySQL to be ready, then migrate (errors shown for debugging)
echo "Waiting for database... DB_HOST=${DB_HOST} DB_DATABASE=${DB_DATABASE} DB_USERNAME=${DB_USERNAME}"
until php artisan migrate --no-interaction --force; do
    echo "Database not ready yet, retrying in 3s..."
    sleep 3
done
echo "Migrations complete."

# Seed the database (insertOrIgnore — safe to run on every start)
echo "Seeding initial data..."
php artisan db:seed --no-interaction --force 2>&1 || echo "Seed failed — continuing without seed data."
echo "Seed done."

# Clear any stale local-dev cache files that may be baked into the image
php artisan config:clear 2>/dev/null || true
php artisan route:clear  2>/dev/null || true
php artisan cache:clear  2>/dev/null || true

# Rebuild config cache with actual ECS env vars
php artisan config:cache || echo "Config cache skipped."
# NOTE: route:cache intentionally skipped — /api/health closure breaks serialization

# Ensure PHP-FPM (www-data) can read/write all generated cache files
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
exec nginx -g "daemon off;"
