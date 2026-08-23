# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this
repository.

speedrunwr.com — a Laravel 12 (PHP `^8.3`) app that syncs and serves speedrun.com records,
deployed as a container via Coolify.

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
