#!/bin/sh
set -e

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
php artisan optimize

# Jalankan Supervisor (yang akan mengontrol App + Queue + Schedule)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
