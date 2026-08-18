# Vyrobel — GUEST MONEY-PATH PROOF · **PROVEN** (and the gap was smaller than the canon said)

**Date:** 2026-08-18 · **Branch:** `fix/guest-money-path-proof-and-a2` (atop sealed main `ae052b4`)

> ## ⚠️⚠️ THE HEADLINE IS A CORRECTION: `book()` WAS NEVER UNVERIFIED.
> Confirm-first found the storefront money path **already driven and already proven** in the suite — including
> the full canon chain. The audit's *"UNVERIFIED-WITHOUT-MUTATION"* and F4 were true statements about **what a
> read-only audit could see**, not about the system. **The seal I wrote — *"reserve→folio money-path proof
> remains"* — overstated the gap, and this report corrects it.**
>
> What was genuinely missing is **narrower and real**, and is now proven: the chain had only ever been proven
> on a property with **no taxes and no fees**, summing **the room line alone**. **✅ NOW PROVEN: the grand
> total the storefront DISPLAYS is exactly what the folio CHARGES**, with VAT, city tax and cleaning present.
> **A2 folded in**, and the mutation that silently missed in Pass 1 **now bites**.

---

## 1 · CONFIRM-FIRST — what already existed

| Already proven, before this pass | Where |
|---|---|
| A **type-only** storefront booking through the real `booking.reserve` route produces a real folio at the type price | `StorefrontUnitChargeTest` "step zero" |
| The **full canon chain** — folio room charge === `reservation_units.price_minor` === **`booking_quotes.total_minor`** | `SeasonalRateTierTest:127-131` |
| Unit-override pricing, no-override fallback, and an unavailable-unit falling back to type-level | `StorefrontUnitChargeTest` tests 2–4 |

⚠️ **So the canon recipe's step — *"get ONE type-only storefront booking to produce a folio"* — was already
done, and its zero-movement assertion is still in the suite.** I extended rather than rebuilt, exactly as the
brief required. And `booking.book` was **not** driven: the proof posts to `route('booking.reserve', …)` with
`payment_option = on_arrival`, the path the checkout form actually posts.

### ⚠️ 1.1 The gap, located precisely

`publicPropertyScenario` creates **0 taxes and 0 fees**, and both existing chain proofs sum
**`folio_items.type = 'room'` only**. So the chain was proven for **the room line on a tax-free property**.
**Never proven:** that the composite grand total a guest is *shown* — the €373.65 shape the audit measured and
the F2/F3 card copy describes — is what the folio actually *charges*. That is #17 through the storefront path
with taxes and fees present, and it is what the whole display layer rests on.

---

## 2 · ✅ THE PROOF — `tests/Feature/StorefrontGrandTotalChargedTest.php`

Three tests, on a property carrying the real shape (23% exclusive VAT · per-person-night city tax · flat cleaning).

| # | Proves |
|---|---|
| **1** | The **displayed** grand total (read from the rendered storefront card, **not** recomputed from `StayEstimator` — that would agree by construction and prove nothing) equals **Σ ALL `folio_items`**, and the folio's own `total_minor` agrees with its items. **€502.80** = room 36000 + VAT 8280 + city tax 1500 + cleaning 4500 |
| **2** | The canon chain **with taxes present**: folio room charge === `reservation_units.price_minor` === `booking_quotes.total_minor` |
| **3** | **F4 CLOSED** — 0 quote rows before, **exactly 1** after, carrying the right property and total |

**⚠️ Probe-the-positive, applied to a booking.** A `booking.reserve` POST returns **200 when it re-renders on a
validation error**, having created nothing. Every assertion reads the **artifacts** — reservation, folio,
folio_items, reservation_units, booking_quotes — never the status code.

**⚠️ Taxes and fees land as their own lines**, asserted separately: a folio that reached the right total by
charging one fat room line would pass the sum while destroying the tax documents.

### 2.1 Why F4 read as unverifiable

The seeded demo has **0 `booking_quotes`** because the **seeder books through the operator path**
(`AssignmentService`), which mints no quote. Only the **storefront** path writes one — so a read-only audit on
seeded data could never see this link. Driving the storefront path is what closes it.

---

## 3 · ✅ A2 — one derivation, and the mutation now BITES

`StayEstimator::isInclusivePercentage()` is now **the one derivation** of *"this percentage tax is already in
the room price"*. `taxApplies()` returns its negation; `estimate()`'s inline copy is gone. **Behaviour
preserving** — every figure unchanged.

### ⚠️ 3.1 The green→red transition — the correctness proof for A2

| Mutation target | Tests red |
|---|---|
| **The ONE derivation** (post-A2) | **4** — including **`holds_the_grand_total_byte_identical`**, the money guard, **and** the new storefront charge proof |
| **`taxApplies()` alone** (the pre-A2 blind-spot shape) | **1** — ⚠️ **the total-invariant money guard stays GREEN** |

**That contrast is the whole finding.** In Pass 1 a mutation at `taxApplies()` left the money guard green and I
recorded it as "the mutation missed". It missed because `estimate()` never called it. Now a single mutation
reaches both paths — *a money-path guard whose reach you cannot mutate is a guard whose reach you don't know*.

---

## 4 · MUTATION-VERIFIED — including two mutations that MISSED

| Mutation | Result |
|---|---|
| Stop posting **taxes** to the folio | ✅ **REDS** test 1 (displayed ≠ charged) |
| Stop posting **fees** | ✅ **REDS** test 1 |
| Corrupt the **quote total** | ✅ **REDS** tests 1, 2 **and** 3 |
| The A2 derivation | ✅ **REDS** 4 (§3.1) |

⚠️ **Two quote mutations silently missed first**, and the reason matters: I guessed the field name from the
**database column** (`total_minor`) when the model writes a **`Money` cast** (`'total' => $total`). Both perl
substitutions matched nothing and the suite stayed green — which looks exactly like a weak test. Fixed by
switching to an **exact-match replace that asserts the old text was present** before writing. *On a money path,
"did the mutation apply?" must be proven, not assumed.*

---

## 5 · MONEY-AS-SEEDED — the booking lived and died inside the transaction

Every test uses **`RefreshDatabase`**. The persisted demo database, after the full run:

| rate rows | minor | overrides | reservations | payments | seasons | folio_items | **quotes** |
|---|---|---|---|---|---|---|---|
| **929** | **10,912,000** | **0** | **41** | **16** | **0** | **40** | **0** |

⚠️ **Byte-identical, and `quotes` is still 0** — the clearest possible evidence the real bookings this pass
drove never touched the persisted data. Sums and counts, never a hash (date/ULID-nondeterministic).

---

## 6 · SCOPE — what "charged" means here

⚠️ **The folio's charge amount, not a live Stripe capture.** Go-live keys are not set, and the proof books with
`payment_option = on_arrival` — the real choice that keeps the folio local (the card path redirects to Stripe
and would say nothing about what was charged). **A live payment capture remains out of scope and untested**,
gated on the founder's go-live flip.

---

## 7 · STATUS

| | |
|---|---|
| Full suite | ✅ **2468 / 2466 passed / 2 skipped / 0 failed** (baseline 2465 → **+3, exactly this file**) |
| PHPStan L5 · Pint | ✅ **0** · clean |
| `book()` | ✅ **PROVEN LIVE** — and was already, before this pass |
| The chain with taxes/fees | ✅ **NEWLY PROVEN** — displayed === charged |
| **F4** | ✅ **CLOSED** — the storefront path materialises the quote |
| **A2** | ✅ **CLOSED** — one derivation; the guard now bites |
| Money-as-seeded | ✅ byte-identical |
| Live Stripe capture | ⛔ **out of scope** — founder's go-live flip |
| Deferred F3 migrate-to-gross | ⛔ unchanged — only if EU consumer-display compliance requires it |

**⚠️ No money-path bug was surfaced.** The chain reconciled on the first run, and the mutations confirm the
assertions bite. Nothing was fixed inline; A2 was the only change, and it is behaviour-preserving.
