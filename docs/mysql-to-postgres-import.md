# Importing the production MySQL dump into Postgres

This is the procedure for the one-time data carry-over at cutover, referenced by
homelab#17. It has been run end-to-end against the actual production dump
(`speedrunwr.sql`, MySQL 5.7 source, taken 2026-08-16) and a freshly-migrated
Postgres 16 database, using the `dev` container from this repo's
`docker-compose.yml`. Row counts, foreign keys, and encoding were verified after
import; see "Verification" below for the exact checks.

## Scope: six tables, ~1,700 rows

Only these tables are imported, in this order (foreign keys require it):

| Order | Table | Rows | Depends on |
| --- | ---: | ---: | --- |
| 1 | `users` | 325 | — |
| 2 | `liked_runs` | 679 | — |
| 3 | `liked_run_user` | 602 | `users`, `liked_runs` |
| 4 | `user_verifications` | 60 | `users` |
| 5 | `easteregg` | 40 | — |
| 6 | `password_resets` | 13 | — (references `email` by value, not FK) |

### Everything else in the dump is skipped

- **`records`, `records_1702800002`, `records_1702886402`, `records_1702972802`**
  — 87.8 MB of the dump's 88 MB. `records` (the base table) is empty in
  production; the three generation tables are duplicated snapshots the old
  Python scraper produced. None of it is imported. `php artisan speedrunwr:sync`
  repopulates `records` from the speedrun.com API after cutover — this was
  confirmed and settled in the issue discussion, not re-litigated here.
- **`migrations`** — never import this table. Laravel owns it, and the rows in
  the dump are for the 2018 Laravel 5.5 migration set, not the current
  Postgres-targeted migrations in `database/migrations/`. Importing it would
  make `artisan migrate` believe those migrations already ran and it would
  skip creating the schema.
- Any other table in the dump not listed above (there are none of consequence
  at this size, per the row/size breakdown already gathered on the issue).

## Prerequisites

- The target Postgres database has already had `php artisan migrate --force`
  run against it (all 12 migrations, from empty). Do this first — the six
  tables must exist with the current schema before importing rows into them.
- The dump file, e.g. mounted or copied into reach of a shell that has
  `docker` available.
- No MySQL client needs to be pre-installed anywhere permanent. The procedure
  spins up a **throwaway** `mysql:5.7` container purely to parse the dump's SQL
  correctly (extended-insert dialect, string escaping, dates) and re-emit it as
  plain delimited text — not as a runtime dependency of the app.

## Why go via a temporary MySQL container instead of parsing the SQL by hand

The dump's `INSERT INTO ... VALUES (...),(...),...;` lines are one giant line
per table (mysqldump's extended-insert format) with standard MySQL string
escaping. Hand-parsing that with sed/awk risks getting quoting wrong on the one
row that has an apostrophe or backslash in it. Loading it into a real MySQL
server and reading it back out with `mysqldump --tab` costs a couple of minutes
and guarantees correct parsing, at the cost of a temporary container. At ~1,700
rows this is far simpler than standing up `pgloader` for a one-time job.

## Procedure

All commands assume the dump is at `$DUMP` and a scratch directory `$OUT` exists
on the host running the migration.

### 1. Extract just the six tables from the dump

The full dump is 88 MB; the six tables of interest are one `CREATE TABLE` and
one `INSERT INTO` line each. Pulling just those out first keeps the temporary
MySQL container's job small and fast:

```sh
awk '
  /^CREATE TABLE `(users|liked_runs|liked_run_user|user_verifications|easteregg|password_resets)`/ {f=1}
  f {print}
  f && /^\) ENGINE=/ {f=0; print ""}
  /^INSERT INTO `(users|liked_runs|liked_run_user|user_verifications|easteregg|password_resets)`/ {print; print ""}
' "$DUMP" > "$OUT/six_tables.sql"
```

This produces a ~210 KB file with just the six `CREATE TABLE` statements and
their six single-line `INSERT INTO` statements.

### 2. Load it into a throwaway MySQL container

```sh
docker run --rm -d --name srwr-mysql-import \
  -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=speedrunwr \
  mysql:5.7

# wait for it to accept connections
until docker exec srwr-mysql-import mysqladmin ping -uroot -proot --silent 2>/dev/null; do sleep 2; done
```

**Load with `--default-character-set=utf8mb4` explicitly.** This is the one
step that is easy to get wrong silently:

```sh
docker exec -i srwr-mysql-import mysql --default-character-set=utf8mb4 \
  -uroot -proot speedrunwr < "$OUT/six_tables.sql"
```

See "Landmine: the client charset flag is not optional" below for what happens
if this flag is dropped.

### 3. Verify the row counts landed exactly

```sh
docker exec srwr-mysql-import mysql --default-character-set=utf8mb4 -uroot -proot speedrunwr -e \
  "SELECT (SELECT COUNT(*) FROM users) users,
          (SELECT COUNT(*) FROM liked_runs) liked_runs,
          (SELECT COUNT(*) FROM liked_run_user) liked_run_user,
          (SELECT COUNT(*) FROM user_verifications) user_verifications,
          (SELECT COUNT(*) FROM easteregg) easteregg,
          (SELECT COUNT(*) FROM password_resets) password_resets;"
```

Expect exactly `325 / 679 / 602 / 60 / 40 / 13`. This matched on the real dump.

### 4. Export each table as tab-delimited text

`mysqldump --tab` writes into whatever directory `secure_file_priv` allows
(check with `SHOW VARIABLES LIKE 'secure_file_priv'`; on the stock `mysql:5.7`
image it's `/var/lib/mysql-files/`):

```sh
docker exec srwr-mysql-import mysqldump --default-character-set=utf8mb4 \
  -uroot -proot --no-create-info --tab=/var/lib/mysql-files \
  speedrunwr users liked_runs liked_run_user user_verifications easteregg password_resets

docker cp srwr-mysql-import:/var/lib/mysql-files/users.txt "$OUT/users.txt"
docker cp srwr-mysql-import:/var/lib/mysql-files/liked_runs.txt "$OUT/liked_runs.txt"
docker cp srwr-mysql-import:/var/lib/mysql-files/liked_run_user.txt "$OUT/liked_run_user.txt"
docker cp srwr-mysql-import:/var/lib/mysql-files/user_verifications.txt "$OUT/user_verifications.txt"
docker cp srwr-mysql-import:/var/lib/mysql-files/easteregg.txt "$OUT/easteregg.txt"
docker cp srwr-mysql-import:/var/lib/mysql-files/password_resets.txt "$OUT/password_resets.txt"
```

`mysqldump --tab`'s output is tab-delimited with `\N` for NULL — which is
Postgres's default `COPY` text-format NULL token, so no reformatting is needed
between the two.

### 5. Import into Postgres with `psql \copy`, in FK order

Column lists are given explicitly (matching the dump's column order, which
matches the current migrations' column order for all six tables) rather than
relying on table order, so this can't silently misalign if either schema ever
changes:

```sh
docker compose cp "$OUT/users.txt" db:/tmp/users.txt
docker compose cp "$OUT/liked_runs.txt" db:/tmp/liked_runs.txt
docker compose cp "$OUT/liked_run_user.txt" db:/tmp/liked_run_user.txt
docker compose cp "$OUT/user_verifications.txt" db:/tmp/user_verifications.txt
docker compose cp "$OUT/easteregg.txt" db:/tmp/easteregg.txt
docker compose cp "$OUT/password_resets.txt" db:/tmp/password_resets.txt

docker compose exec -T db psql -U speedrunwr -d speedrunwr <<'EOF'
\copy users ("id","userName","email","password","remember_token","created_at","updated_at","verified") FROM '/tmp/users.txt'
\copy liked_runs ("id","runId","gameId","categoryId","levelId","userId","platformId","regionId","competition","primaryTime","date","youtubeId","twitchId","created_at","updated_at") FROM '/tmp/liked_runs.txt'
\copy liked_run_user ("id","user_id","liked_run_id","created_at","updated_at") FROM '/tmp/liked_run_user.txt'
\copy user_verifications ("id","user_id","key","created_at","updated_at") FROM '/tmp/user_verifications.txt'
\copy easteregg ("id","ip","time","created_at","updated_at") FROM '/tmp/easteregg.txt'
\copy password_resets ("email","token","created_at") FROM '/tmp/password_resets.txt'
EOF
```

`easteregg.ip` is Postgres `inet` (from the migration's `$table->ipAddress()`)
versus the dump's plain `varchar(45)` — no transform needed, Postgres parses
the dotted-quad text directly.

### 6. Advance the id sequences

All five id-bearing tables import explicit ids from the dump, so each
`SERIAL` sequence has to be moved past the highest imported id or the next
`INSERT` from the app collides:

```sh
docker compose exec -T db psql -U speedrunwr -d speedrunwr <<'EOF'
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('liked_runs_id_seq', (SELECT MAX(id) FROM liked_runs));
SELECT setval('liked_run_user_id_seq', (SELECT MAX(id) FROM liked_run_user));
SELECT setval('user_verifications_id_seq', (SELECT MAX(id) FROM user_verifications));
SELECT setval('easteregg_id_seq', (SELECT MAX(id) FROM easteregg));
EOF
```

(`password_resets` has no id column — nothing to advance.)

### 7. Tear down the temporary MySQL container

```sh
docker stop srwr-mysql-import
```

(Started with `--rm`, so stopping it also removes it — no leftover volume.)

## Verification

Run after import, before considering it done:

```sql
-- Row counts match the dump exactly
SELECT count(*) FROM users;               -- 325
SELECT count(*) FROM liked_runs;          -- 679
SELECT count(*) FROM liked_run_user;      -- 602
SELECT count(*) FROM user_verifications;  -- 60
SELECT count(*) FROM easteregg;           -- 40
SELECT count(*) FROM password_resets;     -- 13

-- No orphaned foreign keys
SELECT count(*) FROM liked_run_user lru LEFT JOIN users u ON u.id = lru.user_id WHERE u.id IS NULL;              -- 0
SELECT count(*) FROM liked_run_user lru LEFT JOIN liked_runs lr ON lr.id = lru.liked_run_id WHERE lr.id IS NULL; -- 0
SELECT count(*) FROM user_verifications uv LEFT JOIN users u ON u.id = uv.user_id WHERE u.id IS NULL;            -- 0
```

All of the above were run against the real dump and returned the expected
values. Additionally: the app (`docker compose up -d app`) was hit at `/` and
`/up` after the import and both returned 200, and `/user/verify/{key}` was
exercised against a real imported `user_verifications` row and correctly
flipped that user's `verified` flag — confirming the imported data is not just
present but usable by the application as-is.

## Landmines

### The client charset flag is not optional

**Do not load or export the dump without `--default-character-set=utf8mb4` on
every `mysql`/`mysqldump` invocation.** Skipping it silently double-encodes
every multi-byte UTF-8 username on the way in. This was caught in testing: with
the flag omitted on the load step, a Cyrillic username stored correctly in the
dump as `Тима` (bytes `d0 a2 d0 b8 d0 bc d0 b0`) came back out of MySQL as
`Ð¢Ð¸Ð¼Ð°` (bytes `c3 90 c2 a2 ...` — UTF-8 bytes that were read as Latin-1 and
re-encoded to UTF-8). Re-running the load with the flag on both the import and
every subsequent read fixed it. There are at least 20 non-ASCII usernames in
the real data (Cyrillic, Portuguese, one German umlaut) that would have been
silently corrupted by this.

### The database-level charset is a red herring; the table-level charset is what matters

The dump's `CREATE DATABASE` statement specifies `utf8`/`utf8_unicode_ci` (the
3-byte MySQL "utf8", not utf8mb4) — which, taken at face value, is exactly the
kind of thing that mangles any 4-byte character (most emoji, some CJK
extension characters). **This does not actually apply to the six tables
imported here**: every one of them declares `DEFAULT CHARSET=utf8mb4 COLLATE
utf8mb4_unicode_ci` explicitly at the `CREATE TABLE` level, which overrides the
database default. Checked directly against the dump, not assumed. If any
4-byte character ever made it into one of these six tables, it is intact in
the source and will come across intact — nothing to fix here, but worth
confirming again if the six-table list above ever grows to include a table
that does inherit the database's `utf8` default.

### `migrations` table: do not import it

Called out above, repeated here because it is the easiest mistake to make by
habit (`mysqldump` without a table list dumps everything, including this). If
it's imported by accident, `artisan migrate` will report "Nothing to migrate"
against an empty schema and the app will fail on the first query.

### `APP_KEY` travels with the dump, not through it

Not a dump landmine exactly, but adjacent: the dump does not carry `APP_KEY` —
that lives in the environment, not the database. Whatever `APP_KEY` is
currently live in production has to be carried over to the new environment's
`.env` unchanged. Regenerating it invalidates every session and anything
encrypted with the old key (nothing currently is, but don't introduce the
habit). This repo's own local `.env` for dev/testing uses a freshly-generated
key, which is correct for dev — production's real key is a separate,
out-of-band concern for whoever does the cutover.
