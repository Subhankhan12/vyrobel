# How much of the app goes blank when the script does not arrive?

**2026-09-03.** Measured across **80 authenticated screens** in a real browser, with the script **blocked**
and, separately, with JavaScript **disabled**.

---

## ⚠️ The answer, first: none of it

**Zero screens go blank or near-blank in either realm.**

| realm | screens | unchanged (≥90% of main-content text) | materially reduced | blank |
|---|---|---|---|---|
| operator | 45 | **42** | 3 | **0** |
| owner | 35 | **34** | 1 | **0** |
| guest | — | `layouts/app.blade.php` carries **no `x-cloak` at all** | — | **0** |

⚠️ **So the brief's framing — "how much of the operator and owner apps go blank" — has the answer *none*,
and the mechanism being shell-wide did NOT make the harm shell-wide.** A per-screen defect had a shell-wide
cause, and the census is what distinguishes them. A shell-wide fix would have been wrong.

**Ranked by how much of the main content disappears** (chrome excluded — see the instrument note):

| screen | main text | controls | ⚠️ working POST buttons |
|---|---|---|---|
| **`/app/housekeeping`** | 961 → 574 | 55 → 34 | ⚠️⚠️ **7 → 0** |
| `/app/group-booking` | 494 → 251 | 18 → 9 | 0 → 1 |
| `/app/reservations/create` | 803 → 610 | 82 → 20 | 0 → 1 |
| `/owner` | 1624 → 1430 | 59 → 56 | 2 → 2 |

⚠️ **Only ONE screen loses working functionality.** The other three lose Alpine-driven affordances that
would not have worked anyway — and two of them *gain* a submit button, because a fallback becomes visible.

**Cloaked page body vs cloaked dropdown, as the brief required:** of the 158 `x-cloak` occurrences, the ones
that matter are those whose `x-show` is **TRUE at init**. Everything else — modals, dropdowns, the
notification panel, the assign/detail overlays — starts hidden and is **correctly** cloaked. The housekeeping
board was the only case where *every* branch of a mutually-exclusive toggle was cloaked, so nothing showed.

---

## 2 · What still WORKS with scripts off — and it is a lot

| | operator | owner |
|---|---|---|
| screens with a real `<form>` | 38 / 45 | 35 / 35 |
| `<form>` elements | 302 | 220 |
| visible submit buttons, script working | 105 | 100 |
| **visible submit buttons, script BLOCKED** | **100** | **100** |

⚠️⚠️ **200 of 205 submit buttons survive the script never arriving.** The app is overwhelmingly
server-rendered forms posting to certified, gated routes — they do not need Alpine, and they keep working.

**So cloaking the housekeeping board really was throwing away working functionality**, not hiding something
that was inert anyway: 13 real `<form method="POST">` and 7 submit buttons sat in the DOM, reachable by the
server, invisible to the user. ⚠️ **That is the interesting answer the brief asked for, and it is the reason
this was worth fixing rather than papering over with a message.**

---

## 3 · The failure modes today

| mode | what a user got, before |
|---|---|
| bundle **404** after a deploy / **CSP** refusal (measured by blocking the request) | main text 961 → **574**, **0 of 7** submit buttons |
| **JavaScript disabled** (measured separately) | **identical**: 574, 0 of 7 |
| **slow network** | the same view, for as long as the script takes |

⚠️ **A permanent failure was indistinguishable from a slow load** — the cloak applies from first paint, so
"still loading" and "never arriving" render identically, with no error and nothing to wait for.

⚠️ **The timing question matters and the answer favours keeping the cloak where it belongs:** the
`[x-cloak] { display: none !important }` rule ships in the head, so the content is hidden **from first
paint** — there is no flash to observe. That is a genuine purpose, and the fix preserves it.

---

## 4 · `<noscript>` is necessary and not sufficient — so what covers the failed load?

A browser with JavaScript **enabled** whose bundle **404s** never renders `<noscript>`. The three honest
options were:

| option | verdict |
|---|---|
| **ship the content visible and let the script hide it** | ✅ **chosen** — it covers *both* modes identically, because it does not depend on why the script is missing |
| a timeout that reveals cloaked content if Alpine has not booted | ❌ **actively wrong here** — it would pop open every modal, dropdown and overlay in the app. The census says the remaining cloaked elements *should* stay hidden |
| an explicit failure message | 🚫 not needed once nothing is hidden that should be shown; it is also **copy**, which is the founder's |

⚠️ After the fix there is no blank screen to explain, so "blank is worse than broken" is satisfied by
removing the blank rather than by describing it.

---

## 5 · What the cloak is really for — preserved

`x-cloak` hides un-initialised Alpine markup. But **an element whose `x-show` is TRUE at init is not
un-initialised markup — it is the content.** The board's `x-data` starts
`view: localStorage.getItem('vy:hk:view') || 'list'`, so `list` is the initial view and `board` is not.

**The fix uncloaks only the `list` branch.** The `board` branch stays cloaked, so the two views never render
at once before Alpine boots — the flash the cloak exists to prevent. ⚠️ **The residual cost, stated:** a user
whose stored preference is `board` sees a brief flash of the list. Cosmetic, on a minority path, against a
blank working surface on the failure path.

---

## 6 · The marketing rule does NOT transfer — argued, not assumed

*"Visible by default, JS collapses"* was established for a site that ships **no framework** and degrades to a
complete, useful document. **This app needs JavaScript and always did** — Livewire cannot function without
it. Transplanting that rule wholesale would have been the kind of imported premise this project keeps
catching.

⚠️ **What transfers is narrower and stands on its own: do not hide the branch you have chosen to show.**
That is defensible without any appeal to no-script support, and it is the rule the guard encodes.

---

## 7 · Instrument notes — two errors caught before they were reported

⚠️ **My first metric was wrong.** I measured `document.body.innerText`, which is dominated by the shell's
nav and header, so a page whose entire working surface was hidden still scored **"60% kept"**. Re-measured
against `<main>` only. **A number that flattering would have understated the defect and I nearly published it.**

⚠️ **My first owner census ran as the OPERATOR.** The second login did not log out first, so the session
persisted and the run landed on `/app/dashboard` — the wrong realm entirely. Caught by asserting the landing
path, and fixed with an explicit logout. *A pass is bounded by the role it runs as.*

**Both failure modes were driven separately**, as required: `Network.setBlockedURLs` for a 404/CSP refusal,
and `Emulation.setScriptExecutionDisabled` for disabled JavaScript. They produced identical results here.

---

## 8 · Scope, and the guard

**Fixed per-screen, because the census says the cause does not generalise** — one screen, one branch. Fixing
the shell would have changed 80 screens to solve one, which is the inverse of the instance-not-class error.

`tests/Feature/CloakedInitialStateTest.php`:

- **the class guard** — no element may carry `x-cloak` when its own `x-data` makes its `x-show` true at init.
  It parses the initialisers (`key: 'lit'` and `key: localStorage.getItem(…) || 'lit'`) and the branch
  conditions (`key === 'lit'`), and carries a **non-vacuity floor** so "no offenders" cannot mean "found
  nothing". ⚠️ **Its limitation is stated in the failure message rather than implied:** it judges only that
  shape — the one a view toggle uses, and the one that produced the measured defect.
- **the instance** — the default branch must be uncloaked *and* the non-default one must stay cloaked, so a
  naive "remove the cloak" cannot pass.

**Mutations** — each applied, confirmed landed in its own block, run, reverted:

| mutation | |
|---|---|
| the default branch cloaked again (the measured defect) | **RED (2)** |
| the non-default branch uncloaked (the flash returns) | **RED (1)** |
| the comment-strip removed | ⚠️ **GREEN — see below** |

⚠️⚠️ **The comment-strip is PRECAUTIONARY, NOT LOAD-BEARING, and I am saying so because the mutation proved
it.** The fix's own comment explains why that branch is not cloaked and contains the word `x-cloak`; I
stripped comments expecting the scanner to read its own needle — the trap that has fired nine times here.
It does not, because the comment contains no tag-shaped `x-cloak` + `x-show` pair. **The strip stays** (a
future comment quoting the pattern would fire it) **but claiming it was necessary would have been a false
claim about my own work.**
