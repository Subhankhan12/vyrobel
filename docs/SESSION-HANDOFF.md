# Vyrobel — SESSION HANDOFF / MASTER STATE (SAVE v2 · 2026-08-22)

> **Read this first, then `docs/PROJECT-STATE.md` (the RESUME block, then §N), then `docs/DECISIONS.md`.**
> This file is written to be self-contained: a fresh session should be able to resume from it alone.
>
> ⚠️ **THE GIT STATE IS A POINTER, NOT AN ASSERTION** — run `git log --oneline -1 origin/main` and
> `git status -sb`. ⚠️ **This line used to state a hash, and it was stale within one commit every time**: the
> commit that updates it changes the very thing it describes, so it can never be more than one commit
> accurate (§N.32.5). The dated snapshot is in §8; the command above is the truth.

---

## ⚠️ 0 · RECONCILIATION NOTE — what THIS pass corrected

This save was written **against the repo, not against the brief that requested it**, and three claims did not
survive that. All three had been carried forward as true.

| # | The claim, as carried | What the code says | Where it had spread |
|---|---|---|---|
| 1 | ⚠️ *"`ops/deploy.sh` installs the scheduler cron **and refuses to deploy if told to skip it**."* | **The install is real** (`ops/deploy.sh:168-179`, idempotent append). **The refusal is not.** `fail()` at `:53` only prints to stderr; `set -euo pipefail` does not fire on a successful `printf`; the script's single `exit 1` is the health check at `:203`. With `DEPLOY_NO_CRON_INSTALL=1` the deploy **prints red and completes**. | Written in `DECISIONS.md` (Gate 7) → copied into `docs/legal/DATA-INVENTORY.md` → carried into the next brief. **Three surfaces, one unchecked sentence.** All corrected. |
| 2 | *"Deploy track remaining: §7.4 secrets · §7.5 runtime · §7.6 CI."* | **The deploy track is COMPLETE** — `DEPLOYMENT.md` marks §7.1–§7.6 all ✅. §7.4/§7.5 are *documented inside* `DEPLOYMENT.md`; there is **no** separate `Dockerfile` or `docs/SECRETS.md` (`Dockerfile.ci` is the CI runtime). | `PROJECT-STATE.md` RESUME. Corrected. |
| 3 | ⚠️ B12: *"The per-unit **model matches**; the rate is 50–125% out (€9/unit vs €4)."* | **The model did not match.** All four seeded plans are `pricing_type = 'flat'` with `unit_rate_minor` **NULL** — the engine charges **flat €49 / €99**. `config('billing.per_unit_minor') = 900` seeds a column **no plan uses**. | `DECISIONS.md` B12, then repeated for two gates. Annotated in place as superseded. |

⚠️ **All three are the same failure**: a statement written once, believed thereafter, and never re-read
against the code. **#1 and #3 were the architect's own framing.** That is why this pass exists, and why the
rule is *the repo wins*.

**Verified as of 2026-08-23 — ⚠️ counts, so DATED and CHECKABLE, never standing facts** (§N.32.5):

| count | as of 2026-08-23 | verify with |
|---|---|---|
| design masters | 84 (102 MB) | `ls docs/design/front/*.dc.html \| wc -l` |
| `marketing.page.*` routes | 19 | `php artisan route:list --json` |
| marketing views | 19 | `ls resources/views/marketing/*.blade.php \| wc -l` |
| CI gate steps | 8 | `grep -c "name: 'Gate" .github/workflows/ci.yml` |
| reports in `docs/audits/` | **19** | `ls docs/audits/ \| wc -l` |

⚠️ **The audits count had ALREADY DRIFTED — it said 18 and the directory held 19.** The pass that wrote "18"
went on to add a report, so the number was false before the ink dried. That is the self-referential decay of
§N.32.5 in its purest form, and it is why every row above now carries the command that settles it.

---

## 1 · What Vyrobel is

A **multi-tenant hotel / short-stay PMS**, built by a **solo founder** (Zürich). Operators run properties;
owners see their own units and money; guests self-serve their stay.

**Stack:** Laravel · MySQL 8 · Livewire + Blade + Tailwind + Alpine · Redis + Horizon · ULID primary keys ·
spatie **teams-mode** RBAC · `organization_id` tenancy on every business table · warm-glass `--vy-*` design
tokens.

⚠️ **SIX shells** — `resources/views/components/<realm>/shell.blade.php`:
**public** (storefront) · **guest** · **owner** · **operator** · **admin** · **marketing** ← *the sixth,
added this arc.*

**Money discipline:** integer minor units, append-only ledgers, correct-by-reversal, a single write path per
concept. `ops/money-as-seeded.sh` pins the demo money: **929 / 10,912,000 / 0 `unit_rate_overrides` / 41 /
16 / 0**.

⚠️⚠️ **THAT PIN IS CURRENTLY RED, FOR TWO ATTRIBUTED REASONS, AND WAS DELIBERATELY NOT RE-PINNED**
(`docs/audits/OWNER-COLLECTION-GATE-2026-08-23.md` §7): the reservation count **41 → 40** is **PRE-EXISTING
and date-driven** — the demo seeder is date-relative and reproduces 40 on baseline code, so ⚠️ **the pin is
date-fragile**; and the September statement moved because the collection gate correctly holds uncollected
revenue. ⛔ Re-pinning is a founder call and depends on the collection-risk decision.

---

## 2 · The working method — load-bearing; the new session must follow it

1. **Claude is the architect.** It writes **ONE dense, self-contained `.md` prompt per task**.
2. The **founder runs that prompt in a SEPARATE Claude Code session** (which has the repo).
3. The founder **pastes the report back**; Claude confirms it in depth and writes the next prompt.

⚠️ **Claude has NO repo access in the architect session.** Everything it believes comes from reports — which
is exactly how corrections #1 and #3 above propagated. **A build session must reconcile against the code and
say so when the brief is wrong.**

**Every build prompt embeds, in this order:**
CONFIRM-FIRST (verify the brief's premises before acting — *it cuts both ways*; items shrink as often as they
grow) → **RENDER-verify** (open it, use it, screenshot it) → **§N.8f reuse** (one definition per concept) →
**mutation-proof every guard** → baseline (**N.14.5**; suite floor **4242** assertions, now far exceeded) →
**PHPStan L5 + Pint** → trailer **`Claude Opus 4.5`** (never "Opus 5") → **REPORT → STOP**.

⚠️ **Product, business and compliance decisions are SURFACED, never picked.** Pricing copy, legal text,
service commitments, tier names — the founder rules; the build records the ruling.

---

## 3 · Where memory lives

| File | What it is |
|---|---|
| **`docs/SESSION-HANDOFF.md`** | ← this file. The resume surface. ⚠️ Re-read it against `git log`; it decays silently. |
| `docs/PROJECT-STATE.md` | The RESUME block + the **§N canon** + the invariant register. |
| `docs/DECISIONS.md` | **Append-only** dated "why" log. Supersede by *annotating*, never by rewriting. |
| `docs/DEPLOYMENT.md` | The runbook. §7.1–§7.6 = the (complete) deploy track. |
| `docs/legal/DATA-INVENTORY.md` | **142 cited entries** for counsel + `PRIVACY-STRUCTURE.md`, `TERMS-STRUCTURE.md`. |
| `docs/design/DIVERGENCE-REGISTER.md` | Per-page design fidelity + the **ratified divergences that must NOT be "fixed"**. |
| `docs/audits/` | The audit reports (⚠️ **count is dated, see §0** — `ls docs/audits/ \| wc -l`). |
| `CLAUDE.md` | Project instructions + demo credentials. Loaded every session — keep it lean. |

---

## 4 · ⚠️⚠️ THE VERIFICATION CANON — the arc's most valuable output

> **This is the part to preserve above all else.** It was spread across ~16 reports and is consolidated here
> and in `PROJECT-STATE.md` §N. **Every item carries the instance that earned it** — none is theoretical.

### 4.1 The vacuity family — *a guard that cannot fail*

⚠️ **RULE: every new guard needs its OWN vacuity check. Prove it fails when there is nothing to check.**

| Shape | The instance |
|---|---|
| A **rule shared** between test and implementation | The honesty gate re-implemented the rule in raw helpers, with a pinned fixture as arbiter, precisely to avoid this. |
| A **page-wide** assertion for a **region-specific** property | Gate 1: "no fabricated figure on the page" passed while the stats band held four. |
| A positive control **structurally incapable** of the failure | A one-segment wildcard route cannot shadow a two-segment URI, so the storefront-shadowing control proved nothing. |
| **Fixtures prove the predicate**, nothing checks the data | The role-coverage guard asserted its own fixtures. |
| A guard over **EMPTY data** early-returning | Gate 4b's no-writes guard on an empty DB. |
| **Counting requests, not work** | The same guard counted HTTP calls rather than rows written. |
| **Inert by design** → needs a synthetic positive | The cookie forward-guard: nothing to catch until a vendor exists. |
| ⚠️ **Measuring the WRONG SIDE of the boundary** | **P-6:** the cross-org guard counted rows in the *victim's* org while the write auto-filled the *attacker's*. **Pricing:** the comparison guard compared `comparison()` against the config — *the source against itself* — and a row invented **in the view** left it green. |

### 4.2 Instrument rules

- ⚠️ **A grep proves the absence of ITS NEEDLES, not of the thing.** The Gate-5 article sweep grepped `%`,
  `€` and "Vyrobel", called five articles clean — they carried **210 unsourced outcome claims** containing
  no number and no brand.
- ⚠️ **An empty result from a CRASHED tool is an ARTEFACT, not a verdict.**
- ⚠️ A **poll loop's exit code can report `grep`'s state**, not the watched thing (a "failed" background task
  that had simply matched zero lines).
- ⚠️ **VALIDATE THE INSTRUMENT AGAINST A KNOWN POSITIVE BEFORE TRUSTING ANY NEGATIVE.** The reachability
  sweep's first instrument missed `firstOrNew` and `new Model;` — two false "SEEDER-ONLY" findings avoided.
- ⚠️ **A source-scanning guard reads its own needle** (§N.27.5, now **five** instances). Newest: the pricing
  guard's HTML stripper opened its match on the literal text `<script>` that a **CSS comment in the shell was
  shipping into every page**, and swallowed the body — reporting "renders no price node" about a page that
  renders two.

### 4.3 What assertions CANNOT see

- **Usability.** A date picker rendered and could not complete a range — found by *clicking it twice*.
- ⚠️⚠️ **LAYOUT DOES NOT FAIL LOUDLY.** **A grid squeezes rather than overflows**, so `scrollWidth` looks
  perfect. **Nineteen marketing pages were non-responsive below 980px for SIX gates** — 200s, no raw Blade,
  no overflow — because the converter stripped the masters' `<style>` blocks and nothing read the
  `data-h1` / `data-sec` hooks still sitting in the markup. Found by **one screenshot at 390px**.
- **A 500.** `/app/rates` returned 500 while **the suite, PHPStan and Pint were all green** — three
  `word@directive` traps in one edit.
- **Unexercised branches** · **persistent-worker state** (a static memo that would leak across visitors).

⚠️ **RENDER-VERIFY: open it, use it, screenshot it, read the diff, read the branches.**

### 4.4 The generation lessons — **both directions**

- ⚠️ A fix applied **AFTER** generation is undone by the next generation → **fixes live IN THE CONVERTER.**
  ⚠️⚠️ **AND THE CONVERTER MUST LIVE IN GIT — it did not, for the whole arc.** `convert.py` (918 lines, every
  honesty transform) existed only in a garbage-collected `/private/tmp` scratchpad. Found intact and committed
  to `tools/marketing/` on 2026-08-23; had it cleared first, all 19 marketing pages would have been
  un-regenerable. ⚠️ Canon called it `pxbuild.py` for the entire arc — that is a 35-line driver, not the
  converter. See §N.33. ⚠️ **Made RUNNABLE 2026-08-23** (`tools/marketing/`, six path assumptions not one;
  `decode.cjs` because a `.js` file is ESM in this repo; `heads_px.py` had been missing entirely).
  ⚠️⚠️ **The regeneration proof then caught a POST-GENERATION EDIT on `/switching`** — the drop was made in
  the generated Blade, not the converter, so regenerating restores the dropped link. Reported, not
  overwritten; the guard reds on it. **⛔ Open: move that transform into `convert.py`.** §N.33.4–.5.
  ⚠️ `tools/audit/reach.py` (the reachability instrument that produced P-6, P-7 and the 12 seeder-only
  entities) is committed too.
- ⚠️⚠️ **AND THE CONVERSE: a fix living ONLY in generated output makes the generator a DESTRUCTOR.**
  `/pricing` had become **un-regenerable** — B12's binding existed nowhere in the converter, so running it
  would have silently restored four hardcoded tiers **on the cards too**.
- ⚠️ **A ZERO-MATCH transform must FAIL LOUDLY.** Gate 6's DPA removal — written explicitly so "a page built
  later cannot inherit it" — **matched none of the six DPA mentions on the three Gate-7 pages it was written
  for.** The generator now exits non-zero on a needle that matched nothing; it caught three more within
  minutes, including one no-oped by an *earlier transform in the same pipeline*.
- ⚠️ **The report proves a match, not the RIGHT match.** `_element_span()` had an off-by-length bug
  (`rfind(sub, 0, i+1)` cannot match a tag beginning at `i`) and cut the **previous sibling** while
  reporting success.

### 4.5 Claim + sweep lessons

- ⚠️⚠️ **CHROME CARRIES CLAIMS SITEWIDE** — a page-scoped ruling never reaches it. **5+ instances**, one
  already live (the nav's Alternatives row named four competitors on *every* page).
- ⚠️⚠️ **SWEEP, DON'T COUNT.** D13's ruling named two instances; a sweep found **six**. P-6's impossible
  instruction had a **sixth site 34 lines from one already fixed, in the same modal**. The pricing sweep took
  **three passes, each finding a new class** (prices → tier *names* → the SEO `title=` → billing *mechanics*
  in prose).
- ⚠️⚠️ **PARTIAL BINDING PRODUCES AUTHORITATIVE FALSEHOOD.** Home bound its *prices* and left its *names*, so
  the page rendered **the real €49 under the invented label "Starter"** — worse than either being wrong
  alone, because the number is authoritative and the label is fiction.
- ⚠️ **A banned-substring gate cannot distinguish *"we have one"* from *"we don't yet"*.** CI Gate 5 blocks
  the phrase "data processing agreement" and therefore blocks the honest correction as firmly as the false
  claim. **Left untouched; the page was reworded and the tension surfaced.**
- ⚠️ **DOCUMENTATION IS A CLAIM ABOUT BEHAVIOUR, and no data-gate can see it.** 27 doc pages, **220 false
  claims**, **zero** caught by the content ledger.
- ⚠️ **A case study is BY DEFINITION a claim about a real customer.** Three invented ones were refused and
  rebuilt as labelled *illustrative scenarios*.
- ⚠️ **An offer to introduce someone to an invented business is a representation someone can ACT on.**
- ⚠️ **A comment that outlives its subject is a claim like any other.** `Guest::anonymize()` defers abandoned
  booking quotes to *"the scheduled retention purge"* — **which does not exist.** Two stale route comments
  said pages were unregistered that had shipped two gates earlier.

### 4.6 The reachability class

- ⚠️⚠️ **THE DEMO SEEDER CREATES STATE THE APPLICATION CANNOT CREATE** — so **the demo looks complete while
  the product is not**. **12 SEEDER-ONLY entities** (⚠️ the sweep found 11; the **12th is the arrival
  secrets** — `door_code` / `wifi_*` / `arrival_notes`, where every reference in `app/` is a READ, so #33 is
  enforced on data no operator can enter — §N.32.4); the money chain stops at statement finalisation.
- ⚠️ **GATED-UNREACHABLE ≠ no path** (P-7: `property_user`'s only writer needs a row only it could create).
- ⚠️⚠️ **A SIGNPOSTED MISSING FEATURE IS WORSE THAN AN ABSENT ONE.** The app instructed *"add units, rates &
  channels to go live"* when all three were impossible — **the tenant concludes THEY are failing.**

### 4.7 Misc, and both cost real time

- ⚠️ **A CSS comment reaches the browser; a Blade comment does not.** A Gate-7 CSS comment shipped the
  literal text `<script>` onto 19 pages.
- ⚠️ **Blade compiles STATEMENTS BEFORE it strips COMMENTS** — an inline-PHP directive inside a docblock
  opens a real PHP block. It 500'd `/pricing`. A sweep found **17 directives inside comments**; one file
  documents this exact bug *in a comment that still contains the directive causing it*.
- ⚠️ **A directive whose `@` follows a word character is not compiled** (`yet@endunless`) — ships raw source.

---

## 5 · What is BUILT — verified against the repo

- **The three realms sealed and money-verified** — operator `/app`, owner `/owner`, guest `/guest`, plus the
  storefront (`/book/{property}`, `/stay/{organization}`) and `/admin`.
- **Guest notifications + email preferences.**
- **Stripe capture — built and DORMANT** (test-mode; the live-key flip is the founder's).
- ✅ **The deploy track is COMPLETE** — §7.1 backups + proven restore · §7.2 scheduler/queue liveness ·
  §7.3 `ops/deploy.sh` · §7.4 secrets (documented) · §7.5 runtime (documented) · §7.6 CI.
- **P-6 Gate 1 (`2edf171`) — a real tenant can now SELL.** Room type → unit → rate plan → rates → a completed
  storefront booking, proven end-to-end through the app's own HTTP paths with **no seeder and no factory**.
- **The front website** (below).

### 5.1 The front website

`docs/design/front/` — **84 masters, 102 MB, committed** per ruling (a). A converter turns a master into a
Blade page; honesty transforms live **in the converter**.
Built: the **marketing shell + nav + footer + SEO component**; `/` is the marketing home; routes are
`marketing.page.*` — **19 published**.

**BUILT (19):** Home · Product · Features · Modules · How It Works · **Pricing** · Switching ·
For Hotels / For Apartments / For Villas · **Try Booking** (the honest sandbox — real prices via the
read-only `priceOver()`, **nothing reserved, charged or sent**) · the four `example-scenarios/*` ·
**About · Security · Integrations · Contact** (the company pages that survived audit) · plus the cookie
notice (a POST, not a page).

**HELD / REFUSED — each a ruling, not a backlog item:**

| Page | Why |
|---|---|
| `Alternatives` / `Compare` | ⚠️ **DROPPED 2026-08-22 — the ruling EXECUTED.** 12 unsourced competitor claims, none sourceable without the founder supplying evidence. Removed from the URL map, the nav (desktop + mobile drawer), the footer and a CTA on the published `/switching` page — **7 sites; the brief named 2**. The drawer pin moved 21 → 19. ⚠️ The 12 claims are **KEPT** in `claims.json` as the audit record: the drop removes the PAGES, not the finding. |
| `Privacy` / `Terms` | ⚠️ **Structure + data inventory only, UNPUBLISHED. Legal text NOT drafted** — a plausible-looking policy is worse than none. |
| The **27 docs/resources** | ⚠️ **220 false claims** found in the Gate-5 audit. Deferred. |
| `Changelog` | **Refused.** Eight entries: three **predate the repository's first commit**, two are dated **in the future**. Zero releases have ever happened. |
| `Careers` | **Refused.** Its subject is a company that does not exist. ⚠️ Nothing is discarded by refusing it — the page has no form and no file input. |
| `Demo Confirmed` | The post-submit state of a demo booker refused with **D5**. |

### 5.2 ⚠️ The EIGHT CI gates — each was VIOLATED and observed RED, not taken on trust

| # | Gate | What it stops |
|---|---|---|
| 0 | **Runtime / parity** | The image drifting from the documented runtime contract. |
| 1 | **Pint** | Style drift. |
| 2 | **PHPStan L5** | Type/static regressions. |
| 3 | **The full Pest suite** | Everything else. |
| 4 | **Money as seeded** | Any change to the demo money surface. |
| 5 | **Content honesty** | Unreal/unconsented ledger content reaching production. |
| 6 | **Claims freshness** | A third-party claim without `source_url` + `verified_on` + `review_by`, or past review. |
| 7 | **The consent forward-guard** | Analytics/vendors shipping without reading consent. |

⚠️ Plus (inside Gate 3) the newer guards: `MarketingPriceSourceTest` (one source for every price and tier
name), `MarketingCompanyPagesTest`, `TenantCanBecomeSellableTest`.

---

## 6 · Every founder decision, and the OPEN ones

**Ruled** (reproduced from `DECISIONS.md`):

| | Ruling |
|---|---|
| **D1** | **KEEP** the illustrative figures/customers/testimonials for now — **enforced by CI** (Gate 5), and filtered out in production rendering. |
| **D2** | **`/` = the marketing home.** It displaces only the stock Laravel splash; the storefront never had `/`. |
| **D3** | **= (b)** — `/try-booking` **rebuilt** as an honest sandbox on the real demo property. |
| **D6** | **ONE palette** (§N.8f). The design's two divergent hex tones are **not adopted**. |
| **D7** | **English first.** The 27 translations deferred — **and with them the `/{locale}/…` route, which is E1: the only change capable of shadowing `/book/…`.** |
| **D9** | **Tier-1 login, zero auth change.** Tiers 2/3 declined (cross-realm enumeration; widening `/admin`). |
| **D11** | **Build the cookie dialog** — in-house, no CMP. |
| **D12** | **Keep the social icons**; real URLs before launch. |
| **D13** | **Mockup names must be distinct from the ledger.** ⚠️ The ruling named two instances; a sweep found **six**. |
| **tracking** | **(a)** — the full masters committed. |
| **case studies** | Rebuilt as **labelled illustrative scenarios** at `/example-scenarios/`. |
| **claims** | Build the ledger + freshness gate **first**, then source. |
| **B12** | **`/pricing` reads from `config/billing.php`.** ⚠️ See §0 #3 — its stated premise was wrong. |

⚠️ **OPEN — nothing may assume these:**
**D4** support widget · **D5** demo booker · **D8** photography · the **B1–B11 claim sources** ·
the **`/pricing` headline copy**.

---

## 7 · What REMAINS

### 7.1 Product

| Item | Note |
|---|---|
| ⚠️ **GDPR account deletion does not exist** | **Article 17.** No path — app, command or documented — deletes an operator, owner, staff member or organisation. `User` declares `SoftDeletes`; nothing calls it. |
| ⚠️ **The phantom retention purge** | `Guest::anonymize()` defers abandoned booking quotes to a *"scheduled retention purge"* **that does not exist**. |
| ⚠️ **Permanent stores erasure cannot reach** | `audit_events` (IP + user-agent, no prune) · `webhook_events.payload` (verbatim OTA guest name/email/phone) · `domain_events`. |
| ⚠️ **Possible Article 9 data** | Staff **leave `reason`** — free text, no retention, no delete route. |
| ✅ **② + the accrual pair — DONE 2026-08-23** | ⚠️ **The defect was MISNAMED.** Accruing on charge is CORRECT (the ledger is an accrual, keyed on `service_date`); the real defect was that nothing distinguished owed-and-**collected** from owed-and-**uncollected**. Fixed with a collection-aware payable balance (`OwnerCollection`) gating what a STATEMENT may claim — the ledger untouched. Finalisation got its first real caller (`POST /app/owners/{owner}/close-period`). The ordering is mutation-proven. ⚠️ **Two founder decisions SURFACED, not picked: who bears collection risk, and what triggers finalisation.** ⚠️ **And a restatement is surfaced, not done silently** — see `docs/audits/OWNER-COLLECTION-GATE-2026-08-23.md` §7. |
| ⚠️ **19 ungated operator screens** | **Three render money**, incl. `/app/dashboard`'s "Revenue today" tile — visible to Housekeeping. |
| **P-7** | Per-property scoping (`property_user`) is **gated-unreachable**. |
| **Channels** | **Build or remove** (decision ④). Unreachable today; `Channel::create` exists only in the seeder. |
| ⚠️ **The operator shell is desktop-fixed** | `/app` does not respond below ~980px. Pre-existing; the *marketing* shell was fixed this arc. |

### 7.2 Founder / legal

- ⛔ **The Article 28 DPA** — does not exist; a go-live blocker. The **142-entry data inventory** is ready for counsel.
- ⛔ **The sub-processor list** — does not exist. Real ones identified: **Postmark** (live), **Stripe**, **Anthropic** (inert).
- ⛔ ⚠️ **The *"never hold client funds"* claim — HELD.** Facts established, **no legal verdict rendered**: a
  plain **platform charge + a separate Transfer**, so funds would rest in Vyrobel's **own** Stripe balance.
  ⚠️ **Awaiting a payments lawyer** — and **Q10, whose Stripe account the configured secret belongs to, which
  the repo cannot answer.**
- ⛔ **Stripe live-key flip + webhook registration** · **the test-mode probe (B)/(C) still unrun.**
- ⛔ **`MAIL_MAILER` off `log`** · **VAT** · **Passport keys.**
- ⛔ **Retention periods** for ~97 categories, including the contact table this arc created.
- ⛔ ⚠️ **SEO finalisation — LAST.** The published page set must stop moving first. ⚠️ **The storefront must
  end up ALLOWED and SITEMAPPED, never disallowed.**

---

## 8 · Git state

⚠️ **EVERYTHING IN THIS SECTION IS A DATED SNAPSHOT, NOT A STANDING FACT.** ⚠️ The hash is kept because it
has diagnostic value — it tells a reader roughly where this document's OTHER claims were last true — but it
is a hypothesis to check, never something to rely on. ⚠️ **The repo is truth if it has moved.**

| as of | value | verify with |
|---|---|---|
| **2026-08-23** | `origin/main` = local = **`fa62f82`**, nothing unpushed | `git log --oneline -1 origin/main` · `git status -sb` |
| **2026-08-23** | untracked, deliberately: `docs/audits/owner-portal-parity-2026-08-17.md` | `git status --short` |
| **2026-08-23** | suite **2671 / 2669 passed / 2 skipped / 18,662 assertions** · PHPStan L5 **0** · Pint clean · money as seeded unchanged | `php artisan test` (⚠️ **alone**) · `./vendor/bin/phpstan analyse` · `./vendor/bin/pint --test` · `ops/money-as-seeded.sh` |
| **2026-08-23** | remote branches: `main` + `audit/operator-deep-parity` + `audit/guest-deep-parity` + `integrate/operator-fixes` + 4 × `fix/owner-*` | `git ls-remote --heads origin` |

⚠️ **Seven local `fix/*` and `feat/*` branches have no upstream and are NOT pushed** — every one is fully
merged into `main` (`git rev-list --count main..<branch>` = 0 for all seven), so nothing would be lost if the
machine died. They are left in place, not deleted.

---

## 9 · ⚠️ The through-line — record this honestly

**Most of this arc's commits are things CAUGHT, not built:**

220 false documentation claims · three invented customers · 12 unsourced competitor claims · a fabricated
changelog (three entries predating the first commit, two in the future) · four security properties the
product lacks · an invented four-tier pricing ladder over a two-plan engine · six sites of one impossible
instruction · **19 pages silently non-responsive for six gates** · 11 seeder-only entities · a GDPR case
deferred to a mechanism that does not exist.

⚠️⚠️ **AND THE DEMO LOOKED COMPLETE THROUGHOUT.** Every one of these shipped past a green suite.

⚠️⚠️ **THE METHOD REPEATEDLY CAUGHT THE ARCHITECT'S OWN FRAMING** — this is the engagement's defining
pattern, and it must stay visible:

- the **`€9/unit`** figure read out of a config default and amplified for two gates (§0 #3);
- the **"refuses to deploy if told to skip"** over-claim, written in one report and propagated to three
  surfaces (§0 #1);
- the **`:8000` staleness** endorsed without checking;
- **two offered pricing approaches when the right answer was a third** (neither "fix the table" nor "drop
  it", but *rebuild it at the size the truth actually is*);
- the brief for **this very save** carried #1 and #3 as fact.

⚠️ **The correct posture for the next session: treat every brief as a starting point, verify against the
code, and say plainly when the brief is wrong.** That is not friction — it is the only thing that has
reliably worked.

---

## 10 · Immediate next

1. ⛔ **Founder-side**: the DPA · the sub-processor list · the funds-flow legal question (+ Q10) · the Stripe
   live-key flip and webhook registration · `MAIL_MAILER` · the D4/D5/D8/B1–B11 rulings.
2. **Product, in defensibility order**: the **②+accrual pair** · **GDPR account deletion** · the phantom
   retention purge · the 19 ungated operator screens.
3. **Then** channels (build or remove), P-7, the operator shell's responsiveness.
4. ⚠️ **SEO finalisation LAST**, once the page set stops moving.
