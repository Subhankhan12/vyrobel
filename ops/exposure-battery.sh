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
SELF_TEST=0
# ⚠️ Credentials for measuring the wall BENEATH the lock. A PATH to a netrc file, never a value:
#    a password in an env var or on a command line is visible in `ps` on shared hosting.
#      VY_BATTERY_NETRC=~/.vy-netrc ops/exposure-battery.sh
VY_BATTERY_NETRC="${VY_BATTERY_NETRC:-}"
CURL_AUTH=()
[ -n "$VY_BATTERY_NETRC" ] && CURL_AUTH=(--netrc-file "$VY_BATTERY_NETRC")
for arg in "$@"; do
    case "$arg" in
        --prove-red) PROVE_RED=1 ;;
        --self-test) SELF_TEST=1 ;;
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

served=0
app_refused=0
layer_answered=0
unmeasured=0
redirects=0

# ⚠️⚠️ "I COULD NOT MEASURE" IS NOT "THE WALL FAILED", AND CONFLATING THEM RUINS THE INSTRUMENT.
# 🔬 Measured 2026-09-21: a transient curl failure on one probe made this script report an EXPOSURE and
# exit non-zero while the wall was perfectly intact. A battery that cries wolf on a network blip gets
# re-run until it goes green, which is precisely the habit it exists to prevent. So a probe that cannot
# be measured is retried once and then reported as UNMEASURED with its own exit code (2) — ⚠️ never
# silently downgraded to green, and never dressed up as a breach.
fetch() {
    # Echoes the HTTP status for ONE attempt (or "ERR"); body to $2, RESPONSE HEADERS to $3.
    # --path-as-is keeps `..` unnormalised so a traversal probe reaches the server as written rather than
    # being tidied up by curl before it ever leaves this machine.
    local path="$1" body="$2" headers="$3" status rc
    status="$(curl -sS --path-as-is --max-time 20 ${CURL_AUTH[@]+"${CURL_AUTH[@]}"} -D "$headers" -o "$body" -w '%{http_code}' "${BASE}${path}" 2>/dev/null)"
    rc=$?
    [ "$rc" -ne 0 ] && status="ERR"
    printf '%s' "$status"
}

# ── ⚠️⚠️ SCORE BY WHICH LAYER ANSWERED, NOT BY WHICH NUMBER CAME BACK ──────────────────────────────
# ⚠️⚠️ THIS INSTRUMENT WAS FOOLED TWICE IN ELEVEN HOURS, AND BOTH TIMES THE FIX WAS "ADD A VERDICT FOR
#    THAT STATUS CODE". 🔬 First the maintenance page (503) was scored as "refused"; then the private-
#    preview lock (401) was, and the score JUMPED 46 → 53/53 **the moment the site got safer**.
#    ⚠️⚠️ **WHEN A METRIC IMPROVES WITHOUT THE THING IT MEASURES IMPROVING, THE METRIC MOVED, NOT THE
#    WORLD.** ⚠️ Collecting status codes one disappointment at a time guarantees the THIRD layer fools it
#    too, so the question is no longer "what number came back" but **"who answered"**.
#
# 🔬 THE DISCRIMINATOR, MEASURED 2026-09-23 rather than assumed: `x-powered-by: PHP/8.4.19` is present on
#    exactly the responses PHP generated (`/up` 200, `/up/liveness` 503, `/webhooks/stripe` 503) and on
#    none of the web server's own (`/.env` 403, `/artisan` 404, `/` 401). ⚠️ `content-security-policy`
#    appears on BOTH and is useless here; ⚠️ `www-authenticate` rides along even on a 200 — neither can
#    tell you who answered.
#
# ⚠️ IF `expose_php=Off` IS EVER SET, THIS MARKER VANISHES and every probe falls into LAYER-ANSWERED.
#    That understates what was proven rather than overstating it — the safe direction — and the premise
#    control below turns it into a refusal to score at all rather than a silent downgrade.
PHP_MARKER='^[Xx]-[Pp]owered-[Bb]y:[[:space:]]*PHP'

php_reached() { grep -qE "$PHP_MARKER" "$1" 2>/dev/null; }

# classify STATUS PHP_REACHED(1|0) MARKER_HIT  ->  served | app-refused | layer | redirect | unmeasured
# ⚠️ PURE: no network, no globals. That is what makes the scoring itself mutation-testable offline.
classify() {
    local status="$1" php="$2" hit="$3"
    # ⚠️ A content marker outranks any status. A 404 that still ships APP_KEY= is an exposure, and
    #    scoring it by its status code is precisely how a leak hides behind a tidy number.
    if [ -n "$hit" ]; then printf 'served'; return; fi
    case "$status" in
        200|206) printf 'served'; return ;;
        ERR)     printf 'unmeasured'; return ;;
    esac
    # ⚠️⚠️ THE WHOLE RULE, IN ONE BRANCH: if PHP did not generate this response, SOMETHING IN FRONT OF THE
    #    APPLICATION answered — the lock, the wall, the CDN, or whatever gets added next year. It proves
    #    nothing about what the application would have done, WHATEVER ITS STATUS CODE.
    if [ "$php" != "1" ]; then printf 'layer'; return; fi
    case "$status" in
        # ⚠️ PHP answered — but the maintenance page is still a layer in front of routing.
        503)                 printf 'layer' ;;
        401|403|404|405|410) printf 'app-refused' ;;
        3[0-9][0-9])         printf 'redirect' ;;
        # ⚠️ PHP returned something this instrument does not understand. That is an instrument fault,
        #    NOT a pass — the old code's `*)` fell through to a bucket and that is how drift starts.
        *)                   printf 'unmeasured' ;;
    esac
}

probe() {
    local path="$1" expect_public="${2:-0}"
    local body headers status bytes hit php verdict kind
    body="$(mktemp)"; headers="$(mktemp)"
    status="$(fetch "$path" "$body" "$headers")"
    if [ "$status" = "ERR" ]; then
        sleep 2
        status="$(fetch "$path" "$body" "$headers")"   # one retry before calling it unmeasurable
    fi
    bytes="$(wc -c < "$body" | tr -d ' ')"
    hit="$(grep -aoE "$MARKERS" "$body" 2>/dev/null | head -1)"
    php=0; php_reached "$headers" && php=1

    if [ "$expect_public" = "1" ]; then
        # The --prove-red control: this path IS public, so the battery must call it an exposure.
        if [ "$status" = "200" ]; then
            verdict="SERVED (control — the battery can go red)"; served=$((served+1))
        else
            verdict="control did not return 200 — battery unproven"; unmeasured=$((unmeasured+1))
        fi
        printf '  %s %-6s %-8s %s\n' "$(pad "$path")" "$status" "$bytes" "$verdict"
        rm -f "$body" "$headers"; return
    fi

    kind="$(classify "$status" "$php" "$hit")"
    case "$kind" in
        served)
            if [ -n "$hit" ]; then verdict="⚠️⚠️ SERVED — content marker [$hit]"
            else verdict="⚠️⚠️ SERVED — ${bytes}B"; fi
            served=$((served+1)) ;;
        app-refused)
            verdict="APPLICATION-REFUSED — PHP answered and refused (the only thing that counts as proven)"
            app_refused=$((app_refused+1)) ;;
        layer)
            if [ "$php" = "1" ]; then verdict="LAYER-ANSWERED — the maintenance page; the app was never asked"
            else verdict="LAYER-ANSWERED — something in front of PHP; proves nothing about the app"; fi
            layer_answered=$((layer_answered+1)) ;;
        redirect)
            verdict="LAYER-ANSWERED — a redirect defers the question; probe the target"
            layer_answered=$((layer_answered+1)); redirects=$((redirects+1)) ;;
        *)
            verdict="UNMEASURED — no usable response"; unmeasured=$((unmeasured+1)) ;;
    esac
    printf '  %s %-6s %-8s %s\n' "$(pad "$path")" "$status" "$bytes" "$verdict"
    rm -f "$body" "$headers"
}

# ── ⚠️⚠️ THE SELF-TEST: MUTATION-PROVE THE SCORING IN BOTH DIRECTIONS ───────────────────────────────
# ⚠️ Deleting something tests PRESENCE. Adding something tests PERMISSIVENESS. 🔬 Both of this
#    instrument's real failures were the SECOND kind — a new layer appeared and was welcomed into the
#    "refused" bucket — so a self-test that only removes things would have passed over both of them.
#    ⚠️ The 418 case below IS that failure, written down so it cannot happen a third time.
run_self_test() {
    local fails=0 got
    check() { # expect status php hit label
        got="$(classify "$2" "$3" "$4")"
        if [ "$got" = "$1" ]; then
            printf '  \033[32m✓\033[0m %-58s -> %s\n' "$5" "$got"
        else
            printf '  \033[31m✗\033[0m %-58s -> %s (expected %s)\n' "$5" "$got" "$1"; fails=$((fails+1))
        fi
    }
    printf '  %s\n' "SELF-TEST — the scoring, against synthetic responses (no network)"
    printf '  %s\n' "------------------------------------------------------------------------------------"
    check app-refused 404 1 ""    "404 WITH php marker            = Laravel's router refused"
    check app-refused 403 1 ""    "403 WITH php marker            = the app refused"
    check layer       401 0 ""    "401 no php marker              = the private-preview LOCK"
    check layer       503 1 ""    "503 WITH php marker            = the maintenance page"
    check layer       404 0 ""    "404 no php marker              = the web server's wall"
    check layer       418 0 ""    "⚠️ 418 from a LAYER THAT DOES NOT EXIST YET"
    check unmeasured  418 1 ""    "418 WITH php marker            = PHP said something unknown"
    check served      200 1 ""    "200                            = served"
    check served      200 0 ""    "200 from a layer               = still served"
    check served      404 1 "APP_KEY=" "⚠️ 404 that still ships APP_KEY= (content beats status)"
    check unmeasured  ERR 0 ""    "curl failed                    = instrument fault, never a pass"
    printf '  %s\n' "------------------------------------------------------------------------------------"
    if [ "$fails" -gt 0 ]; then
        printf '  \033[31m✗\033[0m SELF-TEST FAILED (%d). Do not trust a score from this run.\n' "$fails"
        return 1
    fi
    printf '  \033[32m✓\033[0m SELF-TEST PASSED — a new layer lands in LAYER-ANSWERED, never in refused.\n'
    return 0
}

if [ "$SELF_TEST" = "1" ]; then
    run_self_test || exit 1
    printf '\n'
fi

# ── ⚠️⚠️ THE PREMISE CONTROL: CAN THIS INSTRUMENT SEE PHP AT ALL? ───────────────────────────────────
# ⚠️ Every verdict below rests on one assumption — that a PHP-generated response is recognisable. If
#    `expose_php=Off` is ever set, or the exemption that makes this path reachable is removed, that
#    assumption fails SILENTLY and every probe becomes "layer-answered". ⚠️⚠️ AN INSTRUMENT MUST CHECK
#    ITS OWN PREMISES; this one refuses to score rather than report a confident nothing.
PREMISE_PATH="${VY_BATTERY_PREMISE:-/up}"
_pb="$(mktemp)"; _ph="$(mktemp)"
_ps="$(fetch "$PREMISE_PATH" "$_pb" "$_ph")"
if php_reached "$_ph"; then
    printf '  premise: %s -> %s, carries a PHP marker. The PHP/not-PHP discriminator works.\n' "$PREMISE_PATH" "$_ps"
    rm -f "$_pb" "$_ph"
else
    printf '  \033[31m✗\033[0m PREMISE FAILED: %s -> %s carries NO PHP marker.\n' "$PREMISE_PATH" "$_ps"
    printf '      ⚠️⚠️ This instrument cannot tell a PHP response from a layer in front of it, so every\n'
    printf '      verdict would read LAYER-ANSWERED and the run would prove nothing while looking calm.\n'
    printf '      Check expose_php, or that %s is still reachable, before trusting any score.\n' "$PREMISE_PATH"
    rm -f "$_pb" "$_ph"
    exit 2
fi

printf '  %-52s %-6s %-8s %s\n' "PATH" "HTTP" "BYTES" "VERDICT"
printf '  %s\n' "------------------------------------------------------------------------------------"
for p in "${PROBES[@]}"; do probe "$p" 0; done

if [ "$PROVE_RED" = "1" ]; then
    printf '  %s\n' "------------------------------------------------------------------------------------"
    printf '  %s\n' "PROVE-RED CONTROL — a path that IS public; the battery must call it an exposure:"
    probe "/robots.txt" 1
fi

printf '  %s\n' "------------------------------------------------------------------------------------"
printf '  base=%s\n' "$BASE"
printf '  APPLICATION-REFUSED=%d   LAYER-ANSWERED=%d   SERVED=%d   UNMEASURED=%d\n' \
    "$app_refused" "$layer_answered" "$served" "$unmeasured"
[ "$redirects" -gt 0 ] && printf '  (of the layer-answered, %d were redirects — the question was deferred, not answered)\n' "$redirects"
[ -n "$VY_BATTERY_NETRC" ] && printf '  (run WITH credentials from %s — the lock was not in the way)\n' "$VY_BATTERY_NETRC"

# ⚠️ Order matters: a real exposure outranks an instrument fault, so it is reported even when some other
#    probe could not be measured.
if [ "$served" -gt 0 ]; then
    printf '  RESULT: ⚠️⚠️ %d PROBE(S) SERVED — something is reachable that must not be.\n' "$served"
    exit 1
fi
if [ "$unmeasured" -gt 0 ]; then
    printf '  RESULT: ⚠️ %d probe(s) UNMEASURED — an instrument fault, NOT a clean bill of health.\n' "$unmeasured"
    exit 2
fi
# ⚠️⚠️ GREEN MUST NOT BE REACHABLE WITHOUT MEASURING. 🔬 Every run in this battery's life before
#    2026-09-23 was answered by a 503 or a 401 — a layer in front — and every one of them reported a
#    confident score. If the application refused NOTHING, the application was never asked.
if [ "$app_refused" -eq 0 ]; then
    printf '  RESULT: ⚠️⚠️ NOTHING WAS MEASURED. All %d probes were answered by a layer in front of the\n' "$layer_answered"
    printf '          application, so this run says nothing whatever about exposure.\n'
    printf '  ⚠️ Nothing was SERVED, which is good news about the layers and no news about the wall.\n'
    printf '  ⚠️ To measure: lift maintenance, and/or re-run with VY_BATTERY_NETRC so the lock is not\n'
    printf '     in the way. Until then this is not a gate, it is a weather report.\n'
    exit 2
fi
printf '  RESULT: %d probe(s) refused BY THE APPLICATION; %d never reached it.\n' "$app_refused" "$layer_answered"
if [ "$layer_answered" -gt 0 ]; then
    printf '  ⚠️ The %d layer-answered probes are NOT proven — a layer in front replied for them, and\n' "$layer_answered"
    printf '     that is unmeasurable whatever its status code was.\n'
fi
printf '  ⚠️ This says what is REACHABLE. It does not say the rules did not CHANGE —\n'
printf '     that is ops/htaccess-drift-check.sh, and it is a separate question.\n'
exit 0
