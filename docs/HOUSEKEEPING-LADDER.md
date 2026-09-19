# The housekeeping status ladder — what it now refuses, and the eleven moves the founder must rule on

**2026-09-13 · FIX 03 · closes F-15.** The status route validated the *target* and never the row's *current
state*, so a `pending` task jumped straight to `inspected`: a dirty room became guest-ready in one request,
`started_at` still NULL, never started, never cleaned — and `inspected_by` named the person who skipped both.

⚠️ **This document exists because the fix is small and the open question is large.** The guard refuses two
moves. Eleven more are genuinely ambiguous, and **this pass deliberately did not decide them.**

## 1 · The evidence base — derived from the code, not from intuition

| source | what it says |
|---|---|
| `housekeeping_tasks.status` enum ([migration:23](../database/migrations/2026_07_02_001600_create_housekeeping_tasks_table.php#L23)) | `pending` · `in_progress` · `clean` · `inspected` — four, no more |
| `HousekeepingBoard::NEXT_ACTIONS` | `pending→in_progress` · `in_progress→clean` · `clean→inspected` · `inspected→∅` |
| Both views' submit value | `$card['next'][0]['to']` — **only ever the single forward step above** |
| `GenerateDepartureCleanTask` + factory + enum default | tasks are **created** at `pending` (creation, not a transition) |
| `HousekeepingActionController::ALLOWED` | targets `in_progress`, `clean`, `inspected` — **`pending` is not a reachable target over HTTP** |

🔬 **There is exactly ONE writer of a status transition:** `HousekeepingService::updateTaskStatus()`, with one
production caller. No job, no console command, no bulk action, no model event writes a status. The guard is in
that method, so every entrance meets it.

## 2 · The map — all sixteen moves, classified

`L` legal (the product performs it today) · `✋` **REFUSED by the guard** · `?` ambiguous, **permitted unchanged**

| from ↓ / to → | `pending` | `in_progress` | `clean` | `inspected` |
|---|---|---|---|---|
| **`pending`** | `?` same | **L** | `?` skips a marker | **✋ F-15** |
| **`in_progress`** | `?` backward | `?` same | **L** | **✋** |
| **`clean`** | `?` backward | `?` backward | `?` same | **L** |
| **`inspected`** | `?` backward | `?` backward | `?` backward | `?` same |

**3 legal · 2 refused · 11 ambiguous-and-permitted.** The four moves *to* `pending` are service-only — the
route rejects that target with 422, and this pass did not change that.

### Why exactly those two are refused

⚠️ **`in_progress` is a PROGRESS MARKER; `clean` is an ASSERTION.** Skipping the marker loses nothing a human
claimed — a housekeeper who cleans a room without pressing Start has still done the job, and refusing them
would break a shift. Skipping `clean` destroys the only record that **anyone ever declared the room cleaned**,
and then `inspected_by` names the sole actor — collapsing the cleaner/inspector separation that **F-17** shows
is recorded nowhere else (`housekeeping_tasks` has no `started_by` and no `completed_by`).

So `inspected` is reachable only from `clean` (the real step) and from `inspected` (a double-press, unchanged).

## 3 · ⚠️⚠️ THE ELEVEN RULINGS — decisions this pass refused to make

Each has a defensible answer in both directions and **the code does not currently say**. Per the brief's rule,
where a choice was forced the **permissive** reading was taken, so today's behaviour is preserved until ruled.

⚠️⚠️ **THESE `R`-NUMBERS ARE LOCAL TO THIS FILE. THEY ARE NOT THE RESERVATION-LADDER RULINGS (F-63).**
🔬 `R1`–`R4` below are the HOUSEKEEPING questions, which `AUDIT-SUMMARY.md` §4 calls **H1**–**H4** and which
were ruled together under **R-HK-11** (*keep the ladder permissive*) and **H4**. ⚠️ The reservation ladder's
`R1`–`R6` live in `docs/STATUS-LADDERS.md` and are **different questions with different answers** — this
file's `R2` is *"may a clean room go backward"* (permitted), **not** *"may a no-show check in"* (refused,
and ⚠️ **still unruled** — see `STATUS-LADDERS.md` §2a). **Do not read an answer here as an answer there.**

| # | the question | moves | permitted today | what it costs |
|---|---|---|---|---|
| **R1** | May a room be marked clean without ever being started? | `pending→clean` | ✅ yes | `started_at` stays NULL forever; the board's "Elapsed" bar has no basis; guest-ready is then two clicks from dirty |
| **R2** | May a cleaned or inspected room go backward? | 6 moves | ✅ yes | genuinely needed (a clean room found dirty, a wrong inspection) — but nothing records *why* it reversed |
| **R3** | May the same status be re-submitted? | 4 moves | ✅ yes | ⚠️ **it RE-STAMPS `completed_at` and `inspected_by`** — the "a second termination is a REWRITE, not an append" shape of invariant #29, in a new place |
| **R4** | Should a task be re-openable to `pending` at all? | 4 moves | ❌ not over HTTP | a task closed in error can only be moved forward or sideways, never reset |

⚠️ **R1 is the one I would put in front of you first, and I want to name a tension rather than hide it.** The
brief's rule says choose the permissive reading, and I did. But this project's own canon says *the fail-safe
direction is set by which error is SILENT* — and here the errors are not symmetric:

* wrongly **refusing** `pending→clean` → the housekeeper sees an explicit message and presses Start first. **Loud, recoverable, one extra click.**
* wrongly **permitting** it → a room reaches guest-ready with no record it was ever cleaned. **Silent.**

**By the canon rule this move should be refused; by the brief's rule it should be permitted.** It is permitted,
because the brief governs this pass — but it is a ruling, not a conclusion.

⚠️ **R3 is the second.** It is not strictly a ladder question — the transition is legal, the *write* is the
problem — so fixing it means making a same-status move a no-op, which `unassign()` in this very service already
does ("no-op when already unassigned, so a stray/duplicate undo is harmless"). The precedent exists in-file.

## 4 · What this teaches the reservation-ladder fix (F-18)

1. **Derive the map from `NEXT_ACTIONS`-equivalents first.** The UI's own forward-move table *is* the product's
   statement of the legal ladder, and it is far narrower than the controller's accept-list. The gap between
   those two is precisely the defect surface.
2. **Count the writers before writing the guard.** Housekeeping had exactly one, which is why this fix is 61
   lines. Reservations will not — and a guard on one entrance is not a guard.
3. ⚠️ **Expect the ambiguous set to dwarf the illegal set.** Here it was 11 against 2. Budget the pass for
   *writing up rulings*, not for writing guards.
4. ⚠️ **Distinguish MARKERS from ASSERTIONS.** That distinction did all the work here and should transfer:
   skipping a progress marker is a workflow shortcut; skipping a state where a human asserted something
   destroys a record. Reservations have both.
5. **No view offered a refused move**, so the guard broke no UI path and no fixture — but that also means the
   refusal is unreachable by clicking, and must be proven by a crafted POST and a stale-page race.
