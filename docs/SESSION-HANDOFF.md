# Vyrobel — SESSION HANDOFF / MASTER STATE (2026-08-21)

> **A fresh session should be able to resume from this file alone.** It is written to be self-contained:
> what the product is, how the work is done, the canon, what was built, what remains, and where the
> previous framing was wrong.
>
> ⚠️ **Every claim below was reconciled against the actual repo before writing.** Where the repo and the
> brief disagreed, the repo won and the correction is recorded in §0. A handoff that contradicts the code
> is worse than no handoff — it is a confident wrong answer.

---

## ⚠️ 0 · RECONCILIATION NOTE — what this pass corrected

| # | Claim | What the repo says |
|---|---|---|
| **1** | "6 unpushed commits" (said twice in session) | ⚠️ **Five.** `8dd2cbf · 9b6a948 · e37f417 · 2b37bf3 · 895dea5`. All now pushed; **origin/main == local == `895dea5`**. |
| **2** | `docs/audits/owner-portal-parity-2026-08-17.md` "unaccounted for" | ⚠️ **Identified.** 134 lines, the **first/shallow** owner parity audit. Main already carries the **deep** successor `owner-portal-DEEP-parity-2026-08-17.md`. It is a superseded earlier draft, not a lost artifact. Still untracked — see §8. |
| **3** | ⛔ The 2026-08-18 handoff's open item — the operator audit **reports** | ⚠️ **STILL OPEN, unchanged.** `git ls-tree main -- docs/audits` returns **no operator report at all**. Both live only on the unmerged branch **`audit/operator-deep-parity`**. ⛔ **That branch must NOT be pruned.** |
| **4** | PROJECT-STATE's canonical header: *"zero deploy artifacts"*, *"deployment REMAINS"*, *"suite 2185 / 2194, 6 non-green"* | ⚠️ **All three now stale.** Deploy artifacts exist (§7.1–§7.6 built); the last full run was **2,524 / 2,522 passed / 2 skipped / 0 failed**. Corrected in the RESUME this pass. |

| **5** | "the trackers are current" | ⚠️ **`DIVERGENCE-REGISTER.md` had gone stale** — its last entry was **G13 (2026-08-12)**, with nothing for the three guest surfaces built since, though **`Guest Email Preferences.html` is a real wireframe**. ⚠️ A parity register that stops being appended goes wrong **silently**. **Closed this pass**, with the gap itself recorded. `DECISIONS.md` and `DEPLOYMENT.md` were current. |

Verified true and unchanged: money-as-seeded `929 / 10,912,000 / 0 / 41 / 16 / 0`; guest notifications **0**;
guest email preferences **0**; §G.1 €49/€99 (`config/billing.php:65,78`, both marked **PLACEHOLDER**);
all nine cited commits present; every cited file present.

---

## 1 · What Vyrobel is

Independent **multi-tenant hotel / apartment / short-stay property-management system**. Solo founder:
**Subhan** (Zürich). Stack: **Laravel · MySQL 8 · Livewire + Blade + Tailwind + Alpine · Redis + Horizon ·
ULID · spatie teams RBAC · shared-DB multi-tenancy via `organization_id` · warm-glass `--vy-*` design across
four shells** (guest, owner, operator, public).

**Four surfaces, three sealed realms.** Owner, operator and guest are each **AUDITED · FIXED · SEALED ·
MONEY-VERIFIED**. The public storefront is the fourth surface.

---

## 2 · The working method — load-bearing, and the new session must follow it

An **architect** Claude session writes ONE dense, self-contained `.md` prompt per task → **Subhan runs it in
a SEPARATE Claude Code session** (that session has the repo + browser/Playwright MCP) → pastes the report
back → the architect **confirms it in depth** (names the specific wins; owns its own framing errors plainly,
without self-abasement) → writes the next prompt.

⚠️ **The architect does NOT have the repo.** It cannot run git or tests; those run in the Code session. It
presents prompts as files.

⚠️ **Genuine product / business / compliance decisions are SURFACED to the founder, never picked.** Each
decision-bearing prompt is labelled with the ruling it assumes.

**House prompt shape:** orient block → `THE #1..#N RULES` → `THE FLOW` → a dense hyphenated *Standing rules*
run-on → a final prose paragraph → a STOP marker. Every build prompt embeds: **CONFIRM-FIRST** →
**RENDER-verify** → **reuse-don't-rebuild (§N.8f)** → **mutation-VERIFIED** → **baseline (STASH from CLEAN,
§N.14.5; 4242)** → **PHPStan L5 + Pint** → commit trailer **Claude Opus 4.5** → **REPORT** → **STOP**.

---

## 3 · Where memory lives

| File | Role |
|---|---|
| **`docs/PROJECT-STATE.md`** | ⚠️ start here — the RESUME block, then the §N canon (§N.13–§N.30), §G.1, the A–M block |
| **`docs/DECISIONS.md`** | append-only dated "why" log — every gate, every ruling |
| **`docs/DEPLOYMENT.md`** | the runbook · **§0** pre-flight · **§7.1–§7.6** the six deploy items, all BUILT |
| **`docs/design/DIVERGENCE-REGISTER.md`** | per-page design fidelity + **40 RATIFIED divergences that must NOT be "fixed"** |
| **`docs/design/MISSING-BACKEND-BY-PAGE.md`** | the design-parity deliverable + the DO-register |
| **`docs/audits/`** | 10 audit reports on main (owner deep, guest deep, Stripe capture, probe runbook, …) |
| **this file** | the narrative master state |

---

## 4 · The canon (§N) — the reusable lessons, which are the real asset

### ⚠️⚠️ §N.29 — LABEL vs PROTECTION · **the engagement's defining lesson**

**A guard's green must depend on the protection RUNNING** — not on where it lives, and not on what it is
named. Three forms:

| Form | What it does | Why it is invisible |
|---|---|---|
| **(a) LABEL** | asserts a thing is *named* or *declared*, not that it is *true* | reads exactly like a real check |
| **(b) LOCATION** | asserts a file / gradient / controller *exists* | fails on a safe refactor; goes **silent** when the behaviour moves |
| **(c) MISSED-MUTATION** | the mutation applied but never reached the path — or the fixture resolved it by luck | the suite reds elsewhere, or greens for the wrong reason |

**The rule:** guard the **RENDERED behaviour** or a **ROUTE-TABLE fact**, never a file path. Mutation-prove
every guard **and** verify the mutation both *applied* and *hit the path deterministically*. A
zero-matching filter must be shown capable of a **positive first**.

⚠️ **Caught 13+ times across the engagement — almost none by review, nearly all by mutation.** And it
caught its own author repeatedly: the `provider_ref` phantom read, the `ext-intl` grep whose needle set
was drawn from what intl was *expected* to be for, and several verification probes that grepped a route
name against HTML or guessed a CSS class.

### §N.30 — the payments columns

⚠️ **`gateway` and `gateway_ref`.** There is **no `provider_ref` and no `method`** — reading them via
`?? null` returns **0 unfailably**, which looks exactly like a working measurement (§N.30.1).
⚠️ **"0 overrides" means `unit_rate_overrides`** — there is no `rate_calendar.override_reason` (§N.30.2).

### §N.8f — reuse the PROVEN path, don't rebuild

One definition per concept; a second computation is *how* surfaces drift. ⚠️ **Refined this engagement:
reuse is only safe once the HOST can run it** — the guest shell loads no Alpine, so the notification bell
was reused in **link mode**, not popover mode.

### The PROBE RULE — a limit of the instrument is not a gap in the build

A negative is evidence **only** after the probe is shown capable of a **positive**. Twice this engagement a
scoped observation was promoted into canon as a system gap: `book()` recorded as "unverified" (it was
always proven), and `payments.pay` recorded as "0-exercised" (true of the **demo seed**, false of the
build — five tests drive that route).

### The rest of the load-bearing set

- **A finding is a HYPOTHESIS** — confirm against code, in *either* direction. Confirm-first has made items
  smaller as often as larger.
- **§N.13.1 — the defect sits BESIDE the flagged thing.** Held for five chapters, and again this
  engagement: the `OperatorPortfolioTest` flake had already been fixed **twice** in this repo, one file over.
- **A register is one reader's finding — SWEEP** (§N.27.1). The audit named 4 password paths; there were 9.
- **money-as-seeded is SUMS AND COUNTS, never a hash** (the hash is date-dependent — `DemoSeeder` builds the
  rate window from `today()->addDays(180)`, and row hashes include ULIDs):
  **`929 rate rows / 10,912,000 minor / 0 unit_rate_overrides / 41 reservations / 16 payments / 0 seasons`**,
  plus **0 guest notifications** and **0 guest email preferences**.
- **§G.1 is the SaaS SUBSCRIPTION model** — operator tiers **€49 / €99, VAT-inclusive**, flat view carries
  no VAT line (`config/billing.php:65,78`, both **PLACEHOLDER** pending the founder's real prices).
  ⚠️ **It is NOT guest room pricing** — the storefront invariant is **#17**.
- **#17** storefront/folio reconciliation · **#28** PII + self-scoped perimeter (a guest sees only their own)
  · **#33** arrival-secrets window · **#39** never ship a control the engine cannot honour · **#86** the
  operator per-org email switch (`EmailTemplateResolver`).
- **The storefront is ONE POST** — `booking.reserve` calls `quote()` **and** `book()` in one request. Do not
  drive `booking.book` (a JSON API needing a `payment_token` the storefront never sends).
- **`->distinct()->count()` returns 0 in this codebase** — use `pluck` + `unique` in PHP (§N.27.7).
- **A source-scanning guard reads its own needle** — tokenise and skip comments (hit 3×+).
- **A check must be INDEPENDENT of what it checks** (§N.18.1) — verify a deletion with a raw query.
- **On a money path, an over-permissive check IS the bug** (§N.18.2); **the fail-safe direction is set by
  which error is SILENT** — when in doubt, charge the tax (§N.18.4).

⚠️ The **full** list lives in `docs/PROJECT-STATE.md` §N.13–§N.30. The above are the load-bearing ones.

---

## 5 · What was BUILT this engagement — each verified against the repo

| Work | Commits | Verified |
|---|---|---|
| **Guest fix pass** — pricing display · 403 realm-branching · VAT cue | merged, sealed | on main |
| **Guest money-path** — the full tax-stack storefront chain, #17 through `booking.reserve` | merged, sealed | MONEY-VERIFIED |
| **Guest notifications** — complete, sealed | `aa1a91e` → `d866837` → `f623bb8` → **`2ed497a`** | ✅ 8 concrete classes + 1 abstract base in `app/Notifications/Guest/` |
| **Guest email preferences** | **`8dd2cbf`** | ✅ `GuestEmailPreferences` + `GuestEmailPreference` + the sparse migration |
| **Stripe capture — BUILT-AND-DORMANT** | **`9b6a948`** | ✅ audit + probe runbook on main |
| **Deploy §7.4 secrets** | **`e37f417`** | ✅ inventory + `.env.example` + custody |
| **Deploy §7.5 runtime contract** | **`2b37bf3`** | ✅ §7.5 in `DEPLOYMENT.md` |
| **Deploy §7.6 CI** | **`895dea5`** | ✅ `.github/workflows/ci.yml` · `Dockerfile.ci` · `ops/runtime-contract.sh` · `ops/money-as-seeded.sh` |

### Guest notifications — the eight types
`booking-confirmed` · `checkin-ready` (#33 window) · `message-received` (personal **and** bulk) ·
`payment-due` · `booking-cancelled` (→ `guest.trips`, **not** the reservation) · `refund-processed` ·
`review-request` · `account-security` (**G8, always-on**, unreachable by any preference).

Each on a **real trigger**, emailing **once**, **self-scoped** (#28), and **GDPR-erased** — `Guest::anonymize()`
deletes the notification rows. The `/guest/notifications` feed plus the bell (header top-right; mobile row 1).
Built on Laravel-native `DatabaseNotification` + the polymorphic `notifiable` morph (`'guest' => Guest::class`
via `Relation::enforceMorphMap`) and the existing `NotificationPreferences::CATALOG`.

### Guest email preferences — a SEND-SITE gate, not an `allows()` change
⚠️ The guest transactional emails **never consult `allows()`** — that gates the in-app feed only. So the
opt-out is a new gate at each Mailable send site, mirroring `EmailTemplateResolver::enabled()`.
**Two live toggles** (`payment`, `reviews`) · `txn` + `pre-arrival` + G8 **always-on/locked** (pre-arrival
carries the door-code nudge — a guest who could switch it off could arrive unable to get in) ·
`offers` + `winback` + `push` rendered **disabled/deferred** (no engine — #39) · the marketing unsubscribe
left untouched as a **separate** control on a separate legal basis.

### Stripe capture — confirmed, not built
`guest.payments.pay` → **hosted** Checkout (SAQ A, no raw PAN) → the folio settles on the
**`payment_intent.succeeded` webhook**, never the redirect. `StripeSafetyGuard` keys on `STRIPE_FAKE` first,
then the **live key prefix** — so a `sk_test_` key **binds the real client off-production**, which is what
makes the test-mode probe possible. ⚠️ **Go-live is a key-flip; there is no capture gap.**

### The deploy track — six items, all built
**§7.1** backups + a proven restore · **§7.2** scheduler/queue liveness · **§7.3** `ops/deploy.sh`
(pull-in-place + maintenance mode, asymmetric rollback, installs the `schedule:run` cron) · **§7.4** secrets ·
**§7.5** the runtime contract · **§7.6** CI.

⚠️ **§7.6 highlights:** all four gates (**suite · PHPStan L5 · Pint · money-as-seeded**) run on every push
and PR, **each individually proven to red the build** (§N.29 applied to CI — no `|| true`, no
`continue-on-error`, no filter, no `--parallel`). The runtime is **consumed** from §7.5 via
`ops/runtime-contract.sh` (the executable form), not restated (§N.8f). ⚠️ **`Dockerfile.ci` is dev/CI-only
and was never built** — Docker is not installed on the dev machine, so **Gate 0 on the first CI run is what
verifies it**.

⚠️ **The `OperatorPortfolioTest` flake was NEVER order-dependent.** `seedScenario` codes units `U1`, and the
test asserted `assertDontSee('U1')` against a page carrying a 40-char random CSRF token — `Str::random(40)`
is alphanumeric, so it contains `U1` **~1% of the time** (measured **212 / 20,000 = 1.06%**). It was testing
the **needle**, not the behaviour, and an ordering fix would have changed nothing. Fixed with the repo's own
established remedy (a leak-proof needle), the **positive asserted first**, and the **worst case pinned** —
the negative now runs under a token deliberately containing `U1`, so a short needle reds deterministically
instead of flaking months later.

---

## 6 · What REMAINS — all founder-side · **none of it is a product gap**

### Immediate
- **The CI first run** — fires on the `895dea5` push. ⚠️ **A Gate-0 red means the parity image is missing one
  named requirement** (a `Dockerfile.ci` line), **not a code regression and not a suite failure.** Read the
  named requirement and add it.
- **Stripe test-mode probe (B) + (C)** — bracket **(A)** is done (the guard *permits* test keys
  off-production). Waits on the founder: `STRIPE_FAKE=false` + `sk_test_…`/`pk_test_…`, **`APP_ENV` stays
  `local`**, the `whsec_…` from **`stripe listen`** (not the dashboard), then pay with `4242 4242 4242 4242`
  and `4000 0000 0000 0002`. Then the architect runs **(B)** confirm and **(C)** cleanup back to 16 payments
  / 0 Stripe intents. ⚠️ **Claude never handles a real key, never runs `stripe listen`, never places a
  secret value.**

### Founder go-live wiring
- Offsite backup + a real restore drill.
- **APP_KEY custody** (and `APP_PREVIOUS_KEYS` on any rotation — without it, every encrypted guest PII field
  becomes unreadable).
- `/up` + `OPS_HEARTBEAT_PING_URL` + alerting + on-call.
- ⚠️ **The local `php.ini` fix Gate 0 found:** `upload_max_filesize` **2M → 12M**, `post_max_size`
  **8M → 14M**, `memory_limit` **128M → 256M**. `php artisan serve` is the CLI SAPI, so **the 8 MB guest ID
  upload and the 10 MB attachment path have only ever been tested below 2 MB on this machine.**

### Go-live switches
- Stripe **live** keys + `STRIPE_FAKE=false` + **`APP_ENV=production`** (load-bearing — the guard forces the
  fake otherwise) + ⚠️ **register the LIVE webhook endpoint** — *the silent-failure one*: without it the guest
  is charged and the folio **never settles**.
- `STRIPE_CONNECT_WEBHOOK_SECRET` **before payouts** (absent today; owner payout rail only, not guest capture).
- ⚠️ **`MAIL_MAILER` off `log`** — the other silent-failure default.
- VAT registration · `passport:keys` per environment · `tax:set-rate` per jurisdiction (the seeded rates are
  clearly-labelled **EXAMPLES** the founder must replace) · the **VAT-validation fork** (send-as-is, already
  implemented, vs VIES-first, a build).

### Parked — decisions/triggers, not gaps
- **F3 migrate-to-gross** — compliance-gated; reverses the ratified "leave rooms exclusive + add a cue"
  ruling. Only if EU inclusive-display is required.
- **The flagged notification candidates** — `winback` (consent-skip) · `charge-posted` and
  `checked-in/out` (noise-skip) · `review-responded` (product call) · `waitlist` (a separate
  availability-matching project).

### Housekeeping
- ⛔ **The operator audit REPORTS are still not on main** (§0 #3) — they exist only on
  **`audit/operator-deep-parity`**. Main's operator seal cites evidence main does not carry. **A docs-only
  merge; recommended.** ⛔ **Do not prune that branch.**
- **The §7.5.6 pointer** — §7.5.6 lists the runtime checks in prose while `ops/runtime-contract.sh` enforces
  them. They agree today; a one-line pointer would collapse the last §N.8f duplication.
- **`docs/audits/owner-portal-parity-2026-08-17.md`** — the superseded shallow owner audit (§0 #2). Commit as
  history or delete; founder's call.
- **12 local branches**, most merged and prunable (incl. `feat/guest-notifications-gate1`).
- **Shut `:8000`** (`php artisan serve`, mine). ⚠️ `:8347` and `:8099` are **not** the founder's and were not
  started by this work — leave them.

---

## 7 · Git state

| | |
|---|---|
| **origin/main** | **`895dea5`** — *ops(deploy §7.6): CI …* |
| **local main** | **`895dea5`** — level, nothing ahead or behind |
| **Working tree** | clean, bar the one untracked file in §8/housekeeping |
| **Chain** | `2c26ba0` → `aa1a91e` → `d866837` → `f623bb8` → `2ed497a` → `8dd2cbf` → `9b6a948` → `e37f417` → `2b37bf3` → `895dea5` |
| **Last full suite** | **2,524 tests · 2,522 passed · 2 skipped · 0 failed** · 16,075 assertions · exit 0. ⚠️ **Run it ALONE** — a parallel run corrupts the shared test DB |
| **PHPStan** | L5, **0** · **Pint** clean |

---

## 8 · ⚠️ The through-line — record this honestly

**Claude's framing of the codebase was corrected by confirm-first roughly seven-plus times**, every time
before anything was built on the wrong premise:

| # | The framing | What the code said |
|---|---|---|
| 1 | §G.1 = guest room pricing | it is the **SaaS subscription** model |
| 2 | the merge topologies | repeatedly different from assumed |
| 3 | `book()` "unverified" | ⚠️ **it was never unverified** — the probe was the limit |
| 4 | the notification system model | `DatabaseNotification` + notifiable morph + `CATALOG` — **no `audience` field routes anything** |
| 5 | "Risk 2" (the guard resolver) | **false** — `$request->user()` IS the guest inside `auth:guest` |
| 6 | the bulk message path needs wiring | **already wired** |
| 7 | reuse the bell as a popover | ⚠️ **the guest shell loads no Alpine** — reused in link mode instead |
| 8 | `payments.pay` "0-exercised" | true of the **demo seed**, false of the **build** |
| 9 | the flake is "order-dependent" | ⚠️ a **needle** defect — randomness, not ordering |
| 10 | `ext-intl` "unused" | one guarded `\Locale` call; the grep's needle set was incomplete |

⚠️ **The method is built to catch exactly this, and it did — every time, without a defect reaching a guest.**
That is the pattern worth carrying: *the architect's framing has been wrong more often than the codebase,
and confirm-first is what makes that safe.* Do not skip it because a claim sounds settled.

---

## 9 · The architect's stance

Warm architect-to-founder. Confirms each report in depth — names the specific wins, and owns its **own**
prompt and framing errors plainly (accountability without self-abasement). **HOLDS THE LINE on founder
decisions** — surfaces them, does not pick pricing or business calls, and labels each decision-bearing
prompt with the ruling it assumes. ONE dense self-contained `.md` prompt per task. STOPs at clean
boundaries. Cannot run git or tests — the Code session does.

---

## 10 · Immediate next

1. **Watch the CI first run.** A Gate-0 red = one missing `Dockerfile.ci` line, not a regression.
2. **The Stripe test-mode probe** — founder runs the runbook; the architect then runs (B) + (C).
3. ⛔ **Decide the operator-audit-reports question** — the one item carried unresolved from 2026-08-18.
4. **Founder go-live wiring + switches** — including the local `php.ini` fix and, before any real traffic,
   `MAIL_MAILER` off `log` and the LIVE webhook endpoint registered.

⚠️ **No product work remains.** Everything above is infrastructure, founder cutovers, or a decision.
