# Vyrobel — Project State (resume here)

**On a fresh session read THIS first, then `docs/DECISIONS.md` for the dated "why".** This is a
pointer map to the real code, not a copy of it — follow the links on demand. If anything here
conflicts with the repo, **trust the repo** and fix this file.

---

> ⚠️ **NEW SESSION? START WITH `docs/SESSION-HANDOFF.md` (2026-08-18)** — the full master state, reconciled
> against the repo. ⚠️ Its §0 records four drifts found during reconciliation, the load-bearing one being that
> **the operator audit REPORTS were never merged to main** — they exist only on `audit/operator-deep-parity`
> (`57ea036`), so that branch must not be pruned.

# ⚠️ CANONICAL STATE — **ELEVEN CHAPTERS CLOSED** (§N.13–§N.27) · ⚠️ **THE BACKEND AUDIT FOUND NO ROT** — every route resolves, zero stubs/dead actions, exactly THREE wrong-not-absent defects (all fixed) → the remaining feature work is ADDITIVE · ⚠️ **NOTHING ON THE FEATURE BACKLOG BLOCKS LAUNCH — the gates are the DEPLOYMENT SPRINT (zero deploy artifacts + the ONE crontab line the GDPR deletion depends on) and the FOUNDER's Stripe/VAT steps** · Wave 0 + Wave 1 complete; Wave 2 complete except `DO3-3` (next, money-adjacent — prove idempotency) · **THE BILLING CODE *AND* BUILD ARE COMPLETE AND HONEST** (`display = charge = STRIPE`, admin side included, §N.19.6) · suite **2185 / 2194**, 6 non-green = the documented set (⚠️ the count **walks a 5–10 band on its own**, §N.17.7 — and ⚠️ **run it ALONE**) · ⚠️ **"closed" = closed everywhere a pass could REACH** · deployment + scale-perf REMAIN

<!-- SAVE v8 (2026-08-12) — supersedes v7. v7 was written with the GUEST realm untouched. v8 closes the
     DESIGN-PARITY WALK CHAPTER: the guest realm's 12 gates (§G-1…§G-12) and the first CROSS-REALM PDF sweep
     (17 templates + PdfService) are COMPLETE. Its new canon is §N.13 — the three-inversions thesis (the gap
     was ALWAYS adjacent to the certified guard), the mutation discipline complete in BOTH directions
     (false-GREEN 4 ways + false-RED via a broken file → `php -l` after every mutation edit),
     defence-in-depth-needs-per-layer-tests, the matured contrast method (freeze by COMPLETING animations),
     the §N.4k taxonomy, the five-survived/several-corrected §N.8b record, the verify-every-promise family,
     and DO10-4 reframed as CORRECTNESS. The reconciled 43-item DO-register lives at the end of
     MISSING-BACKEND-BY-PAGE.md. -->

<!-- SAVE v9 (2026-08-12) — supersedes v8's RESUME; v8's §N.13 canon stands unchanged. v9 closes the CLEANUP
     CHAPTER: five passes after the walk (DO-INV-4 e994dc6 · DO-EMAIL-1 b940cda · DO-EMAIL-2 d517187 ·
     DO-EMAIL-3 ac502bf · DO10-4 ee6be74). Its canon is §N.14 — the cross-pass synthesis, headlined by
     ⚠️ A CONTRAST/RENDER PASS IS BOUNDED BY THE ROLE IT RUNS AS (a conditional SURFACE is invisible too, and
     the condition can be AUTHORIZATION — extends G10). Also: a CERTIFIED component can have un-measured
     defaults; INPUT vs DISPLAY changes the fix; the toolchain false-RED pair (php -l AND view:clear) plus a
     guard vacuous against its own subject; a number that moves is not automatically a correction; a colour
     has several grep-forms; a re-scoping note can amputate an item's founding scope; a shared layout's
     defaults are content and can be FALSE; token-drift means map to the TOKEN.
     ⚠️ "Closed" means closed everywhere this pass could REACH — see §N.14.1 and DO-EMAIL-5.
     ⚠️ DO-EMAIL-9 is a LIVE false claim on a security email — the runway's item ①.
     [v11: CLOSED at 52321b8 — the footer reason is now keyed with a generic-true default. This line is kept as
      the v9 snapshot it was, annotated rather than rewritten, so the reasoning that was true then stays legible.]
     ⬅ THE NEXT CHAPTER IS THE BACKEND PHASE — start it in a FRESH chat. -->

<!-- SAVE v10 (2026-08-12) — supersedes v9's RESUME; §N.13 and §N.14 stand unchanged. v10 folds in THE FIRST
     BUILD since the walk: the guest FAQ centre (23bf335, GET /guest/help) over the certified
     HelpKnowledgeBase. Its canon is §N.15 — verify-every-promise now covers AUTHORING (verify BEFORE writing;
     when the true answer VARIES, ROUTE to the authoritative screen rather than pick a number); three new
     guard traps (a vacuous assertion can hide in a SHARED NAV · N.14.2 extends to reused CSS classes, e.g. an
     undefined .vy-sr-only hides nothing · an absence assertion must read VISIBLE TEXT because CSS comments
     ship); the canon applied AT BIRTH held; and the Guest-Help verdict was SPLIT — the FAQ half BUILT, the
     support half SCOPED (§N.15.6) and conditional, not permanently refused.
     ⚠️ THIS RESUME IS THE SESSION-HANDOFF SURFACE — a fresh session should orient entirely from it. -->

<!-- SAVE v11 (2026-08-12) — supersedes v10's RESUME; §N.13/§N.14/§N.15 stand unchanged. v11 closes the BACKLOG
     SEQUENCE: the support backend (6550087) then DO-SEED-1 (f9142c8) · DO-EMAIL-9+8 (52321b8) · DO-EMAIL-4
     (a70c147) · DO-EMAIL-5/6 (2e8cd0f) · DO-EMAIL-7 (4440c7a). Its canon is §N.16 — §N.14.1 turned into a
     REUSABLE PROCEDURE (with the permission-DIFF that makes it finishable and the subset-algebra that bounds the
     frontier); a THIRD way a surface hides, by DATA, completing the state/authorization/data taxonomy;
     KEYED-NOT-TYPED as the house pattern for copy that must stay true, load-bearing on a GENERIC-TRUE default;
     a-comment-is-not-a-defence (the root form of the stale-claim family); consolidation-can-be-an-honesty-
     regression and its mirror, don't-guard-a-false-invariant; the translucent-gradient POSITIONAL plate,
     de-emphasis belongs on a redundant mark, and four more instrument faults; and verified-fine as an honest
     outcome — with §N.13.1's thesis holding to the last pass (the defect sat BESIDE the flagged item).
     ⚠️ The demo DB is now date-robust, so a fresh session needs no hand-provisioning.
     ⬅ NEXT: ⛔ the BILLING blocker (a founder priority call) · DO-TOKEN-1 · DO-EMAIL-5-continued + the DATA axis. -->

<!-- SAVE v17 (2026-08-15) — supersedes v16's RESUME; §N.13–§N.26 stand unchanged. v17 closes TWO chapters the
     v16 RESUME never listed: ⑩ the RESTORE/A11Y TAIL (§N.22–§N.26) and ⑪ THE BACKEND AUDIT + THE FEATURE WAVES
     (§N.27) — the pivot from repair to shipping. The audit's verdict is the frame for everything after it:
     ⚠️ NO ROT (every route resolves, zero stubs), exactly three "wrong not absent" defects (all fixed), and
     ⚠️ NOTHING ON THE FEATURE BACKLOG BLOCKS LAUNCH — deployment does.
     ⚠️ v16's §N pointer was STALE (it read §N.13–§N.21 while §N.22–§N.26 already existed); corrected here. -->

> ### ⬅ RESUME HERE — **THE SESSION-HANDOFF SURFACE.** Read this, then §N.27 (newest), then §N.13–§N.26, then the DO-register.
>
> *Written so a session with no other context can start work immediately. Everything below is verified state,
> not summary. **SAVE v17 (2026-08-15).***
>
> ## WHERE THE PROJECT IS — IN ONE PARAGRAPH
>
> The product is **built, audited, and design-parity-complete across all realms**. A read-only backend audit
> (`a743bcd`) then established the single most important fact for planning: ⚠️ **THERE IS NO ROT** — every route
> resolves, there are zero stubbed actions and zero dead controls, and exactly **three** items were *wrong*
> rather than *absent* (all three fixed). So the remaining feature work is **additive**, and ⚠️ **none of it
> blocks launch** — the launch gates are **infrastructure and founder steps**, nothing else (see LAUNCH GATES).
> **Wave 0 (correctness) and Wave 1 are complete. Wave 2 is complete except `DO3-3`, which is next.**
>
> **ELEVEN CHAPTERS ARE CLOSED, IN ORDER** (①–⑨ unchanged from v16 — see §N.13–§N.21):
> **① THE DESIGN-PARITY WALK** (§N.13) · **② THE CLEANUP SEQUENCE** (§N.14) · **③ THE FIRST BUILD + ITS
> BACKEND** (§N.15) · **④ THE BACKLOG SEQUENCE** (§N.16) · **⑤ THE BILLING WORKSTREAM** (§N.17 + §G.1) ·
> **⑥ THE DO-BILL PAIR** (§N.18) · **⑦ THE PLAN PANEL** (§N.19) · **⑧ THE PRE-BILLING TAIL + THE PROBE SAGA +
> A RETRACTION** (§N.20) · **⑨ THE A11Y FAMILY + AXIS 3** (§N.21).
>
> **⑩ THE RESTORE / A11Y TAIL (v17, §N.22–§N.26)** — `DO-A11Y-3+4+5` (`f3b469e`) · `DO-EMAIL-5-continued-4`
>   (`d521995`) · `DO-RESTORE-1` (`8582b8c`, an audit that **restored nothing — the premise did not hold**) ·
>   `DO-RESTORE-1a` (`0b6f39d`) · `DO-RESTORE-3` (`c2c9f39`) · `DO-EMAIL-5-continued-5` (`c570a5c`) ·
>   `DO-PROBE-3` (`ce5c281`) · `DO-STATE-1` (`5b5f324`) · `DO-FRONTDESK-1` (`8aaf71b`) · `DO-RESTORE-4`
>   (`5da284f`) · `DO-RESTORE-5` (`f1184d2`). ⚠️ **Headline: when no text colour can clear both ends of a
>   gradient, the background is the variable, not the text** (§N.26.1).
>
> **⑪ THE BACKEND AUDIT + THE FEATURE WAVES (v17, §N.27)** — the fourteen passes below. ⚠️ **Headline: the
>   ledger under-described FOUR items out of ten, and on a security item that gap WAS the item** (§N.27.1).
>
> ### THE SHIPPED LEDGER — every hash below is confirmed against `git log`
>
> | # | item | commit | what shipped | key finding | follow-ups |
> |---|---|---|---|---|---|
> | 0 | **`DO-BACKEND-AUDIT-1`** | `a743bcd` | the read-only remaining-work ledger → **`docs/BACKEND-REMAINING-WORK.md`** | ⚠️ **NO ROT** — every route resolves, zero stubs/dead actions; exactly **3** wrong-not-absent; ⚠️ **launch-blocking = INFRASTRUCTURE ONLY** | drives Waves 0–4 |
> | 1 | **`DO-CORRECTNESS-1` 1/3** | `4c18534` | payout badge: `processing` → "In transit", `held` → "On hold" (both had read "Payout pending") | **three** definitions across **two disjoint tone vocabularies** — converged on the two that share one, left the third (§N.27.4) | — |
> | 2 | **`DO-CORRECTNESS-1` 2/3** | `d154e48` | the Schedule PDF's false footer assertions → one true snapshot line | ⚠️ caught a worse one beside it: **"statutory rest periods observed"** — a labour-law claim the code cannot support (§N.13.1) | → `D34-1` |
> | 3 | **`DO-CORRECTNESS-1` 3/3** | `ba290c9` | waitlist dedupe on a **generated non-null `window_key`** | ⚠️ a naive UNIQUE index is **NULL-inert** in MySQL — the sibling table's rule had to be re-derived, not copied | — |
> | 4 | **`D34-1`** (Wave 1) | `dd4a5ef` | `RosterWeek` gains thin `publicationState()`/`openShifts()`; the Schedule PDF renders them truthfully | ⚠️ **same DATA, not same FORMATTING** — `diffForHumans()` is true on screen and **false on paper** (§N.27.3) | `D34-1a` grid convergence · `D34-1b` impossible null-check · `D34-1c` department filter |
> | 5 | **`DO13-4`** (Wave 1) | `23422be` | real password composition (`min(8)->letters()->numbers()->symbols()`) via ONE `App\Support\Auth\PasswordPolicy`, bound through `Password::defaults()` | ⚠️ **THE AUDIT NAMED 4 PATHS; THERE ARE 9.** A content-sweep is blind to a screen with no rule at all — **sweep the ROUTE** (§N.27.1). `CreatePlatformAdminCommand` (min 12 + mixedCase) deliberately **NOT** collapsed | `DO13-4a` HIBP `uncompromised()` — optional founder call; the guard asserts it is OFF, so enabling it is deliberate |
> | 6 | **`D24-2`** (Wave 1) | `ec42515` | passkeys wired into the operator realm — the subsystem **reused** via `<x-auth.passkeys>`, not rebuilt | ⚠️ **THE DEVICE GATE WAS LIFTABLE** — a **CDP virtual authenticator proved operator passkey login end-to-end** (§N.27.8). Sign-in needed **no wiring** (shared `web` guard + `RedirectsByRole`) | **`D24-2a` FIXED in this pass** — the controller hardcoded `owner.passkey_registered`, logging the **wrong realm** into the audit trail |
> | 7 | **`D3-4`** (Wave 1) | `bbd1bbd` | last-year occupancy suppresses on **COVERAGE**, not presence (`MetricSet::coveragePct` / `hasComparableCoverage`) | LY divided by the availability **spine**, so a 3-of-30-day month showed a **fake full %**. ⚠️ **3 consumers, not the ledger's 1.** ⚠️ `->distinct()->count('date')` returned **0 for every period** — a compiling query, silently wrong (§N.27.7). A pre-existing fixture **encoded the bug** — the fixture was fixed, not the rule | **`D3-4a` DECIDED: SUPPRESS at 80% STANDS** (below) |
> | 8 | **`D1-3`** (Wave 2) | `8f5c953` | `expected_arrival` on the dashboard arrivals, matching Front Desk char-for-char | ⚠️ the ledger's "populated" was **0 of 41** on demo — it is written at online check-in, which the demo never runs, so **the POPULATED case was the unrendered branch** (§N.27.7). Free text, never a defaulted clock | `D1-3a` extract `<x-operator.expected-arrival>`; a parity test reads BOTH sources meanwhile |
> | 9 | **`D2-1`** (Wave 2) | `7bff7bd` | `max_occupancy` as **`Sleeps N`** on the rack row, matching the existing idiom | ⚠️ schema is `int unsigned NOT NULL DEFAULT 2` → **NULL is impossible**; the real edge is a storable **`0`** (unrecorded ≠ sleeps-nobody) → mapped `0 → null` so it omits (§N.27.2) | `D2-1a` over-capacity detection (now cheap; logic + product call) · `D2-1b` **two existing `Sleeps N` surfaces render `0` unguarded** |
> | 10 | **`DO9-1`** (Wave 2) | `d7da6ab` | the Add-Unit `notes` textarea — the write side; validation + persistence already existed at **0-line diffs** | ⚠️ **ADDING AN INPUT CAN WEAPONIZE A DORMANT DISPLAY** — the read-back already existed *and already escaped* (`{{ }}`); a `{!! !!}` there would have made this owner→operator **stored XSS** (§N.27.9). §O9's Alpine-root trap re-met | `DO9-1a` a long note dominates the joined detail line (cosmetic) |
> | 11 | **`D10-2 + D10-5`** (Wave 2) | `166e0bd` | inbox **stage** + **VIP** badges — **REUSED** from Front Desk, not rebuilt | ⚠️ **A LOGGED "STOPPED FOR A GOOD REASON" IS A GIFT** — a prior pass found that feeding raw status to `ReservationStage::meta()` renders **"Unknown"**; this pass **satisfied the objection**, and **mutation M2 now reproduces that exact bug** (§N.27.10) | — |
> | 12 | **`D29-5 + DO6-4`** (Wave 2) | `17847bd` | per-notification **dismiss** in BOTH realms — ONE mechanism (`notifications.dismissed_at` + one filter in the one `NotificationFeed` builder, so the unread count inherits it) | ⚠️ **THE ANTI-CONFLATION:** `owner_attention_states` and `notifications` **both** carry `read_at` + `dismissed_at` — same names, unrelated meanings. Dismiss leaves the attention store **untouched** (asserted: 0 rows after). Morph-alias fixture trap caught (§N.27.2) | — |
> | 13 | **`D3-1`** (Wave 2) | `b28122d` | ⚠️ **SHIPPED** (see the discrepancy note below) — a custom `?from=`/`?to=` range on operator Insights, resolved by the shared `CustomRange` **inside the one `resolveRange()` seam** | it **inherits D3-4's coverage guard** because it feeds the same `PerformanceMetrics` path — a custom metric path would have re-opened the exact fake-full-month figure D3-4 had just closed. ⚠️ **The 13-month max span is dictated by `monthlyTrend*()`'s `$guard < 13`**, not taste (§N.8d). LY needed nothing — already a span shift | `D3-1a` the **owner** Insights has its own `resolveRange()` (3 presets vs 5) and no custom window |
>
> ⚠️ **ONE RECONCILIATION NOTE, kept because it is the method working.** The v17 checkpoint brief listed `D3-1`
> as *"IN FLIGHT — prompt written, NOT run."* **`git log` shows it shipped at `b28122d`.** The source won; the
> row above is written from the commit, not the brief. If a future brief and the history disagree, **trust the
> history** and record the correction rather than the claim.
>
> **Suite 2185 / 2194 · 14,065 assertions · 6 non-green = the documented set** (`BookingImportTest` ×5 ·
> `OperatorGuestProfileTest` ×1). ⚠️ **The count WALKS a 5–10 band on its own — neither a fix nor drift**
> (§N.17.7). PHPStan L5 (0) · Pint clean · `migrate:fresh --seed` clean. ⚠️ **The seed needs MAILPIT running**
> (SMTP 127.0.0.1:1025) — an abort without it is **ENVIRONMENTAL**, not a regression.
> ⚠️ **Run the suite ALONE** — a parallel run corrupts the shared test DB and invents ~17 spurious failures.
>
> ## THE DECISIONS THAT ARE MADE (do not re-litigate; see §G.1 + `DECISIONS.md`)
>
> - **BILLING** — pricing is **configurable DATA** and a plan declares its own shape; per-unit **PRORATES**; the
>   flat tiers are **€49 / €99 VAT-INCLUSIVE**; per-unit VAT via **Stripe Tax (Option B)**; plan CRUD is live in
>   the super-admin panel. ⚠️ **The old "flat tier OR per-unit?" blocker was a FRAMING ARTEFACT and dissolved**
>   (§N.17.1) — what remains is dashboard steps, not a choice.
> - **THE RESTORE RULINGS** — the CTA wireframe orange **`#E8703A` is sub-AA and ACCEPTED** by founder decision;
>   the avatar + plan-tag plates are **flattened** with dark warm-brown text (§N.26.1). ⚠️ These are **decided
>   exceptions, not open defects** — but a *regression* on them must still read as a defect (§N.26 closing note).
> - ⚠️ **`D3-4a` — SUPPRESS at 80% coverage STANDS** (not caveat). A caveated number still gets anchored on, and
>   the observed gap (36% → 100%) makes the exact threshold non-delicate.
> - **40 RATIFIED design divergences MUST NOT be "fixed"** — see `docs/design/DIVERGENCE-REGISTER.md`.
>
> ## OPEN FOLLOW-UPS (all logged in the ledger; none blocking)
>
> `DO13-4a` HIBP/`uncompromised()` (optional founder call) · `D34-1a` / `D34-1b` / `D34-1c` (grid convergence ·
> the impossible null-check · the department filter) · `D1-3a` (extract the arrival component) · `D2-1a` /
> `D2-1b` (over-capacity detection · the two unguarded `Sleeps 0` surfaces) · `DO9-1a` (long-note truncation) ·
> `D3-1a` (custom range on the owner Insights) · **design remainders:** `DO-EMAIL-5-continued-6` (the axis-3
> tail, ~55 files logged-UNSWEPT = ⚠️ **unmeasured, not "probably fine"**) · `DO-STATE-2` · `DO-PROBE-4` ·
> `DO-PROBE-3-gap-C` (blocked on a rasterizer). ⚠️ **`D24-2a` was FIXED inside `D24-2`, and `D3-4a` is
> DECIDED** — neither is open.
>
> ## ⬅ WHAT TO DO NEXT — the backlog, in order
>
> **① `DO3-3` — failed-payout Retry / recovery.** ⚠️ **The last Wave-2 item, size M, and MONEY-ADJACENT.** The
>   payout status exists; the owner-facing recovery affordance does not (`OwnerPayouts.php:44` is a comment).
>   ⚠️ **CONFIRM FIRST what "retry" actually does, and prove IDEMPOTENCY — a retry must not double-pay.** The
>   certified rail already has a unique idempotency key + a ledger row-lock (#45): **orchestrate it, never
>   re-implement it.** Anything touching live Stripe stays founder-gated.
> **② WAVES 3–4 — features + de-duplication.** `D1-1` · `D8-2` · `DO9-2`/`DO9-3` · `D18-5`/`D18-6` · `D23-2` ·
>   `DO24-1` · Excel exports · the `DO25-1`/`DO23-2` §N.8f drift · hand-rolled star colours · `DO20-1` PDF page
>   numbering · delete the dead `guest/partials/trip-card.blade.php` · **unify the two operator payout maps**.
> **③ The design remainders** listed under OPEN FOLLOW-UPS, at the founder's chosen depth (⚠️ trading depth for
>   coverage is legitimate **only if DECLARED** — a shallow sweep reads like a clean bill).
>
> ## ⛔ LAUNCH GATES — **THE ACTUAL BLOCKERS, AND THEY ARE NOT FEATURES**
>
> ⚠️ **Shipping features does not move launch closer.** Feature-completeness and launch-readiness are different
> axes (§N.27.12). Per the audit (§3.11 of the ledger):
>
> **① THE DEPLOYMENT SPRINT (size L) — the real gate. ⚠️ SCOPED AND HALF-BUILT: §7.1, §7.2 and §7.3 are DONE.**
>   `docs/DEPLOYMENT.md` §7 is the scoping pass, ranked by harm. **Built:** §7.1 backups + a *proven* restore
>   (`ops/backup.sh` · `ops/restore-drill.sh`) · §7.2 scheduler/queue liveness alerting (`ops/liveness-drill.sh`)
>   · **§7.3 the deploy script (`ops/deploy.sh`, 2026-08-18)** — 12 ordered steps, refuses without a backup
>   passphrase, refuses `APP_DEBUG=true` in production, **contains no destructive command on any executable
>   line**, and carries a code-only rollback (a DB restore stays a deliberate human decision).
>   ⚠️ **The crontab is now INSTALLED AND VERIFIED BY THE DEPLOY** — `* * * * * php artisan schedule:run`,
>   without which **8 scheduled commands silently never run, including the GDPR 14-day guest deletion and the
>   30-day ID purge**. ⚠️ *Configuration checks (`crontab -l`, `schedule:list`) prove it is set up; only the
>   schedule log proves it FIRED.*
>   **⬅ REMAINING: §7.4 secrets · §7.5 Dockerfile/pinned runtime · §7.6 CI.** ⛔ The founder runs the deploy —
>   `--dry-run`, then staging, then a rollback rehearsal, then production.
> **⛔ ② THE FOUNDER'S STRIPE/VAT GO-LIVE STEPS.** The billing code *and* build are COMPLETE (§N.17–§N.19).
>   Remaining: enable Stripe Tax + register jurisdictions (⚠️ `automatic_tax` is **INERT** until then), create
>   the Prices per `docs/BILLING-STRIPE-PRICES.md`, and fill the prod env — plus the open **VAT-validation
>   fork**: **send-as-is** (implemented) vs **VIES-first** (needs a build). ⚠️ A session can only remind and
>   verify wiring — **never claim the live Prices exist**.
>
> ## HOW TO WORK — the method that produced everything above
>
> **The loop:** Claude (architect) writes ONE dense, self-contained `.md` prompt per task → the founder runs it
> in a SEPARATE Claude Code session → pastes the report back → Claude confirms and writes the next.
> **ONE prompt at a time; stop at clean, pushed boundaries.**
>
> **Every prompt carries the same spine:** orient (read this file first) → ⚠️ **CONFIRM-FIRST** (the ledger
> under- or mis-describes — and ⚠️ **it cuts both ways: three items got SMALLER**, §N.27.11) → numbered rules →
> **force the hidden branch** (state · authorization · DATA · actor) → ⚠️ **RENDER/SUBMIT to verify** (§N.21.6 —
> a fix in the file is not a fix on the page) → **reuse, don't rebuild** (§N.8f) → ⚠️ **mutation-prove every
> guard, and VERIFY THE MUTATION APPLIED** (§N.4p, §N.27.6) → read the source, never a constant → leave a guard
> behind → **don't over-reach: log adjacent findings, don't build them** → suite baseline (the documented 5–10
> band; ⚠️ **run it ALONE**, and stash from a CLEAN worktree, §N.14.5) → PHPStan L5 + Pint → commit + push with
> the trailer **`Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>`** ⚠️ (**NOT "Opus 5"** — an earlier
> commit had this wrong) → **REPORT and STOP**.
>
> **Read before starting: §N.27 (newest — the audit + waves canon) · §N.22–§N.26 (the restore/a11y tail) ·
> §N.21 (the checked capture contract) · §N.20 (the probe saga + the RETRACTION) · §N.19 · §N.18 · §N.17 ·
> §N.16 (the three ways a surface hides; keyed-not-typed) · §N.15 · §N.14 (start at §N.14.1) · §N.13 ·
> §N.8/§N.8b/§N.8c/§N.8d/§N.8f · §N.4p (the mutation discipline) · §N.9 (what is reopened).**
>
> ⚠️ **Honesty rules that outrank tidiness:** never ship UI for a capability the engine lacks (#39); when a true
> answer VARIES, route to the authoritative screen rather than pick a number (§N.15.2); **a flagged item that
> measures sound is closed AS sound** (§N.16.7); and **consolidation that flattens a real distinction is an
> honesty regression** (§N.16.5).
>

> ## 🏁 THE PER-PAGE **DESIGN-PARITY PASS** IS COMPLETE FOR BOTH BUILT REALMS (2026-08-09 → 2026-08-11)
>
> **Read §N before quoting "what remains is only a decision + infrastructure" — that line is no longer the
> whole picture.** A page-by-page pass ran against the **125 prototypes in `docs/design/raw/`**: ONE page per
> turn, make the live design match **exactly**, and **LOG — never build — every backend the prototype implies**
> to **`docs/design/MISSING-BACKEND-BY-PAGE.md`**, which drives a SEPARATE backend phase.
>
> **State: 🏁 OPERATOR 42/42 (34 gates, `0aa9ede`→`edb7cce`) + 🏁 OWNER 32/32 (`6d974d7`→`a884793`). ⬅ GUEST
> (32 prototypes) entirely UNTOUCHED.**
>
> It does **not** contradict the closed sweep — it is presentation work and preserves every §5 refusal and
> ratified divergence (**H.bis**, **#39**). But it surfaced a great deal the sweep did not, and the findings
> were rarely cosmetic: a **money-affecting calendar no keyboard could reach** (twice — O10, then O23 in a
> *duplicated* copy), **five €0.00 months drawing visible bars**, **every rating rendering as five full stars**,
> a **boolean printed as an unread count**, a **status flag gated on the wrong fact that told an owner to redo
> work they had already done**, and **six comments/names that contradicted the code**. ⚠️ **Design-matched is
> NOT a11y-closed** — §N.9 lists 6 reopened operator items; three owner items are scoped-but-unstarted.

> ## 🏁 THE WIREFRAME-PARITY SWEEP IS **FULLY CLOSED** — WAVES 1/2/3 + the auth/isolation pass (2026-08-09)
>
> A full 124-wireframe parity sweep found **5 HIGH** — a JSON-clobbering write that silently killed every
> free-cancellation window, owner attribution with **no production trigger**, an Owners page reading "Paid /
> €0.00" over **€885.60** genuinely outstanding, **two disagreeing room-revenue figures on one screen**
> (€11,560 vs €7,080), and marketing email with **no unsubscribe at all** — plus **3 structural MEDIUMs**, **23
> false-copy items**, a minor/low tail, and **3 items escalated OUT of WAVE 3 because they touched auth or
> isolation**. **Every one is now fixed, verified-already-fixed, or reclassified as a refusal — 0 unaddressed
> gaps.** One item per gate; each regression test proven to fail without its fix. Detail: **§F.4** (WAVE 1) ·
> **§F.5** (WAVE 2) · **§F.6** (WAVE 3) · **§F.7** (the closure) · the two corrections to the sweep's own
> figures in `docs/WIREFRAME-PARITY-SWEEP-2026-08-08.md` §2b.
>
> **⚠️ TWO ITEMS ARE DELIBERATELY COPY-ONLY — do NOT "finish" them without a founder call:** **#6** the rates
> guardrail now promises only the proposal path it genuinely enforces, and **#10** the real team limit is
> displayed. Neither is ENFORCED (the first is an operator/owner power decision; the second belongs with the
> billing decision **G.1**). The **§5 refusals-to-preserve** are likewise NOT gaps.
>
> **⬅ WHAT REMAINS IS NO LONGER PARITY OR QUALITY:** the ⛔ **open billing decision (G.1)** · **deployment** ·
> **scale-perf** · the founder's **go-live flips** · the **marketing site**.

> ## ✅ ALL THREE POST-AUDIT PASSES ARE COMPLETE (2026-08-08)
>
> The audit's findings have been worked to completion in three sequenced passes, each gate committed and
> pushed separately, each dual-method verified (code read **and** real browser), each shipping a regression
> test aimed at the exact blind spot that let the defect through:
>
> 1. **BLOCKERS** — the 5 HIGH findings + the cron/scheduler gap + the per-IP login throttle.
>    Headline: the **refund race** (H1), fixed FIRST because it is inert today and unrecoverable the moment
>    the Stripe live key is switched on.
> 2. **HARDENING** — security headers/CSP (0 external origins), cookie + proxy config, the **plaintext IBAN
>    flashed into the sessions table**, CSV injection, branded error views, dependency advisories, 15 Google
>    Fonts → self-hosted, seeder production guard.
> 3. **POLISH** — the technician pool (owners offered as staff), WCAG AA contrast, guest-portal
>    responsiveness, and **all 26 parity GAPS closed or honestly reclassified**
>    (`docs/design/DIVERGENCE-REGISTER.md`, `DECISIONS.md` 2026-08-08).
>
> **Four defects were worse than the audit reported**, found by verifying against the code before accepting a
> finding: the technician-pool fix needed the **server-side write guards**, not just the pickers; the owner
> booking detail wasn't a date-format bug but a **hardcoded check-out time that was simply wrong** (10:00
> where the property says 11:00); the tax-residency dropdown wasn't cosmetic but a **money defect** (a country
> name matched no configured rate → a silent ZERO withholding on a real payout); and the guest portal had
> **zero viewport breakpoints** — a 225px overflow on all 14 screens, which the audit hadn't measured.
>
> **Three reported gaps were closed by INVESTIGATION, not code** — guest email preferences and the documents
> hub already existed at a different location, and guest Help was given a real destination rather than a page
> of invented FAQ answers. Nothing was fabricated to fill a gap, and the **40 RATIFIED divergences were not
> touched** (#39).
>
> ### ⛔ ONE BLOCKER REMAINS OPEN — BY DECISION, NOT BY OVERSIGHT
> **The pricing model is unresolved.** Two contradictory definitions run simultaneously and only the flat
> tier price (€49/€99) reaches Stripe; the per-unit figure (units × €9 × VAT ≈ €44) does not. At the live-key
> flip a customer would be **billed roughly double what the app quoted them**. Both are honest code — what is
> missing is the *product decision*. Escalated to the founder and **still OPEN** — the
> recommendation to settle it at go-live is the assistant's, not a founder ruling. It is a **blocker on the Stripe
> live-key flip**, not a backlog item. See `DECISIONS.md` (2026-08-08) for both
> resolution paths and their real cost.
>
> Still outstanding beyond that: **deployment** and the **scale-perf workstream** (composite indexes, N+1 hot
> spots, pagination/caching — before the first large tenant), plus the founder's other go-live flips.


> ## ⚠️ STATUS CORRECTED 2026-08-08 — READ THIS BEFORE THE BLOCK BELOW
>
> **Vyrobel is NO LONGER "QA-COMPLETE, 0 open defects."** A full-depth 7-layer audit
> (`docs/AUDIT-COMPLETE-2026-08-07.md` — 24 agents, 446 routes, 152 pages, 183 parity verdicts) found **66
> findings: 0 CRITICAL, 5 distinct HIGH, 35 MEDIUM, 20 LOW**. The three prior QA passes were thorough and
> their verdict was honest for what they probed — but all three tested BEHAVIOUR THROUGH THE UI, and the two
> worst findings live exactly where UI testing cannot reach: a queue worker rendering outside tenant context,
> and a race window inside a service. `Mail::fake()` + `assertQueued` let a **100% booking-confirmation
> delivery failure** pass 1,617 green tests.
>
> **The 5 HIGH + the 2 launch-blocking deployment gaps are now FIXED** (commits `e4775ec`, `6c10049`,
> `f1c6513`, `209b1f3`, `8d0ad62`, `42829e7` — see the audit's §0 RESOLUTION LOG and the 2026-08-08
> `DECISIONS.md` entry). Suite **1640 / 1633 passed / 11,238 assertions**; PHPStan L5 (0); Pint clean;
> `migrate:fresh --seed` clean and now yielding **0 failed jobs (was 36)**.
>
> **✅ THE HARDENING SPRINT IS ALSO DONE (2026-08-08).** Every "should-fix before launch" item is closed:
> security headers on all five realms (CSP built from a real asset inventory, no external origin permitted) ·
> secure cookie + TrustProxies + HTTPS forcing (all `config:cache`-safe) · the owner IBAN no longer flashed
> into `sessions` · `CsvSafe` on **49/49** `fputcsv` calls · branded error views · rotating logs · **0**
> dependency advisories · npm lockfile · seeders refuse production · self-hosted fonts. See the audit's §8
> table and the 2026-08-08 `DECISIONS.md` entry.
>
> **STILL OPEN — production-readiness is CONDITIONAL, not achieved:**
> - **POST-LAUNCH / scale (unchanged):** 210 queries on `/app/insights` and **116 on the PUBLIC unauthenticated
>   storefront** on 40 reservations; missing composite indexes measured at **73×–200× at 500k**; **zero
>   pagination and zero caching app-wide**; 26 prototype-parity gaps (9 MEDIUM); WCAG AA contrast failures on
>   two shared tokens; the guest portal has **zero viewport breakpoints**.
> - **DEPLOYMENT is a real sprint, not a flip:** there is no Dockerfile, CI workflow, deploy script or backup
>   strategy in the repo. `docs/DEPLOYMENT.md` (new) carries the runbook, including the ONE cron entry
>   without which the GDPR deletion and ID purge never run.
>
> ⚠️ **TECH-DEBT CORRECTION:** `OwnerUnitDetailViewTest` fails **11/12 in isolation on every run** — a REAL
> deterministic defect, not the "random ULID collision flake" recorded below. `BookingImportTest` genuinely
> is an ordering flake (11/11 isolated). The "7 documented flakes" framing was half wrong.
>
> ⚠️ **SEQUENCING:** fix H1 (the refund race) **before** the Stripe live-key flip — it was the one finding
> that converts from harmless to unrecoverable at that moment. *(Done: `e4775ec`.)*
>
> The block below is the pre-audit record. It remains accurate about what was BUILT; treat its
> "QA-COMPLETE / 0 defects" and "only flips + deployment remain" claims as superseded by this header.

---

## (A) IDENTITY + CURRENT STATUS — finish-to-100 COMPLETE · AUDITED · blockers + hardening + polish FIXED · 🏁 the WIREFRAME-PARITY SWEEP FULLY CLOSED (2026-08-09)

**Vyrobel is a multi-tenant, AI-native hotel / apartment / short-stay property-management SaaS** (PMS + channel
manager + booking engine + operator/owner/guest apps + owner accounting + AI seams), for operators running a MIX
of hotels, aparthotels, serviced apartments, villas and multi-unit rentals. **Three tenant realms — Operator
(`/app`) · Owner (`/owner`) · Guest (`/guest`) — plus a platform Super-Admin console (`/admin`).**

⚠️ **DO NOT REPEAT THE OLD CLAIM "QA-COMPLETE, 0 OPEN DEFECTS."** It was honest for what the three QA passes
probed and is now superseded. The **CURRENT, CORRECTED status is:**

> **Finish-to-100 COMPLETE across all realms → independently AUDITED (7-layer, A/B dual-method × prototype
> parity) → ALL launch-blockers, ALL hardening and ALL polish RESOLVED → 🏁 the 124-wireframe PARITY SWEEP
> FULLY CLOSED (WAVES 1/2/3 + the auth/isolation pass; 0 unaddressed gaps).** What remains is **(1) ONE OPEN
> BILLING DECISION — the founder's business call, and a blocker on the Stripe live-key flip · (2) the
> DEPLOYMENT sprint · (3) the SCALE-PERF workstream · (4) the founder's go-live flips · (5) the marketing site.**

⚠️ **AND DO NOT REPEAT "PARITY WORK REMAINS" EITHER.** The entire quality backlog — money, security, privacy,
isolation, parity, honesty-in-copy, UX and the minor gaps — is addressed and verified. **What is left is not
quality work:** billing is a *decision*, deployment is *infrastructure*, scale-perf is a *pre-large-tenant*
workstream, and the flips are the *founder's*.

⚠️ **ONE QUALIFIER, ADDED 2026-08-09:** a **DESIGN-PARITY PASS is now running on top of all of the above (§N)** —
per-page, presentation-only, one page per turn. It is **not** a reopening of the parity sweep (that closed, and
this pass preserves its refusals); it is a finer-grained design-fidelity track whose second output is a **logged
backend backlog** (`docs/design/MISSING-BACKEND-BY-PAGE.md`) for a later phase. **🏁 Operator 42/42 COMPLETE;
owner (32) + guest (32) untouched.** So "the remainder is only a decision + infrastructure" is true of *quality*,
and incomplete as a description of *what is being worked*.

The audit is why the status changed: all three QA passes tested behaviour **through the UI**, and the two worst
findings live exactly where UI testing cannot reach — **a queue worker rendering outside tenant context** (100%
of booking-confirmation emails failed permanently; hidden because `Mail::fake()` + `assertQueued` asserts a job
was *enqueued*, never that it *renders*) and **a race window inside a service** (the #27 refund bound had no row
lock). Both are fixed. **Section map: A identity+status · B stack · C realms+surfaces · D engines · E invariants ·
F the COMPLETE history (finish-to-100 + 3 QA passes + the audit + the 3 remediation passes) · G ⚠️ OPEN DECISIONS ·
H honest deferrals · I the roadmap · J tech-debt · K marketing site · L process lessons · M founder pattern ·
⬅ N THE DESIGN-PARITY PASS (the ACTIVE track — method, progress, the next page, its own lessons).**
The dated per-gate build log with hashes is the `## Status` section further below; the "why" is
`docs/DECISIONS.md`; per-page fidelity is `docs/design/DIVERGENCE-REGISTER.md`; the full audit + its resolution
log is `docs/AUDIT-COMPLETE-2026-08-07.md`; earlier per-page audits are `docs/FULL-AUDIT-OPERATOR-GUEST.md`; the
three QA reports are `docs/QA-FULL-REPORT-2026-08-04.md`, `docs/QA-DEEP-REPORT-2026-08-04.md`,
`docs/QA-THIRD-REPORT-2026-08-04.md`; the deploy runbook is `docs/DEPLOYMENT.md`.

## (B) Stack + architecture (incl. the POST-HARDENING security posture)
- **Laravel 13 · PHP 8.3+ · MySQL 8/InnoDB (genuine MySQL, NOT MariaDB) · Livewire 4 + Blade + Tailwind 4 (Vite) · Alpine (bundled by Livewire) · Redis + Horizon queues · Pest 4 / PHPUnit 12 · PHPStan L5 · Laravel Pint.** Money = integer minor units + ISO-4217 (`Money` cast; NEVER floats). PKs = ULIDs on tenant tables. Time = store UTC, compute guest-facing "today" in the property timezone; stays are half-open `[check_in, check_out)`.
- **Multi-tenancy:** shared-DB, `organization_id` on every business table via the `BelongsToOrganization` trait (global scope + auto-fill + a guard that THROWS on an unscoped tenant query outside super-admin). **RBAC:** `spatie/laravel-permission` teams-mode (team = organization).
- **Design system (warm-glass `--vy-*`):** Sora (display) + Inter (sans); warm cream/terracotta palette + the `#E8703A→#C2410C` orange gradient; glass blur + hairlines + `--vy-r-*` radii + `--vy-shadow*`; motion primitives `vyFadeUp`/`vyDrift`/`vyShimmer` (honour `prefers-reduced-motion`).
- **Three shells (all real):** the **parameterized shared OPERATOR shell** (`components/layouts/operator.blade.php` — the GATE-0 decision was to parameterize it, NOT fork 42 per-page chromes; carries the property switcher **#38**, read-only mode **#84**, role-gated nav **#104**, tenant scope); the **owner shell**; the **760px GUEST shell** (`x-guest.shell`, peach/warm-glass) — a SEPARATE guest guard (refused from `/app` + `/owner`), every page guest-isolated (foreign reservation/guest/thread → **404**). **⚠️ The GUEST realm has NO Alpine** → all interactivity is native GET / progressive enhancement (tabs, search, filters, month pickers), never client JS state.

- **⚠️ THE MIDDLEWARE FACT THAT SHAPES EVERY SCOPED READ (invariant #20) — memorise this before touching any list, filter or pager.** `SetTenantForWebRequest` is registered **GLOBALLY**, but `SetCurrentPropertyForWebRequest` is mounted on the **`/app` ROUTE GROUP** — so it does **NOT** run on `/livewire/update`. A Livewire wire-action therefore inherits its property scope from a component prop rather than re-resolving it. **Consequence: every scope-dependent read rides a GET link, never a wire-action** — which is why the list FILTERS already worked that way, and why **PAGINATION was built the same way** rather than with `WithPagination`. A page change is an ordinary request that re-runs every middleware, so page 2 is freshly tenant- *and* property-scoped. (The same fact is why Livewire components hold a **string id** + `#[Locked]` and `findOrFail` in `render()`, never a scoped model as a public prop.)
- **Shared `<x-pager>` (2026-08-09)** — the ONE realm-neutral GET-link pager (`resources/views/components/pager.blade.php`), used by operator Guests, operator Reservations and the Notifications feed in **both** realms. ⚠️ It carries **self-contained inline styles on purpose**: two attempts to add its CSS to the owner layout 500'd the page (once from an insertion inside a Blade conditional, once because a Blade directive name written **inside a CSS comment** still compiles). Shared `<x-vy-scrollbar />` likewise carries the ONE scrollbar theme (page + pane, resolved from the `--vy-*` tokens so it cannot drift from the palette; `scroll-behavior: smooth` yields to `prefers-reduced-motion`), included once per web surface — PDFs and emails deliberately excluded.
- **⚠️ POST-HARDENING SECURITY POSTURE (2026-08-08, `6c390ad`→`505627a`):** global **security response headers on EVERY response in all five realms — including unmatched-route 404s** (`SecurityHeaders` is registered GLOBALLY, not on the `web` group, because group middleware only runs once a route has MATCHED): CSP · HSTS (only when the request is genuinely secure) · X-Frame-Options · X-Content-Type-Options · Referrer-Policy. **The CSP permits ZERO external origins** — all fonts are self-hosted (`public/fonts/`), no CDN, no Google Fonts. Four documented relaxations only (style/script `unsafe-inline`, `unsafe-eval` for Alpine, `frame-ancestors 'self'` for the same-origin email-preview iframe, `img-src data: blob:`). Plus: `SESSION_SECURE_COOKIE` + `TrustProxies` + HTTPS scheme forcing driven from **`config/security.php`, NOT `env()`** (⚠️ `env()` returns NULL under `config:cache`, which the deploy runbook runs); sensitive fields (`iban`, `account_holder`, `tax_id`, secrets, 2FA codes) globally excluded from validation-error flash so an **IBAN can never be written in plaintext to the `sessions` table**; `CsvSafe` on **all 49** `fputcsv` calls; branded, self-contained, realm-aware error views that **never print the exception**; rotating logs; production-guarded seeders; 0 dependency advisories.
- **⚠️ DESIGN-TOKEN + SHELL CHANGES FROM THE POLISH PASS (2026-08-08):** `--vy-muted` and `--vy-faint` were **DARKENED to meet WCAG AA** (`4f27c81` — muted 3.77→5.66, faint 2.68→4.60, computed against the TRUE worst-case background: the card's `rgba(255,252,248,.58)` composited over all four gradient stops → `rgb(249,223,207)`), across 13 files. The **760px guest shell is now RESPONSIVE** (`c5babfc`) — it previously had **ZERO viewport breakpoints** (its only `@media` was `prefers-reduced-motion`), so the header forced a 615px document at 390px: a **225px horizontal overflow on all 14 guest screens**. It now carries a `max-width: 640px` breakpoint (nav scrolls inside itself, 44px tap targets) plus an `html, body { overflow-x: hidden }` guard; **0px overflow on all 14 pages**, reduced-motion intact.

## (C) The three realms + their surfaces (all at genuine 100%)
*(⚠️ The per-realm counts below are APPROXIMATE and drift as surfaces are added — the audit enumerated **446
routes / 152 pages**. Enumerate from the route list when an exact number matters; the surface NAMES are the
reliable part.)*
- **OPERATOR (`/app`, ~43 surfaces):** Dashboard · Calendar/Rack · Front Desk · Inbox · New Reservation · Group Booking · Maintenance (board / costs / settings) · Work Order · Housekeeping · Team Schedule · Reports (+ PDF) · Owners / Owner-Detail / Payout-Run · Notification Preferences · Email Templates (+ editor) · Channels · Billing (+ checkout/paywall) · Insights · Guests / Guest-Profile · Rates · Refund · Reservations / Detail · Reviews · Roles · Security · Search · Profile · Portfolio · Settings (one combined `/app/settings` hub) · Units · Unit-Requests · Notifications feed · the PDFs. **13 gates + FINAL, HEAD `d8e5255`.**
- **GUEST (`/guest` + `/book`/`/stay`):** Browse/storefront · Booking · Room · Checkout · Account · Check-in · Arrival Info (+ PDF) · Add-ons · Trips · Reservation · Manage · Payments · Wallet/Credits · Messages · Saved (Wishlist+Waitlist) · Review · Receipt · the 9 transactional emails · the guest auth (blind-index login, booking-tied claim, reset). **Phase-4 pixel-diff G0–G4 + G-FINAL, HEAD `9efeb99`.**
- **OWNER (`/owner`, completed earlier):** Overview · My Units / Unit Detail · Bookings (+ PDF) · Statement (+ PDF) · Payouts / Payout Method / Balance · Expenses · Block Calendar · Documents · Reports / Insights · Tax Center · Settings hub · Security · Team · owner sub-tenancy + staff rostering.
- **PLATFORM SUPER-ADMIN (`/admin`, the dark console — a SEPARATE `admin` guard, 2FA-MANDATORY):** Overview (platform KPIs) · Customers (suspend/reactivate/assign-plan) · Plans (edit price + feature toggles that gate tenant `planAllows`) · Security & activity · Integrations (credentials encrypted at rest, write-only). First admin sign-in is FORCED through 2FA enrollment; created out-of-band via `admin:create`, never a public route.

## (D) The certified engines (the ANTI-DRIFT core — reused everywhere, all real + tested; NEVER reimplement per-caller)
`AssignmentService` (single inventory write path — createBooking/assignUnit/reschedule under `SELECT…FOR UPDATE`; the no-double-booking guarantee; the `Unit::isBookable()` OOS/paused guard on create/move/reschedule) · `UnitAvailabilityService`/`AvailabilityService` · `FolioService`/`FolioTotals` (money #17) · `OwnerRateService`/`RateAndRestrictionService` (rates, weekend-uplift) · the **payout engine #71** (test-mode, Connect rail, double-transfer-guarded) · the **#83 Mailables** (branded, preference-gated) · `HousekeepingService`/`HousekeepingTask` · `MaintenanceService` · **#66 security** (trusted devices / login-approval / passkeys) · `InboxService` (unified inbox) · `StaffRoster`/`ShiftRosterService`/`RosterWeek` · `ArrivalPack::for` (**#33** door code) · `ReviewRating` (**#76**) · `GuestWalletService` (append-only credit ledger) · `CancellationPolicy` · `GuestDeletionService` (14-day archive-not-destroy) · `GuestIdDocumentService` (encrypted ID, 30-day purge) · `PdfService` · `CsvSafe` (CSV-injection-safe exports) · `ReportPeriod`/`OperatorReportData` · the shared data helpers `GuestTrip`/`GuestReservationView`/`GuestPayments`/`GuestHistory`/`PerformanceMetrics`/`OwnerLedgerAttribution`/`OwnerStatementBreakdown` (ONE definition per concept — two `match`es WILL drift).

- **⚠️ THE OWNER-REALM ENGINE SET (confirmed by the owner design pass, OWNER 1–11 — reuse, never rebuild):**
  `OwnerBlockService` (owner availability blocks — **real writes that take inventory off-sale**, non-overlap
  enforced, routed through the certified `AssignmentService`) · `OwnerStatementBreakdown` + the real
  `owner_statement_lines` (**the** owner-statement source; the screen and the PDF share it, so SCREEN == PDF
  cannot drift) · `OwnerOccupancy` (⚠️ keyed by **PHYSICAL unit id** — *not* the room-type-wide
  `AvailabilityService`, which would fold in units this owner does not own; a test locks this) ·
  `OwnerLedgerAttribution` · `OwnerUnitAnalytics` · `OwnerCommissionRate` · `OwnerPayoutService::pay` (**#71**
  test-mode) · `OwnerUnitRequestService` (⚠️ files a `unit_requests` row and **creates NO unit** — the
  operator approves and creates it as their own action) · `Iban::masked` (takes only `iban_last4`, so a raw
  IBAN is **structurally un-emittable**) · `NotificationFeed` (self-scoped **by its query root**) ·
  `<x-charts.bars>` (the honest-zero rule — ⚠️ the `max(2, $pct)` min-bar floor recurred on **four** owner
  pages; the fix belongs on the shared component, §N.9 #4) · `PdfService` (⚠️ **NEVER touched from a page
  gate** — the render pipeline and the 2.70 watermark belong to the realm-wide PDF sweep).
  - ⚠️ **The #6 rate split, as verified at OWNER-4 (the brief had it inverted):** `RateProposalService` is the
    **OPERATOR** path — the guardrail constrains what a manager may propose. The **OWNER** approves, declines,
    counters, sets the guardrail, and prices exclusively-owned room types by **direct apply**
    (`owner.rates.apply` → `OwnerRateService`). A room type shared with other inventory is honestly read-only.
    ⚠️ There are **0 `RateGuardrail` rows** in the demo DB, so only the unguarded case is live — and that is
    truthful, not a gap.
  - ⚠️ **`SettingsService`'s JSON `array_merge` is NOT universal** — several settings stores are **typed
    columns**, not a JSON blob: `maintenance_settings`, and notification preferences in **both** realms (a
    typed `(user, event, channel)` pivot via `updateOrCreate`). **Verify per surface before assuming a merge
    hazard exists.**

- **⚠️ CHANGES FROM THE REMEDIATION PASSES (2026-08-08) — a future session must not reintroduce the old shapes:** `FolioService::refundPayment()` now runs inside a **transaction + `lockForUpdate` on the original payment + a deterministic idempotency key**, mirroring `OwnerPayoutService::pay()` (which remains **the reference implementation** for any new money path). `FolioTotals::for()` now ALSO returns **`received_minor` + `refunded_minor`** — the two halves it always computed and discarded — so the guest statement's "Credited" tile cannot become a second derivation of refunds (the three original keys are byte-identical). The **#83 booking-confirmation Mailable is dispatched as a tenant-restoring queued job** (scalars-only payload, recipient resolved in-context so PII stays out of the payload). The **technician-assignment pool is STAFF-ONLY** via `StaffRoster::staffUserIds()`/`assignableStaff()` — **including the three server-side write guards**, not just the pickers. `Guest::anonymize()` covers three further stores (`reservations.notes_internal`/`expected_arrival`, `reviews.operator_response`, `GuestChangeRequest.payload`, converted `BookingQuote` fields). `WithholdingService`/`OwnerVatService` look a rate up by **`owners.tax_country` as an ISO CODE** — the residency UI now emits codes and normalises legacy names on save (`Countries`). **NEW shared definitions:** `OperatorLetterhead` (the ONE operator registered-identity block — 5 hand-rolled copies existed and had already drifted), `Countries` (the ISO ⇄ name contract), `StayTime` (property check-in/out display — replaced hardcoded, partly FALSE times).

- **⚠️ CHANGES FROM THE PARITY SWEEP (2026-08-08/09) — likewise do not reintroduce the old shapes:**
  - **`GuestWalletService` is now concurrency-safe** — transaction + `lockWallet()` row lock + a re-read of the bounds INSIDE the transaction + deterministic idempotency keys + a promo-row lock + a re-reversal bound. It was check-then-act with the UNIQUE `idempotency_key` column unused, so a raced promo redemption returned **success twice**. It now mirrors `OwnerPayoutService::pay()` — **still the reference for any new money path**.
  - **`CancellationPolicy` survives a settings save** — Stay Policies used `fill()` on a JSON-cast column, replacing `policies.cancellation` wholesale and deleting `free_until_days`, so `isRefundable()` was false for every reservation at that property. Now a JSON **merge**, with `free_until_days` genuinely settable.
  - **Owner revenue attribution has a PRODUCTION TRIGGER** (`OwnerLedgerAttribution`) — its only callers were the seeder and tests, so bookings raised occupancy/ADR while owner earnings, statements and payouts stayed at zero. ⚠️ It fires **after the folio is posted**, never on `reservation.confirmed`: attribution reads folio revenue that `createBooking()` has not yet written, so the obvious wiring would have banked €0 and latched `liveVersion` permanently.
  - **ONE revenue source** — `OperatorReportData`'s panel and its Top-units table both read the folio (they were `reservation_units.price_minor` vs folio: **€11,560 vs €7,080**, inherited by the PDF). ⚠️ The **Portfolio-vs-Reports** difference is NOT the same defect and is deliberately left alone: `PerformanceMetrics` pro-rates `price_minor` by in-range nights, a folio has no per-night split, and the ADR/RevPAR identities depend on that pro-rating.
  - **`MarketingUnsubscribe`** (new) — the ONE place the signed opt-out link is minted (`url()`, `oneClickUrl()`, `mailHeaders()`), backing a real logged-out unsubscribe route. ⚠️ `mailHeaders(null)` returns an EMPTY `Headers`, so transactional mail never advertises an unsubscribe; the controller renders on GET and performs on POST (prefetch-safe), reads cross-tenant via `runAsSuperAdmin`, writes via `forOrganization`, and renders **no PII**.
  - **`MaintenanceCostService::report()` REQUIRES the property read-set** (`report(array $propertyIds, ?string $from, ?string $to)`) — it was tenant-scoped only, so a pivot-restricted user saw the costs and **unit names** of properties they may not work in. The scope MIRRORS the board's `whereHas('unit', property_id ∈ readIds)` rather than inventing a second mechanism, and making the argument required means **PHPStan fails any caller that forgets it** — screen and export cannot disagree (#17/#38). Period reading is the ONE `MaintenancePeriod` helper, so the export links always cover the window on screen.
  - **`WorkspaceSwitchController`** (new) — `active_organization_id` was **read and never written**, pinning a multi-org user to `first()` forever. It mirrors `PropertySwitchController` exactly, validates the target against the user's OWN active memberships, and clears `current_property_id` so no foreign property id survives the move. The picker lists only workspaces the user can actually ENTER (#30 — "gate the control too"; a Membership is not operator access).
  - **`User::changePasswordTo()` terminates sibling sessions** via `ActiveSessions::terminateFor()` — a reset rotated the hash and remember-token but left live server-side sessions alive, so an attacker holding one was unaffected by the victim resetting a compromised password. It lives in the ONE path both the reset flow and the in-app change already call, and `OwnerSecurityController` points at the same helper, so "sign out others" and "reset kills siblings" cannot diverge.
  - **`OwnerCommissionRate` is the ONE formatter** — the rate was written **three ways** across four call sites, two of them on the SAME page (`18.00%` beside `18%`, because `commission_pct` is `decimal:2`). Also: `FolioTotals::refunded_minor` is now surfaced on the guest Payments overview; the booking timeline consults `AssignmentService::TERMINAL_STATUSES` (#29's list) so a checked-out stay no longer advertises an upcoming check-in; `HousekeepingService`'s "Suggested" housekeeper is **computed as least-loaded** (it badged `$i === 0` while printing that person's real, contradicting load) without re-sorting the crew list the filter chips depend on; and `NotificationFeed::forUser()` filters in SQL (`data->category`, `read_at`) and returns **both** a plain list of mapped rows AND a paginator of models — the shape that satisfies `LengthAwarePaginator`'s **invariant** `TValue` without a cast, a widened type, or a PHPStan baseline entry.


### D.guest — the engines the GUEST walk + the PDF sweep certified (2026-08-12 · reuse, NEVER rebuild)

- **`ResolvesGuest::guest()` + `$this->guest()->reservations()->findOrFail()`** — the realm's isolation. Scope
  by RELATION (`lead_guest_id`), never route-model binding; a foreign id 404s. Proven on every surface.
- **`ArrivalPack::for()`** — the `#33` window gate. **Double-gated** (the data nulls the secrets; both views
  re-gate on `$pack['released']`) **and now carrying a DATA-CONTRACT guard** so each layer fails alone
  (§N.13.3). **`ArrivalPackDocument` DELEGATES to it** — and its docblock saying so is accurate (verified).
- **`GuestChangeRequest`** — the operator-confirmed request state machine (no guest-side fait accompli).
- **`GuestIdDocumentService` + `Guest::anonymize()`** — GDPR erase. **COVERED *and* TRIGGERED**: the
  guest-facing delete stamps `scheduled_deletion_at` (+14d), the daily `guests:process-deletions` command runs
  `processDue()` → `anonymize()`. ⚠️ **Delete = ANONYMIZE-IN-PLACE, never a row destroy** ⚠️ **and the chain
  depends on the `schedule:run` cron** (`DO-G9-1`).
- **`FolioService` / `FolioTotals`** — `#17`, integer minor units, ONE source; no Blade re-derivation.
- **`GuestWalletService`** — the append-only credit ledger; the balance is **DERIVED, never stored**.
- **`HelpKnowledgeBase`** — the ONE curated help content store, serving TWO audiences from one class:
  `articles()` (owner — read by the owner Help blade AND the grounded `AskVyrobelService` via `HelpRetriever`)
  and **`guestArticles()`** (guest — read by `/guest/help`, added 2026-08-12, **not forked**). Same contract
  both sides (+ a `category` on the guest set); guest entries link only `guest.*`, owner only `owner.*`.
  ⚠️ Every answer is verified against the code that implements it, and a value that VARIES (the per-property
  cancellation window) is **routed to its authoritative screen, never averaged into a number** (§N.15.2).
- **`PropertySupport`** (2026-08-12) — the ONE derivation of a property's guest-facing support contact, read by
  BOTH the operator settings form that sets it and the guest Help block that shows it, so the sentence an
  operator picks is the sentence a guest reads. ⚠️ **Honest absence is its RETURN TYPE**: `for()` yields **null**
  when nothing is populated (no card at all), and null per key otherwise — no placeholder exists to render.
  ⚠️ **The reply norm is a NORM by construction** — the column stores a KEY into `REPLY_NORMS`, whose phrases all
  begin *"usually"* and live in code, so a guarantee is not expressible and is refused by validation (§N.8d /
  `DO-EMAIL-9` closed structurally, not by helptext). ⚠️ Nothing is measured; no default is ever seeded.
  ⚠️ Phone renders as `tel:` **only** — WhatsApp is unverified and therefore not drawn (`DO-SUP-1`).
- **`<x-star-rating>`** — floor-don't-round, glyph-differentiated (greyscale-safe), one accessible name,
  **defaults `#8F5A06` / `#7A6E64`** (both ≥3:1 as a non-text graphic on every plate the product renders them
  on, incl. the peach `#FBD3BF`). ✅ **`DO10-4` CLOSED (`ee6be74`)** — every rating row in the product now
  renders through it or its email-safe equivalent; the `owner/unit-detail:338` remnant is migrated.
  ⚠️ **Its defaults were `#E8A63A` / `#B9A99A` and FAILED 3:1 (2.08 / 2.25) until the capstone measured them
  (§N.14.2) — certified for BEHAVIOUR was never the same claim as its rendered defaults passing.**
- **`OrganizationScope` + `ResolvePublicOrganization` / `ResolvePublicProperty`** — the public storefront's
  tenant scope, resolved from the ROUTE SLUG into **real tenant context** (never a guard bypass). A suspended
  or cancelled org's storefront **404s**; an **inactive property** is off-sale on both storefronts, now
  mutation-proven on both layers.
- **the signed claim link** — `temporarySignedRoute(…, now()->addHour())` + `hash_equals(sha1(email))` inside
  the signed payload + still-unclaimed + has-a-booking. **Its one-hour LIFETIME is now guarded**, not merely
  true, and the page's "expires in an hour" is bound to it.
- **`app/Services/PdfService.php`** ⚠️ — **NOT `app/Support/Pdf/`** (that holds the twelve `*Document`
  data-builders). Renders any of the **17** templates; the export-audit stamp is now `#5f5853` (6.98).
  ⚠️ Its plate is still non-deterministic (`position:fixed`) — `DO-PDF-2`.
- **`ExportAudit::stamp()`** — records the download AND returns the watermark line, so the log and the file
  can never disagree.

## (E) The load-bearing INVARIANTS (held across ALL THREE realms — proven under the audit's crafted attacks)
- **#17 — money reconciles to the cent** (folio/receipt/payments/statements; integer minor units; never sum across currencies).
- **#28 — privacy perimeter:** operator sees full guest contact ONLY on the Guests CRM (front-desk/inbox name-only); OWNER is name-only everywhere; guest ID docs owner-NEVER + encrypted; every free-text store ABOUT a guest is added to `anonymize()` in the same change (incl. PII in JSON columns).
- **#42 — OWNER ISOLATION (sharpened by the owner design pass, OWNER 1–11):** an owner sees ONLY their own
  properties, units, figures, statements and documents. ⚠️ **A per-unit / per-statement surface keyed on a URL
  id is the classic isolation surface** — a foreign id must **404**, including an **operator-owned unit inside
  the SAME organization** (measured at OWNER-10: own units 200 · same-org operator-owned 404 · another
  property's unit 404 · nonsense 404). It is **structural**: the models carry `BelongsToOwner`, the id is a
  plain string prop resolved by `findOrFail` under the active scope, never route-model-bound. ⚠️ A downloadable
  **PDF is the worst-case leak** (a whole statement as a file) — cross-owner 404 there is mutation-proven
  (OWNER-11). ⚠️ Within the owner realm #28 also means: an owner sees **their own account**, but **never guest
  contact** (name-only at most) and **never a raw IBAN** (masked, structurally).
- **#33 — door code is guest-only + window-gated:** shown to the guest for THEIR stay within the release window (48h); owner NEVER; withheld before the window (proven).
- **#39 — NO fabrication:** reproduce the design with REAL data, an honest empty state, or an honest "Soon" — NEVER faked figures/history/reviews/sync/positions/cards.
- **#20 — the property middleware does NOT run on `/livewire/update`** (it is on the `/app` route group; the tenant middleware is global). **Therefore every scope-dependent read — filters AND pagination — rides a GET link**, so each page load is freshly tenant + property scoped. See **B**.
- **#38 — tenant + property isolation:** a foreign id answers **404**, never 403 (a distinguishable 403 would leak that another org's record exists). Property access is the `property_user` pivot read through `PropertyContext` — ⚠️ **an EMPTY pivot deliberately means FULL-ORG access** (documented, preserved); a restricted user must see no other property's data on screen **or in any export**. Working-context switches (property, workspace) grant nothing the membership doesn't.
- **#27 refund bounded** (cumulative ≤ original, same-currency, guard before the gateway). **#29 re-termination bounded** (a terminal reservation can't be re-terminated). **#30 permission-escalation closed** (route-gated `can:`, gate the role-granting route too, and **gate the CONTROL too** so the UI never invites a 403). **Archive-not-destroy** (guest 14-day undo + anonymize; owner/property scheduled, financials retained). **Emails genuinely DELIVER**; **marketing email always carries a working opt-out** (signed, logged-out, `List-Unsubscribe`) while transactional mail carries none.

- **⚠️ INVARIANTS STRENGTHENED BY THE 2026-08-08 PASSES:** **#27 is now CONCURRENCY-SAFE** (transaction + `lockForUpdate` + idempotency key — the bound held logically but had no row lock, so two simultaneous refunds could each see the same "already refunded" total). **#28 expanded twice** — `anonymize()` now covers three further free-text stores, and the **technician pool excludes owner-realm users at the WRITE GUARD, not just the picker** (a Membership is NOT operator access; External Owners hold one because it is how their org resolves). **#30 closed on the Guests CRM + 21 writes** (`can:` gates; nav controls gated too, so the UI no longer invites a 403). **Booking-confirmation emails now genuinely DELIVER** (previously 100% failed on the worker). **#39 held throughout the polish pass** — the 40 RATIFIED divergences were NOT touched, and three reported "gaps" were closed by investigation rather than by inventing content.

- **⚠️ INVARIANTS STRENGTHENED BY THE PARITY SWEEP (2026-08-08/09):** **#17 now holds under CONCURRENCY on the wallet too** (lock + idempotency, the refund/payout pattern), and revenue has ONE source per concept so two figures on a screen cannot disagree. **#38 is now enforced on Maintenance Costs** — screen AND export take the identical property scope, adversarially proven with a real `property_user` row. **#30** gained the workspace picker (only enterable workspaces offered) and the session-termination path. **#39 moved from "no fabricated DATA" to "no fabricated CLAIMS"** — the sweep's verdict was that *the money reconciles and the privacy perimeter holds; what fails is COPY THAT ASSERTS BEHAVIOUR THE CODE DOESN'T IMPLEMENT*, and ~23 such surfaces were made honest. Charts now **scale to real data** rather than to a goal (a €6,000 target rendered an €885.60 best month at 7–15%).

## (F) The COMPLETE history — finish-to-100 · 3 QA passes · the 7-layer AUDIT · the 3 remediation passes
The finished product was verified by **three independent QA passes**; every prior finding is RESOLVED and re-confirmed live. Full reports: `docs/QA-FULL-REPORT-2026-08-04.md`, `docs/QA-DEEP-REPORT-2026-08-04.md`, `docs/QA-THIRD-REPORT-2026-08-04.md`. Memories: [[project-qa-fix-pass]], [[project-qa-third-pass]].
- **Pass 1 — broad E2E (`QA-FULL-REPORT`):** every page across all realms driven in a real browser (Playwright MCP). Found **7**: **F-GUEST-1 (HIGH** — storefront "pay on arrival" fabricated a paid folio), **F-OP-1 (MED** — Front Desk/Calendar defaulted to the furthest-future booking, not today), **F-OP-2/F-OP-3/F-OP-5 (LOW)**, **F-OP-4/F-DOC-1 (TRIVIAL)**.
- **The fix pass (commits `1e564df` → `e492537` → `9b8828d` → `1f47ee3` → `8b06081`):** all 7 fixed, one per gate, each browser-verified + test-covered. F-GUEST-1 removed the fabricated `recordPayment('offline')` so pay-on-arrival leaves the balance DUE (reconciles #17) **and corrected the two tests that had ENCODED the bug**. F-OP-1 = shared `App\Support\Schedule\BoardDate` today-default. F-DOC-1 = docs corrected to the `/book/seaside-demo` property slug (org slug `vyrobel-demo` 404s by design).
- **Pass 2 — deep coverage (`QA-DEEP-REPORT`):** 8 critical mutations driven with DB re-reads. **0 new genuine defects**; **2 false alarms correctly investigated-and-disproven** (the "Check in" button `undefined` action = Alpine binds the id on modal-open; the dashboard "not re-scoping" = a regex matched the switcher's dropdown text). Found **D2-OBS-1 (LOW** — on-desk collect stored the real cash/bank choice only in `metadata` while the queryable column read a generic `"manual"`) → fixed **`b2e1d7b`** (distinct nullable `payments.method` column + backfill, surfaced via `Payment::methodLabel()`; money/#17/idempotency untouched).
- **Pass 3 — final mutating-ops + motion (`QA-THIRD-REPORT`):** the remaining ~22 mutating operations driven through the real controller routes to **persistence + #17 reconciliation + isolation + bounded-guard** checks (calendar reschedule · maintenance OOS→return-to-sale→owner-cost→CSV-injection-safe · schedule move/publish/leave-conflict · inbox send/note/link/**real deferred-retry** · group-booking 5-step atomic · rates→folio + consent gate · owner #68 expense-approval net-drop + re-approve idempotent + IBAN encrypted · super-admin plan-gate/suspend/encrypted-integration), **plus a real Playwright VIDEO/TRACE motion pass** (7 `.webm` + 7 traces + 28 frames in `.playwright-mcp/qa3/`; `vyFadeUp` staggers, count-ups 0→value settle, **prefers-reduced-motion honoured on video**, modals centered / popovers anchored — no `x-show` mis-center). **0 new defects; 1 candidate (a stale `/book/vyrobel-demo` demo URL) correctly investigated-and-retracted against the live file** (CLAUDE.md already reads `/book/seaside-demo`; the 404 came from a stale system-reminder snapshot).
- **Net:** 3 passes → **0 open QA defects**; every prior finding resolved + re-confirmed. Money reconciles to the cent (#17) on every path; every bounded guard held (#27/#29/#30/#68, no-double-booking, no-double-charge); every privacy rule held (#28/#33 + encryption at rest + full `anonymize()` coverage); every honest deferral stayed honest (deferred sends fail visibly, payout engine test-mode, no fabricated figures).


### F.2 — THE COMPLETE 7-LAYER AUDIT (2026-08-07 · `docs/AUDIT-COMPLETE-2026-08-07.md`)
An independent, READ-ONLY, full-depth audit run on **A/B dual-method** (every material claim verified BOTH by
driving the real browser/DB AND by reading the code — **a disagreement between the two IS a finding**) ×
**prototype-vs-built parity** (every page scored FAITHFUL / RATIFIED-DIVERGENCE / GAP / NO-WIREFRAME).
**446 routes enumerated · 152 pages captured · 183 parity verdicts.**

**Result: 66 findings — 0 CRITICAL · 7 raised → 5 distinct HIGH · 35 MEDIUM · 20 LOW · 3 TRIVIAL.**
**Parity: 66 FAITHFUL · 40 RATIFIED-DIVERGENCE · 26 GAP · 51 NO-WIREFRAME.**

Two calibrations materially shaped that result and must be preserved by any future audit:
- **A #39 refusal is CORRECT, never a GAP.** The prototypes are populated with invented data; the product
  deliberately ships real data or an honest empty/"Soon". Scoring that naively would have produced ~40 false
  findings. **The 40 RATIFIED divergences are deliberate and MUST NOT be "fixed" into fabrication.**
- **Every HIGH was handed to an agent instructed to REFUTE it**, defaulting to RETRACTED if it could not
  independently reproduce the claim. **14 verdicts: 13 upheld, 1 retracted, 6 severities corrected DOWNWARD.**
  No severity was inflated — which is why there are no CRITICALs.

**The audit's own key insight:** *the guards that exist are excellent; the gaps are where a guard was never
placed.* Every HIGH was a **missing** control on a path whose sibling path already has the correct control —
the refund lacked the lock the payout has; the guest CRM lacked the `can:` gate 115 other routes have; the
queued mail lacked the tenancy restoration `RetryableSyncJob` has. **The correct pattern was already adjacent
to every defect**, which is what made remediation cheap and low-risk.

The honest-deferral and no-fabrication claims **survived a determined falsification attempt** (the Channex
"it's a stub" claim was investigated and RETRACTED — it is a real 6,398-line surface that fails closed).

### F.3 — THE THREE REMEDIATION PASSES (all 2026-08-08 — ALL COMPLETE)
| Pass | Commits | What |
|---|---|---|
| **1 · BLOCKERS** | `e4775ec` `6c10049` `f1c6513` `209b1f3` `8d0ad62` `42829e7` `7d4aed0` | The 5 HIGH + 2 launch-blocking deployment gaps. **H1 refund race** → mirrored `OwnerPayoutService::pay()` (transaction + `lockForUpdate` + idempotency key), **sequenced FIRST because it is inert today and unrecoverable at the Stripe live-key flip**; verified by racing **two real OS processes**. **H2 emails** → a tenant-restoring queued job; ⚠️ the audit's stated cause was wrong — it was a **RENDER** failure, not deserialization. **H3** → `can:` gates on the Guests CRM + 21 writes (coverage 92/124 → 113/124; the remaining 11 are exactly the enumerated self-only routes). **H4/H5** → `anonymize()` extended over three surviving stores. Plus the **cron ENTRY** (the schedule existed; only `schedule:run` was absent — without it GDPR deletion and the ID purge never run) and the **per-IP login throttle** (the per-credential one already existed). |
| **2 · HARDENING** | `6c390ad` → `505627a` | Security headers on every realm **including unmatched-route 404s** (group middleware runs only after a route matches — measured), secure cookie + TrustProxies + HTTPS forcing (**config:cache-safe** — `env()` under `config:cache` returns NULL, caught before shipping), the **plaintext IBAN flashed into the `sessions` table** (raw-row verified), `CsvSafe` on all 49 `fputcsv` calls, branded error views, 18 dependency advisories → 0, npm lockfile, complete `.env.example`, production-guarded seeders, 15 Google Fonts → self-hosted (**CSP now permits 0 external origins**). **4 silent-failure config traps caught before shipping.** |
| **3 · POLISH** | `78de41d` `4f27c81` `c5babfc` `f0e606c` `0925714` `c6b6788` `4142a05` `82506bb` `be1b345` `c40fef4` | **All 26 parity GAPs closed — 23 fixed, 3 reclassified as never-gaps** — plus the technician pool, WCAG AA contrast, and guest-portal responsiveness. ⚠️ **Verifying against the code found FOUR items WORSE than the audit reported:** the technician fix needed the **server-side write guards** (a crafted POST still assigned work to an External Owner after the pickers were clean); the owner booking detail was not a date-format bug but a **hardcoded check-out time that was simply FALSE** (10:00 where the property stores 11:00); the tax-residency dropdown was **a MONEY defect** (`tax_country` is the withholding lookup key, so choosing a residency wrote `"Portugal"`, matched nothing, and **silently computed a ZERO withholding on a real payout** — proven: `rateFor('Portugal')` → null, `rateFor('PT')` → the rate); and the guest portal had **ZERO viewport breakpoints** (a 225px overflow on all 14 screens — not in the audit at all). **Three "gaps" were closed by INVESTIGATION, not code** (guest email preferences and the documents hub already existed at a different location; guest Help got a real destination rather than invented FAQ content). **Five shared definitions were extracted rather than copied** — `OperatorLetterhead` (5 hand-rolled copies existed and had ALREADY drifted), `Countries`, `StayTime`, the `StaffRoster` helpers, and `FolioTotals` now returning the two halves it already computed and discarded. |

**FINAL VERIFICATION (2026-08-08, HEAD `c40fef4`):** suite **1692/1699 · 11,540 assertions**; PHPStan L5 (0);
Pint clean; `migrate:fresh --seed` clean. The 7 non-green are exactly the documented pre-existing set (see **J**).

### F.4 — THE WIREFRAME-PARITY SWEEP + WAVE 1 (2026-08-08 — *historical framing; ALL waves are now closed, see §F.7*)

**The sweep** (`docs/WIREFRAME-PARITY-SWEEP-2026-08-08.md`, plan in `…-FIX-PLAN-…`): all **124** wireframes decoded
from source (124/124 byte-identical to the prior decodes — nothing trusted an inherited artifact), mapped to live
routes with **zero unmapped and zero phantom**, captured through real authenticated sessions in 4 auth contexts,
analysed dual-method (wireframe × DOM × code), then **every finding re-verified against the code by the lead —
40 verified, 0 retracted.** Verdict: **the product is materially more honest than its own wireframes.** The four
global checks came back clean — **0** of 16 fabricated prototype values anywhere (#39 holds product-wide), 0
rendering failures, and the #28 privacy perimeter confined to exactly Guests CRM + Guest Profile. The dominant
failure mode is **copy that asserts behaviour the code does not implement** (~23 surfaces).

**WAVE 1 — the 5 HIGH: ALL FIXED, verified two ways, one per gate.**

| # | Defect | Commit | Live proof after the fix |
|---|---|---|---|
| 1 | `fill()` on a JSON-cast column REPLACED `policies.cancellation` wholesale on every save, silently deleting `free_until_days` → `isRefundable()` false for every reservation at that property | `ecf3685` | `{"type":"flexible","free_until_days":2}` survives a save |
| 2 | Owner revenue attribution had **no production trigger** (callers: seeder + tests only) — bookings raised occupancy/ADR while owner earnings, statements and payouts stayed at zero | `165dea2` | attributed **€5,980.00** == folio room revenue on owner units **€5,980.00**; backfill candidates **0** |
| 3 | `/app/owners` tested **every** payout, so one historical `paid` short-circuited every later branch — a finalized-unpaid statement read "Paid" and the payable KPI read €0.00 | `6312df2` | old logic → "Paid" + €0.00; new → the real **€885.60** outstanding |
| 4 | Two "room revenue" sources on ONE screen (`reservation_units.price_minor` vs folio) — **€11,560 vs €7,080, a 63% overstatement**, inherited by the PDF | `123bf89` | Revenue panel **€7,080.00** == Top-units Σ **€7,080.00** |
| 5 | Marketing email could be sent with **no opt-out anywhere** — no route, no link, no `List-Unsubscribe`; the only toggle lived inside a *claimed* account | `bca3679` | signed link works logged-out → consent false → send refuses; header on marketing, absent on transactional |

**⚠️ Two corrections to the sweep's own figures** (both now written into the report, §2b): HIGH-2's **"€1,680
unattributed" was WRONG** — it measured owner bookings with **no folio at all** (15 of 24 on a fresh seed, from
`seedAnalyticsHistory`), the **same root cause as HIGH-4**, not a second gap; and HIGH-4's **portfolio-vs-reports
half is NOT the same defect** and was deliberately left alone — `PerformanceMetrics` pro-rates `price_minor` by
in-range nights, a folio has no per-night split, and the ADR/RevPAR identities depend on that pro-rating.

**Two method lessons worth keeping.** (1) **A regression test must be proven to FAIL without its fix.** HIGH-3's
first test **passed without the fix** — it asserted the row's money figure, which prints from the statement
regardless of pay status — and had to be re-aimed at the pay-status signal. (2) **The obvious wiring can be the
harmful one:** hooking HIGH-2 to `reservation.confirmed` would have banked €0 and latched `liveVersion`
permanently, because attribution reads folio revenue `createBooking()` does not post.

**VERIFIED (2026-08-08, HEAD `bca3679`):** suite **1715/1722 · 11,610 assertions** (+23 regression tests, all 5
gates); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **The 7 non-green are the SAME documented
pre-existing set** (`BookingImportTest` 6 + `OwnerUnitDetailViewTest` 1 — see **J**); the count did not move.

*(As written at the time: "STILL OPEN — WAVE 2 and WAVE 3". **Both are now closed** — §F.5, §F.6, §F.7.)* §5
refusals and §6 extra work were preserved untouched throughout.

### F.5 — WIREFRAME-PARITY WAVE 2 (2026-08-09 — COMPLETE: the 3 structural MEDIUMs + all 23 false-copy items)

The sweep's headline finding — *"the money reconciles and the privacy perimeter holds; what fails is COPY
THAT ASSERTS BEHAVIOUR THE CODE DOESN'T IMPLEMENT"* — is now **closed**. Nine gates, each with a regression
test **proven to fail without its fix**:

| Gate | What | Commit |
|---|---|---|
| S1 | Guest wallet: check-then-act with NO transaction and NO lock; the UNIQUE `idempotency_key` unused. A raced promo redemption returned **success TWICE** — free money, `max_redemptions=1` blown past | `00a9fe5` |
| S2 | `notes_guest`/`notes_internal` written by 3 paths, rendered by NONE — a guest's "please leave a cot" reached no member of staff | `47cf1ff` |
| S3 | "Switch to yearly" hardcoded `plan=pro` — an Essential org was silently upgraded | `cb4dec0` |
| #17 · #21 | "Revenue today" summed the WHOLE ORG beside one property's occupancy · "Latest payout" was a tied sort returning the OLDEST (April €442.80 for August €721.60) | `8deb066` |
| #11 · #12 · #14 | "This month" was the month of the latest BOOKING · "Nightly rate" showed achieved ADR (€180 configured vs €193.33 ADR) · the add-on STAY TOTAL sat under a per-night label | `8437a93` |
| #3 · #5 · #8 | Two refund ETAs for one refund · two payout-frequency labels, one of them arithmetically wrong · "All 12 months" on any year | `599a452` |
| #2 · #6 · #10 · #13 · #20 · #22 | Unenforced or unqualified claims | `f930dda` |
| #4 · #7 · #9 · #15 · #19 | Prototype fiction: `VYR-…`, "All systems operational", Apple Pay, a waitlist email with no trigger, a guest-only footnote on owner threads | `babbb74` |
| #1 · #16 · #18 · #23 | Controls wired to nothing: a template switch that still sent, a dead SLA signal, write buttons shown to view-only roles, a guest language setting nothing read | `b44bb64` + `9620b95` |

**⚠️ TWO ITEMS ARE COPY-ONLY BY FOUNDER DECISION (2026-08-09) — do NOT "finish" them without a decision:**
- **#6 Owner Rates.** `RateProposalService` genuinely enforces the guardrail (mode, ±% tolerance, floor and
  ceiling) — but only on a **proposed** rate; `RateActionController`'s direct write ignores it entirely. The
  copy now promises exactly the proposal path. **Binding the operator's direct write to an owner's
  floor/ceiling shifts control between the two parties → deferred as a product decision.**
- **#10 Team limit.** The real plan limit is now displayed (Essential 5, Pro genuinely unlimited). **Whether
  it should be ENFORCED belongs with the open billing decision (G.1) → deferred.**

**⚠️ FOUR PRE-EXISTING TESTS WERE HOLDING THE FALSE COPY IN PLACE.** `OwnerDocumentsViewTest` demanded
`assertSee('All 12 months')`; `OwnerHelpTest` demanded `assertSee('All systems operational')` and its own
comment called that "HONEST"; `OperatorWorkOrderTest` asserted a fresh org shows no SLA card; and
`MaintenanceDetailViewTest` asserted the SLA card's ABSENCE as "no fabricated SLA" on a ticket that genuinely
had an 8h target. **A green suite was actively defending the claims the sweep found.** All four were
corrected toward the truth (they now demand the true copy AND forbid the false), never relaxed. Same lesson
as the five READ-found security holes (#26–#30), on a new axis: **green tests are necessary, not sufficient.**

**Two self-inflicted bugs, both caught by verification rather than review:** a `@php(...)` directive that
compiled to a malformed `<?php(` and **500'd the Payout Method page** while Pint, PHPStan and content-matching
tests all passed over it (the "static checks cannot see a rendered page" rule, live); and the #16 fix being
incomplete because `firstOrCreate()` ALSO returns a model without schema defaults — the same trap one level
down, caught only by running the full suite and noticing the non-green count had gone 7 → 8.

**VERIFIED (2026-08-09, HEAD `9620b95`):** suite **1748/1755 · 11,722 assertions**; PHPStan L5 (0); Pint
clean; `migrate:fresh --seed` clean. The 7 non-green are the SAME documented pre-existing set.

*(As written at the time: "REMAINING — WAVE 3". **Now closed** — §F.6 and §F.7.)*

### F.6 — WIREFRAME-PARITY WAVE 3 (2026-08-09 — **COMPLETE**; closure recorded in §F.7)

**The headline is a method result: verify-first invalidated FOUR of the five named items.** They were already
fixed or were never gaps, and none was re-fixed — the corrupted date literal (`P` = UTC offset, `M` = month;
fixed by the polish pass), the `Team &amp; access` double-escape (renders correctly), the Agreement raw-JSON
leak (not reproducible on current data), and the **Statement-of-Account PDF**, which has been its own document
since the 2026-08-07 audit: a 4-tile KPI band (Charges / Paid / Credited / Balance due) above an "Account
activity" table, built from `FolioDocument::data` so it shares the receipt's folio source and cannot drift (#17).

**Six genuine gaps fixed** — `270806f`, `51c3f9f`, `35dc05f`:
- **The commission rate was written THREE ways** — four call sites, two of them on the SAME page (`18.00%`
  beside `18%`), because `commission_pct` is a `decimal:2` and a string cast yields "18.00". All now route
  through one shared `OwnerCommissionRate`.
- **A finished stay advertised an upcoming check-in** — the timeline never consulted terminal status, so a
  checked-out booking rendered "Check-in — upcoming" above its own "Checked out". One demo row is genuinely in
  that state. Now gated on the certified `AssignmentService::TERMINAL_STATUSES` (#29's list).
- **Pending add-on rows rendered BLANK** (functional) — the controller read `payload['add_ons']`; the service
  writes `add_on_ids`. Now the right key, resolved to real names rather than ULIDs.
- **The Payments overview hid refunds** — `FolioTotals::refunded_minor` already existed and was never
  surfaced, so "Paid to date" (NET of refunds) looked smaller with no explanation. Same single pass as `paid`.
- **The reservation page invented arrival hours** — `From 15:00` / `By 11:00` where the property set none (#39).
- **Two Insights figures lacked their unit** — weekday occupancy tiles, and the owner percentage-point delta.

**The rest of the tail, all closed in later gates:** two stated expiries that were never stated read from
config/constant and two error-page items reclassified as refusals (`f36356b`) · the **"Suggested" housekeeper**
made genuinely least-loaded (`9d90cc1`) · **pagination** on operator Guests + Reservations (`f12dbe3`) and the
**Notifications feed in BOTH realms** (`a54bfea`) · the owner **payout year filter** (`fb51b40`) · owner
**chart scaling** (`c0b31ab`). Three further items were escalated OUT of WAVE 3 because they touched auth or
isolation — see the auth/isolation pass below.

### ✅ THE AUTH/ISOLATION PASS IS COMPLETE (2026-08-09) — all three flagged items fixed

| # | Gap | Commit | Adversarially verified |
|---|---|---|---|
| 1 | **Maintenance Costs ignored the property access restriction** — `resolved()` was tenant-scoped ONLY, no property constraint, so a pivot-restricted user saw the costs, **unit names** and billable lines of properties they may not work in | `8ddd389` | Real `property_user` row: user pivoted to Vista (€0.00) saw **no** Seaside €225 on screen **or in the CSV**; unrestricted user saw €225 on both. Export + screen agree (#17) |
| 2 | **Workspace picker had no write path** — `active_organization_id` was read and never written, pinning a two-org user to `first()` forever | `5f27085` | Real second membership: picker listed both, switch persisted and moved the tenant; foreign org id refused; single-workspace user gets **no** control |
| 3 | **A password reset left live sessions alive** — the hash and remember-token rotated, but server-side sessions were untouched, so an attacker holding a session was unaffected | `306715f` | Seated a real attacker session, changed the password through the UI: attacker's session **gone**, own browser still signed in |

**Design notes worth keeping.** (1) The cost scope MIRRORS the board's `whereHas('unit', property_id ∈
readIds)` rather than inventing a second mechanism, and `report()` now REQUIRES the read-set so PHPStan
fails any caller that forgets it. (2) The workspace switch mirrors `PropertySwitchController` exactly and
clears `current_property_id`, so no foreign property id survives the move. (3) Session termination lives in
the SHARED `changePasswordTo()`, and `OwnerSecurityController` was pointed at the same helper, so
"sign out others" and "reset kills siblings" cannot diverge.

**Two things the BROWSER caught that the tests did not:** switching to a workspace where the user holds a
Membership but no back-office role landed on a 403 — correct behaviour (a Membership is not operator access)
but an invited 403, so the picker now lists only workspaces the user can enter (#30); and the role-pivot
query first 500'd on `model_has_roles.team_id`, because spatie's teams column is configurable and is
`organization_id` here.

**Riders delivered:** the Maintenance-Costs **period selector** (3M/6M/12M/All time) the report never had —
with the export links carrying the active period, so the PDF/CSV always covers the window on screen.

**Empty-pivot behaviour preserved** as documented: an empty `property_user` still means full-org access.

---

⚠️ **(historical — now resolved) THREE OPEN ITEMS WERE NOT WAVE 3:**
1. the login **workspace picker** (`active_organization_id` is read and never written);
2. **password-reset sibling-session termination**;
3. **Operator Maintenance Costs ignores the property access restriction** (escalated 2026-08-09) —
   `MaintenanceCostService::resolved()` is tenant-scoped ONLY, with no property constraint, so a
   pivot-restricted user would see the costs, **unit names** and billable lines of properties they may not
   work in, while the sibling board scopes through `currentReadIds`. **Latent today** (`property_user` has 0
   rows and an empty pivot means full org), real the moment anyone is pivoted to a subset. ⚠️ When fixed,
   `MaintenanceCostExportController` calls the SAME service — the export must take the IDENTICAL scope and
   period or it will disagree with the screen (#17). The period selector is genuine WAVE-3 work that can ride
   along with it.

**VERIFIED (2026-08-09, HEAD `35dc05f`):** suite **1758/1765 · 11,751 assertions**; non-green exactly the
documented **7**; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. Every fix browser-rendered.

### F.7 — 🏁 THE WIREFRAME-PARITY SWEEP IS FULLY CLOSED (2026-08-09)

**All 124 wireframes swept; every finding resolved, verified-already-fixed, or reclassified as a refusal —
0 unaddressed gaps.**

| Wave | Scope | Outcome |
|---|---|---|
| **1** | 5 HIGH | fixed (`ecf3685`→`bca3679`) |
| **2** | 3 structural MEDIUMs + 23 false-copy | fixed (`00a9fe5`→`b44bb64`, `9620b95`) |
| **3** | the minor/low tail | fixed / already-fixed / reclassified (`270806f`, `51c3f9f`, `35dc05f`, `f36356b`, `9d90cc1`, `f12dbe3`, `fb51b40`, `c0b31ab`, `a54bfea`) |
| **auth/isolation** | 3 escalated items | fixed (`8ddd389`, `5f27085`, `306715f`) |

**The final three (this pass).** The owner **payout year filter** (real years, history-only narrowing) · the
owner **chart scaling** — bars scaled to the goal crushed real data (a €6,000 target rendered an €885.60 best
month at 7–15%; now 50–100%, with an above-range goal line captioned as such) · **Notifications pagination**
across BOTH realms, with PHPStan's invariant `TValue` resolved by SHAPE (items = a list, paginator = of
models) rather than any cast, widening, or baseline entry.

**⚠️ NOT "fixed", by design — do not close these:** the **§5 refusals-to-preserve** (fabricating them is
worse than absent — including 404 "popular pages" and 500 "support reference", which have no analytics and no
correlation id behind them) and the **two founder-deferred items**: **#6** the rates guardrail (honest copy;
binding the operator's direct write is a shift of control between parties) and **#10** the team limit (real
limit displayed; enforcing it belongs with the billing decision).

**The lesson the whole sweep taught, restated:** a green suite is necessary and not sufficient. Four
pre-existing tests were actively *defending* false copy; roughly four more passed without their fix; and
across the sweep the browser caught what every static check missed — including, in the final gate alone, two
separate 500s (one from a Blade directive name written inside a CSS comment).

**VERIFIED (2026-08-09, HEAD `a54bfea`; re-confirmed on the current HEAD):** suite **1796 passed / 1803 ·
11,858 assertions**; non-green exactly the documented **7** (5 failures + 2 errors = `BookingImportTest` 6 +
`OwnerUnitDetailViewTest` 1); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; both realms
browser-rendered.

**⬅ REMAINING VYROBEL WORK IS NO LONGER PARITY:** the ⛔ **OPEN BILLING DECISION** (G.1) · the **deployment
sprint** · the **scale-performance workstream** · the founder's **go-live flips**.

## (G) ⚠️ OPEN DECISIONS — the founder's call, NOT defects

> ⚠️ **SAVE v6 — two FURTHER founder calls surfaced by the owner design pass.** Both are recorded here so a
> future session does not "fix" them unilaterally:
>
> - **G.4 — D16-7: money in flight is invisible in the KPIs, on BOTH realms.** `OwnerPayoutService::pay()`
>   writes **`processing`** on the real-transfer path, but the payout tiles are built from `paid` and
>   `pending` only — so a payout genuinely in flight appears in **no tile** (confirmed operator-side, then
>   again owner-side at OWNER-3). ⚠️ **This is a Stripe-live prerequisite**: today the engine is test-mode so
>   nothing sits in `processing` for long, but the moment real transfers run, an owner's money will vanish
>   from their own summary while it moves. Surfaced, deliberately NOT fixed (it is a computation, not a
>   design gap). Related, also logged: the payout history has **no deterministic order** — every demo row
>   shares one `created_at` because the engine creates a run in a single pass, so ties are the normal case
>   and a ULID tiebreaker is needed in the ONE shared read.
> - **G.5 — the "earned this month" figure DEFINITION (OWNER-5).** My Units says *"Earned this month
>   €541.60"* (all ledger lines by `service_date`) while Statements says *"August 2026 €721.60"* (the three
>   lines that statement CLAIMED). **Both are correct**; the €180 gap is a real unstatemented expense.
>   Whether the tile should include unstatemented ledger items is a **product call**, not a bug — logged, not
>   re-worded.

### ✅ G.1 — THE BILLING / PRICING MODEL — **🏁 CODE *AND* BUILD COMPLETE (2026-08-13); THE FLIP IS GATED ON NON-CODE ONLY**

> 🏁 **NOTHING BILLING-RELATED REMAINS TO BUILD.** Four passes, the DO-BILL pair, and the **super-admin plan
> panel** (`f389317`) — the last item on the list. ⚠️ **v13 said "code complete"; that was accurate but
> incomplete**, because one build was still outstanding. Both halves are now done.
>
> ⚠️ **THE PANEL WAS NOT A CRUD FORM — it closed the invariant's ADMIN-SIDE BACK DOOR.** Subscriptions stored
> only `plan_id`, so every displayed price came from the LIVE plan row, and `update()` could already change it
> under a live subscriber. Stripe would have kept charging the old Price while Vyrobel displayed the new one:
> **`display ≠ charge`, re-opened from the side four passes never checked** (§N.19.1, §N.19.6). Subscriptions
> now **capture their terms** — quantity stays live, terms freeze — and existing rows were **backfilled**, so
> the ruling protects customers already on the books rather than only future ones.
>
> 🏁 **THE BILLING CODE IS COMPLETE AND HONEST END-TO-END** — four passes plus the DO-BILL pair:
> **`DO-BILL-2`** (`c6d398a`) retired the last live §N.8d (the Add-property modal's unconditional "billed per
> unit"; the sibling sweep measured the cluster at ONE), and **`DO-BILL-1`** (`dcce44f`) closed the last money
> gap (the stored VAT number now reaches the Stripe customer as a typed `tax_id`, fail-safe, through ONE sync
> path from three writers and the webhook). **Cross-pass canon: §N.17 + §N.18.**
>
> ⚠️ **WHAT "COMPLETE" MEANS HERE, precisely:** the engine charges the plan's declared shape · the page
> renders from that shape · the configured Price reaches Stripe or the app **refuses to bill** · Stripe Tax
> computes the customer's own tax · **no surface claims a tax or a per-unit consequence that is false for some
> org** · the customer's VAT number reaches Stripe so reverse charge can apply · **and no admin edit can make
> the displayed price diverge from the charged one** (§N.19.6). ⚠️ **It does NOT mean money
> has moved:** no live key has ever been used, `StripeSafetyGuard` is untouched, and everything is proven
> against the fake client.
>
> 🏁 **THE FOUR PASSES ARE CLOSED AND CONTIGUOUS:** the read-only investigation → **pass 1** the shape-aware
> engine (`43862db`, the blocker dissolved) → **pass 3** the page renders from the plan (`615904c`, the live
> §N.8d retired) → **pass 2** the real Price wiring + loud failures (`7de1f35`, the tax-hop under-charge found)
> → **pass 2b** Stripe Tax (`9fed4a1`, the app honest under variable tax; two more surfaces fixed).
> **Cross-pass canon: §N.17.** ⚠️ **This entry is no longer an OPEN DECISION — it is a founder TASK LIST.**

> ⚠️ **THE QUESTION WAS DISSOLVED, NOT ANSWERED.** This entry framed it as *"which of two models does Vyrobel
> sell — flat tier (A) or per-unit (B)?"* The founder's ruling is that **pricing is configurable DATA and the
> product supports BOTH shapes**, managed from a later super-admin panel. There is no model to choose; there is
> a plan **engine** that must charge faithfully to whatever shape a plan declares. The "A vs B contradiction"
> was an artefact of two hardcoded models coexisting, not a real either/or.
>
> **RULING 1 — pricing is configurable data, both shapes.** A plan is `flat` (one price regardless of units) or
> `per_unit` (rate × the org's real units). ⚠️ **The "+ VAT" this line originally carried was RETIRED by pass
> 2b** — a per-unit charge is now the **pre-tax subtotal** and Stripe computes the customer's own tax (§N.17.3).
> **RULING 2 — per-unit prorates immediately.** A mid-cycle unit change charges the difference now, via Stripe
> proration — not at the next renewal.
>
> ✅ **BILLING PASS 1 (2026-08-13) BUILT THE ENGINE TO BOTH RULINGS** — `plans.pricing_type` + the per-unit rate
> and VAT as columns; `Plan::quantityFor()`/`chargeFor()`; a shape-aware `createSubscriptionCheckout` (the
> `'quantity' => 1` hardcode is gone) and `BillingService::syncSubscriptionQuantity()` for the prorated
> mid-cycle change. Proven against the FAKE Stripe client — **no live money, by construction**.
> ⚠️ **The quantity is DERIVED, never stored** (`BillableUnits`) — the number charged is the number the org has,
> and the Billing page's figure now reads the same source, so the two cannot disagree (§N.8d with money attached).
>
> ✅ **BILLING PASS 3 (2026-08-13) RETIRED THE LIVE §N.8d.** The customer Billing page now renders FROM the
> org's plan shape, reading pass 1's engine: a FLAT org sees its flat price with **no per-unit breakdown, no VAT
> split and no proration promise**; a PER-UNIT org sees rate × real units (⚠️ **pre-tax since pass 2b — the
> "+ VAT" here is superseded**, see §N.17.3) and the proration line, which is
> **true** since pass 1. ⚠️ The per-unit markup lives INSIDE the per-unit branch, so a flat org cannot render it
> — unrepresentable rather than merely hidden (§N.16.3). ⚠️ **Display = charge**: every figure is
> `Plan::chargeFor()`, the same expression Stripe is sent, so the page cannot quote one number while the invoice
> says another. The "€88.56 placeholder" headline is now a real derived figure.
>
> ⛔ **STILL OPEN, and it is what now blocks the flip:**
> **②** real Stripe **Price objects of each shape** + env wiring — the plan rows still carry `price_fake_*` ids
> (it fails loudly: Stripe rejects an unknown price, and `startCheckout` throws on an empty one).
> Plus the **super-admin plan-CRUD panel** (which is what would let a founder actually create a per-unit plan;
> today one can only be made in a seeder/console).
>
> ✅ **RULED (2026-08-13): FLAT TIERS ARE VAT-INCLUSIVE — €49/€99 is the final price.** Pass 3's question is
> answered; the flat view correctly shows no VAT split, and a flat Stripe Price is created `tax_behavior:
> inclusive` at the same amount. Nothing in the engine changes.
>
> ✅ **BILLING PASS 2 (2026-08-13) WIRED THE PRICES AND MADE THE FAILURES LOUD.** `STRIPE_PRICE_*` is the source;
> the seeder's `price_fake_*` fallback is now **dev-only** and **throws in production**; `startCheckout` refuses
> a missing or placeholder id before creating a session. Specs for the founder: `docs/BILLING-STRIPE-PRICES.md`.
>
> ⚠️ **AND PASS 2 FOUND THE REAL GAP — NOTHING SENDS TAX TO STRIPE.** The checkout sends `price` + `quantity`
> and no tax at all, while a per-unit plan's engine total is `units × rate + VAT`. With a €9 Price and 8 units
> Stripe would take **€72.00** where the page says **€88.56** — a silent **€16.56/month under-charge**, §N.8d at
> the money boundary. ⚠️ **The app now REFUSES to bill a per-unit plan in production until the convention is
> declared** (`STRIPE_TAX_BEHAVIOR`), rather than under-charge quietly. Flat tiers are unaffected (inclusive).
>
> ✅ **RULED (2026-08-13): PER-UNIT VAT REACHES STRIPE VIA STRIPE TAX — `tax_behavior: exclusive` +
> `automatic_tax`, with reverse charge handled by Stripe.** Option B. The €9 unit Price is PRE-TAX and Stripe
> adds the customer's own tax at checkout — including **€0 for an EU business under reverse charge**.
>
> ✅ **BILLING PASS 2b (2026-08-13) WIRED IT — AND THE REAL CHANGE IS THAT THE APP GAVE UP A CLAIM.** The ruling
> makes tax **customer-dependent**, so the engine's hardcoded `+ 23%` stopped being merely unsent and started
> being **false**: a reverse-charge business pays €0, not 23%. The fix was therefore NOT better arithmetic — it
> was to stop asserting a post-tax total the app cannot know. **Three layers moved together:**
> - **Checkout** — `automatic_tax: enabled` for the exclusive (per-unit) shape only, plus the inputs Stripe Tax
>   actually needs: `billing_address_collection: required`, `tax_id_collection: enabled`, and
>   `customer_update.address` (without which Stripe rejects the session for an existing customer). ⚠️ **Flat is
>   untouched** — €49 is inclusive and final, so asking Stripe to add tax would inflate it.
> - **Engine** — `Plan::chargeFor()` per-unit is now the **PRE-TAX SUBTOTAL** (`units × €9`), carrying a new
>   `tax_known: false`. Flat keeps `tax_known: true`.
> - **Page** — per-unit shows the subtotal + **"tax calculated at checkout"**; the fixed "incl. 23% VAT" is gone.
>   One keyed `tax_note` (§N.16.3) drives all three sites, so they cannot drift apart again.
>
> ⚠️ **THE PASS-2 REFUSAL IS LIFTED BECAUSE THE CORRECT PATH EXISTS, NOT BECAUSE THE GUARD WAS REMOVED.**
> `STRIPE_TAX_BEHAVIOR` now defaults to `exclusive` and `assertTaxConventionResolved()` still throws the moment
> it is unset — proven by a test that unsets it and watches production refuse again.
>
> ✅ **DISPLAY = CHARGE = STRIPE, restored under variable tax.** The app asserts only the half it knows (the
> pre-tax subtotal, which is exactly `Price × quantity`); Stripe owns the tax. Pass 2's €72.00-vs-€88.56 gap is
> closed from the other end — not by sending the 23%, but by never having claimed it.
>
> ⚠️ **2b FOUND TWO MORE SURFACES ASSERTING THE SAME FIXED VAT — both beside the one pass 3 fixed** (§N.13.1
> again: the defect sits next to the flagged item). **(1)** the Billing page's own **"Next invoice" strip** read
> `SubscriptionPricing`, not the plan — so a FLAT org saw "€49.00/mo" in the headline and "€72.00 + €16.56 VAT"
> in the strip below it. **(2)** operator **Settings → "Your plan"** priced *every* org with the per-unit
> formula: the demo org on flat Essential displayed **"Vyrobel Essential · €88.56/mo"** beside a Billing page
> saying €49.00. Both now read `Plan::chargeFor()` (§N.8f), and `SubscriptionPricing` no longer computes VAT at
> all — it had become a second, divergent total for one concept (5535 against 4500).
>
> ✅ ⚠️ **CLOSED at `dcce44f` — the paragraph below describes the gap as it stood, and the sync now exists**
> (typed `tax_id`, three writers + the webhook, fail-safe; §N.18.2–.4). Kept for the reasoning that was true
> when written. ⚠️ **FLAGGED, NOT SILENTLY DEFAULTED — the VAT-ID sync gap (`DO-BILL-1`).** The app collects
> `organizations.billing_vat_id` in its own checkout form, but that value is used **only for PDF letterheads and
> is never sent to Stripe**. Stripe's own `tax_id_collection` now asks for it again at the hosted page; a
> customer who skips it there is charged VAT they could have reverse-charged. Syncing the stored id onto the
> Stripe customer is a follow-up, deliberately not invented here.
>
> ✅ **`DO-BILL-1` CLOSED (2026-08-13) — the stored VAT number now reaches the Stripe customer.**
> `organizations.billing_vat_id` was PDF-only; Stripe Tax decides reverse charge from the CUSTOMER's `tax_ids`,
> so a business that had already given Vyrobel its number and skipped Stripe's prompt paid VAT it could have
> reverse-charged. It is now attached as a **typed** `tax_id` (`VatId` derives `eu_vat`/`ch_vat`/`gb_vat`/
> `no_vat` from a **closed** country map), pushed from all **three** writers and from the **webhook** the moment
> a customer id first exists — which matters most for RENEWALS, since Stripe re-computes tax per invoice.
>
> ⚠️ **THE POSTURE IS ASYMMETRIC AND DELIBERATE: when in doubt, CHARGE THE TAX.** An untypeable number is never
> sent, a Stripe rejection is never treated as success, and both are logged (never the value — it is a customer
> identifier). Overcharging is visible and refundable; wrongly **zero-rating** is silent and lands on the
> operator at an audit.
>
> ⛔ ⚠️ **A NEW FOUNDER DECISION — VAT VALIDATION (compliance, deliberately NOT decided here).** The app performs
> a **STRUCTURAL** check only ("is this plausibly a VAT number of a country we can name?"), never a **VALIDITY**
> check ("does this number exist and belong to this business?"). Two options:
> - **(a) send as-is, let Stripe validate** — what is implemented. Stripe validates EU numbers against VIES
>   **asynchronously**, so a sale can be zero-rated *before* validation completes. Simple; a small window.
> - **(b) require VIES validation before honouring reverse charge** — safer, but adds a dependency and a state
>   ("pending validation") the product does not have today.
>
> ⚠️ The **safe half of (a) is implemented regardless** (never send what we cannot type; never treat a rejection
> as success). Choosing (b) is a build, and it is the founder's call — flagged, not guessed.
>
> ⛔ **REMAINING — the founder's step, which no code here can do** (`StripeSafetyGuard` refuses a live key
> outside production): **(1)** ⚠️ **enable Stripe Tax in the dashboard and register the tax jurisdictions** —
> `automatic_tax` in code is **INERT** until that is done, so this is not optional; **(2)** create the Price
> objects per the spec doc (the per-unit one at **€9.00, `tax_behavior: exclusive`**); **(3)** fill the
> production env. Plus the
> **super-admin plan-CRUD panel** (today a per-unit plan can only be made in a seeder or console).
>
> ⚠️ **A CORRECTION TO THE FIGURES BELOW (measured 2026-08-12).** The historic "B ~€44 vs A €99, billed DOUBLE"
> is stale and its **sign has reversed**: the demo org measured **B €88.56** (8 units) vs **A €49.00**
> (`essential_monthly`). The divergence is not a fixed 2× — it crosses at ~4.4 units (Essential) / ~8.9 (Pro)
> and grows unbounded in **either** direction with portfolio size. The engine dissolves this for any plan whose
> shape is declared; ③ closes the display half.

<details><summary>The original entry, kept for the reasoning that was true when written</summary>

### ⛔ G.1 — THE BILLING / PRICING MODEL (a GO-LIVE BLOCKER; **UNRESOLVED** — superseded above)
**Two contradictory pricing models run simultaneously, and only ONE reaches Stripe:**

| | Definition | Shown on | Reaches Stripe? |
|---|---|---|---|
| **A · flat tier** | `config('billing.tiers')` — Essential €49/mo, Pro €99/mo | paywall · checkout · tier cards | **YES** — a subscription is created against `stripe_prices[tier][interval]` |
| **B · per-unit** | `SubscriptionPricing` — active units × `per_unit_minor` (€9) × VAT | operator Billing "what you pay" · Settings "Your plan" | no |

For the demo org they disagree materially: **B quotes ~€44/mo where A charges €99/mo.** Harmless today because
no charge is live. **At the Stripe live-key flip it becomes a customer billed roughly DOUBLE what the app told
them.** Both figures are honest, real code; neither is fabricated. What is missing is the **product decision**
about which model Vyrobel sells.

⚠️ **THIS IS THE FOUNDER'S BUSINESS DECISION — a future session must NOT pick a side.** Picking one silently
would be inventing a product decision, which is worse than inventing data. **Surface it; do not resolve it.**
**Resolution paths and their real cost are recorded in `docs/DECISIONS.md` (2026-08-08).** Adopting **A** is a
UI-only change (nothing reaching Stripe moves). Adopting **B** additionally requires moving the Stripe
integration to quantity/metered prices, because the tier Price IDs would no longer describe the charge — a real
billing-integration workstream.

⚠️ **RECORD-KEEPING CORRECTION:** an earlier revision of these notes described this as "deferred by the
founder." It was not a founder ruling — **deferring to go-live is the assistant's RECOMMENDATION, pending the
founder's confirmation.** Read it as **OPEN** until the founder states their choice.

⛔ **BLOCKS:** the Stripe live-key flip (alongside the already-fixed H1 refund race). The marketing site's
Pricing page (**K**) must also match whichever model is chosen.

⚠️ **A SEPARABLE BUG FOUND ALONGSIDE IT IS ALREADY FIXED, and must not be confused with the decision:**
"Switch to yearly" hardcoded `plan=pro`, silently upgrading an Essential org. It was fixable regardless of
which pricing model wins, and was fixed in WAVE 2 (`cb4dec0`) — ⚠️ note the trap: `plans.code` is
`{tier}_{interval}`, so the "obvious" fix reproduced the bug because `resolveTier()` falls back to `'pro'`;
the real fix is `Str::beforeLast($code, '_')`.

</details>

### G.2 — OWNER RATE GUARDRAIL (#6) — honest in copy TODAY, **enforcement DEFERRED**
`RateProposalService` genuinely enforces the guardrail (mode, ±% tolerance, floor and ceiling) — but only on a
**proposed** rate; `RateActionController`'s direct write ignores it entirely. **The copy now promises exactly
the proposal path it enforces**, so nothing false is stated. **Binding the operator's direct write to an
owner's floor/ceiling shifts control between the two parties — a product decision, not a defect.** Do not
"finish" it without an explicit founder call.

### G.3 — ESSENTIAL TEAM LIMIT (#10) — honest in display TODAY, **enforcement DEFERRED**
The **real** plan limit is now displayed (Essential 5, Pro genuinely unlimited) rather than an invented one.
**Whether it should be ENFORCED is a paywall lever and belongs with the billing decision (G.1).** Deferred
with it.

## (H) HONEST DEFERRALS (each a founder go-live FLIP — honestly surfaced in-product TODAY, NOT unbuilt; verified correct even under falsification)
- **Channex / OTA live sync** — unblocks OTA brand-split, channel two-way sync, rate push, Expedia insight, OTA inbox channels (today: real `channels.status`, honest "connect Channex", never a fabricated last-sync).
- **Live Stripe charge + payout go-live** — the live-key flip + Connect KYC + SEPA + IBAN verification (today: TEST-MODE-first hosted Checkout across all 3 seams — subscription / guest card / owner payout — webhook-driven, SAQ A no raw PAN, `StripeSafetyGuard` forbids a live key outside production; the payout-engine `PAYOUT_ENGINE_ENABLED` flip; `docs/STRIPE-PRODUCTION.md`).
- **SMS (Twilio)** — door code by EMAIL meanwhile. **Push (FCM/APNs)** — no VAPID. **Websockets/presence** — Messages has no realtime/typing. **Live LLM** — currently a `FakeLlmClient`. **Third-party ID scan / KYC** — capture-only, `verification_status=pending` (never "verified"). **Production email** is LIT (Postmark, `MailSafetyGuard`); **OAuth server** (Passport, owner-scoped) is built — keys are a per-env flip. **Tax rates** are founder-configured per jurisdiction (`tax:set-rate` — the seeded rates are EXAMPLE only).

### H.bis — 🚫 THE §5 REFUSALS-TO-PRESERVE (a future session must NEVER "fix" these into fabrication)
These are **absences on purpose**. Fabricating any of them is worse than leaving it absent (#39):
- **404 "popular pages"** — there is no analytics, and the uniform 404 is a deliberate **#38** choice:
  distinguishing "doesn't exist" from "isn't yours" would leak that another org's record exists.
- **500 "support reference"** — there is no correlation id behind it; a fake reference is worse than none.
- **No fake bank verification / micro-deposits** and no "Verified" badge on an unverified payout account.
- **KYC stays `pending`, never "verified"** — the ID is captured and encrypted; nothing verifies it yet.
- **No raw PAN anywhere** (SAQ A — hosted Checkout only), **no stored-card "····4242"**, no on-page Apple Pay.
- **No SEPA export**, and **"Withdraw early" stays DISABLED** — there is no owner-initiated transfer rail, so
  it must never fake a withdrawal, a transfer, or an "in transit" figure.
- **Reversal PDFs 404 without a real reversal**; no invented fee, tax, withholding or OTA brand split.
- **Guest contact is absent from the owner realm and the front desk** (#28); the **door code is
  window-gated** (#33); **New-role / Delete-role are refused** (#26/#30).
And the **§6 "extra work to keep"** — features the product has that the wireframes never asked for — must not
be removed in the name of parity.

## (I) WHAT REMAINS — the roadmap

0. ⬅ **THE DESIGN-PARITY PASS — the ACTIVE track (§N).** 🏁 **The OPERATOR realm is COMPLETE (42/42, 34
   gates, `0aa9ede`→`edb7cce`)**; **next is the OWNER realm**, then the untouched **32 guest** set. It
   also accumulates **the backend backlog** (`docs/design/MISSING-BACKEND-BY-PAGE.md`) that becomes a
   separate later phase. Presentation-only — it changes nothing on items 1–5.
1. ⛔ **THE BILLING DECISION (G.1) — the founder's business call.** Blocks the Stripe live-key flip. Do NOT
   pick a side; surface it.
2. **THE DEPLOYMENT SPRINT** — there are currently **NO deploy artifacts**: no Dockerfile, no CI/CD, no deploy
   script, no backup strategy. `docs/DEPLOYMENT.md` is the runbook and includes the two traps the earlier
   passes surfaced: the **ONE `* * * * * php artisan schedule:run` cron entry** without which all 8 scheduled
   commands silently never run (**including the GDPR 14-day guest deletion and the 30-day ID purge**), and the
   **`config:cache` gotcha** (`env()` outside a config file returns NULL once cached). ⚠️ **NEVER `db:seed`
   production** — the seeders create a demo super-admin with password `password` (now production-guarded).
3. **THE SCALE-PERF WORKSTREAM** — *before the first large tenant, NOT a launch blocker.* Measured by the
   audit: **composite indexes worth 73×–200× at 500k rows**, N+1 on heavy pages (**210 queries on
   `/app/insights`, 116 on the PUBLIC storefront**), **zero caching app-wide**, unbounded table growth.
   ⚠️ **"Zero pagination app-wide" is now PARTLY CLOSED** — operator Guests, operator Reservations and the
   Notifications feed in both realms are bounded and GET-paged through the shared `<x-pager>` (2026-08-09).
   Any further list follows that pattern, **not** `WithPagination` (see **B**/#20).
4. **THE FOUNDER GO-LIVE FLIPS** — see **H**. ⚠️ The Stripe flip is gated on **both** the already-fixed H1
   refund race **and** the still-open billing decision (**G.1**).
5. **THE MARKETING WEBSITE** — see **K**; its Pricing page must match whichever billing model is chosen.

## (J) Known tech-debt (NOT product bugs)

**The 7 non-green are two distinct things — ⚠️ the older "both are flakes" label was HALF WRONG and is
corrected here. THE COUNT IS THE BASELINE: if a run shows 8, you broke something — go and find it (that is
exactly how the incomplete `firstOrCreate` fix in WAVE 2 was caught).**

⚠️ **RE-VERIFIED AT SAVE v12 (2026-08-13, HEAD `9fed4a1`) — AND THE COUPLING RUNS BOTH WAYS.** A full run showed
**5** non-green, all `BookingImportTest`. Measured immediately after: `BookingImportTest` passes **11/11 in
isolation** (in-suite only, as always), while `OwnerUnitDetailViewTest` **fails 1/13 in isolation, three runs
running** (`Unit […] is blocked (inventory_block) over the requested dates` — date-coupled) **yet passed inside
the full suite**. ⚠️ **So "isolate it" is not a universal oracle**, and **7 → 5 was NOT a fix** — nothing in the
billing diff touches reservations, availability or owner units. Full reasoning: **§N.17.7**.

⚠️ **THE BASELINE IS A RANGE — 5 to 10 — NOT A NUMBER (J.quater; widened from 5–9 at SAVE v6).** **Measured
at SAVE v6 (2026-08-10, HEAD `64272cf` + docs): `1837 tests · 1828 passed · 12,042 assertions` — **9
non-green**, exactly the documented set (`BookingImportTest` ×6, `OwnerUnitDetailViewTest`, `StubViewsTest`
×2).** ⚠️ **The
upper bound moved to 10 during OWNER-10**: one run added `PublicStorefrontLandingTest` to the documented nine.
It was proven NOT to be the change (a stashed run at HEAD showed nine, a second run with the change was clean
at nine, and it passes 14/14 in isolation), and the obvious mechanism — its closing `assertDontSee('369')`
catching a generated identifier — was **measured and REJECTED** (~0.004% per slug/ULID). **The mechanism is
recorded as UNKNOWN rather than written up as a finding** (§N.4l). Next time it appears, capture the assertion
message first. It was 7 at
`9696b9c`, 9 after the 2026-08-10 date roll (J.ter), and **5 on a later full run in the same session**. Which
tests fail depends on **run order and the current date**, so the count is not well-defined:
**`BookingImportTest`** passes 11/11 in isolation and fails only in-suite (ordering); **`OwnerUnitDetailViewTest`**
and **`StubViewsTest`** are date-coupled to `DemoSeeder`'s relative dates. The rule above only works if the
number is **MEASURED each gate, not quoted from these notes** — and a suspected new failure is proven
pre-existing by **stashing and re-running**, never by name-matching.

- **`BookingImportTest` (6) — a genuine TEST-ORDERING ARTIFACT.** Fails only inside a large batch; **passes
  11/11 in isolation**, re-confirmed 2026-08-08. Order-dependent (it avoids `RefreshDatabase` and drives a real
  queue worker on the OTA import path). Not a product defect.
- **`OwnerUnitDetailViewTest` (1) — a REAL DETERMINISTIC DEFECT, not a flake.** Fails **11/12 in isolation on
  every run** (`inventory_block` over the requested dates). On the audit backlog. Confirmed identical on clean
  HEAD with each remediation pass's changes stashed, so none of those passes introduced it.

- **⚠️ ONE KNOWN TRANSIENT — do NOT chase it.** `OwnerSettingsTest` asserts `assertDontSee('4242')` (no
  fabricated stored card) and can collide with a **randomly generated IBAN** that happens to contain `4242`,
  producing a transient failure. Confirmed by isolating the test (fails 8/8 on the colliding seed) and
  re-running the suite. If the count rises unexpectedly, isolate before assuming a regression.

### ⚠️ J.quater — THE BASELINE IS NOT A SINGLE NUMBER (measured at P27, 2026-08-10)

**⚠️ Third data point (SAVE v5, 2026-08-10): `1832 tests · 1823 passed · 5 failed · 12,017 assertions` — and
the ONLY failing file was `BookingImportTest` (5).** Both date-coupled files (`OwnerUnitDetailViewTest`,
`StubViewsTest`) passed on this run. That is the **low end of the range**, measured on the same working tree
that produced 9 earlier the same day — which is the clearest possible confirmation that **the count is a
range, not a number**, and that it moves with run order and the clock, not with the work.

⚠️ **Two full-suite runs in the SAME session produced DIFFERENT failure sets** — so quoting "the baseline is 9"
is itself the stale-assertion mistake, one level up. Measured at P27 (`1809 tests · 11,896 assertions`):

| File | In isolation | In the full suite | What it is |
|---|---|---|---|
| `BookingImportTest` | **PASSES 11/11** | **fails 5** | **Ordering / test-pollution artifact** — not a date issue at all. |
| `OwnerUnitDetailViewTest` | **fails 1** | passed in run 2, failed in run 1 | Date-coupled seeder collision (`UnitUnavailableException` — *blocked (inventory_block)*). |
| `StubViewsTest` | **fails 2** | passed in run 2, failed in run 1 | Same collision (*already occupied*). |

**So the honest statement is a RANGE, not a count: 5–9 pre-existing failures**, split between an
**order-dependent** file and two **date-coupled** ones — and which ones fire depends on run order and the
current date. ⚠️ **Do not cite a fixed baseline number again.** Re-measure each gate, and *isolate* a failing
file before calling it a regression: a file that passes alone and fails in-suite is pollution, not your change.

⚠️ **This sharpens L.31.** It is not only that an asserted baseline goes stale — **a baseline expressed as a
single integer was never well-defined here.**

### ⚠️ J.ter — THE BASELINE MOVED 7 → 9 ON 2026-08-10 (a DATE-ROLL collision, NOT a regression)

**During the design-parity session of 2026-08-09/10 the clock rolled past midnight, and `StubViewsTest`
began failing 2 of 3** — taking the suite from the documented **7** non-green to **9**.

- **Proven pre-existing / not caused by the gate that found it** (P26 Channels): the two failures reproduce
  on **clean HEAD with the gate's changes stashed**, and that gate touched only a Blade label and a test file.
- **The cause:** `StubViewsTest` runs `$this->seed(DemoSeeder::class)` — whose reservations are seeded
  **relative to "today"** — and then books into `$s['units'][0]` itself. Once the date advanced, a seeded stay
  overlapped the range the test books, so `AssignmentService` correctly raised
  **`UnitUnavailableException` — "already occupied over the requested dates"**. ⚠️ **The engine is behaving
  correctly; the TEST is date-coupled to the seeder.**
- **Not fixed here** — it is seeder/test date-coupling, outside a presentation gate, and papering over it
  could mask a genuine collision. **The honest fix is to decouple the test's booking window from the demo
  seeder's relative dates** (pin a clock, or book outside the seeded span).
- ⚠️ **THE BASELINE IS THEREFORE 9 UNTIL THIS IS FIXED**: `BookingImportTest` 6 · `OwnerUnitDetailViewTest` 1 ·
  **`StubViewsTest` 2**. Re-measure it each gate rather than quoting this number (**L.31**).

### ✅ J.bis — THE 8th NON-GREEN: found, understood, and CLOSED (2026-08-09, `9696b9c`)

**A full run at `ee0121a` returned `1795 / 1803 · 11,857` — EIGHT non-green, not the seven every note asserted.**
The documented seven were unmoved (`BookingImportTest` 6 · `OwnerUnitDetailViewTest` 1). **The eighth was
introduced by the design-parity pass itself and is now fixed. The baseline is back to 7.**

**What it was.** `DesignTokenContrastTest › it declares the AA values in every shell` (operator shell) —
*"Expecting … not to contain `rgb(138, 128, 121)`."* **`3bf9c02` (design-parity P8, Housekeeping)** promoted the
five housekeeping status colours from raw hexes in the view to shared `--vy-hk-*` tokens, and one of them —
**`--vy-hk-ooo: rgb(138, 128, 121)`** — is the exact literal the test forbade.

⚠️ **CORRECTION TO THIS FILE'S OWN PRIOR ENTRY:** `rgb(138,128,121)` is the pre-AA **`--vy-muted`** original,
**not `--vy-faint`** (the test's own header records `--vy-muted` 3.77 and `--vy-faint` `rgb(166,154,146)` 2.68).
The earlier note here, in `DECISIONS.md` and in memory said "faint" and was wrong. Corrected in all three.

**Why it was NOT an AA regression — verified, not assumed.** `--vy-hk-ooo` is consumed in exactly ONE place:
`housekeeping-board.blade.php:110`, as `'dot' => 'var(--vy-hk-ooo)'`, painted as the **`background` of a 9px
`.vy-hk-dot`** (the rule sets only width/height/border-radius — no `color`). It is **never text**, and every dot
is printed beside its status **word**, so **1.4.1 holds and the 4.5:1 text bar (1.4.3) does not apply to it at
all**. The failure was a **whole-file substring match firing on a decorative fill** — broader than the guard's
own stated intent, which is about *secondary TEXT reverting to an unreadable grey*.

**The fix — option (a), the assertion tightened to what it actually guards (`tests/Feature/DesignTokenContrastTest.php`).**
The bare `not->toContain(...)` became **two precise guards per failing triple**: it may not appear **as the
`--vy-muted` / `--vy-faint` token value**, and it may not appear **as a text `color:`** anywhere in the shell.
⚠️ The `color:` match uses a **`(?<![-\w])` lookbehind** so `background-color:` / `border-color:` /
`outline-color:` — which are decorative — do not false-positive; that is the same trap the P14 accent sweep
recorded (a naive replace matched property names *ending* in `color:`).

⚠️ **Option (b) — re-picking `--vy-hk-ooo` — was considered and REJECTED:** it would change a *correct design
value* to satisfy an *over-broad test*, which is backwards, and the warm-neutral grey is the right colour for an
"Out of order" dot. **Nothing was baselined, suppressed or deleted.**

**The tightened guard was PROVEN still live — four ways, each run and observed, not reasoned about:**

| Mutation | Required | Observed |
|---|---|---|
| `--vy-muted` reverted to `rgb(138, 128, 121)` | RED | **FAILED** (line 79) |
| `--vy-faint` reverted to `rgb(166, 154, 146)` | RED | **FAILED** (line 80) |
| a pre-AA grey hardcoded as a text `color:` | RED | **FAILED** (line 102) |
| the same greys as `background-color:` + `border-color:` | PASS | **PASSED** — the lookbehind, not permissiveness |

**The net result is a STRICTLY STRONGER guard than before:** it still catches both token regressions, it now
*also* catches a pre-AA grey hardcoded as text (which the old check caught only incidentally), and it no longer
fires on decorative fills.

⚠️ **THE REAL LESSON — it survived SEVEN subsequent gates (P9→P15) unnoticed.** That is exactly the failure mode
**L.18** ("know the baseline non-green count") exists to catch, and it was missed because the count was
*asserted from notes* rather than *measured*. **An asserted baseline goes stale exactly like an asserted
finding: RE-MEASURE it each gate.** (Recorded as **L.31** in §N.8.)

Optional nicety: an org-slug → property redirect for the storefront (today `/book/{org-slug}` 404s by design;
the correct URL is the PROPERTY slug, e.g. `/book/seaside-demo`).

## (K) The marketing website (a SEPARATE track — not in this application repo)
*(Status per the founder; verify against that track's own workspace — this app repo contains no marketing-site files today. `docs/DECISIONS.md` only forward-references "the later marketing-site work" + a marketing pricing page.)* A **Claude Design marketing site** is being built using **Vyrobel's exact `--vy-*` design tokens** (same warm-glass system as the app, for visual continuity). Planned pages: **Home / Product / Features / Modules / How-it-works / Pricing / About / Contact** (+ nice-to-haves). **`login` / `signup` / `forgot-password` already exist in the app and are NOT rebuilt** by the marketing track. A future session helping refine it should reuse the app's `--vy-*` tokens and NOT duplicate the auth pages.

---

## (L) Process lessons that governed the build (and should govern all future work)
1. **VERIFY AGAINST THE CODE** — the audit, a reviewer, AND a docblock are guides, not gospel. Read the code first. (Caught: false "gaps" [whole gates already exact-match], a stale Payments docblock ["Soon" when charge was LIVE], a reviewer's own wrong premise [`PropertyContext::id()`], an understated docblock [a complete backend].)
2. **BROWSER-VERIFY THE REAL FLOW** — green tests + a correct DOM can BOTH hide a real-browser bug. Caught this program: a nested `<form>` dropping fields; `toISOString()` UTC day-roll; an `hkEnd()` URL-ordering 404; Alpine `x-show` stripping inline `display:flex`; a HARDCODED "Verified" badge lying about real state; the RAW-BLADE trap (`word@if(…)…}}@endif` → the `@if` doesn't compile but the `@endif` does → orphaned-endif 500). ⚠️ Dates from JS use LOCAL components or server strings, never `toISOString()`; status badges must reflect REAL state; centre modals via a shared class that survives `x-show`, not inline `display:flex`.
3. **ONE SHARED RULE** — no two paths that should agree may compute independently (compose-drift, receipt-vs-folio, pricing-rail-vs-server, page-vs-PDF). Reuse the certified engines/helpers.
4. **NEW PII UNDER `anonymize()`** — including PII hidden in JSON columns; wire + test-prove in the same change.
5. **PRESERVE REFUSALS + DEFERRALS + BOUNDED GUARDS + PRIVACY PERIMETERS** (#27/#29/#30/#28/#33/#39).
6. **GATE DISCIPLINE** — one surface per gate to genuine 100%; browser-verify side-by-side with the wireframe; commit + push at each clean boundary; adversarial-review money/PII/auth/security diffs; append the "why" to `DECISIONS.md`.
7. **INVESTIGATE-BEFORE-REPORTING / UNDER-REPORT CONFIDENCE** (earned across the 3 QA passes) — verify a suspected bug against the real mechanics/live file before writing it up. This disproved **2 false alarms** in Pass 2 (a modal's `undefined` action that Alpine binds on open; a regex matching dropdown text) and **1 phantom finding** in Pass 3 (a "stale storefront URL" that the live file already had correct — the 404 came from a stale recalled/system-reminder snapshot, not the code). Recalled context + system-reminder doc snapshots can be STALE vs the live file — grep the source first.


### L.bis — the lessons the AUDIT + the three remediation passes added (2026-08-07/08)
8. **VERIFY AGAINST THE CODE BEFORE ACCEPTING A FINDING — including the audit's own.** A finding's *symptom* is
   usually right and its *stated cause* often is not. Across the fix + polish passes the stated root cause was
   off roughly **seven times**, and **four items were WORSE than reported** (a money defect, a crafted-POST
   bypass, a false stated fact, a 14-screen overflow). It also caught **three bugs introduced by the fixes
   themselves**. Reproduce → read the path → then fix. When a fix and an existing test disagree, **fix the fix,
   not the test**, until proven otherwise. Before calling a failure pre-existing, confirm it fails identically
   on clean HEAD (`git stash`).
9. **A/B DUAL-METHOD: browser AND code/DB — a disagreement IS a finding.** `Mail::fake()` + `assertQueued`
   asserts a job was *enqueued*, never that it *renders*: 1,617 green tests sat on top of a 100% email-delivery
   failure. Green tests are necessary, never sufficient.
10. **SERVER-SIDE GUARDS, NOT JUST UI.** A crafted POST walks straight past a clean picker. Fixing the list
    without the guard is theatre — proven by the technician pool, where the pickers were only half the defect.
11. **SILENT-FAILURE VIGILANCE AT THE CONFIG/DEPLOY LAYER.** `env()` under `config:cache` returns NULL; a
    middleware on the `web` group never runs on an unmatched-route 404; a framework middleware running later
    silently reset a custom `TrustProxies` subclass. Four such traps were caught before shipping — each would
    have failed silently in production.
12. **NEVER "FIX" A RATIFIED REFUSAL INTO FABRICATION (#39).** The 40 RATIFIED divergences are deliberate
    honest-empty/"Soon" states. If closing a gap would require inventing data, it is a refusal — **stop, and
    correct the note instead.** Three "gaps" were closed by INVESTIGATION rather than code.
13. **DON'T DECIDE THE FOUNDER'S BUSINESS.** The billing model is a commercial decision, not a defect. Picking
    one silently would be inventing a *product* decision — worse than inventing data. **Surface it (G.1).**
14. **CORRECT YOUR OWN RECORD.** These notes previously said the billing deferral was the founder's ruling; it
    was the assistant's recommendation. Record-keeping errors get corrected in the record, not defended.

### L.ter — the lessons the PARITY SWEEP added (2026-08-08/09)
15. **PROVE EVERY REGRESSION TEST FAILS WITHOUT ITS FIX.** A test that passes without the fix guards nothing.
    This bit roughly **five times** across the sweep — a masking guard, a tautological SQL assertion, an
    unrelated guard, an overdraw guard, and a stylesheet string-match. Revert the fix, confirm RED, restore.
16. **WATCH FOR TESTS THAT DEFEND THE BUG.** **Four pre-existing tests were holding false copy in place** —
    `OwnerDocumentsViewTest` demanded "All 12 months", `OwnerHelpTest` demanded "All systems operational" and
    its own comment called that "HONEST", plus two SLA assertions. A green suite was actively defending exactly
    what the sweep found. **Correct such a test TOWARD THE TRUTH** (demand the true copy AND forbid the false)
    — never relax it, never delete it.
17. **A SWEEP'S NAMED LIST GOES STALE — VERIFY FIRST, INCLUDING AGAINST THE PROMPT.** Four of WAVE 3's five
    named items were already fixed by an earlier pass or were never gaps. And **the obvious fix repeatedly
    PRESERVED the bug**: the "bank €0 forever" wiring, agreeing-at-zero, `resolveTier()` landing on Pro because
    `plans.code` is `{tier}_{interval}`, and `WithPagination` — which would have *lost* the property scope it
    was meant to preserve. The audit, the sweep, a reviewer, a docblock, your own recalled context **and the
    prompt itself** are all guides, not gospel.
18. **KNOW THE BASELINE NON-GREEN COUNT.** 7 → 8 means you broke something. That single number caught an
    incomplete fix (`firstOrCreate()` also returns a model without schema defaults) that every static check and
    the browser had passed over. Know the known transient too (**J**) so you isolate instead of chasing.
19. **ESCALATE ACROSS THE CLASSIFICATION BOUNDARY.** Three items filed as "minor/low" actually touched auth or
    isolation. They were **pulled OUT of WAVE 3 and given the heavier pass** (adversarial, deliberately seeded
    attack state, dual-method) rather than treated as cosmetic tweaks. A finding's severity is what the code
    says it is, not the label it arrived with. ⚠️ All three were **LATENT** — `property_user` had 0 rows, no
    demo user had two orgs — so they were invisible to a demo walkthrough and needed seeded attack state.
20. **CLEAN-INCOMPLETE BEATS BROKEN-COMPLETE.** A working-but-unprovable payout filter was **reverted** rather
    than shipped on a test that could not discriminate, then re-implemented with an assertion that could.
    **Never suppress PHPStan to move on** — the `LengthAwarePaginator` `TValue` obstacle was resolved by
    changing the returned SHAPE, not by a cast, a widened type, or a baseline entry. Stop at a clean, pushed
    boundary; leave the next session a resume point, not a mess.

## (M) The founder's working pattern (for future sessions — "Subhan", solo founder/builder; see memory `user-subhan`)
Propose-before-build (STOP at ⛔ gates); **NO fabricated data** (honest empty/Soon, flag demo seeds as demo); **extend, don't duplicate** (reuse the certified engines — anti-drift); **gate-per-phase** with explicit GATE REPORTs + an append-only `DECISIONS.md`; outputs saved as files; adversarial-review passes on risky diffs; validate a design against real stakeholders before treating it as definitive. Works across macOS/Windows; uses Claude for planning/architecture/prompt-authoring while Claude Code/Codex executes. Founder owns the go-live cutovers (Stripe live-key, email DNS+token, payout-engine flip, OAuth keys, tax rates, Channex/SMS/KYC accounts).

---

# (N) 🏁 THE DESIGN-PARITY PASS — **COMPLETE FOR BOTH BUILT REALMS** (2026-08-09 → 2026-08-11 · OPERATOR 42/42 + OWNER 32/32 · GUEST 32 UNTOUCHED)

*Memories: `project-design-parity-pass` (state) · `design-parity-technique` (method + traps).
Deliverable + per-page record: **`docs/design/MISSING-BACKEND-BY-PAGE.md`**.*

> ### 🧭 READING ORDER (the sub-sections were appended per gate, so the numbering is not the reading order)
> **Status:** 🏁 OPERATOR **42/42** complete · ⬅ OWNER **18/32** in progress · GUEST **0/32**. **NEXT = owner page 19 (Booking Detail).**
>
> | Read | Section | What it gives you |
> |---|---|---|
> | 1 | **N.1–N.3** | the method, the deliverable, the 125-prototype enumeration |
> | 2 | **N.8** | ⚠️ **THE FIVE PRINCIPLES** — apply every one, every gate |
> | 3 | **N.8c** | ⚠️ **THE TWO STANDING TRAPS** — `toContain` variadic + CSS comments render |
> | 4 | **N.10** | ⚠️ the **OWNER RUNNING LESSONS** (verify-don't-assume, structural refusals, don't-seed, read-only≠writes-nothing, false probes) |
> | 5 | **N.9** | ⚠️ the **REOPENED OPERATOR ITEMS** — what must NOT be half-fixed from inside a page gate |
> | 6 | **N.4 / N.4b** | the per-gate tables: operator 42/42, then owner 1–11 |
> | 7 | **N.4c–N.4m** | the per-gate lessons in detail (each earned by one gate) |
> | 8 | **N.5–N.7, N.8b** | realm-wide tokens · surfaced figure-honesty findings · the resume queue · the long-form process lessons |


## N.1 The method (unchanged between turns)

Walk the **standalone prototypes in `docs/design/raw/`** — decoding the `__bundler` bundle **fresh** each time
(`/tmp/audit/*.decoded.html` holds decoded copies; verify byte-identity where one exists) — **ONE page per
turn**, opening the prototype and the live page **side-by-side in a real browser (Playwright MCP)**, and produce
**two strictly separated outputs**:

1. **Fix the live page's design/UI/UX to match the prototype exactly** — down to px, weight and gap.
   **Markup and styling ONLY.**
2. **LOG every piece of implied backend to `MISSING-BACKEND-BY-PAGE.md` — logged, NEVER built.**

Then **report and STOP**, and wait. Clean-incomplete beats rushed — that is why 6a/6b/6c, 7a/7b and 12a/12b were
split rather than crammed into one turn.

**Every diff is classified into exactly one of four buckets:**

| Bucket | Action |
|---|---|
| **DESIGN-GAP** | Fix now, through the `--vy-*` tokens |
| **REFUSAL-TO-PRESERVE** | **Keep the honest version.** The prototype is showing fabricated data the product deliberately refuses (#39 / sweep §5 / the 40 ratified divergences) |
| **EXTRA-WORK** | Keep. A capability the product has that the wireframe never asked for (sweep §6) — never removed "for parity" |
| **MISSING-BACKEND** | Log it; do not build it |

**The standing rules:** the prototype is the **DESIGN source of truth, NEVER the DATA source of truth** — real
data or an honest empty state, always (#39) · **never disturb the real backend** (no service, guard, engine or
computation touched to close a design gap) · match hexes by **intent through the token system**, keeping
`--vy-muted`/`--vy-faint` at their WCAG-AA darkened values · **#17 · #20 · #27 · #28 · #29 · #33 · #38 · #39
all hold** · browser-verify each page before *and* after · hold the **baseline 7 non-green** (**J**), PHPStan
L5 (0), Pint clean · **commit + push per page**.

⚠️ **The single most frequent judgement call, stated plainly: when the "gap" is the live page honestly refusing
the prototype's invented data, the gap IS the feature.** Match the layout; never the fabrication.

⚠️ **THE "HOLD THE BASELINE" RULE WAS NOT ACTUALLY HELD — see J.bis.** `3bf9c02` (P8) took the suite from 7
non-green to **8**, and seven further gates shipped on top without anyone noticing. **Re-measure the count each
gate; do not assume it.**

## N.2 The deliverable — `docs/design/MISSING-BACKEND-BY-PAGE.md`

The running, per-page **backend backlog** that will drive a **separate later backend phase**. Each page gets a
section whose table states, per item: **what the prototype shows · what the live product verifiably lacks**
(read against the code, never assumed) **· what it would need** (route/controller/service/model/migration/job,
named concretely) **· a verdict** — `REAL GAP` (build later) · `RATIFIED REFUSAL` (do NOT build; it would be
fabrication) · `ACCOUNT-GATED` (a founder go-live flip, **H**, not missing code). Recording the refusals
explicitly is deliberate: it stops the backend phase "discovering" one and building a fabrication.

## N.3 The enumeration (this — not any earlier candidate list — is the source of truth)

`docs/design/raw/` holds **125 prototypes**: **42 OPERATOR · 32 OWNER · 32 GUEST · 19 shell/email/PDF/auth**
(plus an `Archive.zip`). ⚠️ **Some are many-to-one:** `Operator Inbox` + `Messages` + `Conversation` decode onto
**one** live page, and `Property Settings` is a **second prototype of `/app/settings`** — prototype variants, not
missing surfaces. **Every one of the 42 operator prototypes maps to a real live route — no missing-surface
finding.** The full reconciliation is `MISSING-BACKEND-BY-PAGE.md` **§15 GATE A**.

## N.4 Progress — 🏁 **OPERATOR COMPLETE — 42 of 42 prototypes, 34 numbered gates** (`0aa9ede` → `edb7cce`, all pushed)

⚠️ **Count correction:** §15 GATE A records "22 covered / 20 remaining"; **GATE B then closed Refund**, **P16
Payout Run**, **P17 Billing**, **P18 Rates**, **P19 New Reservation**, **P20 Group Booking**, **P21 Owners**,
**P22 Owner Detail**, **P23 Roles**, **P24 Security**, **P25 Reviews**, **P26 Channels**, **P27
Portfolio** and **P28 Search**, so the current figure is **36 covered / 6 remaining** (36 + 6 = 42). Notes saying
"22/42 … 19 remaining" are the pre-GATE-B count and do not add up.

| Gate | Page(s) | Commit |
|---|---|---|
| P1 | Operator Dashboard | `0aa9ede` |
| — | **a11y GATE — sidebar nav to AA in BOTH shells** (`--vy-on-grad`, `--vy-on-grad-soft`) | `93247e7` |
| P2 | Operator Calendar | `114f04b` |
| P3 | Operator Insights ⚠️ (surfaced **D3-4**, §N.6) | `6e6d980` |
| — | **a11y GATE — accent-as-TEXT to AA** (`--vy-accent-text`) | `81c5d18` |
| P4 | Operator Reports | `415c28f` |
| P5 | Operator Front Desk | `9755cad` |
| P6a | Reservations list | `f60cecd` |
| P6b | Reservation detail | `1a4eea6` |
| — | **SEED GATE — a real cancelled + a real no-show, through the certified path** | `4490b42` |
| P6c | Reservation Cancelled + No-show variants | `2a997a6` |
| P7a | Guests CRM list | `6cd171d` |
| P7b | Guest Profile | `6aa7dea` |
| P8 | Housekeeping | `3bf9c02` |
| P9 | Team Schedule | `c3d72bd` |
| P10 | Inbox (Messages + Conversation + Inbox → one page) | `1fe2233` |
| P11 | Maintenance board | `b49d98d` |
| P12a/b | Work-order detail (**at parity**) + Maintenance Costs | `5403bd2` |
| P12 | Work-order detail (at parity) + Maintenance Costs onto the shared `<x-charts.bars>` | `5403bd2` |
| P13 | Maintenance Settings | `f3f8c1d` |
| P14 | Operator Settings (all 13 blocks; + Property Settings, the 2nd prototype of the same page) | `279ea80` |
| P15 | **GATE A** operator coverage sweep + **GATE B** Refund (**verified AT PARITY, 0 code**) | `ee0121a` |
| — | **TEST GATE — the 8th non-green closed** (J.bis; a contrast guard tightened to its intent, proven still live) | `9696b9c` |
| P16 | **Operator Payout Run** ⚠️ real owner money (4 design fixes · 6 backend items · **D16-7** a money-visibility finding) | `4458f52` |
| P17 | **Operator Billing** ⚠️ the OPEN billing decision (6 design fixes incl. a pre-existing modal bug · 5 backend items · a **G.1** observation) | `3f86270` |
| P18 | **Operator Rates & availability** ⚠️ what guests are charged (2 design fixes · 6 backend items · a strengthened #30 gate test) | `252cb67` |
| P19 | **Operator New Reservation** ⚠️ money + inventory (3 design fixes · 4 backend items · create NEVER submitted, counts proven unchanged) | `de83bd4` |
| P20 | **Operator Group Booking** ⚠️ money + inventory, multi-unit (2 design fixes incl. the **shared `.vy-wiz-*` stepper consolidation** · 3 backend items · block NEVER submitted) | `1b435a7` |
| P21 | **Operator Owners (LIST)** ⚠️ owner PII + isolation (2 design fixes · 3 backend items · **a real LIST-isolation coverage gap closed**; ⚠️ its €0.00 is a REAL computed zero and was NOT changed) | `19f8ec4` |
| P22 | **Operator Owner DETAIL** ⚠️ deepest owner PII + the #6 guarded proposal (1 design fix · 4 backend items; ⚠️ **the guardrail silence is CORRECT — no `RateGuardrail` binds this owner**) — **Owners area COMPLETE** | `e4e3dfa` |
| P23 | **Operator Roles & permissions** ⚠️ RBAC (1 design fix · 3 backend items · **the matrix verified against the real pivot — 0 fabricated grants** · **a self-escalation test gap closed + proven**) | `5446885` |
| P24 | **Operator Security** ⚠️ auth (⚠️ **a hardcoded “Protected” badge that lied with 2FA OFF — now derived + tested** · 2 a11y fixes · 2 backend items) — **Settings' deferred pair now complete (Billing §17 + Security)** | `b9f26d0` |
| P25 | **Operator Reviews** (⚠️ **the page had never been tokenised — 3 measured AA failures incl. the STAR RATING at 3.08; now 0** · the rating gained its number · 4 backend items) | `101e1e6` |
| P26 | **Operator Channels** ⚠️ the OTA-claim ROOT (1 over-claim fixed: “Channels mapped”→“configured” + a scoped guard · 5 backend items, **4 refusals — almost the whole prototype**; ⚠️ live page NOT browsable: Pro-gated, plan deliberately unchanged) | `638e67a` |
| P27 | **Operator Portfolio** ⚠️ the multi-property ROLLUP (#38): band/stats/occupancy-rail/chips restyled · the never-rendered **arrivals** chip + **“Full report ›”** (gate was already computed) now rendered · the design's **“—” for an average over zero** (computed zeros kept) · **AA 4.43→5.18** · ⚠️ found the **#17 test was VACUOUS** — replaced + mutation-proven; #38 shown to hold across **two independent layers**; 5 backend items | P27 |
| P28 | **Operator Search** ⚠️ the cross-entity PII surface: query bar / filter CHIP LINKS (#20, zero wire:click) / results header / tiled rows · **the design's guest-row EMAIL + city REFUSED (#28)** — pinned by 3 mutation-proven tests · `.vys-badge` **4.41→AA** (accent-as-text on a Livewire page — the predictor isn't the whole story) · foreign drill-down→404 · 6 backend items | P28 |
| P29 | **Operator Notifications** — toolbar/segmented tabs/category chips restyled · ⚠️ **WCAG 1.4.1 fixed: unread rode on colour alone** (`title` on a non-interactive span isn't announced) → `aria-hidden` dot + `.vy-sr-only` text, mutation-proven · ⚠️ **the two prototypes CONTRADICT each other on the icon shape** (operator circle vs owner square) → kept the ONE shared tile, documented · SMS-in-footer + fabricated rows/categories refused · **nothing marked read (242/242/0 unchanged)** · 5 backend items | P29 |
| P30 | **Operator Notification Preferences** ⚠️ a CLAIMS surface — two-column layout + breadcrumb + top Save · ⚠️ **WCAG 2.1.1 (Level A) FIXED: the page's ONLY real controls were unoperable by keyboard** (`display:none` on the checkbox — `focus()` left activeElement on BODY) · 1.4.1 no-provider cells given text · **#30 the new crumb is gated** (Settings needs `properties.manage`; this page has NO gate) · Push-as-"This device·Chrome·On" + SMS-as-verified-number REFUSED · **nothing saved (0 rows, checksum identical)** · 4 backend items | P30 |
| P31 | **Operator Email Templates** ⚠️ email REALLY sends — card grid + breadcrumb + switch + the never-linked **Preview-emails gallery** · **Active pill 4.41→6.23** (controller raw-hex risk realised) · merge vars verified REAL · **preview source verified: real reservation but NAME-only, 0 emails/phones — not a #28 widening** · per-card **engagement stats REFUSED** (no tracking exists) + New-template/Draft/winback/Email+SMS refused · **nothing saved, no mail sent** · 5 backend items | P31 |
| P32 | **Operator Email Editor** ⚠️ highest-consequence read-only surface — action bar via HTML5 `form=` (nothing rewired) + switch toggles (focusable, not display:none) · **contrast 5→0** (the Active pill was the SAME raw-hex pill as P31 — one concept written twice) · ⚠️ **preview clipped at 1000px** (breakpoint 960→1180; Reference value was hidden) · ⚠️ **found: the preview has TWO sample sources** — subject shows `VY-2043`, body `R-F7ERPHS4` for one booking (logged, preview-only) · **nothing saved, no mail sent** · 4 backend items | P32 |
| P33 | **Operator Profile** (self-only) — hero/switches/segmented date format · ⚠️ **REFUSED the design's sign-in LOCATION** ("Faro, PT" — NO geolocation exists anywhere; it reads as security evidence) + "password changed 3 months ago" (no column) · ⚠️ **fixed a label that read the OPPOSITE of its control** ("Interface motion" drove `reduce_motion`) · contrast **2→0** via the SHARED `.vy-tag.tone-confirmed` — **one edit fixed 5 views** (vs P32's duplicated pill) · ⚠️ my first geolocation guard was **VACUOUS** (no sessions row) — strengthened + mutation-proven · **nothing saved** · 4 backend items | P33 |
| P34 | 🏁 **Operator Schedule PDF — THE LAST OPERATOR PAGE (42/42)**. A DOCUMENT: verification over restyle. **Shared `RosterWeek` verified** (4 callers, 1 builder, no 5th computation) · **SCREEN==PDF proven cell-by-cell** (10 shifts / 80h reconcile) · #38 scope + #30 gate mutation-proven · ⚠️ **the document carried the PRE-AA palette** — `#A69A92` **2.74** on the per-cell shift TIMES; now 5.79 · added the **real generated-at** stamp + a **print-safe legend** (black-and-white safe) · 4 backend items, 2 of them **STALE template claims** (open shifts + publish now exist) | P34 |

## N.5 The two REALM-WIDE tokens + the shared consolidations + the seeder gate

⚠️ **STILL WANTED — a shared `<x-status-pill>`.** The Active/Off pill was **hand-written twice** (the Email
Templates list and the Email Editor) and therefore **failed AA twice**, fixed separately at **P31** and
**P32**. By contrast `.vy-tag.tone-confirmed` is a shared class, so **one edit at P33 fixed five views**. This
is principle (3) in N.8, and the pill is the outstanding instance of it.

- **`--vy-on-grad` / `--vy-on-grad-soft`** (`93247e7`) — the sidebar nav labels and items hardcoded the
  prototype's greys (`#9C9089` / `#8A8079`) and measured **1.82 : 1 / 2.23 : 1** against the **real sampled
  gradient pixel**. Fixed realm-wide in **both** shells: now **4.74 / 4.93** operator and **4.71 / 4.93** owner
  (active 4.62, hover 6.52). ⚠️ `--vy-muted` cannot serve both the gradient and the glass — hence a token pair.
  This also closes the 2026-08-07 audit's gradient-background exclusion.
- **`--vy-accent-text: rgb(176,56,8)`** (`81c5d18`) — accent used **as TEXT on glass** measured **4.09–4.46**
  (the prototype's own `#B4400F` was 4.48 — also under AA). A new token was pointed at the **41 rules where the
  accent is text with no background of its own**; the **31 chip/tag rules that carry their own tint were left
  alone** (measured 4.61, passing), and **`--vy-accent` itself is UNTOUCHED**, so every gradient, button, border
  and icon fill is byte-identical. After: **4.92–5.47** operator, **5.45** owner.
  ⚠️ **248 inline `color: var(--vy-accent)` declarations REMAIN** across `resources/views/`, concentrated in the
  owner realm (bookings 13 · owner-settings 9 · help 9 · messages 8 · welcome 7 · unit-detail 7). **A blanket
  replace would be WRONG** — many sit on tinted backgrounds where the accent IS the correct foreground. **Each
  site needs its real composited background measured. This deserves its own pass.** (Also: a naive replace
  matches property names ending in `color:` — use a lookbehind; `border-color: var(--vy-accent)` stays.)
- **Anti-drift consolidations made during the pass:** the integrated search pill was promoted to **ONE opt-in
  `.vy-input-group--pill`** and the `.vy-res` / `.vy-gst` copies **deleted** (this was the *third* page to want
  it); and Maintenance Costs' hand-rolled month chart moved onto the shared **`<x-charts.bars>`**.
- **The SEED GATE (`4490b42`)** — the Cancelled and No-show variant prototypes had no live state to match, so a
  **real cancelled and a real no-show reservation were seeded THROUGH the certified services** (never a raw
  status write), so folio, availability spine, audit trail and **#29** are genuinely consistent. Demo goes
  **40 → 42 reservations**; the list and its GET-link pagination were re-confirmed still property-scoped after.

## N.6 ⚠️ SURFACED figure-honesty findings (recorded, deliberately NOT fixed — they belong to the BACKEND phase)

**The register:** **D3-4** (Insights LY occupancy ÷ spine coverage — below) · **D16-7** (a payout in
`processing` appears in **none** of the Payout-Run KPIs, so in-flight money is invisible the moment Stripe goes
live) · **D2-2** (per-channel figures have no channel attribution — **0 of 42 reservations carry a
`channel_id`**; the Channex root) · the **trend-without-history** class (a delta with no comparable prior
period — refused on Insights, Portfolio and Email Templates). All per-page D-items live in
`docs/design/MISSING-BACKEND-BY-PAGE.md`; each carries what's shown / what's verifiably missing / what it would
need / a verdict.

### D3-4 in detail

**Operator Insights' "Last year" occupancy is divided by the availability spine's COVERAGE, not by the month.**
The `availability` table holds **9 rows in all of 2025** (earliest `2025-08-21`); September 2025 has **3 rows for
a 30-day month**, each `total=2, sold=1` — so the chart prints **"Last year 50%"** under a label that reads as
*the month's* occupancy. Four LY months (Aug/Sep/Oct/Nov) read exactly 50% for this reason, **and it feeds the
KPI deltas** (the `−40 pts` occupancy delta is measured against that same thin window).

- **Why it was not fixed here:** the figure comes out of `PerformanceMetrics::monthlyTrendForProperties` — **a
  computation**, and this pass is design-only. Recorded, not touched.
- **What it needs (backend phase):** a **coverage guard** on the LY series — compute the spine's day-coverage
  per comparison window and omit the LY bar **and** its delta below a threshold, exactly as the product already
  omits vs-LY where prior-year data is **absent**. The honest states already exist (`has_ly`, `enough`); this
  extends them from *absent* coverage to *partial* coverage.
- ⚠️ **It is NOT merely a demo artifact.** The thin 2025 spine comes from `DemoSeeder::seedAnalyticsHistory`,
  but **the same hole opens for any tenant in its first year — which is every new tenant.**
- It is the **WAVE-2 class exactly**: *a figure under a label that means something else.*

## N.4b OWNER realm progress — 🏁 **COMPLETE — 32 of 32** · `6d974d7` → O32 (`a2d5a9e`→) · ⬅ **NEXT = the Owner Invite Email, then GUEST (32)** · ⚠️ **O26 contrast FIX + the PDF sweep + DO10-4 remain scoped + unstarted**

**Every gate below is design-matched, browser-verified, backend-logged, baseline-held and pushed.** The
one-line summary of what the owner realm has cost and returned so far: **three 🔴 real defects** (a chart
misreporting money, a wizard submittable by Enter from step 1, a money-affecting calendar unusable by
keyboard), **one realm-wide a11y item CLOSED** (P29 colour-only unread, fixed once in the shared bell),
**one always-passing assertion class found and repaired across 36 test files**, and **six brief premises
corrected**. Detail per gate:


| Gate | Page(s) | Commit |
|---|---|---|
| O1 | **Owner Dashboard** (`/owner`) — ⚠️ the earnings chart was **misreporting real money twice**: the tallest bar was silently compressed by `flex-shrink` (September asked 150px, rendered 126px — the current month understated 16%) and the **goal line did not share the bars' baseline** (a goal equal to the best month drew *below* it; ~25% error mid-range). Both fixed via the wireframe's own 78% headroom + a label-block offset · the `max(4, …)` floor that draws a stub for a genuine **€0** month replaced with the shared `<x-charts.bars>` zero rule · value-graduated bar wash · goal **pill on the line** · attention chips capped at 3 + the dashed **"+N more"** · **the guest NAME was truncated on 3 of 4 upcoming stays** by the honest "Not yet attributed" (slot resized, string preserved) · **AA 23 → 5** (real-pixel measured) · a dead duplicate `.vy-delta--up/--down` pair deleted · ⚠️ **realm-wide finding SURFACED, not fixed: white-on-accent-gradient fails AA (3.98–4.02) across BOTH shells** — needs its own a11y gate · ⚠️⚠️ **THREE VACUOUS GUARDS FOUND AND REPAIRED** (below) | O1 |
| O2 | **Owner Statement** (`/owner/statements/{id}`) — the realm's MONEY DOCUMENT, rendering twice (screen + PDF). ⚠️ **The PDF still carried the PRE-AA greys** — `#8A8079` **3.86** on white was the `.neg` class, i.e. **the commission/deduction figures**, and `#A69A92` **2.74** the footer → 6.98 / 5.79 (the dark band's greys correctly left alone at 8.21 / 7.05) · ⚠️ **a regression I introduced at O1**: `--vy-amber-text` was tuned to ONE sample, measured 4.45–4.50 here and **1.87 on the dark payout rail** (darkening is backwards on dark; the rail was ALREADY failing at 2.72 pre-O1, and **all three tones failed** — paid 1.77, draft 1.65) → token re-tuned to the worst sample + a **dark-context trio** `--vy-amber/green/muted-on-dark` (5.10/5.07/6.00) · documents rebuilt to icon rows · history rows to the design's cards (**kept a real `<table>`**, scoped to `.vy-st-hist` not the shared `.vy-table`) · a genuinely **0% occupancy month now draws nothing** + the series gained a real accessible name (its figures lived only in `title` on an `aria-hidden` container) · **contrast 8 → 3** · ⚠️ **restyling Export PDF to the design's accent pill ADDED a 4th instance of the white-on-gradient family — reverted rather than widen a known AA failure** · **SCREEN == PDF proven figure-by-figure**; a stale "DEFERRED/stubbed" docblock corrected | O2 |
| O3 | **Owner Payouts** (`/owner/payouts`) — ⚠️⚠️ **D16-7 RECURS ON THE OWNER'S OWN PAYOUTS PAGE**: `OwnerPayoutService::pay()` writes **`processing`** on the real-transfer path, but all three tiles are built from `status==='paid'` and `status==='pending'` — **a payout genuinely in flight appears in NO tile** (`held` likewise, though it drives the banner). Verified by construction; **logged, not fixed** (a computation) · ⚠️ **a second real defect: the payout history has no deterministic order** — `payouts()` sorts by `created_at` DESC alone and **all six demo rows share one timestamp** (the engine creates a run in a single pass, so ties are the NORMAL case), rendering oldest-first; needs a ULID tiebreaker in the ONE shared read · ⚠️ **`--vy-green-text` re-tuned — the SAME single-sample mistake as the amber one gate earlier** (4.31–4.44 on the warmer payout rows → 5.86–6.04; Statement re-measured, no regression) · **contrast 21 → 6** (pre-AA `#9C9089` prefs labels 2.66 · 8 accent-as-text sites · the shared `.vy-table` header at 4.47) · summary line + Pending/preferences state pills from REAL state · projections / retry-ETA / verification-hold **REFUSED** · **READ-ONLY honoured — no bank update, no preference row, statuses unchanged** · stale docblock (`status ∈ …` omitted `processing`/`held` — D16-7's blind spot, written down) corrected | O3 |
| O4 | **Owner Rates** (`/owner/rates`) — the GUARDED sibling. ⚠️ **THE GATE BRIEF'S PREMISE WAS INVERTED and the code + the wireframe agree with each other, not with it**: `RateProposalService::propose()` is the **OPERATOR** side (the guardrail constrains what the MANAGER may propose — floor/ceiling reject, ±tolerance auto-applies); the owner APPROVES/DECLINES/COUNTERS, sets the guardrail, and prices their own units by **DIRECT apply** (`owner.rates.apply` → certified `OwnerRateService`). The prototype's own header says so. · ⚠️ **Fixture truth: 0 `RateGuardrail` rows in the whole DB and 0 `rate_proposals`** — so live can only show the UNGUARDED case (guarded enforcement is covered non-vacuously by `RateProposalTest`, which seeds real rows; none seeded here — that would be mutating demo data for a screenshot). **The honesty check PASSES**: with no row `propose()` leaves every proposal `pending`, which is exactly what the ±0% card says — no "within policy" badge, no fabricated limit · **wireframe's TWO COLUMNS restored** (the guardrail sat below the fold of what it governs) + **a 1537px-vs-1440px horizontal overflow the restructure exposed** (`1fr` → `minmax(0,1fr)`) · **contrast 21 → 3, the realm's worst** — the page hardcoded the pre-AA greys (`#A69A92` ×10, `#8A8079` ×7 at **1.89–3.74**) on the copy explaining the guardrail; + 2 SHELL fixes (`.vy-topbar-sub` sits on the GRADIENT → `--vy-on-grad`; active nav 4.49 → `--vy-accent-text`), Payouts re-measured, no regression · **2 real keyboard defects**: `outline:none` with no `:focus` left the floor/ceiling + counter inputs with NO focus indicator, and the auto-approve **slider had no accessible name** · comp-set/market + Peak tier **REFUSED** · **READ-ONLY honoured — 0 proposals, 0 guardrails, no rate written** · stale docblock ("Guardrails … unmodelled") corrected | O4 |
| O5 | **Owner My Units** (`/owner/units`) — ⚠️ **THE OWNER-4-LOGGED FOCUS-RING DEFECT IS CLOSED AND CONFIRMED LIVE**: `.vy-mu-rate-input` had `outline:none` with no `:focus`, so the inline nightly-rate editor had **no focus indicator at all**; **two more instances of the same pattern found here** (weekend/min-stay + the message textarea) · **3 pricing inputs had no accessible name** → per-unit `aria-label`s (65 controls with both editors open: 0/0/0) · ⚠️ **a REAL figure-honesty finding, WAVE-2 class**: the tile says *"Earned this month €541.60 · Net · August 2026"* while Statements says *"August 2026 €721.60"* — **both correct**, different bases (all ledger lines by `service_date` vs the 3 lines the statement CLAIMED); the €180 gap is a real **unstatemented expense** dated 9 Aug. **Logged, not re-worded** — the basis is a product call · **contrast 29 → 10** (all 10 the gradient family): **the three pre-AA greys logged at Owner-1 for THIS page closed** (`.vy-mu-stat-label`/`-uplabel`/`-calhead`, 2.28–2.78) + ⚠️ **the booked calendar DAY NUMBERS at 3.57** (they are the dates) → 5.47–9.07 · **the dark "Best performer" panel PASSED** (the Owner-2 mirror holds) · bulk **"Approve all" REFUSED** (one click over a rate change + a €340 expense) · **READ-ONLY honoured — units/rate_calendar `updated_at` still the seed time, `owner_attention_states` still empty** | O5 |
| O6 | **Owner Notifications** (`/owner/notifications`) — 🏁 **THE OPERATOR REALM'S LAST OPEN P29 ITEM IS CLOSED.** The colour-only unread state (WCAG 1.4.1) was still live on BOTH logged surfaces: the owner feed page and the **SHARED `bell.blade.php`** (both shells). Fixed with the GATE-29 pattern (`aria-hidden` dot + `.vy-sr-only` word, never a `title=` on a non-interactive span) — **the shared partial fixed ONCE**, reaching both bells; ✓ controls named. **Mutation-proven 3 ways** · ⚠️ **A third defect surfaced: `.vy-sr-only` was defined ONLY in the operator layout**, so the shared partial's hidden text was **visible in the owner shell** — *a shared partial cannot depend on a class only one shell defines* · ⚠️ **PREMISE CORRECTION: `Owner Notifications.html` is the PREFERENCES page, not the feed** (Settings/Notifications, channels, per-event Email/SMS matrix) → it belongs to `/owner/notification-preferences` and **the live feed has NO prototype**; also the feed is **self-scoped by query root**, not by a 404 · **contrast 6 → 3** (both non-family failures were text-on-the-page-GRADIENT again — the footer link needed `--vy-accent-tint-text` since even `--vy-accent-text` reads only 3.77 there) · ⚠️ **Blade compiles component tags inside CSS comments** — naming the bell with `<x-…>` syntax in a comment 500'd every owner page · **242 notifications across 5 users, owner sees exactly their own 6**; **READ-ONLY honoured (still 6 of 6 unread)** | O6 |
| O7 | **Owner Notification Preferences** (`/owner/notification-preferences`) — the CLAIMS surface, using the prototype O6 identified. ⚠️ **Verified against the code: `CHANNELS = ['in_app','email']`**, so **SMS and PUSH are STRUCTURALLY UNSETTABLE** (no row can exist — 0 verified) and the page shows SMS honestly gated with no submitting input · ⚠️ **BRIEF CORRECTION: in-app is NOT a toggle here** — the wireframe's grid is Email+SMS and the feed always records, so in-app is **preserved via hidden inputs** which are **load-bearing** (`save()` defaults a missing key to FALSE, so removing them would silently disable every in-app notification) · **storage verified: a typed `(user,event,channel)` pivot via `updateOrCreate`, no JSON blob, no wholesale replace** — the WAVE-1 merge hazard is structurally absent; `always_on` forced at SAVE time · ⚠️ **the per-event SMS cells announced via `aria-label` on a NON-INTERACTIVE span** (the P29 pattern) → real `.vy-sr-only` text, matching the operator page — **this fix depended on O6 putting `.vy-sr-only` in the owner shell** · ⚠️ **the SMS column header measured 1.63, the worst on the page**, and said only "SMS" → `--vy-muted` + "· not set up" · **contrast 17 → 2** (pre-AA `#A69A92`×9 + `#8A8079`×5 at 1.93–3.32) · ⚠️ **`--vy-on-grad` re-tuned shell-wide — the THIRD token to need it**: set from ONE gradient pixel at O4 (4.68), it read **4.42** on a lighter point of the same gradient. **A token on a gradient has a RANGE of backgrounds** · **GATE-30 defect NOT present** (real buttons; the 15 `display:none` are hidden inputs) · **READ-ONLY honoured — still 0 preference rows** | O7 |
| O8 | **Owner Insights** (`/owner/insights`) — ⚠️ **THE D3-4 PREMISE IS INVERTED FOR THIS PAGE**: D3-4 is an OPERATOR-path defect (`forProperties` divides by the availability SPINE's coverage); the OWNER path (`forOwnerUnits`) divides by **units × calendar days** and is **structurally immune** — and the page **SHOWS its denominator** ("57 of 730 unit-nights" = 2×365). Verified, recorded so the backend phase doesn't "fix" a correct figure. The vs-LY deltas are therefore honest, and attach only where the prior year had occupancy · ⚠️ **REAL DEFECT: four genuinely-0% months were drawing bars** (`max(2,pct)`; Nov's own tooltip read "0% (on the books)" beside a drawn bar) — **third page with this floor**; fixed on all three floors here · 🏁 **THE `--vy-on-grad` CHURN IS RESOLVED BY MEASUREMENT** — sampled **7,502 background pixels behind the elements that actually use it**; darkest point rgb(239,174,135); ⚠️ **premise correction: it is DARK text on the PAGE gradient, so the worst case is the DARKEST point**; `--vy-on-grad` already cleared (5.15, unchanged) but **`--vy-on-grad-soft` was FAILING at 4.26 and had never been re-tuned** → 4.63 · ⚠️ **CROSS-REALM FINDING: the OPERATOR shell's pair FAILS at 2.95 / 2.77** (its darkest point is rgb(216,129,89); gate `93247e7` measured ONE pixel) — **logged for its own gate**, not changed inside an owner gate · delta arrows gained a WORD; contrast → only the gradient family | O8 |
| O9 | **Owner Add Unit** (`/owner/add-unit`) — ⚠️ **THE BRIEF'S PREMISE CORRECTED AGAIN: this is NOT a creation flow.** An owner cannot create a unit anywhere in the product; the wizard files a **`unit_requests`** row and the OPERATOR creates the real unit as their own action. The page's own copy says so and the service is explicit ("It creates NO unit") · 🔴 **REAL DEFECT FOUND AND FIXED — the wizard was submittable from STEP 1 by pressing Enter.** Implicit submission fires against the step-4 button *even while step 4 is hidden*, and server-side it **passes** (only `requested_name` + `requested_kind` are required, and the kind is pre-selected) — so one Enter on step 1 filed a real request on the operator's desk with four silent defaults, no rate and no photos. **Keyboard users are the ones who reach for Enter.** Browser-proven BOTH ways (step 1: 1 submit → **0**; step 4: still **1**), regression test **mutation-proven** · ⚠️ **a probe of mine misread it once** — `querySelector('[x-data]')` matched the SHELL's Alpine root (the bell popover), so the fix looked like it over-blocked: *verify the probe before the page* · **13 unnamed controls → 0**: 5 inputs were named by **placeholder only** (`.vy-au-flabel` was a `<div>`, never associated) → real `<label for>`, and **8 icon-only steppers had no name at all** → `aria-label` · chip selection was **colour-only and unexposed** (14 chips) → `aria-pressed` + named groups · **`.vy-au-money`'s missing focus ring — the O4-logged defect FOR THIS PAGE — closed**, plus a second instance: the file input is `opacity:0` over its tile so **its own ring is invisible too** → `.vy-au-drop:focus-within` · the error block showed **only the first** message → `role="alert"` + all of them · **contrast 15 → 6, icons 2 → 0**; ⚠️ **the failing labels were `--vy-faint`/`--vy-muted` used on the PAGE gradient — the fix was to reach for the EXISTING `--vy-on-grad-soft`, not to retune a token 8 closed pages depend on** · REFUSED: the prototype's **town chips** (no town catalogue exists — `location` is a free string), its hardcoded **15%** (live reads the real agreement, 18%), any earnings projection, any client-side "looks good ✓" · **READ-ONLY honoured after a full four-step walk incl. Enter on steps 1 and 4 — `unit_requests` still 0, units/room_types/media/rate_calendar unchanged, `units.updated_at` still the seed time** | O9 |
| O10 | **Owner Unit Detail** (`/owner/units/{unit}`) — 🔴 **REAL DEFECT: the availability calendar was MOUSE-ONLY.** 31 of the page's 36 interactive elements were keyboard-unreachable — every clickable day was a `<div>` with an Alpine `@click`, no `tabindex`, no `role`, no key handler — so *tap an open day to block it* (a REAL write through the certified `OwnerBlockService`, taking inventory off-sale) could not be done without a mouse. **GATE-30's class, at scale, on a money-affecting control.** Fixed by making the interactive cells real `<button>`s (focus, Enter/Space and a ring, no JS); **browser-proven by keyboard with 0 submissions fired**, regression test mutation-proven · a day's STATE also reached AT only as a fill colour (the interactive cells' `title` said "Tap for detail", not *booked*) → each cell now names its date + state; recorded honestly that this closes **4.1.2**, while for the visual **1.4.1** axis the three states already differ by luminance, not hue alone · ⚠️ **the `max(2,pct)` floor AGAIN — the FOURTH page** (7 genuinely-zero months drew stubs) · **the status block was the prototype's toggle SWITCH** — live had a pill + labelled button that duplicated the header's status and squeezed the copy to five lines; restored as a real gated POST with `role="switch"` and the state still in words · **contrast 25 → 6**: ★ **rating glyphs at 1.85 ×4 — the stars ARE the score** → `--vy-amber-text` 6.07; the "Active" pill at **3.20** because the design's tint assumes a CARD but the pill sits on the page gradient → the same tint composited over a near-white plate (4.63, tint and text colour untouched); 6 accent-as-text sites; `--vy-faint` on the gradient → `--vy-on-grad-soft` · **#42 measured in-browser: own units 200, an operator-owned unit in the SAME org 404, another property's unit 404, nonsense 404** · ⚠️ **the actions here are DIRECT owner writes, not requests** (pause/resume, block, price) — the Owner-9 pattern does NOT generalise; #6 holds as Owner-4 established (direct because the room type is exclusively owned) · stale comment ("weekend uplift NOT offered") corrected · REFUSED: the prototype's named-guest **message panel**, its **cleaner identity + "Schedule a cleaning"**, "Direct bookings saved you €X", a per-unit payout timeline, channel sync · **READ-ONLY honoured — `units.updated_at` still the seed time, blocks still 2, rates untouched** | O10 |
| O11 | **Owner Statement PDF** (`/owner/statements/{statement}/pdf`) — ⚠️ **PREMISE CORRECTION: O2 already covered this document** (it decoded this prototype, measured the generated PDF's content stream and fixed the pre-AA greys). So this was a layout diff + **re-verification — and O2's fix HELD**, now proven and, for the first time, GUARDED · 🔴 **the find: writing that guard was IMPOSSIBLE, because the comment explaining the fix was a CSS comment — and a CSS comment is RENDERED** (the O6 lesson in a new place), so `#8A8079`/`#A69A92` shipped inside every generated statement; moved to a Blade comment (stripped at compile) → the guard now exists and is mutation-proven · **footer fixed**: dompdf broke the date across lines and hyphenated the contact address mid-domain ("billing@vyrobel-/demo.test") → explicit split + `nowrap`, verified in the regenerated PDF · ⚠️ **contrast ON PAPER: the document's own ink is AA-clean** (5.18–15.40 on white) — **four apparent failures were MY PROBE'S ERROR** (white text + two greys that sit on DARK PLATES, scored against paper; re-scored on their real plates: 5.18/15.76/8.21/7.05, matching the template's own note). The only genuine on-white failure is the shared **PdfService watermark 2.70** — the logged realm-wide item, out of scope · **#17 verified from the PDF's OWN content stream**: 1,080.00 − 194.40 (real 18%) = 885.60 (printed 3×), by-unit 540+540 = 1,080 (2×), ADR 135.00 = 1080÷8 — **SCREEN == PDF** · **#28** masked `IBAN ···· 1000`, no raw IBAN (structural), no guest data · **#42 mutation-proven** (dropping the scope makes the cross-owner test red); ⚠️ a LIVE foreign probe was impossible — the demo DB has exactly ONE owner, and seeding a second for a screenshot would mutate demo data (*silence is correct*, the O4 precedent) · ⚠️ **the export is NOT side-effect-free by design** — each download writes an `export_downloads` audit row (Wave B GATE 5); money rows untouched · LOGGED not fixed: **`MoneyFormatter` renders `+€0.00`** (a plus on a deduction) — fixing one surface would split SCREEN==PDF, so it belongs to a shared-helper gate (blast radius measured: 9 call sites, 0 asserting tests) | O11 |
| O12 | **Owner Settings hub** (`/owner/settings`) — ⚠️ **PREMISE CORRECTION: password-change is NOT here** — it lives on `/owner/security` (`ChangePasswordController@owner`, throttled, self-only, the shared rotate path that kills sibling sessions), with 2FA; **`Owner Security.html` is a SEPARATE prototype for its own gate**. So no password/2FA surface was touched · ⚠️ **the honesty find: the "Verified" pill was gated on `pending_email === ''`** — *"no change pending"*, **not** *"this address is verified"*. Two different facts; a user with a null `email_verified_at` would have been shown a green Verified beside an unverified address. **Truthful today only by accident** (every owner-realm invite path sets `email_verified_at` on accept) — recorded honestly as LATENT, fixed anyway, guard **mutation-proven** (⚠️ the first mutation attempt silently didn't apply — a mutation that doesn't mutate proves nothing; redone and confirmed) · the avatar upload was the page's **only unstyled control** (the raw "Choose File / No file chosen") **and unnamed** → a design-system pill, `aria-label`, `opacity:0` **never `display:none`** · **contrast 20 → 9**: 7 accent-as-text links, **every field label** (`$lbl`) and two helper lines on the glass → `--vy-on-grad-soft`, plus **`$err` — the validation-error style I could not trigger read-only, so I PROBED it and measured 4.81** rather than assuming · **stale route comment corrected** ("payout account/IBAN, notification prefs, team and 2FA … stubbed 'Soon'" — all four shipped) · ⚠️ **this is where the prototype fabricates hardest and the product already refuses all of it**: a **Visa ···· 4242** card the owner doesn't have, **15%** (real: 18%), a **"Signed in … Dublin, IE"** session location (**the GATE-33 refusal, committed again by the prototype**), a **"since 2023"**, and a **"Guests · Guest contacts" export** that would hand an owner a guest contact list (#28) · **keyboard 66 named / 0 unnamed / 0 `display:none` / 0 `div@click`** · **READ-ONLY honoured across all 7 forms — user/owner rows, owner_users, payout_methods and `export_downloads` all unchanged** (⚠️ unlike the O11 PDF, viewing settings writes no audit row) | O12 |
| O13 | **Owner Security** (`/owner/security`) — ⚠️⚠️ **TWO PREMISE CORRECTIONS, one of which would have made the page LIE.** (a) **There is NO 12-character password policy** — the brief asserted one; **all TEN password paths are `min(8)`**, and the page's "At least 8 characters" is exactly what the server enforces. **Had I "restored" a 12+ claim, a SECURITY surface would have overstated its own protection** — this pass's own defect class, inverted. Nothing changed. (b) **the security SCORE is real and rendered** (40/100 · Basic, derived from password-set 40 + 2FA 40 + unused-recovery 20) — an earlier note saying "a checklist, never a score" was stale · 🔴 **the find: a SHARED partial was carrying ONE page's layout width** — `two-factor-panel.blade.php` hardcoded `max-width:560px`, the OPERATOR column, so on the owner page it rendered as a lone **560px** card among eleven **1184px** ones. Moved to the callers; **the operator page measured before AND after, unchanged (all cards still 560, 0 overflow)**; guard mutation-proven with the mutation **asserted-as-applied** (the O12 lesson, applied). ⚠️ **Third form of the shared-partial trap: after "cannot depend on a class only one shell defines" (O6) comes "cannot carry a layout constant only one shell's design uses"** · **contrast 10 → 6**: the real green "This device" badge **3.69** → `--vy-green-text` 6.10; the score-tier pill **3.84** (the O10 tint-assumes-a-card shape) → tint over a near-white plate 4.74; 5 accent-as-text incl. **the destructive Deactivate heading + button** → `--vy-accent-text` 5.23 · ⚠️ **THE PROTOTYPE IS THE MOST DANGEROUS IN THE REALM and the product refuses all of it**: a **FABRICATED PENDING SIGN-IN** ("Chrome on Windows · **Porto, Portugal · IP 82.14.x** · Deny/Approve" — GATE-33 at its worst, presented as a live security decision), **"Trusted locations"** (needs geoip — live implements trusted DEVICES, the real concept), and the mock's own weaker policy copy · **every badge reads its real fact** (2FA Off from `hasTwoFactorEnabled()`, unused-only recovery count, real `sessions` rows) — **no hardcoded green** (GATE-24) · **§N.4k proven empirically**: 7 forms, each ONE submit scoped to its own action, no field can reach another form's action — **nothing benign can fire Deactivate** · **READ-ONLY across all 7 forms — password hash byte-identical, 2FA still null, status active, sessions 1, passkeys 0** | O13 |
| O14 | **Owner Documents** (`/owner/documents`) — a LIST of downloadable money documents, so **#42 is the core check**. ⚠️ **Premise confirmed distinct** — and ⚠️ **`Owner Archive.html` is a SECOND prototype that is ALSO a live page** (`/owner/archive`): its own gate, untouched · ⚠️ **a nav "defect" that VERIFIED AS CORRECT**: the sidebar "Statements" points at `/owner/archive` because **there is no `/owner/statements` index** — the statement LIST *is* the archive (only `/{id}` detail + PDFs exist). Checked before reporting · **contrast 10 → 2, and both remaining are the SHELL's gradient family — the page's own ink is fully AA, the realm's best result so far**: ⚠️ **"full archive ›" measured 2.70, the worst non-family site on any owner page**, and **NO accent tone reaches AA on that gradient** (darkest = 3.85) → `--vy-on-grad` + an **underline**, so the link affordance stops depending on colour at all; the header line 3.98 → on-grad-soft; the **six per-month NET figures** 4.38–4.45 → `--vy-accent-text` · **#17 reconciles ON THE PAGE**: the six monthly nets sum to **€3,690.00**, exactly the annual tax card · **#42 MUTATION-PROVEN** — the fixture already seeds a **second owner with a distinctive €500 statement**, asserts his links absent from A's list and **B→A's PDF 404**; dropping the scope turns **3 tests red** · 📌 **DO14-1 — a THIRD form of D16-7**: `payoutStatusMeta`'s `default =>` swallows **`processing`** AND **`held`** (both genuinely written by `OwnerPayoutService`), so a remittance advice for money **in flight** or **held** reads *"Payout pending"* — **mislabelled rather than invisible** (O3's tiles dropped it entirely). **Shared helper → logged for the D16-7 gate, not fixed here** · ⚠️ **a suspicion MEASURED AND DISMISSED**: a wide guard tripped on the shared bell's accent link — measured **5.06, it PASSES**; recorded as a negative sweep, guard re-scoped · **nothing downloaded this gate — `export_downloads` still 7; 6/6 notifications still unread** | O14 |
| O15 | **Owner Team & access** (`/owner/team`) — an AUTHORIZATION surface. ⚠️ **Two premise corrections:** (a) **the invite is a DIRECT owner action, not a request** — Owner-9's units-are-a-request finding does NOT generalise; (b) **the gate is STRONGER than assumed** — the brief expected "a sub-user may view, writes gated", but **`EnsurePrimaryOwner` gates the READ SCREEN too**, so a sub-user (even a **co-owner**) gets **403 on the page**. Team management is held by **no role at all** (`isPrimaryOwner()`), which makes **self-escalation structurally impossible** · **#30 and #42 BOTH mutation-proven**: removing `EnsurePrimaryOwner` turns the sub-user-refusal test red; dropping `whereIn('owner_id', $ownerIds)` from the member resolve turns the cross-owner-404 test red · **the role MATRIX is the REAL `OwnerTeamRole` grid**, area for area — and it is deliberately **NOT a privilege chain** (accountant has documents+exports but not calendar; view-only has calendar but not documents) · **GATE-33 held** — no fabricated "last active {city}"; the rows read the real `last_login_at` ("Active now" / "Hasn't signed in yet"), the helper's docblock saying *"never a fabricated relative time"* · **the watermark claim is TRUE and the page proves it** by showing the real export-download log · **the invite email field was the page's ONLY unnamed control** — placeholder-only, on the form that grants access to the owner's money → `aria-label`, guard mutation-proven · **contrast 11 → 7**: "View only" pill + member count 3.51/3.57 → `--vy-on-grad`; "Accountant" pill 4.48 → `--vy-green-text`; invite meta 4.06 → `--vy-amber-text`. ⚠️ **All 7 survivors are the white-on-gradient family — including a NEW GREEN variant** (member avatars on `rgb(92,141,82)`, **3.61**): the family is **not purely accent-coloured** · **§N.4k proven on a privilege page**: 9 forms, each one submit; **no field can reach another member's form or a DELETE/Remove route** · 📌 **DO15-1 — no seat count/limit is shown at all** (#10, **G.1-adjacent**): nothing faked, no unenforced limit advertised — surfaced, not decided · **READ-ONLY across all 9 forms — nothing invited, no role changed, no one removed** | O15 |
| O16 | **Owner Payout Method** (`/owner/payout-method`) — ⚠️ **REAL BANKING, and the most fabrication-dense prototype in the realm**: it ships an ENTIRE micro-deposit verification system that does not exist ("Verifying" badge, "Send micro-deposits & verify", a Deposit-1/Deposit-2 modal, "reversed within 5 business days", "Resend deposits"), a **fabricated "Next payout €10,370 · Estimated 3 Nov 2025"**, a 48h hold and a 2-business-day SEPA-trace SLA. **The product refuses all of it** — live shows the REAL €885.60, no date, and each retained line qualified *"with your live banking connection"* · ⚠️ **the structural fact that decides the page: `payout_methods` has NO verification column at all**, so a "Verified" badge would be **pure fabrication**, not a stale value; and the full IBAN is stored **encrypted** with only last4/country in the clear, so `Iban::masked` makes a raw IBAN **structurally un-emittable** · **#28 swept on the RENDERED DOM: 0 IBAN-shaped strings, only `···· 1000`** — **mutation-proven** (emitting `$m->iban` turns the masked-IBAN test red) · 🔴 **contrast: the validation-error flash measured 3.03 ON A BANKING PAGE** (`.vy-flash.is-err` used `--vy-amber`, a FILL tone, as TEXT) → `--vy-amber-text` **5.08**; the error only renders after a failed submit so it was **probed in and measured**, not assumed · the **pre-AA `#9C9089` literal** still lived here at **2.55–2.66** (worst on the page) → on-grad-soft; **13 → 6**, all survivors the gradient family · ⚠️ **the shared `.vy-flash` rule exists in BOTH layouts but they are INDEPENDENT rules with different palettes** (owner amber / operator accent) — **and the operator layout has no `--vy-amber-text` token at all**, so a blanket change was impossible; checking first (the O6 lesson) kept the fix owner-only, blast radius verified across all 3 consumers · ⚠️⚠️ **A GUARD OF MINE WAS VACUOUS AND MUTATION CAUGHT IT**: I tightened `assertDontSee('Verifying')` to also forbid **"Verified"** — the stronger claim — but it sat in a fixture with **NO payout method**, so no account row ever rendered and the injected fake badge passed. **Relocated to the account-rendering test, re-mutated, now genuinely red.** Textbook empty-fixture vacuity · **§N.4k**: Enter in the IBAN targets Save, **never the DELETE form** · **READ-ONLY across all 7 forms (a FAKE IBAN typed, never submitted) — payout_methods still 1, same last4/default/seed-time `updated_at`, preferences still 0, owner tax fields still null** | O16 |
| O17 | **Owner Balance** (`/owner/balance`) — ⚠️⚠️ **PREMISE INVERTED: this page is the D16-7 REFERENCE IMPLEMENTATION, not another victim.** The brief expected `processing`/`held` collapsed or dropped (O3 dropped them from tiles; O14's shared `payoutStatusMeta` collapses both into "Payout pending"). **The opposite:** `OwnerBalance` uses its OWN mapping — **On hold = persisted `held` + paused pending; In transit = `processing` + released pending**, with per-row chips naming them as **distinct words**. ⚠️ **So the D16-7 gate now has a working reference to copy: make `payoutStatusMeta` match `OwnerBalance::rowStateMeta()`** · 🔴 **the find: those buckets were COMPLETELY UNGUARDED.** Dropping `processing` from In-transit or `held` from On-hold left **every** Balance test green — **and** `PayoutEngineSurfaceTest`, the one test that creates a real `held` payout, because its identity is computed **from the DB** not the component and its `assertSee('On hold')` passes on the tile's **static label**. New guard asserts the **component's own buckets** in both states with the identity intact; **mutation-proven both directions**. ⚠️ **Found only by running the mutation — the first attempt PASSED, which is what exposed the hole** (the O16 lesson, immediately repeating) · **contrast 28 → 4**: ⚠️ **the ON HOLD tile's own label+sub measured 3.08/2.98 — on the D16-7-critical tile — and on that tint NO grey token reaches AA** (`--vy-on-grad` tops at 4.31) → `--vy-ink` **6.80/6.57**, hierarchy carried by size+weight; ~20 "on a statement" links + "Payouts →" → accent-text · ⚠️ **the 4 survivors, honestly**: 3 gradient-family + **the DISABLED "Withdraw €963.60" at 2.33, formally EXEMPT and deliberately left** — darkening a control that **cannot act** would make it look live, which this page's first rule forbids · **stale docblock corrected**: it stated a **4-term** identity with pending-only buckets, contradicted by the code **8 lines below** and by the test asserting **5** terms (incl. Carried forward) · **#17 on screen: 963.60 + 885.60 + 0.00 + 2,804.40 = €4,653.60 = Net** · **READ-ONLY — nothing withdrawn; payouts still 6 (pending/paid only), seed-time timestamps** | O17 |
| O18 | **Owner Bookings** (`/owner/bookings`) — 🔴 **the chart's x-axis SKIPPED EMPTY MONTHS, and the net-revenue LINE stepped across the gap.** `chart()` grouped only months that HAVE bookings, so with **November 2026 genuinely empty** (verified in the DB) the axis read `… Sep · Oct · Dec …` and the polyline joined **October straight to December as if consecutive** — compressing two months into one step and misreporting a money trend. Fixed to a contiguous calendar axis (live: **May…Dec, 8 consecutive months, November rendering 0px**); guard added, **mutation-proven** · ⚠️ **and the zero would have DRAWN A BAR**: the min-bar floor was here **twice** — `max(2, pct)` in the view **AND a CSS `min-height:3px`**. **Fifth page with this bug, and the FIRST time it appeared as CSS** — the planned shared-`<x-charts.bars>` fix (§N.9 #4) would **not** have caught a CSS floor, so that gate's scope needs widening · **contrast 21 → 3** (all survivors gradient-family): 10 × the "Confirmed" pill at 3.75–3.81 and the chart net label at 3.65 sit on an accent **TINT** where `--vy-accent-text` is only marginal (4.51/4.33) → `--vy-accent-tint-text` 5.40/5.19; the goal delta used `--vy-amber` as TEXT (the O16 pattern) → amber-text; breadcrumb + separator on the gradient → on-grad-soft · ⚠️ the **over-goal** delta never renders in the demo, so it was **probed in and measured** · **#42 AND #28 both MUTATION-PROVEN** (unscoping the unit set → red; appending the guest email to the row → red); live: **2 foreign booking ids → 404**, list clean, **0 emails / 0 phones / 0 door-codes / no `guest_id` in the DOM** · **no OTA-brand split** — the real enum only (`Direct`/`Corporate`/**`OTA`**, D2-2) · **"Not yet attributed" never a fake €0** | O18 |
| O19 | **Owner Booking Detail** (`/owner/bookings/{booking}`) — the per-id isolation surface. **#42 mutation-proven at its sharpest**: reservations carry no `owner_id`, so the boundary is `findOrFail` **then** `abort_if($ownedLines->isEmpty(), 404)` against the owner's own units (the #26 shape); removing that one line turns the cross-owner test **red**. Live: own **200/200**, a FOREIGN booking **on an operator-owned unit in the SAME org** → **404**, nonsense → 404 · **#28 mutation-proven ON THIS URL** (the guard loops list **and** detail): seeding a guest with a real email+phone and leaking it here turns it red — ⚠️ **but the first mutation was VACUOUS** (echoing a `guest_email` key that doesn't exist rendered nothing and "passed"); the **component** had to be mutated too. *Third occurrence of the O12/O16 family.* Live: 0 emails / 0 phones / 0 door-codes / no country / no address · 🔴 **a docblock CITED A TEST THAT DOES NOT EXIST** — `OwnerBookingDetailContactTest`; the guard is real but lives in `OwnerBookingsTest`. **A named-but-absent test reads as stronger evidence than an unnamed one.** Citation corrected · **contrast 36 → 5, the worst single reading of the owner realm**: the page carried **all three** pre-AA literals — `#A69A92` (×8, at **1.37** on the header gradient), `#9C9089` (×2), `#8A8079` (×9) → `--vy-on-grad-soft`; the four-state status pill was flat accent-on-tint → **plated tints + darkened tones** (green 5.96 · amber 4.80 · red 6.08 · blue 7.19) · **the 5 survivors are all white-on-gradient family** — ⚠️ incl. the guest-initials avatar at **3.09** · **#17 on screen: €180.00 × 3 = €540.00 − €97.20 (the REAL 18%, not the prototype's 15%) = €442.80** · refusals held: guest email/phone/country, the OTA brand · **READ-ONLY** | O19 |
| O20 | **Owner Booking Summary PDF** (`/owner/statements/{statement}/bookings`) — 1 of a 3-PDF batch, measured ON PAPER. **Premise: the DOCUMENT was already design-matched at #56** (letterhead · `BS-YYYY-MM` · 4-up KPI · By unit / By channel / Bookings-this-period · callout · footer all match the decoded prototype) — **what was never closed is the a11y axis**, the same shape as O11 · 🔴 **THE FINDING: SCREEN AND PDF DISAGREE ABOUT "NIGHTS"** — the statement screen prints **"8 nights · 2 units"** under the gross, the PDF's Nights KPI prints **6**, for the SAME statement. Both real, neither fabricated: the screen counts every **physically occupied** stay-night (`PerformanceMetrics::roomNightsSold`), the PDF sums the nights of the **bookings whose revenue this statement claimed**. The screen's number is attached to the GROSS, so it asserts 8 nights produced €1,080.00 when 6 did. **Surfaced, not decided** (a cross-surface money-label call) — neither figure touched · **contrast on paper 3.86 → 6.98**: `#8A8079` (8 declarations, **25 text runs** — every heading, table header, sublabel, KPI key, letterhead) and the footer's `#A69A92` **2.74 → 5.79**, retargeted to the values `owner-statement` already uses so the two owner documents read alike; measured on the **regenerated PDF's content stream** · 🔴 **the document printed on TWO SHEETS, the second carrying only the footer** — dompdf applies its **own default page margin ON TOP of** `.wrap`'s padding-bottom, overflowing by a few points. **No HTML-level check can see this** (the markup is valid); it took counting `/Type /Page` in the generated file. Now ONE page · ⚠️ **A SIXTH VACUOUS-GUARD FORM, caught on myself:** the page-count guard **passed with the bug reintroduced** — this file's fixture caps at **two** bookings (a third collides on availability) and two rows fit either way, so the fixture could never reach the boundary. Widening it didn't help; the guard was **rejected and replaced** with a mutation-provable rule assertion · **#42 mutation-proven both ways** (`withoutGlobalScopes()` → cross-owner AND fail-closed both red); **#30** `$capExports` · **#17**: by-unit 540+540 = by-channel 540+540 = rows 2×540 = **€1,080.00** = `gross_minor` 108000 = **the screen's own string** · **occupancy denominator = units×days** (2×30=60; per-unit 5/30 + 3/30 → headline 8/60 = 13%) · **#28 content stream: guest NAMES only, 0 emails / 0 phones / no IBAN** — ⚠️ disclosed: the watermark carries the **downloader's own** address, by design · **READ-ONLY** (an `export_downloads` audit row writes by design; money unchanged: gross 108000, net 88560) · **PdfService UNTOUCHED** — its `#a99b8e` watermark re-measured at **2.70**, and the pre-AA greys remain in **14 other templates**, ⚠️ **two of which are pages 21 and 22** | O20 |
| O21 | **Owner Remittance Advice PDF** (`/owner/payouts/{payout}/remittance`) — 2 of the 3-PDF batch. ⚠️ **PREMISE CORRECTED — the prototype's "Management fee · Fixed monthly −€150.00" is REAL AND MODELLED, not a fabrication.** `ManagementAgreement.management_fee_minor`/`_interval` → **`OwnerAttributionService::attributeManagementFee()`** (pro-rated, idempotent, posted as an `owner_ledger` **management_fee debit**) → a statement line → the advice. The DEMO agreement simply carries none (`NULL`), so the line is **genuinely absent** — the #70 `payment_fee` pattern, **not a refusal**. Proven in **BOTH directions** by a new guard (no fee → no line; a real **€150.00** monthly fee → the certified service posts it and the advice states it), and mutation-proven by fabricating the prototype's exact line into the document → red · 🔴 **D16-7 · A FOURTH FORM: a real payout state rendered UNSTYLED.** `payouts.status` has **six** values; the pill had rules for **four** — `processing` and `held` (⚠️ exactly D16-7's two) emitted a class with **no matching rule**, so the plate and colour vanished and the status degraded to bare text. **The LABEL was never dishonest** (`ucfirst` of the real enum), so the document never lied — it stopped LOOKING like a status. Fixed by giving both the **pending tone**, mirroring `OwnerBalance::rowStateMeta()` (the reference) where the two share a tone and are told apart by their WORD · **LOGGED not decided (DO21-1/2)**: `payoutStatusMeta` (`FormatsOwnerData:99`) still has **no case** for either → both collapse to "Payout pending" on Payouts/Documents/Statement/Dashboard — ⚠️ **this document does NOT use that helper**, so it is on the honest side; and one `held` payout now reads **three ways** ("On hold" · "Payout pending" · "Held") · **contrast on paper**: `#8A8079` (14 runs) **3.86 → 6.98**, `#A69A92` **2.74 → 5.79**, and ⚠️ **`#B26A08` — the PENDING pill AND the PROVISIONAL flag — 4.24 on white but only 3.72 on the `#FBEFD8` plate it actually sits on** → `#804C05` (**6.24** plate / **7.11** white); the other three pills already passed on their plates (5.61 / 5.76 / 5.08), **measured not assumed** · **#17 SCREEN==PDF**: +€1,080.00 − €194.40 + €0.00 = **€885.60** = `amount_minor` = the screen's own figure, and €194.40 is exactly **18.0%** (the mock 15% would be €162.00) · **#28** IBAN masked "···· 1000" (CAP_PAYOUT-gated per owner, omitted otherwise), no guest data (bucket labels only) · **#42 mutation-proven both ways** · **#71** honest · **READ-ONLY** · **PdfService untouched** (watermark 2.70); **13 templates** still carry the pre-AA pair, ⚠️ **page 22 among them** | O21 |
| O22 | **Owner Annual Tax Summary PDF** (`/owner/tax/{year}/summary.pdf`) — 3 of 3, the batch closer; the PEAK-authority money document (an owner hands it to an accountant). **#60's multi-year fabrication is refused STRUCTURALLY and more strongly than briefed** — the URL carries **no owner id at all** (the owner comes from `$request->user()`), so a cross-owner request is **not expressible**; a year with no committed statements returns `null` → **404** (live: **2025 → 404**, 2026 → 200). The period prints the **REAL statement span** "1 Apr – 30 Sep 2026", not a fabricated calendar year · 🔴 **A STALE DOCBLOCK POINTING THE DANGEROUS WAY:** BOTH docblocks still said *"no withholding (we don't compute it)"* — stale since the tax engine (#73), whose `annualSummary()` emits a real `withholding_minor` bucket + a "Withholding tax" category (`OwnerTaxService:181`) that reaches the table like any other deduction. **A reader believing the comment might add a SECOND withholding line and double-count a real payout deduction on a TAX document.** Both corrected + pinned by a both-directions guard. *(O19's sibling: a docblock citing a test that doesn't exist → here, one DENYING a feature that does.)* · **#73 framing verified**: the document prints the withheld **AMOUNT only, never a rate** — ⚠️ a first probe said otherwise and was a **FALSE POSITIVE matching CSS `width:33%`** (correct the probe, not the page — 7th occurrence); footer carries *"not tax advice — share it with a qualified accountant"*. **LOGGED DO22-1**: when withholding is non-zero the document doesn't say the figure came from CONFIGURED rates (Reports carries an "EXAMPLE rates" warning) — **surfaced, not decided**, legal-facing copy is the founder's call · **contrast**: `#8A8079` **3.86 → 6.98 across 27 runs — the widest of the three**, ⚠️ including **`.neg`, the DEDUCTION FIGURES themselves** (the O2/O11 finding, on the document most likely to be read by an accountant); `#A69A92` **2.74 → 5.79** on the footer — ⚠️ **exactly where the "not tax advice" framing lives, the least legible text on the document** · **#17**: 4,500.00 − 810.00 = 3,690.00, month rows sum exactly, **810/4500 = 18.0%** · zero categories filtered (no invented €0.00 row, mutation-proven) · **#28** no IBAN, no guest data · greyscale-safe (a real U+2212 minus) · **READ-ONLY** · 📌 **PDF SWEEP RECOMMENDATION SURFACED, not self-authorised**: 3 templates clear, **12 remain + `PdfService:54`'s 2.70 watermark on every page of every document** — recommend ONE dedicated gate (the watermark is untouchable from a page gate; each template still needs its own generated-file measurement; guest/operator docs are outside this pass) | O22 |
| O23 | **Owner Block Calendar** (`/owner/calendar`) — 🔴 **THE O10 KEYBOARD LOCKOUT, FOUND AGAIN IN A SECOND COPY OF THE GRID.** Unit Detail was fixed at O10, but this page has its **own** day-cell markup in its own view — not a shared partial — so the fix never reached it. An open day was a `<div>` with only `@mousedown`/`@mouseenter`, **no tabindex, no role, and 0 keydown handlers on any cell**: **15 open cells, 0 focusable**. Selecting was **mouse-drag ONLY** — and selecting is the first half of BLOCKING, which takes inventory off-sale via `OwnerBlockService`. **A keyboard user was locked out of a money-affecting action on the page built for it.** Fixed to real `<button>`s + spoken date + `aria-pressed` + focus ring + a **keyboard range path** (`pickSel` anchors on the first Enter, extends on the second; bound to fire only for a pointer-detail-less click so the mouse drag is untouched). **Proven live: 19–22 Aug selected entirely by keyboard, ZERO network activity** · ⚠️ **the prototype is mouse-only by construction** (tabindex count **0** in the whole decoded file) — an added fix, not a divergence, same call as O10 · 🔴 **DO23-2: the two grids are DUPLICATED, not shared** — O10's fix had to be re-made from scratch and the next one will be made twice again; extract one day-cell partial (**the shared-partial trap in its "second unfixed copy" form**) · ⚠️ **PREMISE CORRECTED: a foreign unit FALLS BACK, it does not 404** — `resolveUnit()` matches `?unit=` only within the owner's own collection, so a foreign id renders **A1**, appears **nowhere** in the HTML, and the posted `unit_id` stays the owner's; no leak, structural, but a silent fallback. The WRITE guard (`ownedUnitOrFail`) is **mutation-proven** · **READ-ONLY**: `inventory_blocks=2`, `max(updated_at)=2026-08-09 07:17:59`, `availability` 650 / blocked 6 / sold 105 — **identical before and after** · **§N.4k Enter-safe BY CONSTRUCTION** (the POST form holds only `type=hidden` inputs + is submitted explicitly; the visible note sits OUTSIDE any form) · **#28** guest NAME only on a booked cell · **contrast 22 → 3**: ⚠️ **`.vy-cal-past` numbers at 1.48 — the worst reading of the owner realm** and the **O17 disabled-exemption does NOT apply (it is TEXT, not a disabled control)**; the guest name on the booked TINT 3.90 → `--vy-accent-tint-text` (the O19 rule); survivors are the 3 known gradient-family items, **none added** · **the note input had NO focus indicator and NO accessible name** — both fixed (⚠️ a probe flagged the whole nav too: **FALSE POSITIVE** reading unfocused defaults, 8th of the pass) · ⚠️ **A VACUOUS GUARD CAUGHT ON MYSELF: my keyboard needle was satisfied by MY OWN CODE COMMENT** quoting the same expression — deleting the real binding left it GREEN. Comment reworded + the assertion tightened to the ATTRIBUTE; both mutations now red. **(The O11 comment trap, now biting a TEST needle.)** | O23 |
| O24 | **Owner Archive** (`/owner/archive`) — ⚠️ **TWO PREMISE CORRECTIONS.** (1) The brief called this "25/32, confirm 24 ran first" — **page 24 (Reports) had NOT run** (HEAD was O23, the index read "NEXT = 24"); Archive is indexed **24/32** and **Reports is still outstanding**. (2) **The prototype's fabrications were ALREADY refused and the ZIP is ALREADY honest** — "3 years · 34 documents", "since June 2023", the 7-year retention promise and `OS-2025-09` are all NOT PORTED with a guard forbidding each, and the ZIP is a genuinely `disabled`+`aria-disabled` "Soon". Live: **zero fabricated needles** · 🔴 **A DOCBLOCK DENYING A REAL FEATURE — TWICE (the O22 pattern):** `OwnerArchive.php` filed the per-year tax summary under **NO BACKEND** claiming *"there is no tax engine, and its Owner Annual Tax Summary PDF does not exist"* — both false since #73, and the document is the one **gated at O22**; a test comment repeated the claim. ⚠️ **The CODE was already right — the view links `owner.tax.annual` per year, CAP_EXPORTS-gated — so acting on the docblock would have added a DUPLICATE link to a working feature** (the inverse of O22's risk). Both comments corrected · ⚠️ **THE UNGATED ROUTE IS CORRECT — gating it would have been a REGRESSION:** `/owner/archive` has no capability middleware while `/owner/documents` has `$capDocuments`, but **`CAP_FINANCIALS` ("earnings & statements") is held by ALL THREE roles** — adding a gate would have removed a view-only sub-user's intended access to their own earnings. Every download control IS `@ownercan(CAP_EXPORTS)`-gated (#30) · **#60 MUTATION-PROVEN** (restoring the prototype's counts → red); **#42 MUTATION-PROVEN** (`withoutGlobalScopes()` → **3** guards red) · **#17**: the six statements sum to **€3,690.00 exactly** = the lifetime hero = the 2026 bar = **the same figure the Annual Tax PDF stated at O22**; per payout currency, never a cross-currency sum · **LOGGED not decided**: **DO24-2** a finalized statement always reads "Payout pending" even when its payout is really **held** — Archive uses `statusMeta` (statement enum, all 3 mapped) so it does **NOT** inherit the D16-7 collapse, but it cannot express a hold (latent — no held payout in the demo); **DO24-3** the per-year bars are **sparse**, and ⚠️ **deliberately NOT "fixed" as an O18 repeat** — these are LABELLED bars, and a €0 bar for a year the owner wasn't onboarded would assert "you earned nothing", a #60-class fabrication in the other direction · **contrast 9 → 2**: 6 × the "View" link at **4.45–4.49** (raw accent-as-text, marginally under) → `--vy-accent-text`; the sub-header at **3.98 on the HERO GRADIENT** → `--vy-on-grad`; survivors are the 2 known shell gradient items, **none added** · **READ-ONLY** (ZIP disabled, nothing downloaded); ⚠️ **9th probe FP** — "2 unnamed inputs" were `type=hidden` and the "unreachable divs" were the shell's Alpine roots | O24 |
| O25 | **Owner Reports** (`/owner/reports`) — 🔴 **A MONTH THAT EARNED NOTHING WAS DRAWING A BAR.** The net-by-month chart carried a **two-percent floor** (applied twice — bar + value label), so **five months that genuinely earned €0.00 each rendered a visible bar while printing "€0.00" right above them**. ⚠️ **The AXIS was already correct** (`netByMonth()` seeds every month and walks `addMonth()`), so the O18 *axis* bug does NOT apply — only the bar did; **verified fresh rather than assumed**. Fixed to the honest value + a 3px hairline ONLY when non-zero; live after: the five zero months render **0px**, all twelve still on the axis · 🔴 **A #60 GUARD THAT COULD NOT FAIL:** the reporting-year test asserted **only positives** while its comment claimed the fabricated years were gone — **hardcoding the wireframe's 2026/2025/2024/2023 left it GREEN**. ⚠️ **Repairing it took THREE needles, and the two failures matter:** `assertDontSee('2024')` **fails on CORRECT markup** (a bare 2024 legitimately appears in the custom-range MONTH picker — the O14 too-generic-needle lesson) · `'range=year:2024'` **can never fail** (not the emitted href) · ✅ `'year=2024'`, the real GET key — **now mutation-proven**. *A needle must come from the RENDERED OUTPUT, not the assumed parameter* · ⚠️ **BOTH Archive carry-forwards CHECKED, NEITHER APPLIED** — the stale-docblock-denies-a-feature pattern does **NOT** recur (this docblock already describes the #73 tax engine as REAL/COMPUTED — **clean negative**); no €0 row was back-filled for a pre-membership year · **the rate is the REAL 18%** (−€1,076.40 on €5,980.00 = exactly 18.0%; **mutation-proven** — forcing 15.0 turns the gross-to-net guard red; `15%` appears nowhere) · **#17 reconciles FOUR ways to €4,653.60** (gross-to-net · per-unit 54%+46% · by-channel · the month chart), one source · **#73** "not tax advice" + EXAMPLE-RATES tag intact · **#28** IBAN masked · **READ-ONLY** (every export a GET link, none fired) · **contrast 15 → 5**: ⚠️ **the "EXAMPLE RATES" tag — the #73 warning itself — was the LEAST LEGIBLE TEXT on the page at 3.82** (the O22 shape exactly), fixed on its **own inline override** because `.vy-soon-tag` is defined in the SHARED shell and must not be retuned for one page; + 6 accent-as-text and the chart labels; survivors are 5 white-on-accent, **none added** · **LOGGED DO25-1**: the month chart is a **local** implementation, not the shared `<x-charts.bars>` — which is why this floor survived after being fixed elsewhere (the O23 duplication axis) · ⚠️ **10th probe FP** (popover wrappers carry only `@keydown.escape.window`; each has a real button trigger) | O25 |
| O26 | **Owner Reviews** (`/owner/reviews`) — 🔴 **EVERY RATING RENDERED AS FIVE FULL STARS.** The hero row was `str_repeat('★', round($avg))` followed by `str_repeat('★', 5 - round($avg))` — **both halves repeat the SAME FILLED glyph**, so any rating drew five gold stars. Live: a **4.5 average displayed as a perfect 5**; a 3.0 would have too. On a social-proof surface the glyphs overstated the real figure sitting beside them · **Fixed by EXTRACTING the shared `<x-star-rating>`** (new + additive, DO10-4 partly closed): it **floors** the filled count — ⚠️ **my first version ROUNDED, which still showed 4.5 as a perfect five; on a social-proof surface the honest rule is NEVER ROUND UP** — marks the remainder with a **different glyph** so filled-vs-empty survives greyscale (1.4.1), and carries **one accessible name** ("4.5 out of 5 stars") with the glyphs `aria-hidden`. Live after: **★★★★☆**. ⚠️ Colours are **self-contained literals, not tokens**, because it renders in five shells (the O6 trap). **The other TEN call sites are untouched — no 12th copy added**; the migration is DO10-4's own gate · **#28 byline first-name+initial, STRUCTURAL** (`GuestName::reviewByline`) — live `Sofia M.` / `Anna K.`, **0 emails**; ⚠️ **the existing guard was STRENGTHENED**: its fixture gave the guest **no contact**, so it proved the surname was trimmed but **could not** prove the projection withholds an email — *a guest with no email cannot leak one*. **Mutation-proven twice** (full surname → red; email appended to the byline → red) · **READ-ONLY is STRUCTURAL** — the page contains **no review form at all** (the only forms are the shell's); nothing live-looking to disable. ("Report" in the DOM is the sidebar **Reports** nav item — checked) · **#39 aggregates reconcile**: 4+1+1+0+0 = **6 reviews** = the printed count = 3+3 per villa, and **(5×4+4×1+3×1)/6 = 4.5** exactly · **#42** unit ids from the owner-scoped service; the pending-proposal `count()` is on `RateProposal`/`BelongsToOwner` (checked — a bare count is where a rollup leaks) · **keyboard 0 unnamed / 0 display:none / 0 unreachable** · ⚠️⚠️ **CONTRAST NOT MEASURED — TOOL FAILURE, DISCLOSED.** The screenshot step timed out repeatedly (including on a page that captured fine earlier — an environment failure, not this page). A CSS-compositing fallback I wrote was **REJECTED**: it scored white text at 1.0 against a painted-over layer and reported 90 failures. **Publishing numbers from a probe I know is unsound is worse than publishing none** (11th probe FP — the first I authored myself). **This page's contrast is OUTSTANDING**, including the new `☆` colour `#B9A99A`, chosen but **not verified against 1.4.11** | O26 |
| O27 | **Owner Messages** (`/owner/messages`) — 🔴 **A HARDCODED "1" STOOD IN FOR AN UNREAD COUNT.** The row rendered `<span class="vy-msg-badge">1</span>` whenever `$row['unread']` was truthy — but that value is a **BOOLEAN**, so **a thread holding three unread messages printed "1"**. The Unread TAB badge beside it **is** a real count, so two identical-looking badges meant different things. Fixed to an unread **DOT + spoken "Unread"** — no digit the data cannot support; the tab kept its real number and gained SR context. **DO27-1 logged**: there is no per-thread unread COUNT · **#28 is STRUCTURAL and stronger than a predicate**: `MessageThread` has `owner_id`+`BelongsToOwner`, `OwnerScope` does `whereIn(owner_id,…)`, and **a NULL owner_id can NEVER satisfy a whereIn** — so every operator↔guest thread is excluded by the scope itself. Demo holds **3 guest-side threads + 2 owner-relay**; the page renders **exactly the 2**. DOM: **0 emails, 0 phones, no door code, the word "guest" appears nowhere**. **Mutation-proven** (`orWhereNull(owner_id)` → *"never shows an owner a guest conversation"* red) · **SENDING OBSERVED READ-ONLY** — baseline `message_threads=5 / messages=9`, max ts `2026-08-09`, **identical after**; nothing sent, reacted, starred or pinned · **§N.4k Enter SAFE and deliberate** — single-line composer = the chat convention; the risk case (Enter from the file field) **cannot send empty**: `body` is `required` client-side AND the controller refuses body+files empty · **unread not colour-only** (dot + weight + spoken) · **contrast 15 → 8**: ⚠️ **the rail is TRANSLUCENT over the peach gradient**, so its text sat on a saturated ground — timestamp **2.64**, tabs **2.85**, count **3.37**; `--vy-on-grad` was **still not enough** (4.06–4.42) → **`--vy-ink`** (the O14/O17 rule: on a strong tint NO grey reaches AA). Search icon 2.06 + Starred icon 2.74 (1.4.11) fixed. **Survivors 8, all white-on-accent, none added** — incl. the **message bubbles at 3.97 (the owner's own message TEXT — the largest body this family touches yet)** and ⚠️ **`.vy-nav-item is-active` 3.79, the SHARED shell nav, deliberately NOT retuned from a page gate** · 📌 **O26's contrast, un-measurable last gate, was MEASURED here** (the tool recovered on a browser restart): **30 failures, ~17 of them the ★ spans NOT migrated to `<x-star-rating>`** (gold **1.68–2.09**, empty glyph **1.44–1.61**) — DO10-4 biting exactly where predicted; the migrated hero is exempt (aria-hidden behind a name). ⚠️ Two readings (1.03/1.12) are **boundary-sampling artifacts, not findings**. **The O26 FIX is scoped and UNSTARTED — deliberately not folded into this gate** | O27 |
| O28 | **Owner Help** (`/owner/help`) — ⚠️ **PREMISE CORRECTED: the AI assistant is BUILT, not deferred.** *Ask Vyrobel* shipped in **Wave D** — `AskVyrobelService` retrieves over the Help KB first and **escalates deterministically with NO model call when retrieval is empty** (so out-of-scope/account-specific questions can never reach the model), plus a per-owner token cap and a circuit breaker to the same grounded fallback. Real POST route (PRG). Locally `LLM_FAKE` defaults true with no key, and a test proves the fake client is forced outside production **even with a real key** · ⚠️ **the page's own docblock listed "the AI Q&A" under REFUSED — stale since Wave D, the THIRD docblock denying a shipped feature** (O22, O24). Corrected · 🔴 **THE FIX — THE WORDS CAME OUT, THE SIGNAL STAYED.** A previous pass removed **"All systems operational"** (nothing observes system health) and a guard still forbids that string — but **the visual half survived**: a **green presence dot under a heading reading "System status"**, beside a sentence that is not a status (*"Your operator is your first line of support"*). **A green dot under that heading asserts "operational right now" as loudly as the words did**, with nothing behind it — the GATE-24/Owner-12 trap, and the very board this file's docblock records as REFUSED; it was also **colour-only**. Dot removed; heading → **"How support works"**. Guarded + mutation-proven · ⚠️ **A FALSE ALARM I RAISED AND CLEARED**: "replies in" matched *"your team replies **in your inbox**"* — a LOCATION, not a time SLA. **Read the context before calling a claim** (the O25 needle lesson, in the reading direction) · **DO28-1/2 RATIFIED REFUSALS**: there is **no presence or SLA signal and the page claims neither** (no "desk online", no "under an hour") — do NOT restore the prototype's promise; and no ticket queue (messaging is the tracked channel) · **contact is honest** — **zero `mailto:`/`tel:` links**; the card points at where the real number lives (welcome pack + latest statement) rather than inventing one · **READ-ONLY** (nothing submitted) · **§N.4k** single required named field → Enter submits the one thing it can, never empty · **contrast 7 → 5 (3 real + 2 artifacts)**: ⚠️ **the AI DISCLOSURE line measured 4.48 — the sentence telling the owner to verify the assistant was the LEAST LEGIBLE text on the page** → `--vy-ink`. **Third time the honesty text is the worst reading** (O22 "not tax advice", O25 "EXAMPLE rates", now this). Survivors = 3 white-on-accent, none added; ⚠️ **2 reported failures are BOUNDARY-SAMPLING ARTIFACTS** (bg range spans an element edge; the same elements read ~3.83/3.88 cleanly elsewhere) | O28 |
| O29 | **Owner Onboarding / Welcome** (`/owner/welcome`) — ⚠️ **PREMISE CORRECTED: NOT A WIZARD.** The brief treated it as a multi-step MUTATION flow with per-step writes and an Enter risk; it is a **73-line READ-ONLY hand-off page** — **no `<form>` in `main` at all**, no input/textarea/select; every next step is an `<a href>` to the page that owns the write. **§N.4k is MOOT** (nothing submits, so Enter cannot finish early — the Owner-9 trap was CHECKED, not inherited), and READ-ONLY was structural (confirmed anyway: `owners`/`payout_methods` counts + `max(updated_at)` unchanged) · 🔴 **THE FIX — A STATUS FLAG GATED ON THE WRONG FACT ISSUED A FALSE INSTRUCTION.** `hasPayout` read `$units->isNotEmpty() && PayoutMethod::exists()`; the unit conjunct has nothing to do with whether an account is on file, so **an owner who had already saved a payout method but had no units yet got the EMPTY branch — which prints "add a bank account on the secure Payout Method page"**, telling them to redo work they had done. Fixed to `PayoutMethod::query()->exists()` (owner-scoped by `BelongsToOwner`); mutation-proven. ⚠️ **The GATE-24/Owner-12 family in its UNDERSTATING direction — the more damaging one, because a false "not done" issues a false INSTRUCTION where a false "done" merely misleads** · **no fabricated progress** (no bar, no "N of 4", no tick on an undone step; the one completion claim is conditional on the real owner-scoped `exists()`) · **state not colour-only** (the chip's colour AND the heading's wording both change) · **real 18%**, never the mock 15% · **no IBAN/micro-deposit/"verified" anywhere** — DO29-1 ratified: the thin flow hands off to the certified Payout Method page · ⚠️ **CONTRAST: THE FIRST MEASUREMENT WAS UNSOUND AND DISCARDED** — it reported 22 failures including text scored **against its own colour** (the screenshot fired before the hide-text style painted). The probe now **asserts every candidate computes transparent BEFORE capturing** and freezes the entry animations (these cards fade in on a delay). *(The O26 rule applied to my own run.)* From the sound baseline **12 → 4**: chips **2.62/3.15/3.50** + links **2.79/3.46** on cards compositing over the hero peach — `--vy-accent-text` reaches only **~3.3** and `--vy-accent-tint-text` **3.74**, so both → **`--vy-ink`**, links keeping affordance via **underline + arrow**; sub-lines **4.05–4.43** → ink **per element** (`.vy-line-sub` is SHARED). Remaining 4 = the brand mark 3.88 + the CTA (same standard accent gradient, ~3.9 elsewhere — its 1.00 here is a **mis-sample**, reported bg pure white) + 2 shell boundary artifacts | O29 |
| O30 | **Owner Expenses & maintenance** (`/owner/expenses`) — ⚠️ **PREMISE CORRECTED: the third state is `queried`, NOT "rejected"** — a queried expense is **held out of the net while a conversation opens**, not refused. `approve()` posts the debit, `query()` posts nothing, `isPendingApproval()` blocks a second decision; both routes `$capActions` (co-owner only) · **#17 RECONCILES IN BOTH STATES, and the page is STRICTER than "approved"**: Spent-this-year **€709.00** (Σ 2026 rows, count 5 ✓) · Awaiting-approval **€84.00** (the one pending row ✓, labelled *"not charged until you do"*) · Charged-to-payouts **€250.00** = Σ `owner_ledger` `entry_type='expense'` ✓. ⚠️ **There are FIVE approved expenses (€745.00) but only TWO ledger rows (€250.00)** — three approved rows carry no `attributed_ledger_id` (migration back-fill + non-deductible never posts). **The KPI counts the LEDGER, not the approval state**, so it claims €250.00 not €745.00 — it reports only what actually hit the owner's money. **Mutation-proven BOTH ways** (drop `postExpense()` from approve → red; force-create everything `approved` → red) · 🔴 **A STALE TEST *NAME* — the FOURTH stale-claim, one layer out.** `OwnerExpensesViewTest` was titled *"…and **invents no approval workflow**"*; its BODY was updated when Wave C GATE 2 shipped the machine (a comment says so) but the NAME was not — **and the name is what CI output and a grep show**, where it reads as an assertion the workflow doesn't exist. Renamed. *(O22/O24/O28 were docblocks; this is the same shape in a test name.)* · **vendor is REAL** (real column + real values + honest "—"; Wave-A refused-then-added confirmed) · **READ-ONLY** — approved 5/€745.00, pending 1/€84.00, `max(updated_at)` and the 2 ledger rows **identical before and after**; nothing approved or queried · **state is a WORD**, and no tick/badge asserts an unheld state (O28) · **the charged flag reads the LEDGER** — the right fact (O29) · **contrast 18 → 8**: ⚠️ **the KPI band is translucent over the hero gradient — the FOURTH consecutive owner page** with this shape; the shell greys measured **3.05–4.35** → `--vy-ink` in a **PAGE-SCOPED style block** (⚠️ the shell's definitions untouched — retuning them changes every owner page); the pending **€84.00** needed ink too (accent-text only 3.13). **Remaining 8 = the brand mark + 7 MIS-SAMPLES** — pills/buttons reporting 1.00–1.52 against a pure-white or off-element background, each verified to carry the standard accent gradient (true value ~3.8–3.9). ⚠️ **Small elements are where this probe mis-samples** | O30 |
| O31 | **Owner Tax Center** (`/owner/tax`) — 🔴 **A FOURTH DOCBLOCK DENYING A SHIPPED FEATURE.** The view's header said *"WITHHOLDING is NOT modelled"* — stale since the **#73 engine**; the component reads the REAL `withholding_minor` summed over committed statements. Corrected. ⚠️ **THREE OF THE FOUR (O22 tax PDF · O24 archive · O31) are about the TAX ENGINE specifically** — Wave D shipped without sweeping the surfaces that had previously refused it · **#73 holds exactly as at O22/O25**: withholding is REAL and the page prints an **AMOUNT, never a rate** — live *"None · withheld from payouts"* with the honest residency explanation, matching **Σ `withholding_minor` = 0** exactly (a genuine zero, not a refusal). **ZERO percentage signs anywhere on the page**; **mutation-proven** (making the card state *"applied at 15% of your gross earnings"* → red), and the opposite direction is covered by `OwnerTaxSurfaceTest` (the real withheld figure once a rate IS configured) · **no fabricated "valid ✓"** · **#60** only 2026, linking the real Annual Tax Summary PDF (O22's) · **READ-ONLY** — `tax_id` still NULL, `vat_registered` still true, owner `updated_at` unchanged · **#42/#28** self-resolved, no raw IBAN, no guest data · **DO31-1 LOGGED, not added**: the page carries no explicit *"not tax advice"* line (the PDF and Reports do). ⚠️ **Structurally it needs one less** — it presents **no rate and no computed determination**, only a real amount plus a link to the PDF that does carry it. Formal wording is **DO22-1, the founder's**; legal copy is not a design gate's to write · **contrast 16 → 4**: ⚠️ **the honesty text was checked FIRST per the named O22/O25/O28 pattern — and this time was NOT the worst reading** (*the pattern held as a PRIORITY, not a prediction*). `.vy-tax-lbl` **2.89–4.03** on the peach → ink, plus helpers/`*`/segment/archive line — **the FOURTH consecutive page** where shell greys can't reach AA over the hero gradient · ⚠️ **TWO readings were the O11 DARK-PLATE FP, caught BEFORE touching anything**: "About your tax" 1.00 and "Vyrobel keeps records…" 1.41 sit inside `.vy-card--dark`, where white-at-60% actually measures **5.78**; the probe now EXCLUDES `.vy-card--dark` subtrees (**12th probe FP**). Remaining 4 = brand mark + 3 mis-samples | O31 |
| O32 | 🏁 **Owner Agreement** (`/owner/agreement` + `/owner/agreement/pdf`) — **THE LAST OWNER PAGE.** ⚠️ **THE RATE IS AUTHORITATIVE HERE AND IT FOLLOWS THE ENGINE**: live **18%**, no 15% anywhere, matching `commission_pct = 18.00`, rendered through the shared `OwnerCommissionRate::display()` — and the existing guard proves it follows the DATA, not a constant (owner A `15.00` → "15%", owner B **22.75** → "22.75%", a value no literal could produce). **Mutation-proven** (forcing `display()` to return a fixed `'18%'` → red). The PDF shows the same 18% + per-villa 18%/18% — **SCREEN == PDF** · 🔴 **A SIXTH STALE CLAIM — a DUPLICATE docblock quoting the MOCK rate.** `commissionDisplay()` carried **two stacked docblocks**; only the second binds, and the first was orphaned text whose worked example read **"15.00%"** — the wireframe's mock rate — **on the one page that DEFINES the real one.** Merged; no example rate quoted at all now · **ACKNOWLEDGE observed READ-ONLY, append-only #7** — `agreement_amendment_acknowledgments` still **0**, agreements/amendments both 1, `max(updated_at)` unchanged; an existing guard proves a second acknowledge records no second consent · ⚠️ **#42 MUTATION-PROVEN — and my FIRST attempt proved NOTHING**: swapping `query()`→`withoutGlobalScopes()` left it GREEN because the real guard is an **explicit `whereIn('owner_id', …)`** the swap never touched; removing THAT line turns the cross-owner 404 test red *(the O12 lesson — check what you actually removed)* · **no fabricated terms** (5 honest "Not set" for NULL demo fields, guarded against rendering as zero; one real amendment, no multi-amendment fiction #60) · **the PDF's framing preserved**: *"…**not a signed legal contract**; your binding agreement is held by your operator"* · **no raw IBAN**, self-scoped, **keyboard 0/0/0** · **contrast — screen 7 → 4, PDF 4 → 2**: ⚠️ **the honesty text was the WORST reading on BOTH surfaces — the 5th and 6th occurrences, and the sharpest yet, because here it is the text DISCLAIMING the document's authority.** PDF `#A69A92` **2.74** on *"not a signed legal contract"* → 5.79, and `#8A8079` across **19 runs** 3.86 → 6.98 (guarded on the RENDERED document, mutation-proven, **PdfService untouched**); screen `#8A8079` **2.27/2.31** + `.vy-fx-note` **4.25** (the line explaining what acknowledging legally does) → ink · 📌 **PDF sweep: 5 owner documents clear, 11 templates + the 2.70 watermark remain — the ONE-GATE recommendation stands** *(🏁 the gate RAN on 2026-08-12 and closed it — really **17** templates, and the stamp proved INFORMATIONAL + fixable; §PDF-SWEEP)* | O32 |

## N.4c ⚠️⚠️ `expect(...)->not->toContain($needle, $message)` IS AN ALWAYS-PASSING ASSERTION

Found at gate **O1** while tightening a false-positive #39 guard. Pest's `toContain()` is **variadic over
needles** — a "message" passed as a second argument becomes a **second needle**, so the expectation reads
*"does not contain BOTH"*, which is true whenever the message sentence is absent. **It can never fail.**

**Mutation-proven, twice:** a literal `expenses@endif` was rendered into the owner Dashboard and
**`assertNoRawBlade()` — the shared guard for the `word@directive` Blade trap, called by 36 test files
across the owner AND operator realms — stayed GREEN.** ⚠️ That trap **fired on this very gate** (it 500'd
the page); had it degraded to *raw source on the page* instead, nothing in the suite would have caught it.

A sweep found **10 dead assertions in 5 files**, several load-bearing: **`GuestServiceTest`** (#28 — guest
PII in `audit_events` / `domain_events`), **`BackOfficeAccessTest`** (#42 — cross-owner name/money/unit on
operator routes), `DesignTokenContrastTest` (the AA token floor), `MaintenanceAssigneePoolTest`. **All were
repaired to `Assert::assertStringNotContainsString()` and every test still passes** — the invariants hold;
the assertions were simply unable to fail. The security-relevant ones were re-mutated and observed to fire.

**Standing rule from here on:** never write `expect(...)->not->toContain($needle, $message)`. Use PHPUnit's
`assertStringNotContainsString($needle, $haystack, $message)`, which takes a real message argument.

## N.4d ⚠️ THE PRE-AA GREYS SURVIVE IN 15 MORE PDF TEMPLATES — and a token needs a NAMED background

Two findings from gate **O2**, both realm-wide.

**(1) Documents are read on paper, and 15 of them still carry the pre-AA palette.** The owner Statement PDF
was measured from its OWN rendered content stream: `#8A8079` **3.86** and `#A69A92` **2.74** on white — and
`#8A8079` was the `.neg` class, **the commission and deduction figures themselves**. Fixed here to 6.98 /
5.79 (the operator Schedule-PDF pair). The same literals remain in **15 other templates** — remittance,
booking summary, annual tax, statement history, folio, VAT invoice, credit note, guest statement, refund
receipt, arrival, operator report, maintenance costs, agreement (+ amendment), booking confirmation. **Every
one is a document a customer reads printed. One sweep, not fifteen page gates.** ⚠️ Also surfaced, untouched
by the standing rule: `PdfService.php:54` hardcodes the export-audit watermark at `#a99b8e` — **2.70 on
paper**, on the line that records who took the document.

**(2) A "text" token is meaningless until you name the background it is text on.** `--vy-amber-text` was
tuned at O1 against a single sampled pixel. On the next page it measured **4.45–4.50** (marginal) and on the
**dark payout rail 1.87** — because darkening is exactly backwards there. ⚠️ The rail was **already failing
before O1** (2.72) and **every tone of that pill failed** (paid 1.77, draft 1.65): the tint fix deepened a
hole it did not create. Now: the token is tuned against the **worst** sampled background, and a dark-context
trio (`--vy-amber-on-dark` / `--vy-green-on-dark` / `--vy-muted-on-dark`) **mirrors** rather than darkens.
**Tune against the worst background the token will meet, and measure the pill's own composited pixel — not
the panel behind it.**

## N.4e ⚠️ D16-7 IS NOT OPERATOR-ONLY — in-flight money is invisible on the OWNER'S payouts page too

Found at gate **O3**, verified by construction. `OwnerPayoutService::pay()` writes **`processing`** on the
real-transfer path (`$result->pending ? 'processing' : 'paid'`) — i.e. exactly what happens once Stripe is
live. `OwnerPayouts::metrics()` builds **every** tile from two filters, `status === 'paid'` and
`status === 'pending'`, and the page renders exactly three tiles. **A payout that is genuinely in flight
therefore appears in none of them**; `held` is absent from the tiles as well (it at least drives the on-hold
banner). The owner sees an unchanged "Paid this year" and a "Pending" that has dropped to zero while their
money is actually moving. **Logged (§O3 DO3-1), not fixed — it is a computation, and this pass is design-only.
It becomes wrong the day Stripe goes live, not before.**

⚠️ **The same blind spot was written into the class docblock**, which declared
`Payout.status ∈ pending/paid/failed/reversed` — omitting `processing` and `held`. Corrected in place. *When a
comment enumerates the states, an omission there is a preview of the bug in the code beneath it.*

⚠️ **A second O3 finding — a money list with no deterministic order.** `OwnerPortalService::payouts()` sorts by
`created_at` DESC alone, and **all six demo payouts share one timestamp**: the sort key distinguishes nothing,
and the history renders **oldest-first**. This is not a demo artifact — `PayoutRunService` creates a run's
payouts in a single pass, so ties are the normal case. Needs a ULID tiebreaker in that ONE shared read (the
Dashboard, Statement, Payouts page and the CSV export all consume it, so it must not be patched per-page).

## N.4f ⚠️ VERIFY THE GATE BRIEF'S PREMISE — O4 shipped with an inverted one

The O4 brief described the owner Rates page as "the GUARDED path: the owner proposes, `RateProposalService`
validates against the guardrail". **The direction is the opposite**, and both the code and the wireframe say
so: `propose()` is the **OPERATOR** side (the guardrail constrains what the *manager* may propose — floor /
ceiling reject, ±tolerance auto-applies, anything larger waits); the **owner** approves, declines, counters,
*sets* the guardrail, and prices their own exclusively-owned room types by **direct apply**. The prototype's
own header: *"Review the pricing your manager proposes and set the limits they work within."*

**Had the brief been taken at face value, the gate would have built a "propose" flow that does not exist and
pointed a guardrail readout at the wrong actor.** This is the fifth time the pass has had to correct a stated
premise (N.8's "verify the PROMPT's own premise"); it is now the single most reliable source of near-misses.

⚠️ **And the trap the brief was really about — a fabricated guardrail — the page passes.** With **0
`RateGuardrail` rows in the entire database**, `propose()` has no floor/ceiling branch and no auto-approve
branch, so every proposal stays `pending`; the card's ±0% state says exactly that. **No "within policy"
badge, no invented limit, no implied protection.** Guarded enforcement is covered by `RateProposalTest`,
which seeds **real** guardrail rows — so it is not vacuous, and none was seeded here just to enrich a
screenshot.

## N.4g ⚠️ `outline:none` WITHOUT A `:focus` RULE — a repeating, invisible keyboard defect

O4 found it on Rates and **logged it for the My Units gate**; O5 closed it there and found **two more
instances on the same page**. The pattern is a styling reset (`outline:none` on an inner input so the wrapper
can carry the visual) where **no replacement `:focus` rule was ever written** — leaving the control with **no
visible focus indicator at all** (WCAG 2.4.7). It is invisible to a contrast sweep and to any test that does
not drive focus, and it lands on **editors**: the nightly rate, the weekend uplift, the min stay, the
counter-offer, the message box.

**Grep for `outline:\s*none` and check for the paired `:focus` / `:focus-within` rule; the fix belongs on the
WRAPPER.** ⚠️ A probe that only reads `outline` will miss controls whose ring is a `box-shadow` — check both,
or it reports false failures (it did, on `.vy-input`).

⚠️ **Companion lesson from the same probe:** an accessible-name check must resolve **`label[for=]`**, not just
ancestor `<label>`s — O4's probe reported two FALSE "unnamed" date inputs, while O5's reported three that were
genuinely unnamed. **Correct the probe, then trust it.**

## N.4h 🏁 THE P29 COLOUR-ONLY-UNREAD ITEM IS CLOSED — and a shared partial needs its class in BOTH shells

Operator GATE 29 fixed the unread state on the operator page and logged the same **WCAG 1.4.1** defect as
**still open** on two surfaces. **O6 closed both**, verified still present beforehand: the owner feed page and
the **shared `components/notifications/bell.blade.php`**, which renders in BOTH shells — so the shared partial
was fixed **once** and reached both bells (Principle 3 working as intended).

⚠️ **The corollary that nearly broke it:** `.vy-sr-only` was defined **only in the operator layout**. A shared
partial emitting `class="vy-sr-only"` was therefore hidden in operator and **plainly visible in owner**. *A
shared partial cannot depend on a class only one shell defines* — when promoting a fix into a shared file,
check every shell it renders in.

**Mutation-proven three ways** (colour-only restored on the feed → red; on the shared bell → red;
`.vy-sr-only` deleted from the owner shell → red). ⚠️ The third assertion was **initially vacuous**:
`toContain('.vy-sr-only')` matched the class *attribute* in the bell markup and passed with the CSS rule
deleted. Tightened to assert the **rule** and re-mutated. *Even a guard written by someone watching for
vacuity can be vacuous — mutate every branch, not just the first.*

⚠️ **Two further O6 findings.** (1) **`Owner Notifications.html` is the PREFERENCES page, not the feed** — it
belongs to `/owner/notification-preferences`, and **the live feed has no prototype at all**; recorded so a
later gate does not restyle a feed into a preferences page. (2) **Blade compiles component tags inside CSS
comments** — writing the bell's `<x-…>` tag name in a comment inside the layout's `<style>` block rendered the
whole component into the stylesheet and 500'd every owner page. **Comments are not inert to Blade.**

## N.4i ⚠️ A TOKEN PLACED ON A GRADIENT HAS A RANGE OF BACKGROUNDS — the third token to need re-tuning

`--vy-on-grad` was set at O4 from **one** sampled gradient pixel (4.68 there). At O7 the same token measured
**4.42** on a lighter point of the *same* gradient, on the same element class. Re-tuned to the lightest
sampled point: **5.01** there, **5.31** back at O4's sample.

That is now **three tokens in a row** fitted to a single sample and wrong on the next page — `--vy-amber-text`
(O2), `--vy-green-text` (O3), `--vy-on-grad` (O7). **The rule is settled: sample a token's background at
several points across the surfaces it lands on, tune to the WORST, and re-measure the pages already passed.**
A page background that is itself a gradient guarantees a range, not a value.

⚠️ **A second O7 lesson — a fix can depend on an earlier gate.** The per-event SMS cells announced their
deferred state with an `aria-label` on a **non-interactive `<span>`** (the P29 pattern again). The correct
fix — real `.vy-sr-only` text, as the operator prefs page already used — was only possible because **O6 had
just added `.vy-sr-only` to the owner shell**. Backlog items compound: closing one unlocked the next.

## N.4j 🏁 THE on-grad CHURN IS RESOLVED BY MEASUREMENT — and the OPERATOR shell fails the same test

**Owner-8 ended three gates of re-tuning by measuring instead of sampling.** `--vy-on-grad` /
`--vy-on-grad-soft` were fitted to single pixels at O2 and O7. Here the real range was taken empirically:
**7,502 background pixels sampled from behind every element that actually uses the tokens** (sidebar nav +
labels + topbar sub-line), darkest composited point **rgb(239,174,135)**.

⚠️ **Premise correction:** these are **DARK text on the PAGE gradient** (`--vy-bg`, peach→grey + ambient
orbs), not the accent gradient — so the worst case is the **DARKEST** point, not the lightest. Result:
`--vy-on-grad` already cleared at **5.15** and is unchanged; **`--vy-on-grad-soft` was FAILING at 4.26** and
had never been re-tuned in its own right → rgb(82,73,67) = **4.63**, the softest value that clears.

⚠️ **The same method exposes a CROSS-REALM failure.** The **operator** shell's darkest on-grad point is
**rgb(216,129,89)** (its orbs sit under the sidebar), where **both** its tokens fail: **2.95** and **2.77**.
The operator realm's own a11y gate (`93247e7`) claimed "4.74 / 4.93" — **measured against one pixel**.
Clearing AA there needs ≈ rgb(51,45,41), a visibly darker sidebar label across all 42 completed pages.
**LOGGED for its own gate** — a closed realm's shell must not be changed inside an owner page gate.

**The settled rule: SAMPLE a token's real background range across the elements that use it, tune to the
worst case, and never trust a single pixel.** Three tokens (`--vy-amber-text`, `--vy-green-text`,
`--vy-on-grad*`) have now needed this.

## N.4k ⚠️ A MULTI-STEP FORM CAN BE SUBMITTED FROM A STEP THAT IS NOT ITS LAST

O9's headline finding is not a style defect and would never have surfaced from reading the page: **pressing
Enter in any text field of the add-unit wizard submitted the whole form from step 1**, because implicit
submission targets the form's first submit button *regardless of whether that button is displayed*. The
server accepted it — the wizard's four steps are a client-side convenience, and only two of the fields it
collects are required — so a single keystroke filed a real request on the operator's desk.

**The general rule: if a wizard's steps are client-side, its LAST step is not the only way to submit it.**
Any multi-step form with one `type="submit"` must either prevent implicit submission until the final step or
have no submit button in the DOM before it. Check every remaining wizard (`New Reservation`, `Group Booking`,
the signup wizard) when their gates come round.

⚠️ **And the corollary about probes**: the fix briefly looked wrong because
`document.querySelector('[x-data]')` matched the **shell's** Alpine root, not the wizard's — the shell has
its own `x-data` for the notification popover. *When a verification contradicts a change you just reasoned
through, suspect the probe first.* (Owner-8 had the same shape: two "unnamed" inputs were an artefact of a
probe that only walked ancestor labels.)

## N.4l ⚠️ THE BASELINE SET GAINED A NEW MEMBER — and the mechanism is UNKNOWN, not guessed

One full-suite run during O10 showed **10** non-green: the documented nine **plus**
`PublicStorefrontLandingTest::it shows the real derived rating and review, never a fabricated count`.

**It is not the gate's change**, established rather than asserted: the diff touches only
`livewire/owner/unit-detail.blade.php`, which `/stay/{org}` cannot render; a stashed full run at HEAD showed
the usual nine (with that test passing); **a second full run WITH the change was clean at nine**; and the
test passes **14/14** in isolation.

⚠️ **A hypothesis was formed and then REJECTED by measurement.** The test ends in
`assertDontSee('369')` — a substring check against a page carrying generated identifiers — so "a random
ULID/slug happened to contain 369" looked like the mechanism. Measured: a random 8-char slug contains `369`
**0.0035%** of the time and a ULID **0.0045%** — orders of magnitude too rare. **The mechanism is unknown**,
and it is recorded as unknown rather than written up as a finding. Next time it appears, capture the
assertion message before anything else.

**The baseline is therefore 5–10 non-green, not 5–9** — and the practical rule stands: the set is a RANGE,
re-measured every gate, and a new member is isolated (and stash-proven) before it is called a regression.

## N.4m ⚠️ A COMMENT CAN DEFEAT THE GUARD THAT PROTECTS THE FIX IT DOCUMENTS

O11 tried to write the obvious regression guard for the statement PDF — *this document contains neither
pre-AA grey* — and it **failed on the first run**. The reason: the note explaining O2's fix was a **CSS**
comment inside `<style>`, and **a CSS comment is RENDERED**. So the very prose describing the fix was
shipping `#8A8079` and `#A69A92` inside every generated statement, and no substring guard could ever pass.

Moving the prose to a **Blade** comment (stripped at compile) removed it from the output and made the guard
possible; it is now mutation-proven. **This is the third form of the same trap:** O6 found Blade *compiles
component tags* inside CSS comments; `DesignTokenContrastTest` (§J) was tripped by a token literal a comment
mentioned; now a comment defeated a guard outright.

**The rule: documentation that names a forbidden literal must live where it is NOT rendered.** In a Blade
view that means a Blade comment, never a CSS one. And when a substring guard fails on its first run, suspect
the comment before the code.

⚠️ **A second O11 note — "read-only" is not the same as "writes nothing".** The statement export writes an
`export_downloads` audit row on every download, by design (Wave B · GATE 5 made the watermark + download log
real). Probing an audited export therefore leaves rows behind; what must be unchanged is the **money**.

## N.4n ⚠️ A DERIVED BADGE CAN BE TRUTHFUL BY ACCIDENT — check the FACT it claims, not the fact it reads

O12 found the owner Settings hub rendering a green **"Verified"** pill whenever `pending_email === ''`. That
condition means *"no email change is pending"*. The badge claims *"this address is verified"*. **Two different
facts** — and a user whose `email_verified_at` is null would have been shown a green Verified beside an
unverified address.

⚠️ **It was not lying today**, and that is the interesting part: every owner-realm path (both invite services
and the email-change confirmation) sets `email_verified_at` on accept, so the two facts happen to coincide.
**The badge was truthful by accident rather than by construction** — the kind of thing that becomes a real
defect the moment someone adds a path that doesn't verify. Recorded as **LATENT**, fixed anyway, and locked
with a guard.

**The rule: for any badge, pill or ✓ that asserts a fact, read the condition and ask whether it tests THAT
fact.** This is the softer sibling of GATE-24's hardcoded green ✓ that lied outright — here the value was
derived, just from the wrong column.

⚠️ **And a mutation that does not mutate proves nothing.** The first attempt to mutation-prove the new guard
used a `perl` substitution whose escaping silently failed; the test "passed" against an unchanged file and
briefly looked like a vacuous guard. **Assert that the mutation actually applied** (diff, grep, or an
`assert` in the edit script) before reading the result.

## N.4o ⚠️ A SHARED PARTIAL CANNOT CARRY ONE SHELL'S LAYOUT CONSTANT — and a brief can ask you to make a page lie

**Two lessons from O13, the owner Security page.**

**(1) The third form of the shared-partial trap.** `livewire/partials/two-factor-panel.blade.php` renders on
BOTH `/app/security` and `/owner/security`, and hardcoded `max-width: 560px`. That is the **operator** page's
column — every card there is 560px. On the **owner** page, where every card is 1184px, the shared panel
rendered as a lone half-width outlier. Fixed by moving the constraint to each caller, with the operator page
**measured before and after and proven unchanged**.

The family so far: **O6** — a shared partial cannot depend on a class only one shell *defines*. **O13** — a
shared partial cannot carry a layout constant only one shell's *design* uses. **Anything shell-specific
belongs to the caller.**

**(2) ⚠️ A brief's security assertion can be wrong in the dangerous direction.** The gate brief stated *"the
12+ char policy is honest, never weakened"*. **No such policy exists** — all ten password paths in the
codebase are `PasswordRule::min(8)`, and the page truthfully says *"At least 8 characters"*. Acting on the
brief would have made a **security surface overstate its own protection**: the same fabrication defect this
pass hunts, pointed the other way. It is not enough to check that copy isn't *weaker* than the code — check
that it isn't *stronger* either. **Verify the policy against the validator, every time.**

⚠️ Related: the page's own probe reported `has12: true`, which turned out to be CSS `12px`. **Fourth
probe-induced false positive of the pass** (after O8, O10, O11) — the rule stands: suspect the probe first.

## N.4p ⚠️ A GREEN TEST CAN DEFEND NOTHING — **SEVEN FORMS**, all found only by MUTATING

⚠️ **THE CONSOLIDATED LIST (the owner realm's most valuable output). Every one was green, and every one was
found by changing the code and watching the test *not* fail.**

1. **A variadic `toContain`** — `not->toContain($needle, $message)` treats the message as a second needle, so
   it can never fail. `assertNoRawBlade()` was dead across **36 files** (Owner-1).
2. **A mutation that never applied** — a `perl` substitution silently no-op'd, so the "passing" run was
   meaningless (Owner-12). **Assert the edit landed before reading the result.**
3. **A fixture that cannot reach the assertion** — a `DontSee` guard whose fixture never renders the forbidden
   thing (Owner-16). **A `DontSee` guard must live where the thing it forbids WOULD render.**
4. **An assertion on a STATIC LABEL, not behaviour** — `assertSee` on a tile's hardcoded caption passes in
   every data state (Owner-17).
5. **A mutation that applies but never reaches the OUTPUT** — the view was made to echo a key the component
   does not provide, so it rendered an empty string (Owner-19).
6. **A fixture that cannot reach the BOUNDARY CONDITION** — a page-count guard whose fixture caps at two rows,
   which fit either way (Owner-20). ⚠️ **That guard was REJECTED, not shipped: a guard that cannot fail is
   worse than none, because it reads as coverage.**
7. **A needle taken from an ASSUMED parameter instead of the RENDERED output** (Owner-25). Too broad and it
   **fails on correct markup**; too narrow and it **can never fail**. Repairing one #60 guard took three
   needles, two of which would have shipped broken.

**Plus two rules the same investigations produced:**
- **A comment can satisfy the needle** — a guard's literal was matched by the code comment quoting it, so
  deleting the real binding left the test green (Owner-23; the O11 comment trap on the test side).
- ⚠️ **THE MATURE RULE (Owner-32): when a mutation PASSES, the first question is what you ACTUALLY removed.**
  A #42 attempt swapped `query()` for `withoutGlobalScopes()` and stayed green — because the real guard was an
  explicit `whereIn('owner_id', …)` the swap never touched. **A passing mutation demands investigation, not a
  conclusion.**

**RULE:** prove every guard by mutation; assert the attribute/RULE form, not a loose string; keep comments
from carrying literals a guard asserts; reject a guard that cannot fail.

### The original O16 note (kept — it is where the pattern started)


O16 tightened a refusal guard on the banking page — `assertDontSee('Verifying')` was forbidding the weaker
claim, so **"Verified"** was added, the stronger one. The mutation (injecting a fake `Verified` badge into the
account row) **PASSED**. The assertion was dead: it lived in the *"defers the live rail honestly"* test,
whose fixture **adds no payout method**, so the account row never renders there at all.

**This is empty-fixture vacuity, and nothing about the assertion's syntax revealed it.** It was needle-only,
correctly negated, on the rendered output — and still could never fail. Only running the mutation exposed it.
Relocated to the test that actually renders an account; re-mutated; now genuinely red.

**The rule: a `DontSee` guard must live where the thing it forbids WOULD render.** Before trusting one, ask
what the fixture actually puts on the page — and prove it by making the forbidden thing appear.

⚠️ This is now the **third** distinct way a green test has defended nothing in this pass: the variadic
`toContain` (§N.4c), a mutation that silently didn't apply (§N.4n), and now a fixture that can't reach the
assertion. **All three were found by mutating, never by reading.**

## N.4q 🏁 D16-7 HAS A REFERENCE IMPLEMENTATION — and its buckets were unguarded on the page that gets it right

**O17 inverted the expected finding.** Balance was briefed as another D16-7 victim; it is the **one owner
surface that gets `processing` and `held` right**, via its own `rowStateMeta()` rather than the shared
`payoutStatusMeta` that collapses both into *"Payout pending"* (O14):

- **On hold** = persisted `held` + a pending payout the tax-hold pauses
- **In transit** = `processing` (an initiated transfer awaiting its webhook) + a released pending payout

⚠️ **The D16-7 gate should therefore not design a fix — it should copy this one.** Make the shared helper
match `OwnerBalance::rowStateMeta()`, then re-check Payouts (O3) and Documents (O14).

**But the buckets themselves were completely unguarded.** Dropping either from the component left every
Balance test green *and* `PayoutEngineSurfaceTest` green — the one test that creates a genuine `held` payout —
because **its identity is computed from the DB rather than from the component**, and its `assertSee('On
hold')` passes on the tile's static label. A guard now asserts the **component's own buckets**;
mutation-proven both ways.

⚠️ **This is the O16 lesson repeating one gate later**: the hole was found *only* because the mutation was
run and **passed**. Two gates running, a green suite has hidden a real gap that no amount of reading would
have shown. **Mutate the thing you are about to trust — and when the mutation passes, that is the finding.**

⚠️ **Also recorded: a disabled control can be the RIGHT answer to a contrast failure.** The "Withdraw" button
measures 2.33 and was deliberately left: it is formally exempt (inactive components), and darkening a control
that **cannot act** would make it look live — which on a money page is the worse honesty failure.

## N.4r ⚠️ THE MIN-BAR FLOOR HAS A CSS FORM — and a chart can lie through its AXIS, not just its bars

**Two O18 findings that widen an existing gate.**

**(1) The floor is not only `max(2, $pct)`.** Owner Bookings carried it **twice**: the PHP form in the view
*and* a CSS `min-height: 3px` on the bar container. The planned fix — move the honest-zero rule onto the
shared `<x-charts.bars>` (§N.9 #4) — **would not have caught the CSS one**. ⚠️ **That gate must grep for
`min-height` on bar/stack classes too**, not just for `max(`.

**(2) A chart can misreport through its AXIS while every bar is correct.** `chart()` grouped only the months
that *had* bookings, so an empty month vanished from the x-axis — and the net-revenue **polyline** then joined
October straight to December as though they were consecutive, compressing a two-month gap into one step. Every
individual figure was right; the *time base* was wrong.

**The rule: for any time series, the axis is a CALENDAR, not a list of periods that happen to have data.**
Fill the range, let an empty period be a genuine zero, and check what the connecting line does across it. This
is a new member of the honest-chart family alongside the min-bar floor and the `max(2,pct)` stub.

## N.7 🏁 OPERATOR COMPLETE (42/42) — **OWNER 18/32 UNDERWAY**, then GUEST (32).

**The operator realm is finished.** 42 prototypes, 34 gates (`0aa9ede`→P34), every page decoded, diffed in a
real browser, fixed where it was a design gap, and **logged where the product honestly refuses**.

⚠️ **What the operator pass actually found** — worth carrying into the owner realm:
- **Fabrication concentrates on surfaces that look authoritative**: an invented open-rate on an email card, a
  city beside a last sign-in, a "verified" SMS number, a sync that never ran, engagement stats with no
  tracking. The pattern: *the more a figure would be trusted, the more the mock invents it.*
- **Two independent a11y axes** — contrast AND operability. A page can measure 0 contrast failures and still be
  unusable by keyboard (P30), or clip real data inside an iframe while reporting no overflow (P32).
- **Duplication is the enemy of a fix**: the same "Active" pill failed AA twice (P31, P32) because it was
  hand-written twice; one shared class fixed five views at once (P33).
- **A green test can be defending nothing** — proven twice (P27, P33). Mutate every guard.
- **Docblocks go stale faster than code**: P34 found the PDF template still claiming "no unassigned-shift
  model" and "no published state" after both had shipped.

⚠️ **NOTES CARRIED FORWARD (all still apply).** ⑴ Measure BOTH axes, visible-scoped, names via wrapping labels.
⑵ Scope a guard to the CLAIM (the CSS-comment trap, twice). ⑶ Baseline is a RANGE, 5–9 (J.quater). ⑷ A green
test can defend nothing. ⑸ Reflected input is not disclosure. ⑹ Prototypes can contradict each other. ⑺ `title=`
is not a name. ⑻ Look for controls that cannot be OPERATED. ⑼ A nav link needs the TARGET's gate. ⑽ Measure the
states your change introduces. ⑾ Check embedded content's responsive state. ⑿ A shared class is fixed once; a
duplicate must be found twice. ⒀ Read a label against the CONTROL it drives. ⒁ ⚠️ **NEW (P34) — verify the
PROMPT's premise**: "Housekeeping lacks schedule.view" was false (9 roles hold it), which would have made the
#30 assertion vacuous. ⒂ ⚠️ **NEW (P34) — a PDF's palette is read on PAPER**: the pre-AA greys survived there
long after the screens were fixed, on the very figures a rota exists to communicate.

**⬅ THE OWNER REALM IS UNDERWAY — 8 of 32 (O1 Dashboard, O2 Statement, O3 Payouts, O4 Rates, O5 My Units, O6 Notifications, O7 Notification Preferences, O8 Insights).** Then **GUEST (32)**, untouched.

**Then the OWNER (32) and GUEST (32) prototype sets — entirely untouched by this pass.**

## N.8 THE FIVE ESTABLISHED PRINCIPLES — the operator realm's distilled lessons (carry into OWNER + GUEST)

These five are what 42 pages actually taught. The numbered lessons below (21+) are the worked detail.

1. ⚠️ **FABRICATION CLUSTERS ON WHATEVER LOOKS MOST AUTHORITATIVE.** The mock invented an email **open-rate**,
   a **city beside a last sign-in** (which reads as *security evidence* — exactly what a user checks to judge
   an unfamiliar session), a **"verified" SMS number** (asserting a verification flow that never ran), a
   **sync** that never ran, **per-channel booking stats** with no channel attribution, and a **permission
   grant** on an RBAC matrix. *The more a figure would be trusted, the more the prototype invents it — so
   refuse hardest exactly there.*
2. ⚠️ **TWO INDEPENDENT ACCESSIBILITY AXES: CONTRAST and OPERABILITY.** They do not predict each other. P30's
   page measured **0 contrast failures** while its **only controls were unreachable by keyboard**
   (`display:none` removes an input from the tab order *and* the a11y tree — use `appearance:none`/`opacity:0`
   and keep a `:focus-visible` ring). P32's preview **clipped real data inside an iframe** while the page
   reported **no overflow**. Neither axis is predicted by render type (a Livewire page with zero raw hex still
   failed AA 8×, via accent-as-text). **Measure both, every page**; scope the keyboard probe to **visible**
   controls; check names via the **wrapping label** too; measure **the states your own change introduces**;
   and measure **documents as they'll be read — on paper** (P34's PDF still carried the pre-AA greys on the
   per-cell shift *times*).
3. ⚠️ **DUPLICATION DEFEATS A FIX.** A shared definition is fixed **once** — `.vy-tag.tone-confirmed` fixed
   **five views** in one edit (P33). A duplicate must be found **twice** — the same Active pill failed AA at
   **P31 and again at P32**. Promote at the third copy; and **never a second computation**: the schedule PDF
   and the grid share `RosterWeek` (D9-1), so a figure the shared source doesn't expose gets **logged**, not
   recomputed in the template.
4. ⚠️ **A GREEN TEST CAN BE DEFENDING NOTHING.** Proven twice by mutation: a **#17 rollup assertion** that
   compared €0.00 to €0.00 on a page of zeros (P27), and a **geolocation guard** that passed with the
   fabrication injected because the fixture seeded **no `sessions` rows** (P33). **Prove every new guard fails
   without its fix**, and watch specifically for **empty-fixture vacuity**.
5. ⚠️ **COMMENTS GO STALE FASTER THAN CODE.** Docblocks claimed a comparison was "deferred" after it shipped,
   "5 real guest emails" when there were 7, "no unassigned-shift model" and "no published state" after both
   existed (P34). **Verify prose against current behaviour and correct the comment in the same change.**

**Also established:** ⚠️ **verify the PROMPT's own premise** — a JSON-merge hazard where the table is typed
columns; a #6 guardrail that lives on another page; an "unpermitted" role that #43 makes broad (9 roles hold
`schedule.view`). ⚠️ **Surface decision evidence without deciding** — the billing flip's direction was
measured and handed over, no side picked. ⚠️ **A refusal can be STRUCTURAL** — name-only *by construction*
(SearchService), an IBAN that **cannot** be emitted raw (`Iban::masked`), a test-send with **no recipient
input** at all. ⚠️ **An unverified guess is NOT a finding** — when a sweep comes back negative, record the
negative (P30's `display:none` sweep found exactly one instance).

## N.8b ⚠️ THE STALE-CLAIM PATTERN — a comment, a name or a value that contradicts the code (SIX instances, TWO shapes, ONE root cause)

The single most repeated finding of the owner realm. A claim written **near** the code outlived the code, and
in every case a reader who trusted it would have done the wrong thing.

**Shape A — DENIES a shipped feature (5×).** O22 (a docblock saying the tax PDF carries "no withholding") ·
O24 (a docblock saying "there is no tax engine, and its PDF does not exist") · O28 (a docblock listing the AI
Q&A under REFUSED) · O31 (a view header saying "WITHHOLDING is NOT modelled") · **O30 — a TEST NAME**
(*"…invents no approval workflow"*), which is **worse than a docblock**: a test name surfaces in CI output and
in a grep, where it reads as an assertion the suite is making.

**Shape B — MISSTATES a value (1×).** O32: two stacked docblocks on one method; only the second binds, and the
orphaned first quoted **"15.00%"** — the wireframe's *mock* rate — as a worked example **on the page that
defines the real 18%**.

⚠️ **ROOT CAUSE (O31): three of the five denials are the tax engine specifically.** Wave D shipped it
**without sweeping the surfaces that had previously, and correctly, refused it**. Generalised:

> **When a long-refused capability finally ships, the refusals it invalidates are scattered across every
> surface that documented them — docblocks, view headers, and test names alike.**

**STANDING PRACTICE (§N.8e):** shipping a deferred capability is not done until you have grepped the surfaces
that documented its refusal and updated them. **RULE:** verify a docblock's or test name's claims about
tests, about absence, **and about values** — not just its citations.

## N.8d ⚠️ THREE CLASSES OF DISHONEST DISPLAY (each found live, each now named)

**① FABRICATED STATUS HAS TWO DIRECTIONS.** Overstating (a false "done"/"verified"/"all set"/a green
"operational" dot) **misleads**. Understating (a false "not done") **issues a false instruction** — O29's
`hasPayout` was gated on `units` rather than the fact it claimed, so an owner who had already saved a payout
method was told to *"add a bank account"*. ⚠️ **The understating direction is the more damaging: it does not
merely mislead, it directs the user to act.** RULE: a status derives from **the real fact it claims** — not a
proxy, not a hardcoded label, not a convenient conjunct.

**② A FALSE CLAIM'S VISUAL SIGNAL OUTLIVES ITS TEXT (O28).** An earlier pass deleted the words *"All systems
operational"* and a guard still forbids them — but the **green dot** survived, asserting the same claim just
as loudly. This unifies several findings that looked unrelated: the **min-bar floor** (a bar claims a non-zero
value — O18/O25), the **all-five-stars** glyph row (claims a perfect rating — O26), the **hardcoded "1"**
badge (a digit claims a count — O27), and the dot itself. **RULE: a text guard alone is incomplete — remove
the visual assertion too.**

**③ THE HONESTY TEXT IS SYSTEMATICALLY THE LOWEST-CONTRAST TEXT ON THE PAGE.** The words that make a page
honest are styled as fine print: O22 *"not tax advice"* **2.74** · O25 *"EXAMPLE rates"* **3.82** · O28 the
AI-verification notice **4.48** · O32 the authority disclaimer, **the worst reading on both the screen and the
PDF** — the sharpest form, because the least legible sentence was the one disclaiming the document's own
authority. ⚠️ **This is a PRIORITY, not a PREDICTION**: O31 checked it first and found it was *not* the worst.
**RULE: on any claims/legal/tax/AI surface, measure the honesty text first — then keep measuring.**

## N.8e ⚠️ THE CONTRAST PROBE IS AN INSTRUMENT WITH A KNOWN ENVELOPE (hardened + characterized, O26→O31)

12 probe false-positives this pass — **one of them self-authored**. The probe is now documented rather than
trusted:

- **O26 — when it breaks, publish NOTHING.** The validated method (hide text → screenshot → sample the
  composited pixel) can fail **environmentally**. A CSS-compositing fallback written in its place was
  **unsound** (it scored white text at 1.0 against a layer that is actually painted over) and its output was
  **discarded**. Numbers from a probe known to be unsound are worse than no numbers.
- **O27 — the timeout is environmental**: `browser_close` + navigate recovers it. Then go back and measure the
  page you deferred.
- **O29 — HARDENED**: it now asserts every candidate computes to a **transparent fill before capturing**, and
  **freezes entry animations** (cards fade in on a delay, so an early screenshot samples the wrong opacity).
- **O30 — CHARACTERIZED**: reliable on **body text**, unreliable on **small controls** — pills and a 29px
  button mis-sample against pure white or an off-element background. Verify small controls directly.
- **O31 — excludes `.vy-card--dark` subtrees**: the O11 dark-plate FP recurred (white-at-60% on a dark card
  reads 1.00 but actually measures **5.78**).

**RULE: score the REAL composited plate; correct the probe, not the page; state the envelope wherever the
numbers appear.**

## N.8f ⚠️ THE DUPLICATION BACKLOG — Principle 3's unpaid debt (all point at the backend/refactor phase)

- **DO23-2 — the two calendar grids are DUPLICATED, not shared.** O10 fixed the keyboard lockout on Unit
  Detail; O23 found it **still live** on Block Calendar and had to re-make the fix. → Extract ONE day-cell
  partial. **Lesson: after fixing any defect, grep for a second copy of the widget.**
- **DO25-1 — the Reports month chart is a LOCAL implementation**, not the shared `<x-charts.bars>` — which is
  precisely why the min-bar floor survived there after being fixed elsewhere (now seen in 3+ chart locations).
  → Consolidate, carrying honest-zero (**PHP `max(2,pct)` AND CSS `min-height`**) and the contiguous-time-axis
  rule (§N.9 #4).
- **DO10-4 — the ★ glyph is duplicated across 11 files.** O26 extracted `<x-star-rating>` and wired Reviews;
  **10 call sites plus O26's own two remaining spans are unmigrated**, and O26's measured tail is unfixed.
- **The translucent-band contrast shape recurred on FOUR consecutive owner pages** (O28–O31): shell greys land
  on the hero gradient and cannot reach AA, needing `--vy-ink` **page-scoped** every time. → A realm-close
  shared-fix candidate on the shell's translucent KPI-band/rail pattern. ⚠️ **Never retune the shell's own
  definitions from a page gate** — that changes every owner page.

## N.8c ⚠️ THE TWO STANDING TRAPS — check both on every remaining gate

**TRAP 1 — `expect(...)->not->toContain($needle, $message)` CAN NEVER FAIL.** Pest's `toContain()` is
**variadic**, so a trailing "message" becomes a SECOND needle and the negation is satisfied by any string
missing either one. Found at OWNER-1 and mutation-proved: `assertNoRawBlade()` — used in **36 test files
across both realms** — could never fail, and a raw `expenses@endif` stayed green. **10 dead assertions in 5
files were repaired**, including **#28 guest-PII-in-audit-events** and **#42 cross-owner money**; all still
pass. ⚠️ **RULE: write `not->toContain` guards NEEDLE-ONLY, or attach the message via a separate `->and()`.**
⚠️ And `toContain('.some-class')` matches a **class attribute**, not a CSS **rule** (OWNER-6) — assert the
rule or the behaviour, never the selector string. **Verified clean for 10 consecutive owner gates.** Detail:
§N.4c.

**TRAP 2 — A CSS COMMENT IS RENDERED; A BLADE COMMENT IS NOT.** Three distinct forms have now bitten:
1. **OWNER-6** — a `<x-…>` component tag *named inside a CSS comment* was **compiled by Blade** and rendered
   into the stylesheet, **500'ing every owner page**.
2. **`DesignTokenContrastTest`** (§J) — a whole-file substring guard matched a token literal that only
   appeared *in a comment*, reporting a failure that was not an AA regression.
3. **OWNER-11** — the comment documenting O2's contrast fix **shipped the OLD pre-AA hex values into every
   generated statement PDF**, which both hid the regression for a full pass and made the obvious substring
   guard impossible to write.

⚠️ **RULE: never put design values, component tags, or fix-notes in a CSS comment — use a Blade comment**
(stripped at compile). ⚠️ **And GUARD a document/PDF contrast fix against the GENERATED output** — O2's fix
was correct but unguarded, and survived a whole pass unnoticed. Detail: §N.4m.

## N.9 ⚠️ REOPENED OPERATOR-REALM ITEMS — **6 items · design-matched is NOT a11y-closed** · each needs its OWN gate

The operator realm is 42/42 **design-matched**, but the owner pass surfaced six items that touch it. ⚠️ **Each
needs its OWN gate. None may be half-fixed from inside an owner page gate** — that is the discipline that kept
`--vy-faint` and the operator shell tokens from being changed under 42 closed pages without verification.

| # | Item | Evidence | Why its own gate |
|---|---|---|---|
| 1 | **The operator shell's on-grad tokens FAIL at the true darkest point** | gate `93247e7` set `--vy-on-grad` / `-soft` from **one** sampled pixel and claimed 4.74/4.93; measured against the real darkest point (`rgb(216,129,89)`) they are **2.95 / 2.77**, under AA | the fix needs ≈`rgb(51,45,41)` — a **visibly** darker sidebar label across all 42 closed pages |
| 2 | **§N.4k implicit submit** — a multi-step form can be submitted from a step that is not its last | OWNER-9: Enter on **step 1** of Add-Unit filed a genuine `unit_requests` row (the server requires only name + kind) | the operator wizards (**New Reservation**, **Group Booking**) and any other multi-step form need the same check |
| 3 | **Keyboard-unreachable `div@click` interactive grids** | OWNER-10: **31 of 36** controls on the availability calendar were clickable `<div>`s with no tabindex/role/key handler — and clicking a day writes through `OwnerBlockService`, taking inventory off-sale | the operator **Calendar, Front Desk, Housekeeping board, Maintenance board** are the same drag/click shape |
| 4 | **The `max(2, $pct)` min-bar floor** draws a stub for a genuine 0% | recurred on **FOUR** owner pages (Dashboard, Statement, Insights, Unit Detail ×2) | belongs on the shared `<x-charts.bars>` honest-zero rule — **fix once**, not per page |
| 5 | **The realm-wide PDF-contrast sweep** | ~**15** templates still carry the pre-AA literals, plus `PdfService.php:54`'s watermark at **2.70** on white | one sweep, not 16 hand-fixes; ⚠️ **never touch `PdfService` from a page gate** |
| 6 | **White-on-accent-gradient family + the accent-as-text sweep** | the gradient family recurs on every page (worst so far `.vy-au-preview-cover-ph` **2.54**); the accent-as-text sweep stands at **22 fixed** of the ~248 declarations | surfaced every gate, **never half-fixed and never ADDED to** |

## N.10 ⚠️ OWNER-REALM RUNNING LESSONS — carry into every remaining owner and guest page

**(a) VERIFY-DON'T-ASSUME is load-bearing — but do NOT over-generalise a correction.** Six owner gates each
corrected a premise in their own brief: OWNER-4 (the #6 roles were **inverted** — the operator proposes, the
owner approves and prices directly), OWNER-6 (the prototype was **mislabeled**, and the scope is structural,
not a 404), OWNER-7 (in-app is **not a toggle** — the hidden inputs are load-bearing, since `save()` defaults a
missing key to false), OWNER-8 (**D3-4 does not apply** to the owner path, and `--vy-on-grad`'s worst case is
the *darkest* point), OWNER-9 (**Add Unit is a REQUEST, not a create**). ⚠️ Then OWNER-10 showed the other
edge: that last correction **did not generalise** — the Unit Detail's actions really are direct owner writes.
**Trust the code and the prototype's own copy over any assertion in a brief — and re-verify each surface fresh.**

**(b) A refusal or a scope can be STRUCTURAL.** `Iban::masked` takes only `iban_last4`, so a raw IBAN is
un-emittable. The notification feed is self-scoped *by its query root*, not by a 404. SMS/Push are unsettable
because `CHANNELS = ['in_app','email']`. Owners cannot create a unit **at all**. A structural refusal is
stronger than a guard — say so, and don't "add" a check that already cannot fail.

**(c) DO NOT seed demo data to enrich a screenshot.** OWNER-4 found **0 `RateGuardrail` rows**, so only the
unguarded case was live; OWNER-11 found **exactly one owner**, so no foreign statement existed to probe.
In both, seeding would have been mutating demo data for a picture. **The guard proves it; silence is correct.**

**(d) "READ-ONLY" ≠ "WRITES NOTHING."** OWNER-11: every statement PDF download writes an `export_downloads`
audit row **by design** (Wave B · GATE 5). What must be unchanged is the **money/state** row — disclose the
audit write rather than claiming a side-effect-free read.

**(e) A PROBE CAN PRODUCE FALSE FAILURES — three gates running.** OWNER-8: two inputs looked unnamed because
the probe only walked ancestor labels. OWNER-10: a fix looked like it over-blocked because
`querySelector('[x-data]')` matched the **shell's** Alpine root, not the wizard's. OWNER-11: four "contrast
failures" were white text and two greys sitting on **dark plates**, scored against white paper. ⚠️ **Scope the
probe to visible controls, score against the REAL composited background, and when a result contradicts what
you just reasoned through, suspect the probe first — then correct the probe, not the page.**

**(f) BACKLOG ITEMS COMPOUND.** OWNER-7's fix depended on OWNER-6 having added `.vy-sr-only` to the owner
shell. A shared partial cannot depend on a class only one shell defines — check every shell a shared file
renders in.

## N.8g The process lessons this pass earned, in detail (extend **L**) — the OPERATOR-era set

21. **MEASURE COMPUTED VALUES, NOT THE SOURCE.** Three real bugs were invisible to file-reading and only
    appeared when the *rendered computed value* disagreed with the source: a cascade fight lost to a later
    equal-specificity duplicate; a stray `*/` eating its own rule; and **two `style` attributes on one element**
    — HTML keeps the first and silently discards the rest, so the Settings cancellation-policy selector had
    **no visible selected state at all** (its radio is `position:absolute;opacity:0`).
22. **THE DOM MEASURES CURRENT STATE, NOT CAPABILITY.** A built-but-dormant feature reads as "missing" on a
    probe — internal notes (none on that thread), Retry (no failed message), templates (inside a modal), the
    resolve modal (absent for an `open` ticket), the density toggle (`x-show="view === 'board'"` while the page
    loads in List). **Re-check against the source before classifying.**
23. **VERIFY THE PROMPT'S OWN PREMISE.** A flagged hazard may not apply: the "SettingsService JSON-merge"
    warning did not hold on Maintenance Settings, whose table is **typed columns** with `array_key_exists`
    guards — the wholesale-replace hazard is **structurally absent** there. Likewise a grep for ID-doc/erase
    terms returned zero: nothing to match, so nothing to invent.
24. **PROMOTE A SHARED RULE AT THE THIRD COPY — and know that a hand-rolled duplicate MISSES the shared
    component's honesty fixes.** Moving Maintenance Costs onto `<x-charts.bars>` fixed two real defects for
    free: the local copy printed its figure **only in a `title` tooltip** (invisible on touch entirely), and
    floored bars at `max(3, …)` so **a genuinely €0 month drew a stub that reads as real spend** (#39).
25. **DON'T REPRODUCE THE PROTOTYPE'S DEFICIENCY.** Where matching the mock would break AA (status or
    department carried by **colour alone**) or state something false (a per-property header on **org-wide**
    settings; a populated country column that is really empty), keep the honest/accessible version **and log**.
26. **SEED THROUGH THE REAL PATH.** A raw status write produces an **impossible state**; driving the certified
    service makes the demo data genuinely consistent (`4490b42`).
27. **PROVE PROPERTY SCOPE THREE WAYS** on an isolation-critical surface — structural diff (view-only) +
    service-signature enforcement (a required argument makes PHPStan fail any forgetful caller) + the
    adversarial test (`MaintenanceCostScopeTest`).
28. **PROVE A BASELINE FAILURE IS PRE-EXISTING BY STASHING**, not by name-matching.
29. **A REFUSAL CAN BE STRUCTURAL, AND HALF-FABRICATION IS FABRICATION.** An API key is not re-displayable
    because **only its hash persists**; and an element with 2 real tiles beside 2 invented ones is refused
    **whole** — rendering the real half and inventing the rest is exactly the fabrication this pass exists to
    avoid (Settings' hero KPI strip).
30. **KEEP THE INVARIANT VISIBLE.** The Refund page **surfaces #27** — "€369.00 still refundable … anything
    above that is **refused, not trimmed to fit**" — plus a folio-reconciliation badge and an append-only
    explanation. That is **better than the mock**, and it is why the page needed no design change at all.
31. **AN ASSERTED BASELINE GOES STALE EXACTLY LIKE AN ASSERTED FINDING — MEASURE IT EACH GATE.** The suite
    went 7 → 8 non-green at **P8** and rode through **seven** further gates unnoticed, because every note
    (including this file) *asserted* "the baseline is 7" and each gate trusted the note instead of running the
    count (**J.bis**). The lesson that catches a regression is worthless if its input is quoted rather than
    re-measured. **Corollary, from the same fix: when a guard false-positives, tighten it to its stated intent
    and PROVE it still fires** — a test that passes because it now checks nothing is worse than the false
    positive it replaced. Four mutations were run and observed; two of them existed only to prove the guard
    still went RED. ⚠️ **And a false positive is not automatically the test's fault — but here it was**, and the
    alternative (changing a correct design value to satisfy an over-broad test) was rejected as backwards.

## Status (verified against the repo)

> ⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-08-03 (v44) · ✅ GUEST realm Phase-4 pixel-diff FINISHED → 🎉 VYROBEL FINISH-TO-100 COMPLETE (operator + guest + owner) (the guest Phase-4 pixel-diff pass, G0–G4 + G-FINAL; `docs/DECISIONS.md` capstone; `docs/FULL-AUDIT-OPERATOR-GUEST.md` GUEST RESOLUTION LOG; `docs/design/DIVERGENCE-REGISTER.md`; saved memory `project-guest-phase4-pixel-diff`).** All **10 Phase-4 guest pages** are now matched to their decoded wireframes — presentation fidelity + motion over the ALREADY-REAL, certified, SHARED backend (no engine change), every page guest-isolated (foreign → 404). **6 gates, all pushed to `main` (HEAD `eefd4a6`):** G1 `f7398e0` Trips (travel-account hero + tabs/search/year — native GET, no Alpine + rich cards) · G1b `1383b57` Reservation (status hero + 4 quick-stat tiles + 2-col bento; #33 door code withheld >48h out) · G2a `25f409b` Payments (3 real stat tiles reconciling #17 + real hosted-Stripe pay + honest no-stored-card) · G2b `079813c` Manage (real cancellation free-until; request→confirm; travel-credit upsell refused) · G3 `3703023` Wallet+Receipt (real ledger; document receipt from the SHARED folio; referral program refused) · G4 `eefd4a6` Messages+Saved+Review (name-only chat, no presence/SLA/phone; no waitlist "#N in line"; no promo). **Verify-first held (the operator GATE-11 lesson):** the pages were already functional on the correct guest shell — the gap was pixel-diff, not backend; several carried NO fabrications already (grep-clean). Guest invariants intact: #17 reconcile · #28 isolation + name-only · #33 window-gated door code · archive-not-destroy 14-day delete + anonymize. Guest suite **233/233**; full suite green modulo the documented pre-existing `BookingImportTest` flakes; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; every page browser-verified side-by-side with its wireframe. **➡ Vyrobel's design-fidelity finish is COMPLETE across all three realms; the ONLY remaining work is the founder's external-infra go-live flips (Channex, Stripe live/Connect/SEPA/IBAN, Push/SMS, KYC, websockets) + deployment — none are UI/fidelity gaps, all honestly surfaced today.**

> ⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-08-03 (v43) · ✅ OPERATOR REALM FINISHED TO GENUINE 100% (the finish-to-100 pass, GATES 0–12 + FINAL; `docs/DECISIONS.md` 2026-08-02/03 entries; `docs/FULL-AUDIT-OPERATOR-GUEST.md` RESOLUTION LOG; `docs/design/DIVERGENCE-REGISTER.md`; saved memory `project-operator-finish-to-100`).** Every operator page is now at genuine 100% — bespoke-chrome fidelity (via the **parameterized shared shell**, GATE-0 decision: do NOT fork per-page chromes — the shell already carried the prototype chrome, anti-drift) + full motion + full features — on REAL, tenant-scoped, reconciling data. **13 gates, all pushed to `main` (HEAD `0363fe2`):** 0 `b984687` shell · 1 `0434b1c` New Reservation · 2 `2a8efb8` Front Desk · 3 `5fd25a0` Inbox · 4 `3abdc10` Group Booking · 5 `a549624` Calendar/Rack · 6 `2606c29` Maintenance · 7 `f43a6d4` Dashboard · 8 `c726c97` Housekeeping · 9 `ba48dc6` Team Schedule · 10 `df9a3cc` Reports · 11 `3bd582b` Owners group (verify-complete — 5 pages already exact-match #86/#90/#93/#94/#95; the one honesty fix = a hardcoded "Verified" badge that was lying → now real `email_verified_at`) · 12 `8d85783` **the carried-over createBooking OOS write-path guard CLOSED** (shared `AssignmentService::occupy()` refuses a named unit_id when `! isBookable()`, covering create/group/walk-in, OTA path untouched; mutation-proven) · FINAL `0363fe2` (Settings = one deliberate combined `/app/settings` hub; Winback/Waitlist = honestly deferred, nothing fake fires). The GATE-11 lesson held for GATE 12: most long-tail pages were already exact-match from tasks #96–#104 → **verify-first, don't rebuild.** Remaining per page = external-infra **HONEST DEFERRALS only** (see the audit RESOLUTION LOG). Suite **1603 / 1610** (the 6–7 failures are the documented pre-existing `BookingImportTest` isolation flakes — 11/11 in isolation, OTA import path, unrelated); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **THE OPERATOR REALM IS CLOSED.**
> **⬅ NEXT VYROBEL FINISH TRACK — the GUEST realm's Phase-4 pixel-diff pass:** the ~9 guest pages that are functional-on-the-guest-shell but were **not freshly pixel-diffed** this pass — Trips, Reservation view, Manage-Booking, Payments, Wallet, Messages, Saved/Wishlist, Review, Receipt (the guest storefront/Browse/Checkout/Check-in + Account + Arrival + Add-ons WERE rebuilt exact-match, v41/v42 below). Same discipline: verify-first vs the decoded prototype, browser-verify the real flow, build only genuine residual gaps, preserve refusals/deferrals, one page/group per gate.
> **STANDING PRODUCT-WIDE HONEST DEFERRALS (test-mode / honestly surfaced until the founder's provider accounts + go-live flips — none faked #39):** Channex/OTA live sync (never a fabricated "last sync") · SMS/Twilio · Push/FCM/APNs (no VAPID) · live Stripe charge (SAQ A, no raw PAN) + owner-payout go-live (Stripe Connect) + the payout-engine flip · IBAN verification / micro-deposits · SEPA export · websockets/presence (no realtime inbox toast/typing) · live LLM (no AI replies/insights) · third-party KYC (guest ID captured + encrypted, never "verified"). Documented per surface; the go-live flips are Subhan's (`docs/STRIPE-PRODUCTION.md`, `docs/EMAIL-PRODUCTION.md`, `docs/OAUTH-DESIGN.md`, `tax:set-rate`).
> **KNOWN TECH-DEBT — the `BookingImportTest` isolation flakes (5–7 of ~1610):** they pass **11/11 in isolation** but fail in the full run — they avoid `RefreshDatabase` and drive a real queue worker, so they are ORDER-DEPENDENT (OTA import path, unrelated to any operator/guest page). Optional future stabilization (give them `RefreshDatabase` or isolate the worker); NOT a finish blocker.

> ⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-08-01 (v42) · GUEST PORTAL batch 2 exact-match rebuild COMPLETE (invariants #116–117; `docs/GUEST-BROWSE-CHECKIN-CHECKOUT-AUDIT.md`, `DECISIONS.md`).** Three more guest wireframes rebuilt exact-match on REAL data, BUILDING what was missing — 3 gated commits (`c13b1d5`, `4f833f6`, `2ec43ff`; + `842d698` wallet backend/FK-hardening). PHASE 1 decode+investigate → 3 founder decisions (D1 build a multi-property landing · D2 real encrypted ID store · D3 gated wallet credit). **Browse** = a NEW multi-property per-org landing `/stay/{organization}` (`ResolvePublicOrganization` → `StorefrontLandingController`): real towns (grouped from `address.city`), real from-price (cheapest forward `rate_calendar` nightly), featured rooms + per-room ratings (#76), amenity chips (only where they exist), real recent reviews (#28), and a hero rating badge = the REAL org average+count (hidden when none — never "4.8 · 369 stays"); native GET search → the real `booking.browse` (#116/D1). **Checkout** restyled to the wireframe (2-col, reconciling summary #17) with a REAL gated wallet toggle (`apply_credit`, capped, server-authoritative, no-Alpine preview) + real free-cancel (`CancellationPolicy::freeUntilForCheckIn`); NO raw PAN / on-page Apple Pay / "charged at check-in" (SAQ A → hosted Stripe, D3/D4). **Check-in** = the 4-step wizard (Details → ID → Arrival → Done) that stamps only the pre-check-in INTENT + a real `arrival_window` (operator-visible) + a REAL ENCRYPTED ID: `GuestIdDocumentService` Crypt-wraps the bytes on the PRIVATE disk (ciphertext), Media→Document→GuestDocument `pending` (captured, never "verified"), owner-never (#28); `guest-documents:purge-expired` deletes file+rows 30 days after checkout; `anonymize()` clears the guest's `notes_guest`+`arrival_window`; door code BY EMAIL (SMS deferred), no fabricated co-guest name (#117/D2). Refused (never faked): fabricated towns/counts/"369 stays" · inline PAN + on-page Apple Pay + "charged at check-in" · "ID verified"/third-party KYC scan · door-code "by SMS". All surfaces browser-verified; suite green (only the pre-existing `BookingImportTest` flake, 11/11 in isolation); PHPStan L5 (0); Pint clean; `migrate` clean. **Invariants #1–117.**

> ⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-08-01 (v41) · GUEST PORTAL exact-match rebuild COMPLETE (invariants #110–114; `docs/GUEST-PORTAL-AUDIT.md`, `DECISIONS.md`).** The five guest wireframes rebuilt exact-match to the peach-glass design on REAL data, BUILDING what was missing, under the honesty rule — 3 gated commits (`51ae811`, `4db8fa5`, GATE 3). PHASE 1 decode+investigate → 4 founder decisions (D1 test-mode hosted Stripe · D2 real 14-day scheduled delete · D3 honest cards · D4 operator-confirmed add-ons). **Booking storefront** (`/book/{property}`): rebuilt (frosted search pill, real bedrooms/amenities/max-price/sort filters, real-rating cards #76); the grand total reconciles to the folio **incl. the property's real cleaning fee** (`book()` posts room+fees+taxes via `PropertyCharges`, never a hardcoded €45 — #17/#110); Reserve-&-pay = certified `AssignmentService` + TEST-MODE hosted Stripe (fulfil-on-webhook, no PAN). **Arrival Info** re-skinned + a new printable **PDF** (`ArrivalPackDocument`, same window-gated `ArrivalPack` — a pre-window PDF omits the door code, #33/#111). **Add-ons** (`/guest/.../extras`): new page on the real `AddOn` catalogue, VAT-inclusive; operator-confirmed request → `GuestChangeRequestService::confirm()` posts the folio (reconciling #17, idempotent — #112/D4). **Account**: hero + 5-KPI + 3-col bento (real credit/stays/waitlists/consent); a REAL 14-day scheduled delete (`GuestDeletionService` + `scheduled_deletion_at` + undo + the `guests:process-deletions` daily command → per-policy cancellation + `Guest::anonymize()` — #113/D2); honest cards (no vault/#114/D3) + honest SMS; trip-offers → `marketing_consent` (#87). Refused (never faked): cross-property "Where"/map (no geo), house-guide/checkout/local-picks/host-SLA, invented extras, "····4242"/raw-PAN, SMS send, waitlist "#N". All 5 browser-verified; suite green (only the pre-existing `BookingImportTest` flake); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–114.**

> ⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-08-01 (v40) · GUEST EMAILS: Booking Confirmation + Cancellation + Email
> Previews exact-match COMPLETE (task #109, invariant #109) — the two guest Mailables (they existed but minimal)
> rebuilt exact-match to `Booking Confirmation Email.html` + `Cancellation Email.html`, email-safe, and threaded
> through the #86 template editor (catalog 5→7, defaults byte-faithful so the #83 five + `GuestEmailTest` stay
> green); the Email Previews page (`Email Previews.html`) rebuilt to the tabbed gallery + a real own-inbox
> test-send. **GATE 1:** Confirmation — the REAL folio-item price breakdown with **Total === total_minor** (#17;
> honest degrade, no fabricated nightly split), real dates/times, add-to-calendar (Google/Outlook/Apple .ics via
> a new `CalendarLinks`), a 48h door-code **POINTER** (#33), honest **"your original payment method"** (no masked
> card, SAQ A/#28), real source (#85), "free cancellation until {date}" from the real policy. Cancellation — now
> **branded** (`BrandsGuestMail`) + **two honest cases** (a real recorded refund vs "No refund due" — the deposit/
> non-refundable/waived split + WELCOME10 promo **refused**, #39). Both threaded through #86 (template()+catalog+
> preview-sample+enabled()-guard). **GATE 2:** the Previews gallery — a 9-pill tab switcher, an inline `<iframe>`
> rendering the REAL Mailable per tab (can't drift), honest status captions, and a real **Send-test** to the
> operator's **OWN inbox only** (the certified rule — the wireframe's free-typed recipient refused), gated
> `properties.manage` + `@can`-gated control. Nothing faked. Fresh-session entry point: this block →
> `docs/GUEST-EMAILS-CONFIRMATION-CANCELLATION-PREVIEWS-AUDIT.md` → `docs/DECISIONS.md`. **Invariants #1–109.**
>
> ---
>
> **PRIOR (v39) — 2026-07-31 · OPERATOR WORK ORDER detail exact-match COMPLETE (task #108,
> invariant #108) — the Work Order detail (`/app/maintenance/{ticket}`) matched to `Operator Work Order.html`
> EXACTLY on the REAL `MaintenanceTicket` — the DETAIL of the SAME model the Maintenance board lists (anti-drift;
> the board undisturbed). The page already existed (#89) on the operator design system; this is an exact-match
> restyle + gap-fill. **GATE 1:** the 2-col layout + dark Status hero + 4-col Details + Reported-issue + real
> photos + Activity timeline + **composer** (a real Note/Progress/Part-ordered update via a new
> `MaintenanceService::addNote` on the SHARED `Note` store; timeline MERGES real `audit_events` ∪ notes) +
> Assignee + **SLA** (the ONE shared `MaintenanceSla`, honestly absent with no target) + **Out-of-order impact**
> (real block + real affected-booking count narrowed to "N arrival(s) tonight" + real rack link) + the **Reassign
> modal** (real staff + real open-work load). **GATE 2:** the full **Mark-resolved** modal — What-was-done
> (`resolution_notes`, also to the timeline) + Time-spent (`labor_hours`, informational, never priced) + Parts-cost
> (bills the owner, **parts_minor === cost_minor** reconciling #17) + **Return-to-sale** (genuinely un-blocks the
> unit, mutation-proven; keep-blocked leaves it out of order) + **Notify-front-desk** (real, toggle-respecting) —
> plus the Reassign reason chips (recorded on the assign event) + notify-both. HONESTLY OMITTED (#39): Area,
> sequential `WO-####`, technician specialty / "On site" / availability, outcome chips, "€/night on hold". Every
> write `can:maintenance.manage` + read-only-respected + audited; the board / availability-rack / Costs report /
> owner-Expenses / #88 attachments undisturbed. A raw-blade `word@if` trap was caught before shipping. Nothing
> faked. Fresh-session entry point: this block → `docs/OPERATOR-WORK-ORDER-AUDIT.md` → `docs/DECISIONS.md`.
> **Invariants #1–108.**
>
> ---
>
> **PRIOR (v38) — 2026-07-31 · OPERATOR SECURITY + SETTINGS + TEAM SCHEDULE exact-match
> COMPLETE (task #104b) — the three operator System pages matched to their decoded wireframes EXACTLY on REAL
> data, building what was missing. **Security** (`/app/settings/security`): overview + KPI tiles · change-password ·
> 2FA · an **IP allowlist that GENUINELY ENFORCES** (new `ip_allow_rules` + an `EnforceIpAllowlist` middleware in
> the `/app` group — `IpUtils::checkIp` exact+CIDR, fail-open on no-tenant/zero-rules, lockout-mitigated,
> mutation-proven to 403 a non-covered IP, never a fake toggle) · active sessions · trusted devices (#66) ·
> **Connected apps HONEST** "No apps connected" (OAuth is owner-data-scoped only #72) · self-scoped **data export**.
> **Settings** (`/app/settings`): the System→Settings HUB **DEDUPED by supersede-by-layer** over the ONE
> `SettingsService` store (new config = columns on `organizations`, never a parallel store) — **Invoice details**
> real + **SAF-T issuance honestly deferred** (no pipeline → config-real, not fabricated-certified), **Your plan**
> real (`billingStatus()`/`SubscriptionPricing`), commission the REAL **18%** (`OwnerCommissionRate`, never 15%).
> **Team Schedule** (`/app/schedule`): the interactive rota — **Publish week** (a real `schedule_publications`
> finalize stamp + a real notify to rostered staff; edits stay live) · **Open shifts** (net-new `open_shifts`;
> FILL = a real Shift via the certified `ShiftRosterService` + delete the open row) · the **read-only write-guard
> fix** (the schedule writes didn't respect a paused workspace — the Rate/Housekeeping peers did). Nothing faked.
> Fresh-session entry point: this block → `docs/OPERATOR-SECURITY-SETTINGS-SCHEDULE-AUDIT.md` → `docs/DECISIONS.md`.
> **Invariants #1–107.**
>
> _(Intervening operator exact-match tasks #98–104 — Property Settings, Rates+Refund, Roles+Schedule-PDF+Search,
> and the full operator COMPLETENESS AUDIT + BUILD — are dated in `docs/DECISIONS.md`; this header was brought
> current from v37/#97 to v38/#107 in this pass.)_
>
> ---
>
> **PRIOR (v37) — 2026-07-31 · OPERATOR PORTFOLIO + PROFILE exact-match COMPLETE — the
> multi-property Portfolio (real per-property analytics: Revenue·month/Occupancy/Arrivals/Needs-attention +
> per-card ADR/revenue + honest Fix-sync via real channels.status; Group = Σ reconciles #17; read-only guard)
> and the operator Profile (real personal details + preferences [motion/density genuinely applied, date-format
> shared helper] + a READ-ONLY real-spatie role-caps display + Sign-in & security reusing #66 password/2FA +
> REAL sessions via the shared ActiveSessions + sign-out-of-all-other-devices). Nothing faked. Fresh-session
> entry point: this block → `docs/OPERATOR-PORTFOLIO-PROFILE-AUDIT.md` → `docs/DECISIONS.md`. **Invariants #1–97.**
>
> ---
>
> **PRIOR (v36) — 2026-07-31 · OPERATOR OWNERS + OWNER DETAIL + PAYOUT RUN + NOTIFICATIONS
> exact-match COMPLETE — the OPERATOR SIDE OF THE OWNER FINANCIAL SYSTEM on the certified spine: the owners
> list (real net/pay-status, 18% commission, Add-owner reuses #80), Owner Detail (net-to-owner reconciling at
> 18%, masked IBAN, edit/pause-payouts, careful archive-not-destroy remove, notes), the Payout Run (the
> certified PayoutRunService in TEST MODE — a persisted reconciling run + lock, no real money; on-hold gate;
> masked IBAN; SEPA honest-disabled; read-only guard), and the Notifications feed Unread filter. Reconciling
> (#17) at the real 18%; nothing faked. Fresh-session entry point: this block → `docs/OPERATOR-OWNERS-PAYOUTRUN-AUDIT.md`
> → `docs/DECISIONS.md`. **Invariants #1–95.**
>
> ---
>
> **PRIOR (v35) — 2026-07-31 · OPERATOR MESSAGES + NEW RESERVATION + NOTIFICATION PREFERENCES
> exact-match COMPLETE — the 3-pane operator↔guest inbox (assign-to-staff + real in-app notify + start-from-
> template), the full 7-step reservation wizard on the certified engine (reconciling extras/transfer/tax/credit
> folio, test-mode payment, localStorage draft), and the operator notification prefs on the reused #79 engine
> (Email gates delivery; Push + SMS honest-gated; real `low_rating` alert). This block SUPERSEDES every block
> below it. Fresh-session entry point: this block → `docs/OPERATOR-MESSAGES-NEWRES-NOTIFS-AUDIT.md` →
> `docs/DECISIONS.md`.**
>
> **Operator Messages (`/app/inbox`) + New Reservation (`/app/reservations/create`) + Notification Preferences
> (`/app/notification-preferences`) (invariants #90–92)** matched to their wireframes EXACTLY on REAL data,
> reusing/extending existing engines (never rebuilt). Six founder decisions **Recommended** (A–F). **GATE 1 —
> Messages (`7c37a13`, #90):** the unified 3-pane `OperatorInbox` on the SAME `MessageThread`/`Message` +
> `InboxService` (the two-page inbox removed; `/app/inbox/{thread}` → `?c=`), FULL guest data (distinct from
> owner↔operator name-only #77); Assign (real, `StaffRoster` picker, "guests never see this") firing a REAL
> in-app `ThreadAssignedNotification` to the assignee (in-app only, no fake push; `inbox.thread_assigned`
> dispatcher branch); New-message → start from a #83 template (`EmailTemplateResolver` → real outbound);
> read-only guard added; suggested replies honest-omitted. **GATE 2 — New Reservation (`ce29db1`, #91):** the
> 7-step wizard on the SAME certified `AssignmentService::createBooking` (non-overlap unchanged); the create
> flow posts REAL reconciling folio lines — extras (`AddOn`) + transfer (per-leg) + property taxes (`Tax`) +
> returning-guest wallet credit (`GuestWalletService`), room + extras + transfer − credit + tax reconciling
> (#17, mutation-proven); VIP + consent (#86); card honest "Soon" (no PAN, test-mode #45); the shared
> `ReservationExtras`; a live estimate summary + localStorage draft; house rules from real `Property`/
> `PropertyPolicy`; door code guest-only (#33); demo AddOns + city tax seeded. **GATE 3 — Notification
> Preferences (#92):** rebuilt on the SHARED `NotificationPreferences` engine + controller (audience operator);
> Email real + gating delivery (mutation-proven); **Push + SMS HONEST-GATED** (zero infra — display-only, never
> submitted #39); the real `in_app` preserved via hidden inputs; quiet hours real; daily digest honest
> (`daily_summary`, not dispatched); the new real `low_rating` catalog key + `LowRatingNotification` fires only
> on a real rating < 4. 🔒 operator-scoped; the certified `AssignmentService` reused; #17 the folio reconciles;
> the #79 prefs engine reused (Email gates delivery); #33 door-code guest-only; #20 writes controller-routed +
> audited; #39 no fabrication (assignment/charge/SMS/push/suggested-reply/low_rating all real-or-honest).
> `OperatorInboxComposeTest` (5) + `OperatorNewReservationExtrasTest` (4) + `OperatorNotifPrefsGateTest` (5);
> all three pages browser-verified. PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants
> #1–92.**
>
> ---
>
> **PRIOR (v34) — 2026-07-31 · OPERATOR MAINTENANCE (board + costs + settings) exact-match
> COMPLETE — real SLA breach, real labour/parts split tied to the owner-billed Expenses, real technicians +
> auto-assign master toggle, real photos, mark-out-of-order genuinely blocks selling.** Fresh-session entry
> point (superseded): `docs/OPERATOR-MAINTENANCE-AUDIT.md` →
> `docs/DECISIONS.md`.**
>
> **Operator Maintenance board (`/app/maintenance`) + Costs (`/app/maintenance/costs`) + Settings
> (`/app/maintenance/settings`) (Invariant #89)** matched to their wireframes EXACTLY on REAL data, building
> what was missing. The two hardest mandates were already certified and reused untouched (anti-drift):
> **mark-out-of-order genuinely blocks selling** (certified `setUnitCondition('out_of_service')` → availability
> `out_of_order` → `AssignmentService` guard — the owner-unit-pause #69 mechanism; **mutation-proven** it throws
> `InsufficientAvailabilityException`) and **costs tie to the owner-billed Expenses** (`postExpenseIfOwned` →
> `OwnerExpenseService::recordExpense`). Founder decisions **Recommended** (A/B/C/D). **GATE 1 — board:**
> `HasMedia` + real photo upload/delete (`RoomTypePhotoController` pattern, `mt_photo`); migration
> `labor_minor`/`parts_minor`/`awaiting_parts`/`part_name`/`part_eta`; the shared `MaintenanceSla` (a REAL breach
> from `sla_*_hours`) drives the board SLA badge; the shared `RevenueOnHold` (reuses the Dashboard `onHoldMinor`);
> auto-assign wired in `MaintenanceService::open()` (master-toggle-gated). `MaintenanceList` = the flat list + a
> right rail that is now REAL (Technicians / Parts-on-order / OOO hero); detail drawer (pipeline, SLA, parts,
> photos, activity, Start/Resolve[labor+parts]/photo); New-WO (photos + mark-OOO with the "check the rack"
> warning); Assign modal. **GATE 2 — Costs (`cd371c8`):** `MaintenanceCostService` gains the REAL labour/parts
> split (labour + parts === `cost_minor`, #17; legacy COALESCEs to all-labour) + `parts_on_order` + `billable()`;
> the billable totals **tie to the owner_expense** (mutation-proven); real exports — PDF + CSV (both
> `maintenance.manage`-gated + audited) + Email-to-me (PDF to the operator's OWN inbox), **Excel honestly
> deferred**; page rewritten to the wireframe (KPIs, spend-by-category, billable table, dark spend hero,
> labour-vs-parts, spend-by-month, top-units, Alpine export menu). **GATE 3 — Settings:** `auto_assign_enabled`
> master toggle wired end-to-end (mutation-proven both ways); `technicianPanel()` (category assignees + anyone
> holding an open order + a **real open-order load**); honest Invite = a "Manage team & roles →" link to
> `app.roles`; honesty corrected (maintenance alerts **not dispatched yet**, require-photo **not enforced yet**).
> 🔒 operator-scoped; the `MaintenanceTicket`/`MaintenanceService`/availability/owner-Expense path reused
> (anti-drift — the Dashboard #84 + owner Expenses undisturbed); #17 costs reconcile; #20 every write
> controller-routed + gated + audited; #39 no fabrication (Excel + require-photo + maintenance-alert dispatch
> honestly deferred, not stubbed); category divergence documented (KIND-3 — the 5 real categories, not
> HVAC/Furniture). `MaintenanceRebuildTest` 16 + `MaintenanceBoardViewTest` 7 + `MaintenanceModuleTest` 3 +
> `OperatorMaintenanceAdminTest` 6 all green; full suite 1348 green; PHPStan L5 (0); Pint clean;
> `migrate:fresh --seed` clean; all three pages browser-verified. **Invariants #1–89.**
>
> ---
>
> **PRIOR (v33) — 2026-07-30 · OPERATOR HOUSEKEEPING + INSIGHTS exact-match COMPLETE — real HK
> management (assign-all, photos, real elapsed) + real-derived insights (Expedia honest).**
>
> **Operator Housekeeping (`/app/housekeeping`) + Insights (`/app/insights`) (Invariant #88)** matched to their
> wireframes EXACTLY on REAL data — both had substantial real backends, so this is match-the-design + a few
> genuine builds. Founder decisions **Recommended**: real `started_at` (real elapsed); real-derived insights +
> Expedia/brand fabrications REFUSED; single proof photo collection. **GATE 1 — Housekeeping (`964eec8`):** the
> operator MANAGING the real `HousekeepingTask` (the owner read-only `OwnerUnitDetail::cleanings` untouched).
> Migration `started_at` (set on in_progress → the "Elapsed {m}/{exp}m" bar is REAL; expected minutes a
> documented per-turnover constant); **`HousekeepingService::assignAll`** (greedy least-load, waiting-guest
> first, each via the certified audited `assign()`) + a "clean next" priority bump; **real proof-photo
> upload/delete** (the `RoomTypePhotoController` media pattern → `Media` on the task, `hk_proof`). The board:
> **Board/List + density** (client-persisted), **crew filter + search** (GET), the **5-status tally** (4 real
> statuses + real out-of-order via `condition_status`), lanes, room cards (turnover/**urgency** [name-free
> guest-arriving via `reservation_units`]/**elapsed**/action + #87 VIP+prefs), **assign + assign-all modals**,
> a **room-detail drawer** (pipeline, photos, action). Crew = real `StaffRoster::housekeepers()` + a computed
> live load. Every write `housekeeping.manage`-gated + read-only (#84). "Notified on device" softened honestly
> (real task-list, no fake push). **GATE 2 — Insights:** over the real `PerformanceMetrics`/`BookingCadence`
> (portfolio via `readIds`). Range presets; 4 KPIs with **real vs-LY deltas** (only where prior-year data
> exists); occupancy-by-month (this-year + on-the-books + real LY); by-property table; forward pace; and
> **real-derived "Worth a look" cards** (peak weekday / departure-day concentration / forward on-the-books —
> each shown only above a real threshold). **The Expedia "€4,600" card + the Booking.com/Airbnb/Expedia brand
> split + the Marina-Expedia suggested-step are REFUSED** (no connected channel, no OTA-brand data — #39): the
> channel mix shows REAL sources; the Expedia slot is an honest "connect a channel" note. "Decisions stay
> yours" kept. 🔒 operator-scoped; the HousekeepingTask + analytics reused (owner surfaces undisturbed); #20 HK
> writes audited; insights **REAL-DERIVED-ONLY** (#39); read-only respected; #17 reconciles.
> `OperatorHousekeepingActionsTest` (6) + `OperatorAnalyticsTest` 5→8; both browser-verified (7 rooms
> auto-balanced; real −40pts vs-LY + €7,740 on-the-books insight + Expedia honest). PHPStan L5 (0); Pint clean;
> `migrate:fresh --seed` clean. **Invariants #1–88.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v32) · OPERATOR GUESTS + GUEST PROFILE exact-match COMPLETE — full CRM, GDPR bulk-delete,
> consent-gated marketing, prefs→ops.**
>
> **Operator Guests list (`/app/guests`) + Guest Profile (`/app/guests/{guest}`) (Invariant #87)** matched to
> their wireframes EXACTLY and the Wave-2 honestly-omitted promises turned REAL (reviews #76 exist; the mandate
> authorised the prefs/consent/city stores). ⚠️ **#28: the operator sees + edits FULL guest contact (the manager)
> — the name-only rule is OWNER-surfaces-only; the perimeter test keeps front desk / reservations / inbox
> name-only.** Three founder decisions **Recommended**: consent gates a real marketing send; prefs flow to
> front-desk + housekeeping; CSV export built (audited). **GATE 1 — the list (`0818e6d`):** migration
> `guests.city`/`prefers_email`/`preferences` (all nulled by `anonymize()`); filters → **All · In-house ·
> Upcoming · VIP** (real stay-state); table adds **Last stay** + **Lifetime(+balance due)**; search matches name +
> city + **exact email** (`email_hash` blind index). **Bulk select** (Alpine, ULID-id-keyed — no PII in any wire
> payload) → **Message** (real email + inbox thread; "marketing" mode SKIPS non-consented via `GuestMarketing`) ·
> **Add tag** · **Export** (audited CSV, count-only, CSV-injection-safe `c035998`) · **Delete** = the GDPR erasure
> (loops the certified `Guest::anonymize()` — PII erased, **reservations RETAINED anonymized**; `can:guests.erase`
> + typed-ERASE server-confirm; HONEST copy). **Add guest** create. Every write respects read-only (#84).
> **GATE 2 — the profile:** restructured to the wireframe (header Message[real thread-start]/Edit-modal/Current-
> stay · stat strip · **stay history + real per-stay refund** · **Latest review** [#76, honest empty state, no
> fake 5.0] · **dark Lifetime hero** [billed + open folio, #17] · **Contact** [email/phone/**city** +
> prefers-email/consent] · **Preferences** [real store] · Edit modal). **The "shown to housekeeping & front desk"
> promise (once verified-false) MADE TRUE:** the front desk + housekeeping surface the guest's VIP + preference
> labels via the ONE shared `Guest::preferenceLabels()` (operational flags, not contact PII — name-only intact).
> **Marketing consent genuinely gates** a marketing send (`GuestMarketing::canReceive`; transactional stays
> ungated). 🔒 operator-scoped full contact (#28 owner-only); #17 aggregates reconcile; #20 CRUD + bulk-delete
> controller-routed + audited; the delete **anonymizes-not-destroys** (mutation-proven); consent gates email;
> read-only respected; #39 no fabrication. `OperatorGuestBulkTest` (8) + `OperatorGuestCrmFlowTest` (7);
> `OperatorGuestsListTest` + `OperatorGuestProfileTest` updated; the name-only perimeter stays green. PHPStan L5
> (0); Pint clean; `migrate:fresh --seed` clean; both pages + FD/HK flow browser-verified. **Invariants #1–87.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v31) · OPERATOR EMAIL TEMPLATES + EDITOR exact-match COMPLETE — a REAL customization store
> that drives the #83 guest sends.**
>
> **Operator Guest-emails list (`/app/email-templates`) + a NEW single-template Editor (Invariant #86)** matched
> to `Operator Email Templates.html` / `Operator Email Editor.html` EXACTLY — replacing the old static "Editing ·
> Soon" catalog with a **real per-org `email_templates` store that GENUINELY drives the 5 #83 guest Mailables**
> (mutation- + browser-proven end-to-end). `EmailTemplateCatalog` holds each template's DEFAULTS (= the #83
> strings, with `{merge}` placeholders) + real trigger/channel/blocks/vars; `EmailTemplateResolver` →
> `ResolvedEmailTemplate` (stored-override ?? default, merged) is threaded into all 5 Mailables' `envelope()` (subject)
> + `content()` (`heading`/**`intro`**/`button`/`blocks` — `intro` NOT `message`, a real reserved-var bug fixed),
> and every blade renders `{{ $heading/$intro/$button }}` + wraps each toggleable block in `@if ($blocks[…])`.
> **Defaults render byte-faithfully → GuestEmailTest 15/15 unchanged.** The 3 real triggers
> (`SendGuestTransactionalEmails`/`SendPreArrivalEmails`/`SendPaymentReminders`) skip a send when
> `EmailTemplateResolver::enabled()` is false → **OFF genuinely stops that email.** **LIST:** real rows
> (status/name/trigger/channel) + a real Active/off toggle POST + Preview + Edit; honest stat (status/trigger,
> NO fabricated open-rate — decision A). **EDITOR (`OperatorEmailEditor`):** editable subject (+ real insert chips)
> / heading / message / button / block toggles + a LIVE preview that renders the REAL `emails.{key}` blade + layout
> via the shared `EmailPreviewSample` with the draft overlaid (**preview == send, can't drift**) + a real Test-send
> to the operator's own inbox (decision B; MailSafetyGuard). SAVE = a native form POST (inputs carry BOTH
> `wire:model.live` + `name=`) → `EmailTemplateActionController` → audited `EmailTemplateService`. 🔒 every write
> `can:properties.manage` (#30 — the Settings tier; a Hotel Manager correctly gets a view-only list) → audited (#20);
> tenant-scoped (`TenantContext`, never a request id); key whitelisted (foreign key 404s); #39 no fabrication (no
> open-rate; the signature is the honest team line, never an invented person; nothing saves to nothing).
> Mutation-proven (`OperatorEmailTemplatesTest`, 9: saved heading changes the real render · merge resolves ·
> disabled block absent · OFF stops the real checkout trigger while ON sends · guarded+audited save · Front-Desk
> 403 · toggle · list+editor render · unknown key 404) + browser-verified (edit→save→the INDEPENDENT real email
> preview reflects the saved heading + omitted block; the verification temp user + demo row removed → demo back to
> defaults). Anti-drift — reuse the 5 #83 Mailables + `emails/layout.blade.php` + `EmailPreviewController` render
> path + `AuditEventRecorder` + `<x-operator.shell>`. Suite **1310/9774** green; PHPStan L5 (0); Pint clean;
> raw-blade guard clean; `migrate:fresh --seed` clean. **Invariants #1–86.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v30) · OPERATOR CHANNELS + BILLING exact-match COMPLETE.**
>
> **Operator Channels (`/app/channels`, NEW) + Billing (`/app/billing`) (Invariant #85)** matched to their
> wireframes — both heavily gated, nothing faked. **GATE 1 — Channels (`d286014`):** the whole page is OTA
> sync = **account-gated** (no real Channex integration). New Pro-gated page (`EnsurePlanFeature:channels`);
> the DESIGN matched but HONEST-GATED — the **Direct** channel is REAL (`source='direct'`, always live), a
> connected OTA channel's STATUS is real but live sync/import is honestly gated ("activates with your Channex
> account" — NEVER a fabricated "Synced/imported"), the booking-mix + stats are REAL by `reservations.source`;
> the fabricated "3 bookings waiting to import" alert replaced by the honest "setup required · Vyrobel never
> fabricates a sync it can't run." **GATE 2 — Billing:** the active-subscription management view. **Real
> per-unit FIGURE** — a new `SubscriptionPricing` = real active-unit count × a PLACEHOLDER per-unit price (€9)
> × placeholder VAT (23%), reconciling (net+VAT==gross, #17; 8×€9=€72+€16.56=€88.56), no real charge.
> **HONEST-gated** card ("No card on file", never a fake ····4242) + invoices (the real trial Free €0.00, "no
> invoice is invented"); real counts/billing-details. **The REAL pause/cancel → read-only state machine** —
> `SubscriptionLifecycleService` + `organizations.readonly_from` + the extended `OperatorWorkspace` gate +
> `billing.pause/cancel/resume`: PAUSE → read-only NOW (the shared gate the Dashboard/Calendar/certified-write
> consume); CANCEL → read-only from the paid-through date; RESUME clears; **orthogonal to the paywall**
> (subscription status never changed). **Mutation-proven** (`OperatorBillingLifecycleTest`): pause →
> `isReadOnly` true → a new booking POST refused. Browser-proven end-to-end (Pause 3 months → the Dashboard
> shows "Workspace paused — read-only until 30 Oct"). 🔒 #17 the figure reconciles; #39 no fabrication
> (channel-sync/card/paid-invoice/charge); the pause genuinely gates read-only; test-mode boundary
> (`StripeSafetyGuard`, no real charge until go-live). `OperatorChannelsTest` (3) + `OperatorBillingLifecycleTest`
> (4). Suite **1301/9748** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–85.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v29) · OPERATOR DASHBOARD + CALENDAR exact-match + the cross-cutting READ-ONLY mode COMPLETE.**
>
> **Operator Dashboard (`/app/dashboard`) + Calendar (`/app/calendar`) (Invariant #84)** matched to their
> wireframes on REAL tenant-scoped data (the operator realm + shell already existed — REUSE/EXTEND).
> **GATE 1 (`9c6e0c9`) — read-only + Dashboard:** the cross-cutting **read-only "Workspace paused" mode** is
> a REAL, additive, ORTHOGONAL pause — a nullable `organizations.paused_until` + a shared
> `App\Support\Operator\OperatorWorkspace` gate, read once + consumed by the shell banner (shell-wide) AND
> every operator write; honest (unset in demo → no banner) + orthogonal to the subscription paywall
> (`EnsureSubscriptionActive` untouched). The Dashboard matched the design + wired the missing REAL sections:
> Open work orders (+urgent, `MaintenanceTicket`), Today's tasks (real derived), Occupancy · next 7 days
> (real per-night — the once-"Not yet available" stub), ADR (`PerformanceMetrics`), Maintenance (real
> out-of-order + on-hold from `RateCalendar`), Channels (HONEST — "Not connected", never a fake "Synced").
> **GATE 2 — Calendar:** the real rack (certified availability + reservations) gained a real room-type filter
> and — the centerpiece — a **REAL inline NEW RESERVATION**: two-phase RMB-safe (#20), guest+stay → the
> certified `UnitAvailabilityService`'s real bookable units → Confirm POSTs to `ReservationCreateController`
> → `AssignmentService::createBooking` (row-lock **non-overlap**) → a real reservation + guest + folio + the
> real `BookingConfirmationMail`; read-only respected (affordance hidden + the certified write guarded). Added
> the `app.calendar` route (`/rack` alias kept). 🔒 operator/tenant-scoped; the certified `AssignmentService`
> reused (non-overlap mutation-proven); the read-only gate enforced at the single write path; #39 no
> fabrication (no fake channel-sync/occupancy/ADR/on-hold/reservation). `OperatorDashboardViewTest` 3→5,
> `OperatorRackViewTest` 4→6; both browser-verified (a real reservation R-YWME7DTF created via the calendar
> Confirm; the read-only banner on pause). Suite **1294/9709** green; PHPStan L5 (0); Pint clean;
> `migrate:fresh --seed` clean. **Invariants #1–84.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v28) · 5 TRANSACTIONAL EMAILS exact-match COMPLETE.**
>
> **The 5 guest/account transactional emails (Invariant #83)** — pre-arrival · payment-reminder · refund ·
> review-request · verify — matched to their decoded wireframes as REAL Mailables on real triggers,
> REUSING/EXTENDING the shipped Phase-3 Mailables + the shared email-safe `emails/layout.blade.php` +
> bulletproof-button pattern (anti-drift). All 5 Mailables + templates already existed (basic Phase-3
> versions); this matched the designs + wired the real data + the one still-missing trigger.
> **GATE 1 (guest stay, `d800964`):** pre-arrival (wired the shared `ArrivalPack` real **door code + wifi**
> — computed at the 48h send moment, released + **guest-only**, the **#33 owner-perimeter untouched**;
> **BUILT the real trigger** `emails:send-pre-arrival` + daily schedule; real balance + pay-now →
> `guest.payments`) · payment-reminder (real Total/Paid/Balance from one `FolioTotals` read, Total −
> Paid == Balance #17, + pay button) · refund (real amount + status timeline; HONEST "your original payment
> method" — no `card_last4`, #28; reason only when set; ETA a range). **GATE 2 (account):** review-request
> (**BUILT the `?rating` star deep-link** — each star pre-fills the real review via
> `GuestReviewController::show`; "only ask once" real; honest byline) · verify (booking-tied claim link;
> HONEST **real 1-hour** expiry not the wireframe's 24h; **welcome-credit teaser OMITTED** — no such
> feature, never fabricated). 🔒 #17 balance/refund reconcile · #28 no card leak (none stored, name-only) ·
> #33 arrival secrets guest-only (owner-perimeter intact) · #39 no fabrication (no fake
> balance/refund/card/reason/credit/trigger/named-host). `GuestEmailTest` +7, `GuestReviewTest` +1; all 5
> browser-preview-verified (real door code 6273 + wifi; reconciling balance; refund timeline; star
> deep-links; verify honest 1h + no credit). Suite **1290/9684** green; PHPStan L5 (0); Pint clean;
> raw-blade guard clean; no schema change. **Invariants #1–83.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v27) · OWNER AGREEMENT + AMENDMENT ACKNOWLEDGMENT exact-match COMPLETE.**
>
> **Owner Agreement (`/owner/agreement`) (Invariant #82)** matched to `Owner Agreement.html` on the REAL
> `ManagementAgreement`, BUILDING the missing pieces (single gate). The wireframe's headline interaction —
> "Amendment ready to sign" — is now REAL as an in-portal **ACKNOWLEDGMENT**: new `agreement_amendments` +
> `agreement_amendment_acknowledgments`; the owner reviews (Preview shows the real changed terms) and
> acknowledges (a real audited consent record — who/when/version, #20, CAP_ACTIONS via
> `AgreementAmendmentService`), which APPLIES the new terms via the **certified effective-dating** (a
> superseding effective-dated `ManagementAgreement` — history never rewritten; the change goes "in effect"
> automatically when the date arrives). Framed "acknowledged in your portal", **NEVER a fake "signed"
> badge**; a legally-binding e-signature is honest-deferred. Idempotent (unique + re-submit 404s). The
> previously-refused richer terms are now BUILT: **Term/Notice/Auto-renew** (additive nullable columns,
> honest when null), **"What your fee covers"** (descriptive standard-service copy + the REAL owner-billed
> expense categories, reconciling with Expenses), **"Terms by villa"** (REAL per-unit commission via the
> new shared `OwnerAgreementTerms` — the SAME `resolveFor` the attribution engine uses, #17; cleaning
> honest "At cost"). **Download agreement** + per-amendment PDFs are REAL (`OwnerAgreementDocument`,
> `$capExports`-gated #42, "summary not a signed contract"). **"Message your manager"** → the real
> `owner.messages` relay (owner→operator). Commission = the real 18% (`OwnerCommissionRate`), reconciling.
> 🔒 #28 owner-scoped explicitly (no OwnerScope on these tables — fail-closed), no guest data; #17 the
> commission reconciles with the statements + the same `resolveFor` per villa; #20 acknowledgment
> controller-routed + CAP_ACTIONS + audited; #42 PDFs CAP_EXPORTS-gated; #39 no fake signed-badge / invented
> term / cleaning-rate / owner-stays / inclusion / e-signature claim. `OwnerAgreementViewTest` (7, updated)
> + `OwnerAgreementAmendmentTest` (5, new); browser-verified (acknowledge → real record + the superseding
> 17% agreement "Starts 1 Oct 2026", current stays 18%). Suite **1283/9649** green; PHPStan L5 (0); Pint
> clean; `migrate:fresh --seed` clean. **Invariants #1–82.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v26) · OWNER RATES + REMITTANCE PDF exact-match COMPLETE.**
>
> **Owner Rates (`/owner/rates`) + the Remittance Advice PDF (Invariant #81)** matched to their two wireframes on
> REAL owner-scoped data, BUILDING the missing pieces. **GATE 1 — Rates (`e5d8adb`):** rebuilt to the wireframe's
> four sections (keeping the shipped direct-edit as an "Adjust a rate directly" addition). Proposed rate changes
> surfaces the REAL rate-proposals (#67) on the page. 🔨 **BUILT the COUNTER** — a real `countered` state (owner
> counters → operator accepts, applying the owner's rate via the certified `OwnerRateService`; additive, audited).
> 🔨 **BUILT the GUARDRAILS** — a per-owner `rate_guardrails` store (mode / auto-approve ±% / floor / ceiling) that
> GENUINELY ENFORCES on operator proposals in `RateProposalService::propose()` (below floor/above ceiling
> REJECTED · within ±% [dynamic] AUTO-APPLIED · else pending), gated on an explicit saved row so existing
> proposals are undisturbed — mutation-proven, never inert. Current rates = Base/Weekend/Occ (Peak OMITTED — no
> tier). Where you sit = the REAL average alone; the comp-set / "below market" OMITTED (no market feed —
> honest "benchmarking coming", #39). `OwnerRateService::applyForExclusiveOwner()` lets the certified write run
> owner-side AND operator-side without the owner scope (exclusive-ownership verified). **GATE 2 — the Remittance
> PDF:** it already reconciled (renders the payout's REAL `owner_statement_lines` → the commission is already the
> real **18%**); matched to the wireframe by adding the real VAT/country letterhead, the masked IBAN (CAP_PAYOUT,
> never full, #28), the RA-YYYY-MM ref, and the Payment-details section; the est-date stays honest (paid-on only
> once paid). 🔒 #28 masked IBAN + owner-scoped; #17 reconciles at the real 18%; #20 counter/guardrails
> controller-routed + gated + audited; #42 PDF CAP_EXPORTS-gated; #39 no comp-set / fake-counter / inert-guardrail
> / invented-Peak / fabricated est-date. `RateProposalTest` +6, `OwnerRemittancePdfTest` +1; browser-verified
> (counter→countered, guardrails, remittance reconciling at 18%). Suite **1278/9620** green; PHPStan L5 (0); Pint
> clean; `migrate:fresh --seed` clean. **Invariants #1–81.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v25) · INVITE OWNER (form + email) exact-match COMPLETE.**
>
> **Invite Owner — the operator "Invite owner" modal + the invite EMAIL (Invariant #80)** matched to their two
> wireframes EXACTLY on the **REAL, certified invite flow** — no backend rebuild. The flow already existed + is
> real: `POST /app/owners/invite` (`can:users.manage`, throttled, CSRF) → `OwnerInviteService` (name/email/role/
> units/commission → a real pending `owner_invite` with a sha256 single-use 7-day token) → the real role-aware
> `OwnerInviteMail` → `AcceptInviteController` (set password → activate → assign units + create the
> `ManagementAgreement` at the chosen commission → spend the token → `/owner`). Role map: **owner → External
> Owner** (portal), **manager → Hotel Manager**, **accountant → Accountant** (both operator staff). **GATE 1
> (`0853092`) — the FORM:** the modal restyled to the exact warm-glass wireframe (header icon, segmented Role,
> real unit chips, the 12/15/18/Set-up-later agreement picker defaulting to the REAL **18%**, the access-level
> cards) + the in-modal **"Invite sent"** confirmation (real PRG flash: real email + real expiry + Pending +
> resend + Preview) + a new operator-admin **email-preview route** (renders the real Mailable, never sends/writes).
> **GATE 2 — the EMAIL:** `emails/owner-invite` matched exactly (email-safe: role-aware eyebrow, "Invited by
> {operator}", the role-aware feature section, the bulletproof set-password button + REAL tokened link, real
> expiry + "no account until you set a password", the exact footer); `OwnerInviteMail::copyFor()` extended (same
> Postmark/`MailSafetyGuard` send path). 🔒 #20 operator-admin gated + audited; #39 no fabrication (real commission
> → real agreement [never the mock's 15%], real invite/token/email/activation; units for the owner role only —
> staff are org-wide); anti-drift (reuse `OwnerInviteService` + `OwnerInviteMail`; the preview renders the same
> Mailable). `InviteOwnerFormTest` (3) + the certified invite suite green; browser-verified (owner/accountant
> modals, the real send → real pending invite + queued email + the exact sent view; both email variants). Suite
> **1271/9583** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–80.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v24) · OWNER NOTIFICATIONS exact-match rebuild COMPLETE.**
>
> **Owner Notifications (`/owner/notification-preferences`, Invariant #79)** rebuilt to the decoded wireframe
> EXACTLY on the REAL `NotificationPreferences` engine — **no schema/service/controller change** (the shared
> engine + the operator prefs page byte-unchanged; founder chose display-only SMS). The engine already existed +
> is real: a typed per-user × per-event × per-channel store whose `allows()` genuinely gates delivery (via
> `FeedNotification::via()`, mutation-proven), owner Mailables extend `FeedNotification`, and the wireframe's 5
> events map EXACTLY to the real owner catalog. **⚠️ EMAIL is REAL** (per-event prefs stored + honored; an
> email-off really stops the email; defaults correct). **⚠️ SMS is HONEST-GATED** — display-only, disabled,
> "not active until SMS is set up", **no `name` → never submitted/stored** (no provider, a Wave-D hard-defer),
> NEVER a toggle that implies a send (#39). **⚠️ The urgent-bypass guarantee is REAL — verbatim** (`account_security`
> is `always_on` → can never be suppressed; no marketing event exists). The **In-app column is dropped** (portal
> always records) but the in-app pref is **preserved** via hidden inputs (the feed is never silently off); the
> owner's OWN email (auth user) + phone (`Owner.phone`) are shown (NOT guest #28); **quiet hours** kept as a
> documented addition. Save = the existing self-only POST (#20) + the wireframe toast. `OwnerNotificationsPageTest`
> (3) proves the sections + real-email-honored + in-app-preserved + no-sms-row + urgent-bypass. Suite **1268/9565**
> green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–79.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v23) · OWNER BOOKING DETAIL exact-match rebuild COMPLETE.**
>
> **Owner Booking Detail (`/owner/bookings/{booking}`, Invariant #78)** rebuilt to the decoded wireframe EXACTLY
> on the already-correct owner-scoped backend (the page was ALREADY owner-scoped via the through-units 404 guard,
> ALREADY refused guest contact #28, ALREADY used the shared `OwnerLedgerAttribution` — so this was a
> presentation rebuild + the two corrections the wireframe gets wrong + the omitted sections). **⚠️ CORRECTION 1
> — #28:** the wireframe exposes guest email/phone/country → REFUSED (never reach the page OR the new PDF; honest
> "contact handled by your management team" line; **"Message {guest}" → the OPERATOR relay** `owner.messages`,
> NEVER owner→guest; country OMITTED — strict name-only). **⚠️ CORRECTION 2 — #17:** commission "· 15%" → the
> REAL **18%** (`OwnerCommissionRate`; the real ledger breakdown — room_revenue €540 − commission €97.20 = net
> €442.80, reconciling to the cent). Rebuilt EXACT: identity header (avatar · name · 4-state pill · sub), the
> reservation day-hero (34px CI/CO + dashed-connector nights pill + 4-col incl. real Nightly rate), the gross→net
> earnings card, the guest card, a REAL **timeline** (from `booked_at`/`payment_status`/`guest_checked_in_at`/
> checked-out/the real `Payout.paid_at`/`cancelled_at` — the fabricated pre-arrival email OMITTED #39), the dark
> **net hero + count-up** (Alpine, presentational, ends at the REAL net, respects reduced-motion), booking-details
> (Rate plan OMITTED — not modelled), and actions. **🔨 BUILT the per-booking confirmation PDF**
> (`OwnerBookingConfirmationDocument` + `pdfs.owner-booking-confirmation` + `OwnerBookingConfirmationPdfController`
> on the `PdfService`/`ExportAudit` pattern — guest NAME-only #28, `CAP_EXPORTS`-gated, owner-scoped via the SAME
> through-units guard). 🔒 #28 (the email/phone refusal proven on the page AND the PDF); #17 real 18% reconciling;
> #20 the gated PDF is the only write; #42 CAP_EXPORTS; #39 no fabrication. `OwnerBookingsTest` 15→18; browser-
> verified (attributed + unattributed). Suite **1265/9528** green; PHPStan L5 (0); Pint clean; `migrate:fresh
> --seed` clean. **Invariants #1–78.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v22) · OWNER MESSAGES exact-match 3-pane rebuild + rich chat COMPLETE.**
>
> **Owner Messages (`/owner/messages`, Invariant #77)** rebuilt to the decoded wireframe as a FULL messaging inbox
> on the REAL owner↔OPERATOR relay (`InboxService`) — NEVER owner→guest; guest data name-only (#28). Founder
> decisions: compose = per-staff/assign · read-status = BUILD the real receipt · presence/typing = honest-omit.
> **GATE 1 (`c070913`)** — the wireframe's single-page **3-pane** (conversation rail + inline active thread pane),
> replacing the old split list + `/{thread}` pages — **this CLOSES the KIND-3 "separate pages" divergence** in the
> design register. Picking a conversation is a GET `?c=` (middleware runs, #20); `owner.messages.show` resolves
> OWNER-SCOPED first (findOrFail → cross-owner/guest 404) then redirects (isolation preserved, not a bare 302). The
> real core: threads (OwnerScope on `owner_id` → guest/cross-owner 404 by construction), the real staff
> correspondent (compose recipients = the org's REAL back-office staff, the ASSIGNEE named until a reply lands),
> owner_last_read_at unread (marked read on the explicit `?c` open BEFORE the rail is computed → same-request badge
> clear), search, mirrored transcript, compose→assign, reply-send, quick-replies, honest empty states. Removed the
> orphaned `OwnerMessageThread` component + view (one messaging concept). **GATE 2** — the six rich features, all
> REAL + mutation-proven (`OwnerMessagesRichTest`): **reply-to** (`reply_to_message_id` self-FK + quote + composer
> context), **attachments** (polymorphic `Media` via the Add-Unit upload pattern; image inline / file chip),
> **reactions** (`message_reactions`, emoji allow-list, idempotent toggle, audited), **read-receipt**
> (`operator_last_read_at` set in `InboxService::markRead` → real Delivered→Read, **never a defaulted ✓✓**),
> **starred** (`message_stars`, per-user panel), **pinned** (`pinned_message_id` banner + toggle; audit + domain
> event). Every write is a controller POST through `InboxService`, owner-scoped (the `{thread}` findOrFail 404s
> cross-owner/guest; every `message_id`/`reply_to` re-validated against the thread). 🔒 #28 owner↔operator only +
> name-only; #20 write discipline; #39 presence/typing/SLA + vendor desks stay honest-omitted/refused. Suite
> **1262/9514** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; all six browser-verified.
> **Invariants #1–77.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-30 (v21) · OWNER REVIEWS & REPUTATION exact-match rebuild COMPLETE.**
>
> **Owner Reviews (`/owner/reviews`, Invariant #76)** rebuilt to the decoded wireframe EXACTLY, on REAL
> owner-scoped data. **The pivotal PHASE-1 finding:** the "agency-reply / response system" ALREADY EXISTED
> (`Review.operator_response` + `responded_at`, `ReviewService::respond`, the operator route `reviews.respond`
> `can:reviews.manage` audited #20, `ReviewList` surfacing it #28) — so it was SURFACED, not rebuilt. What was
> built: the response ANALYTICS + the exact page + the (founder-approved) category ratings. **GATE 1** — a new
> shared `OwnerReviewSummary` (overall / REAL response rate + avg time / last-30 / by-villa + trend / distribution,
> all DERIVED #39), the extended shared `ReviewList` (initials / real reservation SOURCE / when / villa / filter /
> honest "Awaiting a reply" pending), the exact `reviews.blade` (shell + `--vy-*` + `vyFadeUp`), the operator
> responses READ-ONLY, and the HONEST "Handled for you" (OTA review-ingest + Airbnb/Booking.com brand split
> account-gated, **NO fabricated 24h SLA**). **GATE 2** — the founder-approved **per-category sub-ratings**
> (`rating_cleanliness/communication/location/value` nullable + the guest form + `OwnerReviewSummary::categories` +
> demo backfill) → the real "By category" card + **the softest-category note linking the REAL Wave-C rate-proposals
> (`owner.rates`)** — the old refusal is now a real observation + a real link. ⚠️ A **raw-Blade trap**
> (`replied@endif`) swallowed 5 sections while compiling clean — caught by browser-verification (the "static checks
> can't see a rendered page" lesson). 🔒 #28 owner-scoped + initial-only; #39 no fabricated rating/aggregate/
> category/SLA; #20 the response-write stays the operator's audited controller route (owner surface read-only).
> Mutation-proven (`OwnerReviewsPageTest`, 5 — incl. the operator→owner end-to-end flow + category persistence);
> browser-verified. Suite **1255/9470** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean.
> **Invariants #1–76.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-29 (v20) · OWNER-REALM AUDIT FIX PASS COMPLETE (⚠️ per-owner role security).**
>
> **⚠️ SECURITY — per-owner role enforcement (Invariant #75).** A read-only comprehensive owner-realm audit
> (`docs/OWNER-REALM-AUDIT-REPORT.md`) found ONE P1 + two P2s + doc-rot; all fixed in a triaged, per-gate pass,
> every logic/security fix MUTATION-PROVEN (fails-before / holds-after). **P1-1 (the security priority):**
> `SetOwnerScopeForPortal` resolved a portal user's owner-team role as the single HIGHEST role across the UNION of
> ALL their owner memberships (and `isPrimaryOwner` as a GLOBAL boolean; the OAuth API inherited it) — so a user
> who is co-owner/primary of Owner A + a lesser role on Owner B (same tenant) acted with **co-owner powers on B**
> (approve B's expenses/rate-proposals, edit B's rates, read B's masked IBAN, manage B's team, read B via an OAuth
> token — the blast radius was runtime-PROVEN then CLOSED). **Fix:** role is resolved and enforced **PER OWNER** —
> `OwnerContext` holds a per-owner role map + `primaryOwnerIds`, with `canOn(ownerId,cap)` / `isPrimaryOwnerOf` /
> `ownerIdsWithCapability` / `intersectOwners`; `can()`/`isPrimaryOwner()` remain COARSE first-filter route gates
> (exact for the single-owner common case), backed by the authoritative per-owner check at each sensitive
> write/read (expenses, rate-proposals, rates, IBAN, team, unit-pause, block, targets, payout method/prefs,
> attention, statement-PDF/report-email payout visibility); the OAuth scope gate narrows a token to the owners its
> per-owner role actually backs. `OwnerCrossOwnerRoleTest` (7) proves the leak closed both ways + legitimate access
> preserved. **P2-1** Balance clearing identity now holds even after a post-lock refund (a "Carried forward" bucket
> = stamped rows of statements with no active payout → `Available+On hold+In transit+Paid+Carried forward == Net`
> to the cent; `OwnerBalanceReconciliationTest`). **P2-2** Reports "Gross to net" now includes payment_fee (#70) +
> withholding (#73) — a real "Tax withheld" line matching the annual summary, the `reconciles` flag wired into the
> view (`OwnerReportsReconciliationTest`). **P2-3** the My Units calendar day-detail was back-ported from Unit
> Detail (guest name-only #28 + unit + stay + net, shared value rules; browser-verified). **P2-4 + P3 doc-rot:**
> the stale "REFUSED / NO BACKEND" honesty-map comments swept to the shipped reality (comments only). No money
> changed (the two reconciliation fixes correct the DISPLAY/breakdown); single-owner behaviour unchanged. Suite
> **1250/9418** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–75.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-29 (v19) · "ASK VYROBEL" SUPPORT AI COMPLETE.**
>
> **⚠️ A support-AI must NEVER fabricate (Invariant #74).** "Ask Vyrobel" is a REAL grounded (RAG-style) support
> assistant on the owner Help page — and the honesty guarantee is **ARCHITECTURAL, not a trusted-model hope**:
> ① retrieve over the real Help KB → **no relevant content ⇒ escalate to the real relay with NO LLM call** (so an
> out-of-scope or account-specific question — "what's my payout" — can never be handed a figure to repeat, #39/#28);
> ② daily token cap reached / ③ circuit breaker open ⇒ grounded fallback (show the real help + relay), no call;
> ④ else the grounded call, and on ANY provider failure trip the breaker → fallback. Every answer carries
> **citations to the real help screens** + an always-present **escalate-to-team** button (the real owner↔operator
> relay) + the honest *"AI assistant — verify important things; message the team for account help"* note.
> **⚠️ Load-bearing correction:** the prompt's "reuse the existing LlmManager (cost ledger, circuit breaker,
> versioned prompts, Anthropic client)" was FALSE — none existed. The LLM layer is **NET-NEW**, built the
> codebase's config-gated way (mirrors Stripe/Channex): `LlmClientInterface` → `FakeLlmClient` (deterministic,
> default `LLM_FAKE=true`, dev reflects ONLY retrieved help) / `AnthropicLlmClient` (Http, **production-only**
> behind `LlmSafetyGuard::forceFakeOutsideProduction` — a real key never fires outside production); versioned
> `Prompts`; the `llm_usage` cost ledger (`LlmCostLedger`, per-owner/day cap); `AskVyrobelService` orchestrates.
> **What's new:** `App\Support\Help\HelpKnowledgeBase` (the ONE shared FAQ KB — the FAQ page + the assistant read
> it, anti-drift) + `HelpRetriever` (deterministic token-overlap, threshold→empty); the `App\Support\Llm\*` seam +
> cost ledger + circuit breaker; `AskVyrobelService` + `AskResult`; `owner.help.ask` (a controller `<form>`,
> rate-limited — every owner write stays a controller route, mutation boundary intact) + the rebuilt Help hero.
> Behaviour-proven (`HelpRetrievalTest`/`AskVyrobelTest`/`OwnerHelpTest`): in-scope → grounded + citations (1 call);
> out-of-scope/empty → escalate (0 calls); cost cap / provider failure → grounded fallback (0 / breaker-open);
> ledger records; the guard forces fake outside production. Browser-verified end-to-end (grounded answer + source
> link; honest escalate + NO fabricated sources; escalate → a REAL owner↔operator thread). The live Anthropic flip
> is the founder's account-gated go-live. Suite **1240/9392**; PHPStan L5 (0); Pint clean; `migrate:fresh --seed`
> clean. **Invariants #1–74.**
>
> ---
>
> ⭐⭐⭐⭐ **2026-07-29 (v18) · VAT + WITHHOLDING TAX ENGINE COMPLETE.**
>
> **⚠️ Tax figures have legal consequences (Invariant #73).** A real VAT + withholding engine — but the ENGINE is
> code and the RATES are CONFIGURED by the founder per jurisdiction: **the session NEVER invents/hardcodes/presents-
> as-authoritative a tax rate**; it seeds only clearly-labelled EXAMPLE rates the founder MUST replace. Every output
> is labelled *"computed from your configured rates — verify with your accountant; not tax advice"*; unconfigured →
> honest "not configured", never a fabricated figure. Makes REAL what the Reports/Tax-Center audits correctly
> refused. **The key split:** VAT is INFORMATIONAL (no payout effect); WITHHOLDING is a REAL payout deduction.
> **What's new:** `tax_rates` store + `TaxRateService` (`rateFor` — real over example, or null) + `tax:set-rate`
> admin command + EXAMPLE seeds; `OwnerVatService` (output/input/net VAT per quarter from configured rates × real
> gross/commission, reconciling #17; honest states); the `withholding` ledger entry_type + statement bucket +
> `WithholdingService` (posts the computed line at finalize from the pre-withholding net → reduces `net_payable` →
> the payout, reconciling #17 — exactly the `payment_fee` pattern, so the payout engine is unchanged); the Reports
> VAT summary (deferred → REAL, honestly labelled + example warning) + VAT-return CSV + the Tax Center withholding
> card. Mutation-proven (`TaxRateConfigTest`/`OwnerVatTest`/`WithholdingTest`/`OwnerTaxSurfaceTest`): VAT reconciles
> quarter→annual; withholding reduces the net correctly (#17 both states); unconfigured → honest; the demo net
> €885.60 unchanged (no PT withholding rate). Browser-verified (Reports VAT computed + example-flagged + "not tax
> advice"). Suite **1217/9225**; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–73.**
>
> ---
>
> ⭐ **PRIOR — 2026-07-29 (v17) · OAuth2 AUTHORIZATION SERVER COMPLETE. This block SUPERSEDES every
> block below it. Fresh-session entry point: this block → `docs/OAUTH-DESIGN.md` → `docs/DECISIONS.md`.**
>
> **⚠️ SECURITY feature (Invariant #72).** A real OAuth2 authorization server (**Laravel Passport v13.7.5**, clean
> on Laravel 13) so third-party apps get SCOPED, owner-consented, tenant-isolated, revocable access — the owner
> Security page's "Connected apps" ("Coming soon") is now REAL. **Fully additive** — the `api` (passport) guard is
> only invoked explicitly (`auth:api`); the session realms + default `web` guard are untouched. It ADDS a third
> gate (the token scope) on top of the certified `OwnerScope`/`OrganizationScope`/`OwnerTeamRole` — never a new
> isolation mechanism. **What's new:** the `api` guard + `HasApiTokens`/`OAuthenticatable` on `User` (ULID `user_id`
> migrations; keys gitignored); **`OAuthScopes`** (least-privilege read-first: read:bookings/statements/units →
> owner-cap map); `OAuthClientRegistrar` + `oauth:register-client` (platform-admin, no self-serve); a **branded
> consent screen** (`Passport::authorizationView`, exact requested scopes) + authorization-code + **PKCE** + audited
> token issuance; **`routes/api.php` TRIPLE-GATED** (`auth:api` → `ResolveApiOwnerContext` [token→owner+org, fails
> closed] → `EnsureOwnerApiScope` [scope + owner-cap, no over-grant]); `OwnerApiController` (safe fields, name-only
> #28, no arrival secrets #33); `OAuthConnectionService` + `OwnerConnectedAppController` (self-only, re-auth, audited
> revoke). **Mutation-proven** (`OAuthClientTest`/`OAuthConsentFlowTest`/`OAuthApiTest`/`OAuthConnectedAppsViewTest`):
> owner + scope isolation both ways · no over-grant (accountant-role token can't carry read:bookings) · non-owner
> 403 · deny→no token · approve→only-requested-scopes · PKCE enforced · **revoke immediate** (revoked → next request
> 401). Browser-verified: consent shows exactly the requested scopes; connected app + revoke render live. **Note:**
> `composer audit` flags 10 advisories on `dompdf/dompdf` (<3.1.6) — PRE-EXISTING, unrelated (a dompdf bump is
> separate). Suite **1201/9162**; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–72.**
>
> ---
>
> ⭐ **PRIOR — 2026-07-29 (v16) · AUTOMATED PAYOUT ENGINE (TEST-MODE) COMPLETE. This block SUPERSEDES
> every block below it. Fresh-session entry point: this block → `docs/PAYOUT-ENGINE-DESIGN.md` → `docs/DECISIONS.md`.**
>
> **⚠️ REAL-MONEY seam, built TEST-MODE-only (Invariant #71).** The automated payout engine (`docs/PAYOUT-ENGINE-DESIGN.md`)
> replaces the honest "payouts handled by your agency" (Option A) with a REAL scheduled engine running in TEST MODE —
> real money NEVER moves in the build (`PayoutEngineMode`='test'; the manual rail records / the fake Connect client
> simulates; `StripeSafetyGuard` forbids a live key outside production). It ORCHESTRATES the certified
> `OwnerPayoutService` + Connect rail + webhook (never reimplemented). **What's new:** `payout_runs` (platform-level
> audit + idempotency) + `PayoutRun`; a persisted **`held`** payout status (the tax-hold is now real + auto-releasing)
> + `payout_run_id`/`held_at`; `OwnerPayoutService::hold()/releaseHold()` + a widened idempotency guard;
> `PayoutRunService` (super-admin owner selection → per-org create/pay/hold, idempotent on `run_key`); `PayoutSchedule`
> (pure cadence — Nth business day / weekly, `isDue`/`nextRunDate`); `runScheduled`+`dueOwnerIds` (paused/minimum/due);
> the `payouts:run` command (inert unless `PAYOUT_ENGINE_ENABLED`, TEST-MODE banner) + a daily scheduler; `PayoutEngineMode`.
> **Surfaced (test-mode-labelled):** the Balance clearing model folds in `held`/`processing` so `Available + On hold +
> In transit + Paid == Net` stays exact (#17); the Payouts "Next scheduled payout" is the real `nextRunDate`; a shared
> `<x-owner.payout-test-mode>` banner on both surfaces; withdraw stays disabled. Mutation-proven across
> `PayoutEngineTest` / `PayoutScheduleTest` / `RunScheduledPayoutsCommandTest` / `PayoutEngineStripeTest` /
> `PayoutEngineSurfaceTest`; browser-verified as the demo owner (held + banner + real next date). Live cutover =
> the founder's step (`STRIPE-PRODUCTION.md` §3b), documented not done. Suite **1183/9091**; PHPStan L5 (0); Pint
> clean; `migrate:fresh --seed` clean. **Invariants #1–71.**
>
> ---
>
> ⭐ **PRIOR — 2026-07-29 (v15) · OWNER COMPLETENESS SWEEP · WAVES A + B + C COMPLETE. This block SUPERSEDES
> every block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block →
> `docs/DECISIONS.md` → `docs/OWNER-COMPLETENESS-SWEEP.md`.**
>
> **Wave C (🟠 founder-approved product-decision features — ⚠️ REAL money, full money-path discipline; Invariants #67–70):**
> **GATE 1 RATE-PROPOSALS (#67)** — real operator→owner `rate_proposals` (exclusive-owned; pending→approved|declined|
> withdrawn); operator PROPOSES (`rates.manage`) → owner APPROVES (CAP_ACTIONS) applies via the **certified
> `OwnerRateService`** (rate really changes, mutation-proven) / DECLINES / operator WITHDRAWS; pending = a real Approve/
> Decline attention item (extended `OwnerAttention.actions`, ONE shared feed for proposals + expenses on My Units +
> Overview) + a real Insights link. `RateProposalService` / `Owner|Operator RateProposalController` / `RateProposalTest` (6).
> **GATE 2 EXPENSES-APPROVAL (#68 ⚠️ deduction model)** — `owner_expenses.approval_state` pending→approved|queried; an
> expense is CREATED **pending + NOT charged** (no ledger line → net unchanged), owner APPROVES → the debit posts → net
> drops → **reconciles #17**, QUERY → nothing charged + opens the owner↔operator relay (name-only #28); **#17 proven
> BOTH states** (`ExpenseApprovalTest` 5); maintenance-sourced costs stay auto-charged (certified path intact); no
> auto-approve timer (#39). **GATE 3 OWNER UNIT PAUSE/RESUME (#69)** — `OwnerUnitStatusService` toggles `units.is_active`
> + recomputes availability (certified `AvailabilityService`, 365d) so a paused unit **genuinely stops taking new
> bookings** — mutation-proven (`OwnerUnitStatusTest`: booking throws `InsufficientAvailabilityException` while paused,
> succeeds on resume; existing stays kept #7); owner-scoped + CAP_ACTIONS POST + audited; toggle on My Units + Unit
> Detail. **GATE 4 STATEMENT "Payment & ops fees" bucket (#70)** — new `payment_fee` ledger entry_type +
> `owner_statements.payment_fee_minor` + statement line (signed sum of payment/ops fee lines, in `net_source_minor` by
> construction → gross − commission − fees − refund == net to the cent, page + PDF); **NEVER fabricated (#39)** — no
> writer posts fees yet (deferred live-payment seam) → genuine €0 → line omitted; proven both states
> (`OwnerStatementTest`); `OwnerTaxService.annualSummary` includes it; **Sep demo net €885.60 unchanged**. Suite
> **1161/8989** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; pause/resume + statement breakdown
> browser-verified. **Invariants #1–70.**
>
> ---
>
> **Wave A + B (2026-07-29):** the owner completeness sweep built what earlier owner-page exact-matches had deferred/refused/simplified.
> **Wave A** (shared backends, anti-drift): `owner_attention_states` store · REAL weekend-uplift pricing
> (`setRateWeekendAware`) · modelling adds (owner address → statement letterhead, expense vendor) · quick wins
> (Email-to-me Mailable, Overview goal line, Insights vs-last-year). **Wave B** (per-page, consumes Wave A):
> **My Units** (action-center store consumption + bulk + weekend-uplift editor) · **Unit Detail** (weekend uplift +
> richer day-detail #28 + read-only cleaning schedule from real `HousekeepingTask`) · **Rates** (nightly + weekend +
> min-stay manage view; fixed a latent min-stay read bug) · **Security** — 3 REAL features, all mutation-proven:
> **trusted devices** (skip-2FA, `trusted_devices`, hash-only revocable cookie) · **login approval** (`login_approvals`,
> opt-in, signed-link email, held-until-approved, single-use, chains to 2FA) · **passkeys/WebAuthn** on
> **web-auth/webauthn-lib ^5.3** (`passkeys`; real register + usernameless sign-in **proven end-to-end in Chromium via a
> CDP virtual authenticator**) · **Team** (`export_downloads` + `ExportAudit::stamp` → watermarked exports + a real
> download log; the security note's claim now TRUE) · **Settings** (quiet-hours held-EMAIL window; theme kept honest —
> no dark palette) · **Onboarding** (`/owner/welcome`, the IBAN-less thin flow). deps: web-auth/webauthn-lib (founder-
> approved). Honesty held (no fake per-unit-attention/cleaner/trusted/approved/passkey/watermark-not-applied/IBAN/dark-
> theme #39). Suite green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–66.**
>
> ---
>
> ⭐ **PRIOR — 2026-07-28 (v13) · owner SECURITY + HELP exact-match (#66); Onboarding DEFERRED (now built in Wave B).**
>
> **Two owner account pages matched to their wireframes; a third (Onboarding) deferred (`docs/OWNER-SECURITY-ONBOARDING-HELP-AUDIT.md`,
> DECISIONS 2026-07-28 #66, DIVERGENCE Security/Help rows). Both were already built honestly (#46 + the static Help
> surface); this pass matched their design while keeping every honest call.**
> - **GATE 1 — Security (`aa041f9`):** `/owner/security` matched to the wireframe on the REAL backend (REUSED —
>   password/2FA/sessions/export-JSON/deactivate). The hero **score card** now renders a **REAL DERIVED score** (Password
>   40 + 2FA 40 + Recovery codes 20 = /100; tier Basic/Good/Strong; 4 real stat cards; "Boost your score" links the real
>   2FA control) — never a fabricated number (#39). Trusted-locations / connected-apps / login-approval / passkeys stay
>   honest **"Coming soon"**.
> - **GATE 3 — Help:** `/owner/help` matched HONESTLY — an **FAQ-search hero** (no fake "Ask Vyrobel" AI), the real
>   **message relay** + honest contact (NO invented phone/email/WhatsApp numbers), **messaging as the tracked channel**
>   (no fake ticket queue), a **static "All systems operational"** line (no live health check), and the real FAQ.
> - **GATE 2 — Onboarding DEFERRED (founder decision):** net-new, not launch-blocking, and its centerpiece (**IBAN
>   capture + "micro-deposits on the way"**) is un-buildable-honestly — the real banking path is the certified
>   `owner.payout-method` page. If built later: an honest thin flow (profile confirm + real 18% units + a hand-off to
>   Payout Method + notification prefs), NO IBAN capture, NO fake micro-deposit.
> - **Verified:** `OwnerSecurityTest` 6/6 (the derived score reflects true state — 1/3 protections + Basic tier + the 2FA
>   nudge) + a new `OwnerHelpTest` (real relay + FAQ; no fake AI/ticket/status/number). Suite **1096 / 8653 GREEN**; PHPStan
>   **L5 = 0**; Pint clean; `migrate:fresh --seed` clean; both browser-verified (Security score ring + real controls; Help
>   FAQ search filters, honest states). No schema change, no new writes. **Invariants #1–66.**
>
> Owner exact-match track shipped: Overview (#54) … Tax Center (#64) · Team & access + Unit Detail (#65) · **Security +
> Help (#66)**. Remaining built-but-not-yet-exact-matched: MANAGE — Rates / Reviews / Agreement / Messages · **Onboarding**
> (deferred net-new). No launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v12) · owner TEAM & ACCESS + UNIT DETAIL exact-match (#65). This block SUPERSEDES
> every block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block →
> `docs/DECISIONS.md`.**
>
> **Two owner pages matched to their wireframes, gated per page (`docs/OWNER-TEAM-UNITDETAIL-AUDIT.md`, DECISIONS
> 2026-07-28 #65, DIVERGENCE Owner-Team + Owner-Unit-Detail rows).**
> - **GATE 1 — Team & access (`e5d1a1f`):** rebuilt `/owner/team` on the REAL owner-team feature (REUSED — members /
>   role change / remove / invite / pending revoke, all `EnsurePrimaryOwner`-gated POSTs, #20). Added: the **"what each
>   role can see" matrix REAL from `OwnerTeamRole::CAPABILITIES`** (the SAME grid the gates read — anti-drift); a
>   **TRUTHFUL recent-activity** (real sign-ins + invites, never a fabricated log); a **NEW real RESEND**
>   (`owner.team.invites.resend`); and an **HONEST security note** — the wireframe's "exports are **watermarked** with who
>   downloaded them" claim was FALSE (no watermark is applied) → softened to the true claim (#39).
> - **GATE 2 — Unit Detail:** rebuilt `/owner/units/{unit}` — an AGGREGATION page. **The Wave-1 "no backend" refusals were
>   STALE** (backends shipped later); re-verified + now BUILT REAL: **day-blocking** (tap-to-block → `OwnerBlockService`,
>   browser-proven), **nightly-rate + min-stay edit** (`OwnerRateService`, browser-proven €180→€185 + returns to the unit),
>   **reviews** (`ReviewRating`/`ReviewList`, byline first-name+initial #28), and **guest-messages → the owner↔operator
>   relay** (`owner.messages.start`, name-free #28, never owner→guest). **Weekend uplift** honest "Not available";
>   **snooze / OTA-sync / cleaning-schedule / per-unit-payout-timeline / "Direct saved you"** stay honest/refused (#39).
>   The existing real sections (KPIs / occupancy / revenue / attribution-deductions [reconciles #17] / stays [name-only
>   #28] / channel-mix / commission / best-badge) were restyled to the wireframe, unchanged in data.
> - **Verified:** `OwnerTeamInviteTest` 6→8 + `OwnerUnitDetailViewTest` 8→10 (the stale Wave-1 fiction test updated for
>   the now-real features). Suite **1095 / 8626 GREEN**; PHPStan **L5 = 0**; Pint clean; `migrate:fresh --seed` clean;
>   browser-proven (Team: matrix = 11 real grants, resend works, no watermark; Unit Detail: rate edit + tap-to-block
>   mutate real data, view-only sub-user gets no write control). One additive `OwnerRateController` redirect branch;
>   nothing else disturbed. **Invariants #1–65.**
>
> Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF
> (#57) · Payouts + Payout Method (#58) · Balance + Expenses (#59) · Block Calendar + Documents (#60) · Reports + Insights
> (#61) · Settings hub (#62) · Reports deepened (#63) · Tax Center (#64) · **Team & access + Unit Detail (#65)**. Remaining
> built-but-not-yet-exact-matched: MANAGE — Rates/Reviews/Agreement/Messages · ACCOUNT — Security · Help. No launch-blocking
> build remains.
>
> ---
>
> ⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v11) · owner TAX CENTER exact-match (#64). This block SUPERSEDES every block
> below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block → `docs/DECISIONS.md`.**
>
> **The owner Tax Center (`/owner/tax`) — one of the most-REAL owner pages — was matched to the full decoded
> `Owner Tax Center.html` (`docs/OWNER-TAXCENTER-AUDIT.md`, DECISIONS 2026-07-28 #64, DIVERGENCE Owner-Tax-center row).
> This page GATHERS already-built features into one screen; the build REUSED them (anti-drift, proven shared) + added
> one small additive column + an honest withholding state. Single gate.**
> - **REUSE map (every section wires to a built feature):** the **2-state tax-hold banner** (accent *incomplete* with the
>   real held amount + **GREEN** *complete*) = the SAME hold as Payouts/Balance (`OwnerTaxService::heldTotalMinor` +
>   `Owner::hasTaxDetails`; `OwnerPayoutService::pay` refuses without a tax ID) — **browser-proven: saving a tax ID
>   released the real €885.60 hold AND cleared it on Payouts too**; the **Tax profile** form → the ONE `owner.tax.save`
>   store Settings/Payouts also write (encrypted #28, audited, self-only, `$capActions` #20); the **Annual summaries** +
>   per-year **PDF** (`owner.tax.annual`, reconciling #17, `$capExports` #42); the **Documents** link (`owner.documents`).
> - **D-TAX-1 — WITHHOLDING honest (NOT modelled).** The wireframe's residency-driven "15% treaty / 20% non-resident …
>   withheld on payouts" is fabricated (Vyrobel withholds nothing) → rendered as an HONEST "None" card, never a fake rate
>   (#39). Same discipline as the Reports VAT block.
> - **D-TAX-2 — the "VAT registered" toggle stores a REAL flag.** One additive `vat_registered` boolean (a REGISTRATION
>   FLAG for the letterhead — NOT a VAT computation); de-registering drops the encrypted number (the flag is the truth).
> - **Verified:** `OwnerTaxCenterTest` 5→7 (`vat_registered` round-trips through the shared store + de-register drops the
>   encrypted number; honest withholding — never "treaty rate"/"withheld on payouts"; the green complete state). Suite
>   **1091 / 8570 GREEN**; PHPStan **L5 = 0**; Pint clean; `migrate:fresh --seed` clean; browser-verified (save→hold
>   released + cleared on Payouts, VAT toggle reveals the number, annual PDF 200). Additive column only — nothing
>   disturbed. **Invariants #1–64.**
>
> Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF
> (#57) · Payouts + Payout Method (#58) · Balance + Expenses (#59) · Block Calendar + Documents (#60) · Reports + Insights
> (#61) · Settings hub (#62) · Reports deepened (#63) · **Tax Center (#64)**. Remaining built-but-not-yet-exact-matched:
> MANAGE — Rates/Reviews/Agreement/Messages · ACCOUNT — Security/Team · Help. Everything else below is current; no
> launch-blocking build remains.
>
> ---
>
> ⭐⭐ **CURRENT STATE — 2026-07-28 (v10) · owner REPORTS exact-match DEEPENED to the full wireframe (#63). This block
> SUPERSEDES every block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block →
> `docs/DECISIONS.md`.**
>
> **The owner Reports page (`/owner/reports`) — built at #61 with the honest data right — was deepened to the FULL decoded
> `Owner Reports.html` (design + behaviour) on REAL owner-scoped analytics (`docs/OWNER-REPORTS-AUDIT.md`, DECISIONS 2026-07-28
> #63, DIVERGENCE Owner-Reports row). Read-only: year / date-range / export are GETs → no write to gate (#20 has nothing to
> guard here).**
> - **GATE 1 (`f05a9b8`) — analytics exact-match + two real data upgrades:** the reporting-year dropdown driven by the owner's
>   REAL statement years (never the wireframe's fabricated 2024/2023) + a custom From/To date-range (real GET clip) + a richer
>   CAP_EXPORTS-gated Export menu (real PDF/CSV/spreadsheet/annual-tax/all-statements; PDF-pack + Email honest-"Soon"); KPIs
>   (YoY Δ **honest-omitted** — no prior-year data, #39); net-by-month chart (peak bubble; prior-year series omitted); per-unit
>   grid (ADR/RevPAR header + share bars + All-units total); **Booking sources = NET by channel** (NEW REAL — ledger×
>   `reservations.source` via the SHARED `OwnerBookingsReader` labels; **Σ channels == period net EXACTLY**, #17 — replaced
>   nights-by-channel, a stale-refusal re-verification win); gross-to-net keeps the **REAL 18% agreement rate** (`OwnerCommissionRate`,
>   never 15%), reconciles; dark hero (YoY badge omitted); payout card (masked IBAN CAP_PAYOUT-only #28); real Documents;
>   notifications = the shared shell bell (redundant right-rail card dropped).
> - **GATE 2 — Tax & VAT summary honest-deferred in the wireframe's card chrome.** The wireframe's Output/Input/**Net VAT due ·
>   payable to [authority]**/per-quarter/**Mark-filed**/numbers-bearing VAT-return CSV is a KIND-2 fabrication — Vyrobel models
>   **no VAT** (only a stored encrypted `vat_number`). REFUSED the figures; styled the honest deferred state (icon + header +
>   "Not yet available" chip + real tax-country when set + real annual-tax PDF/Tax-Center links; no figure/toggle/return, #39).
> - **Verified:** `OwnerAnalyticsTest` 7→10 (net-by-channel reconciles; real year dropdown; custom range clips; honest VAT).
>   Suite **1089 / 8541 GREEN**; PHPStan **L5 = 0**; Pint clean; `migrate:fresh --seed` clean; browser-verified (year dropdown
>   "2026 · 6 statements YTD", custom range Aug–Oct €2,750.80, 3 real exports 200, net-by-channel Direct €4,460.80 + Walk-in
>   €442.80 + Other −€250.00 = €4,653.60, honest VAT). No schema change, no new writes. **Invariants #1–63.**
>
> Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF (#57) ·
> Payouts + Payout Method (#58) · Balance + Expenses (#59) · Block Calendar + Documents (#60) · Reports + Insights (#61) ·
> Settings hub (#62) · **Reports deepened to full wireframe (#63)**. Remaining built-but-not-yet-exact-matched: EARNINGS —
> Tax-center · MANAGE — Rates/Reviews/Agreement/Messages · ACCOUNT — Security/Team · Help. Everything else below is current; no
> launch-blocking build remains.
>
> ---
>
> ⭐ **CURRENT STATE — 2026-07-28 (v9 · TOTAL-RECALL CONSOLIDATION, verified against the repo: `git log`, `composer.json`,
> `php artisan test`, `route:list`). This block SUPERSEDES every block below it (all kept as historical detail).
> Fresh-session entry point: `docs/RESUME.md` → this block → `docs/DECISIONS.md`. HEAD `d2091b4` on `main` (== origin,
> tree clean).**
>
> **WHAT VYROBEL IS.** A multi-tenant, AI-native **hotel/apartment-management SaaS** — PMS + channel manager + booking
> engine + operator/owner/guest apps + a public storefront + self-signup + a platform Super-Admin panel — for operators
> running a MIX of hotels, aparthotels, serviced apartments, villas and multi-unit rentals.
>
> **STACK (locked — `docs/DECISIONS.md`).** **Laravel 13** (`^13.8`) on **PHP ^8.3** (composer floor; local runs 8.4) ·
> **Livewire 4** + Blade + **Tailwind 4** (Vite) · **MySQL 8 / InnoDB** (genuine MySQL, not MariaDB) · queues on
> **Redis + Horizon** · **ULID** PKs on tenant tables · **spatie/laravel-permission teams-mode** RBAC (team = org) ·
> **shared-DB multi-tenancy** via `organization_id` + the `BelongsToOrganization`/`OrganizationScope` global scope
> (`runAsSuperAdmin` = the ONE sanctioned bypass) · integer minor-unit money (`*_minor` + the `Money` cast) · Pest 4 /
> PHPStan **L5** / Pint. Browser verification = the **Playwright MCP** driving `php artisan serve` (real, but NOT a
> re-runnable suite).
>
> **VERIFIED THIS PASS (2026-07-28).** Suite **1086 tests / 8497 assertions GREEN** (`php artisan test`, exit 0) ·
> PHPStan **L5 = 0** · Pint **clean** · `migrate:fresh --seed` clean · **Invariants #1–62** (the numbered ledger below
> now reaches #62 — reconciled with the prose this pass). Requires a genuine MySQL 8 `vyrobel_test` DB + Redis.
>
> **BUILD STATUS — everything buildable is COMPLETE.**
> - **Phases 0–7 DONE** (the whole tenant product): 0 fix/harden · 1 operator app · 2 owner portal · 3 notifications +
>   transactional emails · 4a/b/c guest portal · 5 reviews · 6a multi-property + 6b analytics · 7a tenant provisioning /
>   self-signup · 7b subscription billing (free-trial) · 7c owner sub-tenancy + owner team · 7d staff rostering.
> - **Phase 8 (account-gated integrations): the two obtainable ones are LIT UP, test-mode / code-complete** — PRODUCTION
>   EMAIL (Postmark; `MailSafetyGuard`; `docs/EMAIL-PRODUCTION.md`) + **LIVE STRIPE across all three seams** (subscription
>   billing · guest card charge · owner payout rail) webhook-driven, signature-verified, multi-layer idempotent, no raw
>   PAN (SAQ A), `StripeSafetyGuard` refuses a live key outside `production`; `docs/STRIPE-PRODUCTION.md`. **The live
>   cutovers are the founder's** (email DNS+token; the Stripe live-key flip) — never done in the build.
> - **Design-fidelity pass (all 4 gates) + polish sweep + gap-sweep Batches A/B/C + add-charge idempotency — DONE.**
> - **THE SUPER ADMIN PANEL (`/admin`) — COMPLETE + AUDITED (invariants #49–53):** Gate 0 (isolated realm, seeded-not-
>   signup, noindex) → Build 1 (Dashboard · Customers [suspend/reactivate/assign-plan] · Plans [DB-backed CRUD +
>   additive feature-gating]) → Build 2 (Security & Activity Log · Integrations [encrypted channel credentials]) → deep
>   Playwright audit (`docs/SUPERADMIN-AUDIT.md`, 0 critical / 0 P1) → Profile & Account page → completeness review
>   (`docs/SUPERADMIN-COMPLETENESS.md`). Launch-ready.
>
> **THE OWNER-PAGE EXACT-MATCH TRACK — matching each LIVE owner page to its decoded wireframe (exact design + REAL
> owner-scoped backends, HONEST where account-gated). DONE + PUSHED, `#54`–`#62`:**
> Overview (**#54**) · My Units + Add Unit (**#55**) · Bookings page + Booking-Summary PDF (**#56**) · Statement page +
> Statement PDF (**#57**) · **Payouts + Payout Method (#58)** · Balance + Expenses (**#59**) · Block Calendar + Documents
> (**#60**) · Reports + Insights (**#61**) · **Settings hub (#62)**. Each was a PHASE-1 read-only audit
> (`docs/OWNER-*-AUDIT.md`) → a gated build (GATE 1 design + real backends · GATE 2 doc twin / second section-group) →
> per-gate commit → suite + PHPStan + Pint + browser-verify. **REMAINING owner pages (built + at-parity but NOT yet
> exact-matched — the next candidates, NOT launch-blocking):** EARNINGS — **Tax center** · MANAGE — **Rates · Reviews ·
> Agreement · Messages** · ACCOUNT — **Security · Team** (dedicated real pages) · **Help**. (`Reports + Insights` and
> `Settings` were prompts-written earlier and are now BUILT — #61/#62.)
>
> **⚠️ #58 PAYOUTS — WHAT IT ACTUALLY IS (verified in the repo; note vs any "Option A / no-IBAN" framing).** Both pages
> match their wireframes real-where-real + honest-where-account-gated. **REAL / built:** the payout **summary + history +
> KPIs + reconciling breakdown + remittance PDF** (all statement-derived) · the masked payout **IBAN** (`OwnerNextPayout`,
> CAP_PAYOUT-only #28) · the **bank-account CRUD** — `PayoutMethod` with an **ENCRYPTED `iban`** (+ `iban_last4`/country),
> the add-route **ISO mod-97-validated** before storing, self-only · **Stripe Connect onboarding** (`OwnerConnectController`,
> **test-mode**, + a signature-verified `webhooks.stripe.connect`). So the app **DOES collect (encrypted) bank details** —
> it is NOT a no-IBAN honest-summary-only page. **DEFERRED = the automated payout EXECUTION/DISBURSEMENT ENGINE** (scheduled
> payouts, micro-deposit/bank verification, SEPA tracing, the 48h hold, instant withdrawal) — a major fintech initiative
> account-gated on live Stripe/banking + PSD2/PCI/GDPR; the pages show real statement-derived money and label these
> pending-live, **never faked**. `ManualPayoutRail` is the real, tested disbursement path; the Balance page's "Withdraw
> early" is honestly disabled (no owner-initiated transfer rail). **If the founder wants a true "Option A" (collect NO
> IBAN until a certified pipeline exists), that is a CODE change — the repo currently collects encrypted IBANs.**
>
> **THE DECODE METHOD (owner wireframes are `dc-runtime` bundles).** `JSON.parse` the `<script type="__bundler/template">`
> content → the `<x-dc>` body = the real markup + inline `style` tokens + `{{ placeholders }}`. Decoder used this pass:
> `node /tmp/decode_wf.mjs "docs/design/raw/Owner X.html"`. Serve `docs/design/raw` over `php -S` to render (it self-
> hydrates; looping animations block full-page screenshots — extract the DOM/tokens instead). **REUSE the shared owner
> chrome** `<x-owner.shell active= title= subtitle= viewer=>` + the layout's warm-glass `--vy-*` tokens (`--vy-grad`,
> `--vy-grad-dark`, `--vy-accent`, `--vy-card`, `--vy-kpi-grid` [`--2`/`--4`], `--vy-track`, `--vy-status`, animations
> `vyFadeUp`/`vyShimmer`/`vyDrift`/`vyToastIn`/`vyCardIn`) — extend, never one-off. **Two live Blade traps:** a directive
> whose `@` follows a word char is NOT compiled (`word@if` ships raw source); and a shell `title="A &amp; B"` DOUBLE-
> escapes through `{{ $title }}` → pass a plain `&`.
>
> **THE HONEST DEFERRALS (do NOT build ad hoc; the register lower down is authoritative).** Stripe billing sync ·
> operator impersonation · suspension enforcement · per-feature usage limits · **real channel sync** (Channex/OTA —
> account-gated; the Settings "Connected channels" shows Direct live + OTA honest-**Pending**, never a fake "Connected") ·
> customer self-connect · re-point the Horizon gate to the `admin` guard · the My-Units action-center snooze/day-detail ·
> the operator approve→create-Unit flow (a `UnitRequest` is reviewed, never auto-created) · **⚠️ the automated PAYOUT
> ENGINE** (above — deferred fintech) · **owner expense-APPROVAL + rate-PROPOSAL workflows** (no such entity — REFUSED as
> fabrication; Expenses/Insights link the real Rates/Tax instead) · **VAT filing-status / VAT computation** (not modelled
> — Reports' Tax&VAT card is honest-deferred) · **SMS + push notifications** (no provider — the real channels are in-app +
> email; Settings labels SMS/Push "soon") · the remaining un-exact-matched owner pages · the Phase-3 tails.
>
> **WHAT'S LEFT.** No LAUNCH-BLOCKING build remains — the product is demo-ready end-to-end and the Super Admin panel is
> production-ready. A **free-trial / early-access** launch works with the panel as-is; a **paid** launch needs only the
> already-scoped **Stripe go-live** + **suspension enforcement**. Then **DEPLOYMENT** (production hosting / domain / HTTPS
> / MySQL / Redis-Horizon) + the founder's **cutovers** (email DNS+token; Stripe live-key). The owner-page exact-match
> work is ongoing **polish, NOT launch-blocking** — and the audits repeatedly find the wireframes describe MORE product
> than exists (approval/proposal/VAT/channel-sync/payout-engine), so real usage should drive **which deferred capability
> to actually build** rather than chasing every prototype affordance.
>
> **LOGIN CREDENTIALS** (all password `password`; `php artisan migrate:fresh --seed` recreates them):
> operator back office `demo-staff@vyrobel-demo.test` (Hotel Manager) or `demo-admin@vyrobel-demo.test` (org admin) → `/app` ·
> owner `demo-owner@vyrobel-demo.test` (External Owner, primary) → `/owner` · guest `demo.traveller@vyrobel-demo.test` →
> `/guest/login` · **platform admin `admin@vyrobel-demo.test` → `/admin` (forced 2FA enrol on first login)**. Public
> storefront `/book/seaside-demo`; self-signup `/signup`. (Also seeded: owner sub-users accountant/view-only; operator
> accountant/housekeeper — `database/seeders/DemoSeeder.php` + `PlatformAdminSeeder.php`.)
>
> ---
>
> ⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v8) · owner SETTINGS HUB exact-match (#62). This block SUPERSEDES every
> block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block →
> `docs/DECISIONS.md`.**
>
> **The owner-page exact-match track reached its largest surface: the owner Settings HUB (`/owner/settings`) — the
> 1,136-line wireframe's 13 sections (`docs/OWNER-SETTINGS-AUDIT.md`, DECISIONS 2026-07-28 #62, DIVERGENCE row 144).**
> A HUB: MOST sections REUSE features already built (anti-drift, not rebuilt); honest where account-gated. Built in two
> gated section-groups.
> - **GATE 1 — identity & money (`7981030`):** Hero (derived completeness + real stats) · Profile (`OwnerAccountController`)
>   · Business & tax (`owner.settings.update` + the ONE `owner.tax.save`) · **Billing** (the REAL management-fee rate via a
>   new shared `OwnerCommissionRate` — 18%, NEVER the wireframe's 15%; the owner has no card/subscription → honest fee view,
>   no fabricated card) · Payout account (real masked IBAN, `OwnerNextPayout`, CAP_PAYOUT #28) · Profile completeness
>   (derived). `OwnerReports` refactored onto the shared `OwnerCommissionRate` (one definition, two surfaces).
> - **GATE 2 — preferences & access:** Language & region (locale real; currency/timezone the property's, read-only) ·
>   Notifications (reuse `owner.notification-preferences` — **in-app + email real; SMS + push honest-pending**, no provider)
>   · Connected channels (**Direct live; OTA honest-Pending**, never a fake "Connected") · Team access (summary + the reused
>   `owner.team`) · Appearance & motion (**real reduce-motion**; theme soon) · Data export (real CAP_EXPORTS exports #28).
> - **Verified:** `OwnerSettingsTest` 5→7 + `OwnerAccountProfileTest` updated; PHPStan **L5 = 0**; Pint clean; the whole
>   13-section hub browser-verified (18% fee, ···· 1000 IBAN, OTA Pending, SMS/Push soon, real team/exports, no raw Blade).
>   🔒 masked IBAN never full + CAP_PAYOUT #28, real fee rate #17, controller-route writes #20, exports #42, nothing
>   fabricated (no fake channel-sync / SMS / push / card) #39/#37. **Invariants #1–62.**
>
> **Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF
> (#57) · Payouts + Payout Method (#58) · Balance + Expenses (#59) · Block Calendar + Documents (#60) · Reports + Insights
> (#61) · Settings hub (#62).** Remaining built-but-not-yet-exact-matched: EARNINGS — Tax-center · MANAGE —
> Rates/Reviews/Agreement/Messages · ACCOUNT — Security/Team (their dedicated pages) · Help. Everything else below is
> current; no launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v7) · owner REPORTS + INSIGHTS exact-match (#61). This block SUPERSEDES
> every block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block →
> `docs/DECISIONS.md`.**
>
> **The owner-page exact-match track added its 11th + 12th surfaces: the owner Reports (`/owner/reports`) + Insights
> (`/owner/insights`) pages (`docs/OWNER-REPORTS-INSIGHTS-AUDIT.md`, DECISIONS 2026-07-28 #61, DIVERGENCE Reports/Insights
> rows).** Both wireframes decoded; both rebuilt EXACTLY on the REAL Batch-B analytics (`PerformanceMetrics`,
> `BookingCadence`, `OwnerLedgerAttribution` — reused, anti-drift). Two judgment calls founder-approved (VAT honest-defer;
> proposals honest link).
> - **Reports (GATE 1, `949ea93`):** 3 KPIs + Net-earnings-by-month + Performance-by-unit (ADR/RevPAR/nights/occ/net/share)
>   + Booking-sources-by-channel (real `reservations.source`) + the **Gross-to-net** (reconciles to the cent #17 at the
>   **REAL agreement commission rate 18%** — read from `ManagementAgreement`, NEVER the wireframe's hardcoded 15%) + payout
>   summary + real Documents + CAP_EXPORTS-gated exports. **HONEST-DEFER:** the Tax & VAT summary is NOT modelled → an
>   honest deferred card (no fabricated Output/Input/Net VAT figure or "Mark filed", #39); "Email to me" = honest Soon.
>   Notifications = the real self-scoped feed.
> - **Insights (GATE 2):** 2-column rebuild — Occupancy-by-month (this-year + on-the-books), Booking-pace, Rate-performance
>   per unit, lead-time + day-of-week (+ a real derived caption), and **"Worth a look" REAL DERIVED observations**
>   (hottest weekday / median booking window / forward revenue — decisions-stay-yours). **HONEST-OMIT** the "vs last year"
>   series/deltas (no prior-year data, #39). **HONEST** the proposals callout → a real "Open your Rates" link (no fake
>   proposal entity/workflow).
> - **Verified:** suite green (`OwnerAnalyticsTest` 4→7 — gross-to-net at a distinctive real 22% rate, honest VAT, export
>   gating, real insights + honest rates link); PHPStan **L5 = 0**; Pint clean; both browser-verified (gross €5,980 −
>   commission €1,076.40 [18%] − costs €250 = net €4,653.60; real derived insights). 🔒 owner-scoped #28, reconciling #17
>   at the real rate, exports #42, nothing fabricated (no fake VAT/proposal/advice) #39. **Invariants #1–61.**
>
> **Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF
> (#57) · Payouts + Payout Method (#58) · Balance + Expenses (#59) · Block Calendar + Documents (#60) · Reports + Insights
> (#61).** Remaining built-but-not-yet-exact-matched: EARNINGS — Tax-center · MANAGE — Rates/Reviews/Agreement/Messages ·
> ACCOUNT — Settings/Security/Team. Everything else below is current; no launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v6) · owner BLOCK CALENDAR + DOCUMENTS exact-match (#60). This block
> SUPERSEDES every block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this
> block → `docs/DECISIONS.md`.**
>
> **The owner-page exact-match track added its 9th + 10th surfaces: the owner Block Calendar (`/owner/calendar`) +
> Documents (`/owner/documents`) pages (`docs/OWNER-CALENDAR-DOCS-AUDIT.md`, DECISIONS 2026-07-28 #60, DIVERGENCE rows
> 141/148).** Both wireframes decoded; both rebuilt EXACTLY on their REAL owner-scoped backends (both were already real).
> - **Block Calendar (GATE 1, `ab4333b`):** the interactive grid — **click a day or press-and-drag a range** (Alpine) →
>   the Selected-range composer (reason **buttons** Personal stay / Maintenance / Seasonal hold + optional note + a
>   **Repeat** section) → Confirm → a REAL block POST (`OwnerBlockService` → certified `AssignmentService`, non-overlap
>   enforced, `$capActions` #20/#42, audited). **Click a blocked day → a REAL release.** 4-state legend + real stat line;
>   booked cells show the guest **NAME** (name-only #28). **Series = REAL:** REPEAT expands into N REAL individual blocks
>   sharing a new nullable `series_id` (never a fake series #39); an over-taken occurrence is skipped + reported; the
>   Scheduled list collapses a series to one row + Remove; a toast + **Undo**. New: `calendar.release-series`,
>   `OwnerBlockService::blockSeries/releaseSeries`, `InventoryBlock.series_id` (migration + fillable).
> - **Documents (GATE 2):** a year-filter pill row + **one frosted card per real month** (period · No. · Net · STMT/BKG/RA
>   rows + PDF) + a **DARK annual tax-summary card per year** with the **REAL net** (Σ `net_source_minor` = the annual
>   PDF's basis, reconciling #17). The backend was already real (on-demand PDFs, `CAP_EXPORTS`-gated, owner-scoped #28);
>   the fabricated multi-year history + "€98,710" stay refused; the not-in-wireframe ZIP stub is dropped.
> - **Verified:** suite **1081/8394 green** (`OwnerBlockCalendarTest` 10 + `OwnerDocumentsViewTest` 6); PHPStan **L5 = 0**;
>   Pint clean; `migrate` clean; both pages browser-proven (a real drag-select block → release; real statement +
>   annual-tax PDF downloads, %PDF-). 🔒 owner-scoped #28 (guest name-only), controller-routed #20, exports #42,
>   reconciling #17, nothing fabricated / no fake series #39. **Invariants #1–60.**
>
> **Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF
> (#57) · Payouts + Payout Method (#58) · Balance + Expenses (#59) · Block Calendar + Documents (#60).** Remaining
> built-but-not-yet-exact-matched: EARNINGS — Tax-center · MANAGE — Reports/Insights/Rates/Reviews/Agreement/Messages ·
> ACCOUNT — Settings/Security/Team. Everything else below is current; no launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v5) · owner BALANCE + EXPENSES exact-match (#59; ⚠️ Balance is real money).
> This block SUPERSEDES every block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md`
> → this block → `docs/DECISIONS.md`.**
>
> **The owner-page exact-match track added its 7th + 8th surfaces: the owner Balance (`/owner/balance`) + Expenses
> (`/owner/expenses`) pages (`docs/OWNER-BALANCE-EXPENSES-AUDIT.md`, DECISIONS 2026-07-28 #59, DIVERGENCE rows 129/147).**
> A read-only PHASE-1 audit classified EVERY element REAL-NOW / ACCOUNT-GATED / NOT-BUILT (load-bearing — Balance's
> "Withdraw early" is instant real-money movement), founder-approved, then two gated builds.
> - **Balance (GATE 1, `a096a89`) — REAL clearing model:** the KPI band is **Available** (Σ unstamped `owner_ledger` net) /
>   **On hold** (Σ pending payouts held by the REAL tax-hold) / **In transit** (Σ released pending), with the identity
>   **Available + On hold + In transit + Paid == Net position** holding per currency **to the cent** (#17). The
>   Balance-activity ledger keeps its real rows + running balance + a per-row **state chip**; the **"Why is €X held?"** card
>   + **Add tax details** render ONLY on a real tax-hold; the masked **IBAN** is CAP_PAYOUT-only (#28); the auto-payout
>   cadence + **"How money clears"**; a real balance-activity **Export CSV** (CAP_EXPORTS). **⚠️ MONEY-SAFETY:** "Withdraw
>   early" is honestly **disabled** — there is NO owner-initiated transfer rail, so it NEVER fakes a withdrawal / transfer /
>   "in transit" (#39); "In transit" is a real figure (€0 while held). Two prior refusals (Withdraw button, Why-held /
>   How-clears cards) were **stale** and are now built REAL (reconciling now the tax-hold is real, #58).
> - **Expenses (GATE 2) — real, honest:** KPIs **Spent this year** / **Pending review** (count) / **Charged to payouts**; a
>   REAL **Unit** column + unit filter (**vendor REFUSED** — unmodelled, #39); a real **Export CSV** (CAP_EXPORTS); the
>   "Need something fixed?" deferral is now a REAL **relay** into the owner↔operator channel (`owner.messages.start`, writes
>   no expense); a read-only **"Pending review"** callout fills the wireframe's attention slot honestly. **REFUSED (#39):**
>   the **"Awaiting your approval"** KPI + the **Approve/Query** workflow (no owner-approval state machine — deductibility is
>   set by the management agreement). By-category proportional bars retained (Batch B).
> - **Demo (D-BE-5):** `seedOwnerBalanceAccrual` (un-statemented October revenue → a real positive Available **€963.60**) +
>   `seedOwnerExpenses` (a flagged spread across both apartments + all three categories + the deductible tri-state; the demo
>   agreement marks `other` non-deductible so incidental supplies stay off the owner ledger).
> - **Verified:** suite **1077 tests / 8370 assertions green** (`OwnerBalanceViewTest` 6, `OwnerExpensesViewTest` 5);
>   PHPStan **L5 = 0**; Pint clean; `migrate:fresh --seed` clean; both pages browser-verified. 🔒 reconciling to the cent
>   (#17), IBAN masked never full (#28), exports CAP_EXPORTS-gated (#42), nothing fabricated / no money-movement (#39).
>   **Invariants #1–59.**
>
> **Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF
> (#57) · Payouts + Payout Method (#58) · Balance + Expenses (#59).** Remaining built-but-not-yet-exact-matched: EARNINGS —
> Tax-center · MANAGE — Reports/Insights/Rates/Reviews/Agreement/Messages · ACCOUNT — Settings/Security/Team. Everything
> else below is current (tenant Phases 0–7 + polish COMPLETE; email + live Stripe test-mode-proven; Super Admin panel
> complete + audited). No launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v4) · owner PAYOUTS + PAYOUT METHOD exact-match (#58; ⚠️ real money + real
> banking). This block SUPERSEDES every block below it (all kept as historical detail). Fresh-session entry point:
> `docs/RESUME.md` → this block → `docs/DECISIONS.md`.**
>
> **The owner-page wireframe-exact-match track added its 5th + 6th surfaces: the owner Payouts (`/owner/payouts`) + Payout
> Method (`/owner/payout-method`) pages (`docs/OWNER-PAYOUTS-AUDIT.md`, DECISIONS 2026-07-28 #58, DIVERGENCE rows 130/136).**
> A read-only PHASE-1 audit first classified EVERY element REAL-NOW / ACCOUNT-GATED / NOT-BUILT (load-bearing here — a fake
> bank verification or payout is dangerous, not just dishonest), founder-approved with four decisions, then two gated
> builds. **Both pages match their wireframes EXACTLY, real where real, honest where account-gated, nothing faked.**
> - **REAL (wired):** payout **history / KPIs / the reconciling next-payout breakdown (#17) / the lifecycle stepper (real
>   states only) / the remittance PDF**; the **masked payout IBAN** (`OwnerNextPayout`, #28, CAP_PAYOUT-only); the whole
>   **bank-account CRUD** (add/set-default/remove — encrypted IBAN, mod-97-validated, self-only, `$capPayout`); **Stripe
>   Connect onboarding** (`OwnerConnectController`, test-mode; live enablement flips on the `account.updated` webhook — the
>   live-key flip is Subhan's); a NEW real **payout-preferences store** (`owner_payout_preferences` — frequency/day/minimum/
>   pause via `owner.payouts.preferences`, a `$capPayout` controller route #20) shown on both pages; the **payout-history
>   Export CSV**; and the **Tax & remittance details** inline on Payout Method (real `Owner` fields via the ONE
>   `owner.tax.save` path — anti-drift + the real hold-release; feeds the remittance PDF, now printing `legal_name`).
> - **HONEST account-gated (exact design, labelled pending the live banking connection, NEVER faked):** micro-deposit
>   **verification** (no verification column → no fake "Verified"/"Verifying"/"deposits sent"), the live **transfer** rail,
>   **SEPA** tracing, the 48h default-**hold**. The on-hold banner surfaces ONLY the real **tax-hold** (pending payout +
>   no tax details), never a fabricated "verification pending". The projected upcoming schedule stays "not yet available".
> - **Demo (D-PAY-1):** a flagged, mod-97-VALID default payout method (Revolut Business · LT IBAN, encrypted, masked
>   ···1000) — WITHOUT tax details, so the tax-hold "Paused" + "Add tax details" attention demo stays intact.
> - **Verified:** suite **1073 tests / 8324 assertions green** (`OwnerPayoutsViewTest` 2→6 + `OwnerPayoutMethodTest` 7→9);
>   PHPStan **L5 = 0**; Pint clean; `migrate:fresh --seed` clean; both pages browser-verified; the preferences + inline-tax
>   writes proven. 🔒 IBAN masked never full (#28), writes `$capPayout`/`$capActions` controller routes (#20/#42),
>   reconciling to the cent (#17), nothing fabricated (#39). **Invariants #1–58.**
>
> **The owner-page exact-match track has now shipped Overview (#54) · My Units + Add Unit (#55) · Bookings + Booking-Summary
> PDF (#56) · Statement + Statement PDF (#57) · Payouts + Payout Method (#58).** Remaining built-but-not-yet-exact-matched:
> EARNINGS — Balance/Expenses/Tax-center · MANAGE — Reports/Insights/Rates/Reviews/Agreement/Messages · ACCOUNT —
> Settings/Security/Team. Everything else below is current (tenant Phases 0–7 + polish COMPLETE; email + live Stripe
> test-mode-proven; the Super Admin panel complete + audited). No launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v3) · owner STATEMENT exact-match (page + PDF, #57). This block SUPERSEDES every
> block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block →
> `docs/DECISIONS.md`.**
>
> **The owner-page wireframe-exact-match track added its 4th surface: the owner Statement page + the downloadable
> Statement PDF (`docs/OWNER-STATEMENT-AUDIT.md`, `DECISIONS.md` 2026-07-28, DIVERGENCE row 132 → RESTRUCTURED-exact).**
> Both were rebuilt to their two decoded wireframes (`Owner Statement.html` = the app page; `Owner Statement PDF.html` =
> the print document) EXACTLY, on REAL owner-scoped, reconciling data. **Two page refusals were STALE and are now REAL**
> (their backends shipped in Overview #54 on a sibling surface): the **occupancy card + mini bar chart** (shared
> `OwnerOccupancyTrend`) and the **masked payout IBAN** (shared `OwnerNextPayout`, CAP_PAYOUT-only). Also: the month
> "jump to month" switcher, the plain-glass hero, the gross "N nights · M units" sublabel, a standalone Download-statement,
> a **Statement-history Export CSV + a per-year Export PDF + a per-row PDF** (`owner.statements.history.*`, owner-scoped +
> CAP_EXPORTS, `?year=` clips), and by-unit unit-name links. The **PDF** became the wireframe's letterhead statement —
> real `billing_company`/`country` letterhead + OS-YYYY-MM ref + a net-payable dark headline + real **Occupancy/Nights/ADR**
> stats + a 3-column breakdown + a **Performance-by-unit** table + a payout-note callout (IBAN CAP_PAYOUT-gated) + an
> identity/email footer. **Anti-drift:** three shared helpers extracted — `OwnerNextPayout` + `OwnerOccupancyTrend` (from
> `OwnerDashboard` #54, which now delegates) + `OwnerStatementBreakdown` (page + PDF share ONE per-unit split). **Honesty
> held:** breakdown Σ==net + by-unit Σ==net (page)/gross (PDF) to the cent (#17); NO guest data on either surface (#28);
> exports are CAP_EXPORTS controller routes, no `/livewire/update` (#20); the IBAN CAP_PAYOUT-only (#30); no "payment & ops
> fees" line and no estimated payout date invented (#39). A **flagged demo statement history** (Apr–Aug 2026, real stays →
> attribution → statements finalized in period order → paid) makes the page/Overview demo on a real shape; **September's
> statement is unchanged (net €885.60)**. **Verified:** suite **1066 tests green** (`OwnerStatementViewTest` 5→9 +
> `OwnerStatementPdfTest` 5→7); PHPStan **L5 = 0**; Pint clean; `migrate:fresh --seed` clean; browser-verified page + rendered
> document vs the two wireframes; adversarial review on the full diff. **Invariants #1–57.**
>
> **The rest of the state below is current** — everything buildable is COMPLETE (tenant Phases 0–7 + polish; email + live
> Stripe test-mode-proven; the Super Admin panel complete + audited). The owner-page exact-match track has now shipped
> **Overview (#54) · My Units + Add Unit (#55) · Bookings + Booking-Summary PDF (#56) · Statement + Statement PDF (#57)**;
> the remaining owner pages (EARNINGS — Payouts/Balance/Expenses/Tax-center · MANAGE — Reports/Insights/Rates/Reviews/
> Agreement/Messages · ACCOUNT — Settings/Security/Team) are built + at-parity, the next candidates if the founder continues.
>
> ---
>
> ⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v2, TOTAL-RECALL). This block SUPERSEDES every block below it (all kept as
> historical detail). Fresh-session entry point: `docs/RESUME.md` → this block → `docs/DECISIONS.md`.**
>
> **VERIFIED THIS PASS (docs-only consolidation; no feature code changed):** `git` HEAD **`f9da60d`** on `main`
> (== `origin/main`, clean) · suite **1060 tests / 8207 assertions green** (`php artisan test`, exit 0) · PHPStan
> **L5 = 0** · Pint **clean** · `migrate:fresh --seed` **clean** · **Invariants #1–56** (the numbered list below is
> reconciled to #56).
>
> ---
>
> ### WHAT VYROBEL IS
> A multi-tenant, AI-native **hotel / apartment-management SaaS** for operators running a MIX of hotels, aparthotels,
> serviced apartments, villas and multi-unit rentals. Three layers: (1) the **TENANT product** — an operator back office
> (`/app`), an external-owner portal (`/owner`), a guest portal (`/guest`), a public storefront (`/book/{slug}`) and
> public self-signup (`/signup`) that provisions a whole new trial tenant; (2) the **PLATFORM** — a Super Admin panel
> (`/admin`) to run the SaaS; (3) the **integrations tail** (email, payments, channel manager, KYC).
>
> **Stack (locked; see `CLAUDE.md`):** Laravel **13** / PHP 8.4 · MySQL 8 InnoDB · Livewire **4** + Blade + Tailwind **4**
> (Vite) · Redis + **Horizon** · **ULID** PKs on tenant tables · spatie **teams-mode** RBAC (team = org) · shared-DB
> multi-tenancy via `organization_id` + `BelongsToOrganization`/`OrganizationScope` (`runAsSuperAdmin` = the ONE
> sanctioned bypass) · **integer minor-unit money** (`*_minor` + `Money`) · Pest 4 / PHPStan L5 / Pint. Browser
> verification = the **Playwright MCP** driving `php artisan serve` (real, but NOT a re-runnable suite).
>
> ### COMPLETE BUILD STATUS
> **Everything buildable is DONE.** No launch-blocking build remains.
> - **Phases 0–7 — the entire tenant product — COMPLETE + certified** (per-gate detail in `DECISIONS.md`): P0 fix/harden ·
>   P1 operator app · P2 owner portal · P3 notifications + all transactional emails · P4a/b/c the guest portal · P5 reviews ·
>   P6a multi-property (`PropertyContext`, the complete `->first()` refactor) + P6b analytics (`PerformanceMetrics`) ·
>   P7a tenant provisioning/self-signup · P7b subscription billing (free-trial model) · P7c owner sub-tenancy/team ·
>   P7d team schedule/rostering.
> - **Phase 8 — account-gated integrations — the two OBTAINABLE ones are lit up:** **production email** (Postmark;
>   `MailSafetyGuard`; `docs/EMAIL-PRODUCTION.md`) and **live Stripe across all three seams** (subscription billing ·
>   guest card charge · owner payout/Connect) — **test-mode-proven** (webhook-driven, signature-verified, multi-layer
>   idempotent, no raw PAN/SAQ-A, payout double-transfer-guarded; `StripeSafetyGuard` refuses a live key outside
>   `production`). The **live-key flip + DNS/token are Subhan's** documented cutovers (`docs/STRIPE-PRODUCTION.md`).
> - **Cross-cutting hardening DONE:** the full **design-fidelity pass** (4 gates, `docs/design/DIVERGENCE-REGISTER.md` =
>   the per-page source of truth) · the **polish sweep** (`docs/POLISH-SWEEP.md`) · the **GAP-SWEEP batches A/B/C**
>   (`docs/GAP-SWEEP.md` — nav filter, owner PDFs wired, REAL analytics depth #47, operator money-write **idempotency**
>   #48) · the **notification bell→panel** rebuild · the **read-only system audit** (`docs/AUDIT-REPORT.md`).
> - **THE SUPER ADMIN PANEL (`/admin`) — COMPLETE + AUDITED (invariants #49–53).** Built across Gate 0 (isolated realm +
>   hardened auth + **mandatory 2FA**) + Build 1 + Build 2, then a **Playwright audit (0 critical / 0 P1, production-ready)**,
>   a **Profile/Account page**, and a **completeness review** (`docs/SUPERADMIN-AUDIT.md` + `docs/SUPERADMIN-COMPLETENESS.md`).
>   Surfaces: Dashboard (real cross-org metrics) · Customers (list/detail/**suspend**/reactivate/assign-plan) · Plans
>   (DB CRUD + feature-gating + assign) · Security & Activity Log (login-signal capture + audit slice) · Integrations
>   (encrypted platform channel credentials + test-stub) · Account. Admins are created via the `admin:create` CLI.
>   **The panel is LAUNCH-READY for a free-trial / early-access product exactly as-is.**
> - **THE OWNER-PAGE EXACT-MATCH TRACK (ongoing hands-on polish — NOT launch-blocking).** Per-page wireframe-exact
>   rebuilds of the owner portal on REAL backends, "prior deferrals in scope". **DONE + pushed:** owner **Overview**
>   (#54, `docs/OWNER-OVERVIEW-AUDIT.md`) · **My Units + Add Unit** (#55, `docs/OWNER-UNITS-AUDIT.md`) · **Bookings page +
>   Booking-Summary PDF** (#56, `docs/OWNER-BOOKINGS-AUDIT.md`, commits `b571555`+`f9da60d`). **NOT yet exact-matched**
>   (they are built + AT-PARITY, just not through the wireframe-exact rebuild): EARNINGS — Statements/Payouts/Balance/
>   Expenses/Tax-center · MANAGE — Reports/Insights/Rates/Reviews/Agreement/Messages · ACCOUNT — Settings(Profile)/
>   Security/Team. These are the remaining candidates if the founder continues this track.
>
> ### HOW TO DECODE AN OWNER WIREFRAME (the method — reused every exact-match gate)
> The prototypes in `docs/design/raw/*.html` are **`dc-runtime` bundles**, NOT plain HTML — the visible markup is packed
> in a script. To read one: **render it in a browser (Playwright over a throwaway `php -S` static server — `file:` is
> blocked)** and screenshot, OR **`JSON.parse` the `__bundler`/template `<script>` payload → the `<x-dc>` body is the
> real markup + design tokens**. Then match colour/gradient/font/hover/shadow/radius/spacing/animation exactly. (Memory:
> `owner-wireframe-decode`.) Reuse the **shared owner chrome** — `<x-owner.shell>` (frosted sidebar/topbar, breadcrumb,
> account menu) + the **`--vy-*` design tokens** in `resources/views/components/layouts/owner.blade.php`'s `<style>`
> (accent `#C2410C`, `--vy-grad`, glass surfaces, `.vy-card/.vy-kpi/.vy-table/.vy-status`, `.vy-kpi-grid--4`) — and the
> shared bar chart `<x-charts.bars>`. **Never** invent one-off tokens; **page-scope** any page-specific CSS (a class
> name leaking into the shared owner layout can trip another page's `assertDontSee` isolation test).
>
> ### HONEST DEFERRALS (do NOT build ad hoc — see the register below + `docs/SUPERADMIN-COMPLETENESS.md` / `GAP-ANALYSIS.md`)
> - **Platform / paid-launch:** Stripe **billing sync** + revenue/invoices/refunds (needs live Stripe) · **suspension
>   ENFORCEMENT** (Suspend sets a flag today; a suspended customer can still log in) · **impersonation** (support "view as
>   customer") · **per-feature usage limits** (plan `feature_limits` beyond the boolean gate) · **customer self-connect**
>   of their own Stripe/channels · the **Horizon-gate RE-POINT** to the `admin` guard (the founder can't reach Horizon in
>   prod today — the gate is bound to an unassignable role) · multi-admin / GDPR data-request / manual customer-create +
>   trial-adjust (day-two ops).
> - **Tenant-product tails:** **real channel sync** (Channex live) · WhatsApp/OTA inbox channels · SMS · guest ID/**KYC** ·
>   owner payout EXECUTION (Stripe Connect + KYC) · the **My-Units action-center** snooze/dismiss store + richer day-detail
>   (function as real links today) · the **operator approve→create-Unit** flow (Add-Unit lands a real `UnitRequest`, NOT a
>   `Unit` — the operator-side approval that promotes a request to a Unit is deferred) · the **remaining un-exact-matched
>   owner pages** (above) · assorted **P3 tails** (editable email-template store, etc.). **Each is DEFERRED HONESTLY —
>   build the experience, never fake the charge/send/verify.**
>
> ### WHAT'S LEFT / PATH TO LAUNCH
> 1. **Choose the launch model.** *Free-trial / early-access* is **launch-ready NOW** (panel + product as-is). *Paid* needs
>    only the already-scoped **Stripe go-live** (Subhan's live-key flip) + **suspension enforcement**.
> 2. **Deploy.** Runs locally on XAMPP; needs production hosting / domain / HTTPS / MySQL 8 / Redis-Horizon.
> 3. **Subhan's cutovers.** Email DNS + Postmark token (`docs/EMAIL-PRODUCTION.md`) · the Stripe live-key flip
>    (`docs/STRIPE-PRODUCTION.md`).
> 4. **(Ongoing, optional) owner-page exact-match polish** on the remaining pages — quality work, NOT launch-blocking.
>
> ### LOGIN CREDENTIALS (all password `password`; source of truth = `database/seeders/DemoSeeder.php` + `PlatformAdminSeeder.php`)
> | Realm | URL | Email | Role |
> |---|---|---|---|
> | Operator — org admin | `/app` | `demo-admin@vyrobel-demo.test` | Property Owner (org admin, `['*']`) |
> | Operator — manager | `/app` | `demo-staff@vyrobel-demo.test` | Hotel Manager |
> | Owner portal | `/owner` | `demo-owner@vyrobel-demo.test` | External Owner (primary owner) |
> | Guest portal | `/guest/login` | `demo.traveller@vyrobel-demo.test` | booking-tied guest |
> | **Platform admin** | `/admin` | `admin@vyrobel-demo.test` | Super Admin (**forced 2FA enrol on first login**) |
>
> Public storefront (no login): `/book/seaside-demo`. Self-signup (provisions a new trial tenant): `/signup`. ⚠️ `demo-owner`
> is REFUSED from `/app` and bounced to `/owner` — invariant #26 WORKING, not a bug. (Also seeded: owner sub-users
> `demo-owner-accountant@` / `demo-owner-viewer@`; operator `demo-accountant@` / `demo-housekeeper@`.)
>
> ---
>
> ⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 · owner Bookings EXACT-MATCH BUILD complete (page + PDF, #56) on top of the
> total-recall consolidation + owner My Units/Add Unit exact-match (#55). This block SUPERSEDES every block below it
> (all kept as historical detail). For the fresh-session read-order + one-paragraph summary + creds + next action, start
> at `docs/RESUME.md`.** Re-verified against the repo THIS pass: **suite green `1060` tests / `8207` assertions
> (`php artisan test`, exit 0)** · PHPStan **L5 = 0** · Pint clean · `migrate:fresh --seed` clean · **Invariants #1–56**.
> **Owner Bookings (`docs/OWNER-BOOKINGS-AUDIT.md`, invariant #56):** the LIVE page `/owner/bookings` + the Booking-Summary
> PDF are now WIREFRAME-EXACT on REAL owner-scoped data, built as two gated deliverables. The two items once refused as
> fabrication are REAL (never faked): the page's **"Bookings over time" chart** (`reservations.status` stacks + net via
> `OwnerLedgerAttribution` + a real goal line) and **"Monthly goal" ring** (real net ÷ an owner-SET `owner_monthly_target`;
> honest "no target" when unset) — plus a Status filter, date-range, sortable columns, pagination, a name-only CSV Export,
> and the `$capActions` target-write (controller route, currency derived server-side, #20/#30) — page + export share the
> ONE `OwnerBookingsReader`. The **PDF** gained REAL owner-scoped occupancy (`PerformanceMetrics::forOwnerUnits`,
> physical-unit-keyed), the guest NAME-only column (`GuestName`, #28), the real channel commission (`ota_cost`), and a real
> letterhead VAT/country — Σ(rows)===gross unchanged; the OTA **brand** split stays refused (unmodelled). Two adversarial-
> review passes (GATE 1 fixed 4 real bugs — subMonth overflow, sticky sort-dir, chart-legend-vs-truncation, net-sort; GATE
> 2 clean). HEAD on `main`; the only non-doc work since the last consolidation was these two exact-match gates.
>
> **WHAT VYROBEL IS.** A multi-tenant, AI-native **hotel / apartment-management SaaS**, in three layers: the TENANT
> PRODUCT (operator back office `/app` · owner portal `/owner` · guest portal `/guest` · public storefront + booking
> engine `/book/{slug}`), the SaaS LAYER (public self-signup `/signup` → atomic tenant provisioning · subscription
> billing on a free-trial model · owner sub-tenancy · staff rostering), and the **SUPER ADMIN platform panel `/admin`**
> (the founder's console — a separate hardened realm). **Stack (verified against `composer.json`):** **Laravel 13**
> (`laravel/framework ^13.8`) / PHP 8.4 · MySQL 8 / InnoDB · Livewire 4 + Blade + Tailwind 4 · Redis + Horizon · ULID
> PKs · spatie **teams-mode** RBAC · shared-DB multi-tenancy via `organization_id` + the `OrganizationScope` global
> scope (`runAsSuperAdmin` = the ONE sanctioned bypass). Integer minor-unit money (`*_minor` + `Money`).
>
> **BUILD STATUS — EVERYTHING BUILDABLE IS COMPLETE; the active hands-on track is owner-page wireframe-exact-match
> polish (not launch-blocking).**
> - **Tenant product (Phases 0–7): COMPLETE** — harden · operator app · owner portal · notifications + transactional
>   emails · guest auth + portal · reviews · multi-property (`PropertyContext`) · REAL analytics (`PerformanceMetrics`,
>   RevPAR = ADR × occupancy) · atomic tenant provisioning · subscription billing (free-trial) · owner team · schedule.
> - **Phase 8 (account-gated integrations) — the two OBTAINABLE ones LIT UP (test-mode / code-complete):** production
>   email (Postmark + `MailSafetyGuard`; `docs/EMAIL-PRODUCTION.md`) · LIVE STRIPE across all three seams (subscription ·
>   guest card · owner payout/Connect — `StripeSafetyGuard`, webhook-source-of-truth, multi-layer idempotency, payout
>   double-transfer-guarded; `docs/STRIPE-PRODUCTION.md`). The live cutovers (email DNS + token; Stripe live-key flip)
>   are the founder's remaining NON-CODE steps. Still account-gated: Channex live sync · WhatsApp/OTA inbox · SMS · KYC.
> - **Design-fidelity pass (76 surfaces) + owner account rebuild + notification-panel rebuild + Batches A/B/C + the
>   comprehensive polish sweep (`docs/POLISH-SWEEP.md`, Batches 1–6 + P3 tails, FULLY CLEARED) + add-charge server
>   idempotency: ALL COMPLETE.**
> - **⭐ SUPER ADMIN PANEL: COMPLETE + AUDITED + PRODUCTION-READY.** Gate 0 (hardened isolated `/admin` realm —
>   separate `platform_admins` table + `admin` guard, mandatory 2FA, the audited `PlatformQuery::read()/mutate()`
>   wrapping `runAsSuperAdmin`, noindex, seeded-not-signup; **#49**) → Build 1 (Dashboard · Customers
>   [list/detail/suspend/reactivate/assign-plan] · Plans [DB-backed CRUD + feature-gating; **#50**]) → Build 2
>   (Security & Activity Log [login capture, 90-day-retention privacy; **#51**] · Integrations [encrypted channel
>   credentials + management + test-stub; **#52**]) → deep Playwright audit (`docs/SUPERADMIN-AUDIT.md` — 0 critical /
>   0 P1) → Profile & Account page (profile · email re-verify · password · 2FA recovery; **#53**) → completeness review
>   (`docs/SUPERADMIN-COMPLETENESS.md` — launch-ready + the roadmap).
> - **⭐ OWNER-PAGE WIREFRAME-EXACT-MATCH WORK (the recent track — every live owner page matched to its decoded
>   design-component wireframe: EXACT design + REAL owner-scoped backends, one gated page at a time):**
>   - **Owner OVERVIEW** (`/owner`) — exact match + real backends via the shared owner chrome + shared `OwnerAttention` /
>     `OwnerUpcomingStays` / `OwnerOccupancy` / `OwnerLedgerAttribution`. **COMPLETE + pushed** (`d80abdf` design /
>     `f267e2c` backends; **INVARIANT #54**).
>   - **Owner MY UNITS** (`/owner/units`) — exact design + real card/calendar actions (edit-pricing via `OwnerRateService`
>     per-room-type [honest-disabled for shared inventory] · message-relay to the operator [name-only #28] · block-dates
>     via `OwnerBlockService` · month-occupancy CSV export [counts-only #28]; action-center snooze/day-detail honestly
>     deferred) + the calendar heat-grid recolored to the wireframe terracotta scale (`4a3d538`). **COMPLETE + pushed**
>     (`0be1363` / `33e2475`; **INVARIANT #55**).
>   - **Owner ADD UNIT** (`/owner/add-unit`) — the exact **4-step wizard** (basics · space & capacity · photos · rate &
>     terms) + sticky Live preview + Done, on a **rich, real, pending `UnitRequest`** (schema additive; amenities via the
>     shared `amenitiables` pivot; photos via the shared `media` collection, ULID filenames); the write is ONE gated
>     controller POST (never `/livewire/update`, #20); commission is the owner's REAL agreement rate (honest line if
>     none). Proven end-to-end in the browser (a real "Casa Verde Retreat" request filed + surfaced read-only on the
>     operator `unit-requests` screen; a demo artifact `migrate:fresh --seed` clears). **COMPLETE + pushed** (`2193840`;
>     **INVARIANT #55**). Decisions **D1** (action-center = real items + honest-empty; no owner approve/decline entity
>     exists) · **D2** (message-guest = relay to the operator team, name-only; no owner→guest channel, #28) · **D3**
>     (add-unit = a rich real `UnitRequest`, the live `Unit` stays operator-made) — all recorded in `DECISIONS.md`.
>   - **⬅ NEXT (active): Owner BOOKINGS page + Owner BOOKING-SUMMARY PDF.** **PHASE-1 read-only audit is COMPLETE
>     (`docs/OWNER-BOOKINGS-AUDIT.md`, 2026-07-28) — awaiting GATE-1 approval.** Both live surfaces ALREADY exist on real
>     owner-scoped data (the page `App\Livewire\Owner\OwnerBookings`; the PDF **`OwnerBookingSummaryDocument`** via
>     `owner.statements.bookings` + `resources/views/pdfs/owner-booking-summary.blade.php`) — the work is an EXACT-MATCH
>     upgrade, "prior deferrals in scope". The audit's decisive finding: every wireframe figure has an honest owner-scoped
>     source, so the two items once refused as fabrication become REAL — the **"Bookings over time" chart** (real
>     per-check-in-month status counts + net via `OwnerLedgerAttribution` + a **goal line backed by a NEW owner-set
>     `owner_monthly_target`**, owner-supplied not invented) and the **PDF occupancy / per-channel commission /
>     guest-name-only column** (the old PDF-docblock refusals are STALE — `OwnerOccupancy`/`PerformanceMetrics` +
>     `owner_ledger.ota_cost` now back them; guest name-only is an established owner-surface precedent, #28). The ONLY
>     standing refusal is the OTA **brand** split (Booking.com vs Airbnb — unmodelled; rendered as the real `source`
>     enum). GATE 1 = the page (breadcrumb · GUESTS stat · chart + goal ring · Status/date-range filters · sortable
>     table · pagination · Export CSV); GATE 2 = the PDF (letterhead billing_company/VAT/country · BS-YYYY-MM ref ·
>     4-cell KPI incl. occupancy · by-unit occupancy · by-channel commission · guest-name-only · callout footer). See §4
>     of the audit for the full build plan.
>
> **THE DECODE METHOD (owner wireframes).** The attached `docs/design/raw/*.html` are **`dc-runtime` `__bundler`
> bundles**, not plain HTML — the real markup is `JSON.parse(<script type="__bundler/template">)` → the `<x-dc>` body
> (inline-style design tokens + `{{ }}` placeholders); a `<script type="text/x-dc">` DCLogic model holds the sample
> data. **Decode before matching.** Reuse/extend the shared **warm-glass owner chrome** (`<x-owner.shell>`, frosted
> sidebar/topbar) + the `--vy-*` tokens (accent `linear-gradient(140deg,#E8703A,#C2410C)`; peach→grey diagonal page bg;
> Inter + Sora; vyFadeUp/vyDrift/vyShimmer) — never one-offs. A PDF wireframe (white letterhead, no app shell) is a
> DIFFERENT deliverable — match the print document's own design.
>
> **QUALITY STATE (re-verified against the repo THIS pass):** suite **1050 tests / 8149 assertions green** (exit 0);
> PHPStan **L5 = 0**; Pint **clean**; `migrate:fresh --seed` clean. **Invariants #1–55** — the canonical numbered list
> below is now reconciled to #55 (it had lagged: #54 owner Overview, #55 owner My Units + Add Unit added this pass).
>
> **HONEST DEFERRALS (all correctly deferred — NONE block a launch):** Stripe billing sync (the paid-launch
> dependency) · impersonation (high-risk support tool) · suspension access-ENFORCEMENT (today a status flag; needed to
> actually cut off a non-payer) · per-feature-limit enforcement · the real channel sync + customer-self-connect (need
> the channel accounts) · **the Horizon queue gate is unreachable in production** (bound to the unassignable global
> `Super Admin` role — re-point it to the `admin` guard; small + high-value) · the My Units action-center snooze/dismiss
> persistence + a richer calendar day-detail · the operator review/approve→create-live-`Unit` flow · the remaining
> owner pages not yet exact-matched (Bookings/PDF audited-not-built; others further out) · the P3 polish tails.
>
> **WHAT'S LEFT (path to launch).** The product + the Super Admin panel are launch-ready — a **free-trial /
> early-access launch needs nothing more**; a **paid launch** needs only the already-scoped **Stripe go-live +
> suspension enforcement**. Then **DEPLOYMENT** (production hosting / domain / HTTPS / MySQL / Redis-Horizon) + the
> founder's email + Stripe cutovers. The owner-page exact-match work (Bookings + PDF next) is ongoing polish, not
> launch-blocking. **Roadmap:** Phase 0 launch → 1 first-paying (billing sync · real revenue/invoices/refunds ·
> suspension enforcement · re-point the Horizon gate · trial-adjust/cancel/manual-create) → 2 scale (multi-admin +
> roles · alerting · GDPR export/erase · impersonation · usage analytics) → 3 polish.
>
> **RESUME CREDENTIALS** (all password `password`; `php artisan migrate:fresh --seed` recreates them): operator
> **`demo-admin@vyrobel-demo.test`** (Property Owner / org admin) or `demo-staff@vyrobel-demo.test` (Hotel Manager) at
> `/app`; owner **`demo-owner@vyrobel-demo.test`** at `/owner`; guest **`demo.traveller@vyrobel-demo.test`** at
> `/guest/login`; **PLATFORM ADMIN `admin@vyrobel-demo.test`** at `/admin` (forced 2FA enrolment on first login).
> Public storefront (no login) `/book/seaside-demo`; self-signup `/signup`.
>
> **THE NEXT ACTION.** No launch-blocking build remains. The active hands-on task is the **owner-page exact-match
> track: Owner Bookings page + Booking-Summary PDF** — the PHASE-1 audit is done (`docs/OWNER-BOOKINGS-AUDIT.md`), so
> the next step is **GATE 1 (the page) then GATE 2 (the PDF)** per §4 of that audit (both on real data; guest name-only
> #28; net reconciling #17; owner writes on controller routes #20; the PDF stays CAP_EXPORTS-gated). In parallel, the
> founder's strategic decisions stand: **choose the launch model** (free-trial now vs paid after Stripe go-live +
> suspension enforcement), then **deploy**. Read `docs/RESUME.md` first.
>
> ---
>
> ⭐⭐⭐ **CURRENT STATE — 2026-07-27 · OWNER OVERVIEW exact-match + real backends (INVARIANT #54).** The owner
> Overview (`/owner`) was rebuilt to the decoded `docs/design/raw/Owner Dashboard.html` wireframe EXACTLY **and**
> wired so every section is operational on REAL owner-scoped data (no stubs, no fabrication). Two gates: **GATE 1**
> (design, `d80abdf`) — peach-diagonal background + ambient orbs/streaks, frosted **terracotta-active** sidebar,
> greeting header + account-menu dropdown, 42px bell, attention bar, glass quick actions, 52px hero + terracotta
> delta, ↗ metric link-cards, segmented earnings toggle, halo dark rail (shared owner chrome extended, not
> one-offs; the 5 extra real nav items KEPT — D2). **GATE 2** (real backends) — occupancy KPI + per-unit bars +
> delta + sparkline (`OwnerOccupancy`), the attention feed (new shared `OwnerAttention`), the next-30-days upcoming
> stays (new shared `OwnerUpcomingStays` — **guest NAME only #28** + owner NET via `OwnerLedgerAttribution` +
> client-side Alpine Source/Status filters — a Livewire round-trip would bypass the owner scope), the masked IBAN +
> tax-hold paused-state, the time-aware greeting, and the 4 quick actions (real routes, capability-gated). Honesty
> held (#39): no earnings goal, no estimated payout date, no invented occupancy (the demo shows a real LOW
> occupancy, never a fake 78%); honest empty states. Mutation-/render-proven (`OwnerUpcomingStaysTest` +
> `OwnerDashboardViewTest` — 30-day clip · name-only · net-reconcile · honesty · `assertNoRawBlade`); browser-
> verified (real data + working filter dropdown + paused rail + account dropdown). Suite **1042**; PHPStan L5 = 0;
> Pint clean; `migrate:fresh --seed` clean. Docs: `docs/OWNER-OVERVIEW-AUDIT.md`, DECISIONS.md 2026-07-27,
> `design/DIVERGENCE-REGISTER.md` (owner Overview → RESTRUCTURED-exact + operational). **Invariants #1–54.**
>
> ---
>
> ⭐⭐ **CURRENT STATE — 2026-07-27 TOTAL-RECALL CONSOLIDATION. This block SUPERSEDES every block below it (all kept
> as historical detail). For the fresh-session read-order + one-paragraph summary + creds + next action, start at
> `docs/RESUME.md`.** Documentation-only consolidation — **no feature code changed** since the account page; HEAD on
> `main`, tree clean after the docs commit.
>
> **WHAT VYROBEL IS.** A multi-tenant, AI-native **hotel / apartment-management SaaS**, in three layers: the TENANT
> PRODUCT (operator back office `/app` · owner portal `/owner` · guest portal `/guest` · public storefront + booking
> engine `/book/{slug}`), the SaaS LAYER (public self-signup `/signup` → atomic tenant provisioning · subscription
> billing on a free-trial model · owner sub-tenancy · staff rostering), and the **SUPER ADMIN platform panel `/admin`**
> (the founder's console — a separate hardened realm). **Stack:** Laravel 13 / PHP 8.4 · MySQL 8 / InnoDB ·
> Livewire 4 + Blade + Tailwind 4 · Redis + Horizon · ULID PKs · spatie **teams-mode** RBAC · shared-DB multi-tenancy
> via `organization_id` + the `OrganizationScope` global scope (`runAsSuperAdmin` = the ONE sanctioned bypass).
>
> **BUILD STATUS — EVERYTHING BUILDABLE IS COMPLETE.**
> - **Tenant product (Phases 0–7): COMPLETE** — harden · operator app · owner portal · notifications + transactional
>   emails · guest auth + portal · reviews · multi-property (`PropertyContext`) · REAL analytics (`PerformanceMetrics`,
>   RevPAR = ADR × occupancy) · atomic tenant provisioning · subscription billing (free-trial) · owner team · team schedule.
> - **Phase 8 account-gated integrations — the two OBTAINABLE ones are LIT UP (test-mode / code-complete):**
>   PRODUCTION EMAIL (Postmark + `MailSafetyGuard`; `docs/EMAIL-PRODUCTION.md`) · LIVE STRIPE across all three seams
>   (subscription · guest card · owner payout/Connect — `StripeSafetyGuard`, webhook-source-of-truth, multi-layer
>   idempotency, payout double-transfer-guarded; `docs/STRIPE-PRODUCTION.md`). The live cutovers (email DNS + token;
>   Stripe live-key flip) are the founder's remaining NON-CODE steps. Still account-gated: Channex live sync ·
>   WhatsApp/OTA inbox · SMS · guest ID/KYC.
> - **Design-fidelity pass (76 surfaces, `docs/design/DIVERGENCE-REGISTER.md`): COMPLETE (all 4 gates).** Owner account
>   rebuild + notification-panel rebuild: COMPLETE. Batch A (cheap wins) + B (analytics depth) + C (operator
>   operations): COMPLETE. The comprehensive polish sweep (`docs/POLISH-SWEEP.md`, all 4 dimensions, Batches 1–6 + P3
>   tails): FULLY CLEARED. Add-charge server idempotency (the last money-path correctness item): CLOSED.
> - **⭐ THE SUPER ADMIN PANEL: COMPLETE + AUDITED + PRODUCTION-READY.** Gate 0 (hardened isolated `/admin` realm —
>   separate `platform_admins` table + `admin` guard, mandatory 2FA, the audited `PlatformQuery::read()/mutate()`
>   wrapping `runAsSuperAdmin`, noindex + robots, seeded-not-signup) → Build 1 (Dashboard · Customers
>   [list/detail/suspend/reactivate/assign-plan] · Plans [DB-backed CRUD + feature-gating, additive]) → Build 2
>   (Security & Activity Log [login capture, 90-day-retention privacy, audit slice] · Integrations [encrypted channel
>   credentials + management + test-stub]) → deep Playwright audit (`docs/SUPERADMIN-AUDIT.md` — 0 critical / 0 P1;
>   UX/polish/honesty fixes) → Profile & Account page (profile · email re-verify · password · 2FA recovery) →
>   **completeness review (`docs/SUPERADMIN-COMPLETENESS.md`)**.
>
> **QUALITY STATE (verified against the repo):** suite **1040 tests green** — the admin work was strictly ADDITIVE, the
> tenant suite never regressed (981 → 997 → 1013 → 1029 → 1031 → 1040); PHPStan **L5 = 0**; Pint clean;
> `migrate:fresh --seed` clean. **Invariants #1–53** — the full canonical numbered list below was extended in THIS pass
> from #41 to #53 (it had lagged the prose).
>
> **HONEST DEFERRALS (all correctly deferred — real priority from `docs/SUPERADMIN-COMPLETENESS.md`; NONE block a
> launch):** Stripe billing sync (the paid-launch dependency) · suspension access-enforcement (a status flag today —
> needed to actually cut off a non-payer) · impersonation (high-risk; a support tool) · per-feature-limit enforcement ·
> real channel sync + customer-self-connect (need the channel accounts) · **the Horizon queue is unreachable in
> production** (its gate is bound to the unassignable global `Super Admin` role — re-point it to the `admin` guard;
> small + high-value) · the residual global-`Super Admin` assignability · the remaining P3 polish tails.
>
> **WHAT'S LEFT (path to launch, from the completeness review):** the panel is **operationally launch-ready** — a
> **free-trial / early-access launch needs nothing more**; a **paid launch** needs only the already-scoped **Stripe
> go-live + suspension enforcement**. Then **DEPLOYMENT** (production hosting / domain / HTTPS / MySQL / Redis-Horizon)
> + the founder's email + Stripe cutovers. **Roadmap:** Phase 0 launch → 1 first-paying (billing sync · real
> revenue/invoices/refunds · suspension enforcement · re-point the Horizon gate · trial-adjust/cancel/manual-create) →
> 2 scale (multi-admin + roles · admin alerting · GDPR export/erase · impersonation · usage analytics) → 3 polish.
>
> **RESUME CREDENTIALS** (all password `password`; `php artisan migrate:fresh --seed` recreates them): operator
> **`demo-admin@vyrobel-demo.test`** (Property Owner / org admin) or `demo-staff@vyrobel-demo.test` (Hotel Manager) at
> `/app`; owner **`demo-owner@vyrobel-demo.test`** at `/owner`; guest **`demo.traveller@vyrobel-demo.test`** at
> `/guest/login`; **PLATFORM ADMIN `admin@vyrobel-demo.test`** at `/admin` (forced 2FA enrolment on first login).
> Public storefront (no login) `/book/seaside-demo`; self-signup `/signup`.
>
> **THE NEXT ACTION for a fresh session:** there is NO outstanding build. The next decision is the founder's — **choose
> the launch model** (free-trial now vs paid after the Stripe go-live + suspension enforcement), then **deploy**. If
> building, take the next item from the `docs/SUPERADMIN-COMPLETENESS.md` roadmap (Phase 1). Read `docs/RESUME.md` first.
>
> ---

> ⭐ **CURRENT STATE — 2026-07-23 total-recall save (HEAD `cace277`, `main`, local == origin, tree clean).
> This block supersedes any earlier "next is Phase 7c" / "Rates next" prose below (kept as historical detail).**
>
> **Latest (2026-07-26, see `DECISIONS.md` for detail):** post-audit gap-sweep build batches A/B/C (owner PDFs · REAL
> per-unit + by-unit + Insights-cadence analytics · operator ops: housekeeping-assign / move / add-charge / on-desk
> payment money-gate + tax editing) — **Invariants #1–48** — then the **NOTIFICATION EXPERIENCE rebuild**: the top-bar
> **bell opens a PANEL (Alpine popover, no new dependency) in BOTH shells** instead of navigating (only "Open all
> notifications →" reaches the full page), built in the feed's exact design language (NO separate panel wireframe — the
> proto bell is static, recorded honestly) via the ONE shared `NotificationFeed::recentForUser()` + `<x-notifications.bell>`
> + `<x-notifications.category-icon>`; the full pages gained two parity deltas (square tile + unread wash). REAL-only (fake
> "Channels"/"Housekeeping" pills REFUSED), self-only #28/#26, existing self-only POST mark-read routes. Suite **977/977**.
> Then a **presentation POLISH SWEEP** (`docs/POLISH-SWEEP.md`, Batches 1–6: CSS tokens · hover states · dark-panel/tokenize ·
> the framework-free double-submit-disable · count-up · owner Expenses bars) and — closing that sweep's last money-path flag —
> **ADD-CHARGE SERVER IDEMPOTENCY**: `postCharge` now carries a per-submit server nonce mirroring the on-desk payment gate
> exactly (nullable-unique `folio_items.idempotency_key`; a `charge_nonce`), so a duplicate — even two concurrent POSTs
> bypassing the UI disable — records the charge ONCE (mutation- + browser-proven; extends **#48** to both money-write routes).
> Suite **981/981**; PHPStan L5 = 0; Pint clean. **Then the POLISH SWEEP's P3 tails cleared → the sweep is now FULLY CLEARED
> (P1·P2·P3):** signup step cross-fade (I4) + guest-portal keyboard focus rings (M5) + the password-strength meter on
> set-password & signup (D14 — a client-side VISUAL AID; the `Password::min(8)` server rules UNCHANGED + authoritative,
> 76 password-enforcement tests green) — all browser-proven; D10 chart consolidation re-assessed vs the prototype and
> **finally deferred by decision** (interactive Alpine chart vs pure-static shared component + a per-bar-label regression).
> Presentation + one client-side meter only; money/auth/isolation + invariants #1–48 UNTOUCHED; suite 981/981, PHPStan L5 = 0,
> Pint clean.
>
> **Then SUPER ADMIN · GATE 0 (2026-07-26) — the hardened, ISOLATED `/admin` platform realm (INVARIANT #49; features NOT
> built yet).** A NEW parallel realm ADDED ALONGSIDE the tenant realms, ADDITIVE ONLY — **the prior 981 tests all stay
> green** (suite now **997/997**: 981 existing + 16 new Gate-0 isolation proofs); PHPStan L5 = 0; Pint clean;
> `migrate:fresh --seed` clean (seeded demo admin). A separate `platform_admins` table + `PlatformAdmin` model (NO
> `organization_id`, NO spatie role) + a NEW `admin` session guard — a platform admin is NOT a tenant `User`, so tenant
> guards never resolve it and it never resolves a tenant user (the `guest`-realm discipline). **Cross-tenant reads use the
> EXISTING `TenantContext::runAsSuperAdmin()` via `PlatformQuery` — admin-guard-gated + audited + scope-restored (never
> leaks; `/admin` has no ambient tenant context, so a stray tenant query there fails CLOSED).** MANDATORY 2FA (TOTP),
> hardened rate-limited login, admin actions audited to a SEPARATE `platform_admin_actions` ledger, noindex + robots
> disallow (path not subdomain — SEO untouched), NO public sign-up (seeded via `php artisan admin:create`). A cohesive
> "control-room" admin design (distinct from the warm tenant glass). **Mutation-proven isolated + browser-verified**
> (login → forced 2FA → console; operator refused at `/admin`). **The deferred Phase-7a platform-admin item is RESOLVED
> PROPERLY** — platform admin ≠ org `User` ≠ the unassignable global spatie role (and STEP 0 confirmed there was never an
> `is_super_admin` flag on `users` to move; a test asserts its absence). See `DECISIONS.md` 2026-07-26. **Invariants #1–49.**
>
> **Then SUPER ADMIN · BUILD 1 (2026-07-26) — the core console: Dashboard + Customers + Plans** on the proven Gate-0
> foundation. ADDITIVE ONLY — **the existing 997 tests all stay green** (suite now **1013/1013** = 997 + 16 new). **🔒
> EVERY cross-tenant READ via the audited `PlatformQuery::read()`; every tenant-scoped WRITE via the new sibling
> `PlatformQuery::mutate()`** (both runAsSuperAdmin — scope-restored, admin-guard-gated, audited; no new bypass). **Dashboard:**
> real cross-org metrics (customers/status/subscriptions/properties/reservations/plan-mix/6-mo signups + a recurring
> PLAN VALUE labelled honestly as contracted-not-billed since charging is account-gated) — real (#39), honest zeros.
> **Customers:** list + detail of every org (operational facts only, **#28 — no end-guest PII**), + audited
> suspend/reactivate (`mutate`); **impersonation DELIBERATELY deferred** (a test asserts no such route). **Plans:** the
> Phase-7b `plans`/`plan_features` catalog is now ADMIN-authoritative — CRUD (integer money #17), and **plans DRIVE
> FEATURE-GATING** via the existing `Organization::planAllows` (fail-open): a reusable `EnsurePlanFeature` middleware +
> clean "not in your plan" page (additive — a granting plan behaves EXACTLY as today; only an excluding plan gates),
> assign-a-plan-to-a-customer (audited, drives features). **🔒 Stripe billing sync DEFERRED** (plan mgmt + gating +
> assignment now; the `stripe_price_id`/subscription sync is the go-live seam). `BillingService::catalog()` re-pointed to
> read features from the DB (config = seed + blurb). **Mutation- + browser-proven** (assigning demo Essential→Pro flipped
> `planAllows('channels')` false→true). **NEW INVARIANT #50 — plans are DB-backed + drive feature-gating ADDITIVELY**
> (granting = unchanged behaviour, excluding = a clean gated state; every admin cross-tenant op via the audited
> PlatformQuery). See `DECISIONS.md` 2026-07-26. **Build 2 = Security & activity log + Integrations. Invariants #1–50.**
>
> **Then SUPER ADMIN · BUILD 2 (2026-07-26) — the final two areas → THE PANEL IS COMPLETE.** ADDITIVE ONLY — **the
> existing 1013 tests all stay green** (suite now **1029/1029** = 1013 + 16 new). **Security & Activity Log
> (privacy-built-in):** auth signals (login/failed/lockout) captured by ADDED listeners on the framework auth events —
> **the tenant login flow is NOT modified** (the capture can never break auth); operator/owner use `Auth::validate` (no
> per-attempt Failed) so their signal is the Lockout, guest fires Failed, the admin realm dispatches its own Failed. **🔒
> PRIVACY BUILT IN FROM COMMIT 1 (GDPR):** a 90-day retention prune (`platform:prune-activity`, scheduled), security-
> monitoring purpose (documented), /admin-only, **#28 — no guest email persisted plaintext**. The view = recent sign-ins
> + a 24h failure summary + a cross-tenant account-events slice (audit ledger via `PlatformQuery::read`, audited) +
> per-customer slice. **Integrations:** the founder's master channel credentials (Channex/OTAs) in `platform_integrations`,
> **`encrypted:array` at rest** (like the owner IBAN, #28 — proven the plaintext never lands on disk), **write-only /
> masked (never echoed after save)**, managed via an audited action (records WHICH channel, never the value), a
> test-connection STUB (real sync account-gated, labelled honestly); the customer-self-connect model documented, built
> later. **NEW INVARIANTS #51** (auth signals captured by added-listeners — tenant flow unmodified; privacy-retained) **+
> #52** (platform credentials encrypted-at-rest + admin-managed + never-echoed). **THE SUPER ADMIN PANEL IS COMPLETE
> (Gate 0 + Build 1 + Build 2). Deferred tails (account-gated / documented follow-ups): Stripe billing sync,
> impersonation, suspension access-enforcement, per-feature-limit enforcement, the real channel sync, customer-self-connect.**
> See `DECISIONS.md` 2026-07-26. **Invariants #1–52.**
>
> **Then a DEEP END-TO-END AUDIT of the whole Super Admin panel (2026-07-27, `docs/SUPERADMIN-AUDIT.md`) — a full
> Playwright walk of every area/page/form/action, audited as hard on security as on UI. ZERO CRITICAL + ZERO P1;
> every high-stakes property re-verified LIVE (isolation, no cross-tenant leak [context-less tenant query throws
> after an admin read], every action audited, no credential plaintext leak, mandatory 2FA, no enumeration, #28 no
> guest PII, money #17). Genuine findings were all UX/polish/honesty, fixed additively:** customer-list PAGINATION
> (25/page, filters carried); SUSPENSION HONESTY (it's a status flag, not yet an access block — presentation fix,
> enforcement still deferred); a plans-catalog honesty note; the admin double-submit guard; the wired `plans.toggle`
> button; an admin favicon. Honest deferrals (Stripe sync, impersonation, suspension enforcement, per-feature limits,
> real channel sync, self-connect) confirmed correct + untouched. Additive; the security model preserved (audited
> `PlatformQuery`, no new bypass); tenant realms untouched; suite green (admin 50/50) + PHPStan L5 + Pint clean.
> **The Super Admin panel is AUDITED + FIXED + PRODUCTION-READY.**
>
> **Then the SUPER ADMIN PROFILE & ACCOUNT page (2026-07-27, INVARIANT #53)** — every platform admin manages their
> OWN identity (profile · login email · password · 2FA recovery), a real gap. Additive; existing 1031 tests green
> (suite now **1040/1040** = 1031 + 9 new). A SEPARATE parallel implementation for the admin realm that MIRRORS the
> owner account-rebuild discipline (not its wiring): 🔒 self-only; password change requires the CURRENT password
> (`current_password:admin`) + the real `Password::min(8)` rule + the single hash+rotate path; **email change is
> RE-VERIFIED** — parked in the new `platform_admins.pending_email`, switched only when a SIGNED expiring link
> (sha1-bound) opened from the new inbox confirms it, current-password required, the old email stays the login and
> **the pending email CANNOT log in until verified** (fail-closed on bad-signature/stale-hash/taken-email); 2FA
> recovery-code regeneration (current-password + audited); every change audited to `platform_admin_actions`.
> Mutation- + browser-proven end-to-end. See `DECISIONS.md` 2026-07-27. **Invariants #1–53.**
>
> **THE ENTIRE BUILDABLE PRODUCT IS COMPLETE (Phases 0–7), and Phase 8's two OBTAINABLE integrations are LIT UP.**
> Three full user-facing apps — operator back office (`/app`), owner portal (`/owner`), guest portal (`/guest`) —
> plus a public storefront (`/book/{slug}`) and public self-signup (`/signup`) that atomically provisions a new
> tenant org on a free trial. On a certified multi-tenant backend (PMS + channel manager + Stripe payments + owner
> accounting + maintenance + unified inbox + notifications/emails + reviews + analytics + tenant provisioning +
> subscription billing + owner sub-tenancy + staff rostering), behind **hardened multi-realm auth** (operator/owner
> `web` guard + a separate `guest` guard; login · self-signup · invites · owner-team invites · password reset ·
> change-password · 2FA/TOTP). MULTI-PROPERTY (`PropertyContext`) · REAL analytics (`PerformanceMetrics`, RevPAR =
> ADR × occupancy) · the SaaS layer (atomic provisioning · subscription billing/paywall · owner team · team schedule).
> **Phase 8 — PRODUCTION EMAIL** (Postmark, `MailSafetyGuard` code-guard): code-complete, Subhan's cutover = DNS +
> token (`docs/EMAIL-PRODUCTION.md`). **Phase 8 — LIVE STRIPE across all three seams** (subscription billing · guest
> card charge · owner payout rail/Connect): TEST-MODE-PROVEN (webhook-source-of-truth, signature-verified against the
> raw body, multi-layer idempotency, no raw PAN/SAQ A, the payout double-transfer-guarded, `StripeSafetyGuard`
> code-guard), Subhan's cutover = the live-key flip (`docs/STRIPE-PRODUCTION.md`).
>
> A **full-system Playwright AUDIT** (`docs/AUDIT-REPORT.md`, all 5 apps, 26 screenshots) found ONE demo-blocker +
> follow-ups, now FIXED (the four unstyled operator controller-pages wrapped in `x-layouts.operator`; the storefront
> rate-seed rolling window; the dev expire-trial button = already-gated false positive; the `/livewire/update`
> owner-capability boundary test — gate holds). Verdict: **DEMO-READY**; customer-ready pending the account-gated tail
> + Subhan's cutovers + deployment.
>
> A **DESIGN-FIDELITY PASS is ✅ COMPLETE — ALL FOUR GATES (2026-07-25)** (`docs/design/DIVERGENCE-REGISTER.md` = the
> per-page source of truth, **76 page-rows**; summary folded into `docs/design/CATALOGUE.md`; dated entry in
> `DECISIONS.md`) — presentation-only, per-page, three-category discipline (✅ KIND-1 layout → FIX · 🚫 KIND-2
> fabricated content → REFUSE, style the honest/empty state · 📐 KIND-3 beyond the presentation boundary → DOCUMENT).
> **GATE 0 done** (shell regrouped to OVERVIEW/FRONT DESK/OPERATIONS/FINANCE/SYSTEM) + the pre-audit 4 pages
> (Dashboard, Reservation Detail, Reservations List, Guests CRM RESTRUCTURED; Rack Gantt + Front Desk = KIND-3
> divergences). **✅ GATE 1 — OPERATOR COMPLETE (14 queue items, `4e5efde`→`6467acb`):** RESTRUCTURED (real KIND-1) =
> Rates · Maintenance ×4 (board KPI band · work-order dark `.vy-tonight` status panel · costs real proportional bars ·
> settings two-column) · Notifications feed (category icons) · Inbox (KPI band) · Reports (real KPI band) · Insights &
> Portfolio (dark-hero → uniform light) · Housekeeping (KPI band). DOCUMENTED divergence (no code) = Units (no
> prototype) · Property Settings · **Roles (editable catalog REFUSED for security #30)** · Profile · **Billing
> (account-gated #41)** · Team Schedule (KIND-3). **✅ GATE 2 — OWNER COMPLETE (25 surfaces):**
> 5 RESTRUCTURED (Bookings + Booking-detail 4-up KPI · **Statement** hero-visibility fix [a real invisible-element
> bug: `vy-swap-next` overrode `.vy-fade` with a no-`forwards` keyframe → stuck `opacity:0`; removed] · **Reports**
> 2-up KPI via a new opt-in `.vy-kpi-grid--2` · **Insights** 4-up KPI), 11 at-parity, 9 intentional-divergence
> (Unit Detail #28 · Rates direct-edit · Payout Method #45 · Add Unit request · Reviews #37 · Team matrix-drift ·
> Settings/Help focused subset · Tax multi-year). **✅ GATE 3 — GUEST COMPLETE (17 surfaces, 0 code):** 15 at-parity,
> 2 divergence (Login hero/social · Signup booking-tied-claim); #28/#33/#34/#36/#37/#45 surfaced honestly in the UX.
> **✅ GATE 4 — PUBLIC + ONBOARDING/BILLING COMPLETE (0 code):** Room detail / Checkout (#45 pay-on-arrival) / Signup
> wizard (#40/#41) / Paywall+trial (#41) / Sub-checkout (#45) / Invite-owner / operator auth = at-parity; Browse +
> Search results = scope divergence (per-property storefront vs multi-property collection). **THE WHOLE PASS WAS
> PRESENTATION-ONLY — 3 owner CSS/Blade fixes total, GATES 3+4 zero code; suite green throughout.** Shared UI
> anti-drift generalised: `.vy-tonight` dark stat panel, count-agnostic `.vy-dash-kpis` (`--3`/`--5`) AND owner
> `.vy-kpi-grid` (`--2`/`--4`). `docs/design/raw/*.html` served via `php -S 127.0.0.1:8899` (gitignored) for diffing.
>
> Suite **924 tests / 7517 assertions** green on MySQL 8; PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean;
> Redis/Horizon live. **Invariants #1–#45.** Read order for a fresh session: **PROJECT-STATE → DECISIONS →
> GAP-ANALYSIS → AUDIT-REPORT → design/DIVERGENCE-REGISTER.**
>
> **TAGGED OUTSTANDING REGISTER (honest):**
> - **DESIGN-FIDELITY PASS — ✅ COMPLETE (all 4 gates, 2026-07-25)** (operator · owner · guest · public/billing; 76
>   page-rows in `docs/design/DIVERGENCE-REGISTER.md`, summary in `docs/design/CATALOGUE.md`, dated entry in
>   `DECISIONS.md`). Presentation-only throughout — 3 owner CSS/Blade fixes total, GATES 3+4 zero code; suite green.
> - **GAP-SWEEP + Batch A cheap wins — ✅ DONE (2026-07-25)** (`docs/GAP-SWEEP.md` = the READ-ONLY sweep of all four
>   realms; dated entry in `DECISIONS.md`). Wired the 4 cheapest wins whose backends already existed (LINK/RENDER
>   only, no service/route/money/auth touched): operator nav FILTER — role-gated items hidden instead of a misleading
>   "Coming soon" (`687e234`); the owner **booking-summary · remittance · annual-tax** PDFs now REAL
>   `@ownercan(CAP_EXPORTS)`-gated links (`owner.statements.bookings` / `owner.payouts.remittance` / `owner.tax.annual`
>   — all built+tested; remittance offered only when a payout is matched, annual-tax only for years with committed
>   statements). Browser-verified: 4 PDFs 200/application-pdf; a **view-only sub-user gets 403 on all four (#42)**.
>   Suite **945 / 7644** green; PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean.
> - **BATCH B — ANALYTICS — ✅ DONE (2026-07-26)** (`docs/GAP-SWEEP.md` §5; dated entry in `DECISIONS.md`;
>   register rows updated). Three features, all REAL (#39): **(1)** the owner Statement **by-unit split** — pure
>   ledger arithmetic reconciling to the net to the cent (#17), owner-scoped (#28) — `dbf07d3`; **(2)**
>   **per-unit analytics** on My Units + Unit Detail (real revenue · nightly rate · best-performer · 6-month NET ·
>   12-month occupancy · channel mix · a real "Needs your attention" feed) via the shared `OwnerUnitAnalytics` +
>   a new shared `<x-charts.bars>` — `1e1269f`; **(3)** operator + owner **Insights booking cadence** (lead-time
>   distribution + median · day-of-week occupancy · on-the-books) via ONE shared
>   `App\Support\Analytics\BookingCadence` (anti-drift), replacing the "Soon" deferrals. A **flagged, demo-only
>   seed** (`DemoSeeder::seedAnalyticsHistory`, backdated `created_at`) gives the demo a real, meaningful shape
>   (24 reservations, median lead 42 days, buckets 21/21/42/17%); the runtime computes real aggregates from real
>   rows (proven by `BookingCadenceTest`). **REFUSED (#39):** the recommendation/pricing engine + the per-element
>   "vs last year" deltas (no fabricated LY; prior-year demo seed scoped out — needs prior-year availability).
>   **INVARIANT #47.** PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean.
> - **BATCH C — OPERATOR OPERATIONS — ✅ DONE (2026-07-26)** (`docs/GAP-SWEEP.md` §5; dated entry in
>   `DECISIONS.md`). Wiring EXISTING guarded services, never raw writes: **housekeeping assign** (a real
>   Housekeeping-dept picker → `HousekeepingService::assign`, `housekeeping.manage` — `a728fcc`); **reservation
>   move** (via `AssignmentService::assignUnit` — same-type free units, lock + no-overlap; proven not a raw
>   write) + **add-charge** (`postCharge` #17) + **the on-desk payment MONEY-GATE** — idempotency (a
>   double-submit records ONCE, mutation-proven both ways; a new UNIQUE `payments.idempotency_key`) + a
>   Cash/Bank/Card selector (card = the deferred Stripe seam, "test-mode") — `b6bd623`. **Correction to the
>   sweep:** on-desk CASH payment was ALREADY built; the real gap was idempotency + move + charge. Settings:
>   **Tax & fees editing** now real (bounded, `properties.manage`); **Invoice-numbering + Branding** scoped to a
>   focused follow-up (honest "Soon" — no invoice-series store yet; branding logo is a larger media surface);
>   Notification routing stays "Soon". **INVARIANT #48.** Suite green; PHPStan L5 = 0; Pint clean.
>   **↳ FOLLOW-UP (2026-07-26): add-charge now ALSO carries a per-submit server nonce** — #48 money-idempotency
>   now covers BOTH money-write routes. Batch-4's UI double-submit-disable was the ONLY guard on `postCharge`;
>   it now mirrors the payment path exactly (nullable-unique `folio_items.idempotency_key` + pre-check +
>   catch-`UniqueConstraintViolationException`; a `charge_nonce` hidden field). A duplicate — even two
>   **concurrent** POSTs bypassing the UI disable — records the charge ONCE. Mutation-proven (same key → 1, no
>   key → 2, distinct → 2) + browser-proven (2 concurrent same-nonce POSTs → 1 `folio_item`). Closes
>   POLISH-SWEEP I3's flag; DECISIONS.md 2026-07-26. Suite **981/981**; PHPStan L5 = 0; Pint clean.
> - **ACCOUNT-GATED remaining** (each a focused prompt as the account lands): Channex live OTA sync · WhatsApp/OTA
>   inbox channels · SMS (waitlist/notifications) · guest ID verification / KYC. (Email + Stripe are DONE — pending
>   only Subhan's cutovers.)
> - **RESIDUAL PLATFORM item** (deferred): the global `Super Admin` role's residual assignability — a Horizon-style
>   platform gate + a spatie-PK rework; a founder must NEVER hold it (provisioning already gives them `Property
>   Owner`, #40).
> - **SUBHAN'S LIVE CUTOVERS (not code):** email DNS (SPF/DKIM/DMARC) + Postmark token + one real send
>   (`docs/EMAIL-PRODUCTION.md`); Stripe go-live (activate · live Prices · both webhook endpoints · flip ENV · $1
>   smoke-test → refund, `docs/STRIPE-PRODUCTION.md`).
> - **DEPLOYMENT (not a build):** runs locally on XAMPP — needs production hosting/domain/HTTPS/MySQL/Redis-Horizon.
>
> ---
> *Historical one-paragraph-answer (pre-7c; kept for the arc — trust the CURRENT STATE block above where they differ):*

> **THE ONE-PARAGRAPH ANSWER.** The product is **COMPLETE, DEEP, DEMOABLE end-to-end, and safe for a real owner
> login**: a certified multi-tenant backend (PMS + channel manager + payments + owner accounting + maintenance
> + unified inbox), a **fully hardened auth chapter** (login · invites · password reset · change-password ·
> **2FA/TOTP**), and **~68 styled, browser-verified screens** across the owner portal, the operator back office,
> the public booking storefront, the **COMPLETE guest portal** (Phase 4b/4c) and the **Reviews module** (Phase 5:
> Guest Review · Operator Reviews · Owner Reviews · storefront ratings/reviews) — all on real scoped data under the
> honesty rule. **Five security/integrity
> holes have been found and closed this session, none of them by a failing test:** the `/app` authorization leak
> (**#26**), the cumulative over-refund (**#27**), the missing GDPR-erasure trigger (**#28**), the unguarded
> re-termination (**#29**), and a **privilege escalation** — any back-office role could invite itself an
> Accountant and inherit `payments.refund` — closed by enforcing the permission catalog (**#30**). **The
> security/integrity register is CLEAR**, so every screen inherits correct enforcement instead of needing a
> retrofit. What remains is **subsystems/epics** (real commitments, not quick wins), **speculative** (Tier 2 —
> designed, no backend, no signal), or **account-gated** (needs someone else's credentials), plus the small open
> items in the register. **The buildable-in-a-vacuum, clearly-worth-it core is essentially complete — 2FA was the
> last such subsystem.** ⚠️ **UPDATE (2026-07-20): the customer signal ARRIVED** — a warm operator contact
> (Fatima) liked the demo AND the prototype, so the requirement is now **"make the real product work like the
> prototype"** (the "no customer has reacted / don't build blind" framing that once sat here is SUPERSEDED — see
> the NEXT ACTION section at the foot of this file). **Phases 0, 1, 2, 3, 4a, 4b, 4c, 5, 6 (a+b), 7a AND 7b of
> `docs/GAP-ANALYSIS.md` are DONE — Guest Portal + Reviews complete, MULTI-PROPERTY, real ANALYTICS, public
> SELF-SIGNUP, AND SUBSCRIPTION BILLING (the free-trial model); Phase 7c (Owner Team) is next.** Phase 7b = BILLING:
> plans/tiers (Essential/Pro, PLACEHOLDER prices — Subhan sets real ones) + `App\Support\Billing\SubscriptionStatus`
> (the ONE derivation — trial/active/lapsed, **fails OPEN** on a missing record) + the REAL trial started atomically
> in 7a provisioning + the Paywall + Checkout (company/VAT, period toggle) + access-gating (`EnsureSubscriptionActive`:
> lapsed → paywall, NEVER mid-trial) + feature-gating (`Organization::planAllows` — channels Pro-only). **🪤 THE LIVE
> CHARGE + Stripe webhooks are ACCOUNT-GATED** (deferred, same as the guest card charge / owner payout rail): the
> subscribe action takes no money, stores no card, never flips an org to active (`stripe_subscription_id` null) —
> invariant **#41**. Phase 7a = TENANT
> PROVISIONING: `App\Services\TenantProvisioningService` atomically creates a new operator org (org + provisioned
> roles + founder + `Property Owner` + first property in ONE transaction) — the chicken-and-egg escaped via the
> pre-tenant `TenantContext::runAsSuperAdmin` path (`Organization::create`'s first `app/` caller;
> `OrganizationRoleProvisioner::provision()`'s first real caller) — plus the public `/signup` onboarding wizard
> (rate-limited, neutral enumeration, founder auto-logged-in, honest "finish setup"). **BOTH deferred fail-closed
> RBAC defects FIXED, mutation-proven:** org-creation now provisions roles (#1); the founder holds the ASSIGNABLE
> `Property Owner`, never the global `Super Admin` (#2 self-provision) — invariant **#40**. Phase 6b = ANALYTICS: the shared `App\Support\Analytics\PerformanceMetrics` +
> `MetricSet` metric layer (ADR/RevPAR/occupancy/room revenue, stay-night attribution, **RevPAR = ADR × occupancy
> exactly**, reconciling to `FolioTotals` + the availability spine; scopes property/all/owner-units) → operator
> Insights + Reports (`reports.view`, per-property + all-properties) → owner Reports + Insights (owner-scoped,
> **earnings reconcile to the owner ledger**) → the real Portfolio comparison. **Every figure a REAL aggregate; the
> prototype's invented history/narrative REFUSED; a thin period an honest empty state; lead-time/pace deferred "Soon"
> — invariant #39.** 3 gated sub-gates, mutation-proven (reconciliation + owner isolation), the fabrication-absence
> tested. Phase 6a = MULTI-PROPERTY: a per-user session-backed `PropertyContext` + a topbar
> switcher + the COMPLETE `Property::…->first()` refactor (Tier A 10 sites/8 files + Tier B 5 blend-risk list screens,
> grep-clean — a `->first()` silently returns an ARBITRARY property once an org runs several, so this was a genuine
> wrong-property bug) + property CRUD (`properties.manage`, org-scoped) + the Portfolio overview (real per-property
> data; ADR/RevPAR deferred to 6b). Property access = the `property_user` **pivot-or-all** allow-list (empty ⇒ full
> org — invariant **#38**); `#[Locked]`+mount-capture (`ResolvesCurrentProperty`) fixes the Livewire `/livewire/update`
> boundary. Mutation-proven the selected context governs (2 gated steps). Phase 5 = the REVIEWS module across five surfaces: Guest Review (tied to a real completed stay,
> one per stay — **#36**), moderation (only published+public is public; rating DERIVED, never fabricated — **#37**),
> Operator Reviews (respond + moderate, `reviews.manage`, new-review notification wired), Owner Reviews
> (owner-scoped read), and the public storefront (derived rating + published reviews, #28 byline) — 4 gated
> sub-gates, pushed. Phase 4c = guest money +
> engagement: Payments + Folio Receipt + five money PDFs (card charge account-gated), the Wallet/credits ledger
> (append-only, balance DERIVED, no-overdraw — **invariant #34**), Guest Messages (the Unified Inbox extended, third
> participant surface — **invariant #35**), and Wishlist + Waitlist (real self-only stores; notify-trigger/auto-hold
> deferred honestly) — 4 gated sub-gates, pushed. Phase 4b = the GUEST
> PORTAL CORE on the 4a foundation — Trips · Reservation detail · Manage Booking (operator-confirmed requests,
> **#32**) · Online Check-in (a guest pre-check-in intent, NOT the operator's authoritative check-in) · Arrival
> Info (door code + wifi, isolated to the owned booking AND released only within the arrival window, **#33**);
> every screen guest-isolated + mutation-proven, 4 gated sub-gates, pushed. A 500 the green suite masked (operator
> confirm route-model-bound a scoped model that resolved pre-tenant) was found by opening it in a browser and
> fixed + test-hardened. Phase 4a = the GUEST AUTH FOUNDATION — a
> SECOND auth realm for guests (Guest Authenticatable on a separate `guest` guard + a blind-index email lookup,
> booking-tied claim + email verification, guest login + reset hardened, Guest Account + guest isolation), realm
> separation + guest isolation mutation-proven, the operator/owner auth untouched — 4 gated sub-gates, pushed.
> Phase 3 = the notification subsystem (in-app feeds +
> typed preferences that GENUINELY gate on both channels) + the wired real events + all guest transactional emails
> (Mailpit-real) + owner/operator email delivery + the Email Templates view — 5 gated sub-gates, pushed.

- **On `main`, working tree clean, local == origin** (HEAD advances by one with the doc commit that carries these
  words; check `git log` for the true HEAD — see the HEAD convention below). Only the
  untracked design-source exports under `docs/design/raw/` stay uncommitted — **gitignored** raw Claude Design
  HTML, **not in a fresh clone**. Suite: **`php artisan test` → 869 tests / 7233 assertions, all green** on
  genuine **MySQL 8** (`vyrobel_test`) — re-verified for this capstone. ✅ **BOTH flaky tests are CLOSED**
  (root-caused 2026-07-17 — and they were the same disease: an assertion whose needle was too generic, never a
  product bug. See the register). The suite is a trustworthy gate.
  **PHPStan (Larastan) level 5, 0 errors** (`--memory-limit=1G`) — re-verified. **Pint clean** — re-verified.
  ⚠️ `phpunit.xml` sets **`memory_limit=512M`** — the suite outgrew PHP's 128M CLI default (verified headroom,
  not a leak; see the register). `php artisan test` spawns a child that re-reads php.ini, so `-d` won't reach it.
  Queue = **Redis/Horizon live** (`QUEUE_CONNECTION=redis`, `REDIS_CLIENT=predis`; `database` documented as
  fallback). The three planning docs (`docs/ROADMAP.md`, `docs/TIER1-SPEC.md`, `docs/DESIGN-COVERAGE.md`) live
  on `main`.
- ⚠️ **THE HEAD CONVENTION.** These docs are written and committed *before* the commit that contains them, so
  a "HEAD" named in any entry is the commit **one behind** the one that carries the words. The consolidation
  entry naming `e160eeb` ships in the commit *after* `e160eeb`. Check `git log` for the true HEAD; never treat
  a HEAD quoted in prose as current.
- **LATEST — the AUTH CHAPTER is DONE (2026-07-18).** After Wave 4 Core came two auth gates: **CHANGE PASSWORD**
  (`e6978d8`, operator `/app/security` + owner `/owner/settings`, current-password-verified, reusing the one
  shared `User::changePasswordTo()` hash+rotate path) and **2FA / TOTP** (`031716a` enrollment + recovery codes
  → `aca017c` login challenge → `11288d6` consolidation) — opt-in per user, secrets/codes **encrypted** (fixing
  a pre-existing plaintext `two_factor_secret`), the pending state grants no auth **by construction** (**#31**).
  `google2fa` + `bacon-qr-code`, not Fortify; browser-verified both portals. **2FA was the last buildable-in-a-
  vacuum subsystem.** See `docs/DECISIONS.md` 2026-07-18 + `docs/AUTH-INVENTORY.md`.
- **PRIOR PHASE: WAVE 4 CORE COMPLETE (2026-07-17)** — the three genuinely-buildable Wave 4 screens, three
  gated sub-gates (`8971d20` **Operator Rates** per-room-type over the certified `setRate`/`setRestriction` →
  `6e3b717` **New Reservation** guarded `createBooking` + 3 guards [honest per-unit availability via the new
  `UnitAvailabilityService`, PII name-only, NO raw PAN] → HEAD **backed Settings** [`/app/settings` +
  `/owner/settings`; first `app/` writes of properties/property_policies/organizations config; owner self-edit
  `user_id`-guarded]). Shared `App\Support\Money\MoneyInput` (one typed-money parser). No new permission —
  every gate reused an existing catalog permission. **DEFERRED as subsystems/account-gated** (Portfolio ·
  Owner Team · Vyrobel Onboarding · Group Booking · Owner Rates · Channels-as-drawn · Roles · Stripe card
  tokenization) — per-screen readiness + the trap register live in `docs/WAVE4-INVENTORY.md`. **Prior — WAVE 3
  COMPLETE (2026-07-17):** the owner inbox. **Prior — WAVE 2 COMPLETE:** five gated sub-gates, a commit each
  (`4eb71e2` → `cb8bcce` → `44444e6` → `f86cf4e` → HEAD): **Reservations list · Cancelled/No-show terminal
  states · the Refund screen · the Guests CRM list · the Guest profile.** Suite **430/4835 → 511 tests / 5503
  assertions**. Unlike Wave 1 this was **not** presentation-only — Sub-gate 5 adds the first guest write path
  (`GuestService`), because the design's "Save changes" persisted nothing and a fake save is not a screen.
  Three shared extractions prevent screen-to-screen drift: **`ReservationStage`** (badge + SQL in one class),
  **`GuestSpend`** (the BILLED definition, reconciling to the ledger by construction) and **`GuestHistory`**
  (stays · nights · "guest since" — extracted because the inbox had ALREADY shipped a different rule and said
  *"2 stays · guest since 2024"* where the CRM said *"1 stay · guest since 2026"*). **⚠️ Read the WAVE 2
  OUTSTANDING REGISTER in the deferred register below — four real items, deliberately not fixed.**
  See `docs/DECISIONS.md` 2026-07-17 (two entries).
- **(historical) WAVE 1 COMPLETE + the `/app` AUTHORIZATION HOLE CLOSED (2026-07-16).**
  Wave 1 shipped as four gated sub-gates (`55c9219` → `5530f6b` → `9ff2ad9` → `4d68e8c`): **10 new screens/
  documents over the CERTIFIED owner-accounting backend, presentation only** (⚠️ the original prose said "9"
  — an arithmetic slip, not a change in the facts; the Wave 1 tables below have always listed **10**: owner
  Balance · Expenses · Agreement · Archive · Unit Detail · Documents + 2 PDFs, and operator Owner Detail +
  Payout Run. Corrected 2026-07-17.) (Mechanically verified at every
  gate: **ZERO** files changed under `app/Services`, `app/Models`, `database/migrations`, `app/Payouts`).
  Browser-verifying it then surfaced a **critical pre-existing authorization hole** (an owner-role user could
  reach the whole operator back office and every other owner's financials), now **FIXED in two gated steps**
  (`5155a48` route gate → `f63866a` root cause) — **invariant #26**, defense-in-depth, each layer proven to
  block independently. Suite **340/3831 → 430 tests / 4835 assertions** (Wave 1 + the `/app` gate + the refund bound #27).
  **The product is now safe for a real owner login.**
- **AUTH — COMPLETE & SECURE (chapter finished): login/logout + invites (owner + staff) + password reset +
  in-app change-password + 2FA (TOTP).** **Change password** (`e6978d8`, operator `/app/security` behind #26
  only so every back-office role reaches it · owner card on `/owner/settings`): `auth()`-resolved (never a
  request id), **current-password-verified** (anti-hijack), reusing the ONE shared `User::changePasswordTo()`
  (hash via the 'hashed' cast + rotate remember-token) that reset was refactored onto so they can't drift.
  **2FA (TOTP, `031716a`→`aca017c`→`11288d6`):** enrollment (confirm-before-activate) + one-time recovery codes
  (single-use, `hash_equals`) + a login challenge slotted between password and session (`Auth::validate` →
  challenge → `Auth::login`; non-2FA login unchanged), secrets/codes **encrypted at rest** (fixing the
  pre-existing plaintext `two_factor_secret`), disable-requires-re-auth, self-only, rate-limited — **invariant
  #31** (`TwoFactorService` / `TwoFactorChallengeController` / `RequireTwoFactorPending`; `google2fa` +
  `bacon-qr-code`, NOT Fortify). Real session auth fronts the app; **the dev-login shim is gone.** Phase A: one shared login
  (operator+owner), active-only `Auth::attempt`, per-(email,ip) rate limit, generic non-enumerating errors,
  session regeneration, role-routing (`/app` vs `/owner`), `/book` public. Phase B: an operator invites via a
  **sha256-hashed, single-use, 7-day, revocable** tokened email link → the invitee sets a **hashed**
  password → activates → auto-login. **Three roles on one secure path:** OWNER → owner link + units +
  agreement → `/owner`; **STAFF (Hotel Manager / Accountant)** → Membership + a **real, fail-safe-validated**
  spatie catalog role → `/app`. **Password reset** on Laravel's Password broker — same posture (no
  enumeration, active-only, tokened single-use expiring, hashed, remember-token rotated, session regenerated,
  rate-limited). Proven by `PhaseBEndToEndTest` + `StaffInviteTest` + `PasswordResetTest`. **Dev email wired
  to Mailpit** (invite + reset emails land in a viewable inbox); expired invites are pruned daily
  (`invites:prune-expired`). **DEFERRED, flagged not faked:** **email DELIVERY in production** (dev = Mailpit;
  needs a real provider). **2FA is now BUILT** (see the auth bullet above / DECISIONS.md 2026-07-18). Remaining
  BLOCKED auth subsystems: self-signup/tenant-provisioning, guest auth, sessions/connected-apps/IP-rules. See
  `docs/DECISIONS.md` 2026-07-13 + `docs/AUTH-INVENTORY.md`.
- **DESIGN PASS — the original 17-screen pass is COMPLETE, and Waves 1–4 + auth have since taken it to ~38:**
  **owner portal (4 + Wave 1's 6 + 2 PDFs + Wave 3's Messages + Wave 4's Settings)** · **operator core (5) +
  Units (1) + Maintenance board & work order (2) + Inbox list & conversation (2) + Wave 1's Owner Detail &
  Payout Run (2) + Wave 2's Reservations list · Cancelled/No-show · Refund screen · Guests list · Guest profile
  (5) + Wave 4's Rates · New Reservation · Settings (3) + the `/app/security` change-password/2FA page (1)** ·
  **public booking storefront (3, incl. the real checkout WRITE)** · plus the auth screens (login · reset ·
  set-password · forgot · **2FA challenge**). The per-wave inventories below are authoritative; this line is the count. All on the certified backends, honesty rule enforced per-screen by a render/
  isolation test; **backends/schema/guarantees UNCHANGED** (presentation only). **No unstyled stubs
  remain.** Three role design systems: `components/layouts/owner`+`<x-owner.shell>`+`FormatsOwnerData` ·
  `components/layouts/operator`+`<x-operator.shell>`+`FormatsOperatorData` · `components/public/shell`
  (server-rendered, no Livewire). Operator writes use **POST→controller→service-action→PRG**; the public
  checkout write is session-CSRF-protected and goes through `AssignmentService`/`FolioService`.
- **DEMO-READINESS POLISH — DONE (2026-07-14).** **Room photos:** real operator uploads on the shared
  `media` table (secure upload — content-validated images, ULID filenames, size cap, tenant-scoped),
  rendered on the storefront (cover + gallery) + unit cards with the **gradient fallback kept**; storage =
  local `public` disk in dev (**S3 for prod, flagged**). **PDF export:** the **owner statement**
  (owner-scoped) and the **operator folio** (tenant-scoped) download as branded, PDF-safe documents
  (`barryvdh/laravel-dompdf`) that **reconcile to the on-screen figures to the cent** (shared
  `MoneyFormatter` + `FolioTotals`), no invented lines. Still deferred: descriptions/reviews, CSV export,
  remittance/booking-summary PDFs. See `docs/DECISIONS.md` 2026-07-14.
- **MAINTENANCE MODULE — BUILT & CERTIFIED (2026-07-14).** Maintenance tickets on units
  (`MaintenanceService`, mirrors HousekeepingService — guarded lifecycle open→in_progress→resolved/cancelled)
  with the TWO integrations that make it a real Vyrobel module: **(1) out-of-service ↔ availability** — an
  OOS ticket flips the unit to `out_of_service` and emits the EXISTING `unit.condition_changed` event → the
  existing recompute listener excludes it (AssignmentService/AvailabilityService UNTOUCHED; sold never
  touched; two-tickets + never-auto-cancel + honest negative-over-committed signal handled); **(2) cost →
  owner deductible expense** — a costed ticket on an owned unit posts via the CERTIFIED
  `OwnerExpenseService::recordExpense(source:$ticket)` → owner_ledger → statement (operator-owned = no
  expense; correction reverses append-only; period-lock respected). **STYLED UI COMPLETE (design pass
  2026-07-14):** the **board** (`/app/maintenance` — kanban by real status; "Unit OOS" badge + OOS rail;
  real New-ticket + Assign writes) and the **work order** (`/app/maintenance/{ticket}` — real facts +
  audit-event timeline; state-gated guarded lifecycle writes; **both integrations surfaced** — the OOS
  callout with the **manual-move flag** (real active-booking count on the blocked unit) and the cost card
  read from the real `owner_expense` (charged-to-owner vs operator-cost)). `DemoSeeder` seeds a coherent
  maintenance spread through `MaintenanceService`. See `docs/schema/maintenance.md` + `docs/DECISIONS.md`
  2026-07-14 (two entries).
- **UNIFIED INBOX — COMPLETE end-to-end (backend + styled UI, 2026-07-14).** Guest-messaging inbox (threads/messages linked to real
  guests/reservations), built with the **interface + fake + real** pattern: the **manual channel works
  end-to-end now**; the **real email/WhatsApp/OTA adapters are config-gated skeletons that throw "not
  configured"** (account-gated, never faked). `message_threads` + `messages` (tenant-scoped, morph-mapped);
  **inbound idempotency** via `unique(org, channel, external_message_id)` + createOrFirst catch-the-race
  (proven under TRUE OS-level concurrency); `InboxService` single write path; `App\Messaging` (MessageChannel
  interface + ManualChannel + Deferred skeletons + resolver, mirrors `App\Payouts`); inbound rides the
  reliability backbone (`ProcessInboundMessageJob`). Guest linking **REUSES `App\Services\GuestMatcher`** (the
  same matcher OTA import uses — no parallel matcher; hashed non-PII ref, encrypted address); threading GROUPS
  by ref or a participant-hash anchor (stranger case included); `Guest::anonymize()` **redacts the guest's
  conversation** (GDPR). **STYLED UI COMPLETE (design pass 2026-07-14):** the **thread list** (`/app/inbox` —
  guest name + real channel badge + preview + unread + linked-reservation tag; Alpine filters/chips over real
  rows) and the **conversation** (`/app/inbox/{thread}` — cream/terracotta bubbles, real delivery states incl.
  the honest FAILED; a REAL reply over the thread's channel, deferred → honest "not connected" composer; real
  Assign/Close/Snooze/Reopen; context sidebar with a DERIVED guest stay-stat, the reservation card + link).
  **Inbox is now in the operator nav.** Guest NAME + channel shown; encrypted identity never rendered.
  `DemoSeeder` seeds three real conversations. See `docs/schema/inbox.md` + `docs/DECISIONS.md` 2026-07-14 (three entries).
- **THE CORE PRODUCT IS COMPLETE & DEMOABLE end-to-end** — a real hybrid operator could run day-to-day
  operations (calendar/front-desk/housekeeping/maintenance/folio/dashboard/units/**inbox**), message a guest
  and see delivery states, an owner could read their statements/payouts/units + download a PDF, and a guest
  could book direct (browse→room→checkout→confirmation, a real guarded write) — the storefront shows real
  photos. **The buildable-in-a-vacuum core is essentially complete; what remains is account-gated or net-new
  speculative — see "NEXT ACTION".**
- **Phase 1 CERTIFIED** (multi-tenant core) · **Phase 2 CERTIFIED** (channel manager + payments +
  booking engine + staff layer; **Stripe validated against real test-mode 2026-07-03**) · **Phase 3 ·
  Module 1 — Owner Accounting & Payouts CERTIFIED** (2026-07-03).
- **Owner Accounting commit chain (per-gate):** `e9ccce0` (Step 0 schema/decisions) → `34f7e81`
  (Step 1 owners+agreements) → `b3be74a` (Step 2 attribution engine) → `222cef0` (Step 3 expenses) →
  `863c514` (Step 4 statements) → `bf05b04` (Step 5 payouts) → `079ae0e` (Step 6 portal+isolation) →
  `af25e6b` (Step 7 CERTIFIED).
- **OWNER PORTAL DESIGN PASS — COMPLETE (all 4 screens styled + live).** Presentation only, **no
  backend/schema/guarantee change.** Owner Statement, Dashboard, Payouts, and My Units are now styled and
  wired to real owner-scoped data behind the `components/layouts/owner` design system, `<x-owner.shell>`,
  and the `App\Livewire\Owner\Concerns\FormatsOwnerData` trait. Commit chain: `970b0e7` (Screen 1
  Statement) → `8f76dbd` (Screen 2 Dashboard) → `a8f7b77` (Screen 3 Payouts) → `bff8e05` (Screen 4 My
  Units). The **design-pass honesty rule is locked in `docs/DECISIONS.md`** (2026-07-11) and enforced by
  per-screen render/isolation tests. See "Owner portal — STYLED & LIVE" below.
- **OPERATOR DESIGN PASS — OPERATOR CORE COMPLETE (Screens 1–5 styled + live).** Presentation only, **no
  backend/schema/guarantee change.** A dedicated operator design system was extracted (mirrors the owner one):
  `components/layouts/operator` (same tokens; copied, not shared — owner layout untouched),
  `<x-operator.shell>` (grouped sidebar + topbar, extracted on Screen 1), and
  `App\Livewire\Operator\Concerns\FormatsOperatorData` (viewer + `money()` + **warm-only** status/source/
  housekeeping/priority/folio labels keyed to the real enums). Operator screens are **TENANT-scoped**
  (not owner-scoped); guest NAMES shown (staff surface), encrypted email/phone never. DONE: **Screen 1
  Calendar/Rack** `0e1ccaa` · **Screen 2 Front Desk** `39724e6` · **Screen 3 Housekeeping** `d715496` ·
  **Screen 4 Reservation Detail** `b49676d` · **Screen 5 Operator Dashboard** `434e1a9` (a NEW page —
  route `/app/dashboard`). Screens 2–4 **wire real mutations** via the reusable **operator-mutation
  pattern** — **POST → controller → existing service action → PRG redirect** (tenant-scoped by re-run
  middleware; `{id}` string param → `findOrFail` → cross-org 404; append-only preserved for money):
  check-in/out (Front Desk), status advance (Housekeeping), record-payment + full refund (Reservation
  Detail). Screen 5 is read-only; every KPI is a TRUE org figure (occupancy summed org-wide from the
  Availability spine — not a mislabeled per-type number). See "Operator screens — STYLED & LIVE" below.
- **⚠️ TWO live-integration checkpoints OUTSTANDING** (deferred money/network rails — everything else
  is real + tested): **Channex staging** and **Stripe Connect payout rail** (see Deferred register).

## What's built (one line per area — names the key service + where it lives)

- **Tenancy / RBAC** — `App\Models\Concerns\BelongsToOrganization` (global scope + auto-fill + guard)
  via `App\Support\Tenancy\TenantContext`; spatie teams (team = org), 14-role catalog
  (`config/authorization.php`). `SetTenantForWebRequest` middleware sets context per web request.
- **Property & inventory** — mixed hotel+apartment+villa under one property; polymorphic
  `media`/`documents`/`notes`/`tags`/`amenities` reused via `app/Models/Concerns/*`.
- **Rates & availability** — two-level; `App\Services\AvailabilityService` (recompute total/ooo/blocked,
  read `available`) — a collaborator of AssignmentService, never a second write path.
  `App\Services\RateAndRestrictionService` (price/restriction edits emit domain events).
- **Guests** — encrypted `email`/`phone`; `Guest::anonymize()` (GDPR: null PII + purge file bytes).
- **Reservations** — ONE `reservations` table, `source` distinguishes channel;
  **`App\Services\AssignmentService`** is the single guarded inventory write path (book/assign/move/
  extend/split/cancel/no-show/block/importBooking).
- **Folios / payments / tax** — `App\Services\FolioService` (append-only ledger, correct-by-reversal);
  `App\Payments\` — `PaymentGateway` interface + `ManualPaymentGateway` + **`StripeGateway`** (behind
  `Stripe/StripeClientInterface`, fake + real; **validated live test-mode**).
- **Channel manager (Channex)** — `App\Services\Channex\` (`ChannelManagerClient` + Fake/real client,
  ARI snapshot builder, webhook signature); `App\Jobs\Sync\` (RetryableSyncJob, AriPushJob,
  ProcessChannexWebhookJob); `App\Services\ChannelMappingGuard`; overbooking = accept-and-flag.
- **Public booking engine** — `App\Services\BookingEngineService` (search/quote/book/expire),
  `booking_quotes` advisory holds, `ResolvePublicProperty` middleware, `/book/{property}/*` (rate-limited).
- **Staff action layer** — `App\Services\FrontDeskService` (thin delegation) +
  `App\Actions\Reservations\CheckIn`/`CheckOut`.
- **Maintenance module** — `maintenance_tickets` (`App\Models\MaintenanceTicket`, morph `maintenance_ticket`)
  + **`App\Services\MaintenanceService`** (guarded lifecycle; `setUnitCondition` emits the existing
  `unit.condition_changed` → recompute excludes an out-of-service unit; `resolve`/`correctCost` post/reverse
  the cost via `OwnerExpenseService` on owned units). **Styled** operator screens: `App\Livewire\MaintenanceList`
  (board) + `App\Livewire\MaintenanceTicketDetail` (work order) + `App\Http\Controllers\Operator\MaintenanceActionController`
  (POST store/assign/start/resolve/cancel → PRG). Design: `docs/schema/maintenance.md`.
- **Unified Inbox module** — `message_threads` + `messages` (`App\Models\MessageThread`/`Message`, morph
  `message_thread`/`message`; tenant-scoped) + **`App\Services\InboxService`** (single write path: startThread/
  postInbound[idempotent]/sendOutbound/assign|close|snooze|reopen/receiveInbound). Channel seam **`App\Messaging`**
  (`Contracts\MessageChannel` + `Channels\ManualChannel` [real] + `DeferredChannel` skeletons + `MessageChannelResolver`)
  mirrors `App\Payouts`. Inbound on the backbone: `App\Jobs\Sync\ProcessInboundMessageJob`. Guest linking reuses
  **`App\Services\GuestMatcher`** (shared with `BookingImportService`). **STYLED** operator screens:
  `App\Livewire\InboxList` (thread list) + `App\Livewire\InboxThread` (conversation) +
  `App\Http\Controllers\Operator\InboxActionController` (POST reply/assign/close/reopen/snooze/link-reservation
  → PRG); `channelMeta()`/`channelIsConnected()` on `FormatsOperatorData`. In the operator nav. Design:
  `docs/schema/inbox.md`.
- **Owner Accounting (Phase 3 · Module 1)** — `owners` + `management_agreements` (effective-dated,
  `ManagementAgreement::resolveFor()`); **`App\Services\OwnerAttributionService`** (reads folio+
  reservation_units → immutable `owner_ledger`); **`App\Services\OwnerExpenseService`** (`owner_expenses`
  → deduction lines); **`App\Services\OwnerStatementService`** + `App\Services\PeriodLockGuard`
  (`owner_statements`/`owner_statement_lines`, period-locked projection); **`App\Services\OwnerPayoutService`**
  + `App\Payouts\` (`PayoutRail`/`ManualPayoutRail`/`StripeConnectPayoutRail` skeleton, `payouts`);
  **owner isolation** via `App\Support\Owner\OwnerContext` + `App\Models\Scopes\OwnerScope` +
  `App\Http\Middleware\SetOwnerScopeForPortal`; portal reads via `App\Services\OwnerPortalService`.
- **Auth (Phase A + B)** — `App\Http\Controllers\Auth\LoginController` + `LoginRequest` (shared
  operator/owner session login: active-only, rate-limited, non-enumerating, role-routing) + styled
  `auth/login`; **invite flow (owner + staff)** = `App\Services\OwnerInviteService` (sha256-hashed
  single-use expiring tokens; `owner_invites` table + `App\Models\OwnerInvite`, `owner_id` nullable) →
  `App\Mail\OwnerInviteMail` (email-safe, role-aware copy) → `App\Http\Controllers\Auth\AcceptInviteController`
  (public `/invite/{token}` set-password → activate → auto-login, redirect by role) ← operator side
  `App\Http\Controllers\Operator\InviteOwnerController` + `App\Livewire\Operator\OperatorOwners`
  (`/app/owners` + role-aware invite modal). **Three roles:** owner (owner link + `/owner`) · Hotel Manager
  · Accountant (Membership + real spatie role → `/app`); staff role-map + fail-safe live in the service.
  **Password reset** = `App\Http\Controllers\Auth\PasswordResetController` + `App\Mail\PasswordResetMail`
  (Laravel Password broker; `auth/forgot-password` + `auth/reset-password`; shared
  `App\Http\Controllers\Auth\Concerns\RedirectsByRole`). **Expired-invite prune** =
  `App\Console\Commands\PruneExpiredInvites` → `OwnerInviteService::pruneExpired()` (scheduled daily).
  Email delivery = Mailpit in dev (prod provider deferred).
- **Auth — change-password + 2FA (this session).** **Change password:** `App\Http\Controllers\Auth\ChangePasswordController`
  (operator + owner → one shared `change()`), the ONE shared `App\Models\User::changePasswordTo()` (hash +
  remember-token rotate — reset refactored onto it), operator page `App\Livewire\Operator\OperatorSecurity`
  (`/app/security`, behind #26 only), owner card on `/owner/settings`. **2FA (TOTP):**
  `App\Support\TwoFactor\TwoFactorService` (google2fa + bacon-qr-code — enroll/confirm/recovery-codes/verify/
  verifyChallenge/consumeRecoveryCode/disable), `App\Http\Controllers\Auth\TwoFactorController` (enable/confirm/
  regenerate/disable, self-only) + `TwoFactorChallengeController` (the login challenge) +
  `App\Http\Middleware\RequireTwoFactorPending` (fences the challenge; the pending marker is a session key, NOT
  the guard — **#31**); shared Livewire `App\Livewire\Concerns\PreparesTwoFactor` + partial across both Security
  panels; `users.two_factor_secret`/`two_factor_recovery_codes` **encrypted** casts;
  `LoginRequest`/`LoginController` split `Auth::validate` → challenge → `Auth::login`.
- **Wave 2–4 operator/owner screen services + shared extractions.** First guest write path
  **`App\Services\GuestService`** (Wave 2). Wave 4: **`App\Services\UnitAvailabilityService`** (honest per-unit
  availability for New Reservation) + the Rates grid as the first production caller of `setRate`/`setRestriction`.
  Drift-proof shared definitions (each with a proof-it's-shared test): **`App\Support\Reservations\ReservationStage`**
  (stage badge + SQL filter in one class), **`App\Support\Guest\GuestSpend`** (the BILLED lifetime-spend
  definition, reconciles to the ledger by construction), **`App\Support\Guest\GuestHistory`** (stays · nights ·
  "guest since"), **`App\Support\Money\MoneyInput`** (one typed-money parser), and the Blade-safety guard
  `assertNoRawBlade()` (tests/Support). Owner inbox: `message_threads.owner_id` + `BelongsToOwner` (Wave 3).
- **Photos** — shared `media` table (collection `photos`) via `App\Models\Concerns\HasPhotos` on RoomType;
  operator upload/reorder/cover/remove through `App\Http\Controllers\Operator\RoomTypePhotoController`
  (secure: content-validated images, ULID filenames, size cap, tenant-scoped); `public` disk (S3 for prod).
  Rendered on the storefront + unit/owner cards with a gradient fallback. `App\Support\Media\PlaceholderImage`
  seeds real demo JPEGs.
- **Shared presentation helpers (Wave 1) — "a document and the screen it mirrors must never disagree".**
  Each exists because the same concept was about to live in two places and drift:
  **`App\Support\Payouts\RailLabel`** — the ONE rail→label map (owner Payouts screen + Remittance PDF +
  Payout Run). An unknown rail **THROWS** rather than prettifying the column into a fabricated payment
  method; PHPStan enforces exhaustiveness. **`App\Support\Owner\OwnerOccupancy`** — the ONE owner-scoped
  occupancy derivation (My Units + Unit Detail), extracted behaviour-preservingly from the certified
  `OwnerMyUnits::occupancy()`. **Keyed on PHYSICAL UNIT ID, never room type** — `availabilityRange()` /
  `OwnerPortalService::occupancy()` are room-type-wide and a room type is shared with hotel rooms and OTHER
  owners' units, so reading occupancy that way shows an owner a number driven by units they do not own.
  Same family as `Support\Money\MoneyFormatter` + `Support\Folio\FolioTotals`.
- **PDF export** — `App\Services\PdfService` (barryvdh/laravel-dompdf) renders branded, PDF-safe documents:
  **owner statement** (`App\Http\Controllers\Owner\OwnerStatementPdfController` + `Support\Pdf\OwnerStatementDocument`,
  owner-scoped) · **operator folio** (`App\Http\Controllers\Operator\FolioPdfController` + `Support\Pdf\FolioDocument`,
  tenant-scoped). Both reconcile to the screen to the cent via the shared `Support\Money\MoneyFormatter` +
  `Support\Folio\FolioTotals`.
- **Multi-realm auth (operator/owner `web` + a separate `guest` guard)** — the operator/owner chapter (login ·
  invites [owner/Hotel Manager/Accountant] · password reset · change-password via `User::changePasswordTo` ·
  **2FA/TOTP** [`google2fa` + `bacon-qr-code`, enrollment + recovery codes + login challenge, secrets encrypted]);
  and the **guest realm** (Phase 4a) — `App\Auth\GuestUserProvider` on a separate `guest` guard (`config/auth.php`),
  a keyed **blind index** (`guests.email_hash`) since the encrypted email isn't queryable, booking-tied claim +
  email verification, `Guest::changePasswordTo`. `App\Http\Middleware\SetTenantForGuestRequest` +
  `App\Http\Controllers\Guest\Concerns\ResolvesGuest` (the single guest-resolution point). Realm separation +
  guest isolation mutation-proven; the operator/owner chapter unregressed.
- **Notification subsystem (Phase 3)** — `notifications` table (Laravel-native `Notifiable`) + the
  `App\Listeners\DispatchNotificationsForDomainEvent` listener (real `DomainEventRecorded` events → per-user feed,
  isolation-scoped + double-fire idempotency) + a TYPED `App\Services\NotificationPreferences` store that GENUINELY
  gates BOTH channels via `App\Notifications\FeedNotification::via()` (always-on Account-&-security can't be
  disabled). Operator/owner notifications in `App\Notifications\Operator\*` / `Owner\*`. 9 guest transactional
  emails (`App\Mail\*` on a shared brand layout) via `App\Listeners\SendGuestTransactionalEmails` (booking/
  cancellation/refund/review-request LIVE on their domain events; a mail failure never rolls back the domain txn).
- **THE GUEST PORTAL (Phase 4b/4c) — complete** — on the guest guard, every screen guest-isolated + mutation-proven
  (resolve through the guest's own relation, foreign → 404): **Trips · Reservation detail · Manage Booking**
  (operator-confirmed `guest_change_requests` via `App\Services\GuestChangeRequestService` — #32) **· Online
  Check-in · Arrival Info** (`App\Support\Guest\ArrivalPack`, door-code isolation + timing — #33) **· Payments +
  Folio Receipt + 5 money PDFs** (`App\Support\Guest\GuestPayments` + `App\Support\Pdf\GuestInvoiceDocument`/
  `GuestReversalDocument`; card charge account-gated) **· the Wallet/credits LEDGER** (`App\Services\GuestWalletService`
  + `guest_credit_ledger`/`promo_codes`; append-only, derived balance, no overdraw — #34; `FolioService::applyWalletCredit`
  is the folio-side seam) **· Guest Messages** (the Unified Inbox EXTENDED — `InboxService::postGuestMessage`/
  `markReadByGuest`, `guest_last_read_at` — #35) **· Wishlist + Waitlist** (`App\Services\GuestWishlistService` +
  `guest_wishlist_items`/`guest_waitlist_entries`). Controllers in `App\Http\Controllers\Guest\*`.
- **THE REVIEWS MODULE (Phase 5) — complete across 5 surfaces** — `reviews` model + **`App\Services\ReviewService`**
  (the single write path: submit tied to a real completed stay [#36] · publish/reject moderation · operator
  respond). The ONE rating derivation **`App\Support\Reviews\ReviewRating`** (`publicScope()` = public AND
  published; average DERIVED, never fabricated — #37) + `App\Support\Reviews\ReviewList` (the shared public list,
  #28 byline). Surfaces: guest `App\Http\Controllers\Guest\GuestReviewController` · operator
  `App\Http\Controllers\Operator\OperatorReviewsController` (`reviews.manage`) · owner `App\Livewire\Owner\OwnerReviews`
  (owner-scoped read) · public storefront (`BookingEngineController` room/browse). `App\Notifications\Operator\NewReviewNotification`
  on `review.submitted`; the Review-Request email on `reservation.checked_out`.
- **THE SHARED-EXTRACTION ANTI-DRIFT FAMILY** (the "a document and the screen it mirrors must never disagree" rule —
  one definition, so surfaces can't disagree; several mutation-proven-as-shared): `Support\Money\MoneyFormatter` ·
  `Support\Folio\FolioTotals` · `Support\Owner\OwnerOccupancy` · `Support\Payouts\RailLabel` ·
  `Support\Reservations\ReservationStage` · `Support\Guest\GuestSpend` · `Support\Guest\GuestHistory` ·
  `Support\Money\MoneyInput` · `Services\UnitAvailabilityService` · `User`/`Guest::changePasswordTo` ·
  `Support\Owner\OwnerLedgerAttribution` (the ONE "Net to you") · `Support\Guest\GuestTrip` ·
  `Support\Guest\GuestReservationView` · `Support\Guest\GuestName` (incl. the #28 `reviewByline`) ·
  `Support\Guest\ArrivalPack` · `Support\Reviews\ReviewRating` + `ReviewList` · `Support\Property\PropertyContext`
  (the ONE current-property resolution) + `Support\Property\PropertyOccupancy` (the ONE occupancy-for-a-property, shared
  by the Dashboard + Portfolio) · `Support\Analytics\PerformanceMetrics` + `MetricSet` (the ONE performance-metrics
  derivation — ADR/RevPAR/occupancy/revenue, reconciling; shared by operator Insights/Reports, the Portfolio
  comparison, owner analytics) · `assertNoRawBlade()` (the Blade-safety
  guard). **Before adding a computed figure to a second screen, extract the definition and prove it's shared.**

## INVARIANTS THAT MUST NEVER BREAK — #1–55 (the full guarantee stack)

> **All 55, grouped below.** #1–14 Phase 1 core (single write path · no double-booking · append-only ledger ·
> money in integer minor units · encrypted PII · tenant scoping · event discipline) · #15–18 Owner Accounting
> (attribution · append-only `owner_ledger` · period-locked statements · **never a cross-currency sum**) ·
> #19–20 the design pass (the honesty rule · scoped-render + guarded-mutation) · #21 Auth · #22 Maintenance ·
> #23–25 Unified Inbox (inbound exactly-once + grouping · deferred channels fail HONESTLY · inbox PII/GDPR) ·
> **#26 the `/app` back-office authorization gate** · **#27 the cumulative refund bound** · **#28 the
> guest-PII perimeter (+ the erasure trigger)** · **#29 the termination bound** · **#30 fine-grained
> permission enforcement** · **#31 the 2FA-pending state grants no authenticated access** · **#32 guest-initiated
> changes are operator-confirmed requests** · **#33 door-code/arrival isolation + timing** · **#34 the guest wallet
> is an append-only ledger (derived balance, no overdraw)** · **#35 guest inbox isolation** · **#36 reviews tie to
> real completed stays** · **#37 review moderation + rating are real (only published is public; rating derived)** ·
> **#38 multi-property: every property-scoped operation resolves through the selected `PropertyContext`, never an
> arbitrary `->first()`; a WRITE targets a concrete property, never "all"; property access is the `property_user`
> pivot-or-all allow-list; a created property is org-scoped** · **#39 analytics are REAL aggregates through the ONE
> shared `PerformanceMetrics` layer, reconciling to `FolioTotals` / the availability spine / the owner ledger — NEVER
> fabricated (no invented history/trend/narrative); a thin period shows an honest empty state; owner analytics are
> owner-scoped (their units only)** · **#40 tenant provisioning is ATOMIC + provisions roles: a new org is created
> only through `TenantProvisioningService` (org + 13 roles + founder + `Property Owner` + first property in ONE
> transaction, all-or-nothing); a provisioned org has a valid RBAC state; the founder holds the ASSIGNABLE org-admin
> role, never the global `Super Admin`; org isolation holds for provisioned orgs** · **#41 subscription billing:
> the trial is REAL (from the 7a provisioning timestamp, days derived); access is gated on the real billing state
> AFTER the trial (in-trial/active = full, expired/canceled = the Paywall, NEVER a mid-trial lock-out) and FAILS
> OPEN on a missing subscription; feature-gating is by plan (channels Pro-only); the LIVE Stripe charge + webhooks
> are ACCOUNT-GATED — nothing flips an org to `active` off a fake payment, no raw card stored; billing is
> tenant-scoped + `billing.manage`-gated** · **#42 owner sub-tenancy (Owner Team): a sub-user ⊆ their owner ⊆ their
> org — the NESTED isolation is COMPOSED from the existing `OwnerScope` (owner boundary) + tenant scope (org
> boundary), NO new scope; the 3 fixed owner-team roles (co-owner/accountant/view-only) gate the portal by the
> design's capability matrix (NOT a privilege chain) through `EnsureOwnerCapability`, and the **payout account /
> IBAN is co-owner-ONLY** (money-security, #28); team management is held by NO role — gated on
> `isPrimaryOwner()` (primary-owner-only), so `canAssign()` refuses every sub-user and self-escalation is
> STRUCTURALLY impossible; the owner-realm invite reuses the hardened token discipline (sha256-only, single-use,
> expiring, no-enumeration) and is OWNER-SCOPED (it can only land a sub-user under the invite's own `owner_id`); a
> removed sub-user's orphaned login is disabled (can't log in); the capability gate COMPOSES with — never masks —
> the owner-scope fail-closed (it only 403s users WITH owner access)** · **#43 team schedule (rostering): the
> roster is TENANT + PROPERTY scoped over REAL staff — a shift lands on the SELECTED property
> (`PropertyContext::id()`, never `->first()`, never cross-property/org) and is assigned only to a real staff
> member (`StaffRoster` = active `Membership` ∩ a back-office role; owners/guests/fabricated names excluded);
> managing the roster is manager-gated (`schedule.manage`, NOT Front Desk) while viewing is broad
> (`schedule.view`); time-off is a request→approval SEPARATION (a staff member requests their OWN, only a
> manager approves) and **SELF-APPROVAL is impossible** (`LeaveService` throws if the actor is the person the
> leave is for, or grants their own) — the guest-change-request/owner-add-unit pattern; a shift-on-approved-leave
> is a REAL computed conflict (assign-on-leave is refused; a pre-existing clash is flagged), never fabricated;
> the schedule PDF renders from the SAME `RosterWeek` builder as the grid, so it RECONCILES and is
> property-scoped (no id in the URL → cross-property/org not accessible). Shift-swapping / labor-cost /
> coverage-AI are OUT of scope (deferred honestly, never faked)** · **#44 production email (Phase 8): a real
> email is NEVER sent from a non-production environment — enforced in CODE by `MailSafetyGuard` (a real-sending
> transport [postmark/ses/…] is refused outside `production` and downgraded to `log` at boot, so even
> MAIL_MAILER=postmark in a dev/CI .env can't reach a real person); the test suite runs on a NON-SENDING
> transport (`array`); provider secrets live in ENV only (never committed); the Phase-3 boundary HOLDS with the
> real transport — a provider send failure is caught + logged (never the address, #28) and NEVER rolls back the
> booking/notification (the email is best-effort); mail goes to the correct recipient only (the guest's/owner's
> OWN address, #28). Bounce/complaint suppression is Postmark-native for v1 (an app-side, blind-indexed
> suppression store is documented, not faked); the DNS records + the one controlled real send are Subhan's** ·
> **#45 live Stripe (money paths, test-mode-first): a real charge can NEVER happen outside `production` — a live
> `sk_live_` key is refused by `StripeSafetyGuard` (the fake client is bound); the card NEVER touches the server
> (hosted Checkout — PCI SAQ A, no raw PAN); every money path FULFILS ON THE WEBHOOK, never the redirect (a
> subscription activates / a folio settles / a payout confirms only on checkout.session.completed /
> payment_intent.succeeded / transfer.created); every webhook is SIGNATURE-VERIFIED against the raw body (the
> account + Connect endpoints have SEPARATE secrets) and idempotent on `webhook_events(source,event.id)`;
> double-money is impossible by MULTIPLE layers — a deterministic sending-side Idempotency-Key on every Stripe
> write (retry → the original object), the `event.id` unique index (re-delivery → once), and — for the guest
> charge — `firstOrCreate(gateway_ref)`; **the owner payout CANNOT double-transfer** (a unique Idempotency-Key +
> a `lockForUpdate` row-lock on the payout, re-checked pending inside the lock — proven to transfer twice WITHOUT
> and once WITH) and a transfer only ever goes to ITS OWN owner's connected account; the ledgers stay
> append-only / correct-by-reversal / integer money (#17, #27), reconciling; the LIVE-KEY FLIP (test→live keys +
> the live webhook endpoints) is Subhan's documented go-live step, never done in the build**.
> · **#46 the OWNER ACCOUNT AREA (Profile & account + Security):** the owner self-profile
> (name/login-email/avatar/language/password/2FA/sessions/data-export) is PER-USER — resolved from `auth()`,
> NEVER a request id — so every owner-realm user incl. accountant/view-only SUB-USERS manages their OWN account
> (the old sub-user 404 is gone); the OWNER-PARTY/business details + payout links + team + DEACTIVATE are
> PRIMARY-OWNER-ONLY (`EnsurePrimaryOwner`, #42); a login-email change RE-VERIFIES the new address (a signed
> `temporarySignedRoute` mailed to it, `{hash}`=`sha1(pending_email)`) BEFORE it switches; DEACTIVATE is a
> REVERSIBLE, NON-DESTRUCTIVE `users.status` flag (login is active-only → a paused account can't sign in;
> bookings/statements/payouts STAY; PII erasure remains the #28 `anonymize()` path; reactivation restores);
> active sessions are the REAL `sessions` table (sign-out-others password-gated + rotates the remember-token);
> the account-protection checklist is DERIVED from true state (password set · 2FA on/off · unused recovery
> codes) — NEVER a fabricated score; all writes are controller POSTs (never `/livewire/update`).
>
> **Owner-page + platform cluster (added since the summary above was written):** · **#47** analytics depth (Batch B,
> no fabricated LY/pricing) · **#48** operator money-write idempotency (Batch C) · **#49–53** the SUPER ADMIN platform
> panel (isolated `/admin` realm · DB-backed plans + feature-gating · auth-signal capture + privacy · encrypted platform
> credentials · admin account discipline) · **#54** the owner Overview exact-match + real backends · **#55** the owner
> My Units + Add Unit exact-match + real backends · **#56** the owner **Bookings** exact-match (page + Booking-Summary
> PDF): the "Bookings over time" chart + "Monthly goal" ring are REAL (status counts + `OwnerLedgerAttribution` net + an
> owner-SET `owner_monthly_target`, never a fabricated goal); the page + CSV export read the ONE `OwnerBookingsReader`
> (can't drift; name-only, no contact — #28; the target write is a `$capActions` CONTROLLER route with a server-derived
> currency — #20/#30); the PDF shows REAL owner-scoped occupancy (`PerformanceMetrics::forOwnerUnits`, physical-unit-keyed),
> the guest NAME-only (`GuestName`), the real channel commission (`ota_cost`) and the real letterhead VAT — the OTA
> **brand** stays refused (unmodelled); Σ(rows)===gross to the cent is preserved.
>
> ⚠️ **#26–30 each closed a live hole that the fully-green suite never noticed** (#26 an owner reading every other
> owner's financials · #27 a €369 payment taking €1,107 in refunds · #29 a duplicate OTA webhook rewriting a
> completed stay · #30 any back-office role minting itself an Accountant). Every one was found by **reading**,
> never by a test failing. The guest-realm/reviews guards (#32–37) were all built mutation-proven from the start
> (each proven to FAIL without the guard). See META-LESSONS (b), (e), (i).

**Phase 1 core:**
1. **Single inventory write path** — ONLY `AssignmentService` inserts/mutates `reservation_units` /
   `availability.sold` (this INCLUDES OTA imports via `importBooking()` — never a parallel path).
2. **Two-level no-double-booking** — Level A (room-type counts) + Level B (physical-unit non-overlap)
   under `SELECT … FOR UPDATE` in one transaction; **deterministic lock order** (`room_type_id` +
   `check_in`) → deadlock-free. MySQL has no exclusion constraint — the guarantee lives in this service;
   the concurrency test is mandatory.
3. **One reservations table** — `source` distinguishes direct/OTA/walk-in/corporate/group; never parallel tables.
4. **OTA overbooking = accept-and-flag** (`sold` never exceeds `total`; excess modeled as `overbooked`
   line + `overbooking_conflicts`); **direct/public bookings REJECT when full** (asymmetry is deliberate).
5. **Advisory booking holds are NEVER counted in `sold`** — `booking_quotes` is a price/availability
   snapshot; real occupancy only at `book()` under the AssignmentService lock.
6. **ARI push is a pure post-commit consumer** — reads CURRENT state at execution (not enqueue-time),
   event-keyed idempotency (`ari_push:{channel}:{room_type}:{domain_event_id}`), never mutates `sold`,
   never emits an inventory event (no push→event→push loop).
7. **Append-only correct-by-reversal financial ledger** — posted `payments`/`invoices`/`folio_items`
   never edited/deleted; refunds/voids are NEW offsetting entries; deposit hold ≠ paid until captured.
   ⚠️ **#7 bounds EDITS, not SUMS** — see #27, which is a genuinely different guarantee. Conflating the two
   is exactly how a €369.00 payment once took three full refunds while every rule here held.
8. **Money = integer minor units + ISO currency**, via `App\Support\Money\Money` / `MoneyCast`. Never a float.
9. **PII** — `guests.email`/`phone` encrypted; GDPR erase = `anonymize()` (null PII, purge file bytes).
10. **Event direction** — `domain_events` emitted post-commit by the WRITE side only;
    `AvailabilityService::recompute` + all listeners are PURE consumers. **Availability recompute +
    `sold` mutation stay SYNCHRONOUS/inline — never `ShouldQueue`** (only the outbound ARI push is async).
11. **Idempotency = unique constraint + catch-the-race** — `webhook_events`, `reservations(channel_id,
    ota_reservation_id)`, `sync_jobs(idempotency_key)` are real DB uniques; app catches
    `UniqueConstraintViolationException` and resolves to the winner.
12. **Enforced morph map** — `Relation::enforceMorphMap` on; every polymorphic-capable model registered
    (AppServiceProvider) or Laravel throws.
13. **Tenant scoping** — every business model uses `BelongsToOrganization`; the guard THROWS with no
    context; explicit super-admin bypass only.
14. **ULID keys everywhere** except spatie's own tables (documented integer-PK exception).

**Owner Accounting (Phase 3 · Module 1) additions:**
15. **Attribution is derived + READ-ONLY** — `OwnerAttributionService` reads `folio_items` +
    `reservation_units`, NEVER mutates folios/reservations/inventory/`availability`; owner_share +
    operator_share reconciles to the folio room revenue **to the cent** (`Money::allocate` is
    sum-preserving — no minor unit created/lost; fuzz-proven).
16. **`owner_ledger` append-only + WHOLE-TRANSACTION correct-by-reversal** — a cancel/refund/modify
    reverses EVERY component of a booking together (mirror rows, `reverses_id`), never partial, never
    edits originals; nets to zero; DB-idempotent (`idempotency_key`).
17. **Owner statements = pure projection + period-locked** — net == Σ component lines == ledger net;
    draft reproducible; finalize STAMPS `owner_ledger.statement_id` + locks (immutable; regenerate
    throws); post-lock adjustments carry forward onto the next statement as labeled prior-period
    adjustments — **gap-free (every ledger line claimed by exactly one statement)**; cross-currency uses
    operator-supplied `fx_rate`/`fx_source` (no silent float); payouts finalized-only + positive-net,
    negative net carries forward (never a reverse-charge), append-only lifecycle (`supersedes_id`).
18. **OWNER ISOLATION — a row-level scope WITHIN a tenant, treated like tenant isolation** —
    `OwnerContext` + `OwnerScope` (via `BelongsToOwner` on Unit/OwnerStatement/OwnerStatementLine/
    Payout/OwnerLedgerEntry/OwnerExpense/**MessageThread**). **FAILS CLOSED** (active + empty set → nothing,
    never all owners); inactive off-portal (operator/CLI/tests see all, subject to tenant scope); activated
    only by `SetOwnerScopeForPortal`, **deactivated on `terminate`** (no leak into operator requests);
    **composes with tenant scope** (both boundaries hold; cross-org 404 both directions).
    - **⚠️ THE TRAIT IS THE BOUNDARY — WITHOUT THE COLUMN THE SCOPE DOES NOT FAIL CLOSED, IT NEVER RUNS**
      (proven in Wave 3, and the reason `message_threads.owner_id` landed BEFORE the owner-inbox screen).
      `OwnerScope` keys on `owner_id`; a model without that column simply cannot carry `BelongsToOwner`, so
      an owner-facing read over it is **tenant-scoped only** — every other owner's rows, visible. That is
      invariant #26's shape wearing a different hat. **Before building any owner-facing screen over a model,
      check it has the column and the trait; the scope will not warn you.**
    - **THE BOUNDARY FALLS OUT OF THE SCOPE — never write the predicate yourself.** On `MessageThread` an
      operator↔guest thread has `owner_id = NULL`, so `owner_id IN (their ids)` excludes it automatically:
      the owner portal cannot see a guest conversation without anyone coding that rule. Adding a manual
      `->where('owner_id', …)` alongside would imply the scope were optional.
    - Proven SYMMETRICALLY (both owners, both directions — a scope that returned everything would pass a
      one-way test) and MUTATION-PROVEN: removing the trait fails 4 tests, including the guest-thread leak.
      (`OwnerPortalIsolationTest`, `OwnerInboxIsolationTest`.)

**Design pass (presentation-only — these govern EVERY styled screen, all ~31 of them):**
19. **Design-pass honesty rule** — every styled element is exactly one of: **REAL** (wired to a certified
    read/service), **DERIVED** (computed from real figures only), **DEFERRED** (visually stubbed + "not
    yet available"), or **NO-BACKEND** (disabled "Soon"). NEVER a fabricated figure/status/action/photo/
    IBAN/date. Money reconciles to the cent against its source; statuses/labels are keyed to the **real
    enums only**; warm colours (no green/red). **Backends/schema/guarantees are UNCHANGED by any screen —
    presentation only.** Enforced per-screen by a render/isolation test. (Locked `docs/DECISIONS.md`
    2026-07-11; extended to operator + public.)
20. **Scoped-render + guarded-mutation discipline (isolation holds on every screen)** — a scoped model is
    resolved in `render()` (or the controller) via a tenant/owner/property-scoped `findOrFail` from a
    **string** id, NEVER a route-model-bound prop (RMB resolves during `SubstituteBindings`, before the
    scope middleware → leak/guard-throw). Scope-dependent **mutations** never ride a Livewire wire-action
    (it re-renders via `/livewire/update`, bypassing the route middleware → scope leak); they use
    **POST→controller→existing-service-action→PRG** (operator; tenant-scoped, cross-org id 404) or a
    **session-CSRF POST** (public checkout). The public checkout write still goes through the SINGLE
    guarded path (`AssignmentService`, source=direct — rejects when full, never overbooks), never a
    parallel one; its POST is CSRF-protected (the machine JSON API stays exempt by path
    `book/*/quote`,`book/*/book`). Guest PII (encrypted email/phone) is never rendered; guest NAMES are
    shown only on staff surfaces.

**Auth (Phase A + B — these govern login + the owner invite flow):**
21. **Auth security stack** — sign-in is **active-only** (`Auth::attempt(['status'=>'active'])`, so an
    `invited`/`disabled` login can't authenticate); auth endpoints (login + `/invite/*`) are
    **rate-limited** and return **generic, non-enumerating** errors (no "which email/invite exists"
    leak — a spent invite token yields the byte-identical page as a garbage one); **passwords are hashed**
    (`hashed` cast, never plaintext); **sessions regenerate** on login and on invite-activation.
    Invite tokens are **high-entropy, stored ONLY as `sha256(plain)` (never plaintext), single-use**
    (status `pending→accepted`), **7-day expiring**, and revocable. The operator invite write is
    **tenant-scoped** (POST→controller→`OwnerInviteService`→PRG; a foreign unit id is dropped, never
    assigned cross-org). Three roles share the machinery: **owner** (owner link → `/owner`) + the two
    **staff** roles **Hotel Manager / Accountant** (Membership + a **real, validated** spatie catalog role
    → `/app`; no owner/agreement/units). Staff role assignment is **fail-safe** — the slug must map to a
    catalog role AND be provisioned for the org (validated at invite AND accept; an unknown role 422s,
    never a phantom/cross-org grant). On activation an owner's **owner-scope binds** (invariant #18 still
    fails closed for a non-linked user); staff land in `/app` with their role's permissions. (Locked
    `docs/DECISIONS.md` 2026-07-13.)

**Maintenance module:**
22. **Out-of-service is a unit-level exclusion, NOT a booking-core change** — a maintenance OOS ticket
    transitions the unit's `condition_status` and emits `unit.condition_changed`; the EXISTING availability
    recompute already counts `out_of_service` into `out_of_order` → the unit is not offered. `AvailabilityService`
    and the `AssignmentService` locking core are **UNTOUCHED**; `sold` is never touched (no-double-booking +
    single-write-path hold). A unit returns to service only when no other active OOS ticket holds it; an
    existing booking is **NEVER auto-cancelled** (flagged for manual move); an over-committed room-type shows
    a negative raw available (honest) while the clamped `available()` stays 0 (no oversell). **Maintenance
    cost flows to the owner ONLY through the certified `OwnerExpenseService`** (source = the ticket) →
    `owner_ledger` → statement — never a direct ledger write; operator-owned units create no expense;
    corrections reverse append-only; period-lock respected. **The styled work order SURFACES both
    integrations** — the OOS callout with the real manual-move count, and the cost card read from the real
    `owner_expense` — without changing any guarantee (presentation only). (Locked `docs/DECISIONS.md`
    2026-07-14, two entries.)

**Unified Inbox module:**
23. **Inbound is exactly-once + threads GROUP** — a duplicate provider delivery (same `external_message_id`)
    collapses to one `messages` row via `createOrFirst` on `unique(org, channel, external_message_id)` (proven
    under true OS-level concurrency); messages group into the right thread by the `unique(org, channel,
    external_thread_ref)` anchor (or a non-PII participant hash for a stranger) — never a thread-per-message.
    `postInbound` runs createOrFirst in **autocommit** (not a long transaction) so the losing INSERT's
    re-SELECT sees the committed winner — the same discipline as the Channex webhook leg.
24. **Real channels are honest-fail, never faked** — `ManualChannel` is the only working channel; the
    email/WhatsApp/OTA adapters are config-gated `DeferredChannel` skeletons whose `send()`/`normalizeInbound()`
    **throw "not configured"**, and a reply on a deferred channel leaves an **honest `failed` message** + a
    clean flash (never a fake send). Guest linking reuses the SINGLE `GuestMatcher` (no parallel matcher); the
    external-ref value is a **hash** (non-PII) while the address stays encrypted on the guest.
25. **Inbox PII discipline** — `participant_identity` is the **encrypted routing anchor** (never rendered, never
    a competing copy of a linked guest's PII); the operator surface shows the guest NAME + conversation only.
    `Guest::anonymize()` **scrubs the guest's message bodies + sender labels + participant identity/subject**,
    keeping the skeleton for audit (never soft-delete-and-keep-PII). (Locked `docs/DECISIONS.md` 2026-07-14.)

**The operator/owner boundary (the `/app` authorization gate — 2026-07-16):**
26. **An owner-role user is NOT operator-capable, and is refused `/app` at BOTH the route AND the role
    level.** Two independent layers; each is tested in isolation so a regression in either is caught alone:
    - **ROUTE (layer 1)** — `App\Http\Middleware\EnsureBackOfficeAccess` on the **`/app` GROUP** (never
      per-route, so a route added later cannot forget it; a test asserts EVERY `app/` route carries it).
      It requires a role from the `config('authorization.back_office_roles')` **ALLOW-LIST**, checked
      **team-scoped** — so no role, a portal-only role, or a role held in a DIFFERENT org all **fail
      closed**, and the gate composes with tenant isolation rather than bypassing it. An owner-linked user
      is redirected to `/owner`; everyone else gets a bare 403. Neither response renders operator data.
      **Ordering is load-bearing:** it MUST run after `SetTenantForWebRequest` sets spatie's team id, or
      `hasAnyRole()` matches nothing and EVERY operator is locked out — asserted by a test on the resolved
      middleware chain.
    - **ROLE (layer 2)** — the owner-invite path assigns the owner-only **`External Owner`**
      (`owner_portal.view`, nothing else), so the allow-list has **nothing to admit** even on a route that
      forgets the middleware. Asserted with NO HTTP at all: an activated owner's
      `hasAnyRole(back_office_roles)` is false.
    - **⚠️ `Property Owner` IS a back-office role** — it is the ORG ADMIN (`['*']`, all 44 permissions), NOT
      the unit owner (that is `External Owner`). The names are a trap; `DemoSeeder` fell into it and granted
      a unit owner `payments.refund`. **The demo seeds the production model** (demo-owner → `External
      Owner`, demo-staff → `Hotel Manager`) and a test locks it — otherwise the demo journeys would not
      exercise the boundary they appear to.
    - **The owner's Membership STAYS and is not the bug** — `SetTenantForWebRequest` resolves the org ONLY
      from active Memberships, and `SetOwnerScopeForPortal`'s `Owner::query()` runs under the tenant guard,
      which THROWS with no context: remove it and `/owner` 500s on every route. Nothing in `OwnerContext`/
      `OwnerScope` reads Membership (owner identity is purely `owners.user_id`). **A Membership is NOT
      operator access** — assuming it was IS what created the hole.
    - **Owner-role assignment is deliberately non-throwing** when the role is unprovisioned (unlike
      `assignStaffRole`, which throws): a staff member without their role is broken, but an owner's portal
      access comes from the owner link, so an owner with no role works identically and is equally
      non-operator-capable. It logs and proceeds rather than blocking a legitimate activation. A role
      missing from the CATALOG is a code bug and still throws.
    (Locked `docs/DECISIONS.md` 2026-07-16; proven by `BackOfficeAccessTest` + `OwnerNotOperatorCapableTest`,
    the former mutation-verified — removing the gate fails 5 of its tests.)

**The refund bound (money — 2026-07-16):**
27. **CUMULATIVE refunds against a payment may EQUAL but never EXCEED that payment's original amount.**
    **This is NOT invariant #7 and was not implied by it.** #7 (append-only, correct-by-reversal) says a
    refund is a NEW offsetting row and the original is never edited — it bounds **edits**. Nothing bounded
    the **sum**. Both held simultaneously while a €369.00 payment took **three full refunds = €1,107.00**
    (every refund a legitimate append-only row, original untouched). #27 is the missing bound.
    - **Enforced in `FolioService::refundPayment()` — the single write path, so EVERY caller is covered.**
      Never re-implement it per controller. `remaining = original − Σ prior succeeded refunds`; reject
      `amount > remaining` with `App\Exceptions\Folio\RefundExceedsPaymentException` — a clean domain
      rejection, **never a silent clamp or partial application**. It subsumes the old per-call check (no
      priors ⇒ remaining IS the original), so partial refunds up to the original still work exactly as before.
    - **ORDERING IS LOAD-BEARING:** the guard runs **before** `$this->gateway->refund()`. The gateway is
      invoked before any row is written, so a guard placed after it would move REAL money and only then
      reject. Do not reorder.
    - **The sum** = `payments WHERE type='refund' AND status='succeeded' AND
      metadata->refunds_payment_id = <id> AND currency = <original.currency>` — the link is a **JSON path**
      (there is no FK column). Only `succeeded` counts (a failed refund moved no money and must not consume
      headroom). **`withTrashed()`** — a soft-deleted refund STILL bounds; posted financial rows should never
      be deleted (#7), so this is belt-and-braces: a delete must never hand back refundable headroom.
    - **Same-currency only** — minor units are not comparable across currencies, so the bound and its sum
      would be meaningless. (Previously unchecked: a 100 USD refund against a 100 EUR payment PASSED.)
    - **Non-positive rejected** — a €0.00 refund passes any `> remaining` test and posts a junk row into a
      ledger that can never be tidied away.
    - **The UI must not invite the rejection:** `ReservationDetail` marks a payment refundable only while
      `remainingRefundableMinor() > 0` (and exposes the remainder). The button persisting after a full
      refund is what made the bug reachable in the shipped product. The service stays the authority.
    (Locked `docs/DECISIONS.md` 2026-07-16; proven by `RefundGuardTest` — written FIRST and run against the
    unguarded code: **7 of 10 failed pre-fix, 10/10 pass post-fix, and removing the guard fails 4 again**.)

**The guest-PII perimeter (Wave 2 — 2026-07-17):**
28. **Guest contact PII (`guests.email`/`phone`) is rendered ON THE GUESTS CRM ONLY — the list and the
    profile — and NOWHERE else.** Front desk, reservations list and inbox show the guest NAME only. The CRM
    is where an operator legitimately needs to reach a guest; a booking row is not.
    - **The perimeter is ENFORCED BY A TEST, not by a docblock.** `OperatorGuestsListTest` renders the front
      desk, the reservations list and the inbox and asserts each shows the NAME and NOT the contact — so a
      passing run means "name yes, contact no", never "the page was empty". **Mutation-proven:** adding an
      email to the reservations list fails it.
    - **No public Livewire prop may hold a guest.** A public prop is serialized into the page payload and
      round-trips over `/livewire/update`; both CRM screens hold a plain string id and resolve in `render()`,
      so decrypted contact reaches the HTML and stops there. (This is the same `$xxxId` rule as invariant #20,
      for a second reason.)
    - **⚠️ THE AUDIT TRAIL MUST NOT BECOME A SECOND, UNENCRYPTED PII STORE.** `email`/`phone` are encrypted
      casts; `audit_events.before`/`after` and `domain_events.payload` are plain JSON that
      `Guest::anonymize()` does not clear. **`GuestService` records WHICH FIELDS changed, never their
      values** — enough to answer "who touched this guest's email on Tuesday?", never enough to reconstruct
      it. Writing the old/new address there would re-retain the exact PII an erase destroys and silently
      undo encryption-at-rest. `Guest::anonymize()` already stated this rule for itself ("never put the
      erased PII into the audit before/after"); **it holds for every write, not just the erase.**
    - **Every PII store must be erasable.** `anonymize()` clears the guest's columns, their ID documents,
      their inbox message bodies/participant identity AND (since Wave 2 shipped the first note writer) their
      **note bodies** — a staff note about an identified person is that person's personal data. When a new
      free-text store about a guest is added, it MUST be added to `anonymize()` in the same change.
    - **AND IT IS NOW REACHABLE (2026-07-17).** Until then `anonymize()` was called from **NOWHERE** — the
      erase was correct, thorough and tested, and no operator could run it, so a data-subject request could
      not actually be served. `POST /app/guests/{guest}/anonymize` is the trigger: gated on
      **`can:guests.erase`** (Property Owner / Group Admin only — running the house does not include erasing a
      person), **server-verified typed confirmation** (a client `confirm()` is bypassable by anyone who can
      POST, and an irreversible action's intent check must survive a bookmarked request), and it **calls the
      existing method** rather than re-implementing redaction — a second copy would drift and quietly miss a
      store, which for an erasure means keeping data you said you destroyed. The erased profile still renders,
      and its financial history still reconciles.
    - **Search cannot see it, and the page says so.** Encrypted TEXT is never indexed by value, so the CRM
      searches names only and states that in the footer rather than letting an operator conclude the data is
      missing. (The blind-index is a standing deferral.)
    (Locked `docs/DECISIONS.md` 2026-07-17; proven by `OperatorGuestsListTest` + `OperatorGuestProfileTest` +
    `GuestServiceTest`.)

**The termination bound (2026-07-17):**
29. **A TERMINAL RESERVATION CANNOT BE TERMINATED AGAIN.** Terminal = `AssignmentService::TERMINAL_STATUSES`
    = `['cancelled', 'no_show', 'checked_out']` — **the ONE definition**, read by every layer.
    - **The guard is INSIDE the private `terminate()`**, before the transaction (a rejected call rolls nothing
      back and touches no inventory). Both public entries — `cancelReservation()` and `markNoShow()` — funnel
      through it, so **one check covers every caller that exists or ever will**. That is the whole point.
    - **⚠️ WHY THE SERVICE AND NOT THE CALLERS: there were already TWO callers and they DISAGREED.** The Wave 2
      note claimed the operator controller was the only one; `BookingImportService::cancel()` — the OTA/Channex
      **inbound webhook** path, i.e. a network-triggered, RETRYING caller — had always reached it too, with its
      own guard checking only `['cancelled','no_show']`. So an OTA cancelling a stay the guest had **already
      completed** (`checked_out`) went straight through and stamped `cancelled_at` onto it, while the
      controller refused exactly that. Two rules, one concept.
    - **A SECOND TERMINATION IS A REWRITE, NOT AN APPEND** — which is exactly why #7 never caught it. It
      overwrites `cancelled_at`/`cancellation_reason` (destroying the provenance the detail screen renders from
      those very columns), appends a junk `cancelled → cancelled` row to an append-only trail, and re-emits
      `reservation.cancelled` to every consumer. **Same blind spot, same shape, as #27** bounding the SUM of
      refunds where #7 only bounded their edits.
    - **The OTA path NO-OPS, it does not throw** — it reads `TERMINAL_STATUSES` and returns before ever asking.
      On a retrying webhook, letting a terminal booking reach the guarded service would turn an ordinary
      duplicate delivery into a **poison message**. Idempotent at the edge, authoritative in the service.
    - **The controller keeps its allow-list** (defense-in-depth, the #26 shape), and a test asserts
      `CANCELLABLE` is the exact **complement** of `TERMINAL_STATUSES` over the real 7-value enum — so the UI
      can never offer what the service will refuse.
    (Locked `docs/DECISIONS.md` 2026-07-17; proven by `TerminateGuardTest` — written FIRST: **5 failed + 1
    error pre-guard, 13/13 after, and neutering the guard fails 5 again**. The bug was demonstrated on real
    demo data before the fix.)

**Fine-grained authorization — the catalog is CONSULTED (2026-07-17):**
30. **THE SENSITIVE WRITE ROUTES ENFORCE THEIR CATALOG PERMISSION.** Until now the 45-permission catalog was
    **decorative**: not one permission was checked anywhere, so the coarse #26 door admitted all 11
    back-office roles EQUALLY and `payments.refund` — deliberately withheld from Hotel Manager and Front Desk
    — was effectively held by everyone who could reach `/app`. **A catalog that describes a policy the app
    does not enforce is worse than none: it reads like protection.**
    - **ENFORCED** via `can:<permission>` **route middleware** on the `/app` group:
      `owners.invite → users.manage` · `reservations.refund` **+ its GET screen** `→ payments.refund` ·
      `reservations.payment → payments.record` · `payouts.create|pay → payouts.manage` ·
      `reservations.cancel|no-show → reservations.cancel` · `guests.anonymize → guests.erase`.
    - **🔑 THE HEADLINE WAS PRIVILEGE ESCALATION, NOT THE REFUND.** `POST /app/owners/invite` assigns a REAL
      spatie role. Proven end-to-end before the fix: **Housekeeping → invite self as `accountant` → invite
      minted → Accountant holds `payments.refund`. Two requests, no operator involved.** Gating the refund
      route alone would have been **cosmetic** — the attacker just mints an Accountant. Never gate a money
      route without gating the role-granting route in the same change.
    - **`payouts.manage` DID NOT EXIST** and was added (catalog 44 → **45**). The payout run had always moved
      real money with no permission to check against. Granted to Property Owner\*/Group Admin/Accountant,
      mirroring `payments.refund`; **not** Hotel Manager — remitting an owner is an accounting act.
    - **⚠️ AUTHORIZATION GOES ON THE ROUTE, NOT IN THE SERVICE — deliberately unlike #27/#29.** A permission is
      a REQUEST-level fact about an ACTOR; those are DATA-level bounds about a ROW. `FolioService::refundPayment()`
      is called by tests and jobs with **no authenticated user**; a `can()` there would be meaningless or fatal.
      The bound guards every caller; the permission guards every actor. **Do not "unify" them.**
    - **THE UI MUST NOT INVITE A 403** (#27's principle, applied to authorization): every control on a gated
      route is `@can`-gated — the refund button + partial-refund link, Record-a-payment, cancel/no-show, every
      payout control, the whole invite modal (not just the button that opens it), and the erase card.
    - **ORDERING IS SOUND AND VERIFIED:** `Authorize` runs **last** — after #26 and after
      `SetTenantForWebRequest` sets the spatie team — and is re-applied on Livewire update requests.
      ⚠️ **A null team fails SILENTLY to `false`**, so nothing may be placed above that middleware.
    - **#26 STILL RUNS FIRST.** This is a fine-grained layer ON TOP of the coarse door, never a replacement —
      an owner is bounced before any permission is consulted (asserted).
    - **FIX FIXTURES, NEVER THE CHECK** (the #26 lesson): 28 tests across 7 files moved to
      correctly-permissioned roles. ⚠️ **Cross-org isolation tests must keep a PERMITTED actor**, or `can:`
      403s before the scoped `findOrFail` and the `assertNotFound()` silently stops proving tenancy.
    (Locked `docs/DECISIONS.md` 2026-07-17; proven by `PermissionEnforcementTest` + `GuestErasureTest` —
    written FIRST: **6 and 8 failed respectively; 10/10 and 9/9 after; stripping every `can:` fails 5 again**.)
31. **THE 2FA-PENDING STATE GRANTS NO AUTHENTICATED ACCESS — BY CONSTRUCTION.** When a user with 2FA gives the
    correct password, they are NOT logged in: `LoginRequest` uses `Auth::validate` (verify, no session) and
    `LoginController` holds them in a **pending** state — a plain session key `login.2fa.user_id`, **NEVER the
    auth guard** — then redirects to `/two-factor-challenge`. Because the marker is not the guard, the pending
    user is not `Auth::check()`-true, so `/app` + `/owner` (behind `auth`) **reject them with no new
    authorization code** — the existing middleware does it. `RequireTwoFactorPending` only fences the challenge
    page itself. Only a valid **TOTP or single-use recovery code** runs `Auth::login` (+ session `regenerate`
    for fixation, + route by role); invalid codes are **rate-limited** per pending-user+IP. Recovery codes are
    **single-use** (`used_at` stamped, `hash_equals`); the secret + codes are **encrypted at rest** (the secret
    column was plaintext before 2FA — now fixed). A **non-2FA** login is unchanged (validate+login ≡ the old
    `attempt`). ⚠️ **Do not turn the pending marker into an authenticated session or a guard** — that would hand
    a password-only attacker everything the second factor is meant to stop. (Built `docs/DECISIONS.md`
    2026-07-18; enrollment `031716a`, challenge `aca017c`; proven by `TwoFactorEnrollmentTest` [12] +
    `TwoFactorChallengeTest` [8], the latter mutation-proving that a correct password alone reaches nothing.)

**Guest Portal (Phase 4b/4c) — the guest realm + its guards:**
32. **GUEST-INITIATED CHANGES ARE OPERATOR-CONFIRMED REQUESTS.** A guest changing dates / adding an extra /
    cancelling touches inventory / the folio / money, so it is only ever a `pending` `guest_change_requests` row
    the operator confirms. The guest NEVER performs the real inventory/folio/refund write and can't self-approve
    (the confirm route is `/app`, refused to the guest realm). Same shape as the operator/owner boundary: the
    guest may ask, the operator acts. (Phase 4b · SG3.)
33. **DOOR-CODE / ARRIVAL-INFO ISOLATION + TIMING.** The arrival pack — ESPECIALLY the door code — shows ONLY for
    the authed guest's OWN booking (resolved through their own relation, never a request id) AND only within the
    arrival window (`ArrivalPack`, `LEAD_DAYS=2` before check-in through check-out; withheld before it, and for a
    cancelled booking). `door_code`/`wifi_password` are encrypted at rest; an unset field is honest, a code is
    never fabricated. Leaking a code to the wrong guest — or months early — is a physical-security issue, so both
    guards apply. (Phase 4b · SG4.)
34. **THE GUEST WALLET IS AN APPEND-ONLY LEDGER.** `guest_credit_ledger` mirrors `owner_ledger` — `direction`
    credit/debit, positive `amount_minor`, `reverses_id` self-FK (correct-by-reversal), `expires_at`. There is
    DELIBERATELY NO balance column: the spendable balance is DERIVED (Σ live credits − Σ debits) in
    `GuestWalletService`. It can NEVER overdraw — a debit beyond the derived balance is refused BEFORE any write
    (`WalletOverdrawException`), and expired credit doesn't count. Integer minor units (#8). Apply-to-booking is a
    real debit + a folio credit via the additive `FolioService::applyWalletCredit()` (`posted_by` null — a guest
    request's default guard is the guest, so it never reads `Auth::id()`). (Phase 4c · SG2.)
35. **GUEST INBOX ISOLATION.** A guest sees ONLY their own thread(s) (resolved through `guest_id`, never RMB) —
    never another guest's, never an owner↔operator thread. A guest-side read moves only `guest_last_read_at`,
    never the operator's `unread_count` (the same trap the owner-inbox extension avoided). The guest side EXTENDS
    the one Unified Inbox — `InboxService::postGuestMessage`/`markReadByGuest` mirror the owner methods; no
    reimplementation. (Phase 4c · SG3.)

**Reviews (Phase 5) — the module's two guarantees:**
36. **REVIEWS TIE TO REAL COMPLETED STAYS.** A guest reviews only their OWN completed (checked-out/PAST) booking —
    never a future/cancelled/foreign/not-their stay — and at most once per stay (`reviews.reservation_id` unique).
    The review FREEZES the physical `unit_id`/`room_type_id` at review time (so the owner + storefront surfaces
    don't drift on live re-resolution). Owner isolation: an owner sees reviews for THEIR OWN units only (resolved
    through the owner-scoped `OwnerPortalService::units()`). (Phase 5.)
37. **REVIEW MODERATION + RATING ARE REAL.** A submitted review is `pending` (never auto-public); only a
    published+public review is publicly visible (a pending/rejected/private one never is; a **private** review can
    NEVER be published — the guest's choice is binding). Every rating is DERIVED from published+public reviews via
    the ONE shared `App\Support\Reviews\ReviewRating` (`publicScope()` = the single source of "publicly visible") —
    never a stored/fabricated number; a subject with none shows "No reviews yet". (Phase 5.)

38. **MULTI-PROPERTY: OPERATIONS ACT ON THE SELECTED PROPERTY, NEVER AN ARBITRARY `->first()`.** An org runs N
    properties; before Phase 6a ~10 sites resolved "the property" as `Property::query()->orderBy('name')->first()`,
    which silently returns an ARBITRARY property once there is more than one (a wrong-property read, or a wrong-property
    WRITE — a booking in the wrong property). Every property-scoped read/write now resolves through
    `App\Support\Property\PropertyContext` (the per-user session-backed current property), set by
    `SetCurrentPropertyForWebRequest` on the `/app` group and chosen with the topbar switcher.
    - **A WRITE targets a CONCRETE property, never "all"** — `PropertyContext::id()` always yields a single property
      (the default when "all" is selected); "all properties" (`readIds()`) is an explicit ROLL-UP honoured by aggregate
      READ surfaces only (Dashboard/Portfolio/the Tier-B list screens).
    - **PROPERTY ACCESS = the `property_user` pivot-or-all allow-list.** A user with pivot rows is restricted to those
      properties; a user with NO rows gets the whole org (`User::accessiblePropertyIds()`; empty ⇒ full org, so no
      existing user is locked out and single-property is unregressed). Honoured on BOTH the switcher and the write path
      (a pivoted user can't switch to, or book/create into, an unassigned property).
    - **Livewire boundary:** a wire-action re-render hits `/livewire/update`, OUTSIDE the `/app` group, so the context
      is INACTIVE there — operator screens capture the resolved ids at `mount()` and hold them as **`#[Locked]`** props
      (`App\Livewire\Concerns\ResolvesCurrentProperty`); Locked stops a client smuggling a non-assigned property id
      (cross-property access WITHIN an org is not caught by tenant scope). Same family as invariant #20.
    - **A CREATED property is ORG-SCOPED** — `PropertyController` (gated `properties.manage`, #30) creates through
      `BelongsToOrganization` (organization_id auto-filled, never cross-org) and it enters the selector. The
      **grep-clean** proof (no property-resolution `->first()` remains) is the sweep's completeness guarantee — a missed
      site is a wrong-property bug. Mutation-proven both ways. (Phase 6a.)

39. **ANALYTICS ARE REAL AGGREGATES — NEVER FABRICATED.** Every analytics figure is derived through the ONE shared
    `App\Support\Analytics\PerformanceMetrics` + `MetricSet` — occupancy/ADR/RevPAR/room revenue on a stated
    stay-night basis (RN = in-range nights of active `reservation_units`, reconciling to `availability.sold`; RR =
    `price_minor` pro-rated, reconciling to the folio room line; AN = `availability.total` for a property scope, or
    unit-count × days for an owner scope). **The identity RevPAR = ADR × occupancy holds EXACTLY** (same RN). Reports
    revenue is Σ real `folio_items` (reconciles to `FolioTotals`); operator costs show only what's real (maintenance),
    the rest "Not tracked yet"; owner **earnings reconcile to the owner ledger** via `OwnerLedgerAttribution` (the
    statements' figure). **The prototype's invented multi-year history + generated narrative are REFUSED** — never
    rendered; a real-derived observation ("busiest night is Monday") is fine, an invented one is not; a thin/empty
    period shows an honest empty state, never a fake curve. Money never sums across currencies (#17). **Operator
    analytics gated `reports.view`** (NOT Front Desk); **owner analytics owner-scoped** (their units only — resolved
    through `OwnerPortalService::units()` + owner-scoped `OwnerLedgerEntry`, a non-linked user fails closed).
    Reconciliation + owner isolation mutation-proven; the fabrication-absence tested. **Deferred honestly:** lead time
    + booking pace + channel-attribution depth (derivable but too thin in real data). (Phase 6b.)

40. **TENANT PROVISIONING IS ATOMIC + PROVISIONS ROLES (the chicken-and-egg, solved).** A brand-new operator org is
    created ONLY through `App\Services\TenantProvisioningService`, in ONE `DB::transaction` (all-or-nothing): the
    Organization (via `TenantContext::runAsSuperAdmin` — the pre-tenant escape, since `Organization` is the tenant
    boundary, not a `BelongsToOrganization` model) + its 13 provisioned roles (`OrganizationRoleProvisioner::provision`
    — org-creation now provisions roles, so the org is not dead-on-arrival under #26 — **RBAC DEFECT #1 FIXED**) + the
    founder `User` (hashed, active) + an active `Membership` + `assignRole('Property Owner')` on the org's spatie team
    (the founder holds a legitimate, ASSIGNABLE org-admin role — never the global `Super Admin`, a cross-tenant
    platform role a founder must never hold — **RBAC DEFECT #2 resolved for self-provision**) + the first `Property`. A
    mid-provision failure rolls back the ENTIRE org (no orphan org/user/roles/membership/property); a provisioned org
    is tenant-isolated both directions; the founder belongs only to their org. The public `/signup` surface is
    rate-limited, hardened (hashed password, session regenerated, no email-verification gate — the founder matches
    existing operators), and handles enumeration neutrally (org existence never leaks; a duplicate email fails
    non-committally). Mutation-proven. (Phase 7a.)

41. **SUBSCRIPTION BILLING — the trial is REAL, access is gated on the real state, the charge is ACCOUNT-GATED.**
    Vyrobel bills the operator on the free-trial model. A new org (7a) starts a real `trialing` subscription
    (`trial_ends = created + trial_days`; days DERIVED by `App\Support\Billing\SubscriptionStatus`, never a stored
    countdown), created atomically inside the 7a provisioning transaction. Plans/tiers live in config/billing.php
    with CONFIGURABLE PLACEHOLDER prices (Subhan sets real ones — never fabricated as final), materialised into the
    real `plans`/`plan_features` tables. **ACCESS GATE** (`EnsureSubscriptionActive` on `/app`): a LAPSED org
    (expired trial / canceled) → the Paywall; but the gate bites ONLY after the trial (in-trial/active = full — a
    founder is NEVER locked out mid-onboarding/mid-trial), FAILS OPEN on a missing subscription (a missing billing
    record must never paywall — the opposite of the fail-CLOSED security posture), and always lets the billing
    routes through (so a lapsed org can reach the Paywall). **FEATURE GATE** (`Organization::planAllows`): `channels`
    is Pro-only (Essential refused, Pro/Pro-trial granted), surfaced as a "Pro" nav lock. **🪤 THE LIVE CHARGE IS
    ACCOUNT-GATED** — the subscribe action takes no money, stores no card, and NEVER flips an org to `active`
    (`status='active'` + `stripe_subscription_id` are only ever reached through live Stripe Billing, deferred like
    the guest card charge + owner payout rail). Company/VAT are the org's own business identity (not card data).
    Billing is tenant-scoped + `billing.manage`-gated (founder/Property Owner, not Front Desk). Mutation-proven.
    (Phase 7b.)

42. **OWNER SUB-TENANCY (Owner Team) — nested isolation, COMPOSED not invented.** A sub-user ⊆ their owner ⊆ their
    org: the nesting COMPOSES the existing `OwnerScope` (owner boundary) + tenant scope (org boundary) — NO new scope.
    The 3 fixed owner-team roles (co-owner / accountant / view-only) gate the portal by a capability MATRIX
    (`EnsureOwnerCapability` route gate + `@ownercan` UI, both reading the ONE `OwnerTeamRole` matrix — not a privilege
    chain); the **payout account / IBAN is co-owner-ONLY** (money-security, #28). Team management is held by NO role —
    gated on `isPrimaryOwner()` (`EnsurePrimaryOwner`), so `canAssign()` refuses every sub-user and self-escalation is
    STRUCTURALLY impossible. The owner-realm invite reuses the hardened token discipline (sha256, single-use, expiring,
    no-enumeration) and is OWNER-SCOPED (can only land a sub-user under the invite's own `owner_id`); a removed
    sub-user's orphaned login is disabled. The capability gate COMPOSES with — never masks — the owner-scope
    fail-closed (it only 403s users WITH owner access). (Phase 7c.)
43. **TEAM SCHEDULE (rostering) — tenant + property scoped over REAL staff.** A shift lands on the SELECTED property
    (`PropertyContext::id()`, never `->first()`, never cross-property/org) and only on a real staff member
    (`StaffRoster` = active `Membership` ∩ a back-office role; owners/guests/fabricated names excluded). Managing the
    roster is manager-gated (`schedule.manage`, NOT Front Desk); viewing is broad (`schedule.view`). Time-off is a
    request→approval SEPARATION (a staff member requests their OWN; only a manager approves) and **self-approval is
    impossible** (`LeaveService` throws if actor == subject). A shift-on-approved-leave is a REAL computed conflict
    (assign-on-leave refused; a pre-existing clash flagged), never fabricated. The schedule PDF renders from the SAME
    `RosterWeek` builder as the grid, so it RECONCILES and is property-scoped. Shift-swap / labor-cost / coverage-AI
    are OUT of scope (deferred honestly, never faked).
44. **PRODUCTION EMAIL (Phase 8) — a real email NEVER sends from a non-production environment.** `MailSafetyGuard`
    refuses a real-sending transport outside `production` (downgrades to `log` at boot), so even `MAIL_MAILER=postmark`
    in a dev/CI `.env` can't reach a real person; the suite runs on a non-sending (`array`) transport. The Phase-3
    boundary HOLDS with the real transport — a provider send failure is caught + logged (never the address, #28) and
    NEVER rolls back the booking/notification (best-effort). Mail goes only to the correct OWN recipient. Provider
    secrets live in ENV only. The DNS records + the one controlled real send are the founder's cutover.
45. **LIVE STRIPE (money paths, test-mode-first) — built so it CANNOT charge wrong.** A real charge can NEVER happen
    outside `production` — a live `sk_live_` key is refused by `StripeSafetyGuard` (the fake client is bound in CI).
    The card NEVER touches the server (hosted Checkout — PCI SAQ A, no raw PAN). Every money path FULFILS ON THE
    WEBHOOK, never the redirect; every webhook is SIGNATURE-VERIFIED against the raw body (the account + Connect
    endpoints have SEPARATE secrets) and idempotent on `webhook_events(source,event.id)`. Double-money is impossible by
    MULTIPLE layers (a deterministic sending-side Idempotency-Key + the `event.id` unique index + `firstOrCreate`);
    **the owner payout CANNOT double-transfer** (unique Idempotency-Key + a `lockForUpdate` row-lock, re-checked
    pending inside the lock — proven twice WITHOUT and once WITH) and a transfer only ever goes to ITS OWN owner's
    connected account. The ledgers stay append-only / correct-by-reversal / integer money (#17, #27), reconciling. The
    LIVE-KEY FLIP is the founder's documented go-live step, never done in the build.
46. **THE OWNER ACCOUNT AREA (Profile & account + Security) — per-user, self-resolved.** The owner self-profile
    (name / login-email / avatar / language / password / 2FA / sessions / data-export) is PER-USER — resolved from
    `auth()`, NEVER a request id — so every owner-realm user incl. accountant / view-only SUB-USERS manages their OWN
    account. The owner-party / business details + payout links + team + DEACTIVATE are PRIMARY-OWNER-ONLY
    (`EnsurePrimaryOwner`, #42). A login-email change RE-VERIFIES the new address (a signed `temporarySignedRoute`,
    `{hash}` = `sha1(pending_email)`) BEFORE it switches. DEACTIVATE is a REVERSIBLE, NON-DESTRUCTIVE `users.status`
    flag (login is active-only; financials STAY; PII erasure remains the #28 `anonymize()` path). Sessions are the REAL
    `sessions` table (sign-out-others password-gated + rotates the remember-token). The protection checklist is DERIVED
    from true state, NEVER a fabricated score. All writes are controller POSTs (never `/livewire/update`).
47. **ANALYTICS DEPTH IS REAL (Batch B) — no fabricated LY / pricing / recommendations.** The per-unit + by-unit +
    booking-cadence analytics are REAL aggregates: the owner Statement **by-unit split** reconciles to the net to the
    cent (#17, owner-scoped #28); **per-unit analytics** (revenue · nightly rate · best-performer · 6-mo NET · 12-mo
    occupancy · channel mix · a real attention feed) run through the shared `OwnerUnitAnalytics`; operator + owner
    **Insights booking cadence** (lead-time distribution + median · day-of-week occupancy · on-the-books) runs through
    ONE shared `App\Support\Analytics\BookingCadence` (anti-drift). A flagged, demo-only backdated seed gives shape; the
    runtime computes real aggregates from real rows (proven by `BookingCadenceTest`). REFUSED: the recommendation /
    pricing engine + the "vs last year" deltas (no fabricated LY). Extends #39.
48. **OPERATOR MONEY-WRITES ARE IDEMPOTENT + GO THROUGH GUARDED SERVICES (Batch C).** On-desk operations wire EXISTING
    guarded services, never raw writes: housekeeping assign (`HousekeepingService::assign`), reservation move
    (`AssignmentService::assignUnit` — lock + no-overlap), add-charge (`FolioService::postCharge`, #17). BOTH
    money-write routes (the on-desk payment + add-charge) carry a per-submit server nonce backed by a nullable-UNIQUE
    `idempotency_key` (+ a pre-check + catch-the-race), so a duplicate — even two CONCURRENT POSTs bypassing the UI
    disable — records ONCE (mutation- + browser-proven). Tax & fees editing is bounded + `properties.manage`-gated.
49. **THE SUPER ADMIN PANEL IS A SEPARATE, ISOLATED REALM (the deferred Phase-7a item, resolved properly).** `/admin`
    runs on a NEW `admin` session guard over a separate `platform_admins` table (NO `organization_id`, NO spatie role)
    — a platform admin is NOT a tenant `User`, so tenant guards never resolve it and it never resolves a tenant user
    (the `guest`-realm discipline). `/admin` has NO ambient tenant context, so a stray tenant query there fails CLOSED.
    Every cross-tenant READ goes via `PlatformQuery::read()`, every tenant-scoped WRITE via `PlatformQuery::mutate()` —
    both admin-guard-gated + audited + wrapping `runAsSuperAdmin` with the scope restored in `finally` (never leaks; no
    new bypass). MANDATORY 2FA (TOTP), hardened rate-limited login, actions audited to `platform_admin_actions`, noindex
    + robots-disallow (path not subdomain), NO public sign-up (seeded via `php artisan admin:create`). Confirmed: there
    was never an `is_super_admin` flag on `users` (a test asserts its absence).
50. **PLANS ARE DB-BACKED + DRIVE FEATURE-GATING ADDITIVELY.** The `plans` / `plan_features` catalog is
    ADMIN-authoritative — CRUD (integer money #17) — and drives feature-gating via the existing
    `Organization::planAllows` (fail-open) + a reusable `EnsurePlanFeature` middleware: a GRANTING plan behaves EXACTLY
    as before (additive — no regression), only an EXCLUDING plan gates (a clean "not in your plan" page).
    Assign-a-plan-to-a-customer is audited + drives features. `BillingService::catalog()` reads features from the DB.
    Stripe billing sync (the `stripe_price_id` / subscription sync) is the deferred go-live seam.
51. **AUTH SIGNALS ARE CAPTURED BY ADDED LISTENERS — the tenant flow is NOT modified; privacy is built in.**
    Login / failed / lockout are captured by listeners ADDED on the framework auth events (`RecordAuthActivity` →
    `login_activity`) — the tenant login flow is untouched, so the capture can never break auth (operator/owner use
    `Auth::validate`, so their signal is the Lockout; guest fires Failed; the admin realm dispatches its own Failed).
    Privacy from commit 1 (GDPR): a 90-day retention prune (`platform:prune-activity`, scheduled), security-monitoring
    purpose, /admin-only, **no guest email persisted plaintext (#28)**.
52. **PLATFORM INTEGRATION CREDENTIALS ARE ENCRYPTED-AT-REST + ADMIN-MANAGED + NEVER ECHOED.** The founder's master
    channel credentials (Channex / OTAs) live in `platform_integrations`, cast `encrypted:array` (like the owner IBAN,
    #28 — the plaintext never lands on disk), write-only / masked (never echoed after save), managed via an audited
    action that records WHICH channel, never the value. The test-connection is an honest STUB (real sync account-gated,
    labelled honestly); the customer-self-connect model is documented, built later.
53. **A PLATFORM ADMIN'S ACCOUNT MANAGEMENT IS SELF-ONLY, RE-AUTHED, EMAIL-RE-VERIFIED, AUDITED.** Every action
    resolves `Auth::guard('admin')->user()`, never a request id. Password + email + recovery-code changes require the
    CURRENT password (`current_password:admin`). An email change is parked in `platform_admins.pending_email` and
    switched ONLY when a SIGNED, expiring link (`sha1(pending_email)`-bound) opened from the NEW inbox confirms it — the
    OLD email stays the login and the PENDING email CANNOT log in until verified (fail-closed on bad-signature /
    stale-hash / taken-email). 2FA recovery-code regeneration requires the current password. Every change is audited to
    `platform_admin_actions` (which field, never the value). Mirrors the tenant discipline, never weaker.
54. **THE OWNER OVERVIEW (`/owner`) MATCHES ITS WIREFRAME EXACTLY + IS FULLY OPERATIONAL ON REAL OWNER-SCOPED DATA.**
    Rebuilt to the decoded `Owner Dashboard.html` on the shared owner chrome, with every section wired through the
    SHARED owner derivations (anti-drift — the Overview cannot drift from My Units / Bookings): occupancy
    (`OwnerOccupancy::forMonth`, per PHYSICAL unit — the old "room-type-wide" deferral superseded), the attention feed
    (shared `OwnerAttention`), next-30-days upcoming stays (shared `OwnerUpcomingStays` — guest NAME-only #28, owner NET
    via `OwnerLedgerAttribution`, "Not yet attributed" never a fake €0), the masked IBAN + tax-hold paused rail. The
    interactive Source/Status filters are CLIENT-SIDE (Alpine) over server-rendered rows — a Livewire round-trip would
    bypass `SetOwnerScopeForPortal` and leak. Honest empties; no fabricated goal / estimated-date / occupancy (#39).
    Decision D2: the 5 extra REAL nav items the wireframe omits are KEPT (shared shell — no functional regression).
    Mutation-/render-proven (`OwnerUpcomingStaysTest` + `OwnerDashboardViewTest`; `assertNoRawBlade`).
55. **THE OWNER MY UNITS (`/owner/units`) + ADD UNIT (`/owner/add-unit`) MATCH THEIR WIREFRAMES EXACTLY + ARE FULLY
    OPERATIONAL ON REAL OWNER-SCOPED DATA.** My Units: real card/calendar actions, each on a gated controller route —
    edit-pricing via `OwnerRateService`/`RateAndRestrictionService` (per-ROOM-TYPE, exclusive-owned only; a shared type
    is honestly non-editable, no fake save; browser-proven 180→185 persisted), message-relay to the operator
    (`owner.messages.start`, name-only #28), block-dates via `OwnerBlockService`, month-occupancy CSV export (counts-only
    #28, `exports`-gated); the calendar heat-grid uses the wireframe terracotta scale. Add Unit: the exact 4-step wizard
    + Live preview + Done, on a rich real pending `UnitRequest` (amenities via the shared `amenitiables` pivot, photos
    via the shared `media` collection with ULID filenames), the write ONE controller POST (never `/livewire/update`,
    #20), commission the owner's REAL agreement rate (an honest line if none); a request creates NO inventory — the
    operator reviews it (and sees the rich detail) before creating the real unit. Integer money (#17), guest
    name-/counts-only (#28), nothing fabricated — a proposed request is badged "Pending review", never a live/priced
    unit (#39). Proven end-to-end in the browser; `OwnerMyUnitsViewTest` + `OwnerUnitRequestTest` (6→12).

56. **OWNER BOOKINGS (`/owner/bookings`) + THE BOOKING-SUMMARY PDF MATCH THEIR WIREFRAMES EXACTLY ON REAL DATA.** The two
    items once refused as fabrication are now REAL (stale-refusal re-verification): the "Bookings over time" chart (real
    `reservations.status` stacks + net via `OwnerLedgerAttribution` + a goal line) and the "Monthly goal" ring (real net ÷
    an owner-SET `owner_monthly_target`; honest "no target" when unset). Status filter, date-range, sortable columns,
    pagination, a name-only CSV export, and the `$capActions` target-write (controller route, currency server-derived, #20)
    — page + export share the ONE `OwnerBookingsReader`. The PDF gained real owner-scoped occupancy, the guest NAME-only
    column (`GuestName`, #28), real channel commission (`ota_cost`), a VAT letterhead; Σ(rows)==gross. `OwnerBookings*`
    tests; adversarial-reviewed.

57. **OWNER STATEMENT (`/owner/statements/{id}`) + THE STATEMENT PDF MATCH THEIR WIREFRAMES EXACTLY ON RECONCILING DATA.**
    Two stale refusals (predating #54) are now REAL: the occupancy card + mini bar chart (shared `OwnerOccupancyTrend`) and
    the masked payout IBAN (shared `OwnerNextPayout`, CAP_PAYOUT-only #28). Month switcher, plain-glass hero, gross
    "N nights · M units" sublabel, statement-history Export CSV + per-year/per-row PDF (`owner.statements.history.*`,
    CAP_EXPORTS), by-unit unit links. The PDF is the letterhead statement (net-payable headline + Occupancy/Nights/ADR +
    3-col breakdown + Performance-by-unit + payout callout). Page + PDF share the ONE `OwnerStatementBreakdown`; breakdown
    Σ==net + by-unit Σ==net/gross to the cent (#17); NO guest data (#28); no invented fees/date (#39).

58. **OWNER PAYOUTS + PAYOUT METHOD MATCH THEIR WIREFRAMES — REAL WHERE REAL, HONEST WHERE ACCOUNT-GATED (⚠️ real money).**
    REAL: payout history/KPIs/reconciling breakdown/remittance PDF · the masked payout IBAN (`OwnerNextPayout`, #28,
    CAP_PAYOUT-only) · the **bank-account CRUD** — `PayoutMethod` with an ENCRYPTED `iban`, ISO **mod-97-validated**,
    self-only, `$capPayout` · **Stripe Connect** onboarding (`OwnerConnectController`, **test-mode**, + a signature-verified
    Connect webhook) · a real payout-preferences store · payout-history Export CSV · inline Tax details via the ONE
    `owner.tax.save`. **HONEST account-gated (exact design, labelled pending-live, NEVER faked):** micro-deposit
    verification (no fake Verified/deposits), live transfer, SEPA trace, the 48h hold. **The automated payout
    EXECUTION/DISBURSEMENT engine is DEFERRED** (account-gated fintech; `ManualPayoutRail` is the real tested path). NOTE:
    the page DOES collect encrypted IBANs — it is not a no-IBAN honest-summary-only design.

59. **OWNER BALANCE + EXPENSES MATCH THEIR WIREFRAMES ON REAL / HONEST DATA (⚠️ Balance is real money).** Balance is a REAL
    reconciling **clearing model** — Available (unstamped `owner_ledger` net) + On hold (tax-held pending payouts) +
    In transit (released pending) + Paid == Net position, per currency **to the cent (#17)** — with a per-row state chip,
    the tax-hold "Why is €X held?" card, the masked IBAN (CAP_PAYOUT #28), a real Export CSV. **"Withdraw early" is honestly
    DISABLED** (no owner-initiated transfer rail — never a fake movement/"in transit", #39). Expenses: real KPIs (Spent /
    Pending review / Charged) + a real Unit column/filter (**vendor REFUSED — unmodelled**) + Export CSV + a real
    repair-relay (`owner.messages.start`); the **approve/query workflow is REFUSED** (no owner-approval state machine —
    deductibility is set by the agreement). By-category bars. `OwnerBalanceViewTest` (6) + `OwnerExpensesViewTest` (5).

60. **OWNER BLOCK CALENDAR + DOCUMENTS MATCH THEIR WIREFRAMES ON THE REAL BACKENDS.** Calendar: the interactive grid —
    click a day or press-and-drag a range (Alpine) → a REAL block POST (`OwnerBlockService` → the certified
    `AssignmentService`, non-overlap enforced, `$capActions` #20, audited); click a blocked day → a REAL release; booked
    cells show the guest NAME only (#28). **REPEAT/SERIES expands into N REAL individual blocks sharing a new nullable
    `InventoryBlock.series_id`** (never a fake recurring rule, #39); an over-taken occurrence is skipped + reported; a
    toast + Undo. Documents: month cards + year-filter + a DARK annual-tax card with the REAL net (Σ `net_source_minor` =
    the annual PDF's basis, reconciling #17); the archive is the real on-demand PDFs, CAP_EXPORTS-gated. `OwnerBlockCalendarTest`
    (10) + `OwnerDocumentsViewTest` (6); block/release/series mutation-proven + browser-proven.

61. **OWNER REPORTS + INSIGHTS MATCH THEIR WIREFRAMES ON THE REAL BATCH-B ANALYTICS.** Reports: KPIs + net-by-month +
    per-unit ADR/RevPAR/share + by-channel (real `reservations.source`) + the **Gross-to-net** breakdown (reconciles to the
    cent #17 at the **REAL agreement commission rate — read from `ManagementAgreement` via the shared `OwnerCommissionRate`,
    NEVER the wireframe's hardcoded 15%**) + payout summary + real Documents + CAP_EXPORTS exports; the **Tax & VAT summary
    is HONEST-DEFERRED** (VAT not modelled — no fabricated figure/"Mark filed" #39). Insights (2-col): occupancy-by-month
    (this-year + on-the-books; **vs-last-year honest-omitted** — no prior-year data) + booking-pace + rate-performance +
    lead-time + day-of-week (+ a real derived caption) + **"Worth a look" REAL DERIVED observations**; the **"rate
    proposals" callout is HONEST** — a real link to the Rates page, NO fake proposal entity/workflow. `OwnerAnalyticsTest` (7).

62. **THE OWNER SETTINGS HUB (`/owner/settings`) MATCHES ITS WIREFRAME — 13 SECTIONS, EACH REUSING A REAL BACKEND (ANTI-
    DRIFT) OR HONEST WHERE ACCOUNT-GATED.** REUSED: Profile (`OwnerAccountController`) · Business & tax (`owner.settings.update`
    + the ONE `owner.tax.save`) · Payout account (real masked IBAN, CAP_PAYOUT #28) · Team (summary + the real `owner.team`) ·
    Notifications (`owner.notification-preferences` — in-app + email are the REAL channels) · Data export (the CAP_EXPORTS
    exports #28) · Security (→ `owner.security`) · Reduce-motion (the layout honors `prefers-reduced-motion`); Hero +
    completeness are DERIVED. **HONEST-gated (never faked #39/#37):** Billing shows the REAL management-fee rate (shared
    `OwnerCommissionRate`, 18% — never 15%) with NO owner card/subscription (that's the operator's); OTA channels are
    honest-**Pending** (Direct is the one live channel); **SMS + push are honest-pending** (no provider); currency/timezone
    are the property's (read-only). Every write is a guarded controller POST (#20). `OwnerSettingsTest` (7) +
    `OwnerAccountProfileTest` updated.

## Deferred / outstanding register (do NOT build ad hoc; see DECISIONS.md)

### ⚠️ SCHEMA-ONLY: ~8 models whose tables EXIST but which no code wires (read before building "a new module")

Verified 2026-07-17 — each has a migration **and** an Eloquent model, and **zero references from
`app/Services`, `app/Http`, `app/Livewire` or `app/Actions`**: `Subscription` · `SubscriptionItem` · `Plan` ·
`PlanFeature` · `UsageCounter` · `ApiKey` · `Webhook` · `WebhookDelivery` · `CorporateAccount` · `Group` ·
`Invoice`. They are column-level specified in `docs/schema/phase-1.md`.

**Why this block exists:** the register below calls Billing a module with "NO backend today", and Wave 4 calls
"New/Group Booking" net-new. **That is true of services and routes and FALSE of schema and models** — and it
is wrong in exactly the direction CLAUDE.md's *"Reuse, don't duplicate — never build a parallel structure for
an existing concept"* rule exists to prevent. A future Billing module must extend `subscriptions`/`plans`; a
Group Booking screen must use the existing `groups` table and `reservations.group_id` FK; a public API must
use `api_keys`/`webhooks`.

⚠️ **NAMING TRAP:** `webhooks` + `webhook_deliveries` (tenant OUTBOUND webhooks — inert) are **not**
`webhook_events` (INBOUND Channex idempotency — real, load-bearing, invariant #11). Do not conflate them.

---

### ✅ CLOSED (2026-07-17) — the three 🔴 security/integrity items, and the flaky test

The Wave 2 register is **cleared of its security/integrity items**, done BEFORE Wave 3 so new screens inherit
correct enforcement rather than needing a retrofit. Full detail: `docs/DECISIONS.md` 2026-07-17.

1. ✅ **`terminate()`'s missing internal guard** → **invariant #29**. `f4a8913`. The audit found it was worse
   than logged: there were already **two** callers and they **disagreed** (the OTA webhook path let
   `checked_out` through). One `TERMINAL_STATUSES` definition, guarded in the shared private `terminate()`.
2. ✅ **The unenforced permission catalog** → **invariant #30**. `9123ee7`. 7 routes + the refund screen now
   enforce `can:<permission>`; `payouts.manage` added (catalog 44 → 45). ⚠️ The headline turned out to be
   **privilege escalation** (Housekeeping could mint itself an Accountant), not the refund.
3. ✅ **`Guest::anonymize()` had no UI trigger** → folded into **#28** + **#30**. `94ef7ad`. It was called from
   NOWHERE; now a `guests.erase`-gated, server-confirmed trigger on the guest profile.
4. ✅ **The flaky test** — root-caused, not shrugged off (see below).

### ✅ CLOSED (2026-07-17) — BOTH flaky tests: the same disease, and neither was a product bug

`OwnerStatementPdfTest::"renders only real ledger lines"` asserted `toContain($owner->name)` — the **raw**
faker name — against **Blade-rendered (escaped) HTML**. `OwnerFactory` uses `faker->name()`, and **1.68% of
faker names contain an apostrophe** ("Abelardo O'Conner" → `O&#039;Conner`), so ~**1 run in 6** failed on a
page that was **perfectly correct**. Fixed to assert `e($owner->name)`; **proven deterministically** by
forcing a real apostrophe-bearing faker name (old assertion fails, new one passes).

**THE SECOND ONE (found in Wave 3), same disease:** `BackOfficeAccessTest` asserted
`assertDontSee($unit->code)` where `seedScenario` codes units **`U2`** — *two characters* — against owner
pages carrying **three random 40-char CSRF tokens** (`@livewireScripts`). `Str::random(40)` is alphanumeric,
so it contains "U2" **~1.05% of the time** → **~15% per run** across 8 routes. Fixed with a leak-proof needle
(`BRUNOUNIT9`) — the repo's own `FOREIGNUNIT9` prior art — and proven deterministically.

**THE LESSONS, worth more than either fix:**
1. **BOTH were the same disease: testing the NEEDLE, not the BEHAVIOUR.** A raw faker name vs escaped HTML;
   a 2-char code vs random tokens. In both, the app was perfectly correct and the assertion was wrong.
   **When a test flakes, suspect the assertion's specificity before you suspect the app.**
2. **A needle must be one that could ONLY appear by the bug.** `assertDontSee('U2')` cannot distinguish a
   leak from a coin flip. `assertDontSee('BRUNOUNIT9')` can.
3. **CAPTURE the failure; do not re-run until it goes away.** (`php artisan test > run.json 2>&1`.) The four
   true-OS-concurrency suites were the obvious suspects for the first flake and were **innocent** (6/6 green
   under targeted runs) — re-running would only ever have hidden it.

### 🔴 STILL OPEN — the register's remaining items

1. ✅ **DEAD enum values — RESOLVED in Phase 0.2 (2026-07-20), except one low-priority remnant.**
   | table.column | was dead | resolution |
   |---|---|---|
   | **`reservations`**`.payment_status` | `refunded`, `charged_back` | ✅ `refunded` now **WRITTEN** (`FolioService::paymentStatus()` — a fully-refunded stay); `charged_back` **REMOVED** from the enum (no dispute flow). Mutation-proven (`DeadEnumResolutionTest`). |
   | `folios.status` | `closed`, `void` | ✅ **REMOVED** from the enum (no folio close/void lifecycle; re-add WITH enforcement when `FolioService::closeFolio()` is built). |
   | `payments.status` | `refunded` | ⚠️ **STILL dead but harmless** — a refund creates a NEW `type='refund'` row (never restamps the original), and no UI keys on it. Drop in a future cleanup (out of 0.2's scope, which was the two above). |
   The lesson stands: **verify any status-keyed UI against what the code actually produces**, not the enum the migration declares.

2. **THE CATALOG HAS NO INBOX PERMISSION — logged, not invented** (found 2026-07-17 while enforcing #30).
   `InboxActionController` performs six real writes (reply — which sends a **guest-facing message** — plus
   assign/close/reopen/snooze/link-reservation) and there is **no `inbox.*` / `messages.*` permission in the
   catalog to gate them on**. They remain protected only by the coarse #26 door. Deliberately **not** invented
   during the enforcement gate: naming a permission is a product decision about who may speak to a guest, and
   inventing one to make a table look complete is exactly the fabrication the honesty rule forbids. Decide the
   name + role grants in a dated `docs/DECISIONS.md` entry, then enforce.
   *(The other deferred low-risk surfaces — housekeeping / maintenance / photos / guest notes+tags — DO have
   real permissions and simply were not in the first enforced set. The inbox has none at all.)*
   ⚠️ **Wave 3 widened this, and did not narrow it:** the OWNER inbox (`/owner/messages`) is gated by **#26 +
   the owner scope** (an owner can only ever reach their own threads — invariant #18, mutation-proven), and
   the operator's replies to owner threads run through the same unguarded `InboxActionController`. So the gap
   is unchanged in kind: still no `inbox.*` permission, still a product decision about who may speak to a
   guest or an owner. Still logged, still not invented.

3. ✅ **The two RBAC defects from the 2026-07-16 audit — BOTH FIXED in Phase 7a (2026-07-21), mutation-proven.**
   They were fail-closed and never exploitable, and were formally deferred to "the gate that builds org creation"
   — which is Phase 7a (tenant provisioning).
   - ✅ **Defect 1 (provisioning) — FIXED.** `App\Services\TenantProvisioningService` calls
     `OrganizationRoleProvisioner::provision()` atomically inside the org-creation transaction (the provisioner's
     first real caller), so every self-signup org gets its 13 team-scoped roles and is NOT dead-on-arrival under
     #26. Proven: a provisioned org has ≥13 roles and its founder can manage.
   - ✅ **Defect 2 (Super Admin) — RESOLVED for self-provision.** The founder holds the ASSIGNABLE, team-scoped
     `Property Owner` (org admin, `['*']` within the org) — the legitimate top role for a tenant founder — never
     the global `Super Admin` (a cross-tenant platform role a founder must never hold — that would be an
     over-grant). **RESIDUAL, re-scoped as a SEPARATE deferred PLATFORM item (NOT a self-provision defect):** the
     global `Super Admin` stays unassignable (`model_has_roles.organization_id` is NOT NULL and part of the PK;
     MySQL forbids NULL in a PK, so making it assignable needs a surrogate-PK rework of spatie's OWN table). That
     affects only the Horizon/platform-admin gate — no tenant founder needs it — so it stays deferred to whenever
     the spatie schema is next reworked; `Super Admin` remains out of `back_office_roles` until then. (See
     `docs/DECISIONS.md` 2026-07-21, Phase 7a.)

4. ✅ **THE CHANNEL MANAGER — no longer inert; the `channels.status` writer shipped in Phase 0.1 (2026-07-20).**
   `App\Services\ChannelConnectionService` is the real writer (`connect()` → `connected`, `activateMapping()` →
   `active` — the exact values the ARI push + mapping guard gate on), recording audit + domain events inside the
   service. The `DemoSeeder` now seeds a connected channel + active mappings, so `migrate:fresh --seed` produces
   a non-empty, ARMED channels table. Mutation-proven (`ChannelConnectionTest`: a disconnected channel enqueues
   NO push; after `connect()` a booking pushes exactly once). ⚠️ **What remains is the operator-facing connect
   FLOW: the Channels UI (Phase 1) that lets a real operator create + connect a channel through this writer**
   (+ the `channels.manage` permission), and the live Channex sync itself (account-gated). The backend is no
   longer inert; the UI to drive it in production is Phase 1 (see `docs/GAP-ANALYSIS.md`).

### ✅ CLOSED (2026-07-16) — the `/app` authorization hole. Safe for a real owner login.

The critical item that stood here is **FIXED** (`5155a48` route gate → `f63866a` root cause). An owner-role
user could reach the entire operator back office and every other owner's financials; both layers are now in
place and each is proven to block independently. See **invariant #26** below and `docs/DECISIONS.md`
2026-07-16. Two smaller defects surfaced by that audit remain open — they are recorded in "Other deferrals"
below (**no org-creation path provisions roles**; **`Super Admin` is unassignable**), and neither is
exploitable (both fail closed).

---

**AT A GLANCE — the honest four-way triage a fresh session needs.** The buildable-in-a-vacuum, clearly-worth-it,
self-contained core is **essentially complete** (2FA was the last such subsystem). What remains splits cleanly:

**① REGISTER items — MOST CLOSED in Phase 0 (2026-07-20); detail above.** ✅ **The channel manager is no longer
inert** — the `channels.status` writer shipped (0.1). ✅ **The dead enums are resolved** — `payment_status`
now writes `'refunded'`, and `charged_back`/`folios.status='closed'`/`'void'` were dropped (0.2); one harmless
remnant (`payments.status='refunded'`) documented. ⏸️ **The two fail-closed RBAC defects** (no app-level
org-creation provisions roles → Phase 7; `Super Admin` unassignable → a spatie-schema migration) were reviewed
and **formally deferred** to their fix gates (0.3). Still genuinely open: the **inbox has no catalog permission**
(its writes are #26-only — a product decision, deliberately not invented). None blocking.

**② SUBSYSTEMS / EPICS — buildable now (no external account), but REAL commitments, NOT quick wins.** Each is a
gated phased build with a genuine backend, exactly like Owner Accounting was:
- **Self-signup / tenant-provisioning** (the org-creation epic — and the chicken-and-egg with the RBAC defect
  above: no path provisions roles, so it must `runAsSuperAdmin` outside `/app`).
- **Guest auth** (`Guest` is NOT `Authenticatable` today — a whole auth surface, separate from operator/owner).
- **Sessions / connected-apps / IP-rules** (the "More security" stubs — session-tracking + OAuth-app store + an IP engine).
- **Portfolio** (multi-property subsystem) · **Owner Team** (owner sub-tenancy) · **Group Booking** (atomic
  group write — extend the EXISTING `groups` + `reservations.group_id`, never a parallel table) · **Owner Rates**
  (needs owner-scope rate schema) · **Channels-as-drawn** (CRUD + the missing `channels.status` writer + a live
  connect flow — see register item 4) · **Roles** (a security redesign, needs the RBAC defects resolved first).
  Per-screen readiness + traps: `docs/WAVE4-INVENTORY.md`.

**③ SPECULATIVE MODULES — designed, ZERO backend, NO customer signal. Do NOT build blind (per the ROADMAP).**
**Guest Portal** (~24 authenticated screens — the largest bet; ROADMAP is authoritative, `CATALOGUE.md` counts
32 by including guest-auth/other guest screens) · **Analytics / reports** (ADR/RevPAR/insights) · **Reviews /
reputation** · **Subscription / billing / paywall** (must extend the schema-only `subscriptions`/`plans`) ·
**Notifications** · transactional **email templates** (~9). Smaller in-product items: CSV / remittance-advice /
booking-summary exports; room descriptions + reviews.

**④ ACCOUNT-GATED — cannot be built until an external credential is obtained (blocked in a vacuum).**
- **Real inbox channels** — email (inbound-email provider e.g. Postmark/Mailgun + verified sending domain) ·
  whatsapp (Meta WhatsApp Business API approval + a BSP/Twilio number) · booking_com/airbnb (OTA messaging API).
  The `manual` channel + the whole interface work now; each adapter is an honest throwing skeleton.
- **Channex staging** — real ARI/webhook/HMAC vs a Channex sandbox account + a public tunnel (fake fully tested).
- **Stripe Connect payouts** — owner disbursement (connected-account onboarding + KYC). `ManualPayoutRail` is real+tested.
- **Stripe card checkout** — live card entry (Elements + 3DS) needs live/test keys; New Reservation's card capture
  waits on this; manual/pay-on-arrival is the real path now.
- **Production email delivery** — a real transactional provider (Postmark/SES). Dev/demo = Mailpit (wired).

**Design is NOT a bottleneck.** `docs/design/CATALOGUE.md` is the authoritative per-file inventory
(**125 files / 124 screens** — reconciled 2026-07-16; the denominator the ROADMAP's "~42 of ~124" rests on).
Most catalogued screens map to the ② / ③ modules above.

---

**TWO live-integration checkpoints (money/network rails — the only unproven-against-reality items):**
- **Channex staging** — ARI push shape + inbound webhook payload + HMAC scheme are **documented
  guesses** (validated only vs `FakeChannexClient` + simulated signed payloads). Blocked: needs a
  Channex staging account + a public tunnel.
- **Stripe Connect payout rail** — real owner money movement (connected-account onboarding + KYC).
  `StripeConnectPayoutRail.send()` **throws** (config-gated skeleton); **`ManualPayoutRail` is the real,
  tested disbursement path.** Blocked: needs Connect credentials.

**Design-pass deferrals (flagged in-product, backend-gated — NOT fake; presentation only):**
- **Stripe card entry at checkout** (Stripe Elements + 3DS) — the public checkout wires **manual /
  pay-on-arrival** through `FolioService` (real, append-only); real card capture is tied to the deferred
  Stripe live checkpoint. No fake card field.
- **Room photos — DONE** (no longer deferred). Real operator uploads on the shared `media` table (collection
  `photos`, `HasPhotos` on RoomType); secure upload (content-validated images, ULID filenames, size cap,
  tenant-scoped) on the Operator Units screen; rendered on the storefront (cover + gallery) + unit cards,
  with the **gradient fallback kept** for no-photo rooms. Storage = the local **`public` disk** in dev
  (`storage:link`); **S3/cloud is the production path** (flagged, not hardcoded). **Descriptions + reviews
  stay deferred** (no schema).
- **The remaining design-only screens** — most of the ~120-file Claude Design library (`docs/design/
  CATALOGUE.md`) maps to FUTURE modules with **no backend today** and were deliberately NOT built: the full
  **guest portal** (~24 per ROADMAP), **reviews**, **subscription billing / paywall**, **transactional emails** (~9), and
  **analytics** (Reports/Insights, ADR/RevPAR trends). Do NOT build backend for these ad hoc — each is a gated
  module. *(The **Maintenance** and **Unified Inbox** modules are now COMPLETE end-to-end — backend + styled
  UI — so they're no longer in this future list.)* *(`docs/design/CATALOGUE.md` is the authoritative per-file
  inventory + backend-status legend — read it before starting any new screen.)*
- **PDF export — DONE** (no longer deferred): the **owner statement** (owner-scoped) and the **operator
  folio** (tenant-scoped) both download as branded, PDF-safe documents via `barryvdh/laravel-dompdf` +
  `App\Services\PdfService`, reconciling to the on-screen figures **to the cent** (shared `MoneyFormatter`
  + `FolioTotals`). **Still deferred:** **CSV** export, the **remittance-advice / booking-summary** PDFs,
  and folio **print** — honestly flagged "Soon".
- **Per-screen deferred affordances** are logged in each screen's DECISIONS entry — e.g. rack
  drag-to-reschedule; housekeeping drag + assign-housekeeper (no assign method); reservation-detail
  add-charge / void / partial refund / move-room / cancel; dashboard revenue-trend / ADR / RevPAR;
  owner-portal IBAN/payout-method, approvals/action-center.

**Auth deferrals (Phase A + B complete; these are flagged, not faked — see DECISIONS.md 2026-07-13):**
- **Manager / Accountant invites — WIRED** (no longer deferred). The invite flow now covers **owner +
  Hotel Manager + Accountant** on the same secure machinery. Owner → owner link + `/owner`; STAFF →
  Membership + a real spatie catalog role (`Hotel Manager` / `Accountant`) → `/app` (no owner/agreement/
  units). Role assignment is fail-safe (mapped + provisioned-for-org, validated at invite AND accept).
- **Email DELIVERY** — **DEV/DEMO is now wired to Mailpit** (`brew install mailpit`; run `mailpit` → SMTP
  `127.0.0.1:1025`, UI `http://127.0.0.1:8025`; `.env` `MAIL_MAILER=smtp`, `MAIL_PORT=1025`). A real
  `OwnerInviteMail` lands in the Mailpit inbox with the tokened `/invite/{token}` link clickable
  (browser-verified 2026-07-13). `.env.example` defaults to `MAIL_MAILER=log` (zero-dependency) with the
  Mailpit switch documented inline. **PRODUCTION still needs a real transactional provider**
  (Postmark/SES/etc.) — **Mailpit is dev/demo only.** The flow was always complete; only the prod
  transport remains deferred.
- **2FA (TOTP) — ✅ BUILT & CERTIFIED (2026-07-18), no longer blocked.** Opt-in per user: enrollment
  (confirm-before-activate) + one-time recovery codes + the login challenge, secrets/codes **encrypted**
  (fixing the pre-existing plaintext `two_factor_secret`), disable-requires-re-auth, self-only, rate-limited.
  The pending state grants nothing by construction → **invariant #31**. `google2fa` + `bacon-qr-code`, NOT
  Fortify. Proven by `TwoFactorEnrollmentTest` (12) + `TwoFactorChallengeTest` (8); browser-verified both
  portals. See DECISIONS.md 2026-07-18. (Password reset + change password were done earlier — see the auth
  chapter above.)

**Maintenance module — COMPLETE end-to-end (backend certified + styled UI, design pass 2026-07-14).**
Nothing deferred for this module. (Was: "styled maintenance UI" + "manual-move flag surfacing" — **both now
DONE**: the styled board + work order ship the two integrations, and the work order surfaces the manual-move
flag as the real active-booking count on the blocked unit, linking to the existing move-room/rack path. The
actual room move still routes through the existing guarded `AssignmentService` move path — never auto-cancelled.)

**Unified Inbox module (COMPLETE end-to-end — backend + styled UI, in the operator nav; see DECISIONS.md
2026-07-14). The ONLY remaining deferral is the real external channels:**
- **Real external channels** — the `email`/`whatsapp`/`booking_com`/`airbnb` adapters are config-gated
  `DeferredChannel` skeletons that **throw "not configured"** (never faked; the styled composer honestly shows
  "isn't connected yet" per channel). What unblocks each: **email** — an inbound-email provider (Postmark/
  Mailgun inbound parse) + a verified sending domain; **whatsapp** — WhatsApp Business API approval + a BSP
  (Twilio/Meta) number; **booking_com / airbnb** — OTA messaging API access (Channex messaging or the OTA
  partner API). The internal **manual** channel works today. (Was also deferred: the styled inbox UI + adding
  it to the nav — **both now DONE**.)

**RBAC defects surfaced by the 2026-07-16 authorization audit (both FAIL CLOSED — not exploitable; fix when
the area is next touched):**
- **No application-level org creation provisions roles.** `OrganizationRoleProvisioner` is called ONLY by
  `DemoSeeder` + two tests — there is no `Organization::create` anywhere in `app/`. So any org born outside
  the seeder would have **zero roles**, and the `/app` back-office gate (invariant #26) would lock out every
  user in it. Moot today (no org-creation path exists at all); **wire provisioning into org creation when
  that lands**, or the first real org is dead on arrival. This is also why owner-role assignment is
  non-throwing (an owner must not be blocked by an org-setup gap).
- **`Super Admin` is unassignable, so the Horizon gate can never pass** outside `local`.
  `model_has_roles.organization_id` is `NOT NULL` **and part of the PRIMARY KEY**, so a global (team-null)
  role assignment cannot be stored, while the team-null lookup filters on `organization_id IS NULL` —
  `hasRole('Super Admin')` is therefore always false. Fails closed (safe). Zero such assignments exist. Fixing
  it needs a migration (nullable pivot team column + PK rework) — do NOT do it casually; it is spatie's own
  schema. `Super Admin` is deliberately absent from `back_office_roles` until then (untestable dead code in a
  security gate is worse than an honest note).
- **The suite needs `memory_limit=512M`** (set in `phpunit.xml`). Verified headroom, NOT a leak: dompdf embeds
  a full DejaVu font per PDF-export test, and every operator test provisions its org's 13-role × 44-permission
  catalog. `php artisan test` spawns a child PHP that re-reads php.ini, so a `-d memory_limit` on the parent
  never reaches it — hence the phpunit.xml setting. `tests/Support/auth_helpers.php` memoizes provisioning
  per org to keep the cost down.

**Other deferrals:** promo codes + deposits on the booking engine (schema only); `guests.stripe_customer_id`
/ saved cards (blind-index story); **auto-FX provider** (statements take an operator-supplied rate — no
silent float); plus standing Phase 1 deferrals (blind-index guest matching,
gap-free invoice numbering, dated out-of-order ranges, `inventory_blocks.quantity`, tenant-custom
amenities, same-day-turnover housekeeping dedupe).

## Regression net — `php artisan test` (MySQL `vyrobel_test`)

**819 tests / 6910 assertions, all green on MySQL 8. "Green here means the whole guarantee stack holds"** — Phase 1
(double-booking/tenant/ledger/GDPR/event-discipline), Phase 2 (cross-system contention/sync-reliability/
payments), Owner Accounting (attribution-to-the-cent/append-only-reversal/period-locking/owner-isolation),
**the phased build** (Phases 0–5: operator + owner buildable sets · the notification subsystem · the whole
Guest Portal [auth realm + Trips/Reservation/Manage/Check-in/Arrival + Payments/Wallet/Messages/Saved] · the
Reviews module), and the **mutation-verified security/isolation suites** — authz (#26) · refund bound (#27) ·
2FA (#31) · guest realm-separation + guest isolation (#32/#33/#35) · the wallet ledger no-overdraw (#34) ·
owner isolation · reviews tie-to-real-stay + moderation + owner-review isolation (#36/#37) · permission
enforcement (#30) — **each proven to FAIL without its guard** (the mutation is applied, the test goes red, the
guard is restored). Also covered:
**Auth Phase A + B** (`AuthLoginTest` · `OwnerInviteBackendTest` · `OwnerInviteAcceptTest` ·
`OwnerInviteEmailTest` · `OperatorInviteOwnerTest` · **`StaffInviteTest`** — Hotel Manager / Accountant
activation → `/app` + real spatie role, no owner artifacts, fail-safe on unprovisioned/unknown role · and
**`PhaseBEndToEndTest`** — one continuous invite→email→activate→portal→token-spent chain with the
hashed/single-use/no-enumeration security recap),
the owner-portal design pass (per-screen render/isolation + occupancy-correctness), the operator design
pass (5 screens), and the public booking storefront (`PublicBookingBrowseTest` · `PublicBookingRoomTest` ·
`PublicBookingCheckoutTest` — real sellable rooms/prices with NO auth, public property isolation, and the
**public checkout WRITE: real book via AssignmentService, advisory hold never changes `sold`, two-holds-
one-books with no overbooking**), together. The true-parallel no-double-booking suite (`ConcurrentBooking`
/`Deadlock`/`CrossSystemConcurrency`, 9 tests/114 assertions) re-run green after the checkout screen.
- **TRUE OS-level parallelism** (separate PHP processes + MySQL connections, shared future-timestamp
  barrier, committed data, no RefreshDatabase): `ConcurrentBookingTest`, `DeadlockAvoidanceTest`,
  `CrossSystemConcurrencyTest` (repeat-runs zero-flake).
- **Real Redis + time-travel** (no real sleeps): `SyncReliabilityTest`, `AriPushTest`, cross-system retry.
- **In-memory fakes, no network** — `FakeChannexClient`, `FakeStripeClient` (real HMAC) back the rest;
  **Stripe additionally validated live once** (test-mode, 2026-07-03).
- **Owner Accounting suite (45 named tests):** `tests/Unit/MoneyAllocateTest` + `tests/Feature/`
  `OwnerAttributionTest`, `OwnerExpenseTest`, `OwnerStatementTest`, `OwnerPayoutTest`,
  `OwnerPortalIsolationTest` (+ shared `tests/Support/owner_helpers.php`).
- **Real auth security tests** — `AuthLoginTest` (active-only, rate limit, no enumeration, role-routing,
  session) + the invite suite; `PhaseBEndToEndTest` proves the whole invite chain as one continuous
  browser-less flow (hashed/single-use token, no-enumeration, hashed password, auto-login, owner-scope);
  `PasswordResetTest` (no-enum, active-only, single-use, expiring, rate-limited, hashed round-trip);
  `PruneExpiredInvitesTest` (conservative prune + untouched/idempotent/tenant-safe cases).
- **Tier 1 finishing-sweep tests** — `CheckoutDirtiesUnitTest` (checkout→unit dirty via the real
  service/event chain, real dirty→clean cycle, idempotent) · `UnitFactoryContextTest` (factory respects
  tenant context + explicit-override).
- **Demo-readiness tests** — `RoomPhotosTest` + `RoomTypePhotoUploadTest` (photos on the media table;
  secure upload — rejects non-image/disguised/oversized; cross-org 404; display + gradient fallback) ·
  `OwnerStatementPdfTest` + `FolioPdfTest` (branded PDFs reconcile to the cent; owner/tenant-scoped
  download → cross-owner/cross-org 404; no invented lines; PDF-safe).
- **Maintenance module suite** — `MaintenanceTicketTest` (guarded lifecycle) · `MaintenanceOutOfServiceTest`
  (OOS excludes from availability + returns; two-tickets; cross-unit isolation; negative over-committed with
  no oversell; never auto-cancel) · `MaintenanceCostExpenseTest` (owned → owner_expense via the certified
  path + reconciles; operator-owned → none; append-only reversal; period-lock) · `MaintenanceModuleTest`
  (tenant-scoped render + cross-org 404; end-to-end tie-together). **Styled-UI design pass:**
  `MaintenanceBoardViewTest` (real lanes/fields; OOS badge + rail; real create with the OOS side-effect;
  foreign-unit 404; assign persist; non-member 422 + cross-org 404; tenant isolation + cancelled-off-board)
  · `MaintenanceDetailViewTest` (real facts + audit-event timeline; state-gated lifecycle persist; guarded
  illegal transition → clean error; owned → charged-to-owner + real owner_expense reconciles; operator →
  operator-cost + no owner_expense; OOS callout + real manual-move count; cross-org detail/action 404). The
  concurrency suite is re-run green after the module (no-double-booking + single-write-path intact).
- **Unified Inbox suite** — `InboxCoreTest` (thread/message create; morph map; inbound idempotency;
  outbound via manual; deferred adapters throw [asserted]; honest failed record; encrypted participant_identity;
  guarded transitions; tenant isolation; backbone-job idempotency) · `InboxLinkingTest` (known-address → existing
  guest [reused matcher, no dup]; unknown → created+linked+threaded; grouping by ref AND participant hash;
  reservation link; reply via the thread channel; anonymize redacts the conversation; tenant isolation; cross-org
  reservation-link rejected) · **`InboxConcurrencyTest`** (TRUE OS-parallel, 5× — two concurrent inbound of the
  same external id → exactly one message + one thread, no 500) · `InboxModuleTest` (HTTP integrity: list/thread
  render tenant-scoped; cross-org thread + reply → 404; real reply via manual [delivered, unread cleared];
  deferred reply → honest failed + flash; guest NAME shown, encrypted identity not rendered; end-to-end
  inbound→match→group→reply). `BookingImportTest` stays green after the shared-GuestMatcher refactor.
  **Styled-UI design pass:** `InboxListViewTest` (real channel label + preview; encrypted identity not
  rendered; reservation tag only when linked; tenant isolation) · `InboxThreadViewTest` (transcript + real
  delivery status; deferred-channel honest "not connected" composer; forced deferred reply → honest failed;
  assign/close/reopen/snooze persist + guarded; the guest stay-stat derived-real or omitted; link-reservation
  persists).
- **Security / integrity suite — MUTATION-VERIFIED (each proven to FAIL without its guard; this is the point).**
  `BackOfficeAccessTest` (#26 — an owner-role user is bounced from every `/app` route + the owner-only role;
  each defense-in-depth layer blocks independently) · `RefundGuardTest` (#27 — cumulative refunds bounded,
  same-currency, non-positive rejected; **failed 7/10 pre-fix**) · the `terminate()` service guard (#29 — a
  terminal reservation cannot be re-terminated; both callers funnel one `TERMINAL_STATUSES`) ·
  `PermissionEnforcementTest` (#30 — 7 routes + the refund screen enforce `can:`; **stripping every `can:`
  refails 5**; the privilege-escalation invite path closed) · `GuestErasureTest` (#28 — `guests.erase`-gated,
  typed-ERASE-confirmed anonymize; **8 failed pre-fix**). All found by READING, not by a failing test.
- **Auth — change-password + 2FA.** `ChangePasswordTest` (9 — happy path + remember-token rotation, wrong
  current rejected/unchanged, `auth()`-resolution [A changes, B untouched], owner side, the non-manager
  reachability of `/app/security`, the shared path) · `TwoFactorEnrollmentTest` (12 — confirm-before-activate
  mutation-proven, recovery codes single-use + regenerate-invalidates, disable-requires-re-auth, encrypted at
  rest, self-only) · **`TwoFactorChallengeTest`** (8 — **mutation-proving invariant #31**: a correct password
  alone reaches neither `/app` nor `/owner`; valid TOTP completes; recovery code completes + is consumed [no
  reuse]; invalid rejected + throttled [blocks even a valid code past the limit]; a non-2FA login is unchanged).
- **Wave 2–4 screens + the shared-definition drift proofs** (a shared rule is worthless unless a test proves
  both surfaces use it): `ReservationStage` — its 28-combination status×date partition is asserted exhaustive
  (a chip can't drift from what it filters) · `GuestSpend` — `for()` / `forGuestIds()` return identical figures
  and **reconcile to the ledger by construction** · `GuestHistory` — one stays/nights/"guest since" rule (the
  inbox and CRM had shipped two). Plus the per-screen render/isolation + guarded-write tests for the
  Reservations list, Cancelled/No-show, the Refund screen, the Guests CRM + profile, Operator Rates, New
  Reservation (the 3 guards), Settings (operator + owner), and the owner inbox (`/owner/messages`, owner-scope
  mutation-proven — invariant #18).
- **THREE browser-verified user journeys** — ⚠️ **NOT part of the runnable regression net; do not read this
  line as a gate.** They were an agent-driven **Playwright MCP** pass against a live `php artisan serve`
  (there is no installed Playwright, no specs, and every artefact is gitignored), so unlike every other
  item in this section they **cannot be re-run or re-checked**. Historically real and valuable — the pass
  found and fixed two demo-data gaps — but a manual pass, on real demo data: **(1)
  public booking** (browse→room→checkout→real direct booking), **(2) operator ops**
  (calendar/front-desk/housekeeping/folio/dashboard), **(3) owner portal** (dashboard/statement/payouts/
  units, money reconciles to the cent). This pass **found + fixed the two demo-data gaps** (public slug,
  owner-accounting seeding) — see `docs/DECISIONS.md` 2026-07-12; the demo now demos the full loop
  end-to-end. (The invite modal + full auth login were also browser-verified during Phase B.)

## Owner portal — STYLED & LIVE (design pass complete)

The 4 owner-portal screens are **styled + wired to real owner-scoped data** (no longer unstyled stubs).
Shared foundation: `resources/views/components/layouts/owner.blade.php` (the design system — frosted glass,
terracotta `#C2410C`, Sora/Inter, the real `vyFadeUp/vyShimmer/vyDrift/vySwap` keyframes) ·
`resources/views/components/owner/shell.blade.php` (`<x-owner.shell>` — sidebar + topbar) ·
`App\Livewire\Owner\Concerns\FormatsOwnerData` (money/status/viewer formatting). **Presentation only —
backends/schema/guarantees UNCHANGED.**

| Route | Component / view | Styled |
|---|---|---|
| `/owner` | `App\Livewire\Owner\OwnerDashboard` → `livewire/owner/dashboard` | ✅ styled + live |
| `/owner/statements/{statement}` | `App\Livewire\Owner\OwnerStatementView` → `livewire/owner/statement` | ✅ styled + live (RMB-safe, cross-owner 404) |
| `/owner/payouts` | `App\Livewire\Owner\OwnerPayouts` → `livewire/owner/payouts` | ✅ styled + live (new page; lifecycle uses only the REAL pending/paid/failed/reversed states) |
| `/owner/units` | `App\Livewire\Owner\OwnerMyUnits` → `livewire/owner/units` | ✅ styled + live (new page; **owner-scoped occupancy** — excludes non-owner units of the same room type) |

**WAVE 1 (2026-07-16) — the owner-accounting long-tail. All owner-scoped + fail-closed; presentation only.**

| Route | Component / view | Styled |
|---|---|---|
| `/owner/balance` | `App\Livewire\Owner\OwnerBalance` → `livewire/owner/balance` | ✅ REAL ledger activity + per-row running balance; DERIVED net position (Σ `signedMinor`); in-transit/paid from the real payout enum. Withdraw/CSV disabled "Soon"; the design's clearing-state machine + tax hold OMITTED (no schema). |
| `/owner/expenses` | `App\Livewire\Owner\OwnerExpenses` → `livewire/owner/expenses` | ✅ REAL `owner_expenses` incl. maintenance-sourced (polymorphic `source`); the `deductible` **TRI-STATE** (true/false/**null** — never coerced); voided badge; the statement each hit (`attributed_ledger_id → ledger.statement_id`). The owner approve/query workflow OMITTED (no state machine). |
| `/owner/agreement` | `App\Livewire\Owner\OwnerAgreement` → `livewire/owner/agreement` | ✅ REAL effective-dated terms, **display-only**, honest nulls ("Not set", never "0%"). ⚠️ `ManagementAgreement` + `Owner` are **NOT** owner-scoped → scoped explicitly via `OwnerContext`, fail-closed (leak test locks it). e-signature/amendments OMITTED. |
| `/owner/archive` | `App\Livewire\Owner\OwnerArchive` → `livewire/owner/archive` | ✅ REAL finalized statements w/ the real gap-free `sequence_no`; **drafts excluded**; DERIVED lifetime/counts/span (the design's "3 years · 34 documents" refused). Nav key `statements`. |
| `/owner/units/{unit}` | `App\Livewire\Owner\OwnerUnitDetail` → `livewire/owner/unit-detail` | ✅ REAL unit facts + **owner-scoped occupancy (this unit only)** + attribution breakdown + real stays (no guest PII). Cross-owner/operator-owned unit → 404. Most of the design OMITTED (see DECISIONS 2026-07-16). |
| `/owner/documents` | `App\Livewire\Owner\OwnerDocuments` → `livewire/owner/documents` | ✅ Aggregation over the **real generatable PDFs** only (statement · booking summary · remittance) — every listed row downloads (test follows the links). ZIP/tax-summary "Soon", never linked. |
| `/owner/statements/{s}/bookings` | `Owner\OwnerBookingSummaryPdfController` + `Support\Pdf\OwnerBookingSummaryDocument` | ✅ PDF — the bookings behind a statement's gross. Occupancy + the guest column OMITTED (see below). |
| `/owner/payouts/{payout}/remittance` | `Owner\OwnerRemittancePdfController` + `Support\Pdf\OwnerRemittanceDocument` | ✅ PDF — remittance advice per payout; the amount label is derived from the REAL status ("Net payable" while pending, never "Net paid"). |

**WAVE 3 (2026-07-17) — the OWNER INBOX. Owner-scoped by `BelongsToOwner`; a REAL write path.**

| Route | Component / view | Styled |
|---|---|---|
| `/owner/messages` | `App\Livewire\Owner\OwnerMessages` → `livewire/owner/messages` | ✅ the owner's REAL threads with their operator's staff (a real user + their real back-office role — NOT the design's 5 fabricated directory entries, two of which were the SaaS vendor). Unread badge/filter/count DERIVED from `owner_last_read_at` vs later staff replies (never the operator's `unread_count`). Search hits real `messages.body` — **plaintext, so no blind index needed**, unlike guest contact. Compose = ONE real query (the org's back-office members). |
| `/owner/messages/{thread}` (+ POST `messages.reply` / `messages.start`) | `App\Livewire\Owner\OwnerMessageThread` + `Owner\OwnerMessageActionController` → `livewire/owner/message-thread` | ✅ the transcript, **MIRRORED** (`direction` is org-relative → the owner's own message is `inbound`; the operator inbox reads the same rows the other way up). The reply is a REAL message via `InboxService::postOwnerMessage()` → it lands in the operator's inbox, **named**, and bumps their unread. `failed` rendered honestly (#24); **no "✓✓ Read"** — that value is not in the enum. |

⚠️ **The backend Wave 3 added, and why each piece exists:** `message_threads.owner_id` + `BelongsToOwner`
(**without the column the scope does not fail closed — it never runs**; see invariant #18) · `owner_last_read_at`
(the operator's `unread_count` is theirs, and `markRead()` zeroes it) · `postOwnerMessage()` (not
`postInbound()` — the GuestMatcher would mint a spurious Guest for the owner; not `sendOutbound()` — wrong
direction + hard-codes the "Operator" label) · `markReadByOwner()`. The operator inbox now NAMES owner threads
(was "Unknown sender"). **CUT, not deferred:** owner↔"Vyrobel Support"/"Billing" (the SaaS vendor —
cross-tenant, cannot live in an org-scoped table) and owner↔"Maintenance team" (a team is not a user).

Per-screen tests: `OwnerStatementViewTest`, `OwnerDashboardViewTest`, `OwnerPayoutsViewTest`,
`OwnerMyUnitsViewTest`, **`OwnerMessagesTest`** (the mirror, the real reply, the loop closing into the
operator's inbox, the fabrications absent), **`OwnerInboxIsolationTest`** (symmetric + mutation-proven)
(+ existing `OwnerPortalIsolationTest`). The **design-pass honesty rule** — REAL →
wire live · DERIVED → from real figures only · DEFERRED → stubbed "not yet available" · NO-BACKEND →
disabled "Soon" (never a fake action/IBAN/date/download); reconcile money to the cent; owner isolation
holds; guest PII never exposed — is locked in `docs/DECISIONS.md` (2026-07-11).

## Operator screens — STYLED & LIVE (5 core + Units + Wave 1's 2 + Wave 2's 5)

Shared foundation: `resources/views/components/layouts/operator.blade.php` (dedicated operator layout —
same Vyrobel tokens as the owner one, copied not shared) · `resources/views/components/operator/shell.blade.php`
(`<x-operator.shell>` — grouped sidebar + topbar) · `App\Livewire\Operator\Concerns\FormatsOperatorData`
(viewer + `money()` + warm-only status/source labels, real enum only). **TENANT-scoped, presentation only.**

| Route | Component / view | Styled |
|---|---|---|
| `/app/rack` | `App\Livewire\ReservationRack` → `livewire/reservation-rack` | ✅ styled + live (availability + booking bars; warm status tones; drag DEFERRED; overbooked excluded) |
| `/app/front-desk` | `App\Livewire\FrontDesk` → `livewire/front-desk` | ✅ styled + live (arrivals/departures/in-house + real folio balance; **check-in/out WIRED + persist** via POST→`FrontDeskActionController`→`FrontDeskService`→PRG; payment collection DEFERRED) |
| `/app/housekeeping` | `App\Livewire\HousekeepingBoard` → `livewire/housekeeping-board` | ✅ styled + live (kanban; the 4 columns ARE the real status enum; **status advance WIRED + persists** incl. unit sync via POST→`HousekeepingActionController`→`HousekeepingService`→PRG; drag + assign-housekeeper DEFERRED — no assign method exists) |
| `/app/reservations/{reservation}` | `App\Livewire\ReservationDetail` → `livewire/reservation-detail` | ✅ styled + live (RMB-safe; folio as the real **append-only ledger**, reconciled to the cent, no invented lines; **record-payment + full refund WIRED + persist append-only** via POST→`ReservationFolioActionController`→`FolioService`→PRG; add-charge/void/partial-refund/move/cancel DEFERRED) |
| `/app/dashboard` | `App\Livewire\Operator\OperatorDashboard` → `livewire/operator/dashboard` | ✅ styled + live (NEW page; read-only; every KPI a TRUE org figure — occupancy summed org-wide from the Availability spine, arrivals/HK counts, payments-today from the ledger; revenue/ADR/RevPAR trend DEFERRED) |

**WAVE 1 (2026-07-16) — the operator side of the owner-accounting long-tail. TENANT-scoped (NOT owner-scoped).**

| Route | Component / view | Styled |
|---|---|---|
| `/app/owners/{owner}` | `App\Livewire\Operator\OperatorOwnerDetail` → `livewire/operator/owner-detail` | ✅ the operator's lens on ONE owner (units/agreement/statements/payouts/balance). `Owner` is `BelongsToOrganization` → cross-org 404 free. ⚠️ **OwnerScope is INACTIVE on `/app`**, so every other read is filtered EXPLICITLY by `owner_id` (the test asserts symmetrically both ways that two owners in ONE org never bleed). Portal-access state is the REAL invite state (`user_id` alone does NOT mean access — it is linked at invite time to an inactive login). Owner email/phone rendered (the operator's own CRM record); **tax_id + raw address never read**. Edit/Remove modal + its no-column toggles OMITTED. |
| `/app/payouts/run` (+ POST `payouts.create` / `payouts.pay`) | `App\Livewire\Operator\PayoutRun` + `Operator\PayoutRunActionController` → `livewire/operator/payout-run` | ✅ **REAL money writes** through the certified `OwnerPayoutService` (`createFromStatement` / `pay`) via POST→controller→service→PRG. Ready-set mirrors the service's own rule exactly (finalized+locked, net>0, no pending/paid payout). Idempotent + guarded (an illegal transition → clean flash, never a 500). **`ManualPayoutRail` is the real rail and the copy says so plainly** ("Vyrobel does not move this money — marking a transfer paid RECORDS a bank transfer you already made"); **Stripe Connect is disabled "Soon", never callable**. SEPA export / IBAN / the "no verified IBAN" hold rail OMITTED (no such field exists); the design's non-reconciling constants never ported. |

**WAVE 2 (2026-07-17) — operator daily ops. TENANT-scoped, behind the #26 gate. Locked decisions in
`docs/DECISIONS.md` 2026-07-17.**

| Route | Component / view | Styled |
|---|---|---|
| `/app/reservations` | `App\Livewire\Operator\ReservationsList` → `livewire/operator/reservations-list` | ✅ every booking, one row each. Stage chips + badges BOTH from the shared **`ReservationStage`** (`scope()` for the SQL, `for()` for the badge — a chip cannot drift from what it filters). Chip counts are the REAL count of what the click delivers, search included. Tiles = this calendar month, by `check_in`, cancelled/no-show EXCLUDED, per currency. **OMITTED:** the paused banner, the bulk composer + Send, bulk select, the month picker, sort controls, the "Refunded" badge (a DEAD enum). "Booking.com"/"Completed" never rendered — neither is modelled. |
| `/app/reservations/{reservation}` (extended) | `App\Livewire\ReservationDetail` + `Operator\ReservationLifecycleController` | ✅ **cancelled + no-show terminal states** — real terminal panel, timeline, cancellation policy, and guarded lifecycle writes (`cancel` / `markNoShow`) gated off the controller's public `CANCELLABLE`/`NO_SHOWABLE` allow-lists. ⚠️ Those constants are **load-bearing**: `AssignmentService::terminate()` has **no internal guard** (register item 1). |
| `/app/reservations/{reservation}/refund` | `App\Livewire\Operator\ReservationRefund` → `livewire/operator/reservation-refund` | ✅ the dedicated refund screen over the **#27-guarded** backend. It **re-derives nothing**: shows the real `remainingRefundableMinor()` per payment, caps the input at it, and offers nothing once remaining = 0 — so the UI cannot invite a rejection. The service stays the authority. **Partial refunds were never a backend gap** — the controller is now amount-aware (no `amount` → full remaining), rejects €0 BEFORE the service, and surfaces an over-refund as a clean flash, **never a silent clamp**. |
| `/app/guests` | `App\Livewire\Operator\GuestsList` → `livewire/operator/guests-list` | ✅ the CRM list. Real `is_vip`/`risk_flag`/tags/nationality; **⚠️ PII-visible (invariant #28)** — email/phone decrypted HERE and on the profile only. "Total billed" + open balance from the shared **`GuestSpend`**; stays from the shared **`GuestHistory`**; badge from `ReservationStage`. Search is **name-only and says why** (encrypted TEXT is not indexed by value). **OMITTED:** the bulk bar + Send/Delete modals (a guest is erased via `anonymize()`, never deleted), CSV export, Add guest (no form exists), pagination, sort, the "+4%" delta. |
| `/app/guests/{guest}` (+ POST `guests.update` / `guests.notes.store` / `guests.tags.store`, DELETE `guests.tags.destroy`) | `App\Livewire\Operator\GuestProfile` + `Operator\GuestActionController` → `livewire/operator/guest-profile` | ✅ **REAL writes** through the new `App\Services\GuestService` (the single guest write path) — contact edit (encrypted on save by the cast), VIP/risk/marketing toggles, **notes** (the first writer of the shared `notes` table, ever) and **tags** (created on first use — `unique(org,name)`, so a pick-list would have been empty forever). The design's "Save changes" fired a toast and persisted **nothing**; this survives a reload. **⚠️ The audit records WHICH fields changed, never their values** (invariant #28). **OMITTED:** the whole "Latest review" panel (no reviews table exists), the localStorage refund netting + its non-reconciling "€978", "Dublin, Ireland" (no residence column), "Prefers email" (no column), the 8 hard-coded preference chips, and the "Shown to housekeeping"/"Flags arrivals" claims (**verified FALSE**). |

Per-screen tests: `OperatorRackViewTest`, `OperatorFrontDeskViewTest`, `OperatorHousekeepingViewTest`,
`OperatorReservationDetailViewTest`, `OperatorDashboardViewTest`, **`OperatorOwnerDetailViewTest`**,
**`OperatorPayoutRunTest`** (the real create/pay writes asserted against the DB, idempotency, guarded
double-pay, cross-org 404), and **Wave 2's** `OperatorReservationsListTest` · `ReservationStageTest` ·
`ReservationTerminalStatesTest` · `RefundScreenTest` · **`OperatorGuestsListTest`** (the PII perimeter,
mutation-proven) · **`OperatorGuestProfileTest`** (the save is real, not a toast) · `GuestSpendTest` ·
`GuestHistoryTest` · `GuestServiceTest` (the audit must not hold PII; `anonymize()` redacts notes). The
**operator-mutation pattern**
(reuse for every operator write): **POST → controller → existing service action → PRG redirect**; the POST
re-runs the web middleware (tenant context set), `{id}` is a **string** param resolved via tenant-scoped
`findOrFail` (never RMB) → cross-org 404. Never a Livewire wire-action for a scoped mutation.

## Public booking storefront — STYLED & LIVE (COMPLETE — 3 screens, incl. the real WRITE)

A dedicated **PUBLIC** design system (unauthenticated; context via `ResolvePublicProperty` — the slug sets
real tenant/property scope, NOT the operator `app` group / `SetTenantForWebRequest`, no logged-in user).
Foundation: `resources/views/components/public/shell.blade.php` (`<x-public.shell>` — full-page storefront
chrome: property header + centered content + footer, NO operator sidebar; same Vyrobel tokens, copied). **No
Livewire** (server-rendered, rate-limit-friendly). Browse + Room Detail are READ-ONLY; **Checkout is the one
public WRITE** — a session-CSRF POST that books through the single guarded `AssignmentService` path.

| Route | Controller / view | Styled |
|---|---|---|
| `/book/{property}` | `BookingEngineController@browse` → `booking/browse` | ✅ styled + live (search + real sellable rooms via `BookingEngineService::search`; per-night derived from real total; names-only teaser before a search; Select → room detail) |
| `/book/{property}/rooms/{roomType}` | `BookingEngineController@room` → `booking/room` | ✅ styled + live (property-scoped → cross-property 404; real specs + real amenities/availability/price; photos/description/reviews DEFERRED; Book → checkout) |
| `/book/{property}/checkout` (GET review · **POST write**) + `/reservation/{quote}` | `BookingEngineController@checkout`/`reserve`/`reservation` → `booking/checkout`,`booking/reservation` | ✅ styled + live — **the WRITE flow.** Quote→hold→**book THROUGH AssignmentService** (source=direct, rejects when full, never overbooks)→pay THROUGH FolioService (append-only)→confirmation with the real reference. Manual/pay-on-arrival wired; Stripe card entry DEFERRED. **POST is session-CSRF-protected** (not exempt) + `throttle:booking`. |

Per-screen tests: `PublicBookingBrowseTest`, `PublicBookingRoomTest`, `PublicBookingCheckoutTest` (real
book via AssignmentService; advisory hold never changes `sold`; **CRITICAL — two holds on the last unit,
only one books, `sold` never exceeds capacity**; cross-property checkout 404; CSRF token on the write
form; no auth). The JSON API (`/search` `/quote` `/book` `/confirmation`) is the real final backend,
unchanged (`PublicBookingEngineTest`); its POST endpoints stay CSRF-exempt by path (`book/*/quote`,
`book/*/book`).

Operator Units (the last stub) is now styled too: `/app/units` → `App\Livewire\UnitsList` →
`livewire/units-list` — ✅ styled + live (real unit fields + real cleanliness/condition enums + owner
link where set; `OperatorUnitsViewTest`: render + tenant isolation). **No unstyled contract stubs
remain** — every styled surface over the certified backend is done.

**Livewire RMB rule (must hold in the design pass):** never type-hint a scoped model as a public
Livewire prop (route-model binding resolves during `SubstituteBindings`, BEFORE the tenant/owner
middleware). Hold the id as a string; resolve via scoped `findOrFail` in `render()`. **Also (proven on
the owner portal):** scoped-data-dependent interactivity must NOT use Livewire wire-actions — they
re-render via `/livewire/update`, bypassing the route middleware → scope leak. Use Alpine/client-side or
plain GET links (e.g. `?month=`).

## Pointer index (read on demand)

- **THIS FILE** (`docs/PROJECT-STATE.md`) → read FIRST · then **`docs/DECISIONS.md`** (the dated "why" behind every gate, append-only, full arc Phase 1 → Unified Inbox UI).
- **QA (the complete 3-pass verification — read for the "is it verified?" answer)** → **`docs/QA-FULL-REPORT-2026-08-04.md`** (Pass 1 broad E2E + the 7-finding fix pass) · **`docs/QA-DEEP-REPORT-2026-08-04.md`** (Pass 2 deep mutations + D2-OBS-1) · **`docs/QA-THIRD-REPORT-2026-08-04.md`** (Pass 3 final mutating-ops + motion video/trace; 0 open defects). Motion artifacts (local, gitignored): `.playwright-mcp/qa3/`.
- **Vision / phases / buy-vs-build** → `docs/master-plan.md`
- **`docs/BOOTSTRAP-AND-OPERATING-GUIDE.md`** — *(historical: the original zero-to-Phase-1 setup runbook +
  operating model. Superseded as guidance — but its **environment gotchas are still current** (XAMPP/MySQL-not-
  MariaDB, the memory/config file layout), and it is the only place the operating model is written down.)*
  ⚠️ **Environment prerequisites for a green `php artisan test`: a genuine MySQL 8 `vyrobel_test` DB (NOT
  XAMPP's MariaDB) + Redis running** (Horizon queue) + Mailpit for the mail tests' dev transport.
- **Data contracts (column-level source of truth)** → `docs/schema/phase-1.md` · `docs/schema/phase-2.md`
  · `docs/schema/phase-3-owner-accounting.md` · **`docs/schema/maintenance.md`** · **`docs/schema/inbox.md`**
- **Gated build prompts** → `docs/prompts/phase-1.md` · `docs/prompts/phase-2.md` ·
  `docs/prompts/phase-3-owner-accounting.md` (all done/certified) · `docs/prompts/live-integration-checkpoint.md`
  (Stripe leg done; Channex Steps 1–2 outstanding)
- **Design source of truth** → **`docs/design/CATALOGUE.md`** (per-file inventory + backend-status legend; headline count reconciled to **125 files / 124 screens**) · `docs/design/design-system-brief.md` · `docs/design/owner-portal-spec.md`. ⚠️ The raw Claude Design HTML lives in **`docs/design/raw/*.html` — GITIGNORED / local-only, NOT in a fresh clone** (125 files on this machine); the `.md` specs above ARE versioned. **A fresh clone cannot read the designs** — work from `CATALOGUE.md` / `DESIGN-COVERAGE.md` / `WAVE2-INVENTORY.md` / `WAVE4-INVENTORY.md` / `AUTH-INVENTORY.md`, which are versioned and were written from them. ⚠️ **The per-page inventories are per-wave, not total:** Waves 2 and 4 + the auth screens have versioned inventories; a FUTURE build of a design without one (most Tier-2 modules) must first produce its inventory from `raw/` **on a machine that has it** (this one does), or the design is simply unavailable on a clone.
- **PLANNING SOURCE OF TRUTH (read before starting any new screen)** →
  **`docs/ROADMAP.md`** (designed-vs-built-vs-remaining across ~16 modules; **~42 of ~124 screens done (~34%)**
  — a column sum with one cross-listed row, so distinct = 41; the tilde is load-bearing; Guest Portal counted at
  ~24) · **`docs/TIER1-SPEC.md`** (per-screen build specs — backend reality per feature, write methods,
  buildability verdict, the 5-wave order with **Waves 1–4 CORE done**) · **`docs/DESIGN-COVERAGE.md`**
  (exists-vs-missing design audit — 125 files / 124 screens, near-total coverage) · **`docs/WAVE2-INVENTORY.md`**
  + **`docs/WAVE4-INVENTORY.md`** (per-page feature inventories, every element tagged ✅ BUILD-NOW / 🔨
  NEEDS-BACKEND / 📋 DECORATIVE-FAKE / ⬜ OTHER-MODULE — **the template for reading ANY design before building
  it**; WAVE2 is the artefact that found the €1,107 over-refund by inventorying rather than testing) ·
  **`docs/AUTH-INVENTORY.md`** (every auth/onboarding design: BUILT vs quick-buildable vs blocked — carries the
  superseding 2026-07-18 note that change-password + 2FA are now built).
- **Code style / testing patterns** → `docs/CODING-STANDARDS.md` · **Conventions + commands** → `CLAUDE.md`
- **Code** → `app/Services/` (incl. `InboxService`, `GuestMatcher`, `MaintenanceService`, owner-accounting services,
  and the **guest-realm/reviews** services: `GuestAccountService`, `GuestWalletService`, `GuestWishlistService`,
  `ReviewService`) · `app/Payments/` · **`app/Payouts/`** · **`app/Messaging/`** (channel interface + ManualChannel +
  Deferred skeletons + resolver) · `app/Services/Channex/` · `app/Jobs/Sync/` · `app/Actions/Reservations/` ·
  **`app/Mail/`** · `app/Notifications/` (+ `Operator/`) · `app/Listeners/` (domain-event → notifications/emails) ·
  **`app/Auth/`** (`GuestUserProvider` — the custom `guest-eloquent` driver, registered in `AppServiceProvider`) ·
  `app/Http/Controllers/` (+ `Auth/`, `Operator/`, `Owner/`, **`Guest/`** — whose `Concerns/ResolvesGuest` resolves the
  guest FROM the guard, never a request id) · `app/Http/Middleware/` (incl. `EnsureBackOfficeAccess`,
  `SetTenantForGuestRequest`, the guest-guard chain) · `app/Http/Requests/` (incl. `Auth/`, `Guest/GuestLoginRequest`) ·
  `app/Providers/` (morph map in `AppServiceProvider`) ·
  `app/Livewire/` (+ `app/Livewire/Owner/` incl. `OwnerReviews`, `app/Livewire/Operator/`) ·
  `app/Support/` (`Tenancy/`, `Owner/`, **`Guest/`** incl. `GuestName`/`GuestTrip`/`GuestPayments`, **`Reviews/`** =
  `ReviewRating`+`ReviewList`, `Money/`, `Events/`, `Media/`, `Pdf/`, `Folio/`, `Reservations/`, `Payouts/`) ·
  `app/Models/` (+ `Scopes/` incl. `OwnerScope`, `Concerns/` incl. `BelongsToOrganization`/`BelongsToOwner`) ·
  `resources/views/` (+ `operator/`, `owner/`, **`guest/`**, `booking/`, `pdfs/`, `emails/`) ·
  **`config/auth.php`** (the `guest` guard + its `guests` provider on the custom `guest-eloquent` driver, alongside the
  `web` guard/`users` provider — plus the separate `guests` password-reset broker) ·
  **`config/authorization.php`** (THE permission catalog + role→permission map + `back_office_roles` allow-list — the
  single source for #26/#30) · `database/migrations/` + `database/seeders/DemoSeeder.php` ·
  `tests/Feature/` + `tests/Unit/` + `tests/Support/`.

## NEXT ACTION — ⭐ SUPERSEDED by the CURRENT STATE block at the TOP of this file (2026-07-22)

> **⚠️ The authoritative next action is the ⭐ CURRENT STATE block at the top: ALL of Phase 7 is done AND Phase 8's
> email + Stripe are lit up AND the audit + demo-blocker fixes are done — so NEXT = RESUME the DESIGN-FIDELITY PASS
> (operator queue, `Rates` next; see `docs/design/DIVERGENCE-REGISTER.md`). The section below is retained for its
> Phase-7 detail but no longer names the next action.**


> **CURRENT (2026-07-22). Phases 0, 1, 2, 3, 4a, 4b, 4c, 5, 6 (a+b) AND ALL OF PHASE 7 (7a, 7b, 7c, 7d) of the phased
> plan are COMPLETE — the SaaS layer is done** — three full user-facing apps (operator · owner · guest) + a public
> storefront + **public self-signup that provisions a whole new tenant org with a free trial** + **subscription
> billing** + **OWNER TEAM (owner sub-tenancy)** + **TEAM SCHEDULE (staff rostering + time-off + a schedule PDF)** +
> hardened multi-realm auth (operator/owner `web` + a separate `guest` guard, incl. 2FA), all on the certified backend,
> ~89 styled/browser-verified screens on real scoped data under the honesty rule, now **MULTI-PROPERTY with real
> ANALYTICS, SELF-SERVE SIGNUP + BILLING, OWNER SUB-TENANCY, and STAFF ROSTERING**. Suite **895 / 7383** green on MySQL;
> PHPStan L5 + Pint clean; `migrate:fresh --seed` clean (2 demo properties, demo org on a real Essential subscription,
> demo owner + 2 sub-users + a pending invite, a demo roster week + leave + request). Suite **900 / 7409** with
> **PRODUCTION EMAIL lit up** (Postmark wired as the prod transport via ENV; test/prod separation enforced in code by
> `MailSafetyGuard`; the Phase-3 mail-failure boundary re-proven with the real transport; DNS + the one real send are
> Subhan's). Suite **921 / 7496** with **LIVE STRIPE wired TEST-MODE-FIRST across all three seams** (subscription
> billing · guest card charge · owner payout rail/Connect — webhook-driven, multi-layer idempotent, no raw PAN,
> the payout double-transfer-guarded; the live-key flip is Subhan's). The **OWNER ACCOUNT AREA** was then rebuilt
> (Profile & account + Security — the per-user identity split that fixes the sub-user 404; email re-verify;
> avatar/language/sessions/export/reversible-deactivate; #46). Invariants **#1–46**. Suite **942 / 7631**
> after a **full READ-ONLY system audit** (`docs/AUDIT-REPORT.md`, 26 browser screenshots) whose demo-blockers were
> then cleared in a targeted presentation/seed/test pass: `c0ce419` wrapped the four **unstyled operator controller-
> pages** (Reviews/Guest requests/Email previews/Email templates — bare `<x-operator.shell>`, no document `<head>`)
> in `<x-layouts.operator>` — the "static checks can't see a rendered page" lesson, live; `7cd1550` rolled the demo
> `rate_calendar` to a `today→+180d` window (storefront demos real availability, not "0 rooms"); and the
> `/livewire/update` owner-capability caveat is **CLOSED** by `OwnerCapabilityMutationBoundaryTest` (gate holds — owner
> writes are gated controller routes; NO owner Livewire component exposes a mutating action, so nothing rides
> `/livewire/update`). **Next = the design-fidelity pass** (`docs/AUDIT-REPORT.md` §4). **Guest Portal + Reviews
> complete; multi-property + real analytics; tenant provisioning + subscription billing + owner team + team schedule +
> production email + live Stripe (test-mode) ship (the Postmark live token + DNS, and the Stripe live-key flip, are
> Subhan's go-live steps).** (The dated
> per-phase detail is in
> `docs/DECISIONS.md`; the older "customer signal / don't build blind" framing below is HISTORICAL — the signal came
> and every phase since was built to the prototype.)
>
> **✅ PHASE 6b — ANALYTICS (DONE, 2026-07-21, 3 gated sub-gates `8254e26` → `df9c95e` → this gate).** The shared
> `PerformanceMetrics` + `MetricSet` layer (ADR/RevPAR/occupancy/room revenue, stay-night attribution, RevPAR = ADR ×
> occupancy exactly, reconciling to `FolioTotals` + the availability spine) → operator Insights + Reports
> (`reports.view`, per-property + all-properties) → owner Reports + Insights (owner-scoped, **earnings reconcile to the
> owner ledger**) → the real Portfolio comparison. Every figure a REAL aggregate; the invented history/narrative
> REFUSED; a thin period an honest empty state; lead-time/pace deferred "Soon" — invariant **#39**. Reconciliation +
> owner isolation mutation-proven; the fabrication-absence tested. **✅ PHASE 6a — MULTI-PROPERTY** (2 gated steps):
> `PropertyContext` + the topbar switcher + the COMPLETE `->first()` refactor (grep-clean) + property CRUD + the
> Portfolio — invariant **#38**.
>
> **✅ PHASE 7a — TENANT PROVISIONING (DONE, 2026-07-21, 2 gated steps `0c4643d` → this gate).**
> `TenantProvisioningService` atomically creates a new operator org (org + provisioned roles + founder + `Property
> Owner` + first property in ONE transaction; chicken-and-egg escaped via `runAsSuperAdmin`) + the public `/signup`
> onboarding wizard (rate-limited, neutral enumeration, founder auto-logged-in, honest "finish setup"). **BOTH
> deferred fail-closed RBAC defects FIXED, mutation-proven** — invariant **#40**.
>
> **✅ PHASE 7b — SUBSCRIPTION BILLING (DONE, 2026-07-22, 3 gated sub-gates `4a3b5cd` → `5a52696` → this gate).** The
> free-trial model: plans/tiers (placeholder prices) + `SubscriptionStatus` + the real trial (from 7a) + the Paywall +
> Checkout (company/VAT, period toggle) + access-gating (`EnsureSubscriptionActive`: lapsed → paywall, never mid-trial,
> fail-open) + feature-gating (channels Pro-only). **The LIVE Stripe charge + webhooks are ACCOUNT-GATED** — no faked
> payment, no "active" off a fake charge, no raw card. Invariant **#41**.
>
> **⬅ NEXT = PHASE 7c — OWNER TEAM.** Owner sub-tenancy — an owner invites their own users (a permission subsystem).
> Then Team Schedule / rostering (7d).
>
> **THE FULL REMAINING MAP (`docs/GAP-ANALYSIS.md` is authoritative):**
> - **Phase 7c — Owner Team (NEXT):** owner sub-tenancy (an owner invites their own users) · then Team Schedule /
>   rostering (7d).
> - **Phase 8 — account-gated integrations** as accounts land (see the ACCOUNT-GATED tail below) — incl. **LIVE
>   subscription billing** (the Stripe charge + webhooks the 7b Checkout seam is built up to).
>
> **🔒 ACCOUNT-GATED TAIL (external accounts required — CANNOT be built until obtained; deferred HONESTLY throughout
> — build the experience, NEVER fake a charge/send/verify):** the card charge / Apple Pay everywhere (Stripe live) ·
> **LIVE subscription billing** (Stripe Billing — the 7b Checkout seam is built up to the charge + webhooks) · owner
> payout EXECUTION (Stripe Connect + KYC) · live OTA sync (Channex staging) · WhatsApp / OTA inbox channels ·
> SMS (waitlist + notifications) · production email (Mailpit is dev) · guest ID verification / KYC.
> **Subhan's parallel track — START Stripe Connect + an email provider FIRST** (they unblock the most: real
> payments + the owner payout rail + production email + the review/pre-arrival/waitlist email triggers).
>
> **🪤 DEFERRED SUBSYSTEMS (real features, later — NOT account-gated, just not built yet; each stubbed honestly,
> never faked):** response-automation + review-gating (Reviews — **deceptive review-gating deliberately NOT built**;
> the honest equivalent is the guest's own "send privately to the manager") · loyalty tiers (Silver/Gold) · referral
> auto-earn (a referral credit is real only when genuinely earned) · the waitlist notify-trigger + auto-hold ·
> email-template editing · guest email-change (a re-verify flow).
>
> **✅ RESOLVED (Phase 7a, 2026-07-21):** the two fail-closed RBAC self-provision defects are FIXED — org-creation now
> provisions roles (#1), and the founder holds the assignable `Property Owner` (#2). Residual (re-scoped, separate,
> deferred): the global `Super Admin` stays unassignable — a PLATFORM-admin concern (Horizon gate), never a tenant
> founder's, needing a spatie-PK rework whenever that schema is next touched.
>
> ---
> **(HISTORICAL — the framing that led here; kept for the record.)** A warm operator contact (Fatima)
> **liked the demo AND the prototype**, so the requirement is now concrete: **the real product must work the way
> the prototype shows.** The prototype (124 designed screens) is the SPEC; the repo stays the truth for what backs
> it; every gap gets BUILT, not faked. The full phased map — **audit-verified against real code** (5-agent pass,
> 2026-07-20; 50 claims → 33 confirmed / 8 incorrect / 8 nuanced) — is **`docs/GAP-ANALYSIS.md`**.
>
> **✅ PHASE 4a COMPLETE (2026-07-20) — the GUEST AUTH FOUNDATION**, 4 gated sub-gates (`524a1d3` → `70f4d02` →
> `e2710ff` → this gate): a SECOND auth realm for guests, alongside the operator/owner `web` realm WITHOUT
> disturbing it. Guest Authenticatable on a separate `guest` guard + a keyed **blind index** (the encrypted email
> isn't queryable) · BOOKING-TIED claim + email verification (Phase-3 Verify Email wired; NO free sign-up — a
> conscious divergence) · guest login + password reset hardened (#28 through the reset token table + link) · Guest
> Account (self-only) + safe account removal (revoke access, keep records). **REALM SEPARATION** (a guest can't
> reach /app|/owner; a guest ⟂ web at guard + provider level) + **GUEST ISOLATION** (a guest sees/edits only their
> own; resolve from the guard never a request id) + **BOOKING-TIED** + **NO-ENUMERATION** all mutation-proven; the
> existing operator/owner auth chapter UNREGRESSED. A claimed demo guest (demo.traveller@vyrobel-demo.test /
> password) is seeded. NEXT = **Phase 4b** (the Guest Portal core — Trips/bookings/Account detail/etc. — on this
> foundation). Detail: `docs/DECISIONS.md` 2026-07-20 (Phase 4a).
>
> **✅ PHASE 3 COMPLETE (2026-07-20) — the notification subsystem + all transactional emails**, 5 gated sub-gates
> (`7cca65c` → `7905570` → `640f149` → `6c51271` → this gate): the notification CORE (Laravel-native, real events
> → per-user feed, isolation + idempotency) · Notification Preferences (TYPED store that GENUINELY gates delivery,
> mutation-proven, always-on security, Phase-1 maintenance toggle resolved) · the real events wired (checkout
> balance-due, out-of-service, payout/statement to owner+operator, channel error, account-security; deferrals
> precise about why) · the 9 guest transactional emails (Mailpit-real; booking/cancellation/refund/reminder live,
> the rest template-built-trigger-deferred) · owner/operator email delivery through the SAME preference gate
> (selective email defaults; mutation-proven off→no-email) + the Email Templates view (editing deferred honestly).
> **ONE account-gated piece remains for emails: a production mail provider (Mailpit is dev).** NEXT = **Phase 4**
> (guest auth is the true greenfield the ~24-screen Guest Portal sits on).
>
> **The honest position:** **~55 built screens/artifacts · ~70 designed-but-unbuilt.** What remains splits into
> ① subsystems/epics (guest auth, notifications, reviews/wallet, tenant-provisioning, Owner Rates,
> Channels-as-drawn, Portfolio) · ② the ~24-screen Guest Portal (on guest auth) · ③ account-gated (inbox
> channels, Channex, Stripe Connect/cards, prod email). **⚠️ AUDIT CORRECTION:** **multi-property is NOT a schema
> epic** — the schema already holds N properties/org; Portfolio is a property-selector + CRUD + ~8–10 `->first()`
> call-site refactors (re-scoped L→M). The three TRUE greenfield subsystems are **guest auth · notifications ·
> reviews/wallet** — multi-property is not one.
>
> **✅ PHASE 0 (fix & harden) COMPLETE (2026-07-20, `a7068d1`)** — the channel manager un-inerted, the dead
> enums resolved, the two fail-closed RBAC defects re-logged + deferred, stale docblocks fixed,
> `docs/QA-WALKTHROUGH.md` written.
>
> **✅ PHASE 1 (complete the operator app, buildable set) COMPLETE (2026-07-20)** — five gated sub-gates
> (`eefd22d` Group Booking · `1595583` Search · `35019e9` Roles · `a4a3ddc` Maintenance Costs+Settings · HEAD
> Operator Profile). Six screens, five new services (`GroupBookingService`, `SearchService`,
> `RoleAssignmentService`, `MaintenanceCostService`, `MaintenanceSettingsService`) + `OperatorProfileController`.
> The security-critical shapes held: **Group Booking** is atomic over the existing `groups` with a phantom-event
> guard (a rolled-back block fires ZERO `reservation.confirmed` events — mutation-proven); **Search** is
> tenant-scoped and searches guests by NAME only (encrypted email/phone can't be `LIKE`-searched → the
> blind-index decision is DEFERRED and the limit is surfaced, never silent); **Roles** REFUSED the design's
> catalog-edit (an escalation weapon) and enforces the **privilege-subset rule** (`perms(role) ⊆ perms(actor)`,
> so only `['*']` grants `['*']`); **Maintenance Costs** are real aggregates that reconcile to the cent (the
> fabricated labour/parts split refused, not faked); **Maintenance Settings** are typed columns via the
> `SettingsService` pattern (NOT the dormant KV scaffold); **Operator Profile** is a SELF-ONLY name/email write
> (auth-resolved, never a request id — mutation-proven a smuggled `user_id` can't reach another account). Deferred
> out of Phase 1 (logged, not faked): the **blind-index** contact-search decision, **notification dispatch**
> (→ Phase 3), and PDF/Excel **exports** of the cost report.
>
> **✅ PHASE 2 (complete the owner portal, buildable set) COMPLETE (2026-07-20)** — six gated sub-gates
> (`0dfc560` Bookings + Detail · `598cc9b` Block Calendar · `58f0b49` Owner Rates · `5119548` Tax Center + PDF ·
> `b95228f` Payout Method · HEAD Add Unit + Help). Eight owner screens + the operator Unit Requests screen + the
> Annual Tax PDF (owner 15 → 23). **Owner isolation is the phase's #1 concern and every gate proves it a different
> way:** Bookings 404 a cross-owner `{booking}` (reservations have no owner_id → scoped THROUGH the units); Block
> Calendar's write guard is mutation-proven for block AND release (and it un-latented `blockInventory` to enforce
> `assertUnitFree`); Owner Rates — the highest-risk MONEY write — scopes by **exclusive ownership** (a room type
> is editable only if EVERY unit is the owner's), mutation-proven both ways, DIRECT edit with the no-approval
> divergence recorded; Tax Center's payout HOLD is genuine (refused at the money path, released on tax-ID save —
> mutation-proven), tax_id encrypted; Payout Method's IBAN is encrypted + masked, self-only across read/set-default/
> remove; Add Unit is an owner REQUEST (not direct billable-inventory creation), operator-visible. New shared rule
> `OwnerLedgerAttribution` (the ONE "Net to you"); + `OwnerBlockService`, `OwnerRateService`, `OwnerTaxService`,
> `PayoutMethod`/`Iban`, `UnitRequest`. Deferred (logged, not faked): Stripe Connect payout EXECUTION
> (account-gated), owner-rate/unit approval workflows, the operator's unit-creation-from-a-request (their action).
>
> **NEXT = PHASE 3** — the NOTIFICATION subsystem + all transactional emails (events → dispatcher → in-app feeds +
> preferences → the ~9 email templates + triggers; live sending flips on when the provider account exists). This
> completes the operator+owner apps (**make Fatima operational soonest**) before the large Guest Portal phase (4).
> See `docs/GAP-ANALYSIS.md` for the full 0–8 plan + the corrected per-front inventory.

**(historical) The original Wave-4 lead-in:**

**WAVE 2 IS COMPLETE** (2026-07-17, five gated sub-gates, `4eb71e2` → `cb8bcce` → `44444e6` → `f86cf4e` →
HEAD): **Reservations list · Cancelled/No-show terminal states · the Refund screen · the Guests CRM list ·
the Guest profile.** Suite **511 tests / 5503 assertions**, PHPStan L5 0 errors, Pint clean,
`migrate:fresh --seed` clean, browser-verified at every gate — including a **real profile write** that
survives a reload, with the phone ciphertext at rest and the audit trail holding only the field NAMES.

1. ✅ **DONE — the WAVE 2 REGISTER's security/integrity items are CLEAR** (2026-07-17, three gated fixes:
   `f4a8913` #29 · `9123ee7` #30 · `94ef7ad` the erasure trigger; plus the flaky test root-caused). They were
   deliberately done BEFORE Wave 3 **so new screens inherit correct enforcement rather than needing a
   retrofit** — a Wave 3/4 write route now simply carries its `can:<permission>`, and its control is
   `@can`-gated, because the pattern is established and tested. What remains in the register (the dead enums,
   the missing inbox permission, the two fail-closed RBAC defects) is **small and none of it blocks a build**.
2. ✅ **DONE — Wave 3, the Owner Messages inbox** (2026-07-17, `49ab9a6`). ⚠️ **And it was NOT
   presentation-only, though both planning docs said so** — `TIER1-SPEC` rated it READY and `ROADMAP` called
   it 🟢 M. `message_threads` had no `owner_id`, so `BelongsToOwner` could not be applied and `OwnerScope`
   **never ran**: the screen as specced would have shown every owner every guest conversation and every other
   owner's threads. **The lesson for Wave 4: a UI-shape match is not a backend match. Audit the model's
   scoping columns before trusting a READY rating.**
   **NEXT — Wave 4** per `docs/TIER1-SPEC.md`: Rates + Channels UI, New/Group Booking, Portfolio, Block
   Calendar, Roles/Settings. **2FA is now DONE** (2026-07-18 — invariant #31); the 2 remaining
   NEEDS-BACKEND-FIRST items (Global Search, Maintenance Settings) last.
   ⚠️ Two register items bear on Wave 4 directly: the **dead enum values** (a status-keyed UI renders never,
   silently — Rates/Channels are status-heavy) and the fact that **New/Group Booking has schema already**
   (`groups`, `reservations.group_id`) — extend it, never build a parallel structure.
   ⚠️ **Global Search still needs the blind-index decision** — the Guests CRM proved the constraint concretely:
   it SHOWS contact detail it cannot SEARCH, because encrypted TEXT is never indexed by value.
3. **In parallel — CUSTOMER VALIDATION is the highest-value NON-BUILD move, and nothing else changes that.**
   The wedge demos at full depth (statement → remittance PDF → payout run → the owner sees it paid,
   reconciling to the cent), operator daily-ops runs end-to-end (reservations → refund → guest CRM), and the
   operator/owner boundary is now genuinely enforced — **safe for a real owner login**. ⚠️ **State it plainly:
   NO CUSTOMER HAS EVER SEEN THIS PRODUCT.** The buildable-in-a-vacuum core is essentially complete; what
   remains is security-hardening (1), account-gated (needs someone else's credentials), or **speculative**
   (Tier 2: Guest Portal ~24, Analytics, Reviews, Billing, Notifications, Emails — designed, no backend, and
   no evidence anyone wants them). Every additional Tier-2 screen is a bet placed without signal.
   *(There is no outreach material in this repo — no demo script, no cold-email draft. If any exists it lives
   outside version control; do not assume it.)*

---

## META-LESSONS (earned expensively — re-read before any build)

These are not platitudes; each one is here because it caught a real bug or prevented a real lie, repeatedly.

- **(a) A BRIEF IS A HYPOTHESIS; THE REPO IS TRUTH.** Building has caught roughly a dozen confidently-stated,
  factually-wrong brief assumptions — `AssignmentService::terminate()` "throws on an illegal transition" (it
  has **no guard at all**); events belong in controllers (**no controller in this repo calls a recorder —
  services do**); `Note` was morph-mapped (it was **not** — the first `record()` on it would have thrown);
  `statement_id + room_revenue` reconciles to `gross_minor` (it does not); `owners.user_id != null` means
  portal access (it does not); "9 screens" in Wave 1 (the tables always said **10**); the existing
  `two_factor_secret` column was safe to reuse (it was **plaintext** — the 2FA build had to encrypt it); the
  certified channel manager works in production (**nothing writes `channels.status`** — it is inert without a
  connect/activate flow, found 2026-07-18 by grep). **Audit first, every time. Echo the plan against real code before writing any of it.**
  - **⚠️ AND THE COROLLARY THAT COST US: "VERIFY" MEANS MEASURE THE RIGHT THING.** The 2026-07-17
    consolidation "caught" a brief claiming **44 permissions** and *corrected* it to **39** — citing the repo.
    **The brief was right and the correction was wrong.** 44 is the catalog (`config('authorization.permissions')`);
    39 was the union of permissions named across roles — a *different question*, confidently mistaken for the
    same one, then written into a security invariant and quoted here as proof that briefs lie. A measurement
    that disagrees with a claim has not refuted it until you have checked you measured **the same thing**.

- **(b) GREEN TESTS ARE NECESSARY, NOT SUFFICIENT.** **Both** critical security bugs — the €1,107 over-refund
  on a €369 payment, and the `/app` leak of every owner's financials — were found by **READING and
  inventorying**, never by a test failing. The fully-certified suite was green over both, for weeks. A suite
  proves the things you thought to ask; it cannot tell you what you never asked.
- **(c) ADVERSARIALLY RE-READ EVERY MONEY AND AUTH PATH.** That is the technique behind (b). Regression tests
  structurally cannot find a missing invariant — only someone asking "what bounds this?" can. #27 exists
  because someone asked what bounded the SUM when #7 only bounded the EDITS.
- **(d) EXTRACT THE SHARED DEFINITION, AND PROVE IT IS SHARED.** Two `match`es for one concept WILL drift.
  This session caught **three screens silently disagreeing** about the same guest (the inbox said "2 stays ·
  guest since 2024" where the CRM said "1 stay · guest since 2026" — both sincere). The proof matters as much
  as the extraction: assert the two callers return identical results, and mutate the helper to prove the
  screen reads it.
- **(e) BROWSER-VERIFY EVERY SCREEN.** Raw Blade shipped to users (`word@directive` — the `\B` rule) and a
  Livewire prop shadowing view data (a 500) both passed `php -l` **+ PHPStan + Pint + a Blade compile**.
  Static analysis cannot see a rendered page. Run the suite **and** open the thing.
- **(f) EVEN THE DOCS NEED SKEPTICISM — INCLUDING THIS FILE, INCLUDING THIS SECTION.** Two proofs, both from
  the same consolidation: a docs-sweep agent hardened a hedged "~26" into an unhedged "42" and attached a "no
  overlaps, so it reconciles" guarantee that its own next sentence refuted; and the pass that wrote *this very
  lesson* simultaneously "corrected" a TRUE "44 permissions" into a FALSE "39" and enshrined it inside a
  security invariant (see (a)'s corollary). **Writing down "be skeptical" provides no immunity.**
  **Load-bearing hedges are load-bearing.** The only thing that actually caught either error was **three
  independent blind readers sent at the finished docs with instructions to disprove them** — no amount of
  careful self-review did. Do that before calling any consolidation done.
- **(g) SHIP THE ERASE WITH THE WRITER, AND THE GUARD WITH THE GATEWAY.** A latent GDPR gap becomes real the
  moment you build the thing that writes; an ordering detail (guard **before** the gateway call) is the
  difference between rejecting and moving real money then rejecting.
- **(h) A UI-SHAPE MATCH IS NOT A BACKEND MATCH — audit the model's scoping columns before trusting a "READY"
  rating.** Wave 3 (the owner inbox) was rated presentation-only by BOTH planning docs, yet `message_threads`
  had no `owner_id`, so `BelongsToOwner` could not apply and `OwnerScope` **never ran** — building it as specced
  would have shown every owner every other owner's (and every guest's) conversation. A screen that looks like a
  read is a security build if the data isn't scoped for it. **Trust the schema, not the mockup.**
- **(i) SECURITY FIXES HAVE A METHOD, and it is not "add a check".** Every one of this session's five holes
  followed it: **audit-first** (read the real code, find who actually calls the path — `terminate()` had two
  callers that *disagreed*); **put the guard where ALL paths are covered** (`refundPayment()`/`terminate()`
  guard in the single service method, not per-controller; authorization on the ROUTE where a permission is a
  request fact, deliberately unlike the data-bound service guards); **mutation-prove it** (write the test FIRST,
  watch it fail, fix, watch it pass, then strip the guard and watch it fail again — a guard with no red run
  proves nothing); **fix the fixtures, never the check** (a test that fails because its actor lacks a permission
  is telling you to fix the actor); **defense-in-depth** (#26 was two independent layers, each proven to block
  alone). And #31's variant: **prefer relying on a guard that already exists** (the pending state is rejected by
  the standard `auth` middleware) over inventing a new one that a future route could forget.
- **(j) WHEN A TEST FLAKES, SUSPECT THE ASSERTION BEFORE THE APP.** Both flakes closed this arc were the same
  disease — a needle too generic to distinguish the bug from a coin flip (a raw faker name vs escaped HTML; a
  2-char unit code vs random CSRF tokens). The app was correct both times. **A needle must be one that could
  ONLY appear by the bug; CAPTURE the failing run, do not re-run until it hides.** (Detail in the register.)

---

## (historical) NEXT ACTION — WAVE 1 — ✅ DONE 2026-07-16

Removed 2026-07-17: it was a full stale copy of the Wave-1 build plan that still read as pending and
contradicted the live NEXT ACTION above by two waves. Wave 1's record lives where it belongs — the dated
entry in `docs/DECISIONS.md` (2026-07-16) and the Wave 1 tables in the styled-surface inventory above.

---

# (N.12) 🏁 REALM CLOSE — what is DONE, what is SCOPED-BUT-UNSTARTED, and what remains

## The record

| realm | prototypes | state | range |
|---|---|---|---|
| **OPERATOR** | 42 | 🏁 design-matched (34 gates) | `0aa9ede` → `edb7cce` |
| **OWNER** | 32 + the invite email | 🏁 **REALM COMPLETE** | `6d974d7` → *(this gate)* |
| **GUEST** | 32 | ⬅ **UNTOUCHED** | — |
| *(shell)* | 19 | folded into the two realms | — |

**Owner gates 16–32** (all verified present at SAVE v7): 16 Payout Method `0ac7080` · 17 Balance `cb59c13` ·
18 Bookings `5a84065` · 19 Booking Detail `b297f0a` · 20 Booking Summary PDF `bf8f8c2` · 21 Remittance PDF
`074a054` · 22 Annual Tax PDF `4f8ea65` · 23 Block Calendar `899e9e3` · 24 Archive `d5d23bd` · 25 Reports
`96cb0bb` · 26 Reviews `2c3d1de` · 27 Messages `8f446f0` · 28 Help `ed3ffad` · 29 Onboarding `11464fa` ·
30 Expenses `932468a` · 31 Tax Center `a2d5a9e` · 32 Agreement `a884793`.

⚠️ **A record inconsistency, noted not fixed:** `MISSING-BACKEND-BY-PAGE.md` headings switched format
mid-pass — **`§O1`…`§O19`** then **`§O-20`…`§O-32`**. All 32 entries are present; a grep must allow both forms.

## ⚠️ SCOPED BUT UNSTARTED — three owner items that are NOT design work

1. **O26's Reviews contrast** — measured at O27 once the tool recovered: **30 failures, ~17 of them the ★
   spans not migrated to `<x-star-rating>`**. The fix is scoped; it was deliberately not folded into a later
   page gate.
2. **DO10-4 — the star migration**: 10 remaining call sites (guest/operator/public/**email**) + O26's own two
   spans.
3. **The realm-wide PDF-contrast sweep** — see below.

## 📌 THE PDF-CONTRAST SWEEP — scoped, recommended as ONE gate, awaiting go-ahead

The pattern is identical on every document measured: **`#8A8079` → `#5F5853`** (3.86 → 6.98) and
**`#A69A92` → `#6C645F`** (2.74 → 5.79), each verified on the **generated file**, each guarded on the rendered
document. **5 owner documents are clear** (statement · booking summary · remittance · annual tax · agreement).

~~**11 templates remain**~~: `owner-agreement-amendment` · `owner-booking-confirmation` ·
`owner-statement-history` · `operator-report` · `maintenance-costs` · `arrival` · `folio` · `guest-statement` ·
`guest-vat-invoice` · `guest-credit-note` · `guest-refund-receipt` — plus `PdfService`'s `#a99b8e` stamp.

> 🏁 **SUPERSEDED — the PDF-SWEEP GATE closed all of this (2026-08-12, `1c1f370`).** ⚠️ **And this enumeration
> was one short: 5 clear + 11 listed = 16, but there are 17 templates — `operator-schedule` is missing from
> the list.** The sweep found the same mapping this section predicted (`#8A8079`→`#5F5853`,
> `#A69A92`→`#6C645F`) — **no drift between the owner precedent and the realm-wide fix** — and closed the
> stamp too: it is the **export-AUDIT stamp**, informational, in the bottom margin, so **fixable rather than a
> founder decision** (`#5f5853`, 6.98). ⚠️ Its path is `app/Services/PdfService.php`, **not**
> `app/Support/Pdf/`. **224 distinct inks across 15 rendered templates → 0 failures.** See §PDF-SWEEP + §N.13.

**Why ONE dedicated gate:** the watermark lives in `PdfService`, which **no page gate may touch** (so it would
otherwise never be fixed); each template needs its **own generated-file measurement** (O21's `#B26A08` passed
on white at 4.24 and **failed at 3.72** on the plate it actually sat on); and the guest/operator documents are
cross-realm. **Not started — the founder's go-ahead.**

## ⚠️ D16-7 — IN-FLIGHT / HELD MONEY: four forms, one root, one REFERENCE

**Root:** the payout-status model does not distinguish `processing` / `held` / `pending` cleanly on the
surfaces that report money.

- **O3** — `processing` dropped from the KPI tiles.
- **O14** — the shared **`payoutStatusMeta`** (`app/Livewire/Owner/Concerns/FormatsOwnerData.php:99`)
  **collapses `processing` + `held` into "Payout pending"** on Payouts, Documents, Statement and Dashboard.
- **O21** — the remittance status **pill had CSS rules for four of the enum's six values**, so `processing`
  and `held` rendered with **no plate at all**. (Labels stayed honest.)
- **Cross-surface** — one `held` payout reads **three ways**: "On hold" · "Payout pending" · "Held".

⚠️ **THE REFERENCE: `OwnerBalance::rowStateMeta()` (O17) already gets this RIGHT** — On hold = persisted
`held` + paused pending; In transit = `processing` + released pending. **The gate should COPY it into
`payoutStatusMeta`, not design a fix**, and give the pill rules for all six enum values. Archive is on the
honest side already (it reads the *statement* enum via `statusMeta` — O24). A Stripe-live prerequisite; the
founder's call.

## ⚠️ OPEN FOUNDER DECISIONS — surfaced, NOT decided

- ~~**G.1 BILLING** — the one open go-live blocker: two contradictory pricing models (per-unit €72 vs tier €49,
  break-even ≈5.4 units) and only the tier price reaches Stripe → **~2× billing at the live-key flip**.~~
  ⚠️ **RESOLVED AT SAVE v12 (2026-08-13) — AND IT WAS NEVER A DECISION.** The "contradiction" was an artefact of
  two hardcoded models coexisting; pricing is configurable data and a plan declares its own shape (§N.17.1).
  The billing code is complete and honest; **what remains is the founder's Stripe DASHBOARD work** (enable
  Stripe Tax + jurisdictions · create the Prices · fill prod env) — see §G.1. Still genuinely open here:
  **#6** the rate guardrail and **#10** the Essential team limit.
- **G.4 / D16-7** — a `processing`/`held` payout invisible in KPIs on both realms (fix = copy
  `rowStateMeta()`).
- **G.5 / Owner-5** — the "earned this month vs statement" figure definition.
- **Owner-20** — the cross-surface **"nights under a gross line"** call: the statement screen prints
  *occupied* stay-nights (8) while the Booking Summary PDF prints *revenue-attributed* nights (6). **Both are
  real and honestly derived**; which belongs under a gross figure is a product decision.
- **DO22-1** — whether the tax document / Reports / Tax Center carry formal legal *"EXAMPLE rates"* / *"not
  tax advice"* wording. ⚠️ Tax Center (O31) structurally needs it least: it shows **no rate and no computed
  determination**.

## ⚠️ THE BASELINE — a NON-DETERMINISTIC 5–10 RANGE (§J.quater)

Documented non-green set: **`BookingImportTest` ×6** (passes in isolation, fails only in-suite — ordering),
**`OwnerUnitDetailViewTest`** and **`StubViewsTest` ×2** (date-coupled). **Re-measured after the invite-email
gate: `1860 tests · 1851 passed · 12,160 assertions` — 9 non-green, exactly the documented set** (+3 tests,
+28 assertions = the three new invite guards). **Measured at realm close: `1857
tests · 1848 passed · 12,132 assertions` — 9 non-green, the documented set.** PHPStan L5 (0); Pint clean.
**Prove any NEW failure pre-existing by STASHING** — the count moves with run order and the clock, not with
the work.

## ⚠️ WHAT REMAINS — the runway

1. ~~**Owner Invite Email** (`OwnerInviteMail`)~~ — 🏁 **DONE. THE OWNER REALM IS COMPLETE.** See
   `MISSING-BACKEND-BY-PAGE.md` **§O-Invite**. Four things this gate established that the page gates could
   not: **(a)** an email cannot reference a `--vy-*` token, so every literal is a permanent drift risk —
   all nine were verified against the live declarations, and a guard now fails if one rots; **(b)** the
   computed contrast probe **false-PASSED the one element that mattered** — `getComputedStyle` scores text
   against `background-color`, so the wordmark on the gradient band read 5.18 (the `bgcolor` *fallback*)
   when the rendered pixel is **3.18**; three successive pixel-sampling probes were themselves vacuous
   before one was sound (§N.8e, in a new medium); **(c)** the prototype's gradient CTA is **unreachable**
   for AA — no warm orange lighter than `#C2410C` clears 4.5 for white normal text — so parity was refused
   rather than bought with a regression; **(d)** ⚠️ **I introduced a raw-Blade regression and an existing
   test caught it** — extending `emails/layout.blade.php`'s docblock closed the comment early with a stray
   `--}}`, shipping the next paragraph as live template text into all 15 emails that extend it.
   `OperatorPropertySettingsGate1Test` went red on *"not to contain 'Powered by Vyrobel'"*. The
   raw-Blade family is not only `word@directive`.
2. **THE GUEST REALM — 32 prototypes. ⬅ IN PROGRESS: `G1 Trips` · `G2 Reservation` · `G3 Arrival+PDF` · `G4 Check-in` · `G5 Manage+Add-ons` · `G6 Payments+Receipt` · `G7 Messages` · `G8 Review` · `G9 Account` · `G10 Wallet+Saved` · `G11 Auth` · `G12 Storefront` DONE — 🏁 **ALL 12 GATES; THE GUEST-REALM PAGE WALK IS COMPLETE** (operator 42/42 · owner 32/32 · guest 12/12).** Mapped in
   `docs/design/GUEST-RECON.md`; per-gate detail in `MISSING-BACKEND-BY-PAGE.md` **§G-1**. The no-Alpine GET
   realm; `#33` door-code window-gating; real guest PII on every surface — expect `#28` to dominate over `#17`.
   ⚠️ **G1 established the realm's CONTRAST METHOD, and G2–G12 must use it:** `getComputedStyle` cannot
   measure this realm — every guest view sits on the page gradient, so ancestor-walking finds no opaque
   background and defaults to white (it reported `rgb(255,255,255)` for **all 36** gradient-backed runs and
   scored white-on-orange as 1.00). Instead: stash each run's colour, blank all glyphs, screenshot the
   **background plate**, read boxes **from that same render**, sample the **central region excluding
   element-child boxes**, and score against the **worst pixel**. ⚠️ **The probe was wrong three times before
   it was right** (rounded corners belong to the parent · a 20px reflow between box-capture and screenshot ·
   a child of a different colour), so it carries **two validation anchors**: white on `--vy-grad` ≈ **3.4**
   and white on solid `--vy-accent` = **exactly 5.18**. If both do not hold, publish nothing.
   ⚠️ **The GATE-3 records are leads, not conclusions** — G1 found its row denying four shipped features
   (§N.8b, eighth instance) and corrected it in place, exactly as the recon predicted.
   ⚠️ **G2 added three rules the later gates need.** (a) **A token verified on one page is NOT verified** —
   `--vy-amber-text` passed at 4.70 on Trips and FAILED at 4.37 on Reservation, because these badges are
   translucent on a translucent card over the gradient and their contrast moves with the backdrop; G1's
   token was darkened and G1's comment corrected in place. (b) **O32 cuts both ways** — a passing mutation
   can mean REDUNDANCY, not a vacuous test: the door code is gated twice (ArrivalPack *and* the view), so
   only removing BOTH produces a real leak. (c) **Check which VARIANT rendered** — three `.vy-btn`s
   measured on G2 were all `--ghost`; the primary only draws when a balance is due, and reporting the
   ghosts would have falsely 'confirmed' the one item G1 left unverified.
   ⚠️ **G3 closed the #33 gate proper, and it is the realm's STRONGEST area, not its weakest.** The PDF
   builder genuinely DELEGATES to `ArrivalPack` (the first docblock this pass has checked and found
   TRUE); **both** surfaces are double-gated (the premise that the PDF has no view re-check is false);
   and this file's fixtures already assigned a unit, so the systemic vacuous-#33 trap that caught G1 and
   G2 did NOT apply here. What was actually wrong was COPY: the screen and PDF disagreed about the unlock
   date, and the PDF footer asserted 'live from check-in' when the engine releases it LEAD_DAYS earlier —
   the third instance of the G2 mock-value family. ⚠️ Also: the same-token-different-plate failure
   recurred a THIRD time (ghost button: 5.03 on the card, 4.41 on the wash), so treat 'same token, new
   backdrop' as a STANDING re-measure, not an occasional one.
   ⚠️ **G4 (the first inbound-PII surface) — the privacy invariant HELD, the a11y did not.** All four
   check-in stores (`guests.phone`, `notes_guest`, `arrival_window`, the encrypted ID document + bytes)
   are reachable by `anonymize()`, each proven by removing it and watching the erasure suite go red —
   nothing to log. What failed was the FORM: **Enter in the phone field on step 1 completed the whole
   check-in** (one form, three steps, one submit — implicit submission fired the real write and stamped
   `guest_checked_in_at` before the guest saw the ID or arrival steps), and **the ID upload was
   keyboard-unreachable** (`hidden` = display:none, label not focusable). Both fixed and mutation-proven.
   ⚠️ Also: **a token moved at G2 and this page didn't**, because it hardcoded `#8f6414` rather than
   referencing `--vy-amber-text` — five literals in all. When a shared token changes, grep the realm for
   its OLD value, not just its call sites. And the worst contrast readings on a PII form were its own
   **field labels** (2.99).
   ⚠️ **G5 (the request-write pair) — the request model is sound; the COPY promised a right that does not
   exist.** `GuestChangeRequest` is a real pending→operator-only machine, no manage POST touches the
   reservation, and a pending request renders amber-pending (never a green plate or a struck-through
   booking) — §N.8d holds both directions. But the extras page promised **"Free to cancel up to 24h before
   arrival"**, shipped verbatim from the prototype, with **no cancellation window and no removal path
   anywhere** — the fifth mock-value instance and the first to fabricate a RIGHT rather than restate a
   constant. Removed. Also: **Enter on the cancellation reason fired the request with no chance to
   reconsider** (guarded with the repo's own destructive-submit idiom), and the add-on toggles were
   focusable but showed **no focus ring** (opacity:0 input, no card indicator — WCAG 2.4.7).
   ⚠️ Two probe/assertion errors caught in my own work: an assertion that tested the shared STYLESHEET
   rather than applied markup (`.vy-badge--confirmed` is defined on every page), and a global token
   replace that recoloured a BORDER — text tokens are for text. And a WCAG-exempt disabled control
   (2.24) is reported as exempt, not counted as a pass or silently dropped.
   ⚠️ **G6 (the money surface) — #17 and #45 both hold; the gap was the SILENCE after checkout.** The
   figures reconcile to the cent on the rendered page (1107.00 − 922.50 = 184.50; the receipt ties to the
   hub), one source (`FolioTotals`), no Blade arithmetic; the pay form's only field is `_token` (zero card
   inputs — SAQ A verified in the DOM). ⚠️ **The PROTOTYPE's money label is wrong and the live is right**:
   the mock defines `Paid = charged − refunded`, the engine `paid = received − refunded` — the mock would
   report an unpaid booking as paid. ⚠️ **§N.8d's UNDERSTATING half**: Stripe returns to `?paid=1` but the
   folio settles on the webhook, and the page read nothing from that return — a guest back from a
   completed checkout saw an unchanged balance, no acknowledgement, and could pay again. Fixed with copy
   that asserts nothing that could be false (`?paid=1` is a URL anyone can type). ⚠️ **The worst leak was
   unguarded**: the `/pay` POST had no foreign-id test — only that the route appeared in HTML. Now
   mutation-proven. ⚠️ **`DO-G2-2` was a PREDICTION at G2 and is now confirmed by measurement** —
   `--vy-amber` as text at five sites, including a receipt's balance at 4.11, **and one I missed at G5**.
   ⚠️ **G7 (the #28 boundary from the guest side) — the isolation is STRUCTURAL, and there is no visibility
   filter because there cannot be one.** `messages` has NO internal/visibility column at all; staff
   annotations are `notes` rows on a different table the transcript never joins, and owner threads carry
   `owner_id` with no `guest_id` (the O27 pattern). **A table the query never touches beats a flag that can
   be forgotten** — the third structural refusal after O27 and G3's delegation. Proven anyway by seeding a
   REAL staff note via `InboxService::addNote()` and rendering the thread's notes as a mutation.
   ⚠️ **The first attempt at that mutation silently did not apply** (the anchor was `@forelse`, not
   `@foreach`) and reported a meaningless 'passed'. **A mutation that fails to apply looks exactly like a
   guard that holds** — check that the edit landed, not just that the suite went green.
   ⚠️ §N.4k was **safe by construction** for the third time (textarea never implicitly submits; after G5's
   GET preview and G6's no-text-input pay form) — worth naming as a pattern, because it is the cheapest
   safety and the easiest to destroy by accident.
   ⚠️ **G8 (guest-authored PUBLIC content) — DO10-4's '10 sites' was an OVER-COUNT.** It counted every ★
   glyph; a 5-star RATING ROW is the only thing `<x-star-rating>` can replace. **The real scope is THREE**:
   `owner/reviews` (done at O26), `guest/review` (done here), and **`owner/unit-detail:338`, which uses
   `round()` — a 4.6 average renders FIVE filled stars.** That is the Owner-26 defect still live, and it is
   all that remains of DO10-4 (`DO-G8-1`) — ✅ **since CLOSED, `ee6be74`; and the later coverage sweep found
   four more star sites this count missed (§N.14.1)**. Everything else is a single glyph beside a number, a picker
   input, or an unrelated icon. ⚠️ **The shared component's DEFAULT COLOUR fails** (#E8A63A = 2.05 vs the
   3:1 non-text bar — a star is the visual carrier of the rating); passed a colour at the call site and
   logged the default for the owner page to measure, rather than changing a shared default from inside
   another realm's gate. ⚠️ **§N.4k, highest stakes yet: Enter on a focused star PUBLISHED the review** —
   a textarea is safe by construction (G7) but a radio group is not, and this content is visible to other
   travellers. ⚠️ **An existing guard broke and the MARKUP gave way, not the guard** — an `aria-label`
   split the contiguous `value="4" checked` that a load-bearing deep-link test asserts; attribute order
   changed instead of weakening the assertion.
   ⚠️ **G9 — THE `anonymize()` REACHABILITY THREAD IS CLOSED.** G4/G7/G8 each proved `anonymize()` COVERS
   its store, but each proved it by calling `anonymize()` DIRECTLY — nothing tied the guest-facing delete
   to those stores. ⚠️ **The delete does not call `anonymize()` at all**, and that is correct: it stamps
   `scheduled_deletion_at` 14 days out (undoable), and a DAILY COMMAND runs the sweep — a **three-link**
   chain whose middle link is the single `schedule:run` cron `CLAUDE.md` warns about. A new guard drives
   the real chain end-to-end and asserts all three gates' PII is gone; **both links mutation-proven**.
   "Delete" means **anonymize-in-place**, never a row destroy (consistent with the operator-side rule).
   ⚠️ The 8-form §N.4k sweep found **no consequential mis-fire — the first gate since G4 needing no new
   confirm guard**; the account-delete is protected by a **typed word**, a legitimate third pattern beside
   safe-by-construction and the confirm idiom. Two real a11y finds instead: a toggle with **no accessible
   name and no state**, and the **fourth recurrence** of the opacity:0-invisible-focus pattern.
   ⚠️ **G10 — A POLICY THE CODE NEVER IMPLEMENTED.** The wallet's "How credits work" card made three
   claims; **two were false**. "Credit comes from refunds, cancelled add-ons…" — only `redeemCode()` writes
   a credit in production (a refund returns money to the CARD; `TYPE_ADD_ON_CANCELLED` has **no writer**),
   and the demo looked right only because the seeder posts one directly. "Most credits are valid for **12
   months**" — **no such rule exists**: `credit()` defaults to NULL (never expires). ⚠️ **It is a prototype
   literal shipped as fact — the SAME failure `GuestReservationTest` already guards for the door code's
   "48h", recurring in another file.** The page disproved itself on screen: a credit expiring in 60 days
   printed directly above the claim. §N.8d in **both** directions in one card. Also: an EXPIRED credit
   rendered in the same green "+" as a live one though it does not count — the visual contradicting the
   balance above it. ⚠️ **DO-G6-2 CLOSED** (`--vy-amber` as text = 4.08 → `--vy-amber-text` = 6.15) — and
   the amber I thought contradicted it lives in a partial **included nowhere**, so the claim was ACCURATE
   (the 2nd §N.8b claim to survive verification, after G3). ⚠️ **New for §N.8e: a CONDITIONAL colour is
   invisible to the probe until its condition is true** — the expired chip had to be injected to be
   measured, and that is why it survived earlier sweeps. ⚠️ **The keyboard probe lied too** — 7 unlabelled
   controls reported as named because it fell back to option text and `name`.
   ⚠️ **G11 — A DIFFERENT LAYOUT, AND A CREDENTIAL WITH AN UNDEFENDED LIFETIME.** The auth chapter is NINE
   views on `guest/auth/layout.blade.php` (not three, and it includes unsubscribe + guest/home); there is no
   separate email-verify route — **`claim.verify` IS the verification**. ⚠️ **None of the shell's G1–G10 fixes
   carried**: the layout declares no text tokens at all, and both headline fixes were here unfixed — the
   button label measured **3.88 → 5.18**. ⚠️ **But one shell fix was deliberately NOT copied**: `--vy-accent`
   as link text FAILED on the shell and PASSES here (4.80–5.11) because this card is lighter — *a translucent
   token has no fixed contrast* cuts BOTH ways, so the measurement decides, never the precedent.
   ⚠️ **THE GAP: the claim link's EXPIRY was undefended.** Tampering and a wrong hash were tested; expiry was
   not — so swapping `temporarySignedRoute` for a permanent `signedRoute` left EVERY test green while turning
   every old claim link into a **permanent account-takeover key** and the page's "expires in an hour" into a
   lie. Now driven across the boundary (200 at 59 min, 403 at 61) and mutation-proven.
   ⚠️ **AN O32 CATCH — a mutation that PASSED and proved NOTHING.** A regex written for
   `->middleware(['signed'])` missed the real `->middleware('signed')` and stripped the first two quoted
   occurrences instead — **login-approval and unsubscribe**, silently disarming a guard in a file I wasn't
   running. The edit "landed"; the run passed; it demonstrated nothing. **G7's silent mutation and O32's
   passing mutation are one failure from two sides: verify the edit landed ON THE TARGET.**
   ⚠️ **DO-G11-1** — an EXPIRED claim link (the ordinary case) 403s in middleware, so the guest never reaches
   the honest "link isn't valid" page and instead sees the OPERATOR's *"your role doesn't include this
   area — ask an administrator on your team"*. The honest page exists; only the rarer paths reach it. The
   docblock claiming otherwise is corrected in place.
   🏁 **G12 — THE LAST GATE. THE STOREFRONT IS TWO STOREFRONTS, ON A THIRD LAYOUT, SCOPED BY SLUG NOT
   DOMAIN.** An org landing (`/stay/{org}`) + a per-property engine (`/book/{property}`); both resolve the
   tenant from the ROUTE SLUG into REAL tenant context, so isolation is the certified `OrganizationScope`.
   ⚠️ **THE GATE-4 "at-parity / intentional divergence" ROW WAS STALE — the G1 pattern again, UNDERSTATING.**
   Every element it called a scope divergence (multi-property collection, towns, featured stays, "browse by
   vibe", the review wall) now EXISTS and is REAL; corrected IN PLACE. ⚠️ **THE GAP: the SELL scope.** Tenant
   isolation was well covered and non-vacuous, but `properties.status` was defended by NOTHING — deleting
   either active-check left the suite green while re-publishing a withdrawn property's listing AND its whole
   booking funnel (one middleware line gates browse/room/search/checkout/quote). Both layers now
   mutation-proven. ⚠️ **A PROBE FAILURE I CAUSED AND ALMOST PUBLISHED:** `animation:none` reverts a
   `.vy-fade` element to its base `opacity:0`, so the plate showed white through every animated card — **34
   false failures at exactly 1.00**, the same stuck-opacity mechanism as the Owner Statement bug, reproduced
   by my own instrument. **Freeze by COMPLETING animations (`duration:0.001s` + `fill-mode:forwards`), never
   by removing them — and assert 0 elements remain transparent before capture.** ⚠️ **The Owner-26 star bug
   is STILL LIVE**, found from the storefront: `ReviewList::stars_off` returns the FILLED ★, so the owner
   Reviews LIST renders five identical stars separated only by colour (WCAG 1.4.1) — G8 fixed the hero, the
   rows below kept the old pair. Fixed at the one definition. ⚠️ **The invisible-focus family reached FIVE
   occurrences across TWO mechanisms** (`opacity:0` at G5/G8/G9; an explicit `outline:none` here) — a firm
   §N.8f shared-fix candidate. Contrast: landing 19→0 (85 runs), browse 3→0.
   🏁 **THE PDF SWEEP — the first CROSS-REALM gate, and the one pass allowed to touch `PdfService`.**
   ⚠️ **17 templates, not 11**, and `app/Support/Pdf/PdfService.php` **does not exist** (it is
   `app/Services/PdfService.php`). ⚠️ **THE "WATERMARK" IS NOT A WATERMARK** — it is the export-AUDIT
   stamp ("Downloaded by X on Y · Confidential"), a footer line in the bottom MARGIN, not a faint mark
   behind content. So it is **INFORMATIONAL and FIXABLE, not a founder decision**: darkening it defeats
   nothing, because there is no content for it to compete with. Measured on real ink at **2.70 on paper and
   2.41 on a warm footer band** (the recorded 2.70 was the optimistic case) → `#5f5853` = **6.98**.
   ⚠️ **What was NOT fixed:** `position:fixed` makes its plate NON-DETERMINISTIC — it prints over whatever
   the page ends with (DO-PDF-2). Surfaced, not forced.
   **§N.8f confirmed by grep:** §G-3 fixed `#8A8079`/`#A69A92` in the ARRIVAL template only (verified: 0
   occurrences there — the 5th §N.8b claim to survive); the other sixteen kept both. One mapping across all
   templates onto greys ALREADY in those documents — the palette SHRINKS (4 greys → 2). **224 distinct inks
   → 0 failures.** Greyscale-safe (states print as WORDS; that template's own comment is accurate).
   ⚠️ **THE FINDING: a guard layer no test could see.** Removing `ArrivalPack`'s data-level door-code gate
   left **25 tests green**, including the one named "withholds the door code… (#33)" — because BOTH views
   independently re-gate on `$pack['released']`. Nothing leaks today; the point is the second layer could go,
   or a new surface could trust `$pack['door_code']`, and nothing would notice. A data-contract guard now
   closes it, mutation-proven. ⚠️ **AND THE NEAR-MISS THAT FOUND IT: my first mutation went RED FOR THE WRONG
   REASON** — bash escaping corrupted the file and I nearly logged "#33 re-proven ✅" on a syntax error.
   **G11's rule needs a second half: verify the edit landed on the target — AND that the RED came from the
   change, not from breaking the file. A mutation that reds for the wrong reason is as worthless as one that
   passes.** (`php -l` after every mutation edit, from here on.)
3. ~~**The PDF-sweep gate**~~ — 🏁 **DONE.** ⚠️ **17 templates, not 11**, and the "2.70 watermark" is the
   export-AUDIT stamp (informational, in the bottom margin — **fixable, not a founder decision**; real ink
   **2.70 on paper / 2.41 on a warm band** → `#5f5853` = 6.98). **224 distinct inks → 0 failures.**
   See `MISSING-BACKEND-BY-PAGE.md` **§PDF-SWEEP** and **§N.13**.
4. ~~**`DO-G8-1`**~~ ✅ **CLOSED (`ee6be74`)** — **`owner/unit-detail:338`**: the star migration's LAST remnant (`round()` → floor via
   `<x-star-rating>`). ⚠️ G8 fixed the hero and G12 fixed `ReviewList::stars_off`; ~~this one line is the
   whole of what remains of DO10-4~~ — ⚠️ **it was not**: the capstone's coverage sweep found **four further
   star sites** and a **third defect in the shared component itself** (§N.14.1/§N.14.2). Correctness, not tone.
5. **The BACKEND phase** — the D-items in `MISSING-BACKEND-BY-PAGE.md` (including **D16-7 via
   `rowStateMeta()`**), the **6 reopened operator a11y gates** (§N.9), the **3 open founder decisions**, and
   the **deferred-capability refusal sweep** (§N.8b: for each capability that has shipped, grep the surfaces
   that documented its refusal).

---

# (N.13) 🏁 THE GUEST REALM + THE PDF SWEEP — **THE CROSS-GATE SYNTHESIS** (SAVE v8, 2026-08-12)

*Each gate's own record is in `MISSING-BACKEND-BY-PAGE.md` §G-1…§G-12 + §PDF-SWEEP, and the runway entry below
carries the per-gate detail. **This section is what emerged ACROSS them** — it EXTENDS §N.4p, §N.8b, §N.8e and
§N.8f rather than replacing them.*

## N.13.1 ⚠️ THE REALM'S THESIS — the isolation model INVERTED THREE TIMES, and the gap was ALWAYS ADJACENT

| gates | what the scope WAS | the CERTIFIED guard (well covered) | ⚠️ the ADJACENT check (defended by nothing) |
|---|---|---|---|
| **G1–G10** | **scope to the guest** — `$this->guest()->reservations()->findOrFail()` on `lead_guest_id` | isolation: a foreign id 404s, mutation-proven on every surface | **`anonymize()` was COVERED but UNTRIGGERED.** G4/G7/G8 each proved it covers its store — each by calling it **directly**. Nothing tied the guest-facing DELETE to those stores; the sweep test asserted 3 fields. **G9** drove the real chain: schedule → **cron** → `processDue()` → `anonymize()`. |
| **G11** | **the signature IS the authorization** (a full guard elsewhere; `claim.verify`/`unsubscribe` are the only signed GETs) | tampering, a wrong hash and a swapped target were all tested | **the signature was CHECKED but its LIFETIME was UNPROVEN.** Swapping `temporarySignedRoute` for a permanent `signedRoute` left **every test green** — while turning every old-inbox claim link into a **permanent account-takeover key** and the page's "expires in an hour" into a lie. |
| **G12** | **the tenant is the scope** — *and the second scope is whether the thing is even FOR SALE* | tenant isolation, covered **non-vacuously** (the fixture seeds a whole second org) | **`properties.status` was DEFENDED BY NOTHING.** No test seeded an inactive property, so deleting either active-check re-published a withdrawn property's listing **and its entire booking funnel** — one middleware line gates browse/room/search/checkout/quote. |

> ### ⚠️ THE GENERALIZABLE LESSON
> **The risk was never in the certified guard. It was in the uncertified check standing NEXT to it.**
> *coverage vs **trigger*** · *signature vs **lifetime*** · *tenant vs **status***. Three inversions, three
> times the same shape. A guard that is famous gets tested; the quiet condition beside it does not — and
> neither static analysis nor "the guard has a test" will find it. **When you arrive at a new surface, ask
> what sits BESIDE the guard everyone knows about.**

## N.13.2 ⚠️ THE MUTATION DISCIPLINE — now complete in BOTH directions (extends §N.4p)

§N.4p enumerated the ways a mutation can **falsely pass**. The walk added a fifth form and, at the PDF sweep, a
whole second direction.

**FALSE-GREEN — the mutation passed and proved nothing (4 ways):**
1. **VACUOUS FIXTURE** — the fixture *cannot* fail. The systemic `#33` case: `createBooking` leaves
   `reservation_units.unit_id` NULL, so there was no door code to leak (G1/G2). G12's sell-scope needed a
   **seeded inactive property** before it could fail at all.
2. **WRONG-THING-REMOVED (O32)** — a passing mutation means vacuity **or redundancy**; both must be resolved.
3. **EDIT-DIDN'T-APPLY (G7)** — the anchor didn't match, the run reported a meaningless "passed".
4. **WRONG-TARGET (G11)** — the edit landed, and on the wrong thing: a regex written for
   `->middleware(['signed'])` missed the real `->middleware('signed')` and stripped the **first two** quoted
   occurrences — **login-approval and unsubscribe**, silently disarming a guard in a file I wasn't running.

**FALSE-RED — the mutation failed, but not because of the guard (1 way):**
5. **BROKEN FILE (PDF sweep)** — bash escaping leaked a backslash into `'door_code' => \$code`. The tests went
   red on a **syntax error**, and I nearly recorded *"#33 re-proven ✅"*. Re-run cleanly it went **green** —
   which is what exposed a real gap (N.13.3).

> ### ⚠️ THE UNIFIED RULE
> **Verify the edit landed ON THE TARGET — and that the pass/fail came from THE GUARD, not from the fixture or
> the file.** In practice: assert the anchor matched; assert the replacement is not a no-op; **run `php -l`
> after every mutation edit**; and after a *passing* mutation always ask *what did I actually remove?* O32
> cuts both ways. A fixture must be **able** to fail. Prove a just-written guard by mutating it (G4/G5).
>
> ⚠️ **EXTENDED BY THE CLEANUP SEQUENCE — see §N.14.4 for the complete four-question account.** The false-RED
> has a **second toolchain source**: a **stale compiled Blade view** (`DO-EMAIL-3`) turned a correct restore
> red — so **`php artisan view:clear` after every Blade mutation edit**, the Blade analogue of `php -l`. And
> the vacuity check has a sharper form: **a guard can be vacuous against its own subject** if it asserts
> literals repeated from the source rather than what the code renders (`DO10-4`).

## N.13.3 ⚠️ DEFENCE-IN-DEPTH NEEDS **PER-LAYER** TESTS (new — the PDF sweep)

Removing `ArrivalPack`'s data-level door-code gate left **25 tests green** — *including the one named
"withholds the door code until the arrival window opens (#33)"* — because `guest/arrival.blade.php` **and**
`pdfs/arrival.blade.php` each independently re-gate on `$pack['released']`.

**Nothing was leaking.** The point is sharper than a bug: **a redundant layer MASKS the failure of the layer
beside it**, so the data contract was untestable through any view. Remove the second layer later, or add a
surface that trusts `$pack['door_code']`, and the leak is live and silent.

> **Each layer needs a test that fails when THAT LAYER ALONE is removed.** A data-contract guard now asserts
> `ArrivalPack::for()` withholds the code, wifi and notes outside the window with no view in the way —
> mutation-proven on both halves. *This is N.13.1's adjacent-gap thesis applied to **test coverage** of a
> defended-in-depth guard.*

## N.13.4 ⚠️ THE CONTRAST METHOD — matured (consolidates §N.8e)

- **Pixel-sample the REAL render, with TWO SELF-VALIDATING ANCHORS** (white-on-`--vy-grad` ≈ 3.4;
  white-on-solid-`--vy-accent` = **5.18**). If the anchors don't read true, **publish nothing**.
  `getComputedStyle` both **false-PASSES** (the invite email's `bgcolor` under a gradient) and **false-FAILS**
  (defaults to white for all gradient-backed runs) — G1.
- ⚠️ **A translucent token's contrast MOVES WITH ITS BACKDROP** — re-measure per plate. **THE MEASUREMENT
  DECIDES, NEVER THE PRECEDENT, and it cuts BOTH ways:** a fix needed elsewhere may **not** be needed here
  (G11 — `--vy-accent` as link text *passes* on the lighter auth card), and a token that passed elsewhere may
  **fail** here (G2/G3).
- ⚠️ **A CONDITIONAL style is INVISIBLE until its condition is FORCED** — inject the real markup to sample it
  (G10's expired-credit chip; G12's town-tile labels; O16's validation flash).
- ⚠️ **FREEZE BY COMPLETING ANIMATIONS, NEVER BY REMOVING THEM.** `animation:none` reverts a `.vy-fade`
  element to its base `opacity:0`, so the plate goes white — **34 false failures at exactly 1.00** (G12), the
  same stuck-opacity mechanism as the Owner Statement bug, reproduced by the instrument itself. Use
  `animation-duration:0.001s` + `fill-mode:forwards`, and **assert zero elements remain transparent before
  capture**.
- **The PDF axis:** measure **INK ON PAPER** — not `getComputedStyle`, and **not a histogram** (it counts
  backgrounds and rules as ink; G3 discarded one). Legitimate shortcut, *verified not assumed*: the 17 print
  templates use **flat fills only** (no gradient/rgba/opacity), so an ancestor-walk yields the true plate —
  anchored against the pixel probe. **Greyscale-safe** too: a print is B&W, so a colour-only distinction dies.
  Measure a stamp/watermark **on a document that HAS it**.
- **When a shared token or literal changes, GREP the realm for its OLD value** (G4/G5; the sweep grepped the
  two greys to zero).
- ⚠️ **The instrument fails too.** Beyond the 12 false positives of the owner pass: G12's freeze (34), the
  PDF-sweep's own content-stream parser (discarded — it under-detected dark plates and reported white-on-white
  at 1.00), and a keyboard probe that reported controls as *named* because it fell back to `name`/option text
  (G10 — **7 unlabelled controls**). **Compute the accessible name strictly**, and check `:focus-visible` with
  a **real Tab**, not `el.focus()` (G12).

## N.13.5 ⚠️ THE §N.4k SAFE-PATTERN TAXONOMY (consolidated)

**Judge every form's Enter by its STAKES** — a request < a private write < a public write < a credential or a
delete. The safe patterns:
1. **SAFE BY CONSTRUCTION** — a GET form, a textarea, or no text input at all (G5, G7, G9, G12's search).
2. **THE CONFIRM IDIOM** — `onsubmit="return confirm(…)"`, the repo's destructive-submit convention (G5, G8).
3. **TYPE-TO-CONFIRM** — a typed word gates the action, so Enter *after* typing it is the intended completion
   (G9's account-delete `DELETE`).

⚠️ **A radio group SUBMITS on Enter** — the accessible input can *introduce* the risk (G8). ⚠️ **JUDGE THE
STAKES: an intended on-change write is not a defect** (G10's language toggle; G9's password form, where Enter
fails validation rather than writing anything wrong). **Guard the consequential, record the safe** — and
prefer opting into the repo's **existing** shared guard (`data-guard-submit` + `partials.submit-guard`) over a
new mechanism (G10 found it applied unevenly).

## N.13.6 ⚠️ THE §N.8b RECORD — the discipline CHECKS; it does not assume-false

**SIX claims SURVIVED verification and are recorded as plainly as the failures:** ⚠️ **G13's `DO-INV-3`** (no
public help destination, no configured support address — re-verified: `.env.example:72` is still
`MAIL_FROM_ADDRESS="hello@example.com"`, no `config/*.php` support address, no property phone column) ·
§G-3's arrival "no drift" ·
**DO-G6-2** ("saved is the last amber site" — I briefly thought I'd found a counterexample and **retracted it**:
the file was dead code) · G11's honest-status (*"your email is confirmed"* is substantively accurate — holding
the emailed link **is** the proof) · G12's policy claim (*"full refund up to 2 days"* really is derived from
`PropertyPolicy`) · the PDF sweep's arrival-is-clean.

**Several were CORRECTED:** ⚠️ **GATE-3 *and* GATE-4 both UNDERSTATED** — each recorded as fabricated or
out-of-scope something the product had since built (the G1 pattern, twice); G11's `claim-invalid` docblock
**overstated** its own coverage (it is *not* reached for expired/tampered/unsigned links); ⚠️ **and correction
#6, at G13: `DO-G7-2` — one of the walk's OWN register entries — was simply FALSE.** It claimed a guest cannot
start a message thread; `guest.messages.start` exists and its "New message" form is always rendered
(`guest/messages.blade.php:37`). **A finding is not safer for being ours.**

⚠️ **A "none / neither / not present" conclusion reached by GREPPING SPECIFIC STRINGS is a false-completeness
risk** — check against what the product **renders**, not against the phrasing you expected.

## N.13.7 ⚠️ THE MOCK-VALUE / VERIFY-EVERY-PROMISE FAMILY (~8 instances)

Copy drifts from the engine. **Verify every promise, figure and policy LINE BY LINE against the code:**

| shape | instance |
|---|---|
| a **value** | the door code's "48h" — really `ArrivalPack::LEAD_DAYS` (G2); still stale in a docblock (`DO-G4-1`) |
| a **right** | a fabricated "free to cancel up to 24h" (G5) |
| a **formula** | the prototype's `paid = charged − refunded` vs the engine's `received − refunded` (G6) |
| a **whole policy CARD** | G10's "How credits work" — **2 of 3 lines false**, **self-disproving on screen** (a 60-day expiry printed directly above a "valid for 12 months" claim), and **demo-masked** (the seeder posts the credit no production writer creates) |
| a **timing claim** | G11's "expires in an hour" — true, and now **DEFENDED** by the expiry guard rather than merely true |
| the **honest-absence corollary** | no price → **omit it and say why**, never fabricate (G12's *"Pick dates above to see availability & prices"*) |

## N.13.8 ⚠️ `DO10-4` IS A **CORRECTNESS** ISSUE, NOT A TONE PREFERENCE

The star bug is filled-vs-empty distinguished **by colour alone** → in greyscale or for a colour-blind reader a
**4 reads as a 5** (WCAG 1.4.1), and `round()` rounds a 4.6 **up to five stars**. `<x-star-rating>`
(floor-don't-round, glyph-differentiated, one accessible name) is the fix. G8 migrated the guest review and the
owner hero; **G12 fixed `ReviewList::stars_off`, which returned the FILLED glyph** and fed the owner Reviews
*list* rows one section below the migrated hero. ~~⚠️ The last remnant is `owner/unit-detail:338` (`DO-G8-1`) —
an owner page, still live.~~
✅ **CLOSED at the `DO10-4` capstone (2026-08-12, `ee6be74`) — see §STAR-CAPSTONE and §N.14.2/§N.14.3.** The
remnant was migrated and verified live **as the owner** on a forced 4.67 average (old: five filled stars; now
four + one empty, named). ⚠️ Two corrections to the account above: `unit-detail:338` was **not** colour-only —
it already emitted ★ then ☆, so its defect was `round()` alone; and the sweep found a **third** defect this
section never anticipated — **`<x-star-rating>`'s OWN default colours failed 3:1** (§N.14.2).

---

# (N.14) 🏁 THE CLEANUP SEQUENCE — **THE CROSS-PASS SYNTHESIS** (SAVE v9, 2026-08-12)

*Five passes ran after the walk closed: `DO-INV-4` (`e994dc6`) → `DO-EMAIL-1` (`b940cda`) → `DO-EMAIL-2`
(`d517187`) → `DO-EMAIL-3` (`ac502bf`) → `DO10-4` (`ee6be74`). Each wrote its own record — §EMAIL-SWEEP,
§EMAIL-SWEEP-2, §WEB-GREY-SWEEP, §ADMIN-EMAIL-BRAND, §STAR-CAPSTONE in `MISSING-BACKEND-BY-PAGE.md`, plus a
dated `DECISIONS.md` entry each. **This section is what emerged ACROSS them.** It EXTENDS §N.4p, §N.8b, §N.8d,
§N.8e and §N.8f rather than replacing them.*

## N.14.1 ⚠️ THE HEADLINE — **A CONTRAST/RENDER PASS IS BOUNDED BY THE ROLE IT RUNS AS** (extends G10)

> **No probe, however mature, can measure markup a permission check never emitted.**

`DO-EMAIL-2` assumed it would find an *instrument* failure — the operator/owner gates predated the matured
contrast method, so surely the old probe was too weak. **It wasn't.** All eleven `owners.blade.php` greys sit
inside **`@can('users.manage')`** — a permission the **demo Hotel Manager the gates were driven as does not
hold** — and inside an Alpine `x-show="open"` modal on top of that. Verified live: as `demo-staff` the markup
is **absent from the DOM entirely**. Measuring required a **role swap** (to a `Property Owner`, one of only
three roles holding it) **and then** a forced Alpine state.

**This extends G10.** G10 said *a conditional style is invisible until the condition is FORCED*.
⚠️ **A conditional SURFACE is invisible too — and the condition can be AUTHORIZATION.**

> **Coverage is not "force every UI state". It is "force every state ACROSS EVERY ROLE THAT CAN REACH IT".**

**Proven twice, and both times the sweep out-found the register:**
- `DO-EMAIL-2` — **28 occurrences across 9 files where the register named 13**; and `DO-EMAIL-5` (a fourth
  grey, every form label in that modal at 2.84) surfaced **the moment the pass looked past its own scope**.
- `DO10-4` — the coverage sweep found **four star sites nobody had named**, including
  `operator/guest-profile:132` at 1.98 and unnamed: **an operator page no star pass had ever rendered.**

⚠️ **Consequence for any "is X fixed EVERYWHERE?" claim:** it needs the multi-role, forced-state sweep, or it
means *"fixed everywhere I happened to look."* Both `DO-EMAIL-5` and a full operator/owner re-audit exist as
register items precisely because this bound has not yet been walked deliberately.

## N.14.2 ⚠️ A "CERTIFIED" COMPONENT CAN HAVE UN-MEASURED DEFAULTS (closes `DO-G8-2`)

**The migration TARGET is not automatically sound.** `<x-star-rating>` — the certified component every star
row was being migrated *to*, whose docblock correctly enforces floor-don't-round, glyph-differentiation and a
single accessible name — shipped **defaults that failed 3:1** as a non-text graphic: `#E8A63A` **2.08** and
`#B9A99A` **2.25**.

> *The thing everything was being migrated to drew a star you could barely see.*

**Certified for BEHAVIOUR ≠ its RENDERED defaults pass.** The two were never the same claim, and nothing had
checked the second. ⚠️ **Measure the thing you migrate TO, not only the sites you migrate.** Answers
`DO-G8-2`, which had sat as *"never measured on the pages that use it"*.

⚠️ **And the measurement corrected the fix mid-pass** (§N.13.4's rule, live): the first retarget was chosen
against the two *card* plates; the live render then showed the row sits on the **peach hero plate `#FBD3BF`**,
where the new empty star **still failed at 2.76**. Re-chosen against the **worst real plate**. *Pick the plate
from the render, not from the page you assume it is.*

## N.14.3 ⚠️ INPUT vs DISPLAY CHANGES THE FIX — and the display fix can INTRODUCE a bug

A rating **DISPLAY** wants filled-vs-empty by glyph (★/☆). A rating **INPUT** wants a per-option accessible
name and **must not pre-fill**, because a filled star states a rating the user has not given.

The register carried *"the email star"* as though it were a display. `review-request` renders **five tappable
rating INPUTS**, each deep-linking `route('guest.review', [..., 'rating' => $n])` under the copy *"Tap a
star…"*. ⚠️ **Applying the display fix would have shipped a phantom rating** — a bug the fix itself
introduced. The real defects were different: contrast as a non-text **control** (2.14 → 4.17) and the fact
that **each link's accessible name was the bare glyph "★"**, so a screen reader heard *"star, link"* five
times with nothing to choose between.

> **Read what the control DOES before applying the pattern its shape suggests.**

## N.14.4 ⚠️ THE TOOLCHAIN FALSE-RED PAIR — completing §N.4p / §N.13.2

§N.13.2 closed the mutation discipline in both directions and named one false-RED (a bash-corrupted file).
The cleanup added its sibling, and the pair is now a family:

| # | false-RED source | symptom | the check |
|---|---|---|---|
| a | **a bash-corrupted file** (PDF sweep) | RED from a **syntax error**, nearly logged as *"#33 re-proven ✅"* | **`php -l` after every mutation edit** |
| b | **a stale compiled Blade view** (`DO-EMAIL-3`) | RED **with the source verifiably correct** after a restore | ⚠️ **`php artisan view:clear` after every Blade mutation edit** — *the Blade analogue of `php -l`* |

> ### ⚠️ §N.4p's COMPLETE ACCOUNT — a guard's verdict must survive four questions
> 1. **Did the edit land ON THE TARGET?** (G7 didn't apply · G11 hit the wrong occurrence · O32 removed the
>    wrong thing)
> 2. **Did the pass/fail come from the GUARD — or from the FIXTURE?** (a vacuous fixture cannot fail)
> 3. **…or from the FILE?** → `php -l`
> 4. **…or from the COMPILED CACHE?** → `view:clear`
>
> ⚠️ **And `DO10-4` added the sharpest instance of (2): a guard can be vacuous against ITS OWN SUBJECT.** The
> star contrast test asserted `$ratio('#8F5A06', …)` — **hardcoded literals repeated from the source**. It was
> arithmetic, not the component: reverting the component's colour left it **green**. Rewritten to read the
> inks the component **actually renders**, the mutation went red naming the value and the plate.
> **Every guard needs a non-vacuity assertion, and a guard that restates the source proves nothing.**

## N.14.5 ⚠️ A NUMBER THAT MOVES IS NOT AUTOMATICALLY A CORRECTION (the counter-lesson to §N.8b)

§N.8b trains suspicion of the *existing* record. `DO-EMAIL-1` supplied the counterweight: a re-measurement of
`DO-INV-4`'s published **332 runs / 120 FAIL** read **321 / 118**, and looked exactly like a correction. It
was not. The cause was a **scratchpad name collision** — an auth *screen* render had overwritten a same-named
*email* render in the before-snapshot. Re-rendered from a **clean pre-sweep worktree** it is **332 / 120
exactly**, and the published figures stood.

> **Old claims get verified — and so do the corrections to them. A new, differing number can be the artefact.
> Establish WHICH reading is broken before rewriting the record; do not assume the later one wins.**

## N.14.6 ⚠️ A COLOUR HAS SEVERAL GREP-FORMS — a form-scoped grep UNDER-COUNTS

A hex-6 grep is not a search for a colour. The same value lives as `#RRGGBB`, `#RGB`, `rgb(r,g,b)`, and named
keywords. **`DO-EMAIL-2`'s register entry named 13 sites; the real footprint was 28** — and the two the hex
grep could never have found were the `rgb()` forms, one of which was **the body copy of an entire
`billing/feature-locked` 403 page**.

It compounds `DO-EMAIL-1`'s version of the same flaw **inside the instrument**: a probe whose regex matched
only 6-digit hex could not read `color:#fff`, so that run silently **inherited its parent's colour** and the
report named the wrong literal while pointing at the right pixel.

> **"Find every occurrence of colour X" must search every FORM — and the matcher inside your instrument is
> itself a source of under-counting.**

## N.14.7 ⚠️ A RE-SCOPING NOTE CAN AMPUTATE AN ITEM'S FOUNDING SCOPE

A register-hygiene failure distinct from a stale record. `DO-INV-4` was raised at the invite-email gate against
**`#A99E95`** in `emails/layout.blade.php`. A later annotation — *"now SCOPED: the offenders are the same two
greys the PDF sweep mapped"* — was accurate about the PDF sweep and **narrowed the item past its own founding
literal**, which turned out to be the **widest of the three** (7 occurrences reaching **15 templates** through
one shared declaration). Closing on two of three would have marked "done" the very thing it was raised for.

> **Check an item against its ORIGIN, not only its latest annotation. A scoping note is a lens, not a
> replacement definition.**

## N.14.8 ⚠️ A SHARED LAYOUT'S DEFAULTS ARE CONTENT TOO — AND CAN BE FALSE

Inheriting a brand wrapper inherits its **boilerplate**, and boilerplate is a claim. The shared email layout's
default service line — *"You're receiving this because it relates to your booking with us."* — is **false on an
account or security email**: a platform admin has no booking. `DO-EMAIL-3` used the layout's footer-override
slot rather than ship it.

⚠️ **But the sibling ships it RIGHT NOW.** `owner-email-change` — an owner's **login-email change**, a security
notification — renders that line live. That is **`DO-EMAIL-9`, an open §N.8d on a security email**, and the
open question is how many other account/security templates extending the layout inherit it.

> **A wrapper's default copy is content the wrapper asserts on every page that adopts it. Audit it per
> audience, not once.**

## N.14.9 ⚠️ TOKEN-DRIFT — MAP THE LITERAL TO THE TOKEN, NOT TO ANOTHER LITERAL

On the web the hardcoded grey **is** the drift: the literals are the **pre-AA values of tokens that have since
been retuned** — `#8A8079` is the old `--vy-muted` (now `rgb(95,88,83)`), `#A69A92` the old `--vy-faint` (now
`rgb(108,100,95)`). The token moved; the hardcoded copies stayed frozen at the pre-remediation palette.

**So the fix is the TOKEN** — it follows the next retune; another literal would freeze again. **Three
exceptions, each measured rather than assumed:**
- **Emails cannot read tokens** (clients strip custom properties) → an email-safe **literal**.
- **A `var()` in an SVG presentation attribute renders NOTHING where unsupported** → for a *decorative* icon
  that is a worse trade than a hardcoded value (`DO10-4`'s strokes; `var()` *was* verified to resolve in
  Chromium, and the literal was still chosen).
- **Standalone pages with their own `<style>`** (`billing/feature-locked`, `auth/login-approval*`) have no
  `--vy-*` in scope → literals, logged as **`DO-EMAIL-6`** rather than pretended away.

⚠️ **And the token layer has its own drift: `--vy-amber-text` is defined TWICE with different values** — guest
`rgb(126,88,17)` vs owner `rgb(128,76,5)` (**`DO-EMAIL-4`**). One concept, two definitions: mapping "to the
token" is ambiguous until that is reconciled.

---

## N.14.10 · WHAT THE CLEANUP LEFT TRUE

| surface | state |
|---|---|
| **PDFs** (17 templates + `PdfService`) | grey drift **CLOSED** (the v8 sweep) |
| **Emails** (20 templates) | grey drift **CLOSED**; **all 20 now on-brand**; **335 text runs / 0 FAIL** |
| **Operator/owner web screens** | the three greys **CLOSED** across 28 occurrences / 9 files, mapped to **tokens** |
| **Rating stars** | the **O26 defect family RETIRED** — every rating row floors, differs by GLYPH, carries one accessible name, and clears 3:1 on the **worst plate it renders on**; `<x-star-rating>`'s own defaults fixed to `#8F5A06` / `#7A6E64` |
| **Suite** *(at the close of the cleanup)* | 1907 / 12,448 · 9 non-green = the documented baseline set · PHPStan L5 (0) · Pint clean. **Now 1914 / 12,613** after the FAQ build (§N.15) — same 9 non-green. |

⚠️ **What it did NOT close, and should not be read as closing:** the role-coverage bound (N.14.1) means the
operator/owner realms have been swept **only where this pass could reach**. `DO-EMAIL-5` (a full re-audit) is
the honest successor, and a fourth grey was already found inside the one modal that was forced open.

---

# (N.15) 🏗 THE FIRST BUILD — **THE GUEST FAQ CENTRE**, and what building (rather than fixing) taught (SAVE v10, 2026-08-12)

*`23bf335` — `GET /guest/help`. The full record is **§GUEST-FAQ** in `MISSING-BACKEND-BY-PAGE.md`; the dated
entry is in `DECISIONS.md`. This section is the canon: what the first NEW UI since the walk proved, and the
spec the next build works against.*

## N.15.1 · WHAT SHIPPED

| piece | detail |
|---|---|
| route / controller / view | `GET /guest/help` (guest-guarded) · `GuestHelpController` · `guest/help.blade.php` · a `Help` nav entry |
| content | **`HelpKnowledgeBase::guestArticles()` — 10 entries / 5 categories**, added to the CERTIFIED engine, **not forked** (§N.8f). Same contract as the owner set plus a `category`. |
| **no schema** | the knowledge set is curated code, not a table — confirmed |
| the one contact | the **existing relay** (`guest.messages`), in a "Still need help?" card |

**The view holds no help content.** It renders the engine, so the FAQ and its source cannot drift the way two
copies of a claim always do — the mechanism the owner side already proved, now serving two audiences from one
class. Guest entries link only `guest.*` routes, owner entries only `owner.*`; a test pins both.

## N.15.2 ⚠️ VERIFY-EVERY-PROMISE NOW COVERS **AUTHORING** — and it is cheaper there

The walk's verify-every-promise family (§N.13.7) was always **retrospective**: find the copy that drifted from
the engine. Writing a FAQ inverts it — **every sentence is a fresh claim about the product, made before any
test exists to catch it.**

> **Verify BEFORE writing. It costs a grep; verifying after costs a gate.**

Every answer was read out of the code first. Two shaped the content:

- ⚠️ **The door-code answer INTERPOLATES `ArrivalPack::LEAD_DAYS`, it does not type a number.** `DO-G4-1` had
  already caught a docblock claiming "48h" against a constant that says **2 days**. A stale docblock misleads a
  maintainer; **a stale FAQ strands a guest at a door.** A test pins the interpolation *and* forbids "48h"
  returning.
- ⚠️ **THE SECOND RULE IS NEW: WHEN THE TRUE ANSWER VARIES, ROUTE — DO NOT PICK A NUMBER.** The cancellation
  window is per-property (`DO-G12-1` records that even the storefront's single figure over-promises the moment
  two properties differ). The entry therefore points at the booking's own Manage page, which shows *that*
  booking's real terms. **A pointer to the authoritative surface is a true answer; an averaged or typical
  number is a false one.** This is honest-absence applied to authoring rather than to a UI affordance.

## N.15.3 ⚠️ THREE NEW TRAPS THE BUILD WALKED INTO — all caught, all now canon

### (a) A VACUOUS ASSERTION CAN HIDE IN A SHARED NAV *(O32, a new flavour)*

The relay guard was `assertSee(route('guest.messages'))`. It stayed **green** with the card's button repointed
at another screen — because **the guest shell links Messages in its nav on every page**. The assertion was
satisfied by furniture, not by the thing under test.

> **Presence-somewhere ≠ presence-where-it-matters. Scope a link-presence assertion to the SPECIFIC element**
> (here: the substring from "Still need help?" onward). ⚠️ Any page-level `assertSee` of a URL that also appears
> in a shared shell is vacuous by construction.

### (b) N.14.2 EXTENDS FROM COMPONENTS TO **REUSED CLASSES AND TOKENS**

§N.14.2 said *measure the thing you migrate to*. The build generalised it within minutes: the page used
`.vy-sr-only` for the search label — **and the guest shell does not define it.** An undefined visually-hidden
class hides **nothing**; the label would have rendered as stray text next to the search box.

> **Verify a reused class or token EXISTS before relying on it.** "It's in the design system" is a belief until
> grepped. Every other reused class and token on the page was checked; only this one was missing.

### (c) THE SHIPPING-CSS-COMMENT TRAP, IN ITS **NEGATIVE-ASSERTION** FORM

The no-support guard scanned the raw response for forbidden words and fired on **"PHONE"** — inside a **CSS
comment** in the shell (`/* ── PHONE (audit …) ── */`), a *breakpoint* heading, not a telephone. **CSS comments
ship** (the trap `DO-EMAIL-2` and the PDF sweep already recorded, here in a new guise).

> **An absence assertion must read what the user SEES.** Strip `<style>`/`<script>` and tags first: "the page
> renders no phone number" is a claim about **visible text**, and a raw-substring scan tests something else.
> The guard now reads visible text *and* separately forbids `tel:`/`callto:` anywhere in the markup.

⚠️ **Note the shape of all three: none was a bug in the feature. All three were bugs in the GUARDS** — and two
of the four mutations passed on the first attempt because of them. **New UI needs its guards mutated as hard as
a security fix's.**

## N.15.4 ⚠️ BUILDING TO THE MATURED CANON AT BIRTH — it held

The page was built against §N.13/§N.14 from the first line rather than fixed afterwards, and the record is that
this cost little and caught things early:

- **Rendered and verified AS THE GUEST** (N.14.1) — not as whatever role happened to be logged in.
- **Keyboard:** 28 focusable controls, **0 unnamed** under a strict accessible-name computation (no fallback to
  `name`/placeholder — G10's lesson); a **real Tab** press confirmed `:focus-visible` actually fires (G12:
  `el.focus()` does not set it), and each custom control has a matching outline rule.
- **Contrast:** pixel-sampled with **two self-validating anchors** on every plate, across the populated state,
  the **forced open-accordion** state (an answer's text does not exist until `<details>` is open — G10) and the
  no-results state → **0 failing runs**. ⚠️ The chips sit on their own plates *above* the card and were measured
  there separately (4.67 / 6.52) rather than assumed to share the card's.
- **§N.4k:** search and category are **GET** — no write exists on the page, so Enter is a safe read by
  construction, and the guest realm's no-Alpine convention is kept (a native `<details>` accordion).
- **Tokens, not literals** (N.14.9).

> **The canon is cheaper applied at birth than retrofitted.** Every item above was minutes during the build;
> each has cost a whole gate somewhere earlier in this project.

## N.15.5 ⚠️ THE GUEST-HELP VERDICT WAS **SPLIT** — and the refusal it reverses is only half-reversed

`DO-G13-1` was decided by the founder in two parts:

| half | ruling | state |
|---|---|---|
| **the FAQ centre** | ✅ **BUILD** — the 2026-08-07 refusal's premise (*"its content would have to be invented"*) is **falsified** by the `HelpKnowledgeBase` engine | **BUILT** (`23bf335`) |
| **the support half** (phone / hours / reply time) | ⏳ **BUILD THE BACKEND FIRST** — ⚠️ *not* don't-build, and ⚠️ *not* ship-the-UI-over-nothing | ✅ **BUILT 2026-08-12** — the backend exists, so the condition is met. See N.15.6 + §SUPPORT-BACKEND. |

⚠️ **So the 2026-08-07 refusal is REVERSED in one half and PRESERVED in the other — and the preserved half is
now CONDITIONAL, not permanent.** It stands only until the backend exists. Recorded precisely because "the
refusal was reversed" and "the refusal stands" are both true of different halves, and a future session reading
either sentence alone would get it wrong.

> ✅ **UPDATE (2026-08-12): THE CONDITION HAS BEEN MET AND `DO-G13-1` IS CLOSED IN BOTH HALVES.** The support
> backend shipped, so the second half is no longer "preserved-but-conditional" — it is **built, on real stored
> data**. ⚠️ **What was never conditional and has NOT moved:** the page still claims nothing it cannot honour.
> A property whose operator has set nothing renders exactly the pure-FAQ page it shipped as. The refusal was
> never "don't show support"; it was **"don't promise support that will not answer"**, and that rule is now
> enforced by the schema rather than by anyone remembering it. *A conditional refusal is discharged by building
> the condition — not by deciding the refusal was too strict.*

## N.15.6 ✅ THE SUPPORT BACKEND — **BUILT** (2026-08-12; the spec below is what it was built against)

> 🏁 **SHIPPED — `DO-G13-1` IS NOW CLOSED IN BOTH HALVES.** Three nullable per-property columns + a reversible
> migration → a **Guest support** section on `/app/settings` (`properties.manage`) → a conditional block on
> `/guest/help`. The shared derivation is **`PropertySupport`** (§D.guest). Full record: **§SUPPORT-BACKEND** in
> `MISSING-BACKEND-BY-PAGE.md`. The spec below stands as written — it was followed, with **one correction**:
> ⚠️ **`DO-SUP-1` — the number renders as a TELEPHONE only.** The spec said "phone / WhatsApp"; nothing in the
> product verifies that a number answers on WhatsApp, so drawing that affordance would assert a channel that may
> not exist (#39) — the same shape as the reply-time promise this build exists to avoid.
>
> ⚠️ **THREE THINGS THIS BUILD ADDED TO THE CANON:**
> 1. **HONEST ABSENCE IS STRONGEST AS A TYPE, NOT A RULE.** `for()` returning null (and the columns being
>    nullable with no default) means the placeholder does not exist to be rendered. Nobody has to remember it.
> 2. ⚠️ **A PASSING MUTATION IS A FINDING (O32, in its redundancy form).** Removing the card-level null return
>    left every row-level guard **green** — because each row is separately gated — while the page would have
>    drawn an **empty, property-named card**: a placeholder by another name. Redundancy hid it. The guard now
>    asserts no support markup exists at all in the unconfigured state. **Ask what you actually removed.**
> 3. ⚠️ **A PREFIX ASSERTION TESTS A PREFIX.** The catalogue first stored bare intervals, so the page read
>    *"We usually within a few hours."* — broken English that `toContain('We usually')` was perfectly happy with.
>    **The browser caught it; the test did not.** Phrases are now complete predicates and the guard asserts the
>    whole composed sentence. *§N.15.4's "verify as the guest" is not only about a11y — it catches copy a
>    substring assertion cannot see.*

A founder decision, recorded here so the next session builds *against* it rather than re-deciding it.

> ### THE PRINCIPLE, BAKED INTO THE DATA SHAPE
> **Every field displays ONLY when populated. Empty means the affordance is ABSENT — not blank, not "—", not a
> placeholder.** Honest absence stops being a discipline someone has to remember and becomes what the schema
> does by default.

| piece | spec | ⚠️ the honesty constraint |
|---|---|---|
| **Channel** | the existing relay (already real) **+ a stored phone / WhatsApp PER PROPERTY** (new field) | shown only where populated; no property-less global number |
| **Reply time** | a **SOFT, operator-set NORM** (stored) | phrased *"usually within…"*. ⚠️ **NEVER a guarantee, never a fabricated figure, and never a measured-and-presented-as-promised one.** This is the `DO-EMAIL-9` class: a stored expectation is honest **as a norm**, dishonest as a commitment. |
| **Hours** | **real per-property hours** (stored) | shown where set. ⚠️ **No "24/7" unless the stored hours actually say so.** |

**Shape:** 3 new per-property fields → one migration → operator-settings UI to **set** them → a guest-facing
support block to **display** them.

⚠️ **The guest block is NEW GUEST UI, so it inherits this section's canon:** render and verify **as the guest**
(N.14.1) · both a11y axes at birth · **verify every reused class/token exists** (N.15.3b) · **non-vacuous
guards, scoped to the specific element** (N.15.3a) · **absence assertions read visible text** (N.15.3c).

⚠️ **Until it is built, the FAQ page must stay pure** — no slot, no placeholder, no commented seam. The later
build adds its own block then.

---

# (N.16) 🏁 THE BACKLOG SEQUENCE — **THE CROSS-PASS SYNTHESIS** (SAVE v11, 2026-08-12)

*Seven passes after the FAQ build: the support backend (`6550087`) → `DO-SEED-1` (`f9142c8`) → `DO-EMAIL-9`+`8`
(`52321b8`) → `DO-EMAIL-4` (`a70c147`) → `DO-EMAIL-5`/`6` (`2e8cd0f`) → `DO-EMAIL-7` (`4440c7a`). Each wrote its
own record in `MISSING-BACKEND-BY-PAGE.md` (§SUPPORT-BACKEND · §DO-SEED-1 · §EMAIL-FOOTER-RESTRUCTURE ·
§AMBER-TEXT-RECONCILIATION · §ROLE-COVERAGE-RE-AUDIT · §HK-STATUS-DETERMINATION) plus a dated `DECISIONS.md`
entry. **This section is what emerged ACROSS them.** It EXTENDS §N.13/§N.14/§N.15 rather than replacing them.*

## N.16.1 ⚠️ THE HEADLINE — **§N.14.1 IS NOW A PROCEDURE**, not a warning

§N.14.1 said *a contrast pass is bounded by the ROLE it runs as*. As a slogan it changed nothing. It is now a
**reusable audit** — the general answer to *"is X fixed EVERYWHERE?"*:

> 1. **Enumerate** the roles and their permission sets.
> 2. **Identify** which roles prior passes actually ran as.
> 3. ⚠️ **DIFF** — compute the permissions those roles LACK.
> 4. **Grep** the markup gated on exactly those permissions.
> 5. **Render as a role that PASSES the gate**, and force the conditional on top of it.
> 6. **Measure the delta.**

⚠️ **STEP 3 IS WHAT MAKES IT FINISHABLE.** Without the diff, "audit every screen as every role" is combinatorial
and therefore never done. With it, the target was **6 permissions / 12 gated blocks**.

⚠️ **AND THE FRONTIER IS BOUNDED BY PERMISSION ALGEBRA.** A role whose permissions are a **subset** of a covered
role can reveal **no new markup** — so eight of the eleven operator roles were closed *by reasoning*, and only
the `*` superset (Property Owner / Group Admin) extended the frontier. That is what stops "all the other roles"
from becoming open-ended debt.

**It paid for itself immediately:** it found **`#B7ADA4` at 2.00** — *worse* than the known target, named by
nobody, visible only once a role that could open the modal rendered it.

> **Pin the method with a ROLE-AWARE guard:** render the gated screen as BOTH roles and assert the markup is
> **absent for one and present for the other**. A contrast guard that runs as a role which cannot see the markup
> asserts nothing — it is vacuous by construction, which is the DO-EMAIL-2 trap wearing a test's clothes.

## N.16.2 ⚠️ A THIRD WAY A SURFACE HIDES — **BY DATA** (the taxonomy is now complete)

A surface can be invisible to a pass in **three** ways, and a complete audit forces all three:

| # | hidden by | force it with | named at |
|---|---|---|---|
| 1 | conditional **STYLE / STATE** | force the state (open the modal, inject the variant) | **G10** |
| 2 | **AUTHORIZATION** | render as a role that passes the gate | **§N.14.1** |
| 3 | ⚠️ **DATA** | create the data the surface needs | **N.16.2 (new)** |

The storefront filter keys are the instance: not gated by any permission — **public** — but invisible unless a
search has been **performed** *and* the property actually **has** filter data. They measured **1.77**.

⚠️ **The data axis has never been swept in any realm.** Logged as part of `DO-EMAIL-5-continued`.

## N.16.3 ⚠️ **KEYED, NOT TYPED** — the house pattern for copy that must stay true

Two builds reached for the same shape independently, both to close a §N.8d dishonesty that would otherwise rot:

| build | the risk | the shape |
|---|---|---|
| the guest support **reply norm** | a stored "we reply in X" becomes a promise | a KEY into a catalogue of soft norms, all starting *"usually"* |
| the email **footer reason** | one hardcoded line is false for some audience | a KEY into a catalogue of true reasons, one per category |

> **Copy that must stay true is a KEY into a vetted catalogue in code — never free text.** The false version is
> then **unrepresentable**: a refusal that *cannot fail*, which is strictly stronger than validation or helptext,
> because there is no path by which a caller can express the falsehood.

⚠️ **THE LOAD-BEARING PIECE IS THE DEFAULT.** It must be **generic-true**, never the specific-false claim. The
old footer defaulted to *"relates to your booking"* — so every new template inherited a falsehood by doing
nothing. The new default is true of anyone, so a forgetful caller still cannot lie. *Rewording the default would
have moved the falsehood; keying it removed the category of error.*

## N.16.4 ⚠️ **A COMMENT IS NOT A DEFENCE** — the root form of the stale-claim family

`DemoSeeder` mixed a today-relative window with an absolute calendar. They were disjoint when written; time
walked one into the other. ⚠️ **The codebase had already met this exact failure once** — `seedOwnerBalanceAccrual`
carries a *"ROBUST TO DATE-DRIFT"* forward-scan for precisely this reason. The other site had a **comment**:
*"clear of the window, so no collision."*

> **Two sites, one invariant: one turned it into CODE, the other into PROSE. The prose rotted.**
> A comment asserting an invariant is a **claim that decays exactly where nothing enforces it**. When you find
> *"X can't happen here"* written as a comment, you have found a candidate for a **guard** — this is the root
> form of the §N.8b stale-claim family.

⚠️ **And a latent, time-coupled bug needs a test at MULTIPLE temporal edges.** A same-day test would have been
green every single day before the break. The guard seeds at four travelled dates — and honestly, the mutation
does **not** red at 2031, because a literal that far in the past no longer collides. *One date would not have
caught it.*

## N.16.5 ⚠️ CONSOLIDATION CAN BE AN **HONESTY REGRESSION** DRESSED AS TIDINESS

Folding a one-off into a general system is usually hygiene. But **if the one-off encoded a real distinction,
flattening it reduces honesty.**

`DO-EMAIL-3`'s admin-email override carried a nuance: an email-change confirmation goes to the address being
**added**, whose reader may hold **no account at all** — so even *"a security change on your account"* is not
safely true of them. Declaring `security` and moving on would have been a small dishonesty introduced *by the
cleanup*. `email_change` therefore exists as its own category. ⚠️ **The old test defending that sentence is what
surfaced it** — G8, live: *a test that looks like refactor collateral may be encoding a real invariant; read it
before you change it.*

⚠️ **AND ITS MIRROR (`DO-EMAIL-4`): do not write a guard that is FALSE against the design.** Eight tokens differ
across the three shells; **seven differ deliberately** (guest radii, lighter shadow, `--vy-glass-border`,
`--vy-on-grad` measured per shell). A blanket *"all tokens must agree"* rule would have been **overreach dressed
as rigour** — and still a bug. The guard was scoped to the one token that had no reason to differ.

## N.16.6 ⚠️ THE **POSITIONAL PLATE**, DE-EMPHASIS, AND FOUR MORE INSTRUMENT FAULTS

**A token on a TRANSLUCENT element over a GRADIENT has a plate that varies with POSITION.** `--vy-muted` read
**6.00** on the light part of the housekeeping band and **4.25** on the saturated part — *failing at full
opacity*. It was **wrong by position, not by carelessness**. → **Sample the worst position, never a
representative one.**

⚠️ **DE-EMPHASIS BELONGS ON A REDUNDANT MARK, NEVER ON MEANING-BEARING TEXT.** `.is-zero` dimmed a whole chip to
`opacity:.62`, taking a status WORD to **2.28** — while the "0" beside it already carried the empty state. The
dimming was **redundant and harmful**; it now rides the dot.

**The instrument family gained four members** (extending §N.13.4 / §N.14.4):

| fault | symptom | the check |
|---|---|---|
| **out-of-bounds pixel read** | returns **black** → a failure that never happened (3.29/2.95) | scroll the element into the viewport; distrust a reading implausible beside its neighbours |
| **a broken verification grep** | reported an edit had not landed when it had → a wrong mutation verdict | **the check must be as trustworthy as the thing it checks** |
| **a vacuity check that is itself vacuous** | spatie's team id unset ⇒ *both* premise assertions pass for the wrong reason | assert the premise can fail |
| **comment semantics are language-specific** | a CSS comment **ships** and must be scanned; a Blade comment is **stripped** and must be excluded | strip by language before any absence assertion |

## N.16.7 ⚠️ **VERIFIED-FINE IS AN HONEST OUTCOME** — and the defect sits BESIDE the flagged thing

`--vy-hk-ooo` was re-opened because `DO10-4` had established that a colour-carried status is *correctness*, not
tone. Measured, it was **sound**: every chip prints its word, the dot clears 3:1 anyway, greyscale confirms it.
**Nothing was changed, and the pass PINNED the verified-fine state** so it cannot regress to colour-only.

> **Inventing a fix for a passing thing would have been the failure.** A flagged item that measures sound is
> closed *as sound* — with a guard, so the finding is not merely "we looked once".

`DO-EMAIL-6` is the same shape: the register named the wrong defect. The standalone pages' **literals pass**
(the concern was maintainability, correctly); what actually failed was the **CTA at 3.08**.

⚠️ **AND §N.13.1's THESIS HELD TO THE VERY LAST PASS.** The flagged item (the OOO fill) was fine; the defect sat
**beside** it (the tally chip dimming its own status word). *The risk is next to the thing everyone is looking
at* — three chapters running.


---

# (N.17) 🏁 THE BILLING WORKSTREAM — **THE CROSS-PASS SYNTHESIS** (SAVE v12, 2026-08-13)

*Four passes plus a read-only investigation, closing the last ⛔ go-live blocker on the code side: the
investigation → **pass 1** the shape-aware engine (`43862db`) → **pass 3** render-from-plan (`615904c`) →
**pass 2** the Price wiring (`7de1f35`) → **pass 2b** Stripe Tax (`9fed4a1`). ⚠️ **The billing CODE is complete
and honest. What remains is the founder's Stripe dashboard work — see §G.1.** The numbering is out of order
because pass 3 was deliberately taken before pass 2: a half-aware page is worse than a known-false one.*

⚠️ **This chapter is where the honesty discipline met REAL MONEY, and it changed shape.** In the design walk a
false claim cost credibility. Here a false claim is a wrong charge, and the failure modes are not symmetric: an
over-charge produces a complaint, an **under-charge produces silence**. Most of what follows is about the
direction that nobody reports.

## §N.17.1 ⚠️ A GO-LIVE BLOCKER CAN BE A FRAMING ARTEFACT, NOT A DECISION

§G.1 stood open for months as *"flat tier **or** per-unit?"* — filed as a **founder pricing choice**, with a
standing instruction to surface it and never pick a side. That instruction was right about the etiquette and
wrong about the question.

⚠️ **There was no model to choose.** Pricing is **configurable data**, and a plan row can declare its own shape.
The "A vs B contradiction" existed **only because two models were hardcoded and coexisting** — `config('billing.
tiers')` charging Stripe while `SubscriptionPricing` displayed a per-unit figure nobody billed. Once a plan
carries `pricing_type`, both shapes are simply supported and the contradiction evaporates. The blocker was an
artefact of the implementation, wearing the costume of a business decision.

⚠️ **THE LESSON — when a blocker frames itself as "choose between X and Y", ask first whether the real fix is
"stop hardcoding the choice."** A question that cannot be answered without the founder may not be a question at
all; it may be a missing abstraction. *Distinguish a genuine product decision (what to charge) from a fork the
code invented (which mechanism exists).* The genuine decisions did remain, and were ruled — they were about
**amounts and tax conventions**, never about which engine to keep.

⚠️ **The original §G.1 framing is preserved in its own `<details>` block, annotated rather than erased** — the
same discipline v11 used. It was accurate about what the code did at the time.

## §N.17.2 ⚠️ §N.8d AT THE MONEY BOUNDARY — `display = charge = STRIPE`, not merely `= page`

Passes 1 and 3 proved **engine = page**: the figure on the Billing page is `Plan::chargeFor()`, the same
expression the charge uses, so the page cannot quote one number while the app intends another.

⚠️ **That chain was one link short, and the missing link was broken.** Nobody had inspected what the checkout
**actually sends**. It sent `price` + `quantity` and **no tax configuration whatsoever** — no `tax_behavior`, no
`automatic_tax`, no `tax_rates`. A per-unit plan's engine total was `units × rate + VAT`, so:

| | amount (8 units, €9.00) |
|---|---|
| page and engine said | **€88.56** |
| Stripe would have taken | **€72.00** |
| ⚠️ silent under-charge | **€16.56 / month** |

⚠️ **A SILENT UNDER-CHARGE IS THE INSIDIOUS DIRECTION.** No customer complains about being billed too little.
There is no error, no exception, no failed payment — just quiet revenue loss that stays invisible until someone
reconciles Stripe against the product. An over-charge announces itself within a day; this would not have.

⚠️ **THE LESSON — the display=charge chain must span all the way to the PAYMENT PROCESSOR.** The last hop can
disagree with everything upstream while every internal test agrees with itself. It was found by one grep asking
*what leaves the app* (`grep -rn "tax_behavior\|automatic_tax\|tax_rates" app/ config/` → nothing), not by any
test — because every test compared the app to the app.

## §N.17.3 ⚠️ GIVE UP THE CLAIM, NOT THE CALCULATION — the chapter's headline

The founder ruled **Stripe Tax** for per-unit (`tax_behavior: exclusive` + `automatic_tax`). That ruling changed
what kind of thing the tax **is**: it became **customer-dependent** — country, business status, and **EU reverse
charge, which is €0**.

⚠️ **So the hardcoded `+ 23%` went from UNSENT (pass 2's finding) to FALSE.** It was no longer a figure the app
neglected to transmit; it was a figure that is simply **wrong for a whole class of customers**. A reverse-charge
business pays nothing, not 23%.

⚠️ **AND THE FIX WAS NOT BETTER ARITHMETIC.** There is no percentage that is right, because the right value
depends on facts the app does not hold. The fix was to **stop asserting a post-tax total at all**:

- the **engine** drops the VAT → `chargeFor()` returns the **pre-tax subtotal**, carrying `tax_known: false`;
- the **page** shows *"€72.00/mo · 8 units × €9.00 · **tax calculated at checkout**"*;
- the app asserts **only what it knows** and **defers the rest to Stripe** (§N.16.2 — the customer's tax status
  is DATA, resolved elsewhere; the page truthfully declines to resolve it).

⚠️ **Pass 2's €16.56 gap therefore closed FROM THE OTHER END — not by sending the 23%, but by never having
claimed it.** The pre-tax subtotal the page shows is exactly `Price × quantity`, so display = charge = Stripe
holds again, now under a tax the app does not own.

⚠️ **THE LESSON, and it generalises well beyond money — WHEN THE TRUTH IS UNKNOWABLE AT THE POINT OF DISPLAY,
GIVE UP THE CLAIM.** Do not fabricate a plausible-looking number: **any specific value is false for some case**,
and the plausible ones are the most dangerous because nothing looks wrong. Deferring ("calculated at checkout")
is not a weaker answer than a number — it is the only true one. *This is #39's no-fabrication rule arriving at
the same place from the money side.*

## §N.17.4 ⚠️ §N.8f ON MONEY SURFACES — a divergent SECOND TOTAL is HOW surfaces drift (the §N.13.1 recurrence)

Pass 3 fixed the Billing page's headline. Pass 2b found **two more surfaces asserting the same fixed VAT, both
beside it**:

1. ⚠️ **The Billing card's own "Next invoice" strip** — which read `SubscriptionPricing`, not the plan. A **flat**
   org saw **"€49.00/mo"** in the headline and **"€72.00 + €16.56 VAT"** in the strip **directly beneath it**.
   *One card contradicting itself, inches apart.*
2. ⚠️ **Operator Settings → "Your plan"** — which priced *every* org with the per-unit formula. The demo org on
   flat Essential displayed **"Vyrobel Essential · €88.56/mo"** while its own Billing page said **€49.00**.

⚠️ **ROOT CAUSE: `SubscriptionPricing` was a SECOND, DIVERGENT TOTAL FOR ONE CONCEPT** — 5535 where
`Plan::chargeFor()` said 4500. That is §N.8f (one definition per concept) violated on a money surface, and it is
**exactly the mechanism of the token family**: two computations of one thing will diverge, and the divergence
shows up on whichever surface nobody re-read.

Fixed by routing every surface through `Plan::chargeFor()` and **stripping VAT from `SubscriptionPricing`
entirely** — it cannot be allowed to act as a competing tax authority. All three sites on the Billing card now
read ONE keyed `tax_note` (§N.16.3), so a future edit cannot make them disagree again.

⚠️ **§N.13.1's THESIS HELD ONTO THE MONEY SURFACES — four chapters running.** *The defect sat beside the thing
that had just been fixed.* Pass 3 corrected the headline and left a contradiction six lines below it.

## §N.17.5 ⚠️ REFUSE-TO-CHARGE BEATS CHARGING WRONG · LIFT A GUARD BY SATISFYING IT, NEVER BY REMOVING IT

When pass 2 found the tax gap, the two honest resolutions (a VAT-inclusive unit Price, or Stripe Tax with
reverse charge) had **different compliance consequences**. That is a founder decision, so the pass **refused to
pick** — and made the **app** refuse too: a per-unit plan **throws in production** until `STRIPE_TAX_BEHAVIOR`
is declared.

⚠️ **REFUSING TO CHARGE BEATS CHARGING THE WRONG AMOUNT.** Contain the wrong outcome, surface the decision, do
not guess on a money path. A loud failure at the boundary is recoverable in minutes; a silent under-charge is
discovered in a quarterly reconciliation. ⚠️ Note the scope discipline: **flat tiers were never gated**, because
their charge was already correct — a refusal that blocks a correct path is not caution, it is breakage.

⚠️ **AND PASS 2b LIFTED IT THE RIGHT WAY.** The convention is now **declared** (`exclusive`) **and**
`automatic_tax` is **actually wired** — so the refusal lifts because **its condition is satisfied**, not because
the check was deleted. The guard still throws the instant the convention is unset, and **a test unsets it and
watches production refuse again**. *Deleting a guard whose condition you have met looks identical in a green
suite to deleting one you have not.*

## §N.17.6 ⚠️ THE DERIVED-NOT-STORED QUANTITY · AND THE TWO-PART PASS

⚠️ **The charged quantity DERIVES from the org's real units** (`BillableUnits::count()`), and there is
**deliberately no `subscriptions.unit_count` column**. A stored count is correct until the next unit is added
and then quietly wrong, with nothing to announce the drift — and **a charged quantity that disagrees with
reality is §N.8d with money attached**: the customer is billed for a number that is not true of them. Display
and charge read the **same expression**, which is why the page tracks a mid-cycle unit change for free.

⚠️ **THE TWO-PART PASS — a shape worth reusing for every account-gated seam.** Pass 2 split cleanly:

- **the PASS did, autonomously:** wire the real `STRIPE_PRICE_*` ids, make every failure **loud** (a placeholder
  or missing id throws before a session exists; the seeder's fake fallback throws in production), and prove all
  of it **against the fake client**;
- **the FOUNDER does, and only the founder can:** create the Price objects and fill the production env —
  because `StripeSafetyGuard` refuses a live key outside `production`, by design, untouched.

⚠️ **THE HONEST BOUNDARY IS THE POINT: prove the WIRING; never claim the live Prices exist.** Those are two
different claims and the report must separate them. "The app sends the configured id and refuses a placeholder"
is proven from dev; "the Price behind that id exists with the right amount" is a dashboard confirmation no test
in this repo can make.

⚠️ **A guard for a production-only branch needs TWO-SIDED non-vacuity.** Each loud-fail test **forces the
production context** (`app()->detectEnvironment` + non-fake) — a guard that never enters its branch proves
nothing — **and** asserts the same plan is *fine* outside production, so the test cannot pass by throwing
always.

## §N.17.7 ⚠️ THE BASELINE, RECONCILED — 7 → 5 IS **NEITHER A FIX NOR DRIFT**

⚠️ **A premise correction, measured rather than assumed.** The billing passes were read as taking the non-green
count **7 → 5**, and the natural reading — *the `DO-SEED-1` precedent, where 9 → 7 really was a fix* — is that
something got fixed. **It did not.** Measured at HEAD `9fed4a1`:

- **`BookingImportTest`** — **11/11 in ISOLATION**, fails only in-suite. It contributed **5** on the run in
  question and has historically contributed **6**. Order/state coupling, unchanged.
- **`OwnerUnitDetailViewTest`** — ⚠️ **fails 1 of 13 in ISOLATION, three runs running**, with
  `Unit […] is blocked (inventory_block) over the requested dates` — a genuine **date-coupled** fixture clash —
  **yet it PASSED inside the full suite** on the run that showed 5.

⚠️ **SO THE COUPLING RUNS IN BOTH DIRECTIONS, AND "ISOLATE IT" IS NOT A UNIVERSAL ORACLE.** One documented member
passes alone and fails in-suite; another fails alone and passes in-suite. **The count moved because the roll of
the dice differed, not because anything changed** — nothing in the billing diff touches reservations,
availability or owner units.

⚠️ **THE DOCUMENTED SET, RE-STATED ACCURATELY:** `BookingImportTest` (up to 6, in-suite only) ·
`OwnerUnitDetailViewTest` (1, date-coupled) · `StubViewsTest` (up to 2, date-coupled). **The baseline is the
5–10 RANGE recorded in (J), not a number** — and 5 sits at its bottom edge. ⚠️ **Do not record a range-walk as a
fix.** A false "we fixed two" is the same species of error as a false finding: it retires attention from
something still broken. *Re-measure every gate; when the count moves, prove which member moved and why before
calling it anything.*

---

# (N.18) 🏁 THE DO-BILL PAIR — **THE BILLING CODE IS COMPLETE** (SAVE v13, 2026-08-13)

*Two fixes after the billing workstream closed: **`DO-BILL-2`** (`c6d398a`) — the Add-property modal's
unconditional "billed per unit", rendered from the plan's shape · **`DO-BILL-1`** (`dcce44f`) — the stored VAT
number finally reaching the Stripe customer. ⚠️ **With these the billing CODE is complete and honest
end-to-end. Everything remaining is NON-CODE — the founder's Stripe dashboard steps and one open decision —
plus a single unbuilt panel.** Chapter canon: §N.17 (the workstream) + this.*
> ✅ **UPDATE (SAVE v14): that panel was then built (`f389317`, §N.19), so the BUILD is complete too and nothing
> billing-related remains to write.**

⚠️ **Both were found by READING, not by a failing test** — `DO-BILL-2` by SAVE v12's own coherence sweep,
`DO-BILL-1` by pass 2b's grep. That is now five chapters in which the record's own re-reading outperformed the
suite (§N.13.1, and the five security holes before it).

## §N.18.1 ⚠️ A CLEANUP CHECK MUST BE INDEPENDENT OF THE MECHANISM UNDER TEST (§N.4p, generalised)

Billing pass 2b created a transient per-unit plan for a browser render and reported it *"reverted + verified"*.
⚠️ **The verification was VACUOUS.** `Plan` uses `SoftDeletes`, so `Plan::where('code', …)->delete()`
**soft-deleted** the row, and the check `Plan::where('code', …)->count() === 0` passed — **because the same
global scope that hid the row from the check also hid it from everything else.** The row persisted for two
commits and surfaced only when a later `firstOrCreate` hit a unique-key violation on a row Eloquent insisted
did not exist.

⚠️ **A VERIFICATION ROUTED THROUGH THE MECHANISM IT VERIFIES INHERITS THAT MECHANISM'S BLIND SPOTS.** The check
could not have detected the failure it was written to detect; it could only ever confirm what the scope already
guaranteed. Verify with something **independent** — a raw `DB::table(...)` query, or `withTrashed()`.

⚠️ **THIS GENERALISES §N.4p.** The mutation discipline said *the verdict must come from the guard — not the
fixture, the file, the compiled cache, or a role that cannot see the markup.* The general form is now: **the
check must be INDEPENDENT of what it checks.** Every earlier trap is an instance of it.

## §N.18.2 ⚠️ ON A MONEY/TAX PATH, AN OVER-PERMISSIVE STRUCTURAL CHECK *IS* THE FAILURE MODE

`DO-BILL-1`'s first VAT validator accepted **`"not-a-vat-id"`**. Normalised it becomes `NOTAVATID`; the leading
**`NO` is Norway**; the body passed a bare "alphanumeric, plausible length" test. ⚠️ **A sentence typed into the
field was one step from reaching Stripe as a Norwegian VAT number** — which Stripe might well accept, and which
would **zero-rate a sale that owed tax**.

⚠️ **ON A TAX PATH THE STRUCTURAL CHECK IS NOT A CONVENIENCE — IT IS THE SAFETY MECHANISM.** "Permissive" does
not mean "a weaker guard"; it means "the bug". The check is the only thing standing between a typo and a
mis-typed tax identity.

Fixed by requiring a **run of at least four digits** in the body: every real VAT number has one, prose has none.
⚠️ **Tighten to what a real identifier HAS, not to what an invalid one lacks** — you cannot enumerate the ways a
string can be wrong, but you can state what a right one looks like. *The guard caught this before it shipped,
which is the argument for writing the unhappy-path test first.*

## §N.18.3 ⚠️ ROUTE EVERY WRITER OF A MONEY-AFFECTING FIELD THROUGH ONE SYNC PATH (§N.8f, on WRITE paths)

`billing_vat_id` had **three** in-app writers — Billing details, the subscribe action, and **Settings → invoice
details**, the third found only by sweeping for writers rather than trusting the two that were obvious.

⚠️ **WITHOUT A SINGLE SYNC, THE REVERSE-CHARGE OUTCOME WOULD HAVE DEPENDED ON WHICH SCREEN THE OPERATOR USED.**
Same customer, same number, different tax — decided by an irrelevant UI choice. That is not a missing feature;
it is a money outcome determined by navigation.

`StripeTaxIdSync` is the ONE path, called from all three writers **and** the webhook. ⚠️ **§N.8f has always said
one definition per concept for READ paths** (`MoneyFormatter`, `FolioTotals`, `Plan::chargeFor`); this is its
**write-path** form: **many writers, ONE sync.** The outcome must not depend on the entry point.

## §N.18.4 ⚠️ FAIL SAFE — AND THE DIRECTION IS DECIDED BY WHICH ERROR IS SILENT

A VAT ID drives the tax outcome, and the two failure directions are **not symmetric**:

| direction | what happens | how it is discovered |
|---|---|---|
| overcharge (VAT applied when reverse charge was due) | customer pays too much | **visible** — they complain; refundable |
| wrong zero-rating (reverse charge applied wrongly) | customer pays €0 | **silent** — the operator carries it to an audit |

⚠️ **SO THE FAIL-SAFE DIRECTION IS SET BY WHICH FAILURE IS SILENT: when in doubt, CHARGE THE TAX.** An
untypeable number is never sent · a Stripe rejection **returns false rather than throwing** (a bad VAT number
must not break a subscription) and is **never treated as success** · both are logged **without the value**,
which is a customer business identifier · a corrected number **replaces** the stale one rather than joining it,
because two numbers on one customer is ambiguous and the stale one could win.

⚠️ **Generalise it:** when choosing which way a guard should fail, do not ask which error is *smaller* — ask
which error is **silent**. Bias toward the one that announces itself.

## §N.18.5 ⚠️ GUARD THE SPECIFIC VACUITY THAT LET THE BUG HIDE · AND THE WEBHOOK-IS-THE-ONLY-ATTACH-POINT REFINEMENT

**`DO-BILL-2` survived the entire design walk because it was ROLE-GATED** (`@if ($canManage)`). Rendered as a
role without `properties.manage`, the modal is simply absent — so *"no false claim"* is **vacuously true**
(§N.14.1). ⚠️ **The guard therefore pins the trap itself**, not only the fix: one test asserts a Front Desk user
is refused the page (403), and **every copy assertion is preceded by a presence assertion** on the modal. *Test
the fix; then test the reason the bug was invisible.*

⚠️ **AND `DO-BILL-1` PRODUCED A PREMISE REFINEMENT WORTH KEEPING: the app NEVER creates a Stripe customer.**
Checkout does, and the id arrives by **webhook**. So the webhook sync is not an extra call site — it is the
**only** attach point for a first-time customer. ⚠️ **It is also where the money is: Stripe re-computes tax on
every RENEWAL invoice**, so a missing tax id would have overcharged a reverse-charge business **every month**,
not once. *Reading how the object is actually created changed the fix from a nicety into the main path.*

## §N.18.6 ⚠️ RECORD AN UNEXPLAINED, NON-REPRODUCING ANOMALY *AS* UNEXPLAINED (§N.17.7, reinforced)

One full-suite run at `DO-BILL-1`'s commit showed an **8th** non-green — one beyond the documented set. It did
**not reproduce** on two subsequent full runs (both exactly 7 = `BookingImportTest` ×6 + `OwnerUnitDetailViewTest`
×1), and it could **not be identified**.

⚠️ **It is recorded as an unexplained range-walk within the documented 5–10 band — NOT claimed as proven
pre-existing, and NOT waved away.** There was a real temptation to write "within the documented range" and move
on; that phrasing would have been true and also a small false certification, because *nobody checked*.

⚠️ **§N.17.7 reinforced.** "Isolate it" is not a universal oracle (one member passes alone and fails in-suite;
another fails alone and passes in-suite), the count walks its band on its own, and **an anomaly you cannot
explain is written down as unexplained.** The register's value comes from the difference between "verified" and
"assumed" staying visible — including when the assumption is the comfortable one.

---

# (N.19) 🏁 THE PLAN PANEL — **TERMS FREEZE, QUANTITY STAYS LIVE** (2026-08-13)

*The last billing build. Two founder rulings: **full CRUD of both shapes**, and **edits affect NEW subscriptions
only — existing subscribers keep their terms**. The second turned out to be the whole pass.*

## §N.19.1 ⚠️ THE PREMISE CHECK CHANGED THE PASS — AND FOUND A LIVE DEFECT

The pass was specified as "build the panel". Checking first what actually existed found **the panel already
did** — `index`, `edit`, `update`, `toggle`, platform-admin gated and audited. Missing were **create**,
**shape-awareness**, and any concept of *sellable*.

⚠️ **More importantly, `update()` already let an admin change `price_minor` on a plan with live subscribers** —
and `subscriptions` stored only `plan_id`, so every price the app displayed was read from the LIVE plan row.
**The founder's ruling was not merely unimplemented; it was unenforceable, and the surface that would violate it
was already shipped.**

⚠️ **AND THE FAILURE MODE WAS NOT THE OBVIOUS ONE.** The natural reading of "an edit re-prices existing
customers" is wrong for this architecture: Stripe bills an existing subscription against the **Stripe Price
object** it was created with, and nothing here pushes a rate change to it. So an edit would **not** have moved
the money — it would have moved only the number Vyrobel *displays*, leaving the product quoting one figure while
Stripe charged another. **The display ≠ charge divergence four billing passes closed, re-opened from the admin
side.** *Reading the mechanism, rather than reasoning about the feature, is what turned a plausible risk into
the actual one.*

## §N.19.2 ⚠️ TERMS FREEZE, QUANTITY STAYS LIVE — §N.8f's derived-vs-stored tension, inverted

Billing pass 1 established that the billable **QUANTITY** must be **DERIVED**, never stored: a stored count is
right until the next unit is added, and a charged quantity that disagrees with reality is §N.8d with money
attached (`BillableUnits`).

⚠️ **The agreed TERMS are the exact opposite: they must be FROZEN.** A price that moves when someone edits a
catalogue row is not "up to date", it is a different agreement than the customer entered into. So:

| | | |
|---|---|---|
| **quantity** | DERIVED, live | it must track reality — the org really does owe for a new unit |
| **terms** (rate, shape, Price, currency) | CAPTURED, frozen | they must NOT move when the catalogue is edited |

Both live in one place, and **the arithmetic is shared** (`PlanTerms`): `Plan::chargeFor()` and
`Subscription::chargeFor()` build the same object and call the same method. ⚠️ **Two sources of terms with two
calculations is precisely how `SubscriptionPricing` drifted from `Plan::chargeFor()`** and put €88.56 on one
screen against €49.00 on another (§N.17.4). The sources differ; the maths does not.

## §N.19.3 ⚠️ ENUMERATING WRITERS IS A LOSING GAME — BIND THE BEHAVIOUR TO THE MODEL

Capture was first wired at each **known** call site: the billing service, the admin customer screen. Verifying
`migrate:fresh --seed` then showed the demo subscription with **null terms** — because `DemoSeeder` creates rows
directly and was a call site nobody had listed.

⚠️ **This is `DO-BILL-1`'s three-VAT-writers lesson (§N.18.3) recurring within one pass, and it upgrades the
remedy.** There the fix was *route every writer through one sync path*; here even that was not enough, because
the list of writers is not knowable — seeders, factories and future code are all writers. **The capture moved
onto the model's `creating`/`updating` hooks**, so every path that persists a subscription captures, including
ones that do not exist yet. *One sync path is good; a path nobody can bypass is better.*

⚠️ Note how it was caught: not by a test, but by **checking the demo database after the seed** — the same
verify-the-outcome habit that caught the SoftDeletes leftover (§N.18.1).

## §N.19.4 ⚠️ ON A MONEY SURFACE, "SELLABLE" IS A PROPERTY TO ENFORCE, NOT A JUDGEMENT TO TRUST

`PlanIntegrity` refuses to persist a plan the engine cannot charge — a per-unit plan with **no rate** (it would
bill €0 × units), a **zero** rate (the same, quietly), an **unknown shape** (it falls through `isPerUnit()` to
the flat branch and bills the wrong basis). ⚠️ `pricing_type` is a **closed set**, never a free string.

⚠️ **And a plan whose Stripe Price does not exist is a DRAFT, not a product.** The panel can create a plan; only
the founder can create the Price behind it (`StripeSafetyGuard` forbids a live key outside production).
Activating one regardless would put a tier on the paywall whose checkout cannot complete — the broken checkout
billing pass 2 made *throw*. **Making it unreachable is the better half of the same guard**: pass 2 catches it
at the till, this catches it at the shelf.

⚠️ **ONE RULESET FOR CREATE AND EDIT** (§N.8f). The old `update()` validated only `name` + `price`; two rulesets
would drift, and the looser one becomes the way to save a malformed plan.

## §N.19.5 ⚠️ G8 — TWO PRE-EXISTING TESTS LEGITIMATELY CHANGED, AND THE FIXTURE WAS THE UNDER-SPECIFIED PART

`PlatformPlansTest`'s plan fixture carried no `pricing_type` and no `stripe_price_id`, and its update payloads
sent only `name` + `price`. Under the stricter contract both fail — **correctly**: they described a plan that
could not really be sold and a save that could not really be valid.

⚠️ **The invariants were preserved and the FIXTURES amended** — the plan now carries a shape and a Price (it
represents a live plan, which is what those tests are about), and the payloads carry the shape. *Nothing about
what those tests assert was relaxed; what changed is that the world they describe is now internally consistent.*

## §N.19.6 ⚠️ THE CAPSTONE — AN INVARIANT PROVEN ON ONE SIDE OF THE APP CAN HAVE A BACK DOOR ON ANOTHER

Four billing passes established `display = charge = Stripe` and proved it from the **customer's** side: the page
reads the engine, the engine's figure is what reaches Stripe, and no surface claims a tax it cannot know.

⚠️ **The plan panel could break all of it with one form submit, and it was already shipped.** Not by charging
the wrong amount — by editing a catalogue row so the app *displayed* a different price than Stripe was billing.
The invariant was never tested from the **admin's** side, because the admin is not who the invariant was written
about.

⚠️ **THE GENERALISATION, and it outlives billing: an invariant is only as strong as the set of ACTORS it was
checked against.** The billing passes checked the customer, the webhook and Stripe. They did not check the
platform admin — a different realm, a different guard, and a surface that writes the very rows the whole chain
reads. **When an invariant matters, enumerate who can write its inputs, not just who reads its outputs.**

This is the fourth distinct way a defect has hidden from a pass in this project, and it belongs beside the other
three (§N.16.2): conditional **STATE** · **AUTHORIZATION** · **DATA** · and now **ACTOR** — the surface was
neither hidden nor conditional, it simply belonged to somebody the pass was not thinking about.

---

# (N.20) 🏁 THE PRE-BILLING TAIL — **THE PROBE SAGA AND A RETRACTION** (SAVE v15, 2026-08-13)

*Six passes after the billing chapter: `DO-TOKEN-1` (`92c7b9b`) · `DO-STAR-1` (`5864514`) ·
`DO-EMAIL-5-continued` BLOCKED (`2bb4b54`) · `DO-PROBE-1` (`600f1bc`) · the re-run stopped (`8440b7e`) ·
`DO-PLATE-1` + the on-gradient fix (`568cdd3`, `dd5ecb9`) · `DO-SUP-1` (`9eebdb5`) · `DO-PROBE-2` (`5f96247`).*

⚠️ **THE HEADLINE IS NOT A FIX — IT IS A RETRACTION.** This stretch spent most of its effort building a
measuring instrument, discovered the instrument had manufactured a product defect, and withdrew the finding.
That sequence, not any individual pass, is what these sections are for.

## §N.20.1 ⚠️ THE INSTRUMENT SAGA — FOUR corrections to ONE tool, every one caught by a KNOWN-ANSWER fixture

`tools/contrast-sampler.php` exists because `getComputedStyle` cannot see a gradient, and this product paints
gradients on the body, on cards and on buttons. Getting it right took **four** corrections, and **every single
one produced a plausible number first**:

| # | the wrong reading | the correction |
|---|---|---|
| 1 | the single darkest pixel was the AA halo | a 10th-percentile worst |
| 2 | halo dominates dense text even after an ink filter | adaptive halo rejection (>45% toward the ink) |
| 3 | in a dense small-text box the halo is the MAJORITY of the interior | read **clear columns beside the glyph run**, never the interior |
| 4a | the true minimum read the element's own EDGE blend (accent anchor 2.08) | a 5% percentile — **columns remove the halo, the percentile removes the edge; they are complementary** |
| 4b | "clear" meant *far from the ink*, so a 70%-toward-background fringe qualified (dense line 4.07 vs a known 4.50) | "clear" means **IS the plate** |

⚠️ **THE LESSONS, and they are the durable output of the whole stretch:**

- **An instrument that produces PLAUSIBLE NUMBERS is more dangerous than one that CRASHES.** Every correction
  above was found by a fixture with a **known answer**, never by the numbers looking wrong.
- ⚠️ **Anchor-validation proves a tool can measure SOMETHING — not that it measured the RIGHT PLATE.** One
  early probe reproduced both anchors exactly *and* scored a white button label 1.2 on cream. Validate on the
  HARD case: a white label on a gradient **button**, read on the **button's** plate.
- ⚠️ **"VALIDATED" IS PER-INPUT-CLASS.** A fixture certifies the tool only for its own input distribution. The
  first harness used sparse text in padded blocks and certified nothing about dense 12px labels — the class
  that actually broke it. **Validate on the hardest representative case, and NAME the classes still untested**
  (here: dense-small-text on a *steep* gradient, and text over a photo backdrop — both named unvalidated).
- **Sample the element's OWN box** — a button's plate is the button's gradient, not an ancestor card's.
- **The worst PIXEL and the worst PLATE POSITION diverge under anti-aliasing** — read a low percentile of
  genuine plate, never the extremum.
- **Fail loud:** UNMEASURABLE reports a *reason*, never a number; every result carries
  N-measured / K-failed / M-unmeasurable, so a 0-failures/0-measured cannot pose as a clean bill.
- ⚠️ **"Publish nothing when the probe breaks" governs a CLEAN BILL as much as a defect list.** One probe
  version was honest and blind — 166 unmeasurable, "0 failures". That is the most dangerous output available.
- **COMMIT the validation harness** (`ContrastSamplerValidationTest`, 11 tests) so the next session re-verifies
  the instrument in seconds instead of re-deriving it and re-paying the whole saga.

## §N.20.2 ⚠️ THE RETRACTION — a finding is not safer for being OURS

The stopped re-run (`8440b7e`) reported a **product-wide AA floor defect**: `DesignTokenContrastTest` assumed a
card plate lighter than the real page, with `--vy-faint` measuring 4.19 and 4.07. It was written into the
register as a defect.

⚠️ **`DO-PLATE-1` measured it properly and RETRACTED it.** Those readings were **anti-alias halo** from inside a
dense 12px box. The true card plate, sampled away from glyphs, is **4.50 – 4.67**, and the test's constant
(4.55) sits **inside** that range. **The constant is sound; there was never a product-wide card defect.**

⚠️ **THE TELL that caught it:** the box-interior median (4.35) read **darker than the true plate immediately
beside the same text** (4.50+). That is physically impossible for a real plate — only ink-blend darkens *at* the
glyphs. An independent adjacent-plate sample is what exposed it.

⚠️ **THE LESSON: a finding is NOT safer for being ours.** A self-authored, already-committed conclusion deserves
*more* scrutiny than an inherited one, because sunk cost and face cost both push the other way. The floor
finding was reasonable when made and wrong under measurement, and saying so plainly is cheaper than defending
it. *This is the fourth chapter in which re-reading beat the suite — and the first where what it caught was our
own claim.*

✅ **What WAS real was isolated from the same data and fixed:** text sitting directly on the page gradient
measured **3.51**; `.vy-topbar-sub` was routed to `--vy-on-grad` (`dd5ecb9`), 3.65 → 4.50.

## §N.20.3 ⚠️ THE HEADROOM POSTURE — ruled **A (accept)**, with a floor-guard rider

> ⚠️ **SUPERSEDED IN PART BY §N.21 — read this paragraph as the RECORD OF A DECISION, not as current fact.**
> Both figures below were later falsified by measurement: **`--vy-faint` is 4.4953**, not 4.50 (§N.21.1 — the
> 2-dp rounding), and **`--vy-on-grad` was 4.33–4.48** on the plates its users actually sit on (§N.21.3 — it
> had been derived against a *picked* stop, and has since been re-derived by sampling to `rgb(68,59,54)`).
> ⚠️ **The POSTURE still stands** — don't darken a token that MEETS AA for headroom. What changed is which
> tokens were found to meet it.

⚠️ **FOUNDER DECISION — A (accept).** `--vy-faint` and `--vy-on-grad` clear the true worst plate at **exactly
4.50**: AA is **met**, with zero margin. **B** (darken the tokens) and **C** (lighten the gradient stop) were
**declined** — both are product-wide visual changes, and this is a robustness preference, not a compliance
requirement. The warm-glass palette is deliberate and AA-correct.

⛔ **THE RIDER, carried into the sweep — GUARD THE FLOOR.** `DesignTokenContrastTest` asserts against a
**constant**, so a future gradient tweak that erodes 4.50 would **not** red it. That is the silent-drift vector
the zero margin creates. Re-point the test at the **true composited worst plate** (at minimum re-pin the
constant to `rgb(250,221,206)` = 4.50; better, measure it with the validated sampler) so drift **reds the
test**. *Make the drift structural rather than a hope* — the same principle as `DO-TOKEN-1` below.

## §N.20.4 ⚠️ VERIFIED-FINE, STRUCTURALLY — the strongest §N.8d safeguard is a MISSING FIELD

`DO-SUP-1` closed **verified-fine**: the support phone renders `tel:`-only, and it is honest **by
construction** — `SettingsService::SUPPORT_FIELDS` has **no WhatsApp field**, so a fabricated `wa.me` channel is
**unrepresentable**, not merely discouraged. Keyed-not-typed at its purest: **the absence of the slot IS the
safeguard.**

⚠️ **Premise corrected:** this was a deliberate build-time decision, already documented at
`PropertySupport.php:30` — *"PHONE, NOT WHATSAPP … nothing verifies a number answers on WhatsApp."* The register
entry was a **deferral note, not a defect report**.

⚠️ **A comment documenting a deliberate ABSENCE is the good kind.** It is not load-bearing — the missing field
is — but it records *why*, which stops a well-meaning future session from "completing" the omission. The
constructive inverse of *a comment is not a defence*.

N.13.1 neighbours were checked and are clean: hours render the operator's own words when set; the reply norm is
keyed and hedged, never "guaranteed"; "WhatsApp" elsewhere is only a channel-record enum and a
throws-until-configured skeleton.

## §N.20.5 ⚠️ CLOSE A DRIFT VECTOR STRUCTURALLY · AND THE LIST YOUR PROOF ITERATES OVER IS ITSELF AN INSTRUMENT

`DO-TOKEN-1` moved the **31 shared tokens** into one canonical `components/design/tokens.blade.php`, included
before each shell's `<style>`, preserving the **7 deliberate** per-shell divergences.

⚠️ **A shell can no longer diverge a token it no longer DECLARES.** The strongest fix does not guard a defect
family — it makes it **unrepresentable**. (The same shape as §N.20.4's missing field, and the shape the
floor-guard rider is asking for.)

⚠️ **AND THE LIST ITSELF WAS AN INSTRUMENT.** The hand-typed token list **invented seven tokens that do not
exist and omitted thirteen that do** — caught only because the *parsed* and *resolved* counts disagreed.
**Regenerate the set your proof iterates over programmatically, and cross-check its size.** A proof is no
stronger than the enumeration it runs over.

**The deliberate-vs-accidental criterion, recorded:** *drift produces arbitrary differences; theming produces a
direction.* The five guest divergences all move one way (smaller, softer, warmer) — theming. `--vy-amber-text`
had two unrelated values — drift.

No-visual-change was **proven by resolved-value diff** (0 changed across four shells), and one latent bug fell
out: public's `--vy-wash` was undeclared, so a review-response background rendered transparent.

## §N.20.6 ⚠️ AN ACCESSIBLE NAME CAN BE CORRECT-THEN-OVERRIDDEN · AND REVEAL-ON-HOVER IS TABBABLE-BUT-INVISIBLE

`DO-STAR-1` was **verified-fine on the flagged concern** — the Messages star is a bookmark whose state is
carried by glyph **shape** (filled vs outline), so DO10-4's colour-only failure is absent. ⚠️ **And the same
measurement found two defects BESIDE it** (N.13.1 again):

1. ⚠️ **A static `aria-label="Star"` OVERRODE the correctly-computed `title="Unstar"`** — a screen-reader user
   heard the wrong action on a toggle. The right words were being derived and thrown away one attribute later.
   **Put toggle STATE in `aria-pressed` and keep the NAME stable.** *An accessible name can be
   correct-then-overridden, not merely missing* — a failure mode no "is it labelled?" check catches.
2. ⚠️ **A `:hover`-only action bar left in the tab order** — focusable, invisible (WCAG 2.4.7). **Pair
   reveal-on-hover with reveal-on-`:focus-within`.** Not applied to Reply/React, which are not toggles.

⚠️ **Instrument note:** a `getComputedStyle` opacity read taken **mid-transition** returned a false `0` — the
fix was correct, the measurement was early. §N.8e's freeze rule, in a new guise.

---

# (N.21) 🏁 THE A11Y FAMILY, AXIS 3 PROVEN, AND THE CHECKED CAPTURE CONTRACT (SAVE v16, 2026-08-13)

Five passes since v15: Part A (`921dae3`) and its floor-guard rider (`923435d`), DO-A11Y-1 with DO-A11Y-2
folded in (`bcbc651`), Part B (`2b11af6`), and the throughput pass (`43e9f0d`). The through-line is that the
**instrument and the record kept being wrong in ways only verification caught** — and that the fixes were
mostly at a level *above* the thing flagged.

## §N.21.1 ⚠️ THE FLOOR-GUARD FALSIFIED ITS OWN PREMISE — the 0.005 miss

§N.20.3 ruled posture **A (accept)** on the grounds that `--vy-faint` clears the true worst plate at **exactly
4.50** — AA met, zero margin, so darkening is a robustness preference rather than a compliance need. The rider
carried into the next pass was purely defensive: re-point `DesignTokenContrastTest` at the true composited
plate so drift REDS the test.

⚠️ **Pinning the measured plate turned the test red.** `--vy-faint` on `rgb(250,221,206)` is **4.4953**, not
4.50 — the earlier report gave the **2-decimal rounding**, and the decision was taken on that rounded number.
Unrounded, it **misses AA by 0.005**.

⚠️ **RESOLVED NON-DESTRUCTIVELY, NOT SETTLED.** Both ways to green the test belong to the founder: darkening
the token is option **B** (declined), and loosening the assertion to 4.49 is **quietly lowering a compliance
bar nobody was asked to lower**. So the AA test keeps the *derived* plate `rgb(249,223,207)` where it genuinely
passes at 4.55, and the **measured floor is pinned separately as a FACT, not as a compliance claim** — which
closes the silent-drift vector whichever way the call goes. Mutation-proven; the file's stale header (which
documented `--vy-faint` as `rgb(123,114,108)`, two token generations old) corrected in the same change.

> **The lesson is about PRECISION IN A DECISION RECORD.** A rounded figure is fine in prose and fatal in a
> premise. When a number decides something, record the precision the decision actually needs.

⚠️ **FOUNDER RULING (2026-08-13): ACCEPTED.** The 0.005 stands as measured and **no token was darkened** —
4.4953 is far below any perceptual or measurement threshold, and darkening `--vy-faint` product-wide for it
would be the option-B change posture A already declined. **The non-destructive handling STANDS unchanged**: the
AA test keeps the derived plate `rgb(249,223,207)` where `--vy-faint` genuinely passes at 4.55, and the
measured **4.4953 floor stays pinned separately as a FACT**, so the silent-drift vector remains guarded — any
future gradient or glass change that darkens the card still REDS the test. ⚠️ **This closes the founder call;
it does NOT change the measurement, and §N.21.2's coherence note is unaffected** (the 0.005 still refines the
retraction's ~4.50 rather than overturning it).

## §N.21.2 ⚠️ THE COHERENCE NOTE — the 0.005 is NOT the retracted floor finding

⚠️ **A future session must not read §N.21.1 as overturning §N.20.2.** They are distinct and they AGREE:

| | the RETRACTED finding (§N.20.2, `8440b7e` → withdrawn `568cdd3`) | the 0.005 (§N.21.1, `923435d`) |
|---|---|---|
| claim | a **product-wide AA floor defect**: `--vy-faint` at 4.19 / 4.07 | `--vy-faint` = **4.4953** on the true worst plate |
| source | the **halo** inside a dense text box — an instrument artefact | the **plate**, sampled away from any glyph |
| status | **RETRACTED, stays retracted** | **standing**, an open founder call |

The retraction's own conclusion was that the true card floor is **~4.50–4.67 and SOUND**. The 0.005 does not
contradict that — it **refines the "~4.50" to a measured 4.4953**. *The retraction was right; this is a
precision refinement of it.* Both must survive in the record together.

> ⚠️ **FORWARD POINTER (§N.22, DO-RESTORE-1):** a founder directive asked for the wireframe accent orange to
> be restored system-wide, which would supersede parts of this section. **The audit found the premise does not
> hold on this product** — the brand accent was never darkened (only the CTA gradient's light stop and a
> text-only token), and there is **no large text on orange anywhere**, so the 3:1 bar applies to nothing.
> **Nothing was restored; the options are recorded in §N.22.4 awaiting a ruling.** This section stands as
> executed until that ruling lands.

## §N.21.3 ⚠️ THE A11Y FAMILY — two items were ONE defect, and the root was a shell that missed a fix

DO-A11Y-1 (the shared classes) and DO-A11Y-2 (the `--vy-on-grad` token) were logged as separate items.
**Sorting the failing classes BY THE PLATE THEY LAND ON collapsed them into one:**

- on a **card**, or on a tag's own tint → `--vy-on-grad` / `--vy-muted` already clear them; a **mapping** suffices.
- on the saturated **page gradient** → **nothing in the palette cleared them except `--vy-ink`**, because
  `--vy-on-grad` — the token that exists precisely for that plate — was **itself sub-AA**.

⚠️ **DO-A11Y-2 was the root cause of half of DO-A11Y-1, not a stray crumb.** Fixing the classes was impossible
until the token was fixed, because there was nowhere honest to map them to.

⚠️ **THE ROOT: the OPERATOR shell never received the OWNER shell's O2/O7 fix.** Both `--vy-on-grad` and
`--vy-accent-text` were derived against a **PICKED** gradient stop rather than by **SAMPLING** the plate behind
the elements that use them. The owner shell had exactly this defect, fixed it by sampling **7,502 pixels**, and
left a note reading *"Re-measure by SAMPLING, not by picking a pixel."* The operator shell never got that
treatment — **§N.8f cross-shell: the same concept, fixed in one realm and left broken in the other.**

| token | derived against | actually sits on | old reading |
|---|---|---|---|
| `--vy-on-grad` rgb(84,74,68) | rgb(241,185,150) *picked* | down to **rgb(235,152,106)** | **4.33–4.48 FAIL** |
| `--vy-accent-text` rgb(176,56,8) | rgb(252,223,204) *picked* | down to **rgb(249,194,166)** | **3.82–4.46 FAIL** |

⚠️ **A FALSE COMPOSITING PREMISE, CORRECTED.** The old comment claimed *"a tinted chip carries its own surface
and already measured 4.61."* False: a chip's tint composites **over the card, over the gradient**, so the chip
is **darker** than the card, never lighter.

⚠️ **THE WORST TWO READINGS WERE A DIM, NOT A COLOUR.** `.vy-avatar` **2.27** and `.vy-plan-tag` **2.46** are
white text on a light accent tint, so the **surface** moved to `--vy-accent` (white = 5.18). `.vy-plan-tag` was
*still* failing at **3.07** afterwards — an ancestor `.vy-nav-item[aria-disabled]` at **`opacity:.55`** dimming
a locked item's label to **2.89** and its "PRO" tag to **2.42**. Modelling the *painted* pair, even `.9` leaves
the tag failing. Removed — **§N.16.6 verbatim**, and nothing is lost because the explicit "PRO" text and
`aria-disabled` already carry the state (the redundant mark the rule asks for).

⚠️ **COMPLIANCE-VS-HEADROOM IS DECIDED PER TOKEN.** Darkening a token that **MISSES** AA is required;
`--vy-on-grad-soft` **passed** on every sampled page and was deliberately **left unchanged** — darkening it
would be the posture-B change the founder declined. *The guard asserts the non-change, so a future
"consistency" tidy-up cannot quietly make it.*

## §N.21.4 ⚠️ AXIS 3 PROVEN SWEEPABLE — and the CHECKED CAPTURE CONTRACT

**§N.16.2's third axis was swept for real.** The taxonomy is now complete in PRACTICE:

| axis | how a surface hides | the forcing move |
|---|---|---|
| 1 STYLE/STATE (G10) | a hover/open/error state | **force the state** |
| 2 AUTHORIZATION (§N.14.1) | gated markup absent for the role | **render as the role** |
| 3 **DATA** | markup that does not exist until a list is populated / a search performed / a record reaches a state | **force the DATA condition** |

It found what an unswept axis predicts — defects on markup no empty-state pass could see: the owner Expenses
row chips (**4.12** / **4.48**), the guest NAME on a **booked** calendar cell (**4.05**, which cannot exist
without a booking), the calendar legend (**4.29**), and My Units' attention centre (**4.13–4.44**).

⚠️ **TWO MORE CAPTURE FAULTS, AND THEY FABRICATED DEFECTS AGAIN** — the fifth and sixth corrections to this
instrument (§N.20.1 counted four). The first populated page measured reported **nine avatars at 1.17–1.26**:
the **viewport was shorter than the document** (a 2939px page in a 1650px viewport), and a **Livewire redirect
re-rendered the DOM between collection and capture**, leaving every coordinate stale. Hand-reading the avatar's
own pixels showed the card colour. **A fabricated defect, caught by verification — the third time in this
family.**

> ⚠️ **THE FIX IS THE PRINCIPLE: A BROKEN CAPTURE MUST REFUSE, NOT FABRICATE.** The collector now emits
> `viewportH` / `viewportW` / `url`, and the reporter **REFUSES to print numbers** when the document exceeds
> the viewport or the screenshot is short (proven firing). *An instrument that cannot detect its own broken
> preconditions will report them as findings.*

⚠️ **THE ANCESTOR-DIMMING SAFEGUARD RETURNED 0 ACROSS THREE PASSES — and that is a MEASURED FINDING.** These
data-conditional states genuinely do not use ancestor opacity. A zero that was *checked* is not the same as a
zero that was *assumed*, and the record says which this is.

## §N.21.5 ⚠️ "THE CLASS IS FIXED" NEEDS EVERY COPY · AND MOVING A TOKEN MEANS MOVING ALL ITS MIRRORS

⚠️ **§N.8f, twice in two passes, in its two different failure modes:**

1. **EVERY COPY.** DO-A11Y-1 fixed `.vy-avatar`'s **operator** copy and reported the class fixed. Part B found
   the **owner** copy still carrying `var(--vy-grad)`, whose light stop `#E8703A` gives white **3.08**. The
   completion claim was true *for one of two definitions*. **A shared name with per-shell copies is not fixed
   until every copy is enumerated.**
2. **ALL MIRRORS.** Darkening `--vy-amber-text` in the token file while its **email and PDF literals** still
   read `#804C05` is precisely two-values-one-name — the drift `AmberTextTokenTest` exists to catch, and it
   **caught it red**. The canonical value moved everywhere at once instead, and only **improved** the surfaces
   it already served (email 6.39 → 7.64, PDF pill 6.24 → 7.47). *Email and PDF cannot reach a CSS token, so a
   token with mirrors is a distributed value, not a single one.*

## §N.21.6 ⚠️ A CLASS'S MEASURED COLOUR IS NOT ITS RULE'S COLOUR · AND "CLEAR THE PLATE", NOT "DARKEN"

⚠️ **TRACE THE CASCADE TO THE WINNING DECLARATION.** `.vy-mu-stat-label` painted **4.24**, but its *rule* was
already `--vy-muted` — an **inline `style="color:var(--vy-faint)"` won the cascade**. Darkening the rule would
have changed a **passing** declaration (a posture-A violation) **and not fixed the defect**. *The colour an
element paints is not necessarily the colour its class declares.*

⚠️ **THE FIX DIRECTION FALLS OUT OF THE PLATE.** One fix in the family went **lighter**: on the dark
performance card `--vy-accent-3` read **4.30**, so the label moved to `--vy-peach`. Every other fix darkened.
The rule is **"clear the plate you are actually on"** — "darken the text" is a heuristic that happens to be
right on light grounds and wrong on dark ones.

⚠️ **REPORTING DISCIPLINE: the measurement is the evidence, not the crop.** Part B shipped a before/after crop
that clipped the very chips it illustrated; the throughput pass sized its crop from the **measured element
boxes**. A bad illustration does not weaken a measurement, but it must not be offered as one.

## §N.21.7 ⚠️ THE PROBE-HARNESS GAP · THE THIRD PROBE GAP · THE GLOBAL-CONST TRAP

⚠️ **THE COLLECTOR HAD NEVER BEEN COMMITTED AT ALL.** The two Part-A capture corrections lived entirely in one
session's tool calls — `tools/contrast-sampler.php` was (correctly) unchanged, because those corrections belong
to **candidate generation**, not plate sampling. **A correction that is not committed is a correction hoped.**
Now `tools/contrast-collect.js`.

⚠️ **A THIRD SCOPE GAP (DO-PROBE-3):** an element under a **dimmed ancestor** passes the own-opacity check
while painting far below its declared colour — a **false clean bill**, the worst output this tool can produce.
**Detect-and-flag is IN** (the collector refuses such elements with a reason); **correct computation** — 
compositing both glyph and plate at the effective alpha — remains the open work.

⚠️ **THE GLOBAL-CONST TRAP.** A top-level `const` in a Pest file is a **GLOBAL**. `WORST_GRADIENT_PLATE`
collided across two test files: the guard **passed alone and failed in-suite**, against the *other* file's
plate. **PREFIX per-file test constants, and run a new guard IN-SUITE, not just isolated** — the mirror of
§N.17.7's warning that "isolate it" is not a universal oracle.

## §N.21.8 🧭 THE STATE AFTER v16

- **The design programme is COMPLETE IN METHOD.** Part A (role axis), the shared-class family (source-level),
  and axis 3 (data axis) are all proven; what remains of axis 3 is **throughput, not method**.
- **The instrument is VALIDATED with a CHECKED CAPTURE CONTRACT** — **six** corrections total (§N.20.1's four,
  plus viewport-vs-document and redirect-between-collect-and-capture) — and **named scope gaps**:
  ancestor-dimming (flagged), dense-text `no-clear-band`, steep gradients, photo backdrops, and **dompdf PDFs,
  which a browser probe cannot reach at all**.
- **The AA floor is SOUND** (posture A stands) **with the 0.005 precision-miss open** as a founder call.
- **Billing CODE + BUILD are complete**; the flip is gated only on the founder's Stripe/VAT steps.
- ⚠️ **"Closed" means closed everywhere a pass could REACH** — bounded by role, by data condition, and now
  explicitly by **probe scope**. An UNMEASURABLE is **UNSWEPT**, never clean.

---

# (N.22) ⚠️ DO-RESTORE-1 — THE WIREFRAME-ORANGE AUDIT (2026-08-14)

**FOUNDER DIRECTIVE:** restore the original wireframe accent/brand orange system-wide (buttons + layout
accents, all shells/roles/portal), on the reasoning that WCAG AA is **3:1 for large/bold text** — so button
labels, being large/bold, would already pass on the wireframe orange and the restore would cost nothing.

⚠️ **THE AUDIT RAN FIRST, AND IT FALSIFIES THE PREMISE. NOTHING WAS RESTORED.** Reported rather than acted on.

## §N.22.1 ⚠️ PREMISE CORRECTION 1 — the brand orange was never darkened

The directive assumes the contrast programme darkened "the brand orange". It did not. Diffing the current tree
against SAVE v15 (`291a534`, pre-programme):

| token | v15 (wireframe) | current | changed? |
|---|---|---|---|
| `--vy-accent` | `rgb(194,65,12)` | `rgb(194,65,12)` | **NO** |
| `--vy-accent-2` | `rgb(217,102,46)` | `rgb(217,102,46)` | **NO** |
| `--vy-accent-3` | `rgb(232,112,58)` | `rgb(232,112,58)` | **NO** |
| `--vy-accent-border` | `rgba(232,112,58,.28)` | unchanged | **NO** |
| `--vy-grad` **light stop** | `#E8703A` | `#D14102` | **YES** |
| `--vy-accent-text` | `rgb(176,56,8)` | `rgb(147,42,7)` | **YES** (a TEXT token, never a fill) |

**The layout accents — borders, dots, tints, decorative bars, chips — are already the wireframe orange and
always were.** ~42 standalone `#E8703A` uses were deliberately preserved in DO-A11Y-3. **There is nothing to
restore there.** What changed is (a) the CTA gradient's light stop, and (b) a text-only colour. Plus three
element-level remaps (`.vy-avatar`, `.vy-plan-tag`) whose *backgrounds* moved.

## §N.22.2 ⚠️ PREMISE CORRECTION 2 — this product has NO large text on orange

WCAG AA large text = **≥24px regular, or ≥18.66px BOLD**. Measured across the operator shell and front desk,
the full distribution of text-on-orange:

`17px/600` · `13.5px/600` · `13px/600` · `12.5px/500` · `12px/600` · `11.5px/700` · `11px/500` · `9.5px/700`
· `8.5px/700`

⚠️ **LARGE surfaces found: ZERO.** Not one text-on-orange element in the product reaches the large-text
threshold — the biggest is the 17px/600 brand mark, which needs 18.66px **at weight 700** to qualify. Vyrobel's
design language uses small, medium-weight labels on orange throughout. **The 3:1 bar therefore applies to
nothing**, and the "zero-cost restore" the directive anticipated does not exist on this product.

## §N.22.3 THE AUDIT TABLE — measured on the RESTORED wireframe orange (validated probe, checked contract)

| element | size/weight | applicable bar | ratio on `#E8703A` | verdict |
|---|---|---|---|---|
| `.vy-plan-tag` "Pro" | 8.5px/700 | SMALL 4.5:1 | **3.35** | ⚠️ BELOW |
| `.vy-pill--accent` "Walk-in" | 12.5px/500 | SMALL 4.5:1 | **3.32** | ⚠️ BELOW |
| `.vy-fd-seg-btn.is-on` | 13.5px/600 | SMALL 4.5:1 | **3.23** | ⚠️ BELOW |
| `.vy-act--accent` "Check in" | 12px/600 | SMALL 4.5:1 | **3.31** | ⚠️ BELOW |

**GROUP A (passes its bar) = 0 surfaces. GROUP B (below) = every one.**
For reference: the current `#D14102` measures **4.70** — it clears the small-text bar; `#E8703A` measures
**3.08** at the light stop.

## §N.22.4 ⚠️ NOTHING WAS RESTORED — and the options are the founder's

The directive's instruction was: restore Group A, surface Group B, apply nothing to Group B. **Group A is
empty, so the correct execution is to restore nothing and surface everything.** The audit ran on a working
state; the tree was reverted to the committed colours.

**THE OPTIONS, quantified:**

| option | what it means | measured result |
|---|---|---|
| **(a)** restore `#E8703A`, accept sub-AA | every CTA + tag at **3.08–3.35** vs a 4.5 bar | the brand colour returns; small text on orange is below AA product-wide |
| **(b)** restore `#E8703A`, change the LABEL colour | white **3.08** ✗ · `--vy-ink` **4.99** ✓ · near-black **5.87** ✓ | works, but CTAs become **dark text on orange** — a large visual change |
| **(c)** restore `#E8703A`, raise labels to LARGE | white on `#E8703A` = **3.08** ≥ 3.0 ✓ | works, but needs every CTA label at **≥18.66px bold** (today 8.5–17px) |
| **(d)** keep `#D14102` | **4.70** ✓ | the status quo: AA holds, the orange is ~1 shade deeper than wireframe |

⚠️ **CONTEXT, AS A FACT NOT A PUSH:** the European Accessibility Act applies to consumer-facing booking
services in the EU from June 2025, and Vyrobel's storefront and guest portal are exactly that. Whether AA is a
compliance requirement or a preference here is a founder/legal call, not a technical one.

⚠️ **NOT DECIDED, NOT APPLIED.** Per the directive, Group B is the founder's ruling. `DO-RESTORE-2` will apply
whichever option is chosen.


## §N.22.5 ✅ DO-RESTORE-1(a) EXECUTED — the CTA wireframe orange is restored, the sub-AA is ACCEPTED

⚠️ **FOUNDER RULING, taken with the measured numbers in hand:** restore the CTA gradient's light stop to the
wireframe **`#E8703A`**, **ACCEPTING white-on-CTA at ~3.1–3.35** against the 4.5:1 small-text bar. Executed
2026-08-14.

**SCOPE — the CTA gradient's light stop ONLY.** `#D14102` → `#E8703A` across **43 occurrences in 29 files**
(the central `--vy-grad`, the 10 shells that predate DO-TOKEN-1 and declare their own, and the invite-email CTA
mirror — §N.8f). Deliberately **NOT** touched:

| left alone | why |
|---|---|
| `--vy-accent` `rgb(194,65,12)` | never moved — it is already the wireframe value |
| the ~42 standalone `#E8703A` accents | never moved — borders, dots, decorative fills |
| `--vy-accent-text` `rgb(147,42,7)` | ⚠️ **a different surface** — link/label body text, not the CTA fill. The ruling was for the CTA orange; this value buys real readability on small text and was not in the ask. **Flagged as deliberately-left; reverting it is a separate ruling.** |
| `.vy-avatar` / `.vy-plan-tag` backgrounds | ⚠️ **also moved by DO-A11Y-1** (from a light peach gradient / `--vy-grad`) and **NOT restored** by this ruling, whose scope is the CTA gradient stop. They now measure 5.05–5.55. **If the founder wants these back on the wireframe orange too, that is a further ruling.** |

⚠️ **THIS IS AN ACCEPTED EXCEPTION, NOT A DEFECT AND NOT A REGRESSION.** A future contrast pass must not
re-flag white-on-CTA as a bug: it is a decided state. Measured after the restore: `.vy-pill--accent` **3.32**,
`.vy-act--accent` **3.31**, `.vy-fd-seg-btn.is-on` **3.23** — all against a 4.5 bar, all accepted.

⚠️ **THE GUARDS WERE RE-POINTED, NOT LOOSENED.** `AccentCtaContrastTest` now asserts the sub-AA **openly** —
the ratio, the band, and the authority (DO-RESTORE-1(a)) — so it passes because it states the **true decided
state**, never because a bar was quietly lowered. It also **protects the decision**: re-darkening the CTA
toward AA REDS it (mutation-proven, including the single-missed-mirror case). The original DO-A11Y-3 rationale
is kept in the file, marked SUPERSEDED rather than deleted, so the next pass does not re-derive the same
argument and "fix" the decision away.

⚠️ **A GUARD TRAP CAUGHT IN PASSING:** the original "white clears AA" test asserted **arithmetic on a
constant**, not the shipped value — so when the source changed it kept **PASSING while certifying a colour the
product no longer used**. Every colour assertion in that file now reads the SOURCE. *A guard that pins a
literal instead of reading the artefact will survive the very change it exists to catch.*

**Recorded as a fact, not a push:** the European Accessibility Act covers consumer-facing EU booking services
from June 2025; the storefront and guest portal are in scope. The decision was taken with that noted.


## §N.22.6 ✅ DO-RESTORE-3 — the avatar / plan-tag wireframe peach restored · ⚠️ THE LOWEST RATIO IN THE PRODUCT

**FOUNDER RULED:** restore `.vy-avatar` and `.vy-plan-tag` **backgrounds** to their wireframe values. Executed
2026-08-14. **5 backgrounds across both shells** (§N.8f — the avatar concept has four definitions in the
operator shell plus one in the owner shell; the owner-copy miss Part B closed is why every copy is checked):

| surface | restored to | note |
|---|---|---|
| operator `.vy-avatar`, `.vy-thread-av`, `.vy-dash .vy-arr-av` | `linear-gradient(140deg, #F0A57A, #D9662E)` | the peach gradient |
| operator `.vy-plan-tag` | `var(--vy-grad)` | rides the CTA gradient restored in DO-RESTORE-2 |
| owner `.vy-avatar` | `var(--vy-grad)` | ⚠️ the two shells use **different** wireframe backgrounds — original, not drift |

### ⚠️⚠️ THE NUMBER, STATED PLAINLY — this is materially worse than the CTA decision

**White on the avatar's light stop `#F0A57A` = 2.03.** Measured `.vy-plan-tag` = **3.35**.

⚠️ **2.03 is the lowest contrast anywhere in the product.** For scale: the AA small-text bar is 4.5, the
large-text bar is 3.0, and the CTA exception the founder accepted in DO-RESTORE-1(a) sits at **3.1–3.35**.
**2.03 does not reach even the large-text bar** — and the avatar initials are 11px/500, so 4.5 is what applies.
This is a **general readability concern, not an AA technicality**: white initials on light peach are hard to
read for everyone, not only for users with low vision.

⚠️ **OPTION (b) REMAINS OPEN AND IS ARGUABLY BETTER — the wireframe peach with DARKER TEXT:**

| text on `#F0A57A` | ratio |
|---|---|
| white (current, restored) | **2.03** ⚠️ |
| `--vy-ink` `rgb(43,35,32)` | **7.60** ✓ |
| `#5C2E0B` (a warm dark brown) | **5.60** ✓ |

**Option (b) keeps the exact wireframe background and fixes the readability**, because the problem is the
white text, not the peach. It was surfaced with these numbers; the founder may take it at any time
(`DO-RESTORE-4`). **Nothing was applied for (b) — this pass did the pure restore that was asked for.**

### The guards

Re-pointed, **not loosened** (DO-RESTORE-2's pattern): they assert the restored backgrounds by **reading the
source**, state the sub-AA **openly** with its authority, and **pin option (b)'s numbers** so the alternative
stays in the record rather than needing re-derivation. Mutation-proven three ways — re-darkening the operator
avatar, missing the owner copy, or re-darkening the plan-tag each RED the guard.

⚠️ The §N.8f invariant that caught the original owner-copy miss is **unchanged in force, only in value**: the
two shells must agree. It caught a miss in one direction; it now catches one in the other.


# (N.23) 🏁 DO-PROBE-3 — TWO PROBE GAPS CLOSED, ONE BLOCKED (2026-08-14)

Three named scope gaps, each attacked independently and each trusted only where a **known-answer fixture**
proves it. **The instrument's honest scope is now narrower in its unknowns and wider in what it measures.**

## §N.23.1 ✅ GAP A — ancestor opacity, CLOSED (measured, not flagged)

CSS `opacity` composites the whole subtree, so the effective alpha is the **product down the ancestor chain**
and the painted colour is the ordinary source-over composite `alpha*text + (1-alpha)*plate`. The collector now
computes that alpha; the sampler composites and measures **what is painted** rather than what is declared.

| fixture case | declared says | painted (hand-computed) | sampler |
|---|---|---|---|
| black on white, no dimming | 21.00 | 21.00 | **21.00** ✓ |
| ancestor `opacity:.55` | 21.00 | **4.74** | **4.74** ✓ |
| nested `.55 × .50 = .275` | 21.00 | **1.96** | **1.96** ✓ |
| `opacity:0` | — | invisible | **UNMEASURABLE** (`invisible-zero-opacity`) ✓ |

⚠️ **§N.18.1 — VALIDATED AGAINST RAW PIXELS, NOT THE TOOL'S OWN ARITHMETIC.** The painted glyphs read straight
out of the PNG are **exactly** `0`, `115`, `185` — precisely what the alpha model predicts. *The model
describes what the browser actually paints; the ratio is then just arithmetic on top of it.*

⚠️ **The real-world witness** is DO-A11Y-1's locked nav item: it DECLARED a colour measuring **9.40** while
PAINTING at **2.89**. Measuring the declared value there was the worst false clean bill this tool has produced.

⚠️ **Zero opacity refuses rather than scoring ~1.0** — compositing at alpha 0 returns the plate itself, which
would read as a catastrophic contrast failure instead of "nothing is painted here".

## §N.23.2 ✅ GAP B — dense text, PARTIALLY closed, residue named

| sub-class | status | evidence |
|---|---|---|
| dense text on a **SOLID** background | ✅ **NOW MEASURABLE** | the computed background **IS** the plate — no column hunting, no anti-alias fringe, and it works when text fills the box edge-to-edge. Fixture hits the hand-computed **4.54** exactly |
| dense text on a **gradient** with line-leading | ✅ measurable | the existing clear-column path recovers it (4.59) |
| dense text on a **gradient, edge-to-edge** | ⚠️ **STAYS UNMEASURABLE** | there is genuinely no background pixel to read; any figure would be invented. Still reports `no-clear-band` |

⚠️ **A FIXTURE THAT FAILED TO BUILD ITS OWN CASE, reported rather than papered over.** The new `#edge` fixture
was *intended* to be the residue case and produced a number instead — its text did not truly fill the box, so
clear columns existed. The residue is therefore still carried by the **pre-existing** committed
`dense-noband` fixture, which was re-verified to refuse. *A fixture that does not construct the case it claims
certifies nothing.*

## §N.23.3 ⛔ GAP C — dompdf PDFs, BLOCKED ON TOOLING

The 5 `pdfs/*` are server-rendered; a browser probe cannot reach them, and **no rasterizer exists in this
environment**: `pdftoppm` ✗ · `ghostscript` (`gs`) ✗ · `mutool` ✗ · `qpdf` ✗ · ImageMagick `convert` ✗ ·
php-`imagick` ✗. **Not faked.** Unblocking needs ONE of: `brew install poppler` (pdftoppm) or
`brew install ghostscript`, then a small non-browser sampler that rasterizes a page and samples the same way.

⚠️ Note a second obstacle beyond the rasterizer: a PDF has **no DOM**, so there are no element rects to sample
against. The PDF path needs its own candidate model (declared template colours, or region annotations) — it is
a different instrument for a different medium, not a reuse of this one.

## §N.23.4 🧭 THE INSTRUMENT'S HONEST SCOPE, UPDATED

**NOW MEASURES:** flat plates · page/card gradients · gradient BUTTONS on their own plate · dense small text
via clear columns · **text under dimmed ancestors, at its true painted colour** · **dense text on any solid
background, with no clear column required**.

**STILL CANNOT MEASURE:** dense text filling a **gradient** element edge-to-edge (no plate pixel exists) ·
text over a **photo** backdrop · **dompdf PDFs** (blocked on a rasterizer, and needing their own candidate
model). Each reports a REASON, never a number.

⚠️ **Eight corrections now**: §N.20.1's four, Part B's two capture faults, and these two. **Every one was
caught by a fixture with a known answer — none by the numbers looking wrong.**


# (N.24) 🏁 DO-STATE-1 — AXIS 1 SWEPT · THE TAXONOMY IS COMPLETE (2026-08-14)

**§N.16.2's three-axis taxonomy is now swept in PRACTICE, not just described.** Each axis hides a surface a
different way, and each needed its own forcing move:

| axis | how a surface hides | the forcing move | swept |
|---|---|---|---|
| 1 **STYLE/STATE** (G10) | closed modal · inactive tab · collapsed accordion · hover/focus reveal | **force the state VISIBLE** | ✅ **this pass** |
| 2 **AUTHORIZATION** (§N.14.1) | gated markup absent for the role | render **as the role** | ✅ Part A |
| 3 **DATA** (§N.16.2) | markup that needs a populated list / performed search / record state | force the **data condition** | ✅ Part B + throughput |

## §N.24.1 ⚠️ THE FINDING — A MODAL CARD IS NOT A PAGE CARD

`--vy-glass` is `rgba(255,252,248,.58)`, tuned for a card sitting on the **light page**. Inside a modal the
identical `.vy-card` sits on a **dark scrim** (`rgba(43,35,32,.4)`) which composites straight through it:

| | plate | `--vy-faint` | `--vy-muted` |
|---|---|---|---|
| card on a **page** | rgb(249,223,207) | 4.55 ✓ | 5.5 ✓ |
| the same card in a **modal** | **rgb(215,199,189)** | **2.79** ⚠️ | **3.38** ⚠️ |

**Every muted or faint label in every modal in the product was measured against that plate** — and no earlier
pass could see it, because a closed modal renders nothing. Fixed by naming the surface once
(`.vy-modal-card`, `rgba(255,252,248,.94)`) and marking all **11 modal cards across 4 files** (§N.8f). Page
cards untouched — they already pass, and darkening them would violate posture A. The backdrop blur survives.
**Re-measured: 3 → 1 failure, the survivor being the DECIDED CTA.**

## §N.24.2 ⚠️ THREE METHOD TRAPS, ALL CAUGHT BY RE-MEASURING

⚠️ **The fix failed to land TWICE before it worked**, and only the re-measure revealed it (§N.21.6):

1. **FORCE-AND-COLLECT IN ONE SYNCHRONOUS BLOCK MEASURES THE PRE-STATE DOM.** Setting the Alpine flag and
   walking the DOM in the same evaluate collected candidates *before* Alpine applied `x-show` — so the
   hit-test saw an uncovered page while the screenshot captured the scrimmed one. **101 fabricated failures.**
   The DOM-changed-between-collect-and-capture fault, arriving from the opposite direction.
   → **FORCE the state, AWAIT two animation frames, settle, THEN collect** — in separate calls.
2. ⚠️ **EQUAL SPECIFICITY MEANS ORDER DECIDES.** `.vy-card` and `.vy-modal-card` are both single-class
   selectors. Defined *before* `.vy-card`, the new rule silently lost and the page did not move — twice, since
   the first correction inserted it after an *earlier* `.vy-card` occurrence rather than the last.
   *A cascade fix is not landed until the computed style says so.* The guard now asserts the ORDER.
3. **A GUARD THAT FAILED FOR THE WRONG REASON.** Pest's `toContain()` takes **multiple needles**, so a
   "failure message" passed as a second argument is silently searched for as text. The guard reddened on a
   string that was never meant to be in the file.

## §N.24.3 🧭 STATE COVERAGE — what was forced, what remains

**FORCED + MEASURED:** the seed's closed modal (`msgOpen`) · the inline bulk-select bar (`selectMode`) — the
43 parked ancestor-dimming fires were `opacity:0` panels of exactly this kind.

**LOGGED-UNSWEPT → `DO-STATE-2`:** the other 10 scrim modals (now carrying the fix, but not individually
force-measured) · inactive **tab panels** (front-desk Departures/In-house) · `aria-expanded` accordions (8
files) · `:focus-within` hover reveals (9 files) · toast/flash states · loading/skeleton states · disabled
states. ⚠️ `x-show` appears in **48 files** — this pass forced two states deeply, not forty-eight shallowly.


# (N.25) 🏁 DO-RESTORE-4 — THE COLOUR A RULING NAMES CAN BE THE WRONG ONE (2026-08-15)

Option (b) — keep the wireframe peach, darken the text — executed on the peach copies, **blocked on the
gradient copies**, and with the ruling's own colour corrected by measurement.

## §N.25.1 ⚠️ A GRADIENT HAS TWO WORST CASES, AND THEY ARE AT OPPOSITE ENDS

`#5C2E0B` was offered at **5.60** — computed against the peach's **light stop**, which is the worst case for
**white** text. For **dark** text the worst case is the **other end**. Measured on the plate the initials
actually sit on:

| surface | painted fill | white | `#5C2E0B` | `--vy-ink` |
|---|---|---|---|---|
| peach avatars | rgb(242,194,170)..rgb(221,112,58) | 1.61 / 3.25 | 7.06 / **3.49** ✗ | 9.58 / **4.73** ✓ |
| `--vy-grad` copies | rgb(238,190,170)..rgb(203,76,22) | 1.67 / 4.58 | 6.80 / **2.48** ✗ | 9.22 / **3.36** ✗ |

**Applying the named colour would have moved the failure rather than removed it.** `--vy-ink` (the ruling's own
stated alternative) is what clears the peach, so `--vy-on-peach` resolves to it — named, not inlined, because
the avatar concept has six copies (§N.8f).

## §N.25.2 ⛔ THREE COPIES CANNOT BE FIXED BY TEXT COLOUR AT ALL

`.vy-plan-tag`, the owner `.vy-avatar` and `.vy-owner-avatar` ride `--vy-grad`, which spans light peach to deep
orange. **White fails at one end, dark text at the other — no single colour clears both.** They keep their
decided state; the guard asserts that NON-CHANGE, so a future pass cannot "fix" them into the opposite
failure. ⚠️ **The only real fix is flattening that background — a founder call on the LOOK, logged as
`DO-RESTORE-5`.**

## §N.25.3 🧭 THE LESSON

> **A number in a ruling carries the assumptions of whoever measured it.** The founder chose (b) on a figure
> that was correct for the stop it was taken from and wrong for the surface it was applied to. Re-deriving it
> against the real painted plate — before applying — is what caught it. *Execute the intent, not just the
> literal value; and when they diverge, say so rather than silently doing either one.*


# (N.26) 🏁 DO-RESTORE-5 — FLATTEN THE PLATE WHEN NO TEXT COLOUR CAN WIN (2026-08-15)

## §N.26.1 ⚠️ A GRADIENT CAN BE UNFIXABLE BY TEXT COLOUR — AND THAT IS A BACKGROUND DECISION

The avatar family rode two gradients. **A gradient has two worst cases at opposite ends:** white is worst at
the light stop, dark text at the dark stop. So:

| surface | white | dark ink | fixable by text? |
|---|---|---|---|
| peach gradient | 1.61 / 3.25 | 9.58 / **4.73** | thinly — ink passes at 4.73, 4.32 at the declared stop |
| `--vy-grad` | 1.67 / **4.58** | 9.22 / **3.36** | ⚠️ **NO — neither direction clears both ends** |

**When no text colour can clear both ends, the text is not the variable — the background is.** Flattening gives
one plate value and no end to hug: `--vy-avatar-bg: #F0A57A` (the wireframe peach's own light stop, so the
avatars stay on-brand) carries dark ink at **7.60**, measured **7.60** on the page.

## §N.26.2 🧭 THE SCOPE GUARD THAT MATTERED MOST

The avatars rode the **same token as every CTA**. Flattening `--vy-grad` itself would have silently destroyed
the founder's CTA restore (DO-RESTORE-1a). Only the avatar surfaces stopped *using* it; the token and its
**36 CTA uses** are untouched, verified on a live page, **and a guard reds if anyone flattens the shared
token** — the scope guard is now structural, not a note.

> **A shared token is a shared decision.** Changing what one surface *uses* is local; changing the token is
> global. The register's history is full of the second being done by accident — this pass made the difference
> explicit and testable.

## §N.26.3 THE FAMILY, CLOSED

Five avatar backgrounds across both shells → `--vy-avatar-bg`, dark ink. `.vy-plan-tag` → solid `--vy-accent`
+ white (5.18) so "Pro" still reads as a badge (option b; the flat-peach alternative measured higher but read
as a muted chip). **`.vy-owner-avatar` reclassified DECIDED → PASSING.** The two-gradient split that made the
family unfixable is gone: one plate, one text colour, one guard.


## §N.26.4 ⚠️ §N.8f APPLIES TO GUARDS, NOT JUST TO TOKENS

DO-RESTORE-3's decision was written into **three** test files. Two were re-pointed in this pass; the third
(`SharedClassContrastTest`) was missed and **only the full suite caught it** — it still asserted the avatars
must ride the gradient, i.e. it would have demanded the defect back.

> **A guard is a COPY of a decision.** Copies drift exactly the way tokens do, and the same rule applies:
> when a decision changes, find every copy. A per-file run would not have surfaced it; the full suite did.

Also fixed here, and overdue: **the joiner/classifier lived in the gitignored `.playwright-mcp/`**, so five
passes' worth of corrections and the founder-decision classification existed in one working tree only. It now
sits beside the other two probe pieces at **`tools/contrast-report.php`** — the exact failure
`contrast-collect.js` already names in its own header, committed at last.

⚠️ And the reclassification has teeth in one direction that matters: while the avatar family sat in `$decided`,
a **regression** on any of it would have been filed as "accepted exception" and never read as a defect.

---

# (N.27) 🏁 THE BACKEND AUDIT + THE FEATURE WAVES — **THE PIVOT FROM REPAIR TO SHIPPING** (SAVE v17, 2026-08-15)

The ten passes from `a743bcd` to `b28122d`: a read-only audit of the whole backend, the three "wrong, not
absent" corrections it found, and then two waves of feature work run off its ledger. **The chapter's headline
is the audit's own verdict — there is NO ROT**, so everything after it is additive.

## §N.27.1 ⚠️ **A REGISTER IS A RECORD OF WHAT ONE READER FOUND — SWEEP, ESPECIALLY FOR SECURITY**

The audit ledger (`docs/BACKEND-REMAINING-WORK.md`) is the best map of the backend that exists, and it
**under- or mis-described the scope of four items out of ten**:

| item | the ledger said | the sweep found |
|---|---|---|
| `DO13-4` | 4 set-password paths | ⚠️ **9** — self-signup, platform-admin, and three guest paths were unnamed |
| `D3-4` | 1 consumer of the LY figure | **3** — the occupancy guard, the KPI deltas, and the pace row |
| `D1-3` | the column is "populated" | **0 of 41** rows on demo (§N.27.7) |
| `D2-1` | the column is nullable | `int unsigned NOT NULL DEFAULT 2` — the real edge is a storable `0` |

⚠️ **On a SECURITY item the gap between 4 and 9 is the whole item.** Fixing four password paths and shipping
would have left five screens setting weak passwords under a commit message announcing the opposite — worse than
not doing it, because the follow-up would never have been written.

⚠️ **AND A CONTENT-SWEEP IS BLIND TO THE ABSENCE IT SEEKS.** Grepping for `Password::` finds the paths that
already say something about passwords; a screen with **no rule at all** matches nothing and is invisible to
exactly the search meant to find it. **Sweep the ROUTE, not the text** — enumerate every endpoint that can set
a credential, then check each. That is how the silent screens surfaced.

## §N.27.2 ⚠️ **READ THE LIVE SCHEMA — IT NAMES THE REAL EDGE, AND THE REAL EDGE IS RARELY THE OBVIOUS ONE**

Three passes had their premise corrected by one `SHOW CREATE TABLE`:

- **`D34-1`** — `schedule_publications.published_at` is **NOT NULL**, so the grid's `?->published_at !== null`
  null-check can never be false (logged `D34-1b`).
- **`D2-1`** — `NOT NULL DEFAULT 2` means NULL is *impossible*; the defensive null-guard everyone reaches for
  would have guarded nothing while a storable **`0`** printed **"Sleeps 0"** — *unrecorded* rendered as
  *sleeps nobody*. Mapping `0 → null` at the read makes it omit.
- **`D29-5`** — `notifications` and `owner_attention_states` **both** carry `read_at` and `dismissed_at`.

⚠️ **IDENTICALLY-NAMED FIELDS ON DIFFERENT ENTITIES ARE MAXIMALLY CONFUSABLE, AND THE NAME CANNOT DISTINGUISH
THEM.** A notification-dismiss and an attention-dismiss are different acts on different tables; nothing in the
column names says so. **Assert the boundary** — `NotificationDismissTest` proves the attention store still holds
0 rows after a notification is dismissed. A comment saying "these are different" is not a defence (§N.16.4).

## §N.27.3 ⚠️ **"READ THE MODELS THE SAME WAY" MEANS THE SAME *DATA*, NOT THE SAME *FORMATTING***

Two passes hit the same shape from opposite sides:

- **`D34-1`** — the grid renders publication as `diffForHumans()` ("2 days ago"). **True on screen, false on
  paper**: a PDF is read weeks later, and a relative timestamp printed on it silently becomes a lie. The PDF
  takes the same DATA from the same builder and renders an absolute date.
- **`D1-3`** — `expected_arrival` is **free text** ("late evening", "after 6"). Coercing it to a clock value is
  fine for *sorting*, and a lie as *displayed text*. It renders exactly as typed, never defaulted.

**Share the source; let each surface format for its own medium and audience.**

## §N.27.4 ⚠️ **CONVERGE ON ONE MECHANISM (§N.8f) — BUT A LEGITIMATELY *STRONGER* PATH STAYS SEPARATE**

`DO13-4` bound the rule and its user-facing sentence together in one `PasswordPolicy` so a screen cannot state
one rule and enforce another. But `CreatePlatformAdminCommand` requires **min(12) + mixedCase** — stronger, and
deliberately so. **It was NOT collapsed into the shared default**, and a test pins it.

⚠️ **DE-DUPLICATION IS NOT FLATTEN-TO-WEAKEST.** The same judgement recurred in the payout badge (`4c18534`:
three definitions across two *disjoint* tone vocabularies — converged on the two that share a vocabulary, left
the third) and in the operator-vs-owner tone classes. **Same concept ≠ always one definition; confirm before
collapsing.**

## §N.27.5 ⚠️ **A GUARD THAT IS TOO LOOSE — OR TOO BROAD — CANNOT CATCH WHAT IT EXISTS TO CATCH**

Both directions, repeatedly:

- **Too loose:** `D10-2`'s original return shape was annotated permissively enough that the wrong-shaped value
  type-checked. Tighten to the shape the code actually produces.
- **Too broad:** banning the literal `tone-vip`, `#B4400F`, `in_app`, or `roomNightsSold > 0` fails on *correct*
  code elsewhere in the file. **Scope the assertion to the real target.**
- ⚠️ **AND A SOURCE-SCANNING GUARD READS ITS OWN NEEDLE.** Banning `Password::min(`, `attention`, and
  `PerformanceMetrics::` each failed on the guard's *own comment or docblock* — **three times in one session**,
  the last on a docblock that legitimately *names* the method it explains the code does not call. **Tokenise
  (`token_get_all`, skipping `T_COMMENT`/`T_DOC_COMMENT`): a source guard must read what RUNS, not what is
  written about it.** (§N.4m is the same lesson from the other side.)

## §N.27.6 ⚠️ **A MUTATION OR FIXTURE PROVES NOTHING UNTIL YOU CONFIRM IT *APPLIED* / *CONSTRUCTED ITS CASE***

§N.4p's seven forms all recurred, plus new ones:

- **The edit landed on the wrong call site** — `D3-4`'s coverage argument went to the *owner* `assemble()`, not
  the *operator* one. Green, and defending nothing. Caught by probing real months, not by reading.
- **The fixture never built its case** — `D1-3`'s `book()` helper hardcodes the reservation header to
  2026-09-01, so date overrides reach only the line; `D29-5`'s `notifiable_type` needed the **morph alias**
  (`'user'`), because an FQCN row is invisible to `$user->notifications()`.
- **A residue check measured the environment, not the change** — a global "0 message threads" assertion is
  vacuous under `RefreshDatabase` and passes for the wrong reason (§N.17.7: it also passed alone and failed
  in-suite).

## §N.27.7 ⚠️ **A QUERY THAT COMPILES AND RUNS CAN BE SILENTLY, TOTALLY WRONG — PROBE THE VALUES**

`D3-4`'s coverage count was written `->distinct()->count('date')` — valid Eloquent, runs clean, and returned
**0 for every period**. Coverage of 0% would have suppressed the last-year figure *everywhere*, which looks
exactly like a working guard. The fix (`count(DB::raw('DISTINCT \`date\`'))`) came from **printing the numbers
for real months**, not from re-reading the code. **At the boundary between an ORM and SQL, read the output.**

⚠️ **AND A PRE-EXISTING TEST CAN ENCODE THE BUG.** `OperatorAnalyticsTest`'s vs-LY fixture seeded only 10 of 30
days — the exact thin-coverage case the new guard suppresses. **The fixture was fixed, never the rule.** A test
that fails when a defect is corrected is evidence about the *test*.

⚠️ **AND "POPULATED ON DEMO" ≠ "POPULATED IN PRODUCTION."** `expected_arrival` is written at online check-in —
an action the demo never performs — so the column was empty on all 41 rows while being entirely real in
production. **The populated state was the unrendered branch; force it.**

## §N.27.8 ⚠️ **A DEVICE-GATED LIMITATION CAN BE LIFTABLE — LOOK FOR A VIRTUAL INSTRUMENT FIRST**

`D24-2` looked like it ended in a caveat: passkey *registration* is testable, but the *login ceremony* needs
real hardware. It doesn't. **A CDP virtual authenticator proved operator passkey sign-in end-to-end** —
usernameless, resident-key, real crypto (`localhost`, not `127.0.0.1`, for the RP ID).

**Before writing "cannot be verified without a device", check whether the platform ships a virtual one.** The
honesty rule (never claim an unproven ceremony works) is satisfied far better by *proving it* than by flagging
it. Registration also caught **`D24-2a`**: the controller hardcoded `owner.passkey_registered`, so an operator's
key logged the **wrong realm into the audit trail** — fixed in the same pass.

## §N.27.9 ⚠️ **ADDING AN INPUT CAN WEAPONIZE A DORMANT DISPLAY**

`DO9-1` added a `notes` textarea to the owner's Add-Unit wizard. The column, its validation and its **read-back
on the operator's screen already existed** — the field was simply unreachable. So the write was not the risk:
**if that dormant read-back had used `{!! !!}`, shipping the input would have opened owner→operator stored
XSS** in one line, in a pass that looks like adding a form field.

**Before shipping a write, verify how the value is READ.** It escaped (`{{ }}`), so the round-trip completed
safely — but that was a fact to *check*, not to assume.

## §N.27.10 ⚠️ **A LOGGED "STOPPED FOR A GOOD REASON" IS A GIFT — SATISFY THE OBJECTION, AND GUARD THE TRAP**

`D10-2` had been attempted before and correctly abandoned: feeding a raw `reservations.status` to
`ReservationStage::meta()` renders **"Unknown"**. The note explaining why is worth more than the attempt. This
pass **satisfied the objection** (resolve the stage in the component) rather than working around it, and
**mutation M2 reproduces the exact "Unknown" bug** so the trap is now guarded rather than remembered.

⚠️ **A DOCUMENTED TRAP IS NOT A SOLVED TRAP.** §O9's Alpine-root trap (`document.querySelector('[x-data]')`
matches the *shell's* root, not the component's) was recorded — and walked into again in `DO9-1`.

## §N.27.11 ⚠️ **CONFIRM-FIRST CUTS BOTH WAYS — SOMETIMES IT SHRINKS THE WORK**

The discipline is "the ledger under-describes", but the corrections ran in both directions, and **three items
got smaller** once checked: `D24-2`'s sign-in needed **no wiring at all** (shared `web` guard +
`RedirectsByRole`), `DO9-1`'s round-trip already existed *and* escaped, and `D3-1`'s last-year comparison was
**already a span shift** (`->subYear()` on both ends), so an arbitrary window was correct with zero changes.

**Verify either way. Do not assume the audit is always wrong** — assume only that it is *one reader's finding*.

## §N.27.12 ⚠️ **UI EXISTS ≠ FEATURE WORKS — AND FEATURE-COMPLETENESS ≠ LAUNCH-READINESS**

The audit's own headline, and the frame for everything that follows it:

- **NO ROT.** Every route resolves; there are zero stubbed actions and zero dead controls. Exactly **three**
  items were *wrong* rather than *absent*, and all three are fixed (`DO-CORRECTNESS-1`).
- ⚠️ **NOTHING ON THE FEATURE BACKLOG BLOCKS LAUNCH.** The launch blockers are **infrastructure**: no deploy
  artifacts at all, the **one crontab line** (without which 8 scheduled commands — including GDPR 14-day
  deletion and the 30-day ID purge — silently never run), and the founder's Stripe/VAT steps.

**These are different axes.** Shipping features does not move launch closer; the deployment sprint does.

### §N.28 — RATES MATERIALIZE AT AUTHORING TIME THROUGH THE CERTIFIED WRITER. THEY NEVER RESOLVE AT READ TIME.

⚠️ **This is a money rule, and it was learned by nearly shipping the opposite.**

The weekend uplift does not exist as a resolution step. `RateAndRestrictionService::setRateWeekendAware()`
**writes** the higher Fri/Sat price into `rate_calendar` at authoring time; there is no read-time weekend
layer. So the runtime precedence is only two levels:

    unit_rate_overrides (plan, unit, date)   → wins
    rate_calendar       (plan, date)         → everything else, already containing whatever was authored

⚠️ **THEREFORE ANY NEW RATE DIMENSION MUST MATERIALIZE THROUGH THE CERTIFIED WRITER**, never become a
read-time input to `NightlyRate`. Two facts make the alternative unworkable, and both were confirmed by
reading the code rather than assumed:

1. **It would be INERT for most bookings.** `NightlyRate::stayTotalForUnit()` returns NULL for a unit with no
   override, so the default storefront charge is `BookingEngineService::priceOver()` → `rate_calendar`. A
   read-time dimension would be consulted almost never — and in the one case it *is* consulted, the per-unit
   override wins anyway.
2. **It would create a SECOND PRICE SOURCE.** **22 files read `rate_calendar` directly** against **7** that
   use the resolver — including the storefront landing "from" price and the owner Rates page. A read-time
   dimension would show one price on the landing page and charge another (D4-1).

This is why the read-time seasonal design was **rejected and stopped before any code**, and the materialized
one shipped (`6114dcb`): written into `rate_calendar`, a season is visible to every reader at once, the
storefront and folio need no change, and the per-unit override still wins with no new logic.

⚠️ **The cost of materializing is that applying OVERWRITES real prices for the range** — which is what setting
a seasonal rate means. So it is a money mutation: `rates.manage`-gated, with a computed blast-radius preview,
and season-over-weekend is **authoring order (last write wins)**, stated legibly rather than left to surprise.

---

# (N.29) 🏁 A GUARD'S GREEN MUST DEPEND ON THE PROTECTION *RUNNING* (2026-08-20)

The guest-notifications feature (three gates, `aa1a91e` → `d866837` → `f623bb8`) produced **ten weak guards
and one weak mutation**. Every one was caught by a mutation or by the full suite. **None was caught by
review** — and that is the whole lesson, because review reads what a test *says* it checks. Only a mutation
shows whether the green *depends* on the protection.

> **A guard is only worth its green if removing the protection turns it red. Assert the protection RUNNING —
> never where it lives, never what it is named.**

## §N.29.1 ⚠️ THE THREE FORMS — establish WHICH before you touch anything

**(a) LABEL — it asserts the name of the thing, not the thing.**
The class is present in the markup, but nothing proves it *hides* (`.vy-sr-only` matched inside
`class="vy-sr-only"`, so renaming the CSS rule away changed nothing it read). The middleware is *declared*,
but nothing proves it cannot be *stripped* (`gatherMiddleware()` still lists `auth:guest` after
`->withoutMiddleware('auth:guest')`). The filter *names* a class while the column stores a **morph alias**
(`notifiable_type = Guest::class` under `enforceMorphMap` matches **zero rows — before and after**, so the
erasure test asserted `0 === 0` forever). ⚠️ **A zero-matching filter is unfailable. Show the instrument
capable of a POSITIVE first, or its later zero is evidence about the instrument, not the system** (§N.20).

**(b) LOCATION — it asserts where the thing lives.**
"the gradient is in `bell.blade.php`" · "no `GuestNotificationsController.php` exists" · "exactly one route
LINE". Each was true when written and each failed on a **safe refactor** — extracting a sub-component, adding
a read-only controller, registering a second route for a different guard. ⚠️ **The dangerous half is the
mirror: a location-guard also goes SILENT when the behaviour moves somewhere it is not watching.** It fails
loudly on safe change and quietly on real change — exactly backwards.

**(c) MISSED-MUTATION — the mutation never reached the path.**
A green under mutation means the guard is weak **or** the mutation missed, and the two are indistinguishable
until you check. `actingAs($g, 'guest')` **rebinds the default guard**, so swapping a guard-resolver for the
default one stayed green — the harness had pre-arranged the answer. An order-dependent wrong-actor
(`orderByDesc('created_at')->first()`) resolved the *right* guest by fixture luck; a deterministic one
(`whereKeyNot($authedId)`) reddened **four** tests. ⚠️ **One of the eleven was a weak MUTATION, not a weak
guard** — the guard was fine. Diagnose which; do not "fix" a healthy guard.

## §N.29.2 THE RULE

**Guard the RENDERED behaviour, or a ROUTE-TABLE fact — never a file path.** A render follows the definition
wherever it moves next; a route-table assertion states the mechanism ("every notification write route points
at the shared controller") rather than a proxy for it ("this filename must not exist"). When a guard breaks
on a refactor, the fix is **re-calibration to the protection, never relaxation** — and the re-calibrated
guard should end up **stronger**: after the bell guards moved to the render, breaking the gradient *inside the
extracted file* reds, which the old guard could not see at all.

**Mutation-prove every guard, and verify every mutation twice:** that it APPLIED (assert the substitution
matched exactly once — a `git checkout` that silently reverts to HEAD, or a needle matching a comment as well
as the code, both produce a meaningless run), and that it HITS THE PATH the test exercises, deterministically.

## §N.29.3 THE ELEVEN

Label: the `.vy-sr-only` markup match · the `gatherMiddleware()` declaration read · the `Guest::class`
morph-alias filter · Pest's `toContain($needle, $message)` silently asserting the *message* as a second
needle. Location: the badge-gradient file read · the glyph file read · the `file_exists(...)` fork proxy · the
one-route-LINE count. Missed-mutation: `actingAs` rebinding the default guard · the order-dependent
isolation fixture. And one weak MUTATION: the order-dependent wrong-actor that resolved correctly by luck.

⚠️ **Ten of the eleven were written in the same feature that then caught them.** Writing a guard and trusting
it are one motion; only the mutation separates them.

---

# (N.30) 🏁 TWO NAMES THE CANON HAD WRONG — carry these, not the phantoms (2026-08-20)

Both are small, both were load-bearing in a probe, and both produced a **zero that could not fail**.

## §N.30.1 ⚠️ THE PAYMENTS COLUMNS ARE `gateway` AND `gateway_ref` — there is no `provider_ref`, no `method`

`STRIPE-CAPTURE-CONFIRM-2026-08-20.md` §3 reported the 16 seeded payments as *"`method = (null)`, zero Stripe
intent ids"*, measured with `!empty($r->provider_ref ?? null)`.

⚠️ **Neither column exists.** The `?? null` fallback meant the probe asked about a property that was not
there and returned **0 for every row, unfailably** — the §N.29 LABEL form, committed one pass after §N.29 was
written, by its author.

Re-measured on the real columns: **16 payments · `gateway = 'manual'` on all · all 16 carry a non-null
`gateway_ref` · 0 real Stripe intents (`pi_`/`cs_`)**. The verdict was unchanged and never rested on the bad
probe — `DemoSeeder:1567`'s `recordPayment(…, 'tok_demo_card')` and `gateway='manual'` are independent
evidence — but the supporting figure did.

> **A probe that names a field which does not exist does not error in PHP — it returns a falsy zero. Any
> `?? null` on an unverified column name is an unfailable question. Confirm the column exists before you
> trust its emptiness.**

⚠️ The committed audit still carries the phantom in its §3; it is flagged there for correction rather than
silently rewritten, so the audit trail shows the mistake and the fix.

## §N.30.2 ⚠️ "0 OVERRIDES" MEANS `unit_rate_overrides`

The money-as-seeded shorthand **929 / 10,912,000 / 0 / 41 / 16 / 0** names its third figure "0 overrides".
That is **`unit_rate_overrides`**. ⚠️ There is **no `rate_calendar.override_reason` column** — reading the
shorthand literally throws `SQLSTATE[42S22] Unknown column`.

The full reading: `rate_calendar` rows · `SUM(rate_calendar.price_minor)` · `unit_rate_overrides` ·
`reservations` · `payments` · `rate_seasons`.

