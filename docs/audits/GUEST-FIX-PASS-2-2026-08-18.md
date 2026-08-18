# Vyrobel — GUEST FIX PASS 2 · F3 CUE (ratified) + A1 ERROR-FOOTER · **DONE**

**Date:** 2026-08-18 · **Branch:** `fix/guest-vat-cue-and-error-footer` (on top of `fdcce2e` = F2 + F5)
**Closes:** the guest fix pass opened by the sealed deep-parity audit.

> ## ✅ STATUS — DONE. Copy only; **the total never moved**.
> **F3 cue** ✅ — the room nightly now reads **`€85.00 / night · excl. VAT`**, parallel to the add-ons'
> *"Prices include VAT"*. **A1** ✅ — the error-page footer is realm-branched through the **same**
> `ErrorRealm` derivation the back-link and the F5 403 body read. **F3 ratification recorded** in
> `DECISIONS.md` + `DIVERGENCE-REGISTER.md`, with the migrate-to-gross path deferred as a money-path decision.
> Total **byte-identical €373.65**; money-as-seeded intact; 0 numbers changed.

---

## 1 · CONFIRM-FIRST

| Confirmed at source | Where |
|---|---|
| The add-ons' inclusive caption to parallel | `resources/views/guest/extras.blade.php:58` — `Prices include VAT`, gated `@if ($vatRate > 0)` |
| The card nightly + the F2 total sublabel | `resources/views/booking/browse.blade.php:249-250` |
| The room-detail nightly (**same payload**) | `resources/views/booking/room.blade.php:110` |
| The footer, shared across all six error views | `resources/views/errors/layout.blade.php:80` |
| The realm helper to reuse | `app/Support/ErrorRealm.php` (created by F5) |

---

## 2 · ✅ F3 — the cue (copy only)

| Surface | Before | After |
|---|---|---|
| Storefront card | `€85.00 / night` | **`€85.00 / night · excl. VAT`** |
| Room detail | `€85.00 / night` | **`€85.00 / night · excl. VAT`** |
| Total sublabel | *(F2, unchanged)* | `€373.65 total · incl. €58.65 VAT · €15.00 City tax · €45.00 Cleaning fee` |
| Pre-reserve checkout | — | **no change needed** — it already itemises `VAT · 23% · added`, so the basis is explicit |

**Three things make the cue honest rather than decorative:**

1. ⚠️ **It is gated on the tax being genuinely CHARGED**, via the existing `StayEstimator::taxApplies` — false
   for an inclusive percentage tax. A property that already prices inclusively shows **no cue**, because for it
   the nightly really *is* gross and "excl. VAT" would be a lie. **Test 7 pins this.**
2. ⚠️ **It names the property's OWN tax**, never a hardcoded "VAT" — an IVA or MwSt property is described
   correctly, the same way F2's enumeration takes its labels from the real tax rows.
3. ⚠️ **It is on the NIGHTLY, never the total.** The total genuinely *includes* the tax; marking it "excl."
   would be false. **Test 6 asserts the absence of `total · excl.` as well as the presence of the cue.**

**Reused, not rebuilt:** the card's existing `.vy2-price-sub` span and the room detail's `.vy-price-unit` —
no new component, no token changes.

---

## 3 · ✅ A1 — the error footer, realm-branched from ONE derivation

The footer lives in the **shared layout** and renders on **all six** error views, so it was telling every
reader in every realm to *"contact your Vyrobel administrator"*. A guest has none.

`ErrorRealm::supportLine()` was added **next to** `forbiddenMessage()` — the footer reads the **same**
`current()` derivation the back-link and the 403 body already read. **No second realm `match` was written**,
which is the whole reason the helper exists.

| Realm | Footer |
|---|---|
| **guest** | *"If this keeps happening, reply to your booking confirmation email and the property will help."* |
| **owner** | *"…contact the primary owner on your account, or your property manager."* |
| **app · admin · public** | ⚠️ **unchanged** — *"…contact your Vyrobel administrator."* |

**Only the footer changed** — the other five error-view **bodies** are untouched.

---

## 4 · ✅ The F3 ratification, recorded

`DECISIONS.md` (2026-08-18) and `DIVERGENCE-REGISTER.md` now record: **the two bases are ratified, not a
defect** — rooms VAT-exclusive, add-ons VAT-inclusive, both reconciling to the folio (#17), ambiguity closed by
a cue. ⛔ And the **deferred** path is written down so it is not rediscovered as a bug: inclusive room display
*while holding the total* requires **re-authoring rates to gross + `taxes.inclusive = true` across 929
materialised rows** through `RateAndRestrictionService` — a money-path migration, to be taken up **only** if EU
consumer-display compliance later requires it. **A1 done; A2 (the `taxApplies`/`estimate` duplication) still
open for that pass.**

---

## 5 · MUTATION-VERIFIED — and each mutation checked to hit its path

⚠️ Pass 1's lesson applied: a green under mutation means *the mutation missed* at least as often as *the test
is weak*. Every mutation below was confirmed present in the source before its result was believed.

| Test | Mutation | Result |
|---|---|---|
| **6 · the cue** | blank `$nightlyNote` | ✅ **REDS** |
| **7 · the gate** | show the cue even when VAT is inclusive | ✅ **REDS** |
| **8 · A1 footer** | return the operator line for guests | ✅ **REDS** |
| **1 · total invariant** (money guard, carried from Pass 1) | make the percentage tax inclusive in `estimate()` | ✅ **REDS** — and reds 3 + 6 too, since a reprice moves the enumerated figures |

**8 tests / 42 assertions green** on restore.

### ⚠️ 5.1 An auditor self-correction worth recording

Test 6 first asserted **`incl. €58.65 VAT`** — the figure from the audit's **demo property**. The test fixture
prices at **€120.00/night**, so its real VAT is **€82.80**. The test failed against correct code. *A real
figure taken from the wrong fixture fails exactly like a broken feature* — corrected to the fixture's own
number, with the reason recorded in the test.

---

## 6 · RENDER-CONFIRMED (§N.21.6)

| Surface | On screen |
|---|---|
| Card (`.captures/fix2-01-card-cue.png`) | `€85.00 / night · excl. VAT` · **`€373.65 total`** · incl. €58.65 VAT · €15.00 City tax · €45.00 Cleaning fee — ⚠️ **total unchanged to the cent**; second card `€180.00 / night · excl. VAT` · €724.20 |
| Guest 404 (`fix2-02-guest-footer.png`) | *"Back to your trips"* + *"reply to your booking confirmation email…"* |
| Guest 403 (`fix2-04-guest-403-footer.png`) | the F5 body **and** the A1 footer, both guest-framed |
| App 404 (`fix2-03-app-footer.png`) · public 404 | ⚠️ **operator line unchanged** |

⚠️ **The first guest probe proved nothing and was re-run.** `/guest/…` requires auth, so an anonymous request
**redirected to login (200)** — not an error page. Only after signing in does the guest realm actually render
an error page. *A redirect is not a render.*

---

## 7 · DIDN'T OVER-REACH · NO REGRESSION

| Guard | Result |
|---|---|
| Numbers changed | ✅ **none** — copy only |
| Money as-seeded | ✅ **byte-identical** — 929 / 10,912,000 / 0 / 41 / 16 / 0 / 40 |
| Rate engine · materialisation (D4-1) | ✅ untouched |
| F2 enumeration · F5 403 body | ✅ preserved (pinned by tests 2 and 4) |
| A2 · reprice · migration | ✅ **not done** — money-path pass |
| `book()` / reserve | ✅ not exercised — still UNVERIFIED-WITHOUT-MUTATION |
| Other five error-view **bodies** · add-ons pricing · ratified refusals · owner/operator · deploy gate | ✅ untouched |
| Regression | ✅ **52 tests / 345 assertions** green across the touched suites |
| PHPStan L5 · Pint | ✅ **0 errors** · clean |

**⚠️ The guest fix pass is now closed: F2 ✅ · F5 ✅ · F3 ✅ (ratified + cue) · A1 ✅ · A2 open (money-path).**
