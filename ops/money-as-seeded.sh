#!/usr/bin/env bash
#
# ops/money-as-seeded.sh — THE MONEY BASELINE GATE.
#
# ⚠️ WHAT THIS IS FOR. It is a tripwire for one question: *did this pass change money data?* Every build pass
# ends by re-measuring the demo money against a hand-written baseline, so CI can fail the moment a change
# moves it, and the founder can run the identical check before a release.
#
# ⚠️ SIX AGGREGATES, in this order:
#     rate_calendar rows / SUM(price_minor) / unit_rate_overrides / reservations / payments / rate_seasons
# ⚠️⚠️ PLUS, IN FULL MODE ONLY, A ROW-LEVEL DIGEST over four money tables (F-62, added FIX 57) — see the
#     EXPECTED_DIGEST block below for exactly how it is computed and how to regenerate it by hand.
# ⚠️ The third figure is `unit_rate_overrides` — NOT `rate_calendar.override_reason`, which does not exist
# (§N.30.2 records the probe that read a phantom column and returned 0 unfailably).
#
# ══ ⚠️⚠️ THE DATE ASSUMPTION, STATED — because it was UNSTATED and the gate red on its own ═══════════════
#
# ⚠️ THE DEMO SEEDER IS DELIBERATELY DATE-RELATIVE, AND THAT IS CORRECT. `DemoSeeder::showcaseMonth()` keeps
# the demo's stays genuinely upcoming on any run day; anchoring it to a constant date would leave a prospect
# opening a storefront full of bookings that already happened. The seeder's own docblock recorded the
# consequence honestly: *"the demo's exact money figures depend on the run day … it is simply no longer a
# constant."*
#
# ⚠️⚠️ THE CHECK WAS WHAT DISAGREED. This file pinned all six figures as constants. MEASURED across seven
# seed dates (each in its OWN process, after a batch probe was caught producing an artefact):
#
#     seeded on     rate_cal   SUM(minor)   ovr  resv  pay  seas
#     2026-08-22         929   10,912,000     0    40   16     0
#     2026-09-01         929   10,928,000     0    42   16     0
#     2026-10-15         929   10,920,000     0    41   16     0
#     2026-12-01         929   10,926,000     0    42   16     0
#     2027-01-31         929   10,912,000     0    42   16     0
#     2027-02-14         929   10,916,000     0    42   16     0
#     2027-06-30         929   10,912,000     0    42   16     0
#
#   • DATE-INVARIANT: rate_calendar COUNT (a fixed 180-day window) · unit_rate_overrides · payments ·
#     rate_seasons. These may be asserted unconditionally.
#   • ⚠️ DATE-DEPENDENT: SUM(price_minor) — the weekend uplift means a window starting on a different weekday
#     holds a different mix of Fri/Sat — and the RESERVATION COUNT, because the analytics-history seeder skips
#     a stay when it collides with existing demo inventory, and whether it collides depends on the run day.
#   ⚠️ Note 10,912,000 recurs on three unrelated dates: the old pin passed by COINCIDENCE as often as by
#     correctness, which is why "it has always been green" was never evidence.
#
# ⚠️ SO THE GATE NOW HAS TWO MODES, and says which one it ran:
#
#   FULL       — the database was seeded at the REFERENCE DATE below. All six figures are asserted.
#                Usage:  export VY_SEED_DATE=2026-09-01; php artisan migrate:fresh --seed; ops/money-as-seeded.sh
#
#   INVARIANT  — seeded on some other day (the normal developer case). Only the four date-invariant figures
#                are asserted, and the gate SAYS what it could not check.
#                ⚠️ WHAT INVARIANT MODE STOPS CATCHING: a changed RATE PRICE (that moves SUM) and a changed
#                BOOKING SET (that moves the reservation count). Those are exactly the changes most worth
#                catching — so ⚠️ **CI must run FULL mode.** Invariant mode is a developer convenience, and
#                it is deliberately loud about being a weaker check.
#
# ⚠️⚠️ WHAT A GREEN RUN OF THIS GATE DOES AND DOES NOT MEAN.
#
#   • ✅ F-62 — CLOSED IN FULL MODE (FIX 57). ⚠️ It used to compare COUNTS AND SUMS ONLY, so an
#     equal-and-opposite pair of errors netted to zero and passed: a row priced 1,000 too high beside one
#     priced 1,000 too low left SUM(price_minor) exact and this gate silent. 🔬 That was DEMONSTRATED in
#     tenant three (never tenant one) before it was fixed. FULL mode now also asserts a ROW-LEVEL DIGEST
#     over rate_calendar, reservations, payments AND owner_ledger, so a netting pair or a reattributed row
#     now FAILS the gate. ⚠️⚠️ INVARIANT MODE DOES NOT CHECK IT — the digest is date-dependent.
#
#   • ⚠️⚠️ AND THE CLAIM IS STILL SCOPED, WHICH IS WHY THE SUCCESS LINE SPELLS IT OUT. FIVE money tables are
#     read by NO instrument at all — folios, folio_items, owner_statements, owner_expenses, subscriptions —
#     and a digest sees the AMOUNT AT A KEY, not every other column on the row. The honest claim is
#     "tenant one's six aggregates are as seeded AND its four covered money tables are row-identical",
#     never "the money is unchanged".
#
#   • F-61 — ⚠️⚠️ RUNNING THIS GATE IN FULL MODE PUTS TENANT TWO'S ENTIRE OPERATOR BACK OFFICE BEHIND THE
#     PAYWALL. Tenant two is seeded with a 14-day trial, so seeding at REFERENCE_DATE expires it on
#     2026-09-15; at any real present date EnsureSubscriptionActive correctly holds every /app/* route at
#     /app/billing. ⚠️ THAT IS INVARIANT #41 WORKING — it is not a bug and it must not be "fixed".
#     ⚠️⚠️ DO NOT EXTEND THE TRIAL, MOVE THE CLOCK, OR RE-SEED AT A LATER DATE TO MAKE A JOURNEY WALKABLE.
#     That is re-baselining a fixture to make a check pass. Tenant two's OWNER portal is not subscription
#     gated and stays reachable. See docs/KNOWN-LIMITS.md L8.
#
# ⚠️ THE EXPECTATIONS BELOW ARE HAND-MEASURED CONSTANTS, NOT COMPUTED FROM THE SEEDER. A pin derived from
# the seeder's own logic would verify the seeder against itself and pass through any seeder change — the
# same source-against-itself failure the `/pricing` comparison guard shipped (§N.31.1 shape 8).
#
# ⚠️ IT REQUIRES A FRESHLY SEEDED DATABASE and NEVER seeds by itself: doing that against the wrong
# DB_DATABASE would drop it. Production is never seeded at all (DEPLOYMENT §4).
#
# Exit: 0 = unchanged. 1 = it moved (the figures are printed side by side).

set -uo pipefail

# ⚠️ THE REFERENCE DATE. Changing it means re-measuring EXPECTED_FULL at the new date, by hand.
REFERENCE_DATE="2026-09-01"

# The sealed baselines. Change these ONLY together with a recorded decision explaining what moved and why.
#
# ⚠️ RE-PINNED 2026-08-23, and every changed number is attributed:
#   • The previous pin was `929 / 10912000 / 0 / 41 / 16 / 0`, measured on an unrecorded day.
#   • SUM 10,912,000 → 10,928,000 and reservations 41 → 42 are the REFERENCE DATE, not a code change: both
#     figures are date-dependent (table above) and the pin now names the day it was taken on.
#   • ⚠️ The demo's September owner statement separately moved (gross 100,000 → 46,000, net 82,000 → 37,720)
#     under the collection gate in `d94452f` — 54,000 of demo September comes from folios that were never
#     paid, so it is correctly held rather than payable. That is the gate WORKING, and it is not one of the
#     six figures below; it is recorded in docs/audits/OWNER-COLLECTION-GATE-2026-08-23.md §7.
EXPECTED_FULL="929 / 10928000 / 0 / 42 / 16 / 0"
EXPECTED_INVARIANT="929 / 0 / 16 / 0"

# ══ ⚠️⚠️ THE ROW DIGEST — F-62, ADDED FIX 57. ⚠️ THIS IS A PIN; EVERY RULE ABOUT PINS APPLIES. ═══════════
#
# ⚠️⚠️ WHY IT EXISTS. The six aggregates above are COUNTS AND SUMS, so an equal-and-opposite pair of errors
# nets to zero and passes. 🔬 DEMONSTRATED (FIX 56, in tenant three, never tenant one): a +1000 / -1000 pair
# left `180 / 2214000 / 0 / 0 / 0 / 0` BYTE-IDENTICAL while a row digest moved. The aggregates cannot see it.
#
# ⚠️⚠️ AND WHY `owner_ledger` IS HERE THOUGH IT IS NOT ONE OF THE SIX: the failure this exists to catch — a
# credit moved from one owner to another — LIVES THERE, and no version of this gate had ever read that table.
# Its only other instrument is the suite pin (count + sum), which is blind to exactly the same swap. A digest
# over the other three would have closed a class that does not contain its own motivating example.
#
# ── ⚠️ HOW TO REGENERATE IT BY HAND. Four tables; for each: build the KEY below, append "=" and the integer
#    amount, SORT THE RESULTING LINES AS STRINGS (⚠️ the sort order is part of the pin — an unstable ORDER BY
#    makes this move for no reason, and a gate that cries wolf is a gate that gets skipped), join with "\n",
#    sha256, take the FIRST 16 HEX CHARACTERS. Then join the four with " / ", in this order:
#
#      rate_calendar  KEY = property.slug | room_type.code | rate_plan.name | rate_calendar.date
#                     AMT = rate_calendar.price_minor
#      reservations   KEY = property.slug | check_in | check_out | status | source
#                     AMT = reservations.total_minor
#      payments       KEY = idempotency_key | property.slug | check_in | check_out | status | source
#                           | payments.type | payments.status          (reached via folio -> reservation)
#                     AMT = payments.amount_minor
#      owner_ledger   KEY = owner.name | unit.code | property.slug | check_in | check_out | entry_type
#                           | direction | service_date | description
#                     AMT = owner_ledger.amount_minor
#
# ⚠️⚠️ EVERY COMPONENT IS COALESCED TO `CHAR(126)` (a tilde) AND JOINED WITH `CHAR(124)` (a pipe), AND THAT
#    IS LOAD-BEARING, NOT STYLE: MySQL `CONCAT()` RETURNS **NULL IF ANY ARGUMENT IS NULL**. 🔬 Caught while
#    building this — `payments.method` is NULL on all 16 rows, so an un-coalesced CONCAT collapsed all
#    sixteen keys to ONE and the digest went maximally blind while looking like it worked. ⚠️ `CHAR(n)` is
#    used rather than a quoted literal only because this block is a single-quoted shell string.
#
# ⚠️ AND THE BLOCK REFUSES RATHER THAN GUESSING: if a key is not unique across its table it prints
#    KEY-COLLISION and the gate FAILS. A colliding key silently merges two rows, which would blind the
#    digest exactly where it matters most.
#
# ⚠️⚠️ SCOPE: TENANT ONE (`vyrobel-demo`), like every other figure in this file.
# ⚠️ DATE-DEPENDENT, so it is asserted in FULL MODE ONLY — the stay dates and the 180-day rate window move
#    with the seed date, so the digest legitimately differs at any other date.
# 🔬 Re-proven FIX 57 across two `migrate:fresh --seed` runs at the reference date: all four keys UNIQUE
#    (929/929, 42/42, 16/16, 29/29), zero null keys, and all four digests IDENTICAL both times.
EXPECTED_DIGEST="a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297"

# ⚠️⚠️ SCOPED TO TENANT ONE (`vyrobel-demo`) — FIX 17, 2026-09-14. READ THIS BEFORE CHANGING IT.
#
# The product gained a SECOND demo organisation. That moves every GLOBAL count in this file, and there were
# two honest responses: re-pin the six figures, or scope them to the organisation they have always described.
#
# ⚠️ SCOPING WAS CHOSEN, and the reason matters more than the choice:
#   • The six figures keep their EXACT values. Fifteen audit phases and sixteen fix passes quoted
#     `929 / 10928000 / 0 / 42 / 16 / 0`; every one of those reports stays comparable. A re-pin would make
#     each historical quotation ambiguous — "which baseline was that?" — for no gain.
#   • This gate's subject has always been THE PINNED DEMO MONEY, which is tenant one's. A second tenant's
#     money has never been verified by anybody, so folding it into the assertion would make the gate assert
#     something nobody has checked.
#   • A re-pin is for when the guarded thing legitimately CHANGED (as `model_has_roles` 7 → 14 did, when the
#     roles themselves changed). Tenant one's money did not change at all here; the database merely gained an
#     unrelated neighbour. Re-baselining to accommodate a neighbour is how a gate stops meaning anything.
#   • Scoping is strictly TIGHTER, not looser: it still fails on any change to exactly what it always guarded.
#
# ⚠️ THE COST, STATED PLAINLY: this gate no longer sees tenant two's money. That is deliberate — tenant two
#   exists to make the money-write routes exercisable WITHOUT risking anything pinned (⚠️ how many there are
#   is UNKNOWN and enumerated nowhere — F-59; this line used to say "seventeen"). Its figures
#   are PRINTED below as RECORDED, never asserted, so a reader can see them and never mistake them for a pin.
#
# ⚠️ Resolved by SLUG, not by id: organisation ids are ULIDs regenerated on every `migrate:fresh --seed`.
MEASURE='
    $t1 = DB::table("organizations")->where("slug", "vyrobel-demo")->value("id");
    if ($t1 === null) { echo "NO-TENANT-ONE"; return; }
    echo DB::table("rate_calendar")->where("organization_id", $t1)->count()
        ." / ".(int) DB::table("rate_calendar")->where("organization_id", $t1)->sum("price_minor")
        ." / ".DB::table("unit_rate_overrides")->where("organization_id", $t1)->count()
        ." / ".DB::table("reservations")->where("organization_id", $t1)->count()
        ." / ".DB::table("payments")->where("organization_id", $t1)->count()
        ." / ".DB::table("rate_seasons")->where("organization_id", $t1)->count();
'

# RECORDED, NEVER ASSERTED — every other organisation's equivalent figures, so they are visible and cannot be
# confused with the pin above.
MEASURE_OTHERS='
    $t1 = DB::table("organizations")->where("slug", "vyrobel-demo")->value("id");
    $rows = DB::table("organizations")->where("slug", "!=", "vyrobel-demo")->get(["id","slug"]);
    foreach ($rows as $o) {
        echo "OTHER ".$o->slug.": "
            .DB::table("rate_calendar")->where("organization_id",$o->id)->count()." / "
            .(int) DB::table("rate_calendar")->where("organization_id",$o->id)->sum("price_minor")." / "
            .DB::table("unit_rate_overrides")->where("organization_id",$o->id)->count()." / "
            .DB::table("reservations")->where("organization_id",$o->id)->count()." / "
            .DB::table("payments")->where("organization_id",$o->id)->count()." / "
            .DB::table("rate_seasons")->where("organization_id",$o->id)->count().PHP_EOL;
    }
'

MEASURE_DIGEST='
    $t1 = DB::table("organizations")->where("slug", "vyrobel-demo")->value("id");
    if ($t1 === null) { echo "NO-TENANT-ONE"; return; }
    $specs = [
        "rate_calendar" => DB::table("rate_calendar as rc")
            ->join("rate_plans as rp", "rp.id", "=", "rc.rate_plan_id")
            ->join("room_types as rt", "rt.id", "=", "rp.room_type_id")
            ->join("properties as p", "p.id", "=", "rt.property_id")
            ->where("rc.organization_id", $t1)
            ->select(DB::raw("CONCAT(COALESCE(p.slug,CHAR(126)),CHAR(124),COALESCE(rt.code,CHAR(126)),CHAR(124),COALESCE(rp.name,CHAR(126)),CHAR(124),COALESCE(rc.date,CHAR(126))) as k"), DB::raw("rc.price_minor as amt")),
        "reservations" => DB::table("reservations as r")
            ->join("properties as p", "p.id", "=", "r.property_id")
            ->where("r.organization_id", $t1)
            ->select(DB::raw("CONCAT(COALESCE(p.slug,CHAR(126)),CHAR(124),COALESCE(r.check_in,CHAR(126)),CHAR(124),COALESCE(r.check_out,CHAR(126)),CHAR(124),COALESCE(r.status,CHAR(126)),CHAR(124),COALESCE(r.source,CHAR(126))) as k"), DB::raw("r.total_minor as amt")),
        "payments" => DB::table("payments as pay")
            ->leftJoin("folios as f", "f.id", "=", "pay.folio_id")
            ->leftJoin("reservations as r", "r.id", "=", "f.reservation_id")
            ->leftJoin("properties as p", "p.id", "=", "r.property_id")
            ->where("pay.organization_id", $t1)
            ->select(DB::raw("CONCAT(COALESCE(pay.idempotency_key,CHAR(126)),CHAR(124),COALESCE(p.slug,CHAR(126)),CHAR(124),COALESCE(r.check_in,CHAR(126)),CHAR(124),COALESCE(r.check_out,CHAR(126)),CHAR(124),COALESCE(r.status,CHAR(126)),CHAR(124),COALESCE(r.source,CHAR(126)),CHAR(124),COALESCE(pay.type,CHAR(126)),CHAR(124),COALESCE(pay.status,CHAR(126))) as k"), DB::raw("pay.amount_minor as amt")),
        "owner_ledger" => DB::table("owner_ledger as ol")
            ->join("owners as o", "o.id", "=", "ol.owner_id")
            ->leftJoin("units as u", "u.id", "=", "ol.unit_id")
            ->leftJoin("reservations as r", "r.id", "=", "ol.reservation_id")
            ->leftJoin("properties as p", "p.id", "=", "r.property_id")
            ->where("ol.organization_id", $t1)
            ->select(DB::raw("CONCAT(COALESCE(o.name,CHAR(126)),CHAR(124),COALESCE(u.code,CHAR(126)),CHAR(124),COALESCE(p.slug,CHAR(126)),CHAR(124),COALESCE(r.check_in,CHAR(126)),CHAR(124),COALESCE(r.check_out,CHAR(126)),CHAR(124),COALESCE(ol.entry_type,CHAR(126)),CHAR(124),COALESCE(ol.direction,CHAR(126)),CHAR(124),COALESCE(ol.service_date,CHAR(126)),CHAR(124),COALESCE(ol.description,CHAR(126))) as k"), DB::raw("ol.amount_minor as amt")),
    ];
    $out = [];
    foreach ($specs as $name => $q) {
        $rows = $q->get();
        $lines = $rows->map(function ($r) { return $r->k . "=" . (int) $r->amt; })->all();
        if (count(array_unique($rows->pluck("k")->all())) !== count($lines)) { echo "KEY-COLLISION:" . $name; return; }
        sort($lines, SORT_STRING);
        $out[] = substr(hash("sha256", implode("\n", $lines)), 0, 16);
    }
    echo implode(" / ", $out);
'

ACTUAL="$(php artisan tinker --execute="${MEASURE}" 2>/dev/null | tr -d '\r' | grep -E '^[0-9]+ / ' | tail -1)"

if [ -z "${ACTUAL}" ]; then
  echo "❌ Could not measure the demo money — the query returned nothing."
  echo "   A gate that cannot measure has NOT passed. Seed the database first:"
  echo "     export VY_SEED_DATE=${REFERENCE_DATE}; php artisan migrate:fresh --seed"
  echo "   ⚠️ EXPORT it — the check re-reads it, so a one-command prefix leaves this script"
  echo "      running the weaker INVARIANT mode without you asking for it."
  exit 1
fi

# ⚠️⚠️ DID THE SEED ACTUALLY FINISH? A GATE THAT CANNOT TELL "MONEY MOVED" FROM "THE SEED ABORTED" WILL
# BLAME CODE FOR AN INFRASTRUCTURE FAILURE — and it did exactly that on 2026-08-23, when
# `migrate:fresh --seed` began failing with a UnitUnavailableException and left a PARTIAL database (749
# rate rows instead of 929). Every figure was then "wrong", and the gate's message pointed at the last code
# change, which was innocent.
#
# ⚠️ `test@example.com` is the LAST row DatabaseSeeder::run() creates, after every other seeder. Its absence
# means the run aborted part-way. That is a sentinel for COMPLETION, not a money figure — it is deliberately
# checked before, and separately from, the baseline comparison.
SEED_COMPLETE="$(php artisan tinker --execute='echo DB::table("users")->where("email","test@example.com")->exists() ? "yes" : "no";' 2>/dev/null | tr -d '\r' | grep -E '^(yes|no)$' | tail -1)"

if [ "${SEED_COMPLETE}" = "no" ]; then
  echo "❌ THE SEED DID NOT COMPLETE — this is NOT a money finding."
  echo "   DatabaseSeeder's final row (test@example.com) is missing, so the run aborted part-way and the"
  echo "   database is PARTIAL. Comparing a partial database against the baseline would blame whatever code"
  echo "   changed last for a seeding failure."
  echo "   Re-run and read the error:  php artisan migrate:fresh --seed"
  exit 1
fi

# The four date-invariant figures, in order: rate_calendar count / overrides / payments / seasons.
ACTUAL_INVARIANT="$(echo "${ACTUAL}" | awk -F ' / ' '{print $1" / "$3" / "$5" / "$6}')"

OTHERS="$(php artisan tinker --execute="${MEASURE_OTHERS}" 2>/dev/null | tr -d '\r' | grep -E '^OTHER ' || true)"

# ⚠️ THE ROW DIGEST (F-62). Captured alongside the aggregates; asserted in FULL mode only.
ACTUAL_DIGEST="$(php artisan tinker --execute="${MEASURE_DIGEST}" 2>/dev/null | tr -d '\r' | grep -E '^([0-9a-f]{16} / |KEY-COLLISION:|NO-TENANT-ONE)' | tail -1)"

case "${ACTUAL_DIGEST}" in
  KEY-COLLISION:*)
    echo "❌ THE ROW DIGEST CANNOT BE TRUSTED — a natural key is NOT UNIQUE in ${ACTUAL_DIGEST#KEY-COLLISION:}."
    echo "   A colliding key merges two rows, so the digest would go blind exactly where it matters."
    echo "   ⚠️ This is a SCHEMA or SEED change, not a money finding. Fix the key in this file, do not widen it"
    echo "      by adding a volatile column (ids and generated references regenerate on every seed)."
    exit 1 ;;
  "")
    echo "❌ THE ROW DIGEST COULD NOT BE MEASURED — the query returned nothing. A gate that cannot measure"
    echo "   has NOT passed. Seed the database first (see above)."
    exit 1 ;;
esac
echo "  scope:     ⚠️ TENANT ONE (vyrobel-demo) ONLY — 1 of 3 organisations. See the note in this file."
if [ -n "${OTHERS}" ]; then
  echo "${OTHERS}" | sed 's/^/             📄 RECORDED, NOT ASSERTED — /'
fi
echo "             (rate_calendar / SUM(price_minor) / unit_rate_overrides / reservations / payments / rate_seasons)"
echo "  blind to:  ⚠️ (1) EVERY OTHER TENANT — northgate-demo-city-rooms AND harbourpoint-demo-walkthrough."
echo "             ⚠️ (2) FIVE MONEY TABLES NO INSTRUMENT READS AT ALL: folios, folio_items,"
echo "                    owner_statements, owner_expenses, subscriptions."
echo "             ⚠️ (3) COLUMNS OUTSIDE EACH TABLE-KEY-AND-AMOUNT: a digest sees the amount at a key, not"
echo "                    every other column on the row (reservations 28, payments 15, rate_calendar 3)."
echo "             ⚠️ (4) F-61 — harbourpoint IS THE WRITABLE ONE and is EXPECTED to move. Never pin it."
echo "             ⚠️ F-62 IS NO LONGER ON THIS LIST IN FULL MODE — the row digest below closed it for the"
echo "                four covered tables. ⚠️ In INVARIANT mode it still applies: the digest is NOT checked."
echo "  actual:    ${ACTUAL}"
echo "  digest:    ${ACTUAL_DIGEST}"
echo "             (rate_calendar / reservations / payments / owner_ledger — row-level, TENANT ONE)"

# ⚠️⚠️ THE MODE IS DECIDED BY THE APPLICATION'S EFFECTIVE CONFIG, NOT BY THE SHELL VARIABLE.
# `php artisan config:cache` bakes VY_SEED_DATE in at cache time, so a shell that exports it can face an
# application that never saw it — the seed then runs at the real today while the shell believes it ran at the
# reference date, and the gate would red on a CALENDAR difference while blaming code. Asking the app what it
# actually used makes a false pass and a false fail both impossible.
# ⚠️ This is NOT the vacuous seeder-derived pin: it decides WHICH CHECK TO RUN. The expectations themselves
# stay hand-measured constants that the seeder has no say in.
EFFECTIVE_DATE="$(php artisan tinker --execute='echo (string) config("database.seed_date");' 2>/dev/null | tr -d '\r' | grep -E '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' | tail -1)"

if [ -n "${VY_SEED_DATE:-}" ] && [ "${VY_SEED_DATE}" != "${EFFECTIVE_DATE}" ]; then
  echo "⚠️  VY_SEED_DATE='${VY_SEED_DATE}' but the application's effective config.database.seed_date is"
  echo "    '${EFFECTIVE_DATE:-null}'. The seed did NOT use your date — almost always a cached config."
  echo "    Run:  php artisan config:clear"
  echo "    Falling back to the weaker INVARIANT check rather than comparing against the wrong baseline."
fi

if [ "${EFFECTIVE_DATE}" = "${REFERENCE_DATE}" ]; then
  echo "  expected:  ${EXPECTED_FULL}"
  echo "  expected:  ${EXPECTED_DIGEST}  (digest)"
  echo "  mode:      FULL — seeded at the reference date ${REFERENCE_DATE}; all six figures AND the row digest asserted."

  # ⚠️⚠️ A DIGEST COMPUTED BUT NOT COMPARED IS DECORATION. Both must match.
  if [ "${ACTUAL}" = "${EXPECTED_FULL}" ] && [ "${ACTUAL_DIGEST}" != "${EXPECTED_DIGEST}" ]; then
    echo "  mode=FULL"
    echo "❌ THE AGGREGATES ARE EXACT AND THE ROWS ARE NOT — THIS IS THE F-62 CLASS, CAUGHT."
    echo "   Counts and sums are unchanged, so something moved that nets to zero: an equal-and-opposite pair"
    echo "   of price errors, or a row REATTRIBUTED — a credit moved between owners, a payment moved between"
    echo "   reservations. ⚠️ The old gate printed mode=FULL on exactly this and said the money was as seeded."
    echo "   ⚠️ Do NOT re-pin the digest to make this green. Find what moved first."
    exit 1
  fi

  if [ "${ACTUAL}" = "${EXPECTED_FULL}" ] && [ "${ACTUAL_DIGEST}" = "${EXPECTED_DIGEST}" ]; then
    echo "  mode=FULL"
    echo "✅ MONEY AS SEEDED · FULL — TENANT ONE'S six aggregates unchanged AND its four covered money tables"
    echo "   are ROW-IDENTICAL as seeded (rate_calendar, reservations, payments, owner_ledger)."
    echo "⚠️  THIS IS NOT \"THE MONEY IS UNCHANGED\". FIVE money tables are read by NO instrument at all —"
    echo "    folios, folio_items, owner_statements, owner_expenses, subscriptions — and nothing here sees"
    echo "    any tenant but the first. The honest claim is the sentence above it, exactly as written."
    echo "⚠️  F-61 — AND THIS MODE HAS A COST: seeded at ${REFERENCE_DATE}, tenant two's 14-day trial expired"
    echo "    2026-09-15, so its ENTIRE /app/* back office is behind the paywall at any real present date."
    echo "    That is invariant #41 working. ⚠️ Do NOT extend the trial or move the reference date to walk it —"
    echo "    that is re-baselining a fixture to make a check pass. Its OWNER portal is not gated."
    exit 0
  fi
else
  echo "  expected:  ${EXPECTED_INVARIANT}  (the four date-invariant figures)"
  echo "  measured:  ${ACTUAL_INVARIANT}"
  echo "  mode:      ⚠️ INVARIANT — the app's effective seed_date is '${EFFECTIVE_DATE:-unset}', not ${REFERENCE_DATE}."
  echo "             SUM(price_minor) and the reservation count are DATE-DEPENDENT and were NOT checked, so"
  echo "             ⚠️ a changed rate price or a changed booking set would NOT be caught by this run."
  # ⚠️ An opt-in for any caller that must not accept the smaller check. Default OFF, so no existing
  #    invocation changes behaviour; CI already runs FULL via a step-level `env:` (which, unlike a
  #    command prefix, does reach both commands).
  if [ "${VY_REQUIRE_FULL:-0}" = "1" ]; then
    echo "  mode=INVARIANT"
    echo "❌ VY_REQUIRE_FULL=1 was set and the FULL check did NOT run. Refusing to report a partial result."
    exit 1
  fi

  echo "             For the full check:"
  echo "               export VY_SEED_DATE=${REFERENCE_DATE}; php artisan migrate:fresh --seed; ops/money-as-seeded.sh"
  echo "             ⚠️ EXPORT it. A 'VAR=x cmd1 && cmd2' prefix binds to cmd1 ONLY, so the check below"
  echo "                would run without it and silently land back here."

  if [ "${ACTUAL_INVARIANT}" = "${EXPECTED_INVARIANT}" ]; then
    # ⚠️⚠️ THIS BRANCH DELIBERATELY DOES NOT PRINT "✅" AND DELIBERATELY DOES NOT SAY "money as seeded".
    #
    # The two modes used to end in lines that differed only in a trailing parenthetical, and both exited 0.
    # A reader skimming for the tick, a report quoting the last line, or a grep for "Money as seeded" could
    # not tell a 4-of-6 run from a 6-of-6 one — and on 2026-08-26 a pass reported this run as the gate.
    # ⚠️ The weaker mode is NOT a lesser pass of the same check; it is a DIFFERENT, smaller check, and it
    #    says so in words that cannot be mistaken for the gate's own green.
    echo "  mode=INVARIANT"
    echo "⚠️ PARTIAL — 4 of 6 figures checked. THIS IS NOT THE MONEY-AS-SEEDED GATE."
    echo "   The four date-invariant figures are unchanged, which is worth knowing and is ALL it means."
    echo "   ⚠️ DO NOT QUOTE THIS RUN AS GREEN: SUM(price_minor) and the reservation count were never"
    echo "      compared, so a changed rate price or a changed booking set would have passed silently."
    exit 0
  fi
fi

echo "❌ THE DEMO MONEY MOVED. This is not automatically a bug, but it is never incidental:"
echo "   either a seeder changed, or something now writes money that did not before."
echo "   ⚠️ Rule it out first: was the database seeded at the reference date? A figure that moved because of"
echo "   the CALENDAR is not a code change — that is the failure this gate itself once had."
echo "   Establish which before merging, then update the baseline in this file WITH the reason."
exit 1
