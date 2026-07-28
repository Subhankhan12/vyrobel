# Owner Settings — wireframe-fidelity audit (the settings HUB)

**Date:** 2026-07-28 · **Author:** build session · **Status:** PHASE 1 (read-only) complete → proceeding to the gated build.

Founder brief: make the LIVE owner **Settings** (`/owner/settings`) match the decoded `Owner Settings.html` EXACTLY as a
HUB aggregating the many already-real owner features, HONEST where account-gated. The largest owner page (~13 sections);
MOST wire to features ALREADY BUILT — **reuse (anti-drift), don't rebuild.**

Wireframe decoded → `/tmp/wf_settings.txt` (1136 lines). Live today: the `/owner/settings` "Profile & account" page (the
#46 rebuild) — My profile (avatar/name/language/login-email) + Owner & business details (primary-owner-only) + a Security
link + a "More" row (Notification prefs / Payout account / Team). It's a real SUBSET; the hub expands it to all 13 sections.

---

## THE REUSE MAP + section-by-section classification

| # | Section | Class | REAL backend to reuse / honest resolution |
|---|---|---|---|
| 1 | **Hero** (completeness % · tier · channels/team/invoices stats) | ✅ DERIVED | computed from the real sections below — no new store. |
| 2 | **Profile** (avatar · name · email verify · phone) | ✅ REAL | reuse `OwnerAccountController` (`owner.account.update` / `.avatar` / `.email` + verify) + `user.locale`. **Phone-verify → HONEST:** the owner-party phone is stored but there is NO SMS verify — show/edit it, never a fake "Verified". |
| 3 | **Business & tax details** (legal name · VAT · company reg · tax country · billing) | ✅ REAL | reuse the ONE `owner.tax.save` path (built in Payouts; feeds the remittance PDF — anti-drift) + `owner.settings.update` (owner-party name/billing email/phone, primary-owner-only #42). |
| 4 | **Billing & invoices** | 🚫 GATED → HONEST | the OWNER has **no Vyrobel subscription/card** (that's the OPERATOR's Phase-7b billing, a different party). The owner's "billing" is the **management fee** = commission, deducted via statements. **RESOLUTION:** show the real fee arrangement (the **REAL agreement rate, 18% — read from `ManagementAgreement`, never the wireframe's hardcoded 15%**), "billed via your statements", and the real statement/remittance PDFs as the fee "invoices" (CAP_EXPORTS). NO owner card-on-file (never faked, #41/#39). |
| 5 | **Language & region** | ⚖️ REAL / 📐 honest | `user.locale` is REAL (already editable). currency/timezone/date-format are NOT stored per-user → shown read-only from the property (honest "set by your property"), never a fake writable control. |
| 6 | **Notifications** (per-event Email/SMS/Push + Quiet hours) | ⚖️ REAL / 🚫 gated | REAL store `NotificationPreference` via `owner.notification-preferences` — channels are **`in_app` + `email` ONLY**. **SMS + Push are NOT built** (no provider) → honest per-channel (labelled pending a provider), never a fake toggle. **Quiet hours** not stored → honest-defer. The Settings section summarizes + links the real full toggle page (anti-drift — one store). |
| 7 | **Connected channels** | ⚖️ REAL / 🚫 gated | **Direct / Vyrobel is REAL** (always-on). **OTA sync is ACCOUNT-GATED** (Channex stub — the super-admin Integrations audit established this): the connect/disconnect is the EXACT design, HONESTLY labelled pending the channel integration, NEVER a fake "Connected" that syncs nothing (#39/#37). |
| 8 | **Team access** (invite · roles · members) | ✅ REAL | reuse the owner-team feature (`owner.team` + invite/role/remove) — summarize (member count) + link (anti-drift). |
| 9 | **Appearance & motion** | ⚖️ REAL / 📐 honest | **Reduce motion is REAL** — the owner layout honors `@media (prefers-reduced-motion: reduce)` (respects the OS setting). Theme is not stored → honest-defer (light for now). |
| 10 | **Data export** (Bookings/Guests/Financials CSV · full JSON) | ✅ REAL | reuse the CAP_EXPORTS-gated exports (`owner.bookings.export`, `owner.statements.history.csv`, …, owner-scoped #28) + the owner-data export on the Security page ("Request my data"). |
| 11 | **Security & privacy** | ✅ REAL | link to the real `owner.security` page (password · 2FA · sessions · deactivate · data export). |
| 12 | **Profile completeness** | ✅ DERIVED | a checklist computed from the real profile / tax / payout-method / team state. |
| 13 | **Payout account** (masked IBAN · manage · session/sign-out) | ✅ REAL | the real masked IBAN via `OwnerNextPayout` (CAP_PAYOUT-only #28) → "Manage in Payouts"; sign-out via the real security session route. |

## Investigate resolutions (the judgment points)
- **Fee rate:** the wireframe hardcodes "15%" — render the **REAL agreement rate (18%)** from `ManagementAgreement`. (Same fix as Reports #61.)
- **Billing & invoices:** honest — the owner has no card/subscription; show the management-fee arrangement + the real statement PDFs as invoices. No fabricated invoice/card.
- **Notifications:** in-app + email REAL (link the real prefs page); **SMS + Push honest-pending** (no provider); Quiet hours honest-defer.
- **Connected channels:** Direct REAL; OTA honest-labelled pending the channel integration — never a fake "Connected".
- **Phone-verify:** honest (stored phone, no SMS verify — no fake "Verified").

## BUILD PLAN (gated by section-group)

**⛔ GATE 1 — Identity & money** (Hero · Profile · Business & tax · Billing & invoices · Payout account · Profile
completeness). Rebuild `owner-settings.blade.php` to the wireframe's two-column shell; wire Profile + Business/tax to the
EXISTING controllers (reuse), Billing to the honest management-fee view (**real 18% rate**) + real statement PDFs, Payout
account to the real masked IBAN (`OwnerNextPayout`, CAP_PAYOUT #28), and compute the Hero stats + completeness checklist
from the real state. Tests + browser + commit.

**⛔ GATE 2 — Preferences & access** (Notifications · Connected channels · Team access · Language & region · Appearance &
motion · Data export · Security). Wire each to its real backend (reuse) or the honest gated state: Notifications (real
in-app+email, SMS/Push honest-pending, → the real prefs page), Channels (Direct real, OTA honest-labelled), Team
(summary + link), Language (locale real, region honest), Motion (real reduce-motion), Data export (real CAP_EXPORTS
exports), Security (link). Consolidate docs (this file → resolved, DECISIONS #62, DIVERGENCE Settings row, PROJECT-STATE,
CLAUDE). Tests + browser (a real save + a real export) + commit + PUSH.

### Guards
🔒 #28 masked-IBAN-never-full + owner-scoped + no other-party PII · #17 the real fee rate · #20 controller-route writes +
audited (all writes reuse the existing guarded controllers) · #42 exports CAP_EXPORTS-gated · #39 nothing fabricated
(**no fake channel-sync / SMS / push / invoice / card / verified-phone**). Reuse the built features (anti-drift); DISTURB
NOTHING existing.
