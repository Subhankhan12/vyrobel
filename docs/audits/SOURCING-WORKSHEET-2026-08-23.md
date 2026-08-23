# The sourcing worksheet — how to work through it

**`SOURCING-WORKSHEET-2026-08-23.csv` · 47 decisions · ⚠️ nothing in it is sourced**

---

## ⚠️ 72 rows in the ledger, **47 decisions** on this worksheet

| | |
|---|---|
| G1/G2/G3 rows in `claims.json` | **72** |
| ⚠️ removed as NOISE (headings, sidebar nav labels) | **20 rows / 5 distinct** |
| ⚠️ moved to cluster A (asserts a capability that does not exist) | 2 |
| ⚠️ moved to ruling-A labelling (a mock statement for an invented property) | 3 |
| ⚠️ **real sourcing decisions** | **47** |

⚠️ **Nobody should read 72 rows as 72 decisions.** The single largest saving is one sidebar nav label —
*"GDPR, retention and anonymisation"* — appearing in the chrome of all sixteen guides. **Sixteen rows, one
label, zero decisions.**

⚠️ **After the noise came out, the dedupe saving was exhausted**: all 47 remaining claims appear on exactly
one page each, so 47 decisions settle 47 rows.

## ⚠️ The 47 is not a clean number — expect to cut some on sight

⚠️ **Stated plainly, because the alternative is you discovering it row by row.** This pass was asked to
re-read the **25 rows without terminal punctuation**, and it did — 20 turned out to be headings or nav
labels. ⚠️ **The precision of the remaining 47 is UNAUDITED**, and the same pattern-matching that produced
those 20 false positives produced these rows too. Two are visible in the first few lines:

- `S1-001` — *"Free cancellation until 14 days before arrival, then 50%"* is a **worked example of a policy
  string**, flagged only because it contains `%`. Not a claim about anything.
- `S1-170` — *"This matters practically as well as legally."* is a **transitional sentence**, flagged
  jurisdiction-scoped because it contains the word "legally". The flag is wrong.

⚠️ **`cut_instead = y` is the right answer for these, and it costs you seconds.** The worksheet is sized to
be worked through, not to be accurate to the row — **47 is an upper bound on the research, not a count of
real claims.**

## ⚠️ Sourcing is RECONCILIATION, not lookup

⚠️⚠️ **A source almost never says exactly what the copy asserts.** You will find a report that says
something adjacent — a different year, a different market, a range where the copy states a point. **The
honest resolution is that THE CLAIM CHANGES TO MATCH THE SOURCE, never the reverse.**

That is why the worksheet has a **`revised_claim_text`** column and why it matters more than
`source_url`. A row where you paste a URL and leave the claim untouched is the failure mode this whole
ledger exists to prevent: a citation that looks like evidence and supports something narrower than the
sentence beside it.

**Three outcomes per row, all legitimate:**
1. **Sourced as written** — fill `source_url`, `verified_on`, `review_by`. Leave `revised_claim_text` empty.
2. **Sourced with a change** — fill all three *and* `revised_claim_text` with what the source actually supports.
3. **Cut** — put `y` in `cut_instead`. ⚠️ **A cut is not a failure.** Several of these will be cheaper to
   delete than to defend, and a page is not weakened by losing a claim it could never stand behind.

## What is harder than it looks

| flag | rows | why |
|---|---|---|
| ⚠️ **jurisdiction-scoped** | **6** | *"in many jurisdictions"* is **not sourceable as written**. Name one jurisdiction and cite its statute, or cut. A source for Portugal does not support a sentence about "many countries". |
| ⚠️ **vendor claim** | **3** | Cite the vendor's **terms or documentation**, never their marketing page — marketing changes without notice and is written to persuade. |
| ⚠️ **will go stale** | **9** | A rate or figure that moves. **Set `review_by` short** — this is exactly what that column is for, and a missing `review_by` fails closed. |

## ⚠️ How a later pass ingests this — designed, not improvised

The worksheet is **CSV**, not a markdown table, for one reason: **you are filling in four columns across
47 rows**, which is spreadsheet work — the claim text is long enough that a markdown table becomes
unreadable and unreliable to edit by hand. Open it in Numbers, Excel or Sheets; keep the header row.

**The round trip:**

1. You fill `source_url` / `verified_on` / `review_by`, plus `revised_claim_text` or `cut_instead`.
2. A later pass reads it keyed on **`decision_id`**, which is the ledger id — ⚠️ **do not edit that column,
   it is the join key.**
3. For each row it: writes the citation onto the matching `claims.json` row; sets `disposition` to
   `remove` where `cut_instead` is `y`; and where `revised_claim_text` is present, records it in
   `tools/marketing/spec/` as the replacement for slice 1 to implement — ⚠️ **exactly as the G4/G5/G6 spec
   already does, so there is one mechanism, not two.**
4. Gate 6 then re-evaluates: a page clears only when every claim on it is sourced, ruled on, or cut.

⚠️ **`decision_id` is one ledger row per decision here.** If a future sweep finds a claim repeated across
pages again, that decision must fan back out to every row sharing its text — `appears_on` in `claims.json`
carries that relationship. ⚠️ **A claim cut on one page while surviving on another is the sitewide-chrome
failure in a new place.**

## ⚠️ What "cleared" does and does not mean

Five pages now read as cleared — `docs`, `guide-access`, `guide-checkin`, `guide-reservation`,
`guide-security-doc`. ⚠️ **That means "no unsourced claims remain in the ledger". It does NOT mean "ready
to publish."** All five are still **unbuilt**, and four of them clear because their claims are ruled on but
**not yet implemented** — the spec's replacements still have to be applied in slice 1. `guide-access`
clears for a better reason: both its live claims are now internally sourced by a mutation-proven test.
