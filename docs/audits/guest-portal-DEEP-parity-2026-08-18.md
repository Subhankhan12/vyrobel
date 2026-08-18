# Guest surface — DEEP parity audit · 2026-08-18 (READ-ONLY · REPORT-ONLY · money-as-seeded)

**Branch** `audit/guest-deep-parity` · the third and last deep parity audit, after owner (28 pages, sealed) and
operator (52 pages, 7 findings, sealed).

> ## ⚠️⚠️ PREMISE CORRECTION FIRST — THE GUEST SURFACE WAS **NOT** UN-AUDITED
>
> The brief opens *"this is the THIRD and LAST deep parity audit … the last un-deep-audited role."*
> **That is not the state of the repo.** Discovered mid-pass, from the register itself:
>
> - `DIVERGENCE-REGISTER.md:238` — **"GATE 3 status (guest): ✅ COMPLETE — all 17 guest surfaces diffed
>   (0 code changes — entirely at-parity / intentional-divergence)."**
> - **27 `Guest *` rows** already carry per-page verdicts in that register (Trips, Reservation, Manage Booking,
>   Check-in, Arrival Info, Payments, Folio Receipt, Wallet/Credits, Messages, Wishlist+Waitlist, Review,
>   Account, the money PDFs, …).
> - A **§G-1…§G-13 guest walk** with its own `G13-VERDICTS.md`, plus **five** guest audit documents in `docs/`
>   (`GUEST-PORTAL-AUDIT.md`, `GUEST-BROWSE-CHECKIN-CHECKOUT-AUDIT.md`, `GUEST-EMAILS-…-AUDIT.md`,
>   `FULL-AUDIT-OPERATOR-GUEST.md`, `OPERATOR-GUESTS-PROFILE-AUDIT.md`).
>
> ⚠️ **So the per-page element diff this brief asks for has largely been done before**, and several things I was
> on course to file as findings are already adjudicated there (see §7). **What was genuinely missing — and is
> what this pass contributes — is the PERIMETER:** the anonymous-leak probe, the guest-isolation probe, and the
> money-chain reconciliation against seeded folios. Those are complete.
>
> ⚠️ **The inventory can be wrong applies to the audit's own premise, not only to its page list.** Reporting
> 33 pages as freshly-discovered work would have overstated this pass and implicitly discarded a completed gate.

## ⚠️ STATUS — PARTIAL, AND SAID SO
>
> | | |
> |---|---|
> | Pages browser-diffed | **9 of 32** (+ the full perimeter) ⚠️ *and largely re-diffing a completed gate — see the premise correction* |
> | Perimeter checks | ✅ **COMPLETE** — anonymous-leak · guest isolation · pricing reconciliation · money chain |
> | Findings | **2 premise corrections · 1 divergence · 2 honest limits · 2 auditor self-corrections · 1 inventory correction** |
> | Money as-seeded | ✅ **byte-identical**: 929 rate rows · 10,912,000 minor · 0 overrides · 41 reservations · 16 payments · 0 seasons · 40 folio items · 0 quotes |
> | Mutations exercised | **0** — `book()` UNVERIFIED-WITHOUT-MUTATION |
>
> ⚠️ **23 pages REMAIN.** This is a resumable ledger, not a completed sweep — the perimeter (the part unique to
> the guest surface) is finished; the per-page element diffs are not.

---

## 1 · HARNESS — proven both ways before any diff

| Instrument | Proof |
|---|---|
| Playwright ↔ live guest | `/book/seaside-demo` renders *"Find your stay · Book direct at Demo Seaside Property"*, title `Book your stay · Demo Seaside Property` |
| Playwright ↔ wireframes | `:8099` `Guest Browse.html` renders **1,931 chars** of real copy |
| **Guest entry model** | ⚠️ **A REAL LOGIN**, not a token/link. `demo.traveller@vyrobel-demo.test` / `password`, separate `guest` guard at `/guest/login` (`DemoSeeder.php:538` — a booking-tied claim: `forceFill(['password' => 'password', 'email_verified_at' => now()])`). Login lands on `/guest/trips`. |
| Guest's own booking | `R-E2AZQ1NX` (`01m0a26wh3z7…`), 5→8 Sep 2026, confirmed. Guest has **3** reservations. |
| Another guest's booking | `01m0a26wjv4v92j0fyf6sgqkbj` — the isolation target |

⚠️ **`guests.email` is an encrypted cast**, so a raw `where('email', …)` finds nothing — the guest had to be
located by name. A lookup that silently returns null would have made every downstream check vacuous.

### ⚠️ AUDITOR SELF-CORRECTION #1 — a CSP failure that was mine, not the build's

The storefront logged 4 console errors: *"Loading the image … violates Content Security Policy `img-src 'self'`"*.
It reads as a real defect. It is not: the app generates image URLs on `localhost:8000` while I was browsing
`127.0.0.1:8000`, and **under CSP those are different origins**. Measured both ways:

| Origin | `imgLoaded` |
|---|---|
| `127.0.0.1:8000` | **0** |
| `localhost:8000` | **2** ✅ |

**A harness artifact.** The whole audit was re-run on `localhost`. ⚠️ Had this gone unchecked it would have been
filed as a broken storefront.

### ⚠️ AUDITOR SELF-CORRECTION #2 — I read the SOURCE and mis-judged the RENDER

`Guest Wishlist.html` returned only **172 chars**, and its raw HTML is full of `__bundler` / *"Unpacking…"* /
*"requires JavaScript"* machinery. I formed the hypothesis that **the wireframe had not hydrated** and that my
char count was measuring a loading shim — which would have invalidated that row's diff.

**Re-probed at 2s and 8s: identical 172 chars, and the text is real —** *"Your wishlist · Rooms you save will
appear here · **No saved stays yet**"*. The prototype is **genuinely drawn as an empty state**. It hydrated
fine; every prototype here is a bundle.

⚠️ **The error was reading the raw HTML and drawing a conclusion about the render.** That is this audit's own
first rule running backwards — *diff the rendered DOM, not the source* — and it would have produced a false
retraction of a correct reading. **The original char counts stand**; they came from `innerText` after
hydration throughout.

*(Consequence for the row: the live `/guest/saved` carries real wishlist + waitlist data with real POST routes
while the prototype is empty — the LIVE side is richer, an **EXTRA**, not a gap.)*

---

## 2 · THE LEDGER — derived from `route:list` + `docs/design/raw/`

**34 guest-facing GET routes** · **33 guest wireframes**.

| Shell | Routes | Note |
|---|---|---|
| **Public `/book`** (anonymous) | **6** | ⚠️ **`/book/{property}/search` is a JSON API, not a page** — it returns `{"data":[{room_type_id,…,total_minor}]}`. Counted as an endpoint; **no diff is owed**, and treating it as a page would have produced a phantom gap. Effective public pages: **5** |
| **Guest** (authed) | **28** | all behind `Authenticate:guest` + `SetTenantForGuestRequest`, except the 5 pre-auth routes (`login`, `claim`, `claim/sent`, `claim/verify/{guest}/{hash}`, `forgot-password`, `reset-password/{token}`) |

**Effective diffable pages: 32** (⚠️ 33 − `/guest/home`, confirmed an alias). Diffed this pass: **9**.

| # | Page | Wireframe | Shell | Status |
|---|---|---|---|---|
| 1 | `/guest/trips` | `Guest Trips.html` | guest | **DONE** |
| 2 | `/guest/reservation/{r}` | `Guest Reservation.html` | guest | **DONE** |
| 3 | `/guest/payments` | `Guest Payments.html` | guest | **DONE** |
| 4 | `/guest/account` | `Guest Account.html` | guest | **DONE** |
| 5 | `/book/{property}/rooms/{roomType}` | `Guest Villa Detail.html` | public | **DONE** |
| 6 | `/guest/home` | — | guest | ⚠️ **ALIAS, not a page** — `routes/web.php:397`: *"legacy → redirects to trips"*. Renders byte-identically to `/guest/trips` (431 chars, same controls). **No diff owed** |
| 7 | `/guest/messages` | `Guest Messages.html` | guest | **DONE** |
| 8 | `/guest/help` | `Guest Help.html` | guest | **DONE** |
| 9 | `/guest/saved` | `Guest Wishlist.html` | guest | **DONE** |
| 10 | `/guest/wallet` | `Guest Credits.html` | guest | **DONE** |
| 11–33 | the rest | mapped | both | **REMAINING** |

---

## 3 · ⚠️ THE PREMISE CORRECTION — §G.1 IS NOT THE GUEST PRICING RULE

The brief states the headline check as: *"§G.1 flat/seasonal prices are VAT-INCLUSIVE FINAL prices — the price
shown IS what the guest pays; no separate VAT line; no VAT added at reserve."*

⚠️ **Read at source, §G.1 is something else entirely.** `PROJECT-STATE.md:761` — **"G.1 — THE BILLING /
PRICING MODEL"** is the **SaaS subscription** model: operator plans, `flat` vs per-unit **plan shapes**, the
super-admin plan panel, captured subscription terms, VAT numbers reaching Stripe for reverse charge. Its own
words: *"**Stripe Tax computes the customer's own tax**"* — tax **computed and added**, which is the opposite
of a VAT-inclusive rule. `flat` there is a **plan shape**, not a room rate.

**So a §G.1 violation cannot be filed against the storefront — the section does not govern it.** The guest
storefront's honesty invariant is **#17: the grand total reconciles to the folio**, recorded in
`DIVERGENCE-REGISTER.md:14` (*"grand total reconciles to the folio incl. the real cleaning fee (#17)"*).

⚠️ Filing the brief's check as written would have produced a **false headline finding** against a correct
build. Recorded as a premise correction, per *a finding is one reader's hypothesis*.

### What the pricing model actually is — measured

Storefront card, Double Room, 20→23 Sep (3 nights):

    €85.00 / night      €373.65 total · incl. €45.00 cleaning

Derived from the property's **real** tax/fee rows (`taxes`: VAT 23% percentage, City tax €2.50 per-person-night;
`fees`: Cleaning €45.00):

| Component | Double Room | One-Bedroom Apt |
|---|---|---|
| Accommodation (3 nights) | €255.00 | €540.00 |
| VAT 23% | €58.65 | €124.20 |
| Cleaning fee | €45.00 | €45.00 |
| City tax (€2.50 × 2 × 3) | €15.00 | €15.00 |
| **Displayed total** | **€373.65** ✅ | **€724.20** ✅ |

Both reconcile **to the cent**. ⚠️ **Rates are VAT-EXCLUSIVE and VAT is itemised** — confirmed on the seeded
folio, which carries VAT as its own line:

    R-E2AZQ1NX   Room stay €300.00 · VAT €69.00 · Σ €369.00 = folios.total_minor 36900 ✓
    reservation_units.price_minor €300.00 = the "Room stay" folio item ✓

**And the guest sees exactly that**: `/guest/reservation/{r}/receipt` renders *"Room stay €300.00 · VAT €69.00
· €369.00"*. **Display === charge, and the guest is shown the tax rather than surprised by it.**

### ⚠️ DIVERGENCE (minor, honesty-adjacent) — the per-night headline is pre-tax while the total is post-tax

`€85.00 / night` × 3 = €255.00, but the total is €373.65. The card's sublabel says **"incl. €45.00 cleaning"**
and stops there — it does not say the total also includes **€58.65 VAT and €15.00 city tax**.

**Not a fabrication and not a hidden charge** — the final total is shown prominently *before* reserve, which is
the substance of the honesty rule. But the label under-states what it is "incl." of, and a guest reading
"€85/night" would reasonably arrive at €255. **Recorded as a divergence, not a violation.** The one-word fix
would be a fuller "incl." list; that is a product call, not an audit finding.

---

## 4 · ⚠️ THE GUEST PERIMETER — the checks owner and operator did not have

### 4.1 · The anonymous surface leaks nothing — **probed positive first**

| Page | Rendered real data? | Guest PII | Owner financials | Operator internals |
|---|---|---|---|---|
| `/book/seaside-demo` (with dates) | ✅ 2 room cards, real prices | **none** | **none** | **none** |
| `/book/…/rooms/{roomType}` | ✅ €85.00 · €373.65 · €45.00 · real rating "★ 5 · 1 review" | **none** | **none** | **none** |

⚠️ **The positive came first**: each page was confirmed to render real, non-empty data before its emptiness of
PII was asserted — an empty page leaks nothing and proves nothing.

**And the authed surface is genuinely closed to anonymous callers:** `/guest/home` and
`/guest/reservation/{own}` both render the **login page** (128 chars) rather than any content.

### 4.2 · Guest isolation — **positive before negative**

| Probe | Result |
|---|---|
| ✅ **POSITIVE** — own reservation `R-E2AZQ1NX` | **200**, real content, €369.00 |
| ✅ **POSITIVE** — own receipt | **200**, Room stay €300.00 · VAT €69.00 · €369.00 |
| 🔒 another guest's reservation | **404** |
| 🔒 another guest's receipt | **404** |

**404, not 403** — the same shape the owner audit proved for owner scope: the resource does not resolve rather
than announcing that it exists.

### 4.3 · The money chain, read-only

| Link | Result |
|---|---|
| `folio_items` Σ vs `folios.total_minor` | ✅ **0 of 41 folios mismatch** (#17) |
| `reservation_units.price_minor` → folio "Room stay" | ✅ €300.00 = €300.00 on the probed reservation |
| ⚠️ `booking_quotes.total_minor` | **UNVERIFIABLE on seeded data — 0 rows** |

⚠️ **The brief's chain `folio_items = reservation_units.price_minor = booking_quotes.total_minor` cannot be
closed at the quote link**, because the seeder creates reservations through the **operator** path
(`AssignmentService`), not the storefront's `quote()`. `booking_quotes` is empty and joins by
`converted_reservation_id`. **An honest limit, not a gap** — and one only a live booking (or a seeded quote)
could close.

---

## 5 · ⚠️ THE RESERVE → FOLIO CHAIN IS **DEFERRED**, NOT DONE

The storefront is **one POST** — `booking.reserve` = `quote()` + `book()` in the same request — so a quote
cannot be pulled without creating a reservation and folio.

**This pass rendered the flow up to the reserve control and stopped.** The `Reserve` / `Book this room`
buttons were located and left **unpressed**.

| Mutation | Status |
|---|---|
| `POST booking.reserve` (`quote()` + `book()`) | ⚠️ **UNVERIFIED-WITHOUT-MUTATION** |
| Guest check-in, add-ons, cancel, date-change, pay-balance, review, account delete | ⚠️ **surfaced, unexercised** |

⚠️ **Stated plainly: the reserve→folio→charge chain is the one thing this pass does not verify.** What *is*
verified is that the **already-seeded** folios reconcile and that the storefront's displayed total is composed
from the same real tax/fee rows. **A separate dedicated money-path proof is recommended** and must not be
inferred from this report.

---

## 6 · PER-PAGE DIFFS (5 of 33)

Both-sides char counts recorded for every row; every live page screenshotted.

### 6.1 · `/guest/trips` vs `Guest Trips.html` — wf 625 / live 431

| Wireframe | Live | Verdict |
|---|---|---|
| Upcoming / Past / Cancelled tabs | ✅ with **real counts** (Upcoming 2 · Past 1 · Cancelled 0) | **MATCH +** |
| `Manage` · `View reservation` | ✅ both | **MATCH** |
| Stay money (€120.00) | ✅ **real** (€369.00 · €184.50) | **MATCH** |
| `All years` filter | absent | **MISSING-candidate** ⚠️ source not yet read — not filed |
| `Documents` control | absent | **MISSING-candidate** ⚠️ source not yet read — not filed |
| `Browse stays` · avatar "Amara AO" | absent / different | **DATA-DEPENDENT** (identity is the real logged-in guest) |

### 6.2 · `/guest/reservation/{r}` vs `Guest Reservation.html` — wf 1,005 / live 839

| Wireframe | Live | Verdict |
|---|---|---|
| Check-in online · 2 min | ✅ identical | **MATCH** |
| Check-in / check-out date rail | ✅ real (5 Sep 15:00 → 8 Sep 11:00) | **MATCH** |
| Balance · settled | ✅ **€369.00 Balance · settled** (wf shows €0) | **MATCH** — live carries the real figure |
| Arrival info / Add-ons / Manage / Message | ✅ all four | **MATCH** |
| `Directions` | absent | **MISSING-candidate** ⚠️ unread |
| `Help & FAQs`, `Account & payment methods` | absent from this page | **DIVERGENCE — placement** (both exist as their own routes) |

### 6.3 · `/guest/payments` vs `Guest Payments.html` — wf 971 / live 844

| Wireframe | Live | Verdict |
|---|---|---|
| Receipt list | ✅ **3 real receipts** with real refs (R-E2AZQ1NX · R-ZZ4HWTRO · R-CG2FFE07) and real states | **MATCH +** |
| Totals | ✅ real (€1,107.00 · €922.50 · €184.50 · €369.00) | **MATCH** |
| **`···· 4242 Card on file`, `Add payment method`** | **absent** | ✅ **RATIFIED REFUSAL** — `DIVERGENCE-REGISTER.md:14` refuses *"····4242/raw-PAN card entry (SAQ A/#28)"*. **Not a gap** |
| `Pay by card` | ✅ present | **MATCH** (the honest Stripe-hosted seam) |
| VAT invoice / Statement of account | routes exist (`invoice.pdf`, `statement.pdf`) | **DIVERGENCE — placement**, not absence |

### 6.4 · `/guest/account` vs `Guest Account.html` — wf 988 / live 1,093

| Wireframe | Live | Verdict |
|---|---|---|
| Credit KPI (€88) | ✅ **real €35.00** | **MATCH** |
| Waitlists | ✅ real (1) | **MATCH** |
| Language EN/DE/PT | ✅ | **MATCH** |
| **`2 Saved cards`, `Add card`, `Remove`** | **absent** | ✅ **RATIFIED REFUSAL** — no card vault (D3), register line 14 |
| Delete my account | ✅ present (the real 14-day scheduled delete, D2) | **MATCH** |
| Live is **larger** than the wireframe (1,093 > 988) | — | **EXTRA** |

### 6.5 · `/book/…/rooms/{roomType}` vs `Guest Villa Detail.html` — wf 2,620 / live 627

| Wireframe | Live | Verdict |
|---|---|---|
| Room title, sleeps, amenities, reviews | ✅ real (Double Room · sleeps 3 · real amenities · **★ 5 · 1 review**) | **MATCH** |
| Price + total | ✅ €85.00 / night · €373.65 total · incl. €45.00 cleaning | **MATCH** |
| Reserve control | ✅ `Book this room` | **MATCH** (⚠️ **not pressed**) |
| Save / wishlist | ✅ `♥ Save to wishlist` | **MATCH** |
| **`Show all 124 reviews`, `Show all 26 amenities`** | real counts instead (1 review) | ✅ **HONEST** — the prototype's figures are invented (#39) |
| `Share`, `Show all photos`, `Report this listing` | absent | **MISSING-candidate** ⚠️ unread |
| Multi-property framing (`All villas`, "Villa Azure") | single-property (`All rooms`, "Double Room") | ✅ **RATIFIED KIND-3** — register line 14: *"the storefront is single-property vs the wireframe's multi-property browse"* |

---

## 7 · FINDINGS

| # | Class | Finding |
|---|---|---|
| **A** | ⚠️ **premise correction** | **§G.1 does not govern guest pricing** — it is the SaaS subscription billing model, whose own text says Stripe Tax *adds* the customer's tax. The storefront rule is **#17 reconciliation**, and it **holds to the cent**. Filing the brief's check as written would have produced a false headline finding |
| **B** | divergence (minor) | The card's **"incl. €45.00 cleaning"** under-states the total's composition — €58.65 VAT and €15.00 city tax are also included. The final total *is* shown pre-reserve, so this is a labelling divergence, not a hidden charge |
| **C** | honest limit | **`booking_quotes` has 0 rows** — the quote link of the money chain is unverifiable on seeded data (the seeder books via the operator path) |
| **D** | honest limit | **The reserve→folio chain is DEFERRED**, not done — one POST, not exercised |
| **E** | auditor self-correction | The CSP image failure was **my origin choice**, not the build (`localhost` vs `127.0.0.1`) |
| — | **ratified refusals confirmed, NOT gaps** | card-on-file / `····4242` (SAQ A, #28) · saved cards / vault (D3) · multi-property browse (KIND-3) · the prototype's invented review and amenity counts (#39) |

**Backend gaps: 0 found so far.** **Frontend MISSING-candidates: 5, none filed** — `All years`, `Documents`,
`Directions`, `Share`, `Report this listing` — ⚠️ each needs its source read before it becomes a finding
(*a DOM absence is not a capability absence*; the owner audit caught this three times).

---

## 8 · SELF-CHECK OF THIS DELIVERABLE

| Check | Result |
|---|---|
| Every DONE row has both-sides char counts | ✅ 5/5 |
| Every capture cited exists on disk | ✅ 5 files under `.captures/deep-guest-*` |
| Perimeter claims probed positive-first | ✅ anonymous-leak and isolation both |
| Tallies re-derived, not carried | ✅ routes and wireframes re-counted from source |
| No verdict rests on reasoning where a fetch was owed | ⚠️ **5 MISSING-candidates deliberately NOT filed** for exactly this reason |
| Money as-seeded | ✅ byte-identical, start → end |
| Mutations exercised | ✅ **0** |
| ⚠️ Ledger complete | ❌ **5 of 33 — stated, not implied** |

**REMAINING: 23 pages.** The perimeter is complete; the per-page sweep is not.\n\n⚠️ **But before resuming, settle the scope question the premise correction raises:** GATE 3 already diffed all 17 guest\nsurfaces with per-page verdicts. Re-diffing them yields duplicate verdicts, not new information. The honest\nremaining work is (a) the pages GATE 3 did **not** cover, and (b) the **deferred money-path proof** — not a\nsecond full sweep of adjudicated ground.

---

## 9 · BATCH 2 — four more pages (both sides hydrated; char counts recorded)

### 9.1 · `/guest/messages` vs `Guest Messages.html` — wf 1,142 / live 182

| Wireframe | Live | Verdict |
|---|---|---|
| Thread list + composer | ✅ one real thread (*"Early check-in? 18 Aug 2026 · 09:11"*) + `Send`, real POST `guest/messages` | **MATCH** (thin — **DATA-DEPENDENT**, one seeded thread) |
| Host card "Marco Your host", "~10 min Typical reply" | absent | ⚠️ register-adjudicated — see §7 |
| Reservation context rail (dates, balance, `View reservation`) | absent from this page | **DIVERGENCE — placement** |
| `Call` · `Email` the host | absent | ✅ consistent with **#28** (no guest-facing contact channels beyond the relay) |

### 9.2 · `/guest/help` vs `Guest Help.html` — wf 1,900 / live 687

| Wireframe | Live | Verdict |
|---|---|---|
| Topic filters | ✅ **real**: All · Before you arrive · Payments · Your booking · After your stay · Your account | **MATCH** |
| Search | ✅ | **MATCH** |
| `Message us` | ✅ real POST `guest/help` | **MATCH** |
| **"19 Help articles · 5 Topics · < 1 hr Typical reply · 24/7 Urgent line"** | absent | ✅ **HONEST** — prototype counts and an SLA the build does not measure (#39) |

### 9.3 · `/guest/saved` vs `Guest Wishlist.html` — wf 172 / live 538

| Wireframe | Live | Verdict |
|---|---|---|
| Wishlist section | ✅ with a **real saved room** + real `remove` POST | **MATCH** |
| ⚠️ *(prototype is an EMPTY state — "No saved stays yet")* | live carries **real wishlist AND waitlist** rows + `Join waitlist` / `Leave` POSTs | ⚠️ **EXTRA — the live side is richer than the prototype** |

⚠️ A rare shape: the prototype shows the empty state and the build shows the populated one. **Over-crediting
risk inverted** — the correct verdict is EXTRA, not "wireframe incomplete".

### 9.4 · `/guest/wallet` vs `Guest Credits.html` — wf 1,480 / live 696

| Wireframe | Live | Verdict |
|---|---|---|
| Credit balance | ✅ **real €35.00** | **MATCH** |
| Redeem a code | ✅ real POST `guest/wallet/redeem` | **MATCH** |
| Apply to a booking | ✅ real POST `guest/wallet/apply/{reservation}` with a real €184.50 owed | **MATCH** |
| Ledger (added/used) | ✅ real (€25.00 · €10.00) | **MATCH** |
| **Silver membership tier · "€103 total earned" · referral / copy-invite** | absent | ✅ **RATIFIED REFUSAL** — `DIVERGENCE-REGISTER.md:232` refuses the whole loyalty/referral system as fabricated; `:311` records `Guest Referral Landing` as **DON'T-BUILD as drawn (#39)** with five absent subsystems. ⚠️ **Explicitly: "Do NOT 'fix' `guest/wallet.blade.php`'s honest deferral — it is test-defended (`GuestWalletTest.php:175`)."** |

⚠️ **§7 UPDATE — three of my five MISSING-candidates are already adjudicated.** `Documents` (13 register
mentions), `Directions` (2) and `Share` (54) appear in the register; the wallet's membership/referral absence
is an explicit ratified refusal. **None of the five should be filed without reading its register row first** —
which is precisely why they were left unfiled.
