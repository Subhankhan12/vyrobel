# AUDIT — PHASE 12 · MARKETING MANAGER & GUEST SUPPORT AGENT · THE CONSOLE SWEEP

**Roles:** Marketing Manager · `demo-marketing@vyrobel-demo.test` · **and** Guest Support Agent ·
`demo-support@vyrobel-demo.test` — reported separately throughout.
**Realm:** operator (`/app`, `web` guard). **These are the last two of the eleven back-office roles.**
**Instrument:** MCP Playwright → `chrome-headless-shell` via the project `.mcp.json`
**Date:** 2026-09-12
**Application code changed:** none — verified, §8. **Suite not run:** no application code changed, so it
is not required. Stated per the brief.

**Evidence tiers.** 🔬 MEASURED · 🖥 BROWSER · 📖 ASSERTED · 📄 RECORDED. **Only 🔬 and 🖥 are facts.**

**Permissions — both derived from config AND the live database, and both agree exactly:**

| role | count | permissions |
|---|---|---|
| Marketing Manager | 🔬 **5** | `corporate_accounts.manage · guests.view · reports.view · reviews.manage · reviews.view` |
| Guest Support Agent | 🔬 **7** | `folios.view · guests.view · housekeeping.view · reservations.view · reviews.manage · reviews.view · schedule.view` |

---

## 0. #1 — THE CONSOLE. **The old "zero errors" claim was TRUE when made. F-20 is a later regression.**

The brief offered three possibilities — the claim was wrong, it regressed, or the sweep was narrower than
it sounded. **It is the second, and the evidence is documentary, not inferential.**

| check | result |
|---|---|
| Did the old audit load `/app/rates`? | 🔬 **Yes** — it reports that screen's layout at two widths (`APP-AUDIT-2026-09-02.md:117, 142`) |
| Could one of its four roles reach it? | 🔬 **Yes** — it drove Hotel Manager, which holds `rates.view` |
| Was its instrument able to see this error kind? | 📄 **Yes, explicitly validated** — *"a page that must emit a console error produced both the `console.error` and the uncaught `ReferenceError`"* |
| **Did `editSeason` exist on 2026-09-02?** | 🔬 **NO.** `git show HEAD:…/rates-grid.blade.php \| grep -c editSeason` → **0**. Working tree → **11** |
| When did it arrive? | 🔬 Last **commit** to that view is `2edf171`, **2026-08-22**. `SEASON-EDIT-DELETE.md` is dated **2026-09-04** — two days *after* the audit |
| Is it committed even now? | 🔬 **No** — the season edit/delete work is uncommitted (+296 lines in that one file) |

**So the sweep was not narrow and the claim was not false. The defect arrived after it, in work that is
still uncommitted.** ⚠️ **A claim can be true when made and false later — check the date before calling a
predecessor wrong.** That is the lesson, and it cuts in the programme's favour this time.

### And the claim still holds today, independently re-measured

🔬 Console captured on **every screen of both full walks — 108 loads**, `console.error` *and* `pageerror`:

| | screens with console output | of those, **403 gate noise only** | **application errors** |
|---|---|---|---|
| Marketing Manager | 25 | 25 | **0** |
| Guest Support Agent | 25 | 25 | **0** |

Every entry is `Failed to load resource: the server responded with a status of 403 (Forbidden)` — the
browser reporting a 403 *document's own status*, on exactly the screens the gate refused. That is
precisely what the old audit described. 🔬 **Zero `pageerror`s. Zero JavaScript errors on any screen
either role can use.**

⚠️ Neither role holds `rates.view`, so 🔬 `/app/rates` is **403 for both** — F-20 could not be observed
here, and is **NOT EXAMINED** by this phase's own walks. Phase 11's measurement stands as its evidence.

---

## 1. #2 — IS F-20 A CLASS? **No. One instance, and the count is one.**

Tested two ways, with a positive control.

**Structural pass.** A detector walked every element with `x-show`/`x-if`/`x-on:click`/`@click`/`x-text`
on all 30 reached screens, resolving each identifier against ancestor `x-data` declarations. It flagged
5 screens. ⚠️ **All 5 were artefacts of my own detector** — confirmed, not assumed:

**Runtime pass, using Alpine's own scope resolver (`Alpine.$data(el)`) and then clicking:**

| screen | my detector | Alpine's resolver | buttons clicked | **errors thrown** |
|---|---|---|---|---|
| `app/search` | 1 suspect | **0** | 2 | **0** |
| `app/security` | 3 suspects | **0** | 5 | **0** |
| `app/front-desk` | 13 | 13 — all `x-for`/`x-if` **template** scopes (`guest`, `nights`, `check_in_label`) | 8 | **0** |
| `app/housekeeping` | 1 | 1 — a template scope (`type`) | 11 | **0** |
| `app/maintenance` | 5 | 5 — **words inside string literals** (`shut`, `fixed`, `minutes`, `thermostat`) | 5 | **0** |

🔬 **31 buttons clicked across the five suspects; `errorsDuring: []` on every one.**

**The positive control is `/app/rates` itself** — the same console instrument recorded **12 errors per
load** there in Phase 11, and **zero** here. So a dead page and a dead control remain distinguishable, and
these screens are alive.

**F-20 stands as a single instance**, confined to `/app/rates`, in uncommitted work. **It is not a class,
and the fix is one misplaced pair — not a structural sweep.**

---

## 2. #3 — GUEST DATA · **invariant #28 holds exactly, measured by kind**

🔬 Counted by kind, never transcribed. As Guest Support Agent:

| surface | emails | phones | card-like | IBAN-like | money figures |
|---|---|---|---|---|---|
| `app/inbox` (thread list) | **0** | **0** | 0 | 0 | 0 |
| `app/inbox?c={thread}` (detail) | **0** | **0** | 0 | 0 | 0 |
| `app/reservations/{id}` | **0** | **0** | 0 | 0 | 10 |
| `app/reviews` · `app/front-desk` | **0** | **0** | 0 | 0 | 0 |
| **`app/guests`** (the CRM list) | 4 | 4 | 0 | 0 | 7 |
| **`app/guests/{id}`** (CRM profile) | 2 | 1 | 0 | 0 | 7 |

**Contact PII renders on the Guests CRM and nowhere else** — the invariant, stated as *"Contact PII
renders on the Guests CRM ONLY (list + profile); front desk / reservations / inbox are name-only"*,
**holds on every surface this role reaches.** 🔬 Zero card-like and zero IBAN-like strings anywhere.
The role legitimately holds `guests.view`, so the CRM exposure is by design, not a leak.

### Thread reachability — established, and it is a founder question, not a defect

🔬 All **5** threads open by URL (`/app/inbox?c={id}`), every one HTTP 200 with its messages rendered.
🔬 The default list shows **4 of 5** — the fifth is *closed* (it offers "Reopen") and filtered out of the
list, **but is still reachable by URL**.

**So thread access is ORGANISATION-WIDE, not assignment-scoped.** The list carries assigned/unassigned
filters, so assignment exists as a *view*, not a *boundary*. ⚠️ For a support role whose job is answering
guests, org-wide access is defensible; for a larger team handling sensitive complaints it may not be.
**Tenant scope is intact** (the one-organisation demo makes cross-tenant untestable, as every phase has
noted). Recorded as a product decision for the founder, not scored as a defect.

⚠️ **A risk I checked and dissolved.** `operator-inbox.blade.php:223` carries a Blade comment —
*"Latest INTERNAL note — staff-only (guests never see this)"* — which looked like F-21's shape: a
user-facing rule living only in source. 🔬 It is not: there is **no internal-note control on this surface
for this role** (`hasInternalNoteControl: false`), and the one thing the agent can write is labelled
**"Reply via Direct…"**, naming the channel it leaves on. The confusion cannot arise. Checked and clear.

---

## 3. #4 — PHANTOM RATIOS, AND **can either role do a job? Yes — both can.**

| role | ratio | the phantom |
|---|---|---|
| **Marketing Manager** | 🔬 **1 of 5** | `corporate_accounts.manage` — registered (category B, *"does not exist as an operator surface"*); 🔬 `corporate_accounts` table also holds **0 rows** |
| **Guest Support Agent** | 🔬 **1 of 7** | `housekeeping.view` — the same registered one seen in Phases 8, 9 and 10 |

⚠️ **Phase 7's prediction is wrong, and I want to correct it plainly:** it named Marketing Manager as
*"likeliest to be MOSTLY INERT."* 🔬 **Four of its five permissions are real** — `guests.view` (12 routes),
`reports.view` (8), `reviews.manage` (3), `reviews.view` (1).

**And both roles have a real, working job surface.** 🔬 On `/app/reviews`, each role gets **14 live
controls** — 7 reviews × **Respond** and **Reject**, posting to `/app/reviews/{id}/respond` and
`/app/reviews/{id}/reject`, with **zero console errors on interaction**. Add the guest CRM (4 controls on
a profile), the inbox (Reply · Snooze · Reopen), and — for Marketing — reports and insights.

**Neither is a role that exists only in the matrix.** That question has now been asked of both and
answered no.

### The ratio across the whole ladder

| role | phantoms / permissions | all registered? |
|---|---|---|
| Housekeeping | 1 / 4 | ✅ |
| Maintenance | 1 / 4 | ✅ |
| Marketing Manager | **1 / 5** | ✅ |
| Guest Support Agent | **1 / 7** | ✅ |
| Revenue Manager | 3 / 10 | ✅ |
| Reservation Agent | 1 / 11 | ✅ |
| Front Desk (Phase 6) | 📄 7 / 20, of which 5 known + 1 service-enforced + F-14 | — |

**Every phantom found since Phase 9 was already in `docs/PHANTOM-PERMISSIONS.md`.** The register is
accurate and the audit has stopped finding new ones — the remaining work is the founder's rulings on it,
not more discovery.

---

## 4. #5 — TWO FULL WALKS, REPORTED SEPARATELY

🔬 54 screens each, scored **by HTTP status** (not by matching one gate's copy — Phase 10's trap).

| | DENIED | REACHED | DOWNLOAD | 500s | zero-`h1` | duplicate `h1` |
|---|---|---|---|---|---|---|
| **Marketing Manager** | 25 | **25** | 4 | 0 | 0 | 1 |
| **Guest Support Agent** | 25 | **26** | 3 | 0 | 0 | 1 |

**F-12 CONFIRMED a fifth and sixth time** — `app/maintenance/{ticket}` renders two `h1` for both roles.

**Downloads, captured as download events:** Marketing — the three report exports + `security/export`.
Support — `schedule/pdf`, `security/export`, and **`reservations/{id}/folio.pdf`** (it holds `folios.view`).

**The two roles differ in an instructive way.** Marketing reaches `insights`, `owners` and `reports`
(via `reports.view`) but is denied `guest-requests`, `search`, `schedule` and the folio PDF. Support is
the mirror: it reaches those four and is denied the three reporting screens. 🔬 Each role's reach matches
its grant precisely; neither reaches anything its permissions do not explain.

**Navigation — no prediction made, and the numbers differ again:**

| role | links rendered | reached | **dead ends** |
|---|---|---|---|
| Marketing Manager | 19 | 18 | **1** — "Payout run" |
| Guest Support Agent | 20 | 18 | **2** — "Owners", "Payout run" |

Running measured tally across the ladder: **1 · 2 · 0 · 2 · 2 · 2 · 1 · 1 · 2**. ⚠️ The count tracks
*which* permissions a role holds, never how many it lacks — Phase 7's retraction holds for the ninth and
tenth consecutive measurement.

---

## 5. #6 — F-13's SILENCE

🔬 Six reached-but-actionless screens per role (`units`, `rack`, `calendar`, `reservations`,
`housekeeping`, `front-desk`): **0 of 6 explain anything** for either role — no "read-only", no "your
role", no pointer to an administrator.

⚠️ As in Phases 10 and 11 I decline to score all six: most are read surfaces for every role, so their
silence is not a permission-communication gap. **The contrast the programme has established still holds** —
both 403 pages explain themselves (including the distinct *"Not in your plan"* plan gate), and the money
screens explain disabled states well. The silence is specific to in-page read-only states.

**F-21's shape — an explanation living only in source — was hunted and not found again** on these roles'
screens (§2, the internal-note check). One instance remains: the agreed-rate disclosure's scope.

---

## 6. #7 — MEASURED BEFORE, UNCHANGED AFTER. **No message was sent.**

🔬 Every table measured before the first load and again at the end:

| table | before | after |
|---|---|---|
| **`rate_calendar`** | 929 / 10912000 / `4fc27b4af1076e86c0d268056067ffa4` | 🔬 **byte-identical** |
| **`messages`** | **9** | **9 — no message sent** |
| `message_threads` · `guests` · `reviews` · `corporate_accounts` | 5 · 10 · 7 · 0 | unchanged |
| `reservations` · `payments` · `owner_ledger` (sum **730640**) · `owner_expenses` | 42 · 16 · 29 · 6 | unchanged |
| `audit_events` · `domain_events` | 160 · 196 | **160 · 196 — zero residue** |
| `rate_seasons` · `notes` · `role_has_permissions` | 0 · 0 · 260 | unchanged |

**Zero append-only residue for the second phase running** — nothing this phase did was a write. The reply,
snooze, respond and reject controls were enumerated and their targets read; **none was submitted**.

🔬 **All 14 web-guard demo accounts authenticate.** 🔬 `DemoDataSafetyGuard::DEVELOPMENT_ENVIRONMENTS`
still `['local','testing']`.

🔬 `git diff --name-only` filtered by session mtime returns **zero tracked files**. F-1 … F-22 stay open;
**this phase adds no new numbered finding** — its two candidates (a second F-20 instance; an
internal-note ambiguity) were both dissolved by measurement.

---

## 7. ⚠️ WHAT THIS BRIEF GOT WRONG

1. **The console dichotomy was incomplete.** The brief offered "either that changed, or the sweep never
   looked properly." Both readings blame one of the two passes. 🔬 The truth is neither: **the code did
   not exist yet.** The audit was right, and the defect is newer than it.
2. **"A clean-console claim that was never true would be a finding about the programme."** It *was* true.
   Saying so is the finding — and it is worth as much as a defect would have been.
3. **Phase 7's "Marketing Manager is likeliest to be mostly inert"** does not hold — 4 of 5 real (§3).
4. **"These are the last two operator roles"** — 🔬 **correct**, verified: eleven back-office roles exist
   and Phases 3–12 have now walked all eleven.

---

## 8. NEAR-FALSE-POSITIVES — three new, **35 total**

33. **"Five more screens have F-20's shape."** My regex identifier-extractor pulled words out of **string
    literals** (`shut`, `fixed`, `thermostat` — text from ticket titles) and could not see `x-for`
    template scopes or keys from `x-data="someFn()"`. 🔬 Alpine's own `$data()` resolver plus 31 clicks
    cleared all five. **Ask the framework, not your own parser.**
34. **"25 screens have console errors"** — per role, which reads as alarming and would have contradicted
    the old audit. 🔬 All 25 are the browser reporting a **403 document's own status**, on exactly the
    gated screens. **Classify console output before counting it.**
35. **"The old zero-errors claim must be wrong, because `/app/rates` throws."** 🔬 Resolved by `git show
    HEAD` — the throwing code did not exist when the claim was made. **A claim can be true when made and
    false later; check the date before calling a predecessor wrong.**

---

## 9. HAND-FORWARD — **the two realms need different instruments**

**Findings open:** F-1 … F-22. No new numbered finding. F-12 confirmed for the fifth and sixth time.

**Baselines for Phase 13 — all unchanged:** `rate_calendar` **929 / 10912000 / `4fc27b4af1076e86c0d268056067ffa4`** ·
`audit_events` **160** · `domain_events` **196** · `messages` 9 · `message_threads` 5 · `guests` 10 ·
`reviews` 7 · `reservations` 42 · `payments` 16 · `owner_ledger` 29 / 730640 · `owner_expenses` 6 ·
`rate_seasons` 0 · `role_has_permissions` 260.

**The operator realm is complete: all eleven back-office roles walked, Phases 3–12.**

### ⛔ Phase 13 — the OWNER PORTAL (`/owner`). What will NOT transfer:

- **There is no `can:` middleware to sweep, so the phantom-ratio method does not apply.** Authorization is
  `EnsureOwnerCapability` reading the **`OwnerTeamRole` capability matrix** over three fixed roles. The
  equivalent instrument is to enumerate that matrix and test each capability, not to grep routes.
- **`EnsurePrimaryOwner` is a second, separate gate** — team management is primary-owner-only and held by
  no role, so self-escalation is structurally impossible. That claim is worth testing directly.
- **The 54-screen count does not carry.** 📄 The prior audit counted **29** owner screens across 3
  accounts; re-measure rather than reuse.
- **`demo-owner@` is REFUSED from `/app` and bounced to `/owner` — that is invariant #26 WORKING.** Do not
  report it as a defect; every prior phase has been warned and it remains the easiest false positive here.
- **The nested isolation is the thing to probe:** sub-user ⊆ owner ⊆ org, composed from the existing
  `OwnerScope` + tenant scope. 📄 `/livewire/update` was the boundary a prior pass closed with
  `OwnerCapabilityMutationBoundaryTest` — verify it still holds rather than assuming.
- **What DOES transfer:** the console sweep, the click-every-button test, row-state proof, checksums,
  measure-before-touching, and the positive-control discipline.

### ⛔ Phase 14 — the GUEST REALM (`/guest`). What will NOT transfer:

- **A genuinely separate `guest` guard over the `guests` table** — not `users`. Nothing about the operator
  permission model applies.
- **The PII rule INVERTS.** In the operator realm the rule is name-only outside the CRM. Here the guest
  **should** see their own contact details, payments and documents — and must see nobody else's.
  **Measuring "0 emails on screen" would be the wrong test.** The right one is: every field shown belongs
  to the authenticated guest.
- **`guests.email`/`phone` are encrypted casts** with blind-index login (📄 `sha1($email)`,
  `GuestAccountService:57`); login is **booking-tied claim**, not open signup.
- 📄 An **append-only wallet ledger** exists — treat it with the money discipline, not the CRUD discipline.
- 📄 The prior audit measured 13 guest screens + 14 isolation probes and found the realm **properly
  responsive at 375px where the operator realm is not** — worth re-confirming, and a rare good-news item.

**Method notes worth keeping:**
- **Classify console output before counting it** (#34) — a 403 document's status is not an application
  error, and conflating them would have manufactured a contradiction with a correct earlier finding.
- **Ask the framework, not your own parser** (#33) — `Alpine.$data(el)` settled in one pass what a regex
  got wrong five times.
- **Date a claim before contradicting it** (#35) — `git show HEAD:<file>` is the cheapest way to know
  whether the thing you are looking at existed when someone last looked.
