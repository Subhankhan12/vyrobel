# Doc rewrite · Pass 1 — re-verify, re-cost, publish only what is already true

**2026-08-23 · read-only on content · ⚠️ nothing published, and that is the finding**

---

## 1 · ⚠️ How many of the 220 are now TRUE: **one**

⚠️⚠️ **The brief's premise is wrong on P-6.** It says the triage is stale because the product moved *three*
times — P-6 Gate 1, cluster J, cluster E. **The triage already incorporated P-6**: its §1.1 is titled *"How
many FALSE claims are now TRUE: zero. Two moved FALSE → PARTIAL"*, and cluster D is headed *"the cluster P-6
changed"*. Only **J and E** post-date it — by one day.

| cluster | claims | re-verified against current code | now TRUE |
|---|---|---|---|
| **J** · per-property scoping | 1 | `app.settings.team-scope` exists, `users.manage`-gated, editor live | ✅ **1** |
| **E** · arrival secrets | 15 | the *writer* now exists — but no doc CLAIM flipped | **0** |
| C, D, and the rest | 204 | unchanged since 2026-08-22 (one day) | 0 |

**⚠️ So: 1 of 220.** The prior re-verification found zero; this one found one. **Measured, not assumed.**

### ⚠️ Cluster E flipped no claim — it removed a *blocker*, which is a different thing

E's doc claims are all about *when* secrets release: *"held until a moment you choose"*, *"only once check-in
is complete and the balance settled"*, *"the front desk can override and release manually"*, *"on a schedule
you set"*. **`ArrivalPack::for()` still gates purely on `LEAD_DAYS = 2` and non-cancelled** — I re-read it.
⚠️ **The reveal action is not a manual release**: it shows an operator the code; it does not release it to a
guest early. All still FALSE.

**What E changed is the triage's own sentence**: *"until (i) exists the page cannot honestly tell an operator
to enter a door code at all — which is most of what it is for."* ⚠️ **That is now false.** The page is
*fixable* where it previously was not. **A blocker lifted is not a claim made true**, and conflating the two
is exactly how a re-verification inflates its own result.

### Carried forward: the lower-confidence flags
The seven `/resources` articles and `/docs/settings` remain **un-refuted, not verified** (8 of 19 Gate-5
verifiers died). Clusters **S1, B, D and K** rest materially on them. **Nothing in this pass improved that**,
and no page resting on them should publish before someone reads them adversarially.

## 2 · ⚠️⚠️ THE FINDING THAT MATTERS MOST — the claims gate is blind to all 27 pages

The brief asked whether `growing-direct-bookings` is still blocked by ledger entry **B8**. **The answer is
worse than "yes".**

```
pageIsCleared('compare')                  → false     (4 ledger entries, unsourced)
pageIsCleared('alternatives')             → false     (8 ledger entries, unsourced)
pageIsCleared('growing-direct-bookings')  → TRUE      ⚠️ 0 ledger entries
```

⚠️ **A page with no ledger entries reads as CLEARED.** `B8-ota-commission-band` — *"Typical cost: 15-18% of
every booking"*, `source_url: null` — is keyed to page `compare`. The article repeats the same unsourced
number, carries no entry of its own, and therefore **passes the gate**.

⚠️⚠️ **This generalises to all 27.** The ledger holds **12 entries, every one on `alternatives` or
`compare`.** The triage found **210 unsourced claims across the 27 doc pages** — **none of them are in the
ledger.** So Gate 6 would go green on any of them.

**So: the article is blocked by the FINDING, and invisible to the GUARD.** ⚠️ **`pageIsCleared()` fails OPEN
for anything not in the ledger** — defensible as "no held claims", dangerous as a publication gate.

⛔ **Founder-level consequence: no doc page may publish until its claims are entered in the ledger**, or the
gate passes vacuously and the S1 finding is enforced by nothing.

## 3 · ⚠️ Pages published this pass: **ZERO** — because none qualifies

The rule was: publish only pages whose claims are **all true today with no content change**. ⚠️ **The
triage's own table settles it** — 5 LIGHT EDIT · 18 REWRITE · 2 GUTTED · 2 BLOCKED = **27**. **Every one needs
at least a light edit.** Add S1 (unsourced claims on all 27) and the set is empty twice over.

⚠️ **Nothing was softened to make anything publishable.** An unfalsifiable page is worse than an absent one.

✅ **S2 verified honest**: `MarketingUrls::publishedSeries()` derives from the published route table and
returns **0** for `guide-`, `resource-` and `doc-` today; `seriesPosition()` returns null and the component
renders nothing. **It does not inflate**, so a partial publication would be honest — S2 genuinely unblocks,
it just has nothing to unblock yet.

## 4 · ⚠️ THE RE-COST — and it is bigger than the triage implied, in a different way

⚠️ **The triage's "weeks of writing, no code" is the wrong shape.** Gate 5 built **zero** of the 27; the
claims live in the masters, so an honest page = **converter transforms + a driver**.

**Measured inventory** (not estimated):

| | count |
|---|---|
| masters for the 27 pages | **27** (20 doc/guide/faq/glossary/resources + 7 `Vyrobel Article *`) — ⚠️ **the triage's 27 is exactly right** |
| of those, **in the converter registry** | **14** |
| ⚠️ **masters with NO registry entry** | **13** — 6 guides (Access, GDPR, Reports, Security, Settings, Storefront) + **all 7 articles** |
| drivers covering them | **0** |

**Against the scenarios port's actual numbers** — 61 transforms, 4 pages, 1 driver, plus zero-match,
ordering, idempotence and byte-identical proofs:

| | scenarios | docs | ratio |
|---|---|---|---|
| pages | 4 | 27 | **6.75×** |
| transforms | 61 | ~220–300 (1–1.5 per claim, as observed) | **~4–5×** |
| drivers | 1 | 1 (serving 27) | 1× |

⚠️ **Say plainly: this is larger than "3½×".** By pages it is **~7×** the scenarios port, which took a full
working session for four pages with a spec for two of them. ⚠️ **And the docs have no spec at all** — every
transform is RECONSTRUCTED by definition, the harder half of that pass.

⚠️⚠️ **And the largest part is not code.** The **210 unsourced claims** cannot be fixed by a transform. They
need a **source or a cut, per claim, by the founder**. No amount of converter work touches them.

**What a docs driver needs** (beyond the scenarios driver's shape): the shell `title`/`description` from
`convert()` meta · a per-page provenance docblock · `:index` · ⚠️ **and integration with
`<x-marketing.series-counter>`, which is a RUNTIME component, not a converter literal** — so the driver must
emit the component, never a number.

## 5 · ⚠️ A finding outside the brief's scope — 8 built pages have no driver either

`features`, `for-apartments`, `for-hotels`, `for-villas`, `how-it-works`, `modules`, `product`, `try-booking`
are **built and live** with **no driver**. ⚠️ **That is the same landmine the scenarios port just defused**:
any post-generation edit on them is invisible and un-regenerable, and nobody would find out until someone
ran a converter that does not exist for them. **Not fixed here — reported.**

## 6 · The proposed slicing — ordered by pages-unblocked-per-unit-of-work

| slice | work | publishes | why here |
|---|---|---|---|
| **0** ⛔ | ⚠️ **Enter the doc pages' claims in the ledger** (founder + a sweep) | 0 | **Nothing else is safe first.** Until this exists the gate is blind and every later slice publishes unguarded. Cheapest thing with the largest safety delta. |
| **1** | The **driver** + the **13 missing registry entries** | 0 | One-off; every later slice depends on it. Registry entries are one line each. |
| **2** | ⚠️ **Security + GDPR unconditional corrections** | `guide-security`, `guide-gdpr` | Required regardless; 2 pages for hours of work once the driver exists. |
| **3** | The **5 light-edit pages** | ⚠️ **3** (not 5 — `growing-direct-bookings` needs a source for B8's number; `owner-statement` is not a light edit) | Best pages-per-transform ratio of the content work. |
| **4** | The **18 rewrites** | 16 | The bulk. Slice by cluster, not by page, so one transform serves every page sharing a fragment (§N.8f). |
| **5** | Cluster A when the Channex account lands | `guide-channels` | Externally blocked. |

**Recommended first slice: 0 then 1.** ⚠️ Slice 0 because *the guard that is supposed to stop a false claim
currently cannot see one on any of these pages* — and slice 1 because it is one-off and unblocks everything
after it. **Neither publishes a page**, which is the honest shape of this work: the first real publication is
slice 2.

### ⚠️ Which pages the unconditional Security/GDPR corrections gate
They are **constraints on publishing, not a slice to defer**, and they cannot be "applied" to pages that do
not exist. Concretely they gate: **`guide-security`** and **`guide-gdpr`** (their subjects), plus **any page
repeating those claims** — `docs`, `faq` and `guide-settings` per clusters F and G. ⚠️ **No page carrying an
F or G claim may publish in any slice until the claim is corrected**, including in slice 3.

### Unpublishable under any slicing
**`changelog`** (fabricated — entries predating the first commit, two dated in the future; only real releases
fix it) · **`careers`** (its subject is a company that does not exist) · **`alternatives` / `compare`**
(dropped, and their 12 ledger claims are held unsourced).

## 7 · What this brief got wrong

| claim | verdict |
|---|---|
| "the product moved three times since the triage" | ⚠️ **wrong** — the triage already incorporated P-6 (§1.1). Only J and E post-date it |
| an unknown number of FALSE claims are now TRUE | ⚠️ **one**, and it is cluster J's |
| "220 claims is roughly 3½× the scenarios port" | ⚠️ **understated** — ~7× by pages, and with **no spec**, which is the harder half |
| the 27 pages have no driver | ✅ confirmed — ⚠️ **and 13 of the 27 masters are not even in the registry** |
| `growing-direct-bookings` may still be blocked by B8 | ⚠️ **worse** — it is blocked by the finding while reading as **CLEARED** to the gate |
| Gate 5 built zero of the 27 | ✅ confirmed |
| the triage's "27 pages" | ✅ confirmed exactly — 27 masters on disk |
