# ⚠️ FINDING — registering a master changed an already-committed page

**2026-08-23 · slice 1 · `story-lisbon` · ⚠️ NOT overwritten; this needs a ruling**

---

## What happened

Registering the 13 missing doc masters made `Vyrobel Article Housekeeping` known to the converter. ⚠️ **A
link on `story-lisbon` pointed at exactly that master**, and the converter treats a link to a known page
differently from a link to an unknown one:

| | |
|---|---|
| **committed today** | `<a href="#" …>Read: housekeeping at scale</a>` — ⚠️ a dead anchor going nowhere |
| **converter now produces** | `<x-marketing.nav-link key="article-housekeeping" …>` — a **marked non-link**, because that page is `pending` |

⚠️ **The registry is GLOBAL.** Adding an entry for the docs work reshaped a page that has nothing to do
with the docs — a coupling worth knowing about before the next registration.

## Blast radius: exactly one page, one link

**18 of 19** built pages still regenerate byte-identical. Only `story-lisbon` differs, and only in this one
anchor. No other built page links to any of the 13 newly-registered masters.

## ⚠️ It was NOT overwritten, and the driver is why

`scbuild.py` refused to write it and printed the diff — **the scenarios port's refuse-to-overwrite rule
doing its job on its first real test.** The standing rule is explicit: *never overwrite a committed page
when a regeneration differs; every difference is a finding.*

## ⚠️ But the consequence must be stated plainly

**`story-lisbon` is now un-regenerable** — converter output ≠ committed file. ⚠️ **That is precisely the
state the scenarios port existed to eliminate**, and leaving it is not neutral: it is the same landmine,
re-armed on one page.

## ⛔ The ruling needed

1. **Accept the change and regenerate the page.** ⚠️ The new output is arguably *better*: a dead `href="#"`
   becomes an honest marked non-link, exactly how every other unbuilt destination is rendered. It is a
   presentation-only change to one anchor. **This is what we would recommend.**
2. **Revert that one registry entry.** Keeps the page byte-identical, but `article-housekeeping` then has no
   registry key — and it is one of the 27 the docs work needs.
3. **Leave it un-regenerable.** ⚠️ We would not: it re-arms the exact landmine on a page whose honesty
   rewrites are the reason the scenarios port happened.

⚠️ **Nothing was written either way.** The page on disk is unchanged.
