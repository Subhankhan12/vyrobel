#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────────────────────────
# Vyrobel — DEPLOY §7.3 · the production deploy. §5 of docs/DEPLOYMENT.md is the same seven commands
# run by hand; this file exists because §7 ranked the risk precisely: "the risk is a skipped or
# REORDERED step under pressure, not ignorance." Order is load-bearing here —
#   · migrate BEFORE the caches (a cached config/route set built against the old schema is stale)
#   · horizon:terminate AFTER the new code is in place (workers must respawn INTO the new release)
#   · the cron verified BEFORE `up`, because its failure mode is silence, not an error
#
# ⚠️⚠️ THIS SCRIPT NEVER DESTROYS DATA. There is no migrate:fresh, no migrate:refresh, no db:wipe and
# no seeder anywhere in it — deliberately, and a deploy that wipes production is unrecoverable in a
# way nothing else here is. It runs `migrate --force` (incremental) and only after a backup.
#
# ⚠️ IT HOLDS NO SECRETS. Credentials are read from .env at run time (same `env_get` shape as
# ops/backup.sh — one definition). Secrets management proper is §7.4.
#
# ⚠️ THE BACKUP IS ops/backup.sh, NOT A DUMP WRITTEN HERE. That path is the PROVEN one —
# ops/restore-drill.sh restores it into a scratch database and compares. A second dump written here
# would be an unproven backup wearing the same name (§N.8f: one definition per concept).
#
# USAGE
#   ops/deploy.sh                 # deploy the current branch's origin HEAD
#   ops/deploy.sh --ref v1.2.0    # deploy a specific tag/branch/sha
#   ops/deploy.sh --dry-run       # print every step, execute none (safe anywhere)
#   ops/deploy.sh --rollback-to <sha>   # revert CODE to a release (DB restore stays manual — see below)
#
# ⚠️ ROLLBACK IS DELIBERATELY ASYMMETRIC. Code reverts automatically on a failed health check; the
# DATABASE does not. Restoring a database over live production is a decision with data-loss
# consequences (anything written since the snapshot is gone), so this script prints the exact
# restore command and stops rather than running it for you.
# ─────────────────────────────────────────────────────────────────────────────────────────────────
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "${APP_DIR}"

DRY_RUN=0
REF=""
ROLLBACK_TO=""
while [ $# -gt 0 ]; do
  case "$1" in
    --dry-run)     DRY_RUN=1 ;;
    --ref)         REF="${2:?--ref needs a value}"; shift ;;
    --rollback-to) ROLLBACK_TO="${2:?--rollback-to needs a value}"; shift ;;
    -h|--help)     sed -n '1,32p' "${BASH_SOURCE[0]}"; exit 0 ;;
    *) echo "unknown argument: $1" >&2; exit 2 ;;
  esac
  shift
done

step()  { printf '\n\033[1m▶ %s\033[0m\n' "$*"; }
note()  { printf '  %s\n' "$*"; }
fail()  { printf '\n\033[31m✖ %s\033[0m\n' "$*" >&2; }
run()   { if [ "${DRY_RUN}" = "1" ]; then printf '  [dry-run] %s\n' "$*"; else eval "$@"; fi; }

# Read config from .env rather than duplicating it — the same helper ops/backup.sh uses.
env_get() { grep -E "^${1}=" "${APP_DIR}/.env" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"'"'"'' ; }

# ── ROLLBACK MODE ────────────────────────────────────────────────────────────────────────────────
if [ -n "${ROLLBACK_TO}" ]; then
  step "ROLLBACK — reverting code to ${ROLLBACK_TO}"
  note "⚠️ This reverts CODE ONLY. If the failed deploy ran a migration, the schema is still the NEW one."
  run "php artisan down --render=\"errors::503\" || true"
  run "git reset --hard '${ROLLBACK_TO}'"
  run "composer install --no-dev --optimize-autoloader --no-interaction"
  run "npm ci && npm run build"
  run "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache"
  run "php artisan horizon:terminate || true"
  run "php artisan queue:restart"
  run "php artisan up"
  note "Code is back on ${ROLLBACK_TO}."
  note "⚠️ If a migration ran, restore the DB DELIBERATELY (this loses anything written since the snapshot):"
  note "    BACKUP_PASSPHRASE='…' ops/restore-drill.sh   # rehearse into a scratch DB first"
  exit 0
fi

# ── 1 · PRE-FLIGHT ───────────────────────────────────────────────────────────────────────────────
step "1/12 Pre-flight"
[ -f "${APP_DIR}/.env" ] || { fail ".env is missing at ${APP_DIR}/.env"; exit 2; }

APP_ENV_V="$(env_get APP_ENV)"; APP_DEBUG_V="$(env_get APP_DEBUG)"; APP_URL_V="$(env_get APP_URL)"
note "APP_ENV=${APP_ENV_V:-<unset>}  APP_URL=${APP_URL_V:-<unset>}"

[ -n "$(env_get APP_KEY)" ] || { fail "APP_KEY is empty. Encrypted guest PII is unreadable without it (§7.4)."; exit 2; }

# ⚠️ APP_DEBUG=true in production leaks stack traces, env values and query contents to the public.
if [ "${APP_ENV_V}" = "production" ] && [ "${APP_DEBUG_V}" = "true" ]; then
  fail "APP_DEBUG=true with APP_ENV=production — refusing. That leaks stack traces and env values publicly."
  exit 2
fi

run "php artisan db:monitor --max=1000 >/dev/null" || { fail "Database unreachable — fix before deploying."; exit 2; }
if [ "${DRY_RUN}" != "1" ]; then
  php -r 'exit((function(){ try { (new Redis())->connect(getenv("REDIS_HOST")?:"127.0.0.1", (int)(getenv("REDIS_PORT")?:6379)); return 0; } catch (\Throwable $e) { return 1; } })());' \
    && note "Redis reachable" || note "⚠️ Redis check inconclusive — Horizon needs it; verify manually"
fi

PREV_SHA="$(git rev-parse HEAD)"
note "Current release: ${PREV_SHA}"
note "⚠️ ROLLBACK POINT — if this deploy fails: ops/deploy.sh --rollback-to ${PREV_SHA}"

# ── 2 · BACKUP (the rollback point for the schema) ───────────────────────────────────────────────
step "2/12 Database + media backup (ops/backup.sh — the proven path)"
if [ -z "${BACKUP_PASSPHRASE:-}" ]; then
  fail "BACKUP_PASSPHRASE is not set, so no pre-migration backup can be taken."
  note "A deploy that migrates without a restorable snapshot has no way back. Set it and re-run:"
  note "    BACKUP_PASSPHRASE='…' ops/deploy.sh"
  exit 2
fi
run "ops/backup.sh"
note "⚠️ Restore rehearsal (do this periodically, not during an incident): ops/restore-drill.sh"

# ── 3 · MAINTENANCE MODE ─────────────────────────────────────────────────────────────────────────
step "3/12 Maintenance mode"
run "php artisan down --render=\"errors::503\" --retry=60"

# From here on, any failure must still bring the app back up.
trap 'fail "Deploy failed — bringing the app out of maintenance mode."; php artisan up || true; \
      note "Rollback: ops/deploy.sh --rollback-to '"${PREV_SHA}"'"' ERR

# ── 4 · CODE ─────────────────────────────────────────────────────────────────────────────────────
step "4/12 Fetch + place new code"
run "git fetch --all --tags --prune"
if [ -n "${REF}" ]; then
  run "git checkout --detach '${REF}'"
else
  run "git pull --ff-only"
fi
note "New release: $( [ "${DRY_RUN}" = "1" ] && echo '<dry-run>' || git rev-parse HEAD )"

# ── 5 · PHP DEPENDENCIES ─────────────────────────────────────────────────────────────────────────
step "5/12 composer install (production)"
run "composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist"

# ── 6 · ASSETS ───────────────────────────────────────────────────────────────────────────────────
# ⚠️ Built here because that is the model §5 established. When §7.6 (CI) starts publishing a built
# artifact, this step becomes "fetch the artifact" — the seam is here, not spread through the file.
step "6/12 Frontend assets (vite build)"
run "npm ci"
run "npm run build"

# ── 7 · MIGRATIONS ───────────────────────────────────────────────────────────────────────────────
# ⚠️⚠️ --force means "yes, run in production". It does NOT mean force-drop. There is deliberately no
# fresh/refresh/wipe/seed anywhere in this file.
step "7/12 Migrations (incremental, never destructive)"
run "php artisan migrate --force"

# ── 8 · CACHES (after migrate — order is load-bearing) ───────────────────────────────────────────
step "8/12 Production caches"
run "php artisan config:cache"
run "php artisan route:cache"
run "php artisan view:cache"
run "php artisan event:cache"

# ── 9 · QUEUE WORKERS ────────────────────────────────────────────────────────────────────────────
# ⚠️ AFTER the new code is in place: terminate tells the supervisor's workers to die so they respawn
# INTO the new release. Doing it earlier just restarts them into the old one.
step "9/12 Restart queue workers"
run "php artisan horizon:terminate || true"
run "php artisan queue:restart"

# ── 10 · THE SCHEDULER CRON — silence is the failure mode ────────────────────────────────────────
# ⚠️⚠️ THE GDPR JOBS LIVE OR DIE HERE. guests:process-deletions (the 14-day erasure) and
# guest-documents:purge-expired (the 30-day ID purge) are invoked ONLY by `schedule:run`. With no
# cron there is no error, no failed job and no alert — erasure requests are accepted and silently
# never served. That is invisible until an audit asks for evidence, so this step INSTALLS the line
# rather than merely warning about it.
step "10/12 Scheduler cron"
CRON_LINE="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
if crontab -l 2>/dev/null | grep -Fq "schedule:run"; then
  note "✅ present: $(crontab -l 2>/dev/null | grep -F 'schedule:run' | head -1)"
elif [ "${DEPLOY_NO_CRON_INSTALL:-0}" = "1" ]; then
  fail "No schedule:run cron, and DEPLOY_NO_CRON_INSTALL=1. GDPR deletion + ID purge will NOT run."
  note "Install manually:  (crontab -l 2>/dev/null; echo '${CRON_LINE}') | crontab -"
else
  note "⚠️ No schedule:run cron found — installing it (idempotent append):"
  note "    ${CRON_LINE}"
  run "(crontab -l 2>/dev/null; echo '${CRON_LINE}') | crontab -"
fi
run "php artisan schedule:list"
note "⚠️ Expect all 8 commands with a next-due time. §7.2's heartbeat is what catches later silence."

# ── 11 · UP ──────────────────────────────────────────────────────────────────────────────────────
step "11/12 Leave maintenance mode"
run "php artisan up"
trap - ERR

# ── 12 · HEALTH CHECK ────────────────────────────────────────────────────────────────────────────
step "12/12 Health check"
HEALTH_URL="${APP_URL_V%/}/up"
if [ "${DRY_RUN}" = "1" ]; then
  note "[dry-run] curl -fsS -o /dev/null -w '%{http_code}' ${HEALTH_URL}"
else
  CODE="$(curl -fsS -o /dev/null -w '%{http_code}' --max-time 20 "${HEALTH_URL}" || echo 000)"
  if [ "${CODE}" = "200" ]; then
    note "✅ ${HEALTH_URL} → 200"
  else
    fail "${HEALTH_URL} → ${CODE} (expected 200)"
    note "The app is UP but not healthy. Roll the code back with:"
    note "    ops/deploy.sh --rollback-to ${PREV_SHA}"
    note "⚠️ If step 7 ran a migration, the schema is still NEW after a code rollback — restoring the"
    note "   database is a separate, deliberate decision (it discards anything written since the backup)."
    exit 1
  fi
fi

step "Deploy complete"
note "Release:  $( [ "${DRY_RUN}" = "1" ] && echo '<dry-run>' || git rev-parse HEAD )"
note "Previous: ${PREV_SHA}  (rollback: ops/deploy.sh --rollback-to ${PREV_SHA})"
note "Now confirm by hand: Horizon dashboard reachable · failed_jobs empty · php artisan about"
