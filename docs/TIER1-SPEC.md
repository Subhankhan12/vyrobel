# Vyrobel — Tier 1 "Completion" Screen Specification (build-ready)

**Purpose.** A build-ready spec for the **Tier 1** screens from `docs/ROADMAP.md` — designed-but-unbuilt
screens whose **backend largely already exists**, i.e. styled-UI-over-real-backend work. For each screen: the
design's feature inventory, the **backend reality per feature** (grounded in the real repo — model/service/
column/route named), the write-actions, a strength rating, and a buildability verdict. Ends with a recommended
build order.

**Out of scope (Tier 2, net-new, no backend — one line each, deferred):**
- **Guest Portal** (~24 authenticated screens) — design-only, no guest-auth/self-service backend. DEFER.
- **Reviews / Reputation** — design-only, no reviews schema/service. DEFER.
- **Analytics / Reports** (Operator/Owner Reports + Insights) — design-only, no aggregation layer. DEFER.
- **Subscription / Billing** (Operator Billing, Paywall, Checkout) — schema-only, no billing service. DEFER.
- **Notifications** (feed + preferences) — design-only, no notification backend. DEFER.
- **Email template system** (9 transactional + editor) — design-only, no template store/renderer. DEFER.

---

## ⚠️ Method & honesty note (read first)

The design files in `docs/design/raw/` are **minified, JS-bundled React SPAs** (~0.5–1.6 MB each). Their UI
strings are **not reliably extractable by text search** — confirmed by direct probing (only stray literals
like "VIP"/"Export" survive; most labels are dynamically constructed and return zero). This matches
`docs/design/CATALOGUE.md`'s own caveat that a "pixel-exact component list needs a browser render pass."

Therefore, in this spec:
- **The DESIGN feature inventory** is drawn from the catalogue's **content-verified** "notable extras" (its
  author fingerprinted the dominant content strings) **plus standard PMS screen composition**, and is marked
  *"design intent — exact components not machine-verified from the bundle"* where inferred. Treat it as the
  **shape** to build toward, to be confirmed against the rendered HTML during the actual build (open the file
  in a browser then).
- **The BACKEND reality** (the ✅/🟨/🟥 tags, service methods, model columns, routes) is **rigorously
  repo-verified** — this is the decision-relevant part and is where confidence is high. Every ✅ REAL names the
  real model/service/column/route.

Backend tags: ✅ **REAL** (backing exists) · 🟨 **DERIVABLE** (computable from existing data, not yet exposed)
· 🟥 **NEEDS BACKEND** (no backing yet) · ⬜ **NO-BACKEND/DEFER** (for an unbuilt module — stub/omit per the
honesty rule, invariant #19).

**Verified backend surface this spec relies on** (from the repo):
- Services & methods — `AssignmentService` (createBooking, importBooking, assignUnit, extend, split,
  cancelReservationUnit, cancelReservation, markNoShow, **blockInventory**), `FolioService` (openFolio,
  postCharge, postAddOn, applyTax/Fee, recordPayment, settlePayment, **refundPayment**, authorizeDeposit,
  captureDeposit), `FrontDeskService` (arrivals, departures, inHouse, checkIn, checkOut, moveRoom,
  collectPayment), `HousekeepingService`, `RateAndRestrictionService` (**setRate, setRestriction**),
  `OwnerPayoutService` (**createFromStatement, pay, retry, reverse**), `OwnerStatementService` (generateDraft,
  finalize), `OwnerExpenseService` (recordExpense, postExpense, voidExpense), `OwnerAttributionService`,
  `OwnerPortalService` (units, statements, payouts, occupancy), `InboxService`, `MaintenanceService`,
  `AvailabilityService` (availableOn, availabilityRange), `PdfService` (download, raw), `GuestMatcher`.
- Models with the columns used below — `Guest` (is_vip, risk_flag, marketing_consent, nationality, dob,
  external_refs, encrypted email/phone), `Reservation` (reference, source, status, payment_status,
  balance_minor, notes_guest/internal, group_id, ota_commission_minor), `Property` (type, star_rating, status,
  timezone), `OwnerLedgerEntry` (append-only, entry_type, direction, amount_minor, reservation_id, unit_id,
  statement_id, reverses_id), `OwnerExpense` (category, deductible, amount_minor, voided_at),
  `ManagementAgreement` (commission_pct, management_fee_minor, effective_from/to, attribution_rules,
  payout_schedule), `InventoryBlock` (room_type_id, unit_id, start_date, end_date, reason),
  `Channel` (provider, status, credentials[encrypted], commission_pct, last_error, last_synced_at),
  `ChannelMapping` (kind, external_id, status, last_verified_at), `RateCalendar`, `Restriction`,
  `Payout` (rail, status, amount_minor, owner_statement_id, supersedes_id), `OwnerStatement` (finalized_at,
  locked, net_payable_minor, sequence_no), `Note` (polymorphic), `Tag`, `User` (two_factor_secret/enabled —
  columns only, no flow), spatie roles/permissions (`config/authorization.php`, 14-role catalog).

---

## Summary table

Strength: **SIMPLE** (display over existing data) · **MODERATE** (derived data + a few writes) · **HEAVY**
(many extras, new writes, or partial backend). Buildability: **READY** (pure integration) · **MOSTLY-READY**
(small backend additions listed) · **NEEDS-BACKEND-FIRST** (a backend gate precedes the UI).

**Status (2026-07-17):** the Strength/Buildability ratings below are the **pre-build** assessment and are kept
as the record of what we predicted. **Wave 1 shipped 2026-07-16; Wave 2 shipped 2026-07-17** — see
*Recommended build order* for what each wave actually shipped, and the per-screen sections for the decisions
that resolved their open questions.

### Operator

| Screen | Route / stub | Strength | Buildability |
|---|---|---|---|
| Reservations list | `/app/reservations` — net-new | SIMPLE | **READY** |
| Reservation Cancelled (state) | reuse `/app/reservations/{id}` | SIMPLE | **READY** |
| Reservation No-show (state) | reuse `/app/reservations/{id}` | SIMPLE | **READY** |
| New Reservation (wizard) | `/app/reservations/create` — net-new | HEAVY | **MOSTLY-READY** |
| Group Booking | `/app/reservations/group` — net-new | HEAVY | **MOSTLY-READY** |
| Refund (dedicated) | reuse folio actions — net-new page | MODERATE | **READY** |
| Guests CRM (list) | `/app/guests` — net-new | MODERATE | **READY** |
| Guest Profile | `/app/guests/{guest}` — net-new | MODERATE | **MOSTLY-READY** |
| Portfolio (multi-property) | `/app/portfolio` — net-new | MODERATE | **MOSTLY-READY** |
| Global Search | `/app/search` — net-new | MODERATE | **NEEDS-BACKEND-FIRST** |
| Channels config | `/app/channels` — net-new | HEAVY | **MOSTLY-READY** |
| Rates & availability grid | `/app/rates` — net-new | HEAVY | **MOSTLY-READY** |
| Payout Run (batch) | `/app/payouts/run` — net-new | MODERATE | **READY** |
| Operator Owner Detail | `/app/owners/{owner}` — net-new | MODERATE | **READY** |
| Maintenance Costs (analytics) | `/app/maintenance/costs` — net-new | MODERATE | **MOSTLY-READY** |
| Maintenance Settings | `/app/maintenance/settings` — net-new | MODERATE | **NEEDS-BACKEND-FIRST** |
| Roles & permissions | `/app/roles` — net-new | MODERATE | **MOSTLY-READY** |
| Security / audit center | `/app/security` — net-new | MODERATE | **MOSTLY-READY** (2FA gated) |
| Settings | `/app/settings` — net-new | MODERATE | **MOSTLY-READY** |
| Property Settings | `/app/properties/{p}/settings` — net-new | MODERATE | **MOSTLY-READY** |
| Profile | `/app/profile` — net-new | SIMPLE | **MOSTLY-READY** (2FA gated) |

### Owner

| Screen | Route / stub | Strength | Buildability |
|---|---|---|---|
| Owner Expenses | `/owner/expenses` — net-new | SIMPLE | **READY** |
| Owner Balance (ledger) | `/owner/balance` — net-new | SIMPLE | **READY** |
| Owner Agreement | `/owner/agreement` — net-new | SIMPLE | **READY** |
| Owner Unit Detail | `/owner/units/{unit}` — net-new | MODERATE | **READY** |
| Owner Block Calendar | `/owner/units/{unit}/blocks` — net-new | HEAVY | **READY** |
| Owner Bookings (list) | `/owner/bookings` — net-new | MODERATE | **MOSTLY-READY** |
| Owner Booking Detail | `/owner/bookings/{res}` — net-new | MODERATE | **MOSTLY-READY** |
| Owner Remittance Advice PDF | controller — net-new | SIMPLE | **READY** |
| Owner Booking Summary PDF | controller — net-new | SIMPLE | **READY** |
| Owner Documents | `/owner/documents` — net-new | SIMPLE | **MOSTLY-READY** |
| Owner Archive | `/owner/archive` — net-new | SIMPLE | **READY** |
| Owner Messages (inbox) | `/owner/inbox` — net-new | MODERATE | **READY** |
| Owner Team | `/owner/team` — net-new | MODERATE | **MOSTLY-READY** |
| Owner Settings / Security | `/owner/settings` — net-new | MODERATE | **MOSTLY-READY** (2FA gated) |
| Owner Rates | `/owner/rates` — net-new | MODERATE | **MOSTLY-READY** (policy gate) |
| Owner Add Unit (wizard) | `/owner/units/add` — net-new | MODERATE | **MOSTLY-READY** |

### Auth / Onboarding

| Screen | Route / stub | Strength | Buildability |
|---|---|---|---|
| Operator Onboarding (wizard) | `/app/onboarding` — net-new | HEAVY | **MOSTLY-READY** |
| Owner Onboarding (wizard) | `/owner/onboarding` — net-new | MODERATE | **MOSTLY-READY** |
| 2FA setup/verify | in Profile/Security — net-new | MODERATE | **NEEDS-BACKEND-FIRST** |

**Account-gated (NOT Tier 1 — listed for completeness, belongs to ROADMAP Tier 3):** `Owner Payout Method`
(🔴 Stripe Connect KYC), Stripe card checkout, real inbox channels, Channex live, production email.

---

## Operator — per-screen detail

### Reservations list — `Operator Reservations.html`
- **STATUS: ✅ BUILT — Wave 2 · sub-gate 1 (2026-07-17).** Live at `/app/reservations` (`ReservationsList`).
  **Decision that resolved the status semantics:** a *stage* is **not** a *status*.
  `App\Support\Reservations\ReservationStage` holds the badge **and** the SQL filter (`for()` + `scope()`) in
  ONE class, so a filter chip cannot drift from what it filters. The design showed "Completed" and "In house"
  as if they were statuses — they are not in the 7-value enum ("Completed" is `checked_out`; "In house" is the
  *stage* of a `checked_in` reservation). No enum value was invented; `DUE` and `CANCELLED` are separate stages
  because folding them into the three headline buckets would lie.
- **Route/stub:** net-new `/app/reservations` (only the *detail* screen exists today). Livewire list component.
- **Design contains (intent):** paginated reservations table (guest, dates, room/unit, source badge, status
  badge, balance); **filters** (status, source/channel, date range, property); **search**; **bulk actions**;
  empty/toast states; row → detail. *(Exact filter set not machine-verified from the bundle.)*
- **Backend reality:**
  - Table rows, all columns — ✅ REAL (`Reservation`: reference, check_in/out, source, status, payment_status,
    balance_minor; `RoomType`/`Unit` via `reservation_units`).
  - Status/source badges — ✅ REAL (real enums; warm-only per invariant #19; `FormatsOperatorData` labels).
  - Filters (status/source/date/property) — 🟨 DERIVABLE (query scopes over existing columns).
  - Balance column — ✅ REAL (`reservations.balance_minor`).
  - Bulk actions — 🟥 NEEDS BACKEND *only if* they mutate (e.g. bulk-cancel); a bulk **read/export** is 🟨.
- **Writes:** none required for the list itself (row actions route to existing detail writes). Bulk-cancel, if
  built, loops `AssignmentService::cancelReservation` (exists).
- **Strength:** SIMPLE. **Biggest risk:** none material — it's a table over a fully-modeled entity; watch the
  scoped-render discipline (resolve in `render()`, no RMB) and keep filters as GET params (not wire-actions).
- **Verdict:** **READY.**

### Reservation Cancelled / No-show (states) — `Operator Reservation Cancelled.html`, `Operator Reservation No-show.html`
- **STATUS: ✅ BUILT — Wave 2 · sub-gate 2 (2026-07-17).** Both terminal states render on
  `/app/reservations/{id}`; cancel and no-show are guarded POSTs (`ReservationLifecycleController` →
  `AssignmentService::cancelReservation` / `markNoShow`), exactly as the "biggest risk" line below called for.
  Fee engines (cancellation/no-show fees) were deliberately deferred, not faked.
- **Route/stub:** reuse `/app/reservations/{id}` (`ReservationDetail`) with state-conditional rendering.
- **Design contains (intent):** the reservation detail rendered in a terminal state — cancellation/no-show
  banner, the folio showing the reversal/charge ledger, refund affordance.
- **Backend reality:**
  - Cancelled/no-show state — ✅ REAL (`Reservation.status`; `AssignmentService::cancelReservation` /
    `markNoShow` already exist and are tested).
  - Folio reversal/charge lines — ✅ REAL (append-only `FolioService`, correct-by-reversal).
  - Refund — ✅ REAL (`FolioService::refundPayment`, already wired into the detail screen).
- **Writes:** cancel (`cancelReservation`), no-show (`markNoShow`), refund (`refundPayment`) — **all exist.**
  Currently the styled detail defers cancel/no-show affordances (per PROJECT-STATE); this just surfaces them
  via the operator-mutation pattern (POST→controller→service→PRG).
- **Strength:** SIMPLE. **Biggest risk:** wiring cancel/no-show as guarded POST actions (not wire-actions).
- **Verdict:** **READY.**

### New Reservation (wizard) — `Operator New Reservation.html`
- **Route/stub:** net-new `/app/reservations/create`. Multi-step wizard.
- **Design contains (intent):** a **stepper** (dates → availability/room selection → guest → rate plan →
  review/confirm); live availability/occupancy check; rate-plan pick; price preview; guest search-or-create.
  *(Stepper confirmed as the screen's dominant pattern in the catalogue; step details are design intent.)*
- **Backend reality:**
  - Availability check — ✅ REAL (`AvailabilityService::availabilityRange` / `availableOn`).
  - Booking write — ✅ REAL (`AssignmentService::createBooking` — the single guarded write path; rejects when
    full, deterministic locking).
  - Guest search-or-create — ✅ REAL (`GuestMatcher::matchOrCreate`; `Guest` model).
  - Rate/price preview — ✅ REAL (`RateCalendar` / `RateAndRestrictionService`; `FolioService` for totals).
  - Folio open on book — ✅ REAL (`FolioService::openFolio`).
- **Writes:** create booking (`createBooking` — exists). No new service needed.
- **Strength:** HEAVY (multi-step state, live availability, guest linkage). **Biggest risk:** wizard state must
  not leak scope — availability re-checks and the final book must go through guarded POST/service calls, not
  Livewire wire-actions on scoped data (invariant #20). The *backend is entirely there*; complexity is UI/UX.
- **Verdict:** **MOSTLY-READY** — no new backend; the only "addition" is a thin controller orchestrating the
  existing `createBooking` + a guest-lookup endpoint. Rate it MOSTLY-READY due to wizard complexity, not
  backend gaps.

### Group Booking — `Operator Group Booking.html`
- **Route/stub:** net-new `/app/reservations/group`.
- **Design contains (intent):** multi-room group flow — a group header, N room lines, shared or split folios,
  block-hold, group guest list.
- **Backend reality:**
  - Group entity — ✅ REAL (`Group` model + `reservations.group_id`).
  - Multi-room booking — ✅ REAL (loop `AssignmentService::createBooking` per room, or a multi-line booking;
    `assignUnit`/`split` exist).
  - Shared folio / group billing — 🟨 DERIVABLE / 🟥 partial (folios are per-reservation; a *group folio roll-up*
    would be a new derivation, not a new table).
- **Writes:** create group + member reservations. **No dedicated `createGroupBooking` method exists** — it
  would be a thin orchestration over `createBooking` + `Group`.
- **Strength:** HEAVY. **Biggest risk:** group-billing semantics (shared vs split) — decide before building;
  the atomic multi-room booking is the other risk (all-or-nothing across N `createBooking` calls).
- **Verdict:** **MOSTLY-READY** — needs a small `AssignmentService`/orchestration method for atomic group
  create + a group-folio roll-up decision. Everything underneath exists.

### Refund (dedicated) — `Operator Refund.html`
- **STATUS: ✅ BUILT — Wave 2 · sub-gate 3 (2026-07-17).** Live at `/app/reservations/{id}/refund`
  (`ReservationRefund`), POSTing to the existing `reservations.refund` action (now amount-aware). **The screen
  exists to respect invariant #27 — the cumulative refund bound — at the UI:** it shows the real
  *remaining-refundable* per payment and caps the input at it. ⚠️ #27 was closed in `0b9c800` **after** this
  spec was written: the "append-only correctness" note below was **necessary but not sufficient** — append-only
  (#7) bounds EDITS, #27 bounds the SUM, and a €369 payment took three full refunds while both reads of this
  spec's advice were satisfied.
- **Route/stub:** net-new page (or modal) — refund flow. The action already exists inside `ReservationDetail`.
- **Design contains (intent):** refund form (amount, reason, method), folio preview, toggles (full/partial).
- **Backend reality:**
  - Refund write — ✅ REAL (`FolioService::refundPayment`; append-only new offsetting entry, invariant #7).
  - Partial refund — ✅ REAL (`refundPayment` takes an amount).
- **Writes:** `refundPayment` (exists). Currently full-refund is wired; a dedicated screen adds partial + reason.
- **Strength:** MODERATE. **Biggest risk:** enforcing append-only correctness (never edit the original) — the
  service already guarantees it; the UI must just call it.
- **Verdict:** **READY.**

### Guests CRM — list `Operator Guests.html` + profile `Operator Guest Profile.html`
- **STATUS: ✅ BUILT — Wave 2 · sub-gates 4–5 (2026-07-17).** List `/app/guests` (`GuestsList`) + profile
  `/app/guests/{guest}` (`GuestProfile`); writes via `GuestActionController` → `GuestService`.
  **The decisions that resolved this screen's open questions:**
  - **Lifetime spend — DONE, and the answer is BILLED.** `App\Support\Guest\GuestSpend`, labelled
    **"Total billed"**, with the real **open balance BESIDE it**. The design's single figure netted a refund
    off AND counted unpaid charges — **it could not be both**, so the two are shown as the two separate facts
    they are. Shared by the list and the profile, so they cannot disagree.
  - **Stays / nights / "guest since" — DONE.** `App\Support\Guest\GuestHistory`, **shared with the inbox's
    guest card** — which already rendered "N stays · guest since YYYY" counting cancellations, so the inbox and
    the CRM were about to say "3 stays" and "2 stays" about the same person on the same afternoon, both
    sincerely. A stay = a non-cancelled reservation, keyed on `ReservationStage::CANCELLED_STATUSES` so it
    cannot drift from the stage badge either.
  - **`Guest::reservations()` now exists** — keyed on `reservations.lead_guest_id`, the FK every built operator
    surface already treats as "the guest of this booking". The `reservation_guests` pivot is **deliberately
    excluded**: including it would make "reservations of this guest" mean two different things on two screens.
  - **Search is name-only** — email/phone are encrypted at rest (see *Global Search* below). The page **SHOWS
    contact detail it cannot SEARCH**, and its footer says so outright rather than silently returning nothing.
- **Route/stub:** net-new `/app/guests` (list) + `/app/guests/{guest}` (profile).
- **Design contains (intent, list):** guest table; **filters** (VIP, risk, nationality, marketing consent);
  **search**; **export**; **VIP / risk badges**; lifetime spend + last-stay columns; row → profile.
  *(Confirmed literals in the bundle: "VIP", "Tag", "Export", "Lifetime", "Last stay", "Add guest".)*
- **Design contains (intent, profile):** guest header (name, contact, VIP/risk), stay history, folio history,
  notes, tags, messages, edit modal.
- **Backend reality:**
  - Guest rows, VIP/risk/nationality/consent — ✅ REAL (`Guest`: is_vip, risk_flag, nationality,
    marketing_consent, dob).
  - **PII (email/phone)** — ✅ REAL but **encrypted**; renders on the staff surface (guest NAME always; contact
    per policy). Never expose on non-staff surfaces (invariant #20).
  - Notes / tags — ✅ REAL (polymorphic `Note`, `Tag`).
  - Stay history — ✅ REAL (`Reservation` where lead_guest_id / `reservation_guests`).
  - **Lifetime spend / last stay** — 🟨 DERIVABLE (aggregate folios/payments per guest; not a stored column).
    **Now derived — `GuestSpend` (billed + open balance) + `GuestHistory` (stays/nights/since).**
  - Export — 🟨 DERIVABLE (CSV of the query; note CSV export is a standing deferral — flag it).
- **Writes:** edit guest, add note/tag, `anonymize()` (GDPR — exists). Guest create = Eloquent/`GuestMatcher`.
- **Strength:** MODERATE. **Biggest risk:** the lifetime-spend aggregation (derive, don't fake) and PII
  discipline (encrypted contact, name-only where appropriate).
- **Verdict:** list **READY**; profile **MOSTLY-READY** (needs the lifetime-spend/last-stay derivation helper).
  **Both BUILT (2026-07-17) — the derivation shipped as `GuestSpend` + `GuestHistory`; the helper the profile
  was waiting on is the one that also stopped the inbox and the CRM disagreeing.**

### Portfolio (multi-property) — `Operator Portfolio.html`
- **Route/stub:** net-new `/app/portfolio`.
- **Design contains (intent):** per-property cards/rows with occupancy + RevPAR/ADR + revenue; property switcher.
- **Backend reality:**
  - Properties — ✅ REAL (`Property`: type, star_rating, status, timezone).
  - Per-property occupancy — 🟨 DERIVABLE (`AvailabilityService` summed per property — same technique as the
    operator dashboard's org-wide occupancy).
  - **RevPAR / ADR** — 🟥 NEEDS BACKEND (no analytics aggregation layer — this overlaps ROADMAP Tier 2
    Analytics). Show occupancy + revenue (derivable); **defer/stub ADR/RevPAR** per the honesty rule.
- **Writes:** none (read-only overview).
- **Strength:** MODERATE. **Biggest risk:** not over-promising the analytics — occupancy/revenue are
  derivable; ADR/RevPAR trends are Tier-2 territory and must be stubbed "Soon", not fabricated.
- **Verdict:** **MOSTLY-READY** — buildable as an occupancy/revenue overview now; the ADR/RevPAR cards defer.

### Global Search — `Operator Search.html`
- **Route/stub:** net-new `/app/search`.
- **Design contains (intent):** cross-entity results (reservations, guests, units/villas, owners) grouped, with
  quick-nav.
- **Backend reality:**
  - Entity data — ✅ REAL (all entities exist).
  - **A search query layer** — 🟥 NEEDS BACKEND (no unified search service; guest email/phone are encrypted so
    not directly LIKE-searchable — a blind-index is a standing Phase-1 deferral). Reference/name/unit-code
    search is buildable; encrypted-field search is gated on the deferred blind-index.
- **Writes:** none.
- **Strength:** MODERATE. **Biggest risk:** encrypted-PII search — cannot search guest email/phone without the
  deferred blind index; scope search to non-encrypted fields (reference, guest name, unit code) and flag the
  limitation.
- **⚠️ Wave 2 PROVED this concretely (2026-07-17), so it is no longer a prediction:** the Guests CRM search is
  **name-only**, and the screen **shows contact detail it cannot search** — its footer says so outright
  ("Search covers names only — email and phone are encrypted at rest, so they cannot be searched"). The
  blind-index gap is already visible in the shipped product; Global Search will hit exactly the same wall, and
  the CRM's footer is the precedent for how to flag it honestly rather than silently returning nothing.
- **Verdict:** **NEEDS-BACKEND-FIRST** — a (small) search service must precede the UI; and full guest-contact
  search needs the deferred blind-index.

### Channels config — `Operator Channels.html`
- **Route/stub:** net-new `/app/channels`.
- **Design contains (intent):** channel cards (provider, status, last-sync, error), **connect stepper**,
  **commission** field, room-type/rate-plan **mapping** editor, sync status, per-channel enable/disable.
  *(Catalogue-confirmed: "commission", connect "stepper", sync status.)*
- **Backend reality:**
  - Channel cards, status, last_error, last_synced_at, commission_pct — ✅ REAL (`Channel` model, all columns).
  - Credentials (encrypted vault) — ✅ REAL (`Channel.credentials` encrypted cast).
  - Mappings (room_type/rate_plan → external id, health) — ✅ REAL (`ChannelMapping`: kind, external_id,
    status, last_verified_at; `ChannelMappingGuard` for read-resolution).
  - ARI push / import pipeline — ✅ REAL (certified; `AriSnapshotBuilder`, sync jobs).
  - **Live connect to a real Channex account** — 🔴 ACCOUNT-GATED (the config *UI* is buildable now against the
    fake; a genuine connect needs Channex staging — ROADMAP Tier 3).
- **Writes:** create/edit channel, create/edit/activate mapping, enable/disable. **No dedicated CRUD service
  exists** — these are simple Eloquent writes (models are fully modeled); a thin controller suffices. Reads/
  resolution already go through `ChannelMappingGuard`.
- **Strength:** HEAVY (many sub-panels + mapping editor + connect stepper). **Biggest risk:** the mapping editor
  UX and keeping *live connect* honestly stubbed (config now, real sync gated on Channex staging).
- **Verdict:** **MOSTLY-READY** — needs a small channel/mapping CRUD controller; live sync stays honestly gated.

### Rates & availability grid — `Operator Rates.html`
- **Route/stub:** net-new `/app/rates`.
- **Design contains (intent):** a rate-plan × date **grid** (price per night), **restrictions** (min/max stay,
  CTA/CTD, stop-sell), channel-push indicator, heavy filters, bulk edit across a date range.
- **Backend reality:**
  - Rate grid (price/night) — ✅ REAL (`RateCalendar`: price_minor, per rate_plan/date;
    `RateAndRestrictionService::setRate`).
  - Restrictions — ✅ REAL (`Restriction`: min_stay, max_stay, closed_to_arrival/departure, stop_sell;
    `RateAndRestrictionService::setRestriction`).
  - Channel push on edit — ✅ REAL (setRate/setRestriction emit domain events → `EnqueueAriPushOnInventoryChange`).
  - Availability shown alongside — ✅ REAL (`AvailabilityService::availabilityRange`).
- **Writes:** `setRate`, `setRestriction` — **both exist** and already emit the ARI push. Bulk = loop over a
  range.
- **Strength:** HEAVY (grid UI + bulk range edits + two write types). **Biggest risk:** the grid editing UX and
  bulk-range writes; guarded-mutation discipline (each cell/bulk save is a POST→service, not a wire-action).
  Backend is fully present.
- **Verdict:** **MOSTLY-READY** — no backend gap; rated MOSTLY-READY for grid/bulk-edit UI complexity only.

### Payout Run (batch) — `Operator Payout Run.html`
- **Route/stub:** net-new `/app/payouts/run`.
- **Design contains (intent):** select finalized statements → run payouts, batch summary, export, status.
- **Backend reality:**
  - Finalized statements — ✅ REAL (`OwnerStatement`: finalized_at, locked, net_payable_minor).
  - Create payout from statement — ✅ REAL (`OwnerPayoutService::createFromStatement`).
  - Pay / retry / reverse — ✅ REAL (`OwnerPayoutService::pay/retry/reverse`; `ManualPayoutRail` is the real,
    tested rail).
  - **Stripe Connect disbursement** — 🔴 ACCOUNT-GATED (`StripeConnectPayoutRail` throws; manual rail works now).
- **Writes:** createFromStatement, pay (both exist). Batch = loop over selected finalized statements.
- **Strength:** MODERATE. **Biggest risk:** honest rail labeling — manual payouts are real; Connect is gated.
  Positive-net/finalized-only rules already enforced by the service (invariant #17).
- **Verdict:** **READY** (via `ManualPayoutRail`; Connect stays gated).

### Operator Owner Detail — `Operator Owner Detail.html`
- **Route/stub:** net-new `/app/owners/{owner}` (the Owners *list* `/app/owners` already exists as
  `OperatorOwners`).
- **Design contains (intent):** one owner's gross/commission/net, statements list, payouts, units, agreement.
- **Backend reality:**
  - All figures — ✅ REAL (owner-accounting services + `OwnerLedgerEntry`, `OwnerStatement`, `Payout`, `Owner`,
    `ManagementAgreement`). This is the **operator-side (tenant-scoped, not owner-scoped) lens** on the same
    certified data the owner portal already renders.
- **Writes:** invite (exists via `OwnerInviteService`), generate/finalize statement (`OwnerStatementService`),
  create payout (`OwnerPayoutService`). All exist.
- **Strength:** MODERATE. **Biggest risk:** scope — this is tenant-scoped (operator sees all their owners), NOT
  the owner portal's owner-scope; don't accidentally apply `OwnerScope`.
- **Verdict:** **READY.**

### Maintenance Costs (analytics) — `Operator Maintenance Costs.html`
- **Route/stub:** net-new `/app/maintenance/costs`.
- **Design contains (intent):** cost **trend** charts, per-unit/category breakdown, export.
- **Backend reality:**
  - Cost data — ✅ REAL (`MaintenanceTicket` cost + `OwnerExpense` where source = ticket).
  - **Trend aggregation / charts** — 🟨 DERIVABLE (group costs by period/unit/category — a new derivation, no
    new table).
- **Writes:** none.
- **Strength:** MODERATE. **Biggest risk:** it's really a mini-analytics screen — buildable by derivation, but
  overlaps Tier-2 Analytics; keep it to real aggregations, no fabricated benchmarks.
- **Verdict:** **MOSTLY-READY** (needs a cost-aggregation helper; data is all there).

### Maintenance Settings — `Operator Maintenance Settings.html`
- **Route/stub:** net-new `/app/maintenance/settings`.
- **Design contains (intent):** SLA definitions, categories, default assignees, toggles.
- **Backend reality:**
  - **SLA / category config** — 🟥 NEEDS BACKEND (no SLA or category schema; `MaintenanceTicket` has no
    category/SLA columns today).
- **Writes:** would need new config tables + service.
- **Strength:** MODERATE. **Biggest risk:** it's a config surface with no backing schema.
- **Verdict:** **NEEDS-BACKEND-FIRST** (add category/SLA config first). Lowest priority in the cluster.

### Roles & permissions — `Operator Roles.html`
- **Route/stub:** net-new `/app/roles`.
- **Design contains (intent):** role list + permission **matrix**, assign users, edit modal.
- **Backend reality:**
  - Roles/permissions — ✅ REAL (spatie teams mode; 14-role catalog in `config/authorization.php`;
    `OrganizationRoleProvisioner`).
  - Assign role to user — ✅ REAL (spatie API; `Membership`).
  - **Custom per-org roles / editable permission sets** — 🟨/🟥 (the catalog is fixed; *creating custom roles*
    or editing permission sets per org is new — reading/assigning the catalog is REAL).
- **Writes:** assign/revoke role (spatie — exists). Custom-role CRUD = new.
- **Strength:** MODERATE. **Biggest risk:** deciding fixed-catalog (READY) vs editable custom roles (new
  backend). Ship the read + assign matrix first.
- **Verdict:** **MOSTLY-READY** (read + assign over the fixed catalog now; custom-role editing deferred).

### Security / audit center — `Operator Security.html`
- **Route/stub:** net-new `/app/security`. *(Design file is ~2.2 MB — the heaviest.)*
- **Design contains (intent):** audit-event log + export, 2FA management, session/device list, Stripe/API
  security, toasts.
- **Backend reality:**
  - Audit log — ✅ REAL (`AuditEvent` model; every state change writes one — invariant "Events").
  - Audit export — 🟨 DERIVABLE (CSV of audit rows — CSV is a standing deferral, flag it).
  - **2FA** — 🟥 NEEDS BACKEND (columns `two_factor_secret/enabled` exist, **no flow**).
  - Sessions/devices — 🟨 DERIVABLE (Laravel sessions table) / 🟥 (device tracking not modeled).
- **Writes:** 2FA enable/disable (needs the flow). Audit log is read-only.
- **Strength:** MODERATE. **Biggest risk:** 2FA is unbuilt; the audit-log view is READY, the 2FA panel is
  NEEDS-BACKEND.
- **Verdict:** **MOSTLY-READY** for the audit-log view; **2FA is NEEDS-BACKEND-FIRST** (split the screen).

### Settings / Property Settings / Profile — `Operator Settings.html`, `Operator Property Settings.html`, `Operator Profile.html`
- **Route/stub:** net-new `/app/settings`, `/app/properties/{p}/settings`, `/app/profile`.
- **Design contains (intent):** org/commission/tax settings + tabs + toggles + logo upload (Settings);
  folio/tax/policy config (Property Settings); user profile + password + 2FA + avatar (Profile).
- **Backend reality:**
  - Org/user/property/policy data — ✅ REAL (`Setting`, `Organization`, `User`, `Property`, `PropertyPolicy`).
  - Tax/fee config — ✅ REAL (`Tax`, `Fee` models).
  - Logo/avatar upload — ✅ REAL (secure media pattern already exists — `HasPhotos`/media, RoomTypePhotoController
    as the template).
  - **Some toggles** — 🟥 NEEDS BACKEND (any setting key not yet modeled needs a `Setting` row/definition).
  - **2FA in Profile** — 🟥 NEEDS BACKEND (no flow).
- **Writes:** update settings/policies/profile (Eloquent; media upload pattern exists). 2FA = new.
- **Strength:** MODERATE. **Biggest risk:** scope-creep across many toggles — build the backed ones, stub the
  rest honestly.
- **Verdict:** **MOSTLY-READY** (backed fields now; unmodeled toggles + 2FA deferred).

---

## Owner — per-screen detail

*(All owner screens inherit the owner design system — `<x-owner.shell>`, `FormatsOwnerData` — and the
**owner-scope** middleware `SetOwnerScopeForPortal` + fail-closed `OwnerScope` (invariant #18). Resolve models
owner+tenant-scoped in `render()`, never RMB. Reads flow through `OwnerPortalService` where it fits.)*

### Owner Expenses — `Owner Expenses.html`
- **Route/stub:** net-new `/owner/expenses`.
- **Design contains (intent):** expense list (date, category, amount, deductible flag, source e.g.
  maintenance), filters, export.
- **Backend reality:** ✅ REAL (`OwnerExpense`: category, deductible, amount_minor, expense_date, voided_at,
  source_type/id; `OwnerExpenseService`). Owner-scoped via `OwnerScope` (has `BelongsToOwner`).
- **Writes:** none for the owner (expenses are operator-posted; owner view is read-only). Export = CSV (deferral).
- **Strength:** SIMPLE. **Risk:** none material. **Verdict:** **READY.**

### Owner Balance (ledger) — `Owner Balance.html`
- **Route/stub:** net-new `/owner/balance`.
- **Design contains (intent):** running balance / ledger view (net payable, entries, balance over time), export.
- **Backend reality:** ✅ REAL (`OwnerLedgerEntry` — append-only, entry_type, direction, amount_minor,
  posted_at, statement_id). Running balance 🟨 DERIVABLE (cumulative sum — invariant #16 guarantees it nets
  correctly).
- **Writes:** none (read-only ledger). **Strength:** SIMPLE. **Verdict:** **READY.**

### Owner Agreement — `Owner Agreement.html`
- **Route/stub:** net-new `/owner/agreement`.
- **Design contains (intent):** management terms — commission %, management fee + interval, effective dates,
  payout schedule, attached documents.
- **Backend reality:** ✅ REAL (`ManagementAgreement`: commission_pct, management_fee_minor,
  management_fee_interval, effective_from/to, payout_schedule, attribution_rules; `resolveFor()`).
  Documents 🟨 (polymorphic `Document` exists; attach if used).
- **Writes:** none (owner-read; operator sets terms). **Strength:** SIMPLE. **Verdict:** **READY.**

### Owner Unit Detail — `Owner Unit Detail.html`
- **Route/stub:** net-new `/owner/units/{unit}` (the units *list* `/owner/units` exists).
- **Design contains (intent):** one unit — occupancy calendar, revenue/performance, commission, bookings.
- **Backend reality:** ✅ REAL (`Unit`; `OwnerPortalService::occupancy` — already owner-scoped, excludes
  non-owner units per the My Units precedent); per-unit revenue 🟨 DERIVABLE (`OwnerLedgerEntry` where unit_id).
  **ADR/RevPAR trend charts** 🟥 (Tier-2 analytics — stub).
- **Writes:** none. **Strength:** MODERATE. **Risk:** owner-scoped occupancy correctness (reuse the My Units
  logic — don't leak room-type-wide numbers). **Verdict:** **READY** (occupancy + revenue; trends defer).

### Owner Block Calendar — `Owner Block Calendar.html`
- **Route/stub:** net-new `/owner/units/{unit}/blocks`.
- **Design contains (intent):** a **drag calendar** to block/unblock unit dates (owner reserves their own
  unit), reason, toasts.
- **Backend reality:** ✅ REAL — **`AssignmentService::blockInventory` exists** (the guarded write path;
  `InventoryBlock`: room_type_id, unit_id, start_date, end_date, reason). Availability recompute already
  consumes blocks.
- **Writes:** blockInventory (exists). Unblocking = release the block (verify a delete/release path;
  `cancelReservationUnit` handles reservation lines — confirm the block-removal method during build).
- **Strength:** HEAVY (drag calendar UX). **Biggest risk:** (1) the drag interaction must NOT be a Livewire
  wire-action on scoped data — use a client calendar that POSTs to a guarded controller → `blockInventory`
  (invariant #20); (2) confirm a block-**removal** path exists (block create is proven; removal may need a thin
  method). **Verdict:** **READY** (create proven; confirm/expose block-removal — small).

### Owner Bookings (list) + Owner Booking Detail — `Owner Bookings.html`, `Owner Booking Detail.html`
- **Route/stub:** net-new `/owner/bookings`, `/owner/bookings/{res}`.
- **Design contains (intent):** owner-lens list of bookings on their units (gross, commission, net, timeline),
  charts/trend, detail with earnings breakdown.
- **Backend reality:** 🟨 DERIVABLE — reservations attributed to the owner via `OwnerLedgerEntry.reservation_id`
  (+ unit_id) join back to `Reservation`. The *earnings* per booking are REAL (owner_ledger); the **owner-lens
  booking query** is not currently exposed (`OwnerPortalService` has units/statements/payouts/occupancy, not
  bookings). Charts/trend 🟥 (Tier-2 — stub).
- **Writes:** none. **Strength:** MODERATE. **Biggest risk:** building the owner-scoped booking query correctly
  (join ledger→reservation, owner-scoped, no PII leak beyond guest name). **Verdict:** **MOSTLY-READY** (add an
  `OwnerPortalService::bookings()` derivation).

### Owner Remittance Advice PDF + Booking Summary PDF — `Owner Remittance Advice PDF.html`, `Owner Booking Summary PDF.html`
- **Route/stub:** net-new controllers (mirror `OwnerStatementPdfController` + `Support\Pdf\OwnerStatementDocument`).
- **Design contains (intent):** print/export of remittance (net payable/gross/commission per payout) and
  booking summary (gross/commission/occupancy).
- **Backend reality:** ✅ REAL (`PdfService` + `Payout`/`OwnerStatement`/`OwnerLedgerEntry` data; the owner
  statement PDF is already built and reconciles to the cent — same pattern).
- **Writes:** none (generate/stream PDF). **Strength:** SIMPLE. **Risk:** reconcile-to-the-cent discipline
  (shared `MoneyFormatter`, no invented lines — invariant #19). **Verdict:** **READY.**

### Owner Documents + Owner Archive — `Owner Documents.html`, `Owner Archive.html`
- **Route/stub:** net-new `/owner/documents`, `/owner/archive`.
- **Design contains (intent):** a library of owner docs (statements, remittances, agreements) + archived items.
- **Backend reality:** 🟨 DERIVABLE — statements/payouts/PDFs are all existing rows; a "documents" view is a
  listing over them. A generic polymorphic `Document` exists if attachments are needed. Archive = finalized/old
  statements (✅ `OwnerStatement.status`/sequence).
- **Writes:** download (exists). **Strength:** SIMPLE. **Verdict:** Documents **MOSTLY-READY** (aggregation
  view); Archive **READY**.

### Owner Messages (inbox) — `Owner Messages.html`
- **Route/stub:** net-new `/owner/inbox`.
- **Design contains (intent):** owner ↔ operator thread list + conversation.
- **Backend reality:** ✅ REAL (`InboxService` + `MessageThread`/`Message` — the whole inbox backend exists and
  the operator surface is built). Owner surface = the same service, owner-scoped, on the owner shell.
- **Writes:** reply/etc. via `InboxService` (exists). **Strength:** MODERATE. **Biggest risk:** owner-scoping
  the threads (owner ↔ operator only) + the same PII discipline (invariant #25). **Verdict:** **READY.**

### Owner Team — `Owner Team.html`
- **Route/stub:** net-new `/owner/team`.
- **Design contains (intent):** owner sub-users + roles + invites.
- **Backend reality:** 🟨 — users/roles/`Membership` exist; `OwnerInviteService` invites *owners*, not
  *owner-sub-users*. An owner-team invite (sub-user under the owner) is a small extension of the invite
  machinery. **Writes:** invite sub-user (extend `OwnerInviteService`). **Strength:** MODERATE. **Verdict:**
  **MOSTLY-READY.**

### Owner Settings / Security — `Owner Settings.html`, `Owner Security.html`
- **Route/stub:** net-new `/owner/settings`, `/owner/security`.
- **Design contains (intent):** owner profile/invoice/channel prefs; password + 2FA + export.
- **Backend reality:** ✅ profile/password (`User`, `Owner`); 🟥 2FA (no flow); export = CSV (deferral).
- **Writes:** update profile/password (exists); 2FA new. **Strength:** MODERATE. **Verdict:** **MOSTLY-READY**
  (2FA gated).

### Owner Rates — `Owner Rates.html`
- **Route/stub:** net-new `/owner/rates`.
- **Design contains (intent):** owner-visible/editable rate grid for their units.
- **Backend reality:** ✅ `RateAndRestrictionService` exists — but **whether an owner may edit rates is a
  product/policy decision** (owners setting rates vs operator-managed). Read is REAL; write depends on policy.
- **Writes:** setRate (exists) if owner-editable. **Strength:** MODERATE. **Biggest risk:** the policy gate.
  **Verdict:** **MOSTLY-READY** (decide owner-edit policy first; likely read-only owner view initially).

### Owner Add Unit (wizard) — `Owner Add Unit.html`
- **Route/stub:** net-new `/owner/units/add`.
- **Design contains (intent):** stepper — unit details → room type → agreement/commission → confirm.
- **Backend reality:** ✅ `Unit`/`RoomType`/`ManagementAgreement` models; **no dedicated unit-create service**
  (units are seeded/factory-made today) — unit create is simple Eloquent + agreement link. **Writes:** create
  unit + agreement (thin new orchestration). **Strength:** MODERATE. **Verdict:** **MOSTLY-READY** (small
  create-unit action + wizard).

---

## Auth / Onboarding — per-screen detail

### Operator Onboarding (wizard) — `Vyrobel Onboarding.html`
- **Route/stub:** net-new `/app/onboarding`.
- **Design contains (intent):** new-operator stepper — org/property setup → first unit/room type → channel
  connect → done.
- **Backend reality:** ✅ the underlying creates exist (`Organization`, `Property`, `Unit`/`RoomType`, `Channel`
  models; role provisioning via `OrganizationRoleProvisioner`), but **no onboarding orchestration flow** ties
  them into a guided first-run. **Writes:** create org/property/unit/channel (models exist; a wizard controller
  orchestrates). **Strength:** HEAVY (multi-entity first-run). **Biggest risk:** orchestration + idempotent
  resume; tenancy context during setup. **Verdict:** **MOSTLY-READY** (glue, not new backend).

### Owner Onboarding (wizard) — `Owner Onboarding.html`
- **Route/stub:** net-new `/owner/onboarding`.
- **Design contains (intent):** owner first-run — accept agreement, add unit(s), set payout method.
- **Backend reality:** ✅ agreement/unit exist; 🔴 payout method = Stripe Connect (gated — stub that step).
  **Writes:** accept agreement, add unit (as above). **Strength:** MODERATE. **Verdict:** **MOSTLY-READY**
  (payout-method step gated → stub honestly).

### 2FA setup / verify — (within `Operator Profile` / `Operator Security` / `Owner Security`)
- **Route/stub:** net-new panels in the profile/security screens.
- **Design contains (intent):** enable 2FA (QR/secret), verify code, recovery codes, disable.
- **Backend reality:** 🟥 NEEDS BACKEND — `users.two_factor_secret` / `two_factor_enabled` **columns exist but
  there is no TOTP flow, no recovery codes, no challenge on login.** **Writes:** enable/verify/disable + a login
  challenge — all new. **Strength:** MODERATE. **Biggest risk:** it touches the login path (add a 2FA challenge
  step to the certified auth flow — do it carefully to preserve invariant #21). **Verdict:**
  **NEEDS-BACKEND-FIRST.**

---

## Recommended build order

Sequenced fastest-wins-first, **grouped by shared backend** so a cluster ships together. Every item is 🟢
buildable now (no external account) unless marked 🔴.

### Wave 1 — ✅ COMPLETE (shipped 2026-07-16) — over the *owner-accounting* backend (the wedge's long tail)
**Shipped, across four gated sub-gates:** owner **Balance** · **Expenses** · **Agreement** · **Archive** ·
**Unit Detail** · **Documents** · operator **Owner Detail** · **Payout Run** (REAL money writes via the
certified `OwnerPayoutService`; `ManualPayoutRail` real, Stripe Connect honestly disabled "Soon") · plus the
**Remittance Advice** and **Booking Summary** PDFs. Presentation only — **zero** files changed under
`app/Services` / `app/Models` / `database/migrations` / `app/Payouts`, verified per gate. Three shared
extractions prevent screen↔document drift: `Support\Payouts\RailLabel`, `Support\Owner\OwnerOccupancy`
(keyed on **physical unit id, never room type**), and `assertNoRawBlade()`.

*(Original plan, kept as the record.)* All reuse the owner shell + `OwnerPortalService`/owner-accounting
services + `OwnerScope`. Ship as one design sub-pass:
1. **Owner Balance** (ledger) · **Owner Expenses** · **Owner Agreement** · **Owner Archive** — SIMPLE, pure
   read over `OwnerLedgerEntry`/`OwnerExpense`/`ManagementAgreement`/`OwnerStatement`.
2. **Owner Remittance Advice PDF** + **Owner Booking Summary PDF** — mirror the existing statement-PDF pattern.
3. **Owner Unit Detail** — occupancy (reuse My Units logic) + per-unit revenue; trends stub.
4. **Operator Owner Detail** — the operator-side (tenant-scoped) lens on the same data (`/app/owners/{owner}`).
5. **Payout Run** — batch over `OwnerPayoutService::createFromStatement/pay` (ManualPayoutRail; Connect 🔴 stub).
6. **Owner Documents** — aggregation view over the above (MOSTLY-READY).

### Wave 2 — ✅ COMPLETE (shipped 2026-07-17) — over the *reservations/folio* backend (operator daily-ops)
**Shipped, across five gated sub-gates:** operator **Reservations list** · **Reservation cancelled / no-show**
terminal states · the **Refund** screen · the **Guests CRM list** · the **Guest profile**. Four shared
definitions came out of it so two surfaces cannot disagree: `Support\Reservations\ReservationStage` (badge AND
its SQL filter), `Support\Guest\GuestSpend` ("Total billed" + open balance beside it),
`Support\Guest\GuestHistory` (stays/nights/since — shared with the inbox guest card), and `Guest::reservations()`
(lead_guest_id; pivot deliberately excluded). It also produced **invariant #28** (the guest-PII perimeter) and a
**🔴 four-item outstanding register** — see below.

*(Original plan, kept as the record.)*
7. **Reservations list** + **Cancelled/No-show** states — table + terminal-state detail over `Reservation` +
   existing `cancelReservation`/`markNoShow`/`refundPayment`.
8. **Refund** (dedicated, partial + reason) — over `FolioService::refundPayment`.
9. **Guests CRM list** — over `Guest` (+ VIP/risk/tags/notes); then **Guest Profile** (MOSTLY-READY — add the
   lifetime-spend derivation).

### ⛔ SEQUENCED FIRST — clear the Wave 2 outstanding register (before any Wave 3 work)
Wave 2 shipped with **four items deliberately found-and-not-fixed**, recorded in the
**🔴 WAVE 2 OUTSTANDING REGISTER** in `docs/PROJECT-STATE.md` (that register is the authority; do not restate
its detail here): `AssignmentService::terminate()`'s **missing internal status guard** · **two dead enum
values** (`payments.payment_status = 'refunded'`, `folios.status = 'closed'`) · the **🔴 unenforced permission
catalog** (a real security follow-up) · **`anonymize()` has no UI trigger** (a GDPR erase path with no way to
invoke it). This is correctness/security debt from a wave we just shipped — it is cleared **before** Wave 3
starts, not carried.

### Wave 3 — ⬅️ NEXT BUILD TARGET (pending) — READY, over *inbox* + owner surfaces
*(Starts once the register above is clear.)*
10. **Owner Messages** (inbox) — reuse `InboxService`, owner-scoped, on the owner shell.

### Wave 4 — MOSTLY-READY, small backend additions (grouped by shared backend)
11. **Rates & availability grid** + **Channels config** — the *distribution* cluster; both over
    `RateAndRestrictionService` + `Channel`/`ChannelMapping` (add a thin channel/mapping CRUD controller). Live
    Channex sync stays 🔴 gated.
12. **New Reservation** wizard, then **Group Booking** — the *booking-creation* cluster over
    `AssignmentService::createBooking` (Group needs a small atomic-group + folio-rollup method).
13. **Owner Bookings** + **Owner Booking Detail** — add `OwnerPortalService::bookings()` derivation.
14. **Portfolio** — occupancy/revenue overview (derivable); ADR/RevPAR cards stub.
15. **Owner Block Calendar** — over `AssignmentService::blockInventory` (confirm/expose block-removal); drag via
    client calendar → guarded POST, never a wire-action.
16. **Roles** (read + assign over the fixed catalog) · **Owner Team** (extend invites) · **Settings / Property
    Settings / Profile / Owner Settings** (backed fields; unmodeled toggles + 2FA deferred) · **Add Unit** /
    **Onboarding** wizards (orchestration glue) · **Maintenance Costs** (cost aggregation) · **Security/audit
    log view** (read).

### Wave 5 — NEEDS-BACKEND-FIRST (a backend gate precedes the UI)
17. **Global Search** — build a search service first (non-encrypted fields; guest-contact search needs the
    deferred blind-index).
18. **2FA** — build the TOTP flow + login challenge first (touches the certified auth path — careful).
19. **Maintenance Settings** — add SLA/category schema first.

---

## Honest notes

- **Design-implies-more-than-the-backend-has (stub/defer per invariant #19):**
  - **ADR / RevPAR / trend charts** appear on Portfolio, Owner Unit Detail, Owner Bookings, Maintenance Costs —
    these are **Tier-2 Analytics** territory (no aggregation layer). Build the *derivable* parts (occupancy,
    revenue, cost sums) and **stub ADR/RevPAR "Soon"** — do not fabricate.
  - **Export buttons** recur across nearly every list — **CSV export is a standing deferral.** Wire the ones
    that matter as real CSV later; stub the rest honestly (PDF export exists for statement/folio only).
  - **Global Search** cannot search guest email/phone (encrypted; blind-index deferred) — scope to reference/
    name/unit-code and flag the limitation.
  - **Owner Rates** implies owner-editable pricing — that's a **product policy decision**, not just UI; default
    to a read-only owner view unless the policy says owners set rates.

- **Backend gates that must precede styling (the only true NEEDS-BACKEND-FIRST in Tier 1):** **2FA** (no flow,
  and it touches the login path), **Global Search** (needs a search service), **Maintenance Settings** (needs
  SLA/category schema). Everything else is READY or a *small* addition (a derivation helper, a thin CRUD
  controller, or an orchestration method) — not a schema-first gate.

- **The reassuring pattern:** the vast majority of Tier 1 is **pure integration over certified services** —
  Owner accounting, reservations/folio, inbox, rates/channels, and payouts all already have their write paths
  (`createBooking`, `refundPayment`, `blockInventory`, `setRate`/`setRestriction`, `createFromStatement`/`pay`,
  `InboxService`). The single-write-path and honesty invariants (#1, #7, #17–20) already hold — Tier 1 is about
  *surfacing* them, not rebuilding them.

- **Account-gated bleed-through:** three Tier-1 screens have a gated *sub-part* (not the whole screen) — Payout
  Run (Stripe Connect step), Channels (live Channex sync), Owner Onboarding (payout-method step). Build the
  screen now against the real/manual path; keep the gated step honestly stubbed. This is not a blocker.

- **Extraction caveat (restated):** the per-screen *feature inventories* above are the design **intent**
  (catalogue content-verified + PMS convention), not a machine-exact component list — the bundles don't permit
  that. Confirm the exact panels/modals/filters by opening the specific `docs/design/raw/*.html` in a browser
  at build time. The **backend verdicts are repo-verified and are the load-bearing part of this spec.**

---

*Read-only audit. Every ✅ REAL grounded in the repo (`app/Services`, `app/Models`, `routes/web.php`,
`database/migrations`). Trust the repo over this doc where they drift, and fix this doc.*
