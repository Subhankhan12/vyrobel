# Vyrobel — Operator Portal Deep Parity Audit · COMPLETE FINDINGS REPORT

**Date:** 2026-08-18 · **Branch:** `audit/operator-deep-parity` · **Method:** read-only, report-only
**Evidence log:** `docs/audits/operator-portal-DEEP-parity-2026-08-18.md` (38 dated runs, ~3,000 lines)
**This document:** every issue found, large and small, in one place.

> **Demo integrity, start → end (verified at every run):** `rate_calendar` md5
> **`693e3237b51205e5373e9833b23c8b34`** · 929 rows · 10,920,000 minor · **0** unit overrides ·
> **41** reservations · **0** seasons · 40 folio items · **16** payments · **0** anonymized guests ·
> 8 users / 7 role assignments. **0 lines of code changed. 0 mutations exercised (41 surfaced).**

---

## 1 · EXECUTIVE SUMMARY

| | |
|---|---|
| Pages diffed | **52 of 52**, + 2 data-states (`cancelled`, `no_show`) |
| Denominator | **derived**: 54 GET `/app` URIs − 2 aliases |
| Wireframes cross-checked | **42 of 42** — 0 unmapped in either direction |
| Roles rendered / derived | **4 of 11** rendered · **11 of 11** derived |
| **Security/gating findings** | **7** |
| Frontend gaps (MISSING) | **1** (minor) |
| Backend gaps | **0** |
| Honest refusals confirmed | **34** |
| Inventory corrections | **8** |
| Auditor self-corrections | **16** |
| Missing regression guards | **1** |
| Mutations surfaced / exercised | **41 / 0** |

### The headline

**Every permission gate in the build is present and correct at the route layer.** The findings are not broken
gates — they are (a) pages that carry **no gate at all**, and (b) **controls that advertise a gated action**
without carrying the gate themselves.

**Critically bounded by run 34:** **116 of 116** operator writes are controller-routed, and **0 of 37**
operator Livewire components expose a mutating action. `/livewire/update` therefore carries no operator
mutation. **So Findings 2–7 are misleading UI, not privilege escalation** — the POST still hits a gated route.
**Finding 1 is different in kind:** ungated *reads* of money and PII, with no second gate behind the page.

---

## 2 · THE SEVEN FINDINGS

### ⚠️ FINDING 1 — 25 GET `/app` routes carry no permission gate · **HIGHEST**

**Measured** (run 36), as Housekeeping (4 permissions), ids substituted:

- **25** of 54 GET `/app` routes have no `Authorize:` middleware
- **23** of those return **200**
- **11** render money in page content
- **2** more return a money/security **artifact**
- The 2 that refuse do so for **non-permission** reasons

| Route | Status | What it hands over |
|---|---|---|
| `/app/owners/{owner}` | 200 | **€4,653.60 gross · €2,804.40 commission · €885.60 net · €1,080.00 · statements · payout history — plus the owner's email address** |
| `/app/payouts/run` | 200 | €885.60 · €2,804.40 · €442.80 · €656.00 |
| `/app/reservations` | 200 | €738.00 · €184.50 · €1,082.40 |
| `/app/notifications` | 200 | €332.10 · €721.60 · €467.40 |
| `/app/email-previews/{key}` | 200 | **€540.00 · €124.20 · €664.20 — real booking money inside a "template preview"** |
| `/app/reservations/{reservation}` | 200 | €300.00 · €69.00 · €369.00 folio |
| `/app/maintenance` | 200 | €180.00 · €45.00 · €100.00 |
| `/app/dashboard` | 200 | €113.33 · €100.00 |
| `/app/owners` | 200 | €885.60 |
| `/app/front-desk` | 200 | €140.00 |
| `/app/email-templates/{key}/edit` | 200 | €0.00 |
| `/app/reservations/{r}/folio.pdf` | 200 | **`application/pdf`** — a real folio document |
| `/app/security/export` | 200 | **`application/json`** — a security-events export |
| `/app/calendar` · `/app/rack` · `/app/housekeeping` · `/app/maintenance/{ticket}` · `/app/units` · `/app/profile` · `/app/security` · `/app/notification-preferences` · `/app/email-previews` · `/app/email-templates` | 200 | no money |
| `/app/channels` | **403** | `EnsurePlanFeature` — a **plan** gate, not a permission |
| `/app/inbox/{thread}` | **403** | refused by its **redirect target** (`/app/inbox` is `guests.view`-gated) |

⚠️ **Blast radius:** a missing gate does not discriminate between actors — **all 11 back-office roles** reach
all 25, not Housekeeping alone.

**Two exposures no page-diff surfaced** (found only when routes were measured for *content*, not status):
- `/app/email-previews/{key}` reads as a harmless template preview and is **populated from real bookings**
- `/app/security/export` is a **security-events export** with no permission gate

**Nature:** READ exposure. The WRITE side of these surfaces is correctly gated.
**Status:** recorded, not fixed. **Product question:** which of the 25 *should* carry a gate.

---

### ⚠️ FINDING 5 — the email-template editor renders WRITE controls for every role · **HIGH**

| Route | Gate |
|---|---|
| `GET /app/email-templates` | *** none *** |
| `GET /app/email-templates/{key}/edit` | *** none *** |
| `POST /app/email-templates/{key}` | `Authorize:properties.manage` |
| `POST /app/email-templates/{key}/test` | `Authorize:properties.manage` |
| `POST /app/email-templates/{key}/toggle` | `Authorize:properties.manage` |

**The three writes are correctly gated. The page that offers them is not**, and
`operator-email-editor.blade.php` carries **no `@can` anywhere** (measured, not assumed).

Accountant and **Housekeeping (4 permissions)** both render the booking-confirmation editor with
**`Save template`**, **`Send me a test`**, the merge-tag palette and 3 section toggles.

**Two distinct problems:**
1. **Write controls that invite a 403** — Finding 2's shape, but three controls, on a page reachable by the
   least-privileged role.
2. **A read exposure** — the org's outbound guest email copy (subject, heading, body, merge tags) is legible
   to every back-office role. Not PII, so not a #28 breach; still content no housekeeper needs.

⚠️ **The sibling surface is correctly gated AND tested.** `OperatorEmailTemplatesTest` asserts, verbatim:
*"Front Desk sees NO toggle and NO edit — the UI never offers a write the route would 403 (#30)"*, plus a
separate test proving the save route 403s for Front Desk and writes no row.

⚠️ **And the hidden entry point makes it worse, not better.** Because the list correctly hides the edit link,
the editor *looks* unreachable — but the route has no gate, so a typed URL, bookmark, stale link or
autocomplete lands any of the 11 roles on a page offering two writes. **A gated doorway is not a gated room.**

---

### ⚠️ FINDING 6 — units photo controls gated on the WRONG permission · **HIGH**

`units-list.blade.php:83`

    @if ($canManageRates ?? false)

`UnitsList.php:113` defines that as **`rates.manage`**. The four forms inside post to
`room-types.photos.cover` · `.move` · `.destroy` — all requiring **`units.manage`**.

**Not a missing gate — a gate on the wrong thing, failing in BOTH directions:**

| Role | `rates.manage` | `units.manage` | Effect |
|---|---|---|---|
| Property Owner · Group Admin · Hotel Manager | yes | yes | consistent |
| **Revenue Manager** | **yes** | **no** | ⚠️ **sees the photo controls; every POST 403s** |
| Front Desk · Reservation Agent · Housekeeping · Maintenance · Accountant · Marketing Manager · Guest Support Agent | no | no | consistent |

⚠️ **Exactly one role is affected, and it was never rendered.** PO and Hotel Manager hold both permissions;
Accountant and Housekeeping hold neither. **All four tested roles see a self-consistent page** and were
structurally incapable of revealing this. It was found by *deriving* the permission matrix, not by rendering.

⚠️ **The over-hidden direction is live but latent:** any role granted `units.manage` without `rates.manage`
**cannot see controls it is entitled to use**. No seeded role is in that state today — it is one permission
edit away. **This is the audit's only over-hidden case.**

⚠️ **FIX TRAP:** this is **not** "add a `@can`" — it is **change `rates.manage` → `units.manage`**. A sweep
that only adds guards leaves it wrong.

---

### ⚠️ FINDING 7 — `/app/unit-requests` offers Approve/Decline to roles that cannot use them · **MEDIUM**

    GET  /app/unit-requests              can:units.view
    POST /app/unit-requests/{r}/approve  can:units.manage
    POST /app/unit-requests/{r}/decline  can:units.manage

The view carries **no guard at all** (no `@can`, no component flag), so the buttons render for anyone who can
reach the page. `units.view` is strictly broader than `units.manage`:

| Sees Approve/Decline, POST 403s | Rendered in this audit? |
|---|---|
| Revenue Manager · Front Desk · Maintenance | ⚠️ no |
| **Housekeeping** | ✅ **yes — and the audit still missed it on the page** |

⚠️ **Why the page-diff missed it: `unit_requests` has 0 rows.** With no rows the buttons never render, so the
gate on them was never exercised — the page looked identically well-behaved for all four roles *precisely
because it showed nothing to anyone*. **An empty table conceals a control-gating defect perfectly.**

---

### ⚠️ FINDING 2 — `/app/calendar` `New reservation` without `@can` · **MEDIUM**

A write control rendered without the `reservations.create` gate its route carries. A role lacking the
permission sees the control and receives a 403 on use. `reservation-rack.blade.php` — page ungated, component
computes no capability flag. Independently re-derived from source in run 33, confirming the sweep method.

---

### ⚠️ FINDING 4 — work-order `Add update` offered to roles that cannot add one · **LOW**

`maintenance-ticket-detail.blade.php:56`

    <a href="#wo-composer" class="vy-btn vy-btn--ghost">Add update</a>

**No gate at all**, while the composer it targets is inside `@if ($canManage && ! $readOnly)`. The id it
points at sits on the **Activity card** (line 164), which is ungated — so for Accountant and Housekeeping the
link resolves, scrolls to a read-only timeline, and nothing happens.

Measured: both roles render `Add update` with **zero action buttons and zero forms** on the page.

**Verdict: over-exposed LABEL, not capability.** Nothing becomes writable, no request is issued, no 403 is
invited. It is a **dead affordance**.

---

### ⚠️ FINDING 3 — the schedule drag instruction is shown to a role that cannot drag · **LOW**

`schedule.blade.php:179`

    <span>Drag a shift to move it — or drop it on another person's day to swap.
    @if ($canManage)Click a cell to add or change a shift.@endif</span>

⚠️ **Half the sentence is gated and half is not.** The `@if ($canManage)` wraps *"Click a cell to add or
change a shift"* and leaves *"Drag a shift to move it"* **outside it, in the same span**. Housekeeping is told
to drag; the move route 403s. Bounded by sweep to **one line in one file**.

**Open sub-observation (unproven):** zero `draggable="true"` nodes render for **any** role — the instruction
may describe an interaction that is not wired at all. Recorded as a candidate, not a finding.

---

## 3 · FRONTEND GAP (MISSING)

| # | Item | Detail |
|---|---|---|
| M1 | **`Rebook guest`** on a cancelled reservation | The wireframe (`Operator Reservation Cancelled.html`) offers it. **No `rebook` anywhere** in `app/`, `resources/`, `routes/`, `DECISIONS.md` or the divergence register — so unlike the fee waiver this is **not** a recorded refusal. **MISSING (frontend, minor):** rebooking *is* creating a reservation, which exists and is gated at `/app/reservations/create`. What is absent is the **shortcut from a dead booking to a new one**, not the capability. |

**Backend gaps: 0.** Every capability the operator surface implies exists behind it.

---

## 4 · PER-PAGE DIVERGENCES (all 52 pages)

Buckets: **MATCH** · **DIVERGENCE** (ratified / honest-refusal / minor) · **MISSING** · **EXTRA**.

### 4.1 · Ratified / honest refusals — the build declining to fabricate

These are **correct behaviour**, confirmed against `DECISIONS.md` / `DIVERGENCE-REGISTER.md` or the source.
**None is a defect. None should be "fixed".**

| Page | Prototype showed | Build does | Basis |
|---|---|---|---|
| Front desk · Reservations · Search · Guests | OTA **brands** (`Booking.com`, `Expedia`, `Airbnb`) | real `source` enum, no brand | brand unmodelled (#37) |
| Reviews | `BY CHANNEL — Booking.com 4.8 · Airbnb 4.9` | absent | #37 |
| Channels | `All synced · 12 arrivals` per card | sync half absent | no live channel sync to claim (#37) |
| Notifications | `Expedia disconnected · Channels` row | absent | #37 |
| Inbox | channel facets incl. **WhatsApp 2** | absent | account-gated deferral |
| Reviews | `OVERALL RATING 4.8 **+0.2**` | `4.6`, **no delta** | vs-prior refused (#39) |
| Insights | `OCCUPANCY 77% +2 pts vs last year` | occupancy only | vs-prior renders only where real prior-year data exists (#39) |
| Reports | `REVENUE · SEP €172,900 +9%` | revenue, **no delta** | #39 |
| Guests | `REPEAT GUESTS 31% +4%` | figure, **no delta** | #39 |
| Portfolio | `REVENUE · MONTH €214,600 +9%` | revenue, **no delta** | #39 |
| ⚠️ Reports | **top-owner leaderboard** — named owners + earnings | **absent** | a named-owner earnings ranking is invented data |
| ⚠️ Security | **`Suspicious login blocked`** + city/date/time | **ABSENT** | no sign-in history recorded — **fabricating a break-in is the worst class of fabrication** |
| Security | `Secure my account` CTA | absent | follows the above |
| Email templates | per-card engagement stats (*"72% opened"*) | replaced by *"click tracking isn't collected, so no engagement stats are shown"* | no tracking exists |
| Billing | `Next invoice … to **Visa ····4242**` | date present, **no card** | `stripe_subscription_id` NULL (#41) |
| Billing | `Switch to yearly · save €129,60` | absent | a computed saving on a plan/period pair never billed |
| Reviews | `Request automation` | absent | no review-request automation exists |
| Reviews | canned replies (`Warm thanks`, `Make it right`) | absent | no reply templates modelled |
| ⚠️ **Reservation · no-show** | full **fee-waiver workflow**: `Waive fee…`, `Full fee · €175.00`, `Half · €87.50`, reason chips, *"refund to Expedia VC"* | **`No-show fee · waiver` + `SOON`**, `anyFeeFigure: false` | **no fee engine exists** (`ReservationDetail.php:71`, locked decision 5) |
| **Reservation · cancelled** | `Cancellation fee` | **`Cancellation fee` + `SOON`** | same |
| Group booking · New reservation | `City tax` + **`VAT (10%)`** hardcoded in the prototype's JS | **derived** from the property's real `Tax` rows — **23%** (€300 → €69 → €369) | a hardcoded rate shipped is a **wrong tax** |
| Group booking · New reservation | rail totals a stay with nothing selected | **`—`** until `count > 0` / `unitId` | a component of a total is itself a claim; `€0.00` asserts the stay is free |
| Rates | seasonal apply-panel | absent | **DOCUMENTED DEFERRAL** — engine done, operator UI pending (`DECISIONS.md`) |

⚠️ **The no-show waiver is the strongest single refusal in the audit.** The prototype hands over a specific
number, four reason codes and a payment-rail phrase; the build **names the exact thing it cannot do** and
marks it `SOON` rather than dropping the concept or inventing the figure. A waiver amount is one an operator
would **quote to a guest**.

### 4.2 · Minor divergences (presentation, no defect)

| Page | Item |
|---|---|
| Reservations | month selector `Sep 2025` + `Select` → tabs + search instead |
| Reservations | KPIs `THIS MONTH · 64` / `BOOKED VALUE €18,420` absent — the real total rides the `All 41` tab |
| Reservations | `BALANCES DUE` is a real **column** rather than a KPI (placement) |
| Guests | tabs `All · In-house · Upcoming · VIP` not in the visible control set |
| Inbox | fewer filter chips than the prototype (`All`, `2 Unread` visible) |
| Inbox | `New message` not in the visible set |
| Search | `Recent searches` + `Clear` not in the visible set |
| Settings | prototype's 5 tabs (`General`/`Policies`/`Team access`/`Tax & fees`/`Branding`) → **one long-form page** — ⚠️ **RATIFIED** (`DIVERGENCE-REGISTER` line 10: `/app/settings` is deliberately ONE combined hub) |
| Maintenance settings | preset SLA chips (`Same day`…`1 month`) → numeric inputs `min=1 max=720` (build more expressive) |
| Billing | `€66,42/mo incl. 23% VAT` → **`€49.00/mo`** real plan price; VAT-inclusive label absent |
| Work order | wireframe's `Reassign` via *"Open the rack to reassign"* → a real in-place `/assign` POST |
| Work order | `€310` in the prototype body → no money (costs live on `/app/maintenance/costs`) |

### 4.3 · Data-dependent absences (data, not gaps)

| Page | Absent element | Why |
|---|---|---|
| Front desk · Reservations · Inbox · Reports | `Workspace paused — read-only until 5 Oct` + `Resume now` | a real subscription-paused state (#41); demo org is on an **active** subscription |
| Billing | `Invoices` list with `Paid` + PDF | no real invoices exist — no live charge has run (#41) |
| Billing | `30-day trial 5 Jun – 4 Jul 2026` | this org is past trial, on a real subscription |
| Maintenance costs | `Workspace paused` banner | active subscription |
| Unit requests | request cards + Approve/Decline | **0 rows** — ⚠️ **and this concealed Finding 7** |

### 4.4 · EXTRA — the build exceeding the prototype (enhancements, never defects)

| Page | Extra |
|---|---|
| Reservations | every tab carries a **real count**; adds `Arrival due` and `Cancelled` tabs |
| Reservations | `STAGE` column (shared `ReservationStage`) + per-row `BALANCE DUE` |
| Front desk | `Check in walk-in`, `Collect`, `Cancel`, `Check out` in row-detail panels |
| Maintenance | 7 explicit status states (`All open` · `Open — not started` · `In progress` · `Urgent` · `Awaiting parts` · `Out of order` · `Resolved · 7d`) |
| Maintenance | `Settings` link (`maintenance.manage`) |
| Maintenance settings | **Out-of-order policy** (`block_room_from_sale`, `notify_front_desk`, `require_photo_on_resolve`) |
| Maintenance settings | **Notify me about** — 5 real toggles incl. SLA-breach and daily digest |
| Work order | `Mark awaiting parts`, `Cancel work order` — real lifecycle transitions |
| Reviews | ⚠️ *"Auto-responses and review-gating are coming soon — for now, every response and publish decision is yours."* — **an honest deferral stated in the UI** |
| Insights | richer ranges: `Year to date` · `Last 6 months` · `Summer (Jun–Aug)` · `Q4 · on the books` · `Full year 2026` + custom range |
| Reports | `Jump to month…` with 6 real months; states the basis (*arrivals*) |
| Search | `Everything, in one place` |
| Schedule | real `‹ Last week` / `Next week ›` navigation |
| Security | `Update password` · `Enable two-factor` with rules stated |
| Notifications | real categories incl. `Maintenance`, `Reputation`; per-row `✓` read / `×` dismiss + `Unread` chip |
| Guest profile | `Message`, tag add/remove, **`Erase this guest`** (GDPR `anonymize`) |
| Owner detail | **Rate proposals** panel (#67), **Management agreement**, **Statements** table, **Payouts** |
| Group booking | deposit method incl. **`Card · soon`** with a real explanation (#45) |
| Group booking | rail note: *"Taxes & fees are posted to the real folio on confirm — the authoritative total is the folio."* |
| Cancelled reservation | `CANCELLED AT` timestamp beyond the prototype's chip |

---

## 5 · GATING VERDICTS — mapped to the capability matrix

### 5.1 · The role model

| Role | Perms | Demo account | Rendered in this audit |
|---|---|---|---|
| Property Owner | 49 | `demo-admin@vyrobel-demo.test` | ✅ |
| **Group Admin** | **42** | — | ⚠️ no |
| Hotel Manager | 37 | `demo-staff@vyrobel-demo.test` | ✅ |
| **Front Desk** | **19** | — | ⚠️ no |
| Accountant | 12 | `demo-accountant@vyrobel-demo.test` | ✅ |
| **Revenue Manager** | **10** | — | ⚠️ no |
| **Reservation Agent** | **10** | — | ⚠️ no |
| **Guest Support Agent** | **7** | — | ⚠️ no |
| **Marketing Manager** | **5** | — | ⚠️ no |
| Housekeeping | 4 | `demo-housekeeper@vyrobel-demo.test` | ✅ |
| **Maintenance** | **4** | — | ⚠️ no |

⚠️ **The demo seeds 4 accounts; the back-office allow-list holds 11 roles.** All 11 were **derived** from
`role_has_permissions`; 7 were never rendered because they have no demo account.

⚠️ **PERMISSION COUNT DOES NOT PREDICT REACH.** Marketing Manager holds **5** permissions and reaches **9**
gated pages; Accountant holds **12** and reaches **6**. Maintenance and Housekeeping both hold **4** and reach
**8** and **3**. *"I tested the least-privileged role"* was never a bound on the others.

⚠️ **All 11 roles reach all 25 ungated pages.**

### 5.2 · Correctly gated (verified at the render layer, no mutation)

| Surface | Gate | Evidence |
|---|---|---|
| Front desk — every mutating control (`Walk-in`, `Move`, `Check in`) | `reservations.*` | PO/HM see them; Accountant + Housekeeping see **none** |
| `/app/rates` | `rates.manage` | Housekeeping **403** — page never renders |
| `/app/reservations/{r}/refund` | `payments.refund` | ⚠️ **Hotel Manager 403** — separation of duties |
| `/app/reservations/{r}` | — | HM: lifecycle actions, **no refund**; Accountant: refund actions, **no lifecycle** — a disjoint pair |
| Guest profile `Erase` | `guests.erase` | PO has it; **Hotel Manager renders the page without it** |
| `/app/guests/{guest}` | `guests.view` | ACC + HK **403** |
| Owner detail controls | `users.manage` (Edit owner / Add note) · `rates.manage` (proposals panel) | HM sees proposals only; ACC + HK read-only, **zero POST forms** |
| `/app/maintenance/settings` | `maintenance.manage` | ACC + HK **403** |
| Work order lifecycle | `maintenance.manage` via one `$canManage` | ACC + HK: **zero actions, zero forms** — a genuine read-only view |
| `/app/group-booking` · `/app/reservations/create` | `reservations.create` | ACC + HK **403** |
| `/app/billing/checkout` | `billing.manage` | HM + ACC + HK **403** |
| `/app/owners/invite/preview` | `users.manage` | HM + ACC + HK **403** |
| `/app/schedule/pdf` | `schedule.view` | HK **200** (881 KB real PDF), Accountant **403** |
| Email-templates **list** | `properties.manage` | Front Desk sees **no toggle, no edit** — *and it is tested* |
| `/app/notification-preferences` | *(none — self-only)* | ✅ **RATIFIED** — `DECISIONS.md:6153` |
| `/app/channels` | `EnsurePlanFeature` | **403 for every role incl. Property Owner** on Essential — a **plan** gate, not a bug |

### 5.3 · Over-exposed

Findings **1, 2, 4, 5, 6, 7** (§2).

### 5.4 · Over-hidden

**One, latent:** Finding 6's reverse direction — a role with `units.manage` but not `rates.manage` cannot see
photo controls it is entitled to use. No seeded role is in that state today.

### 5.5 · Server-side enforcement

⚠️ **Every gated write is `UI-gated; server-enforcement unverified-without-mutation`.** Proving a POST is
rejected requires POSTing a gated action — forbidden by this brief. **41 mutations surfaced, 0 exercised.**

---

## 6 · MUTATIONS SURFACED (41) — none exercised

| Endpoint | Writes | Money | Inventory |
|---|---|---|---|
| `POST …/{reservation}/check-in` · `/check-out` | reservation status, stay lifecycle | no | ⚠️ yes |
| `POST …/{reservation}/assign` · `/move` | `reservation_units` via `AssignmentService` | no | ⚠️ **yes** |
| `POST …/{reservation}/cancel` | status → cancelled, `cancelled_at`, reason | no | ⚠️ yes |
| ⚠️ `POST …/{reservation}/refund` | **`FolioService::refundPayment`** — bounded refund (#27) | ⚠️ **REAL** | no |
| `POST …/{reservation}/charge` | folio line (#17) | ⚠️ **yes** | no |
| `POST …/{reservation}/payment` | `payments` row, unique idempotency key | ⚠️ **yes** | no |
| `POST /app/reservations` (create) | reservation + folio + availability | ⚠️ yes | ⚠️ **yes** |
| `POST /app/group-booking` | block of reservations + master folio + deposit | ⚠️ **yes** | ⚠️ **yes** |
| `POST /app/rates/*` | `rate_calendar`, weekend uplift, min-stay | ⚠️ **yes** | no |
| `POST /app/rates/units/*` | `unit_rate_overrides` | ⚠️ **yes** | no |
| `POST /app/housekeeping/status` · `/assign` · `/assign-all` · `/photos` | `HousekeepingTask` state, assignee, media | no | ⚠️ yes (room readiness) |
| `POST …/maintenance/{ticket}/start` | status → `in_progress` | no | no |
| `POST …/maintenance/{ticket}/resolve` | status → resolved (+ optional return-to-sale) | no | ⚠️ **yes** |
| `POST …/maintenance/{ticket}/cancel` · `/assign` · `/notes` · `/parts` · `/photos` | lifecycle, assignee, note, parts, media | no | no |
| `POST /app/maintenance/settings` | 13 settings incl. **`block_room_from_sale`** | no | ⚠️ **policy** |
| `POST /app/guests/{guest}` · `/notes` · `/tags` · `/message` | profile, note, tag, outbound message | no | no |
| ⚠️ `POST …/{guest}/anonymize` | **GDPR erase — strips PII, sets `anonymized_at`** | no | **irreversible** |
| `POST /app/owners/{owner}/update` · `/note` | owner fields, note | no | no |
| ⚠️ `POST …/{owner}/pause-payouts` | halts the payout rail for this owner | ⚠️ **money-adjacent** | no |
| ⚠️ `POST …/{owner}/remove` | removes the owner relationship | ⚠️ **money-adjacent** | no |
| `POST /app/rate-proposals` | a `rate_proposals` row (#67) | no | no |
| `POST /app/unit-requests/{r}/approve` · `/decline` | request state + unit creation | no | ⚠️ yes |
| `POST /app/payouts/run` · `/mark-paid` | **payout run, transfers, ledger stamps** | ⚠️ **REAL** | no |
| `POST /app/settings/*` (basics, policies, invoice, brand, support, taxes, fees, organization) | property + org config | ⚠️ **tax rates** | ⚠️ **status** |
| `POST /app/email-templates/{key}` · `/test` · `/toggle` | template copy; **sends a real email** | no | no |
| `POST /app/inbox/{thread}/reply` · `/assign` · `/close` · `/reopen` · `/snooze` · `/link-reservation` · `/retry` | thread + message state | no | no |
| `POST /app/schedule/assign` · `/leave/approve` · `/leave/deny` | roster shifts, leave decisions | no | no |
| `POST /app/roles/assign` · `/permissions` | **role + permission grants** | no | ⚠️ **authorization** |
| `POST /app/portfolio` (store/update) · `/property/switch` | property CRUD, context | no | ⚠️ yes |
| `POST /app/billing/subscribe` · `/cancel` · `/company` | subscription state | ⚠️ **yes** | no |
| `POST /app/room-types/photos/cover` · `/move` · `/destroy` | media order + deletion | no | no |
| `POST /app/notification-preferences` | self-only preference matrix | no | no |

---

## 7 · INVENTORY CORRECTIONS (8) — the ledger was wrong 8 times

| # | Correction |
|---|---|
| 1 | ⚠️ **A route regex stopped at the dot.** `app/[a-z0-9/_{}-]*` silently truncated `folio.pdf` **and all six** `export.{csv,pdf,xlsx}` routes — hiding the entire export surface. |
| 2 | ⚠️ **Wrong middleware needle.** `can:` is resolved to `Illuminate\Auth\Middleware\Authorize:<perm>` in `route:list --json`; searching for the alias returns *"0 gated"* and is simply wrong. |
| 3 | ⚠️ **A third gate type existed.** Beyond permission (`Authorize:`) and subscription (`EnsureSubscriptionActive`) there is **`EnsurePlanFeature`**, which 403s `/app/channels` for **every** role on Essential. |
| 4 | ⚠️ **`/app/rack` is an alias of `/app/calendar`** — same `ReservationRack::class`, byte-identical render (1,540 chars, identical control set). Never a 50th page. |
| 5 | ⚠️ **`/app/inbox/{thread}` is a redirect**, not a page — it redirects to `/app/inbox?c=`, and its 403 comes from the **target's** gate. |
| 6 | ⚠️ **The denominator was inherited, never derived.** "50" came from a run-1 estimate, then adjusted −1 per alias, so it stayed *plausible* while never being recomputed. Derived properly: **54 GET URIs − 2 aliases = 52**. |
| 7 | ⚠️ **`Operator Work Order.html` had no route in the ledger** — found by accident in run 27; it is the detail behind `maintenance.show`. |
| 8 | ⚠️ **The wireframe→route direction was never swept.** Rule #1 asks for both; 31 runs walked routes→wireframes only. Sweeping the reverse found **5 unmapped wireframes**, two of which are **data-states** (`cancelled`, `no_show`) that a route-driven ledger is structurally blind to. |

---

## 8 · AUDITOR SELF-CORRECTIONS (16) — the instrument was wrong more often than the build

| # | Error | Why it mattered |
|---|---|---|
| 1 | **Phantom controls** — harvested `innerText \|\| aria-label \|\| title` across all elements; hidden buttons yielded labels | manufactured a **false over-exposure** on the front desk. Fixed with `Element.checkVisibility()`. |
| 2 | Truncated route regex (§7.1) | hid the whole export surface |
| 3 | Wrong middleware needle (§7.2) | reported "0 gated" |
| 4 | Missed the third gate type (§7.3) | a 403 looked unexplained |
| 5 | Run-10 filter could not match `Add property` | right conclusion, **broken evidence** — withdrawn |
| 6 | Arithmetic: asserted July 2026 has 8 Fri/Sat nights; it has **9** | a figure stated without counting |
| 7 | Inferred Finding 1 four times before **measuring** it | run 13 replaced 4 revisions of inference with 1 pass of ground truth |
| 8 | ⚠️ **A cap that truncated BY ROLE.** `<main>` **contains the sidebar**, so a 30-item control cap cut Property Owner's `Save changes` (PO has more nav entries) — reading as *"the higher role gets a read-only form"* | **the artefact correlated with the thing being measured** — the most dangerous shape an instrument error can take |
| 9 | ⚠️ **A noise filter ate the signal.** Excluded `vyrobel-demo.test` addresses to drop the operator's own from a #28 read — and thereby removed the **guest's real email** | nearly recorded a phantom PII asymmetry |
| 10 | ⚠️ **The self-check counted the wrong set** — "68 captures" was the whole `.captures/` dir (28 owner-audit + 19 unrelated); the operator audit had **21** | happened **inside the self-check**, in the **flattering** direction |
| 11 | ⚠️ **A button-only harvest is blind to an honest refusal.** Deferrals render as `<div class="vy-defer-item">`, not controls — the read returned `Refund · Record · Add` and saw **none** of the 4 `SOON` items | would have written the no-show waiver up as **MISSING**, under-crediting exactly the honesty the audit exists to verify |
| 12 | ⚠️ **A `{param}` URI is not fetchable** — the ground-truth sweep silently skipped **all 7** parameterised routes and reported a clean total | a skipped row and a clean row look identical in a status table |
| 13 | ⚠️ **Finding 1 carried two numbers** (25 structural vs 13 behavioural) in one document, neither acknowledging the other | the sixth decay instance, introduced *while writing the summary meant to end them* |
| 14 | ⚠️ **Claimed the 7 unrendered roles needed a demo write.** `operatorUser($s, [], '<Role>')` takes the role as a parameter and is already used in **52** files for Front Desk alone | conflated *"unverifiable read-only against the DEMO"* with *"unverifiable"* — filed a non-blocker as the largest open item |
| 15 | ⚠️ **Said the email-templates pass "never asked the same question."** It did — rigorously, with a test | too strong, and unfair to the code |
| 16 | ⚠️ **A per-control probe's `NO` does not generalise to the surface** | one step from writing *"no gating test exists for email templates"*, which is flatly false |

### ⚠️ The meta-pattern: every summary decayed

**Six times** a summary drifted from the evidence beneath it — the RESUME block, the denominator, the
self-check, the capture count, the header (which said *13 runs* when there were 34, listed **3** findings when
there were 7, and asserted *"write routes — no gap"* against Findings 6 and 7), and the tally. **Each was
caught by re-deriving, never by re-reading.**

---

## 9 · THE POSITIVE RESULT — the strongest measurement in the audit

**The operator Livewire surface is read-only by construction.**

| Measure | Result |
|---|---|
| Operator Livewire components exposing a mutating action | **0 of 37** |
| Public methods across **all** components (every realm) | `render` (65) · `mount` (11) · 1 trait hook — **nothing else** |
| Operator writes that are controller-routed | **116 of 116** `<form method="POST">` |
| `wire:click` / `wire:submit` action handlers | **0** |
| `wire:model.live` | 5, all the email editor's **live preview**; persistence is a separate gated POST |
| Harness proven both ways | ✅ the same sweep returned 65 `render` + 11 `mount` |

⚠️ Invariant #30 says *"Authorization: ROUTE, not service"* — a model that is only sound **if every write is
routed**. **Measured: it is.** The architecture and the authorization strategy actually agree.

⚠️ **This is what bounds Findings 2–7 to affordance-level.** It is also **unguarded**: there is no operator
equivalent of `OwnerCapabilityMutationBoundaryTest`. One future `public function save()` in an operator
component silently creates a write path route middleware never sees — and turns every affordance defect above
into a **real bypass**, with nothing failing to announce it.

---

## 10 · TEST COVERAGE OF THE FINDINGS

| Finding | Feature tested? | A test asserting **who may see the control**? |
|---|---|---|
| 1 · owners/{owner} ungated | 3 files | ⚠️ **no** |
| 2 · calendar `New reservation` | 8 files | ⚠️ **no** |
| 3 · schedule drag text | **none** | ⚠️ **no** |
| 4 · work-order `Add update` | **none** | ⚠️ **no** |
| 5 · editor `Save template` | 2 files | ⚠️ **no** (the **list** page *is* covered) |
| 6 · units photo controls | 1 file (`RoomTypePhotoUploadTest`) | ⚠️ **no** |
| 7 · unit-requests Approve | 2 files (`OperatorUnitRequestWorkflowTest`) | ⚠️ **no** |

**Not one of the seven has a test that would fail if it regressed.** Five have happy-path tests that exercise
the action **as an entitled role**; two have no test touching them at all.

⚠️ **This is the `#26–#30` pattern** the canon records as the project's hardest-won lesson: *five security
holes found by READING, none by a failing test — green tests are necessary, not sufficient.* **Findings 6 and
7 are the sixth and seventh members of that family.**

---

## 11 · WHAT THIS AUDIT COULD NOT ESTABLISH

| | |
|---|---|
| **Server-side enforcement of any gated write** | 41 surfaced, **0 exercised** — proving a POST rejects requires POSTing a gated action |
| **Control-level behaviour for 7 of 11 roles** | no demo account; their **page reach** is derived, but which **controls** render inside is unverified (a `@can` evaluates against the user at render time). ⚠️ Finding 6 was found *only* because it was derived — it is invisible to all four rendered roles |
| **Screenshot coverage** | **60 operator captures** — every renderable page at the reference role + 6 role-pairs on the findings. The 52 × 4 = 208 matrix was **never attempted and is not claimed**. 8 artifact routes are downloads, not renderable |
| **Cross-tenant isolation** | out of scope — this measured *intra-org* role gating only |
| **Behaviour behind a wizard step** | both create wizards read at step 1 and from source; advancing risks a write |
| **The schedule drag interaction** | zero `draggable="true"` nodes render for any role — the instruction may describe unwired behaviour. **Candidate, unproven** |

---

## 12 · RECOMMENDED FIX ORDER

### A · One sweep — Findings 2 · 4 · 5 · 6 · 7

The worklist is **mechanically enumerable**: every control whose target route carries `Authorize:`. Run 33
built it from source — 25 operator views examined, 20 flagged, **17 innocent** (10 gated by the page's own
route, 7 by a component flag), **3 real**.

⚠️ **Two traps:**
- **Finding 6 is not "add a `@can`"** — it is **`rates.manage` → `units.manage`**. A guard-adding sweep leaves it wrong.
- **Add the assertion, not just the guard.** The template exists in your repo: `OperatorEmailTemplatesTest`'s
  Front-Desk render assertion, written with `operatorUser($s, [], '<Role>')`.

### B · Finding 1 — a product question, not a patch

Which of the 25 ungated pages *should* carry a gate. `/app/owners/{owner}`, `/app/payouts/run`,
`/app/email-previews/{key}` and `/app/security/export` are the ones handing over money, an owner email, real
booking figures and a security export respectively.

### C · The missing regression guard

An operator counterpart to `OwnerCapabilityMutationBoundaryTest`, pinning **0 mutating Livewire actions**.
Nothing is wrong today; it protects the property the entire `can:`-middleware strategy rests on.

### D · Close the actor gap — cheap

`operatorUser($s, [], 'Revenue Manager' | 'Front Desk' | …)` renders any of the 11 roles in the suite, against
an isolated database. **No demo write. No decision needed.**

---

## 13 · DELIVERABLE STATUS

| Check | Result |
|---|---|
| Ledger 0 remaining | ✅ 52/52 + 2 data-states, denominator **derived** |
| Every page control-diffed across all 4 rendered roles | ✅ |
| Every finding confirmed at source | ✅ 7/7 (route middleware + view source quoted) |
| Every finding cross-checked against the 40 ratified divergences | ✅ **none is ratified** |
| Seasonal apply-panel treated as a documented deferral | ✅ recorded at `/app/rates` |
| Money + inventory as-seeded | ✅ identical start → end |
| Mutations exercised | ✅ **0 of 41** |
| Code changed | ✅ **0** |
| Screenshot per page **per role** | ⚠️ **not met** — 60 captures; the 208 matrix was never the standard applied |

**No code changed · no mutation exercised · demo pristine · tree clean.**
