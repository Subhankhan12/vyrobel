# Vyrobel — Design Export Catalogue (`docs/design/raw/`)

**What this is:** an inventory of the Claude Design HTML exports, each identified by its **actual content**
(not just its filename), with the extra components/features worth preserving, duplicates flagged, and a
mapping to the existing certified backend / unstyled stubs for the design pass.

## Ground truth (read this first)

- **120 files total** = **119 screens + `index.html`** (a "Vyrobel · Screen Library" manifest that groups
  them: **32 Guest · 40 Operator · 31 Owner · 5 platform-auth · 9 Emails · 2 System/shared**). The count
  and grouping were read from `index.html`.
- **The filenames are NOT messy/auto-generated — they are clean and descriptive, and content CONFIRMS every
  one** (e.g. `Owner Statement.html` really contains "Net payable"/"Gross"/"commission"; `Operator Front
  Desk.html` really contains "Arrivals"/"Departures"; `Operator Dashboard.html` really contains "ADR"/KPIs).
  **I found no filename↔content mismatch.** So the "I don't know which is which" premise doesn't hold —
  the map below is essentially the manifest, verified by content.
- **Caveat on "extra features":** each file is a **self-contained JS-bundled SPA** ("Bundled Page" —
  content is rendered by inlined JavaScript, not present as static DOM). I fingerprinted by grepping the
  **content strings inside the bundle**, which reliably identifies the SCREEN but only *approximates* its
  components. Shared sidebar/nav strings (ADR, Tax, Villa, Message, Insights, Filter…) appear in every
  operator/owner file and are **noise**; I classified by the *dominant/elevated* terms. **For pixel-exact
  component lists (which charts, which modals, dark-mode, animations), a browser render pass is needed** —
  I can do that next if you want it.
- **Nothing was renamed, moved, or modified.**

**Backend-status legend** (how ready the real data is for this screen):
- ✅ **existing unstyled stub** — a Livewire/route stub already exists to style + wire (listed in PROJECT-STATE).
- 🟡 **backend exists, no dedicated stub** — the service/data is certified; needs a new styled page wired to it.
- ⬜ **no backend yet** — design-only / a future module (e.g. unified inbox, maintenance, guest portal, billing, analytics).

---

## Operator (40) — property-management back office

| File | Screen (content-confirmed) | Backend | Notable extras to preserve (content-derived) |
|---|---|---|---|
| `Operator Dashboard.html` | Operator overview / KPI dashboard (ADR, occupancy, arrivals, housekeeping, maintenance, revenue) | 🟡 | KPI tiles + a chart; today's arrivals/departures/HK; alert counts |
| `Operator Calendar.html` | Reservation timeline / rack (drag) | ✅ `ReservationRack` `/app/rack` | drag-to-move calendar grid; per-channel; filters |
| `Operator Front Desk.html` | Arrivals / departures / in-house (check-in/out) | ✅ `FrontDesk` `/app/front-desk` | balance column, folio actions, print, empty/toast states |
| `Operator Housekeeping.html` | Housekeeping **KANBAN** (cleaning board) | ✅ `HousekeepingBoard` `/app/housekeeping` | **drag-and-drop kanban** (Drag:41), heavy filters, team assign, toasts |
| `Operator Reservations.html` | Reservations list | 🟡 | filters, bulk, empty/toast states |
| `Operator Reservation Detail.html` | Single reservation + **folio ledger** | ✅ `ReservationDetail` `/app/reservations/{id}` | folio (12) + refund actions, modal, payment method, balance |
| `Operator Reservation Cancelled.html` | Reservation detail — cancelled state | 🟡 | folio/refund ledger for a cancellation |
| `Operator Reservation No-show.html` | Reservation detail — no-show state | 🟡 | no-show charge/refund flow, modal |
| `Operator New Reservation.html` | Create booking (wizard) | 🟡 (AssignmentService) | multi-step **stepper**, availability/occupancy check, rate plan |
| `Operator Group Booking.html` | Group booking | 🟡 (groups schema) | multi-room group flow |
| `Operator Refund.html` | Issue refund | 🟡 (FolioService.refund) | refund flow (Refund:50), toggles, folio |
| `Operator Channels.html` | Channel manager (Channex) config | 🟡 (channels backend) | channel cards, **commission** (16), connect **stepper**, sync status |
| `Operator Rates.html` | Rates / ARI grid | 🟡 (RateAndRestrictionService) | rate-plan grid, channel push, heavy filters |
| `Operator Owners.html` | Owners list (operator side of owner accounting) | 🟡 (owners backend) | net payable / payouts / statements columns |
| `Operator Owner Detail.html` | Single owner — statements/payouts/commission | 🟡 (owner accounting) | gross/commission/payout/statement (operator managing an owner) |
| `Operator Payout Run.html` | Batch payout run to owners | 🟡 (OwnerPayoutService) | select statements → run payouts, export |
| `Operator Guests.html` | Guest CRM list | 🟡 (guests backend) | export (8), filters (40), profiles |
| `Operator Guest Profile.html` | Single guest profile | 🟡 | history, folio, messages, modal |
| `Operator Messages.html` | Unified inbox (operator) | ⬜ (Unified Inbox = next module) | conversation list + thread, channels, toasts |
| `Operator Maintenance.html` | Maintenance tickets / work orders | ⬜ (no maintenance module) | **work orders** (20), filters (77), export, stepper |
| `Operator Maintenance Costs.html` | Maintenance cost analytics | ⬜ | cost **trend** charts (Trend:17), export |
| `Operator Maintenance Settings.html` | Maintenance config | ⬜ | SLAs/categories, toggles |
| `Operator Work Order.html` | Single work order | ⬜ | timeline, assignment |
| `Operator Reviews.html` | Reviews / reputation | ⬜ | review moderation, replies |
| `Operator Reports.html` | Reports / analytics (RevPAR, ADR, payouts) | ⬜ (analytics) | many charts, **trend** (17), export/download (14) |
| `Operator Insights.html` | Insights / analytics (occupancy, RevPAR) | ⬜ (analytics) | charts (4) |
| `Operator Portfolio.html` | Multi-property portfolio overview | 🟡 (properties) | RevPAR per property, occupancy |
| `Operator Team Schedule.html` | Staff scheduling (drag) | ⬜ | **drag** roster (Drag:52), export |
| `Operator Roles.html` | RBAC roles + permissions | 🟡 (spatie roles) | permission matrix, modal |
| `Operator Security.html` | Security / audit log center | 🟡 (audit_events, partial) | big file (2.2MB); audit export (17), 2FA, Stripe, toasts (36) |
| `Operator Billing.html` | Operator's own subscription billing | ⬜ (billing) | subscription/invoices, export |
| `Operator Settings.html` | Operator settings (commission, taxes, roles…) | 🟡/⬜ | many toggles, upload, tabs |
| `Operator Property Settings.html` | Per-property settings | 🟡 (properties/policies) | folios/tax/policy config, upload |
| `Operator Profile.html` | Operator user profile | 🟡 (users) | 2FA, roles, upload |
| `Operator Notifications.html` | Notification feed | ⬜ | feed list |
| `Operator Notification Preferences.html` | Notification settings | ⬜ | per-event toggles |
| `Operator Search.html` | Global search | 🟡 | cross-entity results (reservations/guests/villas) |
| `Operator Email Editor.html` | Email template editor | ⬜ | WYSIWYG editor |
| `Operator Email Templates.html` | Email templates list | ⬜ | template cards, toggles |
| `Operator Schedule PDF.html` | **PDF** — printable staff schedule | ⬜ | print (6) — print/export variant |

---

## Owner (31) — owner portal (earnings, units, tax)

| File | Screen (content-confirmed) | Backend | Notable extras to preserve |
|---|---|---|---|
| `Owner Dashboard.html` | Owner overview / earnings | ✅ `OwnerDashboard` `/owner` | payout (27) + statement (18) summary, **charts** (4), heavy filters |
| `Owner Statement.html` | **Owner statement + breakdown** (Net payable / Gross / commission) | ✅ `OwnerStatementView` `/owner/statements/{id}` | Net payable (5), Gross (31), export/download; the wedge's hero screen |
| `Owner Statement PDF.html` | **PDF** — statement print/export variant | ✅ (same data) | print (4) — pairs with `Owner Statement.html` |
| `Owner Payouts.html` | Payout history | 🟡 (payouts backend) | Payout:160 (dominant), export (14), print, empty/toast states, modal |
| `Owner My Units.html` | Owner units — cards + **occupancy calendar** | 🟡 (units + AvailabilityService) | Occupancy calendar (2), Villa (35), revenue per unit, huge filter set |
| `Owner Unit Detail.html` | Single unit — occupancy + performance | 🟡 | occupancy calendar, **trend** charts (17), commission |
| `Owner Expenses.html` | Owner expenses list | 🟡 (OwnerExpenseService) | expense (12) + maintenance (10), export |
| `Owner Balance.html` | Running balance / ledger | 🟡 (owner_ledger) | Net payable, ledger (4), balance (14), export |
| `Owner Agreement.html` | Management agreement terms | 🟡 (management_agreements) | commission (9), management fee, documents |
| `Owner Bookings.html` | Owner's bookings list | 🟡 (reservations attributed) | gross (19), **charts** (7), **trend**, timeline, export |
| `Owner Booking Detail.html` | Single booking — owner earnings view | 🟡 | gross (24), payout (47), **timeline** (14) |
| `Owner Reports.html` | Owner reports / analytics | ⬜ (analytics) | payout (98), statement (40), **charts** (11), RevPAR |
| `Owner Insights.html` | Owner insights / analytics | ⬜ (analytics) | occupancy (8), ADR, **charts** (4) |
| `Owner Reviews.html` | Owner-facing reviews | ⬜ | review list, **trend** |
| `Owner Rates.html` | Owner-set rates | 🟡 (rates, if owner-editable) | rate grid |
| `Owner Block Calendar.html` | Owner blocks unit dates (drag calendar) | 🟡 (inventory_blocks) | **drag** calendar (11), block dates, toasts |
| `Owner Tax Center.html` | Tax center | ⬜ (tax) | tax (48), VAT invoice, documents |
| `Owner Annual Tax Summary PDF.html` | **PDF** — annual tax summary | ⬜ | print — gross revenue/commission/tax |
| `Owner Payout Method.html` | Bank / payout method setup | ⬜ (Stripe Connect deferred) | payout (29), remittance, bank details |
| `Owner Remittance Advice PDF.html` | **PDF** — remittance advice | 🟡 (payout data) | print — Net payable/gross/commission |
| `Owner Documents.html` | Owner documents (statements/remittances) | 🟡 | document list, remittance (5) |
| `Owner Archive.html` | Archived statements/documents | 🟡 | statements (8), chart |
| `Owner Booking Summary PDF.html` | **PDF** — booking summary | 🟡 | print — gross/commission/occupancy |
| `Owner Onboarding.html` | Owner onboarding (wizard) | ⬜ | **stepper**, payout (14) |
| `Owner Add Unit.html` | Add a unit (wizard) | 🟡 (units) | **stepper**, agreement/commission |
| `Owner Messages.html` | Owner ↔ operator inbox | ⬜ (Unified Inbox) | thread (Message:23) |
| `Owner Notifications.html` | Owner notification feed | ⬜ | feed, toasts |
| `Owner Team.html` | Owner sub-users / team | 🟡 (users/roles) | roles (7), invites |
| `Owner Security.html` | Owner account security | 🟡 (users) | password (13), 2FA, export |
| `Owner Settings.html` | Owner settings | 🟡 | invoice/channel/profile config, export |
| `Owner Help.html` | Owner help / FAQ | ⬜ | FAQ, wizard |

---

## Guest (32) — guest booking, stay & self-service (⬜ **no guest-portal backend yet — the closest built thing is the public booking-engine stub `/book`**)

| File | Screen (content-confirmed) | Backend | Notable extras |
|---|---|---|---|
| `Guest Browse.html` | Browse / search villas | ⬜ (closest: `/book` search) | listings, wishlist, filters |
| `Guest Villa Detail.html` | Villa/property detail | ⬜ (closest: `/book`) | gallery, wishlist, modal |
| `Guest Booking.html` | Booking flow | ⬜ (closest: `/book` quote) | wishlist, availability, filters |
| `Guest Checkout.html` | Checkout / payment | ⬜ (closest: `/book` book) | credits/refund, balance |
| `Guest Check-in.html` | Digital check-in | ⬜ | **stepper**, ID upload, housekeeping ref |
| `Guest ID Verification.html` | ID upload/verification | ⬜ | **upload/stepper** |
| `Guest Reservation.html` | Guest's reservation detail | ⬜ (folio data exists) | folio, timeline, add-ons, payment, modal |
| `Guest Manage Booking.html` | Modify/cancel booking | ⬜ | refund (30), balance, calendar |
| `Guest Trips.html` | Trips list (past/upcoming) | ⬜ | reviews, folios, empty state |
| `Guest Payments.html` | Payments / cards | ⬜ | payment methods, statements, refunds |
| `Guest Credits.html` | Guest credits / referrals | ⬜ | referral (19), credits/refunds |
| `Guest Add-ons.html` | Purchase add-ons | ⬜ | add-on cards, empty state |
| `Guest Documents.html` | Guest documents (invoices/statements) | ⬜ | statements/invoices/credit notes, download |
| `Guest Messages.html` | Guest ↔ operator inbox | ⬜ (Unified Inbox) | thread |
| `Guest Reservation.html`… (see above) | — | — | — |
| `Guest Review.html` | Leave a review | ⬜ | rating form |
| `Guest Waitlist.html` | Join waitlist | ⬜ | waitlist form |
| `Guest Wishlist.html` | Saved villas | ⬜ | wishlist grid, empty state |
| `Guest Referral Landing.html` | Referral landing | ⬜ | referral, credits, toasts |
| `Guest Arrival Info.html` | Pre-arrival info | ⬜ | directions, codes, check-in |
| `Guest Help.html` | Guest help / FAQ | ⬜ | FAQ, upload |
| `Guest Account.html` | Guest account settings | ⬜ | profile/payment/docs, filters, toasts |
| `Guest Email Preferences.html` | Email preferences | ⬜ | toggles, modal |
| `Guest Login.html` | Guest login | ⬜ (auth) | sign in (5), password |
| `Guest Signup.html` | Guest signup | ⬜ (auth) | password, verify email |
| `Guest Verify Email.html` | Verify email | ⬜ (auth) | verify token |
| **Guest PDFs** — `Guest Folio Receipt PDF`, `Guest VAT Invoice PDF`, `Guest Credit Note PDF`, `Guest Refund Receipt PDF`, `Guest Statement of Account PDF`, `Guest Arrival Info PDF` | **PDF print/export** variants of the folio/invoice/credit/refund/statement/arrival screens | ⬜ | print (4 each); each pairs with its on-screen sibling (e.g. `Guest Folio Receipt.html`) |
| `Guest Folio Receipt.html` | Folio receipt (on-screen) | ⬜ (folio data exists) | pairs with `…PDF` |

---

## Platform auth & subscription (5)

| File | Screen | Backend | Notable extras |
|---|---|---|---|
| `Vyrobel Login.html` | **Operator/staff login** | ⬜ (no login UI built — auth pass) | sign in (5), password, "Vyrobel" branded |
| `Vyrobel Reset Password.html` | Reset password | ⬜ (auth) | password (11) |
| `Vyrobel Onboarding.html` | New-operator onboarding | ⬜ | **stepper**, property/channel setup |
| `Vyrobel Paywall.html` | Subscription paywall | ⬜ (billing) | plan gating |
| `Vyrobel Checkout.html` | Subscription checkout | ⬜ (billing) | paywall/subscription/billing |

---

## Emails (9) & System/shared (2) & Index

| File | What it is | Notable extras |
|---|---|---|
| `Booking Confirmation Email.html` · `Cancellation Email.html` · `Payment Reminder Email.html` · `Pre-arrival Email.html` · `Refund Confirmation Email.html` · `Review Request Email.html` · `Verify Email.html` · `Waitlist Notification Email.html` · `Winback Email.html` | **9 transactional email templates** (⬜ email system) | print/download; each is a static email layout |
| `Email Previews.html` | **Catalog** of all email templates (previews) | multiple email previews in one page |
| `System States.html` | **Shared edge/empty/error states catalog** (loading, empty, 404, offline…) | reusable state components — useful design-system reference |
| `index.html` | **Screen Library manifest** — links all 119 screens grouped by role | the source of the counts above; not an app screen |

---

## Duplicates, pairs & overlaps (flagged)

- **No accidental duplicate screens** — each of the 119 is a distinct screen per the manifest, and content
  agrees. But there are deliberate **on-screen ↔ PDF pairs** (the PDF is a print/export of the same data,
  **keep both**, not redundant):
  `Owner Statement` ↔ `Owner Statement PDF`; `Guest Folio Receipt` ↔ `Guest Folio Receipt PDF`;
  plus PDF-only exports (`Owner Remittance Advice PDF`, `Owner Annual Tax Summary PDF`, `Owner Booking
  Summary PDF`, `Guest VAT Invoice PDF`, `Guest Credit Note PDF`, `Guest Refund Receipt PDF`, `Guest
  Statement of Account PDF`, `Guest Arrival Info PDF`, `Operator Schedule PDF`).
- **Same data, different role/lens** (design-pass overlaps to keep visually consistent, not duplicates):
  owner earnings appear in `Owner Statement`/`Owner Payouts`/`Owner Balance` **and** operator-side
  `Operator Owner Detail`/`Operator Owners`/`Operator Payout Run`; a reservation appears in
  `Operator Reservation Detail`, `Owner Booking Detail`, and `Guest Reservation`.
- **`Owner Statement` vs `Owner Reports`** and **`Operator Reports` vs `Operator Insights`** overlap in
  charts/figures — the "most complete" analytics screens are `Operator Reports` (RevPAR/ADR/trend/export)
  and `Owner Reports` (charts:11); the lighter ones (`Insights`) are dashboards over the same data.
- **Nothing was unidentifiable.** The only uncertainty is *component-level* (which exact charts/modals/
  dark-mode) because content is JS-rendered — confirmable with a render pass.

## Recommended for the design pass (screens with a REAL backend/stub today)

Style these **first** — they have certified backends + (some) existing unstyled stubs:
- **Owner accounting (the wedge):** `Owner Statement` (✅ stub) · `Owner Dashboard` (✅ stub) · `Owner
  Payouts` · `Owner My Units` · `Owner Expenses` · `Owner Balance` · `Owner Agreement` · operator side:
  `Operator Owners` · `Operator Owner Detail` · `Operator Payout Run`.
- **Operator ops (existing stubs):** `Operator Calendar` (✅ `/app/rack`) · `Operator Front Desk` (✅) ·
  `Operator Housekeeping` (✅) · `Operator Reservation Detail` (✅) · `Operator Dashboard`.
- **Public booking:** `Guest Browse`/`Villa Detail`/`Booking`/`Checkout` → the `/book` stub.

**Design-only for now (⬜ no backend — do NOT build backend for these during the design pass):** the full
guest portal (32), unified inbox (Operator/Owner/Guest Messages), the entire maintenance module, reviews,
subscription billing/paywall, email templates/editor, waitlist/wishlist/referral, and the analytics
(Reports/Insights) screens. These map to future Phase-3+ modules, not the certified backend.
