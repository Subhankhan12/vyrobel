# Vyrobel — Design Coverage Report (exists vs missing per screen)

**Purpose.** For every screen Vyrobel needs (built + planned, per `docs/ROADMAP.md` + `docs/TIER1-SPEC.md`),
state whether a design HTML already exists in `docs/design/raw/` (name the file) or is **❌ MISSING** (needs
creating in Claude Design before it can be built). Ground truth = the **real folder listing**, not the
catalogue's stated count.

**Headline:** design coverage is **near-total** — the design library actually **exceeds** what's built (most
files are designs for not-yet-built modules). Of ~90 needed screens, only **~3 are missing a design**, and
those are edge cases (one already built without a design, one gated on a new backend, one a minor email
template). **Wave 1 (the immediate build target) is 100% designed — no design gate blocks it.**

---

## A. Folder truth

- **Actual files in `docs/design/raw/`: 125** (all `.html`; no non-HTML files).
- **Screens = 124** (125 minus `index.html`, the Screen Library manifest).
- **Catalogue drift:** `docs/design/CATALOGUE.md` states **120 files (119 screens + index)** — **off by 5.**
  The folder has **5 more** files than the catalogue enumerates. The extras are **newer exports** that
  correspond to screens that are **now built** (inbox + auth):

  | Extra file (in folder, not in catalogue's count) | What it is | Build status |
  |---|---|---|
  | `Operator Inbox.html` | Operator unified-inbox thread list | ✅ built (`/app/inbox`) |
  | `Operator Conversation.html` | Operator inbox conversation view | ✅ built (`/app/inbox/{thread}`) |
  | `Owner Invite Email.html` | Owner/staff invite email template | ✅ built (`emails/owner-invite`) |
  | `Vyrobel Invite Owner.html` | Operator "invite owner" screen | ✅ built (`/app/owners` modal) |
  | `Vyrobel Set Password.html` | Accept-invite / set-password screen | ✅ built (`auth/set-password`) |

  > **Action:** `docs/design/CATALOGUE.md` should be reconciled to **125 files / 124 screens** and the 5
  > screens above added to its tables (they're currently absent or under-counted). This report already accounts
  > for them.

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
| Owner Unit Detail | ✅ `Owner Unit Detail.html` | — |
| Owner Expenses | ✅ `Owner Expenses.html` | — |
| Owner Balance | ✅ `Owner Balance.html` | — |
| Owner Agreement | ✅ `Owner Agreement.html` | — |
| Owner Bookings | ✅ `Owner Bookings.html` | — |
| Owner Booking Detail | ✅ `Owner Booking Detail.html` | — |
| Owner Block Calendar | ✅ `Owner Block Calendar.html` | — |
| Owner Rates | ✅ `Owner Rates.html` | — |
| Owner Documents | ✅ `Owner Documents.html` | — |
| Owner Archive | ✅ `Owner Archive.html` | — |
| Owner Remittance Advice PDF | ✅ `Owner Remittance Advice PDF.html` | — |
| Owner Booking Summary PDF | ✅ `Owner Booking Summary PDF.html` | — |
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
| Operator Reservations (list) | ✅ `Operator Reservations.html` | — |
| Operator Reservation Cancelled | ✅ `Operator Reservation Cancelled.html` | — |
| Operator Reservation No-show | ✅ `Operator Reservation No-show.html` | — |
| Operator New Reservation | ✅ `Operator New Reservation.html` | — |
| Operator Group Booking | ✅ `Operator Group Booking.html` | — |
| Operator Refund | ✅ `Operator Refund.html` | — |
| Operator Guests (CRM) | ✅ `Operator Guests.html` | — |
| Operator Guest Profile | ✅ `Operator Guest Profile.html` | — |
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
| Operator Payout Run | ✅ `Operator Payout Run.html` | — |
| Operator Owner Detail | ✅ `Operator Owner Detail.html` | — |
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

## D. Wave 1 coverage (the immediate build target) — **10/10 EXIST ✅**

Per `docs/TIER1-SPEC.md`, Wave 1 is the READY owner-accounting long-tail. **Every Wave 1 screen has a design —
zero design gate; all can be built now.**

| Wave 1 screen | Design | Status |
|---|---|---|
| Owner Balance | ✅ `Owner Balance.html` | build now |
| Owner Expenses | ✅ `Owner Expenses.html` | build now |
| Owner Agreement | ✅ `Owner Agreement.html` | build now |
| Owner Archive | ✅ `Owner Archive.html` | build now |
| Owner Remittance PDF | ✅ `Owner Remittance Advice PDF.html` | build now |
| Owner Booking PDF | ✅ `Owner Booking Summary PDF.html` | build now |
| Owner Unit Detail | ✅ `Owner Unit Detail.html` | build now |
| Operator Owner Detail | ✅ `Operator Owner Detail.html` | build now |
| Payout Run | ✅ `Operator Payout Run.html` | build now |
| Owner Documents | ✅ `Owner Documents.html` | build now |

**Verdict: Wave 1 is 100% design-covered. Nothing to design first — proceed straight to the styled-UI build.**

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

- **Folder truth:** 125 files / 124 screens (catalogue says 120 — **reconcile +5**, all now-built inbox/auth).
- **Coverage:** near-total. Of ~90 needed screens across all modules, **only ~3 lack a design** — and those
  are: **Operator Units** (already built), **Password Reset Email** (already built), and **2FA** (needs backend
  anyway). **All Tier-2 future modules are fully designed.**
- **Wave 1:** **10/10 designed** — no design work precedes the build.
- **Bottleneck reality:** **design is not the constraint** for any planned work. Every buildable-now screen and
  every speculative future module already has its design. The constraint is build effort (Tier 1) and product
  commitment (Tier 2), not design availability.

---

*Read-only audit. Ground truth = the real `docs/design/raw/` listing (125 files). Trust the folder over the
catalogue; the catalogue should be updated to match.*
