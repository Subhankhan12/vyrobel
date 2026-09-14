# AUDIT — PHASE 9 · RESERVATION AGENT · THE STATE-MACHINE PHASE

**Role:** Reservation Agent · `demo-agent@vyrobel-demo.test` (Sofia Almeida)
**Realm:** operator (`/app`, `web` guard)
**Instrument:** MCP Playwright → `chrome-headless-shell` via the project `.mcp.json`
**Date:** 2026-09-12
**Application code changed:** none — verified, §8.
**Suite not run:** no application code changed, so it is not required. Stated per the brief.

**Evidence tiers.** 🔬 MEASURED · 🖥 BROWSER · 📖 ASSERTED · 📄 RECORDED. **Only 🔬 and 🖥 are facts.**

**Permissions.** 🔬 **11 — config and DB agree exactly.**
`availability.view · folios.view · guests.manage · guests.view · rates.view · reservations.cancel ·
reservations.create · reservations.manage · reservations.restriction_override · reservations.view ·
schedule.view`. Holds `reservations.cancel`; does **not** hold `payments.record`,
`reservations.checkin` or `reservations.checkout` — the mirror of Front Desk, as the brief reported.

---

## 0. THE #1 QUESTION — **IS THE RESERVATION LADDER BOUNDED? YES, AT THE SURFACE.**

**No illegal transition succeeded, and no money moved.** F-15's shape does **not** reach the reservation
state machine through the product's own controls.

🔬 The real state machine, read from code before probing — it is **wider than the brief's six**:

| | |
|---|---|
| Statuses | `enquiry` · `tentative` · `confirmed` · `checked_in` · `checked_out` · `cancelled` · `no_show` |
| `CANCELLABLE` ([`ReservationLifecycleController:63`](../app/Http/Controllers/Operator/ReservationLifecycleController.php#L63)) | `enquiry, tentative, confirmed, checked_in` |
| `NO_SHOWABLE` ([:86](../app/Http/Controllers/Operator/ReservationLifecycleController.php#L86)) | `confirmed` **only** |
| check-in ([`FrontDeskActionController:41`](../app/Http/Controllers/FrontDeskActionController.php#L41)) | requires `status === 'confirmed'` |
| check-out ([:54](../app/Http/Controllers/FrontDeskActionController.php#L54)) | requires `status === 'checked_in'` |
| `TERMINAL_STATUSES` ([`AssignmentService:73`](../app/Services/AssignmentService.php#L73)) | `cancelled, no_show, checked_out` |

**Five illegal transitions, each pressed through the page's own form** (real session, real CSRF token,
no invented request), then read off the row:

| probe | POST status | what the screen said | row |
|---|---|---|---|
| cancel a `cancelled` | **302 PRG** | "That reservation can't be cancelled — it's already closed." | unchanged |
| cancel a `checked_out` | **302 PRG** | same | unchanged |
| no-show a `no_show` | **302 PRG** | "…can't be marked a no-show — only a confirmed booking can be." | unchanged |
| no-show a `checked_out` | **302 PRG** | same | unchanged |
| no-show a `cancelled` | **302 PRG** | same | unchanged |

🔬 **302, not 403 — the permission PASSED and a STATE guard refused.** That distinction is the whole
point: a 403 would have proved nothing about the ladder.

🔬 **Row state after all five: byte-identical.** Status, `cancelled_at`, `cancellation_reason`,
`guest_checked_in_at`, `balance_minor`, `payment_status`, every `reservation_units` line state, every
payment, and the per-reservation history count — all four probe reservations unchanged, and
`availability_sold` still **105**.

**And the UI never offers what the write refuses** (#30's "gate the control too"), 🖥 verified per status:

| detail page for a… | offers Cancel | offers Mark no-show |
|---|---|---|
| `confirmed` | ✅ yes | ✅ yes |
| `checked_out` · `cancelled` · `no_show` | ❌ no | ❌ no |

The refusals also *explain themselves in the rule's own terms*. Set against Phase 8's board, which
stated a rule the server did not have, this is the same problem solved correctly.

⚠️ **NOT EXAMINED:** `CANCELLABLE` includes `checked_in`, and the seeded world holds **zero** `checked_in`
reservations (🔬 33 confirmed · 7 checked_out · 1 cancelled · 1 no_show). That branch was not exercised.

---

## 1. 🟠 F-18 (MEDIUM) — **F-15's shape DOES exist here, one layer below the HTTP surface**

The bound is real at the controller. It is **absent from the actions that do the writing** — and the
asymmetry with cancel is the finding.

```php
CheckInReservation::handle()    if ($reservation->status === 'checked_in')  return $reservation; // idempotent
CheckOutReservation::handle()   if ($reservation->status === 'checked_out') return $reservation; // idempotent
```

Each guards only *target-equals-current*. **Neither checks a legal SOURCE state** — precisely F-15's
shape ("validate the target, never the current state").

**Proven, not reasoned.** Each call made inside a transaction that was **always rolled back**, with the
tenant context set so the probe actually reached the layer under test:

| layer · call | result |
|---|---|
| **SERVICE** `AssignmentService::cancelReservation()` on a `cancelled` row | 🔬 **REFUSED — `ReservationAlreadyTerminalException`** |
| **ACTION** `CheckOutReservation::handle()` on a `confirmed` row, never checked in | 🔬 **ACCEPTED → status became `checked_out`** |
| **ACTION** `CheckInReservation::handle()` on a **`cancelled`** row | 🔬 **ACCEPTED → status became `checked_in`** |

🔬 After rollback: `reservations` 42 · cancelled row still `cancelled` · confirmed row still `confirmed` ·
history 51 · audit 156 · domain 195. **Nothing persisted.**

**A cancelled booking was revived to `checked_in`, and a booking that was never checked in was checked
out — at the layer that performs the write.** Cancellation is bounded in its service (#29);
check-in/check-out are bounded only in their controller.

**Reachability today: none.** 🔬 Both live callers guard correctly —
`FrontDeskActionController` (the status checks above) and `ReservationCreateController:310`'s walk-in,
which requires `$reservation->status === 'confirmed'` **and** `can('reservations.checkin')`. So this is a
**latent defence-in-depth gap, not an exploitable hole** — and I am saying so plainly rather than
inflating it. It becomes live the moment a third caller appears: a queued job, an OTA inbound handler, a
Livewire action. The project's own doctrine is that this bound belongs in the service *because* "services
are called by jobs/tests with no user."

⚠️ Note the contrast that shows the omission is specific, not systemic: `CheckOutReservation` **does**
carry a money guard — it throws `OutstandingBalanceException` on an unpaid folio. The status guard is the
one that is missing.

---

## 2. 🟡 F-19 (LOW, but sits on a security decision) — the route comment is **stale and states the opposite of the truth**

[`routes/web.php:1176-1183`](../routes/web.php#L1176-L1183), in bold, immediately above the cancel route:

> ⚠️ THE STATUS GUARD LIVES IN THE CONTROLLER… **`AssignmentService::terminate()` has NO status guard:**
> re-cancelling an already-cancelled reservation **does not throw**, it silently OVERWRITES
> `cancelled_at`/`cancellation_reason`… **The proper fix is a guard inside terminate() itself (a service
> change — recorded as outstanding).**

🔬 **The guard exists** ([`AssignmentService:1035`](../app/Services/AssignmentService.php#L1035)) and
throws `ReservationAlreadyTerminalException` *before* the transaction. 🔬 **Proven above** — the call was
refused. `CLAUDE.md` records it as shipped **invariant #29**.

The comment describes a pre-#29 world and still calls the fix outstanding. It is load-bearing
documentation at exactly the spot an engineer reads before touching the lifecycle, and it tells them the
service cannot be trusted. Two plausible harms: someone removes the controller constants believing a
service guard is coming, or adds a duplicate guard — the "two computations of one concept" drift the
canon names (§N.8f).

**This is the canon's own failure mode** — *"A HANDOFF SURFACE DECAYS SILENTLY… nothing fails when it goes
stale."* Here the stale surface is a code comment, not a doc.

---

## 3. #2 — CANCEL: THE MIRROR OF PHASE 6 · **established by reading, deliberately NOT performed**

Phase 6 proved Front Desk is refused the cancel (403, `cancelled_at` NULL). This role **holds it** — 🔬
the POST returns **302, not 403**, so the permission genuinely passes. That is the controlled comparison,
and it is now closed.

**I did not perform a cancellation, and the reason is measured, not cautious:**

🔬 [`ReservationCancellationService::cancelWithPolicyRefund()`](../app/Services/ReservationCancellationService.php#L34):

```php
$refundable = CancellationPolicy::isRefundable($reservation, $on);
$this->assignment->cancelReservation($reservation, $reason);      // releases inventory, writes the trail
$refundedMinor = $refundable ? $this->refundEverythingPaid($reservation) : 0;   // ← REAL REFUND
```

**Cancelling CAN move money.** `refundEverythingPaid()` calls `FolioService::refundPayment()` — the
certified single refund path (#27), which per Phase 3 posts an **irreversible `owner_ledger` adjustment**.
🔬 The one `confirmed` reservation I could have used carries a succeeded payment of **36900 minor**.

🔬 **And there is no reverse.** A repo-wide sweep for `uncancel` / `reinstate` / `revive` /
`restoreReservation` and for any such route returns **nothing**. A cancellation additionally releases the
inventory line and writes three append-only trails. It is one-way.

**So the path is established by reading, and the judgement stands with the three before it** (Phase 2
suspension, Phase 7 statement finalization, Phase 3 refund). 🔬 `owner_ledger` is unmoved at
**29 / 730640**; `payments` at **16**; the 17 money-write routes remain unexercised by this phase too.

🔬 **The seeded world already contains the proof the path works**: the one `cancelled` reservation carries
`payment_status='refunded'` and a matched pair — `payment:succeeded:24600` **and**
`refund:succeeded:24600`. The cancellation→refund path is real and has run; it did not need me to run it
again on live data.

### The payment denial, by row state and correctly attributed

| probe | POST status | attribution |
|---|---|---|
| `POST /app/reservations/{r}/payments` | **403** | permission gate (`payments.record`) |
| `POST /app/reservations/{r}/refunds` | **403** | permission gate (`payments.refund`) |
| `POST /app/front-desk/{r}/check-in` | **403** | permission gate (`reservations.checkin`) |
| `POST /app/front-desk/{r}/check-out` | **403** | permission gate (`reservations.checkout`) |

🔬 **403 on the POST itself** — not 419, not 429, not a login bounce. 🔬 `payments` still 16, sum
unchanged; the folio row untouched. 🖥 And the control is gated too: `front-desk.blade.php:478-490` wraps
the payment control in `@can('payments.record')`, and no payment control rendered for this role.

---

## 4. #3 — DOES A TRANSITION WRITE A TRAIL? **YES — the opposite of F-17, and it names WHO**

Every reservation transition writes **three** records, and **two of them carry an actor**:

| | check-in | check-out | cancel / no-show |
|---|---|---|---|
| `domain_events` | ✅ `reservation.checked_in` | ✅ `reservation.checked_out` | ✅ `reservation.cancelled` / `.no_show` |
| `audit_events` (has `actor_id`) | ✅ | ✅ | ✅ |
| `reservation_status_history` | ✅ | ✅ | ✅ |

🔬 `reservation_status_history` columns: `id, organization_id, reservation_id, from_status, to_status,
**changed_by**, reason, created_at` — a dedicated, purpose-built actor column recording the *from* and
*to* status. `AssignmentService::recordStatusHistory()` even documents the boundary: *"`changed_by` →
users, so the actor is the OPERATOR (web guard) or null — NEVER a signed-in guest."*

**This is the answer F-17 could not give for housekeeping.** There, the status change wrote no audit row,
`domain_events` has no actor column, and the table had no `started_by`/`completed_by`. Here the same
question has two independent answers. **F-17 is the outlier, not the norm** — which strengthens it as a
finding rather than weakening it.

⚠️ **One honest caveat, and it is NOT a defect.** 🔬 All 51 seeded history rows have `changed_by` NULL,
and there are 🔬 **zero** `reservation.*` rows in `audit_events`. That is the **seeder** writing rows
directly rather than through the audited service paths — it is not evidence the trail fails. ⚠️ It also
means **I could not empirically confirm that a real signed-in transition populates `changed_by`**: this
role's only two state-machine writes are both irreversible, so there was no safe transition to perform.
**Established from code, NOT measured.** A later phase holding a reversible transition should close it.

---

## 5. #4 — TWO VIEWS, ONE TRUTH: **F-16's shape does NOT exist on the reservation surfaces**

🔬 Swept `reservations` · `calendar` · `front-desk` · `rack` · `search` · `guest-requests`:

| | measured |
|---|---|
| Surfaces offering cancel / no-show | **exactly one** — the reservation detail. Zero elsewhere. |
| `localStorage` keys written by any of them | **0** |
| `$persist` in the rendered markup | **absent** |
| View toggles | only front-desk's `arrivals`/`departures`/`inhouse` tabs — **not persisted** |

**There is no second view to disagree with, and no sticky preference that could strand anyone.** A reload
returns the desk to its default tab. F-16's mechanism is specific to the housekeeping board.

---

## 6. #6 — THE WALK: all 54, re-measured

🔬 54 = 45 plain + 9 parameterised — the same set Phase 8 walked, re-measured for this role.

| | DENIED | REACHED | DOWNLOAD | REDIRECTED |
|---|---|---|---|---|
| **54** | **22** | **28** | **3** | **1** |

🔬 Zero 500s. Downloads (all succeeded, captured as download events, not read as page status):
`app/schedule/pdf` · `app/security/export` · `app/reservations/{r}/folio.pdf` (this role holds
`folios.view`).

**Two open findings REPRODUCE on screens Phase 8 could not reach** (they were denied to Housekeeping):

- **F-7 CONFIRMED** — 🔬 `app/group-booking` and `app/reservations/create` render **zero `h1`**. Exactly
  the two screens F-7 named, now observed by a role that can reach them.
- **F-12 CONFIRMED** — 🔬 `app/maintenance/{ticket}` renders **two `h1`**. *(Phase 8 reached this screen
  but measured only the first heading on parameterised routes, so it did not catch the duplicate — a gap
  in my own Phase 8 instrument, corrected here.)*

**§N.32.2 confirmed from the other side.** `app/inbox/{thread}` is ungated in the route table and is a
legacy deep-link that redirects into the gated `app/inbox`. Phase 8 measured **403** (Housekeeping lacks
`guests.view`); this role holds it, so the same route measures **REACHED**. 🔬 Identical route, opposite
verdict, decided entirely by the destination. **Destination, not status** — demonstrated twice now.

**Navigation.** 🔬 20 links rendered, 18 reached, **2 dead ends** — "Owners" → `/app/owners` and
"Payout run" → `/app/payouts/run`, both 403.

⚠️ **And this is the sharpest available evidence for Phase 7's retraction.** This role holds **11**
permissions; Housekeeping holds **4**. Both produce **exactly 2** dead ends, and *the same two*. Dead ends
do not scale with permissions lacked. Running measured tally: **1 · 2 · 0 · 2 · 2**. No prediction made.

### Phantom ratio — **1 of 11, and it is already registered**

🔬 Swept routes **and** `app/` **and** `resources/`, comments separated from code:

| permission | routes | code sites | verdict |
|---|---|---|---|
| `folios.view` · `guests.manage` · `guests.view` · `rates.view` · `reservations.cancel` · `reservations.create` · `reservations.manage` · `reservations.view` · `schedule.view` | 1–12 | 0–17 | ✅ REAL |
| `reservations.restriction_override` | **0** | **2** — incl. [`AssignmentService:739`](../app/Services/AssignmentService.php#L739) | ✅ **REAL — service layer** |
| `availability.view` | 0 | 0 | ⚠️ unenforced — **but KNOWN and registered** |

⚠️ **`reservations.restriction_override` again proves the route-only check is wrong.** A routes-only sweep
calls it a phantom; it is enforced in the service. This is the third phase to catch it.

⚠️ **`availability.view` is NOT a new finding.** The brief's instruction to disbelieve a zero was right:
it is in [`docs/PHANTOM-PERMISSIONS.md:34`](PHANTOM-PERMISSIONS.md) (category A — "read through the
calendar and rack → `rates.view`/`reservations.view`"), asserted by a **test**
(`tests/Feature/PermissionEnforcementContractTest.php:30`), listed in `KNOWN-LIMITS.md`, and already
marked KNOWN by Phase 6. Reporting it would have been a double-count.

---

## 7. ⚠️ CORRECTIONS — including to my own Phase 8 report

**7.1 · F-14's framing was wrong, and I wrote it.** Phase 8 said `housekeeping.view` appears "exactly
twice — and both are the same comment," and Phase 6 said "its only occurrence in the entire codebase is a
route comment." 🔬 **Both are false.** `housekeeping.view` is:

- [`docs/PHANTOM-PERMISSIONS.md:35`](PHANTOM-PERMISSIONS.md) — **a dedicated register**, category A,
  with the exact explanation: *"the board has no gate at all; every write → `housekeeping.manage`"*
- `tests/Feature/PermissionEnforcementContractTest.php:31` — **a test that asserts the non-enforcement**
- `PHANTOM-PERMISSIONS.md:74` — a written discussion of its consequence

**My Phase 8 sweep covered `app/`, `resources/`, `config/`, `database/` and `routes/` — it excluded
`tests/` and `docs/`, which is precisely where the answer lived.** The permission still gates nothing, and
the register itself says it "needs a ruling" — so **F-14 stays open as a founder ruling**, but it is a
*registered, tested, deliberate* non-enforcement, **not an undocumented oversight discovered by the audit**.
Phase 6 was right that it is not in `KNOWN-LIMITS.md`'s twelve; it was wrong that nothing else records it.

This is the canon's own rule — *"Verify before accepting a finding — including your own"* — landing on the
programme's own output.

**7.2 · The brief's premise did not hold, and that is the headline.** It anticipated the unbounded ladder
repeating on the reservation. Through the product's own controls it does **not**: every illegal transition
was refused by a state guard, with an explanatory message, writing nothing. The real defect is one layer
down and currently unreachable (F-18).

**7.3 · The brief's status list was incomplete.** It named six; 🔬 there are **seven** — `enquiry` and
`tentative` exist and are `CANCELLABLE`. Minor, but the map matters when the map is the deliverable.

**7.4 · My own Phase 8 instrument had a gap.** The parameterised-route walk recorded only the first `h1`,
so it could not have detected a duplicate. Corrected here; F-12 confirmed as a result.

---

## 8. #7/#8 — MEASUREMENTS, AND NOTHING CHANGED

🔬 Measured **before** any probe and **again at the end** — identical:

| table | before | after |
|---|---|---|
| `reservations` | 42 | **42** |
| `payments` (sum 880680) | 16 | **16** |
| `owner_ledger` (sum **730640**) | 29 | **29** |
| `audit_events` | 156 | **156** |
| `domain_events` | 195 | **195** |
| `reservation_status_history` | 51 | **51** |
| `unit_status_logs` | 6 | **6** |
| `availability` sold | 105 | **105** |
| `role_has_permissions` | 260 | **260** |

**Zero append-only residue this phase** — the five illegal transitions were refused before any write, and
every layer probe ran inside a rolled-back transaction. Phase 8's residue figures (156 · 195 · 6) are
**still the correct baseline for Phase 10**.

🔬 **All 14 web-guard demo accounts authenticate** (tested through `Auth::attempt`, not a hash compare) —
zero failures. 🔬 `DemoDataSafetyGuard::DEVELOPMENT_ENVIRONMENTS` is still `['local','testing']` and still
throws everywhere else.

🔬 **Nothing fixed. No application code changed** — `git diff --name-only` filtered by session mtime
returns **zero tracked files**. The only repo writes are this document and the harness's `.remember/`
bookkeeping. F-1 … F-17 stay open; F-18 and F-19 are added.

---

## 9. NEAR-FALSE-POSITIVES — five new, **23 total**

19. **"429 THROTTLE" from a regex over the page body.** My rejection-attribution matched the digits
    `429` (and other prose) anywhere in the returned HTML, labelling a state refusal a throttle. Fixed by
    reading the **POST's own status** with `redirect:'manual'` (302) and the **rendered flash element** —
    and above all by reading the row. **Attribute a rejection from the response, never from page text.**
20. **`availability.view` as a fresh phantom.** Already in a dedicated register, a test, and
    `KNOWN-LIMITS`. `KNOWN-LIMITS.md:143` even warns that *"a grep for `availability.view` matched prose"*.
    **Check the register before naming a phantom.**
21. **`TenantContextMissingException` read as a source-state refusal.** My first layer probe reported all
    three calls "REFUSED" — by the tenant guard, which fires first in a CLI context. The layer under test
    was never reached. **A refusal proves only which guard fired.**
22. **`ReservationCreateController:310` read as an unguarded second caller.** The raw grep looked like a
    bypass of the controller's status check; reading the surrounding lines shows it guards **both**
    `status === 'confirmed'` **and** `can('reservations.checkin')`. **Read the context, not the hit.**
23. **"Front desk correctly hides its controls for this role."** 🔬 The desk rendered **zero rows** on
    every date tried, so no row-level control could exist to hide. Recorded as **NOT EXAMINED**, not as a
    pass. **An absent control on an empty screen proves nothing.**

---

## 10. HAND-FORWARD

**Findings open:** F-1 … F-17, **plus F-18 (action-layer source guard) and F-19 (stale lifecycle comment)**.
F-7 and F-12 confirmed; F-14 re-framed (§7.1); F-11 measured at 2 dead ends for an 11-permission role.

**Baselines for Phase 10 — unchanged from Phase 8:** `audit_events` **156** · `domain_events` **195** ·
`unit_status_logs` **6** · `reservations` **42** · `payments` **16** · `owner_ledger` **29 / 730640** ·
`reservation_status_history` **51** · `role_has_permissions` **260** · `availability` sold **105**.

**Roles remaining:** Revenue Manager · Maintenance · Marketing Manager · Guest Support Agent — then the
owner and guest realms.

### ⚠️ Is the unbounded ladder a CLASS across the product?

**Partly — and the answer is more useful than a yes or no.** Two state machines are now measured and they
differ *by layer*, not by surface:

| machine | controller guard | service/action guard |
|---|---|---|
| Housekeeping task (F-15) | ❌ **none** | ❌ **none** |
| Reservation cancel / no-show | ✅ `CANCELLABLE` / `NO_SHOWABLE` | ✅ `terminate()` (#29) |
| Reservation check-in / check-out | ✅ status checks | ❌ **none (F-18)** |

**The pattern to hunt is not "is there a guard" but "is the guard in the layer that WRITES."** Every
state machine below should be checked at *both* layers, with a rolled-back service-level call, not only
through HTTP:

1. **Maintenance tickets** — 🔬 `MaintenanceService:354` writes `'status' => 'cancelled'` directly.
   **Phase 11's role owns this, and `/app/maintenance/{ticket}` is ungated and reachable even by
   Housekeeping.** The highest-value next target.
2. **Guest change requests** — `GuestChangeRequestService:104` writes `'status' => 'confirmed'`.
3. **Payouts** — `pending → processing → paid → failed`, plus the `held` status; money-bearing.
4. **Owner statements** — `finalize()` is irreversible (Phase 7); is there a source-state guard?
5. **Reviews** — moderation states (`reviews.manage`).
6. **Subscriptions** — trial → active → lapsed, and `SubscriptionStatus` **fails OPEN**.
7. **Rate proposals** — `pending → approved | declined | withdrawn` (#67).
8. **Owner expenses** — `pending → approved | queried` (#68), which moves the ledger.
9. **`reservation_units`** — `active | overbooked | cancelled`, the inventory spine.

**Method notes worth keeping:**
- **Probe a layer with the tenant context SET**, inside a transaction you always roll back. Without it the
  tenant guard answers first and every result reads as a refusal (#21).
- **A 302 is the interesting result, not the 403.** A 403 means the permission gate answered and the state
  machine was never consulted. For this role, cancel returned 302 and that is what made the phase possible.
- **Sweep `tests/` and `docs/` too.** F-14's correction was sitting in a test file and a register that my
  Phase 8 sweep excluded by construction (§7.1).
