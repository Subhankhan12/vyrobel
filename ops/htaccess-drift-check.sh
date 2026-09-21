#!/usr/bin/env bash
#
# ⚠️⚠️ THE DRIFT CHECK ON THE ONLY WALL THIS DEPLOYMENT HAS.
#
# ⚠️ `public_html/.htaccess` is the only thing between the public internet and `.env`, `artisan` and
# `storage/` — the document root IS the application root (KNOWN-LIMITS L12p). It is panel-owned, no deploy
# writes it, and until 2026-09-21 it existed in one place with its only backup on the same disk.
# `ops/reference/public_html.htaccess` is now the off-host record; this script checks the live file against
# the RULES that record encodes.
#
# ⚠️⚠️ IT ASSERTS THAT EACH RULE IS **PRESENT**, NOT THAT THE FILE IS BYTE-IDENTICAL — deliberately.
# The hosting panel may legitimately append its own directives, and a byte comparison would go red every
# time it did. ⚠️ **A guard that cries wolf gets muted**, and muting the guard on the only wall is a worse
# outcome than not having written it. ⚠️ So: state the rule, not the list — the same reason a ladder records
# a principle rather than an enumeration of cases.
#
# ⚠️ WHERE IT SITS. This catches a CHANGE. `ops/exposure-battery.sh` catches an EFFECT. A rule can be edited
# without anything becoming reachable, and something can become reachable without this file changing (a panel
# change, a server that stops honouring AllowOverride). ⚠️ Neither subsumes the other — which is the same
# design rule as the alarm's two independent detectors.
#
# EXIT: 0 = every rule present · 1 = at least one rule missing or the anti-rule violated · 2 = cannot run.
#
# USAGE:
#   ops/htaccess-drift-check.sh [PATH]      # default: the production path (run it ON the host, or pipe via ssh)
#   ops/htaccess-drift-check.sh --self-test [PATH]
#         ⚠️ Proves the check can FAIL, by running it against a copy with one rule deleted.
#         A guard never seen to fire is not a guard — this project has paid for that lesson three times.
set -u

FILE="$HOME/domains/vyrobel.com/public_html/.htaccess"
SELF_TEST=0
for arg in "$@"; do
    case "$arg" in
        --self-test) SELF_TEST=1 ;;
        -*) printf 'unknown argument: %s\n' "$arg" >&2; exit 2 ;;
        *) FILE="$arg" ;;
    esac
done

# ⚠️ Each rule is (label, extended-regex, why-it-matters). The WHY is not decoration: when the panel adds a
# line and somebody has to judge whether the file is still correct, the reason is what they judge against.
# A bare list of regexes would tell them what changed and nothing about whether it mattered.
RULE_LABEL=(); RULE_REGEX=(); RULE_WHY=()
add_rule() { RULE_LABEL+=("$1"); RULE_REGEX+=("$2"); RULE_WHY+=("$3"); }

add_rule "rewrite engine on" \
    '^[[:space:]]*RewriteEngine[[:space:]]+On' \
    "without it every rule below is inert and the raw directory is served"

add_rule "Options +FollowSymLinks" \
    'Options[^#]*\+FollowSymLinks' \
    "a symlink INSIDE a served tree is not traversed without it. 🔬 The first deploy attempt 404'd EVERY application route for exactly this reason"

add_rule "Options -Indexes" \
    'Options[^#]*-Indexes' \
    "otherwise Apache lists directory contents for any path with no index file"

add_rule "THE WALL: catch-all re-root under current/public" \
    'RewriteRule[[:space:]]+\^\(\.\*\)\$[[:space:]]+/current/public/\$1' \
    "⚠️⚠️ THIS IS THE ACTUAL PROTECTION, not the deny-lists. It re-roots every request under current/public/, where .env, artisan and storage do NOT live. Lose this and those files are served directly"

add_rule "the catch-all's own guard" \
    'RewriteCond[[:space:]]+%\{REQUEST_URI\}[[:space:]]+!\^/current/public' \
    "without it the rewrite re-applies to its own output and loops"

add_rule "site root straight to the front controller" \
    'RewriteRule[[:space:]]+\^\$[[:space:]]+/current/public/index\.php' \
    "rewriting / to a DIRECTORY lets Laravel's trailing-slash rule emit a 301 that the CDN then caches. 🔬 Measured: replayed ~30 min over a corrected origin"

add_rule "top-level source directories denied" \
    'RedirectMatch[[:space:]]+404[[:space:]]+\^/\(bootstrap\|config\|database\|resources\|routes\|storage\|vendor\|ops\)' \
    "defence in depth over the re-root: these names must not resolve at the top level"

add_rule "release source directories denied through /current/" \
    'RedirectMatch[[:space:]]+404[[:space:]]+\^/current/\(app\|bootstrap\|config\|database\|resources\|routes\|storage\|vendor\|ops\)' \
    "⚠️ 'current' is a real traversable symlink, so the release's own app/ IS reachable this way. This rule — and ONLY this one — must still name 'app'"

add_rule "top-level sensitive files denied" \
    'RedirectMatch[[:space:]]+404[[:space:]]+\^/\(composer\\\.\(json\|lock\)\|package\\\.json\|artisan\|vite\\\.config\\\.js\)\$' \
    "artisan is a PHP file in the document root; Apache would EXECUTE it, not serve it"

add_rule "sensitive files denied through /current/" \
    'RedirectMatch[[:space:]]+404[[:space:]]+\^/current/\(composer\\\.\(json\|lock\)\|package\\\.json\|artisan\|vite\\\.config\\\.js\)\$' \
    "the same files are reachable a second way through the release pointer"

# ⚠️⚠️ THE ANTI-RULE. `/app` is the operator back office's own URL prefix, NOT a filesystem path in the
# document root. 🔬 Measured 2026-09-21: including `app` in the TOP-LEVEL deny hard-404'd all 55+ operator
# routes at Apache, before PHP ever ran — and it looked exactly like a hardening rule doing its job.
# ⚠️ A refusal from the wrong layer is a bug wearing a guard's clothes. If someone "restores hardening" by
# putting 'app' back, the product breaks and the 404 will look correct. This is here so it goes red instead.
ANTI_REGEX='RedirectMatch[[:space:]]+404[[:space:]]+\^/\([^)]*\bapp\b'
ANTI_WHY="'app' must NOT appear in the TOP-LEVEL deny — it is the operator back office's URL prefix, not a directory in the document root. It belongs ONLY in the ^/current/ rule"

assert_file() {
    # Echoes one line per rule; returns the number of failures. $2=1 prints, $2=0 stays quiet.
    local f="$1" verbose="${2:-1}" fails=0 i
    for i in "${!RULE_LABEL[@]}"; do
        if grep -qE "${RULE_REGEX[$i]}" "$f" 2>/dev/null; then
            [ "$verbose" = "1" ] && printf '  \033[32m✓\033[0m %s\n' "${RULE_LABEL[$i]}"
        else
            fails=$((fails + 1))
            [ "$verbose" = "1" ] && { printf '  \033[31m✗\033[0m %s — MISSING\n' "${RULE_LABEL[$i]}"; printf '      why it matters: %s\n' "${RULE_WHY[$i]}"; }
        fi
    done
    # The anti-rule: presence is the failure. Strip the /current/ line first — it is allowed to name `app`.
    if grep -vE 'RedirectMatch[[:space:]]+404[[:space:]]+\^/current/' "$f" 2>/dev/null | grep -qE "$ANTI_REGEX"; then
        fails=$((fails + 1))
        [ "$verbose" = "1" ] && { printf "  \033[31m✗\033[0m ANTI-RULE VIOLATED — app is back in the top-level deny\n"; printf '      why it matters: %s\n' "$ANTI_WHY"; }
    else
        [ "$verbose" = "1" ] && printf "  \033[32m✓\033[0m anti-rule: app absent from the top-level deny (the operator app stays reachable)\n"
    fi
    return "$fails"
}

[ -r "$FILE" ] || { printf 'cannot read %s\n' "$FILE" >&2; exit 2; }
command -v grep >/dev/null 2>&1 || { echo "grep not found"; exit 2; }

printf '  file: %s (%s bytes)\n' "$FILE" "$(wc -c < "$FILE" | tr -d ' ')"
printf '  %s\n' "-------------------------------------------------------------------------------"
assert_file "$FILE" 1
FAILS=$?
printf '  %s\n' "-------------------------------------------------------------------------------"

if [ "$SELF_TEST" = "1" ]; then
    # ⚠️ Prove the check can fire: delete the single most load-bearing rule from a COPY and confirm red.
    TMP="$(mktemp)"; trap 'rm -f "$TMP"' EXIT
    grep -v 'RewriteRule[[:space:]]*\^(\.\*)\$[[:space:]]*/current/public/\$1' "$FILE" > "$TMP"
    assert_file "$TMP" 0
    SELF=$?
    if [ "$SELF" -gt "$FAILS" ]; then
        printf '  \033[32m✓\033[0m SELF-TEST: removing the catch-all re-root takes failures %d → %d. The check fires.\n' "$FAILS" "$SELF"
    else
        printf '  \033[31m✗\033[0m SELF-TEST FAILED: deleting the wall did not change the verdict (%d → %d).\n' "$FAILS" "$SELF"
        printf '      ⚠️ A guard never seen to fire is not a guard. Fix the check before trusting a green.\n'
        exit 1
    fi
fi

if [ "$FAILS" -gt 0 ]; then
    printf '  RESULT: ⚠️⚠️ %d RULE(S) MISSING OR VIOLATED — the wall has drifted.\n' "$FAILS"
    printf '  ⚠️ The canonical content is in ops/reference/public_html.htaccess. Restoring is MANUAL:\n'
    printf '     no deploy writes public_html. Copy from its BEGIN marker down, then re-run the battery.\n'
    exit 1
fi
printf '  RESULT: every rule present; the anti-rule holds.\n'
printf '  ⚠️ This says the file did not CHANGE. It does not say nothing is REACHABLE —\n'
printf '     that is ops/exposure-battery.sh, and it is a separate question.\n'
exit 0
