# The three status ladders — and the per-transition decisions only the founder can make

> ⚠️⚠️ **BUILT 2026-09-19 (FIX 33): R4 and R1 are now enforced in the ACTIONS that write the status.**
> `CheckOutReservation::ALLOWED_FROM = ['checked_in']` — `confirmed` is refused, and `DemoSeeder` was changed
> to check in first at **all seven** of its checkout sites rather than the guard being widened to suit it.
> `CheckInReservation::ALLOWED_FROM = ['confirmed']` is unchanged; R1's refusal **message** now names the way
> out. ⚠️⚠️ **R3 IS NOW BUILT (FIX 34)** — after FIX 33 stopped on it, the founder ruled RECORD THE SEND, THEN REVERSE AND DISCLOSE. `ReverseCheckOut` is a separate named action (a plain check-in still cannot reverse a checkout), `guest_review_invites` records every outcome so an absent row can only mean *predates the record*, and `ReviewService::isCompleted()` now asks *has ever completed a stay* so the invitation survives the correction. ⚠️ The original stop said — the checkout emails the guest a review request that cannot be
> recalled, leaves no record that it was sent, and the undo breaks the very link it sent (a checked-in stay is
> IN_HOUSE, not PAST, so the review 404s). **A false undo is worse than none; the remaining question is the
> founder's and is stated in §4.**
>
> ⚠️ **H1 WAS QUESTIONED AND UPHELD (2026-09-19).** `pending → clean` — marking a room clean without ever
> starting it — was put back to the founder because this project's own fail-safe rule points the other way
> (the wrong permission fails SILENTLY: a room reaches guest-ready with no record it was cleaned, while the
> wrong refusal costs one click and says so). **The founder re-affirmed it. It stays permitted.** Recorded
> because a rule that was challenged and upheld is worth more than one never examined — do not re-open it.
>
> ⚠️⚠️ **RULED 2026-09-19 — the answers are in `docs/AUDIT-SUMMARY.md` §4.** R4 **REFUSE** (`confirmed →
> checked_out`, build queued — it will redden `ReservationLadderTest`, deliberately) · R6 **LEAVE THE OTA
> AUTHORITATIVE** (pinned) · R-HK-11 **KEEP THE LADDER PERMISSIVE** (pinned) · H4 **PERMIT REOPEN-TO-PENDING,
> DELIBERATELY** (pinned). ⚠️ **R1 and R3 were NOT put and remain open.**
>
> ⚠️⚠️ **DECIDE FROM `docs/AUDIT-SUMMARY.md` §4, NOT FROM HERE.** §4 carries every transition in ONE table
> with today's measured default beside it. **This file is the reasoning behind those rows** — why each default
> was chosen, and what the two passes disagreed about. ⚠️ **Counts in this file are of its date; the rows in
> §4 are the count.**

**2026-09-13.** Three state machines in this product are now bounded the same way: the guard lives in the
layer that performs the write, every entrance meets it, and the controller holds no status logic.

| ladder | guard | closed by |
|---|---|---|
| Maintenance ticket | `MaintenanceService::assertTransition()`, 4 call sites | pre-existing — **measured exemplary, the reference** |
| Housekeeping task | `HousekeepingService::assertTransition()`, 1 writer | FIX 03 (F-15) |
| Reservation check-in / check-out | `CheckIn/CheckOutReservation::assertTransition()`, the 2 writing actions | FIX 04 (F-18) |

⚠️ **What FIX 04 actually closed:** reviving a **terminal** reservation. `cancelled` and `no_show` can no
longer reach `checked_in` or `checked_out`, and a `checked_out` stay can no longer walk back to `checked_in` —
the three states `AssignmentService::TERMINAL_STATUSES` already declares final.

⚠️⚠️ **THE PARAGRAPH ABOVE IS FIX 04's STATE AND TWO OF ITS CLAIMS HAVE SINCE BEEN RULED THE OTHER WAY —
corrected FIX 42.** It read *"a `checked_out` stay can no longer walk back to `checked_in`"* and
*"`confirmed → checked_out` stays permitted (R4 below)"*. 🔬 Both are now false in the code: **R3 was ruled
PERMIT-WITH-REVERSAL and BUILT (FIX 34)** — `ReverseCheckOut` is routed at
`front-desk/{reservation}/reverse-checkout` — and **R4 was ruled REFUSE and BUILT (FIX 33)**, which removed
`confirmed` from `CheckOutReservation::ALLOWED_FROM`, now `['checked_in']`. ⚠️ The R3 and R4 answer rows
below were correct throughout; only this prose went unswept.

⚠️⚠️ **THIS DOCUMENT EXISTS FOR ONE REASON: both fixes deliberately declined to decide what SHOULD be legal.**
A guard that invents policy is worse than a missing guard, because it looks authoritative. Below is every move
either fix left open, in one table, so a single sitting can answer them all.

---

## 1 · ⚠️ The two passes chose OPPOSITE defaults, and that was deliberate

| | FIX 03 · housekeeping | FIX 04 · reservations |
|---|---|---|
| default where the code was silent | **permissive** — 11 moves left working | **restrictive** — backward moves refused |
| why | nothing in the code declared any housekeeping state final, and refusing a legitimate move breaks a cleaner's shift | 🔬 `AssignmentService::TERMINAL_STATUSES = ['cancelled','no_show','checked_out']` — **the codebase already declares these three final**, and both live callers already required exactly the source the guard now allows |

⚠️ **The reservation map changed NO reachable behaviour.** `FrontDeskActionController` already required
`status === 'confirmed'` to check in and `'checked_in'` to check out; the walk-in path required `'confirmed'`.
FIX 04 moved that same bound down to the layer that writes, where a job or a future caller also meets it.

**So the restrictive default was derived, not chosen.** The permissive tie-break applies where the code is
silent; here it speaks.

---

## 2 · ⚠️⚠️ THE OPEN DECISIONS — the rows are the count

### Reservations (FIX 04) — five, each with a commercial consequence

⚠️ **A sixth, R6, was added by FIX 14 and is now transcribed into `AUDIT-SUMMARY.md` §4** (it was not, until FIX 31) — an inbound channel rewriting a stay the
guest is physically in. It is a reservation decision but belongs beside the writer it concerns.

| # | the question | move | today | if you say yes |
|---|---|---|---|---|
| **R1** ✅ **RULED — REFUSE (2026-09-19)** | May a **cancelled** booking be reinstated when the guest changes their mind? | `cancelled → checked_in` | ❌ refused | the room may have been resold; reinstating needs an availability re-check, not just a status write |
| **R2** | May a **no-show** become a late check-in? | `no_show → checked_in` | ❌ refused | the commonest real-world case in the list — a guest arriving at 01:00 after the night audit ran |
| **R3** ✅ **RULED — PERMIT + REVERSE; BUILT (FIX 34)** | May a **check-out be reversed** (wrong guest, same day)? | `checked_out → checked_in` | ✅ **via `ReverseCheckOut`, with disclosure** | ⚠️ the departure already created a cleaning task and dirtied the unit; reversing the status does not reverse those |
| **R4** ✅ **RULED — REFUSE (2026-09-19), BUILT** | May a stay be **checked out without ever being checked in** (express departure)? | `confirmed → checked_out` | ❌ **refused since FIX 33** | ⚠️ see below — this one was measured, not reasoned, and it changed the answer |
| **R5** | May an **enquiry or tentative** booking check in directly? | `enquiry/tentative → checked_in` | ❌ refused | skips confirmation, so a stay exists with no confirmed sale behind it |

### ⚠️⚠️ R4 deserves its own paragraph, because the first map got it wrong

Phase 9 reported `confirmed → checked_out` as **half of F-18** — "a booking that was never checked in was
checked out". FIX 04's first map therefore refused it. That map **reddened 41 tests across five certified
suites and broke `migrate:fresh --seed`.**

🔬 The cause: **`DemoSeeder` performs this transition twice**, through this very action, never checking in
first — [:605](../database/seeders/DemoSeeder.php#L605) (the past stay that makes the review flow demoable)
and [:700](../database/seeders/DemoSeeder.php#L700) (the walk-in). The seeder is product code and is named in
the derivation sources; the first derivation simply did not check it.

⚠️ **The guard was not widened to make tests pass — the map was re-derived after reading a source that had
been missed.** The distinction matters, and the two look identical from the outside, which is why it is
recorded here rather than quietly corrected.

**So R4 is genuinely open**: an express checkout with no arrival record is something the product does today,
and a test now asserts it, so tightening it later is a deliberate act that breaks a test rather than a silent
narrowing. ⚠️ If you rule it illegal, the seeder must check in first — a seeder change, not a guard change.

⚠️ **Already answered by the existing code, listed so it is not re-opened by accident:** a **checked-in stay
MAY be cancelled** (`CANCELLABLE` includes `checked_in`) and that is unchanged.

### Housekeeping (FIX 03) — eleven, all currently permitted

| # | the question | moves | today |
|---|---|---|---|
| **H1** | May a room be marked clean without ever being started? | `pending → clean` | ✅ permitted |
| **H2** | May a cleaned or inspected room go backward? | 6 moves | ✅ permitted |
| **H3** | May the same status be re-submitted? | 4 moves | ✅ permitted — ⚠️ **and it RE-STAMPS `completed_at` and `inspected_by`**, invariant #29's "a second termination is a rewrite" shape in a new place |
| **H4** | Should a task be re-openable to `pending`? | 4 moves | ❌ not over HTTP |

⚠️ **H1 carries a tension worth re-stating.** The brief for FIX 03 said choose permissive, and it did. This
project's own canon says *the fail-safe direction is set by which error is silent* — and wrongly refusing
`pending → clean` costs one extra click and says so, while wrongly permitting it lets a room reach guest-ready
with no record it was cleaned, silently.

---

## 3 · Should the three guards be generalised into one? — **argued, and the answer is no**

**For:** §N.8f, one definition per concept; this project has twice found byte-identical implementations of one
concept, and three copies of a shape will drift.

**Against, and this is the stronger case:** what is duplicated is **six lines of `in_array` and a throw**. What
is *not* shareable is the part that carries all the risk — the map itself, which is irreducibly per-domain. The
three also differ in a way that is meaningful rather than accidental:

| ladder | how the legal set is expressed | why |
|---|---|---|
| Maintenance | `allowedFrom` passed **per call site** (4 sets) | one service, four distinct verbs |
| Housekeeping | a **const map keyed by target** | one writer, many targets |
| Reservations | a **const list per class**, one target each | two single-purpose actions |

Extracting a shared helper would centralise the trivial half and leave the dangerous half distributed —
precisely inverting what §N.8f is for. **Recommendation: do not merge the code. Keep ONE shared surface for the
part that actually drifts — the policy — which is this document.**

⚠️ Reported, not built. Building it was explicitly out of scope for FIX 04.

---

## 4 · ⚠️ A fourth writer exists, and it is NOT covered by any of the three guards

🔬 `BookingImportService::build()` sets `'status' => 'confirmed'` unconditionally, and `modify()` passes it to
`AssignmentService::reimportBooking()`, which does a raw `$reservation->update($attributes)`.

**So an inbound OTA modification rewrites the status to `confirmed` whatever it was** — including on a booking
that is `checked_in` or `checked_out`. A guest physically in the room would show as not yet arrived.

⚠️ **NOT FIXED BY FIX 04, deliberately, for three reasons:** it is a separate finding from F-18 (which is about
the two check-in/out actions); guarding it means deciding whether an OTA may revert a live stay, which is a
founder ruling with commercial consequence; and a guard that starts throwing inside an external-sync path is
the production outage nobody can diagnose from the interface. **It needs its own pass and its own ruling.**

⚠️ Reachability: the Channex integration is account-gated and not live (`CHANNEX_FAKE=true`), so this is latent
today — exactly as F-18 was latent before it was fixed.

### ✅ CLOSED for three of four statuses by FIX 14 (2026-09-13) — and the fourth is R6, below

**Numbered F-29 and fixed in two layers, both copied from `cancel()` rather than designed:**

* **`BookingImportService::modify()` — an idempotent NO-OP, recorded.** Exactly `cancel()`'s shape and for
  exactly its stated reason: this runs on the retrying webhook path, where a throw becomes
  `webhook_events` FAILED → retry → `dead_letter`. ⚠️ **It records where `cancel()` is silent** —
  `reservation.ota_modification_refused`, `actor_type` **`system`** (a sync has no user), naming the channel,
  PII-safe.
* **`AssignmentService::reimportBooking()` — an authoritative THROW**, before the transaction, in
  `terminate()`'s exact shape. ⚠️ **The guard is on the RAW WRITER so it covers every caller**, not only the
  one entrance that exists today — a guard on one entrance is precisely how F-18 and F-29 both hid.

⚠️ **The split is not a design choice of this pass. It is the author's own, written in `cancel()`:
"Idempotent here; authoritative there."**

⚠️ **FIX 04's reason 2 still stands for `checked_in`, and that is why this closure is three-quarters, not
whole.** The sibling's rule is `TERMINAL_STATUSES` — `cancelled`, `no_show`, `checked_out`. **`checked_in` is
not terminal**, so copying the sibling does not reach it, and deciding it is the founder's:

| # | question | the move | today |
|---|---|---|---|
| **R6** | May an inbound channel modification rewrite a stay **the guest is physically in**? | `checked_in` → `confirmed` (plus dates, price and unit lines) | ⚠️ **STILL PERMITTED** — unguarded, exactly as before FIX 14 |

⚠️ **R6 is the symptom §4 opened with** — *"a guest physically in the room would show as not yet arrived"* —
and it is the one case the fix does **not** close. It is listed here rather than decided. ⚠️ **The commercial
argument runs both ways**, which is why it is a ruling: a real OTA can legitimately extend a stay mid-visit,
and refusing that would drop a genuine booking change on the floor; but an unguarded rewrite un-arrives a
guest who is standing in the room.
