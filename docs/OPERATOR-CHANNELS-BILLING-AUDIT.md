# Operator Channels + Billing — exact-match audit (PHASE 1, read-only)

**Pages:** `/app/channels` (NEW — no route today) + `/app/billing` (`OperatorBilling` — a paywall today, not the mgmt view)
**Wireframes:** `Operator Channels.html` · `Operator Billing.html` (dc-runtime bundles — decoded)
**Date:** 2026-07-30 · **Status:** PHASE 1 complete; awaiting the OTA-gating + pricing-model decisions.

---

## 0 · The honesty headline

Both wireframes depict **capabilities the app does not yet have the accounts/backing for**, and both are the
right honest-gating case:
- **Channels is 100% OTA sync — account-gated.** No real OTA/Channex integration runs (`ChannexClient` throws
  without an API key; `services.channex.fake` defaults true; the endpoint shape is "UNVERIFIED against live
  Channex"). Local `connect()`/`disconnect()` are real STATE toggles, but **nothing ever reaches a real OTA.**
  → Match the design, gate the OTA honestly, NEVER a fake "Synced"/import/sync-activity. The **Direct** channel
  (Vyrobel's own storefront, `reservations.source='direct'`) is real.
- **Billing's active-subscription detail is almost entirely unbacked.** `Subscription` has real STATE only
  (status/trial/period); there is **no per-unit quantity, no card/payment-method model, no subscription-invoice
  model** (the `Invoice` model is the guest folio invoice), **no VAT amount**, and prices are flat placeholders
  (`config/billing.php`: Essential €49 / Pro €99). The wireframe's "6 units × €9.00 · €66.42 incl 23% VAT ·
  Visa ····4242 · Paid" is **entirely unbacked today.** → Build the real STATE + real computed FIGURES
  (placeholder-flagged), honest-gate the CHARGE/card/paid-invoices, and build the REAL pause/cancel state
  machine (the mandate's core — it drives the cross-cutting read-only mode).

---

## 1 · Reuse map

| Concern | Reuse |
|---|---|
| Shell + read-only banner | `<x-operator.shell>` (banner already renders from `OperatorWorkspace`) |
| The read-only gate | `App\Support\Operator\OperatorWorkspace` (reads `organizations.paused_until`) — **the pause writes it** |
| Subscription state | `Subscription` (status/plan/trial/period), `SubscriptionStatus`, `Organization::billingStatus()`, `currentSubscription()` |
| Plan/prices | `Plan` (`price_minor`), `config/billing.php` (placeholder tiers), `BillingService::catalog()` |
| Stripe test-mode | `services.stripe.fake` (default true) → `FakeStripeClient`; `StripeSafetyGuard`; `PayoutEngineMode` = the copyable live/test resolver (no real charge until production + live key) |
| Billing details | `Organization` `billing_company`/`billing_vat_id`/`billing_email`/`country` + `BillingActionController::saveCompany` |
| Counts | `Unit where is_active` (units); `Channel where status='connected'` (channels); memberships + spatie back-office role (team) |
| Channels | `Channel`/`ChannelMapping`, `ChannelConnectionService` (connect/disconnect — real local state) |
| Booking-mix | `reservations.source` via `PerformanceMetrics` (`sourceNights`) + `OwnerBookingsReader::sourceLabel()` |

---

## 2 · Channels — per-element classification (`/app/channels`, NEW)

| Element | Class | Plan |
|---|---|---|
| The page + nav item | 🔨 BUILD | New `app.channels` route + component; flip the nav "Channels" null → `route('app.channels')` (behind the real `planAllows('channels')` gate — Pro-only) |
| Read-only banner | ✅ REAL | shared shell |
| **Direct channel** | ✅ REAL | a real "Direct" row — Vyrobel's storefront; real `source='direct'` booking count |
| Connected channels list | 🟠 REAL state / HONEST-GATED sync | real `Channel` rows + real `status`/`last_synced_at`; connect/disconnect are real toggles, but the **two-way sync/import is account-gated** (honest "activates with your Channex connection") — never a fake "Synced/Live" that syncs nothing |
| Connect-a-channel flow | 🟠 HONEST-GATED | the design renders (pick marketplace → map → push → import); the action honestly explains real sync needs the Channex account (or a real local connect state-flip + honest note) — NO fabricated import |
| Reconnect flow (token expired) | 🟠 HONEST-GATED | the design renders; "3 bookings waiting to import" / "3 imported" are **REFUSED** (no real OTA bookings, no real import) — honest "reconnect activates with your Channex account" |
| Sync activity log | 🚫 HONEST | real channel domain events (connected/disconnected) if any, else honest-empty — NEVER fabricated "2-way sync / imported" entries |
| OTA bookings / Direct-share / Booking-mix | 🟠 REAL Direct / HONEST OTA | real Direct count (`PerformanceMetrics` sourceNights) + the real OTA count (`source='ota'` — genuinely ~0 without sync) → honest, never a fabricated OTA count |
| "Every direct booking saves ~15%" | 🚫 REFUSED | a fabricated commission-saved figure (no real OTA commission baseline) — omit or state honestly |

## 3 · Billing — per-element classification (`/app/billing`)

| Element | Class | Plan |
|---|---|---|
| Settings sub-nav (General/Team/Billing) | 🔨 BUILD (presentation) | render the tabs (General→`app.settings`, Team→`app.roles`, Billing active) |
| Plan card (tier · status · period · subscribed date) | ✅ REAL | `Subscription` status/plan/`current_period_end`; `SubscriptionStatus::label()` |
| **"N units × €X · €Y/mo incl VAT"** | 🔨 BUILD (Decision B) | **per-unit pricing** (a placeholder per-unit price × the REAL active-unit count × a placeholder VAT rate) — reconciling (#17), flagged placeholder, NO real charge; OR the flat plan price + counts |
| Next invoice date/amount | ✅ REAL (projected) | `current_period_end` + the computed figure — a real projection, never a fake "Paid" |
| Switch-to-yearly / Change plan | 🟠 REAL state / gated | real `plan_id` switch (state) + honest "applies at your next cycle / activates with live billing"; no real charge |
| **Invoices list** | 🚫 HONEST | the real trial (Free €0.00) + honest "invoices appear once live billing is enabled" — **NO fabricated "Paid €66.42" invoice** (no charge ever happened; no subscription-invoice model) |
| What you're paying for (units/team/channels) | ✅ REAL | real counts (`Unit is_active` / memberships+role / `Channel connected`) |
| **Payment method (card ····4242)** | 🚫 HONEST-GATED | no card is stored (hosted Checkout, account-gated) → "Add a payment method (activates with live billing)" — **NEVER a fake ····4242** |
| Billing details (company/VAT/address/email) | ✅ REAL | `Organization` fields + `saveCompany` (Edit works) |
| **Cancel · Pause state machine** | 🔨 BUILD (REAL — the core) | see §4 |
| Test-mode badge | ✅ REAL | mirror `PayoutEngineMode` — "test mode · no real charge until go-live" |

## 4 · The pause/cancel state machine (REAL — drives the cross-cutting read-only mode)

Net-new (nothing writes `paused_until` today; the enum already allows `subscriptions.status='paused'`). A new
`SubscriptionLifecycleService`:
- **Pause** (up to 3 months): sets `organizations.paused_until = now()+N months` **and** `subscriptions.status
  = 'paused'`; audited. → the workspace goes READ-ONLY via the existing `OperatorWorkspace` gate (the banner +
  the certified-write refusal I already built). **Mutation-proven** (paused → new bookings refused).
- **Resume**: clears `paused_until` + status back to `active`.
- **Cancel**: `status='canceled'` + `paused_until = current_period_end` (runs until paid-through, then
  read-only); audited. Owners-lose-access + 90-day export are stated honestly (no destructive delete).
- Teach `SubscriptionStatus` about `paused` (a `paused()` + `label()` branch; `hasFullAccess()` stays true so
  a paused org is read-only IN `/app`, not paywalled — orthogonal, as the gate documents).

## 5 · Guards
#17 the subscription figure reconciles (real unit count × per-unit × VAT); #39 no fabrication (no fake
sync/import/sync-activity/OTA-count/paid-invoice/card/charge); the pause genuinely gates read-only
(mutation-proven, reusing the certified gate); test-mode boundary (`StripeSafetyGuard` — no real charge
outside production); tenant-scoped throughout; `billing.manage`-gated writes (#30).

## 6 · Build plan (two gates)
- **GATE 1 — Channels:** new `app.channels` route + component + blade (exact design); real Direct channel +
  real `Channel` rows/status; honest-gate every OTA flow (connect/reconnect/sync/import — never a fake
  sync/import); real booking-mix (Direct) + honest OTA; flip the nav item. Tests + browser. Commit.
- **GATE 2 — Billing:** extend `OperatorBilling` to the active-subscription mgmt view (plan/units/VAT figure ·
  what-you-pay · billing details real · card+invoices honest-gated · Settings sub-nav) + the REAL
  `SubscriptionLifecycleService` (pause/cancel → read-only, mutation-proven) + teach `SubscriptionStatus`
  about paused. Tests (the figure reconciles; pause → read-only enforced) + browser. Consolidate docs; bump HEAD.

---

## FOUNDER DECISIONS
- **A — Channels OTA:** **honest-gated** (no Channex accounts — recommended; match the design, gate the sync,
  never fake it) vs wire a real Channex integration (only if the founder now has the accounts + credentials).
- **B — Billing pricing model:** **per-unit placeholder** (build it — matches the wireframe + mandate: a
  placeholder per-unit price × the REAL unit count × placeholder VAT, reconciling, no charge; recommended) vs
  keep the existing **flat-tier** (show the real plan price + VAT + the counts, no per-unit line).
- **(mandated, stated — not choices)** invoices + card **honest-gated** (no fabricated paid-invoice/card); the
  **pause/cancel state machine is REAL** and drives read-only (mutation-proven); no real charge until go-live.

## RESOLUTION
_(appended at GATE close)_
