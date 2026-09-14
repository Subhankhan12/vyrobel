# The 156 bare scope sites in `tests/` — classified, and the two that were lying

**2026-09-05.** ⚠️ **This is a classification, not a rewrite.** Of 156 sites, **5** were changed away from
the bare form and **4** kept it while gaining an explicit `trashed()` assertion. **147 were left exactly as
they are**, because a test that deliberately reads across tenants is doing its job.

---

## ⚠️⚠️ At the top, as required: NO certified test went red, and NO application code changed

Nothing was amended, nothing was weakened, and no behaviour underneath turned out to be wrong. Everything
below strengthened an assertion that was demonstrably not testing what its name claimed.

---

## 1 · The classification

**Counted by tokenising, never grepping** — the docs and guards explaining this trap contain the trap.

| | count | |
|---|---:|---|
| bare `withoutGlobalScopes()` in `tests/` | **156** | ⚠️ the brief's figure is exactly right |
| …in `app/` | **0** | the earlier sweep holds; allow-list still empty |

⚠️ **The brief named five soft-deleting models (`Plan`, `Organization`, `Subscription`, `Property`,
`RoomType`). There are 43** — measured with `getGlobalScopes()` across `app/Models`. That table in
`SOFT-DELETE-SCOPE.md` listed only the models that happened to appear at the app's twelve sites, and reading
it as the full list would have understated the exposure by a factor of eight. ⚠️ **`User` is one of the 43**,
which is exactly the "refuses to remove the last admin" family.

| class | sites | what it means |
|---|---:|---|
| **INERT — model never soft-deletes** | **59** | `OwnerLedgerEntry`, `OwnerPayoutPreference`, `Review`, `GuestWaitlistEntry`, … No `SoftDeletingScope` exists to drop. **Cannot ever be wrong.** |
| **INERT — nothing is trashed in that test** | **52** | On a soft-deleting model, but no removal happens anywhere in the file. ⚠️ **Safe today, armed tomorrow** — the day someone adds a `->delete()` these become live. |
| **LEGITIMATE — says `trashed()` out loud** | **5** | Already the correct form, e.g. `OwnerUser::withoutGlobalScopes()->withTrashed()->find(…)->trashed()`. ⚠️ **A test asserting a row WAS trashed must see trashed rows — changing these would break them.** |
| **SAFE — no assertion the scope can affect** | **17** | Fixture loads and reads whose result is compared on a field, not on existence. |
| **SUSPECT — read individually** | **23** | Below. |

⚠️ **"Safe" and "inert" are different findings and are kept apart.** 59 sites cannot be wrong; 52 are merely
not wrong *yet*.

---

## 2 · The 23 suspects, each checked

The dangerous shape is narrow: **an assertion that something is gone, absent, refused or counted, where a
trashed row being visible decides the outcome.**

⚠️⚠️ **And the direction that matters is counter-intuitive.** For a `count() === 0` refusal the bare call is
**stricter**, not weaker — a trashed row is *counted*, so a "wrote it then soft-deleted it" bug is caught
where a scoped count would miss it. **Two of those were confirmed positively by planting**, not assumed:

| site | planted | result |
|---|---|---|
| `PayoutDestinationRequiresReauthTest:58` — *"refuses to add a payout account…"* | a trashed `PayoutMethod` named `Attacker` | **RED — it bites. Safe, and stronger for being bare.** |
| `ArchivedPropertyGhostUnitsTest:158` — *"refuses to block dates…"* | a trashed `InventoryBlock` on the ghost unit | **RED — it bites. Safe.** |

**The genuinely dangerous polarity is the opposite one: proving SURVIVAL with `not->toBeNull()`.** A removed
row returns from `find()` exactly as a live one, so the assertion cannot tell a refusal from a removal.

---

## 3 · ⚠️⚠️ Two false greens, DEMONSTRATED — planted, passed, fixed, red

### The first sighting, found and read

`docs/AUDIT-2026-08-29.md:1278`:

> The first draft asserted the owner survived with `Owner::withoutGlobalScopes()->find(...)->not->toBeNull()`.
> **`withoutGlobalScopes()` drops the SoftDeletingScope too**, so that returns a REMOVED owner exactly as it
> returns a live one — the refusal tests would have passed against a removal that went through. **All four
> assertions now go through `trashed()`.**

⚠️ **It IS fixed** — `RemovalAndSuspensionRequireReauthTest:46` asserts `->trashed()`, with a docblock at
:39–42 naming the trap. ⚠️ **And note the fix kept the bare call**: you must *see* the row in order to assert
it is not trashed. **So the defect is not the call — it is the call plus an existence-only assertion.**

### ⚠️ Defect 1 — the sibling, in a file that already knew the answer

`TeamAccessRequiresReauthTest` applies the correct `withTrashed()->trashed()` form at lines **101 and 111**
for `OwnerUser` — **and missed it at 164 for `Owner`.** *"A defect once made is usually made twice in the
same file"*, and here the fix was made twice and skipped once.

```php
it('cannot remove or demote the primary owner, so the account can never be locked out', …)
    expect(Owner::withoutGlobalScopes()->find($c['ownerId'])->user_id)->not->toBeNull();
```

| | |
|---|---|
| **planted** a soft-delete of the primary owner, immediately after the refusal | **PASSED** ⚠️ |
| fixed to `withTrashed()->find(…)->trashed()->toBeFalse()` | green |
| **re-planted** against the fixed assertion | **RED** |

⚠️⚠️ **A test named "the account can never be locked out" passed with the account removed.**

### ⚠️ Defect 2 — the same shape, one non-cascade assertion over

`ArchivedPropertyDoesNotBillTest:86` — *"leaves every unit row exactly as it was"*, whose own comment reads
*"a soft-deleted property must not cascade"*.

| | |
|---|---|
| **planted** a soft-delete of **both** unit rows | **PASSED** ⚠️ |
| fixed to assert `trashed()->toBeFalse()` | green |
| **re-planted** | **RED** |

⚠️⚠️ **The test that exists to rule out a cascade could not detect a cascade.**

### ⚠️ Defect 3 — a vacuity floor satisfiable by deleted rows

`MarketingPriceSourceTest:245` — a floor whose stated purpose is *"PROVES the price guard reads real,
non-empty sources"*.

| | |
|---|---|
| **planted** every plan soft-deleted | **PASSED** ⚠️ — the floor counted trashed plans |
| fixed to `Plan::query()` | green |
| **re-planted** | **RED** |

⚠️ **And this one is also a source-agreement fix.** `MarketingPricing` was itself changed to `Plan::query()`
(`SOFT-DELETE-SCOPE.md` site 1) precisely so `/pricing` stops advertising deleted plans. A test reading a
**wider** set than the code it checks rebuilds the two-sources-drift hazard on the test side. ⚠️ **All four
occurrences in that file were changed, not the two the scan flagged** — the same "made twice" lesson,
applied to my own fix.

### Defect 4 — a refusal that could not see a removal

`OperatorControlGateTest:196` asserted only `status === 'pending'` after a forbidden approve. Planted a
soft-deleted request → **PASSED**; now also asserts `trashed()->toBeFalse()` → **RED** when re-planted.

### ⚠️ "I could not construct a failure" — a real result

`OwnerRemovalSparesTheLastRoleManagerTest:204` (`User::withoutGlobalScopes()->findOrFail(dana)` in the
runbook-recovery test) **looked** dangerous. Planting a trashed `dana` made it go **RED** — the downstream
`ormSignInAbleManagers($org)` assertion catches it. ⚠️ **It was not broken. Left exactly as it is.**

### Reasoned safe, not changed

`GoogleSignInTest:309/324` compares **before/after** counts, so trashed rows cancel — and a
create-then-soft-delete would be *caught* by the bare form, making it stronger. `PermissionEnforcementTest:220`
is a `count()===0` refusal (stricter, as above). The remaining suspects are fixture loads whose result is
compared on a field.

### ⚠️ Defect fix vs readability change, distinguished

- **Defect fixes** (a false green demonstrated by planting): `TeamAccessRequiresReauthTest`,
  `ArchivedPropertyDoesNotBillTest`, `MarketingPriceSourceTest`, `OperatorControlGateTest`.
- **Alignment only** (no false green demonstrated): `MarketingClaimsTest:257` → `Plan::query()`, so the test
  reads the same catalogue `/pricing` renders. ⚠️ **Said plainly: this one was not proven broken.**

---

## 4 · ⚠️ Is a guard honestly expressible? **Yes — but only the fourth rule tried**

Three candidates were **measured and rejected**, not argued away:

| candidate rule | hits | real defects | verdict |
|---|---:|---:|---|
| no bare call anywhere in `tests/` | 156 | 4 lines | ⚠️ needs ~152 allow-list entries — **a list nobody reads** |
| + soft-deleting model + asserts `toBeNull()` | 10 | 0 | over-fires: `toBeNull()` is the *stricter* polarity |
| + only `not->toBeNull()` | 4 | 0 | over-fires on tests asserting a row was **created** |
| **+ a REMOVAL attempted in the same test** | **4** | **4** | ✅ **exact** |

**`tests/Feature/TestSurvivalAssertionGuardTest.php`** implements the fourth:

> a bare `withoutGlobalScopes()` on a **soft-deleting model** · a test proving survival with
> `not->toBeNull()` · a **removal attempted** in that test · and `trashed()` never appearing.

⚠️ **Against the pre-fix text it flags exactly the four lines that bit, and nothing else out of 156. Against
the tree today it flags nothing. The allow-list is empty and has never had an entry.** The soft-deleting
model list is read from the models at runtime, so a model that *gains* soft-deletion is covered that day.

⚠️ **A non-vacuity test drives the same scanner over two fixtures** — the defect's shape (must fire) and the
correct form (must not) — because a guard that finds nothing looks identical to a guard that cannot find
anything.

⚠️⚠️ **AND IT READ ITS OWN NEEDLE ON THE FIRST RUN — the tenth instance of that trap here, hit while writing
the guard against it.** The probe fixtures were heredocs, and a heredoc is a **string**, which
comment-stripping does not remove. Fixed by **assembling** the call from fragments so the literal never
appears in the file — which is why the guard needs no self-exclusion.

---

## 5 · Mutations — each applied, landed, RED

| # | mutation | landed | guard | the test itself |
|---|---|---|---|---|
| **M1** | restore the bare survival assertion (primary owner) | ✅ | **RED** | ⚠️ **still passes** |
| **M2** | restore the bare non-cascade assertion (units) | ✅ | **RED** | ⚠️ **still passes** |
| **M3** | loosen the guard's removal condition | ✅ | **RED** | — |

⚠️⚠️ **M1 and M2 are the whole argument in one line: the test keeps passing while only the guard notices.**
That is what a false green looks like from the inside, and why the guard had to exist rather than the fix
alone.

Plus, per site fixed: **planted → passed → fixed → re-planted → RED**, four times.

---

## ⚠️ Where this brief was wrong

1. ⚠️⚠️ **"which soft-delete: `Plan`, `Organization`, `Subscription`, `Property`, `RoomType`"** — there are
   **43**, including `User`, `Owner`, `Unit`, `Reservation`, `Payment` and `Guest`. The five are the models
   that appeared at the *app's* twelve sites, not the population.
2. ⚠️ **"a test asserting a COUNT … will pass while seeing trashed rows"** — for the `count() === 0` refusals
   here the bare form is **stricter**, and both were confirmed by planting to bite correctly. The brief's
   own worst case, *"refuses to remove the last admin"*, is real — but as an **existence** assertion, not a
   count.
3. ✅ Correct and load-bearing: that most of the 156 are fine; that the work is separating them; that the
   first sighting is the template; and that a demonstrated false green beats an argued one.
