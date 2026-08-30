#!/usr/bin/env bash
# `make database download` — fetch this app's newest dump from Cloudflare R2.
#
# NOT a live pg_dump over SSH. The nightly backup already produces a verified
# dump of every app database and puts it in R2 (homelab Conventions/Backups.md),
# so a dev machine needs no production SSH access and no write path to a live
# database.
#
# Credentials come from a fleet-level READ-ONLY R2 token, shared by every repo:
#
#   ~/.config/homelab/backups.env      mode 600, overridable by HOMELAB_BACKUP_ENV
#     R2_ACCOUNT_ID=
#     R2_ACCESS_KEY_ID=
#     R2_SECRET_ACCESS_KEY=
#     R2_BUCKET=db-backups
#
# One shared file, deliberately not this repo's .env: a fleet credential copied
# per project is maintained in none of them.
set -euo pipefail

DB_NAME="${DB_NAME:-speedrunwr}"
DEST_DIR="${DEST_DIR:-backups}"
CONFIG="${HOMELAB_BACKUP_ENV:-$HOME/.config/homelab/backups.env}"

# Already in the environment wins. `make database download` runs this INSIDE the
# dev container and passes the credential in with compose's --env-file, because
# the file lives on the host at ~/.config/homelab and the container cannot see
# it. Reading the file directly still works when rclone is on your PATH.
if [ -n "${R2_ACCESS_KEY_ID:-}" ] && [ -n "${R2_SECRET_ACCESS_KEY:-}" ] && [ -n "${R2_ACCOUNT_ID:-}" ]; then
  :
elif [ ! -r "$CONFIG" ]; then
  cat >&2 <<EOF
error: $CONFIG not readable.

This needs the fleet's READ-ONLY R2 token, which is created by hand:
  Cloudflare dashboard -> R2 -> Manage API tokens -> Create
  Object Read only, scoped to the db-backups bucket alone.
Then write it to $CONFIG (mode 600) and record it in the password manager.

Do NOT copy the credentials from racknerd's /etc/homelab/backup.env — that
token has write access to the backups.
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

command -v rclone >/dev/null || { echo "error: rclone is not installed" >&2; exit 1; }

export RCLONE_CONFIG_R2_TYPE=s3
export RCLONE_CONFIG_R2_PROVIDER=Cloudflare
export RCLONE_CONFIG_R2_ACCESS_KEY_ID="$R2_ACCESS_KEY_ID"
export RCLONE_CONFIG_R2_SECRET_ACCESS_KEY="$R2_SECRET_ACCESS_KEY"
export RCLONE_CONFIG_R2_ENDPOINT="https://${R2_ACCOUNT_ID}.r2.cloudflarestorage.com"
# R2 ignores regions but the S3 client insists on one.
export RCLONE_CONFIG_R2_REGION=auto
# The token is scoped to this bucket alone and therefore cannot create
# buckets. Without this, rclone probes with a CreateBucket that R2 answers 403
# before falling back. The per-remote form is the one that takes effect for a
# remote defined through RCLONE_CONFIG_* — the generic --s3-no-check-bucket
# does not.
export RCLONE_CONFIG_R2_NO_CHECK_BUCKET=true

# The prefix is chosen from the date at WRITE time: the 1st of a month goes to
# monthly/, Sunday to weekly/, everything else to daily/. So on a Monday the
# newest object is under weekly/, and looking only in daily/ silently hands you
# yesterday's data. Search all three.
#
# Sorted by the timestamp IN THE NAME, not by mtime. The name carries the UTC
# instant the dump was taken; mtime is when the upload finished. They usually
# agree, which is what would make trusting the wrong one survive review.
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

if [ -z "$found" ]; then
  echo "error: no app-${DB_NAME}-*.dump found in any prefix." >&2
  echo "What IS in the bucket:" >&2
  for prefix in daily weekly monthly; do
    rclone lsf "R2:${R2_BUCKET}/${prefix}/" 2>/dev/null \
      | grep -E '^app-.*\.dump$' | sed "s|^|  ${prefix}/|" >&2 || true
  done
  echo >&2
  echo "\"you asked for a database that is not backed up\" and \"the bucket is empty\"" >&2
  echo "must not both look like an empty directory." >&2
  exit 1
fi

newest="$(printf '%s' "$found" | sort -k1,1 | tail -1)"
object="$(echo "$newest" | awk '{print $1}')"
prefix="$(echo "$newest" | awk '{print $2}')"
remote="R2:${R2_BUCKET}/${prefix}/${object}"

mkdir -p "$DEST_DIR"
target="${DEST_DIR}/${object}"

# Download to .partial and rename only on success. A connection that dies
# mid-stream leaves a truncated dump that is non-empty and passes every "is it
# there?" check, indistinguishable from a good one until restore day.
echo "==> $remote"
rclone copyto "$remote" "${target}.partial" --progress

remote_size="$(rclone size "$remote" --json 2>/dev/null | grep -o '"bytes":[0-9]*' | cut -d: -f2)"
local_size="$(wc -c < "${target}.partial" | tr -d ' ')"
if [ -n "$remote_size" ] && [ "$remote_size" != "$local_size" ]; then
  rm -f "${target}.partial"
  echo "error: size mismatch — remote $remote_size, local $local_size" >&2
  exit 1
fi

# pg_dump -Fc archives start with the magic string PGDMP. Same check the backup
# writer makes before storing anything.
if [ "$(head -c 5 "${target}.partial")" != "PGDMP" ]; then
  rm -f "${target}.partial"
  echo "error: ${object} has no PGDMP header — not a pg_dump -Fc archive" >&2
  exit 1
fi

mv "${target}.partial" "$target"
echo "==> $target ($local_size bytes, verified)"
echo
echo "This is production data: real users, real email addresses, real password"
echo "hashes. backups/ is gitignored; delete it when you are done with it."
