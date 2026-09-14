# AUDIT — PHASE 11 · REVENUE MANAGER · THE RISKIEST PHASE

**Role:** Revenue Manager · `demo-revenue@vyrobel-demo.test` (Tomás Ribeiro)
**Realm:** operator (`/app`, `web` guard)
**Instrument:** MCP Playwright → `chrome-headless-shell` via the project `.mcp.json`
**Date:** 2026-09-12
**Application code changed:** none — verified, §8. **Suite not run:** no application code changed, so it
is not required. Stated per the brief.

**Evidence tiers.** 🔬 MEASURED · 🖥 BROWSER · 📖 ASSERTED · 📄 RECORDED. **Only 🔬 and 🖥 are facts.**

**Permissions.** 🔬 **10 — config and DB agree exactly:** `availability.manage · availability.view ·
properties.view · rates.manage · rates.view · reports.view · reservations.view · restrictions.manage ·
schedule.view · units.view`. 📄 Holds no guest permission at all.

> ## ⛔ NO SEASON WAS APPLIED. `rate_calendar` IS BYTE-IDENTICAL.
> 🔬 Before: `rows=929 sum=10912000 md5=4fc27b4af1076e86c0d268056067ffa4`
> 🔬 After: `rows=929 sum=10912000 md5=4fc27b4af1076e86c0d268056067ffa4`
> 🔬 `max(updated_at)` still the seed timestamp `2026-09-05 01:16:00` — **not one rate row was touched.**

---

## 0. #1 — DOES A PRIOR-VALUE TRAIL EXIST? **NO. The recorded claim holds, and it is now measured.**

Established by reading **before any write**, on four independent lines of evidence:

| check | result |
|---|---|
| `rate_calendar` schema | 🔬 `id, organization_id, rate_plan_id, date, price_minor, currency, created_at, updated_at` — **no prior-value column** |
| any snapshot / version / history table for rates | 🔬 **none** — the only `*_history` table in the database is `reservation_status_history` |
| `AuditEventRecorder::record($action, $subject, **$before**, $after)` | 🔬 arg 3 confirmed as `$before` |
| every rate-path call site | 🔬 **11 of 11 pass `null`** — `RateAndRestrictionService:62, 92, 131, 175` and `RateProposalService:86, 99, 138, 159, 181, 218, 235` |

```php
// RateAndRestrictionService::setRate — the certified writer a season apply delegates to
RateCalendar::query()->updateOrCreate(['rate_plan_id'=>…, 'date'=>…], ['price' => $price]);   // in-place
…
$this->audit->record('rate_calendar.updated', $ratePlan, null, $payload + ['price_minor'=>…]);
//                                                        ^^^^ $before
```

🔬 **And empirically:** the database holds 2 `rate_calendar.updated` audit rows; **both have `before = NULL`.**
🔬 The `after` payload carries `nights`, `start_date`, `end_date`, `room_type_id` — *not* the replaced price.

🔬 **The code says so itself.** `AgreedRateDisclosure:22`: *"no provenance column whatsoever — only
`rate_plan_id`, `date` and `price_minor`. Nothing records what a [rate was]."*

**Conclusion: a season apply is irreversible, and I did not perform one.** Not once, not to one night.
`SeasonalRateService::apply()` captures nothing before delegating; it only stamps `applied_at`.

⚠️ **The same gap applies to RESTRICTIONS**, which the record does not mention:
🔬 `RateAndRestrictionService:175` — `audit->record('restriction.updated', $roomType, **null**, …)`.
A min-stay or stop-sell change also has no prior value stored. It is *less* severe only because the flags
are few and visible on screen, so an operator can reconstruct them by eye.

**This judgement — establish rather than perform — has now been right six times** (suspension · statement
finalization · the refund · the cancellation · the cost correction · this).

---

## 1. 🔴 F-20 (HIGH) — **the season Edit and Delete dialogs can never open. A documented, working feature is unreachable.**

🔬 Every page load of `/app/rates` throws:

```
PAGEERROR: ReferenceError: editSeason is not defined
PAGEERROR: ReferenceError: deleteSeason is not defined
```
(12 console errors per load; Alpine 3.15.12 **is** loaded and healthy.)

**Cause, located exactly.** `rates-grid.blade.php:470` declares
`x-data="{ confirmSeason: null, editSeason: null, deleteSeason: null }"`. The *buttons* that set those
variables are inside it (`:531` Edit, `:540` Delete). The *dialogs* are not:

🔬 Measured by DOM containment, then by clicking:

| control | button in scope | **dialog in scope** | dialog opens |
|---|---|---|---|
| **Apply** (`confirmSeason`, `:596`-ish) | ✅ | ✅ | ✅ **opens** (`display:block`) |
| **Edit** (`editSeason`, dialog at `:658`) | ✅ | ❌ **outside** | ❌ **never** (`display:none`) |
| **Delete** (`deleteSeason`, dialog at `:700`) | ✅ | ❌ **outside** | ❌ **never** (`display:none`) |

⚠️ **The Apply row is the positive control** — without it, "the dialogs don't open" could mean Alpine was
broken, my click was wrong, or the page was dead. Apply opens from the same scope on the same page, so
the failure is specific to two dialogs placed outside their `x-data`.

**The backend is correct and complete — I proved it by invoking both routes directly:**

| route | result |
|---|---|
| `PUT /app/seasons/{id}` on my draft | 🔬 **302 PRG** → name, dates and price all changed; `applied_at` still NULL; **`rate_calendar` checksum unchanged** (editing writes no prices, exactly as documented) |
| `DELETE /app/seasons/{id}` | 🔬 **302 PRG** → season gone, `rate_seasons` back to 0, screen shows "No seasons yet." |

**So: `SEASON-EDIT-DELETE.md` opens with *"It now edits and deletes."* The routes exist, the controller
logic is right, the guards are right — and no operator can reach any of it.** A Revenue Manager who
creates a season by mistake cannot remove it through the product.

⚠️⚠️ **And this is the shape the programme has been hunting for ten phases.** Every prior defect was a
*missing* check. This is the first case of **work that was done correctly and then not wired up** — the
"someone already did the thinking and the wiring was lost" shape — appearing in the **UI layer** rather
than the guard layer. It reads as complete to anyone auditing by inspection: the code is all there.

---

## 2. 🟠 F-21 (MEDIUM) — #2: the disclosure, read by its own role at last. **The message is good. Its scope is invisible.**

🖥 **What it says** (apply dialog, `rates-grid.blade.php:620-630`):

> **[N]** of those nights carry a rate negotiated with **[an owner / N owners]** (**[from]** to **[to]**).
> Applying replaces those prices, **and the owner is not told.**

🖥 And again at preview time (`:508`), so it is seen **before** the confirm step, not only at the moment of
committing. **Judged as the brief asks — could a person deciding whether to proceed understand it?
Yes.** It is quantified, dated, names the owner count, and states the consequence including the
notification gap. It does not hedge.

🖥 **The irreversibility sentence is present and, per §0, TRUE:**
> *"The prices it replaces are not stored anywhere. **This cannot be undone.**"*

🖥 Plus a third sentence I had not seen recorded, and it is a good one:
> *"Stays already booked keep the price they were booked at — this changes what is offered from now on."*

### But the known limit is not visible to the reader

📄 The disclosure is deliberately silent on an owner's **self-set** rate — it detects only an **approved
`rate_proposals`** row whose agreed price still **value-matches** the current night
(`AgreedRateDisclosure:107`). That limit is explained — in a **Blade comment**:

```blade
{{-- ⚠️ "Negotiated" is exact: it covers rates an owner agreed through a proposal,
     which is the only kind that leaves a record. See AgreedRateDisclosure. --}}
```

🔬 **A Blade comment does not render.** And 🔬 a sweep of the view for any *rendered* text conveying the
limit — "self-set", "set themselves", "only covers", "does not cover", "owner set" — returns **nothing.**

**The entire signal of scope is the single adjective "negotiated".** The decision not to say "owner" is
right — it avoids implying a check that was not made — but the product stops there. An operator reading
"4 nights carry a rate negotiated with an owner" has no way to learn that a rate the owner set directly on
`/owner/rates` would produce **no warning at all**. The brief's formulation is confirmed by measurement:
**a disclosure whose scope is invisible is half a disclosure.**

⚠️ **NOT EXAMINED — and this bounds the finding.** 🔬 `rate_proposals` = **0** in the seeded world, so the
disclosure could not be observed firing with a non-zero count without manufacturing an approved proposal
(a write). The wording above is read from the template and the service, not seen populated.
**A phase with a seeded approved proposal should watch it render.**

📄 The doc already prices the fix: a nullable `set_by`/`source` pair on `rate_calendar`
(~1 migration + 4 writer edits + backfill). ⚠️ **Worth separating for the founder: that closes the
*provenance* gap, not the *prior-value* gap. Knowing who set a price is not knowing what it replaced —
undo would still be unbuildable.**

---

## 3. #3 — THE TWO-STEP, WALKED TO ITS BOUNDARY. **The preview does not undercount.**

Reversibility proven first: 🔬 `store()` writes **only** a `rate_seasons` row — no audit event, no domain
event, no `rate_calendar` touch — and `destroy()` hard-deletes it, returning the table to its baseline 0.

🖥 Created one draft (`2026-11-10 → 2026-11-13`, €199.00). The preview rendered:

> **NOW €100.00 → AFTER €199.00 · "4 of 4 nights · 3 units" · "includes 1 Fri/Sat night, replacing any
> weekend uplift" · status "Saved · no prices written"**

🔬 **Every figure verified against the database:**

| claim | verified |
|---|---|
| "4 of 4 nights" | ✅ exactly 4 dates in the inclusive range |
| "1 Fri/Sat night" | ✅ exactly 1 — 2026-11-13 is a Friday |
| "3 units" | ✅ the room type has exactly 3 units |
| "NOW €100.00" | ✅ all four existing rows are `10000` minor |
| coverage | ✅ rows present for 4 of 4 nights |

🔬 And the two-step holds: after saving, `rate_calendar` checksum was **unchanged**. The screen's promise
— *"Saving writes no prices"* — is literally true.

**Edit and delete refusals — honest, not silent.** 📖 The rule is drafts-only. 🔬 The guard is
`SeasonActionController::update():82` (`applied_at !== null` → `withErrors`). ⚠️ It is **controller-only,
with no service equivalent — but that is complete here**, because 🔬 `RateSeason` has exactly one create
site (`:49`) and one edit site (`:103`), both in that controller. Unlike F-18, there is no second entrance
to bypass. Judged against Phase 10's reference shape, it is a different but sound arrangement.

🔬 The delete dialog **does** carry the warning, in both branches
(`<template x-if="deleteSeason?.applied">`): *"the nightly prices it wrote stay as they are, and the prices
they replaced are not stored anywhere."* ⚠️ **Though per F-20 no operator will ever see it** — the dialog
cannot open. The honest copy exists and is unreachable.

---

## 4. #4 — THE FOURTH LADDER: **a two-state flag, partially bounded**

⚠️ **The brief's four states do not exist as states.** 🔬 `rate_seasons` has **no status column** —
`id, organization_id, rate_plan_id, name, starts_on, ends_on, price_minor, currency, applied_at,
created_at, updated_at`. So:

| brief's "state" | reality |
|---|---|
| draft / applied | 🔬 `applied_at IS NULL` — a nullable timestamp, not an enum |
| superseded | 🔬 **not stored at all** — an emergent property of precedence (narrowest wins) |
| deleted | 🔬 a hard row delete (no `SoftDeletes`, deliberately — documented) |

| transition | guard | verdict |
|---|---|---|
| edit an **applied** season | ✅ controller `:82`, and the controller is the only writer | **bounded** |
| delete | none — any season is deletable | **by design**, documented, and the dialog says what it does and does not do |
| **apply an ALREADY-APPLIED season** | 🔬 **none — `apply()` checks `applied_at` nowhere** | ⚠️ **unguarded** |

⚠️ **Re-apply is unguarded, and `SeasonalRateService:167` does `forceFill(['applied_at' => now()])->save()`
— so a second apply overwrites the record of when it was first applied.** That is the same shape as the
re-termination defect `CLAUDE.md` describes for #29 (*"a second termination is a REWRITE, not an
append… it destroys `cancelled_at`"*). Here a second apply destroys the original `applied_at`, and
silently re-overwrites any price set in the meantime — including an owner's.

🔬 **Established by READING ONLY. Deliberately not probed** — even in a rolled-back transaction, invoking
`apply()` writes to `rate_calendar`, and rule #1 admits no exception. The brief endorses read-and-rollback
as the safe instrument generally; for *this* path it is not safe, and the code is unambiguous enough that
a probe would add nothing.

---

## 5. 🟡 F-22 (LOW–MEDIUM) — season CRUD writes **no audit row at all**

🔬 Measured, not inferred: I created a season, edited it, and deleted it — and `audit_events` stayed at
**160** and `domain_events` at **196** across all three. 🔬 `SeasonActionController::store/update/destroy`
call `audit->record` zero times.

**So nothing records who created, edited or deleted a pricing object.** The apply step *does* audit (via
the certified writer, `rate_calendar.updated`) — but with `before = null` and with the *rate plan* as the
subject, not the season. Combined with F-20 (the delete dialog can't open, so deletions must go through
the route directly), a season can appear and disappear with no attributable author.

Same family as **F-17** (housekeeping status writes no audit row) and **F-9** (password change writes none
while an export does). 🔬 Confirmed again this phase from the other side: the three report exports I
triggered in the §6 walk each wrote an audited row.

---

## 6. #5 — RESTRICTIONS AND INVENTORY

⚠️ **A correction to my own first sweep.** I initially found no writer for restrictions and was about to
report "a create with no delete". 🔬 **Wrong:** `RateActionController:87-185` writes `min_stay`,
`stop_sell` and `closed_to_arrival` via `POST /app/rates`, and the code is explicit —
`$attributes['min_stay'] = $minStay;  // null clears it`.

**So there is no CRUD-missing-half here: the same control that sets a restriction clears it.** My grep had
searched for the service method name rather than the column writers.

| | |
|---|---|
| gate | 🔬 `can:rates.manage` — **not** `restrictions.manage` |
| reversible? | ✅ `min_stay => null` clears; the booleans set false |
| prior value recorded? | ❌ 🔬 `audit->record('restriction.updated', …, **null**, …)` |
| reaches a live offer? | ✅ **yes** — `RestrictionGuard` enforces `min_stay`/`stop_sell`/`closed_to_arrival` at booking time, and `AriSnapshotBuilder` pushes them to channels |

**That last row is the boundary, and I stopped at it.** A `stop_sell` would change what a stranger is
quoted on the storefront. 🔬 `restrictions` unmoved at 4; `availability` unmoved at 653.

---

## 7. #6 — THE WALK: all 54

🔬 Scored **by HTTP status**, not by matching one gate's copy — the trap that produced Phase 10's NFP #25.

**23 DENIED · 26 REACHED · 5 DOWNLOAD.** Zero 500s. Zero screens with no `h1`.
🔬 `app/channels` correctly classified DENIED this time — HTTP 403, title *"Not in your plan"* — the
**plan** gate, distinct from the permission gate, and it explains itself.

**F-12 CONFIRMED a fourth time** — 🔬 `app/maintenance/{ticket}` renders two `h1`.

**Downloads (5):** the three report exports (`csv`/`pdf`/`xlsx` — this role holds `reports.view`),
`app/schedule/pdf`, `app/security/export`.

**Navigation — and this breaks the brief's pattern.** 🔬 22 links rendered, 21 reached, **ONE dead end**:
"Payout run" → `/app/payouts/run` (403). ⚠️ The brief states the *same two* dead ends have appeared for
three consecutive roles and treats that as the expected shape. **Here it is one, not two** — this role
holds `reports.view`, so "Owners" resolves. Running measured tally: **1 · 2 · 0 · 2 · 2 · 2 · 1**.
The count tracks *which* permissions are held, never how many are lacked — the retraction holds, and this
is the first role to vary the number since it was made.

**F-13's silence.** 🔬 Six reached screens render no write controls and no explanation (`units`, `rack`,
`reservations`, `housekeeping`, `maintenance`, `owners`). ⚠️ As in Phase 10 I decline to score all six:
most are read surfaces for every role. The pattern worth carrying is that **both 403 pages explain
themselves well**, and the money screens explain disabled states well (Phase 7) — the silence is specific
to in-page read-only states, not systemic.

### Phantom ratio — **3 of 10, and all three are registered**

| permission | routes | code sites | verdict |
|---|---|---|---|
| `rates.manage` | 12 | 13 | ✅ REAL |
| `reports.view` | 8 | 7 | ✅ REAL |
| `schedule.view` · `reservations.view` · `rates.view` · `units.view` · `properties.view` | 1–3 | 1–3 | ✅ REAL |
| **`restrictions.manage`** | **0** | **0** | ⚠️ registered — *"stay restrictions ride the rates surface → `rates.manage`"* |
| **`availability.manage`** | **0** | **0** | ⚠️ registered — *"availability is written only through `AssignmentService`"* |
| **`availability.view`** | **0** | **0** | ⚠️ registered — *"read through the calendar and rack"* |

All three appear in `docs/PHANTOM-PERMISSIONS.md` category A (enforced under a different permission) and
are awaiting a founder ruling. **Not new findings** — and §6 confirms the register's claim for
`restrictions.manage` is factually correct.

---

## 8. #7/#8 — NOTHING MOVED, NOTHING FIXED

🔬 Measured before and after. **Every table unmoved, including a zero-residue phase:**

| table | before | after |
|---|---|---|
| **`rate_calendar`** | 929 / 10912000 / `4fc27b4a…` | **identical** |
| `rate_seasons` | 0 | **0** |
| `restrictions` · `rate_plans` · `rate_proposals` · `availability` | 4 · 5 · 0 · 653 | **unchanged** |
| `reservations` · `payments` · `owner_ledger` (sum **730640**) · `owner_expenses` | 42 · 16 · 29 · 6 | **unchanged** |
| `audit_events` · `domain_events` | 160 · 196 | **160 · 196 — no residue** |
| `role_has_permissions` | 260 | **260** |

⚠️ The zero audit residue is itself the evidence for **F-22**: three season operations wrote no trail.

🔬 **All 14 web-guard demo accounts authenticate.** 🔬 `DemoDataSafetyGuard::DEVELOPMENT_ENVIRONMENTS` is
still `['local','testing']`.

🔬 `git diff --name-only` filtered by session mtime returns **zero tracked files**. F-1 … F-19 stay open;
**F-20, F-21, F-22** are added.

---

## 9. ⚠️ WHAT THIS BRIEF GOT WRONG

1. **The season's four states do not exist.** There is no status column: draft/applied is a nullable
   `applied_at`, superseded is never stored, deleted is a hard row removal (§4).
2. **"The same two dead ends for three consecutive roles"** does not generalise — 🔬 this role has **one**
   (§7). The observation was true of those three roles and was being read as a rule.
3. **The prior-value claim was RIGHT** — and I want to say so as plainly as the corrections. 📄 "the audit
   row's `before` is literally NULL" is 🔬 exactly true, at 11 call sites and in both existing rows.
4. **"Read-and-rollback is the safe instrument here"** — for the season *apply* it is not, because the
   rollback still writes to `rate_calendar` first. I used reading alone for that one path (§4).

---

## 10. NEAR-FALSE-POSITIVES — four new, **32 total**

29. **"The delete dialog doesn't warn that prices stay."** It does — inside
    `<template x-if="deleteSeason?.applied">`. I had grepped for the *controller flash's* wording
    ("does not restore") instead of the *dialog's* ("stay as they are"). **Two surfaces, two wordings, one
    rule — search for the concept, not one phrasing.**
30. **"No route writes restrictions."** `RateActionController` does, under `rates.manage`. I grepped for
    the service method name I had already seen rather than for the columns. **Search for the data, not the
    function you expect.**
31. **"Clicking Apply didn't open the dialog."** My `/^Apply$/` matcher hit the **rate grid's** Apply —
    a POST to `/app/rates`, the certified rate writer — not the season's. 🔬 The checksum proved nothing
    was written, but this was a genuine near-miss on a money path. **On a page with a money control,
    scope every selector to its section before clicking.**
32. **"Alpine is broken on this page."** Alpine 3.15.12 is loaded and healthy; the Apply dialog opens
    normally. Only two dialogs fail, because only they sit outside the scope. **The positive control is
    what separated "page broken" from "two elements misplaced" (F-20).**

---

## 11. HAND-FORWARD

**Findings open:** F-1 … F-19, **plus F-20 (dead Edit/Delete dialogs), F-21 (invisible disclosure scope),
F-22 (unaudited season CRUD)**. F-12 confirmed a fourth time.

**Baselines for Phase 12 — all unchanged:** `rate_calendar` **929 / 10912000 / `4fc27b4af1076e86c0d268056067ffa4`** ·
`audit_events` **160** · `domain_events` **196** · `rate_seasons` 0 · `restrictions` 4 · `availability` 653 ·
`reservations` 42 · `payments` 16 · `owner_ledger` 29 / 730640 · `owner_expenses` 6 ·
`role_has_permissions` 260.

**Remaining:** Marketing Manager · Guest Support Agent — **then the owner portal and the guest realm.**

### ⚠️ The two realms — nothing learned here transfers

Both are **separate identity tables behind separate guards**, and every technique this programme has built
assumes the operator realm's shape. What each will need:

- **Owner portal (`/owner`)** — `web` guard but `External Owner` role, refused from `/app` by invariant
  #26 (*that refusal is correct; do not report it as a bug*). Authorization is **not** spatie permissions:
  it is `EnsureOwnerCapability` reading the `OwnerTeamRole` capability matrix, with **primary-owner-only**
  actions behind `EnsurePrimaryOwner`. **So the phantom-ratio method does not apply** — there is no
  `can:` middleware to sweep. It needs a capability-matrix equivalent. The nested isolation
  (sub-user ⊆ owner ⊆ org) is the thing to probe, and `/livewire/update` is the boundary a prior pass
  closed with `OwnerCapabilityMutationBoundaryTest`.
- **Guest realm (`/guest`)** — a genuinely separate `guest` guard over the `guests` table, with
  **encrypted** `email`/`phone` and blind-index login. **PII discipline inverts here:** the operator rule
  is name-only outside the CRM; in the guest realm the guest owns their own data. Login is booking-tied
  claim, not open signup. Expect `sha1($email)` hashing (`GuestAccountService:57`) and an
  append-only wallet ledger.
- **For both:** the 14 demo accounts are operator-realm; the owner is `demo-owner@`, the guest
  `demo.traveller@`, both `/ password`. Neither realm has a 54-screen surface — the counts must be
  re-measured, not carried.

**Method notes worth keeping:**
- **A positive control separates "broken page" from "broken element"** (F-20, NFP #32). It has now earned
  its place twice in two phases.
- **Scope a selector to its section before clicking on a money screen** (NFP #31).
- **Checksum the table you are protecting**, not just its row count — `md5(group_concat(...))` caught
  nothing this phase precisely because nothing happened, which is the point.
