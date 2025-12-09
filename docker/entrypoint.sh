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

# Run migrations (careful in production, maybe flag controlled)
echo "Running migrations..."
php artisan migrate --force

# Optimize caches
echo "Caching configuration..."
# php artisan optimize

# Jalankan Supervisor (yang akan mengontrol App + Queue + Schedule)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
