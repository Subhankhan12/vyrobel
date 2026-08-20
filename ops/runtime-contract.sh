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
