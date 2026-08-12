# Vyrobel — Decision Log

A running record of locked decisions — what was decided and why — so the project stays honest across gated phases. Append new decisions with a date as we lock them.

## 2026-08-09 — The 8th non-green CLOSED: a contrast guard tightened to its intent, not relaxed (GATE A)

**The decision: fix the TEST, not the design value — and prove the tightened guard still fires.**

`DesignTokenContrastTest` forbade the literal `rgb(138, 128, 121)` anywhere in a shell, to stop the pre-AA
`--vy-muted` grey creeping back. The design-parity pass's P8 gate (`3bf9c02`) added
**`--vy-hk-ooo: rgb(138, 128, 121)`** — the "Out of order" housekeeping **status-dot** token — which is the same
triple by coincidence of palette, and the suite went 7 → 8 non-green.

**Verified before deciding** (the finding, not the label): the token is consumed in exactly one place,
`housekeeping-board.blade.php:110`, as the **`background` of a 9px `.vy-hk-dot`** — the rule sets only
width/height/border-radius, no `color` — and every dot is printed beside its status **word**. So **1.4.1 holds
and the 4.5:1 text bar does not apply to a decorative fill.** The failure was a real false positive.

**Two honest options existed.** **(b) re-pick `--vy-hk-ooo`** was **rejected**: it changes a *correct design
value* to satisfy an *over-broad test*, which is backwards, and a warm neutral is the right colour for an
out-of-order dot. **(a) tighten the assertion to what it actually guards** was taken: each failing triple is now
forbidden **as a `--vy-muted` / `--vy-faint` token value** and **as a text `color:`**, with a `(?<![-\w])`
lookbehind so `background-color:` / `border-color:` / `outline-color:` do not false-positive — the same trap the
P14 accent sweep recorded. **Nothing was baselined, suppressed or deleted.**

**The guard was proven STILL LIVE by four observed mutations**, two of which exist only to show it goes RED:
`--vy-muted` reverted → **FAILED (79)** · `--vy-faint` reverted → **FAILED (80)** · a pre-AA grey hardcoded as
text `color:` → **FAILED (102)** · the same greys as `background-color:`+`border-color:` → **PASSED**. The result
is **strictly stronger** than the original: it still catches both token regressions, it now *also* catches a
hardcoded pre-AA text grey, and it no longer fires on decoration.

⚠️ **The lesson worth more than the fix: it survived SEVEN gates (P9→P15) because the baseline count was
QUOTED FROM NOTES rather than MEASURED.** An asserted baseline goes stale exactly like an asserted finding.
Recorded as **L.31** (`PROJECT-STATE.md` §N.8) and in §J/§J.bis.

Baseline restored to **7** (6 `BookingImportTest` + 1 `OwnerUnitDetailViewTest`); PHPStan L5 (0); Pint clean.

## 2026-08-09 — ⬅ A PER-PAGE DESIGN-PARITY PASS IS THE ACTIVE TRACK (started; IN PROGRESS)

Recorded here because the two a11y token gates below (`93247e7`, `81c5d18`) were **produced by this pass** and
otherwise read as free-standing, and because a fresh session reading this log needs to know a track is open.

**The decision.** After the wireframe-parity sweep closed, run a **finer-grained, per-page design-parity pass**
over the **125 standalone prototypes in `docs/design/raw/`** — **ONE page per turn**, prototype and live page
side-by-side in a real browser — with **two strictly separated outputs**: (1) **fix the design NOW**, markup and
styling only; (2) **LOG the implied backend, never build it**, to **`docs/design/MISSING-BACKEND-BY-PAGE.md`**,
which will drive a **separate later backend phase**.

**Why the separation is the whole point.** Fixing design and building backend in the same turn is how a
presentation pass quietly becomes a feature pass and starts touching money, guards and engines. Splitting them
keeps each turn reviewable, keeps the certified services untouched, and turns "what this page implies it can do"
into a written backlog instead of an improvisation.

**What is deliberately NOT done.** No service, guard, engine or computation is changed to close a design gap.
The prototype is the **DESIGN source of truth, never the DATA source of truth** — every §5 refusal and every one
of the 40 ratified divergences is preserved (#39). ⚠️ **The recurring judgement call: where the "gap" is the
live page honestly refusing the prototype's invented data, the gap IS the feature.**

**State at this entry.** **Operator 23 of 42 prototypes covered** (Pages 1–15, `0aa9ede`→`ee0121a`), **19
remaining**; the owner (32) and guest (32) sets untouched. **⬅ Next page: Operator Payout Run.** Full method,
per-gate commit table, the two realm-wide tokens, the seeder gate, the D3-4 figure-honesty finding and the
pass's own process lessons are **`docs/PROJECT-STATE.md` §N**.

**⚠️ CORRECTION TO THE RECORD, measured 2026-08-09: the suite baseline was 8, not 7 — and this pass caused the
8th.** A full run at `ee0121a` gave **1795 / 1803**. The documented seven were unmoved; the eighth was
**`DesignTokenContrastTest`**, broken by **`3bf9c02` (P8, Housekeeping)**, which promoted the housekeeping status
colours to `--vy-hk-*` tokens — and `--vy-hk-ooo: rgb(138, 128, 121)` is **the exact literal that test forbids**,
because that triple is the pre-AA **`--vy-muted`** original. **It is not an AA regression** (the token is used
once, as a 9px status **dot** background, never as text, and every dot carries its status word). The test's
guard was a whole-file substring match, broader than its stated intent. **It rode through seven subsequent gates
unnoticed — exactly the failure mode the "know your baseline count" lesson exists to prevent.**
**✅ CLOSED at `9696b9c`** — see the dedicated entry below and `PROJECT-STATE.md` **§J.bis**.
⚠️ *A prior revision of this entry said the triple was the pre-AA `--vy-faint`; it is `--vy-muted`. Corrected.*

**One finding logged, deliberately NOT fixed — D3-4.** Operator Insights' "Last year" occupancy divides by the
availability spine's **coverage**, not by the month, so three nights of 2025 data print as "50%" under a month
label and feed the KPI deltas. It is produced by `PerformanceMetrics::monthlyTrendForProperties` — **a
computation**, outside a design pass — so it is recorded for the backend phase. ⚠️ It is **not** a demo artifact
alone: the same hole opens for **any tenant in its first year**.

## 2026-08-09 — Accent-as-TEXT brought to AA via a new `--vy-accent-text` token (brand fills untouched)

The second half of the realm-wide contrast work, surfaced by the design-parity pass on Insights.

**The gap.** `--vy-accent` (`rgb(194,65,12)`) is both the brand fill *and* the link/label text colour. As
**text on a glass card** it measured, by sampling real rendered pixels with the glyphs blanked:

| Surface | Before | After |
|---|---|---|
| `.vy-arr-link` on a card | **4.40–4.46:1** | **5.22–5.30:1** |
| `.vy-task-go`, `.vy-ins-ob-a` | **4.27–4.45:1** | **5.28–5.35:1** |
| `.vy-task-sec-label` (worst) | **4.09:1** | **4.92:1** |
| owner `.vy-ov-attn-link` | — | **5.45:1** |

⚠️ The wireframes' own `#B4400F` is **4.48:1** here — also under AA, so matching the prototype literally
would not have fixed it.

**The decision: a second token, not a change to `--vy-accent`.** `--vy-accent-text: rgb(176,56,8)` (~10%
deeper, same terracotta family) is applied to the **41** declarations where accent is text; the **31**
chip/tag declarations that paint their own tinted surface were deliberately left on `--vy-accent` (measured
**4.61**, passing — and their tint is part of the brand look). **`--vy-accent` itself did not move**, proven
in-browser: the token still reads `rgb(194,65,12)` and the brand mark's gradient is byte-identical, so every
button, gradient, border and icon fill is unchanged.

**Two traps worth recording.**
1. **A naive replace of `color: var(--vy-accent)` also matches `border-bottom-color:` and `border-color:`,**
   because those property names *end in* `color:`. Five border declarations were silently retargeted and had
   to be reverted — borders are fill, not text. A follow-up grep now asserts the token appears **only** as a
   plain `color:` declaration (41/41).
2. **Verifying corrected the fix direction.** On Page 3 the assumption was that `--vy-accent` would be
   *better* than the prototype's `#B4400F`; measured, it is **worse** (4.06 vs 4.48). Both failed, which is
   what made a new token the right answer rather than a swap.

Together with `--vy-on-grad`/`--vy-on-grad-soft` (the sidebar fix earlier today) this closes the realm-wide
contrast gaps that the 2026-08-07 audit's gradient-background exclusion left open on both surfaces —
text on the page gradient, and accent text on glass.

Suite 1796/1803 · 11,858 assertions (the documented 7 non-green, unmoved); PHPStan L5 (0); Pint clean.

## 2026-08-09 — Sidebar nav contrast brought to AA in BOTH shells (closes the sweep's gradient-bg exclusion)

Surfaced by the design-parity pass on the Operator Dashboard and fixed realm-wide before continuing.

**The gap.** `.vy-nav-label` and `.vy-nav-item` hardcoded the prototype's greys (`#9C9089` / `#8A8079`).
Measured by **sampling the real rendered pixels** out of a Playwright screenshot (the nav text hidden so
the capture shows the true composited background, orbs and light-streaks included):

| | Before | After |
|---|---|---|
| Operator group labels | **1.82:1** | **4.74:1** |
| Operator nav items | **2.23:1** | **4.93:1** |
| Owner group labels | **1.81:1** | **4.71:1** |
| Owner nav items | **2.21:1** | **4.93:1** |

**Why the 2026-08-07 audit missed it.** That sweep's contrast pass **excluded gradient-background cases
as false positives** (it had produced 90 raw "failures", cut to 2 real token failures). The exclusion was
about mis-measurement, not an exemption — and it took the sidebar with it. The polish pass then darkened
`--vy-muted`/`--vy-faint` for text on **glass cards**, which is a different and lighter basis. This closes
that gap.

**The decision: two new semantic tokens, not a darker `--vy-muted`.**
`--vy-on-grad: rgb(84,74,68)` and `--vy-on-grad-soft: rgb(88,78,72)` name the real distinction — *text
sitting directly on the page gradient* vs *text on a glass card*. `--vy-muted` cannot serve both: measured
against the gradient's darkest sampled stop `rgb(241,185,150)` it reaches only **4.03:1** and fails AA,
while it is correct (5.48:1) on a card. Two tokens keep the group-label/item tonal step and both clear 4.5.
Warm stone, deliberately not a cold grey, so the shell still reads warm.

**States verified, not assumed:** active (terracotta on the frosted pill) **4.62:1**, hover **6.52:1** —
both AA, both still clearly distinguishable from rest state. The `aria-disabled` Pro item keeps its
`opacity:.55` and is exempt (WCAG 1.4.3 inactive components), but its hover colour was routed through the
token too so no raw hex survives in either nav.

**Applied to BOTH shells because `--vy-bg` is byte-identical between them** — verified, not assumed, and
the owner realm was then measured independently rather than inheriting the operator numbers.

⚠️ **Still carrying raw prototype greys** (`#9C9089` ×5, `#A69A92` ×2) on owner *card* labels
(`.vy-ov-metric-label`, `.vy-mu-stat-label`, `.vy-mu-uplabel`, `.vy-mu-calhead`, `.vy-ov-stays-foot-l`,
`.vy-au-cta--wait`). Those sit on **glass**, not the gradient, so they are a different measurement and were
left for the owner-realm pages of the design pass rather than swept in blind.

Suite 1796/1803 · 11,858 assertions (the documented 7 non-green, unmoved); PHPStan L5 (0); Pint clean.

## 2026-08-09 — THE AUTH/ISOLATION PASS IS COMPLETE: all three flagged items fixed

`8ddd389` Maintenance-Costs scope + period + export parity · `5f27085` workspace-picker write ·
`306715f` password-reset session termination. Each dual-method verified (browser AND code/DB) and
adversarially; each carries a regression test proven to fail without the fix. Detail in PROJECT-STATE.

**What made these worth escalating out of WAVE 3.** All three were latent — nothing leaked in the seeded
demo, because `property_user` had 0 rows and no demo user belongs to two orgs. They needed tests and
deliberately-seeded adversarial state to expose, which a cosmetic pass would never have produced.

**Decisions locked.**
- The Maintenance-Costs scope MIRRORS the board (`whereHas('unit', property_id ∈ readIds)`) rather than
  inventing a second mechanism, and `report()` REQUIRES the read-set — PHPStan then fails any caller that
  forgets it (it caught all four immediately). An empty read-set yields NOTHING; it must never fall back to
  org-wide, which would reopen the hole.
- The export and the screen resolve ONE period param through ONE helper. An earlier cut had the screen read
  `?period=` and the export `?from=`/`?to=` — and the export links carry no dates, so every export would
  silently have been ALL-TIME beside a 6-month page. That is the two-readers-drift trap (#17) reappearing
  inside its own fix.
- The workspace picker lists only workspaces the user can ENTER (membership AND a back-office role). This
  HIDES a dead end; `EnsureBackOfficeAccess` remains the boundary and is untouched.
- Session termination lives in the shared `changePasswordTo()`, so reset and in-app change cannot drift;
  the reset kills all sessions, the in-app change spares the current browser.

**Preserved:** empty-pivot = full-org access (documented and intended); a reset is scoped to the resetting
user and is not a global sign-out (tested).

**Suite:** 1775/1782 · 11,795 assertions, non-green exactly the documented 7 throughout.

## 2026-08-09 — 🏁 THE WIREFRAME-PARITY SWEEP IS FULLY CLOSED (WAVES 1/2/3 + the auth/isolation pass)

The last three tail items landed: `fb51b40` owner payout YEAR filter · `c0b31ab` owner chart scaling ·
`a54bfea` Notifications pagination (both realms). With them, **every item from the 124-wireframe sweep is
resolved, verified-already-fixed, or reclassified as a refusal — 0 unaddressed gaps.**

**Final tally:** 5 HIGH · 3 structural MEDIUMs · 23 false-copy items · the minor/low tail · 3 escalated
auth/isolation items. Suite **1796/1803**, non-green exactly the documented 7 throughout.

**Decisions locked in this last pass.**
- **The chart scales to the DATA, not the goal.** `earningsSeries()` scaled bars against
  `max($maxNet, $goalMinor)` to keep the goal line inside the chart — but a €6,000 target against an €885.60
  best month rendered the real bars at 7–15%. They are now 50–100%. The goal line still renders (it is the
  owner's real target) and pins to the top when above range, with the caption SAYING "above this range" so
  its position cannot imply a proximity that isn't real (#39).
- **The payout year filter narrows the HISTORY only.** The KPI band and next-payout card describe the
  owner's whole position; filtering them by a browsed year would make their labels false.
- **Notifications: `items` is a LIST, the paginator is returned separately as a paginator of MODELS.** That
  is what resolved PHPStan's invariant `TValue` — by shape, not by cast, widening, or a baseline entry.
  Both realms were paginated in the SAME change because the feed is shared.

**Method notes worth keeping (all three cost real time and were caught the same way — by rendering).**
1. **A test must discriminate, not merely pass.** The payout filter was reverted last session because the
   assertion used money strings, which the KPI band also satisfies. It now counts HISTORY ROWS and checks
   per-row remittance links. And the row counter itself over-counted by a constant until it matched
   `class="vy-row-month"` rather than the bare class name, which the stylesheet also contains.
2. **The Blade `@`-trap from an unexpected direction:** writing a directive's NAME inside a CSS comment is
   still compiled. That produced the second of two 500s while Pint, PHPStan and the tests all passed.
3. **Static checks cannot see a rendered page** — stated in CLAUDE.md, and it earned its place again twice
   in this single gate.

**Not "fixed", by design:** the §5 refusals (fabricating them is worse than absent) and the two
founder-deferred items (#6 rates guardrail, #10 team limit) — honest in copy, awaiting a product decision.

**Remaining Vyrobel work is no longer parity:** the ⛔ OPEN BILLING DECISION · the deployment sprint · the
scale-performance workstream · the founder's go-live flips.

## 2026-08-09 — Maintenance Costs ESCALATED out of WAVE 3 into the auth/isolation pass

Verified against code, not assumed: `MaintenanceCostService::resolved()` is
`MaintenanceTicket::query()->where('status','resolved')` — **tenant-scoped only, no property constraint** —
and `report()` takes no arguments. The sibling board (`MaintenanceList`) scopes through `currentReadIds`.

**The call:** this is an ACCESS gap, not a presentation one, so it is flagged rather than fixed in WAVE 3.
`PropertyContext` documents `property_user` as *"an OPTIONAL access restriction … the set the user MAY WORK
IN"*. A screen bypassing it shows a pivot-restricted user the cost figures, the **unit names** and the
billable lines of properties they may not work in. WAVE 3 explicitly excludes auth/isolation.

**Honest exposure:** LATENT. `property_user` has 0 rows today and an empty pivot grants full-org access, so
no user is restricted and nothing leaks right now. It becomes real the moment anyone is pivoted to a subset —
the same latency profile as the WAVE-2 "Revenue today" item (which WAS fixed, because that one was a wrong
FIGURE on a dashboard whose every sibling KPI was already scoped; this is the whole report being unscoped).

**Carried forward for whoever fixes it:** `MaintenanceCostExportController` calls the SAME service, so the
PDF/CSV must take the IDENTICAL scope and period or export and screen will disagree (#17). The missing period
selector is genuine WAVE-3 presentation work and should ride along with that fix rather than be done twice.

This makes THREE items awaiting the auth/isolation pass: the workspace picker, reset sibling-session
termination, and this.

## 2026-08-09 — WIREFRAME-PARITY WAVE 3 STARTED (3 gates); the named item list was largely STALE

Gates: `270806f` owner format/timeline · `51c3f9f` guest add-on/refund/arrival-hour · `35dc05f` Insights
unit labels. Suite 1758/1765, non-green exactly the documented 7. **WAVE 3 is NOT closed** — the fix plan's
WAVE 3 section records done-vs-open.

**The finding worth recording: verify-first invalidated 4 of the 5 named items.** The corrupted date literal
was already fixed by the polish pass; `Team &amp; access` renders correctly; the Agreement JSON leak is not
reproducible on current data; and the Statement-of-Account PDF has been its own document since the
2026-08-07 audit (4-tile KPI band + activity table, built from `FolioDocument::data`, so it shares the
receipt's folio source and cannot drift, #17). Re-fixing any of them would have been churn — or worse, would
have "fixed" something that was already right.

**Six real gaps closed**, of which two were functional rather than cosmetic: pending add-on rows rendered
BLANK because the controller read `payload['add_ons']` while the service writes `add_on_ids`; and the guest
Payments overview never surfaced `FolioTotals::refunded_minor`, so "Paid to date" (which is NET of refunds)
appeared smaller with nothing explaining it.

**A decision recorded for the remaining list:** the login **workspace picker** and **password-reset
sibling-session termination** are in the sweep's WAVE 3 rows but touch **auth/isolation**, which WAVE 3
explicitly excludes. They are flagged, not folded in, and need their own gated pass.

## 2026-08-09 — WIREFRAME-PARITY WAVE 2 CLOSED: the 3 structural MEDIUMs + all 23 false-copy items

Nine gates: `00a9fe5` S1 wallet concurrency · `47cf1ff` S2 write-only notes · `cb4dec0` S3 yearly-switch ·
`8deb066` wrong figures · `8437a93` mislabelled figures · `599a452` anti-drift copy · `f930dda` unenforced
claims · `babbb74` prototype fiction · `b44bb64` + `9620b95` unwired controls. Each carries a regression test
proven to fail without its fix. Detail in `PROJECT-STATE.md` §F.5 and the sweep report §2c.

**⛔ TWO ITEMS DELIBERATELY LEFT AS COPY-ONLY — the founder's decision (2026-08-09), NOT to be "finished"
by a later session without an explicit call:**

- **#6 — the Owner Rates guardrail.** Verified before rewording, and the picture was more nuanced than the
  sweep recorded: `RateProposalService` genuinely enforces the pricing mode, the ±% auto-approve tolerance
  AND the floor/ceiling — but only on a **proposed** rate. `RateActionController`, the operator's direct
  rate write, never consults the guardrail. The copy now promises exactly the proposal path and says plainly
  that the operator can still set a rate directly. **Making the guardrail bind the operator's direct write
  is a real shift of control between operator and owner — a product decision, deferred.**
- **#10 — the plan team limit.** The page claimed "unlimited" for every plan; Essential's plan sets 5. The
  REAL limit is now displayed (and Pro's genuine null still reads "unlimited" — a test pins both directions).
  **Whether the limit should BIND is part of the open billing decision (G.1), deferred.**

**Method notes worth keeping.**
1. **Four pre-existing tests were holding the false copy in place** — `OwnerDocumentsViewTest`,
   `OwnerHelpTest`, `OperatorWorkOrderTest`, `MaintenanceDetailViewTest`. Two of them described the
   fabrication as "HONEST" in their own comments. A green suite was defending exactly what the sweep found.
   All four were corrected toward the truth, never relaxed.
2. **Static checks cannot see a rendered page — again.** A `@php(...)` directive compiled to a malformed
   `<?php(` and 500'd the Payout Method page while Pint, PHPStan and the new tests all passed, because those
   tests matched file CONTENT rather than a rendered page.
3. **The obvious fix can hide the same bug one level down.** #16's cause was that an unsaved Eloquent model
   carries no schema defaults; the fix routed through `firstOrCreate()`, which returns a model that ALSO
   lacks them. Only running the FULL suite (non-green 7 → 8) surfaced it.

**Verified:** suite **1748/1755 · 11,722 assertions**; PHPStan L5 (0); Pint clean; `migrate:fresh --seed`
clean; the 7 non-green are the SAME documented pre-existing set.

## 2026-08-08 — WIREFRAME-PARITY SWEEP + WAVE 1: the 5 HIGH findings are CLOSED

A full parity sweep of **all 124 wireframes** (`docs/WIREFRAME-PARITY-SWEEP-2026-08-08.md`, plan in
`docs/WIREFRAME-PARITY-FIX-PLAN-2026-08-08.md`), then WAVE 1 — the five HIGH findings, one per gate.

**Commits:** `15700b4` the sweep docs (documentation-only) · `ecf3685` HIGH-1 cancellation JSON ·
`165dea2` HIGH-2 attribution trigger · `6312df2` HIGH-3 per-statement payable · `123bf89` HIGH-4 one
revenue source · `bca3679` HIGH-5 marketing unsubscribe.

**What the sweep concluded.** The product is materially MORE honest than its own wireframes: 0 of 16
fabricated prototype values appear anywhere (#39 holds product-wide), 0 rendering failures, and the #28
perimeter is confined to exactly Guests CRM + Guest Profile. The dominant failure mode is **copy that
asserts behaviour the code does not implement** — that is WAVE 2, still open.

**Decisions locked in WAVE 1:**

- **HIGH-2 is triggered AFTER the folio is posted, NOT on `reservation.confirmed`.** The obvious wiring
  would have been actively harmful: attribution reads folio room revenue, which `createBooking()` does not
  post, so it would have banked €0 and latched `liveVersion` permanently. The trigger sits at the three real
  entry points after `postFees`/`postTaxes`, refuses to latch a zero, and a `owners:backfill-attribution`
  command covers existing rows.
- **`/app/portfolio` was deliberately NOT forced to agree with `/app/reports`.** `PerformanceMetrics`
  pro-rates `price_minor` across in-range nights; a folio carries no per-night split and cannot be pro-rated,
  and the ADR/RevPAR identities depend on that pro-rating. Different bases BY DESIGN, both real, both
  labelled. Only the two figures **on one screen** were unified onto the folio (the authoritative money
  source, #17).
- **The unsubscribe covers MARKETING only.** Transactional mail stays outside the consent gate — a guest
  cannot opt out of their own booking confirmation — and a test pins that boundary. The route is public and
  signed (it must work from an old email with no login), GET only renders (a prefetching scanner must not opt
  anyone out), and POST is CSRF-exempt because it is the RFC 8058 one-click target where the signature IS the
  authorisation. No new external send was enabled.
- **The sweep's own €1,680 figure was corrected, not quietly dropped.** It measured owner bookings with no
  folio at all (15 of 24 on a fresh seed) — the same root cause as HIGH-4, not a second gap. The correction is
  written into the report at the point of the original claim.

**Method lesson (recorded because it nearly cost a real guarantee).** HIGH-3's first regression test **passed
without the fix** — it asserted the row's money figure, which prints from the statement regardless of pay
status. Every WAVE-1 test was then run against a stashed tree; a test that cannot fail guards nothing.

**Verified:** suite **1715/1722 · 11,610 assertions** (+23 tests); PHPStan L5 (0); Pint clean;
`migrate:fresh --seed` clean; the 7 non-green are the SAME documented pre-existing set, unmoved.

## 2026-08-08 — HARDENING SPRINT: the audit's should-fix-before-launch set is CLOSED

Follows the same day's launch-blocker pass. Closes every item in `AUDIT-COMPLETE-2026-08-07.md` §8
"SHOULD FIX BEFORE LAUNCH". Explicitly NOT in scope (later workstreams): the deployment sprint (CI/CD,
Docker, backups), the scale-performance work, the 26 parity gaps, the founder go-live flips.

**Commits:** `6c390ad` headers · `33e585d`+`a2c7632` cookie/proxy/HTTPS · `260e17c` IBAN · `20aa3b9` CsvSafe ·
`65f0edf` error views + logging · `b9d697d` deps · `00862df` lockfile · `8a7487f` fonts/seeder/env ·
`f222c03` headers on 404s.

**Decisions locked.**
- **The CSP is built from an inventory, and its relaxations are documented, not accidental.** `style-src`
  keeps `'unsafe-inline'` because ~97 inline style ATTRIBUTES cannot carry a nonce (no mechanism exists);
  `script-src` keeps `'unsafe-eval'` because Livewire's Alpine evaluates via `new Function`. Stated honestly
  in-code: with both, script-src is NOT meaningful XSS protection here — the real wins are `frame-ancestors`,
  `object-src`, `base-uri`, `form-action`. Tightening either needs code work, not a header edit.
- **`frame-ancestors 'self'`, not `'none'`** — the Email Previews page frames a SAME-ORIGIN app URL so email
  CSS cannot leak into `/app`. `'none'` would have silently broken it. `'self'` still blocks the actual
  threat (cross-origin clickjacking).
- **The CSP now permits NO external origin**, which is only true because the fonts were self-hosted. Anything
  reaching for a third party is now a violation by definition rather than a judgement call.
- **`CsvSafe::row()` replaces per-cell `field()`** as the contract: a per-column judgement call is what let 7
  owner exports ship with no sanitisation at all. Two tests are STRUCTURAL (no private copies; no unwrapped
  `fputcsv`), so the gap cannot reopen.
- **Sensitive input is excluded from the flash GLOBALLY**, not per-controller, so it covers forms not yet
  written. The cost — a rejected payout form no longer repopulates the IBAN — is the correct trade.
- **Seeders refuse production in two places** (DatabaseSeeder and PlatformAdminSeeder independently, since the
  latter can be invoked alone). It minted a super-admin whose password was the literal string `password`.
- **`.env.example` documents app-specific vars only.** Listing all 101 framework knobs would bury the 15 that
  matter.

**⚠️ Three self-inflicted problems were caught by verification rather than shipped — worth recording because
each looked correct:**
1. `env()` outside `config/` returns NULL under `config:cache`, which the deploy runbook runs — HTTPS forcing
   and trusted proxies would have been silently dead in production. PHPStan caught it; `config/security.php`
   fixes it.
2. Reading `config()` inside `bootstrap/app.php`'s middleware closure FATALS (no `config` binding yet), and a
   custom TrustProxies subclass is silently RESET by the framework's own. The working lever is the static
   `TrustProxies::at()` from `boot()`.
3. The security headers were registered on the `web` GROUP, so an unmatched-route 404 — the branded page most
   likely to be probed — returned ZERO headers while every real page returned five. Found in the final sweep.

**State:** suite 1667 / 1660 passed / 11,377 assertions; the 7 non-green are exactly the two documented files.
PHPStan L5 (0) · Pint clean · `migrate:fresh --seed` clean · `composer audit` clean · `npm audit` clean.

**Remaining after this sprint:** the deployment sprint (CI/CD, Docker/deploy artefact, backups), the
scale-performance workstream (indexes, N+1, pagination, caching), the 26 parity gaps, and the founder's
go-live flips.


## 2026-08-08 — AUDIT FIX PASS: the launch blockers (5 HIGH + cron + rate-limit) are CLOSED

**Context.** `docs/AUDIT-COMPLETE-2026-08-07.md` (7 layers × 5 realms, 24 agents, 66 findings, 0 CRITICAL)
found 5 distinct HIGH issues plus 2 launch-blocking deployment gaps. This pass fixed exactly those, one per
gate, and nothing else — the SHOULD-FIX/POST-LAUNCH lists are deliberately untouched.

**The governing method, and why it mattered.** Every HIGH was a MISSING control where the sibling path
already had the right one, so each fix MIRRORED the adjacent pattern rather than inventing a mechanism:
H1 → `OwnerPayoutService::pay()` (lock + idempotency); H2 → `RetryableSyncJob` (tenant restore across the
queue boundary); H3 → the `can:` middleware 115 routes already use; H4/H5 → the `anonymize()` that already
cleared 8 stores. No new abstraction was introduced anywhere.

**Three findings were SHARPENED against the code before being fixed — the corrections changed the work:**
1. **H2 is a RENDER failure, not a deserialization failure.** The ModelIdentifier restores fine; the Blade
   then touches `leadGuest`/`property` on a context-less worker. (Production named `[App\Models\Guest]`, not
   `[Reservation]` — the tell.) Wrapping only the deserialize would not have fixed it.
2. **F6-4 is a missing CRON ENTRY, not a missing schedule.** All 8 commands are correctly registered and
   `schedule:list` shows them; `schedule:run` simply appears nowhere in the repo. Without it the GDPR 14-day
   deletion and 30-day ID purge silently never execute — which would have quietly undone H4/H5.
3. **F-2a-04 was half right.** A per-credential login throttle already existed in the FormRequests with
   better UX; only the PER-IP dimension was missing, which is precisely why 30 distinct-email attempts
   passed. Fixed only that. A first pass added a redundant duplicate and broke the pre-existing test — the
   fix was corrected, not the test.

**Decisions locked.**
- **The BOUND, not the key, protects the refund total.** `refundPayment()` gains an OPTIONAL caller
  idempotency key; it is deliberately NOT auto-derived onto the row, because two sequential PARTIAL refunds
  of equal amount are legitimate and must not be silently collapsed. The row lock is the correctness fix.
- **The gateway call stays INSIDE the refund lock**, matching the payout rail — correctness of the money
  bound outranks lock duration (the network-in-lock cost is tracked separately as audit P5-02).
- **The inbox gates on `guests.view`, not `guests.manage`** — a Guest Support Agent holds view only and
  replying IS their job; both gates exclude the same low-privilege roles anyway.
- **`reviews.body` is RETAINED on erasure and flagged**, not silently kept: it is the guest's opinion OF THE
  PROPERTY, the author is already anonymised, and destroying it changes published content. The one-line
  change is documented in-code if policy decides otherwise.
- **Nothing was enabled.** Live Stripe stays off (the Idempotency-Key only matters once the founder flips
  live keys); no new external send; no fabricated data; every honest deferral preserved.

**Regression tests target the exact blind spots**, not the symptoms: a TWO-PROCESS refund race (proven to
FAIL pre-fix); a queued mailable RENDERED across a real serialize/deserialize boundary with the context
cleared, through a real transport — closing the `Mail::fake()`/`assertQueued` hole that let a 100% delivery
failure pass 1,617 green tests; plant-every-column erasure verified against RAW DB rows; and both throttle
dimensions. **Four pre-existing tests that ENCODED the H2 bug were corrected.**

**Commits:** `e4775ec` (H1) · `6c10049` (H2) · `f1c6513` (H3) · `209b1f3` (H4+H5) · `8d0ad62` (cron +
throttle) · `42829e7` (restore the mail side-effect boundary that GATE 2 broke — caught by the existing
`MailDeliveryBoundaryTest`, which did its job).

**State:** suite 1640 tests / 1633 passed / 11,238 assertions; the 7 non-green are EXACTLY the two documented
files. PHPStan L5 (0) · Pint clean · `migrate:fresh --seed` clean and now yielding **0 failed jobs (was 36)**.

⚠️ **Tech-debt correction:** `OwnerUnitDetailViewTest` fails 11/12 **in isolation on every run** — a real
deterministic defect, NOT the "random ULID collision flake" the docs claimed. `BookingImportTest` genuinely
is an ordering flake (11/11 isolated).

⚠️ **Vyrobel is no longer "QA-COMPLETE, 0 open defects."** Correct status: **launch blockers fixed; a
hardening + deployment sprint and the scale-performance work remain** (see the audit's §8).


## Locked as of 2 July 2026

- **Product name:** Vyrobel — an independent hotel management SaaS platform.
- **Wedge / ICP:** the hybrid operator (hotel + apartment + villa + multi-unit) with real owner accounting; SMB beachhead in under-served geographies. Not competing head-on with Mews/Cloudbeds on breadth.
- **Architecture:** all-in-one, single unified data model (the "semantic layer" that makes AI agents work natively).
- **Database:** MySQL 8 / InnoDB — genuine MySQL (not MariaDB); no Postgres-specific features.
- **Multi-tenancy:** shared database, `organization_id` column + global scope + tenant guard.
- **Primary keys:** ULID.
- **RBAC:** spatie/laravel-permission, teams mode (team = organization).
- **Double-booking prevention:** two-level (room-type counts + physical-unit non-overlap), enforced in a single guarded `AssignmentService` (app-level locking, since MySQL has no exclusion constraint).
- **Buy-vs-build:** RENT the channel manager (Channex), payments (Stripe + Channex PCI proxy), messaging transport (Meta WhatsApp API / Twilio / SendGrid), smart-lock APIs. BUILD the data model, AI agent layer, hybrid experience, website builder, PWAs, automation builder.
- **Build order:** Phase 1 foundation → Phase 2 distribution/payments → Phase 3 differentiators (website, inbox, owner accounting, AI) → Phase 4 intelligence → Phase 5 platform/moat.

## New decisions

### 2026-07-02 — Phase 1 Step 0 resolutions

1. **Queue driver = `database` for Phase 1** (Horizon+Redis deferred to Phase 2). — **Why:** CLAUDE.md/DECISIONS.md govern over the prompt's general stack line; "Horizon on Redis" describes the end-state, not a Phase-1 requirement, and Phase 1's async needs are trivial. **Corollary (critical):** the authoritative availability mutation (`sold++`/`sold--`) happens **inline and synchronously inside the `AssignmentService` transaction**; the `domain_events` recompute listener is **defense-in-depth only**. Integrity tests must run that listener **synchronously** (not via a queued job) so the double-booking test never depends on a running `queue:work`.
2. **`users.id` converted to ULID now** (skeleton-only data, right moment). — **Why:** Rewrite the skeleton migration to ULID + spec columns (`two_factor_secret`, `two_factor_enabled`, `locale`, `status`, `last_login_at`); switch `sessions.user_id` to `foreignUlid`; `password_reset_tokens` is email-keyed so no PK change. This makes **every** user-referencing FK uniform ULID (`created_by`, `assigned_to`, `posted_by`, `changed_by`, `verified_by`, `audit_events.actor_id`) — no mixed-type FKs anywhere.
3. **ULID on all first-party tenant business tables; third-party package tables keep their native PKs.** — **Why:** spatie `roles`/`permissions` stay integer-keyed; converting their internal keys buys nothing and breaks upgrade paths. This is the governing principle for PK type across the schema.
4. **Soft deletes follow the schema.** SoftDeletes on operational tables; `guests` uses `anonymize()` (never soft-delete-and-retain PII); `audit_events`/`domain_events` are immutable (no soft delete). — **Addition:** posted financial records (`payments`, `invoices`, and **posted** `folio_items`) are **correct-by-reversal** — services never delete them, only void/reverse via new entries, even if a `SoftDeletes` trait sits there as a safety net. Financial integrity comes from an append-only ledger, not deletions.
5. **Spec-gap resolutions:** use the `payments.gateway` superset enum (`stripe,paypal,square,manual,bank_transfer,ota_collect`); `subscription_items` = `subscription_id`, `plan_feature_id`/feature key, `quantity`, nullable `stripe_item_id` (null until Phase 2 Stripe); separate `property_policies` table per the prompt; align `.env.example` to MySQL.

**spatie teams + ULID catch:** with `team_foreign_key => 'organization_id'` and `organizations.id` as ULID, the published spatie migration defaults the `organization_id` column on `roles`, `model_has_roles`, and `model_has_permissions` to `unsignedBigInteger`. These must be **`char(26)`/ULID** to match `organizations.id` (the `model_id` morph is handled via `ulidMorphs`) — otherwise a runtime type-mismatch occurs.

**Step 1 close-out addenda:**

- **Static-analysis bar:** PHPStan (Larastan) **level 5** is the standing minimum (`phpstan.neon`). Ratchet up 1–2 levels as the codebase grows.
- **Tenancy opt-outs (intentional, do not "fix"):** `Membership` (`organization_user`) and `PropertyAssignment` (`property_user`) deliberately do **not** use the tenant global scope — a user must read their memberships across **all** orgs they belong to; these link tables are queried explicitly by `user_id`/`organization_id`.
- **Deferred FKs / forward hooks:**
  - Closed in **Step 2**: `property_user.property_id` foreign key + `PropertyAssignment::property()` relation (properties now exist).
  - Stays a nullable column with **NO FK target** until the owners module (**Phase 3**): `units.owner_id` — forward hook, not a stub.
- **Deferred to Phase 2 / Step 9:** request-scoped middleware that syncs `TenantContext` ↔ spatie team id (`setPermissionsTeamId`) for real HTTP requests. In Phase 1, context is set programmatically (seeders/services/tests).
- **Step 8 watch-item:** the per-org role provisioner must run inside the target org's team context (correct `setPermissionsTeamId`) so the 13 tenant roles attach to the right `organization_id`.

**Step 2 close-out addenda:**

- **`amenities` is a global/standard catalog for Phase 1.** Non-breaking future extension for tenant-custom amenities: add a nullable `organization_id` (null = global, set = tenant-custom); the catalog query becomes `where organization_id is null or organization_id = <current>`. The `amenitiables` join is unaffected.
- **Enforced morph map (standing rule):** `Relation::enforceMorphMap` is on (AppServiceProvider). Every polymorphic-capable model added later (guests→documents, reservations→notes, folios, event subjects, spatie `model_has_roles.model_type`, …) **MUST** be registered in the map or Laravel throws. Treat "add to morph map" as a per-step checklist item.
- **Deferred-FK register:** `room_types.default_rate_plan_id` → close FK in **Step 3** (once `rate_plans` exists). `units.owner_id` stays open until **Phase 3** (owners). `AvailabilityService.sold` term → wired in **Step 5** (reservation_units + AssignmentService); AvailabilityService is a *collaborator* of AssignmentService, not a second write path.

**Step 3 close-out addenda:**

- **Availability event direction (standing rule):** `domain_events` are emitted **only** by the write side (AssignmentService, inventory-block creation, rate/price actions). `AvailabilityService::recompute` is a **pure consumer and MUST NEVER emit events** — prevents feedback loops when Step 5 wires the recompute listener onto `domain_events`.
- **Known P1 limitations + non-breaking extensions (do not build now):** (a) `out_of_order` is date-independent in P1 (from current `condition_status`); dated OOO ranges are a later enhancement. (b) room-type-level `inventory_blocks` count as **1** unit; add nullable `inventory_blocks.quantity` later (null = 1) for "block N units."
- **Step 8 test obligation:** assert the overlap case — a unit both `out_of_order` **and** under a block is subtracted **once**, and `available()` never goes negative.
- **Step 5 design constraint (carry-forward):** AssignmentService must hold the `SELECT … FOR UPDATE` lock covering **both** its own `sold` update **and** any `recompute` run inside the booking transaction, so a concurrent recompute cannot stomp the availability row mid-booking. `sold` is written only by AssignmentService; `total`/`out_of_order`/`blocked` only by recompute.

**Step 4 close-out addenda:**

- **`guest_documents.document_id → documents`** (not a raw `media_id`): reconciles schema Module 4 (`media_id`) with Module 8 ("guest IDs live in `documents`") + the reuse directive. The `Document` carries `signed_url_required`/`media_id`; `guest_documents` wraps it with `type` + `verification_status`.
- **Step 8 test obligation:** `anonymize()` must purge the actual stored file **bytes** (the `Media` storage object on disk), not only the DB rows — a real GDPR erase removes the stored file. (Phase 1 stores no real bytes yet; wire the storage delete when uploads land.)
- **Blind-index for encrypted guest email/phone remains deferred;** likely resurfaces in Phase 2 OTA import (matching incoming bookings to existing guests).

**Step 5 close-out addenda:**

- **`reservation_units.currency` added** alongside `price_minor` (schema lists `price_minor` only): money is always minor units + ISO currency (convention #3) — the price snapshot carries its own currency rather than silently inheriting the reservation header.
- **Deadlock avoidance:** `AssignmentService` acquires availability locks in **deterministic order** (sort by `room_type_id` + `check_in`) before occupying multi-line bookings — prevents a circular-wait between two multi-room bookings under `SELECT … FOR UPDATE`.
- **Booking tests run on genuine MySQL** (`vyrobel_test`, per `phpunit.xml`); the concurrency test uses **true OS-level parallelism** (two PHP processes, separate connections, shared-timestamp barrier), NOT an injected in-process barrier.
- **Step 8 test-obligation register (accumulated — lock these into the Step 8 suite):**
  1. concurrency double-book under true parallelism (proven in Step 5 — re-assert in the Step 8 suite);
  2. deadlock avoidance — two multi-room bookings acquiring overlapping locks in opposite order serialize cleanly, no deadlock;
  3. availability overlap — a unit both `out_of_order` AND blocked is subtracted once, `available()` never negative;
  4. `anonymize()` purges the actual stored file bytes, not just DB rows.

**Step 6 close-out addenda:**

- **`taxes`/`fees` carry both `rate` (percentage modes) and `amount_minor`+`currency` (fixed modes)** — schema lists only `rate`; fixed-amount taxes/fees are money and need Money columns (convention #3). The service switches on `mode`.
- **Folio ledger sum rule:** `folio.total_minor = SUM(folio_items.amount_minor)`. Tax/fee/add-on are each posted as their own `folio_items` lines; the per-line `tax_minor` is breakdown metadata only and is **NOT** summed (avoids double-counting tax).
- **Invoices numbered per property:** `unique(property_id, number)`. When invoicing goes live (P2/P3), replace `max+1` with a **gap-free sequence/counter** — tax authorities require gap-free numbering and `max+1` races under concurrency. Deferred, not built now.

**Step 7 close-out addenda:**

- **Departure-task idempotency keys on `unit + date + type`** (`housekeeping_tasks` has no `reservation_id`): replay-safe for at-least-once queued delivery later. Known P1 edge — two different reservations turning over the **same unit on the same day** dedupe to one departure task; a deliberate P1 choice, revisit if same-day-turnover tracking is needed.
- **Demo data is flagged by obvious demo naming, NOT an `is_demo` column** (Step 8 forbids new schema): the `DemoSeeder` org/property/guests/reservations use unmistakable "Demo" names + a fixed `vyrobel-demo` slug.
- **`Guest::anonymize()` now deletes the stored file bytes** (`Storage::disk()->delete()`) of ID-document media, not just the DB rows — completes the GDPR obligation for a real erase.

**Step 8 close-out — Phase 1 core certified:**

- **Phase 1 guarantee stack certified at Step 8** (permanent regression net = the full named suite, 19 tests / 103 assertions on genuine MySQL): no-double-booking proven under **true OS-level parallelism** (5×5 stress runs, no flakiness); deadlock-free multi-room locking (deterministic lock order); tenant isolation + guard + super-admin bypass; float-free integer-minor money; append-only correct-by-reversal ledger (refund = new row, hold ≠ paid until capture); GDPR erase incl. **file-byte** purge; one-directional event→listener discipline (checkout→task, no recompute loop).
- **Enforced morph map earned its keep:** the suite surfaced an unregistered `InventoryBlock` used as an event subject (first exercised by the availability-overlap test); registered `inventory_block`. Confirms the enforced map is doing its job rather than silently storing FQCNs.

**Step 9 close-out — stub views + tenant middleware (BUILT, not skipped):**

- **Request-scoped tenant middleware wired** (`SetTenantForWebRequest`, appended to the `web` group in `bootstrap/app.php`): resolves the active org from the authenticated user (session override if a member, else first active membership) and sets **both** `TenantContext` and spatie's team id. **Closes the Step-1 deferred item.** CLI/queue/tests keep programmatic context.
- **Livewire 4.3 installed** (deferred since Step 1). Four UNSTYLED, read-only contract-stub pages (`app/Livewire/`: ReservationRack, ReservationDetail, UnitsList, HousekeepingBoard) that expose/document the data shapes for the later design pass, pulling real data through the services. **Not the design** — no CSS/design system; the design pass replaces these views.
- **Suite grew to 21 tests / 116 assertions** (added `StubViewsTest`: pages render real data through the services; web-layer tenant isolation holds — another org's reservation 404s).
- **Livewire-4 gotchas recorded:** (a) default `component_layout` is the namespaced `layouts::app` (unregistered → 500) — pinned each component with `#[Layout('components.layouts.app')]`; (b) a typed `public Model` prop makes Livewire resolve route-model binding during `SubstituteBindings`, **before** the tenant middleware — so hold the id as a string and resolve via tenant-scoped `findOrFail` in `render()`.
- **No login UI** — stub routes are `auth`-protected and verified via `actingAs`; a login screen belongs to the design/auth pass (Phase 2).

### 2026-07-02 — Phase 2 Step 0 gate resolutions

- Redis client: predis/predis (phpredis not loaded; pure-PHP, sufficient for current volume). REDIS_CLIENT=predis.
- Redis server: user installs/starts (brew install redis); Step 1 must PREFLIGHT-check reachability and STOP clearly if absent — never self-install.
- STANDING RULE (guarantee integrity): the availability recompute + sold mutation stay SYNCHRONOUS/inline (listeners remain non-ShouldQueue). ONLY the outbound ARI push to Channex runs async on the queue. Never move inventory recompute onto the queue — it would create a stale-availability oversell window.
- Booking hold: advisory booking_quotes hold with expiry, NOT counted in sold and NOT a hard-hold through AssignmentService. Inventory is only truly occupied at confirmed payment via AssignmentService, which re-checks availability. Two soft-holds on the last unit is acceptable; only one converts.
- Abandoned bookings: a status on booking_quotes (expired-unconverted), not a separate table.
- Deferred to Phase 3: guests.stripe_customer_id / saved cards — depends on the deferred guest-matching/blind-index story. Phase 2 Stripe uses payment intents + deposit holds against reservation/folio only.

### 2026-07-02 — Phase 2 Step 1 close-out (sync reliability backbone, Redis BLOCKED → RESOLVED, see next entry)

- **Redis preflight FAILED — `QUEUE_CONNECTION` stays `database` for now.** `127.0.0.1:6379` refused the connection (no server running) and `redis-server` is not on PATH. Per the Step 0 standing rule, this was not self-installed. `laravel/horizon` + `predis/predis` ARE installed (composer.json/lock updated), `config/horizon.php` + `HorizonServiceProvider` ARE scaffolded, and `.env`/`.env.example` `REDIS_CLIENT` is now `predis` — but Horizon has never actually been run, and `QUEUE_CONNECTION=redis` has NOT been flipped. **Action needed from the user:** `brew install redis && brew services start redis` (or equivalent), then flip `QUEUE_CONNECTION=redis` in `.env` and run `php artisan horizon` to actually exercise the queue on Redis. Until then, the `database` queue driver (already proven by the Step 1 suite) is what's actually running.
- **Horizon dashboard gate:** `viewHorizon` is restricted to the platform `Super Admin` role (`config('authorization.super_admin_role')`), not a hardcoded email list. Because that role is global (`team_id = null`, per `RoleCatalogSeeder`) while `SetTenantForWebRequest` sets an org-scoped permissions team id on every web request, the gate explicitly clears the team scope for the check (same pattern as `OrganizationRoleProvisioner`) — otherwise a real Super Admin would fail `hasRole()` while browsing as themselves. Horizon still auto-bypasses this gate in the `local` environment (framework default).
- **`sync_jobs`/`sync_logs` built as genuine tenant business data** (`BelongsToOrganization`, not the "stamped, not scoped" `domain_events`/`audit_events` pattern) — they back a future per-tenant mapping-health/sync-error dashboard. This means `RetryableSyncJob` (the abstract base every Step 3/4 sync job extends) has to carry `organizationId` explicitly and run its whole body inside `TenantContext::forOrganization()`, since a queue-worker process has no ambient tenant context — the guard would otherwise throw on the very first query.
- **`sync_jobs.type` is a plain `string`, not a DB enum** — deliberate: Steps 2-4 will introduce `ari_push`/`booking_import`/`booking_modify`/`booking_cancel`/`mapping_sync` values that don't exist yet, and a MySQL enum would need an `ALTER TABLE` each time. `direction` and `status` ARE DB enums since that set is fully closed now.
- **Idempotency mechanism:** `SyncJobDispatcher::dispatch()` does `firstOrCreate` on a unique `idempotency_key`, only enqueuing the job when the row is newly created, and dispatches `afterCommit()` so a worker can never pick up a job before the enqueuing transaction (e.g. a future `AssignmentService` write) actually commits. Hardened against the `firstOrCreate` TOCTOU race (two concurrent callers both missing the `SELECT`) by catching `UniqueConstraintViolationException` and re-fetching the winner's row — mirrors the rigor of the Phase 1 `AssignmentService` locking discipline, at the enqueue layer rather than the row-lock layer.
- **Retry/backoff/dead-letter:** `RetryableSyncJob` (`tries=5`, `backoff()=[10,30,60,120,300]`) atomically claims a `sync_jobs` row (`UPDATE ... WHERE status IN (pending,failed)`) before running `process()`, so two deliveries of the same job can never both execute it — this is the idempotent-execution guarantee, independent of the idempotency-key guarantee at enqueue time. `sync_jobs.available_at` is populated on each retryable failure (dashboard-facing "next retry at") and cleared on dead-letter. `failed()` moves the row to `dead_letter` and writes a final summary `sync_logs` line.
- **Proven by `tests/Feature/SyncReliabilityTest.php`** (5 tests, real `database` queue connection, no faked timing): idempotent enqueue (`Queue::fake()`), enqueue→process→succeed, retry-with-backoff-then-succeed (via `Carbon::setTestNow` time-travel between `queue:work --once` calls — no real sleeping), dead-letter after exhausting `tries` (5 failures + 1 dead-letter summary = 6 `sync_logs` rows) with no reprocessing after, and no-reprocessing-after-success. **No Channex call exists yet** — this step proves the machinery in isolation, per the master prompt. Full suite: **26 tests / 152 assertions**, PHPStan clean, Pint clean.

### 2026-07-02 — Redis switched on (Step 1 blocker resolved)

- User installed + started Redis. Switched `QUEUE_CONNECTION=redis` (`.env`; `database` documented as fallback), `REDIS_CLIENT=predis` confirmed, Redis reachable (`PONG` via socket + `Redis::ping()` through predis).
- Re-ran the full suite on the Redis-configured app — **26/26 green** (suite itself runs with `QUEUE_CONNECTION=sync` per `phpunit.xml`, by design, so tests never depend on external infra; this confirms the app-level switch caused no regression).
- **Horizon processes jobs end to end:** added `sync` to the Horizon supervisor's watched queues (`config/horizon.php` — SyncJobDispatcher enqueues to `sync`, which the default supervisor did not watch). Started `php artisan horizon`, dispatched a `FlakySyncJob` (success case) through `SyncJobDispatcher` to Redis; Horizon picked it up and ran it (`RUNNING → DONE 14.98ms` in its log), `sync_jobs` went `pending → succeeded` (1 attempt), one info `sync_logs` row. Terminated Horizon cleanly. **Redis/Horizon are the live queue path from here.**

### 2026-07-02 — Phase 2 Step 2 close-out (channel mapping model + mapping guard)

- **`channels` = the OTA credential vault, per (organization, property, provider).** `credentials` uses the `encrypted` cast (convention #5) — verified ciphertext at rest, plaintext never stored; `settings` must never carry secrets. `provider` is an `enum(['channex'])` for now (other providers are a Phase 5 concern; enum signals "channex only" and an `ALTER` is acceptable when that changes). `status` enum: disconnected/connected/error/disabled.
- **`channel_mappings` maps room_type OR rate_plan to an external id, keyed by `kind`.** Two coexisting uniques — `unique(channel_id, room_type_id)` and `unique(channel_id, rate_plan_id)` — rely on MySQL allowing multiple NULLs in a unique index: a room_type mapping has `rate_plan_id = NULL` (many share `(channel_id, NULL)` on the rate_plan unique) and vice-versa, so each unique only ever constrains its own kind. Health is `status` (pending/active/error/disabled) + `last_verified_at`/`last_error`.
- **Mapping guard (`App\Services\ChannelMappingGuard`) — "never a silent no-op."** `resolveRoomTypeMapping()` / `resolveRatePlanMapping()` return the ACTIVE mapping or throw `UnmappedChannelEntityException` (carries channel/kind/entity + a reason, with an actionable "create/activate a channel_mapping" message) AND write a `Log::warning('channel.mapping_guard.blocked', …)` first — so even a caller that swallows the exception leaves a loud trace. **Only `status = active` counts as mapped** — pending/error/disabled is treated as unmapped, so a half-configured channel cannot quietly leak inventory or bookings. Steps 3-4 (ARI push / booking import) call this guard before doing anything; Step 4 callers additionally record to `sync_logs` when a live sync context exists. Non-throwing `hasActive*Mapping()` variants exist for callers that want to branch.
- **Closed the Phase 1 hook:** `reservations.channel_id` is now a real FK → `channels` (`nullOnDelete`; direct/walk-in carry NULL), plus `Reservation::channel()` relation. Same add-FK-later pattern as Phase 1's `add_reservation_fk_to_guest_documents`.
- **`ChannelFactory`/`ChannelMappingFactory` are tenant-context-aware:** `organization_id` defaults to `TenantContext::id()` when a context is active, else a fresh `Organization::factory()`. Without this, factory-created channels/mappings landed in a *different* org than the active context and the tenant-scoped guard couldn't see them (caught during Step 2 testing). Registered `channel` + `channel_mapping` in the enforced morph map.
- **Verified:** `migrate:fresh --seed` green (all 15 migrations + RoleCatalog + Demo seeders), full suite **35 tests / 179 assertions** (Phase 1 + Step 1 + 9 new Step 2 tests in `ChannelMappingTest.php`), PHPStan L5 clean, Pint clean. Plus a live-DB runtime smoke check proving the guard blocks unmapped + pending mappings with a clear error, resolves active ones, credentials are encrypted at rest, and org B sees zero of org A's channels/mappings. **No `AssignmentService`/inventory code was touched.**

### 2026-07-02 — Phase 2 Step 3 close-out (Channex client + outbound ARI push)

- **`ChannelManagerClient` interface** (`app/Services/Channex/`) + **`FakeChannexClient`** (in-memory, inspectable — `pushes()`/`pushCount()`/`callCount()`/`lastPush()`, plus a `failUntilCall` knob to simulate N failures then success) + **`ChannexClient`** (real REST skeleton over Laravel `Http`, `POST /properties/{id}/ari`). Bound in `AppServiceProvider::register()` via `services.channex.fake` (default **true**): `CHANNEX_FAKE=true` pinned in `.env` and `phpunit.xml`, so the fake is what runs everywhere until real staging credentials are deliberately configured. **The real `ChannexClient`'s request/response shape is UNVERIFIED against live Channex** — explicitly documented on the class itself as the live-integration checkpoint, not proven by this step.
- **ARI push = current full state, never a delta.** `AriSnapshotBuilder` is a pure reader: `available` per night from `AvailabilityService::availabilityRange()` (already-recomputed, post-recompute state), plus price/restrictions for every rate_plan that has an ACTIVE `channel_mapping` on that channel (rate-plan-scoped restriction wins over room-type-scoped for the same date; unmapped rate plans are silently excluded from `rate_plans` — only a missing ROOM_TYPE mapping blocks the whole push).
- **`RateAndRestrictionService` (new, minimal) closes a real Phase 1 gap:** `rate_calendar`/`restrictions` had schema but zero write path — nothing emitted a domain event on a price/restriction edit, so Step 3's "rate/restriction changes trigger a push" requirement was unbuildable without one. Added `setRate()`/`setRestriction()`: plain per-night upsert in a transaction, then ONE post-commit `rate_calendar.updated`/`restriction.updated` domain event covering the whole edited range (same `{room_type_id, start_date, end_date}` payload shape as `inventory_block.created`). Does not touch inventory/`sold`/`AssignmentService`. **Registered `rate_plan` in the enforced morph map** (first polymorphic use, as this service's domain-event subject).
- **`EnqueueAriPushOnInventoryChange` — pure post-commit consumer, same discipline as `RecomputeAvailabilityOnInventoryChange`.** Registered LAST on `DomainEventRecorded` so it reads availability AFTER any recompute for the same event has already applied. Reacts to: `reservation.confirmed`/`cancelled`/`no_show` (room_type+range derived from the reservation's affected lines, grouped by room_type — a multi-room-type booking gets one push per distinct room_type), `reservation_unit.extended`/`cancelled` (from the line itself), and the payload-carrying inventory/rate/restriction events. **Only pushes to `channels.status = 'connected'`** (a disconnected channel would just guarantee a wasted retry-to-dead-letter cycle). It never mutates availability, writes `sold`, or emits a domain event itself.
- **Idempotency key = `ari_push:{channel_id}:{room_type_id}:{domain_event_id}`, not `{channel}:{room_type}:{range}`.** Deliberate: keying on the range alone would let a booking followed quickly by a cancellation for the same room_type+overlapping-range collapse to ONE `sync_jobs` row — the second operation's push would be silently dropped (dispatcher only enqueues on `wasRecentlyCreated`). Keying on the triggering `domain_event_id` instead means every distinct state-changing event gets its own sync_jobs row and its own push — while a genuinely-redelivered SAME event still dedupes (same event id -> same key). Correctness under re-ordering: **not enforced by ordering at all** — each `AriPushJob::process()` reads CURRENT availability/rate/restriction state at execution time (never a snapshot captured at enqueue time), so regardless of which of two competing jobs runs first or gets delayed by retries, the last one to actually execute always reflects the true current state. This is genuine last-write-wins via "always push current truth," not via dispatch ordering.
- **Unmapped room_type is NOT a retryable failure.** `AriPushJob::process()` catches `UnmappedChannelEntityException` from the guard, writes a `sync_logs` warning via the new `RetryableSyncJob::note()` helper, and returns normally — `sync_jobs.status` ends as `succeeded` (delivery handled, nothing to retry) rather than cycling to dead-letter for a config gap a retry can't fix. The guard's own `Log::warning` still fires per Step 2.
- **Verified:** `migrate:fresh --seed` green, full suite **44 tests / 219 assertions** (Phase 1 + Steps 1-2 + 9 new Step 3 tests in `AriPushTest.php`), PHPStan L5 clean, Pint clean. Tests proved (against `FakeChannexClient` only, real client never touched): exactly one push per booking/cancel/block/rate-change (no missed/duplicate/loop — asserted via `DomainEvent::count()` and `pushCount()`); an unmapped room_type produces zero pushes + a clear logged warning + job still succeeds; a disconnected channel gets zero pushes enqueued; a transiently-failing client retries with real backoff (time-travel, `database` connection) and succeeds on the 3rd attempt with exactly one push recorded; a permanently-failing client dead-letters at exactly 5 attempts with zero pushes ever recorded; `availability.sold` is bit-for-bit identical to a plain Phase 1 booking (the push adds nothing) and no extra domain events are emitted (no loop).
- **`tests/Support/channel_helpers.php` (new):** `channelScenario()` moved out of `ChannelMappingTest.php` into a shared Support file (required from `Pest.php`) so `AriPushTest.php` can reuse it without depending on PHP's incidental cross-file function-load order.

### 2026-07-02 — Phase 2 Step 4 close-out (inbound booking import + overbooking policy)

- **Public webhook endpoint does the minimum, returns fast.** `POST /webhooks/channex/{channel}` (`ChannexWebhookController`, CSRF-exempt via `bootstrap/app.php`, no route-model binding — resolves the channel under `runAsSuperAdmin` then sets tenant context, avoiding the Phase-1 Step-9 binding-before-context gotcha). It validates the HMAC signature, dedupes into `webhook_events` on `(source, external_event_id)`, enqueues a `ProcessChannexWebhookJob` through the Step 1 backbone, and returns **202** — zero inventory work in the request. Signature = HMAC-SHA256 over the raw body keyed by `services.channex.webhook_secret`, **fails closed** (no secret ⇒ reject). ⚠️ **Webhook shape + HMAC scheme are DOCUMENTED GUESSES**, exercised only against simulated signed payloads — same live-integration checkpoint as the ARI request shape.
- **OTA bookings import THROUGH `AssignmentService` — the single write path is absolute.** New `AssignmentService::importBooking()` runs the SAME locking core as direct bookings (deterministic lock order, `SELECT … FOR UPDATE`), but is **accept-and-flag, not reject** (`occupyOrFlag()`): if every night has capacity it seats the line (`status=active`, `sold++`, identical to direct); if any night is full it STILL records the line as **`status=overbooked`** (new enum value, added by a raw `ALTER`), does **NOT** increment `sold`, and raises one `overbooking_conflicts` row. `BookingImportService` only normalizes/maps/guest-matches and delegates — it never writes `reservation_units`/`availability`.
- **Overbooking accounting keeps the invariant clean: `sold` = count of ACTIVE occupying lines, always ≤ `total`.** An `overbooked` line carries no `unit_id`, is excluded from Level B overlap checks, and is excluded from sold-freeing on cancel (it never consumed sold). `releaseLine()` centralizes this: active → free sold; overbooked → resolve its conflict (`cancelled_external`), no sold change. `terminate()` now iterates active+overbooked lines through `releaseLine()`, so cancelling an OTA reservation both frees real sold and closes its conflicts (direct reservations have no overbooked lines → no-op superset). This is why `sold` never inflates past capacity — the excess is modeled explicitly, not by letting `available` go negative.
- **Asymmetry proven:** direct booking / walk-in still throw `InsufficientAvailabilityException` when full (unchanged); only the OTA import path accepts-and-flags. Both assertions live in one test.
- **Two-layer idempotency:** webhook redelivery dedupes on `webhook_events(source, external_event_id)` (controller only enqueues on `wasRecentlyCreated`); the same booking arriving via different events dedupes on `reservations(channel_id, ota_reservation_id)` in `BookingImportService::import()`, backstopped by a DB `unique(channel_id, ota_reservation_id)` (MySQL multi-NULL ⇒ direct bookings never collide). Modification re-syncs lines via `reimportBooking()` (release all current lines, re-occupy-or-flag, reservation stays confirmed); cancellation of an unknown or already-terminal booking is a **safe no-op**.
- **Unmapped INBOUND external id fails loudly, never silently dropped.** `ChannelMappingGuard::resolveInboundRoomTypeMapping()` / `resolveInboundRatePlanMapping()` (reverse lookup by `external_id`) throw `UnmappedChannelEntityException::forInbound()` + log — so the import job fails, `webhook_event` → `failed`, `sync_jobs` → `failed` (retries, then dead-letter) with a readable error, leaving the booking parked for manual mapping rather than lost. (Contrast Step 3's OUTBOUND unmapped, which is a benign skip-and-succeed — an inbound drop would lose a real booking, so it must surface.)
- **Guest matching:** by this channel's external ref only (`external_refs->{channel_id} = ota_guest_id`), else create. Blind-index matching of encrypted email/phone stays deferred to Phase 3.
- **Loop closed, no feedback loop:** an OTA import emits `reservation.imported` (and modify → `reservation.modified`), which `EnqueueAriPushOnInventoryChange` now consumes to push ARI back OUT to the other channels. Verified `sold` is bit-identical to a plain direct booking and the push emits no inventory events. `rangesFromReservation()` simplified to derive the affected room_type+range from ALL of the reservation's lines (any status) — robust across confirm/cancel/import/modify since the push reads current state anyway.
- **New:** `webhook_events`, `overbooking_conflicts` tables + models (morph-mapped `webhook_event`, `overbooking_conflict`); `reservation_units.status` gained `overbooked` (documented via an explicit `@property` on the model because Larastan reads the enum from the original CREATE migration and can't parse the raw `ALTER`). No `AssignmentService` locking-core change — only a new sibling path reusing the same lock/`adjustSold`.
- **Test-isolation hardening (pre-existing latent fragility surfaced by this step):** non-RefreshDatabase tests COMMIT rows, and the suite's first RefreshDatabase test (`AssignmentServiceTest`, alphabetically) runs `migrate:fresh` **before** `BookingImportTest`, so BookingImportTest's committed data was surviving into `TenantIsolationTest`'s super-admin count (13 vs 2). Also the shared `jobs`/`failed_jobs` tables leaked queued jobs across tests/runs, breaking the AriPush/SyncReliability retry tests. Fixed with shared helpers: `flushQueue()` (beforeEach on the three queue-driving files) and `cleanupCommittedTenantData()` (afterEach — force-deletes orgs so FK-cascade wipes tenant data, clears event logs), generalizing `ConcurrentBookingTest`'s existing self-clean discipline.
- **Verified:** `migrate:fresh --seed` green, full suite **55 tests / 279 assertions** (Phase 1 + Steps 1-3 + 11 new Step 4 tests in `BookingImportTest.php`), PHPStan L5 clean, Pint clean. All Channex interaction was against `FakeChannexClient` + simulated signed payloads — **no network; the real client + webhook signature/shape remain unverified against live Channex (the deferred integration checkpoint)**. Cross-system concurrency stress (direct-vs-OTA race) is deliberately **not** built here — that is Step 5.

### 2026-07-02 — Phase 2 Step 5 close-out (cross-system concurrency & sync-reliability crux suite)

- **New permanent suite `tests/Feature/CrossSystemConcurrencyTest.php` (7 named tests) + `tests/Support/cross_race_worker.php`.** No new product surface, no new tables — this step hardens and PROVES what Steps 1-4 built. `AssignmentService` remains the sole inventory writer.
- **Parallelism approach (honest breakdown):**
  - **TRUE OS-level parallelism** (proc_open separate processes, separate MySQL connections, shared future-timestamp barrier, committed data, no RefreshDatabase — the Phase 1 `ConcurrentBookingTest` technique): **Test 1 (direct vs OTA)**, **Test 2 (OTA vs OTA)**, and **Test 4's concurrent leg** (3 processes importing the same booking at once).
  - **In-process against `FakeChannexClient`**: Test 3 (ARI-push-per-change), Test 4's sequential leg, Test 6 (overbooking + resolve), Test 7 (mapping guard both directions).
  - **Redis end-to-end via time-travel** (real Redis transport, `queue:work redis --once` + `Carbon::setTestNow`, no real sleeps): Test 5.
  - **No test hits the network** — all Channex interaction is the fake + simulated signed payloads; the live Channex/Stripe checkpoint remains deferred.
- **Direct-vs-OTA race decider — stated honestly.** The headline races a direct booking (targets the specific last unit → runs Level A + Level B) against a type-assigned OTA import for the SAME last unit (`totalUnits=1`). Because an OTA import carries no `unit_id`, the cross-system contention is decided by the `SELECT … FOR UPDATE` availability-row lock + Level A count (the row lock is the mutex enforcing both levels); whoever wins seats, the other is rejected (direct) or recorded overbooked+conflict (OTA). Pure two-parties-on-the-same-physical-unit Level-B contention is not expressible direct-vs-OTA (OTA never targets a unit) and stays proven by Phase 1's `ConcurrentBookingTest` (re-asserted green). Every round asserts: exactly one active seat, `sold == 1` on every night (never 2), and conflicts = 1 iff the OTA lost the seat.
- **Repeat-run result: 5 runs × 5 internal rounds × 2 race types = 50 real races, zero flakiness** (each run: 2 tests / 55 assertions, ~15.7s). Plus one in-suite pass.
- **Two concurrency safeguards surfaced by Test 4 (allowed hardening, no new surface):** (1) the webhook controller now uses `WebhookEvent::createOrFirst` (not `firstOrCreate`) so two concurrent deliveries of the same event resolve to one row instead of a 500 on the unique index; (2) `BookingImportService::import()` catches `UniqueConstraintViolationException` from the reservation insert and re-fetches the winner, so concurrent duplicate imports both return the one reservation without error. The DB `unique(channel_id, ota_reservation_id)` is the backstop both rely on.
- **Re-asserted no regression:** full suite **62 tests / 380 assertions** green on MySQL `vyrobel_test` (Phase 1's 21 + Steps 1-4's 34 + Step 5's 7), PHPStan L5 clean (analyses `app`+`database`; the crux suite's cross-file Pest helpers are not in scope, by design), Pint clean, `migrate:fresh --seed` clean. The Phase 1 core guarantees (double-booking, deadlock-free multi-room, tenant isolation, append-only ledger, GDPR erase, one-directional event discipline) all remain green — Step 5 added cross-system contention on top without weakening any of them.

### 2026-07-02 — Phase 2 Step 6 close-out (Stripe payment gateway)

- **`StripeGateway implements PaymentGateway` — the existing Phase 1 interface, unchanged.** `charge/authorize/capture/void/refund` map onto Stripe PaymentIntents behind a thin `StripeClientInterface` seam (`app/Payments/Stripe/`): `FakeStripeClient` (in-memory, inspectable — records intents/captures/refunds, `nextIntentStatus` knob for 3DS; **implements Stripe's real `Stripe-Signature` HMAC scheme** so tests sign faithfully) and the SDK-backed `StripeClient` (config-gated, never run by CI). `stripe/stripe-php` ^20.3 installed. No new tables/models — reuses `payments`, `payment_authorizations`, `webhook_events`.
- **Gateway selection is config-driven:** `services.payments.gateway` (`manual`|`stripe`, default manual) drives the `PaymentGateway` container binding; `services.stripe.fake` (default true) gates `StripeClientInterface` → fake vs real. FolioService resolves `PaymentGateway` per use, so a per-context switch is just a config read (per-tenant override is a Phase 3 hook). `ManualPaymentGateway` retained and re-asserted working.
- **Phase 1 guarantees preserved exactly:**
  - **TOKEN-ONLY** — `$token` is a Stripe `payment_method` id, `gateway_ref` is a Stripe intent/refund id; a raw PAN/CVV never reaches the app. Re-asserted: `card_token` holds the token and never matches a PAN pattern.
  - **APPEND-ONLY / correct-by-reversal** — refunds are new offsetting `payment` rows (original untouched); auth→capture advances the `payment_authorizations` lifecycle in place (a hold lifecycle, not a posted money-movement row), captures/refunds are always new ledger entries. `FolioService` remains the SINGLE place folio/reservation totals recompute from the ledger — Stripe results post THROUGH it.
  - **Deposit holds** map onto `payment_authorizations` (authorize → capture / void), integer minor units, hold ≠ paid until captured.
- **SCA/3DS surfaced, not swallowed (European requirement).** A `requires_action` intent returns an UNsuccessful `GatewayResult` carrying the `client_secret` (via a new **optional, additive** `GatewayResult::$clientSecret` — the interface method signatures are unchanged). `FolioService::recordPayment` maps such a result to a **`pending`** payment (not a false `failed`, not counted as paid) and stashes `client_secret` in `metadata`; the existing `payments.status` enum already had `pending`. Manual/synchronous gateways return successful → `succeeded`, so Phase 1 behavior is untouched.
- **Stripe webhooks (real signature, idempotent).** `POST /webhooks/stripe` validates Stripe's `Stripe-Signature` scheme via the client seam (config-gated `services.stripe.webhook_secret`), resolves the target `payment` (hence org) under super-admin then sets context, dedupes on `webhook_events(source='stripe', external_event_id)` via `createOrFirst` (concurrent-safe, Step 5 pattern), and calls the new **`FolioService::settlePayment(pending → succeeded|failed)`** — which only advances a `pending` row (double-apply-safe) and recomputes. Processing is inline (a light, idempotent DB update; Stripe retries on non-2xx). `settlePayment` completes an unsettled payment's lifecycle — it never mutates a settled ledger entry.
- **OTA card data via Channex** uses the tokenized/proxy pattern — the import path stores no card data; a Channex-provided token would flow through the same `card_token`/`payment_method` seam. (Modeled; the live token exchange is part of the deferred Channex checkpoint.)
- **Verified:** `migrate:fresh --seed` green, full suite **68 tests / 429 assertions** (Phase 1 + Steps 1-5 + 6 new Step 6 tests in `StripePaymentTest.php`), PHPStan L5 clean, Pint clean. Tests proved (against `FakeStripeClient` only): config-selected charge posts through FolioService + card_token is a token; deposit hold not paid until capture; refund is a new append-only entry; **3DS → pending (with client_secret), then a webhook confirms → paid**; webhook applied exactly once under N redeliveries + invalid signature rejected with nothing recorded; ManualPaymentGateway still works. **HONESTY: everything is against the mocked Stripe client — no network. Live Stripe TEST-MODE verification with real test keys is a separate deferred checkpoint (parallel to Channex staging); the real `StripeClient` + SDK webhook path are unverified by CI.**

### 2026-07-02 — Phase 2 Step 7 close-out (public booking engine — backend + unstyled contract stub)

- **`booking_quotes` built exactly per the Step 0 lock: one table, advisory hold only.** New `BookingQuote` model (Money-cast subtotal/tax/fees/discount/total, encrypted `email`/`phone`, `requested` json). Abandoned capture is folded in via `status=abandoned` + `recovery_status` — no separate table. Registered `booking_quote` in the enforced morph map. Added a nullable/unique `properties.slug` (new column) — the public URL identifier the booking route resolves on.
- **The hold is genuinely advisory — proven, not just asserted.** `BookingEngineService::quote()` only inserts a `booking_quotes` row with `held_until`/`expires_at` timers; it never calls `AssignmentService`, never touches `availability.sold`. Two quotes for the last unit both succeed (tested). `expireStaleQuotes()` (the abandoned sweep) is a plain `UPDATE ... WHERE expires_at < now()` — no inventory interaction either.
- **Inventory is occupied ONLY at book() time, through `AssignmentService` — no parallel path.** `book()` re-derives the reservation lines from the quote's snapshot and calls `AssignmentService::createBooking()` (`source=direct`), which re-checks availability under its own lock exactly as any other direct booking. A quote is a price/availability snapshot, nothing more — the lock at book time is the only thing that's authoritative. Catches `BookingException` → `status: unavailable` (graceful failure, HTTP 409); **never a fallback to force/overbook** — the Step 4 asymmetry holds (only OTA imports accept-and-flag; direct/public bookings reject when full).
- **Payment posts through the existing `FolioService`/`StripeGateway` — no bypass.** `book()` opens the folio, posts the room charge, then `recordPayment()` (which already handles the Step 6 pending/3DS mapping transparently — `client_secret` surfaces to the API response when SCA is required). The booking's own domain events (`reservation.confirmed`) drive the Step 3 `EnqueueAriPushOnInventoryChange` listener exactly as any other booking — closing the loop so a direct sale updates the OTAs outward. No new event-direction code was needed; the booking engine is a pure caller of already-built, already-proven services.
- **Public tenant resolution is real context, never a bypass.** New `ResolvePublicProperty` middleware resolves `{property}` (a plain string route param, not model-bound — avoids the Phase 1 Step 9 binding-before-middleware trap) under an explicit `runAsSuperAdmin` lookup, then sets **both** `TenantContext` and the spatie team id for the rest of the request — the same mechanism `SetTenantForWebRequest` uses for authenticated web requests, just keyed off a public slug instead of a session membership. `BookingEngineController` re-loads the property tenant-scoped from the stashed id on every action, and every quote/reservation lookup is additionally filtered by `property_id` — a request in property A's context 404s on property B's quote rather than ever reading it.
- **Rate limiting:** a new named limiter (`booking`, `RateLimiter::for` in `AppServiceProvider`), per-IP, `services.booking.rate_limit` (default 60/min, config-driven so tests can tighten it). Applied to the whole `/book/{property}/*` route group via `throttle:booking`.
- **Unstyled contract stub confirmed:** `resources/views/booking/stub.blade.php` has zero CSS classes, `<style>` tags, or stylesheet links (grepped to confirm) — plain HTML documenting the exact JSON contract (search/quote/book/confirmation) for the design pass to replace, same spirit as Phase 1 Step 9's Livewire stubs. The JSON endpoints themselves ARE the real, final backend contract (not stubbed) — only the presentation layer is a placeholder.
- **Deferred, cheaply, per the prompt:** promo codes and deposits have schema support (`booking_quotes.promo_code`/`discount_minor`, `payment_authorizations` for deposits) but no engine logic in Step 7 — not exercised by search/quote/book. Flagged here rather than half-built silently.
- **Verified:** `migrate:fresh --seed` green, full suite **74 tests / 471 assertions** (Phase 1 + Steps 1-6 + 6 new Step 7 tests in `PublicBookingEngineTest.php`), PHPStan L5 clean, Pint clean. Tests proved: search returns correct availability/prices and never another org's room_type; a quote never changes `sold` and expiring it marks `abandoned` (still no `sold` change); book+pay seats at confirm (not hold), pays via the Stripe fake through FolioService, and enqueues an outward ARI push; **two holds on the last unit both succeed, but only one book() seats — the second is cleanly `unavailable`, never overbooked**; a cross-property request 404s rather than reading/booking another org's data; the rate limiter engages (429 past the configured cap). **HONESTY: all Channex/Stripe interaction is against the in-memory fakes — no network. The live Channex staging and Stripe test-mode checkpoints remain deferred, unchanged from Steps 3/4/6.**

### 2026-07-02 — Phase 2 Step 8 close-out (staff action layer — backend + contract stubs)

- **Assembly, not rebuild — the whole point of the step.** The only genuinely new logic is one action + read queries; everything else delegates:
  - **`CheckInReservation` action (new)** — the one missing piece, mirroring the Phase 1 `CheckOutReservation` exactly: a pure `confirmed → checked_in` state transition in a transaction + `reservation_status_history` row + post-commit `reservation.checked_in` event. It does NOT go through `AssignmentService` and touches no inventory (the nights were occupied at booking time). No listener acts on the event today — it's semantic-layer/audit context.
  - **`FrontDeskService` (new, thin)** — the staff-action layer. Check-in/check-out delegate to the `CheckIn`/`CheckOut` actions; `moveRoom()` delegates to `AssignmentService::assignUnit` (the ONLY inventory writer — re-checks Level B on the target, frees the old, throws if occupied); `collectPayment()` delegates to `FolioService::openFolio` + `recordPayment` (ledger stays the single recompute point, through the configured gateway). Each method is 1-3 lines of delegation — no re-implemented inventory/payment/HK logic. The only original code is the read queries (`arrivals`/`departures`/`inHouse`), plain tenant-scoped reads over `reservations`.
  - **Housekeeping reused as-is** — the Phase 1 `HousekeepingService` + the existing Step 9 `HousekeepingBoard` stub cover the cleaning board; nothing new built.
- **Contract stubs:** added one unstyled `FrontDesk` Livewire component + `resources/views/livewire/front-desk.blade.php` (arrivals/departures/in-house tables + the action contracts documented in a comment; zero CSS). Route `/app/front-desk` added to the existing `auth`-protected `app` group. No offline-sync, no styled PWA — the design pass. The typed-model-vs-middleware rule didn't bite here (the front-desk stub takes no route-model param; it reads the first tenant-scoped property like `ReservationRack`).
- **Auth proof without a login route:** there is still no login UI (design/auth pass), so an unauthenticated request can't be asserted via a redirect (no `login` route to resolve → it would 500). Auth protection is proven structurally (`app.front-desk` gathers the `auth` middleware) plus `actingAs` 200s + org isolation — the same approach as Phase 1 Step 9's `StubViewsTest`.
- **Verified:** `migrate:fresh --seed` green, full suite **82 tests / 498 assertions** (Phase 1 + Steps 1-7 + 8 new Step 8 tests in `StaffActionsTest.php`), PHPStan L5 clean, Pint clean. Tests proved: check-in transitions state with `sold` untouched; check-out **reuses** the Phase 1 action and the departure HK task still auto-generates via the listener (proves reuse, not reimplementation); room move goes through `AssignmentService` (frees old / re-checks new) and a move onto an occupied unit is rejected (`UnitUnavailableException`, no overbook); payment collection posts through `FolioService` on both Manual and Stripe gateways (token-only, ledger recompute); arrivals/departures/in-house reads are correct and tenant-scoped (another org sees none); staff surface is auth-protected and org-isolated. **No service logic was duplicated. HONESTY: Stripe via the in-memory fake — no network; live checkpoints unchanged.**

### 2026-07-02 — Phase 2 Step 9 close-out — PHASE 2 CERTIFIED (full integrity suite & consolidation)

- **Phase 2 (Distribution & Payments) is COMPLETE and certified** — the backend + unstyled contract stubs for the channel manager, Stripe payments, public booking engine, and staff action layer. No new features or tables were added in Step 9; it hardens and certifies what Steps 0–8 built. The permanent named suite is the certification.
- **Consolidation was mostly verification, not new tests.** An audit mapped every Step 9 obligation to an already-existing explicit named test across the 18 Feature files. Coverage was complete except for **two ARI-push nuances the Step 9 prompt names explicitly** but that lacked their own named test (they were only implied by other tests). Both were added to `tests/Feature/AriPushTest.php` and prove already-built behavior (invariant #13 in PROJECT-STATE.md):
  1. **Event-keyed idempotency** — *"keys idempotency on the triggering domain event: a redelivered SAME event dedupes to one push, a DIFFERENT event does not collapse."* Redelivering the same `reservation.confirmed` `DomainEvent` through `EnqueueAriPushOnInventoryChange::handle()` recomputes the SAME key `ari_push:{channel}:{room_type}:{domain_event_id}`, so `SyncJobDispatcher::firstOrCreate` returns the existing row and nothing new dispatches (still 1 push); a genuinely different event (the cancellation) carries a new `domain_event_id` → new key → its own push (2 pushes, no collapse). This is exactly why the key is event-scoped, not range-scoped.
  2. **Reads current state at execution time** — *"pushes CURRENT availability read at execution time, not a snapshot captured at enqueue time."* Under a deferred `database` queue: book unit 1 (enqueues push J1 while availability = 1), then book unit 2 (availability now 0, enqueues J2), then run exactly ONE queued job — its snapshot reports `available = 0` (current), not 1 (enqueue-time). The `sync_jobs` payload only ever carries the room_type + date range, never an availability figure; `AriPushJob::process()` reads `availability` fresh via `AriSnapshotBuilder`. This is what makes event-keyed idempotency safe under re-ordering (last job to execute always pushes truth).
- **Honest final gate (all actually run, this session):**
  - `php artisan migrate:fresh --seed` — green (all migrations + RoleCatalog + Demo seeders).
  - `php artisan test` — **84 tests / 509 assertions, all green** on genuine MySQL `vyrobel_test` (was 82/498; +2 tests / +11 assertions = the two new ARI tests). Phase 1's core suite (double-booking, deadlock-free multi-room, tenant isolation, append-only ledger, GDPR erase, one-directional event discipline) re-asserted green — zero regression.
  - PHPStan (Larastan) **level 5** — clean, 0 errors (analyses `app`+`database`; needs `--memory-limit=1G` on this machine, an environment ceiling, not a code issue).
  - Laravel Pint — clean.
  - **Concurrency repeat-run: 5 runs of `ConcurrentBookingTest` + `DeadlockAvoidanceTest` + `CrossSystemConcurrencyTest`, all green (9 tests / 114 assertions each, ~37s/run), zero flakiness.** `CrossSystemConcurrencyTest` runs 5 internal rounds each for the direct-vs-OTA and OTA-vs-OTA races → **50 real cross-system races** across the 5 runs, plus Phase 1's true-parallel `ConcurrentBooking`/`Deadlock` every run.
  - **Horizon processing the sync queue on Redis — proven end-to-end this session:** dispatched a real `FlakySyncJob` through `SyncJobDispatcher` to the Redis `sync` queue (`vyrobel-database-queues:sync`, verified LLEN=1 while pending); started `php artisan horizon`, which picked it up (`Tests\Support\Jobs\FlakySyncJob .. RUNNING → 9.98ms DONE`); `sync_jobs` row went `pending → succeeded` (1 attempt) with one `sync_logs` line and the Redis list drained to 0; terminated Horizon cleanly. `QUEUE_CONNECTION=redis`, `REDIS_CLIENT=predis`, `Redis::ping()` = PONG.
- **Parallelism honesty (which guarantee is proven how):**
  - **TRUE OS-level parallelism** (separate PHP processes via `proc_open`, separate MySQL connections, shared future-timestamp barrier, committed rows, no `RefreshDatabase`): Phase 1 `ConcurrentBookingTest` + `DeadlockAvoidanceTest`; Phase 2 `CrossSystemConcurrencyTest` (direct-vs-OTA race, OTA-vs-OTA race, concurrent webhook redelivery leg).
  - **Real Redis transport + time-travel** (`queue:work --once` + `Carbon::setTestNow`, no real sleeps): retry/backoff/dead-letter in `SyncReliabilityTest`, `AriPushTest`, `CrossSystemConcurrencyTest`; the new execution-time-read test uses a deferred `database` queue to open a gap between enqueue and run.
  - **In-process against fakes — NO network, ever:** `FakeChannexClient` (Channex) and `FakeStripeClient` (Stripe, real HMAC scheme) back every remaining test.
- **⚠️ TWO LIVE-INTEGRATION CHECKPOINTS REMAIN OUTSTANDING — NOT verified, NOT implied done:**
  - **(a) Channex staging** — the real ARI push request shape, the inbound webhook payload shape, and the HMAC signature scheme are **documented guesses**, exercised only against `FakeChannexClient` + simulated signed payloads. The real `ChannexClient` (SDK-less REST skeleton over Laravel `Http`) has never hit a real Channex account.
  - **(b) Stripe test-mode with real keys** — the real `StripeClient` (SDK-backed) and its live webhook path are unexercised by CI; every test runs against `FakeStripeClient`. The webhook signature scheme IS Stripe's real documented HMAC scheme (not a guess), but was never validated against a live-sent webhook.
- **Deferred register carried forward (unchanged, do NOT build ad hoc):** promo codes + deposits on the booking engine (schema support in `booking_quotes.promo_code`/`discount_minor` + `payment_authorizations`, no engine logic wired); `guests.stripe_customer_id`/saved cards (Phase 3 — depends on the guest blind-index story); plus all standing Phase 1 deferrals (blind-index for encrypted guest email/phone, gap-free invoice numbering, `units.owner_id` FK → Phase 3 owners, dated out-of-order ranges, `inventory_blocks.quantity`, tenant-custom amenities, same-day-turnover housekeeping dedupe).
- **Next (Phase 2 is the gate; do NOT start either without a written, gated prompt):** EITHER the **design pass** (styled UI replacing the deliberately-unstyled booking-engine + staff/housekeeping contract stubs) OR **Phase 3** (white-label website builder, unified inbox, owner accounting + payouts, first AI agents). Decide and write that prompt the same gated way.

### 2026-07-03 — Live-integration checkpoint: STRIPE leg VALIDATED against real test-mode (Channex still outstanding)

- **Ran `docs/prompts/live-integration-checkpoint.md` Step 3 (Stripe) only.** The real, SDK-backed `StripeClient` + the real `Stripe-Signature` webhook path were exercised against a real Stripe **TEST-mode** account (Stripe CLI `stripe listen` forwarding live test webhooks to `/webhooks/stripe`), on the dev DB `vyrobel`, through the real `StripeGateway`/`FolioService`. Config-gated live via `.env` (`STRIPE_FAKE=false`, `PAYMENTS_GATEWAY=stripe`); **reverted to fakes (`true`/`manual`) at close.** CI never touches the network — `phpunit.xml` pins `STRIPE_FAKE=true`. **Channex Steps 1–2 NOT run — no staging account (see bottom).**
- **Validated live (all against real Stripe test-mode; token-only, no PAN anywhere):** (a) `pm_card_visa` charge → `succeeded`, posted through `FolioService`; (b) 3DS card → real `requires_action` + `client_secret` → mapped to a **pending** payment (not a false success); (c) authorize→capture **with a card** → `requires_capture`→`succeeded`; (d) refund → real refund `succeeded`, recorded as a NEW offsetting ledger entry (original untouched); (e) real webhooks (`payment_intent.succeeded`, `charge.refunded`) delivered + **signature-verified with the real scheme**, idempotent under redelivery (redelivery → `202 duplicate`, settled once), and an **invalid signature → HTTP 400** from the real client.
- **RECONCILIATION OF RECORD — deposit-hold null-token bug (found live, fixed all four corners).** `FolioService::authorizeDeposit()` authorized with **no** payment method (`gateway->authorize($amount, null)`). Real Stripe returns `requires_payment_method` (you cannot hold funds without a card) and the subsequent capture throws `InvalidRequestException`. `FakeStripeClient` had **masked** this by returning `requires_capture` for a null card and capturing unconditionally. Fixed:
  1. **Real path** — `authorizeDeposit(Folio, Money, ?string $cardToken = null, ?DateTimeInterface $expiresAt = null)` now takes and forwards a card token (also stored on the hold row; token-only).
  2. **Faithful fake** — `FakeStripeClient::createPaymentIntent` returns `requires_payment_method` when `$paymentMethod === null`; `capturePaymentIntent` now THROWS unless the intent is `requires_capture` — so CI would catch this class of bug going forward.
  3. **Test** — the Stripe deposit test passes `pm_card_visa` to `authorizeDeposit`.
  4. **CI pin** — `phpunit.xml` pins `PAYMENTS_GATEWAY=manual` so the suite's default gateway no longer depends on `.env` (which a live checkpoint flips to `stripe`); Stripe tests still opt in per-test via `config()`.
- **Benign differences (documented, no code change):** real intent/refund ids are `pi_…`/`re_…` vs the fake's deliberate `pi_fake_…`/`re_fake_…` marker (the gateway branches on status, never on prefix); real Stripe emits many more event types than the two we settle on (`payment_intent.created`, `charge.succeeded`, `refund.created`, …) — the controller correctly acknowledges all with a matching payment (202) and only settles on `payment_intent.succeeded`/`payment_failed`/`canceled`. The `charge.*` id branch of `StripeEvent::paymentIntentId()` (`data.object.payment_intent`) was confirmed against a real `charge.refunded` payload.
- **ONE residual, documented (NOT a code gap):** the 3DS **settle** half was proven with a **real, signature-verified `payment_intent.succeeded` webhook against a real succeeded intent**, with a local `pending` payment representing the pre-confirmation state. The only step not automatable in CI is completing the **browser 3DS challenge** itself — the code path (pending → webhook → settled → paid) is fully exercised live.
- **Honest gate (all run this session):** full suite **84 tests / 509 assertions green** on MySQL `vyrobel_test` (fakes, no network — same count, now reflecting the corrected contract); Pint clean; PHPStan L5, 0 errors. Dev DB restored to clean demo-seed state (the 5 throwaway live-run orgs + their stamped logs removed; only `vyrobel-demo` remains). No secrets committed (`.env` gitignored; only 4 code/config files + docs changed).
- **CHANNEX checkpoint REMAINS OUTSTANDING** — the real ARI push shape, inbound webhook payload shape, and HMAC scheme are still documented guesses, never network-verified. **Blocker: no Channex staging account provisioned yet** (inbound also needs a public tunnel). It is now the **single** outstanding live checkpoint; run `live-integration-checkpoint.md` Steps 1–2 when a staging account exists.

### 2026-07-03 — Phase 3 · Module 1 (Owner Accounting) Step 0 gate resolutions — APPROVED

Baseline audit green (`php artisan test` = 84 tests / 509 assertions) before any build. Schema drafted at `docs/schema/phase-3-owner-accounting.md` (proposal). The four flagged conflicts were approved as proposed, with these locks:

- **OTA commission source (the biggest gap — there was NO source column):** add **`channels.commission_pct`** (per-connection default, nullable decimal) **+ `reservations.ota_commission_minor`** (nullable per-reservation override, populated later from the real Channex booking payload). **When BOTH are absent → attribute OTA cost = 0 AND flag it "unverified / pending channel reconciliation" VISIBLY on the owner statement** (a line/note the owner sees) — never silently guessed. **Tied to the Channex live checkpoint:** real commissions backfill and the flag clears when Channex goes live. Both columns added now (Step 1) so later steps read real columns.
- **`fees.kind`** (nullable enum `cleaning`/`other`) added so **cleaning-fee attribution is deterministic**, not string-name-matched. Added now (Step 1).
- **`Money` gains `percentage()` + `allocate()`** (largest-remainder / banker's rounding) in **Step 2** — pure additions. **INVARIANT: allocated parts ALWAYS sum back to the original — no cent created or lost** (gets an explicit test in Step 2/7).
- **Cross-currency:** statements record an **operator-supplied `fx_rate` + `fx_source`** explicitly — no silent float, no auto-FX provider (an FX provider is deferred to the register).
- **Attribution reversal is WHOLE-TRANSACTION (new invariant):** a cancel/refund/modify reverses **EVERY** attributed component of that booking together (room, commission, mgmt fee, OTA cost) under the same `attribution_version` — never partial. Half-reversed owner ledgers are forbidden.
- **Attribution rule set of record (as proposed):** room revenue → owner gross; management commission (`commission_pct`) → operator; fixed mgmt fee → owner (pro-rated per interval); OTA commission → **owner by default (overridable to operator)**; city/tourism tax → pass-through (excluded from both); cleaning fee → **owner by default (overridable)**; add-ons → operator; damage/security deposit → excluded (not revenue). `management_agreements.attribution_rules` json stores **overrides only**; a missing key = the documented default. Only OTA-cost-bearer and cleaning-fee-owner are overridable — the rest are fixed/definitional.

### 2026-07-03 — Phase 3 · Module 1 Step 1 close-out (owners & management agreements)

- **`owners` built** (`BelongsToOrganization`, ULID, SoftDeletes): person/company party with encrypted PII (`email`/`phone`/`tax_id`, same discipline as guests), `payout_currency`, optional `user_id` → portal login via the Phase 1 External Owner role, `status`. Relations: `user`/`units`/`agreements`.
- **`management_agreements` built (effective-dated):** `commission_pct`, fixed `management_fee` (MoneyCast on `management_fee_minor`/`_currency`) + `management_fee_interval`, `payout_currency`/`payout_schedule`, `attribution_rules` json (**overrides only**), `effective_from`/`effective_to`, `status`. `unit_id` null = owner-wide, set = per-unit override.
  - **Effective-dated resolution (`ManagementAgreement::resolveFor($ownerId, $unitId, $date)` + `scopeEffectiveOn`):** returns the single governing agreement for a date — **most-specific-wins** (per-unit override sorts before owner-wide via `ORDER BY unit_id IS NULL`), constrained to `status=active` and `effective_from <= date <= effective_to` (null `effective_to` = open-ended). Returns null when no agreement covers the date. This is the read the Step 2 attribution engine will call per-night.
  - `rule($key, $default)` helper reads a single override, falling back to the engine-supplied default (the defaults live in code + DECISIONS.md, not copied into every row).
- **Phase 1 hook CLOSED:** `units.owner_id` is now a real FK → `owners` (`nullOnDelete`, stays nullable — hotel rooms have no owner). Added `Unit::owner()` relation.
- **Contract additions folded in now (per Step 0 lock) so later steps read real columns:** `channels.commission_pct` (nullable decimal(5,2); Channel model fillable+cast) and `fees.kind` (nullable enum `cleaning`/`other`, default `other`; Fee model fillable). No behavior wired yet — these are read by the Step 2 attribution engine.
- **Morph map:** registered `owner` + `management_agreement`. **PHPStan L5 note:** Larastan doesn't infer the `array` cast on a json column, so `ManagementAgreement` carries an explicit `@property array<string,mixed>|null $attribution_rules` (same pattern as the Step 4 overbooked-enum `@property`).
- **Verified (all actually run):** `migrate:fresh --seed` green (5 new migrations + RoleCatalog + Demo seeders); full suite **84 tests / 509 assertions green** on MySQL `vyrobel_test` (zero regression — no new tests this step, tables/FKs added without breaking Phase 1/2); PHPStan L5 **0 errors** (`--memory-limit=1G`); Pint clean. **Live-DB smoke check (9 assertions, all PASS, throwaway orgs force-deleted after):** (1) a unit assigned to an owner via the closed FK reloads with the relation intact; (2a–d) effective-dated resolution returns 10% on a 2025 date, 15% on a 2026 date (term change), 20% for the per-unit override (most-specific wins), and null before any agreement; (3a–b) owners are tenant-scoped (Org A cannot see Org B's owner; sees exactly its own); (3c) both models are morph-mapped. Dev DB left at clean demo state (0 owner rows). **No attribution/statement/payout logic built — that is Step 2+.**

### 2026-07-03 — Phase 3 · Module 1 Step 2 close-out (revenue attribution engine — the crux)

- **`Money::percentage()` + `Money::allocate()` (pure additions).** `allocate($weights)` uses **largest-remainder** distribution so parts **ALWAYS sum back to the original exactly — no minor unit created or lost** (sign preserved for negative totals/reversals). Proven by `tests/Unit/MoneyAllocateTest.php` including a **2000-case deterministic fuzz** over random amounts/weights. `percentage()` is a single rounded portion (half-up); exact complementary splits use `allocate([bp, 10000-bp])`.
- **`owner_ledger` — append-only source of truth.** Each attributed component is its own immutable line (`entry_type` ∈ room_revenue/commission/management_fee/ota_cost/cleaning/addon/expense/adjustment; `direction` credit/debit; `amount_minor` always positive, direction carries the sign; `service_date` for per-night; `attribution_version`; `unverified` flag + `note`; `meta` json audit detail). **No SoftDeletes.** Idempotency is **DB-enforced** by a unique `idempotency_key` (`{rid}:{unit}:{entry_type}:v{ver}` for originals, `rev:{origId}` for reversals, `fee:{agr}:{start}:{end}` for fees) — re-running attribution never double-posts, and a reversal never collides with its original. `statement_id` is a nullable forward hook for Step 4 (no FK target yet). Model `OwnerLedgerEntry` (table `owner_ledger`), morph-mapped `owner_ledger`.
- **`OwnerAttributionService` — READ-ONLY over operational data** (reads `folio_items` + `reservation_units`; never mutates folios/reservations/inventory/availability — asserted). Rule set of record applied:
  - **Room revenue** = the authoritative folio room total (Σ `folio_items` type=room, since `BookingEngineService` posts room as ONE lump line), **allocated across active `reservation_unit` lines** — weighting by `price_minor` when all are priced, else by **night count**. This makes multi-unit *and* mid-stay-move attribution per-night correct, and is robust to `split()` zeroing the moved-out segment's price (a mid-stay move = `split()`+`assignUnit()`). Owner + operator shares reconcile to the folio room revenue **to the cent** (via `allocate`).
  - **Commission** = `allocate(room, [pctBp, 10000-pctBp])` → operator share is the exact complement of owner net. **OTA cost** = `reservations.ota_commission_minor` → else `channels.commission_pct` → else **0 flagged `unverified`/`ota_unverified`** (visible, never guessed); owner-borne by default, overridable to operator. **Cleaning** (`fees.kind='cleaning'`) → owner credit by default, overridable. **Add-ons** → operator by default (no owner line), overridable to owner. **Tax** (pass-through) and **deposits** (holds, not folio revenue) → excluded from both sides. **Null governing agreement on a covered date → attribute NOTHING + post one explicit `adjustment` flag line** (`unverified`, note `no_agreement`).
  - **Contract input added now:** `reservations.ota_commission_minor` (nullable; the per-Step-0-lock per-reservation override, populated later from the real Channex payload).
- **WHOLE-TRANSACTION correct-by-reversal (invariant):** `reverseReservation()` reverses **EVERY** component of the booking's live version together in one transaction — a mirror line per original (`reverses_id` set, direction flipped, same amount) — never partial, never editing originals; the booking nets to zero; idempotent. `reattributeReservation()` reverses the live version wholesale and posts a fresh version (numbers reproducible per version). `attributeManagementFee()` pro-rates a fixed fee to the period (idempotent on the period key).
- **Verified (all actually run):** `migrate:fresh --seed` green (2 new migrations: `owner_ledger`, `reservations.ota_commission_minor`); full suite **113 tests / 2597 assertions green** on MySQL `vyrobel_test` (was 84/509 → +15 `MoneyAllocateTest` incl. fuzz, +14 `OwnerAttributionTest`; **zero regression**); PHPStan L5 **0 errors**; Pint clean. Attribution tests prove every mandated scenario: room+commission exact complementarity; OTA override→channel-pct→0-unverified; cleaning/add-on rules+overrides; tax+deposit excluded; multi-unit split-to-owners; mid-stay move per-night; whole-transaction reversal (mirror per component, nets to zero, idempotent) + re-attribution; null-agreement flag; idempotency + read-only. **Worked example (one OTA booking, 15% mgmt commission, 12% channel):** room_revenue +30000 · commission −4500 · ota_cost −3600 · cleaning +4500 → **owner net 26400**; room credit == folio room (30000); owner_share (25500) + operator_share (4500) == 30000; city tax (600) excluded. **No attribution touches folios/inventory. Statements/payouts are Step 4/5 — not built.**

### 2026-07-03 — Phase 3 · Module 1 Step 3 close-out (owner-attributable expenses)

- **`owner_expenses` built** (`BelongsToOrganization`, ULID, SoftDeletes): `owner_id` (nullable — resolved from the unit), `unit_id`, `category` (cleaning/maintenance/other), `amount` (MoneyCast), `expense_date`, `deductible` (nullable — resolved per agreement), polymorphic **`source`** (reuse hook), `attributed_ledger_id`, `flag` (no_owner/no_agreement), `voided_at`. Morph-mapped `owner_expense`.
- **Reuse, not duplicate:** `source` is a polymorphic pointer to the EXISTING record the cost came from — cleaning links to a real **`housekeeping_task`**; a manual expense leaves it null. **No maintenance-ticket module exists yet** (master-plan lists `MaintenanceTicket` as a later entity), so maintenance expenses attribute via their own `unit_id` and the `source` hook is ready for that future module (the test links a maintenance expense's source to the `unit` record — no parallel cost log invented, nothing faked).
- **`OwnerExpenseService`:** `recordExpense()` resolves owner (from unit), the governing agreement (`resolveFor` on the expense date), and **deductibility** — deductible unless the agreement's `non_deductible_categories` rule excludes the category. A deductible expense posts a `owner_ledger` **deduction line** (`entry_type=expense`, debit) immediately, idempotent on `expense:{id}`. A **non-deductible** expense is recorded with `deductible=false` and posts **NO** ledger line (explicit operator cost, not omission). An expense on an **owner-less unit or with no governing agreement** is recorded and **flagged** (`no_owner`/`no_agreement`) with no ledger line — never silently attributed.
- **Whole-transaction reversal reused for corrections:** `voidExpense()` reverses the expense's ledger line (a new mirror row, `reverses_id` set, `rev:{origId}` key) and marks `voided_at` — append-only, never edits the original, idempotent; the owner nets back to zero.
- **Verified (all actually run):** `migrate:fresh --seed` green (1 new migration); full suite **120 tests / 2632 assertions green** (was 113/2597 → +7 `OwnerExpenseTest`; **zero regression**); PHPStan L5 **0 errors**; Pint clean. Also extracted the shared owner test helpers into `tests/Support/owner_helpers.php` (required from `Pest.php`), so owner tests don't depend on cross-file function-load order (same discipline as `channel_helpers.php`). Tests prove: deductible cleaning-from-housekeeping-task posts one deduction to the right owner and drops net by exactly the amount; maintenance expense attributes to the correct owner/unit via its source; non-deductible expense recorded with NO ledger line; void reverses append-only + nets to zero + idempotent; owner-less/no-agreement recorded + flagged, not attributed; posting idempotent. **Worked example:** a deductible €65.00 cleaning expense (sourced from a real `housekeeping_task`) → one `expense` debit line → owner net drops by exactly 6500. **Statements/payouts are Step 4/5 — not built.**

### 2026-07-03 — Phase 3 · Module 1 Step 4 close-out (owner statements — period-locked, append-only projection)

- **`owner_statements` + `owner_statement_lines` built** (morph-mapped `owner_statement`/`owner_statement_line`). A statement is a **PURE PROJECTION** of `owner_ledger`: breakdown caches (gross/other_revenue/commission/management_fee/expenses/ota_cost/adjustments/net_source, all signed, in `source_currency`) + presentation lines (gross → other → commission → mgmt fee → expenses → OTA → prior-period → **net**). **Component lines sum EXACTLY to `net_source_minor` == the ledger net for the period, to the cent.** Closed the Step 2 forward hook: `owner_ledger.statement_id` FK → `owner_statements`.
- **`OwnerStatementService`:** `generateDraft()` recomputes numbers + lines from the ledger without stamping — **idempotent/reproducible** (re-running a draft yields identical numbers); throws if the period is already locked. `finalize()` (in a transaction) **STAMPS** `owner_ledger.statement_id` on every claimed line, recomputes, sets `locked=true`/`status=finalized`, and assigns a per-owner **gap-free `sequence_no`**. A locked statement is immutable — regenerating throws.
- **Period locking + gap-free carry-forward (the crux):** a statement **claims every UNSTAMPED ledger line with `service_date <= period_end`**. Locked-period lines are already stamped, so the only unstamped lines a later statement picks up are its own period's lines + any **post-lock stragglers** dated into a sealed period → surfaced as clearly-labeled **prior-period adjustments** (bucketed by the explicit `prior_period_adjustment` flag, or `service_date < period_start`). **Every ledger line is claimed by exactly one statement** (statements finalized in period order).
- **Explicit lock boundary (not by convention):** new `App\Services\PeriodLockGuard` (`lockedThrough`/`isSealed`). `OwnerAttributionService` and `OwnerExpenseService` now consult it — any ledger line posted with a `service_date` inside a locked period is marked `prior_period_adjustment=true` (keeps its true date, carries forward). `owner_ledger.prior_period_adjustment` column added. When nothing is locked, `isSealed` is false → **zero behavior change to Steps 2/3** (suite confirms).
- **Cross-currency:** when payout currency ≠ ledger currency, an **operator-supplied `fx_rate` + `fx_source`** are recorded on the statement and `net_payable_minor` = `round(net_source × fx_rate)` (single rounding, no silent float, no auto-FX provider — deferred). Same-currency path: `fx_rate` null, `net_payable == net_source`.
- **Unverified OTA lines surface** on the statement as a visible `ota_cost` line flagged `unverified` (kept even when the amount is 0/pending), plus `owner_statements.has_unverified`.
- **Verified (all actually run):** `migrate:fresh --seed` green (3 new migrations); full suite **126 tests / 2675 assertions green** (was 120/2632 → +6 `OwnerStatementTest`; **zero regression**); PHPStan L5 **0 errors**; Pint clean. Tests prove: projection reconciles (net == Σ lines == ledger net); draft idempotent + finalize locks/stamps + regenerate-locked throws; **post-lock refund does NOT touch the locked statement, lands on the next statement as a prior-period adjustment, gap-free (every line claimed once)**; cross-currency records fx + converts net, same-currency unaffected; unverified OTA shows pending; owner isolation (a statement projects only its owner's ledger). **Worked example:** September finalized/locked at net 25500; a post-lock refund left it UNCHANGED and carried forward onto October as a −25500 prior-period adjustment; 0 unclaimed lines. **Payouts are Step 5 — not built.**

### 2026-07-03 — Phase 3 · Module 1 Step 5 close-out (payouts — calculation + lifecycle; money rail deferred)

- **`payouts` built** (`BelongsToOrganization`, ULID; morph-mapped `payout`): owner, source `owner_statement`, Money `amount` in the owner's payout currency, `rail` (manual/stripe_connect), `status` (pending/paid/failed/reversed), nullable `external_ref`/`connect_account_id` (deferred), `supersedes_id` (append-only chain), `failure_reason`, `paid_at`/`failed_at`, `initiated_by`.
- **`PayoutRail` interface + `PayoutResult`** (mirrors the Phase 1 PaymentGateway seam) + **`ManualPayoutRail`** (records an offline bank transfer as paid). **`StripeConnectPayoutRail` is a config-gated SKELETON whose `send()` THROWS** — real Connect (connected-account onboarding + KYC) is a separate deferred live checkpoint; never bound/exercised in tests. Config-gated binding: `services.payouts.rail` (default `manual`) → `PayoutRail` (same pattern as `payments.gateway`).
- **`OwnerPayoutService`:** `createFromStatement()` — **finalized-only** (throws on a draft), idempotent (returns an existing pending/paid payout), creates a pending payout for a **positive** net. `pay()` disburses through the rail → on success marks the payout `paid` + **flips the statement to `paid`**; on failure marks it `failed`. `retry()` of a failed payout is a **NEW** row (`supersedes_id`), the failed row preserved. `reverse()` of a paid payout is a NEW `reversed` row + statement back to `finalized`. All transitions emit `domain_events` + `audit_events` — **append-only, history always reconstructable**.
- **Negative-net behaviour (chosen + documented):** a statement's net is period activity. **Positive → payout; zero → nothing owed, no payout; negative → NO payout and NEVER a reverse-charge** — the negative balance is **carried forward** as a single `adjustment` ledger line (`note=balance_carried_forward`, `prior_period_adjustment=true`) dated into the next period, so it offsets a future positive payout (standard "balance brought forward"). This is why the Step 4 post-lock-refund period (net −25500) doesn't charge the owner — it reduces the next payout instead.
- **Verified (all actually run):** `migrate:fresh --seed` green (1 new migration); full suite **132 tests / 2709 assertions green** (was 126/2675 → +6 `OwnerPayoutTest`; **zero regression**); PHPStan L5 **0 errors**; Pint clean. Tests prove: payout from a finalized statement for the exact net in the owner's currency (draft refused); manual rail pays + flips statement to paid; **zero/negative net → no positive payout, negative carries forward** (asserted the carry line + date + sign; a fresh activity-free owner produces neither payout nor carry); **append-only** failed+retry = two records with a reconstructable `supersedes_id` chain, original never edited away; Stripe Connect rail config-gated + **not** the active rail; owner isolation on payouts. **Worked example:** finalized statement net 25500 EUR → manual payout (amount/currency/rail) → paid → statement flipped to paid; reconciles.
- **Deferred register (add):** **Stripe Connect payout rail** — real owner disbursement (connected-account onboarding + KYC) is an unverified live checkpoint, built here only as a throwing config-gated skeleton behind `PayoutRail`. Joins the Channex staging checkpoint as an outstanding live-integration item.
- **Next: Step 6** — owner portal reads + the owner-isolation security boundary (unstyled contract stubs). Not started.

### 2026-07-03 — Phase 3 · Module 1 Step 6 close-out (owner portal reads + owner isolation — the new security boundary)

- **Owner isolation is a NEW access boundary, built + tested like tenant isolation.** `App\Support\Owner\OwnerContext` (singleton, mirrors `TenantContext`): a row-level scope WITHIN a tenant. **Inactive by default** — operator/manager/CLI/queue/tests see all owners (subject to tenant scope); activated ONLY on the portal.
- **`OwnerScope` (global scope) + `BelongsToOwner` trait** applied to `Unit`, `OwnerStatement`, `OwnerStatementLine`, `Payout`, `OwnerLedgerEntry`, `OwnerExpense`. Behaviour: inactive → no constraint; active with ids → `owner_id IN (ids)`; **active but EMPTY → FAIL CLOSED (`1 = 0`, nothing) — never falls open to all owners.** Added `owner_statement_lines.owner_id` (denormalized from the parent, set in `writeLines`) so lines scope directly (defense-in-depth).
- **`SetOwnerScopeForPortal` middleware** runs AFTER `SetTenantForWebRequest`: resolves the owner(s) the authed user links to within the active org (`owners.user_id`) and activates the scope; **always activates on the portal** (a non-linked user → empty set → fails closed), and **deactivates on `terminate()`** so it can never leak into a later operator request. `OwnerContext` registered as a singleton.
- **Owner portal stubs (unstyled, contract-only, like Phase 1 Step 9):** `App\Livewire\Owner\OwnerDashboard` (`/owner` — the owner's units + occupancy via `AvailabilityService`, statements, payouts, net totals) and `OwnerStatementView` (`/owner/statements/{statement}` — statement + line breakdown). Reads assemble through `OwnerPortalService` over the owner-scoped models. **RMB rule honoured:** the statement id is a plain string prop resolved owner+tenant-scoped in `render()` (never a typed route-model-bound prop) → a cross-owner id 404s. Routes: `auth` + `SetOwnerScopeForPortal`. No CSS/design system (grep-confirmed); no login UI (auth via `actingAs`).
- **Verified (all actually run):** `migrate:fresh --seed` green (1 new migration); full suite **139 tests / 2747 assertions green** (was 132/2709 → +7 `OwnerPortalIsolationTest`; **zero regression** — the `OwnerScope` on `Unit`/owner models is inert off-portal, so operator/service code is untouched); PHPStan L5 **0 errors**; Pint clean. Security tests prove: owner A sees only A's units/statements/lines/payouts/ledger/expenses and B's specific rows resolve to null (not just absent from a list); **fail-closed** empty scope → 0 on every model; a non-linked portal user sees "No units/No statements" (not everything); a cross-owner statement 404s; an owner's view is a strict subset of the operator aggregate (never the org-wide total); the operator side still sees all owners; owner scope composes with tenant scope (org X owner ∧ org Y tenant → empty; cross-org statement 404 both directions).
- **OWNER ISOLATION FAILS CLOSED — stated explicitly and demonstrated:** with the scope active but the owner set empty, every owner-facing query returns **nothing** (operator saw 3 units; owner A saw 1; empty scope saw 0; operator view restored after). An empty/missing scope never falls open to all owners.
- **Next: Step 7** — full integrity suite & consolidation (owner-isolation re-asserted with the tenant suite; attribution rule set + guarantees restated; deferred Connect checkpoint in the register). Not started.

### 2026-07-03 — Phase 3 · Module 1 Step 7 — OWNER ACCOUNTING MODULE CERTIFIED (full integrity suite & consolidation)

- **Phase 3 · Module 1 (Owner Accounting & Payouts) is COMPLETE and certified** — backend + unstyled owner-portal contract stubs. No new features/tables in Step 7; it consolidates and certifies Steps 0–6. **Coverage audit: every module obligation maps to an explicit, passing NAMED test** — 45 owner-accounting tests across 6 files: `tests/Unit/MoneyAllocateTest.php` (5) + `tests/Feature/` `OwnerAttributionTest`(14) · `OwnerExpenseTest`(7) · `OwnerStatementTest`(6) · `OwnerPayoutTest`(6) · `OwnerPortalIsolationTest`(7).
- **Attribution rule set OF RECORD (certified):** room revenue → owner gross; management commission (`commission_pct`) → operator (exact complement via `Money::allocate`); fixed management fee → owner, pro-rated per interval; OTA commission → **owner by default, overridable to operator**; city/tourism tax → pass-through (excluded from both); cleaning fee (`fees.kind='cleaning'`) → **owner by default, overridable**; add-ons → operator by default, overridable; damage/deposit → excluded. OTA-cost source: `reservations.ota_commission_minor` → `channels.commission_pct` → **0 flagged unverified (visible-pending)**. Only OTA-cost-bearer + cleaning-fee-bearer are overridable (`management_agreements.attribution_rules`, overrides-only).
- **Guarantees certified (the module's invariants — added to PROJECT-STATE.md):**
  - **Attribution is derived + read-only**: reads `folio_items` + `reservation_units`, never mutates folios/reservations/inventory; owner_share + operator_share reconciles to the folio room revenue **to the cent**; multi-unit splits per unit, mid-stay moves attribute per-night.
  - **`owner_ledger` is append-only, correct-by-reversal, WHOLE-TRANSACTION**: a cancel/refund/modify reverses EVERY component of a booking together (mirror rows, `reverses_id`), never partial, never edits originals; nets to zero; DB-idempotent (`idempotency_key`). `Money::allocate` is sum-preserving (fuzz-proven — no cent created/lost).
  - **Owner statements are a pure projection, period-locked**: net == Σ component lines == ledger net for the period; a draft is reproducible; finalize stamps `owner_ledger.statement_id` + locks; a locked statement is immutable (regenerate throws); a post-lock adjustment carries forward onto the next statement as a labeled prior-period adjustment — **gap-free (every ledger line claimed by exactly one statement)**. Cross-currency records an operator-supplied `fx_rate`/`fx_source`; unverified OTA surfaces visibly.
  - **Payouts**: created only from a finalized statement's positive net (in the owner's payout currency); manual rail marks the statement paid; negative net carries forward (never a reverse-charge), zero net → nothing; append-only lifecycle (retry/reversal = new records, `supersedes_id`).
  - **OWNER ISOLATION (new access boundary, treated like tenant isolation)**: a row-level scope WITHIN a tenant. **Fails closed** (active + empty owner set → nothing, never all owners); an owner reads only their own units/statements/lines/payouts/ledger/expenses (another owner's specific rows resolve to null); a non-linked portal user gets nothing; an owner's view is a strict subset of the operator aggregate; the operator side is unaffected (scope inert off-portal, deactivated on `terminate`); **composes with tenant scope** (both boundaries hold; cross-org 404 both directions).
- **HONESTY BOUNDARY — what's proven-against-reality vs deferred:** the **accounting is fully real and tested** — attribution, the append-only ledger, expenses, period-locked statements, payout calculation + lifecycle all run against genuine MySQL with named integrity tests (no fakes, no network needed). **Only the Stripe Connect money RAIL is deferred** — `StripeConnectPayoutRail` is a config-gated skeleton whose `send()` throws; the manual rail is the real, tested disbursement path. Owner disbursement to a real bank/Connect account is the single deferred piece of THIS module.
- **Honest module gate (all actually run this session):** `php artisan migrate:fresh --seed` green; `php artisan test` → **139 tests / 2747 assertions, all green** on MySQL `vyrobel_test` (Phase 1's 21 + Phase 2's 63 + Owner Accounting's 45 + the Money unit tests; **zero regression** to any prior guarantee); PHPStan (Larastan) **level 5, 0 errors** (`--memory-limit=1G`); Pint clean (whole codebase). **Concurrency repeat-run: 3× `ConcurrentBookingTest` + `DeadlockAvoidanceTest` + `CrossSystemConcurrencyTest` — 9 tests / 114 assertions each, ~36.7s/run, ZERO flakiness** (Phase 1 true-parallel double-booking + deadlock-free + Phase 2 cross-system direct-vs-OTA / OTA-vs-OTA races, 5 internal rounds each → 30 real races across the runs). Phase 1/2 core (double-booking, folio ledger, tenant isolation, event discipline, cross-system concurrency) all re-asserted green with the owner-accounting layer added.
- **Deferred register (carried forward — do NOT build ad hoc):**
  - **Stripe Connect payout rail** — real money movement to owners (connected-account onboarding + KYC): a separate credentialed live checkpoint, like the Stripe payments one. Joins the Channex staging checkpoint as an outstanding live-integration item.
  - **Auto-FX provider** — statements take an operator-supplied `fx_rate`/`fx_source`; no automatic FX feed (deliberate — no silent float).
  - **Maintenance-ticket source** for maintenance expenses — no maintenance module exists yet; the polymorphic `owner_expenses.source` hook is ready for it (master-plan `MaintenanceTicket`). Maintenance expenses are manual / unit-sourced until then.
  - **`UnitFactory` test-infra debt** — `UnitFactory` hard-codes its own org (not tenant-context-aware like `ChannelFactory`/`OwnerFactory`); owner tests pass `organization_id`/`unit_id` explicitly. Fix the shared factory in a dedicated pass, not mid-module.
  - Plus all standing Phase 1/2 deferrals (Channex staging live checkpoint; blind-index guest matching; gap-free invoice numbering; dated out-of-order ranges; `inventory_blocks.quantity`; tenant-custom amenities; same-day-turnover housekeeping dedupe; promo codes/deposits on the booking engine; `guests.stripe_customer_id`/saved cards).
- **NEXT (do NOT start without a written, gated prompt):** the next **Phase 3 module — Unified Inbox** (WhatsApp/email/OTA/SMS); OR close the **Channex staging live checkpoint**; OR a **thin design slice** to demo the wedge (styled owner statement/portal over the certified backend). Decide and write that prompt the same gated way.

### 2026-07-11 — DESIGN PASS · honesty rule locked (Owner Statement / Screen 1 shipped)

- **Design pass underway (presentation only — NO backend/schema/guarantee changes).** Screen 1, the
  styled **Owner Statement** (`OwnerStatementView` `/owner/statements/{id}`), is live on real owner-scoped
  data (commit `970b0e7`): the approved Claude Design ported onto the stub via a new reusable owner-portal
  layout (`components/layouts/owner` — formalized tokens: frosted glass blur(22px)/saturate(1.15), warm
  layered shadow, terracotta `#C2410C`, Sora/Inter, real vyFadeUp/vyShimmer/vyDrift/vySwap keyframes).
  Suite 139→141 tests / 2769 assertions green, PHPStan L5 + Pint clean; the stub layout
  (`components/layouts/app`) is untouched → other role stubs unaffected.
- **DESIGN-PASS HONESTY RULE (locked — all owner/operator/guest screens):** never render a financial line
  or figure the backend does not compute. Triage every design element into one bucket and handle it so:
  - **REAL** → wire live through the owner-scoped read services (`OwnerPortalService` etc. — called, never modified).
  - **DERIVED** → compute from REAL figures only (e.g. a "% of gross" sublabel from the statement's own numbers); never invent or persist a derived value.
  - **DEFERRED** → visually stubbed + flagged **"not yet available"** (e.g. by-unit split, occupancy — aggregations not built); never fabricated.
  - **NO-BACKEND** → a disabled **"Soon"** affordance; never a fake action / IBAN / date / download.
  - **Money reconciles to the cent** against the source ledger/statement; **owner isolation must hold** (RMB rule: string id → owner+tenant scoped `findOrFail` in `render()`; cross-owner → 404).
  - **Guest PII is never exposed** on owner surfaces (encrypted `guests.email`/`phone`; e.g. no guest names on owner "upcoming stays").
  - Established + proven by Screen 1 (the invented "card-processing fee" line was OMITTED — a real expense/`ota_cost` line fills that slot when present, labelled truthfully). Enforced per-screen by a render/isolation test.

### 2026-07-11 — DESIGN PASS · owner portal COMPLETE (4 screens styled + live)

- **All 4 owner-portal screens are styled + wired to real owner-scoped data** (presentation only — no
  backend/schema/guarantee change): Owner Statement (`970b0e7`), Dashboard (`8f76dbd`), Payouts
  (`a8f7b77`), My Units (`bff8e05`). Shared foundation: `components/layouts/owner` (design system),
  `<x-owner.shell>` (sidebar + topbar, extracted at the 2nd screen), and the
  `App\Livewire\Owner\Concerns\FormatsOwnerData` trait. Each screen carries a per-screen render/isolation
  test enforcing the honesty rule; owner isolation (fail-closed) holds; guest PII never exposed; money
  reconciles to the cent. My Units additionally proves **owner-scoped occupancy** (excludes non-owner
  units in the same room type — the room-type-wide `availabilityRange` trap is avoided).

### 2026-07-12 — DESIGN PASS · operator screens 1–2 styled + live (operator design system extracted)

- **Operator design pass begun; a dedicated operator design system was extracted** (presentation only —
  NO change to AssignmentService / AvailabilityService / FolioService / CheckIn·CheckOut actions /
  HousekeepingService / schema / any guarantee). Mirrors the owner-portal foundation so the two role
  surfaces stay visually one system while staying independent:
  - `resources/views/components/layouts/operator.blade.php` — a **dedicated, self-contained** operator
    layout carrying the SAME formalized Vyrobel tokens as the owner layout (frosted glass blur(22px)/
    saturate(1.15), terracotta `#C2410C`, Sora/Inter, real `vyFadeUp`/`vyShimmer`/`vyDrift` keyframes) +
    screen-specific CSS (rack timeline grid; front-desk rows/tags/summary). Tokens are **copied, not
    shared**, so the certified owner layout is never edited by an operator change — the same
    "dedicated layout per surface" decision the owner layout already records.
  - `resources/views/components/operator/shell.blade.php` — `<x-operator.shell>` (grouped sidebar +
    topbar), extracted on the FIRST operator screen exactly as `<x-owner.shell>` was, so every later
    operator screen inherits ONE chrome. Nav honesty: only real, built routes link; the rest are
    non-interactive "Soon" placeholders.
  - `App\Livewire\Operator\Concerns\FormatsOperatorData` — the operator mirror of `FormatsOwnerData`
    (viewer chip + `money()` + **warm-only** status/source labels whose keys are EXACTLY the real
    reservation `status` enum — nothing invented).
- **Operator screens are TENANT-scoped** (`BelongsToOrganization`), NOT owner-scoped — the operator
  legitimately sees all of the org's data, and guest NAMES are shown (as a front desk does); encrypted
  `guests.email`/`phone` are still never rendered.
- **Screen 1 — Operator Calendar → `ReservationRack` (`/app/rack`)** (`0e1ccaa`): REAL date window
  (GET-link nav, RMB-safe), per-night availability (`AvailabilityService`), and booking bars from active
  `reservation_unit` lines (real guest/status/source/dates) packed into non-overlapping lanes. Warm
  status tones only. DEFERRED: drag-to-reschedule (mutates inventory via AssignmentService) — labelled,
  never a broken interaction; **overbooked lines excluded** (they hold no inventory). NO-BACKEND: new/
  filter/export "Soon". `ReservationUnit::reservation()` gained the generic relation annotation its
  sibling relations already carried (docblock only).
- **Screen 2 — Operator Front Desk → `FrontDesk` (`/app/front-desk`)** (`39724e6`): REAL arrivals/
  departures/in-house (`FrontDeskService`) with real guest/status/unit-or-"Unassigned"/source/nights/
  guests and the **real folio balance** (`balance_minor`, FolioService-maintained), outstanding balances
  terracotta-flagged. **Check-in / check-out are WIRED and persist** via a NEW pattern reused for every
  operator mutation from here: **POST → controller → existing service action → PRG redirect** (
  `App\Http\Controllers\FrontDeskActionController` → `FrontDeskService` → the Phase 2 Step 8 actions).
  Deliberately NOT a Livewire wire-action — a POST to a normal route re-runs the web middleware so the
  mutation is tenant-scoped; `{reservation}` is a **string** param resolved via tenant-scoped
  `findOrFail` (never RMB) → cross-org 404, mutates nothing. Guarded to sane transitions. DEFERRED:
  "Collect payment" (needs amount + card-token + gateway flow) shown real-looking but flagged — never
  fakes a payment; "Assign unit" links to the rack (the real assignment surface). NO-BACKEND: walk-in/
  filters/print "Soon".
- **Honesty rule (2026-07-11) now enforced across owner + operator** by a per-screen render/isolation
  test. **Verification (all actually run):** full suite **155 tests / 2860 assertions green** on MySQL
  `vyrobel_test` (owner 141 baseline + rack 4 + front-desk 4 vs the 139 pre-design-pass); PHPStan L5 0
  errors; Pint clean. New tests: `OperatorRackViewTest` (render + real window filtering + no-invented-
  status + isolation + PII) and `OperatorFrontDeskViewTest` (render + real balance flag + no-invented-
  status + isolation + **check-in persists through the real action** + cross-org check-in 404s + PII).
- **NEXT: continue the operator screens** — `Operator Housekeeping` (`/app/housekeeping`), then
  `Operator Reservation Detail` (`/app/reservations/{id}`), then `Operator Dashboard`; then the public
  booking engine. Same discipline (inherit the operator system; honesty rule; RMB/PRG for mutations;
  per-gate commit + per-screen test). Other tracks (Unified Inbox module, Channex/Stripe-Connect live
  checkpoints) remain available but are NOT the chosen next step.

### 2026-07-12 — DESIGN PASS · operator Screens 3–4 styled + live (housekeeping, reservation detail)

- **Two more operator screens styled + live** (presentation only — no change to HousekeepingService /
  FolioService / AssignmentService / the append-only ledger / schema / any guarantee), both inheriting
  the operator design system + the operator-mutation pattern from Screens 1–2:
  - **Screen 3 — Operator Housekeeping → `HousekeepingBoard` (`/app/housekeeping`)** (`d715496`): a
    cleaning **kanban** whose **4 columns ARE the real `housekeeping_tasks.status` enum**
    (pending/in_progress/clean/inspected — no invented columns). Per card: real unit, turnover `type`,
    `priority`, `date`, assigned housekeeper (or "Unassigned"). Warm tones only. **Status advance
    (Start / Mark clean / Mark inspected) is WIRED + persists** via POST → `HousekeepingActionController`
    → `HousekeepingService::updateTaskStatus` → PRG — including the unit-cleanliness sync on clean/
    inspected; only real-enum targets accepted (422 otherwise). **DEFERRED (not faked):** drag-to-move
    (offered as the safe POST status button instead — no drag that doesn't persist); **assign-housekeeper
    — there is NO `HousekeepingService` method for it, so the assignee is DISPLAYED but "Assign" is a
    flagged "Soon"** (never a faked assignment).
  - **Screen 4 — Operator Reservation Detail → `ReservationDetail` (`/app/reservations/{id}`)**
    (`b49676d`): RMB-safe (string id → tenant-scoped `findOrFail` in `render()`). **The folio is rendered
    as the real APPEND-ONLY LEDGER** — every `folio_item` + `payment` its own chronological line (charges
    +, payments −, refunds their OWN offsetting line; a prior line is never edited/struck). **Totals are
    derived from the same rows and RECONCILE to the folio's cached figures to the cent.** **No line the
    ledger doesn't contain is rendered** (the owner-statement fake-fee precedent — proven by an absent-
    fabricated-fee assertion). A deposit/pre-auth line shows ONLY when a real `deposit_hold` exists.
    **Money actions — WIRED + persist APPEND-ONLY** via POST → `ReservationFolioActionController` →
    `FolioService` → PRG: **Record payment** (through the configured gateway; manual = offline) and
    **full Refund** per settled payment (a NEW offsetting `refund` row; the original is never touched —
    proven). The refund's target payment is scoped to the reservation (cross-org 404). **DEFERRED (not
    faked):** add charge, partial/custom refund, void, capture-hold, move room (guarded AssignmentService),
    modify/cancel, export/print.
- **Operator design pass now = 4 screens** (Calendar/Rack, Front Desk, Housekeeping, Reservation Detail),
  all on the shared operator system, honesty rule enforced per-screen. **Verification (all actually run):**
  full suite **163 tests / 2910 assertions green** on MySQL `vyrobel_test` (+4 `OperatorHousekeepingViewTest`,
  +4 `OperatorReservationDetailViewTest` vs the 155 after Screens 1–2); PHPStan L5 0 errors; Pint clean.
  `StubViewsTest` (housekeeping) updated to the styled type label; `StaffActionsTest` unchanged + green.
- **NEXT: `Operator Dashboard`** (new page over certified data), then the public booking engine
  (`/book/{property}`), then `/app/units`.

### 2026-07-12 — DESIGN PASS · Operator Dashboard styled + live — OPERATOR CORE COMPLETE

- **Screen 5 — Operator Dashboard → `App\Livewire\Operator\OperatorDashboard` (`/app/dashboard`)**
  (`434e1a9`): a **NEW tenant-scoped page** (no prior stub/route — like the owner Payouts/Units screens),
  on the shared operator design system. Read-only; presentation + thin page only — no service/schema/
  guarantee change. This **completes the operator core** (Screens 1–5).
- **Every KPI is a TRUE org figure** — the metrics-screen risk is a plausible-but-wrong number (the owner
  room-type-wide occupancy trap). Handling per the honesty rule:
  - **REAL:** occupancy tonight % + rooms sold/available — **summed ORG-WIDE from the real `Availability`
    spine** (Σ `total`/`sold`/`available()` across the property's tenant-scoped room types), never a
    mislabeled per-room-type figure; arrivals/departures/in-house counts (`FrontDeskService`);
    housekeeping status counts (real `housekeeping_tasks.status` enum) + "to clean" (pending+in_progress);
    **payments received on the date** (settled payment/capture − succeeded refund, tenant-scoped `Payment`
    ledger); today's arrivals as a **names-only** list.
  - **DERIVED:** occupancy % (sold/total), to-clean — from real figures only.
  - **DEFERRED (flagged, not faked):** revenue / ADR / RevPAR trend — a series aggregation this pass
    doesn't build → a "Not yet available" stub, never a fabricated chart or number.
  - **NO-BACKEND:** custom range / export disabled "Soon".
  - **Guest PII:** arrivals names only; encrypted email/phone never rendered. As-of day via `?date=` GET
    links (RMB-safe); scoped reads resolve in `render()`.
- **The "Dashboard" nav item in `<x-operator.shell>` now points to the real route** (`route('app.dashboard')`).
- **OPERATOR CORE COMPLETE — 5 screens** (Calendar/Rack, Front Desk, Housekeeping, Reservation Detail,
  Dashboard) all on the shared operator system, honesty rule enforced per-screen. **Verification (all
  actually run):** full suite **166 tests / 2926 assertions green** on MySQL `vyrobel_test` (+3
  `OperatorDashboardViewTest`); PHPStan L5 0 errors; Pint clean. The dashboard test proves occupancy is
  the true org figure ("2 of 3 rooms sold", 67%), tenant isolation (org B's 3-of-3 never enters org A's
  2-of-3, no combined 5-of-6), the payments KPI is real + tenant-scoped, no fabricated series, no PII.
- **NEXT: the public booking engine** (`/book/{property}` — styled onto the unstyled stub; the JSON API is
  the real final backend), then `/app/units`. NB it is PUBLIC (context via `ResolvePublicProperty`, not the
  operator `app` group / `SetTenantForWebRequest`).

### 2026-07-12 — DESIGN PASS · public booking storefront Screens 1–2 (Browse, Room Detail) — read-only

- **The public booking storefront design pass began — a NEW PUBLIC design system** (presentation only —
  no change to AvailabilityService / BookingEngineService / AssignmentService / schema / any guarantee).
  Both screens are **READ-ONLY and write NO inventory** (the booking WRITE / checkout is the next screen).
  - **PUBLIC context (no bypass):** unauthenticated; `ResolvePublicProperty` resolves the `{property}` slug
    under an explicit super-admin lookup, sets **real** `TenantContext` + spatie team id to the property's
    org, and stashes the property id — every downstream read runs tenant+property-scoped. A visitor for
    property X can only ever see X's inventory; another property/org's data 404s. This is NOT the operator
    `app` group and NOT `SetTenantForWebRequest`; there is no logged-in user.
  - **`<x-public.shell>`** (`components/public/shell.blade.php`) — a full-page storefront chrome (property
    header + centered content + footer, **NO operator sidebar/nav**), reusing the SAME Vyrobel tokens
    (frosted glass, terracotta, Sora/Inter, keyframes) — copied, self-contained, same "dedicated shell per
    surface" decision. **No Livewire** (server-rendered, rate-limit-friendly for a public page).
- **Screen 1 — Browse / Search → `BookingEngineController@browse` (`/book/{property}`)** (`44ae311`):
  restyled the landing (renamed `stub()`→`browse()`, removed `booking/stub.blade.php`). REAL search
  (GET → re-runs `ResolvePublicProperty`) + the **sellable rooms** for the dates via the certified
  `BookingEngineService::search` (availability via `AvailabilityService`, real prices; only `available≥1`
  + a real full-stay price). Per-night **derived** from the real total. Before a date search → a
  **names-only room teaser** (no availability/price claim); a no-availability search → an honest empty
  state. The Select CTA links forward to Screen 2 (never books).
- **Screen 2 — Room Detail → `BookingEngineController@room` (`/book/{property}/rooms/{roomType}`)**
  (`c933973`): the room-type is resolved **PROPERTY-scoped** (`RoomType where property_id = X` →
  cross-property/org 404). REAL specs from the schema (name/kind/sleeps/adults/children, + bedrooms/
  standard-occupancy shown only when the nullable column is set), REAL amenities via the polymorphic
  amenities catalog (only when catalogued), and REAL availability/price for the searched dates.
  **DEFERRED (flagged, not faked):** photos/gallery (decorative banner, "Photos soon"), amenities when
  none exist ("not yet listed"), description/reviews/map ("coming soon"). "Book this room" is flagged
  "Soon" and performs **no write**. Read the `{roomType}` route param via `$request->route()` (a two-param
  binding quirk fed the method arg the property slug); `RoomType` gained `@property int|null` for
  `bedrooms`/`std_occupancy` (nullable columns whose `integer` cast made Larastan infer non-null — docblock
  only).
- **Verification (all actually run):** full suite **172 tests / 2970 assertions green** on MySQL
  `vyrobel_test` (+3 `PublicBookingBrowseTest`, +3 `PublicBookingRoomTest`); PHPStan L5 0 errors; Pint
  clean. Tests prove: real sellable rooms/prices render with **no auth**; **public isolation** (X's
  storefront never shows Y's rooms; a Y room 404s on X; unknown slug/room 404); nothing fabricated (teaser
  + empty-dates honest). The JSON API contract (`PublicBookingEngineTest`) is unchanged + green.
- **NEXT: the public booking CHECKOUT (the WRITE flow)** — quote → book, routing THROUGH `AssignmentService`
  (never overbooks) + `FolioService` (append-only); the advisory `booking_quotes` hold reserves no
  inventory. Then `/app/units`.

### 2026-07-12 — DESIGN PASS · public booking CHECKOUT (Screen 3) — the first public WRITE, on the real path

- **The public checkout is styled + live — the FIRST public screen that WRITES inventory (anonymous
  visitor), given more care than any prior screen.** Presentation + a thin page: the controller only
  CALLS the certified services — **no change to `AssignmentService`/`FolioService`/the append-only ledger/
  schema/any guarantee, and NO parallel write path.**
  - **GET `/book/{property}/checkout`** — review + guest form (READ-ONLY; real price via the certified
    search path; writes nothing).
  - **POST `/book/{property}/checkout`** — the WRITE: creates the advisory `booking_quotes` HOLD (reserves
    NO inventory, has expiry), then books **THROUGH `BookingEngineService::book`** → `AssignmentService`
    (source=direct, re-checks availability **under its lock** and **REJECTS when full — never overbooks**;
    only OTA imports accept-and-flag) → room charge + payment **THROUGH `FolioService`** (append-only). On
    a lost race → an honest "just booked by someone else" redirect, **never a faked confirmation**. PRG →
    styled confirmation with the **real reservation reference**.
  - **Payment:** the wired method is **manual / pay-on-arrival**, recorded for real through `FolioService`.
    Real card entry (**Stripe Elements + 3DS**) is the **DEFERRED** rail (the deferred Stripe live
    checkpoint) — no fake card field, no fabricated payment.
- **CSRF — deliberate, not disabled (a public write is an attack surface):** the JSON API (`/quote`,
  `/book` — machine clients, token-authenticated by contract) stays CSRF-exempt, but the blanket `book/*`
  exemption was **NARROWED to `book/*/quote` + `book/*/book`** so the styled session form
  (`book/{property}/checkout`) is **session-CSRF-validated** (`@csrf`) on top of the group's
  `throttle:booking` rate limit. (`bootstrap/app.php`.)
- **Public isolation:** `quote()` resolves the room-type **property-scoped** (`RoomType where
  property_id = X`) → a checkout on property X **cannot** book property Y's room (404, nothing written).
  No PII beyond what the guest enters. The room "Book this room" CTA now links forward to checkout.
- **Two route fixes:** read the `{quote}` route param via `$request->route()` (the same two-param binding
  quirk that fed `room()` the slug); the checkout DRYs its offer lookup via a shared `offerFor()` helper.
- **THE CRITICAL GUARANTEE — verified, no regression:** `PublicBookingCheckoutTest` proves a full flow
  creates a **real direct reservation via `AssignmentService`**, reserves inventory **at book, not at
  hold**, takes a **real append-only payment**, and shows the real reference; the advisory hold (and an
  expired hold) **never change `sold`**; and **two holds on the last unit both succeed but only ONE
  `book()` converts — the second is cleanly rejected, `sold` never exceeds 1** (asserted at the service
  level AND through the styled screen). Public isolation (cross-property checkout 404) and the CSRF token
  on the write form are asserted. **Full suite 178 tests / 2997 assertions green; the true-parallel
  no-double-booking suite (`ConcurrentBooking`/`Deadlock`/`CrossSystemConcurrency`, 9/114) re-run green**
  after the checkout screen; PHPStan L5 0 errors; Pint clean. The JSON API contract
  (`PublicBookingEngineTest`) is unchanged + green.
- **NEXT: the last unstyled stub `/app/units`** (operator units list). Deferred booking polish (flagged
  in-product): Stripe card checkout, room photos/descriptions/reviews.

### 2026-07-12 — DESIGN PASS · operator Units styled — the STYLED SURFACE is COMPLETE

- **`/app/units` → `App\Livewire\UnitsList`** styled onto the shared operator design system (reused, not
  redefined). Presentation + a thin read: READS only; no service/schema/guarantee change; tenant-scoped
  (`Unit` `BelongsToOrganization`). **REAL:** each unit's code/name/room-type/kind (`unit_kind`)/sleeps
  (`max_occupancy`) + **cleanliness + condition keyed to the real enums** (`housekeeping_status`
  clean/dirty/inspected, `condition_status` in_service/out_of_order/out_of_service — warm tags) + active
  flag + the **owner LINK where `owner_id` is set** (owner NAME only; units without an owner show
  "Operator-owned" — real, not blank-faked). **DERIVED:** summary counts. **DEFERRED:** per-unit photos
  (no real bytes → not shown). **NO-BACKEND:** add/edit/filter/export "Soon". **PII:** owner name shown (a
  business party, not a guest); no encrypted email/phone. Verified by `OperatorUnitsViewTest` (render +
  real enums + owner link + no PII + tenant isolation).
- **THE DESIGN PASS IS COMPLETE — the whole styled surface over the certified backend is done:** owner
  portal (4 screens) · operator core (5: Calendar/Rack, Front Desk, Housekeeping, Reservation Detail,
  Dashboard) · operator Units · public booking storefront (3: Browse, Room Detail, and the real checkout
  WRITE through AssignmentService + FolioService). No unstyled contract stubs remain. Three role design
  systems: `components/layouts/owner` + `<x-owner.shell>` + `FormatsOwnerData`; `components/layouts/operator`
  + `<x-operator.shell>` + `FormatsOperatorData`; `components/public/shell` (server-rendered, no Livewire).
  The honesty rule (2026-07-11) held across all ~13 screens, each with a render/isolation test; operator
  writes use POST→controller→service-action→PRG; the public write is CSRF-protected (JSON API exempt by
  path). **Suite 180 tests / 3014 assertions green; PHPStan L5 0 errors; Pint clean.** Backends/schema/
  guarantees are UNCHANGED throughout — presentation only.
- **NEXT (needs a written, gated decision — none started):** deferred in-product polish (Stripe card
  entry at checkout, room photos/descriptions/reviews); OR the next Phase 3 module (Unified Inbox); OR a
  live-integration checkpoint (Channex staging / Stripe Connect payout rail).

### 2026-07-12 — BROWSER-VERIFICATION PASS (3 user journeys) — 2 demo-data gaps found + fixed

The styled surface was exercised end-to-end in a real browser (Playwright), not just by feature tests —
three user journeys against the demo data. **No app logic changed; both fixes were demo DATA** (the
`DemoSeeder`), and each gap was a shipped, tested feature left *undemoable* by missing seed data — exactly
what a browser pass (not a unit test) surfaces.
- **Flow 1 — public booking (`6e3ceb6`):** the `DemoSeeder` created the demo property with a **NULL slug**,
  but `/book/{property}` resolves by **property slug** → the whole public storefront was unreachable for the
  demo. Fix: seed `slug = 'seaside-demo'`. Proven in-browser: search → room → checkout → **real** direct
  booking (`source=direct` via `AssignmentService`, inventory reserved at book, real manual payment through
  `FolioService`, real reference). (Plus two browse-copy fixes: `middteman`→`middleman`; stale
  "next screens" footer reworded.)
- **Flow 3 — owner portal, THE WEDGE (`c66e258`):** `demo-owner@vyrobel-demo.test` was **not linked to an
  Owner** and no owner-accounting data was seeded → `/owner` **failed closed** (empty portal), making the
  wedge undemoable despite the certified backend. Fix (demo data **through the certified services** —
  nothing fabricated): the demo user is a real Owner of apartments A1/A2 with an **18% agreement**; bookings
  attributed via `OwnerAttributionService` → `owner_ledger`; a September statement finalized via
  `OwnerStatementService`; a pending payout via `OwnerPayoutService`. **Net €885.60 = gross €1,080 − 18%
  €194.40, reconciles to the cent** (ledger signed-sum == statement net). Proven in-browser across
  dashboard / statement (no invented fee line, no PII) / payouts (real enum states only) / units
  (owner-scoped occupancy) with the scope staying active across GET-link month nav.
- **Flow 2 — operator ops:** exercised (calendar/front-desk/housekeeping/folio/dashboard); no gaps found.
- **Verified:** the demo now demos the full loop (public booking → operator manages → owner sees reconciling
  money). Suite **180 / 3014 green; PHPStan L5 0; Pint clean** — no app logic touched.

### 2026-07-13 — AUTH PHASE A (login/logout) + PHASE B (owner invite flow) — COMPLETE & secure

Real, session-based authentication now fronts the whole app — **the temporary dev-login shim is gone**.
Backends/schema/guarantees are UNCHANGED beyond additive invite plumbing; the flow **calls** the existing
owner-accounting services, it does not reimplement them.

**Phase A — the shared Vyrobel login (`ddbaf47`).** ONE session login for operator + owner
(`LoginController` create/store/destroy, `LoginRequest`, styled `auth/login`):
- **Active-only sign-in** — `Auth::attempt(['email','password','status'=>'active'])`, so an `invited`
  (not-yet-activated) or `disabled` login **cannot** authenticate even with the right password.
- **Rate-limited per (email, ip)** (Breeze pattern) + **generic errors** (no account enumeration) +
  **session regenerated** on login and invalidated on logout. Logout is a **CSRF-protected POST** behind
  `auth`.
- **Role routing:** an authed user linked to an `Owner` (via `owners.user_id`) → `/owner`; otherwise the
  operator app `/app`. `/app/*` + `/owner/*` require `auth` (redirect to `/login`); `/book/*` stays public.
  The two context middleware (`SetTenantForWebRequest`, `SetOwnerScopeForPortal`) simply read
  `$request->user()` — login just authenticates; context resolution was already real.

**Phase B — the owner invite flow (`0bfc48b` · `4b2f196` · `ab78ac3` · `5ab7e96`).** Operator invites an
owner → email → owner sets password → lands in their portal:
- **Token design:** high-entropy `Str::random(64)`; **only its `sha256` hash is persisted**
  (`owner_invites.token_hash`, indexed → O(1) lookup), **never the plaintext**; **single-use** (status
  `pending → accepted`), **7-day expiry**, **revocable**, tied to (email, org). Re-inviting **refreshes**
  the pending invite's token instead of duplicating (`OwnerInviteService`).
- **Operator invite = the proven operator-mutation pattern** — CSRF-protected, `throttle:10,1` **POST →
  `InviteOwnerController` → `OwnerInviteService::invite()` → PRG**. The POST re-runs the web middleware so
  the invite + assigned units are created **org-scoped**; a foreign unit id is **silently dropped**
  (`Unit::whereIn(...)` tenant filter) — never assigned across orgs. Creates an **inactive `invited`
  login** (unusable random password) + a **pending `inactive` owner** + an active membership, then
  dispatches the email.
- **Owner-only, defense-in-depth:** the modal renders **Manager/Accountant DISABLED "Coming soon"**, the
  controller re-validates `Rule::in(['owner'])`, AND the service throws on any non-owner role. The client
  is never trusted.
- **Accept = public, token-authenticated** (`AcceptInviteController`, `/invite/{token}`, `throttle:10,1`):
  `findRedeemable()` matches by sha256 under `runAsSuperAdmin` and returns null for any **unknown / expired
  / spent** token → the **SAME generic "invalid link" page** (no enumeration of which invites exist).
  `accept()` runs `forOrganization(...)` in a DB transaction: sets the password (**`hashed` cast** — never
  plaintext), **activates** the login + owner, **assigns the units**, **creates the `ManagementAgreement`**
  at the invite's commission, and **SPENDS the token**. Then **auto-login + session regenerate** → `/owner`,
  where owner-scope binds to the now-active owner and shows their real data.
- **Email = `OwnerInviteMail`** rendered **email-safe** (table layout, all-inline CSS, no
  backdrop-filter/flex, Arial fallback, bulletproof button, preheader) — brand *evoked*, not the frosted
  app chrome faked.

**DEFERRED (flagged, not faked):**
- **Manager / Accountant invites** — UI-disabled **and** server-rejected; only OWNER is wired in Phase B.
- **Email DELIVERY** — dev uses `MAIL_MAILER=log` (the Mailable is really rendered + logged, proven by the
  end-to-end test, but nothing leaves the box). Real delivery needs a configured driver — **Mailpit/SMTP**
  locally, a real transactional provider in production. The flow is complete; only the transport is
  deferred. Also deferred: **password reset** ("Forgot password?" shows "Soon"), and **2FA** (schema
  columns exist, no flow).

**VERIFIED — one continuous end-to-end proof + the full net.** `PhaseBEndToEndTest` drives the WHOLE
chain in one test (**39 assertions**): operator (logged in, tenant-scoped) invites via the modal action →
`OwnerInviteMail` dispatched with the tokened link (captured from the Mailable) → guest opens `/invite/
{token}` → sets a valid password → login+owner activate, unit assigned, 18% agreement created → auto-login
→ `/owner` shows the owner's own unit → the token is **SPENT** (a reuse gives the **byte-identical** page as
a garbage token → no enumeration). Security recap asserted in-chain: token **hashed** (`token_hash ===
sha256(plain)`, not the plaintext), password **hashed** (`Hash::check` true, not stored raw), **single-use**
(second activation impossible), **auto-login** establishes the session, **owner-scope binds**. Plus the
per-step suites: `AuthLoginTest` (11), `OwnerInviteBackendTest` (5), `OwnerInviteAcceptTest` (7),
`OwnerInviteEmailTest` (2), `OperatorInviteOwnerTest` (6, incl. tenant-scope / cross-org-drop /
role-reject / active-email-graceful / auth-required). **Full suite 212 tests / 3220 assertions green;
PHPStan L5 0 errors; Pint clean.**

### 2026-07-13 — Mailpit wired for dev/demo email (config/docs only — no app logic)

The invite email now **lands in a viewable inbox** for demos instead of only logging. **Mailpit** is the
dev mail catcher: `brew install mailpit`, run `mailpit` (SMTP `127.0.0.1:1025`, UI `http://127.0.0.1:8025`),
`.env` `MAIL_MAILER=smtp` + `MAIL_PORT=1025` (`.env` stays gitignored — no secrets committed; Mailpit needs
no auth). `.env.example` keeps `MAIL_MAILER=log` as the **zero-dependency safe default** (a fresh clone
works with no Mailpit installed) with the Mailpit switch documented inline and `MAIL_PORT` pre-set to 1025.
- **Verified end-to-end (browser):** an operator sent an owner invite through the real modal → the real
  `OwnerInviteMail` arrived in Mailpit (from *Vyrobel*, branded, personalized "Hi …", single-use expiry
  note) → its tokened `/invite/{token}` link opened the **Set your password** screen. No app code changed;
  full suite **212 / 3220** still green (tests use `Mail::fake`, unaffected).
- **HONESTY:** Mailpit is **dev/demo only**. **PRODUCTION still needs a real transactional provider**
  (Postmark/SES/etc.) — that remains the one deferred piece of the email story.

### 2026-07-13 — Invite flow extended to STAFF roles (Hotel Manager + Accountant) — owner-only no longer

The invite flow now covers **three** roles on ONE set of secure machinery (hashed/single-use/expiring
tokens, no enumeration, rate-limit, session-regen, tenant isolation) — NOT a parallel system:
- **OWNER** (unchanged): an external `Owner` party → owner link + `/owner` portal, optional assigned
  units + management agreement. **No spatie role** (portal access comes from the owner link).
- **STAFF — Hotel Manager / Accountant** (new): a `Membership` + a **real spatie catalog role** → the
  operator app `/app`. **No owner record, no agreement, no units.**

**Real RBAC keys (read from `config/authorization.php`, not invented):** the modal's "Manager" maps to
the catalog role **`Hotel Manager`** (there is NO bare "Manager" role); "Accountant" maps to **`Accountant`**.
Slug→role is a whitelist in `OwnerInviteService::STAFF_ROLE_MAP`; **`resolveStaffRole()` fail-safes** —
the slug must map to a catalog role AND that role must be **provisioned for the org** (team-scoped;
`OrganizationRoleProvisioner` installs it at org-creation / demo seed) before assignment. This is checked
at **invite time** (operator gets a clean flash, never a phantom invite) AND again inside the accept
transaction (defense-in-depth — a non-assignable role rolls the whole activation back, never a
half-activated login with no role). The team foreign key is **`organization_id`** (spatie teams mode,
per `config/permission.php`), not `team_id`.

**Schema:** `owner_invites.owner_id` made **nullable** (additive migration) — staff invites carry no
owner. The `OwnerInvite`/`OwnerInviteMail` names are kept (historical; they're the invite records/mail,
now role-aware) rather than a churny rename.

**The fork, handled explicitly:** the accept controller redirects **by the invite** — owner (`owner_id`
set) → `/owner`; staff (`owner_id` null) → `/app` (Phase-A role-routing already sends a member with no
owner link to `/app`). The set-password greeting resolves the name from the owner (owner path) or the
invited login (staff path). The modal **enables** Manager/Accountant (was disabled "Coming soon"),
**hides the owner-only fields** (assign units, commission) when a staff role is selected, and the
title/subtitle/helper + the **email copy** are **role-aware** (a manager is never told about an "owner
portal"). The set-password subtitle was de-ownered ("your Vyrobel account").

- **Server-side (never trusts the client):** `InviteOwnerController` validates `Rule::in(['owner',
  'manager','accountant'])` and drops owner-only fields for staff; the service assigns only REAL,
  validated catalog roles. An unknown role 422s; an unprovisioned/​unmappable role → clean flash.
- **Verified (browser + tests):** an operator invited a Manager → the email landed in **Mailpit** with
  role-aware copy ("Your Hotel Manager access", no "owner portal") → the invitee set a password → activated
  as **status=active, NOT an Owner, roles=[Hotel Manager]**, landing at `/app/dashboard`. New
  `StaffInviteTest` (5) covers manager + accountant activation (role assigned, no owner artifacts, /app),
  the owner path unchanged (owner + agreement + units, /owner, no staff role), role-key correctness (exact
  catalog names; no bare "Manager"), and the fail-safe (unprovisioned role → clean error, nothing written).
  The two old "manager/account rejected" assertions were updated to "unknown role rejected" (the feature
  changed). **Full suite 217 tests / 3280 assertions green; PHPStan L5 0 errors; Pint clean.**
- **DEFERRED still:** email DELIVERY in production (Mailpit is dev only); password reset; 2FA. Manager/
  Account invites are **no longer deferred** — they are wired.

### 2026-07-13 — Tier 1 sweep · password reset (auth chapter complete)

Real forgot/reset password on Laravel's **Password broker**, styled from "Vyrobel Reset Password" onto the
shared auth tokens (login + set-password language). Same security posture as the rest of auth: **no
enumeration** (`/forgot-password` always returns the identical generic status; **ACTIVE users only** — an
invited-but-not-activated user has no password to reset and uses their invite, a distinction never leaked),
**tokened single-use expiring** links (broker-owned), on reset the **password is hashed**, the
**remember-token rotated** (signs out other sessions), the **session regenerated**, and the user signed in
+ **routed by role** (shared `RedirectsByRole` trait, extracted from `LoginController`). **Rate-limited**
request endpoint; **CSRF** on all forms. Custom email-safe `PasswordResetMail` via
`User::sendPasswordResetNotification` (brand-consistent with the invite email) → lands in **Mailpit** in
dev; **production still needs a real provider**. The login "forgot password?" link is now real (was "Soon").
- **Verified (browser + tests):** request reset for an active user → email in Mailpit → link → new password
  → signed in at `/app`. `PasswordResetTest` (8): emailed to active; no-enumeration (ghost + invited get the
  same generic response, no email); full round-trip (hashed, old fails / new works); single-use; expiring;
  rate-limited; login link enabled. **Suite 225 / 3321 green; PHPStan L5 0; Pint clean.**

### 2026-07-13 — Tier 1 sweep · PRODUCT DECISION: check-out dirties the unit

**Decision:** on **check-OUT**, the departed unit becomes **`dirty`** (it needs cleaning before the next
guest). — **Why:** previously check-out generated the departure-clean *task* but left the unit's
`housekeeping_status` untouched, so the Units screen misreported a just-vacated unit as clean, and a later
"mark clean" was a **clean→clean no-op** (the loop the browser pass flagged). Now the state reflects reality
and a dirty→clean cycle is a **real** transition.
- **How (on the real service/event chain, not inline):** the existing `GenerateDepartureCleanTask` listener
  (the one-directional consumer of `reservation.checked_out` that already creates the departure task) now
  ALSO flips each unit dirty **through `HousekeepingService::markUnitHousekeepingStatus`** — never inline in
  the checkout action. That emits `unit.housekeeping_changed` (a **housekeeping-only** event NO inventory
  consumer reacts to → never re-triggers availability recompute; the consumer discipline holds). **Idempotent:**
  the unit is only dirtied when not already dirty; a double check-out is a no-op (the CheckOut action returns
  early → no second event), so nothing doubles.
- **KPI note:** the dashboard "to clean" (= pending + in_progress **tasks**) and the housekeeping board were
  already correct off the departure task; this fix is about the **unit** status (Units screen) and closing
  the dirty→clean cycle.
- **Verified:** `CheckoutDirtiesUnitTest` (5) — checkout → unit dirty + departure task pending + a
  from-clean→dirty log; the board shows it + the dashboard renders the to-clean KPI; a real dirty→clean cycle
  (from-dirty→clean log, not a no-op); idempotent double-checkout; an already-dirty unit isn't re-dirtied.
  **Suite 230 / 3339 green; PHPStan L5 0; Pint clean.**

### 2026-07-13 — Tier 1 sweep · invite prune + UnitFactory test-infra debt

**Expired-invite prune (Step 3):** `invites:prune-expired` (scheduled daily) → `OwnerInviteService::pruneExpired()`.
CONSERVATIVE — over-cleanup is the only real risk, so every guard errs toward leaving things alone: only
**pending + never-accepted + past-expiry** invites are marked `expired`; the pending owner is **soft-removed**
only if still `inactive` and its login isn't active (`owners.status` is an enum → SoftDeletes, not a relabel);
the invited login is marked `expired` only if still `invited` AND has **no remaining redeemable/accepted
invite anywhere** (a login shared with a live invite in another org is left intact). Runs as an explicit
super-admin platform pass (tenant-safe); idempotent. `PruneExpiredInvitesTest` (7) covers the pruned case
AND every untouched case (accepted/active, still-valid, activated login, cross-org shared login) + idempotency.

**UnitFactory test-infra debt RESOLVED (Step 4):** `UnitFactory` now sets
`organization_id => app(TenantContext::class)->id() ?? Organization::factory()` — respecting the active tenant
context, **consistent with `ChannelFactory`/`OwnerFactory`** — instead of always minting a fresh (mismatched)
org. An explicitly passed `organization_id` still wins (backward compatible). **Blast radius was nil in
practice** (`Unit::factory()` is used only inside `HousekeepingTaskFactory`, which no test exercises — tests
build units via `Unit::create`), so **no test needed adjustment**; the fix is for correctness of any future
in-context factory use. Test-infra only, no app code touched. `UnitFactoryContextTest` (2) proves both the
context-aware default and the explicit-override. **Full suite unchanged at 237 pre-existing + 2 new =
239 / 3372 green; PHPStan L5 0; Pint clean.** (`HousekeepingTaskFactory` carries the same latent pattern but
is out of this scope and currently unused.)

### 2026-07-14 — Demo-readiness polish: room photos + PDF export

Two additive pieces to make the validated core look/feel like a real product. No change to the certified
booking/owner-accounting cores beyond additive wiring; honesty rule held throughout.

**Room photos (Part A):** REUSE the shared polymorphic `media` table (convention #7 — never a parallel
photo table): additive migration adds `collection`/`sort_order`/`is_cover`/`alt`; `HasPhotos` on RoomType
(the sellable entity) gives `photos()` + `coverPhoto()`. Operator upload/reorder/cover/remove via
`RoomTypePhotoController` (the operator-mutation POST→PRG pattern). **SECURITY** (the app's first file
upload, treated as an attack surface): accepts only real images (`image` rule validates actual content +
`mimes:jpeg,jpg,png,webp`, so a disguised `.jpg` is rejected), size-capped (5 MB), **generated ULID
filenames** (user's filename never used → no path traversal), stored on the `public` disk deliberately;
**tenant-scoped** (room-type + photo resolve via string ids → cross-org 404, nothing written). Rendered on
the storefront (cover + gallery) + operator/owner unit cards, with the **decorative gradient kept as the
honest fallback** for no-photo rooms ("Photos soon" removed). **Storage:** local `public` disk in dev
(`storage:link`; generated files gitignored, regenerate on seed); **S3/cloud is the production path
(flagged, not hardcoded).** Demo photos are REAL, locally-generated JPEGs (`Support\Media\PlaceholderImage`
via GD) — never hotlinked or fabricated URLs.

**PDF export (Part B):** `barryvdh/laravel-dompdf` (dompdf) ^3.1 + a thin `PdfService`. Branded, **PDF-safe**
documents (table layout + inline CSS + DejaVu Sans for € / U+2212 — NOT the app's frosted glass). Two
documents, both from REAL data (services called, never modified) with **NO invented lines**:
- **Owner statement** (`OwnerStatementPdfController` + `Support\Pdf\OwnerStatementDocument`) — **owner-scoped**
  (id → `findOrFail` under OwnerScope → cross-owner 404, fail-closed for non-linked); gross → commission →
  fee → expenses → OTA → net, provisional lines flagged, FX only on a real conversion.
- **Operator folio** (`FolioPdfController` + `Support\Pdf\FolioDocument`) — **tenant-scoped** (id → `findOrFail`
  → cross-org 404); the append-only ledger (charges +, settled payments −, refunds their own + line).
- Both **reconcile to the on-screen figures to the cent AND to the character** via a shared
  `Support\Money\MoneyFormatter` (the owner/operator screens now delegate to it) + a shared
  `Support\Folio\FolioTotals` (the folio reconciliation formula, now used by both the screen and the PDF —
  removing the duplication). The fake "card-processing fee" never appears. Wired to the previously-"Soon"
  download affordances; **CSV export + remittance/booking-summary PDFs + folio print stay honestly deferred.**

**Verified:** browser — an operator uploaded a real JPEG (stored as a ULID filename, rendered on the
storefront gallery + unit cards); an owner downloaded `vyrobel-statement-1-2026-09.pdf` showing
€1,080.00 − €194.40 + €0.00 (PROVISIONAL) = €885.60 (matching the screen); an operator downloaded a folio
PDF (Room +€300 / Tax +€69 / Payment −€369 → balance €0.00). Tests: `RoomPhotosTest` (7),
`RoomTypePhotoUploadTest` (10), `OwnerStatementPdfTest` (5), `FolioPdfTest` (5). **Full suite 266 tests /
3461 assertions green; PHPStan L5 0 errors; Pint clean.**

### 2026-07-14 — MAINTENANCE MODULE built & certified (tickets + 2 integrations)

A self-contained backend module that slotted into infrastructure deliberately left ready. Design +
audit: `docs/schema/maintenance.md`. Mostly REUSE — one new table (`maintenance_tickets`).

**Tickets core (Step 1):** `maintenance_tickets` (ULID, `BelongsToOrganization`, SoftDeletes, morph
`maintenance_ticket`) + `MaintenanceService` mirroring `HousekeepingService`: guarded lifecycle
`open → in_progress → resolved`, `open|in_progress → cancelled` (illegal jumps throw; idempotent
re-start/assign). Cost = integer minor units. Post-commit `maintenance_ticket.*` events are one-directional
and NOT availability triggers — a plain ticket never touches sold/availability.

**Integration 1 — out-of-service ↔ availability (Step 2):** a `takes_out_of_service` ticket, on open/start,
flips its unit to `out_of_service` via `MaintenanceService::setUnitCondition` (a real `condition_status`
transition + a `unit_status_logs` type=condition row) which emits **`unit.condition_changed`** over the
room-type's published window. The **EXISTING** `RecomputeAvailabilityOnInventoryChange` listener consumes it
— `out_of_service` is already counted into `out_of_order`, so the unit drops out of availability.
**AvailabilityService and the AssignmentService locking core are NOT modified; `sold` is never touched** —
out-of-service is a unit-level exclusion, exactly like OOO/blocks. Return-to-service on resolve/cancel only
when no OTHER active OOS ticket holds the unit (two-tickets case). An existing booking is **NEVER
auto-cancelled** (a physically-assigned reservation on a now-OOS unit is left intact + flagged for manual
move). If the room-type is already fully sold, the row's raw `total−sold−ooo−blocked` reads **negative**
(honest over-committed signal) while the clamped `available()` stays **0** so new bookings are cleanly
rejected — **no oversell, no Phase-1 change**. The **concurrency suite was re-asserted green** (9/114).

**Integration 2 — cost → owner deductible expense (Step 3):** resolving a costed ticket on an OWNED unit
posts via the **certified** `OwnerExpenseService::recordExpense(category:'maintenance', source:$ticket)` →
owner-from-unit + deductibility from the agreement + the `owner_ledger` deduction line → onto the statement.
**NEVER a direct owner_ledger write.** An **operator-owned unit** (owner_id null) records the cost on the
ticket only — **no owner_expense** (explicit, not dropped). `correctCost(ticket, ?Money)` handles corrections
+ voiding the charge **append-only** — reverses via `voidExpense` (a new reversing line; the original is
never edited) then re-posts. **Period-lock respected** by the certified path — a cost dated in a sealed
period lands as a `prior_period_adjustment` on the next statement. This gives the previously-deferred
`owner_expenses.source` hook its real source (the maintenance ticket).

**Stub (Step 4):** an UNSTYLED operator contract stub — `App\Livewire\MaintenanceList` (`/app/maintenance`) +
`App\Livewire\MaintenanceTicketDetail` (`/app/maintenance/{ticket}`, RMB rule → cross-org 404). The styled
maintenance UI is a later design pass; surfacing the manual-move flag to the operator is future work.
**→ RESOLVED the same day by the MAINTENANCE UI design pass (next entry): both the styled UI and the
manual-move flag surfacing are now DONE and removed from the deferred register.**

**Invariant #22** locked in PROJECT-STATE. **DEFERRED (now resolved — see next entry):** styled maintenance
UI + the manual-move flag surfacing. **Verified:** `MaintenanceTicketTest` (7) · `MaintenanceOutOfServiceTest` (6) ·
`MaintenanceCostExpenseTest` (6) · `MaintenanceModuleTest` (3) + the re-run concurrency suite. **Full suite
288 tests / 3552 assertions green; PHPStan L5 0 errors; Pint clean.** No regression to the booking or
owner-accounting cores.

### 2026-07-14 — MAINTENANCE UI design pass — module now COMPLETE end-to-end (board + work order)

The maintenance module's operator surface, styled onto the SAME operator design system as the other
screens (operator layout, `<x-operator.shell>`, `FormatsOperatorData` — reused, never redefined).
**Presentation + wiring of EXISTING actions only** — no schema/service/availability/cost path changed.

**Board (`/app/maintenance`, `MaintenanceList`):** a kanban whose 3 lanes ARE the real active-status enum
(open/in_progress/resolved — no invented columns; cancelled is terminal → off the board). Cards show real
unit/category/priority/assignee; resolved cards show real cost + date. The **"Unit OOS" badge** shows only
when `takes_out_of_service` AND the unit is genuinely `condition_status = out_of_service`. Summary chips +
the **"Units out of service" rail** are DERIVED from real rows. Two REAL writes via POST →
`MaintenanceActionController` → `MaintenanceService` → PRG: **New ticket** (`open()`, own units only) and
**Assign** (active org member). Foreign unit / cross-org ticket → 404; non-member assign → 422.

**Work order (`/app/maintenance/{ticket}`, `MaintenanceTicketDetail`):** RMB rule kept (string id →
tenant-scoped `findOrFail` → cross-org 404). Real facts + description + an **activity timeline built ONLY
from the ticket's real `audit_events`** (opened/assigned/started/resolved/cancelled — actor + timestamp;
nothing fabricated). **Lifecycle writes** (Start/Resolve-with-optional-cost/Assign/Cancel) each offered ONLY
when the real state allows the transition; the service stays the authority — an illegal transition is caught
and flashed cleanly (**never a 500**). **Both integrations made visible:** (1) an **OOS callout** + the
**manual-move flag SURFACED** — the REAL count of active `reservation_unit` rows on the blocked unit (absent
when zero), linking to the rack (surfaces the previously-deferred flag; the actual move still happens via the
existing move-room/rack path — never auto-cancelled); (2) a **cost card** whose destination is read from the
ACTUAL `owner_expense` (source = ticket): present → "charged to {owner} as a deductible expense"; absent →
"operator cost". Never a fabricated charge.

**Model typing (doc-only, PHPStan L5):** added an `@property` block to `AuditEvent`; `currency`/`timezone`
to `Property`; a `BelongsTo<Property>` generic to `Unit::property()`.

**Demo seed:** `DemoSeeder` now seeds a coherent maintenance spread through `MaintenanceService` (real path,
not raw inserts): an OPEN ticket, an IN-PROGRESS ticket, an OOS ticket on room **101** (which has an active
stay → the work order shows the real "1 booking to move" flag), a RESOLVED **owner-cost** ticket on apartment
**A2** (→ a real deductible `owner_expense` for the demo owner) and a RESOLVED **operator-cost** ticket on
room 103 (→ no owner_expense). All clearly demo, coherent with the existing demo org/units/owner.

**Deferred register:** **REMOVED** "styled maintenance UI" and "the manual-move flag surfacing" (both now
done). **Verified:** `MaintenanceBoardViewTest` (7) + `MaintenanceDetailViewTest` (7); `migrate:fresh --seed`
runs clean with the new maintenance seed. **Full suite 302 tests / 3639 assertions green; PHPStan L5 0
errors; Pint clean.** No regression to the booking, owner-accounting, or maintenance-backend cores.

### 2026-07-14 — UNIFIED INBOX module — CORE built (interface + fake + real; real channels deferred)

A guest-messaging inbox: one place to see + reply to guest messages across channels, threads linked to
real guests/reservations. Built with the **interface + fake + real** pattern proven on Channex/Stripe —
the **internal core + a manual channel** work end-to-end now; the **real external adapters (email/WhatsApp/
OTA)** are config-gated skeletons that **throw "not configured"**, account-gated. Design: `docs/schema/inbox.md`.
**Two new tables; everything else reuses existing infra** (no parallel structures).

**Core (Step 1):** `message_threads` + `messages` (ULID, `BelongsToOrganization`, SoftDeletes, morph
`message_thread`/`message`). The **threading anchor** is `unique(org, channel, external_thread_ref)`; **inbound
idempotency** is `unique(org, channel, external_message_id)` + `createOrFirst` catch-the-race (same discipline
as `webhook_events`). `channel` is a communication-medium ENUM (manual/email/whatsapp/booking_com/airbnb) —
**NOT** a FK to the OTA-distribution `channels` table (a different concept). `InboxService` is the single write
path: `startThread` / `postInbound` (idempotent) / `sendOutbound` (via the resolved channel) /
`assign|close|snooze|reopen` (guarded) / `markRead` / `receiveInbound` (backbone). `App\Messaging` mirrors
`App\Payouts`: a `MessageChannel` interface + `MessageDeliveryResult`/`InboundMessage` DTOs + `ManualChannel`
(REAL) + `DeferredChannel` skeletons (`EmailChannel`/`WhatsAppChannel`/`BookingComChannel`/`AirbnbChannel`
throw) picked by `MessageChannelResolver`. Inbound rides the reliability backbone
(`ProcessInboundMessageJob extends RetryableSyncJob`) — queued/retried/dead-lettered + idempotent at BOTH the
sync-job and message levels. Proven under **true OS-level concurrency** (proc_open barrier, the no-double-booking
harness): two concurrent inbound of the same external id → **exactly one message + one thread**, no 500.

**Race-safety (locked):** `postInbound` runs `createOrFirst` in **AUTOCOMMIT** (not inside an outer
transaction) — the losing INSERT's re-SELECT must see the COMMITTED winner, which a long REPEATABLE-READ
snapshot hides. This matches the Channex webhook leg. The unread bump is an **atomic increment**.

**Linking + threading (Step 2):** guest linking **REUSES the existing matcher** — extracted
`App\Services\GuestMatcher` (match by `external_refs->{key}`, else create), now used by BOTH
`BookingImportService` (behaviour-preserving refactor) and the inbox — **no parallel matcher**. The
external-ref VALUE is a **hash** of the identity (non-PII in `external_refs`); the real address lands in the
guest's **encrypted** email/phone. Threading GROUPS (never a thread-per-message): by the provider thread ref,
else by a non-PII participant-hash anchor `p:{hash(channel|identity)}` so a stranger's messages still thread.
`InboxService::linkReservation` (tenant-scoped) attaches booking context. **GDPR:** `Guest::anonymize()` now
also **redacts the guest's inbox conversation** — scrubs message bodies + sender labels + the thread's
encrypted participant_identity/subject, keeping the skeleton for audit (never soft-delete-and-keep-PII).

**Unstyled stub (Step 3):** operator contract stubs `App\Livewire\InboxList` (`/app/inbox`) +
`App\Livewire\InboxThread` (`/app/inbox/{thread}`, RMB rule → cross-org 404). The reply box is a **REAL**
outbound — POST → `InboxActionController` → `InboxService::sendOutbound` → PRG. On the manual channel it
records **delivered** + clears unread; on a DEFERRED channel it leaves an **honest failed record** + a clean
flash (never a faked send, never a 500). **PII:** the surface shows the guest NAME + conversation (front-desk
precedent); the encrypted `participant_identity`/contact is **never rendered**. The **styled inbox UI is
DEFERRED to a design pass**, and the inbox is **NOT yet in the operator nav** (added with the styled UI).

**Demo seed (Step 4):** `DemoSeeder` seeds three coherent conversations through the REAL `InboxService`:
a pre-arrival question from **Demo Traveller** LINKED to their reservation (+ a delivered operator reply), an
UNLINKED stranger enquiry (matcher creates the guest; threads on the participant anchor), and a RESOLVED
(closed) thread from **Demo Walker** (+ a delivered reply).

**DEFERRED (honest — account-gated, NOT faked):**
- **email** channel — needs an inbound-email provider (Postmark/Mailgun inbound parse) + a verified sending domain.
- **whatsapp** channel — needs WhatsApp Business API approval + a BSP (Twilio/Meta) number.
- **booking_com / airbnb** channels — need OTA messaging API access (Channex messaging or the OTA partner API).
- **Styled inbox UI** — a design pass (the stub is unstyled); the inbox joins the operator nav then.

**Inbox invariants (added to PROJECT-STATE):** inbound exactly-once via `external_message_id`; deferred
channels **honest-fail** (throw + failed record, never faked); `Guest::anonymize()` scrubs message bodies +
participant identity; `participant_identity` is the **encrypted routing anchor** (never rendered, never a
competing copy of a linked guest's PII).

**Verified:** `InboxCoreTest` (12) · `InboxLinkingTest` (9) · `InboxConcurrencyTest` (true-parallel, 5×) ·
`InboxModuleTest` (7 — the HTTP integrity suite). `BookingImportTest` stays green after the GuestMatcher
refactor. `migrate:fresh --seed` runs clean with the inbox seed. **Full suite 331 tests / 3790 assertions
green; PHPStan L5 0 errors; Pint clean.** No regression to any Phase 1/2, owner-accounting, maintenance, or
reliability invariant.

### 2026-07-14 — UNIFIED INBOX UI design pass — module now COMPLETE end-to-end (list + conversation + nav)

The Unified Inbox's operator surface, styled onto the SAME operator design system as the other screens
(operator layout, `<x-operator.shell>`, `FormatsOperatorData` — reused, never redefined). **Presentation +
wiring of EXISTING actions only** — no change to `InboxService`, the schema, the channel interface, the
reliability backbone, or the linking/threading/GDPR logic.

**Thread list (`/app/inbox`, `InboxList`):** a scannable conversation list — each row shows the guest NAME,
the real **channel badge** (keyed to the `message_threads.channel` enum, warm tints + a glyph letter — no
brand green/blue), the real last-message preview (bold when unread), an unread dot + count, status, and the
**linked-reservation tag ("{ref} · {unit}") only when genuinely linked**. Filters (All/Unread/Assigned-to-me/
Closed) + a channel filter + search are **Alpine over the REAL rendered rows**; the summary chips are DERIVED
counts aligned to the tab semantics. `FormatsOperatorData` gained `channelMeta()` (label/glyph/warm-tone per
enum) + `channelIsConnected()`.

**Conversation (`/app/inbox/{thread}`, `InboxThread`):** RMB rule kept (string id → tenant-scoped findOrFail
→ cross-org 404; opening marks read). A real transcript — inbound cream / outbound terracotta bubbles by
`direction`, day dividers, real timestamps + the real outbound **delivery status** (Delivered/Sent, and the
honest **FAILED** for a deferred-channel send — invariant #24). The **reply box sends a real outbound over the
thread's channel** (POST → `InboxActionController` → `sendOutbound` → PRG); the manual channel delivers, a
**DEFERRED channel shows an honest "isn't connected yet" composer** (no Send; a forced POST records an honest
`failed` message + a clean flash — never a fake send, never a 500). **Lifecycle actions** (Assign/Close/Snooze/
Reopen) run through the guarded service, offered per the real state (new controller methods + routes). The
**context sidebar** shows the Guest card (NAME + the CHANNEL only — never raw contact, invariant #25 — with a
**derived "N stays · guest since {year}"** from the guest's real reservations, OMITTED when none — never
hard-coded), the Reservation card (ref/unit/dates/status + a View link) or a real tenant-scoped **"Link to a
reservation"** picker, and the Assigned-to card.

**Nav:** "Inbox" is now a top-level operator nav item (near the top — a daily driver), added with the styled
UI as planned.

**Deferred register:** **REMOVED** "styled inbox UI" and the "not yet in the operator nav" note (both done).
The **real external channels (email/whatsapp/booking_com/airbnb) remain deferred** behind the interface,
account-gated (unchanged — the composer honestly reflects this per channel). **Verified:** `InboxListViewTest`
(3) + `InboxThreadViewTest` (6); `migrate:fresh --seed` renders the demo inbox threads on the styled screens;
browser-verified the reply round-trip (Delivered). **Full suite 340 tests / 3831 assertions green; PHPStan L5
0 errors; Pint clean.** The Unified Inbox module is now **COMPLETE end-to-end (backend + styled UI)**.

### 2026-07-16 — ROADMAP AUDIT + planning docs (analysis only, NO code) — the current planning source of truth

The buildable-in-a-vacuum core being complete, this was the gated ROADMAP-AUDIT-then-fork task named in the
prior consolidation. **Read-only analysis** — no app code, schema, or test touched; the only outputs are three
new versioned docs. Every "built/done" claim was cross-checked against the actual repo (routes, `app/Livewire`,
`app/Http/Controllers`, `app/Services`, `app/Models`, `database/migrations`), trusting the repo over the
catalogue's optimism. **These three docs are now the planning source of truth for what to build next** (read
them before starting any new screen):

- **`docs/ROADMAP.md`** — the full designed-vs-built-vs-remaining map across ~16 modules. Headline: **~26 of
  ~124 designed screens done (~21%) — the operational spine + owner-accounting wedge + public booking write +
  real auth.** Remaining work triaged into three tiers: **Tier 1** (~14 buildable-now *completion* workstreams —
  styled UI over certified backends, only 3 need a backend gate first: Global Search, 2FA, Maintenance
  Settings), **Tier 2** (~6 net-new *speculative* modules with no backend — Guest Portal ~24, Analytics,
  Reviews, Billing, Notifications, Emails), **Tier 3** (~8 *account-gated* items — real inbox channels, Channex
  live, Stripe Connect payouts, Stripe card checkout, production email, live billing). **Key ratio: only ~8 of
  ~90 remaining screens are truly account-gated; the bulk is buildable now** — the constraint is build effort +
  product commitment, not external blockers.
- **`docs/TIER1-SPEC.md`** — build-ready per-screen specs for the Tier 1 completions: for each, the design
  feature inventory + the **repo-verified backend reality per feature** (✅ REAL / 🟨 DERIVABLE / 🟥
  NEEDS-BACKEND / ⬜ DEFER, each naming the real model/service/column/route), the write actions (service method
  named or flagged as new), a strength rating, and a **buildability verdict** (READY / MOSTLY-READY /
  NEEDS-BACKEND-FIRST). Ends with a **5-wave build order** grouped by shared backend. **Honesty caveat recorded
  in the doc:** the design HTML files are minified React SPAs that are opaque to text extraction — the design
  feature lists are catalogue-derived *intent* (confirm exact panels in-browser at build time), while the
  **backend verdicts are rigorously repo-grounded** (the load-bearing part).
- **`docs/DESIGN-COVERAGE.md`** — exists-vs-missing design audit. **Ground truth: `docs/design/raw/` has 125
  files / 124 screens** (the catalogue says 120 — **off by 5**; the extras `Operator Inbox`, `Operator
  Conversation`, `Owner Invite Email`, `Vyrobel Invite Owner`, `Vyrobel Set Password` are now-built inbox/auth
  exports). **Coverage is near-total — only ~3 needed screens lack a design** (Operator Units [already built],
  Password Reset Email [already built], 2FA [needs backend anyway]); **every Tier-2 future module is already
  fully designed.** **Design is NOT the bottleneck for any planned work.** Also surfaced an **un-scoped module**
  in the designs (`Operator Team Schedule` + `Operator Schedule PDF` — staff rostering, no backend, not in the
  ROADMAP) — flagged as a potential Tier-2. **Action noted:** reconcile `docs/design/CATALOGUE.md` to 125/124.

**Decision — NEXT = Wave 1 (the owner-accounting long-tail).** Per TIER1-SPEC's build order, and confirmed by
DESIGN-COVERAGE (**all 10 Wave 1 designs EXIST — zero design gate**): four gated sub-gates over the CERTIFIED
owner-accounting backend, owner/tenant-scoped, honesty rule enforced — (1) Owner Balance · Expenses · Agreement
· Archive; (2) Remittance-Advice + Booking-Summary PDFs; (3) Owner Unit Detail + Operator Owner Detail;
(4) Payout Run + Owner Documents. Stub what the design implies but the backend lacks (ADR/RevPAR charts, CSV
export, owner-editable rates). Then Waves 2–4 (reservations/folio · inbox · rates/channels/settings).
**In parallel, customer validation of the already-demoable product remains the highest-value non-build move**
(the Tier-2 bets are speculative without signal). **Verified at this point (unchanged by the analysis): 340
tests / 3831 assertions green on MySQL, PHPStan L5 0 errors, Pint clean.** The three docs were committed on
branch `docs/roadmap-audit` (7fea230 → ae1541b → 9785561) and **merged to `main`** in this consolidation so a
fresh session on `main` sees them.

### 2026-07-16 — 💰 MONEY FIX SHIPPED: cumulative refunds against a payment are now BOUNDED (invariant #27)

**A live financial bug, found while inventorying Wave 2 — closed before the Wave 2 refund screen builds on
top of it.** `FolioService::refundPayment()` checked the refund amount against the **ORIGINAL** on every
call and **nothing summed prior refunds**, so a payment could be refunded without limit. Proven on a dev DB:
a **€369.00 payment accepted three full refunds = €1,107.00** (each call satisfying `36900 <= 36900`).

**It was reachable in the SHIPPED product, not merely latent.** The original payment stays
`type=payment, status=succeeded` after a refund, `ReservationDetail:171` marked **any** settled payment
`refundable => true`, and the controller re-checked only that the payment was settled — so the Refund button
**persisted after refunding** and a second click refunded again. Wave 2's refund screen would have turned
"click twice" into "refund €50 twenty times".

- **Decision: this is a NEW invariant (#27), NOT a violation of #7.** — **Why:** the distinction is the whole
  lesson. Invariant #7 (append-only, correct-by-reversal) held perfectly throughout: every refund WAS a
  legitimate new offsetting row and the original was never touched. **#7 bounds EDITS; it never bounded the
  SUM.** A guarantee stack can be fully green and still leak money if nobody ever wrote down the bound. #7's
  entry in PROJECT-STATE now cross-references #27 so the two are not conflated again.
- **Decision: the guard lives in `FolioService::refundPayment()`, never per-controller.** — **Why:** it is
  the single write path (audited: exactly ONE production caller — `ReservationFolioActionController:57` —
  plus 3 test callers), so one guard covers every path, including the Wave 2 screen and anything added later.
  Same reasoning as `AssignmentService` being the sole inventory writer.
- **Decision: the guard runs BEFORE the gateway call.** — **Why:** `$this->gateway->refund()` is invoked
  before any row is written. A guard placed after it would move REAL money against a live gateway and only
  then reject. This ordering is load-bearing and is documented in the method.
- **Decision: reject with a domain exception, never clamp.** — **Why:** silently clamping a €6,000 refund to
  the €5,000 remaining would post an amount the operator never authorised. `RefundExceedsPaymentException`
  (extends `RuntimeException`, mirroring `App\Exceptions\Booking\*`) is caught by the controller → a clean
  flash, never a 500.
- **Decision: `withTrashed()` when summing prior refunds.** — **Why:** conservative for a money guard. Posted
  financial rows should never be deleted (#7), so this is belt-and-braces — but a soft-delete must never
  become a way to hand back refundable headroom. Only `succeeded` refunds count: a failed refund moved no
  money and must not consume headroom (locked by a test).

**Two further real bugs found in the same method and fixed here:**
1. **Cross-currency refunds passed.** `refundPayment` never checked that the refund currency matched the
   payment's — it stamped the refund with the **amount's** currency and compared raw minor units across
   currencies. **A 100 USD refund against a 100 EUR payment was accepted.** Now rejected: the cumulative sum
   is only meaningful same-currency, so the check is required for the bound to mean anything.
2. **A €0.00 refund was accepted** — a hole in the first cut of *this* fix, caught by its own test: with
   `remaining = 0` the controller called `refundPayment(…, Money(0))`, and `0 > 0` is false, so it posted a
   junk €0.00 row into an append-only ledger and reported "Refund issued.". Non-positive amounts now rejected.

**PROOF — fail-then-pass, then mutation (a money test that cannot fail is worthless):** `RefundGuardTest`
(10) was written **FIRST** and run against the unguarded code — **7 of 10 FAILED** (the over-refund accepted,
the HTTP path not rejecting, the button persisting). After the fix: **10/10**. Removing the guard again fails
**4**; restoring it passes 10/10. Two of my own tests were also wrong and fixed: one asserted on a route
**name** that never renders (only the URL does) so it passed **vacuously**; another misused Pest's
`toThrow()` second argument as a description when it is a **message matcher** — the same class of trap as
`toContain()` in Wave 1.

**Browser-verified on real demo data:** three refund attempts through the real operator UI on the same
€369.00 payment → **€369.00 refunded, ONE row, zero junk rows, original untouched** ("Refund issued." then
"already fully refunded." twice). Also confirmed live: a legitimate partial refund works (10000 → remaining
23210), €0.00 rejected, cross-currency USD rejected, `remaining+1` rejected, `exactly remaining` accepted →
remaining 0.

**Legitimate refunds unregressed** — the two existing partial-refund proofs (`FolioLedgerTest:23`,
`FolioPdfTest:73`) and `StripePaymentTest` pass untouched; the money/ledger paths (folio · payments · owner
attribution/expenses/statements/payouts · the Wave 1 PDFs) re-assert green at 97 tests / 2516 assertions.
Suite **420 → 430 tests / 4835 assertions**, PHPStan L5 0 errors, Pint clean, `migrate:fresh --seed` clean.

**The lesson worth keeping:** this was found by *inventorying a design*, not by a test failing — the whole
certified suite was green over it. Guarantees only hold where someone wrote the bound down.

---

### 2026-07-16 — ✅ SECURITY FIX SHIPPED: the `/app` authorization hole is CLOSED (route gate + owner-only role)

The finding recorded below is **fixed**, in two gated steps: **`5155a48`** (layer 1, the route gate) →
**`f63866a`** (layer 2, the root cause). **The product is now safe for a real owner login.** Suite
**402/4652 → 420 tests / 4795 assertions**, PHPStan L5 0 errors, Pint clean, `migrate:fresh --seed` clean.
Locked as **invariant #26** in `docs/PROJECT-STATE.md`.

**The audit came first, and it inverted the obvious fix** — which is why it was worth doing before touching
code. The naive move ("gate `/app` on a staff role/permission") would have been **exactly backwards on real
data**: the ONLY role assignment in the entire database was the **OWNER** holding `Property Owner`
(`['*']` — all 44 permissions, including `payments.refund`), while the actual staff user held **none**.
Verified empirically: owner `can('reservations.view')` = **true**, staff = **false**. Shipping the obvious
gate would have locked out staff and admitted the owner.

**Ground truth established (Step 0, read-only):**
- `/app` was `Route::middleware('auth')` and nothing else. The **only** role check in the entire codebase was
  the Horizon gate — zero `@can`/`@role` in any view, zero `authorize()`/`Gate::allows` in any controller or
  Livewire component, zero spatie middleware registered, zero `403`s in `app/`.
- **The owner's Membership is LOAD-BEARING and was never the bug.** `SetTenantForWebRequest` resolves the org
  ONLY from active Memberships; `SetOwnerScopeForPortal`'s `Owner::query()` runs under the tenant guard,
  which THROWS with no context. Remove the Membership → `/owner` 500s on every route. Nothing in
  `OwnerContext`/`OwnerScope` reads Membership at all (owner identity is purely `owners.user_id`). So the
  answer to "(a) tenant-context only / (b) load-bearing / (c) confers a role" is: **(a) by intent, (b) in
  effect, never (c)**. The bug was that `/app` treated "authenticated + has a Membership" as "is staff".
- **The naming trap:** `Property Owner` is the **ORG ADMIN** (`['*']`), *not* the unit owner — that is
  `External Owner` (`owner_portal.view` only, provisioned but assigned by nothing). `DemoSeeder` conflated
  them. **Production was sound** (the invite path gives owners no role, staff a real one); **the DEMO data
  was inverted**.

**The fix — defense-in-depth, two layers, each proven to block INDEPENDENTLY:**
- **Layer 1 — `App\Http\Middleware\EnsureBackOfficeAccess` on the `/app` GROUP.** Requires a role from the new
  `config('authorization.back_office_roles')` **allow-list**, checked **team-scoped**. Allow-list, not
  deny-list: no role, a portal-only role, or a role in a DIFFERENT org all fail **closed**, and it composes
  with tenant isolation instead of bypassing it. Owner-linked → redirected to `/owner` (a legitimate user
  gets a home, not a dead end); everyone else → bare 403. Neither response renders operator data.
  - **Keys on a ROLE, not a permission** — no single catalog permission spans every staff role
    (Housekeeping/Maintenance/Marketing Manager/Accountant share none), verified against the matrix.
  - **On the GROUP, never per-route** — a test asserts EVERY `app/` route carries it, so one added later
    cannot forget it.
  - **Ordering is load-bearing:** it must run AFTER `SetTenantForWebRequest` sets spatie's team id, or
    `hasAnyRole()` matches nothing and every operator is locked out. Neither middleware is in
    `Router::$middlewarePriority`, so both sink to the end in declaration order — asserted on the resolved
    chain so a future priority tweak fails loudly rather than silently.
  - **`Super Admin` deliberately NOT listed** — a global (team-null) role a team-scoped check could never
    match, and currently unassignable anyway (see the defect below). Untestable dead code in a security gate
    is worse than an honest note.
- **Layer 2 — `OwnerInviteService` assigns the owner-only `External Owner`** (`owner_portal.view`, nothing
  else) on the owner path. The allow-list then has **nothing to admit** even on a route that forgets the
  middleware. **The Membership stays** (load-bearing, above).
  - **Deliberately NON-throwing when the role is unprovisioned**, unlike `assignStaffRole()` which throws —
    and the asymmetry is the point. A staff member without their role is BROKEN (locked out of `/app`), so
    that path rolls back. An owner's portal access comes from `owners.user_id`, so an owner with **no** role
    reaches `/owner` identically and is **equally** non-operator-capable. Throwing would invent a new failure
    mode blocking a legitimate owner over an unrelated org-setup gap (nothing provisions roles at org
    creation — see below). It logs and proceeds. A role missing from the CATALOG is a code bug and still throws.

**Demo seeder corrected — the demo must model production, or the gate is not what the demo journeys prove.**
demo-owner held `Property Owner` (all 44 permissions incl. `payments.refund`/`guests.erase`/`users.manage`);
demo-staff held **nothing**. Now: demo-owner → `External Owner`, demo-staff → `Hotel Manager`, via the real
provisioner path; a test locks the model. (`StubViewsTest` test 1 was passing only *because* of that wrong
role — it drove OPERATOR pages as the owner; corrected to act as demo-staff.)

**Fixtures corrected, not the gate weakened.** 87 tests began 403ing because ~19 operator test files built
their staff as *"Membership, no role"* — which was never realistic (a real staff invite assigns a role; an
OWNER also holds a Membership — that was the hole). New shared helpers (`tests/Support/auth_helpers.php`:
`operatorUser` / `grantBackOfficeRole`) model a real operator. Owner, stranger, cross-org and non-member
fixtures were deliberately left role-less so their fail-closed/404/422 assertions still prove what they claim.

**Proof (not just green):** `BackOfficeAccessTest` (10) is **mutation-verified** — with the gate removed 5
fail, restored 10/10 pass. `OwnerNotOperatorCapableTest` (7) proves each layer alone: layer 2 asserts an
activated owner's `hasAnyRole(back_office_roles)` is false **with no HTTP at all** (so it holds for an
ungated route); layer 1 strips layer 2 by granting the owner a back-office role and shows the gate is what
stands at the door — each test justifies the other's existence. Browser-verified on real demo data: as
demo-owner, all six previously-leaking routes (`/app/dashboard`, `/app/owners`, `/app/rack`,
`/app/front-desk`, `/app/inbox`, `/app/payouts/run`) land on `/owner` with zero operator data; the owner
portal is fully intact; as demo-staff, all seven operator routes return 200.

**Two sub-defects surfaced and left open (both FAIL CLOSED — not exploitable; recorded in PROJECT-STATE):**
1. **No app-level org creation provisions roles** — `OrganizationRoleProvisioner` is called only by
   `DemoSeeder` + two tests; there is no `Organization::create` in `app/` at all. Any org born outside the
   seeder would have zero roles and the gate would lock out everyone in it. Moot today; **wire provisioning
   into org creation when that lands.**
2. **`Super Admin` is unassignable → the Horizon gate can never pass** outside `local`.
   `model_has_roles.organization_id` is `NOT NULL` and part of the PRIMARY KEY, so a team-null assignment
   cannot be stored while the team-null lookup filters on `IS NULL`. Fixing it needs a migration against
   spatie's own schema — not to be done casually.

**Also:** `phpunit.xml` now sets `memory_limit=512M`. Verified **headroom, not a leak** (the whole suite
passes under a raised limit): dompdf embeds a full DejaVu font per PDF test and every operator test now
provisions a 13-role × 44-permission catalog. Set there because `php artisan test` spawns a child PHP that
re-reads php.ini, so `-d memory_limit` never reaches it.

---

### 2026-07-16 — 🔴 SECURITY FINDING (now FIXED — see the entry above): the `/app` operator back office has NO role gate

**Found while browser-verifying Wave 1 · Sub-gate 3** (logged in as the demo OWNER, loaded the OPERATOR
screen `/app/owners/{owner}` and it rendered every financial detail). **Not a demo-data artifact — this is
production behaviour.** Two facts combine:

1. **`routes/web.php` — the `/app` group is `Route::middleware('auth')` ONLY.** No role, no permission, no
   policy. Tenant scope is enforced (you only see your OWN org), but *any authenticated member* of that org
   reaches every operator screen.
2. **`OwnerInviteService` grants an ACTIVE `Membership` on BOTH paths** — its own comment says so:
   *"Membership (active so the tenant context resolves once the login is activated) — BOTH paths."* So a
   **real, production-invited OWNER** gets an active operator Membership, `SetTenantForWebRequest` resolves
   their org, and `/app/*` opens.

**Impact:** an owner-role user can browse the entire operator back office of their org — rack, front desk,
housekeeping, maintenance, reservations + folios, the unified inbox (guest conversations), the owners list,
and now `/app/owners/{owner}`: **every other owner's balance, statements, payouts, commission and agreement.**
This defeats owner isolation (invariant #18) at the ROUTE layer — `OwnerScope` is deliberately inactive
off-portal, which is correct for a real operator and catastrophic for an owner who can reach `/app`.

- **Pre-existing, NOT introduced by Wave 1** — `/app/owners` has listed every owner since the Auth Phase B
  work (`2d28b32`), and every operator screen has always shared this group. Wave 1 **deepens what is exposed
  there** (a list of owner names → one owner's complete financials), which is how it surfaced.
- **Decision: NOT fixed inline.** — **Why:** Wave 1 is a gated *presentation* pass ("no guarantee change"),
  and this is a cross-cutting **authorization** decision spanning every operator screen: which of the 14
  catalog roles may reach `/app`; whether an owner should hold a Membership at all (it exists so the tenant
  context resolves — removing it needs a different context path for owners); and how staff-vs-owner is
  decided (spatie role vs `owners.user_id`). Bolting a guess onto one screen would be worse than the honest
  finding: it would imply the surface is protected when it is not.
- **This is the TOP post-Wave-1 priority — it must be closed before ANY real owner login.** Recorded as a
  CRITICAL item in `docs/PROJECT-STATE.md`'s outstanding register. Sketch (needs a decision, not a default):
  gate `/app` on a real staff capability (spatie role/permission from `config/authorization.php`), keep
  `/owner` on `SetOwnerScopeForPortal`, and add a test that an owner-role user gets 403 on every `/app` route.
  The demo seeder gives `demo-owner` a Membership too, so the fix must keep the demo coherent.

---

### 2026-07-16 — WAVE 1 COMPLETE — the owner-accounting styled long-tail (4 gated sub-gates, presentation only)

Wave 1 of the ROADMAP audit's build order, executed as four gated sub-gates with a commit each. **9 new
screens/documents over the CERTIFIED owner-accounting backend. Presentation + wiring of existing services
ONLY — mechanically verified at every gate: ZERO files changed under `app/Services`, `app/Models`,
`database/migrations`, `app/Payouts`, `app/Support/Owner` (services/), `app/Support/Money`.** No schema, no
service, no guarantee touched. Suite **340/3831 → 430/4835**, PHPStan L5 0 errors, Pint clean at every gate.

**What shipped** (all owner screens owner-scoped + fail-closed; operator screens tenant-scoped):
- **Sub-gate 1** (`55c9219`) — **Owner Balance** (real ledger activity + per-row running balance; DERIVED
  net position = Σ `signedMinor`; in-transit/paid from the real payout enum) · **Owner Expenses** (real
  `owner_expenses` incl. maintenance-sourced via the polymorphic `source`; the `deductible` **TRI-STATE**
  true/false/**null** never coerced; voided badge; the statement each hit via
  `attributed_ledger_id → ledger.statement_id`) · **Owner Agreement** (real effective-dated terms,
  display-only, honest nulls) · **Owner Archive** (finalized statements w/ the real gap-free `sequence_no`;
  drafts excluded; DERIVED lifetime/counts/span).
- **Sub-gate 2** (`5530f6b`) — **Owner Remittance Advice PDF** (per payout) + **Owner Booking Summary PDF**
  (the bookings behind a statement's revenue), on the existing `PdfService` + shared `MoneyFormatter`.
- **Sub-gate 3** (`9ff2ad9`) — **Owner Unit Detail** (unit facts, owner-scoped occupancy, attribution
  breakdown, real stays) + **Operator Owner Detail** (`/app/owners/{owner}` — the tenant-scoped lens).
- **Sub-gate 4** (this commit) — **Operator Payout Run** (finalized statements → REAL payouts via the
  certified `OwnerPayoutService`) + **Owner Documents** (aggregation over the real generatable PDFs).

**Three shared extractions (reuse-don't-duplicate; each prevents two surfaces from disagreeing):**
- **`App\Support\Payouts\RailLabel`** — the ONE rail→label map (screen + PDF). An unknown rail **THROWS**
  rather than prettifying a column into a fabricated payment method; PHPStan enforces exhaustiveness.
- **`App\Support\Owner\OwnerOccupancy`** — the ONE owner-scoped occupancy derivation (My Units + Unit
  Detail), extracted behaviour-preservingly from the certified `OwnerMyUnits::occupancy()`. **Keyed on
  PHYSICAL UNIT ID, never room type.** `availabilityRange()`/`OwnerPortalService::occupancy()` are
  room-type-wide, and a room type is shared with hotel rooms and OTHER owners' units — reading occupancy
  that way shows an owner a number driven by units they do not own (a cross-owner leak dressed as a metric).
  Proven live: two units in ONE room type, 3 nights each → the unit page shows 3 nights/10%, not 6/20%.
- **`assertNoRawBlade()`** (tests/Support/owner_helpers.php) — see the Blade bug below.

**Two real bugs found — both invisible to static analysis, both caught by actually running/opening things:**
1. **Raw Blade shipped to the user.** Blade does NOT compile a directive whose `@` follows a word character
   (the `\B` guard that lets an email address render literally), so
   `Totals exclude voided expenses@if ($multiCurrency)…@endif` rendered its **raw source** on the owner
   Expenses page. It renders as ordinary *text*, so the whole suite passed straight over it — only a browser
   caught it. Fixed (ternary), and locked for every owner screen by `assertNoRawBlade()`, which was **proven
   to fail on the reintroduced bug** before being trusted.
2. **A Livewire prop/view-data collision 500'd two screens** (10 failing tests). Livewire injects public
   props into the view scope, so `public string $unit` **shadows** the `'unit' => [...]` array from
   `render()` and `$unit['name']` fatals on a string offset. The `$xxxId` suffix on
   `OwnerStatementView`/`ReservationDetail`/`MaintenanceTicketDetail` is **load-bearing, not styling** —
   documented on both new components.

**Three corrections to the plan, found by building against the real code (recorded so the roadmap stops
repeating them):**
- **ADR/RevPAR are absent from all 10 Wave 1 designs** (verified by grep) — the plan's "stub ADR/RevPAR"
  instruction is moot here; they live in Reports/Insights (Tier 2). CSV export and owner-editable rates
  were real and were deferred.
- **`statement_id + entry_type='room_revenue'` does NOT reconcile to `gross_minor`.**
  `OwnerStatementService::buckets()` routes a room_revenue line into **adjustments**, not gross, when
  `prior_period_adjustment` is true or `service_date < period_start`. The Booking Summary mirrors that real
  partition and discloses prior-period rows as a real count rather than silently dropping them.
- **`owners.user_id !== null` does NOT mean "portal access active".** `OwnerInviteService` links `user_id`
  to an **inactive `'invited'` login at invite-creation time**, so that test would assert access nobody
  accepted. The operator screen renders the truthful state (pending invite + real expiry / genuinely-active
  login / the real `OwnerInvite` enum).

**Honesty rule (invariant #19) — the designs demand a great deal of backend that does not exist.** The
exports were **fully decoded** (they are not opaque — see `docs/design/CATALOGUE.md`), which made the gap
explicit. Standing order for this wave: **omit aggressively** — where a control would imply state with no
schema, it is NOT rendered even disabled (a greyed "Approve" asserts an awaiting-your-decision state that
has no state machine).
- **Disabled "Soon"** (a real thing that isn't wired): withdraw-early, CSV export, repair request, agreement
  download, message-manager, archive ZIP, tax summary, Stripe-Connect disbursement.
- **OMITTED** (would imply absent state): the Available/On-hold/In-transit *clearing* machine · the tax-ID
  hold · the owner **approve/query** workflow · e-signature/amendments · owner-editable pricing (`units` has
  **no rate/fee column at all** — the design's €140/€45 are literals) · day-blocking · Live/Snoozed toggle ·
  calendar sync · reviews (no schema) · cleaning schedule/cleaner ratings · the guest-message composer ·
  rank/"Best performer" · channel mix + per-unit payout timeline (payouts are per-statement/per-owner —
  per-unit would be invented attribution) · the operator Edit-owner modal + its three no-column toggles ·
  Remove owner (the design's own button is inert) · IBAN/bank details (no field exists anywhere) ·
  estimated settlement dates · the "Workspace paused" billing banner (a fabricated system status).
- **Design fiction refused:** fabricated prior-year occupancy (the Unit Detail design invents 2024/2023 as
  ×0.9/×0.8 of this year plus a hard-coded jitter array — *never ship fabricated history*) · "3 years · 34
  documents" (derived instead) · the `OS-/RA-/BS-2025-09` ref formats (the real reference is `sequence_no`) ·
  "Booking.com"/"Airbnb" (**the OTA brand is NOT modelled** — `reservations.source` is a 7-value enum and
  `channels.provider` only has `channex`) · "Direct bookings saved you €X" (`directRev × 0.15`) · the Payout
  Run's non-reconciling constants (its 6 rows sum to 66,000 but it claims 89,990; "11 transfers" vs "12
  statements") and its **zero** interactivity (0 onClick, 0 checkboxes — every button inert) · "Scheduled"/
  "On hold"/"In review"/"Sent"/"Ready" (none are real enum values).
- **Occupancy OMITTED from the Booking Summary PDF** — it is not an attributed value, and the only
  occupancy read available there is room-type-wide (the trap above).
- **PII:** no guest name/email/phone on ANY owner surface (tests assert a distinctive guest name is absent
  from the unit page and the booking PDF); `owners.tax_id` + the raw address JSON are never read on the
  operator screen. The owner's **email/phone ARE** rendered on the operator screen — a deliberate, narrower
  call: that is the OPERATOR's own CRM record for a business counterparty they invited (they typed the
  email), and the Owners list already renders the pending-invite email. The guest-PII rule is unchanged.

**Everything reconciles to the cent, cross-surface** (browser-verified on real demo data): ledger net
`70560` → Balance "€705.60" → the operator's owner-detail balance; statement `gross_minor 108000` →
"€1,080.00" on the Booking Summary PDF and the operator statement row; `net_payable_minor 88560` → "€885.60"
on the Remittance PDF, the pending payout and Balance's in-transit; unit A1 `44280` → "€442.80" = 540.00 −
97.20. Money is per-currency throughout — **never a cross-currency sum** (no silent FX, invariant #17).

---

### 2026-07-17 — WAVE 2 COMPLETE — the operator daily-ops long-tail (5 gated sub-gates)

Wave 2 of the ROADMAP build order, five gated sub-gates with a commit each: **Reservations list ·
Cancelled/No-show terminal states · the Refund screen · the Guests CRM list · the Guest profile.** Suite
**430/4835 → 511/5503**, PHPStan L5 0 errors, Pint clean, `migrate:fresh --seed` clean at every gate.
Browser-verified on real demo data at every gate.

Unlike Wave 1 this was **not** presentation-only: Sub-gate 5 adds the first guest write path (a real
`GuestService`), because the design's "Save changes" persisted nothing and a fake save is not a screen.

**THE SIX LOCKED DECISIONS** (taken before building, because each was ambiguous or self-contradictory in the
designs — the SQL was always trivial; the *definition* was the deliverable):

1. **Lifetime spend = BILLED**, labelled "Total billed", with the real open-folio balance BESIDE it. The
   design's "lifetime value" netted a refund off (only coherent for money COLLECTED) while including €701 of
   unpaid charges (only coherent for money BILLED). **It cannot be both.** → `App\Support\Guest\GuestSpend`.
2. **Status semantics = ONE shared resolver, three screens.** In-house = `checked_in`; Upcoming = `confirmed`
   ∧ future check-in; Past = `checked_out` ∨ window closed. → `App\Support\Reservations\ReservationStage`,
   which holds BOTH the badge (`for()`) and the SQL filter (`scope()`) so a chip cannot drift from the badge
   it filters on. `DUE` exists because the three headline buckets don't cover a came-due-never-checked-in
   arrival; `CANCELLED` is deliberately NOT folded into PAST (a cancelled *future* stay never happened).
3. **Stat tiles = current calendar month, by `check_in`, EXCLUDING cancelled, per currency**, with the scope
   on the face of the tile. Every design figure was a constant that reconciled to nothing.
4. **PII = the Guests CRM only.** Guest email/phone are shown on the CRM list + profile and NOWHERE else;
   front desk, reservations and inbox stay name-only. **Enforced by a test that asserts the perimeter from
   the outside, and mutation-proven** (leaking an email onto the reservations list fails it).
5. **Fee engines DEFERRED** — a cancellation-policy subsystem, not a screen. Never compute or fake a fee.
6. **Property Switcher DEFERRED** — no active-property concept exists; solve it once, globally.

**THE SHARED EXTRACTIONS** (the anti-drift discipline — two `match`es for one concept WILL disagree):
- **`Support\Reservations\ReservationStage`** — the stage vocabulary; badge + SQL in one class. Gained
  `mostSignificant()` (which ONE stage speaks for a guest with several stays) + `CANCELLED_STATUSES`.
- **`Support\Guest\GuestSpend`** — the BILLED definition. Reconciles to the ledger **by construction**:
  `FolioService::recompute()` writes the cached folio columns FROM the same append-only rows `FolioTotals`
  reads, and `GuestSpendTest` asserts the identity folio-by-folio so a future drift fails loudly.
- **`Support\Guest\GuestHistory`** — stays · nights · "guest since". **Extracted because three screens were
  about to disagree and one had already shipped**: `InboxThread`'s guest card counted EVERY reservation
  (cancellations included) and dated "guest since" from the earliest of them. Proven live: for a guest with
  one real 2026 stay and one cancelled 2024 booking the inbox said *"2 stays · guest since 2024"* while the
  CRM said *"1 stay · guest since 2026"* — both sincere, on both numbers. The inbox now reads the shared rule.
- **`Guest::reservations()`** — the approved shallow relation, on `lead_guest_id` (the FK every built surface
  already treats as "the guest of this booking"). The `reservation_guests` pivot's fellow-travellers are
  deliberately excluded, or "this guest's reservations" would mean two things on two screens.

**REAL BACKEND ADDED (Sub-gate 5 only)** — `App\Services\GuestService`, the single guest write path:
profile edit · notes · tags. `docs/WAVE2-INVENTORY.md` §A9 proposed "plain Eloquent + a controller"; that was
**wrong against this codebase's conventions** — CLAUDE.md requires an `audit_events` + `domain_events` row per
state change, and every writer records those INSIDE the service (no controller in the repo calls a recorder).

- **⚠️ THE AUDIT TRAIL MUST NOT BECOME A SECOND, UNENCRYPTED PII STORE.** `guests.email`/`phone` are encrypted
  casts; `audit_events.before`/`after` are plain JSON that `anonymize()` does not clear. Recording the old/new
  email there would re-retain the exact PII an erase destroys and silently undo encryption-at-rest. The audit
  therefore records **which fields changed, never their values** — enough to answer "who touched this guest's
  email on Tuesday?", never enough to reconstruct it. (`Guest::anonymize()` already stated this rule for
  itself; it holds for every write.) Asserted by test, verified in the browser.
- **`'note' => Note::class` added to the morph map** — a live tripwire, proven not assumed:
  `(new Note)->getMorphClass()` THREW `ClassMorphViolationException` under `enforceMorphMap`, and both
  recorders call `getMorphClass()` on the subject. Latent only because Note had always been the morph CHILD.
- Tags are **created on first use** (`firstOrCreate`, tenant-scoped): `tags` has been real since Phase 1 and
  **nothing had ever written one**, so a pick-list would be empty in every tenant forever. `unique(org, name)`
  makes a name the identity within a tenant, so create-on-use is the intended shape, not a duplicate factory.
  Detaching keeps the catalogue — it is the workspace's vocabulary, not one guest's property.

**A DELIBERATE DEVIATION FROM "LOG, DON'T FIX" — `Guest::anonymize()` now redacts NOTE BODIES.** The register
below logs GDPR gaps rather than fixing them, and that was right while notes had **zero writers anywhere** —
the gap was unreachable. Sub-gate 5 builds the writer, which promotes it to **live**: operators type
identifying detail into `notes.body` ("travelling with X"), and `anonymize()` already redacts inbox message
bodies for exactly this reason. Shipping the writer without the erase would have broken CLAUDE.md's PII
invariant **with brand-new code**. Three lines, inside the existing transaction, mirroring the message
redaction; the row + author + timestamp survive (never soft-delete-and-keep-PII). The *other* gap —
`anonymize()` has no UI trigger — remains logged.

**Money reconciles to the cent, cross-surface** (browser-verified on real demo data): every guest's rendered
"Total billed"/"Open balance" equals `FolioTotals`' independent read of the append-only rows — Booker €246.00,
Resident €664.20, Traveller €369.00, Walker €664.20 with €332.10 due. The profile and the list show the same
figures **because they read the same helper**, and €369.00 ÷ 3 nights = €123.00/night.

**OMITTED, not faked** (the standing order is OMIT AGGRESSIVELY — each verified absent, not greyed out): the
whole **"Latest review" panel** (~15% of the profile — there is no reviews table, model or migration
anywhere); the **paused-billing banner** (rejected by name in Wave 1, not reopened); the **localStorage refund
netting** and its non-reconciling "€978 open folio" (both branches hard-coded; 978 only holds if the refund is
exactly €277); **"Dublin, Ireland"** + the City input (no residence column — `nationality` is char(2), a
NATIONALITY); **"Prefers email"** (no `preferred_channel`; `language` is a locale); the **8 hard-coded
preference chips** (a designer's guess in the design's own script → the tenant's real tag catalogue); the
**bulk bar, its Send/Delete modals and the CSV export** (a guest is erased via `anonymize()`, never deleted —
a Delete button would keep the PII while looking like an erase); **pagination + sort** (neither has a handler
in the design); the **"+4%" delta** and the 248/38/31% constants (no historical snapshot exists to compute a
trend against); and the **"Shown to housekeeping & front desk" / "Flags arrivals" claims — verified FALSE**
(nothing outside the model reads `is_vip`, and neither surface reads tags). **Marketing consent is shown as a
RECORD, not a promise**: the column is real and this page is its first reader anywhere, but there is no
sending mechanism, so the caption says exactly that.

**The design's vocabulary was wrong and is not used:** "Completed" is not in the status enum (it means
`checked_out`); "In house" is the STAGE of a `checked_in` row; **"Booking.com"/"Expedia"/"Airbnb" are not
modelled at all** (`source` is direct/walk_in/ota/corporate/group/agent/manual with no brand column) — an OTA
booking reads "OTA". (Don't be fooled by `message_threads.channel`, which *does* carry `booking_com`/`airbnb`
— that is the messaging medium, a different enum for a different concept.)

**Also closed in Wave 2:** the **partial-refund CONTROLLER gap** — `FolioService::refundPayment()` always took
an arbitrary `Money`, so partial refunds were never a backend gap; the controller just never sent one. It is
now amount-aware (no `amount` → the full REMAINING, preserving the inline button), rejects €0 **before** the
service (an `InvalidArgumentException` that is not caught would have 500'd), and surfaces an over-refund
rejection as a clean flash that is **never silently clamped** — refunding less than was asked for without
saying so is its own money bug.

---

### 2026-07-17 — WAVE 2 OUTSTANDING REGISTER — four items found while building, deliberately NOT fixed

Each was surfaced by Wave 2, verified empirically, and sequenced AFTER it by explicit decision. Recorded here
so none is silently dropped. **None is a regression; all four predate Wave 2.**

1. **`AssignmentService::terminate()` has NO internal status guard.** Proven: re-cancelling an already-
   cancelled reservation overwrites `cancelled_at`/`cancellation_reason` and writes a junk `cancelled →
   cancelled` status-history row. Wave 2 guards it at the CONTROLLER (`ReservationLifecycleController`'s
   public `CANCELLABLE`/`NO_SHOWABLE` allow-lists, which the component also gates its buttons off), so the
   shipped UI cannot reach it — but **the service is the single write path and must guard itself**
   (defense-in-depth, the same shape as the #27 refund bound). My own brief asserted "both throw for an
   illegal transition"; the code disagreed.
2. **Two DEAD enum values** — `payments.payment_status = 'refunded'` and `folios.status = 'closed'` are in the
   schema and **never written by any code** (`FolioService::paymentStatus()` only ever writes
   paid/partial/unpaid). The designs assume states the code never produces, so a UI keying off either would
   render **never, silently**. Either write them or drop them; until then, no surface may depend on them.
3. **🔴 THE UNENFORCED PERMISSION CATALOG.** `payments.refund` is held by Super Admin / Property Owner /
   Group Admin / Accountant and NOT by Hotel Manager / Front Desk — **and no route enforces any permission.**
   The coarse #26 staff/owner gate is the only real authorization: any back-office role can do anything a
   back-office role can do. Lower blast-radius than the `/app` hole (that leaked *other owners' financials* to
   an owner; this is staff-vs-staff within one tenant) but it is a real security follow-up, and the catalog
   currently **describes a policy the app does not enforce**.
4. **Two GDPR gaps** — (a) `Guest::anonymize()` has **no UI trigger**: the erase is correct and tested but can
   only be invoked from code, so a real erasure request cannot be served by an operator. (b) *(CLOSED
   2026-07-17 — see the entry above: note bodies are now redacted, because Wave 2 shipped the first note
   writer and would otherwise have opened the hole itself.)*

Plus, from the same audit and still open: **Super Admin is unassignable** (pivot PK) and **no org-creation
path provisions roles** — both fail closed, so neither is exploitable.

---

### 2026-07-17 — TOTAL-RECALL CONSOLIDATION (docs only, no code) — the arc verified, four corrections recorded

A full context consolidation so a fresh session can resume with complete understanding. **Documentation only
— zero code changed.** Everything below was verified against the live repo, not against a brief.

**GROUND TRUTH at consolidation:** HEAD `e160eeb` on `main`, local == origin (pushed), working tree clean.
**511 tests / 5503 assertions green** on genuine MySQL 8 (`vyrobel_test`), **PHPStan (Larastan) L5 — 0
errors**, **Pint clean**, `migrate:fresh --seed` clean, queue = Redis/Horizon live. `docs/design/raw/*.html`
(125 files) remains **gitignored** — a fresh clone has the versioned `.md` specs, not the designs themselves.

**THE ARC IS COMPLETE.** All 48 dated entries *preceding this one* verified present and chronological (a `grep -c '^### 20'` returns a higher number — it counts this entry and the ones appended after it; same self-reference class as the HEAD convention): Phase 1 (Step 0 + Steps 1–8
close-out addenda: tenancy/RBAC · property/inventory · rates/availability · guests/encrypted-PII ·
reservations + AssignmentService · folios + FolioService · housekeeping) → Phase 2 (Steps 0–9, CERTIFIED;
Channex, the sync-reliability backbone, Stripe, public booking, staff layer) → the Stripe live-test-mode
validation → Owner Accounting (Steps 0–7, CERTIFIED) → the design pass + browser-verification → Auth A/B +
Mailpit + staff invites + password reset → the Tier 1 sweep → demo-readiness (photos + PDFs) → Maintenance
(built + UI) → Unified Inbox (core + UI) → the ROADMAP planning docs → 💰 the refund bound (#27) → ✅ the
`/app` gate (#26) + its finding → Wave 1 → Wave 2 + its register. **Nothing was missing; nothing was
rewritten** (this log is append-only, and its dated entries are a ledger).

**FOUR CORRECTIONS — the consolidation brief was wrong on each, and the repo won (meta-lesson (a) again):**
1. **Invariants are #1–28, not #1–27.** The brief asked for "#1–27" five times. **#28 (the guest-PII
   perimeter) was locked in Wave 2 the same day** and is real, tested and mutation-proven. Recorded as 28.
2. **The permission catalog holds 39 distinct permissions across 14 roles (11 back-office), not 44.** Counted
   from `config/authorization.php`. The *finding* is unchanged and confirmed by a fresh grep: **not one is
   checked anywhere** (`can:` / `->can(` / `authorize(` / `permission:` return zero hits across
   `routes/web.php`, `app/Http/Controllers/`, `app/Http/Middleware/` outside `EnsureBackOfficeAccess`, which
   checks ROLE, not permission).
3. **~42 of ~124 screens are built (~34%), not ~26 (~21%)** — the brief quoted the pre-Wave-1 figure. The
   ROADMAP was recounted row-by-row against real routes. ⚠️ It is a **column sum with one cross-listed row**
   (`Owner Invite Email` is Done under both Auth and Emails), so **distinct = 41**; the tilde is load-bearing.
4. **Wave 1 shipped 10 screens/documents, not 9** — an arithmetic slip in the original prose; the Wave 1
   tables always listed 10. Corrected in `docs/PROJECT-STATE.md` with the correction noted inline.

Also: the brief said to "reconcile the catalogue count to 125/124 **if not done**" — **it was already done**
(2026-07-16). The honest remainder is that those five screens are still absent from the catalogue's
*per-screen tables*, so a reader looking up "Operator Inbox" still will not find it. And the brief stated
that "cold emails + a demo script were prepared" — **no such artefact exists in this repo.** Not recorded as
fact; if it exists it lives outside version control.

**🔴 A NEW OUTSTANDING ITEM FOUND BY THIS AUDIT — ONE UNIDENTIFIED FLAKY TEST.** Of six consecutive full
runs, five were 511/511 green and **one reported 510 passed / 1 failed**. The failure was not captured (the
run printed only counts) and never reproduced: five further full runs, plus six targeted runs of all four
true-OS-concurrency suites (the prime suspects — they race real processes), were all green. **The 511/5503
headline is real and reproducible; the suite is not 100% reliable run-to-run.** This matters because the
suite is a real gate — a gate that blinks 1-in-6 for unknown reasons will one day hide a regression. Logged
in the register with the instruction to CAPTURE the failure next time rather than re-run.

**ONE ASYMMETRY IN THE LOG, recorded rather than papered over:** Phase 2 has an explicit "PHASE 2 CERTIFIED"
entry and Owner Accounting has "MODULE CERTIFIED" — **Phase 1 has neither.** Its Step 0–8 decisions ARE fully
recorded (2026-07-02), and its certification is asserted in `PROJECT-STATE.md`/`CLAUDE.md` and backed by the
live suite (the double-booking and tenant-isolation gates pass today). No retroactive certification stamp was
back-dated into the ledger — that would be inventing a gate nobody held. Noted here as the honest record.

**Also consolidated:** the two per-wave "lessons" lists were promoted into a durable **META-LESSONS** section
in `PROJECT-STATE.md` (a brief is a hypothesis / green ≠ sufficient / adversarially re-read money+auth /
extract-and-prove-shared / browser-verify / distrust the docs too / ship the erase with the writer), and a
58-line stale "(historical) NEXT ACTION — WAVE 1" block was collapsed — it still read as pending and
contradicted the live next action by two waves.

**NEXT ACTION is unchanged and stated in `PROJECT-STATE.md`:** clear the 🔴 register (the `terminate()`
service-level guard + enforcing the permission catalog) BEFORE Wave 3 — they are real gaps, not polish. Then
Wave 3 (owner inbox) / Wave 4. **In parallel: customer validation. No customer has ever seen this product.**

---

### 2026-07-17 — ⚠️ CORRECTING THE CORRECTION: "44 permissions" was RIGHT; my "39" was wrong

**Reverting forward** (this log is append-only — the entry above stays as written, and this one supersedes
its correction #2). Found by sending three blind readers at the finished docs with instructions to disprove
them; nothing in my own review caught it.

**What I got wrong.** The consolidation entry above "corrected" a brief's claim of **44 permissions** to
**39**, citing the repo — and then quoted that as evidence in META-LESSON (a) that briefs lie. **The brief was
right. The correction was wrong.** Verified:

```
config('authorization.permissions')                      → 44   ← THE CATALOG. Quote this number.
union of permissions NAMED across role definitions       → 39   ← a DIFFERENT question
in the catalog, reachable ONLY via the ['*'] wildcard    →  5   ← organizations.manage, billing.manage,
                                                                    roles.manage, settings.manage, api.manage
```
`OrganizationRoleProvisioner` expands `['*']` to `config('authorization.permissions')` — **all 44** — so
`Property Owner` genuinely holds all 44, and the pre-existing "13 roles × 44 permissions" notes in
`PROJECT-STATE.md` and `tests/Support/auth_helpers.php` were **correct all along**. My script measured the
union across roles and I read its answer as a refutation of a claim it was not measuring.

**Why this is worth a whole entry rather than a quiet edit.** It is the exact failure the same pass warned
about, committed in the same pass: the false number was written *into the prose of security invariant #26*,
and then generalized into a durable "lesson" — so a future session would have inherited both the wrong figure
and a wrong reason to trust it, with the file's own authority behind them. **A measurement that disagrees
with a claim has not refuted it until you have checked that you measured the same thing.** META-LESSON (a)
now carries that corollary, and (f) now names this incident.

**Corrected in place** in `docs/PROJECT-STATE.md` (a snapshot, not a ledger — it should state today's truth):
the register now quotes **44 (catalog) / 14 roles = 13 per-org + 1 global Super Admin / 11 back-office**, and
states both metrics side by side so nobody "recounts" and flips it a third time.

**The security FINDING is untouched and re-confirmed:** **not one permission is enforced anywhere** — zero
hits for `can:` / `->can(` / `authorize(` / `permission:` across `routes/web.php`, `app/Http/Controllers/`,
`app/Http/Middleware/` outside `EnsureBackOfficeAccess`, which checks a ROLE. The count was never the point;
the enforcement gap is.

**Same audit, other real fixes** (all verified, all now corrected in `CLAUDE.md`/`PROJECT-STATE.md`):
- **`E2E: Playwright` was in the LOCKED STACK and Playwright is not installed** — not in `package.json`, no
  config, zero tracked specs. Browser verification is real but is done via the **Playwright MCP server**
  against a live `php artisan serve`; every artefact it produces is gitignored. It is therefore **not part of
  the runnable regression net** and no longer listed as if it were.
- **`PHP 8.2+` in the locked stack; `composer.json` requires `^8.3`** — 8.2 cannot install this project.
- **`docs/prompts/phase-1.md` was billed as "the current build prompt"** — Phase 1 certified long ago.
- **The demo had no documented way in.** The docs' headline is "COMPLETE & DEMOABLE" and their top priority is
  customer validation, yet the credentials existed only inside `DemoSeeder.php`. Now documented — including
  that `demo-owner` is *refused* from `/app` **by design** (invariant #26), which a fresh session would
  otherwise read as a bug.
- **The dead-enum register named the wrong table and undercounted.** It is `reservations.payment_status`
  (not `payments.`), and there are **at least five** dead values, not two.
- **~10 tables are schema-only** (plans/subscriptions/usage_counters/api_keys/webhooks/groups/…): models and
  migrations exist, nothing wires them. The register said billing has "NO backend today", which invites
  exactly the parallel-structure duplication CLAUDE.md forbids.

---

### 2026-07-17 — 🔐 THE SECURITY/INTEGRITY REGISTER CLEARED — three gated fixes before Wave 3

Done deliberately **before** more screens, so Wave 3/4 inherit correct enforcement instead of a retrofit.
Three independent fixes, a gate each, every guard mutation-proven in both directions. Suite **511/5503 →
543/5646**; PHPStan L5, Pint clean; `migrate:fresh --seed` clean; all three browser-verified together.

**⚠️ EVERY ONE OF THESE WAS WORSE THAN THE REGISTER SAID, AND THE AUDIT IS WHY WE KNOW.**

---

**1. ✅ `AssignmentService::terminate()` — the TERMINATION BOUND (invariant #29). `f4a8913`.**

The register said: guarded only at the controller, so a *future* caller could reopen it. **The audit found
there were already TWO callers and they DISAGREED.** `BookingImportService::cancel()` — the OTA/Channex
**inbound webhook** path, i.e. a network-triggered, RETRYING caller — had always reached
`cancelReservation()`, carrying its own guard that checked only `['cancelled','no_show']`. So **an OTA
cancelling a stay the guest had already COMPLETED (`checked_out`) went straight through and stamped
`cancelled_at` onto it**, while the operator controller refused exactly that transition. Two rules, one
concept, silently disagreeing on a network-driven path.

Proven on real demo data before the fix (run, not asserted):
```
1st cancel : reason="Guest called - family emergency"  at=10:17:04  historyRows=2
2nd cancel : reason="oops duplicate webhook"           at=10:17:05  historyRows=3
→ original REASON destroyed · cancelled_at OVERWRITTEN · junk `cancelled -> cancelled` row appended
```

**IT IS #27'S SHAPE AGAIN: a certified service missing a bound.** #7 (append-only) was never violated — every
row a second termination wrote was a legitimate append. **#7 bounds EDITS; nothing bounded a SECOND
TERMINATION**, which is a REWRITE: it destroys the provenance the detail screen renders from those very
columns and re-emits `reservation.cancelled` to every consumer.

- `AssignmentService::TERMINAL_STATUSES = ['cancelled','no_show','checked_out']` — **the ONE definition**.
- The guard sits in the **private** `terminate()`, **before** the transaction (a rejected call rolls nothing
  back and touches no inventory). Both public entries funnel through it → one check, every caller.
- `ReservationAlreadyTerminalException extends BookingException` (→ RuntimeException) so the controller's
  existing catch renders a clean flash, never a 500.
- **The OTA path NO-OPS rather than throws** — it reads the shared constant and returns before asking. On a
  retrying webhook, letting a terminal booking reach the guarded service would turn an ordinary duplicate
  delivery into a **poison message**. Idempotent at the edge, authoritative in the service.
- The controller keeps its allow-list (defense-in-depth, the #26 shape), and a test asserts `CANCELLABLE` is
  the exact **complement** of `TERMINAL_STATUSES` over the real 7-value enum — two constants in two files is
  how drift starts.
- **Mutation-proven:** 5 failed + 1 error pre-guard → 13/13 → neutered → 5 fail again.

---

**2. ✅ THE PERMISSION CATALOG IS NOW ENFORCED (invariant #30). `9123ee7`.**

The catalog was **decorative**: 44 permissions, 14 roles, and **not one checked at any route, middleware or
controller** — the coarse #26 door admitted all 11 back-office roles EQUALLY.

**🔑 THE HEADLINE WAS NOT THE REFUND — IT WAS PRIVILEGE ESCALATION, and gating the refund alone would have
been COSMETIC.** `POST /app/owners/invite` assigns a **real spatie role** and was gated only by that door.
Proven end-to-end before the fix:
```
Housekeeping (units.view, housekeeping.view, housekeeping.manage — cannot refund, cannot manage users)
  → POST /app/owners/invite {role: accountant, email: attacker@controlled.test}
  → ✗ INVITE MINTED role=accountant status=pending
  → Accountant holds payments.refund => true      ← two requests, no operator involved
```
**Never gate a money route without gating the role-granting route in the same change** — the attacker simply
invites themselves the role.

**ENFORCED** — `can:<permission>` route middleware on: `owners.invite → users.manage` ·
`reservations.refund` **+ its GET screen** `→ payments.refund` · `reservations.payment → payments.record` ·
`payouts.create|pay → payouts.manage` · `reservations.cancel|no-show → reservations.cancel`.

- **`payouts.manage` DID NOT EXIST** (catalog 44 → **45**). The payout run had always moved real money with no
  permission to check against. Property Owner*/Group Admin/Accountant — **not** Hotel Manager: remitting an
  owner is an accounting act, same logic as `payments.refund`.
- **⚠️ AUTHORIZATION GOES ON THE ROUTE, NOT THE SERVICE — the deliberate exception to #27/#29's rule.** A
  permission is a REQUEST fact about an ACTOR; a bound is a DATA fact about a ROW. `FolioService::refundPayment()`
  is called by tests and jobs with **no authenticated user**. Do not "unify" them.
- Ordering verified: `Authorize` runs **last** — after #26, after `SetTenantForWebRequest` sets the spatie
  team — and survives Livewire updates. ⚠️ **A null team fails silently to `false`.**
- **THE UI NEVER INVITES A 403** (#27's principle, applied to authorization): the refund button + partial link
  + screen, Record-a-payment, cancel/no-show, every payout control, and the **whole invite modal** (not just
  its button) are `@can`-gated.
- **FIX FIXTURES, NEVER THE CHECK** (the #26 lesson): 28 tests / 7 files moved to correctly-permissioned
  roles. `StaffInviteTest` got a **targeted** `givePermissionTo('users.manage')` rather than a role swap —
  a swap would have destroyed the deliberate non-provisioning its fail-safe test exists to prove.
  ⚠️ **Cross-org isolation tests keep a PERMITTED actor**, or `can:` 403s before the scoped `findOrFail` and
  the `assertNotFound()` silently stops proving tenancy.
- **THE DECISION BEHIND IT** (locked with the user): the catalog is RIGHT — a Hotel Manager cannot hand money
  back; that is a normal, deliberate control. So the demo gains a **second and third account** and
  *demonstrates* the separation instead of hitting a wall.
- **Mutation-proven:** 6 failed pre-enforcement → 10/10 → strip every `can:` → 5 fail again.

**THE FOUR DEMO ACCOUNTS NOW MIRROR PRODUCTION RBAC** (and together they demo the whole story, rather than one
all-powerful login — the inverted-seeder mistake #26 found, in reverse):
```
demo-admin@…      Property Owner  → org admin: invites staff/owners; can do anything
demo-accountant@… Accountant      → refunds + runs the payout run
demo-staff@…      Hotel Manager   → runs the house; NO refund, NO payout, NO invite
demo-owner@…      External Owner  → /owner only; refused from /app entirely (#26)
```
⚠️ `demo-admin` was added because enforcing `users.manage` otherwise left the **invite flow undemoable** —
`demo-staff` could invite before *only because nothing checked*. Caught by seeding and looking, not by a test.

---

**3. ✅ GDPR ERASURE HAS A TRIGGER. `94ef7ad`.**

⚠️ **The reframe: `Guest::anonymize()` was called from NOWHERE** — no route, no controller, no component;
every hit outside the model was a comment or a test. The erase was correct, thorough and tested, and **no
operator could run it**, so a data-subject request could not actually be served. This BUILT the feature.

- `POST /app/guests/{guest}/anonymize` → controller → **the existing `anonymize()`** → PRG. It re-implements
  **no** redaction: that method already nulls the columns, hard-deletes ID documents *and their bytes*,
  redacts inbox message bodies + the thread's participant identity, and redacts note bodies. **A second copy
  would drift and quietly miss a store — which for an erasure means keeping data you said you destroyed.**
- **`can:guests.erase`** (Property Owner / Group Admin only): running the house does not include erasing a
  person. Route AND button gated.
- **NOT ONE-CLICK, AND THE SERVER CHECKS:** a typed `ERASE` validated server-side; the `confirm()` dialog is
  only a softer second layer, because **intent must survive a bookmarked POST**.
- Asserted end-to-end: raw guest row carries no name/phone; note body `''`; every inbox message body `''`;
  thread `participant_identity` null — while **€300.00 of billed history still reconciles** and the row is
  neither deleted nor soft-deleted.
- **Mutation-proven:** 8 failed pre-build → 9/9 → strip `can:guests.erase` → the permission test fails;
  neuter the typed-confirmation rule → the not-one-click test fails.

---

**4. ✅ THE FLAKY TEST — root-caused by CAPTURING it, not re-running.**

`OwnerStatementPdfTest::"renders only real ledger lines"` asserted `toContain($owner->name)` — the **raw**
faker name — against **Blade-rendered (escaped) HTML**. `OwnerFactory` uses `faker->name()`, and **1.68% of
faker names contain an apostrophe** ("Abelardo O'Conner" → `O&#039;Conner`), so ~**1 run in 6** failed on a
page that was **perfectly correct**. A TEST bug, never a product bug. Fixed to `e($owner->name)` and **proven
deterministically** by forcing a real apostrophe-bearing name: old assertion fails, new one passes.

**Two lessons worth more than the fix:** the four true-OS-concurrency suites were the obvious suspects and
were **innocent** (6/6 green under targeted runs) — the answer only came from capturing the failure
(`php artisan test > run.json 2>&1`). And it is a clean case of testing **the needle rather than the
behaviour**.

---

**STILL OPEN (kept in the register, none blocking):** the **dead enum values** (≥5, not 2 — a status-keyed UI
renders never, silently); **no inbox permission exists in the catalog**, so the inbox's six writes — including
a guest-facing reply — remain #26-only (**logged, not invented**: naming it is a product decision about who
may speak to a guest, and it matters directly to Wave 3); and the two fail-closed RBAC defects (Super Admin
unassignable; no org-creation path provisions roles).

**WHAT THIS BUYS THE NEXT BUILD:** the enforcement layer is now correct and patterned, so a Wave 3/4 write
route carries its `can:<permission>` and `@can`-gates its control **by following the established, tested
shape** — rather than shipping unguarded and being retrofitted later. That was the entire point of doing this
before the screens.

---

### 2026-07-17 — WAVE 3 COMPLETE — the OWNER INBOX (owner ↔ operator staff), over the existing InboxService

The owner portal's Messages screen, on the certified Unified Inbox backend. The `messages` nav slot the shell
had reserved as an honest "Soon" is now a real route. Suite **543/5646 → 556/5733**; PHPStan L5, Pint clean;
`migrate:fresh --seed` clean; browser-verified end-to-end, both directions.

**🔴 THE STEP-0 AUDIT CHANGED THE SHAPE — this was NOT presentation-only.** `docs/TIER1-SPEC.md` rated it
**READY** and `docs/ROADMAP.md` called it 🟢 **M**; both mistook a UI-shape match for a backend match. The
truth: `OwnerScope` keys on `owner_id`, `message_threads` had no such column, so `BelongsToOwner` **could not
be applied** — the scope did not "fail closed", **it never ran**. An owner inbox over the model as it stood
would have been tenant-scoped only: **every guest conversation and every other owner's threads, visible to
any owner.** Invariant #26's exact shape. The column landed before a line of the screen.

**THE MINIMAL BACKEND** (confirmed at the gate; nothing else was added):
- **`message_threads.owner_id`** (nullable FK → owners, nullOnDelete) + **`BelongsToOwner` on MessageThread**.
  **`guest_id` XOR `owner_id` IS the discriminator** — deliberately NOT a `participant_type` enum, which
  would be a third source of truth able to disagree with the two FKs (the drift `RailLabel`/`OwnerOccupancy`
  were extracted to prevent).
- **`message_threads.owner_last_read_at`** — because `unread_count` is the **OPERATOR's** ("inbound since
  THEY last read") and `markRead()` zeroes it. An owner-side read touching it would **silently clear the
  operator's badge for work they have not seen**. Hence `InboxService::markReadByOwner()`, which touches only
  the timestamp. The owner's unread = staff replies after it.
- **`InboxService::postOwnerMessage()`** — extends, never reimplements. **Not `postInbound()`**: it runs the
  GuestMatcher (minting a **spurious Guest for the owner**) and is webhook-shaped (find-or-create by
  `external_thread_ref` = a silent wrong-thread risk). **Not `sendOutbound()`**: that is the operator's
  direction and hard-codes the `"Operator"` sender label.
- **`ManualChannel`'s docblock widened — behaviour unchanged.** "Recording IS the delivery" already described
  an in-app message exactly. A distinct `in_app` channel + adapter was considered and **rejected**: it would
  be byte-for-byte identical (reuse, don't duplicate). The enum names the MEDIUM; the FKs say who.
- **The operator inbox now NAMES an owner thread** — it said "Unknown sender", which was both ugly and wrong
  (the owner is the most precisely-known counterparty in the system). Required, or an owner messages into a
  void.

**THE BOUNDARY FALLS OUT OF THE SCOPE — nobody writes a predicate** (see invariant #18):
`/owner` → `owner_id IN (their ids)`, so a guest thread's NULL owner_id is excluded automatically ·
`/app` → OwnerContext inactive → unconstrained, so the operator sees everything and can reply ·
no owner link → `1=0`. Proven **symmetrically** (both owners, both directions, plus never-a-guest-thread and
cross-org) and **mutation-proven**: delete the trait and 4 tests fail, including the guest-thread leak.

**THE TRANSCRIPT IS MIRRORED, and that is the model being right, not a hack.** `messages.direction` is
ORG-relative (`inbound` = into the organization), so on an owner thread the OWNER's own messages are the
`inbound` ones and staff replies are `outbound`. The operator inbox reads the same rows the other way up.
**One column, two correct perspectives, nothing duplicated to drift.** Asserted, not eyeballed.

---

**⚠️ CUT — ARCHITECTURALLY OUT, *NOT* "DEFERRED POLISH".** The distinction matters: these are not things we
ran out of time for, they are things that **cannot exist in this shape**. The design's counterparty list was
three products in one rail:
- **"Vyrobel Support" / "Vyrobel Billing" — the SaaS VENDOR, not the operator.** Vyrobel is multi-tenant;
  `message_threads` is `organization_id`-scoped. **A thread to Vyrobel-the-company is not a tenant
  conversation at all** — it is cross-tenant vendor support, staffed by Vyrobel employees who are in no org's
  `users` table, spanning every tenant. It cannot live in this table without breaking tenancy. That is a
  helpdesk product (identity, routing, staffing, SLA), not a screen. **Do not "add it later" here.**
  (Its seeded thread also promised a *"Payouts → Preferences"* screen that does not exist.)
- **"Maintenance team" — a TEAM, not a user.** There is no user id to address or attribute a reply to; a
  team/queue counterparty is a second model (many recipients, assignment, no single author). The Maintenance
  module is already complete end-to-end and is where repair conversations belong.
- **BUILT INSTEAD: owner ↔ a named operator staff user** — **ONE real query** (the org's back-office members)
  replacing the design's **five fabricated directory entries**, which contradicted themselves inside a single
  file (`ana` was `online: true` in one list and `false` in the other).

**OMITTED POLISH — no backend, flagged with the reason, never faked** (~a third of the design):
- **"✓✓ Read"** — an **INVENTED ENUM VALUE**. `messages.delivery_status` is exactly
  received/queued/sent/delivered/failed; there is no `read`, no `read_at`, no receipt mechanism. Worse, the
  design **defaults every seeded message to it** (asserting staff have read everything the owner ever sent)
  **while omitting `failed`** — the one real state the built inbox honestly surfaces (#24). We render
  `failed` and no receipts.
- **Presence dots** — no presence exists anywhere (`users.last_login_at` is a login timestamp, not a
  heartbeat; there is no websocket layer).
- **"usually replies within an hour"** — not computed: the design picks between two strings using the
  fabricated presence boolean. There is no first-response metric and no SLA. It would make a **response-time
  promise on behalf of staff that the app cannot measure, let alone keep** — the Wave 1 stub-ADR/RevPAR rule.
- **Typing indicator** (a setTimeout on a fake bot) · **stars/reactions** (localStorage — per-browser,
  per-device, invisible to the operator) · **the pin** (persists **nowhere**, not even localStorage) ·
  **attachments** (`blob:` URLs that die on reload; the real `media`/`documents` polymorphs exist, but wiring
  them is its own gate) · **the canned auto-reply bot** — which would demo this as a **live, staffed,
  two-way system with presence and receipts**. It is the single most misleading thing in the file.

**CHEAP-WIN CHECK — nothing cleared the bar; SKIPPED deliberately rather than padding the wave:**
- **Per-thread favourite/mute.** The demo owner has **2 threads** — favouriting/sorting two things is
  meaningless. And **"mute" implies suppressing notifications that do not exist**: there is no notification
  backend (`app/Notifications/` does not exist; `Notifiable` is only Laravel's password-reset mail trait).
  That is the same class as the response-time metric — a promise the app cannot keep.
- **Static-prefill suggested chips.** Value is ~5 seconds of typing. And **2 of the design's 3 chips gesture
  at features the owner nav honestly marks "Coming soon" three inches away**: *"Can I block dates for
  personal use?"* (no owner calendar — `owner.calendar` does not exist) and *"Send me the tax summary"* (no
  tax centre — `owner.tax` does not exist). Shipping them means either the design's copy (misleading) or
  inventing product copy. Not worth it.

**THE OPERATOR SIDE IS COMPLETE — verified, not assumed.** Owner threads appear in the EXISTING operator
inbox, **named**, with the composer enabled (`manual` is a connected channel), and a reply through the real
POST path lands in the owner's portal as **theirs**, marked unread again. The full round trip works both
ways through the real UI; no operator-side affordance was needed.

**STILL STANDS:** the **inbox-permission gap** — the catalog has **no `inbox.*` permission**, so these
threads (like the guest inbox's six writes) are gated by **#26 + the owner scope** only. Naming a permission
is a product decision about who may speak to a guest/owner; still logged, still not invented. And **GDPR
#25**: `Guest::anonymize()` redacts threads **by `guest_id`**, so owner threads are untouched by it — correct
(an owner is not a guest; erasing a guest must not shred the owner's conversations), and stated so nobody
"fixes" it.

**Two more real bugs found by building:**
- **A same-second ordering ambiguity.** Two messages in one conversation routinely share a whole-second
  `created_at` — a reply seconds after a question is the NORMAL case, not an edge — so the "last message"
  preview could show the older one. Fixed with the repo's own ULID tie-break (`created_at DESC, id DESC`).
- **🎯 THE SECOND FLAKY TEST — root-caused, and the pair is now instructive.** `BackOfficeAccessTest`
  asserted `assertDontSee($unit->code)` where `seedScenario` codes units **`U2`** — *two characters* —
  against owner pages carrying **three random 40-char CSRF tokens** (`@livewireScripts`). `Str::random(40)`
  contains "U2" **~1.05% of the time** → **~15% per run** across 8 routes. Fixed with a leak-proof needle
  (`BRUNOUNIT9`), using the repo's own `FOREIGNUNIT9` prior art, and **proven deterministically**.
  **BOTH flakes were the same disease: testing the NEEDLE rather than the BEHAVIOUR** — a raw faker name vs
  escaped HTML, and a 2-char code vs random tokens. In both, the app was perfectly correct and the assertion
  was wrong. **When a test flakes, suspect the assertion's specificity before the app.**

Also: the owner shell gained an optional `subtitle` prop (mirroring `<x-operator.shell>`; the ten screens
that pass none are unchanged), the owner design system gained its first form + transcript CSS (built on OWNER
tokens, not copied from the operator layout), and `inboxOwner()`/`ownerThread()` live in `tests/Support` —
a function declared in one Pest file only loads with that file.

`DemoSeeder` seeds **two real owner↔operator threads through the real service** (one left unread, so the
badge and the Unread filter demo on genuine state).

## 2026-07-17 — WAVE 4 CORE built (Rates · New Reservation · backed Settings) — three gated sub-gates

**The three genuinely-buildable Wave 4 screens per `docs/WAVE4-INVENTORY.md`, each its own gate + commit,
each wiring an EXISTING certified service (never a parallel path) + only the small additions named below.
The other Wave-4 designs (Portfolio, Owner Team, Vyrobel Onboarding, Group Booking, Owner Rates,
Channels-as-drawn, Roles) are SUBSYSTEMS or account-gated — out of scope, see the register + the inventory.**

- **Sub-gate 1 — Operator Rates** (`/app/rates`; `RatesGrid` + `RateActionController`). The FIRST-EVER
  production caller of the certified-but-uncalled `RateAndRestrictionService` (`setRate`/`setRestriction`).
  **PER-ROOM-TYPE, not per-unit** (STRUCT-1: `rate_calendar` is keyed on `rate_plan_id`, so a per-unit grid
  would silently move every unit under a room type). A room-type × 14-day grid of real rates / availability /
  restriction badges + three derived tiles (avg rate PER CURRENCY, booked %, open nights — **no fabricated
  trend**; the design's "+8% vs Aug" has no baseline). **Integer money (#17):** the typed € is parsed to
  minor units by STRING arithmetic (`175.57 → 17557`, never `(int) round($x*100)` which drops a cent).
  Gated `can:rates.view` (page) / `can:rates.manage` (write) — both already in the catalog, correctly scoped
  (Property Owner / Group Admin / Hotel Manager / Revenue Manager, NOT Front Desk). The dead-enum
  channel-sync pills were omitted proactively.

- **Sub-gate 2 — New Reservation** (`/app/reservations/create`; `NewReservation` +
  `ReservationCreateController`). ONE direct booking through the certified single write path
  `AssignmentService::createBooking` (the no-double-booking guarantee is untouched — this screen never
  re-implements it), plus the three guards the audit demanded (the design is "SAFE as drawn: ❌"):
  **GUARD 1 honest availability** — a new `UnitAvailabilityService` mirrors createBooking's Level A
  (room-type `available()≥1` every night) + Level B (unit free of active `reservation_unit` / `inventory_block`
  overlaps) + priced; the controller re-derives it server-side (never trusts a client price), and the row
  lock stays the actual guarantee (a forced booking of a taken unit is rejected, nothing created).
  **GUARD 2 PII** — the existing-guest search is NAME-only (email/phone are encrypted, never rendered or
  queried); a new guest's contact details go to the encrypted columns, never echoed back.
  **GUARD 3 no raw PAN (PCI)** — there is NO card-number field anywhere; an optional payment is the MANUAL
  folio path (`FolioService::recordPayment` over `ManualPaymentGateway` — cash/bank), gated `payments.record`;
  card capture is stubbed "Soon" for the account-gated Stripe tokenization (asserted: no PAN column, card
  method refused, `card_token = null`). Gated `can:reservations.create`; separation of duties — an Agent may
  book but not take payment.

- **Sub-gate 3 — backed Settings slices** (`/app/settings`: `OperatorSettings` + `SettingsActionController`
  + `SettingsService`; `/owner/settings`: `OwnerSettings` + `OwnerProfileController`). The FIRST code in
  `app/` to write `properties` / `property_policies` / `organizations` config (only the seeder did before).
  **Operator + Property settings de-conflicted into ONE surface** (so tax/policy config has one write path,
  not two that drift). BACKED + wired: property name/timezone/star-rating/check-in-out/status, stay policies
  pet/smoking/min-max-stay, org currency/locale/billing-email. Per-section permission split:
  `can:properties.manage` (property + policies; Property Owner + Group Admin) / `can:settings.manage` (org;
  Property Owner only) — both existing, never Front Desk. Taxes/fees rendered READ-ONLY (real data; full CRUD
  is a later, larger surface). Unbacked design fields (branding/white-label, invoice numbering, notification
  routing) stubbed **"Soon", never a toast-only fake save** — the design's `saveGuard:()=>{}` trap.
  **Owner self-profile** (name + encrypted email/phone): the SAFE self-edit the audit named — `Owner` is
  `BelongsToOrganization` NOT `BelongsToOwner`, so `OwnerScope` does not scope it; the write is guarded by an
  EXPLICIT `where('user_id', auth id)` (no owner id anywhere in the request), proven by having owner A's edit
  leave owner B untouched.

**Shared extraction:** `App\Support\Money\MoneyInput::toMinor()` — the ONE string→minor-unit parser (no float
drift), used by both the rate and the payment writes; Sub-gate 1's `RateActionController` was refactored onto
it (one parser for every typed-money surface). Two Larastan relation generics added
(`RoomType::ratePlans`/`defaultRatePlan`, `Property::policies`) so `->first()` infers the model, not `Model`.
**No new permission was needed** — every gate reused an existing catalog permission (the inventory's
`channels.manage`-absent finding does not touch these three screens).

**Verification (all three gates):** suite **588 tests / 5946 assertions** green on MySQL; PHPStan L5 + Pint
clean; `migrate:fresh --seed` clean; each browser-verified with a REAL persisting write — Rates €100.00 →
€155.50 with the avg tile recomputing to a reconciling €141.98; a booking landing on the certified
Reservation Detail with folio Room +€300 / Payment −€150 / Balance €150 "reconciled to the folio ledger"; the
property star-rating and the owner's phone persisting across the PRG.

**KEPT in the register — Wave 4 CORE does NOT close these:** the **dead-enum channel-lifecycle**
(`channels.status` + `channel_mappings.status` are READ as ARI-push gates but written only by a test factory,
so the certified channel manager is INERT in production until a status writer exists — the Channels UI is that
missing writer, not "a thin CRUD controller") · the two payment/folio dead enums (`payment_status='refunded'`,
`folios.status='closed'`) · the two RBAC self-provision defects (Super Admin unassignable; no org-creation
path provisions roles) · no `inbox.*` and no `channels.manage` permission in the catalog.

## 2026-07-17 — CHANGE PASSWORD built (operator + owner) — the auth chapter is complete for the logged-in user

**The one genuinely quick-buildable auth screen from `docs/AUTH-INVENTORY.md` — a logged-in operator or owner
changes their OWN password from within the app.** One gate, reusing the existing reset machinery; no password
crypto reimplemented.

- **The shared path (so reset + change can't drift):** extracted `User::changePasswordTo(string)` — hash (via
  the `password` 'hashed' cast) + rotate `remember_token` (`Str::random(60)`) + save — and refactored
  `PasswordResetController::reset()` ONTO it. RESET and CHANGE now go through ONE hash+rotate path.
- **The controller:** `Auth\ChangePasswordController` (operator + owner methods → a shared `change()`). The
  user is resolved from `auth()` ONLY — no user id anywhere in the request — so a session can only change its
  OWN password (an operator can't touch an owner's and vice-versa; proven by test). 🔒 The **current password
  is verified** (`current_password` rule → `Hash::check`) BEFORE any change — the anti-hijack guard; a wrong
  current password changes nothing (proven). New password meets the reset rules (`min:8`, `confirmed`).
  Rate-limited (`throttle:6,1`) + CSRF + PRG.
- **Session behaviour (matches reset):** the current device stays signed in; rotating the remember token
  invalidates any "remember me" cookie on OTHER sessions. (`Auth::logoutOtherDevices` would additionally end
  other *active* sessions but needs the `AuthenticateSession` middleware — out of scope; the remember-token
  rotation is the reset-flow baseline.)
- **Where it lives:** a lean operator **`/app/security`** page (`OperatorSecurity`) behind `auth` + #26 with
  **NO fine-grained gate** — because `/app/settings` is `properties.manage`-gated (managers only) and EVERY
  back-office user must be able to change their own password (a Housekeeping user reaches `/app/security` but
  NOT `/app/settings` — proven). On the owner side, a change-password card on the existing **`/owner/settings`**
  (every owner reaches it). The operator shell's "Security" nav is now live.
- **Honesty:** the design's Security screens also draw 2FA, active sessions, connected apps and IP rules — all
  **unbacked subsystems** (still 🔴 in `docs/AUTH-INVENTORY.md`) — shown as an honest "Soon", never faked.

**Verification:** `tests/Feature/ChangePasswordTest.php` (9 — happy path + remember-token rotation, wrong
current rejected/unchanged, mismatch + too-short rejected, `auth()`-resolution [A changes, B untouched], owner
side, auth-required, the non-manager reachability of `/app/security`, and the shared path). Suite **597 tests /
5986 assertions** green (the reset flow is unregressed by the refactor); PHPStan L5 + Pint clean;
`migrate:fresh --seed` clean; **browser-verified BOTH sides end-to-end** — change → log out → log in with the
new password (operator + owner).

**The auth chapter is now complete for the logged-in user** (login · forgot/reset · invite/set-password ·
**change password**). The remaining auth items stay **BLOCKED subsystems** (per `docs/AUTH-INVENTORY.md`): SaaS
self-signup / tenant-provisioning, 2FA (TOTP), the whole Guest Portal auth, sessions/connected-apps/IP-rules.

## 2026-07-18 — TWO-FACTOR AUTHENTICATION (TOTP) built — enrollment + recovery codes + login challenge (invariant #31)

**2FA is now a complete, opt-in, hardened subsystem — the first item lifted off the auth BLOCKED list, because it
was never account-gated, only unbuilt** (no third-party credential involved — just TOTP maths a user's own
authenticator app runs). Built in three gated steps (`031716a` enrollment · `aca017c` challenge · this consolidation).
No hand-rolled crypto: `pragmarx/google2fa` (TOTP) + `bacon/bacon-qr-code` (QR SVG); NOT Fortify (it would collide
with this repo's bespoke, hardened auth chapter).

- **The single source of truth is `User::hasTwoFactorEnabled()`** = `two_factor_secret !== null && two_factor_confirmed_at !== null`. The legacy `two_factor_enabled` boolean is a **display mirror only** and is NEVER the gate (it cannot drift the decision).
- **Enrollment is confirm-before-activate (anti-lockout).** `TwoFactorService::startEnrollment()` stores a secret with `two_factor_confirmed_at = null` — which `hasTwoFactorEnabled()` treats as NOT active, so a generated-but-unconfirmed secret never gates login. Only `confirm()` with a **valid 6-digit code** flips it on and issues the recovery codes. An invalid confirm code activates nothing (mutation-proven).
- **🔐 ENCRYPTED AT REST — and this fixed a pre-existing latent exposure.** `two_factor_secret` was a **plaintext** column before 2FA was wired; it is now `encrypted`, and `two_factor_recovery_codes` is `encrypted:array`. Proven by asserting ciphertext in the raw DB column. (Larastan sees only the encrypted string column, so `User` carries an accurate `@property list<array{code,used_at}>|null` hint — a real type annotation, not a silencing cast.)
- **Recovery codes: 8, shown ONCE, single-use.** Stored as `{code, used_at}`; a challenge consumes a match by stamping `used_at` (constant-time `hash_equals`); regenerate invalidates the whole prior set. A used code cannot log in again (proven at rest + in the browser).
- **Disable requires re-auth.** Turning 2FA off (while active) requires the **current password** (`current_password` rule) — an open session cannot silently strip it. Cancelling an unconfirmed *pending* enrollment needs no password (there is nothing to protect yet).
- **Self-only.** Every operation resolves the user from `auth()` / `$request->user()` — never a request-supplied id — so a session can only touch its OWN 2FA (operator ↔ owner isolation proven).
- **THE LOGIN CHALLENGE — the load-bearing change to the login flow.** `LoginRequest::authenticate()` now uses **`Auth::validate`** (verify the password, establish NO session) instead of `Auth::attempt` (which fuses verify+login). `LoginController::store()` then decides: a non-2FA user is logged in immediately (`Auth::login` — validate+login is **equivalent** to the old single `attempt`, so that path is **unregressed**); a 2FA user is held in a **PENDING** state and redirected to `/two-factor-challenge`. The challenge accepts a TOTP **or** a single-use recovery code; success runs `Auth::login` + session `regenerate()` (fixation) + routes by role. Invalid codes are rejected and **rate-limited** (keyed on the pending user + IP — a 6-digit code is brute-forceable otherwise; the throttle blocks even a valid code once tripped).
- **⚠️ THE PENDING STATE GRANTS NOTHING — BY CONSTRUCTION, not by a new check (invariant #31).** The "2FA pending" marker is a **plain session key** (`login.2fa.user_id`), **NEVER the auth guard**. So a password-verified-but-code-pending user is not `Auth::check()`-true, and `/app` + `/owner` (behind `auth`) reject them with **no new authorization code written** — the existing middleware does it. `RequireTwoFactorPending` only fences the challenge PAGE (no pending state → back to login; already authenticated → bounced). **This is the whole reason the flow is safe:** we relied on the guard already there rather than inventing one that could be forgotten.

**Verification.** `TwoFactorEnrollmentTest` (12) + `TwoFactorChallengeTest` (8) — the challenge file **mutation-proves** the security property: a 2FA user who gives the *correct password* cannot reach `/app` or `/owner` until a valid code; valid TOTP completes; valid recovery code completes **and is consumed** (no reuse); invalid is rejected + throttled (a valid code is blocked past the limit); a wrong password never enters the pending state; a **non-2FA user logs in unchanged** (no regression). Full suite **617 tests / 6100 assertions** green; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean. **Browser-verified end-to-end BOTH portals** (operator + owner): enroll → recovery codes → log out → TOTP-challenged login → recovery-code login → disable-with-re-auth (wrong password rejected, 2FA stays on; correct password turns it off) → subsequent login passes straight through. Demo accounts reset to password-only afterward, so the documented demo login is unchanged.

**The auth chapter now includes 2FA.** Remaining BLOCKED auth subsystems (per `docs/AUTH-INVENTORY.md`): SaaS self-signup / tenant-provisioning, the whole Guest Portal auth, and sessions / connected-apps / IP-rules (all still honest "Soon").

## 2026-07-18 — CAPSTONE SESSION CONSOLIDATION (docs only, NO code) — the buildable core is complete; next is customer validation

**A total-recall consolidation at the close of a very large session**, refreshing `docs/PROJECT-STATE.md` +
`CLAUDE.md` to the true current state and adding this dated marker. **No code changed** — this entry records
where the product stands, not a new decision.

- **Ground truth (verified for this capstone):** HEAD `11288d6`, on `main`, **local == origin, tree clean**.
  **`php artisan test` → 617 tests / 6100 assertions, all green** on MySQL 8 (`vyrobel_test`). **PHPStan L5, 0
  errors. Pint clean.** (The doc commit carrying these words advances HEAD by one — the HEAD convention.)
- **The arc this session covered** (all already recorded in their own dated entries above; this is the index):
  Wave 1 (owner-accounting long-tail, 10 screens) → the `/app` authorization fix (**#26**) → the cumulative
  over-refund fix (**#27**) → Wave 2 (reservations + Guests CRM, 5 screens; the `ReservationStage`/`GuestSpend`/
  `GuestHistory` extractions; the notes-redaction GDPR fix) → the security register cleared (`terminate()` guard
  **#29**, the enforced permission catalog **#30**, the `anonymize()` UI trigger **#28**; both flaky tests
  root-caused) → Wave 3 (the owner inbox; `owner_id` + `BelongsToOwner`, **#18** extended) → Wave 4 CORE (Rates,
  New Reservation, Settings; `MoneyInput`, `UnitAvailabilityService`) → **Change Password** → **2FA (TOTP)**,
  invariant **#31**. **Five security/integrity holes closed this session, every one found by READING, not by a
  failing test.**
- **The honest state, recorded plainly:** the buildable-in-a-vacuum, clearly-worth-it, self-contained core is
  **essentially complete — 2FA was the last such subsystem.** What remains is **① subsystems/epics** (real
  gated builds: Portfolio, Owner Team, tenant-provisioning, Group Booking, Owner Rates, Channels-as-drawn,
  Roles, guest auth, sessions/apps), **② speculative modules** (Guest Portal ~24, Analytics, Reviews, Billing,
  Notifications, Emails — designed, zero backend, **no customer signal**), and **③ account-gated** (real inbox
  channels, Channex live, Stripe Connect, Stripe card checkout, prod email). Plus the small **still-open register
  items** (dead enums; no inbox permission; two fail-closed RBAC defects; and — recorded this session — **the
  channel manager is inert in production because nothing writes `channels.status`/`channel_mappings.status`**,
  verified by grep).
- **THE NEXT MOVE IS NOT A BUILD — it is CUSTOMER VALIDATION.** The product demos at full depth and is safe for
  a real owner login; **no customer has ever reacted to it.** An intro email is out to a warm contact; a real
  reaction should DIRECT which ① epic or ③ integration is worth committing to, rather than betting blind. **More
  building has diminishing returns until the signal comes in.** Full tagged triage + the invariant list #1–31 +
  the meta-lessons live in `docs/PROJECT-STATE.md`; read it first on a new session, then this file.

## 2026-07-20 — CUSTOMER SIGNAL ARRIVED + the master GAP ANALYSIS (audit-verified) — the plan is now "build it all, like the prototype"

**The "no signal, don't build blind" framing above is SUPERSEDED.** A warm operator contact (Fatima) **liked the
demo AND the prototype**, and the requirement is now concrete: **the real product must work the way the prototype
shows.** The prototype (all 124 designed screens) is the SPEC for what each screen shows; the repo stays the truth
for what backs it; every gap gets BUILT (not faked). The full phased map is a new durable doc, **`docs/GAP-ANALYSIS.md`**.

- **This was NOT taken on faith — the plan was audit-verified against real code before being recorded** (a
  5-agent verification pass, 2026-07-20, at HEAD `c34669a`; 50 repo-checkable claims → 33 CONFIRMED, 8 INCORRECT,
  8 NUANCED). Meta-lesson (a) held: the plan's structure and every large gap it named were TRUE (guest auth,
  notifications, reviews, wallet, tenant-provisioning, group booking — all verified zero-backing), but the audit
  corrected three things, now baked into `GAP-ANALYSIS.md`:
  - **⚠️ MULTI-PROPERTY IS NOT A SCHEMA EPIC (the consequential correction).** The schema already supports N
    properties/org — `properties.organization_id` is a plain FK, the `property_user` pivot + `User::properties()`/
    `Property::staff()` relations exist, and `property_id` is already on `channels`/`groups`/`rate_plans`. The
    limitation is a UI convention: ~6 files / ~9 call sites resolve "the property" as
    `Property::query()->orderBy('name')->first()` (`FrontDesk`, `ReservationRack`, `OperatorDashboard`,
    `NewReservation`, …). **Portfolio (Phase 6) re-scoped from an L-subsystem to an M** (a property selector/context
    + a property-CRUD path + refactoring those call sites).
  - **CORRECTED COUNTS.** ~40 built screens/artifacts (not "38–41 loosely"), ~84 designed-but-unbuilt. The plan's
    per-front counts double-counted **terminal states** (Reservation Cancelled/No-show are states on Reservation
    Detail, not screens), **POST sections** (Property Settings is a section of the one `/app/settings` screen), and
    a nonexistent separate **owner Security screen** (change-password + 2FA live on `/owner/settings`); and it
    MISSED **Units** (built, styled, no design file). **Guest Portal = ~24** (the authoritative ROADMAP module
    count), not 32 (that's the design-export file count incl. the 4 already-built public-booking screens).
  - **NUANCES baked in:** the generic `settings` KV table + `Setting` model are DORMANT (future settings screens
    follow the typed-column `SettingsService` pattern, not the KV scaffold); maintenance has a hard-coded 5-value
    `category` enum already (only SLA + *configurable* categories are missing); a property-scoped availability
    search exists (`BookingEngineService::search`) — the gap is GLOBAL cross-entity search + the blind-index
    decision for encrypted guest contact.
- **The three TRUE greenfield subsystems the prototype assumes** (verified absent, zero backing): **guest auth**
  (`Guest extends Model`, not `Authenticatable`), **notifications** (no table, no `app/Notifications/`, zero
  `->notify()`), and **reviews + wallet/credits** (no tables). Multi-property is NOT one of them.
- **NEXT ACTION is now the phased build in `docs/GAP-ANALYSIS.md`, starting PHASE 0 (fix & harden):** the inert
  channel manager (a real `channels.status` writer + connect skeleton + demo seed — prove the ARI push fires), the
  dead-enums decision, the two RBAC defects, the stale-docblock cleanup, and a QA walkthrough script
  (`docs/QA-WALKTHROUGH.md`). Sequencing bias: **make Fatima operational soonest** (Phases 0–3 before the large
  Guest Portal phase). PROJECT-STATE + CLAUDE NEXT ACTION updated to point here.

## 2026-07-20 — PHASE 0 (fix & harden) COMPLETE — five gated sub-gates; the product no longer lies to itself

The first phase of `docs/GAP-ANALYSIS.md`, done as five gated sub-gates (per-gate commits, mutation-proven where
there's a guard, browser-verified where there's a screen). Goal: close what was silently broken BEFORE building
new features on top of it.

- **0.1 — the inert channel manager, fixed (`8e3b54c`).** `App\Services\ChannelConnectionService` is the real
  `channels.status` / `channel_mappings.status` writer that was missing: `EnqueueAriPushOnInventoryChange` pushes
  ARI only to `status='connected'` channels (mapping guard only includes `status='active'`), but nothing in
  `app/` ever wrote either — only factories — so the certified sync backbone could never fire for a real
  operator. The writer records audit + domain events inside the service; the `channel.*` events are NOT in the
  ARI trigger set (no loop). The local activation is real; the live Channex handshake stays account-gated behind
  the `ChannelManagerClient` seam. `DemoSeeder` now seeds a connected channel + active mappings.
  **MUTATION-PROVEN** (`ChannelConnectionTest`): a disconnected channel enqueues NO push on a booking; after
  `connect()` an equivalent booking enqueues + processes exactly one. The Channels UI that drives this in
  production (+ `channels.manage`) is Phase 1.
- **0.2 — the dead status enums, resolved per enum (`88b2326`).** `reservations.payment_status='refunded'` is
  now genuinely WRITTEN (`FolioService::paymentStatus()` — a fully-refunded stay, so it no longer reads the small
  lie 'unpaid'); `'charged_back'` dropped (no dispute flow). `folios.status` `'closed'`/`'void'` dropped (no
  close/void lifecycle; a half-built lock would be a worse lie). A migration prunes both enums (safe — zero rows
  carried a removed value). Mutation-proven + a `SHOW COLUMNS` assertion that no unwritable value remains
  (`DeadEnumResolutionTest`). One harmless remnant (`payments.status='refunded'`) documented, out of scope.
- **0.3 — the two fail-closed RBAC defects, re-logged + formally deferred (`863c5b1`).** Neither has a code path
  to fix today, so (the sanctioned outcome for a fail-closed defect) each is deferred with reasons to the gate
  that builds its fix: defect #1 (no org-creation provisions roles) → Phase 7's tenant-provisioning transaction
  MUST call `OrganizationRoleProvisioner::provision()`; defect #2 (`Super Admin` unassignable) → a spatie
  `model_has_roles` schema migration. A global `Organization::created` hook for #1 was CONSIDERED and REJECTED
  (it would add provisioning overhead + shared-permission-table contention to 90 org-creating test files, incl.
  the true-parallel concurrency suites, to guard a path that doesn't exist). The provisioner's docblock, which
  falsely claimed it was "called at organization-creation time", is now honest + loud.
- **0.4 — stale "2FA is Soon" docblocks fixed (`18f4193`).** `OperatorSecurity`'s docblock still called 2FA an
  unbacked "Soon" panel (and change-password its "ONE real feature"), and the owner-settings Blade comment
  listed 2FA among stubbed subsystems — both contradicted by the shipped real TOTP (invariant #31). Corrected;
  the genuinely-unbacked panels stay honestly "Soon". Comment-only.
- **0.5 — the QA walkthrough (`b7b36fd`).** `docs/QA-WALKTHROUGH.md`: a run-it-yourself runbook over every built
  screen, every role — public booking, all 21 operator + 15 owner screens, the security-boundary checks
  (#26/#28/#30 + cross-org 404), the full auth chapter, a role matrix, and a per-surface "Soon" register so
  honest deferrals aren't logged as bugs.

**Verification:** full suite **625 tests / 6125 assertions** green on MySQL (Phase 0 added +8: the 5 channel-
connection + 3 dead-enum tests); PHPStan L5 + Pint clean; `migrate:fresh --seed` clean (demo channel connected +
mappings active, enums pruned); the money screens browser-verified (render intact after the 0.2 docblock edits).
**NEXT = Phase 1** (complete the operator app: Search, Group Booking, Roles, Maintenance Costs/Settings, Operator
Profile).

## 2026-07-20 — PHASE 1 (complete the operator app, buildable set) COMPLETE — five gated sub-gates

The second phase of `docs/GAP-ANALYSIS.md`, done as five gated sub-gates (audit-first · per-gate commit ·
mutation-proven where there's a guard · browser-verified where there's a screen · honesty rule · integer money ·
real enums · tenant/self isolation fail-closed). Six operator screens shipped; the operator app's buildable-set is
now complete (operator 21 → 27 built). **Push held to this final gate** (the five commits landed together). The
recurring lesson: **every one of these screens had a security-critical shape the design did NOT show, and the audit
is why we know** — building any of them as drawn would have shipped a hole.

- **SG1 — Group Booking (`eefd22d`).** `GroupBookingService` + `AssignmentService::createGroupBooking()` — an
  ATOMIC multi-unit block over the EXISTING `groups` + `reservations.group_id` (extend, never a parallel
  per-channel table; `source='group'`). The security-critical decision is the **phantom-event guard**:
  `DomainEventRecorder::record()` dispatches `DomainEventRecorded` SYNCHRONOUSLY, so the group's
  `reservation.confirmed` events are recorded ONLY after the outer transaction commits (one per persisted member)
  — otherwise a rolled-back block would fire ARI-push/recompute listeners for reservations that never existed.
  Prices are re-derived server-side (integer minor units via `UnitAvailabilityService::availableUnitMap`, never a
  client float); the organiser's PII goes to the encrypted `Guest` columns; the write is gated `can:reservations.create`.
  **MUTATION-PROVEN** (`OperatorGroupBookingTest`, 6): pre-book a unit, attempt an overlapping group → the whole
  block rolls back (0 groups persisted) AND the `domain_events` count is UNCHANGED — no phantom event escaped.
- **SG2 — Global Search (`1595583`).** `SearchService` — a GLOBAL cross-entity search (reservations by
  reference/lead-guest name · guests · units · owners), tenant-scoped by construction. The security/honesty
  decision: **guests are searched by NAME only.** `guests.email`/`phone` are `encrypted` casts → the ciphertext is
  not `LIKE`-searchable, so a contact-string search is impossible without a blind index. Rather than silently
  return nothing for an email query, the screen DETECTS a contact-looking query (`looksLikeContact()`) and surfaces
  the limitation. **The blind-index decision is DEFERRED** (it's a schema commitment — a hashed index column per
  searchable field); logged, not faked. Per-entity results are gated on the viewer's abilities
  (`guests.view`/`units.view`), the route on `can:reservations.view`. Proven tenant-isolated + name-not-email
  (`OperatorSearchTest`, 8).
- **SG3 — Roles (`35019e9`) — the security-critical gate.** `RoleAssignmentService` — VIEW the fixed permission
  catalog + ASSIGN/REVOKE a catalog role, gated `can:users.manage`. **The design as drawn was an escalation
  weapon** (New role / Save / Delete = edit the catalog itself) and was **REFUSED** — the catalog is read-only.
  Assignment enforces the **privilege-subset rule**: an actor may only assign a role whose permissions they
  already hold IN FULL (`perms(role) ⊆ perms(actor)`), so a Group Admin cannot grant `Property Owner` (`['*']`) —
  only `['*']` grants `['*']`. Plus: no self-management, tenant-bound target, catalog-roles-only. spatie is
  team-scoped (team = org); `setPermissionsTeamId($orgId)` around every read/write. **MUTATION-PROVEN**
  (`OperatorRolesTest`, 7): a Group Admin's attempt to assign `Property Owner` throws `RoleAssignmentException`
  and grants nothing. (This gate needed Phase 0.3's RBAC re-log as its backdrop — the two fail-closed defects stay
  deferred to their fix gates.)
- **SG4 — Maintenance Costs + Settings (`a4a3ddc`).** `MaintenanceCostService` — every figure a REAL derived
  aggregate over resolved tickets (total · orders · avg · by-category · top-units · by-month · owner-unit vs
  operator-unit split from `units.owner_id`), reconciling to the cent; an empty period is `—`, never a fabricated
  €0.00. **The prototype's labour-vs-parts split was REFUSED, not faked** — the schema holds ONE `cost_minor` per
  ticket, no breakdown to render. `MaintenanceSettingsService` + a new `maintenance_settings` table persists SLA
  hours, category→technician assignees (tenant-validated — a cross-org technician is dropped), and toggles as
  **TYPED COLUMNS via the `SettingsService` pattern — NOT the dormant KV `Setting` scaffold** (the audit's
  standing rule). Notification toggles STORE now; dispatch is honestly **deferred to Phase 3**. `MaintenanceSetting`
  registered in the morph map (it's an audit subject). Both gated `can:maintenance.manage`. Proven
  (`OperatorMaintenanceAdminTest`, 6): aggregates reconcile, typed persistence (not toast-only), cross-org
  assignee dropped, gate holds for Front Desk.
- **SG5 — Operator Profile (this gate).** `OperatorProfileController` — the logged-in operator edits their OWN
  name + work email, via POST → controller → PRG (a REAL persist on the `users` row, not a toast-only save).
  **SELF-ONLY, and the guard is the resolution itself:** the user is `$request->user()` (the authenticated login)
  and there is NO user id anywhere in the request — so there is no id to tamper with to reach another account (the
  change-password / owner-self-edit discipline). `email` is the login identifier (plaintext on `User`, unlike
  encrypted guest PII) and is kept UNIQUE-ignoring-self. Role / status / member-since render read-only; the
  design's unbacked fields (phone / preferred-name / timezone / density) are **OMITTED, not faked** (no column →
  no fake save). Behind auth + #26 with NO fine-grained gate (like `/app/security` — every back-office user has a
  profile). **MUTATION-PROVEN** (`OperatorProfileTest`, 8): a smuggled `user_id`/`id` in the body is ignored (only
  the actor's row changes, the victim's is untouched); email uniqueness; name required; an owner-role user is
  refused (#26 → `owner.dashboard`).

**Deferred out of Phase 1 (logged, not faked):** the **blind-index** contact-search decision (SG2 — a schema
commitment), **notification dispatch** for the maintenance toggles (SG4 → Phase 3's notification subsystem), and
**PDF/Excel exports** of the cost report (SG4 — the figures are real; only the export is deferred).

**Verification:** full suite **660 tests / 6278 assertions** green on MySQL (Phase 1 added +35 across the five
gates); PHPStan L5 + Pint clean; `migrate:fresh --seed` clean; the Operator Profile self-edit browser-verified end
to end (edit name → PRG → the new value round-trips to the topbar chip, identity card AND the form field; role /
status / member-since shown; nav highlights "Your profile"; no raw Blade). **NEXT = Phase 2** (complete the owner
portal's buildable set: Bookings + Detail · Block Calendar · Owner Rates · Tax Center · Payout Method · Add Unit ·
Help).

## 2026-07-20 — Owner Rates: DIRECT edit, no approval (conscious divergence) + the exclusivity guard (Phase 2 · SG3)

Recorded at the gate (the full Phase-2 consolidation lands at SG6). Two decisions worth pinning now:

- **DIRECT edit, NO approval — a CONSCIOUS DIVERGENCE from the design.** The "Owner Rates" prototype is an
  approval/guardrails model: the manager *proposes* rate changes, the owner *approves / declines / counters*, and
  sets floor/ceiling guardrails ("Every change needs your sign-off before it goes live"). The product decision is
  **FULL DIRECT edit** — the owner sets the rate and it is live immediately. There is NO proposal / approval /
  guardrail schema, so those sections (Proposed changes, Guardrails, comp-set, Base/Weekend/Peak tiers) are NOT
  rendered as real and NO fake "pending sign-off" state is shown. An operator-approval workflow would be a clean
  additive layer later (a proposals table + an approve step in front of `setRate`). Recorded so the divergence is
  a decision, not an omission.
- **THE OWNER-SCOPE GUARD ON A PER-ROOM-TYPE MONEY WRITE — exclusivity.** Rates key on rate_plan → room_type, and
  a room type can hold units owned by different parties, so an owner may edit a room type's rate ONLY IF they own
  it EXCLUSIVELY (every unit in it is theirs). The editable set is resolved through ownership
  (`OwnerRateService::editableRoomTypes()`: owner-scoped units → their room types → drop any with a foreign unit),
  never from a request id; a write outside it throws `OwnerRateException` → 403 with nothing written. This is both
  the read-isolation set and the write allow-list — mutation-proven both ways (removing the guard lets owner A set
  owner B's rate AND lets A edit a room type shared with the operator; both 2/2 fail without it). Wired through the
  certified `RateAndRestrictionService` (`setRate`/`setRestriction`) — the SAME path the operator screen drives;
  `RoomType::primaryRatePlan()` was extracted so both screens agree on which plan a room type's rate lives on.
  Integer money via `MoneyInput` (175.57 → 17557, string arithmetic, no float).

## 2026-07-20 — PHASE 2 (complete the Owner portal, buildable set) COMPLETE — six gated sub-gates

The third phase of `docs/GAP-ANALYSIS.md`, done as six gated sub-gates (audit-first · per-gate commit ·
mutation-proven guards · browser-verified · honesty rule · integer money · owner isolation fail-closed). Eight
owner screens + one operator screen (Unit Requests) + the Annual Tax PDF; the owner portal's buildable-set is now
complete (owner 15 → 23 built). **All six commits pushed together at this final gate.** The through-line: **owner
isolation is the #1 concern of the whole phase, and every gate proves it a different way.**

- **SG1 — Owner Bookings + Booking Detail (`0dfc560`).** Owner-scoped THROUGH the units: reservations carry no
  `owner_id` (owner A + owner B share the operator's ORG), so the set is derived from the owner-scoped
  `OwnerPortalService::units()`, and Booking Detail 404s a `{booking}` id unless it touches an owned unit —
  mutation-proven (disable the guard → owner B's booking leaks at 200). "Net to you" flows through the ONE new
  shared `App\Support\Owner\OwnerLedgerAttribution` rule (net-per-currency + gross→net breakdown), and
  `OwnerUnitDetail` was refactored onto it so Unit Detail / Bookings / Booking Detail / the statement can't drift.
  Guest NAME only (invariant #28); real source enum (OTA, never a brand); stage via the shared `ReservationStage`.
- **SG2 — Owner Block Calendar (`598cc9b`).** The owner holds/releases dates on their OWN units via the certified
  `AssignmentService` (single inventory write path), never a parallel system. The owner-scope WRITE guard
  (`OwnerBlockService::ownedUnitOrFail`, resolved from owned units, never a request id) is mutation-proven for
  BOTH block and release. ⚠️ We ALSO closed a latent gap: `blockInventory` now calls `assertUnitFree` for
  unit-level blocks — the overlap enforcement the migration + model docblocks always promised but never actually
  ran — safely (existing block + the 20 concurrency tests stay green). `reason='owner'` (real enum) + the granular
  reason in `notes`; unblock reuses the already-wired `inventory_block.released` event; never touches `sold`.
- **SG3 — Owner Rates (`58f0b49`) — the phase's highest-risk gate: an owner-scoped MONEY write.** FULL direct
  edit via the certified `setRate`/`setRestriction` (same path as the operator; `RoomType::primaryRatePlan()`
  extracted so both agree). ⚠️ Because rates are per-room-type and a room type can hold units of different owners,
  the guard is **exclusive ownership**: `editableRoomTypes()` keeps only room types where EVERY unit is the
  owner's (drops any with a foreign unit) — resolved through ownership, never a request id. Mutation-proven BOTH
  ways (cross-owner write AND the subtle shared-with-operator case both 403 without it). **DIRECT edit, NO
  approval — a conscious divergence** from the design's approval/guardrails model, recorded (the design's
  Proposed-changes/Guardrails/comp-set/tiers have no schema and are NOT faked as "pending"). Integer money.
- **SG4 — Tax Center + Annual Tax PDF (`5119548`).** Self-only (owner by `user_id`), `tax_id`/`vat_number`
  ENCRYPTED at rest (asserted ciphertext; audit records fields not values, #28). The payout HOLD is GENUINE, not
  a label: `OwnerPayoutService::pay()` throws `PayoutHeldException` before the rail send for a tax-incomplete
  owner and releases on save — mutation-proven (disabling it lets a tax-incomplete owner be paid). No regression:
  a `tax_id` default on `OwnerFactory` keeps factory owners tax-complete. Real annual aggregation reconciling by
  construction (a statement's buckets sum to its net); withholding omitted (no engine), not faked.
- **SG5 — Payout Method (`b95228f`).** New `payout_methods` (IBAN ENCRYPTED + never rendered; masked ···· last4
  from cleartext `iban_last4`/`iban_country` so a list never decrypts the whole number). Self-only mutation-proven
  across read / set-default / remove (owner-scoped resolve → cross-owner 404). Real ISO 7064 mod-97 checksum +
  country/last4 from the string; the bank NAME is NOT fabricated (no registry — the owner labels it). Live Stripe
  Connect rail deferred honestly — no fake Verified / micro-deposit / schedule / next-payout-date.
- **SG6 — Add a Unit (request flow) + Help (this gate).** ⚠️ POLICY (stated): an owner does NOT create billable
  inventory directly — Add Unit files a `unit_requests` record (owner-scoped, pending, self-only) the operator
  reviews; a new operator `/app/unit-requests` screen (tenant-scoped, gated `units.view`) is the visibility side;
  the operator's actual unit-creation stays their own manual action (not built). `requested_kind` is the REAL
  `units.unit_kind` enum (no invented types). Help is a STATIC FAQ linking to the real owner screens + the ONE
  real contact route ("Message your manager" → the Wave-3 owner↔operator inbox) — the design's AI Q&A, support
  TICKET queue, article counts and system-status board are unbuilt subsystems and are NOT rendered (no faked
  tickets/chat/status).

**Verification:** full suite **697 tests / 6468 assertions** green on MySQL (Phase 2 added +41); PHPStan L5 + Pint
clean; `migrate:fresh --seed` clean (4 new migrations: group-booking fields were Phase 1; here tax-profile fields,
payout_methods, unit_requests); every screen browser-verified on the demo owner/operator. **NEXT = Phase 3** (the
notification subsystem + transactional emails).

### 2026-07-20 — Phase 3 COMPLETE: the notification subsystem + all transactional emails (5 gated sub-gates)

**The whole chapter is done and pushed.** Events → dispatcher → per-user in-app feeds + genuinely-gating
preferences → the real events wired → the guest transactional emails (Mailpit-real) → owner/operator email
delivery through the same gate → the Email Templates view. Commits `7cca65c` → `7905570` → `640f149` →
`6c51271` → this gate.

- **SG1 — notification CORE (`7cca65c`).** Laravel-native (`Notifiable` + the `notifications` table + a
  `FeedNotification` base). A `DispatchNotificationsForDomainEvent` listener turns REAL `DomainEventRecorded`
  events into per-user database notifications; operator = the org's back-office staff (`NotificationRecipients`),
  owner = the ONE owner via their linked user. **ISOLATION mutation-proven** (a user never reads/marks another's).
  ⚠️ **The sync event bus DOUBLE-DELIVERS** (listeners are auto-discovered AND manually registered) — the
  idempotent inventory listeners masked it; a notification must not double-fire, so a static `$processed` guard
  keyed on the domain-event id makes each event produce its notifications exactly once. Bells + feeds in both
  shells.
- **SG2 — Notification Preferences (`7905570`).** A TYPED `notification_preferences` store (constrained
  `event_key` + `channel` enum + bool), NOT the KV scaffold. `NotificationPreferences` = the fixed CATALOG + THE
  GATE `allows()`, called from `via()` BEFORE delivery. 🪤 **Preferences GENUINELY gate — mutation-proven** (an
  off toggle drops the channel → nothing is written; bypass it and the off-case delivers → test fails). Self-only
  (audience from the route default, never a request id — a user can't change another's). **Always-on** (Account &
  security) can't be disabled — `save()` forces it + `allows()` short-circuits + the UI locks it, all three agree
  (mutation-proven). Resolved the Phase-1 maintenance deferral: `maintenance_ticket.opened` (urgent) gated by the
  org `MaintenanceSetting.notify_new_urgent` AND the per-user pref (two-layer, proven).
- **SG3 — wire the real events (`640f149`).** Audit-first: enumerated the ~40 event types that actually fire
  before wiring. Wired `reservation.checked_out` (folio balance > 0) → BalanceDue · `unit.condition_changed`
  (→out_of_service) → UnitOutOfOrder · `payout.paid` → owner PayoutSent **+** operator OwnerPayoutSent ·
  `owner_statement.finalized` → owner StatementReady **+** operator StatementFinalized · `channel.error` →
  ChannelSyncError · `owner.payout_method_changed` → account-security (always-on). **One service change, stated:**
  `OwnerPayoutMethodService` recorded audit-only — it now ALSO records the `owner.payout_method_changed` DOMAIN
  event (the #events invariant: a state change writes BOTH rows; payload carries only WHICH change, never the
  IBAN). **DEFERRED honestly** (no backing event today, NOT wired): arrivals/housekeeping-behind/SLA/daily-summary
  (need a scheduler) · new-review (Phase 5) · new-device sign-in (needs device detection) · performance-digest
  (needs an aggregator) · terms-changed (no agreement-change event exists). Balance>0 guard + owner isolation both
  mutation-proven; an unmapped real event (`reservation.cancelled`) yields nothing (we defer, never invent).
- **SG4 — guest transactional emails (`6c51271`).** Nine email-safe Mailables on a shared brand layout (the
  owner-invite system; the on-screen frosted design isn't email-safe). Integer money via `MoneyFormatter` (same
  figure as folio/PDFs). **Real triggers now:** booking-confirmation (`reservation.confirmed`) · cancellation
  (`reservation.cancelled`) · refund (`payment.refunded`, successful only) via `SendGuestTransactionalEmails` —
  same bus/idempotency, but ⚠️ **a mail failure is caught so a booking never rolls back on an SMTP hiccup** (the
  correct transaction/side-effect boundary) — and to the guest's OWN address only (no cc/bcc; no address → no
  send). Payment-reminder = the real `emails:send-payment-reminders` command (scheduled daily; balance from the
  shared `FolioTotals`). **Template built · trigger deferred honestly:** pre-arrival (scheduler; access code only
  when a real one exists — never fabricated) · review→Phase 5 · waitlist→Phase 4 · verify→Phase 4 · winback
  (scheduler; NO fabricated discount). **Email Previews** (`/app/email-previews`) render every email with real
  sample data, sending nothing. Root-cause typing: added `@return` generics to `Folio`/`Payment::reservation()`
  + `Reservation::property()` and `@property $address` to `Property` (benefits all callers).
- **SG5 — owner/operator email delivery + Email Templates + consolidate (this gate).** ⭐ **The SG2 email toggle
  is no longer cosmetic:** `FeedNotification::via()` now adds the `mail` channel through the SAME gate
  (`allows(email)`), and `toMail()` renders the branded `emails.notification` view to the user's own address.
  🪤 **Mutation-proven** (force `mail` on unconditionally → the off-toggle test fails). **Email is selective by
  default** — a per-channel `email_default` in the catalog (financial/security/urgent ON, routine operational
  OFF), resolved in ONE `defaultFor()` so the gate and the screen can't disagree; so activating email doesn't
  bury users in mail. Proven from the real seed: only email-default-ON events emailed (owner "Your statement is
  ready" → the owner only; operator "Statement finalized"/"Urgent work order" → the 3 staff only; NO "New
  booking"/"Balance due" emails), isolation holding on the email channel. **Email Templates screen**
  (`/app/email-templates`, back-office gate) — **HONEST CHOICE: the VIEW is real** (every template's subject,
  trigger status, and merge variables + a live-preview link), **editing is deferred** ("Edit templates · Soon",
  NOT a fake save — a per-tenant template store + variable substitution + Mailables reading overrides is a genuine
  subsystem). Guest-level email preferences stay a Phase-4 (guest-portal) concern; production email delivery
  stays account-gated (a provider; Mailpit is dev).

**Phase-3 invariants (added to the register):** preferences gate delivery on BOTH channels (in-app + email),
never cosmetic; always-on security notifications can't be suppressed; notification isolation (owner ↔ operator,
per-user); **a guest-email/notification-mail failure NEVER rolls back the domain transaction that triggered it**;
no PII in domain-event payloads or logs (record WHICH changed, never values); guest emails go to the guest's own
address only.

**Verification:** full suite green on MySQL (Phase 3 added the notification core/preferences/wiring/email
tests) — see the count in `PROJECT-STATE.md`; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean; **Mailpit
(real SMTP, dev) shows the real emails** — booking/refund/reminder to the guest, statement/payout/urgent to the
right owner/operator only. Two `git checkout` traps hit + recovered (an uncommitted `via()` edit reverted during a
mutation pass, twice — re-applied and re-proven both times; the lesson: mutation-restore an untracked/working-tree
guard with perl or Write, not `git checkout`). **NEXT = Phase 4** (guest auth + the Guest Portal core).

### 2026-07-20 — Phase 4a COMPLETE: the GUEST AUTH FOUNDATION (a second auth realm, 4 gated sub-gates)

**The most security-sensitive greenfield in the project — a SECOND auth realm for guests, alongside the
operator/owner `web` realm, WITHOUT disturbing it.** Commits `524a1d3` → `70f4d02` → `e2710ff` → this gate. The
existing auth chapter (login · invites · reset · change-password · 2FA) is fully intact throughout (asserted).

- **SG1 — Guest Authenticatable (`524a1d3`).** Guest implements the Authenticatable / CanResetPassword /
  MustVerifyEmail contracts + traits (mirroring the framework `User`). New `guests` columns: `password`
  (nullable — a booking exists before the account is claimed; `hashed` cast), `remember_token`,
  `email_verified_at`, and **`email_hash`** — a BLIND INDEX (keyed HMAC of the normalised email), because
  `guests.email` is encrypted (non-deterministic → not queryable), kept in lock-step with the email by a saving()
  hook. A SEPARATE `guest` guard + custom `GuestUserProvider` (retrieves by `email_hash`, CLAIMED accounts only,
  querying WITHOUT the tenant scope — guest auth is pre-tenant, the org is derived from the resolved guest: the
  one legitimate cross-tenant read). `Guest::hasPortalAccount()` = password AND verified. GDPR `anonymize()` now
  ALSO destroys the account. **Realm separation mutation-proven** at guard + provider level (a guest is not a web
  user and vice versa; `web->check()` false for a guest → /app + /owner reject by construction).
- **SG2 — booking-tied claim + email verification (`70f4d02`).** A guest claims access for their BOOKING email:
  enter email → if it has a real booking (blind index) + no account → a native HMAC-`signed`, expiring link (the
  MustVerifyEmail primitive; no token stored, no PII in the URL) → set password → claimed + verified + signed in
  on the guest guard. Wires the **Phase-3 Verify Email trigger** (was template-built, deferred). **Booking-tied +
  no-enumeration mutation-proven** (bypassing the has-booking check makes a no-booking email send a link → the
  test fails; the response is identical whether or not a booking/account exists, including the already-claimed
  path). ⚠️ **DIVERGENCE (recorded):** the design's "Join for member rates" FREE sign-up is NOT built — accounts
  are booking-tied; a guest with no booking cannot create one.
- **SG3 — guest login + password reset (`e2710ff`).** Login on the `guest` guard (never web; rate-limited per
  email+IP in a `|guest|` bucket; session regenerated; generic failure; only claimed+verified resolves — the
  provider now requires both). Reset on a SEPARATE `guests` broker + `guest_password_reset_tokens`. ⚠️ **#28
  through the reset surface:** the token table keys on the BLIND INDEX (`getEmailForPasswordReset → email_hash`),
  never plaintext; the reset link is TOKEN-ONLY (no email in the URL); the ONE shared `Guest::changePasswordTo()`
  hash+rotate path (mirrors `User`'s). **Realm separation mutation-proven** (making login attempt on the `web`
  guard breaks guest login entirely — a guest's creds match no `User`, so the guest guard is load-bearing). ⚠️
  Caught in the browser pass: `redirect()->intended()` shares `session('url.intended')` across guards → a stale
  /app|/owner target could cross realms → switched to a direct redirect (intended() is unsafe across auth realms).
- **SG4 — Guest Account + guest isolation + consolidate (this gate).** The authed guest's OWN profile
  (name/phone/country/language — email read-only), change-password (current-verified, `current_password:guest`,
  via the shared `changePasswordTo()`), and account removal. 🔒 **GUEST ISOLATION mutation-proven** — everything
  resolves from the guest guard, NEVER a request id (no `{guest}` param); resolving from a request id instead lets
  A edit B → the isolation test fails. Path-aware `redirectGuestsTo` (a `/guest/*` bounce goes to the GUEST login,
  never the operator login). ⚠️ **CHOICES (stated):** EMAIL change DEFERRED (it's the login identifier + drives
  the blind index → needs re-verify + re-index). ACCOUNT DELETION = **revoke portal access** (null the auth
  fields), KEEPING the guest row + PII + bookings — SAFER than an instant self-anonymize (which would strip a
  guest's name off an upcoming booking / operator records); full GDPR erasure stays operator-mediated
  (`Guest::anonymize()`). This DIVERGES from the design's "This can't be undone" framing (the UI says "remove
  portal access", honestly).

**Phase-4a invariants (register):** GUEST REALM SEPARATION — a guest authenticates only on the `guest` guard and
can never reach /app or /owner (by construction; mutation-proven); GUEST ISOLATION — a guest sees/edits ONLY their
own record (resolved from the guard, never a request id; mutation-proven A-can't-reach-B); BOOKING-TIED CLAIM — an
account exists only for an email with a real booking; NO ENUMERATION across claim/verify/login/reset; #28 EXTENDED
— the blind index keeps the encrypted email queryable for auth without storing plaintext, and the reset token
table + link carry only the hash/token, never the plaintext.

**DEFERRED / ACCOUNT-GATED:** the Guest Portal itself (Trips, bookings, Account detail) → Phase 4b/4c on this
foundation; guest email-change → a later re-verify flow; guest ID verification (KYC) → account-gated; production
email delivery → a mail provider (Mailpit is dev). **Seed:** a claimed demo guest (demo.traveller@vyrobel-demo.test
/ password, booking-tied) so 4b has a real guest login.

**Verification:** guest-auth suite (22: foundation 5 · claim 6 · login/reset 6 · account 5) green; the existing
operator/owner auth UNREGRESSED; full suite green (count in `PROJECT-STATE.md`); PHPStan L5 + Pint clean;
`migrate:fresh --seed` clean; browser + Mailpit verified (claim → Verify Email → set password → home; login →
home, /app + /owner blocked; reset email token-only + blind-index token table; account self-edit persists).
**NEXT = Phase 4b** (the Guest Portal core, on this auth foundation).

## 2026-07-21 — PHASE 4b: the GUEST PORTAL CORE (Trips · Reservation · Manage · Check-in · Arrival) — invariants #32–33

Built on the 4a auth foundation, four gated sub-gates, each guest-isolated + mutation-proven, then pushed:

- **SG1 — Guest Trips** (`28aa439`) — the post-login landing (`/guest/trips`): the authed guest's own bookings,
  upcoming/past, resolved through `Guest::reservations()` (`lead_guest_id`), NEVER a request id. New: the guest
  portal shell (`<x-guest.shell>`), `App\Support\Guest\GuestTrip::card()` (status via `ReservationStage`, money
  via `FolioTotals`+`MoneyFormatter` — the same resolvers the operator/owner surfaces use, so the guest can't
  disagree with them), `ResolvesGuest` trait (the single guest-resolution point), `SetTenantForGuestRequest`
  (sets the guest's OWN org as tenant context so portal reads resolve — explicitly NOT the isolation; the
  isolation is resolving through the guest's relations). Isolation mutation-proven: `Reservation::query()` leaks
  B's booking to A.
- **SG2 — Guest Reservation detail** (`9cac64b`) — one owned booking: stay, charges/balance (reconciling to the
  folio via `GuestReservationView`), a timeline that DELIBERATELY omits the operator staff actor (PII restraint).
  Payment "Settle" stubbed → 4c. Isolation mutation-proven: `Reservation::findOrFail` lets A open B → 200.
- **SG3 — Guest Manage Booking** (`79151f1`) — **THE OPERATOR-CONFIRMED-REQUEST PATTERN (invariant #32).** A
  guest changing dates / adding extras / cancelling touches inventory / the folio / money, so EVERY guest action
  is a `pending` `guest_change_requests` row the operator confirms — the guest NEVER runs the real write and
  can't self-approve. New: `GuestChangeRequest` + `GuestChangeRequestService` (`confirm`/`decline` are STATUS
  ONLY; the real date-change / cancel-and-refund / folio charge stays the operator's certified tools, deferred
  honestly), a REAL read-only date-change quote through the SAME rate path the booking engine uses
  (`BookingEngineService::priceOver()` made public) that says "no published price yet" rather than fabricate a
  figure, and the operator confirm/decline screen (`/app/guest-requests`, guest NAME only · #28). Proven: a real
  cancel injected into the request path fails 3 tests; guest can't self-approve (web guard refuses at `/app`);
  cross-org operator confirm → 404.
  - **🔐 A 500 the green suite MASKED, found by opening it in a browser.** The operator confirm/decline
    route-model-bound `{guestRequest}`, but `SetTenantForWebRequest` is APPENDED to the web group so it runs
    AFTER `SubstituteBindings` — the scoped `GuestChangeRequest::first()` resolved with NO tenant context and
    threw `TenantContextMissing` (HTTP 500). The test passed only because the `TenantContext` singleton lingered
    from `seedScenario` in the same process. Fixed by resolving a STRING id INSIDE the action (the established
    operator-controller pattern — every other operator controller already does this; RMB of a scoped model is the
    anti-pattern), scope intact → cross-org still 404s. The test was HARDENED with `TenantContext::forget()` so it
    exercises the real middleware path — fixing the test's blind spot, not just the bug. The lesson (again):
    static checks + a green suite cannot see a page that 500s.
- **SG4 — Guest Check-in + Arrival Info** (`this commit`) — **DOOR-CODE / ARRIVAL ISOLATION + TIMING (invariant
  #33).**
  - **Online check-in** is the GUEST's pre-check-in intent — it stamps `reservations.guest_checked_in_at`, a
    submitted flag DISTINCT from the operator's authoritative check-in (`FrontDeskService::checkIn` →
    `status='checked_in'`, where the real arrival guards run). The guest confirming their details NEVER moves the
    booking's status. Proven: injecting `status='checked_in'` into the guest submit fails the test.
  - **Passport / ID capture is ACCOUNT-GATED KYC** — an honest "coming soon" stub; no image captured, stored, or
    "verified".
  - **Arrival Info** — directions (the property's real address, the SAME source as the Phase-3 pre-arrival email
    via the shared `ArrivalPack::directions()` — one rule, no drift), check-in/out times, and the sensitive
    access secrets. A SIMPLE arrival store was added on the physical `units` (`door_code` + `wifi_password`
    **encrypted at rest**, `wifi_ssid`, `arrival_notes`). The door code is guarded TWICE: (1) resolved through
    the guest's own relation (foreign → 404), (2) released by the arrival WINDOW inside `ArrivalPack`
    (`LEAD_DAYS=2` before check-in through check-out, and never for a cancelled booking) — a code exposed months
    early or to the wrong guest is a physical-security leak. When no real code exists it says "collect your key at
    reception"; before the window it says "closer to arrival" — never a fabricated code. Copy-to-clipboard for
    code/wifi. Local recommendations honestly "coming soon". Proven: A opening B's arrival → 404 (mutation-proven
    — bypassing the guest relation lets A see `BBB-SECRET`); forcing `released=true` fails the withhold test.
  - **Demo:** the pre-arrival window is real (days, not months) yet the demo must SHOW a released pack — so guest1
    gets a near-term stay (arrives tomorrow, priced + available over just those dates) whose code IS released,
    ALONGSIDE the far Sep stay whose code is still withheld: both states demoable. Demo door codes/wifi seeded on
    the units (flagged demo by living in `DemoSeeder`).

**NEW INVARIANTS.**
- **#32 — Guest-initiated changes are OPERATOR-CONFIRMED REQUESTS.** A guest can propose a date-change / add-on /
  cancellation, but only as a `pending` request record; the guest never performs the real inventory / folio /
  refund write and can't self-approve (the confirm route is `/app`, refused to the guest realm). The operator
  confirms (status only) and then applies the real change with their certified tools. Same shape as the
  operator/owner boundary: the guest may ask, the operator acts.
- **#33 — Door-code / arrival-info isolation + timing.** The arrival pack — ESPECIALLY the door code — is shown
  ONLY for the authed guest's OWN booking (resolved through their own relation, never a request id) AND only
  within the arrival window (withheld before it, and for cancelled bookings). Unset fields are honest; a code is
  never fabricated. `door_code`/`wifi_password` are encrypted at rest beneath both guards.

**DEFERRED (honest, not faked):** online payment / Apple Pay / card → **Phase 4c** (Stripe); wallet + receipts →
4c; guest ID verification (KYC) → account-gated; guest reviews → **Phase 5**; the operator's real execute-on-
confirm of a guest change request → the operator's existing guarded tools (front desk / reservations / folio);
local recommendations → later.

**Verification:** Phase-4b guest-portal tests green (Trips/Reservation isolation · Manage 7 · Check-in+Arrival 8);
full suite **773 / 6738** green on MySQL; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean; **browser-verified
end-to-end** both portals — guest requests a date change → pending → operator confirms → status-only (booking
untouched); guest opens the near-term stay → **released door code + copy** + wifi; the far stay → **withheld**
("closer to arrival"); online check-in → submitted, `status` unchanged. **NEXT = Phase 4c** (guest money +
engagement: payments/wallet/receipts + reviews).

## 2026-07-22 — PHASE 4c: GUEST MONEY + ENGAGEMENT — **THE GUEST PORTAL IS COMPLETE** — invariants #34–35

Four gated sub-gates, each guest-isolated + mutation-proven, then pushed. This completes the Guest Portal.

- **SG1 — Payments + Folio Receipt + money PDFs** (`4e172e0`) — the guest's balance + history across their OWN
  bookings via the shared `FolioTotals` (the exact operator/owner figures, reconciling to the cent), a per-booking
  Folio Receipt screen, and five real PDFs (`GuestDocumentController`): Folio Receipt + Statement reuse
  `FolioDocument`; new **VAT Invoice** (`GuestInvoiceDocument` — net + `type='tax'` = gross, reconciling *by
  construction*, the right way for a legal tax doc) and **Refund Receipt + Credit Note** (`GuestReversalDocument` —
  from real succeeded refunds; rendered ONLY when a real refund exists, else 404 — never fabricated). 🪤 The card
  charge / Apple Pay is DEFERRED (account-gated Stripe, same as New-Reservation card capture): balance shown +
  honest "Soon", no faked payment, no raw PAN, no guest payment write path. Isolation mutation-proven on the
  sharpest leak — bypassing the guest relation in the PDF controller lets A download B's financial documents.
- **SG2 — the Wallet / Credits LEDGER** (`9353d16`) — **invariant #34.** `guest_credit_ledger` mirrors
  `owner_ledger`: `direction` credit/debit · positive `amount_minor` · `reverses_id` self-FK (correct-by-reversal)
  · polymorphic `source` · `expires_at`. **Deliberately NO balance column** — the spendable balance is DERIVED
  (Σ live credits − Σ debits) in `GuestWalletService`. **No overdraw** — `debit`/`applyToBooking` throw
  `WalletOverdrawException` BEFORE any write when the amount exceeds the derived balance; expired credit is excluded
  (query-time; strict FIFO lot accounting is a stated seam). **Redeem a code** — real `promo_codes`, single-use
  (`max_redemptions` + once-per-guest); invalid/expired/already-used refused honestly. **Apply to booking** — a real
  debit AND a folio credit that reduces what's owed, via a new additive `FolioService::applyWalletCredit()` (a
  negative discount line through the single folio write path; `posted_by` null — a guest request's default guard is
  the guest, so it never reads `Auth::id()`, avoiding the FK trap `postCharge` would hit). Both models registered in
  the morph map. Mutation-proven: neutering the overdraw guard lets a debit exceed the balance; dropping the
  `guest_id` filter surfaces another guest's credit. Deferred honestly: loyalty tiers, referral auto-earn.
- **SG3 — Guest Messages** (`5006c7b`) — **invariant #35.** The guest side of the EXISTING Unified Inbox (extended,
  not reimplemented — the third participant surface on one inbox, after operator + owner). `InboxService` gains
  `postGuestMessage()` + `markReadByGuest()`, the exact mirror of the owner methods: a guest message is `inbound`
  (into the org — the operator reads it the other way up), `author_user_id` null, `sender_label` the guest NAME only
  (#28). It bumps `unread_count` (the operator's badge); `markReadByGuest` touches only the new `guest_last_read_at`,
  NEVER `unread_count` (the Wave-3 owner trap, avoided the same way). Threads resolve only through the authed guest's
  `guest_id` (never RMB). Mutation-proven: bypassing the filter lets A open another guest's AND an owner↔operator
  thread. GDPR (#25): `Guest::anonymize()` already redacts these bodies — proven for a portal-authored message.
- **SG4 — Wishlist + Waitlist** (`this commit`) — real, guest-owned stores. `guest_wishlist_items` (saved room
  types; save is idempotent; a "Save to wishlist" button on the public storefront room view, shown only to a
  signed-in guest, ties browse → wishlist). `guest_waitlist_entries` (join for sold-out dates — exact dates OR a
  flexible month; re-confirm extends the expiry; leave). Both self-only, resolved through the guest's `guest_id`;
  isolation mutation-proven (A can't remove B's item / leave B's waitlist entry). 🪤 Deferred honestly: the notify
  TRIGGER ("a spot opens" = a cancellation freeing a waitlisted date → Phase-3 email) + SMS (account-gated) + auto-
  hold (holding inventory has real availability implications, would go through AssignmentService) — the store +
  re-confirm + expiry are real; the trigger/hold are a stated seam, not faked.

**NEW INVARIANTS.**
- **#34 — the guest WALLET is an append-only ledger.** Balance DERIVED (no balance column), integer minor units
  (#17), correct-by-reversal (owner_ledger/folio discipline). It can NEVER overdraw — a debit beyond the derived
  spendable balance is refused before any write, and expired credit doesn't count toward it.
- **#35 — guest inbox isolation.** A guest sees ONLY their own thread(s) (resolved through `guest_id`, never RMB) —
  never another guest's, never an owner↔operator thread. A guest-side read moves only `guest_last_read_at`, never the
  operator's `unread_count`. (The same shape as the owner-inbox extension in Wave 3.)

**THE GUEST PORTAL IS COMPLETE** — auth foundation (4a) + Trips · Reservation · Manage (operator-confirmed requests)
· Check-in · Arrival (4b) + Payments · Folio Receipt · money PDFs · Wallet · Messages · Wishlist · Waitlist (4c),
every screen guest-isolated + mutation-proven, on real data under the honesty rule.

**DEFERRED / ACCOUNT-GATED (honest, not faked):** the card charge / Apple Pay → account-gated Stripe; SMS alerts →
account-gated; loyalty tiers → a loyalty subsystem; referral auto-earn → deferred (a referral credit is real only
when earned); the waitlist notify-trigger + auto-hold → a stated seam (a cancellation listener + Phase-3 email +
AssignmentService hold); production mail provider → Mailpit is dev.

**Verification:** Phase-4c guest tests green (Payments 8 · Wallet 7 · Messages 5 · Saved 4); full suite **797 /
6839** green on MySQL; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean; **browser-verified end-to-end** —
Payments hub reconciles (Paid €738); a real 879KB `%PDF` downloads in the live session; wallet €35 derived → redeem
`WELCOME10` → €45 → apply €20 → wallet €25 AND booking owed €184.50 → €164.50 (both sides reconcile); guest sends a
message → it lands in the operator's existing inbox (unread bumped); a room saved from the public storefront
appears on the Saved page. **NEXT = Phase 5** (the Reviews module — guest review → moderation → operator/owner
surfaces).

## 2026-07-23 — PHASE 5: the REVIEWS MODULE (one module, five surfaces) — invariants #36–37 — COMPLETE

Four gated sub-gates, each guard mutation-proven, then pushed. One cohesive module across guest · moderation ·
operator · owner · public storefront.

- **SG1 — Guest Review** (`83e9d63`) — a guest reviews a REAL completed stay. New `reviews` model (tied to one
  reservation, unique = one review per stay; denormalizes the physical `unit_id` + `room_type_id` from the stay's
  assigned unit — so the owner + storefront surfaces query without re-resolving, and the review is FROZEN to the
  unit stayed in). rating (1–5) · tags · body · visibility (public/private) · status (pending/published/rejected).
  `ReviewService` is the single write path. 🔒 TIED TO A REAL COMPLETED STAY (mutation-proven both ways): the
  reservation resolves through the guest's own relations (foreign → 404) AND must be a completed (PAST) stay
  (future/cancelled → 404); one per stay. A public review is `pending` (never auto-public); "send privately to the
  manager" sets `visibility=private` (operator feedback, never published). **Closed two Phase-3/4b deferrals:** the
  Review-Request email now fires on the real `reservation.checked_out` event (Mailpit-proven), and the Guest Trips
  card shows a "★ Review your stay" link → "★ Reviewed". GDPR: `anonymize()` de-identifies the review (author →
  "Guest") — it flows from the guest anonymization, no review-table change; no PII in the `review.submitted` event.
- **SG2 — Moderation + the shared rating derivation** (`fcf52fb`) — **invariant #37.** `ReviewService::publish()`
  (a pending public review → published; a PRIVATE review can NEVER be published — the guest's choice is binding)
  and `reject()` (→ rejected, unpublishing a published one). `App\Support\Reviews\ReviewRating` is the ONE
  rating-derivation definition (mirrors FolioTotals): `publicScope()` = the single source of "publicly visible"
  (visibility=public AND status=published); `forRoomType`/`forUnit`/`forProperty` → `{count, average, has_reviews}`,
  average DERIVED (never stored), no reviews → `has_reviews=false` ("No reviews yet", never a fabricated number).
  Mutation-proven: dropping the `status=published` filter lets a pending review count → caught.
- **SG3 — Operator Reviews + response + moderate** (`814806c`) — `reviews.view` + `reviews.manage` added to the
  catalog (a reputation task, NOT Front Desk; granted to Hotel Manager / Marketing Manager / Guest Support Agent —
  and Group Admin, to keep the privilege-subset rule intact: adding `reviews.*` to Hotel Manager broke Group
  Admin's ability to assign it — a real regression `OperatorRolesTest` caught). `OperatorReviewsController`
  (`/app/reviews`): list (guest NAME only #28), respond, publish/reject; string `{review}` resolved tenant-scoped
  in-action (never RMB); index on `reviews.view`, actions on `reviews.manage`, controls `@can`-gated.
  **Closed the Phase-3 deferral:** `review.submitted` dispatches `NewReviewNotification`, preference-gated on the
  `new_review` catalog key (which existed since Phase 3, trigger deferred). Response-automation + review-gating
  DEFERRED honestly (the screen says so); deceptive review-gating deliberately NOT built. Mutation-proven: dropping
  `can:reviews.manage` lets Front Desk publish → caught.
- **SG4 — Owner Reviews + public storefront** (`this commit`) — **invariant #36.** Owner Reviews (`/owner/reviews`,
  the pre-existing "Soon" nav item now wired) — READ-ONLY (the operator owns the response); the owner sees the
  PUBLISHED reviews for THEIR OWN units, resolved through the owner-scoped `OwnerPortalService::units()` (owner→units,
  the OwnerScope boundary). 🔒 OWNER ISOLATION mutation-proven (bypassing the owner scope lets A see B's unit
  reviews → caught). Public storefront: the room-detail + browse cards show the DERIVED rating + the published
  reviews (via the shared `ReviewRating`/`ReviewList`); only published shown (a pending/private review never
  appears); no reviews → "No reviews yet"; the byline is `GuestName::reviewByline` = first name + last initial only
  (#28), anonymized → "A guest".

**NEW INVARIANTS.**
- **#36 — reviews tie to REAL completed stays.** A guest reviews only their OWN completed (checked-out/PAST)
  booking — never a future/cancelled/foreign/not-their stay — and at most once per stay. The review freezes the
  physical `unit_id`/`room_type_id` at review time. Owner isolation: an owner sees reviews for THEIR OWN units only.
- **#37 — review moderation + rating are REAL.** A submitted review is `pending` (never auto-public); only a
  published+public review is publicly visible (a pending/rejected/private one never is; a private review can never
  be published). Every rating is DERIVED from published+public reviews via the shared `ReviewRating` — never a
  stored/fabricated number; a subject with none shows "No reviews yet".

**THE REVIEWS MODULE IS COMPLETE** across five surfaces (guest · moderation · operator · owner · storefront), on
real data under the honesty rule. **CLOSED DEFERRALS:** the Phase-3 Review-Request email + new-review notification,
and the 4b Guest Trips review link — all now wired real.

**DEFERRED HONESTLY:** response-automation + review-gating → a subsystem (the manual response + moderation is real;
deceptive gating is deliberately not built) · production mail provider → Mailpit is dev.

**Verification:** Phase-5 tests green (Guest Review 8 · Moderation 7 · Operator 4 · Owner+Storefront 3); full suite
**819 / 6910** green on MySQL; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean (a completed stay + published
reviews seeded); **browser-verified end-to-end** — a guest reviews their completed stay (pending), the operator
publishes it + the new-review notification lands in the feed, the owner sees only their unit's review, and the
public storefront shows the derived rating (★5 / ★4) + the published review with a #28 byline + the operator's
response. **NEXT = Phase 6** (multi-property + Analytics).

## 2026-07-23 — PRE-PHASE-6a TOTAL-RECALL CONSOLIDATION (documentation only, no code changes)

A full context snapshot before Phase 6a, verified against the actual repo. **This entry is the resume anchor: the
product now has THREE full user-facing apps + a public storefront + hardened multi-realm auth, all on the certified
backend; Phases 0–5 of the phased plan (`docs/GAP-ANALYSIS.md`) are COMPLETE.**

**Ground truth (verified this consolidation):** HEAD `8d33d8e` (the actual HEAD is this doc commit, one ahead — the
"advances by one" convention: the docs name HEAD one commit behind because the commit that writes these words IS the
new HEAD). Branch `main`, working tree clean, local == origin (all Phase-0→5 work pushed). Suite **819 tests / 6910
assertions, all green on MySQL 8** (`vyrobel_test`). **PHPStan L5 clean · Pint clean · `migrate:fresh --seed`
clean.** 97 migrations · 49 service classes · ~50+ styled/browser-verified screens · **invariants #1–37**. (One
stray Pint formatting nit in the Phase-4c `guest_credit_ledger` migration — a docblock `@see` FQCN — was auto-fixed
here so the snapshot is genuinely clean; formatting only, no logic.)

**THE COMPLETE BUILD ARC (all recorded above in dated detail; summarized here as the index):**
- **Original build (pre-plan):** Phase 1 core (tenancy/RBAC teams-mode · ULIDs · integer money · inventory +
  AssignmentService [the no-double-booking guarantee] · availability · guests/PII encryption · reservations · folios
  + FolioService · housekeeping) · Phase 2 (Channex + the sync-reliability backbone · Stripe, validated live once ·
  public booking · staff actions) · Owner accounting (attribution → append-only `owner_ledger` → statements →
  expenses → payouts + OwnerContext isolation) — all CERTIFIED. Then: the design pass + browser-verification · Auth
  A/B + password reset + Mailpit · demo-readiness (photos + PDFs) · Maintenance · the Unified Inbox (interface +
  ManualChannel + deferred channels) · the planning docs (ROADMAP/TIER1-SPEC/DESIGN-COVERAGE) · **Wave 1** (owner
  long-tail, 9 screens; OwnerOccupancy/RailLabel/assertNoRawBlade) · **security: /app authz gate #26** · **over-refund
  guard #27** · **Wave 2** (reservations + Guests CRM, 5 screens; ReservationStage/GuestSpend/GuestHistory; notes
  redaction) · **security follow-ups: terminate() guard #29 · the ENFORCED permission catalog #30 · the anonymize()
  UI trigger** · **Wave 3** (owner inbox, owner_id + BelongsToOwner) · **Wave 4 CORE** (Operator Rates · New
  Reservation [3 guards, no raw PAN] · backed Settings; MoneyInput/UnitAvailabilityService) · **Change Password** ·
  **2FA/TOTP #31**.
- **The customer signal + the plan:** Fatima liked the demo AND the prototype → "build the real product like the
  prototype" (SUPERSEDED "no signal, don't build blind"). The master `GAP-ANALYSIS.md` audit corrected multi-property
  from an L to an M (the schema already holds N properties/org).
- **The phased build (all gated, mutation-proven, pushed):** **Phase 0** (fix & harden: channels.status writer · dead
  enums resolved · QA-WALKTHROUGH) · **Phase 1** (operator buildable set: Group Booking · Search · Roles · Maintenance
  Costs/Settings · Profile) · **Phase 2** (owner buildable set: Bookings/Detail · Block Calendar · Owner Rates · Tax
  Center · Payout Method · Add Unit · Help) · **Phase 3** (notifications feed + typed preferences + emails) · **Phase
  4a** (guest auth foundation: the guest guard + blind index + booking-tied claim) · **Phase 4b** (Guest Portal core:
  Trips · Reservation · Manage [#32] · Check-in/Arrival [#33]) · **Phase 4c** (Guest Portal money + engagement:
  Payments/PDFs · Wallet ledger [#34] · Messages [#35] · Wishlist/Waitlist — THE GUEST PORTAL COMPLETE) · **Phase 5**
  (Reviews module: Guest Review [#36] · moderation + ReviewRating [#37] · Operator Reviews · Owner Reviews ·
  storefront).

**NEXT = Phase 6a** (multi-property): the `->first()` refactor (a COMPLETE grep-clean sweep — the schema already
holds N properties/org, so a missed call site is a wrong-property bug), then a property selector, property CRUD, and
the Portfolio screen — proven before Phase 6b (Analytics) builds on it. See `docs/PROJECT-STATE.md` for the full
resume snapshot (invariants #1–37, the tagged register, the account-gated tail, the meta-lessons).

### 2026-07-21 — Phase 6a COMPLETE: MULTI-PROPERTY (property context/selector + the complete `->first()` refactor + property CRUD + Portfolio; 2 gated steps)

**The gap Phase 6a closed (the audit's most consequential correction).** Multi-property was NOT a schema epic — the
schema already held N properties per org (`properties.organization_id` a plain FK; the `property_user` staff pivot
with live `User::properties()`/`Property::staff()`; `property_id` on `channels`/`groups`/`rate_plans`). The real
limitation was a UI/QUERY convention: ~10 call sites resolved "the property" as
`Property::query()->orderBy('name')->first()`, which **silently returns an ARBITRARY property once an org runs more
than one** — a wrong-property read, or worse a wrong-property WRITE (a booking landing in the wrong property). So this
was re-scoped **L → M** and built as a CORRECTNESS refactor + a selector + CRUD + Portfolio. The two steps:

**⛔ STEP 0 — the audit (read-only).** Enumerated every property-resolution site, tiered by risk: **Tier A** (10
explicit `->first()` sites across 8 files — reads vs writes distinguished) + **Tier B** (5 org-wide list screens that
BLEND properties with no `->first()` to flag them — the subtle ones a naive grep misses: RatesGrid, ReservationsList,
HousekeepingBoard, MaintenanceList, UnitsList). Public/slug resolution (`ResolvePublicProperty`, `BookingEngineController`)
+ non-property `->first()`s correctly left alone. Confirmed the **property-access model** and the **selector design**.

**⛔ STEP 1 — the property context/selector + the COMPLETE refactor (`5589ac0`).**
- **`App\Support\Property\PropertyContext`** (singleton, mirrors `TenantContext`/`OwnerContext`) — the current working
  property + the `property_user` allow-list (`accessibleIds`) + an explicit "all properties" roll-up (`readIds`,
  honoured by aggregate reads only). **`id()` ALWAYS returns a concrete property** (the default when "all" is active),
  so a property-scoped WRITE can never target "all". A foreign/stale selection is normalised away — never leaks.
- **PROPERTY-ACCESS MODEL (decision):** the `property_user` pivot is an **OPTIONAL allow-list** — a user with pivot
  rows is restricted to those properties; **a user with NO pivot rows gets the whole org** (empty ⇒ full org). This
  safely activates the dormant forward-hook: no existing user is locked out, single-property behaviour is unregressed,
  and no data migration is needed. `User::accessiblePropertyIds()` is the one rule.
- **`SetCurrentPropertyForWebRequest`** middleware on the `/app` group (after tenant is set) — resolves the per-user
  session selection (`current_property_id`), VALIDATED against the accessible set (a foreign/stale id falls back to the
  default; never 500s). The **topbar switcher** (`x-operator.property-switcher` → `PropertySwitchController`,
  POST→session→PRG, re-validates server-side); a single-property org shows a static label, not a dropdown.
- **`App\Livewire\Concerns\ResolvesCurrentProperty`** — captures the resolved values in `mount()` and holds them as
  **`#[Locked]`** props. **WHY (a real Livewire isolation fix):** a wire-action re-render hits `/livewire/update`,
  which is OUTSIDE the `/app` group, so the context is INACTIVE there; capturing at mount keeps render() correct across
  wire updates, and `#[Locked]` stops a client smuggling a non-assigned property id onto a wire update (cross-property
  access WITHIN an org is not caught by tenant scope). Same family as the invariant-#20 scoped-model-prop trap.
- **THE COMPLETE REFACTOR (grep-clean):** all 10 Tier-A sites resolve through the context (reads show the selected
  property; the writes — GroupBooking, NewReservation, Settings ×3, GroupBookingController — LAND in it);
  ReservationCreateController derives `property_id` from the chosen room type (correct by construction) and now honours
  the allow-list on the write path. The 5 Tier-B screens scope to the read set (selected, or all). `OperatorDashboard`
  aggregates over the read set. **Proven grep-clean** — no property-resolution `->first()` remains (only the
  slug/public sites + docblock references to the old pattern).
- **Mutation-proven** (`tests/Feature/MultiPropertyContextTest.php`, 8 tests): selecting B makes a read/write act on B
  not the alphabetically-first A (and `->first()` is gone); the pivot allow-list refuses a switch to / booking in an
  unassigned property; "all" rolls up both; single-property unregressed.

**⛔ STEP 2 — property CRUD + Portfolio + consolidate (this gate).**
- **`App\Http\Controllers\Operator\PropertyController`** (store + edit) gated **`can:properties.manage`** (Property
  Owner / Group Admin — NOT Front Desk / Hotel Manager, #30, mutation-proven). A created property is **org-scoped** by
  `BelongsToOrganization` (never cross-org), gets a unique storefront slug, and **enters the selector** (and the
  creator's pivot if they are pivoted). An edit resolves its target by a tenant-scoped id (cross-org 404s).
- **`App\Livewire\Operator\OperatorPortfolio`** (`/app/portfolio`, `can:properties.view`) — the multi-property
  overview: an all-properties KPI strip + a card per property with **REAL/DERIVED** data only (unit counts, occupancy
  from the Availability spine via the shared `App\Support\Property\PropertyOccupancy`, arrivals from the front-desk
  read), and a status **DERIVED** from real inventory: **"Finish setup to go live"** when a property has no units yet
  (its genuine pre-operational state, not a faked status). Add/Edit affordances render only for `properties.manage`.
- **HONESTY — deferred, not faked:** the prototype's side-by-side **ADR / RevPAR / revenue comparison table is
  ANALYTICS → deferred to Phase 6b** (never fabricated). "Billed per unit" is a Phase-7 subscription concept (no
  billing subsystem exists yet) — no charge is shown or taken.
- **Anti-drift extraction:** `PropertyOccupancy` is the ONE occupancy-for-a-property derivation, shared by the
  Dashboard and the Portfolio so the two surfaces can't disagree.
- **DEMO:** a 2nd real property (**Demo Vista Apartments** — bookable, honest empty, no stays), sorted after Seaside so
  the demo default is unchanged. **Demo caveat handled, not faked:** `demo-staff` is a Hotel Manager (`properties.view`
  only) → can SEE Portfolio but not create; CRUD browser-verified as **`demo-admin` (Property Owner)** instead.
- **Tests** (`tests/Feature/OperatorPortfolioTest.php`, 6): CRUD org-scope + permission gate (Group Admin creates;
  Hotel Manager + Front Desk 403); Portfolio real data + the honest finish-setup state; tenant isolation (no cross-org
  view/edit); the full add → select → book flow isolated to the new property.

**INVARIANT #38 (multi-property) added** — see PROJECT-STATE. **Verification:** suite **833 tests / 6976 assertions**
green · PHPStan L5 + Pint clean · `migrate:fresh --seed` clean (2 demo properties) · browser-verified (switch →
Rack/Units/New-Reservation/Dashboard all follow, bidirectional, session-persistent; add a property as Property Owner →
"Finish setup to go live" → it enters the switcher). Phase 6a commits: `5589ac0` (Step 1) → this gate (Step 2).

**NEXT = Phase 6b — Analytics** (Insights + Reports, operator per-property + all-properties; owner-scoped views) — the
deferred ADR/RevPAR/revenue comparison lands here. Then Phase 7 (the SaaS layer).

### 2026-07-21 — Phase 6b COMPLETE: the ANALYTICS module (a shared metric layer + operator/owner surfaces; 3 gated sub-gates) · PHASE 6 (a+b) DONE

**The most fabrication-tempting phase, held to the honesty rule.** The prototype's analytics are heavily INVENTED —
multi-year history (2023–2025), "best September on record", named guests, generated narrative ("Saturdays sell out
first", "October is pacing 9 pts behind"). NONE of that is real, and the demo data is recent + sparse, so REAL
analytics show LITTLE — which is CORRECT. Every number in this module is a real aggregate over real bookings/folios/
inventory; a thin/empty period shows an honest empty state; the invented history/narrative is REFUSED (never rendered).

**⛔ SG1 — THE SHARED METRIC LAYER, built + proven FIRST (`8254e26`).** `App\Support\Analytics\PerformanceMetrics`
+ `MetricSet` — the ONE derivation (anti-drift family, alongside `FolioTotals`/`OwnerOccupancy`). **Basis (stated):**
stay-night attribution over `[start, end)` — RN (room-nights sold) = in-range nights of ACTIVE `reservation_units`
(reconciles to the `availability.sold` spine); RR (room revenue) = `price_minor` PRO-RATED by in-range/stay nights
(reconciles to the folio room line); AN (available room-nights) = Σ `availability.total` (property scope) or
unit-count × days (owner scope — unit-level, because a room type is SHARED across owners' units). **ADR = RR÷RN ·
RevPAR = RR÷AN · occupancy = RN÷AN → RevPAR = ADR × occupancy EXACTLY.** Scopes: `forProperties` (single or the 6a
all-properties roll-up) + `forOwnerUnits`. Also real: LOS, cancellation rate, channel mix (`reservations.source`).
Money never sums across currencies (`multiCurrency` flag, #17). **COMPUTABILITY VERDICT (honest):** ✅ REAL —
occupancy · ADR · RevPAR · room revenue · LOS · cancellation rate · channel mix (direct/OTA/walk-in/corporate/group).
📋 DEFER (UI "Soon") — lead time + booking pace (derivable from `booked_at` but too thin in sparse recent data to be
meaningful). 📋 REFUSE — the invented multi-year history + generated narrative. **RECONCILIATION PROVEN** (5 tests):
a known booking → the exact hand-calculated ADR/occupancy/RevPAR/revenue; the RevPAR identity holds exactly; a
boundary-straddling stay pro-rates to its in-range nights; empty → honest zero; owner scope isolates (A sees it, B
sees zero).

**⛔ SG2 — Operator Insights + Reports (`df9c95e`).** Scoped by the 6a property context, gated `can:reports.view` (a
management/analytics baseline — NOT Front Desk / Housekeeping / Reservation Agent, #30, mutation-proven). **Insights:**
real metric cards + a REAL month-by-month trend (only months with real bookings; honest "not enough data yet" below 2
active months) + real channel mix; lead-time/pace deferred "Soon". **Reports:** revenue = Σ REAL folio items
(room/ancillary/tax, reconciles to `FolioTotals`); the ONE real operator cost is MAINTENANCE (resolved tickets on
scope units); the prototype's other cost lines (channel commissions/cleaning/supplies/linen) have NO data source →
listed "Not tracked yet", never fabricated; net = gross − tracked costs; top units by real room revenue. **The
fabrication-absence is TESTED** (`assertDontSee 2023/2024/best September/on record/pts behind`).

**⛔ SG3 — Owner Reports + Insights + the Portfolio comparison + consolidate (this gate).** **Owner analytics**
(`App\Livewire\Owner\OwnerInsights`/`OwnerReports`, `/owner/insights` + `/owner/reports`) — OWNER-SCOPED via
`OwnerPortalService::units()` + the owner-scoped `OwnerLedgerEntry` (never a request id; a non-linked user fails
CLOSED — sees nothing, mutation-proven). **Earnings reconcile to the owner ledger BY CONSTRUCTION** via the shared
`OwnerLedgerAttribution::netPerCurrency` — the exact figure the statements show (per-unit nets sum to the total).
Occupancy/nights = the metric layer over the owner's units. **Owner Insights narrative is real-or-omitted:** the ONE
observation ("your busiest night of the week is …") is genuinely derived from the owner's real bookings and OMITTED
below 5 nights / no clear leader — never fabricated. **The Portfolio comparison** (deferred from 6a) is now the real
side-by-side ADR/RevPAR/revenue per property (this year), via the metric layer, reconciling; a property with no data
shows honest zeros. **New invariant #39.**

**Anti-drift extraction:** `PerformanceMetrics` + `MetricSet` join the shared family; the same numbers appear on
operator Insights, the Portfolio comparison, and (owner-scoped) owner analytics.

**Browser-verified:** operator Insights (Seaside this-year: ADR €128.24, trend Jul €600 + Sep €1,580, Direct+OTA) ·
Reports (room €2,180 + tax €501.40 + maintenance €225 → net €1,955, untracked "Not tracked yet") · Portfolio
comparison (Seaside ADR €128.24/RevPAR €6.86/€2,180 · Vista €0 honest empty · Portfolio group) · owner Reports (net
€705.60 = A1 €442.80 + A2 €262.80, reconciles) · owner Insights (ADR €180, "busiest night is Monday", honest thin
trend). Suite **848 tests / 7099 assertions** green · PHPStan L5 + Pint clean · `migrate:fresh --seed` clean. Phase 6b
commits: `8254e26` (SG1) → `df9c95e` (SG2) → this gate (SG3).

**PHASE 6 (a + b) is COMPLETE — the product is multi-property with real analytics. NEXT = Phase 7 — the SaaS layer**
(tenant provisioning / self-signup — where the two deferred fail-closed RBAC self-provision defects finally get fixed
· subscription billing · Owner Team). Deferred honestly out of 6b: lead time + booking pace + channel-attribution
depth (thin data); the invented history/narrative (refused).

### 2026-07-21 — Phase 7a COMPLETE: TENANT PROVISIONING / self-signup (atomic org creation + both RBAC defects fixed; 2 gated steps)

**The SaaS layer begins: a public path that creates a brand-new operator organization.** This is the epic the
audit flagged from the start — `Organization::create` appeared in ZERO `app/` files, `OrganizationRoleProvisioner::provision()`
had ZERO callers, and two fail-closed RBAC defects were formally deferred to "the gate that builds org creation."
That gate is here, and both are fixed.

**THE CHICKEN-AND-EGG (solved cleanly).** A normal write can't create an org: `BelongsToOrganization` throws with
no tenant context; the permission gate needs a user holding `organizations.manage` — but no user exists yet. The
escape is a controlled PRE-TENANT path built from existing primitives (the same shape the seeder uses):
`TenantContext::runAsSuperAdmin()` creates the org OUTSIDE any tenant/permission context (`Organization` is the
tenant boundary, not a `BelongsToOrganization` model), then `provision()` gives it a valid RBAC state, then
`forOrganization()` runs the rest inside it. Public signup is a pre-auth route, so it never touches the gate.

**⛔ STEP 1 — `App\Services\TenantProvisioningService` (`0c4643d`).** THE single atomic path, all in one
`DB::transaction`: ① `runAsSuperAdmin` → `Organization::create`; ② `OrganizationRoleProvisioner::provision($org)`
— **its first real app caller** (RBAC DEFECT #1 FIXED: org-creation now provisions the org's 13 team-scoped
roles, so a self-signup org is no longer dead-on-arrival under the #26 back-office gate); ③ `forOrganization` →
founder `User` (hashed, active) + active `Membership` + `assignRole('Property Owner')` on the org's spatie team
(RBAC DEFECT #2 resolved for self-provision: the founder holds a legitimate, ASSIGNABLE org-admin role — never
the global `Super Admin`, a cross-tenant platform role a founder must never hold) + the first `Property`.
**Mutation-proven** (4 tests): a mid-provision failure rolls back the ENTIRE org (no orphans); tenant isolation
both directions; the founder belongs only to their org; a working RBAC state (founder holds Property Owner with
`properties.manage`/`users.manage`/`payments.refund`, NOT Super Admin — both defect fixes proven); existing
tenants unregressed.

**⛔ STEP 2 — the onboarding wizard (this gate).** `App\Http\Controllers\Auth\OnboardingController` + a public
`/signup` (GET wizard, POST provision): a 3-step self-contained wizard (business → founder account → first
property; vanilla-JS stepper, degrades to a full form; "connecting channels is optional — Soon"). One POST calls
the provisioning service atomically → the org + founder + roles + first property exist → the founder is logged
in on the web guard (session regenerated, like accept-invite) → lands on the Portfolio, where their one property
honestly shows **"Finish setup to go live"** (0 units — the real provisioned state, never a faked "ready").
**HARDENING:** rate-limited (`throttle:5,1`); founder password hashed; NO email-verification gate (`User` isn't
`MustVerifyEmail` — the founder matches every existing operator); enumeration handled neutrally — ORG existence
never leaks (slugs auto-disambiguate), and a duplicate email fails with a NEUTRAL message (no "already taken"); a
fully zero-enumeration accept-and-verify variant is deferred (needs production email + trades away immediate
login). **Tests** (5): end-to-end signup → the founder can OPERATE the org (reaches `/app/settings` +
`/app/roles` through the real #26/#30 gates); neutral duplicate-email; validation creates nothing; isolation from
an existing tenant; an authed user is bounced from `/signup`. Also hardened a pre-existing order-flaky owner test
(the 2-char `U1`/`U2` needle → distinctive codes) so the gate stays trustworthy.

**Browser-verified:** signed up "Harbour Collective" → a brand-new ISOLATED org (its Portfolio shows only its own
"Harbour Lofts", never the demo's Seaside/Vista), the founder logged in as admin (reaches `/app/roles`, sees the
provisioned role catalog + their Property Owner role). Suite **857 tests / 7139 assertions** green · PHPStan L5 +
Pint clean · `migrate:fresh --seed` clean. New invariant **#40**. Phase 7a commits: `0c4643d` (Step 1) → this gate.

**THE TWO RBAC DEFECTS ARE FIXED — removed from the open register.** Residual, re-scoped as a SEPARATE deferred
PLATFORM item (NOT a self-provision defect): the global `Super Admin` remains unassignable
(`model_has_roles.organization_id` is NOT NULL and part of the PK; MySQL forbids NULL in a PK), which affects only
the Horizon/platform-admin gate — a tenant founder must never hold Super Admin, so this is out of scope for
self-signup and stays deferred to whenever the spatie schema is next reworked.

**NEXT = Phase 7b — SUBSCRIPTION BILLING** (Paywall + Checkout + Operator Billing; Stripe Billing, live charges
when live keys exist). Then 7c (Owner Team) · 7d (rostering) · Phase 8 (account-gated integrations).

### 2026-07-22 — Phase 7b COMPLETE: SUBSCRIPTION BILLING — the free-trial SaaS model (charge account-gated; 3 gated sub-gates)

**Vyrobel bills the OPERATOR — the SaaS revenue model.** DECIDED: the free-trial model — sign up (7a) →
provisioned with a real trial (full access, N days) → trial expiry → the Paywall → subscribe. The most
account-gated phase: the STRUCTURE + LOGIC + UI are built REAL; the LIVE CHARGE + Stripe webhooks are deferred
HONESTLY (the same discipline as the guest card charge + the owner payout rail). NEVER a faked payment.

**⛔ SG1 — plans/tiers + subscription-state + the REAL trial (`4a3b5cd`).** Reuses the existing schema-only
`plans`/`plan_features`/`subscriptions` tables (their first real callers). config/billing.php holds the tiers
(Essential/Pro, monthly/yearly) with CONFIGURABLE PLACEHOLDER prices + feature splits (marked — Subhan sets
real pricing; nothing fabricated as final) + `trial_days`; `BillingPlanSeeder` materialises them.
`App\Support\Billing\SubscriptionStatus` is THE derivation (onTrial/active/lapsed/trialDaysRemaining from the
timestamp, never a stored countdown) — **fails OPEN on a missing subscription** (a missing billing record never
paywalls — the opposite of the fail-CLOSED security posture, so a billing bug can't lock out a customer).
`BillingService::startTrialFor` starts the trial ATOMICALLY inside the 7a provisioning transaction (`status =
'active'` is only ever reached through a real Stripe charge — no method flips it off a fake payment).

**⛔ SG2 — Paywall + Checkout (`5a52696`).** `OperatorBilling` (the Paywall — real derived status + the tiers
with placeholder prices + the feature splits) + `OperatorBillingCheckout` (chosen tier, real monthly/yearly
toggle, company/VAT capture [`organizations.billing_company`/`billing_vat_id`, a new migration — the org's own
business identity, NOT card data], the summary; NO card field). `BillingActionController::subscribe` is **THE
ACCOUNT-GATED SEAM** — takes no money, stores no card, does NOT activate; records the choice + returns an honest
"Vyrobel isn't collecting payments yet — no card was charged". Gated `can:billing.manage` (founder/Property
Owner, NOT Front Desk). Fixed a Blade word@directive trap ("subscribed@if").

**⛔ SG3 — access + feature gating + consolidate (this gate).** `EnsureSubscriptionActive` on the `/app` group:
a LAPSED org (expired trial / canceled) is redirected to the Paywall — but the gate bites ONLY after the trial
(in-trial/active = full access, never a mid-onboarding lock-out), FAILS OPEN on a missing subscription, and
ALWAYS lets the billing routes through (so a lapsed org can reach the Paywall to subscribe). Feature-gating:
`Organization::planAllows($feature)` — `channels` is Pro-only (Essential refused, Pro/Pro-trial granted),
surfaced as a "Pro" lock on the Channels nav item for an Essential org. The demo org is seeded ACTIVE on
ESSENTIAL (a sensible demo billing state — clearly demo data; it also demos the channels Pro-lock). A DEV/TEST-
ONLY "expire trial" affordance (404s outside local/testing) demos the access gate — never a real payment.

**Mutation-proven** (across BillingTrialTest/BillingPaywallTest/BillingAccessGateTest, 12 tests): the trial is
real (14 days from the provisioning timestamp); a past-trial org is expired/lapsed; access — an expired/canceled
org → the Paywall, an in-trial/active org → full, a no-subscription org fails open, the Paywall stays reachable
when lapsed; feature — Essential refused channels, Pro granted; the subscribe seam NEVER charges or activates
(sub stays trialing, stripe id null). Browser-verified: signup → "Trial · 14 days left"; Checkout pro-yearly →
€990 summary + honest "no card charged"; the demo Essential org shows the Channels Pro-lock; expiring the trial
→ redirected to the Paywall ("Trial ended").

**⚠️ DEFERRED / ACCOUNT-GATED (the biggest seam):** the LIVE Stripe charge + webhooks + the subscription
lifecycle FROM Stripe (`stripe_subscription_id` stays null; nothing flips to active) — Subhan's to obtain, same
as the guest card charge + owner payout rail. The specific PRICES are placeholders Subhan sets. New invariant
**#41**. Suite **869 tests / 7233 assertions** green · PHPStan L5 + Pint clean · `migrate:fresh --seed` clean
(demo active on Essential). Phase 7b commits: `4a3b5cd` (SG1) → `5a52696` (SG2) → this gate (SG3).

**NEXT = Phase 7c — OWNER TEAM** (owner sub-tenancy — an owner invites their own users). Then 7d (Team Schedule /
rostering) · Phase 8 (account-gated integrations, incl. LIVE Stripe billing when the account lands).

---

## 2026-07-22 — PHASE 7c: OWNER TEAM (owner sub-tenancy) ✅ COMPLETE

A primary owner invites their OWN sub-users into the owner portal, scoped to that owner's data, at one of three
fixed roles. **Nested sub-tenancy**: a sub-user ⊆ their owner ⊆ their org. Three gated sub-gates.

**⛔ SG1 — the model + NESTED isolation (`11eb637`).** `owner_users` (org, owner, user, role
[co_owner/accountant/view_only], status [active/disabled], soft-deletes, `unique(owner_id,user_id)`) — the nested
boundary table + `OwnerUser` model + `Owner::teamMembers()`. `OwnerTeamRole` — the fixed 3-role capability model.
`SetOwnerScopeForPortal` now resolves owner ids from BOTH the primary owner (`Owner.user_id`) AND active
`owner_users` memberships, and the acting role (primary → co_owner; else highest sub-membership). `OwnerContext`
gained `role()`. `EnsureBackOfficeAccess` recognises an active sub-user as owner-linked → redirected to /owner.
**THE CRUX: the nesting falls out of COMPOSITION** — the sub-user's `OwnerContext` resolves their owner via the
pivot → the existing `OwnerScope` (owner boundary) + tenant global scope (org boundary) both hold automatically,
**no new scope invented**. Mutation-proven (`OwnerTeamIsolationTest`, 4): A's sub-user sees A's units never B's ·
refused /app + /guest, served /owner · disabled sub-user + non-linked member fail closed (empty scope) · primary
owner unregressed.

**⛔ SG2 — the capability matrix + the payout gate + role integrity (`5b5ea7b`).** `EnsureOwnerCapability`
middleware gates owner routes by the acting role's capability (authorization by ROUTE — a REQUEST fact about an
ACTOR, invariant #30 discipline); `OwnerContext::can()`/`isPrimaryOwner()`; `@ownercan`/`@primaryowner` Blade
conditionals gate the UI from the SAME source (never invites a 403). Capability grid taken **verbatim from the
"Owner Team" design matrix** — deliberately **NOT a privilege chain** (accountant has documents+exports but not
calendar; view-only has calendar but not documents/exports): financials (all) · approvals/actions (co-owner) ·
documents (co+acct) · calendar (co+view) · exports (co+acct) · **payout (co-owner ONLY)**. **🔒 Payout account /
IBAN is co-owner-ONLY** — an accountant or view-only sub-user reaching the encrypted IBAN is a money-security
hole (#28); browser-proven both are 403'd. **🔒 Role integrity is STRUCTURAL**: team management is held by NO
role (not even a co-owner) — gated on `isPrimaryOwner()` — so `OwnerTeamRole::canAssign()` requires the primary
owner and self-escalation is IMPOSSIBLE. **DECISION (design-faithful, strictest)**: team management =
**primary-owner-only** (the design's Co-owner reads "Can't manage the team"); the one-line co-owner relaxation
stays in reserve. **The gate COMPOSES, never masks**: it only 403s users WITH owner access; a non-linked /
disabled user falls through to the SG1 empty fail-closed — so all 12 owner-scope isolation tests stayed green
(gate redesigned, tests NOT rewritten). Browser also caught a demo-setup collision (the seeded operator
`demo-accountant@` shares an email) — NOT a code bug. Mutation-proven (`OwnerTeamCapabilityTest`, 7).

**⛔ SG3 — the Owner Team screen + invite flow + consolidate (this gate).** `owner_team_invites` + `OwnerTeamInvite`
+ `OwnerTeamInviteService` — the OWNER-REALM parallel of `OwnerInviteService`, REUSING the exact token discipline
(sha256-only, single-use, expiring, no-enumeration, session-regen) but with owner-realm semantics (joins an
`owner_users` membership under a SPECIFIC owner + role, never an Owner party). `AcceptOwnerTeamInviteController`
(public `/team-invite/{token}`, reuses the set-password view). `EnsurePrimaryOwner` middleware gates the screen
+ EVERY mutation at the ROUTE (mutations are real controller routes, NOT Livewire actions — so the owner
middleware, which does NOT run on `/livewire/update`, enforces the gate on every write; the 6a boundary lesson).
`OwnerTeamController` (invite/change-role/remove/revoke; `{member}`/`{invite}` resolved owner-scoped, never
trusted from the request; a removed sub-user's orphaned login is disabled → **can't log in**). `OwnerTeam`
read-only Livewire component + view (you + members + pending invites + invite form + role legend; a TRUTHFUL
last-active from real `last_login_at`, never a fabricated "active 3 days ago"). **SG3 UX polish**: the self-only
tax/payout screens now show an honest "belongs to the account owner and is managed by them" for a SUB-USER
(`@ownersubuser` = has owner access but not primary owner) — never a bare "not linked" that implies a broken
account, never another owner's data; a genuinely non-linked user keeps the plain "no owner linked" state.
Demo-seeded: two real sub-users (accountant + view-only) + a pending invite under the demo owner.
**🔒 The invite is SCOPED + hardened**: it can only land the sub-user under the `owner_id` on the row (no
mis-scoping); no enumeration; hashed single-use token. Mutation-proven (`OwnerTeamInviteTest`, 6): invite → claim
joins the right owner at the right role · no enumeration (bad/spent token → generic page) · screen + every
mutation primary-owner-only (a co-owner sub-user is 403) · primary owner manages the team · a removed sub-user
can't log in · cross-owner member 404s (owner-scoped mutations). Browser-verified end-to-end: owner sees the team
+ invites someone (real pending row) · a sub-user is refused team/payout/calendar (403), served docs/statements,
bounced from /app · the tax honest-state renders for a sub-user.

New invariant **#42** (owner sub-tenancy). Suite **886 tests / 7326 assertions** green · PHPStan L5 + Pint clean ·
`migrate:fresh --seed` clean (demo owner + 2 sub-users + a pending invite). Phase 7c commits: `11eb637` (SG1) →
`5b5ea7b` (SG2) → this gate (SG3).

**NEXT = Phase 7d — TEAM SCHEDULE / rostering.** Then Phase 8 (account-gated integrations, incl. LIVE Stripe
billing when the account lands).

---

## 2026-07-22 — PHASE 7d: TEAM SCHEDULE / rostering ✅ COMPLETE (and with it PHASE 7 — the SaaS layer)

A weekly staff shift roster + a time-off/leave layer + a schedule PDF, for the operator. MODERATE rostering
(roster + time-off + PDF) — NOT shift-swapping / labor-cost / coverage-AI (those are deferred honestly, out of
scope). Over the org's REAL staff, per-property. Two gated sub-gates.

**⛔ SG1 — the weekly roster grid (`88e3af1`).** `shifts` table + `Shift` model ({org, property_id, user_id,
date, shift_type[early/day/late/off]}, unique(property_id,user_id,date), morph alias `shift`). `ShiftType`
(fixed pattern set + default hours) + `StaffDepartment` (role→frontdesk/housekeeping/manager) + `StaffRoster`
(the org's REAL staff = active `Membership` ∩ a back-office role, team-scoped; owners/guests excluded) — one
definition each. `ShiftRosterService` — THE single write path: assign upserts / `off` clears, stamps the
passed-in property + active org, records audit; the caller validates the user against `StaffRoster`.
`OperatorSchedule` Livewire grid (`ResolvesCurrentProperty`): real staff × 7 days for the SELECTED property,
department filter + week nav ride GET links; managers get an inline per-cell shift picker (a real POST),
everyone else read-only. `ScheduleController@assign` — a real POST gated `can:schedule.manage` (manager-level,
NOT Front Desk), property from `PropertyContext::id()`. New permissions `schedule.view` (broad — any staff sees
the roster) + `schedule.manage` (managers); nav gated `schedule.view`. Mutation-proven (`OperatorScheduleTest`
1–4): real staff only (owners + prototype fixtures excluded; empty dept → honest empty) · a shift lands on the
SELECTED property never A/cross-org · non-staff/cross-org user refused · VIEW broad / MANAGE manager-only.
Browser: real demo staff; assign persists; property switch → the roster follows.

**⛔ SG2 — time-off/leave + the schedule PDF + consolidate (this gate).** `leave_requests` + `LeaveRequest`
({org, user_id, start/end_date, status[pending/approved/denied], requested_by/reviewed_by}, morph alias
`leave_request`) — leave is about the PERSON + a date range, org-scoped (no property). `LeaveService` — THE
single write path: `request()` (staff requests OWN → pending) · `grant()` (a manager grants approved leave to
ANOTHER) · `approve()`/`deny()` (a manager decides). **🔒 SELF-APPROVAL is impossible**: approve/deny throw if
the actor is the person the leave is FOR, and grant throws if a manager targets themselves — mirroring the
guest-change-request / owner-add-unit / role-assignment self guard. **🪤 REAL CONFLICT**: `ShiftRosterService`
now REFUSES a working shift on an approved-leave day (a computed `LeaveService::isOnApprovedLeave` check, never
a fabricated warning); a shift set BEFORE leave was approved is FLAGGED on the grid (a real
working-shift-on-a-leave-day). `RosterWeek` — the ONE roster builder (real staff × days with the leave overlay
+ computed conflict), read by BOTH the grid AND the PDF, so **the PDF reconciles to the screen by construction**.
`ScheduleController` gains request/grant/approve/deny (`schedule.view` for own request; `schedule.manage` for
the rest) + `pdf()` (the week's roster as a PDF via `PdfService`, the SELECTED property — no id in the URL, so
cross-property/org isn't accessible). UI: leave cells + conflict flags on the grid, a time-off request form
(anyone) + the manager approval queue (a manager's OWN pending request shows "Awaiting another manager", never
self-approvable), a PDF button. Demo-seeded: a real week of shifts + an approved leave + a pending request.
Mutation-proven (`OperatorScheduleTest` 5–9): request → approve → approved leave blocks a shift · a manager
can't approve/grant their OWN · the conflict is REAL (shift+leave flagged; no overlap → none) · request broad /
approve manager-only · the PDF streams, is property-scoped, reconciles (B has the shift, A doesn't). Browser +
PDF verified: 10 real seeded shifts, 2 on-leave cells, the approval queue, a valid 860 KB property-scoped PDF.

**⚠️ DEFERRED / OUT OF SCOPE (honestly, not faked):** shift-swapping, labor-cost/payroll,
coverage-optimization AI — never rendered/stubbed as if built. New invariant **#43**. Suite **895 tests /
7383 assertions** green · PHPStan L5 + Pint clean · `migrate:fresh --seed` clean (a demo week + leave +
request). Phase 7d commits: `88e3af1` (SG1) → this gate (SG2).

**PHASE 7 (the SaaS layer) is COMPLETE** — 7a tenant provisioning · 7b subscription billing · 7c owner team ·
7d team schedule. **NEXT = Phase 8 — account-gated integrations** (LIVE Stripe billing + card charge + owner
payout rail + Channex live sync + WhatsApp/OTA inbox + production email + guest ID verification, as the
accounts land).

---

## 2026-07-22 — PHASE 8 (first piece): PRODUCTION EMAIL — Postmark wired into the Phase-3 seam ✅ COMPLETE

Phase 3 built the ENTIRE mail layer (9 Mailables + the preference gate + the "a mail failure never rolls back
the domain txn" boundary) on Mailpit. This lights up **Postmark** as the real PRODUCTION transport — a config/ENV
swap, NOT a Mailable rebuild. Provider = **Postmark** (Subhan's choice); live creds/domain not yet set, so the
one controlled real send is DOCUMENTED for Subhan to run (no real send in this build). Two gated sub-gates. See
`docs/EMAIL-PRODUCTION.md` (the runbook).

**⛔ SG1 — provider config + the test/prod separation, proven FIRST (`9b40e32`).** 🔒 **MailSafetyGuard — THE #1
guard, in CODE not convention:** a real-sending transport (postmark/ses/resend/mailgun/sendmail) is REFUSED
outside `production` and downgraded to `log`, applied at boot in AppServiceProvider — so even
`MAIL_MAILER=postmark` in a stray dev/CI `.env` can't email a real person unless `APP_ENV=production`; Mailpit
`smtp` / `log` / `array` (the test transport) are left untouched. Postmark config via ENV ONLY
(`services.postmark.token` = `env('POSTMARK_TOKEN')`; message stream + an 8s fail-fast client timeout in
`config/mail.php`); `.env.example` documents the keys + the env matrix, no values. `docs/EMAIL-PRODUCTION.md`
documents the exact SPF/DKIM/DMARC + custom Return-Path DNS records (Subhan's to add). Mutation-proven
(`MailTransportSafetyTest`, 3): the running suite is on a NON-SENDING transport (`array`; can't email a real
person) · the guard downgrades every real provider to `log` outside production and keeps it in production ·
`log`/`array`/Mailpit-`smtp` untouched.

**⛔ SG2 — delivery hardening + bounce handling + consolidate (this gate).** 🔒 **The Phase-3 boundary RE-PROVEN
with the real transport:** a provider send failure (network / 5xx / rate-limit) is caught + logged (never the
address — #28) and NEVER rolls back the booking/notification — proven against a transport that always throws
(`MailDeliveryBoundaryTest`, 2: a booking AND a cancellation both survive a provider outage). **Delivery
hardening:** the 8s fail-fast timeout (SG1) + the best-effort-with-catch boundary. **Retry/queueing DEFERRED
deliberately (not faked):** making the transactional Mailables `ShouldQueue` would flip `Mail::assertSent` →
`assertQueued` across the certified `GuestEmailTest`, i.e. rebuild the certified layer — out of scope; the
boundary already guarantees a failure never breaks the business op. **Bounce/complaint — Postmark-native for v1
(honest deferral):** Postmark automatically suppresses hard bounces + spam complaints on ITS side (won't
re-deliver to a dead address) + surfaces them in the dashboard — the real deliverability protection. App-side
handling (a Basic-Auth-verified `/webhooks/postmark` + a suppression store keyed by a **blind-index hash** of the
address, since the guest email is encrypted PII #28) is documented as the future build, NOT stubbed/faked. **The
controlled real send** (the ONE real send — to Subhan's OWN address, never a customer) is finalized as documented
steps for Subhan to run once his creds + DNS are set (may land in spam until DKIM/DMARC propagate — honest).

**⚠️ REMAINS SUBHAN's:** the Postmark server API token (prod ENV) · the DKIM/Return-Path/DMARC DNS records ·
running the one controlled real send. New invariant **#44**. Suite **900 tests / 7409 assertions** green ·
PHPStan L5 + Pint clean · no real send in any environment · no secrets in the repo.

**Phase-8 account-gated register — production email ✅ LIT UP.** Still pending (as accounts land): **LIVE Stripe
billing** (next) · card charge / Apple Pay · owner payout rail (Stripe Connect + KYC) · Channex live sync ·
WhatsApp / OTA inbox channels · SMS · guest ID verification.

---

## 2026-07-22 — PHASE 8 (second piece): LIVE STRIPE across all three seams (test-mode-first) ✅ COMPLETE

Real money — the safety posture INVERTS (build so it CANNOT charge wrong). Live Stripe wired into the three
seams Phase 2 built the plumbing for (guest charge, owner payout) + the 7b subscribe seam — TEST-MODE-FIRST,
proven against the `FakeStripeClient` (no network) with real Stripe signature verification. **NO live key in the
build** (the flip is Subhan's final step). Four gated sub-gates. Runbook: `docs/STRIPE-PRODUCTION.md`.

**⛔ SG0 — foundation (`ae29575`).** Phase 2 already had the client seam + a signature-verified,
event.id-idempotent webhook controller + the `STRIPE_FAKE` gate (the deferred-not-faked discipline vindicated).
Added: 🔒 **StripeSafetyGuard** (the money #1 guard — a live `sk_live_` key is REFUSED outside `production`,
the fake is bound; a stray `STRIPE_FAKE=false`+live key in dev/CI can't charge) + the **sending-side idempotency
key** (`StripeIdempotency::keyFor`, pure/deterministic, threaded through every write; the fake dedupes) +
`STRIPE_CONNECT_WEBHOOK_SECRET` (the separate Connect endpoint). Two-layer idempotency now complete + proven both
ways (`StripeFoundationTest`).

**⛔ SG1 — subscription billing (`2a1bb47`).** subscribe → a Stripe Checkout Session (subscription mode; the
plan's `stripe_price_id`; the card on Stripe's page). 🔒 **Fulfil on the WEBHOOK not the redirect:**
`BillingWebhookService` is the ONLY place an org goes active/past_due/canceled (checkout.session.completed /
customer.subscription.* / invoice.*); the 7b `SubscriptionStatus` derives access from `status`, so the lifecycle
drives the paywall from real events. Columns `subscriptions.stripe_customer_id` + `plans.stripe_price_id`. Proven
a closed tab still activates via the webhook (`BillingStripeTest`).

**⛔ SG2 — guest card charge (`f8a31e9`).** The guest pays their OWN booking's balance → a Checkout Session
(payment mode). 🔒 **Reconcile on the WEBHOOK (max reuse):** a PENDING Payment is pre-created against the
session's PaymentIntent (`FolioService::recordCheckoutIntent` — `firstOrCreate` on `gateway_ref` → a 3rd
double-charge layer), and the EXISTING `payment_intent.succeeded` webhook (→ `settlePayment` → recompute) posts
the real charge to the folio, integer money, reconciling. 🔒 Guest isolation #28 (booking through
`guest()->reservations()`, foreign → 404). Refunds unchanged — the single `FolioService::refundPayment` (#27
cumulative bound, correct-by-reversal) reused. `GuestCardChargeTest`.

**⛔ SG3 — the owner payout rail (Connect) + consolidate (this gate) — the HIGHEST-STAKES seam.** Owner connects
a Stripe **Standard** account (they own KYC/payouts; Stripe handles it) via hosted onboarding — `OwnerConnectController`,
self-only, co-owner-gated (#42); `stripe_connect_payouts_enabled` flips on the `account.updated` Connect webhook.
`StripeConnectPayoutRail::send()` creates a **Transfer** to THE PAYOUT'S owner's connected account.
🔒 **THE DOUBLE-TRANSFER GUARD (the key proof):** `OwnerPayoutService::pay()` runs the whole disburse inside a
DB transaction that **row-locks the payout** (`lockForUpdate`) and re-checks `pending` INSIDE the lock — so a
concurrent pay() (double-click / a webhook racing a sync callback) BLOCKS then refuses (no 2nd transfer); the
transfer also carries a deterministic **Idempotency-Key**. Both proven load-bearing: **two transfers WITHOUT the
guard, ONE WITH** (`OwnerPayoutStripeConnectTest`). 🔒 Correct owner only (destination resolved from the payout's
owner, never another's). Webhook-driven: pay() → `processing`; the `transfer.created` CONNECT webhook (SEPARATE
secret, event.id-idempotent) → `paid` + statement paid. Refuses an un-connected owner (no transfer, honest).
Owner Payout Method UI: the "Automated transfers coming soon" deferral is now a real "Connect Stripe" onboarding.
The payout status enum gained `processing`.

**⚠️ THE LIVE-KEY FLIP IS SUBHAN's FINAL STEP** (documented in `docs/STRIPE-PRODUCTION.md`): activate the account,
create live Prices, enable Connect, register BOTH live webhook endpoints (account + Connect), flip the prod ENV
(`STRIPE_FAKE=false` + live keys + `PAYMENTS_GATEWAY=stripe` + `PAYOUTS_RAIL=stripe_connect`), then a $1 real-card
smoke-test + refund per Stripe's go-live. New invariant **#45**. Suite **921 / 7496** green · PHPStan L5 + Pint
clean · `migrate:fresh --seed` clean · no live key in the build · no secrets in the repo · no raw PAN.
Phase-8-Stripe commits: `ae29575` (SG0) → `2a1bb47` (SG1) → `f8a31e9` (SG2) → this gate (SG3).

**Phase-8 account-gated register — production email ✅ · LIVE STRIPE (all 3 seams) ✅ (test-mode-proven; live-flip
= Subhan's).** Still pending (as accounts land): Channex live sync · WhatsApp / OTA inbox channels · SMS · guest
ID verification (KYC).

### 2026-07-22 — Audit demo-blocker remediation (targeted; presentation/seed/test-scoped) · 3 gated fixes

A full READ-ONLY system audit (`docs/AUDIT-REPORT.md`, 26 browser screenshots, HEAD `4fb22fa`) drove a live
Playwright browser through all five apps, health-swept every route, ran the RBAC/owner-capability matrices, and
exercised four real mutations (guest check-in, storefront booking → `R-KHMWQN33` + inventory + domain event,
self-serve signup → atomic provisioning, trial-expiry → paywall). It surfaced ONE demo-blocker plus follow-ups;
this pass fixed them **without touching money/auth/isolation logic or the mutation-proven guards**.

- **GATE 1 (`c0ce419`) — P1: four operator pages rendered UNSTYLED.** Reviews · Guest requests · Email previews ·
  Email templates are the only operator screens rendered by a plain **controller** whose Blade root is
  `<x-operator.shell>` — a **body-only** component (no `<!doctype>/<head>/@vite`). The 25 Livewire pages get the
  document via `#[Layout('components.layouts.operator')]`; `<x-guest.shell>` self-wraps; `<x-operator.shell>` does
  not. HTTP `200` + no exception + no raw-Blade leak, so every static check + the status-only health sweep missed
  it — **the CLAUDE.md "static checks cannot see a rendered page" lesson, live.** Fix: wrap each of the four views'
  shell in `<x-layouts.operator>` (the SAME document the Livewire pages sit inside). Chosen over making
  `<x-operator.shell>` self-wrap (would DOUBLE-`<head>` the 25 Livewire pages). Presentation/layout-only.
  **Browser-verified** (the only real proof): four pages fully styled; 25 Livewire pages unregressed (exactly one
  `<head>` each). `fix-reviews-after.png` · `fix-guest-requests-after.png` · `fix-dashboard-noregress.png`.
- **GATE 2 (`7cd1550`) — P2 storefront "0 rooms" (seed) + P2 dev button (false positive).** The storefront offered
  nothing for most dates because the demo `rate_calendar` was seeded only for a fixed Sept band — availability was
  open but UNPRICED (engine correct). **Seed-only fix:** `RANGE` is now a rolling `today → +180d` window (spanning
  the fixed Sept demo stays); `seedCalendarRange` made idempotent (`updateOrCreate`) so the wide window overlaps the
  near-term stay's own range without colliding on the unique `(rate_plan_id, date)`. Engine untouched. Browser-
  verified: 20–23 Aug now offers 2 rooms (`fix-storefront-august.png`). The dev **"expire trial"** button was a
  **false positive** — already gated to `local` at the view **and** `abort_unless(local,testing)→404` at the
  controller; a production operator never sees or reaches it. No change.
- **GATE 3 — the `/livewire/update` owner-capability caveat: CLOSED, gate holds, no gap.** The audit confirmed the
  owner capability gates at the ROUTE layer but hadn't exercised the Livewire-update mutation layer (which skips
  route middleware — the 6a boundary). New `tests/Feature/OwnerCapabilityMutationBoundaryTest.php` (3 tests / 21
  assertions) proves the boundary two ways: (1) the **real HTTP mutation path** (POST/PATCH/DELETE to the payout +
  team controller routes) 403s for every non-permitted sub-user (accountant/view-only on payout; every sub-user
  incl. co-owner on team management) and passes for the account holder; (2) **structurally**, the sensitive owner
  Livewire components (`OwnerPayoutMethod`, `OwnerTeam`) — and in fact **every** owner Livewire component — expose
  only `render`/`mount`, i.e. **no mutating wire action**, so `/livewire/update` carries no owner mutation to
  bypass a gate. The 7c design (owner writes on gated controller routes, not wire actions) is confirmed sound.

Suite **924 / 7517** green (921 + the 3 new) · PHPStan L5 · Pint clean · `migrate:fresh --seed` clean. **Next: the
design-fidelity pass** (`docs/AUDIT-REPORT.md` §4). Account-gated tail stays deferred (live charge/payout/KYC/OTA/
WhatsApp/SMS). Audit-fix commits: `c0ce419` (G1) → `7cd1550` (G2) → this doc-consolidation (G3).

### 2026-07-22 — Total-recall consolidation · BACKFILL (Phases 7c/7d + Phase 8 email/Stripe) + the design-fidelity pass

This entry backfills the dated log for phases that shipped (commits below) but whose per-phase DECISIONS entries
were deferred to this consolidation, then records the audit-era design-fidelity pass. Chronology is carried by the
hashes; the entry itself is appended (append-only order preserved). All are on `main`, pushed.

**Phase 7c — OWNER SUB-TENANCY (Owner Team) COMPLETE** (`3b…`→`5b5ea7b`→`8b341c2`). An owner sub-user ⊆ their
owner ⊆ org, the NESTED isolation COMPOSED from the existing `OwnerScope` + tenant scope (no new scope invented).
`owner_users` pivot + the ONE capability matrix `App\Support\Owner\OwnerTeamRole` (three fixed roles: co-owner /
accountant / view-only) read by BOTH the route gate `EnsureOwnerCapability` and the UI `@ownercan` — so gate and UI
can't drift. The SENSITIVE payout account is **co-owner ONLY** (money-security, #28 on the encrypted IBAN column);
team management is **primary-owner ONLY** (`EnsurePrimaryOwner`/`isPrimaryOwner()`, held by NO role — self-escalation
structurally impossible; `OwnerTeamRole::canAssign()` requires the primary owner). The owner-realm invite reuses the
hardened token discipline + is owner-scoped. **Owner writes are CONTROLLER routes (not Livewire actions)** so the
middleware runs (the `/livewire/update` boundary) — later proven by `OwnerCapabilityMutationBoundaryTest`. Invariant **#42**.

**Phase 7d — TEAM SCHEDULE COMPLETE** (`88e3af1`→`152dc8d`). A weekly staff roster, TENANT + PROPERTY scoped over
REAL staff (`StaffRoster` = active `Membership` ∩ a back-office role; a shift lands on the selected `PropertyContext`,
never `->first()`, never a fabricated person). `ShiftType`/`StaffDepartment`/`StaffRoster` + the ONE `RosterWeek`
builder read by both the grid and the schedule PDF (they reconcile). Managing = `schedule.manage` (manager-level, not
Front Desk); viewing = broad `schedule.view`. Time-off is a request→approval SEPARATION with **self-approval
impossible** (`LeaveService`); a shift-on-approved-leave is a REAL computed conflict. Invariant **#43**. THE BUILDABLE
PHASE 7 (SaaS layer) IS COMPLETE.

**Phase 8 — PRODUCTION EMAIL · LIT UP** (`9b40e32`→`f879de3`; `docs/EMAIL-PRODUCTION.md`). Postmark wired as the
production transport; STRICT test/prod separation. THE #1 GUARD `MailSafetyGuard` (code-guard) REFUSES a real-sending
transport (postmark/ses) outside `APP_ENV=production` and downgrades to `log` — so even `MAIL_MAILER=postmark` in dev
can't email a real person. The Phase-3 boundary (a mail failure NEVER rolls back the domain txn) re-proven with the
real transport; bounces handled Postmark-native. Domain auth (SPF/DKIM/DMARC) documented. **Code-complete; Subhan's
cutover = the DNS records + the Postmark server token + one real send to his own address.** Invariant **#44**
(email-safety + mail-never-rolls-back).

**Phase 8 — LIVE STRIPE · LIT UP (test-mode-proven) across all THREE seams** (`ae29575` SG0 → `2a1bb47` SG1 subscription
billing → `f8a31e9` SG2 guest card charge → `dda2a80` SG3 owner payout rail; `docs/STRIPE-PRODUCTION.md`). Seams:
subscription billing (Checkout → webhook-driven org activation), guest folio card charge (hosted Checkout → folio on
`payment_intent.succeeded`, reconciles), owner payout rail (Connect Transfer → payout `processing→paid` on
`transfer.created`). **Iron rules, all proven against `FakeStripeClient` (real HMAC, no network):** the card never
touches the server (hosted Checkout — PCI SAQ A); **fulfil on the WEBHOOK, not the redirect**; every webhook
SIGNATURE-VERIFIED against the RAW body (two DISTINCT endpoints — account `/webhooks/stripe` + Connect
`/webhooks/stripe/connect`, separate secrets); **multi-layer idempotency** (deterministic `StripeIdempotency` send-key
+ the `webhook_events` unique `event.id` index + `firstOrCreate(gateway_ref)`); the owner payout **can never
double-transfer** (unique key + `lockForUpdate` ledger row-lock, re-check pending inside the lock — proven to double
WITHOUT the lock and once WITH) and only ever pays its OWN owner; ledgers stay append-only / correct-by-reversal /
integer-minor (#17,#27). `StripeSafetyGuard` (code-guard) REFUSES a live `sk_live_…` key outside `production` (the fake
client is bound instead). **Test-mode-proven; the LIVE-KEY FLIP is Subhan's documented final step** (activate account,
live Prices, register both webhook endpoints, flip ENV, $1 real-card smoke-test → refund). Invariant **#45**.

**FULL-SYSTEM AUDIT + demo-blocker fixes** — recorded in the 2026-07-22 audit entry above (`4fb22fa` audit →
`c0ce419`/`7cd1550`/`420995a` fixes). Verdict: **demo-ready** after the fixes; **customer-ready pending the
account-gated tail** (Channex/WhatsApp/SMS/KYC) + Subhan's email/Stripe cutovers + deployment.

**DESIGN-FIDELITY PASS — IN PROGRESS** (`bdeb3f7`→`6ae6d3e`→`1d4a7b8`→`936bd2f`→`5c78a67`→`98c4e13`;
`docs/design/DIVERGENCE-REGISTER.md`). Presentation-only (Blade/CSS/components/layout/states — NEVER
behaviour/logic/guards; suite stays 924/7517 green per page-commit). The shared design TOKENS already match
`docs/design/design-system-brief.md` near-exactly (the app was built from it), so this is PER-PAGE layout fidelity.
The sharpened discipline — **THREE-CATEGORY discrimination** on every gap: ✅ **KIND-1** layout/arrangement → FIX with
shared tokens/components; 🚫 **KIND-2** fabricated content (84% occupancy, Marco Reyes, task lists, synced-channel
panels) → REFUSE, style the real/sparse/empty state to the prototype's treatment (a well-styled honest state IS
parity); 📐 **KIND-3** beyond the presentation boundary (a data-shape the component doesn't fetch, or JS not built) →
DOCUMENT as an intentional divergence, do NOT cross the boundary to chase a look. GATE 0: the operator **shell**
regrouped to the prototype's sections (OVERVIEW/FRONT DESK/OPERATIONS/FINANCE/SYSTEM), propagating to every operator
page (`bdeb3f7`). Then RESTRUCTURED (KIND-1): **Dashboard** (uniform 5-tile KPI row + a right-rail dark "Tonight"
panel + an HK progress bar — real data, refused the fabricated 84%/tasks/channels; `6ae6d3e`), **Reservation Detail**
(a prominent dark "Balance due" panel leading the right rail — real reconciled folio; refused the prototype's
email/phone guest card as a #28 PII breach; `936bd2f`), **Reservations List** (3 uniform full-width KPI tiles + FIXED a
shared-grid regression the Dashboard commit introduced: `.vy-dash-kpis` is now count-agnostic — `repeat(4)` default +
opt-in `--3`/`--5` modifiers; `5c78a67`), **Guests CRM** (3 uniform full-width KPI tiles; `98c4e13`). INTENTIONAL
DIVERGENCE (KIND-3, documented not chased): **Reservation Rack** (prototype = per-unit Gantt with spanning bars; app =
per-room-type availability-counts grid — a Gantt needs per-unit spans the component doesn't fetch) and **Front Desk**
(prototype = tabs + hourly timeline + unassigned count = JS/data; the app's KPI band + arrival rows already at parity).
Shared UI extractions introduced/generalised: the **`.vy-tonight` dark stat panel** (Dashboard + Reservation Detail)
and the **count-agnostic `.vy-dash-kpis`** modifiers — the anti-drift discipline applied to UI, not just logic. NEXT:
resume the operator queue — **Rates next**, then Maintenance/Units/Settings/Roles/Profile/Notifications/Inbox/Reports/
Team Schedule/Housekeeping/Billing — then GATE 2 (owner), GATE 3 (guest), GATE 4 (public/billing + fold the register
into `docs/design/CATALOGUE.md`). Setup to resume: `php -S 127.0.0.1:8899` from `docs/design/raw/` (gitignored) for
side-by-side diffing.

**This consolidation:** docs-only, no code changed; suite **924 / 7517** green on MySQL, PHPStan L5 + Pint clean,
`migrate:fresh --seed` clean; HEAD `98c4e13`, `main`, local == origin. Invariants **#1–#45**.

### 2026-07-23 — DESIGN-FIDELITY PASS: GATE 1 (operator) COMPLETE + GATE 2 (owner) STARTED (17 gated page-commits) + total-recall save

Continues the design-fidelity pass entry above (which ended at `98c4e13` / "Rates next"). Presentation-only
throughout — Blade/CSS/components/layout/states ONLY; NEVER behaviour/logic/services/queries/guards/routes/
permissions/money/auth/isolation. The full suite **924 / 7517** + PHPStan L5 (0) + Pint stayed green and all blades
compiled **after every page-commit**; each commit browser-verified (app page vs the `docs/design/raw/` prototype at
`:8899`). Every page classified by the THREE-CATEGORY discipline and recorded in `docs/design/DIVERGENCE-REGISTER.md`.
Commit chain `4e5efde` → `cace277` (18 commits incl. this save), all on `main`, pushed.

**✅ GATE 1 — OPERATOR COMPLETE (14 queue items).**
- **RESTRUCTURED (real KIND-1 layout fixes):**
  - **Rates** `4e5efde` — dark-hero + 2-light → 3 uniform light tiles (`.vy-dash-kpis--3`).
  - **Maintenance area** `30896ec` (4 sub-screens): board 4 stat-pills → the `.vy-dash-kpis` band; **work order** rail
    now leads with the dark `.vy-tonight` STATUS panel; **costs** got real **proportional bars** (width ∝ `total_minor`,
    peak terracotta, in-blade from data already passed); **settings** single-column → the shared two-column
    `.vy-detail-cols` (rule editors | notify/policy rail).
  - **Notifications feed** `f16d041` — per-row category icon in a soft circle (keyed to the real category); unread dot moved.
  - **Inbox** `4910564` — 4 stat-pills → the `.vy-dash-kpis` band (click-to-filter preserved).
  - **Reports** `519409c` — now leads with a real KPI band (Gross / Net / Arrivals / Tax — figures already computed).
  - **Insights** `c797444` · **Portfolio** `f05a885` — removed the dark-hero KPI tile → uniform light (Portfolio's
    occupancy bar kept a page-local light-surface track bg; shared `.vy-dk-track` untouched).
  - **Housekeeping** `c335fb0` — 3 stat-pills → the `.vy-dash-kpis--3` band (kanban = the prototype's Board mode).
- **DOCUMENTED (KIND-2 refused / KIND-3 beyond boundary — no code change, register + evidence only):**
  Units `7bd0be8` (no prototype — app-original) · Property Settings `8d427d4` (sprawling settings dashboard = unbuilt
  editors, or lives in dedicated screens) · **Roles `01d9181` (editable permission catalog REFUSED for security — #30
  escalation weapon; app = read-only catalog + assign)** · Profile `b825821` (self-only; extra fields/sessions unbacked) ·
  Team Schedule `4d55867` (KIND-3: drag-to-move / open-shifts / coverage / publish-workflow) · **Billing `6467acb`
  (post-live-charge management view = account-gated Stripe #41; app shows the honest plan-selection state).**
  Refused throughout: channel-sync ribbons, technician rosters, parts-on-order, pacing/goal narratives, workspace-paused
  banners, labour/parts splits, fabricated trends (#39), and any guest-contact card that would breach **#28 PII**.

**◑ GATE 2 — OWNER STARTED (6 of ~24 pages verified).**
- **RESTRUCTURED:** **Owner Bookings** + **Owner Booking-detail** `9a7c087` — the 4 KPI cards wrapped 3+1 (the shared
  owner `.vy-kpi-grid` is `repeat(3)`); fixed with a NEW opt-in `.vy-kpi-grid--4` modifier (existing `repeat(3)` owner
  pages untouched — the count-agnostic-grid discipline, now on the owner side too). Full suite re-confirmed 924/7517
  after the shared-CSS add. Booking-detail's prototype guest **email/phone/country** card refused (#28).
- **AT PARITY** (`9a7c087`/`54b718d`/`cace277`): **Owner Dashboard · Balance · Payouts · My Units** — the owner portal
  was built from `design-system-brief.md`, so it is consistently at-parity (earnings hero + dark NEXT-PAYOUT panel +
  KPI bands + reconciled breakdowns + honest "Soon"/"Not yet available" states). REFUSED: the Dashboard/Units fabricated
  occupancy trends/goals (#39) and — load-bearing — the Dashboard **Upcoming-stays with guest names + amounts** (would
  breach **#28** guest-PII on the owner portal; the app shows the explicit PII note instead).
- **RESUME HERE:** Owner Statement(+PDF) · Unit Detail · Rates · Tax Center(+PDF) · Payout Method · Add Unit · Reviews ·
  Reports · Insights · Calendar/Block · Messages(+thread) · **Team (+ co-owner/accountant/view-only capability views)** ·
  Settings · Help · Notifications · Expenses · Documents · Agreement · Archive. Then **GATE 3 (guest)** → **GATE 4
  (public + onboarding/billing)** → fold the register into `docs/design/CATALOGUE.md` + a final full-suite run.
  Learning carried: the systematic owner KIND-1 (the 4-up KPI grid) is already handled; the remaining owner pages are
  very likely AT PARITY or minor divergences.

**This save:** docs-only, NO code changed; suite **924 / 7517** green on MySQL 8, PHPStan L5 (0) + Pint clean,
`migrate:fresh --seed` clean (unchanged — this session touched only Blade/CSS + docs); HEAD `cace277`, `main`,
local == origin (pushed). Invariants **#1–#45** (unchanged — a presentation pass adds no invariant).
`docs/design/raw/*.html` stay gitignored (served via `php -S 127.0.0.1:8899` for diffing, not in a fresh clone).

## 2026-07-25 — DESIGN-FIDELITY PASS ✅ COMPLETE (all four gates) — presentation-only, suite green throughout

**The deep per-page design-fidelity pass is COMPLETE.** Every app page was diffed against its `docs/design/raw/`
prototype (served at `http://127.0.0.1:8899`) and classified into the three-category discipline, with the full
per-page record in `docs/design/DIVERGENCE-REGISTER.md` (**76 page-rows**) and the summary folded into
`docs/design/CATALOGUE.md`.

- **GATE 1 — Operator** (prior sessions): shell regroup + operator pages RESTRUCTURED / intentional-divergence.
- **GATE 2 — Owner (25 surfaces):** 5 RESTRUCTURED, 11 at-parity, 9 intentional-divergence. **3 presentation-only
  code fixes** — (1) Owner **Statement** hero card was rendering at `opacity:0` (a static `vy-swap-next` class
  overrode `.vy-fade`'s animation with a no-`forwards` keyframe → reverted to base `opacity:0`); removed the class
  so it fades in like every other card (a real invisible-element bug the browser revealed — the "static checks
  can't see a rendered page" lesson, live). (2) Owner **Reports** 2 real KPI tiles wrapped on the `repeat(3)` grid
  → added the opt-in `.vy-kpi-grid--2` modifier (mirrors `--4`; existing pages byte-unchanged). (3) Owner
  **Insights** 4 real KPI tiles wrapped 3+1 → applied the existing `.vy-kpi-grid--4`.
- **GATE 3 — Guest (17 surfaces):** 15 at-parity, 2 intentional-divergence (Login property-marketing-hero + social
  login; Signup **booking-tied claim** vs the prototype's open self-serve signup). **0 code changes.**
- **GATE 4 — Public + onboarding/billing (~12 surfaces):** at-parity (Room detail, Checkout, Signup wizard, Paywall,
  Subscription checkout, Invite-owner, operator Login/Reset/Set-password), 2 scope-divergences (Browse + Search
  results = the app's **per-property** storefront vs the prototype's **multi-property brand collection**). **0 code
  changes.**

**Method:** app page probed/screenshotted vs the prototype screenshot; each gap sorted KIND-1 (layout → FIX with
shared tokens) / KIND-2 (fabricated content or security-breaching → REFUSE) / KIND-3 (beyond the presentation
boundary → DOCUMENT). Per-page commits + register rows; pushed per gate. **The whole pass changed ZERO behaviour** —
the only code touched across all four gates was the 3 owner CSS/Blade layout fixes above; GATE 3 + GATE 4 were
entirely at-parity / intentional-divergence (documentation only).

**Honesty held throughout (REFUSED, never faked):** fabricated occupancy/history/trends/narratives (#39), invented
loyalty/referral/membership + welcome-credit promos, dead-`channels.status` sync ribbons, invented reviews/ratings
(#37), guest-PII/contact/message cards on the owner & operator surfaces (#28), the door-code/wifi outside the gated
in-window Arrival Info (#33), editable role catalogues (#30), stored cards / live charges / Apple Pay (#41/#45),
and open guest self-signup (booking-tied claim only). Where a prototype looked fuller because it was invented, the
app's honest real/sparse/empty state — styled to the prototype's treatment — **is** the parity.

**Verification:** suite **924 / 7517** green on MySQL 8 (re-run at completion), PHPStan L5 = 0, Pint clean after
every commit; blades compile; browser-verified each fix. `migrate:fresh --seed` unaffected. Invariants **#1–#45**
unchanged (a presentation pass adds no invariant). `docs/design/raw/*.html` stay gitignored.

## 2026-07-25 — OWNER ACCOUNT AREA rebuilt: "Profile & account" + "Security" (two gates) — invariant #46

The owner-portal account area was rebuilt to the wireframes (`Owner Settings.html` + `Owner Security.html`),
fully operational (real DB, real backend, real security), resolving a real defect: the profile was tangled
between two identities and **404'd for owner-team sub-users**.

**GATE 1 — `/owner/settings` → "Profile & account" (`bc33f33`).** The identity split:
- **MY PROFILE (per-USER, `auth()`-resolved)** — avatar upload (new `users.avatar_path`, content-validated,
  ULID filename, public disk — the RoomTypePhoto pattern), name (`users.name`), **login email RE-VERIFIED**
  before it switches (parked in new `users.pending_email`; a signed `temporarySignedRoute` whose `{hash}` is
  `sha1($pendingEmail)` is mailed to the new address via `OwnerEmailChangeMail`; the guest-claim mechanism),
  and **language** (`users.locale` persisted + applied via a new `SetLocale` middleware — English-only strings
  today, the preference is honest + saved). New `OwnerAccountController` (self-only, POST→PRG, audit
  field-names-only). **This makes the page work for accountant/view-only SUB-USERS** (they have no `Owner` row —
  the old page's `Owner::where('user_id')` returned null → 404).
- **OWNER / BUSINESS DETAILS** — the existing `OwnerProfileController`, now **route-gated by
  EnsurePrimaryOwner** (#42); sub-users see an honest note. Tax/VAT → Tax Center, bank → Payout Method (linked,
  not duplicated). The stale "More · Soon" stubs are now REAL links. Nav "Settings" → "Profile".

**GATE 2 — `/owner/security` → "Security" (the dead "Security" nav link, now a real page).** Per-USER:
- **Password** (`changePasswordTo`, current-verified) + **2FA** (TOTP + recovery codes) — MOVED here from
  Settings; owner routes renamed `owner.settings.{password,2fa}` → `owner.security.*`.
- **Account protection checklist** — DERIVED from true state (password set · 2FA on/off · unused recovery
  codes), NEVER a fabricated 0–100 score / "suspicious login".
- **Active sessions** — the REAL `sessions` table (database driver); **"sign out all other sessions"**
  deletes the other rows + rotates the remember-token, password-gated.
- **Deactivate account** — 🔒 PRIMARY-OWNER-ONLY (EnsurePrimaryOwner), password-gated, **REVERSIBLE +
  NON-DESTRUCTIVE**: flips `users.status` to 'disabled' (login is active-only, so the account can't sign in) —
  bookings/statements/payouts STAY; PII erasure remains the separate #28 `anonymize()` path. Reactivation
  (status → 'active') restores access.
- **"Request my data"** — a real OWNER-SCOPED JSON export (profile + statements + payouts + units).
- The rest of the wireframe (passkeys, login-approval/trusted-locations, connected apps, full login history,
  new-device alerts, session-timeout/theme) is honest "Soon" — never a fake toggle. The fabricated
  security-score / suspicious-login / billing / connected-channels are REFUSED.

**Mutation-proven (OwnerAccountProfileTest 12 · OwnerSecurityTest 6 · updated OwnerSettings/ChangePassword/
TwoFactorEnrollment):** sub-user profile works (no 404) · #28 self-only · #42 primary-owner gate on
business/deactivate · email re-verify (park/switch/reject-mismatch/reject-unsigned) · avatar upload/remove/
reject-non-image · sessions sign-out password-gated + really deletes rows · deactivate primary-owner-only +
reversible + non-destructive (disabled → can't log in, reactivate → can, no financial data deleted) · export
owner-scoped. All writes are controller POSTs (never /livewire/update — the boundary lesson).

**INVARIANT #46 (new) — the owner account area.** Owner self-profile (name/login-email/avatar/language/
password/2FA/sessions/export) is PER-USER, `auth()`-resolved, never a request id — so every owner-realm user
incl. SUB-USERS manages their OWN account; OWNER-PARTY/business + payout + team + DEACTIVATE are
PRIMARY-OWNER-ONLY (EnsurePrimaryOwner, #42); a login-email change RE-VERIFIES the new address before it
switches; DEACTIVATE is a reversible, non-destructive status flag (login active-only; financial data stays;
erasure stays the #28 anonymize path); active sessions are the REAL sessions table (sign-out-others
password-gated); the protection checklist is DERIVED from true state (never a fabricated score); writes are
controller POSTs, never /livewire/update.

**Verification:** suite **942 / 7631** green on MySQL 8; PHPStan L5 = 0; Pint clean; `migrate:fresh --seed`
clean; browser-verified as the owner AND an accountant sub-user (the sub-user profile works; the real session
shows; deactivate is primary-owner-only). Presentation + the new backend only — money/auth/isolation paths
otherwise untouched. Invariants **#1–46**.

### 2026-07-25 — Batch A "cheap wins" — wired existing built+tested backends (from `docs/GAP-SWEEP.md`)

A systematic READ-ONLY gap sweep (`docs/GAP-SWEEP.md`) surfaced a small set of features whose backend
ALREADY EXISTED (built, routed, often tested) but that rendered as "Soon" or were mislabeled. Batch A wired
them — **LINK/RENDER ONLY**: a nav filter + four PDF-link swaps + the stale-comment cleanup. **No controller,
service, route, money, auth, or isolation path was touched.** Two gates, committed separately.

**GATE 1 — operator nav filter (`687e234`).** `resources/views/components/operator/shell.blade.php` had no
`array_filter`, so a null-route item — how the nav array encodes "this role lacks the permission" — fell
through to the disabled **"Coming soon"** placeholder. A Hotel Manager therefore saw *Roles & access* /
*Settings* / *Billing* as "Coming soon" for pages that exist and work for the org admin (the role 403s at the
route). Mirrored `<x-owner.shell>`'s existing filter: drop null-route role-gated items so a permission-less
role doesn't see them at all. The ONE null kept is `channels` — genuinely account-gated (Channex / a Pro
feature), so its honest "Pro"/"Soon" placeholder is correct. **Nav visibility only; authorization unchanged**
(the route gates still 403). Browser-verified both roles (admin sees all three, unchanged; Hotel Manager no
longer does; `/app/roles` still 403s). New `OperatorNavVisibilityTest` (2 tests).

**GATE 2 — three owner PDF links (booking-summary · remittance · annual-tax).** Each was a real, built,
routed (tested) PDF rendered as a "Soon" tag; wired to its existing route, **each `@ownercan(CAP_EXPORTS)`-
gated** exactly like the neighbouring statement PDF (#42 — a view-only sub-user gets no live export link and
a 403 at the route):
- **Booking-summary** (`owner.statements.bookings`) + **remittance** (`owner.payouts.remittance`) on the
  Statement page. The remittance is offered ONLY when a payout is matched — else an honest "Once paid" (no
  broken link). Surfaced the already-resolved matched-payout `id` in `OwnerStatementView`'s view payload (the
  ONLY consumer is this link) — presentation glue, no service/route change.
- **Annual-tax summary** (`owner.tax.annual` → `OwnerAnnualTaxDocument`) on Documents (per-year link + a
  latest-year shortcut) + Archive (per-year link). Linked ONLY for years with committed statements — exactly
  what `OwnerTaxService::annualSummary` sums (status ∈ finalized/paid) — so a link never 404s. The stale
  "…Owner Annual Tax Summary PDF does not exist" comments were deleted; only the full-archive ZIP stays
  honestly deferred (no ZIP builder).

**Verification.** Browser-verified as the demo owner: all four PDFs download **200 / application/pdf**
(`vyrobel-bookings-…`, `vyrobel-remittance-…`, `vyrobel-annual-tax-…`, `vyrobel-statement-…`); no raw Blade;
no stale "Soon" for the wired features. As a **view-only sub-user** (#42): the Statement "More documents"
card is hidden and all four export routes return **403** (fail-closed). Suite **945 / 7644** green on MySQL 8
(+3: two nav-visibility tests + one Statement "Once paid" empty-state test; three view-tests updated from the
old "Soon" assertions to the new real links); PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean.
Money/auth/isolation paths + invariants **#1–46** UNCHANGED.

### 2026-07-26 — Batch B ANALYTICS — per-unit + by-unit + Insights cadence, all REAL (from `docs/GAP-SWEEP.md` §5)

The three Batch-B analytics features from the gap sweep, built EXACTLY to their wireframes (the `vy-*` design
system) fed with REAL aggregates — never runtime fabrication (#39). Three gated commits.

**GATE 1 — Owner Statement "By unit" split (`dbf07d3`).** The deferred stub became a REAL per-unit split
(name · N nights · X% occupancy · NET · gross · % of net · proportional bar · Total net earnings). Pure ledger
arithmetic, so it reconciles BY CONSTRUCTION (#17): it reads the exact ledger lines the statement CLAIMED
(`owner_ledger.statement_id`), groups them by `unit_id`, and nets each group through the shared
`OwnerLedgerAttribution` — so Σ per-unit net (+ any unattributed remainder from a flat fee) == the statement's
source net, to the cent. Occupancy/nights via the shared `PerformanceMetrics`. Owner's own units only (#28); a
draft shows an honest "available once finalized". Reconciliation + #28 tests; browser-verified (2 demo units,
€442.80 + €442.80 = €885.60).

**GATE 2 — per-unit analytics on My Units + Unit Detail (`1e1269f`).** Filled the "Soon" per-unit metrics with
real aggregates + a NEW shared bar-chart component (`<x-charts.bars>`, reused by Insights):
- My Units: KPI band (Units · Avg occupancy · **Earned this month** = real ledger net); per-unit cards with real
  **Revenue** (gross, for comparability) + **Nightly rate** (achieved ADR); a **Best performer** ranking (★
  badge + card); a **"Needs your attention"** feed of REAL items only (a genuine payout hold, a missing payout
  method, an agreement renewing within 30 days) — the prototype's fabricated rate proposals NOT built (#39).
- Unit Detail: **Revenue · 6 months** (this unit's NET from the ledger — empty months honest zeros, never the
  design's fabricated prior years), **Occupancy · 12 months** (the shared `OwnerOccupancy` + real avg/peak), a
  **best-performer badge** (only when the unit genuinely leads, >1 unit), **Channel mix** by the real
  `reservations.source` enum (never an OTA brand). Q4 can't-be-real items kept honest (cleaning schedule /
  per-unit payout timeline / guest-message bodies stay omitted; the rail links the real owner-level Payouts;
  #28). Anti-drift: `OwnerUnitAnalytics` (per-unit metrics + best-performer, one definition read by both).

**GATE 3 — operator + owner Insights booking cadence, one shared computation.** A NEW shared
`App\Support\Analytics\BookingCadence` (anti-drift — ONE implementation read by BOTH Insights pages) computes,
from real rows: the lead-time distribution + median (real `created_at` → `check_in`), day-of-week occupancy
(the availability spine for a property scope, the unit count for an owner scope), and on-the-books revenue
(reuses `PerformanceMetrics` over a forward range). Both pages replaced their "Soon" deferrals with real
sections: a **Booking window / Median lead time** KPI, a **Next 90 days** (on-the-books) KPI, the **"When
guests book"** lead-time distribution, and **"Nights that fill first"** day-of-week occupancy (via the shared
`<x-charts.bars>`). Owner scope = `OwnerScope`; operator = `PropertyContext` (#28). Honest empty states when
history is thin.

**THE DEMO SEED (Q1/Q2 — flagged demo data, NOT production).** `DemoSeeder::seedAnalyticsHistory()` adds a
deterministic spread of ~18 real bookings through the certified `AssignmentService`, with `created_at`
backdated to realistic leads before check-in (the ONLY demo affordance — production has genuine timestamps),
so Insights renders a MEANINGFUL, REAL shape on the demo (24 reservations across 12 created-at dates → median
lead 42 days, buckets 21/21/42/17%). Owner (apartment) stays are attributed so the owner ledger stays
consistent. The RUNTIME always computes real aggregates from real rows — proven by `BookingCadenceTest` (change
a `created_at`, the distribution moves; no history → an honest empty state, no invented median).

**REFUSED throughout (#39):** the prototype's recommendation/pricing engine — "Worth a look", "Suggested next
step", rate proposals, "Turn insight into rate" — and the per-element **"vs last year"** deltas (the app shows
no fabricated LY; the seeded prior-year comparison was scoped OUT — the demo has no prior-year availability,
and the runtime shows real current-period metrics only, never an invented delta).

**INVARIANT #47 (new) — Batch-B analytics are REAL + reconciling + shared.** Per-unit figures come from
`PerformanceMetrics` / the owner ledger (`owner_ledger.unit_id`); the by-unit statement split reconciles to the
statement net to the cent (#17); booking cadence (lead-time / DOW / on-the-books) is ONE shared
`BookingCadence` read by both Insights pages (never two copies); best-performer is the real ranking with an
honest empty state; owner analytics are owner-scoped (#28), operator via `PropertyContext`; demo analytics
timestamps are flagged demo-only and the runtime computes real aggregates regardless; NO runtime fabrication of
trends/benchmarks/recommendations/LY-deltas (#39).

**Verification:** suite **951 → 96x** green on MySQL 8 (Batch-B tests: by-unit reconciliation + #28; per-unit
real + empty-state; `BookingCadenceTest`; operator + owner cadence render); PHPStan L5 = 0; Pint clean;
`migrate:fresh --seed` clean (24 reservations, real lead-time shape). Browser-verified all five surfaces vs the
prototypes. Money/auth/isolation paths + invariants #1–46 UNCHANGED. Presentation + read-only aggregates only.

### 2026-07-26 — Batch C OPERATOR OPERATIONS — assign · move · add-charge · payment money-gate · tax editing

The Batch-C operator-operations features from `docs/GAP-SWEEP.md` §5, built exactly to the wireframes with the
`vy-*` system, WIRING EXISTING guarded services — never raw writes. **A key finding corrected the sweep: the
on-desk CASH payment was ALREADY built** (a real Record form → `FolioService::recordPayment` via the default
manual gateway); the genuine gaps were narrower.

**GATE 1 — Housekeeping assign (`a728fcc`).** The board's "Assign · Soon" → a real housekeeper picker.
`housekeeping_tasks.assigned_to` + the assignee display already existed; wired the write:
`POST /app/housekeeping/{task}/assign` → a new `HousekeepingService::assign` → PRG, gated `housekeeping.manage`.
🪤 REAL STAFF: the picker + guard use `StaffRoster::housekeepers()` / `housekeeperIds()` — the **Housekeeping
department only** — so a non-housekeeper / cross-org / fabricated id is rejected (422). Demo: a real
`demo-housekeeper` (Housekeeping role). Mirrors `ScheduleController::assign` / `MaintenanceActionController`.

**GATES 2+3 — reservation move + add-charge + the payment money-gate (`b6bd623`, same screen).**
- **Move room:** a picker of SAME-room-type units free for the dates → `POST /app/reservations/{id}/move` →
  `AssignmentService::assignUnit` (availability `FOR UPDATE` lock + no-overlap + same-type), NEVER a raw
  update. Gated like check-in/out (any back-office; cross-org 404). Proven not a raw write: a move to an
  OCCUPIED unit is refused by the guard. Same-room-type only (Q5); a cross-type target is refused.
- **Add charge:** `POST …/charge` → `FolioService::postCharge` (#17 integer, append-only), gated `folios.manage`.
- 🔒 **THE MONEY GATE — on-desk payment idempotency + method selector.** recordPayment gained an optional
  idempotency key; a per-submit nonce on the form makes a double-click / re-submit record ONCE — checked
  before the gateway charge, backed by a new UNIQUE `payments.idempotency_key` index. **Mutation-proven both
  ways:** two DIFFERENT nonces record twice, the SAME nonce records once (browser: a double-submit produced
  ONE €77 payment). All existing money guards intact (#17, ≤0 rejected, single `FolioService` path, correct
  folio tenant-404, append-only, `payments.record`-gated). The **method selector** — Cash / Bank transfer
  record NOW (real manual payments, the method on the payment metadata); **Card is the account-gated Stripe
  seam**, labelled "via Stripe · test-mode" and NOT charged in the build (an honest deferral).

**GATE 4 — Tax & fees editing (this commit).** The Settings "Editing · Soon" (the sweep's named gap) →
editable, bounded `Tax`/`Fee` rate/amount via `SettingsService::updateTax`/`updateFee` (rate clamped 0–100,
amount ≥ 0 integer minor #17) → `POST /app/settings/tax-fees` → PRG, gated `properties.manage`; tenant-safe
(a cross-org Tax/Fee id resolves to null and is skipped). **Scoped to a focused follow-up (honest "Soon"):
Invoice numbering** (needs a new store — no invoice-series columns exist) **and Branding** (property name is
already editable via the existing Property-details form; logo/white-label is a larger media surface).
**Notification routing** stays "Soon" (Q4 — a net-new store). Transparent: two of the three approved settings
editors are deferred, not silently dropped.

**INVARIANT #48 (new) — operator-ops actions wire the guarded services, never raw writes.** A housekeeping
assign sets `assigned_to` only after `StaffRoster` validates real Housekeeping-dept staff; a room move goes
ONLY through `AssignmentService::assignUnit` (lock + no-overlap + same-type); a charge ONLY through
`FolioService::postCharge`; a payment ONLY through `FolioService::recordPayment`, now **idempotent** on the
on-desk path (a double-submit records once); the method selector is honest (cash/bank real, card = the
deferred Stripe seam); settings edits are bounded + `properties.manage`-gated; every write is a controller
POST → service → PRG (never /livewire/update), tenant/PropertyContext-scoped, cross-org 404. Each gate
mutation-proven to bite.

**Verification:** suite **968 → 97x** green on MySQL 8 (Batch-C tests: assign real-staff/permission/scope;
move via assignUnit incl. occupied-refused + cross-org 404; add-charge #17 + folios.manage; **payment
idempotency double-submit → 1, two nonces → 2**, method selector card-deferred/cash-manual; tax editing
bounded + clamp + gate); PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean. Browser-verified every
screen. Money/auth/isolation paths + invariants #1–47 otherwise UNCHANGED.

**Security follow-up (2026-07-26, same day) — property-scope the tax/fee edit (cross-property IDOR).** The
automated commit review flagged that `SettingsActionController::updateTaxesAndFees` resolved a Tax/Fee by
`find($id)` under the ORG scope only — so from Property A's settings an operator could POST a sibling
Property B's tax id (a property a `property_user`-restricted user may not access, #38). Fixed: the write is
now `->where('property_id', $currentProperty->id)->find($id)` for both Tax and Fee (a cross-property id → null
→ skipped), and the READ (`OperatorSettings::taxes()`/`fees()`) is property-scoped to match, so the editor
only ever shows + edits THIS property's rows. Mutation-proven: with the context pinned to A, a POST carrying
B's tax id leaves B unchanged. Suite green.

### 2026-07-26 — NOTIFICATION EXPERIENCE — the top-bar bell now opens a PANEL, both shells (2 gates)

**The founder's ask:** clicking the notification bell opened the FULL page directly; it should open a
**dropdown/panel preview**, and reach the full page only via an explicit "Open all notifications →" link — both
surfaces matching the notification design system exactly. Investigated first (Phase-1 findings), then built to
the approved decisions. **Presentation + one shared-helper extension only; behaviour/money/auth/isolation
UNCHANGED. Invariants #1–48 untouched.**

- **Key honest finding:** there is **NO separate panel wireframe** — the prototype bell is a static button that
  opens nothing (verified in the proto: clicking it does nothing; the only "panels" are decorative blobs). So
  "match the wireframe" for the panel means **match the FEED PAGE's design language** — recorded honestly here
  and in the DIVERGENCE-REGISTER. The owner "Notifications" prototype file is the **preferences** screen, not a
  feed (already noted at parity).

- **GATE 1 — the bell → PANEL (both shells), real feed, exact design language** (`a07ca65`):
  - **Anti-drift:** extended the ONE shared `NotificationFeed` with a capped `recentForUser($user, $limit = 6)`
    (+ the unread count) and a private `map()` — the single row-shape mapper now shared by the full page AND the
    panel, so they cannot drift. The bell reuses this unread count for its badge.
  - **Shared components:** `<x-notifications.category-icon>` (ONE definition of the rounded-square category
    glyph tile) + `<x-notifications.bell :open-all-url>` (the Alpine popover — Livewire-4-injected Alpine, **no
    new dependency**), reused **verbatim by the operator + owner shells**. The bell is now a `<button @click>` —
    it NO LONGER navigates; only the footer "Open all notifications →" link reaches the full page. Owner layout
    gained the `[x-cloak]` rule (operator already had it).
  - **Panel:** right-aligned ~360px popover under the bell — header "Notifications · N unread"; the top ~6 recent
    rows in the feed's design language (square category tile · bold-if-unread title · uppercase REAL category tag
    · one-line context · relative time · unread dot + faint orange row wash); each row links to its REAL target;
    footer = "Mark all read" (if unread>0, the EXISTING self-only POST `notifications.read-all`, PRG) + "Open all
    notifications →". `@click.outside` + `Esc` close it; honest "All caught up" empty state.
  - **🪤 Real only:** REFUSED the mock's fabricated demo rows and the fake **"Channels"/"Housekeeping"** pills
    (not real notification types) — the panel + page render only real notifications and the categories that
    actually exist, with honest labels (`system`→"System", `maintenance`→"Maintenance", `reputation`→
    "Reputation"). **🔒 Self-only (#28/#26):** reads the authenticated user's own Notifiable rows (an owner sees
    only their own, no cross-owner/tenant; payloads name-only) — the panel inherits, NO new scope. Mark-read
    (single + all) reuses the EXISTING self-only POST controller routes (a cross-user id 404s), never
    `/livewire/update`.

- **GATE 2 — full-page parity deltas + docs** (this commit):
  - The two approved deltas on **both** feed pages so the page reads identically to the panel: the row icon is
    now the shared rounded-**square** tile (operator page was a circle; the owner page had **no** icon — now
    added), and unread rows carry the faint orange **wash** (in addition to the dot). Presentation-only.
  - The pages still render real notifications, the real category pills (honest labels — no fake pills), mark-all
    + per-row mark-read (existing routes), the empty state, and the preferences footer link.

- **Verification:** browser-verified BOTH shells — bell opens the panel (does NOT navigate; both operator +
  owner), real recent rows in the feed's design language, a row links to its real target, "Mark all read" (PRG)
  clears the badge, `Esc` + click-outside close it, "Open all notifications →" is the ONLY navigation to the
  page; the two page pages read identically to the panel. Suite **977/977** green (+5 new
  `NotificationPanelTest`: capped/newest-first/unread-count, self-only, panel-in-operator-shell,
  panel-in-owner-shell, honest empty state); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean.

### 2026-07-26 — POLISH BATCH 1 — defined the missing CSS tokens/classes (the highest-leverage sweep finding)

**The finding (`docs/POLISH-SWEEP.md` §1, "the single biggest signal"):** the app *referenced* six CSS tokens/classes it
never *defined*, so those elements silently fell back to transparent/plain/unstyled — invisible to a green suite (the
pages render 200, nothing errors). **Presentation-only fix: DEFINE each one ONCE in the shared design system** (each
realm's inline `:root` / `<style>` — there is no single external stylesheet; that per-realm block IS the shared layer),
so every referencing element across all four realms picks it up. **No behaviour/logic/money/auth/isolation; invariants
#1–48 untouched.**

- **Values (prototype-exact vs derived, noted):**
  - **`--vy-shadow-dark: rgba(60,30,10,0.28) 0 10px 30px`** — **brief-exact** (`design-system-brief.md` §"Dark hero
    panel" + the owner layout's existing declaration). Added to the OPERATOR `:root` (owner already had it).
  - **`--vy-wash: #F6EBE2`** — **derived-consistent, and in fact the value the app already renders app-wide via the
    `var(--vy-wash, #F6EBE2)` fallback** (owner reviews/team chips, operator schedule chips). Only the GUEST portal pages
    used `var(--vy-wash)` with NO fallback → transparent. Declared in the guest shell + owner + operator token blocks.
  - **`.vy-card--accent { border-color: var(--vy-accent-border) }`** — mirrors the owner layout's canonical definition;
    added to the OPERATOR layout (consumers: reservation-detail "Record a payment", maintenance-ticket-detail "Actions",
    guest-profile "Edit profile").
  - **`.vy-pill.is-on`** — **prototype-consistent** (the prototype's selected pills are gradient-filled with white text +
    an accent shadow, as the app's `.vy-seg-btn.is-on`): `background: var(--vy-grad); color:#fff; border-color:transparent;
    box-shadow: var(--vy-shadow-accent)`. Added to the OPERATOR layout (consumers: Insights + Reports range pills).
  - **`.vy-field` / `.vy-field-label`** (+ `.vy-fl-note`/`.vy-fl-right`) — mirrors the operator layout's definition;
    added to the OWNER layout (consumers: block-calendar, payout-method, tax-center, rates, add-unit form labels).
  - **`.vy-flash`** base — gave the base a neutral glass tone (`background: var(--vy-glass)` + hairline border + ink) so a
    bare `.vy-flash` (billing / billing-checkout / insights multi-currency / portfolio notices) is styled; `.is-ok`
    (green) / `.is-err` (amber) still override. Operator layout.

- **Files (3, presentation-only):** `resources/views/components/layouts/operator.blade.php` (`--vy-wash`,
  `--vy-shadow-dark`, `.vy-card--accent`, `.vy-pill.is-on`, base `.vy-flash` tone) · `…/layouts/owner.blade.php`
  (`--vy-wash`, `.vy-field`/`.vy-field-label`) · `…/components/guest/shell.blade.php` (`--vy-wash`).

- **Browser-verified across all four realms** (these were invisible to tests): the **guest chat inbound bubble** now
  renders its cream `#F6EBE2` fill (the conversation no longer looks one-sided — before/after captured); the operator
  **Insights active range pill** shows its gradient selected state (was identical to inactive); the owner
  **payout-method/…** form labels render styled uppercase; `--vy-shadow-dark`, `.vy-card--accent`, and the bare
  `.vy-flash` resolve (computed-style confirmed). This closes **POLISH-SWEEP findings D1–D6** and their ~dozen dependent
  elements. Suite green; PHPStan L5 (0); Pint clean.

### 2026-07-26 — POLISH BATCH 2 — hover states on shared tap-targets (the "feels inert" finding)

**The finding (`docs/POLISH-SWEEP.md` M2):** clickable cards/rows across the guest portal, the public storefront, and
several operator/owner card-grids had **no hover feedback** — the UI felt inert (hover is invisible to a green suite).
**Presentation-only fix: add the prototype's hover treatment ONCE to a shared tap-target class**, applied **opt-in to
genuinely-interactive elements only** so no static/placeholder card gets a misleading hover. No
behaviour/logic/money/auth/isolation; invariants #1–48 untouched.

- **Prototype hover vocabulary (extracted from the compiled prototype's `:hover` rules):** cards lift on hover —
  `transform: translateY(-1…-3px)` + a soft elevated shadow (`0 18–44px rgba(150,90,50,.16–.22)`); rows take a subtle warm
  background tint (`rgba(120,70,40,.06)` / `rgba(217,102,46,.08)`). Consistent with the app's existing `translateY(-1px)`
  button-lift + `.15s` transitions.
- **Shared classes defined once per realm (`layouts/operator.blade.php`, `layouts/owner.blade.php`,
  `components/guest/shell.blade.php`, `components/public/shell.blade.php`):**
  - **`.vy-card--tappable`** — `cursor: pointer` + `:hover { transform: translateY(-2px); box-shadow: 0 18px 44px
    rgba(150,90,50,.18) }` (guest: a softer `0 16px 40px rgba(150,90,50,.16)`). **Prototype-matched** (lift + elevated shadow).
  - **`.vy-row--tappable`** (guest) — `:hover { background: rgba(120,70,40,.05) }` (**prototype-matched** warm row tint).
  - **`a.vy-owner-row:hover`** (operator) — `background: rgba(255,255,255,.5)`, scoped to the anchor so the static `<div>`
    pending-invite rows are untouched.
- **Applied opt-in to interactive tap-targets** (5 view files): guest **Trips** cards (via a new `$tappable` include flag on
  the shared `trip-card` partial — set true from Trips, default false so the partial reused as the **Reservation-detail
  header** stays static), guest **Messages** rows, storefront **Browse** room cards, owner **My Units** real cards (NOT the
  dashed "List a new property · Soon" placeholder), operator **Portfolio** property cards. `a.vy-owner-row` covers **Owners**.
- **🔒 Interactive-only scoping — browser-verified:** the dashed unit placeholder carries **0** modifiers; the
  Reservation-detail trip-card header renders **0** tappable cards — neither gets a misleading hover.
- **Browser-verified across realms** (hover is invisible to tests): real hover fires on **guest** Trips cards
  (`translateY(-2px)` + `0 16px 40px` shadow — before/after captured) and **operator** Portfolio cards (`translateY(-2px)` +
  `0 18px 44px`); **owner** unit-card modifier + `cursor:pointer` + `:hover` rule confirmed; **public** uses the byte-identical
  rule (the demo storefront wasn't reachable on this seed — a slug/config detail unrelated to this change). Closes
  **POLISH-SWEEP M2**. Suite green; PHPStan L5 (0); Pint clean.

### 2026-07-26 — POLISH BATCH 3 — dark-panel swaps + tokenize (`docs/POLISH-SWEEP.md` D7/D8/D9)

**Presentation-only.** Two owner panels the prototype renders DARK were light in the app; a Tax-Center banner icon was missing;
and a few flagged pages carried hardcoded hex / off-token radii. **CONTENT/figures UNTOUCHED (#17/#39); invariants #1–48
untouched.**

- **Dark-panel swaps — REUSE the shared `.vy-card--dark`** (grad-dark bg + white text + `--vy-shadow-dark`; = the design
  brief's "Dark hero panel", the same treatment as the owner Dashboard/Payouts next-payout panels), not a one-off:
  - **Owner Booking-Detail NET-TO-YOU rail** (`booking-detail.blade.php`): `.vy-card--accent` → `.vy-card--dark`; the inline
    divider → `rgba(255,255,255,.14)`; the "—" fallback → `rgba(255,255,255,.4)`. The real net figure renders white on dark
    (`.vy-hero-figure` has no own colour → inherits the panel's white). Browser-verified: €442.80 white on the dark rail.
  - **Owner Tax-Center "About your tax"** (`tax-center.blade.php`): `.vy-card` → `.vy-card--dark` + a leading info icon
    (circle-i tile). Copy unchanged.
  - **Anti-drift:** added shared child overrides to the owner layout so ANY `.vy-card--dark` styles its content —
    `.vy-card--dark .vy-line-sub { color: rgba(255,255,255,.6) }` + `.vy-card-title { color:#fff }` (the `.vy-eyebrow`
    override already existed). So the two panels reuse ONE dark treatment; future dark panels inherit it.
- **Tax-Center held-payout banner icon** — added the prototype's warning icon (triangle-exclamation in a filled `--vy-grad`
  circle + `--vy-shadow-accent`) leading the banner; content unchanged. Browser-verified.
- **Tokenize (D9, the flagged pages):**
  - **public Room** (`booking/room.blade.php`) — the hexes that EQUAL a token replaced 1:1 (zero visual change):
    `#2B2320→--vy-ink`, `#8A8079→--vy-muted`, `#A69A92→--vy-faint`, `#E8703A→--vy-accent-3`, `#C2410C→--vy-accent`,
    `#F6EBE2→--vy-wash`; `border-radius:9px→--vy-r-sm`.
  - **guest pay-error** (`guest/payments.blade.php`) + **operator Schedule** error banner — the off-palette brick-red
    (`#C2453A`/`#8A2F27`) → `--vy-accent` (a minor on-palette correction, matching the operator `.vy-flash.is-err` convention);
    Schedule `#F6EBE2→--vy-wash`.
  - **Left intentionally** (noted, not drift): the roster's **bespoke leave/conflict status tints** (`#B0322A`, `#FBE9E7`,
    `#FBF3E4`, `#F4E7CC`, `#8A6E2F`) — semantic status colours with no exact palette token (a candidate for a future
    status-token set); and the Room one-offs `#4A4038`/`#6b6b6b`/`22px` (no exact token). Browser-verified the Schedule
    renders on the tokens (`--vy-wash` → the identical `rgb(246,235,226)`; 0 raw tokenized-hex left).
- **Files (6, presentation-only):** `layouts/owner.blade.php`, `livewire/owner/booking-detail.blade.php`,
  `livewire/owner/tax-center.blade.php`, `booking/room.blade.php`, `guest/payments.blade.php`,
  `livewire/operator/schedule.blade.php`. Closes **POLISH-SWEEP D7/D8/D9**. Suite **977/977**; PHPStan L5 (0); Pint clean.

### 2026-07-26 — POLISH BATCH 4 — interaction fixes + the double-submit-disable (`docs/POLISH-SWEEP.md` I1/I2/I3/D14)

Two gates. **No money/auth/isolation LOGIC changed — the fixes are UI/interaction/layout; the services/guards/routes are
untouched; invariants #1–48 intact.**

- **GATE 1 — the double-submit-disable (a REAL correctness fix, its own gate; same class as the Batch-C payment
  double-record).** A slow, state-changing submit with no disable-on-click invites a double-click → a double-execute.
  - **Mechanism — framework-free (the guest portal + public storefront + the standalone auth pages load NO Alpine/Livewire,
    so an Alpine guard is inert there):** a shared delegated vanilla script `partials/submit-guard.blade.php` (`@once`,
    self-guarded) guards every `<form data-guard-submit>` — the FIRST submit disables the submit button + swaps its label
    (via `<x-busy-btn data-busy-label>`); a rapid SECOND submit is `preventDefault`'d. It runs in the BUBBLE phase AFTER any
    inline `onsubmit="return confirm(…)"`, and short-circuits on `defaultPrevented`, so a CANCELLED confirm never sticks the
    button disabled. Included in the guest shell + public shell + operator layout; the standalone signup page keeps its own
    equivalent vanilla guard. Reuses no new dependency.
  - **Guarded (slow / money / inventory / provisioning writes):** public checkout Confirm (writes inventory), signup
    Create-account (provisions an org), guest Pay-by-card (Stripe redirect), operator record-payment / add-charge / move-room,
    operator refund (full + partial), + the guest Account writes.
  - **🔒 DEFENSE-IN-DEPTH — the server guards are UNTOUCHED.** The disable is the front-line UX guard ON TOP of the payment
    idempotency nonce (Batch C — verified still present), the guest-charge `firstOrCreate`, and the atomic provisioning +
    rate-limit. **Browser-PROVEN fires-once:** guest Pay-by-card (label→"Redirecting…", 2nd+3rd submit blocked), signup
    ("Creating your account…"), operator record-payment ("Recording…"); a simulated cancelled-confirm does NOT lock the
    button. **FLAGGED:** `add-charge` (`postCharge`) has NO server-side idempotency — the UI guard is the front line; a server
    nonce (like the payment) is a warranted follow-up.
- **GATE 2 — interaction/consistency:**
  - **Account-in-shell (I1):** `guest/account.blade.php` rendered in the narrow auth card (`@extends('guest.auth.layout')`)
    → a guest on Account was stranded with no portal nav. Now renders in `<x-guest.shell active="account">` (nav present,
    760px, "Account" active). Added shared `.vy-field`/`.vy-label`/`.vy-input`(+`:focus` ring)/`.vy-error`/`.vy-link` to the
    guest shell so its forms render styled. No auth-logic change (same authed page, right shell). Browser-verified.
  - **Settle-stub (I2):** the guest Reservation + Receipt pages showed a disabled **"Settle · Soon"** while the Payments hub
    offers a LIVE "Pay by card" for the same booking — a contradiction. Resolved to ONE honest truth: a live **"Pay your
    balance →"** link to `guest.payments`. Browser-verified (no "Soon", links to the hub).
  - **Auth uniformity (D14, partial):** `onboarding` + `invite-invalid` lacked the auth family's button-lift + card fade +
    drift blob → added, matching login/reset/etc. **Deferred (noted):** the signup step cross-fade (I4) and the set-password
    strength meter (a feature-add, not a uniformity bug).
- **Files:** new `components/busy-btn.blade.php` + `partials/submit-guard.blade.php`; edited `booking/checkout`,
  `guest/payments`, `guest/account`, `guest/reservation`, `guest/receipt`, `auth/onboarding`, `auth/invite-invalid`,
  `livewire/reservation-detail`, `livewire/operator/reservation-refund`, `components/guest/shell`, `components/public/shell`,
  `components/layouts/operator`. Presentation/interaction-only. Suite **977/977**; PHPStan L5 (0); Pint clean.

### 2026-07-26 — POLISH BATCH 5 — the micro-interaction "feel" pass (`docs/POLISH-SWEEP.md` M1/M3/D10)

Presentation-only (CSS/JS micro-interactions). **The real figures/data are UNCHANGED — only their animation-in.**
Invariants #1–48 intact.

- **STEP 1 — KPI/hero count-up (M1).** The prototype animates KPI numbers counting up (~800ms cubic ease-out); the app
  rendered them statically (operator: none; owner: only 4 hero figures, via **4 duplicated `@script` blocks** — drift).
  - **ONE shared mechanism** (`resources/views/partials/count-up.blade.php`, `@once`), included in the operator + owner
    layouts; the **4 owner `@script` duplicates were removed** (dashboard / statement / payouts / archive). Anti-drift.
  - **Parse-from-text:** it reads the number out of the already-rendered string (handling €, %, thousands, decimals, a
    leading sign), animates a cosmetic 0→value over 800ms cubic ease-out, then **restores the original `innerHTML` on the
    final frame** — so the figure is authoritative, never an approximation. 🔒 **#17** money lands on the exact cent; **#39**
    no fabricated value. Targets the shared KPI/hero classes (`.vy-dk-val`, `.vy-kpi-val`, `.vy-hero-figure`,
    `.vy-dark-figure`, `[data-countup]`) so **no per-page markup change** is needed; **skips** `.is-deferred`, non-numeric
    ("Nothing due"/"—") and zero values. 🔒 **Graceful:** JS-off / reduced-motion → the real number is already in the DOM.
  - **Browser-verified:** operator Insights money KPI counts up **€46.97 → €116.84** (lands exactly); owner dashboard hero
    **€337.88 → €885.60** (lands exactly); the deferred "—" tile is correctly NOT animated. Handles money, %, "2.4 nights",
    "42 days", grouped "€6,660.00".
- **STEP 2 — loading skeletons (M3): RESOLVED HONESTLY, no fake skeleton added.** The busy-state half was already fixed in
  Batch 4 (submit-disable + "Processing…"). For a *loading* skeleton: the app is **fully server-rendered** — no
  `wire:loading`, no `#[Lazy]`, no `wire:navigate` (confirmed) — so there is **no genuine in-page async gap** to attach a
  content skeleton to (page-nav loading = the NProgress bar; form submits = the Batch-4 busy state). The existing
  `.vy-skeleton` usages are **deferred "not yet available" placeholders** (occupancy) — animating them would be a **fake
  perpetual shimmer** that falsely implies loading (#39), so they stay **static**. `vyShimmer` remains a reserved decorative
  keyframe (unused). Per the task's own honesty rule, **no fake skeleton was added** — the correct honest resolution.
- **STEP 3 — chart consolidation (D10): assessed, deferred.** Most charts already use the shared `<x-charts.bars>`
  (unit-detail, operator + owner Insights "nights that fill first", operator Insights). The remaining hand-rolled charts
  aren't clean drop-ins — the owner **Dashboard** earnings chart is an **interactive 3M/6M/12M** variant (the component is
  static), and the component renders **per-bar value labels** the hand-rolled month charts omit — so a swap changes the look
  and needs a focused design decision. Left as a **P3 design-consolidation follow-up** rather than a risky visual change.
- **Files:** new `partials/count-up.blade.php`; edited `components/layouts/operator` + `owner` (the includes), and removed the
  4 `@script` blocks from `livewire/owner/{dashboard,statement,payouts,archive}`. Closes **POLISH-SWEEP M1** (M3 resolved
  honestly, D10 deferred). Suite **977/977**; PHPStan L5 (0); Pint clean.

### 2026-07-26 — POLISH BATCH 6 — owner Expenses proportional bars (`docs/POLISH-SWEEP.md` F1); sweep actionable items cleared

The last actionable sweep item. **Real aggregate + presentation; the amounts are the owner's REAL expense figures — never
fabricated (#39); reconciling (#17); owner-scoped (#28). Invariants #1–48 intact.**

- **The bars.** The owner Expenses "By category" was a plain list. Each REAL category now renders a **proportional bar** via
  the **shared `.vy-track`** (7px track + peach-gradient fill — the SAME treatment the statement by-unit split uses, not a
  one-off). The bar's basis is **÷total** (share of the block total, the same as the by-unit split's `bar_width` — the
  prototype draws a comparative ÷max bar, but the task + the by-unit precedent specify the reconciling ÷total share, and for
  the demo's single category the two are identical).
- **Real aggregate (deterministic, reconciling).** `OwnerExpenses::byCurrency()` gained a per-category `total_minor` +
  `bar_width` = `round(catTotal / blockTotal × 100)`. The category totals are Σ of the owner's REAL non-voided
  `owner_expenses` rows, so the shown categories **reconcile to the block total** (#17). **#39:** a category with **no real
  expense is filtered out** (no €0.00 filler row/bar). **#28:** the query is already owner-scoped (the component reads the
  authed owner's own expenses only). No service/ledger/money logic changed — a pure derived proportion over existing rows.
- **Verified.** Browser: the demo owner's lone Maintenance €180 renders a **full 100% bar** and reconciles to the €180 total;
  cleaning/other are filtered (no filler). Test (`OwnerExpensesViewTest`, +1 test / assertions): a 3-category owner (cleaning
  €75 + other €33 + maintenance €120 = €228) renders bars **33% / 14% / 53%** (deterministic, proportional to the real
  amounts) via the shared `.vy-track`, and a lone-category owner renders a **100%** bar with no "0 expenses" filler (#39).
- **Files:** `app/Livewire/Owner/OwnerExpenses.php` (the `bar_width` aggregate + zero-filter), `livewire/owner/expenses.blade.php`
  (the `.vy-track` bars), `tests/Feature/OwnerExpensesViewTest.php` (+ proportion/reconcile/#39 assertions). Closes
  **POLISH-SWEEP F1**. Suite green; PHPStan L5 (0); Pint clean.
- **Sweep status: the ACTIONABLE items are cleared** across Batches 1–6 (all P1/P2 + the actionable P3: M1 count-up, F1 bars;
  M3 skeletons resolved honestly). **Deferred P3 tails (small, tracked):** signup step cross-fade (I4), set-password strength
  meter (D14), remaining guest-portal focus rings (M5), D10 chart consolidation (a per-bar-label design decision).

### 2026-07-26 — ADD-CHARGE SERVER IDEMPOTENCY — closed the last money-path gap flagged by Polish Batch 4 (I3)

Batch 4's double-submit-disable is a **UI** guard; it flagged that `add-charge` (`FolioService::postCharge`) — unlike the
on-desk payment path (Batch C) — had **no server idempotency**, so a determined double-submit (a scripted POST bypassing the
disable, a network retry, a double-click racing it) could **double-POST a charge** to a folio. This closes that gap by
**mirroring the existing on-desk payment idempotency EXACTLY** — the same per-submit nonce / pre-check / unique-index / catch
mechanism (`recordPayment`), not a new one. **Money-path change: maximum care. Invariants #1–48 intact; money/auth/isolation
logic UNCHANGED — idempotency ADDED on top, not a rewrite.**

- **The mechanism (mirror of `recordPayment`).** New nullable-unique `folio_items.idempotency_key`
  (`2026_08_01_000000_add_idempotency_key_to_folio_items`, `after('posted_at')`; on `FolioItem::$fillable`). `postCharge`
  gained a trailing `?string $idempotencyKey = null`: **pre-check** `FolioItem::where('idempotency_key',$key)->first()` returns
  the original line BEFORE the domain event fires (a duplicate → the original, once, no re-fired `folio.charge_posted`); the
  `DB::transaction` stores the key on the `FolioItem::create`; a `catch (UniqueConstraintViolationException)` around the txn
  returns the winner's row on a **concurrent** race (the pre-check misses when two requests both `SELECT` empty). **The
  ordering is load-bearing** (pre-check → write → catch-race), identical to the payment path.
- **Opt-in — every existing caller preserved.** A **null** key = no idempotency = unchanged behaviour, so `postAddOn` /
  `applyTax` / `applyFee` and the room posting each still write a **distinct** line (they SHOULD — two taxes are two lines).
  Only the on-desk route opts in. A **separate** `charge_nonce` (distinct from `payment_nonce`) so a payment + a charge from
  the same rendered page never collide on the key.
- **The route wiring.** `ReservationDetail::render()` emits a fresh `charge_nonce = (string) Str::uuid()` per render; the
  add-charge form carries it as a hidden `idempotency_key` (alongside the Batch-4 `data-guard-submit`);
  `ReservationFolioActionController::addCharge` validates it `required|string|max:64` and passes
  `idempotencyKey: $validated['idempotency_key']`. A **fresh render = a fresh token = a new charge** — only a DUPLICATE of the
  SAME submission is deduped (no over-dedup).
- **Money guards STAY** (all pre-existing, unchanged): integer minor units (#17), append-only ledger (a new line, never an
  edit), correct-folio tenant-404, the `folios.manage` route gate, domain + audit events inside the service.
- **Mutation-proven** (`tests/Feature/OperatorReservationDetailViewTest.php`, +3 tests, `@group security`): a double-submit
  with the **same nonce → ONE** `folio_item` (+ folio `total_minor` reflects one €25, not two); two **different** nonces →
  **TWO** (the guard is keyed, no over-dedup); and the load-bearing proof — two `postCharge` with **no key → TWO** rows
  (guard-off doubles), the **same key → ONE** (the second returns the original `->id`). Proves the KEY, not some other
  invariant, is what dedupes.
- **Browser-proven** (operator, live server): the add-charge form renders the hidden `idempotency_key` nonce; **two CONCURRENT
  POSTs with the same nonce** (fired via `Promise.all`, bypassing the UI disable entirely) recorded the €17.50 charge **ONCE**
  server-side (verified in the DB: exactly 1 `folio_item`, keyed by the nonce). The injected test line was then removed (demo
  data left clean).
- **Files:** the migration; `app/Models/FolioItem.php` (`$fillable`); `app/Services/FolioService.php` (`postCharge`
  idempotency); `app/Http/Controllers/ReservationFolioActionController.php` (`addCharge` validate + pass);
  `app/Livewire/ReservationDetail.php` (`charge_nonce`); `resources/views/livewire/reservation-detail.blade.php` (hidden field);
  the 3 existing add-charge route tests updated for the now-required nonce + the 3 new mutation-proof tests. Closes
  **POLISH-SWEEP I3's flag**. Suite **981/981 green** (7792 assertions); PHPStan L5 (0); Pint clean; `migrate` clean.

### 2026-07-26 — POLISH P3 TAILS — the sweep is now FULLY CLEARED (P1 · P2 · P3); presentation + one client-side visual aid

The last four deferred P3 tails from `docs/POLISH-SWEEP.md`, cleared in two gates. **All presentation-only EXCEPT the
strength meter, which is a small CLIENT-side visual aid — the server-side password policy stays authoritative + UNCHANGED.
No money/auth/isolation LOGIC touched; invariants #1–48 intact; suite 981/981; PHPStan L5 (0); Pint clean throughout.**

**GATE 1 (`d5695ff`) — signup step cross-fade (I4) + guest-portal focus rings (M5).** Pure CSS in two blade views.
- **I4:** the signup wizard changed steps as a hard cut. Each step's content now **fades up, staggered**, on activation,
  matching the prototype's EXACT step progression — rendered the raw prototype (`docs/design/raw`, port :8899) and observed
  it: `vyFadeUp` 0.45s, staggered ~60ms. Implemented CSS-only via `[data-step].on > *` (the animation re-fires each activation
  as display flips none→block), keyed purely off the `.on` class the wizard JS already toggles — **the signup
  LOGIC/validation/provisioning is unchanged**; no-JS shows all steps un-animated; reduced-motion honoured. Browser-proven
  (step 0→1, step-2 content runs vyFadeUp 0.45s staggered 0/.06/.12/.18s).
- **M5:** the guest portal pages use **inline-styled** inputs (not `.vy-input`), so `.vy-input:focus` never reached them, and
  nav/buttons/links/textareas had no keyboard ring. Added a consistent **`:focus-visible`** ring (`2px solid var(--vy-accent)`,
  offset 2px — the app's EXACT idiom from operator/owner/auth) across the portal via **broad element selectors**
  (`.vy-wrap a/button/input/select/textarea`, `.vy-top a/button`) so the inline-styled inputs are covered. Keyboard-only (never
  a mouse-click halo). The prototype ships zero focus rules → this is an a11y/internal-consistency nicety, not a prototype-match.
  Browser-proven (tabbing the messages page: the ring shows on the header nav, the inline Subject input, the message textarea,
  the Send button, and links — `focus-visible:true`, `2px rgb(194,65,12)`).

**GATE 2 — set-password strength meter (D14) + D10 decision + consolidation.**
- **D14 — the strength meter (a VISUAL AID, NOT a gate).** `reset-password` already had the meter; it's now on **`set-password`
  AND the signup wizard** (onboarding step 2), so the whole auth family is uniform. Ported reset's EXACT treatment (`.vy-meter`
  4-bar + `.vy-strength` label + a length/case/digit heuristic). 🔒 **The meter is client-side guidance only — it does NOT
  change the server-side password validation.** Its floor ("At least 8 characters") reflects the **real** rule (`Password::min(8)`,
  uniform across every password path — signup/invite/reset/change/guest); the higher bars are advisory strength. **Proven the
  server still enforces:** only 2 blade views changed (no controller), and the 76 password-enforcement tests / 507 assertions
  (Onboard/Invite/PasswordReset/ChangePassword/Claim) stay green. Browser-proven the meter reacts to typing (8-lower→Weak,
  +case→Fair, +digit→Good, 13-char mix→Strong; below the 8-char floor → no fill) on both set-password (via a generated invite
  token, then cleaned up) and the signup wizard.
- **D10 — RE-ASSESSED against the rendered prototype → FINALLY DEFERRED by decision (NOT consolidated; no code change).** The
  only remaining hand-rolled chart, the owner **Dashboard** earnings chart, is the ONE **interactive** chart (`x-data`, a
  3M/6M/12M toggle, a per-bar `x-show` windowing expression) and it **faithfully matches the prototype** — whose dashboard chart
  also has the 3M/6M/12M toggle and shows a single highlighted-month callout + a "€9k goal" line, **not** a persistent € value
  under every bar. The shared `<x-charts.bars>` is a **pure-static** component (5 static consumers) that renders a **bold € value
  under EVERY bar**. Consolidating would need (a) injecting Alpine `x-data` + a per-bar `x-show` windowing semantic into the
  shared component (coupling it to Alpine + a rule its static consumers don't want) and (b) a value-label-suppression prop to
  avoid a **per-bar-label visual regression vs the prototype**. High coupling + real regression risk for low anti-drift benefit
  (the static month charts are already unified) → the "risky, un-verifiable swap" the task says to avoid. Kept bespoke, finally,
  by decision — recorded here and in POLISH-SWEEP D10.

**Files (all views):** `auth/onboarding.blade.php` (step cross-fade + meter), `auth/set-password.blade.php` (meter),
`components/guest/shell.blade.php` (focus rings). Docs: POLISH-SWEEP (I4/M5/D14 DONE, D10 finally-deferred, sweep FULLY
cleared), this entry, `design/design-system-brief.md` (the meter + focus conventions), `design/DIVERGENCE-REGISTER.md` (rows).
**The polish sweep is now FULLY CLEARED — every P1/P2/P3 finding is actioned or finally-decided.**

### 2026-07-26 — SUPER ADMIN · GATE 0 — the hardened, ISOLATED /admin platform realm (INVARIANT #49); the deferred Phase-7a platform-admin item RESOLVED PROPERLY

The founder's platform console, built foundation-first: **ONLY the realm + auth + security + the safe cross-tenant
read + the isolation proofs — NO features** (Dashboard/Customers/Plans/etc. come in later gates on this proven base).
**ADDITIVE ONLY — nothing existing disturbed: the whole prior suite stays green** (997 tests / 7844 assertions =
the existing **981 all still green** + **16 new Gate-0 isolation proofs**); PHPStan L5 (0); Pint clean;
`migrate:fresh --seed` clean (a seeded demo admin). **Invariants #1–49.**

**STEP 0 correction (honesty, not a guess).** The build brief assumed an `is_super_admin` flag on `users` to "move
off". **There is NO such column** (grep-proven; a test asserts `users` has neither `is_super_admin` nor `is_admin`).
The only `isSuperAdmin()` in the repo is the method on the `TenantContext` request singleton (the bypass state), not
a user flag. The REAL latent escalation surface is the one Phase 1 found: the seeded global spatie `Super Admin` role
(`team_id = null`, `['*']`), which is **structurally unassignable** (`model_has_roles` PK includes the non-null
`organization_id`) — fail-closed, zero assignments. **A separate realm SUPERSEDES it** (below); the dormant spatie
role is left untouched (touching it — RoleCatalogSeeder/HorizonServiceProvider — would disturb existing behaviour for
no security gain, since it already fails closed). Recorded, not fabricated-migrated.

**THE REALM (separate, above tenancy).** A NEW `platform_admins` table (ULID; NO `organization_id`; 2FA columns
mirroring `users`) + a NEW `PlatformAdmin` model (`Authenticatable`, **no `HasRoles`, no tenant relations**) + a NEW
`admin` session guard over a standard eloquent `platform_admins` provider (`config/auth.php`, additive — `web`/`guest`
byte-unchanged). A platform admin is **NOT a tenant `User`**, so — exactly like the `guest` realm vs `web` — the
tenant guards can never resolve it and it can never resolve a tenant user. **This is the proper resolution of the
deferred Phase-7a platform-admin item: platform admin ≠ org `User` ≠ the (unassignable) global spatie role.**

**THE SAFE CROSS-TENANT READ (the crux — the EXISTING closure, not a new bypass).** `App\Support\Platform\PlatformQuery::read()`
is the ONE cross-tenant path: it (1) is **admin-guard-gated** — throws unless `Auth::guard('admin')->check()`, so the
bypass can never fire from a tenant realm; (2) wraps the EXISTING, tested `TenantContext::runAsSuperAdmin()` (suspends
the `OrganizationScope`, runs, **restores context in a `finally`**); (3) **audits** every read. **Non-leak proven:**
`/admin` requests carry NO ambient tenant context (`SetTenantForWebRequest` no-ops for a non-`web`-`User`), so a stray
tenant query there FAILS CLOSED (throws) rather than leaking — and a test proves that after an admin read, the scope is
restored (a subsequent context-less tenant query still throws). Gate 0 exercises it once (the Overview org count) — not
a feature.

**HARDENING.** MANDATORY 2FA (`EnsurePlatformAdminTwoFactor` — an un-enrolled admin is trapped at setup, reaches
nothing else; TOTP via the same google2fa/bacon primitives, a self-contained `PlatformTwoFactorService` so the tenant
2FA path is byte-untouched) · admin login rate-limited + lockout on a dedicated `admin|` throttle namespace, generic
no-enumeration errors, session-regenerate on every auth transition · the admin's OWN actions AUDITED to a separate
immutable `platform_admin_actions` ledger (kept OUT of the tenant `audit_events` so nothing there is disturbed) ·
**noindex** (an `X-Robots-Tag` middleware on the whole group + `<meta robots>` in the shell + `Disallow: /admin` in
robots.txt; on a PATH not a subdomain → main-domain SEO untouched) · **NO public sign-up** — the first admin is seeded
ONLY via `php artisan admin:create` (hidden password prompt, `Password::min(12)->mixedCase()->numbers()`); a demo admin
(`admin@vyrobel-demo.test` / `password`, no 2FA → forced to enrol) for `--seed`.

**THE DESIGN (built well from the start).** A cohesive "control-room" admin design system — deep-slate + indigo,
deliberately DISTINCT from the warm tenant glass but as polished — shared `<x-admin.auth>` + `<x-admin.shell>`
components (login/challenge/setup/recovery + the console shell), ready for the feature gates. Gate 0 ships only the
shell + an honest empty Overview (feature areas shown as "Soon", never faked).

**🔒 ISOLATION PROOFS (mutation-proven, `tests/Feature/Admin/PlatformAdminGate0Test.php`, 16 tests):** a tenant
operator/owner/guest is REFUSED `/admin` (bounced to the admin login) and cannot authenticate into the admin guard;
a platform admin is refused the tenant realms (defense-in-depth: even presented to `/app`, `EnsureBackOfficeAccess`
403s a non-`User`); the bypass is admin-guard-gated + restores scope (no leak); mandatory 2FA enforced (no-2FA → setup,
correct-password-for-an-enrolled-admin does NOT log in but challenges); no public sign-up route; admin actions audited;
noindex header + robots. **Browser-verified end-to-end:** login → forced 2FA enrolment → the console (the audited
cross-tenant org count) → sign-out; an authenticated operator hitting `/admin` lands on the admin login, never the
console.

**Files (all NEW except the additive edits):** migrations `2026_08_02_000000_create_platform_admins_table` +
`…_000100_create_platform_admin_actions_table`; models `PlatformAdmin` + `PlatformAdminAction` (+ factory);
`App\Support\Platform\{PlatformQuery,PlatformAuditLogger}`; `App\Support\TwoFactor\PlatformTwoFactorService`;
middleware `NoIndex` + `EnsurePlatformAdminTwoFactor` + `RequirePlatformAdminTwoFactorPending`; controllers under
`App\Http\Controllers\Admin\{Auth\AdminLoginController,Auth\AdminTwoFactorChallengeController,Auth\AdminTwoFactorController,DashboardController}`
+ `App\Http\Requests\Admin\AdminLoginRequest`; views under `resources/views/admin/**` + `components/admin/{auth,shell}`;
`App\Console\Commands\CreatePlatformAdminCommand`; `Database\Seeders\PlatformAdminSeeder`. **Additive edits (existing
byte-preserved otherwise):** `config/auth.php` (+`admin` guard/provider), `routes/web.php` (+`/admin` group + imports),
`bootstrap/app.php` (+`admin/*` redirect arm), `public/robots.txt` (+disallow), `database/seeders/DatabaseSeeder.php`
(+seeder call). **NEXT: the feature gates (Dashboard · Customers · Plans · Security & activity · Integrations) on this
proven, isolated foundation.**

### 2026-07-26 — SUPER ADMIN · BUILD 1 — Dashboard + Customers + Plans (the core console) on the proven Gate-0 foundation

The founder's operating console, three gated areas on the isolated /admin realm. **ADDITIVE ONLY — the existing 997
tests (981 original + 16 Gate-0) all stay green** (suite now **1013/1013** = 997 + 16 new Build-1 tests); PHPStan L5
(0); Pint clean; `migrate:fresh --seed` clean. **🔒 EVERY cross-tenant read via the EXISTING audited
`PlatformQuery::read()` (runAsSuperAdmin — scope-restored, admin-guard-gated, audited); every tenant-scoped WRITE via
the new sibling `PlatformQuery::mutate()` (same envelope). No new bypass; no tenant read/write outside the closure.**
Real data only (#39); integer money (#17). Invariants #1–49 intact.

**GATE 1 — Dashboard (`0aa7f9d`).** Real cross-org metrics computed inside ONE `PlatformQuery::read('platform.dashboard')`
per load: total customers + by-status (active/trialing/suspended/cancelled), active subscriptions + on-trial, properties
+ reservations platform-wide, plan distribution, a 6-month signups series, and a recurring PLAN VALUE (Σ active/trialing
subs' plan prices, yearly ÷12) — labelled honestly as *contracted, not-yet-billed* (live charging is account-gated).
`PlatformMetrics` service. Honest zero/empty states. Control-room KPI band + a real signups chart + status/plan
breakdown (new admin design tokens). Browser-verified (demo org: 1 customer, €49.00/mo, 2 properties, 24 reservations).

**GATE 2 — Customers (`ec7db1c`).** See + inspect every org: `PlatformCustomers` reader → the list (all orgs + plan +
status + property/staff counts, searchable + status-filterable, no N+1) and a detail (facts + subscription/plan +
footprint). **🔒 #28-judicious** — operational facts ONLY, never end-guests' contact PII. `CustomerController` reads via
`read`; **suspend + reactivate via `mutate`** (audited, real `organizations.status` change, rate-limited, confirm
dialog). **🔒 Impersonation / view-as-tenant is DELIBERATELY NOT built** (high-risk — deferred; a test asserts no such
route). **Suspend is a real audited status marker; ENFORCING it (locking a suspended org out across realms) touches
tenant auth middleware and is a documented follow-up, not done here.** Browser-verified (real demo customer + detail).

**GATE 3 — Plans (this commit).** The `plans` + `plan_features` tables (Phase 7b — a GLOBAL first-party catalog) become
ADMIN-AUTHORITATIVE:
- **Plan CRUD** (`PlanController`): edit name, price (integer minor, #17), active, and the feature toggles/limits
  (analytics/channels/team). Global tables → no bypass needed; every write AUDITED. **🔒 Stripe billing sync DEFERRED
  (Q4)** — editing sets price + features (display + gating); it does NOT create/update a Stripe Price/subscription; the
  `stripe_price_id` column is the untouched seam.
- **Feature-gating (the key SaaS mechanism, wired ADDITIVELY).** The existing `Organization::planAllows()` (reads
  `plan_features`, FAILS OPEN on no-subscription/unknown-key) is the gate. New reusable `EnsurePlanFeature` route
  middleware (`plan.feature:channels`) + a clean `billing.feature-locked` "not in your plan" page — **fail-open by
  construction so applying it NEVER changes behaviour for an org whose plan grants (or doesn't mention) the feature;
  only a plan that EXPLICITLY excludes it gates.** No new tenant-route gate was applied (the existing Pro-only channels
  gate in the operator shell is the live example) — the middleware is the proven, ready tool. **Mutation-proven:**
  editing a plan's `channels` feature flips `planAllows('channels')` for orgs on that plan; `EnsurePlanFeature` passes a
  granted feature / 403s an excluded one / fails open with no context.
- **Assign a plan to a customer** (`CustomerController::assignPlan`, from the customer detail): sets the org's
  subscription `plan_id` (via `mutate` — Subscription is tenant-scoped), which DRIVES its features. Audited. **🔒 Stripe
  untouched** (`stripe_subscription_id` stays null). **Browser-proven end-to-end:** assigning the demo org Essential→Pro
  flipped `planAllows('channels')` false→TRUE.
- **config→DB (the careful edit).** `BillingService::catalog()` now reads feature `enabled`/`limit` from the DB
  `plan_features` (admin-authoritative), label from config (display copy) — byte-identical today (DB seeded from config,
  so the Paywall tests stay green), but an admin feature edit now flows to the Paywall, not just the gate. config remains
  the SEED + the marketing blurb source (the full DB-drives-marketing move is the later marketing-site work).

**Deferred (noted honestly):** Stripe billing sync (plan Prices / subscription lifecycle) — the go-live step;
impersonation; suspension access-enforcement across realms; per-feature LIMIT enforcement (creation-blocking) — the
limits are shown + editable, not yet enforced. **Build 2 = Security & activity log + Integrations.** Files: new
`PlatformMetrics`/`PlatformCustomers`, `PlatformQuery::mutate()`, `DashboardController` (expanded), `CustomerController`,
`PlanController`, `EnsurePlanFeature`, `admin/{dashboard,customers,plans}` + `billing/feature-locked` views, the admin
shell tokens, `tests/Support/admin_helpers.php`, 3 test files (16 tests); additive edits: `routes/web.php`,
`BillingService::catalog()`, the admin shell nav (Customers/Plans live).

### 2026-07-26 — SUPER ADMIN · BUILD 2 — Security & Activity Log + Integrations — THE PANEL IS COMPLETE

The final two areas, completing the founder's platform console. **ADDITIVE ONLY — the existing 1013 tests all stay
green** (suite now **1029/1029** = 1013 + 16 new Build-2 tests); PHPStan L5 (0); Pint clean; `migrate:fresh --seed`
clean. **🔒 Every cross-tenant read via the audited `PlatformQuery::read()`; every admin action audited.** Invariants
#1–52.

**GATE 1 — Security & Activity Log (`21eeaf6`), privacy-built-in.** Auth events are not domain events, so login
capture is done by ADDING listeners on the FRAMEWORK auth events — **the tenant login/auth flow is NOT modified**:
- `login_activity` table + `LoginActivity` model + `RecordAuthActivity` listener registered on `Login` / `Failed` /
  `Lockout` (AppServiceProvider, additive). Captures successful logins (all realms), failed attempts, and lockouts
  (the repeated-failure probing signal). **The write can NEVER break auth** (swallowed on failure — the "a mail failure
  never rolls back the txn" discipline). Operator/owner login uses `Auth::validate` (fires no per-attempt `Failed`), so
  their signal is the `Lockout`; guest uses `attempt` (fires `Failed`); the admin realm additionally dispatches `Failed`
  from its OWN request (own code) to capture admin failures granularly.
- **🔒 PRIVACY BUILT IN FROM THE FIRST COMMIT (GDPR):** a 90-day RETENTION prune (`platform:prune-activity`, scheduled
  daily) purges rows — the IP and all; purpose = SECURITY MONITORING (documented, legitimate-interest); security-events-
  only, NOT ambient tracking; access = the /admin realm only. **🔒 #28** — a guest's encrypted email is NEVER persisted
  plaintext (identifier null for the guest realm; captured by id); passwords never stored.
- `PlatformActivity` reader + `ActivityController` + view: recent sign-in activity (login/failed/lockout + IP + realm),
  a 24h failure summary, and a cross-tenant account-events slice (from the tenant `audit_events` ledger via
  `PlatformQuery::read` — audited). Per-customer audit slice added to the customer detail. The privacy posture is
  surfaced in the UI. Browser-verified (real admin login + real account events + the privacy note); existing auth suite
  green (listeners don't regress the flow).

**GATE 2 — Integrations (this commit).** The founder's MASTER platform credentials for the booking channels
(Channex/OTAs):
- `platform_integrations` table + model with `credentials` as **`encrypted:array`** — the SAME at-rest encryption as the
  owner IBAN / guest PII (#28); the ciphertext is what lands on disk (mutation-proven: the raw DB column does NOT contain
  the plaintext). A fixed channel catalog (Channex / Booking.com / Airbnb / Expedia).
- `IntegrationController`: index (catalog + status) · edit/update (add/ROTATE the credential — **write-only / masked;
  the stored secret is NEVER echoed back**, proven; a blank field keeps the existing value) · a **test-connection STUB**
  (honest — "a live test runs once the {channel} account is connected", account-gated). Managing is an AUDITED admin
  action; **the audit records WHICH channel, NEVER the value** (#28 discipline).
- **🔒 The real channel SYNC is account-gated** (needs the real Channex/OTA accounts) — this ships the page + encrypted
  store + connection model + the stub; the sync is deferred honestly (labelled). **The customer-self-connect model**
  (a tenant later connecting THEIR own channel account on top) is DOCUMENTED in the UI + here, built later.

**NEW INVARIANTS: #51** — login/auth SECURITY SIGNALS are captured by ADDED listeners (the tenant auth flow is never
modified; the capture never breaks auth) with privacy built in (90-day retention, security purpose, /admin-only, no
guest PII). **#52** — platform integration credentials are ENCRYPTED at rest, admin-managed, and NEVER echoed after
saving. **THE SUPER ADMIN PANEL IS COMPLETE** (Gate 0 + Build 1 + Build 2). **Deferred tails (all account-gated or
documented follow-ups):** Stripe billing sync · impersonation · suspension access-enforcement · per-feature-limit
enforcement · the real channel sync · customer-self-connect. Files: `login_activity` + `platform_integrations`
migrations/models, `RecordAuthActivity`, `PrunePlatformActivity`, `PlatformActivity`, `ActivityController` +
`IntegrationController`, `admin/{activity,integrations}` views, 2 test files (16 tests); additive edits:
`AppServiceProvider` (listeners), `routes/console.php` (prune schedule), `routes/web.php`, `AdminLoginRequest` (dispatch
Failed), the customer detail (activity slice), the admin shell nav (all 5 areas live).

### 2026-07-27 — SUPER ADMIN PANEL — deep end-to-end audit + fixes (`docs/SUPERADMIN-AUDIT.md`)

A full Playwright walk of every /admin area/page/form/CRUD/action, audited as hard on security/isolation as on UI.
**Result: ZERO CRITICAL + ZERO P1** — the panel is production-ready on security; every high-stakes property was
re-verified LIVE (not just by tests): realm isolation, **no cross-tenant leak** (a context-less tenant query throws
after an admin read — scope restored), every admin action audited, **no credential plaintext leak** (encrypted at
rest, masked, audit records channel-only), mandatory 2FA, no login enumeration, #28 (no guest PII on admin surfaces),
money #17. **Genuine findings were all UX/polish/honesty**, fixed additively (Phase B):
- **P2-1 pagination** — `PlatformCustomers::list()` now returns a paginator (25/page, `withQueryString()`); a
  control-room pager; test (30 orgs → 2 pages, filter carried) + browser proven.
- **P2-2 suspension honesty** — the suspend confirm + a detail note now state suspension is a status FLAG that does
  NOT yet block access (enforcement stays a documented follow-up — NOT built; the honest-presentation fix).
- **P2-3 plans-catalog honesty** — an "About the catalog" note (tiers fixed + editable; a new tier is a follow-up
  tied to the marketing pricing page).
- **P3** — the shared double-submit guard on the admin state-changing forms (reused `partials/submit-guard`); the
  previously-dead `plans.toggle` route wired to an Activate/Deactivate button (audited); an admin favicon.
- **P3 accepted (documented, no change):** `reactivate()`→active is the correct target (billing state derives from
  the subscription); admin recovery-code regeneration is out-of-scope polish (recover via `admin:create`/DB).

**Honest deferrals CONFIRMED correct (not findings, not touched):** Stripe billing sync, impersonation (no route),
suspension access-enforcement, per-feature-limit enforcement, real channel sync (stub), customer-self-connect. Every
fix stayed additive + preserved the security model (audited `PlatformQuery`, no new bypass) + disturbed nothing in
the tenant realms. Suite green (existing + new fix tests, admin 50/50); PHPStan L5 (0); Pint clean; `migrate:fresh
--seed` clean. **The Super Admin Panel is audited, fixed, and production-ready.**

### 2026-07-27 — SUPER ADMIN · PROFILE & ACCOUNT page — the admin manages their own identity (INVARIANT #53)

Every platform admin can now manage their OWN account (a real gap — there was no such page): profile (name),
login email (re-verified), password, and 2FA recovery codes. On the proven /admin realm. **ADDITIVE ONLY —
existing 1031 tests stay green (suite now 1040/1040 = 1031 + 9 new); PHPStan L5 (0); Pint clean; `migrate:fresh
--seed` clean.** A SEPARATE, parallel implementation for the admin realm — it MIRRORS the owner account-rebuild
DISCIPLINE, not its wiring (does not reuse the tenant routes/controllers).

**🔒 The highest-privilege identity, so the discipline is mirrored, never weakened:**
- **Self-only** — every action resolves `Auth::guard('admin')->user()`, NEVER a request id.
- **Password change** requires the CURRENT password (`current_password:admin` — the anti-hijack guard, mirroring
  `ChangePasswordController`) + the real server-authoritative rule (`Password::min(8)`, confirmed) + the single
  hash+rotate path (`PlatformAdmin::changePasswordTo`); this session stays signed in. Mutation-proven: the new
  password works, the OLD no longer does; a wrong current password / too-short new password is rejected.
- **Email change is RE-VERIFIED** (mirrors `users.pending_email`): the new email is parked in the NEW
  `platform_admins.pending_email` column and only switched when a SIGNED, expiring link (`temporarySignedRoute` +
  `sha1(pending_email)` binding) opened from the NEW inbox confirms control (`AdminEmailChangeMail`). Requires the
  CURRENT password to request (stronger than the owner flow). **Mutation-proven: the OLD email stays the login
  and the PENDING email CANNOT log in until verified; fail-closed on a bad signature / stale hash / an
  address taken meanwhile.** The verify route is public+signed (may be opened where the admin isn't signed in).
- **2FA recovery** — status + regenerate one-time recovery codes (current-password required; audited) — closes
  the audit's out-of-scope-polish item (`PlatformTwoFactorService::regenerateRecoveryCodes` added).
- **Every change AUDITED** to `platform_admin_actions` (which field changed, never the value).

**NEW INVARIANT #53** — a platform admin's account management is self-only, re-auths sensitive changes
(current-password for password/email/recovery), re-verifies email (signed link, pending-until-confirmed, old
stays login), and audits everything — mirroring the tenant discipline, never weaker. Browser-verified
end-to-end. Files: the `pending_email` migration, `PlatformAdmin` (+fillable/property), `AccountController`,
`AdminEmailChangeMail` + email view, `admin/account/index` view, `PlatformTwoFactorService::regenerateRecoveryCodes`,
the account routes (+ the signed verify route), the shell user-chip link, `PlatformAccountTest` (9). **Invariants
#1–53.**

### 2026-07-27 — SUPER ADMIN PANEL — completeness review (`docs/SUPERADMIN-COMPLETENESS.md`), no code

A read-only review — not "does what's built work?" (that audit is done: 0 critical / 0 P1) but "what does a platform
admin running this SaaS genuinely NEED that isn't there yet?" — producing a prioritized report for the founder to
choose from. **No code changed** (`4605826`, report only). Findings, in short:

- **The panel is operationally sufficient to LAUNCH a free-trial / early-access product as-is.** There is no
  launch-blocking hole in the panel itself. A paid launch depends only on the already-scoped **Stripe billing
  go-live** + **suspension enforcement** (a suspend today is a status flag, not an access block).
- **Genuinely-missing (all after-launch, none blocking), ranked:** suspension enforcement · Stripe billing sync +
  revenue/invoices/refunds · **queue/failed-jobs visibility** (the founder currently CANNOT reach Horizon in
  production — its gate is bound to the unassignable global `Super Admin` role; re-point it to the `admin` guard,
  small + high-value) · multi-admin/team + roles · GDPR data request (export/erase a customer) · manual customer
  create + trial adjust/cancel · admin alerting · impersonation (read-only, heavy-audit) · usage analytics ·
  audit-log search/export. Plus nice-to-haves (admin session mgmt · feature flags/maintenance mode · DB-editable
  settings · CSV export · customer comms).
- **Already-deferred, confirmed CORRECT (not re-flagged):** Stripe billing sync · impersonation · suspension
  enforcement · per-feature-limit enforcement · real channel sync · customer-self-connect — restated with their real
  priority so they can be sequenced.
- **Roadmap:** Phase 0 launch (free-trial now; paid = Stripe go-live + suspension enforcement) → Phase 1 first-paying
  (the billing/revenue cluster · re-point the Horizon gate · support ergonomics) → Phase 2 scale (multi-admin ·
  alerting · GDPR · impersonation) → Phase 3 polish.

The completeness lens confirmed the existing deferrals are honest and correctly presented — no honesty finding. This
CLOSES the Super Admin panel work: **Gate 0 → Build 1 → Build 2 → deep audit → account page → completeness review.**

### 2026-07-27 — TOTAL-RECALL DOCUMENTATION CONSOLIDATION (docs only)

A documentation-consolidation pass so a fresh session inherits the FULL context. **No feature code — git shows only
docs.** Verified every claim against the repo: **suite re-run green 1040/1040 (8046 assertions, exit 0)**; PHPStan L5 =
0; Pint clean; `migrate:fresh --seed` clean; the demo/admin creds confirmed against the seeders; the Super Admin realm
surface confirmed against the routes/controllers/views/models. Changes:

- **`docs/PROJECT-STATE.md`** — prepended an authoritative 2026-07-27 CURRENT-STATE block (what Vyrobel is · the full
  phase-by-phase build status incl. the complete Super Admin panel + the completeness review · the verified quality
  state · the honest deferrals with real priority · what's left + the launch roadmap · the resume creds) that
  supersedes every earlier block; and **extended the canonical numbered Invariants list from #41 to #53** (it had
  lagged the prose — #42 owner sub-tenancy · #43 team schedule · #44 production email · #45 live Stripe · #46 owner
  account area · #47 analytics depth · #48 operator money-write idempotency · #49 the isolated admin realm · #50
  DB-backed plans/gating · #51 auth-signal capture + privacy · #52 encrypted platform credentials · #53 admin account
  discipline), retitling the section "#1–53".
- **`docs/RESUME.md`** — NEW fresh-session orientation index: read-order, one-paragraph state, stack, run/demo, the
  login table, the next action (launch decision + deployment), and the working discipline. This is the "START HERE".
- **`docs/DECISIONS.md`** — this entry + the completeness-review entry above.

Confirmed: a fresh session reading `docs/RESUME.md` → `docs/PROJECT-STATE.md` now inherits the complete context —
what Vyrobel is, everything built (Phases 0–8 + the Super Admin panel), the current quality state, the honest
deferrals, what's left, the creds, and the exact next action. **Invariants #1–53.**

### 2026-07-27 — OWNER OVERVIEW — exact wireframe match + real backends (INVARIANT #54); two gates

The owner Overview (`/owner`, `App\Livewire\Owner\OwnerDashboard`) rebuilt to the decoded
`docs/design/raw/Owner Dashboard.html` wireframe EXACTLY **and** wired so every section is operational with REAL
owner-scoped data — no stubs, no fabrication. Read-only Phase 1 audit (`docs/OWNER-OVERVIEW-AUDIT.md`) → two gated
builds. Suite **1040 → 1042** (2 new `OwnerUpcomingStaysTest`; `OwnerDashboardViewTest` updated in place);
PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean. The rest of the app + the tenant system untouched.

**PHASE 0 — decoded the wireframe** (a `dc-runtime` bundle, not plain HTML): `JSON.parse` the
`<script type="__bundler/template">` → the real `<x-dc>` markup (inline-style design tokens) + the `{{ }}` data
placeholders; the manifest's gzip/base64 assets = the Inter/Sora fonts + runtime. Confirmed the palette
(warm-glass, `#C2410C`, dark rail). Rendered live at `:8899` for the side-by-side.

**GATE 1 — exact design match (presentation; `d80abdf`).** Shared owner chrome (all owner pages inherit — the "one
system" outcome the founder chose): the peach→grey diagonal background (`--vy-bg`) + the wireframe's ambient color
orbs + light streaks (reduced-motion honoured); the frosted sidebar exact-match (200px, no panel bg/border,
**terracotta-active item** `#C2410C` — was `--vy-ink`, the notable fix); the greeting `<h1>` (30px, opt-in
`.is-greeting`), a 42px frosted bell, and a real **account-menu dropdown** (Profile / Security / Sign out —
sign-out preserved) replacing the static chip + logout icon. **Decision D2:** the 5 extra REAL nav items
(Bookings / Add a unit / Payout method / Documents / Notifications) the wireframe omits are **KEPT** — the shell is
shared, so removing them would strand those pages on every owner screen; the visual treatment matches, the
functionality doesn't regress. Overview body via scoped `.vy-ov-*` (the shared `.vy-card` / `--dark` /
`.vy-hero-figure` / `.vy-track` already match the wireframe glass surfaces). Suite 1040/1040.

**GATE 2 — real backends (operational).** Every section wired to REAL owner-scoped data through the SHARED owner
derivations (anti-drift — the Overview cannot drift from My Units / Bookings):
- **Occupancy KPI + per-unit bars + MoM delta + trend sparkline** — `OwnerOccupancy::forMonth` (owner-unit-scoped,
  keyed by physical unit id; the old "room-type-wide" deferral is superseded). The demo shows a REAL low occupancy
  (3% / A1 6% / A2 0%), never a fabricated 78% (#39).
- **Attention feed** — extracted `OwnerMyUnits::attentionItems()` into a shared `App\Support\Owner\OwnerAttention`
  read by BOTH surfaces: real tax-hold / missing-payout-method / agreement-renewal chips; empty → honest "caught
  up". The fabricated "rate-change proposal" chip stays REFUSED (#39).
- **Upcoming stays** — new shared `App\Support\Owner\OwnerUpcomingStays`: the owner's REAL next-30-days arrivals,
  **guest NAME only (#28** — never email/phone; **Decision D1**, within the existing owner Bookings perimeter, which
  reversed the dashboard's old "never shown" copy + updated its test), owner **NET** via the shared
  `OwnerLedgerAttribution` (reconciles; a future unattributed stay shows "Not yet attributed", never a fake €0),
  real source/status. The Source/Status **filters + sort are CLIENT-SIDE (Alpine)** over the server-rendered rows —
  a Livewire round-trip would bypass `SetOwnerScopeForPortal` and leak (the discipline OwnerBookings documents).
- **Next-payout rail** — real masked **IBAN** (`Iban::masked`, shown only to a payout-capable role — CAP_PAYOUT) +
  the **tax-hold paused-state** (a pending payout AND an owner with no tax details — the ONLY real "paused"). No
  estimated date, no "Scheduled/On hold" state (no backing field — #39).
- **Greeting** (real time-aware + owner first name) + the four quick actions wired to real routes (calendar /
  messages / statement-PDF / help, capability-gated so the UI never invites a 403).

**Mutation-/render-proven:** `OwnerUpcomingStaysTest` — the 30-day clip (a beyond-30d + a past booking excluded),
guest **name-only** (email/phone never in the feed), the owner NET reconciles to the cent (€170.00), real
source/status counts, and fail-closed on an empty/foreign unit set. `OwnerDashboardViewTest` — the render shows the
guest NAME but NOT email/phone (#28 at the render boundary), a real attention chip, occupancy no longer stubbed, no
fabricated goal/estimated-date, `assertNoRawBlade` (the `word@directive` trap — hit once during the build, on
`attention@else`, and fixed). Browser-verified end-to-end (real data in every section; the filter dropdown narrows
by source/status with real counts; the paused rail; the account dropdown).

**NEW INVARIANT #54** — the owner Overview matches the wireframe exactly AND is fully operational on REAL
owner-scoped aggregates (occupancy / attention / upcoming stays / payout), through the SHARED owner derivations
(`OwnerOccupancy` / `OwnerAttention` / `OwnerUpcomingStays` / `OwnerLedgerAttribution` — anti-drift), guest
NAME-only (#28), integer money (#17), honest empty states, nothing fabricated (#39); its interactive filters are
client-side (never a Livewire round-trip that would bypass the owner scope). Files: `OwnerAttention` +
`OwnerUpcomingStays` (new shared), `OwnerDashboard` (occupancy/attention/upcoming/payout/greeting), `OwnerMyUnits`
(delegates attention), `dashboard.blade.php` (full rebuild), `layouts/owner` + `owner/shell` (chrome), the 2 test
files. Docs: `OWNER-OVERVIEW-AUDIT.md` (gaps → fixed), this entry, `design/DIVERGENCE-REGISTER.md` (the owner
Overview row → RESTRUCTURED-exact + operational), `PROJECT-STATE.md`. **Invariants #1–54.**

---

## 2026-07-28 — Owner **My Units** + **Add Unit** rebuilt wireframe-exact + fully operational (real backends)

Two owner surfaces rebuilt to their prototypes (`Owner My Units.html` / `Owner Add Unit.html`) EXACTLY and wired to
REAL, owner-scoped backends — gated per page. Everything on-screen is real (#39); money is integer minor (#17); the
owner-scope boundary holds (every write a controller POST, never `/livewire/update`, #20); guest data is name-only /
counts-only (#28). Suite **1050/8149** green; PHPStan L5 (0) + Pint clean; browser-proven end-to-end.

**My Units (`/owner/units`) — GATE 1 (design) + GATE 2 (real actions):**
- **GATE 1 (`0be1363`)** — a wireframe-exact rebuild on the shared owner chrome, scoped `.vy-mu-*` (3-tile stats · a
  collapsible action-center · gradient unit cards with occupancy bars + Active/star-Best badges · the calendar heat-grid ·
  the dark best-performer panel).
- **GATE 2 (`33e2475`)** — real card/calendar actions, each on a gated controller route: **Edit pricing** to the
  certified `OwnerRateService`/`RateAndRestrictionService` (per-ROOM-TYPE, **exclusive-owned only** — a unit sharing
  its type with foreign inventory is honestly non-editable, no fake save; browser-proven 180->185 persisted to
  `rate_calendar`); **Message guest** to the real owner-operator relay (`owner.messages.start`), unit-scoped, **no guest
  name** (#28); **Block dates** to `OwnerBlockService`; **Export** to a new `owner.units.export` month-occupancy CSV
  (counts-only #28, gated `exports`). Client-side Alpine for the collapse/heat-grid/day-count. Deferred honestly (not
  faked): the action-center snooze/dismiss store + a richer day-detail — both already function as real links / real
  counts. `OwnerMyUnits::editableRates()` + `occDelta()`; `OwnerUnitsExportController`; `OwnerMyUnitsViewTest` +2.

**Add Unit (`/owner/add-unit`) — GATE 3 (wizard + rich real request) + GATE 4 (prove it):** *supersedes the old
INTENTIONAL-DIVERGENCE (a 3-field request form).* The page is now the wireframe's **4-step wizard** (The basics ·
Space & capacity · Photos · Rate & terms) + a sticky **Live preview** + a **Done** state, exactly (scoped `.vy-au-*`),
fully operational on a **rich, real, pending `UnitRequest`**:
- **Schema** (additive migration) — `location` · `street_address` · `bedrooms`/`bathrooms`/`sleeps` · a **PROPOSED**
  `requested_rate_minor`/`cleaning_fee_minor` + `min_nights` + `currency` (#17). Amenities attach through the shared
  **`amenitiables`** pivot (reuse `HasAmenities`); photos through the shared **`media`** collection (reuse
  `HasPhotos`). `UnitRequest` gained a morph alias (`unit_request`) — it is now a morph parent + audit subject.
- **Write** — ONE controller POST (`OwnerUnitRequestController`, never `/livewire/update`, #20). Server-authoritative:
  kind in the real unit enum, amenity ids `exists` in the catalog (a fabricated id is rejected), money via `MoneyInput`
  (a present-but-unparseable amount is an error, not a silent drop), photos content-validated (image mimes, capped),
  the STORED filename a generated **ULID** (never the client's, so no traversal), on the `public` disk. First photo =
  cover. PRG to the Done state (read back **owner-scoped** from the DB, so it only ever confirms the owner's OWN request).
- **Honesty (#39), resolved not faked** — the "Live preview" reflects the owner's **own entered values** badged
  **"Pending review"** (a proposed request, never a live/priced unit); the dark panel shows the owner's **REAL**
  commission % from their active agreement (no agreement -> an honest "your team confirms it" line, never a fabricated
  %) and a live-computed net/night. The amenity chips render the **real global catalog** (extended with a common real
  vocabulary — legitimate shared *reference* data, not fabricated business data — so the step is usable). **Location is
  an honest text field** (no real town vocabulary exists, so chips would be KIND-2 fabrication). A request creates
  **NO** unit — the operator reviews it and creates the real unit as their own action (the existing policy); the
  operator `unit-requests` screen now **surfaces the captured rich detail** (read-only summary: location · capacity ·
  proposed rate · amenity/photo counts) so the reviewer can actually see what was submitted.
- **GATE 4 — proven end-to-end (browser):** the demo owner drove all four steps (name/type/town, capacity + real
  amenities, a real photo upload mirrored into the live preview, a 220 rate with the **real 18% commission -> "you keep
  ~180"**) -> Submit -> the Done confirmation. Verified the real pending `UnitRequest` in the DB (Casa Verde Retreat ·
  villa · Hvar · 3bd · 220/60 minor · WiFi+Pool via the pivot · a photo on disk with a ULID name) and its appearance on
  the operator `unit-requests` screen (owner · name · Villa · Pending · the rich detail).
- Tests: `OwnerUnitRequestTest` grew from 6 to 12 (rich submission + amenity pivot + photo `media` + the ULID filename ·
  MoneyInput reject · fabricated-amenity reject · non-image reject · the wizard renders on real data with the real
  commission + real catalog · the honest no-agreement line · the operator sees the rich detail).

**NEW INVARIANT #55** — the owner **My Units** and **Add Unit** pages match their wireframes exactly AND are fully
operational on REAL owner-scoped data through the SHARED services/pivots (`OwnerRateService` · `OwnerBlockService` ·
`OwnerOccupancy`/`OwnerUnitAnalytics` · `amenitiables`/`HasAmenities` · `media`/`HasPhotos` · `MoneyInput`), with
integer money (#17), guest name-/counts-only (#28), every write a controller POST (never `/livewire/update`, #20), and
nothing fabricated (#39 — a proposed request is badged "Pending review", never a live unit; commission is the owner's
real agreement rate or an honest line; amenities are the real catalog; location is an honest text field). An owner
Add-Unit request creates NO inventory — it is a rich intake the operator reviews (and sees in full) before creating the
real unit. **Invariants #1-55.**

---

## 2026-07-28 — Owner **My Units** calendar-heat fix · Owner **Bookings** PHASE-1 audit · total-recall consolidation (docs)

Three closely-related owner items, then a docs-only total-recall pass. **The consolidation commit is scoped to docs by
explicit path** — an unrelated Owner Bookings GATE-1 build was in progress in a parallel session at the time, and its
in-flight feature code (new controllers/model/migration/reader + modified `routes/web.php`/`FormatsOwnerData`/
`MoneyFormatter`) was left UNTOUCHED (never `git add -A`).

- **Owner My Units — calendar heat recolor (`4a3d538`).** The `/owner/units` occupancy heat-grid was recolored to the
  wireframe's SOLID terracotta scale (`#F2D7C4` → `#C2410C`), replacing a translucent dusty-salmon wash; shared
  `$heatBg`/`$heatFg` closures in `units.blade.php` keep the grid + legend swatches from drifting. Browser-verified
  (Sep 2026 shows the full ramp — free days a warm pale peach, a single-booked day mid-salmon with dark numerals).
  Presentation-only; suite unchanged (**1050/8149**).
- **Owner Bookings — PHASE-1 audit (`docs/OWNER-BOOKINGS-AUDIT.md`, read-only; awaiting GATE-1 approval).** Decoded both
  `dc-runtime` wireframes (the Bookings app page + the Booking-Summary PDF) and audited the two LIVE surfaces — the page
  `App\Livewire\Owner\OwnerBookings` and the PDF `OwnerBookingSummaryDocument` (route `owner.statements.bookings`,
  `resources/views/pdfs/owner-booking-summary.blade.php`) — which ALREADY run on real owner-scoped data; the founder's
  bar is an EXACT-match upgrade with "prior deferrals in scope". Decisive data-availability finding: every wireframe
  figure has an honest owner-scoped source, so the two items once refused as fabrication become REAL — the **"Bookings
  over time" chart** (real per-check-in-month `reservations.status` counts + net via `OwnerLedgerAttribution` + a **goal
  line backed by a NEW owner-set `owner_monthly_target`** — a value the OWNER supplies, never invented) and the **PDF
  occupancy / per-channel commission / guest-name-only column** (the old PDF-docblock refusals are STALE —
  `OwnerOccupancy`/`PerformanceMetrics` back occupancy, `owner_ledger.ota_cost` backs commission, guest name-only is an
  established owner-surface precedent, #28). The ONE standing refusal: the OTA **brand** split (Booking.com vs Airbnb —
  unmodelled; render the real `source` enum). Build plan in §4 (GATE 1 = the page; GATE 2 = the PDF); honesty unchanged
  (#39 / #28 / #17 / #20; the PDF stays CAP_EXPORTS-gated).
- **Total-recall consolidation (docs only).** Re-verified against the repo THIS pass: `php artisan test` re-run
  **1050/8149 green** (exit 0), PHPStan **L5 = 0**, Pint clean. Corrected the recorded stack to **Laravel 13**
  (`composer.json` `laravel/framework ^13.8`). Prepended an authoritative **2026-07-28 CURRENT-STATE block** to
  `docs/PROJECT-STATE.md` (what Vyrobel is · the full phase-by-phase build status incl. the Super Admin panel + the
  owner-page exact-match track + the Bookings audit · the decode method · the quality state · the honest deferrals · the
  path to launch · the creds · the next action) that supersedes every earlier block; and **reconciled the canonical
  numbered Invariants list to #1–55** — it had lagged at #53 (added **#54** owner Overview exact-match + **#55** owner My
  Units + Add Unit exact-match; commit `93587b6` had introduced #55 in prose without extending the list). Updated
  `docs/RESUME.md` (date · invariant range · suite count · the next action = Owner Bookings + Booking-Summary PDF).
  Confirmed a fresh session reading `docs/RESUME.md` → `docs/PROJECT-STATE.md` inherits the complete context. **Invariants
  #1–55.**
- **Owner Bookings — EXACT-MATCH BUILD, both gates ✅ (2026-07-28; `docs/OWNER-BOOKINGS-AUDIT.md`; commits per gate).**
  The founder's "prior deferrals in scope" upgrade, done as two gated deliverables, honesty unchanged (#39/#28/#17/#20).
  - **GATE 1 — the Bookings PAGE (`/owner/bookings`), wireframe-exact + real backends.** The two items once REFUSED as
    fabrication (DIVERGENCE row 127) are now REAL: the **"Bookings over time" chart** (segmented `reservations.status`
    stacks per check-in month + the real net line via `OwnerLedgerAttribution` + a real goal line) and the **"Monthly
    goal" ring** (real current-month net ÷ the owner's OWN target — a value they set, honest "no target" state when
    unset). New backing: `owner_monthly_targets` (owner+currency, unique) + `POST owner.bookings.target` (`$capActions`,
    co-owner only; **currency DERIVED server-side** from the owner ledger via the new `OwnerBookingsReader::primaryCurrency`,
    never trusted from the form; idempotent upsert). New `GET owner.bookings.export` (`$capExports`) → CSV of the SAME
    rows via the ONE shared `App\Support\Owner\OwnerBookingsReader` (page + export can't drift; guest name-only, no
    contact — #28). The component gained GUESTS distinct count, a Status filter, date-range presets+custom, sortable
    columns, pagination — all server-side over the owner-scoped set (GET, so the scope middleware re-runs; **no Livewire
    mutation** — #20). Page-scoped CSS (kept OUT of the shared owner layout so its class names can't trip other pages'
    `assertDontSee` isolation guards) + an opt-in shell breadcrumb. Flagged DEMO-only €6,000 target so the ring renders
    on a fresh seed. **Adversarial review (2 agents): all isolation/PII/capability/honesty/money invariants HOLD; fixed 4
    real bugs** — `Carbon::subMonth()` overflow made "Last month" collapse into the current month on a 31st; a tab-DEFAULT
    sort direction became sticky across tabs (now only an EXPLICIT sort/dir persists); the chart legend counted the full
    set against an 8-month-truncated chart (now sums the displayed months); net-sort now sinks a booking with no net in
    the portfolio currency — each with a regression test. `OwnerBookingsTest` 15 (9 new).
  - **GATE 2 — the Booking-Summary PDF (`OwnerBookingSummaryDocument` + `pdfs/owner-booking-summary.blade.php`),
    wireframe-exact + real data.** The old PDF-docblock refusals were STALE and are now REAL: **occupancy** (headline +
    per-unit) via the shared `PerformanceMetrics::forOwnerUnits()` (keyed by physical unit id — owner-scoped, never
    room-type-wide; period `[start, end)` with the last day included; denominator = earning units, consistent with the
    rows shown), the **guest NAME column** (name-only via the shared `GuestName`, #28 — never contact), and the per-channel
    **commission** (Σ real `owner_ledger.ota_cost`; €0 → honest "no commission"). Real **letterhead** (`billing_company` +
    `billing_vat_id` + `country`; VAT shown only when set; street/city not modelled) + a period-derived **BS-YYYY-MM** doc
    ref (the statement it accompanies is cross-referenced by its REAL `sequence_no`, never the design's "OS-…"). The full
    booking list is kept (founder's call) with the wireframe's callout styling. **STILL REFUSED (#39):** the OTA **brand**
    split (Booking.com/Airbnb) — unmodelled; grouped by the real `source` enum. The Σ(rows)===gross reconciliation is
    UNCHANGED (occupancy/commission are additive presentation, never summed into money). Composed strings moved into the
    builder (so the PDF view is logic-free — a top-of-file `@php` block was dropping the `<head>` at compile time). Flagged
    DEMO-only `billing_company`/`billing_vat_id` on the demo org so the letterhead renders on a fresh seed. **Adversarial
    review: CLEAN — no critical/high; all 7 invariants HOLD (reconciliation, owner-scoped occupancy with correct bounds,
    guest name-only, real commission, graceful letterhead, PDF-safe + isolated, blade correct); 2 LOW notes accepted (N+1
    occupancy queries — kept for arbitrary-period correctness on an infrequent PDF; a multi-currency commission-label
    edge — informational).** PDF test 8 (updated to the GATE-2 honesty stance: name-only shown, occupancy real, commission
    real, contact NEVER, OTA brand still refused; Σ===gross still asserted).
  - **Verification (both gates):** suite **1060 / 8207 green** · PHPStan **L5 = 0** · Pint clean · `migrate:fresh --seed`
    clean · browser-verified page + rendered-document vs the two wireframes (data reconciling: page Sep €886 = the two
    real €442.80 bookings = €885.60 net; PDF €1,080 gross = Σ by-unit = Σ by-channel = Σ bookings, real 13% occupancy).
  - **Invariant #56 — Owner Bookings exact-match (page + PDF):** the "Bookings over time" chart + "Monthly goal" ring are
    REAL (status counts + `OwnerLedgerAttribution` net + an owner-SET `owner_monthly_target`, never a fabricated goal); the
    page + CSV export read the ONE `OwnerBookingsReader` (can't drift; name-only, no contact — #28; the target write is a
    `$capActions` CONTROLLER route with a server-derived currency — #20/#30); the PDF shows REAL owner-scoped occupancy
    (`PerformanceMetrics::forOwnerUnits`, physical-unit-keyed), the guest NAME-only (`GuestName`), the real channel
    commission (`ota_cost`) and the real letterhead VAT — the OTA **brand** stays refused (unmodelled); Σ(rows)===gross
    to the cent is preserved. **Invariants #1–56.**

---

## 2026-07-28 — Owner **Statement** page + **Statement PDF** rebuilt wireframe-exact + real reconciling data (#57)

The owner Statement (`/owner/statements/{id}`, `App\Livewire\Owner\OwnerStatementView`) and its downloadable **Statement
PDF** (`owner.statements.pdf` → `OwnerStatementDocument` → `pdfs/owner-statement.blade.php`) rebuilt to their two decoded
wireframes (`Owner Statement.html` = an app page; `Owner Statement PDF.html` = a print document) EXACTLY, on REAL,
owner-scoped, reconciling data — the founder's "prior deferrals in scope" bar, same as Overview (#54) / My Units + Add Unit
(#55) / Bookings (#56). Read-only PHASE-1 audit first (`docs/OWNER-STATEMENT-AUDIT.md`) → two gated builds. Honesty held
throughout (#39/#28/#17/#20). Suite **1060 → 1066** (+6 net new); PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean.

**The decisive finding (re-verifying stale refusals):** the DIVERGENCE-register row 132 refused the page's occupancy
sparkline + the "IBAN ···4821" chip as fabrication — but those refusals PREDATE the Overview exact-match (#54), which
shipped a real owner occupancy trend (`OwnerOccupancy` + `PerformanceMetrics::monthlyTrendForOwnerUnits`) and a real masked
IBAN (`Iban::masked` + `OwnerPayoutMethodService`, CAP_PAYOUT) on a SIBLING owner surface. So both are STALE → now REAL.
The only standing refusals: the "Payment & ops fees" breakdown line (the engine has NO such bucket — its closed set is
gross · other_revenue · commission · management_fee · expenses · ota_cost · adjustments · net) and the estimated payout
DATE (no scheduled-date field). Neither is invented.

**GATE 1 — the PAGE (`ddf1f55`).** Rebuilt to the wireframe: the month **"jump to month"** switcher (real statements +
net); a **plain-glass hero** (dropped the accent border, the Overview-hero fix); the breakdown with the real gross **"N
nights · M units"** sublabel + the derived "18% of gross room revenue"; the dark rail with the real **masked IBAN chip**
(CAP_PAYOUT only) + the tax-hold paused note (no est. date); the **real occupancy card + mini bar chart**; a standalone
**Download statement** + a **More documents** card; **Statement history** with a real **Export CSV**, a per-year **Export
PDF**, and a per-row statement PDF; **by-unit** unit-name links. New routes `owner.statements.history.{csv,pdf}`
(`OwnerStatementHistoryExportController` + `OwnerStatementHistoryDocument` + `pdfs/owner-statement-history.blade.php`),
owner-scoped + **CAP_EXPORTS**, optional `?year=` (clips, never widens; a DISTINCT `/statement-history/*` path so
`/statements/{statement}/pdf` can't shadow it). No `/livewire/update` mutation (#20). Page-scoped CSS in the blade (kept
OUT of the shared owner layout so its class names can't trip other pages' `assertDontSee` isolation guards); fixed a
`word@directive` raw-Blade trap (`transfer@endif`).

**GATE 2 — the PDF.** Rebuilt `OwnerStatementDocument` + the blade to the print wireframe: the real **letterhead**
(`billing_company`/`country`/"managed with Vyrobel"), the **OS-YYYY-MM** ref, the subtitle, a **net-payable dark headline**
+ a stats panel (**Occupancy · Nights · ADR**, real via the shared breakdown; ADR = gross ÷ nights, divide-safe), a
3-column breakdown with detail, a **Performance-by-unit** table (nights · occupancy · revenue + a Total-gross row), a
payout-note callout, and an identity/email footer. The masked **IBAN** renders only for a **CAP_PAYOUT** exporter
(`$canSeePayout` computed in the controller from `OwnerContext`) — an accountant export (CAP_EXPORTS) omits it (#28); no
estimated date (#39). PDF-safe (dompdf HTML4/CSS2.1, DejaVu Sans); the view is logic-free (strings composed in the builder
— the #56 "top-of-file @php dropped the `<head>`" lesson); a second `word@directive` trap (`transfer@if`) fixed.

**Anti-drift (Decision D-STMT-4):** three shared helpers extracted — **`OwnerNextPayout`** (masked IBAN + tax-hold, from
`OwnerDashboard` #54, which now delegates) · **`OwnerOccupancyTrend`** (occupancy % + delta + sparkline + per-month bars,
from `OwnerDashboard`, which now delegates — the Overview + Statement occupancy can't drift) · **`OwnerStatementBreakdown`**
(the per-unit net/gross/occupancy split, from `OwnerStatementView`, reused verbatim by the PDF — page + document can't
drift). The whole suite staying green is the proof the `OwnerDashboard` refactor didn't regress the Overview.

**Decisions (founder-approved):** **D-STMT-1** seed a flagged demo statement history — `DemoSeeder::seedOwnerStatementHistory`
creates Apr–Aug 2026 real apartment stays → attribution → statements finalized IN PERIOD ORDER (so each claims only its own
lines) + marked paid (a demo affordance, since a real `pay()` needs the tax details the demo owner deliberately lacks), run
BEFORE the September finalize so **September still claims only its own lines (net €885.60 unchanged)**; every net is real
gross − the real 18% commission (#39). **D-STMT-2** the IBAN on the PDF is CAP_PAYOUT-gated (an accountant export never
leaks the account number). **D-STMT-3** the history "Export PDF" is a real per-year statement-history summary
(`OwnerStatementHistoryDocument`, Σ net per currency — never a cross-currency sum, #17). **D-STMT-4** the three shared
helpers above.

**Verification:** suite **1066 / 8280+ green** (`OwnerStatementViewTest` 5→9, `OwnerStatementPdfTest` 5→7); PHPStan L5 = 0;
Pint clean; `migrate:fresh --seed` clean (6 demo statements, Sep €885.60 preserved, the Overview "paused" tax-hold intact);
browser-verified — the page vs `Owner Statement.html` (month picker, occupancy 13% + bars, history €3,690 net reconciling,
by-unit → €885.60) and the rendered document vs `Owner Statement PDF.html` (OS-2026-09, Occupancy 13% / Nights 8 / ADR
€135.00, breakdown €1,080 − €194.40 = €885.60, by-unit €540 + €540 = €1,080). Adversarial review passes on the full diff.

**Invariant #57 — Owner Statement exact-match (page + PDF):** page + PDF match their wireframes exactly on REAL,
owner-scoped, reconciling data; occupancy card/stats + masked IBAN (CAP_PAYOUT) are REAL (stale refusals, now backed);
breakdown Σ == net and by-unit Σ == net (page) / gross (PDF) to the cent (#17); page + PDF share the ONE
`OwnerStatementBreakdown` (can't drift); NO guest data on either surface (#28); exports are CAP_EXPORTS controller routes,
the IBAN CAP_PAYOUT-only (#30); no "payment & ops fees" line and no estimated payout date invented (#39). **Invariants #1–57.**

---

## 2026-07-28 — Owner **Payouts** + **Payout Method** rebuilt wireframe-exact — real where real, honest where account-gated (#58)

⚠️ REAL MONEY + REAL BANKING. The owner **Payouts** (`/owner/payouts`, `OwnerPayouts`) and **Payout Method**
(`/owner/payout-method`, `OwnerPayoutMethod`) pages rebuilt to their decoded wireframes (`Owner Payouts.html`,
`Owner Payout Method.html`) EXACTLY — design + behaviour — with REAL payout data + real actions where they exist, and
HONEST labelling of the account-gated banking. Read-only PHASE-1 audit first (`docs/OWNER-PAYOUTS-AUDIT.md`, with a per-
element REAL-NOW / ACCOUNT-GATED / NOT-BUILT classification), founder-approved with four decisions, then two gated builds.
The load-bearing rule here: **never fabricate a payout, a bank-account verification, a micro-deposit, or a bank detail** —
a fake is dangerous, not just dishonest. Suite **1071 → 1073**; PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean.

**The decisive investigation (real vs account-gated).** Far more is REAL than the register's old "account-gated #45"
implied: payout **history / KPIs / the reconciling next-payout breakdown (#17) / the lifecycle stepper (real states only) /
the remittance PDF** are real; the whole **bank-account CRUD** (add/set-default/remove — IBAN **encrypted**, mod-97-validated,
**masked** #28, self-only, `$capPayout` co-owner) is real; **Stripe Connect onboarding** is real **test-mode** (`OwnerConnectController`;
`stripe_connect_payouts_enabled` flips on the `account.updated` webhook — the live-key flip is Subhan's). Genuinely
account-gated (the live banking rail): micro-deposit **verification** (there is NO verification column — a "Verified"
badge would be fabrication), the live **transfer** rail, **SEPA tracing**, the 48h default-**hold**. Not-built: payout
**preferences** (no store) + the **projected** upcoming schedule (no forecast).

**GATE 1 — the Payouts PAGE (`7c343a6`).** Wireframe-exact: the on-hold banner now surfaces the REAL **tax-hold** (a
pending payout + no tax details → "Add tax details"), never a fabricated "verification pending"; the dark rail's **masked
IBAN pill** (`OwnerNextPayout`, #28, CAP_PAYOUT only); the real **payout-account card** (default `PayoutMethod` + Manage);
a real payout-history **Export CSV** (`owner.payouts.export`, CAP_EXPORTS); and a **NEW real preferences store** (D-PAY-2)
— `owner_payout_preferences` (frequency · business-day · minimum · pause) via `owner.payouts.preferences` (a `$capPayout`
controller route, #20; co-owner writes, others read-only), labelled honestly as a preference (never a fake schedule/date).
The projections stay an honest "not yet available".

**GATE 2 — the Payout Method PAGE.** Wireframe-exact: the account list (masked IBAN, default radio, set-default, remove),
the Add-account form, the **Connect Stripe** banner (test-mode), the dark **PAYOUTS GO TO** rail (real default + next-payout
amount, no fabricated date), the **Payout schedule** (the shared preferences store), and the **Tax & remittance details**
inline (D-PAY-3) — real `Owner` fields (type · legal_name · tax_id · vat_number · tax_country) written via the ONE
`owner.tax.save` path (anti-drift + the real hold-release; tax_id/vat encrypted #28). The account-gated banking
(micro-deposit **verification**, **SEPA** tracing, the 48h **hold**) is the EXACT design labelled honestly pending the live
banking connection — **never a fake Verified / deposit / trace**. The **remittance PDF** payee now prints the owner's
registered `legal_name` when set (fed by the tax details), else the display name.

**Decisions (founder-approved).** **D-PAY-1** — seed a flagged, mod-97-VALID demo payout method (Revolut Business · LT
IBAN, encrypted, masked ···1000) so the pages demo a real masked IBAN, WITHOUT tax details (so the tax-hold "Paused" +
"Add tax details" attention stay; only "confirm bank account" resolves). **D-PAY-2** — a real owner-scoped payout-preferences
store (a preference, not a schedule engine — the actual cadence/execution is confirmed with the operator + the live rail).
**D-PAY-3** — the tax & remittance details are editable inline on Payout Method but write via the ONE `owner.tax.save`
path (ONE store, two entry points; feeds the remittance PDF; registered address stays omitted — not modelled). **D-PAY-4**
— the account-gated banking is the EXACT design, honestly labelled pending the live connection, NEVER faked; the on-hold
banner surfaces ONLY the real tax-hold.

**Invariant #58 — Owner Payouts + Payout Method exact-match:** both pages match their wireframes on REAL / honest data;
REAL wired (history/breakdown/remittance + masked IBAN [#28, CAP_PAYOUT] + bank-account CRUD + Stripe Connect [test-mode] +
a real preferences store + inline tax details via the shared write); the account-gated banking (verification / live
transfer / SEPA / 48h-hold) is reproduced EXACTLY, labelled pending-live, and **NEVER faked** (no fake verify/deposit/
payout/schedule); writes are `$capPayout`/`$capActions` controller routes (#20/#28/#42), IBAN masked never full (#28),
reconciling to the cent (#17), nothing fabricated (#39). **Invariants #1–58.**

## 2026-07-28 — Owner-page exact-match: **Balance + Expenses** (invariant #59)

The owner-page exact-match track continued to the last two Earnings pages (`docs/OWNER-BALANCE-EXPENSES-AUDIT.md` =
the PHASE 1 per-element audit). Both pages match their decoded wireframes on REAL / honest data. **⚠️ Balance is
real money** — the money-movement posture inverts (build so it CANNOT move money wrongly).

**GATE 1 — Balance (`/owner/balance`).** Rebuilt to the wireframe as a **reconciling clearing model**: the KPI band is
**Available** (Σ unstamped `owner_ledger` net) / **On hold** (Σ pending payouts held by the REAL tax-hold) / **In transit**
(Σ released pending), with the identity **Available + On hold + In transit + Paid == Net position** holding per currency
**to the cent** (#17). The Balance-activity ledger keeps its real rows + derived running balance and gained a per-row
**state chip** (Available / Paid out / On hold / In transit / Cleared) + an inline statement link; the **"Why is €X held?"**
card + **Add tax details** render ONLY on a genuine tax-hold; the masked payout **IBAN** is CAP_PAYOUT-only (#28); the
**auto-payout cadence** + **"How money clears"** explainer; a real **balance-activity CSV** (`owner.balance.export`,
CAP_EXPORTS). **MONEY-SAFETY (D-BE-1):** "Withdraw early" is honestly **disabled** — there is NO owner-initiated transfer
rail (the Payouts investigation established this), so it NEVER fakes a withdrawal / transfer / "in transit" (#39); "In
transit" is a real figure (€0 while held), never a staged movement. The two prior refusals (the active Withdraw button;
the Why-held / How-clears cards) were **stale** — derivable + reconciling now that the tax-hold is real (#58) — so they are
now REAL (D-BE-2). Component docblock rewritten to match. `OwnerBalanceViewTest` rewritten (6 tests): the reconciling
identity, tax-hold real-only, In-transit €0-while-held vs released, masked-IBAN CAP_PAYOUT, export owner-scoped + gated,
no raw Blade. Committed `a096a89`.

**GATE 2 — Expenses (`/owner/expenses`).** Rebuilt to **"Expenses & maintenance"**. KPIs: **Spent this year** (Σ live
current-year) / **Pending review** (COUNT of NULL-deductible) / **Charged to payouts** (Σ deductible-this-year). The
history table gained a REAL **Unit** column + unit filter (name ?? code) — **vendor REFUSED** (no such column, #39); the
polymorphic source shown instead; the tri-state deductible chip + inline statement/flag as a **Status** column; a real
**Export CSV** (`owner.expenses.export`, CAP_EXPORTS). "Need something fixed?" is now a REAL **relay** into the
owner↔operator channel (`owner.messages.start` — same hardened path as My Units; writes NO expense). A read-only
**"Pending review"** callout (real NULL-deductible rows) fills the wireframe's attention slot honestly. **REFUSED (D-BE-3,
#39):** the **"Awaiting your approval"** KPI + the **"Needs your approval"** Approve/Query workflow — there is no
owner-approval state machine (deductibility is resolved by the **management agreement**, not owner-approved). By-category
**proportional bars** retained (Batch B). `OwnerExpensesViewTest` updated (5 tests, +export/relay/unit; "Spent this year"
year-scoped).

**Decisions (founder-approved).** **D-BE-1** — the "Withdraw early" instant transfer is honestly account-gated (exact
design, disabled, labelled pending the live payments connection); NEVER a fake movement. **D-BE-2** — the reconciling
clearing model is derivable + reconciles now that the tax-hold is real, so the previously-refused clearing states are
built REAL. **D-BE-3** — expense approval is NOT a real workflow → the honest analog (Pending-review count + read-only
list + explainer), never a fake approve/query. **D-BE-5** — a flagged demo seed (`seedOwnerBalanceAccrual` un-statemented
October revenue → a real positive Available €963.60; `seedOwnerExpenses` a flagged spread across both apartments + all
three categories + the tri-state; the demo agreement marks `other` non-deductible so incidental supplies stay off the
owner ledger).

**Invariant #59 — Owner Balance + Expenses exact-match:** both pages match their wireframes on REAL / honest data;
Balance's clearing model reconciles to the cent (#17) and its withdrawal is honestly account-gated (never a fake
movement, #39); Expenses' KPIs/history/unit/export/by-category are real and its repair path is a real owner→operator
relay; expense approval + vendor are REFUSED (#39); masked IBAN never full (#28); exports CAP_EXPORTS-gated (#42). Suite
**1077/8370** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; browser-verified. **Invariants #1–59.**

## 2026-07-28 — Owner-page exact-match: **Block Calendar + Documents** (invariant #60)

The owner-page exact-match track continued to the Calendar + Documents pages (`docs/OWNER-CALENDAR-DOCS-AUDIT.md` =
the PHASE 1 per-element audit). Both wireframes decoded (dc-runtime bundles); both live pages rebuilt EXACTLY on their
REAL owner-scoped backends. Both backends were already real (the block/release path; the on-demand PDFs).

**GATE 1 — Block Calendar (`/owner/calendar`, `ab4333b`).** Rebuilt to the wireframe as an interactive grid: **click a
day, or press-and-drag a range** (client-side Alpine) → the Selected-range composer → Confirm → a REAL block POST
(`OwnerBlockController` → `OwnerBlockService` → the certified `AssignmentService`, non-overlap enforced, `$capActions`
#20/#42, audited). **Click a blocked day → a REAL release.** The 4-state legend (Booked / Owner block / Selecting /
Open) + a real stat line; booked cells now show the guest **NAME** (name-only, #28, via the shared `GuestName` —
reversing the old page's tighter no-name choice, matching the owner Bookings-PDF precedent); reason **buttons**
(Personal stay / Maintenance / Seasonal hold) + an optional note; the Scheduled-blocks list (a series collapses to one
row, Remove releases it) + a **toast + Undo** (Undo releases what was just created).
- **Investigate (a) REASON:** already real — stored in `InventoryBlock.notes` (the `reason` column stays the enum
  `'owner'`). `OwnerBlockService::REASONS` aligned to the wireframe's three.
- **Investigate (b) REPEAT/SERIES:** a REAL build — `OwnerBlockService::blockSeries()` expands the recurrence (weekly /
  fortnightly / monthly × occurrences, capped at `MAX_OCCURRENCES` = 12) into **N REAL individual `InventoryBlock`s**
  sharing a new nullable **`series_id`** (a small honest schema add), NEVER a fake "scheduled series" (#39). An
  occurrence whose nights are already taken is **skipped and reported** ("N of M blocks created"). New:
  `calendar.release-series`, `OwnerBlockService::releaseSeries()`.

**GATE 2 — Documents (`/owner/documents`).** Rebuilt to the wireframe: a year-filter pill row + **one frosted card per
real month** (statement period · No. · Net · the STMT/BKG/RA doc rows with real status + rail + PDF) + a **DARK annual
tax-summary card per year** ("{year} annual tax summary · All 12 months · net {X} · one PDF for your accountant"). The
whole backend was already real (an aggregation over the on-demand generators, `CAP_EXPORTS`-gated, owner-scoped #28).
- **Investigate — ANNUAL TAX SUMMARY:** already exists (`owner.tax.annual` → `OwnerAnnualTaxDocument`); now surfaced as
  the dark card with the **REAL net** = Σ `net_source_minor` for the year (the annual PDF's own basis via
  `OwnerTaxService::annualSummary`), so the card reconciles to that PDF to the cent (#17). The design's fabricated
  "net €98,710" stays refused; the not-in-wireframe "Download everything ZIP" stub is dropped (not stubbed).

**Decisions (founder-approved by the brief).** **D-CAL-1** — the block reason is real (stored in `notes`); the three
options match the wireframe. **D-CAL-2** — the repeat is a REAL series (N individual blocks + `series_id`), never faked;
over-taken occurrences are skipped + reported. **D-DOC-1** — the annual tax summary is the existing real PDF, surfaced
with the real reconciling net; the ZIP is dropped as not-in-wireframe.

**Invariant #60 — Owner Block Calendar + Documents exact-match:** both pages match their wireframes; the Calendar's
drag-select block/release/series are REAL (mutation-proven, owner-scoped #28 guest-name-only, controller-routed #20,
audited, non-overlap enforced) with NO fabricated series (#39); the Documents archive is the real on-demand PDFs +
the real annual-tax summary, `CAP_EXPORTS`-gated (#42), reconciling (#17). Suite **1081/8394** green; PHPStan L5 (0);
Pint clean; `migrate` clean; browser-proven (a real drag block + release; real statement + annual-tax PDF downloads).
**Invariants #1–60.**

## 2026-07-28 — Owner-page exact-match: **Reports + Insights** (invariant #61)

The owner-page exact-match track continued to the analytics pages (`docs/OWNER-REPORTS-INSIGHTS-AUDIT.md` = the PHASE 1
per-element audit). Both wireframes decoded; both rebuilt EXACTLY on the REAL Batch-B owner analytics (`PerformanceMetrics`,
`BookingCadence`, `OwnerLedgerAttribution`) — reused, not duplicated (anti-drift). Two judgment calls were founder-approved
(VAT honest-defer; proposals honest link).

**GATE 1 — Reports (`/owner/reports`, `949ea93`).** Rebuilt from a minimal page to the full wireframe: 3 KPIs (Net earned /
Avg occupancy / Nights sold), a **Net-earnings-by-month** chart, **Performance by unit** (ADR/RevPAR/nights/occupancy/net/
share), **Booking sources** by the real `reservations.source` enum, the **Gross-to-net** breakdown, a payout summary, real
Documents, a dark hero, and CAP_EXPORTS-gated exports.
- **⚠️ COMMISSION RATE (D-RI-1):** the wireframe hardcodes "Commission · 15%"; the page now renders the **REAL agreement
  rate** (18% in the demo) read from `ManagementAgreement.commission_pct` — never a hardcoded value. The gross-to-net
  reconciles to the cent (#17): gross + commission + fees + adjustments == net, bucketed from the real owner ledger.
- **⚠️ TAX & VAT (D-RI-2, founder-approved HONEST-DEFER):** VAT is NOT modelled (no VAT rates/bases — only a stored VAT
  number). The Tax & VAT summary renders as an honest deferred card (points at the real annual tax summary + Tax Center),
  with NO fabricated Output/Input/Net VAT figure and NO "Mark filed" (#39 — a VAT figure a tax authority could rely on is
  never faked). "Email to me" = an honest "Soon" (no report Mailable). Notifications = the real self-scoped feed.

**GATE 2 — Insights (`/owner/insights`).** Rebuilt to the 2-column wireframe: **Occupancy by month** (this-year achieved +
on-the-books forward), **Booking pace** (real forward months), **Rate performance** per unit (occupancy + achieved ADR),
the lead-time distribution + day-of-week occupancy (+ a real derived caption), and **"Worth a look"** = REAL DERIVED
observations (hottest weekday, median booking window, forward revenue — each a real computed fact, decisions-stay-yours).
- **⚠️ vs LAST YEAR (D-RI-3, HONEST-OMIT):** the design's "vs last year" series + deltas are omitted (no prior-year data →
  never a fabricated comparison, #39).
- **⚠️ RATE PROPOSALS (D-RI-4, founder-approved HONEST):** the "N rate proposals waiting / Review proposals / proposed −8%"
  callout asserts a proposal entity that does not exist (refused in the My Units audit) → replaced with an honest "Turn
  insight into rate → Open your Rates" link to the real owner Rates page, with NO fabricated proposal count/workflow.

**Decisions (founder-approved).** **D-RI-1** — render the REAL agreement commission rate, never the wireframe's 15%.
**D-RI-2** — VAT is honest-deferred (no fabricated figure/filing). **D-RI-3** — vs-LY comparisons omitted where no prior-year
data. **D-RI-4** — the proposals callout is an honest Rates link, not a fake proposal workflow. (Email-a-report deferred.)

**Invariant #61 — Owner Reports + Insights exact-match:** both pages match their wireframes on the REAL owner analytics
(owner-scoped #28; gross-to-net reconciles to the cent #17 at the REAL commission rate; exports CAP_EXPORTS-gated #42);
the stateful/unbacked bits are HONEST (VAT deferred, vs-LY omitted, proposals → a real Rates link) with NOTHING fabricated
(#39). `OwnerAnalyticsTest` 4→7 (gross-to-net at a distinctive real 22% rate; honest VAT; export gating; real insights +
honest rates link). Suite **1084/8446** green; PHPStan L5 (0); Pint clean; both browser-verified. **Invariants #1–61.**

## 2026-07-28 — Owner-page exact-match: **Settings HUB** (invariant #62)

The owner-page exact-match track reached the largest page: the Settings HUB (`docs/OWNER-SETTINGS-AUDIT.md` = the PHASE 1
per-section classification + reuse map). The 1,136-line wireframe's 13 sections were rebuilt EXACTLY — **most REUSE
features already built (anti-drift), none rebuilt** — HONEST where account-gated. Built in two gated section-groups.

**GATE 1 — Identity & money (`7981030`).** Hero (DERIVED completeness ring + real stats channels/team/invoices) · Profile
(reuse `OwnerAccountController` — avatar/name/locale/email-verify, works for sub-users) · Business & tax (reuse
`owner.settings.update` [primary-owner #42] + an inline `owner.tax.save` form — anti-drift, the same store as Tax Center +
the remittance PDF) · Billing & invoices · Payout account (real masked IBAN, `OwnerNextPayout`, CAP_PAYOUT #28) · Profile
completeness (a DERIVED checklist).
- **⚠️ FEE RATE (D-SET-1):** the wireframe hardcodes "15%"; a new shared `App\Support\Owner\OwnerCommissionRate` reads the
  REAL agreement rate (18% in the demo) — used by BOTH the Settings billing card and Reports' gross-to-net (one definition,
  can't drift). `OwnerReports::commissionLabel()` refactored onto it.
- **⚠️ BILLING (D-SET-2):** the OWNER has no Vyrobel card/subscription (that's the operator's Phase-7b billing) → an
  honest management-fee view (the real rate, billed via statements) + the real statement PDFs as fee documents. NO
  fabricated card/invoice (#39/#41).

**GATE 2 — Preferences & access.** Language & region (locale real via Profile; currency/timezone the property's,
read-only) · Notifications (reuse `owner.notification-preferences` — in-app + email are the REAL channels; **SMS + Push are
honest-pending**, no provider #39) · Connected channels (**Direct is the one live channel; OTA is honest-labelled Pending**
— never a fake "Connected" that syncs nothing #37/#39) · Team access (a real summary + link to the reused `owner.team`) ·
Appearance & motion (**Reduce motion is REAL** — the layout honors `prefers-reduced-motion`; theme honest-soon) · Data
export (the real CAP_EXPORTS-gated exports #28).

**Decisions (founder-guidance-aligned; no new prompts needed).** **D-SET-1** — render the REAL agreement commission rate,
never the wireframe's 15% (shared helper). **D-SET-2** — billing is the honest management fee, no owner card. **D-SET-3** —
SMS/Push notifications honest-pending (no provider). **D-SET-4** — OTA channels honest-Pending, Direct live, never a fake
"Connected". **D-SET-5** — Team / Notifications management REUSE the dedicated pages (summary + link, anti-drift).

**Invariant #62 — Owner Settings hub exact-match:** the 13-section hub matches the wireframe, each section REUSING its real
backend (Profile/tax/team/exports/security/payout-IBAN/notifications) or HONEST where gated (OTA channels / SMS / push /
owner card / theme — labelled, never faked #39/#37); the REAL fee rate (shared `OwnerCommissionRate`, #17); masked IBAN
never full + CAP_PAYOUT (#28); every write a guarded controller POST (#20). `OwnerSettingsTest` 5→7 + `OwnerAccountProfileTest`
updated. Suite **1086/8497** green; PHPStan L5 (0); Pint clean; browser-verified. **Invariants #1–62.**

## 2026-07-28 — OPEN QUESTION (flagged, not decided): payout "Option A" vs the shipped #58 IBAN collection

A docs-consolidation brief referred to Payouts (#58) as "Option A: honest payout-summary, the real payout ENGINE deferred,
**NO IBAN-collection-without-a-certified-pipeline**." Verified against the repo, the SHIPPED #58 does NOT match that: it
**collects real bank details** — `PayoutMethod` stores an **encrypted `iban`** (+ `iban_last4`/country), the
`owner.payout-method.add` route **ISO mod-97-validates** the IBAN before storing (self-only, `$capPayout`), and
`OwnerConnectController` runs **Stripe Connect onboarding (test-mode)** with a signature-verified Connect webhook. What is
actually DEFERRED is the automated payout **EXECUTION/DISBURSEMENT engine** (scheduled payouts · micro-deposit/bank
verification · SEPA tracing · the 48h hold · instant withdrawal), account-gated on live Stripe/banking + PSD2/PCI/GDPR;
`ManualPayoutRail` is the real tested disbursement path, and the Balance "Withdraw early" is honestly disabled.

**This mismatch is UNRESOLVED — recorded so it is not lost.** If the founder genuinely wants "Option A" (collect NO owner
IBAN until a certified disbursement pipeline exists, on a compliance basis), that is a **code change** — remove or gate the
`PayoutMethod` add-path + the Connect onboarding, and reduce the Payout Method page to an honest summary + a "banking
connects with the live payout engine" deferral. The current build is the fuller "collect-now (encrypted), disburse-later"
design. No code was changed for this consolidation (docs-only); flagged for the founder to decide.

## 2026-07-28 — Owner REPORTS exact-match, DEEPENED to the full wireframe (#63)

The owner Reports page (`/owner/reports`) — already built at #61 with the honest data right — was **deepened to the full
decoded `Owner Reports.html`** (design + behaviour) on REAL owner-scoped analytics, honest where account-gated
(`docs/OWNER-REPORTS-AUDIT.md`). The page stays **read-only** (year / date-range / export are GETs → no write to gate, #20).

**GATE 1 (`f05a9b8`) — analytics exact-match + the two data upgrades:**
- **Reporting-year dropdown** driven by the owner's REAL statement years (never the wireframe's fabricated 2024/2023) +
  "Earlier years live in Documents"; YTD / Last-6-months range options; a **custom From/To date-range** popover (a real GET
  clipping the owner ledger).
- **Richer Export menu** wired to REAL routes (PDF report → statement-history PDF · CSV · spreadsheet(=CSV) · annual tax PDF ·
  all-statements → Documents), honest **"Soon"** for PDF-pack + Email-to-me; CAP_EXPORTS-gated (#42).
- **D-REP-1 — NET-by-channel is REAL (stale-refusal re-verification win).** The prior page showed nights-by-channel; the
  wireframe wants **net** by channel. It IS computable: each owner-ledger entry is attributed to its reservation's REAL
  `source` (via a keyed `reservations.source` lookup) or an honest **"Other"** bucket for non-reservation rows, summed by
  `signedMinor()`. **Σ(channels) == the period net EXACTLY** (a decomposition of the same net, not a parallel figure, #17).
  Uses the SHARED `OwnerBookingsReader::sourceLabel` (anti-drift — the same labels as Bookings; `ota` → "OTA", never a brand #39).
- **D-REP-2 — prior-year comparison HONEST-OMITTED.** The wireframe's chart dashed prior-year bars + KPI "+Δ" + hero YoY badge
  have **no real basis** (the demo owner ledger is 100% the current year; zero prior-year rows) → omitted, never fabricated
  (#39). The series/delta appear only when real prior-year data exists (`hasPrior*` / `yoyDelta()` guards).
- Gross-to-net keeps the **REAL agreement commission rate (18%, `OwnerCommissionRate`, never 15%)**, reconciles (#17); per-unit
  grid (ADR/RevPAR header + share bars + All-units total); dark hero (YoY badge omitted → real nights·units); payout card
  (masked IBAN CAP_PAYOUT-only, #28); real Documents. Notifications = the shared shell bell (the redundant right-rail card dropped).

**GATE 2 — Tax & VAT summary: HONEST-DEFERRED in the wireframe's card chrome.**
- **D-REP-3 — the VAT block is a KIND-2 fabrication → REFUSE the figures, style the honest state.** The wireframe's Output VAT /
  Deductible input VAT / **Net VAT due · payable to Autoridade Tributária** / per-quarter chips / **Mark-filed** toggles /
  numbers-bearing **VAT-return CSV** assert a VAT COMPUTATION Vyrobel does not model (grep-confirmed: only a stored encrypted
  `vat_number` string — no rate/base/filing store anywhere). A "Net VAT due" figure is one an owner could **file a real return
  on** → unacceptable to fabricate even "for information only" (#39). The block is rendered in the wireframe's card chrome
  (icon + header + a clear **"Not yet available"** chip + the owner's REAL stored tax country when set) as an **honest deferred
  state** — NO figure, NO "payable to", NO filing toggle, NO VAT-return-with-numbers — linking the REAL annual tax summary PDF +
  Tax Center. Re-confirms the founder-approved #61 VAT decision. "Email to me" stays honest-"Soon" (no report Mailable).

**Verified:** `OwnerAnalyticsTest` 7→10 (net-by-channel reconciles to the cent; real reporting-year dropdown, no fabricated
prior years; custom date-range clips; VAT honest — no Output/Net-VAT/Mark-filed/"payable to"/VAT-return). Full suite green;
PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. Browser-verified: year dropdown (real "2026 · 6 statements YTD"),
custom range (Aug–Oct €2,750.80), export menu (3 real exports 200 — CSV/PDF/annual), net-by-channel reconciles (Direct
€4,460.80 + Walk-in €442.80 + Other −€250.00 = €4,653.60), honest VAT. Reuses `<x-owner.shell>` + `--vy-*` +
`OwnerLedgerAttribution`/`PerformanceMetrics`/`OwnerNextPayout`/`OwnerCommissionRate`/`OwnerBookingsReader` (anti-drift). No
new writes, no schema change. **Invariants #1–63.**

## 2026-07-28 — Owner TAX CENTER exact-match (#64)

The owner Tax Center (`/owner/tax`) — already one of the most-REAL owner pages — was matched to the full decoded
`Owner Tax Center.html` (design + behaviour). This page GATHERS already-built features into one screen; the build
REUSED them (anti-drift, not rebuilt) and added only one small additive column + an honest withholding state
(`docs/OWNER-TAXCENTER-AUDIT.md`, DIVERGENCE Owner-Tax-center row).

**The reuse map (every section wires to an ALREADY-BUILT feature — proven shared):**
- **Tax-hold banner (2 states)** → `OwnerTaxService::heldTotalMinor` + `Owner::hasTaxDetails()` = the SAME hold as
  Payouts/Balance (`OwnerPayoutService::pay` genuinely refuses without a tax ID). Browser-proven: saving a tax ID
  released the real €885.60 hold AND cleared it on the Payouts page too.
- **Tax profile store** → `owner.tax.save` (`OwnerTaxController` → `OwnerTaxService::saveTaxDetails`) = the SAME path
  Settings "Business & tax" + Payouts write. Encrypted `tax_id`/`vat_number` (#28), audited, self-only (`user_id`,
  never a request id), `$capActions` (#20). Saving a tax_id releases the hold.
- **Annual summaries + PDF** → `owner.tax.annual` (`OwnerAnnualTaxDocument`) = the SAME PDF Documents offers,
  reconciling (#17), `$capExports` (#42). **Documents** link → the real `owner.documents` archive.

**D-TAX-1 — WITHHOLDING is NOT modelled → HONEST (never fabricated).** The wireframe's `Component` hardcodes a
residency→rate map ("15% treaty rate withheld on payouts to Ireland", "20% non-resident") — but Vyrobel withholds
NOTHING (grep-confirmed: no withholding engine; the only deductions are commission/ota_cost/expense). A "15%
withheld" figure that reduces no payout is a fabrication (#39) and a dangerous one (an owner could under-report
expecting a withholding credit never remitted). The Withholding card is rendered in the wireframe's design as an
HONEST universal state: figure **"None"** + note "Vyrobel doesn't withhold tax from your payouts — you receive your
full net earnings and declare your rental income in your own tax return." Same discipline as the Reports VAT block.

**D-TAX-2 — the "VAT registered" toggle stores a REAL flag (one additive column), NOT a VAT computation.** The
wireframe's toggle needs a registration boolean independent of the number; the store had only `vat_number`. Added an
**additive `vat_registered` boolean** (default false) to `owners` + `Owner::$fillable`/casts + `OwnerTaxService::FIELDS`
+ the controller validation. The toggle gates the (encrypted) VAT number; de-registering (`vat_registered=0`) drops
the number so no stale VAT id lingers on the letterhead (the flag is the source of truth). This is a REGISTRATION
FLAG for the letterhead/invoices — NOT a VAT computation (unlike the Reports VAT block, correctly refused).

**Design:** rebuilt `tax-center.blade.php` to the wireframe (page-scoped `.vy-tax-*` + shared `<x-owner.shell>` +
`--vy-*`; the GREEN #4F7A46/#6FA764 "Tax details complete" state, the Registered-as segmented toggle, the VAT-registered
toggle, the honest Withholding card, the annual-summary card-rows, the VAT-invoices/Documents link, the dark
About-your-tax panel). The form still POSTs to `owner.tax.save` (a real controller write, #20 — NOT `/livewire/update`).

**Verified:** `OwnerTaxCenterTest` 5→7 (`vat_registered` round-trips through the shared store + de-register drops the
encrypted number; honest withholding — never "treaty rate"/"withheld on payouts"; the green complete state). Full
suite green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. Browser-proven: save→hold released (+ cleared on
Payouts, anti-drift), VAT toggle reveals the number field, annual PDF 200. Additive column only — DISTURB NOTHING (the
green suite is the proof). **Invariants #1–64.**

## 2026-07-28 — Owner TEAM & ACCESS + UNIT DETAIL exact-match (#65)

Two owner pages matched to their decoded wireframes (`Owner Team.html`, `Owner Unit Detail.html`), gated per page
(`docs/OWNER-TEAM-UNITDETAIL-AUDIT.md`, DIVERGENCE rows).

**GATE 1 — Team & access (`e5d1a1f`).** Rebuilt `/owner/team` on the REAL owner-team feature (REUSED, not rebuilt):
members / role change / remove / invite / pending revoke — every mutation a controller POST behind `EnsurePrimaryOwner`
(#20). Additions:
- **"What each role can see" matrix — REAL**, derived verbatim from `OwnerTeamRole::CAPABILITIES` (the SAME grid the
  route gates + `@ownercan` read) — anti-drift, never a fabricated table.
- **Recent activity — TRUTHFUL**: real member sign-ins + invites sent only (team actions aren't audited → no fabricated
  event log).
- **D-TEAM-1 — a NEW real RESEND** (`owner.team.invites.resend`): mints a fresh token + extends expiry + re-emails,
  primary-owner-gated + throttled. The proto's Resend was a no-op toast; now it works.
- **D-TEAM-2 — the security note's "financial exports are watermarked with who downloaded them" claim is FALSE** (no
  watermark is applied, and downloads aren't audit-logged) → SOFTENED to the true claim ("separate sign-ins, role-scoped
  access, 2-step"). Never claim a control that isn't implemented (#39).
- Kept the real inline role-change editor (the wireframe shows a display-only pill — retaining the real feature).

**GATE 2 — Unit Detail.** Rebuilt `/owner/units/{unit}` to the wireframe. This page AGGREGATES built features; the build
REUSED them (anti-drift). **The headline: the Wave-1 component's "omitted, no backend" refusals were STALE** — the
backends shipped later — so, per the stale-refusal re-verification rule, they are now BUILT REAL:
- **D-UD-1 — day-blocking is REAL** (`calendarDays()` renders booked/blocked/open via the SAME derivation as the Block
  Calendar; tap an open day → `OwnerBlockService` via `owner.calendar.block`, `$capActions`, non-overlap enforced).
  Browser-proven: blocking 2026-10-01 flipped the day to blocked ("1 blocked").
- **D-UD-2 — pricing edit is REAL** (`pricing()` reads the current nightly rate + min-stay; edit → `OwnerRateService`
  via `owner.rates.apply` with a new `redirect=unit` branch returning to the unit). Per-room-type, exclusive-owned only
  (honest read-only for a shared room type). Browser-proven €180→€185, live + returned to the unit. **Weekend uplift is
  NOT modelled → honest "Not available"** (never a fake save, #39).
- **D-UD-3 — reviews are REAL** (`ReviewRating::forUnit` + `ReviewList::forUnits`; byline first-name+initial only, #28;
  honest-empty; no category bars — unmodelled).
- **D-UD-4 — guest messages → the owner↔operator RELAY** (`owner.messages.start`) — name-free (#28), NEVER a guest thread
  or owner→guest.
- **HONEST / still refused (#39):** the snooze toggle (no owner `is_active` write → read-only status), OTA sync
  (account-gated → honest note), cleaning schedule (not owner-facing → "coordinated by your management team", never a fake
  cleaner), the per-unit payout timeline (payouts are per-statement → the real Payouts link), and "Direct saved you €X"
  (fabricated ×0.15). The real condition-status notice (Out of order / Out of service) is retained. All existing real
  sections (KPIs / occupancy / revenue / attribution-deductions [reconciles #17] / stays [name-only #28] / channel-mix /
  commission / best-badge) were restyled to the wireframe, unchanged in data.

**Verified:** `OwnerTeamInviteTest` 6→8 + `OwnerUnitDetailViewTest` 8→10 (the stale Wave-1 fiction-refusal test updated —
the now-real features are covered; the genuinely-unmodelled fictions still refused). Full suite green; PHPStan L5 (0);
Pint clean; `migrate:fresh --seed` clean. Browser-proven: Team (matrix = 11 real grants, resend re-sends, no watermark);
Unit Detail (rate €180→€185 persisted + returns to the unit; tap-to-block created a real block; all sections render;
view-only sub-user gets no write control). Guards: #28 owner-scoped + guest name-only, #17 reconcile, #20 controller
writes + audited, #39 nothing fabricated. One additive redirect branch on `OwnerRateController`; nothing else disturbed.
**Invariants #1–65.**

## 2026-07-28 — Owner SECURITY + HELP exact-match (#66) · Onboarding DEFERRED

Two owner account pages matched to their decoded wireframes (`Owner Security.html`, `Owner Help.html`); the third
wireframe (`Owner Onboarding.html`) is **DEFERRED** (founder decision). `docs/OWNER-SECURITY-ONBOARDING-HELP-AUDIT.md`,
DIVERGENCE rows. Security + Help were ALREADY built honestly (the account rebuild #46 + the static Help surface); this
pass matched their design while keeping every honest call.

**GATE 1 — Security (`aa041f9`).** Rebuilt `/owner/security` to the wireframe on the REAL security backend, REUSED:
Change-password (`owner.security.password`, current-pw + rules, audited #20), 2FA (`owner.security.2fa.*`), Active
sessions + sign-out-others (real `sessions` table, re-auth), "Request my data" (a REAL owner-scoped streamed JSON export
— not a fake emailed link), Deactivate (reversible, primary-owner-only #42).
- **D-SEC-1 — the gamified SECURITY SCORE is REAL, DERIVED (never fabricated).** The wireframe's n/100 score is rendered as
  a deterministic function of the TRUE protections: **Password set 40 + Two-factor 40 + Recovery codes 20 = /100**; tier
  Basic/Good/Strong from the real number; the 4 stat cards are all real (Protections on n/3 · Points to full · Active
  sessions count · Two-factor On/Off); "Boost your score" lists the REAL remaining protections, each "Enable" jumping to
  the real 2FA control. No fabricated point economy (#39).
- **D-SEC-2 — trusted locations / connected apps / login approval / passkeys / sign-in history = HONEST "Coming soon"**
  (unmodelled — never a fake toggle/location/app/approval).

**GATE 3 — Help.** Rebuilt `/owner/help` to the wireframe, HONEST throughout.
- **D-HELP-1 — the "Ask Vyrobel" AI Q&A is REFUSED** (no support AI) → the hero is an **FAQ search** (client-side over the
  real FAQ) + the real message relay, never a fabricated instant answer.
- **D-HELP-2 — the support-TICKET queue is REFUSED** (no ticket store) → **messaging IS the tracked channel** (the
  owner↔operator inbox, with full history); the honest copy says "no separate ticket queue".
- **D-HELP-3 — the live SYSTEM-STATUS board is REFUSED** → an honest **static "All systems operational"** line (not a
  fabricated live health check).
- **D-HELP-4 — the fabricated owner-desk phone/email/WhatsApp numbers are NOT invented** → the real channel is the relay;
  an honest note points to the welcome pack / statement for any direct line. The FAQ content is real (linking real owner
  screens).

**D-ONB-1 — ONBOARDING DEFERRED (net-new).** The welcome wizard is net-new, NOT launch-blocking, and its centerpiece — an
**IBAN capture + "two micro-deposits on the way"** — is un-buildable-honestly (no certified micro-deposit pipeline; the
real banking path is the certified `owner.payout-method` page, which owns IBAN collection). Rather than build a thin
re-presentation of existing surfaces, the founder deferred it. **If built later:** honest thin flow (profile confirm +
real 18% units + a hand-off link to the real Payout Method + notification prefs), NO fresh IBAN capture, NO fake
micro-deposit, real commission rate.

**Verified:** `OwnerSecurityTest` 6/6 (the derived score reflects true state — a fresh owner shows 1/3 protections + Basic
tier + the 2FA nudge; no fabricated events) + a new `OwnerHelpTest` (real relay + FAQ; no fake AI/ticket/status/number).
Full suite green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; both browser-verified (Security score ring +
real controls; Help FAQ search filters, honest states). No schema change, no new writes. **Invariants #1–66.**

---

## 2026-07-29 — OWNER COMPLETENESS SWEEP · WAVE A (shared backends) — BUILT & SHIPPED (Invariants #1–66, unchanged; behaviour additive)

The `docs/OWNER-COMPLETENESS-SWEEP.md` Wave A (the shared backends built FIRST so later waves reuse them, anti-drift) is complete. Four gates, four commits; suite **1111/8731** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; UI-visible additions browser-verified.

- **GATE 1 — `owner_attention_states` store** (`b0f09cc`): the shared snooze/dismiss/mark-read/undo backbone behind My Units' action-center AND the Overview attention feed (ONE store, both surfaces — proven shared). Owner-scoped (#28), controller-routed (#20) + audited, CAP_ACTIONS-gated, tamper-safe (`OwnerAttention::signalFor` resolves a posted key to a REAL current signal → a stale key is a no-op). `OwnerAttentionStateTest` (4): snooze hides on both surfaces + Undo restores; dismiss hides; tamper no-op; CAP gate.
- **GATE 2 — REAL weekend-uplift pricing** (`a354a81`): additive `rate_plans.weekend_uplift_pct` (decimal(5,2), nullable) + `RateAndRestrictionService::setRateWeekendAware` (per-night Fri/Sat = base×(1+uplift%), the rest = base; ONE txn + ONE `rate_calendar.updated` event) + `OwnerRateService::apply(?float $weekendUpliftPct)` (backward-compatible — existing 6-arg callers untouched) + `weekendUpliftFor()` reader + controller 0–200% validation. NOT an inert flag (#39) — it moves real weekend prices. Same owner-scoped exclusive-owned guard as the nightly rate. `OwnerRatesTest` +2 (uplift real on Fri/Sat only; flat when unset). *(UI editors are a Wave-B item.)*
- **GATE 3 — modelling additions** (`bd9fabf`): (a) owner registered **address** — captured in the primary-owner business form (`OwnerProfileController` → the existing `Owner.address` JSON; all-blank clears it), rendered as the statement-PDF "Prepared for" letterhead block ONLY when set (never fabricated); `@property` annotation added to `Owner` (matches `Property`) so L5 sees `address` as array. (b) expense **vendor** — additive `owner_expenses.vendor`; `OwnerExpenseService::recordExpense` accepts it; real Vendor column on the Expenses page + CSV, honest "—"/blank when unknown (maintenance-sourced costs stay null); demo seed populates 3 real supplier names. (c) **per-user currency/timezone = HONEST HOLD, not a fake column** — money has no FX so a personal display currency would misstate amounts (#39); a personal timezone applied nowhere in this portal is an inert store; both stay property-derived read-only. Tests: `OwnerExpensesViewTest` +1, `OwnerStatementPdfTest` +1, `OwnerSettingsTest` +1.
- **GATE 4 — quick wins** (`this commit`): (a) "**Email to me**" is REAL — a queued `OwnerReportMail` (ShouldQueue) attaching the owner's latest FINALIZED statement PDF to their OWN inbox (no recipient in the request), CAP_EXPORTS-gated route (`owner.reports.email`, throttled), `MailSafetyGuard` applies; honest flash when there is no finalized statement (never a fake send, #39). PDF built in the controller where OwnerScope is active, then queued (worker never re-scopes). `OwnerReportEmailTest` (4). (b) Overview earnings-chart **goal line** — the owner's `owner_monthly_target` (#56, the SAME model the Bookings ring reads), scaled into the chart, shown only when set. `OwnerDashboardViewTest` +1. (c) Insights **vs-last-year** occupancy delta — the same window one year back via `PerformanceMetrics::monthlyTrendForOwnerUnits`, matched by month-of-year, a ▲/▼ delta shown ONLY where the prior-year month genuinely had occupancy (omitted, never fabricated, for a month with no history); a bounded prior-year demo seed (apt occupancy only, **NOT** ledger-attributed → demo Balance/statements unchanged). `OwnerAnalyticsTest` +1 (delta appears only with prior-year data).

**Honesty holds (documented refusals, not stubs):** per-user display currency (no FX) · per-user timezone (inert here) · Reports "PDF pack" (bundling deferred). **Nothing money/auth/isolation changed** — every write stays controller-routed + capability-gated + audited; every figure reconciles (#17); owner scope + guest name-only hold (#28).

---

## 2026-07-29 — OWNER COMPLETENESS SWEEP · WAVE B (per-page completeness) — BUILT & SHIPPED (Invariants #1–66)

Wave B consumes the Wave-A shared backends (no duplication, anti-drift) + builds each page's remaining 🟢 items. Six gates, per-gate commits; every UI-visible piece browser-verified; existing suite green throughout; reconciling (#17); owner-scoped (#28); controller writes capability-gated (#20) + audited.

- **GATE 1 — My Units** (`cc48d53`): the action-center now fully consumes `owner_attention_states` — per-item **Mark-read** + a Read tag + dimmed rows, **BULK** Mark-all-read / Snooze-all (over `OwnerAttention::currentSignals`, genuine current signals only, CAP_ACTIONS + audited), and a **Status filter** (client-side by tone; a per-unit filter honestly omitted — the real signals are account-level). The Edit-pricing card gains the **weekend-uplift% + min-stay editor** (consume Wave-A `setRateWeekendAware`) with a live Fri/Sat preview, prefilled from the real plan + restriction. `OwnerAttentionStateTest` +3, `OwnerRatesTest` +1.
- **GATE 2 — Unit Detail** (`0d8f70f`): three stale refusals now REAL — **weekend uplift** (reads `weekend_uplift_pct`; editor writes it), a **richer day-detail** popover (booked day → guest NAME-only #28 via `GuestName` + stay + owner net; blocked → reason), and a **read-only cleaning schedule** from the real `HousekeepingTask` (date/type/status; the owner sees but doesn't manage; never a fake cleaner). The stricter "no guest name" self-precedent relaxed to #28 name-only (consistent with Block Calendar). `OwnerUnitDetailViewTest` 10→12.
- **GATE 3 — Rates page** (`9cd8b95`): the owner's pricing home now edits **nightly + weekend-uplift% + min-stay** in one manage view (columns + editor + a real derived Fri/Sat preview + prefill), consuming Wave-A. The design's Base+Weekend tiers are now real; Peak/proposals/guardrails stay honestly unmodelled. **Latent bug closed:** owner-set min-stays are keyed by `room_type_id` (setRestriction) but OwnerRates AND OwnerUnitDetail read them by `rate_plan_id` → never showed; both fixed. `OwnerRatesTest` +1.
- **GATE 4 — Security (3 real features)**: **(4a `9efd507`) Trusted devices** (skip-2FA-from-here) — `trusted_devices` (hash-only bearer cookie, user-bound, 30-day expiry, revocable); a live trust skips the 2FA challenge; Security-page list + remove (re-auth). Mutation-proven (`TrustedDeviceTest` 5). **(4b `b47f75b`) Login approval** — opt-in `require_login_approval` + `login_approvals`; a sign-in from an untrusted device is held PENDING + a signed-link approval email; completes only once approved (single-use); chains to 2FA if also on; a trusted device skips it; owner toggle (re-auth). Mutation-proven (`LoginApprovalTest` 7). **(4c `2274367`) Passkeys (WebAuthn)** on **web-auth/webauthn-lib ^5.3** (founder-approved dep) — `passkeys` (serialized `PublicKeyCredentialSource`, no secret); register (resident/discoverable + UV) + verify + store; usernameless assertion sign-in that logs in ONLY on a genuine assertion; any malformed/forged input → null (never 500/login); Security-page register/list/remove + a login-page passkey button. **The full crypto ceremony is proven END-TO-END in Chromium via a CDP virtual authenticator** (register → log out → passwordless sign-in → landed authenticated); `PasskeyTest` (5) locks the unit-testable parts + boundaries. `vy_td` cookie exempt from encryption (bearer token). 32 auth tests green.
- **GATE 5 — Team export watermark + download log** (`7b4032f`): the security note's "exports are watermarked with who downloaded them" made **TRUE** — `export_downloads` + the ONE `ExportAudit::stamp()` (records the download AND returns the watermark line, so file + log can't drift); `PdfService` stamps a downloader footer; all **9** owner export controllers wired (PDF footer / CSV `# Downloaded by …` comment row); Team page shows a real owner-scoped **download log**. `ExportWatermarkTest` (4); 99 export tests green.
- **GATE 6 — Settings + Onboarding** (`27c37e1`): **quiet hours** (real) — `users.quiet_hours_*` + the shared `QuietHours` window rule; `FeedNotification::via` holds routine EMAIL during the window (in-app kept, always-on security exempt); saved from the notification-prefs form. **THEME kept honest** (no dark palette; a stored-but-uneffective theme would be a fake, #39 — deferred not stubbed). **Onboarding** — `/owner/welcome` (the #66 IBAN-less thin flow): real units + the REAL commission rate + a certified Payout-Method hand-off (NO IBAN, NO micro-deposit) + notification link + honest done-state. `QuietHoursTest` (4), `OwnerWelcomeTest` (2).

**deps added (founder-approved):** web-auth/webauthn-lib ^5.3 (+ spomky/symfony transitive) for passkeys. **Honesty held throughout:** no fabricated per-unit attention, no fake cleaner, no fake trusted/approved/passkey (all validator- or store-backed), no watermark-that-isn't-applied, no IBAN capture / micro-deposit, no fake dark theme. Every security feature mutation-proven; passkeys crypto browser-proven with a virtual authenticator. Suite green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean.

---

## 2026-07-29 — OWNER COMPLETENESS SWEEP · WAVE C (🟠 founder-approved product-decision features) — BUILT & SHIPPED (Invariants #1–70)

Wave C is the founder-approved 🟠 tier of `docs/OWNER-COMPLETENESS-SWEEP.md` — two real **approval STATE MACHINES** + real owner control + a real money bucket. ⚠️ **These affect REAL money, so full money-path discipline throughout:** every transition a controller POST, capability-gated (#20) + audited; every money effect mutation-proven (approve → money moves + reconciles; pending/decline → no money effect); reconciles to the cent (#17); owner-scoped + guest name-only (#28); nothing fabricated (#39); reuse the certified services + the Wave-A attention store (anti-drift). Four gates, per-gate commits; existing suite green throughout; **new invariants #67–70.**

- **GATE 1 — RATE-PROPOSALS state machine (#67)** (`445e81b`): a real operator→owner proposal entity — `rate_proposals` (owner-scoped, exclusive-owned room type only, snapshots current + proposed nightly/weekend-uplift/min-stay, `state` pending→approved|declined|withdrawn, `decided_at/by`). Every transition a capability-gated controller POST + audit + domain event: the **operator PROPOSES** (`rates.manage`, on the Owner Detail page) → pending; the **owner APPROVES** (`OwnerRateProposalController`, CAP_ACTIONS) → applies through the certified `OwnerRateService::apply` so the rate **really changes** (mutation-proven), sets approved; **DECLINE** leaves the rate untouched; the operator **WITHDRAWS** a pending one. A pending proposal is a REAL Approve/Decline **attention item** (extended `OwnerAttention` with an optional `actions` list — the ONE shared feed now drives both proposals + expenses on My Units + Overview) and the **Insights** "review proposals" link points at the real pending count. Closes 3 refused surfaces (proposal entity / My Units action / Insights link). `RateProposalTest` (6, money-effect proven).
- **GATE 2 — EXPENSES-APPROVAL state machine (#68 — ⚠️ changes the deduction model)** (`565fd58`): `owner_expenses.approval_state` pending→approved|queried (+ `decided_at/by`, `queried_note`). An owner expense is now **CREATED pending and NOT charged** — a pending deductible posts **NO ledger line**, so it does **not** reduce net (`recordExpense` gates the `postExpense` behind approval + an `approve_now` flag). The **owner APPROVES** (`OwnerExpenseApprovalController`, CAP_ACTIONS) → the debit line posts → net drops → **reconciles #17**; **QUERY** posts nothing and opens the real owner↔operator relay (`InboxService`, name-only #28). ⚠️ **#17 proven in BOTH states** (pending: net unchanged, no line; approved: net −fee + line; `ExpenseApprovalTest` 5). A real **"Needs your approval"** section + attention item + KPIs ("Awaiting your approval" = pending; "Charged to payouts" = approved only). **Maintenance-sourced** owner costs stay auto-charged (`MaintenanceService` passes `approve_now`), preserving the certified maintenance→ledger→statement path. No auto-approve timer invented (#39). 13 existing test files updated for the new default (all money tests green).
- **GATE 3 — OWNER-CONTROLLED UNIT PAUSE/RESUME (#69)** (`90e80c5`): a real owner pause on their OWN units — `OwnerUnitStatusService` toggles `units.is_active` + **recomputes availability** over a 365-day window through the certified `AvailabilityService`, so a paused unit's capacity is genuinely removed and it **stops taking new bookings** — mutation-proven (`OwnerUnitStatusTest`: a new booking throws `InsufficientAvailabilityException` while paused, succeeds again on resume; existing stays untouched #7). Owner-scoped (`Unit` BelongsToOwner findOrFail 404s cross-owner), CAP_ACTIONS controller POST (#20) + audited. Toggle wired on My Units + Unit Detail (Active/Paused badge + Pause/Resume). The **owner weekend-uplift** half of this gate was already first-class from Wave A/B (`OwnerRateService` via `owner.rates.apply`) — confirmed, not rebuilt.
- **GATE 4 — STATEMENT "Payment & ops fees" bucket (#70)** (`30d3ae3`): a new `payment_fee` owner-ledger entry_type + `owner_statements.payment_fee_minor` bucket + "Payment & ops fees" statement line — the signed sum of a period's payment/ops fee ledger lines (card-processing / platform-ops fees), distinct from management commission + OTA cost, **included in `net_source_minor` by construction** so gross − commission − fees − refund == net holds to the cent on the page AND the PDF (both iterate the same real lines). **NEVER fabricated (#39):** no writer posts `payment_fee` lines yet (the deferred live-payment seam), so the bucket is a genuine €0 and the line is **omitted** by the existing omit-empty rule. Proven BOTH states (`OwnerStatementTest`: a real fee projects + reconciles + surfaces the labelled line; no fee → line absent). `OwnerTaxService::annualSummary` includes the new bucket so gross + deductions == net stays exact. **Sep demo net €885.60 unchanged — nothing disturbed.**

**Honesty held (refusals, not stubs):** no auto-approve countdown, no fabricated fee/proposal/approval, the pause is a real availability effect not a label. **Money discipline held:** every transition controller-routed + capability-gated + audited; #17 reconciles in every state (incl. both expense-approval states); owner scope + name-only (#28). Suite **1161 / 8989** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; pause/resume + statement breakdown browser-verified.

---

## 2026-07-29 — AUTOMATED PAYOUT ENGINE (⚠️ REAL MONEY seam · TEST-MODE-only build) — BUILT & SHIPPED (Invariant #71)

`docs/PAYOUT-ENGINE-DESIGN.md` is the authoritative design (PHASE 0 investigated + approved, then built phase-by-phase). The engine replaces the honest "payouts handled by your agency" (Option A) with a REAL scheduled engine running in **TEST MODE** — clearly labelled not-yet-live until the founder's separate cutover (`STRIPE-PRODUCTION.md` §3b). **⚠️ Real money NEVER moves in this build** (`PayoutEngineMode` = `test`; the manual rail records or the fake Connect client simulates; `StripeSafetyGuard` forbids a live key outside production). The investigation's decisive finding: nearly every payout PRIMITIVE already existed + was certified (`OwnerPayoutService` create/pay/confirm/retry/reverse, the `StripeConnectPayoutRail`, the Connect webhook, the double-transfer guard, the tax-hold) — so the engine ORCHESTRATES that core and never reimplements amount/transfer/state logic (anti-drift). The only real gap was a scheduled, preference-driven run + a persisted hold.

- **PHASE 1 — run model + state machine (`a346d0d`):** `payout_runs` (platform-level audit + idempotency record) + `PayoutRun`; a new persisted **`held`** payout status (the tax-hold becomes a real, visible, auto-releasing state, not just a thrown exception) + `payout_run_id`/`held_at`; `OwnerPayoutService::hold()`/`releaseHold()` (audited) + the `createFromStatement` idempotency guard widened to `pending|processing|held|paid` (so a re-run never creates a second payout for an in-flight statement); `PayoutRunService::run()` (super-admin owner selection → per-org create/pay/hold → tallied, idempotent on `run_key`); `PayoutEngineMode` (live only for a live key in production). Mutation-proven (`PayoutEngineTest`): amount == reconciling statement net (#17); a tax-incomplete owner is genuinely `held` (no money moves) then auto-releases + pays; idempotent no-double-pay; per-owner isolation; the clearing identity `Available + Held + In transit + Paid == Net` holds after a run.
- **PHASE 2 — schedule + command (`22162e0`):** `PayoutSchedule` (the ONE pure, deterministic cadence — monthly + twice-monthly on the Nth **business** day, weekly on the ISO weekday; `isDue`/`nextRunDate`, no fabricated date #39); `PayoutRunService::runScheduled()` + `dueOwnerIds()` (not paused · fires today · meets `minimum_minor` — a below-minimum owner is skipped and rolls forward, never a partial pay; a held-only owner is due whenever their cadence fires); `payouts:run` command (**inert unless `PAYOUT_ENGINE_ENABLED`** — a fresh deploy never auto-pays; `--force` for a manual test run; TEST-MODE banner) + a daily scheduler entry; `config('services.payouts.engine_enabled')`. Mutation-proven (`PayoutScheduleTest` + `PayoutEngineTest` + `RunScheduledPayoutsCommandTest`): only due owners run; paused skipped; below-min rolls forward then pays; same-day key idempotent; the config gate.
- **PHASE 3 — Stripe Connect TEST-MODE (`cf2bb83`):** the engine drives the certified Connect rail + webhook in test mode — **no engine code change** (it uses the config-bound rail), so `PAYOUTS_RAIL=stripe_connect` → a run initiates a real (test-mode) transfer → `processing` → the `transfer.created` Connect webhook confirms → `paid`. Mutation-proven end-to-end (`PayoutEngineStripeTest`, GENUINE HMAC signatures via `FakeStripeClient`): transfer to the correct owner → processing → webhook → paid; an un-connected owner fails with NO transfer, then pays once connected; a same-key re-run + a webhook replay are both idempotent (exactly one transfer). Verifying against the real Stripe test API is a manual step (documented for the founder).
- **PHASE 4 — surface + consolidate:** the Balance clearing model folds in the persisted `held` (→ On hold) + `processing` (→ In transit) states, so `Available + On hold + In transit + Paid == Net` **stays exact (#17)** with the new states (backward-compatible with the manual-path pending-hold); the Payouts **"Upcoming schedule"** placeholder is now the REAL next scheduled payout date (`PayoutSchedule::nextRunDate` from the owner's own preferences); a shared **`<x-owner.payout-test-mode>` banner** on both surfaces ("Test mode — automated payouts run in test mode; real transfers activate when your account goes live"); the hold banner + lifecycle stepper handle `held`/`processing`; **"Withdraw early" stays disabled** (no owner-initiated rail). Mutation-proven (`PayoutEngineSurfaceTest`) + browser-verified as the demo owner (held state + banner + real "5 Aug 2026 · Monthly" next date). Also fixed a **pre-existing** date-drift demo-seed collision (`0b37e65`, reproduced at HEAD) that broke `migrate:fresh --seed` once the date passed ~2026-07-28.

**Standing guarantees held throughout:** ⚠️ TEST-MODE only — real money never moves; reconciles to the cent (#17); owner-scoped (#28); every write/transition controller-routed or command + audited (#20); reuse the certified core (never reimplemented); nothing fabricated (#39); the live cutover documented for the founder, not done. Suite **1183 / 9091** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; both owner surfaces browser-verified. **Invariants #1–71.**

---

## 2026-07-29 — OAuth2 AUTHORIZATION SERVER (⚠️ SECURITY — third-party scoped access to owner/tenant data) — BUILT & SHIPPED (Invariant #72)

`docs/OAUTH-DESIGN.md` is the authoritative design (PHASE 0 investigated + approved, then built phase-by-phase). Makes the owner Security page's "Connected apps" (an honest "Coming soon" pill) REAL: a third-party app requests SCOPED access on an owner's behalf, the owner authorizes/revokes via a real consent screen, and the API is **tenant + scope double-gated**. **Laravel Passport v13.7.5** (resolved cleanly on Laravel 13; the dry-run de-risked it). **Fully additive — the `api` guard is only ever invoked explicitly (`auth:api`); the three session realms + default `web` guard are untouched** (the green suite is the proof). It ADDS a third gate (the token scope) on top of the certified `OwnerScope`/`OrganizationScope`/`OwnerTeamRole` — never a new isolation mechanism.

- **PHASE 1 — Passport + client/scope model (`70c0864`):** installed Passport (ULID `user_id` migrations — the User PK is a ULID; OAuth signing keys generated + **gitignored**, the private key never committed); the `api` (passport) guard; `HasApiTokens` + `implements OAuthenticatable` on `User`; **`OAuthScopes`** — the ONE least-privilege, READ-FIRST catalog (`read:bookings`/`read:statements`/`read:units`), each mapped to a real owner capability (`calendar.view`/`financials.view`) — the "no over-grant" map; `OAuthClientRegistrar` + the `oauth:register-client` command (**platform-admin registration** — no open self-serve, an abuse surface we avoid); a clearly-labelled demo example client. No write scopes / password / client-credentials grant (least surface). `OAuthClientTest` (5).
- **PHASE 2 — authorization-code + PKCE + consent (`234236c`):** the **branded consent screen** (`resources/views/oauth/consent.blade.php` via `Passport::authorizationView`) — the owner sees EXACTLY the requested scopes (human descriptions) and must actively Authorize; **PKCE** for public clients; every token issuance **audited (#20)** by an `AccessTokenCreated` listener that resolves the actor + owner-org itself (the token exchange is session-less) and records which app + scopes (never a secret/token value). Mutation-proven (`OAuthConsentFlowTest`, 4): consent shows exactly the requested scopes; a DENIED consent issues NO token; an APPROVED consent + PKCE exchange issues a token carrying ONLY the requested scope; a WRONG PKCE verifier is rejected (400).
- **PHASE 3 — scoped, tenant-isolated API + revoke (`05d70a8`) — the security crux:** `routes/api.php` (registered via `bootstrap/app.php` `withRouting(api:)`), each owner read endpoint **TRIPLE-GATED**: `auth:api` → **`ResolveApiOwnerContext`** (the session-less token's user → owner + org, mirroring `SetOwnerScopeForPortal`; sets `TenantContext` + spatie team + `OwnerContext`; **fails closed** — 403 for a non-owner token, 403 if it straddles orgs [single-tenant by design]) → **`EnsureOwnerApiScope:<scope>`** (the token must carry the scope AND the scope must map to a capability the resolved owner ROLE holds — the "no over-grant" guarantee). The `OwnerApiController` returns only safe fields (guest NAME-only #28; never contact, door codes or Wi-Fi #33) through the certified `OwnerBookingsReader`/`OwnerPortalService`. Revoke: `OAuthConnectionService` (self-only list + revoke) + `OwnerConnectedAppController` (self-only, `current_password` re-auth, audited #20) + `owner.security.connected-apps.revoke`. **Mutation-proven both isolations fail closed** (`OAuthApiTest`, 7): owner isolation (A's token sees only A's bookings, never B's); scope isolation both ways (read:bookings → statements 403, → bookings 200); no over-grant (an accountant-role token → read:bookings 403 [lacks calendar.view], read:statements 200 [has financials.view]); non-owner 403; units never leak door codes/Wi-Fi; **REVOKE is immediate** (revoked at the DB → a fresh request rejects the same token 401); the audited controller revoke (wrong password refused).
- **PHASE 4 — Security "Connected apps" REAL + consolidate:** removed the "Connected apps" pill from the Security "Coming soon" grid and added a real card — per app: name · the exact scopes granted (pills) · authorized date · a self-only, re-authed **Revoke** (matching the trusted-devices pattern); honest empty state. `OAuthConnectedAppsViewTest` (2). Browser-verified end-to-end: the consent screen shows exactly the requested scopes (bookings + units, NOT the unrequested statements); the connected app + revoke render live.

**Standing guarantees held:** SECURITY-grade rigor — authorization-code + PKCE, least-privilege read-first scopes, owner-consented via a real consent screen showing exactly the requested scopes, revocable (immediate), audited (#20); the API double-gated (scope AND tenant+owner), both isolations mutation-proven to fail closed; a token can NEVER over-grant beyond the owner's role or cross tenants; owner/tenant-scoped (#28), name-only, no arrival secrets (#33); additive — nothing disturbed. **Note:** `composer audit` flags 10 advisories on `dompdf/dompdf` (<3.1.6) — PRE-EXISTING, unrelated to Passport; a dompdf bump is a separate change. Suite **1201/9162** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; consent + connected-apps browser-verified. **Invariants #1–72.**

---

## 2026-07-29 — VAT + WITHHOLDING TAX ENGINE (⚠️ tax figures have legal consequences) — BUILT & SHIPPED (Invariant #73)

`docs/TAX-ENGINE-DESIGN.md` is the authoritative design (PHASE 0 investigated + approved, then built phase-by-phase). Makes REAL what the Reports + Tax Center audits correctly REFUSED as fabrication (*"an owner could file on it — dangerous even 'for information only'"*). **The governing discipline:** the ENGINE is code, but the RATES are CONFIGURED by the founder per jurisdiction — **the session NEVER invents, hardcodes, or presents-as-authoritative a tax rate**; it seeds only clearly-labelled EXAMPLE rates the founder MUST replace. Every output is honestly labelled *"computed from your configured rates — verify with your accountant; not tax advice."* Unconfigured → an honest "not configured" state, never a fabricated figure. Deterministic, integer money (#17), reconciling. Owner-scoped (#28). **The key split:** VAT is INFORMATIONAL (no payout effect — the owner files it); WITHHOLDING is a REAL payout deduction.

- **PHASE 1 — the rate-configuration mechanism (`8a6b2c9`):** the `tax_rates` store (platform-level, per jurisdiction: `rate_type` accommodation_vat/service_vat/withholding · percent · effective dates · `is_example`) + `TaxRateService::rateFor` (the effective configured rate or NULL — the honest "not configured"; **prefers a real rate over an example**) + `tax:set-rate` (the platform-admin mechanism to set a REAL authoritative rate) + `TaxRate` model. DemoSeeder seeds EXAMPLE PT VAT rates ONLY (`is_example=true`, "replace"); **deliberately NO withholding rate → the demo net is unchanged.** Mutation-proven (`TaxRateConfigTest`, 5): unconfigured → null; effective-dating; real-over-example; an example is returned FLAGGED; the command sets a real rate + refuses bad input.
- **PHASE 2 — the VAT computation engine (`01a5f53`):** `OwnerVatService` — INFORMATIONAL output/input/net VAT per quarter + annual, computed from FOUNDER-CONFIGURED rates × REAL statement amounts (output = accommodation-VAT × `gross_minor` rental income; input = service-VAT × `abs(commission_minor)` agency fee; net = output − input), integer (`Money::percentage`, half-up), reconciling to gross/commission to the cent (#17). **Never touches a payout.** Honest states: not VAT-registered → `not_registered`; no rate → `not_configured`; a figure on an EXAMPLE rate → `example` (flagged "not for filing"); a real rate → `ok`. Mutation-proven (`OwnerVatTest`, 4): output 1200/input 690/net 510 from a 20000 gross + 3000 fee at 6%/23%, reconciling quarter→annual; example flagged; unconfigured/unregistered → honest.
- **PHASE 3 — the withholding engine + REAL money effect (`77e330a`):** a new `withholding` owner_ledger entry_type + owner_statement_lines line_type + `owner_statements.withholding_minor` (raw ALTERs, mirroring `payment_fee`); the `withholding` bucket threaded through `OwnerStatementService`; **`WithholdingService`** posts ONE real `withholding` DEBIT at statement finalize (computed as configured-rate × the PRE-withholding net — Σ of the period's other lines, non-circular; idempotent per owner+period; append-only). Because it's a real ledger line summed into `net_source_minor`/`net_payable_minor`, it flows automatically: `OwnerPayoutService` copies net verbatim into the payout (genuinely smaller), the `PayoutRunService` picks it up unchanged, and the Balance clearing identity holds. No rate → nothing posted (genuine €0). `OwnerTaxService.annualSummary` includes the withholding bucket + category (gross + deductions = net stays exact). Mutation-proven (`WithholdingTest`, 3): 25% on a 17000 pre-net → 4250 withheld → net 12750 → payout 12750, reconciling at the ledger + statement lines (#17); NO rate → 17000 unchanged (the other state); 10% → 15300. Certified statement/payout/tax/balance tests undisturbed (54 green); demo Sep net €885.60 unchanged.
- **PHASE 4 — surface + consolidate:** the Reports "Tax & VAT summary" (honest-deferred → REAL computed output/input/net VAT per quarter from `OwnerVatService`, honestly labelled + an "EXAMPLE rates — not for filing" warning + the honest not-registered/not-configured state) + a **VAT-return CSV** (`owner.tax.vat-return`, CAP_EXPORTS, with the honest header; 404 when unavailable) + the Tax Center withholding card (the real withheld figure when configured, else honest "None") + the annual summary/PDF. The demo owner is made VAT-registered PT (no `tax_id` → the payout tax-hold "Paused" demo intact) so the Reports VAT demos the real engine on the seeded EXAMPLE rates (flagged). `OwnerTaxSurfaceTest` (4) + updated `OwnerTaxCenterTest`/`OwnerAnalyticsTest`. Browser-verified: Reports shows computed Q3 €155.40/−€107.23/€48.17, 2026 total €270.00/−€186.30/€83.70, flagged "Based on EXAMPLE rates · not for filing · not tax advice", no fabricated "payable to [authority]".

**Standing guarantees held:** the engine is code, the rates are founder-configured (never invented/hardcoded/presented-as-authoritative #39); deterministic + integer money (#17) + reconciling; withholding reduces the net payout correctly (mutation-proven), VAT never touches a payout; unconfigured → honest "not configured", never a fabricated figure an owner could file on; every output labelled "computed from your configured rates — verify with your accountant; not tax advice"; owner-scoped (#28); additive — nothing disturbed; demo money unchanged. Suite **1217/9225** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–73.**

## 2026-07-29 — "ASK VYROBEL" SUPPORT AI (⚠️ a support-AI must NEVER fabricate) — BUILT & SHIPPED (Invariant #74)

`docs/SUPPORT-AI-DESIGN.md` is the authoritative design (PHASE 0 investigated + approved, then built phase-by-phase). Makes REAL the "Ask Vyrobel" AI that the Help audit correctly REFUSED as fabrication (`OwnerHelp.php` — *"NO fake AI"* #39). **⚠️ Load-bearing correction from the investigation:** the prompt's *"reuse the existing LlmManager (cost ledger, circuit breaker, versioned prompts, the Anthropic client)"* was FALSE — a full sweep found **no LlmManager, no LLM client, no cost ledger, no circuit breaker, no RAG, no LLM SDK**. The "AI-native" tagline was aspirational. So the LLM layer is **NET-NEW**, built the codebase's config-gated fake/real way (mirrors Stripe/Channex): **default `LLM_FAKE=true`, the live Anthropic call the founder's account-gated go-live.** **The governing discipline:** grounding is **ARCHITECTURAL, not a trusted-model hope** — a retrieval gate BEFORE any LLM call means an out-of-scope or account-specific question can never be handed a figure to fabricate; it escalates to the real relay instead.

- **PHASE 1 — grounding KB + retriever (`00ef644`):** `App\Support\Help\HelpKnowledgeBase` — the ONE shared source of the 7 real, curated FAQ articles (id·keywords·question·answer·route·label), extracted from `help.blade` so the FAQ page AND the assistant read the SAME content (anti-drift; each links a real owner screen). `HelpRetriever::retrieve` — deterministic token-overlap scoring (no embeddings; a 7-item corpus), top-k with scores, threshold `MIN_SCORE`→empty. **Prove (`HelpRetrievalTest`, 14 assertions):** 7 real articles each linking a registered route; in-scope questions retrieve the right verbatim article; out-of-scope/gibberish/empty → `[]` (the escalate signal); ranking non-increasing + capped at k.
- **PHASE 2 — the grounded assistant + guardrails + cost/circuit (`e128902`):** the LLM seam — `LlmClientInterface` → `FakeLlmClient` (deterministic, scriptable `willReturn`/`willThrow`; dev reflects ONLY the retrieved help, never open knowledge) / `AnthropicLlmClient` (Http facade, **production-only**, failures → `LlmUnavailableException`); **`LlmSafetyGuard::forceFakeOutsideProduction`** (a real key NEVER fires outside production or with no key; bound in `AppServiceProvider` like the Stripe seam); versioned `Prompts` (the grounded system prompt forbids open knowledge / invented policy-numbers / account answers); `config/services.php` `llm` block; the `llm_usage` cost ledger (`LlmCostLedger`, per-owner/day token cap, atomic increment) — reads NO owner data (#28 trivial); `AskVyrobelService` orchestrating ① empty retrieval → escalate NO-call · ② cost cap → grounded fallback · ③ circuit open → grounded fallback · ④ grounded call, any failure trips the breaker → fallback, success records spend. **Behaviour-proven (`AskVyrobelTest`, 7):** in-scope → grounded + citations (1 call); out-of-scope/empty → escalate (0 calls); cost cap → fallback (0 calls); provider failure → fallback + breaker opens (no 2nd call); ledger records; the guard forces fake outside production.
- **PHASE 3 — the real grounded Help surface + consolidate:** `owner.help.ask` (a rate-limited controller `<form>`, NOT a Livewire action — every owner write stays a controller route, so the `/livewire/update` mutation boundary is intact; a READ over generic help, no owner-data write) → `OwnerHelpAskController` → `AskVyrobelService` → PRG back with the result flashed as **PRIMITIVE data** (`AskResult::toArray()` — never the object; the `database` session driver round-trips an object back as an array, caught in the browser not the `array`-driver test — the "static checks can't see a rendered page" lesson, live). The Help hero rebuilt: a real ask box (typing also filters the FAQ live) + suggested questions + the honest note; the answer renders below with **real-source citations** + an always-present **"Message your team about this"** → the real relay (`owner.messages.start`, question prefilled) + the honest note, on EVERY path (answer/grounded-fallback/escalate). FAQ + relay + honest status retained. `OwnerHelpTest` (stale `assertDontSee('Ask Vyrobel')` updated — the feature is now real; +2 surface tests). **Browser-verified end-to-end:** in-scope → grounded answer + `/owner/payout-method` source link; out-of-scope → honest *"I don't have that in Vyrobel's help material"* + NO fabricated sources; escalate → a REAL owner↔operator thread carrying the question.

**Standing guarantees held:** NEVER fabricate — grounded (answers ONLY from the real Help KB; empty retrieval → deterministic escalate, no LLM call); guard-railed (escalate to the REAL relay always present; never invents policy/numbers/commitments/account data; reads no PII #28); cost-controlled (`llm_usage` caps per-owner/day) + circuit-breaker fallback to FAQ+relay; rate-limited; the honest "AI assistant — verify important things; message the team for account help" note. The LLM layer is NET-NEW, default fake, the live call the founder's go-live (like the Stripe live-key flip). Additive — nothing money/auth/isolation changed (the green suite is the proof). Suite **1240/9392** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–74.**

## 2026-07-29 — OWNER-REALM AUDIT FIX PASS (⚠️ SECURITY: per-owner role) — BUILT & SHIPPED (Invariant #75)

`docs/OWNER-REALM-AUDIT-REPORT.md` (the read-only audit) drove a triaged fix pass in severity order: one P1 security finding, two P2 reconciliation gaps, one P2 UI gap, and the P2/P3 doc-rot cluster. Every logic/security fix is MUTATION-PROVEN (a test that FAILS on the pre-fix code, then HOLDS after); the two reconciliation fixes are proven UNDER their breaking condition; nothing else disturbed (the green suite is the proof); money reconciles to the cent (#17).

- **GATE 1 — P1-1 CROSS-OWNER UNION-ROLE OVER-GRANT (`0dec9ad`, Invariant #75).** `SetOwnerScopeForPortal` resolved a portal user's role as the single HIGHEST role across the UNION of all their owner memberships, and `isPrimaryOwner` as a GLOBAL boolean; the OAuth API inherited it. So a user who is co-owner/primary of Owner A + a lesser role on Owner B (same tenant) acted with co-owner powers on B. **Step 1 — PROVEN** (`OwnerCrossOwnerRoleTest`, 7): the blast radius was runtime-demonstrated — approve B's expenses (302), approve/decline B's rate-proposals (302), edit B's rates (302), read B's masked IBAN (shown), manage B's team (302, primary-of-A), and read B's data via an OAuth token (B's booking present) — ALL leaked; legitimate access on A worked. **Step 2 — FIXED per-owner:** `OwnerContext` now holds a PER-OWNER role map (ownerId→role) + `primaryOwnerIds`; new `roleFor`/`canOn`/`isPrimaryOwnerOf`/`ownerIdsWithCapability`/`primaryOwnerIds`/`intersectOwners`; `can()`/`isPrimaryOwner()` kept as COARSE first-filter gates (exact for the single-owner common case) + `activatePerOwner()`. `SetOwnerScopeForPortal` + `ResolveApiOwnerContext` build the per-owner map (primary → co_owner on that owner; sub-membership → its own role; primary wins). `EnsureOwnerApiScope` narrows the token's reads to `ownerIdsWithCapability(scope cap)` via `intersectOwners` — no union inheritance. Authoritative per-owner enforcement at every sensitive write/read where the target owner is known (the coarse route gate is unchanged): expense approve/query, rate-proposal approve/decline, rate apply (`editableRoomTypes` → CAP_ACTIONS owners), masked IBAN (`OwnerNextPayout` → CAP_PAYOUT owners), team (`OwnerTeamController` + `OwnerTeam` → `primaryOwnerIds`), statement-PDF + report-email payout visibility, unit pause, block-create, bookings-target, payout-method/prefs, attention (single-item 403; bulk skips non-actionable owners). **Step 3 — PROVEN CLOSED:** the same test now blocks every leak (403/404/absent) both in the portal AND via the OAuth token, while co-owner-of-A access is preserved. 🔒 #28 owner-scoped + PER-OWNER role · #42 primary gates per-owner · OAuth isolation per-owner.
- **GATE 2 — the two reconciliation gaps (#17, `c65c856`).** **P2-1 Balance:** a negative (carried-forward) statement's stamped rows sat in Net but in none of the four payout tiles → the identity broke by the carried amount. FIX: a "Carried forward" bucket (stamped rows of statements with no active payout) → `Available + On hold + In transit + Paid + Carried forward == Net` to the cent in every state; a conditional line, shown only when non-zero. Mutation-proven (`OwnerBalanceReconciliationTest`, 2 — normal + post-lock-refund). **P2-2 Reports gross-to-net:** omitted `payment_fee` (#70) + `withholding` (#73) → the net line silently failed to add up once either posted, and disagreed with the annual summary; the `reconciles` flag was never read. FIX: payment_fee → Fees; withholding → its own "Tax withheld" line (matches `OwnerTaxService::annualSummary`); every entry_type bucketed; the flag wired into the view. Mutation-proven (`OwnerReportsReconciliationTest`, 1 — reconciles with a configured 25% withholding rate). **No money changed — the display/breakdown was corrected; both lines hidden in the demo (browser-verified no regression).**
- **GATE 3 — the UI gap + the honesty-map sweep + consolidate (`0067e36` + this).** **P2-3 My Units day-detail:** back-ported the richer popover from Unit Detail (guest NAME-only #28 + unit + stay + owner net), reusing the SAME shared value rules (`GuestName` + `OwnerLedgerAttribution::netDisplays`) — no drift; browser-verified ("Demo Booker · A2 · 30 Nov – 2 Dec 2026 · 2 nights"); the `OwnerMyUnitsViewTest` PII tripwire updated to the real #28 line (name-only shown, contact never). **P2-4 + P3 doc-rot:** the stale HONESTY-MAP comments that said "REFUSED / NO BACKEND / no invented line" for now-built features were swept to match the shipped reality (expenses approve/query, statement + PDF payment_fee/withholding lines, the payout engine, Reports VAT + email, rate-proposals, security passkeys/OAuth, the sweep-doc HARD-DEFER list) — comments/docs only, no logic change (the honesty map is load-bearing — a future build reads it).

**Standing guarantees held:** #28 owner-scoped + PER-OWNER role; #17 both reconciliation gaps hold to the cent under their breaking conditions (no money changed); #42 primary-owner gates per-owner; #20 write discipline (every owner write a gated controller route); the OAuth isolation per-owner; #39 the honesty map now accurate. Every logic/security fix mutation-proven (fails-before/holds-after). Suite **1250/9418** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; the UI-visible fixes browser-verified. **Invariants #1–75.**

## 2026-07-30 — OWNER REVIEWS & REPUTATION (exact-match rebuild + response analytics + category ratings) — BUILT & SHIPPED (Invariant #76)

`docs/OWNER-REVIEWS-AUDIT.md` is the authoritative audit (PHASE 1 decode + investigate; presented + two founder decisions taken). The owner Reviews page (`/owner/reviews`) rebuilt to the decoded "Owner Reviews" wireframe EXACTLY, on REAL owner-scoped data. **PHASE-1 finding that reframed the build:** the "agency-reply / RESPONSE system" ALREADY EXISTS and is production-shape — `Review.operator_response` + `responded_at`, `ReviewService::respond()`, the operator route `POST /reviews/{review}/respond` (`can:reviews.manage`, `owned()`-scoped, audited #20), and `ReviewList` already surfacing it with the #28 byline. So the "build" was the response ANALYTICS + the exact-design surface + the (approved) category ratings — NOT a new response model.

- **GATE 1 — the exact-design page + real aggregates + the response system surfaced (`79352c0`).** New shared `OwnerReviewSummary::forUnits` (built on `ReviewRating::publicScope` — DERIVED, never stored/fabricated #39): overall rating, **REAL response rate** (responded ÷ total) + **avg response time** (`responded_at` − `published_at`, honestly omitted when none), last-30, by-villa (per-unit count/rating + a REAL recent-vs-prior trend, "— steady" when no delta), 5★→1★ distribution (reconciles to the count). Extended `ReviewList` (shared, #28 initial-only byline): initials, the REAL reservation SOURCE (Direct/OTA/Walk-in — the Airbnb/Booking.com brand split unmodelled → honest), relative "when", villa (unit) name, per-villa filter key, and the honest PENDING state ("Awaiting a reply" for a published review with no response — never a fabricated "being drafted"). `reviews.blade` rebuilt to the wireframe (hero KPIs, by-villa, recent-reviews feed + client-side per-villa filters, rating breakdown, honest "Handled for you"), shared `x-owner.shell` + `--vy-*` + `vyFadeUp`; the operator responses render READ-ONLY ("{agency} replied {response}"); "Handled for you" is HONEST — real responses + the **OTA review-ingest account-gated** ("sync activates once those channels are connected"), **NO fabricated 24h SLA**. Flagged demo reviews across A1/A2 (real `ReviewService`, most responded + 2 awaiting, backdated `published_at`), NEVER attributed to the owner ledger (money figures unchanged). ⚠️ **RAW-BLADE TRAP caught live:** `replied@endif` / `responded@if` (a directive whose `@` follows a word char isn't compiled) left an `@if` open and swallowed 5 sections — compiled OK + `php -l` passed, but the RENDERED page was broken; fixed with ternary `{{ }}`. The "static checks can't see a rendered page" lesson, again.
- **GATE 2 — the founder-approved per-category ratings + the By-category card + the real rate link (this commit).** Founder decisions: **BUILD category ratings** + honest **"Awaiting a reply"** pending state. Added `rating_cleanliness/communication/location/value` (nullable tinyint 1–5) to `reviews`; the guest review form collects them (optional selects); `ReviewService::submit` persists them (clamped/null); `OwnerReviewSummary::categories` averages the PRESENT sub-ratings (a category no guest rated is omitted — never fabricated #39) + flags the softest. The By-category card renders the real averages + **"{softest} is your softest score — the proposed [rate changes](owner.rates) factor this in"** — the Value observation now LINKS the REAL Wave-C rate-proposals (`owner.rates`), a real observation + a real link, NOT the old refusal. Demo backfill: category ratings on the demo reviews (Value trends softest — the wireframe narrative).

**Standing guarantees held:** #28 owner-scoped (the owner's OWN unit reviews only) + guest INITIAL-only byline (never contact PII); #39 every rating/aggregate/category DERIVED from real published reviews (never stored/fabricated); the response rate/time real (or honestly omitted); the OTA review INGEST + Airbnb/Booking.com brand split honestly ACCOUNT-GATED (like OTA sync); NO fabricated SLA; #20 the operator response-write stays the existing controller route (`reviews.respond`, `can:reviews.manage`, audited) — the owner surface is READ-ONLY; the Value→rate link is REAL. Mutation-proven (`OwnerReviewsPageTest`, 5): the page renders real aggregates + responses; By-category averages + the softest note + the rate link; the operator response surfaces on the owner page end-to-end; the guest per-category sub-ratings persist. Browser-verified (2 villas · 6 reviews · 67% · 4.5★ · filter · responses + awaiting · By-category 4.8/4.8/4.8/4.6 with Value softest + the rate link). Suite **1255/9470** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–76.**

## 2026-07-30 — OWNER MESSAGES (exact-match 3-pane rebuild + rich chat features on the REAL relay) — BUILT & SHIPPED (Invariant #77)

`docs/OWNER-MESSAGES-AUDIT.md` is the authoritative audit (PHASE 1 decode + investigate; presented + three founder decisions taken: compose = per-staff/assign · read-status = BUILD the real receipt · presence/typing = honest-omit). The owner Messages page (`/owner/messages`) rebuilt to the decoded "Owner Messages" wireframe as a FULL messaging inbox on the REAL owner↔OPERATOR relay (`InboxService`) — never owner→guest; guest data name-only (#28). Two gates.

- **GATE 1 — the unified 3-pane page on the REAL relay (`c070913`).** `/owner/messages` is now ONE page (conversation rail + inline active thread pane), replacing the old split list + `/{thread}` pages — **this CLOSES the KIND-3 "separate pages" divergence** the design register carried. Picking a conversation is a GET `?c=` (so the owner-context middleware runs, #20); `owner.messages.show` resolves OWNER-SCOPED first (findOrFail → cross-owner/guest 404) THEN redirects, so the two-inbox isolation contract is preserved, not weakened to a 302. Real core wired: the owner's threads (OwnerScope on `owner_id` → a guest/cross-owner thread 404s by construction), the real staff correspondent (`author_user_id` → real name + real back-office role; compose recipients are the org's REAL back-office staff, the ASSIGNEE named until a reply lands), owner_last_read_at-derived unread (marked read on the explicit `?c` open, BEFORE the rail is computed so the badge clears same-request), search, the mirrored transcript, compose→assign (`resolveStaffAssignee` re-validates tenant + allow-list, never trusts the body), reply-send, quick-replies, honest empty states. Removed the now-orphaned `OwnerMessageThread` component + `message-thread` view (one messaging concept, no duplicate). Presence + typing OMITTED.
- **GATE 2 — the six rich features, all REAL + mutation-proven (`OwnerMessagesRichTest`).** Migrations (additive): `messages.reply_to_message_id` (nullable self-FK, nullOnDelete), `message_threads.operator_last_read_at` + `pinned_message_id`, new `message_reactions` (`unique(message_id,user_id,emoji)`) + `message_stars` (`unique(message_id,user_id)`), both tenant-scoped. **Reply-to** — quotes an earlier message (validated to belong to THIS thread) + the composer context banner + the quoted render. **Attachments** — polymorphic `Media` on `Message` via the Add-Unit upload pattern (generated ULID filename, `public` disk, `attachments` collection, validated type/size), image inline / file chip; a file-only message is allowed, an empty one refused. **Reactions** — `InboxService::toggleReaction` on a fixed emoji allow-list (`REACTIONS`), idempotent toggle, audited (no domain event — a per-user annotation). **Read-receipt** — `InboxService::markRead` now also sets `operator_last_read_at` when an operator opens the thread (the mirror of `owner_last_read_at`); the owner's message reads "Delivered" until then, "Read" after — **REAL, never a defaulted ✓✓** (the audit's correct original refusal is now satisfiable). **Starred** — `toggleStar`, private per-user view-state (no events, the `markReadByOwner` precedent) + the starred panel (only the viewer's stars). **Pinned** — `setPin` (shared thread state → audit + domain event), the banner + toggle-to-unpin. Every write is a controller POST (`owner.messages.{reply,react,star,pin}` + `start`), routed through `InboxService`, owner-scoped (the `{thread}` findOrFail 404s cross-owner/guest, every `message_id` re-validated against the thread).

**Standing guarantees held:** #28 owner↔OPERATOR only (the OwnerScope on `owner_id` makes a guest/cross-owner thread 404) + correspondents NAMED, guest data name-only, NEVER owner→guest; #20 every write a controller route through the single `InboxService` write path, audited (pin also emits a domain event; star/receipt are per-user view-state, no events); #39 nothing fabricated — presence dots + typing indicator + the "usually replies within an hour" SLA stay honest-OMITTED (no real-time infra), the read receipt is a REAL `operator_last_read_at`, the "Vyrobel Support/Billing" vendor desks + "Maintenance team" non-user REFUSED (recipients are real staff). Each rich feature mutation-proven (persists + reflects + owner-scoped 404 on a cross-owner thread + a cross-thread `message_id`/`reply_to` rejected). GATE 1 test 7 updated (the receipt is now REAL — "Delivered" until the operator reads, so it asserts Delivered + still no ✓✓/Read pre-read). Suite **1262/9514** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; all six browser-verified (pin banner + reply-to quote/context + reaction palette + ❤️1 chip + starred panel + ✓ Delivered receipt). **Invariants #1–77.**

## 2026-07-30 — OWNER BOOKING DETAIL (exact-match rebuild + #28 guest-contact refusal + real 18% commission + confirmation PDF) — BUILT & SHIPPED (Invariant #78)

`docs/OWNER-BOOKINGDETAIL-AUDIT.md` is the authoritative audit (PHASE 1 decode + investigate; presented + two founder decisions taken: BUILD the confirmation PDF · OMIT guest country). `/owner/bookings/{booking}` (`OwnerBookingDetail`) rebuilt to the decoded "Owner Booking Detail" wireframe EXACTLY, on the REAL owner-scoped reservation. **PHASE-1 finding that framed the build:** the page was ALREADY owner-scoped (the through-units 404 guard — a `{booking}` that doesn't touch one of THIS owner's units 404s), ALREADY refused guest email/phone/country (#28), and ALREADY used the shared `OwnerLedgerAttribution` for earnings — it was just the OLD catalogued layout. So this was a presentation rebuild on the correct backend + the two NON-NEGOTIABLE corrections the wireframe itself gets wrong + wiring the omitted sections (timeline, booking-details, "Message", a confirmation PDF).

- **⚠️ CORRECTION 1 — GUEST CONTACT REFUSED (#28).** The wireframe exposes the guest's email (`l.andersen@icloud.com`) + phone + country. The entire owner realm shows guest NAME only — an owner seeing a guest's contact is a privacy breach AND a platform-bypass/disintermediation risk. The rebuild sends the view NAME + a REAL owner-scoped "New/Repeat guest · N stays" tag ONLY (email/phone/country never reach the view or the PDF); an honest "Guest contact details are handled by your management team" line stands in; **"Message {guest}" routes to the OPERATOR relay** (`owner.messages`, owner↔operator staff) — NEVER owner→guest. Country/nationality OMITTED (founder decision: strict name-only). Proven: `OwnerBookingsTest` asserts the page (existing) AND the new PDF document never carry the contact.
- **⚠️ CORRECTION 2 — REAL 18% COMMISSION (not the wireframe's hardcoded 15%).** The wireframe hardcodes `gross * 0.15` + "commission · 15%". The earnings render the REAL ledger breakdown (a demo booking: room_revenue €540.00 − commission €97.20 [= 18%] = net €442.80) and the rate LABEL is the shared `OwnerCommissionRate` (18%). Gross − commission == net reconciles to the cent (#17). The wireframe's cleaning-collected-then-paid lines are NOT in the ledger → the real gross→net lines are rendered instead (no fabrication). New test: an 18%-agreement booking shows "18%" and NEVER "15%".
- **The rebuild (presentation + real wiring):** the identity header (avatar · name · 4-state status pill · sub), the reservation day-hero (34px check-in/out + dashed-connector nights pill + 4-col Unit/Guests/Nights/**real Nightly rate**), the earnings gross→net card, the guest card (#28), a REAL **timeline** (from `booked_at` / `payment_status` / `guest_checked_in_at` / checked-out / the real `Payout.paid_at` / `cancelled_at` — the fabricated "pre-arrival email" OMITTED, #39), the dark **net-to-you hero** with a `prefers-reduced-motion`-respecting **count-up** (presentational, ends at the REAL net) + the REAL payout badge/line, the **booking-details** card (Confirmation/Source+dot/Channel/Booked-on — **Rate plan OMITTED**, not modelled), and the **actions**.
- **🔨 CONFIRMATION PDF (built real, founder-approved).** No per-booking confirmation PDF existed (the "Booking Summary" PDF is statement-level). Built `OwnerBookingConfirmationDocument` + `pdfs/owner-booking-confirmation.blade` + `OwnerBookingConfirmationPdfController` on the certified `PdfService`/`ExportAudit` pattern: the operator letterhead + booking facts + the gross→net earnings (18%) + guest NAME only (#28). Route `owner.bookings.confirmation` in the `$capExports` group (`CAP_EXPORTS` — co-owner + accountant; a view-only sub-user refused); OWNER-SCOPED inside the controller (the IDENTICAL through-units 404 guard as the page, deliberately duplicated because the reservation carries no owner_id); watermarked + audited.

**Standing guarantees held:** #28 owner-scoped + guest NAME-ONLY — the email/phone/country refusal is the critical guard, proven on BOTH the page and the PDF (contact never in the data); "Message" is the operator relay, never owner→guest. #17 integer money, gross − commission == net reconciling, the REAL 18% rate (never 15%). #20 the only write is the gated PDF download (a controller route). #42 the PDF is `CAP_EXPORTS`-gated. #39 no fabrication — the pre-arrival-email timeline event, the un-modelled rate plan, and the cleaning-collected/paid lines that aren't in the ledger are OMITTED, never invented; the count-up is presentational and ends at the real net. Mutation-/behaviour-proven (`OwnerBookingsTest` 15→18: the real 18% label never 15%; the PDF owner-scoped + exports-gated; the PDF document name-only + reconciling). Nothing else disturbed (the green suite is the proof). Browser-verified (attributed: 18% · €442.80 · real timeline · Paid hero · name-only guest · PDF downloads; unattributed: honest "—" + "Not yet attributed"). Suite **1265/9528** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–78.**

## 2026-07-30 — OWNER NOTIFICATIONS (exact-match rebuild · real Email prefs · honest SMS gating · real urgent-bypass) — BUILT & SHIPPED (Invariant #79)

`docs/OWNER-NOTIFICATIONS-AUDIT.md` is the authoritative audit (PHASE 1 decode + investigate; presented + one founder decision: SMS = display-only, not stored). `/owner/notification-preferences` (`OwnerNotificationPreferences`) rebuilt to the decoded "Owner Notifications" wireframe EXACTLY, on the REAL `NotificationPreferences` engine — **no schema/service/controller change** (the shared engine + the operator prefs page are byte-unchanged).

- **PHASE-1 finding that framed the build:** the preference engine ALREADY exists and is real — a typed per-user × per-event × per-channel store whose `NotificationPreferences::allows()` genuinely gates delivery (called from `FeedNotification::via()` before send, mutation-proven), the owner Mailables extend `FeedNotification` (so an email-off really stops the email), and the wireframe's 5 events map EXACTLY to the real owner catalog (statement_ready, payout_sent, performance_digest, terms_changed, account_security). So this was a presentation rebuild on the real engine + honest SMS gating.
- **⚠️ EMAIL is REAL.** The per-event email toggles are stored + honored end-to-end (defaults correct — financial/security on, Performance digest off). Proven: a per-event email-off through the page POST flips `allows(...,'email')` to false.
- **⚠️ SMS is HONEST-GATED (founder decision: display-only, not stored).** SMS delivery is account-gated (no Twilio/provider — a Wave-D hard-defer). The SMS column renders in the exact wireframe style but is **disabled**, clearly labelled "not active until SMS is set up", carries **no `name`** (never submitted, never stored), and the "Where we reach you" SMS card shows the owner's phone (honest "No number on file yet" when unset) with a disabled switch — NEVER a toggle that implies a send (#39). Zero disturbance to the shared engine + the operator page. When SMS delivery is built, the column activates then.
- **⚠️ The urgent-bypass guarantee is REAL — rendered verbatim.** "We never send marketing without asking, and urgent account or payout alerts always come through even if a channel is off." `account_security` (sign-ins + bank/payout-method changes) is `always_on`: `allows()` short-circuits to true on every channel and `save()` forces it enabled — it genuinely bypasses a channel-off. There is no marketing event in the catalog. Not aspirational; already enforced.
- **The In-app column is dropped from the UI** (the portal feed always records — "statements are always kept in your portal regardless") but the **in-app pref is PRESERVED** via hidden inputs, so dropping the column never silently turns off the feed. The owner's OWN email (the auth user's — where mail routes) + phone (the owner's own `Owner.phone`) are shown — NOT a guest #28 concern. **Quiet hours** (a real shipped Wave-B feature the wireframe omits) is KEPT as a documented addition. The save is the existing self-only controller POST (#20); the wireframe's bottom-right toast fires on the real save (the PRG flash) + auto-dismisses.

**Standing guarantees held:** #20 the save is a self-only controller POST (audience from the route, never the request); #39 no fabrication — no SMS toggle that implies a send (disabled, unstored, no provider), no false urgent-guarantee (the bypass is REAL via `always_on`), no marketing event (none in the catalog); anti-drift — the ONE `NotificationPreferences` engine + the owner Mailables, no parallel store; the feed is not silently turned off (in-app preserved); the owner's own contact (not guest #28). Mutation-/behaviour-proven (`OwnerNotificationsPageTest`, 3: the exact sections + real email + honest SMS gate render; a page-POST email toggle is honored AND in-app preserved AND no sms row ever created; account_security email can't be turned off through the page). Nothing else disturbed (the green suite is the proof). Browser-verified (email toggle persisted, SMS gated + disabled, the save toast). Suite **1268/9565** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–79.**

## 2026-07-30 — INVITE OWNER (form + email) exact-match on the REAL invite flow — BUILT & SHIPPED (Invariant #80)

`docs/INVITE-OWNER-FLOW-AUDIT.md` is the authoritative audit (PHASE 1 decode of BOTH wireframes + investigate). The operator "Invite owner" modal (`Vyrobel Invite Owner.html`) + the invite EMAIL (`Owner Invite Email.html`) matched EXACTLY to their wireframes on the **REAL, certified invite flow** — no backend rebuild. **PHASE-1 finding that framed the build:** the operator→owner invite is already complete + real — `POST /app/owners/invite` (`can:users.manage`, throttled, CSRF) → `OwnerInviteService` already accepts name/email/role(owner/manager/accountant)/commission_pct/unit_ids, creates a real pending `owner_invite` (sha256 token, 7-day expiry), sends the real role-aware `OwnerInviteMail`, and `AcceptInviteController` redeems it (set password → activate → assign units + create the `ManagementAgreement` at the chosen commission → spend the single-use token → `/owner`). The wireframe's guess "owner-team roles" is NOT this flow (that's the separate owner→team invite); the real mapping is **owner → External Owner** (portal + units + commission), **manager → Hotel Manager** (operator staff), **accountant → Accountant** (operator staff).

- **GATE 1 — the FORM (`0853092`).** The `owners.blade` modal restyled to the exact wireframe (warm-glass 460px scrim + `vyModalIn`, header icon + role-aware title, segmented Role, the real tenant-scoped unit chips, the management-agreement segmented picker defaulting to the REAL **18%** via `OwnerCommissionRate`, Cancel/Send); added the exact **access-level cards** (Accountant "Read & export…" / Manager "Full operations…"); added the in-modal **"Invite sent"** confirmation rendered on the real PRG via a structured flash (`owner_invite_sent` = the real invited email + real 7-day expiry) with "Pending", "expires on {date} · resend from the owner's row", "Preview the invite email", "Back to owners". New `InviteOwnerPreviewController` renders the REAL `OwnerInviteMail` (`->render()`, transient invite + placeholder token — NEVER sends/writes), gated `can:users.manage`. Backend untouched save the additive flash. **Honest divergences (#39):** units show for the OWNER role only (the real manager/accountant are org-wide operator staff, so unit assignment doesn't apply + is dropped server-side — never a no-op control); the commission picker defaults to the real 18% (a legitimate SELECTION → the real `ManagementAgreement`, never a fabricated rate, never the wireframe mock's 15%).
- **GATE 2 — the EMAIL.** `emails/owner-invite.blade` matched to the exact wireframe (email-safe, table-based, inline CSS): the role-aware eyebrow, the **"Invited by {operator}"** line, the headline + role body, the role-aware **feature section** ("In your portal" / "What you can access" / "In the operator app"), the bulletproof **Set your password** button + the paste-able **real tokened link**, the real **expiry** + "no account is created until you set a password", and the exact **footer** (invited-by reason + the automatic-send note). `OwnerInviteMail::copyFor()` extended (eyebrow / section title / feature items / role reason / operator) — the SAME real Postmark + `MailSafetyGuard` send path; the token link → real `/invite/{token}` activation.

**Standing guarantees held:** #20 the invite is a controller POST, **operator-admin gated (`can:users.manage`)** + throttled + audited via the flash/PRG; the preview route is operator-admin-gated too. #39 no fabrication — the commission is the real chosen rate (→ `ManagementAgreement`), the invite/token/expiry/resend are real, the email genuinely sends (Postmark), the set-password link activates a real account (no account until the password is set); units shown only where they apply (owner). Anti-drift — reuse `OwnerInviteService` + `OwnerInviteMail`, never a parallel invite path; the preview renders the SAME Mailable (can't drift). Mutation-/behaviour-proven (`InviteOwnerFormTest`, 3 — the PRG flashes the real email+expiry + the sent view renders; the preview renders the real Mailable role-aware without sending/writing; non-admin refused). The certified invite suite (`OwnerInviteBackendTest`/`StaffInviteTest`/`PhaseBEndToEnd`/`PermissionEnforcement`/`OperatorInviteOwner`) stays green. Browser-verified (owner + accountant modals; the real send → real pending invite + queued email + the exact "Invite sent" view; both email variants via the preview). Suite **1271/9583** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–80.**

## 2026-07-30 — OWNER RATES + REMITTANCE ADVICE PDF (exact-match; BUILT the counter + guardrails; honest comp-set; reconciling remittance at 18%) — BUILT & SHIPPED (Invariant #81)

`docs/OWNER-RATES-REMITTANCE-AUDIT.md` is the authoritative audit (PHASE 1 decode of BOTH wireframes + investigate; presented + 4 decisions: build the counter + guardrails, omit the comp-set, keep the direct-edit alongside, omit Peak). Two gates.

- **GATE 1 — the Rates page (`e5d8adb`).** `/owner/rates` rebuilt to the wireframe's four sections on REAL owner-scoped data, keeping the shipped direct-edit. **Proposed rate changes** surfaces the REAL rate-proposals (#67) on the page (Approve/Decline reuse the certified path). 🔨 **BUILT the COUNTER** — a real `countered` state (additive `STATE_COUNTERED` + `countered_rate_minor`/`note`/`at`/`by`; `state` is a `string(20)`): owner counters (pending→countered, nothing applied) via `owner.rate-proposals.counter` (CAP_ACTIONS); the operator accepts (countered→approved, applies the owner's rate via the certified `OwnerRateService`) via `app.rate-proposals.accept-counter` (rates.manage); `withdraw()` also closes a counter. 🔨 **BUILT the GUARDRAILS** — a per-owner `rate_guardrails` store (pricing_mode / auto_approve_pct / floor / ceiling) via `owner.rates.guardrails` (CAP_ACTIONS) that GENUINELY constrains operator proposals in `RateProposalService::propose()`: below floor / above ceiling → REJECTED; within ±auto_approve_pct of current (dynamic mode) → AUTO-APPLIED immediately; else pending. Enforcement gated on an explicit saved row (absent = today's behaviour → existing proposals undisturbed); mutation-proven, never inert. **Current rates** = Base/Weekend/Occ (real; Occ via `PerformanceMetrics`); **Peak OMITTED** (no peak tier modelled, #39). **Where you sit** = the REAL average alone; the comp-set / "below market" OMITTED (no market-rate feed — honest "benchmarking coming", never fabricated #39). `OwnerRateService::applyForExclusiveOwner()` added — verifies exclusive ownership via the context-independent foreign-unit check, so the certified rate write works owner-side (approve) AND operator-side (auto-approve / accept-counter) with no security regression. `RateProposalTest` +6 (counter round-trip; owner-scoped counter 404; floor/ceiling reject; dynamic auto-approve vs pending; fixed never auto; guardrail save). Browser-verified (counter→countered, guardrails, honest where-you-sit).
- **GATE 2 — the Remittance PDF.** It ALREADY existed + reconciled — `OwnerRemittanceDocument` renders the payout's REAL `owner_statement_lines`, so the commission is already the **real 18%** (the certified statement line, never a hardcoded 15%). Matched to the wireframe by adding the real elements it honestly omitted: the **VAT/country letterhead** (`billing_company`/`billing_vat_id`/`country`, when set), the **masked IBAN** (`iban_last4` via the shared `Iban::masked`, passed CAP_PAYOUT-gated by the controller — #28, never the full number, omitted for a non-payout-capable viewer), the **RA-YYYY-MM** reference (period-derived, like BS-YYYY-MM), and the **Payment details** section. The est-settle date stays honest — `paid_on` only once genuinely paid, never a projection (#39). `OwnerRemittancePdfTest` +1. Browser-verified: Gross €540.00 − commission €97.20 (= 18%) = net €442.80, masked IBAN ···· 1000, VAT letterhead, RA-2026-04, reconciling.

**Standing guarantees held:** #28 owner-scoped + masked IBAN (CAP_PAYOUT, never full) + no guest data on either surface; #17 integer money, the remittance breakdown reconciles to the statement to the cent, the real 18% commission (never 15%); #20 the counter + guardrails + proposal decisions are controller POSTs, capability-gated (CAP_ACTIONS owner / rates.manage operator) + audited; #42 the remittance PDF is CAP_EXPORTS-gated + owner-scoped; #39 no fabrication — no fake comp-set/market-position, no fake counter (a real state that really applies), no inert guardrail (it genuinely constrains + auto-applies), no invented Peak tier, no fabricated est-date. Anti-drift — reuse `RateProposalService`/`OwnerRateService`/the statement-payout math/`OwnerNextPayout`'s masking rule. Existing certified suites undisturbed. Suite **1278/9620** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–81.**

### 2026-07-30 — OWNER AGREEMENT + AMENDMENT ACKNOWLEDGMENT exact-match (invariant #82)

`/owner/agreement` matched to the decoded `Owner Agreement.html` on the REAL `ManagementAgreement`, BUILDING what the wireframe needed (the mandate). Single gate. `docs/OWNER-AGREEMENT-AUDIT.md` is the per-section source of truth. Three founder decisions taken **Recommended**: build the real acknowledgment · build additive term/notice/renew columns · descriptive included copy + real at-cost categories.

- **The investigate — the amendment-signing flow — is BUILT REAL as an in-portal ACKNOWLEDGMENT.** New `agreement_amendments` (operator-published: version/title/summary/effective_date/`new_terms` json/status) + `agreement_amendment_acknowledgments` (the owner's real consent: who/when/version, unique-per-amendment). `AgreementAmendmentService::acknowledge()` records the consent, AUDITS it (#20, `owner.agreement.amendment_acknowledged` — WHO/version, never the changed values verbatim), and APPLIES the new terms via the **certified effective-dating**: it bounds the current portfolio agreement at `effective_date − 1` and creates a SUPERSEDING `ManagementAgreement` from `effective_date` carrying `new_terms`. Because the model's `effectiveOn` scope is date-keyed, the change becomes "in effect" automatically when the date arrives — **history is never rewritten**, no scheduler. Idempotent (unique index + a re-submit 404s as no-longer-published). **Framed "acknowledged in your portal", NEVER a fake "signed" badge; a legally-binding e-signature (identity assurance / signature certificate) is honest-deferred to real e-sig infra.** `AgreementAmendment` added to the enforced morph map (it is an audit subject).
- **The agreement is REAL.** Commission = the real `commission_pct` (18% demo) via `OwnerCommissionRate`, reconciling with the statements (#17, anti-drift). Per-villa commission via a NEW shared `App\Support\Owner\OwnerAgreementTerms::perVilla()` — resolved through the certified `ManagementAgreement::resolveFor()` (most-specific-wins), i.e. the SAME rate the attribution engine applies (anti-drift). Additive nullable `term_months`/`notice_days`/`auto_renews` on `management_agreements` (rendered honestly — row omitted when null, never a fabricated "12 months"). "What your fee covers" = descriptive standard-service copy (a generic honest service description, not a per-owner term) + the REAL owner-billed deductible expense categories (Cleaning/Maintenance in the demo — reconciling with the Expenses page). Per-villa cleaning = honest "At cost" (no per-unit rate modelled). Owner-stays / cancellations OMITTED (not modelled — never "30 nights fee-free"/"per your policy").
- **PDFs + relay.** A real agreement-summary PDF + a per-amendment PDF (`OwnerAgreementDocument` + `OwnerAgreementPdfController`, `$capExports`-gated #42, `ExportAudit`-stamped, owner-scoped explicitly; honest "summary, not a signed legal contract" footer; the amendment PDF shows the real acknowledgment record). "Message your manager" → the real `owner.messages` relay (owner→operator, never owner→guest) — a stale "Soon" removed (the relay shipped in #77).
- **Verify.** `OwnerAgreementViewTest` (7, updated to the new exact-match layout — the old "never invents a signing flow" assertion replaced by the real flow) + `OwnerAgreementAmendmentTest` (5, new): acknowledgment records/audits/applies + supersedes via effective-dating (today stays 18%, on/after the effective date 17%); idempotent (records once); owner-scoped 404 (a co-owner can't acknowledge another owner's amendment); honest framing + the relay; the agreement + amendment PDFs download. Browser-verified as the demo owner: the page exact-matches (active agreement, amendment, fee-covers, terms-by-villa, key-terms, documents, change-terms), Preview shows the real 17% change, acknowledge → a real "Acknowledged by Demo Owner on 30 Jul 2026" record + the superseding 17% agreement "Starts 1 Oct 2026" in Agreement history (current stays 18%).

**Standing guarantees held:** #28 owner-scoped explicitly (management_agreements/agreement_amendments carry no OwnerScope — fail-closed `whereIn`), no guest data on the page; #17 the commission is the ONE real rate reconciling with the statements, per-villa via the same `resolveFor`; #20 the acknowledgment is a controller POST, CAP_ACTIONS-gated + audited; #42 the PDFs CAP_EXPORTS-gated; #39 no fabrication — no fake "signed" badge (a real acknowledgment record), no invented term/cleaning-rate/owner-stays/inclusion, no e-signature claim. Anti-drift — reuse `ManagementAgreement`/`OwnerCommissionRate`/the certified effective-dating/the PDF pattern/the relay; the certified attribution engine untouched (the new columns are additive, ignored by it). Existing certified suites undisturbed. Suite **1283/9649** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–82.**

### 2026-07-30 — 5 TRANSACTIONAL EMAILS exact-match (real Mailables on real triggers) (invariant #83)

The 5 guest/account transactional emails (pre-arrival · payment-reminder · refund · review-request · verify) matched to their decoded wireframes as REAL Mailables on real triggers. All 5 Mailables + templates already existed (basic Phase-3 versions) — this REUSED/EXTENDED them (anti-drift: the shared email-safe `emails/layout.blade.php` + the bulletproof-button pattern), matching the designs + wiring the real data + the one still-missing trigger. `docs/TRANSACTIONAL-EMAILS-AUDIT.md` is the per-email source of truth. Two founder decisions taken **Recommended** (verify copy at the real 1-hour expiry; build the `?rating` star deep-link).

- **Triggers — mostly already real, one built.** Refund fires on `payment.refunded`; review-request on `reservation.checked_out` (real `guest.review` link); payment-reminder from the real daily `emails:send-payment-reminders` command; verify from the booking-tied claim flow (`GuestAccountService::sendClaimLink`). **BUILT the one deferred trigger:** `emails:send-pre-arrival` (a new scheduled command mirroring `SendPaymentReminders`, sending the day the arrival window opens = 48h before check-in) + a daily schedule. No email claims a trigger it lacks.
- **⚠️ The pre-arrival door code + wifi are REAL + GUEST-ONLY — the #33 owner-perimeter is untouched.** #33 forbids exposing arrival secrets to the OWNER, NOT to the guest whose stay it is. The Mailable wires the shared `ArrivalPack::for()` (the SAME resolver the Arrival Info screen uses — no drift), computed at the 48h send moment so the code/wifi are released (real) at send time and withheld (never fabricated) out-of-window or for a cancelled stay; delivery is to the lead guest's OWN email only (`SendGuestTransactionalEmails::sendToGuest`), no owner/operator surface. Mutation-proven: the command sends 48h out with the real code, guest-only (one recipient, no cc/bcc), and withholds the code out-of-window.
- **Amounts reconcile (#17), nothing fabricated (#39).** Payment-reminder shows Total / Paid / Balance from one `FolioTotals` read (Total − Paid == Balance, proven); the pre-arrival balance line shows only when money is genuinely owed; the payment link is the real `guest.payments`. Refund shows the real `amount_minor` + a status timeline (Requested/Processed = the real `processed_at`; Arriving = an honest range) — HONEST destination "your original payment method" (Payment stores no `card_last4` — #28, never a fabricated masked card), reason only when a real `cancellation_reason` is set, ETA a range not a guaranteed date.
- **Review-request** — the star row deep-links each star to `guest.review?rating=N`; `GuestReviewController::show` reads the bounded 1–5 param and pre-selects that star ("already halfway done"), ignored once a review exists. "We only ask once" is real (one review per stay — DB `unique(reservation_id)`). Honest byline, no fabricated named host.
- **Verify** — the copy states the **REAL 1-hour** signed-link expiry (never the wireframe's 24h) via a passed `$expiresLabel`; the **welcome-credit teaser is OMITTED** — there is no welcome-credit feature (the guest wallet `GuestCreditEntry` has no signup/welcome grant type), so nothing is fabricated (the verify itself is fully real).

**Standing guarantees held:** #17 balance/refund reconcile (real FolioTotals / amount_minor); #28 no card leak (none stored; name-only guest); #33 arrival secrets guest-only (owner-perimeter intact — the email → the guest's own address, no owner/operator surface); #39 no fabrication (no fake balance/refund/card/reason/credit/trigger/named-host). Anti-drift — reuse `ArrivalPack`/`FolioTotals`/`MoneyFormatter`/`GuestName`/the review + claim backends/the shared email layout. Nothing existing disturbed (the 5 Mailables' triggers that were already live are byte-unchanged; only the pre-arrival trigger was added). `GuestEmailTest` +7, `GuestReviewTest` +1; all 5 browser-preview-verified. Suite **1290/9684** green; PHPStan L5 (0); Pint clean; raw-blade guard clean; `migrate:fresh --seed` clean (no schema change). **Invariants #1–83.**

### 2026-07-30 — OPERATOR DASHBOARD + CALENDAR exact-match + the cross-cutting READ-ONLY mode (invariant #84)

The operator Dashboard (`/app/dashboard`) + Calendar (`/app/calendar`) matched to their wireframes on REAL tenant-scoped data, BUILDING what was missing + establishing the cross-cutting read-only mode. The operator realm + shell already existed — this REUSED/EXTENDED them (anti-drift). `docs/OPERATOR-DASHBOARD-CALENDAR-AUDIT.md` is the per-element source of truth. Two founder decisions taken **Recommended** (real additive `paused_until` + shared gate; inline quick new-reservation via the certified controller).

- **The cross-cutting READ-ONLY / "workspace paused" mode.** No real paused state existed (subscriptions are trialing/active/lapsed; lapsed → hard paywall). Built a REAL, additive, ORTHOGONAL pause: a nullable `organizations.paused_until` + a shared `App\Support\Operator\OperatorWorkspace` gate (`isReadOnly`/`resumeLabel`), read ONCE and consumed by the shell banner (shell-wide → every operator page) AND every operator write. Real + honest (unset in the demo → no banner, nothing is paused) + **orthogonal to the subscription paywall** (`EnsureSubscriptionActive` untouched; a paused org is still trialing/active so it stays IN `/app` read-only rather than being redirected). A past pause is never read-only (no spurious lock). The Billing subsystem/admin sets it; browser-verified by setting it. **The write-gate is enforced at the SINGLE certified write path** (`ReservationCreateController`), so both the calendar quick-panel AND the full form refuse bookings when paused.
- **Dashboard** — matched the design + wired the previously-missing REAL sections: KPIs (Arrivals · Departures · Occupancy tonight +Δ vs yesterday · Revenue today · **Open work orders** + urgent from `MaintenanceTicket`); **Today's tasks** (real derived — urgent/open work orders + rooms to clean + arrivals to check in; honest "all caught up"); **Occupancy · next 7 days** (real per-night % from the certified spine — replaced the stale "Not yet available" stub); **ADR** (`PerformanceMetrics::forProperties`); **Maintenance** (real distinct out-of-order units + the real nightly rate on hold Σ from `RateCalendar`, honest-omit when unpriced #39); **Channels** (HONEST — real `channels.status`/`last_synced_at`; "Synced" ONLY when genuinely connected, else "Not connected" — never a fabricated "Synced"). All existing dashboard data (occupancy/arrivals/departures/housekeeping/revenue) kept + reused; tenant-scoped (PropertyContext read set); guest data name-only (the operator is the manager, existing convention).
- **Calendar** — the "Calendar" nav already pointed at the real rack (`ReservationRack`); added the canonical `app.calendar` route (`/rack` kept as an alias for existing deep-links). Real **room-type filter** (GET). **The inline NEW RESERVATION is REAL** — a two-phase RMB-safe flow (#20): the operator enters guest + stay (a GET "Check rack") → the REAL bookable units from the certified `UnitAvailabilityService` (with the real stay total) → each unit's **Confirm** is a POST to the certified `ReservationCreateController` → `AssignmentService::createBooking` (row-lock **non-overlap** — the SAME engine the owner block calendar's `blockInventory` uses) → a real reservation + real/matched guest profile + folio + the real `BookingConfirmationMail`. "Open the full form →" links to the existing full reservation form. Read-only respected (the affordance hidden + the certified write guarded). OTA cells honest (real `source`); out-of-order reflected in the availability spine; drag-to-reschedule stays honestly deferred.

**Standing guarantees held:** operator/tenant-scoped (never owner-scoped); the certified `AssignmentService` reused for the new reservation (non-overlap = the SAME row-lock, mutation-proven); the read-only gate respected at the single write path; #39 no fabrication (no fake channel-sync/Synced, occupancy, ADR, on-hold, or reservation). Anti-drift — reuse `PropertyOccupancy`/`PerformanceMetrics`/`FrontDeskService`/`HousekeepingTask`/`MaintenanceTicket`/`AvailabilityService`/`ReservationRack`/`ReservationCreateController`/`UnitAvailabilityService`/`BookingConfirmationMail`. Nothing existing disturbed (the owner realm shares these models — the green suite proves it). `OperatorDashboardViewTest` 3→5, `OperatorRackViewTest` 4→6; both pages browser-verified (a real reservation R-YWME7DTF created via the calendar's Confirm; the read-only banner on pause). Suite **1294/9709** green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–84.**

### 2026-07-30 — OPERATOR CHANNELS + BILLING exact-match (honest OTA gating · real per-unit subscription · real pause/cancel→read-only) (invariant #85)

The operator Channels (`/app/channels`, NEW) + Billing (`/app/billing`) pages matched to their wireframes. Both are heavily gated; `docs/OPERATOR-CHANNELS-BILLING-AUDIT.md` is the per-element source of truth. Two founder decisions taken **Recommended**: Channels honest-gated (no Channex accounts); Billing per-unit placeholder pricing.

- **CHANNELS — honest OTA gating (GATE 1).** The whole page is OTA sync, which is ACCOUNT-GATED (no real Channex integration — the `ChannexClient` throws without a partner key; the fake client is bound; nothing reaches a real OTA). New `OperatorChannels` component + `app.channels` route, **Pro feature-gated** (`EnsurePlanFeature:channels` → an Essential org gets the honest feature-locked page); the nav "Channels" item flipped from the null placeholder to the live link for a Pro org (Essential keeps the Pro lock). The DESIGN is matched faithfully but: the **Direct** channel is REAL (Vyrobel's storefront, `reservations.source='direct'`, always live, 0% commission); a connected OTA channel's STATUS is real (a real `channels.status` toggle) but live two-way sync/import is honestly gated ("mapped & ready — live sync activates with your Channex account") — **NEVER a fabricated "Synced / N imported / last sync 3 min ago"**; the booking-mix + OTA/Direct-share stats are REAL by `reservations.source` (via `OwnerBookingsReader::sourceLabel`); the wireframe's fabricated "Expedia disconnected · 3 bookings waiting to import" alert is replaced by the honest "Channel sync — setup required · Vyrobel never fabricates a sync it can't run." `OperatorChannelsTest` (3).
- **BILLING — real per-unit subscription + honest card/invoices (GATE 2).** The wireframe's active-subscription detail was almost entirely unbacked (`Subscription` has real STATE only; no per-unit qty, no card model, no subscription-invoice model, no VAT). Built: a new shared `SubscriptionPricing` computes the org's REAL active-unit count × a PLACEHOLDER per-unit price (`config/billing.php` `per_unit_minor`=€9.00) × a placeholder VAT (23%) → the reconciling `/mo incl VAT` figure (net + VAT == gross, #17), flagged "test mode · placeholder pricing," **no real charge** (`StripeSafetyGuard`/account-gated). **HONEST-GATED** (never fabricated #39): "No card on file" (the card is entered on Stripe's hosted page, account-gated — never a fake ····4242); the real trial invoice (Free €0.00) + honest "no invoice is invented" (no fake "Paid €66.42"). Real: the plan/status/period, "What you're paying for" counts (units/team/channels), and the editable billing details (`billing_company`/`billing_vat_id`/`billing_email` via `saveCompany`). The management view shows only for an **active** subscription (a trialing/lapsed org keeps the paywall/tier-catalog — split keyed on `active`). Browser: **8 units × €9 = €72 + €16.56 VAT = €88.56/mo incl 23% VAT**.
- **THE REAL PAUSE/CANCEL → READ-ONLY STATE MACHINE (the cross-cutting mode's real backend).** New `SubscriptionLifecycleService` + `organizations.readonly_from` (nullable) + the extended `OperatorWorkspace` gate + `BillingActionController` pause/cancel/resume routes (`billing.manage`, audited #20). **PAUSE** (up to 3 months) sets `paused_until = now+N` → the workspace is read-only NOW (the shared gate the Dashboard/Calendar/certified-write already consume — the wireframe's "Pause instead"). **CANCEL** sets `readonly_from = current_period_end` → full access until the paid-through date, then read-only indefinitely (data kept). **RESUME** clears both. **Orthogonal to the paywall** (`EnsureSubscriptionActive` untouched — the subscription `status` is never changed, so no BillingAccessGate behaviour changes and no real charge moves). **Mutation-proven** (`OperatorBillingLifecycleTest`): pause → `OperatorWorkspace::isReadOnly()` true → a new booking POST is REFUSED (nothing created); resume → live again; cancel schedules read-only from the paid-through date (proven both sides — future date = full access, past date = read-only). Browser-proven end-to-end: Pause 3 months on Billing → the Dashboard shows "Workspace paused — read-only until 30 Oct."

**Standing guarantees held:** #17 the subscription figure reconciles (real unit count × per-unit × VAT); #39 no fabrication (no fake channel-sync/Synced/import/OTA-booking; no fake card/paid-invoice/charge); the pause genuinely gates read-only (mutation-proven, reusing the certified gate); the test-mode boundary (`StripeSafetyGuard` — no real charge until production go-live). Anti-drift — reuse the Stripe/billing infra, the `OperatorWorkspace` gate, `ChannelConnectionService`, `OwnerBookingsReader::sourceLabel`. Existing billing/dashboard suites undisturbed. Suite **1301/9748** green; PHPStan L5 (0); Pint clean; raw-blade guard clean; `migrate:fresh --seed` clean. **Invariants #1–85.**

### 2026-07-30 — OPERATOR EMAIL TEMPLATES + EDITOR exact-match — a REAL customization store that drives the #83 sends (invariant #86)

The operator "Guest emails" list (`/app/email-templates`) + a NEW single-template **Editor** matched to their wireframes (`Operator Email Templates.html` · `Operator Email Editor.html`) EXACTLY, as a **real per-org template-customization manager that GENUINELY drives the #83 guest Mailables** — mutation-proven + browser-proven end-to-end. The old page was a static catalog with an honest "Editing · Soon"; this is the genuine build. `docs/OPERATOR-EMAIL-TEMPLATES-AUDIT.md` is the per-element source of truth. Both founder decisions taken **Recommended**: **A** list stat = honest (status/trigger, no fabricated open-rate — no send-tracking exists); **B** Test-send = a real send to the operator's own email via `MailSafetyGuard`.

- **The store (`email_templates`).** Per-org, per-`template_key`: `enabled` + subject/heading/message/button + `blocks` (json toggle map) + `updated_by`; unique(org, key). `EmailTemplate` model (BelongsToOrganization; morph alias `email_template`). `EmailTemplateCatalog` holds the 5 templates' DEFAULTS (= the current #83 strings, with `{merge}` placeholders) + each one's real trigger/channel/toggleable-blocks/vars. `EmailTemplateResolver::for($org,$key,$vars)` → `ResolvedEmailTemplate` (effective = stored-override ?? catalog-default, merged; `block()`; `enabled()`) — read `withoutGlobalScope(OrganizationScope)` with an explicit `organization_id` predicate so a QUEUED Mailable render doesn't trip the tenant guard yet never leaks another org. `EmailTemplateService` (whitelisted upsert, empty→null, audited `email_template.updated` / `enabled` / `disabled` — records WHICH fields changed, never their values).
- **Threaded into all 5 #83 Mailables (additive).** Each `envelope()` uses the resolved (merged) subject; each `content()` passes `heading` / **`intro`** / `button` / `blocks` (⚠️ the data key is `intro`, NOT `message` — `$message` is Blade's reserved mail variable, a real bug found + fixed) + the existing data. Each blade renders `{{ $heading }}` / `{{ $intro }}` / `{{ $button }}` and wraps every toggleable block in `@if ($blocks['…'] ?? true)`. **The defaults equal the current strings byte-faithfully → an un-customized template renders identically → GuestEmailTest 15/15 unchanged.** The 3 real triggers (`SendGuestTransactionalEmails` · `SendPreArrivalEmails` · `SendPaymentReminders`) skip the send when `EmailTemplateResolver::enabled()` is false → **OFF genuinely stops that email.** `VerifyEmailMail` gained an optional `?organizationId` (it carries no reservation) so it honours per-org customization; `GuestAccountService` passes the guest's org.
- **The LIST.** `EmailTemplateController` now renders the 5 real catalog rows over the store's real `enabled`/customized state: status (Active/off), name (+ a "Customized" chip when a row exists), real trigger, channel, Preview, Edit. A real Active/off toggle POST (`EmailTemplateActionController::toggle`). Honest footer — no open/click tracking is collected, so no engagement stats are shown (decision A). Edit/toggle controls render only for a user who can submit them (`properties.manage`), so the UI never invites a 403.
- **The EDITOR (`OperatorEmailEditor`, Livewire).** Holds the draft (subject/heading/message/button/blocks) as `wire:model.live` props; the LIVE PREVIEW renders the REAL `emails.{key}` blade + layout via the shared `EmailPreviewSample` (real sample reservation, draft overlaid on `content()->with` — so **preview == send, can't drift**), shown in an isolated `<iframe srcdoc>`. Insert chips append the real merge tokens; block toggles show/hide live. SAVE is a native form POST (each input carries BOTH `wire:model.live` for the preview AND `name=` for the submit) → `EmailTemplateActionController::save` → `EmailTemplateService` (no mutating Livewire action). A real Test-send (decision B) builds the customized Mailable over the real sample and sends to the operator's own email (MailSafetyGuard → log/Mailpit outside production). "Last edited by X · date" from `updated_by`/`updated_at`.

**Guards / honesty held.** Every write is a controller POST gated `can:properties.manage` (#30 — the same tier as Settings; **a Hotel Manager correctly gets a view-only list**, exactly like Settings, and the demo manager account reflects this) → audited service (#20); tenant-scoped via `TenantContext` (never a request id); the template key is whitelisted against the catalog (a foreign key 404s). No fabrication (#39): no fake open-rate/send-count; the signature block is the honest property/team line, never an invented named person; nothing is a mockup that saves to nothing. **Proof** — `OperatorEmailTemplatesTest` (9): a saved heading replaces the default in the REAL rendered email · a merge var resolves in the real subject · a disabled block is genuinely absent from the real render · OFF does not send on the real checkout trigger while ON does · a guarded+audited controller save · a Front-Desk save 403 · the toggle flips `enabled` · list+editor render · unknown key 404. **Browser-verified** (as a Property Owner): the live preview updated on edit, the tap-a-star block vanished on toggle-off, Save persisted, and the **independent** email preview (the real Mailable) rendered the saved heading + omitted the block — then the verification's temp user + demo template row were removed (demo back to defaults). Anti-drift — reuse the 5 #83 Mailables + `emails/layout.blade.php` + `EmailPreviewController` render path + `AuditEventRecorder` + `<x-operator.shell>`. `GuestEmailTest` + `GuestReviewText` + notifications/settings/billing suites undisturbed. PHPStan L5 (0); Pint clean; raw-blade guard clean; `migrate` clean. **Invariants #1–86.**

### 2026-07-30 — OPERATOR GUESTS + GUEST PROFILE exact-match — full CRM, GDPR bulk-delete, consent-gated marketing, prefs→ops (invariant #87)

The operator Guests list (`/app/guests`) + Guest Profile (`/app/guests/{guest}`) matched to their wireframes EXACTLY and the Wave-2 honestly-omitted promises turned REAL (reviews #76 now exist; the mandate authorised the prefs/consent/city stores). `docs/OPERATOR-GUESTS-PROFILE-AUDIT.md` is the per-element source of truth. **⚠️ #28 scope: the operator sees + edits FULL guest contact (the property manager) — the name-only rule is OWNER-surfaces-only; the perimeter test keeps front desk / reservations / inbox name-only.** Three founder decisions taken **Recommended**: consent gates a real marketing send; prefs flow to front-desk + housekeeping; CSV export built (audited).

- **The GDPR delete was already exemplary — the bulk trigger was the gap.** `Guest::anonymize()` (Wave 2) nulls PII, hard-deletes ID-doc bytes, redacts message + note bodies, sets `anonymized_at`, and KEEPS the row so reservations/folios/ledger stay intact (anonymized). GATE 1 adds **bulk** anonymize (loops the certified method), `can:guests.erase`-gated (#30, Property Owner/Group Admin — a Hotel Manager is refused, proven), typed-ERASE server-confirmed, HONEST dialog copy (the wireframe's "history removed" is corrected — financials are retained). Mutation-proven: bulk erase nulls PII AND the reservations survive pointing at the anonymized rows.
- **GATE 1 — the list.** Migration `guests.city` + `prefers_email` + `preferences` (JSON catalog; all nulled by `anonymize()`). Filters restructured to **All · In-house · Upcoming · VIP** (real stay-state via the shared `ReservationStage::scope`). Table: Guest(avatar/name/VIP/tags/email·phone) · Nationality · Stays · **Last stay** · **Lifetime(+balance due)** · Status. Search now matches name + city + an **EXACT email** via the `email_hash` blind index (partial email/phone honestly cannot — encrypted). **Bulk select** is Alpine, keyed on ULID ids so no guest PII enters any `/livewire/update` payload; each bulk form posts ids to a controller. Bar: **Message** (real `OperatorGuestMessageMail` + a manual inbox thread; a "marketing" mode SKIPS non-consented recipients — reported honestly), **Add tag** (reuses `attachTag`), **Export** (a real audited CSV — `guest.exported` records who + count, never values; **CSV-formula-injection-safe** after the automated security review, `c035998`), **Delete** (the GDPR erasure above). **Add guest** is a real create modal → `GuestService::create` (audited). Every guest write (incl. the pre-existing edit/note/tag) now respects read-only (#84 — `GuestActionController` didn't before).
- **GATE 2 — the profile.** Restructured to the wireframe: header (Message → a real guest↔operator thread-start via `InboxService`; Edit modal; Current stay) · stat strip (Stays/Nights/Avg + source split) · **stay history with a real per-stay refund line** (succeeded refund payments) · **Latest review** (reviews #76 — the guest's real latest review, stars + body + attribution; honest "No reviews yet", never a fabricated 5.0) · **dark Lifetime-value hero** (billed + open folio, reconciling #17) · **Contact** (email/phone/**city** + prefers-email/consent record) · **Preferences** (the real structured store) · Tags · Edit modal (name/email/phone/city + the 8 preference chips + VIP/prefers-email/marketing-consent) · GDPR erase.
- **"Shown to housekeeping & front desk" — the once-verified-false promise MADE TRUE.** `FrontDesk::present()` and the `HousekeepingBoard` (via the unit's arriving reservation) now surface the guest's `is_vip` + preference labels through the ONE shared `Guest::preferenceLabels()` (read by the profile, the front desk and housekeeping — anti-drift). These are operational flags, NOT contact PII — the name-only perimeter (email/phone) is unaffected (#28), asserted (no contact leak on the front desk).
- **Marketing consent genuinely gates email.** The new `GuestMarketing::canReceive()` (`marketing_consent` + not-anonymized + has-email) is the ONE marketing gate; the bulk "marketing" message skips non-consented recipients (mutation-proven — the opted-out guest gets no mail). Transactional emails (booking/refund) deliberately stay ungated (a service message the guest is owed).

**Guards / honesty held:** operator-scoped full contact (#28 owner-only name rule; perimeter green); #17 aggregates reconcile (shared `GuestSpend`/`GuestHistory`/`FolioTotals`/`ReservationStage`); #20 every CRUD + bulk delete controller-routed + audited; the delete **anonymizes-not-destroys**; consent genuinely gates marketing; read-only respected on every write; #39 no fabrication (no fake guest/aggregate/consent/review — honest empty states). Anti-drift — reuse `Guest::anonymize()`, `GuestService`, the shared aggregate helpers, `Review` (#76), `InboxService`, `SendGuestTransactionalEmails` send seam, `OperatorWorkspace`, `Guest::preferenceLabels()`. `OperatorGuestBulkTest` (8) + `OperatorGuestCrmFlowTest` (7); `OperatorGuestsListTest` + `OperatorGuestProfileTest` updated to the new design; the front-desk/reservations/inbox name-only perimeter stays green. PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; both pages + the FD/HK flow browser-verified. **Invariants #1–87.**

### 2026-07-30 — OPERATOR HOUSEKEEPING + INSIGHTS exact-match — real HK management + real-derived insights (invariant #88)

The operator Housekeeping (`/app/housekeeping`) + Insights (`/app/insights`) pages matched to their wireframes EXACTLY on REAL data, building what was missing. `docs/OPERATOR-HOUSEKEEPING-INSIGHTS-AUDIT.md` is the per-element source of truth. Both had substantial real backends already — this is match-the-design + a few genuine builds. Three founder decisions taken **Recommended**: a real `started_at` (real elapsed); real-derived insights with the Expedia/brand fabrications REFUSED; a single proof photo collection.

- **GATE 1 — Housekeeping (`964eec8`): the operator MANAGING the real `HousekeepingTask`.** The owner sees the SAME model read-only (`OwnerUnitDetail::cleanings`) — undisturbed (anti-drift). Migration `housekeeping_tasks.started_at`, set on the in_progress transition by the certified `HousekeepingService::updateTaskStatus`, so the board's **"Elapsed {m}/{exp}m" bar is REAL** (expected minutes a documented per-turnover constant — departure 45 / deep 90 / stayover 30 / arrival_prep 20). **`HousekeepingService::assignAll`** — greedy **least-load** auto-balance, waiting-guest rooms first, each assignment through the certified `assign()` (validated real-housekeeper, audited); `assign` gains a "clean next" priority bump. **Proof photos** — a real secure upload/delete reusing the `RoomTypePhotoController` media pattern (validated image content, generated filename, `public` disk, a `Media` row on the task with `collection='hk_proof'`; the task already `HasMedia`), never a fake photo. The board: **Board/List views + density** (client-persisted), **crew filter + search** (GET), the **5-status tally strip** (4 real HK statuses + real **out-of-order** from `units.condition_status`), lanes, **room cards** (unit/turnover/**urgency** [a name-free "guest arriving/waiting" derived from `reservation_units` — reservations carry no direct unit_id]/**elapsed**/action + the #87 VIP+prefs), **assign + assign-all modals**, and a **room-detail drawer** (stage pipeline, photos, action). The crew is the **real `StaffRoster::housekeepers()`** (active Membership ∩ the Housekeeping/Maintenance role) with a **computed live load** (open-task count — no fabricated availability). Every write `housekeeping.manage`-gated + read-only (#84)-respecting. **"Notified on device" softened honestly** — the task appears in the crew member's real task list; there is no device-push channel, so no fabricated notification. `OperatorHousekeepingActionsTest` (6): started_at stamped · assign+priority · assign-all balances by load · photo upload · Front-Desk 403 · read-only refuses. Browser-verified: 7 rooms auto-assigned + balanced (Rosa's load 0→7), the drawer + photo capture.
- **GATE 2 — Insights: portfolio analytics + REAL DERIVED insight cards.** Over the real `PerformanceMetrics`/`MetricSet`/`BookingCadence` (scoped by `PropertyContext::readIds()` — a real portfolio). **Range presets** (YTD/6m/Summer/Q4-on-books/Full-year). **4 KPIs** (Occupancy/RevPAR/ADR/Booking-window) with **real vs-last-year deltas** — shown ONLY where the same range one year earlier actually has data (`MetricSet::isEmpty()` gate); honestly absent otherwise (never a fabricated delta against an empty year, #39). **Occupancy-by-month** (this-year + on-the-books future months + a real LY series, LY attached only where prior-year data exists). **By-property table** (per-property Occ/ADR/RevPAR + a Group roll-up). **Forward pace by property** (next-30-days occupancy vs the same window last year; ahead/behind only where LY exists). **Real-derived "Worth a look" cards** (a new helper): the real **peak weekday** (shown at ≥80%), the real **check-out-day concentration** (shown at ≥45% over the best two consecutive days), and the real **forward on-the-books revenue** — each rendered ONLY when the real data clears its threshold. **The wireframe's Expedia "costing €4,600" card, the Booking.com/Airbnb/Expedia BRAND channel-split, and the Marina-Expedia "suggested next step" are REFUSED** — Expedia is not a connected channel and `reservations.source` has no OTA brand (an OTA stay is just `ota`), so there is NO real data; the channel mix shows the REAL sources and the Expedia slot is an honest "connect a channel to see channel-level pace" note (no invented euro figure). **"Decisions stay yours"** framing kept. (Note: "#85" in this codebase is Operator Billing, not OTA — the OTA honesty gate is the `OperatorChannels` decision; either way there is no real Expedia data.) `OperatorAnalyticsTest` 5→8 (real vs-LY where prior-year exists · the wireframe layout · Expedia refused/DontSee "costing").

**Guards / honesty held:** operator-scoped; the `HousekeepingTask` + the shared analytics reused (anti-drift — the owner HK read-only view + owner Insights undisturbed, proven by the green suite); #20 every HK write controller-routed + `housekeeping.manage` + audited; the insight cards **REAL-DERIVED-ONLY** (no fabricated insight/assignment/photo/notification, #39); read-only (#84) respected on every HK write; #17 the analytics reconcile to `FolioTotals`/the availability spine. Anti-drift — reuse `HousekeepingService`/`StaffRoster`/`RoomTypePhotoController` media pattern/`PerformanceMetrics`/`MetricSet`/`BookingCadence`/`PropertyContext`. Suite green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; both pages browser-verified. **Invariants #1–88.**

### 2026-07-31 — OPERATOR MAINTENANCE (board + costs + settings) exact-match — real SLA breach, real labour/parts split tied to owner Expenses, real technicians + auto-assign (invariant #89)

The operator Maintenance board (`/app/maintenance`), Costs report (`/app/maintenance/costs`) and Settings (`/app/maintenance/settings`) matched to their wireframes EXACTLY on REAL data, building what was missing. `docs/OPERATOR-MAINTENANCE-AUDIT.md` is the per-element source of truth (with a RESOLUTION appended at GATE close). The two hardest mandates were already certified and are reused untouched (anti-drift): **mark-out-of-order genuinely blocks selling** (the certified `setUnitCondition('out_of_service')` → availability `out_of_order` → `AssignmentService` guard — the owner-unit-pause #69 mechanism) and **costs tie to the owner-billed Expenses** (`MaintenanceService::postExpenseIfOwned` → `OwnerExpenseService::recordExpense`). Four founder decisions taken **Recommended**: (A) a real labour/parts split + awaiting-parts state; (B) PDF+CSV real / Excel honest / email-to-self real; (C) assign-to-any-active-member + honest Invite; (D) require-photo stored, enforcement deferred.

- **GATE 1 — board.** `HasMedia` on `MaintenanceTicket` + a real photo upload/delete controller (`maintenance/{id}` on the `public` disk, the `RoomTypePhotoController` pattern, collection `mt_photo`). Migration `2026_09_02_000000` — `labor_minor`/`parts_minor`/`awaiting_parts`/`part_name`/`part_eta`. The shared `App\Support\Maintenance\MaintenanceSla` (a REAL breach: `opened_at` + the priority's `sla_*_hours` target < now, only on an active ticket → breached/due_soon/on_track) drives the board's SLA badge. The shared `App\Support\Maintenance\RevenueOnHold` reuses the Dashboard `onHoldMinor` derivation (tonight's `RateCalendar` price for blocked units; 0 when unpriced). Auto-assign wired in `MaintenanceService::open()` (reads `MaintenanceSetting`, master-toggle-gated → `assignee_{category}_id`). `MaintenanceList` rebuilt as the flat work-order list + a right rail that is now REAL (Technicians / Parts-on-order / OOO hero). Detail drawer (pipeline, SLA, parts, photos, activity, Start/Resolve[labor+parts]/photo), New-WO (photos + mark-out-of-order with the "check the rack" warning), Assign modal. Tests (`MaintenanceRebuildTest`, 7): auto-assign, labour/parts reconcile + tie to owner expense, awaiting-parts, real SLA breach, photo upload, read-only refuses, **mark-out-of-order blocks a booking (throws `InsufficientAvailabilityException`, mutation-proven)**.

- **GATE 2 — Costs (`cd371c8`).** `MaintenanceCostService::report()` gains the REAL labour/parts split (`SUM(COALESCE(labor_minor, cost_minor, 0))` / `SUM(COALESCE(parts_minor, 0))` — a legacy ticket records as all-labour so labour + parts === total to the cent, #17) + `parts_on_order` + the `billable()` line items. `MaintenanceCostExportController` (csv/pdf/email) + `MaintenanceCostReportMail` + `pdfs/maintenance-costs.blade.php` + `emails/maintenance-cost-report.blade.php` — PDF report + CSV of the billable line items (both `can:maintenance.manage` + audited `maintenance.costs_exported`), Email-to-me sends the PDF to the operator's OWN address (MailSafetyGuard), Excel honestly deferred. Page rewritten to the wireframe (KPIs, spend-by-category bars, billable table + reconciling totals row, dark spend hero with the owner-billed/operator-borne split, labour-vs-parts, spend-by-month, top-units, Alpine export menu). +5 tests (split reconciles, billable === owner_expense, exports gated + audited, email real).

- **GATE 3 — Settings.** Migration `2026_09_02_000001` — `auto_assign_enabled` (bool). Wired end-to-end (service `TOGGLE_FIELDS` → controller → the Alpine switch that greys the category map when off). `MaintenanceSettingsService::technicianPanel()` — category assignees + anyone holding an open order, each with a **REAL open-order load** (a grouped count, never fabricated). The blade: master toggle, the greyed category map, the Technicians panel (with a "Manage team & roles →" link to `app.roles` — the honest Invite, no fabricated technician role/email-invite), the SLA pickers (drive the real board breach), the out-of-order policy + notify toggles with honesty corrected to reality (maintenance alerts **not dispatched yet** — stored, never sent; require-photo **not enforced yet**). +4 tests (master toggle persists + routes; toggle-off leaves unassigned; panel reports real load; settings save `maintenance.manage`-gated).

**Guards / honesty held:** operator-scoped; the `MaintenanceTicket` + `MaintenanceService` + the certified availability + owner-Expense path reused (anti-drift — the Dashboard #84 + owner Expenses undisturbed, proven by the green suite); #17 costs reconcile (labour + parts = total; the billable tie === the owner_expense, mutation-proven); #20 every write controller-routed + gated + audited; exports `maintenance.manage`-gated + audited; the **SLA is a real breach computation, never fabricated**; **mark-out-of-order genuinely blocks selling (mutation-proven)**; read-only (#84) respected on every write; #39 no fabrication (no fake work-order / OOO / cost / SLA / technician / parts / notification; Excel + require-photo enforcement + maintenance-alert dispatch shown as honestly deferred, not stubbed). **Category divergence documented (KIND-3):** the app's 5 real ticket categories (plumbing/electrical/appliance/structural/other), not the wireframe's HVAC/Furniture/etc. `MaintenanceRebuildTest` 16/16 + `MaintenanceBoardViewTest` 7/7 + `MaintenanceModuleTest` 3/3 = 26 green; full suite green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; all three pages browser-verified (board, costs + PDF download + export menu, settings + master-toggle save round-trip). **Invariants #1–89.**

### 2026-07-31 — OPERATOR MESSAGES + NEW RESERVATION + NOTIFICATION PREFERENCES exact-match — operator↔guest inbox + assign-to-staff, the full reservation wizard on the certified engine, operator prefs reused (invariants #90–92)

Three operator pages matched to their wireframes EXACTLY on REAL data, reusing/extending existing engines (never rebuilt). `docs/OPERATOR-MESSAGES-NEWRES-NOTIFS-AUDIT.md` is the per-element source of truth. Six founder decisions taken **Recommended** (A real AddOn/Tax + honest-omit · B real wallet credit · C build the real `low_rating` key · D client localStorage draft · E build the real in-app assign-notify · F suggested-replies honest-omit).

- **GATE 1 — Messages (`7c37a13`, invariant #90).** `/app/inbox` rebuilt as the unified 3-pane `OperatorInbox` on the SAME `MessageThread`/`Message` + `InboxService` (the two-page `InboxList`/`InboxThread` removed; legacy `/app/inbox/{thread}` → `?c=` redirect), mirroring the owner 3-pane. **operator↔GUEST = FULL guest data** (name + channel + reservation context + derived stay history), DISTINCT from the owner↔operator name-only relay (#77); the encrypted participant identity is never rendered (#25/#28). Rail + active thread (honest delivery incl. `failed` #24) + **Assign** (real `InboxService::assign`, `StaffRoster` role/department picker, internal "guests never see this") firing a **REAL in-app `ThreadAssignedNotification`** to the assignee (in-app only — no fake push; new `inbox.thread_assigned` dispatcher branch) + **New message** (a guest with a current/upcoming stay → start from a #83 template via `EmailTemplateResolver` → a real outbound). Read-only guard **added** to inbox writes (was ungated). Suggested replies honest-omitted. +5 `OperatorInboxComposeTest`; inbox/notification/owner-messages tests updated to the 3-pane.

- **GATE 2 — New Reservation (`ce29db1`, invariant #91).** `NewReservation`/`ReservationCreateController` extended to the 7-step wizard on the SAME certified `AssignmentService::createBooking` (Level-A/B `FOR UPDATE` non-overlap — unchanged, the #84 guarantee). The create flow now posts REAL reconciling folio lines: extras (`AddOn` via `FolioService::postAddOn`) + transfer (per-leg `AddOn`) + property taxes (`Tax` via `applyTax`) + returning-guest wallet credit (`GuestWalletService::applyToBooking`) — **room + extras + transfer − credit + tax reconciles by construction** (#17; `recompute()` derives the total from the append-only ledger; mutation-proven). The shared `ReservationExtras` (one quantity-basis definition — wizard preview + controller post can't drift). VIP + marketing consent (#86) captured on a new guest. Card stays honest "Soon" (no PAN, test-mode #45); manual cash/bank real (`payments.record`). 7-step Alpine wizard + a live ESTIMATE summary from REAL prices + localStorage draft (matches the wireframe; cleared on confirm). House rules from real `Property`/`PropertyPolicy`; door code guest-only (#33). Demo AddOns (Breakfast/Late checkout/Baby cot/Airport transfer) + a city tax seeded (flagged demo). +4 `OperatorNewReservationExtrasTest` (folio reconciles, real wallet credit spent, VIP/consent, fake extra skipped).

- **GATE 3 — Notification Preferences (this commit, invariant #92).** `/app/notification-preferences` rebuilt to the wireframe on the SHARED `NotificationPreferences` engine + `NotificationPreferenceController` (audience `operator`) — the same engine the owner page uses. Channel cards (Email real + verified; **Push + SMS HONEST-GATED** — no VAPID/web-push/device-token/provider anywhere, so display-only/disabled, NEVER a submitted field #39). The 12-event × Email/Push/SMS grid (Email real + genuinely gating delivery via `FeedNotification::via()`, mutation-proven; Push/SMS honest; the real `in_app` channel preserved via hidden inputs). Quiet hours real (`QuietHours` holds routine email; in-app unaffected; always-on security exempt). Daily digest = the honest `daily_summary` pref (stored, not dispatched — no job yet). **Built the real `low_rating` catalog key + `LowRatingNotification`** — the dispatcher's `newReview` branch fires it ONLY when the REAL rating < 4 (never fabricated), so the grid matches the wireframe. +5 `OperatorNotifPrefsGateTest`.

**Guards / honesty held:** operator-scoped (Messages FULL guest data — the manager, distinct from owner name-only #77); the certified `AssignmentService` reused (non-overlap unchanged — the SAME as #84); #17 the folio reconciles (accommodation + extras + transfer − credit + tax = total, from the append-only ledger); payment test-mode (no real charge, no PAN #45); the #79 prefs engine reused (Email gates delivery, mutation-proven); #33 door code guest-only; #20 reply/assign/create/save controller-routed + gated + audited; #39 no fabrication (no fake assignment/charge/stored-card/SMS/push/suggested-reply; low_rating a real rating branch; the wizard summary an honest estimate). Anti-drift — reuse `InboxService`/`MessageThread` (#77) · `AssignmentService`/`FolioService`/`GuestWalletService` (#84) · `NotificationPreferences`/`QuietHours` (#79) · `EmailTemplateResolver` (#83) · `StaffRoster` · new shared `ReservationExtras`. Full suite green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; all three pages browser-verified. **Invariants #1–92.**

### 2026-07-31 — OPERATOR OWNERS + OWNER DETAIL + PAYOUT RUN + NOTIFICATIONS exact-match — the operator side of the owner financial system (invariants #93–95)

Four operator pages (all already existing) matched to their wireframes EXACTLY on REAL, reconciling data, reusing the certified owner-financial spine (never rebuilt). `docs/OPERATOR-OWNERS-PAYOUTRUN-AUDIT.md` is the per-element source of truth. Four founder decisions taken **Recommended**: wire the real `PayoutRunService` (test-mode persisted run + lock) · SEPA honest-disabled "Soon" · on-hold = the real tax-hold + no-payout-method gate (real "Request IBAN", no fake verified) · build a real notes timeline.

- **GATE 1 — Notifications + Owners list (`7d33c9a`, invariant #93).** Notifications feed gained a real **"Unread" filter** (`NotificationFeed` honours `?filter=unread`); the Housekeeping/Channels wireframe chips are honestly absent (no notification fires them — #39). Owners list (`OperatorOwners`) rebuilt to the wireframe: KPIs + owner rows with the REAL statement `net_payable_minor` + a real pay-status + a payout-run summary + the statement schedule showing the **REAL 18% commission** (`ManagementAgreement.commission_pct` via a distinct-rate read, NEVER the wireframe's hardcoded 15%). On-hold pay-status is honest (the certified tax-hold OR no payout method on file). Add-owner already reuses the #80 `OwnerInviteService`. +4 tests.

- **GATE 2 — Owner Detail (`769e6e0`, invariant #94).** The **net-to-owner breakdown** of the latest finalized statement via the shared `OwnerLedgerAttribution::breakdown` (credits − deductions == net, reconciling to the cent at the real 18% — #17); paid-this-year + occupancy (`PerformanceMetrics::forOwnerUnits`); the **masked IBAN** (`Iban::masked($payoutMethod->iban_last4)`, CAP_PAYOUT only, never the full number, the operator never enters it) with NO fake "verified" badge (none exists) — honest "no payout method on file" otherwise. `OwnerManageController`: **Edit owner** (commission → a NEW effective-dated `ManagementAgreement`, "applies from the next statement"; portal toggle) · **Pause payouts** — REAL via `OwnerPayoutPreference.paused` (the certified engine already skips a paused owner; the amount stays in the ledger + carries via the statement) · **Add note** (real `Owner::noteEntries` polymorphic `Note` timeline) · ⚠️ **Remove owner** — a careful **ARCHIVE-not-destroy**: SOFT-DELETE the owner (FK cascades fire only on a HARD delete → statements/payouts/ledger/bookings RETAINED), `units.owner_id` nulled, the active agreement ENDED, the linked portal user deactivated + invites revoked, type-the-exact-name confirm. All writes `can:users.manage`, read-only-gated, audited (#20). +6 tests (net reconciles at 18%; edit → new agreement; pause real; remove archives-not-destroys; typed-name confirm; gate).

- **GATE 3 — Payout Run (this commit, invariant #95).** Wired the certified **`PayoutRunService::run(manual)` in TEST MODE** (`PayoutRunActionController::runNow`) — a real **persisted `PayoutRun`** that RECONCILES (run total = Σ owner nets, each payout `amount_minor` = the statement's `net_payable_minor`, per currency — #17) and LOCKS (idempotent on `run_key`), moving **NO real money** (`PayoutEngineMode::current()` = test outside production; `ManualPayoutRail` records / `FakeStripeClient` simulates — #71/#45). **Added the read-only guard** to every payout write (`create`/`pay`/`runNow`) — a real safety FIX (it was missing). A **test-mode banner** (`<x-owner.payout-test-mode>`), a real run header (status + created/paid/held tallies), **masked IBAN** per transfer (CAP_PAYOUT, last-4, never full), the honest **On-hold** card (the certified `held` tax-hold + owners with a positive-net ready statement but NO payout method on file — with a real "Request IBAN" prompt, NO fabricated "verified IBAN"), **Export SEPA honestly disabled "Soon"** (no SEPA/pain.001 generator exists → no fabricated bank file, #39), and the honest carry-forward note (the real negative-net #P2-1 mechanism, not a button). +4 `OperatorPayoutRunEngineTest`; `OperatorPayoutRunTest` updated (SEPA now an honest disabled Soon).

**Guards / honesty held:** operator-scoped (tenant; `OwnerScope` inactive off-portal — explicit `owner_id` reads); the certified payout engine #71 reused (reconciling + TEST-MODE — no real money until go-live); #17 the run total + the net-to-owner reconcile at the real 18%; IBAN masked (#28/CAP_PAYOUT, never full, operator never enters it); #20 add/edit/remove/pause/run controller-routed + gated + audited; **remove archives-not-destroys** (statements/payouts/ledger/bookings retained); #39 no fabrication (no fake payout/transfer/verified-IBAN/SEPA-file/reconcile-break; the on-hold is the real tax/no-method gate; carry-forward is the real negative-net mechanism). Anti-drift — reuse `PayoutRunService`/`OwnerPayoutService`/`PayoutEngineMode` (#71) · `OwnerLedgerAttribution` · `OwnerCommissionRate`/`ManagementAgreement` · `OwnerPayoutPreference` · `OwnerInviteService` (#80) · `Iban`/`PayoutMethod` · `NotificationFeed` (#79) · `PerformanceMetrics`. `OperatorOwnersList` 4 + `OperatorOwnerManage` 6 + `OperatorPayoutRunEngine` 4 + the reused-engine suites green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; all four pages browser-verified. **Invariants #1–95.**

### 2026-07-31 — OPERATOR PORTFOLIO + PROFILE exact-match — real multi-property analytics + operator account reusing #66 security (invariants #96–97)

Two operator pages (both existing) matched to their wireframes EXACTLY on REAL data. `docs/OPERATOR-PORTFOLIO-PROFILE-AUDIT.md` is the per-element source of truth. Multi-property confirmed REAL (Phase 6a/6b — no build). Founder decisions **Recommended**: build operator sessions (port owner into a SHARED helper) · preferences store all + apply motion/density/tips + date-format incremental · personal fields build preferred_name+phone+locale+per-user timezone.

- **GATE 1 — Portfolio (`10169f4`, invariant #96).** The KPI strip matched to the wireframe: **Revenue·month** (real room revenue via `PerformanceMetrics::forProperties`, Σ properties) · Occupancy tonight (`PropertyOccupancy`) · Arrivals today (`FrontDeskService`) · **Needs-attention** (real derived — setup-incomplete + `channels.status='error'`). Property cards gained per-property **ADR + room revenue** (this month) + an **HONEST Fix-sync** reading the real `channels.status`/`last_synced_at` ("Sync needs attention" on a real error → a Fix-sync link / "Channels connected" / "No channels connected") — NEVER a fabricated "synced N min ago" (#85/#39; demo Vista has no channel). The side-by-side comparison is "This month" (Group = Σ properties, reconciling by construction — the same `PerformanceMetrics::forProperties` for a row `[$id]` and the Group `$accessibleIds`, #17). Added the **read-only guard** to `PropertyController::store/update` (was missing). +5 tests (revenue KPI reconciles; needs-attention real; honest no-channel + real errored-channel fix-sync; read-only refuses add).

- **GATE 2 — Profile (this commit, invariant #97).** A migration adds `preferred_name`/`phone`/a per-user `timezone` + the four preference columns. **Personal details** (name/preferred-name/email/phone/language[`locale`]/timezone) editable, self-only (`$request->user()`, no id in the request), audited. **Preferences** stored + genuinely APPLIED — the operator layout reads the authed user's `reduce_motion` (`data-reduce-motion="1"` overriding the `prefers-reduced-motion` media query) + `compact_density` (`data-density="compact"` tightening spacing); date-format via the new shared `App\Support\DateFormat` helper (applied on the profile now; incremental workspace-wide, honestly). **Role & access** = a READ-ONLY display of the REAL spatie capabilities — the key caps the user actually holds (via `can()`) + the full `getAllPermissions()` set, "managed by your workspace admin"; a Hotel Manager correctly shows payments+reports ✓ but owner-payouts/team — (never a fabricated permission list, #39). **Sign-in & security** reuses #66 (password + 2FA summary linking to the Security page) + REAL sessions via the NEW SHARED `App\Support\Security\ActiveSessions` helper (extracted from `OwnerSecurity`, which was refactored to delegate to it — anti-drift, ONE definition) + a REAL **sign-out-of-all-other-devices** (`OperatorProfileController::signOutOtherSessions` — current-password-confirmed, deletes the user's other `sessions` rows, rotates the remember-token, audited). +5 tests (personal details save; prefs stored + applied to the body; real role caps; sign-out-others revokes for real; wrong-password refused).

**Guards / honesty held:** operator-scoped; #17 Group totals = Σ properties (reconciling, by construction); #20 profile edits controller-routed + audited (self-only); the #66 security reused (password/2FA) + the owner sessions logic extracted into a SHARED `ActiveSessions` (owner + operator, anti-drift); #39 no fabrication (Fix-sync the real `channels.status`; role caps the real spatie set; sessions real `sessions` rows; preferences stored + genuinely applied; multi-property real). Read-only respected (added the missing portfolio-write guard; personal settings stay editable by design). Anti-drift — reuse `PerformanceMetrics`/`PropertyOccupancy`/`FrontDeskService`, `PropertyContext`/`property_user`, `PropertyController`, `Channel`, spatie caps, `OperatorSecurity`/`ChangePasswordController`, the new `ActiveSessions` + `DateFormat`. `OperatorPortfolio` 6 + `OperatorPortfolioGate1` 5 + `OperatorProfile` (existing) + `OperatorProfileGate2` 5 + `OwnerSecurity` 7 green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; both pages browser-verified. **Invariants #1–97.**

## 2026-07-31 — Operator PROPERTY SETTINGS exact-match (task #98, invariants #98–99)

Rebuilt `/app/settings` (context-scoped, PropertyContext — NOT a `{property}` param) to `Operator Property Settings.html` on REAL backends, gated.

- **GATE 1 (#98, `15bcf9b` + security `c744834`):** basics (+address) · policies (cancellation/deposit/pets/self-check-in — door code GUEST-only #33) · owner card at the **REAL 18%** (`ManagementAgreement` via `Unit.owner_id`, never the wireframe's 15%) · **REAL booking pause** (`PropertyBookingPauseService` → certified `AvailabilityService`; `bookings_paused_at` zeroes `total`, new bookings refused, existing stays run — mutation-proven) · who-can-access (real `property_user` team + role tiers) · **guest-facing BRAND** (new per-property store → shared `emails.layout` + guest shell + review page, fail-open to Vyrobel; Postmark-safe: from-name + reply-to per-property, from-address fixed verified; live preview mirrors the real email) · read-only guard added. SVG dropped from logo uploads (stored-XSS) — raster only.
- **GATE 2 (#99):** ⚠️ **the Tax & Fees are now the REAL folio source, reconciling (#17)** — the ONE `StayEstimator` rule set makes an **inclusive** VAT informational (not double-charged) + posts non-optional fees (`applyFee` finally has a caller in `ReservationCreateController::postFees`); the **sample 4-night stay reconciles to a real folio to the cent** (mutation-proven). Custom **Add-a-fee** (per-stay/night/guest, optional-at-booking). **Remove-property** = a careful scheduled **archive-not-destroy** (`PropertyRemovalService` + `properties:process-removals`; stays live until last checkout, soft-delete when due, channels disconnect, cancellable, records retained — NEVER a hard delete). Demo VAT is exclusive + no prior demo fee → existing folios unchanged; a flagged demo cleaning fee added.
- **Raw-blade trap caught in the browser (not tests):** `right now@endif` (word char before `@endif`) 500'd on GET while static checks + POST tests stayed green — fixed + a GET-render test added.

**Guards / honesty held:** property-scoped; #17 sample==real-folio; #33 door-code guest-only; #20 writes controller-routed + audited; #66-style read-only guard; #39 no fabricated fee/total/brand/permission; remove archives-not-destroys (records retained). Suite green (only known-flaky BookingImportTest); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. `docs/OPERATOR-PROPERTY-SETTINGS-AUDIT.md` is the per-section source of truth.

## 2026-07-31 — Operator RATES & availability + REFUND exact-match (task #100, invariants #100–101)

Rebuilt `/app/rates` + `/app/reservations/{res}/refund` to their wireframes on REAL data, gated.

- **GATE 1 — Rates (#100, `01b1302`).** On the certified `RateAndRestrictionService` (the SAME model the owner sees → operator rates flow to the owner, reconciling). BUILT: **bulk edit** (`RateBulkService` — increase%/decrease%/set-exact × units × nights, skip booked/closed, update-derived); the REAL **derived-plan engine** (`RateDerivationService` — Non-refundable −10% + Weekly −15% = base × adjustment, recompute on base change, mutation-proven); `setRatePerNight` primitive; rate-plans list; **honest channels** (#85), **honest pacing** (no fabricated trend #39); the **read-only guard** (gap fixed). Grid writes stay DIRECT (proposals #67/guardrails #81 = separate owner-detail surface). Smart-pricing omitted (no engine).
- **GATE 2 — Refund (#101).** On `FolioService::refundPayment` (#27; test-mode safe). BUILT: **the owner statement adjusts** — `AdjustOwnerLedgerOnRefund` (on `payment.refunded`) → `OwnerAttributionService::attributeRefund` posts an owner-scoped `adjustment` (net-ratio share of the refund; reconciles from the owner's actual net; idempotent; no-op for operator-only; mutation-proven €50→€40 at 80%); the **reason** (+note+destination) on the refund `metadata` (additive `?string $reason`, #27 intact); the **#83 email brand-aware** (`BrandsGuestMail`). **HONEST:** the real **gateway** (never a fabricated "····4242", #28); per-charge deferred (needs a folio_items↔payments link). Refunds un-blocked by the workspace pause.

**Guards / honesty held:** operator-scoped; rates flow to the owner via the shared model; derived plans reconcile; #17 the refund reconciles + the owner statement adjusts (reconciling from the owner's net); #27 cumulative bound intact; #28 no card fabrication; #39 no fake rate/derived/sync/suggestion/adjustment; TEST-MODE (no real money). Suite green (only the known-flaky BookingImportTest, 11/11 isolated); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. `docs/OPERATOR-RATES-REFUND-AUDIT.md` is the per-element source of truth.

## 2026-07-31 — Operator ROLES & permissions + SCHEDULE PDF + SEARCH exact-match (task #102, invariants #102–103)

Rebuilt `/app/roles` + `/app/schedule/pdf` + `/app/search` to their wireframes on REAL data, gated.

- **GATE 1 — Roles (#102, `87b5ccd`).** ⚠️ The permission matrix genuinely edits real capabilities: `RolePermissionService` writes the spatie `role_has_permissions` pivot (team-scoped `givePermissionTo`/`revokePermissionTo`) → a toggle flips `$user->can()` immediately (mutation-proven end-to-end). Gated `can:roles.manage` (org-admin: Property Owner + Super Admin); editable = the non-wildcard back-office roles (Super Admin/Property Owner['*']/External Owner/Guest LOCKED); guards: subset + **last-admin** (never leave zero role-managers) + catalog-permission-only + team-scoped; **audited** (#20); read-only-guarded; member emails surfaced. New-role/delete-role HONESTLY deferred — back-office `/app` access is config-anchored (`EnsureBackOfficeAccess` #26 untouched); the catalogue is fixed.
- **GATE 2 — Schedule PDF + Search (#103).** *Schedule PDF:* a summary (team/shifts/hours) DERIVED from the ONE `RosterWeek` builder (reconciles to the grid #43) + a real time-off list (`LeaveRequest`) + a `PropertyBrand` letterhead; "open shifts" omitted (no unassigned-shift model), "provisional until published" reworded (no publish state), VAT omitted. *Search:* real date/source/status filters + server-side counts + guest-email blind-index (`email_hash` exact-match #28) + sort + occupancy band; recent via client `localStorage`; suggested = real upcoming arrivals.

**Guards / honesty held:** the RBAC matrix edits real capabilities (mutation-proven), `roles.manage`-gated + audited + last-admin safety; the schedule PDF reconciles to the one builder; search operator-scoped (full guest data — the manager, distinct from owner name-only #28); #39 no fabricated permission/rota/result. Suite green (only known-flaky BookingImportTest); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. `docs/OPERATOR-ROLES-SCHEDULE-SEARCH-AUDIT.md` is the per-element source of truth.

### 2026-07-31 — Operator COMPLETENESS AUDIT + BUILD (#104; `docs/OPERATOR-COMPLETENESS-AUDIT.md`)

A full adversarial completeness audit of the operator realm (all 42 wireframes decoded; 9 parallel agents; route→controller `authorize()` verified) found the realm **far more complete than a rebuild implies** — ~20 pages fully-operational, most gaps concentrated in ONE non-operational surface, ONE #17 reconciliation bug, a cluster of real-able-absent sections, and — the headline — a **read-found SECURITY cluster**. Built in gated order (19 commits, per-item, each browser-verified where UI + mutation-proven where guard/write); nothing already-real regressed; honest deferrals kept honest.

- **⛔ GATE 0 (SECURITY, #20/#30):** operator WRITE routes shipped with no `can:` middleware AND no controller `authorize()` — a privilege-within-org hole (worst: `maintenance.resolve` posts a real owner expense, reachable by Front Desk/Accountant). Gated with the dedicated permissions that already existed but were never wired (`maintenance.manage` / `housekeeping.manage` / `reservations.assign` / `reservations.checkin/checkout`), each chosen so the legitimate role keeps access; controls gated on the same permission. **Same pattern as the #26–#30 read-found holes — green tests were necessary, not sufficient.**
- **GATE 1:** Unit Requests → OPERATIONAL (approve creates the app's FIRST real unit + assigns the owner + makes it bookable · decline · notify) · **Group Booking #17** (block folios now charge tax+fees via the ONE shared `App\Support\Folio\PropertyCharges` the direct flow uses — was room-only, under-billed) · Reservation Detail unified `audit_events` activity trail + guest-CRM link · Maintenance awaiting-parts UI (wired the built-but-unwired writer) + SLA card (shared `slaLabel`).
- **GATE 2:** Reports revenue-by-channel + revenue-by-month + real CSV export (shared `OperatorRevenueBreakdown`, reconciling to gross) · Reviews reputation dashboard (`ReviewRating::operatorSummary`) + status filters · **Property Settings VAT Incl./Added toggle now PERSISTS** + flips the folio rule · Rates room-type grid filter.
- **GATE 3:** Inbox snooze/reopen UI (closed-thread dead-end fixed) · Security real active-sessions panel (was "Soon", live on Profile) · Dashboard arrival detail + Reservations Refunded pill & unit search · Calendar bar cross-link + range switcher · Schedule grant-leave UI + coverage summary · Front Desk **estimated-arrival** captured (new `reservations.expected_arrival`) + displayed (New-Res field was dropped).
- **Honest deferrals kept (not faked):** reservation add-note / team_note (needs #28 `anonymize()` wiring) · send_confirmation (needs threading a suppress flag through the certified booking event) · Front-Desk time-of-day PLOT + Schedule open-shifts (free-text window / net-new model — would fabricate) · account-gated integrations unchanged (OTA/SMS/Push/live-payment/IBAN/IP-allow-list).

**New invariant #104:** every operator write is capability-gated at the ROUTE (the controller carries no `authorize()`, so the middleware IS the actor gate — verify the role matrix so the legitimate role keeps access); a cross-surface money rule (tax/fee posting) lives in ONE shared helper so the direct + group flows can't drift (#17). Suite green + new tests; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean.
### 2026-07-31 — Operator SECURITY + SETTINGS + TEAM SCHEDULE exact-match (task #104b, invariants #105–107; `docs/OPERATOR-SECURITY-SETTINGS-SCHEDULE-AUDIT.md`)

Three operator System pages matched to their decoded wireframes EXACTLY — design + behaviour — on REAL data, building what was missing. Founder decisions (Recommended, approved): Settings SUPERSEDE-BY-LAYER over the ONE `SettingsService` store · SAF-T config real / issuance honest-deferred · Scheduling BUILD BOTH minimal-real (publish + open-shifts) + read-only fix · Connected apps honest.

- **GATE 1 — Security (`84b2706`, invariant #105).** `/app/settings/security` rebuilt: overview + 4 KPI count tiles · change-password · 2FA (reuse `two-factor-panel`) · **IP allowlist GENUINELY ENFORCED** — a NEW `ip_allow_rules` table + an `EnforceIpAllowlist` middleware registered in the `/app` group AFTER `EnsureBackOfficeAccess` (Symfony `IpUtils::checkIp`, exact + CIDR; **fail-open** when no tenant context / zero rules; the first rule auto-adds the current IP as a lockout mitigation; `settings.manage`-gated add/remove, audited) — mutation-proven it actually 403s a non-covered IP and lets a covered one through, NEVER a fake toggle · active sessions (`ActiveSessions`) · trusted devices (`TrustedDeviceService`, current-password-gated revoke, #66) · **Connected apps HONEST** "No apps connected" (operator OAuth is owner-data-scoped only #72 → there is no operator app catalogue to fabricate) · **Data export** (self-scoped account + own `audit_events`; JSON/CSV; date-range; csv-safe). `OperatorSecurityIpAllowlistTest` (5) + `OperatorSecuritySessionsTest` updated.

- **GATE 2 — Settings (`1ab313f`, invariant #106).** `/app/settings` is the System→Settings HUB, **DEDUPED by SUPERSEDE-BY-LAYER** over the ONE `SettingsService`/`SettingsActionController` store — every existing writer reused unchanged, new config as COLUMNS on `organizations` (never a parallel store). Added: **Invoice details** (billing company/VAT/city + series-prefix / next-number / auto-issue → `updateInvoiceDetails`, `settings.manage`, audited) with an honest **SAF-T "Soon"** note — the config is real; the certified sequence + SAF-T export are honestly deferred (no invoicing pipeline exists, grep-clean → nothing fabricated) · **Your plan** card (`Organization::billingStatus()` + `SubscriptionPricing::current('monthly')`, real state + label, Manage → `app.billing` #85; €99 = a configurable placeholder, honest) · Notification-routing + Guest-messages as honest links to their real editors (`notification-preferences` #79 · `email-templates` #83/#86). Owner-statement commission is the REAL **18%** (`OwnerCommissionRate`, never the wireframe's hardcoded 15%). `OperatorSettingsInvoiceTest` (3).

- **GATE 3 — Team Schedule (`445cc34`, invariant #107).** The interactive-rota additions on the REAL rota model: **Publish week** (a `schedule_publications` per-(property, week) finalize stamp + a real `SchedulePublishedNotification` [new `schedule_published` CATALOG entry, Operations] to each rostered staffer; edits stay LIVE, re-publish idempotent) · **Open shifts** (a net-new `open_shifts` table — a `Shift` can't be open [`user_id` non-nullable + unique(property,user,date)], so an open slot is its own row; FILL = a REAL Shift via the certified `ShiftRosterService` [leave-conflict guard intact] + delete the open row in one txn; post / assign / remove) · **READ-ONLY FIX** — the audit found the schedule writes did NOT respect a paused workspace though the banner showed (unlike the Rate/Housekeeping peers); every roster write (assign · time-off · grant/approve/deny · publish · open-shift) now short-circuits via `guardReadOnly()` when `OperatorWorkspace::isReadOnly()`. All writes are `can:schedule.manage` controller POSTs (#30), property-scoped (`PropertyContext`, never `->first()`), over real `StaffRoster` staff, audited, nothing fabricated (#39). `OperatorScheduleInteractiveTest` (5), existing 12 schedule tests green. Browser-verified as demo-staff: posted an open shift → assigned to Rosa Fernandes (a real Early shift) → published the week (team notified, "Published … ago" tag, button → Re-publish).

**New invariants:** **#105** — a back-office access control (IP allowlist) must GENUINELY enforce or not exist: real middleware in the `/app` group, fail-open on no-tenant/zero-rules, lockout-mitigated, mutation-proven to block — never a fake toggle; where a capability is owner-data-scoped only (OAuth #72) the operator surface stays honestly empty, never a fabricated catalogue. **#106** — operator Settings is a HUB DEDUPED by supersede-by-layer over the ONE `SettingsService` store: new config is columns on existing tables, never a parallel store; a pipeline that doesn't exist (SAF-T issuance) is config-real + honestly deferred, never fabricated-certified. **#107** — the schedule "publish" is a real per-(property, week) finalize stamp + a real notify (edits stay live, no fake draft state); an "open shift" is a real unassigned row that FILLS through the certified `ShiftRosterService` (never a fabricated assignment); and EVERY roster write respects the read-only workspace. **Presentation follow-ups documented** (Team-Schedule day-view / drag-move / coverage bars / add-shift modal; Settings cancellation-days / deposit-% / booking-ahead as honest config extensions). Suite green + new tests (+13); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Invariants #1–107.**
### 2026-07-31 — Operator WORK ORDER detail exact-match (task #108; `docs/OPERATOR-WORK-ORDER-AUDIT.md`)

The operator Work Order DETAIL (`/app/maintenance/{ticket}`) matched to `Operator Work Order.html` EXACTLY on the REAL `MaintenanceTicket` — the DETAIL of the SAME model the Maintenance board (`MaintenanceList`) lists, so anti-drift is satisfied by construction and the board is undisturbed. The page already existed (#89) on the operator design system; this is an exact-match restyle + gap-fill. Founder pre-approval carried through both gates.

- **GATE 1 (`9e92450`) — detail + SLA + OOS impact + reassign/photos/composer.** The 2-col grid, dark Status hero, 4-col Details, Reported-issue + real photos, Activity timeline + composer, Assignee, SLA, Out-of-order impact, two modals. REAL: the **Activity composer** posts a real staff update (Note / Progress / Part-ordered) through a NEW `MaintenanceService::addNote` on the SHARED polymorphic `Note` store (a nullable `notes.kind`; never a parallel updates table), and the timeline **MERGES** real `audit_events` ∪ real notes with per-type dot colours; the **SLA** is surfaced from the ONE `MaintenanceSla` helper the board reads (due-by / countdown / progress / open-for), honestly ABSENT with no configured target; the **out-of-order impact** is the real block + a real affected-booking count narrowed to "N arrival(s) tonight" + the real rack link; the **Reassign modal** lists real org staff with a REAL open-work load (N open · M urgent); **photos** are real Media (mt_photo, #88/#89). Shared `HasMedia::media()` gained a generic (mirrors `HasNotes`). +7 `OperatorWorkOrderTest`.

- **GATE 2 (`4874e53`) — Mark-resolved (cost #17 + hours + notes + return-to-sale + notify) + reassign flow.** The full Mark-resolved modal: **What-was-done** → a new real `resolution_notes` column ALSO posted to the timeline; **Time-spent** → a new real `labor_hours` column (informational — hours are NOT auto-priced, no labour rate is modelled, so they never touch the owner bill); **Parts-cost** (€) bills the owner via the certified `OwnerExpenseService` (**parts_minor === cost_minor**, the SAME integer-minor the Costs report sums — reconciles #17); **Return-to-sale** (default ON) genuinely un-blocks the unit via the certified `releaseUnitIfClear`, and OFF a new `keepBlocked` option leaves it out of order (both mutation-proven; `sold` never touched); **Notify-front-desk** = a real, preference-gated `MaintenanceResolvedNotification` to the reporter (toggle-respecting). The **Reassign flow** gained reason chips (a real recorded field on the assign audit/domain payload) + a "Notify both technicians" toggle → real `MaintenanceReassignedNotification` to the new + prior assignee. Two new CATALOG entries. +4 `OperatorWorkOrderTest`.

**New invariant #108:** the operator Work Order DETAIL is the exact-match view of the SAME `MaintenanceTicket` the board lists (anti-drift; the board undisturbed); its SLA is the ONE shared `MaintenanceSla` (board + detail can't drift); OUT-OF-ORDER genuinely blocks via the certified availability spine and RETURN-TO-SALE genuinely un-blocks (both mutation-proven; the affected-arrival is a real reservation count, name-only #28); MARK-RESOLVED captures real cost that reconciles (#17) to the Costs report + owner Expense (parts_minor === cost_minor), plus real labour-hours (informational, never priced) + a real resolution note; the composer/reassign/photo writes reuse the shared `Note`/assign/Media paths; every write is `can:maintenance.manage` (#30) + read-only-respected + audited; nothing is fabricated (#39 — Area / WO-#### / specialty / "On site" / availability / outcome-chips / "€/night on hold" OMITTED, never invented). A raw-blade `word@if` trap was caught in the modal copy before shipping. Suite green + new tests (+11); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; browser-verified end-to-end. **Invariants #1–108.**
### 2026-08-01 — Guest emails: Booking Confirmation + Cancellation exact-match + Email Previews harness (task #109; `docs/GUEST-EMAILS-CONFIRMATION-CANCELLATION-PREVIEWS-AUDIT.md`)

The two guest Mailables (they already existed but minimal, Phase 3) rebuilt exact-match to `Booking Confirmation Email.html` + `Cancellation Email.html`, email-safe, and threaded through the #86 template editor (catalog 5→7); the Email Previews page (`Email Previews.html`) rebuilt to the tabbed gallery + a real own-inbox test-send. Founder pre-approved the honesty refusals below.

- **GATE 1 (`2364256`).** **Confirmation:** real folio-item price breakdown with **Total === `total_minor`** (#17; honest degrade to Total-only, no fabricated nightly split); real dates/times/guests/receipt; **add-to-calendar** (Google/Outlook/Apple .ics) via a new `App\Support\Email\CalendarLinks` from real dates; **HONEST** — no masked card → "your original payment method" (Payment is token-only, SAQ A, #28); door code a **48h pointer only** (#33, `PreArrivalMail` carries the code); real source category, never an OTA brand (#85); "free cancellation until {date}" from the real policy. **Cancellation:** now **branded** (`BrandsGuestMail` — the gap) + **two honest cases** (a real recorded refund vs "No refund due" — the deposit/non-refundable/waived split is unmodelled + refused; a real refund fires its own `RefundConfirmationMail`); a warm come-back invite, **no fabricated promo** (#39). **#86:** two catalog entries (defaults byte-faithful), a `template()` + `EmailPreviewSample` arm on each, blades read `$heading/$intro/$button/@if($blocks[...])`, the `enabled()` guard added to both triggers. +5 `GuestEmailBookingCancellationTest`.

- **GATE 2 (`73bc366`).** The Previews gallery: a 9-pill tab switcher, an inline `<iframe>` rendering the REAL Mailable per tab (`app.email-previews.show` — can't drift), honest status captions, and a **real Send-test** (`EmailPreviewController::testSend`, the ONE shared builder) to the **operator's OWN inbox only** (the certified rule — the wireframe's free-typed recipient refused; `MailSafetyGuard` downgrades outside prod). Gated `can:properties.manage` (#30) + `@can`-gated control. +3 `OperatorEmailPreviewsTest`.

**New invariant #109:** the two guest transactional emails are exact-match to their wireframes AND threaded through the #86 editor (catalog defaults byte-faithful so the #83 five + `GuestEmailTest` stay green); the confirmation price breakdown reconciles (#17 — real folio items, Total === `total_minor`, honest degrade, no fabricated split); a MASKED CARD is never shown (Payment is token-only, SAQ A → "your original payment method", #28); the confirmation door code is a 48h POINTER, never the code (#33); the cancellation is TWO honest cases (a real recorded refund or "No refund due" — the deposit/non-refundable/waived split + WELCOME10 promo + OTA brand are fabrications, refused #39/#85); calendar links are generated from real dates; the Email Previews gallery renders EACH real Mailable (anti-drift, can't drift from the send) + a real test-send to the operator's OWN inbox only (the certified own-email rule, `properties.manage`-gated + `@can`-gated control). Suite green + new tests (+8); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean; both emails browser-rendered + the gallery browser-verified. **Invariants #1–109.**

---

## 2026-08-01 — GUEST PORTAL exact-match rebuild (Booking storefront · Arrival Info + PDF · Add-ons · Account) — invariants #110–114

The five guest-facing wireframes rebuilt exact-match to the peach-glass design on REAL data, BUILDING what was missing, under the honesty rule. Audit = `docs/GUEST-PORTAL-AUDIT.md` (PHASE 1 decode + investigate + 4 founder decisions). Three gated commits.

**Four founder decisions (locked in PHASE 1):** D1 storefront pay = **test-mode hosted Stripe** (reuse `createPaymentCheckout`+`recordCheckoutIntent`+webhook). D2 account delete = **real 14-day scheduled erase** (reversible). D3 cards = **honest empty state** (no vault, no raw PAN). D4 add-ons = **operator-confirmed request** → folio posts on confirm.

- **GATE 1 (`51ae811`) — Booking storefront.** `booking/browse` rebuilt to the peach-glass wireframe (frosted search pill, real bedrooms/amenities/max-price/sort filters, horizontal cards with real ratings #76 + availability, signed-in-gated chrome, no map [no geo → refused]); room/checkout/reservation re-skinned. **#17:** the grand total shown === the folio total INCLUDING the property's real non-optional cleaning fee — `BookingEngineService::book()` now posts room + fees + taxes via the certified `PropertyCharges` (never a hardcoded €45); the checkout shows the reconciling breakdown via the shared `StayEstimator` (browser-verified €340 + VAT €78.20 + city €20 + cleaning €45 = €483.20). **D1:** "Reserve & pay" routes through the certified TEST-MODE hosted Stripe Checkout (fulfil-on-webhook, no PAN/SAQ A, StripeSafetyGuard); pay-on-arrival stays the manual fallback (a `takePayment` flag preserves the certified engine tests + the JSON endpoint). +`PublicBookingStorefrontPayTest` (4).
- **GATE 2 (`4db8fa5`) — Arrival Info + PDF + Add-ons.** Arrival re-skinned (warm-glass hero, "Save printable guide", message-host); the **new printable PDF** (`ArrivalPackDocument` + `pdfs/arrival` on the certified `PdfService`) reuses the SAME window-gated `ArrivalPack` as the screen + the #83 email — **#33: a pre-window PDF omits the code** (tested); house-guide/checkout/local-picks stay REFUSED. **Add-ons** = new "Enhance your stay" page on the REAL `AddOn` catalogue (never the invented 6), priced per `ReservationExtras` basis, VAT-inclusive (=== the folio delta, #17); **D4:** "Add to booking" posts real AddOn ids to a pending `GuestChangeRequest` — the operator confirms and `GuestChangeRequestService::confirm()` posts the folio (`postAddOn` + a separate VAT line on the taxable base only), reconciling to the cent + idempotent. +5 tests.
- **GATE 3 — Account.** Rebuilt to the hero + 5-KPI + 3-column bento on REAL data (credit via `GuestWalletService`, stays, waitlists via `GuestWaitlistEntry`, language). **D2 — the real 14-day scheduled erase:** `GuestDeletionService` (mirrors `PropertyRemovalService` #98) — `schedule()` stamps `scheduled_deletion_at`=+14d + emails (`AccountDeletionScheduledMail`) + audits (PII-safe `['fields']`); `cancel()` undoes; the daily `guests:process-deletions` command runs `processDue()` = per-policy cancellation of upcoming reservations (certified `AssignmentService::cancelReservation`) + the certified `Guest::anonymize()`. Never immediate/destructive; type-DELETE gated; reversible until it runs. **D3:** payment methods = honest "No saved cards" (no vault, no raw PAN). **#87:** the trip-offers toggle flips `marketing_consent`; SMS honest-disabled (no provider). +7 `GuestAccountTest`.

**New invariants:**
- **#110** — the storefront "Reserve & pay" books through the certified `AssignmentService` (no-overbook) with the folio carrying room + the property's real fees/taxes (never a hardcoded fee), and takes payment through the TEST-MODE hosted Stripe rail (fulfil-on-webhook, no PAN); shown total === folio total (#17).
- **#111** — the Arrival PDF is built from the SAME window-gated `ArrivalPack` as the screen + the #83 email (one resolver, no drift); a pre-window PDF/screen omits the door code (#33 right side — guest sees their own, owner never).
- **#112** — a guest add-on is an operator-confirmed REQUEST storing real scoped AddOn ids; the folio is posted only on the operator's confirm via the certified path (reconciling #17, idempotent) — a guest never writes the operator's folio directly (D4).
- **#113** — "delete my account" is a REVERSIBLE 14-day SCHEDULED erase (`scheduled_deletion_at` + undo + a daily sweep that cancels upcoming reservations per policy + runs the certified `Guest::anonymize()`) — never an immediate/destructive delete, never a fabricated undo timer (D2).
- **#114** — the guest saved-cards surface is an honest empty state: no saved-card vault, no raw PAN on the server (SAQ A / #28), never a fabricated stored card (D3).

Honesty held (REFUSED, never faked): cross-property "Where"/map (no geo), the structured house-guide/checkout-steps/local-picks/named-host+SLA+phone, the invented extras catalogue, "····4242"/raw PAN, the SMS send, the waitlist "#N in line". Suite green (the only failures = the pre-existing order-flaky `BookingImportTest`, green in isolation); PHPStan L5 (0); Pint clean; `migrate` clean; all five pages browser-verified.

---

## 2026-08-01 — REFUND-ON-CANCEL (policy-driven, in-window only) — invariant #115

Follow-up to the Guest Portal (the flagged gap: cancelling a booking released inventory but never issued a refund). Founder decision: **full auto-refund in-window only**.

- **`App\Support\Reservations\CancellationPolicy`** — the ONE reading of the policy's free-cancel window (`freeUntil` = check-in − `free_until_days`, `isRefundable(on)`); `BookingConfirmationMail`'s inline derivation refactored onto it (anti-drift). ⚠️ The policy encodes ONLY a deadline, not a post-deadline refund %, so nothing is invented (#39).
- **`App\Services\ReservationCancellationService::cancelWithPolicyRefund`** — cancels via the certified `AssignmentService` (throws-if-terminal preserved), then, WHEN the cancellation is within the free window, refunds everything paid through the certified bounded `FolioService::refundPayment` (#27 same-currency, TEST-MODE gateway, reconciling #17). After the window: **no auto-refund** (a manual operator decision).
- **Wired** the operator cancel (`ReservationLifecycleController` — flashes the refunded amount) + the guest account-delete sweep (`GuestDeletionService`) through it. OTA/no-show/system cancels are untouched (no auto-refund).

**Invariant #115** — a cancellation within the property's free-cancel window auto-issues a FULL, bounded (#27), test-mode refund of everything paid via the certified `FolioService::refundPayment` (reconciling #17); after the window there is NO auto-refund — the policy encodes no partial amount, so it stays a manual operator decision (never a fabricated %, #39). The refund-on-cancel is one path (`ReservationCancellationService`), reused by the operator cancel + the guest account-delete.

+`RefundOnCancelTest` (6): policy window · in-window full refund reconciles · after-window none · bounded to paid (#27) · operator route auto-refunds + flashes · the guest-delete sweep refunds in-window. Browser-verified (operator cancelled `R-NLEGCIJG` in-window → €246.00 refund posted + flashed). PHPStan L5 (0); Pint clean; the operator-cancel + terminate-guard suites green (nothing disturbed).

---

## 2026-08-01 — GUEST PORTAL batch 2 (Browse landing · Checkout restyle · Check-in wizard + ID vault) — invariants #116–117

Three more guest wireframes rebuilt exact-match on REAL data, BUILDING what was missing, under the honesty rule. Audit = `docs/GUEST-BROWSE-CHECKIN-CHECKOUT-AUDIT.md` (PHASE 1 decode + investigate + 3 founder decisions). Three gated commits.

**Three founder decisions (locked in PHASE 1):** D1 Browse = **build a multi-property per-org landing** (real aggregation; no fabricated towns/counts). D2 ID = **build a real encrypted store** (Crypt-wrapped, register-only, pending-never-verified, 30-day purge, owner-never #28). D3 checkout wallet credit = **yes, gated** to a signed-in guest with a real balance.

- **GATE 1a (`c13b1d5`) — multi-property landing (Browse, `/stay/{organization}`).** `ResolvePublicOrganization` (the org-level sibling of `ResolvePublicProperty` — real tenant context; live during trial+active, suspended → 404) → `StorefrontLandingController`: real "where to stay" towns (grouped by `address.city`) with a real from-price (cheapest forward nightly in `rate_calendar`; unpriced → omitted), real featured room types (from-price + per-room rating #76), real "browse by what matters" amenity chips (only amenities that exist, linked to a property that has them), honest why-direct perks (free-cancellation reflects the REAL policy window, not "14 days"), real recent reviews (#76, #28 byline). Hero rating badge = the REAL org average + REAL count (`ReviewRating::forOrganization`) — hidden with no reviews, NEVER a fabricated "4.8 · 369 stays". Search = native GET → the real per-property `booking.browse`. DemoSeeder: real towns (Faro/Lagos) + room-type amenities. +`PublicStorefrontLandingTest` (7).
- **GATE 1b (`4f833f6`) — Checkout restyle.** `booking/checkout` restyled to the wireframe (Secure-checkout header, 2-col details+payment | reconciling summary, room hero, dates, breakdown, wallet toggle IN the summary, big Total, free-cancel). **D3:** the wallet toggle is a REAL checkbox (`apply_credit`) shown ONLY for a signed-in guest with a real balance; the server caps at min(spendable, owed) + posts the real discount line; a no-Alpine inline preview swaps the shown Total using server-computed full/net strings (can't drift, #17). **Free-cancel:** `CancellationPolicy::freeUntilForCheckIn` (the ONE reading, now callable pre-booking). **D4/honesty:** NO raw PAN, NO on-page Apple Pay, NO "charged at check-in" (SAQ A → hosted Stripe). +`PublicCheckoutViewTest` (2) + wallet backend + two guest-actor FK-hardening fixes (`842d698`: `AssignmentService`/`FolioService` resolve the actor via the `web` guard — a users-FK actor must be an operator or null, never a signed-in guest).
- **GATE 2 (`2ec43ff`) — Check-in wizard + ID vault.** `guest/checkin` rebuilt to the 4-step wizard (Details → ID → Arrival → Done), server-rendered + a tiny no-Alpine stepper (JS-off → all steps visible + submittable); still stamps only the pre-check-in INTENT. **D2 — REAL encrypted ID:** `GuestIdDocumentService` Crypt-wraps the raw bytes BEFORE disk on the PRIVATE `local` disk (the blob is ciphertext; a test decrypts it back), chain Media→Document→GuestDocument `verification_status='pending'` (captured, never "verified"), owner-never (#28); `guest-documents:purge-expired` (scheduled daily) hard-deletes the file + rows 30 days after checkout; `Guest::anonymize()` also clears the guest's own `notes_guest` + `arrival_window`. New `arrival_window` column (operator-visible); the note reuses `notes_guest`. SMS→EMAIL everywhere (no provider); the third-party "Run the full ID check" scan dropped (no KYC); no fabricated co-guest name (count only). +`GuestCheckinIdVaultTest` (7); `GuestArrivalCheckinTest` updated (stale KYC-stub → real capture).

**New invariants:**
- **#116** — the multi-property storefront landing (`/stay/{organization}`) aggregates ONLY real data through the certified tenant scope: towns from `address.city`, from-price = cheapest forward `rate_calendar` nightly (unpriced → omitted), featured/ratings via `ReviewRating`/#76, amenity chips only where the amenity exists; the org rating badge is the REAL average + count (hidden when none) — no fabricated town/count/price/"369 stays" (#39); org resolution is real tenant context (`ResolvePublicOrganization`, live during trial+active, cross-org 404).
- **#117** — the guest ID captured at online check-in is REALLY encrypted at rest (Crypt-wrapped bytes on the PRIVATE disk — the blob is ciphertext), register-only, `verification_status='pending'` (CAPTURED, never a fabricated "verified" — #39, legally consequential), owner NEVER sees it (#28), and short-lived (a scheduled 30-day-after-checkout purge of the file + rows, plus immediate purge on GDPR erase). The single writer is `GuestIdDocumentService`; delivery of arrival secrets is BY EMAIL (#33) — SMS is honestly deferred (no provider), never faked.

Honesty held (REFUSED, never faked): the fabricated towns/counts/"369 stays" (real avg only) · inline raw PAN + on-page Apple Pay + "charged at check-in" (SAQ A → hosted Stripe) · "ID verified"/third-party KYC scan (capture-only, pending) · door-code "by SMS" (relabelled email) · a fabricated co-guest name (count only). Suite green (the only failures = the pre-existing order-flaky `BookingImportTest`, green in isolation — 11/11); PHPStan L5 (0); Pint clean; `migrate` clean; the landing + checkout + all 3 check-in steps + done browser-verified.

---

## 2026-08-02 — OPERATOR REALM → 100% pass · GATE 0: the bespoke-chrome ARCHITECTURE DECISION + topbar (foundational)

The operator realm audit (`docs/FULL-AUDIT-OPERATOR-GUEST.md`) found **0/27 pages at 100% fidelity**, with "shared-shell restructure vs each prototype's bespoke chrome" as the #1 root cause. The founder directed finishing every operator page to genuine 100% (bespoke chrome + motion + features), one page per gate, **preserving the shell's real machinery** (property switcher #38, read-only #84, nav-gating #104, tenant scope). GATE 0 establishes the approach that governs the whole pass.

**THE DECISION — parameterize the SHARED shell, do NOT fork 42 bespoke chromes (option (b)).** On inspecting the code, the premise partly dissolved: the shared operator layout (`components/layouts/operator.blade.php`) **already carries the prototype's exact chrome** — the same `--vy-bg` warm gradient (`135deg #F6C89B→#D9D6D2`), the `.vy-amb-orb1/2/3` floating orbs, the `vyFadeUp/vyShimmer/vyDrift` keyframes, the glass/radii/shadow tokens, the 230px transparent sidebar, the frosted-glass active-nav pill, and Alpine (via `@livewireScripts`). The prototype chrome is **identical across all operator pages** (same gradient/orbs/sidebar/topbar cluster) — only the H1 title and a per-page toolbar differ. So forking per page would **duplicate an already-correct chrome and violate anti-drift** (Phase 0.3's "ONE source" rule). The correct move: finish the SHARED shell's chrome to pixel-match the prototype — which upgrades **every** operator page at once and keeps the machinery by construction (it already lives in the shell).

**The pattern (for every later gate):** a page keeps using `<x-operator.shell active title subtitle :viewer :toolbar>`; the shell renders the prototype-exact chrome + machinery; the page's gate focuses on its **content, motion, and features** — never re-doing chrome.

**GATE 0 delivered (the specific chrome gap the audit flagged realm-wide):**
- **Account-dropdown popover** (replaces the static chip) — an Alpine `x-data` toggle: the button (name + real role + avatar + chevron) → a frosted popover (avatar + name + real login email) with **Your profile / Notification preferences / Security / Sign out**, all REAL routes; role + email derived from `auth()->user()` (self only) so no page passes them.
- **Search pill** (→ `app.search`, gated `reservations.view`) + **"Today · {date}" chip** in the topbar, matching the prototype; the redundant in-content search on Dashboard removed (de-dup).
- A **read-only pill** beside the H1 (from the same `OperatorWorkspace` as the banner, #84).
- A `$toolbar` slot for per-page topbar controls (date-nav / filters / primary action) rendered into the bespoke chrome, so pages don't fork the shell.
- `prefers-reduced-motion` honored; `[x-cloak]` guards the popover flash.

**Machinery preserved + verified:** property switcher (#38) in the sidebar, read-only banner + pill (#84), role-gated nav (#104) — all unchanged and green. Proven on the **Dashboard** browser side-by-side (topbar + account dropdown open/close pixel-match the prototype; property switcher + nav intact). 58/58 operator render tests green (every page renders through this shell); `OperatorDashboardViewTest` + `OperatorNavVisibilityTest` 7/7; Pint clean; Blade compiles. **No behaviour changed; no fabrication introduced.**

**Files:** `components/operator/shell.blade.php` (topbar + account dropdown + toolbar slot + machinery unchanged), `components/layouts/operator.blade.php` (topbar/account-dropdown CSS), `livewire/operator/dashboard.blade.php` (search de-dup). This is gate 1 of ~13 in the operator-100% pass.

---

## 2026-08-03 — OPERATOR REALM → 100% pass · GATES 9–12 + the two FINAL disambiguations (near-complete)

The operator finish-to-100 pass (governed by the GATE-0 parameterized-shell decision above) is essentially complete. Gates 9–12 landed; the two FINAL routing/trigger ambiguities are definitively resolved (both = intentional/honest states, documented — no code change warranted).

**Gates completed this stretch (each verify-first, browser-verified, adversarially reviewed where money/PII/auth/security, per-gate committed + pushed):**
- **GATE 9 (`ba48dc6`) — Team Schedule:** week drag-grid (move/swap) via a certified-composed `ShiftRosterService::moveShift` (atomic, leave-guarded; drag carries server-rendered Y-m-d strings → no toISOString day-roll), add/edit-shift modal, Week/Day view toggle + day agenda, HONEST per-department coverage bars (days-with-cover/7 — refused the prototype's fabricated 14/16).
- **GATE 10 (`b2aa12b`, `df9a3cc`) — Reports:** real Occupancy/ADR/RevPAR KPIs with HONEST vs-last-year deltas (shown only where prior-year data exists), a Reporting-month picker, and a real dompdf PDF export. Anti-drift: a shared `OperatorReportData` builder the page AND the PDF both consume (reconcile to the cent, #17); a shared `ReportPeriod` resolver (page + CSV + PDF). 4-lens adversarial review → 2 fixed (incl. an ADR-delta degenerate-zero-baseline honesty fix; verify-against-code also caught a reviewer's own premise being wrong — `PropertyContext::id()` already returns the first-accessible property).
- **GATE 11 (`3bd582b`) — Owners group = VERIFY-COMPLETE:** all five pages (Owners #93, Owner Detail #94, Payout Run #95, Notification Preferences #90, Email Templates #86) were ALREADY at exact-match from prior tasks — the FULL-AUDIT overstated the work. The one genuine defect: Notification Preferences showed a HARDCODED "Verified" email badge (a #39 fabrication browser-verify exposed — demo-staff's email was actually unverified). Fixed to reflect the real `email_verified_at`; demo operator/owner accounts seeded verified.
- **GATE 12 (`8d85783`) — the carried-over OOS WRITE-PATH GUARD is CLOSED:** since GATE 6 the create path (`AssignmentService::occupy`, the shared core of `createBooking` + `createGroupBooking`) checked overlap but not bookability, so a crafted request / group booking / walk-in / future job could seat a stay on a paused / out-of-service unit (the availability read + the create controller's re-validation blocked the UI + a crafted controller POST, but not the write path itself). Now `occupy()` refuses a named `unit_id` when `! $unit->isBookable()` (throws `UnitUnavailableException::blocked`) BEFORE `assertUnitFree`/insert/`adjustSold` — the SAME rule `assignUnit`(move)+`reschedule` enforce, one guard covering every create caller (#29-style). Mutation-proven (`CreateBookingUnitGuardTest`): OOS refused atomically (no reservation/reservation_unit/sold), paused refused, bookable still books. The OTA `occupyOrFlag` path (unit_id null) is unaffected.

**GATE 12 verify-first result (the GATE-11 lesson held):** the long-tail pages carry prior-task exact-match markers (Rates/Refund #100, Roles/Search #102, Profile/Portfolio #96, Property Settings #98, Reservation Detail "design pass", Channels "exact-match"). A targeted honesty-defect hunt (hardcoded status-claim badges — the recurring real-bug class) across the operator views came back CLEAN (the only status badges are `@if`-gated on real state). The highest-fabrication-risk pages preserve their refusals explicitly: Channels (no fabricated "last sync"; real connected count; #37) and Insights (Expedia/OTA-brand split refused; honest empty states; #39).

**FINAL #1 — Settings vs Property Settings → RESOLVED: one intentional combined hub.** There is a single `/app/settings` route (`OperatorSettings`, title "Property settings"). It deliberately serves BOTH decoded wireframes: all of *Operator Property Settings* (policies, tax & fees, guest brand, owner, property status/archive, who-can-access) PLUS the org-level parts of *Operator Settings* (**Your plan** — real billing state #85, "Manage plan" → the dedicated `/app/billing` route; **Tax & invoicing** — real invoice details / legal entity / VAT; **Notification routing** — honest "Soon", per-user prefs are real #79 but route-to-role isn't built). The wireframe's *Team & roles* section is the dedicated `/app/roles` route. **Reason for one surface, not two:** the two wireframes overlap heavily (both are Tax-centric), the selected-property context (`PropertyContext`) naturally hosts both the property- and org-level settings for that property, and the genuinely-separate deep concerns (full plan management, full team/role management) already have their own routes the hub links to. Splitting would fragment a cohesive settings experience for no functional gain. **Decision: keep the single combined hub; documented as intentional.**

**FINAL #2 — Winback + Waitlist email triggers → RESOLVED: honestly deferred, nothing fake fires.** The `WinbackMail` + `WaitlistNotificationMail` templates exist and are previewable (`EmailPreviewController`), but their triggers are DEFERRED — the guest-email listener (`SendGuestTransactionalEmails`) maps only the real events that fire today (booking confirmed/cancelled, refund, checked-out) and its docblock states the others (pre-arrival, review, waitlist, winback, verify) "have their templates built but their triggers deferred to a later phase / a scheduled command, so nothing fake fires here." They are NOT in the `EmailTemplateCatalog`, so the Email Templates page never presents them as live/Active. **This is the correct #39 state (a template that never sends is not shown as live). Decision: confirmed deferred-with-a-reason; wiring the real re-engagement / availability triggers is a later-phase item, not an operator-finish blocker.**

**Remaining to fully close the operator realm:** the doc-consolidation sweep (FULL-AUDIT-OPERATOR-GUEST.md → per-page RESOLVED; DIVERGENCE-REGISTER.md → each operator page at 100% with its honest deferrals; PROJECT-STATE.md → operator realm finished, guest Phase-4 pixel-diff pass next) + a final full-suite run. Suite state at this entry: booking/assignment/group/reschedule/front-desk 211/217 with the 6 known pre-existing `BookingImportTest` isolation flakes (pass 11/11 alone, OTA path, unrelated); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean.

---

## 2026-08-03 — OPERATOR REALM → 100% pass · CLOSED (doc-consolidation capstone)

The operator finish-to-100 pass is **COMPLETE across GATES 0–12 + FINAL**, all pushed to `main` (HEAD `0363fe2`). This entry is the closing capstone; the per-gate detail is in the two entries above (2026-08-02 GATE 0; 2026-08-03 GATES 9–12 + FINAL) and the per-page RESOLUTION LOG in `docs/FULL-AUDIT-OPERATOR-GUEST.md`. **Doc-consolidation sweep (docs-only, no code/tests/migrations/seeds touched):** the audit's RESOLUTION LOG (per operator page: gate + commit + what closed it + remaining honest deferrals), the DIVERGENCE-REGISTER operator-100% entry (+ the GATE-0 architecture decision + the ratified KIND-3 divergences), this capstone, and PROJECT-STATE's operator-realm-finished + guest-Phase-4-next update.

**What "complete" means here (honestly):** every operator page is at genuine 100% — bespoke-chrome fidelity (via the parameterized shell), full motion, full features — on REAL, tenant-scoped, reconciling data, with only **external-infra HONEST DEFERRALS** remaining (Channex sync, Stripe Connect / SEPA / IBAN verify, Push/SMS, live Stripe charge, open-rate tracking, Winback/Waitlist triggers — all test-mode or honestly surfaced, none faked #39). It does NOT mean every deferred integration is live — those are the founder's provider-account + go-live flips.

**Load-bearing decisions of the pass (recap):**
- **GATE 0 — parameterize the shared shell, do NOT fork 42 bespoke chromes.** The shell already carried the prototype chrome; forking would duplicate it + violate anti-drift. Every gate finished content/motion/features on the shared shell; machinery (#38/#84/#104/tenant) preserved by construction.
- **The createBooking OOS write-path guard is CLOSED** (`8d85783`): the shared `AssignmentService::occupy()` refuses a named `unit_id` when `! isBookable()`, covering create/group/walk-in (OTA `occupyOrFlag` untouched); one guard, every caller safe (#29-style); mutation-proven.
- **`/app/settings` is a deliberate combined hub** (property + org sections; plan→`/app/billing`, team→`/app/roles`) — not two pages.
- **Winback + Waitlist triggers are honestly deferred** (templates built + previewable, not in the catalog, listener maps only real events) — nothing fake fires.

**Process lessons that held throughout (and each caught real defects):**
- **VERIFY AGAINST THE CODE — the audit AND the reviewer AND the docblock are guides, not gospel.** The audit overstated the work (GATE 11's five pages + most of GATE 12 were already exact-match from #86–#104); a GATE-8 docblock understated a complete backend; a GATE-10 reviewer's own premise was wrong (`PropertyContext::id()` already returns the first-accessible property). Read the code first.
- **BROWSER-VERIFY THE REAL FLOW — green tests + a correct DOM can both hide a real-browser bug.** Caught this pass: a nested `<form>` dropping fields; `toISOString()` UTC day-roll; an `hkEnd()` URL-ordering 404; Alpine `x-show` stripping inline `display:flex`; and a HARDCODED "Verified" badge lying about real state (browser-verify exposed the demo email was actually unverified). Watch: any status badge/label must reflect REAL state; dates from JS use LOCAL components or server-rendered strings, never `toISOString()`.
- **NO TWO PATHS THAT SHOULD AGREE MAY COMPUTE INDEPENDENTLY — one shared rule.** The Inbox compose-drift (preview vs submit picked different reservations); `Unit::bookable()` reconciling the read + the write; the Reports page + PDF sharing `OperatorReportData`; the schedule grid + PDF sharing `RosterWeek`.
- **NEW PII-BEARING FIELDS COME UNDER `anonymize()` — including PII hidden in JSON columns.** The Group-Booking organiser name lived in a `groups.contact` JSON blob that survived erase → fixed with an `organiser_guest_id` FK the anonymize covers.
- **Preserve every honest refusal (#39), honest deferral, bounded guard (#27/#29/#30), and privacy perimeter (#28 owner name-only / #33 door-code guest-only).** Reproduce the DESIGN with REAL data or an honest empty/Soon state — never fabrication.

**Suite state at close:** 1603 / 1610 (the 6–7 failures are the documented pre-existing `BookingImportTest` isolation flakes — 11/11 in isolation, OTA import path, unrelated to any operator work); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean.

**NEXT track:** the GUEST realm's Phase-4 pixel-diff pass (Trips, Reservation view, Manage, Payments, Wallet, Messages, Saved, Review, Receipt — functional-verified, not freshly pixel-diffed). The operator realm is closed.

---

## 2026-08-03 — GUEST realm Phase-4 pixel-diff → COMPLETE · VYROBEL FINISH-TO-100 CLOSED (operator + guest + owner)

The guest Phase-4 pixel-diff pass is COMPLETE: all 10 guest pages (Trips · Reservation · Manage · Payments · Wallet/Credits · Messages · Saved · Review · Receipt) matched to their decoded wireframes across gates G0–G4, all pushed to `main` (HEAD `eefd4a6`). Per-page detail (gate + commit + what matched it + honest deferrals) is the GUEST RESOLUTION LOG in `docs/FULL-AUDIT-OPERATOR-GUEST.md`; the dated summary is in `docs/design/DIVERGENCE-REGISTER.md`. **With this, Vyrobel's finish-to-100 is COMPLETE across all three realms** — operator (`d8e5255`), owner (earlier), guest (`eefd4a6`).

**What "complete" means (honestly):** every guest page is at genuine wireframe fidelity + motion on REAL, guest-isolated, reconciling data. It does NOT mean every deferred integration is live — the remaining Vyrobel work is ONLY the founder's external-infra go-live flips (Channex, Stripe live/Connect/SEPA/IBAN, Push/SMS, KYC, websockets), none of which are UI/fidelity gaps.

**Load-bearing decisions of this pass:**
- **Presentation over an already-real, SHARED backend — no engine change.** Every gate reused the certified engines the operator realm also uses (`AssignmentService`, `FolioService`/`FolioTotals` #17, `ArrivalPack` #33, `ReviewRating` #76, `GuestWalletService`, `InboxService`, `CancellationPolicy`, `PdfService`) and the shared guest data helpers (`GuestTrip`/`GuestReservationView`/`GuestPayments`/`GuestHistory`) — so a page and its sibling can't drift (Receipt vs Reservation folio; Reservation header vs Trips card = ONE `GuestTrip::card`). The only helper changes were ADDITIVE (`GuestTrip::card` gained gradient/countdown/date_range; `GuestPayments::overview` gained `total_charged`).
- **The guest realm has NO Alpine** → all new interactivity is native GET / progressive enhancement (Trips tabs/search/year filter, hero-tile anchor links), never client JS state.
- **Every wireframe fabrication REFUSED (#39), reproduced as an honest empty/Soon:** saved-card vault + Apple Pay + Face-ID (SAQ A — hosted Stripe, test-mode); "Card on file · Expires 04/27"; "Online now"/typing + "~10 min" SLA + "Marco Silva" persona + contact phone (Messages, name-only #28); refer-a-friend program (Wallet); "convert to travel credit" upsell (Manage); waitlist "#N in line" position (Saved); "10% off CORALIS-RETURN" promo (Review). Verify-against-code found Saved + Review + the current Wallet/Messages backends carried NO fabrications already — only Reservation/Payments/Manage/Messages-thread needed guarding.

**Process lessons that recurred (same as the operator finish):**
- **VERIFY AGAINST THE CODE — audit/reviewer/DOCBLOCK are guides, not gospel.** Payments' controller docblock claimed card charge was "deferred · Soon", but paying is LIVE (Phase-8 hosted Stripe) → fixed the stale docblock. Several "pending" pages were already honest + close (Saved, Review, Wallet).
- **BROWSER-VERIFY THE REAL FLOW — the RAW-BLADE TRAP re-bit on Reservation** (`Check-in@if(...)…}}@endif` — the `@if` after a word char didn't compile but the `}}@endif` did → orphaned `@endif` → 500; rewrote as inline ternaries). #33 door-code withholding + #17 reconciliation were confirmed in a live browser, not just tests.
- **Guest invariants held:** #28 isolation (foreign reservation/thread/booking → 404, mutation-proven) + name-only owner-facing; #33 window-gated door code (withheld >48h out, browser-proven); #17 folio/receipt/payments reconcile to the cent (Room €300 + VAT €69 = €369 = paid); archive-not-destroy (the 14-day delete + anonymize covers the thread bodies).

**Suite state at close:** guest suite 233/233; full suite green modulo the documented pre-existing `BookingImportTest` isolation flakes (OTA import path, 11/11 in isolation, unrelated); PHPStan L5 (0); Pint clean (a pre-existing import-order drift in `tests/Feature/GuestAccountTest.php` was tidied); `migrate:fresh --seed` clean.

**➡ THE VYROBEL FINISH-TO-100 IS CLOSED.** All three realms are at genuine design fidelity on real, reconciling, isolated data under the honesty rule. The next Vyrobel work is deployment + the founder's provider-account go-live flips — not UI.

---

## 2026-08-04 — Full-E2E QA (real-browser) → QA FIX PASS: all 7 findings RESOLVED

A complete Playwright-MCP-driven QA of every realm + page (`docs/QA-FULL-REPORT-2026-08-04.md`) found the product strong — money reconciles to the cent, privacy perimeters (#28/#33) hold, cross-realm/tenant isolation holds — with **7 findings** (1 HIGH, 1 MEDIUM, 3 LOW, 2 TRIVIAL). All 7 were then fixed, one per gate, each verified in a real browser + covered by a test, invariants + honest refusals/deferrals preserved. On `main`.

- **F-GUEST-1 (HIGH, `1e564df`)** — the storefront "pay on arrival" **fabricated a paid-in-full folio**: `reserve()` branched on `config('services.payments.gateway')` and recorded a full `'offline'` payment for every non-Stripe booking, so a guest told "no card is collected here" got a folio marked paid (balance €0) and a "Payment received" confirmation. **Fix (removes a fabrication, #39):** `payment_option ∈ {card,on_arrival}` is now a REAL submitted field (a genuine chooser when the Stripe rail is wired, an honest hidden `on_arrival` when it isn't); pay-on-arrival records **no** payment → balance stays DUE (reconciles #17) and the confirmation honestly says "Pay on arrival"; card is honored only when `gateway=stripe` (unchanged test-mode hosted Checkout, fulfil-on-webhook). Corrected the two tests that encoded the old auto-settle; added regression tests (0 payments + balance due, both configs). Browser+DB verified.
- **F-OP-1 (MEDIUM, `e492537`)** — Front Desk + Calendar opened on the **furthest-future** booking (`->max('check_in')` → 13 Dec), not today. **Fix:** one shared `App\Support\Schedule\BoardDate` rule — today when there's current/upcoming occupancy, else the most-recent-PAST arrival for a stale dataset, never the furthest future; both components consume it (anti-drift). Pure unit test + a front-desk feature test; browser-verified both open on 3 Aug.
- **F-OP-2 (LOW, `9b8828d`)** — Dashboard Channels card showed on an Essential org that 403s at `/app/channels`. **Fix:** gate the card on the same `Organization::planAllows('channels')` (omitted on Essential). Card data was already honest.
- **F-OP-3 (LOW, `9b8828d`)** — controller-rendered pages showed a generic "Operator / O" chip. **Fix:** `<x-operator.shell>` derives the chip from the real authed staff user (self) on every page — one rule, no drift.
- **F-OP-5 (LOW, `9b8828d`)** — reservation-detail claimed "Move room … deferred until it persists safely", but the guarded `app.reservations.move → AssignmentService` form works. **Fix:** corrected the stale note (persists immediately, locked, no overbooking).
- **F-OP-4 (TRIVIAL, `1f47ee3`)** — email preview "Booking confirmation" showed €0.00 (a folio-less booking). **Fix:** `sampleReservation()` prefers the latest booking with a real total → shows €264.10.
- **F-DOC-1 (TRIVIAL, `1f47ee3`)** — docs said `/book/vyrobel-demo` (the ORG slug, which 404s). **Fix:** corrected to `/book/seaside-demo` (property slug) in CLAUDE.md/PROJECT-STATE/RESUME/QA-WALKTHROUGH; no storefront-routing change.

**Process (the finish-pass discipline held):** verified each finding against the code before fixing; browser-verified every UI flow (the F-GUEST-1 fabrication was itself a "green tests + correct DOM hide a real bug" case); one shared rule per concept (BoardDate, the shell chip); nothing money/auth/isolation changed except REMOVING the F-GUEST-1 fabrication.

**Suite state at close:** 1616/1623 green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. The 7 non-green are pre-existing, unrelated flakes — 6 `BookingImportTest` ordering flakes (11/11 in isolation) + 1 `OwnerUnitDetailViewTest` random inventory_block collision — none in any file this pass touched (proven by the 18-file diff scope).

---

## 2026-08-04 — QA D2-OBS-1 fixed: distinct on-desk payment method (cash vs bank), money untouched

The deep-QA's one LOW observation (D2-OBS-1) is resolved. The on-desk Collect stored the real Cash/Bank
choice only inside `payments.metadata` while the queryable `gateway` read a generic "manual". Added an
additive, nullable **`payments.method`** column (backfilled from `metadata->method` for historical rows),
populated by `FolioService::recordPayment` from the operator's choice; a `Payment::methodLabel()` helper
renders it, surfaced on the reservation-detail folio ledger ("Received · Cash" / "· Bank transfer"). This is
a LABEL/reporting change only — the amount, integer-minor storage, #17 reconciliation, and the double-submit
idempotency are all untouched; the card path stays the honest test-mode Stripe seam (never faked). Browser +
DB verified (Cash→`cash`, Bank transfer→`bank_transfer`, distinct not `manual`, money unchanged, folio
reconciles); a regression test asserts the method is stored distinctly with money unchanged. Suite
1617/1624 (the 7 non-green are the documented pre-existing BookingImportTest + OwnerUnitDetailViewTest flakes,
unrelated); PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean.

## 2026-08-08 — ⛔ PRICING MODEL UNRESOLVED: a hard blocker on the Stripe live-key flip (founder decision: defer)

The post-audit polish pass surfaced that **two independent, contradictory pricing models run
simultaneously**, and only one of them reaches Stripe:

| | Definition | Where it is shown | Reaches Stripe? |
|---|---|---|---|
| **A · flat tier** | `config('billing.tiers')` — Essential €49/mo, Pro €99/mo | paywall · checkout · tier cards | **YES** — `stripe_prices[tier][interval]` is what a subscription is created against |
| **B · per-unit** | `SubscriptionPricing` — active units × `per_unit_minor` (€9) × VAT | operator Billing "what you pay" · Settings "Your plan" | no |

For the demo org the two disagree materially: **B quotes ~€44/mo where A charges €99/mo.** Today that is
harmless because no charge is live. **At the live-key flip it becomes a customer being billed roughly
double what the app told them** — the same shape as the H1 refund race: inert now, unrecoverable later.
Both figures are honest, real code; neither is fabricated. What is missing is the *product decision* about
which model Vyrobel actually sells.

**STATUS: ⚠️ OPEN — AWAITING THE FOUNDER'S DECISION. No pricing code changed in this pass.**

⚠️ **RECORD-KEEPING CORRECTION (2026-08-08).** An earlier revision of this entry read "DECISION (founder): DEFER."
That overstated it: the recommendation to defer is the **assistant's**, pending the founder's confirmation. Treat
this as **UNRESOLVED and awaiting the founder** until they confirm in their own words.
Choosing between A and B is a commercial decision, not a defect fix, and picking one silently would have
been inventing a product decision — worse than inventing data. Recorded here rather than resolved.

⛔ **THIS IS A GO-LIVE BLOCKER, not a backlog item.** It must be resolved BEFORE `STRIPE_KEY` is switched to
a live key, and it is now listed alongside the founder's other cutovers. Resolving it means either:

- **adopt A** — the per-unit figure stops being presented as the bill (UI-only change; nothing that reaches
  Stripe moves); or
- **adopt B** — the tier cards/paywall are rebuilt to quote per-unit AND the Stripe integration moves to
  quantity/metered prices, because the tier Price IDs would no longer describe the charge (a real
  billing-integration workstream, not a UI fix).

Everything else about billing is unchanged and remains correct: the trial is real, access-gating fails
open on a missing record, the live charge stays account-gated, and no org is ever flipped to active off a
fake payment (#41).

## 2026-08-08 — POST-AUDIT POLISH PASS: all 26 parity gaps closed (or honestly reclassified)

The last of the three post-audit passes (blockers → hardening → **polish**). Six gates, each committed and
pushed on its own, each dual-method verified (code read + real browser) and each shipping a regression test
aimed at the exact blind spot that let the defect through.

| Gate | Commit | What |
|---|---|---|
| 1 | `78de41d` | **Technician pool** — 3 pickers **+ 3 server-side write guards** onto the shared `StaffRoster`. The guards were the half that mattered: a crafted POST still assigned work to an External Owner after the lists were fixed. Owners excluded by ROLE, not by a missing membership (#28). |
| 2 | `4f27c81` | **WCAG AA contrast** — `--vy-muted` 3.77→5.66, `--vy-faint` 2.68→4.60, computed against the TRUE worst-case background (the card's `rgba(255,252,248,.58)` composited over all four gradient stops → `rgb(249,223,207)`), across 13 files. |
| 3 | `c5babfc` | **Guest portal responsiveness** — the shell had ZERO viewport breakpoints; the header forced a 615px document at 390px, a **225px overflow on all 14 guest screens**. Now 0px on all 14, nav scrolls inside itself, 44px tap targets, reduced-motion intact. |
| 4 | `f0e606c` `0925714` | **Guest PDFs** — the VAT invoice's statutory supplier identifiers (the data was already seeded; it just never rendered), and the Statement of Account rebuilt as a statement with a real **Credited** tile from the same `FolioTotals` pass. |
| 5 | `c6b6788` `4142a05` | **Owner + admin/auth** — the tax-residency identifier defect (a **money** defect: a name matched no configured rate → silent ZERO withholding), the payment-reminder brand regression, the arithmetically impossible preview, and System-states verified closed in all 5 realms. |
| 6 | `82506bb` | **LOW/TRIVIAL sweep** — the corrupted date format **and the hardcoded stay times underneath it** (check-out said 10:00 where the property says 11:00 — a false fact, not a formatting bug), the double-escaped heading, raw JSON on a legal document, and the guest Help/Preferences/Documents trio resolved by evidence. |

**Four shared definitions were extracted, never a copy added** — `OperatorLetterhead` (5 hand-rolled letterhead
copies existed and had ALREADY drifted), `Countries` (the ISO code ⇄ name contract), `StayTime`, and
`StaffRoster::staffUserIds()/assignableStaff()`. `FolioTotals` gained `received_minor`/`refunded_minor` — the
two halves it already computed and discarded — so the new Credited tile could not become a second derivation.

**Honesty held throughout (#39).** Three of the reported "gaps" were closed by INVESTIGATION rather than code:
guest email preferences and the documents hub already existed at a different location, and guest Help was
given a real destination rather than a page of invented FAQ answers. Nothing was fabricated to fill a gap; the
40 RATIFIED divergences were not touched.

⛔ **ONE GAP WAS DELIBERATELY NOT CLOSED** — the pricing-model contradiction (see the entry above). Closing it
required choosing what Vyrobel sells, which is a product decision, not a defect fix. Escalated to the founder,
for decision; the assistant's recommendation is to settle it at go-live. It is a **blocker on the Stripe live-key
flip**, not a backlog item, and it remains **OPEN** until the founder confirms which model Vyrobel sells.

**FINAL VERIFICATION:** full suite **1692/1699 · 11,540 assertions**. The 7 non-green are EXACTLY the
documented pre-existing set — 6 × `BookingImportTest` (which **pass in isolation**: a test-ordering artifact)
and `OwnerUnitDetailViewTest` (a real pre-existing failure, already on the audit backlog). Both categories were
confirmed identical on clean HEAD with this pass's changes stashed, so nothing here introduced them. PHPStan L5
(0); Pint clean; `migrate:fresh --seed` clean.

---

## 2026-08-10 — DESIGN-PARITY **P27 · Operator Portfolio** (`/app/portfolio`, `can:properties.view`)

**Presentation-only.** No metric math, no scope mechanism, no money path changed. `docs/design/MISSING-BACKEND-BY-PAGE.md` §27.

**Fixed (design):** the design's full-bleed gradient card **band**; label-above-value **stats** with the design's
dividers; a per-card **occupancy rail** (real, `aria-label`led); the design's **chip row** — including the
**arrivals count that was computed but never rendered**; **"Full report ›"**, which the design has and the
component **already had a gate for** (`canViewAnalytics`) but never rendered — now gated on `reports.view`, the
same permission `/app/reports` requires (#30); **card stat labels carry their period** (the cards are *this
month*, the table below is *this year*); a stale docblock.

**⚠️ Matched the design's structure, NOT its contrast defect.** The prototype's band runs `#E8703A→#C2410C` with
white ink — white on the `#E8703A` end measures **3.10**, under the 4.5 bar for the 15px name. Ours runs from the
deep end (`#C2410C→#9A330A`): measured **5.18** / **4.68**. Separately, `.vy-prop-current` ("Working here") was a
**real pre-existing AA failure at 4.43** — now 5.18, and `nowrap` (it wrapped mid-phrase at 1000px).

**⚠️ "—" for an average over zero — this is the DESIGN's rule, not an inference.** The prototype's freshly-added
property renders `adr: '—'` and, in the table, `occ/adr/revpar: '—'`, while revenue stays a real `€0`. Live now
matches: ADR `—` when `roomNightsSold == 0`, occupancy/RevPAR `—` when `availableRoomNights == 0`, and **revenue
keeps its computed zero** (the Owners-list ruling: a computed zero is not a missing zero).

**⚠️ SCOPE-SAFETY DIVERGENCE (deliberate).** The prototype deep-links each card to *Dashboard · Calendar*. Our
property scope is the **session** `PropertyContext`, so those links from a non-current card would silently render
the property the user is actually working in. They render **only on the current card**; elsewhere the card offers
**Open** — the real switch, validated against the accessible set (foreign → 422).

**⚠️ A GREEN TEST WAS DEFENDING NOTHING.** The pre-existing "#17 reconciles the portfolio to the sum of properties"
test was **vacuous**: `seedScenario()` books nothing, so it asserted `€0.00` against a page of zeros — **proven by
mutation** (inflating the rollup by €9,999.99 left it green). Replaced with real, distinct figures
(€100 + €250 = €350 against a foreign org holding €999), **proven to discriminate**: a **one-cent** break in
Σ==total turns it red.

**⚠️ #38 is held by TWO independent layers, verified.** `PropertyContext::accessibleIds()` *and* `Property`'s global
tenant scope. Disabling **either alone** leaves the page correct; the test only goes red when **both** are removed.
Defence-in-depth measured, not assumed.

**⚠️ Also recorded, NOT fixed here:** ⑴ the Add-property modal says *"billed per unit"*, asserting one of the two
contradictory pricing models — **surfaced, deliberately unchanged**, because editing it either way picks a side of
the ⛔ **open billing decision**. The live modal asserts **no price**, unlike the prototype's *"+14 units × €9/mo ·
prorated from today"*. ⑵ The operator shell's **topbar H1 overlaps the search box at ≤1000px** — **proven
pre-existing** by stashing and re-measuring on clean HEAD; realm-wide, not this page. ⑶ **No provisioned role holds
`properties.view` without `reports.view`**, so the "Full report" gate's negative branch is unreachable via the stock
catalogue; the test revokes the permission directly so it still discriminates.

**Refused / logged (5):** the hardcoded **"+9%"** revenue delta (**D3-4 class** — no comparable prior period);
per-property **"4 unread"**; the post-add **setup checklist** (partly Channex-tied); the modal's **per-unit price +
proration** (billing decision); **rank-tinted bands** (encodes a ranking in colour alone, WCAG 1.4.1 — the table
already ranks numerically).

**Verification:** `OperatorPortfolioTest` + `OperatorPortfolioGate1Test` **14/14 · 58 assertions** (3 added);
PHPStan L5 **0**; Pint clean; **0 console messages**, **0 horizontal overflow** at 1440 and 1000; contrast measured
across every visible text node (**0 failures**, band measured against its lightest stop). Schema untouched, so no
`migrate:fresh` was needed. Seed unchanged (2 properties / 42 reservations) — the Add-property modal was observed
**read-only** and never submitted.

⚠️ **Baseline correction recorded the same day (J.quater).** The full suite measured **1809 tests / 11,896
assertions**, with **5–9** pre-existing failures depending on run order and date — **two full runs in one session
gave different failure sets.** `BookingImportTest` **passes 11/11 in isolation** and fails only in-suite (ordering
/ pollution); `OwnerUnitDetailViewTest` + `StubViewsTest` fail on the date-coupled seeder collision. None touch
Portfolio. **Stop citing a fixed baseline integer — isolate a failing file before calling it a regression.**

---

## 2026-08-10 — DESIGN-PARITY **P28 · Operator Search** (`/app/search`, `can:reservations.view`)

**Presentation-only.** No query, scope or row projection changed — `SearchService` is untouched.
`docs/design/MISSING-BACKEND-BY-PAGE.md` §28.

**⚠️ THE REFUSAL THAT DEFINES THIS PAGE.** The design's guest row carries **`lukas.meyer@mail.com · Munich,
Germany`**. Contact PII renders on the **Guests CRM only** (list + profile); search is **not** a permitted PII
surface, and it is the one screen that aggregates guests, reservations, owners and units into a single list —
the easiest place to widen #28 by accident. Refused, and now **pinned by three mutation-proven tests**: no guest
contact on a guest row, none riding along on a reservation row, and no owner contact **or IBAN — not even the
masked `iban_last4`** on an owner row. Adding the design's email back turns them red.

**PII perimeter — measured before AND after, unchanged:** 0 emails · 0 phones · 0 IBANs · 0 masked accounts ·
0 `mailto:`/`tel:` links. No field added; nothing decrypted that was not already.

**Fixed (design):** the single pill query bar (leading magnifier, in-field clear ×); **filter chip rows**
(Status / Source / Occupancy) replacing native selects, plus a labelled Dates range; the results header
(*"N results for "q""* + Sort segmented + Clear all filters); group headings with counts; rows rebuilt with the
design's icon tile, two-line title/sub and chevron; Recent searches restyled.

**⚠️ #20 — the chips are LINKS, deliberately.** Every chip is a plain `<a href>` GET carrying `q` **and every
other active filter**, so filters compose rather than clobber and the tenant/property middleware re-runs on each
change. **Zero `wire:click` on the page**: `/livewire/update` does not run the property-context middleware, so a
wire-action filter would silently drop the scope. Verified live (`?q=Demo&status=confirmed&source=direct`;
42 → 38 results).

**⚠️ Tokens — the render-type predictor is NOT the whole story.** This is a Livewire page with **zero raw hex**
that still failed AA **8×**: `.vys-badge` used `color: var(--vy-accent)` at 10.5px and measured **4.41**. The
controller-vs-Livewire predictor tracks *un-tokenised hex*; **accent-as-text is an independent risk**. Now
`--vy-accent-text`, 0 failures. **Measure regardless of render type.**

**#38 verified:** results are org-scoped (the existing cross-org test is genuinely discriminating — dropping the
`Guest` global scope makes it fail), and a **foreign drill-down 404s** (new test, asserting the actor's OWN
records 200 first so the 404 cannot be a masked 403). ⚠️ **Note the contrast with P27:** Portfolio's isolation
survives losing either of two layers; **Search's is single-layer** — the model global scope is the only net.
There is also **no Scout/Meilisearch dependency**, so no shared index to mis-scope.

**Refused / logged (6):** the guest-row email + city (#28) · a **Documents** result group (⚠️ if built, a folio
receipt needs its own permission, not the page's `reservations.view`) · reservation-row stay dates + unit name ·
a per-guest "2 stays" count · Dates month-presets · a **"Relevance"** sort (nothing scores relevance — offering
it would be a false claim about ordering).

**⚠️ Recorded, not fixed:** *"Recent searches"* persists **guest names** in `localStorage`, surviving logout —
and a shared front-desk terminal is the normal deployment, so the next operator can read the previous one's
searched guest names. Real feature, browser-local, but a genuine #28-adjacent exposure that deserves a
deliberate decision (clear on logout, or drop it). Also recorded: **reflected input is not disclosure** —
searching *by* an email echoes it into the input; the test therefore asserts on a value the operator did not
type (the phone).

**Verification:** `OperatorSearchTest` + `OperatorScheduleSearchGate2Test` **15/15 · 70 assertions** (5 added);
PHPStan L5 **0**; Pint clean; **0 console**, **0 horizontal overflow** at 1440 and 1000; contrast re-measured
(8 → 0). States checked: results, **no-results** (honest, no fabricated "did you mean"), **no-query** (recent +
real upcoming arrivals). Schema untouched — no `migrate:fresh` needed. No app/ code changed.

---

## 2026-08-10 — DESIGN-PARITY **P29 · Operator Notifications** (`/app/notifications`, self-only feed)

**Presentation + one a11y correction.** No backend, no `NotificationFeed`, no route touched.
`docs/design/MISSING-BACKEND-BY-PAGE.md` §29.

**Fixed (design):** the design's toolbar — an inline **unread pill** beside the heading and the tabs collapsed
into **one segmented shell** (active = raised white pill) instead of loose buttons; rows rebuilt with the
**category as a chip beside the title**, the unread dot at the row's right edge, and the styling moved out of
inline attributes into `.vyn-*` classes.

**⚠️ WCAG 1.4.1 — the unread state rode on colour alone.** The dot carried only `title="Unread"`, which is **not
reliably announced on a non-interactive span**, so for assistive tech "unread" was colour + font-weight only.
The dot is now `aria-hidden` decoration with real `.vy-sr-only` text, and the ✓ button gained an `aria-label`
naming its notification. **Mutation-proven** (restoring the `title`-only dot turns the new test red).
`.vy-sr-only` is defined **once** in the operator layout — a generic utility, not a page concern.

**⚠️ THE DESIGN SOURCE CONTRADICTS ITSELF, and the product is the more coherent side.** Measured in the browser:
*Operator Notifications* renders the row icon as a **38px circle**; *Owner Notifications* renders the same
component as a **34px rounded square**. The live product has ONE shared `<x-notifications.category-icon>`
(rounded square) used by the operator page, the owner page **and** the bell panel. Matching the operator
prototype would break owner parity and fork one concept into two definitions — precisely what the anti-drift
rule forbids. **Kept the shared tile; documented the conflict.** New rule: when two prototypes disagree, the
single shared definition wins.

**⚠️ Self-scope + mark-read, READ NOT FIRED.** `NotificationController` writes through
`$request->user()->notifications()->findOrFail(...)` — a cross-user id **404s by construction**. Nothing was
marked read, marked-all-read or dismissed: the dev feed is identical before and after at
**242 total / 242 unread / 0 read**.

**⚠️ The unread count is single-sourced.** The only unread-count queries in the codebase are the two inside
`NotificationFeed`; no view derives its own. The bell renders **"9+" as a display cap** with
`aria-label="{{ $unread }} unread"` carrying the real number, and counts stay **whole-set, not page-set**
(already tested) — which is why the pill can read "59 unread" while a filtered view shows nothing.

**Refused / logged (5):** the footer's *"or **SMS**"* (no provider — deferred §H; live already omits it) ·
the mock rows, including *"Expedia disconnected · sync stopped 41 min ago"* — ⚠️ **the F-OP-2 / D26-1 sync
fabrication arriving through a different door**, a notification asserting a sync that has never run ·
fabricated **Channels / Housekeeping** category pills (the tab row is built from the REAL distinct categories) ·
the circular icon (above) · per-row **dismiss/delete** (only mark-read exists).

**⚠️ Realm-wide finding, logged not fixed:** the identical dot-only unread pattern remains at
`resources/views/livewire/owner/notifications.blade.php:33` and, shared by both shells,
`resources/views/components/notifications/bell.blade.php:46`. Same defect, two surfaces; they should be closed
together so the panel and the page do not disagree about how unread is announced.

**Verification:** notification suites + the token-contrast guard **28/28 · 117 assertions** (2 tests added);
contrast measured **0 failures** before and after (this page was already clean — unlike Search, also Livewire,
which failed 8×: **render type predicts neither risk**); PHPStan L5 **0**; Pint clean; **0 console**,
**0 overflow** at 1440 and 1000; states checked: feed, filtered, honest empty. Schema untouched — no
`migrate:fresh` needed.

---

## 2026-08-10 — DESIGN-PARITY **P30 · Operator Notification Preferences** (`/app/notification-preferences`)

**Presentation + two real accessibility fixes.** No backend, no save semantics, no channel behaviour changed.
`docs/design/MISSING-BACKEND-BY-PAGE.md` §30.

**⚠️ Channels, read from the code:** `NotificationPreferences::CHANNELS = ['in_app', 'email']` — **only two**.
SMS and push are not merely disabled; **they do not exist in the persistence model**, so a preference for them
structurally cannot be stored. Email delivery is real (Mailables, mutation-proven); in-app is real (P29).

**⚠️ Storage model verified: TYPED per-user rows, not JSON.**
`notification_preferences (user_id, event_key, channel, enabled)` written with a targeted `updateOrCreate` per
row; the controller iterates the CATALOG rather than the submitted keys, and always-on events are forced enabled
in the service. **The WAVE-1 wholesale-replace hazard is structurally absent** (the Maintenance-Settings case).
Self-only: saved for `$request->user()` with the audience from the *route default*, never the request.
**No save was submitted:** the table is unchanged at 0 rows (checksum `d751713988987e9331980363e24189ce`).

**⚠️ WCAG 2.1.1 (Level A) — the page's ONLY real controls were unoperable by keyboard.** `.vy-np-check` was
`display: none`, which removes an input from the tab order *and* the accessibility tree. **Proven in the
browser:** `focus()` left `document.activeElement` as `BODY`, and the label wrapper was not focusable either —
so a keyboard-only user could not change a single notification preference, on the page that exists to change
them. Now visually hidden but **focusable**, with a `:focus-visible` ring and a real `aria-label` per toggle;
15/15 in the tab order. ⚠️ **Contrast measured 0 failures on this same page, before and after** — an
accessibility pass that only measures colour would have missed a Level-A failure entirely.

**⚠️ WCAG 1.4.1 — the "no provider" state rode on opacity alone.** The disabled Push/SMS cells were empty spans
carrying only a `title` (not announced on a non-interactive element) — **the same defect found on the feed at
P29**. They now carry real `.vy-sr-only` text, reusing the utility P29 added.

**⚠️ #30 — the new breadcrumb is gated.** `/app/settings` requires `properties.manage`, but this page carries
**no permission gate** (it is self-only), so most users who can set their own preferences cannot open Settings.
An ungated crumb would have invited a 403. Linked only under `@can('properties.manage')`, tested **both ways**
(Group Admin: link present, route opens; Front Desk: no link, route 403s).

**Fixed (design):** the two-column body (matrix left; Quiet hours + Daily digest in a right rail), the
breadcrumb, and a **top-right Save** — the foot Save is deliberately kept too, since the matrix is long and
removing it would make a keyboard user tab back up the whole grid to submit. Channel cards stack at 1100px
(below that the "not set up" tag and the email address wrapped mid-phrase).

**Refused / logged (4):** **Push shown as live** — *"This device · Chrome"* + a green **On**, with 8 grid cells
checked (no FCM/APNs) · **SMS shown as live** with a **verified phone number** (no Twilio) — ⚠️ note the
compounding claim: it asserts not just delivery but a *verification flow that has never run* · a **18:00
digest** send-time (the digest job does not dispatch at all yet, and the page says so) · the mock's shorter,
description-less event list (**EXTRA-KEEP** — live's descriptions say what actually triggers each notification,
and its extra rows are real wired events).

**⚠️ Sweep run, and it came back negative — recorded because it was actually checked.** The keyboard defect is
**not** realm-wide: no other class matching `*check/radio/switch/toggle` uses `display: none`; the sibling
`.vyms-switch input` uses `position: absolute; opacity: 0`, which stays focusable and is correct.
`.vy-np-check` was the only instance. (The P29 1.4.1 finding at `owner/notifications.blade.php:33` and
`bell.blade.php:46` does remain open.)

**Verification:** prefs + notification suites + the token-contrast guard **29/29 · 155 assertions** (3 added,
all mutation-proven); contrast **0 failures** before and after; PHPStan L5 **0**; Pint clean; **0 console**,
**0 overflow** at 1440 (3 channel cards, 683/440 two-column) and 1000 (single column). Schema untouched — no
`migrate:fresh` needed.

---

## 2026-08-10 — DESIGN-PARITY **P31 · Operator Email Templates** (`/app/email-templates`)

**Presentation + one contrast fix + one presentation-only controller field.** No Mailable, no send path, no
#83 tenant-restore, no save semantics touched. `docs/design/MISSING-BACKEND-BY-PAGE.md` §31.

**⚠️ The honesty flips here — email really sends**, so the checks were merge-variables, the preview source and
fabricated engagement, not deferral.

**Merge variables verified REAL:** `guest_name, first_name, unit, nights, reference, balance` — each a real
reservation/guest field the send path fills (existing test: *"resolves merge variables from the real
reservation/guest"*). No token without a backing field.

**⚠️ Preview source verified — and it is NOT a #28 widening.** `EmailPreviewSample` deliberately renders over
the tenant's **real latest reservation** rather than placeholders, so preview == what actually sends. Probed
output: guest **NAME**, property, dates, reference, money — **0 email addresses, 0 phone numbers**. That is the
same name-only level the permitted operator surfaces use, tenant-scoped. Recorded as an observation: it *is*
real customer data on an editor surface, so a future "To:" line in the preview chrome would cross into
CRM-only territory.

**⚠️ Both accessibility axes measured.** **Contrast: 7 failures**, all the "● Active" pill at **4.41** (raw
`#1B7F5C` on `#E7F5EE`) — the controller-page raw-hex risk realised; **fixed → 6.23**. **Keyboard: 21 visible
page controls, 0 unreachable, 0 unnamed**, all with a focus indicator. ⚠️ My first keyboard probe reported 12
"unreachable" — **a false positive**: they were inside the *closed* bell panel and user menu, which correctly
cannot take focus. **Scope the keyboard probe to visible controls.**

**Fixed (design):** the card grid with thumbnail headers (was a flat list), the breadcrumb (**#30-gated** —
`/app/settings` needs `properties.manage`, this page has no gate), and **"Preview emails"**, which links
`app.email-previews` — **a real gallery route that existed and was never linked from here**. The status toggle
became the design's switch: same form, same guarded route, `role="switch"` + `aria-checked` + `aria-label`,
with the word Active/Off still shown (1.4.1). Restyled, not rewired.

**⚠️ A defect I introduced and caught before it shipped:** the paused-card thumbnail tint started at `#8C837C`,
where the white brand text measures **3.77**. No template is off in the demo, so it would only have surfaced
the day someone paused one. Darkened to `#7A716A` → **4.78**, verified by forcing the state in the DOM.
**Measure the states your own change introduces, not just the one on screen.**

**Refused / logged (5):** per-card **engagement stats** (*"72% opened"*, *"38% → review"*, *"64% settle
early"*) — **no open/click tracking exists anywhere**, so every figure would be invented on the surface an
operator would most trust; the page says why instead · **"+ New template"** (the catalogue is 7 fixed keys each
bound to a real Mailable + trigger; a template with no trigger never sends) · a **Draft** state (the model has
Active/Off) · the **winback "Come back next season"** card and the **"Email + SMS"** chip — ⚠️ **no
`WinbackMail` exists, and if one is ever built it MUST go through `GuestMarketing::canReceive` + the
unsubscribe path** · the mock's 6 cards (**EXTRA-KEEP** — live shows all 7 real templates).

**⚠️ The CSS-comment trap bit again.** A guard written as `not->toContain('click-through')` failed against a
**CSS comment** in the operator layout about the drag-to-reschedule drop layer — CSS comments render, Blade
comments do not, exactly as at `638e67a`. Scoped to the actual claim.

**Nothing fired:** `email_templates` unchanged at 0 rows (checksum `d751713988987e9331980363e24189ce`), and
**no mail was dispatched** — the newest Mailpit message predates this gate by 16 hours.

**Verification:** email-template + preview + token-contrast suites **21/21 · 107 assertions** (3 added, both new
guards mutation-proven); PHPStan L5 **0**; Pint clean; **0 console**, **0 overflow** at 1440 and 1000. Schema
untouched — no `migrate:fresh` needed. Also fixed a stale docblock ("the 5 real guest emails" — it is 7).

---

## 2026-08-10 — DESIGN-PARITY **P32 · Operator Email Editor** (`/app/email-templates/{key}/edit`)

**Presentation only.** No Mailable, no #83 tenant-restore, no persistence, no save/test semantics touched.
`docs/design/MISSING-BACKEND-BY-PAGE.md` §32.

**⚠️ NOTHING FIRED — the whole point of this gate.** `email_templates` unchanged at **0 rows**, checksum
`d751713988987e9331980363e24189ce` before and after; **no mail dispatched** (Mailpit still 500 messages, newest
`2026-08-09T11:17:59`, ~16h older than this gate). The single interaction — typing into Heading to prove the
live-preview binding survived the restyle — is **draft-only**: the component has **no mutating Livewire
action**, and a reload discarded it with the checksum unchanged.

**Merge variables REAL** (the catalogue's own `vars`, each merged by the Mailable). **Test-send recipient is
`$request->user()->email` — there is no recipient input at all**, so it cannot be aimed at a guest even
deliberately; now covered by a `Mail::fake()` test asserting it goes to the operator and NOT to the guest.
**Preview probed inside the iframe: 0 emails, 0 phones, 0 mailto/tel** — name-only, the P31 bar.

**Fixed (design):** the action bar — back-link, title + status, **both actions at the top-right**. ⚠️ The
buttons sit outside their forms and reach them with the HTML5 **`form=`** attribute: the same two real POSTs,
repositioned, **nothing rewired** (guarded by a test asserting both form ids AND both real actions). The foot
Save stays (long form, keyboard). Block toggles became the design's **switches** — still real, **focusable**
checkboxes (`appearance:none`, **never `display:none`** — the P30 defect) with a `:focus-visible` ring, named
by their wrapping `<label>`.

**⚠️ Contrast 5 → 0.** The "● Active" pill measured **4.41** — **the same raw `#1B7F5C` pill the templates LIST
carried at P31**. One visual concept written twice, so the P31 fix never reached here. The variable-insert
chips measured **4.19** (accent-as-text at 11.5px). Both fixed; inline error text moved off `--vy-accent` too.
**Realm-wide note:** a shared `<x-status-pill>` would stop the next divergence; not extracted here.

**⚠️ A real responsive defect fixed.** The two-column breakpoint was `960px`, so at a 1000px viewport each
column was ~341px and **the preview iframe clipped** — the message cut mid-sentence and the Stay/Unit/
**Reference values** hidden behind an inner scrollbar. **The page reported no overflow, because the clipping
was inside the iframe.** Raised to `1180px`; measured after: single column, 653px iframe, not clipped.

**⚠️ FOUND AND LOGGED — the preview has TWO sample sources.** The **subject/prose** merge *hardcoded
placeholders* (`mergeSample` → `Alex Traveller`, `Apartment 2B`, `VY-2043`) while the **body** renders the
**real** sample reservation. Measured on one screen: reference **VY-2043** in the subject vs **R-F7ERPHS4** in
the body; unit **"Apartment 2B"** in the prose vs **A2** in the stay table — two references and two units for
one booking. It **undercuts the component's own docblock** (*"what you see IS the send … cannot drift"*): the
blade cannot drift, the merged text does. **Send-time is unaffected** (the Mailable merges real values), so it
is preview-only — but an operator proof-reading sees a contradiction. **This is why I did NOT add the design's
"Previewing as Lukas · VYR-10430" pill**: it would assert a single identity the preview does not have.

**Also logged:** the "Sofia's signature" block + hero-image band (no signature entity, no image asset); free
rich-text body editing (**EXTRA-KEEP** — field-scoped editing is safer in an email that really sends and keeps
the tested layout).

**⚠️ A false positive I corrected:** the keyboard probe first reported 3 unnamed block checkboxes; they are
named by a **wrapping `<label>`** (implicit association), which my check didn't consider.

**Verification:** email-template + preview suites **16/16 · 69 assertions** (4 added; the two structural guards
mutation-proven); PHPStan L5 **0**; Pint clean; **0 console**; **0 overflow** at 1440 (two columns) and 1000
(one column, preview un-clipped). Schema untouched — no `migrate:fresh` needed.

---

## 2026-08-10 — DESIGN-PARITY **P33 · Operator Profile** (`/app/profile`, self-only)

**Presentation + one truthful relabel + one shared-token fix.** No account logic, no password path, no
persistence touched. `docs/design/MISSING-BACKEND-BY-PAGE.md` §33.

**⚠️ Self-scope is structural.** Writes resolve `$request->user()` and **no user id appears anywhere in the
request**, so there is no foreign id to tamper with; email is unique *ignoring self*; the audit records which
fields changed, never their values. **Nothing was saved:** the user-row checksum is
`6d4564c6315add9d447a2c3dda5a1967` before and after, preferences unchanged.

**Password/2FA are on the SECURITY page** (`POST /app/security/password`, `/app/security/2fa/*` — GATE 24).
This page links to them and does not duplicate them, so the 12-char policy and `changePasswordTo`'s
sibling-session termination are untouched.

**⚠️ REFUSED — the design's sign-in LOCATION.** It shows *"Last sign-in — Today 08:14 · **Faro, PT**"* and
*"This device — Chrome · macOS · **Faro, PT**"*. **No geolocation capability exists anywhere** — no
geoip/maxmind/ipinfo package, no config, no column; `sessions` stores `ip_address`, `user_agent`,
`last_activity` and nothing else. ⚠️ **This is the worst class of fabrication on this page:** a city beside a
sign-in reads as *security evidence* — it is precisely what a user checks to judge whether an unfamiliar
session is theirs. Live shows the **real IP** and the real relative time. Also refused: *"Password — Changed 3
months ago"* (no `password_changed_at` column), replaced by an honest pointer to Security.

**⚠️ A label that read the OPPOSITE of its control.** The motion row was titled **"Interface motion"** with a
tiny trailing *"reduce"* carrying all the meaning — but the input is `name="reduce_motion"`, so switching
*"Interface motion"* **on** actually turned motion **off**. Relabelled **"Reduce motion"** with a matching
description. **Behaviour unchanged; only the label now tells the truth.**

**Fixed (design):** the hero (larger avatar, role **chip**, status as **dot + word**), preferences as the
design's **switches**, date format as the design's **segmented control** — all still real, **focusable**
checkbox/radio inputs (`appearance:none`/`opacity:0`, **never `display:none`**) with `:focus-visible` rings and
wrapping-label names — plus **Cancel** beside Save.

**⚠️ Contrast 2 → 0, and the fix was SHARED.** Both failures were accent-as-text at **4.23**: the role chip and
the "This device" tag. The tag is **`.vy-tag.tone-confirmed` in the operator layout**, so one edit reached all
five consumers (layout, reservation-rack, operator-security, operator-profile, schedule). ⚠️ **Contrast this
with P32**, where the same "Active" pill was hand-written twice and the first fix reached only one copy: **a
shared definition is fixed once; a duplicated one has to be found twice.**

**⚠️ MY FIRST GUARD WAS VACUOUS — caught by mutating.** The geolocation test passed even with `Faro, PT`
injected into the sessions row, because the fixture created a user with **no `sessions` rows**, so the block
rendered empty (the P27 failure mode again). Rewritten to seed a real session and assert the real IP renders
first; now **mutation-proven** (injecting the city turns it red).

**Logged:** the avatar **camera badge** — `users.avatar_path` exists and the **owner** realm has real
upload/remove routes, but the **operator realm has none**, so the badge would be a control that does nothing;
live shows honest initials.

**Verification:** profile suites + the token guard + a shared-tag consumer **25/25 · 136 assertions**
(4 added, both refusal guards mutation-proven); PHPStan L5 **0**; Pint clean; **0 console**, **0 overflow** at
1440 and 1000. Schema untouched — no `migrate:fresh` needed.

---

## 2026-08-10 — DESIGN-PARITY **P34 · Operator Schedule PDF** — 🏁 **THE OPERATOR REALM IS COMPLETE (42/42)**

**Template + one presentation field.** `PdfService`, the render pipeline and the roster query are untouched.
`docs/design/MISSING-BACKEND-BY-PAGE.md` §34.

**⚠️ The anti-drift law — VERIFIED.** The PDF calls **`RosterWeek::build($property, $week, $dept)`**, the same
builder the grid calls. **Four callers, one builder, no fifth computation**; every figure on the document
(`teamSize()`, `shiftCount()`, `hours()`, `conflictCount`, `$cell['hours']`, `ShiftType::hours()`) comes from
it. Nothing was recomputed in the Blade to satisfy the design (the D9-1 trap).

**⚠️ SCREEN == PDF — proven cell by cell** on week `-1` (3–9 Aug 2026): Demo Accountant Early ×3 + Leave ×2 ·
Demo Admin Late ×2 · Demo Staff Day ×5 · Rosa Fernandes none — identical on the grid and in the document, and
3+2+5 = **10 shifts** = the document's `SHIFTS 10` / `HOURS 80`.

**#38 / #28.** The property is the **selected context** — **there is no property id in the PDF URL**, so a
foreign property cannot be requested; the filename carries the property key. A new test rosters two properties
in one org and proves each document carries only its own, **mutation-proven** (dropping the
`where('property_id', …)` filter turns it red). The roster is active-`Membership` ∩ back-office role, so
**"Demo Owner" (External Owner) appears on neither the grid nor the document**, and no guest appears anywhere.

**⚠️ The document still carried the PRE-AA palette — and a PDF is read on PAPER.** Measured on white:
`#8A8079` **3.86**, `#A69A92` **2.74**, `#B8ADA4` **2.20**. The 2.74 grey was carrying the **per-cell shift
times** — the start and finish times a rota exists to communicate — plus role labels and the footer. Moved to
the post-AA equivalents (**6.98** / **5.79**). The screens were fixed in the AA pass; this document was missed.

**Fixed (design):** the document title + a **REAL generated-at stamp** in the property's timezone (there was no
production time at all — a printed rota must say how old it is); a **print-safe legend** naming every cell
state in words, closing with *"so this prints correctly in black and white"*; and the design's two-part footer.
**Verified, not changed:** every cell already carried a word (`Early`/`Day`/`Late`/`Leave`/`—`/`/ Leave!`), so
the document was never colour-only.

**Refused / logged (4)** — and ⚠️ **two are STALE claims in the template's own comments**: the "OPEN 3" tile +
open-shifts section (an open-shift model **now exists**, but `RosterWeek` does not expose it — ⚠️ **it must be
added there, never queried in the PDF blade**, which is exactly the D9-1 second-computation trap) · "Posted
by … · provisional until published" (a **publish action now exists**, unread by the document) · the
colour-swatch department legend (colours encode STATE, not department — and a swatch legend fails a
black-and-white print) · a highlighted "today" column (only meaningful when the printed week contains today).

**⚠️ A premise I had to correct:** my first #30 test used **Housekeeping** as the unpermitted viewer and got
**200** — `schedule.view` is **deliberately broad** (#43); 9 back-office roles hold it, only **Accountant** and
**Marketing Manager** do not. Corrected to Accountant; unchecked, the assertion would have been vacuous.

**Verification:** schedule suites **22/22 · 154 assertions** (2 added, the #38 scope guard mutation-proven);
PHPStan L5 **0**; Pint clean; both documents generated through the **real export route** and rendered
(empty-week and populated). Schema untouched — no `migrate:fresh` needed.

---

### 🏁 THE OPERATOR REALM IS COMPLETE — 42/42 prototypes, 34 gates (`0aa9ede` → P34)

Every operator prototype has been decoded, diffed against the live page in a real browser, fixed where the gap
was presentational, and **logged where the product honestly refuses**. What the pass established:

- **Fabrication clusters on the surfaces that look most authoritative** — an invented open-rate on an email
  template, a city beside a last sign-in, a "verified" SMS number, a sync that never ran, per-channel booking
  stats with no channel attribution. The refusals are recorded per page in §15–§34.
- **Two independent accessibility axes.** Contrast *and* operability: P30's page measured **0** contrast
  failures while its only controls were unusable by keyboard; P32's preview clipped real data inside an iframe
  while the page reported no overflow.
- **Duplication defeats a fix.** The same "Active" pill failed AA twice (P31, P32) because it was written
  twice; one shared class fixed five views at once (P33).
- **A green test can be defending nothing** — proven twice by mutation (P27, P33).
- **Comments go stale faster than code** (P34).

**⬅ NEXT: the OWNER realm (32 prototypes), then GUEST (32) — both entirely untouched.**

---

## 2026-08-10 — DESIGN-PARITY · **OWNER realm begins** · Gate O1: Owner Dashboard (1 of 32)

**Method unchanged** (`docs/PROJECT-STATE.md` §N; per-page record `docs/design/MISSING-BACKEND-BY-PAGE.md`
§O1). Prototype decoded fresh from `docs/design/raw/Owner Dashboard.html` (byte-identical to the `/tmp/audit`
copy). Live page opened as the real `External Owner`. Design/markup/styling only — **no service, guard,
engine, figure or scope was changed.**

### Decided

1. **The chart's geometry is load-bearing, and it was wrong in two ways.** Bars were silently compressed by
   `flex-shrink` above ~84% (the current, largest month understated by 16%), and the goal line was
   positioned against the chart while the bars were positioned against the column — so the line did not sit
   at the value it labels. Fixed with the wireframe's own **78% headroom** plus a label-block offset.
   ⚠️ The prototype carries the **same** baseline offset: this is a **lesson-25** case — *do not reproduce
   the prototype's deficiency* when doing so states something false about real money.
2. **A genuinely €0 month must draw nothing.** The bespoke Overview chart still had the `max(4, …)` floor
   that the shared `<x-charts.bars>` had already removed. The shared rule is adopted verbatim. The chart was
   **not** migrated onto that component — it carries a period toggle, tooltip and goal line the shared one
   lacks, and replacing it would have lost real capability.
3. **The honest refusal keeps its words; only its slot was resized.** "Not yet attributed" (#39 — a stay
   with no owner-ledger rows) was truncating the guest NAME on 3 of 4 rows. The name is the #28-relevant
   identifier; the string is unchanged.
4. **Owner tint colours needed their own AA tokens.** The operator realm left tinted status/tag pills alone
   at a measured 4.61; on the **warmer owner glass the same pairs measure 3.26–3.85**. Added
   `--vy-green-text` / `--vy-amber-text` / `--vy-accent-tint-text` (4.71 / 4.76 / 4.75). The brand
   `--vy-green` / `--vy-amber` / `--vy-accent` fills are untouched. **AA 23 → 5**, real-pixel measured.
5. ⚠️ **SURFACED, DELIBERATELY NOT FIXED — white text on the accent gradient fails AA** (3.98–4.02) on the
   brand mark, the account avatar, `.vy-btn--accent`, and two page-local pills. It is **one concept crossing
   BOTH shells**, and the operator realm never measured it (its two a11y gates covered text-on-page-gradient
   and accent-as-text-on-glass; **accent-as-background under white text is the gap between them**). It needs
   its **own realm-wide a11y gate**, exactly as `--vy-on-grad` and `--vy-accent-text` got. Fixing only the
   two page-local instances would have left the page inconsistent *and* duplicated the concept.

### ⚠️⚠️ Found while doing it — three guards that could never fail

`expect(...)->not->toContain($needle, $message)` is an **always-passing assertion** in this codebase: Pest's
`toContain()` is variadic over needles, so the message becomes a second needle and the negation is satisfied
by its absence. **Mutation-proven:** a literal `expenses@endif` was rendered into the owner Dashboard and
**`assertNoRawBlade()` — the shared guard for the `word@directive` Blade trap, used by 36 test files across
the owner AND operator realms — stayed GREEN.** ⚠️ That trap **fired on this gate** and 500'd the page; had
it degraded to raw source on the page instead, nothing in the suite would have caught it.

A sweep found **10 dead assertions across 5 files**, including **#28 guest-PII-in-audit/domain-events** and
**#42 cross-owner name/money/unit on operator routes**. All repaired to
`Assert::assertStringNotContainsString()`; **every affected test still passes**, so the invariants hold — the
assertions were simply unable to fail. The two security-relevant ones were re-mutated and observed to fire.
**Standing rule: never `expect(...)->not->toContain($needle, $message)`.**

### Also closed

- **A dashboard money-leak coverage gap.** `OwnerPortalIsolationTest` asserted unit **codes** across the
  owner boundary but nothing about **money**, on a page whose whole purpose is a money rollup. A new case
  pins the perimeter (B's net never renders for A) and **#17** (Earned YTD == Σ of A's own nets), guarded
  against fixture vacuity. **Mutation-proven** — neutering `OwnerScope::apply()` turns it red.
- **A pre-existing Pint failure** in `OperatorEmailTemplatesTest` (introduced at `e7c2641`, operator P32,
  and stash-proven pre-existing) — the "Pint clean" claim had gone stale. Formatting only.

### Verified, not changed

**#42** owner isolation is structural (`BelongsToOwner` → `OwnerScope`, fails **closed**; 2 of 8 units
visible; cross-owner statement 404s) · **#17** reconciles on the rendered page (six statement rows sum to
€3,690.00 = the Earned-YTD tile, to the cent) · **#28** the IBAN is **structurally** un-emittable raw
(`OwnerNextPayout` passes only `iban_last4` into `Iban::masked()`), no guest contact on the page ·
**#39** no fabricated figure, trend, verification state or estimated payout date · **keyboard** 60 visible
controls, 0 unreachable / 0 unnamed / 0 without a focus indicator, including the two states this gate
introduces.

**Gates:** suite **1833 tests / 12,024 assertions · 9 non-green** — all four documented pre-existing groups
(`BookingImportTest` order-dependent ×6, `OwnerUnitDetailViewTest`, `StubViewsTest` ×2), the **top of the
documented 5–9 range (J.quater)**, re-measured this gate rather than quoted. ⚠️ Making `assertNoRawBlade`
real across 36 files produced **no** new failures — no page ships raw Blade today. PHPStan L5 (0); Pint
clean; page browser-verified at 1440×1000 and 1440×1500, 0 horizontal overflow, 0 console errors.

---

## 2026-08-10 — DESIGN-PARITY · OWNER gate O2: Owner Statement (2 of 32) — the money document

**Route** `/owner/statements/{statement}` (`OwnerStatementView`), role `External Owner`. Prototype
`docs/design/raw/Owner Statement.html`, decoded fresh (byte-identical to `/tmp/audit`). ⚠️ `Owner Statement
PDF.html` and `Owner Archive.html` were decoded and identified as **separate pages** — the archive LIST is
what the sidebar's "Statements" nav actually points at, and gets its own gate. Per-page record:
`docs/design/MISSING-BACKEND-BY-PAGE.md` §O2. **Markup/CSS only — no service, engine, money query or
`PdfService` touched.**

### Decided

1. **The document was measured on PAPER, and it was still pre-AA.** Extracted from the generated PDF's own
   content stream: `#8A8079` **3.86** on white — the `.neg` class, i.e. **the commission and deduction
   figures** — and `#A69A92` **2.74** for the footer. Both moved to the operator Schedule-PDF pair
   (**6.98 / 5.79**). ⚠️ The dark band's greys were deliberately left alone: on `#2A211C` they measure
   **8.21 / 7.05**, and darkening them would be backwards.
2. **A "text" token needs a NAMED background — my Owner-1 token was under-tuned.** `--vy-amber-text` was
   fitted to one sampled pixel; here the same pill measured **4.45–4.50**, and on the **dark payout rail
   1.87**. ⚠️ The rail was **already failing before Owner-1** (`--vy-amber` = 2.72) and **all three tones
   failed there** (paid 1.77, draft 1.65) — my tint fix deepened a hole it did not create. Fixed by tuning
   the token against the **worst** background it meets and adding a dark-context trio
   (`--vy-amber-on-dark` / `--vy-green-on-dark` / `--vy-muted-on-dark`, measured 5.10 / 5.07 / 6.00 against
   the pill's OWN composited pixel, not the panel behind it).
3. **A styling gap is not worth a new accessibility failure.** The wireframe makes "Export PDF" the accent
   gradient pill; restyling it that way **added a 4th instance** of the white-on-accent-gradient family
   (measured 4.00 the moment it rendered — the realm-wide gap surfaced at Owner-1, still awaiting its own
   gate). Reverted to a ghost pill, with the reason recorded in the template: it gets the accent treatment
   in the same change that makes the gradient accessible, not before.
4. **A money grid stays a `<table>`.** The mock renders history rows as a `<div>` stack of cards; the card
   LOOK was adopted, the table semantics kept. ⚠️ And scoped to a new `.vy-st-hist` rather than the shared
   `.vy-table` — this page's `<style>` is page-local, so a bare `.vy-table tbody tr` rule would have
   restyled a shared component on exactly one page.
5. **A genuinely 0% occupancy month draws nothing**, and the sparkline gained a real accessible name — its
   per-month figures previously existed **only in `title`, on an `aria-hidden` container**, invisible to
   touch and to assistive tech alike.

### Verified, not changed

**#17 reconciles from ONE source on BOTH surfaces** — 1,080.00 − 194.40 + 0.00 = **885.60** = net payable =
next payout = by-unit total; history 6 statements → €3,690.00. The page's by-unit split and the PDF's
"Performance by unit" both read the ONE `OwnerStatementBreakdown::forStatement()`, and the PDF's breakdown
rows are the same `owner_statement_lines`; **no total is re-derived in Blade**. **SCREEN == PDF proven
figure by figure** from the PDF's extracted text, at the **real 18% agreement rate** (never the mock's
hardcoded 15%). **#28** — IBAN masked on both surfaces and **structurally** un-emittable raw
(`Iban::masked(last4)`); no guest name/email/phone on either. **#42** — owner+tenant-scoped `findOrFail` on a
string prop; cross-owner 404 on the page **and the PDF**, already covered non-vacuously. **Keyboard** 47
visible controls, 0 unreachable / 0 unnamed / 0 without a ring. **Screen contrast 8 → 3**, the 3 being the
known gradient family.

⚠️ **`toContain` sweep — negative recorded.** Every `not->toContain(...)` in the statement tests is
**needle-only**, so none carries the Owner-1 always-passing defect. No repair was needed here.

⚠️ **A stale docblock corrected** (principle 5): `OwnerStatementView` still declared the by-unit split, the
occupancy chart and the history CSV export "DEFERRED (visually stubbed…)" — **all three have shipped**.

⚠️ **One test re-aimed, not weakened:** the remittance row's honest copy changed ("Once paid" → "Available
once your payout is issued"), so its `assertSee` was updated. What the test encodes — the honest state in
words, and **no remittance link offered** — is unchanged, and the `assertDontSee(route(...))` guard was
**mutation-proven** to still fire.

**Gates:** suite **1833 / 12,024 · 9 non-green** — the identical documented set (`BookingImportTest` ×6,
`OwnerUnitDetailViewTest`, `StubViewsTest` ×2), re-measured this gate, no new failures. PHPStan L5 (0); Pint
clean; screen + PDF browser-verified; 0 horizontal overflow, 0 console errors. No schema touched.

---

## 2026-08-10 — DESIGN-PARITY · OWNER gate O3: Owner Payouts (3 of 32) — the payout surface

**Route** `/owner/payouts` (`OwnerPayouts`), role `External Owner`. Prototype
`docs/design/raw/Owner Payouts.html`, decoded fresh (byte-identical to `/tmp/audit`; 168,102 chars, the
largest owner prototype). Record: `docs/design/MISSING-BACKEND-BY-PAGE.md` §O3. **Markup/CSS only — no
service, engine, money query, bank record or preference touched.**

### ⚠️⚠️ The finding: D16-7 recurs on the owner's own payouts page

`OwnerPayoutService::pay()` writes **`processing`** on the real-transfer path. `OwnerPayouts::metrics()`
builds all three tiles from `status === 'paid'` and `status === 'pending'` only. **A payout genuinely in
flight is therefore in no tile at all** — the owner's "Pending" drops to zero and "Paid this year" does not
move, while the money is actually moving. `held` is absent from the tiles too. Verified by construction;
**logged, not fixed** (it is a computation, and this pass is design-only). It becomes wrong the day Stripe
goes live, not before.

⚠️ The **same omission was written in the class docblock** (`status ∈ pending/paid/failed/reversed`).
Corrected. *When a comment enumerates states, a gap there previews the bug in the code beneath it.*

### ⚠️ A second real defect: the payout history has no deterministic order

`OwnerPortalService::payouts()` orders by `created_at` DESC alone, and **all six demo payouts share one
timestamp** — the sort key distinguishes nothing and the list renders **oldest-first**, the opposite of the
design and of what a money ledger should show. ⚠️ Not a demo artifact: `PayoutRunService` creates a run's
payouts in one pass, so ties are the normal case. **Logged, not fixed** — it is an ORDER BY in a shared read
that the Dashboard, Statement, Payouts page and the CSV export all consume; it must change in one place so a
page and its export cannot disagree.

### ⚠️ `--vy-green-text` re-tuned — the same single-sample mistake, one gate later

The green pill token set at O2 was fitted to the Statement/Dashboard rows; on the Payouts table's warmer rows
the "Paid" pills measured **4.31–4.44**. Re-tuned against the worst background it meets (5.86–6.04 there,
6.41–6.51 on the cooler rows) and **the Statement page was re-measured** — still at its 3 known failures, so
no regression. **Two gates running, a token fitted to one sample has been wrong on the next page. Tune to the
worst case, then go back and re-measure the pages already passed.**

### Decided

- **The summary line reuses `$metrics`, it does not re-sum the rows** — the same total must not be computed
  twice on one page.
- **The new state pills show only REAL state** (the `$hold` the banner already uses; the stored pause
  preference) and are absent when there is nothing to say.
- **Refused (#39):** projected future payouts ("~€9,850 · Projected", a trailing-3-month average), a retry
  ETA, and the bank-**verification** hold with an "est. release" date. A projected payout amount is a
  fabricated figure on the most authoritative money surface an owner has.
- **The live payout-day set (1st–5th business day) is the truthful one** and was kept over the mock's
  "15th / Last": offering a cadence `PayoutSchedule` cannot run would be a promise the engine breaks.
- **The white-on-accent-gradient family recurs (6 instances) — not half-fixed, and nothing added to it.**

### Verified, not changed

**#17** — Paid this year **€2,804.40** = the five real `paid` rows to the cent; Pending €885.60 = the one real
`pending` row; the dark rail's breakdown (1,080.00 − 194.40 + 0.00 = 885.60) matches the statement it links
to; one source, no Blade re-derivation. **#71** — every status is the real row status, under a standing
test-mode banner; nothing claims a live transfer. **#28** — destination masked, structurally un-emittable
raw; no guest data. **#42** — owner-scoped; cross-owner remittance 404s (already covered). **READ-ONLY
honoured** — verified after the gate: the bank method's `updated_at` is still the seed timestamp, **no payout
-preference row exists at all**, payout statuses unchanged. **Keyboard** 58 visible controls, 0 unreachable /
0 unnamed / 0 without a ring / 0 `display:none` inputs. **Contrast 21 → 6**, real-pixel measured across both
viewport halves. ⚠️ **`toContain` sweep — clean negative** (both payout-test calls are needle-only).

⚠️ **A method note:** this prototype could not be screenshotted (its stacked blurred-glass layers and
infinite animations exceed the browser tool's render budget), and the live page hit the same wall whenever a
page-wide `color: transparent` measurement style was injected. The design was read from the decode plus
rendered text/computed styles, and the contrast sweep was re-cut to hide text on **only the candidate
elements**. Faithful, but not a screenshot — recorded rather than glossed. Live before/after screenshots are
real.

**Gates:** suite **1833 / 12,024 · 9 non-green** — the identical documented set, no new failures. PHPStan L5
(0); Pint clean; 1440×1000 verified, 0 horizontal overflow, 0 console errors. No schema touched.

---

## 2026-08-10 — DESIGN-PARITY · OWNER gate O4: Owner Rates (4 of 32) — the guarded sibling

**Route** `/owner/rates` (`OwnerRates`, CAP_ACTIONS), role `External Owner`. Prototype
`docs/design/raw/Owner Rates.html`, decoded fresh (byte-identical to `/tmp/audit`). Record §O4.
**Markup/CSS only — no service, engine, guardrail, rate or proposal touched.**

### ⚠️ The gate brief's premise was inverted — corrected before building anything

The brief called this "the GUARDED path: the owner proposes, validated against the guardrail". In fact
`RateProposalService::propose()` is the **OPERATOR** side — the guardrail constrains what the **manager** may
propose (below floor / above ceiling **rejected**; within ±`auto_approve_pct` **auto-applies**; larger stays
`pending`). The **owner** approves / declines / counters, **sets** the guardrail, and prices their own
exclusively-owned room types by **direct apply** (`owner.rates.apply` → certified `OwnerRateService`). The
prototype's own header agrees: *"Review the pricing your manager proposes and set the limits they work
within."* Taken at face value the brief would have produced a propose-flow that does not exist and a
guardrail pointed at the wrong actor.

### ⚠️ The fabricated-guardrail trap — the page passes it

**Fixture truth established first: 0 `RateGuardrail` rows in the entire database, 0 `rate_proposals`.** So
live can only demonstrate the UNGUARDED case. Traced into `propose()`: with no row there is no floor/ceiling
rejection and no auto-approve branch, so **every proposal stays pending** — which is exactly what the card's
±0% state says (*"Every rate your operator proposes needs your sign-off before it goes live"*). The copy is
literally true. **No "within policy" badge, no fabricated limit, no implied protection.** Guarded enforcement
is covered non-vacuously by `RateProposalTest`, which seeds **real** guardrail rows (floor/ceiling rejection,
dynamic auto-approve, fixed). **No guardrail was seeded for this gate** — that would be mutating demo data to
make a screenshot look richer.

### Decided

- **The wireframe's two columns restored.** The guardrail card — the thing the left column's proposals are
  judged against — had been sitting below the fold of what it governs.
- ⚠️ **A horizontal overflow the restructure exposed and closed**: `1fr 1fr` grid tracks default to an `auto`
  minimum, so the floor/ceiling inputs refused to shrink in the narrower rail and pushed the document to
  **1537px against a 1440px viewport**. `minmax(0, 1fr)`.
- **Contrast 21 → 3 — the realm's worst page so far**, because it hardcoded the prototype's pre-AA greys
  instead of the tokens (`#A69A92` ×10, `#8A8079` ×7, measured **1.89–3.74**) on the copy that explains what
  the guardrail actually does.
- **Two SHELL-level AA fixes, both re-verified on Payouts (no regression):** `.vy-topbar-sub` sits **directly
  on the page gradient**, not on glass — `--vy-muted` read 3.80 there, so it moved to `--vy-on-grad` (4.68);
  and `.vy-nav-item.is-active` read 4.49 on its own frosted pill → `--vy-accent-text` (5.34).
- **Two real keyboard defects fixed:** `.vy-rt-in input` carried `outline:none` with **no** `:focus` styling —
  the guardrail's floor/ceiling and the counter-offer rate had **no visible focus indicator at all**; and the
  **auto-approve slider had no accessible name** (its heading is a `<div>`, not a `<label for>`) — the control
  that decides how much pricing authority is delegated. ⚠️ Two "unnamed" date inputs were a **false positive
  of my own probe** (proper sibling `<label for>`; the resolver only walked ancestors) — the probe was
  corrected, not the page.
- **Refused (#39):** the comp-set average, the market-position bar and *"Priced 5% below market"* — no
  market-rate source exists; and the **Peak** tier, which is not a modelled entity (deriving it from the
  calendar's maximum would present a derived number as a configured tier).

### Verified, not changed

Every control POSTs the real **owner** route (`owner.rate-proposals.approve|decline|counter`,
`owner.rates.guardrails`, `owner.rates.apply`) — **nothing touches the operator's unguarded
`RateActionController`**. **READ-ONLY honoured**, confirmed after the gate: `rate_proposals` still **0**,
`rate_guardrails` still **0**, `rate_calendar`'s newest `updated_at` still the seed timestamp — no rate
written. **#42/#38**: a foreign `?room_type=` is ignored outright ("Double Room" appears nowhere; the
selector offers only the exclusively-owned type). **Rate figures real**: €180.00 base, an honest "—" weekend
(no uplift set), 22% real occupancy. **Keyboard 37 / 0 / 0 / 0.** ⚠️ **`toContain` sweep — clean negative**
(`OwnerRatesTest` and `RateProposalTest` contain none at all). ⚠️ **Stale docblock corrected**: `OwnerRates`
still declared Guardrails "unmodelled … NOT rendered as real" and proposals as attention-feed-only — both
shipped and are on this page.

**Carried, not fixed:** the same `outline:none`-without-`:focus` pattern on `.vy-mu-rate-input input`
(My Units) and `.vy-au-money input` (Add Unit) — logged for those gates.

**Gates:** suite **1833 / 12,024 · 9 non-green** — the identical documented set, no new failures. PHPStan L5
(0); Pint clean; 1440×1000, **0 horizontal overflow**, 0 console errors. No schema touched.

---

## 2026-08-10 — DESIGN-PARITY · OWNER gate O5: Owner My Units (5 of 32)

**Route** `/owner/units` (`OwnerMyUnits`, `$capCalendar`), role `External Owner`. Prototype
`docs/design/raw/Owner My Units.html`, decoded fresh (byte-identical to `/tmp/audit`). Record §O5.
**Markup/CSS only — no service, engine, metric, unit or rate touched.** ⚠️ `Owner Unit Detail` and
`Owner Add Unit` are separate prototypes for their own gates.

### ⚠️ The keyboard defect logged at O4 for this gate is closed — and it had siblings

`.vy-mu-rate-input` carried `outline:none` with **no** `:focus` rule, so the inline nightly-rate editor had
**no visible focus indicator at all** (WCAG 2.4.7). Fixed on the wrapper (`:focus-within`) and **confirmed
live with the editor open**. ⚠️ **Two more instances of the same pattern were on this page** — the
weekend-uplift / min-stay inputs (same wrapper) and the per-unit **message textarea**. Additionally, **three
inputs in the pricing editor had no accessible name** (their captions are `<div>`s and unassociated
`<label>`s); each now carries a unit-specific `aria-label`. Final: **65 controls with both editors open,
0 unreachable / 0 unnamed / 0 without a ring.**

### ⚠️ A real figure-honesty finding — two "August" figures, both correct

The tile reads **"Earned this month · €541.60 · Net · August 2026"**; the Statements page reads
**"August 2026 · €721.60"**. Traced to the rows: the statement sums the **3 lines it CLAIMED** at finalize
(72,160 — matching `net_payable_minor` exactly, #17 holds), while `earnedThisMonth()` sums **every** ledger
line with a `service_date` in the month (54,160). **The €180.00 gap is a real, unstatemented expense dated
9 Aug.** Neither figure is fabricated; they answer different questions.

⚠️ **This is the WAVE-2 class — a figure under a label that means something else.** **Logged, not
re-worded**: whether the tile should exclude unstatemented items, or say so, is a product decision, and
inventing a caption would be guessing at intent.

### Decided

- **Contrast 29 → 10** across two passes (all 10 remaining being the known gradient family). ⚠️ **The three
  pre-AA greys logged at Owner-1 as "carried to the My Units gate" are closed here** —
  `.vy-mu-stat-label` / `.vy-mu-uplabel` / `.vy-mu-calhead` measured **2.28–2.78** on the portfolio KPI
  labels and per-unit occupancy captions. ⚠️ And **the booked calendar day NUMBERS measured 3.57** — those
  numerals are the **dates**, the one thing every cell must carry; darkened to 5.47–9.07 while white
  numerals stay only on the deep cells.
- ⚠️ **The dark "Best performer" panel passed** — the O2 dark-context mirror holds, and no light-tuned token
  was applied to it.
- **Refused:** the mock's bulk **"Approve all"** on the attention list. One click spanning a rate change and
  a €340 expense is a dangerous affordance, not a missing feature; the live per-item Approve/Query stands.
  Also recorded: the mock's per-unit **"Message guest"** implies a guest channel — **the live control
  messages the management team, which is the correct behaviour** (#28), and must not be "fixed" toward the
  mock.

### Verified, not changed

**#42/#38** — the database holds 8 units (`101, 102, 103, A1, A2, V1, V2, V3`); this owner owns **A1 and
A2**, and the rendered page mentions **none of the other six**. **Figures real, denominator right** — avg
occupancy 13% = 8 booked unit-nights ÷ (2 × 31); per-unit 19% and 6% agree; ⚠️ this is **not** the D3-4
class (the divisor is the full month across the real unit count, not the spine's coverage). **#28** — no
guest email/phone anywhere (regex-checked) and **no IBAN at all**. **READ-ONLY honoured** — the editors were
*revealed* (a UI toggle), nothing submitted: `units.updated_at` and `rate_calendar.updated_at` are still the
seed timestamp and **`owner_attention_states` is still empty** (no Approve, Snooze or Mark-read fired).
⚠️ **`toContain` sweep — clean negative** (fourth gate running): the one call in the unit tests is
needle-only.

**Gates:** suite **1833 / 12,024 · 9 non-green** — the identical documented set. PHPStan L5 (0); Pint clean;
1440×1000, 0 horizontal overflow, 0 console errors. No schema touched.

---

## 2026-08-10 — DESIGN-PARITY · OWNER gate O6: Owner Notifications (6 of 32) — 🏁 the P29 item CLOSED

**Route** `/owner/notifications` (`OwnerNotifications`), role `External Owner`. Record §O6.
**Markup/CSS only — no service, feed or route touched.**

### 🏁 The operator realm's last open P29 item is closed

GATE 29 fixed the colour-only unread state on the operator page and logged the same **WCAG 1.4.1** defect as
still open on two surfaces. **Both verified still present, both fixed:** the owner feed page and the
**SHARED `components/notifications/bell.blade.php`** — the shared partial fixed **once**, reaching the
operator and owner bells together. The pattern is GATE-29's: an `aria-hidden` dot plus real
`.vy-sr-only` text, **never** a `title=` on a non-interactive span (which is not reliably announced — the
original finding). The ✓ control now carries `aria-label="Mark “{title}” as read"`.

⚠️ **A third defect surfaced, and it is why the shared fix would otherwise have half-worked:** `.vy-sr-only`
was defined **only in the operator layout**, so the shared partial's visually-hidden text was hidden in
operator and **plainly visible in owner**. Now defined in both. *A shared partial cannot depend on a class
only one shell defines.*

**Verified live:** owner → 0 bare `title="Unread"`, 12 hidden texts, every ✓ named; operator → 0 bare
`title="Unread"`, 31 hidden texts, **at most one per row (no double-announcement)** — unregressed and now
covered by the shared fix. **Mutation-proven three ways.** ⚠️ The third assertion was **initially vacuous**
(`toContain('.vy-sr-only')` matched the class attribute, not the rule) — caught by mutation, tightened, and
re-mutated.

### ⚠️ Premise corrections (two)

1. **`Owner Notifications.html` is the PREFERENCES page, not the feed.** Decoded fresh: breadcrumb
   *"Settings / Notifications"*, *"Choose what we tell you and where"*, channels, a per-event **Email/SMS
   matrix**, quiet hours, Save. It belongs to `/owner/notification-preferences` — which is also what
   `OwnerNotificationsPageTest` tests — and **the live feed has no prototype of its own**. Recorded so a
   later gate does not restyle the feed into a preferences page.
2. **The feed is self-scoped by its query root**, not by a foreign-id 404: `NotificationFeed` reads
   `$user->notifications()`, so a foreign row is never addressable. Verified: **242 notifications across 5
   users; this owner's page renders exactly their own 6.**

### Decided

- **Contrast 6 → 3** (the 3 being the known gradient family). ⚠️ Both non-family failures were the
  **text-on-the-page-gradient** case from O4: the footer sentence measured **4.43** on `--vy-muted` →
  `--vy-on-grad` (5.46), and its "Notification preferences" **link measured 3.17** — on the gradient even
  `--vy-accent-text` reaches only 3.77, so it took `--vy-accent-tint-text` (4.52).
- ⚠️ **Blade compiles component tags inside CSS comments.** Naming the shared bell with its `<x-…>` syntax in
  a comment inside the layout's `<style>` block rendered the entire component into the stylesheet and 500'd
  every owner page. **Comments are not inert to Blade.**

### Verified, not changed

**Every notification real (#39)** — all six are `Your statement is ready`, each naming its REAL month and net
payable (€516.60 / €442.80 / €467.40 / €885.60 / €721.60 / €656.00), matching the statements to the cent; **no
fabricated channel/OTA "sync"** (the D2-2 door) and **no "payout sent to your bank"** implying a live transfer
while the engine is test-mode (#71). **#28** — rows reference statements and money only; no guest name, email
or phone, and no IBAN. **One source for the count** — header "6 unread" = bell badge 6 = the database.
**READ-ONLY honoured** — mark-read/mark-all POST the real gated routes, read but never fired: still 6 of 6
unread. **Keyboard 40 / 0 / 0 / 0.** ⚠️ **`toContain` sweep — clean negative** (fifth gate running).

**Gates:** suite **1834 / 12,032 · 9 non-green** (+1 test, the new 1.4.1 guard) — the identical documented
set. PHPStan L5 (0); Pint clean; 0 overflow, 0 console errors. No schema touched.

---

## 2026-08-10 — DESIGN-PARITY · OWNER gate O7: Owner Notification Preferences (7 of 32) — the claims surface

**Route** `/owner/notification-preferences`, role `External Owner`. Prototype `Owner Notifications.html` —
the file Owner-6 identified as the preferences design. Record §O7. **Markup/CSS only — no service, storage
or save semantics touched.**

### The #1 rule, verified against the code

**`NotificationPreferences::CHANNELS = ['in_app', 'email']`** — identical to the operator's. **Email is
REAL** (`allows()` genuinely gates the owner Mailables). **SMS and PUSH are STRUCTURALLY UNSETTABLE**: not
in the channel set, so no preference row can ever exist — verified, **0 rows for `sms`/`push` and 0 rows
overall**. The page shows SMS with an honest "not set up", a disabled switch, **no submitting input at all**,
and (now) a per-event text announcement. Push is not offered, which is equally honest.

⚠️ **Brief correction:** it said "in-app is real — a real toggle". On the OWNER page in-app is **deliberately
not a toggle** — the wireframe's grid is Email + SMS and the portal feed always records. The in-app
preference is **preserved via hidden inputs**, and those inputs are **load-bearing**: `save()` reads
`$enabled[$key]['in_app']` and defaults a missing key to `false`, so removing them would silently disable
every in-app notification. Recorded so nobody tidies them away.

### Storage model verified

`notification_preferences` is a **typed per-(user, event, channel) pivot**, written with `updateOrCreate`
keyed on all three and iterating only the audience's catalog and only `CHANNELS`. **No JSON blob, no
wholesale replace, no delete** — the WAVE-1 merge-vs-replace hazard is *structurally absent*, the same
ruling as Maintenance Settings. `always_on` is forced true **at save time**, so the urgent bypass lives in
the service, not just the UI.

### Decided

- ⚠️ **The per-event SMS cells announced their state with an `aria-label` on a non-interactive `<span>`** —
  the same unreliable pattern P29 found on the unread dot. Replaced with real `.vy-sr-only` text, matching
  the operator prefs page. ⚠️ **This fix was only possible because O6 added `.vy-sr-only` to the owner
  shell** — backlog items compound.
- ⚠️ **The SMS column header measured 1.63, the worst contrast on the page**, on the label of the column the
  owner most needs explained — and it said only "SMS", leaving the empty column unexplained inside the grid.
  Now `--vy-muted` and carrying "· not set up".
- **Contrast 17 → 2** (the 2 being the known gradient family): the page hardcoded the pre-AA `#A69A92` ×9 and
  `#8A8079` ×5, measured **1.93–3.32**, across every event description, the owner's own email address,
  "Always on" and the guarantee sentence.
- ⚠️ **`--vy-on-grad` re-tuned shell-wide — the third token to need it.** Set at O4 from one sampled gradient
  pixel (4.68), it measured **4.42** on a lighter point of the same gradient. **A token placed on a gradient
  has a range of backgrounds, not one.**

### Verified, not changed

**Every toggle state is a real catalog default** — the six rendered states match `catalogFor('owner')`
exactly, and **0 preference rows exist**, so this is genuinely the fresh-owner state. **Self-scoped** — the
save writes `user_id = $request->user()`. **READ-ONLY honoured** — the form POSTs the real gated route, read
but never submitted: still **0 rows**. **Keyboard 33 / 0 / 0 / 0** — ⚠️ the **GATE-30 defect is not present**
(email cells are real named `<button>`s, the master switch carries `aria-pressed`, quiet hours is a real
checkbox); the 15 `display:none` elements are `input[type=hidden]`, including the load-bearing in-app ones.
**Quiet hours is REAL** (Wave B GATE 6) and the prototype omits it — kept as EXTRA. ⚠️ **`toContain` sweep —
clean negative** (sixth gate running), and one operator guard already asserts a **CSS rule** rather than a
class name, which is the Owner-6 lesson applied.

**Gates:** suite **1834 / 12,034 · 9 non-green** — the identical documented set. PHPStan L5 (0); Pint clean;
0 overflow, 0 console errors. No schema touched.

---

## 2026-08-10 — DESIGN-PARITY · OWNER gate O8: Owner Insights (8 of 32) — the analytics surface

**Route** `/owner/insights` (`OwnerInsights`), role `External Owner`. Record §O8. **Markup/CSS only — no
metric, service or engine touched.**

### ⚠️ The D3-4 premise is inverted for this page — verified, not assumed

The brief called the D3-4 denominator class "the central trap here". It is an **operator-path** defect:
`PerformanceMetrics::forProperties()` divides by `Availability::sum('total')` — the availability **spine's
coverage**. The **owner** path, `forOwnerUnits()`, divides by `count($unitIds) * $days` — **units × calendar
days** — and Owner Insights uses it everywhere, including both the current and prior-year monthly series.
⚠️ **And the page shows its own denominator**: "57 of 730 unit-nights" (2 units × 365). The strongest form
of the antidote is the divisor on screen. **Recorded so the backend phase does not "fix" D3-4 here and break
a correct figure.** The vs-last-year deltas are therefore honest, and attach only to months where the prior
year genuinely had occupancy.

### ⚠️ A real defect: four genuinely-0% months were drawing bars

`max(2, $pct)` put a visible bar under **Jan, Feb, Mar and Nov** — 4 of 12 columns, all genuinely 0%. Nov's
own tooltip read *"0% (on the books)"* beside a drawn bar. **Third page carrying this floor** (Dashboard,
Statement, here); fixed on all three of this page's floors — a zero draws nothing, non-zero keeps 2px.

### 🏁 The `--vy-on-grad` churn is resolved by measurement

Re-tuned from single pixels at O2 and O7. Measured here over **7,502 background pixels sampled behind the
elements that actually use the tokens**; darkest composited point **rgb(239,174,135)**. ⚠️ **Premise
correction:** these are DARK text on the **page** gradient (`--vy-bg`), not the accent gradient, so the worst
case is the **darkest** point. `--vy-on-grad` already cleared (**5.15**, unchanged); **`--vy-on-grad-soft`
was FAILING at 4.26** and had never been re-tuned in its own right → **4.63**.

⚠️ **Cross-realm finding, logged not fixed:** the **operator** shell's darkest on-grad point is
**rgb(216,129,89)** and **both** its tokens fail there — **2.95 / 2.77**. Gate `93247e7` claimed
"4.74 / 4.93" from one sampled pixel. Clearing AA needs ≈ rgb(51,45,41) — a visibly darker sidebar label
across 42 closed pages, so it belongs to its own gate, not an owner page gate.

### Decided

- The vs-LY **delta arrows** now carry direction as a **word** (`aria-hidden` glyph + `.vy-sr-only`
  "up"/"down") and use the tint-safe tokens instead of raw hex.
- The observation CTA moved off `--vy-accent` (4.35) to `--vy-accent-text`.
- **Refused / preserved:** no market or comp-set benchmark (no data source), no rate recommendation or
  projected revenue ("decisions stay yours"; the dark CTA links to Rates without proposing a number), no
  per-channel breakdown (0 reservations carry a `channel_id` — the D2-2 root).

### Verified, not changed

**#42/#38** — the page names **A1 and A2 only**; none of the other six units in the database appears.
**One source** — every figure comes from `PerformanceMetrics` / `BookingCadence`, the same helpers the
Dashboard and My Units use; **nothing re-derived in Blade**. **#28** — no guest email/phone and no IBAN.
**Chart a11y** — series named in a legend (Achieved / On the books), per-bar `title` with month, value and
prior-year figure; not colour-only. **Keyboard 31 / 0 / 0 / 0.** No export exists on this page, so nothing
could be fired. ⚠️ **`toContain` sweep — clean negative** (seventh gate running).

**Gates:** suite **1834 / 12,034 · 9 non-green** — the identical documented set (including the date-coupled
`OwnerUnitDetailViewTest`). PHPStan L5 (0); Pint clean; 0 overflow, 0 console errors. No schema touched.

---

## 2026-08-10 — DESIGN-PARITY · OWNER 9/32 · **Owner Add Unit** (`/owner/add-unit`)

Presentation-only gate. **Two corrections and one real defect.**

**1. The brief's premise was wrong: this is not a creation flow.** An owner cannot create a unit anywhere in
Vyrobel. The wizard files a `unit_requests` row (`pending`); the operator reviews it and creates the real
unit as their own action. Recorded because the gate was briefed as a create, and because the read-only
discipline still mattered — a request is real work on someone's desk.

**2. 🔴 The wizard could be submitted from step 1 by pressing Enter.** Implicit form submission targets the
step-4 submit button even while step 4 is hidden, and the server requires only `requested_name` +
`requested_kind` (the kind is pre-selected). One Enter on step 1 therefore filed a genuine request with four
silent defaults, no rate and no photos. Fixed at the markup level (`@keydown.enter` prevented unless
`step === 4`), **browser-proven both ways** (step 1: 1 submit → 0; step 4: still 1) and covered by a
**mutation-proven** regression test. Generalised as `PROJECT-STATE.md` §N.4k — *a wizard whose steps are
client-side is submittable from any step*; the remaining wizards need the same check.

**3. Accessibility — 13 unnamed controls → 0, contrast 15 AA failures → 6, icons 2 → 0.** Five inputs were
named by placeholder only (the visible label was a `<div>`); eight icon-only steppers had no name at all;
14 chips conveyed selection by colour alone and exposed nothing to AT. `.vy-au-money`'s missing focus ring —
logged at O4 **for this gate** — is closed, along with a second instance (the `opacity:0` file input, whose
own ring is invisible by construction). The failing greys were `--vy-faint`/`--vy-muted` sitting on the page
gradient: fixed by reaching for the **existing** `--vy-on-grad-soft`, not by retuning a token that eight
closed pages depend on.

**Refusals preserved:** the prototype's town chips (no town catalogue exists — `location` is a free string),
its hardcoded 15% (live reads the owner's real agreement, 18%), any earnings projection, any client-side
validation theatre. **Kept beyond the prototype:** "Your requests", and photo upload that genuinely uploads.

**Read-only honoured** after a full four-step walk including Enter on steps 1 and 4: `unit_requests` still 0,
`units` 8, `room_types` 3, `media` 6, `rate_calendar` 925, `units.updated_at` still the seed time. No schema
touched, so no `migrate:fresh`. Suite **1835 / 12,036 · 9 non-green** (the documented non-deterministic set);
PHPStan L5 (0); Pint clean; 0 overflow, 0 console errors.

---

## 2026-08-10 — DESIGN-PARITY · OWNER 10/32 · **Owner Unit Detail** (`/owner/units/{unit}`)

Presentation-only gate. **One real defect, one premise that did *not* generalise, one rejected hypothesis.**

**1. 🔴 The availability calendar was mouse-only.** 31 of the page's 36 interactive elements were
keyboard-unreachable: every clickable day was a `<div>` with an Alpine `@click`, no `tabindex`, no `role`,
no key handler. *Tap an open day to block it* is a REAL write through the certified `OwnerBlockService` —
it takes inventory off-sale — so a keyboard user could not perform a money-affecting action at all. This is
GATE-30's class of defect at scale. Fixed by making the interactive cells real `<button>`s, which restores
focus, Enter/Space and a ring with no JavaScript. Browser-proven by keyboard **with every form guarded and
0 submissions fired**; regression test mutation-proven.

Each cell now also names its date and state — the interactive cells' `title` said *"Tap for detail"*, so the
state reached assistive tech only as a fill colour. Recorded honestly: that closes **4.1.2**; on the visual
**1.4.1** axis the three states already differ by luminance rather than hue alone, so the design's encoding
is kept.

**2. The Owner-9 premise does NOT generalise.** Add Unit is a *request*; these are **direct owner writes** —
pause/resume (`owner.units.toggle-status`), block a day (`owner.calendar.block`) and price
(`owner.rates.apply`), each CAP_ACTIONS-gated, and pricing additionally only when the room type is
exclusively owned. #6 holds exactly as Owner-4 established it. Verified rather than assumed.

**3. Contrast 25 → 6.** The ★ rating glyphs measured **1.85** — and the stars *are* the score. The "Active"
pill measured **3.20** because the design's tint assumes a card while the pill sits on the page gradient;
compositing the same tint over a near-white plate reaches 4.63 without altering the tint or the text colour.
The `max(2, $pct)` chart floor appeared for the **fourth** time (7 genuinely-zero months drew stubs). The
status block was restored to the prototype's compact toggle switch, which had been replaced by a pill plus a
labelled button that duplicated the header's status and squeezed the copy into five lines.

**4. ⚠️ The baseline set gained a new member, mechanism unknown.** One run showed 10 non-green, the extra
being `PublicStorefrontLandingTest`. Not this change: the diff cannot render that page, a stashed run at HEAD
showed the usual nine, a second run with the change was clean at nine, and it passes 14/14 in isolation. A
hypothesis ("a generated id contains the literal `369`, which the test forbids by substring") was **measured
and rejected** — ~0.004% per identifier. Recorded as unknown rather than written up. See §N.4l; the baseline
is now **5–10**.

**#42 measured in-browser:** own units 200 · an operator-owned unit in the same org 404 · another property's
unit 404 · nonsense 404. **Read-only honoured**: `units.updated_at` still the seed time, `inventory_blocks`
still 2, `rate_calendar` untouched, `restrictions` 0. No schema touched, so no `migrate:fresh`.
Suite **1836 / 12,040**; PHPStan L5 (0); Pint clean; 0 overflow, 0 console errors.

---

## 2026-08-10 — DESIGN-PARITY · OWNER 11/32 · **Owner Statement PDF** (`/owner/statements/{statement}/pdf`)

Template/markup only. **A premise correction, a trap, and a re-verification that held.**

**1. O2 already covered this document.** The gate was briefed as the separate PDF "Owner-2 flagged", but O2
decoded this same prototype, measured the generated PDF's content stream and fixed the pre-AA greys. So this
was a layout diff plus re-verification — and **O2's fix held**: the document's own ink measures 5.18–15.40 on
white paper.

**2. 🔴 The comment documenting that fix made the guard impossible — and shipped the old values.** No test
guarded O2's work, and the greys had already survived one full pass undetected. Writing the guard failed
immediately, because the explanatory note was a **CSS** comment inside `<style>` — and a CSS comment is
rendered, so `#8A8079` / `#A69A92` were present in every generated statement's markup. Moved to a Blade
comment (stripped at compile); the guard now exists and is mutation-proven. Generalised as §N.4m — the third
form of this trap, after O6's component-tags-in-CSS-comments and `DesignTokenContrastTest`.

**3. Footer fixed.** dompdf broke the date across lines and hyphenated the contact address mid-domain
("billing@vyrobel-/demo.test"). Explicit column split plus `white-space:nowrap`; verified in the regenerated
PDF.

**4. ⚠️ Four apparent contrast failures were my probe's error** — white text and two greys that sit on the
document's **dark plates**, scored against paper. Re-scored against the plate each actually sits on: 5.18 /
15.76 / 8.21 / 7.05, matching the template's own note. The single genuine on-white failure is the shared
`PdfService` watermark at 2.70 — the logged realm-wide item, explicitly out of scope.

**5. Logged, not fixed.** `MoneyFormatter::display(0, …, signed: true)` returns `"+€0.00"`, so the zero
OTA row prints a plus on a deduction. **Both** the screen and the PDF pass `signed: !isNet`, so fixing one
surface would split SCREEN == PDF; it belongs in the shared helper. Blast radius measured for that gate:
9 call sites, 0 tests asserting the string. Also logged: the `(provisional)` flag and "pending channel
reconciliation" footnote fire off that same €0.00 line while no reservation carries a `channel_id` (D2-2) —
a product question about the flag, surfaced not decided.

**Verified:** #17 from the PDF's own content stream — 1,080.00 − 194.40 (the real 18%) = 885.60, printed 3×;
by-unit 540 + 540 = 1,080; ADR 135.00 = 1,080 ÷ 8; SCREEN == PDF. #28 — masked `IBAN ···· 1000`, no raw IBAN
(structurally un-emittable), no guest data. #42 — **mutation-proven** (dropping the controller's scope turns
the cross-owner test red); a live foreign probe was impossible because the demo DB has exactly one owner, and
seeding a second for a screenshot would mutate demo data. Greyscale-safe (states are words and a minus sign).
⚠️ The export writes an `export_downloads` audit row per download, by design; no money row moved. No schema
touched. Suite **1837 / 12,042 · 9 non-green** (documented set); PHPStan L5 (0); Pint clean.

---

## 2026-08-10 — 💾 SAVE v6 (documentation/memory only) — supersedes SAVE v5

v5 captured the operator design-parity realm complete at the moment the owner realm began. **v6 captures the
owner realm IN PROGRESS — 11 of 32 (`6d974d7` → `64272cf`)** — plus everything that pass has changed about
the project's own understanding of itself. No application code, tests, migrations or seeds were touched.

**What v6 adds to the canonical record (`docs/PROJECT-STATE.md`):**

- **§N.4b** — the per-gate owner table, 11 rows, with a summary of what the realm has returned: **three 🔴 real
  defects** (a chart misreporting money twice; a wizard submittable by Enter from step 1, which filed a
  genuine `unit_requests` row; a money-affecting calendar unusable by keyboard, 31 of 36 controls
  `div@click`), **one realm-wide a11y item CLOSED** (P29, fixed once in the shared bell), **one
  always-passing assertion class** repaired across 36 test files, and **six brief premises corrected**.
- **§N.8c — THE TWO STANDING TRAPS.** (1) `not->toContain($needle, $message)` can never fail (Pest's
  `toContain` is variadic). (2) **A CSS comment RENDERS; a Blade comment does not** — three forms now seen,
  the worst being a fix's own comment shipping the OLD pre-AA hex into every generated statement PDF, which
  hid the regression for a full pass and made the obvious guard impossible to write.
- **§N.9 — REOPENED OPERATOR-REALM ITEMS.** The operator realm is 42/42 design-matched, but the owner pass
  surfaced six items that touch it: the shell's on-grad tokens failing at **2.95 / 2.77** (set from ONE pixel
  at `93247e7`), §N.4k implicit submit (check New Reservation + Group Booking), keyboard-unreachable
  `div@click` grids (Calendar, Front Desk, Housekeeping, Maintenance), the `max(2,pct)` min-bar floor (four
  owner pages → fix once on `<x-charts.bars>`), the realm-wide PDF-contrast sweep (~15 templates +
  `PdfService.php:54` at 2.70), and the white-on-gradient family + the accent-as-text sweep (**22 fixed**).
  ⚠️ **Each needs its own gate. None may be half-fixed from inside a page gate.**
- **§N.10 — the owner running lessons**: verify-don't-assume *but don't over-generalise* · a refusal can be
  **structural** · **don't seed demo data to enrich a screenshot** · **"read-only" ≠ "writes nothing"** ·
  **a probe can produce false failures** (three gates running) · backlog items compound.
- **G.4 / G.5** — two further founder calls surfaced and deliberately **not decided**: **D16-7** (a
  `processing` payout appears in NO KPI on either realm — a Stripe-live prerequisite) and the Owner-5
  "earned this month vs statement" figure definition (both figures correct; the €180 gap is a real
  unstatemented expense).
- **§J.quater — the baseline widened from 5–9 to a 5–10 RANGE.** One OWNER-10 run added
  `PublicStorefrontLandingTest`; proven not to be the change (stashed run at HEAD showed nine, a re-run with
  the change was clean, 14/14 in isolation) and the obvious mechanism was **measured and rejected**
  (~0.004%). Recorded as **UNKNOWN**, not written up as a finding.
- **(D)** — the owner-realm engine set is now named explicitly (`OwnerBlockService`,
  `OwnerStatementBreakdown`, `OwnerOccupancy` *keyed by physical unit id*, `OwnerUnitRequestService` *which
  creates no unit*, `Iban::masked`, `PdfService` never-touch), together with the corrected **#6 split** and
  the warning that `SettingsService`'s JSON merge is **not** universal (several stores are typed columns).
- **(E)** — **#42** sharpened: a per-unit or per-statement surface keyed on a URL id is the classic isolation
  surface; a foreign id must 404 **including an operator-owned unit in the same organization**, and a
  downloadable PDF is the worst-case leak.

**⬅ NEXT = OWNER PAGE 12 of 32.**

---

## 2026-08-10 — DESIGN-PARITY · OWNER 12/32 · **Owner Settings hub** (`/owner/settings`)

Design/markup only, plus one view-model addition and one stale comment.

**1. Premise correction — password-change is not on this page.** It lives on `/owner/security`
(`ChangePasswordController@owner`: throttled, self-only, current password verified, the shared rotate path
that terminates sibling sessions), together with 2FA. `Owner Security.html` is a **separate prototype for its
own gate**, so no password or 2FA surface was touched here.

**2. The honesty find — a badge truthful by accident.** The "Verified" pill was gated on
`pending_email === ''` — *"no change pending"*, not *"this address is verified"*. A user with a null
`email_verified_at` would have seen a green Verified beside an unverified address. It was **not** lying today,
because every owner-realm invite path sets `email_verified_at` on accept — so it was truthful by coincidence
rather than by construction. Recorded as latent, fixed to read the fact it claims, and locked with a
mutation-proven guard. Generalised as §N.4n — including that **a mutation which does not apply proves
nothing**: the first attempt used a `perl` substitution whose escaping silently failed, and the "passing" run
was meaningless until the edit was asserted.

**3. The page's only unstyled control.** The avatar upload rendered as the raw browser widget ("Choose File /
No file chosen") and had no accessible name. Now a design-system pill with an `aria-label`, using `opacity:0`
and **never `display:none`** — a hidden control is an unreachable one (the GATE-30 defect).

**4. Contrast 20 → 9.** Seven accent-as-text links, every field label (`$lbl`) and two helper lines moved to
the right existing tokens. ⚠️ `$err` — the validation-error style — only renders in a state that cannot be
reached without submitting, so it was **probed and measured (4.81)** rather than assumed correct.

**5. What the prototype fabricates, and the product already refuses.** This is the densest fabrication surface
in the owner realm: a stored **Visa ···· 4242**, a **15%** fee (real: 18%), **"Signed in … Dublin, IE"** — the
GATE-33 login-location refusal, committed again by the design — a **"since 2023"**, and a **"Guests · Guest
contacts & lifetime value"** export that would hand an owner a guest contact list (#28). All were already
refused; verified present-and-correct and recorded so none is "fixed" later.

**Read-only honoured** across all 7 forms: user and owner rows, `owner_users`, `payout_methods` and
`export_downloads` all unchanged (⚠️ unlike the O11 statement PDF, *viewing* settings writes no audit row).
No raw IBAN. Keyboard: 66 named, 0 unnamed, 0 `display:none`, 0 `div@click`. No schema touched. Suite
**1838 / 12,058 · 9 non-green** (documented set); PHPStan L5 (0); Pint clean.

---

## 2026-08-10 — DESIGN-PARITY · OWNER 13/32 · **Owner Security** (`/owner/security`)

Design/markup only. **Two premise corrections and one shared-partial fix.**

**1. ⚠️ There is no 12-character password policy — and acting on the brief would have made the page lie.**
The gate brief asserted a "12+ char policy, honest, never weakened". Every password path in the codebase is
`PasswordRule::min(8)` (verified across all ten: change, reset, both invite-accepts, onboarding, guest claim,
guest account, guest reset, admin). The page says *"At least 8 characters"* — exactly what the server
enforces. Restoring a 12+ claim would have made a **security** surface overstate its own protection. Nothing
was changed. Generalised as §N.4o: check copy isn't *stronger* than the code, not only that it isn't weaker.

**2. The security score is real.** An earlier note recorded "a checklist, never a score". There is a score —
40/100 · Basic — deterministically derived from three true facts. Note corrected.

**3. 🔴 A shared partial was carrying one page's layout width.** `two-factor-panel.blade.php` renders in both
shells and hardcoded `max-width: 560px`, the operator column; on the owner page it was a lone 560px card among
eleven 1184px ones. Moved to the callers. **The operator page was measured before and after and is
unchanged.** Guard mutation-proven, with the mutation asserted-as-applied. Third form of the shared-partial
trap, after O6's class-only-one-shell-defines.

**4. Contrast 10 → 6.** The real "This device" session badge (3.69 → 6.10 via `--vy-green-text`), the
score-tier pill (3.84 → 4.74, the O10 tint-assumes-a-card shape), and five accent-as-text sites including the
destructive Deactivate heading and button (→ 5.23, hue kept so danger still reads as danger).

**5. The prototype is the most dangerous in the realm, and the product already refuses all of it:** a
**fabricated pending sign-in** ("Chrome on Windows · Porto, Portugal · IP 82.14.x · Deny/Approve") presented
as a live security decision — GATE-33 at its worst; **"Trusted locations"**, which needs geolocation the
product doesn't have (live implements trusted *devices*, the real concept); and the mock's own weaker policy
copy. Every badge reads its real fact — no hardcoded green (GATE-24).

**Read-only honoured across all 7 forms**: password hash byte-identical, `two_factor_secret` null, status
active, sessions 1, passkeys 0. §N.4k proven empirically — seven forms, each with one submit scoped to its own
action, so nothing benign can reach Deactivate. No schema touched. Suite **1839 / 12,061 · 9 non-green**
(documented set); PHPStan L5 (0); Pint clean.

---

## 2026-08-11 — DESIGN-PARITY · OWNER 14/32 · **Owner Documents** (`/owner/documents`)

Design/markup only. A list of downloadable money documents, so isolation was the core check.

**1. Premise confirmed, plus a sibling.** `/owner/documents` is a distinct route — and `Owner Archive.html`
is a **second prototype that is also a live page** (`/owner/archive`). That is its own gate; nothing here
touched it.

**2. A nav "defect" that verified as correct.** The sidebar "Statements" targets `/owner/archive`. There is
**no `/owner/statements` index route** — the statement *list* IS the archive; only the `{id}` detail and the
PDF routes exist. Checked before reporting, so it was never written up as a finding.

**3. Contrast 10 → 2, and the page's own ink is now fully AA** (both remaining failures are the shell's
gradient family — the realm's best result so far). ⚠️ **"full archive ›" measured 2.70**, the worst
non-family site on any owner page, and **no accent tone reaches AA on that gradient** (the darkest,
`--vy-accent-tint-text`, only reaches 3.85). So the link takes `--vy-on-grad` **and an underline** — its
affordance no longer depends on colour at all. The six per-month net figures and the header line were moved
to the right existing tokens.

**4. #17 reconciles on the page itself:** the six monthly nets sum to €3,690.00, exactly the annual tax
card's figure.

**5. #42 mutation-proven.** The fixture already seeds a second owner with a distinctive €500 statement,
asserts his document links are absent from the first owner's list, and asserts a cross-owner PDF fetch 404s.
Dropping the scope from the statements query turns three tests red.

**6. 📌 DO14-1 — a third form of D16-7, logged not fixed.** `payoutStatusMeta`'s `default =>` collapses
**`processing`** and **`held`** — both genuinely written by `OwnerPayoutService` — into "Payout pending". So a
remittance advice for money actually in flight, or held by the tax hold, is **mislabelled** (O3's tiles
dropped such a payout entirely; here it is merely wrong). The map is a **shared** helper used by several
closed owner pages, so it belongs to the D16-7 gate.

**7. ⚠️ A suspicion measured and dismissed.** A too-wide guard tripped on `color:var(--vy-accent)` coming
from the shared notification bell. Rather than log it, I measured it: **5.06 on the panel surface — it
passes.** Recorded as a negative sweep and the guard re-scoped to this page's own markup. *An unverified
guess is not a finding — and neither is a guard's scoping artifact.*

**Read-only:** nothing was downloaded this gate — `export_downloads` still 7, statements 6, payouts 6, max
statement `updated_at` still the seed time; opening the notification panel left 6/6 unread. No PDF template
touched, `PdfService` not opened. No schema touched. Suite **1840 / 12,068 · 9 non-green** (documented set);
PHPStan L5 (0); Pint clean.

---

## 2026-08-11 — DESIGN-PARITY · OWNER 15/32 · **Owner Team & access** (`/owner/team`)

Design/markup only. An authorization surface, so scope and gating were the core checks — both
mutation-proven rather than asserted.

**1. Two premise corrections.** (a) The invite is a **direct owner action**, not a request to the operator —
Owner-9's "owners can't create units" finding does not generalise. (b) The gate is **stronger** than the
brief assumed: `EnsurePrimaryOwner` covers the **read screen** as well as every mutation, so a sub-user —
even a co-owner — gets 403 on the page rather than a control-less view. Team management is held by **no
role**, which makes self-escalation structurally impossible.

**2. Both guards mutation-proven.** Removing `EnsurePrimaryOwner` from the route group turns the sub-user
refusal test red; dropping the `whereIn('owner_id', $ownerIds)` from the member resolve turns the
cross-owner-404 test red. ⚠️ A live sub-user probe was impossible — both demo sub-users have never signed in
(which is exactly why the page truthfully says "Hasn't signed in yet"), so the tests are the proof.

**3. The role matrix is the real capability grid**, area for area, from the single `OwnerTeamRole` source
that both the route gates and the UI read. Worth recording: it is deliberately **not a privilege chain** — an
accountant has documents and exports but not calendar; view-only has calendar but not documents. Only
co-owner is a superset.

**4. The page's only unnamed control was the invite email field** — placeholder-only, on the form that grants
another person access to the owner's money. Named, with a mutation-proven guard.

**5. Contrast 11 → 7.** Role pills and the member-count badge moved to the right existing tokens. ⚠️ All seven
survivors are the white-on-gradient family — **including a new green variant**: the member avatars sit on a
green gradient and measure 3.61, so that family is **not purely accent-coloured**. Recorded for its gate.

**6. GATE-33 held.** No fabricated "last active {city}" — the rows read the real `last_login_at`, and the
helper's docblock says so explicitly. The security note's watermark claim is true, and the page proves it by
showing the real export-download log.

**📌 DO15-1 — no seat count or team limit is shown anywhere.** The Essential team limit (#10) is tied to the
billing decision (G.1). The honest outcome today is that the page advertises **nothing** about seats, so no
unenforced limit is implied. Surfaced, not decided.

**Read-only honoured across all 9 forms** (invite, two role-changes, two removes, resend, revoke, plus the
shell's): users 8, owner_users 2 with roles and `updated_at` unchanged, 1 pending invite, `model_has_roles` 7.
**Nothing invited, no role changed, no one removed.** §N.4k proven on a privilege page — no field can reach
another member's form or a Remove route. No schema touched. Suite **1841 / 12,070 · 9 non-green** (documented
set); PHPStan L5 (0); Pint clean.

---

## 2026-08-11 — DESIGN-PARITY · OWNER 16/32 · **Owner Payout Method** (`/owner/payout-method`)

Design/markup only. Real banking — the most sensitive owner surface, and the most fabrication-dense prototype
in the realm.

**1. What the prototype ships, and the product refuses.** An entire micro-deposit verification system that
does not exist ("Verifying" badge, "Send micro-deposits & verify", a Deposit-1/Deposit-2 confirmation modal,
"reversed automatically within 5 business days", "Resend deposits"), a fabricated **"Next payout €10,370 ·
Estimated 3 Nov 2025"**, a 48-hour default-change hold, and a two-business-day SEPA-trace SLA. Live shows the
**real €885.60** with **no invented date**, and every retained line is qualified *"with your live banking
connection"*. Verified present-and-correct, and recorded so none of it is "fixed" later.

**2. The structural fact that decides the page.** `payout_methods` has **no verification column at all**, so a
"Verified" badge would be **pure fabrication** rather than a stale value — which is why the refusal is
correct and not merely cautious. And the full IBAN is stored **encrypted**, with only last4/country in the
clear, so `Iban::masked` makes a raw IBAN **structurally un-emittable**. Swept the rendered DOM: **0**
IBAN-shaped strings, only `···· 1000`. Mutation-proven — emitting `$m->iban` turns the masked-IBAN test red.

**3. 🔴 The validation-error flash measured 3.03 — on a banking page**, where an error is precisely what must
be readable. `.vy-flash.is-err` used `--vy-amber` (a fill tone) as text; now `--vy-amber-text` at **5.08**.
The error only renders after a failed submit, which read-only forbids, so it was **probed into the DOM and
measured**. Also fixed: the pre-AA `#9C9089` literal still living here at 2.55–2.66. Contrast **13 → 6**, all
survivors the gradient family.

⚠️ **On the shared flash rule:** it exists in both layouts, but they are **independent rules with different
palettes** (owner amber, operator accent) — and the operator layout has **no `--vy-amber-text` token at
all**, so a blanket change was not even expressible. Checking first kept the fix owner-only; blast radius
verified across all three owner consumers.

**4. ⚠️ A guard of mine was vacuous, and only mutation caught it.** Tightening `assertDontSee('Verifying')` to
also forbid "Verified" looked right — needle-only, correctly negated, on rendered output — but it sat in a
test whose fixture **adds no payout method**, so the account row never rendered and the injected fake badge
passed. Relocated, re-mutated, now genuinely red. Generalised as §N.4p: **a `DontSee` guard must live where
the thing it forbids would render** — and this is the third distinct way a green test has defended nothing in
this pass, all three found by mutating rather than reading.

**Read-only honoured** across all 7 forms (a **fake** IBAN was typed to see the design, never submitted):
`payout_methods` still 1 with the same last4, default flag and seed-time `updated_at`;
`owner_payout_preferences` still 0; owner tax fields still null. **No account added, changed, removed or set
primary.** §N.4k — Enter in the IBAN targets Save, never the DELETE form. No schema touched. Suite
**1841 / 12,071 · 9 non-green** (documented set); PHPStan L5 (0); Pint clean.

---

## 2026-08-11 — DESIGN-PARITY · OWNER 17/32 · **Owner Balance** (`/owner/balance`)

Design/markup only, plus one stale-docblock correction and one new guard. Real money, and the D16-7 surface.

**1. ⚠️ The premise inverted — Balance is the D16-7 *reference implementation*.** It was briefed as another
victim; in fact it is the one owner surface that treats the engine's persisted `processing` and `held` as
first-class, using its own `rowStateMeta()` rather than the shared `payoutStatusMeta` that collapses both into
"Payout pending" (O14). **On hold** = persisted `held` + paused pending; **In transit** = `processing` +
released pending. **The D16-7 gate should copy this, not design a new fix.**

**2. 🔴 Those buckets were completely unguarded.** Dropping `processing` from In transit, or `held` from On
hold, left every Balance test green — **and** `PayoutEngineSurfaceTest`, the one test that creates a real
`held` payout, because its identity is computed from the **DB** rather than from the component and its
`assertSee('On hold')` passes on the tile's static label. A new guard asserts the **component's own buckets**
in both states with the five-term identity intact; mutation-proven both directions. ⚠️ Found only because the
mutation was run and **passed** — the O16 lesson repeating one gate later (§N.4q).

**3. Contrast 28 → 4.** The **On hold tile's own label and sub** measured 3.08 / 2.98 — on the tile the whole
D16-7 question hangs on — and on that accent tint **no grey token reaches AA** (`--vy-on-grad` tops out at
4.31), so they take `--vy-ink` (6.80 / 6.57) with hierarchy carried by size and weight. Twenty row links and
the Payouts link moved to `--vy-accent-text`.

⚠️ **One survivor is deliberate:** the disabled "Withdraw €963.60" button measures 2.33 and was **left**. It is
formally exempt (inactive components), and darkening a control that **cannot act** would make it look live —
the worse honesty failure on a money page. The amount is stated at full contrast directly above.

**4. Stale docblock corrected.** It described a **four-term** identity with pending-only buckets — contradicted
by the code eight lines below it and by the test asserting **five** terms (including Carried forward).

**Verified:** the identity reconciles on screen — 963.60 + 885.60 + 0.00 + 2,804.40 = **€4,653.60** = Net;
self-scoped; masked IBAN only; statuses are distinct words; withdraw genuinely `disabled`/`aria-disabled`.
**Read-only** — nothing withdrawn or transferred; payouts still 6 (`pending`/`paid` only), seed-time
timestamps, 29 ledger rows unchanged. No schema touched. Suite **1842 / 12,078 · 9 non-green** (documented
set); PHPStan L5 (0); Pint clean.

---

## 2026-08-11 — DESIGN-PARITY · OWNER 18/32 · **Owner Bookings** (`/owner/bookings`)

Design/markup only. A list/rollup + money surface; the first half of the list→detail pair with Booking Detail.

**1. 🔴 The chart's x-axis skipped empty months, and the revenue line stepped across the gap.** `chart()`
grouped only the months that have bookings, so with **November 2026 genuinely empty** the axis read
`… Sep · Oct · Dec …` and the net-revenue polyline joined **October straight to December as if consecutive** —
compressing two months into one step and misreporting a money trend while every individual bar was correct.
Fixed to a contiguous calendar axis; live it now reads May–Dec, eight consecutive months, with November at
0px. Guard added and mutation-proven.

**2. And that zero would have drawn a bar.** The min-bar floor was present **twice** — `max(2, pct)` in the
view **and** a CSS `min-height: 3px`. This is the fifth page with the bug and the first time it appeared as
CSS, which matters: the planned shared-`<x-charts.bars>` fix would not have caught it. Generalised as §N.4r,
together with the axis lesson — *for a time series, the axis is a calendar, not a list of periods that happen
to have data*.

**3. Contrast 21 → 3** (all survivors the gradient family). Ten "Confirmed" pills and the chart's current-month
net label sit on an accent **tint**, where `--vy-accent-text` is only marginal (4.51 / 4.33), so they take
`--vy-accent-tint-text` (5.40 / 5.19). The goal delta used `--vy-amber` as text — the O16 pattern — and the
breadcrumb and separator sat on the page gradient. ⚠️ The **over-goal** delta never renders in the demo, so it
was probed in and measured rather than assumed.

**Verified:** #42 and #28 both **mutation-proven** — unscoping the unit set turns the isolation test red, and
appending the guest email to the row turns the PII test red (which already seeds a guest with a real email and
phone). Live: two foreign booking ids → **404**, no foreign reference in the list, and a DOM sweep showing
**0 emails, 0 phone-like strings, 0 door-code mentions, `guest_id` never emitted**. Revenue is one-source via
`OwnerBookingsReader` + `OwnerLedgerAttribution`; **no OTA-brand split** (the real enum only — D2-2); "Not yet
attributed" never a fake €0. Nothing mutated (the page's only write, the monthly target, was not fired). No
schema touched. Suite **1843 / 12,084 · 9 non-green** (documented set); PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 19/32 · Booking Detail (`/owner/bookings/{booking}`)

Presentation-only. **#42 is this page's whole point** and was mutation-proven at its sharpest: a reservation
has no `owner_id`, so the boundary is `findOrFail` **then** `abort_if($ownedLines->isEmpty(), 404)` against the
owner's own unit set. Live, a foreign booking sitting on an operator-owned unit **in the same organization**
404s. **#28 was mutation-proven on this URL specifically** — and the first mutation was **vacuous** (the view
echoed a key the component never provides), so the component had to be mutated too; that is the third
occurrence of the "a green mutation proves nothing" family, and it is now the assumed default.

**A docblock cited `OwnerBookingDetailContactTest`, which does not exist.** The guard is real — it lives in
`OwnerBookingsTest` and covers both the list and this URL — but a named-but-absent test reads as *stronger*
evidence than an unnamed one, and nothing in the toolchain flags it. Citation corrected.

Contrast **36 → 5**, the worst starting page of the owner realm: it carried **all three** pre-AA literals,
with `#A69A92` at **1.37** on the header gradient. The four-state status pill was re-plated (green 5.96 ·
amber 4.80 · red 6.08 · blue 7.19). The 5 survivors are the known white-on-gradient family — including a
guest-initials avatar at **3.09**, a new near-worst instance for that backlog item.

Refusals preserved: the guest's **email, phone and country** (the prototype shows all three; the page says
out loud that contact is handled by the management team, and "Message your team" routes to the operator
relay), the **OTA brand**, and the prototype's **15%** — the page shows the real agreement rate, **18%**.
Logged not built: an owner-scoped per-booking event timeline, and a per-booking document list.

Suite **1843 / 12,084**, 9 non-green — all in the documented non-deterministic set (`BookingImportTest` ×6,
`OwnerUnitDetailViewTest`, `StubViewsTest` ×2). PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 20/32 · Booking Summary PDF (`/owner/statements/{statement}/bookings`)

First of a three-document batch, all measured on paper. **The document's design was already matched at #56** —
every section of the decoded prototype is present — so this gate was the a11y axis, the same shape Owner-11
found on the Statement PDF.

**The finding that leaves the page: the screen and the PDF disagree about "nights."** For statement #6 the
statement screen prints *"8 nights · 2 units"* under the gross line; the PDF's Nights KPI prints **6**. Both
figures are real. The screen counts every physically occupied stay-night on the owner's units
(`PerformanceMetrics::roomNightsSold`); the PDF sums the nights of the bookings whose revenue this statement
actually claimed. Because the screen's number sits under the **gross**, it asserts that 8 nights produced
€1,080.00 when 6 did. Which nights belong under a gross line is a cross-surface money-label decision, so it is
**surfaced here and not decided** — neither figure was touched.

**Contrast on paper:** `#8A8079` (8 declarations, 25 text runs — every heading, table header, sublabel, KPI key
and the letterhead) at **3.86** → `#5F5853` at **6.98**; the footer's `#A69A92` at **2.74** → `#6C645F` at
**5.79**. Retargeted to the values `owner-statement` already carries so the two owner documents read alike,
and measured on the regenerated PDF's own content stream.

**The document printed on two sheets, the second carrying nothing but the footer.** dompdf applies its default
page margin *on top of* `.wrap`'s `padding-bottom`, so the footer overflowed the content box by a few points.
No HTML-level check could see it — the markup is valid — it took counting `/Type /Page` in the generated file.

⚠️ **A sixth vacuous-guard form, caught on my own new test.** The page-count guard **passed with the bug
reintroduced**: this file's fixture caps at two bookings (a third collides on availability) and two rows fit
either way, so the fixture could never reach the page boundary. Widening it did not help, so the guard was
**rejected** rather than shipped, and replaced with a rule assertion that is mutation-provable. A guard that
cannot fail is worse than no guard, because it reads as coverage.

Refusals preserved: the OTA brand, the letterhead city. Kept over the prototype: the full booking list (it
truncates at "8 of 18" — unacceptable on a record the owner keeps) and "Bookings this period" over "this
month". `PdfService` untouched; its `#a99b8e` watermark re-measured at **2.70**, and the pre-AA greys remain
in 14 other templates — two of them are pages 21 and 22 of this batch.

Suite **1845 / 12,087**, 9 non-green — the documented non-deterministic set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 21/32 · Remittance Advice PDF (`/owner/payouts/{payout}/remittance`)

**The premise was wrong in the safe direction, and correcting it mattered.** The prototype's *"Management fee ·
Fixed monthly −€150.00"* was flagged as a possible fabricated charge. It is not: the fee is fully modelled and
certified — `ManagementAgreement.management_fee_minor`/`_interval` → `OwnerAttributionService
::attributeManagementFee()` (pro-rated, idempotent, posted as an `owner_ledger` `management_fee` debit) → a
statement line → the advice. The demo agreement carries no fixed fee, so the line is **genuinely absent** — the
same shape as `payment_fee` in #70, not a refusal. Had I treated it as fabrication I would have written a
guard forbidding a **real deduction** from ever appearing, which would hide a genuine charge from an owner.
The new guard therefore proves **both** directions: no fee configured → no line; a real €150.00 monthly fee →
the certified service posts it and the advice states it. Mutation-proven by fabricating the prototype's exact
line into the document.

**D16-7 gained a fourth form, and this one is purely visual.** `payouts.status` has six values; the pill had
rules for four. A `processing` or `held` payout emitted `pill--processing` / `pill--held` with **no matching
rule**, so the plate and colour disappeared and the status rendered as bare text — exactly the two states
D16-7 is about. The label was never dishonest (`ucfirst` of the real enum), so nothing lied; it simply stopped
looking like a status. Fixed by giving both the pending tone, mirroring `OwnerBalance::rowStateMeta()` — the
reference — where the two share a tone and are distinguished by their word, so greyscale still carries them.

**Logged, not decided:** `payoutStatusMeta` still has no case for either state and collapses both to "Payout
pending" across Payouts, Documents, Statement and Dashboard. This document does **not** use that helper, so it
is on the honest side and nothing was diverged here. One `held` payout now reads three ways across the product
— "On hold", "Payout pending", "Held" — two of them honest and still disagreeing. That vocabulary belongs to
the D16-7 gate.

**Contrast on paper:** the same pre-AA pair as O20 (`#8A8079` across 14 runs, 3.86 → 6.98; `#A69A92`, 2.74 →
5.79), plus `#B26A08` carrying the PENDING pill and the PROVISIONAL flag — 4.24 on white but only **3.72 on
the plate it actually sits on**, at 8.5px bold → `#804C05` (6.24 / 7.11). The other three pills were measured
on their own plates and already passed (5.61 / 5.76 / 5.08).

#17 holds screen-to-document: +€1,080.00 − €194.40 + €0.00 = €885.60, and €194.40 is exactly the **real 18%**
(the prototype's 15% would be €162.00). IBAN masked and CAP_PAYOUT-gated; no guest data; #42 mutation-proven
both ways; PdfService untouched.

Suite **1848 / 12,099**, 9 non-green — the documented non-deterministic set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 22/32 · Annual Tax Summary PDF — and the 3-PDF batch closes

The last of the three documents, and the one with the most authority: an owner may hand it to an accountant.

**The #60 multi-year fabrication was already refused, structurally and more strongly than the brief assumed.**
The URL carries no owner id at all — the owner is resolved from the authenticated user — so a cross-owner
request is not expressible, and a year with no committed statements 404s. Live, 2025 returns 404 and 2026
returns the single real year. The period prints the real statement span ("1 Apr – 30 Sep 2026"), not an
invented calendar year.

**A stale docblock pointing the dangerous way.** Both docblocks on this document still claimed *"no
withholding (we don't compute it)"*. That went stale when the tax engine (#73) shipped: `annualSummary()`
emits a real `withholding_minor` bucket and a "Withholding tax" category that reaches the table like any
other deduction. The risk is specific — a developer trusting the comment might add a second withholding line
and **double-count a real payout deduction on a tax document**. Both corrected, and the behaviour pinned by a
guard asserting both directions (stated when real, silent when genuinely zero). This is the sibling of
Owner-19's finding: there, a docblock cited a test that does not exist; here, one denied a feature that does.

**#73's framing holds.** The document prints the withheld amount and **never a rate** — a first probe claimed
otherwise and was a false positive matching CSS `width: 33%`, the seventh probe FP of this pass. Logged, not
decided: when withholding is non-zero the document does not say the figure derives from *configured* rates,
where Reports carries an explicit "EXAMPLE rates" warning. Adding legal-facing wording to a tax document is
the founder's call.

**Contrast** was the widest spread of the three PDFs: `#8A8079` across 27 text runs at 3.86 → 6.98, including
`.neg` — the deduction figures themselves — and `#A69A92` at 2.74 → 5.79 on the footer, which is exactly where
the "not tax advice" disclaimer lives. The legal framing was the least legible text on the document.

#17 holds: €4,500.00 − €810.00 = €3,690.00, the month rows sum exactly, and €810.00/€4,500.00 is exactly the
real 18%.

**Batch verdict + recommendation.** Three PDFs now measured on paper, each carrying the identical pair
(`#8A8079` 3.86, `#A69A92` 2.74) and each fixed to the identical replacements. **12 templates remain, plus
`PdfService.php:54`'s `#a99b8e` watermark at 2.70 — the only shared one, on every page of every document.**
Recommendation surfaced, **not self-authorised**: fold the remainder into one dedicated gate and do the
watermark there, because a page gate may never touch `PdfService`, each template still needs its own
generated-file measurement (O21's `#B26A08` passed on white and failed on its actual plate), and the guest and
operator documents lie outside this pass entirely.

Suite **1850 / 12,104**, 9 non-green — the documented non-deterministic set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 23/32 · Block Calendar (`/owner/calendar`)

**The Owner-10 keyboard lockout was still live here, in a second copy of the grid.** Unit Detail's calendar was
fixed at O10; this page carries its own day-cell markup in its own view, so that fix never reached it. An open
day was a `<div>` with `@mousedown`/`@mouseenter` and nothing else — no tabindex, no role, and zero keydown
handlers on any cell. Fifteen open cells, none focusable. Since selecting dates is the first half of blocking,
and blocking takes inventory off-sale through `OwnerBlockService`, **a keyboard user was locked out of a
money-affecting action on the page built for exactly that action.**

Every open day is now a real button with a spoken date, `aria-pressed`, a focus ring, and a keyboard range
path: the first Enter anchors, the second extends. It is bound to fire only for a click carrying no pointer
detail — keyboard activation — so the mouse drag behaves exactly as designed. Proven in the browser: 19–22
August selected entirely by keyboard, with zero network activity.

**The real lesson is DO23-2: the two grids are duplicated, not shared.** O10's fix had to be re-made here from
scratch, and the next change will have to be made twice again. One day-cell partial should own this.

**Premise correction:** a foreign unit does not 404 — `resolveUnit()` matches `?unit=` only within the owner's
own collection, so a foreign id falls back to the owner's first unit. Verified live: the foreign id appears
nowhere, only the owner's units are offered, and the posted `unit_id` stays theirs. No leak, structurally
refused, but a silent fallback rather than a 404. The write guard is separate and mutation-proven.

⚠️ **A vacuous guard, caught on my own new test.** My keyboard guard asserted the presence of the binding
expression — and that needle was satisfied by **my own code comment**, which quoted the same expression.
Deleting the real binding left the test green. The comment was reworded to stop carrying the literal and the
assertion tightened to the attribute form; both mutations are now red. This is the Owner-11 comment trap
reappearing on the other side — there a comment shipped a forbidden value into a PDF, here one satisfied a
test's needle.

**Contrast 22 → 3.** The past-day numbers measured **1.48**, the worst reading of the owner realm so far, and
the O17 "disabled stays dim" exemption does not apply — they are text, not disabled controls. The guest name
on the booked tint went to `--vy-accent-tint-text` per the O19 rule. The note input had no focus indicator and
no accessible name; both fixed. The three survivors are the known white-on-gradient family, none added.

Nothing was written: `inventory_blocks`, `max(updated_at)` and the availability sums are identical before and
after the walk-through.

Suite **1851 / 12,110**, 9 non-green — the documented set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 24/32 · Archive (`/owner/archive`)

**Indexed 24, not 25.** The brief asked for "25/32, confirm 24 ran first" — page 24 (Reports) had not run;
HEAD was O23 and the index read "NEXT = owner page 24". Archive was built as asked and takes slot 24;
**Reports is still outstanding.**

**The prototype's fabrications were already refused.** "3 years · 34 documents", "since June 2023", the
7-year retention promise and the `OS-2025-09` reference format are all unported with a guard forbidding each,
and the full-archive ZIP is a genuinely disabled "Soon" rather than a fake link. The live page shows the real
single-year history — "1 year · 6 statements", "since April 2026" — and zero fabricated needles.

**A docblock denied a real feature, twice.** `OwnerArchive.php` filed the per-year tax summary under NO
BACKEND, claiming there is no tax engine and no Annual Tax Summary PDF. Both are false since #73 — and that
document is the one gated at O22. A test comment repeated it. **The code was already correct**: the view links
`owner.tax.annual` per year, CAP_EXPORTS-gated. Acting on the docblock would have added a duplicate link to a
feature that already worked — the mirror image of O22, where believing the comment would have double-counted a
deduction. Both comments corrected. The rule stands: trust the code, not the prose about it.

**The ungated route is correct, and gating it would have been a regression.** `/owner/archive` carries no
capability middleware while `/owner/documents` carries `$capDocuments`, which reads as a gap until the matrix
is checked: `CAP_FINANCIALS` — "earnings & statements" — is held by all three owner roles, and an archive of
statements is exactly that. Adding a gate would have removed a view-only sub-user's intended access to their
own earnings. Every download control on the page is separately `@ownercan(CAP_EXPORTS)`-gated.

**Logged, not decided.** A finalized statement always reads "Payout pending" even when its payout is really
held for missing tax details. Archive uses the *statement* enum (all three values mapped), so it does not
inherit the D16-7 `payoutStatusMeta` collapse — but it cannot express a hold. Latent today; it belongs to the
D16-7 gate. And the per-year bars are sparse: a year without statements is skipped. That was **deliberately
not "fixed" as an O18 repeat** — these are labelled bars, not an unlabelled monthly line, and inserting a €0
bar for a year the owner was not yet onboarded would assert "you earned nothing that year", a #60-class
fabrication in the opposite direction.

#17 holds across surfaces: the six statements sum to €3,690.00 exactly — the lifetime hero, the 2026 bar, and
the same figure the Annual Tax Summary PDF stated at O22. Contrast 9 → 2 (both known shell gradient items).

Suite **1851 / 12,110**, 9 non-green — the documented set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 25/32 · Reports (`/owner/reports`)

**A month that earned nothing was drawing a bar.** The net-by-month chart carried a two-percent floor on the
bar height, applied twice. Live, five months that genuinely earned €0.00 each rendered a visible bar while
printing "€0.00" directly above them — the chart contradicting its own figure. The axis was already correct
(every month in the range is seeded and walked), so unlike O18 this was the bar alone; that distinction was
verified rather than assumed. Fixed to the honest value with a hairline only for non-zero months.

**A #60 guard that could not fail.** The reporting-year test asserted only positives — "Reporting year", the
current year, "Year to date" — while its comment claimed the fabricated years were gone. Nothing tested that,
so hardcoding the wireframe's 2026/2025/2024/2023 list left it green. Repairing it took three needles and the
two failures are the lesson: `assertDontSee('2024')` **fails on correct markup**, because a bare 2024
legitimately appears in the custom-range month picker; `range=year:2024` **can never fail**, because that is
not the href the page emits; only `year=2024`, taken from the rendered output, actually bites. A needle must
come from what the page renders, not from what the parameter is assumed to be.

**Both Archive carry-forwards were checked and neither applied.** The stale-docblock-denies-a-feature pattern
does not recur here — this docblock already describes the Tax & VAT summary as real and computed. It was
checked explicitly because a reporting page is a likely home for a stale "we don't compute VAT" note; the
clean negative is worth recording. And no €0 row was back-filled for a pre-membership year.

**Contrast 15 → 5, and the worst item was the honesty warning itself:** the "EXAMPLE RATES" tag — the #73
flag — measured 3.82, the least legible text on the page. That is exactly the shape O22 found, where the "not
tax advice" framing was the least legible text on the tax PDF. Fixed on its own inline override, because
`.vy-soon-tag` is defined in the shared owner shell and must not be retuned for one page.

The rate is the real 18% (−€1,076.40 on €5,980.00), mutation-proven, and #17 reconciles four independent ways
to €4,653.60. Logged not built: the month chart is a local implementation rather than the shared
`<x-charts.bars>` — which is precisely why this floor survived after being fixed elsewhere.

Suite **1852 / 12,115**, 9 non-green — the documented set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 26/32 · Reviews (`/owner/reviews`)

**Every rating rendered as five full stars.** The hero row repeated the same filled glyph for both the filled
and the empty half, so any average drew five gold stars. Live, a 4.5 displayed as a perfect 5 — and by that
code a 3.0 would have too. On a page that reads as social proof, the glyphs overstated the real figure printed
beside them.

Fixed by extracting the shared `<x-star-rating>`, which partly closes DO10-4. Three things it enforces: the
filled count **floors** — my first version rounded, which still showed 4.5 as a perfect five, and on a
social-proof surface the honest rule is never to round up; the remainder uses a **different glyph**, so
filled-vs-empty survives greyscale instead of depending on gold-vs-grey; and the row carries **one accessible
name** with the glyphs hidden, so a screen reader hears the rating once. Its colours are self-contained
literals rather than tokens, because it renders in the owner, operator, guest, public and email layouts — the
trap that once 500'd every owner page. The other ten call sites are untouched; migrating them is DO10-4's own
gate, and no twelfth hand-written copy was added.

**The #28 guard was strengthened, not just verified.** It asserted the surname was trimmed, but its fixture
gave the guest no email or phone — so it could never have proven that the review projection withholds contact.
A guest with no email cannot leak one. Contact is now seeded and forbidden, and both directions are
mutation-proven.

**Read-only is structural**: the page contains no review form at all, so there is no live-looking reply that
cannot post. The aggregates reconcile — 4+1+1+0+0 = 6 reviews, and (5×4 + 4×1 + 3×1)/6 = 4.5 exactly.

⚠️ **Contrast was NOT measured this gate, and that is a gap in the record.** The screenshot step timed out
repeatedly, including on a page that captured successfully earlier in the session, so the validated
hide-text-then-sample-the-pixel method was unavailable. I wrote a CSS-compositing fallback and rejected its
output: it scored white text at ratio 1.0 against a layer that is actually painted over, and reported 90
failures on a page whose predecessors measured 2–5. Publishing numbers from a probe I know to be unsound would
be worse than publishing none. **This page's contrast is outstanding** — including the new empty-star colour,
which was chosen but not verified against 1.4.11 — and must be measured in a follow-up.

Suite **1853 / 12,120**, 9 non-green — the documented set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 27/32 · Messages (`/owner/messages`)

**A hardcoded "1" stood in for an unread count.** The conversation row printed a badge reading "1" whenever
the thread had anything unread — but that value is a boolean, so a thread holding three unread messages
displayed "1". The Unread tab badge beside it is a real count, which made two identical-looking badges mean
different things. The row now shows a dot with spoken "Unread" and no digit; the tab kept its real number and
gained screen-reader context. A real per-thread count is logged as DO27-1.

**The #28 two-inbox boundary is structural, and stronger than a predicate.** `MessageThread` carries
`owner_id` with `BelongsToOwner`, and `OwnerScope` applies `whereIn(owner_id, …)` — a NULL `owner_id` can
never satisfy a `whereIn`, so every operator↔guest thread is excluded by the scope itself rather than by a
filter someone could forget to write. The demo holds three guest-side threads and two owner-relay threads, and
the page renders exactly the two. Mutation-proven by relaxing the scope to admit NULL.

**Enter in the composer is safe and deliberately so.** A single-line composer that sends on Enter is the chat
convention; the risk case is the other field, since the form also holds a file input. It cannot send an empty
message — `body` is `required` client-side and the controller refuses body-and-files-empty.

**Contrast 15 → 8, with a lesson about the rail.** The conversation rail is translucent over the page's peach
gradient, so its text lands on a saturated ground rather than a card: the timestamp measured 2.64, the tabs
2.85. Moving them to `--vy-on-grad` was **still not enough** (4.06–4.42); they needed `--vy-ink`. That is the
O14/O17 rule again — on a strong tint no grey reaches AA, and hierarchy has to come back through size and
weight. The eight survivors are all white-on-accent, including the message bubbles at 3.97, which is the
largest body of text that family affects anywhere so far, and the shared shell nav at 3.79, which was
deliberately left alone because retuning it from a page gate would change every owner page.

📌 **O26's contrast — which I could not measure last gate — was measured here.** The screenshot tool recovered
after a browser restart. Reviews shows 30 failures, and the shape confirms DO10-4 precisely: roughly seventeen
are the ★ spans that were *not* migrated to `<x-star-rating>`, while the migrated hero is exempt because its
glyphs sit behind an accessible name. Two extreme readings are boundary-sampling artifacts, not findings. The
fix is now scoped and unstarted, and was deliberately not folded into this gate.

Suite **1854 / 12,123**, 9 non-green — the documented set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 28/32 · Help (`/owner/help`)

**Premise corrected: the AI assistant is built, not deferred.** *Ask Vyrobel* shipped in Wave D as a grounded
assistant — it retrieves over the Help knowledge base first and escalates deterministically with **no model
call** when retrieval is empty, so out-of-scope and account-specific questions can never reach the model; a
per-owner token cap and a circuit breaker fall back to the same grounded help. Locally the fake client is in
use with no key, and a test proves the fake is forced outside production even when a real key is present. The
page's own docblock still listed "the AI Q&A" as refused — stale since Wave D, and the third instance of a
docblock denying a feature that exists. Corrected.

**The words came out; the signal stayed.** An earlier pass removed "All systems operational" because nothing
observes system health, and a guard still forbids that string. But the *visual* half of the claim survived: a
green presence dot under a heading reading "System status", beside a sentence that is not a status at all.
A green dot under that heading asserts "operational right now" as loudly as the words did, with nothing behind
it — and it was colour-only besides. The dot is gone and the heading now says what the line actually is. This
is worth remembering as a class: **removing a false claim's text does not remove the claim if its visual
signal remains.**

**A false alarm I raised and cleared:** my needle matched "replies in", which turned out to be "your team
replies *in your inbox*, with full history" — a location, not a time SLA. Read the context before calling a
claim, which is the O25 needle lesson pointing the other way.

Ratified refusals: the page makes **no** presence or SLA claim (the prototype's "desk online · replies in
under an hour" is simply absent), and there is no ticket queue. Whether to promise an SLA at all is the
founder's call; making no timing claim is the honest position today. Contact is honest too — there are zero
`mailto:`/`tel:` links, and the card points at where the real number lives instead of inventing one.

**Contrast 7 → 5.** The AI disclosure line — the sentence telling the owner to verify what the assistant says
— measured **4.48**, the least legible text on the page. That is the third time the honesty text has been the
worst reading (O22's "not tax advice", O25's "EXAMPLE rates", now this), which is starting to look like a
pattern worth watching rather than a coincidence. Two of the five remaining failures are boundary-sampling
artifacts, not findings.

Suite **1855 / 12,127**, 9 non-green — the documented set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 29/32 · Onboarding / Welcome (`/owner/welcome`)

**Premise corrected: this is not a wizard.** It was briefed as a multi-step mutation flow with per-step writes
and an Enter-safety risk. It is a 73-line read-only hand-off page — no form in `main` at all, no input of any
kind, every next step an `<a href>` to the page that actually owns the write. So §N.4k is moot here: nothing
submits, and the Owner-9 Add-Unit trap was checked rather than inherited. "Observe read-only" was structural,
and confirmed anyway.

**A status flag gated on the wrong fact issued a false instruction.** `hasPayout` read `$units->isNotEmpty()
&& PayoutMethod::exists()`. Whether an owner has units has nothing to do with whether a payout account is on
file, so an owner who had already saved one but had no units yet fell into the empty branch — which does not
merely show a wrong badge, it tells them to *"add a bank account on the secure Payout Method page"*. That is
the fabricated-status family in its understating direction, and it is the more damaging one: a false "not
done" issues a false instruction, where a false "done" only misleads. Fixed and mutation-proven.

**My first contrast measurement was unsound, and I discarded it.** It reported 22 failures, several of which
scored text against its own colour — the screenshot had fired before the hide-text style painted. The probe
now asserts every candidate computes to a transparent fill *before* capturing, and freezes the entry
animations, since these cards fade in on a delay. That is the O26 rule applied to my own run: when the probe
is unsound, throw the numbers away rather than publish them. From the sound baseline the page went 12 → 4.

The remaining lesson for the realm: these cards composite over the hero peach, where `--vy-accent-text`
reaches only ~3.3 and even `--vy-accent-tint-text` stops at 3.74. Both the step chips and the links needed
`--vy-ink`, with the links keeping their affordance through an underline and arrow rather than colour — the
O14/O17 rule, now hit on three consecutive owner pages.

Suite **1856 / 12,130**, 9 non-green — the documented set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 30/32 · Expenses & maintenance (`/owner/expenses`)

**Premise corrected: the third state is `queried`, not "rejected".** A queried expense is not refused — it is
held out of the net while a conversation opens in the owner↔operator relay. Approve posts the debit, query
posts nothing, and a second decision is blocked.

**#17 reconciles in both states, and the page is stricter than the brief required.** Spent-this-year €709.00,
awaiting-approval €84.00, charged-to-payouts €250.00 — all three tie out against the database. The
interesting part is the third: there are five *approved* expenses totalling €745.00 but only two ledger rows
totalling €250.00, because three approved rows carry no ledger attribution (the migration back-filled historic
rows as approved, and a non-deductible expense never posts a line). The KPI counts **the ledger, not the
approval state**, so it claims €250.00 — only what actually hit the owner's money. Both directions are
mutation-proven.

**A stale test name — the fourth stale claim, one layer out.** `OwnerExpensesViewTest` was titled "…and
invents no approval workflow". Its body was updated when Wave C GATE 2 shipped the real machine — a comment
inside even says so — but the name was not, and the name is what CI output and a grep show, where it reads as
an assertion that no such workflow exists. Owner-22, -24 and -28 each found a docblock denying a shipped
feature; this is the same shape in a test name, and worth watching for as a distinct place staleness hides.

**Contrast: the translucent-band problem is now a realm pattern, not a page quirk.** This is the fourth
consecutive owner page whose KPI band or rail is translucent over the hero gradient, dropping shell greys to
3.05–4.35. Fixed here in a page-scoped style block, with the shell's own definitions deliberately untouched —
retuning those would change every owner page and belongs to its own gate. Worth surfacing as a candidate
shared fix once the realm is complete.

Also worth recording: seven of the eight remaining "failures" are mis-samples of small elements — pills and
29px buttons whose sample point lands beside them, each verified to carry the standard accent gradient. The
probe is reliable on body text and unreliable on small controls; that limitation should be stated wherever its
numbers are.

Suite **1856 / 12,130**, 9 non-green — the documented set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 31/32 · Tax Center (`/owner/tax`)

**A fourth docblock denying a shipped feature — and a pattern worth naming.** The view's header block said
"WITHHOLDING is NOT modelled", which went stale when the #73 tax engine shipped; the component itself reads
the real `withholding_minor` summed over committed statements. Corrected. What makes it worth recording is
that **three of the four such findings (O22, O24, O31) concern the tax engine specifically** — Wave D shipped
without sweeping the surfaces that had previously, and correctly, refused it. That is a predictable shape:
when a long-refused capability finally lands, the refusals it invalidates are scattered across every surface
that documented them.

**#73 holds exactly as it did at O22 and O25.** Withholding is real and the page prints an amount, never a
rate: live it reads "None · withheld from payouts" with an honest residency explanation, matching Σ
`withholding_minor` = 0 exactly — a genuine zero, not a refusal. There is not a single percentage sign
anywhere on the page in any state. Mutation-proven in both directions.

**Logged, not added (DO31-1):** the page carries no explicit "not tax advice" line where the annual PDF and
Reports do. Structurally it needs one less — it presents no rate and no computed determination, only a real
amount and a link to the PDF that does carry the disclaimer. Whether it should nonetheless carry formal
wording is DO22-1, the founder's call; writing legal copy is not a design gate's job.

**Two contrast lessons.** First, the honesty text was checked first per the O22/O25/O28 pattern — and this
time it was *not* the worst reading. The pattern is worth keeping as a priority, not as a prediction.
Second, two readings were the O11 dark-plate false positive and were caught before anything was touched:
"About your tax" reported 1.00 and "Vyrobel keeps records…" 1.41, but both sit inside `.vy-card--dark`, where
white-at-60% actually measures 5.78. The probe now excludes dark-plate subtrees and scores them separately.

This is also the fourth consecutive owner page whose shell greys cannot reach AA over the hero gradient. The
shared fix remains a candidate for its own gate once the realm closes.

Suite **1856 / 12,130**, 9 non-green — the documented set. PHPStan L5 (0); Pint clean.

## 2026-08-11 — DESIGN-PARITY · OWNER 32/32 · Agreement — 🏁 THE OWNER REALM IS COMPLETE

The last owner page, and the one the whole realm's "18% not 15%" rulings trace back to.

**The rate is authoritative here, and it follows the engine.** Live it shows 18% with no 15% anywhere,
rendered through the shared formatter rather than a Blade literal. The existing guard already proved the
stronger property — that it follows the *data*, not a constant — by rendering 22.75% for a second owner, a
value no hardcoded literal could produce. Mutation-proven. The PDF prints the same 18%, so screen and document
cannot disagree.

**A sixth stale claim, and a new shape of it.** `commissionDisplay()` carried two stacked docblocks; only the
second binds to the method, and the first was orphaned text whose worked example read "15.00%" — the
wireframe's mock rate — on the very page that defines the real one. Merged, with no example rate quoted at
all, because the number belongs to the agreement. The previous five findings were claims that a shipped
feature did not exist; this one is a stale claim about a *value*, which is the same failure wearing different
clothes.

**A mutation that proved nothing, caught.** My first #42 attempt swapped `query()` for `withoutGlobalScopes()`
and the test stayed green — because the real guard is an explicit `whereIn('owner_id', …)` that the swap never
touched. Removing that line turns the cross-owner 404 red. Worth restating: when a mutation passes,
the first question is whether it actually removed the thing being tested.

**The honesty-text pattern reached its sharpest form.** On both surfaces the worst reading was the text that
disclaims the document's own authority: on paper, "…not a signed legal contract…" at 2.74; on screen, the line
explaining what acknowledging legally does at 4.25. Six occurrences now (O22, O25, O28, O31, and both halves
of this page). It remains a priority to check first, not a prediction.

**Realm status.** All 32 owner prototypes are design-matched. What remains is explicitly *not* per-page design
work: O26's Reviews contrast fix, the DO10-4 star migration across ten call sites, and the realm-wide PDF
sweep (11 templates plus PdfService's watermark) — each scoped, each recommended as its own gate, none
started. The Owner Invite Email is a separate template, then the guest realm's 32.

Suite **1857 / 12,132**, 9 non-green — the documented set. PHPStan L5 (0); Pint clean.

---

## 2026-08-11 — DESIGN PARITY · Owner Invite Email (`OwnerInviteMail`) 🏁 **THE OWNER REALM IS COMPLETE**

**Decision: match the "Owner Invite Email" prototype in email-safe form, and REFUSE the two places where
matching it would cost accessibility.** The owner realm's 32 pages were closed at `a884793`; this is the
33rd artifact and it is not a page — it is a Mailable rendering into Gmail/Outlook/Apple Mail.

**Rebuilt to the design:** the gradient brand BAND (previously absent entirely — the email had a small
left-aligned lockup on the page wash), the 600px card, the invited-by chip, the feature panel with tinted
tiles, the full-width CTA, the link-fallback panel with its hairline-divided expiry line, and the footer
BAND inside the card. Presentation only — `app/Mail/OwnerInviteMail.php` is **unchanged**; the send path,
the token minting and the role-copy matrix were not touched.

**Refused, with the measurement behind each:**
- **The gradient CTA.** White 15px normal text needs 4.5. The band's lightest rendered pixel is `#E66D38`
  → **3.18**; `--vy-accent-2` gives 3.57 and `--vy-accent-3` 3.09. **No warm orange lighter than `#C2410C`
  reaches 4.5**, so the gradient CTA is unreachable for AA rather than merely unbuilt. Solid `#C2410C`
  (5.18) stands.
- **The 82%-white eyebrow on the band** (2.55) → a solid `#B03808` plate (6.15).
- **The prototype's greys** `#8A8079` / `#9C9089` / `#A69A92` / `#B7ADA4` — the pre-remediation palette,
  all failing → the token values `#5F5853` / `#6C645F`. The footer/legal line was checked first and now
  measures **4.95**.
- **The inbox chrome, the Download-PDF button, and "Edit in Email templates"** — the first two are the
  mock's simulated mail client; the third is operator-app chrome that must never reach an invitee's inbox.
- **"Get help"** — `/owner/help` is authed and the invitee has no account; no public support URL is
  configured. Omitted rather than pointed at a link that bounces to login (`DO-INV-3`).

**⚠️ The probe lied, and only pixel sampling caught it.** `getComputedStyle` scores text against
`background-color`, so the wordmark on the gradient read **5.18** — that is the `bgcolor="#C2410C"`
*fallback*, invisible to any gradient-capable client. The true worst point is 3.18. Three sampling attempts
were themselves vacuous first (white glyphs read as background; then their antialias haloes; then the page
wash outside the card's rounded corners). Consequence: the brand lockup ships at **weight 700, not the
prototype's 600**, so "≥18.66px bold" is unambiguous rather than arguable.

**⚠️ A regression I introduced, caught by an existing test.** Extending `emails/layout.blade.php`'s docblock
closed the Blade comment early with a stray `--}}`, shipping the next paragraph as live template text into
all 15 emails that extend that layout. `OperatorPropertySettingsGate1Test` went red on *"not to contain
'Powered by Vyrobel'"*. **The raw-Blade family is not only `word@directive`** — an unbalanced comment
delimiter has the same effect, and static analysis sees neither.

**Logged, not built** (`MISSING-BACKEND-BY-PAGE.md` §O-Invite): `DO-INV-1` no `text/plain` part (HTML-only —
a real deliverability + a11y gap; not fabricated, because an invented text part drifts from the HTML
immediately) · `DO-INV-2` no dark-mode handling (the template *requests* light; Gmail and Outlook.com
force-invert anyway) · `DO-INV-3` no public help destination · **`DO-INV-4` a scoped EMAIL-CONTRAST SWEEP** —
the shared layout ships `#A99E95` at **2.24** on the wash, carrying the "why you're receiving this" line on
all 15 emails that extend it. Same shape as the PDF sweep, same recommendation: one dedicated gate.

Three guards added, **all mutation-proven** (`OwnerInviteEmailTest` 2 → 5): no `--vy-*` and none of the four
failing greys · `#28` no other owner and no guest in the render, with exactly two hrefs asserted as an
exhaustive set · `#39`/`#42` the org name and token BOUND per invite. Suite **1860 · 1851 passed · 12,160
assertions**, 9 non-green = exactly the documented set. PHPStan L5 (0); Pint clean. Nothing was sent:
`owner_invites` 0, `jobs` 0, `failed_jobs` 0.

**Runway now:** the GUEST realm (32, untouched) · the PDF-sweep gate · DO10-4 · the BACKEND phase.

---

## 2026-08-12 — 🏁 THE DESIGN-PARITY WALK CHAPTER IS CLOSED (SAVE v8)

**All three realms have now been walked page by page**, plus the first cross-realm sweep:
**operator 42/42** (`0aa9ede`→`edb7cce`) · **owner 32/32** (`6d974d7`→`a884793`) · **guest 12 gates over 32
prototypes** (`63f8a18`→`dd2e127`) · **the PDF sweep**, 17 templates + `PdfService` (`1c1f370`).

**What the guest realm decided, that the other two could not.** Its isolation model **inverted three times**
— scope-to-the-guest → *the signature is the authorization* → *the tenant is the scope, and the second scope
is whether the thing is even for sale*. Each time the certified guard was well covered and the **adjacent**
check was defended by nothing: `anonymize()` was **covered but untriggered**; the signed link's **lifetime**
was unproven (a permanent-link mutation passed every test while turning old inbox links into permanent
account-takeover keys); `properties.status` had **no test at all**, so one middleware line silently gated a
withdrawn property's entire booking funnel. All three now mutation-proven. **§N.13.1.**

**The watermark decision (the PDF sweep's central question) — RESOLVED, not deferred.** The "2.70 watermark"
is not a watermark: it is the **export-audit stamp** (*"Downloaded by … · Confidential"*), a footer line in the
bottom **margin**, not a faint mark behind content. It is therefore **informational and fixable — not a founder
decision**: darkening it defeats nothing, because there is nothing for it to compete with. Measured on real
ink at **2.70 on paper and 2.41 on a warm footer band** (the recorded 2.70 was the optimistic case) → now
`#5f5853` (**6.98**). ⚠️ What *is* deferred and surfaced: `position:fixed` leaves its plate
**non-deterministic** (`DO-PDF-2`) — the structural fix is 17 template layout changes.

**Method canon added (§N.13):** the mutation discipline is now complete in **both** directions — four ways to
falsely pass, and one to **falsely FAIL** (a bash-corrupted file threw a syntax error and was nearly recorded
as *"#33 re-proven ✅"*), hence **`php -l` after every mutation edit**. And **defence-in-depth needs per-layer
tests**: a redundant layer *masks* the failure of the layer beside it — removing `ArrivalPack`'s data gate left
25 tests green, including the one named for that very guard.

**Open decisions this chapter surfaced and deliberately did NOT take** (they are the founder's):
`DO-G10-1` (wallet credit has one production source — intended?) · `DO-G12-1` (the free-cancellation promise
states the org's most generous window unqualified) · `DO-G9-2` (the delete copy does not say deletion **keeps**
anonymized financial history) · `DO-G11-3` (the ratified refusals to preserve: social sign-in, open self-signup,
the join-time welcome credit).

**Next chapter: the BACKEND PHASE**, planned against the reconciled **43-item DO-register** at the end of
`MISSING-BACKEND-BY-PAGE.md`. ⛔ The **billing/pricing go-live blocker (§G.1) remains open and untouched.**

---

## 2026-08-12 — **G13: the three no-route guest prototypes get VERDICTS** (docs only, HEAD at start `e869d5d`)

**A verdict pass, not a gate.** `Guest Help`, `Guest Documents` and `Guest Referral Landing` have no live route,
so there was nothing to render, diff, measure or mutate — the gate machinery did not apply and was not run. Each
was **decoded fresh**, **verified against the engine** (does a model / table / route / service / **writer**
exist?), given a verdict, and **surfaced as a founder decision**. **No product code was written.** The memo is
`docs/design/G13-VERDICTS.md`; the register record is **§G-13** in `MISSING-BACKEND-BY-PAGE.md`; the items are
`DO-G13-1/2/3` under **founder decisions**.

**This closes the guest realm's prototype accounting: 32 prototypes = 12 gates + 3 verdicts.**

**The verdicts (recommendations — the founder decides):**

- **Guest Help → BUILD the FAQ centre · DON'T-BUILD the SLA/phone/24-7 half.** ⚠️ **RULED AND ACTED ON, 2026-08-12 (see the later entry):** the founder **split** it — the FAQ centre is **BUILT** (`23bf335`); the support half was **SCOPED, not refused** (§N.15.6), so the no-support refusal below stands only until that backend exists. ⚠️ **The BUILD reopens a ratified
  refusal**, and that is stated rather than smuggled: the 2026-08-07 audit gave the guest a real support
  destination (Account → *"Help & support"* → the relay, `82506bb`) and explicitly refused a help **centre**
  because *"its content would have to be invented."* **That premise has since been falsified by the product
  itself** — `app/Support/Help/HelpKnowledgeBase.php` is a curated, real, screen-linked FAQ, read by both the
  owner Help blade and the grounded assistant so they cannot drift. The pattern is proven; only a guest KB is
  missing. **Refused on every path (#39):** the *"replies under an hour"* pill, the Typical-reply tile, the
  phone/WhatsApp/hours and the **24/7 urgent line** — `properties` has no phone or support-email column, there is
  no SLA store and no on-call roster, and the same refusals are already ratified on `guest/messages.blade.php`
  and in `OwnerHelp.php`. ⚠️ The 24/7 line is not merely dishonest but **dangerous**: a guest locked out at 02:00
  shown an emergency number nobody answers is worse off than one shown the real relay.

- **Guest Documents → DEFER the hub · DON'T-BUILD the ZIP and the "12 months" · DEFER email-all.** ⚠️ **Downgraded
  from an initial BUILD read** once a ratified `DIVERGENCE-REGISTER.md` row was verified: the rollup already
  exists (Account *"Receipts & documents"* → `/guest/payments`'s *"Receipts & invoices"* → the receipt page → the
  five PDFs). Three hops, but reachable, and the cross-booking money figures already render on Payments via the
  shared `GuestPayments::overview()` (#17). The hub is a **convenience**, and building it reopens a ratified row.
  **"Download all (ZIP)"** has no backend at all (`grep -rln "ZipArchive" app` → nothing) — recommend **omitting**
  the control rather than disabling it. **"kept on file for 12 months" is FALSE and must never ship**: PDFs are
  generated **on demand**; nothing is stored, there is no retention window and no purge job — the §N.13.7
  policy-claim shape. 🟢 **One cheap win independent of all of it:** label the document kinds on the Payments
  rows — a guest cannot currently tell that their **VAT invoice** lives behind a button labelled *Receipt*.

- **Guest Referral Landing → DON'T-BUILD as drawn (#39).** G10's finding re-verified at `e869d5d`: `TYPE_REFERRAL`
  is an enum slot and a display label; the only production credit writer is `GuestWalletService::redeemCode()`
  (posting `TYPE_PROMO`). **Five subsystems are absent** — no referral-code entity (`promo_codes` is org-wide with
  no referrer column), no `referred_by` attribution, **no open guest self-signup** (booking-tied claim only — a
  ratified refusal, `DO-G11-3`), no first-stay reward trigger, no expiry rule. The page's core action would
  require **inverting the realm's identity model and inventing a money writer**. The founder's question is
  whether a referral **programme** is on the roadmap; ⚠️ a stripped landing over `promo_codes` is explicitly
  recommended against — it keeps the dishonest 80% and loses the point.

**⚠️ The `DO-INV-3` tie — RESOLVED: Guest Help DEPENDS ON it, does not RESOLVE it.** `DO-INV-3` is a **public**
destination problem (an invite recipient has no account, so an authed `/owner/help` bounces to login); a
`/guest/help` would be **authed**. They share ONE root cause — **no support contact is configured anywhere**
(`DO-INV-3`'s claim **re-verified and it survives**: `.env.example:72` is still
`MAIL_FROM_ADDRESS="hello@example.com"`, no `config/*.php` support address, no property phone column). **`DO-INV-3`
stays OPEN regardless of the Guest Help decision** — and configuring a real support contact is the single change
that serves both.

**⚠️ Two record corrections, both §N.8b:**
1. **`DO-G7-2` was FALSE and is RETRACTED in place** — one of the walk's own register entries. A guest **can**
   start a message thread: `guest.messages.start` (`routes/web.php:370` → `InboxService::startThread()`) is
   rendered as an **always-visible** "New message" form (`guest/messages.blade.php:37`). The claim was reached by
   looking for the affordance where it was expected instead of against what the page renders. **A finding is not
   safer for being ours.**
2. **NEW METHOD LESSON — "no route" is not "no function."** `GUEST-RECON.md` §4 established *"no live route"* by
   route-list and grep, and that was **true** — but for two of the three, the function had been **relocated and
   RATIFIED in `DIVERGENCE-REGISTER.md`**, which neither the recon nor this pass's first read consulted. It
   **changed a verdict** (Documents BUILD → DEFER) and reframed another (Help: a plain BUILD → *reopening a
   ratified refusal*). **Before calling a routeless prototype a gap, check whether the function already lives
   somewhere else and was ratified there** — otherwise a verdict pass re-litigates a settled decision blind.

**Nothing was built, deferred or deleted unilaterally.** `git diff` is `docs/*` only.

---

## 2026-08-12 — **`DO-INV-4`: the email-contrast sweep — CLOSED** (20 templates + 2 auth screens; contrast only)

**A scoped mechanical sweep, not a gate** — no §N.4k (an email has no form), no browser parity, and the only
guard added is the regression test. Full record: **§EMAIL-SWEEP** in `docs/design/MISSING-BACKEND-BY-PAGE.md`.

**332 text runs measured across 20 rendered templates. 120 failed. After the sweep: 10.**

⚠️ **Two premise corrections, both material:**
1. **The set is 20 email templates and 20 Mailables, not "15".** Fifteen is the number that `@extends` the
   shared layout — real, but not the sweep's surface.
2. **There are THREE offending greys, not two.** The register's *"now SCOPED to the same two greys"* note had
   narrowed `DO-INV-4` **past its own founding literal**: the item was raised at the invite-email gate against
   **`#A99E95`** in `emails/layout.blade.php` (2.24 on the wash, carrying the *"why you're receiving this"* line
   on every template that extends it). Closing the item on two of three would have closed it without doing the
   thing it was raised for.

**The mapping, re-measured in the email context** (the PDF numbers are on white paper; the email card is
`#FFFDFB` and the wash `#F6EBE2`, and the card is **worse** than paper, not better — the measurement decided,
not the precedent): `#8A8079` 3.19–3.80 → **`#5F5853`** 5.78–6.88 · `#A69A92` 2.27–2.70 → **`#6C645F`**
4.79–5.71 · `#A99E95` 2.17–2.58 → **`#6C645F`** 4.94 on the wash it lands on. Both replacements clear 4.5 on all
six plates the email set uses. Old values **grepped to zero**; the single residue is `owner-invite`'s Blade
**docblock**, which does not render — re-verified at 22 text runs / 0 failures, before and after.

⚠️ **§N.8f's best case in this repo so far:** `#A99E95` was **one declaration** in the shared layout carrying two
footer lines on **15 emails at once**. One line fixed, fifteen emails corrected. The other two greys repeat
inline (emails cannot use a stylesheet or a custom property) and were fixed per-occurrence.

**The instrument.** `getComputedStyle` is the wrong tool for an email and has already **false-PASSED one** — at
the invite-email gate it defaulted to white and read white-on-orange as **1.00**. So: render every template's
real HTML (with a stub that forces conditional branches into view), ancestor-walk the **declared** inline
`color` / `background-color` / `bgcolor`, **flag gradients rather than average them**, and anchor the helper —
here against the PDF sweep's own published white-paper figures, reproduced to 2dp (`#8A8079` 3.86, `#5F5853`
6.98, `#A69A92` 2.74, `#6C645F` 5.79). That cross-check **verifies the PDF-sweep record's claim** (§N.8b) and
validates this pass's arithmetic in one step.

**The two `auth/login-approval*` screens** (also named in `DO-INV-4`) needed the **screen** instrument, because
the card is translucent over a gradient: **pixel-sampled**, the true plate is `#FEFAF6`, anchors 14.82 / 2.12,
`.hint` **3.71 → 6.72**. ⚠️ The second screen's `--muted` was a **DEAD declaration** — declared in `:root`,
never referenced. It rendered nothing, so it neither passed nor failed; retargeting it is recorded as a **trap
removal, not a fix.**

**No content changed — proven, not asserted.** Every template was re-rendered and diffed against its pre-sweep
render with hex literals normalised: **19/19 byte-identical**, the only delta across the whole rendered set
being `{#8A8079, #A69A92, #A99E95}` out and `{#5F5853, #6C645F}` in. No copy, subject, link, figure, date, or
mailable/send-path logic was touched.

**Guarded and mutation-proven.** `tests/Feature/EmailContrastSweepTest.php` (4 tests / 34 assertions) anchors the
helper, asserts both replacements clear AA on all six real plates, refuses the three greys in any template, and
keeps the layout email-safe (literal hex, never a `var(--vy-*)` a client would strip). Reverting one template
turns it **red naming the file and the grey**; `php -l` clean on both passes, so the red came from the guard and
not from a broken file (§N.13.2).

⚠️ **CLOSED IS NOT "THE EMAILS ARE AA-CLEAN"** — three findings logged, deliberately not fixed:
- **`DO-EMAIL-1`** — 10 residual failing runs across 7 literals outside `DO-INV-4`'s named greys: `#8A7A6A`
  (**this absorbs `DO-G4-2`**, which the register carried as *unmeasured* — now **3.72** on `#FBF1E9`),
  `#B26A08`, `#9A8C7F`, `#9C9089`, `#E8A317` (the review-request ★ at 2.14 — a **colour-only** signal, the
  DO10-4 greyscale family in an email), `#64748B`, and `#E8EDF6` on a **gradient** plate that must be
  pixel-sampled rather than computed. Six of the seven are one-line changes.
- **`DO-EMAIL-2`** ⚠️ **the sharper find: the grey drift reaches a THIRD surface class.** The pre-AA literals are
  still live on operator/owner **screens** in files the design-parity walk recorded as gated —
  `livewire/operator/owners.blade.php` (~12 live text declarations), `operator-settings.blade.php:441`
  (`#A99E95` on a *"Powered by Vyrobel"* line — the same literal, the same footer role, a different surface),
  `maintenance-ticket-detail.blade.php`, plus two decorative status dots. The §N.8b shape: a realm recorded as
  design-closed still shipping a palette two sweeps have now retired.
- **`DO-EMAIL-3`** — `admin-email-change` is the only **off-brand** email (dark slate/indigo vs the warm
  system). A design decision to confirm, not a defect to fix silently.

⚠️ **No dishonest display (§N.8d) surfaced while measuring** — recorded with its limit: this pass read **colour**,
not claims, and must not be cited as a copy audit.

Suite: 1892 tests / 12,341 assertions, **9 non-green — the documented baseline set** (`BookingImportTest` ×6,
`OwnerUnitDetailViewTest`, `StubViewsTest` ×2), proven pre-existing by stashing. PHPStan L5 (0); Pint clean.

---

## 2026-08-12 — **`DO-EMAIL-1`: the six remaining email literals — CLOSED** (the ★ excluded → `DO10-4`)

The `DO-INV-4` continuation, same instrument and same contrast-only discipline. **10 failing text runs → 1.**
Full record: **§EMAIL-SWEEP-2** in `docs/design/MISSING-BACKEND-BY-PAGE.md`.

Each literal was re-measured on **its own plate** before being touched. All six failed, so all six were mapped:

| literal | measured | → | after |
|---|---|---|---|
| `#8A7A6A` | 3.72 on `#FBF1E9` | `#5F5853` | 6.27 |
| `#B26A08` | 3.80 on `#FBF1E9` | `#7E5811` | 5.73 |
| `#9A8C7F` | 3.22 / 2.78 | `#5F5853` | 6.88 / 5.95 |
| `#9C9089` | 3.06 on `#FFFDFB` | `#5F5853` | 6.88 |
| `#64748B` | 3.62 on `#111B2E` | `#94A3B8` | 6.71 |
| the CTA gradient | white on `#7E89F7` = **3.08** *(pixel-sampled)* | stops → `#5B5FE3`,`#4338CA` | **5.19** *(re-sampled)* |

**Three were not simple darkenings, and each is the interesting part:**
- **`#B26A08` is the raw `--vy-amber` FILL being used as TEXT.** The guest realm already solved this
  (`--vy-amber-text`); an email cannot read a token, so the **value** was inlined. ⚠️ That surfaced
  **`DO-EMAIL-4`**: `--vy-amber-text` is **defined twice with different values** — guest `rgb(126,88,17)` vs
  owner `rgb(128,76,5)`. One concept, two definitions (§N.8f). The guest value was used; which is canonical is
  a design call.
- **`#64748B` had to go LIGHTER.** It is light-on-**dark**, so darkening — the reflex on a failing colour —
  would have made it worse. `#94A3B8` was already the body copy in that same email.
- **The CTA is a gradient, so it was PIXEL-SAMPLED, never computed.** WCAG takes the worst case: the *lightest*
  pixel under the white glyphs. ⚠️ **The gradient is preserved** — unlike the invite email, which had to drop to
  a solid; darkening the stops was enough here.

**`DO-G4-2` is CLOSED as a by-product** — `#8A7A6A` is that item, carried as *"unmeasured"* since G4. (It was
retargeted to `#5F5853` rather than an exact-hue `#5F5449`, measured 6.62: the sub-line sits beside `#5F5853`
text in the same email, so unification beat a bespoke tone. **Palette discipline, not contrast.**)

⚠️ **`#E8A317` — the review-request ★ — is deliberately EXCLUDED and must stay so.** At 2.14 it looks like a
contrast defect, but it is a **colour-only rating signal**: darkening the hex improves the number without fixing
anything, because filled and empty still read alike in greyscale. Its home is **`DO10-4`** (glyph-differentiated,
floor-don't-round), which is correctness (WCAG 1.4.1). **It is the one remaining failing run in the email set.**

⚠️ **Two premise corrections, and one of them was my own instrument:**
1. **`#E8EDF6` was never a defect.** The register recorded it failing at 2.54 on a gradient. The button declares
   `color:#fff`, and the probe's hex regex matched only **6-digit** hex — so the run inherited its parent's
   `#E8EDF6`. It renders on the dark card at **14.65** and always passed. The probe **named the wrong colour
   while pointing at the right pixel**; corrected in place, and it now reads 3-digit hex and named colours.
2. **The `DO-INV-4` figures SURVIVE.** Re-measuring with the corrected probe read **321/118** against the
   published **332/120** — which looked like a correction and was not: an auth *screen* render had overwritten
   the same-named *email* render in my before-snapshot. Re-rendered from a clean pre-sweep worktree (`5d59ac4`)
   it is **332/120 exactly**. ⚠️ **A number that moves is not automatically a correction — establish which
   reading is broken before rewriting the record.**

**Greyscale-safe** (every mapping is luminance-only within its own hue family). **Old values grepped to zero**
across `emails/*`; the sole residue is `owner-invite`'s Blade docblock, which does not render. **No content
changed — proven**: re-rendered and diffed hex-normalised, **20/20 byte-identical**, the only delta being the
literal swaps.

**Guarded**: `EmailContrastSweepTest` 4 → **6 tests / 55 assertions**, including a **non-vacuity** assertion
(white on the OLD gradient stop must still measure < 4.5). **Mutation-proven twice** — reverting the amber and
reverting the gradient each turn it red **naming the file and the literal**; `php -l` clean on every pass.

Suite 1894 / 12,362, **9 non-green — the documented baseline set** (`BookingImportTest` ×6,
`OwnerUnitDetailViewTest`, `StubViewsTest` ×2). PHPStan L5 (0); Pint clean.

---

## 2026-08-12 — **`DO-EMAIL-2`: the three greys on the operator/owner WEB screens — CLOSED**

The third surface class, and the most instructive of the three grey sweeps. Full record: **§WEB-GREY-SWEEP** in
`docs/design/MISSING-BACKEND-BY-PAGE.md`.

⚠️ **THE HEADLINE FINDING IS NOT THE CONTRAST — IT IS WHY THE GATES MISSED IT.** The brief assumed these
survived because the operator (42/42) and owner (32/32) gates predated the matured contrast method. **The
measurement says otherwise, and the true reason is more useful:** all eleven `owners.blade.php` occurrences sit
inside **`@can('users.manage')`** — a permission the **demo Hotel Manager the gates were driven as does not
hold** — and inside an Alpine `x-show="open"` modal on top of that. Verified live: as `demo-staff` the markup
is **absent from the DOM entirely**. Measuring required a **role swap** (to `demo-admin`, a `Property Owner` —
one of only three roles holding that permission) **and then** a forced Alpine state.

> **It was a COVERAGE gap, not an instrument gap. No probe, however mature, can measure markup a permission
> check never emitted. A contrast pass is bounded by the ROLE it runs as.** Put this beside G10 — *a
> conditional style is invisible until forced*: **so is a conditional SURFACE, and the condition can be
> authorization.**

**Premise correction: 28 occurrences across 9 files, not the 13 the register named.** The `rgb()` grep — which
a hex-only grep would have missed entirely — turned up two sites nobody had named: `--vy-hk-ooo` in the
operator layout, and **the body copy of the whole `billing/feature-locked` 403 page**.

**Root cause: TOKEN DRIFT, which is why the fix is a token and not another literal.** The three literals *are*
the pre-AA values of the two text tokens (`#8A8079` = the old `--vy-muted`; `#A69A92` = the old `--vy-faint`;
`#A99E95` a near-neighbour of it). Hardcoding them froze those runs at the pre-remediation palette while the
tokens moved underneath. On web a token is available — unlike email — so every TEXT occurrence now reads
`var(--vy-muted)` / `var(--vy-faint)` and will follow the token if it is ever retuned again.

**Measured on the real render — pixel-sampled, two anchors, never `getComputedStyle`:** invite modal plate
**`#F8F4EF`** (anchors 14.06 / 2.01) — `#A69A92` **2.50 → 5.29**, `#8A8079` **3.52 → 6.38**; Settings
*"Powered by Vyrobel"* on `#F6EBE2` — `#A99E95` **2.23 → 4.94**; the 403 page on `#FCF7F2` —
`rgb(138,128,121)` **3.62 → 6.56**. ⚠️ **The modal plate is precisely why a pixel sample was mandatory**: it is
glass composited over a blurred scrim, a value no declared computation could produce — the email sweep's
declared-hex walk would have been wrong here. **Nothing passed, so nothing was left.**

**Deliberately not changed, each classified:** two status **dots** and three decorative input-adornment **SVG
strokes** (non-text, each paired with a real label — the strokes took the literal `#6C645F` because a `var()`
an engine does not support makes the icon **render nothing**, a worse trade for zero contrast gain);
**`--vy-hk-ooo`**, a status *fill* token sharing the value (recolouring an indicator is a design decision →
`DO-EMAIL-7`); and four comment lines documenting this very remediation — ⚠️ **two of them CSS comments, which
ship** — so the guard matches a **colour declaration**, never a bare substring, and cannot fail on its own
documentation.

**New items logged rather than quietly swept:**
- **`DO-EMAIL-5`** — a **FOURTH grey**: `.vy-inv-lbl { color: #9C9089 }`, every form label in that modal,
  **2.84** on the same plate. One line, same CSS block, outside the named three. ⚠️ It was found *the moment
  the pass looked past its own scope* — which is the argument for a full operator/owner contrast re-audit as a
  backend-phase item.
- **`DO-EMAIL-6`** — **standalone pages cannot reach the design tokens.** `billing/feature-locked` and
  `auth/login-approval*` carry their own `<style>` with no `--vy-*` in scope, so both hold mapped literals and
  will not follow a future retune.
- **`DO-EMAIL-7`** — the `--vy-hk-ooo` fill.
- **`DO-EMAIL-4` was checked here and does NOT apply** — neither `--vy-amber-text` value appears on these
  screens (grep-confirmed). It stays open, untouched.

**Gated guarantees re-verified — 0 regression.** No tenant isolation (#38/#42), no RBAC, no data, no Livewire
logic, no component state was touched. The isolation/RBAC/plan-gating set for these exact screens:
**712 tests, 710 pass, 2 errors = the documented date-coupled baseline pair.** Guard
`tests/Feature/OperatorOwnerGreyDriftTest.php` (3 tests / 27 assertions, incl. a non-vacuity check),
**mutation-proven** — reverting `.vy-inv-help` turns it red naming the file and the colour; `php -l` clean both
ways. Suite **1897 / 12,389**, 9 non-green = the documented set. PHPStan L5 (0); Pint clean.

---

## 2026-08-12 — **`DO-EMAIL-3`: the off-brand platform-admin email — CLOSED** (a branding fix on a SECURITY email)

⚠️ **This one inverts the three contrast sweeps.** There: contrast only, content proven byte-identical. **Here
the wrapper changes and the content is inviolable** — `admin-email-change` notifies of a login-email change,
the classic account-takeover move, so every sentence the recipient acts on had to survive verbatim. Full
record: **§ADMIN-EMAIL-BRAND** in `docs/design/MISSING-BACKEND-BY-PAGE.md`.

⚠️ **Premise correction — `@extends` is not the brand test.** `DO-INV-4` called this the only off-brand email;
**the claim survives, but the reasoning would have been wrong**: **five** templates are standalone
(`admin-email-change`, `login-approval`, `owner-invite`, `owner-report`, `password-reset`) and **four of them
are on-brand**. This one was off-brand by **palette** — a dark slate/indigo document with no brand lockup and
no footer, against nineteen warm Vyrobel cards.

**The argument for the fix was the sibling, not taste.** `owner-email-change` does the **identical job in the
owner realm and was already on-brand**, extending the shared layout. This template now follows it exactly.

**The content is unchanged — proven.** Rendered text extracted before and after: every security sentence
verbatim (the platform-admin framing · the new address · *"your email stays the same until you do"* · *"The
link expires in one hour."* · *"If you didn't request this…"*), **identical href sets** (exactly one — the
signed verify URL), and the only text deltas are wrapper strings (title, preheader, the "Vyrobel" lockup, the
powered-by line, the footer service line). The warning still sits **under the CTA, inside the card** — asserted
by position and **mutation-proven non-vacuous**.

**The footer was overridden deliberately.** The layout's default line is *"You're receiving this because it
relates to your booking with us."* — a platform admin has no booking, so inheriting it would have shipped a
**false statement on a security email**. The template fills the layout's own footer-override slot with an
accurate line instead.

**The security claims were verified against the engine, and they are REAL:** the one-hour expiry is
`URL::temporarySignedRoute(..., now()->addHour())`; *"your email stays the same until you do"* holds because
the new address is parked in `pending_email` and `email` is only written on verify. *"Nothing changes without
opening the link"* is accurate as written (the `pending_email` row is stored, but the login email — what the
sentence is about — does not change). **No false claim** — recorded as plainly as a failure would have been.

⚠️ **But a false line WAS found next door → `DO-EMAIL-9` (§N.8d).** The sibling `owner-email-change` — an
owner's **login-email** change — renders *"You're receiving this because it relates to your booking with us."*
Out of scope here, logged, and worth checking across every account/security email that extends the layout.

**Contrast re-verified after the branding:** 12 runs, **0 FAIL**, inheriting the layout's already-AA greys; the
whole set is 335 runs / 1 FAIL (still only the ★, `DO10-4`). ⚠️ **This supersedes part of `DO-EMAIL-1`** — that
pass darkened this email's indigo CTA gradient; the gradient is now gone in favour of the layout's bulletproof
solid `#C2410C` (white = 5.18, the ratified invite-email decision, and more email-safe than a gradient). The
sweep test's gradient assertion is therefore **structurally vacuous for that file** (O32 — resolve, don't leave
it), kept only as a do-not-reintroduce guard while the real invariant moved into the new test.

⚠️ **A NEW INSTRUMENT TRAP — the Blade analogue of `php -l` (extends §N.13.2).** After restoring the third
mutation the guard stayed **RED while the source was verifiably correct**: a **stale compiled Blade view**.
`php artisan view:clear` turned it green. A false RED from the toolchain, not from the guard — the same family
as the PDF sweep's bash-corrupted file. **Run `php artisan view:clear` after every Blade mutation edit.**

Guard `tests/Feature/AdminEmailChangeBrandingTest.php` (4 tests / 24 assertions), mutation-proven three ways.
Suite **1901 / 12,413**, 9 non-green = the documented baseline set. PHPStan L5 (0); Pint clean.

---

## 2026-08-12 — **`DO10-4`: the star capstone — CLOSED** (a correctness fix; web + email + a coverage sweep)

A star row can state the **wrong rating** two ways, and the product had shipped both. This retires the O26
defect family. Full record: **§STAR-CAPSTONE** in `docs/design/MISSING-BACKEND-BY-PAGE.md`.

**The web remnant.** `owner/unit-detail:338` used `str_repeat('★', round($average))`. Rendered live **as the
owner** (DO-EMAIL-2's lesson: run as the role that can see the page) with a real unit **forced to a 4.67
average** (G10), the old code prints **five filled stars**. It now renders through `<x-star-rating>`: **four
filled + one empty**, named *"4.7 out of 5 stars"*. ⚠️ **That changes what the owner sees, and that is the
point** — the unit stops advertising itself as perfect. An honest rating, not a regression. The forced data
was restored immediately.
⚠️ **Premise correction:** the register called that line colour-only *and* round. It was **not** colour-only —
it already emitted `★` then `☆`. The defect was `round()` alone, plus a missing accessible name.

⚠️ **The sweep found a third defect nobody had named: the shared component's OWN defaults failed 3:1.**
`#E8A63A` measured **2.08** and `#B9A99A` **2.25** — *the component everything was being migrated TO drew a
star you could barely see.* That answers **`DO-G8-2`**, which had sat as "never measured".

⚠️ **And the measurement corrected me mid-pass.** The first retarget was chosen against the two card plates;
the live render then showed the row sits on the **peach hero plate `#FBD3BF`**, where the empty star still
failed at 2.76. Re-chosen against the **worst real plate**: filled `#8F5A06` (4.19 / 5.28 / 5.70), empty
`#7A6E64` (3.59 / 4.52 / 4.88), filled always stronger than empty, hues preserved so greyscale is unaffected.

⚠️ **The email star was not what the register said it was.** `DO-EMAIL-1` routed `#E8A317` here as "a
colour-only rating signal". `review-request` does not display a rating — it renders **five tappable rating
INPUTS**, each deep-linking the review page with that rating pre-selected. **★/☆ would have been wrong**: it
would show a rating the guest has not given. The real defects were (a) contrast — 2.14, and a meaningful
non-text **control** needs 3:1 → the same `#8F5A06` (4.17); and (b) **the accessible name** — each link was
named by its bare glyph "★", so a screen reader heard *"star, link"* five times with nothing to choose
between. Each now names its rating, verified on the real mailable. **The email set is 335 runs / 0 FAIL** —
the ★ was the last failing run in it.

⚠️ **The coverage sweep earned its place** (the DO-EMAIL-2 pattern — the adjacent thing nobody named): four
further sites. `operator/guest-profile:132` at **1.98** and unnamed — an operator page no star pass had ever
rendered. `owner/reviews:123` at **1.93 / 1.48**, unnamed. Two rows whose colours already passed (3.04, 6.49)
— **not "fixed"**, only named, because the measurement decides. And `NewReviewNotification` built the filled
half only, printing *"New review · ★★★"* — ambiguous between 3/5 and 3/3 — while its sibling
`LowRatingNotification` already built both; matched to the sibling. One site, `operator/reviews:70`, was a
**false flag by my own probe**: its stars are correctly `aria-hidden` beside a visible `4/5`.

⚠️ **The guard was vacuous at first (O32).** Reverting the component's filled colour left the contrast test
**green**, because it asserted hardcoded literals — arithmetic, not the component. Rewritten to read the inks
the component **actually renders**; the mutation then went red naming the value and the plate.
`StarRatingCorrectnessTest` (6 tests / 35 assertions) is **mutation-proven five ways**: `floor`→`round`,
`☆`→`★`, drop the name, revert each colour. `php -l` **and `php artisan view:clear`** after every Blade
mutation (DO-EMAIL-3's toolchain false-RED).

**Left deliberately:** `DO-STAR-1` — the owner Messages *"Starred"* flag SVGs still use `#E8A63A`; that is a
bookmark toggle, not a rating, and its state is also carried by a visible count. `DO-EMAIL-4` (the
twice-defined `--vy-amber-text`) flagged again, not fixed.

`OwnerUnitDetailViewTest` remains non-green for its documented reason — a seeding `UnitUnavailableException`
(date-coupled), **unrelated to the star**; its other 12 tests pass. Suite **1907 / 12,448**, 9 non-green = the
documented baseline set. PHPStan L5 (0); Pint clean.

---

## 2026-08-12 — **SAVE v9: the CLEANUP CHAPTER is closed** (a memory-consolidation pass — docs only)

Five passes ran after the design-parity walk closed at SAVE v8 (`e869d5d`): **`DO-INV-4`** (`e994dc6`) →
**`DO-EMAIL-1`** (`b940cda`) → **`DO-EMAIL-2`** (`d517187`) → **`DO-EMAIL-3`** (`ac502bf`) → **`DO10-4`**
(`ee6be74`). Each wrote its own §-record and dated entry; this SAVE adds only what emerged **across** them, plus
the register and RESUME reconciliation. **No product code, template or test was touched.**

**The canon is `PROJECT-STATE.md` §N.14**, nine syntheses, headlined by the one that changes how the next phase
is planned:

> ⚠️ **A CONTRAST/RENDER PASS IS BOUNDED BY THE ROLE IT RUNS AS.** No probe can measure markup a permission
> check never emitted. Eleven greys sat behind `@can('users.manage')` and were **absent from the DOM** as the
> demo operator the gates were driven as. This extends G10: *a conditional SURFACE is invisible too, and the
> condition can be AUTHORIZATION.* **Coverage is "force every state across every role that can reach it".**

The other eight: a **certified component can have un-measured defaults** (`<x-star-rating>`'s own colours
failed 3:1 — *the thing everything was migrated to drew a star you could barely see*) · **INPUT vs DISPLAY
changes the fix** (applying the display pattern to the email's five rating *inputs* would have introduced a
phantom rating) · the **toolchain false-RED pair** (`php -l` **and** `view:clear`) plus **a guard vacuous
against its own subject** (the star test asserted literals repeated from the source) · **a number that moves is
not automatically a correction** (a scratchpad name collision, not a stale figure) · **a colour has several
grep-forms** (13 named → 28 real; the `rgb()` form hid a whole 403 page) · **a re-scoping note can amputate an
item's founding scope** (`DO-INV-4` had been narrowed past `#A99E95`, the literal it was raised for) · **a
shared layout's defaults are content and can be FALSE** · **token-drift means map to the TOKEN**, with three
measured exceptions.

**State consolidated:** the grey-literal drift is CLOSED across PDFs (17 templates), emails (20 templates, all
now on-brand, **335 runs / 0 FAIL**) and the operator/owner web screens the pass could reach (28 occurrences /
9 files, mapped to tokens); the **O26 star defect family is RETIRED**. ⚠️ **"Closed" means closed everywhere
this pass could REACH** — §N.14.1 is explicit that the operator/owner realms were swept only as far as the
roles used allowed, and `DO-EMAIL-5` is the honest successor.

**Register reconciled** with two index blocks: ✅ *closed by the cleanup sequence* (7) and ⬅ *the cleanup's open
successors, grouped by kind* (7). ⚠️ **The totals are not a burn-down** — every pass opened items it could only
see once it started.

**RESUME rewritten for the BACKEND PHASE**, priority order: ⚠️ **① `DO-EMAIL-9`** — a **live §N.8d**: a security
email telling an owner their **login-email change** *"relates to your booking with us"*, with **unknown scope**
(it is the shared layout's default, inherited by every account/security template) and a **reword-vs-restructure
call to surface** · ② `DO-EMAIL-4` (the twice-defined `--vy-amber-text`) · ③ `DO-EMAIL-5`/`DO-EMAIL-6` (the
role-aware re-audit; the standalone pages) · ④ the small tail (`DO-EMAIL-7`/`8`, `DO-STAR-1` — ⚠️ a bookmark,
not a rating) · ⑤ the backend phase proper (the full register · the 6 reopened operator a11y gates · the founder
decisions incl. G13's three verdicts). ⛔ The billing/pricing go-live blocker (§G.1) remains open, untouched by
both chapters.

**Stale claims corrected in place** (§N.8b applied to our own record): the certified-engines list still
described `<x-star-rating>` as having a live remnant *and* carried its pre-fix defaults; §N.13.8, the §G-8
bullet and two runway items still read as though `DO-G8-1` were open — each now closed with a pointer, and each
annotated where "all that remains of DO10-4" turned out to be wrong.

**Recommendation: start the backend phase in a FRESH chat.** The cleanup chapter's canon is durable in §N.14
and the register; a new session should open on the rewritten RESUME rather than inherit this one's context.

---

## 2026-08-12 — **BUILT: the guest Help centre** (`/guest/help`) — a pure FAQ over the certified engine

A build, not a parity gate. It takes G13's `DO-G13-1` verdict, on which the founder ruled: **build the FAQ
centre; the support half is a separate backend; keep it a PURE FAQ.** Full record: **§GUEST-FAQ** in
`docs/design/MISSING-BACKEND-BY-PAGE.md`.

⚠️ **The 2026-08-07 refusal is REVERSED, and annotated in place** in `DIVERGENCE-REGISTER.md`. It refused a help
**centre** because *"its content would have to be invented"* — sound when written, **false now**:
`HelpKnowledgeBase` is curated, real, screen-linked content read by the owner Help page and the grounded
assistant. ⚠️ **The other half of that refusal still stands and was honoured in full** — the page invents
nothing and offers no support affordance.

**Reused, not forked (§N.8f):** `HelpKnowledgeBase::guestArticles()` — 10 entries / 5 categories added to the
same engine, same contract plus a `category`. The view holds **no help content**; it renders the engine, so the
FAQ and the source cannot drift the way two copies of a claim always do. A test asserts both sets keep the
shape, and that guest entries link only `guest.*` routes while owner entries link only `owner.*`.

⚠️ **Every answer was verified against the code before it was written.** The door-code answer **interpolates
`ArrivalPack::LEAD_DAYS`** rather than typing a number — `DO-G4-1` had already caught a docblock claiming "48h"
against a constant that says **2 days**, and a FAQ is exactly where that drift would strand a guest at a door.
Also verified: online check-in is not the operator's authoritative check-in · Manage creates pending requests ·
payment is hosted Stripe Checkout with no stored card · the wallet cannot overdraw · a review needs a completed
stay · deletion is a reversible 14-day schedule after which anonymised financial records remain.
**Omitted rather than guessed:** a cancellation-refund window — it is per-property (`DO-G12-1`), so the entry
points at the booking's own Manage page instead of a number that would be wrong for some guests.

⚠️ **PURE FAQ.** No phone, WhatsApp, hours, reply time or 24/7 line — **and no slot, placeholder or commented
seam for them.** None has a backend, and promising support that will not answer is the class of false claim
`DO-EMAIL-9` records. The ONE contact is the existing relay.

**Verified as the GUEST (N.14.1), both a11y axes at birth.** Keyboard: 28 controls, **0 unnamed** under strict
name computation; a real Tab confirmed `:focus-visible` fires and every custom control has a matching outline
rule. Contrast: pixel-sampled with two anchors across the populated, **forced open-accordion** and no-results
states — **0 failing runs**; the chips measured separately on their own plates (4.67 / 6.52).

⚠️ **N.14.2 earned its place within minutes:** the page reused `.vy-sr-only` — **which the guest shell does not
define.** An undefined visually-hidden class hides nothing and would have rendered the search label as stray
text. Defined locally; every other reused class and token checked to exist first.

**Guarded** by `GuestHelpCentreTest` (7 tests / 165 assertions), **mutation-proven four ways**. ⚠️ **Two
mutations passed at first and both were faults in the guards** (O32): one edit **had not landed** (caught by
grepping the anchor), and the relay assertion was **vacuous** — `assertSee(route('guest.messages'))` stayed
green with the button repointed, because the shell's nav links Messages on every guest page. Now scoped to the
relay card. The negative support assertion also fired a **false positive** first: it scanned raw HTML and hit
"PHONE" in a CSS comment in the shell — a *breakpoint* heading, and a reminder that **CSS comments ship**. It
now asserts on the visible text.

**No schema** — the knowledge set is curated code, not a table. Suite **1914 / 12,613**, 9 non-green = the
documented baseline set. PHPStan L5 (0); Pint clean.

---

## 2026-08-12 — **SAVE v10: the FAQ centre is BUILT, the support backend is SCOPED** (docs only · a session handoff)

A full memory-consolidation pass. **No product code, template or test was touched.** Its purpose is a
**session handoff**: after this, a fresh Claude Code session orients entirely from `PROJECT-STATE.md`'s RESUME,
§N.13–§N.15 and the DO-register.

**Folded in: the first BUILD since the walk.** `GET /guest/help` (`23bf335`) — a guest FAQ centre over the
certified `HelpKnowledgeBase`, reused not forked, with the real relay as its only contact. **Canon: §N.15.**

**Three things the build added to the canon:**
1. ⚠️ **Verify-every-promise now covers AUTHORING.** Writing a FAQ makes fresh claims about the product before
   any test exists to catch them, so **verify before writing — it costs a grep; verifying after costs a gate.**
   And a new rule: **when the true answer VARIES, ROUTE to the authoritative screen rather than pick a number**
   (the per-property cancellation window points at the booking's Manage page; the door-code answer interpolates
   `ArrivalPack::LEAD_DAYS` rather than typing the "48h" that `DO-G4-1` caught as stale).
2. ⚠️ **Three new guard traps, all caught, none of them bugs in the feature:** a **vacuous assertion can hide in
   a shared nav** (`assertSee(route('guest.messages'))` stayed green with the button repointed, because the
   shell links Messages on every page — scope link assertions to the specific element); **N.14.2 extends from
   components to reused CSS classes** (`.vy-sr-only` was undefined in the guest shell, and an undefined
   visually-hidden class hides nothing); and **an absence assertion must read VISIBLE TEXT** — the no-support
   guard false-positived on "PHONE" in a CSS comment, because **CSS comments ship**.
3. ⚠️ **Building to the matured canon at birth held, and cost little** — rendered as the guest, both a11y axes,
   0 unnamed controls, two-anchor contrast across three states including a forced-open accordion.

**The Guest-Help verdict was SPLIT by founder ruling, and the record says so precisely:** the FAQ half is
**BUILT** (reversing the 2026-08-07 refusal, whose premise the engine falsified); the **support half is SCOPED,
not refused** — so that refusal is now **conditional, not permanent**. ⚠️ Both "the refusal was reversed" and
"the refusal stands" are true of different halves; §N.15.5 exists so a future session reading either sentence
alone does not get it wrong.

**The support backend is SCOPED as a spec** (§N.15.6) so the next session builds against it rather than
re-deciding it: 3 new per-property fields → migration → operator-settings UI → a guest support block; a stored
phone/WhatsApp, a **soft operator-set reply NORM** ("usually within…", never a guarantee — the `DO-EMAIL-9`
class), and **real per-property hours** (no "24/7" unless the stored hours say so). ⚠️ **The honesty principle
is baked into the data shape: every field displays only when populated — empty means the affordance is ABSENT.**

**Register updated:** a 🏗 *BUILT since the cleanup* block (`DO-G13-1` split; the support backend as a ready
spec) and an explicit ⚖️ *awaiting a founder ruling* row for `DO-G13-2` (Documents → DEFER) and `DO-G13-3`
(Referral → DON'T-BUILD) — recommendations, not decisions, exactly as the Help verdict was before it was split.

**RESUME rewritten as the session-handoff surface**, with the priority order stated plainly and the billing
go-live blocker (§G.1) called out as outranking everything if launch is near.

**Whole-record coherence checked, §N.8b applied INWARD — three stale self-claims found and closed:** §N.14.10's
state table still carried the pre-build suite figure; `G13-VERDICTS.md` still asserted *"there is no guest help
route"*; and the G13 entry in this log still read as a pending recommendation. Each annotated in place rather
than rewritten, so the reasoning that was true when written stays legible.

**Verified:** DO-numbers contiguous · the engines list now carries `HelpKnowledgeBase` incl. `guestArticles()` ·
`DO-EMAIL-9` still flagged live · the support spec present in both the canon and the register · the suite figure
consistent (1914 / 12,613, 9 non-green = the documented set) · the billing blocker still flagged.

---

## 2026-08-12 — **BUILT: the guest SUPPORT BACKEND** — `DO-G13-1` is now closed in BOTH halves

The other half of the Guest-Help verdict. The founder ruled **build the backend first** — not don't-build, and
not ship-the-UI-over-nothing — and this is that backend plus the two surfaces it makes honest. Three layers, one
commit. Full record: **§SUPPORT-BACKEND** in `docs/design/MISSING-BACKEND-BY-PAGE.md`; canon in §N.15.6.

**SCHEMA.** `2026_09_22_000000_add_property_support_contact_columns` adds `support_phone` · `support_reply_norm`
· `support_hours` to `properties` — **all nullable, none with a default**. Reversible, proven by an actual
rollback and re-migrate rather than by reading `down()`. Per-property, so the tenant perimeter is
`properties.organization_id` unchanged; **no new scope was invented**.

⚠️ **NULLABLE IS THE HONESTY MECHANISM, not a convenience.** NULL is not "unset yet", it is the product state
*this property offers no such channel*, and the guest surface draws the affordance only when the column is
populated. A seeded default would ship an operational promise no operator ever made — a number that rings
nobody, an hour by which nobody replies — which is the `DO-EMAIL-9` class in the one place a guest will act on
it. Nothing is seeded, including in the demo org.

**OPERATOR UI.** A **Guest support** section on `/app/settings` → `POST /app/settings/support`
(`can:properties.manage`, the same gate as the rest of the per-property settings) → `SettingsActionController`
→ the certified `SettingsService::updateSupport`, which audits by FIELD NAME and never by value.
⚠️ **An emptied box is written back as NULL, deliberately** — clearing a phone is *withdrawing a channel*, and a
writer that "only sends what changed" would leave the guest page advertising a number the operator just took
down. Mutation-proven.

**GUEST UI.** A conditional block under "Still need help?" on `/guest/help`, **one card per property the guest
has actually booked**, each carrying the property's name — a support number is a promise made by a specific
front desk, and two desks must never be merged into one number. The properties resolve through `ResolvesGuest`
and the guest's own `lead_guest_id` relation, so a guest cannot see the support details of a property they have
never stayed at; cancelled and no-show stays are excluded. The relay stays unconditional, because it is real for
every property by construction.

**THE SHARED DERIVATION.** `App\Support\Guest\PropertySupport` is read by BOTH surfaces, so the sentence an
operator picks is byte-identical to the sentence a guest reads (§N.8f — two copies of a claim about how fast
someone replies is exactly the mock-value shape this repo keeps paying for).

⚠️ **THE REPLY NORM CANNOT BECOME A GUARANTEE, STRUCTURALLY.** The column stores a KEY into a fixed catalogue;
the wording lives in code and every phrase begins *"usually"*. The operator picks a bucket, never types a
sentence, and `Rule::in(array_keys(REPLY_NORMS))` refuses a hand-posted *"guaranteed within 1 hour"*. That is
§N.8d closed the way §N.10b prefers — a refusal that *cannot fail*, rather than a helptext asking nicely.
Nothing is measured either: the norm is what the operator says they aim for, never a statistic re-presented as a
commitment. And no "24/7" is hardcoded, defaulted or suggested — hours are free text, so if a property is
round-the-clock it is because someone typed that.

⚠️ **PREMISE CORRECTION — `DO-SUP-1`: PHONE, NOT WHATSAPP.** The scoped spec said "a stored phone / WhatsApp per
property". One number is stored and it renders as a **telephone (`tel:`) only**. Nothing in the product verifies
that a given number answers on WhatsApp, so drawing that affordance would assert a channel that may not exist
(#39) — the same shape as the reply-time promise this whole build exists to avoid. A real WhatsApp affordance
needs a second column the operator ticks deliberately, or a verification step. Logged, not smuggled in.

**GUARDED** by `GuestPropertySupportTest` (10 tests / 89 assertions), **mutation-proven seven ways** — the phone
row's gate, the card-level null return, a catalogue phrase turned into a guarantee, the guest read widened to
every property, the `Rule::in` dropped, the writer skipping nulls, and the writer taking a request-supplied
property id. Each red on its own guard, green on restore, with `php -l` and `view:clear` after every edit.

⚠️ **ONE MUTATION PASSED, AND IT WAS A FINDING (O32, redundancy form).** Removing `PropertySupport::for()`'s null
return left every row-level assertion **green** — because each row is separately gated — while the page would
have rendered an **empty, property-named card**: a placeholder by another name, which is exactly what the spec
forbids. Redundancy hid it. The guard now asserts that no support markup exists at all in the unconfigured
state, and the mutation goes red. *A passing mutation is a finding, not a formality.*

⚠️ **AND THE BROWSER CAUGHT A DEFECT THE TEST COULD NOT.** The catalogue first stored bare intervals, so the page
read **"We usually within a few hours."** — broken English that `toContain('We usually')` was perfectly happy
with. Rendering it as the guest is what found it. The phrases are now complete predicates and the guard asserts
the whole composed sentence per entry. *A prefix assertion tests a prefix.*

**VERIFIED AS BOTH ROLES (N.14.1).** As a `Property Owner` (the role holding `properties.manage`): the section
renders, the select offers only the four soft norms plus "Don't show a reply time", and two real saves through
the form wrote hours-only and then phone + norm, audited by field name. As the guest: unconfigured → **0 support
cards, 0 `tel:` links**; hours-only → exactly one row; fully populated → three rows under the property's name.
**Contrast** pixel-sampled on the real render with the anchor reading **5.18** (the published
white-on-solid-accent figure, so the instrument is sound), animations frozen by *completing* them, 0 transparent
elements before capture: labels **6.73**, values **14.83**, the dial link **5.92** — **0 failing runs**, and
greyscale-safe because every row is distinguished by its label rather than by colour. **Keyboard:** 39
focusables, **0 unnamed** under a strict accessible-name computation; the dial link is reached by a **real Tab**
(not `el.focus()`, which does not set `:focus-visible`), genuinely matches `:focus-visible`, and is named *"Call
Demo Seaside Property on +351 289 000 114"*.

⚠️ **A PRE-EXISTING DEFECT FOUND WHILE VERIFYING, NOT INTRODUCED HERE: `migrate:fresh --seed` currently ABORTS.**
`DemoSeeder` dies on a `UnitUnavailableException` — a date-coupled collision between demo stays. **Proven
pre-existing by stashing this build and re-running on a clean tree, where it fails identically** (§N.14.5's
discipline, and the reason to own a clean baseline). It leaves the demo org half-seeded: one property instead of
two, and the demo staff user with **no roles**, so `/app` is unreachable until they are provisioned by hand. The
migration added here applies and rolls back cleanly against it. **Logged, not fixed — it is nobody's regression
and it is not this build's scope, but it blocks any session that needs a demo database.**


---

## 2026-08-12 — **FIXED: `DO-SEED-1` — the demo seed's date rot, at the root** (+ two G13 ratifications)

`migrate:fresh --seed` had stopped completing. Raised while verifying the support backend and **proven
pre-existing** there by stashing and re-running on a clean tree. Full record: **§DO-SEED-1** in
`docs/design/MISSING-BACKEND-BY-PAGE.md`.

⚠️ **THE ROOT CAUSE, WALKED TO RATHER THAN INFERRED.** The seeder mixed **two date systems**: a today-RELATIVE
forward window (the arriving-tomorrow stay, the terminal pair, the whole analytics spread) and an **ABSOLUTE**
calendar (the showcase stays on `2026-09-xx`, five "past" statement months `2026-04`…`2026-08`, an October
accrual, the reviews, the expenses, the agreement dates). They were disjoint **on the day they were written** —
and `seedOwnerStatementHistory` carried a comment saying exactly that: *"clear of the (future-dated)
analytics-history window, so no collision"*. Time walked the relative window into the absolute one and the
comment became false. The precise collision, from the stack: the "past" **August** statement stay on **14–18
Aug**, apartment **A1**, against the analytics entry at **today+3 = 15 Aug** on the same apartment. Analytics
runs first and *tolerates* collisions; the statement history is **strict**, so it aborted the whole seed.

⚠️ **AND THE CODEBASE HAD ALREADY MET THIS EXACT FAILURE ONCE.** `seedOwnerBalanceAccrual` carries a
*"ROBUST TO DATE-DRIFT"* forward-scan added for the identical reason. **The rot was known and defended in one
place out of two** — the other had a comment instead of a guard. *A comment is not a defence; it is a claim that
goes stale (§N.8b) exactly where the guard was missing.*

**THE FIX IS AN ANCHOR, NOT A BUMPED DATE.** Three helpers now derive every formerly-absolute date:
`thisMonth()`, `showcaseMonth()/showcaseDay()` (the NEXT full month — so the showcase stays are upcoming on any
run day, which is what keeps the **#33** withheld-door-code demo honest) and **`completedMonth(n)`** (a month
that has already ENDED). The statement history reads the last of these, which makes its collision
**structurally impossible**: a finished month is strictly before today, and every colliding booking is dated
today-or-later. Also re-anchored: the waitlist, the agreement `effective_from` (now provably before the oldest
attributed night), the pending amendment, the accrual month, the example tax rate, the owner expenses (including
the **pre-agreement** row whose whole purpose is to fall before `effective_from`), and the review anchors —
which held absolute July/May dates while the availability they depend on was already published relative to today.
One shared `bookAptStayNear()` scans forward for a free window for the two stays created *after* the tolerant
analytics pass, and **throws rather than skips**: a demo that quietly loses half a statement's net is worse than
one that fails loudly. It replaced the accrual's private copy of the same idiom (§N.8f).

**WHAT THE SEED NOW PRODUCES, VERIFIED:** **2 properties** (was 1) · the demo staff user **with its `Hotel
Manager` role** (was roleless) · 41 reservations · **6 owner statements** — five `paid` in completed months, one
`finalized` for the showcase month. `/app` renders as `demo-staff` **straight from a clean seed with no
hand-provisioning**; the manual role assignment the support-backend build needed is gone. ⚠️ Not to be
mis-read: `demo-staff` is a Hotel Manager and by design does **not** hold `properties.manage`, so `/app/settings`
still needs a `Property Owner` — the role catalogue working, not a seed defect.

**GUARDED** by `DemoSeedDateRobustnessTest` (7 tests / 25 assertions): the end state, the **structural** property
(every `paid` statement covers a month that has ended; the `finalized` one is next month), a
no-hardcoded-calendar-date grep with comments stripped, and **the seed run at four time-travelled dates** — a
month end, a year boundary, a leap day and 2031.

⚠️ **THE TIME-TRAVEL CASE IS WHAT MAKES THIS A ROOT FIX RATHER THAN A PATCH.** A test asserting only "the seed
passes today" would have been green every single day before this broke. **Mutation-proven:** restoring the
original `'2026-08-01'` literal turns **6 of 7 red** — and the seventh, the 2031 date, legitimately still
passes, because a literal that far in the past no longer collides. *One date would not have caught it.*

⚠️ **AND THE STANDING CLAIM ITSELF WAS THE LESSON.** Every dated entry in this log saying *"`migrate:fresh
--seed` clean"* was true on its date. *"The seed is clean"* is a statement about **a day**; only a date-robust
seed plus a time-travelling guard makes it a statement about the code. The RESUME now says so.

⚠️ **HONEST NOTE ON THE FIGURES.** On this run the showcase statement's net is **€885.60** — the figure the
record has always carried. It is **not structurally pinned**: a drifting analytics stay can fall inside the
showcase month on some run days. Every figure stays REAL and reconciling (#17), so the guard asserts the
statement **shape**, never the constant.

### Ratified the same day — the G13 tail

- **`DO-G13-2` · Guest Documents → DEFER.** Ratified as recommended. The rollup already lives on
  `/guest/payments` (a ratified location divergence); building a second hub would reopen it.
- **`DO-G13-3` · Guest Referral → DON'T-BUILD (#39).** Ratified as recommended. Five subsystems are absent,
  including a **new guest-creation path** — a security-posture change, not a page.

⚠️ Both are now **decisions, not recommendations**, and neither is a gap. With `DO-G13-1` built in both halves,
**the whole G13 set is closed.**

### Recorded, not acted on — `DO-EMAIL-9`

The founder leans **RESTRUCTURE, not reword**: change the shared email layout's default service line and audit
which templates then need their own, rather than the per-template override `DO-EMAIL-3` used. Noted in the
register against `DO-EMAIL-9` so the next session builds against it. **Nothing was changed here.**

**BASELINE — and it MOVED, downward.** Suite **1931 / 12,745**, **7 non-green** (`BookingImportTest` ×6 ·
`OwnerUnitDetailViewTest` ×1). It was **9**: the two `StubViewsTest` failures were `UnitUnavailableException::
occupied` — **the same collision this entry fixes** — and are now green, verified in isolation (3/3) rather than
inferred from the total. PHPStan L5 (0); Pint clean.

⚠️ **TWO SELF-INFLICTED FALSE REDS ON THE WAY, both worth recording** (they extend §N.13.2's false-RED family
with a fifth and sixth source — *the environment*, not the file or the cache):
1. **A CONCURRENT TEST DATABASE.** Running the new seed guard while the full suite was still running produced
   six `SQLSTATE[40001]` **deadlocks** on the `permissions` table. Nothing was wrong with the guard; two
   processes were provisioning roles into one `vyrobel_test`. **Run the guard alone** — it is 7/7.
2. **`MAIL_MAILER=log` ON THE SUITE.** Set out of habit (Mailpit is not running locally), it turned
   `MailTransportSafetyTest` red — a test whose entire job is to assert the suite runs on a non-sending
   transport. **The override broke the very guard that exists to catch it.** The baseline above is a clean run
   with no overrides and nothing else touching the database.

