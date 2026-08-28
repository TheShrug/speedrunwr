#!/usr/bin/env bash
# `make database migrate` — bring the restored schema forward to this branch.
#
# Runs AFTER restore, not instead of it: the newest nightly dump is up to a day
# behind whatever migrations are on the branch you are working on.
set -euo pipefail

COMPOSE="${COMPOSE:-docker compose}"
APP_SERVICE="${APP_SERVICE:-app}"

$COMPOSE up -d "$APP_SERVICE"
$COMPOSE exec -T "$APP_SERVICE" php artisan migrate --force
