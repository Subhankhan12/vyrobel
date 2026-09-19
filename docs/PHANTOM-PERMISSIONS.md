# Permission toggles that gate nothing

**2026-09-04.** ⚠️ **Nothing was granted, loosened, wired or removed.** This pass MEASURES and GUARDS; every
ruling below is the founder's, and two of them move pinned counts.

## #1 · Re-derived — the count moved, twice

| | reported | **measured** |
|---|---|---|
| permissions in the matrix | 46 | **51** |
| unread ("phantom") | ~12 | **17** |
| enforced | — | **34** |
| ungated operator GET screens | 7 | **19** |

⚠️ **The half-gate category the brief predicted does not exist here: it is ZERO.** No permission is read by
a view directive alone. Every permission is either enforced at a route/policy/service check (34) or read by
nothing at all (17) — the split is cleaner and more binary than expected.

⚠️ **And my own first census was wrong.** A naive resource-match reported `payments.view` and
`addons.manage` as "features with no gate"; the routes it found were **guest-realm** (`/guest/payments`,
guest add-on requests), which have their own guard and their own auth guard. Corrected before it reached the
table.

## #2 · The 17, classified — and the answer really is different per toggle

### A. The capability IS enforced, under a DIFFERENT permission (6) → **needs a ruling**

| permission | what actually gates the feature |
|---|---|
| **`taxes.manage`** | `POST /app/settings/tax-fees` → **`properties.manage`** |
| **`fees.manage`** | `POST /app/settings/fees/add` → **`properties.manage`** |
| `restrictions.manage` | stay restrictions ride the rates surface → `rates.manage` |
| `availability.manage` | availability is written only through `AssignmentService` → `rates.manage` / `reservations.manage` |
| `availability.view` | read through the calendar and rack → `rates.view` / `reservations.view` |
| `housekeeping.view` | the board has **no gate at all**; every write → `housekeeping.manage` |

### B. The capability does not exist as an operator surface (8) → **REMOVE is the likely ruling**

`invoices.view` · `invoices.issue` (invoice numbering is an explicit "Soon") · `amenities.manage` ·
`corporate_accounts.manage` · `guest_documents.manage` · `api.manage` (platform-admin CLI only) ·
`organizations.manage` (only `TenantProvisioningService` creates orgs) · `addons.manage` (a **guest**
request flow).

### C. Enforced by a ROLE allow-list rather than a permission (3) → **leave, or reclassify**

`owner_portal.view` · `guest_portal.view` · `payments.view`.

⚠️ **"Label it" was not chosen for any of them** — it is an admission, not a fix.

## ⚠️⚠️ The defect is the OPPOSITE of the brief's framing — and it is worse

The brief expected *"turn a permission off and the role can still do it."* **Measured, by acting:**

```
role:                     Hotel Manager        (team set — see below)
can('taxes.manage')       true                 ← the matrix grants it
can('properties.manage')  false
POST /app/settings/tax-fees → 403              ← the route refuses
```

`taxes.manage` and `fees.manage` are granted to **five roles** (Super Admin, Property Owner, Group Admin,
**Hotel Manager**, **Accountant**); the gate that actually runs, `properties.manage`, is held by **three** —
excluding both. **So the matrix promises access the product refuses.** An admin turning the switch *on*
achieves nothing, and turning it *off* stops nobody who already holds `properties.manage`. Both directions
are false.

⚠️ **The permission is wrong, not the role — REPORTED, NOT FIXED.** Granting `properties.manage` to a Hotel
Manager to make the check pass is widening access to green a gate, the access-shaped twin of re-baselining
money. Wiring the route to `taxes.manage` instead **widens** who can edit tax settings, which is a product
decision. **Yours.**

## #3 · Who would lose access, if anything were wired

Nothing was wired, so nobody lost anything. Had `housekeeping.view` been wired to the board, **Front Desk,
Maintenance and Guest Support Agent** would keep it and every other role would lose the screen — and ⚠️
**eight of fourteen roles have no demo account**, so several of those paths cannot be exercised at all.

## #4 · The silent-false shape — present, and pinned

⚠️ `can()` **fails silently to false without a team set**, and a test now pins it: the same Hotel Manager
returns `true` with the team set and `false` without, with nothing warning in between. Every gate this pass
touched is proven in **both** directions — the denied role is refused **and** the permitted role succeeds —
because a gate proven only in the deny direction may be denying everyone, which looks identical to a working
restriction.

## #5 · The 19 ungated screens — read versus write, separated

Acting as a **Housekeeping** role (identity and team asserted):

| | result |
|---|---|
| READ `/app/front-desk`, `/app/rack`, `/app/reservations`, `/app/maintenance`, `/app/calendar`, `/app/units`, `/app/channels` | **200 — all open** |
| ⚠️ `/app/reservations` | **200 and it renders GUEST NAMES** |
| WRITE `POST /app/reservations/{r}/cancel` | **403 — gated** |
| WRITE `POST /app/housekeeping/{t}/status` | 422 — permitted, as designed |

**Reads are open; writes are gated.** So this is a **privacy** question, not an integrity one — with one
concrete edge: a housekeeper can read guest names on `/app/reservations`. Same tenant, name-only, no contact
PII (invariant #28 holds the perimeter), so it is not a breach — but it is more than a housekeeper needs.

⚠️ **Five of the 19 are legitimately ungated** — `dashboard`, `profile`, `security`, `notifications`,
`notification-preferences` are self-service and every operator needs them. **Not gated in this pass**:
whether a housekeeper should see the rack is a product decision, and each would need a `can:` plus a demo
account for the roles that would lose it.

## #7 · The guard

`tests/Support/permission_helpers.php` + `PermissionEnforcementContractTest`, joining the shared family
(`heading` · `dialog` · `label` · `overflow` · `keyboard`). It reads the catalogue from the app's own config
— never a retyped list — and requires every permission to be enforced **or** carry an allow-list entry with
its reason and the ruling it needs. A second test fails if an entry outlives what it describes.

⚠️ `config/authorization.php` is deliberately **not** an enforcement site: counting the file that *declares*
permissions would make every one look enforced by virtue of existing, which is the exact illusion measured.

⚠️ **Non-vacuity floor:** the catalogue must exceed 40, enforcement sites must exceed 25, enforced must be
fewer than the catalogue, and a known-enforced permission must be seen as such — so a broken walk cannot
read as perfect enforcement.

## #8 · Mutations — every one landed, every one red

| mutation | landed | result |
|---|---|---|
| **wrong fix** — grant `properties.manage` to Hotel Manager | ✅ | **RED** |
| remove the wired check from the tax route | ✅ | **RED** |
| a new unread permission appears | ✅ | **RED** |
| the comment strip removed (scanner reads its own prose) | ✅ | **RED** |
| the non-vacuity floor removed (broken walk) | ✅ | **RED** |

⚠️ The strip mutation went **red**, not green — this file and the test both name these permissions in prose,
so an unstripped scanner really would have read its own explanation.

All three touched files byte-identical after restore.

## ⚠️⚠️ RULED — R-PHANTOM, 2026-09-19 (recorded FIX 41)

**KEEP the seventeen. DOCUMENT them. PIN THE EXACT REGISTER.** No seeder churn, no role-table churn —
nothing is granted, wired or removed, and `role_has_permissions` does not move.

⚠️ **The pin asserts the NAMES, not a count** — `PhantomPermissionRegisterIsPinnedTest`, holding the three
shapes (6 / 8 / 3) separately so a permission cannot drift between categories. 🔬 **A count would not have
been a pin**: measured under a rename mutation, the census still counts **17** and a count-only assertion
stays **green** while the name assertion reddens.

⚠️⚠️ **The "read nowhere" half is a NEGATIVE CLAIM PROVEN BY SCANNING SOURCE** — the needle-reads-its-own-list
trap. It ships only because both halves of the defence were checked in FIX 41:

- **The scan cannot see itself, structurally.** Its roots are `app/`, `routes/`, `resources/views/`, so
  `tests/` (the register) and `docs/` (this file, which names all seventeen in prose) are never read at all;
  `config/` is skipped by an explicit branch, because the file that DECLARES a permission must not count as
  one that READS it. A test asserts this rather than a comment claiming it.
- **It finds a reader planted on purpose.** 🔬 A real `->can('invoices.view')` added to an operator
  controller moved that permission out of the unread set and reddened the pin.

⚠️ **Heredocs are now stripped too** (FIX 41). A heredoc is a string and comment-stripping does not touch
one. 🔬 Proven inert today — the scanned roots contain exactly one heredoc and it names no permission, so
the census is byte-identical with and without the strip. It is there for the fifty-second permission.

⚠️ **The three questions below are ANSWERED BY THIS RULING, and kept as the record of what was weighed.**

## ⚠️ What needs a ruling — reported, not decided

1. **`taxes.manage` / `fees.manage`** — wire the route to them (widens who may edit tax settings), or remove
   them (moves `role_has_permissions`, pinned at 260). ⚠️ **Both are STOP-AND-ASK.**
2. **The 8 permissions with no feature** — removing them moves the pinned 260.
3. **The 14 operational ungated screens** — whether a housekeeper should read the rack, and whether
   `/app/reservations` should show guest names to every role.

## ⚠️ Where this brief was wrong

The count was 46/12/7; it is **51/17/19**. The predicted half-gate category is **empty**. And the defect
runs the other way: the matrix **grants** access the route refuses, rather than failing to restrict — which
is the harder direction to notice, because nobody complains about being denied something they never tried.
