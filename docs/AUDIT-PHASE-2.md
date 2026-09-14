# AUDIT PHASE 2 — the platform-admin realm

**2026-09-12.** 🖥 **Instrument: MCP Playwright** (the `.mcp.json` server, Phase 0 §4a) · HeadlessChrome/151
· `php artisan serve` on `http://localhost:8000`, the `APP_URL` origin.
⚠️ **Validated in both directions before any finding** — known content exact, negative control reported an
absent element.

### The four marks — only 🔬 and 🖥 are facts
**🔬 MEASURED** · **🖥 BROWSER** · **📖 ASSERTED** (read, never run) · **📄 RECORDED** (never re-checked).

### ⚠️⚠️ PROGRAMME CONSTRAINTS — restated, and one of them ANSWERED
1. ⛔ **MULTI-TENANCY IS NOT TESTED.** 🔬 One organisation. ⚠️ **In this realm that absence is at its most
   consequential** — a super-admin is precisely the actor multi-tenancy exists to bound (§5).
2. ⛔ **THE FIVE ORPHAN PERMISSIONS — the hole STANDS, and is now better understood.** 🔬 They are **not**
   exercised here either (§6).

---

## 0 · Verdict — nothing severe, one new finding, one recommendation honoured

| | |
|---|---|
| **Seeder guard (prerequisite)** | ✅ **Still refuses** on `staging` AND `production`, parent AND `--class=` — confirmed **before** signing in |
| **2FA mandate** | ✅ **Cannot be bypassed.** Proven with **writes**, not reads |
| **Recovery codes** | ✅ **Single-use**, shown once, proven with a succeeding control |
| **Screens** | 🔬 **31 admin routes · 14 GET screens · 17 write actions** — the reported "nine" has moved |
| **Irreversible actions** | ✅ **None found.** Suspension is reversible; **plans cannot be deleted at all** |
| **New findings** | **1 — F-5 (MEDIUM)**, which makes the flash defect **a class three doors wide** |
| **Application code changed** | **NONE** |

---

## 1 · THE 2FA MANDATE — 🖥 proven by WRITES, not reads

⚠️ **Prerequisite discharged first.** 🔬 `DemoDataSafetyGuard` refuses in `staging` and `production`, via the
parent seeder **and** `--class=DemoSeeder` **and** `--class=PlatformAdminSeeder`. **Only then** did I sign in
as `admin@vyrobel-demo.test`, whose password is the literal string `password`, published in this repository.

🔬 **The seeded admin was NOT enrolled** — so the unenrolled path was testable without contriving it.

| what was tested | 🖥 result |
|---|---|
| Sign in unenrolled | → **`/admin/two-factor/setup`** — *"Secure your account. Two-factor authentication is required for platform access."* |
| **READ** 5 real admin screens (`/admin`, `/customers`, `/plans`, `/activity`, `/integrations`) | **all 5 redirected to setup** — attributed to `EnsurePlatformAdminTwoFactor` |
| ⚠️⚠️ **WRITE — `POST /admin/plans`** (create a plan) | **302 → `/admin/two-factor/setup`** ✅ **blocked** |
| ⚠️⚠️ **WRITE — `POST /admin/plans/{id}/toggle`** | **302 → `/admin/two-factor/setup`** ✅ **blocked** |
| 🔬 **Did the blocked write land?** | **`plans` still 4; zero `AUDIT%` rows.** ⚠️ *An acceptance must be attributed too* — the redirect alone would not have proved it |

✅ **THE MANDATE CANNOT BE BYPASSED BY A DIRECT POST.** 📖 The middleware reads
`PlatformAdmin::hasTwoFactorEnabled()` — no role, permission or team check — so **the `can()`-without-a-team
trap cannot apply here** (§6).

### 🖥 Recovery codes — single-use, with a control that succeeded
🔬 Enrolment completed with a genuine TOTP computed from the pending secret. **Eight codes shown once**:
*"Store these somewhere safe — each works once if you lose your device."*

| | 🖥 |
|---|---|
| **FIRST USE of a recovery code (control)** | → **`/admin` Overview** ✅ admitted |
| **REPLAY of the same code** | → stays on `/admin/two-factor`, *"That code is invalid or has expired."* |

✅ **SINGLE-USE HOLDS.** ⚠️ The control is what makes this admissible: without it, "refused" could not be
distinguished from "the probe never worked."

⚠️ **Observation — a GET that writes.** 🔬 Visiting `/admin/two-factor/setup` **generates and persists a
secret** (`TwoFactorService:37`). It is defensible (enrolment must show a key) but it means **the setup
screen is a mutation**, and my own "capture before touching" was already too late — see §7.

---

## 2 · THE SCREEN CENSUS — 🔬 measured, and "nine" has moved

🔬 **31 admin routes: 14 GET screens, 17 write actions.** Of the 14 GETs, **2 are pre-auth** (`admin/login`,
`admin/two-factor`), **1 is a token door** (`admin/account/email/verify/…`), **1 is enrolment**
(`admin/two-factor/setup`) — leaving **10 authenticated screens**.

🖥 **7 of 10 reached and rendered** (all HTTP 200, correct `h1`, correct title):

| screen | 🖥 | rows rendered |
|---|---|---|
| `/admin` Overview | ✅ | — |
| `/admin/customers` | ✅ | **1** (the one organisation) |
| `/admin/plans` | ✅ | **4** (matches the pinned `plans` count) |
| `/admin/plans/create` New plan | ✅ | — |
| `/admin/activity` Security & activity | ✅ | **64** |
| `/admin/integrations` | ✅ | **4** |
| `/admin/account` | ✅ | — |

⚠️ **Not reached: 3** — `/admin/customers/{organization}` (detail), `/admin/plans/{plan}/edit`,
`/admin/integrations/{channel}`. **Reported as NOT EXAMINED**, not as clean.

### ⚠️ Which screens are STRUCTURALLY cross-tenant — and why that is unprovable today
📖 **Customers (list + detail), the Overview counters, and Security & activity are cross-tenant by
construction** — they exist to show every organisation. 🔬 **With ONE organisation, `/admin/customers`
rendered exactly 1 row.** ⚠️⚠️ **That is consistent with correct behaviour AND with a total absence of
scoping. Both hypotheses predict one row.** **This is a limitation of the audit, not a property of the
product** — and it is the sharpest instance of the one-organisation constraint in the whole programme.

---

## 3 · THIS REALM EDITS MONEY — what an admin can do to a plan

📖 **Established from source; no plan was created, edited or toggled.**

| | 📖 |
|---|---|
| **Create** | `PlanController::store` validates via `PlanIntegrity::rules()` and forces **`is_active => false`** — a new plan **cannot be born sellable** |
| **Update** | ⚠️ **The SAME ruleset as create** (`PlanIntegrity::rules($model->id)`), with a docblock recording that update *"previously validated only `name` + `price`"* — a §N.8f one-mapping fix already made |
| **Money mapping** | One shared `columnsFrom()` for create and update, so *"`price_minor` from a previous flat life"* cannot show one number and bill another |
| **Toggle** | `admin.plans.toggle` — activation is where `blockedFromActivation()` applies |
| ⛔ **DELETE** | 🔬 **`PlanController` has NO destroy method and there is NO delete route.** ✅ **The recorded recommendation against deletable plans IS honoured by the admin UI** |

✅ **`plans` 4 and `plan_features` 12 both unmoved** — measured before and after (§7).
⛔ **The three open plan rulings were NOT decided and NOT touched.**

### ✅ Nothing irreversible — established WITHOUT doing it
🔬 **Suspension is a status flip, and `reactivate` exists**: `CustomerController::reactivate` →
`update(['status' => 'active'])`. **Neither was executed.**
🔬 **`suspend` additionally requires `current_password:admin`** — and the code records *why the rule is
qualified*: `/admin` runs on `auth:admin`, so **the bare rule would check the `web` guard and validate
nothing.** ⚠️ **That is a real trap, avoided deliberately** — and worth noting it is the same shape as the
`can()`-without-a-team failure: a check that silently validates nothing.

---

## 4 · ⛔ F-5 — THE FLASH DEFECT IS NOW A CLASS THREE DOORS WIDE — **NEW · MEDIUM**

**What was tested.** The admin email-verify door the limiter took from Phase 1b — all four states, decided
by **destination**, then by **what the page actually shows**.

| state | 🖥 status | landed on | flash visible? | attributed to |
|---|---|---|---|---|
| **EXPIRED signature** | **403** | error page | — | **the `signed` middleware**, before the handler |
| **TAMPERED hash** (valid sig) | 200 | **`/admin/login`** | ⛔ **NO** | the handler refused |
| **VALID (control)** | 200 | **`/admin/login`** | ⛔ **NO** | ✅ **ACCEPTED — proven at the data layer:** `email` became the pending address, `pending_email` consumed |
| **ALREADY-USED** (replay) | 200 | **`/admin/login`** | ⛔ **NO** | the handler refused |

⚠️⚠️ **Three different outcomes — including a SUCCESS — produce a byte-identical page.**

🔬 **Cause:** `Admin\AccountController::verifyEmailChange` sets `->with('status', …)` at **lines 151, 156,
163 and 170** — all four outcomes — and redirects to `admin.login`.
🔬 **`resources/views/admin/auth/login.blade.php` contains `session('status')` ZERO times.**
🔬 **The admin realm renders only two flash keys: `admin_notice` and `admin_error`.**

**Consequence — worse here than on the other two doors.** The success message is *"Your email address has
been updated. **Please sign in with your new address.**"* ⛔ **The admin is instructed to sign in with an
address they are never shown, on the account that sits above all tenancy.**

⚠️⚠️ **THE CLASS, NOW THREE DOORS WIDE:**
| | door | key | receiving view |
|---|---|---|---|
| **F-1** | operator sensitive action | `password_status` | `operator-security.blade.php` — not rendered |
| **F-4** | owner email-verify | `status` | `auth/login.blade.php` — not rendered |
| **F-5** | **admin email-verify** | `status` | **`admin/auth/login.blade.php` — not rendered** |

⚠️ **This is a CLASS, not three instances**: *a handler sets a flash the destination view does not render.*
**A guard that enumerates every `->with('key')` and asserts some view in the receiving realm renders it
would close all three and prevent the fourth.** ⛔ **This phase REPORTS; it does not build.**
**Severity: MEDIUM. 🖥 BROWSER-VERIFIED + 🔬 MEASURED. CLASS: NEW.**

⚠️ **KNOWN, re-confirmed, NOT re-reported as new:** the EXPIRED case's 403 reads *"Your role doesn't include
this area… ask an administrator on your team"* — **F-3's copy defect**, on a realm with no roles and no
team. **CLASS: KNOWN (Phase 1).**

---

## 5 · ABOVE TENANCY — and the scope discipline HOLDS

🔬 `TenantContext::runAsSuperAdmin` sets `superAdmin = true` and `organizationId = null` — it drops the
tenant binding wholesale, which is the point of the realm.

⚠️ **The third-sighting question — does dropping tenant scope also drop soft-delete?** ✅ **NO.**
🔬 `CustomerController:112` reads `Subscription::withoutGlobalScopes([OrganizationScope::class])` — **the
scope is named explicitly**, so `SoftDeletingScope` survives.
🔬 **Zero bare `withoutGlobalScopes()` calls anywhere in `app/Http/Controllers/Admin/`.**
✅ **The discipline the invoice resolver established is being followed here. No third sighting.**

---

## 6 · THE ORPHAN PERMISSIONS — the hole STANDS

🔬 **Measured, per permission, across `app/Http/Controllers/Admin/` and `resources/views/admin/`:**

| permission | references in the admin realm |
|---|---|
| `billing.manage` · `roles.manage` · `organizations.manage` · `settings.manage` · `api.manage` | **0 each** |

🔬 **And the realm does not use the permission system at all:** **0** admin controllers reference
`can(`/`hasRole`/`hasPermissionTo`, and **0** admin routes carry `can:` middleware.

⛔ **So the coverage hole is NOT smaller — it stands, and is now better characterised:** those five
permissions are not exercised by the platform admin because **this realm is gated by a guard
(`auth:admin`) plus a 2FA mandate, not by permissions at all.** ✅ **A corollary worth keeping:** the
`can()`-returns-false-without-a-team trap **cannot fire in this realm**, because nothing here calls `can()`.

---

## 7 · STATE — measured before touching, restored, re-measured

| table | before | after |
|---|---|---|
| `plans` | **4** | **4** ✅ |
| `plan_features` | **12** | **12** ✅ |
| `platform_admins` | **1** | **1** ✅ |
| `audit_events` | **146** | **146** ✅ |
| `organizations` | **1** | **1** ✅ |

**Mutations made and reversed:**
- 🔬 Admin **2FA enrolled** (secret, recovery codes, confirmation) → **restored to `NULL / NULL / NULL`**,
  verified. ⚠️ **The true original came from a measurement taken BEFORE I opened the setup page**, because
  that page itself writes a secret — my later "capture" was already contaminated.
- 🔬 Admin `pending_email` set, and the VALID link **really changed `email`** → **both restored exactly**.

### ⚠️⚠️ AND A PROCESS FAILURE OF MY OWN, REPORTED AGAINST MYSELF
🔬 **`platform_admin_actions` went 0 → 8**, every row from this session
(`platform.login` ×2 · `platform.2fa_enrolled` · `platform.dashboard` ×2 · `platform.customers.list` ·
`platform.activity.audit_slice` · `platform.account.email_changed`).

⛔ **I measured `audit_events` before touching anything and did NOT measure this table — the exact mistake
Phase 1b flagged against itself and this brief told me not to repeat.** I found it only by chasing an
unrelated discrepancy.

⚠️ **I am deliberately NOT deleting these rows.** They are the realm's audit trail of a real admin session
that genuinely occurred; the project's own rule is that audit rows are **never pruned**. **Deleting them
would destroy an honest record to make a count look tidy** — a worse outcome than a moved figure with an
attributed cause. **Reported as a re-pin with its reason, not silently restored.**

🔬 **PINNED FIGURES — ALL UNMOVED:** `organizations` **1** · `users` **15** · `reservations` **42** ·
`payments` **16** · `roles` **14** · `permissions` **51** · `model_has_roles` **14** ·
`role_has_permissions` **260** · `plans` **4** · `plan_features` **12** · `owner_ledger` **29 / 730640**.
✅ **All 14 demo accounts authenticate.** ✅ **The platform admin still signs in** (and, correctly, is again
sent to enrolment — its original state).
⚠️ **The money gate was NOT run** (it begins `migrate:fresh --seed`). ⛔ **Suite not run — no code changed.**

---

## 8 · ⚠️ NEAR-FALSE-POSITIVES — two more (running total **NINE**)

8. **"`admin_recovery_codes` is set but never rendered — the admin never sees their codes."** 🔬 **False.**
   `AccountController:46` reads `session('admin_recovery_codes')` and passes it into the view **as a
   variable**, so a blade grep for `session('admin_recovery_codes')` finds nothing. 🖥 The codes **are**
   displayed. ⚠️ **Grepping the view for the session call misses every key read in the controller.**
9. **"`features` is a flash key with no renderer."** 🔬 **False** — my inventory regex matched
   `Plan::query()->with('features')`, **eager loading a relation**. ⚠️ **`->with()` means two different
   things in Laravel, and a flash-key census that does not distinguish them invents findings.**

⚠️ **Both were produced by my own inventory method, and both would have been published as defects.**

---

## 9 · ⚠️ WHERE THIS BRIEF WAS WRONG

1. ⚠️ **"nine screens unexamined."** 🔬 **31 admin routes, 14 GET screens, 10 authenticated screens.**
   The count moved again, as every count in this project has.
2. ⚠️ **"The customer view can SUSPEND an organisation"** — true, but the brief's concern that it might be
   irreversible is **unfounded**: 🔬 `reactivate` exists and is a plain status flip. ✅ The brief was right
   to demand this be established *before* trying it.
3. ⚠️ **It asks whether the orphan permissions "live here", implying they might.** 🔬 **They do not, and
   cannot** — the realm uses no permission checks whatsoever. **The hole stands.**
4. ✅ **Correct and load-bearing:** that the admin email-verify door is structurally identical to F-1/F-4 —
   **it is, and it is now F-5**; and that a write, not a read, is what tests the 2FA mandate.
