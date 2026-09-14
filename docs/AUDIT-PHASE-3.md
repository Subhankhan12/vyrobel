# AUDIT PHASE 3 — the operator realm, as Property Owner

**2026-09-12.** 🖥 **Instrument: MCP Playwright** · HeadlessChrome/151 · `php artisan serve` on
`http://localhost:8000` (the `APP_URL` origin). ⚠️ **Validated in both directions before any finding.**
🔬 **Signed in as `demo-admin@vyrobel-demo.test` — the ONLY holder of `Property Owner`** — asserted at login
(landed `/app/dashboard`, 26 nav items, both demo properties visible).

### The four marks — only 🔬 and 🖥 are facts
**🔬 MEASURED** · **🖥 BROWSER** · **📖 ASSERTED** · **📄 RECORDED**

---

## ⚠️⚠️ 0 · READ THIS BEFORE QUOTING ANYTHING BELOW — THIS IS THE SURFACE, NOT THE PERMISSION SYSTEM

⚠️⚠️ **`Property Owner` is a WILDCARD role — it holds all 51 permissions.** So everything reached here is
**everything the realm offers**, and **NOTHING it is denied has been learned.** ⛔ **This phase proves
nothing whatsoever about the permission system.**

⚠️ **That exact misreading is how eight roles went unexamined for the life of this project.** The denied
half belongs to Phases 4+, and §8 below is its specification.

⚠️ **MULTI-TENANCY REMAINS NOT TESTED** — one organisation. ⚠️ **And see §7: the "five orphan permissions"
figure carried since Phase 0 is WRONG, and is corrected here.**

---

## 1 · ⚠️⚠️ THE MONEY LINE — drawn from source BEFORE a single click

🔬 **17 operator write routes touch money.** Classified **before** any browser write:

| class | routes | decision |
|---|---|---|
| ⛔ **ESTABLISH WITHOUT PERFORMING** | `POST reservations/{r}/refunds` · `POST payouts/create` · `POST payouts/pay` · `POST payouts/run` · `POST billing/{subscribe,cancel,pause,resume,dev-expire}` · `POST owners/{o}/pause-payouts` | **none executed** |
| ⚠️ **would move a pinned figure** | `POST reservations/{r}/payments` · `POST reservations/{r}/charge` | **not executed** |
| ✅ exercised | **none of the money paths** | — |

🔬 **Why the refund is ESTABLISH-ONLY, read from `AdjustOwnerLedgerOnRefund`:** on a `payment.refunded`
domain event it resolves the refund, and **unless the operator explicitly absorbed it
(`isOperatorBorne()`), it posts an owner-scoped `adjustment` line via the certified attribution service.**
⚠️ **There is no inverse action** — a refund writes a ledger row and nothing in the operator realm removes
it. **It cannot be proven to reverse cleanly, so it was not performed.** *(Phase 2 made the same call on
suspension and was right.)*

📖 Worth recording from the same file: *"NOT a zero row: NO row, so the owner's ledger is byte-identical to
one where the refund never happened"* — the operator-borne case is a **deliberate no-op**, not a zero-value
row. **That is the money-honesty rule visible in code.**

✅ **`owner_ledger` 29 / 730640 — UNTOUCHED** (§9).

---

## 2 · THE CENSUS — 🔬 measured, and "29 / 31 / 54" resolves to a fourth answer

🔬 **203 operator routes: 54 GET screens · 149 write actions.**
⚠️ **All three quoted figures describe different things.** **54 is the GET-screen count** — the others are
neither the route total nor the screen total. **A pointer (`route:list`) would not have gone stale.**

🖥 **34 screens attempted, 34 reached (100%).** ⚠️ **20 GET routes NOT attempted** — exports
(`*.csv/.pdf/.xlsx`), PDF streams, and `{param}` detail routes. **Reported as NOT EXAMINED, not as clean.**

### 🖥 The seven dimensions, across the 34
| dimension | result |
|---|---|
| **UI/UX** | 🖥 **Zero unnamed controls on 33 of 34** (full accessible-name algorithm). ⚠️ **2 on `/app/group-booking`** — §5 |
| **DESIGN** | 🖥 **Overflow 0px on all 34.** ✅ `REMAINING-OVERFLOW` **confirmed still held** — KNOWN-FIXED, not re-reported |
| **FUNCTIONALITY** | 🖥 **All 34 HTTP 200 · 0 console errors** on the sampled screens. `/app/channels` returns **403 "Channel manager isn't in your plan"** — ✅ plan feature-gating working, **KNOWN** |
| **CRUD** | §3 — the main event |
| **SECURITY** | ⚠️ **Surface only.** One write tested (password change, §6). **Denials not tested — by design (§0)** |
| **PERFORMANCE** | ⚠️ **Not meaningfully measurable** on `artisan serve`. Deferred |
| **MULTI-TENANCY** | ⛔ **NOT TESTED** — one organisation |

---

## 3 · ⚠️⚠️ CRUD PER OBJECT — and the missing halves

🔬 **Read from the route table, after two of my own heuristics proved wrong (§10).**

| object | C | R | U | D | notes |
|---|---|---|---|---|---|
| **Season** | ✅ | ✅ | ✅ `PUT` | ✅ `DELETE` | ✅ **FULL CRUD — `SEASON-EDIT-DELETE` confirmed BUILT.** **KNOWN-FIXED; the recorded gap is closed** |
| **Reservation** | ✅ | ✅ | ✅ | ✅ cancel | cancel is bounded by invariant #29 (terminal cannot re-terminate) |
| **Guest** | ✅ | ✅ | ✅ `POST guests/{g}` | ⚠️ **`anonymize`** | ✅ **By design** — GDPR erase is anonymise, never soft-delete. **KNOWN** |
| **Maintenance ticket** | ✅ | ✅ | ✅ | ✅ | |
| **Housekeeping task** | ✅ | ✅ | ✅ | ✅ unassign (+ **undo**) | |
| **Owner / Team / Role** | ✅ | ✅ | ✅ | ✅ | |
| ⛔ **Unit** | ✅ `POST units` | ✅ | ⛔ **NONE** | ⛔ **NONE** | **§4 — the sharpest gap** |
| ⛔ **Rate plan** | ✅ `POST rates/plans` | ✅ | ⛔ **NONE** | ⛔ **NONE** | create-only |
| ⛔ **Email template** | ✅ | ✅ | ✅ | ⛔ **NONE** | |
| **Review** | ⛔ n/a | ✅ | ✅ publish/reject/respond | ⛔ **NONE** | ✅ **correct** — reviews are guest-authored; an operator deleting one would be fabrication |
| **Payment** | ✅ | ✅ | ⛔ | ⛔ | ✅ **correct** — append-only, reversed by refund, never deleted |

### ⛔ F-6 · A UNIT CAN BE CREATED AND NEVER EDITED OR REMOVED — **NEW · MEDIUM**
🔬 **`POST app/units` creates. There is no update route, no delete route, and no Livewire
save/update/delete/archive action** on any operator unit component.
🔬 **Yet `units` carries BOTH `is_active` AND `deleted_at`** — the schema supports deactivation *and*
soft-deletion. **The capability exists; nothing in the operator realm reaches it.**

⚠️ **This is exactly the shape the brief names: a create with no delete is a trap for the person using it** —
and it is the same shape as the seasonal-pricing gap, which shipped for months.
📄 **And an asymmetry worth checking in a later phase:** the register records an **owner** pause/resume that
toggles `units.is_active`. ⚠️ **If that holds, an owner can pause a unit the operator cannot** — **NOT
TESTED here (owner realm is out of scope); recorded as a question for Phase 5.**
**Severity: MEDIUM. 🔬 MEASURED. CLASS: NEW.**

⚠️ **Rate plans are create-only** on the same pattern — **NEW, LOW**, since rates themselves are editable
via `POST app/rates` and `bulk`.

---

## 4 · ⚠️ WHICH REFUSALS EXPLAIN THEMSELVES
🖥 `/app/channels` → **403 with a plain-language reason**: *"Channel manager isn't in your plan."* ✅ honest.
📄 The season two-step (edit refused on an applied season, with the reason on screen) is **RECORDED as
built and was not re-exercised** — its CRUD is confirmed present at route level (§3).
⛔ **Not established:** whether the *missing* operations (unit update/delete, rate-plan update/delete)
explain their absence anywhere. **There is nothing to explain them — the controls simply do not exist.**

---

## 5 · 🖥 TWO NEW UI FINDINGS, BOTH ON CREATE SCREENS

### ⛔ F-7 · TWO CREATE SCREENS HAVE NO `h1` — **NEW · LOW**
🖥 **`/app/reservations/create`** and **`/app/group-booking`** render **zero `h1` elements** and begin at
`H2` (*"When & which unit"*, *"The group"*). 🖥 **The other 32 screens each have exactly one.**
⚠️ **Both are the realm's two CREATE forms** — a shared shape, not two coincidences.
**🖥 BROWSER-VERIFIED. CLASS: NEW** (`GUEST-HEADINGS` covered the guest realm; these are operator screens).

### ⛔ F-8 · TWO UNNAMED DATE INPUTS ON `/app/group-booking` — **NEW · LOW**
🖥 `<input type="date" class="vy-gb-input" x-model="ci">` and `…x-model="co"` — **no label, no `id`, no
`aria-label`, no `placeholder`, no wrapping label.** A screen reader announces them as bare date fields.
✅ **Checked against the guard first:** `CONTROL-NAMES` is a closed defect and **holds on 33 of 34
screens** — so this is **a gap the register did not cover, not a regression.**
**🖥 BROWSER-VERIFIED. CLASS: NEW.**

---

## 6 · ⚠️⚠️ F-1, SEEN AS A PERSON SEES IT — and the success path is silent too

🖥 **A real password change was performed on `/app/security` and then attributed at the data layer.**

| | 🖥 / 🔬 |
|---|---|
| The change **succeeded** | 🔬 the stored hash **changed**, and the new password **verifies** |
| What the operator was **told** | 🖥 **NOTHING.** No flash node, no alert region, no message anywhere on the page |

⛔ **F-1 now covers both halves of that screen**: the passwordless *interruption* is silent (Phase 1) **and
a successful password change is silent too** — both carried by `password_status`, which no operator view
renders. ⚠️ **Attribution mattered here: "no message" alone would equally have meant "the submit never
happened." The hash proved it did.**

### ✅ The flash class does NOT widen in this realm
🔬 **Swept every flash key set by operator controllers/Livewire against every key rendered by operator and
shared views.** ⚠️ **After stripping three artefact classes of my own making (§10), every operator flash key
that is set is consumed.** ⛔ **No fourth instance.** **F-1 remains this realm's only case, and it is
specifically `password_status`.**

### ⛔ F-9 · A PASSWORD CHANGE WRITES NO AUDIT ROW — **NEW · MEDIUM**
🔬 `ChangePasswordController` contains **no audit call**; there is **no password-changed event or
listener**; and `audit_events` holds **zero** actions matching `password`/`security`/`auth`.
🔬 `audit_events` stayed at **146** across a successful password change.
⚠️ **Against the project's own stated convention** — *"every meaningful state change writes an
`audit_events` row (who did what) AND a `domain_events` row"*. A credential change on a security screen is
unambiguously that.
⚠️ **What I did NOT establish:** whether some other mechanism (a notification, a mail, a separate table)
records it. **Reported as measured, with that bound stated.** **CLASS: NEW.**

---

## 7 · ⚠️⚠️ THE "FIVE ORPHAN PERMISSIONS" FIGURE IS WRONG — carried since Phase 0, corrected here

🔬 **Measured against the operator route table:**

| permission | routes enforcing it |
|---|---|
| `billing.manage` | **9** |
| `settings.manage` | **4** |
| `roles.manage` | **1** |
| `organizations.manage` | ⛔ **0 — no route anywhere** |
| `api.manage` | ⛔ **0 — no route anywhere** |

⛔ **So it is TWO, not five.** Three of the five are enforced on real, reachable operator routes — and
`Property Owner`, a wildcard role, holds them, so **this phase could and did reach those screens**
(`/app/billing`, `/app/roles`, `/app/settings` all rendered).

⚠️ **The accurate statement, replacing the one carried through Phases 0–2:**
**(a)** `organizations.manage` and `api.manage` are true orphans — **defined, granted, and enforced
nowhere**; **(b)** the other three are enforced but held by **no narrower role**, so **no narrower-role
phase can exercise them** — which is a *role-coverage* gap, **not** an unreachability gap.
⚠️ **Phase 2's "the hole stands" was correct for the admin realm and incomplete as a general claim.**

🔬 **Also measured: 165 of 203 operator routes carry a permission; 19 writes carry none** — and all 19 are
self-scoped (own profile, own security, own notification preferences, dashboard prefs, property switch).
✅ **KNOWN** — matches the recorded "19 ungated routes". **Not re-reported as new.**

---

## 8 · ⚠️⚠️ THE SPECIFICATION FOR PHASES 4+ — what each narrower role must be tested FOR

⚠️ **This is the most valuable output of this phase.** Nothing below was tested; it is the *denied half*
that Phases 4+ exist to cover.

| screen | permission gating it | what a narrower role must be tested for |
|---|---|---|
| `/app/billing`, `/app/billing/checkout` | `billing.manage` (9 routes) | ⚠️ **No narrower role holds it.** Test that every non-wildcard role is REFUSED — and that the refusal is a 403, not a blank screen |
| `/app/roles` | `roles.manage` (1 route) | **The escalation question**: can a role holder grant themselves more? Test `POST app/roles/permissions` as each role |
| `/app/settings`, `/app/security/ip-rules` | `settings.manage` (4) | Same as billing — held by no narrower role |
| `/app/rates`, `/app/rates/bulk` | `rates.manage` (12) | Revenue Manager **should** reach; Front Desk/Housekeeping must not |
| `/app/guests`, `/app/guests/{g}` | `guests.view` (12) / `guests.manage` (8) | ⚠️ **PII perimeter** — invariant #28 says contact PII renders on Guests CRM only. Test each role sees name-only elsewhere |
| `/app/reservations/{r}/refunds`, `/payments`, `/charge` | `folios.manage` (3) / `folios.view` (3) | ⚠️⚠️ **THE MONEY PATHS.** Test that a role without `folios.manage` cannot POST a refund — **a WRITE, not a read** |
| `/app/payouts/*` | `payouts.manage` (5) | Accountant vs Front Desk |
| `/app/housekeeping` | `housekeeping.manage` (7) | Housekeeping holds 4 permissions total — test the 47 it lacks |
| `/app/maintenance/*` | `maintenance.manage` (19) | Largest gated group |
| `/app/schedule` | `schedule.manage` (9) / `schedule.view` (3) | Manager-level vs Front Desk — the split is deliberate |
| `/app/owners`, `/app/owners/{o}` | `users.manage` (10) | ⚠️ **Owner-removal privesc was a real closed hole** — re-test as each role |
| `/app/units`, `/app/portfolio` | `units.manage` (10) / `properties.manage` (17) | |
| `/app/reviews` | `reviews.manage` (3) | |
| `/app/reports`, `/app/insights` | `reports.view` (7) | |
| **The 19 ungated writes** | **none** | ⚠️ Confirm each is genuinely self-scoped for EVERY role — an ungated write that touches another user would be the finding |

⚠️ **And on every one:** *the team context must be set* — `can()` returns **false** with no team, which
would make every role look denied and produce a spectacular false finding. **Three sightings already.**

---

## 9 · STATE — every table measured BEFORE being touched

🔬 **17 tables captured before any write, re-measured after. ALL UNCHANGED:**
`owner_ledger` **29 / 730640** · `payments` **16** · `reservations` **42** · `folios` **20** ·
`folio_items` **40** · `guests` **10** · `units` **8** · `room_types` **3** · `rate_plans` **5** ·
`rate_seasons` **0** · `rate_calendar` **929** · `maintenance_tickets` **5** · `housekeeping_tasks` **7** ·
`audit_events` **146** · `domain_events` **190** · `inventory_blocks` **2** · `owner_statements` **6**.

**One mutation, made and reversed:** 🔬 `demo-admin`'s password changed via the UI (to observe F-1),
hash **captured and verified non-empty first**, **restored byte-identical**.
✅ **All 14 demo accounts authenticate.** ⚠️ **The money gate was NOT run** (it begins `migrate:fresh --seed`).
⛔ **Suite not run — no application code changed.** ⛔ **F-1 through F-5 remain open; F-6 through F-9 added.**

---

## 10 · ⚠️ NEAR-FALSE-POSITIVES — three more (running total **TWELVE**)

10. **"203 operator routes are UNGATED."** 🔬 **False and alarming.** Laravel resolves `can:x` to
    **`Authorize:x`** in the middleware list; my filter looked for the `can:` alias. **165 of 203 are
    gated.** ⚠️ **Publishing that would have been a catastrophic false finding on the product's core
    authorization claim.**
11. **"`desk_refused` and `folio_refused` are flash keys no view renders."** 🔬 **False.** Both are read as
    `session('desk_refused', false)` — **with a default argument** — and passed into a shared
    `<x-operator.flash-notice>` component. My pattern required `session('key')` with an immediate closing
    paren. ⚠️ **A key read with a default argument is invisible to the obvious grep.**
12. **"Seasons and folios have no delete path."** 🔬 **False, twice.** Seasons have `DELETE
    app/seasons/{season}` — my matcher looked for "delete" in the **URI** rather than the **HTTP verb**.
    And my "folio" needle matched **`app/portfolio`**. ⚠️ **Substring matching on route URIs invents both
    absences and presences.**

⚠️ **Also corrected mid-pass, before it became a finding:** "unnamed controls on 11 screens" collapsed to
**two on one screen** once the full accessible-name algorithm (label-for, aria-labelledby, wrapping label)
replaced my crude one. ⚠️ **`CONTROL-NAMES` is a closed defect — a contradiction had to be a regression or a
false reading, and checking said which.**

---

## 11 · ⚠️ WHERE THIS BRIEF WAS WRONG

1. ⚠️ **"the operator realm has been quoted at 29, 31 and 54."** 🔬 **54 is right — as the GET-screen
   count.** The realm has **203 routes**. The three figures were never the same quantity.
2. ⚠️⚠️ **"the FIVE orphan permissions."** 🔬 **It is TWO.** Three of the five are enforced on real operator
   routes (§7). **A figure this programme has repeated in three consecutive reports.**
3. ⚠️ **"seasonal pricing shipped with no edit, no delete and no undo."** 🔬 **True as history, STALE as
   current state** — `PUT` and `DELETE` both exist now. ✅ The *undo* half stands (nothing records what a
   price replaced).
4. ✅ **Correct and load-bearing:** draw the money line before clicking (**the refund proved un-reversible
   and was not performed**); a create with no delete is a trap (**F-6**); and say prominently that this maps
   the surface, not the permission system (**§0**).
