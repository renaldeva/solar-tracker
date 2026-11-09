#!/bin/bash
set -e

# Ensure Laravel storage and bootstrap cache directories exist and are writable
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Generate application key if not set in environment or .env
if [ -z "$APP_KEY" ]; then
    if [ ! -f /var/www/html/.env ]; then
        if [ -f /var/www/html/.env.example ]; then
            cp /var/www/html/.env.example /var/www/html/.env
        fi
    fi
    if ! grep -q "APP_KEY=base64" /var/www/html/.env 2>/dev/null; then
        php artisan key:generate --force || true
    fi
fi

exec "$@"
