# Transactional emails — exact-match audit (PHASE 1, read-only)

**Scope:** 5 guest/account emails matched to their decoded wireframes as REAL Mailables on real triggers.
**Wireframes:** `Pre-arrival Email.html` · `Payment Reminder Email.html` · `Refund Confirmation Email.html`
· `Review Request Email.html` · `Verify Email.html` (dc-runtime bundles — decoded).
**Date:** 2026-07-30 · **Status:** PHASE 1 complete; awaiting the verify-expiry + review-prefill decisions.

---

## 0 · The big picture — this is REUSE/EXTEND, not build-from-zero

All 5 Mailables + Blade templates **already exist** (`app/Mail/*Mail.php`, `resources/views/emails/*`),
built in Phase 3 as **basic** versions. Two triggers were deferred then and have since **shipped** (Phase 4
guest auth, Phase 5 reviews). So the work is: (1) match each template EXACTLY to its wireframe in email-safe
markup, (2) wire the real data each wireframe shows, (3) wire the ONE still-missing trigger (pre-arrival).
Reuse the shared `emails/layout.blade.php` (480px card, terracotta `#C2410C`, table-based, inline CSS —
anti-drift) + the bulletproof-button pattern from `emails/review-request.blade.php`.

---

## 1 · Triggers — what's real vs what to build

| Email | Real trigger | Status |
|---|---|---|
| **Refund** | `payment.refunded` domain event → `SendGuestTransactionalEmails::refundConfirmation()` (only `status==='succeeded'`) | ✅ **REAL, wired** |
| **Review request** | `reservation.checked_out` → `SendGuestTransactionalEmails::reviewRequest()`, links `route('guest.review', $res)` | ✅ **REAL, wired** (Phase 5) |
| **Payment reminder** | `emails:send-payment-reminders` command (daily 09:00), 3-day window, `FolioTotals::for()['balance_minor'] > 0` | ✅ **REAL, scheduled** |
| **Pre-arrival** | *(none)* — `PreArrivalMail` docblock: "TRIGGER … deferred" | 🔨 **BUILD** a scheduled command (mirror `SendPaymentReminders`; 48h = ArrivalPack's `LEAD_DAYS=2`) |
| **Verify** | `GuestAccountService::sendClaimLink()` on the booking-tied **claim** flow (`guest.claim`) | ✅ **REAL, wired** (Phase 4; NOT the domain listener) |

**No email will claim a trigger it lacks.** The only trigger to build is pre-arrival's scheduled command.

---

## 2 · The door-code + WiFi (pre-arrival) — CONFIRMED real + guest-only ✅

- **Real source:** `ArrivalPack::for($reservation, $today)` returns `door_code` / `wifi_ssid` /
  `wifi_password` / `directions` / `check_in_time` / `check_out_time` from the assigned physical `Unit`
  (`door_code` + `wifi_password` are **encrypted** casts; `wifi_ssid` plain). This is the SAME resolver the
  guest Arrival Info screen uses — anti-drift.
- **Timing gate baked in:** the secrets are released only within `LEAD_DAYS = 2` of check-in through
  check-out (and never for a cancelled stay). The pre-arrival email fires at **48h before check-in = exactly
  when the window opens**, so `ArrivalPack::for()` returns the real code/wifi at send time; before the window
  it returns null (the email would say when they unlock — never fabricated).
- **Guest-only delivery — the #33 owner-perimeter is NOT touched.** #33 forbids exposing arrival secrets to
  the **OWNER**; it has never forbidden the **guest** seeing their own stay's access. This email is sent by
  `SendGuestTransactionalEmails::sendToGuest()` → **the lead guest's own email only** (encrypted cast →
  plaintext at send). No owner/operator surface receives it. **This is correct delivery, not a violation.**

---

## 3 · Per-email element classification

### EMAIL 1 — Pre-arrival (48h before check-in)
| Element | Class | Source |
|---|---|---|
| Villa name · "sea-view studio" | ✅ REAL / 🚫 subtitle | property/unit name; the "sea-view studio" descriptor omitted (not modelled) |
| **Door code** + "works from {arrival}" | ✅ REAL (guest-only) | `ArrivalPack::for()` `door_code` + `released_from` |
| Directions + "Open in Maps" | ✅ REAL | `ArrivalPack::directions()` (property address); Maps = a maps URL of the address |
| Parking | 🚫 HONEST | not modelled → omit (never "Space 4") |
| **WiFi** network + password | ✅ REAL (guest-only) | `ArrivalPack` `wifi_ssid`/`wifi_password` |
| Check-in/out times | ✅ REAL | `ArrivalPack` `check_in_time`/`check_out_time` |
| "€120 balance due · pay now" | ✅ REAL / 🔨 | real `FolioTotals` balance (shown only if `> 0`); "Pay now" → `route('guest.payments')` |
| Reference · View booking · Online check-in · Message us | ✅ REAL | `reference`; guest routes; the relay |
| 🔨 trigger | BUILD | `emails:send-pre-arrival` scheduled command |

### EMAIL 2 — Payment reminder (3 days before, if balance due)
Total / Paid / **Balance due** — ✅ REAL via `FolioTotals` (total − paid = balance, reconciling #17;
`balance_minor > 0` is the send condition). "Paid to Booking.com" → the real channel/source (or "Paid"). "Pay
now" → `route('guest.payments')` (the POST `guest.payments.pay` can't be email-linked). Arriving/Unit/Reference
✅ REAL. "Visa/Mastercard/Apple Pay" line = generic accepted-methods copy (honest — Checkout does accept them).

### EMAIL 3 — Refund confirmation (on refund issued)
Amount refunded — ✅ REAL (`abs(amount_minor)`). **"To {card}"** — 🚫 HONEST: Payment stores **no
`card_last4`** (token-only) → "your original payment method" (never a fabricated "···· 4242"; #28 satisfied —
nothing to leak). **Reason** — 🚫 HONEST: no per-refund reason column → show the reservation's
`cancellation_reason` if set, else omit the row. Reference / villa / dates — ✅ REAL. **ETA** — 🚫 HONEST
("5–10 business days · your bank may take longer" — never a guaranteed date). **Status timeline**
(Requested/Processed/Arriving) — Requested + Processed = real `processed_at` ("Today"); Arriving = the honest
ETA range.

### EMAIL 4 — Review request (after checkout, once)
"Thanks for staying, {name}" · stay/unit/reference — ✅ REAL. **Star-tap → the review** — ✅ REAL link
(`route('guest.review', $res)`); **"opens your review, already halfway done" (rating pre-fill)** — 🔨 BUILD a
`?rating=N` read in `GuestReviewController::show` + the review view (small, clean). **"We only ask once"** — ✅
REAL: one review per stay (DB `unique('reservation_id')`) + checkout fires once. "Sofia Duarte" byline →
honest generic ("your host / the {property} team" — no fabricated person).

### EMAIL 5 — Verify (on account creation → the booking-tied CLAIM)
"Confirm your email, {name}" · **Verify button** + copy-paste link — ✅ REAL: the signed
`guest.claim.verify` URL from `GuestAccountService::sendClaimLink()`. "no account will be created if you
ignore" — ✅ REAL (booking-tied claim; nothing activates until verified). **Expiry "24 hours"** — ⚠️ the real
signed link lasts **1 hour** → **Decision A** (honest copy at 1h vs extend to 24h). **"{creditLabel} is waiting
for you" (welcome credit)** — 🚫 **HONEST-OMIT**: there is **no welcome-credit feature** (`GuestCreditEntry`
types = refund/add_on_cancelled/referral/loyalty/birthday/promo/applied/reversal/adjustment — **no
welcome/signup**; no grant tied to claim). Omit the credit teaser entirely — never fabricate "€X credit
waiting". The verify itself is fully real.

---

## 4 · Reuse map

`emails/layout.blade.php` (shared shell) · button pattern from `emails/review-request.blade.php` ·
`ArrivalPack::for()` (door/wifi/directions/times) · `FolioTotals::for()` (balance) · `MoneyFormatter` ·
`GuestName::display` (name-only) · `route('guest.payments')` (pay landing) · `route('guest.review', …)` +
`GuestReviewController` (review) · `GuestAccountService::sendClaimLink` + `guest.claim.verify` (verify) ·
`SendGuestTransactionalEmails` (the trigger hub) · `SendPaymentReminders` (the command template to mirror for
pre-arrival) · `EmailPreviewController` (operator preview page — verify all 5 render).

## 5 · Guards
#17 balance/refund reconcile (real `FolioTotals`/`amount_minor`, never fabricated) · #28 no card leak (none
stored; name-only guest) · **#33 arrival secrets guest-only — the owner-perimeter is untouched (this email →
the guest's own address; no owner/operator surface)** · #39 no fabrication (no fake balance/refund/reason/
card/credit/trigger/reviewer/parking).

## 6 · Build plan (two gates)
- **GATE 1 (guest stay):** pre-arrival (match design; wire `ArrivalPack` door/wifi + real balance + pay link;
  BUILD `emails:send-pre-arrival` scheduled command) · payment-reminder (match; add the real Pay-now button +
  Total/Paid/Balance) · refund (match; add masked-method honest line + reason-if-set + honest ETA + status
  timeline). Extend the 3 Mailables to pass the extra real data. Tests: the pre-arrival command sends 48h out
  with the released code; amounts reconcile; guest-only delivery.
- **GATE 2 (account):** review-request (match; BUILD the `?rating` pre-fill; honest byline) · verify (match;
  real claim link; honest expiry per Decision A; **omit the credit**). Consolidate docs; bump HEAD.

---

## FOUNDER DECISIONS
- **A — Verify link expiry:** the real signed claim link lasts **1 hour**; the wireframe says 24h.
- **B — Review rating pre-fill:** build the `?rating=N` star deep-link (matches "already halfway done") vs a
  plain review link.
- **(confirmed, not a choice)** Welcome credit = **HONEST-OMIT** (no feature); parking/"sea-view studio"/
  named host = honest-omit; door-code/wifi = real + guest-only.

## RESOLUTION
_(appended at GATE close)_
