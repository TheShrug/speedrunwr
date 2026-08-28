#!/usr/bin/env bash
# `make database restore` — drop and recreate the target database from the
# newest dump in backups/.
#
# This DROPS a database. Correct against a disposable dev volume, catastrophic
# against the shared instance, and the only thing between the two is an
# environment variable — so it refuses any non-local host unless
# ALLOW_REMOTE_RESTORE=1 is typed on purpose.
#
# The client runs inside the app container (that is where psql is), but it
# connects to DB_HOST over the network rather than `compose exec`-ing into the
# db service. That matters: an earlier version guarded on DB_HOST while always
# operating on the compose service, so ALLOW_REMOTE_RESTORE=1 with a remote host
# wiped the LOCAL database and reported it as a remote restore. The check and
# the target must be the same thing or the check is theatre.
set -euo pipefail

COMPOSE="${COMPOSE:-docker compose}"
APP_SERVICE="${APP_SERVICE:-app}"
DB_SERVICE="${DB_SERVICE:-db}"
SRC_DIR="${SRC_DIR:-backups}"

env_get() { grep -E "^$1=" .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"'"'"'\r'; }

DB_HOST="${DB_HOST:-$(env_get DB_HOST)}";         DB_HOST="${DB_HOST:-db}"
DB_NAME="${DB_NAME:-$(env_get DB_DATABASE)}";     DB_NAME="${DB_NAME:-speedrunwr}"
DB_USER="${DB_USER:-$(env_get DB_USERNAME)}";     DB_USER="${DB_USER:-speedrunwr}"
DB_PASS="${DB_PASS:-$(env_get DB_PASSWORD)}";     DB_PASS="${DB_PASS:-secret}"
DB_PORT_="${DB_PORT_:-$(env_get DB_PORT)}";       DB_PORT_="${DB_PORT_:-5432}"

case "$DB_HOST" in
  localhost|127.0.0.1|::1|"$DB_SERVICE") ;;
  *)
    if [ "${ALLOW_REMOTE_RESTORE:-}" != "1" ]; then
      echo "error: DB_HOST is '$DB_HOST', which is not local." >&2
      echo "restore DROPS the database. Against the shared instance that is production gone." >&2
      echo "If you truly mean it: ALLOW_REMOTE_RESTORE=1 make database restore" >&2
      exit 1
    fi
    echo "!! ALLOW_REMOTE_RESTORE=1 — this will DROP '$DB_NAME' on '$DB_HOST'" >&2
    ;;
esac

dump="$(ls -1 "$SRC_DIR"/app-*.dump 2>/dev/null | sort | tail -1 || true)"
[ -n "$dump" ] || { echo "error: no $SRC_DIR/app-*.dump — run 'make database download' first" >&2; exit 1; }

# Say what is about to be destroyed, before destroying it.
echo "==> restoring $(basename "$dump")"
echo "    into database '$DB_NAME' on host '$DB_HOST:$DB_PORT_' as '$DB_USER'"
echo "    this DROPS and recreates that database"
echo

$COMPOSE up -d "$DB_SERVICE" >/dev/null 2>&1 || true

psql_in() {
  $COMPOSE run --rm -T --no-deps -e DB_HOST= -e PGPASSWORD="$DB_PASS" \
    "$APP_SERVICE" psql -h "$DB_HOST" -p "$DB_PORT_" -U "$DB_USER" "$@"
}

psql_in -d postgres \
  -c "DROP DATABASE IF EXISTS \"$DB_NAME\" WITH (FORCE);" \
  -c "CREATE DATABASE \"$DB_NAME\" OWNER \"$DB_USER\";"

$COMPOSE run --rm -T --no-deps -e DB_HOST= -e PGPASSWORD="$DB_PASS" \
  "$APP_SERVICE" pg_restore -h "$DB_HOST" -p "$DB_PORT_" -U "$DB_USER" \
  -d "$DB_NAME" --no-owner --no-privileges < "$dump"

# pg_restore exits 0 on an empty restore, so the exit code proves nothing on its
# own. Count what actually landed.
tables="$(psql_in -d "$DB_NAME" -tAc "select count(*) from information_schema.tables where table_schema='public';" | tr -d ' \r')"
rows="$(psql_in -d "$DB_NAME" -tAc "select coalesce(sum(n_live_tup),0)::bigint from pg_stat_user_tables;" | tr -d ' \r')"
echo
echo "==> restored: $tables tables, ~$rows rows"
[ "${tables:-0}" -gt 0 ] || { echo "error: restore produced no tables" >&2; exit 1; }
echo "    run 'make database migrate' — the dump is up to a day behind this branch"
