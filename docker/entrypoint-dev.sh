#!/bin/sh
set -e

if [ -n "$DB_HOST" ]; then
    until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" -q; do
        echo "Waiting for postgres at $DB_HOST:${DB_PORT:-5432}..."
        sleep 1
    done
fi

if [ -f composer.json ] && [ ! -d vendor ]; then
    composer install --no-interaction
fi

if [ "$#" -eq 0 ]; then
    php artisan migrate --force
    php-fpm -D
    exec nginx -g "daemon off;"
fi

exec "$@"
