#!/usr/bin/env bash
#
# ops/public-checks.sh — ⚠️⚠️ EVERYTHING PUBLIC, CHECKED FROM OUTSIDE, AFTER EVERY DEPLOY — needing no session.
#
# ⚠️ THE PUBLIC FRONT DOOR IS NOT COLLATERAL FOR THE ADMIN BACK OFFICE. Run after every swap; any FAIL means the
#    release is rolled back FIRST and diagnosed after — a public failure is not debugged while it is live.
#
# THREE VERDICTS, NEVER TWO: PASS · FAIL · COULD NOT MEASURE. ⚠️ The CDN in front of production shows a
# "Checking your browser…" page (HTTP 403) to some fresh clients. That is NOT the application refusing and NOT a
# pass — it is COULD NOT MEASURE, and it gets a control run before anyone believes it either way.
#
# Checks: /login + both social buttons · both social redirects 302 to their provider · /signup renders its form ·
# every anonymous /admin/* GET redirects to /admin/login (never 200, never 5xx) · anonymous /admin POSTs are
# refused (never 200/5xx) · a 5xx sweep over every parameter-free public GET route · the exposure battery SERVED=0.
#
# EXIT: 0 = every check PASSED · 1 = at least one FAIL · 2 = no FAIL but something COULD NOT MEASURE.
set -uo pipefail

BASE="${1:-https://vyrobel.com}"
REPO="$(cd "$(dirname "$0")/.." && pwd)"
UA="vyrobel-public-checks/1.0"
PASS=0; FAIL=0; CNM=0

verdict() { # $1 PASS|FAIL|CNM  $2 check  $3 detail
    case "$1" in PASS) PASS=$((PASS+1)); tag="PASS";; FAIL) FAIL=$((FAIL+1)); tag="FAIL ✗";; *) CNM=$((CNM+1)); tag="COULD NOT MEASURE";; esac
    printf '  %-18s %-52s %s\n' "$tag" "$2" "$3"
}

# $1 method $2 path → prints "status|location|bodyfile"
fetch() {
    local body; body="$(mktemp)"
    local out; out="$(curl -sS -o "$body" -w '%{http_code}|%{redirect_url}' -X "$1" -A "$UA" -H 'Cache-Control: no-cache' "${BASE}$2" 2>/dev/null || echo "000|")"
    echo "$out|$body"
}
is_cdn_challenge() { grep -q "Checking your browser" "$1" 2>/dev/null; }

echo "  ── public checks against ${BASE} ──"

# 1 · the front door
IFS='|' read -r st loc body <<<"$(fetch GET /login)"
if [ "$st" = 403 ] && is_cdn_challenge "$body"; then verdict CNM "/login renders" "CDN browser challenge";
elif [ "$st" = 200 ]; then
    grep -q "Continue with Google" "$body" && verdict PASS "/login shows 'Continue with Google'" "" || verdict FAIL "/login shows 'Continue with Google'" "button missing"
    grep -q "Continue with X" "$body" && verdict PASS "/login shows 'Continue with X'" "" || verdict FAIL "/login shows 'Continue with X'" "button missing"
else verdict FAIL "/login renders" "HTTP $st"; fi

# 2 · both social redirects leave for their provider
for pair in "google|accounts.google.com" "twitter|x.com twitter.com"; do
    p="${pair%%|*}"; hosts="${pair#*|}"
    IFS='|' read -r st loc body <<<"$(fetch GET /auth/$p/redirect)"
    host="$(echo "$loc" | sed -E 's#^https?://([^/]+).*#\1#')"
    ok=0; for h in $hosts; do [ "$host" = "$h" ] && ok=1; done
    if [ "$st" = 302 ] && [ $ok = 1 ]; then verdict PASS "/auth/$p/redirect → 302 to provider" "$host"
    elif [ "$st" = 403 ] && is_cdn_challenge "$body"; then verdict CNM "/auth/$p/redirect" "CDN browser challenge"
    else verdict FAIL "/auth/$p/redirect → 302 to provider" "HTTP $st → ${host:-no location}"; fi
done

# 3 · sign-up renders its form (every switch defaults ON — a paused form after a deploy is an outage)
IFS='|' read -r st loc body <<<"$(fetch GET /signup)"
if [ "$st" = 403 ] && is_cdn_challenge "$body"; then verdict CNM "/signup renders the form" "CDN browser challenge"
elif [ "$st" = 200 ] && grep -q 'id="signup"' "$body" && ! grep -q "New sign-ups are paused" "$body"; then verdict PASS "/signup renders the form (sign-ups ON)" ""
else verdict FAIL "/signup renders the form (sign-ups ON)" "HTTP $st$(grep -q 'New sign-ups are paused' "$body" && echo ' — SIGN-UPS ARE PAUSED')"; fi

# 4 · anonymous /admin: GETs redirect to the admin login; the login + the invite page are public by design
ADMIN_GET=(/admin /admin/overview /admin/customers /admin/customers/01ANONYMOUSPROBE0000000000 /admin/plans /admin/plans/create
           /admin/plans/01ANONYMOUSPROBE0000000000/edit /admin/activity /admin/audit /admin/switches /admin/account
           /admin/admins /admin/integrations /admin/integrations/channex /admin/two-factor/setup /admin/two-factor
           /admin/triage /admin/support /admin/break-glass /admin/revenue /admin/funnel /admin/privacy
           /admin/hotels /admin/announcements /admin/status)
bad=""; n=0
for pth in "${ADMIN_GET[@]}"; do
    IFS='|' read -r st loc body <<<"$(fetch GET "$pth")"; n=$((n+1))
    if [ "$st" = 403 ] && is_cdn_challenge "$body"; then bad="$bad $pth(CDN)"; continue; fi
    [ "$st" = 302 ] && [[ "$loc" == */admin/login ]] || bad="$bad $pth(${st}→${loc##*/})"
done
[ -z "$bad" ] && verdict PASS "anonymous /admin GETs → /admin/login" "$n routes" || verdict FAIL "anonymous /admin GETs → /admin/login" "$bad"

IFS='|' read -r st loc body <<<"$(fetch GET /admin/login)"
[ "$st" = 200 ] && verdict PASS "/admin/login renders" "" || verdict FAIL "/admin/login renders" "HTTP $st"

RANDTOK="$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 64)"
IFS='|' read -r st loc body <<<"$(fetch GET /admin/invite/$RANDTOK)"
{ [ "$st" = 200 ] && grep -q "can't be used\|can&#039;t be used" "$body"; } && verdict PASS "an unknown invite link gets the neutral page" "" || verdict FAIL "an unknown invite link gets the neutral page" "HTTP $st"

ADMIN_POST=(/admin/customers/01ANONYMOUSPROBE0000000000/suspend /admin/customers/01ANONYMOUSPROBE0000000000/archive
            /admin/customers/01ANONYMOUSPROBE0000000000/restore /admin/switches/signup.enabled /admin/admins /admin/plans
            /admin/break-glass /admin/break-glass/end /admin/support/users/01ANONYMOUSPROBE0000000000/verify-email
            /admin/privacy/requests /admin/announcements /admin/status)
bad=""
for pth in "${ADMIN_POST[@]}"; do
    IFS='|' read -r st loc body <<<"$(fetch POST "$pth")"
    case "$st" in 200|5??|000) bad="$bad $pth($st)";; esac
done
[ -z "$bad" ] && verdict PASS "anonymous /admin POSTs refused (never 200/5xx)" "${#ADMIN_POST[@]} routes" || verdict FAIL "anonymous /admin POSTs refused" "$bad"

# 4b · ADMIN 01 · Phase 5 — the PUBLIC status page renders, and never claims more than was posted
IFS='|' read -r st loc body <<<"$(fetch GET /status)"
if [ "$st" = 403 ] && is_cdn_challenge "$body"; then verdict CNM "/status renders (manual incidents only)" "CDN browser challenge"
elif [ "$st" = 200 ] && grep -q "Vyrobel status" "$body" && ! grep -qi "operational" "$body"; then verdict PASS "/status renders (manual incidents only)" ""
else verdict FAIL "/status renders (manual incidents only)" "HTTP $st$(grep -qi operational "$body" && echo ' — CLAIMS OPERATIONAL')"; fi

# 5 · the 5xx sweep — every parameter-free public GET route, anonymously
ROUTES="$(cd "$REPO" && php artisan route:list --method=GET --json 2>/dev/null | python3 -c '
import json, sys
for r in json.load(sys.stdin):
    u = r["uri"]
    if "{" in u or u.startswith(("api/", "livewire", "_", "oauth/", "storage/")) or u == "up/liveness":
        continue
    print("/" + u.lstrip("/"))' | sort -u)"
five=""; cnt=0
while IFS= read -r pth; do [ -n "$pth" ] || continue
    IFS='|' read -r st loc body <<<"$(fetch GET "$pth")"; cnt=$((cnt+1))
    case "$st" in 5??|000) five="$five $pth($st)";; esac
done <<<"$ROUTES"
[ -z "$five" ] && verdict PASS "5xx sweep — no server error on any public GET" "$cnt routes" || verdict FAIL "5xx sweep" "$five"

IFS='|' read -r st loc body <<<"$(fetch GET /up/liveness)"
printf '  %-18s %-52s %s\n' "info" "/up/liveness" "HTTP $st (503 = a stale heartbeat, by design — read with the health board)"

# 6 · the exposure battery
BAT="$("$REPO/ops/exposure-battery.sh" "$BASE" 2>&1)"
SERVED="$(echo "$BAT" | grep -oE 'SERVED=[0-9]+' | tail -1 | cut -d= -f2)"
case "$SERVED" in 0) verdict PASS "exposure battery SERVED=0" "$(echo "$BAT" | grep -oE 'APPLICATION-REFUSED=[0-9]+   LAYER-ANSWERED=[0-9]+' | tail -1)";;
    "") verdict CNM "exposure battery" "no SERVED= line in its output";;
    *) verdict FAIL "exposure battery SERVED=0" "SERVED=$SERVED";; esac

echo "  ── PASS $PASS · FAIL $FAIL · COULD NOT MEASURE $CNM ──"
[ $FAIL -gt 0 ] && exit 1
[ $CNM -gt 0 ] && exit 2
exit 0
