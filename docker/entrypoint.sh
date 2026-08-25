#!/bin/sh
set -e

# `docker run <img> <cmd>` runs <cmd> instead of serving, like any normal image.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

if [ -n "$DB_HOST" ]; then
    until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" -d "$DB_DATABASE" -q; do
        echo "Waiting for postgres at $DB_HOST:${DB_PORT:-5432}..."
        sleep 1
    done
fi

# Baked at build time these would freeze in whatever env is present during
# `docker build` (none); doing it here means they reflect the real runtime
# environment Coolify injects. config:cache goes first so everything after
# it reads one consistent config.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# migrate goes last so a schema failure kills the container before it serves.
php artisan migrate --force

php-fpm -D
exec nginx -g "daemon off;"
