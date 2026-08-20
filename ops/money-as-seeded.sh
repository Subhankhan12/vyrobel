#!/usr/bin/env bash
#
# ops/money-as-seeded.sh — THE MONEY BASELINE GATE.
#
# ⚠️ WHAT THIS IS FOR. Every build pass in this project ends by re-measuring the demo money and confirming
# it is byte-identical to the baseline. That check has always been run by hand and reported in prose. This
# script is the one definition of it, so CI can fail a build the moment a change moves the demo money —
# and so the founder can run the identical check before a release.
#
# ⚠️ SUMS AND COUNTS, NOT ROW IDs. The baseline is six aggregates, in this order:
#     rate_calendar rows / SUM(price_minor) / unit_rate_overrides / reservations / payments / rate_seasons
# ⚠️ The third figure is `unit_rate_overrides` — NOT `rate_calendar.override_reason`, which does not exist
# (§N.30.2 records the probe that read a phantom column and returned 0 unfailably).
#
# ⚠️ IT REQUIRES A FRESHLY SEEDED DATABASE. Run `php artisan migrate:fresh --seed` first — on a database
# you are willing to destroy. It NEVER seeds by itself: doing that against the wrong DB_DATABASE would
# drop it. Production is never seeded at all (§4).
#
# Usage:  php artisan migrate:fresh --seed && ops/money-as-seeded.sh
# Exit:   0 = the demo money is unchanged. 1 = it moved (the figures are printed side by side).

set -uo pipefail

# The sealed baseline. Change these ONLY together with a recorded decision explaining what moved and why.
EXPECTED="929 / 10912000 / 0 / 41 / 16 / 0"

ACTUAL="$(php artisan tinker --execute='
    echo DB::table("rate_calendar")->count()
        ." / ".(int) DB::table("rate_calendar")->sum("price_minor")
        ." / ".DB::table("unit_rate_overrides")->count()
        ." / ".DB::table("reservations")->count()
        ." / ".DB::table("payments")->count()
        ." / ".DB::table("rate_seasons")->count();
' 2>/dev/null | tr -d '\r' | grep -E '^[0-9]+ / ' | tail -1)"

if [ -z "${ACTUAL}" ]; then
  echo "❌ Could not measure the demo money — the query returned nothing."
  echo "   A gate that cannot measure has NOT passed. Seed the database first:"
  echo "     php artisan migrate:fresh --seed"
  exit 1
fi

echo "  expected:  ${EXPECTED}"
echo "  actual:    ${ACTUAL}"
echo "             (rate_calendar / SUM(price_minor) / unit_rate_overrides / reservations / payments / rate_seasons)"

if [ "${ACTUAL}" = "${EXPECTED}" ]; then
  echo "✅ Money as seeded — unchanged."
  exit 0
fi

echo "❌ THE DEMO MONEY MOVED. This is not automatically a bug, but it is never incidental:"
echo "   either a seeder changed, or something now writes money that did not before."
echo "   Establish which before merging, then update the baseline in this file WITH the reason."
exit 1
