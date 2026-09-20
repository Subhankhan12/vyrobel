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

⚠️⚠️ **AND WHEN YOU DO RE-DERIVE IT AND FIND NO OPEN ROWS — READ THIS BEFORE CONCLUDING ANYTHING.**

**THE OPEN COUNT IS A FUNCTION OF HOW HARD SOMEONE HAS LOOKED.** ⚠️ This register reached zero open findings
once before, on 2026-09-19, and **it was an artefact**: six findings were sitting in a verification document
rather than in the register, and entering them took it straight back off zero. ⚠️⚠️ **Every instrument this
programme has built has then found things a careful human sweep had missed** — a currency detector took nine
recorded sites to **twelve**; a register guard took a reported *"four contradictions"* to **five surfaces
across three findings, plus three more carrying no id at all**; a soft-pin sweep found **four figures called
"pinned" that were asserted by nothing**; a listener audit found a gate that was **true by construction for
every booking it could see**.

⚠️⚠️ **AND FIX 57 IS ITSELF ANOTHER CASE.** The remedy for F-62 was very nearly built over the three money
tables the gate already read — which would have closed a class **that does not contain its own motivating
example**, because the failure it exists to catch (a credit moved from one owner to another) lives in
`owner_ledger`, a table **no version of the gate had ever read**. ⚠️ Only measuring *which tables the gate
actually reads* revealed it.

⚠️ **SO A LOW OPEN COUNT IS EVIDENCE ABOUT THE INSTRUMENTS, NOT ABOUT THE PRODUCT — and here is what remains
unmeasured, in the same breath, every time:**

- ⚠️ **FIVE money tables no instrument reads at all** — `folios`, `folio_items`, `owner_statements`,
  `owner_expenses`, `subscriptions` — and every tenant but the first.
- ⚠️ **§5's untestable list.** ⚠️ Its two payout routes moved from **STRUCTURALLY blocked** to
  **FIXTURE-blocked** when the third tenant arrived: driving them is now possible within one pass, at the cost
  of manufacturing owner fixtures. That is a real change and a founder's call, not a closure.
- ⚠️ **The CONTAINED pair is still in the code**, behind a `reverse()` that throws before any write.
  **Unreachable is not fixed.**
- ⚠️ **The DEFERRED surface is a trigger at GO-LIVE A10**, not a closure.
- ⚠️⚠️ **And the long list of what stands between this and a paying customer is NOT CODE and has not moved in
  months.**

⚠️⚠️ **ON WHAT IS COMMITTED, `git` IS THE AUTHORITY — NOT THIS SENTENCE.** Durably true: the programme's first
**twenty-three passes were committed on 2026-09-14 in seven unpushed commits ending at `19c59931`**, which
closed the condition that hid F-20. ⚠️ **What sits ON TOP of that is a moving quantity — run
`git status --porcelain`.** 🔬 2026-09-19: FIX 29's three closures (F-45, F-46, F-47) were found sitting
uncommitted and were committed by FIX 30. ⚠️⚠️ **WORK LEFT IN THE TREE IS A RECURRING DEFAULT OF THIS
WORKFLOW, NOT A ONE-OFF STATE** — the condition was closed by one pass and reopened by the very next.
**A reader who checks is right; a reader who trusts a frozen sentence is not.**

⚠️ **This document is the only record of the findings in §2b and the rulings in §4 — count them there, not
here.** It went eight passes stale once already; §2 said "two are closed" while twenty were.

⚠️⚠️ **AND SINCE FIX 48 A GUARD COUNTS THEM FOR YOU.** `AuditRegisterIsSelfConsistentTest` re-derives the
census from §2 and §2b's rows and then fails when **any** finding-status claim anywhere under `docs/` disagrees
with its authoritative row. **Five hand sweeps in five consecutive passes each fixed what they saw and each
left more behind** — the last of them reported a clean sweep after correcting two of one claim's three
surfaces. ⚠️ **A status is not a row; it is a claim with more surfaces than anyone counts.** Run the guard; do
not sweep by eye.

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
| ◐ **PARTLY CLOSED** | **1** | F-3 (the `public` realm closed; the **admin** realm reverted and reported as F-32 — ⚠️ **F-32 is now ACCEPTED BY RULING under R-ADMIN-COPY, established FIX 45; F-3 stays PARTIAL for the same reason**) |
| ✅ **CLOSED (FIX 35)** | **1** | **F-23** — wired 2026-09-19 on R-GATE. The category is now EMPTY: nothing is built-but-unwired. |
| ⛔→✅ **WAS BLOCKED; ALL FIVE RULED 2026-09-19** | **5** | ⚠️ **FIX 41 CLOSED TWO OF THESE.** **F-10 → ✅ CLOSED** (R-F10 recorded and pinned in both directions; it read ⚠️ PREMISE FAILED here for nine passes because the answer arrived after FIX 32 ended) · **F-14 → ✅ CLOSED** (R-PHANTOM: the seventeen kept, documented and pinned BY NAME) · **F-24 → ✅ CLOSED (FIX 43)** (R-ARCHIVE built: the owner is notified from the archive writer) · **F-21 → ✅ CLOSED (FIX 46)** (the agreed-rate disclosure now states its own scope) · F-25 → **ACCEPTED BY RULING** (the owner is deliberately not notified) |
| | **27** | |

⚠️⚠️ **THE STATES THAT ARE NEITHER OPEN NOR CLOSED, because calling them either would be false:**
**CONTAINED** — the defect is still in the code and merely unreachable (F-53, F-54) · **ACCEPTED BY RULING** —
the founder weighed it and chose to leave it (F-25, F-32, F-33, F-49, F-52, F-58) · ⚠️ **DEFERRED (new, FIX 46)**
— *ruled to be built, but NOT NOW, with a named TRIGGER that makes somebody meet it again.* **A deferral
without a trigger is an acceptance wearing a different word.** F-36 is the first, and what moves it is the
Channex cutover: its trigger lives as **A10 in `docs/GO-LIVE-REQUIREMENTS.md`**, beside the other thing that
must be true before the channel is switched on.

⚠️⚠️ **AND ⛔ OPEN IS A LIVE STATE AGAIN — FIX 48, 2026-09-19.** For one day this register had none, and
**that zero was an artefact of where the findings were kept, not of the product**: the final verification's
own six findings lived in `FINAL-VERIFICATION.md` §7 and in a report, and **a report is evidence ABOUT the
repository, not the repository.** Entering them ended the zero. ⚠️ **Five rows are open: F-59, F-60, F-61,
F-62, F-63** — one needs an enumeration, one needs a founder ruling, two are disclosed-not-repaired, and the
fifth was found by FOLLOWING one of the others' leads. ⚠️ **None
is a regression; every one of them was already true and simply unrecorded.** ⚠️⚠️ **A zero that survives by
keeping findings outside the register is the most dangerous number this project can print.**

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
| ⚠️ **FIX 32 – FIX 47** | 2026-09-19 | — | ⚠️⚠️ **NOT LEDGERED — THIS TABLE STOPPED BEING MAINTAINED AFTER FIX 31 AND SIXTEEN PASSES ARE MISSING FROM IT.** They are not lost: each is recorded in the STATUS column of the finding it closed, in §2 and §2b, which is where the live record lives. ⚠️ **Noticed by FIX 48 and recorded rather than back-filled** — reconstructing sixteen rows from their findings would be a derivation nobody could check, and this row is true where sixteen invented ones would not be. |
| **FIX 48** | 2026-09-19 | **V-1 … V-6** → F-59 … F-63 | ⚠️⚠️ **THE VERIFICATION'S OWN SIX FINDINGS, ENTERED — AND THE ZERO ENDED HONESTLY.** They had lived only in `FINAL-VERIFICATION.md` §7, so *"zero open"* was true only because they were outside the register. Classified before entry: **two were record contradictions and were FIXED**, **one was an INSTRUMENT fault** and went to `INSTRUMENT-ERRORS.md` class N (it earns no finding number), and **three became rows** beside a fourth from the same document's guard table. ⚠️⚠️ **AND THE REAL DELIVERABLE IS THE GUARD:** `AuditRegisterIsSelfConsistentTest` parses every `F-<n>` mention under `docs/` and fails when one disagrees with its authoritative row. 🔬 **On its first run — before any fix — it reproduced V-1 and V-2 unaided and found THREE MORE surfaces nobody had named: five stale claims across three findings, where the verification had reported two.** ⚠️ It then caught **this pass's own prose**, twenty minutes after it was written. |
| **FIX 49 – 54** | 2026-09-19/20 | F-60, F-61, F-63 | ⚠️ **NOT SUMMARISED HERE — each pass is recorded in the finding row it moved**, in §2b above. Listed so a reader does not read the gap as idleness. |
| **FIX 55** | 2026-09-20 | **F-64** (new) · R5 | ⚠️⚠️ **THE PRODUCT STOPPED TELLING GUESTS A FALSE THING.** A tentative hold emailed the guest *booking confirmed* — 🔬 measured at ONE send, by a test written to assert the correct behaviour and **run before the fix**, so the red was the measurement. Entered as **F-64** with **pre-existing** provenance before a line was changed, then fixed and closed in the same pass. ⚠️⚠️ **THE FIX IS PLACED, NOT JUST WRITTEN:** the gate sits at DISPATCH, not inside the job, because 🔬 the send carries a **sixty-second delay** and a check inside the job would let a hold-time job and a confirm-time job both fire when an operator confirms inside that window. Deciding at dispatch means a hold queues nothing at all — **and the empty queue is asserted, so the race has no second contender to test.** ⚠️ The event is untouched: 🔬 three listeners consume it at hold time and two are correct (the ARI push, the operator notice); only the email was wrong. ⚠️ The most-used journey is pinned by count — an ordinary direct booking still sends **exactly one**. ⚠️ **R5's refusal arm written** (FIX 54 built the route and reported the arm undone), naming the shipped **Confirm hold** label, tied to the view so the two cannot drift. All three pins mutation-proved. |
| **FIX 56** | 2026-09-20 | F-59, F-62 (measured, not built) | ⚠️⚠️ **TWO COUNTS, NO BUILDS — AND THE COUNT IS WHY BOTH ROWS COULD BE DECIDED.** Neither remaining row was a product defect; both were instruments. 🔬 For F-62 the decisive question turned out not to be *how many rows* but **which columns survive a reseed**, and four natural keys proved unique and stable — so the remedy was **buildable**. 🔬 For F-59 the same rule measured **3 / 47 / 163 / 190** at four call-graph depths, so the remedy was **not buildable at any honest depth**. ⚠️ **The pass committed nothing, deliberately** — and that is why FIX 57 had to carry its measurements into the rows: **a report is evidence about the repository, not the repository.** |
| **FIX 57** | 2026-09-20 | **F-62 CLOSED · F-59 ACCEPTED** | ⚠️⚠️ **THE GATE NOW READS ROWS, NOT ONLY TOTALS — AND IT READS THE TABLE THE FAILURE ACTUALLY LIVES IN.** A row digest over `rate_calendar`, `reservations`, `payments` **and `owner_ledger`**, asserted in FULL mode. ⚠️ `owner_ledger` was the point: a credit moved between owners is the motivating example, **no version of the gate had ever read that table**, and its only other instrument is count-plus-sum — **a digest over the other three would have closed a class that does not contain its own motivating example.** 🔬 **Proven by failure, not by green**: the old gate printed `mode=FULL` exit 0 on a moved credit; the new gate exits 1 naming the class. ⚠️⚠️ **And a trap caught mid-build — MySQL `CONCAT()` returns NULL if any argument is NULL**, which collapsed all sixteen payment keys to one; every component is now coalesced and **the gate asserts key uniqueness and refuses rather than guessing.** ⚠️ F-59 accepted by ruling with its depth sweep, `KNOWN-LIMITS.md` **L11** written for whoever answers a security questionnaire, and the never-a-fact **"54"** retired wherever the live record stated it. 🔬 Gate cost 0.85s → 0.91s. |
| **DEPLOY 06** | 2026-09-20 | **F-67 OPENED · L12f CORRECTED · L12g/h/i written** | ⚠️⚠️ **A MEASURE PASS THAT OVERTURNED THE PREVIOUS PASS'S HEADLINE NUMBER.** DEPLOY 05 recorded the queue's worst case as *"≈60 seconds, and unbounded under maintenance"* and called it *"the latency a **payment webhook** could experience"*. 🔬 **The Stripe webhook queues nothing** — `WebhookEventClaim::once()` persists the `webhook_events` row and applies **synchronously, inside the request** — so no queue latency and no `--force` ever touched the payment path. ⚠️ **The 60 seconds was real and attached to the wrong thing**, and the row's remedy (*"add `--force`"*) would have run jobs against a half-migrated schema. ⚠️⚠️ **THE REAL EXPOSURE IS UPSTREAM OF THE QUEUE:** 🖥 `POST /webhooks/stripe` returns **503 on the live host** — `PreventRequestsDuringMaintenance` is **global** and nothing excludes it — **before the controller**, so there is **no row, no job, no log line**: nothing inside the product records that an event was attempted, and only Stripe's Dashboard can say what was missed. 📄 Stripe retries *"up to three days"* live but only *"three times over a few hours"* in the sandbox mode this deployment is in. ⚠️ **The deploy window is not the risk** (🔬 under 15s of command time); **the standing park is** (🔬 5h29m and counting when measured). ⚠️ Excluding the webhook route was considered and **REFUSED**: a handler running against a half-finished deploy is strictly worse than a 503 Stripe will retry. ⚠️ **#3 ruled NOISY-NEVER-LYING** and pinned by `HeartbeatIsMaintenanceGatedTest` (mutation-proved both arms) · **#4** driver + isolation pins added to `ops/runtime-contract.sh` (both mutation-proved) · 🔬 **proof by positive artefact**: a real down→up→down toggle on production moved both frozen heartbeats (**scheduler 09:53:02 → 15:22:01, queue 14:12:35 → 15:21:03**) in a 160-second window, establishing in one stroke that the panel crons are alive, that the silence was maintenance, and that a worker drains within ~60s of a lift. |
| **DEPLOY 07** | 2026-09-20 | **F-67 answered (not adopted) · L12j/L12k written** | ⚠️⚠️ **F-67 AND THE PARK WERE ONE PROBLEM, AND NAMING THEM AS ONE IS WHAT PRODUCED THE ANSWER.** The handler runs **inline**, so a file upload over a live app could run half-old code on the money path → so the site is parked during changes → so the webhook 503s → and the park is unbounded while 📖 Stripe's retry is not (~3 days live, **~hours in the sandbox mode this deployment is actually in**). ⚠️ So the target was never "a deploy script" but **a number: how long is `/webhooks/stripe` unreachable during an ordinary code change?** 🔬 **Measured on the live host, site parked throughout** (`/up` is served while parked, so the swap could be measured without exposing the site): LiteSpeed **does** follow a symlinked docroot · migration **4ms** · `cp -al` of 12,402 files **1,165ms** · the swap **3–4ms** · ⚠️⚠️ **0 non-200 out of 520 probes across three runs** · new code serving within **48ms–3,441ms**. ⚠️⚠️ **AND THE EDGE THAT WOULD HAVE BEEN GOT WRONG: deleting the previous release returned 60/60 → HTTP 500** (4/40 still failing ten seconds later), because workers stay bound to the release they resolved. **The swap is free; pruning is what breaks the site.** ⚠️ The opcache pair that looked fatal on paper (`revalidate_path=0`, `realpath_cache_ttl=120`) cost a **staleness** window, not an outage. ⚠️ **No git remote is required**, so no ruling was needed there. ⚠️ **#3 needed no build** — `ops/runtime-contract.sh` already asserts the interpreter ≥8.4.1, and 🔬 a bare `php artisan` on this host exits **255** rather than running wrongly, so the "latent wrong-version execution" is a **loud refusal**. ⚠️ **#5 built the mechanism rather than a third rule**: `ops/proc-check.sh` refuses to report a zero it cannot vouch for — ⚠️ and its **own first two versions each produced the false negative it exists to refuse** (an exec-optimised marker, then `grep -q` under `pipefail` turning a match into a pipeline failure). ⚠️ `MEMORY.md` trimmed **227 → 171** by condensing, with the ceiling now pinned by a test. ⚠️ The maintenance bypass secret was rotated — old one proven dead (503), new one proven working (302), never echoed, never a shell argument. |
| **DEPLOY 08** | 2026-09-20 | **F-67 CLOSED · L12k ADOPTED · L12m opened** | ⚠️⚠️ **THE FIRST IRREVERSIBLE ACTION IN THE PROGRAMME, AND IT WAS EARNED BEFORE IT WAS TAKEN.** 148 commits pushed to `Subhankhan12/vyrobel` after **four hard stops checked and declared separately** — 🔬 private=true **from the API**, owner==authenticated identity, `origin/main` an **ancestor** of local (so a plain fast-forward; `--force` neither possible nor needed), and a credential scan of **every blob in every ref**. ⚠️ **The scanner was not trusted until it found a needle planted in a scratch clone's history** — tree mode correctly missed the deleted file, history mode found all three, which is the property that matters: **a push publishes what the working tree cannot show you.** ⚠️ 36 distinct hits triaged **by reading**: interpolation, a shell default, test fixtures, and the demo seed password — **reported, never allow-listed.** ⚠️⚠️ **THEN THE LAYOUT, AND THE THREE THINGS READING WOULD HAVE MISSED:** `FollowSymLinks` is required for a symlink *inside* the tree · Laravel's caches **bake absolute paths** (the framework 500'd behind a correct-looking 503) · and **the panel cron is hardcoded to `public_html/artisan`, so the migration silently killed the GDPR cron** — caught only by reading the cron log. ⚠️⚠️ **AND THE DISCOVERY NOBODY WAS LOOKING FOR: there is a CDN in front of production** (`server: hcdn`) that cached a transient 301 and replayed it for minutes while the origin was correct — ⚠️ **any production measurement through that hostname is measuring the edge unless it is cache-busted**; DEPLOY 07's 0/520 survives only because `/up` happens to be `no-cache, private`. ⚠️ 🔬 The numbers: swap **3 ms**, **0 non-200 of 220**, new release at **t+32 ms**, prune safe at **+180 s (0/60)** against **60/60 HTTP 500** immediately — ⚠️ the retention floor is the **120 s realpath TTL**, not process lifetime (the oldest `lsphp` aged 180→360 s **without ever being recycled**). |
| **DEPLOY 09** | 2026-09-20 | **L12m rewritten · L12h resolved · the edge measured** | ⚠️⚠️ **THE LAYER NOBODY OWNED, AND WHAT IT DOES TO EIGHT PASSES OF NUMBERS.** A CDN (`server: hcdn`) sits in front of production and appears in **no document this project owns**; it cached a transient 301 and replayed it **over two hours** (`age: 7004`, still climbing) while the origin served a correct 503. ⚠️⚠️ **Every deployment figure — 0/520, 0/220, the 3 ms swap — was taken through `/up`, which is `no-cache, private`: the ONE route exempt from the layer under test.** ⚠️ **A probe exempt from the thing under test measures nothing about it**, and that probe was chosen for convenience eight passes before anyone knew the layer existed. 🔬 **Re-measured at the edge, uncache-busted, on `/login` across a real swap: 199/200** (one connection failure) against **0/100** in a no-swap control — ⚠️ neither attributed to the swap nor dismissed as ambient. ⚠️⚠️ **THE GATE-3 QUESTION IS ANSWERED FAVOURABLY: the edge cannot answer for the webhook.** 🔬 Live, a junk-signature POST returns `400 {"error":"invalid_signature"}` — a body only the origin's controller produces — on **5 singles, 20 rapid identical, and the Connect endpoint**, with **no `age` on any**; and parked, an origin witness recorded **38 of 38** arrivals including a 25-request burst. ⚠️ Bound: 25 requests from one IP is not a flood. ✅ **HTML/asset skew is impossible here** because HTML is never cached — ⚠️ **a property to protect, since making any page publicly cacheable re-opens it and would raise the prune floor from 180 s to 7 days.** ✅ **Production is now a COMMIT HASH:** diffed file by file first — **1,279 identical, 2 differing, 0 production-only** — so the hard-stop question ("hand-patched work that exists nowhere else?") answered **zero**, then deployed via the release mechanism with a `.release` marker. ✅ The 8 foreign remote branches scanned: **28 blobs unique to them, all audit documentation, nothing credential-shaped**; none deleted, none rewritten. |

### CLOSURE LEDGER — what proved each closure

⚠️ **Derived per finding from the pass that closed it.** Where a closure rests on a test, the test is the
regression net; where it rests on a browser reading, it is **not re-runnable** and is marked so.

| finding | pass | what proved it |
|---|---|---|
| F-1 | 06 | 🖥 Both halves read **on the rendered page** — the change confirmation *and* the gate interruption. `FlashRenderedTest`. |
| F-2 | 09 | 🔬 The device-authorization grant no longer offered: `oauth` routes **10 → 5**, the four device routes gone. |
| F-3 | 09 | 🔬 `public` realm branched and asserted. ⚠️ **Admin half NOT closed** — see F-32. |
| F-10 | 41 | 🔬 R-F10 pinned in BOTH directions: no editable role in a freshly provisioned org holds `billing.manage` / `settings.manage` / `roles.manage`, AND all three remain offered by the matrix and can really be granted. ⚠️ One-directional would have been satisfied by DELETING them. `SeededDefaultKeepsTheThreeTest`. |
| F-14 | 41 | 🔬 R-PHANTOM: the seventeen pinned BY NAME in their three shapes (6/8/3) against the computed census. ⚠️ A count is not a pin — measured: under a rename the count stays 17 and a count-only assertion stays green. `PhantomPermissionRegisterIsPinnedTest`. |
| F-23 | 35 | 🔬 `EnsureOwnerPortalAccess` wired into the `/owner` group BEFORE the scope, its twelve skipped cases re-activated, and the ordering proven by a query-count test. 🖥 Both directions browser-verified. ⚠️ **Added FIX 41 — FIX 35 declared the closure and recorded nothing here.** `IdentitySurfacesTest`. |
| F-56 | 41 | 🔬 `revoke()` refuses to remove a member's last role, before any write, naming the membership as the way out; the positive control proves it still removes a role when others remain; a structural test pins that `revoke()` is the ONLY application-level remover. `LastRoleIsRefusedTest`. |
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
| **F-10** ✅ **CLOSED (FIX 41) — R-F10 RULED 2026-09-19** | platform | `billing.manage` / `settings.manage` / `roles.manage` are reachable by nobody below Property Owner. ⚠️ **FIX 32 found the premise half-wrong** — 🔬 the three are ALREADY grantable through the role matrix, so "unreachable" was never true; the question underneath (**should the SEEDED DEFAULT grant them?**) had never been put. ⚠️⚠️ **THE ANSWER ARRIVED AFTER THAT PASS ENDED AND WAS RECORDED NOWHERE**, so this row read ⚠️ PREMISE FAILED for nine passes while a ruling existed. | P4 |
| **F-11** ✅ CLOSED (FIX 09) | operator | Navigation dead ends — a **per-link filtering gap**. ⚠️ The scaling claim was **RETRACTED** in P7; measured tally across ten roles: 1·2·0·2·2·2·1·1·2·(1). | P5, retracted P7 |
| **F-13** ✅ CLOSED (FIX 07) | operator | The housekeeping board renders with zero controls and no explanation. ⚠️ **Bounded** in P7, then **located** in P8: it is the *Board* view, and it *does* say "Assign to start" — a rule the server does not have. | P6→P8 |
| **F-16** ✅ CLOSED (FIX 07) | operator | **Two views of one screen state different rules and the server agrees with neither.** The choice lives in `localStorage['vy:hk:view']` — one click on "Board" leaves a housekeeper unable to start any task, with no server trace. | P8 |
| **F-17** ✅ CLOSED (FIX 05) | operator | A housekeeping status change writes **no audit row**, while three siblings in the same service do. `domain_events` has no actor column and the table has no `started_by`/`completed_by` — **who cleaned a room is recoverable from nowhere.** | P8 |
| **F-18** ✅ CLOSED (FIX 04) | operator | `CheckInReservation` / `CheckOutReservation` guard only *target-equals-current*, never a legal **source** state. 🔬 Proven in rolled-back transactions: a cancelled booking → `checked_in`; a confirmed booking → `checked_out`. ⚠️ **Latent — no reachable caller today**; both live callers guard correctly. | P9 |
| **F-21** ✅ **CLOSED (FIX 46) — RULED 2026-09-19: the disclosure states its own scope.** 🔬 One sentence, RENDERED (the limit lived only in a Blade comment before), and **unconditional — inside the `agreed_nights > 0` template it would be hidden at ZERO, exactly when "nothing is at risk" is the wrong conclusion.** ⚠️ **It states the RULE rather than a list of exclusions, so it cannot be incomplete**: the count sees only rates agreed through a proposal, and only while the price still matches what was agreed. ⚠️ FIX 45 found TWO limits where this row recorded one; an owner's self-set rate is excluded BY the first rule, not by a third. ⚠️ How the number is computed is UNCHANGED. `OperatorRatesTest` | operator | The agreed-rate disclosure is **correct but its scope is invisible** — the limit (it cannot see an owner's self-set rate) lives only in a **Blade comment**, which never renders. | P11 |
| **F-23** ✅ **CLOSED (FIX 35) — ⚠️ row corrected FIX 41, it read 🔨 BUILT · UNWIRED for five passes** | owner | **No positive "is an owner" gate.** 🔬 An operator reached `/owner`, `/balance`, `/payouts`, `/settings` (all 200) and downloaded a statement CSV. **No data leaked** — every figure €0.00, the CSV 2 lines (watermark + header). An unguarded admission with a misleading surface, and the inverse of invariant #26. | P13 |
| **F-25** ⚠️⚠️ **ACCEPTED BY RULING, 2026-09-19 — NEITHER OPEN NOR CLOSED.** The founder chose operator-side disclosure without owner notification, so the owner still cannot discover this. Pinned by `AgreedRateDisclosureTest`. Calling it closed would be false; calling it open implies work nobody intends | owner | **An owner cannot discover that a season replaced their negotiated rate.** No notification covers it; the only listener on `rate_calendar.updated` pushes to channel managers; `/owner/rates` shows today's value with no history — and that screen is co-owner only. | P13 |
| **F-26** ✅ CLOSED (FIX 01) | guest | **Account deletion cancels and refunds upcoming bookings; the guest was under-told.** 🔬 `processDue()` calls `cancelWithPolicyRefund` on every live reservation. ⚠️ **The audit overstated this**: the screen already said *"Upcoming reservations are cancelled per the property's policy"* — a Phase 14 `grep -o 'This schedules[^<]*'` stopped at the first `<` and missed it. What was genuinely wrong: it said "**Upcoming**" while the code cancels *every* live booking **including a stay in progress**; it did not quantify; and the scheduled state, the email and the notification said nothing at all. ⚠️ The class docblock also contradicted its own method ("the policy is display-only"). | P14, corrected in FIX 01 |
| **F-27** ✅ CLOSED (FIX 08) | guest | **The paid-but-unclaimed window.** The confirmation page's only action is "Back to {property}"; the email's five deep links use `route()` not `signedRoute()` and land a first-time guest on `/guest/login`, headed **"Welcome back"**, where the bridge is a footer link. | P14 |

### 🟡 LOW

| # | realm | finding | evidence |
|---|---|---|---|
| **F-3** ◐ **PARTIAL BY RULING — RULED 2026-09-19 (R-ADMIN-COPY: the 403 fallthrough stays)**; closed for `public` by FIX 09 | auth | Role-flavoured 403 copy on roleless doors. | P1 |
| **F-7** ✅ CLOSED (FIX 09) | operator | `/app/reservations/create` and `/app/group-booking` render **zero `h1`**. Confirmed P9. | P3, P9 |
| **F-8** ✅ CLOSED (FIX 09) | operator | Two unnamed date inputs on `/app/group-booking`. | P3 |
| **F-12** ✅ CLOSED (FIX 09) | operator | Two `h1` on the maintenance ticket detail. 🔬 **Confirmed six times** (P5, P9, P10, P11, P12 ×2). | P5 → P12 |
| **F-14** ✅ **CLOSED (FIX 41) — R-PHANTOM RULED 2026-09-19** | operator | `housekeeping.view` is enforced nowhere. ⚠️ **REGISTERED** — in `PHANTOM-PERMISSIONS.md`, asserted by a test, awaiting a founder ruling. **Not a discovery.** ⚠️⚠️ **RULED: KEEP the seventeen, DOCUMENT them, PIN THE EXACT REGISTER.** No seeder churn, no role-table churn — `housekeeping.view` stays exactly as it is, and is now pinned BY NAME alongside the other sixteen. | P6, reframed P9 |
| **F-19** ✅ CLOSED (FIX 09) | operator | A route comment **inverts the truth**: it states `AssignmentService::terminate()` has no status guard and calls the fix "outstanding". 🔬 The guard exists and throws; `CLAUDE.md` records it as shipped invariant #29. | P9 |
| **F-22** ✅ CLOSED (FIX 05) | operator | Season create / edit / delete write **no audit row** — 🔬 `audit_events` unmoved across all three operations. | P11 |
| **F-24** ✅ **CLOSED (FIX 43) — R-ARCHIVE BUILT** | owner | **No notification of any kind when a property is archived.** The *financial* consequence is handled deliberately ("money that was earned does not stop having been earned"); the communication is absent. | P13 |

---

## 2b · FINDINGS THE FIX PROGRAMME FOUND ITSELF — F-28 onward

**Findings no audit phase recorded — count the rows, never this sentence.** ⚠️ It read *"Twenty-two … F-28 … F-49"* over 28 rows reaching F-55 until FIX 41: three passes extended the table past F-49 and none moved the heading. They were found by *changing* the code, not by walking it — which
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
| **F-36** | 🟠 MED | operator | **A refused channel modification surfaces on no screen.** 🔬 FIX 14's refusal writes `reservation.ota_modification_refused` to `audit_events`, and **nothing renders it**: `ReservationDetail`'s timeline reads `reservation_status_history` plus audit rows whose `subject_type` is a **Payment** (three payment actions only), so a reservation-subject row is filtered out — and **no operator surface reads `webhook_events` or `sync_jobs` at all** (grep across `app/Livewire/` and `resources/views/`). ⚠️ **The F-13 shape**: the system refuses correctly and tells nobody. A channel could be sending modifications that are all being dropped and the only evidence is a database query. ⚠️ Note a throw would not help either — that path writes `sync_jobs.last_error`, not `channels.last_error`, and the Channels screen reads the latter. | FIX 14, 2026-09-13 | ⚠️⚠️ **DEFERRED (2026-09-19) — BUILD IT WHEN THE CHANNEL GOES LIVE, NOT NOW.** ⚠️ **NOT accepted: a deferral without a trigger is an acceptance wearing a different word**, so the trigger is **A10 in `docs/GO-LIVE-REQUIREMENTS.md`**, beside the other thing that must be true before Channex is switched on — where the person enabling it meets it without going looking. ⚠️ **THE COST THE FOUNDER ACCEPTED:** until that surface exists, the first symptom of a refused booking is **an operator asking why it never arrived, and nobody able to answer from the product.** ⚠️ It costs nothing while the channel is off, which is exactly why it was deferred rather than built |
| **F-30** | 🟡 LOW | owner | `OwnerUnitStatusService::setActive()` **accepted `$actorId` and dropped it** — owner pause/resume audit rows recorded the change and not the person. The parameter's presence made the trail look complete. | FIX 10, 2026-09-13 | ✅ CLOSED (FIX 10) |
| **F-31** | 🟡 LOW | operator | **F-19's unrecorded sibling** — the same inverted claim about `AssignmentService::terminate()` in a controller docblock. The audit found one copy; there were two. | FIX 09, 2026-09-13 | ✅ CLOSED (FIX 09) |
| **F-32** | 🟡 LOW | admin | **`ErrorRealm` has no admin branch**, so a platform-admin 403 carries operator-flavoured copy. ⚠️⚠️ **RE-MEASURED FIX 45, AND THAT DESCRIPTION IS WRONG TODAY: `ErrorRealm` DOES have an `admin` branch** — 🔬 the realm resolves (`str_starts_with($path, 'admin')`) and the back-link is `/admin` "Back to the console". **Only the 403 BODY falls through to the operator sentence** — a much smaller thing than this row claimed. ⚠️ **A fix was written and REVERTED**: it collided with a certified assertion that pins the current copy. Closing it means changing that assertion — a decision, not a fix. F-3 is partly closed for this reason. | FIX 09, 2026-09-13 | ⚠️⚠️ **ACCEPTED BY RULING — ESTABLISHED FIX 45, NOT DECIDED BY IT.** 🔬 The founder answered this on 2026-09-19: **R-ADMIN-COPY — "LEAVE THE 403 FALLTHROUGH. The admin branch and back-link stay; the body stays generic"** — recorded **SATISFIED** in §4 and **pinned by `SevenSmallFindingsTest`** (*"the admin 403 KEEPS the operator body, and that is deliberate"*). ⚠️⚠️ **THIS ROW SAT ⛔ OPEN FOR ELEVEN PASSES AFTER THE QUESTION WAS SETTLED**, because F-32 was never re-read against the ruling that answered it: R-ADMIN-COPY names **F-3**, and F-32 is the same fallthrough one realm over. ⚠️ **Nothing was built or decided here — the row was brought into line with §4, which is a measurement** |
| **F-33** | 🟠 MED | operator | **The operator shell folds at 375px** and the navigation becomes unreachable. ⚠️ **FIX 11 changed nothing** — the obvious fix pushed all 22 nav links off-screen, caught by one further measurement. Costed options were reported to the founder. ⚠️ **54 screens sit inside this shell.** | FIX 11, 2026-09-13 | ⚠️⚠️ **ACCEPTED BY RULING (2026-09-19) — THE BACK OFFICE IS DESKTOP-ONLY, DELIBERATELY.** No mobile navigation was built; the ruling was a POSITION, not a feature. ⚠️ Recorded in `docs/KNOWN-LIMITS.md` **L7** with its customer-facing sentence — and **with the argument AGAINST it, so it can be re-opened honestly: housekeeping staff in a real hotel work from a phone**, and `Housekeeping` is one of the eleven back-office roles inside this shell. ⚠️ 🔬 **No product surface states this today** and none was invented — where it should live is a founder decision, reported not taken |
| **F-34** | 🟠 MED | operator | **`RoomType::primaryRatePlan()` returns a deactivated plan when it is the declared default.** The fallback branch filters `is_active`; the `default_rate_plan_id` branch does not. `setRatePlanActive()` refuses only the *last active* plan, never the *declared default*. 🔬 The precondition is live on seeded data — "Double Room" has a declared default **and** 3 active plans, so the refusal does not fire. ⚠️ **FIX 12 built the first operator-reachable way to produce this state.** The consequence (both Rates surfaces edit an off-sale plan) is read from source, **not exercised**. | FIX 12, 2026-09-13 | ✅ **CLOSED (FIX 15)** — ⚠️ **it was THREE lookups, not one**: `RoomType::primaryRatePlan()`, plus copies in `RatesGrid` and `UnitAvailabilityService` whose default branch was ALSO unfiltered and whose fallback was saved only by an eager-load constraint hundreds of lines away. One definition now; all three entry points proven to agree. ⚠️ **R7 open** — whether deactivating the declared default should also clear `default_rate_plan_id` |
| **F-43** | 🔴 **HIGH** | guest | **The public checkout quoted a per-night price in euros under a pound total — at the point of payment.** 🔬 `App\Support\Folio\StayEstimator::estimate()` built the room line's sublabel as `'€'.number_format(...)` while the amount beside it was built in `$currency` — **the same function's own argument**, one line above. It renders on `booking/checkout.blade.php:253`, the PUBLIC guest checkout, and also on the operator's Property-Settings sample stay. ⚠️ **More severe than F-38, and found only because F-38's sweep was widened from views to shared helpers**: F-38 misleads an operator reading their own grid; this one misquotes a *guest* about what they are about to be charged, on a page whose whole job is to state the price. ⚠️ **A shared `App\Support\*` helper — the anti-drift family's own rule broken from the inside.** ⚠️ Latent while every property was EUR. | FIX 22, 2026-09-14 | ✅ **CLOSED — FIX 22.** Routed through `MoneyFormatter::symbol($currency)` |
| **F-44** | 🟠 MED | all realms | ⚠️⚠️ **RE-GRADED BY FIX 29 (was 🟡 LOW): the class is WIDER and LIVE.** 🔬 Nine FURTHER sites fall back on an **EMPTY COLLECTION** rather than a null column — `OwnerRates:200`, `OwnerMyUnits:122`, `OwnerReports:599`, `OwnerInsights:330`, `OperatorPortfolio:84,217`, `GuestPayments:49`, `GuestWalletController:40`, and `RatesGrid:537`. ⚠️ **FIX 22's reachability evidence was the SCHEMA, and the schema cannot see an empty set** — which is why 45 fallbacks were proved dead while this shape was never counted. ⚠️ **One of the nine (`RatesGrid:537`) was written BY FIX 22 while fixing F-38** — an instance of the very class the pass was closing. ⚠️ `portfolioCurrency()` is closed as **F-46**; the other nine are REPORTED, not fixed. **45 unreachable `?? 'EUR'` currency fallbacks sit in live code, indistinguishable at a glance from the reachable one that was a real defect.** 🔬 FIX 22 enumerated 46 sites reading a currency as `$x->currency ?? 'EUR'`; checked against the schema, **45 can never fire** — `currency` is `NOT NULL` on `reservations`, `payments`, `folios`, `rate_calendar`, `rate_plans`, `owner_ledger`, `owner_statements`, `payouts` and `properties`. The 46th (`maintenance_tickets.currency`, the schema's only nullable currency column) WAS reachable and was fixed. ⚠️ **NUMBERED BECAUSE IT IS EXACTLY THE CAMOUFLAGE THAT HID F-38**: a dead fallback and a live one read identically, so a reviewer cannot tell which matters without re-checking the schema each time. ⚠️ **AND IT IS ONE `nullable()` AWAY FROM BEING LIVE** — making any of those columns nullable silently converts a dead branch into F-38 again, with no test to catch it. ⚠️ Not a present defect: nothing renders a wrong currency today. | FIX 22, 2026-09-14 | ✅ **CLOSED (FIX 46) — RULED 2026-09-19: fix all nine AND pin the class.** ⚠️⚠️ **IT WAS TWELVE, NOT NINE — AND THE GUARD FOUND THE OTHER THREE ON ITS FIRST RUN** (`MaintenanceList`, `OperatorInsights`, `OperatorReports`: a `->first()` query that matches nothing when no property is accessible — the same defect one shape over). ⚠️ **That is the argument for a guard over a checklist, made by the guard.** 🔬 The four OWNER surfaces now derive from `owners.payout_currency` (F-46's own answer, char(3) NOT NULL); the other eight have no owner and no property in scope when the collection is empty, so they carry the ONE product default (`config('billing.default_currency')`) instead of a literal repeated per file. ⚠️ **The guard is the deliverable**: `CurrencyNeverDefaultsTest` forbids a currency literal behind an object that may be absent — the blind spot FIX 22's schema evidence could not see — and 🔬 **a fallback planted in a real file makes it RED**. It reuses FIX 42's stripper rather than being a third scanner, reads `app/` only (asserted, not claimed), and its allow-list holds ONE entry, itself a detector false positive with its reason |
| **F-45** | 🟠 MED | operator · guest | **A folio charge recorded no author.** 🔬 `FolioService::postCharge()` and `applyWalletCredit()` wrote a `domain_events` row and **no `audit_events` row**, while every payment method in the SAME service (`recordPayment`, `recordCheckoutIntent`, `settlePayment`) wrote both. ⚠️ **A domain event cannot answer "who"** — 🔬 `domain_events` has **no actor column**, measured. So a write that INCREASES WHAT A GUEST OWES was unattributable. ⚠️ **F-17/F-22's class in a writer FIX 05's sweep never reached** — which is exactly why `FINAL-VERIFICATION.md` called that class closed "where measured". ⚠️ 🔬 **Exactly TWO writers create a `FolioItem`** in the whole codebase, and `postAddOn`/`applyTax`/`applyFee` funnel through `postCharge`, so one recorder covers every entrance. | FIX 27, 2026-09-14 | ✅ **CLOSED — FIX 29, 2026-09-14.** Both writers audit, copying the sibling's shape; the morph map was checked first (`folio_item` IS registered, so no throw). 🔬 Mutation-proven: recording the row with a null actor goes RED |
| **F-46** | 🟠 MED | owner | **A GBP owner saw euros on their first day, on their own earnings page.** 🖥 MEASURED in tenant two: "EARNED YTD **€0.00**" and "NEXT PAYOUT **€0.00**" for an owner paid in GBP. `FormatsOwnerData::portfolioCurrency()` returned a bare `'EUR'` when its collections were empty — so it fires **before any statement or payout exists**, on four screens (dashboard, balance, payouts, expenses). ⚠️ **The amount is right and the symbol is wrong — the dangerous direction**: zero looks identical in every currency, so there is no arithmetic cue. ⚠️⚠️ **F-44 RECORDED THIS CLASS AS LATENT AND IT WAS LIVE.** FIX 22's reachability evidence was the SCHEMA (every `currency` column is NOT NULL) — correct, and **blind to a fallback keyed on an EMPTY COLLECTION**. | FIX 27, 2026-09-14 | ✅ **CLOSED — FIX 29, 2026-09-14.** Derives from the OBJECT: `owners.payout_currency` (`char(3)` NOT NULL). 🔬 Mutation-proven in BOTH tenants — the EUR control would go RED on a fix that hardcoded GBP |
| **F-47** | 🟡 LOW | guest | **A guest who could not claim was sent to a login wall.** 🖥 The refusal copy was never the defect — 403, no identity leak, the expiry named, a human route suggested. But its only CLICKABLE route was "Back to your trips" → `/guest/trips`, **which requires the account the person was trying to create**. ⚠️ **A guest who cannot claim simply leaves, and nothing in this product would ever report it** (Phase 14). | FIX 26, 2026-09-14 | ✅ **CLOSED — FIX 29, 2026-09-14.** `ErrorRealm::home('guest')` now follows the SESSION, not the URL prefix: signed out → `/guest/claim` (`web`-only, no auth guard), signed in → their trips. ⚠️ Enumeration discipline kept — the same public door, so it discloses nothing. Wording untouched and pinned |
| **F-48** | 🟠 MED | platform | **A suspended tenant can still sell.** 🖥 MEASURED with a positive control: suspending tenant two set `organizations.status='suspended'` and its `/stay/{org}` route correctly **404'd** while an active org's returned **200** — so the guard discriminates. But **`/book/{property}` — the storefront that actually takes bookings — still returned 200**. `ResolvePublicOrganization` guards `/stay/{organization}` only; the per-property storefront never passes through it. ⚠️ **The admin's own confirm copy is honest about this** (*"it does not yet block their access"*); the middleware's comment (*"a suspended org's public storefront is down"*) is true of ITS route and reads broader than it delivers. | FIX 26, 2026-09-14 | ✅ **CLOSED (FIX 36, 2026-09-19)** — R-SUSPEND ruled, and the check now lives in `ResolvePublicProperty`, which 🔬 all nine `book/{property}` routes run and none bypasses. The sitemap's property query was org-blind in exactly the same way and is closed with it. ⚠️ The guest is never told why; existing stays are untouched (no `guest/` route runs that middleware). ⚠️ `suspended` ONLY — `cancelled` is **F-51**, and the OTA path is **F-52** |
| **F-49** | 🟡 LOW | owner · platform | **A payout can be made and cannot be un-made through the product.** 📖 ⚠️ **TIER CORRECTED FIX 42 — this read 🔬 MEASURED until now, on the exact sentence the status column says was ASSERTED AND WRONG. The row recording the assertion-versus-measurement lesson was mislabelled by it.** `OwnerPayoutService::reverse()` was described as a genuine, provable inverse — it APPENDS a `reversed` row (never editing the paid one), returns the source statement to `finalized`, and audits with a real `before={"status":"paid"}`. ⚠️ **But no route reaches it**: no controller calls it and no route exists, so an operator can pay an owner and cannot reverse it in the UI. ⚠️ **The capability exists and is simply unrouted** — unlike FIX 27's folio cluster, which has no inverse at all. | FIX 28, 2026-09-14 | ⚠️⚠️ **ACCEPTED BY RULING, 2026-09-19** — the third state F-25 holds: neither open nor closed. The founder accepts that the product can pay an owner and **cannot un-pay them**; `reverse()` is **QUARANTINED (FIX 38)** rather than routed or repaired. 🔬 **AND THE DESCRIPTION THAT STARTED THIS WAS WRONG.** `reverse()` was called "a genuine, provable inverse" — 📖 **ASSERTED, read from source and NEVER EXECUTED** — yet it travelled through this register, the memory record and FOUR briefs as though 🔬 MEASURED. FIX 37 ran it for the first time and found two HIGH defects. ⚠️ **ONLY 🔬 MEASURED AND 🖥 BROWSER ARE FACTS.** ⚠️ Lifting this requires fixing **F-53 and F-54 at the cause** first |
| **F-50** | 🟡 LOW | owner | **The gate's own refusal points at a door that refuses again.** 🔬 A user with no owner account who reaches `/owner` now gets a named 403 — *"You don't have access to this"* — whose body reads *"**Your owner account** doesn't include this area. If you need it, ask the primary owner on your account to grant the **capability**"*, with a back-link **"Back to your portal" → `/owner`**, which refuses them again. ⚠️ **Both halves are wrong for this actor**: they have no owner account and are missing no capability — they are simply not an owner. ⚠️ **This is F-47's shape in a new place** (a refusal offering a route that needs what the person lacks). ⚠️ **NOT A LEAK** — 🔬 no owner name or identity appears. ⚠️ `ErrorRealm`'s owner branch is SHARED with the capability-refused sub-user, for whom the copy is exactly right, so splitting them is a copy decision rather than a fix. | FIX 35, 2026-09-19 | ✅ **CLOSED (FIX 40, 2026-09-19)** — the owner 403 now branches on whether the reader is owner-LINKED, asking the SAME `OwnerContext::actorIsOwnerLinked()` the gate itself asks. ⚠️ **BOTH AUDIENCES WERE MEASURED FIRST, not assumed:** the not-an-owner reader is reachable through the product's own path — `RoleAssignmentService::revoke()` guards the last ROLE-MANAGER but not the last ROLE, so a staff user stripped of their only role keeps an active membership and meets this 403. ⚠️⚠️ **AND THE LINK WAS THE HALF THAT MATTERED:** 🔬 `/app/dashboard` refuses that same reader too, so the obvious destination would have been a SECOND trap; `/` is the only one that admitted them, established by FOLLOWING each as that person. ⚠️ The admin fallthrough (R-ADMIN-COPY) is untouched and its pin stayed green |
| **F-51** | 🟡 LOW | platform | **`cancelled` closes one public surface and not the other.** 🔬 `organizations.status` is `enum('trialing','active','suspended','cancelled')`. `ResolvePublicOrganization` uses `whereIn(['trialing','active'])`, so `/stay/{organization}` closes for **both** `suspended` AND `cancelled`. R-SUSPEND named only `suspended`, so FIX 36 closed `book/{property}` for that one state alone — which **left** a cancelled organisation's booking path open while its `/stay` page was closed. ⚠️ **That gap is now shut: F-51 was ruled 2026-09-19 and built in FIX 39**, so all four states agree on both surfaces. ⚠️ **This is the SAME shape as F-48, one state over.** ⚠️ Deliberately NOT inferred: whether a cancelled account should stop selling is a commercial policy, and the ruling did not cover it. Pinned by a test so a later change is deliberate. | FIX 36, 2026-09-19 | ✅ **CLOSED (FIX 39, 2026-09-19)** — the founder ruled `cancelled` closes the booking path too. 🔬 One change: `ResolvePublicProperty::CLOSED_FOR_BOOKING` became a SET, and the sitemap inherited it because both surfaces read that ONE definition. `/book/{property}` and `/stay/{organization}` now agree on all four states. ⚠️ Pinned by an assertion that `suspended` and `cancelled` are INDISTINGUISHABLE to the public — status and body, byte for byte |
| **F-52** | 🟠 MED | platform · channel | **A suspended organisation still accepts OTA-pushed bookings.** 🔬 `BookingImportService` never reads `organizations.status`, and the inbound path is `POST webhooks/channex/{channel}` — which does **not** run `ResolvePublicProperty`, so FIX 36's guard does not reach it. ⚠️⚠️ **THIS IS A CONFLICT BETWEEN TWO RULINGS, NOT AN OVERSIGHT**: R-SUSPEND says a suspended organisation stops taking bookings; **R6 left the channel manager AUTHORITATIVE**. A sync is arguably not "the booking path". ⚠️ **AND A REFUSAL HERE MUST NOT THROW** — FIX 14 ruled that a throw inside an external sync becomes a retry → dead-letter → an outage nobody can diagnose; if this is closed, it must be a recorded NO-OP like `cancel()`'s. | FIX 36, 2026-09-19 | ⚠️⚠️ **ACCEPTED BY RULING (2026-09-19)** — neither open nor closed. **The channel manager keeps its authority: a closed organisation still accepts OTA-pushed bookings, deliberately.** ⚠️ **THE REASONING:** refusing the sync does not prevent the sale — the OTA has already sold the room and the guest holds a real reservation either way — it only BLINDS THE PMS to a booking that exists, turning a billing dispute into a DOUBLE-BOOKING. A closed storefront stops NEW sales; a refused sync loses the record of one that already happened. ⚠️ **NO CODE CHANGED, WHICH IS WHY IT IS PINNED**: a test named for the decision, plus the reasoning in `BookingImportService` where anyone tempted to add the check would be standing |
| **F-53** | 🔴 **HIGH** | owner · platform | **`OwnerPayoutService::reverse()` is an UNGUARDED APPEND — a double-click reverses an owner's payout twice.** 🔬 MEASURED: one paid payout, two `reverse()` calls, **two `reversed` rows superseding it**, no refusal. ⚠️ **Neither existing protection reaches it.** The method's own `status !== 'paid'` check cannot stop a second press because **`reverse()` never changes the source payout's status** — the row it guards on is still `paid` afterwards. And the unique index `payouts_one_live_per_statement` sits on a VIRTUAL column that is `owner_statement_id` only while status ∈ (`pending`,`processing`,`held`,`paid`) and **NULL otherwise** (🔬 read from `information_schema`), so a `reversed` row keys NULL and **NULLs never collide** in a MySQL unique index. ⚠️ **The concurrent case is moot: there is no guard to race.** ⚠️ **This is the project's own rule fired in the place it was written for — an APPEND needs a guard; a SET does not.** | FIX 37, 2026-09-19 | ⚠️⚠️ **CONTAINED (FIX 38), NOT CLOSED.** 🔬 The unguarded append is still in the code, exactly as measured — it is merely UNREACHABLE: `reverse()` now refuses at its first statement, before any write, and a test pins that the refusal fires BEFORE the append rather than after it. ⚠️ **Calling this closed would be false.** The fix is at the cause — the source payout must reach a TERMINAL status on reversal |
| **F-54** | 🔴 **HIGH** | owner | **After a reversal the statement is STRANDED — `finalized` but unpayable, and the owner is owed money the system believes it has sent.** 🔬 MEASURED: `reverse()` returns the statement to `finalized`, but the source payout **stays `paid`** and therefore keeps the unique live-statement key — so `createFromStatement()` hands back **the SAME original payout, still `paid`**, rather than creating a fresh one. The statement and the payout then disagree permanently. ⚠️ **So a reversal is NOT itself reversible through the product**, which is the question a confirmation screen would have to answer — and the honest answer today is "no, and it leaves the statement unpayable". | FIX 37, 2026-09-19 | ⚠️⚠️ **CONTAINED (FIX 38), NOT CLOSED.** 🔬 The stranding mechanism is unchanged — the statement transition and the payout status are still written by different rules, and only one moves. Only the quarantine stops it executing. ⚠️ Same cause, same one-line fix: a TERMINAL source status releases the unique `live_statement_key` so a fresh payout can be created |
| **F-55** | 🟡 LOW | guest | **The trips list hid a review the emailed link still allowed.** FIX 34 widened the review gate to *has this stay ever completed* and scoped it to `ReviewService` — deliberately, because widening `ReservationStage::PAST` would have started calling occupied rooms "past". The emailed link asked the service; **the trips CARD went on asking the stage**, and a re-opened stay is `IN_HOUSE` — so a guest whose mis-clicked checkout was reversed held a working review link while the button for the same action had vanished from their own list. ⚠️ **The screen disagreed with the server about the guest's own stay.** ⚠️⚠️ **AND IT WAS NEVER NUMBERED UNTIL NOW** — FIX 34 reported it as a residual in its REPORT, and a report is evidence ABOUT the repository, not the repository. | FIX 34, 2026-09-19 | ✅ **CLOSED (FIX 40)** — the card asks the SAME rule via a batch form that `isCompleted()` itself delegates to, so the singular and the list cannot drift. ⚠️ **The N+1 was part of the fix**: one query for the whole set, pinned by a count assertion — which was itself vacuous on the first attempt (stays that are PAST short-circuit before any query) and was rebuilt on REVERSED stays so only the history branch can answer |
| **F-56** | 🟡 LOW | platform · operator | **A member could be stripped of their last role and left holding nothing.** 🔬 `RoleAssignmentService::assertNotLastRoleManager()` asks whether the ORGANISATION keeps someone able to manage access; **nothing asked whether the TARGET keeps anything**. So an admin could remove a colleague's only role and leave them an ACTIVE member of the organisation with no role at all — a person who signs in, is refused `/app` (an allow-list, so no role fails closed) and refused `/owner` too. ⚠️ **FIX 40 built exactly that person on purpose** to reach the 403 copy F-50 rewrote, which is how the state came to notice. | FIX 41, 2026-09-19 | ✅ **CLOSED (FIX 41) — RULED 2026-09-19.** A member always holds at least one role; removing access is removing the MEMBERSHIP, and 🔬 the refusal NAMES that way out rather than being a wall. ⚠️ **ONE REMOVER, AND IT IS GUARDED** — 🔬 measured from vendor source, not method names: spatie's `assignRole()` is `attach()`-only while `removeRole()`/`syncRoles()` detach, and `revoke()` is the ONLY application-level caller in `app/` or `routes/` (the seeder's `syncRoles()` assigns a first role at build time). A test pins that structural fact, so a second remover cannot appear unnoticed. ⚠️ **The refused reader is STILL REACHABLE another way** — `OwnerTeamInviteService` creates an active membership at INVITE time and assigns a role only on ACCEPTANCE — so F-50's copy still addresses somebody |
| **F-57** | 🟠 MED | operator · owner · guest | **A property scheduled for removal keeps selling, and is archived over a live stay.** 🔬 MEASURED, not read: `PropertyRemovalService::schedule()` sets `removal_effective_at` to the last checkout among stays that exist **at that moment**, and 🔬 a sweep of `app/`, `routes/` and `resources/` finds NOTHING else that reads `removal_scheduled_at` or `removal_effective_at` — only the model's casts and one operator display. So the storefront keeps selling, a booking made AFTER scheduling can check out beyond the effective date, and `processDue()` archives on the stale answer. ⚠️ **Executed:** a confirmed stay 20 days out survives the archive, at a property that no longer exists. ⚠️ **This was why FIX 43's notice said nothing about upcoming stays** — it could not have been honest about them. ⚠️ **FIX 44 removes that constraint; the copy is UNCHANGED because it is unruled — see the report.** | FIX 43, 2026-09-19 | ✅ **CLOSED (FIX 44) — RULED 2026-09-19: RE-DERIVE AT EXECUTION.** `processDue()` now recomputes the date from the stays AS THEY STAND and DEFERS rather than archives when one reaches beyond it — the operator is never blocked, and no guest is archived over. ⚠️ **ONE DEFINITION:** `schedule()`'s own rule was EXTRACTED, not rewritten, so both moments ask the same question (🔬 a test drives BOTH paths and requires the same answer). ⚠️⚠️ **THE ACCEPTED CONSEQUENCE, WRITTEN DOWN SO IT IS NOT REDISCOVERED: A PROPERTY THAT KEEPS TAKING BOOKINGS NOW NEVER ARCHIVES** — each run pushes the date out. The founder weighed that against blocking an operator from selling a property they had scheduled for removal. ⚠️ **The deferral is AUDITED** (`property.removal_deferred`, actor `system`, both dates and the reason) because nobody pressed anything, and it **writes the new date forward** so the operator's existing Settings sentence stays true. ⚠️ **The defect-pin that recorded this was DELETED, not inverted**, as its own docblock instructed |
| **F-58** | 🟡 LOW | operator | **A pending removal is visible only if you go and look at that one property.** 🔬 MEASURED, and ⚠️ **NOT the "nothing shows it" the brief expected**: `removal_effective_at` IS read and rendered — `OperatorSettings` builds a removal panel and `operator-settings.blade.php` prints *"Removal is scheduled — the property stays live until the last checkout on **X**. You can cancel any time before it runs."* with a cancel control beside it. ⚠️ **But it is PULL-ONLY AND PER-PROPERTY**: there is no list of pending removals across a portfolio, and no notification when one DEFERS. Before FIX 44 that mattered less, because a scheduled removal always completed; now one can slide indefinitely and the only way to learn is to revisit that property's Settings page. ⚠️ **The audit trail can answer it** (`property.removal_deferred`, actor `system`), so nothing is unknowable — it is simply not surfaced. | FIX 44, 2026-09-19 | ⚠️⚠️ **ACCEPTED BY RULING (2026-09-19) — PER-PROPERTY VISIBILITY IS ENOUGH.** No portfolio list and no deferral notification were built. ⚠️ **What the ruling rests on is now PINNED**: 🔬 nothing asserted that the removal panel actually RENDERS — FIX 44 pinned the date being rewritten, a different claim — so `OperatorPropertySettingsGate2Test` now asserts the sentence, the date and the cancel control on the rendered page. **If that stopped showing, per-property visibility would be zero visibility and the ruling would rest on nothing.** ⚠️ Named for the DECISION, because this is the kind a later pass reads as a gap and helpfully fills |
| **F-42** | 🟡 LOW | owner | **The owner Balance page says "every euro" to a GBP owner.** 🖥 `resources/views/livewire/owner/balance.blade.php:17` hardcodes the subtitle *"What's cleared, what's held, and where every euro is on its way to."* Tenant two's owner is paid in **GBP** and the page's own figures render **£172.80** correctly — only the prose is wrong. ⚠️ **F-38's class in words rather than a symbol**, and the only instance found: a sweep of the owner and operator views for hardcoded currency NOUNS returned this one. ⚠️ Lower severity than F-38 because no figure is mislabelled — but it is on the page an owner reads to find out what they are owed. | FIX 21, 2026-09-14 | ✅ **CLOSED — FIX 22, 2026-09-14.** Reworded to *"where every payment is on its way to"* — **neutral, not "every pound"**: a static subtitle cannot cheaply carry a variable, and neutral prose is correct in every currency. A prose guard now holds it |
| **F-41** | 🟠 MED | platform | **`AuditOAuthTokenGranted` has no idempotence guard, and was being double-registered.** 🔬 Dispatching a single `AccessTokenCreated` in a rolled-back transaction wrote **TWO** `owner.connected_app_token_issued` audit rows. Every other listener on the duplicated bus survived only because each carries its own guard (a per-event static, `firstOrCreate`, or an idempotency key); this one carries none — it calls `audit->record()` unconditionally. ⚠️ **The duplicate registration is fixed (F-40), so it now writes one row** — but the listener is still idempotent only by virtue of being registered once, which is **idempotence by luck, not by design**: a redelivered event or any future second registration double-writes again. ⚠️ **Latent in the demo** (zero tokens issued), live on the first real one. | FIX 19, 2026-09-14 | ✅ **CLOSED — FIX 23, 2026-09-14.** A per-process `static $processed` keyed on `tokenId` — the shape 4 of its 7 domain-bus siblings use, not a seventh invented. 🔬 **Proven by REPLAY**: two dispatches of one grant → **1 row**; guard removed → **2 rows**. A second, different token still audits |
| **F-39** | 🟠 MED | operator | **The refund screen's irreversibility warning sits BELOW the button that issues the refund.** 🖥 MEASURED at 375×812: the one-click "Refund the remaining £270.00" button is at y=1352 and the warning — *"It can't be reversed. A refund reverses a payment, not the charges…"* — is at **y=2492 on a 2918px page**, off-screen entirely. At 1280×900 the warning is on-screen (y=647) but **still below that button** (y=488). ⚠️ Phase 7 called this the best disclosure in the product and FIX 01 and FIX 12 copied it; its *wording* is exemplary and its *position* undoes it on the one screen that moves money irreversibly. ⚠️ The second, amount-aware form ("Review & issue refund", y=1826/906) DOES sit after the warning — only the one-click path does not. | FIX 18, 2026-09-14 | ✅ **CLOSED — FIX 23, 2026-09-14.** The callout MOVED (not copied) from the right rail into the refund card, above the payment list — so it precedes every button on BOTH paths. 🖥 375px: warning 2152→**978**, buttons 1247/1606. Wording byte-identical. ⚠️ **The brief's premise was WRONG** — see §2c |
| **F-40** | 🟠 MED | platform | **Every domain-event listener is registered TWICE, and the second set is in a different order.** 🔬 The dispatcher holds 14 entries for `DomainEventRecorded`: 7 explicit `Event::listen` calls from `AppServiceProvider` in the documented money-first order, and 7 more discovered by Laravel as `Class@handle` — in which `DispatchNotificationsForDomainEvent` (best-effort) precedes `AdjustOwnerLedgerOnRefund` (money). **So every domain event runs every side effect twice.** ⚠️ **No money is lost today**: the explicit money listener runs first and each listener is idempotent (a per-event static guard plus `firstOrCreate` on the ledger key). But the documented ordering — the fix made after an owner debit was once lost behind a best-effort listener — describes only HALF of what is registered, and correctness now rests on the idempotency guards rather than on the order. 🔬 Found by mutation: deleting the explicit registration changed nothing. | FIX 18, 2026-09-14 | ✅ **CLOSED (FIX 19)** — Laravel 13 ships `$shouldDiscoverEvents = true`, so discovery registered a second copy of every listener. Turned off after enumerating what it provided (**discovered-only: 0**). 🔬 14 → 7 registrations, the documented order is now the resolved order, and the ordering mutation reddens again. ⚠️ **The ordering fix had been half-effective since the day it shipped** |
| **F-38** | 🟠 MED | operator | **The rates grid labels every per-unit price in EUR, whatever the property's currency is.** 🔬 `RatesGrid::unitRows()` calls `$this->money($minor, 'EUR')` — a hardcoded literal — while the room-type row two hundred lines up correctly passes `$rate['currency']`. On the GBP tenant every per-unit nightly price renders as **€90.00** when the stored row is **GBP 9000**. ⚠️ **The amount is right and the label is wrong**, which is the dangerous direction: an operator reading €90 on a £90 room has no arithmetic cue that anything is off. ⚠️ **NOT a cross-tenant leak** — the figure is the tenant's own; it was invisible for the whole programme only because every property was EUR. | FIX 17, 2026-09-14 | ✅ **CLOSED — FIX 22, 2026-09-14.** The resolver now answers price AND currency together (`NightlyRate::forUnitWithCurrency`), so the symbol follows the same override→type precedence as the figure. 🔬 Mutation-proven in BOTH currencies |
| **F-37** | 🟠 MED | operator | **The season APPLY dialog is clamped inside the `.vy-fade` card — FIX 02 lifted two of three dialogs out, not three.** 🖥 MEASURED (FIX 15): its overlay is a containing-block child of `div.vy-card.vy-fade` (`transform: matrix(1,0,0,1,0,0)`), so `position: fixed` resolves to the CARD, not the viewport. At **375×812 the panel lands at y=755 and runs to 1069 — off the bottom of the screen**; at 1280×900 the panel is visible but the overlay is 988×381 at (261,204) instead of covering the viewport, so the backdrop does not catch clicks outside the card. ⚠️ `editSeason` and `deleteSeason` threw `ReferenceError` and were moved out; `confirmSeason` never threw, so nothing pointed at it. ⚠️⚠️ **This is the APPLY dialog — the one that writes money.** The fix is to move it beside the other two, inside the same plain wrapper. | FIX 15, 2026-09-14 | ✅ **CLOSED (FIX 16)** — lifted out of the card, beside the two siblings FIX 02 moved. 🔬 After: overlay **375×812 @(0,0)** at every scroll offset (was 341×855 @(17,284)); panel y=20, fully visible; D2 green; the agreed-rate clause proven to FIRE and render. ⚠️ **Corrected shape: the off-screen panel is INTERMITTENT** (scroll- and content-dependent — 49px below the fold at one offset, fully visible at others); **the broken backdrop was CONSTANT**. A test asserting only "panel in viewport" would itself be flaky |
| **F-35** | 🟡 LOW | tests | **The suite is non-hermetic.** 🔬 `Http::preventStrayRequests()` occurs **0 times** in `tests/` and `app/`. A suite that *could* reach the internet is non-hermetic whether or not it currently does. ⚠️ **This is a hardening item and was NOT the cause of anything observed** — see the process finding in §6. | the suite investigation, 2026-09-13 | ✅ **CLOSED (FIX 47) — RULED 2026-09-19: enable it.** `Http::preventStrayRequests()` now runs in `Tests\TestCase::setUp()`. ⚠️⚠️ **THE CLOSURE IS "THE `Http` FACADE IS GUARDED", NOT "THE SUITE MAKES NO OUTBOUND REQUESTS"** — the second is a bigger claim and this pass did not measure it. 🔬 **What it CANNOT see, enumerated:** **Stripe**, whose SDK transports through its own `CurlClient` (held instead by the `StripeClientInterface` seam + `FakeStripeClient` + `StripeSafetyGuard`) · a raw `curl_*` call or a directly constructed `GuzzleHttp\Client` (🔬 **neither appears in `app/` today**) · anything a queued job does in another process. ⚠️ **PROVEN ON, not assumed:** an unfaked request throws `StrayRequestException`, and with the line removed **the same request went through** — so the suite genuinely could reach the internet. ⚠️⚠️ **ZERO tests reddened, and that is a finding rather than an anticlimax:** all three facade users (`ChannexClient`, `AnthropicLlmClient`, `GoogleIdentityProvider`) already sit behind an interface seam bound to a fake, so nothing strayed — **but nothing GUARANTEED that.** A fourth facade user without a seam, or one test binding a real client, would have gone straight out. The guard makes it structural |
| **F-59** | 🟠 MED | programme | ⚠️⚠️ **THE "17 MONEY-WRITE ROUTES" DENOMINATOR IS ENUMERATED NOWHERE, SO THE COVERAGE CLAIM BUILT ON IT CANNOT BE CHECKED.** 🔬 §5 says fourteen remain unexecuted; briefs have said ten; a router sweep finds **54** write routes touching money-adjacent concepts — ⚠️⚠️ **AND THAT 54 WAS ITSELF RETIRED BY FIX 56: 🔬 the same rule measured at four call-graph depths gives 3 / 47 / 163 / 190, so 54 is one arbitrary point on a curve, not a measurement.** **No list of the seventeen exists in any document**, so neither remainder is derivable and the set's membership rule was never written down. ⚠️ **A denominator nobody can reconstruct cannot be audited.** ⚠️ FIX 48 removed every `N of M` claim and replaced it with the RULE plus the enumerated list of what HAS been driven — the false arithmetic is gone, but the set itself is still unenumerated. | FINAL VERIFICATION **V-3**, 2026-09-19 | ✅ **ACCEPTED BY RULING (2026-09-20, FIX 57) — THE DENOMINATOR IS NOT MECHANICALLY DERIVABLE, SO NO GUARD IS POSSIBLE AND NO HONEST PERCENTAGE EXISTS.** ⚠️ **THE MEMBERSHIP RULE, STATED — because three plausible definitions give three different answers and the project had been quoting a number without ever fixing which:** *a money-write route is one whose handler can, on a successful request, cause an insert or update to a row carrying a `*_minor` column.* 🔬 **MEASURED (FIX 56): 513 route entries · 296 POST/PUT/PATCH/DELETE · 14 unresolvable to a `controller@method`.** ⚠️⚠️ **AND THEN THE MEASUREMENT THAT DECIDED IT — THE SAME RULE, AT FOUR TRAVERSAL DEPTHS: 3 / 47 / 163 / 190.** A script must choose how far to follow the call graph, and the answer swings **63×** across plausible choices; the three candidate rules (writes a money table · writes a `*_minor` figure · reaches a payment processor) **stop distinguishing each other at depth ≥ 2**, converging on ~190 because the closure saturates. ⚠️ No depth is principled: depth 0 misses every controller that delegates to a service — which is this project's entire architecture (*events are recorded INSIDE the service; no controller calls a recorder*) — and depth 3 pulls in most of `app/`. ⚠️⚠️ **AND "MECHANICAL WITH AN ALLOW-LIST" IS NOT AN ESCAPE: at depth 2 the exception list would run to ~160 entries, which is a judgement rule wearing a script's clothes.** ⚠️⚠️ **BOTH HALVES OF THE FRACTION ARE UNENUMERABLE, NOT ONLY THE DENOMINATOR.** 🔬 The numerator is **five individually-named driven routes** — the refund (FIX 18) · the season apply (FIX 20) · statement finalization (FIX 21) · the reservation create in tenant three (FIX 50, ref `R-BSXAZEB0`) · the confirm route (FIX 54) — **plus two clusters of unstated size** (FIX 27, FIX 28) whose membership was never written down. ⚠️ **NOTHING IS PINNED: no enumeration, no depth, no count.** A derivation pinned before it is agreed is a faster way to entrench a guess, and this one is agreed to be unknowable. ⚠️ §5 already states the RULE rather than a fraction, and **`KNOWN-LIMITS.md` L11** now carries the consequence where a shipper meets it — the person answering a customer's security questionnaire must not invent a number. |
| **F-60** | 🟠 MED | operator | ⚠️⚠️ **TWO RESERVATION-LADDER TRANSITIONS SHIPPED A DEFAULT NOBODY HAD DECIDED. BOTH ARE NOW RULED.** 🔬 As recorded, `STATUS-LADDERS.md` carried two transitions with **no ruling marker**, neither appearing in §4's answers table, while the programme's headline claimed every ruling was answered. **R2** (`no_show → checked_in`) is ✅ **RULED (2026-09-19) as an extension of R1** — see the status column. The second of them, the `enquiry`/`tentative` → `checked_in` question, was ruled 2026-09-20 and BUILT — see the status column. ⚠️ **An unruled transition is not neutral: the product is already shipping one of the options.** Both are **refused** today; the no-show case is the commonest real-world one — a guest arriving at 01:00 after the night audit. | FINAL VERIFICATION **V-4**, 2026-09-19 | ✅ **CLOSED (FIX 54) — BOTH HALVES NOW RULED AND BUILT.** ⚠️ **R2 was ruled 2026-09-19: REFUSE, as an EXTENSION of R1** — the reasoning behind R1 was always an availability argument and 🔬 it is literally true of a no-show, so the extension was a text change, not a behaviour change. FIX 49 amended R1's ruled text on all four surfaces, **split the shared refusal message** so the `no_show` arm names the route, and pinned it — ⚠️ **the route was DRIVEN before it was named**: 🔬 reschedule REFUSES a released line (*"Only an active reservation line can be rescheduled"*), a **new reservation SUCCEEDS** and takes `availability.sold` back 0 → 2, and the cost is measured (the no-show keeps its folio — a 5,000-minor fee survived — and the guest ends with two reservations). ⚠️ **The second transition was left exactly as it was for one more pass, generic message included.** ⚠️⚠️ **FIX 51 FOUND OUT WHY IT COULD NOT SIMPLY BE RULED THE WAY THE FIRST ONE WAS: THE PROPOSED ANSWER'S PREMISE WAS FALSE.** The ruling put was *refuse, and name the confirm-then-check-in route* — 🔬 **there is no confirm route.** Driven and enumerated rather than reasoned: **sixteen** operator POST routes touch a reservation and **not one promotes it to `confirmed`**; the twelve routes in the whole application matching confirm/promote/accept/activate are booking-confirmation PAGES, invite accepts, an admin reactivate, a guest-request confirm, a rate-proposal counter, two 2FA confirms, a waitlist reconfirm, an owner deactivate and an unsubscribe — **none of them a reservation**. 🔬 No service method promotes one either; `confirmed` is written only at CREATION. ⚠️ **AND THE TWO STATUSES ARE NOT ALIKE, which is why they were driven separately:** 🔬 `tentative` is produced by exactly ONE path (a group booking held tentatively) and **holds inventory** — line `active`, `availability.sold` 2 over the stay — so unlike a no-show **nothing has been released**; while 🔬 **`enquiry` is written by NOTHING in the application at all** — it exists in the enum and in display labels, and no code path creates it. ⚠️⚠️ **So naming a confirm route would have shipped the very dead end the no-show measurement caught last pass, and half the ruling concerns a state the product cannot produce. THE RULING NEEDED RE-PUTTING, NOT PATCHING.** ⚠️⚠️ **IT WAS RE-PUT AND ANSWERED 2026-09-20, AND FIX 54 BUILT WHAT IT ASKED FOR: ✅ CLOSED.** The refusal is unchanged; what was missing was **anywhere to go**, so the confirm route now exists — `tentative → confirmed`, gated `reservations.manage` (⚠️ **no permission was minted; the catalogue held at 51**), reachable from a **Confirm hold** control on the reservation. ⚠️⚠️ **THE DANGER WAS A DOUBLE-COUNT AND IT IS PINNED FOUR WAYS:** 🔬 `availability.sold` read at create, after create, after confirm and after check-in read **0 → 2 → 2 → 2** — the hold is taken once and CONVERTED, never taken twice. 🖥 Driven end to end in tenant three (Harbourpoint present, Northgate and Seaside absent). ⚠️ `enquiry` deliberately gets **no route and no copy**: 🔬 nothing in the application writes that status, so its refusal guards a path nothing can reach — **do not "fix" the missing enquiry flow.** ⚠️ **No inverse was built** — an undo that strands side effects is what produced F-53/F-54, and a second quarantined undo is not wanted. |
| **F-61** | 🟠 MED | instrument | ⚠️⚠️ **THE MONEY GATE AND A WALKABLE TENANT TWO ARE MUTUALLY EXCLUSIVE, AND THE COLLISION IS STRUCTURAL.** 🔬 `mode=FULL` requires seeding at `VY_SEED_DATE=2026-09-01`; tenant two is seeded with a 14-day trial, which therefore expires **2026-09-15**; at any real present date `EnsureSubscriptionActive` correctly holds its whole `/app/*` back office at the paywall. ⚠️ **Neither gate is wrong — invariant #41 is doing its job** — but the instrument that proves money now disables the instrument that proves cross-tenant operator behaviour, and **the writable tenant is the only one that may be written to**. ⚠️⚠️ **DO NOT "FIX" THIS BY EXTENDING THE TRIAL, MOVING THE CLOCK, OR RE-SEEDING AT A LATER DATE** — that is re-baselining a fixture to make a check pass. FIX 48 **disclosed** it (the gate now prints it, `KNOWN-LIMITS.md` **L8**) and repaired nothing. | FINAL VERIFICATION **V-5**, 2026-09-19 | ✅ **CLOSED (FIX 50) — RULED 2026-09-19: BUILD THE DISPOSABLE THIRD TENANT.** `harbourpoint-demo-walkthrough` (EUR, Portugal) is provisioned through the certified `TenantProvisioningService` and then moved to the state a PAYING customer occupies. ⚠️⚠️ **CLOSED BECAUSE THE JOURNEY ACTUALLY RAN, NOT BECAUSE THE TENANT EXISTS** — a tenant nobody has driven has closed nothing, it has added a fixture. 🖥 Signed in as its founder, confirmed the tenant by content (Harbourpoint present, Northgate and Seaside **absent**), and took a reservation through the seven-step wizard: 🔬 **`reservations` 0 → 1** · row `01m2y1ef3h6zt21pws2hj7h1eg`, ref `R-BSXAZEB0`, `confirmed`, direct, 2026-09-23→25, EUR **24600** · `reservation_units` 0 → 1 on unit **H201** · `availability.sold` 0 → **2**. ⚠️ The €246.00 the page computed is exactly 2 × the seeded €123.00 — **no number was typed into a page**. ⚠️ **AND THE RUN CARRIED ITS POSITIVE CONTROL** (INSTRUMENT-ERRORS class **N**): in the same session tenant two's founder was signed in and `/app/reservations` **redirected to `/app/billing`** — so the instrument demonstrably distinguishes a write that must succeed from one that must refuse, and invariant #41 and **L8** are both intact. ⚠️⚠️ **WHY `active` AND NOT A LONGER TRIAL — MEASURED:** `hasFullAccess()` is `onTrial()` OR `active()`; `onTrial()` is date-dependent and is the exact fuse that paywalled tenant two, while `active()` **checks no date at all** and 🔬 `current_period_end` gates nothing anywhere in `app/`. Any trial length is just a later fuse; `active` cannot expire. ⚠️ Not new ground either — 🔬 `vyrobel-demo` already carries `status='active'` with `stripe_subscription_id` NULL. **Zero Stripe.** ⚠️⚠️ **NO PINNED FIGURE MAY EVER REFERENCE IT** — `KNOWN-LIMITS.md` **L9** and the seeder docblock carry the same sentence; it is destroyed by every reseed by design. ⚠️ **THE RBAC FIGURES MOVED AND THAT IS CORRECT:** 🔬 both-tenant `role_has_permissions/permissions/roles/model_has_roles` **469/51/27/16 → 678/51/40/17** (+209 role-permission rows, +13 roles, +1 membership — the new org's catalogue). ⚠️⚠️ **THE PERMISSION COUNT HELD AT 51**, which is the check that matters: permissions are global definitions, so a move there would have meant one was invented. 🔬 **Tenant one's own figures did NOT move by one — 260/51/14/14 exact — and the money gate ran `mode=FULL` with all six unchanged**, which is the proof the third tenant did not leak into the baseline. |
| **F-62** | 🟡 LOW | instrument | ⚠️ **THE MONEY GATE COMPARES COUNTS AND SUMS, SO AN EQUAL-AND-OPPOSITE PAIR OF ERRORS NETS TO ZERO AND PASSES.** A second, independent blindness from the tenant-one scoping already on the record: the gate asserts six aggregates, and two compensating movements inside one of them are invisible to it. ⚠️ It is **not** a claim that this has happened — it is a claim about what a green gate can and cannot mean. FIX 48 recorded it **in the gate's own header and output**, where the person who would be misled is actually reading. | FINAL VERIFICATION §5 guard table, 2026-09-19 | ✅ **CLOSED (FIX 57) — A ROW DIGEST OVER FOUR MONEY TABLES, AND IT CATCHES WHAT THE AGGREGATES CANNOT.** ⚠️⚠️ **MEASURED BEFORE IT WAS BUILT (FIX 56), WHICH IS WHY IT WAS BUILDABLE AT ALL.** The decisive question was never *how many rows* but **which columns survive a reseed** — 🔬 two `migrate:fresh --seed` runs at the reference date: `rate_calendar` **5/8** stable (`date, price_minor, currency, created_at, updated_at`), `reservations` **28/33**, `payments` **14/20**; ⚠️ the volatile set is exactly the ULIDs plus generated references (`id`, `organization_id`, `property_id`, `rate_plan_id`, `folio_id`, `reservation_id`, `lead_guest_id`, `reference`, `gateway_ref`, `metadata`). ⚠️ `created_at`/`updated_at` **are** stable, because the seeder runs at a frozen date. ⚠️ `unit_rate_overrides` and `rate_seasons` hold **0 rows** in tenant one, so a digest there is a constant and would be ceremony — the count already catches a row appearing. 🔬 **FOUR NATURAL KEYS, EACH RE-PROVEN THIS PASS UNIQUE AND RESEED-STABLE:** `rate_calendar` 929/929 · `reservations` 42/42 · `payments` 16/16 · **`owner_ledger` 29/29**, all with zero null keys and all four digests byte-identical across two reseeds. ⚠️⚠️ **`owner_ledger` IS IN SCOPE BECAUSE THE MOTIVATING FAILURE LIVES THERE.** A credit moved from one owner to another is the example this row exists for, and **no version of the gate had ever read that table**; its only other instrument is the suite pin, which is count-plus-sum and blind to exactly the same swap. ⚠️ **A digest over the other three would have closed a class that does not contain its own motivating example.** ⚠️⚠️ **DEMONSTRATED THREE WAYS, ALL IN TENANT THREE OR A CONSTRUCTED FIXTURE — TENANT ONE WAS NEVER WRITTEN TO, not even inside a transaction:** 🔬 (1) a **credit moved between two owners** left `count=6 / SUM=150600` untouched and the old gate printed **`mode=FULL`, exit 0**, while the new gate **failed, exit 1**, naming the class; 🔬 (2) a **+1000 / −1000 pair** left the six aggregates byte-identical at `180 / 2214000 / 0 / 0 / 0 / 0` and moved the `rate_calendar` digest; 🔬 (3) a **pure swap of two amounts, no arithmetic**, left count and sum unchanged by construction and moved the digest. ⚠️ **A PURE SWAP OF TWO *IDENTICAL* VALUES IS NOT A BLIND SPOT — it leaves the database byte-identical and is a non-event, undetectable by any instrument because nothing changed.** ⚠️⚠️ **THE TRAP THAT ALMOST SHIPPED THE FIX BLIND: MySQL `CONCAT()` RETURNS NULL IF *ANY* ARGUMENT IS NULL.** 🔬 `payments.method` is NULL on all 16 rows, so an un-coalesced key collapsed **all sixteen rows to one key** — a digest that looks like it works and sees nothing. Every component is now `COALESCE`d, and ⚠️ **the gate asserts KEY UNIQUENESS and refuses rather than guessing**: a colliding key merges rows and blinds the digest exactly where it matters. 🔬 That guard fired for real on a first fixture whose two owners held byte-identical ledger lines. ⚠️ **COST: 🔬 the gate went 0.85s → 0.91s per run (+7%), 987 + 29 rows.** A gate that becomes slow is a gate that gets skipped; this one did not. ⚠️⚠️ **WHAT IT STILL DOES NOT SEE, and the success line now says so in the gate's own output: FIVE money tables no instrument reads at all — `folios` (20 rows), `folio_items` (40), `owner_statements` (6), `owner_expenses` (6), `subscriptions` (1)** — plus every other tenant, plus **columns outside each key-and-amount pair** (🔬 `reservations` 28, `payments` 15, `rate_calendar` 3). ⚠️ **The honest claim is "tenant one's four covered money tables are row-identical as seeded", NEVER "the money is unchanged".** ⚠️ And the digest is **date-dependent**, so it is asserted in **FULL mode only**. ⚠️ **TWO INSTRUMENTS NOW READ `owner_ledger` AND THEY CATCH DIFFERENT THINGS** — the suite pin (count + sum, run by `php artisan test`) and the gate (row digest, run by a shell script the suite never runs). **Neither supersedes the other; do not delete one because the other exists.** ⚠️ 🔬 **AND FOR THE NEXT PROBE-WRITER: `price_minor` IS UNSIGNED.** An equal-and-opposite pair is not freely constructible — the negative half is bounded by the row's own value, and a −100000 on a 12300 row fails outright with SQLSTATE 22003. |

| **F-63** | 🟠 MED | programme | ⚠️⚠️ **A RULING IDENTIFIER MEANS THREE DIFFERENT THINGS IN THREE FILES, AND IN ONE OF THEM IT IS RULED.** 🔬 Swept 2026-09-19 while checking whether R2 and R5 were already answered somewhere: `docs/HOUSEKEEPING-LADDER.md` carries its **own** `R1`–`R4` (its `R2` = *"may a cleaned or inspected room go backward?"*, **✅ permitted**, which §4 calls **H2** and ruled SATISFIED under R-HK-11), and `docs/DECISIONS.md` carries a **third**, unrelated `R2`/`R3`/`R5` from a docs-triage text-comparison. ⚠️⚠️ **THE HAZARD IS PRECISE AND IT ALMOST FIRED IN THIS PASS: a reader told to check whether the reservation-ladder R2 is ruled elsewhere finds `R2 ✅ yes` in the housekeeping file and concludes it is answered.** It is the F-32 class running BACKWARDS — there a ruling closed a row it did not name; here a ruling appears to answer a question it has nothing to do with. ⚠️ FIX 48 added a disambiguating header to both ladder files and **renamed nothing**: the identifiers are referenced from ruled behaviour, pinning tests and commit messages, so a rename is a real migration, not a tidy-up. | FIX 48, 2026-09-19 (while following FIX 48's own R2/R5 lead) | ⛔ **OPEN — AND FIX 50 MEASURED IT BEFORE REWRITING ANYTHING, WHICH KILLED THE PROPOSED FIX.** ⚠️⚠️ **THE RULED REMEDY WAS *"an unqualified cross-file numeric R-reference must fail"*. 🔬 COUNTED FIRST: there are **211** bare numeric `R<n>`/`H<n>` references outside the three home files, across **18 live documents** — and **they belong to SEVEN unrelated families, not three.** Beyond the reservation ladder and the housekeeping ladder there are: the **2026-08-07 audit's HIGH findings** (H1–H6, in `DECISIONS.md`, `PROJECT-STATE.md`, `STATE.md`) · **documentation-claim row ids** (R1–R10/H1–H6 in `DOC-BATCH5-VERIFICATION.md`) · **per-audit decision numbers** (R1–R3 in `OPERATOR-RATES-REFUND-AUDIT.md`, R1/R4 in `WAVE4-INVENTORY.md`) · **docs-triage comparison rules** (R2/R3/R5, 70 of them in one reconciliation record) · ⚠️⚠️ **and HTML HEADING LEVELS — `H1` meaning `<h1>` — which is the one that settles it: a rule demanding every cross-file `H1` be qualified would demand it of heading levels, and would become exactly the noise that gets switched off.** 🔬 **Only ONE non-home file genuinely references the ladder at all** (`FINAL-VERIFICATION.md`, 11 references), and its mentions sit inside measurements that may not be edited. ⚠️ **So 200 of the 211 must NOT be qualified, because they are not ladder references — and a mass mechanical rewrite would have been both enormous and wrong.** **NOTHING WAS REWRITTEN.** | ✅ **CLOSED (FIX 51) — THE ENFORCEMENT HALF, BUILT ON THE MEASUREMENT THAT KILLED THE BLANKET RULE.** ⚠️⚠️ FIX 50 made the parser **SAFE** (a bare `R<n>` is read as a ladder ruling only inside its home files) — but safe is not enforced: a document anywhere else asserting a ladder ruling's state still passed **in silence**, and that sentence is the entire risk this row names. 🔬 **THE SPLIT THAT MADE IT TRACTABLE: of the 211 cross-file references, only THREE make a RULING-STATE CLAIM** — and a claim is the whole risk, so the rule enforces on claims, not on mentions. **A cross-file ruling-state claim must name which ladder it means** (`the reservation-ladder R2`), reusing the convention §4 already applies when it calls the housekeeping one **H2**; numeric ids only, since the named rulings are globally unique and forcing them would be noise. ⚠️⚠️ **MUTATION-PROVED BOTH WAYS:** the exact risky sentence planted in a document under `docs/` turns it RED naming file, line, id and state; the qualified form **passes** — the rule is satisfiable, not a trap. ⚠️ 🔬 **IT SHIPS WITH AN EMPTY EXCEPTION LIST** — every cross-file ruling claim already in the record is qualified by its own sentence, so there was nothing to excuse. ⚠️ **What is NOT claimed:** the identifiers still collide, by ruling — *guard it, do not rename*. A reader grepping `R2` still meets three families; what can no longer happen silently is the RECORD asserting a state without saying whose. |
| **F-64** | 🔴 HIGH | guest | ⚠️⚠️ **A TENTATIVE HOLD EMAILED THE GUEST "BOOKING CONFIRMED" — THE PRODUCT STATED A FALSEHOOD TO A CUSTOMER.** ⚠️ Every other finding in this register is the product being wrong to its OPERATOR or to its own RECORDS. This one is the product being wrong **to a paying member of the public**, in writing, over email, in its own branding. A guest on an unconfirmed group hold — a hold that carries a **pickup deadline**, i.e. one that expires unless someone acts — received a mail headed *booking confirmed*, carrying the stay dates and the total, for a room nobody had promised them. A guest who acts on it books a flight. 🔬 **MEASURED BEFORE ANYTHING WAS CHANGED, by a test written to assert the correct behaviour and run first: a tentative hold sent `BookingConfirmationMail` ONCE (expected zero), and left ONE `SendBookingConfirmationEmail` job on the queue.** 🔬 **THE CHAIN, read end to end:** `AssignmentService::createGroupBooking()` records `reservation.confirmed` once per persisted member **including tentative ones** — deliberately and correctly, because a hold occupies inventory and must sync to channels. `SendGuestTransactionalEmails::bookingConfirmation()` then checked the per-org template toggle, the wizard's suppression flag and `leadGuest !== null` — **and never the reservation's status.** And `GroupBookingService::create()` gives every member a `lead_guest_id` (the organiser, when no per-member guest is named), so **the one guest-side gate that existed was always satisfied for a hold.** The `SendBookingConfirmationEmail` job then re-read the reservation and sent unconditionally. ⚠️⚠️ **THE EVENT IS NOT THE DEFECT AND MUST NOT BE REMOVED.** 🔬 `reservation.confirmed` at hold time drives **three** listeners and only one of them was wrong: `EnqueueAriPushOnInventoryChange` (the channels must learn the room is gone — removing it oversells) and `DispatchNotificationsForDomainEvent` (the front desk must learn a hold arrived) are both correct. The guest email was the single wrong consumer. | ⚠️⚠️ **PRE-EXISTING.** Found by FIX 54 while building the confirm route, **neither caused nor compounded by it** — the confirm action deliberately recorded a distinct `reservation.hold_confirmed` event precisely to avoid firing this send a second time, and reported the underlying defect rather than fixing it inside a pass that was measuring something else. Entered as a row 2026-09-20, **before** it was repaired. | ✅ **CLOSED (FIX 55) — GATED AT DISPATCH, NOT INSIDE THE JOB, AND THE DIFFERENCE IS THE WHOLE FIX.** ⚠️⚠️ **THE OBVIOUS REPAIR IS RACY AND THE WINDOW IS SIXTY SECONDS WIDE.** 🔬 The listener dispatches with `delay(now()->addSeconds(60))`. Teaching the JOB to re-read the status and return early gives: hold at T+0 → job queued for T+60 → an operator confirms at T+10 → at T+60 the job re-reads `confirmed` and sends, **and the confirm has already sent its own.** Two emails, out of a check whose entire purpose was to prevent one. ⚠️⚠️ **THE SHIPPED FIX IS IMMUNE TO ORDERING BECAUSE THE DECISION IS TAKEN SYNCHRONOUSLY AT THE MOMENT THE EVENT IS RECORDED — READING THE STATUS THE VERY SAME COMMITTED TRANSACTION JUST WROTE — SO A HELD BOOKING NEVER PUTS A JOB ON THE QUEUE AT ALL, AND THERE IS NO PENDING SEND FOR ANY LATER CONFIRM TO RACE WITH.** ⚠️ That is asserted, not argued: the suite proves the queue is **empty** after a hold. A race needs two contenders and there is only ever one. ⚠️ **WHERE THE MAIL NOW COMES FROM:** the listener additionally handles `reservation.hold_confirmed`, the event FIX 54's confirm action already recorded — so the guest is told exactly when it becomes **true**, and the ordinary journeys are untouched. 🔬 Pinned by counts, in one run: hold **0** · confirm **exactly 1** · check-in **0** · **ordinary direct booking still exactly 1** · a group created `confirmed` still one per member (**2 of 2**). ⚠️ 🔬 **THE PRODUCERS WERE ENUMERATED, not assumed:** `createBooking()` defaults to `'status' => 'confirmed'` and **neither** of its two callers overrides it — `ReservationCreateController` (the operator wizard) and `BookingEngineService` (the public engine) — so the product's most-used email was verified unchanged by count, before and after. ⚠️ **NOT BUILT, deliberately: a hold-notification email.** Telling a guest *we are holding a room for you until Friday* is a new guest-facing message with its own copy, its own template toggle and its own preference question. It is a product decision, not a repair, and inventing one inside a defect fix is how a fix becomes a feature nobody ruled. |
| **F-65** | 🔴 HIGH | platform · money | ⚠️⚠️ **THE NO-DOUBLE-BOOKING GUARANTEE DEPENDED ON A HOSTING PROVIDER'S SERVER DEFAULT THAT NOTHING IN THIS REPOSITORY CHOSE, RECORDED OR ASSERTED.** ⚠️ The guarantee is `AssignmentService` holding `SELECT … FOR UPDATE` on `availability`. 🔬 What that statement DOES differs by engine **and by isolation level**: MariaDB 11.6+ ships `innodb_snapshot_isolation=ON`, and at **REPEATABLE READ** a competing `FOR UPDATE` does not block-then-reject — it aborts with **error 1020**. The booking path then surfaces a raw database error instead of refusing gracefully, and 🔬 `DeadlockAvoidanceTest` showed a **legitimate** concurrent booking being lost to it. ⚠️⚠️ **PRODUCTION WAS SAFE ONLY BY ACCIDENT:** Hostinger's global default happens to be READ COMMITTED. The application set no isolation level at all. A provider could have changed that in a maintenance window, with no deploy, no commit and no alarm. 🔬 **THE FULL MATRIX, MEASURED 2026-09-20 — same commit, same seed, full suite each time:** MySQL 8.4.10 @ REPEATABLE READ **3,651 pass** (the historical control) · MySQL 8.4.10 @ READ COMMITTED **3,651 pass** ⚠️ *the cell nobody had ever run* · MariaDB 11.8.9 @ REPEATABLE READ **concurrency FAILS, error 1020** · MariaDB 11.8.9 @ READ COMMITTED **3,648 pass** (3 failures separately explained). ⚠️⚠️ **THIS IS A NEW INSTANCE OF A CLASS THIS PROJECT ALREADY KNOWS — *a behaviour nothing asserts is not a guarantee* — AND THE FIRST IN WHICH THE UNPINNED THING BELONGED TO A THIRD PARTY.** | DEPLOY 02, 2026-09-20 (found while verifying the product on MariaDB) | ✅ **CLOSED (DEPLOY 03) — THE APPLICATION NOW STATES ITS OWN ISOLATION LEVEL, AND IT IS PINNED.** ⚠️⚠️ **READ COMMITTED WAS CHOSEN ONLY AFTER FILLING THE MISSING CELL** — picking the level that makes MariaDB pass, without running MySQL at that level, would have been the same mistake pointed the other way. 🔬 It is the **only** level correct on **both** engines. ⚠️⚠️ **SET AT CONNECT TIME** via `Mysql::ATTR_INIT_COMMAND` in `config/database.php`, on both the `mysql` and `mariadb` connections — **not** in middleware. 🔬 That distinction is the point: a request-scoped hook would have left the **queue worker** — where payment webhooks fulfil and payouts are written — running on the host default, which is the exact dependency being removed surviving in the one place it matters most. ⚠️ It also re-applies on reconnect, which matters because 🔬 the host drops idle connections after **20 seconds**. 🔬 **PROVEN ON FOUR ENTRY POINTS:** artisan/CLI · **from inside a queued job** · the litespeed **web SAPI** on the live host · and locally **with the server global deliberately set to REPEATABLE READ**, where the session still reported READ COMMITTED — which is what proves the application overrides the host rather than merely agreeing with it. ⚠️ Pinned by `DatabaseIsolationLevelTest` (**mutation-proved red**), which also asserts the level **survives a reconnect**. `KNOWN-LIMITS.md` **L12b**. |
| **F-66** | 🟠 MED | instrument | ⚠️⚠️ **THE SUITE CAN GO RED FOR REASONS UNRELATED TO THE CODE — ORDER-DEPENDENT POLLUTION LIVES IN THE INSTRUMENT EVERY OTHER CLAIM RESTS ON.** 🔬 Observed 2026-09-20: `GuestErasureTest` — *"clears EVERY free text store about the guest — raw DB verified"* — **failed once inside a full-suite run** and then **passed twice in isolation**, and the next full run was green. ⚠️ It is not isolation-level related: the failing and passing runs were at the same level. ⚠️ 🔬 The standing cause is known and deliberate: **`RefreshDatabase` is OFF globally** (`tests/Pest.php`), so the test database PERSISTS between runs and fixtures accumulate. `CLAUDE.md` already warns that every fixture needs unique ids. ⚠️⚠️ **WHY THIS DESERVES A ROW RATHER THAN A SHRUG: the one thing this programme actually produced is an instrument.** A suite that reddens at random teaches the next reader to re-run rather than investigate — ⚠️ **which is exactly how a real failure gets dismissed.** ⚠️ And it landed on a **GDPR erasure** test, where a dismissed red is expensive. | DEPLOY 02/03, 2026-09-20 | ⛔ **OPEN — RECORDED, DELIBERATELY NOT FIXED.** ⚠️ The remedy is a decision with a cost: turning `RefreshDatabase` on globally would change every fixture's assumptions and lengthen the run, and it was switched off on purpose. ⚠️ **Closing this means measuring how many tests actually depend on the persisted database, and what the run costs with it on** — a count-before-build pass, not an edit. ⚠️ Until then: **a random red is a finding, not a flake — re-run it, but then investigate it.** |
| **F-67** | 🔴 HIGH | ops · release | ⚠️⚠️ **`ops/deploy.sh` CANNOT RUN ON THE HOST THIS PRODUCT IS ACTUALLY DEPLOYED TO — AND ITS RECOVERY TRAP HAS NEVER FIRED AND CANNOT.** 🔬 Measured step by step on the live host 2026-09-20. **Four of twelve steps are impossible here**, the first at step 4: `git fetch` → **rc=128, "not a git repository"** — ⚠️ **there is no `.git` on production at all**, the app was placed by **file upload**, so `git pull`, `PREV_SHA` and `--rollback-to` all rest on nothing; `composer install` → **rc=2** under the host's default `php` (**8.3.33** vs the required `^8.4.1`; it is rc=0 in 5.63s under `/opt/alt/php84/usr/bin/php`); `npm ci`/`npm run build` → ⚠️ **`node` and `npm` are NOT INSTALLED** (assets are built elsewhere and uploaded); the cron installer → **`crontab: command not found` (rc=127)**, because the crons are Hostinger-panel-managed. ⚠️⚠️ **AND THE TRAP:** line 127 installs `trap '… php artisan up …' ERR` under the comment *"any failure must still bring the app back up"*, but the script sets `set -euo pipefail` **without `-E`** while wrapping every command in the `run()` **function** — and bash does not inherit an `ERR` trap into a function without `errtrace`. 🔬 **Mutation-proved**: a replica of the exact control flow exits **127 with no trap output**, and the one-character change `set -euo` → `set -Eeuo` makes the same replica print the trap's lines. ⚠️ **The resulting failure mode is the SAFE one** — a failed deploy leaves the app **down** rather than publishing a half-migrated site — ⚠️⚠️ **which is precisely why nobody noticed that the file documents a recovery it does not have.** Same shape as the generated-column index and the stray `_e` table: *a guarantee that reads as installed and is not.* | DEPLOY 06, 2026-09-20 | ✅ **CLOSED 2026-09-20 (DEPLOY 08) — the founder ruled, the layout was measured, and it is now LIVE.** ⚠️ Production serves through a release directory and a **single `current` symlink** one level below the panel-owned `public_html`: 🔬 the swap is **one `mv -T`, 3 ms, 0 non-200 of 220 cache-busted probes**, the new release serves at **t+32 ms**, and pruning the previous release is safe at **+180 s (0/60)** where doing it immediately gave **60/60 HTTP 500**. ⚠️ Three things had to be right and all three were found by measurement: `Options +FollowSymLinks` (without it every route 404s) · Laravel's compiled caches **bake absolute paths** (21 occurrences — the framework 500'd while `/login` still served a correct-looking 503) · and ⚠️⚠️ **the panel cron is hardcoded to `public_html/artisan`, so the migration silently killed the cron that drives the 14-day GDPR erasure** — restored by a shim resolving through the *same* pointer, proven by the cron log turning back into `INFO No scheduled commands are ready to run.` ⚠️ **RESIDUE, NAMED:** the procedure is **documented, not scripted** (L12k), and `ops/deploy.sh` still refuses here by design — ⚠️ **scripting it is a separate decision, and a script that looks runnable and isn't is what this finding was.** ⚠️ **AND THE WIN IS NARROWER THAN IT SOUNDS: code-only deploys are now zero seconds; a SCHEMA CHANGE STILL PARKS, which is where the Stripe exposure now lives (L12j).** ⚠️ **DEPLOY 07 did two things and refused a third.** ⚠️ **(1) The script now REFUSES.** A preflight checks the four blockers and **exits 4 without touching anything** — 🔬 proven on the live host (it names all four) and proven *conditional* by a positive control (locally it passes the preflight and stops at the pre-existing `BACKUP_PASSPHRASE` guard, exit 2). **It is no longer a booby trap that documents a recovery it does not have.** ⚠️ Adding `-E` alone would make things **worse** — it would swap the current safe outcome (stay down) for the dangerous one the comment promises (come up, half-migrated); **the comment is wrong, not the flag.** ⚠️ **(2) The "how" is now MEASURED, not theorised** — a release directory + symlink swap on this host costs **ZERO failed requests** (0 non-200 across 520 probes, three runs), needs **no git remote**, and works with the same file upload used today; ⚠️ its sharp edge is that **pruning the previous release is what breaks the site (60/60 → HTTP 500)**, not the swap. See `docs/KNOWN-LIMITS.md` **L12k**. ⚠️ **(3) NOT ADOPTED** — converting `public_html` to a symlink changes how this product is released and touches a path the hosting panel owns; whether the panel preserves a symlinked docroot **cannot be measured from inside a session**. ⚠️⚠️ **Until the founder rules, `ops/deploy.sh` must not be run against this host — and now it will not run.** See also L12h. |

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
can be above the fold — **F-33 exactly**, which is now ⚠️ **ACCEPTED BY RULING**: the back office is
desktop-only by decision (`KNOWN-LIMITS.md` L7), so this is a stated limit, not a pending fix. The warning now
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

⚠️ **DECIDED 2026-09-19 — the answers are the first table below; NO PASS MAY REVISIT ONE.** Every entry states what ships **today** by
default, the named options, what each costs, and what it releases. ⚠️⚠️ **AN UNDECIDED RULING IS NOT NEUTRAL —
the product is already shipping one of the options.** The "today" column is that option.

🔬 **Re-measured from the source on 2026-09-19 (FIX 31), not copied from the descriptions above.** Where
today's behaviour differs from what an entry used to record, the entry says so.

**⚠️⚠️ THE ROWS ARE THE COUNT — THIS SENTENCE NO LONGER STATES ONE.** It said *"THIRTEEN decisions"* while the section below said *"fifteen"* and its answer table carried a different number again. ⚠️ **Three counts of one section, in the register that spent FIX 42 collapsing three drifted censuses into one.** Removed rather than picked (FIX 45): **count the rows**, which is the rule this document already applies to every other heading. ⚠️ **FIX 41: of the 9 findings this once gated, F-10, F-14, F-23 and F-48 are CLOSED and F-25 and F-49 ACCEPTED BY RULING — F-21, F-24 and F-32 remain.** ⚠️ **FIX 43: F-24 is now CLOSED too — only F-21 and F-32 remain.** (As written: F-10, F-14, F-21, F-23, F-24, F-25, F-32, F-48, F-49) plus
the remainder of F-15 and the last quarter of F-29. **Five cost nothing to build** (they ratify or reverse a
line of config or copy); **eight commit to a build.** ⚠️ **R-PHANTOM moves the most surface** — one answer
settles 17 permissions; **R-RATE** and **R-ADMIN-COPY** each release two findings.

### ⚠️⚠️ THE ANSWERS — DECIDED BY THE FOUNDER, 2026-09-19

**The decisions below were all put and answered on 2026-09-19 — ⚠️ count the rows; this sentence used to say "Fifteen" and disagreed with the preamble and with the table itself (removed FIX 45).** The option tables below are kept deliberately: they
are the record of **what was not chosen**, which is worth as much as what was. ⚠️ **States: DECIDED · SATISFIED
(the code already does it and a test now pins it) · QUEUED (decided, build pending) · PREMISE FAILED.**

| ruling | THE ANSWER (founder, 2026-09-19) | state | pinned by |
|---|---|---|---|
| **R-GATE** | **WIRE IT — GATE BEFORE SCOPE.** | ✅ **BUILT (FIX 35)** — and the ordering is PROVEN, not assumed: a test discriminates the two orderings. | `IdentitySurfacesTest` |
| **R6** | **LEAVE THE OTA AUTHORITATIVE.** The channel keeps the last word over a checked-in stay, including its price. `checked_in` stays out of `TERMINAL_STATUSES`. | **SATISFIED** | `OtaTerminalModificationTest` |
| **R-SUSPEND** | **SUSPENSION CLOSES THE BOOKING PATH.** A suspended organisation stops taking bookings. | ✅ **BUILT (FIX 36)** — ⚠️ **and WIDENED by FIX 39: `suspended` AND `cancelled` (F-51). This row said `suspended` only until FIX 41.** Originally `suspended` only; the guest is never told why; existing stays untouched. | `SuspendedOrgClosesBookingTest` |
| **R-REVERSE** | **ROUTE IT FOR OPERATORS** → ⚠️⚠️ **SUPERSEDED 2026-09-19 AFTER FIX 37's STOP: QUARANTINE IT.** Leave it unrouted and make it refuse to run. | ⛔ **NOT BUILT — QUARANTINED (FIX 38).** ⚠️⚠️ **THE PRECONDITION FOR EVER BUILDING IT: the source payout must reach a TERMINAL status on reversal.** That ONE change does both jobs — it releases the unique `live_statement_key` so `createFromStatement()` can make a fresh payout instead of handing back the paid one (**F-54**), and it makes the existing `status !== 'paid'` check able to refuse a second press (**F-53**). Until then the method throws. | `PayoutReversalIsUnguardedTest` |
| **R-ARCHIVE** | **NOTIFY THE OWNER; KEEP IT IRREVERSIBLE.** No un-archive is built. | ✅ **BUILT (FIX 43)** — the notice fires from `PropertyRemovalService::archive()`, the WRITER, so the scheduled pass inherits it; every owner holding a unit is told SEPARATELY (owners attach per-unit, so one property can have several and a joint message would leak). ⚠️ **No undo, restore or grace period was added.** ⚠️ **It carries NO money figure, deliberately** — 🔬 archiving touches no money at all (`owner_statements` and `payouts` have no property column; `owner_ledger`'s units survive a soft delete), and a figure would go stale the moment it was sent | `ArchivedPropertyTellsItsOwnerTest` |
| **R-RATE** | **SEASONS MAY OVERWRITE AN AGREED RATE**; the operator-side disclosure covers it. ⚠️⚠️ **THE OWNER IS NOT NOTIFIED — F-25 is ACCEPTED BY RULING, not closed.** | **SATISFIED** | `AgreedRateDisclosureTest` |
| **R-PHANTOM** | **KEEP THE SEVENTEEN, DOCUMENT THEM, PIN THE EXACT REGISTER WITH A TEST.** No seeder or role-table churn. | ✅ **BUILT (FIX 41)** — ⚠️ **this cell said QUEUED until FIX 42: FIX 41 built the pin and closed F-14 and left its own answer row unswept, the very class it had just corrected in five other places.** ⚠️ FIX 42 then collapsed the second and third censuses into that one source | `PhantomPermissionRegisterIsPinnedTest` · `PermissionEnforcementContractTest` |
| **R-F10** | **NO ROLE BELOW PROPERTY OWNER SHIPS HOLDING THE THREE** (`billing.manage` · `settings.manage` · `roles.manage`). The seeded default stays tight; grantability through the matrix is how an operator opts in. | ✅ **BUILT (FIX 41)** — ⚠️ **this cell said PREMISE FAILED until FIX 42, the same unswept-consequence class: FIX 41 recorded the ruling, closed F-10 and did not update this row.** ⚠️ Pinned in BOTH directions, because "not seeded" alone is satisfied by deleting them from the catalogue | `SeededDefaultKeepsTheThreeTest` |
| **R-SUPPORT** | **KEEP THREADS ORGANISATION-WIDE.** `assigned_to` stays a hint, never an access filter. | **SATISFIED** | `InboxCoreTest` |
| **R7** | **LEAVE THE POINTER.** `default_rate_plan_id` is not cleared on deactivation; the accessor's `is_active` filter is the mechanism. | **SATISFIED** | `PrimaryRatePlanActiveTest` |
| **R-ADMIN-COPY** | **LEAVE THE 403 FALLTHROUGH.** The admin branch and back-link stay; the body stays generic. ⚠️ **F-3 remains PARTIAL BY RULING.** | **SATISFIED** | `SevenSmallFindingsTest` |
| **R-HK-11** | **KEEP THE HOUSEKEEPING LADDER PERMISSIVE.** All fourteen permitted moves stay. | **SATISFIED** | `HousekeepingLadderTest` |
| **H4** | **PERMIT REOPEN-TO-PENDING, DELIBERATELY.** It stops being an accident and becomes a decision. | **SATISFIED** | `HousekeepingLadderTest` |
| **R-SEASON** | **CONFIRM THE DESIGN — NO GUARD ON RE-APPLY.** Re-applying the whole covering set weakest-first is how precedence governs price. (Its edit half was already closed by code.) | **SATISFIED** | `SeasonPrecedenceTest` |
| **R4** | **REFUSE `confirmed` → `checked_out`.** A stay must be checked in before it can be checked out. | ✅ **BUILT (FIX 33)** | `ReservationLadderTest` |
| **R5** | **REFUSE `enquiry`/`tentative` → `checked_in`, AND BUILD THE CONFIRM ROUTE THE REFUSAL POINTS AT.** ⚠️ The refusal is unchanged; what was missing was anywhere to go. 🔬 Before FIX 54 **no confirm route existed anywhere in the product** — sixteen operator POST routes touched a reservation and none promoted one. ⚠️ 🔬 `enquiry` gets no route because **nothing writes that status**. | ✅ **BUILT (FIX 54); THE REFUSAL ARM WRITTEN (FIX 55)** — `tentative → confirmed`, gated `reservations.manage` (⚠️ no permission minted). ⚠️⚠️ **FIX 54 BUILT AND DROVE THE ROUTE BUT LEFT THE REFUSAL FALLING THROUGH TO THE GENERIC MESSAGE, AND SAID SO.** FIX 55 wrote it as the `no_show` arm's sibling: it names **Confirm hold** — 🔬 the SHIPPED label, read from the blade — and states why confirming cannot fail (the hold already occupies the nights, unlike a no-show whose nights were released). ⚠️ The two are tied by a test that reads the button out of the view, so renaming the control turns the copy RED instead of silently making it a lie. **Mutation-proved both ways.** | `ConfirmTentativeHoldTest` · `ReservationLadderTest` |
| **R8** | **PERMIT `tentative` → `confirmed`.** A held group booking becomes a real stay. ⚠️⚠️ The hold is taken at CREATE, so confirming CONVERTS it and must never take a second — 🔬 sold 0 → 2 → 2 → 2 across create, confirm and check-in. | ✅ **BUILT (FIX 54)** | `ConfirmTentativeHoldTest` |
| **H1** | **COVERED BY R-HK-11, NOT A SEPARATE DECISION.** `pending → clean` is one of the fourteen permitted moves that ruling kept. | **SATISFIED** (under R-HK-11) | `HousekeepingLadderTest` |
| **H2** | **COVERED BY R-HK-11, NOT A SEPARATE DECISION.** The six backward moves are among the fourteen that ruling kept. | **SATISFIED** (under R-HK-11) | `HousekeepingLadderTest` |
| **H3** | **COVERED BY R-HK-11, NOT A SEPARATE DECISION.** The four same-status re-submits are among the fourteen that ruling kept. | **SATISFIED** (under R-HK-11) | `HousekeepingLadderTest` |

⚠️⚠️ **H1–H3 WERE ADDED BY FIX 51, AND THEY DECIDE NOTHING NEW — THEY RECORD A DECISION THAT WAS ALREADY TAKEN.**
🔬 Verified before recording, from `HousekeepingService::ALLOWED_FROM` rather than from any description of it:
the ladder permits **exactly fourteen** moves, so R-HK-11's *"all fourteen permitted moves stay"* is literally
accurate — and H1 (1 move), H2 (6) and H3 (4) are all inside that set. ⚠️ The H-groupings OVERLAP rather than
partition it: H4's four moves to `pending` include three of H2's and one of H3's, and three plain forward
moves belong to no H-row at all. **Membership was checked move by move, not by adding the group sizes up.**

⚠️⚠️ **AND THIS IS THE CLASS, WHICH COST ELEVEN PASSES IN THE OPPOSITE DIRECTION (F-32): A RULING TAKEN OVER A
SET ANSWERS EVERY MEMBER OF THAT SET, INCLUDING THE MEMBERS IT DOES NOT NAME — BUT A PARSER CANNOT KNOW THAT.**
*"The ruling covers it"* and *"the record states it"* are different facts, and **only the second one is
enforceable.** Until these rows existed the guard left H1–H3 out of its map entirely, refusing to assert a
state the record did not state — which was right, and is now unnecessary.

⚠️⚠️ **EVERY "SATISFIED" ROW IS A BEHAVIOUR A FUTURE PASS WILL READ AS A DEFECT AND HELPFULLY CORRECT.** Each
pin is named for its decision and was proven by **flipping the behaviour and requiring RED** — a test that
still passes with the behaviour reversed pins nothing. ⚠️ **The sharpest is R-SEASON: adding an idempotency
guard to `apply()` looks like a fix and BREAKS PRICING.**

### ⚠️⚠️ R-F10 — THE PREMISE FAILED, SO NO ANSWER IS RECORDED

**The ruling put was "make the three grantable to back-office roles". 🔬 They already are.**

`billing.manage`, `settings.manage` and `roles.manage` are in the global catalogue
(`config/authorization.php`), and `RolePermissionService::matrix()` offers **`catalogPermissions()` — the
entire catalogue, with no exclusion list — to every editable back-office role.** `RolePermissions`' own
docblock describes the round trip as already possible: *"A Property Owner grants Hotel Manager `roles.manage`
+ `billing.manage` through the matrix. LEGITIMATE."*

⚠️ **F-10's claim is about the SEEDED DEFAULT, not about grantability.** 🔬 Measured in tenant one: all three
are held by **Super Admin and Property Owner** and by no role below. Nothing prevents an operator granting
them today.

⚠️ **So the real question was never asked:** *should the seeded role catalogue grant any of the three to a
role below Property Owner by default?* **That is a different decision and this pass does not answer it.**
⚠️ **No role table, seeder or permission was touched, so no pinned figure moved.**

### ⚠️ R1 AND R3 — RULED 2026-09-19, AND R3 IS STOPPED

| # | the ruling | state |
|---|---|---|
| **R1** | **REFUSE `cancelled` → `checked_in`.** A cancelled booking cannot be revived into an occupied stay; the room may already have been resold. ⚠️⚠️ **EXTENDED 2026-09-19 (F-60): THE SAME REFUSAL COVERS `no_show` → `checked_in`.** The ruling's reasoning was always an availability argument and 🔬 it is literally true of a no-show — the line is already released and the freed nights may already have been pushed to the channels, so permitting it would be a **re-booking against inventory that may no longer exist**. | ✅ **BUILT (FIX 33)** — behaviour was already correct; what changed is the **refusal message**, which now tells the desk a returning guest needs a NEW BOOKING instead of only closing the door. ⚠️ **EXTENDED (FIX 49)**: the two arms are now SPLIT and the `no_show` arm names the route — 🔬 driven first: reschedule REFUSES a released line, a new reservation SUCCEEDS. Pinned by `ReservationLadderTest`. |
| **R3** | **PERMIT `checked_out` → `checked_in`, AND REVERSE WHAT THE CHECKOUT DID** — a real undo, not a status flip. ⚠️ FIX 33 STOPPED on it (the review email cannot be recalled); the founder then ruled **RECORD THE SEND, THEN REVERSE AND DISCLOSE**. | ✅ **BUILT (FIX 34)** — `CheckoutReversalTest` |

### ✅ R3 — BUILT (FIX 34), AND THE BOUNDARY IS THE PART THAT MATTERS

**Three pieces, as ruled.** (1) the review-request send is now RECORDED; (2) `ReverseCheckOut` reverses what
it can and DISCLOSES what it cannot, before the operator presses; (3) the review link survives a re-opened
stay instead of 404ing the guest it already invited.

⚠️⚠️ **THE HARD PART WAS NOT THE MIGRATION — IT WAS THE BOUNDARY IT CREATED.** Every stay that checked out
before `guest_review_invites` existed has NO ROW, and an absent record means something completely different
from an empty one. **Three states, three representations:**

| what the data says | what the operator is told |
|---|---|
| a row **with** `sent_at` | *"A review request was emailed to the guest on … It cannot be recalled."* |
| a row **without** `sent_at` | *"No review request was sent — "* **and why** (operator's switch, guest's preference, or no contact) |
| ⚠️⚠️ **no row at all** | *"We have no record of whether a review request was sent … it checked out before this product started recording them."* |

⚠️ **THE ROW'S EXISTENCE IS THE MARKER.** From the migration onward EVERY checkout writes exactly one row per
stay whatever the outcome, so an absence can only mean "predates the record". A nullable column on
`reservations` would have collapsed the last two states into one empty value, and **that collapse was the
defect**. Read through `GuestReviewInvite::stateFor()` — never by testing a column for emptiness.

⚠️⚠️ **THERE IS NO BACKFILL, DELIBERATELY.** Past sends left no trace; deriving one from `checked_out` plus
the two feature switches would be a guess, and **an invented row cannot be told from a real one afterwards.**
🔬 The demo seed deletes ONE invite row so the UNKNOWN state is visible on a real screen — it creates an
absence, it does not fabricate a record.

⚠️ **WHAT THE UNDO REFUSES TO DECIDE.** A still-`pending` departure task is removed; a **STARTED** one is left
alone and reported, because cancelling work someone has begun is an instruction, not an undo. The unit stays
`dirty` — the guest is back in the room, so "clean" could be false the moment it is asserted. Notifications
already delivered stay delivered. **All of it appears on the confirmation screen before the press.**

⚠️ **NO GUEST PII.** 🔬 The table's columns are `id, organization_id, reservation_id, outcome, sent_at,
channel, created_at, updated_at` — no address, no name, no body.

⚠️ **R4, R1 AND R6 ARE UNDISTURBED.** `CheckInReservation` still accepts `confirmed` only — the reversal is a
separate named action, so a plain check-in can never silently undo a checkout — and `checked_in` stays out of
`TERMINAL_STATUSES`.

### ⚠️ THE BUILD QUEUE — ⚠️ **FIX 41: the heading read "decided, none built" while R-GATE, R-SUSPEND and R4 had all been built. FIX 37 removed R-REVERSE's row from this table, so the maintenance was expected; two passes skipped it.**

| build | touches | closes | ⚠️ |
|---|---|---|---|
| **R-SUSPEND** ✅ **BUILT (FIX 36, widened FIX 39)** | the **public storefront** — nine `/book/{property}` routes | **F-48** | ⚠️⚠️ **TRAVELS ALONE.** It changes who can sell; option B takes a live storefront down. |
| **R-REVERSE** | ⚠️ **REMOVED FROM THE QUEUE 2026-09-19** — superseded by the quarantine ruling | **F-49** (now ACCEPTED BY RULING) | ⚠️⚠️ **DO NOT BUILD.** Routing it today ships a double-reversal on a double-click and strands the statement. Fix F-53 + F-54 at the cause first. |
| **R-GATE** ✅ **BUILT (FIX 35)** | `/owner` middleware; the gate is **already written and mutation-proven** | **F-23** + un-skips its tests | ⚠️ **gate-BEFORE-scope**; if an owner cannot be resolved that early, STOP AND REPORT |
| **R4** ✅ **BUILT (FIX 33)** | `CheckOutReservation::ALLOWED_FROM` — 🔬 now `['checked_in']` | last of the reservation ladder | ⚠️⚠️ **WILL REDDEN `ReservationLadderTest`'s `confirmed → checked_out IS PERMITTED`** — that test currently pins the OLD answer. **Editing it IS the visible record of the decision; it must be deliberate, named and explained in its commit, never a quiet fix to a red.** ⚠️ `DemoSeeder` performs this transition twice and must check in first. |
| **R-ARCHIVE** ✅ **BUILT (FIX 43)** | a new owner notification at archive time | **F-24** ✅ CLOSED | no un-archive is built — ⚠️ and the notice inherits the `properties:process-removals` cron: **it does not fire without `schedule:run`** (GO-LIVE B5) |
| **R-PHANTOM** ✅ **BUILT (FIX 41)** | a test only — no seeder or role-table churn | **F-14** ✅ CLOSED | ⚠️ the seventeen are pinned BY NAME, and FIX 42 collapsed the second and third censuses into that one source |

### The options — kept as the record of what was NOT chosen

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
| **R-ARCHIVE** | Archiving a property is **irreversible in-app** and ⚠️ **the owner WAS told nothing — corrected FIX 43, this row read "is told nothing" in the present tense after the notice shipped.** 🔬 `PropertyRemovalService`'s own docblock: *"Nothing is ever un-archived"* — cancellable only **before** it runs, and it still is: no undo was added. | **A** — irreversible, silent | **Keep** irreversible; **disclose** it on the confirm screen | **Build an un-archive** path | A: small · B: medium · **F-24** · A reversible, B is schema-touching |
| **R-PHANTOM** | 17 permissions gate nothing, in three shapes: **6** enforced under a *different* permission, **8** with no operator surface, **3** enforced by a role allow-list. ⚠️ **A permission that should not exist and one that is merely unenforced need OPPOSITE fixes**, which is why no pass could proceed. | **A** — all 17 remain, gating nothing | **Remove** the ones that should not exist; **wire** the rest | **Wire** all 17 to real checks | per-toggle; the classification is done (`docs/PHANTOM-PERMISSIONS.md`) · **F-14** · removals move pinned counts |
| **R-SUSPEND** | **A suspended tenant can still sell.** 🔬 Confirmed today: `/stay/{organization}` runs `ResolvePublicOrganization`, which 404s a non-`trialing/active` org — but all **nine `/book/{property}` routes**, including `/checkout` and `/book`, run `ResolvePublicProperty`, which checks only `properties.status='active'` and **never looks at the organisation**. | **A** — suspension does not stop bookings | **Leave**: suspension is billing-only, and the admin copy already says *"it does not yet block their access"* | **Close** the booking path on suspension | small (one middleware check) · **F-48** · reversible — ⚠️ **but option B takes a live storefront down; decide what suspension MEANS first** |
| **R-REVERSE** | **A payout can be made and cannot be un-made through the product.** ⚠️⚠️ **THE DESCRIPTION THAT USED TO SIT HERE — "a real, provable inverse" — WAS WRONG, AND IT TRAVELLED FOR FOUR BRIEFS.** It was 📖 **ASSERTED** (read from source, never executed) and moved through this register, the memory record and four briefs as though 🔬 MEASURED. FIX 37 executed it for the first time and found **two HIGH defects**. ⚠️ **ONLY 🔬 MEASURED AND 🖥 BROWSER ARE FACTS.** | ⛔ **QUARANTINED** — see the answers table above |
| **R6** | May an **inbound channel** rewrite a stay **the guest is physically in**? 🔬 Confirmed today: `AssignmentService::reimportBooking()` guards `TERMINAL_STATUSES` = cancelled/no_show/checked_out. **`checked_in` is not terminal, so the rewrite is permitted** — status, dates, price and unit lines. Latent only because Channex is account-gated. | **A** — permitted | **Leave**: a real OTA can legitimately extend a stay mid-visit, and refusing drops a genuine change | **Refuse** it, as the three terminal statuses are refused | small (one status added to a guard) · last quarter of **F-29** · reversible ⚠️ **MOVES MONEY — `total_minor` is rewritten by the same raw update** |
| **R-HK-11** | Which housekeeping transitions are legitimate? See the ladder below — **one table, one sitting.** | **A** — 14 of 16 permitted | **Ratify** the permissive map | **Tighten** named rows | small per row · remainder of **F-15** · reversible |

### ⚠️⚠️ THE OPEN FINDINGS — NEVER PUT TO THE FOUNDER (added FIX 45)

⚠️⚠️ **ANSWERED 2026-09-19 — FIVE OF THE SIX WERE RULED IN ONE SITTING AND BUILT OR RECORDED IN FIX 46.**
This table was written by FIX 45 to make them askable; it is kept as the record of **what was put and what was
chosen**, with each row's outcome below it. ⚠️ **F-35 is ✅ CLOSED (FIX 47)** — it had its own pass because its blast
radius was unknown and its delta had to be uncontaminated.

| finding | ruled | outcome |
|---|---|---|
| **F-44** | fix all nine **and pin the class** | ✅ CLOSED (FIX 46) — ⚠️ **twelve, not nine; the guard found the other three on its first run** |
| **F-21** | the disclosure states its own scope | ✅ CLOSED (FIX 46) — one rendered sentence, unconditional, stating the rule rather than a list |
| **F-33** | the back office is **desktop-only** | ⚠️ ACCEPTED BY RULING — `KNOWN-LIMITS.md` **L7**, with the counter-argument recorded |
| **F-36** | **deferred** until the channel goes live | ⚠️ **DEFERRED** — trigger: **A10 in `GO-LIVE-REQUIREMENTS.md`** |
| **F-58** | per-property visibility is **enough** | ⚠️ ACCEPTED BY RULING — and what it rests on is now pinned |
| **F-35** | **enable it** | ✅ CLOSED (FIX 47) — it had its own pass; the delta had to be uncontaminated |

⚠️ **The options as they were put are kept below.** They were re-measured from the code before being offered,
which is why two of them moved: F-21 had TWO limits where its row recorded one, and F-44 had twelve sites
where its row recorded nine.

⚠️ **NOTHING HERE IS RECOMMENDED.** The options are stated so the reader is choosing, not ratifying.

⚠️ **F-32 IS NOT IN THIS TABLE**: re-measuring found it was already answered by **R-ADMIN-COPY**, and it is now
**ACCEPTED BY RULING**. A finding retired is a question nobody has to answer.

#### The free ones — a decision, not a project

| finding | at stake | ships today | option A | option B | unblocks · reversible |
|---|---|---|---|---|---|
| **F-35** 🟡 | Should the test suite be **forbidden from reaching the internet**? 🔬 Re-measured: `Http::preventStrayRequests()` still appears **zero times** in `tests/` and `app/` — unchanged since recorded. ⚠️ It was never the cause of anything observed. | **A** — the suite *could* reach the network; nothing stops it | **Leave** it — a hardening item with no observed symptom | **Add** `Http::preventStrayRequests()` to the base test case and fake every outbound call it exposes | unblocks a hermetic suite · ⚠️ **B may surface unknown live calls — that is the point AND the cost** · fully reversible |
| **F-58** 🟡 | A pending property removal is **visible only if you open that one property's Settings**. 🔬 The date IS rendered there with a cancel control, and since FIX 44 a deferral rewrites it so the page stays true. What is missing is portfolio-level visibility. ⚠️ **Options carried forward from FIX 44 unchanged — not re-derived.** | **A** — pull-only, per-property | **Leave** it — the date is visible where the removal was scheduled | **Build** a portfolio list of pending removals *(FIX 44 recorded a third: notify the requesting operator on each deferral — ⚠️ most work, and most likely to become noise on exactly the property that defers nightly)* | unblocks nothing else · reversible |

#### The ones that commit to a build

| finding | at stake | ships today | option A | option B | cost · unblocks · reversible |
|---|---|---|---|---|---|
| **F-21** 🟡 | The agreed-rate disclosure tells an operator how many nights a season will overwrite — **but not what it cannot see.** 🔬 Re-measured: it counts a night only when an **APPROVED `RateProposal`** covers it AND the price still equals the agreed figure. So an owner's **self-set** rate (`OwnerRateService::apply()`, a live owner-portal path) is invisible to it — ⚠️ **and so is any agreed rate that has since drifted**, a SECOND unstated limit this row never recorded. Both live only in a Blade comment, which never renders. ⚠️ **NOT the same question as R-RATE**: that ruled seasons MAY overwrite and the owner is NOT told, and 🔬 the dialog already renders *"Applying replaces those prices, and the owner is not told."* This asks whether the disclosure must state **its own scope**. | **A** — an undercount presented without its limits; an operator may read "3 nights" as "all that is at risk" | **Leave** it — the count is exact for what it measures | **Say the limit in the dialog** — one rendered sentence naming what the count excludes | small (copy in one Blade template) · unblocks nothing · fully reversible ⚠️ **A wider fix — counting owner-set rates too — is a different and much larger question, not offered here** |
| **F-33** 🟠 | **The operator shell folds at 375px and the navigation becomes unreachable.** 🔬 Re-measured: the shell carries twelve media queries and **none touches the nav**; there is no drawer, toggle or hamburger, and the file is untouched since before FIX 30. ⚠️ **54 screens sit inside this shell.** ⚠️ FIX 11 tried the obvious fix and it pushed all 22 nav links off-screen. | **A** — the back office is **desktop-only in practice** on a phone | **Accept** it — state desktop-only as a product position | **Build** a mobile nav (drawer, or priority-plus), which FIX 11 costed | ⚠️ **largest of the six** · unblocks any phone use of the back office · reversible, but the work is not cheap to repeat |
| **F-36** 🟠 | **When the channel manager's refusal protects a booking, nobody is told.** 🔬 Re-measured and unchanged: `BookingImportService` writes `reservation.ota_modification_refused` to `audit_events` and **no operator surface reads it** — the reservation timeline filters to Payment-subject rows, and the only mention of `webhook_events`/`sync_jobs` anywhere in views is a marketing-page comment noting they are empty. | **A** — the refusal is invisible; an operator sees an OTA change that silently did not apply | **Leave** it — the guard works and the audit row exists for support | **Surface it** — render reservation-subject audit rows in the timeline, or build a channel-activity view | medium (timeline filter) to large (new surface) · unblocks channel-manager diagnosis · reversible |
| **F-44** 🟠 | Nine surfaces fall back to a **hardcoded `'EUR'`** when their collection is empty — so a GBP owner or guest with nothing yet to show is quoted euros. 🔬 **RE-MEASURED FIX 45: all NINE are still present, at their recorded lines, untouched by any later pass.** ⚠️ The amount is right and the symbol is wrong, which is the dangerous direction — zero looks the same in every currency. ⚠️ **F-46 closed exactly this class at one site**; these nine are the remainder. | **A** — euros shown to non-euro users on an empty surface | **Leave** it — cosmetic, and only ever visible when there is nothing to show | **Derive** each from the owner/property/reservation as F-46 did, or show a neutral dash | nine small edits, **each needing its own fixture to prove** · unblocks honest currency on every empty state · fully reversible |

⚠️ **NONE OF THESE GATE EACH OTHER.** Each can be answered alone, in any order. ⚠️ **F-21 is the only one that
touches a surface another ruling already covers** — R-RATE settled the policy; F-21 asks only whether the
dialog discloses its own blind spot.

### The ladder — every housekeeping and reservation move, one table

⚠️ **THIS HEADING SAID "16 housekeeping moves and 5 reservation moves" AND DISAGREED WITH ITS OWN ROWS** (🔬 six reservation rulings R1–R6; the housekeeping rows sum to seventeen moves as grouped). **The numbers were REMOVED, not corrected (FIX 45)** — establishing which reading was intended would have meant deciding something, and this pass decides nothing. **Count the rows.**

🔬 **Derived from the constants on 2026-09-19**, not from an earlier description:
`HousekeepingService::ALLOWED_FROM` (keyed by target) · `CheckInReservation::ALLOWED_FROM = ['confirmed']` ·
`CheckOutReservation::ALLOWED_FROM = ['checked_in']` — ⚠️ **corrected FIX 42; it read `['checked_in','confirmed']` here for nine passes after FIX 33 removed `confirmed` in the code.** **Both ladders are pinned by committed
tests** (`HousekeepingLadderTest`, `ReservationLadderTest`), so tightening a row later is a deliberate act
that reddens a test rather than a silent narrowing.

⚠️⚠️ **THE TOP FOUR ROWS CAN MOVE MONEY OR REVIVE A DEAD ROW. They are not ladder trivia.**

| # | transition | today | permissive reading | restrictive reading | state |
|---|---|---|---|---|---|
| **R6** | `checked_in → confirmed` *(inbound channel, + dates/price/units)* | ⚠️ **PERMITTED** | an OTA may extend a live stay | an arrived guest cannot be un-arrived by an external system | ⚠️ **never guarded — rewrites `total_minor`** |
| **R4** | `confirmed → checked_out` | ❌ **REFUSED (FIX 33)** — ⚠️ this cell read ⚠️ **PERMITTED** until FIX 42 | express departure, no arrival record | a stay must be arrived before it departs | ⚠️ **measured, not chosen** — `DemoSeeder` does it twice; a test now asserts it |
| **R1** | `cancelled → checked_in` | ❌ refused | a guest may change their mind | the room may have been resold | deliberate — reinstating needs an availability re-check, not a status write |
| **R3** | `checked_out → checked_in` | ✅ **PERMITTED WITH REVERSAL (FIX 34)** — 🔬 `ReverseCheckOut` is routed at `front-desk/{reservation}/reverse-checkout`, `can:reservations.checkout`. ⚠️ this cell read ❌ refused until FIX 42 | wrong guest, same day | departure already created a cleaning task and dirtied the unit | deliberate |
| **R2** ✅ **RULED (2026-09-19) — REFUSE, AS AN EXTENSION OF R1** | `no_show → checked_in` | ❌ refused | **the commonest real case** — arrival at 01:00 after the night audit | a no-show is a commercial event already settled, **and 🔬 its nights are already back on sale** | ⚠️ no longer merely deliberate — **ruled**, and the refusal message now names the way out |
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

* ⚠️⚠️ **THE MONEY-WRITE ROUTES — AND THE DENOMINATOR IS UNKNOWN, SO THIS LINE STATES THE RULE AND NOT A
  FRACTION (F-59).** ⚠️ It read *"3 of 17 exercised, fourteen remain"*; briefs read *"ten remain"*. 🔬 **No list
  of the seventeen exists in any document** — so "17" was a curated subset whose membership rule was never
  written down, and **neither remainder was ever derivable.** ⚠️ **A fraction whose denominator nobody can
  reconstruct is not a coverage measurement**, so it has been removed rather than corrected.

  ⚠️⚠️ **AND A SECOND FIGURE HAS NOW BEEN RETIRED TOO: "a router sweep finds 54 money-adjacent write routes"
  WAS NEVER A FACT.** 🔬 FIX 56 measured the same rule at four call-graph traversal depths and got
  **3 / 47 / 163 / 190** — **54 is one arbitrary point on that curve**, and it travelled through several
  briefs and this document as though measured. ⚠️ **F-59 is now ACCEPTED BY RULING**: the denominator is not
  mechanically derivable, so no guard is possible and no honest percentage exists. See its row and
  `KNOWN-LIMITS.md` **L11**.

  ⚠️ **THE RULE THAT REPLACES IT: a money-write route counts as EXERCISED only when a pass DROVE it and
  recorded the row it wrote. Everything else is read, not run** — and reading is 📖, which this programme has
  already been burned by once (`reverse()` travelled as a measurement through four briefs and held two HIGH
  defects the first time anybody ran it).

  🔬 **DRIVEN, each with the pass that drove it, all in tenant two:** the **refund** (FIX 18) · the **season
  apply** (FIX 20) · **statement finalization** (FIX 21) · and **two clusters of unstated size** (FIX 27,
  FIX 28) whose membership was never written down either. ⚠️ **`payouts/create` and `payouts/pay` are blocked
  STRUCTURALLY**, not merely unscheduled — they need a finalized statement, and `migrate:fresh --seed` erases
  tenant two every pass. **Everything not on that list is unexercised.**
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

⚠️⚠️ **AND SINCE 2026-09-15 THE BROWSER TIER IS STRUCTURALLY NARROWER THAN IT WAS — F-61, added FIX 48.**
🔬 `mode=FULL` requires seeding at the money gate's reference date `2026-09-01`; tenant two is seeded with a
**14-day trial**, so that seed expires it on **2026-09-15**. At any real present date `EnsureSubscriptionActive`
correctly holds **every** tenant-two `/app/*` route at `/app/billing`. ⚠️ **Both gates are right — invariant
#41 is doing its job — but tenant one may never be written to and tenant two's BACK OFFICE can no longer be
reached, so any journey needing a tenant-two operator surface cannot be walked without disabling one of the
two instruments.** ⚠️⚠️ **DO NOT EXTEND THE TRIAL, MOVE THE CLOCK OR RE-SEED AT A LATER DATE TO WALK IT** —
that is re-baselining a fixture to make a check pass. Tenant two's **owner portal is not gated** and stays
reachable. Disclosed in the gate's own output and `KNOWN-LIMITS.md` **L8**; **repaired nowhere**, because the
two ways out — a disposable third tenant, or accepting the remainder as establish-only — are founder choices.

| gap | why |
|---|---|
| **Any currency but EUR** | 🔬 **CLOSED by FIX 22 (2026-09-14).** Every fixture in the repo was EUR — `seedScenario()`, `book()`, the demo tenant — so a hardcoded `'€'` was **indistinguishable** from a resolved currency in all 3,400 tests. ⚠️ Proven, not argued: with F-38 reintroduced, the EUR case passed **green** while the GBP tenant rendered euros over pound prices. A **GBP fixture** now runs beside a EUR control, plus three source guards. ⚠️ **The general form of this gap is still open elsewhere: any dimension a fixture fixes to one value is a dimension the suite cannot test.** |
| **Multi-tenancy / cross-organisation isolation** | 🔬 **one organisation seeded.** The single largest untested property in the product. Every "tenant-scoped → cross-org 404" claim in the codebase is **read, never exercised.** |
| **Owner-to-owner isolation** | 🔬 **one owner seeded.** UNTESTED, not passed. A second owner would also make the union-vs-per-owner capability question (P13 §0) exercisable. |
| **Guest session-to-session interference** | 🔬 **one claimed guest.** The direction that carries risk — a guest reaching another's data — **was** fully tested (16/16 refused). |
| **The money-write routes** ⚠️ **(count UNKNOWN — F-59)** | Untouched by instruction in every phase. Refunds, payments, payouts and statement finalization were **established by reading, never executed**. ⚠️ **The "17" this row used to name is enumerated in no document**; see §5's rule above for what has actually been driven. |
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
  ⚠️ Not re-measured since P14. ⚠️⚠️ **AND THIS IS NOW INVERTED — corrected FIX 41.** It read *"F-23's unwired gate is
  deliberately holding to keep this true — wiring it before R-GATE would convert 9 of these 404s into 403s"*. **The gate WAS
  wired (FIX 35) and that conversion is exactly what that pass recorded doing**, so the 16/16 figure predates the wiring.
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
4. **F-23** — ✅ **CLOSED (FIX 35).** ⚠️ This item read *"BUILT AND UNWIRED. Blocked on R-GATE"* until FIX 41.
5. ~~**F-17 / F-22 / F-9**~~ ✅ **DONE (FIX 05), as one pass**, exactly as suggested.
6. **The founder rulings (§4)** — ⚠️⚠️ **ALL PUT AND ANSWERED 2026-09-19; NO LONGER A CONSTRAINT AT ALL.** ⚠️ **FIX 41: this said "Nine rulings" gating F-23 among others; fifteen decisions were put and answered on 2026-09-19 and F-23 is closed.** Nine rulings
   gate five findings plus F-23 plus F-32. ⚠️ **STALE — F-23 is CLOSED (FIX 35) and F-32 is ACCEPTED BY RULING (established FIX 45). Re-derive in §2/§2b.** **They cost nothing to decide and no further code can close them.**
7. ~~**F-16, F-13**~~ ✅ **DONE (FIX 07).** ~~**F-24**~~ ✅ **DONE (FIX 43).** ~~**F-21**~~ ✅ **DONE (FIX 46).** **F-25** is ACCEPTED BY RULING.
8. **The untestable list (§5)** — ⚠️ **untouched, exactly as it was.** A second organisation, a second owner
   and a second claimed guest would convert the single largest block of unknowns into measurable ones.
   **This is the highest-value work left in the product that is not a founder ruling.**

### What a fourteenth pass would take on

**In evidence order, deciding nothing:**

1. ~~**F-29**~~ ✅ **DONE (FIX 14)** for the three terminal statuses. ⚠️ **R6 (`checked_in`) is what is left**,
   and it is a ruling, not work.
2. **§5's second organisation / second owner / second guest** — the largest unknown, and testable.
   ⚠️ **Now the highest-value work in the product that is not a founder ruling.**
3. ~~**F-35**~~ ✅ **DONE (FIX 47).** ⚠️⚠️ **THE REGISTER HAD NO OPEN FINDINGS FOR EXACTLY ONE DAY, AND FIX 48 ENDED THAT — HONESTLY.** ⚠️⚠️ **THE ZERO WAS TRUE ONLY BECAUSE THE FINAL VERIFICATION'S OWN SIX FINDINGS HAD NEVER BEEN ENTERED INTO THE REGISTER: they lived in `FINAL-VERIFICATION.md` §7 and in a report, and a report is evidence ABOUT the repository, not the repository.** FIX 48 classified all six — two were record contradictions and were FIXED, one was an instrument fault and went to `INSTRUMENT-ERRORS.md`, and **three became real rows (F-59, F-60, F-61)** beside a fourth the same document had recorded in its guard table (**F-62**) — plus a **fifth, F-63**, found only because this pass followed F-60's own lead into the other ladder file and discovered that `R2` means three different things in three files. ⚠️ **Re-derive the census in §2/§2b; do not read a number here.** ⚠️ **And the old caveat still stands**: §5's untestable list is unchanged, and what stands between this and a paying customer is still not code. **Previously read:** *"the last of the fix programme's own open findings"*. ⚠️ **FIX 46 CLOSED OR RECORDED THE OTHER FIVE** (F-21 and F-44 closed; F-33 and F-58 accepted by ruling; F-36 **deferred with a trigger**). F-35 has its own pass because enabling `preventStrayRequests()` has an unknown blast radius and its delta must be uncontaminated. ⚠️ **Previously read:** *"F-21, F-33, F-35, F-36, F-44, F-58 … smallest first."* ⚠️ **F-32 LEFT THIS LIST IN FIX 45**, not by being fixed but by being re-read against **R-ADMIN-COPY**, which had already answered it — it is now ACCEPTED BY RULING. ⚠️ **All six are now DECIDABLE in §4.** ⚠️ **CORRECTED FIX 44, and it was stale independently of this pass**: it read *"F-34, F-33, F-32, F-35"* while **F-34 is closed** and four others had joined. 🔬 Re-derived from the register, never counted from this line.
4. **The rulings** — founder work, not session work. ⚠️ **FIX 14 added R6, so there are ten in §4's register
   plus the seventeen ladder decisions.**

