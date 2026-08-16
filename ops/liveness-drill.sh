#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────────────────────────
# Vyrobel — DEPLOY §7.2 · THE LIVENESS DRILL. Forces the stall and checks the alarm actually fires.
#
# ⚠️ AN ALERT NOBODY HAS TRIGGERED IS DECORATION. A configured monitor proves only that a monitor was
# configured. This ages the heartbeats past their windows and asserts /up/liveness turns 503 — and
# that a healthy app stays 200, because a check that fires on everything gets muted, which restores
# the very silence this exists to end.
#
# Usage:  APP_URL=http://127.0.0.1:8000 ops/liveness-drill.sh
# ⚠️ It restores fresh heartbeats on exit, so running it never leaves a false alarm behind.
# ─────────────────────────────────────────────────────────────────────────────────────────────────
set -euo pipefail
APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
URL="${APP_URL:-http://127.0.0.1:8000}/up/liveness"
cd "${APP_DIR}"

beat()   { php artisan tinker --execute="use App\\Support\\Ops\\Heartbeat; Heartbeat::beat(Heartbeat::SCHEDULER); Heartbeat::beat(Heartbeat::QUEUE);" >/dev/null 2>&1; }
age()    { php artisan tinker --execute="use App\\Support\\Ops\\Heartbeat; use Illuminate\\Support\\Facades\\Cache; Cache::put(Heartbeat::${1}, now()->subMinutes(${2})->toIso8601String(), 86400);" >/dev/null 2>&1; }
code()   { curl -s -o /dev/null -w '%{http_code}' "${URL}"; }
trap 'beat' EXIT

FAIL=0
check() { # name expected
  local got; got="$(code)"
  if [ "${got}" = "${2}" ]; then echo "   ✓ ${1}: HTTP ${got}"; else echo "   ✗ ${1}: HTTP ${got}, expected ${2}"; FAIL=1; fi
}

echo "→ ${URL}"
beat;                         check "healthy (fresh beats) stays silent" 200
age SCHEDULER 20;             check "scheduler stalled → alarm"          503
beat; age QUEUE 45;           check "queue stalled → alarm"              503
php artisan tinker --execute="use App\\Support\\Ops\\Heartbeat; use Illuminate\\Support\\Facades\\Cache; Cache::forget(Heartbeat::SCHEDULER); Cache::forget(Heartbeat::QUEUE);" >/dev/null 2>&1
                              check "never beaten (cron never installed)" 503
beat;                         check "recovered → silent again"           200

echo
if [ "${FAIL}" -eq 0 ]; then echo "✅ LIVENESS DRILL PASSED — the alarm fires on a real stall and stays quiet when healthy."; else echo "❌ LIVENESS DRILL FAILED."; fi
exit "${FAIL}"
