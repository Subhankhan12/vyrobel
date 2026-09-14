# AUDIT PHASE 6 — Front Desk, the role people use all day

**2026-09-12.** 🖥 **Instrument: MCP Playwright** · HeadlessChrome/151 · `php artisan serve` on
`http://localhost:8000`. ⚠️ **Validated in both directions before any finding.**
🔬 **Signed in as `demo-frontdesk@vyrobel-demo.test` — the only holder of `Front Desk`.**

### The four marks — only 🔬 and 🖥 are facts
**🔬 MEASURED** · **🖥 BROWSER** · **📖 ASSERTED** · **📄 RECORDED**

⚠️⚠️ **MULTI-TENANCY REMAINS NOT TESTED** — one organisation. **TWO true orphan permissions.**

---

## 0 · Verdict — the three handed-over jobs, answered

| job | answer |
|---|---|
| **1 · Navigation** | ⛔ **TWO dead ends** (Hotel Manager had one) — **F-11 WIDENS into a class** |
| **2 · Housekeeping** | ✅ **Submits ABSENT, not present-and-refused** — but the board is **silent about it** → **F-13** |
| **3 · The submit question** | ✅ **ANSWERED: script-driven, NOT permission-hiding.** `x-show="step === 7"`. **Phase 5's open question closes as NOT a defect** |

| | |
|---|---|
| **Permissions** | 🔬 **20 of 51** — config and DB **agree exactly** |
| **Screens walked** | 🖥 **46 URLs covering the operator surface; 17 correctly denied** |
| **New findings** | **F-13 (MEDIUM) · F-14 (LOW)** + **F-11 upgraded** |
| **Nothing severe** | ⚠️ No money reached, nothing cancelled, no foreign data |
| **Application code changed** | **NONE** |

---

## 1 · ⚠️⚠️ JOB 1 — THE NAVIGATION, FOLLOWED FIRST

🖥 **18 navigation links followed. 16 resolve. TWO refuse.**

| dead end | route | permission lacked |
|---|---|---|
| **"Owners"** | `/app/owners` | `users.manage` |
| **"Payout run"** | `/app/payouts/run` | `payouts.manage` |

### ⛔ F-11 UPGRADED — the dead-end defect is a CLASS that SCALES with permissions lacked
📄 Phase 5 found **1** dead end for Hotel Manager (12 permissions lacked). 🖥 **Front Desk (19 lacked) has 2.**
⚠️ **And Phase 5's contrast still holds — the filter demonstrably works:** 🖥 Settings, Roles, Billing,
Reports, Rates, Reviews, Insights and Portfolio are **all correctly absent** from Front Desk's navigation.
**So these two escaped a filter that otherwise excludes eight other denied screens.**
⚠️⚠️ **This is not one bad link. It is a per-role gap that grows as roles narrow — and the SEVEN REMAINING
ROLES ALL LACK MORE THAN FRONT DESK** (§8). **Severity: MEDIUM. CLASS: KNOWN (F-11), scope WIDENED.**

### ✅ The reverse direction — usable screens the navigation never offers
🖥 `/app/rack`, `/app/email-previews`, `/app/notification-preferences`, `/app/inbox` (as "Messages"),
`/app/dashboard` are reachable and render. ⚠️ **None is a permission problem.** **Navigation coverage, not
a security finding** — same conclusion as Phase 5.

---

## 2 · ⚠️⚠️ JOB 2 — THE HOUSEKEEPING BOARD: RENDERS, CANNOT ACT, SAYS NOTHING

🖥 **What Front Desk sees:** `/app/housekeeping` → **200**, `h1` "Housekeeping", **7 task rows**, real
statuses visible (*clean · In progress · Inspect · READY*).
🖥 **What it can do: nothing.** **0 visible submit controls.** *(Hotel Manager saw 7 — Phase 5.)*
🔬 **Proven by submitting:** `POST /app/housekeeping/{task}/status` → **403 from the permission gate.**

✅ **The good half:** the controls are **ABSENT, not present-and-refused.** ⚠️ **That is the better of the
two outcomes the brief named** — nobody wastes a shift filling in a form that will be rejected.

### ⛔ F-13 · THE BOARD NEVER SAYS WHY IT CANNOT BE USED — **NEW · MEDIUM**
🖥 **`explainsItself: false`** — no "read-only", no "view only", no "ask an administrator", nothing.
⚠️ A Front Desk user sees **seven tasks in actionable-looking states and no way to advance any of them**,
with **no explanation anywhere on the screen.**
🖥 **Confirmed at 375px too:** 15 controls visible, **overflow 0**, but **not one advances a task** — so the
recorded pinned-action fix is intact; there is simply nothing to pin.
⚠️ **Compare the product doing this RIGHT:** `/app/channels` says *"Channel manager isn't in your plan"*,
and Phase 4 found `/app/roles` rendering **51 disabled inputs with an explicit read-only hint.**
**The pattern exists in this product; this board does not use it.** **🖥 BROWSER-VERIFIED. CLASS: NEW.**

---

## 3 · ⚠️⚠️ JOB 3 — THE SUBMIT QUESTION, ANSWERED: SCRIPT-DRIVEN, NOT PERMISSION-HIDING

**The controlled comparison the brief specified.** 🔬 Front Desk **holds `reservations.create`**.

🖥 **Front Desk sees ZERO visible submits on `/app/reservations/create` — identical to Hotel Manager.**
⚠️ By the brief's rule that would mean *"a create form nobody can submit"*. ⛔ **It does not, and here is why:**

🔬 **The submit exists and its anatomy is conclusive:**
| | |
|---|---|
| Element | `<button type="submit">` labelled **"Confirm reservation"** |
| Why invisible | 🔬 **`x-show="step === 7"`** — an **Alpine directive**, `display: none` until step 7 |
| Also | `disabled: true` until the form validates |
| Context | **25 `x-show`/`data-step` elements**; visible controls are step badges **1–7** and a **"Next"** button |

✅ **VERDICT: the create form is a seven-step wizard. The submit is hidden by a SCRIPT CONDITION, not by a
permission check.** ⛔ **NOT a defect. Phase 5's open question closes.**

⚠️ **Honest bound:** I did not reach step 7 — clicking the numbered badges does not advance the wizard
(they are indicators; the control is **"Next"**). **That is my probe's limit, not the product's.** The
`x-show="step === 7"` attribute is the evidence, and it is a script condition on its face.
⚠️ **Same applies to `/app/group-booking`** — the same Alpine shape, not re-derived.

---

## 4 · ⚠️ CAN FRONT DESK DO ITS JOB?

✅ **Broadly yes.** 🖥 The daily surface renders with real data and working controls:

| step | 🖥 |
|---|---|
| **See the day** | `/app/front-desk` 200 · `/app/calendar` 200 · `/app/dashboard` **5 submits** |
| **Find a booking** | `/app/reservations` **25 rows** · `/app/search` 200 · reservation detail **3 submits** |
| **Guests** | `/app/guests` **10 rows** · guest detail **4 submits** |
| **Take a booking** | `/app/reservations/create` — the wizard (§3), submit present behind `step === 7` |
| **Messages / requests** | `/app/inbox` **2 submits** · `/app/guest-requests` 200 |
| ⛔ **Cancel** | **REFUSED — and correctly so** |

### 🖥 The cancel refusal — honest at the route, and proven not to land
🔬 `POST /app/reservations/{r}/cancel` → **403 from the permission gate**.
🔬 **And proven by STATE, not by count:** the target reservation is still **`confirmed`** with
**`cancelled_at` NULL**. ⚠️⚠️ **The row count alone could NOT have proved this** — a cancel changes a
status, not a total. `reservations` stayed at 42 either way. **This is a sharpening of the row-count rule:
for a status change, the row count is the wrong instrument.**

⛔ **MONEY: established, not completed.** The 17 money-write routes were **not exercised**.
🖥 `/app/reservations/{r}/refund` → **403** (`payments.refund` lacked) — Front Desk **cannot reach the
refund screen at all**. ✅ `payments` **16** and `owner_ledger` **29 / 730640** untouched.

---

## 5 · THE FULL WALK — 46 URLs, seven dimensions

🖥 **17 screens correctly denied** (403 from the permission gate): reviews · maintenance/costs ·
maintenance/settings · rates · portfolio · insights · reports · owners · owners/invite/preview ·
payouts/run · roles · settings · billing · reservation refund · owner detail · reports/export.csv ·
maintenance/costs/export.csv. ✅ **Every one matches a permission Front Desk lacks.**

| dimension | result |
|---|---|
| **DESIGN** | 🖥 **Overflow 0px on every screen, desktop and 375px.** ✅ `REMAINING-OVERFLOW` holds — **KNOWN-FIXED** |
| **UI/UX** | ⚠️ `/app/reservations/create` + `/app/group-booking` **no `h1`** — **KNOWN (F-7)**, confirmed for a third role · `/app/maintenance/{ticket}` **two `h1`s** — **KNOWN (F-12)**, confirmed |
| **FUNCTIONALITY** | 🖥 No 500s. `/app/channels` → *"Channel manager isn't in your plan"* — plan gate, **KNOWN** |
| **SECURITY** | ✅ 4 denied writes → **403 gate**, each proven not to land; 1 permitted write → 302 |
| **PERFORMANCE** | ⚠️ Not meaningfully measurable on `artisan serve`. Deferred |
| **MULTI-TENANCY** | ⛔ **NOT TESTED** |

### ⛔ Both directions, proven by state or count
| denied write | 🖥 | 🔬 landed? |
|---|---|---|
| `reservations/{r}/cancel` | **403 gate** | ❌ status still `confirmed`, `cancelled_at` NULL |
| `rates` | **403 gate** | ❌ `rate_calendar` **929 → 929** |
| `housekeeping/{t}/status` | **403 gate** | ❌ `housekeeping_tasks` **7 → 7** |
| `reviews/{x}/publish` | **403 gate** | ❌ no review state changed |

✅ **Permitted:** `POST app/profile/preferences` → **302, not 403.**
✅ **Team proven set by evidence:** **19 permission-gated screens rendered 200 with real data** while
exactly the 17 whose permissions this role lacks refused. ⚠️ **A global `can()` failure could not produce
that split.**

---

## 6 · ⚠️⚠️ THE SHARPENED TAX SHAPE REPEATS — 7 of 20 permissions do nothing

🔬 **Front Desk holds 20 permissions. 7 are enforced on NO route:**
`availability.view` · `guest_documents.manage` · `housekeeping.view` · `invoices.issue` ·
`invoices.view` · `payments.view` · `reservations.restriction_override`

⚠️ **But two of those seven are NOT what they look like — checked, not assumed:**

| permission | verdict |
|---|---|
| `availability.view`, `guest_documents.manage`, `invoices.issue`, `invoices.view`, `payments.view` | ✅ **KNOWN** — all five are in `KNOWN-LIMITS.md` **L6**'s recorded twelve. **Not re-reported.** |
| **`reservations.restriction_override`** | ⛔ **NOT a phantom.** 🔬 It is enforced **in the service layer** — `AssignmentService:739` calls `$actor->can('reservations.restriction_override')`. ⚠️ **My route-middleware check would have wrongly called it inert** (§9) |
| **`housekeeping.view`** | ⛔ **F-14 — a genuine phantom, and NOT in L6's twelve** |

### ⛔ F-14 · `housekeeping.view` IS ENFORCED NOWHERE — **NEW · LOW**
🔬 Its only occurrence in the entire codebase is a **route comment** (`routes/web.php:1231`), which reads:
*"gated `can:housekeeping.manage` … NOT Front Desk's housekeeping.view-only."*
🔬 **`GET /app/housekeeping` carries no permission middleware at all** — the board is visible to every
back-office role. ⚠️ **So ticking `housekeeping.view` changes nothing whatsoever** — the exact shape Phase 5
sharpened for `taxes.manage`. **It is named in a comment as if it gated something; it gates nothing.**
⚠️ **And it compounds F-13:** the permission that *sounds* like "may see the board read-only" is inert,
while the board's silence about being read-only is the actual defect. **CLASS: NEW.**

---

## 7 · STATE — every table measured BEFORE being touched

🔬 **14 tables captured before any probe. 13 IDENTICAL. ONE MOVED:**
⚠️ **`audit_events` 152 → 153 (+1)** — **RE-PINNED with its cause**: `user.preferences_updated`, my
permitted-half write. ⛔ **NOT deleted** — audit rows are never pruned.

✅ **UNMOVED:** `owner_ledger` **29 / 730640** · `payments` **16** · `reservations` **42** ·
`folios` **20** · `folio_items` **40** · `housekeeping_tasks` **7** · `guests` **10** ·
`domain_events` **190** · `role_has_permissions` **260** · `reservation_units` **42** ·
`maintenance_tickets` **5** · `taxes` **2** · `units` **8**.
✅ **All 14 demo accounts authenticate.** ⚠️ **Money gate NOT run.** ⛔ **Suite not run — no code changed.**
⛔ **F-1 to F-12 open; F-13 and F-14 added; F-11 widened.**
📎 3 files downloaded to `.playwright-mcp/` — gitignored.

---

## 8 · ⚠️⚠️ THE LIST HANDED TO PHASES 7+

🔬 **All seven remaining roles lack MORE than Front Desk — so F-11's dead-end class is MORE exposed in
every one of them.**

| role | holds | loses vs Front Desk | permissions enforced on NO route |
|---|---|---|---|
| **Reservation Agent** | 11 | 11 | 2 |
| **Accountant** | 12 | 14 | **6** |
| **Revenue Manager** | 10 | 16 | 3 |
| **Guest Support Agent** | 7 | 15 | 1 |
| **Housekeeping** | **4** | **17** | 1 |
| **Maintenance** | **4** | **17** | 1 |
| **Marketing Manager** | **5** | **19** | 1 |

### ⚠️ What every remaining phase must carry
1. ⚠️⚠️ **Follow the navigation FIRST and count the dead ends.** F-11 went 1 → 2 as permissions fell from
   12 → 19 lacked. **Housekeeping, Maintenance and Marketing Manager hold 4–5 permissions each** — expect
   the most dead ends there, and **report the count per role so the trend is measurable.**
2. ⚠️⚠️ **`Accountant` is the priority role.** It has **6 route-unenforced permissions** — the highest — and
   Phase 4 established it is **the second role hitting the tax/fee mismatch** (`taxes.manage` without
   `properties.manage`). **Expect the sharpened shape in force.**
3. ⚠️⚠️ **`Housekeeping` (4 permissions) is the F-13 acid test.** It is the role the housekeeping board is
   *for*. **If Housekeeping also cannot advance a task, that is a different and far more serious finding
   than F-13.** ⚠️ **Test it first in that phase.**
4. ⚠️ **Do not re-derive the wizard question** (§3) — it is answered: script-driven.
5. ⚠️ **For status-changing denials, check the STATE, not the row count** (§4).

---

## 9 · ⚠️ NEAR-FALSE-POSITIVES — two more (running total **SEVENTEEN**)

16. **"`schedule/pdf`, `folio.pdf` and `security/export` are denied (403) to Front Desk."** 🔬 **False —
    all three downloaded successfully.** ⚠️ **After a download the page never navigates, so `document.body`
    still holds the PREVIOUS screen** — which happened to be a 403. **My denial flag read the wrong page.**
    ⚠️ *Phase 4 misread downloads as broken routes; this is the same trap wearing the opposite face.*
17. **"`reservations.restriction_override` is a phantom permission."** 🔬 **False.** It is enforced in
    `AssignmentService:739` via `$actor->can(...)`. ⚠️ **My check looked only at route middleware** —
    exactly the narrowness that made `KNOWN-LIMITS` L6 use a whole-codebase `str_contains` instead.
    **A permission can be enforced in a service, a Blade `@can`, or a policy — never conclude from routes alone.**

---

## 10 · ⚠️ WHERE THIS BRIEF WAS WRONG

1. ⚠️⚠️ **"if the submit is absent for BOTH, a create form nobody can submit is a serious finding."** 🔬 It
   **is** absent for both — **and it is not a finding.** The submit is hidden by `x-show="step === 7"`, a
   wizard step condition. ⚠️ **The brief's decision rule was sound but its two branches were not
   exhaustive: "absent for both" also admits "hidden by script for both", which is what this is.**
2. ⚠️ **"Front Desk … CANNOT CANCEL"** — ✅ **confirmed**, and the refusal is correct at the route. ⚠️ But
   the brief asked whether the *screen* explains it; 🔬 **the cancel control is simply absent from the
   reservation detail**, which is the same silent-absence shape as F-13, one screen over.
3. ✅ **Correct and load-bearing, three times:** navigation first (**found 2 dead ends immediately**);
   housekeeping absent-vs-refused (**absent — and the silence is the finding**); and *a result of zero
   should be disbelieved* — **which is exactly how §6's "7 phantom permissions" survived scrutiny down to
   one.**
