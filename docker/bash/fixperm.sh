#!/bin/bash

# Set owner
sudo chown -R www:www . 2>/dev/null

# Default perms
sudo find . -type d -exec chmod 755 {} \; 2>/dev/null
sudo find . -type f -exec chmod 644 {} \; 2>/dev/null

# Writable directories (Laravel + public)
sudo chmod -R 775 public storage bootstrap/cache vendor 2>/dev/null

# Sensitive files (abaikan error jika tidak ada)
sudo chmod 600 .env .env.local .env.production artisan .well-known .git 2>/dev/null

sudo chmod 600 ./docker/bash/fixperm.sh 2>/dev/null