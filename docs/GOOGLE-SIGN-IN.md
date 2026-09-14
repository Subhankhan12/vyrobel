# Sign in and sign up with Google — operator realm

**2026-09-04.** Google on `/app` only: sign in, sign up, link, unlink. ⚠️ **Apple was not built** (a paid
account, no localhost redirects, an expiring JWT secret — its own phase). ⚠️ **The guest and platform-admin
realms were not touched.** ⚠️ **No dependency was installed.**

---

## 1 · ⚠️⚠️ The linking rule is the security core, not the button

**Match on `(provider, provider_user_id)`. Never on email.** An address is a hint that requires proof; it is
not an identity. Matched by address, anyone who controls a Google account at that address owns the Vyrobel
account — including an address the user once held at a domain they no longer control.

It lives in **one** place, `App\Support\Auth\SocialLinking`, which **decides and does not act**: no writes,
no session, no side effects. The controller carries out what it returns, so the rule that must not be wrong
is testable without a browser and cannot differ from the one that runs.

| Case | Behaviour | Proven by |
|---|---|---|
| Identity already linked | **Sign in** — the only silent path | `signs in an ALREADY LINKED identity` |
| Matches nothing, no account at that address | **Sign up** (§3) | `an ABANDONED social signup…`, `completes the social signup…` |
| ⚠️⚠️ **Email matches an existing password account** | ⚠️⚠️ **DO NOT LINK.** Sign in with the password first, then link from the security page | `REFUSES to link when only the EMAIL matches…` + the same at the rule itself |
| Provider email later changes | **Irrelevant** — the link is on the identifier; the address is updated as a display value | `matches on the provider id even after the provider EMAIL changes` |
| Two providers, one person | **Two rows, one user**, reached only from an authenticated session | `allows TWO providers for ONE person` |
| ⚠️ `email_verified: false` | ⚠️ **Refuse to link AND refuse to create** | `REFUSES an unverified address — both to link AND to create` |
| Identity already linked to someone else | **Refuse.** A link is never silently moved | `REFUSES to link an identity that already belongs to someone else` |
| Linked account is suspended | **Refuse** — a link is not a way around a suspension | `a link pointing at a SUSPENDED account…` |

### ⚠️ Where `email_verified` is decided, and why it can never authorise a link

Stated in the code, at the point of decision (`SocialLinking`, class docblock and case ②/③):

> `email_verified: true` asserts the provider verified control of that **mailbox**. It says nothing about
> whether this human is the person who holds the Vyrobel account at that address. **A Google Workspace
> administrator can create an account at any address in their domain**, so the claim proves **mailbox
> control, never continuity of person.**

So it **may** skip a verification email when **creating** (nothing exists to take over), and **must never**
attach an identity to an account that **already exists**.

### ⚠️ The provider id is unique by DATABASE CONSTRAINT

`user_identities` carries `UNIQUE (provider, provider_user_id)` and `UNIQUE (user_id, provider)`. **Not a
validator** — a validator covers one controller and not a seeder, a console command, a queued job or a
direct write. The test asserts it with a **raw `DB::table()->insert()`**, deliberately bypassing every
model, observer and form request, because that is exactly what those callers do.

---

## 2 · ⚠️⚠️ The sign-in definition — settled, and the brief's premise was wrong

**The brief said the last-admin floor "reasons from having a password". Measured, it does not.** Every
reader keys on `users.status`:

| Reader | What it decides | Reasons from |
|---|---|---|
| `LoginRequest::authenticate()` | whether this password sign-in succeeds | password **+** `status` |
| `PasswordResetController::sendLink()` | whether a reset link is sent | ⚠️ **`status` only** |
| `PasswordResetController::reset()` | whether a reset token resolves | ⚠️ **`status` only** |
| `RolePermissions::roleManagersAfter()` — **the last-admin floor** | whether an organisation still has an administrator who can act | ⚠️ **`status` only** |

⚠️⚠️ **So the floor already counts a linked passwordless administrator as ABLE — which is the correct
answer — and the feared defect does not exist.** Both harmful directions, and where each actually lands:

- **Counting them UNABLE** would let the **last real administrator be removed**. ⚠️ The floor does not do
  this, and a test now pins it: a `Property Owner` with a **null password** and a Google link **is** in the
  survivor set.
- **Counting them ABLE when their link is broken** could leave an organisation with **nobody who can get
  in**. ⚠️ This is also correct, for a reason easy to miss and now proven end to end: **the password-reset
  broker's lookup is scoped by status, not by password.** An active account with no password and no link
  can still request a reset link and set one. **It is recoverable, so counting it as able is right rather
  than merely convenient.**

⚠️⚠️ **NOTHING WAS CHANGED IN THE FLOOR — so no certified suite reds, and there is no STOP AND ASK.** What
was added is `SignInCredentials::canSignIn()`, which states the definition **once, next to the floor that
depends on it**, so the next person to add a credential type does not have to re-derive it.

---

## 3 · ⚠️ Signup is authenticate-then-form

`TenantProvisioningService::provision()` creates **org → roles → user → membership → `Property Owner` →
property → trial in ONE transaction**, and `/signup` requires **seven** fields: `org_name`,
`property_type`, `currency`, `property_name`, `name`, `email`, `password`. **A callback supplies two.**

So it is *"authenticate, then complete the organisation form, pre-filled, with the password field gone"* —
and the page says so rather than implying one click.

- ⚠️⚠️ **AN ABANDONED SIGNUP LEAVES NOTHING.** The half-finished state lives **in the session and nowhere
  else**. The test photographs six tables (`users`, `organizations`, `organization_user`, `subscriptions`,
  `properties`, `user_identities`) before and after the callback and asserts they are **identical**. A
  half-provisioned organisation is a money object with no owner.
- ⚠️ **Still ONE transaction.** The provider link is written **inside** it, so an organisation can never
  exist with a passwordless founder and no way to sign in.
- ⚠️ **The posted email is overwritten with the provider's assertion.** The field is pre-filled and
  readonly, but trusting a posted value would let someone authenticate as themselves and provision an
  organisation under a **different** address with their own Google account attached — a takeover assembled
  out of a pre-filled input. Mutation-proven.
- ⚠️ **`/signup` with a password is unchanged**, and a test asserts it.

---

## 4 · ⚠️ Unlinking is a lockout

**The floor: a user must always retain at least one usable credential** — a password, or another linked
provider. `SignInCredentials::mayUnlink()`, called **in the unlink route**, not in a view: a disabled button
is presentation that a direct POST goes straight around, and the person it locks out owns the account.

The screen also **disables the control, marks it `aria-disabled`, and says why** — *"This is the only way
you can sign in. Set a password first, and then you can remove it."* — rather than going silently inert.

⚠️⚠️ **Whether it interacts with the last-admin floor: it does not, and that is a fact about the code.**
An unlink writes to `user_identities` **only** — never `users.status`, never roles, never memberships — and
the floor counts by `status`. **A test asserts the survivor set is byte-identical across an unlink**,
because "these two guards don't interact" is exactly the kind of claim that quietly stops being true.

Unlink is also **self-only**: another user's link is not reachable.

---

## 5 · ⚠️⚠️ No stub, and `state` is validated

**There is no stub implementation in the application and no config flag that could select one.** A stub
provider reachable in production is an authentication bypass — whoever reaches it signs in as anyone.
`FakeChannexClient` is bound by a config default of **true**: right for a channel manager (worst case, no
sync), catastrophic for a login. The model followed is `StripeSafetyGuard` — **fail closed in the dangerous
direction**. The **only** fake lives in `tests/Support/FakeSocialIdentityProvider.php`, bound by a test into
an in-process container, with no route to it. A test asserts the container resolves the **real** provider
and that **no route anywhere** names a fake or a stub.

**`state` is generated per attempt, stored in the session, and validated** — compared with `hash_equals`,
expiring after 15 minutes, and **pulled before any other work**, which makes it **single-use by
construction**. Tested directly in all three failure shapes: **mismatched**, **absent**, and **replayed**.

⚠️ **And Google sign-in does not skip 2FA.** The post-credential rules (trusted device → login approval →
2FA → session) were **extracted** into `Concerns\CompletesSignIn` and are shared with the password login
rather than copied. A second copy is a copy that can forget the 2FA branch — which would make the newer
button a way to make an account *less* safe. Mutation-proven.

---

## 6 · ⚠️ The passwordless gate's first real user

A Google account has **no password by construction**. ⚠️ **The shape it gets is `NULL`** — asserted against
the raw column, because the previous pass found the **empty-string** case was the one its fixtures missed.

Proven end to end in one test: sign in → **browse untouched** → sensitive action → **interrupted** → set a
password on the same card → **the original action now behaves exactly as it does for everyone else**.

⚠️ **The negative half is the whole point of lazy**, and it is asserted: `/app/dashboard` and
`/app/security` are reached with no interruption at all. A gate proven only in the deny direction may simply
be denying everyone.

⚠️ **The discriminator is the middleware's own flash, not the redirect URL** — both outcomes can land on
`/security` (a failing rule redirects "back"), so a URL assertion would pass for the wrong reason and keep
passing if the gate were deleted.

⚠️ **Option (c)** — a 2FA code substituting for the password — **was not built.** Reported, not ruled.

---

## 7 · ⛔ The founder's console steps — exact values

⚠️ **An agent must not sign in to Google Cloud Console.** These are the values to enter.

1. **console.cloud.google.com** → create or select a project (e.g. `vyrobel`).
2. **APIs & Services → OAuth consent screen** → **External**.
   - App name: **Vyrobel** · User support email: yours · Developer contact: yours
   - **Authorised domain:** `vyrobel.com`
   - Publish, or add test users while unverified.
3. **Scopes — exactly these three, nothing more:** `openid` · `email` · `profile`.
   ⚠️ Anything beyond them triggers Google's verification review and asks users to grant access this product
   has no use for. A test pins the scope string.
4. **Credentials → Create credentials → OAuth client ID → Web application.**
5. **Authorised JavaScript origins:** `http://localhost:8000` · `https://vyrobel.com`
6. **Authorised redirect URIs** — ⚠️ **exactly two; the operator realm is the only one built:**
   - `http://localhost:8000/auth/google/callback`
   - `https://vyrobel.com/auth/google/callback`
7. Copy the **Client ID** and **Client secret** into `.env`:
   `GOOGLE_CLIENT_ID=` · `GOOGLE_CLIENT_SECRET=` (and `GOOGLE_REDIRECT_URI=` only if the default
   `<APP_URL>/auth/google/callback` is not what was registered).

⚠️ **Never committed, never invented** — the same class as the Stripe keys. Recorded as **B18** in
`GO-LIVE-REQUIREMENTS.md`; `.env.example` carries both keys **empty**.

### ⚠️⚠️ The feature is OFF without them — no button, no route, no error page

"Configured" and "enabled" are **the same fact**: there is deliberately **no separate on/off flag**, because
a flag can be true while the secret is missing, and that produces the one thing this must never show — a
sign-in button leading to an error page. With both keys empty:

- `enabled()` is false, so the **button is not rendered**;
- both `/auth/google/*` routes return **404** — the honest answer, since the endpoint genuinely does not
  exist. The check is in the controller, **not** at route registration, so a route table cached while the
  credentials were present cannot outlive them;
- **every existing account still signs in with its password.**

All of that is asserted. ⚠️ **This is the state that ships today.**

---

## 8 · ⚠️⚠️ What is proven, and what is not

**A real OAuth round trip cannot be automated** — Google detects and blocks it, and MCP Playwright does not
launch in this environment. The provider is faked **in-process**, so these tests prove **the application's
half and nothing else. A faked provider must never be reported as a round trip.**

| ✅ Proven here (30 tests) | ⛔ NOT proven — needs a human, a real browser, a real account |
|---|---|
| The redirect carries the right client id, scope, redirect URI and state | That Google accepts the client and each redirect URI |
| `state` validated — mismatched, absent and replayed all refused | That the consent screen names Vyrobel and requests only the three scopes |
| The callback provisions exactly the right rows, and nothing when abandoned | That one real Google account completes the flow end to end |
| Linking refuses an email-only match; unlink refuses the last credential | |
| A passwordless user still hits the re-auth gate, and only there | |

⛔ **The three right-hand rows are the go-live checklist** (recorded as B18).

### Mutations — every one applied, landed and RED

| # | mutation | landed | result |
|---|---|---|---|
| **M1** | ⚠️⚠️ **the wrong fix** — match on **email** instead of the provider id | ✅ | **RED** |
| **M2** | allow unlinking the **last credential** | ✅ | **RED** |
| **M3** | **skip state validation** | ✅ | **RED** |
| **M4** | accept an **unverified** address | ✅ | **RED** |
| **M5** | **skip the 2FA challenge** on the shared path | ✅ | **RED** |
| **M6** | trust the **posted email** over the provider assertion | ✅ | **RED** |

M1 was re-run against the final code. No mutation came back green.

---

## ⚠️ Where this brief was wrong

1. ⚠️⚠️ **Its central premise.** *"`SIGN_IN_STATUS` and the last-admin floor … reason from having a
   password"* — **they do not.** All four readers key on `users.status`; the floor already counts a linked
   passwordless administrator correctly, and **no change to it was needed**. The genuine finding underneath
   is a different one: the definition survives passwordless accounts because **the password-reset broker is
   status-scoped, not password-scoped**, so such an account is recoverable.
2. ⚠️ **"the last-admin floor may RED a certified suite — a STOP AND ASK".** Nothing needed changing, so
   nothing reds and there is no decision to bring back.
3. ✅ Correct and load-bearing: that matching by email is takeover; that `/signup` provisions an
   organisation from seven fields; that a Google account has no password by construction; and that a stub
   would be an authentication bypass.

## Not built, deliberately

Apple · the guest and platform-admin realms · owner invitation acceptance (phase 3) · option (c) · any stub
route · any permission change.
