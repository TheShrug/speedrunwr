#!/usr/bin/env bash
# Devcontainer post-create. Idempotent: safe to re-run after a rebuild.
set -euo pipefail

if [ ! -f .env ]; then
  echo "==> creating .env from .env.example"
  cp .env.example .env
  sed -i 's|^DB_HOST=.*|DB_HOST=db|; s|^DB_PASSWORD=.*|DB_PASSWORD=secret|' .env
fi

echo "==> composer install"
composer install --no-interaction

grep -qE '^APP_KEY=.+' .env || { echo "==> php artisan key:generate"; php artisan key:generate; }

echo
echo "Ready. Run 'make' to see the targets."
