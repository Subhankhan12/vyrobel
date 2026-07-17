# Vyrobel — Product Roadmap (designed vs built vs remaining)

**Purpose.** A definitive, repo-grounded audit of the *entire designed product* (the ~124 Claude Design
screens in `docs/design/raw/` indexed by `docs/design/CATALOGUE.md`) against *what is actually built* in the
repo (routes, Livewire components, controllers, services, models, migrations). It turns "what next" from a
guess into a grounded decision. **Every "DONE" here was verified against the real repo, not the catalogue's
optimism.**

**Method.** Catalogue = the screen index. Repo = the truth. Where they disagree, the repo wins and the gap is
flagged. No HTML was re-rendered — the catalogue's content-verified filenames plus a route/component/service
cross-check are sufficient.

**Read alongside:** `docs/PROJECT-STATE.md` (resume snapshot + invariants #1–30 + deferred register) and
`docs/DECISIONS.md` (the dated "why").

**⚠️ Wave status (this doc is the *map*, not the build order).** **Wave 1 (owner-accounting long tail) shipped
2026-07-16** and **Wave 2 (operator daily ops) shipped 2026-07-17** — the scorecard and the Tier 1 backlog
below reflect both. **The build order lives in `docs/TIER1-SPEC.md`** (5 waves), not here.
**✅ The Wave 2 register's security/integrity debt was cleared first (2026-07-17)** — the `terminate()` service
guard (**#29**), the now-ENFORCED permission catalog (**#30**), and the GDPR erasure trigger — deliberately
**before** Wave 3, so new screens inherit correct enforcement instead of a retrofit. **Wave 3 (Owner Messages)
is the next build.** ⚠️ Two register items remain and are worth knowing before you build: the **dead enum
values** (a status-keyed UI can render never, silently) and the **missing inbox permission** — which matters
directly to Wave 3, since the inbox writes have no catalog permission to gate on yet.

**Legend — true current state per screen:**
- ✅ **DONE** — backend built AND styled UI integrated (live in the app).
- 🟨 **BACKEND-ONLY** — certified backend/service exists, but no styled UI (needs a design-integration pass).
- 🟦 **PARTIAL / SCHEMA-ONLY** — some scaffolding exists (a migration + model, or a stub) but it is *not* a
  usable backend — no service, no logic, no UI. Honestly closer to "not built" than "built."
- ⬜ **NOT BUILT** — design only, no backend at all (a future module).

**Buildability tag for remaining work:**
- 🟢 **BUILDABLE NOW** — can be built entirely in-repo with no external dependency (styled UI over an existing
  backend, or a self-contained new module using existing patterns).
- 🔴 **ACCOUNT-GATED / BLOCKED** — needs an external account, API approval, or credential Vyrobel does not
  have. The exact unblocker is stated.

---

## A. Status summary

**Complete & demoable *today*:** a real hybrid operator can run day-to-day operations end-to-end —
**calendar/rack → front desk (check-in/out) → housekeeping → maintenance → reservation + append-only folio →
operator dashboard → units → owners → unified inbox** — all on certified backends behind **real session auth**
(login + owner/staff invites + password reset, dev email via Mailpit). A **guest can book direct** through the
public storefront (browse → room → checkout → confirmation — a real guarded write through `AssignmentService`).
An **owner** can read **statements/payouts/units** reconciling to the cent and download a branded PDF. The
guarantee stack (no-double-booking, tenant isolation, append-only money, owner isolation) is proven by
**543 tests / 5646 assertions** green on MySQL 8.

**The headline of what remains:** the *operational core is done*, and Waves 1–2 have since taken it to roughly
**one-third of the designed vision by screen count**. The other two-thirds is **(1)** styled UIs over backends
that already exist (buildable now — completions/polish), **(2)** four large net-new modules with **no backend at all**
(Guest Portal, Reviews, Analytics, Subscription/Billing — each a speculative multi-week product bet), and
**(3)** a handful of live-rail integrations that are **account-gated** (real inbox channels, Channex live,
Stripe Connect payouts, Stripe card checkout, production email). **The good news: very little of the remaining
work is truly blocked — the blockers are ~5 external accounts; the bulk is buildable-now but speculative.**

### Module scorecard

Screen counts are approximate (a few designs are on-screen ↔ PDF pairs; totals are honest, not padded).
"Done" = built **and** styled **and** live in the repo.

| Module | Designed | Done | Remaining | Status |
|---|---:|---:|---:|---|
| Auth & Onboarding | ~9 | 5 | 4 | **PARTIAL** (core auth done; onboarding wizards not) |
| Public Booking (storefront) | 4 | 4 | 0 | **COMPLETE** (card checkout deferred 🔴) |
| Operator Core / PMS | ~16 | 12 | 4 | **PARTIAL** (spine + Wave 2's daily ops done) |
| Owner Portal | ~28 | 13 | ~15 | **PARTIAL** (wedge + Wave 1's long tail done) |
| Maintenance | 4 | 2 | 2 | **PARTIAL** (module core complete end-to-end) |
| Unified Inbox | ~5 | 2 | 3 | **PARTIAL** (operator done; real channels 🔴) |
| Channel Manager (Channex) | 2 | 0 | 2 | **BACKEND-ONLY** (+ live 🔴) |
| Payments & Payouts | ~3 | 2 | 1 | **PARTIAL** (Wave 1's Payout Run + Owner Detail; Connect 🔴) |
| Settings / Admin / RBAC / Security | ~9 | 0 | 9 | **NOT STARTED** (UI) |
| Notifications | ~4 | 0 | 4 | **NOT STARTED** |
| Emails / Templates | ~12 | 2 | 10 | **PARTIAL** (2 functional emails; no template system) |
| Guest Portal (authenticated) | ~24 | 0 | 24 | **NOT STARTED** (no backend) |
| Reviews / Reputation | 3 | 0 | 3 | **NOT STARTED** (no backend) |
| Analytics / Reports | 4 | 0 | 4 | **NOT STARTED** (no backend) |
| Subscription / Billing | 3 | 0 | 3 | **NOT STARTED** (schema-only) |
| System / shared (design ref) | 2 | — | — | reference only |
| **TOTAL** | **~124** | **42** | **~82** | **~34% of screens done** |

> **How the TOTAL is derived (read before trusting the arithmetic).** **Done ~42** is the sum of the Done
> column, recounted row-by-row against real routes (`routes/web.php`) after Waves 1–2. ⚠️ **It is a column
> sum, not a distinct count:** `Owner Invite Email` is cross-listed under both Auth and Emails and is Done in
> both, so **distinct screens done is 41** — the same cross-listing described below applies to the Done column
> too, not only to Designed/Remaining. The tilde is load-bearing; do not harden it. (An email template has no
> route, so that row is verified by its Mailable + Blade, not by `route:list`.) **Designed ~124** is the
> filesystem count of `docs/design/raw/`, **not** the sum
> of the Designed column: several screens are deliberately listed under two areas (`Owner Payout Method` under
> both Owner Portal and Payments; `Owner Settings`/`Security`/`Team` under both Owner Portal and Settings;
> `Owner Messages` under both Owner Portal and Inbox; `Guest Messages` under both Inbox and Guest Portal;
> `Owner Invite Email` under both Auth and Emails). So the Designed and Remaining **columns sum higher than
> the TOTAL by design** — cross-reference, not double-count. **Remaining ~82 = 124 − 42.**
>
> **Counting basis differs from `docs/PROJECT-STATE.md` — both are right.** This table's unit is the
> **catalogue screen**; PROJECT-STATE's wave summaries count **deliverables** (Wave 1 = "9 screens/
> documents", Wave 2 = "5 screens"). The gap is where one deliverable covers two catalogue screens —
> Wave 1's `Owner Documents`/`Owner Archive` pair, and Wave 2's cancelled/no-show terminal states shipped as
> one pass over `ReservationDetail` but catalogued as two screens. On this table's per-screen basis Waves 1–2
> closed **16 rows** (26 → 42). Do not "fix" either number to match the other.

---

## B. Per-module breakdown

### 1. Auth & Onboarding — **PARTIAL** (the security chapter is done; onboarding wizards are not)

| Screen | State | Notes |
|---|---|---|
| `Vyrobel Login` | ✅ DONE | `auth/login.blade`, `LoginController` — active-only, rate-limited, non-enumerating, role-routing |
| `Vyrobel Reset Password` | ✅ DONE | `auth/forgot-password` + `auth/reset-password`, `PasswordResetController` (Password broker) |
| `Vyrobel Set Password` | ✅ DONE | `auth/set-password`, `AcceptInviteController` — sha256 single-use expiring token |
| `Vyrobel Invite Owner` | ✅ DONE | `OperatorOwners` (`/app/owners`) + role-aware invite modal + `InviteOwnerController` |
| `Owner Invite Email` | ✅ DONE | `emails/owner-invite.blade`, `OwnerInviteMail` (Mailpit in dev) |
| `Vyrobel Onboarding` | ⬜ NOT BUILT | New-operator onboarding wizard (property/channel/first-unit setup). No backend flow. |
| `Owner Onboarding` | ⬜ NOT BUILT | Owner-side onboarding wizard (accept agreement, payout method). |
| `Owner Add Unit` | 🟨 BACKEND-ONLY | Units + agreements backends exist; no styled add-unit wizard. |
| *(Guest Login / Signup / Verify Email)* | ⬜ NOT BUILT | Counted under **Guest Portal** (needs guest-auth backend). |

- **BUILT:** the entire real auth security stack (invariant #21) + invites (owner + Hotel Manager +
  Accountant) + password reset. `2FA` schema columns exist, no flow.
- **MISSING:** onboarding wizards (operator + owner + add-unit) — **UI + light orchestration only** (the
  underlying tables/services exist). — 🟢 BUILDABLE NOW · **M** (each wizard is S–M).
- **MISSING:** 2FA flow — 🟢 BUILDABLE NOW · **M** (schema exists; needs the TOTP flow + UI).

### 2. Public Booking (storefront) — **COMPLETE** (the one public write path)

| Screen | State | Notes |
|---|---|---|
| `Guest Browse` | ✅ DONE | `/book/{property}` — real search via `BookingEngineService` |
| `Guest Villa Detail` | ✅ DONE | `/book/{property}/rooms/{roomType}` — real specs/amenities/price/photos |
| `Guest Booking` / `Guest Checkout` | ✅ DONE | `/book/{property}/checkout` — GET review + **CSRF POST WRITE** through `AssignmentService` + `FolioService`; confirmation w/ real reference |

- **BUILT:** the whole public booking loop incl. the real guarded write (rejects when full, never overbooks);
  storefront shows real room photos.
- **MISSING:** **Stripe card entry at checkout** (Elements + 3DS). Manual / pay-on-arrival is the real path
  today. — 🔴 **ACCOUNT-GATED** · unblock: live/test Stripe keys + the Elements integration · **M**.

### 3. Operator Core / PMS — **PARTIAL** (the daily-ops spine is done; peripheral screens are not)

| Screen | State | Notes |
|---|---|---|
| `Operator Dashboard` | ✅ DONE | `/app/dashboard` — every KPI a true org figure from the Availability spine + ledger |
| `Operator Calendar` (rack) | ✅ DONE | `/app/rack` — availability + booking bars (drag-to-reschedule deferred) |
| `Operator Front Desk` | ✅ DONE | `/app/front-desk` — arrivals/departures/in-house + real check-in/out writes |
| `Operator Housekeeping` | ✅ DONE | `/app/housekeeping` — kanban = real status enum; status-advance writes (drag/assign deferred) |
| `Operator Reservation Detail` | ✅ DONE | `/app/reservations/{id}` — append-only folio + record-payment/refund; folio PDF |
| `Operator Units` | ✅ DONE | `/app/units` — real unit/condition enums + owner link (built beyond the catalogue's list) |
| `Operator Reservations` (list) | ✅ DONE | **Wave 2** — `/app/reservations`; chips + badges both from the shared `ReservationStage` (a chip cannot drift from what it filters) |
| `Operator Reservation Cancelled` | ✅ DONE | **Wave 2** — terminal state on `ReservationDetail`; guarded `cancel` write via `ReservationLifecycleController::CANCELLABLE` |
| `Operator Reservation No-show` | ✅ DONE | **Wave 2** — terminal state on `ReservationDetail`; guarded `markNoShow` via `NO_SHOWABLE` |
| `Operator New Reservation` | 🟨 BACKEND-ONLY | `AssignmentService::book()` exists; no create-booking wizard UI. — 🟢 · **M** |
| `Operator Group Booking` | 🟨 BACKEND-ONLY | `groups` schema + model exist; multi-room group flow not wired. — 🟢 · **M** |
| `Operator Refund` | ✅ DONE | **Wave 2** — `/app/reservations/{id}/refund`, over the **#27-guarded** backend; caps at the real `remainingRefundableMinor()`, re-derives nothing |
| `Operator Guests` (CRM) | ✅ DONE | **Wave 2** — `/app/guests`; ⚠️ **PII-visible (invariant #28)**; search is name-only *and says why* (encrypted TEXT is not indexed by value) |
| `Operator Guest Profile` | ✅ DONE | **Wave 2** — `/app/guests/{guest}`; **real writes** via the new `GuestService` (contact/flags/notes/tags); audit records field NAMES, never values (#28) |
| `Operator Portfolio` | 🟨 BACKEND-ONLY | Multi-property overview; properties + availability exist. — 🟢 · **M** |
| `Operator Search` | 🟨 BACKEND-ONLY | Cross-entity global search; data exists, no search UI/service. — 🟢 · **M** |

- **BUILT:** the operational spine (6 screens) — the part an operator touches every day — **plus Wave 2's
  daily-ops completion (6 more rows: reservations list · cancelled · no-show · refund · guests CRM · guest
  profile).** Wave 2 was **not** presentation-only: the guest profile added `GuestService`, the first guest
  write path, because the design's "Save changes" persisted nothing.
- **MISSING (4):** New Reservation · Group Booking · Portfolio · Global Search — all 🟢 **BUILDABLE NOW** over
  already-certified services, none account-gated. ⚠️ **Global Search needs the blind-index decision first**
  (the Guests CRM proved the constraint: encrypted TEXT is never indexed by value) — it is a Wave 5
  NEEDS-BACKEND-FIRST item, not a straight UI pass. Total ≈ **M–L** across the module.

### 4. Owner Portal — **PARTIAL** (the wedge/hero screens **and** Wave 1's long tail done; settings/analytics/tax remain)

| Screen | State | Notes |
|---|---|---|
| `Owner Dashboard` | ✅ DONE | `/owner` — real owner-scoped earnings |
| `Owner Statement` | ✅ DONE | `/owner/statements/{id}` — the wedge's hero screen; reconciles to the cent |
| `Owner Statement PDF` | ✅ DONE | branded PDF via `PdfService`; reconciles to the cent |
| `Owner Payouts` | ✅ DONE | `/owner/payouts` — real pending/paid/failed/reversed lifecycle |
| `Owner My Units` | ✅ DONE | `/owner/units` — owner-scoped occupancy (excludes non-owner units) |
| `Owner Expenses` | ✅ DONE | **Wave 1** — `/owner/expenses`; real `owner_expenses` incl. maintenance-sourced; `deductible` kept **TRI-STATE** (true/false/**null**), never coerced |
| `Owner Balance` | ✅ DONE | **Wave 1** — `/owner/balance`; real ledger + per-row running balance; net position DERIVED (Σ `signedMinor`) |
| `Owner Agreement` | ✅ DONE | **Wave 1** — `/owner/agreement`; effective-dated terms, display-only, honest nulls ("Not set", never "0%"). ⚠️ scoped explicitly via `OwnerContext` (the models are **not** owner-scoped) |
| `Owner Bookings` / `Owner Booking Detail` | 🟨 BACKEND-ONLY | Attributed reservations exist; owner-lens booking views not built. — 🟢 · **M** |
| `Owner Unit Detail` | ✅ DONE | **Wave 1** — `/owner/units/{unit}`; owner-scoped occupancy for **this unit only**; cross-owner/operator-owned → 404 |
| `Owner Block Calendar` | 🟨 BACKEND-ONLY | `inventory_blocks` exist; owner drag-block calendar not built. — 🟢 · **M** |
| `Owner Documents` / `Owner Archive` | ✅ DONE | **Wave 1** — `/owner/documents` (aggregates only the **really generatable** PDFs — every row downloads) + `/owner/archive` (finalized statements, real gap-free `sequence_no`, drafts excluded) |
| `Owner Remittance Advice PDF` | ✅ DONE | **Wave 1** — `/owner/payouts/{payout}/remittance`; the amount label derives from the REAL status ("Net payable" while pending, never "Net paid") |
| `Owner Booking Summary PDF` | ✅ DONE | **Wave 1** — `/owner/statements/{s}/bookings`; the bookings behind a statement's gross |
| `Owner Team` | 🟨 BACKEND-ONLY | Users/roles/invites exist; owner sub-user UI not built. — 🟢 · **M** |
| `Owner Security` / `Owner Settings` | 🟨 BACKEND-ONLY | User settings exist; no styled owner settings. — 🟢 · **M** |
| `Owner Rates` | 🟨 BACKEND-ONLY | Only if rates are owner-editable; `RateAndRestrictionService` exists. — 🟢 · **M** |
| `Owner Payout Method` | ⬜/🔴 | Bank/payout method setup — tied to **Stripe Connect**. — 🔴 · unblock: Connect onboarding + KYC · **M** |
| `Owner Reports` / `Owner Insights` | ⬜ NOT BUILT | Analytics — see **Analytics module** (no backend). |
| `Owner Reviews` | ⬜ NOT BUILT | See **Reviews module** (no backend). |
| `Owner Tax Center` / `Owner Annual Tax Summary PDF` | ⬜ NOT BUILT | No tax-computation backend. — 🟢 (buildable) but net-new · **L** |
| `Owner Messages` | ⬜ | Owner-side inbox — see **Unified Inbox**. **Wave 3, and unblocked:** the owner portal shell + `InboxService` both exist; it needs the owner-portal inbox SCREEN. (This row used to say "needs owner portal shell" — a phantom blocker: 13 owner screens already live on that shell.) |
| `Owner Notifications` | ⬜ NOT BUILT | See **Notifications module**. |
| `Owner Help` | ⬜ NOT BUILT | Static FAQ. — 🟢 · **S** |

- **BUILT (13):** the 5 wedge screens (dashboard, statement, statement PDF, payouts, units) — the
  differentiator — **plus Wave 1's long tail (6 screens: balance · expenses · agreement · archive · unit
  detail · documents) and 2 PDFs (remittance · booking summary).** Wave 1 was **presentation only**,
  mechanically verified per gate: **zero** files changed under `app/Services`, `app/Models`,
  `database/migrations`, `app/Payouts`. Three shared helpers stop screen↔document drift (`RailLabel`,
  `OwnerOccupancy`, `assertNoRawBlade()`).
- **MISSING (~15):** `Owner Messages` (**Wave 3 — next**), owner-lens `Bookings`/`Booking Detail`/`Block
  Calendar`, `Team`/`Security`/`Settings`/`Rates`, `Help` — all 🟢 **BUILDABLE NOW**; plus `Owner Payout
  Method` (🔴 Stripe Connect) and the analytics/reviews/tax screens (net-new backends). **The
  owner-accounting long tail specifically is no longer the gap — Wave 1 closed it.**

### 5. Maintenance — **PARTIAL** (module core COMPLETE end-to-end; two analytics/config extras remain)

| Screen | State | Notes |
|---|---|---|
| `Operator Maintenance` (board) | ✅ DONE | `/app/maintenance` — kanban; OOS badge/rail; real create + assign; OOS↔availability integration |
| `Operator Work Order` | ✅ DONE | `/app/maintenance/{ticket}` — guarded lifecycle; manual-move flag; cost→owner_expense card |
| `Operator Maintenance Costs` | ⬜ NOT BUILT | Cost-trend analytics. Cost data exists (`owner_expenses`); charts not built. — 🟢 · **M** |
| `Operator Maintenance Settings` | ⬜ NOT BUILT | SLAs/categories config. — 🟢 · **S** |

- **BUILT:** the whole module — `MaintenanceService`, OOS↔availability (invariant #22), cost→owner-expense,
  styled board + work order. Nothing in the *core* is deferred.
- **MISSING:** cost analytics + settings — 🟢 **BUILDABLE NOW**, peripheral polish, not blocking.

### 6. Unified Inbox — **PARTIAL** (operator surface COMPLETE; real channels account-gated)

| Screen | State | Notes |
|---|---|---|
| `Operator Inbox` | ✅ DONE | `/app/inbox` — thread list, channel badges/filters over real rows |
| `Operator Conversation` | ✅ DONE | `/app/inbox/{thread}` — real delivery states, reply/assign/close/snooze/reopen |
| `Operator Messages` | ✅ DONE | (same module; the catalogue's older name for the inbox pair) |
| `Owner Messages` | ⬜ | Owner ↔ operator inbox — backend (`InboxService`) exists; needs an owner-portal inbox screen. — 🟢 · **M** |
| `Guest Messages` | ⬜ | Guest ↔ operator inbox — needs the **Guest Portal** shell. — 🟢 (after guest portal) · **M** |

- **BUILT:** the whole inbox backend (`InboxService`, `App\Messaging` channel seam, idempotency, GDPR redaction,
  invariants #23–25) + the styled operator surface. The **manual channel works today.**
- **MISSING (channels):** `email` / `whatsapp` / `booking_com` / `airbnb` adapters are config-gated skeletons
  that throw "not configured." — 🔴 **ACCOUNT-GATED** · unblock: **email** = inbound provider
  (Postmark/Mailgun) + verified domain; **whatsapp** = Meta WhatsApp Business API + BSP number; **OTA** =
  Channex/OTA messaging API access · **M each**.
- **MISSING (surfaces):** owner + guest inbox screens — 🟢 buildable (gated only on their portals existing).

### 7. Channel Manager (Channex) — **BACKEND-ONLY** (certified backend; no config UI; live is gated)

| Screen | State | Notes |
|---|---|---|
| `Operator Channels` | 🟨 BACKEND-ONLY | `channels` vault + `ChannelMappingGuard` + ARI/webhook pipeline certified; no config UI. — 🟢 (UI) · **M** |
| `Operator Rates` (ARI grid) | 🟨 BACKEND-ONLY | `RateAndRestrictionService` exists; no rate/ARI grid UI. — 🟢 (UI) · **M** |

- **BUILT:** the entire Channex integration against `FakeChannexClient` + simulated signed payloads
  (idempotency, retry/dead-letter, accept-and-flag overbooking).
- **MISSING (UI):** channels + rates screens — 🟢 **BUILDABLE NOW** over the existing services.
- **MISSING (live):** the ARI/webhook/HMAC shapes are **documented guesses**. — 🔴 **ACCOUNT-GATED** · unblock:
  a Channex staging account + a public tunnel (run `docs/prompts/live-integration-checkpoint.md`) · **M**.

### 8. Payments & Payouts — **PARTIAL** (Wave 1 shipped both operator UIs; only Connect remains)

| Screen | State | Notes |
|---|---|---|
| `Operator Payout Run` | ✅ DONE | **Wave 1** — `/app/payouts/run`; **REAL money writes** through the certified `OwnerPayoutService` (`createFromStatement`/`pay`), idempotent + guarded. **`ManualPayoutRail` is the real rail and the copy says so** ("Vyrobel does not move this money — marking a transfer paid RECORDS a bank transfer you already made"); **Stripe Connect disabled "Soon", never callable** |
| `Operator Owner Detail` | ✅ DONE | **Wave 1** — `/app/owners/{owner}`; the operator's lens on ONE owner. ⚠️ **OwnerScope is INACTIVE on `/app`** → every read filtered explicitly by `owner_id` (tested symmetrically both ways) |
| `Owner Payout Method` | ⬜/🔴 | Bank details — **Stripe Connect**. — 🔴 · unblock: connected-account onboarding + KYC · **M** |

- **BUILT:** Stripe payments (validated live test-mode), `FolioService` append-only ledger, owner payouts via
  `ManualPayoutRail` (the real, tested disbursement path).
- **BUILT (UI):** payout-run + operator owner-detail — **shipped in Wave 1** (2026-07-16).
- **MISSING (live):** `StripeConnectPayoutRail.send()` throws. — 🔴 **ACCOUNT-GATED** · unblock: Stripe Connect
  credentials + KYC · **M**.

### 9. Settings / Admin / RBAC / Security — **NOT STARTED (UI)** (mixed backends exist)

| Screen | State | Notes |
|---|---|---|
| `Operator Settings` | 🟨/⬜ | Settings model exists (partial); many toggles unbacked. — 🟢 · **M** |
| `Operator Property Settings` | 🟨 BACKEND-ONLY | `properties` + `property_policies` exist; no config UI. — 🟢 · **M** |
| `Operator Profile` | 🟨 BACKEND-ONLY | Users exist; no profile UI. — 🟢 · **S** |
| `Operator Roles` | 🟨 BACKEND-ONLY | spatie roles/permissions certified; no permission-matrix UI. — 🟢 · **M** |
| `Operator Security` | 🟨 BACKEND-ONLY | `audit_events` exist (partial); no audit-log/2FA center. Design file is 2.2MB. — 🟢 · **M** |
| `Owner Settings` / `Owner Security` / `Owner Team` | 🟨 BACKEND-ONLY | See Owner Portal. — 🟢 · **M** |

- **BUILT:** the underlying data (users, roles, policies, audit events) — but **no settings UI of any kind.**
- **MISSING:** all 🟢 **BUILDABLE NOW**; a cohesive settings/admin area is **M–L**. Some toggles imply small
  new backends (e.g. notification prefs → see Notifications).

### 10. Notifications — **NOT STARTED** (no backend)

`Operator Notifications`, `Operator Notification Preferences`, `Owner Notifications`, `Guest Email Preferences`.
No notification feed/preference backend exists (distinct from the reliability/queue backbone). — 🟢 BUILDABLE NOW
· net-new · **M**.

### 11. Emails / Templates — **PARTIAL** (2 functional emails; no template system)

- **BUILT (functional):** `Owner Invite Email` + password-reset email (`emails/owner-invite`,
  `emails/password-reset`) — real, sent via Mailpit in dev.
- **DESIGNED, NOT BUILT (9 transactional templates):** Booking Confirmation, Cancellation, Payment Reminder,
  Pre-arrival, Refund Confirmation, Review Request, Verify Email, Waitlist Notification, Winback — plus
  `Email Previews` (catalog), `Operator Email Editor`, `Operator Email Templates`. No template
  storage/rendering/editor system. — 🟢 BUILDABLE NOW · net-new · **M–L** (most templates are 🟢 but several
  are triggered by not-yet-built modules, e.g. Review Request needs Reviews).
- **DELIVERY (production):** — 🔴 **ACCOUNT-GATED** · unblock: a real transactional provider (Postmark/SES) +
  verified domain. Dev/demo = Mailpit (wired).

### 12. Guest Portal (authenticated) — **NOT STARTED** (no backend — the single largest net-new module)

~24 authenticated guest screens (distinct from the public booking storefront, which **is** done):
`Guest Login/Signup/Verify Email`, `Guest Trips`, `Guest Reservation`, `Guest Manage Booking`,
`Guest Payments`, `Guest Credits`, `Guest Add-ons`, `Guest Documents`, `Guest Check-in`,
`Guest ID Verification`, `Guest Messages`, `Guest Review`, `Guest Waitlist`, `Guest Wishlist`,
`Guest Referral Landing`, `Guest Arrival Info`, `Guest Help`, `Guest Account`, `Guest Email Preferences`,
plus the guest PDF pack (Folio Receipt, VAT Invoice, Credit Note, Refund Receipt, Statement of Account,
Arrival Info).

- **BUILT:** nothing (the closest is the *public, unauthenticated* `/book` storefront). Folio data exists, so
  a few guest screens (Reservation, Folio Receipt) could reuse it — but there is **no guest-auth backend, no
  guest self-service backend, no wishlist/credits/referral/waitlist backends.**
- **MISSING:** the whole module. — 🟢 BUILDABLE NOW (no external dependency) but a **large, gated, phased
  build like Owner Accounting**, and a **speculative product bet** (no customer signal). · **XL**.
  Sub-pieces need new backends (guest auth, self-service check-in, ID verification, credits/referrals,
  wishlist, waitlist).

### 13. Reviews / Reputation — **NOT STARTED** (no backend)

`Operator Reviews`, `Owner Reviews`, `Guest Review` (+ `Review Request Email`). No reviews schema/service.
— 🟢 BUILDABLE NOW · net-new self-contained module · speculative bet · **M–L**.

### 14. Analytics / Reports — **NOT STARTED** (no backend)

`Operator Reports`, `Operator Insights`, `Owner Reports`, `Owner Insights` (RevPAR/ADR/occupancy trends,
export). Raw data exists (availability, folios, ledger) but **no analytics/aggregation layer** and no charting.
— 🟢 BUILDABLE NOW · net-new · speculative bet · **L**.

### 15. Subscription / Billing — **NOT STARTED** (schema-only, honestly closer to not-built)

`Operator Billing`, `Vyrobel Paywall`, `Vyrobel Checkout`. Migrations/models exist (`subscriptions`, `plans`,
`plan_features`, `subscription_items`, `usage_counters`) — but **no billing service, no paywall/gating logic,
no Stripe subscription integration, no UI.** — 🟢 (in-repo logic) **but** live billing is 🔴 **ACCOUNT-GATED**
(Stripe subscription/product config). Net-new · speculative until Vyrobel is charging customers · **L**.

### 16. System / shared (design reference)

`System States` (loading/empty/404/offline catalog) + `index.html` (manifest). Reference material for the
design system, not app screens.

---

## C. Prioritized backlog (the actionable part)

### Tier 1 — Buildable now, **completes/polishes existing modules** (🟢, over certified backends)

These are styled UIs (or small features) over backends that are already built and tested. **No external
dependency. Highest ratio of value to risk — every one is a completion, not a bet.**

**⚠️ Waves 1–2 have shipped against this table** — the ✅ rows below are DONE, kept (not deleted) so the
backlog stays readable as a ledger. **The sequenced build order is `docs/TIER1-SPEC.md`, not this table.**

| # | Module | Item | Size | Type |
|---|---|---|---|---|
| 1 | Operator Core | ✅ **DONE (Wave 2)** — **Reservations list** + Cancelled/No-show states | M | completion |
| 2 | Operator Core | **New Reservation** wizard (over `AssignmentService`) | M | completion |
| 3 | Operator Core | **Group Booking** flow | M | completion |
| 4 | Operator Core | ✅ **DONE (Wave 2)** — **Guests CRM** (list + profile) + the **Refund** screen; added `GuestService` | M | completion |
| 5 | Operator Core | **Portfolio** + **Global Search** (⚠️ Search needs the blind-index decision first) | M | completion |
| 6 | Owner Portal | ✅ **DONE (Wave 1)** — **Expenses · Balance · Agreement · Documents/Archive** (over owner-accounting) | M | completion |
| 7 | Owner Portal | 🟡 **HALF DONE** — ✅ **Unit Detail (Wave 1)**; **Bookings · Booking Detail · Block Calendar** remain | M–L | completion |
| 8 | Owner Portal | ✅ **DONE (Wave 1)** — **Remittance / Booking-Summary PDFs** (over `PdfService`) | S | completion |
| 9 | Channel Mgr | **Operator Channels** config UI + **Rates/ARI grid** | M | completion |
| 10 | Payments | ✅ **DONE (Wave 1)** — **Payout Run** + **Operator Owner Detail** UIs | M | completion |
| 11 | Maintenance | **Maintenance Costs** analytics + **Settings** | M | polish |
| 12 | Inbox | **Owner Messages** surface (over `InboxService`) — **Wave 3, next** | M | completion |
| 13 | Auth | **Operator + Owner + Add-Unit onboarding** wizards; **2FA** flow | M | completion |
| 14 | Settings | **Property Settings · Profile · Roles matrix · Security/audit** | M–L | completion |

**Tier 1 headline:** **14 workstreams — 5 fully done** (Waves 1–2: rows 1, 4, 6, 8, 10) **+ row 7 half done,
so ~8½ remain.** All still 🟢 buildable now, all completing already-started modules. This is where the
operator/owner product goes from "core done" to "feature-complete PMS + owner portal." **No blockers — but the
WAVE 2 OUTSTANDING REGISTER (`docs/PROJECT-STATE.md`) is sequenced ahead of the next screen**, and `Global
Search` (row 5) is really a Wave 5 NEEDS-BACKEND-FIRST item.

### Tier 2 — Buildable now, **net-new modules** (🟢, but each a multi-week product BET)

Self-contained new modules using existing patterns (gated, phased builds like Owner Accounting). No external
dependency, but **no backend today and no customer signal** — build on validated demand, not blind.

| # | Module | What it is | Size | Note |
|---|---|---|---|---|
| A | **Guest Portal** | ~24 authenticated guest self-service screens + guest-auth backend | XL | Largest bet; highest surface |
| B | **Analytics / Reports** | RevPAR/ADR/occupancy aggregation layer + 4 report screens | L | Bet; data exists, layer doesn't |
| C | **Reviews / Reputation** | reviews schema/service + 3 screens + request email | M–L | Bet |
| D | **Subscription / Billing** | billing service + paywall/gating + 3 screens (schema exists) | L | Bet; also needs Stripe (Tier 3) to charge |
| E | **Notifications** | feed + preference backend + 4 screens | M | Bet |
| F | **Email template system** | 9 transactional templates + editor/list + storage | M–L | Some templates gated on Tier-2 modules |

**Tier 2 headline:** ~6 large net-new bets. All buildable in-repo, but each is speculative without customer
signal — **do NOT build breadth blind.** Guest Portal is the single biggest (XL).

### Tier 3 — **Account-gated** (🔴, cannot proceed until an external account/approval exists)

| # | Module | Item | Unblocker |
|---|---|---|---|
| i | Inbox | Real **email** channel | Inbound-email provider (Postmark/Mailgun) + verified sending domain |
| ii | Inbox | Real **WhatsApp** channel | Meta WhatsApp Business API approval + a BSP/Twilio number |
| iii | Inbox | Real **OTA** channels (booking.com/airbnb) | OTA messaging API access (Channex messaging or OTA partner API) |
| iv | Channel Mgr | **Channex live** (ARI/webhook/HMAC vs reality) | Channex staging account + public tunnel |
| v | Payments | **Stripe Connect** owner payouts | Connected-account onboarding + KYC credentials |
| vi | Public Booking | **Stripe card checkout** (Elements + 3DS) | Live/test Stripe keys |
| vii | Emails | **Production email delivery** | Real transactional provider (Postmark/SES) + verified domain |
| viii | Billing | **Live subscription billing** | Stripe products/prices + Connect (pairs with Tier-2 D) |

**Tier 3 headline:** 8 items, each blocked on exactly one external account. The *code paths and fakes already
exist* for most (Channex, Stripe, inbox channels) — these are integration/validation checkpoints, not
green-field builds. Run `docs/prompts/live-integration-checkpoint.md` for the Channex/Stripe legs.

---

## D. Honest notes

- **Account-gated vs buildable ratio — the reassuring part.** Of the ~82 remaining screens, **only ~8 items
  are truly account-gated** (Tier 3), and even those mostly have working fakes + code paths awaiting a
  credential. **The overwhelming majority of remaining work is buildable now in-repo.** Vyrobel is *not* stuck
  waiting on third parties — it's a matter of build effort and product prioritization.

- **Completion vs speculation — the important distinction.** Tier 1 (14 workstreams, **~8½ still open** after
  Waves 1–2) **completes modules already started** over certified backends — low risk, high confidence,
  clearly valuable to the operators/owners the product already serves. Tier 2 (~6 modules) are **net-new
  speculative bets** — buildable, but with **no customer signal**. Building Tier 2 blind is the main risk to
  guard against.

- **The biggest "module" is the emptiest.** The **Guest Portal is ~24 designed screens with zero backend** —
  it is the single largest chunk of the *designed vision* and the single largest *unbuilt* area. It is a
  genuine XL commitment (new guest-auth + self-service + credits/wishlist/referral backends), not a design
  pass. Do not mistake "32 guest screens exist in the catalogue" for "the guest experience is nearly done" —
  only the *public, unauthenticated booking loop* is built.

- **Schema ≠ backend.** Subscription/Billing has migrations + models but **no service, logic, or UI** — it is
  honestly **NOT STARTED**, not "backend exists." Same caution for `corporate_accounts`, `groups`,
  `webhooks`/`api_keys`, and tax — scaffolding without a service is not a usable backend.

- **What "done" really means here.** The 42 done screens are **the operational heart of the product** (daily
  operator ops + the owner-accounting wedge, wedge *and* long tail + the public booking write + real auth).
  That is exactly the right ~34% to have finished first — it is the demoable, sellable core. The remaining
  ~66% is breadth, not the spine. **Waves 1–2 deepened the spine rather than widening it** — the owner
  accounting story now runs statement → remittance PDF → payout run → the owner sees it paid, and operator
  daily-ops runs reservations → refund → guest CRM, both end-to-end and reconciling to the cent.

- **Recommendation (unchanged from PROJECT-STATE):** before committing to Tier 2's speculative bets,
  **validate with a real hybrid operator** (demo the full built loop). Let real feedback pick which Tier 2
  module (or which Tier 3 unblock) to fund. Tier 1 completions are safe to pursue in parallel regardless —
  they strengthen the exact core you'd demo.

---

*Generated by the roadmap audit (read-only) — every "DONE" cross-checked against `routes/web.php`,
`app/Livewire`, `app/Http/Controllers`, `app/Services`, `app/Models`, and `database/migrations`. Trust the
repo over this doc where they drift, and fix this doc.*
