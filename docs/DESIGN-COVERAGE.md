# Vyrobel — Design Coverage Report (exists vs missing per screen)

**Purpose.** For every screen Vyrobel needs (built + planned, per `docs/ROADMAP.md` + `docs/TIER1-SPEC.md`),
state whether a design HTML already exists in `docs/design/raw/` (name the file) or is **❌ MISSING** (needs
creating in Claude Design before it can be built). Ground truth = the **real folder listing**, not the
catalogue's stated count.

**Headline:** design coverage is **near-total** — the design library actually **exceeds** what's built (most
files are designs for not-yet-built modules). Of ~90 needed screens, only **~3 are missing a design**, and
those are edge cases (one already built without a design, one gated on a new backend, one a minor email
template). **Waves 1 and 2 were both 100% designed and are now both BUILT (2026-07-16 / 2026-07-17) — no
design gate blocked either, and none blocks Wave 3.**

---

## A. Folder truth

- **Actual files in `docs/design/raw/`: 125** (all `.html`; no non-HTML files).
- **Screens = 124** (125 minus `index.html`, the Screen Library manifest).
- **Catalogue drift: RESOLVED ✅.** `docs/design/CATALOGUE.md` was reconciled on **2026-07-16** and now states
  **125 files / 124 screens**, matching the folder. (It previously said 120 files / 119 screens — the figure
  `index.html` still reports, since the manifest predates five exports and was never regenerated. Trust the
  filesystem over `index.html`.) The 5 files the old count missed are **newer exports** for screens that are
  **now built** (inbox + auth) — recorded here for the history:

  | File the old catalogue count missed | What it is | Build status |
  |---|---|---|
  | `Operator Inbox.html` | Operator unified-inbox thread list | ✅ built (`/app/inbox`) |
  | `Operator Conversation.html` | Operator inbox conversation view | ✅ built (`/app/inbox/{thread}`) |
  | `Owner Invite Email.html` | Owner/staff invite email template | ✅ built (`emails/owner-invite`) |
  | `Vyrobel Invite Owner.html` | Operator "invite owner" screen | ✅ built (`/app/owners` modal) |
  | `Vyrobel Set Password.html` | Accept-invite / set-password screen | ✅ built (`auth/set-password`) |

  > **The COUNT action is closed; ONE HALF REMAINS.** The original action had two parts: reconcile the
  > headline count, **and add the five screens to the catalogue's tables**. The first is done — `CATALOGUE.md`
  > now reads 125 files / 124 screens. **The second is not:** those five are named only in the
  > "RECONCILED 2026-07-16" paragraph (`CATALOGUE.md:14-15`) and appear in **no per-screen table**, so a reader
  > looking up "Operator Inbox" in the catalogue still will not find it. Consequently the per-group split
  > (32 Guest · 40 Operator · 31 Owner · 5 platform-auth · 9 Emails · 2 System/shared = 119) is still read from
  > `index.html` and remains **five light** — the catalogue flags this itself. Small, and worth doing when the
  > catalogue is next touched.

---

## B. Coverage by module

Legend: **✅ EXISTS** (design file present, named) · **❌ MISSING** (no design covers this screen).
"Built?" notes whether the screen is already live in the app.

### Owner Portal — **31/31 designed (100%)**

| Needed screen | Design | Built? |
|---|---|---|
| Owner Dashboard | ✅ `Owner Dashboard.html` | ✅ built |
| Owner Statement | ✅ `Owner Statement.html` | ✅ built |
| Owner Statement PDF | ✅ `Owner Statement PDF.html` | ✅ built |
| Owner Payouts | ✅ `Owner Payouts.html` | ✅ built |
| Owner My Units | ✅ `Owner My Units.html` | ✅ built |
| Owner Unit Detail | ✅ `Owner Unit Detail.html` | ✅ built (`/owner/units/{unit}` — Wave 1) |
| Owner Expenses | ✅ `Owner Expenses.html` | ✅ built (`/owner/expenses` — Wave 1) |
| Owner Balance | ✅ `Owner Balance.html` | ✅ built (`/owner/balance` — Wave 1) |
| Owner Agreement | ✅ `Owner Agreement.html` | ✅ built (`/owner/agreement` — Wave 1) |
| Owner Bookings | ✅ `Owner Bookings.html` | — |
| Owner Booking Detail | ✅ `Owner Booking Detail.html` | — |
| Owner Block Calendar | ✅ `Owner Block Calendar.html` | — |
| Owner Rates | ✅ `Owner Rates.html` | — |
| Owner Documents | ✅ `Owner Documents.html` | ✅ built (`/owner/documents` — Wave 1) |
| Owner Archive | ✅ `Owner Archive.html` | ✅ built (`/owner/archive` — Wave 1) |
| Owner Remittance Advice PDF | ✅ `Owner Remittance Advice PDF.html` | ✅ built (`/owner/payouts/{payout}/remittance` — Wave 1) |
| Owner Booking Summary PDF | ✅ `Owner Booking Summary PDF.html` | ✅ built (`/owner/statements/{statement}/bookings` — Wave 1) |
| Owner Annual Tax Summary PDF | ✅ `Owner Annual Tax Summary PDF.html` | — (tax, no backend) |
| Owner Tax Center | ✅ `Owner Tax Center.html` | — (tax, no backend) |
| Owner Payout Method | ✅ `Owner Payout Method.html` | — (🔴 Stripe Connect) |
| Owner Team | ✅ `Owner Team.html` | — |
| Owner Security | ✅ `Owner Security.html` | — |
| Owner Settings | ✅ `Owner Settings.html` | — |
| Owner Onboarding | ✅ `Owner Onboarding.html` | — |
| Owner Add Unit | ✅ `Owner Add Unit.html` | — |
| Owner Messages (inbox) | ✅ `Owner Messages.html` | — |
| Owner Notifications | ✅ `Owner Notifications.html` | — (notifications, no backend) |
| Owner Reports | ✅ `Owner Reports.html` | — (analytics, no backend) |
| Owner Insights | ✅ `Owner Insights.html` | — (analytics, no backend) |
| Owner Reviews | ✅ `Owner Reviews.html` | — (reviews, no backend) |
| Owner Help | ✅ `Owner Help.html` | — |

### Operator / PMS — **15/16 designed** (Operator Units missing)

| Needed screen | Design | Built? |
|---|---|---|
| Operator Dashboard | ✅ `Operator Dashboard.html` | ✅ built |
| Operator Calendar / Rack | ✅ `Operator Calendar.html` | ✅ built |
| Operator Front Desk | ✅ `Operator Front Desk.html` | ✅ built |
| Operator Housekeeping | ✅ `Operator Housekeeping.html` | ✅ built |
| Operator Reservation Detail | ✅ `Operator Reservation Detail.html` | ✅ built |
| **Operator Units** | ❌ **MISSING** | ✅ built (`/app/units`, styled ad hoc on the operator system) |
| Operator Reservations (list) | ✅ `Operator Reservations.html` | ✅ built (`/app/reservations` — Wave 2) |
| Operator Reservation Cancelled | ✅ `Operator Reservation Cancelled.html` | ✅ built (Wave 2 — a terminal state ON the reservation detail, not a separate route) |
| Operator Reservation No-show | ✅ `Operator Reservation No-show.html` | ✅ built (Wave 2 — same; terminal state on the detail) |
| Operator New Reservation | ✅ `Operator New Reservation.html` | — |
| Operator Group Booking | ✅ `Operator Group Booking.html` | — |
| Operator Refund | ✅ `Operator Refund.html` | ✅ built (`/app/reservations/{reservation}/refund` — Wave 2) |
| Operator Guests (CRM) | ✅ `Operator Guests.html` | ✅ built (`/app/guests` — Wave 2) |
| Operator Guest Profile | ✅ `Operator Guest Profile.html` | ✅ built (`/app/guests/{guest}` — Wave 2) |
| Operator Portfolio | ✅ `Operator Portfolio.html` | — |
| Operator Search | ✅ `Operator Search.html` | — |

### Public Booking — **4/4 designed (100%)**

| Needed screen | Design | Built? |
|---|---|---|
| Browse / search | ✅ `Guest Browse.html` | ✅ built (`/book/{property}`) |
| Villa / room detail | ✅ `Guest Villa Detail.html` | ✅ built (`/rooms/{roomType}`) |
| Booking | ✅ `Guest Booking.html` | ✅ built (checkout flow) |
| Checkout | ✅ `Guest Checkout.html` | ✅ built (`/checkout` WRITE) |

### Auth / Onboarding — **8/9 designed** (2FA missing as a standalone)

| Needed screen | Design | Built? |
|---|---|---|
| Login | ✅ `Vyrobel Login.html` | ✅ built |
| Reset Password (form) | ✅ `Vyrobel Reset Password.html` | ✅ built |
| Set Password (accept invite) | ✅ `Vyrobel Set Password.html` | ✅ built |
| Invite Owner (operator screen) | ✅ `Vyrobel Invite Owner.html` | ✅ built |
| Owner Invite Email | ✅ `Owner Invite Email.html` | ✅ built |
| Operator Onboarding (wizard) | ✅ `Vyrobel Onboarding.html` | — |
| Owner Onboarding (wizard) | ✅ `Owner Onboarding.html` | — |
| Owner Add Unit (wizard) | ✅ `Owner Add Unit.html` | — |
| **2FA setup / verify** | ❌ **MISSING** | — (columns exist, no flow; no standalone design — would live inside Security/Profile) |

### Maintenance — **4/4 designed (100%)**

| Needed screen | Design | Built? |
|---|---|---|
| Maintenance board | ✅ `Operator Maintenance.html` | ✅ built (`/app/maintenance`) |
| Work order | ✅ `Operator Work Order.html` | ✅ built (`/app/maintenance/{ticket}`) |
| Maintenance Costs (analytics) | ✅ `Operator Maintenance Costs.html` | — |
| Maintenance Settings | ✅ `Operator Maintenance Settings.html` | — (needs backend) |

### Unified Inbox — **4/4 designed (100%)** (+1 legacy duplicate)

| Needed screen | Design | Built? |
|---|---|---|
| Operator inbox (thread list) | ✅ `Operator Inbox.html` | ✅ built (`/app/inbox`) |
| Operator conversation | ✅ `Operator Conversation.html` | ✅ built (`/app/inbox/{thread}`) |
| Owner Messages | ✅ `Owner Messages.html` | — |
| Guest Messages | ✅ `Guest Messages.html` | — (guest portal) |
| *(legacy)* `Operator Messages.html` | ✅ (older single-file inbox — superseded by Inbox+Conversation) | see §E |

### Guest Portal (authenticated, Tier 2 — no backend) — **~24/24 designed (100%)**

All designed; none built (only the public booking storefront above is built). Designs present:
`Guest Trips` · `Guest Reservation` · `Guest Manage Booking` · `Guest Payments` · `Guest Credits` ·
`Guest Add-ons` · `Guest Documents` · `Guest Check-in` · `Guest ID Verification` · `Guest Messages` ·
`Guest Review` · `Guest Waitlist` · `Guest Wishlist` · `Guest Referral Landing` · `Guest Arrival Info` ·
`Guest Help` · `Guest Account` · `Guest Email Preferences` · `Guest Login` · `Guest Signup` ·
`Guest Verify Email` · `Guest Folio Receipt` — **all ✅ EXIST.**
Guest PDF pack (all ✅): `Guest Folio Receipt PDF` · `Guest VAT Invoice PDF` · `Guest Credit Note PDF` ·
`Guest Refund Receipt PDF` · `Guest Statement of Account PDF` · `Guest Arrival Info PDF`.

### Settings / RBAC — **all designed (100%)**

| Needed screen | Design | Built? |
|---|---|---|
| Operator Settings | ✅ `Operator Settings.html` | — |
| Operator Property Settings | ✅ `Operator Property Settings.html` | — |
| Operator Profile | ✅ `Operator Profile.html` | — |
| Operator Roles | ✅ `Operator Roles.html` | — |
| Operator Security | ✅ `Operator Security.html` | — |
| Owner Settings / Security / Team | ✅ (see Owner Portal) | — |

### Emails / Notifications — **all designed (100%)** (one built email lacks a template design — see §C)

| Needed screen | Design | Built? |
|---|---|---|
| Booking Confirmation Email | ✅ `Booking Confirmation Email.html` | — |
| Cancellation Email | ✅ `Cancellation Email.html` | — |
| Payment Reminder Email | ✅ `Payment Reminder Email.html` | — |
| Pre-arrival Email | ✅ `Pre-arrival Email.html` | — |
| Refund Confirmation Email | ✅ `Refund Confirmation Email.html` | — |
| Review Request Email | ✅ `Review Request Email.html` | — |
| Verify Email | ✅ `Verify Email.html` | — |
| Waitlist Notification Email | ✅ `Waitlist Notification Email.html` | — |
| Winback Email | ✅ `Winback Email.html` | — |
| Email Previews (catalog) | ✅ `Email Previews.html` | — (dev reference) |
| Email Editor | ✅ `Operator Email Editor.html` | — |
| Email Templates (list) | ✅ `Operator Email Templates.html` | — |
| Owner/Staff Invite Email | ✅ `Owner Invite Email.html` | ✅ built |
| **Password Reset Email** | ❌ **MISSING** | ✅ built (`emails/password-reset` — functional, no design template) |
| Operator Notifications | ✅ `Operator Notifications.html` | — (no backend) |
| Operator Notification Preferences | ✅ `Operator Notification Preferences.html` | — (no backend) |
| Owner Notifications | ✅ `Owner Notifications.html` | — (no backend) |
| Guest Email Preferences | ✅ `Guest Email Preferences.html` | — (no backend) |

### Reviews / Reputation (Tier 2 — no backend) — **3/3 designed (100%)**

`Operator Reviews.html` ✅ · `Owner Reviews.html` ✅ · `Guest Review.html` ✅ (+ `Review Request Email.html` ✅).

### Analytics / Reports (Tier 2 — no backend) — **4/4 designed (100%)**

`Operator Reports.html` ✅ · `Operator Insights.html` ✅ · `Owner Reports.html` ✅ · `Owner Insights.html` ✅.

### Billing / Subscription (Tier 2 — schema-only) — **3/3 designed (100%)**

`Operator Billing.html` ✅ · `Vyrobel Paywall.html` ✅ · `Vyrobel Checkout.html` ✅.

### Channels / Payments — **5/5 designed (100%)**

| Needed screen | Design | Built? |
|---|---|---|
| Operator Channels | ✅ `Operator Channels.html` | — (backend certified) |
| Operator Rates / ARI grid | ✅ `Operator Rates.html` | — (backend certified) |
| Operator Payout Run | ✅ `Operator Payout Run.html` | ✅ built (`/app/payouts/run` — Wave 1) |
| Operator Owner Detail | ✅ `Operator Owner Detail.html` | ✅ built (`/app/owners/{owner}` — Wave 1) |
| Owner Payout Method | ✅ `Owner Payout Method.html` | — (🔴 Stripe Connect) |

---

## C. The MISSING list (actionable)

Only **3 needed screens lack a design** (plus optional infra noted). Coverage is otherwise complete.

### (i) MISSING but backend-ready / infra (Tier 1 — could be designed soon, or skipped)

| Missing screen | Module | What it is | Note |
|---|---|---|---|
| **Operator Units** | Operator/PMS | Operator unit inventory list (`/app/units`) | **Already BUILT** without a dedicated design (styled ad hoc on the operator system). A design is *retroactively optional* — nice-to-have for consistency, not blocking. **S** if wanted. |
| **Password Reset Email** | Emails | Transactional password-reset email template | **Already BUILT & functional** (`emails/password-reset`). No Claude Design template exists (its sibling `Owner Invite Email` does). Cosmetic-only gap. **S** if wanted. |

### (ii) MISSING and needs backend anyway (design + backend both required)

| Missing screen | Module | What it is | Note |
|---|---|---|---|
| **2FA setup / verify** | Auth/Security | Enable-2FA (QR/secret), verify code, recovery codes | No standalone design; would live as panels inside `Operator Security` / `Operator Profile` / `Owner Security` (which **do** exist). Since 2FA is **NEEDS-BACKEND-FIRST** anyway (per TIER1-SPEC), design it alongside building the flow. **M** (backend) + design. |

### (iii) Tier 2 (no backend) missing designs — **NONE**

Every net-new no-backend module (Guest Portal, Reviews, Analytics, Billing, Notifications, Emails, Tax) is
**fully designed already.** There is nothing to design before committing to those modules — the design work is
done; only the backend + integration remains. **This is the key finding: design is NOT the bottleneck for any
future module.**

---

## D. Wave 1 coverage — **10/10 EXIST ✅ · ALL BUILT (2026-07-16)**

Per `docs/TIER1-SPEC.md`, Wave 1 was the READY owner-accounting long-tail. **Every Wave 1 screen had a design —
zero design gate — and every one has since shipped.** Kept as the record that the prediction held: the design
library covered the wave completely, and the build needed no design work first.

| Wave 1 screen | Design | Status |
|---|---|---|
| Owner Balance | ✅ `Owner Balance.html` | ✅ built |
| Owner Expenses | ✅ `Owner Expenses.html` | ✅ built |
| Owner Agreement | ✅ `Owner Agreement.html` | ✅ built |
| Owner Archive | ✅ `Owner Archive.html` | ✅ built |
| Owner Remittance PDF | ✅ `Owner Remittance Advice PDF.html` | ✅ built |
| Owner Booking PDF | ✅ `Owner Booking Summary PDF.html` | ✅ built |
| Owner Unit Detail | ✅ `Owner Unit Detail.html` | ✅ built |
| Operator Owner Detail | ✅ `Operator Owner Detail.html` | ✅ built |
| Payout Run | ✅ `Operator Payout Run.html` | ✅ built |
| Owner Documents | ✅ `Owner Documents.html` | ✅ built |

**Verdict (held): Wave 1 was 100% design-covered; nothing had to be designed first. Wave 2 (Reservations list ·
cancelled/no-show terminal states · Refund · Guests CRM · Guest Profile) then shipped 2026-07-17 on the same
footing — also 100% pre-designed.**

**The immediate build target is no longer a design question.** Next up is the **Wave 2 outstanding register**
(`terminate()`'s missing internal status guard · two dead enum values · the unenforced permission catalog ·
`anonymize()` having no UI trigger) — all **code**, no design needed — and then **Wave 3 (Owner Messages
inbox)**, whose design `Owner Messages.html` already **✅ EXISTS** (see §B, Unified Inbox).

---

## E. Extras / unmatched files (nothing silently ignored)

Files in `docs/design/raw/` that don't map 1:1 to a distinct *needed app screen* — kept, not discarded:

| File | Why it's unmatched | Keep? |
|---|---|---|
| `index.html` | Screen Library manifest (links all screens) | reference — keep |
| `System States.html` | Shared edge/empty/error/loading/404/offline states catalog | **design-system reference — keep** (reuse for every screen's states) |
| `Email Previews.html` | Catalog page previewing all email templates | dev reference — keep |
| `Operator Messages.html` | **Legacy** single-file operator inbox — **superseded** by the built `Operator Inbox` + `Operator Conversation` pair | redundant; keep for reference, don't build against it |
| `Operator Team Schedule.html` | Staff scheduling / roster (drag) — a **module not in the ROADMAP's scope** (net-new, no backend, not yet planned) | design for an **un-scoped future module** — note it exists |
| `Operator Schedule PDF.html` | Printable staff schedule — pairs with Team Schedule | same un-scoped module |

> **Note — un-scoped module surfaced:** `Operator Team Schedule` + `Operator Schedule PDF` are a **staff
> scheduling** module that the ROADMAP did not enumerate (it has no backend and wasn't in the module list).
> Design exists; treat as a **potential Tier-2 module** if staff rostering becomes a priority. Flagging so it
> isn't silently ignored.

---

## Summary

- **Folder truth:** 125 files / 124 screens — and the catalogue now **agrees** (reconciled 2026-07-16). The
  +5 were all now-built inbox/auth exports.
- **Coverage:** near-total. Of ~90 needed screens across all modules, **only ~3 lack a design** — and those
  are: **Operator Units** (already built), **Password Reset Email** (already built), and **2FA** (needs backend
  anyway). **All Tier-2 future modules are fully designed.**
- **Waves 1 & 2:** both were 100% designed and **both are now BUILT** (2026-07-16 / 2026-07-17) — design never
  gated either. **Wave 3 (Owner Messages)** is likewise already designed.
- **Bottleneck reality:** **design is not the constraint** for any planned work. Every buildable-now screen and
  every speculative future module already has its design. The constraint is build effort (Tier 1) and product
  commitment (Tier 2), not design availability.

---

*Read-only audit. Ground truth = the real `docs/design/raw/` listing (125 files) — re-verified 2026-07-17.
The catalogue was updated to match on 2026-07-16 and no longer disagrees; where `index.html`'s own manifest
count still lags, trust the folder.*
