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
# ⚠️ D-EDGE-CHECKS (seen 2026-10-06, fixed 2026-11-11, his go): THE EDGE IS NOT THE SITE. After the CDN's bot check met this Mac's automated
#    browser, a run answered 1 PASS · 10 FAIL — every route a 504 or a 307 to itself, ~80 ms each, `/up` included — while the application
#    had logged nothing and the server, asking through the same public hostname, got 200 everywhere. So a probe that fails here is asked a
#    SECOND time from the server itself (over SSH, `curl` through the public hostname, the same request): when the server gets the answer the
#    check wants, the verdict is COULD NOT MEASURE (and says so — "the edge, not the site"); when the server gets the wrong answer too, it is a
#    FAIL seen from TWO places; when the server cannot be reached, the FAIL STANDS (never downgraded without evidence). It is a CLASSIFICATION
#    only: a FAIL is still a FAIL (rule 8 is untouched — DEC-t is his), and a probe that PASSES here is never second-guessed.
#    Off with PUBLIC_CHECKS_SECOND_LOOK=0.
#
# Checks: /login + both social buttons · both social redirects 302 to their provider · /signup renders its form ·
# every anonymous /admin/* GET redirects to /admin/login (never 200, never 5xx) · anonymous /admin POSTs are
# refused (never 200/5xx) · OUR Content-Security-Policy arrives whole (D-PROD-CSP) · a 5xx sweep over every parameter-free public GET route ·
# the exposure battery SERVED=0.
#
# EXIT: 0 = every check PASSED · 1 = at least one FAIL · 2 = no FAIL but something COULD NOT MEASURE.
set -uo pipefail

BASE="${1:-https://vyrobel.com}"
REPO="$(cd "$(dirname "$0")/.." && pwd)"
UA="vyrobel-public-checks/1.0"
PASS=0; FAIL=0; CNM=0

# the server's own vantage point (the same host ops/release.sh deploys to — a drift test holds these equal to release.sh's)
SSH_HOST="${PUBLIC_CHECKS_SSH_HOST:-u545270279@82.25.102.188}"; SSH_PORT="${PUBLIC_CHECKS_SSH_PORT:-65002}"; SSH_KEY="${PUBLIC_CHECKS_SSH_KEY:-$HOME/.ssh/vyrobel_deploy}"
SECOND_LOOK="${PUBLIC_CHECKS_SECOND_LOOK:-1}"
ssh_() { ssh -i "$SSH_KEY" -p "$SSH_PORT" -o ConnectTimeout=20 -o BatchMode=yes "$SSH_HOST" "$@"; }

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

# $1 method $2 path → the SAME request, made by the server itself through the public hostname; prints "status|location|bodyfile",
# or UNAVAILABLE when the server cannot be asked (SSH refused, no key, switched off) — and then nothing is concluded from it.
fetch_server() {
    [ "$SECOND_LOOK" = 1 ] || { echo UNAVAILABLE; return 1; }
    local body out first
    body="$(mktemp)"
    out="$(ssh_ "b=\$(mktemp); w=\$(curl -sS -o \"\$b\" -w '%{http_code}|%{redirect_url}' -X '$1' -A '$UA' -H 'Cache-Control: no-cache' '${BASE}$2' 2>/dev/null || echo '000|'); echo \"\$w\"; cat \"\$b\"; rm -f \"\$b\"" 2>/dev/null)" || { echo UNAVAILABLE; return 1; }
    [ -n "$out" ] || { echo UNAVAILABLE; return 1; }
    first="$(printf '%s\n' "$out" | head -1)"
    printf '%s\n' "$out" | tail -n +2 > "$body"
    echo "$first|$body"
}

# A probe that failed HERE is asked once more from the server. $1 check name · $2 what this Mac saw · $3 method · $4 path · $5 predicate
# (a function taking status, location, bodyfile and returning 0 when that answer is the one the check WANTS).
# Prints the second look's verdict line itself. FAIL stands unless the server proves the edge was the cause.
second_look() {
    local name="$1" detail="$2" method="$3" pth="$4" pred="$5" r sst sloc sbody
    r="$(fetch_server "$method" "$pth")"
    if [ "$r" = UNAVAILABLE ]; then verdict FAIL "$name" "$detail — second look unavailable (the server could not be asked)"; return; fi
    IFS='|' read -r sst sloc sbody <<<"$r"
    if "$pred" "$sst" "$sloc" "$sbody"; then verdict CNM "$name" "this Mac: $detail · the server, through ${BASE#*://}: HTTP $sst — the EDGE, not the site"
    else verdict FAIL "$name" "$detail — and from the server too: HTTP $sst"; fi
}

# the same, for a LIST of failing paths (a batch check): the paths the server proves fine are edge noise, the rest are real.
# $1 check name · $2 method · $3 predicate · $4 total probed · rest: "path(what-this-Mac-saw)" entries · prints the verdict line
second_look_many() {
    local name="$1" method="$2" pred="$3" total="$4"; shift 4
    local entry pth real="" edge="" r sst sloc sbody
    for entry in "$@"; do
        pth="${entry%%(*}"
        r="$(fetch_server "$method" "$pth")"
        if [ "$r" = UNAVAILABLE ]; then real="$real $entry"; continue; fi
        IFS='|' read -r sst sloc sbody <<<"$r"
        if "$pred" "$sst" "$sloc" "$sbody"; then edge="$edge $entry"; else real="$real $entry(server:$sst)"; fi
    done
    if [ -n "$real" ]; then verdict FAIL "$name" "${real# }$([ -n "$edge" ] && echo " · edge-only:${edge}")"
    elif [ -n "$edge" ]; then verdict CNM "$name" "this Mac failed ${edge# } — the server, through ${BASE#*://}, got the right answer for each: the EDGE, not the site"
    else verdict PASS "$name" "$total routes"; fi
}

# what each check WANTS, as a predicate over (status, location, bodyfile) — used here and, for a failed probe, on the server's answer
want_200()            { [ "$1" = 200 ]; }
want_login()          { [ "$1" = 200 ] && grep -q "Continue with Google" "$3" 2>/dev/null && grep -q "Continue with X" "$3" 2>/dev/null; }
want_provider()       { local h; h="$(echo "$2" | sed -E 's#^https?://([^/]+).*#\1#')"; [ "$1" = 302 ] || return 1; local x; for x in $EXPECT_HOSTS; do [ "$h" = "$x" ] && return 0; done; return 1; }
want_signup()         { [ "$1" = 200 ] && grep -q 'id="signup"' "$3" 2>/dev/null && ! grep -q "New sign-ups are paused" "$3" 2>/dev/null; }
want_admin_redirect() { [ "$1" = 302 ] && [[ "$2" == */admin/login ]]; }
want_invite()         { [ "$1" = 200 ] && grep -q "can't be used\|can&#039;t be used" "$3" 2>/dev/null; }
want_not_served()     { case "$1" in 200|5??|000) return 1;; esac; return 0; }
want_status_page()    { [ "$1" = 200 ] && grep -q "Vyrobel status" "$3" 2>/dev/null && ! grep -qi "operational" "$3" 2>/dev/null; }
want_no_5xx()         { case "$1" in 5??|000) return 1;; esac; return 0; }

# ⚠️ D-PROD-CSP (found 2026-10-06; seen RESOLVED 2026-11-14 from outside): the host once REPLACED our Content-Security-Policy with a bare
# `upgrade-insecure-requests`, which silently switched off four protections (nobody framing us, no stray form targets, no <base> rewrite, no
# plugins). Nothing red said so. These are the four a browser needs from OUR policy; a response counts only when it names every one of them.
CSP_NEEDED="frame-ancestors form-action base-uri object-src"
# $1 = a file holding the response HEADERS. Returns 0 when the Content-Security-Policy header(s) name ALL four directives. Several CSP headers are
# fine (a browser enforces each of them, so any one that carries a directive keeps it in force); `Content-Security-Policy-Report-Only` does NOT
# count (it enforces nothing) — hence the colon straight after the name.
csp_has_all() {
    local d
    for d in $CSP_NEEDED; do
        grep -i '^content-security-policy:' "$1" 2>/dev/null | grep -qi -- "$d" || return 1
    done
    return 0
}

# The functions above are the whole of the second-look logic; a test sources this file with PUBLIC_CHECKS_LIB_ONLY=1 and drives them with a
# stand-in curl and ssh, so the script's decisions are proven without a network.
[ "${PUBLIC_CHECKS_LIB_ONLY:-0}" = 1 ] && return 0 2>/dev/null

echo "  ── public checks against ${BASE} ──"

# 1 · the front door
IFS='|' read -r st loc body <<<"$(fetch GET /login)"
if [ "$st" = 403 ] && is_cdn_challenge "$body"; then verdict CNM "/login renders" "CDN browser challenge";
elif [ "$st" = 200 ]; then
    grep -q "Continue with Google" "$body" && verdict PASS "/login shows 'Continue with Google'" "" || verdict FAIL "/login shows 'Continue with Google'" "button missing"
    grep -q "Continue with X" "$body" && verdict PASS "/login shows 'Continue with X'" "" || verdict FAIL "/login shows 'Continue with X'" "button missing"
else second_look "/login renders" "HTTP $st" GET /login want_login; fi

# 2 · both social redirects leave for their provider
for pair in "google|accounts.google.com" "twitter|x.com twitter.com"; do
    p="${pair%%|*}"; hosts="${pair#*|}"; EXPECT_HOSTS="$hosts"
    IFS='|' read -r st loc body <<<"$(fetch GET /auth/$p/redirect)"
    host="$(echo "$loc" | sed -E 's#^https?://([^/]+).*#\1#')"
    ok=0; for h in $hosts; do [ "$host" = "$h" ] && ok=1; done
    if [ "$st" = 302 ] && [ $ok = 1 ]; then verdict PASS "/auth/$p/redirect → 302 to provider" "$host"
    elif [ "$st" = 403 ] && is_cdn_challenge "$body"; then verdict CNM "/auth/$p/redirect" "CDN browser challenge"
    else second_look "/auth/$p/redirect → 302 to provider" "HTTP $st → ${host:-no location}" GET "/auth/$p/redirect" want_provider; fi
done

# 3 · sign-up renders its form (every switch defaults ON — a paused form after a deploy is an outage)
IFS='|' read -r st loc body <<<"$(fetch GET /signup)"
if [ "$st" = 403 ] && is_cdn_challenge "$body"; then verdict CNM "/signup renders the form" "CDN browser challenge"
elif [ "$st" = 200 ] && grep -q 'id="signup"' "$body" && ! grep -q "New sign-ups are paused" "$body"; then verdict PASS "/signup renders the form (sign-ups ON)" ""
else second_look "/signup renders the form (sign-ups ON)" "HTTP $st$(grep -q 'New sign-ups are paused' "$body" && echo ' — SIGN-UPS ARE PAUSED')" GET /signup want_signup; fi

# 4 · anonymous /admin: GETs redirect to the admin login; the login + the invite page are public by design
ADMIN_GET=(/admin /admin/overview /admin/customers /admin/customers/create /admin/customers/01ANONYMOUSPROBE0000000000 /admin/plans /admin/plans/create
           /admin/plans/01ANONYMOUSPROBE0000000000/edit /admin/activity /admin/audit /admin/switches /admin/account
           /admin/admins /admin/integrations /admin/integrations/channex /admin/two-factor/setup /admin/two-factor
           /admin/triage /admin/support /admin/break-glass /admin/revenue /admin/funnel /admin/privacy
           /admin/hotels /admin/announcements /admin/status)
bad=(); n=0
for pth in "${ADMIN_GET[@]}"; do
    IFS='|' read -r st loc body <<<"$(fetch GET "$pth")"; n=$((n+1))
    if [ "$st" = 403 ] && is_cdn_challenge "$body"; then bad+=("$pth(CDN)"); continue; fi
    [ "$st" = 302 ] && [[ "$loc" == */admin/login ]] || bad+=("$pth(${st}→${loc##*/})")
done
if [ ${#bad[@]} -eq 0 ]; then verdict PASS "anonymous /admin GETs → /admin/login" "$n routes"
else second_look_many "anonymous /admin GETs → /admin/login" GET want_admin_redirect "$n" "${bad[@]}"; fi

IFS='|' read -r st loc body <<<"$(fetch GET /admin/login)"
if [ "$st" = 200 ]; then verdict PASS "/admin/login renders" ""; else second_look "/admin/login renders" "HTTP $st" GET /admin/login want_200; fi

RANDTOK="$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 64)"
IFS='|' read -r st loc body <<<"$(fetch GET /admin/invite/$RANDTOK)"
if [ "$st" = 200 ] && grep -q "can't be used\|can&#039;t be used" "$body"; then verdict PASS "an unknown invite link gets the neutral page" ""
else second_look "an unknown invite link gets the neutral page" "HTTP $st" GET "/admin/invite/$RANDTOK" want_invite; fi

ADMIN_POST=(/admin/customers /admin/customers/01ANONYMOUSPROBE0000000000/details /admin/customers/01ANONYMOUSPROBE0000000000/suspend /admin/customers/01ANONYMOUSPROBE0000000000/archive
            /admin/customers/01ANONYMOUSPROBE0000000000/restore /admin/switches/signup.enabled /admin/admins /admin/plans
            /admin/break-glass /admin/break-glass/end /admin/support/users/01ANONYMOUSPROBE0000000000/verify-email
            /admin/privacy/requests /admin/announcements /admin/status)
bad=()
for pth in "${ADMIN_POST[@]}"; do
    IFS='|' read -r st loc body <<<"$(fetch POST "$pth")"
    case "$st" in 200|5??|000) bad+=("$pth($st)");; esac
done
if [ ${#bad[@]} -eq 0 ]; then verdict PASS "anonymous /admin POSTs refused (never 200/5xx)" "${#ADMIN_POST[@]} routes"
else second_look_many "anonymous /admin POSTs refused (never 200/5xx)" POST want_not_served "${#ADMIN_POST[@]}" "${bad[@]}"; fi

# 4b · ADMIN 01 · Phase 5 — the PUBLIC status page renders, and never claims more than was posted
IFS='|' read -r st loc body <<<"$(fetch GET /status)"
if [ "$st" = 403 ] && is_cdn_challenge "$body"; then verdict CNM "/status renders (manual incidents only)" "CDN browser challenge"
elif [ "$st" = 200 ] && grep -q "Vyrobel status" "$body" && ! grep -qi "operational" "$body"; then verdict PASS "/status renders (manual incidents only)" ""
else second_look "/status renders (manual incidents only)" "HTTP $st$(grep -qi operational "$body" && echo ' — CLAIMS OPERATIONAL')" GET /status want_status_page; fi

# 4c · ⚠️ OUR Content-Security-Policy ARRIVES WHOLE (D-PROD-CSP) — asked of /login's HEADERS. A host or CDN that swaps the header for its own is the failure; the
#      server's own look (through the same public hostname) tells the edge's doing from the site's, exactly as for every other check.
CSP_NAME="our Content-Security-Policy arrives whole (4 directives)"
hdr="$(mktemp)"; hbody="$(mktemp)"
st="$(curl -sS -o "$hbody" -D "$hdr" -w '%{http_code}' -A "$UA" -H 'Cache-Control: no-cache' "${BASE}/login" 2>/dev/null || echo 000)"
if [ "$st" = 403 ] && is_cdn_challenge "$hbody"; then verdict CNM "$CSP_NAME" "CDN browser challenge"
elif [ "$st" = 200 ] && csp_has_all "$hdr"; then verdict PASS "$CSP_NAME" "frame-ancestors · form-action · base-uri · object-src"
else
    detail="HTTP $st$([ "$st" = 200 ] && echo ' — a directive is missing from the policy that arrived')"
    shdr="$(mktemp)"
    if [ "$SECOND_LOOK" = 1 ] && ssh_ "curl -sS -o /dev/null -D - -A '$UA' -H 'Cache-Control: no-cache' '${BASE}/login'" > "$shdr" 2>/dev/null && [ -s "$shdr" ]; then
        if csp_has_all "$shdr"; then verdict CNM "$CSP_NAME" "this Mac: $detail · the server, through ${BASE#*://}: the whole policy — the EDGE, not the site"
        else verdict FAIL "$CSP_NAME" "$detail — and from the server too"; fi
    else verdict FAIL "$CSP_NAME" "$detail — second look unavailable (the server could not be asked)"; fi
fi

# 5 · the 5xx sweep — every parameter-free public GET route, anonymously
ROUTES="$(cd "$REPO" && php artisan route:list --method=GET --json 2>/dev/null | python3 -c '
import json, sys
for r in json.load(sys.stdin):
    u = r["uri"]
    if "{" in u or u.startswith(("api/", "livewire", "_", "oauth/", "storage/")) or u == "up/liveness":
        continue
    print("/" + u.lstrip("/"))' | sort -u)"
five=(); cnt=0
while IFS= read -r pth; do [ -n "$pth" ] || continue
    IFS='|' read -r st loc body <<<"$(fetch GET "$pth")"; cnt=$((cnt+1))
    case "$st" in 5??|000) five+=("$pth($st)");; esac
done <<<"$ROUTES"
if [ ${#five[@]} -eq 0 ]; then verdict PASS "5xx sweep — no server error on any public GET" "$cnt routes"
else second_look_many "5xx sweep" GET want_no_5xx "$cnt" "${five[@]}"; fi

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
