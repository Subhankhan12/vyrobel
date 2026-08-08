# Vyrobel — Wireframe-Parity Sweep (all 124 wireframes)

**Date:** 2026-08-08 · **Commit swept:** `99021b8` (working tree clean) · **Mode:** READ-ONLY on application code — no code, tests, migrations or seeds were changed, no bookings/refunds/payouts executed, no email sent · **Deliverable:** this report + `docs/WIREFRAME-PARITY-FIX-PLAN-2026-08-08.md`

---

## 0. VERDICT

> ✅ **UPDATE 2026-08-08 — all five HIGH findings are FIXED, verified and pushed (§2b).** The MEDIUM waves (§4) remain open. §5 refusals and §6 extra work were preserved untouched.

> **Vyrobel is materially more honest than its own wireframes. The money reconciles, the privacy perimeter holds, and nothing fabricated leaked into the product. What fails is COPY THAT ASSERTS BEHAVIOUR THE CODE DOES NOT IMPLEMENT.**

Five HIGH findings and a long tail of "the page says X, the code does Y". Two of the five are structural (a data-destroying write and a missing production trigger); the other three are a short-circuit, an anti-drift violation, and a compliance gap. **Everything in §5 (refusals) and §6 (extra work) is CORRECT and must not be "fixed".**

### Coverage

| Realm | Pages | FAITHFUL | MINOR-GAPS | MATERIAL-GAPS | NOT-VERIFIABLE |
|---|---:|---:|---:|---:|---:|
| Operator (incl. email, billing, auth-adjacent) | 53 | 7 | 39 | 7 | 0 |
| Owner (incl. PDFs) | 31 | 8 | 18 | 4 | 1 |
| Guest (incl. PDFs) | 28 | 8 | 18 | 2 | 0 |
| Public storefront + auth + system states | 12 | 0 | 11 | 1 | 0 |
| **TOTAL** | **124** | **23** | **86** | **14** | **1** |

*(An interim scoreboard circulated during the sweep said 84/16; the authoritative per-page count is **86 MINOR / 14 MATERIAL**. Full matrix in §8.)*

---

## 1. SCOPE + METHOD

- **All 124 wireframes** in `docs/design/raw/` were **decoded from source by this pass** (`JSON.parse` of the `__bundler/template` block → the `<x-dc>` body) and **diffed against the pre-existing `/tmp/audit` decodes: 124/124 byte-identical**. Nothing downstream trusted an inherited artifact.
- The wireframes additionally declare **66 modal/panel states** via `data-screen-label` (Operator Front Desk alone has 6). True surface count ≈ **190**, all in scope.
- **Mapping:** all 124 → live routes/roles, **zero unmapped, zero phantom**. 121 browser-reachable; 3 genuinely unreachable read-only (see §7).
- **Capture:** 112 HTML + 112 full-page screenshots + 9 real PDFs, taken with real authenticated sessions through real middleware, across 4 auth contexts (operator `demo-admin`, owner `demo-owner`, guest `demo.traveller`, unauthenticated).
- **Analysis:** 14 parallel analysts, one per coherent domain batch, each working dual-method — decoded wireframe (spec) × captured DOM/screenshot × code read (route → controller/Livewire → Blade → service → model).
- **Verification:** **every HIGH and MEDIUM was independently re-verified against the code by the lead before entering this report — 40 verified, 0 retracted.**

### Method caveats that changed conclusions

- **`partials/count-up.blade.php` animates every KPI/hero figure 0→value over 800 ms at full decimal precision.** Screenshots therefore capture mid-animation numbers (observed: Agreement `17.99%` vs a true `18.00%`; Reports `€7,076.83` vs `€7,080.00`). Two analysts independently caught this and correctly declined to report it as a money defect. **All numeric claims in this report come from captured HTML or the database, never from a PNG.**
- One apparent product defect — a dead login button — was investigated and proved to be **stale browser state** (no pointer event reached the DOM at all). Not reported as a defect.

---

## 2. THE FOUR CLEAN GLOBAL CHECKS

| Check | Method | Result |
|---|---|---|
| **No fabricated prototype data leaked** | 16 distinctive invented values probed across all 112 captures (`4242`, `Marco Reyes`, `Marisol Vaz`, `Villa Perla`, `Casa Verde`, `Apt 204`, `WELCOME10`, `Synced 3 min`, `in line`, `Sofia Almeida`, `98,710`, `84%`, …) | **0 hits** — invariant #39 holds globally |
| **No rendering failures** | regex sweep for raw Blade directives, unrendered `{{ $var }}`, `SQLSTATE`/stack traces/`Whoops` | **0** across 112 captures |
| **Console** | per-page listeners during the browser pass | **3 errors total**, all the expected non-200s (Channels 403, two reversal-PDF 404s) |
| **#28 privacy perimeter** | searched every capture for the real seeded guest emails/phones | **0** in the owner realm, **0** on the public storefront; operator exposure confined to exactly **Guests CRM + Guest Profile**; guest sees only their own |

Three non-200s were confirmed **correct by design**: Channels 403 (`EnsurePlanFeature:channels`, renders a branded "Not in your plan" page, and the nav correctly renders it as a disabled `Pro`-tagged span with no `href`), and the Credit Note / Refund Receipt PDFs 404 via `GuestDocumentController::ownedWithRefund()` — *"no reversal document without a real reversal"* — with zero refunds in the seed.

---

## 2b. WAVE 1 — ALL FIVE HIGH FINDINGS RESOLVED (2026-08-08)

> **All 5 HIGH are FIXED, verified two ways (browser + an independent DB/figure re-read) and pushed — one per gate, each with a regression test proven to FAIL without its fix.** The MEDIUM waves (§4) remain open.

| # | Finding | Commit | Regression test | Verified |
|---|---|---|---|---|
| HIGH-1 | Cancellation JSON clobbered on save | `ecf3685` | `PropertyPolicyJsonPreservationTest` (4) | `{"type":"flexible","free_until_days":2}` survives a save; the window renders again |
| HIGH-2 | Owner attribution had no production trigger | `165dea2` | `OwnerAttributionTriggerTest` (7) | attributed **€5,980.00** == folio room revenue on owner units **€5,980.00**; backfill candidates **0** |
| HIGH-3 | Owners page short-circuited to "Paid" / €0.00 | `6312df2` | `OperatorOwnersPayableStateTest` (3) | old logic → "Paid" + €0.00 KPI; new → the real **€885.60** outstanding |
| HIGH-4 | Two disagreeing room-revenue figures | `123bf89` | `OperatorReportRevenueSourceTest` (3) | Revenue panel **€7,080.00** == Top-units Σ **€7,080.00** (was €11,560 vs €7,080) |
| HIGH-5 | Marketing email with no unsubscribe | `bca3679` | `MarketingUnsubscribeTest` (6) | signed link works logged-out → `marketing_consent` false → send refuses; `List-Unsubscribe` present on marketing, absent on transactional |

**Method that mattered.** Every regression test was run against a stashed working tree first. HIGH-3's original test **passed without the fix** — it asserted the row's money figure, which prints from the statement regardless of pay status — and had to be re-aimed at the pay-status signal. A test that cannot fail guards nothing.

### ⚠️ Two corrections to this report's own figures

1. **HIGH-2's "€1,680.00 unattributed" (§3) measured the wrong thing.** The missing trigger was real and is fixed, but that figure was not the gap it appeared to be. After the fix, attributed revenue equals folio room revenue on owner units **exactly** (€5,980.00 == €5,980.00) and the backfill finds **0 candidates**. The difference came from owner reservations that have **no folio at all** — **15 of 24** on a fresh seed, created by `DemoSeeder::seedAnalyticsHistory` through `createBooking` without posting one. Those bookings have nothing to attribute, which is the **same root cause as HIGH-4**, not a second gap. Wiring attribution to `reservation.confirmed` (the obvious fix, and what §3 suggested) would have been actively harmful: it would have banked €0 and latched `liveVersion` permanently. The shipped trigger fires **after the folio is posted** and refuses to latch a zero.
2. **HIGH-4's "`/app/portfolio` disagrees with `/app/reports`" is NOT the same defect, and was deliberately left alone.** Portfolio reads `PerformanceMetrics`, which **pro-rates `price_minor` across in-range nights**. A folio carries no per-night split and cannot be pro-rated, and the ADR/RevPAR identities depend on that pro-rating. The two are different bases *by design*, both real and both labelled — forcing them to agree would have broken the metric layer. Only the two figures **on one screen** were unified.

---

## 2c. WAVE 2 — ALL 23 FALSE-COPY ITEMS + THE 3 STRUCTURAL MEDIUMS RESOLVED (2026-08-09)

> **WAVE 2 is COMPLETE.** The sweep's headline finding — *"the code is honest, the copy is not"* — is closed:
> every sentence in §4 either states what the code does, or is gone.

| Gate | Items | Commit |
|---|---|---|
| S1 | Guest wallet: no transaction, no lock, unused UNIQUE key | `00a9fe5` |
| S2 | Reservation notes write-only (a guest's check-in note reached nobody) | `47cf1ff` |
| S3 | "Switch to yearly" silently upgraded Essential → Pro | `cb4dec0` |
| Wrong figures | #17 "Revenue today" org-wide · #21 "Latest payout" was the oldest | `8deb066` |
| Mislabelled figures | #11 "This month" wasn't · #12 rate-vs-ADR · #14 add-on price | `8437a93` |
| Anti-drift copy | #3 payout frequency · #5 "All 12 months" · #8 two refund ETAs | `599a452` |
| Unenforced claims | #2 · #6 · #10 · #13 · #20 · #22 | `f930dda` |
| Prototype fiction | #4 · #7 · #9 · #15 · #19 | `babbb74` |
| Unwired controls | #1 · #16 · #18 · #23 | `b44bb64` |

**Two items are COPY-ONLY by founder decision (2026-08-09)** — the behaviour deliberately unchanged:
**#6** (binding the operator's direct rate write to the owner's floor/ceiling is a shift of control between
the parties) and **#10** (whether the Essential team limit should BIND belongs with the open billing
decision). Both now *describe* reality precisely; neither pretends to enforce it.

### ⚠️ Corrections to this report, found while fixing

1. **#6 was more nuanced than recorded.** `RateProposalService` genuinely DOES enforce the guardrail — mode,
   ±% tolerance AND floor/ceiling — but only on a PROPOSED rate. Only `RateActionController`'s direct write
   ignores it. The promise was true of one path and false of the other, not false outright.
2. **#20 had a SECOND copy** of "reconciles to your statements" in the hero sub-label, missed by the first
   (case-sensitive) pass and caught in the browser.
3. **#16 was not a missing seed** but an unsaved-model trap: `new MaintenanceSetting` carries no SCHEMA
   defaults, so the real 4/8/48/168h targets never applied.

### ⚠️ THREE PRE-EXISTING TESTS ASSERTED THE FABRICATIONS THEY EXISTED TO PREVENT

`OwnerDocumentsViewTest` demanded `assertSee('All 12 months')`; `OwnerHelpTest` demanded
`assertSee('All systems operational')` and its own comment called that "HONEST"; `OwnerMyUnitsViewTest`
depended on the page defaulting to the wrong month. **A green suite was actively holding the false copy in
place** — the same lesson as the five READ-found security holes (#26–#30), on a different axis. All three
were strengthened (they now demand the true copy AND forbid the false), never relaxed.

---

## 2d. WAVE 3 — IN PROGRESS (2026-08-09): 3 gates done, and the named list was largely STALE

**Verify-first paid for itself: 4 of the 5 "known named items" were already fixed or were never gaps** —
the corrupted date literal (polish pass), the `Team &amp; access` double-escape (renders correctly), the
Agreement raw-JSON leak (not reproducible), and the Statement-of-Account PDF (**already its own document**
since the 2026-08-07 audit: 4-tile KPI band + activity table, sharing `FolioDocument::data` with the receipt
so the two cannot drift, #17). **None were re-fixed.**

**Eight genuine gaps fixed** across `270806f`, `51c3f9f`, `35dc05f`, `f36356b`: the commission rate written three ways
(4 call sites, two on one page) · a finished stay advertising an upcoming check-in · pending add-on rows
rendering BLANK (`add_ons` read vs `add_on_ids` written) · the Payments overview hiding refunds · invented
arrival hours (#39) · two Insights figures missing their unit · two REAL expiries never stated (the
60-minute reset link, and the invite's hardcoded "7 days" beside a real date).

**Two items were RECLASSIFIED as refusals-to-preserve, not gaps:** the 404's "popular pages" (no analytics
exist to back "popular"; and the copy is deliberately identical for missing-vs-not-yours, because
distinguishing them leaks another org's records) and the 500's "support reference" (no correlation id is
generated, so any reference shown could not be looked up).

⚠️ **WAVE 3 IS NOT CLOSED.** The bulk of §4/§8's MINOR rows remain — see the fix plan's WAVE 3 section for
what is done vs open. **Two open items are NOT WAVE 3 and must be flagged, not folded in:** the login
workspace picker (`active_organization_id` is read and never written) and password-reset sibling-session
termination — both touch auth/isolation.

**Suite at the documented baseline throughout: 1758/1765, non-green exactly 7.**

---

## 3. THE FIVE HIGH FINDINGS

*(As found. Each is now RESOLVED — see §2b for the fix, commit and verified figures.)*

### HIGH-1 · ✅ RESOLVED (`ecf3685`) · Saving Stay Policies destroys the free-cancellation window
**Page:** Operator Settings / Property Settings (`/app/settings`) · **Location:** `app/Http/Controllers/Operator/SettingsActionController.php:96-97` + `app/Services/SettingsService.php:66-72`

```php
if ($request->filled('cancellation_tier')) {
    $data['cancellation'] = ['tier' => $data['cancellation_tier']];   // whole-array REPLACE
}
```

`SettingsService::updatePolicies()` does `$policy->fill($changes)->save()`, and `fill()` on a JSON-cast column **replaces the column wholesale**.

- **Measured:** live DB holds `{"type":"flexible","free_until_days":2}` → after one save becomes `{"tier":"flexible"}`.
- **Always fires:** the tier control is a radio group with a checked default (`operator-settings.blade.php:104-105`), so `cancellation_tier` is submitted on **every** save of that card — not only when the tier is touched.
- **Impact:** `CancellationPolicy::freeUntil()` keys on `array_key_exists('free_until_days', …)` (`app/Support/Reservations/CancellationPolicy.php:40-45`) → returns `null` → **`isRefundable()` returns FALSE for every reservation at that property**. The guest-facing "free cancellation until X" disappears from the booking-confirmation email, the guest reservation page and the storefront; the storefront's advertised window drops to 0.
- **And the value it writes instead is inert:** a repo-wide grep for `cancellation['tier']` returns exactly one consumer — `app/Livewire/Operator/OperatorSettings.php:169`, the form redisplaying its own radio.
- **Fix direction:** MERGE the key into the existing array, never replace the column. Mirror any existing partial-JSON update pattern rather than `fill()`.
- **Invariant to preserve:** archive-not-destroy; the policy JSON is the single source `CancellationPolicy` reads.

### HIGH-2 · ✅ RESOLVED (`165dea2`) · Owner revenue attribution has no production trigger
**Page:** Owner Reports (symptom surface) · **Location:** `app/Services/OwnerAttributionService.php:63` (`attributeReservation`), `app/Providers/AppServiceProvider.php:389-401`

- **Callers of `attributeReservation()`:** `DemoSeeder` (5) and `tests/` (~60). **Zero in `app/`.** The only wired owner-ledger listener is `AdjustOwnerLedgerOnRefund` (refunds only). No scheduled command touches attribution.
- **Measured live:** booked room revenue on the owner's own units (2026) **€7,660.00** vs attributed to `owner_ledger` **€5,980.00** → **€1,680.00 unattributed**. ⚠️ **This figure was WRONG — see §2b correction 1** (it measured folio-less seed bookings, not an attribution gap). Owner Reports therefore feeds its analytics half from all 20 reservation lines and its money half from the 10 that happen to have ledger rows.
- **Impact in production (worse than demo, because the seeder fakes the trigger):** a new booking raises occupancy, nights and ADR while **owner earnings, statements, payouts and the annual tax summary stay at zero indefinitely.**
- ⚠️ **Already documented and still open:** `docs/WAVE2-INVENTORY.md:1068` states verbatim *"Attribution isn't even auto-wired: grep for `attributeReservation` finds only `DemoSeeder.php:366-367` and tests — no listener, no job, no service call."* This sweep re-surfaced and **measured** it; it is not a new discovery.
- **Fix direction:** wire `attributeReservation` to the real reservation-confirmed/checked-out lifecycle event (the `AdjustOwnerLedgerOnRefund` listener is the adjacent correct pattern), plus a backfill for existing rows.
- **Invariant to preserve:** #17 (owner earnings must reconcile to the ledger); append-only ledger; `PeriodLockGuard`.

### HIGH-3 · ✅ RESOLVED (`6312df2`) · Operator Owners reports €0.00 payable while money is genuinely pending
**Page:** `/app/owners` · **Location:** `app/Livewire/Operator/OperatorOwners.php:134` (payouts loaded grouped by `owner_id` at `:51`)

```php
if ((string) $latest->status === 'paid' || $payouts->contains(fn (Payout $p): bool => (string) $p->status === 'paid')) {
```

`$payouts` is **all** of that owner's payouts, so **any historical paid payout short-circuits every later branch** (hold, needs-review, ready).

- **Impact:** a Finalized-unpaid statement is labelled **"Paid"**; "Needs attention" reads **0**; the payout-run panel reads **"€0.00 · 0 owners scheduled"** — while `/app/owners/{owner}` shows **€885.60 pending** on the same rows.
- **Fix direction:** resolve the payout **per statement**. `payouts.owner_statement_id` already exists (`app/Models/Payout.php:34`, migration `2026_07_03_000110:24`) and is unused here.
- **Invariant to preserve:** #17; the on-hold branch must remain driven by a real blocker (tax hold / no payout method), never invented.

### HIGH-4 · ✅ RESOLVED (`123bf89`) · Two disagreeing "room revenue" figures on one screen
**Pages:** `/app/reports` (and `/app/portfolio`) · **Location:** `app/Support/Reports/OperatorReportData.php` — `topUnits()` (~:169-192) sums `reservation_units.price_minor`; `revenue()` (~:114-135) sums `folio_items type='room'`

- **Measured (2026, Seaside):** `reservation_units` basis **€11,560.00** vs folio basis **€7,080.00** → **€4,480.00 / 63% overstatement**, on the same screen; `/app/portfolio` shows €11,560 for the same year and property that `/app/reports` reports as €7,080. ⚠️ **The portfolio half of this is NOT the same defect and was deliberately not "fixed" — see §2b correction 2** (Portfolio pro-rates by night; a folio cannot be pro-rated). **The PDF export inherits it.**
- **Why they diverge in demo:** `DemoSeeder::seedAnalyticsHistory` (~`:1275`) creates 18 backdated reservations through `AssignmentService::createBooking` alone, which posts **no folio room charge**, whereas every real booking entry point does (`ReservationCreateController:216`, `BookingEngineService:192`, `GroupBookingService:123`). **The dual-basis code is nonetheless the defect** — nothing enforces the reconciliation `PerformanceMetrics` asserts in its own docblock (`:31-33`), so any future path that skips a folio post reopens it silently.
- **Fix direction:** ONE shared revenue source for the concept (the project's own anti-drift rule), plus a reconciliation assertion/test.
- **Invariant to preserve:** #17; the anti-drift "one definition per concept" family.

### HIGH-5 · ✅ RESOLVED (`bca3679`) · Marketing email can be sent, with no way to unsubscribe
**Location:** `app/Http/Controllers/Operator/GuestBulkActionController.php:64-78`; `app/Support/Guest/GuestMarketing.php`

- **No unsubscribe exists anywhere:** no route, no link in any template under `resources/views/emails/`, no `List-Unsubscribe` header. (A repo grep for `unsubscribe|List-Unsubscribe|opt-out` returns only an unrelated tenancy comment.)
- **A live marketing send path does exist:** the operator ticks "this is a marketing message" → `GuestMarketing::canReceive($guest)` → `Mail::to($email)->send(...)`. **`canReceive` has exactly one caller — that controller.**
- **The only opt-out** is a toggle inside a *claimed* guest account; a guest who never claimed one has no route to withdraw consent.
- **Correct as-is:** booking confirmations, refunds, payment reminders and pre-arrival are **service messages** and legitimately sit outside a consent gate. The exposure is the **marketing** path (operator blast + the winback template, whose trigger is deferred). Review requests sit on the line.
- **Fix direction:** a signed, no-login unsubscribe route writing the existing `guests.marketing_consent` flag, plus a footer link + `List-Unsubscribe` header on marketing-class mail.
- **Invariant to preserve:** #28 (the token must not leak identity); transactional mail must remain ungated.

---

## 4. THE MEDIUMS

### 4a · "The page says X / the code does Y" — false copy

Every row verified at the cited line.

| Surface | The page says | The code does | Location |
|---|---|---|---|
| Owner Payout Method | "Changing the default holds payouts 48h as a security check." (with a ✓ tick) | `setDefault()` flips `is_default` + records events. No hold, no delay, no `held_at`. Grep for any 48h implementation: empty. The adjacent bullet *is* honestly qualified; this one is not | `owner/payout-method.blade.php:280` vs `OwnerPayoutMethodService::setDefault()` |
| Owner Payout Method vs Payouts | "Every 2 weeks" / "Twice monthly" — same value, same POST | Engine fires the Nth business day from the **1st AND the 16th** = 24×/yr, not 26. Canonical `OwnerPayoutPreference::frequencyLabel()` says "Twice monthly" and **neither Blade uses it** | `payout-method.blade.php:173` · `payouts.blade.php:274` · `OwnerPayoutPreference.php:82` · `PayoutSchedule.php:36-37` |
| Operator Email Templates | Toggling a template flashes "…is now off — it won't send." | True for 6 of 7. **verify-email sends unconditionally** — no `EmailTemplateResolver::enabled` check | `GuestAccountService.php:85` vs the 6 gated call sites |
| Owner Help | "All systems operational · Payouts, statements and your portal are running normally" (green dot) | Hardcoded string; the component passes no status variable and **no health check exists**. Would read green during a payout outage. *(The docblock at `:28` calls it a deliberate static line — but that disclosure never reaches the reader)* | `owner/help.blade.php:127-128` |
| Owner Documents | "All 12 months · one PDF for your accountant" | The PDF it links to states *"6 statements · 1 Apr – 30 Sep 2026"*. On the one document most likely to reach an accountant | `owner/documents.blade.php:112` |
| Owner Rates | "Every change needs your sign-off before it goes live." | True only of the proposal path. `RateActionController` writes the same rate plan with **no `RateGuardrail` check** — an operator can breach the owner's floor/ceiling with no sign-off | `owner/rates.blade.php:140` vs `RateActionController` |
| Payment Reminder email | "Secure payment · Visa, Mastercard & **Apple Pay**" | The destination page it links to explicitly refuses Apple Pay ("No card is stored… never touch Vyrobel"). The only surface in the product promising it | `emails/payment-reminder.blade.php:53` vs `guest/payments.blade.php:80` |
| Cancellation vs Refund emails | "3–5 business days" / "5–10 business days" — for the same refund | Two hardcoded literals in two files; neither derived from the gateway | `CancellationMail.php:77` · `RefundConfirmationMail.php:70` |
| Operator Search | "search a reservation by reference (**VYR-…**)" | Every reference generated is `R-XXXXXXXX`. `VYR-` appears **nowhere else** in the codebase — inherited verbatim from prototype mock data | `operator-search.blade.php:49` vs `AssignmentService.php:925` |
| Operator Billing | "Team members 7 · **unlimited**" | Hardcoded literal. Org is Essential, whose configured limit is **5** — and the limit is **enforced nowhere** | `billing.blade.php:121` vs `config/billing.php` |
| Owner My Units | Unit cards + Best-performer labelled "This month" | `resolveMonth()` falls back to `max(check_in)` = **2026-12-10** while today is 2026-08-08. **Dashboard says unit A1 19%, My Units says 0%** for the same unit | `OwnerMyUnits.php:129-142` |
| Owner My Units | "Nightly rate €140.00" | The achieved **ADR**, not the configured price. The same card's editor says "Reset to €180.00" — the real rate, already loaded as `base_display` | `OwnerMyUnits.php:249` |
| Owner Onboarding | Card heading "Add your payout account" | Body + CTA on the same card say a payout account is already on file. The `<h2>` is static while everything else branches on `$hasPayout` | `owner/welcome.blade.php:46` |
| Guest Add-ons | "Breakfast · per guest · night · €132.84" | €132.84 is the **whole-stay total**; the unit price is €18.00. The controller computes `unit_price` and the view discards it | `GuestExtrasController.php:53-61` vs `extras.blade.php:61-62` |
| Guest Saved (waitlist) | "If a spot opens we'll email you first." | **No trigger exists.** `WaitlistNotificationMail`'s only caller is the operator preview screen | `guest/saved.blade.php:87` · `GuestSavedController.php:96` |
| Operator Maintenance | Every ticket: "No SLA target set" | Both surfaces fall back to an unsaved `new MaintenanceSetting` (NULL `sla_*`); the row is created only when someone opens Settings, and **no seeder creates it**. Proven: the curl capture has 6× "No SLA target set"; the screenshot taken after Settings was opened shows real targets | `MaintenanceList.php:58` · `MaintenanceTicketDetail.php:96` |
| Operator Dashboard | "Revenue today" beside property-scoped KPIs | `paymentsOn()` applies **no property predicate** while every sibling KPI is `$propertyIds`-scoped. Latent today (all of today's payments are Seaside); over-reports the moment a second property takes a same-day payment | `OperatorDashboard.php:120,421-436` |
| Operator Guests / Guest Profile | Add guest · Message · Add tag rendered for any `guests.view` holder | Those routes require `guests.manage` → Marketing Manager / Guest Support Agent see controls that 403. (Erase **is** correctly `@can`-gated, showing the pattern was known.) Server gate holds — **not a bypass**, but it breaks the project's own "gate the CONTROL too" rule | `guests-list.blade.php:84,200,201` |
| Operator Inbox | Composer footnote "The guest replies wherever they booked" | Renders unconditionally, including on **owner** threads where the counterparty never booked anything | `operator-inbox.blade.php:248` |
| Owner Reports | KPI sub-label "Reconciles to your statements" | €4,653.60 vs statement Σ €3,690.00 — it reconciles to statements **plus** unstamped ledger. The Tax Center shows €3,690.00 for the same year | `owner/reports.blade.php` KPI sub-label |
| Owner Reports | "Latest payout" | Shows the **oldest** (ordered by a `created_at` the seeder writes identically) | `OwnerReports.php:400` |
| Owner Reports (tax) | Banner "rates haven't been configured… illustrative only" + footnote "Computed from the VAT rates configured for PT" | Contradictory statements on the same card; the footnote is emitted unconditionally although `$vat['is_example']` already distinguishes the states | `owner/reports.blade.php:459` vs `:489` |
| Guest Account | Language preference + hero KPI | `guests.language` is persisted and **read by nothing** — no `App::setLocale`, no mail localisation | `GuestAccountController.php:102-104` |
| Operator Reservation Detail | — | The cancel flow always emails the guest; no notify/suppress toggle although `CancellationMail` really sends | `SendGuestTransactionalEmails.php:141` |

### 4b · The three structural MEDIUMs

1. **Guest wallet is the one money path without concurrency protection.** `GuestWalletService::applyToBooking()` runs the owed-check and `assertSpendable()` **outside** `DB::transaction`; the service contains **zero** `lockForUpdate`; **no caller passes an idempotency key** although `post()` accepts one **and `guest_credit_ledger` carries a UNIQUE index on `idempotency_key`** — the exact backstop H1's refund fix used. The two wallet forms are the **only** guest money forms without `data-guard-submit` (15 views use it). `throttle:12,1` bounds volume, not concurrency. `redeemCode()` has the same check-then-act shape. *(Structural, not raced.)* — `GuestWalletService.php:108-130,163-169`
2. **Reservation notes are write-only.** `reservations.notes_internal` and `notes_guest` are written by `ReservationCreateController:204-205` and `GuestCheckinController:124`, and rendered by **no view anywhere** (`grep resources/` → empty). A guest typing *"please leave a cot"* at online check-in is never shown to any operator.
3. **"Switch to yearly" silently upgrades the tier.** `billing.blade.php:59` hardcodes `['plan' => 'pro']`, so an **Essential** org clicking it lands on a **Pro** yearly checkout; the quoted saving matches neither model's destination price. *(Separable from the open billing decision — see the fix plan.)*

### 4c · Other MEDIUMs
- **Guest Email Preferences:** only "Offers & news" is controllable. Pre-arrival, payment-reminder and review-request emails send with **no preference check of any kind**; offers cannot be separated from win-back.
- **Guest Manage Booking:** add-on pending rows render blank — controller reads `payload['add_ons']`, service writes `add_on_ids` (`GuestManageController.php:141` vs `GuestChangeRequestService.php:77`).
- **Vyrobel Login:** no workspace picker. `SetTenantForWebRequest:41-54` reads `session('active_organization_id')` and **nothing in the repo ever writes it** — a two-org user is silently pinned to the first membership.
- **Vyrobel Reset Password:** password reset does not terminate sibling sessions (`User::changePasswordTo` rotates the remember token only); the capability exists in `ActiveSessions` and is used elsewhere.
- **Guest Villa Detail:** no "Things to know" — check-in/out times and the cancellation window are real columns, absent pre-booking.
- **Operator Maintenance Costs:** org-wide, not property-scoped (the sibling board is), and no period selector — the report is all-time.
- **Operator Guests / Notifications / Reservations:** unbounded `->get()` — no pagination anywhere.
- **Operator Portfolio:** roll-up sums `roomRevenueMinor` across properties and formats with the **first** property's currency (latent; both demo properties are EUR) — the cross-currency sum #17 forbids.
- **Owner Bookings:** "Net to you" excludes expense debits (`reservation_id NULL`) — €4,903.60 vs the true ledger net €4,653.60, under a label that doesn't say so.
- **Owner Booking Detail:** timeline renders check-in as *upcoming* directly above a *completed* check-out (terminal status not consulted).
- **Owner Security:** no in-portal pending login-approval queue (approval is email-link only) though `login_approvals` stores the rows.
- **Owner Help:** the entire support-ticket system is absent (honestly redirected to messaging) — a product-scope decision, not a defect.

---

## 5. REFUSALS TO PRESERVE — ⚠️ do NOT "fix" these into fabrication

The **40 ratified divergences** in `docs/design/DIVERGENCE-REGISTER.md` all held. The sweep confirmed these additional refusals as **correct engineering**:

**Money-safety (highest stakes — a fake here is dangerous, not merely dishonest)**
- No micro-deposit verification and **no bank "Verified" badge** — there is no verification column and no deposit rail.
- **"Withdraw early" stays disabled** — there is no owner-initiated transfer rail; "In transit" shows a genuine €0.
- **No SEPA file export** on Payout Run; **no operator debit account**; Stripe Connect disbursement honestly disabled with the KYC reason.
- Payout Run's disclosure that marking a transfer paid **records** a transfer already made — it does not send one.
- **No cancellation / no-show fee figure** — no fee engine; a label marked "Soon" plus an explicit "no fee was calculated or charged".
- **No waive-fee modal** on no-show — waiving a fee that was never posted would create a phantom credit.
- **Reversal PDFs 404 without a real reversal** (`ownedWithRefund()`).
- **No fabricated withholding rate**; VAT is informational and labelled "example rates · not for filing · not tax advice"; unconfigured → honest "not configured".

**Privacy (#28 / #33)**
- Guest contact absent from **every** owner surface and from front desk / reservations / inbox / search / refund — the Guests CRM and Guest Profile are the only permitted surfaces.
- **Door code guest-only and window-gated** — proven released inside 48 h and withheld before, on both screen and PDF.
- Guest ID capture stays **`pending`, never "Verified"** — no KYC provider; no OCR fields, no selfie match, no "filed with SEF".
- Co-guests are a **count**, never a name.

**Security (#26 / #30 / #45)**
- **"New role" / "Delete role" refused** — back-office access is anchored to `config('authorization.back_office_roles')`; an arbitrary role would be unreachable or an escalation surface.
- **No raw PAN anywhere** (SAQ A) — hosted Stripe only, on operator checkout, new reservation, group booking and guest checkout.
- Push/SMS notification channels are not merely disabled but **structurally unsubmittable** (no `name` attribute; the controller iterates only `['in_app','email']`).
- Test-recipient locked to the operator's own inbox on email previews.
- SVG logo upload refused (stored-XSS).

**No-fabrication (#39)**
- No fabricated channel sync ("Synced 3 min ago", "N imported") — a sync timestamp prints only when `channels.last_synced_at` is genuinely set.
- No OTA **brand** split anywhere (unmodelled); no per-brand letter badge on the rack.
- No generated narrative / "suggested next step" / smart-pricing suggestions / comp-set benchmark.
- No vs-last-year delta where no prior-year data exists (and the LY series is omitted, not drawn at zero).
- No invented multi-year history, review volume, loyalty tier, referral program, queue position, promo code, welcome credit, or retention promise.
- No auto-approve timer on owner expenses; no fabricated per-unit attention signals.
- Honest empty states everywhere ("Not yet attributed", "No reviews yet", "—" for an unknown vendor).

---

## 6. EXTRA WORK TO KEEP — ⚠️ do NOT remove

Real capability beyond the wireframes: real **Approve/Query** expense state machine and **rate-proposal** approve/decline/counter · **unit pause/resume** that genuinely stops bookings · **drag-to-reschedule** through the certified `AssignmentService` · an **IP allowlist that actually enforces** · **trusted devices, login approval, passkeys/WebAuthn** · a real **OAuth2 server with per-app scopes and immediate revoke** · **export watermarking + a real download audit log** · **`CsvSafe` on 49/49** `fputcsv` calls · **branded realm-aware error views** (403/404/419/429/500/503) that never print the exception · real **hosted-Stripe pay-by-card** on guest payments, webhook-settled · **honest sold-out redirect** instead of a faked confirmation · **all-or-nothing group booking** · **server-side staff-only technician guards** (not just pickers) · the **shared `RosterWeek`** so grid and PDF cannot drift · per-statement **Remittance PDFs** · the **"Not payable" carry-forward** card · guest **archive-not-destroy** deletion with a real Undo · the **`$asOf` default** on Pre-arrival so preview state == send state.

---

## 7. NOT-VERIFIABLE — ⚠️ to confirm, NOT passed

| Item | Why | How to close |
|---|---|---|
| **Owner Booking Summary PDF** | **Manifest mis-map by this sweep**: captured `/owner/bookings/{booking}/confirmation` (per-booking) instead of the monthly `BS-YYYY-MM` at `/owner/statements/{statement}/bookings`. Fetching the correct one calls `ExportAudit::stamp()`, which writes a row — outside the read-only remit | Capture the correct route in a pass that permits the audit write |
| **Operator Reservation Cancelled / No-show** | The seed contains only `confirmed` + `checked_out`; creating one is a mutation. Classified from Blade state branches + controller code only | Verify on a scratch reservation in a mutating pass |
| **Vyrobel Set Password** (`/invite/{token}`) | `owner_invites` is empty and stores only `token_hash` — **no plaintext token exists** | Verify by issuing a real invite |
| **Vyrobel Paywall (lapsed)** | Demo org is on a real trial/subscription; `/app/billing` renders the management view. Capture is byte-identical to Operator Billing | Verify after flipping an org lapsed |
| **Operator Channels (Pro)** | Pro-plan-gated; demo org is Essential (403). Classified from code + the branded gate page | Verify on a Pro org |
| **All concurrency findings** | **Structural, not raced** — proven by code structure and the absence of locks/keys, not by an observed double-write. Same standard the 2026-08-07 audit applied to its own H1 | Race two processes in a mutating pass |

---

## 8. COVERAGE MATRIX — all 124 pages

Verdict key: **FAITHFUL** = matches · **MINOR-GAPS** = present, small deltas · **MATERIAL-GAPS** = a substantive gap or false statement · `*` = classified from code only (see §7).

### Operator

| Page | Verdict |
|---|---|
| Booking Confirmation Email | MINOR-GAPS |
| Cancellation Email | MINOR-GAPS |
| Email Previews | MINOR-GAPS |
| Operator Billing | MATERIAL-GAPS |
| Operator Calendar | MINOR-GAPS |
| Operator Channels | FAITHFUL* |
| Operator Conversation | MINOR-GAPS |
| Operator Dashboard | MINOR-GAPS |
| Operator Email Editor | MINOR-GAPS |
| Operator Email Templates | MINOR-GAPS |
| Operator Front Desk | MINOR-GAPS |
| Operator Group Booking | FAITHFUL |
| Operator Guest Profile | MINOR-GAPS |
| Operator Guests | MINOR-GAPS |
| Operator Housekeeping | MINOR-GAPS |
| Operator Inbox | MINOR-GAPS |
| Operator Insights | MINOR-GAPS |
| Operator Maintenance | MATERIAL-GAPS |
| Operator Maintenance Costs | MINOR-GAPS |
| Operator Maintenance Settings | FAITHFUL |
| Operator Messages | MINOR-GAPS |
| Operator New Reservation | FAITHFUL |
| Operator Notification Preferences | FAITHFUL |
| Operator Notifications | FAITHFUL |
| Operator Owner Detail | MINOR-GAPS |
| Operator Owners | MATERIAL-GAPS |
| Operator Payout Run | MINOR-GAPS |
| Operator Portfolio | MINOR-GAPS |
| Operator Profile | MINOR-GAPS |
| Operator Property Settings | MATERIAL-GAPS |
| Operator Rates | MINOR-GAPS |
| Operator Refund | MINOR-GAPS |
| Operator Reports | MATERIAL-GAPS |
| Operator Reservation Cancelled | MINOR-GAPS* |
| Operator Reservation Detail | MINOR-GAPS |
| Operator Reservation No-show | MINOR-GAPS* |
| Operator Reservations | MINOR-GAPS |
| Operator Reviews | MINOR-GAPS |
| Operator Roles | MINOR-GAPS |
| Operator Schedule PDF | MINOR-GAPS |
| Operator Search | MINOR-GAPS |
| Operator Security | MINOR-GAPS |
| Operator Settings | MATERIAL-GAPS |
| Operator Team Schedule | MINOR-GAPS |
| Operator Work Order | MINOR-GAPS |
| Owner Invite Email | FAITHFUL |
| Payment Reminder Email | MATERIAL-GAPS |
| Pre-arrival Email | MINOR-GAPS |
| Refund Confirmation Email | MINOR-GAPS |
| Review Request Email | MINOR-GAPS |
| Vyrobel Invite Owner | MINOR-GAPS |
| Waitlist Notification Email | MINOR-GAPS |
| Winback Email | MINOR-GAPS |

### Owner

| Page | Verdict |
|---|---|
| Owner Add Unit | FAITHFUL |
| Owner Agreement | MINOR-GAPS |
| Owner Annual Tax Summary PDF | MINOR-GAPS |
| Owner Archive | FAITHFUL |
| Owner Balance | MINOR-GAPS |
| Owner Block Calendar | FAITHFUL |
| Owner Booking Detail | MINOR-GAPS |
| Owner Booking Summary PDF | NOT-VERIFIABLE |
| Owner Bookings | MINOR-GAPS |
| Owner Dashboard | MINOR-GAPS |
| Owner Documents | MINOR-GAPS |
| Owner Expenses | MINOR-GAPS |
| Owner Help | MATERIAL-GAPS |
| Owner Insights | MINOR-GAPS |
| Owner Messages | FAITHFUL |
| Owner My Units | MATERIAL-GAPS |
| Owner Notifications | FAITHFUL |
| Owner Onboarding | MINOR-GAPS |
| Owner Payout Method | MATERIAL-GAPS |
| Owner Payouts | MINOR-GAPS |
| Owner Rates | MINOR-GAPS |
| Owner Remittance Advice PDF | MINOR-GAPS |
| Owner Reports | MATERIAL-GAPS |
| Owner Reviews | FAITHFUL |
| Owner Security | MINOR-GAPS |
| Owner Settings | MINOR-GAPS |
| Owner Statement | MINOR-GAPS |
| Owner Statement PDF | MINOR-GAPS |
| Owner Tax Center | FAITHFUL |
| Owner Team | FAITHFUL |
| Owner Unit Detail | MINOR-GAPS |

### Guest

| Page | Verdict |
|---|---|
| Guest Account | MINOR-GAPS |
| Guest Add-ons | MINOR-GAPS |
| Guest Arrival Info | FAITHFUL |
| Guest Arrival Info PDF | FAITHFUL |
| Guest Check-in | MINOR-GAPS |
| Guest Credit Note PDF | FAITHFUL |
| Guest Credits | MINOR-GAPS |
| Guest Documents | MINOR-GAPS |
| Guest Email Preferences | MATERIAL-GAPS |
| Guest Folio Receipt | MINOR-GAPS |
| Guest Folio Receipt PDF | MINOR-GAPS |
| Guest Help | MINOR-GAPS |
| Guest ID Verification | FAITHFUL |
| Guest Login | MINOR-GAPS |
| Guest Manage Booking | MINOR-GAPS |
| Guest Messages | MINOR-GAPS |
| Guest Payments | MINOR-GAPS |
| Guest Referral Landing | FAITHFUL |
| Guest Refund Receipt PDF | FAITHFUL |
| Guest Reservation | MINOR-GAPS |
| Guest Review | MINOR-GAPS |
| Guest Signup | FAITHFUL |
| Guest Statement of Account PDF | MINOR-GAPS |
| Guest Trips | MINOR-GAPS |
| Guest VAT Invoice PDF | MINOR-GAPS |
| Guest Verify Email | FAITHFUL |
| Guest Waitlist | MATERIAL-GAPS |
| Guest Wishlist | MINOR-GAPS |

### Public + auth

| Page | Verdict |
|---|---|
| Guest Booking | MINOR-GAPS |
| Guest Browse | MINOR-GAPS |
| Guest Checkout | MINOR-GAPS |
| Guest Villa Detail | MINOR-GAPS |
| System States | MINOR-GAPS |
| Verify Email | MATERIAL-GAPS |
| Vyrobel Checkout | MINOR-GAPS |
| Vyrobel Login | MINOR-GAPS |
| Vyrobel Onboarding | MINOR-GAPS |
| Vyrobel Paywall | MINOR-GAPS* |
| Vyrobel Reset Password | MINOR-GAPS |
| Vyrobel Set Password | MINOR-GAPS* |

---

*Artifacts (decoded wireframes, HTML/PDF captures, screenshots, per-batch JSON) were produced in a session scratchpad and are not committed. This report is the durable record.*
