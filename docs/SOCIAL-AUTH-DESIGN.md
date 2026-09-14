# Vyrobel — social sign-in: what it would mean here · Phase 1 (design only) · 2026-09-02

**Nothing was built.** No dependency, no migration, no route, no `.env` key. This document answers five
questions and asks the founder to rule on each. Every claim below was established against the code or
tested; where something was reasoned rather than measured it says so.

⚠️ **Socialite is NOT installed.** `laravel/passport` is present but is an OAuth **server** (Vyrobel issuing
tokens to third parties) — the opposite direction and unrelated to this feature.

---

## 1 · ⚠️⚠️ The passwordless re-authentication problem — TESTED, and it fails CLOSED

### The scale: 30 sites, 19 controllers, 3 guards

| Guard | Rule | Sites | Controllers |
|---|---|---|---|
| `web` (operator **and** owner share it) | `current_password` | **24** | OwnerTax · OwnerTeam · OwnerConnectedApp · OwnerPayoutPreference · OwnerAccount ×2 · OwnerSecurity ×4 · OwnerPayoutMethod · TwoFactor · Passkey · ChangePassword · RoleAssignment · InviteOwner · OwnerManage · SettingsAction · OperatorSecurity · OperatorProfile |
| `admin` | `current_password:admin` | **4** | Admin/Account ×3 · Admin/Customer ×1 |
| `guest` | `current_password:guest` | **1** | Guest/GuestAccount |

⚠️ The brief listed roughly a dozen; there are **thirty**. The family is larger than remembered.

### What a passwordless user experiences today — measured, not reasoned

`users.password` is **`varchar(255) NOT NULL`** — there is no null-password shape in the schema, so the only
thing a social-only account could carry is an empty string. Driven inside a rolled-back transaction:

```
probe user password = ''        (the only shape the NOT NULL column allows)
  submit ''         -> FAILS: The current password field is required.
  submit 'anything' -> FAILS: The password is incorrect.
  submit 'password' -> FAILS: The password is incorrect.
  Hash::check('', '') = false     Hash::check('x', '') = false
```

⚠️⚠️ **THE GATE DOES NOT LEAK.** The brief framed this as *"either those users cannot perform them, or the
gate is bypassed — both unacceptable"*. **Tested, it is definitively the first.** `Hash::check` against an
empty hash returns `false`, so a social-only user is **permanently locked out of all 30 sensitive actions**
— they cannot change their email, add a payout destination, invite a team member, assign a role, save tax
details, enrol 2FA, register a passkey, or delete their guest account.

**That is a usability catastrophe, not a security hole**, and the distinction matters: it means social
sign-in can be built without opening a hole, but it cannot be built without answering this first.

### The options — costs stated, NONE decided

| | Option | What it costs | What it risks |
|---|---|---|---|
| **(a)** | **Require a password before any sensitive action** — social is a convenience *login*, every account still has a password | Smallest: an interstitial "set a password to continue" on 30 routes, or one middleware. No change to the 30 rules. | Undermines the pitch — "sign in with Google" that then demands a password reads as broken to the user |
| **(b)** | **Re-authenticate through the provider** — an OAuth re-prompt (`prompt=login`/`max_age=0`) in place of `current_password` | Largest: a second confirmation mechanism beside the password one, on 30 sites, plus a return-path for each. Two mechanisms for one concept — the §N.8f drift hazard this project keeps removing | A provider outage locks every social user out of every sensitive action |
| **(c)** | **Require 2FA for social-only accounts; re-auth with a TOTP code** | Medium: `TwoFactorService` already exists and platform admins already have mandatory 2FA, so the pattern is built. A `current_password`-or-code abstraction on 30 sites | Forces every social user through TOTP enrolment — friction at exactly the moment social sign-in was meant to remove it |
| **(d)** | **Refuse social sign-in for roles holding sensitive permissions** | Smallest to build, largest to explain. Social for guests only, or for read-only roles | A capability difference by role is the kind of undisclosed asymmetry this project has repeatedly refused |

**What the surrounding code favours: (a), then (c).** The evidence: `ChangePasswordController` already exists;
`TwoFactorService`, `TrustedDeviceService` and a login-approval flow are all built; and
`EnsurePlatformAdminTwoFactor` shows the project is comfortable *mandating* a second factor for a privileged
realm. Option (b) is the only one that would introduce a second re-authentication concept, which is the
pattern this codebase most consistently removes.

⛔ **FOUNDER DECISION. Everything else in this document depends on it.**

---

## 2 · Linking is the security core

### The rule

**Match on `provider + provider_user_id`. Never on email.** An email is a *hint* that requires proof; it is
not an identity. If a Google identity is matched to an existing account by address alone, then anyone who
controls a Google account at that address owns the Vyrobel account — including any address the user once
had at a domain they no longer control.

### Every case

| Case | Correct behaviour |
|---|---|
| Provider identity already linked | Sign in. The only path that should ever be silent. |
| Matches nothing, no account at that email | Create — subject to §3 (what "create" means differs per realm) |
| **Email matches an existing password account** | ⚠️ **Do NOT link.** Sign in with the password first, then link from the account page. Alternatively send a verification to the address and link on click. Never link on the assertion alone. |
| Provider email later changes | Irrelevant — the link is on `provider_user_id`. Update the stored email as a display value only. |
| Two providers, same person | Two rows in the link table, both pointing at one user. Reached only by linking each from inside an authenticated session. |
| Provider reports `email_verified: false` | ⚠️ **Refuse to link, and refuse to create.** An unverified address from any provider is worthless. |

**May Google's `email_verified` be trusted?** For **linking, no** — it should never be sufficient on its own,
because it asserts the provider verified the address, not that this human is the one who holds the Vyrobel
account. For **creating a new account with a verified address**, yes, it is reasonable to skip a separate
verification email. ⚠️ Google Workspace domain admins can create accounts at any address in their domain, so
`email_verified: true` proves control of the mailbox, never continuity of person.

### The unlink lockout

**Unlinking your only sign-in method locks you out permanently** — the same class as the last-admin floor
already built here, which counts holders who can actually sign in. The floor to enforce: **a user must
always retain at least one usable credential** (a password, or another linked provider). This must be
checked in the unlink path itself, exactly as `OwnerLoginLifecycle` checks before disabling a login —
⚠️ and the audit found that pattern already exists and is the right precedent.

---

## 3 · Which realm — and what "sign up with Google" must provision

**Three separate identity tables**, not one: `web` → `User` (operator **and** owner), `guest` → `Guest`,
`admin` → `PlatformAdmin`. There is no shared identity, so link storage is a per-realm decision.

| Realm | Recommendation | Why |
|---|---|---|
| **Guest** `/guest` | ⭐ **Strongest candidate** | Highest volume, lowest privilege, no `current_password` problem worth solving (1 site, account deletion). ⚠️ But guests have **no account at booking time** — the realm is a booking-tied *claim* flow, so social sign-in here replaces the claim email, it does not create accounts. That is a real simplification for a traveller. |
| **Operator** `/app` | Second, and only after §1 | 24 of the 30 re-auth sites live here. Blocked on the founder's ruling. |
| **Owner** `/owner` | ⚠️ **Invitation acceptance only** | Owners are invited by token and set a password on acceptance — never self-serve. "Accept your invitation with Google" is a genuinely better flow than a password, and is arguably the most useful single thing on this list. |
| **Platform admin** `/admin` | ⛔ **No** | 2FA is mandatory (`EnsurePlatformAdminTwoFactor`). Social sign-in here would be a way to weaken the most privileged realm for no volume. |
| **Storefront** `/book/{slug}` | Follows the guest decision | It is the guest realm's front door. |

### ⚠️⚠️ What `/signup` actually provisions — the reason "Sign up with Google" is not one click

`TenantProvisioningService::provision()` creates, in **one transaction**:

**Organization → the org's roles (`OrganizationRoleProvisioner`) → User → Membership → assign `Property Owner`
→ Property → `startTrialFor($org)`** (the billing trial).

And `/signup` requires: **`org_name`, `property_type`, `currency`, `property_name`, `name`, `email`, `password`.**

**An OAuth callback supplies `name` and `email` only.** The other four are business facts no provider can
supply. So "Sign up with Google" **cannot** be one click — it is *"authenticate, then complete the org
form with your name and email pre-filled and the password field removed"*. That is still worth having;
it is just not the feature the phrase implies, and pretending otherwise would set the wrong expectation.

---

## 4 · Apple — five facts, none obvious

1. ⚠️⚠️ **Apple forbids `localhost` redirect URIs entirely.** "Works on localhost" is **false by
   construction** for Apple. Options: an HTTPS tunnel (ngrok/Cloudflare — a public URL registered as the
   redirect, re-registered whenever it changes on the free tier); a `.local` alias with a locally-trusted
   certificate (Apple still requires a real, resolvable domain — so this does not actually work); or a local
   stub (§7). **Realistically: a tunnel, or no local Apple testing at all.**
2. ⚠️⚠️ **The client secret is a JWT the developer signs, and it expires — six months maximum.** A static
   value in `.env` **will silently stop working**, with the failure appearing as sign-in failures months
   after anyone touched the code. It must be **generated at runtime** from the `.p8` key + Team ID + Key ID
   + Services ID, or regenerated on a schedule with an alert. ⚠️ This is the same failure shape as the
   `MAIL_MAILER=log` family this project already catalogues: no error, no log, invisible until someone looks.
3. ⚠️⚠️ **Apple returns the user's name ONLY on the first authorization, never again.** If the callback is
   not persisted in that single request, the name is gone permanently — re-authorizing does not return it.
   It must be captured before anything else can fail. If the request dies midway, the only recovery is for
   the user to remove the app from their Apple ID and start over.
4. ⚠️ **Hide My Email** issues a `@privaterelay.appleid.com` address the user can disable later, after which
   it **bounces**. For this product that reaches **statements, payout notices and booking confirmations** —
   a bounced statement is a money conversation that silently does not happen. The relay domain must be
   registered with Apple *and* verified with the sending provider (Postmark), or all relayed mail is
   rejected at the door.
5. ⚠️ **Apple requires a paid Developer Program account** — an annual fee and an enrolment that is not
   instant. **This is a cost and a delay, not a five-minute setup.**

**Recommendation: Google first, Apple later or never.** Apple's four traps buy nothing that Google does not,
unless the product ships an iOS app — at which point Apple's rules may *require* it.

---

## 5 · The console steps — the founder's, not the agent's

⚠️ **An agent must not sign in to Google Cloud Console or Apple Developer with anyone's credentials**, and
both enforce 2FA regardless. These are the exact values to enter.

### Google — OAuth 2.0 Client ID (~10 minutes)

1. console.cloud.google.com → create/select a project (e.g. `vyrobel`).
2. **APIs & Services → OAuth consent screen** → External. App name `Vyrobel`; support email; developer
   contact. Authorised domain: `vyrobel.com`. Publish (or add test users while unverified).
3. **Scopes**: `openid`, `email`, `profile` — **nothing more**. Anything beyond these triggers Google's
   verification review and asks users for access this product does not need.
4. **Credentials → Create Credentials → OAuth client ID → Web application.**
5. **Authorised JavaScript origins:** `http://localhost:8000` · `https://vyrobel.com`
6. **Authorised redirect URIs** — one per realm you enable:
   - `http://localhost:8000/auth/google/callback` · `https://vyrobel.com/auth/google/callback`
   - `http://localhost:8000/guest/auth/google/callback` · `https://vyrobel.com/guest/auth/google/callback`
7. Copy the **Client ID** and **Client secret**.

### Apple — only if Apple is ruled in

Paid Developer Program → **Identifiers**: an App ID, then a **Services ID** (this is the client_id) →
enable Sign In with Apple → configure domains `vyrobel.com` and return URL `https://vyrobel.com/auth/apple/callback`
(⚠️ **no localhost accepted**) → **Keys**: create a Sign In with Apple key, download the **`.p8` once** →
record **Team ID**, **Key ID**, **Services ID**.

### Secrets — never committed

`GOOGLE_CLIENT_SECRET` · `APPLE_CLIENT_SECRET`/`.p8` private key · `APPLE_TEAM_ID` · `APPLE_KEY_ID`.

⚠️ **`.gitignore` today covers `.env`, `/storage/*.key` and the Passport oauth keys — it does NOT have a
`*.p8` rule.** An Apple key dropped anywhere outside `/storage` would be committable. **Add `*.p8` before
Apple is configured, not after.**

⛔ **Belongs in `GO-LIVE-REQUIREMENTS.md`** alongside the Stripe keys and the domain — founder-only values.
Not added by this pass, which writes only this document.

---

## 6 · What can be tested, and what cannot

⚠️ **MCP Playwright does not launch in this environment** — five passes have hit the same blocked GPU
helper. **`chrome-headless-shell` over CDP is the working route** and has succeeded five times. Plan around
the instrument that works.

⚠️ **A real OAuth round trip cannot be driven headlessly regardless** — Google detects and blocks automated
sign-in, and will challenge or refuse.

| Testable automatically (fake the provider) | Needs a human, a real browser, a real account |
|---|---|
| The redirect is issued with the right `client_id`, `scope`, `redirect_uri`, `state` | That Google actually accepts the client and the redirect URI |
| `state` is generated, stored and **validated** — a mismatched or replayed state is refused | The consent screen renders correctly and says the right things |
| The callback provisions exactly the right rows (user, membership, role, org, trial) | Hide My Email actually delivers |
| Linking refuses an email-only match; unlink refuses to remove the last credential | Apple's first-authorization name capture |
| A social-only user still hits the re-auth gate per the §1 ruling | Whether the expiring Apple secret is refreshed correctly |

⚠️ **A test that fakes the provider proves the application's half and nothing else.** It must never be
reported as a round trip. That distinction is the whole point of this row.

---

## 7 · The stub — ⚠️ RECOMMENDED AGAINST

A local stub provider would make the whole flow testable on `localhost`, **including Apple, which otherwise
cannot be tested locally at all**. That is a genuine benefit.

⚠️⚠️ **But a stub reachable in production is an authentication bypass — the most severe defect this product
could ship.** Anyone who reaches the stub endpoint signs in as anyone.

The project's own precedent is instructive but **not sufficient**: `FakeChannexClient` is bound by
`config('services.channex.fake', true)` — a **config default of true**, i.e. fail-*open* to the fake. That
is exactly right for a channel manager (the worst case is no sync) and exactly wrong for authentication
(the worst case is total compromise). `StripeSafetyGuard` is the better model: it **refuses a live key
outside `production`** — a guard that fails closed in the dangerous direction.

**Recommendation: no stub.** Test the application's half by faking the provider *inside the test suite*,
where no route exists and nothing can be reached over HTTP. If a stub is ever built anyway, it must be
bound only when `app()->environment('local')` **and** guarded by a test that fails if it is reachable in any
other environment — belt and braces, because the failure mode is unrecoverable.

---

## 8 · Proposed phasing

| Phase | What | Blocks / blocked by |
|---|---|---|
| **1** | *(this document)* | — |
| **2** | ⛔ **The founder's §1 ruling** | **Blocks 4, 5 and 6.** Nothing operator-facing can be built without it |
| **3** | **Owner invitation acceptance with Google** | ⚠️ **Independent** — an invited owner has no password yet, so the re-auth question does not arise at acceptance. Smallest useful piece; can start immediately |
| **4** | **Google on the guest realm** (replacing/augmenting the booking-tied claim) | Needs 2 only weakly (1 re-auth site). Highest volume |
| **5** | **Linking + unlinking + the last-credential floor** | Blocked by 2. Should ship *with* the first realm, not after it |
| **6** | **Google on the operator realm**, incl. the post-callback org form | Blocked by 2 and 5 |
| **7** | **Apple** | Blocked by the paid account and a decision on the runtime-generated secret. ⚠️ Recommend deferring |

**Independent and startable now: phase 3.** Everything else waits on one ruling.

---

## Where the brief was wrong

1. ⚠️ **"either those users cannot perform them, or the gate is bypassed — both unacceptable"** — **tested:
   it is definitively the first, and the gate does not leak.** `Hash::check` against an empty hash returns
   false. The failure mode is safe; the problem is a total lockout, not a bypass.
2. ⚠️ The brief listed ~12 re-auth actions; there are **30 sites across 19 controllers and 3 guards**.
3. ⚠️ **"the guest realm has its own claim flow"** — correct, and stronger than stated: guests have **no
   account at booking time at all**, so social sign-in there *replaces* the claim rather than adding a
   login.
4. ✅ Correct and load-bearing: that linking by email is takeover; that `/signup` provisions an organisation;
   that Apple forbids localhost, expires its secret, and returns the name once.

---

## Status

**Nothing built.** No dependency, migration, route, controller or `.env` key. This document is the only file
added. The database was touched only inside a **rolled-back transaction** — demo state verified intact
afterwards.
