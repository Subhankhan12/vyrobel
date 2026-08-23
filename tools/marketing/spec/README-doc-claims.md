# The documentation-claim spec — G4, G5, G6

**2026-08-23 · ⚠️ a SPEC, not transforms · nothing published**

---

## Why this is a spec and not code

The 27 documentation pages are **unbuilt** and have **no driver** (that is slice 1). A transform written
now could not be regenerated, diffed or proven byte-identical against anything — ⚠️ **it would be code
that cannot be verified.**

⚠️ **And this is precisely the artifact the scenarios port went looking for and only half found.**
`rewrite.py` covered two pages of four; the other two were reconstructed from a diff **with no record of
intent**, and the reconstruction could only ever prove *"reproduces the current file"*. Writing the intent
down **before** the transforms exist is exactly what would have prevented that.

**Read it in this order:**

| file | what it holds |
|---|---|
| `retriage.py` | ⚠️ **read first** — the G6 bucket was four different things wearing one regex |
| `g5_g_false.py` | G5's two replacements + the 8 FALSE product claims and their corrections |
| `g6_guides.py` | 28 verbatim reframes for the 16 guides |
| `g6_articles.py` | the 7 articles + `/resources` + `/faq`: per-page verdict, worked reframe, recommendation |

Each entry is keyed by ledger id, so slice 1 can implement it mechanically: **page · original verbatim ·
replacement verbatim · why**.

## ⚠️ The finding that reshaped this pass: 23 of 96 were misfiled

Slice 0 grouped by pattern, and `most / usually / typically / nobody` catches a **hedged product claim**
exactly as readily as a population generalisation. Re-reading all 96:

| kind | n | what it needs |
|---|---|---|
| **OPINION** | 73 | reframe to what we believe/built/chose |
| ⚠️ **INTERNAL** | 12 | a claim about our product — `kind: internal`, sourced by a test |
| ⚠️ **FALSE** | 8 | ⚠️ **CORRECT it — clusters C/D/E. Hedging it would be actively harmful** |
| **NOISE** | 8 | a heading or rhetorical question — not a claim at all |

⚠️⚠️ **Had the G6 ruling been applied without re-reading, eight FALSE product claims would have been
hedged rather than corrected** — *"in our view the code releases only once the balance is settled"* is
worse than the original, because the original could at least be checked.

## G4 · an internal source is a TEST, never a pointer

`kind: internal` is in the schema. ⚠️ **A `file:line` pointer rots silently** — the code moves, the claim
quietly becomes false, and the citation still points confidently at a line that no longer says what it
said. So an internal source names a **test that fails when the claim stops being true**, and
`MarketingInternalClaimsTest` asserts every named test **actually exists**.

**Sourced today: 3 of 12** (`S1-162/163/164`, the guest-PII perimeter). ⚠️ **The other 9 are
`kind: internal` with `internal_test: null` — which reads as UNSOURCED, deliberately.** Carrying the kind
sources nothing.

### ⚠️ Presence vs absence, and why it matters
| claim | shape |
|---|---|
| S1-162/163/164 owners never see guest contact details | ⚠️ **ABSENCE** — asserts something is *not* shown |
| S1-166 *"Vyrobel does not file on your behalf"* | ⚠️ **ABSENCE** |
| S1-042 *"everyone … gets their own login"* | ⚠️ **ABSENCE-shaped** (no shared logins) — and nothing technically prevents password sharing, so it may not be assertable at all |
| S1-021, S1-106, S1-154, S1-184, S1-100, S1-183 | **presence** — testable directly |

⚠️ **An absence test rests on weaker footing and must not be presented as equally settled.** A test proves
the value is absent *from the surfaces it checks*, not from the product — *a grep proves the absence of its
own needles*. The three sourced claims are absence claims, so their citation is the strongest available,
**not proof**.

⚠️ **And a scope gap, stated rather than papered over**: the sentence says owners cannot see guest
*names*, but the product shows owners a guest name **by design** (name-only is the perimeter). The test
sources the contact-details half only. ⚠️ **The sentence as written is wider than its evidence — a slice-1
correction, not something to fix by weakening the test.**

## G5 · promise nothing measurable

Two distinct claims, 17 rows. ⚠️ *"Support replies within a working day"* is in the **chrome of all
sixteen guides** — **one change, sixteen pages** (§N.8f; the chrome has carried an untrue claim five times
before, this is the sixth).

⚠️ **Two traps avoided, both recorded in `g5_g_false.py`:** *"we usually reply quickly"* is still a promise,
merely unfalsifiable; *"we answer support email ourselves"* is **a claim with a fuse** — true today, false
the day a first hire starts, and nobody will remember it is there.

## G6 · our opinion about US, not a hedged claim about the world

⚠️ **The trap: an opinion about third parties is still a factual claim wearing a hedge.** *"In our view,
most operators find X"* is less false and no better sourced — and 73 of those is the *"we take security
seriously"* failure at scale.

**Guides (28): reframed verbatim.** Density 2–14%; a stray generalisation is decoration on an argument
that stands without it.

⚠️ **Articles (45): NOT rewritten sentence-by-sentence, deliberately.** Density 15–34%, and **the
article's premise IS the generalisation.** Reframe every sentence and you do not get a more honest
article — you get the same article with its confidence removed, which persuades nobody. ⚠️ **Producing 45
hollowed sentences would be doing the wrong thing carefully.** Each page instead carries a verdict, a
worked reframe and a recommendation, for review.

**Where reframing works well:** `article-owners`, `article-statement` — their claims were always about why
we built what we built. **Where it does not:** `article-direct` (predicted outcomes cannot be hedged into
truth — cut them) and `article-pricing` (advice we cannot source; our suggestion is to cut it back to a
mechanics explainer). ⚠️ `article-housekeeping` at 34% is the hardest: our recommendation is to re-argue it
as *"how we built housekeeping and why"* or not publish it.

⚠️ **`/faq`'s 67% is an artefact** — six static prose blocks, because its answers live in JSON-LD. Do not
read it as the most hedge-dense page.

## Disposition mechanism

Resolved rows **keep their place** — the count pin sits at 202 to catch shrinkage, and deleting rows would
fire it on legitimate work. `disposition: soften` (rewritten) or `remove` (not a claim) marks them; the
existing `pageIsCleared()` already skips those while still blocking on any unresolved `publish` claim.
⚠️ **No schema change was needed for the disposition, and none was made.**

⚠️ **Verified: 0 of the 26 pages read as cleared** — every one still carries unresolved G1/G2/G3 claims
awaiting the founder's sourcing. **Nothing was prematurely cleared.**
