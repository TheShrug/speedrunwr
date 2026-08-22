#!/bin/sh
set -e

if [ -n "$DB_HOST" ]; then
    until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" -q; do
        echo "Waiting for postgres at $DB_HOST:${DB_PORT:-5432}..."
        sleep 1
    done
fi

php artisan migrate --force

# Baked at build time these would freeze in whatever env is present during
# `docker build` (none); doing it here means they reflect the real runtime
# environment Coolify injects.
php artisan config:cache
php artisan route:cache
php artisan view:cache

php-fpm -D
exec nginx -g "daemon off;"
