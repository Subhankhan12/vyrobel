#!/usr/bin/env bash
#
# ops/runtime-contract.sh — THE EXECUTABLE FORM OF THE §7.5 RUNTIME CONTRACT.
#
# ⚠️ WHY THIS FILE EXISTS. §7.5 of docs/DEPLOYMENT.md states, in prose, what the production host must
# provide. Prose cannot fail a build. This script asserts the SAME contract against a live machine and exits
# non-zero when it is not met, so CI's image cannot silently drift away from what production promises
# (§N.8f: one definition, enforced — not two copies free to disagree).
#
# ⚠️ IT DELIBERATELY CHECKS THE THINGS `composer check-platform-reqs` CANNOT SEE — the two traps §7.5.1
# recorded:
#   (a) ext-redis / REDIS_CLIENT — the phpredis default needs an extension NOT declared in composer.json.
#   (c) pcntl / posix FUNCTIONS  — php.ini `disable_functions` can neuter them while the extension still
#       reports loaded, Composer still reports success, and Horizon still starts, then cannot fork.
#
# Usage:
#   ops/runtime-contract.sh              # PHP + extensions + functions + ini
#   ops/runtime-contract.sh --services   # …and MySQL 8 + Redis reachability (run once they are up)
#   CI_IMAGE=1 ops/runtime-contract.sh   # …and the CI-ONLY additions (see §7.6: ext-gd for RoomPhotosTest)
#
# Exit codes: 0 = the contract holds. 1 = at least one requirement is not met (every failure is printed).

set -uo pipefail

FAILURES=0
pass() { printf '  \033[32m✓\033[0m %s\n' "$1"; }
fail() { printf '  \033[31m✗\033[0m %s\n' "$1"; FAILURES=$((FAILURES + 1)); }
note() { printf '  \033[33m•\033[0m %s\n' "$1"; }

echo "── §7.5 RUNTIME CONTRACT ─────────────────────────────────────────"

# ── PHP floor — composer.json requires ^8.4.1 ───────────────────────────────────────────────────────
PHP_VERSION="$(php -r 'echo PHP_VERSION;')"
if php -r 'exit(version_compare(PHP_VERSION, "8.4.1", ">=") ? 0 : 1);'; then
  pass "PHP ${PHP_VERSION} ≥ 8.4.1 (composer.json)"
else
  fail "PHP ${PHP_VERSION} is below the 8.4.1 floor declared in composer.json"
fi

# ── Extensions, each one cited to a usage site in §7.5.2 ────────────────────────────────────────────
for ext in zip pdo_mysql mbstring openssl curl fileinfo dom filter libxml xmlreader iconv hash pcre session ctype json sodium pcntl posix; do
  if php -r "exit(extension_loaded('${ext}') ? 0 : 1);"; then
    pass "ext-${ext}"
  else
    fail "ext-${ext} is MISSING (§7.5.2)"
  fi
done

# ── ⚠️ §7.5.1(c) — the FUNCTIONS, not the extension. Horizon forks and signals its workers. ─────────
for fn in pcntl_fork pcntl_signal posix_kill proc_open; do
  if php -r "exit(function_exists('${fn}') ? 0 : 1);"; then
    pass "${fn}() callable"
  else
    fail "${fn}() is BLOCKED — check php.ini disable_functions. Horizon cannot run here (§7.5.1c)"
  fi
done
DISABLED="$(php -r 'echo ini_get("disable_functions");')"
if [ -n "${DISABLED}" ]; then
  note "disable_functions is non-empty: ${DISABLED}"
fi

# ── ⚠️ §7.5.1(a) — the Redis client. composer check-platform-reqs is BLIND to this one. ─────────────
REDIS_CLIENT_SET="$(grep -E '^REDIS_CLIENT=' .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '[:space:]')"
HAS_PHPREDIS="$(php -r 'echo extension_loaded("redis") ? 1 : 0;')"
if [ "${REDIS_CLIENT_SET}" = "predis" ]; then
  pass "REDIS_CLIENT=predis — the pure-PHP client, no extension needed (§7.5.1a)"
elif [ "${HAS_PHPREDIS}" = "1" ]; then
  pass "ext-redis present — the phpredis default is satisfied (§7.5.1a)"
else
  fail "REDIS_CLIENT is not 'predis' AND ext-redis is missing. config/database.php:148 defaults to phpredis, which is NOT in composer.json — so this is the one gap the pre-flight cannot catch (§7.5.1a)"
fi

# ── ⚠️ §7.5.1(b) — intl is OPTIONAL. Recorded, never required. ──────────────────────────────────────
if php -r 'exit(extension_loaded("intl") ? 0 : 1);'; then
  note "ext-intl present — country names render in full. Optional; the app is correct without it (§7.5.1b)"
else
  note "ext-intl absent — the Guests CRM shows alpha-2 codes. Correct, by design (§7.5.1b)"
fi

# ── PHP ini, derived from real upload ceilings + the ID-encryption path (§7.5.4) ────────────────────
bytes() { php -r '
  $v = trim($argv[1]); if ($v === "" || $v === "-1") { echo $v === "-1" ? PHP_INT_MAX : 0; exit; }
  $u = strtolower(substr($v, -1)); $n = (int) $v;
  echo match ($u) { "g" => $n * 1024 ** 3, "m" => $n * 1024 ** 2, "k" => $n * 1024, default => $n };
' "$1"; }

check_ini() {
  local key="$1" min="$2" why="$3"
  local actual; actual="$(php -r "echo ini_get('${key}');")"
  if [ "$(bytes "${actual}")" -ge "$(bytes "${min}")" ]; then
    pass "${key}=${actual} (≥ ${min}) — ${why}"
  else
    fail "${key}=${actual} is below ${min} — ${why} (§7.5.4)"
  fi
}
check_ini upload_max_filesize 12M "the largest accepted upload is 10 MB (ATTACH_MAX_KB)"
check_ini post_max_size       14M "must exceed upload_max_filesize plus the rest of the form"
check_ini memory_limit        256M "an 8 MB ID plus its base64 ciphertext, both held in memory"

# ── Composer's own view — necessary, NOT sufficient (it is blind to (a) and (c) above) ──────────────
if [ -d vendor ]; then
  if composer check-platform-reqs --no-dev --quiet 2>/dev/null; then
    pass "composer check-platform-reqs --no-dev"
  else
    fail "composer check-platform-reqs --no-dev reported an unmet requirement"
    composer check-platform-reqs --no-dev 2>&1 | grep -v success | sed 's/^/      /'
  fi
else
  note "vendor/ absent — skipping composer check-platform-reqs (run it after install)"
fi

# ── STRIPE_FAKE must be pinned in the test config: CI never speaks to Stripe (#45) ──────────────────
if grep -q '<env name="STRIPE_FAKE" value="true"/>' phpunit.xml 2>/dev/null; then
  pass "phpunit.xml pins STRIPE_FAKE=true — the suite can never reach real Stripe"
else
  fail "phpunit.xml no longer pins STRIPE_FAKE=true — the suite could reach real Stripe (#45)"
fi

# ── ⚠️⚠️ THE `mariadb` DRIVER IS FORBIDDEN — and this is about the DRIVER, not the SERVER ───────────
#
# ⚠️⚠️ DO NOT "TIDY" THIS INTO THE MariaDB SERVER CHECK BELOW. They are different things and only one of
# them is a choice this repository makes:
#
#   · the SERVER is whatever the host runs (production runs MariaDB; CI runs genuine MySQL 8);
#   · the DRIVER is `DB_CONNECTION` in .env — Laravel's `mariadb` driver selects a DIFFERENT SCHEMA GRAMMAR.
#
# ⚠️ 🔬 MEASURED 2026-09-20 by running the whole suite once under each driver against the SAME server:
#      DB_CONNECTION=mysql    → 3,648 pass
#      DB_CONNECTION=mariadb  → 3,641 pass + 2 ERRORS
#        SQLSTATE[22007] 1292 Incorrect uuid value: '01M2ZK50D8XW4E674H9XH7SZDY'
#
# ⚠️⚠️ WHY: the `mariadb` grammar emits `notifications.id` as a **native UUID column**, and this application
# writes **ULIDs** into it. The column type and the value type disagree, and every notification insert fails.
# ⚠️ It fails LOUDLY in the suite, which is the only reason this was caught — but it would fail on the first
# real notification in production, i.e. on the first invite, booking confirmation or password reset.
#
# ⚠️ THE QUESTION IS CLOSED (docs/KNOWN-LIMITS.md L12e): the app talks to MariaDB through the `mysql` driver,
# and the engine differences that actually needed handling are handled elsewhere — the isolation level is
# pinned in config/database.php (L12b) and the one-live-payout rule is a pair of triggers (L12a).
# ⚠️ **Switching the driver to "match the server" is the intuitive move and it is the wrong one.**
DB_CONNECTION_VALUE="$(grep -E '^DB_CONNECTION=' .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"'"'"'' | tr -d '[:space:]')"
if [ -z "${DB_CONNECTION_VALUE}" ]; then
  note "no .env DB_CONNECTION to read — skipping the driver pin (run this from the application root)"
elif [ "${DB_CONNECTION_VALUE}" = "mariadb" ]; then
  fail "DB_CONNECTION=mariadb — the mariadb DRIVER makes notifications.id a native UUID while the app writes ULIDs (1292). Use DB_CONNECTION=mysql even when the SERVER is MariaDB (L12e)"
elif [ "${DB_CONNECTION_VALUE}" = "mysql" ]; then
  pass "DB_CONNECTION=mysql — the correct driver, whichever server is behind it (L12e)"
else
  fail "DB_CONNECTION=${DB_CONNECTION_VALUE} — this product is built and tested against the mysql driver only"
fi

# ── ⚠️ AND THE ISOLATION LEVEL MUST STILL BE PINNED BY THE APPLICATION, NOT BY THE HOST ─────────────
#
# ⚠️ 🔬 On MariaDB 11.6+ (`innodb_snapshot_isolation=ON`) a competing `SELECT … FOR UPDATE` at REPEATABLE
# READ aborts with **error 1020** instead of blocking and rejecting — so a legitimate concurrent booking is
# LOST rather than refused. READ COMMITTED is the only level correct on BOTH engines (L12b). The setting is
# applied at PDO CONNECT time so it reaches web, artisan, the queue worker and the scheduler alike.
# ⚠️ `DatabaseIsolationLevelTest` asserts the EFFECTIVE level at runtime; this asserts the CONFIG that
# produces it, so a deleted config line is caught even on a machine where the host default happens to agree.
if grep -q 'ATTR_INIT_COMMAND' config/database.php 2>/dev/null; then
  pass "config/database.php pins the transaction isolation level at connect time (L12b)"
else
  fail "config/database.php no longer sets ATTR_INIT_COMMAND — the no-double-booking guarantee would fall back to the HOST's default isolation level (L12b)"
fi

# ── ⚠️⚠️ THE COMPILED CONFIG HOLDS THE DATABASE PASSWORD — AND `config:cache` WRITES IT 644 ─────────
#
# ⚠️⚠️ **A FIX THAT A ROUTINE COMMAND UNDOES NEEDS A CHECK, NOT ONLY A STEP.** 🔬 `bootstrap/cache/config.php`
# is a compiled copy of `.env` and contains `DB_PASSWORD` **13 times**. `php artisan config:cache` creates it
# at **0644** every single time it runs — so tightening it once is not a fix, it is a fix with a half-life.
#
# ⚠️ 🔬 Measured 2026-09-20 on the live host: **0600 still serves** — `/up` returns 200 through a full
# framework boot and `artisan` still runs for the panel cron — so there is no cost to keeping it closed.
# ⚠️ L12k makes the `chmod` a per-release step; this makes the *result* checkable, because a step in a
# procedure is a line somebody can delete and nobody would notice until the file was read by another tenant.
if [ -f bootstrap/cache/config.php ]; then
  CONFIG_MODE="$(stat -c %a bootstrap/cache/config.php 2>/dev/null || stat -f %Lp bootstrap/cache/config.php 2>/dev/null)"
  case "${CONFIG_MODE}" in
    600) pass "bootstrap/cache/config.php is 0600 (it holds DB_PASSWORD; config:cache writes 0644)" ;;
    *)   fail "bootstrap/cache/config.php is 0${CONFIG_MODE}, not 0600 — it contains the database password in clear, and \`config:cache\` recreates it 0644 every run. chmod 600 it (L12k step 3)." ;;
  esac
else
  note "bootstrap/cache/config.php absent — nothing to check (run after config:cache)"
fi

# ── ⚠️⚠️ `LOG_LEVEL=debug` IN PRODUCTION IS A PII LEAK, AND IT IS A TEMPTING ONE ─────────────────────
#
# ⚠️⚠️ WHY THIS EXISTS, STATED PLAINLY SO IT IS NEVER MISTAKEN FOR A BACK DOOR LATER. 🔬 Measured
# 2026-09-21: production runs `MAIL_MAILER=log` with `LOG_LEVEL=warning`, and Laravel's log mailer writes at
# **debug** — so every email is not merely undelivered, it is **discarded without a trace**. That makes
# invites and password resets untestable, because the token is sha256 in the database and the plaintext
# exists only inside the Mailable.
#
# ⚠️ The one workaround is `LOG_LEVEL=debug`, which drops the full MIME body — **including the recipient's
# address and the single-use token** — into `storage/logs`. That is acceptable on a parked site with one
# test account and **unacceptable the day a real guest exists**, and the difference between those two days
# is invisible from inside the expedient. ⚠️ So the expedient is legitimate ONLY while something is
# watching for it to be left on. This is that something.
#
# ⚠️ IT IS A DIAGNOSTIC, TIME-BOXED AND REVERTED — never a standing configuration.
LOG_LEVEL_VALUE="$(grep -E '^LOG_LEVEL=' .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"'"'"'' | tr -d '[:space:]')"
APP_ENV_VALUE="$(grep -E '^APP_ENV=' .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"'"'"'' | tr -d '[:space:]')"
if [ "${APP_ENV_VALUE}" = "production" ]; then
  case "${LOG_LEVEL_VALUE}" in
    debug)
      fail "LOG_LEVEL=debug with APP_ENV=production — the log mailer writes at debug, so every outbound email body (recipient address + single-use invite/reset token) is being written to storage/logs. Revert it to warning and purge the captured lines." ;;
    "")
      note "LOG_LEVEL unset in .env — the daily channel defaults to 'warning' under APP_ENV=production" ;;
    *)
      pass "LOG_LEVEL=${LOG_LEVEL_VALUE} in production (not debug — mail bodies and their tokens stay out of the log)" ;;
  esac
else
  note "APP_ENV=${APP_ENV_VALUE:-<unset>} — the LOG_LEVEL=debug prohibition is production-only"
fi

# ── CI-ONLY additions (NOT part of the production contract — see §7.6) ──────────────────────────────
if [ "${CI_IMAGE:-0}" = "1" ]; then
  echo "── CI-ONLY (deliberately beyond §7.5 — see §7.6.4) ──────────────"
  if php -r 'exit(extension_loaded("gd") ? 0 : 1);'; then
    pass "ext-gd — required by the TEST SUITE only (RoomPhotosTest → PlaceholderImage::jpeg), never by production"
  else
    fail "ext-gd missing — tests/Feature/RoomPhotosTest.php:79 throws without it (§7.6.4)"
  fi
fi

# ── Services (opt-in: run once MySQL and Redis are up) ──────────────────────────────────────────────
if [ "${1:-}" = "--services" ]; then
  echo "── SERVICES ─────────────────────────────────────────────────────"

  DB_HOST="${DB_HOST:-127.0.0.1}"; DB_PORT="${DB_PORT:-3306}"
  DB_USERNAME="${DB_USERNAME:-root}"; DB_PASSWORD="${DB_PASSWORD:-}"
  MYSQL_BANNER="$(php -r '
    try {
      $p = new PDO(sprintf("mysql:host=%s;port=%s", $argv[1], $argv[2]), $argv[3], $argv[4]);
      echo $p->query("SELECT VERSION()")->fetchColumn();
    } catch (Throwable $e) { echo "UNREACHABLE: ".$e->getMessage(); }
  ' "${DB_HOST}" "${DB_PORT}" "${DB_USERNAME}" "${DB_PASSWORD}" 2>/dev/null)"

  case "${MYSQL_BANNER}" in
    UNREACHABLE*) fail "MySQL not reachable at ${DB_HOST}:${DB_PORT} — ${MYSQL_BANNER}" ;;
    *MariaDB*|*mariadb*)
      # ⚠️ Not a preference. The no-double-booking guarantee is InnoDB SELECT … FOR UPDATE.
      fail "MariaDB detected (${MYSQL_BANNER}) — the contract requires GENUINE MySQL 8 (§7.5.3)" ;;
    8.*|9.*) pass "MySQL ${MYSQL_BANNER} (genuine, ≥ 8)" ;;
    *)       fail "MySQL ${MYSQL_BANNER} is below the 8.x floor (§7.5.3)" ;;
  esac

  REDIS_HOST="${REDIS_HOST:-127.0.0.1}"; REDIS_PORT="${REDIS_PORT:-6379}"
  PONG="$(php -r '
    $fp = @fsockopen($argv[1], (int) $argv[2], $e, $s, 5);
    if (! $fp) { echo "UNREACHABLE"; exit; }
    fwrite($fp, "PING\r\n"); echo trim((string) fgets($fp)); fclose($fp);
  ' "${REDIS_HOST}" "${REDIS_PORT}" 2>/dev/null)"
  if [ "${PONG}" = "+PONG" ]; then
    pass "Redis reachable at ${REDIS_HOST}:${REDIS_PORT}"
  else
    fail "Redis not reachable at ${REDIS_HOST}:${REDIS_PORT} (got '${PONG}') — cache, sessions and the queue depend on it (§7.5.3)"
  fi
fi

echo "──────────────────────────────────────────────────────────────────"
if [ "${FAILURES}" -eq 0 ]; then
  echo "✅ The §7.5 runtime contract HOLDS on this machine."
  exit 0
fi
echo "❌ ${FAILURES} requirement(s) of the §7.5 runtime contract are NOT met."
echo "   This build must not be trusted: it would be testing against a machine production does not have."
exit 1
