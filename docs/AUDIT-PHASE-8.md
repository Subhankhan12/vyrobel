# AUDIT — PHASE 8 · HOUSEKEEPING

**Role:** Housekeeping · `demo-housekeeper@vyrobel-demo.test`
**Realm:** operator (`/app`, `web` guard)
**Instrument:** MCP Playwright → `chrome-headless-shell` via the project `.mcp.json`
**Date:** 2026-09-12
**Application code changed:** none. Verified — `git diff --name-only` filtered by mtime over the session
window returns **zero tracked files**. The only repo writes are this file and the harness's own
`.remember/` bookkeeping.

**Evidence tiers.** 🔬 MEASURED (a number this pass produced) · 🖥 BROWSER (seen in the live browser) ·
📖 ASSERTED (the brief or a doc says so) · 📄 RECORDED (a repo doc says so). **Only 🔬 and 🖥 are facts.**

---

## 0. THE ACID TEST — ANSWERED: **YES**

> The brief: *"Establish whether this role holds `housekeeping.manage`, whether the submit controls
> RENDER, and whether a status actually MOVES when one is pressed… AND IF THE ANSWER IS NO, THAT IS THE
> MOST SERIOUS INTERFACE FINDING OF THE PROGRAMME."*

**The answer is yes on all three counts, proven by row state, not by screen text.**

| Question | Answer | Evidence |
|---|---|---|
| Holds `housekeeping.manage`? | **YES** | 🔬 config and DB agree exactly: 4 permissions, `housekeeping.manage` among them |
| Submit controls render? | **YES — 7** | 🖥 7 `Start` buttons, 7 forms → `POST /app/housekeeping/{id}/status` |
| Does a status actually move? | **YES** | 🔬 row read before and after, below |

```
BEFORE  status=pending      started_at=NULL                  completed_at=NULL
        ── pressed "Start" in the browser ──
AFTER   status=in_progress  started_at='2026-09-12 08:31:22' completed_at=NULL
        ── pressed "Mark clean" in the browser ──
AFTER   status=clean        started_at='2026-09-12 08:31:22' completed_at='2026-09-12 08:32:46'
        unit 01m1qj5x0c7f6mekr7839b87vt  housekeeping_status: dirty → clean
```

**So F-13 is not the shape the programme thought it was.** The board is not "built for a role that role
cannot use." The housekeeper's core job works, end to end, and it survives a JavaScript failure (§4).

That is the good news, and it is real. Everything below is what the acid test surfaced *around* it — and
two of those are more serious than the thing the phase was sent to find.

**Restoration.** The task was restored exactly: all 7 rows `pending`, `started_at`/`completed_at`/
`inspected_by`/`assigned_to` NULL, unit back to `dirty` — verified row by row against the captured
baseline. Restore was done by raw `DB::table()->update()` so the restore itself wrote no events.
**Append-only residue, correctly NOT deleted:** `audit_events` 154 → **156**, `domain_events` 190 → **195**,
`unit_status_logs` 4 → **6**. Next phase should baseline from those figures, not from Phase 7's.

---

## 1. 🔴 F-15 (HIGH) — the housekeeping status ladder is **unbounded**: `pending → inspected` in one request

The status route accepts any target in `ALLOWED` regardless of the row's **current** status.
[`HousekeepingActionController.php:38-54`](../app/Http/Controllers/HousekeepingActionController.php#L38-L54):

```php
private const ALLOWED = ['in_progress', 'clean', 'inspected'];
…
if (! in_array($target, self::ALLOWED, true)) { abort(422, 'Unsupported housekeeping status.'); }
$model = HousekeepingTask::query()->findOrFail($task);
…
$housekeeping->updateTaskStatus($model, $target);          // ← no check of $model->status
```

`HousekeepingService::updateTaskStatus()` does not check it either — it stamps timestamps by target and
writes.

**Proven, not reasoned.** Replaying the page's own form with `status=inspected` on a `pending` task —
the real session, the real CSRF token, no forged credential, exactly what any holder of the permission can
do from devtools:

```
POST /app/housekeeping/01m1qj5xvdg3xwherkfg339ek5/status   status=inspected
→ 200, flash "marked Inspected", no refusal

ROW    status=inspected   started_at=NULL   completed_at='2026-09-12 08:34:00'
                          inspected_by='01m1qj5vmbp796baan9mftqywf'   ← the housekeeper
UNIT   housekeeping_status='inspected'                                ← guest-ready
```

**A dirty room became guest-ready in one request.** It was never started (`started_at` NULL — the only
forensic trace), never cleaned, and `inspected_by` names the person who skipped both as its inspector, so
the cleaner/inspector separation is not enforced either.

**This is the exact shape of the project's own invariant #29.** `CLAUDE.md`: *"a reservation that is
already terminal cannot be terminated again — guarded inside the private `terminate()`… A second
termination is a REWRITE, not an append."* The same reasoning applies here and the guard is absent.
The canon also records why this class hides: *"#7 bounds EDITS, #27 bounds the SUM"* — an append-only
trail does not bound a transition.

**Not a permission bug.** `housekeeping.manage` is correctly required and correctly enforced (8 route
sites, 🔬). The defect is that the *data bound* is missing — and per #30 that bound belongs in the
service, not the route, because services are called by jobs and tests with no actor.

---

## 2. 🟠 F-16 (MEDIUM) — the board's two views disagree about who may start a task, and the server agrees with neither view consistently

One concept, two computations — the drift failure mode the project's own canon names (§N.8f, *"one
definition per concept"*).

| Surface | Gate on the forward button |
|---|---|
| **List** — [`housekeeping-board.blade.php:270`](../resources/views/livewire/housekeeping-board.blade.php#L270) | `($canManage ?? $canAssign) && $card['next'] !== []` |
| **Board** — [`hk-card.blade.php:57`](../resources/views/livewire/partials/hk-card.blade.php#L57) | …same **`&& ($card['assignee'] \|\| $card['status'] !== 'pending')`** |
| **Server** — `HousekeepingActionController::updateStatus` | no assignee requirement at all |

The Board tells the housekeeper **"Assign to start"** (🖥 rendered 7×, 11.5px muted). That rule is
**false**: the List view starts an unassigned task, and the server accepts it — proven in §0, where
`assigned_to` was NULL throughout both transitions.

**And the view choice is sticky, per-browser, and traps the role:**

| `localStorage['vy:hk:view']` | View shown | Status controls | On-screen message |
|---|---|---|---|
| *(unset — fresh browser)* | list | **7** 🖥 | — |
| `"board"` — after one click | board | **0** 🖥 | "Assign to start" ×7 |

There is no server-side preference: 🔬 the `users` table has no preference column and
`audit_events` holds zero `user.preferences_updated` rows for this user. The key is browser-local and
survives every subsequent visit. **One click on "Board" leaves a housekeeper permanently unable to start
any task from their landing screen** until someone clears their site data.

**This is what F-13 actually was.** Phase 7 bounded F-13 to one screen; this phase locates it inside that
screen. **One half of F-13 must be corrected:** it is *not* unexplained — the Board does say
"Assign to start". The defect is that the explanation states a rule the system does not have.

---

## 3. 🟠 F-17 (MEDIUM) — the housekeeper's only write is the one housekeeping write that records no audit row

`CLAUDE.md`, non-negotiable: *"every meaningful state change writes an `audit_events` row (who did what)
AND a `domain_events` row."* 🔬 Measured across the two presses in §0:

| Transition | `domain_events` | `audit_events` |
|---|---|---|
| `pending → in_progress` | +1 (`housekeeping.task_updated`) | **+0** |
| `clean` (via unit sync) | +1 | +1 — but `unit.housekeeping_changed`, subject = the **unit** |

🔬 `audit_events` rows whose `subject_id` is the task: **0**.

Three siblings in the very same service do audit — and one's docblock claims the trail is complete:

```
HousekeepingService.php:51   audit->record('unit.housekeeping_changed', …)
HousekeepingService.php:113  audit->record('housekeeping.task_assigned', …)
HousekeepingService.php:135  audit->record('housekeeping.task_unassigned', …)
                             ↑ docblock: "records the change on the append-only event trail (domain + audit)"
HousekeepingService.php:81   domainEvents->record('housekeeping.task_updated', …)   ← no audit sibling
```

**Who started or cleaned a room is recoverable from nowhere.** 🔬 `domain_events` has no actor column
(`id, organization_id, event_type, subject_type, subject_id, payload, occurred_at, available_at,
processed_at, created_at, updated_at`), and 🔬 `housekeeping_tasks` has **no `started_by` and no
`completed_by`** — only `inspected_by`. The actor is recoverable for the `clean` step only *incidentally*,
via the unit-sync audit row that happens to land in the same second, and that row names the unit.

**F-15 and F-17 compound.** The one column that names a person (`inspected_by`) is the one an unbounded
transition lets someone set for a room they never touched. Same family as **F-9** (password change writes
no audit row) — the trail is thorough where it is thorough and simply absent in specific places.

---

## 4. ✅ Results in the good direction — reported as plainly as the defects

**375px — the brief's primary check. No regression; the prior defect is absent.**

| | page overflow | actions fully on screen | worst element overflow |
|---|---|---|---|
| List @375 | **0** 🔬 | **7 of 7** 🔬 | table 362px — inside its own `overflow-x` scroller |
| Board @375 | **0** 🔬 | n/a (view renders none — §2) | 120px, three ambient decoration divs only |

The brief carried forward *"the pinned action, once 294px off-screen"*. 🔬 **It is not off-screen here.**
The table carries `vy-utable--stick-action` — a sticky action column — and all seven actions measure
fully inside the 375px viewport. The 362px table overflow is horizontal scroll *inside a container*, with
`documentElement.scrollWidth - clientWidth === 0`: the page body never scrolls sideways. **Any non-zero
body overflow would have been a regression; there is none.**

**No-script — tested BLOCKED, not disabled, per the brief.** The JS bundle (`livewire.js`, which carries
Alpine — 🔬 it is the only script the page loads) was aborted at the network layer:

| | measured |
|---|---|
| Alpine present | **no** — `window.Alpine` undefined 🔬 |
| `<noscript>` tags on the page | **0** — consistent with the brief's point: a blocked bundle never renders one |
| `x-show` containers left visible | **1 of 14** — they are server-side hidden, not Alpine-hidden |
| Modals leaked open | **0** |
| Duplicate forms for one task | **0** |
| **Status controls surviving** | **7 of 7** 🔬 |

The controls are plain native POST forms — no `wire:`, no `@click`
([`housekeeping-board.blade.php:271-275`](../resources/views/livewire/housekeeping-board.blade.php#L271-L275)).
**The housekeeper's core job survives a total JavaScript failure.** That is unusual and worth stating.

**Guest PII holds (#28).** 🖥 The housekeeper reaches `/app/reservations/{id}` — `h1` is the guest's name
("Demo Traveller") — and 🔬 **zero email addresses render** on it. They are denied `/app/guests` and
`/app/guests/{id}` (403). Name-only outside the CRM, as designed.

**No "UI invites a 403" violation for this role.** Per #30 the control must be gated too. 🔬 Across the
16 screens this role reaches, the ungated ones render **zero** write controls: calendar 0, channels 0,
front-desk 0, rack 0, units 0, unit-requests 0, maintenance 0, email-templates 0. Every write control
that does render posts to a route this role holds (dashboard task toggles, notification read/dismiss,
own profile, own security, schedule time-off request).

`/app/email-templates/{key}/edit` — an *edit* screen, ungated at the route, reachable — 🔬 renders no
`wire:` action, no submit, and no editable template field. Read-only in practice for this role.
*(Checked deliberately: a Livewire Save is `wire:click`, not a submit button; counting submits alone
would have missed it.)*

---

## 5. The walk — all 54 screens, measured

🔬 54 = **45** plain GET `/app` screens + **9** parameterised. The programme's figure is confirmed by
measurement, not quoted.

| | DENIED | REACHED | DOWNLOAD |
|---|---|---|---|
| 45 plain | 27 | 16 | 2 |
| 9 parameterised | 6 | 3 | 0 |
| **54 total** | **33** | **19** | **2** |

🔬 Zero 500s. Zero screens with no `h1`. Zero screens with duplicate `h1` — **F-7 and F-12 do not
reproduce on anything this role can reach** (their screens are denied to it, so this neither confirms
nor retracts them).

**Reached (19):** calendar · channels · dashboard · email-templates · email-templates/{key}/edit ·
front-desk · housekeeping · maintenance · maintenance/{ticket} · notification-preferences ·
notifications · profile · rack · reservations · reservations/{id} · schedule · security ·
unit-requests · units.
Of these, **17 are ungated at the route**; the two gated ones (`schedule` → `schedule.view`,
`unit-requests` → `units.view`) are permissions this role genuinely holds.

**Downloads (2), both succeeded:** `app/schedule/pdf` (`schedule.view` — held) and `app/security/export`
(self-service). *Handled per the known trap: a download returns `null` from navigation and leaves the
previous page in the DOM; read as a status it would look like a 403.*

**A near-false-positive avoided.** The route table lists `app/inbox/{thread}` as **ungated** while the
`app/inbox` list is gated `guests.view`. That reads as a detail-page hole. 🖥 It measures **403** —
because [`web.php:1023`](../routes/web.php#L1023) is a legacy deep-link that *redirects* into the gated
list. **Destination, not status.** Reporting the route table alone would have produced a false finding.

**Navigation — F-11 for this role.** 🔬 16 `/app` links rendered, 14 reached, **2 dead ends**:
"Owners" → `/app/owners` (403) and "Payout run" → `/app/payouts/run` (403). Consistent with Phase 7's
retraction of the scaling claim: this is a **per-link filtering gap**, not a per-role cliff. Running
tally of measured dead ends: 1 · 2 · 0 · **2**.

---

## 6. Phantom ratio — **1 of 4**

Swept across `routes/`, `app/`, `resources/`, `config/`, `database/`, separating code from comments.

| Permission | Route middleware | Other enforcement | Verdict |
|---|---|---|---|
| `housekeeping.manage` | **8** `can:` sites | `HousekeepingBoard.php:148` → `canAssign` (the UI gate) | ✅ REAL |
| `housekeeping.view` | **0** | **none** | 🔴 **PHANTOM** |
| `schedule.view` | **3** | nav-item gate, `shell.blade.php:71` | ✅ REAL |
| `units.view` | **1** | `OperatorSearch.php:44` (search facet), nav-item gate | ✅ REAL |

### F-14 — CONFIRMED and sharpened

🔬 Outside `config/authorization.php`'s catalogue and role grants, `housekeeping.view` appears
**exactly twice** — and both are the *same comment*, [`web.php:1231`](../routes/web.php#L1231):

> `// housekeeping.view-only). The controller carries no authorize(), so this is the actor gate (#20/#30).`

Zero routes, zero `can()` calls, zero `@can` directives, zero service checks. 🔬 It is nonetheless
**granted in six role blocks** in `config/authorization.php` (lines 178, 195, 223, 242, 248, 274).
Six roles carry a permission that gates nothing anywhere in the codebase.

This is the trap the canon names: *"a source-scanning guard reads its own needle"* — the only evidence
that the permission does anything is a sentence describing it.

Related: 🔬 the **Housekeeping nav item itself is gated on nothing** —
`['housekeeping', 'Housekeeping', <icon>, route('app.housekeeping')]`, with no permission argument,
unlike its neighbour `unit-requests` which carries `auth()->user()?->can('units.view')`. Consistent with
`GET /app/housekeeping` being ungated: the board is visible and reachable to every back-office role.

---

## 7. Corrections this phase makes to the programme's own record

1. **F-13 — recharacterised, one half retracted.** Not "zero controls and no explanation." It is the
   **Board** view specifically; the List view (the default) has 7 working controls; the Board *does*
   explain itself ("Assign to start" ×7). The real defect is that the explanation is false and that the
   view choice is sticky. → folded into **F-16**.
2. **F-14 — confirmed, and enlarged.** "Enforced nowhere" is right. Add: granted to **six** role blocks,
   and its only two textual occurrences are one and the same comment.
3. **The acid test's premise does not hold.** The brief anticipated the answer might be no. It is yes.
   The board is usable by the role it was built for.
4. **The "294px off-screen pinned action" does not reproduce.** At 375px all 7 actions are fully on
   screen behind a sticky action column, and body overflow is 0. Carried forward as a *checked and clear*
   item, not an open one.

---

## 8. Hand-forward

**Findings now open:** F-1 · F-2 · F-3 · F-4 · F-5 · F-6 · F-7 · F-8 · F-9 · F-10 · F-11 (per-link) ·
F-12 · F-13 (→ F-16) · F-14 · **F-15** · **F-16** · **F-17**. None fixed — this programme audits only.

**Baselines for Phase 9** (these moved; do not reuse Phase 7's):
`audit_events` **156** · `domain_events` **195** · `unit_status_logs` **6**.
Unchanged: housekeeping_tasks 7 · units 8 · reservations 42 · payments 16 · owner_ledger 29
(sum 730640) · role_has_permissions 260.

**Roles remaining:** Reservation Agent · Revenue Manager · Maintenance · Marketing Manager ·
Guest Support Agent — then the owner and guest realms.

**Carry forward:**
- **The phantom ratio.** Running: Housekeeping **1 of 4**. Sweep `routes/` *and* `app/` *and*
  `resources/` — and separate comments from code, or the comment answers for the code.
- **Ask F-15's question of every state machine, not just this one.** The pattern is "the target is
  validated, the *current* state is not." Maintenance tickets are the obvious next candidate: the
  Maintenance role walks next and `/app/maintenance/{ticket}` is ungated and reachable even from here.
- **Ask F-16's question wherever one screen has two views.** Two renderings of one rule will drift, and
  the sticky-preference mechanism (`localStorage`) means a user can be parked in the losing one
  indefinitely with no server-side trace.
- **Ask F-17's question of every write a role actually performs.** Not "does the module audit" — it did,
  three times, in the same file — but "does *this* transition audit, and can the actor be recovered."

**Method notes worth keeping:**
- A selector can match a hidden twin. Both board views render into the DOM simultaneously; `page.$()`
  returned the kanban copy of a control while the list copy was the visible one. **Filter to
  `visible=true` before clicking, or you test a control no user can press.**
- **Counting submit buttons misses Livewire.** A `wire:click` Save is not `type=submit`. The
  email-template editor had to be re-probed for `wire:` attributes before "zero write controls" was safe
  to write down.
- **A stored preference silently changed a later measurement.** `/app/housekeeping` measured 7 controls
  early and 0 later in the same session — because an intervening click had written `vy:hk:view=board`.
  A measurement that contradicts an earlier one is a finding about state, not noise; re-derive it before
  reporting either number.
