#!/usr/bin/env bash
# `make database restore` — drop and recreate the local database from the newest
# dump in backups/.
#
# This DROPS a database. That is correct against a disposable dev volume and
# catastrophic against the shared instance, and the only thing between the two
# is an environment variable — so it refuses any host that is not local unless
# ALLOW_REMOTE_RESTORE=1 is typed on purpose.
set -euo pipefail

COMPOSE="${COMPOSE:-docker compose}"
DB_SERVICE="${DB_SERVICE:-db}"
DB_NAME="${DB_NAME:-speedrunwr}"
DB_USER="${DB_USER:-speedrunwr}"
SRC_DIR="${SRC_DIR:-backups}"

# Read the host the app is actually configured against, not the one we hope for.
DB_HOST="${DB_HOST:-$(grep -E '^DB_HOST=' .env 2>/dev/null | cut -d= -f2- | tr -d '"' || true)}"
DB_HOST="${DB_HOST:-db}"

case "$DB_HOST" in
  localhost|127.0.0.1|::1|"$DB_SERVICE") ;;
  *)
    if [ "${ALLOW_REMOTE_RESTORE:-}" != "1" ]; then
      echo "error: DB_HOST is '$DB_HOST', which is not local." >&2
      echo "restore DROPS the database. Against the shared instance that is production gone." >&2
      echo "If you truly mean it: ALLOW_REMOTE_RESTORE=1 make database restore" >&2
      exit 1
    fi
    echo "!! ALLOW_REMOTE_RESTORE=1 — restoring against a NON-LOCAL host" >&2
    ;;
esac

dump="$(ls -1 "$SRC_DIR"/app-"$DB_NAME"-*.dump 2>/dev/null | sort | tail -1 || true)"
[ -n "$dump" ] || { echo "error: no $SRC_DIR/app-$DB_NAME-*.dump — run 'make database download' first" >&2; exit 1; }

# Say what is about to be destroyed, before destroying it.
echo "==> restoring $dump"
echo "    into database '$DB_NAME' on host '$DB_HOST' (service '$DB_SERVICE')"
echo "    this DROPS and recreates that database"
echo

$COMPOSE up -d "$DB_SERVICE"
$COMPOSE exec -T "$DB_SERVICE" psql -U "$DB_USER" -d postgres \
  -c "DROP DATABASE IF EXISTS \"$DB_NAME\" WITH (FORCE);" \
  -c "CREATE DATABASE \"$DB_NAME\" OWNER \"$DB_USER\";"
$COMPOSE exec -T "$DB_SERVICE" pg_restore -U "$DB_USER" -d "$DB_NAME" --no-owner --no-privileges < "$dump"

# pg_restore exits 0 on an empty restore, so the exit code proves nothing on its
# own. Count what landed.
rows="$($COMPOSE exec -T "$DB_SERVICE" psql -U "$DB_USER" -d "$DB_NAME" -tAc \
  "select coalesce(sum(n_live_tup),0) from pg_stat_user_tables;" | tr -d ' \r')"
tables="$($COMPOSE exec -T "$DB_SERVICE" psql -U "$DB_USER" -d "$DB_NAME" -tAc \
  "select count(*) from information_schema.tables where table_schema='public';" | tr -d ' \r')"
echo
echo "==> restored: $tables tables, ~$rows rows"
[ "${tables:-0}" -gt 0 ] || { echo "error: restore produced no tables" >&2; exit 1; }
echo "    run 'make database migrate' — the dump is up to a day behind this branch"
