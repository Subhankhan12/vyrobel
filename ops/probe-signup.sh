#!/usr/bin/env bash
#
# ops/probe-signup.sh <LABEL> — ⚠️ a REAL sign-up against production, to prove the public front door accepts one
# after a deploy (every kill switch defaults ON; a switch that came back OFF would be an outage nobody sees).
#
# ⚠️ It creates a real, clearly-named DISPOSABLE organisation: "<LABEL> PROBE (not a customer)", with an address
#    at the reserved `.invalid` TLD (RFC 2606 — undeliverable by definition, and mail is not delivered anyway).
# ⚠️ The org is then READ BACK on the host by its name, its id is READ (never typed), and it is marked
#    `is_internal` there with an audited ledger row — so no platform statistic ever counts it as a customer.
# ⚠️ Nothing is deleted: there is no hard delete and no prune (both are the founder's ruling).
set -euo pipefail

LABEL="${1:?usage: probe-signup.sh <LABEL e.g. ADMIN-P1>}"
BASE="${2:-https://vyrobel.com}"
STAMP="$(date -u +%Y%m%d-%H%M%S)"
SLUGBIT="$(echo "$LABEL" | tr 'A-Z' 'a-z' | tr -c 'a-z0-9' '-')"
ORG="${LABEL} PROBE (not a customer)"
EMAIL="${SLUGBIT}probe-${STAMP}@vyrobel-probe.invalid"
PW="Probe-$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 20)1a"   # never printed, never stored by us
JAR="$(mktemp)"; trap 'rm -f "$JAR"' EXIT

page="$(curl -sS -c "$JAR" -b "$JAR" -A 'vyrobel-probe-signup/1.0' "$BASE/signup")"
token="$(echo "$page" | grep -oE 'name="_token" value="[^"]+"' | head -1 | sed -E 's/.*value="([^"]+)"/\1/')"
[ -n "$token" ] || { echo "  ✗ no CSRF token on /signup — the form did not render (paused? CDN challenge?)"; exit 1; }

code="$(curl -sS -o /dev/null -w '%{http_code}|%{redirect_url}' -c "$JAR" -b "$JAR" -A 'vyrobel-probe-signup/1.0' \
    --data-urlencode "_token=$token" --data-urlencode "org_name=$ORG" --data-urlencode "location=Probe" \
    --data-urlencode "property_type=hotel" --data-urlencode "currency=EUR" --data-urlencode "property_name=${LABEL} Probe House" \
    --data-urlencode "name=${LABEL} Probe" --data-urlencode "email=$EMAIL" \
    --data-urlencode "password=$PW" --data-urlencode "password_confirmation=$PW" \
    "$BASE/signup")"
echo "  POST /signup → ${code%%|*} → ${code#*|}"
case "$code" in 302\|*/app/portfolio*) echo "  ✓ the sign-up was accepted";; *) echo "  ✗ the sign-up was NOT accepted"; exit 1;; esac

echo "  org name to read back: $ORG"
echo "  email: $EMAIL"
