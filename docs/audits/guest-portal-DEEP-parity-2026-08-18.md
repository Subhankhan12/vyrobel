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
> | Pages browser-diffed | **22** + 6 money artifacts · **29/30 owed wireframes covered, 1 open** ⚠️ *§18 closed early — corrected in §19–§20* |
> | Perimeter checks | ✅ **COMPLETE** — anonymous-leak · guest isolation · pricing reconciliation · money chain |
> | Findings | **2 premise corrections · 1 divergence · 2 honest limits · 2 auditor self-corrections · 1 inventory correction** |
> | Money as-seeded | ✅ **byte-identical**: 929 rate rows · 10,912,000 minor · 0 overrides · 41 reservations · 16 payments · 0 seasons · 40 folio items · 0 quotes |
> | Mutations exercised | **0** — `book()` UNVERIFIED-WITHOUT-MUTATION |
>
> ⚠️ **15 pages REMAIN** (⚠️ 2 more are structurally unverifiable read-only — see §14). This is a resumable ledger, not a completed sweep — the perimeter (the part unique to
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

---

## 10 · BATCH 3 — the reserve-adjacent flow (4 pages) · ⚠️ AND THE HEADLINE FINDING

### 10.1 · `/book/{property}/checkout` — the last screen before the money moves · wf 467 / live 573

⚠️ **This page settles the pricing question the room card only half-answered.** It itemises everything:

    Room · €85.00 × 3        €255.00
    VAT · 23% · added         €58.65
    City tax                  €15.00
    Cleaning                  €45.00
    Total                    €373.65

| Wireframe | Live | Verdict |
|---|---|---|
| Your details (name/email/phone) | ✅ FIRST/LAST NAME · EMAIL | **MATCH** |
| Line-item price breakdown | ✅ **and more explicit** — a labelled **`VAT · 23% · added`** line the prototype lacks | **MATCH + EXTRA** |
| Total | ✅ €373.65, reconciling to the components exactly | **MATCH** |
| **`Pay with Apple Pay` · `CARD NUMBER / EXPIRY / CVC`** | **absent** — replaced by **"PAY ON ARRIVAL · Reserve now and settle at the property — no card is collected here."** | ✅ **RATIFIED REFUSAL** (#45 / SAQ A — no raw PAN) |
| *"won't be charged until check-in"* | *"your room is only reserved once you confirm — **availability is re-checked at that moment**"* | ✅ **EXTRA — an honesty statement the prototype does not make** |
| `Apply wallet credit €88.00` | absent at checkout (wallet apply exists on `/guest/wallet` against a booking) | **DIVERGENCE — placement** |
| `Confirm & pay €501.00` | `Confirm reservation` → `POST /book/seaside-demo/checkout` | ⚠️ **LOCATED, NOT SUBMITTED — UNVERIFIED-WITHOUT-MUTATION** |

⚠️ **This substantially DOWNGRADES divergence B** (§7). I recorded that the room card's *"incl. €45.00
cleaning"* under-states the total's composition. It does — but the **checkout page, the last screen before
commitment, itemises VAT and city tax explicitly**. The guest is therefore never surprised: the card gives a
correct total, and the breakdown appears before any money moves. **B is a card-label nuance, not an honesty
defect** — recorded as such, because over-filing is as wrong as under-filing.

### 10.2 · `/guest/reservation/{r}/extras` (Add-ons) · wf 1,125 / live 632 — ⚠️ **0 register rows**

| Wireframe | Live | Verdict |
|---|---|---|
| Extras catalogue | ✅ **the property's REAL `AddOn` rows** (Airport transfer · Baby cot · Breakfast · Late checkout) | **MATCH** |
| Per-stay totals | ✅ real and basis-aware (€65.00 each way → **€79.95**; €18.00 per guest·night → **€132.84**) | **MATCH** |
| "Prices include VAT" | ✅ present | **MATCH** |
| "Nothing to pay now" | ✅ *"everything settles to your booking, nothing to pay at the door"* | **MATCH** |
| The prototype's 6-extra catalogue + cart badge (`€56 In your cart · 1`) | real catalogue, no cart | ✅ **RATIFIED REFUSAL** — register line 14 refuses *"the wireframe's invented 6-extra catalogue (real = the property's AddOns)"* |

⚠️ **A hypothesis I raised and then resolved AGAINST myself:** Baby cot shows €15.00/night → **€45.00** for 3
nights with **no VAT**, while transfer and breakfast are multiplied by 1.23 — on a page captioned *"Prices
include VAT"*. That reads as an inconsistency. **It is not:** `add_ons.taxable` is **`NO`** for Baby cot and
`yes` for the other three. The per-item VAT treatment follows the real flag, and €45.00 is exactly right.
**Not a finding.**

### 10.3 · `/guest/reservation/{r}/manage` · wf 615 / live 700

| Wireframe | Live | Verdict |
|---|---|---|
| Change dates · Guests & extras · Cancel | ✅ all three, real POSTs (`manage/date-change`, `manage/add-ons`, `manage/cancel`) | **MATCH** — ⚠️ **all UNEXERCISED** |
| Interactive month calendar (tap check-in, then check-out) | date inputs + **`See price difference`** | **DIVERGENCE — ratified** (the guest realm has no Alpine; register line 14: *"peach-glass interactivity is pure CSS + progressive enhancement"*) |
| — | *"Changes are confirmed by the property — we'll message you the moment they are."* | ✅ **EXTRA — honest**: a request, not a promise |
| `booked via Booking.com` | absent | ✅ consistent with the **OTA-brand refusal** (#37) |

### 10.4 · `/guest/reservation/{r}/check-in` · wf 1,066 / live 992

| Wireframe | Live | Verdict |
|---|---|---|
| 3-step wizard (Details → ID → Arrival) | ✅ identical, real POST `check-in` | **MATCH** — ⚠️ **UNEXERCISED** |
| Arrival rail, guest count, door-code timing | ✅ real (*"By email · Door code · pre-arrival"*) | **MATCH** |
| **`By SMS`** door-code delivery | **`By email`** | ✅ **RATIFIED REFUSAL** — no SMS provider (register line 14) |
| Live countdown `6d 05:35:57` | `in 18d` | **DIVERGENCE — minor** (no live ticker; no Alpine) |
| Guest's own email rendered | `demo.traveller@vyrobel-demo.test` | ✅ **CORRECT under #28** — the account holder's own contact on their own page |

---

## 11 · ⚠️ NEW FINDING — TWO PRICING CONVENTIONS ON ONE GUEST JOURNEY

This is what a **cross-page** pass surfaces that a page-by-page gate structurally cannot, and it is this
audit's genuinely new contribution beyond GATE 3:

| Surface | Convention | Evidence |
|---|---|---|
| **Room rates** | **VAT-EXCLUSIVE** — VAT added and labelled at checkout | `€85.00 / night`; checkout adds **`VAT · 23% · added` €58.65** |
| **Add-ons** | **VAT-INCLUSIVE** — per the real `taxable` flag | `€65.00 each way` → **€79.95** (×1.23); page caption *"Prices include VAT"* |

**Each surface is individually correct, labelled, and reconciles to the folio.** But a guest on one journey
reads **`€85.00 / night`** (pre-tax) beside **`€65.00 each way`** (post-tax) and has no cue that the two
figures are on different bases.

**Class: DIVERGENCE (cross-page consistency), not a defect.** Nothing is fabricated, nothing is hidden, and
both labels are true. ⚠️ **Not filed as a gap, and deliberately not "fixed"** — which convention should win is
a product/tax-presentation decision (many jurisdictions mandate tax-inclusive display to consumers), and the
register shows both were arrived at deliberately. **Surfaced for a founder ruling.**

---

## 12 · UPDATED TALLY (re-derived, not carried)

| | |
|---|---|
| Pages browser-diffed | **13 of 32** |
| Captures on disk | **14** |
| Perimeter | ✅ complete |
| Findings | 2 premise corrections · **1 new cross-page divergence** · 3 placement/minor divergences · 2 honest limits · 2 auditor self-corrections · 1 inventory correction · **1 hypothesis resolved against myself** (add-on VAT) |
| Ratified refusals confirmed (not gaps) | **8** — card-on-file · saved cards · Apple Pay/raw PAN · multi-property browse · invented extras catalogue · SMS door code · loyalty/referral · OTA brand |
| Backend gaps | **0** |
| Mutations exercised | **0** |

**REMAINING: 19 pages** — chiefly the money PDFs (receipt · invoice · statement · credit-note · refund ·
arrival), the pre-auth pages (login · claim · forgot/reset), review, arrival info, and the two
`/book/…/{quote}` confirmation routes (⚠️ which need a real quote — **unreachable without booking**, so they
are **structurally unverifiable in a read-only pass** and belong to the deferred money-path proof).

---

## 13 · BATCH 4 — the money artifacts, arrival, review, and the pre-auth pages

### 13.1 · ⚠️ THE MONEY PDFs — isolation proven on the ARTIFACTS, not just the pages

Read through the request context (a `page.goto` aborts on a download — the operator run-29 lesson).

| Artifact | Guest's OWN | Another guest's |
|---|---|---|
| `receipt.pdf` | ✅ **200 · real `%PDF`** · 879,525 B | 🔒 **404** |
| `invoice.pdf` | ✅ **200 · real `%PDF`** · 879,559 B | 🔒 **404** |
| `statement.pdf` | ✅ **200 · real `%PDF`** · 886,158 B | 🔒 **404** |
| `arrival/pdf` | ✅ **200 · real `%PDF`** · 880,932 B | 🔒 **404** |
| `credit-note.pdf` | **404** ⚠️ see below | 🔒 404 |
| `refund.pdf` | **404** ⚠️ see below | 🔒 404 |

⚠️ **The two 404s on the guest's OWN reservation are CORRECT, and I checked before concluding it.** That
reservation has **0 refund payments** (queried first: `items=2 payments=1 refunds=0`). A credit note and a
refund receipt for a booking that was never refunded would be fabricated documents. **DATA-DEPENDENT, not a
gap** — and the honest behaviour.

⚠️ **This is a stronger isolation proof than the page checks:** a guest cannot pull another guest's
**financial documents**, which is where the real exposure would sit. All six 404, and the positive is
established by four real PDFs on the guest's own booking.

### 13.2 · `/guest/reservation/{r}/arrival` — ⚠️ **#33 verified in the harder (pre-window) state**

The probed reservation arrives **5 Sep 2026 — 18 days out**, so the door code must be withheld.

    GETTING IN
    Your access details appear here closer to arrival
    Your door code and WiFi unlock on Thu, 3 Sep — for your security.

| Check | Result |
|---|---|
| Any door code rendered | ❌ **none** — the only 4+ digit run on the page is **`2026`**, the year |
| Any WiFi password rendered | ❌ **none** |
| Does it explain rather than silently omit | ✅ names the **exact unlock date** (3 Sep) and the reason |

✅ **#33 holds, and does so articulately** — the page states the rule instead of leaving a blank.

⚠️ **AUDITOR SELF-CORRECTION #3.** My first probe used `/door code|access code|\b\d{4,6}\b/i` and returned
`doorCode: true` on **both** this page and the Review page. Both were the regex matching **`2026`**. A
four-digit matcher fires on every year, and I nearly recorded a **#33 violation on the strongest page in the
audit**. Fixed by reading the full text and listing the digit runs rather than testing a boolean.

### 13.3 · `/guest/reservation/{r}/review` — live 196 chars

| Element | Live | Verdict |
|---|---|---|
| Rating + review body | ✅ real published review (★5, real text) tied to a **checked-out** stay | **MATCH** |
| `Published.` state | ✅ | **MATCH** — a real two-state control in its published state, **not** a refusal |
| Reviewer byline | not the guest's full name | ✅ consistent with the register's *"review byline never your full name"* (#28) |

### 13.4 · `/guest/login` (anon, 128 chars) · `/guest/claim` (anon, 174 chars)

| Element | Live | Verdict |
|---|---|---|
| Email + password + Sign in | ✅ | **MATCH** |
| `Forgot password?` · `Show` (password reveal) | ✅ | **MATCH** |
| `Access your bookings` → `/guest/claim` | ✅ *"Enter the email from your booking and we'll send you a secure link"* | ✅ **RATIFIED** — booking-tied claim, **not** open self-signup (register: *"Signup/Claim (booking-tied claim vs open self-serve signup)"*, `DO-G11-3`) |
| Property marketing hero / social login | absent | ✅ **RATIFIED DIVERGENCE** (register, GATE 3 line) |
| ⚠️ Leak check | **no PII, no money, no internals** on either anonymous page | ✅ |

---

## 14 · SELF-CHECK AT THE CLOSE (re-derived, not carried)

| Check | Result |
|---|---|
| Pages browser-diffed | **17 of 32** + **6 money artifacts** |
| Captures on disk | ✅ **18** `deep-guest-*` files, re-counted at close |
| Every DONE row has both-sides evidence | ✅ char counts recorded for every wireframe/live pair |
| Perimeter probed positive-first | ✅ anonymous leak · guest isolation (pages **and** artifacts) · #33 pre-window |
| Money as-seeded | ✅ byte-identical start → end |
| Mutations exercised | ✅ **0** |
| Auditor self-corrections | **3** — the CSP origin · the source-vs-render mis-read · **the year-matching door-code regex** |
| Hypotheses resolved against myself | **2** — the add-on VAT (baby cot `taxable=NO`) · the bundler-shim suspicion |

### ⚠️ What this pass still does NOT verify — stated, not implied

1. **The reserve → folio → charge chain.** The reserve form was located (`POST /book/{property}/checkout`)
   and **never submitted**. `book()` remains **UNVERIFIED-WITHOUT-MUTATION**.
2. **`booking_quotes`** — 0 rows; the quote link of the money chain is unverifiable on seeded data.
3. **`/book/{property}/confirmation/{quote}` and `/book/{property}/reservation/{quote}`** — ⚠️ these need a
   **real quote id**, which only a booking creates. **Structurally unverifiable in a read-only pass.** They
   belong to the deferred money-path proof, and are excluded from the denominator rather than counted as
   un-diffed pages.
4. **15 pages remain** — the residue after (3): chiefly `reset-password/{token}`, `claim/sent`,
   `claim/verify/{guest}/{hash}`, `forgot-password`, `messages/{thread}`, `payments/{r}/pay`,
   `email-preferences`, and the storefront `search` JSON endpoint (an API, no diff owed).

**⚠️ Recommendation unchanged and now better evidenced:** the single highest-value next step is the
**dedicated money-path proof** — it closes items 1, 2 and 3 together, and nothing else in this ledger can.

---

## 15 · BATCH 5 — the final pages, and two probes that had to be run *negatively*

Three pages diffed, plus two credential-bearing routes that can only be probed by forging the credential.

| Page | Live | Verdict |
|---|---|---|
| `/guest/forgot-password` | 136 ch · 1 form → `POST /guest/forgot-password` | ✅ real; copy is enumeration-neutral |
| `/guest/claim/sent` | 242 ch · no form; 2 recovery links | ✅ real — **and the copy is the security-correct one** (below) |
| `/guest/messages/{thread}` | 263 ch · real 2-message conversation, real timestamps, `Send` → `POST` same id | ✅ real |

**⚠️ `claim/sent` states the enumeration-safe thing, deliberately.** The body reads *"**If** this email has a
booking with us, we've just sent a secure link"* — it does **not** confirm whether the address exists. That is
the same neutral-enumeration discipline the signup and login paths were credited for. Recorded as a **credit**,
not a finding.

### ⚠️ 15.1 — the two credential routes, probed with FORGED credentials

Both are GET routes carrying a secret in the URL. The positive case needs a real emailed credential, which only
a mutation produces — so the only read-only proof available is the **negative**: a forged value must be refused.

| Probe | Result | Reading |
|---|---|---|
| `/guest/claim/verify/{bogus-ulid}/{bogus-hash}` | **403** | ✅ **refused** |
| `/guest/reset-password/{bogus-token}` | **200 — the form renders** | ⚠️ **not a defect; see below** |

**⚠️ The reset-password 200 is by design, and the honest record is that its real gate is unproven here.** The
token is validated on the **POST**, not on the GET — standard Laravel, and arguably the better choice, since a
GET that 404'd on a bad token would be a token-enumeration oracle. But it does mean **the gate itself sits on a
path this pass did not exercise**: `reset-password` POST is **UNVERIFIED-WITHOUT-MUTATION**, exactly like
`book()`. Rendering the form proves nothing about whether a forged token can actually reset a password.

### ⚠️ 15.2 — SELF-CORRECTION #5: a dead server is indistinguishable from a refusal

The first run of the probes above returned `err` for both — which reads exactly like "the route refused me", and
would have been recorded as a *stronger* result than the truth. **It was my own app server, which I had stopped
two steps earlier.** The re-run therefore leads with a **control probe** (`/guest/login` → 200); only against a
proven-live server is a 403 a refusal rather than a dial tone. *A negative probe needs a positive control in the
same run, or it proves nothing.*

---

## 16 · ⚠️ FINDING GUEST-F5 (low, presentation) — the 403 body is OPERATOR-framed in every realm

The forged-hash probe refused correctly, but rendered:

> "**Your role** doesn't include this area. If you need it, ask an **administrator on your team** to grant the
> permission."

A guest has no role, no team, and no administrator. Neither does an owner. This is the operator RBAC vocabulary
shown to two realms it does not describe.

**⚠️ The realm mechanism already exists one file over — the body just doesn't use it.**
`resources/views/errors/layout.blade.php:18-28` matches on the path across **four** realms to pick the
back-link, which is why the same page correctly ends with *"Back to your trips"*. `errors/403.blade.php:6`
hardcodes one message with **no branch at all**. (⚠️ §N.13.1 again: the defect sits beside the correct thing.)

**Swept, not sampled — and the sweep is what bounds it.** All six error views checked:

| View | Realm-branches | Body |
|---|---|---|
| **403** | **0** | ⚠️ **operator-framed — the only one** |
| 404 | 1 | ✅ neutral ("…or it isn't part of your account") |
| 419 · 429 · 500 · 503 | 0 | ✅ neutral — read correctly to any actor |

So this is **one view**, not "the error pages are wrong" — five of six are already realm-safe by being written
neutrally. **Presentation only; the refusal itself is correct.** Fix shape: branch the message the way the
layout already branches the link. **Not fixed here — this pass is read-only.**

---

## 17 · INVENTORY RE-VERIFICATION AT CLOSE — run both ways, and it moved the denominator

Re-derived from `route:list` and `docs/design/raw/`, not carried forward.

### 17.1 ⚠️ Two inventory corrections — both *shrink* what was owed

1. **`/guest/payments/{reservation}/pay` is `POST`-only.** A GET returns **405**. It is a **mutation endpoint,
   not a page**, and no diff is owed. It was carried in §14's residue as an un-diffed page — wrong.
   *(It was, correctly, never POSTed.)*
2. **There is no guest `email-preferences` route.** §14's residue named one. The *Guest Email Preferences*
   wireframe maps into **`/guest/account`**, which was diffed in batch 2. Also wrong, also removes an item.

⚠️ **Both errors were in a list I wrote myself, one section earlier** — the same lesson the operator audit
recorded and the canon states as *"a register is a record of what one reader found."* Re-derivation caught
them; carrying the list forward would not have.

### 17.2 Wireframes → routes: 32 · mapped 30 · unmapped 2 — both already adjudicated

| Unmapped wireframe | Status | Not a gap because |
|---|---|---|
| **Guest ID Verification** | account-gated | guest ID/KYC is on `CLAUDE.md`'s account-gated tail — deferred honestly, never faked |
| **Guest Referral Landing** | ⛔ DON'T-BUILD-as-drawn | `DIVERGENCE-REGISTER.md` — 5 absent subsystems behind it (#39) |

**Neither is an un-built page.** Both are prior rulings, re-confirmed rather than re-litigated.

---

## 18 · FINAL SELF-CHECK (re-derived at close)

| Check | Result |
|---|---|
| Pages browser-diffed | **20 of 32** + **6 money artifacts** |
| Denominator after corrections | 32 wireframes − 2 adjudicated-unmapped = **30 pages a diff is owed on**; of the 10 not diffed, **2 are structurally unverifiable read-only** (the `{quote}` pair) |
| Captures on disk | ✅ **21** `deep-guest-*`, re-counted |
| Perimeter | ✅ anonymous · guest-vs-guest isolation (pages, **artifacts**, **threads**, **and a pay route**) · #33 pre-window · forged-credential refusal |
| Money as-seeded | ✅ **byte-identical start → end** — `rate_calendar` 929 / €109,120.00 · overrides 0 · reservations 41 · payments 16 · seasons 0 · folio_items 40 · quotes 0 · guests 10 · anonymized 0 |
| Mutations exercised | ✅ **0** |
| Findings | **5** (4 prior + GUEST-F5) |
| Auditor self-corrections | **5** — CSP origin · source-vs-render · door-code regex · **the residue list (×2, §17.1)** · **the dead-server false negative (§15.2)** |

### ⚠️ What this pass still does NOT verify — carried forward unchanged, plus one addition

1. **The reserve → folio → charge chain** — `book()` **UNVERIFIED-WITHOUT-MUTATION**.
2. **`booking_quotes`** — 0 rows.
3. **`/book/{property}/confirmation/{quote}` · `/book/{property}/reservation/{quote}`** — need a real quote id;
   **structurally unverifiable read-only**.
4. **⚠️ NEW — `POST /guest/reset-password`** — the token gate is on the POST, which was not exercised (§15.1).
5. **`POST /guest/payments/{r}/pay`** — a real charge path; never submitted, by constraint.

**⚠️ Recommendation, now with a second item.** The **dedicated money-path proof** still closes 1–3 together and
remains the highest-value next step. Items 4 and 5 are **credential/charge gates that only a mutation pass can
prove** — they should ride along with it rather than becoming a third pass.

**⚠️ AUDIT CLOSED — read-only, report-only, no code changed, 0 mutations, money byte-identical.**

---

## 19 · ⚠️ THE AUDIT WAS CLOSED TOO EARLY — three pages the self-check couldn't see

§18 declared the pass closed. Re-deriving the tally **the way the brief requires** (a summary is a claim)
showed the count of **20** was arithmetically right — 5+4+4+4+3 diff blocks — and **still wrong as a coverage
claim**, because it counted *diff blocks* and never asked **which wireframes they covered**.

⚠️ **THE SELF-CHECK VERIFIED THE WRONG DIRECTION.** It confirmed *every DONE row has evidence* — true — but
never *every owed row is DONE*. Those are different claims, and only the second is coverage. Three pages fell
through the gap:

| Page | Why it was invisible to the self-check |
|---|---|
| **`/book/{property}` (Browse)** | ⚠️⚠️ its wireframe was consumed as the **harness probe** (§1: *"`Guest Browse.html` renders 1,931 chars"*) — so the name appeared in the report, "covered" to any grep, and the **page itself was never diffed**. The anonymous storefront entry: the highest-stakes surface in this audit. |
| **`/guest/reservation/{r}/receipt`** | quoted in §3 as *pricing evidence*, never diffed against `Guest Folio Receipt.html` |
| **`/guest/home`** | in `route:list`, in **no ledger row at all** |

*A wireframe used to prove an instrument is not a wireframe that was audited.* Consuming it for the harness is
exactly what made it look done.

### 19.1 · `/book/{property}` vs `Guest Browse.html` + `Guest Booking.html` — ⚠️ TWO STATES, and the first one nearly broke the audit's headline check

⚠️⚠️ **THE DATELESS STATE INVALIDATED MY OWN LEAK PROBE.** Opened bare, `/book/seaside-demo` renders **323
chars** of a date-gated empty state — *"Pick dates above to see availability & prices"*, room names, and
**zero prices** (`money: []`). My leak probe returned all-null against that — and **all-null on a page that
rendered almost no data is worth nothing**. It is the probe rule failing on the single highest-stakes check
here, and it would have shipped as *"the anonymous surface leaks nothing."*

Re-run **with dates supplied**, so the page is capable of a positive:

| State | Chars | Real data rendered |
|---|---|---|
| dateless | 323 | ⚠️ none — `PICK DATES`, no prices |
| **`?check_in=2026-09-20&check_out=2026-09-23&adults=2`** | **596** | ✅ **€85.00/night · €373.65 total** · **€180.00/night · €724.20 total** · `★ 5 · 1 review` / `★ 4.5 · 6 reviews` · **`1 left` / `2 left`** real availability |

**Only against that** is the leak assertion evidence — and it is clean on all five classes:

| Leak class | Result on the **priced** page |
|---|---|
| Guest PII (emails / phones / other guests' names) | ✅ **none** |
| Owner financials (net · commission · statement · payout · IBAN) | ✅ **none** |
| Operator internals (margin · housekeeping · staff · role · organization) | ✅ **none** |

✅ **THE ANONYMOUS SURFACE LEAKS NOTHING — now properly evidenced, positive-first.**

**⚠️ And the filters were never missing — they are date-gated.** The dateless state shows no sort and no
filters; the searched state renders **Recommended · Top rated · Price ↑ · Price ↓ · BEDROOMS · AMENITIES
(8 real) · MAX PRICE** — i.e. `Guest Booking.html`'s entire results apparatus is **built**. Filing those from
the first render would have been a phantom gap: **DOM-absence ≠ capability-absence, caught live.**

| Wireframe (`Browse` + `Booking`) | Live | Verdict |
|---|---|---|
| Search: WHERE / WHEN / GUESTS | ✅ + **ADULTS / CHILDREN steppers** | **MATCH +** (finer than the wireframe) |
| Sort · BEDROOMS · AMENITIES · MAX PRICE | ✅ all present in the searched state | **MATCH** |
| Room cards: name, type, sleeps, rating, price, total | ✅ all **real** | **MATCH** |
| Availability (`1 left` / `2 left`) | ✅ real | **MATCH +** — not in the wireframe |
| `Select` → | `Reserve` | **MATCH** (⚠️ **not pressed** — UNVERIFIED-WITHOUT-MUTATION) |
| **`List` / `Map` toggle** | absent | **MISSING-candidate** (map view; no geo data modelled) |
| Multi-property collection: Faro/Lagos/Tavira, "3 villas", "from €110", featured stays, category tiles | single-property | ✅ **RATIFIED KIND-3** — register line 14 |
| **"4.8 across 369 stays"**, "124 reviews" | real counts (★5 · 1 review) | ✅ **HONEST** — the prototype's aggregates are invented (#39) |

**Price composition — the third independent confirmation of the two conventions (§11).** €373.65 =
room **€255.00** (3 × €85) + **VAT €58.65** (23% of the room) + **city tax €15.00** + **cleaning €45.00**.
VAT is charged on the room line only, and is **added**, not included.

### 19.2 · `/guest/reservation/{r}/receipt` vs `Guest Folio Receipt.html` — wf 737 / live 410

| Wireframe | Live | Verdict |
|---|---|---|
| Header, reference, stay dates, paid state | ✅ real (`R-E2AZQ1NX` · 5→8 Sep 2026 · **Balance settled**) | **MATCH** |
| Charge lines | ✅ **Room stay €300.00 · VAT €69.00 · Subtotal €369.00** | **MATCH** — reconciles (below) |
| Document downloads | ✅ **Folio receipt PDF · VAT invoice PDF · Statement of account PDF** | **MATCH +** — three real artifacts |
| **`Visa ····4242`** | absent | ✅ **RATIFIED REFUSAL** — no card vault, register line 14 |
| **Per-payment rows** (`Booking.com · prepaid €480.00`, `Visa · €229.00`) | only a scalar **`Total paid`** | **DIVERGENCE (minor)** — see below |
| Letterhead (address, `VAT PT 512 034 776`), `RECEIPT NO.`, `Email me a copy`, `Message the property` | absent | **MISSING-candidate** (present on the **PDF**, which carries the letterhead) |
| `of which VAT · 6%` (**VAT-INCLUSIVE** framing) | `VAT €69.00` **added**, 23% | ⚠️ **DIVERGENCE — the two conventions again** (§11) |

**⚠️ The per-payment row nearly became a false finding, twice over.** The reservation has **exactly one
payment**, so a one-row list and a scalar total render identically — I could not tell them apart from the DOM.
Reading the view settles it: `receipt.blade.php:28` `@forelse ($folio['lines'] …)` loops **charges**, and
`Total paid` is a **scalar** — there is no payment loop at all. So it is a **real divergence, not
data-dependence** — but note part of what the wireframe shows there (`····4242`) is itself a **ratified
refusal**, so this is "no per-payment breakdown", not "the card vault is missing."

**Money chain, read-only, on this reservation:** `folio_items` **2 rows, Σ 36,900** = `folios.total_minor`
**36,900** = displayed **€369.00** ✅ (#17 holds; Room €300 + VAT €69, 23% exactly).

### 19.3 · `/guest/home` — an inventory omission with an explanation, not a page

`GuestHomeController@index` is `return redirect()->route('guest.trips')`. That is why it rendered **431 chars,
identical to `/guest/trips`**. ⚠️ I noticed the identical count and nearly recorded a duplicate diff — the
route is an **alias**, owed no diff of its own. It was still missing from the ledger, which is the omission.

---

## 20 · ⚠️ CORRECTED COVERAGE — derived per wireframe, not counted from diff blocks

| | |
|---|---|
| Guest wireframes | **32** |
| ✅ Covered | **29** (22 pages browser-diffed + 6 money artifacts + 1 as a searched state) |
| ⛔ Adjudicated, no route | **2** — ID Verification (account-gated KYC) · Referral Landing (DON'T-BUILD-as-drawn) |
| ⚠️ Open | **1** — **Guest Verify Email** |
| Unaccounted | **0** |

**⚠️ The one open row, stated exactly.** `/guest/claim/verify/{guest}/{hash}` was **negative-probed only** — a
forged hash returns **403**. The positive requires a real emailed link, which only a mutation produces. It is
**UNVERIFIED-WITHOUT-MUTATION**, not diffed, and must not be read as covered.

**Routes with no wireframe:** `/book/{property}/confirmation/{quote}` · `/book/{property}/reservation/{quote}`
— still **structurally unverifiable read-only** (they need a quote id only a booking creates).

### ⚠️ 20.1 — the corrected close-out (replaces §18's tally)

| Check | §18 said | **Corrected** |
|---|---|---|
| Pages browser-diffed | 20 | **22** + 6 artifacts |
| Wireframe coverage | *not asked* | **29 / 30 owed**, 1 open, 0 unaccounted |
| Captures on disk | 21 | **25**, re-counted |
| Findings | 5 | **5** (GUEST-F5; §19's items are divergences/candidates within existing classes) |
| Auditor self-corrections | 5 | **8** — +§19 the harness-consumed wireframe · +**the dateless leak probe** · +the home/trips duplicate |
| Mutations exercised | 0 | **0** |
| Money as-seeded | byte-identical | **byte-identical** |

⚠️ **The most valuable finding of this batch is about the method, not the build:** *a page named in a report is
not a page audited*, and *a self-check that only validates its own DONE rows can never discover a row that was
never opened.* Coverage must be derived from the **inventory**, page by page — the direction §18 skipped.
