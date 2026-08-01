# Guest Portal — Browse · Check-in · Checkout — exact-match audit (PHASE 1, read-only)

> **✅ BUILT & SHIPPED (2026-08-01, invariants #116–117; `DECISIONS.md`).** All three pages rebuilt exact-match on REAL data: **Browse** = a new multi-property per-org landing `/stay/{organization}` (`c13b1d5`, D1) · **Checkout** restyle + real gated wallet toggle (`4f833f6`, D3/D4) · **Check-in** 4-step wizard + REAL encrypted ID vault + 30-day purge (`2ec43ff`, D2). Every refusal below held (no fabricated towns/counts/"369 stays"; no raw PAN / on-page Apple Pay / "charged at check-in"; ID captured-not-verified, register-only, owner-never #28; door code by email, SMS deferred). Suite green (`BookingImportTest` flake only); PHPStan L5 (0); Pint clean; all surfaces browser-verified.


**Scope:** three decoded guest wireframes — **Browse** (storefront landing), **Check-in** (online, ⚠️ sensitive ID), **Checkout** (payment step) — matched element-by-element against the REAL backend. Classify each element **REAL-NOW / BUILD / NOT-BUILDABLE**, map the reuse, surface the honesty cruxes + founder decisions, then **PAUSE**.

**Method:** the 3 `__bundler` bundles decoded (`docs/design/decoded/*.decoded.txt`), then a 3-agent read-only investigation, cross-checked by direct reading of `BookingEngineController`/`Service`, `StayEstimator`/`FolioTotals`, `GuestCheckinController`, `FrontDeskService`, `GuestDocument`/`Document`/`Media` + the private disk, `GuestWalletService` + the operator credit-apply, `ReviewRating`, `ArrivalPack`, `CancellationPolicy`, `PropertyPolicy`, the demo seed.

**Headline:** **Checkout** is a restyle of the ALREADY-built `booking.checkout` (reconciling #17, certified booking, test-mode Stripe — all real). **Browse** enriches the ALREADY-built single-property storefront pre-search state (the wireframe's multi-town landing has no real source — single-property scope). **Check-in** is a real gap-fill: a wizard over the existing `guest_checked_in_at` intent, plus the **first real writer of the sensitive `GuestDocument` ID store** (encryption + a 30-day retention job are net-new). Three of the wireframe's claims cross an honesty/security/legal line and need an explicit founder call.

---

## 1) BROWSE — the storefront landing

**Existing:** route `/` is the generic Laravel `welcome` splash, NOT a storefront. The real storefront is **single-property** `booking.browse` = `/book/{property}` (`ResolvePublicProperty`, every query property-scoped). There is **no multi-property public landing route**. So Browse = **enrich the single-property pre-search state** (`booking/browse.blade.php`) — a rebuild+gapfill, except the multi-town aggregation which is architecturally absent.

| Element | Class | Wire / note |
|---|---|---|
| Brand mark + name, "Stays"/"Help" nav | **REAL-NOW** | The public shell + the real property name (never a fabricated "Seaside Villas") |
| Signed-in chrome (wishlist "N saved" · bell · account) | **REAL-NOW** *(gated)* | `Auth::guard('guest')` — real when signed in, sign-in affordance when anonymous |
| Hero heading + subcopy | **REAL-NOW** | Static; keep locale-neutral (demo `address` JSON is empty — don't assert "Algarve/Faro") |
| Hero rating badge "4.8 across 369 stays · Faro·Lagos·Tavira" | **BUILD (partial)** | `ReviewRating::forProperty` — **4.8 = real** average (#76); **"369 stays" FABRICATED** → real published-review count or omit; **town list FABRICATED** → drop; no reviews → hide the badge (no "0.0") |
| Search (Where / When / Guests) | **BUILD** | Native GET form → the real `booking.browse` search (no Alpine popovers); **"Where" town picker = NOT-BUILDABLE** (single-property → drop / collapse to the property) |
| **Where-to-stay — towns · per-town count · from-price** | **NOT-BUILDABLE** | Single-property, no town data, no multi-property route → **refuse** (a fabricated town/count/from-price) OR a founder decision to build a new multi-property landing |
| Featured-stays (villa cards) | **REAL-NOW** | Real `RoomType` cards + real from-price (`BookingEngineService::cheapestRatePlan`) + real per-room `ReviewRating` |
| Browse-by-vibe (categories) | **BUILD / partial** | Map to REAL filters (amenities / room kind) → the search; invented vibes with no filter → drop |
| Why-book-direct (perks) | **REAL-NOW** | Static content (honest: "no booking fees" is true for direct) |
| Recent-guests (reviews) | **REAL-NOW** | `ReviewList::forRoomType`/property — real quotes + first-name byline (#28); "No reviews yet" when none — never fabricated |

**Crux:** the **multi-town landing** is the divergence — single-property scope, no town data. Refuse the fabricated towns/counts; the rating average is real, the "369 stays" is not.

---

## 2) CHECK-IN — online check-in (⚠️ sensitive ID)

**Existing:** `GuestCheckinController` (route `guest.checkin`) stamps `reservations.guest_checked_in_at` — a guest pre-check-in **intent**, deliberately SEPARATE from the operator's authoritative `FrontDeskService::checkIn()` (`status='checked_in'`). Its docblock already says ID is honestly deferred ("no passport image captured/stored/verified"). **Rebuild to the 4-step wizard (Details → ID → Arrival → Done) + build the real ID capture.**

| Element | Class | Wire / note |
|---|---|---|
| Countdown to arrival · stat tiles | **REAL-NOW** | Server-computed "in N days" from real `check_in` (static, no live JS needed) |
| Step 1 — Details (name/email/phone prefilled, lead-guest-only) | **REAL-NOW** | Prefill from the guest's own encrypted email/phone + `GuestName`; the "only the lead guest checks in" note is real; #20 audited if editable |
| Step 3 — Arrival window (slots) | **BUILD** | No column today → add a real `arrival_window` on the reservation (operator/front-desk-visible); "housekeeping plans around this" = the slot is visible (a full auto-`HousekeepingTask` schedule is honestly deferred) |
| The check-in submit | **REAL-NOW** | Stamps `guest_checked_in_at` (intent, idempotent) — the SAME the Front Desk sees; NEVER flips the authoritative `status` |
| ⚠️ **Step 2 — ID photo capture** | **BUILD** | The first real writer of `GuestDocument` (type passport/id_card) → `Document` → `Media` on the **private disk** (`storeAs`, no public); guest-scoped, `verification_status='pending'`, register-only, **owner NEVER sees it (#28 — confirmed no surface exposes it)** |
| ⚠️ "Encrypted" claim | **BUILD or RELABEL** | The private disk is access-gated but NOT encrypted-at-rest. Either build a real **Crypt-wrapped encrypted store** (so "Encrypted" is true) or **relabel** to "securely stored · access-controlled" — never claim encryption we don't do. **Founder decision.** |
| ⚠️ "deleted 30 days after checkout" | **BUILD** | A real scheduled command (`guest-documents:purge-expired`) hard-deletes `GuestDocument`+`Document`+`Media` 30 days after `check_out` (reusing the `anonymize()` purge) — a genuine data-minimization job, not a claim |
| ⚠️ "ID verified" / "details matched" | **NOT-BUILDABLE → REFUSE** | No verification engine — `verification_status` stays `pending` (operator/KYC sets it later). Copy = "ID captured for the guest register." A fake "verified" is #39 **and legally consequential** |
| ⚠️ "Run the full ID check" (third-party scan) | **NOT-BUILDABLE** | No KYC provider integrated → capture-only, honestly; never a fake scan/verified flow |
| ⚠️ "Door code by SMS" (hero, phone label, Done, sidebar) | **NOT-BUILDABLE → RELABEL** | No SMS provider anywhere → **relabel every "by SMS" → "by email"** (the real Phase-3 pre-arrival mail + the in-window `ArrivalPack` #33); capture the phone but never imply a text |

**Crux:** the ID is real (encrypted-or-relabelled, register-only, 30-day-deleted, owner-never #28), captured-not-verified (no fake "verified"), and delivered **by email** (SMS honest-deferred).

---

## 3) CHECKOUT — the booking payment step

**Existing:** a **restyle of the ALREADY-built `booking.checkout`** (`/book/{property}/checkout` → `BookingEngineController::checkout/reserve`), NOT a new `/checkout`. Already real + reconciling: the room×nights + cleaning + city-tax breakdown (`StayEstimator`, #17 to the cent), the certified `reserve`→`book` (`AssignmentService` no-overbook, honest sold-out), the test-mode hosted Stripe rail, the confirmation page + email.

| Element | Class | Wire / note |
|---|---|---|
| Your details (name/email/phone) | **REAL-NOW** | The existing checkout form |
| Price breakdown (rate×nights · cleaning · city tax · **Total**) | **REAL-NOW** | `StayEstimator` → the same `FolioService::computeTax/computeFee`/`FolioTotals` the folio uses (#17) — already built |
| ⚠️ Inline card fields (raw PAN) | **NOT-BUILDABLE → REFUSE** | SAQ A / no raw PAN (#41/#45, `StripeSafetyGuard`) → the certified **hosted Stripe Checkout** redirect (test-mode, already built) |
| ⚠️ On-page Apple Pay button | **NOT-BUILDABLE → REFUSE** | Wallets render on the **hosted Checkout** page; a single "Reserve & pay" → redirect, never a wallet token on our surface |
| ⚠️ "won't be charged until check-in" | **REFUSE the claim** | No authorize-now/capture-at-check-in rail; in test-mode nothing is charged → the honest test-mode / pay-on-arrival copy |
| ⚠️ **Apply wallet credit** | **BUILD** | Real deduction via `GuestWalletService` + a folio credit line (the operator `applyCreditIfRequested` pattern — capped at owed, reconciling #17); shown ONLY for a **signed-in guest with a real balance** (anonymous/€0 → hidden) |
| "Free cancellation until {date}" | **REAL-NOW** | Reuse `CancellationPolicy::freeUntilLabel` (I built it) — real free-cancel date, honest fallback when no policy |
| Confirm & pay → "You're all booked! VYR-…" | **REAL-NOW** | Certified `book` + real `generateReference()` + the confirmation email; the success modal → a server redirect to `booking.reservation` (no Alpine) |

**Crux:** the breakdown reconciles (#17), the charge is test-mode hosted Stripe (no raw PAN, no fake "charged at check-in"), and the wallet credit is a real capped deduction — shown only when a signed-in guest has a balance.

---

## Consolidated reuse map (anti-drift)

`BookingEngineController`/`BookingEngineService` → `AssignmentService` (no-overbook) + `FolioService` · `StayEstimator`/`FolioTotals`/`PropertyCharges` (#17) · the test-mode hosted Stripe rail (`createPaymentCheckout` + `recordCheckoutIntent` + webhook) · `ReviewRating::forProperty`/`ReviewList` (#76) · `GuestWalletService` + the operator `applyCreditIfRequested` (real capped credit) · `CancellationPolicy::freeUntilLabel` (free-cancel) · `GuestCheckinController` + `guest_checked_in_at` (the check-in intent, separate from `FrontDeskService`) · `GuestDocument`/`Document`/`Media` + the private disk + the `storeAs`→`Media::create` upload pattern (ID) · `ArrivalPack::for` (#33 door code, by email) · `Guest::anonymize()` purge (reused by the retention job) · the public/guest shells.

**Refused (no real source — never fabricated):** the multi-town landing (towns/counts/from-price — single-property) · "369 stays" · inline raw PAN + on-page Apple Pay (SAQ A) · "won't be charged until check-in" · "ID verified"/third-party scan (no KYC) · door-code "by SMS" (no provider).

---

## ✅ Founder decisions (RESOLVED 2026-08-01 — locked before PHASE 2)

- **D1 — Browse landing → ✅ BUILD A MULTI-PROPERTY PER-ORG LANDING.** A new public surface aggregating the org's REAL properties by town + count + from-price (real `RoomType`/rate + `ReviewRating`). Requires populating the demo properties' `address`/town + a new org-resolved public route. No fabricated towns/counts; "369 stays" → real review count.
- **D2 — ID storage → ✅ BUILD A REAL ENCRYPTED STORE.** Crypt-wrapped ID bytes on the private disk (so "Encrypted" is literally true), register-only, `verification_status='pending'` (never a fake "verified"), owner-never (#28), + a real `guest-documents:purge-expired` command hard-deleting `GuestDocument`+`Document`+`Media` 30 days after checkout.
- **D3 — Checkout wallet credit → ✅ YES, GATED.** The real capped wallet-credit deduction (reconciling #17) shown ONLY for a signed-in guest with a real spendable balance; anonymous/€0 → hidden.
- **D4 — Payment presentation (settled).** Inline PAN + on-page Apple Pay refused (SAQ A); the certified test-mode hosted Stripe redirect (wallets appear there); "won't be charged until check-in" → honest test-mode copy.

---

## Proposed gated build (PHASE 2, after approval)

- **⛔ GATE 1 — Browse (landing) + Checkout (restyle).** Browse: enrich `booking/browse` pre-search (rich hero + real rating badge [real avg, no "369"] + featured real cards + browse-by-vibe→real filters + why-direct + real recent reviews; multi-town dropped per D1). Checkout: restyle `booking/checkout` to the wireframe (2-col, summary), keep the reconciling breakdown (#17) + hosted Stripe + confirmation; add the real wallet-credit apply (D3); "free cancellation until" via `CancellationPolicy`; success → redirect. Tests: checkout reconciles + test-mode + wallet-credit deducts+reconciles; Browse renders real units/reviews. Browser-verify. Commit.
- **⛔ GATE 2 — Check-in (wizard + sensitive ID) + consolidate.** The 4-step server-rendered wizard; Details prefilled; Arrival window stored (new column, operator-visible); ⚠️ the real ID capture (`GuestDocument`+`Document`+`Media`, private/encrypted per D2, register-only, `verification_status='pending'`, owner-never #28) + the `guest-documents:purge-expired` 30-day retention command; "by SMS"→"by email"; door code stays #33/email. Tests: check-in stamps the intent (not the authoritative status); the ID writes a guest-scoped GuestDocument the owner can't see (#28); the retention command purges after 30d; no fake "verified"/SMS. Consolidate docs (`AUDIT` gaps→resolved · `DECISIONS` dated · `DIVERGENCE-REGISTER` · `PROJECT-STATE` · invariants). Browser-verify. Commit + **push all**.

**Invariants preserved:** certified engine reused (no-overbook mutation-proven) · #17 folio + wallet-credit reconcile · payment test-mode (no real charge, no raw PAN) · #33 door code guest-only, by email · #28 the owner never sees guest ID · #76 reviews real · #39 no fabrication (no fake town/count/rate/review/charge/ID-verified/SMS) · disturb nothing (the green suite is the proof).
