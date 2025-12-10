#!/bin/sh
set -e

# Fix storage permissions (required for mounted volumes)
echo "Fixing storage permissions..."
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Fix mpdf tmp directory permissions
if [ -d /app/vendor/mpdf/mpdf/tmp ]; then
    echo "Fixing mpdf tmp permissions..."
    chown -R www-data:www-data /app/vendor/mpdf/mpdf/tmp
    chmod -R 775 /app/vendor/mpdf/mpdf/tmp
fi

# Run initial setup if needed (example: creating storage link if missing)
if [ ! -L /app/public/storage ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

# Wait for MySQL to be ready (with proper health check)
echo "Waiting for database..."
max_retries=30
counter=0
until php artisan db:show --json > /dev/null 2>&1; do
    counter=$((counter + 1))
    if [ $counter -ge $max_retries ]; then
        echo "Database connection timeout after ${max_retries} attempts"
        exit 1
    fi
    echo "Database not ready, waiting... (attempt $counter/$max_retries)"
    sleep 2
done
echo "Database is ready!"

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Optimize caches
# echo "Caching configuration..."
# php artisan optimize

# Jalankan Supervisor (yang akan mengontrol App + Queue + Schedule)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
