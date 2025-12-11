#!/bin/bash

# Set owner
sudo chown -R www:www .

# Default perms
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;

# Writable directories (Laravel + public)
sudo chmod -R 775 public storage bootstrap/cache vendor

# Sensitive files (abaikan error jika tidak ada)
sudo chmod 600 .env .env-local .env-production artisan .git 2>/dev/null

sudo chmod 600 ./docker/bash/fixperm.sh