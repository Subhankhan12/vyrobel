# Vyrobel — Systematic Gap Sweep (READ-ONLY diagnosis)

> **✅ BATCH A DONE (2026-07-25)** — the four cheapest wins below are wired + shipped: **P1-1** operator
> nav filter (`687e234`), **P1-2** Statement booking-summary + remittance PDFs, **P2-1** Documents +
> Archive annual-tax PDF, **P2-2** Statement remittance PDF (owner-PDF trio in the GATE 2 commit). All
> link/render only — the built+tested backends were untouched; each new PDF link is `@ownercan(CAP_EXPORTS)`-
> gated (#42, browser-verified 200/application-pdf + view-only 403); suite 945/945 + PHPStan L5 (0) + Pint.

> **✅ BATCH B DONE (2026-07-26)** — the three buildable analytics features are shipped, all REAL (never
> fabricated, #39): **P2-4** the by-unit statement split (reconciles to the net to the cent, #17 — `dbf07d3`);
> **P2-3** owner per-unit analytics (revenue · nightly rate · best-performer · 6-month NET · 12-month
> occupancy · channel mix — `1e1269f`); **P2-5** Insights lead-time / DOW / on-the-books via a NEW SHARED
> `BookingCadence` read by both operator + owner Insights (GATE 3 commit). A flagged, demo-only seed gives the
> demo a meaningful real shape (median lead 42 days). The recommendation/pricing engine + per-element "vs last
> year" deltas stay REFUSED (#39). Suite green + PHPStan L5 (0) + Pint; browser-verified vs the wireframes.

> **✅ BATCH C DONE (2026-07-26)** — operator operations, wiring EXISTING guarded services (never raw
> writes): **P2-6** housekeeping assign (a real Housekeeping-dept picker → `HousekeepingService::assign`,
> `housekeeping.manage` — `a728fcc`); **P2-7** reservation **move** (via `AssignmentService::assignUnit`, same-
> type free units) + **add-charge** (`postCharge` #17) + the **on-desk payment money-gate** — idempotency
> (a double-submit records ONCE, mutation-proven) + a Cash/Bank/Card selector (card = the deferred Stripe seam)
> — `b6bd623`. **Correction:** on-desk CASH payment was ALREADY built; the real gap was idempotency + move +
> charge. **P2-8** settings: **Tax & fees editing** now real (bounded, `properties.manage`); Invoice-numbering +
> Branding scoped to a focused follow-up (honest "Soon"). Suite green + PHPStan L5 (0) + Pint; browser-verified.

**Date:** 2026-07-25 · **Method:** cross-referenced `docs/PROJECT-STATE.md` + `docs/DECISIONS.md` +
`docs/design/DIVERGENCE-REGISTER.md` (the per-page wireframe→app map, authored in this session's design pass)
+ `docs/AUDIT-REPORT.md`; grepped every shell/nav + every route + every "Soon"/stub string; **browser-probed
navs + health-swept all routes as multiple roles** (Hotel Manager, owner primary, accountant sub-user).
**No code changed — diagnosis only.**

**Context that makes this fast + trustworthy:** the design-fidelity pass (this session) already diffed all ~76
app pages against their wireframes and classified every "Soon"/deferred/fabricated/account-gated item per page;
the owner-profile investigation+build just fixed one instance of each gap class (dead nav link, buried page,
sub-user 404, stale "Soon" stubs). This sweep confirms which of those classes remain elsewhere.

---

## 1. Executive summary

**Honest completeness read:** the app is **genuinely deep and ~90%+ complete on the buildable-in-a-vacuum
core.** Almost nothing is *broken* — every route in every realm returns 200 (or a *correct* 403 for a gated
role), and the recurring "200-but-blank/dead" class (the 4 unstyled pages, the owner-statement `opacity:0`
hero, the owner Security dead-link) has been found + fixed in prior passes. What remains is three tidy
buckets: **(a) a small set of "Soon" stubs whose backend now EXISTS** — cheap link-only wins, the highest
leverage; **(b) one nav-render bug** (operator role-gated items shown as misleading "Coming soon"); **(c)
buildable analytics/feature depth**; and **(d) deliberate, honest account-gated deferrals** (Channex /
WhatsApp / SMS / KYC / live-Stripe / passkeys) that should NOT be built now.

### Gaps by type

| Type | Count | Notes |
|---|---|---|
| dead-link / misleading-nav | **1** | operator shell shows role-gated pages (Roles/Settings/Billing) as "Coming soon" |
| stub-should-be-real (backend EXISTS, unwired) | **4** | Booking-summary PDF · annual-tax PDF on Documents+Archive · remittance PDF on statement · (operator nav filter) |
| unreachable / orphaned page | **0** | every route is nav-linked or reachable from a flow (group-booking←Reservations, email-previews←Email-templates) |
| missing-page (wireframe, no page) | **~4** | guest Help · guest Email-preferences (minor); operator Group-booking has a page; a few guest peripherals folded into other pages |
| role/sub-user-broken | **0** | the owner sub-user 404 is fixed; all sub-user/role 403s are *correct* gating, no 500s |
| broken-render (200-but-blank) | **0 new** | prior passes fixed the 4 unstyled pages + statement opacity; none found this sweep |
| stub-buildable (real feature, on existing foundations) | **~8** | owner per-unit analytics · statement by-unit · housekeeping assignment · front-desk room-moves/on-desk-pay · insights lead-time/pace · operator Settings sub-editors |
| account-gated (honest "Soon", NOT now) | **~9** | Channels/Channex · WhatsApp/OTA inbox · SMS · guest KYC · live-Stripe charge/payout · passkeys/OAuth/login-approval |

### Top 10 — build these next (highest value + genuinely buildable)

1. **[P1·XS] Operator nav: hide role-gated items instead of "Coming soon".** A Hotel Manager sees *Roles*,
   *Settings*, *Billing* as disabled "Coming soon" — they exist + work for admins; the role just lacks
   permission. The owner shell already does this right (an `array_filter`); the operator shell doesn't.
2. **[P1·XS] Link the real Booking-summary PDF on the Statement page.** `OwnerBookingSummaryPdfController` +
   `OwnerBookingSummaryDocument` + route `owner.statements.bookings` + a passing test all EXIST — the page
   just renders "Booking summary · Soon".
3. **[P2·XS] Link the real Annual-tax-summary PDF on Documents + Archive.** `owner.tax.annual` works (real
   `OwnerAnnualTaxDocument`, linked from Tax Center) — but Documents + Archive say "Tax summary · Soon" with a
   stale comment claiming it "does not exist".
4. **[P2·S] Link the real Remittance PDF on the Statement page** (route `owner.payouts.remittance` exists;
   the statement already resolves its matched payout) — currently "Remittance advice · Soon".
5. **[P2·M] Owner per-unit analytics** (Unit Detail / My Units "Soon": per-unit revenue, nightly rate,
   best-performer, occupancy trend) — buildable on the existing `PerformanceMetrics` + `owner_ledger.unit_id`.
6. **[P2·M] Owner Statement "By unit" split** (currently an honest "Not yet available") — derivable from
   `owner_ledger.unit_id`, reconciling to the same figures.
7. **[P2·M] Operator/Owner Insights "Lead time / Booking pace · Soon"** — the real booking data
   (`reservations.created_at` vs `check_in`) exists; a real lead-time distribution + pace is computable.
8. **[P2·M] Operator Housekeeping assignment** ("Assign · Soon") — assign a real staff member (the
   `StaffRoster` / active-membership set already exists from Team Schedule).
9. **[P2·M] Operator Front Desk room-moves + on-desk payment** ("planned") — `AssignmentService.move()` and
   `FolioService.recordPayment()` both exist; this is wiring existing services to the front-desk UI.
10. **[P3·S] Guest Help page** (wireframe `Guest Help.html` exists; no `/guest/help` route) — a static honest
    FAQ + "message the property", mirroring the owner Help page.

### Suggested build order (highest leverage first)

- **Batch A — cheap unwired-link wins (hours, near-zero risk):** #1 operator nav filter · #2 Booking-summary
  PDF link · #3 annual-tax PDF links · #4 remittance PDF link. *All backends exist + are tested; this is
  link/render only — same class as the owner "Soon" stubs just fixed.*
- **Batch B — buildable analytics depth (per-item, gated like the owner-profile build):** #5 owner per-unit
  analytics · #6 statement by-unit · #7 insights lead-time/pace. *All on the existing `PerformanceMetrics` /
  ledger; honest real aggregates only (#39).*
- **Batch C — buildable operator ops:** #8 housekeeping assignment · #9 front-desk room-moves/on-desk-pay ·
  operator Settings sub-editors (VAT/branding/invoice-numbering). *Wire existing services (`AssignmentService`,
  `FolioService`, `StaffRoster`).*
- **Do NOT build now (account-gated, honest "Soon"):** Channels/Channex · WhatsApp/OTA inbox · SMS · guest
  KYC · live-Stripe charge/payout · passkeys/OAuth. *These wait on external accounts (Subhan's track).*

---

## 2. Findings by priority

### P0 — broken on a used path
**None.** No dead page, no 500, no blank render was found in any realm. (The historical P0/P1-class render
bugs — the 4 unstyled operator pages, the owner-statement `opacity:0` hero — are already fixed.)

### P1 — misleading / stubbed-but-real on a used path

**P1-1 · Operator nav shows role-gated pages as "Coming soon"** — *dead-link / misleading-nav.* **✅ DONE (`687e234`).**
- **WHERE:** operator · sidebar · `resources/views/components/operator/shell.blade.php` (render lines 86–109).
- **WHAT:** the shell has no `array_filter` (the owner shell has one at `owner/shell.blade.php:59`), so a
  `null`-route item — which is how the array encodes "role lacks the permission" — falls into the `@else`
  branch and renders as a disabled **"Coming soon"** span. Browser-verified as **Hotel Manager**: *Roles &
  access*, *Settings*, *Billing* all show as "Coming soon" (and the route correctly 403s). They are NOT coming
  soon — they exist and work for an admin (Property Owner). This is the exact class of the owner "Security"
  dead-link just fixed.
- **BACKEND REALITY:** **real-backend-exists** — the pages work; only the nav render is wrong.
- **WIREFRAME:** n/a (a render bug). **EFFORT/VALUE:** XS / high (every non-admin operator sees it).
- **FIX:** add the owner-shell `array_filter` (drop `null`-route role-gated items) to the operator shell,
  keeping only the genuinely account-gated `channels` item as its "Pro"/"Soon" placeholder.

**P1-2 · Statement page renders the real Booking-summary PDF as "Soon"** — *stub-should-be-real.* **✅ DONE (GATE 2).**
- **WHERE:** owner · `/owner/statements/{id}` · `resources/views/livewire/owner/statement.blade.php:123–126`
  ("Booking summary · Soon"); docstring line 12 says it "stays Soon".
- **WHAT:** `OwnerBookingSummaryPdfController::download` + `Support\Pdf\OwnerBookingSummaryDocument` + route
  **`owner.statements.bookings`** (`/statements/{statement}/bookings`) + **a passing test**
  (`OwnerBookingSummaryPdfTest`) all EXIST — the page just doesn't link them.
- **BACKEND REALITY:** **real-backend-exists (built + routed + tested), unwired.** **WIREFRAME:** `Owner
  Booking Summary PDF.html`. **EFFORT/VALUE:** XS / medium.
- **FIX:** replace the "Soon" tag with a link to `route('owner.statements.bookings', $statement['id'])` (gated
  `@ownercan(CAP_EXPORTS)` like the statement PDF).

### P2 — missing/stubbed but valuable + buildable

**P2-1 · Documents + Archive show the real Annual-tax PDF as "Soon"** — *stub-should-be-real (stale).* **✅ DONE (GATE 2).**
- **WHERE:** owner · `resources/views/livewire/owner/documents.blade.php:71` + `archive.blade.php:110`
  ("Tax summary · Soon"); both carry a **stale comment** asserting the "Owner Annual Tax Summary PDF does not
  exist."
- **WHAT:** it DOES exist now — route **`owner.tax.annual`** (`/owner/tax/{year}/summary.pdf`),
  `OwnerTaxController::downloadAnnual` → real `OwnerAnnualTaxDocument` via `OwnerTaxService::annualSummary`,
  already linked + working on the **Tax Center** page. Built after these pages, so they never got the link.
- **BACKEND REALITY:** **real-backend-exists, unwired.** **EFFORT/VALUE:** XS / low-medium.
- **FIX:** link `route('owner.tax.annual', $year)` from Documents + Archive (drop the stale comment).

**P2-2 · Statement page renders the real Remittance PDF as "Soon"** — *stub-should-be-real.* **✅ DONE (GATE 2).**
- **WHERE:** owner · `statement.blade.php:116–119` ("Remittance advice · Soon").
- **WHAT:** route **`owner.payouts.remittance`** (`/owner/payouts/{payout}/remittance`) exists + is real (used
  on the Payouts page). The statement component already resolves its matched `$payout`, so it can link the
  remittance for that payout when one exists.
- **BACKEND REALITY:** **real-backend-exists**, needs the matched-payout id (already in scope). **EFFORT:** S.
- **FIX:** when `$payout` is present, link `route('owner.payouts.remittance', $payout['id'])`; else keep the
  honest empty state.

**P2-3 · Owner per-unit analytics ("Soon" across Unit Detail + My Units)** — *stub-buildable.* **✅ DONE (GATE 2 · `1e1269f`).**
- **WHERE:** owner · `/owner/units`, `/owner/units/{unit}` — `units.blade.php` (13 "Soon" strings),
  `unit-detail` (per-unit revenue / nightly rate / best-performer / occupancy trend deferred).
- **WHAT:** the wireframes show per-unit revenue, ranked best-performer, occupancy trend. Currently honest
  "Soon"/omitted (#39 — no fabrication).
- **BACKEND REALITY:** **buildable** on the existing `Support\Analytics\PerformanceMetrics` + `MetricSet`
  (already scopes owner-units) + `owner_ledger.unit_id`. Real aggregates only. **EFFORT/VALUE:** M / medium.

**P2-4 · Owner Statement "By unit" split** — *stub-buildable.* **✅ DONE (GATE 1 · `dbf07d3`).**
- **WHERE:** owner · `statement.blade.php:197–213` (honest "Not yet available", with decorative skeleton).
- **WHAT:** a per-unit gross/net/occupancy split of the statement. **BACKEND REALITY:** **buildable** — the
  blade itself notes it's "derivable from `owner_ledger.unit_id`". Reconciles to the same figures.
  **EFFORT/VALUE:** M / medium.

**P2-5 · Insights "Lead time / Booking pace · Soon"** (operator + owner) — *stub-buildable.* **✅ DONE (GATE 3, via the shared `BookingCadence` — lead-time / DOW / on-the-books; per-element "vs LY" refused #39).**
- **WHERE:** `operator/insights.blade.php`, owner `insights.blade.php` (honest "Soon").
- **WHAT:** lead-time distribution + booking pace vs prior period. **BACKEND REALITY:** **buildable** — the
  real data (`reservations.created_at` → `check_in`) exists; a lead-time histogram + pace figure is computable
  without fabrication. **EFFORT/VALUE:** M / medium.

**P2-6 · Housekeeping assignment ("Assign · Soon")** — *stub-buildable.* **✅ DONE (`a728fcc`).**
- **WHERE:** operator · `/app/housekeeping` · `housekeeping-board.blade.php` (Assign "Soon", no assignee).
- **WHAT:** assign a cleaning task to a real staff member. **BACKEND REALITY:** **buildable** — the active
  staff set exists (`StaffRoster` = active `Membership` ∩ back-office role, built for Team Schedule); an
  `assigned_to` on the housekeeping task + a guarded assign action. **EFFORT/VALUE:** M / medium.

**P2-7 · Front Desk room-moves + on-desk payment ("planned")** — *stub-buildable.* **✅ DONE (`b6bd623` — move + add-charge + payment idempotency/selector; cash was already built).**
- **WHERE:** operator · `/app/front-desk` · `front-desk.blade.php` (honest "room moves & on-desk payment are
  planned; check-in/out are live").
- **WHAT:** move a reservation to another unit + take a payment at the desk. **BACKEND REALITY:** **buildable**
  — `AssignmentService.move()` (the single guarded inventory write path) and `FolioService.recordPayment()`
  both EXIST; this wires them to the front-desk UI (POST→controller→service). **EFFORT/VALUE:** M / medium.

**P2-8 · Operator Settings sub-editors (VAT / branding / invoice-numbering / notification-routing · "Soon")**
— *stub-buildable.*
- **WHERE:** operator · `/app/settings` · `operator-settings.blade.php` (read-only VAT tile "Editing · Soon";
  Branding / Invoice numbering / Notification routing "Soon").
- **WHAT:** editable tax rate, branding, invoice-number scheme. **BACKEND REALITY:** **buildable** — the
  `property_policies` / `organizations` config rows exist (the Wave-4 backed Settings writes some of them);
  each editor is a bounded, real write. **EFFORT/VALUE:** M-L / medium (per sub-editor).

### P3 — nice-to-have / genuinely account-gated

**P3-1 · Guest Help + Guest Email-preferences pages missing** — *missing-page (minor).*
- **WHERE:** guest · no `/guest/help`, no guest email-preferences route (wireframes `Guest Help.html`, `Guest
  Email Preferences.html` exist). Guest *Documents* / *Add-ons* are folded into Payments/Receipt + Manage
  Booking (not separate gaps). **BACKEND REALITY:** Help = **buildable** (a static FAQ, mirror the owner Help
  page); email-preferences = **buildable** (a guest `NotificationPreferences`-style store) or honest defer.
  **EFFORT/VALUE:** S / low.

**P3-2 · Storefront media + descriptions ("coming soon")** — *account-gated-ish / content.*
- **WHERE:** public · `/book/{slug}` + room detail (placeholder room photos; "room descriptions coming soon";
  amenities "NOT YET LISTED"; location map "coming soon"). **BACKEND REALITY:** the media pipeline
  (`HasPhotos` / secure upload) EXISTS — this is a **content/data** gap (no real photos/descriptions seeded),
  not a code gap. Real operator uploads would fill it. **EFFORT/VALUE:** M / medium (a media/content pass).

**P3-3 · Owner Security "Soon" cards** — *account-gated (honest).*
- passkeys/WebAuthn · trusted-locations/login-approval · connected-apps OAuth · full sign-in history ·
  new-device email-alerts · session-timeout/theme. All honest "Soon" on the just-built Security page. **Do not
  build now** (external providers / net-new subsystems).

**P3-4 · Account-gated integrations (honest "Soon", Subhan's track)** — see §6.

---

## 3. Per-realm rollup

- **OPERATOR (`/app`) — very complete.** 27 pages render for a Hotel Manager; Roles/Settings/Billing correctly
  403 at the route but are **mislabeled "Coming soon" in the nav (P1-1)**. Channels is honestly account-gated
  (Channex + a Pro feature-gate). Group-booking + email-previews + notification-preferences are reachable
  (not orphaned). Buildable depth: front-desk room-moves/on-desk-pay, housekeeping assignment, Settings
  sub-editors, insights lead-time. **State: complete + healthy; one nav bug + buildable ops depth.**
- **OWNER (`/owner`) — very complete (and just hardened).** All 24 nav items are live routes (Security lit up,
  Profile rebuilt this session). The shell correctly **filters** capability-gated items, so sub-users see a
  clean nav + get correct 403s on direct hits (no sub-user 404s remain — verified as the accountant). Cheap
  wins: Booking-summary + annual-tax + remittance PDFs shown as "Soon" despite existing (P1-2/P2-1/P2-2).
  Buildable depth: per-unit analytics, statement by-unit. **State: complete + healthy; a few stale "Soon"
  PDF stubs + buildable analytics.**
- **GUEST (`/guest`) — complete on the built surface.** Nav (Trips/Payments/Wallet/Messages/Saved/Account) is
  all live routes; every guest page renders (diffed in the design pass). Missing peripheral pages: Help +
  Email-preferences (minor, P3-1). Honest deferrals: card charge (Stripe), ID verification (KYC), wallet
  loyalty/referral, SMS waitlist alerts — all account-gated. **State: complete for what's built; peripherals
  + account-gated tail remain.**
- **PUBLIC / ONBOARDING (`/book`, `/signup`, billing) — complete + honest.** The storefront + booking engine +
  signup wizard + paywall + checkout all work end-to-end (design pass + audit verified). Gaps are **content**
  (placeholder photos/descriptions, P3-2) and **account-gated** (live card charge = pay-on-arrival honest
  shell). No dead nav (the public shell is minimal). **State: complete; media/content + live-charge remain.**

---

## 4. Real-backend-exists, JUST UNWIRED (the cheapest wins)

These are a **link or a render tweak** away from working — the backend is built (often tested). Highest
leverage, near-zero risk. *(Same class as the owner Settings "Soon" stubs already fixed.)*

| Gap | The existing backend | The one change |
|---|---|---|
| Operator nav "Coming soon" for Roles/Settings/Billing (P1-1) | the pages exist + work for admins; the owner shell's `array_filter` already solves it | add that filter to `operator/shell.blade.php` |
| Statement "Booking summary · Soon" (P1-2) | `OwnerBookingSummaryPdfController` + route `owner.statements.bookings` + `OwnerBookingSummaryPdfTest` (all exist) | link the route instead of the Soon tag |
| Documents + Archive "Tax summary · Soon" (P2-1) | route `owner.tax.annual` → real `OwnerAnnualTaxDocument` (linked from Tax Center) | link it (drop the stale "does not exist" comment) |
| Statement "Remittance advice · Soon" (P2-2) | route `owner.payouts.remittance` (real, used on Payouts); the statement already resolves `$payout` | link it when a matched payout exists |

---

## 5. Buildable features (real, on existing foundations)

Genuine features worth an owner-profile-style *investigate → gated build*, each grounded in existing
services/data — **real aggregates / real writes only, never fabrication**:

| Feature | Wireframe | Backend reality (foundation to reuse) | Effort / Value |
|---|---|---|---|
| Owner per-unit analytics (revenue / rate / best-performer / occupancy trend) | Owner Unit Detail, My Units | `PerformanceMetrics`+`MetricSet` (owner-unit scope) + `owner_ledger.unit_id` | M / med |
| Owner Statement "By unit" split | Owner Statement | `owner_ledger.unit_id` (blade already says it's derivable) | M / med |
| Insights lead-time / booking-pace (operator + owner) | Operator/Owner Insights | `reservations.created_at`→`check_in`; real distribution + pace | M / med |
| Housekeeping assignment | Operator Housekeeping | `StaffRoster` (active membership ∩ back-office role) + an `assigned_to` | M / med |
| Front-desk room-moves + on-desk payment | Operator Front Desk | `AssignmentService.move()` + `FolioService.recordPayment()` (both exist) | M / med |
| Operator Settings sub-editors (VAT / branding / invoice-numbering / notification-routing) | Operator (Property) Settings | `property_policies` / `organizations` config (Wave-4 backed writes) | M-L / med |
| Guest Help (+ Email-preferences) page | Guest Help / Email Preferences | mirror owner Help (static FAQ) + a guest prefs store | S / low |
| Reservation-rack per-unit Gantt | Operator Calendar | KIND-3 (needs per-unit reservation spans the grid doesn't fetch) — a data-shape change | L / med |

---

## 6. Genuinely account-gated (honest "Soon" — do NOT build now)

These wait on an external provider/account (Subhan's parallel track); the app already presents each as an
honest experience shell, never a fake:

- **Channels / Channex live OTA sync** (operator `channels` nav = account-gated + a Pro feature-gate).
- **WhatsApp / OTA inbox channels** (the inbox is Direct-only; the deferred `DeferredChannel` skeletons).
- **SMS** — waitlist alerts, notification SMS column, SMS-2FA, phone verification.
- **Guest ID verification / KYC** (guest check-in "ID check — coming soon").
- **Live Stripe** — guest card charge / Apple Pay (pay-on-arrival shell), owner payout **execution** (Connect
  + KYC), subscription live charge. All test-mode-first; the live-key flip is Subhan's.
- **Owner Security "Soon" cards** — passkeys/WebAuthn, trusted-locations/login-approval, connected-apps OAuth,
  full sign-in history, new-device email-alerts, session-timeout/theme.
- **Production email cutover** (Postmark DNS + token) — code-complete, Subhan's cutover.

---

## 7. Coverage + honesty note

**Swept:** every shell/nav in all four realms (operator/owner/guest/public); every GET route (operator +
owner top-level, cross-referenced to nav for orphans — none); the full "Soon"/stub string inventory across
`resources/views`; the `docs/design/DIVERGENCE-REGISTER.md` per-page classification (the wireframe→page map).
**Browser-probed** as **Hotel Manager** (operator nav + a 30-route health sweep: 27×200, 3 correct-403) and
as the **accountant sub-user** (owner nav filtered correctly + a 24-route health sweep: 17×200, 7 correct-403,
no breaks) — confirming the sub-user 404 class is gone and no page 500s for a valid role.

**Honestly NOT done:** per-pixel re-diff (the design pass already did that this session); driving the
un-seeded operator roles (Front Desk, Housekeeping) as their own logins (no demo user exists — noted, same
caveat as the audit); exhaustive guest/public re-walk (relied on the design-pass per-page record). No
destructive or external actions taken; **no code changed** — this report is the only artifact.
