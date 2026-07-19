# Vyrobel — Manual QA Walkthrough

**Purpose.** A structured, click-by-click pass over EVERY built screen, in EVERY role, so a human tester (Subhan)
can find defects the automated suite can't (rendering, layout, real-browser interaction, journey coherence). Run
it top-to-bottom; tick each box; log anything that doesn't match **Expect**. Grounded in the real routes at HEAD
(Phase 0). **~40 built screens across public / operator / owner / auth.**

> ⚠️ **"Soon" is NOT a bug.** Every screen honestly shows some affordances as disabled **"Soon"** — these are
> deferred subsystems (§7 lists them per screen). Do **not** log a "Soon" chip/field as broken; that's the
> honesty rule working. Log it only if a "Soon" control actually *does* something, or a real control is missing.

---

## 0. Setup (once)

```bash
# genuine MySQL 8 (not MariaDB) + Redis running
php artisan migrate:fresh --seed      # clean demo data
php artisan serve                     # http://127.0.0.1:8000
# optional, for invite/reset emails: run `mailpit` (UI http://127.0.0.1:8025)
```

**The four demo logins** (all password `password`) — they exist to demo the whole RBAC story, not one god-account:

| Login | Role | Reaches | Can do the money/admin acts? |
|---|---|---|---|
| `demo-admin@vyrobel-demo.test` | Property Owner (**org admin**, `['*']`) | `/app` | **Yes** — invite, refund, payout, everything |
| `demo-accountant@vyrobel-demo.test` | Accountant | `/app` | **Refund + Payout run** — but NOT invite |
| `demo-staff@vyrobel-demo.test` | Hotel Manager | `/app` | Runs the house — **NO** refund, **NO** payout, **NO** invite |
| `demo-owner@vyrobel-demo.test` | External Owner | `/owner` **only** | Refused from `/app` entirely (invariant #26) |

Public storefront (no login): `http://127.0.0.1:8000/book/vyrobel-demo`.

---

## 1. Public booking storefront (NO login) — 3 screens

| # | Screen · URL | Do | Expect |
|---|---|---|---|
| ☐ | **Browse** · `/book/vyrobel-demo` | Load; pick dates | Real property, room types with **real photos**, prices; no login prompt |
| ☐ | **Room detail** · click a room | View gallery + price | Photo gallery, per-night price, availability for the dates; a "Book" CTA |
| ☐ | **Checkout** · proceed to book | Enter guest details, confirm | A REAL booking is created (guarded — no double-book); lands on a **confirmation** page with the reference |
| ☐ | Isolation | Try `/book/some-other-slug` | 404 / not found — only the demo property resolves |

**After booking:** the new reservation should appear in the operator Reservations list (§2) — confirming the public write is real.

---

## 2. Operator app (`/app`) — 21 screens · log in as **demo-admin** first (sees everything)

> Repeat the money-gated rows (refund, payout, invite) as **demo-staff** to confirm the controls DISAPPEAR (§6).

### Operations
| # | Screen · URL | Do | Expect |
|---|---|---|---|
| ☐ | **Dashboard** · `/app/dashboard` | Load | Real KPIs (occupancy summed org-wide, arrivals, revenue) — no obviously-fake numbers |
| ☐ | **Calendar / Rack** · `/app/rack` | Load; change month via the arrows/links | A rack of units × dates with the real demo reservations placed; month navigation works (GET links, not drag) |
| ☐ | **Front desk** · `/app/front-desk` | Find an arrival → **Check in**; find an in-house → **Check out** | Status changes persist on reload; check-out dirties the unit (housekeeping) |
| ☐ | **Housekeeping** · `/app/housekeeping` | Advance a task's status | The board updates; status persists |
| ☐ | **Maintenance board** · `/app/maintenance` | See lanes by status; **New ticket**; **Assign** one | Ticket created + assigned; an OOS ticket shows the "Unit OOS" badge |
| ☐ | **Work order** · click a ticket | **Start** → **Resolve** (add a cost) | Lifecycle persists; a costed ticket on an OWNED unit shows the real owner-expense card; illegal transitions are refused |
| ☐ | **Inbox** · `/app/inbox` | Load; use channel filters/chips | Real threads; guest NAME shown, no email/phone; channel badges |
| ☐ | **Conversation** · open a thread | **Reply** on the manual thread; Assign / Close / Snooze / Reopen | Reply delivers; states persist; the composer for a deferred channel honestly says "not connected" |
| ☐ | **Units** · `/app/units` | Load; upload/reorder a **room-type photo** | Units + room types render; photo upload works (rejects non-images) |

### Reservations & guests
| # | Screen · URL | Do | Expect |
|---|---|---|---|
| ☐ | **Reservations list** · `/app/reservations` | Load; click stage chips (Upcoming/Past/Cancelled…); search a name/ref | List filters by stage; search works; totals + balances real |
| ☐ | **New reservation** · `/app/reservations/create` | Pick unit + dates + guest; create | Honest per-unit availability (no double-book); an optional payment via the manual path; **NO raw card field** |
| ☐ | **Reservation detail** · click a reservation | View folio, stage badge, guest (name only) | Real folio ledger; Cancelled/No-show render as terminal states HERE (no separate screen); deferred actions flagged "Soon" |
| ☐ | **Cancel / No-show** · on the detail | **Cancel** a reservation; **Mark no-show** another | Terminal state stamped; a terminal reservation cannot be re-terminated (guard) |
| ☐ | **Refund** · `/app/reservations/{id}/refund` *(admin/accountant)* | Refund the remaining, or a part | Reversing ledger entry; cannot refund more than the payment's remaining (guard); balance goes back up |
| ☐ | **Guests CRM** · `/app/guests` | Load; search; open a profile | List with VIP/risk/tags; **contact PII shows here** (the only place) |
| ☐ | **Guest profile** · `/app/guests/{id}` | Edit + **Save**; add a note + tag; **Erase** *(admin)* | Save persists (phone encrypted at rest); erase is typed-ERASE-confirmed + `guests.erase`-gated |

### Distribution · Owners · Account
| # | Screen · URL | Do | Expect |
|---|---|---|---|
| ☐ | **Rates** · `/app/rates` | Change a price / restriction in the grid; save | Persists via the certified rate service; integer money (no float drift) |
| ☐ | **Owners** · `/app/owners` | Load; **Invite** *(admin only)* | Owner list; the invite modal issues owner OR staff roles (admin only — see §6) |
| ☐ | **Owner detail** · `/app/owners/{id}` | Load | The owner's units, agreement, balances — all owner-scoped |
| ☐ | **Payout run** · `/app/payouts/run` *(admin/accountant)* | Create + **Pay** a payout | Real money write via the certified path (Manual rail); Stripe Connect honestly "Soon" |
| ☐ | **Settings** · `/app/settings` *(manager+)* | Edit property / policies / organization; save | The three sections persist (typed columns); unbacked fields honestly "Soon" |
| ☐ | **Security** · `/app/security` | Change password (see §5); enable 2FA (see §5) | Both REAL; current-password verified; real TOTP |

---

## 3. Owner portal (`/owner`) — 15 screens · log in as **demo-owner**

| # | Screen · URL | Do | Expect |
|---|---|---|---|
| ☐ | **Dashboard** · `/owner` | Load | Owner-scoped overview: balance, recent payouts, units — ONLY this owner's data |
| ☐ | **My units** · `/owner/units` | Load | Only units this owner owns; occupancy per PHYSICAL unit (not room-type-wide) |
| ☐ | **Unit detail** · `/owner/units/{id}` | Load | That unit's occupancy + revenue |
| ☐ | **Statement** · `/owner/statements/{id}` | Load; **Download PDF**; **Booking summary PDF** | On-screen figures reconcile to the PDF to the cent |
| ☐ | **Balance** · `/owner/balance` | Load | Balance = pending payouts + paid, coherent with the ledger |
| ☐ | **Expenses** · `/owner/expenses` | Load | Real owner-expense deductions (incl. any maintenance cost from §2) |
| ☐ | **Payouts** · `/owner/payouts` | Load; **Remittance PDF** on a paid payout | Payout states real; remittance reconciles |
| ☐ | **Archive** · `/owner/archive` | Load; search past statements | Historical statements list |
| ☐ | **Documents** · `/owner/documents` | Load | Owner documents |
| ☐ | **Agreement** · `/owner/agreement` | Load | The management agreement terms (commission %, dates) |
| ☐ | **Messages** · `/owner/messages` + open a thread | **Send** a message to the operator | Owner ↔ operator thread; the owner sees ONLY their own threads (no guest convos) |
| ☐ | **Settings** · `/owner/settings` | Edit profile + Save; change password; enable 2FA | Self-profile persists; password + 2FA REAL (§5) |

---

## 4. The security boundary (do these deliberately — they're the invariants)

| # | Check | Do | Expect |
|---|---|---|---|
| ☐ | **#26 — owner refused from `/app`** | As **demo-owner**, visit `http://127.0.0.1:8000/app/dashboard` | Bounced to `/owner` (or refused) — an owner must NEVER reach the back office |
| ☐ | **#30 — refund gated** | As **demo-staff** (Hotel Manager), open a reservation detail | **No Refund button / no refund link** — only admin/accountant see it |
| ☐ | **#30 — payout gated** | As **demo-staff**, visit `/app/payouts/run` | Refused (403) — payout is an accounting act |
| ☐ | **#30 — invite gated** | As **demo-staff**, open `/app/owners` | **No Invite control** (only admin) |
| ☐ | **Separation of duties works** | As **demo-accountant**, do a refund + a payout | Both succeed — the right person CAN still do the work |
| ☐ | **Guest PII perimeter (#28)** | On front desk / reservations / inbox | Guest **name only**; email/phone appear ONLY on the Guests CRM |
| ☐ | **Cross-org 404** | Log in to one org; guess another org's reservation/guest id in the URL | 404 (not a leak, not a 403-that-reveals) |

---

## 5. Auth flows (the full chapter)

| # | Flow | Do | Expect |
|---|---|---|---|
| ☐ | **Login** | Log in each of the 4 accounts | Admin/accountant/staff → `/app`; owner → `/owner`; wrong password → generic error |
| ☐ | **Forgot / reset password** | `/forgot-password` → request (check Mailpit) → open link → set new password | Reset works; old password no longer logs in; single-use link |
| ☐ | **Invite** *(as admin)* | `/app/owners` → Invite an owner/staff → open the emailed link (Mailpit) → set password | New user activates + auto-logs-in to the right portal with a real role |
| ☐ | **Change password** | `/app/security` (operator) or `/owner/settings` (owner) → change with WRONG current password | Rejected, unchanged; with the correct current password → changed, other "remember me" sessions dropped |
| ☐ | **2FA enroll** | `/app/security` → **Enable two-factor** → scan/enter the secret in an authenticator → confirm the 6-digit code | Activates; **8 one-time recovery codes** shown ONCE — save one |
| ☐ | **2FA login challenge** | Sign out → log in again | After the password you're **challenged** (`/two-factor-challenge`), NOT straight in; a direct `/app` visit bounces to `/login` |
| ☐ | **2FA TOTP** | Enter the current authenticator code | Logged in |
| ☐ | **2FA recovery code** | Sign out → log in → enter a **recovery code** instead | Logged in; that code is now used (a second use is rejected) |
| ☐ | **2FA disable** | `/app/security` → Turn off with WRONG password, then the correct one | Wrong rejected (2FA stays on); correct turns it off; next login is straight-through |

> ⚠️ **After 2FA testing, reset the demo:** `php artisan migrate:fresh --seed` (so the documented demo logins are password-only again).

---

## 6. Role matrix — the same screen, three operator roles

Log in as each and confirm the money/admin controls appear or vanish correctly (the visible proof of invariant #30):

| Control | demo-admin | demo-accountant | demo-staff |
|---|:---:|:---:|:---:|
| Refund button (reservation detail / refund screen) | ✅ | ✅ | ❌ hidden |
| Payout run controls (`/app/payouts/run`) | ✅ | ✅ | ❌ 403 |
| Invite modal (`/app/owners`) | ✅ | ❌ | ❌ |
| Record a payment | ✅ | ✅ | ✅ |
| Cancel / no-show, check-in/out, housekeeping | ✅ | ✅ | ✅ |
| Change password / 2FA (`/app/security`) | ✅ | ✅ | ✅ (everyone) |

---

## 7. Known "Soon" / deferred per surface (do NOT log these as bugs)

- **Operator nav:** **Channels** and (for non-manager) **Rates**/**Settings** show **"Coming soon"** in the nav —
  the Channels *UI* is Phase 1 (the channel manager backend is real + a demo channel is now seeded, but the CRUD
  screen isn't built). Global **Search** is not built (per-list search boxes are).
- **Reservation detail:** add-charge, partial-refund-from-detail, void, capture-hold, modify/move-room, export,
  print, cancellation/no-show FEES — all disabled **"Soon"** (no fee engine).
- **Refund screen:** refund reason, email-the-guest, refund-by-charge — **"Soon"**.
- **New reservation:** raw card capture — deferred to Stripe (manual/pay-on-arrival is the real path).
- **Owner nav:** Calendar, Tax center, Reports, Insights, Rates, Reviews, Team & access, Help, the Notifications
  bell — all **"Coming soon"**. Owner Settings: payout account/IBAN, notification preferences, team — **"Soon"**.
- **Operator Security / Owner Settings:** active sessions, trusted devices, connected apps, IP allow-list — **"Soon"**
  (change-password + 2FA ARE real).
- **Payout run:** Stripe Connect payout rail — **"Soon"** (the Manual rail is the real, working path).
- **Emails:** only invite + password-reset emails send (via Mailpit in dev). No booking-confirmation / reminder /
  etc. emails yet (the notification subsystem is Phase 3).

---

## 8. Logging defects

For each ✗, note: **screen URL · role · steps · what happened · what you expected**. A screenshot helps. Group by
severity (blocks the demo / cosmetic / "Soon"-that-actually-fires). Feed the list back for a fix pass.
