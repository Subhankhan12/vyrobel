# `ops/deploy.sh` — verified line by line (2026-08-26)

⚠️ **The canon has been wrong about this script twice.** It once recorded that the deploy *refuses* when
told to skip the cron; it does not — it warns and completes. This is a fresh read of all 210 lines, not a
restatement.

## ⚠️ First, two corrections to what was believed

| believed | actual |
|---|---|
| "`fail()` only prints, with no `exit`" | ⚠️ **True of the helper (line 53), but misleading about the script.** **7 of 8** `fail` call sites pair it with an explicit `exit`. Exactly **one** continues. |
| (implied) the script has no strict mode | ⚠️ **Wrong — line 32 is `set -euo pipefail`.** Any unchecked command that fails aborts the run. |

## Every step, and what happens when it fails

| # | step | on failure |
|---|---|---|
| — | arg parsing / `--rollback-to` | `set -e`; rollback path runs `down`/`horizon:terminate` with `|| true` (deliberate: a rollback must not be blocked by an already-down app) |
| 1 | **Pre-flight** | `.env` missing → **EXIT 2** · `APP_KEY` empty → **EXIT 2** · `APP_DEBUG=true` in production → **EXIT 2** · DB unreachable → **EXIT 2** · ⚠️ **Redis check → `note` only, CONTINUES** |
| 2 | **Backup** (`ops/backup.sh`) | `BACKUP_PASSPHRASE` unset → **EXIT 2** · backup command itself under `set -e` → **ABORTS** |
| 3 | Maintenance mode (`down`) | `set -e` → **ABORTS** |
| 4 | Fetch + place code | `set -e` → **ABORTS** |
| 5 | `composer install` | `set -e` → **ABORTS** |
| 6 | Vite build | `set -e` → **ABORTS** |
| 7 | **Migrations** (`--force`, incremental) | `set -e` → **ABORTS**, and the `trap` (line 118) brings the app back **up** |
| 8 | Production caches | `set -e` → **ABORTS** |
| 9 | Restart workers | ⚠️ `horizon:terminate || true` — **CONTINUES** (deliberate: no Horizon running yet on a first deploy) |
| 10 | **Scheduler cron** | cron present → ok · absent → **installs it** (idempotent append) · ⚠️ **absent AND `DEPLOY_NO_CRON_INSTALL=1` → prints a red line and CONTINUES** |
| 11 | `up` | `set -e` → **ABORTS** |
| 12 | **Health check** | non-200 → prints rollback instructions → **EXIT 1** (the only `exit 1` in the file) |

## ⚠️ The one failure path that genuinely continues

**Step 10, with `DEPLOY_NO_CRON_INSTALL=1`.** The deploy prints *"GDPR deletion + ID purge will NOT run"*
and completes successfully.

⚠️ **This was NOT changed, and the reasoning matters.** It is not an oversight — it is an **opt-in escape
hatch the operator must set deliberately**, for a machine whose cron is managed elsewhere (systemd timers,
a platform scheduler, a container orchestrator). Making it exit would break the only supported way to
deploy on such a host. ⚠️ **The consequence is real and belongs in the runbook, not in an `exit`:** without
that cron, **the 14-day guest deletion and the 30-day ID purge never run, silently.**

⚠️ **The `#7.2 heartbeat` referenced in the script's own comments is what is meant to catch this later.**
I did not verify that heartbeat exists and is wired — **UNKNOWN**, and it should be checked before relying
on it.

## Soft checks that continue (by design, but worth knowing)

- ⚠️ **Redis (step 1)** — `note "⚠️ Redis check inconclusive — Horizon needs it; verify manually"`. A deploy
  proceeds with Redis down, and **the first symptom is queued work never running** — silent, not loud.
- `horizon:terminate || true` (steps 9 and rollback) — correct on a first deploy.
- The `trap` on line 118 runs `php artisan up || true` so a mid-deploy failure never strands the app in
  maintenance mode.

## Assumptions the script makes

Each of these is assumed present and **is not checked**:

| assumption | if absent on a fresh machine |
|---|---|
| `git` and a checkout at `APP_DIR` with a remote | aborts (`set -e`) — **loud** |
| `composer` on `PATH` | aborts — **loud** |
| `npm` + a build toolchain for Vite | aborts — **loud** |
| `crontab` available and writable by the deploy user | ⚠️ **`crontab -l` failure is swallowed by `2>/dev/null`** — the script would then try to install and, if that fails, abort. But a **read-only or containerised** crontab is the case to watch. |
| the deploy user may `php artisan down/up` (writable storage) | aborts — loud |
| `ops/backup.sh` and `ops/restore-drill.sh` exist and are executable | aborts — loud |
| a web server / PHP-FPM already configured and pointing at the release | ⚠️ **NOT the script's job** — it never configures nginx, TLS or FPM. **The health check at step 12 is the first thing that notices.** |
| `HEALTH_URL` resolves and TLS is valid | step 12 → **EXIT 1** — loud |

⚠️ **The largest silent-ish gap is Redis**; the largest *unowned* gap is the web server and TLS, which the
script assumes someone else has already done.

## What the script deliberately does NOT do

- **No `migrate:fresh`, `migrate:refresh`, `db:wipe`, or any seeder** — verified by reading; a deploy cannot
  wipe production.
- **Holds no secrets** — reads `.env` at run time.
- **Does not write its own dump** — it calls `ops/backup.sh`, the path `ops/restore-drill.sh` actually
  rehearses.
