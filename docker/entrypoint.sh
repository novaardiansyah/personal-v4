#!/bin/sh
set -e

# Fix storage permissions (required for mounted volumes)
echo "Fixing storage permissions..."
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Create required directories if they don't exist
mkdir -p /app/storage/logs
mkdir -p /app/storage/framework/{sessions,views,cache}
chown -R www-data:www-data /app/storage
chmod -R 775 /app/storage

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
