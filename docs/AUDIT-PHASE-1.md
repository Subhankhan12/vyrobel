# AUDIT PHASE 1 — the authentication surfaces

**2026-09-12.** 🖥 **Instrument: MCP Playwright** (the `.mcp.json` server from Phase 0 §4a), driving
`chrome-headless-shell` · HeadlessChrome/151.0.7922.34 · served by `php artisan serve` on
**`http://localhost:8000`** — ⚠️ **the `APP_URL` origin, deliberately**, per Phase 0's FP-1 lesson.

⚠️ **Validated in BOTH directions before any finding:** known content echoed exactly · a **negative control**
correctly reported an absent element · a **wrong-value control** correctly rejected a false match.

### The four marks — only 🔬 and 🖥 are facts
**🔬 MEASURED** executed and observed · **🖥 BROWSER** driven in a real browser · **📖 ASSERTED** read from
source, never run · **📄 RECORDED** written by an earlier pass, never re-checked.

### ⚠️⚠️ PROGRAMME-WIDE CONSTRAINTS, CARRIED FORWARD — restated so no phase rediscovers them
1. ⛔ **MULTI-TENANCY IS *NOT TESTED*, AND CANNOT BE.** 🔬 **One organisation exists.** There is no second
   tenant's data for any surface to leak. **Not a pass — an untestable dimension.**
2. ⛔ **FIVE PERMISSIONS BELONG TO NO AUDITABLE ROLE** — `billing.manage` · `roles.manage` ·
   `organizations.manage` · `settings.manage` · `api.manage`. They are reachable only through the two
   wildcard roles. **No phase in this programme can exercise them. A PERMANENT COVERAGE HOLE.**

---

## 0 · Verdict

| | |
|---|---|
| **Doors enumerated** | 🔬 **80 auth-related routes → 24 distinct doors**; **43 routes reachable with no session** |
| **Nothing severe found** | ⚠️ **No authentication bypass, no replayable token, no door admitting the wrong person.** |
| **New findings** | **3** — one MEDIUM (a silent interruption), one MEDIUM (a public 500), one LOW (misleading error copy) |
| **Regressions** | **NONE** |
| **Application code changed** | **NONE** |

---

## 1 · THE DOORS — 🔬 enumerated, not assumed

🔬 **80 auth-related routes** from `route:list`. **43 reachable without a session** — the internet-facing
surface — and **37 behind `auth`.** Grouped into **24 distinct doors**:

**Reachable with NO session (the attack surface) — 17 doors**
`/login` · `/signup` (⚠️ **provisions an organisation**) · `/forgot-password` · `/reset-password/{token}` ·
`/guest/login` · `/guest/claim` + `/guest/claim/verify/{guest}/{hash}` · `/guest/forgot-password` ·
`/guest/reset-password/{token}` · `/invite/{token}` (owner invitation) · `/team-invite/{token}` (owner-team
invitation) · `/auth/google/redirect` + `/callback` · `/two-factor-challenge` · `/passkeys/login` (+options) ·
`/login-approval` + `/login-approval/approve/{approval}` · `/admin/login` + `/admin/two-factor`
(**the platform-admin door**) · `/admin/account/email/verify/…` · `/owner/account/email/verify/…` ·
`/oauth/authorize` · `/oauth/device` · `/oauth/token`

**Behind a session — 7 door-families**: security-page password/2FA/passkey/trusted-device writes in both
realms, owner-team invite issuance, operator owner-invite issuance, Passport approve/deny.

⚠️ **`POST /guest/logout` carries only `[web]`** — no `auth:guest`. 📖 Harmless (logout with no session is a
no-op, and the `web` group still enforces CSRF) but recorded because it is the one route in the guest realm
whose middleware does not match its siblings.

---

## 2 · SECURITY — 🖥 proven by WRITES, never reads

⚠️ **Every result below is a POST that was actually sent.** Standing rule: *never assess access with reads
alone.*

| what was tested | 🖥 result | severity |
|---|---|---|
| **User enumeration** — known account + wrong password vs unknown account | ✅ **Indistinguishable.** Both return *"These credentials do not match our records."*, same URL, same markup | — |
| **Rate limiting** — 7 consecutive failures on a throwaway address | ✅ **Throttles at attempt 6**: *"Too many login attempts. Please try again in 53 seconds."* | — |
| **Session regeneration on login** (fixation defence) | ✅ **Regenerated** — session id differs before/after. Cookie `httpOnly: true`, `sameSite: Lax`, `secure: false` (⚠️ correct for HTTP dev; **`SESSION_SECURE_COOKIE` is GO-LIVE `B14`**) | — |
| **Logout invalidation** — replay the pre-logout cookie against `/app/dashboard` | ✅ **Invalidated.** Replay lands on `/login` | — |
| **CSRF on login** — correct / wrong / absent token | ✅ correct → **302 to `/app/dashboard`** · wrong → **419** · absent → **419** | — |
| **Password-reset rate limiting** | ✅ **429 "Too many attempts"** reached during probing | — |
| **Seeder guard** (§5) | ✅ refuses on `staging` and `production`, **including `--class=`** | — |

⚠️ **The control matters:** the correct-token case was included precisely so a 419 could not be mistaken for
"the probe cannot log in at all". **It succeeded, so the rejections are real rejections.**

### 🖥 Token-bearing doors — TAMPERED token, all 8 tested
⚠️ **These are FIRST-CONTACT paths. A broken link here is a customer who leaves, and nothing reports it.**

| door | 🖥 tampered-token response |
|---|---|
| `/invite/{token}` (owner invitation) | **200** — *"This invitation link isn't valid — It may have expired or already been used. Please ask your operator to send a new invitation."* + **"Go to sign in"** ✅ **honest, branded, actionable** |
| `/team-invite/{token}` | **200** — identical copy ✅ |
| `/guest/claim/verify/{guest}/{hash}` | **403** — *"This page belongs to a different booking, or it isn't part of your account."* ✅ scoped and accurate |
| `/owner/account/email/verify/…` | **404** — *"We couldn't find that"* ✅ |
| `/reset-password/{token}` | **200 — renders the reset form.** 📖 Laravel validates the token on POST, not GET. ⚠️ It echoes the email **from the query string**, i.e. the attacker's own input — **not** an enumeration oracle |
| `/guest/reset-password/{token}` | **200** — renders the form and **asks for the email** rather than echoing it |
| `/admin/account/email/verify/…` | **403** — ⚠️ **but the wrong copy** (F-3 below) |
| `/login-approval/approve/{approval}` | **403** — signature correctly refused (`ValidateSignature` + `throttle:12,1`), ⚠️ **wrong copy** (F-3) |

---

## 3 · THE PASSWORDLESS GATE — 🖥 driven in a browser, both halves

**Method.** 🔬 A real session was established while `demo-staff` **still had** a password; the account was
then made passwordless **mid-session** (`users.password = NULL`); the identical confirmation POST was
re-sent. ✅ **The hash was captured first, restored byte-identical after, and all 14 demo accounts verified
to authenticate again** (§8).

⚠️⚠️ **THE URL IS NOT A DISCRIMINATOR — PROVEN, NOT ASSUMED:**

| account state | confirmation POST → | landed on |
|---|---|---|
| **has a password** | **302** | `/app/security` |
| **passwordless** | **302** | `/app/security` |

🖥 **Byte-identical outcomes.** A URL-based check would pass in both directions **and would keep passing if
the gate were deleted.** The real discriminator is the middleware's flash key **`password_status`**.

✅ **The negative half holds.** While passwordless: `/app/dashboard` **200, not interrupted**; a plain GET of
`/app/security` **200, not interrupted**. 📖 The trigger is `$request->has('current_password')` — nothing
role-, permission- or team-scoped, so the `can()`-without-a-team trap cannot fire here.

### ⛔ F-1 · THE INTERRUPTION IS SILENT IN THE OPERATOR REALM — **NEW · MEDIUM**

**What was tested.** After the interruption, what does the person actually see?

**What was found.** 🖥 **Nothing.** `flashPresent: false`; no alert region on the page; `h1` is just
*"Security"*.

🔬 **Cause, located in source:**
- The middleware sets `password_status` and redirects to `owner.security` for owner paths, **`app.security`
  for everything else** (`RequirePasswordBeforeReauth:68-73`).
- 🔬 **`password_status` is rendered in exactly ONE view** — `livewire/owner/owner-security.blade.php:21`.
- 🔬 The operator view `livewire/operator/operator-security.blade.php` renders `security_notice`,
  `security_error`, `connections_status`, `connections_error` — **and no `password_status`.**

**Consequence.** A passwordless **operator** clicks a sensitive action, is silently moved to the Security
page, and is told **nothing**. To them the click simply did not work. ⚠️ **This is the precise failure the
gate exists to prevent** — an unexplained lockout — reproduced one layer out. The owner realm is correct.

⚠️ **It is wider than the gate.** 🔬 `ChangePasswordController:74` flashes the **same key** for *"Your
password has been changed."* — so an operator gets **no confirmation of a successful password change**
either.

**Severity: MEDIUM by consequence.** ⚠️ **Not a security defect** — the gate denies correctly and the
password-set field is present on the page. It is a comprehensibility defect on a security screen.
**EVIDENCE TIER: 🖥 BROWSER-VERIFIED** (absence on the rendered page) **+ 🔬 MEASURED** (the one view that
renders the key). **CLASS: NEW.**

---

## 3b · 🖥 TOKEN DOORS IN ALL FOUR STATES — the gap Phase 1 came back to close

⚠️ An earlier draft covered only the **TAMPERED** state and said so. **Two doors are now covered in all
four**, driven through MCP Playwright against genuinely minted tokens.

**Method.** 🔬 Three scratch `owner_invites` rows were created with **known** tokens (`token_hash` is
`sha256(raw)`), in the three states the schema distinguishes, plus a **real** framework-issued
`password_reset_tokens` row. ✅ **All removed afterwards; `owner_invites` is back to 0, its value at phase
start.**

### Owner invitation — `/invite/{token}`
| state | 🖥 result |
|---|---|
| **VALID** (pending, expires +7d) | **200** — *"Set your password — Welcome, Demo Owner, choose a password to activate your Vyrobel account"*, with real `password` + `password_confirmation` fields ✅ **the happy path works** |
| **EXPIRED** (pending, expires −3d) | **200** — *"This invitation link isn't valid. It may have expired or already been used. Please ask your operator to send a new invitation."* + **"Go to sign in"** |
| **ALREADY-USED** (accepted) | **200** — **identical copy** |
| **TAMPERED** | **200** — identical copy |

✅ **The identical copy for expired / used / tampered is correct, not lazy**: it refuses to disclose *which*
token state occurred, while still telling the person what to do next. **A first-contact path that fails
this well is a real strength** — it is the difference between a customer who asks for a new link and one who
silently leaves.

### Password reset — single-use, 🖥 proven in the browser
| step | 🖥 result |
|---|---|
| **Control — first use of a fresh token** | **302 → `/app/dashboard`** (reset applied, user signed in). ⚠️ **Included deliberately so a later rejection could not be mistaken for "the probe cannot succeed at all."** |
| **REPLAY of the now-consumed token** — fresh session, **fresh CSRF**, limiter clear | **302 → back to the reset form**, `rateLimited: false`, `csrfRejected: false` — **it does NOT reach the dashboard** |
| 🔬 Data layer | The `password_reset_tokens` row is **deleted on successful use** (0 rows), so a replay has nothing to match |

✅ **VERDICT: the reset token is SINGLE-USE.** Two independent lines of evidence agree, and the control
proves the probe could have seen success had it occurred.
⚠️ **Bounded honestly:** the *literal* error wording on the replay was never rendered — the 429 limiter
intervened on the follow-up. **The rejection is proven; its copy is not.**

---

## 4 · GOOGLE'S OFF-STATE — 🖥 all three confirmed

| claim | 🖥 measured |
|---|---|
| **no button** | ✅ `/login` has **no Google href and no Google text**. Its controls are exactly: *Forgot password?* · *Sign in* · *Sign in with a passkey* · *Create an account* |
| **both routes 404** | ✅ `/auth/google/redirect` **404**, `/auth/google/callback` **404**, both with the branded *"We couldn't find that"* page |
| **every existing account still signs in** | ✅ **14 of 14** demo accounts authenticate (§8) |

✅ **The absence is HONEST**: 404 because the controller refuses while `services.google.client_id` is empty
— not a button leading to an error page, which is the defect this design avoids.

⚠️ **KNOWN documentation defect, carried from Phase 0, unchanged:** `routes/web.php:254` and `GO-LIVE` **B18**
both say *"no button, **no route**, no error page"*. 🔬 The **routes are registered** (they appear in
`route:list`) and there **is** an error page (the branded 404). Two of three clauses are wrong.
**CLASS: KNOWN** (Phase 0). **Not re-reported as new.**

---

## 5 · THE SEEDER GUARD — 🔬 still refuses, `--class=` included

🔬 Allow-list: `DemoDataSafetyGuard::DEVELOPMENT_ENVIRONMENTS = ['local', 'testing']`.

| path | `APP_ENV=staging` | `APP_ENV=production` |
|---|---|---|
| `db:seed` (parent) | ✅ **RuntimeException** — *"Refusing to seed the demo database…"* | — |
| `db:seed --class=DemoSeeder` | ✅ **RuntimeException** — *"Refusing to seed the demo organisation…"* | ✅ refuses (even with `--force`) |
| `db:seed --class=PlatformAdminSeeder` | ✅ **RuntimeException** — *"Refusing to seed a demo platform admin…"* | — |

⚠️ **The `--class=` hole is closed at the right layer:** `ensurePermitted` is called inside
`DatabaseSeeder`, `DemoSeeder` **and** `PlatformAdminSeeder` individually — not only in the parent.
✅ **AND THE GUARD IS NOT VACUOUS:** 🔬 `APP_ENV=local db:seed --class=BillingPlanSeeder` still runs
(*"INFO Seeding database"*). **Proven in both directions.**

### ⛔ F-2 · `/oauth/device` RETURNS 500 TO AN UNAUTHENTICATED VISITOR — **NEW · MEDIUM**

🖥 `GET /oauth/device` with no session → **HTTP 500**,
`Illuminate\Contracts\Container\BindingResolutionException`, rendered as the framework's debug page.

It is a **registered, publicly reachable route** (middleware `[web]` only). ⚠️ **Not an auth bypass** — it
grants nothing. But an unauthenticated 500 on a public endpoint is an unhandled error, and **with
`APP_DEBUG=true` it renders a stack trace of framework internals.** In production `APP_DEBUG=false` would
reduce it to a generic 500 — the error itself remains.
**Severity: MEDIUM. EVIDENCE TIER: 🖥 BROWSER-VERIFIED. CLASS: NEW.**

⚠️ **`GET /oauth/authorize` carries only `[web]` — no `Authenticate`** (its POST/DELETE siblings have
`Authenticate:web`). 🖥 Unauthenticated, it returns **401** `{"error":"invalid_client"}` — it fails on client
validation **before** reaching any user check. ⚠️ **NOT TESTED: what it does with a VALID `client_id` and no
session.** That needs a registered OAuth client; **reported as untested, not as safe.**

### ⛔ F-3 · ROLE-FLAVOURED ERROR COPY ON DOORS THAT HAVE NOTHING TO DO WITH ROLES — **NEW · LOW**

🖥 Two **unauthenticated** doors answer a bad token with:
> *"You don't have access to this. **Your role doesn't include this area. If you need it, ask an
> administrator on your team to grant the permission**…"*

- `/admin/account/email/verify/{admin}/{hash}` — the **platform-admin** email door
- `/login-approval/approve/{approval}` — a **signature** failure

Neither has a role, a team, or an administrator. ⚠️ Compare the same product doing it **right** three lines
away: the invitation doors say *"This invitation link isn't valid — it may have expired or already been
used"*, and the guest claim says *"This page belongs to a different booking."*
**Severity: LOW** (comprehensibility; no information is leaked). **🖥 BROWSER-VERIFIED. CLASS: NEW.**

---

## 6 · THE OTHER FIVE DIMENSIONS (Phase 0's definitions, unchanged)

**UI/UX** — 🖥 Every door reached renders a branded page with an `h1`; the invitation and claim doors give a
next action (*"Go to sign in"*). ⚠️ **F-1 is the exception** and is a UI/UX finding above all.
**DESIGN** — ⚠️ **NOT MEASURED this phase.** Contrast and spacing were not re-measured on auth screens; no
claim is made.
**FUNCTIONALITY & BUGS** — 🖥 Login, logout, throttling, CSRF and the reset/claim/invite error branches all
behave correctly. ⛔ **F-2** is the one broken thing found.
**CRUD** — n/a as a dimension here: these doors own no object. The one create-path, **signup (which
provisions an organisation), was deliberately not exercised** — §7.
**PERFORMANCE** — ⚠️ **Not meaningfully measurable**: `artisan serve` is single-process and not the
production runtime. **Deferred to a real server (B1).**
**MULTI-TENANCY** — ⛔ **NOT TESTED. One organisation.** See the constraints block.

---

## 7 · ⚠️⚠️ WHAT WAS NOT TESTED, AND WHY

**Doors examined: 24 of 24. Token doors probed with a TAMPERED token: 8 of 8.** Stated so "nothing found"
cannot be read as "nothing there".

| not tested | why |
|---|---|
| **VALID / EXPIRED / ALREADY-USED** on the **6 other** token doors (guest claim, guest reset, team-invite, the two email-verify doors) | Each needs a genuine issued token for that specific flow. ✅ **The owner invitation and the password reset ARE now covered in all four states — see §3b** |
| **The literal token-error text on a reset replay** | The route's **429 limiter** intervened on the follow-up render. The rejection itself is proven (§3b); only the visible wording is unconfirmed |
| **Signup** (`POST /signup`) | It **provisions an organisation** — moving `roles`, `role_has_permissions` and `model_has_roles`, all pinned. **Founder decision, not taken here** |
| **Passkeys login** | WebAuthn needs a CDP virtual authenticator; not set up this phase |
| **2FA challenge** | Requires an enrolled TOTP secret |
| **Guest claim happy path** | Needs a real claim token from a seeded booking |
| **Google ON state** | No credentials exist, and **an agent must never sign in to a third-party console** |
| **`/oauth/authorize` with a valid client and no session** | Needs a registered OAuth client |
| **Multi-tenancy** | ⛔ One organisation |
| **The five orphan permissions** | ⛔ Reachable by no auditable role — permanent |

---

## 8 · STATE, AND WHAT I CHANGED

⚠️ **A test mutation was made and reversed, reported here in full as the rules require:**
🔬 `users.password` for `demo-staff@vyrobel-demo.test` was set to `NULL` to create a passwordless account,
then restored. **The hash was captured to a file and verified non-empty BEFORE the mutation** (Phase 0's
lesson: *a backup you did not verify exists is not a backup*), and the restore was confirmed
**byte-identical**. ✅ **No row was created or deleted.**

🔬 **Pinned figures — ALL UNMOVED, re-measured after the audit:**
`users` **15** · `reservations` **42** · `payments` **16** · `roles` **14** · `permissions` **51** ·
`model_has_roles` **14** · `role_has_permissions` **260** · `owner_ledger` **29 / 730640** ·
`organizations` **1**.
✅ **All 14 demo accounts authenticate** (`Hash::check('password', …)` — 14 pass, 0 fail).
⚠️ **The money gate was NOT run** — it begins `migrate:fresh --seed`, which would rebuild the world.

⛔ **No application code changed.** ⛔ No realm interior audited. ⛔ No ruling decided. ⛔ No credential
entered anywhere. ⛔ **Suite not run — no code changed, so a run would assert nothing about this pass.**

### ⚠️ MY OWN INSTRUMENT PRODUCED THREE NEAR-FALSE-POSITIVES
**Recorded because each would have been published as fact, and two were alarming.**
1. **"Session still valid after logout" (would have been SEVERE).** 🔬 My probe requested `/app`, which
   **404s for everyone** — it is not a route; the dashboard is `/app/dashboard`. My discriminator looked for
   "login" in a path that never redirects. **Caught by adding an unauthenticated control.**
2. **"Logged in despite a stale CSRF token."** 🔬 `querySelector('form[action*="login"], form')` returns the
   **first `<form>` in document order** — and the login page carries a passkey form. **I submitted the wrong
   form.** The request-API test then showed a clean **419**.
3. **"`/oauth/authorize` is unauthenticated."** 🔬 True of its middleware, but it returns **401** on client
   validation first. **Middleware absence is not reachability.**

4. **"The reset token is NOT single-use" (would have been SEVERE).** 🔬 My verdict treated **any 302** as
   acceptance. But the **destination** is the discriminator: success → `/app/dashboard`, failure → **back to
   the form**. The replay redirected to the form. ⚠️ **I very nearly published a critical false finding on a
   password-reset path.**
5. **"Single-use enforced — replay bounced back to the form" (the opposite error, same probe).** Twice the
   replay was refused by something that never reached the token check — first **419 CSRF**, then **429 rate
   limit** — and my verdict string called both "enforced". ⚠️ **A refusal is not evidence of the refusal you
   were testing for.** *Unreachable is not clear.*

⚠️ **The shape is Phase 0's, in new costume: the instrument's own limits presented as the product's
defects** — and, in 4 and 5, **a verdict string that encoded the wrong discriminator**. Every one was caught
by adding a control that could fail, or by reading the destination instead of the status code.

⚠️⚠️ **The lesson worth carrying to Phase 2:** on a redirect-heavy auth surface, **the status code is not
the outcome — the destination is.** A 302 means success and failure equally.

---

## 9 · ⚠️ WHERE THIS BRIEF WAS WRONG

1. ⚠️ **"MCP Playwright has failed to launch here five times."** Carried from Phase 0's brief and **already
   stale when written** — Phase 0 fixed it. It launched first time here.
2. ⚠️ **"the lazy gate interrupts at the FIRST of 24 sensitive actions."** 🔬 `PASSWORDLESS-GATE.md` itself
   says *"The count moved: 30 occurrences, **24 rule sites**"*, and a grep of controllers today finds
   **19 files** containing `current_password`. ⚠️ **Three different numbers for one thing** — the brief's
   "24" is the doc's rule-site count, not a file or route count. **A pointer would not have gone stale.**
3. ✅ **Correct and load-bearing:** that the discriminator is the flash and not the URL — **proven here, and
   it is exactly how F-1 was found.** Had I checked the URL, both halves would have "passed" and the silent
   interruption would have shipped.

---
---

# PHASE 1b — THE SIX REMAINING TOKEN DOORS

**2026-09-12, appended — Phase 1 above is unchanged.** 🖥 **Instrument: MCP Playwright**, re-validated in
both directions before probing (known content exact · negative control reported an absent element · a
wrong-value control rejected a false match). Served on `http://localhost:8000`, the `APP_URL` origin.

⚠️⚠️ **CONSTRAINTS RESTATED:** **MULTI-TENANCY IS NOT TESTED — one organisation exists.** **FIVE
PERMISSIONS (`billing.manage` · `roles.manage` · `organizations.manage` · `settings.manage` · `api.manage`)
BELONG TO NO AUDITABLE ROLE — a PERMANENT coverage hole no phase can close.**

## 1b.0 · Verdict — 3 doors CLOSED, 2 PARTIAL, 1 LOST TO THE LIMITER

⚠️ **Nothing severe.** No door admitted the wrong person, leaked another guest's booking, or accepted a
replayed token.

| door | states covered | control succeeded? | verdict |
|---|---|---|---|
| **Guest claim** | **4 / 4** | ✅ yes | **CLOSED** — plus isolation verified |
| **Owner team invitation** | **4 / 4** | ✅ yes | **CLOSED** |
| **Owner email-verify** | **4 / 4** | ✅ yes (proven at the data layer) | **CLOSED** — and it produced **F-4** |
| **Guest password reset** | GET 1/2, POST 0/3 conclusive | ⛔ **NO** | **PARTIAL** |
| **Login approval** | signature layer only | ⛔ **NO** | **PARTIAL** |
| **Admin email-verify** | **0 / 4** | — never ran | ⛔ **NOT TESTED — all four probes hit the limiter** |

## 1b.1 · Every probe decided by the DESTINATION, every rejection ATTRIBUTED

### 🖥 Guest claim — the sharpest door, closed
| state | status | landed on | **attributed to** |
|---|---|---|---|
| **VALID (control)** | 200 | the claim page | ✅ **`resolveClaimable` ACCEPTED** — *"Welcome, Demo Walker … Set a password to finish setting up your account"*, real `password` + `password_confirmation` |
| **EXPIRED** | **403** | error page | **the `signed` middleware** — expiry, *before* the resolver ever ran |
| **TAMPERED hash** (valid signature, wrong hash) | **200** | invalid-link page | ✅ **`resolveClaimable` REFUSED** — the signature PASSED, so this is unambiguously the token layer |
| **ALREADY-USED** (replayed after a real claim) | **200** | invalid-link page | ✅ **`resolveClaimable` REFUSED** (`password !== null`) — signature still valid, so again the token layer |

⚠️ **The TAMPERED case is the one that matters methodologically:** a valid signature with a wrong hash
isolates the resolver from the signature middleware. Without it, every refusal would be attributable to
`signed` and the token check would be unproven.

✅ **The failure copy is ACTIONABLE and non-enumerating:** *"This link isn't valid. It may have expired or
already been used. **Request a fresh link to set up your account.**"* + a **"Request a new link"** control.
**One message for three different failures** — it refuses to disclose which, while still telling a
first-time visitor what to do. **For the entire creation path of a realm, this is right.**

### 🖥 ⚠️⚠️ ISOLATION FROM A FRESHLY CLAIMED ACCOUNT — HOLDS
🖥 A real claim was performed (landed `/guest/trips`, *"Welcome back, Demo"*), then:

| | 🖥 |
|---|---|
| **Own booking** (control — must be reachable) | **200**, renders the stay |
| **ANOTHER guest's booking** | **404** — *"doesn't exist, or it isn't part of your account"* |

✅ **Isolation holds from a brand-new account**, with a control proving the probe could see success.
⚠️ **Bounded:** one foreign booking, one freshly claimed guest, **one organisation** — this is *guest-to-guest*
isolation inside a single tenant, **not** multi-tenancy.

### 🖥 Owner team invitation — closed
| state | status | landed on | attributed to |
|---|---|---|---|
| **VALID (control)** | 200 | `/team-invite/…` | ✅ **token ACCEPTED** — *"Set your password"*, real fields |
| **EXPIRED** · **ALREADY-USED** · **TAMPERED** | 200 each | `/team-invite/…` | **token REFUSED** — *"This invitation link isn't valid… ask your operator to send a new invitation"* + *"Go to sign in"* |

⚠️ **Observation, NOT a finding:** the valid page greets *"Welcome, **there**"* where the owner invitation
greets *"Welcome, Demo Owner"*. A team invite carries only an email, so a fallback is expected — but I
**could not locate the template** to confirm whether *"there"* is deliberate. **Reported as unattributed;
do not treat as a defect without reading the view.**

### 🖥 Owner email-verify — closed, and it found F-4
⚠️ **Attribution here required the data layer**, because every outcome renders identically.

| state | status | landed on | attributed to |
|---|---|---|---|
| **EXPIRED** | **403** | error page | **the `signed` middleware** |
| **TAMPERED hash** | 200 | **`/login`** | the handler refused (`hash_equals` on `pending_email`) |
| **VALID (control)** | 200 | **`/login`** | ✅ **ACCEPTED — proven at the data layer**: `pending_email` was consumed (→ NULL) and `email` became the pending address |
| **ALREADY-USED** (replay) | 200 | **`/login`** | the handler refused (`pending_email` now empty) |

⚠️⚠️ **Success and both failures are INDISTINGUISHABLE on the rendered page** — same status, same
destination, no visible message. **Only the database told me which had happened.**

### ⛔ F-4 · THE EMAIL-CHANGE OUTCOME IS NEVER SHOWN TO THE USER — **NEW · MEDIUM**
🔬 `OwnerAccountController::verifyEmailChange` redirects to `login` with
`->with('status', 'That email-change link is no longer valid. Please request a new one.')`.
🔬 **`resources/views/auth/login.blade.php` contains `session('status')` ZERO times** (`grep -c` = 0),
while `resources/views/guest/auth/login.blade.php:10` **does** render it.

**Consequence.** Someone clicking a stale or already-used email-change link is dropped on the sign-in page
with **no explanation whatsoever** — and on success, **no confirmation that their address changed.**
⚠️ **This is F-1's exact shape on a second door**, and the two together make it a **class, not an instance**:
*a flash set by a handler that the destination view does not render.*
**Severity: MEDIUM by consequence** (comprehensibility on an account-security action; no security defect —
the refusal itself is correct). **🖥 BROWSER-VERIFIED + 🔬 MEASURED. CLASS: NEW.**

⚠️ **The admin door is structurally identical and UNTESTED:** 🔬 `Admin\AccountController::verifyEmailChange`
routes **all four** outcomes — success, bad hash, address-taken, missing record — to `admin.login` with a
`status` flash. **Whether that view renders it was never established (the limiter).** **Reported as a
structural risk, NOT as a finding.**

### ⚠️ Guest password reset — **PARTIAL**, control failed
| probe | status | attributed to |
|---|---|---|
| GET, VALID token | 200, form renders (`email`, `password`, `password_confirmation`) | ⚠️ **a page that renders is NOT a token that validated** — the POST decides |
| GET, TAMPERED token | **429** | **RATE LIMITER — INCONCLUSIVE** |
| **POST, VALID token (control)** | 302 **back to the same form** | ⛔ **REFUSED — the control FAILED** |
| POST, replay · POST, tampered | 302 back / **429** | ⛔ **meaningless without a working control** |

⛔ **Because the control never succeeded, none of the rejections can be credited to the token check.**
📖 A likely cause, unconfirmed: `config/auth.php:145` notes the guests broker *"keys on the guest's
blind-index hash (not the plaintext email)"*, so a plaintext `email` field may not match what
`createToken` stored. **Not investigated further — that would be debugging, not auditing.**

### ⚠️ Login approval — **PARTIAL**, control failed twice
| probe | status | attributed to |
|---|---|---|
| **TAMPERED signature** | **403** | ✅ **the `signed` middleware** — the signature layer IS proven |
| **VALID (control)**, 3 rows present | 200 *"This link can't be used"* | ⛔ control failed |
| **VALID (control)**, **sole row**, unexpired, unconsumed | 200 *"This link can't be used"* | ⛔ **control failed again** |

⛔ **The valid state could not be constructed.** My first theory — that the three rows superseded one
another (*"…or been superseded by a newer sign-in"*) — was **disproved** by retrying with a single row.
**Some further precondition exists (most likely a pending login session tied to the approval).**
✅ **What IS proven: the signature layer refuses a forged signature with 403.** ⛔ Everything past it is
untested. **Do not infer the happy path from the failure paths.**

## 1b.2 · How the limiter was handled, and what it cost

🔬 **The limiter is `throttle:6,1` on every signed door** — six requests per minute. **Handling used:**
polled until clear (measured **16s** and **30s** on two occasions) · ordered probes so the expensive,
state-consuming one ran last on each door · cleared cookies between probes.

⛔ **What it still cost:** the **entire admin email-verify door** (4/4 probes returned 429) and the
**guest-reset TAMPERED GET**. **All recorded as INCONCLUSIVE, never as passes.**
✅ One accidental benefit worth stating: because the admin VALID probe never executed, **the platform
admin's email was never changed.**

## 1b.3 · ⚠️ TWO NEW NEAR-FALSE-POSITIVES (Phase 1 recorded five; the running total is **seven**)

6. **"The login-approval control fails because the rows superseded each other."** 🔬 Plausible — the page
   says exactly that — and **wrong**: a sole unexpired, unconsumed row failed identically. ⚠️ **A
   page's own explanation of its failure is a hypothesis, not a diagnosis.**
7. **"The guest reset token is refused."** 🔬 It *was* refused — but the **VALID control was refused too**,
   so the refusal is attributable to my construction of the probe, not to the product. ⚠️ **Reporting
   "refused" here would have credited the app with a check I never reached.**

## 1b.4 · ⚠️⚠️ THE NOT-TESTED TABLE, UPDATED HONESTLY

**Doors closed in all four states: 3 of 6 (guest claim · owner team invitation · owner email-verify),
plus the 2 closed in Phase 1 (owner invitation · password reset) = 5 of 8 token doors total.**

| still not tested | why |
|---|---|
| ⛔ **Admin email-verify — all four states** | Every probe hit the `throttle:6,1` limiter. **Its flash-rendering risk is structurally identical to F-4 and remains unexamined.** |
| ⛔ **Guest password reset — the token check itself** | The VALID control never succeeded; the broker keys on a blind-index hash |
| ⛔ **Login approval — everything past the signature** | The valid state could not be constructed; an unidentified precondition |
| ⛔ **The literal token-error wording on a reset replay** (carried from Phase 1) | Lost to the limiter, twice more |
| ⛔ **Passkeys / WebAuthn** | Needs a CDP virtual authenticator — **explicitly out of scope this phase** |
| ⛔ **2FA challenge** | Requires an enrolled TOTP secret — out of scope |
| ⛔ **Signup** | Provisions an organisation; moves three pinned figures — **the founder's** |
| ⛔ **Google ON state** | No credentials; an agent must never sign in to a third-party console |
| ⛔ **`/oauth/authorize` with a valid client and no session** | Needs a registered OAuth client |
| ⛔ **MULTI-TENANCY** | **One organisation. Not a pass — untestable.** |
| ⛔ **The five orphan permissions** | Reachable by no auditable role. **Permanent.** |

## 1b.5 · STATE — everything restored, twice-checked

| mutation | restored | verified |
|---|---|---|
| A guest was **really claimed** (`password`, `email_verified_at`, `remember_token`) | ✅ all three back to `NULL` | **byte-identical to the captured backup**; unclaimed guests **9 → 9** |
| `demo-staff.pending_email` set, then the VALID link **changed the account's `email`** | ✅ email + pending_email restored | exact string match on both |
| `platform_admins.pending_email` set | ✅ `NULL`; **email never changed** (the probe was limited) | verified |
| 3 scratch `owner_team_invites` | ✅ deleted | **1 → 1** (the pre-existing `demo-owner-invitee` row, dated 2026-09-05) |
| 4 scratch `login_approvals` | ✅ deleted | **0**. ⚠️ **I cleared the table without first capturing its count.** 🔬 No seeder references `LoginApproval`, and it holds transient auth artefacts, so the pre-existing count was almost certainly 0 — **but I did not measure it, and I am not claiming I did.** |
| `guest_password_reset_tokens`, `password_reset_tokens` | ✅ cleared | **0 / 0** |

🔬 **PINNED FIGURES — ALL UNMOVED:** `organizations` **1** · `users` **15** · `reservations` **42** ·
`payments` **16** · `roles` **14** · `permissions` **51** · `model_has_roles` **14** ·
`role_has_permissions` **260** · `owner_ledger` **29 / 730640**.
✅ **All 14 demo accounts authenticate** (14 pass, 0 fail).
⚠️ **The money gate was NOT run** — it begins `migrate:fresh --seed`.
⛔ **No application code changed. F-1, F-2 and F-3 remain open and unfixed.**
⛔ **Suite not run — no code changed, so a run would assert nothing about this pass.**

## 1b.6 · ⚠️ WHERE THIS BRIEF WAS WRONG

1. ⚠️ **"the six doors"** — 🔬 the login approval is **not a token door in the same sense**: its
   `{approval}` segment is a row id and the security is the **URL signature**, not a token compared against
   a stored hash. Its four "states" are therefore row states, not token states.
2. ⚠️ **It presumes all six valid states are constructible.** 🔬 **Two are not** — reported as PARTIAL
   rather than inferred, per rule #4.
3. ✅ **Correct and load-bearing, twice over:** *the destination is the outcome* (owner email-verify is
   indistinguishable by status and destination alike — **only the database resolved it**), and *a refusal
   is not evidence of the refusal you were testing for* (**it caught both new near-false-positives**).
