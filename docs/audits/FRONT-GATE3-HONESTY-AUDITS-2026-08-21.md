# Front website · Gate 3 — the two honesty audits (run BEFORE any page was built)

**2026-08-21.** Read-only analysis. Money as seeded. ⚠️ **Neither the case studies nor the two
competitor-comparison pages were built** — both outcomes are the founder's to rule.

---

## ⚠️ AUDIT (A) — the three case studies ARE D1's three invented, unconsented customers

### What each page asserts

| page | the customer | asserted facts | the quote |
|---|---|---|---|
| `/case-studies/cyclades` | **Kastellis Villas** — ledger `real: null, consent: false` | 42 villas · 31 owners · Cyclades, Greece · replaced "a channel manager, a spreadsheet per owner and a WhatsApp group" · *"the phone stopped ringing"* | *"Our owners stopped calling at month end"* |
| `/case-studies/lisbon` | **Hotel Alfama Reyes** — `real: null, consent: false` | 96 keys · Lisbon · migrated off "a legacy PMS the front desk tolerated" · department-by-department cutover in February | *"Housekeeping runs itself now"* |
| `/case-studies/rotterdam` | **Van Dijk Stays** — `real: null, consent: false` | 18 serviced apartments · Rotterdam · a two-person family business · ⚠️ **"~20% → 50%+ share of nights arriving direct, across one season"** | *"That's not a feature list, that's payroll"* |
| `/case-studies` (index) | all three | *"Three businesses, three reasons for switching"* | ⚠️ *"**Want to talk to one of them?**"* |

⚠️ **The index offers to introduce a prospect to a business that does not exist.** That is the sharpest item
in this audit: not a decorative fabrication but an actionable one, and the point at which an invented case
study stops being marketing copy and becomes a representation a prospect could act on.

⚠️ **The Rotterdam page carries a quantified outcome claim** — a specific before/after performance figure
attributed to a named business. It is the shape of claim that regulators treat as a testimonial-with-results.

### What survives D1's filter: ⚠️ **nothing — and worse, the filter cannot reach these pages at all**

The ledger governs **data**; these pages are **markup**. So there are exactly two possible builds and both
are unacceptable without a ruling:

| if built… | production result |
|---|---|
| as ordinary converted pages (the Gate-2 pattern) | ⚠️ the content is hard-coded markup — **D1's filter never touches it**, and three invented businesses with a quantified outcome claim and three quotes publish straight to production. This is precisely the limit Gate 0 documented and D13 exposed. |
| as ledger-driven pages (the stats-band pattern) | every customer is `consent: false` → filtered → ⚠️ **four pages whose production shape is EMPTY** |

⚠️ **And an empty publicly-routed, sitemapped URL is worse than no page**: dead weight for crawlers, a
visitor landing on nothing, and — because URLs outlive their content — an SEO liability that persists after
the content is fixed. **Publishing then removing leaves dead URLs; never publishing costs nothing.**

### The options (⚠️ NOT picked — this is D1's territory)

| | option | trade-off |
|---|---|---|
| (i) | Build them; do not register or sitemap in production until real stories exist | The work is banked, nothing publishes. ⚠️ But four page files exist that no test can prove correct, and the temptation to "just switch them on" is exactly how this content leaks. |
| (ii) | **Defer entirely until a real customer consents** | Costs nothing, risks nothing, and matches how every other unbuildable thing in this project has been handled. ⚠️ `/case-studies` stays a pending link — which the nav already renders correctly. |
| (iii) | An honest index with a "stories coming" state and no story pages | One real page, no invented customers. ⚠️ But an index of nothing is thin content, and it publishes a URL whose only job is to say "nothing here yet". |
| (iv) | Reframe as clearly-labelled ILLUSTRATIVE scenarios | ⚠️ **This changes what the page IS.** A *case study* is by definition a claim about a real customer; relabelling makes it a worked example, which is a legitimate format but not what the design drew, not what the nav promises ("Customer stories"), and not what a prospect clicking "Want to talk to one of them?" expects. |

**Recommendation: (ii), then (iii) as an interim if `/case-studies` must exist for nav completeness.** The
asset here is not the page — it is a consented customer. One real story is worth more than three invented
ones, and the invented ones actively cost credibility if a prospect ever checks.

---

## ⚠️ AUDIT (B) — every named-competitor claim in `Alternatives` and `Compare`

⚠️ **These sit entirely outside the content gate's reach.** The ledger flags Vyrobel's own figures and
customers; it has no concept of an assertion about a third party. Nothing in CI can see these claims.

### `Alternatives` — ⚠️ **the design is unusually careful here, and that matters**

It names **Mews, Cloudbeds, Guesty and Hostaway** and opens by calling them *"good products with real
customers"*, carries a section titled *"Where we'd tell you to buy something else"*, and ends with a genuine
disclaimer:

> *"Positioning on this page reflects publicly available category analysis at the time of writing and
> describes how each platform is generally positioned, not a feature-by-feature audit. We have not tested
> these products against ours, and none of the vendors named have reviewed this page. Capabilities and
> pricing change; verify with each vendor before deciding — including with us. Mews, Cloudbeds, Guesty and
> Hostaway are trademarks of their respective owners and are named here for identification only."*

That is close to best practice. The claims are **positioning**, not feature or price comparisons, and
several are self-deprecating (*"not a claim of superior engineering"*, *"We are not the cheapest"*).

| # | the exact assertion | verifiable? | date-sensitive? |
|---|---|---|---|
| B1 | **Mews** — *"API-first architecture and a large integration marketplace"*; built for *"boutique hotels and groups with technical resourcing"* | ⚠️ Partly — the API and marketplace are publicly documented; **"large"** is a judgement | Low |
| B2 | **Cloudbeds** — *"PMS, channel manager, booking engine and payments bundled under one subscription, with a broad installed base among independent properties"* | ⚠️ Partly — the bundle is on their public site; ⚠️ **"broad installed base"** is an unsourced market claim | Medium |
| B3 | **Guesty** — *"Vacation-rental automation and marketplace distribution depth"*; *"short-term rental managers at scale"* | ⚠️ Judgement, not fact | Low |
| B4 | **Hostaway** — *"automation without heavy setup"*; *"growing rental portfolios"* | ⚠️ Judgement about a competitor's **ease of setup** — the least defensible of the four | Medium |
| B5 | ⚠️ *"Per unit, the market clusters in a similar band and we sit inside it"* | ⚠️ **No** — an unsourced claim about **competitor pricing**, the category regulators scrutinise most | ⚠️ **HIGH** — competitor pricing changes without notice |
| B6 | *"Some platforms bundle a booking engine; others expect you to bring one from their marketplace"* | ⚠️ Partly — publicly checkable per vendor, but stated generically | Medium |
| B7 | ⚠️ The disclaimer says *"at the time of writing"* — **with no date anywhere on the page** | — | ⚠️ **This is the rot.** An undated "at the time of writing" is unfalsifiable and ages invisibly. |

### `Compare` — ⚠️ **carries the harder numbers and has NO disclaimer at all**

| # | the exact assertion | verifiable? | date-sensitive? |
|---|---|---|---|
| B8 | ⚠️ **"Typical cost: 15–18% of every booking"** (OTAs) | ⚠️ **Unsourced.** In principle checkable — Booking.com/Airbnb/Expedia publish commission structures — but rates vary by market, property type and programme (a Preferred Partner pays more) | ⚠️ **HIGH** |
| B9 | A side-by-side table asserting **"—"** (absent) for OTAs and **"Add-on" / "Separate app" / "Manual" / "Varies"** for "channel manager + spreadsheets" | Category generalisation, not a named-vendor claim — ⚠️ **lower risk**, since no specific product is named | Medium |
| B10 | *"If you run one apartment and Airbnb sends you all the bookings you want, a PMS is overhead. Stay where you are."* | Not a comparative claim — advice against buying | Low |
| B11 | ⚠️ **NO trademark notice, NO disclaimer, NO date** — while naming Mews, Cloudbeds, Guesty, Hostaway and Airbnb | — | — |

### ⚠️ B12 — a Vyrobel-about-Vyrobel contradiction, and it is **already live**

| surface | says |
|---|---|
| `Compare` (not yet built) | **"€4–6 per unit, per month"** |
| ⚠️ **`/pricing`, LIVE since Gate 2** | **Starter €6/unit/mo · Pro €4/unit/mo · Enterprise custom** |
| ⚠️ **`config/billing.php`, what actually charges** | **`per_unit_minor = 900` → €9.00/unit/mo**, tiers named **Essential (€49/mo)** and **Pro (€99/mo)** |

The per-unit **model** matches (billing really is per active unit). The **rate does not** — €4–6 advertised
against €9 configured, a 50–125% gap — and the **plan names do not** (Starter/Pro/Enterprise vs
Essential/Pro). Both sides are labelled placeholder, so neither is a lie; but they are **two placeholder
sets that disagree on the money surface**, and a prospect who reads €4/unit and then gets quoted €9 has
been misled by an inconsistency nobody intended.

⚠️ **I missed this in Gate 2** — the Pricing page was reproduced faithfully and its numbers were never
reconciled against `config/billing.php`. It is live now.

### The refusal, and what would make each claim defensible

⚠️ **Under the rule "refuse to publish any unsourced or unverifiable comparative claim", B1–B6 and B8 are
all refused** — the Alternatives disclaimer *itself states* the claims are untested ("We have not tested
these products against ours"). Removing them does not trim these pages: **it removes their subject.**
`Alternatives` is a page whose entire content is competitor positioning; `Compare`'s central table and its
headline OTA cost figure are the page.

> ⚠️ **So the honest report is: these two pages cannot be built as drawn without a ruling.** Shipping them
> with the claims removed would leave a hollow comparison that compares nothing; shipping them intact would
> publish unsourced third-party claims that no CI gate can see.

**What would make each defensible** — per claim, one of:
1. **A citation + a "verified on" date** — a link to the vendor's own public page, checked and dated.
2. **Softening to non-comparative** — describe what Vyrobel does without asserting what others do
   (e.g. B5 becomes *"our pricing is per unit and includes the booking engine"*, with no market claim).
3. **Removal.**

Cheapest defensible path for `Alternatives`: keep the *structure* and the honest framing, replace B1–B4
with **quotes/links to each vendor's own positioning** (their words, cited and dated), delete B5 entirely
(competitor pricing is the one claim not worth making), and **date the disclaimer**. For `Compare`: cite B8
to the OTAs' own published commission pages with a date, or replace the figure with *"commission varies by
marketplace and programme — check your own statements"*, which is both true and more persuasive.

### ⚠️ The freshness mechanism (recommended — a claim with no expiry rots silently)

This is the same silent-failure family as `MAIL_MAILER=log`: nothing errors when a competitor changes their
pricing, and nobody notices for a year. Recommended, and cheap:

- A `resources/marketing/claims.json` ledger — **the same shape as the content ledger**, one entry per
  external claim: `{ claim, subject, source_url, verified_on, expires_after_days }`.
- Pages render external claims **only** through it (the §N.8f discipline that already governs figures).
- ⚠️ **A CI gate — the sibling of Gate 5** — that fails the build when any claim is past its review date or
  is missing a source, exactly as Gate 5 fails on unconsented content. **The rot then becomes a red build
  instead of a quiet inaccuracy.**

---

## Status

| | |
|---|---|
| **Case studies** | ⚠️ **NOT built.** Four pages whose subject is invented; the index offers to introduce a prospect to a business that does not exist. **Recommend (ii) defer.** Founder's ruling required. |
| **Alternatives / Compare** | ⚠️ **NOT built.** 12 competitor claims inventoried; B1–B6 and B8 refused as unsourced (the page's own disclaimer concedes they are untested). Removing them removes the pages' subject — surfaced rather than shipped hollow. |
| ⚠️ **B12** | A live contradiction: `/pricing` advertises €4–6/unit under plan names the billing system does not have, against `config/billing.php`'s €9/unit and Essential/Pro. **Founder's reconciliation required.** |
| **Built this gate** | `Switching` · `For Hotels` · `For Apartments` · `For Villas` — the four Sales pages that make no third-party claims. |

---

# ⚠️ GATE 3b — the three held items, resolved (2026-08-21)

## (A) The scenarios — rewritten, routed, and guarded

All six mandatory conditions met on all four pages. **Removed:** three invented business identities and
their city+size pins · **four attributed quotes** (three story blockquotes + three quoted `<h2>` headings on
the index) · **the "~20% → 50%+" stat block and the "4 days → 1 afternoon" callout** · **nine animated
result counters** on the index · **the "Want to talk to one of them?" section** with its "Ask for a
reference" CTA. **Added:** a prominent label immediately after each `<h1>`, in the page's own voice.

⚠️ **THE SLUG WAS CHANGED — `/case-studies/…` → `/example-scenarios/…`.** The URL is itself a claim, and
nothing was published yet, so this was the free moment. The old, claim-bearing URLs are asserted to 404.
⚠️ **This diverges from the design's own sitemap deliberately** — the page's identity changed, and a
sitemap slug is not binding when it does. **Surfaced: the founder may prefer a different honest slug; after
launch, changing it costs SEO and leaves dead URLs.**

## (B) The claims ledger + the freshness gate — built; the pages stay held

`resources/marketing/claims.json` holds all 12 claims with `source_url` / `verified_on` / `review_by` /
`disposition`. `MarketingClaims` + **CI Gate 6** refuse to publish anything unsourced, past review, or
missing an expiry (a missing `review_by` **fails closed** — it is the B7 unfalsifiable shape in code).

### ⚠️ What the founder must supply, per claim, to unblock each page

| claim | subject | classification | what unblocks it |
|---|---|---|---|
| **B1** | Mews | sourceable | Their API/marketplace page + date. Soften **"large"** to a count or their own wording. |
| **B2** | Cloudbeds | sourceable | Their bundle page + date. ⚠️ **"broad installed base"** needs a published figure or must be dropped. |
| **B3** | Guesty | sourceable | Their positioning page + date. |
| **B4** | Hostaway | ⚠️ **requires testing** | ⛔ **Cannot be sourced.** "Automation without heavy setup" is a claim about another product's ease of use. **Soften to their own positioning, or drop the setup clause.** |
| **B5** | the market | sourceable in principle | ⛔ **Recommended REMOVAL.** Unsourced competitor pricing — sharpest exposure, fastest decay. ⚠️ It also says "per unit", which **is no longer true of us** (B12). |
| **B6** | all four | sourceable | ⚠️ **One source PER vendor**, not one for the sentence. |
| **B7** | the disclaimer | sourceable | **A real date.** "At the time of writing" with none can never be shown stale. |
| **B8** | OTAs | sourceable | ⚠️ The page's most load-bearing number. Cite the marketplaces' own commission pages, **state the basis** (rates vary by market/programme), short `review_by`. |
| **B9** | categories | sourceable | Keep it categorical — naming a vendor in a cell turns it into a per-vendor claim. |
| **B10** | Airbnb | sourceable | Asserts nothing about their product; ledgered for completeness. |
| **B11** | Compare itself | — | ⚠️ **A missing safeguard, not a claim:** Compare needs Alternatives' disclaimer + trademark notice, dated. |
| **B-not-tested** | our disclaimer | — | ⚠️ **Keep it.** It is true, and sourcing public prices does not make it false. |

**Nine sourceable · one requires-testing · two page-level safeguards.** `Alternatives` and `Compare` stay
unrouted and 404 until sourced — asserted, not assumed.

## (B12) `/pricing` now reads from billing — and the divergence was bigger than Gate 3 reported

⚠️ **Correcting my own Gate-3 finding.** I reported "billing charges €9/unit". It does not: every seeded
plan is **`pricing_type = 'flat'`** — **Essential €49/mo, Pro €99/mo** — and `unit_rate_minor` is null.
`config('billing.per_unit_minor') = 900` seeds a column no plan uses. **The marketing site was advertising
a pricing MODEL the system does not run**, not merely different numbers.

| surface | was | now |
|---|---|---|
| `/pricing` | Starter €6 / Growth €5 / Pro €4 **per unit**, 4 tiers | **Essential €49 / Pro €99 `/ mo`**, from the plan rows |
| `/home` | €6 / €5 per unit (a **third** representation) | the same source |
| feature lists | "Up to 3 team members", a 4-tier ladder | the engine's **real** gates (`channels` Pro-only, `team` limit 5) |
| the framing | "One price per unit", "You pay per unit" | derived from the shape — flat today |

⚠️ **The design's four-tier per-unit ladder cannot express two flat plans**, so the grid is rebuilt from the
catalogue rather than re-numbered. ⚠️ **Surfaced: the headline copy is placeholder** — plain by design,
because writing marketing copy is the founder's, but a false headline could not stay on a live page.
⚠️ **No third price representation remains** (swept: `per_unit_minor` / `prices_minor` appear only in
billing code, its seeder and its tests).
