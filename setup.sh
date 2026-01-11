#!/bin/bash

# For Execute
# sed -i 's/\r$//' setup.sh && bash setup.sh

echo "[setup.sh] Start to execute..."

echo "--> Composer install..."
COMPOSER_PROCESS_TIMEOUT=0 composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "--> Artisan clear cache..."
php artisan optimize:clear

echo "--> Artisan storage link..."
rm -rf ./public/storage
php artisan storage:link

echo "--> Artisan migrate..."
php artisan migrate --force

echo "--> Artisan optimize cache..."
php artisan filament:optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "--> Setting permissions..."
sudo chown -R www:www . 2>/dev/null || true
sudo find . \( -path ./node_modules -o -path ./vendor \) -prune -o -type d -exec chmod 755 {} \;
sudo find . \( -path ./node_modules -o -path ./vendor \) -prune -o -type f -exec chmod 644 {} \;

echo "--> Setting writable permissions..."
sudo chmod -R 775 public storage bootstrap/cache vendor 2>/dev/null

echo "--> Securing credentials files..."
sudo chmod 600 .env .env.local .env.production .well-known .git artisan Makefile setup.sh 2>/dev/null

echo "--> Supervisor setup..."
cp ./deploy/supervisor/queue.conf /etc/supervisor/conf.d/personal_v4_novaardiansyah_id-queue.conf
cp ./deploy/supervisor/schedule.conf /etc/supervisor/conf.d/personal_v4_novaardiansyah_id-schedule.conf

echo "--> Supervisor restart..."
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart personal_v4_novaardiansyah_id-queue
sudo supervisorctl restart personal_v4_novaardiansyah_id-schedule

echo "[setup.sh] Script has been executed successfully..."
