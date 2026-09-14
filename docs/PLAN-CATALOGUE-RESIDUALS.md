# The plan catalogue's three residuals

**2026-09-04.** Three items in one money path, reported rather than fixed across four earlier passes.

## (23) A draft €0 plan claimed its gross was FINAL

**Is a €0 plan legitimate?** The product has **already decided it is not sellable**:
`PlanIntegrity::blockedFromActivation()` refuses a flat plan whose price is not above zero — *"A flat plan
needs a price above zero before it can be sold."* **There is no free tier here.**

**So the defect was not the zero — it was the confidence.** `tax_known: true` asserts *"this gross is
FINAL"*, and a plan whose price nobody has filled in has no final anything. `fromCaptured([])` was made to
REFUSE because it answered from nothing; this answered from the catalogue's own number, so the figure was
never invented — only the certainty was.

**The fix keeps the zero and drops the claim.** `charge()` now returns `tax_known: $priceMinor > 0`. The
gross is still €0; refusing outright would strand a plan mid-creation *and* would forbid a free tier if one
were ever wanted. And because it lives in the ONE calculation, it covers captured terms too — a snapshot
carrying `terms_price_minor: 0` no longer claims finality either.

**What a draft can reach — it was largely latent, and that is a real finding.** `MarketingPricing::tiers()`
filters `is_active`, so a draft never reaches `/pricing`. `update()` re-checks `blockedFromActivation()` on
the fresh model, so a €0 plan cannot be active. ⚠️ **But `trialPlan()` and `planFor()` do NOT filter
`is_active`** — so an org provisioned onto a zeroed trial-tier plan would see its billing page state
**€0.00 as settled and final**, with no "tax calculated at checkout" caveat. Reachable, never triggered:
the four seeded plans are €49.00 / €490.00 / €99.00 / €990.00.

## (25) A derived-key collision silently dropped a plan — measured

`tierKey()` strips the interval suffix, so `essential` and `essential_monthly` both derive `essential`, and
`code` is a free `[a-z0-9_]+` — **the admin panel can produce the collision.** A unique code is not a
unique tier.

⚠️ **Measured, not reasoned.** With a colliding `essential` plan added beside the four seeded ones:

```
active plans: 5   →   cards rendered: 2
card 'essential' carried code 'essential'  (the collider, €12.34)
```

The real €49.00 `essential_monthly` was **replaced on `/pricing`, where money is decided**. The `??=` in
`tiersWithCycles()` is not the defect and was not changed — the pairing pass chose it so a collision could
not corrupt a card mid-render, which turned a corruption into a disappearance. This stops the collision
existing, which is the layer it belongs to.

**Validator, not index — argued.** A database unique index would cover seeders and direct writes, which a
validator cannot. But `tierKey()` is PHP logic over a PHP constant (`CYCLES`); expressing it in SQL means a
**second definition of the derivation**, and two definitions of one concept is the drift this codebase keeps
removing — the failure would be invisible because both would look right alone. A generated column would
need that same SQL. So: a validator, with its limit stated rather than hidden.

⚠️ **The derivation itself is the real defect; this rule is a guard on it.** A tier is an identity and
should be a **column**, not something inferred from the spelling of a code — `my_pro_monthly` derives
`my_pro`, a tier nobody declared. Making `tier` real is a migration plus a backfill plus every reader, and
is deliberately **not** this pass.

**Existing rows satisfy it** — checked before writing: 4 live rows, 4 distinct `(tierKey, interval)` pairs.

## (26) Delete — the RECOMMENDATION is that plans should NOT be deletable

**No delete path exists anywhere.** Six admin routes (index, create, store, edit, update, toggle) and no
`destroy`; no `->delete()` or `->forceDelete()` on a plan in `app/` or `database/`; no console command; the
factory only creates. **The absence of a button really is the absence of the path** — the only way to trash
one is a manual `->delete()` in tinker or a raw write to `deleted_at`.

**`toggle` already covers every legitimate need**: reversible, audited, honoured by billing and marketing,
and refused for a plan that is not sellable.

**Deleting would be worse than useless.** A trashed plan kept selling until the soft-delete scope fix, and
deleting a plan with subscribers 404s their billing page. If one were ever added it would need a **restore
beside it** — a plan is a money object and this project records irreversible actions rather than
discovering them later.

**Nothing was built.** A test holds the recommendation so a future delete is a conversation rather than a
silent addition. **The decision is the founder's.**

## Every plan writer, and what this rule covers

| writer | covered? |
|---|---|
| `Admin\PlanController::store` | ✅ shares `PlanIntegrity::rules()` |
| `Admin\PlanController::update` | ✅ same ruleset (§N.8f — one ruleset, both paths) |
| `BillingService::ensurePlans()` (seeder path) | ❌ — writes only `{tier}_{interval}` codes, which cannot collide |
| `DemoSeeder` / migrations | ❌ — same safe shape |
| `PlanFactory` + direct writes in tests | ❌ — test-only |
| the admin "Shape" select | presentation only |

**What it does to a row that already violates it:** nothing — the rule ignores the row being edited
(`whereKeyNot`), so an existing plan can always still be saved. Proven for **every** live plan.

## Proof — each residual independently, both halves each

- **(23)** a €0 plan yields `tax_known: false` **and** a €49 plan still yields `true`; the captured-terms
  path too; and the pre-existing "cannot be sold" guard is unchanged.
- **(25)** a colliding code is refused **and** every existing code is still accepted when editing itself; a
  genuinely new tier is accepted; the same tier in a **free** cycle is accepted; and with the rule in force
  every active plan renders.
- **(26)** no DELETE route, no destroy method, no delete call.
- Plus: create · edit · toggle driven through the **real admin routes**, and `/pricing` rendering with a
  third single-interval tier — the shape that once 500'd it — showing an honest absent cycle.

### Mutations — every one landed

| mutation | landed | result |
|---|---|---|
| (23) **wrong fix** — never claim finality, even for a real price | ✅ | **RED** |
| (23) revert — a €0 draft claims finality again | ✅ | **RED** |
| (25) **wrong fix** — make the rule interval-blind | ✅ | ⚠️ **GREEN first, then RED** |
| (25) drop the self-ignore — a plan refused by its own row | ✅ | **RED** |
| (25) remove the rule entirely | ✅ | **RED** |

⚠️ **The interval-blind mutation landed and came back GREEN — a gap, not a result.** The "same tier,
different cycle" probe had validated a tier **nothing occupied**, so it passed whether or not the rule
considered the interval at all. Rewritten to occupy the tier in exactly one cycle, it goes RED. The mutation
found the only real weakness in this pass's own tests.

## ⚠️ Where this brief was wrong

It said the collision means *"one card takes another's feature list"*. Both halves are true but the visible
effect is larger: the colliding plan **took the card entirely** — the real plan's code and price vanished
from `/pricing`, not just its features. And it framed (23) as possibly needing €0 refused; the product had
already refused to *sell* €0, so the honest fix was narrower than refusing and forbids nothing.

## ⚠️ What I cannot prove

That no seeder or migration will ever write a colliding pair — a validator cannot reach them, and the
durable answer is a `tier` column rather than a derived key. Recorded, not built.
