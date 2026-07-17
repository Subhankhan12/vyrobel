# Vyrobel — AUTH & ONBOARDING SCREEN INVENTORY

**Purpose.** For every auth/onboarding design, say whether it is **✅ ALREADY BUILT**, **🟢 QUICK-BUILDABLE now** (a styled UI over auth backend that already exists — name the backing), or **🔴 BLOCKED on new backend** (name the exact gap + size). The load-bearing question is **signup**: is "Sign Up" a small registration-into-an-existing-org, or the **tenant-provisioning epic** (org creation) the Wave-4 audit flagged? Answered up front. **Analysis only — no app code changed; this doc is the only artifact.**

**Method.** Every auth/onboarding SPA export in `docs/design/raw/` was decoded (JSON-in-`<script type="__bundler/template">` + the `data-dc-script` logic class) and cross-checked against the REAL auth backend: `routes/web.php` (the auth routes), `LoginController` / `PasswordResetController` / `AcceptInviteController` + the `RedirectsByRole` concern, `LoginRequest`, `OwnerInviteService`, the `User`/`Guest` models, `config/auth.php` (the password broker), and the built `resources/views/auth/*` + `resources/views/emails/*`. Grounded in the repo at HEAD `f581c5f`.

**⚠️ Batch-wide fact (every design):** the design SPAs are client-only prototypes — no `fetch`/XHR/form-POST anywhere; handlers are `setState` + toast + redirects to sibling `.dc.html` mocks; the only persistence is `localStorage` (the two Security screens). So a "screen exists" tells you nothing about backend — each verdict below is grounded in the real Laravel code, not the mock.

**The design set (15 files).** Operator/owner auth: `Vyrobel Login` · `Vyrobel Reset Password` · `Vyrobel Set Password` · `Vyrobel Invite Owner` · `Owner Invite Email` · `Operator Security` · `Owner Security` · `Vyrobel Onboarding` · `Owner Onboarding`. Guest portal: `Guest Login` · `Guest Signup` · `Guest Verify Email` · `Verify Email` (a guest email template) · `Guest Account` · `Guest ID Verification`. **Genuinely MISSING designs:** a standalone SaaS **self-signup/register** screen (none — the Login just links to the onboarding wizard), a dedicated **Forgot-Password request** screen (only the reset *form* is designed; the request screen was built anyway), a standalone **2FA setup** screen (it lives inside the Security screens), and a **Password-Reset email** template (functional, no design).

---

# 🔴 THE SIGNUP VERDICT (read first)

**There is NO public SaaS self-signup in this product — not as a screen, and not as a backend. "Sign up" splits three ways, and only one of them is built:**

1. **Registration INTO an existing org = ✅ BUILT.** New operator/owner users are created ONLY through the **invite → set-password** flow (`AcceptInviteController` + `OwnerInviteService`). This is the app's real "create a user" path, and it is done, styled and security-hardened. `OwnerInviteService::invite()` mints a user for an owner **or** a staff role; `accept()` sets the password, activates, and logs in.

2. **Public self-signup that CREATES A NEW ORGANIZATION = 🔴 the tenant-provisioning EPIC.** The operator Login's only signup affordance is a **"Start a 30-day trial"** link that points straight at `Vyrobel Onboarding.dc.html` (the org-creation wizard). There is **no standalone signup screen and no backend spine**, verified against the real code:
   - **`Organization::create` / `new Organization` appears in ZERO `app/` files** — every occurrence is the seeder or a test.
   - **No register/signup controller or route exists** (a repo-wide grep returns nothing).
   - **`OrganizationRoleProvisioner::provision()`** (which installs the 13 per-org roles) has **zero production callers** — only the seeder + test helpers.
   - **Chicken-and-egg (from `docs/WAVE4-INVENTORY.md`):** at org-creation no user can hold `organizations.manage` (`Super Admin` is unassignable; `Property Owner` presupposes the org already exists), so it **can't be permission-gated**; `BelongsToOrganization` throws with no org context, so it **must run under `runAsSuperAdmin`**; and `/app` 403s a user with no org, so it **must live OUTSIDE `/app`** (like `/login`, `/invite/{token}`) — no such route exists.
   - **Size: L (subsystem/epic)** — create org → provision roles → create first user + log in → create property/building/floor → create room-types/units → set a base rate → seed availability → route into `/app`. It is an epic, not a screen.

3. **`Guest Signup` = a consumer guest account, NOT org creation — but 🔴 BLOCKED anyway.** The guest signup design creates only a guest profile (name/email/phone/password) — it names no organization, property or business. But **`Guest extends Model`, not `Authenticatable`**: guests have **no login, no password column, no auth guard** (the `/guests` routes are the operator CRM, not guest self-service). So guest signup is blocked on the **entire Guest Portal** (a Tier-2 net-new module), not merely a signup form.

**Bottom line:** the invited-registration flow is done; SaaS self-signup is the org-creation epic; guest signup is a different, unbuilt portal. Nobody should treat "Sign Up" as a quick screen.

---

# THE THREE LISTS

## ✅ ALREADY BUILT — the whole operator/owner auth chapter (Auth Phase A/B)
Every one is a working route + controller + a **styled** Blade view (the `vy-*` design system, inline). All are security-hardened (no enumeration, CSRF, rate-limited, session-fixation protection, role-routing).

| Screen | Design file | Route + backing | State |
|---|---|---|---|
| **Login** | `Vyrobel Login.html` | `GET/POST /login` → `LoginController` + `LoginRequest`; view `auth.login` | ✅ built + styled |
| **Forgot-password request** | *(no dedicated design)* | `GET/POST /forgot-password` → `PasswordResetController::showForgot/sendLink`; view `auth.forgot-password` | ✅ built + styled |
| **Reset password (set new)** | `Vyrobel Reset Password.html` | `GET/POST /reset-password` → `PasswordResetController::showReset/reset`; view `auth.reset-password` | ✅ built + styled |
| **Set password (accept invite)** | `Vyrobel Set Password.html` | `GET/POST /invite/{token}` → `AcceptInviteController`; views `auth.set-password` + `auth.invite-invalid` | ✅ built + styled |
| **Invite owner/staff (operator)** | `Vyrobel Invite Owner.html` | `POST /app/owners/invite` (`can:users.manage`) → `InviteOwnerController` + `OwnerInviteService` | ✅ built (modal on `/app/owners`) |
| **Owner/staff invite email** | `Owner Invite Email.html` | `resources/views/emails/owner-invite.blade.php` | ✅ built |
| **Password-reset email** | *(no design)* | `resources/views/emails/password-reset.blade.php` (Laravel broker notification) | ✅ built (functional) |

## 🟢 QUICK-BUILDABLE NOW — styled UI over auth backend that already exists
Small builds; the backend is Laravel-standard or already-present. **Note the thin use-cases honestly.**

| Screen / feature | Backing that already exists | Size | Note |
|---|---|---|---|
| **Change password** (logged-in operator + owner) | Trivial — the `User` password (`'hashed'` cast) + the exact rotate-remember-token / session pattern already in `PasswordResetController::reset`; gate like the Sub-gate-3 settings writers | **S** | The `Owner Security` design even ships a change-password modal (currently orphaned/unreachable). The one genuinely-quick sliver of the Security screens. |
| **Owner Onboarding — Step 1 (confirm profile)** | **Already built** — the Sub-gate-3 owner self-profile writer (`OwnerProfileController`, `user_id`-guarded) persists exactly name + email(+phone) | **S** (done) | The owner is already activated at `/invite`; a thin "welcome" wrapper over the existing writer is all that's left. Steps 2–3 are 🔴 (below). |
| **Email verification** (Laravel `MustVerifyEmail`) | The `users.email_verified_at` column exists; `MustVerifyEmail` is commented out on `User`; Laravel's verification routes/notification are one wiring step away | **S** | ⚠️ **Thin value:** users arrive via the tokened invite (email already proven) and there is no self-signup, so verification is largely redundant today. Quick if wanted; not urgent. |

## 🔴 BLOCKED on new backend — subsystems, not screens
| Screen / feature | Exact gap | Size |
|---|---|---|
| **Vyrobel (operator) Onboarding** — org first-run | The **tenant-provisioning spine** (org-create + first-user/register + role-provisioner caller + property/room-type/unit creation services + a pre-auth `runAsSuperAdmin` route outside `/app`). See the signup verdict. | **L (epic)** |
| **SaaS self-signup / "Start a 30-day trial"** | Same as above — it's the org-creation epic, with no standalone screen and no backend. | **L (epic)** |
| **2FA setup / verify** (inside `Operator Security` + `Owner Security`) | A real TOTP flow: enroll (secret + QR), verify-code, recovery codes, **and login-time enforcement**. The `users.two_factor_secret`/`two_factor_enabled` columns exist but **no code reads or writes the flow** (login never checks them). In the designs 2FA is only a toggle/score-factor + passive copy — not even a designed flow. | **M** |
| **Active sessions / trusted devices / "sign out everywhere"** (Security screens) | Session-tracking infra (device/location/last-seen per session) + revocation. None exists; the design rows are hard-coded. | **M** |
| **Connected apps (OAuth authorizations)** (Security screens) | A third-party-app authorization subsystem (Stripe/QuickBooks/Google/etc. grants). None exists. | **L** |
| **IP allowlist / login-approval / trusted locations** (Security screens) | Net-new access-control subsystems (IP rules on `/app`; new-device approval; geo trust). None exists. | **M–L each** |
| **Guest Login / Guest Signup / Guest Verify Email / Guest Account** | The **entire Guest Portal** — `Guest` is not `Authenticatable`; there is no guest guard, no guest password, no guest login route. A whole authenticated-guest subsystem. | **L (subsystem)** |
| **Guest ID Verification (KYC / SEF)** | A document-capture + identity-verification subsystem (real file upload/camera + a KYC provider + the Portuguese SEF guest-register filing). The design simulates everything (no `<input type=file>`, no upload). | **L + account-gated** |
| **Owner Onboarding — Step 2 (IBAN payout)** | **No bank/IBAN column exists anywhere** (a locked omission across six shipped owner screens) + a real micro-deposit verification rail (Stripe Connect throws). | **L + account-gated** |
| **Owner Onboarding — Step 3 (notification prefs)** | No notification-preferences table + no owner-notification dispatcher. | **M** |

---

# RECOMMENDED BUILD ORDER — the 🟢 quick-buildable set (fastest / safest first)

1. **Change password** (operator + owner, logged-in) — **S, highest value.** A guarded POST that re-uses `PasswordResetController::reset`'s exact pattern (hash the new password, rotate `remember_token` to sign out other sessions, keep the current session). Gate it to the acting user (like the Sub-gate-3 owner self-profile / settings writers). Genuinely useful, genuinely small, backend already proven.
2. **Owner Onboarding Step 1 as a "welcome" wrapper** — **S (mostly done).** A thin styled page over the *already-built* owner self-profile writer, shown once after invite-accept. Omit Steps 2–3 (they're 🔴). Only worth it if a first-run moment is desired — the writer already lives at `/owner/settings`.
3. **Email verification** — **S, but defer.** Wiring Laravel's `MustVerifyEmail` is quick, but with invite-only onboarding and no self-signup it verifies an already-proven email. Build it alongside self-signup, if that epic is ever scoped.

**Everything else in Auth/Onboarding is 🔴** — either the org-creation epic, a 2FA/session/OAuth subsystem, or the whole Guest Portal. None is a "quick styled build."

---

# PER-SCREEN DETAIL

## A · Operator / owner auth — ✅ ALREADY BUILT

### Login — `Vyrobel Login.html` → ✅ BUILT
- **Does:** authenticate an operator or owner; route by role (`RedirectsByRole::homeFor` — an `Owner.user_id` match → `/owner`, an active membership → `/app`, else a clean "no workspace" error). Session-based, CSRF, session regenerated on success (fixation protection), `last_login_at` stamped.
- **Backend (real):** `routes/web.php:76-78` → `LoginController` (create/store/destroy) + `LoginRequest`. **Rate-limited 5 attempts per (email + IP) per minute** with a `Lockout` event. View `auth.login` — **built + styled**.
- **Isolation/security:** no enumeration on the failure path; teardown-on-no-workspace so no half-usable session; logout invalidates + regenerates the session and token.
- **🚩 Design fabrication the build correctly OMITTED:** a **"Use single sign-on"** button (inert — no handler); a **"Start a 30-day trial"** signup link → the onboarding wizard; and the whole "pick an account" demo step. **No "remember me"** in the design *or* the build — `LoginRequest` documents it as **deliberately omitted** ("no long-lived credential cookie this pass"). The built login ships only the wired **"Forgot password?"** link. Nothing to do.

### Forgot-password request → ✅ BUILT (no dedicated design)
- **Does:** email a reset link to **active users only**, then always return the identical generic status (**no account enumeration**). The design set only draws the reset *form*; the request screen was built to match.
- **Backend (real):** `routes/web.php:87-88` (throttle 6/min) → `PasswordResetController::showForgot/sendLink` on Laravel's `Password` broker. View `auth.forgot-password` — **built + styled**.

### Reset password — `Vyrobel Reset Password.html` → ✅ BUILT
- **Does:** set a new password from a tokened link, then sign in + route by role.
- **Backend (real):** `routes/web.php:89-90` → `PasswordResetController::showReset/reset`; Laravel broker (`password_reset_tokens`, **60-min expiry**, single-use). On reset: password hashed, **`remember_token` rotated (signs out other devices)**, session regenerated, `PasswordReset` event, active-users-only. View `auth.reset-password` — **built + styled**. The design's "signs you out of other devices" copy matches the real remember-token rotation.
- **🚩 Fabrication:** none of note — no SSO/remember/signup. (Design min-length is 10 chars vs the built rule of 8 — a cosmetic mismatch, not a gap.)

### Set password / accept invite — `Vyrobel Set Password.html` → ✅ BUILT
- **Does:** the invitee (no usable password yet) sets a password → activate login + owner → spend the single-use token → auto-login → route by role (owner→`/owner`, staff→`/app`). Has form / done / **expired** states.
- **Backend (real):** `routes/web.php:99-100` (throttle 10/min) → `AcceptInviteController` (show/store) + `OwnerInviteService` (`findRedeemable`/`accept`). **Token security:** high-entropy random, **only its sha256 hash stored**, single-use, expiring; any invalid/expired/spent token yields the SAME generic "invalid link" page (no enumeration); session regenerated on activation. Views `auth.set-password` + `auth.invite-invalid` — **built + styled**.

### Invite owner/staff (operator screen) — `Vyrobel Invite Owner.html` → ✅ BUILT
- **Does:** an operator invites an **owner**, **manager**, or **accountant** — collecting name, email, role, assigned units, and a commission %.
- **Backend (real):** `POST /app/owners/invite` (`routes/web.php:162`, `can:users.manage`) → `InviteOwnerController` → `OwnerInviteService::invite(name, email, unitIds, commissionPct, role)`. Built as the invite modal on the `/app/owners` screen. **Security:** gated `users.manage` (invariant #30 — this is the privilege-escalation route Wave 2 hardened); assigns only a **validated** spatie role.
- **🚩 Fabrication:** the design's send is `setState`-only; the built flow really mints the invite + emails the tokened link.

### Owner/staff invite email — `Owner Invite Email.html` → ✅ BUILT
- **Does:** the transactional invite email; CTA **"Set your password"** → the tokened `/invite/{token}` accept link (owner + accountant variants). Copy: "no account is created until you set a password."
- **Backend (real):** `resources/views/emails/owner-invite.blade.php`, sent by `OwnerInviteService`. **Built.**

---

## B · Security screens — 🔴 mostly BLOCKED (one 🟢 sliver)

### Operator Security — `Operator Security.html` → 🔴 BLOCKED (net-new subsystems)
- **Design draws:** IP allowlist, data export, **active sessions**, **trusted devices**, **connected apps (OAuth)**. Every row is hard-coded; every mutation is a toast; the only persistence is `localStorage` (export prefs).
- **Backend reality:** **none of these subsystems exist.** No session-tracking, no OAuth-app authorization store, no IP-rules engine. **🚩 There is NO 2FA control and NO change-password field on this screen** (2FA appears only as passive "trusted device" copy).
- **Verdict:** 🔴 blocked — sessions/trusted-devices (**M**), connected-apps (**L**), IP allowlist (**M–L**). The lone quick piece is a **change-password** field (🟢 **S**, see the quick list), which this design omits.

### Owner Security — `Owner Security.html` → 🔴 BLOCKED (2FA is a toggle, not a flow)
- **Design draws:** a gamified "security score", 2FA/passkey/recovery-codes/login-alerts/strong-password as **score toggles** (no real setup), login-approval, trusted locations, connected apps, deactivate-account, and an **orphaned** change-password modal (its `openCp` trigger is never rendered — unreachable). Persists to `localStorage`.
- **Backend reality:** 2FA columns exist but no TOTP flow / no login enforcement; no passkey (`navigator.credentials`) path; no session/location infra; no deactivate mechanism.
- **Verdict:** 🔴 blocked — **2FA (M)**, sessions/locations (**M**), connected-apps (**L**), deactivate (**M**). The one 🟢 sliver is **change-password** (**S**) — the modal is designed but must be wired to a real guarded write and given a reachable trigger.

---

## C · Onboarding wizards — cross-referenced to `docs/WAVE4-INVENTORY.md`

### Vyrobel (operator) Onboarding — `Vyrobel Onboarding.html` → 🔴 BLOCKED (the epic)
- **Does (design):** 4 steps — Property (name/location/type) → Units (villa/apt counts) → base Rate + currency → Channels (Booking/Airbnb/Expedia) → "ready". All `setState`; nothing persists.
- **Backend reality:** this **is** the org-creation epic from the signup verdict — create org (no service) + first user/login (no register controller) + `provision()` (0 prod callers) + property/room-type/unit creation (no services) + `setRate`/`AvailabilityService::recompute` (the only real leaves) + channel connect (⬜ account-gated, and the `channels.status` writer is a separate dead-enum gap). Must run `runAsSuperAdmin`, outside `/app`.
- **Verdict:** 🔴 **L (tenant-provisioning epic)** — "not a screen." Never ship the channels step.

### Owner Onboarding — `Owner Onboarding.html` → 🟠 SPLIT (Step 1 buildable, 2–3 blocked)
- **Does (design):** 3 steps — confirm profile (name/statement email) → payout account (holder + **IBAN**) → notification prefs. All `setState`.
- **Backend reality:** the owner is **already activated** at `/invite` (this is not a second activation path). **Step 1 = 🟢 already built** (the Sub-gate-3 owner self-profile writer, `user_id`-guarded). **Step 2 (IBAN) = 🔴 L + account-gated** — no bank/IBAN column exists anywhere; the micro-deposit verification it promises has no rail. **Step 3 (prefs) = 🔴 M** — no preferences table/dispatcher.
- **Verdict:** ship Step 1 (done) + a next-steps card; **omit Steps 2–3** (the honesty rule — the IBAN step fabricates a bank-verification capability the product spent six screens refusing to fake).

---

## D · Guest Portal auth — 🔴 BLOCKED on the whole (unbuilt) Guest Portal

**The root gap:** `Guest extends Model` (not `Authenticatable`). There is **no guest login, no guest password, no `guest` auth guard, no guest self-service route** — guests exist only as operator-managed CRM records (encrypted name/contact). So every screen below is blocked not on "guest auth backend" but on the **entire Guest Portal subsystem** (Tier-2, net-new, no backend).

| Screen | Design does | Verdict |
|---|---|---|
| `Guest Login.html` | combined login/signup; "Keep me signed in"; **social (Google/Apple) buttons scripted to fail** | 🔴 L (Guest Portal) |
| `Guest Signup.html` | consumer guest account (name/email/phone/pw + marketing/terms); **social login present-but-failing**; fabricated "€25 welcome credit" | 🔴 L — a guest profile, **NOT** org creation (see signup verdict) |
| `Guest Verify Email.html` + `Verify Email.html` (email template) | auto-verify-on-link (prop-driven timer); the email is guest-branded ("Seaside Villas · Powered by Vyrobel") | 🔴 L (Guest Portal) |
| `Guest Account.html` | logged-in guest profile/cards/prefs/delete — `localStorage` only; **add-card captures a raw PAN** (design only) | 🔴 L (Guest Portal) |
| `Guest ID Verification.html` | KYC document + selfie capture for legal (SEF) check-in — **fully simulated, no real upload** | 🔴 L + account-gated (a KYC subsystem) |

---

# HONEST NOTES

- **"Screens" that are actually subsystems:** SaaS self-signup / Vyrobel Onboarding (org-creation epic), 2FA, sessions/connected-apps/IP-rules (each a Security subsystem), the whole Guest Portal, Guest ID Verification (KYC). None is a quick styled build — do not schedule them as "auth screens."
- **The auth backend that already exists and is done:** the entire operator/owner auth chapter — login, forgot/reset password (Laravel broker), invite/set-password activation (`OwnerInviteService`), the operator invite screen, and both transactional emails. It is **built, styled, and security-hardened**; there is nothing to "quick-build" there.
- **Security specifics (all present in the real code):** no user enumeration (login failure, forgot-password, and invalid-invite all return generic responses); reset tokens are Laravel's single-use 60-min tokens; invite tokens are **sha256-hashed, single-use, expiring**; every auth POST is CSRF-protected and **rate-limited**; sessions are regenerated on every credential change (fixation protection); the remember-token is rotated on reset (signs out other devices). **Remember-me is deliberately absent** — a design "keep me signed in" would be fabrication.
- **Signup must not escalate (if ever built):** the invite route is already gated `can:users.manage` (the escalation route Wave 2 hardened). A future self-signup epic must resolve the chicken-and-egg (`Super Admin` unassignable; `Property Owner` presupposes the org) and run `runAsSuperAdmin` **outside `/app`** — it cannot be a normal tenant-scoped request.
- **The one genuinely-useful quick win:** a **change-password** screen for logged-in operators/owners — small, backend-proven (the reset controller's own pattern), and the only Security-screen feature that isn't a subsystem.

*Read-only inventory. Every ✅/🟢/🔴 is grounded in the real repo (the named route/controller/service/broker/column) at HEAD `f581c5f`; the design SPAs were decoded only to enumerate what each screen draws.*
