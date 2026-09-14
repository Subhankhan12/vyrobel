# A passwordless account, made possible and escapable

**2026-09-04.** Founder ruling: option (a) — a social account gets a password, **asked for only when first
needed**. ⚠️ **No provider was installed and none is referenced.** This is the precondition, and it is
independent.

## #1 · The schema, and everything that assumed it

`users.password` was `varchar(255) NOT NULL` — the schema could not hold a passwordless user at all. It is
now nullable. `platform_admins.password` **stays NOT NULL, deliberately**: an admin is created by console
command with a password and mandatory 2FA, and no provider path reaches that table. Excluded explicitly.

⚠️ **`guests.password` was ALREADY nullable** — the guest realm has held passwordless rows since the claim
flow was built, and `Guest::isClaimed()` reads `password !== null`. This brings `users` into line with a
shape the product already relies on rather than inventing one, and `User::hasPassword()` mirrors it.

**Nothing else assumed non-null.** A sweep for `->password` reads found exactly two, both on `Guest`; no
controller, service, factory or seeder reads a `User`'s hash directly.

### ⚠️⚠️ What Laravel does with a null hash — tested, not reasoned

| | result |
|---|---|
| `Hash::check('anything', null)` | **false** |
| `Hash::check('', null)` | **false** |
| `Hash::check('anything', '')` | **false** |
| `Hash::check('', '')` | **false** |
| `Hash::check('anything', 'not-a-bcrypt-hash')` | ⚠️ **THROWS** — *"does not use the Bcrypt algorithm"* |

**It fails closed, including the empty guess** — the case a naive check gets wrong. And this decides
**null over a sentinel**: a sentinel would turn a denial into a **500 on twenty-four sensitive routes**.
Null denies exactly as the empty string already did, which is the property the migration must not disturb.

## ⚠️ The count moved: 30 occurrences, **24 rule sites**

The brief said *30 sites (web 24 · admin 4 · guest 1)* — which sums to 29. Measured from source: **30
`current_password` lines, of which 6 are attribute-name maps** (`'current_password' => 'current password'`),
leaving **24 enforcement sites — 19 `web`, 4 `admin`, 1 `guest`** across 19 controllers.

## #2 · Lazy, never eager — and one definition

Nothing asks at signup, at login, or on any ordinary screen. `RequirePasswordBeforeReauth` keys on **the
request carrying a `current_password` field** — present exactly when a form demanded confirmation — so it
covers all 24 sites, and the 25th, **without a route list to maintain or 24 route edits**.

⚠️ **It only ever REDIRECTS.** There is no branch that lets a request continue; everyone else falls straight
through to the rule that was always going to run.

**The set-a-password screen is the existing change-password card**, on each realm's Security page. It drops
its "current password" field only when there is none to confirm with, and says why:

> Your account does not have a password yet. Set one below — you will be asked for it when confirming
> sensitive changes.

**No second screen, no second definition** — the controller enforces the same split rather than trusting the
view.

## #3 · Not one of the 24 rules changed

`ChangePasswordController` now requires the confirmation **whenever there is a password to confirm with**,
and `prohibited` when there is not. ⚠️ **This is not a weakening**: for every account that has a password the
rule is byte-for-byte what it was, and a passwordless user reaches it only on their **own authenticated
session, setting their own first credential** — there is no other secret they could be asked for.

⚠️ **Setting a password does not confirm the original action.** Proven: after setting one, the interrupted
action still rejects a wrong `current_password`. **Two separate steps, deliberately** — the alternative
would make setting a password a bypass.

## #4 · The return path

The middleware stores `url.intended` and re-flashes the form's input (`except` the three password fields and
the token), so an interrupted POST is not silently discarded — **a payout-destination change that vanished
would be worse than the lockout this fixes**. Abandoning the screen leaves the user on Security with their
input still in the session; nothing is written either way, because the interruption happens **before** the
handler runs.

## #5 · The second-factor trap — resolved end to end

2FA enrolment itself confirms with `current_password`, so a passwordless user could never enrol the thing
that would make them safer. Proven end to end: **interrupted → set a password on the same card → enrolment
reachable.**

⚠️ **Option (c) — letting a 2FA code substitute for the password — is reported, not built.** It was not
ruled, and it would be a genuine change to what "confirm" means.

## #6 · It cannot be silently false

⚠️ `can()` returns false without a team set — pinned by a test, and that shape has bitten three times. Here
it would have sent **every user, password or not, to set a password forever**, while looking like a working
gate.

**The gate reads `User::hasPassword()` — a column, and nothing role-, permission- or team-scoped.** A test
asserts the middleware's source contains no `->can(`, `hasRole`, `hasPermissionTo` or `Gate::`, and proves
that **with no team set at all** a user with a password still falls through to the original rule.

## #7 · Money and auth unmoved

**Every `web` demo account still signs in** — `demo-staff` and `demo-owner` both authenticate with their
real password, asserted through `Auth::attempt`. This was a migration on the authentication table and the
failure mode was that nobody could get in.

## #8 · Both directions, on every site

For each route: the **passwordless user is redirected to `/security`**, and the **user with a password is
not** — they reach the original rule, which rejects a wrong password exactly as before. ⚠️ A gate proven only
in the deny direction may be denying everyone.

### Mutations — every one landed

| mutation | landed | result |
|---|---|---|
| **wrong fix** — let a passwordless user through | ✅ | **RED** |
| make the gate deny everyone (the silent-false shape) | ✅ | **RED** |
| drop the confirmation for users who have a password | ✅ | **RED** |
| `hasPassword()` counts an empty hash as a password | ✅ | ⚠️ **GREEN, then RED** |

⚠️ **The empty-hash mutation was green, and it was a real gap — not a no-op.** Every fixture used `null`, so
the **empty-string** case — the exact shape the original design probe measured, and what a partial write or
legacy row could leave — was never exercised. Closed with a probe that writes past the `hashed` cast.

## ⚠️ Reported, not built

1. **`SIGN_IN_STATUS` and the last-admin floor** will be wrong the day a provider link exists: both reason
   about whether an account *can* sign in, and a passwordless-but-linked account signs in without a password.
   Untouched here.
2. **Option (c)** — a 2FA code as an alternative confirmation.
3. ⚠️ **A duplicated `<label for="vy-sec-cur">`** on the operator security card (two identical labels on one
   input) — pre-existing, unrelated, not touched.

## ⚠️ Where this brief was wrong

The site count: **24 enforcement sites, not 30** — six of the thirty lines are attribute-name maps, and the
brief's own split (24+4+1) summed to 29. And it framed the guest realm as untouched territory: **`guests`
has been nullable-password all along**, which is why the pattern this pass follows already existed.
