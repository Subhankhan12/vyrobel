# Owner Reviews — exact-match audit (PHASE 1, read-only)

**Date:** 2026-07-30 · **Page:** `/owner/reviews` (`OwnerReviews` + `reviews.blade.php`) vs the decoded wireframe `docs/design/raw/Owner Reviews.html`.
**Method:** decoded the dc-runtime bundle (JSON.parse the `__bundler/template` script → the `<x-dc>` body + the `Component` logic) + read the real reviews backend. **Nothing changed.**

---

## 0. ⚠️ THE PIVOTAL FINDING — the "agency-reply / response system" is ALREADY REAL

The prompt's central investigate ("is there a real review-response model? if not, BUILD it") resolves to: **it already exists and is production-shape.** Evidence:

- **`Review` model + schema** already carry `operator_response` (text) + `responded_at` (timestamp) — the operator's public response per review (Phase 5 · SG3). (`app/Models/Review.php`, the reviews migration.)
- **`ReviewService::respond($review, $response)`** writes `operator_response` + `responded_at` and **audits** it (`review.responded`). (`app/Services/ReviewService.php:99`.)
- **The operator posts responses IN-APP** via `POST /reviews/{review}/respond` → `OperatorReviewsController::respond`, gated **`can:reviews.manage`**, `owned()`-scoped, controller-routed + audited (#20). (`routes/web.php:677`.)
- **The owner already sees them read-only** — `ReviewList::map` surfaces `'response' => operator_response` with the **#28 byline** (`GuestName::reviewByline` = first name + last initial). (`app/Support/Reviews/ReviewList.php:53`.)

So the operator→owner **agency-reply flow is built, real, gated, audited, #28-safe.** What is genuinely MISSING is not the response *model* but the **response ANALYTICS + the page's aggregates** (response rate, avg time, distribution, by-villa, categories) and the exact-design surface. This shrinks GATE 2 dramatically and re-frames the whole build.

---

## 1. Section-by-section classification (REAL / BUILD / NOT-BUILDABLE)

| # | Section (wireframe) | Real backing | Classification |
|---|---|---|---|
| 1 | **Header** — "Reviews & reputation · …Seaside Villas replies for you." | The managing operator/agency responds (real). "Seaside Villas" = the demo operator name. | ✅ **REAL** — real copy; use the managing operator's real name (or "your management team"), not a fabricated agency. |
| 2a | **Overall rating** (★ + "N reviews · all villas") | `ReviewRating` (published+public, DERIVED avg + count) — but currently per-unit/room-type only. | ✅ **REAL** (small BUILD — aggregate `ReviewRating` across the owner's unit ids). |
| 2b | **Response rate** (% + "replies within ~6h") | `operator_response` + `responded_at` exist → **rate = responded ÷ total** is real; **avg time = `responded_at` − `published_at`** is real (both timestamps exist). | 🔨 **BUILD (real)** — add response-rate + avg-response-time to a shared review-summary. The "~6 hours" is real **iff** the timestamps support it, else honest-omit the time (never a fabricated SLA). |
| 2c | **Last 30 days** (count + avg) | Reviews `published_at` within 30d + their avg. | 🔨 **BUILD (real)** — a small derive. |
| 3 | **By villa** ({{name}}/{{count}}/{{rating}}/{{trend}}) | Per-unit `ReviewRating` over the owner's OWN units (#28, owner-scoped). Trend ▲/▼ = a rating delta vs the prior period. | 🔨 **BUILD (real)** — per-unit aggregate; **trend = real computed delta** (this-period avg vs prior) or honest-omit if a period can't be formed. |
| 4a | **Recent reviews** (initials/guest/stars/source/when/villa/text) | `ReviewList::forUnits` (real, #28 byline). Needs: initials, **source**, relative "when", villa (unit) name, per-villa filter key. | ✅ **REAL** (extend the mapper). Guest is **initial-only** (#28). |
| 4b | **"Seaside Villas replied {response}"** | `operator_response` (real, already surfaced). | ✅ **REAL**. |
| 4c | **"Reply being drafted by the team"** (pending) | NO explicit "drafting" flag. A published review with no `operator_response` is "**awaiting a reply**" — real state; "being drafted" is a specific claim without backing. | ⚠️ **FOUNDER DECISION** — honest "Awaiting a reply" (real state; **recommended**) **vs** BUILD a real `response_status` drafting flag the operator sets. Never a fabricated "being drafted". |
| 5 | **Rating breakdown** (5★–1★ counts) | Group published reviews by `rating`. | 🔨 **BUILD (real)** — a distribution derive; reconciles to the review count. |
| 6a | **By category** (Cleanliness/Communication/Location/Value scores) | ⚠️ **NOT stored** — the review has ONE overall `rating` + `tags` (chips); no per-category sub-ratings (the guest form collects rating+tags+body only). | ⚠️ **FOUNDER DECISION** — **BUILD** real per-category sub-ratings (schema + guest review form + demo backfill) **vs** honest-omit the section. Deriving categories from `tags` would be **fabrication (#39) — refused**. |
| 6b | **"Value…the proposed rate changes factor this in"** → link | Wave-C **rate-proposals are REAL** (`RateProposal`; owner approves via the attention feed); the Rates page is `owner.rates` (the wireframe's link target). | ✅ **REAL LINK** (not a refusal) — link the Value observation to `owner.rates` (+ optionally a real pending-rate-proposal count). |
| 7a | **"collected from Airbnb, Booking.com and direct stays"** | Reviews are **in-app** (a guest reviews a completed stay). A review's source = its **reservation's `source`** (direct/ota/walk-in/…). **OTA-native review INGEST** (pulling Airbnb/Booking.com reviews) is **not built**. | 🔒 **ACCOUNT-GATED** — direct/completed-stay reviews are real; the source badge shows the **real reservation source** (Direct / OTA-generic); the **Airbnb/Booking.com brand split + OTA review ingest** are honest-gated (need the OTA accounts, like OTA sync). |
| 7b | **"replies to every one within 24 hours"** (SLA) | Responses are real; a **24h SLA** is a promise. | ⚠️ **SOFTEN to honest** — show the **real** response rate + (if supported) avg time, and an honest true claim ("your management team replies to your published reviews"), NOT a fabricated 24h SLA unless the real avg time supports it. |
| 8 | **Filters** (All villas / per-villa) | Real client-side filter over the owner's real per-unit reviews. | ✅ **REAL** (Alpine, per-unit key). |
| 9 | **Motion/animations, warm-glass, Sora/Inter, `vyFadeUp`** | Shared owner chrome (`x-owner.shell`, `--vy-*`). | ✅ **REUSE**. |

---

## 2. Reuse map (anti-drift — no new parallel structures)

- **`x-owner.shell` + `--vy-*` tokens** — the warm-glass frame, sidebar, top bar (bell + account menu), `vyFadeUp`.
- **`ReviewRating`** (the ONE derived-rating rule) — extend a shared **`OwnerReviewSummary`** on top of it (overall + by-villa + distribution + last-30 + response-rate/time), owner-scoped over the owner's unit ids. Never a stored/fabricated average (#39).
- **`ReviewList`** (the shared feed mapper, #28 byline) — extend the mapper (initials/source/when/villa/filter-key) so the owner + storefront stay consistent.
- **`operator_response` / `ReviewService::respond` / the operator route** — the REAL response system, surfaced read-only.
- **`GuestName::reviewByline`** — the #28 initial-only byline (already used).
- **`owner.rates`** — the real Value → rate-changes link (Wave-C rate-proposals surface in the attention feed).

---

## 3. ⚠️ FOUNDER DECISIONS (these shape GATE 2)

1. **Category sub-ratings — BUILD or honest-omit?** The review model has **no per-category ratings**. To make "By category" real I'd add `rating_cleanliness / rating_communication / rating_location / rating_value` (tinyint 1–5, nullable) to `reviews`, collect them in the **guest review form**, average them in the summary, and backfill the demo. *Recommendation (per the "build what's missing" mandate): **BUILD** — it's contained, but it does touch the guest review form.* Alternative: honest-omit the By-category card (no fabricated scores). **Deriving from `tags` is refused (#39).**
2. **Pending state — honest "Awaiting a reply" or a real drafting flag?** *Recommendation: honest **"Awaiting a reply"*** for a published review with no response yet (a real state), rather than a fabricated "being drafted". Optional: a real `response_status` (`none`/`drafting`/`responded`) the operator sets — a small extra build if you want the exact wireframe wording.

Everything else (overall/by-villa/distribution/last-30/**response rate**/**avg time**/source/OTA-gating/rate-link) is **BUILD-real or honest with no decision needed.**

---

## 4. Build plan (gated)

- **⛔ GATE 1 — the exact-design page on real reviews + aggregates + the real response system + the rate link.** Rebuild `reviews.blade` to the wireframe EXACTLY (hero 3 KPIs, by-villa, recent-reviews feed + filters, rating breakdown, by-category *shell*, handled-for-you), shared chrome + `--vy-*`. Wire the REAL parts: overall rating + last-30 + by-villa + distribution + **response rate + avg time** (new shared `OwnerReviewSummary`), the recent-reviews feed (extended `ReviewList`, #28 byline, real source, filters), the operator responses **read-only** ("replied {response}"), the honest **pending "Awaiting a reply"**, the **Value → `owner.rates`** link, and the honest **source/OTA-gated** "Handled for you" (real responses; OTA ingest honest-gated; no fabricated 24h SLA). By-category renders per the decision (real if built, else honest-omit). 🔒 #28 owner-scoped + initial-only · #39 no fabricated rating/rate/SLA. Suite green + tests + browser-verify. Per-gate commit.
- **⛔ GATE 2 — category ratings (if approved) + pending flag (if approved) + consolidate.** If approved: add the per-category columns + the guest-form fields + the summary averages + the demo backfill, and wire the real By-category scores + the real Value observation. Consolidate docs (this audit → resolved, `DECISIONS.md`, `DIVERGENCE-REGISTER.md` Reviews → exact-match + the response analytics, `PROJECT-STATE.md`, invariant). Browser-verify the operator-responds→owner-reads flow + the rate link. Suite green; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean. Per-gate commit + push.

---

## 5. Guardrails held throughout
🔒 **#28** owner-scoped (the owner's OWN unit reviews only) + guest **initial-only** byline (never contact PII) · **#20** the operator response-write is the existing controller route (`reviews.respond`, `can:reviews.manage`, audited) — the owner surface is **read-only** · **#39** every rating/aggregate is **DERIVED** from real published reviews (never stored/fabricated); the response rate/time are real (or honest-omit); the OTA ingest + brand split are **honest-gated**; no fabricated 24h SLA · **rate link is REAL** (Wave-C rate-proposals, not a refusal).
