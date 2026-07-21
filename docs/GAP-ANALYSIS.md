# Vyrobel — Master Gap Analysis & Phased Development Plan

**"Make the real product work the way the prototype shows."** The complete map from the built repo to the full
124-screen prototype, with every repo-checkable claim **audit-verified against actual code** (5-agent verification,
2026-07-20, at HEAD `c34669a`) and the corrections baked in.

> **THE CUSTOMER SIGNAL (this is why this doc exists).** Fatima — a warm operator contact — **liked the demo AND
> the prototype**. The requirement is now concrete: **the real product must work the way the prototype shows.**
> This **supersedes** the prior "no customer has reacted — don't build blind" framing in PROJECT-STATE/CLAUDE.
> The prototype is now the SPEC for what each screen shows; the repo stays the truth for what backs it; every gap
> gets BUILT (not faked) per this plan. **Sequencing bias: make Fatima OPERATIONAL soonest** (Phases 0–3 complete
> the operator+owner product for her daily business before the large Guest Portal phase).

> ⚠️ **AUDIT PROVENANCE.** The original plan's structure is sound and every large gap it named is genuinely absent
> (guest auth, notifications, reviews, wallet, tenant-provisioning, group booking — all verified with zero backing
> code). The verification changed three things, folded in below: **(a) multi-property is NOT a schema epic** —
> re-scoped from L to M; **(b) the counts** — states/POST-sections/PDFs were miscounted as screens; **(c) several
> nuances** — a dormant settings scaffold, an existing maintenance category enum, an existing property-scoped
> search. Meta-lesson (a) held again: a brief is a hypothesis, the repo is truth.

---

## 0. THE HONEST HEADLINE (corrected)

- **Built & working: ~55 screens/artifacts** — **28** operator screens · **23** owner-portal screens · 3 public-booking
  screens · ~5 auth screens (login/forgot/reset/accept-invite/2FA-challenge) · 4 PDFs (statement/remittance/booking-
  summary/annual-tax; owner folio PDF too) · 2 Mailables. The operational spine: operator daily ops, the
  owner-accounting wedge + the **full owner portal buildable set**, public booking, and a hardened auth chapter.
  **✅ PHASE 1 COMPLETE (2026-07-20)** added 6 operator screens (Search · Group Booking · Maintenance Costs ·
  Maintenance Settings · Roles · Profile) — operator 21 → 27. **✅ PHASE 2 COMPLETE (2026-07-20)** added 8 owner
  screens (Bookings · Booking Detail · Block Calendar · Rates · Tax Center · Payout Method · Add a Unit · Help) +
  the operator Unit Requests screen + the Annual Tax PDF — owner 15 → 23, operator 27 → 28.
- **Not built: ~70 designed screens** across four fronts: the rest of the Operator app (~12), the Owner portal
  long-tail (~8, all deferred subsystems), the **entire Guest Portal (~24 — the largest single gap)**, the SaaS
  platform layer (~3), and ~9 transactional email templates.
- **Three true greenfield subsystems the prototype assumes** (verified absent — zero backing code/schema):
  **GUEST AUTH** (`Guest extends Model`, not `Authenticatable`; no guest guard/password/route), **NOTIFICATIONS**
  (no table, no `app/Notifications/`, zero `->notify()` calls), and **REVIEWS + WALLET/CREDITS** (no tables/models).
  ⚠️ **MULTI-PROPERTY is NOT one of them** — see the correction in §6.
- **~8 items are ACCOUNT-GATED** — cannot work until external accounts exist (Stripe Connect KYC, Stripe live
  cards, Channex, WhatsApp Business, OTA messaging, a production email provider, an ID-verification provider).

**Honest estimate to "everything like the prototype": ~5–7 months of disciplined solo build** at this project's
quality bar, plus the account approvals in parallel.

### ⚠️ Counting rules (the audit's corrections — so the numbers stop drifting)
- **A screen is a routed GET surface**, not a state, a POST section, or a PDF. So: Reservation **Cancelled** /
  **No-show** are terminal *states* rendered on Reservation Detail (no routes) — **not screens**. **Property
  Settings** is one POST section of the single `/app/settings` screen — **not a second screen**. A separate owner
  **Security** screen does **not** exist — change-password + 2FA live ON `/owner/settings` (the nav's "Security" is
  still Coming-soon).
- **Units exists, built, with NO design file** (`/app/units` → `UnitsList`, styled ad-hoc) — count it built.
- **Guest Portal = ~24** (the authoritative ROADMAP module count). The "32" is the design-export *file* count for
  the guest role group, which also contains the 4 already-built public-booking screens + cross-listed artifacts.
- Design totals: **125 files / 124 screens** in `docs/design/raw/` (`CATALOGUE.md`, the filesystem is the authority).

---

## 1. OPERATOR APP — 40 designed · 28 built · ~12 remaining
*(+1 no-design-file screen: **Unit Requests** `/app/unit-requests` — the operator-visibility side of the owner
Add-Unit request, added in Phase 2 · SG6.)*

### ✅ Built & working (28 routed screens)
Dashboard · Calendar/Rack · Front Desk · Housekeeping · Maintenance board · Work Order · Inbox · Conversation ·
Reservations List · New Reservation · Reservation Detail (Cancelled/No-show render here as terminal states) · Refund ·
Guests · Guest Profile · Rates · **Units** (no design file) · Owners · Owner Detail · Payout Run · Settings (with
Property/Policies/Organization POST sections) · Security (password + real TOTP 2FA) · **Search · Group Booking ·
Maintenance Costs · Maintenance Settings · Roles · Profile** (the six ✅ Phase 1 screens).

### ✅ Group O1 + Roles — SHIPPED in Phase 1 (2026-07-20, 5 gated sub-gates)
| Page | Shipped as | Sub-gate |
|---|---|---|
| Operator Group Booking | `GroupBookingService` + atomic `AssignmentService::createGroupBooking` over the EXISTING `groups` + `reservations.group_id` (extend, not parallel); phantom-event guard mutation-proven (a rolled-back block fires ZERO events) | SG1 `eefd22d` |
| Operator Search | `SearchService` — GLOBAL cross-entity (reservations/guests/units/owners), tenant-scoped, **guests by NAME only** (encrypted email/phone can't be `LIKE`-searched → the blind-index decision is DEFERRED, and the limitation is surfaced, never silent) | SG2 `1595583` |
| Operator Roles | `RoleAssignmentService` — VIEW the catalog + ASSIGN, gated `can:users.manage`. The design's catalog-EDIT (New/Save/Delete role) was **refused as an escalation weapon**; the **privilege-subset rule** (`perms(role) ⊆ perms(actor)`) blocks escalation, mutation-proven (Group Admin can't grant Property Owner) | SG3 `35019e9` |
| Operator Maintenance Costs | `MaintenanceCostService` — REAL derived aggregates over resolved tickets (reconcile to the cent); the fabricated labour-vs-parts split **refused, not faked**; empty → "—" | SG4 `a4a3ddc` |
| Operator Maintenance Settings | `MaintenanceSettingsService` + `maintenance_settings` typed columns (SLA hours, category→assignee, toggles) via the `SettingsService` pattern (NOT the dormant KV scaffold); notification dispatch honestly deferred to Phase 3 | SG4 `a4a3ddc` |
| Operator Profile | `OperatorProfileController` — SELF-ONLY name/email write (auth-resolved, never a request id — mirrors `OwnerProfileController`); unbacked design fields (phone/timezone/preferred-name) OMITTED, not faked | SG5 (this gate) |

### ⬜ Remaining (~12), grouped by what they need

**Group O2 — subsystems (each a real project):**
| Page | The subsystem underneath | Size |
|---|---|---|
| Operator Channels | Channel/mapping CRUD + a REAL `channels.status` writer (⚠️ **the certified channel manager is INERT in production** — see Phase 0.1) + connect flow. Live sync = account-gated (Channex) | M–L |
| ✅ Operator Notifications + Notification Preferences | **BUILT (Phase 3)** — events → dispatcher → in-app feed + typed prefs that genuinely gate (in-app + email) | — |
| ✅ Operator Email Templates | **BUILT (Phase 3)** — the documented VIEW (subject/trigger/variables + preview); an editable per-tenant template STORE is deferred honestly ("Edit · Soon") | editor: M–L |
| Operator Team Schedule + Schedule PDF | Staff rostering — a new module (shifts, assignments, PDF) | M |
| ✅ Operator Insights + Reports | **BUILT (Phase 6b)** — real ADR/RevPAR/occupancy/revenue over the shared `PerformanceMetrics` layer, scoped by the 6a property context (per-property + all-properties); Reports = real folio revenue + real maintenance cost (other cost lines "Not tracked yet"). Gated `reports.view`. No fabricated history/narrative. | — |
| Operator Reviews | The REVIEWS module (guest reviews + operator responses) — shared with Owner + Guest surfaces | M–L |
| ✅ Operator Portfolio | **BUILT (Phase 6a)** — the property switcher (topbar, session-backed, pivot-aware) + the Portfolio overview (real per-property unit counts/occupancy/status; ADR/RevPAR deferred to 6b) + property CRUD (`properties.manage`). The complete `->first()` refactor landed with it (grep-clean). | — |
| Operator Billing | Vyrobel-the-SaaS billing the operator (subscription) — pairs with Paywall/Checkout | L |

---

## 2. OWNER PORTAL — 31 designed · 23 built · ~8 remaining

### ✅ Built & working (23 routed screens)
Dashboard · My Units · Statement (+PDF) · Balance · Expenses · Payouts · Archive · Unit Detail · Documents ·
Agreement · Messages (list + thread) · Settings (carries change-password + 2FA + self-profile write) · Remittance
Advice PDF · Booking Summary PDF · **Bookings · Booking Detail · Block Calendar · Rates · Tax Center (+ Annual Tax
PDF) · Payout Method · Add a Unit · Help** (the eight ✅ Phase 2 screens). *(Owner "Security" is NOT a separate
built screen — its capability lives on Settings; the accept-invite set-password page and the Invite Email are
Auth-group artifacts, real but counted there.)*

### ✅ SHIPPED in Phase 2 (2026-07-20, 6 gated sub-gates) — the owner portal's buildable set
| Page | Shipped as | Sub-gate |
|---|---|---|
| Owner Bookings + Booking Detail | Owner-scoped THROUGH the units (reservations have no owner_id; a `{booking}` id 404s unless it touches an owned unit — mutation-proven). "Net to you" via the ONE shared `OwnerLedgerAttribution` rule; guest NAME only | SG1 `0dfc560` |
| Owner Block Calendar | Over the EXISTING `blockInventory` (extended so it enforces `assertUnitFree` for unit-level blocks — the enforcement its docblock always promised); owner-scope write guard mutation-proven; unblock via the wired `inventory_block.released` | SG2 `598cc9b` |
| Owner Rates | FULL owner edit via the certified `setRate`/`setRestriction`. Owner-scope on a MONEY write = **exclusive-ownership** (`editableRoomTypes()` drops any room type with a foreign unit) — mutation-proven both ways; direct-edit-no-approval divergence recorded | SG3 `58f0b49` |
| Owner Tax Center + Annual Tax PDF | Encrypted `tax_id`; the payout HOLD is genuine (`OwnerPayoutService::pay()` refuses a tax-incomplete owner, releases on save — mutation-proven); real annual aggregation reconciling to the cent; self-only PDF | SG4 `5119548` |
| Owner Payout Method | New `payout_methods` (IBAN ENCRYPTED, masked ···· last4 from cleartext columns); self-only mutation-proven across read/set-default/remove; live Stripe Connect rail deferred honestly; real ISO mod-97 IBAN checksum | SG5 `b95228f` |
| Owner Add a Unit + Help | Add Unit is an owner REQUEST (`unit_requests`, pending) the operator reviews — NOT direct creation of billable inventory (policy: state the choice) + a new operator `/app/unit-requests` visibility screen; Help is a STATIC FAQ + the real Messages route (no faked tickets) | SG6 (this gate) |

### ⬜ Remaining (~8) — deferred subsystems (each rides a shared foundation)
| Page | Shared subsystem / reason |
|---|---|
| ✅ Owner Reports + Insights | **BUILT (Phase 6b)** — owner-scoped analytics over the same `PerformanceMetrics` layer (their units only, isolation mutation-proven); earnings reconcile to the owner ledger (`OwnerLedgerAttribution`); a real-or-omitted observation. |
| Owner Reviews | REVIEWS (owner-scoped) |
| ✅ Owner Notifications | **BUILT (Phase 3)** — owner feed + typed preferences (genuinely gating, in-app + email) |
| Owner Team | Owner sub-tenancy (an owner invites their own users) — a permission subsystem (L) |
| Owner Onboarding wizard (Steps 1–3) | No onboarding wizard exists (Step-1 profile write ships on Settings; Steps 2–3 = Payout Method [now built] + notification prefs) |

---

## 3. GUEST PORTAL — ~24 designed · GUEST AUTH FOUNDATION BUILT (Phase 4a) · ~24 portal screens remain

### ✅ Built (the public, anonymous booking flow — counted under public booking, NOT the guest-portal module)
Guest Browse · Villa Detail · Checkout (+confirmation). Real, guarded, can't double-book.

### ✅ GUEST AUTH FOUNDATION — BUILT (Phase 4a, 2026-07-20)
The hard truth WAS "it all sits on guest auth, which does not exist." **It exists now.** `Guest` is Authenticatable
on a SEPARATE `guest` guard (a keyed **blind index** solves the encrypted-email lookup); booking-tied claim + email
verification (Phase-3 Verify Email wired); guest login + reset (hardened, #28 through the reset surface); Guest
Account (self-only, guest-isolation mutation-proven) + safe removal. Realm separation + guest isolation +
booking-tied + no-enumeration mutation-proven; operator/owner auth unregressed. Screens: guest login · claim ·
verify/set-password · forgot/reset · account (+ a minimal home). See `docs/DECISIONS.md` 2026-07-20 (Phase 4a).

### ✅ GUEST PORTAL CORE — BUILT (Phase 4b, 2026-07-21, 4 gated sub-gates `28aa439` → `9cac64b` → `79151f1` → HEAD)
**Trips · Reservation detail · Manage Booking · Online Check-in · Arrival Info** — the guest self-service core, each
on the guest guard + guest isolation (resolve from the guard, never a request id; foreign → 404, mutation-proven
per screen). Manage Booking = the **operator-confirmed-request** pattern (invariant #32): the guest proposes a
date-change/add-on/cancellation as a `pending` `guest_change_requests` row, the operator confirms; the guest never
does the real inventory/folio/refund write and can't self-approve. Online Check-in = a guest pre-check-in intent
(`guest_checked_in_at`), NOT the operator's authoritative check-in. Arrival Info = directions/times + door code +
wifi, isolated to the owned booking AND released only within the arrival window (invariant #33). See
`docs/DECISIONS.md` 2026-07-21 (Phase 4b).

### ✅ GUEST MONEY + ENGAGEMENT — BUILT (Phase 4c, 2026-07-22, 4 gated sub-gates `4e172e0` → `9353d16` → `5006c7b` → HEAD) — **THE GUEST PORTAL IS COMPLETE**
**Payments + Folio Receipt + five money PDFs** (card charge account-gated Stripe; VAT invoice reconciles by
construction; refund/credit docs only when a real refund exists) · **the Wallet/credits LEDGER** (append-only,
balance DERIVED, no-overdraw, single-use promo codes, apply-to-booking via an additive
`FolioService::applyWalletCredit()` — **invariant #34**) · **Guest Messages** (the Unified Inbox extended, the third
participant surface — `postGuestMessage`/`markReadByGuest` mirror the owner methods; **invariant #35**) · **Wishlist
+ Waitlist** (real self-only stores; a storefront "Save" ties browse → wishlist; the waitlist notify-trigger + SMS +
auto-hold deferred honestly). Every screen guest-isolated + mutation-proven. See `docs/DECISIONS.md` 2026-07-22.

**G1 — Guest auth foundation:** ✅ BUILT (Phase 4a). **G2 — Core self-service:** ✅ BUILT (Phase 4b). **G3–G4 —
money-facing + engagement:** ✅ BUILT (Phase 4c). The Manage-Booking real EXECUTE (the operator applying a confirmed
request) stays the operator's certified tools. Remaining account-gated/later: card charge/Apple Pay (Stripe), SMS
alerts, loyalty tiers, referral auto-earn, the waitlist notify-trigger + auto-hold, production mail. **The Guest
Portal is complete; the guest Review feeds Phase 5.**

**G3 — Money-facing:** Payments · Folio Receipt (+PDF) · Credits (wallet) · Refund Receipt PDF · Credit Note PDF ·
Statement of Account PDF · VAT Invoice PDF — folio views + a WALLET/credits ledger + more PdfService docs. **(M–L)**

**G4 — Engagement:** Guest Messages (guest side of the inbox) · Review (feeds Reviews) · Wishlist · Waitlist ·
Add-ons · Referral Landing · Email Preferences · Help. **(M–L spread)**

**G5 — Account-gated:** Guest ID Verification (KYC/ID-scan) — needs an external verification provider.

---

## 4. PLATFORM / SaaS LAYER — 5 designed (+2 in emails) · 4 built · 3 remaining

### ✅ Built
Vyrobel Login · Reset Password · Set Password (accept-invite) · Invite Owner (all hardened; the invite modal also
issues staff roles).

### ⬜ Remaining
| Page | What it really is | Size |
|---|---|---|
| ✅ Vyrobel Onboarding | **BUILT (Phase 7a)** — public `/signup` provisions a new operator org atomically (`TenantProvisioningService`: org + 13 provisioned roles + founder + Property Owner + first property in one transaction) and logs the founder in. The chicken-and-egg is escaped via the pre-tenant `runAsSuperAdmin` path; **BOTH deferred RBAC defects fixed** (org-creation now provisions roles; the founder holds the assignable Property Owner). Mutation-proven (atomic rollback, isolation, RBAC state). | — |
| Vyrobel Paywall + Checkout | **SUBSCRIPTION BILLING** — plans, paywall, subscription checkout (Stripe Billing — partly account-gated for live charges); pairs with Operator Billing | L + gated |

---

## 5. TRANSACTIONAL EMAILS — ✅ BUILT in Phase 3 (2026-07-20)

Booking Confirmation · Cancellation · Payment Reminder · Pre-arrival · Refund Confirmation · Review Request ·
Waitlist Notification · Winback · (Guest) Verify Email · + the Email Previews index — **all 9 built** as email-safe
Mailables on the shared brand layout, plus the branded owner/operator notification email. **Real triggers now:**
booking-confirmation / cancellation / refund (on their domain events) + payment-reminder (the
`emails:send-payment-reminders` command, scheduled) + owner/operator notification emails (through the preference
gate). **Triggers deferred honestly** (template built, no fake send): review→Phase 5, waitlist→Phase 4,
verify→Phase 4, pre-arrival/winback→a scheduler. Dev delivery is **real via Mailpit** (proven); PRODUCTION delivery
needs a mail provider — the ONE remaining account-gated piece.

---

## 6. THE CROSS-CUTTING SUBSYSTEMS — and the multi-property correction

Foundations multiple pages sit on; build each once, deliberately.

1. **GUEST AUTH** — unlocks all ~24 Guest Portal pages. (L) — *verified greenfield.*
2. **NOTIFICATIONS** (events → dispatcher → in-app + email + prefs) — unlocks Operator/Owner/Guest Notifications,
   Notification Preferences, and all ~9 transactional emails. (L) — *verified greenfield.*
3. **REVIEWS** (Operator + Owner + Guest surfaces) + **WALLET/credits ledger** (Guest Credits + Manage Booking). —
   *verified greenfield (no reviews/wallet table anywhere).*

⚠️ **MULTI-PROPERTY IS NOT A SCHEMA SUBSYSTEM — the audit's most consequential correction.** The schema ALREADY
supports N properties per org: `properties.organization_id` is a plain FK (no unique constraint); the `property_user`
staff pivot exists with live `User::properties()` / `Property::staff()` relations; `property_id` is already on
`channels`, `groups`, and `rate_plans`. **The limitation is a UI/QUERY convention, not the model.** ~6 files / ~9
call sites resolve "the property" as `Property::query()->orderBy('name')->first()` (`FrontDesk`, `ReservationRack`,
`OperatorDashboard`, `NewReservation`, `OperatorSettings`, …), and 2 org-wide list screens would blend properties.
**Portfolio therefore needs a property-context/selector + a property-CRUD path (none exists) + refactoring those
~8–10 call sites — re-scoped from L-subsystem to M.** (This is Phase 6.) **✅ DONE (Phase 6a, 2026-07-21):** the
`PropertyContext`/selector, the COMPLETE `->first()` refactor (Tier A 10 sites + Tier B 5 blend-risk screens,
grep-clean), property CRUD, and the Portfolio all shipped — mutation-proven the selected context governs, the pivot is
an optional allow-list (empty ⇒ full org). Analytics (ADR/RevPAR/reports) is Phase 6b.

**Two nuances to carry:**
- **Settings pattern:** a generic `settings` KV table + `Setting` model EXIST but are 100% dormant (zero
  readers/writers). The shipped pattern is **typed columns via `SettingsService`** (whitelisted
  property/policy/org fields). **Future settings screens follow the typed-column `SettingsService` pattern — do NOT
  revive the KV scaffold.**
- **Maintenance:** a `category` enum already exists (5 hard-coded values: plumbing/electrical/appliance/structural/
  other). Only **SLA schema** and **configurable** categories are missing.

---

## 7. ACCOUNT-GATED REGISTER (Subhan's parallel track — no code unblocks these)

| Item | What to obtain | Unblocks |
|---|---|---|
| Stripe Connect | Connect onboarding + KYC | Real owner payouts; Payout Method live |
| Stripe live keys | Live card processing | Card capture at booking/checkout; subscription charges |
| Channex account | Sandbox → live | Real OTA sync (Channels live) |
| WhatsApp Business API | Meta approval (weeks) + a BSP | WhatsApp inbox channel |
| OTA messaging API | Booking.com/Airbnb partner access | OTA inbox channels |
| Email provider | Postmark/Mailgun + verified domain | Production email sending |
| ID-verification provider | e.g. Stripe Identity | Guest ID Verification |

**Start Stripe Connect + the email provider NOW** — cheapest to obtain, unblock the most.

---

## 8. THE PHASED DEVELOPMENT PLAN (the build order)

Sequencing logic: **fix what's silently broken → finish the operator+owner apps (Fatima's daily reality) → build
the shared foundations → the Guest Portal on top → the SaaS layer → live integrations as accounts land.** Every
phase = gated Claude Code prompts (audit-first · ⛔ stops · per-gate commits · mutation-proven guards ·
browser-verified · honesty rule — nothing faked · integer money · real enums · tenant/owner/guest isolation
fail-closed). Consolidation between phases.

**PHASE 0 — Fix & harden (~1 week) — the product must not lie to itself** *(✅ DONE — `a7068d1`)*
- **0.1** The inert channel manager: a real `channels.status` writer (a connect flow that sets `status='connected'`
  — the value `EnqueueAriPushOnInventoryChange` gates on) + a connect-flow skeleton (live sync stays account-gated
  behind the interface, honest "Soon") + a **demo channel seed** (the `channels` table is empty after
  `migrate:fresh --seed`). Prove the ARI push now fires for a connected channel.
- **0.2** The dead enums: `reservations.payment_status='refunded'` and `folios.status='closed'` — write them where
  they belong OR remove the UI assumption. Recommend per enum; implement; prove no UI keys an unwritten value.
- **0.3** The 2 fail-closed RBAC defects (no app-level org-creation provisions roles; Super Admin unassignable) —
  fix, or explicitly re-log with the reason each stays (both fail closed; org-creation provisioning naturally
  belongs to Phase 7's tenant-provisioning).
- **0.4** Cleanup: the stale `OperatorSecurity` "2FA Soon" docblock (the code contradicts it).
- **0.5** A full manual QA WALKTHROUGH SCRIPT (`docs/QA-WALKTHROUGH.md`) — every screen, every role, click+expect.

**PHASE 1 — Complete the Operator app, buildable set** *(✅ DONE — 2026-07-20, 5 gated sub-gates `eefd22d` →
`1595583` → `35019e9` → `a4a3ddc` → this gate)*
Operator Search (global; guests name-only, blind-index deferred) → Group Booking (atomic service over existing
`groups`; phantom-event guard) → Roles (security-first; catalog-edit refused, privilege-subset rule) → Maintenance
Costs (real aggregates) + Maintenance Settings (typed columns) → Operator Profile (self-only name/email). Five new
services (`GroupBookingService`, `SearchService`, `RoleAssignmentService`, `MaintenanceCostService`,
`MaintenanceSettingsService`) + `OperatorProfileController`. Deferred out of Phase 1 (logged, not faked): the
**blind-index** contact-search decision, **notification dispatch** (→ Phase 3), and PDF/Excel **exports** of the
cost report.

**PHASE 2 — Complete the Owner portal, buildable set** *(✅ DONE — 2026-07-20, 6 gated sub-gates `0dfc560` →
`598cc9b` → `58f0b49` → `5119548` → `b95228f` → this gate)*
Owner Bookings + Booking Detail (owner-scoped through units) → Block Calendar (over `blockInventory`, now
enforcing `assertUnitFree`) → Owner Rates (FULL owner edit; exclusive-ownership scoping; direct-edit-no-approval
divergence recorded) → Tax Center (+ Annual Tax PDF; genuine held-payout) → Payout Method (encrypted/masked IBAN;
live Stripe Connect rail deferred) → Add a Unit (owner REQUEST flow + operator Unit Requests visibility) → Help
(static). New: `OwnerLedgerAttribution`, `OwnerBlockService`, `OwnerRateService`/`RoomType::primaryRatePlan()`,
`OwnerTaxService` + `PayoutHeldException`, `PayoutMethod`/`Iban`/`OwnerPayoutMethodService`, `UnitRequest`/
`OwnerUnitRequestService`. **Deferred (logged, not faked):** Stripe Connect payout EXECUTION (account-gated);
any operator-approval workflow for owner rates; the operator's unit-CREATION from a request (their manual action).

**PHASE 3 — The NOTIFICATION subsystem + all transactional emails** *(✅ DONE — 2026-07-20, 5 gated sub-gates
`7cca65c` → `7905570` → `640f149` → `6c51271` → this gate)*
Events → `DispatchNotificationsForDomainEvent` → per-user in-app feeds (Operator + Owner Notifications) + a TYPED
`notification_preferences` store that GENUINELY gates delivery (mutation-proven, both channels) → the real events
wired (isolation-scoped) → 9 guest transactional email Mailables (Mailpit-real; booking/cancellation/refund +
payment-reminder command live, the rest template-built with triggers deferred to their phase) → owner/operator
email delivery through the SAME gate → Email Templates view (editing deferred honestly). Live email sending flips
on when the provider account exists (Mailpit covers dev). Deferred honestly (no backing event): performance
digest / new-device / terms-changed; scheduled ones (arrivals, housekeeping-behind, SLA, pre-arrival, winback)
need a scheduler. Detail: `docs/DECISIONS.md` 2026-07-20 (Phase 3).

**PHASE 4a — GUEST AUTH FOUNDATION** *(✅ DONE — 2026-07-20, 4 gated sub-gates `524a1d3` → `70f4d02` → `e2710ff`
→ this gate)*
A SECOND auth realm for guests, alongside the operator/owner `web` realm WITHOUT disturbing it: Guest
Authenticatable on a SEPARATE `guest` guard + a **blind-index** email lookup (the email is encrypted) → BOOKING-TIED
claim + email verification (wired the Phase-3 Verify Email; no free sign-up — a conscious divergence) → guest login
+ password reset (hardened, #28-through-the-blind-index on the reset token table + link) → Guest Account (self-only,
guest-isolation mutation-proven) + safe account removal (revoke access, keep records). REALM SEPARATION +
GUEST ISOLATION + BOOKING-TIED + NO-ENUMERATION all mutation-proven; the existing auth chapter unregressed. A
claimed demo guest (demo.traveller@vyrobel-demo.test / password) is seeded. Detail: `docs/DECISIONS.md` 2026-07-20
(Phase 4a).

**PHASE 4b — the GUEST PORTAL core (on the 4a foundation)** *(✅ DONE — 2026-07-21, 4 gated sub-gates `28aa439` →
`9cac64b` → `79151f1` → HEAD)*
Trips → Reservation detail → **Manage Booking** (date-change/add-on/cancellation as **operator-confirmed requests**,
invariant #32 — the guest proposes, the operator confirms; the guest never does the real write / can't self-approve)
→ **Online Check-in** (a guest pre-check-in intent, NOT the operator's authoritative check-in; passport/KYC deferred
honestly) → **Arrival Info** (directions/times + door code + wifi, isolated to the owned booking AND released only
within the arrival window — invariant #33, never a fabricated code). Every screen guest-isolated + mutation-proven.
A 500 the green suite masked (operator confirm route-model-bound a scoped model → resolved pre-tenant) was found in a
browser + fixed with the string-id pattern, test hardened. Detail: `docs/DECISIONS.md` 2026-07-21 (Phase 4b).

**PHASE 4c — GUEST money + engagement (on the 4b core)** *(✅ DONE — 2026-07-22, 4 gated sub-gates `4e172e0` →
`9353d16` → `5006c7b` → HEAD) — **THE GUEST PORTAL IS COMPLETE***
Payments + Folio Receipt + money PDFs (card charge account-gated) → the Wallet/credits ledger (append-only, DERIVED
balance, no-overdraw — **#34**) → Guest Messages (the Unified Inbox extended, third participant surface — **#35**) →
Wishlist + Waitlist (real self-only stores; storefront "Save" tie; notify-trigger/SMS/auto-hold deferred honestly).
Every screen guest-isolated + mutation-proven. Deferred honestly: card charge/Apple Pay (Stripe), SMS, loyalty
tiers, referral auto-earn, the waitlist notify-trigger + auto-hold. Detail: `docs/DECISIONS.md` 2026-07-22.

**PHASE 5 — The REVIEWS module** *(✅ DONE — 2026-07-23, 4 gated sub-gates `83e9d63` → `fcf52fb` → `814806c` →
HEAD)*
Guest Review (tied to a real completed stay, one per stay — **#36**) → moderation (only published+public is public;
rating DERIVED via the shared `ReviewRating`, never fabricated — **#37**) → Operator Reviews (respond + moderate,
`reviews.manage`, the new-review notification wired) → Owner Reviews (owner-scoped read) → public storefront
(derived rating + published reviews, #28 first-name byline). Every guard mutation-proven. Closed deferrals: the
Phase-3 Review-Request email + new-review notification, and the 4b Trips review link. Deferred honestly:
response-automation + review-gating (a subsystem). Detail: `docs/DECISIONS.md` 2026-07-23.

**PHASE 6a — MULTI-PROPERTY (re-scoped M)** *(✅ DONE — 2026-07-21, 2 gated steps `5589ac0` → this gate)*
Property context/selector (`PropertyContext` + `SetCurrentPropertyForWebRequest` + the topbar switcher; the
`property_user` allow-list = pivot-or-all) + the COMPLETE `->first()` refactor (Tier A: 10 sites/8 files; Tier B: 5
blend-risk list screens; grep-clean, mutation-proven the selected context governs) + property CRUD
(`properties.manage`, org-scoped) + the Portfolio overview (real per-property data, honest "finish setup" state). The
L→M re-scope was correct — the schema already held N properties. See `docs/DECISIONS.md` 2026-07-21 (Phase 6a).

**PHASE 6b — ANALYTICS** *(✅ DONE — 2026-07-21, 3 gated sub-gates `8254e26` → `df9c95e` → this gate)*
The shared metric layer (`App\Support\Analytics\PerformanceMetrics` + `MetricSet` — ADR/RevPAR/occupancy/room revenue,
stay-night attribution, RevPAR = ADR × occupancy exactly, reconciling to FolioTotals + the availability spine; scopes
property/all/owner-units) → operator Insights + Reports (per-property + all-properties, `reports.view`) → owner
Reports + Insights (owner-scoped, earnings reconcile to the ledger) → the real Portfolio comparison. Every figure a
REAL aggregate; the prototype's invented history/narrative REFUSED; lead-time/pace deferred honestly. See
`docs/DECISIONS.md` 2026-07-21 (Phase 6b). **⇒ PHASE 6 (a + b) COMPLETE.**

**PHASE 7a — TENANT PROVISIONING / self-signup** *(✅ DONE — 2026-07-21, 2 gated steps `0c4643d` → this gate)*
`TenantProvisioningService` (atomic: org + provisioned roles + founder + Property Owner + first property in one
transaction; chicken-and-egg escaped via `runAsSuperAdmin`) + the public `/signup` onboarding wizard. **BOTH deferred
Phase-0.3 RBAC defects FIXED:** (a) org-creation now calls `OrganizationRoleProvisioner::provision()` atomically —
RBAC defect #1; (b) the founder holds the ASSIGNABLE `Property Owner` (org admin), resolving the self-provision gap —
RBAC defect #2. Mutation-proven (rollback, isolation, RBAC state). **Residual, re-scoped as a SEPARATE deferred
platform item:** the global `Super Admin` stays unassignable (spatie `model_has_roles` PK rework; MySQL forbids NULL
in a PK) — affects only the Horizon/platform-admin gate, never a tenant founder. See `docs/DECISIONS.md` 2026-07-21.

**PHASE 7b — SUBSCRIPTION BILLING** *(⬅ NEXT)*
Paywall + Checkout + Operator Billing (Stripe Billing; live charges when Stripe live keys exist). Then → Owner Team
(owner sub-tenancy) → Team Schedule / rostering.

**PHASE 8 — Live integrations (as accounts land — parallel throughout)**
Stripe Connect payouts live → card capture live → Channex live sync → WhatsApp/OTA inbox channels → production
email → Guest ID verification.

---

## 9. HOW WE WORK (unchanged — the discipline that got us here)

Every phase = gated prompts (audit-first, ⛔ stops, per-gate commits, mutation-proven guards, browser-verified,
honesty rule — nothing faked, integer money, real enums, tenant/owner/guest isolation fail-closed). Consolidation
between phases. **The prototype is the SPEC for what each screen shows; the repo stays the truth for what backs it;
every gap between them gets BUILT, not faked**, per this plan.

## 10. ONE RECOMMENDATION TO CARRY

Sequence *within* this plan by what makes Fatima OPERATIONAL soonest. Phases 0–3 complete the operator+owner
product for her daily business; the Guest Portal (4) is the biggest phase and the one where her actual guests'
behaviour should shape details. **One question that safely reorders Phases 3 and 4:** *"Which do you need first —
the guest self-service portal, or the notifications/emails?"* Everything else stands as planned.
