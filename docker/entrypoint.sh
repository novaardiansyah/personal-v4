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

# Start Supervisor (which starts FrankenPHP and Queue workers)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
