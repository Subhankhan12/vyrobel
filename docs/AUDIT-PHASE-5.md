# AUDIT PHASE 5 — Hotel Manager, every screen walked

**2026-09-12.** 🖥 **Instrument: MCP Playwright** · HeadlessChrome/151 · `php artisan serve` on
`http://localhost:8000` (the `APP_URL` origin). ⚠️ **Validated in both directions before any finding.**
🔬 **Signed in as `demo-staff@vyrobel-demo.test` — the only holder of `Hotel Manager`.**

### The four marks — only 🔬 and 🖥 are facts
**🔬 MEASURED** · **🖥 BROWSER** · **📖 ASSERTED** · **📄 RECORDED**

⚠️⚠️ **MULTI-TENANCY REMAINS NOT TESTED** — one organisation.
⚠️⚠️ **TWO true orphan permissions** (`organizations.manage`, `api.manage`) — the corrected figure.

---

## 0 · Verdict

| | |
|---|---|
| **Permissions** | 🔬 **39 of 51** — config and live DB **agree exactly**. Lacks **12** |
| **Screens walked** | 🖥 **52 of 54 GET routes covered.** 2 **NOT EXAMINED** (§1) |
| **Team** | ✅ **PROVEN SET** — 40+ permission-gated screens rendered (§6) |
| **Denials** | ✅ **7 screens + 4 writes refused**, every one **proven by row count**, not status |
| **New findings** | **2 — F-11 (MEDIUM, the navigation lies once) · F-12 (LOW)** |
| **The recorded mismatch** | ⚠️⚠️ **Seen — and it is SHARPER than recorded** (§4) |
| **Application code changed** | **NONE** |

---

## 1 · ALL 54 SCREENS — 🖥 walked, not sampled

🔬 **Re-measured: 54 GET routes** (38 plain · 9 parameterised · 7 downloads) — **Phase 3's figure holds.**
🖥 **52 covered. 9 real downloads captured** (CSV/PDF/XLSX/JSON), **parameterised routes walked with real
IDs** from the database — not skipped.

⛔ **2 NOT EXAMINED, never "clean":** `app/email-previews/{key}` and `app/email-templates/{key}/edit` —
**no template key was discovered to substitute.** *(Phase 2 left 3, Phase 3 left 20; this is 2.)*

### 🖥 The seven dimensions across the 52
| dimension | result |
|---|---|
| **UI/UX** | ⚠️ **2 screens with no `h1`** (F-7, **KNOWN** from Phase 3, confirmed still present) · **1 screen with TWO `h1`s** (F-12, new) |
| **DESIGN** | 🖥 **Overflow 0px on every screen.** ✅ `REMAINING-OVERFLOW` **holds** — KNOWN-FIXED, not re-reported |
| **FUNCTIONALITY** | 🖥 No 500s, no broken renders. 7 × 403 (all correct), 1 × 404 (`/app`, which is not a route — **KNOWN** from Phase 1) |
| **CRUD** | §5 — writes tested both directions |
| **SECURITY** | ✅ **4 denied writes + 7 denied screens**, all attributed to the permission gate and **proven by row count** |
| **PERFORMANCE** | ⚠️ **Not meaningfully measurable** on `artisan serve`. Deferred |
| **MULTI-TENANCY** | ⛔ **NOT TESTED** — one organisation |

---

## 2 · ⚠️⚠️ A 200 IS NOT "FINE" — what these screens actually give this person

✅ **Hotel Manager can do its job.** 🖥 The operational core renders **with real data and working controls**:
`reservations` **25 rows, 1 submit** · `guests` **10 rows** · `housekeeping` **7 rows, 7 submits** ·
`units` **5 rows** · `rates` **7 rows, 3 submits** · `reviews` **14 submits** · `schedule` **3 submits** ·
`portfolio` **3 rows** · `reports` **5 rows** · reservation detail **5 submits** · guest detail **4 submits**.

### ⚠️ Screens that render but offer this role nothing to do
🖥 **16 of 52 screens have ZERO submit controls.** Most are legitimately read-only (calendar, reports,
search results, email previews). **Three are worth naming:**

| screen | 🖥 what Hotel Manager gets |
|---|---|
| **`/app/group-booking`** | **494 chars, 0 submits, an empty state, and no `h1`.** ⚠️ A create screen with no way to create |
| **`/app/reservations/create`** | **803 chars, 0 submits, no `h1`** — ⚠️ the realm's primary create form offers this role no submit button |
| **`/app/owners`** and **`/app/owners/{owner}`** | render (owner detail shows **6 rows**) but **0 submits** — read-only, because `users.manage` is lacked. ✅ Consistent, not broken |

⚠️ **`/app/reservations/create` and `/app/group-booking` having no submit is NOT explained on either
screen.** ⚠️ **NOT ESTABLISHED whether the submit is permission-hidden or JavaScript-driven** (both are
Alpine-heavy). **Reported as an open question for Phase 6, not as a defect** — the honest bound.

---

## 3 · ⚠️⚠️ THE NAVIGATION LIES EXACTLY ONCE

🖥 **All 23 navigation links followed as Hotel Manager. One dead end.**

### ⛔ F-11 · "PAYOUT RUN" IS OFFERED IN THE SIDEBAR AND RETURNS 403 — **NEW · MEDIUM**
🖥 The sidebar shows **"Payout run" → `/app/payouts/run`**, and that route enforces `payouts.manage`,
which **Hotel Manager lacks**. Clicking it yields **403 "You don't have access to this."**

⚠️ **This is a dead end the product offered them** — 1 of 23 links, every time, for the role that is
arguably the product's most-used. ✅ **The other 22 all resolve.**
⚠️ **And the contrast proves it is a gap, not a policy:** 🖥 **Settings, Roles and Billing are correctly
ABSENT from the navigation** for this role — so the nav *does* filter by permission, and `payouts/run`
escaped that filtering. **CLASS: NEW. 🖥 BROWSER-VERIFIED.**

📄 *Phase 4 found Group Admin's navigation fully honest — Group Admin holds `payouts.manage`, which is why
it did not surface there. **The defect only appears for roles that lack it.***

### ✅ The reverse direction — screens reachable by URL but never offered
🖥 **`/app/rack`** (renders identically to `/app/calendar`), **`/app/email-previews`**,
**`/app/notification-preferences`**, **`/app/guest-requests`**, **`/app/insights`** are reachable and
**not in the navigation**. ⚠️ **None is a permission problem** — Hotel Manager holds what they need.
**Reported as navigation coverage, not as a security finding.**

---

## 4 · ⚠️⚠️ THE MISMATCH, SEEN — AND IT IS SHARPER THAN RECORDED

📄 **The record says:** `taxes.manage`/`fees.manage` are granted to roles the route refuses, so *"the matrix
promises access the product refuses."* 📄 **The brief anticipated:** a person who *"believes they have
access, does the work, and loses it at the last step."*

🖥 **What actually happens to Hotel Manager is worse in one way and better in another:**

| step | 🖥 measured |
|---|---|
| Is "Settings" in their navigation? | ⛔ **NO** — correctly hidden |
| Does `/app/settings` render? | ⛔ **NO — HTTP 403**, *"Your role doesn't include this area."* |
| Do the tax controls appear enabled? | ⛔ **They never appear at all** |
| `POST app/settings/tax-fees` | ⛔ **403 from the permission gate** |
| 🔬 Did it land? | ✅ **`taxes` still 2, `fees` still 1, zero AUDIT-named rows** |

⚠️⚠️ **So there is NO "does the work and loses it at the last step" for this role — the screen is
unreachable from the first click.** ✅ **Better**: no wasted effort, no data loss, no false promise on screen.
⛔ **Worse**: **`taxes.manage` and `fees.manage` are entirely unusable by Hotel Manager.** The only screen
that exercises them lives on `/app/settings`, gated by `properties.manage`, which this role lacks.

⚠️⚠️ **THE ACCURATE STATEMENT, replacing the record's:** the defect is **not** a UI that promises and then
refuses. It is that **two permissions are granted to a role that has no reachable surface on which to use
them.** The promise is made **in the permission matrix**, which is the only place these permissions are
visible at all — an administrator ticking `taxes.manage` for Hotel Manager is doing something that has no
effect whatsoever.

⛔ **Nothing was granted to make any check pass.** ⛔ **Evidence for the open ruling; not a decision.**

---

## 5 · BOTH DIRECTIONS — denials proven BY ROW COUNT

### ⛔ Denied — 4 writes, each attributed and each proven not to land
| write | permission lacked | 🖥 | 🔬 did it land? |
|---|---|---|---|
| `POST app/settings/tax-fees` | `properties.manage` | **403 gate** | ❌ `taxes` **2 → 2** |
| `POST app/settings/fees/add` | `properties.manage` | **403 gate** | ❌ `fees` **1 → 1** |
| `POST app/owners/invite` | `users.manage` | **403 gate** | ❌ `owner_invites` **0** |
| `POST app/guests/{g}/anonymize` | `guests.erase` | **403 gate** | ❌ **0 anonymised guests** |

⚠️ **Each attributed:** 403, **not** 419 (CSRF), **not** 429 (throttle), **not** a login bounce.
⚠️ **And the row count is what proves it** — Phase 4's model. A 403 alone proves only that a response was
sent.

### ✅ Permitted — proving the gate is not refusing everyone
🖥 `POST app/rates/units/preview` (`rates.manage`) → **302, not 403** · `POST app/profile/preferences`
(ungated, self-scoped) → **302**.

### ⛔ Screens denied (7) — all correct
`/app/billing`, `/app/billing/checkout` (`billing.manage`) · `/app/settings` (`properties.manage`) ·
`/app/roles` (`users.manage`) · `/app/payouts/run` (`payouts.manage` — **and F-11**) ·
`/app/owners/invite/preview` (`users.manage`) · **`/app/reservations/{r}/refund` (`payments.refund`)**.

✅ **Hotel Manager cannot reach the refund screen.** ⛔ **The 17 money-write routes were NOT exercised;
`owner_ledger` 29 / 730640 untouched.**

### ⛔ F-12 · TWO `h1` ELEMENTS ON THE MAINTENANCE TICKET DETAIL — **NEW · LOW**
🖥 `/app/maintenance/{ticket}` renders **two visible `h1`s**: *"Work order"* and *"Wardrobe door off its
runner"*. **Every other screen has exactly one or zero.** **🖥 BROWSER-VERIFIED. CLASS: NEW.**

---

## 6 · HOW THE TEAM WAS PROVEN SET

⚠️ `can()` returns **false** with no team — three sightings. Here it would have produced a report of
fictional denials.
🔬 **Asserted as Phase 4 did — by the permitted half, not by session inspection:** **40+ permission-gated
screens rendered 200 with real data** (`rates.manage`, `guests.view`, `units.manage`, `reports.view`,
`housekeeping.manage`, `maintenance.manage`, `schedule.*`, `reviews.*`, `folios.view`).
✅ **A 39-permission role visibly doing 39-permissions-worth of work is the assertion.** ⚠️ Had the team
been unset, all 52 would have refused — and the 7 that *did* refuse are exactly the 7 whose permissions this
role lacks, which a global failure could not produce.

---

## 7 · STATE — every table measured BEFORE being touched

🔬 **16 tables captured before any probe. 15 IDENTICAL. ONE MOVED:**

| | |
|---|---|
| ⚠️ **`audit_events` 148 → 152 (+4)** | **RE-PINNED with its cause.** 🔬 Rows are `maintenance.costs_exported` (×3 — my CSV/PDF/XLSX downloads) and `user.preferences_updated` (my permitted-half write). **Honest records of actions I performed.** ⛔ **NOT deleted** — audit rows are never pruned. *(Phases 2 and 4 made the same call.)* |

✅ **UNMOVED:** `owner_ledger` **29 / 730640** · `role_has_permissions` **260** · `permissions` **51** ·
`roles` **14** · `model_has_roles` **14** · `payments` **16** · `reservations` **42** ·
`domain_events` **190** · `taxes` **2** · `fees` **1** · `properties` **2** · `units` **8** ·
`guests` **10** · `housekeeping_tasks` **7** · `maintenance_tickets` **5**.

✅ **All 14 demo accounts authenticate.** ✅ **The seeder still refuses outside `local`/`testing`.**
⚠️ **The money gate was NOT run.** ⛔ **Suite not run — no application code changed.**
⛔ **F-1 through F-10 remain open; F-11 and F-12 added.**
📎 9 files downloaded to `.playwright-mcp/` — 🔬 **gitignored**, outside the repo diff.

---

## 8 · ⚠️⚠️ THE LIST HANDED TO PHASE 6 — Front Desk ⊂ Hotel Manager

🔬 **Front Desk holds 20 permissions; Hotel Manager 39. Front Desk will lose 19.**

⚠️ **15 DISTINCT SCREENS Front Desk must be tested as DENIED:**
`app/insights` · `app/portfolio` · `app/reports` (+ **3 export routes**) · `app/rates` · `app/reviews` ·
`app/owners` · `app/owners/{owner}` · `app/maintenance/costs` (+ **3 export routes**) ·
`app/maintenance/settings`

⚠️ **And 67 WRITES across 11 permissions:**
| permission | screens lost | writes lost |
|---|---|---|
| `maintenance.manage` | 5 | 14 |
| `rates.manage` | 0 | 12 |
| `units.manage` | 0 | 10 |
| `schedule.manage` | 0 | 9 |
| `housekeeping.manage` | 0 | **7** |
| `reports.view` | 7 | 0 |
| `reviews.manage` | 0 | 3 |
| `reservations.cancel` | 0 | 2 |
| `rates.view` · `reviews.view` · `properties.view` | 1 each | 0 |

### ⚠️ What Phase 6 must ALSO carry, from this phase's experience
1. ⚠️⚠️ **Check the navigation for dead ends FIRST.** F-11 exists because a nav link outlived the
   permission that justified it. **Front Desk lacks 19 permissions — it is far more exposed to this shape
   than Hotel Manager was.** ⚠️ **Follow every link; do not assume the filter works.**
2. ⚠️ **`housekeeping.manage` is the sharpest one to watch** — Front Desk loses **7 writes on a screen it
   can still SEE** (`/app/housekeeping` is not permission-gated as a screen). ⚠️ **Expect a screen that
   renders with its actions removed — and check whether that is explained.**
3. ⚠️ **Resolve the open question from §2**: whether `reservations/create`'s missing submit is
   permission-driven or JavaScript-driven. **Front Desk holds `reservations.create`, so it is the
   controlled comparison.**

---

## 9 · ⚠️ NEAR-FALSE-POSITIVES — one more (running total **FIFTEEN**)

15. **"There are 0 operator GET screens."** 🔬 My first census returned **zero**, which would have been
    absurd. `route:list --json` returns `method` as a **string** (`"GET|HEAD"`), not an array, so
    `in_array("GET", …)` threw. ⚠️ **The same shape broke a Phase 2 probe** — and a result of *zero* is the
    one number that should always be disbelieved before it is reported.

⚠️ **Also avoided by construction:** the 9 download routes return **null** from a navigation, which Phase 4
misread as "broken". **This phase waited on the `download` event instead** — all 9 correctly recorded as
reached.

---

## 10 · ⚠️ WHERE THIS BRIEF WAS WRONG

1. ⚠️⚠️ **"a person who believes they have access, does the work, and loses it at the last step."** 🖥
   **Not for this role.** `/app/settings` **403s from the first click** and is absent from the navigation.
   ⚠️ **The real defect is different and arguably worse:** two permissions granted to a role with **no
   reachable surface on which to use them** (§4).
2. ⚠️ **"Phase 4 visited seven screens."** 🔬 Phase 4 visited **11 screens and 7 write routes** across its
   probes. **The correction to the phase design stands** — it was the delta, not the surface — but the
   figure was low.
3. ✅ **Correct and load-bearing, twice:** *a 200 is not fine* (**16 screens render with zero submits; three
   are worth naming**) and *the navigation must not lie* (**F-11 — one dead end in 23 links, found only by
   following every one**).
