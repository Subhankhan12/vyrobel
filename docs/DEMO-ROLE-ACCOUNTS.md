# The roles nobody had ever signed in as — and a seeder that ran on staging

**2026-09-05.** Seven back-office roles gained a demo login. ⚠️ **But the larger finding is the seeder
guard, and it is first because it is a credential exposure, not a coverage gap.**

---

## 0 · ⚠️⚠️ AT THE TOP — the existing demo accounts WERE seedable outside production

`DatabaseSeeder` and `PlatformAdminSeeder` both refused when `app()->isProduction()`. **That is a deny-list
against ONE EXACT STRING, so it failed open everywhere else.** Driven against a scratch database, not
reasoned:

```
APP_ENV=staging php artisan db:seed --class=PlatformAdminSeeder --force
  → platform_admins seeded: 1
  → admin@vyrobel-demo.test
  → Hash::check('password', …) = TRUE
```

⚠️⚠️ **A platform super-admin that sits ABOVE tenancy, with a password published in this repository,
created in a `staging` environment.** `uat`, `qa`, `demo`, `preprod` and a typo such as `producton` all do
the same. And `--class=` **never runs `DatabaseSeeder`**, so a guard living only there is no guard at all
for that command — which is exactly how this was demonstrated.

### The fix — an allow-list, the shape `StripeSafetyGuard` already uses

`StripeSafetyGuard` permits a LIVE Stripe key in `production` **and nowhere else** — an allow-list of one,
so an unrecognised environment gets the safe answer. `App\Support\Demo\DemoDataSafetyGuard` applies the same
inversion to credentials: demo data is permitted in `local` and `testing` **and nowhere else**.

⚠️ **The anti-model is `FakeChannexClient`**, bound by a config default of `true`. Failing open to the fake
is right for a channel manager, where the worst case is no sync. Here the worst case is a login.

⚠️ **There is deliberately no escape hatch.** An `ALLOW_DEMO_SEED=1` override would be the first thing
copied into a server `.env` to "just get the demo up". Seeding demo data somewhere new is a code change.

**Guarded in all three credential-creating seeders** — `DatabaseSeeder`, `PlatformAdminSeeder`,
`DemoSeeder` — because each is reachable directly via `--class=`.

**Re-run after the fix, same command, same scratch database: `platform_admins = 0`, with an explicit
refusal naming the environment.**

---

## 1 · ⚠️ The re-derived list — **nine** unrepresented, not eight, and **seven** need accounts

⚠️ **The brief said eight of fourteen. Measured: five roles had accounts, so nine had none.** Two of those
nine must not get one, which is why the number to seed is **seven**.

| had an account | did not |
|---|---|
| Property Owner · Hotel Manager · Accountant · Housekeeping · External Owner | Group Admin · Revenue Manager · **Front Desk** · Reservation Agent · Maintenance · Marketing Manager · Guest Support Agent · *Super Admin* · *Guest* |

⚠️ **`Super Admin` and `Guest` are deliberately unseeded, and neither is in `back_office_roles`:**

- **`Super Admin`** is a GLOBAL, cross-tenant role. Phase 7a explicitly stopped assigning it to a founder
  (RBAC defect #2), and the platform realm is `PlatformAdmin` — a different table and guard, already seeded
  as `admin@vyrobel-demo.test`. Giving a `User` this role would rebuild the credential that was removed.
- **`Guest`** grants `guest_portal.view`, and `/guest` authenticates against the `Guest` model on its own
  guard. **A `User` holding it reaches nothing — vestigial for this realm.** Reported, not seeded.

### What each new role is for

| role | account | what the person does all day |
|---|---|---|
| **Front Desk** | `demo-frontdesk@` (Marta Silva) | The primary daily role: takes bookings, assigns rooms, checks in and out, takes payment at the desk, issues the receipt. **Cannot cancel.** |
| **Group Admin** | `demo-groupadmin@` (Inês Carvalho) | Runs the organisation. 44 permissions — the only seeded role besides Property Owner holding `users.manage`. |
| **Revenue Manager** | `demo-revenue@` (Tomás Ribeiro) | Prices and availability: rates, restrictions, reports. **Holds no guest permission at all.** |
| **Reservation Agent** | `demo-agent@` (Sofia Almeida) | Takes and **cancels** bookings by phone. Cancels where Front Desk cannot; takes no payment. |
| **Maintenance** | `demo-maintenance@` (Paulo Nunes) | Work orders and the units they attach to. No money, no reservations. |
| **Marketing Manager** | `demo-marketing@` (Beatriz Lopes) | Corporate accounts and reviews. Reads guests; holds **no reservation permission**. |
| **Guest Support Agent** | `demo-support@` (Miguel Costa) | Answers guests after the fact: reads bookings and folios, replies to reviews. Read-only on money. |

All use the demo password `password`.

### ⚠️ No role is a duplicate — measured, not assumed

Comparing every non-wildcard permission set pairwise: **no two roles are identical.** Every one is a strict
subset of `Group Admin`, and all but `Accountant` are subsets of `Hotel Manager`. It is a clean ladder.

⚠️⚠️ **And that ladder is precisely why this gap mattered.** The four represented roles were the **top**
(Property Owner, Hotel Manager) and the **bottom** (Housekeeping) — and **`Front Desk ⊂ Hotel Manager`**, so
auditing as Hotel Manager exercises everything Front Desk *may* do and **nothing Front Desk is denied.** The
middle of the ladder had never been driven.

---

## 2 · ⚠️⚠️ `model_has_roles` moves: **7 → 14**

**Say it once, clearly: the new pinned value is `model_has_roles = 14`.**

It is **+7** — one assignment per new account, nothing else. **Every other figure is unmoved**, verified
after a fresh seed:

| | before | after | |
|---|---:|---:|---|
| `model_has_roles` | 7 | **14** | ⚠️ **+7, the seven new accounts** |
| `role_has_permissions` | 260 | 260 | unmoved |
| `permissions` | 51 | 51 | unmoved |
| `roles` | 14 | 14 | unmoved |
| `plans` · `plan_features` | 4 · 12 | 4 · 12 | unmoved |
| `owner_ledger` rows / sum | 29 / 730640 | **29 / 730640** | unmoved |

⚠️ **No new account owns anything that earns** — no `Owner` record, no unit, no payout method, no ledger
line. The money gate is untouched by their existence, and that is asserted rather than hoped.

---

## 3 · ⚠️ Front Desk used, not merely seeded

### The 19 ungated screens, re-measured as Front Desk

⚠️ **The count does not change with the role — and that is the finding.** Front Desk reads **all 15
parameterless ungated screens at 200**, exactly as Housekeeping did.

**These routes carry no permission at all**, so there is nothing for a wider role to unlock and nothing for
a narrower one to lose. Measuring the census as Housekeeping was **not** the limitation it appeared to be.
Reads open, writes gated — Front Desk genuinely lacks `reservations.cancel`, and the route returns **403**.

### ⚠️ Can Front Desk do its own job? **Yes — nothing blocks it**

`reservations.create` · `reservations.checkin` · `reservations.checkout` · `payments.record` ·
`folios.manage` · `invoices.issue` — all held. And checked against the **real routes**, not the catalogue,
because the permission pass warned that the matrix can promise access the route refuses: `POST
app/reservations/{r}/payments` does **not** answer 403 for Front Desk, while `POST .../cancel` does.

**No permission was granted, wired or removed.**

### ⚠️ A new finding for the outstanding rulings: `/owner` answers 200 to operators

Measured as Front Desk and Group Admin:

| | |
|---|---|
| `/owner`, `/owner/payouts`, `/owner/balance`, `/owner/settings` | **200** |
| owner name in the body · IBAN in the body | **no · no** |
| `/owner/statement` · `/owner/team` | 404 · 403 |
| Front Desk holds `owner_portal.view` | **false** |

⚠️ **Nothing leaks** — `SetOwnerScopeForPortal` resolves to no owner, so every query returns nothing and the
page is an empty shell. ⚠️ **But the protection is that the data is not there, not that the door is shut**,
and `owner_portal.view` — the permission that exists for exactly this — gates none of those routes.
**Evidence for phantom-permission ruling #2; deliberately not gated here.**

---

## 4 · ⚠️ The team is set on every account

`can()` returns **false** with no team, and that shape has bitten three times. Each new account gets an
active `Membership` and `syncRoles` inside the org context — the same path as the four that existed. Every
probe asserts the identity **and** that the team is set before believing any answer, and every account is
proven in **both directions**.

| account | signs in | team | may | may not |
|---|---|---|---|---|
| `demo-frontdesk@` | ✅ | SET | `reservations.checkin` ✅ | `reservations.cancel` ❌ |
| `demo-groupadmin@` | ✅ | SET | `users.manage` ✅ | `owner_portal.view` ❌ |
| `demo-revenue@` | ✅ | SET | `rates.manage` ✅ | `guests.view` ❌ |
| `demo-agent@` | ✅ | SET | `reservations.cancel` ✅ | `payments.record` ❌ |
| `demo-maintenance@` | ✅ | SET | `maintenance.manage` ✅ | `reservations.view` ❌ |
| `demo-marketing@` | ✅ | SET | `reviews.manage` ✅ | `reservations.view` ❌ |
| `demo-support@` | ✅ | SET | `reviews.manage` ✅ | `reservations.create` ❌ |

All five pre-existing accounts still sign in.

---

## 5 · The seed is repeatable

`migrate:fresh --seed` run **twice**, thirteen counts compared: **identical**, including
`owner_ledger` 29/730640 and `reservations` 42. Storefront figures are date-relative and **recorded, never
pinned**.

---

## 6 · ⚠️ A class guard, so this cannot recur

Seven roles went unrepresented for the life of the project and **no test noticed**, because nothing asserted
the relationship between the catalogue and the seeder. A test now fails if any `back_office_roles` entry has
no demo account — add a fifteenth role tomorrow and it reds until it gets a login. A second test pins that
`Super Admin` and `Guest` are outside `back_office_roles`, so their absence is asserted as deliberate rather
than written as a comment.

---

## 7 · Mutations — six, each applied, landed, RED

| # | mutation | landed | |
|---|---|---|---|
| **M1** | ⚠️ **the wrong fix** — restore the deny-list (`!== 'production'`) | ✅ | **RED** |
| **M2** | remove the refusal entirely | ✅ | **RED** |
| **M3** | seed an account without its role (drop Front Desk) | ✅ | **RED** |
| **M4** | drop the team from the probe actor (the silently-false `can()`) | ✅ | **RED** |
| **M5** | un-guard the `--class=` path (`PlatformAdminSeeder`) | ✅ | **RED** |
| **M6** | run the Front-Desk job test as Hotel Manager (which *has* cancel) | ✅ | **RED** |

M1 re-run against the final code → RED; restored byte-identical.

### ⚠️ And one of my own tests was VACUOUS — caught, recorded, fixed

The "Front Desk can do its own job" route checks read `$s['reservations'][0] ?? null` and guarded both
assertions with `if ($reservation !== null)`. **`seedScenario()` returns no `reservations` key**, so the
value was always null and **neither assertion ever ran** — the test passed while checking nothing. It now
makes a real booking with `book($s)` and asserts unconditionally. **M6 exists to prove those assertions
bite.**

---

## ⚠️ Where this brief was wrong

1. **"eight of fourteen roles have no demo account"** — **nine** had none (five were represented, not six),
   and **seven** should get one: `Super Admin` and `Guest` must not.
2. **"the 19 ungated screens were measured as Housekeeping; with Front Desk there may be more, or fewer"** —
   **the number is identical.** Those routes carry no permission, so no role changes the count.
3. ⚠️ **"if `demo-staff` and `demo-owner` are already seedable in production, that is a finding bigger than
   the missing eight"** — the premise was nearly right and the reality is worse in a different way: they
   were **not** seedable in `production`, but **were** in `staging` and every other environment name.
4. **`docs/PERMISSION-ENFORCEMENT.md` does not exist** — the file is `docs/PHANTOM-PERMISSIONS.md`.
   (Named in two consecutive briefs.)
