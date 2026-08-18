# Vyrobel — GUEST SURFACE · DEEP PARITY AUDIT · COMPLETE FINDINGS REPORT

**Date:** 2026-08-18 · **Branch:** `audit/guest-deep-parity` · **Mode:** READ-ONLY · REPORT-ONLY · money-as-seeded
**Evidence log:** `docs/audits/guest-portal-DEEP-parity-2026-08-18.md` (1,264 lines, chronological)
**This file:** the consolidated deliverable — organised by topic, superseded material resolved.

> **VERDICT — the guest surface passes.** 30/30 owed wireframes browser-diffed · 34/34 GET routes accounted ·
> 29 write routes surfaced, **0 exercised** · **0 backend gaps** · **0 fabrications** · the anonymous surface
> leaks nothing · guest isolation holds on pages **and financial documents** · every money figure reconciles
> to the cent. **5 findings, none critical, none a defect in the money path.**
>
> ⚠️ **THE ONE THING NOT VERIFIED:** the **reserve → folio → charge chain**. `book()` is
> **UNVERIFIED-WITHOUT-MUTATION**. Deferred by founder ruling to a dedicated money-path proof. Stated, not implied.

---

## 1 · HARNESS — both instruments proven before any diff

| Instrument | Proof |
|---|---|
| Playwright ↔ live app | `/book/seaside-demo` → **200**, title *"Book your stay · Demo Seaside Property"*, real room types |
| Playwright ↔ wireframes | `:8099` serving `docs/design/raw/`; `Guest Browse.html` → **1,931 chars** of real copy |

**⚠️ GUEST ENTRY MODEL (discovered, not assumed):** a **real email/password login on a separate `guest` guard**
— *not* a token-only model. Account creation is **booking-tied claim**: no open self-signup; the claim link is
a native **`URL::temporarySignedRoute`** (HMAC-signed, 1-hour expiry), keyed on a **blind index**
(`Guest::emailHash()`), with **neutral enumeration** on every response.

- Demo guest: `demo.traveller@vyrobel-demo.test` / `password`
- "The guest's own booking": **`R-E2AZQ1NX`** (`01m0a26wh3z7s3m1e5y14gzxp1`) — €369.00, settled
- Second booking with a real balance: **`R-ZZ4HWTRO`** — €184.50 outstanding

**MONEY-AS-SEEDED BASELINE** (sums and counts, re-derived — never a persisted-DB hash, which is
date/ULID-nondeterministic):

| rate rows | minor total | overrides | reservations | payments | seasons | folio_items | quotes | guests | anonymized |
|---|---|---|---|---|---|---|---|---|---|
| **929** | **10,912,000** | **0** | **41** | **16** | **0** | **40** | **0** | **10** | **0** |

✅ **Byte-identical at close.** A read-only audit changed no money.

---

## 2 · COVERAGE — derived per wireframe and per route, both directions

### 2.1 Wireframes → routes

| | |
|---|---|
| Guest wireframes in `docs/design/raw/` | **32** |
| ⛔ Adjudicated, no route (**not gaps**) | **2** — *Guest ID Verification* (account-gated KYC) · *Guest Referral Landing* (DON'T-BUILD-as-drawn, 5 absent subsystems, #39) |
| **Owed a diff** | **30** |
| ✅ **Covered** | **30** — 23 pages browser-diffed + 6 money artifacts + 1 searched state |
| ⚠️ Open | **0** · Unaccounted | **0** |

### 2.2 Routes → wireframes — **63 guest-facing routes (34 GET · 29 WRITE)**

**34/34 GET accounted for:** 30 diffed; **4 non-diffable, each with a recorded reason and no invented verdict**:

| Route | Why no diff is owed |
|---|---|
| `/book/{property}/search` | a **JSON API**, not a page |
| `/guest/home` | `redirect()->route('guest.trips')` — an **alias** |
| `/book/{property}/confirmation/{quote}` | needs a real quote id — **structurally unverifiable read-only** |
| `/book/{property}/reservation/{quote}` | same |

### 2.3 The pure-marketing question — asked and answered

**No pure-marketing public area exists.** Non-guest public GET routes are the root, `/login`,
`/forgot-password`, `/invite/{token}`, `/signup` and Horizon (ops). There is **no `/about`, `/pricing`,
`/legal`, `/terms`** — the "public" shell **is** the storefront. Operator auth + the signup wizard belong to
the design pass's GATE 4 and are **out of guest scope** — recorded, not silently skipped.

---

## 3 · ⚠️ THE GUEST PERIMETER — the four checks owner and operator did not have

### 3.1 The anonymous surface leaks nothing — **probed positive-first**

⚠️ **This check failed once and had to be re-run.** Opened bare, `/book/{property}` renders **323 chars** of a
date-gated empty state (*"Pick dates above to see availability & prices"*, **zero prices**). A leak probe
returning all-null against that **proves nothing**. Re-run **with dates**, so the page is capable of a positive:

| State | Chars | Real data |
|---|---|---|
| dateless | 323 | ⚠️ none — `PICK DATES`, no prices |
| **priced** (`?check_in=2026-09-20&check_out=2026-09-23&adults=2`) | **596** | ✅ €85.00/night · €373.65 total · €180.00/night · €724.20 total · `★5 · 1 review` / `★4.5 · 6 reviews` · **`1 left` / `2 left`** |

**Only against the priced state is the result evidence — and it is clean:**

| Leak class | Result |
|---|---|
| Guest PII (emails · phones · other guests' names) | ✅ **none** |
| Owner financials (net · commission · statement · payout · IBAN) | ✅ **none** |
| Operator internals (margin · housekeeping · staff · role · organization) | ✅ **none** |

**And the authed surface is genuinely closed to anonymous callers** — `/guest/home` and
`/guest/reservation/{own}` both render the **login page**, not content.

### 3.2 Guest isolation — **positive before negative**

| Probe | Result |
|---|---|
| ✅ own reservation `R-E2AZQ1NX` | **200**, real content, €369.00 |
| ✅ own receipt | **200**, Room stay €300.00 · VAT €69.00 · €369.00 |
| ✅ own message thread | **200**, real 2-message conversation |
| 🔒 another guest's reservation | **404** |
| 🔒 another guest's receipt | **404** |
| 🔒 another guest's thread | **404** |
| 🔒 another guest's `…/pay` | **405** (POST-only route) |

**404, not 403** — the resource does not resolve rather than announcing it exists. Same shape the owner audit
proved for owner scope.

### 3.3 ⚠️ Isolation proven on the FINANCIAL DOCUMENTS, not just the pages

Read through the request context (a `page.goto` aborts on a download).

| Artifact | Guest's OWN | Another guest's |
|---|---|---|
| `receipt.pdf` | ✅ **200 · real `%PDF`** · 879,525 B | 🔒 **404** |
| `invoice.pdf` | ✅ **200 · real `%PDF`** · 879,559 B | 🔒 **404** |
| `statement.pdf` | ✅ **200 · real `%PDF`** · 886,158 B | 🔒 **404** |
| `arrival/pdf` | ✅ **200 · real `%PDF`** · 880,932 B | 🔒 **404** |
| `credit-note.pdf` | **404** ⚠️ correct — see below | 🔒 404 |
| `refund.pdf` | **404** ⚠️ correct — see below | 🔒 404 |

⚠️ **The two 404s on the guest's OWN booking are CORRECT, and I checked before concluding.** That reservation
has **0 refunds** (queried first: `items=2 payments=1 refunds=0`). A credit note or refund receipt for a
never-refunded booking would be a **fabricated document**. **DATA-DEPENDENT, not a gap.**

**⚠️ This is the stronger isolation proof** — a guest cannot pull another guest's *financial documents*, which
is where real exposure would sit. All six 404; the positive is established by four real PDFs on their own booking.

### 3.4 #33 — arrival secrets, verified in the HARDER (pre-window) state

The probed reservation arrives **18 days out**, so the door code must be withheld.

> GETTING IN — *Your access details appear here closer to arrival. Your door code and WiFi unlock on Thu, 3 Sep — for your security.*

| Check | Result |
|---|---|
| Door code rendered | ❌ **none** |
| WiFi password rendered | ❌ **none** |
| Explains rather than silently omits | ✅ names the **exact unlock date** and the reason |

✅ **#33 holds, articulately.**

---

## 4 · ⚠️ THE PRICING MODEL — what it actually is

### 4.1 PREMISE CORRECTION — §G.1 does not govern guest pricing

The brief states the headline check as *"§G.1 flat/seasonal prices are VAT-INCLUSIVE FINAL prices."*
**Read at source, §G.1 is something else.** `PROJECT-STATE.md:761` — **"G.1 — THE BILLING / PRICING MODEL"** —
is the **SaaS subscription** model: operator plans, `flat` vs per-unit **plan shapes**, the super-admin plan
panel, VAT numbers reaching Stripe for reverse charge. Its own words: *"**Stripe Tax computes the customer's own
tax**"* — tax **computed and added**, the opposite of a VAT-inclusive rule. `flat` there is a **plan shape**,
not a room rate.

**A §G.1 violation cannot be filed against the storefront — the section does not govern it.** The storefront's
honesty invariant is **#17: the grand total reconciles to the folio** (`DIVERGENCE-REGISTER.md:14`).

⚠️ **Filing the brief's check as written would have produced a false headline finding against a correct build.**

### 4.2 The real model, measured and reconciling

Storefront card, Double Room, 20→23 Sep (3 nights): **`€85.00 / night · €373.65 total · incl. €45.00 cleaning`**

| Component | Double Room | One-Bedroom Apt |
|---|---|---|
| Accommodation (3 nights) | €255.00 | €540.00 |
| VAT 23% | €58.65 | €124.20 |
| Cleaning fee | €45.00 | €45.00 |
| City tax (€2.50 × 2 × 3) | €15.00 | €15.00 |
| **Displayed total** | **€373.65** ✅ | **€724.20** ✅ |

Derived from the property's **real** `taxes`/`fees` rows. Both reconcile **to the cent**.

**Rates are VAT-EXCLUSIVE and VAT is itemised** — confirmed on the seeded folio *and* on what the guest sees:

```
R-E2AZQ1NX   Room stay €300.00 · VAT €69.00 · Σ €369.00 = folios.total_minor 36900 ✓
reservation_units.price_minor €300.00 = the "Room stay" folio item ✓
/guest/reservation/{r}/receipt renders exactly: "Room stay €300.00 · VAT €69.00 · €369.00"
```

**Display === charge. The guest is shown the tax rather than surprised by it.**

---

## 5 · FINDINGS (5) — complete and classified

| # | Class | Finding | Status |
|---|---|---|---|
| **F1** | ⚠️ **premise correction** | **§G.1 does not govern guest pricing** (§4.1). The storefront rule is **#17**, and it holds to the cent. Filing the brief's check as written would have produced a false headline finding | recorded |
| **F2** | divergence (minor, honesty-adjacent) | The card's **"incl. €45.00 cleaning"** under-states the total's composition — **€58.65 VAT and €15.00 city tax** are also included. `€85/night × 3 = €255`, but the total is €373.65. **The final total IS shown pre-reserve**, so this is a **labelling** divergence, not a hidden charge | **founder call** |
| **F3** | ⚠️ divergence (cross-page) | **TWO PRICING CONVENTIONS ON ONE JOURNEY** — room rates are **VAT-EXCLUSIVE** (`€85.00/night`, checkout adds `VAT · 23% · added €58.65`); add-ons are **VAT-INCLUSIVE** per the real `taxable` flag (`€65.00 each way` → **€79.95**, caption *"Prices include VAT"*). Each surface is individually correct, labelled and reconciling — but a guest reads the two figures on **different bases** with no cue | **founder call** |
| **F4** | honest limit | **`booking_quotes` has 0 rows** — the quote link of the money chain is unverifiable on seeded data (the seeder books via the **operator** path, `AssignmentService`) | inherent |
| **F5** | low, presentation | **The 403 body is OPERATOR-framed in every realm** — *"your role… ask an administrator on your team"* — shown to guests and owners, who have neither. ⚠️ **The realm mechanism already exists one file over**: `errors/layout.blade.php:18-28` branches four realms for the back-link (hence the correct *"Back to your trips"*); `errors/403.blade.php:6` hardcodes one message with **no branch**. **Swept all six error views — 403 is the ONLY operator-framed body**; the other five are neutral. Reached on two guest routes | **not fixed — read-only pass** |

**⚠️ F3 is this audit's genuinely new contribution** — a cross-page pass surfaces what a page-by-page gate
structurally cannot. **Deliberately not "fixed"**: which convention should win is a product/tax-presentation
decision (many jurisdictions mandate tax-inclusive consumer display), and the register shows both were
arrived at deliberately.

### 5.1 BACKEND GAPS: **0**

Every route resolves, every page is backed by real data, no stub, no dead control, no fabricated figure.

---

## 6 · RATIFIED REFUSALS CONFIRMED — **not gaps, and must NOT be "fixed"**

| Refused in the build | Why it is correct |
|---|---|
| `····4242` / card-on-file / saved cards / vault | **SAQ A** — no raw PAN, no card vault (#28, `DIVERGENCE-REGISTER.md:14`) |
| Multi-property browse (Faro/Lagos/Tavira, "3 villas", "from €110", featured stays, category tiles) | **RATIFIED KIND-3** — the storefront is single-property |
| *"4.8 across 369 stays"*, *"124 reviews"*, *"Show all 26 amenities"* | the prototype's aggregates are **invented** (#39); live shows **real** counts (★5 · 1 review) |
| *"€25 welcome credit"* at claim | `claim()` grants **no credit** — inventing one would write **fabricated money into a real ledger** |
| *"Member rates"* / open self-signup | **formally recorded in the source**: `GuestAccountService.php:18-20` — *"BOOKING-TIED… a conscious divergence from the design's 'Join for member rates' framing"* |
| *"Faster checkout with your saved details"* | follows from the card-vault refusal |
| Credit-note / refund PDFs on a never-refunded booking | **DATA-DEPENDENT** — would be fabricated documents |

⚠️ **Over-crediting is as wrong as under-crediting.** "All the claim-page benefits are refused" would itself be
wrong: `GuestCreditEntry::TYPE_LOYALTY` **is** a real credit type, the wallet renders *"Loyalty reward"*, and a
real `WELCOME10` promo (**€10**) is seeded. What does not exist is the **automatic grant at claim time**.

---

## 7 · MISSING-CANDIDATES — surfaced, **deliberately NOT filed**

Each needs its source read before it becomes a finding (*a DOM absence is not a capability absence* — the owner
audit caught this three times, and this audit caught it live on the storefront filters).

| Candidate | Page |
|---|---|
| `All years` filter · `Documents` control | `/guest/trips` |
| `Directions` | `/guest/reservation/{r}` |
| `Share` · `Show all photos` · `Report this listing` | room detail |
| `List` / `Map` toggle | storefront (no geo data modelled) |
| Per-payment breakdown rows | `…/receipt` — ⚠️ **confirmed a real divergence**: the view `@forelse`es **charges**; `Total paid` is a **scalar**, no payment loop. Part of what the wireframe shows there (`····4242`) is itself a ratified refusal |
| Letterhead · `RECEIPT NO.` · `Email me a copy` | `…/receipt` (present on the **PDF**) |

⚠️ **The storefront filters were nearly filed and would have been phantom.** The dateless state shows no sort
and no filters; the **searched** state renders the whole of `Guest Booking.html`'s apparatus — **Recommended ·
Top rated · Price ↑ ↓ · BEDROOMS · AMENITIES (8 real) · MAX PRICE**. They are **date-gated, not missing.**

---

## 8 · THE WRITE SURFACE — 29 routes, **0 exercised**, mapped per page

| Page | Writes it owns | Exercised |
|---|---|---|
| `/book/{property}` | `booking.quote` · `booking.book` ⚠️ **a JSON API no view posts to** | **0** |
| `…/checkout` | **`booking.reserve`** — the storefront's ONE POST | **0** ⚠️ UNVERIFIED-WITHOUT-MUTATION |
| `…/manage` | `manage.cancel` · `manage.date-change` · `manage.add-ons` | **0** |
| `…/check-in` | `checkin.submit` | **0** |
| `…/review` | `review.submit` | **0** |
| `/guest/payments` | **`payments.pay`** ⚠️ a real charge path | **0** |
| `/guest/wallet` | `wallet.apply` · `wallet.redeem` ⚠️ real money | **0** |
| `/guest/saved` | `wishlist.add/remove` · `waitlist.join/leave/reconfirm` | **0** |
| `/guest/messages` · `{thread}` | `messages.start` · `messages.send` | **0** |
| `/guest/account` | `account.update` · `account.password` · `account.preferences` · `account.delete` · `account.delete.undo` | **0** |
| `/guest/login` · `/guest/claim` | `login` · `claim.store` | **0** |
| `…/claim/verify/{guest}/{hash}` | `claim.complete` ⚠️ the claim write | **0** |
| `/guest/forgot-password` | `password.email` | **0** |

**2 writes own no page, both informative:** `POST /guest/logout` is a **shell control**; **`POST /guest/reset-password`
posts to a different URI than the page that renders it** — no `{token}` in the URI, so the token travels in the
**form body**, which independently corroborates why the GET renders for any token and validation is on the POST.

---

## 9 · ⚠️ WHAT THIS PASS DOES **NOT** VERIFY — stated, never implied-done

**Six mutation-gated paths. They are ONE pass, not six.**

1. **`book()` / the reserve → folio → charge chain** — rendered up to the POST, never submitted. **DEFERRED.**
2. **`booking_quotes`** — 0 rows; the quote link unverifiable on seeded data.
3. **`/book/{property}/confirmation/{quote}` · `…/reservation/{quote}`** — need a real quote id.
4. **`POST /guest/reset-password`** — the token gate lives on the POST.
5. **`POST /guest/claim/verify/{guest}/{hash}`** — the GET is proven; the **write** is not.
6. **`POST /guest/payments/{r}/pay`** — a real charge path.

### ⚠️ 9.1 The recommended proof is the CANON'S, not one I invented

An earlier version of this report proposed staging the proof as `quote` → `book` → `reserve`. **That was
retracted.** `book()` requires a **`payment_token` the storefront never sends**, so driving it would prove a
**different path than the one guests use** — and `DECISIONS.md:12602` records that this exact mistake is why a
previous attempt could not book at all.

⛔ **The correct recipe already exists — `DECISIONS.md:12147`:** *"get ONE type-only storefront booking to
produce a folio — no overrides, no unit, just the existing path — and only then add the unit."* That work
already carries a green zero-movement assertion in the suite (a type-only booking writing a folio of `2 × €120.00`).

---

## 10 · AUDITOR SELF-CORRECTIONS (11) — the audit applied to itself

| # | Correction |
|---|---|
| 1 | The CSP image failure was **my origin choice** (`localhost` vs `127.0.0.1`), not the build |
| 2 | A source-vs-render misread — I saw `__bundler` in raw HTML and hypothesised the wireframe hadn't hydrated; it had |
| 3 | ⚠️ **A year-matching door-code regex** — `\b\d{4,6}\b` matched **`2026`**; I nearly recorded a **#33 violation on the strongest page in the audit** |
| 4–5 | Two residue-list errors, both in a list I had written one section earlier: `…/pay` is **POST-only** (no page owed) and **no `email-preferences` route exists** |
| 6 | ⚠️ **A dead server is indistinguishable from a refusal** — a probe returned `err` for two routes, reading exactly like a refusal; it was my own stopped server. Negative probes now carry a **positive control in the same run** |
| 7 | ⚠️ **The audit was closed too early** — the self-check verified *every DONE row has evidence* but never *every owed row is DONE*. Three pages had fallen through, including **Browse**, whose wireframe had been consumed as the **harness probe** and so looked covered to any grep |
| 8 | ⚠️ **The dateless leak probe** — all-null on a page that rendered almost no data; re-run on the priced state (§3.1) |
| 9 | ⚠️ **A false CRITICAL security finding, avoided by confirming** — `resolveClaimable()` keys on `sha1(email)` and my `route:list` read said `mw=NONE` for `signed`. Both routes in fact carry **`ValidateSignature`**; my filter grepped the **alias** while `route:list` prints the **resolved class** |
| 10 | A third needle/pattern false positive — `§15.1`/`§15.2` reported missing; they exist with a ⚠️ heading prefix. **All 38 § references resolve** |
| 11 | ⚠️⚠️ **RETRACTION — I "corrected" the canon without reading it** (§11.1 below) |

### ⚠️ 11.1 The retraction, in full

I filed the brief's *"the storefront is ONE POST"* as an imprecise premise, on the strength of `route:list`
showing three booking writes. **The canon is right and my finding was wrong.** `DECISIONS.md:12602` states it
outright — and is itself the correction of **my exact mistake in mirror image**:

> *"Both this brief and my own note on `5329211` recorded the storefront as TWO posts… **It is ONE.**
> `POST book/{property}/checkout` (`booking.reserve`) calls `quote()` AND `book()` in the same request; the
> folio exists when it returns. **`booking.book` is a JSON API returning `JsonResponse` that no view posts to.**"*

All three claims verified independently: `grep` over `resources/views/` for `booking.book`/`booking.quote` is
**empty**; the storefront form posts to `booking.reserve` (`booking/checkout.blade.php:117`); `book()` marks
**`payment_token` as `required`**.

**Two lessons, and the second is the one worth keeping:**
1. **`route:list` shows what EXISTS; it does not show what the UI USES.** An endpoint's existence is not
   evidence of its role — **the view layer is the authority on which path is the product's**.
2. ⚠️ **A confident finding against canon is the strongest possible signal to go read the canon first.**

---

## 12 · EVIDENCE INDEX — 26 captures, all verified present and non-zero

| # | Capture (`.captures/`) | Page |
|---|---|---|
| 01–04 | `…-01-trips-live` · `-02-reservation-live` · `-03-payments-live` · `-04-account-live` | Trips · Reservation · Payments · Account |
| 05 | `…-05-roomdetail-anon` | room detail **(anon)** |
| 06 · 24 | `…-06-home-live` · `…-24-home-live` | `/guest/home` |
| 07–10 | `…-07-messages-live` · `-08-help-live` · `-09-saved-live` · `-10-wallet-live` | Messages · Help · Saved · Wallet |
| 11 | `…-11-checkout-anon` | Checkout **(anon)** |
| 12–14 | `…-12-addons-live` · `-13-manage-live` · `-14-checkin-live` | Add-ons · Manage · Check-in |
| 15–16 | `…-15-arrival-live` · `-16-review-live` | Arrival (**#33 pre-window**) · Review |
| 17–20 | `…-17-login-anon` · `-18-claim-anon` · `-19-forgot-anon` · `-20-claimsent-anon` | the four **anon** auth surfaces |
| 21 | `…-21-thread-live` | message thread |
| — | **22 deliberately absent** | `…/pay` is **POST-only (405)** — no page, no capture owed |
| 23 · 26 | `…-23-browse-anon` (**dateless**) · `…-26-browse-priced-anon` (**priced — the leak probe's positive**) | storefront, both states |
| 25 | `…-25-receipt-live` | receipt |
| 27 | `…-27-verify-live` | claim/verify (valid signature) |

⚠️ **Incidental corroboration:** `01-trips`, `06-home` and `24-home` are **byte-identical in size (353,863)** —
filesystem evidence that `/guest/home` redirects to trips, independent of the controller.

---

## 13 · SELF-CHECK OF THIS DELIVERABLE (mechanical, not asserted)

| Check | Result |
|---|---|
| Wireframe coverage | ✅ **30/30 owed** · 0 open · 0 unaccounted |
| Route coverage | ✅ **34/34 GET** accounted (4 non-diffable, reasons recorded) |
| Write routes | ✅ **29 surfaced · 0 exercised** |
| Every covered row has **both-sides** char counts | ✅ throughout |
| Every capture cited exists on disk | ✅ **26/26**, re-run exact in both directions |
| § cross-references resolve | ✅ **38/38** |
| Perimeter probed **positive-first** | ✅ all four checks |
| Money reconciles | ✅ `folio_items` Σ = `folios.total_minor` — **0 of 41 folios mismatch** (#17) |
| Money as-seeded | ✅ **byte-identical** start → end |
| Rows written, all sources | ✅ **0** (incl. unclaimed guests **9 → 9**) |
| Mutations exercised | ✅ **0** |

---

## 14 · STATUS

**✅ THE GUEST SURFACE IS AT THE SAME BAR AS OWNER AND OPERATOR.** 0 backend gaps · 0 fabrications ·
perimeter clean on all four checks · every money figure reconciling.

**Open for a founder ruling (2):**
1. **F3** — the two pricing conventions on one journey (room VAT-exclusive vs add-on VAT-inclusive).
2. **F2** — whether the card's *"incl."* sublabel should list VAT and city tax too.

**Open for a fix pass (1):** **F5** — the operator-framed 403 body; the realm mechanism already exists in
`errors/layout.blade.php`.

**Open for a dedicated pass (1):** the **money-path proof** — `DECISIONS.md:12147`'s recipe, closing all six
mutation-gated paths together.

**⚠️ AUDIT CLOSED — read-only, report-only, no code changed, 0 mutations, money byte-identical.**
