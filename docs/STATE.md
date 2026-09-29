# Vyrobel — STATE

**Read this first, and finish it.** **🔬 299 lines / ~3,950 words / ~18 minutes — measured 2026-09-02**, never
described, because ⚠️ **this header has now gone stale three times, the last within one edit of being written.** Everything else is *pointed to*, never repeated: a fact
written in two files drifts and the reader cannot tell which copy is stale.

> ⚠️ **THIS FILE GROWS EVERY PASS — PRUNE WHEN YOU UPDATE IT.** A settled decision becomes one struck-through line;
> history moves to git; a section that outgrows a screen moves to its own file and gets a pointer.
> **If it stops being finishable it stops working**, and the first symptom is someone acting on a premise from a
> section they skipped. ⚠️ **v4 is LONGER than v3 (260 → the count below) and that is a deliberate trade: §2b and
> §6b are the two most valuable things this sequence produced. Six sections were compressed to pay for part of it.
> ⚠️ The first draft grew it by 46 lines while its own header claimed it had not — caught only by measuring.**

> ### The four marks — ⚠️ three were not enough
> **🔬 MEASURED** = executed and observed · **📖 ASSERTED** = read from source, not run ·
> **📄 RECORDED** = written down by an earlier pass, never re-checked; a lead, not a fact ·
> ⚠️ **🖥 BROWSER** *(new 2026-09-01)* = observed in a real rendered page.
>
> ⚠️ **Why 🖥 is its own tier:** nine dead controls and eight permanently-invisible panels **survived every static
> check ever run here**. A served-HTML test asserts the markup is present, and presence was never the defect.
> Never upgrade a mark because a report says so — that is the same error one layer out.

---

## 1 · What this is

A multi-tenant hotel / short-stay **PMS SaaS** for operators running a mix of hotels, aparthotels, villas and
multi-unit rentals. Three logged-in realms — operator (`/app`), owner (`/owner`), guest (`/guest`) — plus a public
booking storefront (`/book/{property}`), public signup, and a marketing site. Built by one founder ("Subhan") with
Claude. **`CLAUDE.md` holds the conventions and demo credentials and is auto-loaded — do not duplicate it here.**

## 2 · Where it stands

**The product is built.** What remains is founder decisions, credentials, and a server.

| | |
|---|---|
| Suite | **🔬 2026-09-02** — 3,112 tests / 3,111 passed / 0 failed / 1 skipped / 23,738 assertions, run alone. PHPStan L5 **0**, Pint clean. ⚠️ **Re-measured across a MONTH-BOUNDARY date roll on unchanged code and identical to 2026-09-01** — a date roll has broken this suite twice, so it is re-run rather than inherited. |
| Demo money | **🔬 2026-09-02** — `money-as-seeded.sh` **FULL**, six figures unchanged. `owner_ledger` 29 / 730640 — ⚠️ **a separate query; the gate does not print it** (§3). |
| CI | **📄** — **8 gates, `Gate 0`–`Gate 7`** in `.github/workflows/ci.yml`. That each was observed red is 📄. |
| Marketing site | **🖥 2026-09-01** — 19 routed pages, all loaded in a browser. `/faq`, `/glossary`, `/changelog`, `/docs` are **not routed and linked from nowhere**. |
| Documentation site | **📖 2026-08-26** — **0 of 27 pages have a view.** Built and checked, not served. |
| Deployment · advisories | **📄** deploy complete (`DEPLOYMENT.md` §7.1–7.6) — **nothing has ever been deployed.** **🔬 2026-08-30** `composer audit` clean; re-run it, the answer ages. |
| §7.2 heartbeat | **📖 2026-08-31 — WIRED, and narrower than it reads.** It certifies **cron is alive** and **a worker consumes** — ⚠️ **not that any of the eight commands succeeded**: a throwing command writes one log line, `schedule:run` still exits **0**, `/up/liveness` stays **200**. ⛔ Founder: set `OPS_HEARTBEAT_PING_URL`, point a monitor at it, decide on `onFailure()`. |

### ✅ FINISHED TRACKS — do not re-open

- **Documentation corrections** — 🔬 2026-08-28: **0 outstanding-and-reachable** of 220 claims. §8 names what remains; **none is a correction pass.**
- **The payment architecture** — 🔬 2026-08-28: all three charge paths resolve a connected account; **no platform-charge fallback.**
- **The re-authentication family** — 🔬 2026-08-31, nine sensitive actions. ⚠️ A claim about *those actions and that method*, not that the realm is secure.
- **Retention**, except open ruling 6.
- **The audit's security findings** — closed or explicitly parked; `AUDIT-2026-08-29.md` §1 is the ranked record.
- **Seasonal pricing** — 🖥 2026-09-01: save · review · apply, with a precedence rule governing **both** display and price.

### ⚠️ Nothing takes real money, by default

📖 2026-08-26. `PAYMENTS_GATEWAY` and `PAYOUTS_RAIL` default **`manual`** · `StripeSafetyGuard` / `MailSafetyGuard` /
`LlmSafetyGuard` refuse live credentials outside `production` · `FakeChannexClient` is the bound default · `LLM_FAKE`
defaults true. ⚠️ **The payment path has never run against real Stripe. Production is the first place it executes.**

## 2b · ⚠️⚠️ THE HIGHEST-YIELD STEP IN ANY PASS: VERIFY THE BRIEF FIRST

> ⚠️⚠️ **A BRIEF'S CENTRAL PREMISE WAS WRONG ROUGHLY HALF THE TIME.** Not its details — its *premise*, the thing the
> whole pass was aimed at. **Checking it against the repository before acting is the most reliable finding this
> project has produced**, and it is worth more than any individual fix, because a false premise aims every check
> in the pass at the wrong thing.

| The brief said | The repository said |
|---|---|
| "A useless write" | It persisted **1 real `inventory_blocks` row and 3 real `rate_calendar` rows at €123.00**, with audit entries and a success message |
| "The transform MISSED those panels" | It was **never called on them** — three call sites, and that attribute was not one. A different fix entirely |
| "Two seeding paths disagree" | **One mechanism.** `VY_SEED_DATE` is implemented *via* `setTestNow`; the fault was a helper that cleared the clock mid-test |
| "Filter `units()`" | 🔬 It would have **erased €300.00 of real earned revenue and a completed stay** — that method is the ATTRIBUTION SET, not a unit list |
| "This needs a change to a certified service" | **Write order sufficed.** Re-applying the covering set weakest-first through the unchanged writer |
| "Fix it in `convert.py`" | The value came from a **head map**; `core/` is derived and gitignored, so neither was editable |
| "The money gate reads one of two seeded worlds" | **The gate never seeds.** It reads whatever database exists |

⚠️ **Three more from the earlier sequence, in `AUDIT-2026-08-29.md`:** "37 controls" (the masters held **~90**
handlers) · "89 of 809 unnamed" (**85 of 579**) · "four low-risk groups" (**two were unrouted**).

### ⚠️ And the counterpart: STOPPING IS A CORRECT OUTCOME

**Three passes refused to proceed, and each refusal was right.** ⚠️ A pass that stops with evidence is not a failed
pass — twice the instruction reversed on that evidence.

1. **Forbidding per-unit plans** would have **inverted a certified assertion** to dismantle a deliberately-built,
   terms-captured capability. → Ruling reversed to *wire the caller*.
2. **Filtering the owner's unit set** would have **deleted real earnings**. → Ruling reversed to *split the concept*.
3. **An ambiguous cascade** — two candidate fixes with different blast radii and no evidence to choose. → Reported.

## 3 · ⚠️⚠️ THE DON'T-REGRESS LIST — consolidated, because it became a liability

> ⚠️ **~26 items were carried into every brief, and ONE WAS FALSE FOR A DOZEN OF THEM.** A list too long to read is
> a list nobody checks. **This is the list now.**

| | Command | Covers |
|---|---|---|
| **1** | `./vendor/bin/pint --test && ./vendor/bin/phpstan analyse --memory-limit=1G && ./vendor/bin/pest` ⚠️ **suite ALONE** | Pint · PHPStan L5 · full suite = **CI Gates 1, 2, 3, 5, 7** |
| **2** | `VY_REQUIRE_FULL=1 VY_SEED_DATE=2026-09-01 bash ops/money-as-seeded.sh` | `migrate:fresh --seed` **and** the six money figures = **CI Gate 4**. Prints `mode=FULL`. |
| **2b** | `SELECT COUNT(*), SUM(amount_minor) FROM owner_ledger;` → **29 / 730640** | ⚠️ **Not covered by 2 and asserted by NO test** — `730640` appears in no `.php`/`.sh`/`.yml`. **The gap.** |

⚠️ **1 and 2 are already CI gates**; the old block re-listed what CI enforces.
**📄 CARRIED, never measured — deletion candidates:** *"the storefront still books"* in any **pinned** form · the
baseline/trailer boilerplate.

### ⚠️⚠️ NEVER PIN A DATE-RELATIVE FIGURE — the rule, and the instance that broke it

`CLAUDE.md` already says it. ⚠️ **A dozen briefs pinned the storefront aggregate anyway** and it looked like a
regression; no price had moved — the window rolled from a 2-night stay to a 1-night one. ✅ **The correct form:**
fetch `/book/seaside-demo/search` with a **fixed** window and assert priced offers exist. 🔬 **2026-09-01 and again
2026-09-02**: Double Room 3n `total_minor 25500`, One-Bedroom 3n `54000` — ⚠️ **the same figures on a window that
moved with the calendar (21→24 Sep, then 22→25).** The offers are real; the window is not a constant. **Recorded,
never pinned.**

## 4 · Never do these

1. **Never run the suite beside other commands** — parallel runs corrupt the shared test DB.
2. **Never treat a report as a verification** — ⚠️ nor *reading* as *running*.
3. **Never cut a true half to green a gate.** A red row for correct work means the row is wrong.
4. **Never amend a certified assertion.** A fixture's world may move; an assertion may not.
5. **Never type a number into a page**, and **never invent a score** you cannot point at a rendered fact to support.
6. **Never promise a future date or capability.**
7. **Never soften a false claim**, and **never negate one by quoting it** — the phrase stays on the page for a skimmer.
8. **Never fabricate seed state silently.** Demo data is flagged as demo, or not written.
9. **Never edit a generated file** — `pxbuild.py` rewrites `marketing/*.blade.php`; transforms go in `convert.py`.
   ⚠️ And **never edit `tools/marketing/core/*.html`** — it is derived and gitignored.
10. ⚠️ **Never pin a date-relative figure** (§3).
11. ⚠️ **Never pin an unpinned date case to make it green** — that deletes the detector, not the defect (§6b).
12. **Never edit a file while a suite is running** — twice done, both runs correctly discarded.

## 4b · ⚠️ ARCHIVING A PROPERTY HIDES ONE ROW — that is the whole design 🔬 2026-09-01

`archive()` soft-deletes the property and **nothing else**. Every child `property_id` is `cascadeOnDelete`, but **a
soft delete fires no cascade**, so all ~20 child tables survive — **intended**: a hard delete would destroy
reservations, folios and statements that must stay queryable.

⚠️⚠️ **THE RULE:** a read is insulated from an archive **only if bounded by a set derived from `Property::query()`**.
Operator screens are safe for free (`User::accessiblePropertyIds()`); any read reaching units **org-wide with no
property step** still sees them — **by construction, not omission.** ⚠️ **The trap that cost two passes:**
`OwnerPortalService::units()` is **the attribution set for history and money**, not a unit list — hence two
accessors, `units()` and `unitsAtLiveProperties()`. `ArchivedPropertyGhostUnitsTest` pins the €300.

## 5 · Open — and who must decide

⚠️ **None of this is queued work. Each is a decision.**

### ⛔ Only a lawyer can do these two

| # | Decision | If skipped | Blocks |
|---|---|---|---|
| 1 | **Article 28 data-processing agreement** | No lawful basis to process customer data | Any real customer |
| 2 | **Flow-of-funds / money-holding question** | Unknown regulatory exposure | Taking real money |

### The founder's — go-live

| # | Decision | If skipped | Blocks |
|---|---|---|---|
| 4 | **Stripe live keys + dashboard steps** | No payments | Go-live |
| 5 | **Hosting region, backup location, mail region** (Hostinger mail since 2026-09-25, not Postmark) | Cannot answer a lawyer's first question | The DPA |
| 6 | **A `failed_jobs` retention window** — a failed mailable persists a recipient email and, once, **a whole statement PDF** | PII kept indefinitely | GDPR posture |
| 7 | **`core/terms.html`'s per-unit proration clause** — meaningless under flat billing. Possibly a lawyer's | A contract contradicting the invoice | Publishing terms |
| 10/11 | **IP + user-agent** in exception logs *and* platform audit rows — **same question, opposite defaults** | Personal data across rotations | GDPR posture |
| 12 | **`sales@vyrobel.com` has no mailbox** — ruled to stay on the privacy page (rights contact) and the terms (support line); everything else prints `contact@` since 2026-09-26. Mail to `sales@` is REJECTED until one alias exists in hPanel (KNOWN-LIMITS L18) | Privacy requests and support mail bounce | Go-live |

### The founder's — product, nothing blocked today

**Money-adjacent, before real money moves:** **H6 permission toggles** (⚠️ **17 of 51 — corrected FIX 42; this said "12 of 46", a figure produced by a census with no comment stripping over a denominator blind to wildcard roles**) (remove,
wire or label; `KNOWN-LIMITS.md` L6) · the **widened negotiated-rate collision** (applying a season re-applies
overlapping *applied* seasons, overwriting manual edits in their range) · **no season undo/edit/delete** (`setRate()`
records the new price with `before` literally `null`) · the **live-plan-vs-snapshot read** · the **unalerted sync
failure** (one log line, no monitor) · **out-of-service units still bill** (deliberate; unratified).

**Reversibility & silence:** archiving a property is **irreversible in-app** · ⚠️ **the owner IS now told (FIX 43,
R-ARCHIVE) — this read "the owner is told nothing" until then** · no **un-archive** route for owner removal · no **last-owner guard on the ownership
axis** · **pause/resume works on a ghost unit** (inert).

**Left with reasons, not oversights:** ⚠️ **search** may legitimately need to find an archived unit, and the
**subject-access export** arguably must include everything the subject is attributed. **Neither was changed — a
data-protection decision is not made to tidy a list.**

**Marketing** (`BROWSER-AUDIT-2026-09-01.md`): four **dead controls** (pricing Monthly/Annual, "Download pricing as
PDF", footer "Join", "Daylight") · the **duplicated cookie banner** on all 19 · **13px horizontal overflow** at 375px
on all 19 · **tap targets under 44px** (29–95/page) · the **stripped print stylesheet** (the `@page` rules never
reach the generated views, so "Print this story" prints the site chrome) · **`data-portfolionote`** — real pricing
copy hidden by inline CSS, referenced by nothing.
**Accessibility:** **30 dialogs** without the D2 contract · **79 unnamed controls** · two structural keyboard
surfaces · ⚠️ **a human check on a real phone** — no harness replaces it.

**Also registered — point, don't re-list:** `GO-LIVE-REQUIREMENTS.md` holds **15 blocking + 7 advisable**.
Struck: ~~the billing model~~ (settled 2026-08-28: flat).

## 6 · ⚠️ THE INSTRUMENT-ERROR CATALOGUE — the most valuable output of the work

⚠️ **🔬 2026-09-01 — 54 instances in 13 classes.** Each is a check that looked like protection and wasn't; each class
recurred, and the next will wear a new face. **The evidence is `docs/INSTRUMENT-ERRORS.md`** — consult it when you
write a check. The names only, so you can recognise one: ⚠️ 🔬 2026-09-25: `stat` on a **symlink** reports the LINK's mode (always 777) — KNOWN-LIMITS L15 read `.env` as world-writable from exactly that; `stat -L` reads the file (600). Class **C**. ⚠️ 🔬 2026-09-26: Laravel 13's `Schema::getTables()` with **no argument lists every database the connection can see** (five on the dev machine) — the shipped privacy inventory named six OTHER projects' tables, and its drift test stayed green because it regenerated through the same list; scope it (`getTables(Schema::getCurrentSchemaName())`) and check against an independent read. Classes **E** and **B**. ⚠️ 🔬 2026-09-26: a schema read fast on BOTH local engines took **1.1 s per call on production's MariaDB** — a 118-second page; only a preflight TIMED ON THE HOST saw it (L12's "not the verified architecture", again). Class **E**.

**A** the assertion that cannot fail · **B** proving something ADJACENT · **C** the wrong layer or target ·
**D** placed wrong in time · **E** green as an artefact of the environment · **F** the mutation that lies ·
**G** the silent no-match · **H** the static check that cannot see runtime · **I** the pinned figure that rots ·
**J** the read that is too small · **K** the control that answers without working · **L** the gate that can be
lifted · ⚠️ **M** *(new)* the premise that was never checked — see §2b.

⚠️ **The four that cost the most:** a **negated variadic matcher** passing on its own absence (3 instances, one by
the author of the lint banning it) · **`php -l` clean on an unresolved class** while the login route 500'd — **it
parses, it does not resolve** · a **composition fatal printed as SILENCE** (zero bytes, exit 1, empty logs — ⚠️ **if
a run returns empty, suspect a fatal before the tests**) · **a transform never called**, invisible to `_NEEDLE_LOG`.

## 6b · ⚠️⚠️ THE WRONG-FIX MUTATION — mutate to the fix you REJECTED

Breaking the correct fix proves a test notices *that code*. **Re-introducing the fix that was rejected proves the
test defends the DECISION.** 🔬 Four instances, 2026-09-01: setting the flag on archive · merging the two owner-unit
accessors (**the €300 test**) · adding a "save and apply" shortcut · a display rule differing from the write path.
**Without it, a future author "tidying" two accessors into one gets a green suite and erases someone's money.**

⚠️ **Its limit: no mutation can catch a change that makes the suite GREENER.** Pinning the one unpinned date case
would delete the detector while turning everything green. **That is why some things are rules (§4.11), not tests.**

## 7 · The corrected canon — ⚠️ the correction, not only the truth

⚠️ **Recorded as corrections so nobody re-derives the wrong one.** Each was **🔬 measured false.**

| Long-carried claim | Status |
|---|---|
| *"A guard-qualified rule against the wrong guard validates NOTHING, silently."* | ⛔ **FALSE.** A wrong-but-**defined** guard **fails closed**; an **undefined** one **throws**. |
| *"`current_password` arrived 2026-07-27, so the suspect set is date-bounded."* | ⛔ **WITHDRAWN. Sweep by ACTION SENSITIVITY, never by date.** |
| *"A type-the-name confirm is UI-only."* | ⛔ **FALSE** — validated server-side. It still does not stop a hijacked session. |
| *"§N.19.6 is an unrecorded-actor defect."* | ⛔ **FALSE.** A **billing** lesson: an invariant is only as strong as the ACTORS it was checked against. |
| *"The scheduler's failures are completely silent."* | ⚠️ **TOO STRONG.** One log line is written. **Silent to every MONITOR, not everywhere.** |
| *"10 dompdf advisories."* · *"The deploy refuses if told to skip the cron."* | ⛔ **STALE** (dompdf v3.1.6, audit clean) · ⛔ **FALSE** (`DEPLOY_NO_CRON_INSTALL=1` warns and completes). |
| ⚠️ *"`OwnerPortalService::units()` is a unit list."* | ⛔ **FALSE.** It is the **attribution set** for the owner's history and money (§4b). |
| ⚠️ *"`per_unit_minor`."* | ⛔ The column is **`unit_rate_minor`**, and it is **not inert** — **six live readers**. An unpopulated column with live readers is its own category. |
| ⚠️ *"The subscription quantity sync is missing."* | ⛔ **FALSE.** Complete — proration and idempotency already decided. **Only a caller was absent.** |
| ⚠️ *"The money gate reads one of two seeded worlds."* | ⛔ **FALSE. The gate never seeds.** The failing world existed only inside PHPUnit and could never reach it. |

### ✅ And the ones that HELD — checked, and right

`owner_ledger` 29 / 730640 · **8 CI gates** · **19 marketing pages** · *"parallel runs corrupt the shared test DB"* ·
**`GO-LIVE` B11's "four subscription plans"** — 4 `plans` rows = 2 tiers × 2 intervals · `config/billing.php:65` really
holds the prices **A2** points at · ⚠️ **the storefront is safe from archived properties** — `ResolvePublicProperty`
resolves through `Property::query()`, so an archived property 404s (🖥 2026-09-01).

## 8 · Will never close — and the guards deliberately left INERT

**41 unmatched audit fragments** (the audit paraphrased; nothing to match) · **12 channels claims** (parked) ·
**3 half-cut rows** (unrepresentable by the gate's present/absent rule) · **known product limits** —
`KNOWN-LIMITS.md` is the customer-facing record.

**Inert on purpose:** the **revoke bound** (the symmetric half of a reachable escalation guard) · the **last-admin
floor** (reachable on the matrix, not on revoke; counts only holders who can sign in) · the **weaker money-gate mode**.

⚠️⚠️ **An open hole the floor cannot close:** `OwnerManageController` never consults it, so removing an owner can
leave an org with **zero** sign-in-able role-managers — **unrecoverable from inside the product** (§0o.3).

## 9 · Rulings worth knowing — ⚠️ a ruling without its reason gets re-opened as an oversight

**Delete permanently (ADMIN 05, 2026-09-26)** — the founder's "delete everything, always" replaces "no hard delete" for
ONE Owner-only action on a suspended or archived, unprotected organisation; archive stays the reversible option and
nothing deletes on a timer. The console's own records of it are kept. · **⚠️ A HOLD, NOT A RULING (ADMIN 06, 2026-09-27)** — delete refuses any organisation with LIVE-mode Stripe money records until his lawyer rules how long they must be kept; test-mode records never block; one method (`OrganizationDeletion::liveMoneyRecords()`), removed when the ruling comes. ⛔ No live Stripe key until ADMIN 06's refusals are live (`STRIPE-PRODUCTION.md`, top). · **Direct charges on the operator's connected account** — the conservative end: whatever a lawyer says about who may
hold funds, direct charges cannot be wrong on it. · **Standard Connect, not Express** — Express puts dispute
liability on the platform. · **No statement-descriptor field** — it would claim a control we do not have. · **The
channels page is stopped**, not deferred; it may make **no** future promise. · **The changelog was refused** — no
release tags, so any entry would be invented. · **The 22 ⚠️ caveat markers stay.** · ⚠️ **The pricing period toggle
was refused twice — but the button is still on the page and still clickable.** A refused feature that leaves a
live-looking control is worse than no control; that is a separate, open defect.

## 10 · Where everything else lives — point, don't copy

| Document | What it holds |
|---|---|
| ⚠️⚠️ **`AUDIT-SUMMARY.md` (776 lines) + `AUDIT-PHASE-0.md` … `AUDIT-PHASE-14.md`** | ⚠️⚠️ **THE CURRENT PRIMARY FINDINGS RECORD (added 2026-09-14).** A fifteen-phase browser audit and the fix programme that acted on it: **F-1…F-43** with status and evidence, the open founder rulings, ⚠️ **what could never be tested**, and the method. ⚠️ **This file previously named it ZERO times, so a cold session told to start here could not find it.** The two rows below are EARLIER audits — correct as history, superseded as the current record. |
| **`AUDIT-2026-08-29.md` (1,977 lines)** | ⚠️ **THE PRIMARY FINDINGS RECORD.** §0–§0o per-pass detail · §1 ranked · §4 false positives · §6 what could not be checked. |
| ⚠️ **`BROWSER-AUDIT-2026-09-01.md` (266 lines)** | ⚠️ **THE RUNTIME RECORD — the only 🖥 evidence.** What the JavaScript does when clicked, the console, real contrast, real widths. ⚠️ **It SAMPLED: console on 5 of 19 pages, focus order on 1, contrast on 1, 768/1280px spot-checked only.** Its §0 coverage statement is the honest bound. |
| **`INSTRUMENT-ERRORS.md`** | **54 instances in 13 classes** — consult when you write a check. §6 holds only the names. |
| **`KNOWN-LIMITS.md`** | ⚠️ **The customer-facing truth** — six limits, each pinned to the code that makes it true, and where each is told. |
| `CLAUDE.md` | Conventions, stack, demo credentials. Auto-loaded — do not duplicate here. |
| `GO-LIVE-REQUIREMENTS.md` · `-RUNBOOK.md` · `DEPLOYMENT.md` · `BILLING-STRIPE-PRICES.md` | 15 blocking + 7 advisable; the ordered steps (**money switch is Phase 4, last**); the runbook; ⛔ the founder's Stripe steps. |
| `DATA-RESIDENCY-INVENTORY.md` · `legal/DATA-INVENTORY.md` · `CONNECT-TARGET-DESIGN.md` | Written **for a lawyer**: every store, sub-processor, retention window; why direct charges. |
| `PROJECT-STATE.md` · `DECISIONS.md` · `SESSION-HANDOFF.md` | Full history and the §N canon. ⚠️ **All three are stale as *current state*** — reference works only. |

### ⚠️ Where documents disagree, and which is right

1. **`PROJECT-STATE.md` / `SESSION-HANDOFF.md`** — the handoff (2026-08-22) predates the Connect switch; the
   suite figure (2,524) is superseded by **🔬 3,112**. ⚠️ **Both are stale as *current state*; correct as history.**
2. **`CLAUDE.md` calls `PROJECT-STATE.md` "the invariant list #1–37"** — its own latest marker is **#1–73**: a stale
   description of another file. ⚠️ **73 is 📄 — never re-derived.**
3. ⚠️ **This file once called the billing model an open founder decision, and once implied the money gate covers
   `owner_ledger`. Both were wrong.** ⚠️ **A state file can be the thing that is wrong** — hence the marks.
4. **`DATA-RESIDENCY-INVENTORY.md` said guests cannot self-serve erasure — wrong**; erasure exists with undo. The
   **export** half stands.
5. ⚠️ **STILL OPEN — `legal/DATA-INVENTORY.md` contradicts itself.** §299 says *"there is no /app export route"* on
   an enumeration of `routes/web.php:836-897`, while `/app/security/export` sits at **`routes/web.php:983`** —
   outside the range read; §170 of the same file records export correctly. ⚠️ **The conclusion may still be right**
   (that export emits only the acting user's own account fields, so Art.15 portability is absent) **but its stated
   reason is false.** ⛔ **A legal document is corrected by its author and a lawyer, not by a build pass.**
6. ⚠️⚠️ **The briefs disagree with the repository roughly half the time** — §2b. **The repository is right.**

---

⚠️ **🔬 Measured 2026-09-14: 300 lines / ~4,039 words / ~18 minutes.** ⚠️ **This header has gone stale three times:**
v3 claimed 237 against 260; a v4 draft claimed "held flat" while adding 46 lines; and a two-line edit made the fresh
299-line count wrong **within the same pass, twice**. **Re-measure with `wc -l docs/STATE.md` on every edit — a length you
describe instead of measuring is wrong by the time anyone reads it.**
