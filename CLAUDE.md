# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this
repository.

speedrunwr.com — a Laravel 12 (PHP `^8.3`) app that syncs and serves speedrun.com records,
deployed as a container via Coolify.

## Local dev interface

`make` is the interface, and it lives **inside the devcontainer** — PHP and `make` are neither of
them on the Windows host. The fleet-wide table and reasoning live in the `homelab` vault at
`Conventions/Local Dev Interface.md`; restated here because that vault is private.

| | |
| --- | --- |
| `make` | List the targets. `.DEFAULT_GOAL := help` |
| `make build` | Build the dev image |
| `make test` | The suite, against a throwaway Postgres — see the warning below |
| `make run` | Serve on **8002**, Postgres on **55402**, URL printed last |
| `make database download restore migrate` | Newest R2 dump → local database → schema forward |

**Ports 8002 / 55402 are assigned, not defaulted.** Every app has fixed numbers so two can run at
once; `PORT=` and **`DB_HOST_PORT=`** override. This repo moved off 8000.

`DB_HOST_PORT`, not `DB_PORT`: compose substitutes variables from this repo's `.env`, where
Laravel's own `DB_PORT=5432` is the port the app uses to reach the `db` service *internally*.
Naming the published port `DB_PORT` silently published Postgres on 5432 instead of 55402 — which
is the exact collision the port table exists to prevent.

> [!CAUTION]
> **Never run the suite through the `app` service.** `docker compose run app php artisan test`
> can wipe a real database. `env_file: .env` puts `DB_*` into the container's real OS environment;
> Laravel's `env()` reads `$_SERVER` while PHPUnit's `<env name="DB_CONNECTION" value="sqlite"/>`
> only sets `$_ENV`/`putenv()`, so `phpunit.xml` never wins — `force="true"` included, verified.
> `RefreshDatabase` then truncates whatever the container points at. That is #14.
>
> `make test` uses the separate `test` service, which carries **no `env_file`** and an explicit
> environment block, against a **tmpfs** `test-db`. Locally the victim of getting this wrong is a
> disposable volume, which is exactly what lets the pattern get copied to a laptop pointing
> somewhere real.

`make database download` reads the nightly backup from R2, not a live `pg_dump` over SSH, so a dev
machine needs no production access. It wants a **read-only** fleet R2 token at
`~/.config/homelab/backups.env` — not this repo's `.env`, and not a copy of racknerd's write
token. `backups/` is gitignored: those dumps are production data.

## Work queue

Work lives in this repo's **GitHub Issues**, one issue per item, with exactly one `type:` label
— `feat` (epic), `tckt` (atomic unit of work), `bug`, `chore`, `spike` (time-boxed
investigation whose output is knowledge). Status is the issue's own state: open with no
`status:` label is queued, `status: active` / `status: blocked` say the rest, `done` is closed as
*completed*, `dropped` is closed as *not planned*. The body is `## Goal` /
`## Acceptance criteria` / `## Notes` by convention — this repo has no
`.github/ISSUE_TEMPLATE/`, so nothing scaffolds that shape for you.

## Branches and pull requests

Two levels:

- **`master` is production *and* the integration branch**, and the base for everything. Nothing
  is committed to it directly — `.github/workflows/deploy.yml` runs on every push to `master`,
  so merging the PR *is* the release. (Docs- and markdown-only commits are `paths-ignore`d and do
  not trigger a rebuild.)
- **A working branch per issue**, cut from `master` and merged back through a pull request.
  `.github/workflows/ci.yml` runs `artisan test` against a throwaway Postgres 16 service on every
  non-`master` branch and PR, so the PR is where a broken build gets caught before it can reach
  the live site.

The gate is real here, but know its shape: `artisan test` runs the actual suite in `tests/`
(record upsert, rate limiting, record row parsing, verified-record timeout, video ID parsing —
not just the stock Laravel example stubs), so a green run means those behaviors still hold. It
does not mean the whole app is safe to ship — it means the paths those tests cover still work.
Trunk-based means there is no staging beat between a merge and speedrunwr.com, so the PR is the
only review point there is; treat an untested code path as unverified regardless of CI color.

Name the branch:

```
TheShrug/<issue>-<type>-<slug>
```

```
^TheShrug/[0-9]+-(tckt|feat|bug|chore|spike)-[a-z0-9]+(-[a-z0-9]+)*$
```

- `<issue>` is the **issue number in this repo** — not a PR number. A PR number doesn't exist
  yet when the branch is cut, and renaming a branch after opening the PR detaches it from its
  head.
- `<type>` matches the issue's one `type:` label.
- `<slug>` is lowercase `a-z0-9-`; `.` and `_` collapse to `-`; aim for ≤ 40 characters. The
  issue holds the full title, so this is a handle, not a summary.

So issue #26 `type: chore` "Add CLAUDE.md branch policy" becomes
`TheShrug/26-chore-add-claude-md-branch-policy`.

**No issue, no branch** — the number is mandatory, so every branch traces back to the queue.
This replaces the old `chore/<slug>` / `feat/<slug>` convention and, deliberately, the "or
whatever an agent's worktree already gave you" escape hatch: a tool that names a branch from a
task description is a branch that has lost its link to the queue.

Branches are grandfathered **by date, not by a list** — the policy was adopted 2026-08-16, and a
branch whose last commit predates that could not have complied. `speedrunwr-mobile` (last commit
2018-07-12) is the one pre-policy branch in this repo. Never rename a branch that already has an
open PR. **Reference the issue number in the PR title too**, so the two link up even for
grandfathered branches.

**Cut from `origin/master`, and fetch first.** A branch cut from a stale local `master` starts
life missing merged work and will conflict with it later. The stale base also lies to you at
close-out: `git branch -d` compares against whatever `master` currently is, so a genuinely
merged branch refuses to delete and looks unmerged. Fast-forward the base rather than reaching
for `-D`, which skips the check entirely and would delete an unmerged branch just as happily:

```sh
git checkout master && git merge --ff-only origin/master
git branch -d <branch>          # now succeeds, and still checks
```

The fleet-wide policy and its reasoning live in the `homelab` vault at
`Conventions/Branching.md`. It's restated here rather than linked because that vault is private
and this repo is public.
