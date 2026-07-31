# Operator Portfolio + Profile — exact-match audit (#96, PHASE 1, read-only)

**Pages:** `/app/portfolio` (`OperatorPortfolio`) · `/app/profile` (`OperatorProfile`) — both exist; being matched to their wireframes EXACTLY on REAL data.
**Wireframes:** `Operator Portfolio.html` · `Operator Profile.html` (dc bundles — decoded; copy in `scratchpad/wf-inventory-96.md`).
**Date:** 2026-07-31 · **Status:** PHASE 1 complete; awaiting the #66-security-sessions + preferences-depth + personal-fields decisions.

**Reuse map:** `PerformanceMetrics::forProperties` + `MetricSet` + `PropertyOccupancy` + `FrontDeskService` (analytics) · `PropertyContext`/`property_user` pivot-or-all (multi-property) · `PropertyController` (add-property) · `Channel.status` (honest fix-sync, #85) · spatie `config/authorization.php` (role caps) · `OperatorSecurity`/`ChangePasswordController`/`PreparesTwoFactor` (#66 password+2FA) · `OwnerSecurity::activeSessions`/`OwnerSecurityController@signOutOtherSessions` (the owner sessions impl to port) · `OperatorProfileController` (personal details).

---

## 1 · PORTFOLIO — per-element (`Operator Portfolio.html`)

**⚠️ MULTI-PROPERTY IS REAL** (Phase 6a/6b): multiple `Property` rows per org, each scoping its own room-types/units/availability/reservations; the demo org seeds **2** properties (Seaside + Vista); scope via `PropertyContext` + the `property_user` pivot-or-all rule. **No multi-property build needed** — it exists. The page already renders KPIs + property cards + a per-property comparison table (Occ/ADR/RevPAR/Revenue + Group) that reconciles Group=Σ by construction (#17, same `forProperties` for a row `[$id]` and the Group `$accessibleIds`).

| Element | Verdict | Backend |
|---|---|---|
| KPI Occupancy-tonight | ✅ REAL-NOW | `PropertyOccupancy::forProperties` |
| KPI Arrivals-today | ✅ REAL-NOW | `FrontDeskService::arrivals` per property |
| KPI **Revenue·month** | 🔨 BUILD | today the strip shows ADR·year; reuse `PerformanceMetrics::forProperties(...)->roomRevenueMinor` for the month (or the Dashboard payments−refunds) |
| KPI **Needs-attention** | 🔨 BUILD | real signals: setup-incomplete (no units) + `channels.status='error'` + HK/work-order counts |
| Property cards (initial/name/location/occ/units/arrivals/status) | ✅ REAL-NOW | `OperatorPortfolio` card builder |
| Card **ADR + Revenue** | 🔨 BUILD (real, add to card) | exists in the comparison table (`comparison()`); surface on the card |
| Card **country flag** | 🚫 honest-omit / 🔨 | no country column (`address` is freeform city only) — honest-omit or derive from a location string; never a fabricated flag |
| Card **Fix sync** | 🔨 BUILD, HONEST | real `channels.status`/`last_synced_at` (#85): "Action needed" (error) / "Connected" / "Not connected" — **NEVER a fabricated "synced"** (#39); demo Vista has no channel |
| Side-by-side Occ/ADR/RevPAR/Revenue + **Group** total | ✅ REAL-NOW | `comparison()` — reconciles Group=Σ (#17) |
| **Add-property** (name/location/type/rooms → finish setup; team roles carry over) | ✅ REAL-NOW / 🔨 read-only FIX | `PropertyController::store` (real `Property::create` + `property_user` carry-over = "team roles carry over"). ⚠️ **Read-only guard MISSING** on portfolio writes — add `OperatorWorkspace::isReadOnly()` |

---

## 2 · PROFILE — per-element (`Operator Profile.html`)

**The page exists but edits only name + email.** Personal-details / preferences / sessions are the builds; role-caps + password + 2FA are reuse.

| Element | Verdict | Backend |
|---|---|---|
| Header (name / role / property / **member-since**) | ✅ REAL-NOW | `user.name`, `getRoleNames()`, `PropertyContext`, `created_at` |
| Personal · Full name · Work email | ✅ REAL-NOW | `users.name`/`email` (editable, unique+verified via `pending_email`), audited |
| Personal · **Language** | 🔨 BUILD UI (real column) | `users.locale` exists — wire the picker (English/PT/ES/FR) |
| Personal · **Preferred name** · **Phone** | 🔨 BUILD | no columns — need a migration |
| Personal · **Timezone** | 🔨 BUILD / decision | no per-user column (timezone is a PROPERTY attribute today) — a per-user column vs show the property tz honestly |
| **Preferences** (compact-density / onboarding-tips / interface-motion / date-format) | 🔨 BUILD | **no preferences store** — none stored or applied. Interface-motion is only OS-level `prefers-reduced-motion` CSS (`operator.blade.php`); a stored/applied toggle is a build |
| **Role & access** (real caps + "managed by admin" + view-full) | ✅ REAL-NOW | spatie: `getRoleNames()` + `getAllPermissions()`/`can()` over `config/authorization.php` — a READ-ONLY display of the REAL caps (never fabricated, #39) |
| Security · **Password** (Change) | ✅ REAL-NOW (reuse #66) | `ChangePasswordController@operator` (`app.security.password`) — summarize + deep-link `app.security` |
| Security · **Two-factor** (On · Manage) | ✅ REAL-NOW (reuse #66) | `PreparesTwoFactor`/`app.security.2fa.*` — same state as the Security page |
| Security · **Last sign-in** | ✅ REAL-NOW | `users.last_login_at` |
| Security · **This device / active sessions** | 🔨 BUILD (port owner) | REAL on the OWNER side (`OwnerSecurity::activeSessions` — the `sessions` table + current-session flag + honest UA parse) but "Soon" on operator — port it |
| Security · **Sign out of all other devices** | 🔨 BUILD (port owner) | owner has it (`OwnerSecurityController@signOutOtherSessions`, deletes other `sessions` rows, audited) — port to operator |
| Read-only | ✅ confirmed | personal settings stay editable (no pause gate on profile/security — the established pattern) |

---

## 3 · The decisions

- **Multi-property → REAL (no build).** Confirmed: per-property analytics reconcile Group=Σ (#17). *(Caveat: a mixed-currency Group uses the first property's currency; fine for the single-currency demo — worth surfacing `MetricSet::multiCurrency` if mixed portfolios ship.)*
- **⚠️ #66 security sessions** — the Profile's "This device / active sessions / sign out all other devices" is REAL on the OWNER side but only "Soon" on operator. **Decision:** build the operator sessions + sign-out-all by porting the owner impl (extract a SHARED sessions helper — anti-drift, one definition), vs honest "Soon". *Recommend: build (port), since the mandate wants the #66 features real and the owner proves the pattern.*
- **⚠️ Preferences depth** — a real prefs store + genuine application. Motion (override `prefers-reduced-motion` with a stored toggle) + density (a body class) + onboarding-tips (a flag) are cheap+real; **date-format** applied workspace-wide is a huge retrofit. **Decision:** store all four + apply motion/density/tips genuinely + apply date-format via a shared formatter on the profile + new surfaces (incremental) vs honest-defer date-format. *Recommend: store all; apply motion/density/tips real now; date-format stored + a shared helper (applied incrementally, honest about scope).*
- **⚠️ Personal fields** — preferred_name + phone (new columns) + wire locale. **Timezone**: a per-user column vs the honest property-timezone display. *Recommend: build preferred_name + phone + locale editable; timezone = a real per-user column (small, and the wireframe treats it as a personal setting).*

## 3b · Decisions CONFIRMED (founder, 2026-07-31)
- **Multi-property → REAL** (no build; per-property analytics reconcile Group=Σ #17).
- **#66 security sessions → BUILD operator (port owner)**: extract a SHARED sessions helper from `OwnerSecurity::activeSessions` + `signOutOtherSessions` → real operator this-device / active-sessions / sign-out-all-other-devices (anti-drift, one definition).
- **Preferences → store all + apply real; date-format incremental**: a real store; interface-motion (stored toggle overriding `prefers-reduced-motion`), compact-density (body class), onboarding-tips (flag) genuinely applied now; date-format stored + a shared formatter applied on the profile + new surfaces (honest that a workspace-wide retrofit is incremental).
- **Personal fields → build preferred_name + phone + locale + a per-user timezone column** (all editable, audited).

## 4 · Guards (all gates)
🔒 operator-scoped; #17 Group totals = Σ properties (reconciling, by construction); #20 profile edits controller-routed + audited (self-only); the #66 security reused (password/2FA) + the owner sessions impl ported into a SHARED helper (anti-drift); #39 no fabrication (no fake sync/analytics/permission/security/flag — fix-sync honest per `channels.status`, role-caps the real spatie set); read-only respected (add the missing portfolio-write guard; personal settings stay editable by design). Anti-drift — reuse `PerformanceMetrics`/`PropertyOccupancy`/`FrontDeskService` · `PropertyContext`/`property_user` · `PropertyController` · `Channel` · spatie caps · `OperatorSecurity`/`ChangePasswordController`/`PreparesTwoFactor` · the owner sessions logic.

## 5 · Build plan
**GATE 1 — Portfolio:** the Revenue-month + Needs-attention KPIs (real) · card ADR/Revenue + honest Fix-sync (real `channels.status`) · the read-only guard · (country flag honest) → tests (KPIs reconcile; fix-sync honest) → browser → commit.
**GATE 2 — Profile:** personal details (preferred_name/phone/locale + [timezone per decision], editable + audited) · a real preferences store applied (motion/density/tips + date-format) · the role-caps display (real spatie) · Sign-in & security reusing #66 (password/2FA) + [operator sessions + sign-out-all per decision] → CONSOLIDATE docs → tests → browser → commit + PUSH.

## RESOLUTION _(GATE close — 2026-07-31, #96)_

Both pages matched to their wireframes on REAL data; all decisions taken as recommended. Two per-gate commits, pushed. Suite green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean.

**GATE 1 — Portfolio (`10169f4`).** Multi-property is REAL (Phase 6a/6b — no build). The KPI strip now matches the wireframe: **Revenue·month** (real room revenue, Σ properties) · Occupancy tonight · Arrivals today · **Needs-attention** (real derived: setup-incomplete + channel-error signals). The property cards carry per-property **ADR + room revenue** (this month) and an **HONEST Fix-sync** (the real `channels.status` → "Sync needs attention"/"Channels connected"/"No channels connected" + a Fix-sync link on a real error — NEVER a fabricated "synced", #85/#39). The side-by-side comparison switched to "This month" (Group = Σ reconciles by construction, #17). Added the **read-only guard** to `PropertyController` store/update (was missing). +5 `OperatorPortfolioGate1Test`.

**GATE 2 — Profile (this commit).** Migration adds `preferred_name`/`phone`/`timezone` + the 4 preference columns (`compact_density`/`onboarding_tips`/`reduce_motion`/`date_format`). **Personal details** (name/preferred-name/email/phone/language[locale]/timezone) editable, self-only, audited. **Preferences** stored + genuinely APPLIED — the operator layout reads the user's `reduce_motion` (`data-reduce-motion` overriding `prefers-reduced-motion`) + `compact_density` (`data-density="compact"`); date-format via the shared `DateFormat` helper (applied on the profile; incremental elsewhere). **Role & access** = a READ-ONLY display of the REAL spatie caps (key caps via `can()` + the full `getAllPermissions()` set — a Hotel Manager correctly shows payments+reports ✓, owner-payouts/team —; never fabricated). **Sign-in & security** reuses #66 (password/2FA link to the Security page) + REAL sessions via the new SHARED `App\Support\Security\ActiveSessions` helper (owner `OwnerSecurity` refactored to delegate to it — anti-drift) + a REAL **sign-out-of-all-other-devices** (`OperatorProfileController::signOutOtherSessions`, current-password-confirmed, deletes other `sessions` rows + rotates the remember-token). +5 `OperatorProfileGate2Test`.

**Honesty held:** no fabricated sync/analytics/permission/security — Fix-sync is the real `channels.status`; the role caps are the real spatie set; the sessions are real `sessions` rows; preferences are stored + genuinely applied (motion/density in the layout, date-format via the shared helper); multi-property is real (Group = Σ reconciles #17). #20 profile edits controller-routed + audited (self-only). Anti-drift — reuse `PerformanceMetrics`/`PropertyOccupancy`/`FrontDeskService`, `PropertyContext`/`property_user`, `PropertyController`, `Channel`, spatie caps, `OperatorSecurity`/`ChangePasswordController`, and the new shared `ActiveSessions` (owner + operator).
