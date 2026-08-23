# Founder rulings on the doc-claim spec

**2026-08-23 · ⚠️ recorded here because slice 1 reads the spec — and in `docs/DECISIONS.md` because a
future reader looks there. §N.32: a ruling that lives in one place is not propagated.**

---

## The three articles — ACCEPTED as recommended

### `article-direct` → **cut the predicted outcomes**

**Concretely:** remove every sentence that predicts a result for the reader. The two named in the spec are
*"Operators who do only this typically see direct share move several points in a season"* and *"Operators
who stay with it for a full season generally find the line crosses somewhere they didn't expect…"*. The
mechanics stay: what direct booking costs, what it requires, what has to be in place.

⚠️ **THE REASONING, WHICH WILL BE NEEDED AGAIN: a predicted outcome cannot be hedged into truth.**
*"In our view, operators typically see direct share move several points"* is the same prediction with a
hedge in front of it — no better evidenced, and now unfalsifiable as well as unsupported. The only honest
forms are to **cut it** or to **state the mechanism without the outcome**. ⚠️ This is the general rule for
the whole G6 class, not a fact about this page.

⚠️ **This page is separately blocked** by `S1-041`, which carries B8's *"fifteen to eighteen percent"* — a
G2 claim awaiting sourcing. Cutting the outcomes does not unblock it.

### `article-pricing` → **cut back to a mechanics explainer**

**Concretely:** the page keeps what the levers ARE and how they interact — seasons, weekend uplift,
minimum stay, per-unit overrides, and the order they resolve in. It drops every claim about **what works**:
*"Friday and Saturday sell themselves in most leisure markets"*, *"in city markets it often behaves like a
weekend night"*, *"most small operators are leaving money on two of them"*.

⚠️ **Why an explainer and not a hedged advice page:** the advice is revenue-management guidance we have
never tested against a portfolio, and hedging it produces confident-sounding copy with nothing behind it.
The mechanics are ours to describe exactly.

⚠️ **It must also drop the orphan-gap promise** (`S1-006`'s surrounding argument) — see `g5_g_false.py`:
the mechanism does not exist.

### `article-housekeeping` → **re-argue as "how we built housekeeping and why", OR do not publish**

⚠️⚠️ **THIS REMAINS A LIVE EITHER/OR. It is not a settled rewrite.** The ruling accepts the
recommendation, and the recommendation was itself a choice between two outcomes. Slice 1 must not read
this as "rewrite it" and quietly pick the first branch.

**Concretely, if re-argued:** the page becomes an account of our own design reasoning — why the board has
four states, why nothing is added by hand, why a lane nobody clears is worse than no lane. Every one of
those is ours to assert. **If not published:** the page is dropped and `/resources` stops advertising it.

⚠️ **Why it is the hardest of the three:** at **34% hedge density** — 12 of 35 prose blocks — its premise
*is* the unevidenced claim that the reader's team has no shared picture of the day. Reframing sentence by
sentence leaves the same article with its confidence removed.

## `S1-042` — KEEP IT, NARROWED

**Original (unassertable):** *"Everyone who touches Vyrobel gets their own login."*

⚠️ Nothing in the product prevents two people sharing a password, so as written this is a claim about
**user behaviour**, not about the product, and no test could settle it.

**Narrowed replacement:**

> **Vyrobel issues a separate account for each person you invite, and stamps the acting account on every
> action it audits.**

⚠️ **THE NARROWING IS HELD TO A HARD TEST, NOT TO SOUNDING SAFER:** it is sourced by `kind: internal`
citing the test `S1-042` in `MarketingInternalClaimsTest`, which asserts (a) two invited people are two
distinct accounts and (b) an audited action carries the acting account's id. ⚠️ **Mutation-proven:**
stop stamping `actor_id` and the test REDs.

⚠️ **Both halves are PRESENCE-shaped** — they assert something IS done, not that something is prevented.
That is stronger footing than the `S1-162/163/164` absence claims, which can only report that a value was
not found on the surfaces they check.
