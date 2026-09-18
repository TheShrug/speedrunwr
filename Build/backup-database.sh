#!/usr/bin/env bash
# `make database download` — fetch this app's newest nightly dump out of Cloudflare R2.
#
# NOT a live pg_dump over SSH. The nightly backup already produces a verified
# dump of every app database and puts it in R2 (homelab Conventions/Backups.md),
# so a dev machine needs no production SSH access and no write path to a live
# database.
#
# Credentials come from a fleet-level READ-ONLY R2 token, shared by every repo in the fleet:
#
#   ~/.config/homelab/backups.env      mode 600, overridable by HOMELAB_BACKUP_ENV
#     R2_ACCOUNT_ID=
#     R2_ACCESS_KEY_ID=
#     R2_SECRET_ACCESS_KEY=
#     R2_BUCKET=db-backups
#
# One shared file, deliberately not this repo's .env: a fleet credential copied per project is
# maintained in none of them.
#
# Override via env vars if your setup differs:
#   DB_NAME             (default speedrunwr — the database name in the shared instance, which
#                        is also the object-name key in R2: app-<DB_NAME>-<stamp>.dump)
#   DEST_DIR            (default <repo root>/backups, gitignored — absolute on purpose, so running
#                        it from a subdirectory still lands the dump in the ignored directory)
#   HOMELAB_BACKUP_ENV  (default ~/.config/homelab/backups.env)
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

DB_NAME="${DB_NAME:-speedrunwr}"
DEST_DIR="${DEST_DIR:-$REPO_ROOT/backups}"
CONFIG="${HOMELAB_BACKUP_ENV:-$HOME/.config/homelab/backups.env}"

# Already in the environment wins, and that is the normal path here. The
# credential file lives on the HOST at ~/.config/homelab/backups.env and this
# script runs inside the container, which cannot see it — so the Makefile
# sources the file on the host and passes the values through as bare `-e NAME`
# on `docker compose run`. Bare because `compose run` has no --env-file, and
# because naming a variable without valuing it keeps the credential out of `ps`.
# Reading the file directly is the fallback, for a shell that can see both it
# and rclone.
if [ -n "${R2_ACCESS_KEY_ID:-}" ] && [ -n "${R2_SECRET_ACCESS_KEY:-}" ] && [ -n "${R2_ACCOUNT_ID:-}" ]; then
  :
elif [ ! -r "$CONFIG" ]; then
  cat >&2 <<EOF
error: $CONFIG not readable.

This needs the fleet's READ-ONLY R2 token, which is created by hand:
  Cloudflare dashboard -> R2 -> Manage API tokens -> Create API token
  Permission: Object Read only
  Specify bucket: db-backups
Then write it to $CONFIG (mode 600) and record it in the password manager.

Do NOT copy the credentials from racknerd's /etc/homelab/backup.env. That token
has WRITE access to the backups, and a laptop must not hold a credential that
can delete or overwrite them.
EOF
  exit 1
else
  # shellcheck disable=SC1090
  set -a; . "$CONFIG"; set +a
fi

for v in R2_ACCOUNT_ID R2_ACCESS_KEY_ID R2_SECRET_ACCESS_KEY; do
  [ -n "${!v:-}" ] || { echo "error: $v is not set in $CONFIG" >&2; exit 1; }
done
R2_BUCKET="${R2_BUCKET:-db-backups}"

command -v rclone >/dev/null || {
  echo "error: rclone is not installed. It is baked into the dev image" >&2
  echo "       (Dockerfile, RCLONE_VERSION); run \`make database download\`." >&2
  exit 1
}

# rclone speaks S3 to R2 with no config file — everything comes from the environment, so no
# credential is written to disk outside the mode-600 file above.
export RCLONE_CONFIG_R2_TYPE=s3
export RCLONE_CONFIG_R2_PROVIDER=Cloudflare
export RCLONE_CONFIG_R2_ACCESS_KEY_ID="$R2_ACCESS_KEY_ID"
export RCLONE_CONFIG_R2_SECRET_ACCESS_KEY="$R2_SECRET_ACCESS_KEY"
export RCLONE_CONFIG_R2_ENDPOINT="https://${R2_ACCOUNT_ID}.r2.cloudflarestorage.com"
# Carried for parity with the upload path, not because this script needs them: both are inert here.
# This script only reads, and rclone's bucket check runs ahead of a WRITE — verified 2026-08-30 by
# running without them against the real bucket, which downloads cleanly. Where they matter is
# homelab's scripts/host/backup-to-r2.sh, which uploads. Keep them so the three copies of this file
# stay one script.
#
# The per-remote form is the one that takes effect for a remote defined through RCLONE_CONFIG_* —
# the generic --s3-no-check-bucket does not. That part is true.
export RCLONE_CONFIG_R2_REGION=auto
export RCLONE_CONFIG_R2_NO_CHECK_BUCKET=true

# The prefix is chosen from the date at WRITE time: the 1st of a month goes to monthly/, Sunday to
# weekly/, everything else to daily/. So on a Monday the newest object is under weekly/, and looking
# only in daily/ silently hands you yesterday's data. Search all three.
#
# Sorted by the timestamp IN THE NAME, not by mtime. The name carries the UTC instant the dump was
# taken; mtime is when the upload finished. They usually agree, which is what would make trusting
# the wrong one survive review.
echo "==> searching daily/ weekly/ monthly/ for app-${DB_NAME}-*.dump"
found=""
for prefix in daily weekly monthly; do
  listing="$(rclone lsf "R2:${R2_BUCKET}/${prefix}/" 2>/dev/null || true)"
  [ -n "$listing" ] || continue
  match="$(printf '%s\n' "$listing" | grep -E "^app-${DB_NAME}-[0-9]{8}-[0-9]{6}\.dump$" || true)"
  [ -n "$match" ] || continue
  while IFS= read -r name; do
    found="${found}${name} ${prefix}"$'\n'
  done <<< "$match"
done

# No match is a HARD FAILURE that prints what it did find. "You asked for a database that is not
# backed up" and "the bucket is empty" must not both look like an empty directory — the same
# principle as the backup writer refusing to guess between two Postgres containers.
if [ -z "$found" ]; then
  echo "error: no app-${DB_NAME}-*.dump found in any prefix." >&2
  echo "What IS in the bucket:" >&2
  for prefix in daily weekly monthly; do
    rclone lsf "R2:${R2_BUCKET}/${prefix}/" 2>/dev/null \
      | grep -E '^app-.*\.dump$' | sed "s|^|  ${prefix}/|" >&2 || true
  done
  exit 1
fi

newest="$(printf '%s' "$found" | sort -k1,1 | tail -1)"
object="$(echo "$newest" | awk '{print $1}')"
prefix="$(echo "$newest" | awk '{print $2}')"
remote="R2:${R2_BUCKET}/${prefix}/${object}"

mkdir -p "$DEST_DIR"
target="${DEST_DIR}/${object}"

# Download to .partial and rename only on success. A connection that dies mid-stream leaves a
# truncated dump that is non-empty and therefore passes every "is it there?" check — the worst
# possible failure for a backup, because it is indistinguishable from a good one until restore day.
echo "==> $remote"
rclone copyto "$remote" "${target}.partial" --progress

remote_size="$(rclone size "$remote" --json 2>/dev/null | grep -o '"bytes":[0-9]*' | cut -d: -f2)"
local_size="$(wc -c < "${target}.partial" | tr -d ' ')"
if [ -n "$remote_size" ] && [ "$remote_size" != "$local_size" ]; then
  rm -f "${target}.partial"
  echo "error: size mismatch — remote $remote_size, local $local_size" >&2
  exit 1
fi

# pg_dump -Fc archives start with the magic string PGDMP. Same check the backup writer makes before
# storing anything.
if [ "$(head -c 5 "${target}.partial")" != "PGDMP" ]; then
  rm -f "${target}.partial"
  echo "error: ${object} has no PGDMP header — not a pg_dump -Fc archive" >&2
  exit 1
fi

mv "${target}.partial" "$target"
echo "==> $target ($local_size bytes, verified)"
echo
echo "Restore it into the local database with: make database restore"
echo
echo "This is production data: real users, real email addresses, real password"
echo "hashes. backups/ is gitignored; delete it when you are done with it."
