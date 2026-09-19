# Vyrobel — FINAL VERIFICATION

**Run 2026-09-14, after the fifteen-phase audit and the twenty-four-pass fix programme that acted on it.**
Written to be readable by someone who has read nothing else in this repository.

> ⚠️ **READ §8 BEFORE ACTING ON ANY OF THIS.** A green verification does **not** mean ready to sell. Everything
> in §8 is still required before a real customer, **none of it is code, and none of it has moved.**

---

## 0 · WHAT THIS REPORT IS, IN ONE PARAGRAPH

An audit found 44 defects. A fix programme closed 32 of them and deliberately left 12, each with a recorded
reason. This document re-checks that the fixes actually landed, that nothing previously sound was broken in
the process, and that the product still works end to end. **It is a confirmation pass, not a new audit** — it
did not go looking for new defects, and the absence of new findings here is therefore **not** evidence that
none exist.

---

## 1 · IS THE PROGRAMME FINISHED? — YES, AND HERE IS THE ARITHMETIC

**44 findings. 32 CLOSED. 12 open, every one of them with a recorded reason.**

| status | count | which |
|---|---|---|
| ✅ CLOSED | 32 | F-1, F-2, F-4…F-9, F-11…F-13, F-15…F-20, F-22, F-26…F-31, F-34, F-37…F-43 |
| ◐ PARTLY CLOSED | 1 | **F-3** — public realm closed; the admin half waits on **R-ADMIN-COPY** |
| 🔨 BUILT · UNWIRED | 1 | **F-23** — the middleware exists and is mutation-proven; wiring waits on **R-GATE** |
| ⛔ BLOCKED ON A NAMED RULING | 6 | **F-10** (R-F10) · **F-14** (R-PHANTOM) · **F-21**, **F-25** (R-RATE) · **F-24** (R-ARCHIVE) · **F-32** (R-ADMIN-COPY) |
| ⛔ OPEN, DELIBERATELY, WITH A REASON | 4 | **F-33** costed and undecided · **F-35** LOW, hardening only · **F-36** needs a feature, not a guard · **F-44** recorded, the founder's call |

⚠️ **NOTHING IS UNADDRESSED AND UNRULED.** That was the gate on running this verification at all, and it is
the reason the rest of this document can be trusted. Every one of the 12 is a decision someone made on the
record, not an item that fell off a list.

⚠️ **NO FIX CAUSED A DEFECT.** Sixteen findings (F-28…F-44) were *discovered by* fix passes — found by
changing the code rather than by walking it — but **not one finding in the register is recorded as having
been introduced by a fix**. That was checked directly, not assumed.

---

## 2 · WHAT IS FIXED — AND HOW IT WAS PROVEN

Each fix was re-proven **the way the original defect was proven**, because a weaker instrument would pass
over a live defect. The clearest examples:

| finding | proven by | result |
|---|---|---|
| **F-15** the unbounded housekeeping ladder | 🔬 **ROW STATE, with a positive control** | `in_progress → inspected` **REFUSED**, row unchanged; `in_progress → clean` **SUCCEEDED** as the control; transaction rolled back |
| **F-43** the guest checkout quoting € under a £ total | 🖥 **The rendered page, in the currency that exposed it** | GBP checkout reads **"Room · £90.00 × 3"** under **"Total £270.00"**, **zero `€` on the page**, 90 × 3 = 270 |
| **F-8** unnamed date inputs | 🖥 **Reading the rendered page** | All **three** inputs named (including the one behind `x-show` that a default-state snapshot missed); 0 unnamed |
| **F-17 / F-22** writes with no actor | 🔬 **Querying the column** | A real status change resolved to **`demo-staff@vyrobel-demo.test`**, with `before={"status":"pending"}` / `after={"status":"in_progress"}` |
| **F-39** the warning below its button | 🖥 **Geometry at two widths** | 375px: warning **978**, buttons **1247 / 1606** — above both. (Before: 2152, below both.) |
| **F-41** the unguarded audit listener | 🔬 **Replaying the event** | Two dispatches of one grant → **1 row**; guard removed → **2 rows** |
| the other 26 | 🔬 **Committed, mutation-proven tests** | All green in a full suite run |

⚠️ **A MARKUP-EXISTENCE TEST WOULD HAVE PASSED WHILE F-20 STOOD**, and that is why none of the above rests on
one. It is also why F-39's guard asserts *document order* rather than the presence of the text: the
existence-only version of that test was written and **passed while the warning still sat below the button.**

### The four recurring classes — closed as classes, or only their instances?

| class | verdict |
|---|---|
| **A missing state guard** | ✅ **CLOSED AS A CLASS.** One reference implementation (`MaintenanceService::assertTransition`) copied to exactly three siblings — housekeeping, check-in/out, and the OTA writer — 🔬 verified as 4 files and no more. |
| **A write with no actor** | ✅ **Closed where measured**, and re-proven live above. ⚠️ Not swept exhaustively across every writer in this pass. |
| **A message whose scope is invisible** | ⛔ **NOT closed as a class** — **F-21** is exactly this shape and is blocked on **R-RATE**. |
| **A control that does nothing** | ✅ **Closed for every known instance** (F-20's dialogs, F-6's unit lifecycle). ⚠️ A class-wide sweep of every control in the product was **not** performed here. |

---

## 3 · WHAT WAS ALREADY SOUND — DID THE FIXING BREAK IT?

**No.** This is the half that matters most, because a fix programme's worst outcome is damage to what already
worked. Every line of the audit's "demonstrably sound" record was re-checked.

| what was recorded sound | re-checked how | verdict |
|---|---|---|
| Owner/tenant scoping fails closed | The scope's own tests + 12 owner pages rendered | ✅ HOLDS |
| **Invariant #28** — no guest PII outside the CRM | 🖥 12 owner pages scanned for any foreign email address | ✅ HOLDS — **0 on every page** |
| Money reconciles across realms **to the cent** | 🔬 A real statement, three independent ways | ✅ HOLDS — 54,000 − 9,720 = **44,280** = `net_payable` |
| The per-owner `canOn()` guard at its write sites | 🔬 Counted, and `git log -S` across every commit | ✅ HOLDS — **exactly 16 write sites**, and **no commit in the entire programme added or removed one** |
| `assertTransition` as the reference ladder | 🔬 File census | ✅ HOLDS — reference + 3 copies, exactly as recorded |
| One `h1` per screen | 🖥 24 operator screens | ✅ HOLDS — **1 on every one** |
| Zero unnamed controls | 🖥 24 operator screens + the group-booking form | ✅ HOLDS — **0**, once wrapping labels are counted (see §6) |
| Zero console errors | 🖥 Accumulated across the whole session | ✅ HOLDS — **0** |
| 0px overflow at 375px | 🖥 Storefront, checkout, housekeeping board | ✅ HOLDS — **0px** |
| The housekeeping board's no-script forms | 🖥 Rendered at 375px | ✅ HOLDS — **19 real POST forms**, actions reachable |
| Append-only residue: every pinned figure | 🔬 Re-measured (§7 below) | ✅ HOLDS — tenant one **exactly** unmoved |

⚠️ **NO REGRESSION WAS FOUND.** Not one §7 entry stopped holding. That is the single most important sentence
in this document after §8.

⚠️ **Two §7 entries were left 📄 RECORDED rather than re-measured. FIX 25 resolved both — one measured, one
moved:**

* **The owner capability matrix — PARTLY MEASURED (2026-09-14).** 🔬 The authoritative matrix
  (`OwnerTeamRole::CAPABILITIES`, the ONE source both the route gate `EnsureOwnerCapability` and the UI
  `@ownercan` read) is exact: **7 capabilities; `co_owner` holds 6, `accountant` 3, `view_only` 2.**
  `payout.manage` is **co-owner-only** and `team.manage` is denied to **all three roles** — confirming
  team management is held by no role, so self-escalation is structurally impossible. ⚠️ **The SCREEN counts
  in the original entry ("accountant denied 7 screens, view-only 15") were NOT re-measured** — those are
  screens, not capabilities, and would need a browser sweep as each sub-user. That half stays 📄 RECORDED.
* **Guest isolation, 16 of 16 — MOVED TO §5, because it cannot be measured here.** 🔬 Each organisation has
  exactly **ONE claimed guest**, so guest-to-guest isolation *within* a tenant has no second actor to probe
  with. It is not a matter of time: **the fixture does not exist.** A permanently unverifiable line in §7
  weakens every other line in it, so it belongs with what could not be tested.

---

## 4 · DOES THE PRODUCT ACTUALLY WORK? — THE JOURNEYS

| journey | verdict |
|---|---|
| **1 · A stranger books** | ✅ **WORKS, both tenants.** Search → offers → room → checkout, at 0px overflow. The EUR checkout reconciles **255.00 + 58.65 + 15.00 + 45.00 = €373.65**; the GBP checkout **£90.00 × 3 = £270.00**. ⚠️ **Stopped before "Confirm reservation"** — no booking was completed and no money moved. |
| **3 · A housekeeper cleans, on a phone** | ✅ **WORKS at 375px.** `pending → in_progress` driven through the real form, **proven by row state** (`started_at` stamped, audit row naming the actor), then **restored to `pending`**. The illegal jump is refused and the legal one succeeds. |
| **5 · An owner reads their money** | ✅ **WORKS.** 11 of 12 pages render with one `h1`, no guest PII, no raw Blade, and the statement reconciles to the cent. |
| **2 · A guest claims and reads** | ⚠️ **NOT EXAMINED in this pass** — covered by committed guest-realm tests, but not driven end to end here. |
| **4 · An operator runs a day** | ⚠️ **PARTIAL.** All 24 operator screens render clean; the full take-a-booking → assign → check-in → payment → receipt sequence was **not** driven. |
| **6 · An admin manages the catalogue** | ⚠️ **NOT EXAMINED** — the authenticated platform-admin realm remains unverified here, as it was in the audit. |

⚠️ **A journey marked NOT EXAMINED is not a journey that passed.**

---

## 5 · ⚠️ WHAT STILL CANNOT BE TESTED — READ THIS BEFORE BELIEVING §3

**This section is as important as every verdict above it. A report that omitted it would be read as
"everything is tested", which is false.**

**Closed since the audit:**
* **Cross-organisation isolation is now EXERCISED, not read.** A second organisation exists
  (`northgate-demo-city-rooms`, GBP). Six object types were probed in both directions, each with a positive
  control: **no leak**, a consistent **404**. This was the single largest untested property in the product.
* **Any currency but EUR.** Every fixture was EUR, so a hardcoded `'€'` and a resolved currency were
  indistinguishable in all 3,400 tests. A GBP fixture now runs beside a EUR control.

**Still untestable, unchanged:**
* **Owner-to-owner isolation *inside one organisation*** — cross-tenant owner separation is now covered, but
  two owners in one org (the union-vs-per-owner capability question) still has no fixture.
* **Guest session-to-session *inside one tenant*** — two claimed guests in one organisation, still untested.
  ⚠️ **This is also why §7's "guest isolation, 16 of 16" cannot be re-measured** (moved here 2026-09-14): with
  one claimed guest per organisation there is no second actor to probe with. The original 16 probes stand as a
  P14 reading; they are **not** re-verifiable in this environment without building a guest.
* **Fourteen of the seventeen money-write routes have never been executed.** Three have (refund, season
  apply, statement finalization), all in the second tenant. **Fourteen remain.**
* **The authenticated platform-admin realm** — nine screens, unaudited and unverified.
* **Anything needing a real card, device, provider or proxy** — live Stripe, Connect, Channex, SMS, WhatsApp,
  guest ID verification, production email delivery, TLS termination behind a real proxy.

⚠️ **The season apply cannot be undone.** Measured after a real apply: the audit row's `before` is **NULL**,
no history table holds the replaced price, and a schema-wide hunt found only coincidences. The dialog's
sentence — *"The prices it replaces are not stored anywhere. This cannot be undone."* — **is true.**

---

## 6 · WHAT THIS REPORT DOES NOT CLAIM

1. **It does not claim the product is defect-free.** It confirms known findings and does not hunt for new
   ones. Three of the four recurring defect classes are closed *as classes*; the fourth (an invisible message
   scope) is open by ruling.
2. **It does not claim every screen was driven.** 24 operator and 12 owner screens were rendered and checked;
   three of six journeys were driven end to end; the platform-admin realm was not entered.
3. **It does not claim the browser evidence is re-runnable.** There is **no E2E suite** in this repository.
   Every 🖥 reading here is real and was taken once. The re-runnable safety net is the **3,508-test suite**.
4. **It does not claim the suite tests everything.** A suite that fixes one value for a dimension cannot test
   that dimension — which is precisely how the currency class survived 3,400 tests. Other such dimensions
   very likely remain.
5. **It does not claim a deployment is safe.** See §8.

⚠️ **Three instrument errors were made *during this verification* and caught before being reported**, which is
the honest measure of how easily a confirmation pass produces false results:
* A control-naming sweep reported **5 unnamed controls**; all five were wrapped in `<label>` elements the
  check did not test for. **True count: 0.**
* A ledger reconciliation reported a **mismatch**; the ledger stores a magnitude plus a `direction` column,
  and the probe added a debit instead of subtracting it. **It reconciles exactly.**
* A ladder probe reported a **refusal** that was an undefined-method error. **The positive control caught
  it** — which is why a control is mandatory.

---

## 7 · MEASUREMENTS TAKEN

| gate | result |
|---|---|
| **Suite, run ALONE** | **3,508 tests · 3,495 passed · 0 failed · 25,498 assertions · 13 skipped** (731 s) |
| **Suite, run IN COMPANY** (dev server + browser active) | **3,508 · 3,495 · 0 failed · 25,498 · 13 skipped** (738 s) — ⚠️ **byte-identical result**, so the suite is not order- or load-dependent |
| **PHPStan L5** | 0 errors |
| **Pint** | clean |
| **Money gate** | ✅ **`mode=FULL`** — all six figures: **929 / 10,928,000 / 0 / 42 / 16 / 0** |
| `owner_ledger` | **29 / 730,640** — unchanged |
| `reservations` · `payments` | **42** · **16** — unchanged |
| `rate_calendar` | **929 / 10,928,000** — unchanged |
| `plans` · `plan_features` · `permissions` | **4** · **12** · **51** — unchanged |

**Three figures moved, all legitimately, and the arithmetic closes to the unit:**

| figure | pin | now | cause |
|---|---|---|---|
| `roles` | 14 | **27** | 13 per org × 2 orgs + 1 global. Tenant one still **13 + 1 = 14**. |
| `role_has_permissions` | 260 | **469** | 209 + 209 + 51 global. Tenant one still **209 + 51 = 260**. |
| `model_has_roles` | 14 | **16** | +2 = the second tenant's two demo users. Tenant one still **14**. |

⚠️ **Roles are per-organisation** (spatie teams mode), so a second tenant necessarily moves these three. **Not
a regression — a re-pin caused by the second organisation, and tenant one is exactly unmoved.**

⚠️ **The `rate_calendar` md5 is NOT a valid pin and was correctly removed from the record.** It is
date-dependent: the demo window rolls (currently 2025-09-13 → 2027-02-27), so the hash changes daily **with
no write at all**. The count and the sum are the real pins, and both hold.

⚠️ **Residue, reported with its cause:** one `housekeeping_tasks` row was moved `pending → in_progress`
through the real form and **restored**; its **audit row was kept**, because audit rows are never pruned.
Browser logins wrote `login_activity` rows — real records of real logins.

**Nothing irreversible was performed.** No booking completed, no refund issued, no season applied, no
property archived, no guest deleted, no statement finalized.

---

## 8 · ⚠️⚠️ WHAT IS STILL REQUIRED BEFORE A REAL CUSTOMER

**This is the most important section in this document. None of it is code. None of it has moved. A green
verification says the software behaves; it says nothing about any of the following.**

**Infrastructure — none of it exists yet:**
* A **server**, a **domain**, and **TLS**.
* **Redis** running, and the queue worker supervised.
* ⚠️ **The scheduler cron** (`* * * * * php artisan schedule:run`). **The GDPR 14-day guest deletion and the
  30-day ID purge depend on it, and they fail *silently* without it** — no error, no failed job. The deploy
  script installs it, but `DEPLOY_NO_CRON_INSTALL=1` prints a warning and **still completes**.

**Money — the founder's own steps, none taken:**
* **Stripe live keys**, and **both** webhook secrets (the account endpoint and the Connect endpoint are
  separate).
* **Stripe Connect** onboarding and KYC for owner payouts.
* **Four Price IDs**, and **real prices** — the ones in the codebase are clearly-labelled placeholders.
* **The payout-engine go-live flip** (`PAYOUT_ENGINE_ENABLED`), currently test-mode only by construction.
* **Real tax rates** per jurisdiction. The engine is code; the rates are configuration, and the seeded ones
  are **clearly-labelled EXAMPLES that must be replaced.** Tax figures have legal consequences.

**Legal — requires a person, not a pass:**
* The **Article 28 data-processing agreement**.
* **The flow-of-funds question** — who is merchant of record, and what that makes Vyrobel.

**And one open decision that blocks shipped behaviour:** the **11 founder rulings** in §4 of
`docs/AUDIT-SUMMARY.md`, six of which are the only thing standing between a recorded defect and its fix.

---

## 9 · WHERE TO READ MORE

* **`docs/AUDIT-SUMMARY.md`** — the findings record: all 44 with severity, evidence and status; the corrected
  shapes; the open rulings; what could never be tested; and the method.
* **`docs/AUDIT-PHASE-0.md` … `AUDIT-PHASE-14.md`** — the fifteen phase reports the summary reconciles.
* **`docs/KNOWN-LIMITS.md`** — what a customer actually meets, and where each limit is told.
* **`docs/GO-LIVE-REQUIREMENTS.md`** — the blocking and advisable items behind §8.

---

⚠️ **This verification changed no application code.** Its only writes are this file and the findings record.
