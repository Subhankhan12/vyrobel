# A season now says what it is about to overwrite — the disclosure half only

**2026-09-05.** ⚠️⚠️ **No policy changed.** `SeasonPrecedence` is untouched, no season refuses anything, and
applying is still permitted exactly as before. **Whether a season SHOULD be able to replace a negotiated rate
remains an open founder ruling.** This pass makes the existing behaviour visible before it lands.

---

## 1 · ⚠️⚠️ Is an owner-agreed rate distinguishable at all? — **Partly. And the part that is not is named.**

`rate_calendar` carries **`id`, `organization_id`, `rate_plan_id`, `date`, `price_minor`, `currency`,
timestamps**. ⚠️ **There is no provenance column of any kind.** Nothing records who set a price or why, and
the audit row's `before` is null — which is exactly why undo was refused rather than built.

⚠️ **But a NEGOTIATED rate has a second record.** An **approved** `rate_proposals` row carries `owner_id`,
`room_type_id`, `from_date`, `to_date`, the applied rate (`countered_rate_minor ?? proposed_rate_minor`) and
`proposed_weekend_pct`.

### How precisely — **to the night, per room type**

`from_date`/`to_date` bound the nights; `room_type_id` resolves to the season's rate plan. So the disclosure
names **individual nights**, not "some of these".

### ⚠️⚠️ And the test is a VALUE MATCH, not an inference

A night is reported only when it **both** falls inside an approved agreement's window **and** still carries
the exact price that agreement applied for that night. So it never guesses — the price either equals the
agreed figure or it does not.

### Supersession — handled, and it is why the disclosure stays quiet

- **A later approved agreement** over the same nights wins: agreements are read in `decided_at` order and
  the last one covering a night is the one that wrote it.
- **A rate changed after approval** — by anyone — makes the value match fail, and the disclosure **falls
  silent**. That is correct: a warning about an agreement somebody has already replaced is noise, not
  information. Proven.

---

## 2 · ⚠️⚠️ The false negative, named — and it is large

> ⚠️⚠️ **AN OWNER'S SELF-SET RATE IS INVISIBLE, AND NOTHING HERE CAN MAKE IT VISIBLE.**

An owner setting a price on `/owner/rates`, My Units or Unit Detail goes `OwnerRateController` →
`OwnerRateService::apply()` → an ordinary `rate_calendar` row, with **no proposal and no record of any
kind**. A season replacing that is indistinguishable from a season replacing a base rate.

⚠️ **This is why the wording on screen says "negotiated", not "owner".** An operator who trusts a warning
also trusts its silence, so the disclosure must not imply it checked something it cannot check. **The limit
is asserted by a test**, not merely described — an owner self-set rate is created through the real service
and the disclosure is proven to report **0**.

⚠️ **The false positive** is a coincidental price match: another actor setting exactly the agreed figure
inside the agreed window is attributed to the agreement. It **over**-warns, which is the safe direction, and
it cannot be removed without provenance either.

### How often would it fire?

**Only on nights inside an approved agreement that still hold its price.** In the demo catalogue there are
**no approved proposals**, so it fires **never** — it is not wallpaper. It appears exactly when an operator
is about to replace something somebody negotiated.

### ⚠️ What provenance would cost, if the founder wants the gap closed

A nullable `set_by`/`source` pair on `rate_calendar` (`season` · `owner` · `proposal:{id}` · `manual`),
written by the four writers (`RateAndRestrictionService::setRate` / `setRateWeekendAware`, the season apply,
the owner apply). **~1 migration + 4 writer edits + backfill-as-unknown.** It would close the self-set gap,
remove the false positive, **and it is the same column undo needs** — the two questions have one answer.
**Costed, not built: it changes a money path and is the founder's call.**

---

## 3 · ⚠️ Disclosure is not policy

**`SeasonPrecedence` is byte-unchanged. Nothing refuses. No exception, no reorder.** The wording **tells**:

> *"**4** of those nights carry a rate negotiated with an owner (2026-09-15 to 2026-09-18). Applying replaces
> those prices, and the owner is not told."*

It states a fact and the Apply button is unchanged — the operator is **not** being asked for permission.

### The policy options, with costs — ⚠️ **NONE decided**

| option | cost | what it trades |
|---|---|---|
| **Warn and proceed** *(what this pass built)* | done | Operator informed; owner still not told; agreed rate still replaceable. |
| **Require a second confirmation** on agreed nights | small — one more dialog state | Friction on a real operational action; does not stop anything. |
| **Refuse** — a season cannot cover an agreed night | medium — a precedence exception, a way to see and override it, a story for "the agreement expired" | Protects the owner; makes seasons unusable on owner-managed stock without an override path. |
| **Notify the owner** | ⚠️ needs the notification + email path and a product decision about volume | Answers the *owner's* half, which warning the operator does not. |

⚠️ **"Warn the operator" and "tell the owner" are different answers to the same question.** This pass built
only the first.

---

## 4 · What the preview already knew, and what was added

`SeasonalRateService::preview()` already loaded `$existing` — the current `rate_calendar` rows for the
season's plan across its window, keyed by date — and already asked `SeasonPrecedence::winnerOn()` per night.
**Provenance was the only gap**, exactly as expected.

⚠️ **The disclosure is handed that SAME `$dates` and `$existing` collection.** A second read of the current
prices would be a second source for one fact, and the two figures sit in the same sentence on screen. Pinned
by a source assertion **and** by a mutation that swaps in an empty collection → **RED**.

⚠️ **The two-step is untouched** — save the draft, review the radius, then apply.

---

## 5 · Where it appears

- **The apply dialog**, immediately after the existing *"The prices it replaces are not stored anywhere.
  This cannot be undone."* — the right home, because that sentence is already the honest admission.
- **The preview row**, beside the precedence line, so it is seen before the dialog opens.

⚠️ **The D2 dialog contract is intact** — role, `aria-modal`, a resolving `aria-labelledby`, `tabindex="-1"`,
`x-trap` bound to its own show expression, Escape. `DialogFocusContractTest` green; the added block is a
`<template x-if>` inside the existing dialog, adding no new dialog.

⚠️ **The season EDIT path needs nothing.** Editing is refused on an applied season and only ever saves a
**draft**, which writes no prices — a draft cannot overwrite anything. Applying is where the disclosure
belongs, and that is where it is.

---

## 6 · ⚠️⚠️ What is the owner told? **Nothing — before or after**

Established, not assumed:

| | |
|---|---|
| Notification / email when a season replaces an agreed rate | **none** |
| Owner attention feed | carries **pending proposals** (a decision they must make) — **nothing** about a replaced rate |
| `/owner/rates` | shows the covering season's **name, price and whether it is applied** — ⚠️ **discoverable if they look, but it announces nothing** |
| `owner_ledger` | records `room_revenue` per reservation — **the money that resulted, never the nightly rate**. A lower agreed rate shows up later as lower earnings, with no explanation and no link to a season. |

⚠️ **Reported, not built.** A notification is a product decision and mail is a go-live item. But it belongs
in the ruling: the operator now knows; the owner still does not.

---

## 7 · Nothing was written

A disclosure reads and says. **Proven byte-identically**: `rate_calendar` is unchanged across a preview
(compared as JSON, not by row count), and only the season row itself is added. Applying a season is still
gated on **`rates.manage`**, unchanged.

**Money and every pin unmoved** — see the gates in the report.

---

## 8 · Proof — a REAL agreed rate through the REAL acceptance path

Every agreement is made by `RateProposalService::propose()` then `approve()`, which applies the rate through
the certified writer. **No fixture fakes provenance** — a fixture would prove only that the disclosure can
read a shape nobody creates.

| test | |
|---|---|
| ⚠️ names 7 negotiated nights, one owner, with the exact window | **positive** |
| says nothing where no night is negotiated | **negative — it is not wallpaper** |
| falls silent once the rate is superseded | correct silence |
| counts only **approved** — a pending proposal applied nothing | |
| ⚠️⚠️ counts Fri/Sat when the agreement carried a **weekend uplift** | the closed false negative |
| ⚠️⚠️ reports **0** for an owner's **self-set** rate | **the limit, pinned** |
| writes nothing (byte-identical `rate_calendar`) | |
| reads the same rows as the blast radius | one source |

### Mutations — five, each applied, landed, RED

| # | mutation | landed | |
|---|---|---|---|
| **M1** | ⚠️ **the wrong fix** — count ALL overwritten nights, not agreed ones | ✅ | **RED** |
| **M2** | remove the disclosure entirely | ✅ | **RED** |
| **M3** | drop the weekend reconstruction | ✅ | **RED** |
| **M4** | count proposals in any state, not only approved | ✅ | **RED** |
| **M5** | re-read prices instead of the preview's collection | ✅ | **RED** |

⚠️ **Every assertion was checked to actually run** — the weekend test asserts the €250 Friday row exists
before relying on it, and the writes-nothing test asserts `agreed_nights === 7` before comparing tables, so
neither can pass while checking nothing.

---

## ⚠️ Where this brief was wrong

1. **"if an owner's agreed rate lives in those same rows WITH NO PROVENANCE … a preview would be GUESSING"** —
   half right. A **negotiated** rate has a second record in `rate_proposals` that ties precisely to the
   nights it set, so no guessing is required. An **owner self-set** rate genuinely has none, and that half of
   the brief's fear is exactly the false negative now named and pinned.
2. **"the audit row's `before` is literally `null`"** — correct, and it is why `rate_calendar` alone can
   never answer this.
3. ✅ Correct and load-bearing: that the preview already computes the radius so provenance is the only gap;
   that a false disclosure is worse than none; that the apply dialog's existing sentence is the right home.
