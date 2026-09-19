# Vyrobel — FINAL VERIFICATION

**Run 2026-09-19, after the fifteen-phase browser audit and the forty-seven-pass fix programme that acted on
it.** Written to be readable by someone who has read nothing else in this repository.

> ⚠️⚠️ **READ §6 BEFORE ACTING ON ANY OF THIS.** §6 is the most important section in this document. A green
> verification does **not** mean ready to sell, and the things this report refuses to claim are more
> decision-relevant than the things it confirms.
>
> ⚠️⚠️ **THIS SUPERSEDES THE VERSION RUN 2026-09-14**, which recorded 44 findings / 32 closed / 12 open. That
> arithmetic was true on its date and is now wrong in every figure. Its *verdicts* largely stand; its numbers
> do not. **Re-derive from `docs/AUDIT-SUMMARY.md` §2 and §2b — never quote a count from this document as
> current state either.** This one will go stale the same way.

---

## 0 · THE POSITION, IN ONE PARAGRAPH

A fifteen-phase browser audit and the fix programme that followed it found fifty-eight defects. Every one of
them now carries a decision: forty-eight are closed, six were weighed and accepted by the founder, two are
contained (the defect is still in the code and merely unreachable), one is partial by ruling, and one is
deferred to a named trigger. **Nothing is open and unruled, and that was verified by re-deriving the register
rather than by quoting it.** What that sentence does *not* mean is that the product is finished or safe to
sell: ten to fourteen of the seventeen money-write routes have never been executed by anything, owner-to-owner
isolation inside a single organisation has never been tested at all, the network guard added this week covers
one transport out of several, the money gate watches one of the two seeded tenants, and everything standing
between this repository and a paying customer is infrastructure, credentials and a lawyer — none of it code,
and none of it moved since before the audit began. **The register being clean is a statement about the
register.**

---

## 1 · THE COMPLETENESS GATE — VERIFIED, NOT QUOTED

This document was refused once before, correctly, when the programme was one-ninth finished. The gate on
running it at all is that no finding is both unaddressed and unruled. **That was re-derived from the register's
own rows, by parsing them, not by reading a summary line.**

🔬 **MEASURED — 58 findings, F-1 … F-58, no gaps, no duplicate rows:**

| state | count | which |
|---|---|---|
| ✅ CLOSED | 48 | the remainder |
| ⚠️ ACCEPTED BY RULING | 6 | F-25, F-32, F-33, F-49, F-52, F-58 |
| ⚠️ CONTAINED | 2 | F-53, F-54 |
| ◐ PARTIAL BY RULING | 1 | F-3 |
| ⏸ DEFERRED (named trigger) | 1 | F-36 → GO-LIVE **A10** |
| ⛔ **OPEN AND UNRULED** | **0** | — |

⚠️ **CONTAINED and ACCEPTED BY RULING are third states, not synonyms for closed.** The first means the defect
is still in the code and merely unreachable. The second means the founder weighed it and chose to live with it.
A reader who collapses either into "fixed" has misread this table.

### ⚠️ The gate passed. The register still contradicts itself in four places.

🔬 **MEASURED. A contradiction here is a finding, not a typo** — and this is the fifth consecutive pass to find
one, each time in a surface the previous closure invalidated. **None of these were repaired** (see §7).

- **V-1 · §8 item 7 says "F-21 remains blocked on §4."** F-21 is ✅ CLOSED (FIX 46) per its own register row.
  Unflagged, unlike the stale lines elsewhere that carry a correction beside them.
- **V-2 · §4's open-findings table still reads "Only F-35 is still open, and it has its own pass,"** with a row
  `⛔ STILL OPEN`. F-35 was closed by FIX 47. ⚠️ **That pass swept §8's open list and F-35's register row and
  missed §4's table** — the closure invalidated a third surface nobody looked at.
- **V-3 · The "17 money-write routes" denominator is enumerated nowhere.** §5 says fourteen remain unexecuted;
  the brief for this pass said ten. 🔬 Neither is derivable from the record: no list of the seventeen exists in
  any document, and a router sweep finds **54** write routes touching money-adjacent concepts, so "17" was a
  curated subset whose membership was never written down. **A denominator nobody can reconstruct cannot be
  audited, and the closure claim built on it cannot be checked.**
- **V-4 · Two reservation-ladder decisions are still unruled.** 🔬 `STATUS-LADDERS.md` §2 shows **R2**
  (`no_show → checked_in`, described there as "the commonest real-world case in the list") and **R5**
  (`enquiry/tentative → checked_in`) with no ruling marker, and neither appears in §4's answers table.
  ⚠️ **"Zero open findings" is not "nothing left to decide."** The product ships a default for both today.

🔬 **A fifth, outside the repository:** the session memory index states "17 rulings — 8 BUILT · 8 SATISFIED ·
1 NOT BUILT." §4's answers table measures **15 rows: 8 SATISFIED · 6 BUILT · 1 NOT BUILT**, reconciling to 17
only by adding R1 and H1, which are ruled elsewhere. **The BUILT count in memory is two higher than the
register supports.**

---

## 2 · HOW EVERY CLAIM IN THIS DOCUMENT IS MARKED

| tier | meaning |
|---|---|
| 🔬 **MEASURED** | executed in this pass, against the real system — **a fact** |
| 🖥 **BROWSER** | observed in a real browser over a real session in this pass — **a fact** |
| 📖 **ASSERTED** | read from source, never run — **not a fact** |
| 📄 **RECORDED** | written down once, not re-checked in this pass — **not a fact** |

⚠️⚠️ **ONLY 🔬 AND 🖥 ARE FACTS, and the tier is in the line, never a footnote.** This programme lost an entire
pass to one 📖 sentence — *"`reverse()` is a genuine, provable inverse"* — which travelled through the
register, the memory and four briefs as though it had been measured. When somebody finally ran it, it held two
HIGH defects. **A specific, plausible, written-down assertion travels as a measurement until somebody runs it.**

⚠️ **The phrases "broadly correct", "appears sound" and "no issues were found" appear nowhere in this document
by design.** Either something was measured and the line says what was measured, or it is not claimed.

---

## 3 · §7 RE-MEASURED, LINE BY LINE — RE-ESTABLISHED, DEMOTED, OR STRENGTHENED

`AUDIT-SUMMARY.md` §7 is the list of what the project claims is demonstrably sound. Several lines were written
before half this work existed. **Nothing below was deleted — a line that could not be re-established is demoted
to its real tier with a note on when it was last true**, because deleting it would hide that somebody once
believed it.

| §7 line | verdict in this pass |
|---|---|
| Tenant and owner data scoping fails closed | 🖥 **STRONGER.** F-23's gate was unwired when that line was written. It is now wired (FIX 35) and I drove it: an operator requesting `/owner`, `/owner/payouts` and `/owner/balance` lands at `/app/dashboard` every time. The reverse holds too — an owner requesting `/app/dashboard`, `/app/reservations`, `/app/guests` lands at `/owner` (invariant #26). |
| Guest isolation — 16 of 16 probes refused with 404 | 🖥 **RE-ESTABLISHED IN A CORRECTED FORM.** The original 16/16 figure predates the gate wiring and stays 📄. **Measured fresh today with a working positive control:** the guest's own reservation returns **200** (40,091 bytes, "Demo Seaside Property"), four reservations belonging to other guests return **404** with **byte-identical** 5,681-byte bodies — existence withheld, no size oracle. ⚠️ Without the positive control this measurement would have been vacuous, and my first attempt at it *was* — see §7 V-6. |
| Invariant #28 holds everywhere measured | 🖥 **RE-ESTABLISHED for the owner realm.** Thirteen owner surfaces swept: the only email addresses rendered anywhere are the owner's **own** login address, two form **placeholders**, and the owner's **own** pending team invitee. **No guest contact data on any owner surface.** ⚠️ Not re-measured on the operator or guest realms this pass — those halves stay 📄. |
| Money reconciles across realms | 📄 → ⚠️ **DEMOTED, SPLIT IN TWO.** 🔬 The *seeded totals* half is re-established: the money gate ran **`mode=FULL`** and all six tenant-one figures are exact. The *cross-realm reconciliation* half — that owner and operator views agree to the cent — **was not recomputed in this pass and stays 📄, last measured P9.** These are different claims and the gate does not cover the second. |
| The owner capability matrix is exact in the browser; `canOn()` at 16 write sites | 📄 **UNCHANGED, NOT RE-MEASURED.** Last true at P13 / FIX 08. I did not drive the accountant and view-only grids this pass. |
| `MaintenanceService::assertTransition()` is the reference bounded ladder, copied three times | 🔬 **RE-ESTABLISHED** — see §5's guard inventory; the ladder tests are in the suite that ran green. |
| The owner realm's refusal copy names the exact person who can grant access | 📄 **UNCHANGED, NOT RE-MEASURED** this pass. |
| The guest realm is the cleanest surface — zero heading defects, unnamed controls, focus-ring gaps, console errors, 375px overflow | ◐ **PARTLY.** 🖥 The guest pages I drove rendered clean with no console errors. **The specific accessibility and 375px measurements were not repeated and stay 📄.** ⚠️ And F-33 is now an accepted ruling, not an open defect: **the back office is desktop-only by decision** (`KNOWN-LIMITS.md` L7). |
| Zero append-only residue; every pinned figure unmoved | 🔬 **RE-ESTABLISHED.** `rate_calendar` **929 / 10928000** · `owner_ledger` **29 / 730640** · `reservations` **42** · `payments` **16** · `role_has_permissions` **260** — all exact after a full reseed at the reference date. |

### 🖥 Lines that are STRONGER than when they were written — and were not on §7 at all

A verification that only lists weaknesses is as unbalanced as one that only reassures. All of these were
measured **in this pass**:

- 🖥 **A suspended organisation stops selling.** With tenant two set to `suspended`, `/book/{property}`, its
  `/search`, its `/checkout` and `/stay/{organization}` all returned **404**, while tenant one's storefront
  stayed **200**. Restoring the status returned it to 200. **Every prediction held, and the write was reversed.**
- 🖥 **The platform-admin realm enforces 2FA before anything.** A correct admin login lands on
  `/admin/two-factor/setup`, and `/admin`, `/admin/customers`, `/admin/plans`, `/admin/activity` and
  `/admin/integrations` **all** redirect there. This was 📄 in §5; it is now measured.
- 🖥 **The guest claim does not leak who exists.** A real unclaimed guest's address and a random non-existent
  address produce the **identical** response, landing and heading ("Check your inbox").
- 🔬 **Signed links are genuinely signature-enforced.** A real claim link was issued and delivered (Mailpit);
  tampering with the **signature** returned **403**, tampering with the **hash** returned **403**, and the guest
  was provably still unclaimed afterwards — nothing was written.
- 🖥 **The currency class (F-44 / F-46) holds where it was found.** On tenant two's GBP owner Payouts screen,
  **rendered text contains 0 euro symbols and 6 pound symbols** (`£0.00`, `£500`, `£1`, `£2.5`). ⚠️ A
  source-level count reported one `€` on six pages; it is inside a **CSS comment**, not rendered money — the
  detector was reading its own commentary.
- 🖥 **The billing gate bites exactly where designed (#41).** Tenant two's trial ended 2026-09-15; at today's
  date it is genuinely lapsed, and its operator is held at `/app/billing` on every route. ⚠️ **Its owner is
  not** — the owner portal remains fully reachable, so an owner keeps sight of their money while the operator
  is locked out. That is a deliberate asymmetry and it is worth the founder knowing it.

---

## 4 · THE JOURNEYS — WALKED IN A REAL BROWSER

🖥 All of the below were driven over real sessions against a running application, in **both** tenants. Each
journey leads with **what it could not do**, because a refused or blocked legitimate step is the most valuable
thing a journey finds.

### 4.1 An operator's day — tenant one (EUR)

> ⚠️ **WHAT IT COULD NOT DO: nothing was written.** Tenant one is the untouchable baseline, so every operator
> action that changes state — a housekeeping transition, a charge, a refund, a check-in — **was deliberately not
> performed.** This journey proves the surfaces render and refuse correctly; **it proves nothing about whether
> the write paths behind them work**, and the money-route gap in §6 is the same gap.

🖥 Thirteen of fifteen operator surfaces render clean as Hotel Manager: dashboard, front desk, reservations,
housekeeping, rates, owners, reports, insights, maintenance, inbox, portfolio, guests, schedule. No raw Blade
on any of them, no exception text, no page-level console errors.

🔬 The two non-200s are **correct behaviour, verified rather than assumed**: `/app/payouts` has no GET route at
all (only `/app/payouts/run`), and `/app/settings` returns **403** because Hotel Manager genuinely does not hold
`settings.manage` — measured against the role's 39 permissions. **That 403 is invariant #30 working**, and it
independently confirms R-F10's finding that the three sensitive permissions sit with Property Owner only.

### 4.2 An owner's day — tenant one (EUR)

> ⚠️ **WHAT IT COULD NOT DO:** no owner write was performed — no expense approved, no rate proposal answered, no
> block placed — for the same tenant-one reason. The owner **approval state machines (F-67/F-68) were not
> exercised.**

🖥 Eleven owner surfaces render on real scoped data, all in EUR, zero GBP. 🖥 The owner is bounced out of the
back office on every attempt (#26). 🖥 No guest PII anywhere in the realm.

### 4.3 A guest's stay — tenant one

> ⚠️ **WHAT IT COULD NOT DO:** no payment, no check-in, no wallet redemption — all are writes, and the only
> guests with paid reservations live in tenant one.

🖥 Trips, payments, wallet and messages all render. 🖥 Guest-to-guest isolation measured with a positive control
(§3). 🖥 A guest requesting `/app/dashboard` or `/owner` lands at `/login`.

🔬 **An incidental behaviour worth recording:** logging out of the `web` realm also ends the guest session —
they share one session store. Correct for one browser; surprising if anyone assumes the realms are independent.

### 4.4 Tenant two (GBP) — the operator's day that could not happen

> ⚠️⚠️ **WHAT IT COULD NOT DO — AND THIS IS THE MOST CONSEQUENTIAL RESULT OF THE PASS: the tenant-two operator
> back office is unreachable.** Every one of eleven `/app/*` routes redirects to `/app/billing`.

🔬 **The cause is correct behaviour with a structural side effect.** Tenant two is seeded with a 14-day trial.
Seeded at the money gate's reference date `VY_SEED_DATE=2026-09-01`, that trial ends **2026-09-15**. Today is
**2026-09-19**, so the trial is genuinely lapsed and `EnsureSubscriptionActive` correctly sends it to the
paywall — invariant #41, doing exactly its job.

⚠️⚠️ **But the two instruments are now in conflict.** The money gate only reports `mode=FULL` when the database
was seeded at the reference date; seeding at the reference date is what makes tenant two's trial expire. **So
"the money gate is FULL" and "tenant two's back office is walkable" became mutually exclusive on 2026-09-15**,
and will stay so. Earlier passes could walk tenant two only because they seeded at *today*. ⚠️ **Any future
pass that needs tenant two's operator surfaces must deal with this first, and no document currently warns it.**

🖥 What *was* reachable: the **owner** portal, which is not subscription-gated — and it is correct GBP
throughout (§3).

### 4.5 A suspended tenant's storefront

🖥 Measured and reversed; see §3. **Every prediction held.**

### 4.6 A claim by a guest who paid but never signed in

> ⚠️⚠️ **WHAT IT COULD NOT DO — THE JOURNEY STOPPED DELIBERATELY, AND THE REFUSAL WAS CORRECT.** 🔬 The only
> unclaimed guests holding paid reservations live in **tenant one**, where completing a claim would write a
> password and a verification timestamp onto a baseline row. Tenant two's sole unclaimed guest (**Tomas Weber**)
> has **zero reservations**, so the journey cannot be run there at all. **The journey is blocked by the
> intersection of two correct rules, not by a defect** — and the same structural problem as §4.4: the writable
> tenant lacks the state the journey needs, and the tenant that has it may not be written to.

🖥 What was established without writing: the request is **enumeration-neutral**; a real signed claim link is
**issued and delivered**; and both a tampered signature and a tampered hash are **refused 403**, with the guest
provably still unclaimed afterwards (§3).

### 4.7 The platform admin

> ⚠️ **WHAT IT COULD NOT DO: the nine screens behind the 2FA wall remain unaudited** — and now for a *measured*
> reason rather than an assumed one. Enrolling 2FA on the demo admin would be a write to tenant one, so it was
> not done. **§5's "9 admin screens unaudited" line stands, unchanged.**

🖥 Login succeeds and every admin route redirects to 2FA setup (§3).

---

## 5 · THE SUITE IS EVIDENCE, NOT PROOF

🔬 **`php artisan test`, run ALONE, pre-flight verified clear (0 pest/phpunit processes, 0 harness processes,
0 servers, 0 listeners on port 8000, 0 connections to `vyrobel_test`):**

> **3,595 tests · 3,594 passed · 1 skipped · 0 failed · 25,929 assertions · 14 min 12 s** (2026-09-19)
>
> 🔬 **PHPStan L5: 0 errors.** 🔬 **Pint: passed.** 🔬 **`migrate:fresh --seed`: clean.**
> 🔬 **Money gate: `mode=FULL` — `929 / 10928000 / 0 / 42 / 16 / 0`, all six tenant-one figures unchanged.**
>
> ⚠️ **That figure is historical the moment it is written.** Run it to know; never quote this line as current.

⚠️⚠️ **DO NOT READ THAT AS "THEREFORE IT WORKS."** This programme has **measured, in this very suite**: two
tests that passed while asserting nothing · one whose name said the account could never be locked out, passing
with the account removed · one that **lied about what it exercised**, bypassing the service its own comment
claimed to drive · a fix that **survived being reverted** against a full green run because nothing pinned it ·
and a matcher made unconditionally true by a message argument to a variadic assertion.

**What the suite is: a large net with known holes, and a record of where the holes were found.** A green run is
evidence at the 🔬 tier for what it asserts, and **nothing at all** for what it does not assert.

### The guards that now exist — and what each one would NOT catch

| guard | catches | ⚠️ would NOT catch |
|---|---|---|
| **Money gate** (`ops/money-as-seeded.sh`, `mode=FULL`) | any movement in tenant one's six seeded figures | ⚠️⚠️ **anything in tenant two** — it is scoped to tenant one and prints green while tenant two's money moves. It also reports only counts and sums: an equal-and-opposite pair of errors nets to zero and passes. |
| **Permission partition** (FIX 42) | a change to the 17 phantom permissions or the role census, by name | a permission that is *renamed* consistently everywhere, and any permission outside the pinned seventeen |
| **Currency detector** (`CurrencyNeverDefaultsTest`) | a currency literal behind an object that may be absent, in `app/` | ⚠️ literals in `resources/`, `config/` or a queued job; the ~39 `?? 'XXX'` fallbacks behind NOT NULL columns it deliberately ignores; and anything a *rendered* page composes that the source scan cannot see |
| **Stray-request guard** (F-35, FIX 47) | an unfaked outbound call through Laravel's `Http` facade | ⚠️⚠️ **Stripe's SDK, which transports through its own `CurlClient`** · a raw `curl_*` call · a directly-constructed `GuzzleHttp\Client` · anything a queued job does in another process. **One transport of several.** |
| **Demo-seeder guard** | a demo seed running where it must not | a hand-written migration or tinker write, which is how most fixtures in this programme were actually made |
| **Stripe safety guard** | a live key outside `production` | a wrong *test* key, a wrong account, or a correct key pointed at the wrong Connect destination |
| **The `reverse()` quarantine** (FIX 38) | any attempt to reach the double-reversal path | ⚠️ **it does not fix the defect** — F-53 and F-54 remain in the code, merely unreachable |

🔬 **Static analysis and style:** see the report's closing gate block below.

---

## 6 · ⚠️⚠️ WHAT THIS REPORT DOES NOT CLAIM

**This is the most important section in this document.** A verification that reads as reassurance is worse than
none, because it will be believed.

1. ⚠️⚠️ **It does not claim the money paths work.** Between **ten and fourteen of the seventeen money-write
   routes have never been executed by anything** — not by this programme, not by the suite, not by a browser.
   🔬 **And the exact figure cannot be established**: no enumerated list of the seventeen exists anywhere (V-3).
   What has been driven, in tenant two only: the refund path (FIX 18), the season apply (FIX 20), statement
   finalization (FIX 21), and two clusters of unstated size (FIX 27, FIX 28). **Everything else is read, not run.**

2. ⚠️⚠️ **Two of those routes are blocked STRUCTURALLY, not by scheduling.** `payouts/create` and `payouts/pay`
   need a finalized statement to act on, and `migrate:fresh --seed` — **a required gate on every pass** — erases
   tenant two every time. Manufacturing a target means finalizing another statement, which cannot be reopened.
   **A pass that rebuilds pays for it two passes later, and there is no way out of that loop as the gates are
   currently written.**

3. ⚠️⚠️ **And as of this pass the blockage is wider than payouts.** 🔬 Seeding at the money gate's reference date
   expires tenant two's trial, so **the entire tenant-two operator back office is now behind the paywall**
   (§4.4). Cross-tenant operator claims cannot be walked at all without first disabling one of the two gates.

4. ⚠️⚠️ **Owner-to-owner isolation inside one organisation has never been tested.** Cross-**organisation**
   isolation is measured and guest-to-guest is measured — **these are different claims and they do not imply the
   third.** The union-versus-per-owner capability shape has no fixture and no test. 🔬 Tenant two has exactly one
   owner; tenant one's three owner accounts were not probed against each other in this pass.

5. ⚠️⚠️ **`reverse()` is QUARANTINED, NOT FIXED.** F-53 and F-54 are **CONTAINED**: the unguarded append and the
   stranding mechanism are **still in the code**, exactly as measured, and are merely unreachable because the
   method refuses at its first statement. **By founder ruling, the product can pay an owner and cannot un-pay
   them.** Anyone who routes `reverse()` without first rebuilding it ships a double-reversal on day one.

6. ⚠️⚠️ **The stray-request guard covers Laravel's `Http` facade and nothing else.** The honest claim is *"the
   facade is guarded."* **Stripe's SDK is invisible to it.** So is raw `curl`, a directly-constructed Guzzle
   client, and anything a queued job does in another process. The suite's zero stray requests is a fact about
   one transport.

7. ⚠️ **Nothing requiring a real card, device, provider, proxy or phone has been tested, and cannot be here.**
   Stripe live keys · Channex sync · WhatsApp/OTA inbox · SMS · guest ID verification · production email
   delivery · a real proxy sending forwarded headers · TLS terminating where expected. **And the back office is
   desktop-only by ruling** (F-33, `KNOWN-LIMITS.md` L7) — that is a position, not a defect awaiting a fix, and
   54 screens sit inside that shell.

8. ⚠️⚠️ **The deferred finding is not a closure.** F-36's channel-refusal surface **does not exist**. 🔬
   `BookingImportService` writes `reservation.ota_modification_refused` to `audit_events` and **no operator
   surface reads it**. Until that surface is built, **the first symptom of a refused booking is an operator
   asking why it never arrived, and nobody able to answer from the product.** Its trigger is **GO-LIVE A10**, and
   it costs nothing only while the channel is off.

9. ⚠️ **This was a verification, not a new audit.** It did not go looking for new product defects, and **the
   absence of new product findings here is not evidence that none exist.** The five findings in §7 are
   record-integrity defects, which is what a verification is actually shaped to find.

10. ⚠️ **Several §7 lines are still 📄 RECORDED and were not re-measured today** — the owner capability matrix,
    the cross-realm money reconciliation, the owner refusal copy, and the guest realm's accessibility numbers.
    **They are believed, not known, and §3 says when each was last true.**

11. ⚠️ **"Zero open findings" is not "nothing left to decide."** 🔬 Two reservation-ladder rulings — **R2** and
    **R5** — remain unruled (V-4), and the product ships a default for both.

---

## 7 · FINDINGS FROM THIS PASS — NUMBERED AND DELIBERATELY UNFIXED

⚠️⚠️ **None of these were repaired.** A verification that fixes what it finds has changed the thing it was
measuring, and the report then describes a state nobody can reconstruct. They are recorded here for the founder
to route into a later pass.

| # | finding | severity |
|---|---|---|
| **V-1** | `AUDIT-SUMMARY.md` §8 item 7 states "F-21 remains blocked on §4"; F-21 is closed (FIX 46). | 🟡 record integrity |
| **V-2** | §4's open-findings table still shows F-35 as `⛔ STILL OPEN`; FIX 47 closed it and swept two other surfaces but not this one. | 🟡 record integrity |
| **V-3** | The "17 money-write routes" denominator is enumerated nowhere; §5 and the current brief give different remainders (fourteen vs ten) and neither is derivable. | 🟠 **an unauditable closure claim** |
| **V-4** | Ladder rulings **R2** and **R5** are unruled and absent from §4's answers table, while the headline reads "every ruling is built". | 🟠 a live default nobody decided |
| **V-5** | 🔬 **Seeding at the money gate's reference date expires tenant two's trial**, putting its entire operator back office behind the paywall. The FULL money gate and a walkable tenant two are now mutually exclusive, and nothing warns a future pass. | 🟠 **instrument conflict** |
| **V-6** | ⚠️ **An instrument fault in this pass, recorded against itself.** My first guest-isolation probe used a wrong URI and returned 404 for *every* id including the guest's own — a clean-looking green that proved nothing. It was caught only by adding a positive control. **A 404 without a control that succeeds is not a refusal.** | 🟡 method |

---

## 8 · ⚠️⚠️ WHAT IS STILL REQUIRED BEFORE A REAL CUSTOMER

⚠️⚠️ **NONE OF THIS IS CODE, AND NONE OF IT HAS MOVED SINCE BEFORE THE AUDIT BEGAN.** The full list with
consequences is `docs/GO-LIVE-REQUIREMENTS.md`; this is the shape of it.

**Infrastructure:** a server · the domain · TLS · a web server / PHP-FPM · `APP_KEY` · `BACKUP_PASSPHRASE` ·
**Redis** · `SESSION_SECURE_COOKIE` / `FORCE_HTTPS` / `TRUSTED_PROXIES` · web-server host pinning and a correct
`APP_URL`.

⚠️ **The scheduler cron (B5) — and two features now depend on it.** Without `* * * * * php artisan schedule:run`
the **14-day guest deletion and the 30-day ID purge never run, silently.** That is a GDPR obligation.
`ops/deploy.sh` installs it, but `DEPLOY_NO_CRON_INSTALL=1` **warns and completes**.

**Money:** Stripe live keys · **both** webhook signing secrets (account *and* Connect — separate endpoints,
separate secrets; a wrong one means payment taken, booking unpaid) · Connect enabled and KYC done · the four
subscription **Price IDs** · **real plan prices** replacing the placeholders · **real tax rates**
(`tax:set-rate`; the seeded ones are labelled EXAMPLES) · `PAYMENTS_GATEWAY=stripe`, which is the real money
switch and goes **last** · and ⚠️ **B17: verify every owner's payout destination BEFORE the Connect rail is
switched on** — a destination corrupted while the rail was off is harmless and invisible until the moment it
isn't.

**Also:** Passport keys per environment · Postmark token and sending domain DNS · an offsite backup target and a
restore drill.

⚠️⚠️ **AND THE LAWYER — outstanding since before the audit began.** The **Article 28 Data Processing Agreement**
(B12) and the **flow-of-funds question** (B13). Neither can be answered from this repository, and the second one
determines how money legally moves.

---

## 9 · ⚠️ EVERYTHING IS LOCAL

🔬 **This work exists on one machine and nowhere else.** The repository is over 120 commits ahead of
`origin/main` and **nothing has been pushed**. That is the same class of risk as work left uncommitted, one
level up: a single disk failure loses the entire audit and fix programme. `.mcp.json` stays untracked
deliberately (it holds a machine-local path).

⚠️ **Believe `git status` and `git log`, not this paragraph** — it goes stale the moment anything is pushed.

---

## 10 · WHERE TO READ MORE

- **`docs/AUDIT-SUMMARY.md`** — the primary record. §2 and §2b are the registers, §4 the rulings and their
  states, §5 what could never be tested, §7 what is claimed sound, §6 the method.
- `docs/GO-LIVE-REQUIREMENTS.md` — §8 above, in full, with the consequence of skipping each.
- `docs/KNOWN-LIMITS.md` — the limits to state to customers rather than fix (L7 is the desktop-only ruling).
- `docs/STATUS-LADDERS.md` — every housekeeping and reservation transition decision, including **R2 and R5,
  which are still open**.
- `CLAUDE.md` — conventions, invariants and the demo credentials.
