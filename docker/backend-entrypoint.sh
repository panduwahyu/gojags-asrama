#!/bin/sh
set -e

cd /var/www/html

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null && [ -z "$APP_KEY" ]; then
    php artisan key:generate --force --ansi
fi

php artisan migrate --seed --force --ansi

exec "$@"
