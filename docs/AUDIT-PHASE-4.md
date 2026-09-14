# AUDIT PHASE 4 — Group Admin, the first real denials

**2026-09-12.** 🖥 **Instrument: MCP Playwright** · HeadlessChrome/151 · `php artisan serve` on
`http://localhost:8000` (the `APP_URL` origin). ⚠️ **Validated in both directions before any finding.**

### The four marks — only 🔬 and 🖥 are facts
**🔬 MEASURED** · **🖥 BROWSER** · **📖 ASSERTED** · **📄 RECORDED**

⚠️⚠️ **MULTI-TENANCY REMAINS NOT TESTED** — one organisation.
⚠️⚠️ **TWO true orphan permissions** (`organizations.manage`, `api.manage`) — **not five**; the corrected
figure from Phase 3 is used throughout and the old one is not revived.

---

## 0 · Verdict

| | |
|---|---|
| **Identity & team** | 🔬 **`demo-groupadmin@vyrobel-demo.test`**, team **PROVEN SET** — §3 |
| **Permissions lacked** | 🔬 **7**, derived from code AND the live DB — they agree exactly |
| **Gates proven BOTH directions** | ✅ **5 denied writes → 403 · 2 permitted writes → 302 · 7 permitted screens → 200** |
| **Escalation** | ✅ **REFUSED** — Group Admin cannot grant itself `billing.manage` |
| **New findings** | **1 — F-10 (MEDIUM)**: three capabilities reachable by nobody below Property Owner |
| **Nothing severe** | ⚠️ No cross-role reach, no money action without permission, nothing irreversible |
| **Application code changed** | **NONE** |

---

## 1 · ⚠️⚠️ THE SEVEN IT LACKS — derived from code, not from any document

🔬 **`config/authorization.php` and the live `role_has_permissions` AGREE EXACTLY** — catalogue **51**,
Group Admin holds **44** (44 DB rows), lacks **7**:

| # | permission | routes enforcing it | in scope here? |
|---|---|---|---|
| 1 | **`billing.manage`** | **9** | ✅ tested |
| 2 | **`settings.manage`** | **4** | ✅ tested |
| 3 | **`roles.manage`** | **1** | ✅ tested |
| 4 | `organizations.manage` | ⛔ **0 — no route anywhere** | true orphan |
| 5 | `api.manage` | ⛔ **0 — no route anywhere** | true orphan |
| 6 | `owner_portal.view` | owner realm | out of scope |
| 7 | `guest_portal.view` | guest realm | out of scope |

⚠️ **So the testable surface of this phase is exactly 14 routes.** The other three lacks are either
unreachable by anyone or belong to realms this phase does not audit.

### ⛔ F-10 · THREE CAPABILITIES ARE REACHABLE BY NOBODY BELOW PROPERTY OWNER — **NEW · MEDIUM**

🔬 Phase 3 established `billing.manage`, `settings.manage` and `roles.manage` are held by **no narrower
role**. 🔬 **This phase confirms the second-highest role in the product lacks all three.**

**Consequence, stated as a product fact:**
- ⛔ **Billing** (9 routes — subscribe, cancel, pause, resume, company details, Connect onboarding)
- ⛔ **Organisation settings and the IP allowlist** (4 routes)
- ⛔ **Editing the permission matrix** (1 route)

…are reachable **only by `Property Owner`**, a wildcard role held by **exactly one seeded account**.
⚠️ **An organisation whose Property Owner is unavailable cannot change its own billing, its organisation
settings, or anybody's permissions** — and Group Admin, the role whose name implies exactly that authority,
cannot either.

⚠️ **This is a product finding, not an audit gap** — the audit *can* reach these (Phase 3 did, as Property
Owner). **It is a role-design question**, and it is **evidence for the three open permission rulings, not a
decision on them.** ⛔ **Nothing was granted to make any check pass.**
**🔬 MEASURED + 🖥 BROWSER-VERIFIED. CLASS: NEW.**

---

## 2 · ⚠️⚠️ BOTH DIRECTIONS ON EVERY GATE — writes, not reads

### ⛔ The DENIED half — 5 writes, all refused by the permission gate
| write | permission | 🖥 |
|---|---|---|
| `POST app/settings/invoice` | `settings.manage` | **403** |
| `POST app/settings/organization` | `settings.manage` | **403** |
| `POST app/security/ip-rules` | `settings.manage` | **403** |
| `POST app/roles/permissions` — ⚠️ **granting itself `billing.manage`** | `roles.manage` | **403** |
| `POST app/billing/company` | `billing.manage` | **403** |

⚠️ **Each attributed:** 403, not 419 (CSRF), not 429 (limiter), not a bounce to login. **The permission gate
is what refused them.**

### ✅ The PERMITTED half — proving the gate is not refusing everyone
| write | permission | 🖥 |
|---|---|---|
| `POST app/rates/units/preview` | `rates.manage` (held) | **302**, not 403 |
| `POST app/dashboard/tasks/toggle` | ungated, self-scoped | **302**, not 403 |
| `POST app/profile/preferences` | ungated, self-scoped | **302** → `/app/profile` |

✅ **Plus 7 permission-gated SCREENS rendered 200** (`rates`, `guests`, `units`, `owners`, `reports`,
`schedule`, `maintenance`).

### 🔬 AND THE ACCEPTANCES ATTRIBUTED TOO
⚠️ **A 403 is not proof a write failed to land, and a 302 is not proof one did.** 🔬 Re-measured:
**`role_has_permissions` still 260**, and 🔬 **`Front Desk` holds `billing.manage` ZERO times** — **the
escalation attempt did not land.** ✅ **Group Admin cannot escalate itself or another role.**

### 🖥 What the refusal looks like to a person
> *"You don't have access to this. **Your role doesn't include this area. If you need it, ask an
> administrator on your team to grant the permission.**"*

✅ **Correct and actionable HERE** — it names the cause (a role), the remedy (ask an administrator) and the
mechanism (a permission). ⚠️ **This is the same copy F-3 flagged as WRONG in Phase 1** — on the platform-admin
email door and a signed-link failure, where there is no role and no team. **The copy is right; its reuse on
roleless doors is the defect.** **F-3 stands, unchanged, and is not re-reported here.**

---

## 3 · ⚠️⚠️ THE TEAM WAS SET — and here is how I know

⚠️ **`can()` returns FALSE with no team.** In this phase that trap would have produced an entire report of
fictional denials, every one looking exactly like a finding.

🔬 **How it was asserted — not by inspecting session state, but by the permitted half:**
**7 screens whose routes enforce permissions Group Admin holds all rendered HTTP 200**
(`rates.manage`, `guests.view`, `units.manage`, `users.manage`, `reports.view`, `schedule.*`,
`maintenance.manage`). ⚠️ **If the team were unset, every one of those would have refused.**
✅ **A role with 44 of 51 permissions demonstrably doing 44-permissions-worth of things is the assertion.**

---

## 4 · ⚠️ NO GATE CONCLUDED FROM A STRING

🔬 Every permission mapping in this report was resolved as the framework does — matching
**`Illuminate\Auth\Middleware\Authorize:<permission>`**, not the `can:` alias. ⚠️ **Phase 3 nearly published
"203 routes are ungated" from exactly that mistake.**
✅ **And every gate was additionally PROVEN WITH A REQUEST** (§2) — middleware presence was never treated as
middleware firing.

---

## 5 · ⚠️⚠️ THE SHARPER DIRECTION — a permission HELD that still yields a refusal

**Group Admin does NOT meet that shape.** 🔬 It holds `taxes.manage`, `fees.manage` **and**
`properties.manage` — the permission the tax/fee route actually enforces — so for this role the matrix and
the route agree.

### 🔬 But the recorded mismatch is now located precisely, for later phases
| | |
|---|---|
| Route enforced | `POST app/settings/tax-fees` and `POST app/settings/fees/add` → **`properties.manage`** |
| Roles holding `taxes.manage` but **lacking** `properties.manage` | 🔬 **`Hotel Manager`** and **`Accountant`** |

⚠️ **Those two roles are promised tax and fee management by the matrix and will be refused by the route.**
**CLASS: KNOWN** (`PHANTOM-PERMISSIONS`, unruled) — ⚠️ **but the affected roles are now named, which the
record did not do.** ⛔ **Evidence for the open ruling; not a decision, and nothing was granted.**

### ✅ And the UI does NOT over-promise to Group Admin
🖥 **Checked, because the obvious reading was wrong:**
- `/app/roles` renders the permission matrix with **51 disabled inputs** and an explicit read-only hint ✅
- `/app/settings` renders **22 forms — and the two `settings.manage` forms (`/settings/invoice`,
  `/settings/organization`) are NOT among them.** The 10 Save buttons present belong to `properties.manage`
  sections Group Admin legitimately holds ✅
- `/app/billing` is **absent from the navigation** entirely ✅

✅ **`@can`-gating of controls is working in this realm.** ⚠️ **My first reading — "10 submit controls with
no hint they will be refused" — was FALSE** (§8).

---

## 6 · PHASE 3'S §8 LIST, WORKED — with counts

**15 entries. Result: 9 DONE · 4 DENIED-AS-EXPECTED · 0 UNEXPECTED · 2 DEFERRED.**

| entry | result |
|---|---|
| `/app/billing` — test refusal, expect 403 not blank | ✅ **DENIED-AS-EXPECTED** — 403, branded, actionable |
| `/app/roles` — the escalation question | ✅ **DENIED-AS-EXPECTED** — self-grant refused, `role_has_permissions` unmoved |
| `/app/settings`, `/app/security/ip-rules` | ✅ **DENIED-AS-EXPECTED** — both writes 403; reads open by design |
| `/app/rates` — Revenue Manager should reach | ✅ **DONE** for GA (holds `rates.manage`) |
| `/app/guests` — the PII perimeter | ⚠️ **DEFERRED** — GA holds `guests.view`+`guests.manage`, so the perimeter is not exercised by this role |
| Money paths (`refunds`/`payments`/`charge`) | ⚠️ **DEFERRED** — GA **holds** `folios.manage`; the denial belongs to a role that lacks it |
| `/app/payouts/*`, `/app/housekeeping`, `/app/maintenance/*`, `/app/schedule`, `/app/owners`, `/app/units`, `/app/portfolio`, `/app/reviews`, `/app/reports` | ✅ **DONE** — all reachable, all permissions held |
| The 19 ungated writes | ✅ **DONE** — `POST app/profile/preferences` accepted (302) and is self-scoped |

### ⚠️ What the list MISSED — improvements for Phases 5+
1. ⚠️ **It assumed every screen's denial is testable by every role.** **It is not.** For Group Admin, 2 of
   15 entries are *untestable by construction* because it holds the permission. **Each entry needs the
   role(s) that actually lack it named** — otherwise later phases will record false "DONE"s.
2. ⚠️ **It omitted the UI half.** Whether a screen *renders controls it cannot submit* is a separate
   question from whether the route refuses, and it is where the user-visible defect lives (§5).
3. ⚠️ **It omitted the 4 download routes** (`export.csv`, `export.pdf`, `export.xlsx`, `schedule/pdf`) —
   these return a **file, not a page**, and a naive probe reads them as a navigation failure (§8).

### 🔬 The 20 routes Phase 3 left unattempted
🖥 **10 sampled as Group Admin: ALL reachable, ZERO denied.** 6 rendered as pages
(`owners/invite/preview`, `email-previews`, `notification-preferences`, `payouts/run`, `unit-requests`,
`rack`); **4 returned downloads** (`reports/export.csv`, `maintenance/costs/export.csv`, `schedule/pdf`,
`security/export`).

---

## 7 · STATE — every table measured BEFORE being touched

🔬 **15 tables captured before any probe. 14 IDENTICAL. ONE MOVED:**

| | |
|---|---|
| ⚠️ **`audit_events` 146 → 148** | **RE-PINNED, with its cause.** 🔬 The two rows are **`maintenance.costs_exported`** (my CSV download) and **`user.preferences_updated`** (my permitted-half write). **Both are correct audit records of actions I really performed.** ⛔ **NOT deleted** — audit rows are never pruned, and deleting them to tidy a count would destroy an honest record. *(Phase 2 made the same call.)* |

✅ **UNMOVED:** `role_has_permissions` **260** · `permissions` **51** · `roles` **14** ·
`model_has_roles` **14** · `owner_ledger` **29 / 730640** · `payments` **16** · `reservations` **42** ·
`domain_events` **190** · `organizations` **1** · `properties` **2** · `taxes` **2** · `fees` **1** ·
`guests` **10** · `units` **8**.

⛔ **None of the 17 money-write routes was exercised.** ✅ **All 14 demo accounts authenticate.**
✅ **The seeder still refuses outside `local`/`testing`.** ⚠️ **The money gate was NOT run.**
⛔ **Suite not run — no application code changed.** ⛔ **F-1 through F-9 remain open; F-10 added.**
📎 Four files downloaded to `.playwright-mcp/` — 🔬 **gitignored**, outside the repo diff.

### ⚠️ An observation that sharpens F-9
🔬 A **maintenance-cost export** writes an audit row. 🔬 Phase 3 measured that a **password change writes
none.** ⚠️ **An export is audited; a credential change is not.** That contrast is stronger evidence for F-9
than F-9's own measurement was.

---

## 8 · ⚠️ NEAR-FALSE-POSITIVES — two more (running total **FOURTEEN**)

13. **"`/app/settings` offers 10 submit controls Group Admin cannot use, with no warning."** 🔬 **False.**
    The two `settings.manage` forms are **not rendered at all**; the 10 Save buttons belong to
    `properties.manage` sections GA legitimately holds. ⚠️ **I counted submit buttons on a page instead of
    checking which routes they post to** — the page was never the unit of analysis; the route is.
14. **"Four export routes are broken — they land on `/app/settings`."** 🔬 **False.** They are
    **downloads**: `page.goto` returns null for a file response and `location` stays on the previous page.
    ⚠️ **A probe that assumes every URL is a navigation misreads every download as a failure.**

⚠️ **Both were mine, and both looked like findings.** ✅ **A third was avoided by construction:** had the
team not been set, all 7 permitted screens would have refused — and the assertion in §3 is what would have
caught it.

---

## 9 · ⚠️ WHERE THIS BRIEF WAS WRONG

1. ⚠️ **"the SEVEN it lacks are the entire point of this phase."** 🔬 **Only THREE are testable here.**
   Two (`organizations.manage`, `api.manage`) reach no route anywhere, and two (`owner_portal.view`,
   `guest_portal.view`) belong to realms this phase does not audit. **The phase's real surface is 14 routes.**
2. ⚠️ **"establish whether Group Admin meets that [tax/fee] shape."** 🔬 **It does not** — it holds
   `properties.manage`. ✅ The question was right to ask; the answer relocates the finding to **Hotel
   Manager and Accountant**, which the record had not named.
3. ✅ **Correct and load-bearing, three times:** both directions on every gate (**the permitted half is what
   proved the team was set**); a string is not a gate (**every mapping resolved as `Authorize:`**); and
   attribute the acceptance too (**`role_has_permissions` at 260 is what proved the escalation failed —
   the 403 alone would not have**).
