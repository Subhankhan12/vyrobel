# VYROBEL — BROWSER AUDIT PROGRAMME · CLOSING SUMMARY

**Phases 0–14 · 2026-09-12 · five realms · MCP Playwright over `chrome-headless-shell`**

**Nothing was fixed during the audit. No application code was changed in any of phases 0–14.** 🔬
`git diff --name-only` filtered by session mtime returned **zero tracked files** at the end of every phase.
The only repo writes were the fifteen `docs/AUDIT-PHASE-*.md` reports and this summary.

⚠️ **A separate FIX programme began on 2026-09-12 and does change code.** §2 and §2b carry its live status;
each fix is scoped to one finding and gated on the suite + PHPStan L5 + Pint + the money gate
(**`mode=FULL`** — ⚠️ **which is scoped to TENANT ONE and is blind to the second organisation, so a green
there proves nothing about tenant two**).

⚠️⚠️ **THIS PREAMBLE STATES NO COUNTS, DELIBERATELY.** It once carried a stale pass count, a stale finding
count and a stale ruling count at the same time — and **two different pass counts in one document**. **Count
the pass rows in §2's ledger; re-derive the findings from the registers in §2 and §2b; count the rulings in
§4.** A number written into a preamble has no mechanism to stay true. ⚠️ **The pass ledger in §2 is the count
of passes; §2 and §2b are the count of findings; §4 is the count of rulings. Each is kept where the rows are,
so the row IS the number.**

⚠️⚠️ **ON WHAT IS COMMITTED, `git` IS THE AUTHORITY — NOT THIS SENTENCE.** Durably true: the programme's first
**twenty-three passes were committed on 2026-09-14 in seven unpushed commits ending at `19c59931`**, which
closed the condition that hid F-20. ⚠️ **What sits ON TOP of that is a moving quantity — run
`git status --porcelain`.** 🔬 2026-09-19: FIX 29's three closures (F-45, F-46, F-47) were found sitting
uncommitted and were committed by FIX 30. ⚠️⚠️ **WORK LEFT IN THE TREE IS A RECURRING DEFAULT OF THIS
WORKFLOW, NOT A ONE-OFF STATE** — the condition was closed by one pass and reopened by the very next.
**A reader who checks is right; a reader who trusts a frozen sentence is not.**

⚠️ **This document is the only record of the findings in §2b and the rulings in §4 — count them there, not
here.** It went eight passes stale once already; §2 said "two are closed" while twenty were.

**Evidence tiers used throughout.** 🔬 MEASURED · 🖥 BROWSER · 📖 ASSERTED · 📄 RECORDED.
**Only 🔬 and 🖥 were ever treated as facts.**

---

## 1 · COVERAGE — what was walked

| realm | what was audited | phases |
|---|---|---|
| Marketing / storefront (anonymous) | the public front, the booking flow as a stranger | 0 |
| Authentication | 24 doors; 6 token doors × 4 states each | 1, 1b |
| Platform admin (`/admin`, `PlatformAdmin` table) | the realm, the seeded-admin credential question | 2 |
| **Operator back office (`/app`)** | **all ELEVEN back-office roles**, 54 screens each | 3–12 |
| Owner portal (`/owner`) | 47 screens × 3 accounts (primary, accountant, view-only) | 13 |
| Guest realm (`/guest`) | 58 routes; 16 own screens + 16 isolation probes | 14 |

**Operator roles walked (Phases 3–12):** Property Owner · Group Admin · Hotel Manager · Front Desk ·
Accountant · Housekeeping · Reservation Agent · Maintenance · Revenue Manager · Marketing Manager ·
Guest Support Agent.

---

## 2 · FINDINGS — F-1 … F-27

**All 27 were OPEN at the close of the audit programme; fixing was out of scope by instruction in every
phase.** ⚠️ **Many fix passes have since run — count the rows in the ledger below rather than trusting a
number in this sentence, which disagreed with the preamble's for sixteen passes. The status column is the
live record.**

⚠️ **THIS SECTION WAS LAST RECONCILED DURING FIX 03 AND WAS EIGHT PASSES STALE.** It read "two are closed"
while twenty were. 🔬 The count below was **re-derived one finding at a time from the fix reports**, not by
adding up their headlines — see the closure ledger.

| status | count | findings |
|---|---|---|
| ✅ **CLOSED** | **20** | F-1, F-2, F-4, F-5, F-6, F-7, F-8, F-9, F-11, F-12, F-13, F-15, F-16, F-17, F-18, F-19, F-20, F-22, F-26, F-27 |
| ◐ **PARTLY CLOSED** | **1** | F-3 (the `public` realm closed; the **admin** realm reverted and reported as F-32) |
| 🔨 **BUILT · UNWIRED** | **1** | F-23 (written and mutation-proven; **deliberately not wired**, blocked on R-GATE) |
| ⛔ **BLOCKED ON A FOUNDER RULING** | **5** | F-10, F-14, F-21, F-24, F-25 |
| | **27** | |

⚠️ **"Closed" means one thing here: the defect no longer reproduces, and a test fails if it returns.** It does
not mean the surrounding area was swept. **Five of the twenty closures also produced a NEW finding** in the
code beside them (§2b) — §N.13.1's shape, holding again.

### The pass ledger — ⚠️ the rows are the count; never write a number into this heading

| pass | date | findings | what it changed |
|---|---|---|---|
| **FIX 01** | 2026-09-12 | F-26 | Guest-deletion disclosure: screen, scheduled-deletion email, in-app notification; the false `GuestDeletionService` docblock corrected. **Words and comments only** — the mechanism was verified sound and untouched. |
| **FIX 02** | 2026-09-12 | F-20 | The Alpine scope lifted off the `.vy-fade` season card onto a wrapper enclosing the card **and** both dialogs. |
| **FIX 03** | 2026-09-13 | F-15 | `HousekeepingService::assertTransition()` — the guard in the service, its one status writer. |
| **FIX 04** | 2026-09-13 | F-18 | A source-state guard in `CheckInReservation` and `CheckOutReservation`. |
| **FIX 05** | 2026-09-13 | F-17, F-22, F-9 | The three missing actor trails, PII-safe; plus the `rate_season` morph alias without which the trail could not physically reference a season. |
| **FIX 06** | 2026-09-13 | F-1, F-4, F-5 | Three flashes set by controllers and rendered by no view — **and a fourth door found in the sweep** (F-28). |
| **FIX 07** | 2026-09-13 | F-13, F-16 | The housekeeping board explains itself; the two views and the server now state one rule. |
| **FIX 08** | 2026-09-13 | F-23, F-27 | The guest first-contact landing (closed). The owner positive gate **built, mutation-proven and left unwired** pending R-GATE. |
| **FIX 09** | 2026-09-13 | F-2, F-3, F-7, F-8, F-11, F-12, F-19 | Seven small findings. F-3 closed for `public` only; its admin half **reverted and reported** (F-32). |
| **FIX 10** | 2026-09-13 | F-6 | Unit edit + deactivate/reactivate. **No delete** — argued from the cascade, not from difficulty. |
| **FIX 11** | 2026-09-13 | — | The 375px shell fold. ⚠️ **CHANGED NOTHING**; costed options reported (F-33). |
| **FIX 12** | 2026-09-13 | — | Rate-plan lifecycle, the F-6 shape one object over. |
| **FIX 13** | 2026-09-13 | — | **This reconciliation.** No application code; no suite run. |
| **FIX 14** | 2026-09-13 | F-29 | The OTA modification writer bounded in **two layers** — an idempotent recorded no-op in `BookingImportService::modify()`, an authoritative throw in `AssignmentService::reimportBooking()` (the raw writer, so every caller is covered). ⚠️ **Three of four statuses; `checked_in` is R6, a founder ruling.** Invariant #29 corrected in `CLAUDE.md`. |
| **FIX 15** | 2026-09-14 | **F-34** + FIX 12's deferred controls | ⚠️ **THREE copies of `primaryRatePlan()`, not one** — unified into the model and filtered. The rate-plan rename / on-sale controls wired, and off-sale plans now stay listed so deactivation is not a one-way door. ⚠️ **The first attempt reproduced F-20 exactly** (16 console errors) — the scope boundary was measured in the browser, not read. |
| **FIX 16** | 2026-09-14 | F-37 | The season **Apply** dialog lifted out of its card. ⚠️ **The brief's stated cause was wrong**: 🔬 every `.vy-card` carries `backdrop-filter`, which clamps on its own — 18 of 25 cards clamp with NO transform — so changing the animation would have fixed nothing and touched ten layouts. ⚠️ **Swept 17 screens / 66 rendered dialogs: exactly ONE was clamped.** |
| **FIX 17** | 2026-09-14 | §5 — the second tenant | ⚠️ **The founder's ruling.** A second organisation (`northgate-demo-city-rooms`, **GBP**) provisioned through `TenantProvisioningService`. The money gate is now **SCOPED to tenant one** rather than re-pinned — its six figures keep their exact values. 🔬 Cross-tenant isolation **DRIVEN for the first time**: no leak. ⚠️ Surfaced **F-38** (a hardcoded currency). |
| **FIX 18** | 2026-09-14 | §5 — the refund path | ⚠️ **The first money-write route ever EXERCISED**, in tenant two. Partial, repeat, operator-absorbed and excess refunds all driven through the real route. 🔬 The owner adjustment posts **debit 8000 of a 10000 refund** — the owner's net share, correct direction; the absorbed branch posts **no row at all**. ⚠️ Surfaced **F-39** (the warning sits below the refund button) and **F-40** (every domain-event listener is registered twice). |
| **FIX 19** | 2026-09-14 | F-40 | ⚠️ **Event discovery turned OFF** (`->withEvents(discover: false)`), after measuring that **DISCOVERED-ONLY was ZERO** — no listener lost its registration. `DomainEventRecorded` 14 → **7**; `AccessTokenCreated` 2 → **1**; 🔬 **zero duplicate pairs remain across all 28 events**. ⚠️ The acceptance test — FIX 18's ordering mutation — **discriminates again**. Surfaced **F-41**. |
| **FIX 20** | 2026-09-14 | §5 — the season apply | ⚠️ **The action this product cannot undo, EXECUTED ONCE** in tenant two. 🔬 The preview promised "4 of 4 nights" and **exactly 4 rows changed** (9000 → 15000 GBP); neighbours untouched; whole table still 120 rows, sum 1,080,000 → **1,104,000 as predicted**; storefront **£270 → £450**. The disclosure fired naming 4 nights and **exactly those 4** were replaced. ⚠️ **The irreversibility claim is now MEASURED AFTER AN APPLY, not reasoned — and it HELD.** |
| **FIX 21** | 2026-09-14 | §5 — statement finalization | ⚠️ **The third irreversible path, EXECUTED ONCE** in tenant two. 🔬 Withholding **predicted 4,320 and posted 4,320** (round(21,600 × 20%), debit); net 21,600 → **17,280**; all 4 ledger lines claimed; parts equal the whole. **The booked-stay claim — untested for twenty passes — MEASURED and HELD** (27,000 → 27,000). Second finalize throws. Surfaced **F-42**. |
| **FIX 22** | 2026-09-14 | F-38 + F-42 | ⚠️ **THE COUNT IS THE FINDING: 22 sites hardcoded a currency, not two.** 🔬 The sweep enumerated every money-render surface — 17 PDFs, 22 mailables, 6 money notifications, 12 CSV exports and the owner API are **all clean** (each passes a real currency); the 22 live in views, Livewire components and three shared `App\Support` helpers. ⚠️ **The worst was not F-38**: `StayEstimator` printed `'€'` into the PUBLIC GUEST CHECKOUT breakdown, so a GBP property quoted "€150.00 × 4" under a "£600.00" total at the point of payment. ⚠️ **One concept had FOUR symbol maps** — collapsed to one. ⚠️ **A crude view grep said 101 and the truth was 56**; 51 lived only in comments. ⚠️ **The guard found a 22nd site the human sweep missed.** ⚠️ **And the suite caught the pass's own error**: neutralising a bound message turned `MoneyBoundRefusesTest` RED — that refusal is *required* to name its limit. Now named, in the owner's real currency. |
| **FIX 23** | 2026-09-14 | F-39 + F-41 | ⚠️⚠️ **THE BRIEF'S CENTRAL PREMISE WAS FALSE.** It said the amount-aware form was ordered correctly and should be copied. 🖥 **MEASURED: it was not.** The warning lived in a 340px aside (`.vy-detail-cols`); at 1280 that column starts higher than the second form, so the warning read 661 vs the button's 829 and **looked** ordered. At 375 the grid collapses and it fell to **2152 — below BOTH buttons (1113, 1472)**. ⚠️ **A COLUMN ARTEFACT, NOT AN ORDER — there was no correct sibling to copy.** Moved into the refund card above the payment list: 375px **978**, ahead of 1247/1606. ⚠️ **Above the button: YES. Seen on arrival at 375px: NO** — the card's own title sits at 904, already below the 812 fold (F-33). ⚠️ **Both paths ALREADY carry a fold-proof `confirm()`** naming irreversibility. ⚠️ F-41 guarded by replay, not registration count. |
| **FIX 24** | 2026-09-14 | — | ⚠️ **THE PROGRAMME COMMITTED.** Twenty-three passes in seven grouped commits ending `19c59931`, 444 files, **not pushed** — closing the condition that hid F-20. `.mcp.json` deliberately left untracked (machine-local path). Added the `STATE.md` §10 pointer that could not find this audit. |
| **FIX 25** | 2026-09-14 | §5 — journeys | Three end-to-end journeys walked (guest claim, an operator's day, the admin catalogue). ⚠️ Tenant two has **no Front Desk role**, so the day was split rather than glossed. |
| **FIX 26** | 2026-09-14 | F-47, F-48 + §5 | The last two journeys and a second claimed guest. ⚠️ **§5 moved a second time**: guest-to-guest isolation MEASURED (identical 404 bodies, no size oracle). Found **F-48** — a suspended tenant can still sell. |
| **FIX 27** | 2026-09-14 | F-45, F-46 | The folio and payment money-route cluster, exercised in tenant two by hand with every figure predicted first. ⚠️ **The premise "none of the remaining routes is irreversible" DIED here** — no void, no delete, and a refund cannot be proven to reverse. |
| **FIX 28** | 2026-09-14 | F-49 | The payout and invoice cluster. 🔬 Established by four measured layers that a real Stripe transfer is impossible in this build. Found **F-49** — `reverse()` is a provable inverse that no route reaches. Two payout routes left **establish-only** rather than spend an irreversible action on a fixture. |
| **FIX 29** | 2026-09-14 | F-45, F-46, F-47 | The three closures, each mutation-proven. ⚠️ **F-44 RE-GRADED 🟡→🟠 with nine further sites** — and one of them (`RatesGrid:537`) had been written **by FIX 22 while closing that very class**. Two certified tests updated to pin BOTH branches. ⚠️ Left its work uncommitted. |
| **FIX 30** | 2026-09-19 | — | Committed FIX 29's three closures, then corrected this document where it stated what only the tree can answer. ⚠️ **Twelve stale absolutes found where three were reported**, including §8's flat *"this programme committed nothing"*. The preamble now points at `git status` instead of freezing an answer. |
| **FIX 31** | 2026-09-19 | — | §4 rebuilt as a decision surface: thirteen rulings re-measured from the source, each given today's default and named options; R-SUSPEND, R-REVERSE and R6 transcribed in; the ladder reduced to one table. ⚠️ **R-SEASON's edit half CLOSED BY CODE**; R-RATE and R-ADMIN-COPY both smaller than recorded. |

### CLOSURE LEDGER — what proved each closure

⚠️ **Derived per finding from the pass that closed it.** Where a closure rests on a test, the test is the
regression net; where it rests on a browser reading, it is **not re-runnable** and is marked so.

| finding | pass | what proved it |
|---|---|---|
| F-1 | 06 | 🖥 Both halves read **on the rendered page** — the change confirmation *and* the gate interruption. `FlashRenderedTest`. |
| F-2 | 09 | 🔬 The device-authorization grant no longer offered: `oauth` routes **10 → 5**, the four device routes gone. |
| F-3 | 09 | 🔬 `public` realm branched and asserted. ⚠️ **Admin half NOT closed** — see F-32. |
| F-4 | 06 | 🖥 Renders on the sign-in page and sits **above the fold at 375px** (y=280). |
| F-5 | 06 | 🖥 Renders **and names the new address**; all four outcomes distinguishable; a tampered link leaks nothing. |
| F-6 | 10 | 🔬 Capacity moved **2 → 3** on reactivation, read from the availability spine. `UnitLifecycleTest`. |
| F-7 | 09 | 🖥 Exactly one `h1` on both create screens, **in both branches**, measured on the rendered page. |
| F-8 | 09 | 🔬 ⚠️ **Three** unnamed inputs, not two — the third sat behind `x-show`. All named from authored text. |
| F-9 | 05 | 🔬 `user.password_changed` resolves to a named person, with IP and user-agent and **no secret**. |
| F-11 | 09 | 🔬 Exactly 2 links escaped the filter; fixed where the filter lives. Hotel Manager 1→0, Front Desk 2→0. |
| F-12 | 09 | 🖥 One `h1`; the ticket title demoted to `h2` and still rendered. |
| F-13 | 07 | 🖥 The board explains itself to all three roles that see it empty; controls stay absent. |
| F-15 | 03 | 🔬 The illegal jump refused, **proven by row state**, with a positive control. `HousekeepingLadderTest`. |
| F-16 | 07 | 🔬 ⚠️ **Two-way, not three-way** — the server agreed with the List view exactly. Both views **and a third surface** (the drag gate) now match it. |
| F-17 | 05 | 🔬 The audit sibling resolves to a named person with before/after. `ActorTrailTest`. |
| F-18 | 04 | 🔬 Refused **by row state** in a rolled-back transaction, with a positive control. `ReservationLadderTest`. |
| F-19 | 09 | 🔬 Comment corrected against the code — **and an unrecorded sibling found** in a controller docblock (F-31). |
| F-20 | 02 | 🖥 All three dialogs open, **proven by clicking**, with a positive control. |
| F-22 | 05 | 🔬 ⚠️ The real blocker was a **missing morph alias** — the trail structurally could not reference a season. |
| F-26 | 01 | 🔬 ⚠️ **Smaller than recorded** — a truncated extraction had hidden existing copy. |
| F-27 | 08 | 🔬 ⚠️ `signedRoute()` **would have been inert** — all five links sit behind `auth:guest`. The landing was fixed instead. |

### 🔴 HIGH

| # | realm | finding | evidence |
|---|---|---|---|
| **F-15** ✅ CLOSED (FIX 03) | operator | **The housekeeping status ladder is unbounded.** The controller validates the target and never the row's current state. 🔬 A `pending` task was driven straight to `inspected`: a dirty room became guest-ready in one request, `started_at` still NULL, `inspected_by` naming the person who skipped both steps. ⚠️ Bounded 2026-09-13 by `HousekeepingService::assertTransition()` — the guard in the SERVICE (its one status writer), the controller holding no status logic. ⚠️ **The map refuses 2 of 16 moves and leaves 11 AMBIGUOUS ones permitted, pending founder rulings — `docs/HOUSEKEEPING-LADDER.md`.** | P8 — proven by row state, restored exactly |
| **F-20** ✅ CLOSED (FIX 02) | operator | **The season Edit and Delete dialogs could never open.** 🔬 `ReferenceError: editSeason is not defined` on every load of `/app/rates`: the buttons sit inside an `x-data` scope, their dialogs outside it. Routes, controller logic and guards are all correct — proven by direct invocation — and **no operator can reach any of it.** ⚠️ Lived in **then-uncommitted** work — 🔬 as measured at the time, `editSeason`: 0 occurrences in HEAD, 11 in the working tree (committed 2026-09-14, `19c59931`; the measurement stands, its tense did not). | P11 — DOM containment + clicking, with a positive control |

### 🟠 MEDIUM

| # | realm | finding | evidence |
|---|---|---|---|
| **F-1** ✅ CLOSED (FIX 06) | operator | The passwordless re-auth interruption is silent — `password_status` is rendered by no operator view; it also silences a *successful* password change. | P1 |
| **F-2** ✅ CLOSED (FIX 09) | auth | `GET /oauth/device` returns **500** to an unauthenticated visitor. | P1 |
| **F-4** ✅ CLOSED (FIX 06) | owner auth | Owner email-verification outcome never shown — `auth/login.blade.php` renders `session('status')` zero times. | P1b |
| **F-5** ✅ CLOSED (FIX 06) | admin auth | Admin email-verification outcome never shown — same shape, making it a class three doors wide. | P2 |
| **F-6** ✅ CLOSED (FIX 10) | operator | **A unit can be created and never edited or removed** — no update/delete route or Livewire action, despite `is_active` and `deleted_at` columns. | P3 |
| **F-9** ✅ CLOSED (FIX 05) | operator | A password change writes **no audit row**, while an export does. | P3 |
| **F-10** ⛔ BLOCKED | platform | `billing.manage` / `settings.manage` / `roles.manage` are reachable by nobody below Property Owner. | P4 |
| **F-11** ✅ CLOSED (FIX 09) | operator | Navigation dead ends — a **per-link filtering gap**. ⚠️ The scaling claim was **RETRACTED** in P7; measured tally across ten roles: 1·2·0·2·2·2·1·1·2·(1). | P5, retracted P7 |
| **F-13** ✅ CLOSED (FIX 07) | operator | The housekeeping board renders with zero controls and no explanation. ⚠️ **Bounded** in P7, then **located** in P8: it is the *Board* view, and it *does* say "Assign to start" — a rule the server does not have. | P6→P8 |
| **F-16** ✅ CLOSED (FIX 07) | operator | **Two views of one screen state different rules and the server agrees with neither.** The choice lives in `localStorage['vy:hk:view']` — one click on "Board" leaves a housekeeper unable to start any task, with no server trace. | P8 |
| **F-17** ✅ CLOSED (FIX 05) | operator | A housekeeping status change writes **no audit row**, while three siblings in the same service do. `domain_events` has no actor column and the table has no `started_by`/`completed_by` — **who cleaned a room is recoverable from nowhere.** | P8 |
| **F-18** ✅ CLOSED (FIX 04) | operator | `CheckInReservation` / `CheckOutReservation` guard only *target-equals-current*, never a legal **source** state. 🔬 Proven in rolled-back transactions: a cancelled booking → `checked_in`; a confirmed booking → `checked_out`. ⚠️ **Latent — no reachable caller today**; both live callers guard correctly. | P9 |
| **F-21** ⛔ BLOCKED | operator | The agreed-rate disclosure is **correct but its scope is invisible** — the limit (it cannot see an owner's self-set rate) lives only in a **Blade comment**, which never renders. | P11 |
| **F-23** 🔨 BUILT · UNWIRED (FIX 08) | owner | **No positive "is an owner" gate.** 🔬 An operator reached `/owner`, `/balance`, `/payouts`, `/settings` (all 200) and downloaded a statement CSV. **No data leaked** — every figure €0.00, the CSV 2 lines (watermark + header). An unguarded admission with a misleading surface, and the inverse of invariant #26. | P13 |
| **F-25** ⛔ BLOCKED | owner | **An owner cannot discover that a season replaced their negotiated rate.** No notification covers it; the only listener on `rate_calendar.updated` pushes to channel managers; `/owner/rates` shows today's value with no history — and that screen is co-owner only. | P13 |
| **F-26** ✅ CLOSED (FIX 01) | guest | **Account deletion cancels and refunds upcoming bookings; the guest was under-told.** 🔬 `processDue()` calls `cancelWithPolicyRefund` on every live reservation. ⚠️ **The audit overstated this**: the screen already said *"Upcoming reservations are cancelled per the property's policy"* — a Phase 14 `grep -o 'This schedules[^<]*'` stopped at the first `<` and missed it. What was genuinely wrong: it said "**Upcoming**" while the code cancels *every* live booking **including a stay in progress**; it did not quantify; and the scheduled state, the email and the notification said nothing at all. ⚠️ The class docblock also contradicted its own method ("the policy is display-only"). | P14, corrected in FIX 01 |
| **F-27** ✅ CLOSED (FIX 08) | guest | **The paid-but-unclaimed window.** The confirmation page's only action is "Back to {property}"; the email's five deep links use `route()` not `signedRoute()` and land a first-time guest on `/guest/login`, headed **"Welcome back"**, where the bridge is a footer link. | P14 |

### 🟡 LOW

| # | realm | finding | evidence |
|---|---|---|---|
| **F-3** ◐ PARTLY CLOSED (FIX 09) | auth | Role-flavoured 403 copy on roleless doors. | P1 |
| **F-7** ✅ CLOSED (FIX 09) | operator | `/app/reservations/create` and `/app/group-booking` render **zero `h1`**. Confirmed P9. | P3, P9 |
| **F-8** ✅ CLOSED (FIX 09) | operator | Two unnamed date inputs on `/app/group-booking`. | P3 |
| **F-12** ✅ CLOSED (FIX 09) | operator | Two `h1` on the maintenance ticket detail. 🔬 **Confirmed six times** (P5, P9, P10, P11, P12 ×2). | P5 → P12 |
| **F-14** ⛔ BLOCKED | operator | `housekeeping.view` is enforced nowhere. ⚠️ **REGISTERED** — in `PHANTOM-PERMISSIONS.md`, asserted by a test, awaiting a founder ruling. **Not a discovery.** | P6, re-framed P9 |
| **F-19** ✅ CLOSED (FIX 09) | operator | A route comment **inverts the truth**: it states `AssignmentService::terminate()` has no status guard and calls the fix "outstanding". 🔬 The guard exists and throws; `CLAUDE.md` records it as shipped invariant #29. | P9 |
| **F-22** ✅ CLOSED (FIX 05) | operator | Season create / edit / delete write **no audit row** — 🔬 `audit_events` unmoved across all three operations. | P11 |
| **F-24** ⛔ BLOCKED | owner | **No notification of any kind when a property is archived.** The *financial* consequence is handled deliberately ("money that was earned does not stop having been earned"); the communication is absent. | P13 |

---

## 2b · FINDINGS THE FIX PROGRAMME FOUND ITSELF — F-28 … F-49

**Twenty-two findings no audit phase recorded.** They were found by *changing* the code, not by walking it — which
is the argument for numbering them here rather than leaving them in conversation. ⚠️ **Five sit in the code
immediately beside a finding being fixed** (§N.13.1). **None was decided by FIX 13.**

⚠️ **F-29 was closed by FIX 14 (2026-09-13) — for three of its four statuses.** The fix copied `cancel()`'s
rule, and that rule is `TERMINAL_STATUSES`; **`checked_in` is not terminal**, so a channel rewriting a stay
the guest is physically in is still permitted and is now **R6** in `docs/STATUS-LADDERS.md`. ⚠️ **A partial
closure is recorded as partial.**

| # | sev | realm | finding | found by | status |
|---|---|---|---|---|---|
| **F-28** | 🟠 MED | owner | **A fourth unrendered flash door.** `payout_method_error` was set by `OwnerConnectController` and rendered by no view — a **Stripe Connect onboarding failure was silent**. The audit's class was three doors wide because three is what it grepped. | FIX 06, 2026-09-13 | ✅ CLOSED (FIX 06) — 🖥 rendered and read on the page |
| **F-29** | 🔴 **HIGH** | operator | **A second reservation-status writer, outside the ladder F-18 closed.** `BookingImportService::modify()` builds `'status' => 'confirmed'` unconditionally and passes it to `AssignmentService::reimportBooking()`, which does `$reservation->update($attributes)` with **no source-state guard**. 🔬 An OTA modification of a `cancelled`, `no_show`, `checked_in` or `checked_out` booking **rewrites it back to `confirmed`** — a terminal reservation resurrected by an external system, with no audit row for the status move. ⚠️⚠️ **AND IT IS BIGGER THAN THE STATUS — established by FIX 14.** `reimportBooking()` **releases every occupying line and re-occupies the new ones**, so a dead booking SILENTLY RE-TOOK INVENTORY; and the same raw `update()` overwrites **`total_minor`, which is MONEY** — read on the booking-confirmation email's grand total (#17), the owner booking detail, the guests CRM and the reservations list's `SUM(total_minor)` (which deliberately counts cancelled rows). **A cancelled booking's price was rewritable by an external system.** ⚠️ **Its sibling `cancel()`, three methods up the same file, carries an explicit terminal guard and a comment explaining why** (invariant #29). Two paths, one concept, one of them guarded. ⚠️ **Reachable only with a live channel** (Channex is account-gated), so it is not exercisable today — but it is a *caller*, which F-18 never had. | FIX 04, 2026-09-13 | ✅ **CLOSED (FIX 14) for the three TERMINAL statuses** — proven by row state on `cancelled`, `no_show` and `checked_out`, and on the raw writer. ⚠️ **`checked_in` REMAINS OPEN — R6**, a founder ruling |
| **F-36** | 🟠 MED | operator | **A refused channel modification surfaces on no screen.** 🔬 FIX 14's refusal writes `reservation.ota_modification_refused` to `audit_events`, and **nothing renders it**: `ReservationDetail`'s timeline reads `reservation_status_history` plus audit rows whose `subject_type` is a **Payment** (three payment actions only), so a reservation-subject row is filtered out — and **no operator surface reads `webhook_events` or `sync_jobs` at all** (grep across `app/Livewire/` and `resources/views/`). ⚠️ **The F-13 shape**: the system refuses correctly and tells nobody. A channel could be sending modifications that are all being dropped and the only evidence is a database query. ⚠️ Note a throw would not help either — that path writes `sync_jobs.last_error`, not `channels.last_error`, and the Channels screen reads the latter. | FIX 14, 2026-09-13 | ⛔ **OPEN — reported, not fixed** (a channel-activity surface is a feature, not a guard) |
| **F-30** | 🟡 LOW | owner | `OwnerUnitStatusService::setActive()` **accepted `$actorId` and dropped it** — owner pause/resume audit rows recorded the change and not the person. The parameter's presence made the trail look complete. | FIX 10, 2026-09-13 | ✅ CLOSED (FIX 10) |
| **F-31** | 🟡 LOW | operator | **F-19's unrecorded sibling** — the same inverted claim about `AssignmentService::terminate()` in a controller docblock. The audit found one copy; there were two. | FIX 09, 2026-09-13 | ✅ CLOSED (FIX 09) |
| **F-32** | 🟡 LOW | admin | **`ErrorRealm` has no admin branch**, so a platform-admin 403 carries operator-flavoured copy. ⚠️ **A fix was written and REVERTED**: it collided with a certified assertion that pins the current copy. Closing it means changing that assertion — a decision, not a fix. F-3 is partly closed for this reason. | FIX 09, 2026-09-13 | ⛔ **OPEN — reported, not fixed** |
| **F-33** | 🟠 MED | operator | **The operator shell folds at 375px** and the navigation becomes unreachable. ⚠️ **FIX 11 changed nothing** — the obvious fix pushed all 22 nav links off-screen, caught by one further measurement. Costed options were reported to the founder. ⚠️ **54 screens sit inside this shell.** | FIX 11, 2026-09-13 | ⛔ **OPEN — costed, undecided** |
| **F-34** | 🟠 MED | operator | **`RoomType::primaryRatePlan()` returns a deactivated plan when it is the declared default.** The fallback branch filters `is_active`; the `default_rate_plan_id` branch does not. `setRatePlanActive()` refuses only the *last active* plan, never the *declared default*. 🔬 The precondition is live on seeded data — "Double Room" has a declared default **and** 3 active plans, so the refusal does not fire. ⚠️ **FIX 12 built the first operator-reachable way to produce this state.** The consequence (both Rates surfaces edit an off-sale plan) is read from source, **not exercised**. | FIX 12, 2026-09-13 | ✅ **CLOSED (FIX 15)** — ⚠️ **it was THREE lookups, not one**: `RoomType::primaryRatePlan()`, plus copies in `RatesGrid` and `UnitAvailabilityService` whose default branch was ALSO unfiltered and whose fallback was saved only by an eager-load constraint hundreds of lines away. One definition now; all three entry points proven to agree. ⚠️ **R7 open** — whether deactivating the declared default should also clear `default_rate_plan_id` |
| **F-43** | 🔴 **HIGH** | guest | **The public checkout quoted a per-night price in euros under a pound total — at the point of payment.** 🔬 `App\Support\Folio\StayEstimator::estimate()` built the room line's sublabel as `'€'.number_format(...)` while the amount beside it was built in `$currency` — **the same function's own argument**, one line above. It renders on `booking/checkout.blade.php:253`, the PUBLIC guest checkout, and also on the operator's Property-Settings sample stay. ⚠️ **More severe than F-38, and found only because F-38's sweep was widened from views to shared helpers**: F-38 misleads an operator reading their own grid; this one misquotes a *guest* about what they are about to be charged, on a page whose whole job is to state the price. ⚠️ **A shared `App\Support\*` helper — the anti-drift family's own rule broken from the inside.** ⚠️ Latent while every property was EUR. | FIX 22, 2026-09-14 | ✅ **CLOSED — FIX 22.** Routed through `MoneyFormatter::symbol($currency)` |
| **F-44** | 🟠 MED | all realms | ⚠️⚠️ **RE-GRADED BY FIX 29 (was 🟡 LOW): the class is WIDER and LIVE.** 🔬 Nine FURTHER sites fall back on an **EMPTY COLLECTION** rather than a null column — `OwnerRates:200`, `OwnerMyUnits:122`, `OwnerReports:599`, `OwnerInsights:330`, `OperatorPortfolio:84,217`, `GuestPayments:49`, `GuestWalletController:40`, and `RatesGrid:537`. ⚠️ **FIX 22's reachability evidence was the SCHEMA, and the schema cannot see an empty set** — which is why 45 fallbacks were proved dead while this shape was never counted. ⚠️ **One of the nine (`RatesGrid:537`) was written BY FIX 22 while fixing F-38** — an instance of the very class the pass was closing. ⚠️ `portfolioCurrency()` is closed as **F-46**; the other nine are REPORTED, not fixed. **45 unreachable `?? 'EUR'` currency fallbacks sit in live code, indistinguishable at a glance from the reachable one that was a real defect.** 🔬 FIX 22 enumerated 46 sites reading a currency as `$x->currency ?? 'EUR'`; checked against the schema, **45 can never fire** — `currency` is `NOT NULL` on `reservations`, `payments`, `folios`, `rate_calendar`, `rate_plans`, `owner_ledger`, `owner_statements`, `payouts` and `properties`. The 46th (`maintenance_tickets.currency`, the schema's only nullable currency column) WAS reachable and was fixed. ⚠️ **NUMBERED BECAUSE IT IS EXACTLY THE CAMOUFLAGE THAT HID F-38**: a dead fallback and a live one read identically, so a reviewer cannot tell which matters without re-checking the schema each time. ⚠️ **AND IT IS ONE `nullable()` AWAY FROM BEING LIVE** — making any of those columns nullable silently converts a dead branch into F-38 again, with no test to catch it. ⚠️ Not a present defect: nothing renders a wrong currency today. | FIX 22, 2026-09-14 | ⛔ **OPEN — recorded, deliberately not changed.** A 45-file edit that alters no behaviour and can be proven by no test; the founder's call whether the camouflage is worth the churn |
| **F-45** | 🟠 MED | operator · guest | **A folio charge recorded no author.** 🔬 `FolioService::postCharge()` and `applyWalletCredit()` wrote a `domain_events` row and **no `audit_events` row**, while every payment method in the SAME service (`recordPayment`, `recordCheckoutIntent`, `settlePayment`) wrote both. ⚠️ **A domain event cannot answer "who"** — 🔬 `domain_events` has **no actor column**, measured. So a write that INCREASES WHAT A GUEST OWES was unattributable. ⚠️ **F-17/F-22's class in a writer FIX 05's sweep never reached** — which is exactly why `FINAL-VERIFICATION.md` called that class closed "where measured". ⚠️ 🔬 **Exactly TWO writers create a `FolioItem`** in the whole codebase, and `postAddOn`/`applyTax`/`applyFee` funnel through `postCharge`, so one recorder covers every entrance. | FIX 27, 2026-09-14 | ✅ **CLOSED — FIX 29, 2026-09-14.** Both writers audit, copying the sibling's shape; the morph map was checked first (`folio_item` IS registered, so no throw). 🔬 Mutation-proven: recording the row with a null actor goes RED |
| **F-46** | 🟠 MED | owner | **A GBP owner saw euros on their first day, on their own earnings page.** 🖥 MEASURED in tenant two: "EARNED YTD **€0.00**" and "NEXT PAYOUT **€0.00**" for an owner paid in GBP. `FormatsOwnerData::portfolioCurrency()` returned a bare `'EUR'` when its collections were empty — so it fires **before any statement or payout exists**, on four screens (dashboard, balance, payouts, expenses). ⚠️ **The amount is right and the symbol is wrong — the dangerous direction**: zero looks identical in every currency, so there is no arithmetic cue. ⚠️⚠️ **F-44 RECORDED THIS CLASS AS LATENT AND IT WAS LIVE.** FIX 22's reachability evidence was the SCHEMA (every `currency` column is NOT NULL) — correct, and **blind to a fallback keyed on an EMPTY COLLECTION**. | FIX 27, 2026-09-14 | ✅ **CLOSED — FIX 29, 2026-09-14.** Derives from the OBJECT: `owners.payout_currency` (`char(3)` NOT NULL). 🔬 Mutation-proven in BOTH tenants — the EUR control would go RED on a fix that hardcoded GBP |
| **F-47** | 🟡 LOW | guest | **A guest who could not claim was sent to a login wall.** 🖥 The refusal copy was never the defect — 403, no identity leak, the expiry named, a human route suggested. But its only CLICKABLE route was "Back to your trips" → `/guest/trips`, **which requires the account the person was trying to create**. ⚠️ **A guest who cannot claim simply leaves, and nothing in this product would ever report it** (Phase 14). | FIX 26, 2026-09-14 | ✅ **CLOSED — FIX 29, 2026-09-14.** `ErrorRealm::home('guest')` now follows the SESSION, not the URL prefix: signed out → `/guest/claim` (`web`-only, no auth guard), signed in → their trips. ⚠️ Enumeration discipline kept — the same public door, so it discloses nothing. Wording untouched and pinned |
| **F-48** | 🟠 MED | platform | **A suspended tenant can still sell.** 🖥 MEASURED with a positive control: suspending tenant two set `organizations.status='suspended'` and its `/stay/{org}` route correctly **404'd** while an active org's returned **200** — so the guard discriminates. But **`/book/{property}` — the storefront that actually takes bookings — still returned 200**. `ResolvePublicOrganization` guards `/stay/{organization}` only; the per-property storefront never passes through it. ⚠️ **The admin's own confirm copy is honest about this** (*"it does not yet block their access"*); the middleware's comment (*"a suspended org's public storefront is down"*) is true of ITS route and reads broader than it delivers. | FIX 26, 2026-09-14 | ⛔ **OPEN — A FOUNDER RULING, not a defect to patch.** Whether suspension should close the booking path is a product decision about what suspension MEANS. Recorded, not decided |
| **F-49** | 🟡 LOW | owner · platform | **A payout can be made and cannot be un-made through the product.** 🔬 `OwnerPayoutService::reverse()` is a genuine, provable inverse — it APPENDS a `reversed` row (never editing the paid one), returns the source statement to `finalized`, and audits with a real `before={"status":"paid"}`. ⚠️ **But no route reaches it**: no controller calls it and no route exists, so an operator can pay an owner and cannot reverse it in the UI. ⚠️ **The capability exists and is simply unrouted** — unlike FIX 27's folio cluster, which has no inverse at all. | FIX 28, 2026-09-14 | ⛔ **OPEN — A FOUNDER RULING.** Whether a payout reversal should be operator-reachable, or stay a deliberate back-office-only action, is a product decision. Recorded, not decided |
| **F-42** | 🟡 LOW | owner | **The owner Balance page says "every euro" to a GBP owner.** 🖥 `resources/views/livewire/owner/balance.blade.php:17` hardcodes the subtitle *"What's cleared, what's held, and where every euro is on its way to."* Tenant two's owner is paid in **GBP** and the page's own figures render **£172.80** correctly — only the prose is wrong. ⚠️ **F-38's class in words rather than a symbol**, and the only instance found: a sweep of the owner and operator views for hardcoded currency NOUNS returned this one. ⚠️ Lower severity than F-38 because no figure is mislabelled — but it is on the page an owner reads to find out what they are owed. | FIX 21, 2026-09-14 | ✅ **CLOSED — FIX 22, 2026-09-14.** Reworded to *"where every payment is on its way to"* — **neutral, not "every pound"**: a static subtitle cannot cheaply carry a variable, and neutral prose is correct in every currency. A prose guard now holds it |
| **F-41** | 🟠 MED | platform | **`AuditOAuthTokenGranted` has no idempotence guard, and was being double-registered.** 🔬 Dispatching a single `AccessTokenCreated` in a rolled-back transaction wrote **TWO** `owner.connected_app_token_issued` audit rows. Every other listener on the duplicated bus survived only because each carries its own guard (a per-event static, `firstOrCreate`, or an idempotency key); this one carries none — it calls `audit->record()` unconditionally. ⚠️ **The duplicate registration is fixed (F-40), so it now writes one row** — but the listener is still idempotent only by virtue of being registered once, which is **idempotence by luck, not by design**: a redelivered event or any future second registration double-writes again. ⚠️ **Latent in the demo** (zero tokens issued), live on the first real one. | FIX 19, 2026-09-14 | ✅ **CLOSED — FIX 23, 2026-09-14.** A per-process `static $processed` keyed on `tokenId` — the shape 4 of its 7 domain-bus siblings use, not a seventh invented. 🔬 **Proven by REPLAY**: two dispatches of one grant → **1 row**; guard removed → **2 rows**. A second, different token still audits |
| **F-39** | 🟠 MED | operator | **The refund screen's irreversibility warning sits BELOW the button that issues the refund.** 🖥 MEASURED at 375×812: the one-click "Refund the remaining £270.00" button is at y=1352 and the warning — *"It can't be reversed. A refund reverses a payment, not the charges…"* — is at **y=2492 on a 2918px page**, off-screen entirely. At 1280×900 the warning is on-screen (y=647) but **still below that button** (y=488). ⚠️ Phase 7 called this the best disclosure in the product and FIX 01 and FIX 12 copied it; its *wording* is exemplary and its *position* undoes it on the one screen that moves money irreversibly. ⚠️ The second, amount-aware form ("Review & issue refund", y=1826/906) DOES sit after the warning — only the one-click path does not. | FIX 18, 2026-09-14 | ✅ **CLOSED — FIX 23, 2026-09-14.** The callout MOVED (not copied) from the right rail into the refund card, above the payment list — so it precedes every button on BOTH paths. 🖥 375px: warning 2152→**978**, buttons 1247/1606. Wording byte-identical. ⚠️ **The brief's premise was WRONG** — see §2c |
| **F-40** | 🟠 MED | platform | **Every domain-event listener is registered TWICE, and the second set is in a different order.** 🔬 The dispatcher holds 14 entries for `DomainEventRecorded`: 7 explicit `Event::listen` calls from `AppServiceProvider` in the documented money-first order, and 7 more discovered by Laravel as `Class@handle` — in which `DispatchNotificationsForDomainEvent` (best-effort) precedes `AdjustOwnerLedgerOnRefund` (money). **So every domain event runs every side effect twice.** ⚠️ **No money is lost today**: the explicit money listener runs first and each listener is idempotent (a per-event static guard plus `firstOrCreate` on the ledger key). But the documented ordering — the fix made after an owner debit was once lost behind a best-effort listener — describes only HALF of what is registered, and correctness now rests on the idempotency guards rather than on the order. 🔬 Found by mutation: deleting the explicit registration changed nothing. | FIX 18, 2026-09-14 | ✅ **CLOSED (FIX 19)** — Laravel 13 ships `$shouldDiscoverEvents = true`, so discovery registered a second copy of every listener. Turned off after enumerating what it provided (**discovered-only: 0**). 🔬 14 → 7 registrations, the documented order is now the resolved order, and the ordering mutation reddens again. ⚠️ **The ordering fix had been half-effective since the day it shipped** |
| **F-38** | 🟠 MED | operator | **The rates grid labels every per-unit price in EUR, whatever the property's currency is.** 🔬 `RatesGrid::unitRows()` calls `$this->money($minor, 'EUR')` — a hardcoded literal — while the room-type row two hundred lines up correctly passes `$rate['currency']`. On the GBP tenant every per-unit nightly price renders as **€90.00** when the stored row is **GBP 9000**. ⚠️ **The amount is right and the label is wrong**, which is the dangerous direction: an operator reading €90 on a £90 room has no arithmetic cue that anything is off. ⚠️ **NOT a cross-tenant leak** — the figure is the tenant's own; it was invisible for the whole programme only because every property was EUR. | FIX 17, 2026-09-14 | ✅ **CLOSED — FIX 22, 2026-09-14.** The resolver now answers price AND currency together (`NightlyRate::forUnitWithCurrency`), so the symbol follows the same override→type precedence as the figure. 🔬 Mutation-proven in BOTH currencies |
| **F-37** | 🟠 MED | operator | **The season APPLY dialog is clamped inside the `.vy-fade` card — FIX 02 lifted two of three dialogs out, not three.** 🖥 MEASURED (FIX 15): its overlay is a containing-block child of `div.vy-card.vy-fade` (`transform: matrix(1,0,0,1,0,0)`), so `position: fixed` resolves to the CARD, not the viewport. At **375×812 the panel lands at y=755 and runs to 1069 — off the bottom of the screen**; at 1280×900 the panel is visible but the overlay is 988×381 at (261,204) instead of covering the viewport, so the backdrop does not catch clicks outside the card. ⚠️ `editSeason` and `deleteSeason` threw `ReferenceError` and were moved out; `confirmSeason` never threw, so nothing pointed at it. ⚠️⚠️ **This is the APPLY dialog — the one that writes money.** The fix is to move it beside the other two, inside the same plain wrapper. | FIX 15, 2026-09-14 | ✅ **CLOSED (FIX 16)** — lifted out of the card, beside the two siblings FIX 02 moved. 🔬 After: overlay **375×812 @(0,0)** at every scroll offset (was 341×855 @(17,284)); panel y=20, fully visible; D2 green; the agreed-rate clause proven to FIRE and render. ⚠️ **Corrected shape: the off-screen panel is INTERMITTENT** (scroll- and content-dependent — 49px below the fold at one offset, fully visible at others); **the broken backdrop was CONSTANT**. A test asserting only "panel in viewport" would itself be flaky |
| **F-35** | 🟡 LOW | tests | **The suite is non-hermetic.** 🔬 `Http::preventStrayRequests()` occurs **0 times** in `tests/` and `app/`. A suite that *could* reach the internet is non-hermetic whether or not it currently does. ⚠️ **This is a hardening item and was NOT the cause of anything observed** — see the process finding in §6. | the suite investigation, 2026-09-13 | ⛔ OPEN — hardening |

⚠️ **F-33 is the one finding whose evidence exists in no file.** FIX 11 changed nothing, so it wrote no
report; its 375px measurement lives only in the conversation that took it. **This row is now its only record** —
which is the drift this reconciliation exists to stop, caught in the act.

---

### ⚠️ THE CURRENCY-SOURCE MAP — where every money surface gets its currency (FIX 22, 2026-09-14)

🔬 **MEASURED by enumeration, not assumed.** The question "where does this screen's currency come from" has
**three** legitimate answers in this product, and conflating them is what made the defect class hard to see.

| domain | the authority | surfaces |
|---|---|---|
| **the stay** | `reservations.currency` / `rate_plans.currency` / `rate_calendar.currency` / `properties.currency` — **all NOT NULL** | the grid, the storefront, the checkout, the folio, the guest portal |
| **the owner** | `owner_statements.currency` / `payouts.currency` / `owner_ledger.currency` — **all NOT NULL** | statements, payouts, balance, the owner PDFs |
| **the platform** | `config('billing.default_currency')` — what **Vyrobel** charges an operator | `/app/billing`, the paywall, `/pricing` and the marketing pages |

⚠️ **THE THIRD IS NOT A DEFECT AND MUST NOT FOLLOW A TENANT.** A GBP hotel still pays Vyrobel in the
platform's currency; "fixing" `/pricing` to render £ would be the same defect reversed. The 41 marketing-page
symbols are therefore **correct as they stand** and the guard excludes them **by path, deliberately**.

⚠️ **AND A SCREEN CAN GENUINELY SHOW TWO CURRENCIES AT ONCE.** `OwnerStatementView::totals()` groups by
currency and joins the results with `·`; `OperatorOwnerDetail` carries a real `multi_currency` flag. So "the
page currency" is not always a single value, and any fix that resolves ONE currency per page would have been
wrong for exactly these screens. Both were left per-row.

⚠️ **THE FORMATTER ALREADY REQUIRED A CURRENCY, AND THAT WAS NOT ENOUGH.** `MoneyFormatter::display(int,
string)` has never had a default. F-38 satisfied it — with a literal. **A required argument prevents
forgetting a currency; it cannot prevent asserting the wrong one**, which is why the guard had to become a
rule about what may appear *at a call site* rather than a change to a signature.

### FIX 23 GATES — 2026-09-14

🔬 **MEASURED, all after the final edit.**

| gate | result |
|---|---|
| **Suite** (ALONE, uncontended) | **3508 tests · 3495 passed · 0 failed · 25,498 assertions · 13 skipped** · 735 s |
| | (FIX 22 baseline 3503/3490/25,485 → **+5 tests, +13 assertions**, nothing else moved) |
| **PHPStan L5** | 0 errors |
| **Pint** | clean |
| **Money gate** | ✅ `mode=FULL` — **929 / 10,928,000 / 0 / 42 / 16 / 0** unchanged |
| **Tenant-one pins** | `owner_ledger` **29 / 730,640** · `payments` **16** · `reservations` **42** · `rate_calendar` **929 / 10,928,000** — all unchanged |
| **Tenant two** | 120 / 1,080,000 / 0 / 1 / 1 / 0 — FIX 18's −8,000 owner adjustment and refunded payment **undisturbed** |
| **Demo accounts** | **16 web-realm accounts authenticate by ATTEMPT** (vyrobel-demo 14 · northgate 2), 0 failures; 2 guest-realm passwords verify |
| **Seeder refusal** | `production` **REFUSED**; `local` permitted (the control) |
| **Browser** | 0 console errors; server killed, **parent AND child confirmed dead**, port refused |

⚠️ **AUDIT RESIDUE, REPORTED WITH ITS CAUSE — AND THEN ZERO.** The browser measurement left **2
`login_activity` rows** in the dev DB (`demo-staff`, `demo-accountant`) — real records of real logins, no
money, no tenant data. The `migrate:fresh --seed` repeatability check then rebuilt the database, so the
residue is now **0 rows**; it is recorded here because it existed, not because it survives. **Zero
`owner.connected_app_token_issued` rows were ever written to the seeded DB**: the F-41 replay ran in
`vyrobel_test` under `RefreshDatabase`.

⚠️ **`migrate:fresh --seed` RE-VERIFIED AFTER THE REBUILD:** clean, **2 orgs** (not a third), money gate again
`mode=FULL` with all six figures, `owner_ledger` back to **29 / 730,640**.

⚠️ **AN INSTRUMENT ERROR, CAUGHT AND RECORDED.** The mutation checks used
`grep -c "self::\$processed[\$key] = true"` — inside DOUBLE quotes the shell expands `$processed`/`$key`, so
the pattern could never match and the check reported **0 whether or not the guard was present**. It was a
broken instrument, not evidence. The mutations' real proof is the RED test (2 rows / the ordering failure);
the restores were re-verified with a literal `grep -F`. ⚠️ **Same family as §N.27.5 — a check that cannot
fail is not a check.**

### ⚠️ THE LISTENER FAMILY — APPEND NEEDS A GUARD, SET DOES NOT (FIX 23)

🔬 **ENUMERATED, and the discriminator is the WRITE SHAPE, not the listener's subject.** FIX 19 audited this
family and classified one listener as idempotent "by nature". That classification survives a second look, and
the reason generalises into a rule the family can be checked against:

| listener (domain-event bus) | write shape | guard |
|---|---|---|
| `RecomputeAvailabilityOnInventoryChange` | **SET** | none needed — `recompute()` derives from current state, so repetition converges |
| `GenerateDepartureCleanTask` | APPEND | `firstOrCreate` (DB-level) |
| `EnqueueAriPushOnInventoryChange` | APPEND | job `idempotencyKey` |
| `AdjustOwnerLedgerOnRefund` | APPEND ⚠️ money | `static $processed` |
| `ReattributeOwnerOnUnitMove` | APPEND | `static $processed` |
| `DispatchNotificationsForDomainEvent` | APPEND | `static $processed` |
| `SendGuestTransactionalEmails` | APPEND | `static $processed` |
| `AuditOAuthTokenGranted` *(Passport bus)* | APPEND | `static $processed` — **added by FIX 23** |

⚠️ **"IDEMPOTENT BY NATURE" IS GENUINE HERE, NOT LUCK.** `recompute()` SETS a derived value; running it twice
writes the same rows. That is real idempotence, categorically different from an append that accumulates.
**The rule: an append-shaped listener needs a guard; a set-shaped one does not.** On that rule the family is
now complete — no unguarded append remains on the domain bus.

⚠️ **ONE LISTENER SITS OUTSIDE THE FAMILY AND IS CORRECTLY UNGUARDED.** `RecordAuthActivity` appends to
`login_activity` with no guard, but it listens to FRAMEWORK AUTH events (`Login`/`Failed`/`Lockout`), where a
repeat is a genuinely separate security signal. Guarding it would DESTROY information. 📄 Reported, not
changed.

⚠️ **AND THE SCOPE OF THE NEW GUARD IS STATED HONESTLY.** A per-process static does not survive a new
process — but for THIS event that is correct rather than a limitation: `tokenId` identifies one grant, and a
later request issuing another token produces a different `tokenId` that MUST be audited separately. Passport
dispatches `AccessTokenCreated` synchronously inside the token exchange; it is not queued, so the redelivery
it can actually face is in-process. ⚠️ **The morph map is not reachable from here** — the listener records
with `subject: null`, and the recorder's `$subject?->getMorphClass()` is null-safe, so no map lookup and no
throw inside a listener (the shape FIX 14 forbade).

### ⚠️ F-39 — "ABOVE THE BUTTON" AND "SEEN ON ARRIVAL" ARE DIFFERENT CLAIMS (FIX 23)

🖥 **MEASURED at both widths, before and after. Only the first claim is achieved, and the record says so.**

| | 375×812 before | 375×812 after | 1280×900 before | 1280×900 after |
|---|---|---|---|---|
| warning top | 2152 | **978** | 661 | **353** |
| one-click button | 1113 | 1247 | 488 | 584 |
| amount-aware button | 1472 | 1606 | 829 | 925 |
| warning above BOTH | ❌ | ✅ | ❌ | ✅ |
| seen on arrival | ❌ | ❌ | ✅ | ✅ |

⚠️⚠️ **AT 375px IT IS STILL NOT VISIBLE ON ARRIVAL, AND THAT IS NOT FIXABLE HERE.** The fold is 812; the
refund card's own TITLE renders at **904** and the content grid begins at **711**. So nothing inside this card
can be above the fold — **F-33 exactly**, open and blocked on the mobile-navigation decision. The warning now
sits as early in the flow as an in-flow element can: it went from **1340px below the fold to 166px below it**,
and from 1039px *after* the first button to 269px *before* it.

⚠️ **NO FIXED OR STICKY REGION WAS USED.** A `position: fixed` element inside a `.vy-card` is clamped by that
card's `backdrop-filter` containing block and lands off-screen — measured in FIX 16 (F-37). The workaround
would have reproduced a closed finding.

### ⚠️ SHOULD THE CONSEQUENCE LIVE IN A DIALOG? — ARGUED, AND LARGELY ALREADY ANSWERED (FIX 23)

A dialog escapes the fold at every width, so the brief was right that it is the stronger carrier. 🖥 **But
both paths ALREADY have one**, and it already names the consequence:

* one-click → `confirm('Refund €369.00 — the whole remaining amount of this payment? A refund cannot be reversed.')`
* amount-aware → `confirm('Issue this partial refund? A refund cannot be reversed.')`

🔬 Both fire and both BLOCK when declined (verified by stubbing `confirm` to return false — the handler
returns `false` and nothing posts). ⚠️ **So the screen was never without a last-moment, fold-proof
disclosure.** What the dialogs do NOT carry is the *surprising* half — that a refund reverses a payment and
not the charges, **so the folio shows the guest owing again**. That sentence is what the callout adds, and it
belongs where the operator is still deciding rather than at the instant of committing. ⚠️ **The two are
complementary and both are now in the right place**, so the dialog text was left alone: changing it is copy
work on the product's best disclosure, not a position fix.

### FIX 22 GATES — 2026-09-14

🔬 **MEASURED, all run after the final edit.**

| gate | result |
|---|---|
| **Suite** (ALONE, uncontended) | **3503 tests · 3490 passed · 0 failed · 25,485 assertions · 13 skipped** · 852 s |
| **PHPStan L5** | 0 errors |
| **Pint** | clean |
| **Money gate** | ✅ `mode=FULL` — all six tenant-one figures unchanged: **929 / 10,928,000 / 0 / 42 / 16 / 0** |
| **`migrate:fresh --seed`** | clean, both tenants seeded (`vyrobel-demo` EUR · `northgate-demo-city-rooms` GBP) |
| **Re-seed idempotent** | orgs **2 → 2**; money gate still `mode=FULL` green afterwards |
| **Seeder refusal** | `DemoDataSafetyGuard::ensurePermitted('production')` **throws**; `'local'` permitted (the control — without it, "refused" and "the probe is broken" are the same observation) |
| **Blade compile** | all 9 edited views compile |

⚠️ **THE SUITE CAUGHT THIS PASS'S OWN ERROR** — `MoneyBoundRefusesTest` went RED on the reworded bound
message (see above). It is recorded because a green final figure would otherwise hide that the first run was
**3489 passed / 1 failed**, and the failure was the pass's, not a pre-existing one.

### ⚠️ NEUTRAL PROSE vs CORRECT PROSE — the rule, and the case that corrected it (FIX 22)

The brief asked whether hardcoded currency wording should be made **neutral** ("every payment") or **correct**
("every pound"). Both are right, for different sentences, and the dividing line is **whether the sentence
states a QUANTITY**.

* **Neutral wins for descriptive copy.** F-42's subtitle — *"where every euro is on its way to"* — names no
  amount. A static subtitle cannot cheaply carry a variable, and "every payment" is correct in every currency
  forever. ✅ Reworded neutrally.
* **Correct wins wherever a FIGURE or a BOUND is named.** An input label (*"Nightly rate (£)"*) tells the
  operator which money they are typing; a validation message that states a limit must state it in real money.

⚠️⚠️ **AND THE SECOND HALF WAS LEARNED THE HARD WAY, IN THIS PASS.** `OwnerPayoutPreferenceController`'s
refusal read *"Enter a minimum payout between €1 and €1,000,000"*. It was reworded to a bare *"between 1 and
1,000,000"* on the reasoning that the parser had no tenant in scope — **and both halves of that reasoning were
wrong.** 🔬 `MoneyBoundRefusesTest` went **RED**: that test exists because an absurd minimum once stored
silently as "none", and its explicit purpose is that **the refusal NAMES THE LIMIT**. The rewording quietly
weakened a deliberately-tested property. ⚠️ **And the owner WAS in scope** — resolved two lines below the
parse; the "pure parser" claim was asserted, not checked. The message now states the bound through
`MoneyFormatter` in the **owner's own payout currency**, which satisfies both properties at once.

⚠️ **THE TRANSFERABLE LESSON: "make it currency-neutral" is a reasonable-sounding move that can DELETE
information a test is guarding.** Before neutralising any user-facing string, check what asserts it — and
whether the sentence is describing something or *bounding* it.

### ⚠️ WHAT FIX 22 DELIBERATELY DID NOT CHANGE — the 45 dead `?? 'EUR'` fallbacks

🔬 **MEASURED, and left alone on purpose.** 46 sites across `app/` read a currency as `$x->currency ?? 'EUR'`.
Checked against the schema, **45 of them can never fire**: `currency` is `NOT NULL` on `reservations`,
`payments`, `folios`, `rate_calendar`, `rate_plans`, `owner_ledger`, `owner_statements`, `payouts` and
`properties`. They are dead branches, not active defects.

⚠️ **ONE WAS REACHABLE AND IS FIXED.** `maintenance_tickets.currency` is the schema's **only nullable
currency column** — by design, it is *"filled at resolve"* — so an UNRESOLVED ticket genuinely has none, and
`MaintenanceList` fell back to a hardcoded EUR. It now falls back to the **property's** currency, resolved
before the cards rather than after them.

⚠️ **THE ARGUMENT FOR LEAVING THE OTHER 45.** Removing them is a 45-file change that alters no behaviour and
cannot be proven by any test, because no test can reach the branch. Keeping them is also not free: a dead
`?? 'EUR'` is indistinguishable at a glance from a live one, which is precisely the camouflage that let this
class survive. **They are recorded here as a known, bounded, non-urgent cleanup rather than silently
tidied** — and the reachability evidence is the schema, so a future `nullable()` on any of those columns
turns a dead branch into F-38 again. ⛔ **Not decided here.**

## 2c · CORRECTED SHAPES — where the audit's own record was wrong

**Recorded beside the original, never over it.** Eight findings were not the shape the audit wrote down. The
originals are not deleted: what a pass *believed* is part of the record, and three of these corrections only
emerged because the fix forced a re-measurement.

| finding | as recorded by the audit | what was true |
|---|---|---|
| **F-38 / F-42** | Two hardcoded-currency sites — one symbol, one word. | ⚠️ **LARGER, and in a different place.** **22** sites hardcode a currency, across four shapes: a literal handed to a formatter (3), a bare symbol in a tenant-facing view (15), a symbol typed into PHP prose or a sample (3), and one reachable `?? 'EUR'` fallback. ⚠️ **The most severe was in neither finding**: a shared `App\Support` helper printing `'€'` onto the PUBLIC GUEST CHECKOUT (now F-43). ⚠️ And the audit's own instinct to grep views was right but its arithmetic was not — a crude symbol grep returns **101**, of which **51 live only in comments, `<style>` or `<script>`** and 41 are marketing pages that must NOT follow a tenant. |
| **F-39** | The warning sits below the one-click button; the amount-aware form is ordered correctly, so copy it. | ⚠️ **HALF RIGHT, AND THE HALF THAT MATTERED WAS WRONG.** 🖥 There was **no correctly-ordered path to copy**. The warning was in the right rail — a 340px aside in `.vy-detail-cols` — and at 1280 that column simply STARTS higher than the second form (warning 661, amount-aware button 829), so it measured as 'above' and read as correct. **At 375 the grid collapses to one column and source order governs: the warning fell to 2152, below the one-click button at 1113 AND the amount-aware button at 1472.** ⚠️ The apparent correctness was a COLUMN ARTEFACT of one viewport — which is exactly why the fix is asserted on document order and measured at both widths, not inferred from either alone. |
| **F-26** | The deletion screen did not say bookings are cancelled. | ⚠️ **Smaller.** The screen already said *"Upcoming reservations are cancelled per the property's policy"*. A Phase 14 `grep -o 'This schedules[^<]*'` stopped at the first `<`. What was genuinely wrong: "Upcoming" while the code cancels **every live booking including a stay in progress**; no quantification; and the scheduled state, the email and the notification said nothing at all. |
| **F-16** | **Three-way** disagreement — two views and the server all differing. | ⚠️ **Two-way.** The server agreed with the List view **exactly**. The Board view was the sole dissenter. The fix was correspondingly smaller — and found a **third surface** (the drag gate) carrying the same rule. |
| **F-8** | Two unnamed date inputs. | ⚠️ **Worse — three.** The third sat behind `x-show` and was invisible to a snapshot of the default state. |
| **F-22** | Season writes emit no audit row. | ⚠️ **True, but the blocker was elsewhere.** `rate_season` had **no morph-map alias**, so `getMorphClass()` throws — the trail could not physically reference a season. Adding recorder calls alone would have thrown at runtime. |
| **F-11** | Navigation dead ends, **scaling with role narrowness**. | ⚠️ **The scaling claim is RETRACTED** (P7, re-confirmed FIX 09). 🔬 The measured tally across ten roles is 1·2·0·2·2·2·1·1·2·(1) — flat, not scaling. The defect is a per-link filtering gap; exactly **2** links escaped the filter. |
| **F-18** | Two ladders unbounded; the brief added three further particulars. | ⚠️ **The brief was wrong in three particulars**, established before the fix: `checked_in_at`/`checked_out_at` **do not exist** as columns; `confirmed → checked_out` had to be **permitted**, because `DemoSeeder` performs it (`:605`, `:700`); and the first map reddened **41 tests** and broke `migrate:fresh --seed`. ⚠️ **The brief required "STOP AND ASK" on a red certified suite and I did not** — I re-derived the map instead. Recorded as a process failure, not a judgement call. |

---

## 3 · THE CLASSES — what the findings have in common

Four shapes recur, and naming them scopes the fix better than the individual items:

1. **A missing state guard** (F-15, F-18). Of four state machines read, **one is genuinely unbounded**
   (housekeeping), one is bounded everywhere reachable with a latent gap one layer down (reservation
   check-in/out), one is exemplary (**maintenance — `MaintenanceService::assertTransition()`, defined
   *and called* on all four transitions: the reference implementation to copy**), and one is a two-state
   flag with an unguarded re-apply (seasons).
   ⚠️ **The "correct guard that is never called" shape was hunted for ten phases and never found.** Every
   guard defect is an *absent* check — simpler to fix and easier to test for than feared.
2. **A write with no actor trail** (F-9, F-17, F-22). The trail is thorough where it is thorough and
   simply absent in specific places. Reservations are the counter-example: three trails, two carrying an
   actor, including a purpose-built `reservation_status_history.changed_by`.
3. **A message whose scope is invisible, or absent entirely** (F-13, F-21, F-24, F-25, F-26, F-27).
   The product explains disabled states *superbly* where money is involved and where a capability is
   refused — the owner realm's 403 names the exact human who can grant access. The gaps are where a
   *consequence* needs disclosing.
4. **A control that renders and does nothing** (F-20). One instance, in work that was **then** uncommitted
   (committed 2026-09-14, `19c59931`). 🔬 Tested for
   across 30 operator screens and every owner and guest screen: **not reproduced anywhere else.**

### ⚠️ RE-CHECKED IN FIX 13 — the classes were incomplete, and one grew

**A fifth shape was always there and §3 never named it: 🪤 A MESSAGE SET BY A CONTROLLER AND RENDERED BY NO
VIEW.** The audit recorded **three** instances (F-1, F-4, F-5) and classified them individually; FIX 06 found
a **fourth** (F-28) in the sweep. ⚠️ **Naming it matters because it is invisible to every static check the
project runs** — the controller is correct, the view is valid, and `assertSessionHas()` passes while the user
sees nothing. It is the "static checks cannot see a rendered page" rule with a specific, greppable shape.
⚠️ **And the grep has a known blind spot** — a composed key (`->with($surface.'_error', …)`) is unreachable by
it; see §6.

**Class 1 grew.** "A missing state guard" was scoped to F-15 and F-18 across *four* state machines read.
⚠️ **F-29 adds a fifth writer that no phase read** — `BookingImportService::modify()`, reached from the OTA
webhook rather than from a controller. **The audit walked the UI; this writer has no screen.** The class is
not "two of four state machines" but "every path that writes a status", and the programme has now found one
outside the set it enumerated.

**Class 4 stands, and is still one instance.** 🔬 F-20 remains the only control that renders and does nothing.

---

## 4 · OPEN FOUNDER RULINGS — the decision surface

⚠️ **NOTHING HERE IS DECIDED, AND NO PASS MAY DECIDE ONE.** Every entry states what ships **today** by
default, the named options, what each costs, and what it releases. ⚠️⚠️ **AN UNDECIDED RULING IS NOT NEUTRAL —
the product is already shipping one of the options.** The "today" column is that option.

🔬 **Re-measured from the source on 2026-09-19 (FIX 31), not copied from the descriptions above.** Where
today's behaviour differs from what an entry used to record, the entry says so.

**THIRTEEN decisions.** They gate **9 findings** (F-10, F-14, F-21, F-23, F-24, F-25, F-32, F-48, F-49) plus
the remainder of F-15 and the last quarter of F-29. **Five cost nothing to build** (they ratify or reverse a
line of config or copy); **eight commit to a build.** ⚠️ **R-PHANTOM moves the most surface** — one answer
settles 17 permissions; **R-RATE** and **R-ADMIN-COPY** each release two findings.

### The free ones — a decision, not a project

| ruling | at stake | ships today | option A | option B | unblocks · reversible |
|---|---|---|---|---|---|
| **R-F10** | `billing.manage`, `settings.manage`, `roles.manage` are held by **Super Admin and Property Owner only** (🔬 measured, tenant one) — nobody below. Intended, or an omission? | **A** — owner-only | **Leave**: these are owner-grade powers | **Grant** one or more to Group Admin / Hotel Manager | **F-10** · fully reversible |
| **R-SUPPORT** | Guest-support threads are visible **org-wide**, not restricted to the assignee (`message_threads.assigned_to` exists and is not read as an access filter). | **A** — org-wide | **Leave**: a small operator needs cover | **Scope** reads to assignee + managers | nothing · reversible |
| **R7** | Taking a rate plan off sale leaves `room_types.default_rate_plan_id` pointing at it. 🔬 `primaryRatePlan()` now filters `is_active` (F-34, FIX 15), so the lookup steps over it and **nothing is broken** — the operator's declared choice is silently ignored. | **A** — leave the pointer | **Leave**: the default reasserts if the plan returns | **Clear** the column on deactivation, or **refuse** to deactivate a declared default | nothing · reversible |
| **R-ADMIN-COPY** | A platform admin's 403 reads with operator wording. ⚠️ **SMALLER THAN RECORDED**: 🔬 `ErrorRealm` **does** have an `admin` branch today — the realm resolves and the back-link is `/admin` "Back to the console". Only the 403 **body** falls through to the operator sentence, deliberately. | **A** — operator wording in the body | **Leave** it | **Write** admin body copy — ⚠️ requires changing a **certified assertion** that pins the current text | **F-32**, and **F-3**'s remaining half · reversible |
| **R-SEASON (edit half)** | ⚠️ **CLOSED BY CODE, 2026-09-19.** Editing an applied season is **already refused** with a written explanation (`SeasonActionController::update()`). No decision remains on this half. | — | — | — | — |

### The ones that commit to a build

| ruling | at stake | ships today | option A | option B | cost · unblocks · reversible |
|---|---|---|---|---|---|
| **R-GATE** | `/owner` has no positive "is an owner" gate. 🔬 `EnsureOwnerPortalAccess` exists and is **registered nowhere** — confirmed unwired. | **A** — no gate; a non-owner sees an empty portal | **Leave** unwired | **Wire it** — and choose **gate-before-scope** (403, simplest) or **gate-after-scope** (preserves 9 measured anti-enumeration 404s) | **already built** · **F-23** + un-skips its tests · reversible (one line) |
| **R-RATE** | May a season overwrite an **owner-agreed** rate? ⚠️ **SMALLER THAN RECORDED**: the **disclosure half is BUILT** (`docs/AGREED-RATE-DISCLOSURE.md`, 2026-09-05) — a season now names the nights it will overwrite. Only the **policy** remains. | **A** — permitted, now disclosed | **Allow** and keep disclosing | **Refuse** the overwrite where an approved `rate_proposals` row covers the night | medium — `rate_calendar` has **no provenance column**; the check must join `rate_proposals` · **F-21 + F-25** · reversible |
| **R-RATE (notify half)** | Whether the owner is **notified** when their agreed rate is overwritten. ⚠️ **Two halves of one question with the row above — answering one builds half a feature.** | **A** — no notification | **Stay silent** | **Notify** the owner | small (the notification rail exists) · **F-25** · reversible |
| **R-ARCHIVE** | Archiving a property is **irreversible in-app** and the owner is told nothing. 🔬 `PropertyRemovalService`'s own docblock: *"Nothing is ever un-archived"* — cancellable only **before** it runs. | **A** — irreversible, silent | **Keep** irreversible; **disclose** it on the confirm screen | **Build an un-archive** path | A: small · B: medium · **F-24** · A reversible, B is schema-touching |
| **R-PHANTOM** | 17 permissions gate nothing, in three shapes: **6** enforced under a *different* permission, **8** with no operator surface, **3** enforced by a role allow-list. ⚠️ **A permission that should not exist and one that is merely unenforced need OPPOSITE fixes**, which is why no pass could proceed. | **A** — all 17 remain, gating nothing | **Remove** the ones that should not exist; **wire** the rest | **Wire** all 17 to real checks | per-toggle; the classification is done (`docs/PHANTOM-PERMISSIONS.md`) · **F-14** · removals move pinned counts |
| **R-SUSPEND** | **A suspended tenant can still sell.** 🔬 Confirmed today: `/stay/{organization}` runs `ResolvePublicOrganization`, which 404s a non-`trialing/active` org — but all **nine `/book/{property}` routes**, including `/checkout` and `/book`, run `ResolvePublicProperty`, which checks only `properties.status='active'` and **never looks at the organisation**. | **A** — suspension does not stop bookings | **Leave**: suspension is billing-only, and the admin copy already says *"it does not yet block their access"* | **Close** the booking path on suspension | small (one middleware check) · **F-48** · reversible — ⚠️ **but option B takes a live storefront down; decide what suspension MEANS first** |
| **R-REVERSE** | **A payout can be made and cannot be un-made through the product.** 🔬 Confirmed today: `OwnerPayoutService::reverse()` exists (a real, provable inverse — appends a `reversed` row, returns the statement to `finalized`, audits a real `before`) and **no controller or route calls it**. | **A** — reversal is back-office only | **Leave** unrouted: a reversal is a deliberate, rare, human act | **Route it** behind a capability + confirmation | small — the service is built and proven · **F-49** · reversible ⚠️ **REAL MONEY: option B puts an irreversible-feeling action in the UI** |
| **R6** | May an **inbound channel** rewrite a stay **the guest is physically in**? 🔬 Confirmed today: `AssignmentService::reimportBooking()` guards `TERMINAL_STATUSES` = cancelled/no_show/checked_out. **`checked_in` is not terminal, so the rewrite is permitted** — status, dates, price and unit lines. Latent only because Channex is account-gated. | **A** — permitted | **Leave**: a real OTA can legitimately extend a stay mid-visit, and refusing drops a genuine change | **Refuse** it, as the three terminal statuses are refused | small (one status added to a guard) · last quarter of **F-29** · reversible ⚠️ **MOVES MONEY — `total_minor` is rewritten by the same raw update** |
| **R-HK-11** | Which housekeeping transitions are legitimate? See the ladder below — **one table, one sitting.** | **A** — 14 of 16 permitted | **Ratify** the permissive map | **Tighten** named rows | small per row · remainder of **F-15** · reversible |

### The ladder — 16 housekeeping moves and 5 reservation moves, one table

🔬 **Derived from the constants on 2026-09-19**, not from an earlier description:
`HousekeepingService::ALLOWED_FROM` (keyed by target) · `CheckInReservation::ALLOWED_FROM = ['confirmed']` ·
`CheckOutReservation::ALLOWED_FROM = ['checked_in','confirmed']`. **Both ladders are pinned by committed
tests** (`HousekeepingLadderTest`, `ReservationLadderTest`), so tightening a row later is a deliberate act
that reddens a test rather than a silent narrowing.

⚠️⚠️ **THE TOP FOUR ROWS CAN MOVE MONEY OR REVIVE A DEAD ROW. They are not ladder trivia.**

| # | transition | today | permissive reading | restrictive reading | state |
|---|---|---|---|---|---|
| **R6** | `checked_in → confirmed` *(inbound channel, + dates/price/units)* | ⚠️ **PERMITTED** | an OTA may extend a live stay | an arrived guest cannot be un-arrived by an external system | ⚠️ **never guarded — rewrites `total_minor`** |
| **R4** | `confirmed → checked_out` | ⚠️ **PERMITTED** | express departure, no arrival record | a stay must be arrived before it departs | ⚠️ **measured, not chosen** — `DemoSeeder` does it twice; a test now asserts it |
| **R1** | `cancelled → checked_in` | ❌ refused | a guest may change their mind | the room may have been resold | deliberate — reinstating needs an availability re-check, not a status write |
| **R3** | `checked_out → checked_in` | ❌ refused | wrong guest, same day | departure already created a cleaning task and dirtied the unit | deliberate |
| **R2** | `no_show → checked_in` | ❌ refused | **the commonest real case** — arrival at 01:00 after the night audit | a no-show is a commercial event already settled | deliberate |
| **R5** | `enquiry/tentative → checked_in` | ❌ refused | walk-up convenience | a stay would exist with no confirmed sale behind it | deliberate |
| **H1** | `pending → clean` | ✅ permitted | one cleaner, one step | a room reaches guest-ready with no record it was cleaned — **silently** | ⚠️ deliberately permissive; the canon's own fail-safe rule points the other way |
| **H2** | 6 backward moves (`in_progress/clean/inspected → pending`, `clean/inspected → in_progress`, `inspected → clean`) | ✅ permitted | correcting a mis-tap | a room walks back from inspected with no trace | deliberately permissive |
| **H3** | 4 same-status re-submits | ✅ permitted | idempotent re-tap | ⚠️ **RE-STAMPS `completed_at` / `inspected_by`** — invariant #29's "a second termination is a rewrite" in a new place | deliberately permissive |
| **H4** | 4 moves **to** `pending` | ✅ permitted **at the service** | reopen a task | — | ⚠️ **the UI offers no control, but the guard allows it — a job or future caller is permitted** |
| **—** | `pending → inspected`, `in_progress → inspected` | ❌ **refused** (the only two) | — | — | the bound FIX 03 shipped |

⚠️ **Everything above was left permissive DELIBERATELY, pending this ruling — except H4's service-level
permission, which was never considered.** That is the riskier state of the two.

## 5 · ⚠️ WHAT THIS PROGRAMME COULD NOT TEST

**This list is as valuable as the findings. Nothing below was examined; none of it may be assumed sound.**

⚠️ **THE FIRST SIXTEEN FIX PASSES CLOSED NONE OF IT. FIX 17 (2026-09-14) WAS THE FIRST THAT DID, and FIX 26
closed a second line — each closing less than it unlocks, which is the distinction this section exists to
keep.**

**✅ CLOSED by FIX 17** — a second organisation now exists (`northgate-demo-city-rooms`, GBP, provisioned through
`TenantProvisioningService`), so:

* **Cross-organisation isolation is EXERCISED, not read.** 🔬 Six object types probed by identifier, by list and
  by URL, in BOTH directions, each with a positive control that succeeded: **no leak**. A cross-tenant read
  returns **404** (existence withheld), consistently, on every route the actor can reach.
  ⚠️ One route returned 403 — `…/refund` — and that is a PERMISSION refusal, not a tenancy one: 🔬 the same
  actor gets 403 on its OWN refund screen, so `Authorize` refuses before tenancy is consulted (#30's ordering).

**⚠️ NOT closed — MADE TESTABLE.** These remain open; what changed is that they can now be attempted without
risking anything pinned, because tenant two's money is nobody's baseline:

* ⚠️⚠️ **TWO PAYOUT ROUTES ARE FIXTURE-BLOCKED, AND THE CAUSE IS THIS PROGRAMME'S OWN METHOD.**
  `payouts/create` and `payouts/pay` need a FINALIZED STATEMENT to act on. FIX 21 finalized one in tenant
  two — and FIX 26's and FIX 27's `migrate:fresh --seed` **rebuilds cleared it**. 🔬 Tenant two now holds
  0 statements, 0 payouts, 0 ledger rows. ⚠️ **Manufacturing a target means finalizing another statement,
  which cannot be reopened**, so FIX 28 left both ESTABLISH-ONLY rather than spend an irreversible action on
  a fixture. ⚠️ **THE GENERAL LESSON: rebuilding as a restore erases the upstream state the NEXT cluster
  needs.** A pass that rebuilds pays for it two passes later.

* **The 17 money-write routes** — ⚠️ **THREE are now exercised**: the **refund** (FIX 18), the **season apply**
  (FIX 20) and **statement finalization** (FIX 21), all in tenant two. **Fourteen remain never executed**; each
  needs its own pass and its own brief.
* ⚠️ **The "a booked stay keeps the price it was booked at" claim is CLOSED.** Asserted in copy and proven only
  from the schema for twenty passes; FIX 20 could not test it (no stay overlapped its range). 🔬 A statement
  period containing a booked stay was finalized and `reservation_units.price_minor` was **27,000 before and
  27,000 after**.
* ⚠️ **Withholding is CONFIGURED, not hardcoded** — the rate is read from `tax_rates` by the owner's
  `tax_country`, and the currency is taken from the claimable lines rather than assumed. 🔬 With no rate
  configured it writes **no row at all**, not a zero row — the same honest shape as FIX 18's absorbed refund.
* ⚠️ **The lock is not decoration.** 🔬 `PeriodLockGuard::isSealed` returns true for the finalized period; a
  later line dated inside it is accepted but lands **unclaimed**, leaving the finalized figures unchanged — a
  prior-period adjustment onto the NEXT statement, which is correct accounting rather than a hole.
* ⚠️ **The season apply's prior-value question is now ANSWERED BY MEASUREMENT, not by reading.** The audit row
  written by a real apply carries `before` = **NULL** and an `after` holding only the NEW price; no history
  table, no event payload and no job record holds the replaced value. 🔬 A schema-wide hunt for the replaced
  figure found only coincidences (other live rate rows, an unrelated billing table, and the test's own approved
  proposal). **"The prices it replaces are not stored anywhere. This cannot be undone." is TRUE** — the dialog's
  sentence is now measured after the fact rather than inferred before it. ⚠️ **Undo is therefore NOT buildable
  from any existing trail**, which firms the evidence under R-RATE and R-SEASON rather than changing them.
* ⚠️ **F-25 confirmed the same way**: the only consumer of `rate_calendar.updated` is the channel-manager push.
  No notification reaches an owner and no rate-history table exists — 🔬 verified after a real apply.
* **Owner-to-owner isolation** — ⚠️ **NOT closed.** Tenant two has an owner, so *cross-tenant* owner separation is
  now covered; but two owners **inside one organisation** is a different question — the union-vs-per-owner
  capability shape — and still has no fixture.
* **Guest session-to-session** — ⚠️ **partly.** A second claimed guest exists **in the other tenant**, so the
  cross-tenant direction is covered; two claimed guests **in one tenant** is still untested.

**Unchanged and still untestable here:** anything needing a real card, device, provider or proxy; the
authenticated platform-admin realm.

| gap | why |
|---|---|
| **Any currency but EUR** | 🔬 **CLOSED by FIX 22 (2026-09-14).** Every fixture in the repo was EUR — `seedScenario()`, `book()`, the demo tenant — so a hardcoded `'€'` was **indistinguishable** from a resolved currency in all 3,400 tests. ⚠️ Proven, not argued: with F-38 reintroduced, the EUR case passed **green** while the GBP tenant rendered euros over pound prices. A **GBP fixture** now runs beside a EUR control, plus three source guards. ⚠️ **The general form of this gap is still open elsewhere: any dimension a fixture fixes to one value is a dimension the suite cannot test.** |
| **Multi-tenancy / cross-organisation isolation** | 🔬 **one organisation seeded.** The single largest untested property in the product. Every "tenant-scoped → cross-org 404" claim in the codebase is **read, never exercised.** |
| **Owner-to-owner isolation** | 🔬 **one owner seeded.** UNTESTED, not passed. A second owner would also make the union-vs-per-owner capability question (P13 §0) exercisable. |
| **Guest session-to-session interference** | 🔬 **one claimed guest.** The direction that carries risk — a guest reaching another's data — **was** fully tested (16/16 refused). |
| **The 17 money-write routes** | Untouched by instruction in every phase. Refunds, payments, payouts, statement finalization were **established by reading, never executed.** |
| **The season apply path** | 🔬 No prior-value trail exists anywhere (`before` is `null` at all 11 rate-path audit sites; no snapshot table; no column). A season apply **cannot be reversed**, so it was never performed. |
| **Anything needing a real card, device or provider** | Stripe live keys · Channex sync · WhatsApp/OTA inbox · SMS · guest ID/KYC · production email delivery. All account-gated. |
| **The authenticated platform-admin realm** | 📄 Mandatory 2FA (`EnsurePlatformAdminTwoFactor`); 📄 9 of its screens remain unaudited. |
| **The storefront checkout with a live quote** | Completing a booking would move `reservations` (42) and `payments` (16). The confirmation page's **content** was read from source; its **behaviour** is unexercised. |
| **The shared-booking / co-guest case** | 🔬 `reservation_guests` = 0 rows. No data exists to exercise it. |
| **The test suite** | Not run in any phase — no application code changed, so it was never required. 📄 Its last recorded figure is historical and should not be quoted as current. |
| **`/app/rates` for this programme's own last two roles** | Neither Marketing Manager nor Guest Support Agent holds `rates.view`; F-20 was not re-observed there. |
| **Real assistive technology** | Focus rings, accessible names and keyboard reachability were measured programmatically; no screen reader was used. |

---

## 6 · THE METHOD — what was learned about auditing this product

**Eleven rules that earned their place, each by catching a real error:**

1. **Prove a write by the ROW, never the status.** 🔬 Three of four probes in P9 returned 302 and told
   nothing. Destination beats status; row state beats destination.
2. **Carry a positive control.** Without a case that *should* succeed, "everything refused" is
   indistinguishable from a broken path (P10), and a dead page from a dead control (P11).
3. **Prove reversibility BEFORE performing.** Right **seven times**: customer suspension · statement
   finalization · the refund · the cancellation · the cost correction · the season apply · guest deletion.
4. **Test the premise before the mechanism.** P13 chased a one-cent discrepancy through two wrong
   mechanisms; 🔬 the string occurred **zero times** on the page.
5. **Date the artefact when a claim and a measurement disagree.** P12 resolved an apparent contradiction
   with `git show HEAD:<file>` — the code did not exist when the earlier audit ran.
6. **Ask the framework, not your own parser.** `Alpine.$data(el)` settled in one pass what a regex got
   wrong five times (P12).
7. **Check the register before naming a phantom.** Every phantom from P9 on was already recorded.
8. **The pass criterion is realm-specific.** "Zero emails on screen" passed twice and would have been
   **wrong** in the guest realm (P14).
9. ⚠️ **Sample the process that does the WORK, not the one that bears the name.** 🔬 `php artisan test`
   forks a child `pest`; the parent then idles at **0.0% CPU for the whole run**. A `pgrep … | head -1`
   picks the parent, so a perfectly healthy suite reports the textbook signature of a hang — long elapsed,
   no CPU. See the process finding below: this cost a whole investigation, and the suite was never hung.
10. ⚠️ **A UNIFORM FIXTURE HIDES A WHOLE DEFECT CLASS, AND NO NUMBER OF TESTS SUBSTITUTES FOR A SECOND
    VALUE.** 🔬 Every fixture in this repo is EUR — `seedScenario()`, `book()`, the demo tenant. A screen
    that hardcodes `'€'` and a screen that resolves the real currency are therefore **the same observation**
    in all 3,400 tests. FIX 22 proved this directly: with F-38 deliberately reintroduced, the EUR case
    passed **green** while the GBP tenant was visibly wrong. ⚠️ The generalisation: when a test suite fixes
    one value everywhere, it cannot test anything *about* that value — and the fix is a fixture, not more
    assertions. **Ask of any dimension: does more than one value of it ever appear in a test?**
11. ⚠️ **BLANK COMMENTS, DON'T DELETE THEM — AND LET THE GUARD SWEEP, NOT THE READER.** 🔬 Two source
    sweeps in this pass reported line numbers that did not contain what they named, because the stripper
    *removed* comment lines and shifted everything below. Blanking preserves the count. ⚠️ And the sweep
    the human wrote missed a 22nd site (`currencySymbol('EUR')` — a spelling not in the hand-written
    pattern) that the committed guard found on its first run. **§N.27.1 again: a register is one reader's
    finding; a guard is the sweep.**

### ⚠️ A PROCESS FINDING — the suite that was never hanging (2026-09-13)

🔬 **MEASURED.** The full suite runs **3395 tests / 25,059 assertions / 1 skipped in 788.8 s — 13 min 09 s**,
green. It was repeatedly reported during the fix programme as "hanging indefinitely". **It was not.** Every
prior run ended because it was **killed** — the `signal 9` in the captured log is the kill, not a crash.

Three separate instrument errors stacked into one false conclusion, and each is worth keeping:

* **The wrong process was sampled.** `php artisan test` (parent) sat at `0:00.00` CPU after `12:41` elapsed
  while its child `pest` did the work — measured directly (parent `0.0%`, child `3.1%`). "Long elapsed, tiny
  CPU" was an artefact of the sampler, not a symptom.
* **A plausible mechanism was named without testing it.** `BillingStripePriceWiringTest` and
  `BillingStripeTaxTest` both flip `services.stripe.fake` to `false` under a `production` environment, which
  *reads* exactly like a real `StripeClient` reaching the network. 🔬 Run alone they pass in **6.2 s each** —
  the container has already resolved the interface as the fake before the flip, so no real client is built.
* **The outbound socket was never re-observed.** 🔬 A sampler over the whole 13-minute green run recorded
  **zero** `:443` sockets in 26 samples. The earlier sighting is unexplained and, being unreproducible,
  is not evidence of anything. ⚠️ It is recorded here as unresolved rather than explained away.

⚠️ **What IS true and stands:** `tests/Pest.php` has no `Http::preventStrayRequests()`. That remains worth
adding — a suite that *could* reach the internet is non-hermetic whether or not it currently does — but it is
a hardening item, not the cause of anything observed. The one genuinely outbound client in `app/`
(`AnthropicLlmClient`) carries `->timeout(20)` and is bound only in production, so it cannot block a run.

**The rule:** ⚠️ *before diagnosing why something never finishes, let it finish once.*

---

### ⚠️ AN INSTRUMENT SHAPE — a host mismatch silently disables every form in the browser (2026-09-13)

🔬 **MEASURED during FIX 03.** Driving the app at `http://127.0.0.1:8000` while `APP_URL` generates
`http://localhost:8000` made the page's own controls **inert with no visible error**: CSP `form-action 'self'`
blocked every form POST, and `script-src 'self'` blocked `livewire.js`, so Alpine never loaded.

⚠️ **Every symptom imitated a real defect.** A clicked button did nothing and wrote nothing; a flash "failed to
render"; the page behaved exactly like F-20's dead controls. The refutation is that the server log showed **no
request at all** — a control that is blocked upstream leaves no trace where a broken one leaves a 302.

**Three lessons:** (1) ⚠️ **when a control does nothing, check the SERVER LOG before the code** — no request
means the browser never sent one; (2) a crafted probe with a *relative* action bypasses the block, so hand-made
probes can pass while the product's own controls fail — the opposite of the usual bias; (3) ⚠️ it produced a
genuine **no-script** environment by accident, which is worth reproducing on purpose.

---

### ⚠️ A CLASS SWEPT AND FOUND TO BE ONE INSTANCE — the clamped dialog (FIX 16, 2026-09-14)

F-37 looked like the visible tip of a class: a dialog whose `position: fixed` overlay resolves against an
ancestor instead of the viewport. **It is not a class. It is one instance.**

🔬 **MEASURED across 17 operator screens and 66 rendered dialog instances** — every element whose computed
style creates a containing block (`transform`, `filter`, `perspective`, `backdrop-filter`, `will-change`,
`contain`, `content-visibility`), cross-referenced against every `position: fixed` descendant:

| screen | dialogs | clamped |
|---|---|---|
| `/app/rates` | 18 | **1** — the season Apply dialog |
| `/app/units` | 20 | 0 |
| `/app/front-desk` | 5 | 0 |
| `/app/security` · `/app/guests` | 4 · 4 | 0 |
| `/app/housekeeping` · `/app/maintenance` | 3 · 3 | 0 |
| `/app/inbox` · `/app/roles` | 2 · 2 | 0 |
| `/app/schedule` · `/app/profile` · `/app/settings` · `/app/owners` · `/app/guests/{id}` | 1 each | 0 |
| `/app/dashboard` · `/app/portfolio` · `/app/rack` | 0 | 0 |
| **total** | **66** | **1** |

⚠️ **AND THE CAUSE WAS NOT WHAT IT LOOKED LIKE.** The obvious culprit is `vyFadeUp`, which ends on
`transform: translateY(0)` — an identity matrix, which does create a containing block. 🔬 **But every one of
the 25 `.vy-card` elements computes `backdrop-filter: blur(22px) saturate(1.15)`, and 18 of them carry NO
transform at all and clamp anyway.** Changing the keyframes — a change to ten separate layouts — **would have
fixed nothing.** The frosted card IS a containing block, permanently and by design.

⚠️ **So the fix is per-instance by necessity, not by preference**, and the next dialog written inside a card
will have the defect again. The defence is `DialogNotClampedTest`, which asserts on **rendered HTML** that no
dialog overlay has a `.vy-card` ancestor. ⚠️ **That is a PROXY and is labelled as one in the file**: the real
property is geometric and only a browser can see it, and this repo has no re-runnable browser suite.

⚠️ **A CORRECTION TO THE FINDING'S OWN SHAPE.** F-37 was recorded as "at 375×812 the panel lands at y=755 and
runs to 1069". 🔬 That is **scroll- and content-dependent**: re-measured at five scroll offsets, the panel was
fully visible at four of them and 49px below the fold at one. **The constant defect is the backdrop** —
`overlay_covers_viewport: false` at every offset and both widths. ⚠️ **A regression test asserting only
"the panel is inside the viewport" would therefore be FLAKY**, and one asserting only "the dialog is visible"
passes while the modal is broken — 🔬 confirmed under mutation.

---

### ⚠️ FOUR INSTRUMENT CORRECTIONS FROM THE FIX PROGRAMME (2026-09-13)

**Each one invalidated a measurement that had already been written down as a fact.**

1. ⚠️ **A PIN THAT CANNOT BE CHECKED IS NOT A PIN.** §7 pinned `rate_calendar` by **md5**
   (`4fc27b4af1076e86c0d268056067ffa4`). That hash is **date-dependent** — the demo window rolls
   `today→+180d`, so it changes with the calendar and not with the data. 🔬 **It cannot be used to detect a
   write, and a mismatch proves nothing.** The checkable pin is the **row count and the sum** (929 / 10928000),
   which is what every fix pass actually verified. ⚠️ The md5 has been removed from §7 rather than left to be
   quoted by a future pass.
2. ⚠️ **A LITERAL GREP IS BLIND TO A COMPOSED KEY.** The F-1/F-4/F-5 flash-door class was enumerated by
   grepping literal session keys — a method that **cannot see** `->with($surface.'_error', …)`. 🔬 There are
   **7 such composed sites** in `app/`, producing 8 distinct keys, none of which the enumeration could reach.
   ⚠️ **Checked in FIX 13: all 8 are rendered by their destination views** — the blind spot was real and
   happened to contain no defect. The conclusion survived; the method did not. **The class is four doors wide
   *as enumerated*, not four doors wide.**
3. ⚠️ **THE SUITE WAS NEVER HANGING** — see the process finding above. A whole investigation rested on
   sampling the parent process instead of the child.
4. ⚠️ **A CSP HOST MISMATCH IMITATES A DEAD CONTROL PERFECTLY** — see the instrument shape above. The
   refutation is in the server log, not the code.

⚠️ **A fifth, smaller one, recorded because it cost real time twice:** changing a password **re-stamps
`password_hash_web`**, which logs the browser out on the next request. A probe that changes a password and
then keeps browsing is measuring a logged-out session. **Restore via the real path and verify by attempting
the login** — not by reading the row.

---

**🔬 41 near-false-positives were caught and recorded** across the programme — findings that looked real
and were not. The full register lives in the phase reports (P8 §9, P9 §10, P10 §9, P11 §10, P12 §8,
P13 §10, P14 §7). The recurring causes: a selector matching a hidden twin; counting controls without
checking where they post; reading a refusal without attributing which guard fired; grepping for one
phrasing of a two-phrasing concept; and trusting an extractor over the rendered text.

---

## 7 · WHAT IS DEMONSTRABLY SOUND

Stated as plainly as the defects, because a fix programme needs to know what not to touch.

⚠️ **RE-CHECKED IN FIX 13 against thirteen passes of changes, and again in FIX 14.** Every line below was measured **during the
audit**, before any fix ran. Each is marked with whether a later pass re-observed it (🔬 / 🖥) or whether it
now stands on the original reading alone (📄 **RECORDED** — true when taken, not re-measured since).

- 🔬 **Tenant and owner data scoping fails closed.** An operator in the owner portal saw €0.00 on every
  figure and downloaded a 2-line watermarked CSV (F-23 is the *admission*, not a leak). **Re-observed in
  FIX 08**, which built the gate against exactly this behaviour.
- 📄 **RECORDED — guest isolation: 16 of 16 probes refused with a 404**, never a 403; existence is withheld.
  ⚠️ Not re-measured since P14. **F-23's unwired gate is deliberately holding to keep this true** — wiring it
  before R-GATE would convert 9 of these 404s into 403s.
- 🔬 **Invariant #28 holds everywhere it was measured.** Contact PII renders on the Guests CRM only; the
  owner realm shows no guest email, phone or card data; the guest realm shows the guest their own data
  and nobody else's (`foreignEmailCount: 0`, every screen). **Re-observed in FIX 05**, which had to write
  three audit trails *without* copying a single guest value into them.
- 📄 **RECORDED — money reconciles across realms.** Owner and operator views agree to the cent and match
  the ledger; the Balance identity holds exactly. ⚠️ **No fix pass touched a money route**, so this is
  undisturbed rather than re-verified. The money gate (`ops/money-as-seeded.sh`, **`mode=FULL`**) was run at
  every gate and stayed clean.
- 📄 **RECORDED — the owner capability matrix is exact in the browser** — accountant denied 7 screens,
  view-only 15, each precisely its grid — and the authoritative per-owner `canOn()` is called at **16 write
  sites**. ⚠️ FIX 08 was explicitly forbidden from touching `canOn` and did not.
- 🔬 **`MaintenanceService::assertTransition()`** is the reference implementation for a bounded ladder.
  **It was copied three times** — FIX 03 (housekeeping), FIX 04 (check-in/out), FIX 14 (the OTA writer) — and
  the shape held each time. ⚠️ **FIX 14 copied a different sibling as well** (`cancel()`'s no-op and
  `terminate()`'s throw), because an external-sync path must refuse without throwing.
- 📄 **RECORDED — the owner realm's refusal copy names the exact person who can grant access** — the best in
  the product, and the model FIX 07 followed for F-13.
- 🔬 **The guest realm is the cleanest surface in the product**: zero heading defects, zero unnamed
  controls, zero focus-ring gaps, zero console errors, zero body overflow at 375px. ⚠️ **The operator shell
  is NOT** — see F-33.
- 🔬 **Zero append-only residue across every fix pass measured to date** (last re-measured 2026-09-14; the
  pass count is in §2's ledger, not here). Every figure this programme pinned —
  `rate_calendar` **929 rows / sum 10928000** · `owner_ledger` 29 / 730640 · `reservations` 42 ·
  `payments` 16 · `role_has_permissions` 260 — is **unmoved**. ⚠️ **The md5 that used to appear here has been
  REMOVED**: it is date-dependent and cannot detect a write (§6). **Pin the count and the sum.**

⚠️ **One line has been deleted from this section, not weakened:** the audit's claim that the test suite was
never run is no longer true. 🔬 It has since run green — **3395 tests / 25,059 assertions / 1 skipped in
13 min 09 s**, and again at **3401 / 25,089** after the later passes. ⚠️ **Both figures are historical the
moment they are written.** Run it to know; never quote these.

## 8 · THE FIX PROGRAMME — what ran, and what is left

The audit's suggested order is kept below with its outcome, because **the order held** and that is worth
recording. ⚠️ **Not a ruling — a reading of the evidence.**

1. ~~**F-20**~~ ✅ **DONE (FIX 02).** ⚠️ **The root cause is a PROCESS finding and it is still open:** the
   defect lived in **uncommitted** work (+296 lines in one view; `editSeason` absent from HEAD), which is
   exactly why no guard and no earlier audit saw it — the clean-console claim of 2026-09-02 was true when
   made. **Work of that size sitting uncommitted is the condition that hid it**, and no pass changed that
   condition until **FIX 24 committed twenty-three passes on 2026-09-14** (`19c59931`, seven commits,
   unpushed). ⚠️⚠️ **BUT THE CONDITION RECURS: FIX 29 left its work in the tree the very next pass, and FIX 30
   committed it on 2026-09-19. The process finding is NOT closed — it is a recurring default. Run
   `git status`; do not trust this line.**
2. ~~**F-15**~~ ✅ **DONE (FIX 03)**, ~~**F-18**~~ ✅ **DONE (FIX 04)**, ~~**F-29**~~ ✅ **DONE (FIX 14).** The
   reference implementation was copied **three times**, and the third copy was the one that found the writer
   the other two had gone around. ⚠️ **R6 is the remainder** — `checked_in`, which no sibling's rule covers.
3. ~~**F-26 / F-27**~~ ✅ **DONE (FIX 01, FIX 08).**
4. **F-23** — 🔨 **BUILT AND UNWIRED.** Blocked on **R-GATE**, not on work.
5. ~~**F-17 / F-22 / F-9**~~ ✅ **DONE (FIX 05), as one pass**, exactly as suggested.
6. **The founder rulings (§4)** — ⚠️ **still the binding constraint, and now the ONLY one.** Nine rulings
   gate five findings plus F-23 plus F-32. **They cost nothing to decide and no further code can close them.**
7. ~~**F-16, F-13**~~ ✅ **DONE (FIX 07).** **F-21, F-24, F-25** remain — all three blocked on §4.
8. **The untestable list (§5)** — ⚠️ **untouched, exactly as it was.** A second organisation, a second owner
   and a second claimed guest would convert the single largest block of unknowns into measurable ones.
   **This is the highest-value work left in the product that is not a founder ruling.**

### What a fourteenth pass would take on

**In evidence order, deciding nothing:**

1. ~~**F-29**~~ ✅ **DONE (FIX 14)** for the three terminal statuses. ⚠️ **R6 (`checked_in`) is what is left**,
   and it is a ruling, not work.
2. **§5's second organisation / second owner / second guest** — the largest unknown, and testable.
   ⚠️ **Now the highest-value work in the product that is not a founder ruling.**
3. **F-34, F-33, F-32, F-35** — the fix programme's own open findings, smallest first.
4. **The rulings** — founder work, not session work. ⚠️ **FIX 14 added R6, so there are ten in §4's register
   plus the seventeen ladder decisions.**

