# Vyrobel — WAVE 2 COMPLETE FEATURE INVENTORY

**Purpose:** guarantee **nothing is silently dropped** when Wave 2 is built, and give you the list of
backend decisions to make. Every feature/element/panel/button/interaction on every Wave 2 design page is
listed below and tagged. Analysis only — **no app code was changed producing this.**

**Status:** read-only inventory · 2026-07-16 · repo at `4d68e8c`..`cbc7d7b` (Wave 1 complete, `/app`
authorization gate closed). Supersedes nothing; it *refines* `docs/TIER1-SPEC.md`'s Wave 2 section, which is
optimistic in two places (see "Where TIER1-SPEC is wrong", below).

## The tags

| Tag | Meaning |
|---|---|
| ✅ **BUILD-NOW** | The backend genuinely exists. The named model/service/column/route is cited and was verified in the repo. |
| 🔨 **NEEDS-BACKEND** | A real, useful feature with no backing data/service. The exact column/table/service method is named, with a size: **S** (a column/scope/derivation) · **M** (a table + relation + service method) · **L** (a subsystem). |
| 📋 **DECORATIVE/FAKE** | Visual-only, hard-coded, fabricated, or an inert control. **Flagged so we knowingly do NOT fake it.** |
| ⬜ **OTHER-MODULE** | Belongs to an unbuilt module (reviews, analytics, billing, guest portal). Note + defer. |

## Method

All six designs were **fully decoded** (markup + the embedded `data-dc-script` logic class, which carries the
hard-coded demo constants and reveals which controls actually do anything). Every ✅ was verified by reading
the real model/service/migration — an unverified ✅ is a defect. The Wave 1 lessons were applied throughout:
these designs contain **fabricated figures, invented statuses, inert buttons and unbacked claims**, and those
are called out rather than ported.

---

# 🔴 STOP — A LIVE FINANCIAL BUG FOUND WHILE INVENTORYING (not a Wave 2 item)

**`FolioService::refundPayment()` has no CUMULATIVE over-refund guard, and the shipped UI already makes it
reachable. A payment can be refunded repeatedly, without limit.**

- `FolioService.php:221` checks only `$amount > $original->amount_minor` — a **per-call** test against the
  ORIGINAL amount. Nothing sums prior refunds of that payment.
- The original payment stays `type=payment, status=succeeded` after a refund, and
  `ReservationDetail.php:171` marks **any** settled payment `refundable` — so the refund button **remains on
  screen after refunding**. `ReservationFolioActionController::refund` re-checks only that the payment is
  settled.
- **Proven empirically** (dev DB, since restored): a **€369.00** payment accepted **three full refunds =
  €1,107.00**. Each call individually satisfied `36900 <= 36900`.

```
original payment 36900 minor (EUR)
  full refund #1 -> ACCEPTED (36900 minor)
  full refund #2 -> ACCEPTED (36900 minor)
  full refund #3 -> ACCEPTED (36900 minor)
TOTAL REFUNDED 110700 against ORIGINAL 36900  => OVER-REFUNDED — NO CUMULATIVE GUARD ❌
```

**This does not violate invariant #7** (the ledger *is* append-only and correct-by-reversal — each refund is a
new offsetting row, the original untouched). The gap is that nothing bounds the *total* reversed. With
`ManualPayoutRail`-style manual gateways it is a bookkeeping error; against a real gateway it is a
double-refund of real money.

**Wave 2 makes it materially worse**: the whole point of the Refund page is to unblock *partial* refunds, which
turns "click twice" into "refund €50 twenty times against a €100 payment".

**Recommendation — fix this BEFORE the Wave 2 refund screen** (S):
1. In `FolioService::refundPayment()`, sum prior succeeded refunds via
   `payments WHERE type='refund' AND status='succeeded' AND metadata->refunds_payment_id = $original->id`
   and reject when `Σ prior + new > original->amount_minor`.
2. Make `ReservationDetail`'s `refundable` subtract prior refunds, so a fully-refunded payment stops
   offering the button.
3. A regression test: two refunds that individually pass but together exceed → the second throws.

*(Read-only task — not fixed here. This is a real backend change and your call.)*

---

# CONSOLIDATED 🔨 NEEDS-BACKEND — the decisions you need to make

Grouped so shared backend is obvious. **Sizes: S** = a column/scope/derivation · **M** = table+relation+service
· **L** = a subsystem.

## A. Guest CRM — shared by Guests LIST + Guest PROFILE (build once, use twice)

> **Good news first: far more exists than `docs/TIER1-SPEC.md` implies.** `guests` already has **`is_vip`**,
> **`risk_flag`**, `marketing_consent`, `nationality`, `dob`, `language`; and `Guest` already uses
> **`HasNotes`, `HasTags`, `HasDocuments`** with `guest` in the morph map. So **VIP, risk, tags and notes are
> ✅ BUILD-NOW, not new backend.** `guest_documents` + its `verification_status` are real too.

| # | Feature | Needs | Size |
|---|---|---|---|
| A1 | `Guest::reservations()` relation | **Absent today.** `hasMany(Reservation::class, 'lead_guest_id')`. Everything below depends on it. **Decide:** `lead_guest_id` only, or ∪ the real `reservation_guests` pivot — the two give different numbers for the same guest. | **S** |
| A2 | **Lifetime spend** (profile hero + list column) | A shared `App\Support\Guest\GuestStats` helper (mirroring `FolioTotals`) over the guest's folios. **⛔ DECIDE FIRST: BILLED (`Σ charges_minor`) or COLLECTED (`Σ paid_minor`)?** The design is self-contradictory — it nets refunds off (collected) while including €701 of unpaid charges (billed). The SQL is trivial; **the definition is the deliverable.** Must be shared or the list and profile will disagree. | **S** once decided |
| A3 | Guest status "In-house / Upcoming / Past" (filter + column + pill) | Scopes over A1 (`checked_in` ∧ date-spanning; future `confirmed`/`tentative`; else past). **Decide the semantics once** — the list, the profile and the reservations-list chips must agree. | **S** |
| A4 | Stays count · Last stay · nights · avg/night | `withCount` + `MAX(check_out)` over A1 + a cancelled/no-show exclusion rule. | **S** |
| A5 | Search by guest **name** | A `whereLike` scope over `first_name`/`last_name` (plaintext). The design's "input" is a `<div>` — needs a real one. | **S** |
| A6 | Search/filter by **email or phone** | **Impossible today** — encrypted `text`, never indexed by value. Needs a **blind index** (`guests.email_hash` HMAC + index) + migration + write-path backfill. This is the standing blind-index deferral (same gate as Global Search). | **L** |
| A7 | `guests.city` (+ arguably `country_code`) | A nullable column. **⚠️ `nationality` is `char(2)` — a nationality, NOT residence.** The design renders "Dublin, Ireland" as an editable city/country; conflating them is a data-modelling error. | **S** |
| A8 | Contact preference ("Prefers email") | A `guests.preferred_channel` enum (or boolean). `guests.language` is a locale, not this. | **S** |
| A9 | Guest write path | `GuestActionController` (update / addNote / toggleTag) — POST→controller→PRG, string id, tenant-scoped. No `GuestService` exists; plain Eloquent + a controller is the right shape. | **S** |
| A10 | Tag **catalog** | `tags` + `HasTags` are real, but **nothing writes `tags` anywhere today** — a tenant tag catalog needs seeding/management before chips mean anything. | **S** write / **M** with management |
| A11 | Country name from `nationality` | An ISO-3166 alpha-2 → name map on `FormatsOperatorData`. | **S** |
| A12 | Guests list route/component/nav | `/app/guests` + `/app/guests/{guest}` + the shell nav key. **Build the LIST first** — the profile's breadcrumb depends on it. | **M** (the pages) |

## B. Reservations LIST

| # | Feature | Needs | Size |
|---|---|---|---|
| B1 | Route + component + nav | `/app/reservations` + `App\Livewire\Operator\ReservationsList`. The shell **already reserves** `['reservations', …, null]` as a disabled "Soon" — building this flips it to a real route. | **M** (the page) |
| B2 | Stat tiles ×3 (count · booked value · balances due) | **Every number is a hard-coded constant.** Real sources exist (`reservations.total_minor` / `balance_minor`, kept in sync by `FolioService::recompute()`), but need **decisions**: which month (the tiles say "This month", the inert picker says "Sep 2025"), which date column (`booked_at` vs `check_in`), whether cancelled counts (cancel never zeroes `total_minor`), and per-currency grouping (`reservations.currency` is per-row). | **S** each, once decided |
| B3 | Filter chips "Upcoming" / "Past" | A decided semantic + scope. The design's `kind` field is fabricated and applies no date test; it lumps `cancelled` into "Past" — but a cancelled future stay is not past. Must agree with A3. | **S** |
| B4 | Month picker | A `?month=YYYY-MM` GET param + a `check_in` range scope. Zero handlers today; its label contradicts the tiles. | **S** |
| B5 | "Refunded" row badge / drawer callout | An exists-derivation over `payments WHERE type='refund'`. | **S** |
| B6 | Bulk message composer → **Send** | The composer is real UI but **Send calls no backend**. A real send needs `InboxService` per recipient + a channel decision — and **every real external channel is account-gated** (`ManualChannel` is the only working one). Honest options: wire to `ManualChannel` only, or omit Send. | **M** + account-gated |

## C. Reservation CANCELLED / NO-SHOW states

> Both are **state variants of the already-built `ReservationDetail`** — most of the ✅ is code that exists.
> **Verified real and better than expected:** `reservations.cancelled_at` ✅, `reservations.cancellation_reason`
> ✅, `AssignmentService::cancelReservation(Reservation, ?string $reason)` ✅ (takes a reason), and the actor +
> full transition trail via **`reservation_status_history`** (`from_status`, `to_status`, `changed_by`,
> `reason`) ✅ — written by `AssignmentService::recordStatusHistory()`. So a **cancellation provenance panel and
> a status timeline are BUILD-NOW.**

| # | Feature | Needs | Size |
|---|---|---|---|
| C1 | **Cancellation FEE** ("1-night fee applied · €280.00") | A policy contract + `CancellationPolicyService::feeFor(Reservation): Money` + a caller posting it via `FolioService`. `property_policies` exists but no fee engine does. **This is the reason the cancelled screen exists.** | **M** |
| C2 | **No-show FEE + WAIVE flow** | Same shape: a no-show fee policy + a charge + a waive action. **Nothing exists.** ⚠️ `docs/TIER1-SPEC.md` rates No-show "SIMPLE · READY" — that rating **does not survive contact with the design**: the spec scoped it as "terminal state + banner + refund", but the design adds a fee model and a waiver. | **M** |
| C3 | No-show **reason** | One-line change: `markNoShow(Reservation, ?string $reason = null)` → `terminate($reservation, 'no_show', $reason)`. The reason column + status-history reason are **already wired**; `markNoShow` just hard-codes `null`. | **S** |
| C4 | Mark-no-show / cancel **actions** | POST→controller→`AssignmentService`→PRG. ⚠️ `markNoShow()` is tested but has **zero production callers** — no UI reaches it today. | **S** |
| C5 | "auto-marked at end of arrival day" | An artisan command + `Schedule::daily()` + a property-timezone-aware arrival-day query. | **S** |
| C6 | "Rebook guest" | A new-booking route prefilled from this reservation (delegating to the existing `AssignmentService`). `Operator New Reservation.html` is a **Wave 4** design. | **M** |

## D. REFUND flow

> **TIER1-SPEC is right where it matters:** `refundPayment` genuinely takes an arbitrary `Money`, is
> append-only, tenant-scoped, audit/domain-event emitting, and is **proven partial by existing tests**
> (`FolioLedgerTest:23`, `FolioPdfTest:73`). The transport exists (`POST /app/reservations/{r}/refunds` → PRG,
> cross-org 404). **Partial refund is a CONTROLLER gap, not a service gap** —
> `ReservationFolioActionController.php:60` hard-codes `new Money((int) $payment->amount_minor, …)`.

| # | Feature | Needs | Size |
|---|---|---|---|
| D1 | 🔴 **Cumulative over-refund guard** | **SHIP-BLOCKER — see the red section above. Live bug today.** | **S** |
| D2 | Partial amount on the write path | The controller accepting an `amount` input instead of hard-coding the full amount. | **S** |
| D3 | Refund **reason** (5 chips + "Recorded on the folio and in reports") | `refundPayment(..., ?string $reason = null)` merged into `payments.metadata` (json, exists) — note the method currently **hard-overwrites** metadata with `['refunds_payment_id' => …]`. Plus a **vocabulary decision** (no enum exists). | **S** |
| D4 | Payment picker / multi-payment refund | `refundPayment` requires **one specific Payment**; the design shows only an aggregate. Needs a UI selector, or `FolioService::refundFolio(Folio, Money)` walking settled payments oldest-first in one transaction. | **S–M** |
| D5 | "Full refund reverses every settled charge on the folio" | Copy is wrong as written: a refund reverses a **payment**; `folio_items` are untouched. Needs D4's loop, or reworded. | **S** |
| D6 | **By-charge refund mode** (checkbox list + selected total) | A link that **does not exist** between `folio_items` and `payments`. Needs `FolioService::creditItem(FolioItem, ?string)` posting a negative `folio_items` line. A different concept from a refund (a credit note). | **M** |

## E. Cross-cutting (appears on EVERY page — decide once, not per screen)

| # | Feature | Needs | Size |
|---|---|---|---|
| E1 | **Property Switcher** | No active-property concept exists **anywhere**. `FrontDesk.php:57` and `ReservationRack` both hard-code `Property::query()->orderBy('name')->first()`, and `DemoSeeder` seeds exactly **one** property. Needs `users.active_property_id` (or a session key) + a resolver + a guarded switch route + every operator screen honouring it. **Solve once, globally.** | **M** |
| E2 | Nav badges (Messages "2", Maintenance "2") | Hard-coded literals on every page. Both derivable cheaply (`InboxList.php:97` already computes the unread count; `MaintenanceTicket` open count). Belongs in the **shell**, not per page. | **S** |
| E3 | Notif Bell | A notifications table + read/unread service + delivery. The built shell already has an **honest inert bell** ("Notifications — coming soon"). | **M** |

## F. Product gaps worth raising (not required by any design)

| # | Gap | Note |
|---|---|---|
| F1 | **`Guest::anonymize()` is reachable from NO UI** | Fully built, certified, GDPR-critical — and no screen calls it. The guest profile is its obvious home; the design omits it. | **S** |
| F2 | **`notes` survive `anonymize()`** | Operator free-text on a guest is **not** redacted by the GDPR erase — a note can contain PII and outlive it. A real gap. | **S** |
| F3 | `is_vip` is read by **zero code** | The column is real; the design promises "flags arrivals & the front desk for extra care" — nothing implements that. Either soften the copy or build the effect. | **S** |

---

# CONSOLIDATED 📋 DECORATIVE / FAKE — do NOT fake these

Every design carries fabricated data. The pattern is identical to Wave 1's ("3 years · 34 documents",
fabricated prior-year occupancy). **Derive it or drop it — never port the constant.**

## Fabricated figures (every headline number in these designs is a literal)
- **Reservations list** — all three stat tiles are constants; the 8-row table is a hard-coded array.
- **Guest profile** — `ltv: 3940` (the headline), open folio `e0(978)` / `e0(701)` (978 only reconciles if the
  refund is *exactly* €277 — `refundAmt()` is arbitrary, so a €50 refund still prints €978), stay totals
  `1023/1120/1797`, refs `VYR-10428/8611/7203`, identity `Amara Okafor` + `amara.okafor@mail.com` + `+353 87
  224 1180` + `Dublin, Ireland`.
- **Nav badges** `2` / `2` on every page.

## Invented statuses (must key to the REAL enums — the Wave 1 "Scheduled"/"On hold" failure)
- **"Completed"** → NOT in `reservations.status` (`enquiry, tentative, confirmed, checked_in, checked_out,
  cancelled, no_show`). Real value: **`checked_out`** → "Checked out".
- **"In house"** → real: `checked_in` → "Checked in".
- **"Booking.com" / "Expedia" / "Airbnb"** → **the OTA brand is NOT modelled.** `reservations.source` has only
  `'ota'`; `channels.provider` is `enum('channex')`. Render **"OTA"** via `FormatsOperatorData::sourceLabel()`.
  ⚠️ Don't be fooled by `message_threads.channel`, which *does* contain `booking_com`/`airbnb` — that's the
  **messaging medium**, a different enum for a different concept.

## Inert controls (markup with no handler — the Wave 1 Payout-Run failure mode)
- **Guest profile "Add note"** — no `sc-camel-on-click` at all.
- **Guest profile "Save changes"** — has a handler, but it is `closeModal` + a toast. **It persists nothing.**
  All 10 of that page's handlers are `setState` only; **zero reach a backend.**
- **Reservations list "Send"** (bulk composer) — fires a toast, clears state, calls nothing.
- **Reservation Cancelled** — **0 onClick, 0 sc-for across the entire page.** It is a static mock.
- **Month picker** (reservations list) — zero handlers.
- **Guest profile stay rows 2 & 3** — `href: '#'`.
- ⚠️ **A design-level toast is a mock, not a backend.** Wave 1's rule.

## Already-rejected devices (closed decisions — do NOT reopen)
- **"Workspace paused — read-only until 5 Oct" banner** (on multiple pages, always rendered — no `sc-if`).
  `OperatorOwnerDetail.php:101-102` **omits this exact banner by name**. `subscriptions.status` genuinely has
  `'paused'`, which makes it tempting — but zero code reads it, there is no read-only enforcement and no
  billing screen. Its sub-line even contains a literally empty `<span data-vyc-zero=""></span>` — a broken
  placeholder that doesn't render as English.
- **`localStorage('vyrobel:refund:last')` cross-page refund netting** — read by ~10 design files.
  `OperatorOwnerDetail.php:111` **omits it by name**. Refunds are real (`payments type=refund`); this channel is not.

## Unbacked UI claims (verified false in the repo today)
- "Shown to housekeeping & front desk" — neither surface reads guest prefs/tags.
- "Flags arrivals & the front desk for extra care" — `is_vip` is read by **zero** code.
- "Default channel for confirmations & messages" — no such routing, no column.
- "Seasonal offers & the occasional newsletter" — no sending mechanism.

## Chrome
Ambient orbs / light streaks / `vyDrift` / `vyFadeUp` / count-up animations / `window.VYC` currency harness /
`window.__vyToast` — design-kit runtimes. Real equivalents: the operator design system + Livewire flash +
`FormatsOperatorData::money()`. The design's **nav grouping differs from the built shell's** and omits Units +
Payout run (both built) — **reuse `<x-operator.shell>` verbatim; the design is the incomplete one.**

---

# BUILD-READINESS SUMMARY

| Page | ✅ build-now | 🔨 needs-backend | 📋 fake | ⬜ other | Verdict |
|---|---|---|---|---|---|
| **1. Reservations LIST** | ~70% | ~12% (mostly S) | ~13% | ~5% | **Strongest page. BUILD NOW** — drop the stat-tile constants, render "OTA" not brands, defer the composer's Send. |
| **2. Reservation CANCELLED** | ~50% | ~40% | ~10% | — | **Mostly already built** (it's a `ReservationDetail` variant). Provenance + timeline are ✅. The **cancellation fee (C1)** is the real work. |
| **3. Reservation NO-SHOW** | ~45% | ~30% | ~20% | ~5% | ⚠️ **TIER1-SPEC's "SIMPLE · READY" is wrong.** The frame is built; the **fee + waive flow — the reason the screen exists — has no backend (C2)**. |
| **4. REFUND** | ~45–50% | ~35% | ~15% | — | **Small delta over a certified backend** (partial is a controller gap) — **but blocked by the live over-refund bug (D1)**. |
| **5. GUESTS LIST** | ~20% | ~55% (nearly all S) | ~20% | ~5% | Thinner than TIER1-SPEC implies, but the 🔨 items are **shallow derivations**, not a module. Collapses into A1+A2+A3. |
| **6. GUEST PROFILE** | ~55% | ~20% | ~15% | ~10% | **Healthiest design** — its arithmetic actually reconciles (unlike Wave 1). Blocked only by the **LTV definition (A2)**. |

## Where `docs/TIER1-SPEC.md` is wrong (corrections from this inventory)

1. **No-show is NOT "SIMPLE · READY".** The spec's three named claims are true (`status='no_show'` ✅,
   `markNoShow` ✅, `refundPayment` ✅) but it scoped the screen as "terminal state + banner + refund". The
   design's actual subject — a **no-show fee + waiver** — has no backend at all.
2. **Guests CRM "(+ VIP/risk/tags/notes)" understates what exists.** All four are ✅ TODAY (`is_vip`,
   `risk_flag` columns; `HasNotes`/`HasTags` on `Guest`, morph-mapped). What's actually missing is
   `Guest::reservations()` and the spend/status derivations.
3. **"Partial refund" is not a backend build** — `refundPayment` already takes a `Money` and is test-proven
   partial. It's a one-line controller change… *plus* the over-refund guard that must land with it.

## RECOMMENDED ORDER

1. **🔴 Fix the over-refund guard (D1)** — a live bug, independent of Wave 2. Small, and Wave 2's refund
   screen makes it far more exploitable.
2. **Decide the three definitions** — they block headline numbers and, if decided per-screen, will make
   screens disagree with each other:
   - **A2** Lifetime spend: **BILLED or COLLECTED?** (recommend BILLED, labelled "Lifetime billed", with the
     real open-folio balance beside it)
   - **A3/B3** Guest & reservation status semantics ("Upcoming"/"Past"/"In-house") — one definition, three screens.
   - **B2** Stat-tile scope (which month, `booked_at` vs `check_in`, cancelled in/out, per-currency).
   - **PII:** does the operator surface render guest **email/phone**? No built screen does today (`FrontDesk`
     and `InboxThread` are name-only *by explicit comment*); `TIER1-SPEC` says "contact per policy" and **the
     policy doesn't exist**. A CRM is the defensible place to say yes — but say it deliberately, in
     `DECISIONS.md`, because the profile modal also *writes* those fields.
3. **Build Reservations LIST** (strongest, flips a real nav placeholder, no decisions blocking).
4. **Build Guests LIST → Guest PROFILE** (in that order — the profile's breadcrumb needs the list; both share
   `GuestStats`).
5. **Build Refund** (after D1) — small delta, high value, unblocks a real "Soon".
6. **Cancelled / No-show:** ship the ✅ provenance + timeline + terminal states now; **flag the fee engines
   (C1/C2) as a product decision** — they are a cancellation-policy subsystem, not a screen.
7. **Defer, don't fake:** the Property Switcher (E1 — solve globally), the bulk-send (B6 — account-gated), the
   paused banner, the localStorage netting, reviews.

---

# PER-PAGE FULL INVENTORIES

Everything below is the exhaustive per-page listing — every element, tagged. Read the page you are
about to build; the consolidated lists above are the decision material.

---

# 1. Reservations LIST (operator)

**Design file:** `docs/design/raw/Operator Reservations.html` — 1,591,378 B · template 89,956 · 23 onClick · 10 sc-for

**What it is.** A reservations table (7 columns) over a 3-tile stat strip, with a search box, 5 filter chips, an inert month picker, a multi-select mode driving a fixed bulk bar, a bulk-message composer modal with live per-recipient name preview, a right-hand booking detail drawer, two empty states, and a toast. It is genuinely interactive **as a design**: 23 `sc-camel-on-click` handlers, 5 `sc-for` loops, 19 `sc-if` branches, real client-side filter+search over an 8-row hard-coded array, real select/select-all tri-state, a real Escape-key cascade, and real token substitution in the composer preview. But **every number is a constant** and **the Send button calls no backend** — it fires a toast and clears state. The table core is a clean read over `Reservation` and is the strongest part; the stat strip, the "Booking.com/Expedia/Airbnb" source column, and the bulk send are the three things that cannot be ported as drawn.

## Feature inventory — every element

> **Verification basis.** Every ✅ below was checked against the repo: `app/Models/Reservation.php` + `database/migrations/2026_07_02_001410_create_reservations_table.php`, `app/Models/Guest.php` + `…001300_create_guests_table.php`, `…001420_create_reservation_units_table.php`, `…001510_create_folios_table.php`, `…001540_create_payments_table.php`, `…020000_create_channels_table.php`, `app/Services/{FolioService,AssignmentService,FrontDeskService,InboxService}.php`, `app/Support/Folio/FolioTotals.php`, `app/Livewire/{FrontDesk,ReservationDetail,ReservationRack}.php`, `app/Livewire/Operator/Concerns/FormatsOperatorData.php`, `resources/views/components/operator/shell.blade.php`, `routes/web.php`, `php artisan route:list`.

### Shell — sidebar (reuse `<x-operator.shell>`; do NOT rebuild)

⚠️ **The design's nav ≠ the built shell's nav.** Design groups: Overview / Front desk / Operations / Finance / System. Built shell groups: OPERATIONS / DISTRIBUTION / OWNERS / ACCOUNT. The design also omits **Units** and **Payout run** (both built + routed) and adds **Insights**/**Reports**/**Guests** (none built). **Reuse the existing shell verbatim; do not port the design's grouping.**

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Vyrobel brand mark "V" + wordmark | 📋 DECORATIVE-FAKE | — | Static markup; shell already has `.vy-brand`. |
| Ambient light streaks (2 radial/linear gradient divs) | 📋 DECORATIVE-FAKE | — | `pointer-events: none`. |
| Floating colour orbs ×3 (`vyDrift` 14s animation) | 📋 DECORATIVE-FAKE | — | Gated on the `motion` design prop (`data-props`: `{"motion":{"editor":"boolean","default":true}}`) — an editor-only prop, not app state. |
| **Property Switcher** (`<dc-import name="Property Switcher">`) | 🔨 NEEDS-BACKEND (**M**) | Needs an **active-property concept**: a `users.active_property_id` column (or a session key) + a resolver + a guarded switch route. Today `FrontDesk.php:57` and `ReservationRack` both do `Property::query()->orderBy('name')->first()` — hardcoded to the first property. `DemoSeeder.php:113` seeds exactly **one** `Property`. | The component is a separate design file, not in this template. No switcher exists anywhere in the app. |
| Nav: Dashboard | ✅ BUILD-NOW | `route('app.dashboard')` → `App\Livewire\Operator\OperatorDashboard` | Already in shell. |
| Nav: Calendar | ✅ BUILD-NOW | `route('app.rack')` → `App\Livewire\ReservationRack` | Shell calls it "Calendar" too. |
| Nav: Insights | ⬜ OTHER-MODULE | — | No route, no model. Tier-2 Analytics (`docs/ROADMAP.md`). Shell has no such item. |
| Nav: Reservations (active state) | ✅ BUILD-NOW | Flip `shell.blade.php` `['reservations', …, null]` → `route('app.reservations')`; pass `active="reservations"`. | This is the page. |
| Nav: Arrivals & departures | ✅ BUILD-NOW | `route('app.front-desk')` → `App\Livewire\FrontDesk` | Shell labels it "Front desk". |
| Nav: Guests | ⬜ OTHER-MODULE | `App\Models\Guest` exists; **no list route/component**. `docs/TIER1-SPEC.md` Wave 2 item **9** ("Guests CRM list"). | Sibling Wave-2 page — do not build here. |
| Nav: Messages | ✅ BUILD-NOW | `route('app.inbox')` → `App\Livewire\InboxList` | Shell labels it "Inbox". |
| Nav: Messages badge "**2**" | 📋 DECORATIVE-FAKE | Hard-coded `<span …>2</span>` in markup. | Real derivation is cheap — 🔨 **S**: `MessageThread::where('status','open')->sum('unread_count')`. But it belongs in the **shell**, not this page. |
| Nav: Housekeeping | ✅ BUILD-NOW | `route('app.housekeeping')` → `App\Livewire\HousekeepingBoard` | |
| Nav: Maintenance | ✅ BUILD-NOW | `route('app.maintenance')` → `App\Livewire\MaintenanceList` | |
| Nav: Maintenance badge "**2**" | 📋 DECORATIVE-FAKE | Hard-coded. | 🔨 **S** if wanted: `MaintenanceTicket::whereIn('status',['open','in_progress'])->count()`. Shell-level. |
| Nav: Rates & availability | ⬜ OTHER-MODULE | No route. Shell has `['rates', …, null]`. Wave 4 item 11. | |
| Nav: Channels | ⬜ OTHER-MODULE | No route. Shell has `['channels', …, null]`. Wave 4 item 11; live Channex is 🔴 account-gated. | |
| Nav: Owners | ✅ BUILD-NOW | `route('app.owners')` → `App\Livewire\Operator\OperatorOwners` | |
| Nav: Reports | ⬜ OTHER-MODULE | — | No route, no model. Not even in the shell. Tier-2. |
| Nav: Settings | ⬜ OTHER-MODULE | Shell has `['settings', …, null]`. Wave 4 item 16. | |
| Nav: Security | ⬜ OTHER-MODULE | Shell has `['security', …, null]`. Wave 4 item 16. | |
| *(Shell has **Units** + **Payout run**, the design does not)* | ✅ BUILD-NOW | `route('app.units')`, `route('app.payouts.run')` | Keep them — the design is the incomplete one. |

### Paused / read-only banner (top of main)

⚠️ **Always rendered — there is no `sc-if` around it.** Every word is hard-coded. This is a whole-banner 📋.

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Pause-icon circle | 📋 DECORATIVE-FAKE | — | |
| "**Workspace paused — read-only until 5 Oct**" | 📋 DECORATIVE-FAKE | Hard-coded string; "5 Oct" is a fabricated date (Wave-1 fake-date pattern). | The *status* is half-real: `subscriptions.status` enum genuinely contains **`'paused'`** (`…000400_create_subscriptions_tables.php:20`, enum = trialing/active/past_due/canceled/incomplete/paused) and `current_period_end` exists. But **nothing reads or writes it** — no billing module, no gate, no UI. |
| "channels stopped selling · `<span data-vyc-zero="">` while paused" | 📋 DECORATIVE-FAKE | The `data-vyc-zero` span is **literally empty** — a broken/unfilled currency-harness placeholder. | Quote: `<span data-vyc-zero=""></span> while paused`. The sentence does not even render as English. Do not port. |
| "**Resume now**" → `Operator Billing.dc.html` | ⬜ OTHER-MODULE | Billing is **Tier 2** (`docs/ROADMAP.md`): `Subscription`/`Plan`/`PlanFeature`/`SubscriptionItem`/`UsageCounter` models exist, but there are **zero billing routes/controllers/services** and live billing is Tier-3 account-gated. | |
| **Whole banner** | 📋 DECORATIVE-FAKE | To make it real: 🔨 **M** — a subscription-state read + a `paused` → read-only enforcement layer across every write. That is a *product* decision (a real read-only mode), not a presentation pass. | **Recommend: omit entirely from the build.** Shipping a banner that always says "paused" on a workspace that isn't would be a fabricated status (invariant #19). |

### Top bar

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| `<h1>` "Reservations" | ✅ BUILD-NOW | `<x-operator.shell title="Reservations">` | |
| `<dc-import name="Notif Bell">` | 📋 DECORATIVE-FAKE | Shell already renders `<span class="vy-bell" title="Notifications — coming soon">` — an honest, non-interactive "Soon". | Notifications is a Tier-2 module. Keep the shell's honest stub. |
| `<dc-import name="Operator Account Menu">` | ✅ BUILD-NOW *(partial)* | Shell renders `viewer['initials']` + `viewer['name']` (from `FormatsOperatorData::viewer()` → `auth()->user()->name`) + a real CSRF `POST route('logout')`. | The chip + sign-out are real. The *dropdown menu* itself doesn't exist — the shell's flat chip is the built precedent. Reuse it. |

### Control row

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Search input, placeholder "Search by guest, reference or unit…" (`queryStr` / `onQuery`) | ✅ BUILD-NOW *(with one caveat)* | `reservations.reference` (indexed `unique(organization_id, reference)`), `guests.first_name`/`last_name` (plain columns), `units.code`/`units.name` via `reservationUnits.unit`. All tenant-scoped by `BelongsToOrganization`. | ⚠️ **Cannot search email/phone** — `guests.email`/`phone` are `encrypted` casts on TEXT columns, never indexed by value (blind-index is a standing deferral; `docs/TIER1-SPEC.md` "Global Search cannot search guest email/phone"). The placeholder correctly promises only guest/reference/unit — **it is honest as drawn.** Design filters client-side over `guest + ref + unit + source`; real version must be a scoped query (`?q=` GET param, per invariant #20 — never a wire-action). |
| Clear-search "×" (`clearQuery`, `display: {{ clearDisplay }}`) | ✅ BUILD-NOW | Pure UI; shows only when `query` non-empty. | |
| Filter chip **All** | ✅ BUILD-NOW | No scope. | |
| Filter chip **In house** | ✅ BUILD-NOW | `status = 'checked_in'` (real enum value). | Design: `pIn.kind = 'inhouse'`. |
| Filter chip **Upcoming** | 🔨 NEEDS-BACKEND (**S**) | **Semantics are ambiguous in the design.** `pConf.kind = 'upcoming'` is set purely from *status = confirmed*, with **no date test** — the "Hansen family" row (12–17 Sep) is tagged `upcoming` while "Amara Okafor" (12–15 Sep) is `inhouse`. Needs a decision: `status='confirmed'` alone, or `status='confirmed' AND check_in >= today` (property-timezone today, per the time convention). | Needs a scope (e.g. `Reservation::scopeUpcoming`). Trivial once decided. |
| Filter chip **Tentative** | ✅ BUILD-NOW | `status = 'tentative'` (real enum value). | |
| Filter chip **Past** | 🔨 NEEDS-BACKEND (**S**) | Design lumps **`pOut` (checked_out) AND `pCanc` (cancelled)** both into `kind: 'past'`. **A cancelled booking is not "past"** — it can be a future stay. Needs a decision + scope. | Also: **`enquiry` and `no_show` match NO chip** — with a 5-chip design an enquiry/no-show reservation is invisible under every filter except All. Flag: either add chips or accept. |
| Filter chip active/inactive styling (`f.bg`/`f.shadow`/`f.color`) | ✅ BUILD-NOW | Pure UI. | |
| **Month picker "Sep 2025"** + chevron | 📋 DECORATIVE-FAKE (**INERT**) | **No `sc-camel-on-click`.** Pure markup. The label `Sep 2025` is a hard-coded literal — and it **contradicts the rows**, which are dated "12–15 Sep" with no year, over a demo DB seeded at **2026-09** (`DemoSeeder.php:182-184`). | To make real: 🔨 **S** — a `?month=YYYY-MM` GET param + a `check_in` range scope. But see the stat-strip trap: the tiles say "This month" while this button implies an arbitrary month — **the two disagree about what scopes the page.** Needs a decision before either is built. |
| **Select / Done** toggle (`toggleSelMode`) | ✅ BUILD-NOW | Pure UI state; clears `sel` + closes drawer/composer on exit. | |
| **Group booking** → `Operator Group Booking.dc.html` | ⬜ OTHER-MODULE | `App\Models\Group` + `groups` table exist (`name`, `block_code`, `pickup_deadline`, `contact`); `reservations.group_id` FK exists. But **no route, no component, and `docs/TIER1-SPEC.md` Wave 4 item 12 says Group "needs a small atomic-group + folio-rollup method"** on `AssignmentService`. | Separate Wave-4 design. Link out or render disabled "Soon". |
| **New reservation** CTA (primary) | ⬜ OTHER-MODULE | `AssignmentService::createBooking(array $reservationAttributes, array $lines)` is the real, certified write path — but the **wizard is `Operator New Reservation.dc.html`, a separate Wave-4 design** (TIER1-SPEC item 12). | Appears **3×** on this page (control row, no-results empty state, no-reservations empty state). Until Wave 4: render as a disabled "Soon" affordance (`ReservationRack` precedent: "New reservation … disabled 'Soon'"). |

### Stat strip (3 tiles) — ⚠️ every figure is a constant, and none of them reconcile

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Tile 1 label "THIS MONTH" | 🔨 NEEDS-BACKEND (**S**) | Scope decision — see below. | Conflicts with the "Sep 2025" picker. |
| Tile 1 figure `statCount` | 📋 DECORATIVE-FAKE | **Hard-coded**: `statCount: empty ? '0' : '64'`. | **Real derivation is easy** (`Reservation::count()` scoped) — but *which* date column? `booked_at` ("booked this month") vs `check_in` ("staying this month") are very different numbers and the design never says. **Ambiguous source → must be decided, not guessed.** |
| Tile 1 sublabel "reservations" | ✅ BUILD-NOW | Static copy. | |
| Tile 2 label "BOOKED VALUE" | 🔨 NEEDS-BACKEND (**S**) | Same scope decision. | |
| Tile 2 figure `bookedValue` | 📋 DECORATIVE-FAKE | **Hard-coded + animated**: `componentDidMount()` runs `const target = 18420; const dur = 800;` with a cubic ease-out `requestAnimationFrame` count-up into `state.bv`. | Real source exists: `SUM(reservations.total_minor)` — kept in sync with the append-only ledger by `FolioService::recompute()` (line 368-372: `$reservation->update(['total_minor' => $total, …])`). ⚠️ **Does it include cancelled?** `AssignmentService::cancelReservation()` → `terminate()` frees inventory + sets status but **never zeroes `total_minor`** — so a naive SUM silently counts cancelled bookings as "booked value". Must be decided. ⚠️ **Multi-currency:** `reservations.currency` is per-row; summing `total_minor` across currencies is arithmetically wrong (the owner screens group by currency — `OwnerUnitDetail` `$byCurrency` precedent). |
| Tile 3 label "BALANCES DUE" | ✅ BUILD-NOW | | |
| Tile 3 figure `balancesFig` | 📋 DECORATIVE-FAKE | **Hard-coded**: `balancesFig: empty ? VYC.fmt(0) : VYC.fmt(1161)`. | Real source: `SUM(reservations.balance_minor) WHERE balance_minor > 0` — synced by `FolioService::recompute()` and reconciling to `FolioTotals::for()` (`charges − paid`) to the cent. |
| Tile 3 sublabel `balancesSub` | 📋 DECORATIVE-FAKE | **Hard-coded**: `balancesSub: empty ? 'nothing due' : '3 folios'`. | Real: `COUNT(*) WHERE balance_minor > 0`. |
| Tile 3 colour `balancesCo` | ✅ BUILD-NOW | `'#C2410C'` when non-zero, `'#8A8079'` when zero — warm only, no red. Compliant with invariant #19. | |
| **🚩 THE RECONCILIATION TRAP** | 📋 DECORATIVE-FAKE | **The tiles do not reconcile to the rows they sit above.** Rows with a balance: €701 + €260 + €80 = **€1,041**. Tile 3 says **€1,161**. Off by €120. Row totals sum to €8,813 (1023+1890+1550+1240+1170+930+1010+0); Tile 2 says **€18,420**. Tile 1 says **64**; the table has **8** rows. | Exactly the Wave-1 non-reconciling-total pattern. `'3 folios'` *does* match the 3 rows with a balance — which makes the €1,161 look real while being invented. **Do not port any of these constants.** |

### Reservations table — header row

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Column **Guest** | ✅ BUILD-NOW | `Reservation::leadGuest()` (`lead_guest_id` → `guests`). | |
| Column **Stay** | ✅ BUILD-NOW | `reservations.check_in` / `check_out` (`date` casts, half-open). | |
| Column **Unit** | ✅ BUILD-NOW | `reservationUnits.unit` → `units.name`/`units.code`. | |
| Column **Source** | ⚠️ see rows | `reservations.source` — **brand is NOT modelled** (below). | |
| Column **Status** | ✅ BUILD-NOW | `reservations.status`. | |
| Column **Total** (right-aligned) | ✅ BUILD-NOW | `reservations.total_minor` + `currency`. | |
| Column 7 (16px, chevron spacer) | 📋 DECORATIVE-FAKE | Empty `<span>`. | |
| **Sorting** | 📋 DECORATIVE-FAKE (**ABSENT**) | **There is no sort anywhere on this page.** Headers are plain `<span>`s with no `sc-camel-on-click`, no aria-sort, no chevrons. | Honest statement: the design offers **zero** sort controls. The footer's "most recent first" is the only ordering claim (see footer). Don't invent sort UI; if wanted, 🔨 **S** (`orderBy` + a `?sort=` param). |
| **Select-all checkbox** (`toggleAll`, in `selMode` only) | ✅ BUILD-NOW | Pure UI. Real **tri-state**: `allCbBg` gradient when all / 20% tint when some / white when none; `allCheckPath` swaps `'M4.5 12.5 9.5 17.5 19.5 7'` (tick) ↔ `'M6 12h12'` (dash). `role="checkbox"` + `tabindex="0"` + `aria-label="Select all"`. | Scopes to **visible** rows only (`rowsRaw.map(r => r.ref)`) — correct behaviour with filters active. |
| Horizontal scroll container (`overflow-x: auto`, `min-width: 480px`) | ✅ BUILD-NOW | Pure CSS. | |

### Reservations table — rows (`sc-for list="{{ rows }}"`, 8 hard-coded entries)

⚠️ **The entire `all` array (lines 828-837) is fabricated demo data.** The real `DemoSeeder` (`DemoSeeder.php:182-184`) creates exactly **3** reservations via `AssignmentService::createBooking` (direct/walk_in/ota, 2026-09). **Do not port a single row.**

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Row click → open drawer (`r.open`) | ✅ BUILD-NOW | Client-side. `if (selMode) toggleRow(r.ref) else openDrawer(r)`. Guarded by `this._qa` so a quick-action click doesn't also open the drawer. | |
| Row keyboard open (`r.openKey`, Enter/Space) | ✅ BUILD-NOW | `role="button"` + `tabindex="0"` + `preventDefault()`. Genuine a11y. | Worth preserving. |
| Row hover in/out (`r.onEnter`/`r.onLeave` → `hoverIdx`) | ✅ BUILD-NOW | Drives the quick-action pill. | |
| Row selected background (`r.rowBg`) | ✅ BUILD-NOW | `rgba(217,102,46,.10)` when selected. | |
| Per-row checkbox (`r.cbBg`/`r.cbBorder`/`r.checkOp`, `selMode` only) | ✅ BUILD-NOW | Pure UI. | ⚠️ Design keys selection by **`r.ref`** (`sel[r.ref]`). Real code must key by **`reservations.id`** (ULID) — `reference` is only unique *per org*, and using it as a selection key invites a cross-org key collision in a shared UI state. |
| Avatar initials (`r.initials`) | ✅ BUILD-NOW | `FormatsOperatorData::initials(string $name)` — already exists (first + last initial, `mb_strtoupper`). | Reuse; don't rewrite. |
| **Guest name** (`r.guest`) | ✅ BUILD-NOW | `leadGuest->first_name . ' ' . last_name`. **PII-safe**: an operator surface may show guest NAMES (`FrontDesk.php:126` + `ReservationDetail.php:64` precedent, invariant #19 "Guest PII — names are shown; encrypted email/phone are never rendered"). The design renders **no** email/phone anywhere. ✅ | ⚠️ **Null-name fallback required**: `guests.first_name`/`last_name` are **nullable** and `Guest::anonymize()` **nulls them** (GDPR). Reuse the FrontDesk precedent: `$name !== '' ? $name : (string) $reservation->reference`. ⚠️ "**The Hansen family**" is a fabricated group-style name — no such shape exists (`first_name`/`last_name` only). |
| **Reference** (`r.ref`, e.g. `VYR-10428`) | ✅ BUILD-NOW | `reservations.reference` — "human code, unique per org", `unique(['organization_id','reference'])`. | The `VYR-104xx` values are fabricated; the column is real. |
| **Stay dates** (`r.dates`, "12–15 Sep") | ✅ BUILD-NOW | `check_in`/`check_out` (`date` casts). `ReservationDetail` formats `'j M Y'`; the design drops the year. | Compute "today" in the property's `timezone` (`properties.timezone`, default UTC) per the time convention. |
| **Nights · guests** (`r.nights`, "3 nights · 2 guests") | ✅ BUILD-NOW | DERIVED: `$checkIn->diffInDays($checkOut)` + `adults + children`. Exact precedent: `FrontDesk.php:153-154`. | |
| **Unit** (`r.unit`) | ✅ BUILD-NOW | `reservationUnits` (status `'active'`) → `unit->name ?? unit->code`, `->unique()->implode(', ')`. Exact precedent: `FrontDesk.php:132-135`. | Eager-load to avoid N+1 (`FrontDesk.php:122` precedent). |
| **Unit = "Unassigned"** | ✅ BUILD-NOW | `reservation_units.unit_id` is **nullable** by design ("null until a physical unit is assigned (e.g. fresh OTA import)"). Precedent: `FrontDesk.php:136` `$unassigned = $lines->isNotEmpty() && $lines->contains(fn ($l) => $l->unit_id === null)`. | Genuinely real, not invented. |
| **🚩 Source** (`r.source`: "Direct", "**Booking.com**", "**Expedia**", "**Airbnb**") | 🔨 NEEDS-BACKEND (**L**) *— or render "OTA"* | **THE OTA BRAND IS NOT MODELLED.** `reservations.source` enum is **exactly** `('direct','walk_in','ota','corporate','group','agent','manual')`. `channels.provider` enum is **exactly** `('channex')` — the channel *manager*, not the OTA. There is **no** brand column anywhere. To render a brand you'd need: a `channels.ota_brand`/`ota_code` column **+** brand extraction in `BookingImportService` **+** a Channex mapping — i.e. real OTA-brand modelling through the import path (**L**, and partly 🔴 Channex-gated). | **Recommended: ✅ render `sourceLabel()` — an OTA booking is "OTA", nothing more specific.** `FormatsOperatorData::sourceLabel()` already exists and does exactly this. Locked precedent: `OwnerUnitDetail.php:453` — *"⚠️ There is NO 'booking_com'/'airbnb': the OTA BRAND IS NOT MODELLED, so a brand can never be rendered here"*; same call in `OwnerBookingSummaryDocument.php:328`. ⚠️ Do **not** be fooled by `FormatsOperatorData::channelMeta()` returning `'booking_com' => 'Booking.com'` — that is the **`message_threads.channel` communication-medium enum** (a different concept, explicitly documented as *"NOT the OTA-distribution `Channel` model"* at `MessageThread.php:18`). Using it here would be a category error. |
| **Status pill** + dot (`r.status`, `r.pillBg`, `r.pillColor`, `r.pillDot`) | ✅ BUILD-NOW | **All 5 design statuses map to real enum values.** Vocabulary audit: "Checked in"→`checked_in` ✅ · "Confirmed"→`confirmed` ✅ · "Tentative"→`tentative` ✅ · "Checked out"→`checked_out` ✅ · "Cancelled"→`cancelled` ✅. **Zero invented statuses on this page** — a pleasant surprise vs Wave 1. Use `FormatsOperatorData::reservationStatusMeta()` (already covers all 7). | ⚠️ **Design has no pill for `enquiry` or `no_show`** — both are real enum values. `reservationStatusMeta()` already returns `['Enquiry','pending']` / `['No-show','no-show']`, so they render; but no filter chip catches them. Tones are all warm (terracotta/amber/muted) — invariant #19 compliant. |
| **"Refunded" badge** (`r.refunded`) | 🔨 NEEDS-BACKEND (**S**) | **Design mechanism is a cross-page mock**: `try { const d = JSON.parse(window.localStorage.getItem('vyrobel:refund:last')); refunded = !!(d && typeof d.amount === 'number' && d.amount > 0); } catch (e) {}` — reads localStorage written by the sibling *Operator Refund* design. 📋 as drawn. | **⚠️ THE DEAD-ENUM TRAP:** `reservations.payment_status` enum *contains* `'refunded'` — but **`FolioService::paymentStatus()` (line 376-383) only ever returns `'paid'`, `'partial'`, or `'unpaid'`.** `grep` confirms **nothing in `app/` ever writes `'refunded'` or `'charged_back'`** to that column. They are **dead enum values**. Keying the badge off `payment_status = 'refunded'` would render **never**, silently. **Real derivation:** `EXISTS(payments WHERE reservation_id = … AND type = 'refund' AND status = 'succeeded')` — matching `FolioTotals::for()` and `ReservationDetail::paymentEntry()`. Size **S** (a scope/`withExists`). |
| **Total** (`r.total`) | ✅ BUILD-NOW | `reservations.total_minor` + `reservations.currency`, formatted by `FormatsOperatorData::money(int $minor, string $currency)`. **Reconciles to the cent**: `FolioService::recompute()` writes `total_minor` from `SUM(folio_items.amount_minor)`, the same rows `FolioTotals::for()` reads. | ⚠️ "Pia Sørensen … Cancelled … **€0.00**" is fabricated — `cancelReservation()` → `terminate()` does **not** zero `total_minor`. A real cancelled booking keeps its total. |
| **Balance due** (`r.due`, "€701 due", `sc-if r.due`) | ✅ BUILD-NOW | `reservations.balance_minor` (same `recompute()` sync; `FrontDesk.php:140,157-159` precedent: `has_balance = balance_minor > 0`). Shown only when > 0. | Colour `#B4400F` — warm, compliant. |
| Row chevron "›" | 📋 DECORATIVE-FAKE | Static SVG. | |
| Row top border / hover lift / focus outline | ✅ BUILD-NOW | Pure CSS (`style-hover`, `style-focus` with `outline: 2px solid rgba(194,65,12,.4)`). | |

### Reservations table — hover quick-action pill (`data-qa="row-actions"`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Pill container reveal (`r.actOpacity`/`r.actPE`/`r.actTransform`) | ✅ BUILD-NOW | Shown when `!selMode && hoverIdx === i`; slides 9px→0. | ⚠️ **Hover-only = not keyboard/touch reachable.** The row itself is focusable but the pill isn't revealed on focus. Real build should add focus-within. |
| `qaDown` click guard | ✅ BUILD-NOW | Sets `this._qa = true` so `r.open` bails once. Paired with `stop` (`e.stopPropagation()`) on each link. | Belt-and-braces; fine. |
| **Check in** action (`r.ctxIn`) | ✅ BUILD-NOW | **Real write exists**: `POST route('app.front-desk.check-in', $reservation)` → `FrontDeskActionController::checkIn` → `FrontDeskService::checkIn(Reservation)` → `App\Actions\Reservations\CheckIn`. Gate: `can_check_in = status === 'confirmed'` (`FrontDesk.php:160`). | ⚠️ Design gates on `r.kind === 'upcoming'` (a fabricated field) and **links to the Front Desk page rather than acting**. Real build can do either — a guarded POST here (CSRF + PRG, invariant #20) or a link. Do **not** put it on a Livewire wire-action. |
| **Check out** action (`r.ctxOut`) | ✅ BUILD-NOW | **Real write exists**: `POST route('app.front-desk.check-out', $reservation)` → `FrontDeskActionController::checkOut` → `FrontDeskService::checkOut` → `App\Actions\Reservations\CheckOut`. Gate: `can_check_out = status === 'checked_in'` (`FrontDesk.php:161`). | Checkout dirties the unit via the real service/event chain (PROJECT-STATE, Tier-1 sweep 2026-07-13). |
| **Message guest** → `Operator Messages.dc.html` | ✅ BUILD-NOW *(as a plain link)* | `route('app.inbox')` → `App\Livewire\InboxList`. | 🔨 **S** if you want it to deep-link to *this guest's* thread: `MessageThread::where('guest_id', …)->latest('last_message_at')->first()` → `route('app.inbox.show', $thread)`, falling back to the list. If no thread exists, starting one needs `InboxService::startThread()` **+ a channel** — see the composer trap below. |
| **Open full reservation** → `Operator Reservation Detail.dc.html` | ✅ BUILD-NOW | `route('app.reservations.show', $reservation->id)` → `App\Livewire\ReservationDetail` — **built and styled**. | The one link on this page that lands on a finished screen. |

### Reservations table — footer

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| `footerCount` — "Showing N of **64** reservations · most recent first" | 🔨 NEEDS-BACKEND (**S**) | The **64** is the same hard-coded constant. The "N" is real (`rows.length`). Filter/query suffixes are real: `' · ' + parts.join(' · ')` where parts = the active chip label lowercased + `'“query”'`. | ⚠️ "**most recent first**" is an **ordering claim the design never implements** — the `all` array is in no particular order and there is no `sort()` anywhere. "Most recent" by what — `booked_at`, `created_at`, or `check_in`? **Ambiguous; must be decided.** (`booked_at` is nullable.) |
| Pagination **prev** button (‹) | 📋 DECORATIVE-FAKE (**INERT**) | **No `sc-camel-on-click`.** Pure markup. Rendered pre-greyed (`color: #A69A92`, no hover style) as if on page 1 — but nothing tracks a page. | |
| Pagination **next** button (›) | 📋 DECORATIVE-FAKE (**INERT**) | **No `sc-camel-on-click`.** Has a `style-hover` but no handler. | |
| **Pagination as a capability** | ✅ BUILD-NOW | `Reservation::query()->paginate()` + Livewire `WithPagination` — standard Laravel, no schema/service needed. | ⚠️ Honest note: **`grep` finds zero `paginate(`/`WithPagination` in the entire `app/` directory** — every built screen renders an unpaginated list. This page would be the **first** paginated surface. That's a new pattern (and it must use GET-param pagination, not a wire-action, to satisfy invariant #20's scoped-render rule). |

### Empty state 1 — no search results (`sc-if noResults` = `!empty && !hasResults`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Search-icon tile + radial glow | 📋 DECORATIVE-FAKE | — | |
| Heading: `No reservations match “{{ queryEcho }}”` | ✅ BUILD-NOW | `queryEcho: this.state.query.trim()` — real echo. | |
| Body copy: "Try a guest name, a reference like **VYR-10428**, or a unit — or clear the filters to see all **64**." | 📋 DECORATIVE-FAKE | Two hard-coded constants baked into prose: the fake reference `VYR-10428` and the fake count `64`. | Rewrite: use a real example reference (or none) + the real total. |
| "**Clear search**" button (`clearQuery`) | ✅ BUILD-NOW | Real handler. | ⚠️ Copy says "clear the **filters**" but the button only clears the **search** — the active chip stays. Minor honesty/UX bug in the design. |
| "**New reservation**" link | ⬜ OTHER-MODULE | Wave 4 (see control row). | |

### Empty state 2 — no reservations at all (`sc-if noReservations` = `empty`)

⚠️ **Unreachable in the design.** `state.empty` is initialised `false` and **nothing ever sets it** — no handler, no toggle, no prop. It is a dead design variant (`const source = empty ? [] : all;`). Real build will reach it naturally on a fresh tenant.

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Book-icon tile + glow | 📋 DECORATIVE-FAKE | — | |
| Heading: "No reservations yet" | ✅ BUILD-NOW | Real condition once wired to a real count. | |
| Body: "Once channels sync or you take a booking, every reservation lands here. Add one by hand, or connect **Booking.com and Airbnb** to fill the calendar automatically." | 📋 DECORATIVE-FAKE *(copy)* | Names OTA brands as if they were first-class integrations. The real integration is **Channex** (a channel *manager*), and the **Channex staging checkpoint is still OUTSTANDING** (🔴 account-gated; PROJECT-STATE "TWO live-integration checkpoints remain"). | Rewrite the copy honestly — don't promise brand connectors the product doesn't have. |
| "**New reservation**" CTA | ⬜ OTHER-MODULE | Wave 4. | |
| "**Connect channels**" → `Operator Channels.dc.html` | ⬜ OTHER-MODULE | No `app.channels` route; shell has `['channels', …, null]`. Wave 4 item 11 (needs "a thin channel/mapping CRUD controller"); **live sync is 🔴 Channex-gated**. | The empty state's *entire* value proposition points at two unbuilt screens. |
| Zeroed stat tiles in empty mode | ✅ BUILD-NOW | `statCount: '0'`, `bookedValue: VYC.fmt(0)`, `balancesFig: VYC.fmt(0)`, `balancesSub: 'nothing due'`, `balancesCo: '#8A8079'`. | The only *honest* numbers in the whole stat strip. |

### Bulk action bar (`sc-if showBulkBar` = `selMode && selCount > 0`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Fixed dark bar, `vyBar` slide-in, `left: calc(50% + 115px)` (sidebar offset) | ✅ BUILD-NOW | Pure CSS. | |
| `{{ selCount }}` + "selected" | ✅ BUILD-NOW | `selectedRows.length`. | |
| "**Clear**" button (`clearSel`) | ✅ BUILD-NOW | `setState({ sel: {} })`. | |
| Divider rule | 📋 DECORATIVE-FAKE | — | |
| "**Send messages**" button (`openMsg`) | 🔨 see composer | Opens the modal. | **⚠️ This is the ONLY bulk action on the page.** There is **no** bulk cancel, no bulk export/CSV, no bulk assign, no bulk check-in, no bulk status change, no bulk tag. Stating that explicitly so nothing is assumed missing later. |

### Bulk message composer modal (`sc-if msgOpen`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Backdrop + blur, click-to-close (`closeMsg`) | ✅ BUILD-NOW | | |
| Modal body click guard (`stop`) | ✅ BUILD-NOW | `e.stopPropagation()`. | |
| Header icon tile | 📋 DECORATIVE-FAKE | | |
| Title: "Message {{ selLbl }}" | ✅ BUILD-NOW | `selLbl = selCount + (selCount === 1 ? ' reservation' : ' reservations')` — real pluralisation. | |
| Subtitle: "Sent to the lead guest on each reservation" | ✅ BUILD-NOW *(concept)* | `reservations.lead_guest_id` is real. | |
| Close "×" (`closeMsg`) | ✅ BUILD-NOW | | |
| Recipient avatar stack (`recipAvatars`, first 3, `margin-left: -8px` overlap) | ✅ BUILD-NOW | `selectedRows.slice(0, 3)` + `initials`. | |
| `recipLine` — "Ana, Marta, Jia" / "Ana, Marta **+3 more**" | ✅ BUILD-NOW | Real: ≤3 → all first names; >3 → first 2 + `'+' + (selCount - 2) + ' more'`. | First names only — PII-safe. |
| **Templates** row ×3: "Check-in details", "Payment reminder", "Thank you" (`t.apply`) | 📋 DECORATIVE-FAKE *(content)* / 🔨 **M** *(as data)* | The three template **bodies are hard-coded string literals** in `renderVals()` (lines 880-882). No templates table, no `message_templates` model, nothing. | Two honest options: **(a)** ship the 3 as literal copy constants (they're product copy, not tenant data) — cheap and honest; **(b)** a real `message_templates` table + relation + CRUD → 🔨 **M**. ⚠️ The "Thank you" body asks the guest for "a quick **review** of your stay" — **Reviews is an unbuilt Tier-2 module** (⬜). Don't ship copy that promises a review flow. |
| Textarea (`msgText` / `onMsgText`) | ✅ BUILD-NOW | | |
| "**Guest name**" insert-token button (`insertName`) | ✅ BUILD-NOW | Appends `{name}` with smart spacing: `(t && !/\s$/.test(t)) ? t + ' {name}' : t + '{name}'`. | Pure client-side. In Livewire this wants Alpine, not a server round-trip per keystroke. |
| Hint: "Each guest is greeted by their own first name" | ✅ BUILD-NOW | | |
| **Preview panel** (`sc-if hasPreview` = `selCount > 0`) | ✅ BUILD-NOW | | |
| — "PREVIEW" label | 📋 DECORATIVE-FAKE | | |
| — Recipient picker avatars (`previewRecips`, first **6**, `p.pick` → `previewIdx`) | ✅ BUILD-NOW | Active/inactive styling via `p.bg`/`p.color`/`p.border`; `title="{{ p.title }}"` = full guest name. | |
| — `previewBody` — live `{name}` substitution with a highlighted pill | ✅ BUILD-NOW | `buildPreview()` splits on `/\{name\}/` and injects a styled `<span>` per token. Empty → italic "Your message preview will appear here…". | Genuinely nice UX; portable with Alpine. |
| — `resolveName()` group/fallback handling | ✅ BUILD-NOW | 3 branches: empty-or-`/^guest$/i` → `{name:'there', kind:'fallback'}`; `/\b(family\|party\|group\|\+)\b/i` or `/^the\s+/i` → strip "the", `kind:'group'`; else first token, `kind:'person'`. | **The `'there'` fallback is genuinely necessary and honest**: `guests.first_name` is nullable and `Guest::anonymize()` nulls it. Keep this logic. |
| — `previewSub` note (3 states) | ✅ BUILD-NOW | "No `{name}` in your message — add it to greet each guest by name" / "No name on file — this guest is greeted with 'there'" / "X is greeted as 'Y'". | Honest, real-condition-driven messaging. |
| — `previewNoteColor` (`#B4400F` warn / `#A69A92` calm) | ✅ BUILD-NOW | Warm only. | |
| `charLine` — "N characters" / "Personalize before you send" | ✅ BUILD-NOW | `canSend = !!msgText.trim()`. | |
| "**Cancel**" (`closeMsg`) | ✅ BUILD-NOW | | |
| Send button disabled styling (`sendBg`/`sendOp`/`sendCursor`) | ✅ BUILD-NOW | Greys to `rgba(120,70,40,.3)` @ `.55` opacity when empty. | |
| **🚩 "Send" button** (`sendMsg`) | 🔨 NEEDS-BACKEND (**M** orchestration) **+ ⬜/🔴 for honest delivery** | **Design calls no backend at all**: `sendMsg: () => { if (!this.state.msgText.trim()) return; const n = selCount; this.fireToast('Message sent to ' + n + ' guests'); this.setState({ msgOpen: false, msgText: '', selMode: false, sel: {} }); }` — a toast and a state reset. 📋 as drawn. | **See the `writes` + `traps` fields — this is the single biggest honesty decision on the page.** |

### Toast (`sc-if toast`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Toast card + tick icon, `vyToastIn` animation, 3.2s auto-dismiss | 📋 DECORATIVE-FAKE *(as a mock)* / ✅ *(as a pattern)* | `fireToast(msg)` + `setTimeout(… 3200)`. A design-level toast is a **mock, not a backend**. | The real equivalent already exists and is the house pattern: POST → controller → service → **redirect with a flash** (PRG), per invariant #20. Every built operator mutation uses it. |
| Toast text "Message sent to N guests" | 📋 DECORATIVE-FAKE | Asserts a send that never happened. | **Do not port this string until a real send exists.** |

### Booking detail drawer (`sc-if drawerMounted`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Scrim + fade (`scrimOpacity`), click-to-close (`closeDrawer`) | ✅ BUILD-NOW | | |
| Panel slide (`panelX` `translateX`, 340ms cubic-bezier), 432px / `92vw` | ✅ BUILD-NOW | Mount/unmount is properly sequenced (double `requestAnimationFrame` in, 340ms `setTimeout` out). | |
| **Escape-key cascade** (`componentDidMount` → `this._onKey`) | ✅ BUILD-NOW | Priority: `msgOpen` → `drawer` → `selMode`. Cleaned up in `componentWillUnmount`. | Genuine a11y; preserve. |
| Header: book icon + `d.ref` | ✅ BUILD-NOW | `reservations.reference`. | |
| Header close "×" (`closeDrawer`) | ✅ BUILD-NOW | | |
| Avatar 52px (`d.initials`) | ✅ BUILD-NOW | `FormatsOperatorData::initials()`. | |
| Guest name (`d.guest`) | ✅ BUILD-NOW | `leadGuest` name; PII-safe. | Same null-name fallback needed. |
| Status pill (`d.status`/`d.pillBg`/`d.pillColor`/`d.pillDot`) | ✅ BUILD-NOW | `reservationStatusMeta()`. | |
| Source line (`d.source`) | 🔨 see rows | Same OTA-brand trap. | |
| **Stay card** — "CHECK-IN" `d.checkIn` / arrow / "CHECK-OUT" `d.checkOut` | ✅ BUILD-NOW | Real columns `check_in`/`check_out`. | ⚠️ Design **regex-parses its own display string**: `const dm = dr.dates.match(/(\d+)\s*[–-]\s*(\d+)\s+([A-Za-z]+)/)`. A pure design hack — read the date columns. Also **no year is rendered**; real dates have years (`ReservationDetail` uses `'j M Y'`). |
| `d.nightsLabel` / `d.guestsLabel` | ✅ BUILD-NOW | DERIVED (`diffInDays`, `adults + children`). | ⚠️ Design splits its own string: `dr.nights.split('·')`. Another hack. |
| **Unit card** — assigned (`d.assigned` → `d.unit`) | ✅ BUILD-NOW | `reservationUnits.unit->name ?? code`. | `assigned: dr.unit !== 'Unassigned'` — string comparison; real version tests `unit_id !== null`. |
| **Unit card** — "Unassigned" (`d.unassigned`) + "**Assign unit →**" link | ✅ BUILD-NOW *(as a link)* | Links to the Calendar/rack — `route('app.rack')`. The real write is `AssignmentService::assignUnit(ReservationUnit $reservationUnit, string $unitId)` (the single guarded inventory write path). | ⚠️ **Honest caveat**: the rack's **drag-to-assign is DEFERRED** (`ReservationRack` header: *"drag-to-reschedule … shown as a labelled 'not yet available' note, never wired"*). So "Assign unit →" lands on a screen that **cannot yet assign**. Either flag it honestly or accept the dead-end. Do **not** invent an assign control here. |
| **🚩 "Channel" card** — renders `{{ d.source }}` | 🔨 NEEDS-BACKEND (**S**) *— or drop the card* | **Category error in the design**: the card is labelled "**Channel**" but is fed `source`. They are different columns. `reservations.channel_id` (nullable FK → `channels`) is the real channel; for a Direct/walk-in booking it is **NULL**, so rendering "Direct" under "Channel" is simply wrong. | Real options: (a) drop the card (the source already shows in the header); (b) render `channel->name` when `channel_id` is set, else "—"/"Direct booking". Note `channels.name` is the operator's own label for the **Channex connection**, not an OTA brand. |
| **Payment card** — "Total" `d.totalFmt` | ✅ BUILD-NOW | `reservations.total_minor` (synced by `FolioService::recompute()`). | |
| **Payment card** — "Paid" `d.paidFmt` | ✅ BUILD-NOW *(real source exists)* | **Design fabricates it by string-parsing**: `const num = (s) => parseFloat(String(s).replace(/[^0-9.]/g, '')) \|\| 0; const paidNum = Math.max(0, totalNum - dueNum);` — derived from the *rendered strings*, not data. 📋 mechanism. | **Real source**: `folios.paid_minor`, or `FolioTotals::for($folio)['paid_minor']` (= Σ settled payments − Σ succeeded refunds). Use it; never `total − due`. |
| **Payment card** — "Balance due" `d.dueFmt` + `d.dueColor` | ✅ BUILD-NOW | `reservations.balance_minor`; `dueColor = #C2410C` when >0 else `#8A8079`. Warm only. | |
| "**Charge balance**" (`sc-if d.hasDue`) → Reservation Detail | ✅ BUILD-NOW *(as a link)* | `route('app.reservations.show', $id)` — where **record-payment is already REAL**: `POST route('app.reservations.payment')` → `ReservationFolioActionController::recordPayment` → `FolioService::recordPayment(Folio, Money, ?string $cardToken, array $metadata)`. | A direct charge *from the drawer* would need an amount + card token + gateway flow → **DEFERRED** (exact `FrontDesk` precedent: *"'Collect payment' (needs an amount + card-token + gateway flow — out of a presentation pass) … never fakes a payment"*). Keep it a link. |
| Refund callout "A refund was issued on this booking" (`sc-if d.refunded`) | 🔨 NEEDS-BACKEND (**S**) | Same `localStorage` mock as the row badge; same real derivation (`payments.type='refund' AND status='succeeded'`). | |
| Footer "**Message**" → Operator Messages | ✅ BUILD-NOW | `route('app.inbox')`. | |
| Footer "**Open full reservation**" → Reservation Detail | ✅ BUILD-NOW | `route('app.reservations.show', $id)` — built + styled. | |

### Cross-cutting design scaffolding

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| `_reeur(s)` currency re-formatter | 📋 DECORATIVE-FAKE | A design harness that **regex-rewrites rendered € strings**: `s.replace(/€\s?(\d[\d,]*(?:\.\d+)?)(k\|M)?/g, …)` multiplying by `VYC.rate()`. Pure demo scaffolding for the design tool's currency switcher. | Real money is `*_minor` bigint + ISO currency via the `Money` cast, formatted once by `FormatsOperatorData::money()`. Never regex money. |
| `window.VYC` / `VYC.fmt()` / `VYC.rate()` | 📋 DECORATIVE-FAKE | Design-tool global. | |
| `motion` prop (`data-props`, boolean, default true) | 📋 DECORATIVE-FAKE | Design-editor prop gating one orb's animation. | |
| **Multi-currency handling** | 🔨 NEEDS-BACKEND (**S**) | Design assumes € throughout. Real: `reservations.currency` is **per-row** (`char(3)`). | Summing `total_minor`/`balance_minor` across currencies is arithmetically invalid. The built precedent groups by currency (`OwnerUnitDetail` `$byCurrency` → a display **per** currency). Decide before building the stat strip. |

### Explicitly ABSENT from this design (so nothing is assumed)

| Feature | Tag | Notes |
|---|---|---|
| Column sorting (any) | 📋 ABSENT | Zero sort handlers; headers are plain spans. |
| CSV / export button | 📋 ABSENT | Not on this page at all. (CSV is a **standing deferral** per `docs/TIER1-SPEC.md`; `grep` confirms no CSV writer exists — only statement/folio **PDF** via `PdfService`.) |
| Bulk cancel / bulk assign / bulk check-in / bulk tag | 📋 ABSENT | "Send messages" is the only bulk action. |
| Column chooser / density / saved views | 📋 ABSENT | |
| Date-range filter (beyond the inert month button) | 📋 ABSENT | |
| Source/channel filter chips | 📋 ABSENT | Only the 5 status-ish chips. |
| Guest email/phone rendering | 📋 ABSENT ✅ | **Good** — the design never renders encrypted PII anywhere. |
| Per-row cancel / no-show | 📋 ABSENT | `AssignmentService::cancelReservation()` / `markNoShow()` exist but this page offers neither (Wave 2 sibling designs *Operator Reservation Cancelled* / *No-show* cover the terminal states). |

## WRITES

**The page has exactly ONE write-shaped action in the design, and it is not wired to anything.**

1. **"Send" in the bulk message composer** (`sendMsg`) — design does: `fireToast('Message sent to N guests')` + `setState({msgOpen:false, msgText:'', selMode:false, sel:{}})`. **No network call. No backend. 📋 as drawn.**
   **NEEDS: a bulk-send orchestration method — `InboxService::sendBulk(iterable $reservations, string $bodyTemplate, ?User $author)` (or a `BulkGuestMessageService`) — does not exist. (M)**
   It would have to, per selected reservation: resolve `lead_guest_id` → find an existing `MessageThread` (`where('guest_id', …)`) or `InboxService::startThread(['channel'=>…, 'guest_id'=>…, 'reservation_id'=>…, 'participant_identity'=>…])` → substitute `{name}` from `guests.first_name` (with the `'there'` fallback) → `InboxService::sendOutbound(MessageThread $thread, string $body, ?User $author)`.
   **⚠️ THE BLOCKER (see `traps`): `sendOutbound()` sends over the THREAD'S channel. `ManualChannel::send()` returns `successful: true, status: 'delivered'` — but its own docblock says "recording IS the delivery … no external provider". It reaches NOBODY. Every channel that actually reaches a guest (`EmailChannel`, `WhatsAppChannel`, `BookingComChannel`, `AirbnbChannel`) is a `DeferredChannel` skeleton that THROWS "not configured" (Tier-3, account-gated).** So a bulk send over `manual` would record N messages, mark them "delivered", and fire "Message sent to N guests" — while no guest ever receives anything. That is a faked money-shaped action in messaging clothing and would violate invariant #19 + #24.

**Writes that the quick-actions/drawer LINK to, which are REAL and already built (reuse, don't rebuild):**
2. **Check in** — `POST route('app.front-desk.check-in', $reservation)` → `FrontDeskActionController::checkIn` → **`FrontDeskService::checkIn(Reservation $reservation)`** → `App\Actions\Reservations\CheckIn`. Gate: `status === 'confirmed'`.
3. **Check out** — `POST route('app.front-desk.check-out', $reservation)` → `FrontDeskActionController::checkOut` → **`FrontDeskService::checkOut(Reservation $reservation)`** → `App\Actions\Reservations\CheckOut`. Gate: `status === 'checked_in'`.
4. **Charge balance** (drawer) — links to Reservation Detail, where `POST route('app.reservations.payment')` → `ReservationFolioActionController::recordPayment` → **`FolioService::recordPayment(Folio $folio, Money $amount, ?string $cardToken = null, array $metadata = [])`** is real and append-only.
5. **Assign unit** (drawer) — links to the rack. Real write is **`AssignmentService::assignUnit(ReservationUnit $reservationUnit, string $unitId)`** — the single guarded inventory write path — but **no UI calls it** (rack drag is deferred). The link lands on a screen that cannot yet assign.

**Answering the brief's question about `refundPayment`:** **`FolioService::refundPayment(Payment $original, Money $amount)` — PARTIAL: YES** (it takes an explicit `Money $amount` and validates `if ($amount->amountMinor() > $original->amount_minor) throw` — i.e. any amount ≤ the original is allowed). **REASON: NO** — there is no reason/metadata param; it hard-writes `'metadata' => ['refunds_payment_id' => $original->getKey()]`. So the dedicated **Refund** design (Wave 2 item 8, "partial + reason") needs **🔨 S: a `?string $reason = null` param threaded into `metadata`** — a genuinely small, additive change. *(Not this page's problem — flagging it because the brief asked and because the sibling page depends on it.)*

**Discipline reminder for whatever is built here:** per invariant #20, any mutation must be `POST → controller → service → redirect (PRG)` with CSRF + a tenant-scoped `findOrFail` from a **string** id — **never** a Livewire wire-action (which re-renders via `/livewire/update` and bypasses the route middleware → scope leak). Filters/search/pagination must ride **GET params**, not wire-actions, for the same reason.

## ISOLATION / PII

**Tenant scoping — straightforward, no owner boundary.**
This is a **pure operator surface**: `Reservation`, `Guest`, `Unit`, `Folio`, `Payment`, `MessageThread` all use `BelongsToOrganization`, so `SetTenantForWebRequest`'s active org auto-scopes every read. `OwnerScope` is **inactive off-portal** and must stay that way — there is no owner/tenant boundary on this page. Precedent: `ReservationRack` — *"TENANT-scoped (operator surface), NOT owner-scoped."*

**The `/app` gate covers it automatically.** The route must go inside the existing `Route::middleware(['auth', EnsureBackOfficeAccess::class])->prefix('app')` group — the gate is **on the GROUP**, and there is a test asserting **every** `app/` route carries it, so a new route cannot forget it (invariant #26, `docs/DECISIONS.md` 2026-07-16). No per-route work needed.

**Scoped-render rule (invariant #20).** `{reservation}` in the drawer/detail links is a **string** id resolved via a tenant-scoped `findOrFail` in `render()`/the controller — never a route-model-bound prop (RMB resolves during `SubstituteBindings`, *before* the scope middleware). Cross-org id → 404. The list itself needs no id resolution; its query is scoped by the global scope.

**PII — the design is clean, with two required guards.**
- ✅ **Guest NAMES are shown, and that is correct** for an operator surface. Locked precedent: `FrontDesk.php:126` + `ReservationDetail.php:64`, and invariant #19: *"Guest PII — names are shown (front-desk surface); encrypted email/phone are never rendered."* (The owner-surface no-names rule does **not** apply here.)
- ✅ **The design renders NO email/phone anywhere** — not in the table, not in the drawer, not in the composer (which shows first names only). Nothing to strip. `guests.email`/`phone` are `encrypted` casts + in `Guest::$hidden`.
- ⚠️ **GUARD 1 — anonymized guests.** `guests.first_name`/`last_name` are **nullable** and `Guest::anonymize()` **nulls them** (GDPR erase, never soft-delete). An anonymized guest renders a blank name + blank initials. Reuse the FrontDesk fallback: `$name !== '' ? $name : (string) $reservation->reference`. The composer's own `resolveName()` already handles this correctly (`→ 'there'`), which is a point in its favour.
- ⚠️ **GUARD 2 — the composer would need PII the design never shows.** To actually send, `startThread()` needs `participant_identity` = the guest's email/phone. That is exactly the encrypted PII the page carefully avoids rendering — it would be *read* (decrypted) server-side and passed to a channel adapter. That's legitimate (the identity column is itself an encrypted cast and is documented "never rendered"), but it means the composer is **not** the PII-free feature it looks like. Worth naming before building.
- ⚠️ **GUARD 3 — search.** Searching guest **name** is fine (plain columns). Searching **email/phone is impossible** — encrypted TEXT, never indexed by value; blind-index is a standing deferral. The placeholder ("guest, reference or unit") is honest; keep it that way and don't quietly add an email search that silently matches nothing.
- ⚠️ **Selection key.** The design keys selection by `r.ref` (`sel[r.ref]`). `reference` is only unique **per org** (`unique(['organization_id','reference'])`). Use the ULID `id`.

## TRAPS (Wave-1 style — reconciliation, scoping, real enums, fabrication)

**1. 🚩 THE OTA BRAND — the page's headline lie.** The Source column renders "**Booking.com**", "**Expedia**", "**Airbnb**". None exist. `reservations.source` enum is **exactly** `('direct','walk_in','ota','corporate','group','agent','manual')`; `channels.provider` enum is **exactly** `('channex')` — the channel *manager*, not the OTA. There is **no brand column anywhere in the schema**. Render `sourceLabel()` → an OTA booking is "**OTA**". Locked precedent, twice: `OwnerUnitDetail.php:453` (*"⚠️ There is NO 'booking_com'/'airbnb': the OTA BRAND IS NOT MODELLED"*) and `OwnerBookingSummaryDocument.php:328`. **Decoy to avoid:** `FormatsOperatorData::channelMeta()` *does* map `'booking_com' => 'Booking.com'` — but that is the **`message_threads.channel` communication-medium enum**, explicitly documented at `MessageThread.php:18` as *"NOT the OTA-distribution `Channel` model"*. Using it on this column would look right and be wrong.

**2. 🚩 FIGURES THAT MUST RECONCILE — and in the design, don't.**
- Tile 3 "Balances due" = **€1,161** / "3 folios". The visible rows due: €701 + €260 + €80 = **€1,041**. **Off by €120.** The "3 folios" *does* match the 3 rows — which is what makes the wrong number look credible.
- Tile 2 "Booked value" = **€18,420** (a `componentDidMount` count-up to `const target = 18420`). Row totals sum to **€8,813**.
- Tile 1 "This month" = **64**. The table has **8** rows. "64" is also hard-baked into the footer string *and* the no-results copy.
- **Named real sources** (all reconcile to the cent — `FolioService::recompute()` writes them from the same rows `FolioTotals::for()` reads): Total → `reservations.total_minor`; Balance → `reservations.balance_minor`; Paid → `folios.paid_minor` / `FolioTotals::for()['paid_minor']`. **Never** `total − due` (the design's string-parsing hack at drawer line 933).

**3. 🚩 AGGREGATE SCOPE IS AMBIGUOUS — three unanswered questions.**
- **Which month?** Tiles say "This month"; the picker says "Sep 2025" — they disagree about what scopes the page. And the picker is inert.
- **Which date column?** "This month" by `booked_at` (booked this month) or `check_in` (staying this month)? Utterly different numbers. `booked_at` is nullable.
- **Cancelled included?** `AssignmentService::cancelReservation()` → `terminate()` frees inventory + sets status but **never zeroes `total_minor`**. A naive `SUM(total_minor)` silently counts cancelled bookings as booked value. (The design's own cancelled row shows a fabricated **€0.00** — real cancels keep their total.)
- **Multi-currency:** `reservations.currency` is per-row; a cross-currency `SUM(*_minor)` is invalid. Built precedent groups by currency (`OwnerUnitDetail` `$byCurrency`).

**4. 🚩 THE DEAD-ENUM TRAP — "Refunded".** `reservations.payment_status` enum contains `'refunded'` and `'charged_back'`. **`FolioService::paymentStatus()` (line 376-383) only ever returns `'paid'`/`'partial'`/`'unpaid'`, and `grep` confirms nothing in `app/` ever writes those two values.** They are **dead enum values**. Keying the badge off `payment_status = 'refunded'` compiles, passes review, and renders **never** — a silent no-op. Correct derivation: `EXISTS(payments WHERE reservation_id = … AND type = 'refund' AND status = 'succeeded')`, matching `FolioTotals::for()` and `ReservationDetail::paymentEntry()`. *(The design's own mechanism is `window.localStorage.getItem('vyrobel:refund:last')` — a cross-page design mock.)*

**5. ✅ STATUS VOCABULARY — clean (the good news).** All 5 design statuses key to **real** `reservations.status` enum values: Checked in→`checked_in`, Confirmed→`confirmed`, Tentative→`tentative`, Checked out→`checked_out`, Cancelled→`cancelled`. **Zero invented statuses** — unlike Wave 1's "Scheduled"/"On hold"/"In review". Use `FormatsOperatorData::reservationStatusMeta()`, which already covers all **7** (incl. `enquiry`, `no_show` — which the design forgot). But the **filter `kind` field IS fabricated**: `pIn/pConf/pTent/pOut/pCanc` carry an invented `kind: 'inhouse'|'upcoming'|'tentative'|'past'` that exists nowhere in the schema — and it **lumps `cancelled` in with `checked_out` as "past"** (a cancelled *future* stay is not past), and defines "upcoming" as *status=confirmed with no date test*.

**6. 🚩 THE BULK-SEND TRAP — the most dangerous thing on the page.** `sendOutbound()` sends over the thread's channel. `ManualChannel::send()` returns `successful: true, status: 'delivered'` — its own docblock: *"recording IS the delivery … no external provider"*. **It reaches nobody.** Every channel that reaches a real guest (`EmailChannel`/`WhatsAppChannel`/`BookingComChannel`/`AirbnbChannel`) is a `DeferredChannel` that **throws "not configured"** (Tier-3, account-gated: needs an inbound provider + verified domain / Business API + BSP number / OTA messaging API). So wiring "Send messages" over `manual` produces N rows marked **delivered**, a toast saying **"Message sent to 5 guests"**, and **zero guests messaged**. It would look completely real and be completely fake — violating invariant #19 (never a fabricated action) and #24 (the composer must honestly reflect a channel's connectedness). The built Inbox already solved this correctly: a deferred channel → an honest **"not connected"** composer + an honest **FAILED** delivery state. **Match that, or defer the composer.**

**7. INERT CONTROLS (real count).** 23 `sc-camel-on-click` — but the ones that matter and do **nothing**: the **month picker "Sep 2025"** (no handler), **pagination prev** (no handler), **pagination next** (no handler). Also **zero sort handlers** anywhere. And `state.empty` is initialised `false` and **never set by anything** — the "No reservations yet" empty state is **unreachable dead design**. Of the 23 handlers, 5 are `stop` (`stopPropagation`) and 5 are close/dismiss — so the genuine interaction surface is ~13.

**8. FABRICATED DATA NOT TO PORT.** The whole 8-row `all` array (lines 828-837): guests "Amara Okafor"/"Marta Kowalska"/"**The Hansen family**" (no such name shape exists — `first_name`/`last_name` only)/"Jia Chen"/"Katrin Novak"/"Lukas Meyer"/"James Okonkwo"/"Pia Sørensen"; refs `VYR-10419`…`VYR-10438`; units "Villa Azure"/"Villa Coralis"/"Villa Dunes"/"Apt 102"/"Apt 103"; the cancelled row's **€0.00**. Real `DemoSeeder.php:182-184` seeds **3** reservations (direct/walk_in/ota, 2026-09). Also fabricated: **"read-only until 5 Oct"**, the **`VYR-10428`** in the empty-state copy, the nav badges **"2"** and **"2"**, and the 3 hard-coded message-template bodies.

**9. THE BROKEN PLACEHOLDER.** The paused banner contains `<span data-vyc-zero=""></span>` — an **empty span**; the sentence renders as *"channels stopped selling ·  while paused"*. An unfilled currency-harness placeholder. Nobody proofread this banner.

**10. THE "Channel" CARD IS A CATEGORY ERROR.** The drawer's card is labelled **"Channel"** but is fed `{{ d.source }}`. `source` and `channel_id` are different columns; for a Direct booking `channel_id` is **NULL**, so "Direct" under "Channel" is wrong by construction.

## BUILD-READINESS VERDICT

**~70% ✅ BUILD-NOW · ~12% 🔨 (mostly S) · ~13% 📋 · ~5% ⬜. Recommendation: BUILD NOW — but drop the stat strip's numbers, render "OTA" not brands, and defer the composer's Send.**

**Why it's strong.** The table core — the actual point of the page — is a clean read over `Reservation` with **every column already backed and already precedented**: guest name + initials, reference, stay dates, nights·guests, unit-or-Unassigned, status pill, total, balance due. `App\Livewire\FrontDesk` is a near-perfect template (same joins, same eager-load, same null-name fallback, same `can_check_in`/`can_check_out` gates), and `FormatsOperatorData` already provides `initials()`, `money()`, `sourceLabel()`, `reservationStatusMeta()` — reuse, don't rewrite. Money reconciles to the cent for free: `FolioService::recompute()` syncs `reservations.total_minor`/`balance_minor` from the same ledger rows `FolioTotals::for()` reads. Both quick-actions (check-in/check-out) hit **real, built, guarded** POST routes. "Open full reservation" lands on a **finished** screen. The status vocabulary is **100% real enum** — a genuine improvement on Wave 1. And the design renders **no encrypted PII anywhere**.

**The three things that must not be built as drawn.**
1. **Source = "Booking.com"/"Expedia"/"Airbnb"** → render **"OTA"**. The brand is not modelled; `channels.provider` only has `'channex'`. Precedent locked twice (`OwnerUnitDetail.php:453`, `OwnerBookingSummaryDocument.php:328`). Beware the `channelMeta()` decoy — that's the messaging-medium enum, a different concept.
2. **The stat strip** — all three figures are constants and **none reconcile** to the rows beneath them (€1,161 vs €1,041; €18,420 vs €8,813; 64 vs 8). The derivations are easy but the **scope is genuinely ambiguous** (which month? `booked_at` or `check_in`? cancelled in or out? multi-currency?). **Decide first, then build** — or ship the tiles honestly reduced to what's unambiguous (e.g. Balances due, per currency, all-time-outstanding).
3. **The bulk composer's Send** — wiring it over `ManualChannel` would record N "delivered" messages, toast "Message sent to 5 guests", and reach **nobody**; every guest-reaching channel throws "not configured" (Tier-3 gated). **Either** ship the composer with an honest "not connected" state + FAILED delivery (exactly what the built Inbox already does — invariants #24), **or** defer the whole Send-messages bulk action and ship Select-mode without it. Do not fake it.

**Suggested shape.** One gate, `App\Livewire\OperatorReservations` on `<x-operator.shell active="reservations">`, `GET /app/reservations` inside the existing `['auth', EnsureBackOfficeAccess::class]` group (the gate is on the group — a test asserts every `app/` route carries it). Filters/search/pagination on **GET params**, never wire-actions (invariant #20). Flip the shell's `['reservations', …, null]` → `route('app.reservations')`. Reuse the FrontDesk presenter almost verbatim. First paginated screen in the repo — use `paginate()` + GET-param pagination.

**Decisions needed before/at build (flag to the human):** (a) "Upcoming"/"Past" chip semantics — is cancelled "past"? (b) stat-tile scope + date column + cancelled-inclusion + multi-currency; (c) composer: honest-stub or defer entirely; (d) the paused banner — **recommend omitting** (it would be a fabricated status); (e) default sort column for "most recent first".

**Ship-now subset if you want the fastest honest win:** the table + search + chips + drawer + quick-actions + both empty states + pagination, with the stat strip cut to one unambiguous tile and Select-mode shipped without Send. That is ~70% of the page, entirely real, zero new backend.

---

# 2. Reservation — CANCELLED state (operator)

**Design file:** `docs/design/raw/Operator Reservation Cancelled.html` — 1,541,162 B · template 44,868 · **0 onClick · 0 sc-for** (static state page)

**What it is.** A STATIC state page — verified: **0 `sc-camel-on-click`, 0 `sc-for`, 0 `data-toast`**. The only dynamic construct in the whole file is one `sc-if value="{{ motion }}"` gating a decorative background orb (the sole prop: `{"motion": {"editor":"boolean","default":true}}`). Every one of the 3 `<button>`s and all 20 `<a href>`s are markup; no handler, no state class, no logic. Structure: operator shell (sidebar + property switcher + notif bell) → paused-workspace banner → breadcrumb → title row (guest name + "Cancelled" pill + 2 buttons) → cancellation banner → two columns: LEFT = 7-tile stay summary + a closed folio ledger (3 rows + a 3-figure footer); RIGHT = a dark "Folio closed" panel + guest card + a "Cancellation record" card. It is a read-only variant of a screen that is ~80% already built — the genuinely NEW surface is the cancellation provenance (when/why/who), the folio-closed lifecycle, and the policy/fee record.

## Feature inventory — every element

**Verification note:** every ✅ below was checked by reading the named file. The page adds little net-new *data* — it mostly re-dresses `App\Livewire\ReservationDetail` (`app/Livewire/ReservationDetail.php` + `resources/views/livewire/reservation-detail.blade.php`). **Reuse that component's `buildFolio()`/`paymentEntry()` and `App\Support\Folio\FolioTotals` — do not build a second folio renderer.**

### Shell / sidebar (mostly already built — reuse `<x-operator.shell>`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Vyrobel logo mark "V" + wordmark | ✅ BUILD-NOW | `resources/views/components/operator/shell.blade.php` (`.vy-brand`) | Already in the shell. |
| Property switcher (`<dc-import name="Property Switcher" hint-size="100%,52px">`) | 🔨 NEEDS-BACKEND (M) | Data exists (`properties` table, `property_user` pivot w/ FK from `2026_07_02_001140_add_property_fk_to_property_user.php`); needs a session-scoped "active property" + a `PropertySwitcher` component + every operator screen honouring it. **No switcher exists anywhere in the repo** (grepped). | Out of scope for one page — flag as a cross-cutting shell decision. |
| Nav group "Overview" → Dashboard | ✅ BUILD-NOW | `route('app.dashboard')` → `App\Livewire\Operator\OperatorDashboard` | |
| Nav → Calendar | ✅ BUILD-NOW | `route('app.rack')` → `App\Livewire\ReservationRack` | Shell already labels this "Calendar". |
| Nav → Insights | ⬜ OTHER-MODULE | No route, no analytics module. | Defer. |
| Nav group "Front desk" → Reservations (rendered ACTIVE on this page) | 🔨 NEEDS-BACKEND (S) | No index route exists; shell has it as `['reservations', …, null]` = disabled "Soon". The Wave-2 `Operator Reservations` page supplies it. | Once that lands, set `active="reservations"`. |
| Nav → Arrivals & departures | ✅ BUILD-NOW | `route('app.front-desk')` → `App\Livewire\FrontDesk` | Shell calls it "Front desk". |
| Nav → Guests | 🔨 NEEDS-BACKEND (S) | No route. Wave-2 `Operator Guests` page supplies it. | |
| Nav → Messages | ✅ BUILD-NOW | `route('app.inbox')` → `App\Livewire\InboxList` | Shell calls it "Inbox". |
| Messages nav badge **"2"** | 📋 DECORATIVE-FAKE | Hard-coded literal `2` in the markup. Real unread data DOES exist (`InboxList.php:97` `->where('unread_count','>',0)->count()`) but is not exposed shell-wide → a real badge is 🔨 S. | Don't ship the literal. |
| Nav group "Operations" → Housekeeping | ✅ BUILD-NOW | `route('app.housekeeping')` → `App\Livewire\HousekeepingBoard` | |
| Nav → Maintenance | ✅ BUILD-NOW | `route('app.maintenance')` → `App\Livewire\MaintenanceList` | |
| Maintenance nav badge **"2"** | 📋 DECORATIVE-FAKE | Hard-coded literal `2`. Real open-ticket count is derivable from `maintenance_tickets.status='open'` → 🔨 S. | |
| Nav → Rates & availability | 🔨 NEEDS-BACKEND (M) | Shell has `['rates', …, null]` = "Soon". `RateAndRestrictionService` + `rate_calendar` exist; no screen. | Wave 4. |
| Nav → Channels | 🔨 NEEDS-BACKEND (M) | Shell has `['channels', …, null]` = "Soon". `channels` table + Channex svc exist; no screen. **Channex staging checkpoint still outstanding.** | Wave 4. |
| Nav group "Finance" → Owners | ✅ BUILD-NOW | `route('app.owners')` → `App\Livewire\Operator\OperatorOwners` | |
| Nav → Reports | ⬜ OTHER-MODULE | No route, no reporting module. | Defer. |
| Nav group "System" → Settings | 🔨 NEEDS-BACKEND (M) | Shell has `['settings', …, null]` = "Soon". | |
| Nav → Security | 🔨 NEEDS-BACKEND (M) | Shell has `['security', …, null]` = "Soon". 2FA needs a backend gate first (per `docs/ROADMAP.md`). | |
| Notif Bell (`<dc-import name="Notif Bell">`) | 📋 DECORATIVE-FAKE | Shell already renders an honestly-disabled bell: `<span class="vy-bell" title="Notifications — coming soon">`. No notifications table. | Keep the disabled precedent. |
| Sidebar nav grouping (Overview/Front desk/Operations/Finance/System) | 📋 DECORATIVE-FAKE | Design's 5 groups ≠ shell's 4 (`OPERATIONS`/`DISTRIBUTION`/`OWNERS`/`ACCOUNT`). | Cosmetic divergence — do NOT re-group the shell for one page. |

### Paused-workspace banner (top of main)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Banner container + pause (❙❙) glyph | ⬜ OTHER-MODULE | Billing/subscription module. | Whole banner defers. |
| Headline "Workspace paused — **read-only until 5 Oct**" | ⬜ OTHER-MODULE | `subscriptions.status` enum DOES include `'paused'` (`2026_07_02_000400_create_subscriptions_tables.php`) and `current_period_end` exists — but there is **no billing screen, no pause flow, and no read-only enforcement anywhere**. "5 Oct" is a hard-coded string. | The enum value is real; the feature is not. |
| Sub-line "Viewing is fine, changes are off · channels stopped selling · `<span data-vyc-zero>` while paused" | 📋 DECORATIVE-FAKE | `data-vyc-zero` is an **empty currency span** — it renders nothing/zero. Broken or placeholder copy in the design itself. | Flag: the sentence is literally incomplete. Do not port. |
| "Resume now" button → `Operator Billing.dc.html` | ⬜ OTHER-MODULE | No billing route (`php artisan route:list` has none). INERT in the design. | |

### Breadcrumb + title row

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Breadcrumb "‹ Reservations" link | 🔨 NEEDS-BACKEND (S) | No reservations index route yet (see nav above). | |
| Breadcrumb current "VYR-10391" | ✅ BUILD-NOW | `reservations.reference` (`$reservation->reference`, unique per org). | ⚠️ **Format is fiction**: `AssignmentService::generateReference()` emits `'R-'.Str::upper(Str::random(8))` (e.g. `R-A3F9K2M1`), never `VYR-#####`. Don't hard-code the mask. |
| H1 guest name "Jonas Lindqvist" | ✅ BUILD-NOW | `Reservation::leadGuest()` → `guests.first_name`+`last_name`. Front-desk/`ReservationDetail` precedent permits operator-side names. | |
| Status pill "✕ Cancelled" | ✅ BUILD-NOW | `reservations.status='cancelled'` (real enum) → `FormatsOperatorData::reservationStatusMeta('cancelled')` returns `['label'=>'Cancelled','tone'=>'cancelled']` — **already implemented**. | The one design status that maps cleanly. |
| "Download receipt" button | 📋 DECORATIVE-FAKE *(control)* / ✅ *(backing exists)* | **INERT — no onClick.** Backing exists: `route('app.reservations.folio-pdf')` → `App\Http\Controllers\Operator\FolioPdfController` (tenant-scoped, `PdfService`, `FolioDocument::data()`). | Wire it to the folio PDF. ⚠️ "Receipt" ≠ "folio" — reuse the existing doc, don't build a second one. |
| "Rebook guest" button (primary CTA) | 🔨 NEEDS-BACKEND (M) | **INERT.** No rebook/clone action exists. Needs: a new-booking route/UI prefilled from this reservation, delegating to `AssignmentService::createBooking()` (which exists). No route today. | Real feature, real value, no backing. |

### Cancellation banner (the core NEW surface)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Banner container + ⊗ icon | ✅ BUILD-NOW | Presentation. | |
| **"Cancelled Wed 10 Sep, 16:42"** (timestamp) | ✅ BUILD-NOW | `reservations.cancelled_at` (timestamp, cast `datetime`) — **written by `AssignmentService::terminate()` line ~600**. | Verified real. |
| **"· guest request"** (reason) | ✅ BUILD-NOW *(with a caveat)* | `reservations.cancellation_reason` — a **nullable free-text `string`**, written by `terminate()`. Also mirrored to `reservation_status_history.reason`. | ⚠️ **NOT an enum** — there is no reason vocabulary. The only real writer in the app passes the literal `'OTA cancellation'` (`BookingImportService.php:101`). "guest request" is design fiction. Render the stored string; if you want a picklist that's 🔨 S (enum/lookup + a cancel UI). |
| **"· by Marco Reyes"** (actor) | ✅ BUILD-NOW | `reservation_status_history.changed_by` (FK→users, set from `Auth::id()` in `AssignmentService::recordStatusHistory()`) → `ReservationStatusHistory::changedBy()` relation → `users.name`. Reach via `Reservation::statusHistory()`. | Verified: relation + column both exist. ⚠️ Null when cancelled by the OTA webhook (no auth user). |
| "Flexible policy" | ✅ BUILD-NOW | `property_policies.cancellation` JSON, cast `array`; **DemoSeeder.php:123 seeds `['type'=>'flexible','free_until_days'=>2]`**; reachable via `Property::policies()` (HasMany). | Data + relation are real. ⚠️ **Nothing in the app reads this column today** (grepped) — you'd be the first consumer. Needs only a label formatter. ⚠️ **Two policy homes exist** (`property_policies.cancellation` AND `rate_plans.cancellation_policy` JSON, always null in practice) → a decision on which is authoritative. |
| "— 1-night fee applied" | 🔨 NEEDS-BACKEND (M) | **No cancellation-fee engine exists.** The policy JSON has no defined schema beyond the seeded 2 keys and no interpreter. Needs: a `CancellationPolicyService::feeFor(Reservation): Money` + a defined policy JSON contract + a caller inside/next to `AssignmentService::cancelReservation()` that posts the fee via `FolioService::postCharge()`. | The single biggest gap on this page. |
| "Villa Dunes released on the rack" | ✅ BUILD-NOW | Real and automatic: `AssignmentService::terminate()` → `releaseLine()` sets `reservation_units.status='cancelled'` (enum `active/cancelled/moved`) and `adjustSold(-1)` frees `availability.sold`. | The guarantee genuinely holds. |
| "confirmation emailed to the guest" | 🔨 NEEDS-BACKEND (M) | **FABRICATED.** `app/Mail/` contains ONLY `OwnerInviteMail` + `PasswordResetMail`. **No guest-facing email exists in the entire app.** Needs: a `ReservationCancelledMail` + a send + a sent-at record. Note guest `email` is an encrypted cast (readable in PHP, so sending is possible). | Never fake a "we emailed them". |
| "View on rack ›" link | ✅ BUILD-NOW | `route('app.rack')` → `ReservationRack`. | ⚠️ Deep-linking to a date/unit is not supported — a plain link only. |

### Stay summary (7-tile grid, LEFT)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "Check-in — Thu 18 Sep" (struck-through) | ✅ BUILD-NOW | `reservations.check_in` (date cast). Strike-through is styling keyed off `status='cancelled'`. | |
| Sub-label "never arrived" | 📋 DECORATIVE-FAKE | Hard-coded string, and **semantically wrong**: "never arrived" is the *no-show* concept (`status='no_show'`), not cancelled. A cancelled booking was never expected to arrive. | Conflates two real, distinct enum values. Drop it. |
| "Check-out — Sun 21 Sep" (struck-through) | ✅ BUILD-NOW | `reservations.check_out`. | |
| "Nights — 3" | ✅ BUILD-NOW | Derived: `$checkIn->diffInDays($checkOut)` — already done in `ReservationDetail::render()`. | Reconciles: 18→21 Sep = 3 ✅. |
| "Unit — Villa Dunes" | ✅ BUILD-NOW | `reservationUnits.unit` → `units.name` (nullable) / `units.code`. `ReservationDetail` already eager-loads `reservationUnits.unit`. | ⚠️ Design shows ONE unit; a reservation has MANY `reservation_units`. The built screen renders a Units *list*. Decide: first line, or keep the list. |
| Sub-label "released 10 Sep" | ✅ BUILD-NOW | Derived from `reservations.cancelled_at` (the release happens in the same transaction). `reservation_units` has `timestamps()` so `updated_at` also works. | Prefer `cancelled_at` — one source. |
| "Guests — 4 adults" | ✅ BUILD-NOW | `reservations.adults` (+ `children`). | ⚠️ Built screen sums `adults+children` into "guests"; design says "4 adults". Minor divergence. |
| **"Source — Booking.com"** | 🔨 NEEDS-BACKEND (S) | ⚠️ **THE TRAP.** `reservations.source` enum is `('direct','walk_in','ota','corporate','group','agent','manual')` — **there is no `booking_com`**. Real value = `'ota'` → `FormatsOperatorData::sourceLabel('ota')` = **"OTA"**. The OTA **brand is not modelled**: `channels.provider` enum is `['channex']` only, and `channels` is unique on `(org, property, provider)` — i.e. ONE Channex *connection* per property, **not** a per-OTA-brand row, so `channels.name` is the connection name, not a brand. `BookingImportService::build()` stores only `source='ota'`, `channel_id`, `ota_reservation_id` — any brand in the Channex payload is **dropped on the floor**. Needs: a `reservations.ota_brand` column (or `external_refs`-style JSON) + capture in `BookingImportService`. | ⚠️ The exact Channex field is **unverified** — the Channex staging checkpoint is still OUTSTANDING. Size S *assuming* the payload carries it. |
| "Reference — VYR-10391" | ✅ BUILD-NOW | `reservations.reference`. | See format caveat above. |

### Folio card — closed ledger (LEFT)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "Folio" card title | ✅ BUILD-NOW | `Folio` model via `Folio::where('reservation_id',…)`. | |
| **"🔒 Closed · read-only" badge** | 🔨 NEEDS-BACKEND (M) | `folios.status` enum **does** include `'closed'` (`2026_07_02_001510_create_folios_table.php`) — **but nothing ever writes it.** `FolioService::openFolio()` hard-codes `'status'=>'open'`; there is **no `closeFolio()`** and **no guard** (grepped: the only `'closed'` hits in `app/` are `InboxThread`/`InboxList`/`InboxService`, an unrelated thread status). Needs: `FolioService::closeFolio(Folio)` + a status guard throwing in `postCharge()`/`recordPayment()` + a caller on cancel/check-out. | The column is real; the **lifecycle is not**. Don't render a lock that enforces nothing. |
| "No further charges can post" | 🔨 NEEDS-BACKEND (M) | Same as above — this is a **claim about an invariant that does not currently exist**. | Honesty rule: don't assert it until the guard exists. |
| Ledger row 1 — "14 Aug · Deposit payment · Visa ····8810 · [Payment] · −€420.00" | ✅ BUILD-NOW *(mostly)* | `payments` row rendered by `ReservationDetail::paymentEntry()` — a settled `type='payment'` renders `−<amount>`, tone `paid`. Date = `payments.processed_at`. | ⚠️ **"Deposit payment" is ambiguous**: `payments.type` has BOTH `payment` and `deposit_hold`; a `deposit_hold` is NOT counted as paid (`FolioService::recompute()` excludes it) and could not net to this balance. It must be a plain `payment`. ⚠️ "Visa ····8810" → see card-brand row below. |
| Ledger row 2 — "10 Sep · Cancellation fee · 1 night · flexible policy · **[Cancellation]** · +€280.00" | 🔨 NEEDS-BACKEND (M) | ⚠️ **INVENTED TYPE.** `folio_items.type` enum is `('room','tax','fee','add_on','product','manual','discount')` — **no `cancellation`**. `FormatsOperatorData::folioItemLabel()` maps exactly those 7. Map to `'fee'` (→ label "Fee") or `'manual'` (→ "Charge"), OR add a `cancellation` enum value. The *line* also can't exist until the fee engine does (row above). | Two gaps in one row: the enum value AND the posting mechanism. |
| Ledger row 3 — "10 Sep · Refund · Visa ····8810 · reverses deposit · [Refund] · +€140.00" | ✅ BUILD-NOW | `payments` `type='refund'`, `status='succeeded'`; `paymentEntry()` already renders exactly this: `'label'=>'Refund','desc'=>'Reverses a payment','amount'=>'+'…,'tone'=>'refund'`. Correct-by-reversal — the original row is untouched. | The design matches the built convention **exactly**. Rare and good. |
| Card brand + last-4 "Visa ····8810" (appears **3×**) | 🔨 NEEDS-BACKEND (S) | **FABRICATED — no such data exists anywhere.** `payments` has `card_token` (a gateway token) + `metadata` (json) — **no brand, no last4** (grepped `last4|last_four|card_brand`: zero hits). `App\Payments\GatewayResult` carries only `successful/reference/status/message/clientSecret`. Needs: capture brand+last4 from Stripe into `payments.metadata` (or 2 columns). | Stripe returns these; we discard them. |
| Ledger date column | ✅ BUILD-NOW | `folio_items.service_date ?? posted_at ?? created_at`; `payments.processed_at ?? created_at`. Already in `buildFolio()`. | |
| Ledger type badges (Payment/Cancellation/Refund) | 🔨 NEEDS-BACKEND (S) | Built screen has no per-row type badge — it uses `label`+`flag`. "Payment"/"Refund" derive from `payments.type` ✅; "Cancellation" is invented (above). | Small presentation add on real data. |
| Footer "Charges — €280.00" | ✅ BUILD-NOW | `FolioTotals::for($folio)['charges_minor']` = Σ `folio_items.amount_minor`. | **Reconciles ✅** — the fee (280) is the only charge. |
| Footer **"Paid net of refund" — €280.00** | ✅ BUILD-NOW | `FolioTotals::for($folio)['paid_minor']` = Σ settled payments − Σ succeeded refunds = 420 − 140 = **280 ✅**. | Built screen labels this simply "Paid". The design's longer label is **more accurate** — consider adopting it. |
| Footer "Balance — €0.00" | ✅ BUILD-NOW | `FolioTotals::for($folio)['balance_minor']` = 280 − 280 = **0 ✅**. | |
| Footer note "Refund of €140.00 settled to Visa ····8810 · **12 Sep**" | 📋 DECORATIVE-FAKE | ⚠️ **Internally inconsistent**: the refund ledger row above is dated **10 Sep**, this says **12 Sep** — one `payments.processed_at` cannot be both. Plus the fabricated card. | Pick one date from the real row. |
| "Reconciled to the folio ledger" badge | ✅ BUILD-NOW | Exists on the built screen: `$folio['reconciles']` = `$balanceMinor === (int)$folioModel->balance_minor`. | ⚠️ **The design DROPS it.** Keep it — it's a real honesty affordance. |

### Dark "Folio closed" panel (RIGHT)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "Folio closed" eyebrow | 🔨 NEEDS-BACKEND (M) | Depends on the `folios.status='closed'` lifecycle that doesn't exist (above). | |
| Big figure "€0.00" | ✅ BUILD-NOW | `FolioTotals` balance / `folios.balance_minor`. | |
| Caption "balance · **settled 12 Sep**" | 🔨 NEEDS-BACKEND (S) | **No settled-at concept.** `folios` has no `settled_at`/`closed_at` — only `updated_at`. Derivable as MAX(`payments.processed_at`) for settled rows (S), or add a column when `closeFolio()` lands. | |
| Progress bar (track + fill) | 📋 DECORATIVE-FAKE | Hard-coded `width: 100%` — not computed from anything. | |
| Bar caption "Fee €280 · refund €140" | ✅ BUILD-NOW | Both derive from real rows (the fee folio_item once postable; the refund payment). | Contingent on the fee line existing. |
| Bar caption "settled" | 📋 DECORATIVE-FAKE | Hard-coded literal. | |
| "View receipt" button | 📋 DECORATIVE-FAKE *(control)* / ✅ *(backing exists)* | **INERT — no onClick.** Same backing as "Download receipt": `route('app.reservations.folio-pdf')`. | ⚠️ **Duplicate CTA** — the same action appears twice on one page (title row + here). Ship one. |

### Guest card (RIGHT)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Avatar initials "JL" | ✅ BUILD-NOW | `FormatsOperatorData::initials()` over the guest name — already implemented. | |
| Guest name "Jonas Lindqvist" | ✅ BUILD-NOW | `guests.first_name`+`last_name`. | |
| "**1st booking**" | ✅ BUILD-NOW *(derived)* | `Reservation::where('lead_guest_id', $guest->id)->count()` — real column, real index-able query. Needs an ordinal formatter. | Scope it tenant-side (the global scope does this) and decide whether cancelled bookings count. |
| "**· Sweden**" | 🔨 NEEDS-BACKEND (S) | `guests.nationality` is `char(2)` (e.g. `SE`) — **there is no country-name map** in the repo. Needs a small ISO-3166 lookup. | |
| **Guest email "j.lindqvist@mail.se"** | 🔨 NEEDS-BACKEND (S) — *policy decision, not a data gap* | ⚠️ **PII.** `guests.email` is an `encrypted` cast + in `$hidden` — technically **decryptable and renderable** in Blade. BUT `ReservationDetail`'s locked honesty triage says verbatim: *"Guest PII — name only; encrypted email/phone never rendered"*, and the built blade renders **no** contact fields. | **This page CONTRADICTS the established precedent.** Not a build task — a decision to record in `docs/DECISIONS.md` before anyone ships it. |
| **Guest phone "+46 70 555 31 08"** | 🔨 NEEDS-BACKEND (S) — *policy decision* | Identical to email: `guests.phone`, encrypted cast, `$hidden`, never rendered by the built screen. | Same decision. |
| Pinned note "Asked about next summer — worth a follow-up offer in spring" | ✅ BUILD-NOW *(read)* / 🔨 (S) *(write)* | **The polymorphic table already covers this** — `notes` (`2026_07_02_001120`): `ulidMorphs('notable')`, `body`, `visibility` enum `('internal','shared')`, `author_id`. `Guest` uses `HasNotes` **and** `Reservation` uses `HasNotes` (both verified in the models). Reading = ✅. | ⚠️ **No note-writing UI exists anywhere**, and `DemoSeeder` seeds no notes → the card would render empty. Needs an empty state + (S) a create-note action. Do NOT build a parallel "guest remarks" concept. |

### Cancellation record card (RIGHT — the other core NEW surface)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Card + "CANCELLATION RECORD" eyebrow | ✅ BUILD-NOW | Presentation over the rows below. | |
| "Reason — Guest request" | ✅ BUILD-NOW | `reservations.cancellation_reason` (free string). | Same caveat as the banner: no enum, real writer passes `'OTA cancellation'`. |
| "Policy — **Flexible · 48h**" | ✅ BUILD-NOW | `property_policies.cancellation` = `['type'=>'flexible','free_until_days'=>2]` (seeded, `array` cast, via `Property::policies()`). "48h" = `free_until_days × 24`. | Real data, real derivation, zero readers today. Needs a formatter + the property-vs-rate-plan authority decision. |
| "Fee — 1 night · €280.00" | 🔨 NEEDS-BACKEND (M) | The **money** could come from a `folio_items` line once postable; the **"1 night"** quantum requires the policy engine that doesn't exist. | See the fee-engine row. |
| "Refunded — €140.00 · 12 Sep" | ✅ BUILD-NOW | Σ `payments` where `type='refund'` AND `status='succeeded'` (exactly `FolioTotals`' refund leg); date = `payments.processed_at`. | ⚠️ Date conflicts with the 10 Sep ledger row. |
| "Confirmation — ✓ Emailed 10 Sep" | 🔨 NEEDS-BACKEND (M) | **FABRICATED** — no guest mail exists (only `OwnerInviteMail`, `PasswordResetMail`). Needs the Mailable + send + a sent-at record to display a date. | |

### Absent — worth stating explicitly

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| **Reinstate / un-cancel action** | 🔨 NEEDS-BACKEND (L) | **The design has none** — worth noting since the task asked. `AssignmentService` has **no reinstate method**; cancel is terminal (`terminate()` frees `sold`, so the inventory may since have been resold). A true reinstate = re-running the full locking core and can legitimately FAIL. | Correctly absent. If ever wanted, it's a real subsystem decision, not a button. |
| Operator-initiated **cancel** action | 🔨 NEEDS-BACKEND (M) | ⚠️ **How does a reservation even reach this state?** `cancelReservation()` has exactly **one** caller in the whole app: `BookingImportService.php:101` (the OTA webhook). There is **no cancel route and no cancel UI** — `reservation-detail.blade.php` lists "Modify · cancel" as a deferred `Soon` item. | So today this page is only reachable for OTA-cancelled bookings. |
| Empty states / modals / toasts / sorts / filters / bulk actions / pagination / footnotes | — | **None exist on this page.** | Stated for completeness — nothing was dropped. |
| Units list card | — | The built screen HAS one (`$units` loop). The cancelled design replaces it with the single "Unit" tile. | Don't silently lose the multi-unit case. |
| Record-payment rail / More-actions rail / Export folio (PDF) | — | On the built screen; the cancelled design **correctly drops** them (a closed folio takes no money). | Intentional, consistent. |

## WRITES

**This page has ZERO functional write-actions — verified: 0 `sc-camel-on-click`, 0 `data-toast`, 0 form elements.** All 3 buttons are inert markup. Enumerating each control and the service method it *would* need:

1. **"Download receipt"** (title row) — INERT. Real backing EXISTS: `GET route('app.reservations.folio-pdf')` → `App\Http\Controllers\Operator\FolioPdfController::download()` → `PdfService::download('pdfs.folio', FolioDocument::data($model), …)`. Tenant-scoped via `Reservation::findOrFail()` under the global scope. **Read-only, not a write.**
2. **"View receipt"** (dark panel) — INERT. Same route as #1 — a duplicate CTA.
3. **"Rebook guest"** (title row) — INERT. NEEDS: a new-booking route/screen delegating to the existing `AssignmentService::createBooking(array $reservationAttributes, array $lines)`. No route exists.
4. **"Resume now"** (paused banner) — INERT link to a non-existent billing screen. NEEDS: the whole billing module.
5. **"View on rack ›"** — a plain navigation link → `route('app.rack')`. Not a write.

**Writes that this state *implies* but does not perform** (all would go through the guarded single write paths — never around them):
- Cancelling: `AssignmentService::cancelReservation(Reservation $r, ?string $reason = null)` — EXISTS and is the correct path (frees `sold` via `releaseLine()`, stamps `cancelled_at`/`cancellation_reason`, writes `reservation_status_history` + `audit_events` + `domain_events`). **No route/UI calls it** (only `BookingImportService`).
- Posting the cancellation fee: **NEEDS `CancellationPolicyService::feeFor()`** + the existing `FolioService::postCharge(Folio, string $type, string $description, Money $unitAmount, …)`.
- The refund: `FolioService::refundPayment(Payment $original, Money $amount)` — **EXISTS and DOES support PARTIAL amounts** (it guards only `$amount > $original->amount_minor`). ⚠️ **It takes NO reason parameter** — `metadata` stores only `['refunds_payment_id' => …]`. A refund *reason* is 🔨 S. ⚠️ Note the wired UI (`ReservationFolioActionController::refund()`) hard-codes a **FULL** refund; partial is listed as deferred "Soon" on the built screen even though the service supports it.
- Closing the folio: **NEEDS `FolioService::closeFolio(Folio)`** — does not exist.
- Emailing the guest: **NEEDS a `ReservationCancelledMail`** — does not exist.

## ISOLATION / PII

**Tenant scoping — the precedent is solid and this page must reuse it verbatim.** `App\Livewire\ReservationDetail` takes `{reservation}` as a **plain string prop** (never route-model-bound) and resolves it in `render()` via `Reservation::query()->findOrFail($this->reservationId)` **after** `SetTenantForWebRequest` has set context — so the `BelongsToOrganization` global scope makes a cross-org id **404**. `FolioPdfController` and `ReservationFolioActionController` use the identical pattern (both documented as such). The cancelled variant is the same component in a different state → **zero new isolation surface**, provided it keeps the plain-string/RMB rule.

**Aggregate scoping:** the only aggregate this page introduces is the guest-card **"1st booking"** count (`Reservation::where('lead_guest_id', …)->count()`). The global scope tenant-scopes it automatically; the open question is *semantic*, not security: does a cancelled booking count toward "1st booking"? Name the rule.

**Guest PII — this page breaks the locked precedent and needs a decision, not a build.**
- `guests.email` and `guests.phone` are Laravel `encrypted` casts (TEXT columns, never indexed by value) and are listed in the model's `$hidden`. `$hidden` only suppresses array/JSON serialization — **Blade can read the decrypted values fine**. So rendering them is *technically possible*.
- BUT `ReservationDetail`'s docblock states the locked rule verbatim: *"Guest PII — name only; encrypted email/phone never rendered"*, and the built blade renders **no** contact fields. **This design renders both.**
- Guest **NAME** is fine on operator surfaces (front-desk precedent) — the H1, the avatar initials, and the guest card name are all safe.
- **Owner-surface rule is not engaged here** (this is an operator screen) — but note the contrast: owner surfaces show no guest names at all.
- **GDPR:** `Guest::anonymize()` nulls `first_name`/`last_name`/`email`/`phone`/`nationality` and stamps `anonymized_at` while **preserving the row and id** (reservation/financial FKs stay intact). This page must therefore render a null-safe guest card — an anonymized guest yields an empty H1, empty initials, no nationality. The built component already guards this (`$name !== '' ? $name : null`). **The design has no anonymized/empty state.**
- ⚠️ If the PII decision goes "show it", note that the pinned-note body is also potentially PII-bearing free text and is NOT covered by `anonymize()` (which redacts `messages`/`message_threads` but **not** `notes`) — a real gap worth flagging separately.

## TRAPS (Wave-1 style — reconciliation, scoping, real enums, fabrication)

**1. "Source — Booking.com" — the headline trap.** `reservations.source` enum is exactly `('direct','walk_in','ota','corporate','group','agent','manual')`. There is **no `booking_com`**. The real value is `'ota'`, and `FormatsOperatorData::sourceLabel('ota')` returns **"OTA"**. The OTA brand is genuinely unmodelled: `channels.provider` enum is `['channex']` only, and `channels` is `unique(organization_id, property_id, provider)` — one Channex *connection* per property, not a per-brand row, so `channels.name` is NOT a brand field. `BookingImportService::build()` persists only `source='ota'`, `channel_id`, `ota_reservation_id`; any brand in the payload is discarded. **Do not port "Booking.com".**

**2. Invented status vocabulary — full map of every status-ish token on the page:**
- "Cancelled" (title pill) → ✅ REAL `reservations.status='cancelled'` → `reservationStatusMeta()` already returns "Cancelled".
- "Closed · read-only" (folio badge) → ⚠️ **half-real**: `folios.status` enum DOES contain `'closed'`, but **no code ever writes it** and no guard enforces it. `openFolio()` hard-codes `'open'`; there is no `closeFolio()`.
- "Cancellation" (folio row badge) → ❌ **INVENTED**. `folio_items.type` enum is exactly `('room','tax','fee','add_on','product','manual','discount')`. Map to `fee`/`manual` or add the value.
- "Payment" / "Refund" (row badges) → ✅ REAL `payments.type`.
- "never arrived" (check-in sub-label) → ❌ **invented AND semantically wrong** — that's the `no_show` concept, not `cancelled`. Two distinct real enum values conflated.
- "Workspace paused" → ⚠️ `subscriptions.status` enum DOES contain `'paused'`, but no billing module, no route, no read-only enforcement.
- "Guest request" (reason) → ❌ **not an enum at all**; `cancellation_reason` is nullable free text. The only real writer passes `'OTA cancellation'`.

**3. Money — the folio genuinely RECONCILES (rare; verify it stays that way).** Against `App\Support\Folio\FolioTotals::for()`: charges = Σ`folio_items.amount_minor` = **280**; paid = Σ settled payments (420) − Σ succeeded refunds (140) = **280**; balance = 280 − 280 = **0**. All three footer figures and the dark panel's €0.00 check out. Sign conventions also match the built `paymentEntry()` exactly (payment `−`, refund `+` as its own offsetting line, original never struck). **This design was drawn by someone who read the ledger.** Named source for every figure: `folio_items` + `payments`, via `FolioTotals` — reuse it, don't recompute.

**4. Date contradiction.** The refund is dated **10 Sep** in the ledger row but **12 Sep** in the footer note, the dark panel ("settled 12 Sep"), and the cancellation record ("Refunded €140.00 · 12 Sep"). One `payments.processed_at` cannot be both. Pick the real row's date.

**5. Fabricated data — do not port:**
- `"Visa ····8810"` (×3) — **no brand/last4 column exists anywhere**; `GatewayResult` carries only `successful/reference/status/message/clientSecret`. Grep for `last4|card_brand`: zero hits.
- `"VYR-10391"` — real refs are `'R-'.Str::upper(Str::random(8))` per `AssignmentService::generateReference()`.
- `"by Marco Reyes"`, `"Jonas Lindqvist"`, `"j.lindqvist@mail.se"`, `"+46 70 555 31 08"`, `"Villa Dunes"`, `"Sweden"` — demo persons/places.
- Nav badges `2` and `2` — hard-coded literals.
- Progress bar `width: 100%` and the caption `"settled"` — hard-coded.
- `"read-only until 5 Oct"` — hard-coded string.
- `<span data-vyc-zero></span>` in the paused banner — an **empty currency span**; the sentence "…· ` ` while paused" is literally incomplete in the design.
- `"confirmation emailed to the guest"` / `"Emailed 10 Sep"` — **no guest email exists in the app at all**.

**6. Demo-data gap (Wave-1 precedent: browser verification caught exactly this class of bug).** `DemoSeeder` seeds **no cancelled reservation and no refund** (grepped: zero `cancelReservation`/`markNoShow`/refund calls). It also seeds **no notes**. So this page currently has **nothing to render** — every browser check would hit a 404 or an empty shell. Seeding must go through `AssignmentService::cancelReservation()` + `FolioService::refundPayment()`, never direct inserts.

## BUILD-READINESS VERDICT

**~50% ✅ build-now, ~40% 🔨 needs-backend, ~10% 📋/⬜** across ~55 catalogued elements — but that ratio is misleading in a *good* way: this is a **state variant of an already-built screen**, so most of the ✅ is code that already exists (`ReservationDetail` + `FolioTotals` + `FormatsOperatorData::reservationStatusMeta()`/`folioItemLabel()`/`money()` + the folio-PDF route). The genuinely new work is small and well-defined.

**The good news, verified:** the cancellation *provenance* the page is built around is **fully backed today** — `reservations.cancelled_at` ✅, `reservations.cancellation_reason` ✅, and the actor via `reservation_status_history.changed_by` → `ReservationStatusHistory::changedBy()` → `users.name` ✅, all written by `AssignmentService::terminate()`. The "released on the rack" claim is real and automatic (`releaseLine()` → `status='cancelled'` + `adjustSold(-1)`). And **the folio arithmetic reconciles exactly** under `FolioTotals` (charges 280 / paid 420−140=280 / balance 0) with sign conventions matching the built `paymentEntry()`. That is a first among the designs audited.

**Recommended approach — build now, in this order:**
1. **Add a cancelled branch to the existing `ReservationDetail` component** (do NOT create a new Livewire class or a second folio renderer). Eager-load `statusHistory.changedBy` and `property.policies`; render the cancellation banner + cancellation-record card from real columns; drop the record-payment/more-actions rails when `status==='cancelled'` (the design already does this correctly). Reuse the folio-PDF route for the receipt CTA (once, not twice). Keep the "Reconciled to the folio ledger" badge the design drops.
2. **Two small backends worth doing with it:** the ISO-3166 nationality map (S) and the folio close lifecycle — `FolioService::closeFolio()` + a guard (M). Without the guard, do **not** render the "Closed · read-only" lock or "No further charges can post"; that badge asserts an invariant that does not currently exist.
3. **Fix the demo-data gap FIRST or the page is unviewable** — `DemoSeeder` seeds zero cancelled reservations, zero refunds, zero notes. Seed via `AssignmentService::cancelReservation()` + `FolioService::refundPayment()`, never direct inserts. This is exactly the Wave-1 failure class that browser verification caught.

**Flag for decision (do not build blind):**
- **Guest email/phone on the card** — directly contradicts `ReservationDetail`'s locked *"name only; encrypted email/phone never rendered"* rule. Needs a `docs/DECISIONS.md` entry either way. Related real gap: `Guest::anonymize()` redacts `messages`/`message_threads` but **not** `notes`.
- **"Source — Booking.com"** — the OTA brand is genuinely unmodelled and `BookingImportService` discards it. Ship **"OTA"** now; the `ota_brand` column is blocked behind the **outstanding Channex staging checkpoint** (the payload field is unverified).
- **Cancellation policy authority** — `property_policies.cancellation` (seeded, real) vs `rate_plans.cancellation_policy` (always null). Pick one before the fee engine.
- **Cancellation fee engine (M)** — the biggest gap. Until it exists, the "1-night fee" line, the "Cancellation" badge, and the Fee row cannot be honestly rendered.

**Note the reachability question:** today `cancelReservation()` has exactly ONE caller — the OTA webhook (`BookingImportService.php:101`). There is no operator cancel route/UI (`reservation-detail.blade.php` lists "Modify · cancel" as `Soon`). So this page is currently only reachable for OTA-cancelled bookings — worth deciding whether the operator cancel action (M) belongs in the same wave.

---

# 3. Reservation — NO-SHOW state (operator)

**Design file:** `docs/design/raw/Operator Reservation No-show.html` — 1,556,750 B · template 59,759 · 12 onClick · 3 toasts

**What it is.** A terminal-state variant of the already-built `App\Livewire\ReservationDetail` (`/app/reservations/{reservation}`): operator shell + paused-workspace banner + breadcrumb + title row, a no-show banner, a 2-column body (stay summary + a 3-row "closed" folio | dark settled panel + guest card + "No-show record" panel), and a **Waive fee modal**. The genuinely new thing the no-show state adds is the **waive-fee flow** — and that flow is where the backend runs out. Interactivity is thin and entirely modal-local: 12 `sc-camel-on-click` (open/close/stop ×5, 2 amount chips, 4 reason chips, 1 notify toggle) and exactly **one** real `data-toast` (`"Fee waived"` — a mock, no state change). The three page-level buttons — Download receipt, Rebook guest, View receipt — have **zero handlers**. The modal ships **open by default** (`state = { wfOpen: true, … }`), a design-tool convenience, not a page state.

## Feature inventory — every element

> **Verification basis.** Every ✅ below was read in the repo. Key files: `app/Services/AssignmentService.php` (`markNoShow` @329 → `terminate` @586), `app/Services/FolioService.php` (`postCharge` @53, `refundPayment` @216, `recompute` @348), `app/Http/Controllers/ReservationFolioActionController.php`, `app/Livewire/ReservationDetail.php`, `app/Livewire/Operator/Concerns/FormatsOperatorData.php`, `app/Support/Folio/FolioTotals.php`, `app/Services/OwnerAttributionService.php` (`attributeAtVersion` @105), and the migrations named per-row.

### Shell / sidebar (operator chrome)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Vyrobel brand mark "V" + wordmark | ✅ BUILD-NOW | `resources/views/components/operator/shell.blade.php` (`.vy-brand`) | Already in the built shell. Reuse `<x-operator.shell>`. |
| Property Switcher (`<dc-import name="Property Switcher" hint-size="100%,52px">`) | 🔨 NEEDS-BACKEND (S) | `properties` + `property_user` exist (`2026_07_02_001140_add_property_fk_to_property_user`). Needs: a session-held active-property id + a scope on the operator reads. The built shell has **no** switcher. | Cross-cutting, not no-show-specific. Flag to the shell owner, don't build here. |
| Nav "Overview → Dashboard" | ✅ BUILD-NOW | `route('app.dashboard')` → `App\Livewire\Operator\OperatorDashboard` | In the shell already. |
| Nav "Overview → Calendar" | ✅ BUILD-NOW | `route('app.rack')` → `App\Livewire\ReservationRack` | Shell key `calendar`. |
| Nav "Overview → Insights" | ⬜ OTHER-MODULE | No route, no component. Analytics module unbuilt. | Shell has no `insights` key at all. |
| Nav "Front desk → Reservations" (active item) | 🔨 NEEDS-BACKEND (S) | Shell has key `reservations` with a **null route** → renders a disabled "Soon" chip (`shell.blade.php` @31). The list screen is a separate Wave 2 item. | The *active* highlight here is fine (`active="reservations"`), but the nav item won't link until the list route lands. |
| Nav "Front desk → Arrivals & departures" | ✅ BUILD-NOW | `route('app.front-desk')` → `App\Livewire\FrontDesk` | Shell labels it "Front desk". |
| Nav "Front desk → Guests" | ⬜ OTHER-MODULE | `Guest` model exists; **no** guest-list route/component. | Wave-2+ screen. |
| Nav "Front desk → Messages" | ✅ BUILD-NOW | `route('app.inbox')` → `App\Livewire\InboxList` | Shell calls it "Inbox". |
| Messages nav badge **"2"** | 📋 DECORATIVE-FAKE (count) / 🔨 (S to make real) | Hard-coded `>2<` in the markup. Real source EXISTS: `message_threads.unread_count` (`2026_07_17_000000_create_message_threads_table` @42); `InboxList.php` @97 already computes `$models->where('status','!=','closed')->where('unread_count','>',0)->count()`. Needs: lift that count into the shell. | Don't ship the literal `2`. Small, genuinely worth doing. |
| Nav "Operations → Housekeeping" | ✅ BUILD-NOW | `route('app.housekeeping')` → `App\Livewire\HousekeepingBoard` | |
| Nav "Operations → Maintenance" | ✅ BUILD-NOW | `route('app.maintenance')` → `App\Livewire\MaintenanceList` | |
| Maintenance nav badge **"2"** | 📋 DECORATIVE-FAKE (count) / 🔨 (S to make real) | Hard-coded `>2<`. Real source: `maintenance_tickets.status` open-count (`2026_07_16_000000_create_maintenance_tickets_table`). | Same treatment as the Messages badge. |
| Nav "Operations → Rates & availability" | 🔨 NEEDS-BACKEND (S for the link) | Backend exists (`RateAndRestrictionService`, `rate_calendar`, `availability`); **no route/screen**. Shell key `rates` → null route. | Wave 4 screen. |
| Nav "Operations → Channels" | 🔨 NEEDS-BACKEND (S for the link) | Backend exists (`channels`, `App\Services\Channex\*`); **no route/screen**. Shell key `channels` → null route. | Wave 4 screen. |
| Nav "Finance → Owners" | ✅ BUILD-NOW | `route('app.owners')` → `App\Livewire\Operator\OperatorOwners` | |
| Nav "Finance → Reports" | ⬜ OTHER-MODULE | No route, no component, no reporting module. | Shell has no `reports` key. |
| Nav "System → Settings" | 🔨 NEEDS-BACKEND (S for the link) | `settings` table exists (`2026_07_02_000900`); shell key `settings` → null route. | |
| Nav "System → Security" | ⬜ OTHER-MODULE | Shell key `security` → null route. 2FA/security screen needs a backend gate first (per `docs/ROADMAP.md`). | |
| Nav grouping labels (Overview / Front desk / Operations / Finance / System) | 📋 DECORATIVE-FAKE | The built shell's groups are **OPERATIONS / DISTRIBUTION / OWNERS / ACCOUNT** (`shell.blade.php` @22-45). The design regroups them. | Presentation-only divergence — pick one and be consistent across Wave 2; don't fork the shell for this page. |

### Page chrome — banners & breadcrumb

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "Workspace paused — read-only until 5 Oct" banner | ⬜ OTHER-MODULE (billing) | `subscriptions.status` enum **does** include `'paused'` and `current_period_end` exists (`2026_07_02_000400_create_subscriptions_tables`). But: **nothing reads it**, no billing service, no read-only enforcement, no `/app/billing` route. | The column is real; the *feature* is not. Do NOT render a paused banner off a column nothing maintains. |
| Banner sub-line "Viewing is fine, changes are off · channels stopped selling · `<span data-vyc-zero>` while paused" | 📋 DECORATIVE-FAKE | The `data-vyc-zero` span is **empty** with no handler in this bundle — it's a hook into the design system's global `VYC` currency helper (external script `74e9b83c-…`, not decoded here). **I cannot tell what it renders.** Sentence reads "…stopped selling · ⟨?⟩ while paused". | Genuinely unclear — flagging rather than guessing. |
| "Resume now" CTA → `Operator Billing.dc.html` | ⬜ OTHER-MODULE (billing) | No billing screen, no resume action. | |
| Breadcrumb "‹ Reservations" link | 🔨 NEEDS-BACKEND (S) | Target is the reservations **list** — no route yet (see nav row above). | Until the list lands, link to `route('app.rack')` or omit. |
| Breadcrumb "VYR-10405" | ✅ BUILD-NOW | `reservations.reference` (unique per org — `2026_07_02_001410` @20,45). `ReservationDetail` already exposes `'reference' => (string) $reservation->reference`. | The literal `VYR-10405` is demo text; `AssignmentService::generateReference()` @645 mints real ones. |

### Title row

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| H1 guest name "Daniel Kovács" | ✅ BUILD-NOW | `Reservation->leadGuest` → `guests.first_name`/`last_name`. `ReservationDetail::render()` @63-64 already builds this exact string. **PII-safe**: operator surfaces may show guest names (front-desk precedent). | |
| "No-show" status chip (clock icon) | ✅ BUILD-NOW | `reservations.status = 'no_show'` — a **real enum value** (`2026_07_02_001410` @24). `FormatsOperatorData::reservationStatusMeta()` @81 **already maps it**: `'no_show' => ['label' => 'No-show', 'tone' => 'no-show']`. | Zero new backend. Reuse the trait — do not redefine. |
| Notif Bell (`<dc-import name="Notif Bell" hint-size="38px,38px">`) | 🔨 NEEDS-BACKEND (M) | No notifications table, no `notifications` model, nothing in the shell. Needs: a table + a read/unread service + a poll or broadcast. | Cross-cutting. Not no-show work. |
| **"Download receipt"** button | 📋 DECORATIVE-FAKE (inert) — but a REAL equivalent exists | **Zero `sc-camel-on-click`, zero `data-toast`.** Pure markup. The real thing already exists: `route('app.reservations.folio-pdf', $id)` → `App\Http\Controllers\Operator\FolioPdfController` (tenant-scoped, branded, reconciles to the cent, streams `application/pdf`). | **Reuse, don't duplicate.** Wire this button to the existing folio PDF and it's ✅. Note `invoices` table exists (`2026_07_02_001530`) but has **no service, no writer, no reader** — do not build a receipt on it. |
| **"Waive fee…"** button | 🔨 NEEDS-BACKEND (M) | `sc-camel-on-click="{{ openWf }}"` — opens the modal (real in-design). The *flow it opens* is the gap — see the Waive-fee modal section. | The only page-level control with a handler. |
| **"Rebook guest"** button | 📋 DECORATIVE-FAKE (inert) | **Zero handlers.** No rebook flow, no new-reservation route (`/app/reservations/create` doesn't exist — separate Wave 2 item). | Don't fake it. Omit or "Soon"-tag per the existing `vy-defer-item` pattern in `reservation-detail.blade.php` @115-119. |

### No-show banner

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "Marked no-show Fri 12 Sep, 23:30" | ✅ BUILD-NOW | `reservations.cancelled_at` (set by `terminate()` @600) **and** `reservation_status_history` (`reservation_id`, `from_status`, `to_status`, `changed_by`, `created_at` — `2026_07_02_001440`), written by `recordStatusHistory()` @613. | Real. Two sources — prefer `reservation_status_history.created_at` where `to_status='no_show'`. |
| "— guest never arrived" | 📋 DECORATIVE-FAKE | Static copy. `markNoShow()` @329 hard-codes `reason = null` (`terminate($reservation, 'no_show', null)`), so `cancellation_reason` / `status_history.reason` are **always NULL** for a no-show. | See "No-show reason" row below — a real S fix. |
| "· auto-marked at end of arrival day" | 🔨 NEEDS-BACKEND (S) | **No scheduler exists.** `routes/console.php` schedules exactly one job: `Schedule::command('invites:prune-expired')->daily()`. `markNoShow()` has **zero production callers** — grep finds only `tests/Feature/AssignmentServiceTest.php:73`. Needs: an artisan command + `Schedule::daily()` + a property-timezone-aware "end of arrival day" query. The service method already exists. | Genuinely small and genuinely useful. Until built, **nothing on this page can honestly say "auto"**. |
| Sub-line "2 calls and a message went unanswered" | 🔨 NEEDS-BACKEND (M) / ⬜ | **Calls are not modelled anywhere** — grep for `call_log`/`CallLog`/`telephony` returns nothing. Messages are real (`messages` + `message_threads`). Needs: a call-log table + a telephony integration, or drop "calls" and derive the message count from `message_threads` where `guest_id`. | Split it: message count is a small derivation; calls are a module. |
| Sub-line "1-night fee charged to the Expedia virtual card" | 🔨 NEEDS-BACKEND (M) | See the no-show-fee rows below. Neither the 1-night fee **nor** "Expedia" **nor** "virtual card" is modelled. | The central lie of this page. |
| Sub-line "night 2 released for resale" | 🔨 NEEDS-BACKEND (L) | See "Partial-night release" trap below. `terminate()` @594-596 releases **every** active/overbooked line — never a subset. | |
| "View messages ›" link | ✅ BUILD-NOW | `route('app.inbox')`. Better: `message_threads.reservation_id` FK exists (`2026_07_17_000000` @35) → deep-link `route('app.inbox.show', $thread)` when a thread is linked. | Real. Guard for the no-thread case. |

### Stay summary (7 cells)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Check-in "Fri 12 Sep" (strikethrough) | ✅ BUILD-NOW | `reservations.check_in` (date). `ReservationDetail` @88 formats it. Strikethrough is presentation keyed to `status === 'no_show'`. | |
| "never arrived" sub-caption | 📋 DECORATIVE-FAKE | Static copy — but *defensible* as a pure presentation restatement of `status='no_show'`. | Not a data claim; safe as a label. |
| Check-out "Sun 14 Sep" (strikethrough) | ✅ BUILD-NOW | `reservations.check_out`. `ReservationDetail` @89. | |
| Nights "2" | ✅ BUILD-NOW | Derived: `$checkIn->diffInDays($checkOut)` — `ReservationDetail` @90 already does exactly this (half-open `[check_in, check_out)`). | |
| **"1 charged · 1 released"** | 🔨 NEEDS-BACKEND (L) | **No partial-night model exists.** See the traps field. | The single biggest fabrication on this page. |
| Unit "Apt 103" | ✅ BUILD-NOW | `units.code` (unique per property — `2026_07_02_001050` @24,36) via `reservation_units.unit_id`. `ReservationDetail` @97 already maps `'unit' => $line->unit?->code`. | Note: after `markNoShow` the line's `status` is `'cancelled'` — the row and `unit_id` **survive** (`reservation_units` uses status lifecycle, never soft-delete). So the unit is still readable. ✓ |
| **"resold Sat 13 Sep"** | 🔨 NEEDS-BACKEND (S) | No link from a no-show to a successor booking. Derivable: query `reservation_units` where `unit_id = X`, `status='active'`, date-range overlaps, `reservation_id != this`. Needs a scope/derivation. | The literal date is fabricated. The derivation is cheap and honest. |
| Guests "1 adult" | ✅ BUILD-NOW | `reservations.adults` + `children`. `ReservationDetail` @91 sums them; here they'd be split for the "1 adult" phrasing. | |
| **Source "Expedia"** | 🔨 NEEDS-BACKEND (M) — **THE OTA BRAND IS NOT MODELLED** | `reservations.source` enum is exactly `('direct','walk_in','ota','corporate','group','agent','manual')` — **no `expedia`, no `booking_com`, no `airbnb`**. `channels.provider` enum is exactly `['channex']` (`2026_07_02_020000` @24) — the channel *manager*, not the OTA. `FormatsOperatorData::sourceLabel()` @99 renders `'ota' => 'OTA'`. Needs: an `ota_brand` column on `reservations` or `channels` (populated from the Channex booking payload), or use `channels.name` as a free-text label. | **The honest render today is "OTA".** Do not invent Expedia. |
| "virtual card on file" sub-caption | 🔨 NEEDS-BACKEND (S) | `payments.card_token` exists (gateway token, **never a PAN** — `2026_07_02_001540` @26) and `payments.gateway` enum **does** include `'ota_collect'`. But no "virtual card" flag and no card brand/last-4 column. Needs: a `card_last4` + `card_brand` column (PCI-safe to store) or a `payments.metadata` convention. | The concept is close but not there. |
| Reference "VYR-10405" | ✅ BUILD-NOW | `reservations.reference`, `unique(organization_id, reference)`. | |

### Folio panel (left column)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "Folio" heading | ✅ BUILD-NOW | `folios` (`2026_07_02_001510`), `Folio::query()->where('reservation_id', …)` — `ReservationDetail` @72-75. | |
| **"Closed · read-only"** lock chip | 📋 DECORATIVE-FAKE — **an invented state in practice** | `folios.status` enum **is** `['open','closed','void']` — so the value is legal. **But nothing in the codebase ever sets it.** Grep for `'closed'`/`'void'` assignments against `Folio` returns nothing; `FolioService::recompute()` @348 only writes `total_minor`/`paid_minor`/`balance_minor`. **Every folio in the system is `'open'`, forever.** | To make real: 🔨 (S) — close the folio in a lifecycle hook + a `postCharge`/`recordPayment` guard. Otherwise this chip lies. |
| "Waiving the fee posts a reversing line" hint | ✅ BUILD-NOW (as copy) | Accurate description of `FolioService::refundPayment()` @216 — "a NEW offsetting Payment is created; the original row is never touched" — and of `postCharge` with a negative `discount` line. | Honest copy. Keep it. |
| Ledger row 1 — "12 Sep · No-show fee · 1 night · Apt 103 · **+€175.00**" | 🔨 NEEDS-BACKEND (M) | The *rendering* is ✅ (`ReservationDetail::buildFolio()` @117-132 renders every `folio_item` exactly like this). The *row itself* doesn't exist: no code posts a no-show fee. Needs: a fee definition + a `FolioService::postCharge($folio, 'fee'|'manual', 'No-show fee', $amount, …)` call from a no-show action. | `folio_items.type` enum `('room','tax','fee','add_on','product','manual','discount')` accommodates it. `€175.00` is hard-coded (`nsCharges: VYC.fmt(175, 2)`). |
| Ledger row 1 — "**No-show**" badge | 📋 DECORATIVE-FAKE (invented vocabulary) | **Not a `folio_items.type`.** `FormatsOperatorData::folioItemLabel()` @239-251 maps the real enum → Room/Tax/Fee/Add-on/Product/Discount/Charge. A no-show fee would badge as **"Fee"**. | Badge it "Fee" and put "No-show" in the description. |
| Ledger row 2 — "12 Sep · Payment · Expedia virtual card ····3307 · **−€175.00**" | ✅ BUILD-NOW (partially) | `payments` row, `type='payment'`, `status='succeeded'`. `ReservationDetail::paymentEntry()` @171-181 renders exactly `'−'.$this->money($amount, $currency)` with label "Payment". `gateway='ota_collect'` is a real enum value. | The **"····3307"** is fabricated — no last-4 column (see above). `−€175.00` is hard-coded (`nsPaymentLine`). Render "Payment · Received" (the built precedent) until a last-4 exists. |
| Ledger row 2 — "**Payment**" badge | ✅ BUILD-NOW | Real: `payments.type='payment'`. Already the built label. | |
| Ledger row 3 — "12 Sep · Night 2 · 13 Sep · not charged, released for resale · **€0.00**" | 📋 DECORATIVE-FAKE — **do NOT port as a ledger row** | There is no such `folio_item`. Its amount renders `{{ nsBalance }}` = `€0.00` — the design is **reusing the balance variable as a line amount**. Posting a €0.00 folio_item to make this appear would fabricate a ledger row (the owner-statement fake-fee precedent forbids exactly this). | If wanted, render it as a **non-ledger annotation** outside the ledger list, derived from `reservation_units.status='cancelled'`. Never as a row. |
| Ledger row 3 — "**Released**" badge | 📋 DECORATIVE-FAKE (invented vocabulary) | Not a `folio_items.type`. Not a `reservation_units.status` either — that enum is exactly `('active','cancelled','moved')` (`2026_07_02_001420` @35). | "Released" exists nowhere. |
| Footer "Charges **€175.00**" | ✅ BUILD-NOW | `App\Support\Folio\FolioTotals::for($folio)['charges_minor']` = Σ `folio_items.amount_minor`. Already wired: `ReservationDetail` @141,146. | Reconciles ✓ (Σ items = 175). |
| Footer "Paid **€175.00**" | ✅ BUILD-NOW | `FolioTotals::for()['paid_minor']` = Σ settled payments − Σ succeeded refunds. `ReservationDetail` @147. | Reconciles ✓. Design renders `{{ nsCharges }}` for Paid — coincidentally equal, not a formula. |
| Footer "Balance **€0.00**" | ✅ BUILD-NOW | `FolioTotals::for()['balance_minor']` = charges − paid. `ReservationDetail` @148-153 also cross-checks against `folios.balance_minor` (`'reconciles'`). | Reconciles ✓ (175 − 175 = 0). **Credit where due — unlike Wave 1, this folio balances.** |
| Footnote "Expedia payout settles with the monthly statement — no action needed" | 📋 DECORATIVE-FAKE | Brand not modelled; no OTA-payout-settlement concept anywhere (`payouts` is the **owner** payout table, a different thing entirely). | Don't port. |

### Dark "settled" panel (right column)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "Folio closed" label | 📋 DECORATIVE-FAKE | Same as the "Closed · read-only" chip — `folios.status` is never set to `'closed'`. | |
| Big balance "**€0.00**" | ✅ BUILD-NOW | `FolioTotals::for()['balance_minor']`. | |
| "balance · fee collected 12 Sep" | 🔨 NEEDS-BACKEND (M) | Depends on the no-show fee existing. The *date* would be `payments.processed_at`. | |
| Progress bar | 📋 DECORATIVE-FAKE | Hard-coded `width: 100%` in the markup — not bound to anything. | Could be honestly driven by `paid_minor / total_minor`. |
| "Fee €175 · night 2 resold" / "settled" | 📋 DECORATIVE-FAKE | `data-vyc="175"` hard-coded; "night 2 resold" unbacked (see above). | |
| **"View receipt"** button | 📋 DECORATIVE-FAKE (inert) | **Zero handlers.** Same as "Download receipt" — wire both to `route('app.reservations.folio-pdf', $id)` and they become ✅. | Two buttons, one real target. Consider collapsing to one. |

### Guest card (right column)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Avatar initials "DK" | ✅ BUILD-NOW | `FormatsOperatorData::initials()` @32-44 — exists, takes a name string. | Reuse. |
| Guest name "Daniel Kovács" | ✅ BUILD-NOW | `guests.first_name`/`last_name` via `leadGuest`. Operator surfaces may show names. | |
| **"1st booking"** | 🔨 NEEDS-BACKEND (S) | Derivable: `Reservation::where('lead_guest_id', $guest->id)->count()` (tenant-scoped). No scope/accessor exists. `InboxThread` already ships a comparable "DERIVED guest stay-stat" — check it for a reusable pattern first. | Cheap. Watch the scoping (per-org, and decide whether cancelled/no-show count). |
| **"Hungary"** (from nationality) | 🔨 NEEDS-BACKEND (S) | `guests.nationality` is `char(2)` — an **ISO code** (`'HU'`), not a country name. Grep: **no country-name map anywhere** in `app/Livewire` or `app/Support`. Needs: an ISO-3166 alpha-2 → name lookup in a formatter. | Or render `HU`. Trivial either way. |
| **Email "d.kovacs@mail.hu"** | ⚠️ **DECISION NEEDED** — technically ✅, but breaks the built precedent | `guests.email` is an `encrypted` cast (`Guest::casts()`) → **it decrypts transparently in Blade**, so rendering is *possible*. `$hidden = ['email','phone']` only blocks array/JSON serialization, **not** `{{ $guest->email }}`. **But** `ReservationDetail`'s own docblock @43 states the locked rule: *"Guest PII — name only; encrypted email/phone never rendered."* The Inbox follows the same rule (guest NAME shown, encrypted identity never rendered). | **This page would be the first operator surface to render decrypted guest contact PII.** That's a deliberate policy change, not a build detail. Escalate — do not quietly ship it. |
| **Phone "+36 30 555 2214"** | ⚠️ **DECISION NEEDED** — same as email | `guests.phone`, `encrypted` cast. Same precedent, same escalation. | |
| Amber callout "2 calls + 1 message before marking · no response logged" | 🔨 NEEDS-BACKEND (M) / ⬜ | Calls unmodelled (see above). "no response logged" implies an outreach-attempt log that doesn't exist. | |

### "No-show record" panel (right column)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Panel heading "No-show record" | ✅ BUILD-NOW | The concept is real — `reservation_status_history` is the append-only lifecycle audit. | Good framing; the panel is mostly buildable. |
| "Marked — 12 Sep · 23:30 · **auto**" | ✅ BUILD-NOW (timestamp) / 🔨 (S) (the "auto") | Timestamp: `reservation_status_history.created_at` where `to_status='no_show'`. The **"auto"** marker: `recordStatusHistory()` @615 sets `changed_by = Auth::id()` — so a scheduler-driven mark would leave it **NULL**, which *is* a usable "auto" signal — **once the scheduler exists** (it doesn't). Alternatively `audit_events.actor_type` enum has `'system'` (`2026_07_02_000700` @22). | Today every no-show is manual-or-nothing. |
| "Policy — **1-night fee**" | 🔨 NEEDS-BACKEND (M) — **no no-show fee policy exists** | `property_policies` (`2026_07_02_001010`) has a `cancellation` **json** column — but **no shape, no reader**: grep shows the *only* write is `DemoSeeder.php:123` → `['type' => 'flexible', 'free_until_days' => 2]`, and **no app code reads it**. `rate_plans.cancellation_policy` (json) likewise. `fees.kind` enum is exactly `['cleaning','other']` (`2026_07_03_000040`) — **no `no_show`**. Needs: a policy shape (nights/percent/flat) + a resolver + `fees.kind` extension so attribution can key off it deterministically. | This is the substance of the no-show state. It is **not** a small job. |
| "Charged to — Expedia VC ····3307" | 🔨 NEEDS-BACKEND (S+M) | Brand (M) + last-4 (S). See Source / virtual-card rows. | |
| "Night 2 — Released · resold" | 🔨 NEEDS-BACKEND (L) | See the partial-release trap. | |
| "Expedia — ✓ Reported 12 Sep" | 🔨 NEEDS-BACKEND (M/L) | **No OTA no-show-reporting concept.** `App\Services\Channex\*` exists for ARI/bookings; reporting a no-show back to the OTA is a channel-API capability that isn't built — and Channex staging is still an **outstanding live-integration checkpoint**. | Account-gated. Defer honestly. |
| **No-show reason** (not shown, but implied by "guest never arrived") | 🔨 NEEDS-BACKEND (S) | `reservation_status_history.reason` and `reservations.cancellation_reason` **both exist and are wired** — `terminate()` @601,603 persists them. But `markNoShow()` @329 hard-codes `null`. Needs: `markNoShow(Reservation $reservation, ?string $reason = null)` → `terminate($reservation, 'no_show', $reason)`. One-line signature change. | Cheapest real win on the page. `cancelReservation()` @321 already takes `?string $reason`. |

### Waive fee modal (`sc-if wfOpen` — **defaults to OPEN**)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Modal open state | 📋 DECORATIVE-FAKE (default) | `state = { wfOpen: true, wfAmt: 'full', wfReason: 'travel', wfNotify: true }` — the modal ships **open** so it renders in the design tool. Not a page state. | Don't mistake this for "the page opens with a modal". |
| Backdrop click-to-close | ✅ BUILD-NOW (UI) | `sc-camel-on-click="{{ closeWf }}"`. Pure UI. | |
| Modal body click-stop | ✅ BUILD-NOW (UI) | `sc-camel-on-click="{{ stopWf }}"` → `(e) => e.stopPropagation()`. Pure UI. | |
| Header "Waive no-show fee" | 🔨 NEEDS-BACKEND (M) | Depends on the fee existing. | |
| Sub "Daniel Kovács · VYR-10405 · Apt 103" | ✅ BUILD-NOW | Name + `reference` + `units.code` — all real. | |
| Close ✕ button | ✅ BUILD-NOW (UI) | `closeWf`. | |
| Fee strip "No-show fee · charged 12 Sep / Expedia virtual card ····3307 · settled / **€175.00**" | 🔨 NEEDS-BACKEND (M) | `{{ nsCharges }}` = hard-coded `VYC.fmt(175, 2)`. Brand + last-4 + fee all unbacked. | |
| Amount chip "**Full fee · €175.00**" | 🔨 NEEDS-BACKEND (S) — the *mechanism* exists | `wfPickFull` → `setState({wfAmt:'full'})`. Real: **`FolioService::refundPayment(Payment $original, Money $amount)` @216 fully supports PARTIAL amounts** — it validates `$amount->amountMinor() > $original->amount_minor` and throws only on *over*-refund. The blocker is the **controller**: `ReservationFolioActionController::refund()` @57 hard-codes the full amount: `new Money((int) $payment->amount_minor, …)`. Needs: accept + validate a request amount. | `€175.00` is the hard-coded `amts = { full: 175, half: 87.5 }`. |
| Amount chip "**Half · €87.50**" | 📋 DECORATIVE-FAKE (the "half" concept) + 🔨 (S) | `amts = { full: 175, half: 87.5 }` — a hard-coded constant, **not** a policy. A "half waiver" is an invented business rule. The underlying partial refund is a real capability (see above). | Ship an **amount input** (the existing `parseAmountMinor()` @63 already does major→minor), not a fabricated "Half" chip. Note `reservation-detail.blade.php` @116 currently lists *"Partial / custom refund — Soon"*. **This modal is that deferred item.** |
| Reason chip "Goodwill" | 🔨 NEEDS-BACKEND (S) | `wfPickGood`. See reason-storage row below. | |
| Reason chip "Guest rebooked" | 🔨 NEEDS-BACKEND (S) | `wfPickRebook`. | |
| Reason chip "Travel disruption" (default selected) | 🔨 NEEDS-BACKEND (S) | `wfPickTravel`. `state.wfReason = 'travel'`. | |
| Reason chip "Other" | 🔨 NEEDS-BACKEND (S) | `wfPickOther`. | |
| **Waiver reason storage** (the 4 chips above) | 🔨 NEEDS-BACKEND (S) | `refundPayment()` @238 hard-codes `'metadata' => ['refunds_payment_id' => $original->getKey()]` — **no reason param, no passthrough**. Two clean options, both small: (a) add `array $metadata = []` to the signature; or (b) **reuse the existing polymorphic `notes` table** — `Reservation` already `use HasNotes` (`app/Models/Reservation.php` @23), and `notes` has `notable` morph + `body` + `visibility('internal'\|'shared')` + `author_id` (`2026_07_02_001120`). | **Prefer (b) — reuse, don't extend the money path.** The reason vocabulary itself is free-form; these 4 chips are a design invention, not an enum. |
| "What happens" bullet 1 — "Posts a reversing waiver line — the original fee is never edited" | ✅ BUILD-NOW (copy) | **Accurate.** `refundPayment()` @213-216 docblock: *"Correct-by-reversal: a NEW offsetting Payment is created; the original row is never touched."* | Honest. Keep. |
| "What happens" bullet 2 — "{amt} refunds to the Expedia virtual card ····3307 · **3–5 business days**" | 📋 DECORATIVE-FAKE | `wfRefundLine` — brand + last-4 fabricated. **"3–5 business days" is invented** — no SLA data anywhere; the gateway result carries no ETA. | Drop the timing claim. |
| "What happens" bullet 3 — **"The waived amount comes off September's owner revenue for Apt 103"** | 📋 DECORATIVE-FAKE — **this claim is FALSE against the real engine** | See traps. Three independent reasons it can't be true today. | **The most dangerous line on the page** — it's a specific, checkable financial promise. |
| Notify toggle "Email the guest / A goodwill note with the refund summary · d.kovacs@mail.hu" | 🔨 NEEDS-BACKEND (M) | `toggleWfNotify` is real UI. Sending is not: the `App\Messaging` **email adapter is a config-gated skeleton that THROWS "not configured"** (invariant #24 — needs an inbound provider + a verified domain). `ManualChannel` works, but that's not an outbound email. Also renders decrypted PII (see guest card). | The Inbox composer already models the honest pattern: disabled + "not connected". **Reuse that treatment.** |
| **Primary CTA "Waive €175.00 · refund to Expedia VC"** | 📋 DECORATIVE-FAKE — **`data-toast` ONLY** | `data-toast="Fee waived"` and **no `sc-camel-on-click`**. It fires the design-system toast and mutates nothing — not even local state. The label `wfLabel` is composed from the hard-coded `amts` + the fabricated brand. **This is the entire point of the modal and it does nothing.** | A design-level toast is a mock, not a backend. The real action = `refundPayment()` + a reason note + (optionally) a reversing `discount` folio line. |
| "Keep the fee" (secondary) | ✅ BUILD-NOW (UI) | `closeWf`. | |
| Footer "Logged in the no-show record with your name · **Marco Reyes**" | ✅ BUILD-NOW (mechanism) / 📋 (the name) | Mechanism is real: `refundPayment()` @253 writes `audit_events` (`actor_type`/`actor_id`), and `recordStatusHistory()` @615 stamps `changed_by = Auth::id()`. `FormatsOperatorData::viewer()` @24 already returns the authed user's name for the topbar. | **"Marco Reyes" is fabricated demo data** — render `viewer()['name']`. |

### Ambient / decorative

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Gradient background + 2 light streaks | 📋 DECORATIVE-FAKE | Pure CSS. Already in the operator design system. | |
| 3 floating colour orbs | 📋 DECORATIVE-FAKE | Pure CSS. First orb is gated on `<sc-if value="{{ motion }}">`. | |
| `motion` boolean prop | 📋 DECORATIVE-FAKE | A **design-tool editor prop** (`data-props='{"motion": {"editor":"boolean","default":true,…}}'`), not a product feature. | Not a user setting. Don't build a toggle for it. |
| `vyFadeUp` / `vyShimmer` / `vyDrift` keyframes | 📋 DECORATIVE-FAKE | Pure CSS, staggered `animation-delay` per panel. | |
| Toast installer script | 📋 DECORATIVE-FAKE | The global `data-toast` click delegate. **This is why a naive grep reports "3 toasts"** — 2 of the 3 hits are the installer's own `closest('[data-toast]')` / `getAttribute('data-toast')`. There is exactly **ONE** real toast button on the page. | |

## WRITES

Only ONE write is even gestured at on this page, and in the design it does nothing.

1. **"Waive €X · refund to Expedia VC"** (modal primary CTA) — `data-toast="Fee waived"` only, **no onClick**. Inert.
   - Partial refund → **EXISTS: `App\Services\FolioService::refundPayment(Payment $original, Money $amount)`** (@216). Partial amounts genuinely supported (validates over-refund, throws; creates a NEW offsetting `refund` Payment; original never touched; `recompute()` re-derives totals).
   - Blocker is the **controller, not the service**: `App\Http\Controllers\ReservationFolioActionController::refund()` (@57) hard-codes FULL — `new Money((int) $payment->amount_minor, (string) $payment->currency)`. **NEEDS: accept + validate a request `amount`** (the existing `parseAmountMinor()` @63 already converts major→minor). Size **S**.
   - Waiver reason → **NEEDS: either `FolioService::refundPayment(…, array $metadata = [])`, or (preferred) reuse the existing polymorphic `notes` table** — `Reservation` already `use HasNotes`; `Note` has `notable` morph + `body` + `visibility` + `author_id`. Size **S**. Prefer the note: don't widen the money path for a free-text reason.
   - Reversing folio line ("posts a reversing waiver line") → **EXISTS: `FolioService::postCharge($folio, 'discount', …, negative Money)`** (@53); `folio_items.type` enum includes `'discount'`. Size **S**.

2. **"Email the guest"** toggle → **NEEDS: a configured outbound email channel.** The `App\Messaging` email adapter is a config-gated skeleton that **throws "not configured"** (invariant #24). Honest treatment already exists on the Inbox composer — reuse it.

3. **Mark-no-show itself** (not on this page, but this page is its consequence) → **EXISTS: `App\Services\AssignmentService::markNoShow(Reservation $reservation)`** (@329) → `terminate($reservation, 'no_show', null)` @586: releases every active/overbooked line through the guarded path, sets `status='no_show'` + `cancelled_at`, writes `reservation_status_history`, emits domain event `reservation.no_show` + an audit event. **ZERO production callers** — only `tests/Feature/AssignmentServiceTest.php:73`. NEEDS: a POST route + controller (operator-mutation pattern: POST → controller → service → PRG) on the *Cancelled/No-show* sibling screen. Size **S**.

4. **"Download receipt" / "View receipt"** → inert in the design, but **EXISTS: `route('app.reservations.folio-pdf', $id)` → `App\Http\Controllers\Operator\FolioPdfController::download()`** (tenant-scoped, branded, reconciles to the cent). Wire, don't build. Do **not** build on the `invoices` table — it exists (`2026_07_02_001530`) but has no service, no writer, no reader.

5. **"Rebook guest"** → inert. **NEEDS: `/app/reservations/create`** (a separate Wave 2 wizard). Not this page's job.

**No write on this page should be a Livewire wire-action** — the locked operator-mutation pattern is POST → controller → service → PRG (re-runs `SetTenantForWebRequest`; `{reservation}` resolved as a plain string via tenant-scoped `findOrFail`, never RMB → cross-org 404).

## ISOLATION / PII

**Tenant scoping — the pattern is already proven; copy it verbatim.**
`ReservationDetail` (@25-26, @59-61) resolves `{reservation}` as a **plain string prop** via a tenant-scoped `findOrFail()` in `render()` after `SetTenantForWebRequest` — never route-model-bound (RMB would trip the tenant scope before context is set). A cross-org id 404s. `/app/*` sits behind `auth` + **`EnsureBackOfficeAccess`** (the group-level gate from DECISIONS 2026-07-16 that closed the owner-walks-into-the-back-office hole). This is an operator surface → **tenant-scoped, never owner-scoped** (`OwnerScope` is inactive off-portal).

**Guest PII — this page proposes a real policy change. Escalate it.**
- **Names: fine.** Operator surfaces may render guest names (front-desk precedent). H1 "Daniel Kovács", the avatar initials, and the modal sub-header are all safe. `FormatsOperatorData::initials()` already exists.
- **Email + phone: ⚠️ this would be a FIRST.** The guest card renders `d.kovacs@mail.hu` and `+36 30 555 2214`. Both are `encrypted` casts on `Guest` — which means they **decrypt transparently in Blade**, so this is *technically possible*. `$hidden = ['email','phone']` only blocks array/JSON serialization, **not** `{{ $guest->email }}`. But the built precedent is explicit and locked: `ReservationDetail`'s own docblock @43 — *"Guest PII — name only; encrypted email/phone never rendered."* The Inbox holds the same line (guest NAME shown, encrypted identity never rendered). **No operator surface in Vyrobel today renders decrypted guest contact PII.** Rendering it here is a deliberate policy decision, not a build detail — take it to a decision, don't quietly ship it.
- The **notify toggle** repeats the email inline ("· d.kovacs@mail.hu") — same call.
- **GDPR still holds.** `Guest::anonymize()` nulls `first_name`/`last_name`/`email`/`phone` and stamps `anonymized_at` while preserving the row + id (reservation/financial FKs intact). This page **must degrade gracefully on an anonymized guest** — `ReservationDetail` @64 already handles it (`trim()` of two nulls → `''` → `'guest' => null`). Don't render a bare "DK" avatar or an empty H1 for an anonymized guest.

**Owner boundary.** The modal's owner-revenue claim ("comes off September's owner revenue for Apt 103") is an *operator-side* statement about owner money — it doesn't leak owner data to a guest or guest data to an owner, so there's no isolation breach. It's simply **not true** (see traps). Separately: owner surfaces show **no guest names** — irrelevant here since this is an operator screen, but relevant if any of this copy is ever mirrored into the owner portal.

## TRAPS (Wave-1 style — reconciliation, scoping, real enums, fabrication)

**1. "1 charged · 1 released" — the partial-night no-show charge does not exist (the page's central fabrication).**
`markNoShow()` → `terminate()` @594-596 loops **every** active/overbooked `reservation_unit` and releases it — never a subset, never a single night. It **never touches the folio**. So after a real no-show: both nights' `sold` are freed, and the room `folio_item` (if one was posted) **still sits on the folio at the full 2-night amount** — the folio would show a €350-ish charge with a €175 payment and a €175 **outstanding balance**, not the design's tidy €0.00. Building the design's model needs: a room-line reversal + a 1-night fee post + a policy resolver, spanning `AssignmentService` + `FolioService` + owner attribution. Size **L**. Everything downstream ("Nights: 1 charged · 1 released", the Released ledger row, "Night 2 · Released · resold", the dark panel's "night 2 resold", the whole no-show banner sub-line) inherits this.

**2. "The waived amount comes off September's owner revenue for Apt 103" — FALSE against `OwnerAttributionService`. Three independent reasons:**
- (a) `attributeAtVersion()` @109-116 filters `reservationUnits->where('status', 'active')` and **returns early if the set is empty**. After `markNoShow`, every line is `'cancelled'` → **a no-show reservation attributes nothing to any owner, ever.**
- (b) A no-show fee wouldn't be attributed even with active lines: `roomRevenueTotal()` @380 sums only `type='room'`; `cleaningFeeTotal()` @396 sums only fees whose `reference_id` is in `Fee::where('kind','cleaning')` — and **`fees.kind` is exactly `['cleaning','other']`** (`2026_07_03_000040`). A no-show fee is `'other'` → invisible to attribution.
- (c) Attribution isn't even auto-wired: grep for `attributeReservation` finds **only `DemoSeeder.php:366-367` and tests** — no listener, no job, no service call. Nothing would fire on a waiver regardless.
→ Do not ship this sentence. Making it true is **M** (extend `fees.kind`, add a no-show branch to attribution, and wire attribution to a lifecycle event) — and it must respect `PeriodLockGuard` + append-only reversal.

**3. Statuses — the design's vocabulary vs the real enums.**
| Design shows | Real? |
|---|---|
| "No-show" (title chip) | ✅ `reservations.status = 'no_show'` — already mapped in `FormatsOperatorData::reservationStatusMeta()` @81. |
| "Closed · read-only" / "Folio closed" | ⚠️ `folios.status` enum **has** `'closed'` — but **nothing in the codebase ever sets it.** Every folio is `'open'` forever. Legal value, invented state. |
| "Released" (ledger badge) | ❌ Invented. Not a `folio_items.type` (`room\|tax\|fee\|add_on\|product\|manual\|discount`). Not a `reservation_units.status` either (`active\|cancelled\|moved`). Exists nowhere. |
| "No-show" (ledger badge) | ❌ Not a `folio_items.type`. Would badge as **"Fee"**. |
| "Payment" (ledger badge) | ✅ `payments.type='payment'`. |
| "Reported 12 Sep" (Expedia) | ❌ No OTA no-show-reporting concept. |

**4. "Expedia" — the OTA brand is not modelled (confirmed).**
`reservations.source` = `('direct','walk_in','ota','corporate','group','agent','manual')`. `channels.provider` = `['channex']` **only** — that's the channel *manager*, not the OTA. `FormatsOperatorData::sourceLabel()` @99 renders `'ota' => 'OTA'`. **The honest render is "OTA".** "Expedia" appears **9 times** across this page (Source cell, no-show banner, 2 ledger rows, folio footnote, dark panel, No-show record ×2, modal fee strip + CTA label + refund bullet). All of it is fabricated. `····3307` likewise — `payments.card_token` is a gateway token, and there is **no `card_last4` column**.

**5. Money figures — the folio DOES reconcile (a genuine improvement on Wave 1).**
`nsCharges: VYC.fmt(175, 2)`, `nsBalance: VYC.fmt(0, 2)`, `nsPaymentLine: '−' + VYC.fmt(175, 2)`. Charges 175 − Paid 175 = Balance 0 ✓, and Σ ledger rows = 175 ✓. `FolioTotals::for()` computes exactly this formula (charges = Σ items; paid = Σ settled payments − Σ succeeded refunds; balance = charges − paid) and `ReservationDetail` @153 cross-checks it against `folios.balance_minor`. **But:** ledger row 3's amount renders `{{ nsBalance }}` — the design is **reusing the balance variable as a line amount** to print €0.00. That row is not a `folio_item` and must never be posted as one (the owner-statement fake-fee precedent). Render it outside the ledger list or not at all.

**6. Fabricated constants — do not port.**
`amts = { full: 175, half: 87.5 }` (the "Half" waiver is an **invented business rule**, not a policy) · `"3–5 business days"` (no SLA data exists anywhere) · `"Marco Reyes"` (use `FormatsOperatorData::viewer()['name']`) · `"VYR-10405"` / `"Daniel Kovács"` / `"Apt 103"` / `"12 Sep 23:30"` / `"d.kovacs@mail.hu"` / `"+36 30 555 2214"` / `"····3307"` · the two nav badges `2` and `2` · the dark panel's `width: 100%` progress bar · `"2 calls and a message"` (**calls are not modelled anywhere** — grep for `call_log`/`CallLog`/`telephony` returns nothing) · `"resold Sat 13 Sep"` · `"Reported 12 Sep"` · `"read-only until 5 Oct"`.

**7. "auto-marked at end of arrival day" — no scheduler exists.**
`routes/console.php` schedules exactly one job: `Schedule::command('invites:prune-expired')->daily()`. `markNoShow()` has **zero production callers**. Nothing on this page can honestly say "auto" until that command exists (**S** — the service is ready and tested).

**8. `data-vyc-zero` — genuinely unclear, flagging rather than guessing.**
An empty `<span data-vyc-zero>` in the paused banner, no handler in this bundle — it hooks the design system's global `VYC` helper (external script `74e9b83c-…`, not decoded). The sentence reads "…channels stopped selling · ⟨?⟩ while paused". I don't know what it renders.

**9. DemoSeeder has no no-show fixture.**
Grep for `no_show`/`expedia`/`kovacs` in `database/seeders/` → nothing. Whatever ships here needs a coherent seeded no-show (via `AssignmentService::markNoShow`, never a raw insert) or the screen is undemoable.

## BUILD-READINESS VERDICT

**~45% ✅ build-now · ~30% 🔨 needs-backend · ~20% 📋 decorative · ~5% ⬜ other-module.** But the percentage flatters it: the build-now half is the *frame* (shell, breadcrumb, name, status chip, stay dates, nights, unit, reference, the folio ledger + reconciled totals, avatar/initials, marked-timestamp, actor) — all of which `App\Livewire\ReservationDetail` **already renders today**. The 🔨 half is the *entire reason this screen exists*: the no-show fee, its policy, the partial-night charge, and the waiver.

**`docs/TIER1-SPEC.md` @151-165 rates this "SIMPLE · READY". That rating does not survive contact with the design.** The spec is right about its three named claims — `status='no_show'` ✅, `markNoShow` ✅, `refundPayment` ✅ — but it scoped the screen as "terminal state + banner + refund affordance". The actual design adds a **no-show fee charge model and a waive-fee flow**, and neither exists:
- **No no-show fee policy.** `property_policies.cancellation` is a json column with no shape and no reader (its only write in the entire codebase is `DemoSeeder.php:123`). `fees.kind` is exactly `['cleaning','other']`.
- **`markNoShow()` never touches the folio** and releases the *whole* stay — so "1 night charged · 1 night released" is not a variant of the built behaviour, it's a different subsystem (**L**).
- **`markNoShow()` has zero production callers.** Nothing in the app can currently produce this state.
- **The modal's owner-revenue promise is false** on three independent counts.

**Recommended approach — split into three, don't build the design as drawn:**

1. **BUILD NOW (small, honest, high value).** State-conditional rendering on the existing `ReservationDetail` — no-show banner, strikethrough dates, the "No-show record" panel from the real `reservation_status_history` + `cancelled_at` + `changed_by`. Plus four genuinely small wins already sitting there: wire the two receipt buttons to the **existing** `app.reservations.folio-pdf`; add `?string $reason` to `markNoShow()` (the columns are already wired, the method just hard-codes null); ship the **mark-no-show POST action** (service exists, tested, unreachable); and **land partial refund** — `FolioService::refundPayment()` already supports it, only `ReservationFolioActionController::refund()` @57 hard-codes the full amount. That last one clears the *"Partial / custom refund — Soon"* chip already sitting in `reservation-detail.blade.php` @116. **This is Wave 2 work and it's ready.**

2. **FLAG FOR DECISION (product, not engineering).** (a) **The no-show fee policy** — where does "1-night fee" come from? `property_policies`, `rate_plans.cancellation_policy`, or a new shape? This is the screen's premise and it's an unmade product decision. (b) **Guest contact PII** — rendering decrypted email/phone would make this the **first** operator surface to do so, against an explicitly locked precedent (`ReservationDetail` @43). (c) **Owner treatment of no-show fees** — does a no-show fee attribute to the owner at all? Answer that *before* anyone writes the waiver's owner-revenue line.

3. **DEFER HONESTLY.** OTA brand + card last-4 (needs the real Channex payload — a still-outstanding live checkpoint), OTA no-show reporting, outbound email (adapter throws "not configured"), the calls log, billing/paused-workspace, Rebook.

**The one-line summary for the build:** this page is a *thin, buildable state variant wrapped around a fee subsystem that doesn't exist*. Ship (1), decide (2), and let the waive-fee modal wait for a policy — its primary CTA does nothing in the design either, which is the design telling us the same thing.

---

# 4. REFUND flow (operator)

**Design file:** `docs/design/raw/Operator Refund.html` — 1,560,694 B · template 63,725 · 14 onClick · 2 toasts · 8 sc-for

**What it is.** A single-reservation refund composer on the operator shell: left column = refund amount (3 modes: Full / Partial / By-charge), reason chips + internal note, and a refund-destination picker (original card vs manual bank transfer); right column = a dark "Refunding" summary with the primary CTA, a guest card, and a "What happens" promise panel. A confirm modal (amount hero, breakdown, notify-guest toggle, Issue) leads to an in-page success state with a receipt reference, a receipt-PDF link, and "Issue another refund". It is genuinely interactive at the DESIGN level — 14 onClick + 2 onChange handlers all wired to real `setState` — but every figure it computes comes from two hard-coded constants (`PAID = 322.00` and a 3-item `itemDefs` array); `doIssue()` performs no network call, it writes `localStorage['vyrobel:refund:last']` and flips `issued: true`. Contrary to the brief, the page fires ZERO toasts: the 2 `data-toast` hits are the shared helper script in `<helmet>`, and no element carries a `data-toast` attribute.

## Feature inventory — every element

> **Verification note.** Every ✅ below was confirmed by reading the named file. The single most important finding: **`FolioService::refundPayment(Payment $original, Money $amount)` (`app/Services/FolioService.php:216`) DOES support a partial amount** (it validates `$amount->amountMinor() > $original->amount_minor` → throws) and is proven by `tests/Feature/FolioLedgerTest.php:23` and `tests/Feature/FolioPdfTest.php:73` ("partial refund → its own line"). It does **NOT** take a reason, does **NOT** take a destination, and does **NOT** post a `folio_item`. The design's three headline asks map to: amount ✅, reason 🔨S, destination 🔨M.

### Shell & chrome

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Operator sidebar shell (glass, orb/streak ambience, Vyrobel mark) | ✅ BUILD-NOW | `<x-operator.shell>` + `components/layouts/operator` + `FormatsOperatorData` | Reuse — do not redefine. Design's inline-style shell is superseded by the built one. |
| Nav: Dashboard / Calendar / Front desk / Housekeeping / Maintenance / Owners | ✅ BUILD-NOW | `shell.blade.php:24-39` → `route('app.dashboard')`, `app.rack`, `app.front-desk`, `app.housekeeping`, `app.maintenance`, `app.owners`, `app.payouts.run`, `app.inbox`, `app.units` | Shell already renders these. |
| Nav: "Reservations", "Insights", "Rates & availability", "Channels", "Reports", "Settings", "Security" | ⬜ OTHER-MODULE | `shell.blade.php` has `reservations`, `channels`, `rates`, `settings`, `security` with a **`null` route** → disabled "Coming soon" | Shell's nav-honesty rule already handles these. Reservations list is Wave 2 sibling; Insights/Reports are unbuilt. |
| "Property Switcher" (`<dc-import name="Property Switcher">`) | 📋 DECORATIVE-FAKE | No property switcher exists in `components/operator/shell.blade.php` | An unresolved design-system import — renders a 100%×52px placeholder box. Don't port; the built shell has none. |
| "Notif Bell" (`<dc-import name="Notif Bell">`, ×1 in title row) | 📋 DECORATIVE-FAKE | `shell.blade.php:93` — `<span class="vy-bell" title="Notifications — coming soon">`, inert | Precedent already set: the bell is a knowingly-dead affordance. |
| Viewer chip + Sign out | ✅ BUILD-NOW | `shell.blade.php:97-104` — `$viewer['initials']/['name']`, `route('logout')` POST | Not in this design, but comes free with the shell. |
| Toast system (`__vyToast` helper in `<helmet>`) | 📋 DECORATIVE-FAKE | — | **Zero `data-toast="…"` attributes exist on this page.** The 2 grep hits are lines 191/193 of the helper itself. The page fires no toast. Use the existing PRG flash (`->with('folio_notice', …)`, `ReservationFolioActionController:76`). |
| `motion` prop → gates one floating orb (`sc-if value="{{ motion }}"`) | 📋 DECORATIVE-FAKE | Design-tool prop (`data-props`), `default: true` | Presentation only. |

### Breadcrumb & title row

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Breadcrumb "‹ Reservations" | ⬜ OTHER-MODULE | No `/app/reservations` list route exists (`shell.blade.php:32` → `null`) | Wave 2 sibling page. Point at `app.reservations.show` or the shell until it lands. |
| Breadcrumb "VYR-10428" → reservation detail | ✅ BUILD-NOW | `Reservation::reference` + `route('app.reservations.show', $id)` (`routes/web.php:191`) | ⚠️ **The literal `VYR-10428` is fabricated.** Real refs are `'R-'.Str::upper(Str::random(8))` (`AssignmentService.php:648`) → e.g. `R-7QK2M9XZ`. Never port the VYR- format. |
| Breadcrumb "Refund" (current) | ✅ BUILD-NOW | Static crumb | — |
| H1 "Refund a guest" | ✅ BUILD-NOW | Static | — |
| Sub-line "Amara Okafor · VYR-10428 · Villa Azure · 12–15 Sep" | ✅ BUILD-NOW | `Reservation::leadGuest` (first_name/last_name), `reference`, `property->name`, `check_in`/`check_out` — exactly the `ReservationDetail::render()` pattern (`app/Livewire/ReservationDetail.php:63-91`) | Guest **NAME** on an operator surface is the established precedent. The literal values are fabricated. |
| "Cancel" link → reservation detail | ✅ BUILD-NOW | `route('app.reservations.show', $id)` | Plain link, no state. |

### Panel 1 — "Refund amount"

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Panel title + "Only settled payments can be refunded." | ✅ BUILD-NOW | **True and enforced twice**: `ReservationFolioActionController:53` (`status ∈ ['succeeded','captured']` && `type ∈ ['payment','capture']`) and `FolioService::refundPayment:218` (`type ∈ ['payment','capture']` else `InvalidArgumentException`) | An honest, backed statement. Keep it. |
| "Paid €322.00" tile (`{{ paidFig }}`) | ✅ BUILD-NOW | `FolioTotals::for($folio)['paid_minor']` (`app/Support/Folio/FolioTotals.php`) = Σ settled payments/captures − Σ succeeded refunds. Same formula `FolioService::recompute()` caches to `folios.paid_minor`. | ⚠️ **RECONCILIATION TRAP:** the design's `PAID = 322.00` is a JS constant. Must come from `FolioTotals` (the shared formula the on-screen folio AND the folio PDF already use) — never a re-derivation. |
| Segmented control: **Full refund / Partial / By charge** (`sc-for list="{{ rtypes }}"`, 3 buttons, each `t.pick` → `setState({rtype})`) | ✅ BUILD-NOW *(Full+Partial)* / 🔨 *(By charge — see below)* | Real handlers in the design. Full = `refundPayment($p, $p->amount_minor)` (already live). Partial = `refundPayment($p, Money::of($minor, $cur))` — **service supports it**. | Livewire `wire:model`/`$set` on a `$mode` property. |
| **FULL mode** — "Full paid amount / Reverses every settled charge on the folio" + `{{ fullFig }}` | 🔨 NEEDS-BACKEND (S) | `refundPayment` refunds **ONE** `Payment` row. A folio with 2+ settled payments cannot be "fully refunded" in one call. | ⚠️ **The copy is wrong twice:** (a) a refund reverses a **payment**, not a "charge" — `folio_items` are untouched; (b) "every settled charge" implies a multi-payment loop that doesn't exist. NEEDS: `FolioService::refundFolio(Folio $f, Money $amount)` that walks settled payments oldest-first, **or** a UI payment-picker. |
| **PARTIAL mode** — € prefix + decimal `<input>` (`onAmount` strips `[^0-9.]`) | ✅ BUILD-NOW | `refundPayment($payment, new Money($minor, $currency))`. Parse via the proven `ReservationFolioActionController::parseAmountMinor()` (`:63` — `(int) round($value * 100)`). | Money is integer minor units end-to-end; never a float. This is THE deferred feature this page unlocks. |
| Quick chips **25% / 50% / Max** (`sc-for list="{{ chips }}"`, `c.pick` → `setState({amount})`) | ✅ BUILD-NOW | Derived client-side from the real `paid_minor`: `PAID*0.25`, `PAID*0.5`, `PAID` | Pure derivation, no backend. ⚠️ Round in **minor units** (`intdiv($paid, 4)`), not `+(322*0.25).toFixed(2)` as the design does. |
| "Max refundable €322.00" note (`{{ maxRefundNote }}`) | 🔨 NEEDS-BACKEND (S) | **THE REAL GAP.** `refundPayment` guards only `$amount > $original->amount_minor` — it does **NOT** subtract prior refunds. Two €200 refunds against one €322 payment BOTH pass → `paid_minor` goes negative. | NEEDS: a cumulative guard in `FolioService::refundPayment` — `Σ payments(type=refund, status=succeeded, metadata->refunds_payment_id = $original->id)` + `$amount` ≤ `$original->amount_minor` → throw. **Ship this with the screen or the screen creates the bug.** |
| **BY-CHARGE mode** — checkbox list of folio lines (`sc-for list="{{ charges }}"`, `c.toggle`), per-row label / "Posted {date}" / amount / check icon | 🔨 NEEDS-BACKEND (M) | Rows themselves are ✅ (`FolioItem`: description, `service_date`/`posted_at`, `amount_minor` — `ReservationDetail::buildFolio()` already renders exactly this). But **refunds attach to a `Payment`, not a `FolioItem`** — there is **no FK, no link, no `reverses_item_id`** between the two tables. | NEEDS a decision + build: to actually "reverse a charge" you must `postCharge($folio, 'discount', "Reversal of …", $negativeMoney, reference: $item)` (`folio_items.type` enum **does** include `'discount'`; `Money` allows negative — `Money::isNegative()`) **AND** `refundPayment`. No `creditItem()`/`reverseItem()` method exists. Size M: a service method + a `reverses_folio_item_id` column (or reuse the existing `reference` morph). |
| By-charge "Selected total" + `{{ chargeSubtotal }}` | ✅ BUILD-NOW *(as a sum)* | Σ selected `folio_items.amount_minor` | Trivially derivable once the rows are real. |
| Hard-coded charge rows: `{id:'room', 'Room · 1 night', '12 Sep', 259.00}`, `{id:'tax','City tax','12 Sep',18.00}`, `{id:'bfast','Breakfast × 2','13 Sep',45.00}` | 📋 DECORATIVE-FAKE | `itemDefs` array, template line 677-681 | **Do not port.** They do sum to 322.00 = `PAID` (internally consistent — unusually honest for these designs), but they are invented. Real types are the `folio_items` enum: `room, tax, fee, add_on, product, manual, discount`. Note "Breakfast × 2" = an `add_on` with `quantity: 2`. |

### Panel 2 — "Reason"

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Panel title + "Recorded on the folio and in reports." | 🔨 NEEDS-BACKEND (S) | **Not currently true.** No reason is persisted anywhere today. | See the two rows below. "…and in reports" is an extra promise (no reporting surface consumes a refund reason). |
| 5 reason chips: **Service issue / Goodwill / Duplicate charge / Cancellation / Price adjustment** (`sc-for list="{{ reasons }}"`, `r.pick`) | 🔨 NEEDS-BACKEND (S) | **NO reason enum, column, or vocabulary exists anywhere in the repo.** `payments` has no `reason` column (`2026_07_02_001540_create_payments_table.php`). `refundPayment` has no `$reason` param and **hard-overwrites** metadata: `'metadata' => ['refunds_payment_id' => $original->getKey()]` (`FolioService.php:238`). | ⚠️ **INVENTED VOCABULARY** — `service`/`goodwill`/`duplicate`/`cancellation`/`price` are design fiction, not a real enum. NEEDS: `refundPayment(..., ?string $reason = null)` merging into `metadata` (json, already there — cheapest), **or** an `enum reason` column. Also needs an explicit vocabulary decision — do not silently adopt these 5. |
| "Add an internal note (optional)" textarea (`onNote` → `setState({note})`) | ✅ BUILD-NOW | **The shared polymorphic `notes` table already covers this.** `database/migrations/2026_07_02_001120_create_notes_table.php` — `ulidMorphs('notable')`, `body`, `enum visibility ['internal','shared']`, `author_id`. `App\Models\Note` + `HasNotes` trait; **both `Reservation` and `Folio` use `HasNotes`**; morph map has `'reservation'`, `'folio'`, `'payment'` (`AppServiceProvider.php:150-156`). | Write with `$folio->notes()->create(['body' => $note, 'visibility' => 'internal', 'author_id' => Auth::id()])`. ⚠️ Caveat: **nothing in `app/` currently writes a `Note`** — this would be the first writer. Table+model+relation+morph-map all real, so ✅ not 🔨. **Do not invent a `refund_notes` table.** |

### Panel 3 — "Refund to" (destination)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Panel title + "Original payment method is fastest for the guest." | 📋 DECORATIVE-FAKE | Marketing copy | — |
| **Radio: "Original card · Visa ····4242"** (`pickCard`, selected by default `dest:'card'`) | 🔨 NEEDS-BACKEND (S) | ⚠️ **`Visa` and `4242` do not exist in the schema.** Repo-wide grep for `last4|last_four|card_brand|brand` returns only `properties.brand` (a hotel brand). `payments.card_token` is a **gateway token only**, and is in `Payment::$hidden`. | Refunding *to the original card* is genuinely how it works (`$this->gateway->refund($original->gateway_ref, $amount)` → Stripe refunds against the payment_intent). But you **cannot render brand/last4**. NEEDS: `payments.card_brand` + `payments.card_last4` columns populated from the gateway response — **or** drop to honest copy ("Original payment method · card on file"). |
| **Radio: "Manual bank transfer · Enter the guest's IBAN · SEPA"** (`pickBank`) | 🔨 NEEDS-BACKEND (M) | **Architecturally not expressible today.** `FolioService` holds **one** container-bound `PaymentGateway` chosen by a single global config: `config('services.payments.gateway')` → `StripeGateway` else `ManualPaymentGateway` (`AppServiceProvider.php:89-91`; default `'manual'`, `config/services.php:70`). `refundPayment` cannot select a destination per-refund. | The `payments.gateway` enum *does* include `'bank_transfer'` and `'manual'`, so the data model tolerates it. NEEDS: a `$gateway`/destination param (or a gateway-resolver) on `refundPayment` + a guest IBAN store. **There is no guest IBAN field** — `guests` has no bank columns (owner IBAN/payout-method is itself already on the deferred register, `PROJECT-STATE.md:435`). Also note "Enter the guest's IBAN" implies an input the design never renders. |
| Radio dots / icon / border state styling (`cardDot`, `bankDot`, `dStyle()`) | ✅ BUILD-NOW | Pure presentation off local state | — |

### Right column — dark "Refunding" summary card

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "REFUNDING" + big `{{ refundFig }}` | ✅ BUILD-NOW | Derived from mode: full→`paid_minor`, partial→clamped input, charge→Σ selected. `Math.min(PAID, Math.max(0, v))` clamp is the right instinct — port it in minor units. | — |
| "of €322.00 paid" (`{{ paidFig }}`) | ✅ BUILD-NOW | `FolioTotals::for($folio)['paid_minor']` | Same source as the Paid tile — must be the identical value. |
| "To → {{ destLabel }}" (`'Visa ····4242'` \| `'Manual bank transfer'`) | 📋 DECORATIVE-FAKE | `destLabel` ternary, template line 721 | Fake for the card branch (no brand/last4 stored); the bank branch is a label for a path that doesn't exist. |
| "Arrives → {{ methodEta }}" (`'Original card · 2–5 business days'` \| `'SEPA transfer · 1–2 business days'`) | 📋 DECORATIVE-FAKE | Hard-coded strings, template line 722 | **No settlement-ETA data exists.** Neither `GatewayResult` nor `StripeRefund` (`{id, status}` only) carries an ETA. Pure invention — do not fake. |
| Primary CTA "Review & issue refund" (`openConfirm`, guarded `if (refundTotal > 0.001)`) | ✅ BUILD-NOW | Opens the modal; the real write is behind it | The float epsilon disappears in minor units (`> 0`). |
| Disabled state CTA "Enter an amount" (`cannotIssue`, `cursor:default`, **no onClick**) | ✅ BUILD-NOW | `canIssue = refundTotal > 0.001` | Correct, honest empty-ish state. Mirror as `@disabled($minor <= 0)`. Backed by the controller too: `parseAmountMinor` ≤ 0 → "Enter a payment amount greater than zero." |

### Right column — guest card

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Avatar initials "AO" | ✅ BUILD-NOW | Derived from `Guest::first_name`/`last_name`; `FormatsOperatorData` already does initials (`$viewer['initials']`) | — |
| Guest name "Amara Okafor" | ✅ BUILD-NOW | `Reservation::leadGuest` → `first_name`/`last_name` | Operator surface → **name is allowed** (front-desk/ReservationDetail precedent). Literal value fabricated. |
| **Guest email "amara.okafor@mail.com"** | 🔨 NEEDS-BACKEND (S) — *policy, not plumbing* | `guests.email` is an **`encrypted` cast** (`Guest::casts()`) over a TEXT column, **and is listed in `Guest::$hidden`**. It is *technically* decryptable and renderable. | ⚠️ **PII DECISION REQUIRED.** The built precedent is explicit — `ReservationDetail`'s docblock: *"Guest PII — name only; encrypted email/phone never rendered."* This design breaks that precedent. TIER1-SPEC's Guests-CRM section says contact renders on the staff surface "per policy" — **that policy is not written down**. Flag for decision; do not render the email by default. Also: if `Guest::isAnonymized()`, name/email are NULL — needs a fallback. |

### Right column — "What happens" info note

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| ✓ "A reversing line is posted to the folio automatically." | ✅ BUILD-NOW *(with a wording fix)* | **Half-true.** `refundPayment` appends a `Payment{type:'refund'}` row — verified: the transaction contains only `Payment::create`, **no `FolioItem`**. `ReservationDetail::paymentEntry()` renders it as its own ledger line ("Refund / Reverses a payment / +€X"). | ⚠️ It is a **payments** row, not a `folio_item`. Charges are unchanged; `paid` drops; **`balance` goes UP** (`recompute()`: `paid = received − refunded; balance = total − paid`). Say "a reversing entry is appended to the ledger" — the correct-by-reversal invariant (#7) is genuinely honoured. |
| ⚠️ "The owner statement adjusts on the next close." | 🔨 NEEDS-BACKEND (L) | ⚠️ **FALSE TODAY — the headline trap on this page.** Two independent reasons: (1) **`OwnerAttributionService` has ZERO production callers.** Repo-wide grep for `attributeReservation`/`reverseReservation`/`reattributeReservation` outside the service returns **only `tests/` and `database/seeders/DemoSeeder.php:366-367`**. (2) Even if invoked, attribution reads **`folio_items` + `reservation_units`** — a refund touches **neither**, so owner revenue would not move by a cent. `payment.refunded` IS recorded to `domain_events`+`audit_events` (`FolioService:249,253`), but **no listener consumes it** — the only three are `RecomputeAvailabilityOnInventoryChange`, `GenerateDepartureCleanTask`, `EnqueueAriPushOnInventoryChange` (`AppServiceProvider:185-187`). | NEEDS a subsystem: a negative `folio_item` credit on refund + a `payment.refunded` → `reattributeReservation` listener + `PeriodLockGuard` handling (post-lock ⇒ carry-forward per invariant #17). **Delete the claim or build it — do not ship this sentence.** |
| ⚠️ "Refunds can't be reversed once issued." | ✅ BUILD-NOW | **True.** No un-refund method exists; `FolioService` is append-only; `refundPayment` rejects `type='refund'` as an input (only `['payment','capture']` are refundable). | An honest, backed warning. Keep. |
| Info icon / warning-triangle icons | ✅ BUILD-NOW | Presentation | — |

### Confirm modal — "Issue refund?"

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Backdrop + blur, click-to-close (`closeConfirm`) and inner `stopClick` (`e.stopPropagation()`) | ✅ BUILD-NOW | Design-level modal mechanics | Real handlers. |
| Header "Issue refund?" + "Amara Okafor · VYR-10428" | ✅ BUILD-NOW | `leadGuest` name + `Reservation::reference` | Literals fabricated. |
| Close "✕" button (`closeConfirm`) | ✅ BUILD-NOW | — | — |
| Amount hero "Refund amount / {{ refundFig }}" | ✅ BUILD-NOW | Derived (as above) | — |
| Breakdown rows: To / Arrives / Reason | 📋 + 🔨 | To = 📋 (fake brand/last4), Arrives = 📋 (invented ETA), Reason = 🔨 S (no store) | Composite row — see the individual rows above. |
| **"Email the guest a refund confirmation" toggle** (`toggleNotify`, default `notify: true`) | 🔨 NEEDS-BACKEND (M) | **No guest-facing mail exists.** `app/Mail/` contains exactly **two** classes: `OwnerInviteMail.php`, `PasswordResetMail.php`. `app/Notifications/` **does not exist**. | NEEDS: a `RefundConfirmationMail` Mailable + blade + a queued send + the decrypted `guests.email` (PII decision above). ⚠️ **And a production mail provider** — `MAIL_MAILER=smtp`→Mailpit is dev/demo only (CLAUDE.md). Defaulting this ON while it silently does nothing is exactly the stub-and-pretend the honesty rule forbids. |
| Toggle knob/track animation (`notifyBg`, `notifyKnob: '19px'/'3px'`) | ✅ BUILD-NOW | Presentation off local state | — |
| **"Issue refund · €322.00" primary** (`doIssue`) | 🔨 NEEDS-BACKEND (S) *(orchestration only)* | The **write itself is ✅ REAL**: `FolioService::refundPayment($payment, $money)` via the proven PRG pattern — `POST route('app.reservations.refund')` → `ReservationFolioActionController::refund` (`routes/web.php:199`). | ⚠️ But the controller today **hard-codes FULL**: `new Money((int) $payment->amount_minor, …)` (`:57`). NEEDS: accept an `amount` input + the cumulative guard + a **payment picker** (the design has none — `refundPayment` requires a specific `Payment`; the controller currently reads `$request->input('payment')`). See "Writes". |
| "Cancel" secondary (`closeConfirm`) | ✅ BUILD-NOW | — | — |
| Footnote "A reversing line posts to the folio · refunds can't be undone" | ✅ BUILD-NOW | Same as the two ✓ rows above | Second half fully true; first half needs the "ledger entry" wording fix. |

### Success state (`sc-if value="{{ issued }}"`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Success hero: gradient check circle + "Refund issued" | ✅ BUILD-NOW | Render on the PRG redirect / flash (`->with('folio_notice', 'Refund issued.')` already exists, `:59`) | — |
| Sub "€322.00 is on its way to Amara Okafor" (`successSub`) | ✅ BUILD-NOW | Refund amount + guest name | — |
| Receipt row — **Amount** | ✅ BUILD-NOW | The created `Payment{type:'refund'}->amount_minor` | Reconciles to the ledger by construction. |
| Receipt row — **To** (`destLabel`) | 📋 DECORATIVE-FAKE | — | See destination rows. |
| Receipt row — **Arrives** (`methodEta`) | 📋 DECORATIVE-FAKE | — | Invented ETA. |
| Receipt row — **Reason** (`reasonLabel`) | 🔨 NEEDS-BACKEND (S) | No reason store | — |
| Receipt row — **Reference** `{{ refundRef }}` | 🔨 NEEDS-BACKEND (S) | ⚠️ **Fabricated:** `'RFND-' + String(Date.now()).slice(-6)` (template line 756). | Real candidates: `payments.gateway_ref` — `manual_ref_<base32 ulid>` from `ManualPaymentGateway::reference('ref')`, or Stripe's `re_…` (`StripeGateway::refund` → `$refund->id`); or the refund `Payment`'s ULID `id`. **No `RFND-NNNNNN` sequence exists.** If you want a human-readable ref, that's a new generator (mirror `AssignmentService::generateReference()`). |
| Email footnote (`emailNote`: "Confirmation emailed to amara.okafor@mail.com" \| "No email sent to the guest") | 🔨 NEEDS-BACKEND (M) | Depends on the mail build above | The *false* branch ("No email sent") is honest and free; the *true* branch is a lie until the Mailable exists. |
| "Back to reservation" primary link | ✅ BUILD-NOW | `route('app.reservations.show', $id)` | With PRG this is just where the redirect lands. |
| **"Download receipt"** → `Guest Refund Receipt PDF.dc.html` (`target="_blank"`) | 🔨 NEEDS-BACKEND (S) | **No refund-receipt PDF exists.** `app/Support/Pdf/` has `FolioDocument`, `OwnerStatementDocument`, `OwnerRemittanceDocument`, `OwnerBookingSummaryDocument` — no refund receipt. `resources/views/pdfs/` has folio + 3 owner templates. | The pattern is proven and cheap: `PdfService::download('pdfs.refund-receipt', RefundReceiptDocument::data($payment), $filename)` + a tenant-scoped route mirroring `FolioPdfController` (string id → `findOrFail` → cross-org 404). **Separate design page — inventory it separately.** Note it's labelled *Guest* receipt → an audience/access decision. |
| **"Issue another refund"** (`issueAnother` → `setState({issued:false})`) | 📋 DECORATIVE-FAKE | Design-only state reset | In a PRG build this is simply a link back to the refund screen. Harmless, but it's not a feature — don't build SPA state for it. |
| `localStorage.setItem('vyrobel:refund:last', {...})` in `doIssue()` | 📋 DECORATIVE-FAKE | Design-only handoff to the receipt-PDF mock page | **Do not port.** Wrapped in `try{}catch(e){}`. The real handoff is the refund `Payment` id in the URL. |

### Cross-cutting

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Route for the page itself | 🔨 NEEDS-BACKEND (S) | Net-new: `Route::get('/reservations/{reservation}/refund', OperatorRefund::class)->name('reservations.refund-form')` inside the existing `['auth', EnsureBackOfficeAccess::class]` + `prefix('app')` group (`routes/web.php:140`) | ⚠️ Name-collides with the existing `app.reservations.refund` **POST** — pick a distinct name. Resolve `{reservation}` as a **plain string** → tenant-scoped `findOrFail` (never RMB), per the locked pattern. |
| **`payments.refund` permission gate** | ✅ BUILD-NOW | **The permission genuinely exists**: `config/authorization.php:111`. Held by `Property Owner` (`['*']`), `Group Admin` (`:147`), `Accountant` (`:204`). **NOT** `Hotel Manager` (`:160` — record only), **NOT** `Front Desk` (`:179` — record only). | ⚠️ **The existing refund route has NO `can:` middleware** (`routes/web.php:199`) — a Front Desk user can currently POST a refund. A dedicated refund screen is the moment to add `->middleware('can:payments.refund')` and hide the entry point. `OwnerInviteService:63` explicitly calls giving a unit owner `payments.refund` a trap. |
| Tenant scoping | ✅ BUILD-NOW | `BelongsToOrganization` on `Payment`, `Folio`, `FolioItem`, `Reservation`, `Note`; `SetTenantForWebRequest` on the web group | Established. |
| Audit + domain events | ✅ BUILD-NOW | `FolioService::refundPayment` already writes both: `domain_events` `payment.refunded` (`:249`) + `audit_events` `payment.refunded` (`:253`) | Invariant #8 satisfied for free. |
| Status vocabulary shown on this page | ✅ / n/a | The page shows **no reservation status badge at all** | Unusually clean — no invented statuses here (unlike Wave 1). The only vocabulary risk is the **reason** chips (invented) and the **payment_status** trap below. |
| `reservations.payment_status = 'refunded'` | 🔨 NEEDS-BACKEND (S) | ⚠️ **Dead enum value.** The enum has it (`…_create_reservations_table.php:33`) but `grep -rn "'refunded'" app/` returns **nothing**. `FolioService::paymentStatus()` (`:376`) can only ever return `paid`/`partial`/`unpaid`. **After a full refund `paid=0` → the reservation reads `'unpaid'`, not `'refunded'`.** | Surprising to an operator ("I refunded it, why does it say unpaid?"). NEEDS a `paymentStatus()` branch (needs a refund-aware signal, since `paid=0` is ambiguous with never-paid). Flag for decision — it's a real product wart this screen will expose. |

## WRITES

Only ONE real write action on this page (plus one optional side-write). Everything else is navigation or local state.

1. **"Issue refund · €X" (`doIssue`, confirm modal)** — the sole money write.
   - **EXISTS:** `App\Services\FolioService::refundPayment(Payment $original, Money $amount): Payment` (`app/Services/FolioService.php:216`) — append-only, creates a NEW `Payment{type:'refund'}`, never touches the original (proven: `tests/Feature/FolioLedgerTest.php:12` asserts the original's amount/type/status are unchanged and the row count goes 1→2). **Partial amounts ARE supported** (`tests/Feature/FolioPdfTest.php:73`).
   - **EXISTS:** the transport — `POST /app/reservations/{reservation}/refunds` → `route('app.reservations.refund')` → `App\Http\Controllers\ReservationFolioActionController::refund()` (`routes/web.php:199`), PRG, tenant-scoped, cross-org → 404.
   - **NEEDS (S):** `ReservationFolioActionController::refund` currently hard-codes FULL — `new Money((int) $payment->amount_minor, (string) $payment->currency)` (`:57`). Must accept an `amount` input (reuse the existing `parseAmountMinor()`, `:63`).
   - **NEEDS (S) — SHIP-BLOCKER:** a **cumulative over-refund guard** in `FolioService::refundPayment`. Today the only check is `$amount->amountMinor() > $original->amount_minor` (`:221`) — prior refunds are NOT subtracted, so N partial refunds can exceed the payment and drive `folios.paid_minor` negative. A partial-refund UI makes this reachable for the first time.
   - **NEEDS (S) — a payment picker:** `refundPayment` needs a **specific `Payment`**. The design never asks which payment; it shows only an aggregate "Paid €322.00". With 2+ settled payments the UI cannot form the call. Either add a picker, or `NEEDS: FolioService::refundFolio(Folio $folio, Money $amount)` that walks settled payments oldest-first within one transaction.
   - **NEEDS (S):** `refundPayment(..., ?string $reason = null)` — no reason is persisted today, and the method hard-overwrites `metadata` with `['refunds_payment_id' => …]` (`:238`), so a caller cannot even smuggle one in.
   - **NEEDS (M):** destination selection — `refundPayment` uses the single container-bound `PaymentGateway` (`AppServiceProvider.php:89`); there is no per-refund gateway/destination argument.

2. **Internal note (textarea)** — optional side-write.
   - **EXISTS:** `$folio->notes()->create([...])` / `$reservation->notes()->create([...])` — `App\Models\Note` + `HasNotes` (both `Folio` and `Reservation` use the trait); `notes` table has `body`, `visibility ∈ ['internal','shared']`, `author_id`, `ulidMorphs('notable')`; morph map registers `'folio'`/`'reservation'`/`'payment'`.
   - No service method needed (there is no `NoteService`), but note this would be the **first `Note` writer in the codebase** — nothing in `app/` currently creates one.

3. **"Email the guest a refund confirmation" toggle** — `NEEDS: App\Mail\RefundConfirmationMail` (+ blade + queued send + a real prod mail provider). `app/Mail/` has only `OwnerInviteMail` + `PasswordResetMail`; `app/Notifications/` does not exist.

4. **"Download receipt"** — `NEEDS: App\Support\Pdf\RefundReceiptDocument` + `resources/views/pdfs/refund-receipt.blade.php` + a tenant-scoped route (mirror `FolioPdfController`, reuse `PdfService::download`).

**NOT writes (design-local state only):** refund-type segmented control, % chips, charge checkboxes, destination radios, reason chips, notify toggle, open/close confirm, "Issue another refund", and the `localStorage['vyrobel:refund:last']` stash in `doIssue()`.

## ISOLATION / PII

**Tenant scoping — solid, precedent exists.**
- Every table this page touches carries `organization_id` + `BelongsToOrganization`: `Payment`, `Folio`, `FolioItem`, `Reservation`, `Note`.
- The page must follow the locked operator pattern (verbatim from `ReservationDetail` + `ReservationFolioActionController`): `{reservation}` is a **plain string param**, resolved AFTER `SetTenantForWebRequest` via a tenant-scoped `findOrFail` — **never route-model-binding** → a cross-org id 404s.
- The refund POST already double-scopes correctly and should be copied: `Payment::query()->where('reservation_id', $model->id)->findOrFail($request->input('payment'))` (`ReservationFolioActionController:49-51`) — the payment must belong to THIS reservation AND is itself tenant-scoped.

**Permission boundary — a real gap this screen should close.**
- `payments.refund` genuinely exists (`config/authorization.php:111`) and is deliberately narrow: **Property Owner, Group Admin, Accountant** hold it; **Hotel Manager and Front Desk hold `payments.record` but NOT `payments.refund`** (`:160`, `:179`).
- ⚠️ **The existing refund route carries no `can:` middleware** (`routes/web.php:199`) — the group is only `['auth', EnsureBackOfficeAccess::class]`. A Front Desk user can POST a refund today. Gate the new screen with `can:payments.refund` and hide the CTA on `ReservationDetail` accordingly. (`OwnerInviteService:63` flags granting a unit owner `payments.refund` as a known trap that `DemoSeeder` once fell into.)

**Guest PII — the one thing on this page that breaks precedent.**
- **Guest NAME ("Amara Okafor") is fine** — operator surface; `ReservationDetail`/`FrontDesk` already render lead-guest names.
- **Guest EMAIL ("amara.okafor@mail.com" in the guest card + the success footnote) breaks the established rule.** `guests.email` is an `encrypted` cast over TEXT and sits in `Guest::$hidden`. It is *decryptable and renderable* — but `ReservationDetail`'s docblock states the posture explicitly: *"Guest PII — name only; encrypted email/phone never rendered."* TIER1-SPEC defers to an unwritten "per policy" for the Guests CRM. **This is a decision, not a build task** — flag it; default to name-only unless the policy is written down first.
- **Anonymized guests:** `Guest::anonymize()` nulls `first_name`/`last_name`/`email` and sets `anonymized_at` while keeping the row (financial FKs intact). A refund on an anonymized guest's reservation is legitimate — the page needs a null-safe fallback for the header, the guest card, and the email toggle (which must be force-disabled). The design has no such state.
- **Card data:** `payments.card_token` is token-only and `$hidden` — correctly never rendered. The design's "····4242" is not a PII leak; it's simply data that does not exist.
- **Owner boundary:** not applicable — this is an operator-only surface. Worth noting the *inverse* though: the design's "the owner statement adjusts on the next close" promises a cross-boundary effect into `owner_ledger` that does not happen (see traps).

## TRAPS (Wave-1 style — reconciliation, scoping, real enums, fabrication)

**1. "The owner statement adjusts on the next close." — FALSE. The headline trap.**
Two independent reasons, both verified:
- `OwnerAttributionService::attributeReservation` / `reverseReservation` / `reattributeReservation` have **ZERO production callers**. A repo-wide grep (excluding the service itself and `vendor/`) returns **only `tests/Feature/*` and `database/seeders/DemoSeeder.php:366-367`**. Nothing in `app/` ever invokes attribution.
- Even if it were invoked, attribution reads **`folio_items` + `reservation_units`** (its own docblock: *"It is READ-ONLY over the operational data: it reads `folio_items` … and `reservation_units`"*). A refund creates a **`payments`** row and touches neither → **owner revenue would not move by one cent.**
- `payment.refunded` IS written to `domain_events` + `audit_events` (`FolioService:249,253`), but **no listener consumes it** — the only three registered are `RecomputeAvailabilityOnInventoryChange`, `GenerateDepartureCleanTask`, `EnqueueAriPushOnInventoryChange` (`AppServiceProvider:185-187`).
→ **Delete the sentence or build the subsystem (L).** Shipping it verbatim is a false promise about money.

**2. Every money figure traces to two JS constants — must be re-sourced.**
`const PAID = 322.00` (line 676) and `itemDefs = [room 259.00, tax 18.00, bfast 45.00]` (677-681). They *do* sum to 322.00 — internally consistent, which is more honest than Wave 1's non-reconciling totals — but wholly fabricated.
- **Named source for "Paid":** `App\Support\Folio\FolioTotals::for($folio)['paid_minor']` = Σ `payments(type ∈ payment|capture, status ∈ succeeded|captured)` − Σ `payments(type=refund, status=succeeded)`. This is the SAME formula `FolioService::recompute()` caches into `folios.paid_minor` and that both `ReservationDetail` and the folio PDF already use. **Use `FolioTotals` — do not re-derive**, or the screen will drift from the folio it is refunding.
- **Named source for the charge rows:** `folio_items` (`description`, `service_date`/`posted_at`, `amount_minor`) — exactly what `ReservationDetail::buildFolio()` already assembles. Real types are the enum `room, tax, fee, add_on, product, manual, discount` — not "Breakfast × 2" as a label.
- **Scoping:** `paid` must be scoped to **this folio** (`folio_id`), not this reservation and not this guest. `Folio` is 1:1 with `Reservation` via `firstOrCreate(['reservation_id' => …])`, so they coincide today — but query the folio.

**3. Cumulative over-refund — a live bug this screen would newly expose.**
`refundPayment` guards ONLY `$amount->amountMinor() > $original->amount_minor` (`:221`). Prior refunds against the same payment are not subtracted. Two €200 refunds on one €322 payment both pass → `paid_minor` = −78, `balance_minor` > `total_minor`. Today unreachable because the only UI does full refunds; **a partial-refund screen makes it reachable on day one.** The design's own "Max refundable €322.00" copy asserts a cap that the service does not enforce. Ship the guard with the screen.

**4. Invented status/reason vocabulary.**
- The 5 reason chips — `service` / `goodwill` / `duplicate` / `cancellation` / `price` — map to **no enum, column, or constant anywhere**. `payments` has no `reason` column. Pure design fiction; needs an explicit vocabulary decision.
- The page shows **no reservation status badge**, so — unlike Wave 1 — there are no invented statuses to map. The real enums remain: `reservations.status` = `enquiry|tentative|confirmed|checked_in|checked_out|cancelled|no_show`; `reservations.payment_status` = `unpaid|partial|paid|refunded|charged_back`; `payments.status` = `pending|succeeded|failed|refunded|authorized|captured|voided`; `payments.type` = `payment|refund|deposit_hold|capture|void|chargeback`.

**5. `payment_status = 'refunded'` is a DEAD enum value.**
`grep -rn "'refunded'" app/` → nothing. `FolioService::paymentStatus()` (`:376`) can only return `paid`/`partial`/`unpaid`. **After a full refund, `paid = 0` → the reservation flips to `'unpaid'`, never `'refunded'`.** This screen will make an operator ask "I just refunded it — why does it say unpaid?" Flag for decision.

**6. A refund INCREASES the balance — the design never says so.**
`recompute()`: `paid = received − refunded`, `balance = total − paid`. Refunding €322 on a €322 folio → `paid = 0`, `balance = €322` — the guest now "owes" again, because `folio_items` (the charges) were never reversed. The design's "Reverses every settled charge on the folio" copy actively implies the opposite. If the intent is *"the guest owes nothing and got their money back"*, the refund must be paired with a **negative `folio_item`** (`postCharge($folio, 'discount', …, $negativeMoney)` — the enum and `Money` both allow it) — a build decision the design silently skips.

**7. Fabricated data not to port:** `VYR-10428` (real: `'R-'.Str::random(8)`, `AssignmentService:648`) · `RFND-{Date.now().slice(-6)}` (real: `payments.gateway_ref` = `manual_ref_<ulid>` or Stripe `re_…`) · `Visa ····4242` (**no brand/last4 column exists anywhere** — repo-wide grep returns only `properties.brand`) · `2–5 business days` / `1–2 business days` (no settlement-ETA data; `StripeRefund` carries only `{id, status}`) · `Amara Okafor` / `amara.okafor@mail.com` · `Villa Azure · 12–15 Sep` · `localStorage['vyrobel:refund:last']`.

**8. Brief correction — there are no toasts.** The 2 `data-toast` occurrences are lines 191/193 of the shared `__vyToast` helper in `<helmet>` (`closest('[data-toast]')` / `getAttribute('data-toast')`). **No element on this page carries a `data-toast` attribute** — the page fires zero toasts. Use the existing PRG flash (`->with('folio_notice', …)`) instead of building a toast layer.

## BUILD-READINESS VERDICT

**~45–50% ✅ build-now; ~35% 🔨 needs backend; ~15% 📋 decorative; 1 hard decision + 1 ship-blocker.**

**The good news — and it is genuinely good.** TIER1-SPEC's "READY" verdict is *substantially* correct where it matters most: **`FolioService::refundPayment` really does take an arbitrary `Money` amount**, is append-only, correct-by-reversal, tenant-scoped, audit- and domain-event-emitting, and is proven partial by two existing tests (`FolioLedgerTest:23`, `FolioPdfTest:73` — "partial refund → its own line"). The transport exists too (`POST /app/reservations/{reservation}/refunds` → `ReservationFolioActionController::refund`, PRG, cross-org 404). The core value of this screen — **unblocking partial refunds, the "Soon" item on `reservation-detail.blade.php:116`** — is a *small* delta over a certified backend, not a build. The reason note even has a home already (the shared polymorphic `notes` table, morph-mapped to `folio`/`reservation`, unused but real — reuse it, don't invent `refund_notes`).

**Recommended approach: BUILD NOW, but ship it as a deliberately narrower screen than the design draws.**
- **Wave-2a (build):** the route (`can:payments.refund`-gated) + Full/Partial modes + the % chips + reason chips + internal note → `notes` + the confirm modal + the success state via PRG flash. Reuse `<x-operator.shell>`, `FormatsOperatorData`, `FolioTotals`, and the `ReservationDetail` scoping pattern verbatim.
- **⛔ SHIP-BLOCKER — do not merge without it:** the **cumulative over-refund guard** in `FolioService::refundPayment`. The service today checks only against the single original payment amount, so N partial refunds can exceed it and drive `paid_minor` negative. That gap is unreachable today *only* because the sole UI does full refunds — this screen makes it reachable on day one, and the design's own "Max refundable" copy promises a cap that does not exist. It is the one backend change this screen genuinely requires (S). Ship it with a test.
- **Also required (S each):** an `amount` input on the controller; a payment picker (or `refundFolio()`) — `refundPayment` needs ONE `Payment` and the design only ever shows an aggregate; a `?string $reason` param (metadata json is the cheap home).

**Cut from v1 (honesty rule):** the destination picker (architecturally not expressible — one container-bound gateway; and no guest IBAN store), "Visa ····4242" (no brand/last4 column exists), the ETAs (no data), the notify-guest toggle (no guest Mailable, and no prod mail provider), the receipt PDF (separate design page), and by-charge mode (needs a `folio_items`↔`payments` link that has never existed).

**⛔ Two things need a DECISION before, not during, the build:**
1. **"The owner statement adjusts on the next close" is false** — `OwnerAttributionService` has zero production callers, and attribution reads `folio_items`, which a refund never touches. Delete the sentence or fund the subsystem (L). Do not ship it.
2. **Guest email on an operator surface** contradicts the written precedent (`ReservationDetail`: *"name only; encrypted email/phone never rendered"*). TIER1-SPEC defers to a "policy" that isn't written down. Default to name-only.

**One product wart this screen will expose:** `payment_status = 'refunded'` is a dead enum value — after a full refund `paid = 0` → the reservation reads **`'unpaid'`**. And because `folio_items` are never reversed, the refund *raises* the balance: the guest "owes" again. Both are correct per the ledger and both will read as bugs to an operator. Worth a decision alongside the build.

---

# 5. GUESTS list / CRM (operator)

**Design file:** `docs/design/raw/Operator Guests.html` — 1,553,225 B · template 54,082 · 14 onClick · 6 sc-for

**What it is.** A single-table guest CRM on the operator shell: a paused-workspace banner, a top bar, a control row (search + 4 filter chips + Select + Add guest), a 3-tile stat strip, a 7-column guest table with 8 hard-coded rows, a footer with pagination, plus a select-mode bulk-action bar and two modals (bulk message, delete confirm) and a toast. Real interactivity is narrower than it looks: of 14 onClick handlers, every one drives only local component state — filter, select-mode, selection, modal open/close, and toasts. The ONLY handler that produces a real artifact is bulk Export, which builds a client-side CSV Blob. Search, Add guest, and both pagination arrows have NO handler at all — they are markup. Both modals' primary buttons (Send / Delete) fire a toast and nothing else.

## Feature inventory — every element

> **Verification note.** Every ✅ below was verified by reading the named file. Counts I could reproduce from the decoded template: **14 `sc-camel-on-click`** (matches the brief) and **8 `sc-if`**. I count **3 `sc-for`** (`filters`, `guests`, `actions` — lines 330, 381, 432), **not 6**. The brief's 6 may count the three `dc-import` sub-components (Property Switcher / Notif Bell / Operator Account Menu) or `sc-for`s inside the raw bundle. I did not verify the raw SPA's count — flagging rather than inventing.

> **Headline finding.** The design surfaces *less* than the backend already has. `guests.risk_flag`, the polymorphic `notes`, and the `tags`/`taggables` catalogue are all REAL and already wired to `Guest` via `HasNotes`/`HasTags` — and **none of them are rendered on this page** (the only tag touchpoint is a bulk button that just toasts). TIER1-SPEC's "over `Guest` (+ VIP/risk/tags/notes)" over-describes this specific design. Conversely, almost every *data cell* in the table is a derivation that does not exist yet.

### Shell — sidebar (`aside`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Vyrobel logo mark "V" + wordmark | ✅ BUILD-NOW | `resources/views/components/operator/shell.blade.php` | Shell already renders chrome; do not re-implement. |
| Property Switcher (`<dc-import name="Property Switcher">`) | 🔨 NEEDS-BACKEND (S) | Needs a session-persisted **active property** + a scope helper. `App\Models\Property` is real, but I found **no active-property concept**; the shell only accepts a `subtitle` string (shell.blade line 7-8, "e.g. the active property name"). | Shell-wide concern, not Guests-specific. Opaque sub-component — I could not read its internals. |
| Nav group heading "Overview" | ✅ BUILD-NOW | shell.blade `$navGroups` | |
| Nav → Dashboard | ✅ BUILD-NOW | `route('app.dashboard')` → `App\Livewire\Operator\OperatorDashboard` | |
| Nav → Calendar | ✅ BUILD-NOW | `route('app.rack')` → `App\Livewire\ReservationRack` | Shell maps design's "Calendar" → rack. |
| Nav → Insights | ⬜ OTHER-MODULE | No route, no aggregation layer. Tier-2 Analytics. | Shell's nav-honesty rule renders unbuilt items as `aria-disabled` "Coming soon". |
| Nav group heading "Front desk" | ✅ BUILD-NOW | shell.blade | |
| Nav → Reservations | ⬜ OTHER-MODULE | No route yet — it is **Wave 2 item 7**, a sibling page in this same wave. | Will become ✅ once that screen lands. |
| Nav → Arrivals & departures | ✅ BUILD-NOW | `route('app.front-desk')` → `App\Livewire\FrontDesk` | |
| Nav → Guests (active/highlighted state) | 🔨 NEEDS-BACKEND (S) | Needs a new `['guests', 'Guests', …, route('app.guests')]` entry in shell.blade `$navGroups` + the new route. | The shell has **no Guests nav item** today. |
| Nav → Messages | ✅ BUILD-NOW | `route('app.inbox')` → `App\Livewire\InboxList` | |
| Messages badge "**2**" | 🔨 NEEDS-BACKEND (S) | Hard-coded literal `2` in markup (line 244). Derivable: `message_threads.unread_count` is a REAL column (`2026_07_17_000000_create_message_threads_table.php`) — sum/count tenant-scoped. Shell renders no badge today. | Don't port the constant. |
| Nav group heading "Operations" | ✅ BUILD-NOW | shell.blade | |
| Nav → Housekeeping | ✅ BUILD-NOW | `route('app.housekeeping')` → `App\Livewire\HousekeepingBoard` | |
| Nav → Maintenance | ✅ BUILD-NOW | `route('app.maintenance')` → `App\Livewire\MaintenanceList` | |
| Maintenance badge "**2**" | 🔨 NEEDS-BACKEND (S) | Hard-coded literal `2` (line 257). Derivable: count of open `maintenance_tickets` (table real, module certified). | |
| Nav → Rates & availability | ⬜ OTHER-MODULE | No route — **Wave 4 item 11**. | |
| Nav → Channels | ⬜ OTHER-MODULE | No route — **Wave 4 item 11**; live Channex sync is a gated checkpoint. | |
| Nav group heading "Finance" | ✅ BUILD-NOW | shell.blade | |
| Nav → Owners | ✅ BUILD-NOW | `route('app.owners')` → `App\Livewire\Operator\OperatorOwners` | |
| Nav → Reports | ⬜ OTHER-MODULE | No route, no reporting module. | |
| Nav group heading "System" | ✅ BUILD-NOW | shell.blade | |
| Nav → Settings | ⬜ OTHER-MODULE | No route — **Wave 4 item 16**. | |
| Nav → Security | ⬜ OTHER-MODULE | No route — **Wave 5** (audit-log view; `audit_events` table is real but has no UI). | |

### Paused / read-only banner

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "**Workspace paused — read-only until 5 Oct**" banner | 📋 DECORATIVE-FAKE | **Established precedent: OMIT.** `App\Livewire\Operator\OperatorOwnerDetail.php:101-102` rejected this exact banner — *"subscription/billing has no backend; porting it would fabricate a system status."* | `subscriptions.status` does have a `'paused'` value (`2026_07_02_000400_create_subscriptions_tables.php`), but **nothing sets, reads, or enforces it** — no billing service, no read-only middleware. The date "5 Oct" is invented. |
| Pause icon (two bars) | 📋 DECORATIVE-FAKE | Part of the omitted banner. | |
| Sub-line "Viewing is fine, changes are off · channels stopped selling · … while paused" | 📋 DECORATIVE-FAKE | Fabricated system status. No read-only enforcement exists anywhere in the codebase. | |
| `<span data-vyc-zero=""></span>` inside the sub-line | 📋 DECORATIVE-FAKE | An **empty** span — a stripped-out demo currency/number token from the design tool. Renders nothing. | Honest flag: I cannot tell what figure it held. Do not guess. |
| "**Resume now**" button → `Operator Billing.dc.html` | ⬜ OTHER-MODULE | Billing/subscription module is unbuilt (no route, no Livewire, no service). | |

### Top bar

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| `<h1>` "Guests" | ✅ BUILD-NOW | Static; shell accepts a `title` slot. | |
| Notif Bell (`<dc-import name="Notif Bell">`) | 📋 DECORATIVE-FAKE | Shell already renders `<span class="vy-bell" title="Notifications — coming soon">` — an honest non-control. No notifications backend. | Reuse the shell's existing treatment; add nothing. |
| Operator Account Menu (`<dc-import>`) | ✅ BUILD-NOW | Shell `$navGroups` ACCOUNT group; real session auth + CSRF `route('logout')` POST. | Opaque sub-component; the shell's version is the built one. |

### Control row

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Search field "Search by name, email or phone…" | 📋 DECORATIVE-FAKE **as drawn** | It is a **`<div>`, not an `<input>`** (line 324-327) — no `sc-camel-on-input`, no handler, no state. Purely a placeholder graphic. | See the two rows below for the real feature split. |
| → Search by **name** | 🔨 NEEDS-BACKEND (S) | `guests.first_name` / `guests.last_name` are plaintext `string` columns (`2026_07_02_001300_create_guests_table.php`). A `whereLike` scope + a wired input. | Genuinely buildable. |
| → Search by **email / phone** | 🔨 NEEDS-BACKEND (L) — **known deferral** | **IMPOSSIBLE via SQL today.** `email`/`phone` are `'encrypted'` casts over `text` columns. The migration docblock is explicit: *"ENCRYPTED ciphertext … TEXT columns, **never indexed by value**"*. `GuestMatcher`'s docblock repeats it: *"encrypted at rest precisely so contact PII is never queryable in the clear."* Needs a **blind-index** column (e.g. `email_hash` HMAC + index) — a standing Phase-1 deferral (`docs/PROJECT-STATE.md:490-491`) and the same gate TIER1-SPEC puts on Global Search (Wave 5 item 17). | **Do not ship a search placeholder that promises email/phone.** Either relabel to "Search by name" or gate the whole field. |
| Filter chip **All** | ✅ BUILD-NOW | Real onClick `f.pick` → `setState({filter:'all'})`. Backing = unfiltered tenant-scoped `Guest::query()` (`BelongsToOrganization`). | |
| Filter chip **In-house** | 🔨 NEEDS-BACKEND (S) | Filters on a fabricated `g.kind` field. Needs a derivation: guests with a `reservations.status = 'checked_in'` row where `check_in <= today < check_out` — the logic already exists in `FrontDeskService::inHouse()` but it is **property-scoped and returns Reservations, not Guests**. | See "Status" column — same derivation. |
| Filter chip **Upcoming** | 🔨 NEEDS-BACKEND (S) | Needs: guests with a future `confirmed`/`tentative` reservation. No such scope. | |
| Filter chip **VIP** | ✅ BUILD-NOW | **`guests.is_vip`** — a real `boolean` column with `default(false)`, in `$fillable`, cast `'boolean'` (`app/Models/Guest.php:38,61`). A `where('is_vip', true)` is genuine. | The one filter with true backing. |
| Filter chip active/inactive styling (`bg`/`shadow`/`color`) | ✅ BUILD-NOW | Pure presentation derived from `this.state.filter === f.id`. | |
| **Select / Done** toggle button | ✅ BUILD-NOW | Real onClick `toggleSelect` → `setState({selectMode: !s.selectMode, sel: {}})`. Pure client UI state; needs no backend. | Label + background swap on state. |
| **Add guest** button | 📋 DECORATIVE-FAKE **as drawn** | **NO onClick** (line 338-341). Inert markup — clicking does nothing. | |
| → the *feature* "create a guest" | 🔨 NEEDS-BACKEND (S) | `Guest::create()` works (model `$fillable` covers first_name/last_name/email/phone/nationality/is_vip/risk_flag/…), but there is **no create form, no controller, no service, and no audit/domain event** for it. Per convention, needs POST → controller → (service) → PRG + an `audit_events` + `domain_events` write. | The design gives **no form** — field set is undefined. Don't guess it here. |

### Stat strip (3 tiles)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Tile 1 label "GUEST PROFILES" | ✅ BUILD-NOW | Static label. | |
| Tile 1 value "**248**" | 📋 constant → 🔨 (S) | Hard-coded `248` in markup (line 349). Derivable: `Guest::count()` (auto tenant-scoped). | **Decision needed:** exclude `anonymized_at IS NOT NULL` rows? An anonymized guest is a row with no PII — counting it as a "profile" is arguably wrong. Also `SoftDeletes` is on `Guest` — the default scope already excludes trashed. |
| Tile 2 label "IN-HOUSE TONIGHT" | ✅ BUILD-NOW | Static label. | |
| Tile 2 value "**38**" | 📋 constant → 🔨 (S) | Hard-coded `38` (line 353). `FrontDeskService::inHouse(string $propertyId, $date)` exists and is correct, **but**: (a) it is **per-property**, this page has no resolved property; (b) it returns **Reservations**, not distinct guests. | **Aggregate-scoping trap.** "38" against 8 visible rows reconciles to nothing. Decide: guests or reservations? one property or org-wide? |
| Tile 3 label "REPEAT GUESTS" | ✅ BUILD-NOW | Static label. | |
| Tile 3 value "**31%**" | 📋 constant → 🔨 (S) | Hard-coded `31%` (line 358). Derivable: guests with ≥2 reservations ÷ total guests. Needs the `Guest::reservations()` relation (does not exist — see below). | Denominator must be defined (all guests? non-anonymized? guests with ≥1 stay?). |
| Tile 3 delta "**+4%**" | 📋 DECORATIVE-FAKE | Hard-coded `+4%` (line 359). **Classic Wave-1 fabricated-history trap.** There is **no prior-period snapshot anywhere** — no historical guest/repeat metric table, no time-series. A delta cannot be computed without inventing a baseline. | **Do not port.** Same family as Wave 1's `×0.9/×0.8 + jitter` invented prior-year. |

### Guests table — header row

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Select-all checkbox (select-mode only) | ✅ BUILD-NOW | Real onClick `toggleAll`. Pure client state over the filtered set. | |
| Select-all **tri-state** rendering | ✅ BUILD-NOW | `allOn` → check path `M4.5 12.5 9.5 17.5 19.5 7`; `someOn` → **dash** path `M6 12h12`; none → opacity 0. Presentation only. | Nicely done in the design; keep. |
| Column header "Guest" | ✅ BUILD-NOW | `guests.first_name` + `last_name`. | |
| Column header "Country" | 🔨 NEEDS-BACKEND (S) | `guests.nationality` is **`char(2)`** (ISO-3166 alpha-2). Design renders full names ("Ireland", "Denmark"). Needs an ISO2→name map (a `FormatsOperatorData`-style helper). | ⚠️ **Semantic mismatch:** the column is *nationality*, not *country of residence*. Label it "Nationality" or accept the conflation knowingly. |
| Column header "Stays" (right-aligned) | 🔨 NEEDS-BACKEND (S) | No count exists. Needs `Guest::reservations()` (**absent** — `app/Models/Guest.php` declares only `guestDocuments()`) + a `withCount`. | ⚠️ **Definition ambiguity:** via `reservations.lead_guest_id` (nullable FK) or via the `reservation_guests` pivot (a guest can be a non-lead occupant)? These give different numbers. Must be decided and stated. Also: exclude `cancelled`/`no_show`? A "stay" that was cancelled is not a stay. |
| Column header "Last stay" | 🔨 NEEDS-BACKEND (S) | Needs `MAX(check_out)` over the guest's non-cancelled reservations. | ⚠️ **The design breaks its own column:** values include `'In house'` and `'Arrives today'` (a *future* stay) — which are not *last* stays. See the row cell below. |
| Column header "Lifetime" (right-aligned) | 🔨 NEEDS-BACKEND (S–M) | No derivation exists. TIER1-SPEC explicitly flags this: *"Guest Profile (MOSTLY-READY — **add the lifetime-spend derivation**)"*. | ⚠️ **Source ambiguous** — see the Traps section. Candidates: `Σ folios.total_minor` (charged) vs `Σ payments.amount_minor where type='payment' AND status='succeeded'` (collected) vs `Σ reservations.total_minor`. **Multi-currency:** `folios.currency` is per-folio `char(3)`; the design shows one flat `€` figure. Summing across currencies is unsound (money convention: integer minor + ISO currency, never floats/implicit FX). |
| Column header "Status" (right-aligned) | 🔨 NEEDS-BACKEND (S) | Derived guest-level state — see the status vocabulary in Traps. | |
| Column header (8th, empty — chevron column) | ✅ BUILD-NOW | Spacer. | |
| **Sorting** | 📋 ABSENT — flag | **There is NO sort control anywhere in this design.** Headers are plain `<span>`s with no handler, no sort indicator, no direction state. | Explicit answer to "every sort": none exist. If we want sorting, it is net-new UI + a `orderBy` whitelist (S) — and note *Lifetime/Stays/Last stay are derived*, so sorting them means sorting on a subquery/aggregate, not a column. |
| `rowCols` grid template | ✅ BUILD-NOW | `'2fr .9fr .5fr .9fr .8fr .9fr 20px'`, prefixed with `'28px '` in select-mode. 7/8 tracks match 7/8 cells — internally consistent. | |

### Guests table — rows (`sc-for` over 8 hard-coded guests)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| The 8 guest rows themselves | 📋 DECORATIVE-FAKE | Entirely fabricated array (lines 544-551): Amara Okafor, The Hansen family, Sofia Lindqvist, Rutger van Dijk, Jia Chen, Kenji Tanaka, Henrik Dahl, Elena Costa — with invented emails (`amara.okafor@mail.com`, `j.hansen@mail.dk`, …), countries, and spends via `VYC.fmt(3940)` etc. | **Do not port any of it.** `DemoSeeder` seeds guests already; it does **not** seed `is_vip`, `risk_flag`, tags, or notes (verified — zero matches) — so a demo of this page needs a small seeder addition (not a backend gap). |
| Row: avatar circle with initials | ✅ BUILD-NOW | **`FormatsOperatorData::initials(string $name)`** already exists (`app/Livewire/Operator/Concerns/FormatsOperatorData.php:32`). Reuse — do not write a new one. | Gradient fill is presentation. |
| Row: guest **name** | ✅ BUILD-NOW | `guests.first_name` + `last_name` (plaintext, nullable). | **PII OK** — operator surfaces may show guest names (front-desk precedent; `FrontDeskService` eager-loads `leadGuest`). |
| Row: **VIP** pill (conditional) | ✅ BUILD-NOW | **`guests.is_vip`** boolean column + `'boolean'` cast. Genuinely real. | Only 1 of 8 demo rows has it (`The Hansen family`). |
| Row: **email** under the name | 🔨 / ⚠️ PII decision (S) | *Technically* renderable — the `'encrypted'` cast decrypts on attribute access. **But** `Guest::$hidden = ['email','phone']` (`Guest.php:47-50`) blocks it from array/JSON serialization, so any Livewire-serialized/`toArray()` path silently drops it; you must access it explicitly. | ⚠️ **This needs an explicit decision, not a default.** The front-desk precedent covers *names*, not contact PII in a bulk-browsable list. Rendering 248 guests' emails on one screen is a materially different exposure from one reservation's guest. And it is **not searchable/sortable/filterable** regardless (encrypted). Also: an anonymized guest has `email = null` — the row must degrade gracefully. |
| Row: **country** cell | 🔨 NEEDS-BACKEND (S) | As per the Country header — ISO2→name map over `guests.nationality`. | |
| Row: **stays** cell | 🔨 NEEDS-BACKEND (S) | As per header. Demo values `'3','2','1','4','1','2','3','1'` are strings, fabricated. | |
| Row: **last stay** cell | 🔨 NEEDS-BACKEND (S) | Demo values mix **three incompatible formats**: `'In house'`, `'Arrives today'`, `'20 Sep 2025'`, `'Aug 2025'` (month-only!). | ⚠️ The column conflates *last stay date*, *current status*, and *next arrival*. `'Aug 2025'` vs `'20 Sep 2025'` is an inconsistent precision the design never resolves. Needs a real format rule. Dates must render in the **property timezone** per the time convention. |
| Row: **lifetime** cell | 🔨 NEEDS-BACKEND (S–M) | `VYC.fmt(3940)` etc. — a demo currency helper with fallback string `'€3,940'`. Real formatting exists: **`FormatsOperatorData::money(int $minor, string $currency)`** (line 47) — reuse it. | ⚠️ **Jia Chen: `stays: '1'` but `spend: VYC.fmt(0)` = €0** — a guest with a stay and zero lifetime value. Incoherent demo data that reveals the source is undefined (charged? collected?). |
| Row: **status** pill + its `pillBg`/`pillColor` | 🔨 NEEDS-BACKEND (S) | Three fabricated constants: `inhouse = {status:'In house', pillBg:'rgba(217,102,46,.13)', pillColor:'#B4400F'}`, `upcoming = {status:'Upcoming', …}`, `past = {status:'Past', …}` (lines 540-542). | See Traps — these are **derived guest-level states, not `reservations.status` enum values**. |
| Row: chevron `›` | ✅ BUILD-NOW | Presentation affordance. | |
| Row: **click → guest profile** | 🔨 NEEDS-BACKEND (S) | `g.onRow` (non-select mode) → `window.location.href = g.href` **only if `g.href !== '#'`**. **Only row 1** has a real href (`'Operator Guest Profile.dc.html'`); **rows 2-8 are `'#'` → clicking does literally nothing.** | The target **Operator Guest Profile** is the sibling Wave-2 page (also unbuilt). Needs `route('app.guests.show', $guest)`. |
| Row: **click → toggle selection** (select mode) | ✅ BUILD-NOW | `setState(s => ({sel: {...s.sel, [g.email]: !s.sel[g.email]}}))`. Pure client state. | ⚠️ **Keyed by `g.email`** — see Traps. Must be the guest **ULID**. |
| Row: checkbox (select mode) | ✅ BUILD-NOW | Presentation of `sel[g.email]`; `cbBg`/`cbBorder`/`checkOp` derived. | |
| Row: selected background tint (`rowBg`) | ✅ BUILD-NOW | `on ? 'rgba(217,102,46,.1)' : 'transparent'`. | |
| Row: hover lift (`translateY(-1px)` + bg) | ✅ BUILD-NOW | CSS only. | |
| **Empty state** | 📋 ABSENT — flag | **There is NO empty state.** Filter to `VIP` (1 of 8 demo rows) and the table just shrinks; filter to something with 0 matches and you get a bare header + footer. | We must design one. Also **no loading state** and **no error state** anywhere on this page. |

### Table footer

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "**Showing 8 of 248 guests**" | 📋 DECORATIVE-FAKE | **Static markup** (line 411) — not bound to anything. It says "8 of 248" **even when a filter reduces the table to 1 row**. Neither number reconciles to anything. | Real version: `$guests->count()` of `$total` from a paginator. Trivially real once the query exists. |
| Pagination **prev** arrow | 📋 DECORATIVE-FAKE | **NO onClick** (line 413-415). Inert markup. Styled as disabled-ish (`color:#A69A92`, no hover). | |
| Pagination **next** arrow | 📋 DECORATIVE-FAKE | **NO onClick** (line 416-418). Inert markup (has a hover style, so it *looks* live — it isn't). | |
| → the *feature* pagination | 🔨 NEEDS-BACKEND (S) | Standard Laravel `paginate()`. No page state, no page-size, no page number in the design — only two arrows. | The design gives no page indicator; that's a gap to resolve. |

### Bulk-action bar (`sc-if hasSel` — appears when ≥1 selected)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| The floating bar itself | ✅ BUILD-NOW | Pure client state (`selCount > 0`). Fixed, `left: calc(50% + 100px)` (offset for the 230px sidebar — brittle but presentational). | |
| `{{ selCount }}` + "selected" | ✅ BUILD-NOW | `filtered.filter(g => sel[g.email]).length`. | |
| "**Clear**" button | ✅ BUILD-NOW | Real onClick `clearSel` → `setState({sel: {}})`. | |
| Bulk **Message** button | 📋 → 🔨 (M) | Real onClick, but it only **opens a modal** (`setState({msgOpen:true})`). See modal below for the real story. | |
| Bulk **Add tag** button | 📋 DECORATIVE-FAKE **as drawn** | onClick → `this.fireToast('Tagged N guests')` and **nothing else**. No tag picker, no selection of *which* tag, no write. The constant is `bulkMsg.tag = 'Tagged ' + selCount + …` (line 586). | |
| → the *feature* "tag guests" | 🔨 NEEDS-BACKEND (S) | **The data layer is REAL and already reusable — do not build a parallel structure.** `tags` (per-tenant catalogue, `unique(organization_id, name)`) + `taggables` polymorphic pivot (`2026_07_02_001130_create_tags_tables.php`); `App\Models\Tag`; and **`Guest` already uses the `HasTags` trait** (`Guest.php:27` → `morphToMany(Tag::class, 'taggable')`). What's missing: a **tag-picker UI** (the design has none — which tag?) + a guarded service method (`attach`/`detach` + `audit_events`/`domain_events` per the events convention). | Genuinely small. The design under-specifies it, not the backend. |
| Bulk **Export** button | 📋 DECORATIVE-FAKE (design-level real, backend-deferred) | **The only handler that makes a real artifact.** `doExport()` builds a client-side CSV Blob + `URL.createObjectURL` download, filename `'Vyrobel-guests-' + rows.length + '.csv'`, then toasts. Columns: `Name, Email, Country, Stays, Last stay, Lifetime value, Status`. Quotes via `'"' + String(s).replace(/"/g,'""') + '"'`, CRLF joined. | ⚠️ **Two problems.** (1) **CSV export is a standing deferral** (`docs/TIER1-SPEC.md:610-611`: *"Export buttons recur across nearly every list — CSV export is a standing deferral"*; `PROJECT-STATE.md:430`). PDF export exists for statement/folio only (`PdfService` + `barryvdh/laravel-dompdf`). (2) It **exports guest EMAIL** — a bulk PII egress with no consent check (`guests.marketing_consent` exists and is ignored), no audit event, no tenant re-scope. Do not ship this as-is. |
| Bulk **Delete** button | 📋 → ⚠️ see modal | Real onClick → `setState({confirmDelete:true})`. | Destructive; see the confirm modal — this is the page's biggest correctness problem. |
| Bulk-bar hover states (`a.hover`) | ✅ BUILD-NOW | Presentation. | |
| Delete button's distinct danger styling | ✅ BUILD-NOW | `bg:'rgba(143,49,16,.5)'`, `co:'#F6C6B4'`. | |
| `vyBar` slide-up animation | 📋 DECORATIVE-FAKE | Keyframe. | |

### Bulk-message modal (`sc-if msgOpen`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Overlay + click-outside-to-close | ✅ BUILD-NOW | onClick `closeMsg`; inner `stopClick` → `e.stopPropagation()`. | |
| Modal icon (chat bubble) | ✅ BUILD-NOW | Presentation. | |
| Title "**Message {{ delCountLabel }}**" | ✅ BUILD-NOW | `delCountLabel = selCount + (selCount===1 ? ' guest' : ' guests')`. | |
| Subtitle "**Sent by email · replies land in your inbox**" | 📋 DECORATIVE-FAKE — **actively misleading** | ⚠️ **`EmailChannel` CANNOT SEND.** It extends `DeferredChannel`, whose `send()` **throws** `RuntimeException` (`app/Messaging/Channels/DeferredChannel.php`). Its stated requirement: *"an inbound-email provider (e.g. Postmark/Mailgun inbound parse) plus a verified sending domain"* — an outstanding account-gated deferral (inbox invariants #23-25). | This string promises the one thing the system provably cannot do. **Must not be ported verbatim.** |
| Textarea (`msgText` / `onMsgText`) | ✅ BUILD-NOW | Real two-way binding: `value={{msgText}}`, `sc-camel-on-input` → `setState({msgText: e.target.value})`. Client state only. | |
| "**Send to {{ delCountLabel }}**" button | 📋 DECORATIVE-FAKE | onClick `sendMsg` → `fireToast('Message sent to N guests')` + `setState({msgOpen:false, msgText:''})`. **No network call, no persistence.** A pure mock. | |
| "Cancel" button | ✅ BUILD-NOW | onClick `closeMsg`. | |
| → the *feature* "bulk message guests" | 🔨 NEEDS-BACKEND (M) | **Reuse `InboxService` — do not build a parallel messaging path.** `InboxService::startThread(array $data)` + `sendOutbound(MessageThread, string $body, ?User)` are real and certified; `ManualChannel` genuinely works (records + delivers, `app/Messaging/Channels/ManualChannel.php`). So a **manual-channel** bulk note is buildable (N threads, one per guest — needs a loop + a bulk method + `participant_identity` routing). **Email is not.** | ⚠️ Also unresolved by the design: no subject field, no per-guest personalisation, no rate limiting, no `marketing_consent` check, and N threads from one textarea is a real UX/design decision. |
| `vyToastIn` overlay animation | 📋 DECORATIVE-FAKE | Keyframe. | |

### Delete-guests confirm modal (`sc-if confirmDelete`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Overlay + click-outside-to-close | ✅ BUILD-NOW | onClick `closeConfirm`; inner `stopClick`. | |
| Trash icon | ✅ BUILD-NOW | Presentation. | |
| Title "**Delete {{ delCountLabel }}?**" / "This can't be undone" | ⚠️ **📋 — WRONG MODEL** | See below. | |
| Body copy: "*The selected guest profiles and their history will be permanently removed. Reservations stay, but are no longer linked to a profile.*" | ⚠️ **📋 DECORATIVE-FAKE — CONTRADICTS INVARIANT #5** | **This is the page's most serious defect.** The PII convention is unambiguous: *"GDPR erase = `anonymize()` (strip PII + set `anonymized_at`) — **NEVER soft-delete-and-keep PII**"*, and `Guest`'s docblock: *"anonymize() … nulls PII and sets `anonymized_at` **WITHOUT deleting the row**, so reservation/financial history … stays intact."* The design proposes the exact opposite: destroy the profile + its history. It is also **factually wrong about the DB**: `reservations.lead_guest_id` is `nullOnDelete` (✓ reservations survive) but **`reservation_guests.guest_id` is `cascadeOnDelete`** — a hard delete **would** destroy those pivot rows. And `Guest` uses `SoftDeletes`, which would **retain the PII** — explicitly forbidden. | |
| "**Delete {{ delCountLabel }}**" button | 📋 DECORATIVE-FAKE | onClick `confirmDeleteRun` → `fireToast(selCount + ' guests removed')` + `setState({confirmDelete:false, sel:{}})`. **No write of any kind.** | |
| "Keep them" button | ✅ BUILD-NOW | onClick `closeConfirm`. | |
| → the *correct* feature: **anonymize guests** | ✅ BUILD-NOW (with rewritten copy) | **`Guest::anonymize()` is real, certified, and does exactly the right thing** (`app/Models/Guest.php:87-142`): nulls first_name/last_name/email/phone/nationality/dob/language/external_refs, **hard-deletes ID documents including the stored file bytes** via `Storage::disk()->delete()`, redacts the guest's inbox threads/messages (GDPR), stamps `anonymized_at`, and writes both `audit_events` (`guest.anonymized`) and `domain_events`. | **Build the action, rewrite the design's words.** Needs: a POST → controller → `anonymize()` → PRG (operator-mutation pattern), tenant-scoped resolution, and a bulk loop. The modal must say *anonymise/erase PII*, not *delete profile + history*. Flag for a product decision — this is a copy change the design's author did not intend. |

### Toast

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Toast container (`sc-if toast`) | ✅ BUILD-NOW | Presentation; `fireToast(msg)` + `setTimeout(… , 3200)` auto-dismiss. | Every toast on this page is a **mock** — a design-level confirmation with no backend behind it. |
| Toast check icon | ✅ BUILD-NOW | Presentation. | |
| Toast strings | 📋 DECORATIVE-FAKE | `bulkMsg` constants (lines 584-589): `'Message sent to N guests'`, `'Tagged N guests'`, `'Exported N guests'`, `'N guests removed'`; plus `doExport`'s `'Exported N guests to CSV'`. | Note the redundancy: `bulkMsg.export` is **dead code** — export routes to `doExport()` which fires its own toast. |
| Toast always shows a **success** icon | 📋 DECORATIVE-FAKE | There is **no error/failure toast variant**. Every real write can fail (guarded transitions, deferred channels). | The Inbox precedent renders honest FAILED delivery states; this page has no vocabulary for failure. |

### Background, motion, persistence

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Peach/sand gradient background | 📋 DECORATIVE-FAKE | `linear-gradient(135deg, #F6C89B 0%, #F1B896 32%, #EBD9CF 62%, #D9D6D2 100%)`. | Operator design system already established. |
| 2 ambient light streaks | 📋 DECORATIVE-FAKE | Radial/linear gradients + blur; one animates `vyShimmer 9s`. | |
| 3 floating colour orbs | 📋 DECORATIVE-FAKE | `#E8703A` (animated `vyDrift 14s`, gated on `motion`), `#F0A57A`, `#FBE3CE`. | |
| `motion` prop | 📋 DECORATIVE-FAKE | `data-props`: `{"motion": {"editor":"boolean","default":true,"section":"Motion"}}`. | Design-tool prop; gates **only the first orb** — the other two and all keyframes ignore it. Not a user setting. |
| Keyframes `vyFadeUp` / `vyShimmer` / `vyDrift` / `vyBar` / `vyToastIn` | 📋 DECORATIVE-FAKE | Staggered entrance delays (.03s → .36s). | |
| Inter + Sora webfonts | ✅ BUILD-NOW | Already in the operator design system. | |
| **localStorage selection persistence** | 📋 DECORATIVE-FAKE — **reject** | `componentDidMount` reads `localStorage.getItem('vyrobel_guests_sel')`; `persistNow()` writes `{sel, selectMode}` on every render. | ⚠️ **Wave-1 precedent says drop it:** `OperatorOwnerDetail.php:108` explicitly rejected *"its localStorage refund netting"*. Persisting a **destructive selection** across page loads is actively dangerous (reload → the Delete bar reappears pre-populated). And it is keyed by **email** — see Traps. |

## WRITES

**Every write-action on this page, and its real path:**

1. **Bulk "Delete" guests** (`confirmDeleteRun`) — the design does NOTHING (fires a toast, clears selection). The *correct* action is **NOT delete**: use the existing, certified **`App\Models\Guest::anonymize()`** (`app/Models/Guest.php:87`) — it nulls all PII, hard-purges ID document files + rows, redacts inbox threads/messages, stamps `anonymized_at`, and writes `audit_events` + `domain_events`. **NEEDS: a controller + route (`POST /app/guests/{guest}/anonymize` or a bulk variant) following the operator-mutation pattern (POST → controller → model/service → PRG, CSRF, tenant-scoped `findOrFail` → cross-org 404), per the FrontDesk/Maintenance/Folio precedents.** The design's copy must be rewritten — it currently describes an operation the PII invariant forbids.

2. **Bulk "Add tag"** (`a.run` for key `'tag'`) — the design only fires `fireToast('Tagged N guests')`. Data layer is REAL and reusable: `tags` + `taggables` tables, `App\Models\Tag`, and **`Guest` already uses `HasTags`** → `$guest->tags()` is a working `morphToMany`. **NEEDS: `TagService::attachTo(Model $subject, string $tagId)` (or equivalent) with `audit_events`/`domain_events` writes per the events convention — no such service exists (no `TagService` in `app/Services/`) — plus a tag-picker UI (the design has none; it never says WHICH tag).**

3. **Bulk "Message"** (`sendMsg`) — the design only fires `fireToast('Message sent to N guests')`. **Reuse `App\Services\InboxService` — it is the single inbox write path.** Real methods exist: **`InboxService::startThread(array $data)`** and **`InboxService::sendOutbound(MessageThread $thread, string $body, ?User $author = null)`**. Over **`ManualChannel`** this genuinely works. Over **email it CANNOT** — `EmailChannel extends DeferredChannel`, whose `send()` **throws** `RuntimeException("The email channel is not configured — deferred skeleton…")`. **NEEDS: a bulk-send method (`InboxService` has no bulk API — it is one-thread-at-a-time) + a route/controller.** The modal's "Sent by email" subtitle is unbuildable today.

4. **"Add guest"** — the design's button has **NO onClick**; it is inert markup. `Guest::create()` works and `$fillable` covers the fields, but **NEEDS: a create form (the design provides none — the field set is undefined), a route + controller, and `audit_events`/`domain_events` writes.**

5. **Bulk "Export" CSV** (`doExport`) — the design does a real *client-side* Blob download. **NEEDS: a server-side export route + PII/consent policy + an audit event. CSV export is a standing deferral** (`docs/TIER1-SPEC.md:610-611`, `PROJECT-STATE.md:430`); only statement/folio **PDF** export exists (`App\Services\PdfService`).

**Non-writes (client state only, no backend needed):** filter chips (`f.pick`), Select/Done (`toggleSelect`), select-all (`toggleAll`), row selection (`g.onRow` in select mode), Clear (`clearSel`), modal open/close (`closeMsg`/`closeConfirm`/`stopClick`), textarea input (`onMsgText`).

**Zero-handler (inert) controls:** the search field (a `<div>`, not an input), **Add guest**, **prev page**, **next page**.

## ISOLATION / PII

**Tenant scoping.** `Guest` uses `BelongsToOrganization` (`app/Models/Guest.php:27`) → global scope + auto-fill + the no-context guard. So `Guest::query()` is automatically org-scoped and a naive list is safe. The three stat tiles and the footer count MUST go through the same scoped query (never a raw `DB::table('guests')`). Any `{guest}` route param must be resolved via a tenant-scoped `findOrFail` **in the component/controller — never route-model binding** (RMB trips the tenant scope before context is set; this is the established pattern across `ReservationDetail`, `MaintenanceTicketDetail`, `InboxThread`).

**Route gate.** The page belongs under the `['auth', EnsureBackOfficeAccess::class]` `/app` group (invariant #26). This matters more than usual here: without the gate, an **owner** — who necessarily holds an active Membership — could read the operator's entire guest PII list. The gate is on the group, so a new route inherits it automatically.

**Guest PII — the load-bearing issue on this page.**
- `guests.email` / `guests.phone` are **`'encrypted'` casts over `text` columns**, and additionally **`$hidden = ['email','phone']`** (`Guest.php:47-50`), which strips them from `toArray()`/JSON — so a Livewire-serialized property would silently drop them.
- **Names are fine.** The front-desk precedent shows guest names on operator surfaces (`FrontDeskService::arrivals/departures/inHouse` all eager-load `leadGuest`). The design's name + VIP + initials rendering is consistent with that.
- **Email in the table row is NOT covered by that precedent and needs an explicit decision.** One reservation's guest contact ≠ a bulk-browsable directory of 248 guests' emails on one screen, with a CSV export button next to it. My recommendation: render the name (✅), and treat the email sub-line as a decision to flag — not a default port.
- **The CSV export egresses email in bulk** with no `marketing_consent` check (that column is real and ignored by the design), no audit event, and no rate limit. This is the sharpest PII edge on the page.
- **Anonymized guests must degrade gracefully.** After `anonymize()`, `first_name`/`last_name`/`email`/`nationality` are all `null`. Every cell in the design assumes they are present — initials would break, name would be blank, country blank. The design has no vocabulary for an anonymized profile. Needs a defined treatment (and a decision on whether `anonymized_at IS NOT NULL` rows appear in the list/count at all).
- **Owner surfaces show no guest names** — not relevant here (this is operator-only), but it is why this page must never be reachable from `/owner/*`.

**Selection key.** The design keys selection by `sel[g.email]` — email is encrypted, nullable (post-anonymize), non-unique in the schema, and is PII sitting in `localStorage`. Must be the guest **ULID**.

## TRAPS (Wave-1 style — reconciliation, scoping, real enums, fabrication)

**1. Fabricated figures with no source (do not port).**
- Stat tiles `248` / `38` / `31%` — hard-coded literals in markup (lines 349, 353, 358). None reconcile to the 8 rows.
- **`+4%` (line 359) — the Wave-1 invented-prior-year trap.** There is **no prior-period snapshot anywhere in the schema** — no historical guest metrics table, no time-series. A repeat-guest delta is uncomputable without inventing a baseline. Drop it (or stub honestly per invariant #19).
- Footer **"Showing 8 of 248 guests"** is *static markup*, not bound to the list — it still reads "8 of 248" when the VIP filter shows 1 row.
- The whole 8-row `all[]` array (lines 544-551): invented names, emails, countries, `VYC.fmt(…)` spends.

**2. Money figures whose source is ambiguous — must be named before building.**
"Lifetime" has **no derivation in the codebase** (TIER1-SPEC concedes this: *"add the lifetime-spend derivation"*). Three candidate sources give three different numbers: `Σ folios.total_minor` (**charged**), `Σ payments.amount_minor WHERE type='payment' AND status='succeeded'` (**collected** — and refunds are correct-by-reversal *new offsetting rows*, so they must be netted, not ignored), or `Σ reservations.total_minor`. **Pick one and name it.** Evidence the design never decided: **Jia Chen has `stays:'1'` and `spend: VYC.fmt(0)`** — one stay, €0 lifetime.
**Multi-currency:** `folios.currency` / `reservations.currency` are per-row `char(3)`. The design renders one flat `€` figure. Summing minor units across currencies is unsound (money convention: integer minor + ISO-4217, never floats, no implicit FX; `PROJECT-STATE.md:490` notes auto-FX is deferred — statements take an operator-supplied rate). Either scope to one currency or render per-currency.
Use **`FormatsOperatorData::money(int $minor, string $currency)`** — do not write a new formatter.

**3. Aggregates that must be scoped correctly.**
- **"In-house tonight = 38"** — `FrontDeskService::inHouse()` exists and is correct, but it takes a **`$propertyId`** and returns **Reservations**, not distinct guests. This page has no resolved property (the Property Switcher is unbacked). Decide: org-wide or per-property? guests or reservations? "Tonight" must be computed in the **property timezone** (time convention), not server time.
- **"Stays"** — via `reservations.lead_guest_id` or via the `reservation_guests` pivot? Different numbers. And must `cancelled`/`no_show` be excluded? (A cancelled booking is not a stay.)
- **"Repeat guests %"** — the denominator is undefined (all guests? non-anonymized? guests with ≥1 stay?).

**4. Status vocabulary — the design's three statuses are NOT enum values.**
The design's vocabulary is exactly: **`'In house'`, `'Upcoming'`, `'Past'`** (constants at lines 540-542), plus the filter ids `all | inhouse | upcoming | vip`.
- **`reservations.status` real enum:** `'enquiry','tentative','confirmed','checked_in','checked_out','cancelled','no_show'`. **None of the design's three appear in it.**
- These are *derived guest-level* states, not reservation statuses — which is legitimate, but it means each needs a stated definition (e.g. In house ⇐ ∃ reservation `status='checked_in'` ∧ `check_in <= today < check_out`; Upcoming ⇐ ∃ `confirmed`/`tentative` with `check_in > today`; Past ⇐ otherwise). **Unlike Wave 1's "Scheduled"/"On hold", these aren't invented-from-nothing — but they are undefined, and the design has no state for `cancelled`/`no_show`-only guests, nor for a guest with zero reservations.**
- Other real enums this page touches but never surfaces: `reservations.source` (`'direct','walk_in','ota','corporate','group','agent','manual'` — **no OTA brand is modelled**), `reservations.payment_status` (`'unpaid','partial','paid','refunded','charged_back'`), `message_threads.status` (`'open','snoozed','closed'`), `message_threads.channel` (`'manual','email','whatsapp','booking_com','airbnb'`).

**5. Column semantics that break on contact with the schema.**
- **"Country"** ← `guests.nationality` is `char(2)` **nationality**, not country of residence. The design shows full names ("Ireland"). Needs an ISO2→name map *and* a decision about the relabel.
- **"Last stay"** mixes three incompatible things: a past date (`'20 Sep 2025'`), a month-only date (`'Aug 2025'`), a *current* status (`'In house'`), and a **future** arrival (`'Arrives today'` — for "The Hansen family", whose status is `Upcoming`). A *last stay* column cannot hold a next arrival.
- Demo incoherence: Jia Chen and Kenji Tanaka are tagged `...upcoming` yet carry past `lastStay` dates (`'20 Sep 2025'`, `'17 Sep 2025'`).
- "The Hansen family" is not a person — `first_name`/`last_name` can hold it, but it signals an unmodelled group/household concept (`groups` table exists and is unused here).

**6. Encrypted-column search — the known blind-index deferral.**
The search placeholder promises **"name, email or phone"**. Email/phone are encrypted `text`, *never indexed by value* (stated in both the migration docblock and `GuestMatcher`'s). **No SQL LIKE/=/index can touch them.** This is the standing deferral (`PROJECT-STATE.md:490-491`) and the same gate TIER1-SPEC puts on Global Search (Wave 5 item 17). Name search is fine. **Do not ship the placeholder as written.**

**7. Client-side state that must not be ported.**
`localStorage['vyrobel_guests_sel']` persists `{sel, selectMode}` across reloads — keyed by **email** (PII in localStorage), and it restores a **destructive selection** (reload → Delete bar reappears armed). Wave 1 already rejected this pattern (`OperatorOwnerDetail.php:108`, "its localStorage refund netting").

**8. Dead code in the design.** `bulkMsg.export` is never used (export routes to `doExport()`, which fires its own toast). Minor, but a tell that the logic was assembled rather than reasoned.

## BUILD-READINESS VERDICT

**Roughly 20% ✅ build-now, ~55% 🔨 small derivations (almost all S), ~20% 📋 decorative/inert, ~5% ⬜ other-module.** The ✅ slice is real but thin: the shell chrome, `FormatsOperatorData::initials()`/`money()`, guest names, the **VIP filter + pill** (`guests.is_vip` is a genuine column), all the client-side interaction state (filter/select/modals/toast), and — importantly — **`Guest::anonymize()`**, which is certified and does exactly what the destructive action should do.

**Recommendation: BUILD NOW, with two decisions flagged first.** The 🔨 items are unusually shallow — they are nearly all one derivation each over `Reservation`, and they collapse into a single `Guest::reservations()` relation plus one query object (a `GuestListService` or a `FormatsGuestData`-style trait) supplying stays / last-stay / lifetime / status. That is a normal presentation-gate amount of work, not a new module. Nothing here needs a schema change except the (already-deferred) blind index.

**Two things must be decided before writing code, not during:**
1. **Delete → anonymize.** The design's delete modal contradicts invariant #5 and misdescribes the FKs. The right action already exists; the copy must be rewritten. This is a product-copy decision, not an engineering one — flag it rather than silently swap the verb.
2. **Email in the row + in the CSV.** The front-desk precedent covers names, not a bulk-browsable directory of contact PII with an export button. Needs an explicit call.

**Ship honestly (invariant #19):** drop the `+4%` (unbackable), omit the paused banner (Wave-1 precedent), relabel or gate the search to name-only (blind-index deferral), stub CSV (standing deferral), and either cut the bulk-message modal or rebuild it on `ManualChannel` with truthful copy — never "sent by email", which provably throws. Also note the design **omits** three things the backend already has (`risk_flag`, notes, tags-on-guest): worth deciding whether to surface them now rather than shipping a CRM that is poorer than its own data model.

---

# 6. GUEST PROFILE / detail (operator CRM)

**Design file:** `docs/design/raw/Operator Guest Profile.html` — 1,558,795 B · template 61,837 · 10 onClick · 3 toasts · 4 sc-for

**What it is.** A single-guest CRM detail page: operator shell + a paused-billing banner, a breadcrumb, a title row (name / in-house pill / contact subline / 4 actions), a 3-tile stat strip, then a two-column body — left: Stay history (3 real-shaped rows) + Latest review; right: a dark "Lifetime value" hero, a Contact card, and a Preferences & notes card — plus an "Edit profile" modal. Genuine design-level interactivity is narrow: 10 `sc-camel-on-click` handlers, of which 6 are open/close/stop-propagation on the one modal, and the other 4 are local-state toggles (3 switches + the pref chips); 4 `sc-camel-on-change` inputs are local-state only. There are 2 `sc-for` loops (stays, prefChips) and exactly ONE real toast ("Profile updated") — the brief's "3 toasts / 4 sc-for" is a grep miscount (the toast script itself matches `data-toast` twice). Nothing on this page persists: "Save changes" only fires the toast and closes the modal, and the "Add note" button has NO handler at all. The backend is genuinely strong underneath (Guest + Reservation + Folio + Note + Tag are all real), so the page is largely buildable — but the design invents a location field, a contact-channel preference, an OTA brand, a "Completed" status, a reviews module, and its two headline money figures.

## Feature inventory — every element

> **Verification basis.** Every ✅ below was checked by reading the named file. Live-DB column lists were confirmed via `Schema::getColumnListing('guests'|'notes')`. `php artisan route:list` returns **0 routes matching "guest"** — this page and its list sibling are entirely net-new.

### Shell / sidebar (`resources/views/components/operator/shell.blade.php`)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Vyrobel logo + wordmark | ✅ BUILD-NOW | `<x-operator.shell>` `.vy-brand` (shell.blade.php:50-56) | Already in the built shell. |
| **Property Switcher** (`<dc-import name="Property Switcher">`) | 🔨 NEEDS-BACKEND (M) | No active-property concept exists anywhere: `grep -rni "property.switch\|activeProperty\|current_property"` over `app/` + `resources/views/` = **0 hits**. `PropertyAssignment` (`property_user`) scopes staff to properties but is not a session/UI switcher. Needs: a session-held active-property id + a scope + a shell partial. | Built shell has no switcher. Cross-cutting — decide once for all operator screens, not here. |
| Nav "Overview" group label | 📋 DECORATIVE-FAKE | Built shell groups are `OPERATIONS / DISTRIBUTION / OWNERS / ACCOUNT` (shell.blade.php:22-45) | Design's grouping (Overview/Front desk/Operations/Finance/System) differs from the built shell. Don't re-group for one page. |
| Nav → Dashboard | ✅ BUILD-NOW | `route('app.dashboard')` → `App\Livewire\Operator\OperatorDashboard` | |
| Nav → Calendar | ✅ BUILD-NOW | `route('app.rack')` → `App\Livewire\ReservationRack` (shell key `calendar`) | |
| Nav → Insights | ⬜ OTHER-MODULE | No route, no analytics layer | ROADMAP Tier 2 Analytics. Built shell has no Insights key at all. |
| Nav → Reservations | ⬜ OTHER-MODULE | No list route (`app.reservations.show` is detail-only). Shell key `reservations` is already a `null`-route "Soon" placeholder (shell.blade.php:31) | Sibling Wave 2 page. |
| Nav → Arrivals & departures | ✅ BUILD-NOW | `route('app.front-desk')` → `App\Livewire\FrontDesk` | |
| **Nav → Guests (rendered ACTIVE)** | 🔨 NEEDS-BACKEND (S) | The shell `$navGroups` array has **no `guests` key**. Needs a new `['guests','Guests',<icon>, route('app.guests')]` entry + the new route. | The nav item this whole page hangs off does not exist yet. |
| Nav → Messages | ✅ BUILD-NOW | `route('app.inbox')` → `App\Livewire\InboxList` (shell key `inbox`) | |
| Messages badge **"2"** | 📋 DECORATIVE-FAKE | Hard-coded literal `2` in the markup | *Derivable* ✅ (S): `InboxList.php:97` already computes `$models->where('status','!=','closed')->where('unread_count','>',0)->count()`. Reuse or drop — never hard-code. |
| Nav → Housekeeping | ✅ BUILD-NOW | `route('app.housekeeping')` → `App\Livewire\HousekeepingBoard` | |
| Nav → Maintenance | ✅ BUILD-NOW | `route('app.maintenance')` → `App\Livewire\MaintenanceList` | |
| Maintenance badge **"2"** | 📋 DECORATIVE-FAKE | Hard-coded literal `2` | *Derivable* ✅ (S) from `MaintenanceTicket` where `status` in (open,in_progress). Built shell has no badges. |
| Nav → Rates & availability | ⬜ OTHER-MODULE | No route; shell key `rates` is a `null`-route "Soon" (shell.blade.php:35) | Wave 4. `RateAndRestrictionService` exists but no screen. |
| Nav → Channels | ⬜ OTHER-MODULE | No route; shell key `channels` is a `null`-route "Soon" (shell.blade.php:34) | Wave 4. |
| Nav → Owners | ✅ BUILD-NOW | `route('app.owners')` → `App\Livewire\Operator\OperatorOwners` | |
| Nav → Reports | ⬜ OTHER-MODULE | No route, no key in the built shell | |
| Nav → Settings | ⬜ OTHER-MODULE | Shell key `settings`, `null` route "Soon" (shell.blade.php:42) | |
| Nav → Security | ⬜ OTHER-MODULE | Shell key `security`, `null` route "Soon" (shell.blade.php:43) | |
| Built shell's **Payout run** nav item | — | `route('app.payouts.run')` exists in the shell but is **absent from this design's nav** | Don't lose it when porting the nav. |

### Paused / read-only banner — ⚠️ ALREADY REJECTED BY PRECEDENT

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| **"Workspace paused — read-only until 5 Oct"** banner | 📋 DECORATIVE-FAKE | **Explicit locked precedent:** `app/Livewire/Operator/OperatorOwnerDetail.php:101-102` OMITS this exact banner — *"the 'Workspace paused — read-only until 5 Oct' billing banner: subscription/billing has no backend; porting it would fabricate a system status."* | `subscriptions.status` DOES include `'paused'` as a real enum value (migration `..._000400_create_subscriptions_tables.php:19`) — but **zero code reads it** (`grep -rn "subscription" app/Http/Middleware/ app/Livewire/ app/Services/` → only that comment). No read-only enforcement, no "until 5 Oct" date column, no billing surface. **Do not port.** |
| Pause icon glyph | 📋 DECORATIVE-FAKE | Inline SVG | |
| "channels stopped selling" sub-line | 📋 DECORATIVE-FAKE | No mechanism — nothing pauses Channex ARI on subscription status | Fabricated system claim. |
| `<span data-vyc-zero=""></span> while paused` | 📋 DECORATIVE-FAKE | Empty span filled at runtime by the design-kit `VYC` currency global | Renders as a blank/zero placeholder. Not data. |
| "Resume now" → `Operator Billing.dc.html` | ⬜ OTHER-MODULE | No billing route/screen exists | |

### Breadcrumb

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "‹ Guests" back-link | 🔨 NEEDS-BACKEND (S) | Needs `route('app.guests')` — the sibling Wave 2 list page | TIER1-SPEC:81 rates the list **READY**. Build the list first so this link is honest. |
| "/ Amara Okafor" current crumb | ✅ BUILD-NOW | `Guest::first_name` + `last_name` (`guests` migration:22-23) | |

### Title row

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Avatar circle "AO" | ✅ BUILD-NOW | `FormatsOperatorData::initials()` (FormatsOperatorData.php:32-44) — already used by `InboxThread.php:79` | Reuse, don't rewrite. |
| `<h1>` guest name | ✅ BUILD-NOW | `Guest::first_name` + `last_name` | Front-desk PII precedent: name is fine (`FrontDesk.php:126`). |
| **"In house · Villa Azure"** pill + live dot | ✅ BUILD-NOW | `Reservation::status === 'checked_in'` (real enum) + `reservation.reservationUnits.unit->name` (`units.name`, Unit.php:32). Label via `FormatsOperatorData::reservationStatusMeta('checked_in')` → `['label'=>'Checked in','tone'=>'in-house']` | Design says "In house"; the built vocabulary says **"Checked in"**. Use the existing helper — one vocabulary across the app. |
| **"Repeat guest"** pill | 🔨 NEEDS-BACKEND (S) | No column. Derive: `Reservation::where('lead_guest_id',$id)->count() > 1`. | Pure derivation — do NOT add a column. |
| Subline: `amara.okafor@mail.com` | ✅ BUILD-NOW | `Guest::email` — `'email' => 'encrypted'` cast (Guest.php:59), `text` column (migration:24) | Decrypts server-side; `$hidden` (Guest.php:47-50) only affects `toArray()/toJson()`, **not** Blade `{{ }}`. See `isolation_pii` — the *policy* is a decision, the *capability* is real. |
| Subline: **"Ireland"** | 🔨 NEEDS-BACKEND (S) | `guests.nationality` is `char(2)` (migration:26) → `'IE'`. Needs a code→name map (a `FormatsOperatorData::countryName()` helper). | ⚠️ Semantic trap: `nationality` ≠ **residence**. The design uses it as a location. |
| Subline: **"guest since Aug 2023"** | ✅ BUILD-NOW | `min(reservations.check_in)` where `lead_guest_id`. **Exact precedent:** `InboxThread::guestCard()` (InboxThread.php:178-183) already computes `$count.' stays · guest since '.$year` this way. | Reuse that logic — lift it into the shared helper (see `needs_backend`). |
| `<dc-import name="Notif Bell">` | 📋 DECORATIVE-FAKE | Built shell already renders an inert bell: `title="Notifications — coming soon"` (shell.blade.php:93) | Precedent is honest-inert. Keep it that way. |
| **"Message"** button → `Operator Messages` | 🔨 NEEDS-BACKEND (S) | `message_threads.guest_id` is REAL (MessageThread.php:52). Linking to an *existing* thread = ✅ (`MessageThread::where('guest_id',$id)->latest('last_message_at')->first()` → `route('app.inbox.show', $thread)`). **Starting a new** thread needs wiring to the existing `InboxService::startThread(array $data)` (InboxService.php:75). | Note the reverse link is already computed-but-unrendered: `InboxThread::guestCard()` returns `'guest_id'` (InboxThread.php:191) and the blade never renders it (`inbox-thread.blade.php:139-140`) — building this page lights that up. |
| **"Edit profile"** button (`openGpe`) | ✅ BUILD-NOW | Design-interactive: `openGpe: () => this.setState({ gpeOpen: true })` | Opens the modal. Modal *contents* are a different story — see below. |
| **"Current stay"** CTA → Reservation Detail | ✅ BUILD-NOW | `route('app.reservations.show', $reservation)` → `App\Livewire\ReservationDetail`. Find via `lead_guest_id` + `status='checked_in'`. | Hide when the guest has no in-house stay — don't render a dead CTA. |

### Stat strip (3 tiles)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Tile "STAYS" = **3** | ✅ BUILD-NOW | `Reservation::where('lead_guest_id',$id)->count()` | ⚠️ Scope decision: `lead_guest_id` only, or also `reservation_guests.guest_id` (a real table — migration `..._001430`, model `ReservationGuest`)? `InboxThread` uses `lead_guest_id` only. Pick ONE and be consistent. |
| Tile sub "3rd underway now" | 🔨 NEEDS-BACKEND (S) | Ordinal derivation: index of the in-house stay in the date-ordered list | Trivial, but must degrade when no stay is in-house. |
| Tile "NIGHTS" = **14** | ✅ BUILD-NOW | `Σ check_in->diffInDays(check_out)`. Precedent: `ReservationDetail.php:90` `'nights' => (int) $checkIn->diffInDays($checkOut)` | Half-open `[check_in, check_out)` — convention holds. |
| Tile sub "across 2 units" | ✅ BUILD-NOW | Distinct `reservation_units.unit_id` across the guest's reservations | |
| Tile "AVG / NIGHT" `{{ avgNightStr }}` | ✅ BUILD-NOW (derivation) / 📋 (the number) | Computed `Math.round((3940 - amt) / 14)` — from the **hard-coded 3940**. Real source: LTV ÷ nights. | The *formula* is sound; the *inputs* are fabricated. Inherits the LTV ambiguity below. |
| Tile sub **"2 direct · 1 Booking.com"** | 🔨 NEEDS-BACKEND (S) — partly impossible | `'direct'` ✅ is a real `source` enum value. **"Booking.com" is NOT MODELLED**: `reservations.source` enum = `('direct','walk_in','ota','corporate','group','agent','manual')` (migration:22) — no brand. `channels.provider` enum = **`['channex']` only** (channels migration:23). `FormatsOperatorData::sourceLabel()` maps `'ota' => 'OTA'` (line 99). | Best honest render: **"2 direct · 1 OTA"**. The only brand-ish string is `channels.name` (free text an operator typed) — not an enum, not reliable. ⚠️ Don't confuse with `message_threads.channel` which DOES have `booking_com`/`airbnb` — that's the *messaging* medium, not distribution. |

### Stay history panel

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| `<h2>` "Stay history" | ✅ BUILD-NOW | — | |
| "All reservations" link | 🔨 NEEDS-BACKEND (S) | No `/app/reservations` list route exists | Sibling Wave 2 page. |
| `sc-for list="{{ stays }}"` (3 rows) | ✅ BUILD-NOW | `Reservation::where('lead_guest_id',$id)->orderByDesc('check_in')` — precedent `InboxThread::linkableReservations()` (InboxThread.php:229-236) | Needs a `Guest::reservations()` relation — **it does not exist today** (`grep -c "function reservations" app/Models/Guest.php` → **0**). |
| Row: `s.dates` "12–15 Sep 2025" | ✅ BUILD-NOW | `reservations.check_in` / `check_out` (`date` casts) | |
| Row: `s.subLine` unit "Villa Azure" | ✅ BUILD-NOW | `reservation.reservationUnits.first()->unit->name` — precedent `InboxThread.php:207,212` | |
| Row: `s.subLine` ref "VYR-10428" | ✅ BUILD-NOW | `reservations.reference` (unique per org, migration:21) | |
| Row: `s.subLine` source "Direct" | ✅ BUILD-NOW | `FormatsOperatorData::sourceLabel($reservation->source)` | |
| Row: `s.subLine` source **"Booking.com"** (row 2) | 🔨 NEEDS-BACKEND (S) — see stat tile above | Not modelled | Render **"OTA"**. |
| Row: `s.subLine` "3 nights" | ✅ BUILD-NOW | Derived from the dates | |
| Row: `s.total` | ✅ BUILD-NOW | `reservations.total_minor` — kept in sync from the ledger by `FolioService::recompute()` (FolioService.php:364-372). Format via `FormatsOperatorData::money()`. | Reconciles to `folios.total_minor` by construction. |
| Row: `s.refunded` → **"−€X refund"** | ✅ BUILD-NOW (concept) / 📋 (the source) | Real: `payments` where `type='refund'` AND `status='succeeded'` (`FolioTotals::for()`, FolioTotals.php:36-37). **Design's source is fake:** `refundAmt()` reads `window.localStorage.getItem('vyrobel:refund:last')` (script:622). | This localStorage device spans ~10 design files (`Operator Refund.html` writes it). **Already rejected by precedent** — `OperatorOwnerDetail.php:111` omits "its localStorage refund netting". |
| Row: `s.status` pill "**In house**" | ✅ BUILD-NOW | `status='checked_in'` → `reservationStatusMeta()` → **"Checked in"** | Use the built label. |
| Row: `s.status` pill "**Completed**" | 📋 DECORATIVE-FAKE — **INVENTED STATUS** | `const done = { status: 'Completed', ... }` (script:685). **"Completed" is NOT in the `status` enum** `('enquiry','tentative','confirmed','checked_in','checked_out','cancelled','no_show')`. | Real value is `'checked_out'` → `reservationStatusMeta()` → **"Checked out"**. Same class of defect as Wave 1's "Scheduled"/"On hold". |
| Row: `s.pillBg` / `s.pillCo` inline colours | 📋 DECORATIVE-FAKE | Hard-coded per-row hex | Use the `tone` token from `reservationStatusMeta()`. |
| Row: `s.current` live dot (`sc-if`) | ✅ BUILD-NOW | `status === 'checked_in'` | |
| Row: chevron `›` icon | 📋 DECORATIVE-FAKE | Inline SVG affordance | |
| Row link `s.href` | ✅ BUILD-NOW (route exists) / 📋 (design's hrefs) | Row 1 → `Operator Reservation Detail.dc.html`; **rows 2 & 3 are `href: '#'`** (script:688-689) — inert in the design | `route('app.reservations.show', $id)` is real for **all** rows. Link every row. |
| Footnote "First stay August 2023 · every stay at Seaside Villas" | ✅ BUILD-NOW | `min(check_in)` + distinct `property.name` (`properties.name`, migration:18) | The "every stay at X" phrasing must collapse gracefully when properties differ. |

### Latest review panel — ENTIRELY UNBUILT MODULE

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| `<h2>` "Latest review" | ⬜ OTHER-MODULE | **No reviews table, model, or migration exists.** `grep -ril "review" app/Models/ database/migrations/` → **0 hits**. The only `rating` in the schema is `properties.star_rating` (the hotel's own star class, migration:28) — unrelated. | Reviews are a whole module (L). ROADMAP Tier 2. |
| "All reviews" link → `Operator Reviews` | ⬜ OTHER-MODULE | No route | |
| 5 filled star icons | 📋 DECORATIVE-FAKE | Five hard-coded `fill="#E8703A"` SVGs — not data-driven, no `sc-for`, no partial star | |
| Score "**5.0**" | 📋 DECORATIVE-FAKE | Hard-coded literal `5.0` | |
| Review body quote | 📋 DECORATIVE-FAKE | Hard-coded: *"Spotless villa and a seamless check-in — the team even remembered we like the quiet side. We'll be back."* | |
| "Villa Coralis · June 2024 · via Booking.com" | 📋 DECORATIVE-FAKE | Hard-coded; also re-uses the unmodelled OTA brand | |
| **Verdict on this panel** | ⬜ OTHER-MODULE | **Omit the whole panel.** Same call as `OperatorOwnerDetail` made on its no-backend blocks. | ~15% of the page's visual weight. Reflow the left column. |

### Dark hero — Lifetime value ⚠️ THE PAGE'S HEADLINE TRAP

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| "LIFETIME VALUE" label | ✅ BUILD-NOW | — | |
| **`{{ ltvStr }}`** big figure | ✅ BUILD-NOW (derivable) — **but the definition must be decided** | Real sources, both named: **(A) BILLED** = `Σ FolioTotals::for($folio)['charges_minor']` over the guest's folios ≡ `Σ folios.total_minor` ≡ `Σ reservations.total_minor` (FolioService.php:350,364-372 keeps all three in sync). **(B) COLLECTED** = `Σ FolioTotals::for($folio)['paid_minor']` (settled payments − succeeded refunds, FolioTotals.php:24-42). | **THE DESIGN IS SELF-CONTRADICTORY** — see `traps`. This is exactly TIER1-SPEC's "add the lifetime-spend derivation" (line 575), and the decision is the work, not the SQL. |
| Count-up animation (`componentDidMount`, 800ms cubic ease-out) | 📋 DECORATIVE-FAKE | `const target = 3940 - this.refundAmt()` (script:629-639) | Pure presentation; the `3940` is the fabricated constant. |
| **`{{ ltvSubline }}`** "3 stays · 14 nights · avg €281 a night" | ✅ BUILD-NOW | All three derivable (count / Σ nights / LTV÷nights) | Internally consistent in the design: 1023+1120+1797 = **3940** ✓, 3+5+6 = **14** ✓, 3940÷14 = **281** ✓. Credit where due — unlike Wave 1, the base arithmetic reconciles. |
| Divider rule | 📋 DECORATIVE-FAKE | — | |
| **`{{ openFolioStr }}`** pill "€701 open folio" | ✅ BUILD-NOW (derivable) / 📋 (the constants) | Real: `FolioTotals::for($folio)['balance_minor']` for the in-house stay ≡ `folios.balance_minor`. **Design hard-codes BOTH branches:** `openFolioStr: amt > 0 ? (e0(978) + ' open folio') : (e0(701) + ' open folio')` (script:699). | **978 only reconciles if the refund is exactly €277** (701+277) — yet `refundAmt()` returns an *arbitrary* localStorage number. A €50 refund still prints €978. Non-reconciling fabrication. |
| "settles at checkout" caption | 📋 DECORATIVE-FAKE | Copy — no mechanism asserts this | Harmless if the balance is real, but it's an unbacked claim. |

### Contact card

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| `<h2>` "Contact" | ✅ BUILD-NOW | — | |
| Email row + envelope icon | ✅ BUILD-NOW | `Guest::email` (encrypted cast, Guest.php:59) | Capability real; **policy is a decision** — no built screen renders guest contact today (`FrontDesk.php:126` renders name only). |
| Phone row + phone icon | ✅ BUILD-NOW | `Guest::phone` (encrypted cast, Guest.php:60) | Same. |
| **"Dublin, Ireland"** location row + pin icon | 🔨 NEEDS-BACKEND (S) | **No city/address column on `guests`.** Live DB confirms: `id, organization_id, first_name, last_name, email, phone, nationality, dob, language, is_vip, risk_flag, marketing_consent, anonymized_at, external_refs, created_at, updated_at, deleted_at`. Needs a nullable `guests.city` (+ arguably `country`, since `nationality` is not residence). | Contrast: `properties.address` IS a real json column — guests have nothing. Either add the column or render country-from-nationality only. |
| Divider rule | 📋 DECORATIVE-FAKE | — | |
| Footer "**Prefers email**" | 🔨 NEEDS-BACKEND (S) | **No column.** `guests.language` exists but is a locale, not a contact-channel preference. Needs `guests.preferred_channel` (enum) or a boolean. | |
| Footer "**marketing consent on file**" | ✅ BUILD-NOW | `guests.marketing_consent` boolean, default false (migration:31; cast Guest.php:62) | Column is real — but read by **zero** code today (`grep -rn "marketing_consent" app/ resources/views/` → only the model). This page would be its first reader. |

### Preferences & notes card

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| `<h2>` "Preferences & notes" | ✅ BUILD-NOW | — | |
| 4 preference chips (Sea view / High floor / Late checkout / Quiet room) | 🔨 NEEDS-BACKEND (S–M) — **but REUSE `tags`, don't build new** | `tags` + `taggables` + `HasTags` on `Guest` are REAL (tags migration `..._001130`; Guest.php:27) and `'guest' => Guest::class` is morph-mapped (AppServiceProvider.php:150) under `enforceMorphMap`. So a tag-backed chip works **today** via Eloquent. **What's missing:** nothing anywhere writes tags (`grep -rn "tags()\|Tag::" app/ database/seeders/` → only the morph-map line), there's no seeded catalog, and no toggle controller. | ⚠️ Do NOT invent a `guest_preferences` table — convention #6 (reuse, don't duplicate). `tags.color` is a real nullable column and can back the design's 2-tone chip styling. |
| Chip active/muted 2-tone styling | 📋 DECORATIVE-FAKE | Hard-coded per-chip hex; "Sea view" terracotta vs the rest muted, with **no backing distinction** | Nothing in the design's data explains why Sea view is highlighted. |
| Note card body text | ✅ BUILD-NOW | `Note::body` (`notes.body` text — live DB confirmed). `Guest` uses `HasNotes` → `morphMany(Note::class,'notable')` | Real table, real relation, zero current writers — this page is its first user. |
| Note byline "**Marco · 12 Sep**" | ✅ BUILD-NOW | `notes.author_id` → `Note::author()` → `User::name` (Note.php:34-37) + `notes.created_at` | |
| **"Add note" button** | 📋 DECORATIVE-FAKE — **INERT** | **No `sc-camel-on-click` on this button** (template:497). It is markup only — the Wave 1 Payout-Run failure mode. | Backend side is easy: `Note::create()` + a POST controller (S) per the operator-mutation pattern (POST → controller → PRG), mirroring `MaintenanceActionController`. |
| `notes.visibility` enum (`internal`/`shared`) | — | Real column, **not surfaced** by the design at all | A guest-facing "shared" note rendered with no visibility indicator is a leak risk. Flag for the build. |

### Edit profile modal (`sc-if value="{{ gpeOpen }}"`) — MOCK; PERSISTS NOTHING

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| Backdrop + blur, click-to-close (`closeGpe`) | ✅ BUILD-NOW | Design-interactive | |
| Modal panel, `stopClick` propagation guard | ✅ BUILD-NOW | `stopClick: (e) => e.stopPropagation()` | |
| Header avatar "AO" | ✅ BUILD-NOW | `initials()` | |
| Title "Edit profile" | ✅ BUILD-NOW | — | |
| Sub "Amara Okafor · guest since 2023" | ✅ BUILD-NOW | Name + `min(check_in)` year | |
| "×" close button (`closeGpe`) | ✅ BUILD-NOW | Design-interactive | |
| **"Guest name"** single input (`gNameStr` / `onGName`) | ✅ BUILD-NOW — with a shape mismatch | `guests.first_name` + `guests.last_name` are **two** columns (migration:22-23) | The design has ONE field. Either split on last-space (lossy for multi-part names) or use two inputs. Decide, don't guess. |
| **"Email"** input (`gEmailStr` / `onGEmail`) | ✅ BUILD-NOW | `guests.email` (encrypted cast) — in `$fillable` (Guest.php:34) | |
| **"Phone"** input (`gPhoneStr` / `onGPhone`) | ✅ BUILD-NOW | `guests.phone` (encrypted cast) — in `$fillable` (Guest.php:35) | |
| **"City / country"** input (`gLocStr` / `onGLoc`) | 🔨 NEEDS-BACKEND (S) | No column — see Contact card above | |
| "Preferences" label + hint **"Shown to housekeeping & front desk"** | 📋 DECORATIVE-FAKE — **unbacked claim** | **Verified false today:** neither `HousekeepingBoard` nor `FrontDesk` reads tags or any guest preference. | Don't ship a promise the app doesn't keep. |
| 8 preference chips `sc-for` + `onToggle` + check-dot opacity | 🔨 NEEDS-BACKEND (S–M) | Catalog hard-coded in `gpeVals()` (script:643-647): `seaView, highFloor, lateCheckout, quietRoom, groundFloor, earlyCheckin, extraTowels, crib`. State-only: `onToggle: () => this.setState(...)`. | Back with `tags` (above). The 8-item list is a designer's guess — make it the tenant's real tag catalog. |
| Toggle **"VIP guest"** (`toggleGVip`) | ✅ BUILD-NOW | **`guests.is_vip` boolean, default false** (migration:29; cast Guest.php:61; `$fillable` Guest.php:38) | Column real. ⚠️ Its description *"Flags arrivals & the front desk for extra care"* is **aspirational**: `grep -rn "is_vip" app/ resources/views/` → **0 hits outside the model**. The toggle can be real; the promised downstream effect does not exist. |
| Toggle **"Prefers email contact"** (`toggleGEmailPref`) | 🔨 NEEDS-BACKEND (S) | No column (see Contact card) | Description *"Default channel for confirmations & messages"* — no such routing exists. |
| Toggle **"Marketing consent"** (`toggleGMkt`) | ✅ BUILD-NOW | `guests.marketing_consent` boolean (migration:31) | Column real; description *"Seasonal offers & the occasional newsletter"* has no sending mechanism. |
| Toggle track/knob colours + slide (`gVipBg`/`gVipLeft` etc.) | 📋 DECORATIVE-FAKE | Computed inline hex/px in `gpeVals()` (script:674-680) | Presentation. |
| **"Save changes"** button | 📋 DECORATIVE-FAKE — **SAVES NOTHING** | `data-toast="Profile updated"` + `sc-camel-on-click="{{ closeGpe }}"` (template:609). It fires the toast and closes the modal. **No persistence, no validation, no request.** | The page's only toast. A design-level toast is a mock, not a backend. Real path: POST → `GuestActionController` → Eloquent update → PRG (S). |
| **"Cancel"** button (`closeGpe`) | ✅ BUILD-NOW | Design-interactive | |
| Footnote "Contact changes apply to future confirmations, not sent emails" | 📋 DECORATIVE-FAKE | Copy — true in spirit, no mechanism behind it | |

### Ambient / motion chrome

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| `motion` prop + `sc-if` on the drifting orb | 📋 DECORATIVE-FAKE | Design-kit boolean prop (`data-props`, template:617) | |
| 2 light streaks, 3 blurred colour orbs, `vyFadeUp`/`vyShimmer`/`vyDrift` keyframes, staggered `animation-delay` | 📋 DECORATIVE-FAKE | Pure CSS | Match the built operator design system instead of porting inline styles. |
| Global toast injector script (`window.__vyToast`) | 📋 DECORATIVE-FAKE | Design-kit runtime | Livewire/session-flash is the built pattern. |
| `window.VYC` / `VYC.fmt()` currency global | 📋 DECORATIVE-FAKE | Design-kit runtime | Real equivalent: `FormatsOperatorData::money()`. |

### ⚠️ Designed-elsewhere / expected-but-ABSENT (so nothing is silently dropped)

| Feature | Tag | Backing / What it needs | Notes |
|---|---|---|---|
| **Folio / payment history panel** | — | The brief expected one; **the design has none** | Correct call — the folio lives on `ReservationDetail` (real ledger + real record-payment/refund). **Reuse, don't duplicate.** |
| **Messages / conversation panel** | — | The brief expected one; **the design has none** — only the "Message" button | Real inbox exists (`InboxList`/`InboxThread`). Link out, don't rebuild. |
| **Documents panel** | — | **Absent from the design**, despite `documents` + `media` + `HasDocuments` on `Guest` (Guest.php:27) all being real | Genuinely available if wanted later. |
| **ID verification** | — | **Absent from the design**, despite `guest_documents` being REAL with `verification_status` enum `('pending','verified','rejected')` and `verified_by` → users (guest_documents migration:32-34) | The brief guessed "⬜ guest portal?" — no: the backend is **already here**, the *design* just doesn't use it. Worth raising. |
| **`risk_flag`** | — | Real column (migration:30), listed in TIER1-SPEC:222 — **the design never shows it** | The list page shows risk badges; the profile doesn't. Inconsistent. |
| **GDPR `anonymize()` action** | — | REAL + certified (Guest.php:87-142) — purges PII, hard-deletes ID docs + media bytes, redacts inbox threads, writes audit + domain events. **Called from ZERO routes/controllers** (`grep -rn "anonymize()" app/ routes/` → model only) | The guest profile is the natural home for this. The design omits it entirely. Flag as a product gap. |
| **`dob` / `language` / `external_refs`** | — | Real columns; not surfaced | `external_refs` (json, OTA guest ids) explains OTA linkage — could be useful. |

### Honest counts (correcting the brief)

| Claim | Reality |
|---|---|
| "10 onClick" | ✅ **Correct** — but 6 are modal open/close/stop-propagation and 4 are local-state toggles. **Zero** reach a backend. |
| "3 toasts" | ❌ **One** real toast (`data-toast="Profile updated"`). The other 2 grep hits are the injector script's own `closest('[data-toast]')` + `getAttribute('data-toast')`. |
| "4 sc-for" | ❌ **Two** (`stays`, `prefChips`). There are 4 `sc-if` and 4 `sc-camel-on-change`. |

## WRITES

**The design performs ZERO real writes. All 10 `sc-camel-on-click` handlers mutate local component state or open/close the modal — none issue a request.**

1. **"Save changes" (Edit profile modal)** — design: `data-toast="Profile updated"` + `closeGpe`. Persists NOTHING; no validation, no request. Covers name / email / phone / city / 8 pref chips / 3 toggles.
   → **NEEDS: `App\Http\Controllers\Operator\GuestActionController::update()`** — POST → `Guest::update()` (all fields are already in `$fillable`, Guest.php:29-42) → PRG. Follow the proven operator-mutation pattern (`MaintenanceActionController` / `ReservationFolioActionController`): plain string `{guest}` param resolved via tenant-scoped `findOrFail` (never RMB) → cross-org 404. **NO existing service method** — `Guest` has no service; plain Eloquent is correct here (there's no inventory/ledger guarantee to protect). Size **S**.
   → Blocked on: `guests.city` + a contact-preference column (see `needs_backend`).

2. **"Add note"** — design: **INERT** (no handler at all, template:497).
   → **NEEDS: `GuestActionController::addNote()`** — POST → `$guest->notes()->create(['body'=>…, 'author_id'=>auth()->id(), 'visibility'=>'internal'])` → PRG. `Note` model + `HasNotes` + morph map `'guest'` all real. No existing service. Size **S**. Decide whether `visibility` is exposed.

3. **Preference chip toggles (both the card and the modal)** — design: `this.setState(...)` only.
   → **NEEDS: `GuestActionController::toggleTag()`** — POST → `$guest->tags()->toggle($tagId)` via the real `HasTags` `morphToMany`. Requires a seeded tenant tag catalog first (nothing writes `tags` anywhere today). Size **S** (write) / **M** (with catalog + management).

4. **VIP / Marketing-consent toggles** — real columns (`is_vip`, `marketing_consent`); covered by write #1.

5. **"Prefers email" toggle** — no column; cannot be written. See `needs_backend`.

**Existing writes this page correctly does NOT duplicate (reuse, don't rebuild):**
- Record payment / refund → `FolioService::recordPayment()` / `FolioService::refundPayment()` via `ReservationFolioActionController` on `ReservationDetail`. *(Note: `FolioService::refundPayment(Payment $original, Money $amount)` **DOES support partial amounts** — it validates `amount <= original->amount_minor` (FolioService.php:216-256) — but has **no `reason` parameter**. The **controller** is what hard-codes a FULL refund: `new Money((int) $payment->amount_minor, …)` (ReservationFolioActionController.php:60). So "partial refund" is a controller gap, not a service gap.)*
- Check-in / check-out → `FrontDeskActionController` → `App\Actions\Reservations\CheckIn/CheckOut`.
- Reply / assign / close / snooze → `InboxService` via `InboxActionController`.
- Any inventory change → `AssignmentService` (single guarded write path — untouched by this page).

**A real write this page SHOULD arguably have and doesn't:** `Guest::anonymize()` (GDPR erase) is fully built + certified but reachable from no UI anywhere in the app.

## ISOLATION / PII

**Tenant scoping — straightforward, precedent is clear.**
`Guest`, `Reservation`, `Note`, `Tag`, `Folio`, `Payment`, `MessageThread` all use `BelongsToOrganization` (global scope + guard that throws with no tenant context). This is an **operator** surface → TENANT-scoped, never owner-scoped. Follow the locked RMB rule used by every built operator screen: `{guest}` is a plain **string** prop resolved in `render()` via a tenant-scoped `findOrFail` **after** `SetTenantForWebRequest` — never a route-model-bound prop (`ReservationDetail.php:50-61`, `InboxThread.php:47-58`, `MaintenanceTicketDetail`). A cross-org guest id then 404s. The route must sit inside the `['auth', EnsureBackOfficeAccess::class]` `/app` group (routes/web.php) — that gate is what stops an *owner* (who necessarily holds a Membership) walking into the back office; it's on the group, so a new route inherits it automatically.

**Aggregate scoping — the real risk.** Every figure on this page (stays, nights, LTV, avg/night, open folio, source mix) is a **cross-reservation aggregate for one guest**. Each must be scoped by BOTH the tenant global scope AND `lead_guest_id = $guest->id`. Also settle the `lead_guest_id`-only vs `lead_guest_id` ∪ `reservation_guests.guest_id` question **once** — `reservation_guests` is a real table (migration `..._001430`, model `ReservationGuest`), and the two definitions give different numbers for the same guest. `InboxThread::guestCard()` (InboxThread.php:178) uses `lead_guest_id` only; match it or change both.

**PII — the sharpest call on this page.**
- `guests.email` / `guests.phone` are **`encrypted` casts** over `text` columns (Guest.php:59-60, migration:24-25) — never indexed by value, decryptable server-side. `$hidden = ['email','phone']` (Guest.php:47-50) affects only `toArray()/toJson()` — **it does NOT stop Blade `{{ $guest->email }}`**. So rendering them is entirely possible.
- **The design renders email + phone in three places** (title subline, Contact card, Edit modal inputs) — and **no built screen renders guest contact today**. `FrontDesk.php:126` and `InboxThread::guestName()` (InboxThread.php:152-163, comment: *"Guest NAME only (front-desk precedent) — never the encrypted participant identity/contact"*) both render **name only**. TIER1-SPEC:218-219 says *"guest NAME always; contact per policy"* — **the policy is not written down anywhere.** This page would be the first contact-rendering surface in the product. That is a **decision to take deliberately**, not a default. It's defensible (an operator CRM is exactly where staff need to phone a guest) — but it should land in `docs/DECISIONS.md`, and the write path (the modal edits email/phone) makes it doubly consequential.
- **Owner boundary — unaffected.** Owner surfaces show NO guest names at all (a stricter, separate rule). Nothing here touches `/owner/*` or `OwnerScope`; `SetOwnerScopeForPortal` is not in this route group.
- **GDPR interaction:** `Guest::anonymize()` nulls `first_name`/`last_name`/`email`/`phone`/`nationality`/`external_refs` and sets `anonymized_at`. This page must render an anonymized guest **gracefully** — name and contact become `null`, but the reservation/financial history (and therefore LTV, stays, nights) **survives by design** (the row is preserved for referential integrity). An unguarded `trim($first.' '.$last)` yields an empty header. `Guest::isAnonymized()` (Guest.php:77) exists — use it. Also: `notes` attached to a guest are **not** touched by `anonymize()` — an operator's free-text note ("Likes the quiet side…") can itself contain PII and would survive an erase. Worth raising as a real GDPR gap, not something this page introduces.
- `message_threads.participant_identity` is encrypted and must never be rendered (invariant) — this page doesn't, and shouldn't start.

## TRAPS (Wave-1 style — reconciliation, scoping, real enums, fabrication)

**1. LIFETIME VALUE IS SELF-CONTRADICTORY — the page's headline number.**
The design computes `target = 3940 - this.refundAmt()` (script:629), i.e. it **nets a refund off LTV**. But `3940 = 1023 + 1120 + 1797` = the sum of **stay totals**, which includes the in-house stay's full €1023 even though €701 of it is **still unpaid** (its own open-folio pill says so).
- Netting a refund is only consistent with **COLLECTED** (`Σ FolioTotals::for($f)['paid_minor']` — a refund is a `payments` row, not a `folio_item`).
- Including the unpaid €701 is only consistent with **BILLED** (`Σ charges_minor` ≡ `Σ folios.total_minor` ≡ `Σ reservations.total_minor`, kept in sync by `FolioService::recompute()`, FolioService.php:364-372).
**It cannot be both.** Under COLLECTED the real number would be 3940−701 = €3,239, not €3,940. Pick one, name it in the label ("Lifetime billed" vs "Lifetime collected"), and record it in `docs/DECISIONS.md`. This *is* the "add the lifetime-spend derivation" work TIER1-SPEC:575 flags — the SQL is trivial; the definition is the deliverable. **Everything downstream inherits the choice**: the avg/night tile, the `ltvSubline`, and the Guests-list "Lifetime" column (TIER1-SPEC:217) must use the identical helper or the list and the profile will disagree.

**2. `€978 open folio` DOES NOT RECONCILE.** `openFolioStr: amt > 0 ? (e0(978) + ' open folio') : (e0(701) + ' open folio')` (script:699). Both branches are hard-coded, and 978 = 701 + 277 — it is only correct if the refund is **exactly €277**. But `refundAmt()` returns an arbitrary number from localStorage, so a €50 refund still prints €978. Real source: `FolioTotals::for($folio)['balance_minor']` ≡ `folios.balance_minor`.

**3. "Completed" IS NOT A REAL STATUS.** `const done = { status: 'Completed', … }` (script:685). The `reservations.status` enum is exactly `('enquiry','tentative','confirmed','checked_in','checked_out','cancelled','no_show')` (migration:23). Real value → `'checked_out'` → `FormatsOperatorData::reservationStatusMeta()` → **"Checked out"**. Same class as Wave 1's "Scheduled"/"On hold". Design's full status vocabulary: **"In house"** → real `checked_in` (built label: "Checked in"); **"Completed"** → **INVENTED** (real: `checked_out`).

**4. "Booking.com" IS NOT MODELLED.** Appears 3× (avg/night sub-line "2 direct · 1 Booking.com"; stay row 2 sub-line; the fake review's "via Booking.com"). `reservations.source` has no brand — only `'ota'` (migration:22), and `channels.provider` is `enum('channex')` **only** (channels migration:23). `FormatsOperatorData::sourceLabel()` maps `'ota' => 'OTA'`. Render **"OTA"**. ⚠️ Don't be fooled by `message_threads.channel`, which *does* contain `booking_com`/`airbnb` — that's the **messaging medium**, a different enum for a different concept (`channelMeta()`, FormatsOperatorData.php:261-271).

**5. FABRICATED DATA NOT TO PORT** — quote-level:
- `state = { ltv: 3940, … }` and `const target = 3940 - this.refundAmt()` — the LTV constant.
- `e0(978)` / `e0(701)` — the open-folio constants.
- Stay totals `this.euro(1023)`, `this.euro(1120)`, `this.euro(1797)`; refs `VYR-10428`, `VYR-8611`, `VYR-7203`; dates `12–15 Sep 2025`, `8–13 Jun 2024`, `5–11 Aug 2023`.
- `gName: 'Amara Okafor', gEmail: 'amara.okafor@mail.com', gPhone: '+353 87 224 1180', gLoc: 'Dublin, Ireland'`.
- The whole review block: `5.0`, five hard-coded filled stars, the quote string, `Villa Coralis · June 2024 · via Booking.com`.
- Nav badges `2` and `2`.
- `prefs: { seaView: true, highFloor: true, lateCheckout: true, quietRoom: true, groundFloor: false, earlyCheckin: false, extraTowels: false, crib: false }` — a designer's 8-item guess, not a tenant catalog.
- `gVip: false, gEmailPref: true, gMkt: true`.
- `href: '#'` on stay rows 2 & 3.

**6. `localStorage('vyrobel:refund:last')` — a cross-page design device, ALREADY REJECTED.** `refundAmt()` (script:621-624) reads a key written by `Operator Refund.html` and read by ~10 design files. `OperatorOwnerDetail.php:111` explicitly omits *"its localStorage refund netting"*. Do the same. (Refunds themselves ARE real — `payments` type=`refund`/status=`succeeded`, `FolioTotals.php:36-37` — just not via this channel.)

**7. THE PAUSED BANNER — already rejected by name.** `OperatorOwnerDetail.php:101-102`: *"the 'Workspace paused — read-only until 5 Oct' billing banner: subscription/billing has no backend; porting it would fabricate a system status."* `subscriptions.status` genuinely has `'paused'`, which makes it *tempting* — but zero code reads it, there's no read-only enforcement, no date column, no billing screen. **Don't reopen a closed decision.**

**8. UNBACKED UI CLAIMS (verified false today).** *"Shown to housekeeping & front desk"* — neither `HousekeepingBoard` nor `FrontDesk` reads guest prefs/tags. *"Flags arrivals & the front desk for extra care"* — `grep -rn "is_vip" app/ resources/views/` → **0 hits outside the model**. The columns/toggles can be real; the promised downstream effects do not exist. Either soften the copy or build the effect.

**9. `nationality` IS NOT RESIDENCE.** The design renders "Ireland" as a location and "Dublin, Ireland" as an editable *City / country*. `guests.nationality` is `char(2)` — a nationality code. Conflating them is a data-modelling error, not just a missing column.

## BUILD-READINESS VERDICT

**Build-readiness: ~55% ✅ build-now · ~20% 🔨 small additive backend · ~15% 📋 don't-build · ~10% ⬜ other-module.** TIER1-SPEC's **MOSTLY-READY** rating holds up — this is one of the healthier Wave 2 designs, and unusually its core arithmetic actually reconciles (1023+1120+1797 = 3940; 3+5+6 = 14; 3940÷14 = 281), which Wave 1's designs did not.

**Recommended approach: BUILD NOW, after one decision and one prerequisite.**

**⛔ Decide first (blocks the headline number): is Lifetime Value BILLED or COLLECTED?** The design is self-contradictory (it nets refunds off — collected — while including €701 of unpaid charges — billed). Both sources are real and named (`Σ FolioTotals::for($f)['charges_minor']` vs `['paid_minor']`); the SQL is trivial and the *definition* is the actual deliverable. Recommendation: **BILLED**, labelled "Lifetime billed", with the real open-folio balance beside it — which is what the design's own layout (hero + open-folio pill) already implies. Record it in `docs/DECISIONS.md` and put it in a shared `App\Support\Guest\GuestStats` helper so the Guests-list "Lifetime" column can't drift from the profile.

**⛔ Second decision (smaller, but a first): does the operator surface render guest email/phone?** No built screen does today — `FrontDesk` and `InboxThread` are name-only by explicit comment. The capability is real (encrypted casts decrypt server-side; `$hidden` doesn't block Blade). TIER1-SPEC says "contact per policy" and **the policy doesn't exist**. A CRM is the defensible place to say yes — but say it deliberately, in DECISIONS, because the modal also *writes* those fields.

**Prerequisite:** build the **Guests list** (`/app/guests`, TIER1-SPEC:81 = READY) first — this page's breadcrumb, nav item, and "All reservations"-style back-links all depend on it, and both screens share the `GuestStats` helper.

**Then the build is mostly assembly**, because the backend is genuinely strong: `Guest` (+ `is_vip`, `risk_flag`, `marketing_consent`), `Reservation` by `lead_guest_id`, `FolioTotals`, `Note`/`HasNotes`, `Tag`/`HasTags`, `MessageThread.guest_id` are all real, and `InboxThread::guestCard()` already contains the stay-history derivation to lift. Additive backend is small and honest: **2 columns** (`guests.city`, a contact-preference field), **1 relation** (`Guest::reservations()`), **1 helper** (`GuestStats`), **1 controller** (`GuestActionController` — update / addNote / toggleTag), **1 nav key**. Reuse `tags` for the preference chips — do **not** invent a `guest_preferences` table (convention #6).

**Omit outright:** the entire Latest review panel (no reviews module — reflow the left column), the paused/read-only banner, the localStorage refund netting, and the Property Switcher (cross-cutting; solve once, elsewhere). The first three are **already-closed decisions** — `OperatorOwnerDetail.php:101-111` rejects the banner and the localStorage netting *by name*. Don't reopen them.

**Correct the design where it lies:** "Completed" → **"Checked out"**; "In house" → **"Checked in"**; "Booking.com" → **"OTA"**. All three via the existing `FormatsOperatorData` helpers — one vocabulary across the app.

**Flag for product, don't build here:** `Guest::anonymize()` is fully built and certified but reachable from **no UI in the entire app** — the guest profile is its obvious home, and the design omits it. Related: `notes` attached to a guest are not redacted by `anonymize()`, so operator free-text can survive a GDPR erase. Both deserve a DECISIONS entry. Also worth raising: `guest_documents` + ID verification (`verification_status` enum) are **already real** — the brief guessed they'd need a guest portal; they don't. The design simply doesn't use them.

**Biggest risk:** not the code — it's shipping the LTV number before deciding what it means, and letting the profile and the list compute it differently.
