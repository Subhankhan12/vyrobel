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
            401|403|404|503) verdict="refused"; refused=$((refused+1)) ;;
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
printf '  base=%s  refused=%d  exposed=%d  unmeasured=%d\n' "$BASE" "$refused" "$exposed" "$unmeasured"

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
printf '  RESULT: all %d probes refused.\n' "$refused"
exit 0
