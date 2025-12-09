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

# Tunggu MySQL ready (simple way)
echo "Waiting for database..."
sleep 10

# Run migrations (careful in production, maybe flag controlled)
echo "Running migrations..."
php artisan migrate --force

# Optimize caches
echo "Caching configuration..."
php artisan optimize:clear
php artisan filament:optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Jalankan Supervisor (yang akan mengontrol App + Queue + Schedule)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
