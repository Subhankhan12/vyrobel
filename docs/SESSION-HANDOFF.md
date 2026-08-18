# Vyrobel — SESSION HANDOFF / MASTER STATE (2026-08-18)

> ## ⚠️ 0 · RECONCILIATION NOTE — read this before trusting §5 or §7
> This file was written from an architect-side handoff and then **reconciled against the actual repo**.
> Four things had drifted; each is corrected in place below and listed here so the drift itself is visible.
>
> | # | Drift | Correction |
> |---|---|---|
> | **1** | ⚠️⚠️ §5 cited `docs/audits/operator-portal-DEEP-parity-2026-08-18.md` as if it were on `main`. **It is not.** | **The operator audit reports were never merged.** They exist only on the unmerged branch **`audit/operator-deep-parity` (`57ea036`)** — both the 3,063-line evidence log and `docs/audits/OPERATOR-AUDIT-FINDINGS-COMPLETE.md`. Only `integrate/operator-fixes` (the **fixes**) reached main. ⛔ **So main's operator seal cites evidence main does not carry.** Owner and guest reports *are* on main. |
> | **2** | §5 said the guest audit covered "30 pages" | **30/30 owed wireframes** = **23 pages browser-diffed + 6 money artifacts + 1 searched state**. "30 pages" overstates the page count. |
> | **3** | §3 listed `journal.txt` as a memory location | **Not in the repo** — it lives in the architect's outputs directory, outside version control. Do not look for it here. |
> | **4** | §7 named only two branches | **12 local branches exist**, most already merged and prunable — listed in §7. |
>
> Everything else verified true against the repo: owner **28 pages + 5 artifacts, 0 backend gaps**; operator
> **52 pages**; guest **0 backend gaps**; §G.1 **€49/€99 VAT-inclusive**; `DEPLOYMENT.md` §7.1/§7.2/§7.3 all
> marked **BUILT**; `app/Support/ErrorRealm.php` and `ops/deploy.sh` both present.

## 1 · What Vyrobel is
Independent multi-tenant hotel / apartment / short-stay property-management system. Solo founder: **Subhan** (Zürich; also runs Meridian, CareOS, RestioX, HRMZY). Stack: **Laravel · MySQL 8 · Livewire + Blade + Tailwind + Alpine · Redis + Horizon · ULID · spatie teams RBAC · shared-DB multi-tenancy via `organization_id` · warm-glass `--vy-*` design across four shells (guest, owner, operator, public).**

## 2 · The working method (load-bearing — this is HOW the work happens)
An **architect** Claude session writes ONE dense, self-contained `.md` prompt per task → **Subhan runs it in a SEPARATE Claude Code session** (that session has the repo + browser/Playwright MCP) → pastes the report back → the architect **confirms it in depth** (names specific wins; owns its OWN prompt/framing errors directly, without self-abasement) → writes the next prompt. ⚠️ The architect does NOT have the repo — it cannot run git or tests; those run in the Code session. Genuine product/business decisions are surfaced via `ask_user_input`, never picked by the architect; each decision-bearing prompt is LABELLED with the ruling it assumes.
**House-style prompt shape:** orient block → THE #1..#N RULES → THE FLOW → a dense hyphenated "Standing rules" run-on → a final prose paragraph → a STOP marker. **Every build/fix prompt embeds:** CONFIRM-FIRST → RENDER/verify (§N.21.6) → reuse-don't-rebuild (§N.8f) → mutation-VERIFIED-to-apply (the filter matches exactly one test AND the mutation HITS THE PATH the test exercises) → read-the-source → guard → don't-over-reach → baseline (STASH from a CLEAN tree, N.14.5; 4242) → PHPStan L5 + Pint → commit trailer **Claude Opus 4.5** → REPORT → STOP.

## 3 · Where memory lives (the durable docs)
`docs/PROJECT-STATE.md` (§N canon + §G.1 + the RESUME block) · `docs/DECISIONS.md` (rulings + corrections; APPEND-ONLY — supersede with a new entry) · `docs/DEPLOYMENT.md` (§7.1/7.2/7.3 DONE; §7.4–7.6 REMAINING) · `docs/design/DIVERGENCE-REGISTER.md` (CURRENT-STATE doc — supersede stale lines IN PLACE) · `docs/design/raw/*.html` (wireframes, served on `:8099`) · `docs/audits/` (the deep-parity + fix + merge + money-path reports — ⚠️ **operator's are NOT here, see §0**) · ~~`journal.txt`~~ ⚠️ **corrected: not in the repo — architect's outputs dir only**.

## 4 · Core canon (the reusable lessons — the real value of this work)
- **A finding is ONE reader's HYPOTHESIS, not the mechanism** — confirm against code, either direction. (Operator Findings 6 & 7 both inverted on confirmation; the owner tax finding was false; §G.1 was mis-mapped.)
- **THE PROBE RULE:** a negative is evidence ONLY after the probe is shown capable of a positive. (The anonymous-leak probe; guest isolation; a dead server reads like a refusal.)
- **The vacuous-mutation / mutation-missed-the-path trap:** a mutation that "reds" proves nothing if the filter matched 0 tests — AND the mutation must HIT THE PATH the test exercises (Pass-1's `taxApplies()` mutation stayed green because `estimate()` never called it). A green under mutation means the mutation MISSED at least as often as the test is weak — check which.
- **Green tests are blind to UI reality — RENDER.** A redirect is not a render (a `/guest/…` 200 was a login redirect). A 200 validation re-render is not a booking. Read the DOM the way the view emits it (aria-label for icon controls).
- **Confirm the MECHANISM, not just the symptom.** Scope observations to the REGION not the page (shell chrome collides with page-wide reads).
- **A summary is a claim — re-derive it, never trust it because you wrote it.** (Summaries decayed 6× in the operator audit.)
- **The demo money HASH is NOT an invariant** (date-dependent: `DemoSeeder` builds the rate window from `today()->addDays(180)`; row hashes include ULIDs). Verify money by SUMS AND COUNTS: **929 rate rows / 10,912,000 minor / 0 overrides / 41 reservations / 16 payments / 0 seasons** (date-dependent — re-derive). Persisted quotes 0.
- **A LIMIT OF THE INSTRUMENT IS NOT A GAP IN THE BUILD.** A read-only probe structurally incapable of a positive gives a negative that is evidence about the INSTRUMENT, not the system. When a scoped finding ("without-mutation", "on seeded data") is promoted into canon, the scope is the part most likely lost — and the part carrying all the truth. (This is why `book()` was wrongly recorded as unverified.)
- **The needle-reads-itself trap:** a grep/safety-check can match its own banner/comment (caught 3×+, incl. the deploy safety grep matching its own warning).
- **Gating a control means gating the SURFACE it opens, not just the button.** A control gated by a component flag (`@if ($canManage)`) is NOT "ungated" for lacking a `@can` literal.
- **A notification's audience is bounded by who can OPEN its `url`.**
- **§N.8f — reuse the PROVEN path, don't rebuild.** A fresh mysqldump is an unproven backup wearing a proven name; use `ops/backup.sh` (the path `restore-drill.sh` proves). Don't auto-run the irreversible (deploy DB-restore is human-gated).
- **D4-1 (§N.28):** ONE resolver, ONE price source — rates MATERIALIZE at authoring time via `RateAndRestrictionService`, never resolve at read time.
- **§28 PII perimeter:** contact PII on the Guests CRM only; the anonymous storefront leaks nothing; a guest sees only their own booking/folio/documents.
- **§G.1 is the SaaS SUBSCRIPTION billing model (operator tiers €49/€99 VAT-inclusive) — NOT guest room pricing.** The storefront honesty invariant is **#17: the grand total reconciles to the folio.**
- **The storefront is ONE POST:** `booking.reserve` (POST checkout) calls `quote()` AND `book()` in one request. Do NOT drive `booking.book` (a JSON API that needs a `payment_token` the storefront never sends). Money-proof shape: `folio_items = reservation_units.price_minor = booking_quotes.total_minor`.
- `->distinct()->count()` returns 0 in this codebase — use `pluck`+`unique` in PHP (§N.27.7).

## 5 · The completed arc (all three realms sealed + money-verified)
- **OWNER** — deep parity audit (`docs/audits/owner-portal-DEEP-parity-2026-08-17.md`, **28 pages + 5 artifacts, 0 backend gaps** — verified on main). Five fixes, all merged, SEALED.
- **OPERATOR** — deep parity audit (**52 pages, 7 findings**). Three fix branches merged + sealed (`4569f0f`). Permanent guards: `OperatorLivewireMutationBoundaryTest` (pins 0/37 components mutate), `OperatorFinanceAudienceGateAgreementTest` (finance-notification recipients == who-can-open `app.owners`). ⚠️⚠️ **CORRECTED: the audit REPORTS are not on main** — `docs/audits/operator-portal-DEEP-parity-2026-08-18.md` and `docs/audits/OPERATOR-AUDIT-FINDINGS-COMPLETE.md` live only on **`audit/operator-deep-parity` (`57ea036`)**, unmerged. See §0.
- **GUEST** — deep parity audit (`docs/audits/guest-portal-DEEP-parity-2026-08-18.md` + `GUEST-AUDIT-FINDINGS-COMPLETE.md`; **30/30 owed wireframes = 23 pages browser-diffed + 6 money artifacts + 1 searched state**; findings F1–F5 + A1/A2; perimeter clean; 0 backend gaps). Fixes: F2 (card enumerates cleaning·VAT·city-tax from the estimate's own lines) · F5 + A1 (error 403 body + footer realm-branched through one `app/Support/ErrorRealm.php` derivation) · F3 (ratified two VAT bases — rooms exclusive, add-ons inclusive — + an "excl. VAT" cue on the nightly, gated on `StayEstimator::taxApplies` so an inclusive property shows none). **Money-path proof:** the full-tax-stack storefront chain reconciles (#17 through `booking.reserve`, €502.80 = 36000 + 8280 + 1500 + 4500, read from the rendered card), F4 closed (0 → 1 quote row), A2 closed (one derivation; the mutation now bites). **SEALED + MONEY-VERIFIED.** ⚠️ Correction recorded: `book()` was NEVER unverified — the audit's "UNVERIFIED-WITHOUT-MUTATION" + F4 were read-only-instrument limits on seeded data, carried into the seal as system gaps in error; the money path was already proven by `StorefrontUnitChargeTest` + `SeasonalRateTierTest`.
- **DEPLOY §7.3** — `ops/deploy.sh` written (server pull-in-place + maintenance mode — NOT containers/atomic-symlinks). Backup via `ops/backup.sh` (the proven-restorable path). Load-bearing orderings: migrate BEFORE caches, `horizon:terminate` AFTER code placed, cron verified BEFORE `up`. Installs the `schedule:run` cron (the 14-day GDPR erasure `guests:process-deletions` + 30-day ID purge `guest-documents:purge-expired` run only via it — no cron = silent non-execution). Asymmetric rollback (code auto-reverts; DB-restore is human-gated). Pre-flight refuses on missing `BACKUP_PASSPHRASE` / `APP_DEBUG=true`+prod / empty `APP_KEY` / unreachable DB. Mailpit documented in bring-up (seeded envs need a catchable mail transport or the seeder dies). No secrets (reads `.env`).

## 6 · Key rulings (headlines; full text in DECISIONS.md)
- §G.1 clarified: subscription billing, not guest pricing (storefront invariant is #17).
- F3: leave rooms VAT-exclusive + a cue; the migrate-to-gross path (gross rates + `taxes.inclusive=true` across 929 rows) is PARKED — only if EU consumer-display compliance later requires inclusive room display.
- The demo money hash retired as an invariant — verify by sums and counts.
- `book()` correction (§4 above) recorded; the "money-path proof remains" caveat retired; guest surface recorded MONEY-VERIFIED.

## 7 · main / branches / commits (⚠️ RECONCILED against the actual repo)
- **origin/main:** `388c8cf` — *"docs(DECISIONS): guest surface MONEY-VERIFIED — retire the money-path caveat"*.
- **local main:** `d13a6e6` — *"ops(deploy §7.3): the deploy script"* — **1 ahead, NOT pushed.** Awaiting Subhan's read of `ops/deploy.sh` before push (it's the artifact that will touch production, so a human read precedes the push).
- **Recent chain:** `eb49d73` → `ae052b4` → `1da7691` → `388c8cf` → `d13a6e6`.
- ⚠️ **12 local branches still exist.** Merged and prunable: `fix/guest-*` (3), `fix/operator-*` (3), `fix/owner-*` (4), `integrate/operator-fixes`, `audit/guest-deep-parity`.
  ⛔ **`audit/operator-deep-parity` (`57ea036`) must NOT be pruned — it is the only copy of the operator audit reports** (see §0).

## 8 · Remaining work (the whole horizon — none of it is a product gap)
- ⛔ **Decide on the operator audit reports** (new, from reconciliation): merge `audit/operator-deep-parity`'s two docs to main, or accept that main's operator seal cites evidence main doesn't carry. **A docs-only merge; recommended.**
- **§7.3 push** — after Subhan reads `ops/deploy.sh`.
- **§7.4 secrets** (RECOMMENDED NEXT — the deploy pre-flight depends on it): the secret inventory, the `.env` template, APP_KEY custody, the `BACKUP_PASSPHRASE` home.
- **§7.5 runtime** — with the server (pull-in-place) model, this is "the runtime the server must provide": pinned PHP `^8.4.1`, `ext-intl` (VAT derivation), `ext-zip`; possibly a dev/CI Dockerfile for parity rather than a production image.
- **§7.6 CI** — and the home for the `OperatorPortfolioTest` order-flake isolation fix (a documented known item).
- **Founder wiring (non-code, Subhan's):** offsite backup, APP_KEY custody, restore-drill, `/up` liveness monitor, `OPS_HEARTBEAT_PING_URL`, on-call.
- **Go-live switches (Subhan's flips):** Stripe live keys, VAT registration, Passport, tax rates, the VAT-validation fork.
- **Parked (NOT gaps):** F3 migrate-to-gross (only if EU compliance requires); the live Stripe capture (the key-flip).

## 9 · The architect's stance
Warm architect-to-founder. Confirms each report in depth — names specific wins, and owns its OWN prompt/framing errors directly (accountability without self-abasement; its framing has been wrong more than the codebase — §G.1, the merge topology, `book()`). HOLDS THE LINE on founder decisions — surfaces them via `ask_user_input`, does NOT pick pricing/business calls, labels each decision-bearing prompt with the ruling it assumes. ONE dense self-contained `.md` prompt per task, presented as a file. STOPs at clean boundaries. Cannot run git/tests — the Code session does.

## 10 · Immediate next
Recommend **§7.4 (secrets)** next (the deploy pre-flight depends on it), then §7.5, then §7.6; then the founder wiring + go-live switches. The §7.3 push is pending Subhan's read of `ops/deploy.sh`.
⛔ **And decide the operator-audit-reports question in §8** — it is the one item this reconciliation added.
