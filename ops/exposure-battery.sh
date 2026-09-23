#!/usr/bin/env bash
#
# ⚠️⚠️ THE EXPOSURE BATTERY — the only instrument that watches the only wall this deployment has.
#
# ⚠️ WHY IT EXISTS. `public_html/` is BOTH the application root and the document root: `.env`, `artisan`
# and `storage/` sit INSIDE the served tree. Nothing but a handful of `RedirectMatch` lines in
# `public_html/.htaccess` stands between the public internet and every secret this system holds — the
# database password, `APP_KEY` (which is the only copy, and without which every encrypted guest record is
# permanently unreadable), and the maintenance bypass secret in `storage/framework/down`.
#
# ⚠️⚠️ A RULE NOBODY TESTS IS A RULE THAT DECAYS. One panel reset, one `AllowOverride` change, one careless
# edit, and the wall is gone with nothing going red. This script is what makes that loud.
#
# ⚠️ IT REPORTS, IT NEVER PRINTS A BODY. If a probe IS exposed, printing the response would put the secret
# into a terminal, a log and a transcript. It prints the marker that matched and the byte count, nothing else.
#
# EXIT CODES:  0 = every probe refused · 1 = at least one exposure · 2 = the instrument could not run.
#
# USAGE:
#   ops/exposure-battery.sh [BASE_URL]           # default https://vyrobel.com
#   ops/exposure-battery.sh --prove-red [BASE]   # adds a KNOWN-PUBLIC path so the battery MUST go red,
#                                                # proving the detector fires. A guard never seen to fire
#                                                # is not a guard.
set -u

BASE="https://vyrobel.com"
PROVE_RED=0
for arg in "$@"; do
    case "$arg" in
        --prove-red) PROVE_RED=1 ;;
        http*) BASE="${arg%/}" ;;
        *) printf 'unknown argument: %s\n' "$arg" >&2; exit 2 ;;
    esac
done

command -v curl >/dev/null 2>&1 || { echo "curl not found"; exit 2; }

# ⚠️ A STATUS CODE IS NOT A RESULT. A 200 is the obvious failure, but a misconfigured server can also hand
# back file content under a 206 or with an error status. So every probe is judged on BOTH the status AND
# the body: if any marker of real content appears, it is an exposure whatever the status said.
MARKERS='APP_KEY=|DB_PASSWORD=|DB_USERNAME=|STRIPE_SECRET|POSTMARK_TOKEN|MAIL_MAILER=|^<\?php|namespace App\\|#!/usr/bin/env bash|"require"|autoload_real|maintenance.*secret|"secret":'

# Paths that MUST be refused. Grouped by the URL shape that could reach them.
PROBES=(
    # ── top-level: the document root IS the application root, so these are literal files on disk
    "/.env"
    "/.env.example"
    "/.htaccess"
    "/artisan"
    "/composer.json"
    "/composer.lock"
    "/package.json"
    "/vite.config.js"
    "/app/Models/User.php"
    "/app/Http/Middleware/EnsureBackOfficeAccess.php"
    "/bootstrap/cache/config.php"
    "/config/app.php"
    "/config/database.php"
    "/database/migrations"
    "/resources/views/layouts/app.blade.php"
    "/routes/web.php"
    "/storage/framework/down"
    "/storage/logs"
    "/vendor/autoload.php"
    "/ops/deploy.sh"
    "/ops/exposure-battery.sh"

    # ── through the `current` symlink (the release is reachable this way by design)
    "/current/.env"
    "/current/.env.example"
    "/current/.release"
    "/current/.gitignore"
    "/current/.gitattributes"
    "/current/artisan"
    "/current/composer.json"
    "/current/composer.lock"
    "/current/package.json"
    "/current/vite.config.js"
    "/current/app/Models/User.php"
    "/current/bootstrap/cache/config.php"
    "/current/config/app.php"
    "/current/config/database.php"
    "/current/database/migrations"
    "/current/routes/web.php"
    "/current/resources/views"
    "/current/storage/framework/down"
    "/current/storage/logs"
    "/current/vendor/autoload.php"
    "/current/ops/deploy.sh"

    # ⚠️⚠️ THE WIDENING. A rule that names one symlink and not the thing it points at is an allow-list
    # wearing a deny-list's clothes. `releases/` sits BESIDE the document root today — these probes are
    # what would catch the day it doesn't, or the day a second pointer is added.
    "/releases/rel_C/.env"
    "/releases/rel_C/app/Models/User.php"
    "/releases/rel_B/.env"
    "/rel_C/.env"
    "/rel_C/app/Models/User.php"
    "/public_html/.env"

    # ── version control, if one is ever left behind (F-67: production had no .git; this keeps it that way)
    "/.git/config"
    "/.git/HEAD"
    "/current/.git/config"

    # ── the old release, reachable if anything ever points at it
    "/current/../releases/rel_B/.env"
    "/current/public/../.env"
)

pad() { printf '%-52s' "$1"; }

exposed=0
refused=0
locked=0
unmeasurable=0
unmeasured=0
printf '  %-52s %-6s %-8s %s\n' "PATH" "HTTP" "BYTES" "VERDICT"
printf '  %s\n' "------------------------------------------------------------------------------------"

# ⚠️⚠️ "I COULD NOT MEASURE" IS NOT "THE WALL FAILED", AND CONFLATING THEM RUINS THE INSTRUMENT.
# 🔬 Measured 2026-09-21: a transient curl failure on one probe made this script report an EXPOSURE and
# exit non-zero while the wall was perfectly intact. A battery that cries wolf on a network blip gets
# re-run until it goes green, which is precisely the habit it exists to prevent. So a probe that cannot
# be measured is retried once and then reported as UNMEASURED with its own exit code (2) — ⚠️ never
# silently downgraded to green, and never dressed up as a breach.
fetch() {
    # Echoes the HTTP status for ONE attempt (or "ERR" if curl itself failed) and writes the body to $2.
    # --path-as-is keeps `..` unnormalised so a traversal probe reaches the server as written rather than
    # being tidied up by curl before it ever leaves this machine.
    local path="$1" body="$2" status rc
    status="$(curl -sS --path-as-is --max-time 20 -o "$body" -w '%{http_code}' "${BASE}${path}" 2>/dev/null)"
    rc=$?
    [ "$rc" -ne 0 ] && status="ERR"
    printf '%s' "$status"
}

probe() {
    local path="$1" expect_public="${2:-0}"
    local body status bytes hit verdict
    body="$(mktemp)"
    status="$(fetch "$path" "$body")"
    if [ "$status" = "ERR" ]; then
        sleep 2
        status="$(fetch "$path" "$body")"     # one retry before calling it unmeasurable
    fi
    bytes="$(wc -c < "$body" | tr -d ' ')"
    hit="$(grep -aoE "$MARKERS" "$body" 2>/dev/null | head -1)"

    if [ "$expect_public" = "1" ]; then
        # The --prove-red control: this path IS public, so the battery must call it an exposure.
        if [ "$status" = "200" ]; then
            verdict="EXPOSED (control)"; exposed=$((exposed+1))
        else
            verdict="control did not return 200 — battery unproven"; unmeasured=$((unmeasured+1))
        fi
    elif [ -n "$hit" ]; then
        verdict="⚠️ EXPOSED — content marker [$hit]"; exposed=$((exposed+1))
    else
        case "$status" in
            200|206) verdict="⚠️ EXPOSED — served ${bytes}B"; exposed=$((exposed+1)) ;;
            # ⚠️⚠️ 503 COUNTS AS REFUSED, AND THAT IS WHY A PARKED RUN PROVES LESS THAN IT LOOKS.
            #    🔬 Measured 2026-09-23 (DEPLOY 11) against the parked site: of 53 probes, **32 returned 404
            #    and 14 returned 403 — those are Apache/.htaccess refusals and hold with or without the park —
            #    while 7 returned 503**, meaning the request WAS rewritten into Laravel and answered by the
            #    maintenance page. ⚠️ Those 7 (`/app/Models/User.php`, `/app/Http/Middleware/…`,
            #    `/current/.release`, `/current/.gitignore`, `/current/.gitattributes`, and two
            #    `rel_*/app/Models/User.php`) prove NOTHING about exposure while the site is parked.
            #    ⚠️⚠️ **A BATTERY THAT RAN BEHIND MAINTENANCE PROVED THE MAINTENANCE, NOT THE EXPOSURE** —
            #    re-run unparked before trusting a 53/53, and expect those 7 to become 404s from Laravel's
            #    router rather than file contents. Until then the honest figure is **46/53 proven**.
            # ⚠️⚠️ 503 IS A THIRD VERDICT — "COULD NOT MEASURE" — NOT A PASS. Fixed 2026-09-23 (MAIL 01).
            #    An instrument needs three answers and this one had two, so it used the wrong one.
            # ⚠️⚠️ 401 IS A FOURTH VERDICT, AND IT ARRIVED THE DAY THE LOCK DID (LIVE 01, 2026-09-23).
            #    🔬 Measured: applying the private-preview lock moved EXACTLY the seven paths listed above
            #    from 503 to 401 — the same seven, because they are the ones that reach Laravel instead of
            #    being refused by the wall. ⚠️ Classing 401 as "refused" turned an honest **46 proven, 7
            #    unmeasurable** into a false **53 refused**, and the figure went UP the moment the site got
            #    SAFER, which is the tell. ⚠️⚠️ A 401 IS THE LOCK ANSWERING. IT IS NO EVIDENCE AT ALL ABOUT
            #    THE WALL — the same mistake as the 503, in a new costume, eleven hours later.
            401) verdict="UNMEASURABLE — the LOCK answered first, so what the wall does here is
                      unknown; re-run WITH credentials"; locked=$((locked+1)) ;;
            403|404) verdict="refused"; refused=$((refused+1)) ;;
            503) verdict="UNMEASURABLE — the maintenance page answered, so the app was reached but the
                      real behaviour is hidden; re-run unparked"; unmeasurable=$((unmeasurable+1)) ;;
            301|302) verdict="refused (redirect)"; refused=$((refused+1)) ;;
            ERR) verdict="UNMEASURED — no response after a retry"; unmeasured=$((unmeasured+1)) ;;
            *) verdict="UNMEASURED — unexpected status"; unmeasured=$((unmeasured+1)) ;;
        esac
    fi
    printf '  %s %-6s %-8s %s\n' "$(pad "$path")" "$status" "$bytes" "$verdict"
    rm -f "$body"
}

for p in "${PROBES[@]}"; do probe "$p" 0; done

if [ "$PROVE_RED" = "1" ]; then
    printf '  %s\n' "------------------------------------------------------------------------------------"
    printf '  %s\n' "PROVE-RED CONTROL — a path that IS public; the battery must call it an exposure:"
    probe "/robots.txt" 1
fi

printf '  %s\n' "------------------------------------------------------------------------------------"
printf '  base=%s  refused=%d  exposed=%d  UNMEASURABLE(503)=%d  UNMEASURABLE(401 lock)=%d  unmeasured=%d\n' "$BASE" "$refused" "$exposed" "$unmeasurable" "$locked" "$unmeasured"

# ⚠️ Order matters: a real exposure outranks an instrument fault, so it is reported even if some other
#    probe could not be measured. Only a clean sweep with nothing unmeasured earns exit 0.
if [ "$exposed" -gt 0 ]; then
    printf '  RESULT: ⚠️⚠️ %d EXPOSURE(S) — the wall is not holding.\n' "$exposed"
    exit 1
fi
if [ "$unmeasured" -gt 0 ]; then
    printf '  RESULT: ⚠️ %d probe(s) UNMEASURED — this is an instrument fault, NOT a clean bill of health.\n' "$unmeasured"
    exit 2
fi
if [ "$unmeasurable" -gt 0 ]; then
  printf '  RESULT: %d refused · %d UNMEASURABLE behind the maintenance page.\n' "$refused" "$unmeasurable"
  printf '  ⚠️⚠️ A BATTERY THAT RAN BEHIND MAINTENANCE PROVED THE MAINTENANCE, NOT THE EXPOSURE.\n'
  printf '  ⚠️ The %d unmeasurable probes reached Laravel rather than Apache — which for /current/.release is\n' "$unmeasurable"
  printf '     the SAFE outcome (the file was NOT served directly); do not "fix" it by breaking it.\n'
  printf '  ⚠️ They cannot be answered until the site is unparked. Re-run then.\n'
elif [ "$locked" -gt 0 ]; then
  printf '  RESULT: %d refused by the wall · %d UNMEASURABLE behind the private-preview LOCK.\n' "$refused" "$locked"
  printf '  ⚠️⚠️ A BATTERY THAT RAN BEHIND THE LOCK PROVED THE LOCK, NOT THE WALL.\n'
  printf '  ⚠️ Those %d probes are the ones that reach Laravel rather than Apache. The lock refusing them is\n' "$locked"
  printf '     the SAFE outcome and must not be "fixed" — but it is not the same claim as the wall refusing\n'
  printf '     them. Re-run WITH credentials (curl -u) to measure the wall underneath.\n'
  printf '  ⚠️ Honest figure: %d of %d proven.\n' "$refused" "$((refused + locked))"
else
  printf '  RESULT: all %d probes refused.\n' "$refused"
fi
exit 0
