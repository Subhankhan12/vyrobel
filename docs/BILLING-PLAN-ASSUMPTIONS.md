# Billing assumed a plan row always resolves — the confident €0 and the 404

**2026-09-03.** Two defects, one root. Both are now refusals rather than answers.

---

## 1 · The confident zero — every path, tested

`PlanTerms::fromCaptured()` coerced six fields through `?? 0` / `?? 'EUR'`, so a snapshot holding **nothing**
produced `net 0 · gross 0 · tax_known: **true**`. Not the wrong number — an assertion that the number is
**certain**.

### Every caller of `tax_known`, and what `true` does

There are exactly **three** live sites, all on one surface:

| site | what `true` causes |
|---|---|
| `OperatorBilling:287` | passes `tax_known` into the view payload |
| `OperatorBilling:296` | the `tax_note` `match` — `true` skips *"tax calculated at checkout"* |
| `billing.blade.php:174` | prints the note **without a `+ ` prefix** — i.e. presents the figure as the **final total** |

⚠️ **Nothing skips a tax calculation, creates a Stripe line, or writes a ledger row on this flag.** It is a
*display* discriminator: `true` means **"this gross is final"**. That is what made the empty snapshot's
`true` a lie — it declared a total final when no total had been computed.

### Every path to an empty snapshot — measured, not reasoned

| path | reachable? | result |
|---|---|---|
| `plan_id` pointing at a **non-existent** plan | ❌ **impossible** — FK `subscriptions_plan_id_foreign` | — |
| subscription created against a **soft-deleted** plan | ✅ **yes** | `captured=NO · plan=NULL · net=0 · tax_known=true` |
| **raw `DB::table()->insert()`** (bypasses model events) | ✅ **yes** | `captured=NO · plan=NULL · net=0 · tax_known=true` |
| `fromCaptured([])` directly | ✅ | `net=0 · gross=0 · tax_known=true` |
| ⚠️ **`fromCaptured(PARTIAL — shape but no price)`** | ✅ **yes** | `net=0 · gross=0 · tax_known=true` |

⚠️ **The last row was not in the report and is the wider defect.** A capture carrying `terms_pricing_type`
but no amount takes the *captured* branch (`terms_captured_at` is non-null), so it never even reaches the
fallback — and coerced to a confident zero just the same.

⚠️ **The origin is a silent no-op.** `Subscription::fillCapturedTerms()` does
`Plan::query()->find($this->plan_id)` and then `if ($plan !== null)` — so when the plan does not resolve it
**captures nothing and says nothing**. The model's stated guarantee ("capture at the model, so no writer can
forget") holds against forgetful writers but not against an unresolvable plan.

### Could a real subscription reach it today?

**No — and the brief's stated trigger is not the real one.** It says the zero *"fires for every subscription
predating the terms-capture migration"*. Verified otherwise:

- the migration **backfills** every uncaptured row, and it reads `plans` with a **raw query**, so it sees
  soft-deleted rows too — a pre-migration row could not be skipped for that reason;
- a **foreign key** forbids a `plan_id` that does not exist at all;
- `Subscription::booted()` captures on `creating`, so every model-written row captures;
- measured: **0 of 1** subscriptions are uncaptured.

⚠️ **And both live consumers already guard.** `OperatorBilling::billingSummary()` (line 263) and
`OperatorSettings` (line 148) each return early when `$subscription->plan === null`, *before* calling
`terms()`. So the confident zero was **latent, not live**.

⚠️ **That inverts the brief's ranking, and it is worth stating plainly.** The €0 is the more dangerous
*shape* — a wrong number stated confidently — but it was unreachable through any current surface, while the
**404 was measured live** on a paying customer's billing page. Both are fixed; the ordering is recorded
because "worse in kind" and "reachable today" are different questions and this project keeps them apart.

---

## 2 · It REFUSES — and the rule is "must not answer", not "must not answer zero"

`fromCaptured()` now throws `MissingPlanTermsException` when the snapshot carries no pricing shape, or
carries a shape with no amount to bill on. `Subscription::terms()` throws when it has neither captured terms
nor a resolvable plan.

**Exception, not an "unknown" shape — argued against the callers.** An unknown-value object would have to be
*checked*, and the two consumers that exist already return early on the same condition, so the shape would
add a branch nobody needs while remaining ignorable by the next caller who forgets — which is the failure
being fixed. A throw cannot be ignored. And it is safe here: neither live consumer can reach it, and the one
job path (`SyncSubscriptionQuantityJob`) carries `$tries = 3`, `$backoff = [30, 120]` and a `failed()` that
logs, so a refusal retries and then announces itself.

⚠️ **The guard is narrower than "any missing field", deliberately.** Only the basis for *this* shape is
required: the migration's backfill writes the plan's own `NULL` into `terms_unit_rate_minor` for every flat
plan, so demanding both bases would refuse **every backfilled subscription in the database** — a "fix" that
breaks billing for everyone. There is a test for exactly that.

⚠️ **`tax_known: true` can no longer be emitted without terms behind it.** The constructor is private and
reachable only through `fromPlan()` (a real catalogue row) and `fromCaptured()` (which now refuses), so
every `true` rests on an amount somebody agreed to. ⚠️ One residual, reported: `fromPlan()` on a **draft**
plan with `price_minor = 0` would still yield `€0 · tax_known: true`. That is the catalogue's own number
rather than an invented one, and `PlanIntegrity` blocks such a plan from being *activated* — but nothing
stops a subscription referencing a draft. Not touched: `PlanIntegrity` is a money path with its own decision.

---

## 3 · The false comment, and the sweep for others

`BillingService:181` read *"ensurePlans() above guarantees both interval rows exist for every config tier."*
**It does not** — `ensurePlans()` reseeds only when `Plan::query()->count() === 0`, so with one row missing
and the others present nothing is reseeded. **Third sighting of this shape: a false comment on a money path.**

**Corrected to what the code does, and the guarantee was NOT made true instead.** Reseeding here would
recreate rows an administrator had removed, on the page that sells them — a **write with real consequences,
triggered by a read**. Tolerating the absence is the smaller claim: a tier whose plan rows do not resolve
genuinely cannot be bought, so it is not offered.

**Sweep for other comments asserting an unenforced invariant** — across `BillingService`, `PlanTerms` and
`Subscription`, matching *guarantee / always / never fails / cannot fail / ensures / must exist*: the only
remaining hit is this document's own quotation of the corrected comment. ⚠️ One softer case was found and
**corrected rather than reported**: `Subscription::terms()`'s docblock claimed the fallback would *"still
price correctly rather than return zero"* — returning zero with confidence is precisely what it did.

**Every other `firstOrFail()`/`findOrFail()` on a plan or subscription**, checked:

| site | verdict |
|---|---|
| `BillingService:157` `trialPlan()` | ⚠️ **fixed** — surfaced a missing trial tier as a **404**, which describes a page that does not exist rather than a catalogue that is not configured. It still fails (a trial cannot start without a plan, and provisioning is transactional) but now says why. |
| `BillingService:182/183` `catalog()` | ⚠️ **fixed** — the 404 above |
| `Admin\PlanController:103/111/160` | ✅ correct — a 404 for a plan id that is not in the panel's own list is the right answer |
| `RatePlan` / `BookingEngine` sites | ✅ different model (`rate_plans`), tenant-scoped, out of scope |

---

## 4 · Snapshot or live — the line drawn

**What the billing page needs from the plan, and who holds it:**

| needed | held by the snapshot? |
|---|---|
| price / unit rate / VAT % / currency / Stripe Price id | ✅ **yes** — these are the **terms** |
| pricing **shape** (flat vs per-unit) | ✅ yes (`terms_pricing_type`) |
| plan **name** | ❌ no |
| plan **code** (for the "switch to yearly" link) | ❌ no |
| **interval** | ❌ no |
| feature list | ❌ no — that is `plan_features`, keyed by `plan_id` |

**The line: a TERM is anything that decides what the customer pays; everything else is DISPLAY.** Terms must
come from the snapshot, because that is what stops an admin's edit changing an existing customer's bill —
and they already do. A **name is not a term**: reading it live is correct, and a renamed plan *should* show
its new name.

⚠️ **The snapshot semantics were NOT changed** — that remains the founder's open question. What changed is
that the page no longer *dies* when the live row is absent.

**What it shows when the plan is gone — measured, not read:** `billingSummary()` and the `plan` payload
both resolve to `null` (they already guarded), and the page renders the **"Choose a plan"** picker. The
strings `Essential` and `49.00` are **absent**. ⚠️ **That is survival, not
correctness**: the customer is still being charged on captured terms the system can read, and the page
presents them as having no plan. Making it render the captured terms under an honest "plan no longer in the
catalogue" label is a real improvement and a **display change beyond this pass's scope — reported**.

---

## 5 · The silent quantity stop

`syncSubscriptionQuantity()` returned `null` when the plan did not resolve. `null` there means *"nothing to
sync"* — **an answer**, and one we do not have: with the plan unresolvable we cannot tell whether the
subscription is per-unit, so a per-unit one would have stopped syncing its quantity to Stripe with **no
error, no log and no alert**, on a charging path.

**It now throws**, which the job path already supports (`$tries`/`$backoff`, and a `failed()` that logs).
No monitoring was added.

⚠️ **INERT TODAY: every seeded plan is `flat`** (`pricing_type = 'flat'`, `unit_rate_minor` NULL), so nothing
reaches this branch. It **arms the moment a per-unit plan exists** and its row stops resolving. Recorded
rather than fixed silently.

⚠️ **Reported, not done:** reading `isPerUnit` from the subscription's **captured terms** instead of the live
plan would remove this case entirely — and `Plan::quantityFor()` and `PlanTerms::quantity()` are already
byte-identical logic. That is the live-vs-snapshot question the F3 pass reported and left open; answering it
is the founder's, so the source of truth here is unchanged.

---

## 6 · How every claim was proven — no `artisan serve` anywhere

The dev server was shown last pass to keep executing a **stale copy** of an `app/` class, making a source
mutation look identical in both directions. Nothing in this pass touched it. **The server was not started.**

| claim | instrument |
|---|---|
| every path to an empty snapshot | `php artisan tinker` — a **fresh process**, inside a transaction, rolled back |
| the FK forbids a non-existent `plan_id` | the database refused the insert (`SQLSTATE[23000] … subscriptions_plan_id_foreign`) |
| `/app/billing` was 404 and is now 200 | a request **through the test kernel** (`actingAs` → `->get()->assertOk()`), booted per test |
| the refusals fire | direct calls to `PlanTerms` / `Subscription::chargeFor()` in the suite |
| every mutation | the suite, re-run per mutation, each landed in its own block |

---

## 7 · What is guarded

`tests/Feature/BillingMissingPlanTest.php` — 7 tests. Notably:

- **the refusal, not the zero** — `fromCaptured([])` must **throw**; a mutation returning a plausible
  **€49.00** instead must still be RED, because the rule is *must not answer*;
- the **partial** snapshot refuses too;
- a **real** flat snapshot with a null unit rate still prices — the guard must not be wider than the defect;
- `tax_known: true` is unreachable from termless input;
- `/app/billing` **renders** for a subscriber whose plan row was deleted;
- the quantity sync **refuses** rather than reporting "nothing to sync".

**Mutations** — each applied, confirmed landed in its own block, run, reverted:

| mutation | |
|---|---|
| the empty-snapshot refusal reverted (confident €0) | **RED (3)** |
| ⚠️ **the WRONG FIX** — a plausible **non-zero** instead of refusing | **RED (2)** |
| the catalogue's tolerance reverted (`firstOrFail`) | **RED (1)** |
| the quantity sync's refusal reverted (silent `null`) | **RED (1)** |
