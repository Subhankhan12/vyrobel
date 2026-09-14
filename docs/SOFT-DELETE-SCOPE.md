# `withoutGlobalScopes()` also drops soft-deletion — the sweep, and what it was hiding

**2026-09-03.** Second catalogued sighting of one mechanism. Twelve call sites; the class is now closed.

---

## 0 · ⚠️⚠️ THE FINDING THAT OUTRANKS THE PRICING PAGE — reported, NOT fixed

**A live subscriber's billing page 404s if their plan is soft-deleted.** Driven, not reasoned: signed in as
a `Property Owner` on the demo org, same session throughout —

| plan state | `/app/billing` |
|---|---|
| intact | **200**, shows "Essential" |
| soft-deleted | **404** |
| restored | **200** |

⚠️ **Controlled for the actor.** `/app` returned 404 in *both* states for this user (no property access), so
that one is the probe's own setup, not the plan — checked before drawing any conclusion.

**Root cause — `App\Services\BillingService`:**

```php
// line 181, and the comment beside it:
//   "ensurePlans() above guarantees both interval rows exist for every config tier."
$monthly = Plan::query()->where('code', $code.'_monthly')->firstOrFail();
$yearly  = Plan::query()->where('code', $code.'_yearly')->firstOrFail();
```

**That guarantee is false.** `ensurePlans()` reseeds only when `Plan::query()->count() === 0`; with one plan
soft-deleted and three alive the count is 3, nothing is reseeded, and `firstOrFail()` throws
`ModelNotFoundException` → 404. `trialPlan()` (line 157) has the same shape.

**⚠️ Note the direction of the pair.** These queries are *correct* — `Plan::query()` respects soft-deletion.
The defect was that `/pricing` **advertised** a plan `/app/billing` **could not find**. One surface saw
trashed rows, the other did not, and they disagreed about the same catalogue.

**What survives a deleted plan** (each tested):

| | result |
|---|---|
| `Subscription` resolves | ✅ yes |
| `$subscription->plan` | ❌ **NULL** — the `belongsTo` respects soft-deletion |
| `planAllows()` | ✅ correct — it queries `plan_features` by `plan_id`, never the `Plan` model |
| `Subscription::terms()` / `chargeFor()` | ✅ — prefers **captured** terms; all 1 subscriptions have them |
| `/app/settings` | ✅ 200 |
| `/app/billing` | ❌ **404** |

⚠️ **Two latent money consequences, reported:** (a) if `terms_captured_at` were ever NULL, `terms()` falls
back to the plan → null → `PlanTerms::fromCaptured([])`, which returns a **confident €0 charge with
`tax_known: true`**; today **0 of 1** subscriptions are uncaptured, so this is latent, not live.
(b) `BillingService::syncSubscriptionQuantity()` returns `null` when the plan is null, so a **per-unit**
subscription would silently stop syncing its quantity to Stripe.

**Not fixed here** — `BillingService` is not a `withoutGlobalScopes` site, and this pass's scope was the
scope class. It is the first thing to take next.

---

## 1 · ⚠️ Correction to the brief: there is no admin delete path for plans

The brief's premise — *"reachable from the admin panel by an ordinary action"* — **does not hold.**

```
GET|HEAD admin/plans            admin.plans.index
POST     admin/plans            admin.plans.store
GET|HEAD admin/plans/create     admin.plans.create
PUT      admin/plans/{plan}     admin.plans.update
GET|HEAD admin/plans/{plan}/edit
POST     admin/plans/{plan}/toggle
```

**No `DELETE` route, no `destroy()` method, no delete control in the views, and no `app/` code that deletes
a Plan.** `Admin\PlanController` cannot delete one. A plan can only be soft-deleted from tinker or by code
not yet written.

**So the answers to "what does the admin think delete does" and "is there a restore path":** the admin
cannot delete, so they are told nothing and there is nothing to restore. What they *have* is **`toggle`**,
which flips `is_active` — reversible, and correctly honoured by `tiers()`.

⚠️ **This makes the Plan instance LATENT, not live** — a trap set for the first person to add a delete
button. The mechanism is real (proved below); its trigger does not exist yet.

**Reported, not added** (`PlanIntegrity` is a money path — its own decision):
- If a delete action is ever added, a plan **with subscribers must not be deletable** — deleting one 404s
  their billing page today.
- **Deactivate and delete are not interchangeable**, and nothing would tell an admin which they used:
  `toggle` is reversible, honoured everywhere, and safe; a soft delete is honoured by billing, ignored by
  marketing (before this fix), and has no restore path. If a delete action is added it needs a restore
  action beside it — or it should not exist for plans at all, which is the stronger answer.

---

## 2 · The sweep — every call site, and which soft-delete

`withoutGlobalScopes()` **with no arguments removes every global scope, and the framework registers
soft-deletion as one.** Nobody reading it thinks "and trashed rows too" — they think "across tenants".

**Counts before this pass:** **12** bare-plural sites in `app/` · **0** using the named-array form ·
**11** named-singular `withoutGlobalScope(X)` (never the trap) · **156** more in `tests/`.

**Global scopes actually on each model** — measured with `getGlobalScopes()`, not assumed:

| model | scopes |
|---|---|
| **`Plan`** | **`SoftDeletingScope` only** |
| **`Organization`** | **`SoftDeletingScope` only** |
| `Subscription` · `Property` · `RoomType` | `OrganizationScope` + `SoftDeletingScope` |

⚠️ **That is the whole story for half the sites.** On `Plan` and `Organization` the bare call was escaping a
tenant scope **that does not exist on those models** — so removing the soft-delete filter was its *only*
effect. Six of the twelve were pure loss.

| # | site | model | verdict | fix |
|---|---|---|---|---|
| 1 | `MarketingPricing:81` | Plan | ❌ advertised deleted plans | `Plan::query()` |
| 2 | `StorefrontSitemapController:111` | Organization | ❌ no tenant scope to escape | `Organization::query()` |
| 3 | `BookingEngineController:553` | Organization | ❌ resolves the org to pick a **Connect account to charge** | `query()` |
| 4 | `GuestFolioPaymentController:60` | Organization | ❌ same — a guest card charge | `query()` |
| 5 | `FolioService:231` | Organization | ❌ same — a folio charge | `query()` |
| 6 | `OperatorConnectController:31` | Organization | ❌ Connect onboarding | `query()` |
| 7 | `Organization:84` | Subscription | ✅ wants to cross tenants | `[OrganizationScope::class]` |
| 8 | `PlatformCustomers:125` | Subscription | ✅ platform-wide read | `[OrganizationScope::class]` |
| 9 | `Admin\CustomerController:111` | Subscription | ✅ platform-wide write | `[OrganizationScope::class]` |
| 10 | `StorefrontSitemapController:77` | Property | ✅ public, cross-tenant | `[OrganizationScope::class]` |
| 11 | `OwnerRateService:152` | RoomType | ✅ owner scope checked separately above | `[OrganizationScope::class]` |
| 12 | `TenantProvisioningService:114` | Property | ⚠️ **genuinely wants trashed rows** | `[OrganizationScope::class]->withTrashed()` |

⚠️ **Sites 3–6 charge money.** Each resolves an organisation purely to obtain its Stripe connected account.
Including archived organisations there was never intended — and since **no `app/` code soft-deletes an
organisation**, the change is behaviourally inert today and removes a latent one tomorrow.

⚠️ **Site 12 is the one legitimate case, and it is now explicit.** `properties.slug` carries a **UNIQUE
index** that a soft-deleted row still occupies, so the uniqueness probe *must* see trashed rows — otherwise
provisioning picks a slug the INSERT then rejects, and an archived property's public URL would be handed to
a different property. It now says `->withTrashed()` **out loud** instead of receiving it as a side effect.

**Result: zero bare-plural sites in `app/`, and the guard's allow-list is EMPTY.** An intent expressed is
not an exception to be listed.

---

## 3 · Why named scopes and not `whereNull('deleted_at')`

A trailing filter would work and is the wrong shape: it re-implements a scope the framework already
applies, so the next caller who copies the pattern inherits the defect, and `withTrashed()` /
`onlyTrashed()` would then silently stop working against it. Naming the scope leaves soft-deletion where it
belongs.

⚠️ **Not extracted into a helper**, deliberately. Five sites now share
`withoutGlobalScopes([OrganizationScope::class])`; wrapping that in an `acrossOrganizations()` macro would
hide the one class name a reader needs to see, and the §N.8f concern is duplicated *logic*, not a repeated
symbol. A local scope on `BelongsToOrganization` is worth considering on its own merits — reported.

---

## 4 · ⚠️ Three instrument errors of my own, in one pass

Recorded because each produced a *confident wrong answer*, and two of them I published mid-pass.

1. **`Cache::remember`.** The storefront sitemap caches its document. My first "proof" that an archived
   property stays listed was a **stale cached copy**. I stated it as proven. It was not.
2. **`git stash` reverts to HEAD, not to the start of the pass.** Stashing `MarketingPricing.php` to compare
   with/without the fix also removed the *previous* pass's `tiersWithCycles()`, so the page **fatalled** and
   showed zero — which reads exactly like "the fix isn't needed". ⚠️ *If a run returns EMPTY, suspect a fatal.*
3. **⚠️⚠️ `php artisan serve` served a STALE `app/` class.** After editing a class on disk, HTTP requests to
   the running server kept executing the old code, so an A/B mutation looked identical in both directions.
   Blade edits *did* take effect (recompiled on mtime), which is exactly why this went unnoticed.
   **An HTTP probe against a long-running dev server is not a valid instrument for an `app/` source
   mutation.** The defect was settled by calling the class directly:

   ```
   plan soft-deleted; MarketingPricing::tiers('monthly') =
     with    Plan::query()               → ["pro_monthly"]
     without Plan::withoutGlobalScopes() → ["essential_monthly","pro_monthly"]   ← the deleted plan
   ```

---

## 5 · What is guarded

**`tests/Feature/SoftDeleteScopeGuardTest.php`**

- **The class guard** — no bare `withoutGlobalScopes()` anywhere in `app/`. ⚠️ **Tokenised with
  `token_get_all()`, not grepped**: the comment explaining why the bare form is wrong *contains the bare
  form*, so a grep-shaped guard fires on its own documentation — the ninth instance of that trap here.
  Allow-list: **empty**.
- **A non-vacuity test** driving the same scanner over a file that definitely contains the needle, proving
  it discriminates comment from code rather than simply seeing nothing.
- **The instance** — a soft-deleted plan is absent from the served `/pricing`, *and* the tier keeps its card
  because its yearly plan is still sold (stopping the advertisement must not drop the tier).
- ⚠️ **No sitemap instance test, and the absence is recorded in the file.** Whether an archived property
  would reach the served document is **unproven** — see §4 — and proving it needs `StorefrontSeoTest`'s
  `seoSellableTenant()` fixture, which would make the guard pass in company and fail alone. The class guard
  covers the call site; the behavioural gap is named, not papered over.

**`tests/Feature/MarketingRegenerationGuardTest.php`** — the two the last pass found **unguarded**:

- **`viewBox`** — no `<svg>` in the marketing views ships without one, plus a **vacuity floor of 185**
  (a "no icon lacks a viewBox" assertion passes perfectly against a site with no icons).
- **`plan_features`** — flipping Pro's real `channels` gate OFF must remove "Channel manager" from the card
  **while `config/billing.php` still says enabled**, which is what tells the two sources apart. Plus the
  fallback: a plan with no `plan_features` rows must still render from the catalogue, so the obvious "fix"
  for the first assertion cannot silently break the second.

**Mutations** — each applied, confirmed landed in its own block, run, reverted:

| mutation | |
|---|---|
| `Plan::query()` → bare form | **RED (2)** — class guard *and* instance |
| a *different* site → bare form (`OwnerRateService`) | **RED (1)** — class guard alone, which is its point |
| a `viewBox` stripped, as a regeneration would | **RED (1)** |
| features read from the config again | **RED (1)** |
