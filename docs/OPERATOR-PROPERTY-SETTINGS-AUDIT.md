# Operator Property Settings — PHASE 1 Audit (task #98)

**Wireframe:** `docs/design/raw/Operator Property Settings.html` (decoded) · `docs/audit/design/proto-op-property-settings.png`
**Existing surface:** `/app/settings` (`app.settings`) — Livewire `App\Livewire\Operator\OperatorSettings` + `SettingsActionController` + certified `SettingsService`.
**Date:** 2026-07-31. Status: PHASE 1 read-only investigation complete; PHASE 2 gated build pending founder decisions below.

---

## Scoping verdict — keep `/app/settings` CONTEXT-scoped (do NOT add `{property}`)

The page is single-property, resolved through `PropertyContext` (the topbar switcher) via `ResolvesCurrentProperty`. A `{property}` route param would break the Livewire `/livewire/update` re-render contract that `ResolvesCurrentProperty` was explicitly designed around (that endpoint is outside the `/app` middleware group, so the context middleware doesn't run there). The wireframe header ("Seaside Villas · 45 rooms · Faro · part of Seaside Group") maps to `currentProperty()` + its org — no param needed.

---

## The decoded wireframe — 8 sections + 2 modals + save bar

1. **Property basics** — logo · display name · check-in from · check-out by · address
2. **Policies** — cancellation (Flexible/Moderate/Strict) · deposit-at-booking · pets (+€25 surcharge) · self check-in ("smart-lock codes 48h before")
3. **Tax & fees (summary)** — City tax €2/night · VAT 6% · included · Cleaning fee €45/stay · [Edit tax & fees]
4. **Owner** — Amara Okafor · 3 units · ~~15%~~ **18%** commission · View ›
5. **Property status** — Pause new bookings (existing stays keep running) · Remove from group
6. **Who can access** — team scoped to the property · Invite to property · pending invites · "What each role can do" (Admin / Front desk / Housekeeping)
7. **Guest-facing brand** — accent colour · logo · emails-come-from · reply-to · "Powered by Vyrobel" toggle · **live email preview**
8. **Taxes (detailed)** — City tax (Faro municipal, per-guest-per-night) · VAT (% · Incl./Added) · Fees · **Add a fee** (resort/parking/breakfast/custom · per-stay/night/guest · optional-at-booking) · **sample 4-night stay** (Room + city-tax + cleaning + fees + VAT = Guest pays)
+ **Add-a-fee modal** · **Remove-property modal** (scheduled archive-not-destroy, type-name-to-confirm) · **save bar** ("changes are live now")

---

## Section-by-section classification

| # | Section | Verdict | Real backend to reuse (anti-drift) |
|---|---------|---------|-------------------------------------|
| 1 | Display name / check-in-out / status | ✅ **REAL** | `SettingsService::updateProperty` (name/timezone/star/check_in_time/check_out_time/status) — already wired |
| 1 | Address | 🔨 **BUILD UI** | `properties.address` JSON column exists + fillable; no editor today → add to the form + whitelist |
| 1 | Logo | 🔨 **BUILD** | polymorphic `media` table (`Property use HasMedia`) — attach `meta.role='logo'`, OR a `properties.logo_path`. ⚠️ emails need a **public** URL (default media disk is `local`/private) |
| 2 | Pets / smoking / min-max stay | ✅ **REAL** | `PropertyPolicy` (pet/smoking/min_stay/max_stay) + `SettingsService::updatePolicies` |
| 2 | Cancellation (Flexible/Moderate/Strict) · deposit | 🔨 **BUILD UI** | `property_policies.cancellation`/`deposit` JSON columns EXIST but unwired → add editor + whitelist |
| 2 | Self check-in toggle | 🔨 **BUILD** | no column → add `property_policies.self_check_in` bool. ⚠️ the actual door code stays **GUEST-only (#33)** — the toggle is config only; the code is never shown here |
| 3+8 | **City tax** | ✅ **REAL** (computes on folios) | `Tax` (mode `per_person_night`) + `FolioService::computeTax()` → posted by `ReservationCreateController::postTaxes()` |
| 3+8 | **VAT** | ⚠️ **FIX** — see Decision A | `Tax` (mode `percentage`) + `computeTax()`. **`taxes.inclusive` is stored but IGNORED** → an "included" VAT is still ADDED on the real folio |
| 3+8 | **Cleaning fee** | ⚠️ **BUILD** — see Decision A | `Fee` + `FolioService::applyFee()`/`computeFee()` — **`applyFee` has ZERO callers**, so cleaning is configured yet never posted; owner attribution reads €0 |
| 8 | **Custom Add-a-fee** (basis + optional) | 🔨 **BUILD** — see Decision B | `fees` table has modes but no `optional`/name-create; settings can only edit amounts today |
| 8 | **Sample 4-night stay reconciles** | ⚠️ depends on Decision A | must call `computeTax`/`computeFee` (never re-implement) → reconciles by construction ONCE the folio actually does inclusive-VAT + posts fees |
| 4 | Owner + commission | ✅ **REAL** + **FIX 15%→18%** | no `property_owner` FK — resolve owners via `Unit.owner_id→Owner`; commission via `ManagementAgreement.commission_pct` + `effectiveOn`/`resolveFor` (real 18%; null→"Not set", never "0%"). Display pattern: `OperatorOwnerDetail` |
| 5 | Pause new bookings | ⚠️ **BUILD** (real fan-out) — see Decision E | `properties.status` is **label-only** today. Real pause = fan out to units reusing `OwnerUnitStatusService::pause` → `AvailabilityService::recompute` (counts only `is_active=true` into `total`) |
| 5 | Remove from group | 🔨 **BUILD** (state machine) — see Decision C | `SoftDeletes` primitive exists; the **scheduled archive-not-destroy** is new |
| 6 | Team scoped to property | ✅ **REAL** | `property_user` pivot + `Property::staff()`; pivot-or-all via `PropertyContext::accessibleIds()`; member display via `RoleAssignmentService::overview()` |
| 6 | Invite to property | ⚠️ **BUILD** (pivot write) — see Decision F | org invite `OwnerInviteService` (`can:users.manage`, hashed single-use 7-day token) is **org-scoped**; property-scoping = write a `property_user` row on acceptance |
| 6 | Role definitions | ✅ **REAL** + **FIX** | `config/authorization.php` (11 real roles). Show REAL caps (Admin→Group Admin/Property Owner, Front desk→Front Desk, Housekeeping→Housekeeping map cleanly), never a fabricated trio |
| 7 | Guest brand (accent/logo/from-name/reply-to/powered-by) | 🔨 **BUILD** (whole store) — see Decision D | no per-property brand store exists; all guest surfaces hardcode global Vyrobel terracotta. Anti-drift targets: `resources/views/emails/layout.blade.php` + `components/guest/shell.blade.php` |
| — | Save / read-only | ⚠️ **FIX** | `SettingsActionController` does **not** call `OperatorWorkspace::isReadOnly()` today (a gap vs peer controllers) → add the guard |

---

## ⚠️ CONSEQUENTIAL DECISIONS (require founder approval before PHASE 2)

### Decision A — Tax & Fees: the folio reconciliation (⚠️ changes money math on every reservation)

The mandate: the Tax & Fees here are "the SOURCE that flows to EVERY folio" and the sample-stay MUST reconcile to the real folio (#17). **Today it does not**, because the certified folio path has two real gaps:
- **VAT `inclusive` is ignored.** `FolioService::computeTax()` always ADDS `base × rate/100` as a positive line; the stored `taxes.inclusive` boolean is never read. A property set to "VAT 6% included" still gets 6% **added on top** — a double-charge relative to how PT accommodation VAT actually works (the rate is already inside the room price).
- **Cleaning/other fees are never posted.** `FolioService::applyFee()`/`computeFee()` exist but have **zero callers** — a configured €45 cleaning fee is charged to nobody, and owner statements read €0 for it.

**Real folio TODAY** for the wireframe's sample (Room €600, city tax €16, VAT 6%, cleaning €45): **€652** (Room 600 + city tax 16 + VAT **added** 36 + cleaning **0**). **Wireframe intends €661** (600 + 16 + 45, VAT included/informational). They cannot both be true.

- **Option A1 (recommended) — make the folio correct, so the sample reconciles by construction.** (a) `computeTax()` honors `inclusive` (an inclusive tax posts an **informational €0 line**, not an added charge); (b) wire `applyFee()` into a new `ReservationCreateController::postFees()` step so configured fees actually post. The sample preview then calls the SAME functions → reconciles to the cent. **This changes folio totals for every new reservation** — a real money-path change: I will verify each affected certified test individually (updating expectations only where the OLD number was wrong, never blindly), mutation-prove the sample == a real folio, and check the demo seed's VAT `inclusive` flag + whether any demo folio/owner-statement figure shifts (and re-seed/adjust honestly if so).
- **Option A2 — leave the folio as-is; make the sample honestly reflect it** (VAT €36 added, cleaning €0/omitted → €652). This keeps money math frozen but the page then **diverges from the wireframe's design** (no €661, cleaning shown as "not charged") — a documented KIND-2/KIND-3 divergence, and the tax/fees are NOT truly the folio source the mandate describes.

**My recommendation: A1** — it is the mandate's explicit intent, and the current behavior (VAT double-charge + uncharged cleaning) is a latent correctness bug. But it is a money change to the certified #84 path, so it is the headline thing to confirm.

### Decision B — Custom "Add a fee" (per-stay/night/guest + optional-at-booking)

The `fees` table has `mode` (flat/per_night/per_person/percentage) but **no `optional` flag and no create-from-settings** (only amount edits). To build the wireframe's Add-a-fee: extend `fees` with `name` + `optional` (optional-at-booking) columns and allow the settings page to create/name fees; per-guest = existing `per_person` mode. Optional-at-booking fees behave like the AddOns path (guest can add). **Recommended:** one coherent fee system (extend `fees` + wire `applyFee` from Decision A); AddOns remain for sellable products. (Depends on A1.)

### Decision C — Remove-property = scheduled archive-not-destroy (new state machine)

Almost entirely new. Build: new `properties` columns (`removal_scheduled_at`, `removal_effective_at`, `removal_requested_by`); the effective date = the last active reservation's checkout (computed at schedule time); a scheduler/command that archives (soft-delete) when reached; **cancellable** by clearing the timestamp before it runs; on archive → disconnect channels (`ChannelConnectionService`), end owner portal access, retain reservations/folios/statements + expose a 90-day export (`ExportDownload`); typed-name confirmation + `can:properties.manage` + audited. **Never a hard `delete()`** (`property_user` `cascadeOnDelete`). Billing-drop (units × €9): **no billing-unit coupling exists** (Phase-7 note) → show the figure honestly, label it as taking effect next cycle, don't fake a decrement. **Recommended** as described.

### Decision D — Guest brand store (build) + Postmark sender honesty

Build a per-property brand store (columns on `properties`: `brand_accent`, `brand_logo` [media], `brand_email_from_name`, `brand_reply_to`, `brand_powered_by` bool). Make the ONE shared email layout + the guest shell + review page brand-aware, **fail-open to today's Vyrobel defaults** (un-branded properties render byte-identically). Live preview = the real email layout with the brand applied. **Postmark constraint (honesty):** a per-property **from-name** (display) + **reply-to** are safe; an arbitrary **from-address** is blocked without domain verification → the "emails come from" address stays the fixed **verified** Vyrobel sender (shown read-only/honest), the editable pieces are the display name + reply-to. **Recommended.**

### Decision E — Property-level pause (real fan-out)

`properties.status` is label-only. A real "pause new bookings" fans out to the property's units (reuse `OwnerUnitStatusService::pause` per unit → `AvailabilityService::recompute`), so new bookings are actually refused while existing stays keep running (append-only). **Recommended.** (Resume fans back in.)

### Decision F — Property-scoped invite

The org invite (`OwnerInviteService`, `can:users.manage`, hashed token) is org-scoped. "Invite to property" reuses it + writes a `property_user` pivot row scoped to the current property on acceptance. Pending invites + role definitions read the real catalog. **Recommended.**

---

## Build plan (gated)

- **GATE 1 — basics · policies · owner · team/access · brand · property-status pause.** Exact design; real backends per the table; brand store + brand-aware layouts (Decision D); property-level pause fan-out (E); property-scoped invite (F); owner card at real 18% (FIX); read-only guard added; door code guest-only (#33). No folio money change in this gate.
- **GATE 2 — Tax & Fees (the folio source) + Remove-property + consolidate.** Decision A (folio: inclusive VAT + posted fees) + B (custom fees) + the sample-stay reconciling to a real folio (mutation-proven, to the cent, #17); Decision C (remove archive-not-destroy). Then consolidate docs + push.

Every write: controller-routed, `can:properties.manage` (or `users.manage` for invites), audited via `SettingsService`/`AuditEventRecorder`, read-only-guarded. 🔒 property-scoped; #17 sample==real-folio; #33 door-code guest-only; #39 no fabricated fee/total/brand/permission; commission real 18%.

---

## FOUNDER DECISIONS (2026-07-31 — approved, PHASE 2 authorized)

- **Decision A → A1 (fix the folio).** Honor `taxes.inclusive` in `computeTax()` (inclusive → informational €0 line) + wire `applyFee()` via a new `ReservationCreateController::postFees()` step. Sample reconciles by construction. Money-path change → per-test verification, mutation-prove sample==real folio, re-check the demo seed VAT flag + any shifted figure.
- **Decision C → full scheduled archive.** New `properties` columns + effective-date = last checkout + scheduler command + cancellable window + channels-disconnect/owner-access-end/90-day-export retention + typed-name confirm + gated + audited. Never a hard delete.
- **Decisions B / D / E / F → proceed per recommendation.** B: extend `fees` (`name`, `optional`) + `applyFee`. D: build per-property brand store + brand-aware shared layouts (fail-open to Vyrobel default); from-address stays the **verified** fixed sender (honest), from-name + reply-to editable (Postmark-safe). E: real property pause fans out to units. F: property-scoped invite writes a `property_user` pivot row on acceptance.
