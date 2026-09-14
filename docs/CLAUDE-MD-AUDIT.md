# `CLAUDE.md` audited claim by claim — and it had been describing the pitch, not the product

**2026-09-05.** ⚠️ **The PWA line was the one that was noticed, not the only one that was wrong.** Nine
corrections; every one justified by a file, a route, a table or a command that was read or run.

---

## 0 · ⚠️⚠️ Provenance — the finding is larger than a line

`git blame` on line 3: **`^97416c7 Subhan Khan 2026-07-02`** — the **bootstrap commit**, and the line is
**byte-identical** to `git show 97416c7:CLAUDE.md`. **Eighty-six commits have touched this file since and
none has revised its opening sentence.**

⚠️ **So line 3 is the original product vision, written before the product existed, and it has been read as a
statement of fact by every session since.** That is the shape, and it explains why the errors cluster in the
oldest, most confident sentences rather than in the recent narrative.

---

## 1 · Every claim, classed

### ⚠️ FALSE — corrected or removed (7)

| # | claim | what the code says | evidence | action |
|---|---|---|---|---|
| 1 | *"ships … guest/staff **PWAs**"* | **no service worker anywhere** in `resources/` or `public/`; **no `rel="manifest"`** in any of 50+ views | file listing + view scan, run — not cited from the audit | ⚠️ **subject REMOVED** |
| 2 | *"**AI agents**"* (plural pillar) | **one** LLM assistant: `AskVyrobelService` → `AnthropicLlmClient`, reachable at `POST owner/help/ask` | `route:list`, `OwnerHelpAskController` | corrected to **"an AI assistant"** |
| 3 | *"**PHP ^8.3** (composer's floor — 8.2 will NOT install)"* | composer requires **`^8.4.1`** | `composer.json` | corrected |
| 4 | *"runs all **9** scheduled commands"* | **12** entries (10 commands + 2 heartbeats) | `php artisan schedule:list` | ⚠️ replaced with the **command**, not a new number |
| 5 | *"`ops/deploy.sh:**168-179**` now INSTALLS it"* | the `crontab` install is at **179–187** | `grep -n crontab ops/deploy.sh` | replaced with **"step 10/12"** |
| 6 | *"**The only demo account that can reach `/app`**"* | **eleven** demo accounts hold a back-office role | `model_has_roles` ⋈ `roles` ⋈ `users` | corrected, and Front Desk named |
| 7 | *"the full invariant list **#1–37**"* | the same file narrates to **#1–73**; the repo cites up to **#115** | `grep` over `CLAUDE.md` + `docs/` | ⚠️ **number removed**, pointer kept |

⚠️ **#6 was already false before this sequence** — `demo-admin`, `demo-accountant` and `demo-housekeeper`
have held back-office roles for months. A session following that line would audit `/app` as Hotel Manager
and believe it had covered the product.

⚠️ **#7 is a contradiction INSIDE ONE FILE**: line 70 ends *"Invariants #1–73"* and line 86 says the full
list is *"#1–37"*. A reader cannot tell which to believe.

### ⚠️ STALE — true once, now misleading (2)

| # | claim | reality | action |
|---|---|---|---|
| 8 | *"**~89** styled, browser-verified screens"* | **218** distinct GET URIs today (`/app` 54 · `/owner` 47 · `/guest` 29 · public 58) | ⚠️ replaced with **`route:list`**, not a new figure |
| 9 | *"read `docs/STATE.md` FIRST — it is **short**"* | STATE.md's own header measures itself at **299 lines / ~3,950 words / ~18 minutes** | corrected to what its header says |

⚠️ **The thirteen suite figures inside line 70 (900/7409 → 1217/9225) were left as history**, because each
belongs to the gate it describes and is correct as a record. **Only the last one read as current state**, and
it is now dated with an explicit warning never to quote a count from this file as current. **The real suite
is 3,386 / 25,022.**

### ✅ TRUE — verified, not assumed

The entire rest of the Stack block: **Laravel `^13.8` · Livewire `^4.3` · Tailwind 4 via `@tailwindcss/vite`
· Horizon `^5.47` + predis · spatie `^8.1` · Pest `^4.7` / PHPUnit `^12.5` · PHPStan L5 · stripe-php `^20.3`
· dompdf `^3.1`** (all from `composer.json`/`package.json`); **no `playwright.config.*` and no playwright
package** — the file's claim that browser verification is not an installed suite is **correct**;
`phpunit.xml` really sets `memory_limit=512M`.

⚠️ **All 27 `docs/…md` paths it points at exist. All 14 symbols it names exist** (`FolioService::refundPayment`,
`AssignmentService::TERMINAL_STATUSES`, `assertNoRawBlade`, `EnsureBackOfficeAccess`, `StripeSafetyGuard`,
`MailSafetyGuard`, `EnsurePrimaryOwner`, `RosterWeek`, …). **The structural claims hold.**

### UNVERIFIABLE

*"multi-tenant, AI-native hotel management SaaS platform"*, *"THE PRODUCT IS COMPLETE, DEEP, DEMOABLE"* — no
machine-checkable referent. **Left alone**: they are positioning, and rewriting them would substitute one
reader's judgement for another's.

---

## 2 · A count is a claim — so most counts were removed, not updated

⚠️ **Where the code can be read, a pointer replaced the figure**, because a number in prose has to be
re-verified by hand forever:

- *"9 scheduled commands"* → **`php artisan schedule:list`**
- *"~89 screens"* → **`php artisan route:list`**
- *"invariant list #1–37"* → the pointer, no range
- *"deploy.sh:168-179"* → **"step 10/12"** (line numbers are the most fragile pointer there is)

The one count that had to stay — the historical suite figure — now **says when it was measured**.

---

## 3 · Aspirations in the present tense

The PWA line was the only one. Every other present-tense feature claim resolves to shipped code, spot-checked
against routes and services.

⚠️ **The PWA absence was NOT added to `KNOWN-LIMITS.md`.** That file states limits **customers meet**; not
shipping an installable app is not a limit a customer encounters — the guest and operator realms are
responsive, and whether to ship a PWA is a product decision, **reported not taken**. ⚠️ Writing *"this does
not ship PWAs"* anywhere would also **negate by quoting** — it plants the idea it denies. **The subject is
simply gone.**

---

## 4 · The other documents

| document | verdict |
|---|---|
| **`docs/STATE.md`** | ⚠️ **Honest by construction, and the model CLAUDE.md should follow.** Every figure is dated and marked 🔬MEASURED / 📖ASSERTED / 📄RECORDED, and line 280 says its own figures are *"stale as current state; correct as history"*. **Stale but not false** — it predates this sequence entirely (last commit 2026-08-28; no mention of the passwordless gate, Google sign-in, the seven demo accounts, the seeder guard, reconciliation or the disclosure). **Left unedited: nothing in it asserts a false present.** |
| **`docs/KNOWN-LIMITS.md`** | ⚠️ **UNTRACKED — never committed.** Dated 2026-08-31 and self-consistent. ⚠️ **`CLAUDE.md` never mentions it**, so a file written to be read is invisible to the file everyone reads. **Reported.** |
| **`docs/GO-LIVE-REQUIREMENTS.md`** | Consistent; B18 (Google) added last pass. |

⚠️ **No contradiction found BETWEEN the documents** — the contradictions were inside `CLAUDE.md` (#7) and
between `CLAUDE.md` and the code.

---

## 5 · What was missing — two added, the rest proposed

**Added** (both to the Commands section, where the command they qualify already lives — no restructuring):

1. ⚠️ **Run the suite ALONE**, and **`RefreshDatabase` is deliberately OFF** so the test DB persists and every
   fixture needs unique ids. Both have cost passes; the second broke two neighbouring tests.
2. ⚠️ **`ops/money-as-seeded.sh`** — the money gate. **A real, executable script the file never mentioned.**

**Already present, so nothing added:** the silently-false `can()` without a team is documented at invariant
#30. ⚠️ *(My first grep said it was absent — the pattern was wrong. Checked again before proposing.)*

**Proposed, NOT added** — each would be one line, and the file is already long:

- a mutation must be checked to have **LANDED**, and a green mutation is a **gap**, not a pass;
- a source-scanning guard **reads its own needle** (ten instances; a heredoc is a string);
- `php artisan serve` can serve a **stale `app/` class**, and killing it by `pkill -f "artisan serve"` leaves
  the **child** listening;
- a brief is wrong often enough that its claims must be verified.

⚠️ **Not bulk-added deliberately: the most valuable version of this file is the one that still gets read.**

---

## 6 · Corrected or removed — never softened

*"ships PWAs"* did **not** become *"is PWA-ready"* or *"supports progressive enhancement"* — the same claim
with better manners. **The subject was removed from the sentence**, which now lists only what ships. Nothing
anywhere negates a false claim by restating it.

---

## 7 · ⚠️ What can be guarded — two classes, and the rest cannot

`tests/Feature/ProjectMemoryTruthGuardTest.php`:

1. ✅ **Every `docs/…md` path CLAUDE.md names must exist** — a pointer that rots fails the reader exactly as a
   false fact does. (Non-vacuity floor: >15 paths must be found.)
2. ✅ **Every framework constraint it pins must match `composer.json`** — eight packages including `php`, the
   one that had drifted. (Floor: all eight must resolve.)
3. ✅ **No service worker and no `rel="manifest"`** — the claim that started the audit, asserted against the
   tree. ⚠️ **This does not forbid building a PWA**: if one is built the test fails, and that failure is the
   reminder to update `CLAUDE.md` in the same change.

⚠️⚠️ **EVERYTHING ELSE IS NOT HONESTLY GUARDABLE, and saying so is part of the result.** *"The architecture
is multi-tenant"*, *"the product is complete"*, a screen count, an invariant range — none has a
machine-checkable referent, and a guard asserting them would either be vacuous or would freeze one reader's
interpretation as fact, which is the defect it is meant to prevent. **The counts are not guarded because they
were removed rather than pinned.**

⚠️ **A first draft of the version guard matched only 3 of 8** — it required the constraint alone in
backticks while the file writes `` `stripe/stripe-php ^20.3` `` as one span. **It was measuring its own
formatting, not the fact.** Caught by its own non-vacuity floor.

### Mutations — four, each applied, landed, RED

| # | mutation | landed | |
|---|---|---|---|
| **M1** | ⚠️ **the wrong fix** — restore the stale `^8.3` PHP floor | ✅ | **RED** |
| **M2** | point CLAUDE.md at a renamed document | ✅ | **RED** |
| **M3** | ship a `rel="manifest"` link in a view | ✅ | **RED** |
| **M4** | break the path guard's non-vacuity floor | ✅ | **RED** |

---

## ⚠️ Where this brief was wrong

1. **"the loudest recorded-fact contradiction"** — the PWA line is false, but the **sharper** defect is the
   one *inside* the file: **#1–73 in one paragraph and #1–37 six lines later.** A reader can dismiss a
   marketing phrase; a self-contradiction gives them no way to choose.
2. **The implied "AI agents" was fabricated too** — it was not. `AskVyrobelService` is real and routed;
   only the plural overstated it.
3. **"`KNOWN-LIMITS.md` … establish whether the PWA absence belongs there"** — it does **not**: that file
   records limits customers meet, and putting it there would negate by quoting.
4. ✅ Correct and load-bearing: that a file wrong about its loudest claim is unlikely to be right about the
   quiet ones — **six further errors**, including two counts, a stale line range and a false demo
   instruction.
