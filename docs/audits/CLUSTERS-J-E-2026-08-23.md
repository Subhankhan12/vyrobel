# Clusters J and E — per-property scoping, and the arrival secrets

**2026-08-23 · J first, because E's form lands on J's authorization surface**

---

## 1 · ⚠️ CLUSTER J — why the gate could not be satisfied

`property_user`'s only writer in `app/` is `PropertyController::store()`:

```php
if ($user !== null && $user->propertyAssignments()->exists()) {
    PropertyAssignment::create([...]);
}
```

⚠️ **A user can only GAIN a pivot row if they ALREADY have one.** From an empty set the scoped population can
never grow — and the set was empty everywhere: **0 rows in the demo database**, and *not even the seeder
writes one* (so this was worse than seeder-only; it was **test-only**).

**Determination: neither a wrong condition nor a deliberate fence.** That guard's job is to keep an
*existing* allow-list consistent when its holder adds a property — "otherwise they'd create a property they
immediately couldn't select" — and **inverting it would silently NARROW a full-org user to one property.**
There is no flag and no "pending" comment anywhere near it. ⚠️ **What was missing is the assignment path
itself**, which the triage sized correctly as *"a screen"*. The condition is unchanged.

### ⚠️ The brief's framing of the fallback is wrong, and it matters

The brief calls the "all properties" fallback a *silent widening* to be stopped. **It is the ratified #38
design** (`User::accessiblePropertyIds()`): *pivot rows ⇒ restricted; no rows ⇒ whole org*, deliberately, so
single-property behaviour is unregressed and nobody is locked out. **The fallback is correct. What was wrong
is that no other state was reachable.** So the proof is stated both ways, and the non-vacuity half is
asserted explicitly: unscoped, the fallback still fires; scoped, it no longer does.

### ⚠️ A display that contradicted enforcement

`OperatorSettings::team()` labelled anyone holding `properties.manage` as "All properties" — but
`accessiblePropertyIds()` consults **only the pivot** and never looks at that permission, so such a user *is*
restricted. Harmless while no row could exist; **a lie the moment cluster J makes them creatable.** Fixed.

### ⚠️ The editor spans the organisation, not the property

The existing list answers "who can access THIS property" and therefore **hides anyone scoped out of it**.
Editing from a list that hides its own subjects is how somebody gets scoped somewhere you can no longer
reach them. **Proven live in the browser**: after scoping Demo Staff to Vista, they vanish from "Who can
access Demo Seaside Property" while remaining editable below.

### Proofs

| # | mutation | result |
|---|---|---|
| M1 | the pivot ignored → everyone gets all properties | ✅ RED |
| M2 | the service's cross-org check removed | ✅ RED *(see below)* |
| M3 | the self-lockout guard removed | ✅ RED |
| M4 | property ids no longer re-resolved under the tenant scope | ✅ RED |
| M5 | the `users.manage` route gate removed | ✅ RED |
| — | the reachability guard, with J's route removed | ✅ RED |

⚠️ **M2 PASSED ON ITS FIRST RUN — my own guard was vacuous.** The *controller* filters the target by
membership independently, so removing the *service's* check changed nothing the HTTP test could see.
**Defence in depth needs a test per layer**, or every layer but the outermost is decorative and the next
caller (a job, a command, a second controller) inherits nothing. A service-layer test was added.

**Blast radius: nobody.** 0 pivot rows exist, so no one loses access; narrowing happens only when an
administrator deliberately assigns scope.

## 2 · CLUSTER E — the arrival secrets, answered as secrets

**At rest — already encrypted, and it is the project convention.** `door_code` and `wifi_password` carry the
`encrypted` cast (`Unit::casts()`), as `guests.email`/`phone` do. ⚠️ `wifi_ssid` and `arrival_notes` are
deliberately **not** encrypted: a router broadcasts its SSID to the street, and directions are not a
credential. Pinned by a test that reads the **raw column past Eloquent** — the cast would hide a plaintext
column perfectly.

**Read-back — the form is write-only; revealing is a separate, audited act.** Rendering a stored door code
into the editor would put a credential to a physical space into every response, browser cache and screenshot
of the units screen, for everyone holding `units.manage`. The field shows only whether one is *set*.
⚠️ **Stated plainly: the reveal does put it on screen once — that is its purpose — and the PRG transport
flashes it through the server-side session for exactly one request.**

**Audit — field NAMES, never values.** ⚠️ *An encrypted column is undone the moment its value is copied
somewhere plaintext*, and nothing sweeps `audit_events`. The test asserts the code appears **nowhere in the
entire table**, not merely in its own row. Reveals are audited too.

⚠️ **A BLANK FIELD MEANS "UNCHANGED", NOT "ERASE".** The form cannot show you what is stored, so a blank
input carries no intent; treating it as an erase would wipe a working door code every time someone edited
the wifi name beside it. Clearing is explicit.

### ⛔ Two policy questions surfaced, not decided

1. **Who may reveal a stored credential?** Currently `units.manage` — the same gate as editing, the widest
   defensible reading. A narrower gate (or a manager-only one) is a founder call.
2. **Should a reveal notify anyone**, or is the audit row sufficient?

### Proofs

| # | mutation | result |
|---|---|---|
| M6 | **#33's window broken** → the code leaks before the window | ✅ RED |
| M7 | the `encrypted` cast removed → plaintext at rest | ✅ RED |
| M8 | the audit copies the VALUES | ✅ RED |
| M9 | blank now erases → a working code is silently wiped | ✅ RED |
| M10 | the unit no longer narrowed to accessible properties | ✅ RED |
| M11 | the `units.manage` route gate removed | ✅ RED |
| — | the reachability guard, with E's route removed | ✅ RED |

**#33 render-verified BOTH ways** (screenshots): a stay 10 days out shows *"Your door code and WiFi unlock on
Mon, 31 Aug — for your security"* and no code; the same stay shifted inside the window shows door code
**5390**, WiFi **Seaside-Guest / welcome2026** and the arrival notes. The demo dates were restored exactly
and the money pin re-verified.

## 3 · The reachability guard

Extended by **one row each**, exactly as it was shaped for — `PropertyAssignment` and `Unit arrival info` —
and both mutation-proven. ⚠️ Arrival info is a **write** path rather than a creation path; the same table
covers it, which is worth noting for whoever adds the thirteenth.

## 4 · What this brief got wrong

| claim | verdict |
|---|---|
| the `property_user` write is gated on an unsatisfiable condition | ✅ confirmed — and it is **test-only**, not seeder-only: no seeder writes one either |
| "the defect is a silent widening"; prove the fallback no longer fires | ⚠️ **half wrong** — the fallback is the ratified #38 design. The defect is that **no other state was reachable** |
| E's form lands on "the unit editor" | ⚠️ **there is no unit editor** — only a store route and a list. The form reuses the D18-5 per-unit modal instead |
| are the arrival columns encrypted at rest? | ⚠️ **already yes** for both credentials — the convention was followed before this pass |
| `resolveProperty()` is "the one place a later fix must touch" | ⚠️ **not the place** — it resolves a *write target*, not access. The fix is the missing writer plus `accessiblePropertyIds()` |

## 5 · Reported, not fixed

- ⚠️ **The operator shell overflows at mobile width** (919px document at a 390px viewport). Known
  desktop-fixed shell; reported per instruction.
- ⚠️ **22 console CSP errors on `/app/units`** — demo photos are absolute-URL'd to `localhost:8000` while the
  verification server ran on 8123. A port artefact of the check, not a defect.
