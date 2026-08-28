# Vyrobel — STATE

**Read this first, and finish it.** ~170 lines — mostly tables and one-line leads, so it scans in a few minutes.
It is 50× shorter than `PROJECT-STATE.md`, which is why that file gets skimmed and this one should not.
Everything else is *pointed to*, never repeated — a fact written in two files drifts, and the reader cannot tell
which copy is stale.

> **✅ VERIFIED (date)** = read against the code on that date, by the session that wrote the line.
> **📄 RECORDED** = written down by an earlier pass and **not re-checked**. Treat as a lead, not a fact.
>
> ⚠️ **This distinction exists because this project's own canon has been confidently wrong, repeatedly, and each
> time it cost a full pass**: the deploy script was said to refuse without a cron (it does not) · the owner payout
> was described as a live Stripe rail (it moves no money) · the money gate was reported FULL green from a run that
> was not full · a *derived* demo figure was recorded as a constant and a later pass "found" money missing.
> ⚠️ **Never upgrade 📄 to ✅ because a report says so. That is the same error one layer out.**

---

## 1 · What this is

A multi-tenant hotel / short-stay **PMS SaaS** for operators running a mix of hotels, aparthotels, villas and
multi-unit rentals. Three logged-in realms — operator back office (`/app`), owner portal (`/owner`), guest portal
(`/guest`) — plus a public booking storefront (`/book/{property}`), public signup, and a marketing site.

Built by one founder ("Subhan") with Claude. **`CLAUDE.md` holds the conventions and the demo credentials, and it
is loaded automatically every session — do not duplicate it here.**

## 2 · Where it actually stands

**The product is built.** What remains is not feature work; it is founder decisions, credentials, and a server.

| | |
|---|---|
| Suite | **✅ VERIFIED 2026-08-26** — 2,871 tests / 2,869 passed / 0 failed / 22,278 assertions / 2 skipped. PHPStan L5 **0**, Pint clean. |
| Demo money | **✅ VERIFIED 2026-08-26** — `ops/money-as-seeded.sh` FULL mode, all six figures unchanged; `owner_ledger` 29 rows / 730640. |
| CI | **✅ VERIFIED 2026-08-26** — 8 gates named in `.github/workflows/ci.yml`. **📄 RECORDED** that each was violated and observed red. |
| Marketing site | **✅ VERIFIED 2026-08-26** — 19 published pages. |
| Documentation site | **✅ VERIFIED 2026-08-26** — **0 of 27 pages have a view.** Nothing is published; the pages are built and checked, not served. |
| Deployment | **📄 RECORDED** complete (`DEPLOYMENT.md` §7.1–7.6). **Nothing has ever been deployed.** |

### ⚠️ Per-unit pricing survives in the design masters — and only there

✅ **VERIFIED 2026-08-28.** Nothing per-unit is externally visible: the published `/pricing` and home derive
price *and* suffix from `MarketingPricing::suffix()` (`'/ '.$period` when flat), and the four doc pages that
once said it now read *"one flat figure per account whatever your unit count"*.

⚠️ **But five masters still teach per-unit and would ship it if ever published directly:**
`core/pricing.html` (a **four-tier** ladder — Starter/Growth/Pro/Enterprise — with unit bands, over a
**two-plan** engine), `core/home.html` (including the **one** `AggregateOffer` block — search engines read
it), `core/terms.html`, `core/compare.html`, `core/alternatives.html`.
**None has a route or a view;** `routes/web.php:639` records `privacy`/`terms` as legal documents
deliberately prepared rather than published.

⚠️ **`core/terms.html` is a contract and was NOT edited.** Its clause — *"Plans are billed per unit, monthly
or annually in advance… Adding units mid-cycle is prorated"* — is false in its price basis **and its
proration rule has no meaning under flat billing**. That is a decision about what customers agree to pay,
and possibly a lawyer's; it is not a copy edit. **Whoever publishes terms must resolve it first.**

⚠️ **The prices are PLACEHOLDERS** (`config/billing.php`; `MarketingPricing::isPlaceholder()` exists to say
so). Correcting the *model* while printing a fictional *figure* would swap one false claim for another —
which is why the corrected pages state the shape and route to the pricing page instead of naming a price.

### ⚠️ Nothing takes real money, by default

- **✅ VERIFIED 2026-08-26** `PAYMENTS_GATEWAY` defaults to **`manual`** (`config/services.php:74`).
- **✅ VERIFIED 2026-08-26** `PAYOUTS_RAIL` defaults to **`manual`** (`config/services.php:110`), described in-file as
  *"done outside the system — the only working rail"*; `stripe_connect` is *"a config-gated SKELETON"*.
  ⚠️ **The owner payout rail moves no money.** Earlier notes calling it a live Stripe rail were wrong.
- **✅ VERIFIED 2026-08-26** Three guards refuse live credentials outside `production` and are bound in
  `AppServiceProvider`: `StripeSafetyGuard`, `MailSafetyGuard`, `LlmSafetyGuard`.
- **✅ VERIFIED 2026-08-26** `FakeChannexClient` is the bound default — **no channel connection is live.**
- **✅ VERIFIED 2026-08-26** `LLM_FAKE` defaults true — the support assistant makes no real Anthropic call outside production.

⚠️ **The payment path has never run against real Stripe** — the guards mean production is the first place it ever
executes for real.

## 3 · Never do these

Each is absolute, and each is here because doing it once cost something.

1. **Never run the suite beside other commands.** Parallel runs corrupt the shared test DB. This produced a false
   `BookingImportTest` failure on 2026-08-26 that looked exactly like a real regression.
2. **Never treat a report as a verification.** A verification that lives only in a chat report gets done twice, and
   the second doing may reach a different answer.
3. **Never cut a true half to green a gate.** If a register row is red for correct work, the row is wrong, not the page.
4. **Never amend a certified assertion.** A fixture's world may move; an assertion may not. Fix the fixture.
5. **Never type a number into a page.** Route to the setting or interpolate it. A typed figure is a second copy that drifts —
   and removing a false one is correct, *re-estimating* it is the same error with better arithmetic.
6. **Never promise a future date or capability**, in the product or on the site.
7. **Never soften a false claim.** A hedge makes it unfalsifiable and the reader still believes it. Correct it or cut it.
8. **Never negate a false claim by quoting it** — the false phrase stays on the page for a skimming reader.
9. **Never fabricate seed state silently.** Demo data is flagged as demo, or it is not written.

## 4 · Open — and who must decide

⚠️ **Nothing below is queued work. Each is a decision, and two of them nobody in this repository can make.**

| # | Decision | If skipped | Blocks |
|---|---|---|---|
| 1 | ⚠️ **Article 28 data-processing agreement** — **a lawyer's, not ours** | No lawful basis to process customer data | Any real customer |
| 2 | ⚠️ **Flow-of-funds / money-holding question** — **a lawyer's, not ours** | Unknown regulatory exposure | Taking real money |
| 3 | ~~The billing model~~ — **SETTLED 2026-08-28: flat, and the copy already said so.** ✅ VERIFIED: the published `/pricing` and home derive their price and their `/ mo` suffix from `MarketingPricing`, and the doc pages already read *"one flat figure per account whatever your unit count"*. ⚠️ **The 2026-08-26 entry was WRONG** — it grepped `tools/marketing/core/*.html` (the design **masters**) rather than the built output. Per-unit text survives only in unpublished masters. |  |  |
| 4 | **Stripe live keys + the dashboard steps** (`BILLING-STRIPE-PRICES.md`) | No payments | Go-live |
| 5 | **Hosting region, backup location, Postmark region** (`DATA-RESIDENCY-INVENTORY.md` §5) | Cannot answer a lawyer's first question | The DPA |
| 6 | **A `failed_jobs` retention window.** It is unbounded today and a failed queued mailable persists a recipient email — and, in one case, a whole statement PDF. Three other retention windows are fixed in code; **a fourth is a decision, not a default.** | PII kept indefinitely | GDPR posture |

⚠️ **Decision 3 is one decision, not nine edits.** Cutting per-unit from a single page (the glossary was the tempting
one) leaves eight pages still teaching it and puts the glossary in conflict with the terms page.

## 5 · How things break here — the recurring classes

⚠️ **This is the most valuable output of the work.** Each class recurred; the instances are evidence that it will again.

- **Complete machinery, no front door** — *5 instances*: an uncalled re-attribution method; company accounts with no
  screen; three restrictions with no writer; an override checked nowhere; a Connect account nobody could create.
  **Ask what route reaches it before believing a feature exists.**
- **A column's existence is not a capability** — `organizations.custom_domain` is read by **nothing** and no
  `Route::domain` exists; it nearly made a false marketing claim look true. `Plan::PRICING_PER_UNIT` is the same shape.
  **Grep for readers, not for the column.**
- **Absence absorbed as agreement** — a silently-skipped null let a gate claim five counters having checked two.
- **A check in the wrong place fails plausibly** — a forbidden-phrase check placed *before* the corrections it guards
  produced exactly the failure it was built to detect; 28 corrections looked unlanded.
- **The instrument cannot see its own blind spot** — a page read CLEARED because nothing looked at it; needles proved a
  page *rendered*, not that it rendered the *corrected* text. **A source-scanning guard also reads its own needle** —
  tokenise and skip comments.
- **A false decline** — a surface saying data is absent while it sits unread in the payload. Worse than a blank: it
  *answers* the question.
- **A false CAUGHT is undetectable** — the mutation harness can only catch a false NOT-CAUGHT.
  ⚠️ **Disbelieve an implausible green.**
- **Payment taken, booking unpaid** — *4 routes*: unrouted events; a wrong signing secret; a refund object read as a
  payment intent; a third charge path nobody switched.
- **A guard that inspects only the failing case** *(new 2026-08-26)* — the half-cut verifier checked its declaration
  only for claims still *outstanding*, but the wrong action made the claim bank as **DONE**, so the one act it existed
  to catch was the one that skipped it. **Verify unconditionally.**
- **Reading the wrong layer** *(new 2026-08-28)* — a grep over the design **masters** found per-unit billing on
  nine pages that the **built** output had already corrected, and it produced a false "founder decision" entry in
  this very file. **Grep `C.convert(key)`, never `core/*.html`.** Third instance of a wrong-layer read.
- **A truncated read is a wrong read** *(new 2026-08-26)* — 200 characters of a paragraph made an uncorrected claim look
  already-corrected. **Read the whole block before classifying it.**
- **A brief is a lead, not a fact** *(new 2026-08-26)* — ~55 consecutive briefs each carried at least one wrong specific.
  **Verify against the repo and say plainly when a brief is wrong** — though it is sometimes entirely right.

## 6 · Finished as it can be — do not re-open

- **41 unmatched audit fragments.** The audit *paraphrased* rather than quoted, so there is no fragment to match.
  Closing them means re-quoting the audit against the pages — **a new authoring job, not a correction pass.**
- **12 channels claims.** Parked by ruling until a real connect flow exists. **📄 RECORDED.**
- **3 half-cut rows.** Structurally unrepresentable by the gate's present/absent rule. **A category, not work** —
  and closing one by deleting its true half breaks its declaration and reds the gate, by design.
- **Known product limits.** Things to *tell* a customer, not fix: no guest self-serve data export (deletion **does**
  exist); fixed retention windows regardless of jurisdiction; no org-wide 2FA enforcement.

## 7 · Rulings worth knowing — with their reasons

⚠️ **A ruling without its reason gets re-opened by someone who assumes it was an oversight.**

- **Direct charges on the operator's own connected account** — chosen as the *conservative* end. Whatever a lawyer
  later says about who may hold funds, direct charges cannot be wrong on that question. **The cost is work, not risk.**
- **Standard Connect accounts, not Express** — Express puts dispute liability on the platform.
- **No statement-descriptor field** — on a Standard account with direct charges the descriptor comes from the
  connected account's own settings. A field here would claim a control we do not have.
- **The channels page is stopped** — not deferred, not "coming soon". It may make **no** future promise.
- **The changelog was refused** — there are no release tags, so any entry would be invented. Refused on principle.
- **The weaker money-gate mode is kept, not removed** — it is what a developer gets against a normally-seeded
  database. It simply may no longer *look* like the real gate.

## 8 · Where everything else lives

**Point here; do not copy.**

| Document | What it holds |
|---|---|
| `CLAUDE.md` | Conventions, stack, demo credentials. Auto-loaded every session. |
| `GO-LIVE-RUNBOOK.md` | The ordered steps to go live. **Money switch is Phase 4, last.** |
| `GO-LIVE-REQUIREMENTS.md` | 15 blocking + 7 advisable items before a real customer. |
| `DATA-RESIDENCY-INVENTORY.md` | Written **for a lawyer**: every store, sub-processor, retention window. |
| `DEPLOYMENT.md` · `DEPLOY-SCRIPT-VERIFIED-2026-08-26.md` | The runbook, and the line-by-line script verification. |
| `CONNECT-TARGET-DESIGN.md` | Why direct charges, and the account model. |
| `BILLING-STRIPE-PRICES.md` | ⛔ The founder's Stripe dashboard steps. |
| `PROJECT-STATE.md` (8.3k lines) · `DECISIONS.md` (17k lines) | The full history and the §N verification canon. **Reference works — nobody reads them start to finish.** |
| `SESSION-HANDOFF.md` | The 2026-08-22 handoff. ⚠️ **Predates the entire Connect money switch and all documentation passes.** |
| `tools/marketing/` | The doc-site corrections gate, the forbidden-phrase check, and `spec/half_cut.py` (the half-cut / verified-true / corrected-unmatchable / founder-blocked registers). |

### ⚠️ Where existing documents disagree, and which is right

1. **`PROJECT-STATE.md` says it is superseded by `SESSION-HANDOFF.md`** — and the handoff is dated **2026-08-22**,
   before the Connect money switch, the residency work and every documentation pass. ⚠️ **Both are stale as a
   *current-state* source. This file is the current one; they remain correct as history.**
2. **`PROJECT-STATE.md`'s headline suite figure (2,524)** is superseded by **✅ 2,871 (VERIFIED 2026-08-26).**
3. **`CLAUDE.md:86` describes `PROJECT-STATE.md` as holding "the full invariant list #1–37"** — ✅ VERIFIED
   2026-08-26 that the status block's own latest marker is **#1–73**. It is not a contradictory count; it is a
   **stale description of another file**, written when 37 existed. ⚠️ **The number 73 is 📄 RECORDED — no pass
   has re-derived the list.**
4. **`DATA-RESIDENCY-INVENTORY.md` said guests cannot self-serve erasure — that was wrong** and was corrected
   2026-08-26: `POST /guest/account/delete` exists, with an undo and a grace period. The **export** half stands.
