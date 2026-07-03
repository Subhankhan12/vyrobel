# Vyrobel — Phase 3 · Module 1: Owner Accounting & Payouts · Claude Code Master Prompt

**How to use:** save at `docs/prompts/phase-3-owner-accounting.md`. Run in a fresh session already oriented on `docs/PROJECT-STATE.md` + `docs/DECISIONS.md`. Gated, audit-first, per-gate commits, tests must really pass, no faked data — same discipline as Phase 1/2.

This is **the wedge**: the hybrid hotel+apartment operator with real owner accounting is *why* Vyrobel wins. Build it deep and trustworthy.

═══════════════════ PASTE BELOW THIS LINE ═══════════════════

# ROLE & MISSION
Build **Owner Accounting & Payouts** (Phase 3, Module 1) on the certified Phase 1/2 foundation. An operator assigns units to external owners, and Vyrobel produces auditable periodic statements — gross revenue attributed to each owner's units, minus commissions, management fees, deductible expenses, and OTA costs = net payout. This is backend + unstyled owner-portal contract stubs; styled UI is the later design pass.

Read `docs/PROJECT-STATE.md`, `docs/DECISIONS.md`, `docs/master-plan.md` (owner portal + accounting), and `docs/schema/phase-1.md` (note the deferred `units.owner_id` hook) first. Build only what each step specifies; **STOP at every ⛔ gate**.

# THE CRUX (drives the design)
1. **Revenue attribution must be explicit, rule-driven, and auditable.** An owner must be able to see *exactly* why their payout is what it is, line by line. Attribution runs off the folio ledger and records each attributed component as its own immutable line. Trust is the product here — vague or unexplainable statements lose owners.
2. **Statements are an append-only, period-locked ledger** — same correct-by-reversal discipline as the folio. Once a period is finalized/paid, it is LOCKED; a later cancellation/refund/adjustment posts an **adjusting entry in the next open period**, never edits a locked statement. Auditable, gap-free.
3. **Owner isolation is a NEW access boundary.** An external owner is a member of the operator's org but must see ONLY their own units, statements, and payouts — never another owner's data and never the operator's full financials. This is a row-level scope *within* a tenant, stricter than `BelongsToOrganization`. Getting it wrong leaks one owner's revenue to another — a trust-destroying bug. Treat it like the tenant-isolation guarantee: explicit, enforced, and tested.
4. **Money = integer minor units + currency, always.** Owners may be paid in a different currency than the booking; model currency explicitly, never float.

# INVARIANTS THAT MUST NOT BREAK
All Phase 1/2 invariants hold (single write path via AssignmentService, append-only folio ledger, tenant scoping, money-as-minor-units, event direction post-commit, enforced morph map, ULID). This module ADDS: owner-isolation scope (owners see only their own rows); owner statements are append-only + period-locked + correct-by-reversal; attribution is derived from committed folio/reservation state and never mutates it.

# RULES OF ENGAGEMENT
- Audit first; STOP at every ⛔ gate; per-gate commit; update `docs/DECISIONS.md` at each close-out.
- Extend, don't duplicate: reuse the folio/ledger append-only pattern, Money, domain events, BelongsToOrganization, the polymorphic infra. Attribution READS the folio ledger; it never writes to folios or touches inventory.
- No faked data or dressed-green tests. Demo/owner seed data clearly flagged.
- Register every new polymorphic-capable model in the enforced morph map.
- The actual Stripe Connect money movement is DEFERRED (see Step 5): build a payout-rail interface + fake now; live Connect is a separate credentialed checkpoint.

## ⛔ STEP 0 — Audit, schema draft & plan (GATE)
1. Audit: confirm Phase 1/2 suite is green (`php artisan test`), and report the current state of the deferred `units.owner_id` column (nullable, no FK — Phase 1 hook to close here).
2. Draft `docs/schema/phase-3-owner-accounting.md` (grounded in the real schema + master plan) proposing columns for: `owners` (org-scoped party — person/company; payout currency; contact; linked user for portal access), `management_agreements` (per owner and/or per unit: commission_pct, fixed management fee + interval, the deductible-expense and fee/OTA-cost attribution RULES, payout currency/schedule), `owner_ledger` (append-only attributed lines — the source of truth), `owner_statements` + `owner_statement_lines` (period-based, derived, lockable), `owner_expenses` (owner-attributable costs: cleaning, maintenance), `payouts` (calculated record + lifecycle; Connect ids deferred). Reuse existing where possible.
3. Propose the attribution rule set (defaults below, overridable per agreement) for approval. Flag any conflict. **STOP.**

## ⛔ STEP 1 — Owners & management agreements (GATE)
- `owners` (BelongsToOrganization, ULID; payout currency; optional linked user for portal login via the Phase 1 External Owner role) and `management_agreements` (commission %, fixed fee + interval, attribution rules, payout schedule/currency; effective-dated so terms can change over time without rewriting history).
- **Close the Phase 1 hook:** add the `units.owner_id` FK now that `owners` exists (nullable — not every unit has an owner).
- Register `owner`/`management_agreement` in the morph map. **STOP.**

## ⛔ STEP 2 — Revenue attribution engine (GATE — the crux)
- Build an `OwnerAttributionService` that, for a given period, attributes committed folio/reservation revenue to owners based on which owner's unit was occupied, writing each component as its own immutable `owner_ledger` line. It READS the folio ledger and reservation_units; it NEVER mutates folios, reservations, or inventory.
- **Default attribution rules (document them; make each overridable per `management_agreement`):**
  - Room revenue for the owner's unit's nights → owner GROSS.
  - Management commission (agreement %) → deducted from owner gross → operator revenue.
  - Fixed management fee → deducted from owner (pro-rated per interval).
  - OTA commission attributable to the booking → deducted from the owner's share by default (owner bears channel cost on their revenue) — configurable to operator.
  - City/tourism tax → PASS-THROUGH (guest→authority); excluded from owner gross and operator revenue.
  - Cleaning fee → per agreement (default: owner revenue if the owner bears cleaning cost; else operator).
  - Add-ons (breakfast/transfer/tours) → operator revenue by default (operator provides them); configurable.
  - Damage/security deposit → not revenue; excluded.
- **Correct-by-reversal:** a later cancellation/refund/modification of an already-attributed booking posts REVERSING `owner_ledger` lines (never edits the originals). Attribution is idempotent per (booking, component, version).
- Multi-unit reservations attribute per unit to the correct owner; split stays and mid-stay unit moves attribute per-night to whoever owned the occupied unit that night. **STOP.**

## ⛔ STEP 3 — Owner-attributable expenses (GATE)
- `owner_expenses` (cleaning, maintenance, other): amount (Money), date, category, the owner/unit it's attributable to, source (manual or linked to a maintenance/housekeeping record where one exists), and whether it's deductible per the agreement. These post to `owner_ledger` as deduction lines during statement generation. Reuse existing maintenance/housekeeping records as sources where available; don't duplicate. **STOP.**

## ⛔ STEP 4 — Owner statements (period-locked, append-only) (GATE)
- `owner_statements` (owner, period start/end, currency, status draft→finalized→paid, locked flag) + `owner_statement_lines` derived from `owner_ledger`: gross → commission → management fee → deductible expenses → OTA cost → **net payable**, all integer minor units. Currency conversion to the owner's payout currency modeled explicitly if it differs (record the rate used; don't silently float).
- **Period locking:** finalizing a statement LOCKS its period. Any later adjustment (post-lock cancellation, refund, correction) posts an adjusting `owner_ledger` line dated into the **next open period** and appears on the next statement — the locked statement is never edited. Gap-free, auditable.
- A statement is a pure projection of the ledger for its period; regenerating a draft yields the same numbers. **STOP.**

## ⛔ STEP 5 — Payouts (calculation now; money rail deferred) (GATE)
- `payouts`: created from a finalized statement's net payable; lifecycle pending→paid/failed; amount (Money) in the owner's payout currency; nullable external/Connect ids (deferred).
- Build a `PayoutRail` interface + a `ManualPayoutRail` (records the payout as marked-paid manually, e.g. bank transfer done offline) — mirrors the Phase 1 PaymentGateway pattern. **Do NOT wire Stripe Connect**: leave a `StripeConnectPayoutRail` as a config-gated skeleton, explicitly flagged unverified, to be validated in a later live checkpoint. A payout marks its source statement paid; both are append-only (a reversed/failed payout is a new record, never an edit). **STOP.**

## ⛔ STEP 6 — Owner portal reads + owner isolation (GATE — the new security boundary)
- Backend + UNSTYLED contract stubs for the owner portal: an owner sees their units, occupancy/calendar (reuse AvailabilityService reads), statements, payouts, net figures — and NOTHING else.
- **Owner isolation scope:** an authenticated owner-user (External Owner role) is restricted to rows for owners they're linked to. Implement as an explicit owner-scope layer for owner-facing reads — do NOT rely on the org scope alone (the owner shares the org with the operator and other owners). The operator/manager side sees all owners; the owner side sees only itself.
- Contract stubs only (like Phase 1 Step 9): documented data shapes, no CSS. Resolve any typed model tenant+owner-scoped AFTER context is set (the documented Livewire RMB rule). **STOP.**

## ⛔ STEP 7 — Full integrity suite & consolidation (GATE)
Named tests, all must actually pass:
1. **Attribution correctness:** a booking on an owned unit attributes room revenue to the right owner; commission/fee/OTA/tax/add-on each land per the rules; a multi-unit reservation splits to the correct owners; a mid-stay unit move attributes per-night correctly.
2. **Correct-by-reversal:** cancelling/refunding an attributed booking posts reversing ledger lines; the original lines are untouched; net nets out correctly.
3. **Period locking:** finalizing locks the period; a post-lock adjustment lands on the NEXT statement, never edits the locked one; statements are gap-free and reproducible from the ledger.
4. **Money integrity:** all figures integer minor units end-to-end; cross-currency payout records the rate; no float drift; statement net = sum of its lines.
5. **OWNER ISOLATION (treat like tenant isolation):** owner A cannot read owner B's units/statements/payouts; an owner cannot read the operator's full financials; the operator sees all; guard/scope enforced, not bypassed.
6. **Payout lifecycle:** manual payout marks the statement paid; append-only (a failed/reversed payout is a new record); Stripe Connect rail is config-gated and NOT exercised (flagged deferred).
7. Full Phase 1 + Phase 2 suite re-asserted green (no regression).

Update `docs/DECISIONS.md` + `docs/PROJECT-STATE.md`: module complete; the attribution rule set of record; the owner-isolation guarantee; the deferred Stripe Connect payout live-checkpoint added to the register. Per-gate commit. **STOP with the honest gate** (test count, PHPStan L5, Pint, owner-isolation proven, Connect deferred). Do not start the next module.

═══════════════════ PASTE ABOVE THIS LINE ═══════════════════

### Deferred from this module (track in the register)
- **Stripe Connect payout rail** — real money movement to owners (connected-account onboarding + KYC): a separate credentialed live checkpoint, like the Stripe payments one. Built here only as a config-gated skeleton behind the `PayoutRail` interface.
- **Owner portal styled UI** — the design pass (contract stubs only here).

### After this module
Send me the Step 7 report and I'll give you the calls, then we scope the next Phase 3 module (Unified Inbox) — or slot in the Channex checkpoint / a thin design slice to demo the wedge, whichever fits where you are.
