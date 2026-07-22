# Vyrobel — Full System Audit (READ-ONLY)

**Date:** 2026-07-22 · **Method:** live Playwright browser drive against a fresh `migrate:fresh --seed`, real in-app flows exercised on the throwaway demo DB · **Diagnosis only — no code was changed.**
**Environment safety:** `STRIPE_FAKE=true`, `PAYMENTS_GATEWAY=manual`, `PAYOUTS_RAIL=manual`, `MAIL_MAILER=smtp`→Mailpit (local catch). No real charges, payouts, or emails were possible.
**Evidence:** 26 screenshots in [docs/audit/screens/](audit/screens/). Findings cite the screenshot + the suspected fix area (a **pointer**, not a fix).

---

## 0. Resolution status (updated 2026-07-22 — demo-blockers cleared)

The demo-blocking findings and the flagged follow-up have been fixed in a targeted, presentation/seed/test-scoped pass (money/auth/isolation logic untouched):

| Finding | Status | Commit |
|---|---|---|
| **P1-1** · four operator pages unstyled | ✅ **RESOLVED** — wrapped the four controller views in `<x-layouts.operator>` (the document layout the 25 Livewire pages already use). Browser-verified: all four fully styled; 25 Livewire pages unregressed (exactly 1 `<head>` each). `fix-reviews-after.png`, `fix-guest-requests-after.png`, `fix-dashboard-noregress.png`. | `c0ce419` |
| **P2-1** · storefront "0 rooms" for most dates | ✅ **RESOLVED** — demo `rate_calendar` now seeds a rolling `today → +180d` window (was a fixed Sept band). Seed-only; engine untouched. Browser-verified: 20–23 Aug now offers 2 rooms. `fix-storefront-august.png`. | `7cd1550` |
| **P2-2** · dev "expire trial" button on Billing | ✅ **FALSE POSITIVE** — already gated to `local` at the view (`@if app()->environment('local')`) **and** the controller (`abort_unless(local,testing) → 404`). A production operator never sees or reaches it. No change. | — |
| **Caveat** · owner capability at the `/livewire/update` layer | ✅ **CLOSED — gate holds, no gap** — the sensitive owner mutations (payout account, team management) are **controller routes** (gated by `EnsureOwnerCapability` / `EnsurePrimaryOwner`), not Livewire actions. New test `OwnerCapabilityMutationBoundaryTest` proves it two ways: the real HTTP mutation path 403s for every non-permitted sub-user, and **every** owner Livewire component exposes only `render`/`mount` (no mutating action rides `/livewire/update`). | Gate 3 |

Remaining open items are the **non-blocking P3s** (non-today default date; storefront media/landing ratings; signup name-prefill verify; storefront guest-name verify) and the **account-gated tail** (live charge/payout/KYC/OTA/WhatsApp/SMS — deferred by design). **Next is the design-fidelity pass** (§4), out of scope for this targeted fix.

---

## 1. Executive summary

Vyrobel is, structurally, a genuinely deep and honest multi-tenant hotel-management SaaS. Across five apps I drove real browser sessions per role, health-swept **every** GET route, ran the security-critical RBAC/capability matrices, and exercised four **real** end-to-end mutations (guest online check-in, a public storefront booking that created a real reservation through the single inventory write-path, a self-serve signup that atomically provisioned a whole new tenant org, and a trial-expiry that correctly dropped the org behind the paywall). The core architecture — tenancy, RBAC, money, inventory concurrency guarantee, provisioning, billing-state derivation — held up under live exercise, and the honesty rule is visibly respected everywhere (deferred features are badged "Soon" / "Not yet available", never faked).

**One real, previously-invisible defect dominates the report:** four operator screens — **Reviews, Guest requests, Email previews, Email templates** — render **completely unstyled** (raw HTML, giant SVG icons, no CSS) on every visit, including normal nav-clicks. They are the four operator pages rendered by plain controllers whose Blade root is `<x-operator.shell>`, a **body-only** component that (unlike `<x-guest.shell>`) omits the `<!doctype>/<head>/@vite` document wrapper. HTTP status is `200` with no exception and no raw-Blade leak, so every static check and the status-only health sweep sail straight past it — a textbook instance of the project's own meta-lesson: *"Static checks cannot see a rendered page… open the thing in a browser."*

### Counts

| Severity | Count | |
|---|---|---|
| P0 — blocker | 0 | (nothing breaks the whole product) |
| P1 — high | 1 | 4 operator pages unstyled/unusable (a single root cause) |
| P2 — medium | 2 | storefront rate-window gap; dev "expire trial" button in customer UI |
| P3 — low / verify | 4 | non-today default date; storefront media/ratings-on-landing; signup name pre-fill; storefront guest name |
| ✅ Verified working | many | RBAC, owner capability matrix, cross-realm isolation, real booking/checkin/signup/paywall, real analytics |

### Top 10 (most important first)

1. **[P1] Four operator pages render unstyled** — Reviews, Guest requests, Email previews, Email templates. `<x-operator.shell>` used as a controller-view root has no document/`@vite` wrapper. → `op-05*.png`
2. **[P2] Storefront shows "0 rooms available" for most dates** — `rate_calendar` is seeded only for Jul 8–13 and September; August (and the rest of the year) is unpriced, so availability exists but nothing is sellable. → `pub-02-search.png`
3. **[P2] "Test only · expire trial" dev button is on the customer-facing Billing screen** — a dev affordance shipping in the UI. → `sg-06-billing.png`
4. **[✅] Owner capability matrix is correct and money-safe** — accountant & view-only are both 403'd from `payout-method` (CAP_PAYOUT) and `team` (primary-owner-only); self-escalation is structurally closed. (Positive, but load-bearing — keep it.)
5. **[✅] Operator RBAC hides gated nav** — Hotel Manager is 403'd from roles/settings/billing **and** those links are absent from the sidebar (no dead 403 buttons).
6. **[✅] Real booking is genuinely real** — storefront checkout created reservation `R-KHMWQN33` (source=direct, €300), **1 unit assigned via `AssignmentService` + 1 `domain_event`** — the no-double-booking single-write-path held from the public funnel.
7. **[✅] Self-serve signup provisions correctly (#40/#41)** — atomic org + **13 roles** + founder = **Property Owner (never global Super Admin)** + first property + a **real 14-day trial** (`status=trialing`, `stripe_subscription_id=NULL`).
8. **[✅] Paywall gating is correct (#41)** — expiring the trial locked the whole `/app` to Billing; mid-trial keeps full access.
9. **[✅] Analytics are real (#39)** — RevPAR = ADR × occupancy verified live (€6.92 = €128.24 × 5.397%); no fabricated series; honest empty states.
10. **[P3] Operator Calendar & Front desk open on a future date (14 Sep), not today (22 Jul)** — verify intent; an operator expects "today" on load.

### Verdict

- **Demo-ready? — YES (blockers cleared 2026-07-22, see §0).** The four unstyled operator pages are fixed and the storefront rate window is widened; both browser-verified. The full loop — storefront booking → operator ops → owner portal → guest portal → self-serve signup → billing/paywall — is now genuinely impressive, coherent, and honest end-to-end on real data.
- **Customer-ready? — NOT YET.** The unstyled-pages bug is a hard blocker for any customer touching Reviews / Guest requests / Email. Beyond it: the account-gated tail (live Stripe **charge**/payout execution, guest ID/KYC, OTA/WhatsApp/SMS channels) is deferred *by design and honestly*, and seed/rate coverage + the dev "expire trial" button need cleanup. The **foundations are sound and verified** — this is a finishing/hardening gap, not an architectural one.

---

## 2. Findings by severity

### P1 — High

#### P1-1 · Four operator pages render completely unstyled (single root cause)
- **Pages/role:** `/app/reviews`, `/app/guest-requests`, `/app/email-previews`, `/app/email-templates` — all operator roles.
- **Category:** 🐞 Bug / 🎨 Design-fidelity (rendering).
- **What's wrong:** each returns HTTP `200` but the body **starts with `<div class="vy-shell">`** — no `<!doctype>`, `<html>`, `<head>`, or `@vite` stylesheet. The browser gets no CSS, so the page is raw HTML with full-size SVG icons (see `op-05b-reviews-viewport.png`, `op-05c-reviews-navclick.png`). Confirmed broken **even via a normal sidebar nav-click** from a styled page — not just on refresh.
- **Root cause (verified by reading):** these four are the only operator screens rendered by a **plain controller** (`return response()->view('operator.reviews', …)`, etc.) whose Blade **root element is `<x-operator.shell>`**. That component (`resources/views/components/operator/shell.blade.php`) emits **only the in-body chrome** — it contains no `<!doctype>/<head>/@vite` (grep: 0 matches). The other 25 operator screens are Livewire full-page components declaring `#[Layout('components.layouts.operator')]`, which supplies the document `<head>`+`@vite`. The **guest** realm avoids the bug because `<x-guest.shell>` **does** emit `<html><head>` itself; **owner** avoids it because all owner screens are Livewire with `#[Layout]`.
- **Why every gate missed it:** `200` + no exception + no raw-Blade leak → PHPStan/Pint/compile and a status-only health sweep all pass. Only a rendered-page look catches it. (Likely regressed when `<x-operator.shell>` was extracted in Phase 6a, after these pages' original verification.)
- **Suspected fix area (pointer):** wrap the four controller views in the operator document layout — i.e. make them render **inside** `components.layouts.operator` (the same wrapper `#[Layout]` gives the Livewire pages), **or** convert the four screens to Livewire full-page components with `#[Layout('components.layouts.operator')]` to match the other 25, **or** make `<x-operator.shell>` self-wrap the document like `<x-guest.shell>` does (guarding against double-wrapping the Livewire pages). The inconsistency between how operator *Livewire* vs *controller* pages obtain `<head>` is the defect.
- **Screens:** `op-05-reviews.png` (full-page: 34,000px column of icons), `op-05b-reviews-viewport.png`, `op-05c-reviews-navclick.png`.

### P2 — Medium

#### P2-1 · Storefront returns "0 rooms available" for most of the year (seed rate gap)
- **Page/flow:** `/book/seaside-demo` booking engine, anonymous.
- **Category:** 🕳️ Feature-gap (demo data).
- **What's wrong:** searching **10–13 Aug** returns "0 rooms available" (`pub-02-search.png`) even though availability rows exist (Double Room had 2 free units). Cause: **`rate_calendar` has 0 rows for August** — it is seeded only for **Jul 8–13 (6 rows)** and **September (90 rows)**. Availability is open but unpriced, and the engine correctly refuses to sell an unpriced room. The empty state itself is honest ("try different dates or fewer guests"); the problem is that a prospective guest/demo-viewer picking a natural date sees "nothing available."
- **Expected:** a rolling bookable window (e.g. next 90–365 nights) so the storefront looks live year-round.
- **Suspected fix area (pointer):** `DemoSeeder` rate-calendar seeding — publish rates across a rolling forward window instead of two narrow bands. (Not a code defect in the engine; the engine's rate-gated behaviour is correct.)
- **Screens:** `pub-02-search.png` (Aug → empty), `pub-03-search-sep.png` (Sept → 2 rooms, real ratings).

#### P2-2 · Dev-only "Test only · expire trial" button on the customer Billing screen
- **Page/role:** `/app/billing`, any billing-capable operator.
- **Category:** 🧭 UX / dev-artifact.
- **What's wrong:** the Billing page renders a **"Test only · expire trial"** button (`sg-06-billing.png`) that immediately lapses the org's trial (I used it to verify the paywall). It's honestly labelled, but it is a **development affordance visible to customers** — in production a customer could nuke their own trial.
- **Suspected fix area (pointer):** gate the control behind `App::environment('local','testing')` or a config flag (e.g. `config('billing.show_test_tools')`), or remove it before customer launch.
- **Screen:** `sg-06-billing.png`.

### P3 — Low / Verify

#### P3-1 · Operator Calendar & Front desk open on a future date, not today
- **Pages:** `/app/rack` (opens on **14–27 Sep**), `/app/front-desk` (opens on **Mon 14 Sep**) — today is **22 Jul 2026**.
- **Category:** 🧭 UX. **Verify intent** (may be Livewire session-persistence of a previously-viewed date, or a "jump to next activity" default).
- **Expected:** front desk / rack should default to **today** on first load; an operator's first question is "who's arriving *today*."
- **Suspected fix area (pointer):** the date-initialisation in the rack / front-desk components (default to `today()` in the property timezone rather than persisting or seeking the next booking).
- **Screens:** `op-02-rack.png`, `op-04-frontdesk.png`.

#### P3-2 · Storefront landing has no ratings and placeholder room images
- **Page:** `/book/seaside-demo` (no dates).
- **Category:** 🎨 Design-fidelity (expected deferral).
- **What's wrong:** the no-dates landing shows room **name only** (no rating) and **solid-colour placeholder tiles** instead of photos (`pub-01-storefront.png`). Ratings **do** appear once a search runs (Double ★5, Apartment ★4 — `pub-03/04`), and placeholders are honestly flagged "coming soon"; so this is a media/polish gap, not a bug. Note for the styling/media pass: real photos + surfacing the property/room rating on the landing.
- **Screens:** `pub-01-storefront.png`, `pub-04-room.png`.

#### P3-3 · Signup step 2 "Your name" pre-filled with the business name (verify)
- **Page:** `/signup` step 2.
- **Category:** 🐞 Bug? — **could not attribute conclusively.** The founder-name field showed the business name I typed in step 1. It is plausible this was caused by my own earlier mis-targeted test injection (a JS `value` set that hit `name="name"`), **not** a product binding. Flagging to **verify** the founder-name field doesn't share Alpine/`x-model` state with the business-name field.
- **Suspected fix area (pointer):** the signup wizard step components — confirm business-name and founder-name bind to distinct fields.
- **Screen:** `sg-03-step2.png`.

#### P3-4 · New storefront booking's lead-guest name rendered empty (verify)
- **Flow:** storefront checkout created `R-KHMWQN33`; the created lead guest's `full_name` came back empty in a DB spot-check.
- **Category:** 🐞 Bug? — **verify.** May be an encrypted-cast/accessor artefact in the tinker context rather than a real data loss (first/last were submitted).
- **Suspected fix area (pointer):** the public booking service's guest creation + `Guest::full_name` accessor over the encrypted first/last columns.

---

## 3. Per-app rollup

| App | Routes health-swept | Visually verified | Real flow exercised | Result |
|---|---|---|---|---|
| **A. Operator** (`/app`) | 34/34 → `200` | dashboard, rack, insights, front-desk, portfolio, billing, checkout, + the 4 broken pages | RBAC sweep (admin / Hotel Manager) | **4 pages unstyled (P1); rest polished + healthy.** RBAC clean (gated links hidden, forced access 403s). |
| **B. Owner** (`/owner`) | 24/24 → `200` | dashboard | Capability matrix (primary / accountant / view-only) | **Excellent.** Payout & team gates hold for both sub-roles; matches design grid exactly. Owner refused from `/app` (#26). |
| **C. Guest** (`/guest`) | 14/14 → `200` | trips, online check-in | **Real online check-in** (persisted `guest_checked_in_at`) | **Polished + healthy.** Cross-realm isolation verified (guest refused from `/app` & `/owner`). |
| **D. Storefront** (`/book`) | full funnel | storefront, search, room, checkout, confirmation | **Real booking → `R-KHMWQN33`** (unit assigned + domain event) | **Works end-to-end.** Rate-window gap (P2-1) is the only blemish. Honest deferrals throughout. |
| **E. Signup + Billing** | wizard + billing | all 3 wizard steps, portfolio, billing, paywall, checkout | **Real org provisioned + trial + paywall** | **Works end-to-end (#40/#41 verified in DB).** Dev button (P2-2) is the only issue. |

### Security / integrity checks (all ✅)
- **Operator RBAC:** Hotel Manager 403'd from `roles`, `settings`, `billing`, `billing/checkout`; those nav links are **absent** from the sidebar (no dead 403 buttons).
- **Owner capability matrix (route layer):**
  - *Accountant* 403'd from `payout-method`, `team`, `calendar`, `bookings`, `rates`, `add-unit`, `units` — allowed financials/documents/tax/exports. ✔ matches grid.
  - *View-only* 403'd from `payout-method`, `team`, `tax`, `documents`, `rates`, `add-unit` — allowed financials/calendar/bookings/units. ✔ matches grid.
  - The sensitive **CAP_PAYOUT** and primary-owner-only **CAP_TEAM** gates hold for **both** sub-roles → self-escalation structurally closed.
- **Cross-realm isolation:** guest → `/app` and `/owner` both redirect away; owner → `/app` redirects to `/owner` (#26). (A transient `200` seen mid-audit was multi-guard session coexistence in one cookie jar, **not** a leak — re-verified after a clean web-guard logout.)
- **Provisioning (#40):** new org gets all 13 roles; founder holds **Property Owner**, **not** global Super Admin.
- **Billing (#41):** real trial from provisioning (`trialing`, `stripe_subscription_id=NULL`); lapsed → paywall; mid-trial → full access.

> ⚠️ **One caveat worth a targeted test (not verified here):** owner capability gates were confirmed at the **route** layer. Livewire's `/livewire/update` endpoint does not run route middleware (the documented PropertyContext footgun). Phase 7c claims these mutations are independently guarded/"mutation-proven"; this audit verified the **route** layer only. Recommend a direct test that a *view-only* owner cannot drive a payout-method / rate-approval mutation via a crafted Livewire update.

---

## 4. Design-fidelity — page by page (feeds the styling pass)

**Overall:** the design system is strong and **consistent** — a warm cream/terracotta palette, rounded cards, a dark "hero" KPI tile, tidy sidebars, and (critically) **honest status badges everywhere** (`SOON`, `NOT YET AVAILABLE`, `coming soon`, `NOT YET LISTED`). Console was clean (0 errors) on every styled page sampled. Fidelity was verified **in depth on ~18 screens spanning all five apps**; the remaining ~70 screens were **health-verified** (`200`, no exception, no raw-Blade). Below, verified screens are called out; the four broken pages are the exception to the otherwise-high fidelity.

### Operator
- **Dashboard** (`op-01`) — ✅ high fidelity. KPI tiles (occupancy hero, rooms available, arrivals/departures, in-house), today's arrivals, housekeeping counts, and an **honest** "Revenue trend — NOT YET AVAILABLE (no fabricated series)" card. `Custom range`/`Export` badged `SOON`.
- **Calendar / rack** (`op-02`) — ✅ high fidelity. Availability grid, a real reservation pill, colour legend, honest "rack is read-only for now" note. *Opens on a future week (P3-1).*
- **Insights** (`op-03`) — ✅ high fidelity **and real**. Occupancy 5% (17/315 room-nights), ADR €128.24, RevPAR €6.92 (= ADR × occupancy, exact), room-revenue bar chart, channel mix; lead-time/pace badged `SOON`.
- **Front desk** (`op-04`) — ✅ high fidelity. Arrivals/departures/in-house columns, a live **Check-in** action, honest "room moves & on-desk payment are planned; check-in/out are live." *Opens on a future date (P3-1).*
- **Portfolio** (`sg-05`) — ✅ high fidelity (seen on the freshly-provisioned org: real €0.00 empty state, "finish setup to go live").
- **Billing / Checkout** (`sg-06`, `sg-08`) — ✅ high fidelity, honest ("placeholder prices", "card never touches Vyrobel", Stripe hosted handoff). *Dev button (P2-2).*
- **Reviews / Guest requests / Email previews / Email templates** — ❌ **unstyled (P1-1).**

### Owner
- **Dashboard / Overview** (`ow-01`) — ✅ high fidelity. Earnings (€885.60), next-payout tile ("payout method setup is coming soon"), units, honest "Occupancy — Not yet available", and a strong PII note: *"Guest personal details stay private and are never shown on the owner portal"* (surfaces invariant #28 in the UX). Action centre/quick-actions badged `SOON`.
- Other 23 owner routes — health-verified `200`, all Livewire (`#[Layout]`), so structurally not exposed to the P1 pattern.

### Guest
- **Trips** (`gu-01`) — ✅ high fidelity. Upcoming/past grouping, per-booking payment state ("Paid in full" / "€184.50 due"), confirmation codes, "★ Reviewed — thank you" on the past stay.
- **Online check-in** (`gu-02`, `gu-03`) — ✅ high fidelity. Details recap, honest "ID check — coming soon", and a **real** "Confirm & check in" → success state.

### Public storefront
- **Landing** (`pub-01`) — ✅ high fidelity; placeholder room tiles + no landing ratings (P3-2).
- **Search results** (`pub-03`) — ✅ high fidelity; real availability, per-room ratings (★5 / ★4), "room descriptions coming soon".
- **Room detail** (`pub-04`) — ✅ high fidelity; price/availability, "Save to wishlist", **real guest review + operator response rendered**, amenities "NOT YET LISTED", "1 left for your dates".
- **Checkout / Confirmation** (`pub-05`, `pub-06`) — ✅ high fidelity; "Pay on arrival — card coming soon, no card collected", clean confirmation with reference + total.

### Signup
- **Steps 1–3** (`sg-01`, `sg-03`, `sg-04`) — ✅ high fidelity; 3-step progress, honest provisioning copy, `SOON` on channels. *Step-2 name pre-fill to verify (P3-3).*

---

## 5. Feature-completeness vs prototype / GAP-ANALYSIS

- **Built and working (verified live):** operator PMS ops (dashboard/rack/front-desk/reservations/guests/search/housekeeping/maintenance/units), multi-property context + portfolio, real analytics (Insights/Reports), owner portal + owner-team sub-tenancy with a correct capability matrix, guest portal (trips/reservation/manage/check-in/payments/wallet/messages/wishlist/reviews), public storefront + booking engine (real reservation creation via the single inventory write-path), reviews across all surfaces, notifications, self-serve signup with atomic tenant provisioning, and subscription billing with a real trial + paywall/feature gating. The honesty rule is respected throughout.
- **Deferred *honestly* (account-gated, correctly not faked):** live Stripe **charge** (guest card / Apple Pay), owner payout **execution** (Connect + KYC), guest **ID verification**, **OTA/WhatsApp/SMS** inbox channels, live **channel sync**. All present as "Soon"/"coming soon" experience shells — no fake charge/send/verify observed. Production email (Postmark) and live-Stripe wiring exist test-mode-first per `docs/STRIPE-PRODUCTION.md` / `EMAIL-PRODUCTION.md`.
- **Gaps surfaced by this audit (not in the deferred list):**
  - The **four unstyled operator pages** (P1-1) — a regression, not a deferral.
  - **Storefront rate-window** demo-data gap (P2-1).
  - **Media**: room photos are placeholders across the storefront (P3-2) — expected, flag for the media pass.

---

## 6. Prioritized fix plan

1. **P1-1 — un-break the four operator pages (do first; blocks demo & customers).**
   Wrap `operator.reviews`, `operator.guest-requests`, `operator.email-previews`, `operator.email-templates` in the `components.layouts.operator` document layout (or convert them to Livewire `#[Layout('components.layouts.operator')]` full-page components like the other 25; or make `<x-operator.shell>` self-wrap the document as `<x-guest.shell>` does — without double-wrapping the Livewire pages). Then **open all four in a browser** (not just check `200`) to confirm CSS loads. Add a rendered-page assertion (assert the response contains `@vite`/`<head>`) so a status-only check can't miss a recurrence.
2. **P2-1 — widen the demo rate window.** In `DemoSeeder`, seed `rate_calendar` across a rolling forward window (≥90 nights) so the storefront looks live year-round. (Or, for an imminent demo, drive the storefront with September dates.)
3. **P2-2 — hide the "expire trial" control** behind `App::environment()` / a config flag, or remove it.
4. **P3-1 — default rack/front-desk to today** (property timezone); verify it isn't unintended session persistence.
5. **P3-3 / P3-4 — verify** the signup founder-name binding and the storefront lead-guest name capture.
6. **P3-2 — media/polish pass:** real room photos; surface property/room ratings on the storefront landing.
7. **Hardening (recommended):** add the direct Livewire-update test that a *view-only* owner cannot drive a payout-method / rate-approval mutation (route gates verified; the `/livewire/update` layer was not exercised here).

---

## 7. Coverage checklist & honesty note

**Exhaustive (every route):** HTTP health of all operator (34), owner (24), guest (14), storefront, and signup GET routes — all `200`, no exceptions, no raw-Blade leaks (the four P1 pages are `200`-but-unstyled). RBAC route-gating sweeps for operator Hotel Manager and owner accountant + view-only. Cross-realm isolation for guest/owner. A full-surface doctype sweep that **found** the four unstyled pages.

**Deep (sampled, ~18 screens):** visual design-fidelity + console across all five apps; the four real mutations (guest check-in, storefront booking, org signup, trial→paywall) verified in the **database** (reservation + unit + domain_event; org + 13 roles + founder role + trial row; persisted check-in timestamp).

**Honestly NOT done:** pixel-diffing against `docs/design/raw/*.html` (compared against the live design system's evident intent, not overlaid); per-screen visual pass on the ~70 health-only screens (owner detail/money screens, guest payments/wallet/messages/wishlist, operator rates/housekeeping/maintenance/roles/settings/schedule interiors) — these returned `200`/clean and, being Livewire `#[Layout]` or full-doc `<x-guest.shell>` pages, are **not** exposed to the P1 pattern, but were not each eyeballed; the operator **Accountant** and untested seeded-absent roles (Front Desk, Housekeeping, etc.) were not individually driven; the `/livewire/update` mutation layer for owner capabilities. No destructive/irreversible or real external actions were taken. The new demo org "Audit Test Lodgings" and reservation `R-KHMWQN33` were created on the throwaway seed DB and left in place (the primary demo org is untouched; the audit expired **only** the new org's trial).
