# GUEST REALM — DESIGN-PARITY RECON

**Date:** 2026-08-11 · **HEAD at recon:** `974a1b9` (owner realm complete — 32 pages + the invite email)
**Status:** ⚠️ **ANALYSIS ONLY.** No app code, Blade, CSS or test was touched. This document maps the realm
before it is walked.

**Prototypes found: exactly 32** `Guest *.html` files in `docs/design/raw/`.

---

## ⚠️ 0 · THREE PREMISE CORRECTIONS — read these before anything else

### 0.1 ⚠️ THE GUEST REALM IS **NOT** "UNTOUCHED"

`PROJECT-STATE.md` §N.12 and the walk brief both record the guest realm as *"32 prototypes, UNTOUCHED."*
**That is wrong.** The 2026-07-25 design-fidelity pass ran a **GATE 3 (guest)** over **16 recorded surfaces**
(claimed as 17) and a **GATE 4** over the public storefront, and closed both as:

> *"✅ COMPLETE — all 17 guest surfaces diffed (**0 code changes** — entirely at-parity / intentional-divergence)."*

⚠️ **Treat that record as UNVERIFIED, not as prior art.** §N.8b now has **seven instances** of a record
denying or misstating the code, and the most recent — the **Owner Invite Email** — was a register row
claiming the email *"matched exactly"* when the design's **entire gradient brand band, the invited-by chip,
the feature panel and the footer band did not exist**. A pass that diffed 17 guest surfaces and changed
**zero lines** is the same shape of claim. It is also explicable without dishonesty: GATE 3 was the *old*
presentation-only three-category pass. It did **not** measure contrast, did **not** measure keyboard
operability, did **not** pixel-sample gradients, and did **not** mutation-prove guards. "At parity" under
those criteria says nothing about these.

**Consequence for the walk:** the 16 GATE-3 rows are **leads, not conclusions**. Where a page gate finds a
real divergence, the register row must be corrected in place (as `§O-Invite` did), not merely appended to.

### 0.2 ⚠️ THE GUEST AUTH MODEL IS A **FULL SEPARATE GUARD**, NOT A MAGIC-LINK MODEL

The brief anticipates *"guests reach pages via a tokened/magic link (not a full login), so #20 (GET-link
scope) + `#[Locked]` are load-bearing."* **The code says otherwise, and this materially reshapes the walk.**

The guest portal is a **real second auth realm**: `Route::middleware(['auth:guest', SetTenantForGuestRequest::class])`
wraps every portal route, with a real login (`/guest/login`), password reset, and a booking-tied *claim*
flow. Isolation is **not** carried by link tokens and **not** by `#[Locked]` (there are **no Livewire
components in the guest portal** — every surface is a plain controller + Blade).

**The actual mechanism is one trait and one relation:**

```
app/Http/Controllers/Guest/Concerns/ResolvesGuest.php
    guest(): Auth::guard('guest')->user()          ← SELF, from the guard. Never a request id.

then, in every controller:
    $this->guest()->reservations()->findOrFail($reservation)
                   ^^^^^^^^^^^^^^ resolved THROUGH the guest's own relation
                                  → a foreign id does not resolve → 404
```

Verified present in **10 controllers** (Arrival ×2, Check-in, Document, Extras, FolioPayment, Manage,
Receipt, Reservation, Review). This is the guest analog of `#38`/`#42`, implemented as *scope-by-relation*
rather than *scope-by-token*.

**Only two guest surfaces are genuinely signed-link:** `guest.claim.verify` / `guest.claim.complete`
(`signed` middleware) and the public `marketing.unsubscribe` (`signed`). **Those two are where the #20-style
reasoning actually applies** — everywhere else, the question at the page gate is *"does this controller
resolve through `$this->guest()->…`, or did it reach for route-model binding?"*

⚠️ **The single highest-value mutation for this realm** is therefore: swap a controller's
`$this->guest()->reservations()->findOrFail()` for `Reservation::findOrFail()` and confirm the
cross-guest test goes red. Per §N.4p and the O12 lesson — *when a mutation passes, ask what you actually
removed* — a mutation that misses this relation proves nothing.

### 0.3 ✅ THE "NO-ALPINE GET REALM" PREMISE IS **CORRECT** — verified, not assumed

`grep -rln "x-data" resources/views/guest/ resources/views/components/guest/` returns **nothing**. Zero
Alpine components in the guest realm. Interactivity is GET links + `<form>` POSTs.

⚠️ **But that does not make the a11y axis cheap — it moves the risk.** There are **31 `<form>` elements
across 19 guest views**, and §N.4k (implicit submit) is a *form* trap, not an Alpine trap. The densest are
**Account (8 forms)**, **Saved (4)**, **Manage (3)** and **Wallet (2)** — a single-input form with more than
one submit path is exactly where Enter picks the wrong action. These four views are the realm's §N.4k
hot spots.

---

## 1 · THE MAPPING TABLE — 32 prototypes

**Auth key:** `G` = `auth:guest` portal · `SIGNED` = HMAC-signed link · `PUBLIC` = no auth.
**Surface key:** `RO` read-only info · `DOC` PDF · `MUT` mutation/form · `LIST` list/rollup · `PUB` public/brand.

| # | prototype | route | controller / view | auth | surface | ⚠️ privacy flags |
|---|---|---|---|---|---|---|
| 1 | **Guest Arrival Info** | `GET /guest/reservation/{r}/arrival` | `GuestArrivalController@show` · `guest/arrival` | G | RO | 🔴 **DOOR-CODE #33** · GUEST-PII |
| 2 | **Guest Arrival Info PDF** | `GET …/arrival/pdf` | `GuestArrivalController@pdf` · `pdfs/arrival` | G | DOC | 🔴 **DOOR-CODE #33** · PDF-SWEEP |
| 3 | **Guest Reservation** | `GET /guest/reservation/{r}` | `GuestReservationController@show` · `guest/reservation` | G | RO | 🟠 reads `ArrivalPack` → **#33** · GUEST-PII |
| 4 | **Guest Check-in** | `GET`+`POST …/check-in` | `GuestCheckinController` · `guest/checkin` | G | MUT | 🔴 **GUEST-PII WRITE** · **ID DOCS** · §N.4k |
| 5 | **Guest Payments** | `GET /guest/payments` · `POST …/{r}/pay` | `GuestPaymentsController` / `GuestFolioPaymentController` · `guest/payments` | G | LIST+MUT | 🔴 **PAYMENT — real money** (#45 Stripe seam) |
| 6 | **Guest Folio Receipt** | `GET …/receipt` | `GuestReceiptController@show` · `guest/receipt` | G | RO | 🟠 PAYMENT · GUEST-PII |
| 7 | **Guest Folio Receipt PDF** | `GET …/receipt.pdf` | `GuestDocumentController@folioReceipt` · `pdfs/folio` | G | DOC | 🟠 PAYMENT · **PDF-SWEEP** |
| 8 | **Guest VAT Invoice PDF** | `GET …/invoice.pdf` | `GuestDocumentController@vatInvoice` · `pdfs/guest-vat-invoice` | G | DOC | 🟠 PAYMENT · **PDF-SWEEP** |
| 9 | **Guest Credit Note PDF** | `GET …/credit-note.pdf` | `GuestDocumentController@creditNote` · `pdfs/guest-credit-note` | G | DOC | 🟠 PAYMENT · **PDF-SWEEP** |
| 10 | **Guest Refund Receipt PDF** | `GET …/refund.pdf` | `GuestDocumentController@refundReceipt` · `pdfs/guest-refund-receipt` | G | DOC | 🟠 PAYMENT · **PDF-SWEEP** |
| 11 | **Guest Statement of Account PDF** | `GET …/statement.pdf` | `GuestDocumentController@statement` · `pdfs/guest-statement` | G | DOC | 🟠 PAYMENT · **PDF-SWEEP** |
| 12 | **Guest Messages** | `GET /guest/messages` · `GET/POST …/{thread}` | `GuestMessagesController` · `guest/messages`, `message-thread` | G | MUT | 🔴 **MESSAGING — the #28 boundary from the guest side** |
| 13 | **Guest Review** | `GET`+`POST …/{r}/review` | `GuestReviewController` · `guest/review` | G | MUT | 🟠 **REVIEW-WRITE** · **DO10-4 star site** |
| 14 | **Guest Manage Booking** | `GET …/manage` + 3 POSTs | `GuestManageController` · `guest/manage` | G | MUT | 🟠 cancel / date-change / add-ons as **REQUESTS** · §N.4k ×3 |
| 15 | **Guest Add-ons** | `GET …/extras` | `GuestExtrasController@show` · `guest/extras` | G | MUT | 🟡 request-only write |
| 16 | **Guest Trips** | `GET /guest/trips` | `GuestTripsController@index` · `guest/trips` + `partials/trip-card` | G | LIST | 🟡 **DO10-4 star site** · post-login landing |
| 17 | **Guest Account** | `GET /guest/account` + 5 POSTs | `GuestAccountController` · `guest/account` | G | MUT | 🔴 **GUEST-PII** · password · **account DELETE** · §N.4k ×8 |
| 18 | **Guest Credits** | `GET /guest/wallet` + 2 POSTs | `GuestWalletController` · `guest/wallet` | G | LIST+MUT | 🟠 **PAYMENT** (append-only wallet ledger) |
| 19 | **Guest Wishlist** | `GET /guest/saved` + 2 POSTs | `GuestSavedController` · `guest/saved` | G | LIST+MUT | 🟡 ⚠️ **shares ONE surface with #20** |
| 20 | **Guest Waitlist** | `GET /guest/saved` + 3 POSTs | `GuestSavedController` · `guest/saved` | G | LIST+MUT | 🟡 ⚠️ **shares ONE surface with #19** · §N.4k ×4 |
| 21 | **Guest Login** | `GET`+`POST /guest/login` | `GuestLoginController` · `guest/auth/login` | PUBLIC | MUT | 🟠 credentials · enumeration · **brand/gradient** |
| 22 | **Guest Signup** | `GET`+`POST /guest/claim` (+ `/claim/sent`) | `GuestClaimController` · `guest/auth/claim`, `sent` | PUBLIC | MUT | 🟠 **booking-tied claim only** (no open signup) |
| 23 | **Guest Verify Email** | `GET`+`POST /guest/claim/verify/{guest}/{hash}` | `GuestClaimController@verify/claim` · `guest/auth/set-password` | **SIGNED** | MUT | 🔴 **the ONE real signed-link surface — the #20 analog** |
| 24 | **Guest Browse** | `GET /book/{property}` | `BookingEngineController@browse` | PUBLIC | PUB | 🟠 **brand/gradient → getComputedStyle trap** · prior GATE-4 "scope divergence" |
| 25 | **Guest Villa Detail** | `GET /book/{property}/rooms/{roomType}` | `BookingEngineController@room` | PUBLIC | PUB | 🟠 brand/gradient · real reviews (#37) |
| 26 | **Guest Checkout** | `GET`+`POST /book/{property}/checkout` | `BookingEngineController@checkout/reserve` | PUBLIC | PUB+MUT | 🔴 **booking write** · #45 no raw PAN |
| 27 | **Guest Booking** | `GET /book/{property}/confirmation/{quote}` · `…/reservation/{quote}` | `BookingEngineController@confirmation/reservation` | PUBLIC | PUB | 🟠 confirmation brand surface · **quote id in URL — verify scoping** |
| 28 | **Guest Email Preferences** | ⚠️ **FOLDED** — `POST /guest/account/preferences` + `GET/POST /unsubscribe/{guest}` (SIGNED) | `GuestAccountController@updatePreferences` · `MarketingUnsubscribeController` | G / SIGNED | MUT | 🟡 no dedicated page; consent split across two surfaces |
| 29 | **Guest ID Verification** | ⚠️ **FOLDED** — inside check-in (`GuestIdDocumentService`) | `GuestCheckinController` · `guest/checkin` | G | MUT | 🔴 **ID DOCS**; standalone KYC is account-gated |
| 30 | **Guest Documents** | 🚩 **NO LIVE ROUTE** | — (per-reservation PDFs exist; no hub) | — | — | see §4 |
| 31 | **Guest Help** | 🚩 **NO LIVE ROUTE** | — (owner has `/owner/help`; guest has none) | — | — | see §4 · ⚠️ ties to `DO-INV-3` |
| 32 | **Guest Referral Landing** | 🚩 **NO LIVE ROUTE** | — (`GuestCreditEntry::TYPE_REFERRAL` is a credit *type* only) | — | — | see §4 |

**Live guest surfaces with NO prototype** (walk them anyway, or record the absence):
`/guest/forgot-password` + `/guest/reset-password/{token}` (the register already notes no guest-specific
prototype exists) · `/unsubscribe/{guest}` (`guest/marketing-unsubscribe`) · `/guest/claim/sent`.

🚩 **Dead view:** `resources/views/guest/home.blade.php` exists and contains a form, but nothing renders it —
`GuestHomeController@index` only `redirect()->route('guest.trips')`, and there is no `view('guest.home')`
call anywhere. Harmless today; flagged because at a page gate it could easily be mistaken for the live
post-login landing.

---

## 2 · THE SHARP PAGES — where the care goes

### 🔴 2.1 THE DOOR CODE (#33) — the realm's signature control, and it spans SIX surfaces

The gate is **one shared helper**, `app/Support/Guest/ArrivalPack.php`, which withholds `door_code`,
`wifi_ssid`, `wifi_password` and `arrival_notes` outside the window (`LEAD_DAYS` before check-in through
check-out, and after cancellation), returning `null` plus a `released_from` date so the screen can honestly
say *when* instead of fabricating. `has_code` is exposed separately from `door_code` — a deliberate split
that lets the UI say "a code exists, not yet" without leaking it.

**Six call sites** — the walk must confirm every one reads the shared gate rather than the raw `Unit`:

| surface | file |
|---|---|
| Arrival screen | `guest/arrival.blade.php` |
| Reservation screen | `guest/reservation.blade.php` |
| Check-in screen | `guest/checkin.blade.php` |
| Arrival PDF | `pdfs/arrival.blade.php` via `App\Support\Pdf\ArrivalPackDocument` |
| Pre-arrival email | `emails/pre-arrival.blade.php` via `App\Mail\PreArrivalMail` |
| model | `app/Models/Unit.php` |

⚠️ **To verify at the page gate (§N.8f duplication):** `ArrivalPackDocument` is a *separate class* from
`ArrivalPack`. It must **delegate** to the shared gate, not re-derive the window. Two derivations of one
security window will drift, and the drift direction that matters is the one that shows a code early.
The docblock claims *"the SAME window-gated ArrivalPack as the screen + the #83 email (no drift)"* — that
claim is exactly the §N.8b shape and must be **read against the code**, not trusted.

### 🔴 2.2 GUEST MESSAGES — the #28 boundary, inverted

This is where the guest realm's #28 inversion is most concentrated: the guest sees their **own** thread, and
must not see operator-internal notes, staff identities beyond what is intended, other threads, other guests,
or another tenant's anything. Owner-realm #28 asked *"is the guest's data hidden?"* — here it asks
*"is everything that is NOT the guest's hidden from them?"*

### 🔴 2.3 GUEST CHECK-IN — the only PII-write + ID-document surface

Registration data and identity documents flow **in** here (`GuestIdDocumentService`). Two axes at once:
`#28` (what is stored, and that it reaches `anonymize()` — the CLAUDE.md rule that *every free-text store
about a guest must be added to `anonymize()` in the same change that creates its writer*), and §N.4k on the
form itself.

### 🔴 2.4 GUEST PAYMENTS — real money

`GuestFolioPaymentController@pay` is the guest-side Stripe seam (#45: hosted Checkout, fulfil on the
webhook, no raw PAN). Money + PII on one page. Walk it with the owner-realm money discipline.

### 🟠 2.5 THE PUBLIC/BRAND SURFACES — and the getComputedStyle false-PASS

**Every single guest view uses a gradient** (`linear-gradient` / `--vy-grad` appears in all 14 portal views
plus `components/guest/shell.blade.php` and `guest/auth/layout.blade.php`). §N.8e's newest finding therefore
applies realm-wide, not just to the storefront:

> `getComputedStyle` scores text against `background-color`. A `bgcolor` fallback **under** a gradient reads
> as a **false PASS** — the Owner Invite Email's wordmark reported **5.18** when the real rendered pixel was
> **3.18**.

⚠️ **Any gradient-backed text in this realm must be pixel-sampled from a glyph-free region of the render.**
And note the three ways that sampling was itself vacuous on the invite email: white glyphs read as
background, then their antialias haloes, then the page wash outside rounded corners. Budget for the probe
being wrong before the page is.

### 🟠 2.6 GUEST REVIEW — the only guest-authored public content

A guest writes content that other people read. Fabrication risk (#37/#39: no invented ratings), plus it is
one of the **DO10-4 star sites**.

---

## 3 · THE INVARIANTS, RANKED FOR THIS REALM

| invariant | how it is enforced here | sharpness |
|---|---|---|
| **#28 — inverted** | the guest IS the subject; the perimeter is guest→(other guests · operator internals · owner money · other tenants) | 🔴 **dominant** |
| **#33 door-code window** | `ArrivalPack` window gate, 6 call sites, `has_code` split from `door_code` | 🔴 **the signature control** |
| **#38/#42 foreign→404** | `ResolvesGuest::guest()` + `$this->guest()->reservations()->findOrFail()` in 10 controllers | 🔴 **the single mutation that matters** |
| **#20 GET-link scope** | ⚠️ **NOT the model.** Only `claim.verify/complete` and `unsubscribe` are `signed` | 🟡 narrow, 2 surfaces |
| **`#[Locked]`** | ⚠️ **N/A — no Livewire in the guest realm at all** | ⚪ not applicable |
| **§N.4k implicit submit** | 31 `<form>`s / 19 views; hot spots Account(8) · Saved(4) · Manage(3) · Wallet(2) | 🟠 the a11y risk lives here |
| **#17 reconciliation** | folio / wallet / the 5 money PDFs | 🟠 present but narrower than the owner realm |
| **#45 no raw PAN** | hosted Checkout on both the storefront and the folio-payment seam | 🟠 |

---

## 4 · 🚩 THE THREE PROTOTYPES WITH NO LIVE ROUTE

Verified by route-list and by grep — not inferred from absence of a URL alone.

| prototype | evidence | reading |
|---|---|---|
| **Guest Help** | no `guest.help` route; `/owner/help` exists, `/guest/help` does not | ⚠️ **Ties directly to `DO-INV-4`'s sibling `DO-INV-3`** — the invite-email gate already found there is **no public help destination and no configured support address**. Same gap, second realm. Likely one fix serving both. |
| **Guest Documents** | per-reservation PDFs exist (5 routes) but no hub page | A *rollup* of documents the guest can already reach one-by-one. Cheapest of the three. |
| **Guest Referral Landing** | `GuestCreditEntry::TYPE_REFERRAL` is a wallet credit **type**; no route, no controller, no view | ⚠️ A referral *landing* implies a referral **programme** (codes, attribution, reward rules). The credit type is a slot, not a feature. **Do not build a landing page for a programme that does not exist** — this is the #39 shape. Surface as a product question. |

**Recommendation:** log all three in `MISSING-BACKEND-BY-PAGE.md` at the gate where each is reached, not
now — the recon's job is to find them, and each needs its own verdict
(`REAL GAP` · `RATIFIED REFUSAL` · `ACCOUNT-GATED`).

---

## 5 · CROSS-REALM / SHARED SURFACES — do not double-walk, do not miss

### 5.1 ⚠️ SIX of the 11 remaining PDF-sweep templates are GUEST-facing

The §N.12 PDF-contrast sweep lists 11 templates plus `PdfService.php:54`'s watermark at 2.70. **Six are in
this realm:** `arrival` · `folio` · `guest-statement` · `guest-vat-invoice` · `guest-credit-note` ·
`guest-refund-receipt`.

**This is the single biggest sequencing decision.** Two options:
- **(a)** Run the PDF sweep as its own gate first (as §N.12 recommends), then walk the guest screens.
- **(b)** Fold each guest PDF into its own page gate (Receipt-screen + Receipt-PDF together, etc.).

⚠️ **(a) is still correct**, for the reason §N.12 gave: `PdfService`'s watermark appears on *every page of
every document* and **no page gate may touch it**, so under (b) it never gets fixed. But (b) has a real
advantage the owner realm proved — a screen and its PDF twin are best diffed together. **Suggested hybrid:
walk the guest screens (which surfaces each PDF's real content), then run ONE PDF gate covering all 11 +
the watermark.** Recorded here so the choice is deliberate rather than drifted-into.

### 5.2 THREE of the 10 remaining DO10-4 star sites are guest

`guest/trips.blade.php` · `guest/review.blade.php` · `guest/partials/trip-card.blade.php`
(+ `emails/review-request.blade.php`). Migrating these to `<x-star-rating>` during the guest walk closes
~40% of DO10-4 as a by-product. **Recommend folding the migration into the Trips and Review gates** rather
than leaving it to a separate sweep.

### 5.3 THE `DO-INV-4` EMAIL-CONTRAST SWEEP IS MOSTLY GUEST EMAILS

The invite-email gate logged the shared `emails/layout.blade.php` footer at **`#A99E95` = 2.24** on the wash,
carrying the *"why you're receiving this"* line across **all 15 emails that extend it**. The majority are
guest-facing: `booking-confirmation` · `cancellation` · `pre-arrival` · `payment-reminder` · `review-request`
· `waitlist-notification` · `guest-password-reset` · `verify-email` · `refund-confirmation` · `winback` ·
`account-deletion` · `operator-guest-message`. **And `docs/design/raw/` contains 8 email prototypes**
(Booking Confirmation · Cancellation · Payment Reminder · Pre-arrival · Refund Confirmation · Review Request
· Waitlist Notification · Winback · Verify Email) that are **not among the 32 guest page prototypes** — they
are a separate deliverable, and the invite-email gate is the template for how to walk one.

### 5.4 `ArrivalPack` is shared with an EMAIL

`PreArrivalMail` reads the same `#33` gate as the two screens and the PDF. A change to the window affects a
**send**, not just a render. Treat with the invite-email discipline: **render, never send**.

---

## 6 · RECOMMENDED WALK ORDER — 12 gates

Sequenced so that shared machinery is verified **before** the surfaces that consume it (the Wave-A lesson
from the owner realm), and so the two most dangerous controls are walked while attention is freshest.

| gate | surfaces | why here | risk |
|---|---|---|---|
| **G1** | **Guest Trips** (+ `trip-card`) | The post-login landing and the realm's shell. Establishes the guest design language, the shell chrome, and the gradient/contrast baseline every later gate reuses. Folds in **DO10-4**. | 🟡 low |
| **G2** | **Guest Reservation** | The hub every per-booking surface hangs off. **First `ResolvesGuest` mutation** — prove foreign→404 here and the pattern is established for G3–G8. | 🟠 med |
| **G3** | 🔴 **Guest Arrival Info** + **Arrival Info PDF** | **#33.** Screen + PDF together, because they must not drift. Verify `ArrivalPackDocument` delegates. Do this early, with full attention. | 🔴 **highest** |
| **G4** | 🔴 **Guest Check-in** | PII write + ID documents + §N.4k. Reads `ArrivalPack`, so it follows G3. | 🔴 high |
| **G5** | **Guest Manage Booking** + **Add-ons** | Both are request-writes on the same booking; 4 forms between them. Natural pair. | 🟠 med |
| **G6** | 🔴 **Guest Payments** + **Folio Receipt** | Real money + the receipt that reports it. #17 + #45. | 🔴 high |
| **G7** | 🔴 **Guest Messages** (list + thread) | The #28 boundary from the guest side. Deserves its own gate. | 🔴 high |
| **G8** | **Guest Review** | Guest-authored public content; #37/#39. Folds in **DO10-4**. | 🟠 med |
| **G9** | **Guest Account** (+ **Email Preferences** folded) | **8 forms** — the §N.4k hot spot — plus password and account-delete. Resolve the Email-Preferences mapping here. | 🟠 med |
| **G10** | **Guest Credits/Wallet** + **Wishlist/Waitlist** | Two prototypes → one `/guest/saved` surface; wallet is append-only money. Both are list+mutation. | 🟠 med |
| **G11** | **Guest Login** + **Signup/Claim** + **Verify Email** (+ reset, no proto) | The whole auth chapter in one gate — shared `guest/auth/layout`, so diffing them together prevents drift. Contains the **one real signed-link surface**. | 🟠 med |
| **G12** | **Guest Browse** + **Villa Detail** + **Checkout** + **Booking/Confirmation** | The public storefront. ⚠️ **Re-verify the 2026-07-25 GATE-4 "at-parity" record — do not inherit it.** Heaviest gradient/brand surfaces → **pixel-sample, do not trust getComputedStyle**. | 🟠 med |

**Then, separately:** the **ONE PDF gate** (all 11 remaining templates + `PdfService`'s 2.70 watermark) and
the **`DO-INV-4` email-contrast sweep**. Both are cross-realm and neither belongs inside a page gate.

**Deferred to their gates, not built:** Guest Help (G-none — ties to `DO-INV-3`), Guest Documents,
Guest Referral Landing. **Recommend a short G13** to give each an explicit verdict rather than letting them
sit unresolved.

---

## 7 · WHAT THIS RECON DID **NOT** ESTABLISH — to verify at the page gate

Honesty about the limits of a map. None of the following was checked, and **an unverified guess is not a
finding**:

- Whether each controller's `$this->guest()->…` scoping is **actually** exercised by a test that fails
  without it (§N.4p). Presence of the pattern ≠ a guard that is proven.
- Whether `ArrivalPackDocument` delegates to `ArrivalPack` or re-derives the window (§N.8f).
- Any contrast or keyboard measurement whatsoever. **Zero pages were rendered for this recon.**
- Whether the 16 GATE-3 register rows are accurate (§0.1) — that is precisely what the walk decides.
- Whether guest free-text stores (check-in notes, message bodies, review text) all reach `anonymize()`.
- Whether the storefront's `{quote}` id in `/book/{property}/confirmation/{quote}` is scoped or guessable.
