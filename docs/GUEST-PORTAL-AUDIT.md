# Guest Portal — exact-match audit (PHASE 1, read-only)

**Scope:** the five decoded guest-portal wireframes — **Booking** (storefront), **Arrival Info** + **Arrival Info PDF**, **Add-ons**, **Account** — matched element-by-element against the REAL backend. This is the read-only PHASE 1: classify every element **REAL-NOW / BUILD / NOT-BUILDABLE**, map the reuse, surface the honesty cruxes + founder decisions, then **PAUSE**.

**Method:** the 5 `__bundler` dc-runtime bundles were decoded (`docs/design/decoded/*.decoded.txt`, JSON.parse the `__bundler/template` → the `<x-dc>` body). Then a 4-agent read-only investigation mapped each page to the certified services, cross-checked by direct reading of `BookingEngineService`, `ArrivalPack`, `ReservationExtras`/`AddOn`, `GuestAccountController`, `FolioService`/`PropertyCharges`, the guest models, and consent/PDF/Stripe seams.

**Headline:** four of the five pages are **rebuild-to-wireframe + gap-fill over MOSTLY-real backends** (not net-new); Add-ons is a **new guest surface wiring existing certified primitives**. The certified engine, folio, ArrivalPack (#33), reviews (#76), consent (#87), wallet/waitlist are all real and reused — **no parallel path is invented**. Three of the task's stated builds cross an honesty or architecture line and need an explicit founder call (below).

---

## The guest chrome (all 5)

Peach→terracotta full-page gradient `linear-gradient(135deg,#F6C89B 0%,#F1B896 32%,#EBD9CF 62%,#D9D6D2 100%)`, two static glow blobs + a **`motion`-gated** `vyDrift`(14s) / `vyShimmer`(9s) (honour `prefers-reduced-motion`), frosted glass cards `rgba(255,252,248,.58–.7)` + `backdrop-filter: blur(22px) saturate(1.15)`, Sora (headings) + Inter (body), terracotta accent `#C2410C`/`#E8703A`, pill radii 30, keyframes `vyFadeUp`/`vyToastIn`. These `--vy-*` tokens already live in `resources/views/components/public/shell.blade.php` (storefront) and `resources/views/components/guest/shell.blade.php` (portal).

---

## 1) BOOKING — the storefront (`/book/{property}` → browse · room · checkout · reserve · confirmation)

**Existing:** `BookingEngineController` (browse/room/checkout/reserve/reservation + search/quote/book JSON) → `BookingEngineService` → certified `AssignmentService` + `AvailabilityService` + `FolioService`. Views `booking/{browse,room,checkout,reservation}.blade.php` in the older `vy-card` design. **Functionally real already:** live availability, real `rate_calendar` prices, real derived ratings/reviews (#76), real no-overbook booking, a real confirmation reference, and a real confirmation email. **This is a design rebuild to the peach-glass wireframe wiring an already-real backend.**

| Element | Class | Wire to / note |
|---|---|---|
| Search (where/dates/guests) | **REAL-NOW** | Same real `check_in/check_out/adults/children` GET → `booking.browse`; frosted popover fields (calendar range, adults/children stepper) |
| "Where" destination = **cross-property** switcher | **NOT-BUILDABLE** | Storefront is **single-property** (`ResolvePublicProperty`); render the current property read-only (documented per-property-vs-multi divergence) |
| Villa/room results (name/rating/reviews/nightly/total/N-left) | **REAL-NOW** | `BookingEngineService::search`; `ReviewRating::forRoomType` (#76, honest "No reviews yet" when none — never 4.8/124); real per-night + total + availability chip |
| Filters — bedrooms / amenities / max-price / sort | **BUILD** | Real query filters over the real offer set (RoomType bedrooms/amenities, derived per-night, ReviewRating sort); honest empty state |
| **List / Map** toggle + map pins | **NOT-BUILDABLE** (map) | No geo coordinates modelled; ship **List-only** (drop the map view + pins) |
| Wishlist heart / "N saved" / notif bell / account menu | **REAL-NOW** *(signed-in only)* | Gate on `Auth::guard('guest')->check()`; anonymous storefront → sign-in affordance, no bell/wishlist (the proto's localStorage wishlist is not a real source) |
| Reserve → **Reserve-&-pay** (grand total **incl. cleaning**) | **BUILD (wiring)** | Routes through certified `AssignmentService` (no-overbook, mutation-proven). **Cleaning is REAL** (a non-optional flat `kind=cleaning` Fee) — `book()` must post fees via `PropertyCharges::postFees` and the shown grand total must include them so **shown total === folio total (#17)**. Never hardcode €45 (render the property's real fee, or omit when none) |
| Payment at "& pay" | **BUILD / decide** | ⚠️ **Founder decision**: keep honest **pay-on-arrival** (current, offline via FolioService) OR **test-mode hosted Stripe** (reuse `createPaymentCheckout` + `recordCheckoutIntent` + the `payment_intent.succeeded` webhook — fulfil-on-webhook, no PAN, `StripeSafetyGuard`). Never a fake/live charge |
| "You're booked! Confirmation VYR-…" | **REAL-NOW** | Real `generateReference()` (proto's VYR-10512 is illustrative); `reservation.confirmed` → `BookingConfirmationMail` really fires |

**Honesty crux:** cleaning fee = **real** (wire it, don't hardcode); reviews = **real derived only**; map/cross-property = **refuse**; storefront chrome = **signed-in-gated**.

---

## 2) ARRIVAL INFO (`/guest/reservation/{reservation}/arrival`) + the printable PDF — ⚠️ the #33 secrets, correctly shown to the guest

**Existing:** `GuestArrivalController::show` resolves the booking **through `$this->guest()->reservations()->findOrFail()`** (owned-only, foreign id = 404) + `ArrivalPack::for(reservation, today)`. `guest/arrival.blade.php` **already renders the real pack honestly** with correct #33 guest-side exposure and honest withheld/empty states. **No PDF exists yet.**

| Element | Class | Wire to / note |
|---|---|---|
| Welcome header + check-in copy | **REAL-NOW** | Reservation + property |
| ⚠️ **Door code** (4 8 2 1) | **REAL-NOW** — the **right side of #33** | `ArrivalPack` returns the assigned unit's real `door_code`, **window-gated** (`LEAD_DAYS=2`, withheld before/after + on cancel). The **owner never** sees this (no owner surface calls ArrivalPack); the **guest correctly does** (their own stay). Never read `Unit.door_code` directly |
| ⚠️ **Wi-Fi** (network + password) | **REAL-NOW** — right side of #33 | Same window-gated ArrivalPack fields |
| Check-in / check-out times · address / directions | **REAL-NOW** | Property times + real address → maps deep-link (shared with PreArrivalMail #83 — no drift) |
| **House guide** (5 accordion sections: Getting in / Heating / Kitchen / Trash / Pool) | **NOT-BUILDABLE** | Fabricated mock content — no structured house-guide backend. Real host text = the single `Unit.arrival_notes` → render as ONE honest "House notes" block |
| **Checkout checklist** (4 steps) | **NOT-BUILDABLE** | Fabricated mock — no checkout-steps model. Refuse (or fold into arrival_notes) |
| **"Marco's local picks"** (3 named venues + distances) | **NOT-BUILDABLE** | Fabricated venues — refuse (#39); keep the honest "neighbourhood guide coming soon" |
| Named host "Marco" · "~10 min" reply SLA · emergency phone | **NOT-BUILDABLE** | No host-person / SLA / property phone modelled — refuse the person/SLA/number; keep a generic operator name |
| Message host | **REAL-NOW** | `guest.messages` / `InboxService` |
| "Save printable guide" → **Arrival Info PDF** | **BUILD** | New route + `GuestArrivalController::pdf()` + `ArrivalPackDocument` + `pdfs/arrival.blade.php`, mirroring `PdfService`/`GuestDocumentController`. **Reuses the SAME `ArrivalPack`** so the PDF is window-gated identically — a pre-window PDF prints the honest "closer to arrival" state, **never** the code (assert this in a test) |

**Honesty crux:** door/wifi/times/address = **real, shown to the guest, owner-refused** (perimeter holds); house-guide/checkout/local-picks/host-person = **refuse** (fabricated); the PDF must **never** be a second ungated read of the secret.

---

## 3) ADD-ONS — "Enhance your stay" (extras that settle to the real folio)

**Existing:** **no `/guest/extras` page** — the only related route is `guest.manage.add-ons` (`GuestManageController::requestAddOns`), a **name-only pending request** (`GuestChangeRequest`, no folio write) against a hardcoded string list. **The real folio-writing path is `FolioService::postAddOn` + the operator `ReservationCreateController::postExtras` pattern.** So this is a **new guest surface wiring existing certified primitives** — not new backend.

**Real catalog (demo):** 4 org+property-scoped `AddOn` rows — Breakfast €18 (taxable, per-guest-night), Late checkout €40 (taxable, flat), Baby cot €15 (non-taxable, per-night), Airport transfer €65 (taxable, per-leg) — **NOT the wireframe's invented 6** (hamper / mid-stay clean / e-bike are fabricated).

| Element | Class | Wire to / note |
|---|---|---|
| "Enhance your stay" hero + KPIs | **REAL-NOW** | Derived counts + cart total |
| Available extras grid | **REAL-NOW** *(real catalog)* | Real `AddOn` rows only (demo: 4). `AddOn` has **no description/image** column → neutral tile + `ReservationExtras::basisLabel`, never invented marketing copy/prices |
| Per-night / per-stay pricing | **REAL-NOW** | `ReservationExtras::basisFor/quantityFor` (per-guest-night / per-night / per-leg / flat) |
| "Prices include VAT" | **BUILD (reconcile)** | `AddOn.price` is stored **VAT-exclusive** (operator posts a separate VAT line). The guest-shown price/total MUST equal the true folio delta = net + computed VAT (reuse `PropertyCharges::postTaxes`/`computeTax`) or the two surfaces disagree (#17). No VAT Tax → show net honestly |
| Cart + "Add to booking" | **BUILD (wiring)** | Posts real folio lines via `FolioService::postAddOn` (recompute → **#17 by construction**, idempotency-guarded). **Nothing charged now** — a line raises the balance, records no payment ("settles to your booking" is literally true) |
| "Free to cancel up to 24h" | **NOT-BUILDABLE** (as a live guarantee) | No per-extra reversal window/state machine — present as **policy copy only**, or wire removal via the request path. Never a fake timer |
| Reservation binding | **BUILD** | Must resolve via `guest()->reservations()->findOrFail` (#28); a per-booking route (`/guest/reservation/{reservation}/extras`) or a standalone defaulting to the active/upcoming booking |

**Honesty crux — the write model (⚠️ founder decision):** **(A)** guest creates a pending `GuestChangeRequest` → operator confirms → certified `postExtras` posts the folio (preserves the established *"a guest never writes the operator's folio, only requests"* boundary), **or (B)** the guest POST directly posts folio lines via `postAddOn`+`postTaxes` (matches the task's #17 NOTE literally). **Both reconcile #17 and reuse the same certified primitives** — this is an architecture call.

---

## 4) ACCOUNT (`/guest/account`) — ⚠️ guest-side delete + card management + honest SMS

**Existing:** `GuestAccountController` — `show`/`updateProfile` (name/phone/nationality/language; email read-only) / `changePassword` / `destroy`. **`destroy()` today is an INSTANT, non-destructive "remove portal access"** (nulls password/verified/remember-token; **keeps the guest row + PII + bookings, cancels nothing**) — a deliberate design (GDPR erase is operator-mediated). The view is a bare 3-card form. **The wireframe's hero + 5-KPI + 3-column bento is unbuilt presentation over MOSTLY-real data.**

| Element | Class | Wire to / note |
|---|---|---|
| Hero: member-since / avatar / email | **REAL-NOW** | `guest.created_at`, `GuestName` |
| KPI — **Credit** | **REAL-NOW** | `GuestWalletService::spendableMinor` (derived, append-only ledger, integer minor, #17). Never the proto's localStorage arithmetic |
| KPI — **Stays** | **REAL-NOW** | `Guest::reservations()->count()` |
| KPI — **Saved cards** | **NOT-BUILDABLE → 0** | No saved-card model (see below) → honest 0 |
| KPI — **Waitlists** / **Language** | **REAL-NOW** | `GuestWaitlistEntry` count; `guests.language` |
| Personal details (edit → Save) | **REAL-NOW** | `updateProfile` (#20; small gap-fill: include `marketing_consent`+`language` on the self-write path) |
| **Payment methods** — list / add (raw-PAN modal) / remove / default | **NOT-BUILDABLE (as drawn) → REFUSE** | No guest saved-card model, no Stripe Customer/SetupIntent; Stripe is **token-only hosted Checkout (SAQ A)**. **Collecting a raw PAN on the server is a PCI violation.** The seeded "Visa ····4242" is fabricated (#39/#28). Ship the wireframe's **own honest empty state** ("No saved cards — add a card at checkout"). ⚠️ Founder decision if a real vault is wanted (below) |
| **Waitlists** (join / leave) | **REAL-NOW** | `GuestWishlistService::waitlist` + `guest.saved.waitlist.*`. **Refuse "#3 in line"** — no queue-position concept; show real dates/expiry |
| Preferences — **Trip offers & news** | **REAL-NOW** | `guests.marketing_consent` — the ONE #87 gate that genuinely stops marketing sends |
| Preferences — **Booking updates by SMS** | **NOT-BUILDABLE → honest-gated** | No SMS provider anywhere. Stored in `preferences` JSON but shown **disabled / "not available yet"** — never a toggle implying a text send (#39) |
| Preferences — **Language** | **REAL-NOW** | `guests.language` (real editable) |
| Credits & wallet / Receipts & documents links | **REAL-NOW** | `guest.wallet`; `guest.payments` + `GuestDocumentController` PDFs |
| Sign out | **REAL-NOW** | `guest.logout` |
| **Delete account** — type DELETE + **14-day undo** + "upcoming reservations cancelled per policy" | **BUILD (consequential) — ⚠️ founder decision** | The real `destroy()` is an instant non-destructive portal-access removal. The **14-day scheduled full-erase + undo + auto-cancel** is unbuilt AND crosses the deliberate *"GDPR erase is operator-mediated"* line — a real **money/legal state change**. See decision D2 |

**Honesty crux:** Credit/Stays/Waitlists/Trip-offers/Language = **real**; **saved cards + raw-PAN = refuse** (PCI/#28/#39); **SMS = honest-gated**; **the 14-day-undo delete is a real consequential build needing explicit approval**.

---

## Consolidated reuse map (anti-drift — one definition per concept)

`BookingEngineService` (search/quote/**book**) → `AssignmentService` (no-overbook) + `AvailabilityService` + `FolioService` · `PropertyCharges::postTaxes/postFees` + `StayEstimator::feeApplies` (the ONE taxes+fees path — real `kind=cleaning` Fee) · `ReviewRating`/`ReviewList` (#76) · `BookingConfirmationMail` via `SendGuestTransactionalEmails` on `reservation.confirmed` · `ArrivalPack::for` (the ONE arrival resolver — page + PreArrivalMail #83 + the new PDF share it, window-gated #33) · `PdfService`/`ArrivalPackDocument`/`pdfs.*` (the certified PDF pattern) · `AddOn` + `ReservationExtras` + `FolioService::postAddOn` (#17) · `GuestWalletService::spendableMinor` · `GuestWishlistService::waitlist` + `guest.saved.waitlist.*` · `GuestMarketing::canReceive` + `guests.marketing_consent` (#87) · `ResolvesGuest` / `guest()->reservations()->findOrFail` (guest isolation #28) · `StripeSafetyGuard` (test-mode only) · the guest/public shells.

**Refused (no real source — never fabricated):** cross-property "Where" + map pins (no geo, single-property) · the structured house-guide / checkout-steps / local picks / named host+SLA+phone (no content model) · the invented 6-extra catalog + marketing copy/photos · saved cards / raw PAN / "····4242" (no vault, PCI) · the SMS send · the waitlist "#N in line".

---

## ✅ Founder decisions (RESOLVED 2026-08-01 — locked before PHASE 2)

- **D1 — Storefront "& pay" rail → ✅ TEST-MODE HOSTED STRIPE.** Reuse the certified `createPaymentCheckout` + `recordCheckoutIntent` + the `payment_intent.succeeded` webhook (the SAME rail `GuestFolioPaymentController` uses) — fulfil-on-webhook, no PAN, `StripeSafetyGuard` refuses a live key outside production. No real charge until go-live.
- **D2 — Account delete → ✅ BUILD THE REAL 14-DAY SCHEDULED ERASE.** `scheduled_deletion_at` column + a queued `Guest::anonymize` job (fires after 14 days) + an undo route (reversible within the window) + a confirmation email + **per-policy** cancellation of upcoming reservations (NOT a destructive cascade of financial records) — all guest-scoped + audited. PII is *scheduled* for removal (never immediate); reservations handled per the cancellation policy.
- **D3 — Card management → ✅ HONEST EMPTY STATE.** Ship the section with the wireframe's own honest empty state ("No saved cards — add a card at checkout"), Saved-cards KPI = 0. NO raw-PAN form (PCI/SAQ A), NO fabricated "····4242". A real vault (Stripe Customer + SetupIntent + Elements) is deferred to the account-gated live-Stripe phase.
- **D4 — Add-ons write model → ✅ OPERATOR-CONFIRMED REQUEST.** Guest POST creates a pending `GuestChangeRequest` (server-authoritative: re-read org+property-scoped `AddOn`s, server-derived quantity/VAT, guest-owned reservation only) → the operator confirms → the certified `postExtras`/`postAddOn` posts the folio (reconciling #17 on confirm). Preserves the established *"a guest never writes the operator's folio, only requests"* boundary. The extra is live once the host confirms — the UI says so honestly.

---

## Proposed gated build (PHASE 2, after approval)

- **⛔ GATE 1 — Booking storefront:** rebuild `booking/browse` (+ room/checkout/reservation) to the peach-glass wireframe; real filters (bedrooms/amenities/max-price/sort); signed-in-gated chrome; wire the **real cleaning fee** into `book()` + the grand total (#17); List-only (drop map); the payment rail per D1; real confirmation ref + email. Tests: certified-booking-via-storefront, folio reconciles (#17), fee posted, honest empty/anonymous states. Browser-verify. Commit.
- **⛔ GATE 2 — Arrival Info + PDF + Add-ons:** re-skin arrival to warm-glass (real door/wifi/times/address shown to the guest, honest refusals for guide/picks/host); **build the Arrival PDF** (shared window-gated `ArrivalPack`, pre-window omits the code — tested); build the Add-ons page wiring the **real `AddOn` catalog** + `postAddOn`/VAT (per D4), reconciling #17, nothing charged now. Tests: #33 shown-to-guest/owner-refused, PDF pre-window omits code, extras reconcile. Browser-verify. Commit.
- **⛔ GATE 3 — Account + consolidate:** rebuild to hero+5-KPI+3-col-bento over real data; real profile edit; **delete per D2**; **cards per D3** (honest empty state or the real test-mode vault); waitlists real (no "#N"); Trip-offers = real `marketing_consent` (#87); SMS honest-gated; language real. Consolidate docs (`DECISIONS.md` dated · `DIVERGENCE-REGISTER.md` the 5 pages · `PROJECT-STATE.md` + invariant list). Tests + browser-verify + push.

**Invariants preserved throughout:** certified engine reused (no-overbook mutation-proven) · #17 folio + extras reconcile to the cent · payment test-mode (no real/fake charge, no PAN) · #33 secrets shown to the guest / owner still refused (mutation-proven) · #28 guest isolation + name-only cross-actor · #87 consent gates marketing · #39 no fabrication (no fake availability/rate/booking/secret/extra/card/SMS/undo-timer) · disturb nothing (the green suite is the proof).
