# Operator Security + Settings + Team Schedule — Audit (PHASE 1, read-only)

**Scope:** exact-match rebuild of `/app/security` (`Operator_Security.html`), `/app/settings` (`Operator_Settings.html`), `/app/schedule` (`Operator_Team_Schedule.html`) — design + behaviour, on real data, building what's missing. Method: 3 wireframes decoded (`/tmp/wf-op/`), 3 parallel agents read every relevant subsystem + classified each element.
**Date:** 2026-07-31. Status: PHASE 1 complete — awaiting founder approval before PHASE 2.

Classes: **REAL-NOW** (already built — reuse) · **DEDUPE** (share the SAME existing store) · **BUILD** (build real) · **HONEST-GATED** (externally-blocked / no pipeline — stored-config or "Soon", never faked) · **NOT-BUILDABLE**.

---

## The four decisions that need your call

1. **Settings DEDUPE = SUPERSEDE-BY-LAYER (recommended).** `/app/settings` currently renders the **Property-Settings** wireframe (#98). `Operator_Settings.html` is the **canonical business hub** (11 sections) — the same property scope, re-drawn Property/Policy/Tax/Team **plus** 5 new subsystems (Invoice/SAF-T · Notification routing · Owner-statement cadence+commission · Your plan · Guest-message templates). The current blade already stubs "Invoice numbering" + "Notification routing" as *"Soon — net-new subsystems"* — this hub is what they waited for. **Plan:** rebuild the component+blade to the 11 sections, reuse **every** existing `SettingsService` writer, add the new *config fields* as columns on the **existing** tables (organizations / `property_policies` JSON / a small invoice-config addition) — **one store, no parallel model**. **Keep** the Property-Settings-only real backends (guest brand → live emails · `bookings_paused_at` → availability · fee CRUD · removal) wired — relocate/retain, don't delete.

2. **SAF-T / certified invoicing = config real, issuance HONEST-deferred.** No invoicing pipeline exists anywhere (grep-clean: no `invoice_number`/`series_prefix`/`saft`). **Buildable-real:** the config fields — Legal entity, VAT number (NIF), Billing city, Series prefix, Next number, Auto-issue flag — stored as real columns via the existing `SettingsService`. **Honestly deferred (never fabricated):** the certified numbered-invoice sequence, the emailed PDF at check-out, and the SAF-T (PT) export — exactly the VAT-filing-style deferral, labelled "Soon".

3. **Connected apps = HONEST.** The #72 OAuth infra is **owner-data-scoped only** (`OAuthScopes` = read:bookings/statements/units → owner caps; `ResolveApiOwnerContext` 403s a non-owner). There is no operator-facing app catalog. The wireframe's Stripe/QuickBooks/Google/Slack directory is a mock array. **Render the honest "No apps connected" empty state; drop the connect directory + disconnect flow** (fabricating a catalog would violate #37/#39).

4. **Scheduling — Publish-week + Open-shifts (build vs honest-defer).** The rota model is REAL (Shift / LeaveRequest / RosterWeek / ShiftType / StaffRoster / ShiftRosterService), but **no publish state and no open-shift concept exist** (`Shift.user_id` is non-nullable + unique, so an "open shift" can't be a Shift row). Recommended: **Publish-week = minimal-real** (a per-(property,week) `schedule_publications` row + a real notification to rostered staff on publish — edits stay live, "Publish" = finalize+notify, no draft buffer) and **Open-shifts = build** (a small net-new `open_shifts` table; filling one creates a real Shift + deletes the open row). Alternative: honest-defer either (keep the "live roster", hide the open-shifts rail). **Also a real bug to fix regardless:** the `ScheduleController` writes don't guard `isReadOnly()` though the paused banner shows — peers do; the rebuild must add the guard.

---

## SECURITY (`/app/security`) — reuse #66 + build IP-allowlist + honest apps + build export

| Element | Verdict | Reuse / plan |
|---|---|---|
| Overview "Protected" + 4 counts (sessions/apps/trusted/IP) | REAL-NOW | `count()` of the four lists |
| Active sessions + "This device" + Sign-out-all-others | REAL-NOW | already live (`ActiveSessions::forUser` + `app.profile.sign-out-others`, #104 GATE 3b). Per-session single sign-out = small BUILD |
| Change password · 2FA/TOTP | REAL-NOW | `ChangePasswordController::operator` · `PreparesTwoFactor` |
| **Trusted devices** (list + Revoke + skip-2FA-30d + empty) | **REAL-NOW (reuse, not owner-only)** | `TrustedDevice` is **user-scoped** (auth realm) + `TrustedDeviceService::forUser`; issuance is in shared auth (2FA login). Reuse the owner's list + a `revokeTrustedDevice` POST (self-only, re-auth, `forgetCookie`, audited). No "add" (trust issued at login) |
| **IP allowlist** (Add/Remove/empty "No IP restrictions") | **BUILD** (net-new) | `IpAllowRule` (`BelongsToOrganization`, ip/label/created_by) + `EnforceIpAllowlist` middleware on `/app` (after `EnsureBackOfficeAccess`) — **0 rules ⇒ allow** (the honest empty state); rules + no match ⇒ 403; fail-open no-tenant. Writes = audited POSTs, `can:settings.manage` + `@can` control gate. **Lockout mitigation:** auto-include the current request IP on first add + warn |
| **Connected apps** | **HONEST** | no operator OAuth → "No apps connected"; drop the fake directory |
| **Data export** (Request · JSON/CSV · date-range) | **BUILD** (extend owner `exportData`) | self-scoped `streamDownload`, JSON+CSV, All-time/This-year/Custom. **#28 scope:** the acting user's account + their **own** `audit_events` (name-only) — NOT org reservations/guest PII; throttled |
| Read-only | keep security usable when paused | `/app/security` is not read-only-gated (correct); keep the new IP/revoke/export writes outside `isReadOnly()` (account security stays available paused) |

## SETTINGS (`/app/settings`) — the deduped hub (one store)

| Section | Verdict | Reuse / plan |
|---|---|---|
| Property (name / check-in-from / check-out-by) | REAL-NOW / DEDUPE | `SettingsService::updateProperty` (`settings.property`) |
| Currency · time zone | REAL-NOW / DEDUPE | tz = `updateProperty`; currency = org `default_currency` (`updateOrganization`, `settings.manage`) |
| VAT rates (Accom % / Extras % / Prices-include-VAT) | REAL-NOW / DEDUPE | `updateTax($v,$inclusive)` (`settings.tax-fees`); **reconciles to the folio** via `StayEstimator`/`computeTax` (#17); inclusive toggle persists (#104 GATE 2c) |
| Invoice details (Legal entity / NIF / Billing city) | **BUILD config real** | new columns + `SettingsService::updateInvoiceDetails` on the SAME store |
| Invoice numbering (series/next#/issues-as) + Auto-issue | **BUILD config + HONEST issuance** | store config columns; numbered-invoice-gen + emailed PDF deferred |
| Certified series · SAF-T (PT) | **HONEST-GATED** | label "Soon" (no pipeline); never fabricate |
| Team & roles (members + Invite + roles link) | REAL-NOW / DEDUPE | `OperatorSettings::team` + `pendingInvites` + roles page |
| Payments & policies — City tax | REAL-NOW / DEDUPE | Tax row |
| Payments & policies — Pre-auth hold / Auto-charge no-shows | **HONEST-GATED** | no engine (live-Stripe seam); honest toggle/defer |
| Notification routing (Route-to-[role], Email/Push/SMS) | Email+in-app REAL · SMS/Push HONEST · **route-to-role net-new** | #79 prefs is per-USER not per-role → build a per-org event→role map OR honest-defer (current stubs "Soon") |
| Owner statements — commission % | REAL-NOW / DEDUPE | real **18%** (`OwnerCommissionRate`, never 15%); finalize/payout cadence = real display (`PayoutSchedule` #71) + owner-portal link |
| Your plan (Vyrobel Pro €99/mo / Manage) | REAL-NOW / DEDUPE | `SubscriptionStatus`/`SubscriptionPricing` state real; €99 = configurable placeholder (honest); Manage → `app.billing` (#85) |
| Cancellation & deposit — tier + deposit-required | REAL-NOW / DEDUPE | `PropertyPolicy.cancellation[tier]`/`deposit[required]` (`updatePolicies`) |
| Cancellation & deposit — free-cancel days / deposit % / late-no-show fee | **BUILD (same JSON store)** | extend the existing `cancellation`/`deposit` JSON; enforcement-at-cancel a later engine (config real) |
| Booking rules — min / max stay | REAL-NOW / DEDUPE | `PropertyPolicy.min_stay`/`max_stay` (booking-time authority is `RateAndRestrictionService`) |
| Booking rules — bookable-ahead / advance-notice / arrival-days | **BUILD or HONEST** | new `property_policies` fields if built; else honest-defer |
| Guest messages (Booking confirmation / Pre-arrival) | REAL-NOW / DEDUPE | `EmailTemplateCatalog` 'pre-arrival' + `BookingConfirmationMail` → read-only summaries linking the Email editor |

## TEAM SCHEDULE (`/app/schedule`) — the interactive rota

| Element | Verdict | Reuse / plan |
|---|---|---|
| Team × days grid · per-cell assign · week nav · dept filter · time-off · coverage · PDF | REAL-NOW | `RosterWeek` + `StaffRoster` + `ShiftRosterService::assign` + `LeaveService`; PDF = same builder |
| Today / Week day-view | **BUILD-REAL** (presentation) | `RosterWeek::dayGroups($i)` (no schema) — one model for grid + day-view + PDF |
| Add-shift modal | **BUILD-REAL** (presentation) | POSTs the EXISTING `app.schedule.assign` |
| Per-dept coverage bars | **BUILD-REAL** | `RosterWeek::coverageByDept()` |
| Drag-move / swap | **BUILD-REAL** | Alpine → new `move` POST = 1–2 `assign()` in a txn **through** `ShiftRosterService` (guard holds) |
| **Publish-week** | **BUILD (minimal-real) or defer** | `schedule_publications` (property,week) + notify on publish; edits stay live |
| **Open shifts** | **BUILD (net-new table) or defer** | `open_shifts` table; fill = create Shift + delete open row (1 txn) |
| "Notified on device" | **BUILD-REAL or drop claim** | a real `ShiftPublished/AssignedNotification` (FeedNotification infra exists) |
| Read-only | ⚠️ **BUG-FIX** | `ScheduleController` writes don't guard `isReadOnly()` (peers do) — add the guard + hide affordances when paused |

---

## Reuse map (anti-drift — reuse, never duplicate)
#66 security (`ActiveSessions` · `PreparesTwoFactor` · `TrustedDevice`/`TrustedDeviceService`) · #72 OAuth (owner-only — apps stay honest) · owner `exportData` pattern · `SettingsService`/`SettingsActionController` (the ONE settings store) · `StayEstimator`/`FolioService::computeTax` (#17) · `OperatorSettings::team` + `OwnerInvite` · `NotificationPreferences` (#79, per-user) · `OwnerCommissionRate` (18%) · `SubscriptionStatus`/`SubscriptionPricing` (#85) · `PayoutSchedule` (#71) · `EmailTemplateCatalog`/`BookingConfirmationMail` (#83/#86) · `RosterWeek`/`ShiftRosterService`/`LeaveService`/`StaffRoster` (the ONE rota model) · `OperatorWorkspace::isReadOnly()` · `AuditEventRecorder`.

## Build plan (gated) — Security → Settings → Team Schedule, per-gate commit + push; suite green + new tests; PHPStan L5 + Pint; browser-verified.

---

## FOUNDER DECISIONS (2026-07-31 — approved)
1. **Settings DEDUPE → SUPERSEDE-BY-LAYER** over the ONE `SettingsService` store (rebuild the hub; reuse every writer; new config = columns on existing tables; keep Property-Settings-only real backends wired).
2. **SAF-T → config real, issuance HONEST-deferred** (store NIF/legal-entity/series/next#/auto-issue; the certified sequence + PDF + SAF-T export are "Soon", never fabricated).
3. **Scheduling → BUILD BOTH (minimal-real):** Publish-week (`schedule_publications` + notify; edits stay live) + Open-shifts (`open_shifts` table; fill = create Shift + delete open). Plus the read-only bug-fix + the presentation items (day-view, add-shift, drag-move, coverage bars).
4. **Connected apps → HONEST** "No apps connected" (no operator OAuth catalog; drop the mock directory).

Build order: GATE 1 Security · GATE 2 Settings · GATE 3 Team Schedule. Per-gate commit + push.
