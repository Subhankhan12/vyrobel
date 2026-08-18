# Vyrobel — GUEST FIX PASS · F2 + F5 DONE · **F3 STOPPED AND SURFACED**

**Date:** 2026-08-18 · **Branch:** `fix/guest-pricing-display-and-403`
**Source:** the sealed guest deep-parity audit (`docs/audits/GUEST-AUDIT-FINDINGS-COMPLETE.md`)

> ## ⚠️ STATUS — 2 of 3 SHIPPED. F3 IS A REPRICING, NOT A DISPLAY FIX.
>
> **F2 ✅ DONE** — the storefront card enumerates every charged component. **F5 ✅ DONE** — the 403 body is
> realm-branched. **⚠️ F3 STOPPED + SURFACED** — VAT-inclusive room display **cannot** be delivered as a
> display-basis change: the system already has a real inclusive mode, and using it **moves the total from
> €373.65 to €315.00** and the owner's revenue from **€255.00 to €207.32**. The brief's own guard applies.
> **Nothing was re-rated.** Money byte-identical; 0 mutations to stored money.

---

## 1 · CONFIRM-FIRST — what the source actually says

The audit was the map; the source is the authority. Confirmed before editing:

| Fact | Source |
|---|---|
| VAT is **`percentage`, `23%`, `inclusive = false`** | `taxes` row, property `seaside-demo` |
| City tax is `per_person_night` €2.50 · Cleaning is `flat` €45.00, non-optional | `taxes` / `fees` rows |
| The card total is built by `BookingEngineController::grandTotal()` → **`StayEstimator::estimate()`** | read at source |
| The realm branch lives in `errors/layout.blade.php:18-28`; `errors/403.blade.php` hardcoded one body | read at source |

**The current total, held invariant:** Double Room, 20→23 Sep, 2 guests —
**€255.00 accommodation + €58.65 VAT + €15.00 city tax + €45.00 cleaning = €373.65**, reconciling to the folio (#17).

---

## 2 · ⚠️ F3 — STOPPED AND SURFACED (the founder decision this actually needs)

### 2.1 The discovery: VAT-inclusive is already a first-class mode, and it is not a display setting

`StayEstimator` documents and implements it:

> *"A percentage tax marked `inclusive` is ALREADY in the room price → **it is NOT charged** (informational
> only); the displayed amount is the **EXTRACTED** portion `base·rate/(100+rate)`, never `base·rate/100`."*

`taxDetail()` already emits **`'23% · included'`** vs **`'23% · added'`**, and `estimate()` already marks the
line `informational: true` and excludes it from the total. **"VAT reads included" is existing configuration
(`taxes.inclusive`), not view formatting.**

### 2.2 Both routes to the ruling, computed

| Route | Total | Owner ex-VAT revenue | Verdict |
|---|---|---|---|
| **Today** (`inclusive = false`, VAT charged) | **€373.65** | **€255.00** | the baseline |
| **(A)** flip `taxes.inclusive = true` — the system's real mode | **€315.00** ⚠️ **−€58.65** | **€207.32** ⚠️ **−€47.68** | ⛔ **a REPRICING** |
| **(B)** gross the *displayed* nightly to €104.55 and relabel VAT "included", while VAT stays charged | €373.65 ✓ | €255.00 ✓ | ⛔ **a FALSE LABEL** |

**(A) is forbidden by the brief's own guard** — *"if making the display inclusive would MOVE THE TOTAL or change
the owner's ex-VAT rate, that is a REPRICING decision — STOP + SURFACE, do NOT silently re-rate."* It moves both.

**(B) holds the total but breaks something worse than the divergence F3 reported.** The storefront would say
*"€104.55/night, VAT included"* while the folio charges €85/night **plus VAT as a separate charged line**.
⚠️ And `StayEstimator` **already uses "included" to mean the other thing** — extracted, not charged. Using one
word for two meanings across surfaces is precisely the §N.8f drift this codebase is built to prevent. The tax
documents (which must itemise VAT) would then disagree with the storefront about what the nightly rate is —
**F3's own complaint, reintroduced one surface over.**

### 2.3 ⛔ THE DECISION THAT IS ACTUALLY OPEN

F3 was filed as a **display-consistency** finding. It turns out the two surfaces differ because they are on
genuinely different **bases**, not because one is formatted badly:

- **Add-ons** are inclusive **because their tax is configured inclusive** — the €79.95 the guest sees *is* the
  charge, VAT extracted for information.
- **Rooms** are exclusive **because VAT is configured exclusive** — it is a real, separately charged line.

**So making them consistent means choosing a basis, which is a pricing decision with three consequences:**
1. the headline nightly rate a guest sees (€85.00 → €104.55),
2. **the total** (€373.65 → €315.00) unless rates are re-authored,
3. **the owner's revenue** (€255.00 → €207.32) unless rates are re-authored.

⚠️ **Re-authoring rates to hold the total** (store €104.55 ex-… i.e. gross rates and mark VAT inclusive) is the
only path that is both honest *and* total-preserving — **and it is a rate migration through
`RateAndRestrictionService` across 929 materialised rate rows, not a display fix.** That is a founder decision
and a separate, money-path pass. **Not done here, not started.**

---

## 3 · ✅ F2 — the card enumerates what the total folds in

`BookingEngineController::grandTotal()` built the note by **re-summing the fees separately**. It now builds it
from **the estimate's own lines** — the same lines the pre-reserve breakdown and the folio read, so the card
cannot drift from them (§N.8f).

| Before | After |
|---|---|
| `€373.65 total · incl. €45.00 cleaning` | `€373.65 total · **incl. €58.65 VAT · €15.00 City tax · €45.00 Cleaning fee**` |

**⚠️ Informational lines are skipped, and that is load-bearing.** An `inclusive` percentage tax is **not
charged** and is not part of this total; listing it after "incl." would make the enumeration fail to sum.
This also means F2 stays correct **whichever way the founder rules on F3**.

**The reader can now account for every cent:** 58.65 + 15.00 + 45.00 = 118.65; 373.65 − 118.65 = **255.00 = 3 × €85.00**.

---

## 4 · ✅ F5 — the 403 body is realm-branched, from ONE derivation

The obvious fix — copy the layout's `match` into `403.blade.php` — would have created a **second definition of
"which realm"**, and two `match`es for one concept drift. So the derivation moved to
**`app/Support/ErrorRealm.php`** and **both** surfaces read it: the layout for the back-link, 403 for the body.
**No new realm was invented** — the same four plus the public default.

| Realm | 403 body |
|---|---|
| **guest** | *"This page belongs to a different booking, or it isn't part of your account. If you reached it from a link we sent you, the link may have expired."* |
| **owner** | *"Your owner account doesn't include this area… ask the **primary owner** on your account to grant the capability."* |
| **app / admin / public** | ⚠️ **unchanged, byte-identical** — an operator genuinely has a role and a team |

**The other five error views were not touched** — the audit swept all six and found 403 the only operator-framed body.

---

## 5 · ⚠️ TWO ADJACENT ITEMS FOUND WHILE FIXING — surfaced, NOT fixed

| # | Item | Why not fixed here |
|---|---|---|
| **A1** | **The error-page FOOTER is operator-framed in every realm** — *"If this keeps happening, contact your **Vyrobel administrator**."* (`errors/layout.blade.php:80`). A guest has no Vyrobel administrator. **Found by RENDERING the fixed 403** — the defect sat six lines below the one I fixed | it lives in the **shared layout**, so changing it renders on **all six** error views — explicitly out of this pass's scope |
| **A2** | **Two derivations of "is this percentage tax charged"** — `StayEstimator::taxApplies()` (used by the folio-posting path) and `estimate()`'s **inline** `$inclusive`. **Found by a mutation that changed one and left the other**, which is exactly how they would drift | a shared-helper refactor on a **money path**, out of scope for a display fix |

---

## 6 · MUTATION-VERIFIED — every guard proven to bite

⚠️ **Each mutation was confirmed to have hit the code the test exercises** — and one did not, which is why this
section exists rather than a list of greens.

| Test | Mutation applied | Result |
|---|---|---|
| **1 · total invariant** (money guard) | make the percentage tax inclusive in `estimate()` (a reprice) | ✅ **REDS** — total 37365 → 31500 |
| **2 · F2 enumeration** | blank the card note | ✅ **REDS** — proves the test reads the note, not "VAT" elsewhere on the page |
| **3 · VAT itemised** (compliance guard) | fold the VAT line into the room line | ✅ **REDS** |
| **4 · F5 realm copy** | revert the guest message to the operator copy | ✅ **REDS** |
| 5 · one realm source | — | pins back-link ↔ body agreement |

⚠️ **THE MUTATION THAT DIDN'T COUNT.** My first money-guard mutation changed **`taxApplies()`** — and the suite
stayed **green**. That was not a vacuous test: `estimate()` never calls `taxApplies()`, it computes `$inclusive`
inline (see **A2**). The mutation hit a different code path. Re-aimed at `estimate()`, the guard **reds**.
*A green under mutation means "the mutation missed" at least as often as "the test is weak" — check which.*

---

## 7 · RENDER-CONFIRMED (§N.21.6)

| Surface | Confirmed on screen |
|---|---|
| Storefront card (`.captures/fix-01-card-enumerated.png`) | `€85.00 / night · **€373.65 total** · incl. €58.65 VAT · €15.00 City tax · €45.00 Cleaning fee` — ⚠️ **the total is unchanged to the cent**; the second card enumerates too (€724.20 · incl. €124.20 VAT) |
| Guest 403 (`.captures/fix-02-guest-403.png`) | the guest copy reads correctly; **no "your role", no "your team", no "permission"**; back-link still *"Back to your trips"* |
| Operator/owner 403 | ⚠️ **pinned byte-identical by test 4, NOT browser-rendered** — producing one would require granting/revoking a permission, i.e. a mutation. Stated rather than implied |

---

## 8 · DIDN'T OVER-REACH · NO REGRESSION

| Guard | Result |
|---|---|
| Money as-seeded | ✅ **byte-identical** — 929 / 10,912,000 / 0 / 41 / 16 / 0 / 40 |
| Stored money / rate engine touched | ✅ **none** — `RateAndRestrictionService` and materialisation untouched (D4-1) |
| Tax documents | ✅ VAT still itemised — pinned by test 3, reds on strip |
| `book()` / reserve path | ✅ **not exercised** — still UNVERIFIED-WITHOUT-MUTATION (the deferred money-path pass) |
| Add-ons pricing · city tax · cleaning computation | ✅ untouched (only the card's *display* grouping) |
| The other five error views · ratified refusals · owner/operator surfaces · deploy gate | ✅ untouched |
| Regression suites | ✅ `PublicBookingEngine` + `ErrorPages` + `GuestClaim` + `GuestAuthFoundation` + `GuestAccount` + `BackOfficeAccess` + both operator gate suites — **65 tests / 437 assertions green** |
| PHPStan L5 · Pint | ✅ **0 errors** · clean |

---

## 9 · ⛔ WHAT NEEDS THE FOUNDER

1. **F3 — the basis decision** (§2.3). Consistency requires choosing a basis, and the only honest
   total-preserving path is a **rate migration** (gross rates + `taxes.inclusive = true`) across 929
   materialised rows — a money-path pass, not a display fix.
2. **A1** — the error-footer wording (touches all six views).
3. **A2** — de-duplicating the two "is this tax charged" derivations (a money path).

**⚠️ Nothing was re-rated. F2 and F5 are shipped and proven; F3 is stopped, surfaced, and waiting.**
