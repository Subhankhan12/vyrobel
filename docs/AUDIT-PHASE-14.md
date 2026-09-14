# AUDIT — PHASE 14 · THE GUEST REALM · THE INVERSION

**Realm:** guest (`/guest`) — a separate `guest` guard over the `guests` table.
**Accounts:** `demo.traveller@` (the one claimed guest) · plus `demo-staff@` and `demo-owner@`, used once
each to test the realm's admission gate in both directions.
**Instrument:** MCP Playwright → `chrome-headless-shell` via the project `.mcp.json`
**Date:** 2026-09-12
**Application code changed:** none — verified, §8. **Suite not run:** no application code changed.

**Evidence tiers.** 🔬 MEASURED · 🖥 BROWSER · 📖 ASSERTED · 📄 RECORDED. **Only 🔬 and 🖥 are facts.**

**Surface.** 🔬 **29 GET + 29 WRITE guest routes = 58** — not the recorded 13. 6 GETs are signed-out.
**Guests.** 🔬 **10 seeded, exactly 1 claimed** (3 reservations). 🔬 `reservation_guests` = **0 rows**,
verifying the brief's claim.

---

## 0. #1 — IS THIS PII *THEIRS*? **Yes. Every field, every screen.**

**The inverted test, applied:** not "are there emails" but "whose are they". 🔬 On every screen, every
rendered email was compared against the signed-in guest's own address:

| screen | emails found | **all theirs?** | foreign | phones | card-like |
|---|---|---|---|---|---|
| `guest/account` | **1** | ✅ **yes** | **0** | 0 | 0 |
| `guest/reservation/{own}/check-in` | **1** | ✅ **yes** | **0** | 0 | 0 |
| `guest/payments` · `wallet` · `trips` · `messages` · `saved` · `notifications` · `help` | 0 | ✅ | **0** | 0 | 0 |
| `guest/reservation/{own}` · `/manage` · `/arrival` · `/extras` · `/receipt` | 0 | ✅ | **0** | 0 | 0 |

**🔬 `foreignEmailCount: 0` on every screen.** The guest sees their own email on their account page and
pre-filled into online check-in — **which is correct, and is exactly what the old pass criterion would
have flagged as a defect.** Their own name is the `h1` on `home`/`trips` ("Welcome back, …") and in the
arrival greeting. No name, email, phone or payment detail belonging to anyone else appears anywhere.

⚠️ **The shared-booking case does not arise.** 🔬 `reservation_guests` is empty, so no co-guest view
exists to leak through. **NOT EXAMINED as a behaviour** — there is no data to exercise it.

---

## 1. #3 — ISOLATION FROM INSIDE: **16 of 16 probes refused, and refused correctly**

Phase 1b proved one probe at one door. This tested it from inside, across every reservation sub-screen.

🔬 Four other guests' reservations × four sub-screens each (`/`, `/manage`, `/receipt`, `/arrival`):

| | result |
|---|---|
| **16 of 16** | **HTTP 404** — *"We couldn't find that"* |
| body length | **233 bytes**, identical every time |
| money figures · emails · phones | **0 · 0 · 0** |

**And the refusal is the right one.** 🔬 A **404, never a 403** — so a probe cannot distinguish "this
booking does not exist" from "this booking is not yours". Existence itself is information, and the product
withholds it. The copy reinforces it: *"The page or record you asked for doesn't exist, **or it isn't part
of your account**. Back to your trips."* — both causes, one message, with a way out.

⚠️ **One claimed guest means the two-claimed-accounts case is UNTESTED.** But the direction that matters —
*can a signed-in guest reach another guest's data* — **was fully tested**, because the other 9 guests hold
39 reservations between them. Isolation is **tested in the direction that carries the risk**, untested for
session-to-session interference between two live accounts.

### Cross-realm admission — clean in **both** directions, and the contrast with F-23

| from | to | result |
|---|---|---|
| guest | `/app/dashboard`, `/app/guests`, `/owner`, `/owner/balance` | 🔬 **all redirect to `/login`** — 0 money |
| operator (`demo-staff@`) | `guest/trips`, `guest/account`, `guest/payments` | 🔬 **all redirect to `/guest/login`** — 0 money, 0 emails |
| owner (`demo-owner@`) | same three | 🔬 **all redirect to `/guest/login`** — 0 money, 0 emails |

**`auth:guest` is the positive gate the owner portal lacks.** F-23 found an operator could enter `/owner`
and be shown a complete-looking portal with €0.00 everywhere. Here the same probe is refused at the door.
**The correct pattern exists in this codebase — it is simply not applied to `/owner`.**

---

## 2. #2 — A STRANGER, ONCE. **The doors are good. The bridge between them is not.**

🔬 Every signed-out screen, judged as a first-time visitor:

| screen | `h1` | what it gives a stranger |
|---|---|---|
| `guest/login` | "Welcome back" | "Sign in to manage your stays" + **"New here? Access your bookings"** |
| `guest/claim` | "Access your bookings" | *"Enter the email from your booking and we'll send you a secure link"* |
| `guest/claim/sent` | "Check your inbox" | 🔬 **neutral enumeration** — *"**If** this email has a booking with us…"* · states the **one-hour expiry** · *"Didn't get it? Check your spam folder"* |
| `guest/forgot-password` | "Reset your password" | clear, with "Back to sign in" |
| bad claim hash | 403 | *"This page belongs to a different booking, or it isn't part of your account. **If you reached it from a link we sent you, the link may have expired**"* |

🔬 All `h1n = 1`. Zero console errors. **These refusals meet the bar the owner portal set** — they name
the likely cause and offer the next step. The claim-sent page in particular is exemplary: it protects
enumeration *and* still helps.

### 🟠 F-27 (MEDIUM) — the paid-but-unclaimed window: every call to action lands on a sign-in wall

🔬 The confirmation page (`booking/reservation.blade.php`) now has `<h1>Booking confirmed</h1>` — **the
recorded "no heading at all" defect is FIXED**, and the Blade comment documents the promotion. It shows
the reference, dates, payment, **Total**, and *"A confirmation is on its way to {email}"*.

⚠️ **Its only action is "Back to {property}" — back to shopping.** It never mentions an account, never
links to `/guest/claim`, and never tells them they can manage the booking online.

🔬 The confirmation **email** is rich and does point into the portal — *"Check in online ›"*,
*"View receipt ›"*, *"See arrival info & house guide"*, *"Message us ›"*, plus calendar links. But
🔬 `BookingConfirmationMail` builds them with plain **`route(...)`, not `signedRoute(...)`**, and those
routes sit behind `auth:guest`.

**So the first-time chain is:** pay → a page that points back to the shop → an email whose every button
lands on **`/guest/login`, headed "Welcome back"** — copy written for a returning user, shown to someone
who has never been here — where the only way forward is a **footer link**, "New here? Access your
bookings", leading to a *second* email and a password.

**It works. It is not a security defect — the redirect is correct.** But for the one person in this
product who has no training, no colleague and no second chance, the path from *"I paid"* to *"I can see my
booking"* runs through a wall that greets them as though they had been here before. 📄 And as the brief
puts it: a guest who cannot get in simply leaves, and nothing in this product would ever report it.

🔬 **A booking reference alone grants nothing** — no reference-keyed route exists. Claiming requires the
booking **email** plus a one-hour signed link (`hash_equals(sha1($email), $emailHash)`, *"no PII in the
URL"*). That is the right design.

⚠️ **NOT EXAMINED:** the confirmation page's live render needs a completed booking with a quote in
session, which this phase must not create. Its **content** is read from source; its **behaviour** is
unexercised — the same gap Phase 0 recorded.

**A route to a human:** 🔬 `/guest/help` renders with a form. The realm does not simply stop.

---

## 3. #5 — 🟠 F-26 (MEDIUM) — deletion is reversible for 14 days; **what it finally does is not disclosed**

⚠️ **The brief's premise is wrong, in the product's favour, and that is worth saying first.** 🔬
`guest/account/delete` **schedules** — it does not delete:

| | measured |
|---|---|
| `GRACE_DAYS` | **14** — `scheduled_deletion_at` = now + 14 days |
| the account meanwhile | **stays fully usable** |
| undo | ✅ a real `guest.account.delete.undo` route; `cancel()` clears the stamp |
| audit | ✅ both `guest.deletion_scheduled` and `guest.deletion_cancelled`, **PII-safe** — `['fields' => [...]]`, never values (#28) |
| the guest | ✅ emailed on schedule **and** on cancel |
| the screen | ✅ requires typing **DELETE**; when scheduled shows the **date** and an *"Undo — keep my account"* button |

**That is a well-built, reversible, audited, warned flow.** The seventh opportunity to be right by not
acting turned out to be an opportunity to correct the premise.

### But the disclosed consequence is not the real one

🔬 `processDue()` → `eraseNow()` calls, for **every still-live reservation**:

```php
// Cancel per policy (+ a full in-window refund, bounded #27) — never block the erase on one.
$this->cancellations->cancelWithPolicyRefund($reservation, 'Guest account deletion');
```

Phase 9 established that this path **issues a real refund and posts an irreversible `owner_ledger`
adjustment**. So a guest pressing a self-service button starts a 14-day timer that **cancels their future
stays and moves money.**

🔬 **What the guest is told, in full:** *"This schedules your profile, saved details and preferences for
permanent removal in 14 days"* and *"everything is kept until it runs."* 🔬 **No guest-facing copy anywhere
mentions that upcoming bookings will be cancelled or refunded.** A guest with a stay next month would lose
it and be told only that their *preferences* were scheduled for removal.

⚠️ **And the file contradicts itself** — the class docblock, 80 lines above the call, says
*"any refund stays the operator's separate step — **the policy is display-only**"*, while the method it
describes calls the refunding path. **F-19's shape** (a comment inverting the truth at a money-relevant
decision point), now in a second file.

**This is the inverse of the product's own best pattern:** the refund screen warns unprompted; the screen
that *causes* a refund does not mention it. **Established entirely by reading. Nothing was deleted** —
🔬 `scheduled_deletion_at` is NULL for all 10 guests at the end of this phase.

---

## 4. #6 — EVERY SCREEN WALKED. **The guest realm is still the cleanest in the product.**

| dimension | measured |
|---|---|
| Screens reached (own) | 🔬 **16** — 9 plain + 7 reservation sub-screens |
| Headings | 🔬 **`h1n = 1` on every reached screen.** 📄 Seven once had none; the fix **HELD** — and the confirmation page's `h1` is now present too |
| Console (app errors) | 🔬 **0** across every screen, signed-in and signed-out |
| JS-driven buttons | 🔬 **0** — the realm is forms and links; **F-20's shape has no surface here** |
| Keyboard | 🔬 **16 stops · 0 without a visible focus ring · 0 controls without an accessible name** |
| 375px | 🔬 **`bodyOverflow = 0` on all 15 screens**; `guest/login` and `guest/claim` have **zero** overflowing elements at all |

📄 The prior audit called this the best realm in the product at 0px overflow and zero console errors.
🔬 **It still is** — and it is the only realm in fourteen phases with no heading defect, no unnamed
control and no focus-ring gap.

**A minor observation, not scored:** 🔬 `/guest/reservation/{own}/review` returns **404** for a future
stay while the guest's two completed stays return **200 ("How was your stay?")**. The state gate is
correct, but the message is the isolation refusal — *"doesn't exist, or it isn't part of your account"* —
for a booking that **is** theirs and simply is not reviewable yet. F-3's shape, smallest instance.

---

## 5. #7 — ZERO RESIDUE, FOURTH CONSECUTIVE PHASE

| table | before | after |
|---|---|---|
| **`rate_calendar`** | 929 / `4fc27b4af1076e86c0d268056067ffa4` | 🔬 **byte-identical** |
| `guests` (claimed **1**) · `reservations` · `payments` · `folios` | 10 · 42 · 16 · 20 | unchanged |
| `owner_ledger` (sum **730640**) · `owner_expenses` · `messages` · `reviews` | 29 · 6 · 9 · 7 | unchanged |
| `audit_events` · `domain_events` | 160 · 196 | **160 · 196 — zero residue** |
| `guests.scheduled_deletion_at` NOT NULL | 0 | **0** |

🔬 **All 14 web-guard demo accounts authenticate, and the `guest` guard authenticates too.**
🔬 `DemoDataSafetyGuard::DEVELOPMENT_ENVIRONMENTS` still `['local','testing']`.
🔬 `git diff --name-only` filtered by session mtime returns **zero tracked files**.

---

## 6. ⚠️ WHAT THIS BRIEF GOT WRONG

1. **"Deletion is irreversible."** 🔬 It is a **14-day reversible schedule** with a real undo, an audit
   trail and two emails (§3). The irreversible part is what the sweep does afterwards — and *that* is
   what goes undisclosed.
2. **"Thirteen signed-in screens."** 🔬 There are **58 guest routes** (29 GET + 29 WRITE); 16 own screens
   were reached in this walk. The recorded 13 predates the programme, as the brief suspected.
3. **"Seven screens had no headings."** 🔬 Fixed — every reached screen has exactly one `h1`, including
   the confirmation page the record singled out as unnamed.
4. **"`reservation_guests` is recorded as always empty."** 🔬 **Correct** — 0 rows.

---

## 7. NEAR-FALSE-POSITIVES — three new, **41 total**

39. **⚠️ The inversion itself.** Applying Phase 12/13's passing criterion — *zero emails on screen* —
    would have flagged `guest/account` (1 email) and `check-in` (1 email) as **defects**. They are
    correct behaviour. **The pass criterion is realm-specific; carrying it forward unexamined would have
    inverted a correct result into a finding.** This is the one the brief predicted, and it was real.
40. **"An own reservation 404s on `/review` — a bug."** 🔬 The guest's other two reservations reach it at
    **200**. It is a state gate on stay completion. **Test the same probe against a sibling before
    calling a refusal a defect.**
41. **"The confirmation email doesn't route the guest into the portal."** 🔬 It does, richly — five deep
    links plus calendar files. The defect is narrower and different: those links require auth (F-27).
    **Read the artefact before characterising what it omits.**

---

## 8. STATUS

**The audit programme is complete.** Fifteen phases (0–14) across five realms: the marketing/storefront
front, the operator back office (all eleven back-office roles), the platform admin, the owner portal and
the guest realm.

**Findings: F-1 … F-27**, of which this phase adds **F-26** and **F-27**.
**Nothing was fixed. No application code was changed in any phase.**

**The programme's closing summary — every finding with severity and status, every open founder ruling,
every NOT-EXAMINED gap, everything that could not be tested, and the full near-false-positive register —
is `docs/AUDIT-SUMMARY.md`.** That document is what a fix programme should be built from.
