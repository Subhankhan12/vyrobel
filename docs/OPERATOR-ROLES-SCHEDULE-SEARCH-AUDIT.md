# Operator Roles & permissions + Schedule PDF + Search — PHASE 1 Audit (task #102)

**Wireframes:** `Operator Roles.html` · `Operator Schedule PDF.html` · `Operator Search.html` (all decoded).
**Existing surfaces:** `/app/roles` (`OperatorRoles` + `RoleAssignmentService` + `RoleAssignmentController`) · `/app/schedule/pdf` (`ScheduleController::pdf` → `pdfs.operator-schedule` via `RosterWeek`) · `/app/search` (`OperatorSearch` + `SearchService`). All three are exact-match rebuilds of EXISTING surfaces.
**Date:** 2026-07-31. Status: PHASE 1 read-only complete; PHASE 2 gated build pending founder decisions.

---

## ROLES & PERMISSIONS — the matrix CAN be genuinely editable (mutation-provable)

**The key finding:** spatie stores role→permission in the DB pivot `role_has_permissions`; `$user->can()` reads it (cached, auto-flushed on write). `config/authorization.php` is the **one-time seed** (copied to the DB at org provisioning via `syncPermissions`); nothing re-seeds on login/schedule. So a team-scoped `Role::findByName($name,'web')->givePermissionTo()/revokePermissionTo()` inside `withTeam(orgId)` **persists AND flips `can()` immediately** — mutation-provable. The current code deliberately keeps the catalog read-only (only user↔role assignment is written).

| Roles element | Verdict | Backend |
|---|---|---|
| Role list (name/desc/level/members) | ✅ **REAL** | `RoleAssignmentService::overview()` |
| Read-only permission chips (grouped) | ✅ **REAL** | `groupedPermissions()` (reads `config('authorization.roles')`) |
| Members per role | ✅ **REAL** (name+initials) — **email = BUILD** | `overview()` embeds members; email not in the data |
| Assign / revoke a role to a user | ✅ **REAL** | `RoleAssignmentController` assign/revoke (subset + no-self + tenant guards) |
| **Editable permission MATRIX (toggle + Save)** | 🔨 **BUILD (Decision RL1)** | spatie DB pivot via a new `RolePermissionService`; genuinely gates (mutation-provable) |
| **New role (copy-from-existing)** | 🔨 **BUILD — constrained (Decision RL2)** | back-office access is gated by the **config** `back_office_roles` allow-list → a runtime role gets no `/app` access without a DB-aware change to `EnsureBackOfficeAccess` (#26) |
| **Delete role (move members)** | 🔨 **BUILD (Decision RL2)** | delete the org's team-scoped role + reassign its members first (no orphaned access) |
| **Last-admin safety** | 🔨 **BUILD** | refuse any edit leaving zero active org members able to `can('roles.manage')` — on BOTH the matrix-edit and assign/revoke paths |
| Gate + audit | ⚠️ **FIX** | today `can:users.manage`, NO audit. Matrix-edit → `can:roles.manage` (Property Owner + Super Admin = org-admin); add audit (#20) |
| Read-only guard on writes | ⚠️ **FIX** | `RoleAssignmentController` doesn't check `OperatorWorkspace::isReadOnly()` (a gap) |

⚠️ **Locked roles** (never editable — wildcard drift / lockout risk): **Super Admin** (global), **Property Owner** (`['*']` org admin), **External Owner** + **Guest** (portal roles, not back-office). Editable = the **9 non-wildcard back-office roles**. The catalog is a **fixed 49-permission list** (the mandate's "44" is stale) — toggle-only, never permission *creation*. **Risk:** `syncPermissions` is destructive — a future re-`provision()` of a live org would revert edits (no such call exists today; guard it).

---

## SCHEDULE PDF — the rota reconciles; add a summary + time-off + letterhead

The rota is REAL: `Shift` (user/date/shift_type/property) + `LeaveRequest` + the ONE `RosterWeek` builder (read by both the grid and the PDF → they reconcile, invariant #43). Staff = active `Membership` ∩ a back-office role (`StaffRoster`). The existing PDF (`pdfs.operator-schedule`, `schedule.view`-gated, dompdf via `PdfService`) renders the grid + legend + conflict count.

| Schedule element | Verdict | Backend |
|---|---|---|
| Weekly rota grid (team × days, shift/off/leave) | ✅ **REAL** (reconciles) | `RosterWeek::build()` (same builder as the grid) |
| Time off (name/kind/days/status) | ✅ **REAL** | `LeaveRequest` + `LeaveService` (`rangeLabel()`) |
| Property letterhead (name/logo/accent) | ⚠️ **FIX** | `PropertyBrand::for()` exists; the PDF hardcodes the mark → wire it |
| Summary (team / shifts / hours) | 🔨 **BUILD** (derived, reconciling) | derive from `$roster->rows`/`cells` + `ShiftType::hours()` — same builder, never a re-query |
| **Open shifts** | 🚫 **HONEST (Decision SC1)** | NO data model — every shift is bound to a user; "open/unassigned" doesn't exist → omit, or honestly redefine (e.g. days with no cover) |
| **"Provisional until published"** | 🚫 **HONEST (Decision SC1)** | NO publish/draft state exists → reword to a true line ("reflects the live roster"), or build a publish state |
| Property VAT | 🚫 **OMIT** | no property-level VAT source (VAT lives in the owner/tax domain) → omit; `Property.address` is available |

---

## SEARCH — cross-entity exists; add filters + counts + email-blind-index

`SearchService` searches reservations (`reference` OR lead-guest name), guests (**name only**), units (code/name), owners (name) — tenant-scoped, grouped into 4, ability-gated (guests.view/units.view). The operator sees FULL guest data (the manager — distinct from owner name-only #28), though results link to the profile rather than surfacing email.

| Search element | Verdict | Backend |
|---|---|---|
| Cross-entity search (reservations/guests/units/owners) | ✅ **REAL** | `SearchService` (LIKE, tenant-scoped, 8/group) |
| Grouped results + client tabs | ✅ **REAL** | the blade's Alpine tab filter |
| **Guest email search** | 🔨 **BUILD (blind index)** | `guests.email` is encrypted → search the deterministic `email_hash` (`Guest::emailHash()`) EXACT-match; phone has no index (deferred) |
| **Category counts (server-side)** | 🔨 **BUILD** | today client-side over a capped set → real `count()` per group |
| **Date / source / status filters** | 🔨 **BUILD** (real) | `check_in`/`check_out` dates; `source`/`status` plain strings (canonical sets in `FormatsOperatorData`) |
| **Occupancy filter** | 🔨 **BUILD-or-honest (Decision SE1)** | needs `PropertyOccupancy`/`PerformanceMetrics`; adults/children are real |
| **Recent searches** | 🚫 **HONEST/BUILD (Decision SE1)** | no store — client `localStorage` (real, per-browser) or honest omit |
| **Suggested** | 🚫 **HONEST/BUILD (Decision SE1)** | no backing — real "recent arrivals" or honest omit |
| **Sort** | 🔨 **BUILD** | fixed per-group today → a real sort control |
| **PropertyContext scoping** | ⚠️ **FIX** | search is tenant-only today; add `PropertyContext` (rule #38) where the wireframe filters by property/occupancy |

---

## ⚠️ CONSEQUENTIAL DECISIONS (founder approval before PHASE 2 — Roles is a sensitive security surface)

**RL1 — Editable permission matrix (the core).** BUILD it via the spatie DB pivot (team-scoped `givePermissionTo`/`revokePermissionTo` → persists + gates immediately, mutation-provable). Gate the edit on **`can:roles.manage`** (Property Owner + Super Admin = org-admin; excludes Group Admin), audited (#20), read-only-guarded. Editable = the **9 non-wildcard back-office roles**; **lock** Super Admin / Property Owner / External Owner / Guest. Guards: subset (grant only what you hold), out-rank, **last-admin** (never leave zero role-managers), catalog-permission-only, team-scoped. *Recommend BUILD* — this is the mandate's explicit ask and is safe + provable.

**RL2 — New-role + delete-role.** Back-office access is anchored on the **config** `back_office_roles` allow-list, so a runtime-created role gets no `/app` access without a **DB-aware change to the certified `EnsureBackOfficeAccess` (#26)** gate. Two paths: **(a) FULL build** — new-role creates a real DB role (copying an existing role's permissions) + registers it as back-office (an org-scoped allow-list the #26 gate also consults) + delete-role reassigns members then deletes; **(b) HONEST-SCOPE** — ship the editable matrix (the real capability) for the fixed catalog roles; new/delete deferred honestly (access is config-anchored; the 11 catalog roles are the set). *Recommend (b) HONEST-SCOPE* — it delivers the mutation-provable core the mandate emphasizes without touching the certified #26 security gate; new/delete can be a later, deliberate change.

**SC1 — Schedule honesty.** "Open shifts" has no data model and there is no publish state. *Recommend HONEST:* the summary (team/shifts/hours) is real + reconciling, time-off is real, the letterhead is real (`PropertyBrand`); **omit the fabricated "open shifts"** (or redefine as a real "days without cover" if wanted) and **reword "provisional until published"** to a true line (no publish state is built). VAT omitted (no source).

**SE1 — Search honesty.** *Recommend:* real date/source/status filters + server-side counts + guest-email blind-index + a real sort + PropertyContext scoping; **occupancy** filter via real `adults+children` band (or `PropertyOccupancy`); **recent searches** via client `localStorage` (real, per-browser); **suggested** = real recent arrivals (or honest omit) — never a fabricated result (#39).

---

## Build plan (gated)

- **GATE 1 — Roles:** the editable matrix (RL1) via `RolePermissionService` (spatie DB, team-scoped, mutation-proven a toggled permission gates) + last-admin safety + `roles.manage` gate + audit + read-only guard + member emails; new/delete per RL2. Exact design. Existing `OperatorRolesTest` green + new tests (a toggled permission gates; the last-admin guard).
- **GATE 2 — Schedule PDF + Search:** the PDF rebuilt (summary + time-off + `PropertyBrand` letterhead, reconciling; open-shifts/publish honest per SC1) + Search rebuilt (filters/counts/email-blind-index/sort/recent per SE1). Then consolidate docs + push.

🔒 the RBAC matrix edits real capabilities (mutation-proven), `roles.manage`-gated + audited + last-admin safety; the schedule PDF reconciles to the one `RosterWeek` builder; search operator-scoped (full guest data — the manager, distinct from owner name-only #28); #39 no fabricated permission/rota/result.

---

## FOUNDER DECISIONS (2026-07-31 — approved, PHASE 2 authorized)

- **RL1 → BUILD the editable matrix.** `RolePermissionService` writes `role_has_permissions` (team-scoped `givePermissionTo`/`revokePermissionTo`; persists + gates immediately, mutation-provable). Route re-gated `can:roles.manage` (org-admin: Property Owner + Super Admin). Editable = the 9 non-wildcard back-office roles; LOCK Super Admin/Property Owner/External Owner/Guest. Guards: subset + out-rank + last-admin + catalog-permission-only + team-scoped. Audited (#20); read-only-guarded. Member emails added.
- **RL2 → HONEST-SCOPE.** Ship the editable matrix for the fixed catalog roles + the last-admin safety. New-role + delete-role deferred honestly (back-office `/app` access is config-anchored; the certified `EnsureBackOfficeAccess` #26 gate stays untouched). The wireframe's New/Delete-role render an honest "the role catalogue is fixed — edit each role's permissions" state.
- **SC1 → HONEST schedule.** Summary (team/shifts/hours) real + reconciling from the ONE `RosterWeek` builder; time-off real; `PropertyBrand` letterhead. OMIT the fabricated "open shifts"; reword "provisional until published" to a true line (no publish state built). VAT omitted (no property source; address shown).
- **SE1 → REAL search + honest recent/suggested-derived.** Date/source/status filters + server-side counts + guest-email blind-index (`email_hash` exact-match) + sort + PropertyContext scoping. Occupancy via a real adults+children band. Recent searches via client `localStorage` (real, per-browser). Suggested = real recent arrivals. Never a fabricated result (#39).
- Read-only guard + audit added to the existing assign/revoke path (a gap); the last-admin guard applies to BOTH the matrix-edit and assign/revoke paths.
