# ②+ACCRUAL — the collection gate, and what it restates

**2026-08-23 · money logic on a sealed surface · ⚠️ two founder decisions surfaced, neither picked**

---

## 1 · ⚠️ THE DETERMINATION — it was payout-gating, not a basis change

**An `owner_ledger` row is an ACCRUAL: revenue recognised when EARNED.** Cited:

- `OwnerAttributionService` "attributes committed folio/reservation revenue to owners", reading `folio_items`
  and `reservation_units` — **charges**, at the moment the folio room line is posted.
- Rows carry `service_date` — **the date the service was delivered**, not a payment date. A cash record would
  key on when money arrived.
- The entry types are accounting categories (`room_revenue`, `commission`, `management_fee`, `ota_cost`,
  `payment_fee`, `withholding`), the ledger is append-only and correct-by-reversal, and `attribution_version`
  versions restatements.

⚠️ **So accruing on charge is CORRECT and was NOT changed.** Changing the basis would have rewritten the
meaning of every existing entry.

⚠️⚠️ **THE DEFECT WAS NARROWER, AND REAL: nothing distinguished owed-and-COLLECTED from owed-and-UNCOLLECTED.**
`OwnerStatementService::claimable()` took *every* unstatemented row by `service_date` alone. Nothing anywhere
in the owner money path consulted a payment. With `PAYMENTS_GATEWAY=manual` every storefront booking is
pay-on-arrival, so that was **the default path, not an edge case**.

**The fix is a collection-aware payable balance** — `App\Support\Owner\OwnerCollection`, gating what a
STATEMENT may claim. The ledger is untouched and goes on saying what is OWED.

## 2 · ⚠️ THE ORDERING, PROVEN

The two defects were cancelling: nothing finalised a statement, so nothing was paid, so nothing was overpaid.
**Unpicking finalisation alone would have started paying owners against uncollected revenue.**

`OwnerCollectionGateTest` (11 tests) is the spine. Mutations, each restored byte-identical:

| # | mutation | result |
|---|---|---|
| M1 | remove the collection gate from `claimable()` | ✅ **RED** — the charged-but-unpaid scenario pays |
| M2 | hold on collection alone (drop the asymmetry) | ✅ **RED** — *"a REVERSAL is being held"* |
| M3 | make the unique index non-unique **in the migration** | ✅ **RED** — *"QueryException not thrown"* |
| M4 | remove the `PAYOUT_ENGINE_ENABLED` gate | ✅ **RED** |

⚠️ **M2 AND M3 PASSED ON THEIR FIRST RUN — both of my own guards were vacuous.**
- M2's fixture reversed the *ledger* but never refunded the *folio*, so the reservation still read
  "collected" and the dangerous state was never built.
- M3 dropped the index on the **dev** database; `RefreshDatabase` rebuilds the **test** database from the
  migration, so the constraint was still there. The mutation had to move into the migration file.

⚠️ **δ = 0 proven two independent ways**: the statement's `net_source_minor` equals the ledger net of the rows
it claimed, *and* equals the sum of its component lines, derived by a different path.

## 3 · ⚠️⚠️ THE BUG THIS PASS SHIPPED AND THEN CAUGHT

The first rule was "hold anything uncollected". ⚠️ **A refund drives the folio balance positive, so a refunded
booking reads as *uncollected* — and the rule would have held the REVERSAL, leaving the owner credited for
money handed back to the guest.** Caught by an existing post-lock refund test (§N.27.7 — a pre-existing test
can encode the rule you are about to break).

**The corrected rule: hold a booking's group only if it is uncollected AND would PAY the owner.** Never hold
a clawback. Never hold a non-reservation debit (an expense, a fee) — holding a deduction pays the owner
*more*.

## 4 · ⚠️ THE BLAST RADIUS — 93 failing tests across 38 files

The gate broke **93 tests in 38 files**, every one for the same reason: *the owner fixtures charge a booking
and never pay it.* That is not incidental — **it is the measure of how deeply "charged" was treated as
"payable" across the whole owner suite.**

Fixed by adding an explicit `collectFolio()` (a new shared helper) after attribution, so each test says the
guest paid. ⚠️ **Deliberately NOT folded into `postRoom()`** — that would hide the gate from every fixture and
leave the most dangerous path untested.

⚠️ **A bulk regex sweep NEUTERED THE SPINE.** It added `collectFolio()` into `gateScenario()` itself, which
would have made every "pays nothing" assertion pass for the wrong reason. Caught by reading the diff; the
helper now carries a comment saying why it must stay uncollected. ⚠️ Two further fixtures (`$sept`,
`$resA`/`$resB`) were missed by the same regex because of their variable names — **sweep, don't count.**

## 5 · ⛔ FOUNDER DECISION 1 — WHO BEARS COLLECTION RISK

Holding until collection means **the platform does not front an owner their money; the owner waits on the
guest.** That is a business allocation, probably an owner-agreement question rather than a code one:

- **(a) hold until fully collected** — implemented, and the only option that cannot overpay;
- **(b) release pro-rata** with the folio's collection ratio — pays sooner, needs fractional allocation and a
  partial-claim model the ledger does not have;
- **(c) the platform fronts the owner** and carries the bad-debt risk — a financing decision.

## 6 · ⛔ FOUNDER DECISION 2 — WHAT TRIGGERS FINALISATION

`finalize()`/`generateDraft()` had **zero callers outside seeders and tests**, and the payout engine pays
**only finalized statements** — so a real deployment had nothing to pay, ever. The demo looked complete
because `DemoSeeder` finalises statements the application could not.

Shipped as an explicit **operator action** (`POST /app/owners/{owner}/close-period`, `can:payouts.manage`,
audited) — the only option needing no ruling. Still to decide: **(a)** operator action, **(b)** a scheduled
period close, **(c)** automatic on period end. And two open questions: a booking landing *after* its period
closes (today it flows to the next statement as a prior-period adjustment, the existing straggler path), and
whether a finalised statement may ever be **reopened**, and by whom (nothing reopens one; `finalize()` locks).

## 7 · ⚠️⚠️ RESTATEMENT — SURFACED, NOT DONE SILENTLY

`ops/money-as-seeded.sh` is **RED**, for **two independent reasons**. Both were attributed by stashing this
work and reseeding on baseline code.

| drift | cause | mine? |
|---|---|---|
| reservations **41 → 40** | ⚠️ **PRE-EXISTING.** Reproduces exactly on baseline with this work stashed. The demo seeder is **date-relative** (a `today→+180d` window) and the date rolled 08-22 → 08-23. ⚠️ **The money-as-seeded pin is date-fragile** — a finding in its own right. | ❌ no |
| Sept statement **gross 100000 → 46000**, net **82000 → 37720** | ⚠️ **MINE, and correct.** 54,000 of the demo's September gross comes from bookings whose folios were never paid. Under the gate they are held, not payable. | ✅ yes |

⚠️ Note the canon's pinned *"Sep demo net €885.60"* had **already** drifted to €820.00 from the date roll,
before this pass touched anything.

⛔ **THE BASELINE WAS NOT RE-PINNED.** `ops/money-as-seeded.sh` says to establish the cause first and then
update it *with the reason* — the cause is established above; the re-pin is the founder's call, and it
depends on Decision 1. **A third option exists:** make the demo seeder collect its September bookings, which
would restore a complete-looking month — but that changes seeded money and is equally a founder call.

## 8 · The two payout defects

- ⚠️ **`POST /app/payouts/run` bypassed `PAYOUT_ENGINE_ENABLED`** — the scheduled command checked it and the
  owner-facing display read it, but this route ran the engine regardless. ⚠️ A flag that gates the scheduled
  path and not the manual one is worse than no flag: every surface reads "off" while one button moves money.
  Fixed, mutation-proven.
- ⚠️ **The one-live-payout rule was application-only.** Confirmed: `payouts` had seven indexes and **only
  `PRIMARY` was UNIQUE** (the brief said "just PRIMARY", which is imprecise but right in substance). Now a
  `VIRTUAL` generated column + unique index — `STORED` was refused with error 1215 because it forces a table
  rebuild against the existing foreign keys. ⚠️ **Proven with the application check bypassed via raw SQL**,
  which is the entire point: an invariant the application enforces is not a constraint. A companion test
  keeps the SQL literal and `Payout::LIVE_STATUSES` in agreement, and another proves the constraint is narrow
  enough to leave the retry history intact.

## 9 · ⚠️ REFUNDS AND DISPUTES — REQUIRED, NOT BUILT

There are **no handlers for `charge.refunded`, `charge.dispute.*` or `payout.*`**. A refund issued from the
Stripe dashboard therefore never reaches the folio, the ledger or a statement, and the owner stays credited
for money returned. What handling them would require:

| event | what it must do |
|---|---|
| `charge.refunded` | record the refund on the folio (`FolioService::refundPayment`, which exists and is bounded by invariant #27), then `OwnerAttributionService::attributeRefund` so the owner's share reverses onto the next statement. `AdjustOwnerLedgerOnRefund` already does this for **in-app** refunds — the gap is the **dashboard-initiated** path. |
| `charge.dispute.created` | ⚠️ funds are withdrawn immediately. The owner's accrual should stop being payable at once, which the collection gate does **for free**: a disputed folio's balance goes positive, so the booking becomes uncollected and is held. ⚠️ **But an already-PAID statement is beyond its reach** — that needs a clawback path that does not exist. |
| `charge.dispute.closed` | release or make permanent, depending on the outcome. |
| `payout.paid` / `payout.failed` | Connect-side payout lifecycle — already handled for the transfer rail. |

⚠️ **This interacts with the collection gate directly and should be built with it in mind** — but it is
Stripe-side and adjacent to the gated Connect work (the payments lawyer + Q10). **Recommend it lands with the
Connect rail, not before**, since the charge model may still move.

## 10 · What the brief got wrong

| claim | verdict |
|---|---|
| the ledger "accrues on charges, not receipts" as a **defect** | ⚠️ **half wrong, and the brief said to check** — accruing on charge is correct; the defect was the missing collected/uncollected distinction |
| `SHOW INDEX` returns "just `PRIMARY`" | imprecise — seven indexes exist, but only `PRIMARY` is UNIQUE. Substance correct |
| nothing finalises a statement | ✅ confirmed — zero callers in `app/` or `routes/` |
| `POST /app/payouts/run` bypasses the flag | ✅ confirmed |
| DO3-3 is built | ✅ confirmed, and not treated as missing |

**Verification:** suite **2682 / 2680 passed / 2 skipped / 18,703 assertions** · PHPStan L5 **0** · Pint clean ·
eight CI gates untouched · the storefront still books (`TenantCanBecomeSellableTest` green) ·
⚠️ **`ops/money-as-seeded.sh` RED for the two reasons in §7, deliberately not re-pinned.**
