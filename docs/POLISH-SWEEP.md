# Vyrobel — Comprehensive Polish Sweep

**Date:** 2026-07-26 · **Type:** READ-ONLY diagnosis (no code changed) · **Scope:** every page, every realm, all four polish dimensions, side-by-side with the `docs/design/raw/` prototype, respecting `docs/design/DIVERGENCE-REGISTER.md`.

This sweep hunts the **fine polish layer** the design-fidelity pass (which classified every page KIND-1/2/3 and drove it to at-parity / intentional-divergence) did not catalogue: exact tokens, hover/focus/transition states, count-up/loading micro-interactions, and genuine interaction bugs (a page can look right and behave wrong — the notification bell was exactly that). **Only GENUINE deviations are flagged.** Anything the register already recorded as KIND-2-fabricated (honesty rule / #39), a security refusal (#28/#30), account-gated (#45/#41/Channex/SMS/KYC), or a recorded KIND-3 (needs-JS/data-shape) is recorded as an **intentional divergence** in the appendix, **not a gap**.

> **✅ POLISH BATCH 1 COMPLETE (2026-07-26)** — the missing-tokens/classes cluster (**D1 `--vy-wash` · D2 `.vy-pill.is-on` · D3 `--vy-shadow-dark` · D4 `.vy-card--accent` · D5 `.vy-field-label` · D6 bare `.vy-flash`**) is **RESOLVED**: each was defined once in the shared design system (`layouts/operator.blade.php`, `layouts/owner.blade.php`, `components/guest/shell.blade.php`), so every referencing element across all four realms now renders. Presentation-only; suite green, PHPStan L5 + Pint clean; behaviour/logic/money/auth/isolation untouched. Browser-verified (guest chat bubble now has its cream fill; the operator active range pill shows its selected state; owner form labels styled; dark-panel shadows + accent cards + bare flash resolved). Values: `--vy-shadow-dark` is brief-exact (`0 10px 30px rgba(60,30,10,.28)`); `--vy-wash` = `#F6EBE2` (the value the app already rendered everywhere via fallback — only the guest pages lacked the fallback); the rest mirror the app's existing canonical definitions. **Remaining batches (2–6) are unaffected.**

> **✅ POLISH BATCH 2 COMPLETE (2026-07-26)** — the **hover finding (M2)** is **RESOLVED**: a shared **`.vy-card--tappable`** (lift `translateY(-2px)` + elevated shadow `0 18px 44px rgba(150,90,50,.18)` — prototype-matched) defined once in all four design systems, plus a guest **`.vy-row--tappable`** (warm tint) and a scoped **`a.vy-owner-row:hover`**. Applied **opt-in to interactive tap-targets only** — guest trip cards, storefront room cards, owner unit cards, operator property cards, guest message rows, operator owner rows — so the portal/storefront/grids stop feeling inert while **static cards get no misleading hover** (the "List a new property · Soon" placeholder and the Reservation-detail trip-card header are deliberately excluded; browser-verified 0 tappable there). Presentation-only; suite green, PHPStan L5 + Pint clean. Real hover verified firing on guest + operator (translateY(-2px) + shadow); owner computed-confirmed; public uses the byte-identical rule.

> **✅ POLISH BATCH 3 COMPLETE (2026-07-26)** — the **dark-panel swaps + tokenize findings (D7, D8, D9)** are **RESOLVED**: the owner **Booking-Detail NET-TO-YOU** rail and the Tax-Center **"About your tax"** panel now render via the **shared `.vy-card--dark`** treatment (grad-dark bg + white text + `--vy-shadow-dark`, prototype-matched) — reused, not a one-off; the held-payout banner gained its **warning icon** and the About panel a **info icon**; and the flagged pages were **tokenized** (public Room exact-match hexes → tokens with zero visual change; guest pay-error + Schedule error-red → `--vy-accent`; Schedule wash → `--vy-wash`). **Content untouched** (the net €442.80 + tax copy — #17/#39 intact). Browser-verified before/after: the two panels now render dark with white figures + icons, matching the prototype; the tokenized pages render on the `--vy-*` tokens with no regression (the roster's bespoke leave/conflict status tints, which have no exact palette token, were left as documented semantic colours). Presentation-only; suite green, PHPStan L5 + Pint clean.

> **✅ POLISH BATCH 4 COMPLETE (2026-07-26)** — the **interaction findings** are **RESOLVED**. **GATE 1 (correctness):** the **double-submit-disable (I3)** — a framework-free guard (`partials/submit-guard` + `<x-busy-btn>` + `data-guard-submit`; the guest portal + public storefront load no Alpine) disables the button + swaps its label on the first submit and blocks the second, on public checkout, signup, guest Pay-by-card, and operator record-payment/add-charge/move/refund. **Browser-PROVEN fires-once** (guest-pay → "Redirecting…", signup → "Creating your account…", record-payment → "Recording…"; 2nd/3rd submit blocked) + confirm-cancel-safe; **server-side idempotency/atomicity UNTOUCHED** (defense-in-depth) — flagged `add-charge` as lacking a server nonce. **GATE 2 (interaction/consistency):** **Account-in-shell (I1)** — the guest Account page now renders in the portal shell with the nav (was a stranded 400px auth card); **Settle-stub (I2)** — the "Settle · Soon" stub → a live "Pay your balance →" link to the Payments hub (one honest truth); **auth uniformity (D14, partial)** — onboarding + invite-invalid gained the family's button-lift + card fade + drift blob. Presentation/interaction-only; suite green, PHPStan L5 + Pint clean; no money/auth/isolation logic changed. **Deferred (noted):** the signup step cross-fade (I4) + the set-password strength meter (a feature-add, not a uniformity bug) + the fuller guest-portal loading/skeleton states (M3) + the remaining portal focus rings (M5) — later micro-pass.

> **✅ POLISH BATCH 5 COMPLETE (2026-07-26) — the micro-interaction "feel" pass.** **STEP 1 (M1 count-up):** ONE shared parse-from-text count-up (`partials/count-up`, 800ms cubic ease-out) consolidates the 4 duplicated owner `@script` blocks and animates every KPI/hero number across operator + owner — **landing on the EXACT server-rendered value** (money to the cent #17; no fabrication #39), skipping deferred/non-numeric/zero, graceful without JS. Browser-verified (operator Insights €46.97→€116.84 exact; owner hero €337.88→€885.60 exact). **STEP 2 (M3 skeletons) — resolved HONESTLY, no fake skeletons:** the app is fully server-rendered (no `wire:loading`/`#[Lazy]`/`wire:navigate`), so there is no genuine in-page async gap; nav loading is the NProgress bar, submits the Batch-4 busy state, and the `.vy-skeleton` deferred placeholders stay static (a shimmer would falsely imply loading, #39). **STEP 3 (D10 charts) — assessed, deferred:** most charts already use the shared `<x-charts.bars>`; the rest aren't clean drop-ins (an interactive earnings chart + per-bar-label differences) → a focused P3 design follow-up. Presentation-only; suite green, PHPStan L5 + Pint clean; the real figures/data UNCHANGED.

### How this was produced (coverage, honestly)
- **Prototype side:** the shared prototype design-system's micro-interaction vocabulary was extracted from the rendered prototype (`php -S 127.0.0.1:8899`) via `document.styleSheets` — keyframes, hover/focus/transition rules, and the count-up JS. The per-page **structural** prototype→app comparison is the DIVERGENCE-REGISTER (76 page-rows), treated as established ground truth and built upon here.
- **App side:** a full code inventory of **all ~80 app views + the four shell/layout design systems** (operator 908-line, owner 418-line, guest shell + auth layout, public shell) — every page's components, hover/focus/transition/animation, empty/loading state, interaction wiring, and undefined-token/class usage.
- **Browser-grounded:** the shared micro-vocabulary diff + the three highest-impact NEW findings (the `--vy-wash` bug on the guest chat, the Account-page-drops-nav, the count-up absence) were confirmed live on the fresh seed across operator / owner / guest logins.
- **Not claimed:** I did not re-render all 80 prototype pages pixel-by-pixel (the register already did that structural pass); findings are grounded in the shared-system diff + the full app-side inventory + the register + targeted live confirmation.

---

## 1 · Executive summary

**26 genuine polish findings.** None are behaviour/logic/money/auth/isolation issues; none are fabricated-content or security refusals (those are correctly divergent). The overwhelming majority are **presentation-only** and cluster into a handful of cheap, high-leverage batches.

**By dimension:** Interaction/behaviour **4** · Design-fidelity **15** · Feature-depth **1** (genuine buildable; the rest are honestly-deferred and excluded) · Micro-interaction **6**.

**By priority:** **P1 · 1** · **P2 · 10** · **P3 · 15**.

**By effort:** XS **9** · S **11** · M **6**.

### The single biggest signal
A cluster of **undefined CSS tokens/classes that silently degrade specific elements** — the app *uses* `--vy-wash`, `--vy-shadow-dark`, `.vy-card--accent`, `.vy-pill.is-on`, `.vy-field-label` and a bare `.vy-flash`, but **never defines them**, so those elements fall back to transparent/plain/unstyled. These are the highest impact-per-effort fixes in the whole app (all XS CSS, exactly the class of the notification square-tile/unread-wash deltas). Define them once and ~10 findings across all four realms clear at once.

### Top 15 — polish these first (highest visible impact)
| # | Finding | Realm · where | Dim | Pri | Effort |
|---|---|---|---|---|---|
| 1 | **`--vy-wash` undefined → transparent backgrounds.** Guest chat **inbound bubbles render as naked text** (verified), + arrival door-code/lock chips, check-in ID icon, review tag-chips, owner reviews/team chips | Guest + Owner | Design | **P1** | XS |
| 2 | ✅ **DONE (Batch 2)** — **No hover on card/row tap targets.** The whole guest portal (trip cards, payment rows, thread rows, saved rows) + public storefront room cards feel inert; several operator card-grids/tables + owner unit/review/team cards too → shared `.vy-card--tappable` lift added to the interactive tap-targets | Guest·Public·Op·Owner | Micro | **P2** | S |
| 3 | **`.vy-pill.is-on` undefined** → the active date-range pill on operator **Insights + Reports has no selected-state highlight** (can't tell which range is active) | Operator | Design | **P2** | XS |
| 4 | ✅ **DONE (B4)** — **Guest Account page drops the portal nav** — renders in a 400px auth card, no Trips/Payments/… nav; the guest is stranded (verified) | Guest | Interaction | **P2** | S |
| 5 | **`--vy-shadow-dark` undefined** → the dark "Tonight"/feature panels & KPI tiles render with **no drop shadow** (Dashboard, Reservation-detail, Maintenance-detail, Owner-detail, Payout-run, Guest-profile) | Operator | Design | **P2** | XS |
| 6 | **`.vy-field` / `.vy-field-label` undefined in the owner system** → form labels render **unstyled** on block-calendar, payout-method, tax-center, rates, add-unit | Owner | Design | **P2** | XS |
| 7 | **No busy/disable feedback on slow, state-changing submits** — public Checkout "Confirm" (writes inventory), signup "Create my account" (provisions an org), guest "Pay by card" (Stripe redirect) → double-submit risk, no progress | Public·Guest | Interaction | **P2** | S |
| 8 | **`.vy-card--accent` undefined** → operator "Record a payment", "Actions", "Edit profile" cards lose their accent and render plain | Operator | Design | **P2** | XS |
| 9 | ✅ **DONE (B4)** — **Guest "Settle your stay · Soon" stub contradicts the live "Pay by card"** Payments hub for the same booking — two surfaces disagree | Guest | Interaction | **P2** | S |
| 10 | **Bare `.vy-flash` renders unstyled** (no bg/tone) on operator billing, billing-checkout, insights, portfolio notices | Operator | Design | **P2** | XS |
| 11 | ✅ **DONE (B3)** — **Owner Booking-Detail NET-TO-YOU rail is a light card**; prototype (+ owner dashboard/payouts) uses a **dark panel** → swapped to the shared `.vy-card--dark` | Owner | Design | **P2** | S |
| 12 | ✅ **DONE (B5)** — **KPI/stat numbers don't count-up** — the prototype animates them (800ms ease-out); the app renders final statically → ONE shared count-up (lands on the exact real figure) now animates every KPI/hero across operator + owner | All | Micro | P3 | M |
| 13 | ✅ **DONE (B3)** — **Hardcoded hex / off-token radius** — operator Schedule (worst), public Room (`border-radius:22px/9px`), guest pay-error, owner error-hex → tokenized (exact-match hexes + error-red→accent; bespoke roster status tints kept) | Op·Public·Guest·Owner | Design | P3 | S–M |
| 14 | **No loading/skeleton states anywhere**; `vyShimmer` keyframe is defined in both operator + owner but **never wired** (skeletons are static) | All | Micro | P3 | M |
| 15 | ✅ **DONE (B3)** — **Owner Tax-Center "About your tax" is a light card** (prototype: dark panel) + the held-payout banner omits the warning icon → dark panel + info/warning icons added | Owner | Design | P3 | S |

### How close to pixel-and-behaviour-perfect is the app, really?
**Structurally and behaviourally, very close — ~90%+.** The design pass already drove all four gates to at-parity/intentional-divergence, and the shared micro-vocabulary largely *matches* the prototype: `vyFadeUp` card entrances, the `vyDrift` ambient blob, list-table row hover, button/pill/input transitions, and a reduced-motion floor are all present; the app even **exceeds** the prototype on focus rings (the prototype ships **zero** `:focus` rules — so "missing focus rings" is *not* a prototype deviation, it's an internal-consistency/a11y nicety). What remains is a **thin polish skin**: (a) the undefined-token cluster (cheap, high-impact), (b) hover on card/grid tap targets (esp. the guest portal + storefront), (c) the count-up + loading micro-interactions the prototype has, and (d) a few real interaction inconsistencies (Account nav, Settle stub). Everything is honest — no fabricated fill, no security/isolation compromise. The last ~10% is almost entirely a **batch of quick CSS wins** plus one micro-interaction pass.

---

## 2 · Findings by dimension

Each finding: **WHERE · WHAT'S OFF · PROTOTYPE SAYS · BUILDABLE? · EFFORT/PRIORITY.** (✔ = browser-verified this sweep.)

### 2A · INTERACTION / BEHAVIOUR

**I1 — Guest Account page abandons the portal shell/nav ✔** · P2 · S · ✅ **DONE (Batch 4)** — the Account page now renders in `<x-guest.shell active="account">` (portal nav present, 760px, "Account" active) instead of the narrow 400px auth card; shared `.vy-field`/`.vy-label`/`.vy-input`(+focus)/`.vy-error`/`.vy-link` added to the guest shell so its forms render styled. Browser-verified.
- WHERE: Guest · `/guest/account` (`resources/views/guest/account.blade.php` renders the **auth layout**, not `components/guest/shell.blade.php`).
- WHAT'S OFF: every other portal destination uses the full shell with the top nav (Trips/Payments/Wallet/Messages/Saved/Account); Account — *itself a nav item* — renders in the 400px auth card with **no nav** (verified: 0 nav items, 400px main). A guest on Account can only leave via a single footer link.
- PROTOTYPE SAYS: Account is a normal portal page inside the same chrome as its siblings.
- BUILDABLE? Presentation — wrap the account view in `<x-guest.shell active="account">`.

**I2 — Guest "Settle your stay · Soon" stub contradicts the live "Pay by card"** · P2 · S · ✅ **DONE (Batch 4)** — the "Settle · Soon" stub on the Reservation + Receipt pages is replaced by a live **"Pay your balance →"** link to the Payments hub (where card payment is wired) — one honest truth about paying that balance. Browser-verified (no "Soon", links to `/guest/payments`).
- WHERE: Guest · `guest/reservation.blade.php` L47 + `guest/receipt.blade.php` L35 show a disabled **"Settle · Soon"**, while `guest/payments.blade.php` L46 ships a **live "Pay by card"** (real `guest.payments.pay` → Stripe Checkout) for the *same* booking.
- WHAT'S OFF: two surfaces tell the guest two different truths about whether they can pay now.
- PROTOTYPE SAYS: a single, consistent "settle your balance" affordance.
- BUILDABLE? Presentation/interaction — point the stub at the Payments hub (or remove it). (The live charge itself stays account-gated #45; the *inconsistency* is the finding.)

**I3 — No busy/disable feedback on slow, state-changing submits** · P2 · S · ✅ **DONE (Batch 4 · GATE 1 — the correctness fix)** — a framework-free double-submit guard (`partials/submit-guard` + `<x-busy-btn>` + `data-guard-submit`) disables the button + swaps its label on the first submit and blocks the second; applied to public checkout, signup, guest Pay-by-card, operator record-payment/add-charge/move/refund. Browser-**proven fires-once** (guest-pay, signup, record-payment) + confirm-cancel-safe; server-side idempotency/atomicity UNTOUCHED (defense-in-depth). **Flagged:** `add-charge` (postCharge) has no server idempotency — a server nonce is a warranted follow-up.
- WHERE: Public Checkout "Confirm reservation" (`booking/checkout.blade.php` L61 — writes inventory via `AssignmentService`+`FolioService`), Signup "Create my account" (`auth/onboarding.blade.php` — atomic tenant provisioning), Guest "Pay by card" (`guest/payments.blade.php` — external Stripe redirect leg).
- WHAT'S OFF: the slowest, most consequential actions submit with **no disabled/spinner/progress**; a double-click is unguarded on the UI, and the user sees nothing happen while a booking/org write runs.
- PROTOTYPE SAYS: interactive submit affordances (the prototype is a React app with per-action state).
- BUILDABLE? A real (small) interaction change — disable-on-submit + inline spinner/label swap.

**I4 — Signup wizard step transition is a hard cut** · P3 · S
- WHERE: Public · `auth/onboarding.blade.php` (vanilla-JS `show()` toggles `display:none/block` between steps).
- WHAT'S OFF: steps swap instantly with no cross-fade, jarring against the polished card entry.
- PROTOTYPE SAYS: smooth step progression.
- BUILDABLE? Presentation — a short fade/slide between `[data-step]` sections.

*(The notification **bell → panel** interaction bug — the exemplar for this whole sweep — was already found & fixed 2026-07-26; not re-counted.)*

### 2B · DESIGN-FIDELITY

**D1 — `--vy-wash` undefined → transparent backgrounds ✔** · **P1** · XS · ✅ **DONE (Batch 1)** — defined `--vy-wash: #F6EBE2` in the guest shell + owner + operator token blocks; the guest chat inbound bubble now renders its cream fill (verified).
- WHERE: cross-realm. Guest: `message-thread` inbound bubbles (verified: naked text, no fill), `arrival` door-code chip + lock icon, `checkin` ID icon, `review` tag chips. Owner: `reviews` + `team` chips (hex-fallback). The token is **used in 5 places across 4 guest files + 2 owner files but never declared** (`var(--vy-wash)` resolves to `rgba(0,0,0,0)`, verified).
- WHAT'S OFF: the intended soft "wash" fill is absent — the guest chat looks one-sided (only the outbound bubble has a background).
- PROTOTYPE SAYS: incoming/secondary chips carry a soft cream wash (like the operator/owner cream incoming bubbles).
- BUILDABLE? Presentation — declare `--vy-wash` once in each shell's `:root`.

**D2 — `.vy-pill.is-on` undefined → no active-filter highlight** · P2 · XS · ✅ **DONE (Batch 1)** — defined `.vy-pill.is-on` (gradient fill + white + accent shadow) in the operator layout; the Insights/Reports active range pill now shows its selected state (verified).
- WHERE: Operator · Insights (`insights.blade.php` L13) + Reports (`reports.blade.php` L12) date-range pills.
- WHAT'S OFF: the *active* range pill is visually identical to the inactive ones — the user can't tell which range they're viewing.
- PROTOTYPE SAYS: the selected range is highlighted (accent fill).
- BUILDABLE? Presentation — define `.vy-pill.is-on` (billing already has the real `.vy-seg-btn.is-on` to mirror).

**D3 — `--vy-shadow-dark` undefined → dark panels have no shadow** · P2 · XS · ✅ **DONE (Batch 1)** — defined `--vy-shadow-dark: rgba(60,30,10,.28) 0 10px 30px` (brief-exact) in the operator token block (owner already had it); the dark "Tonight"/feature panels regain elevation (verified resolves).
- WHERE: Operator · dark "Tonight"/feature panels & tiles (`.vy-tonight` L705, `.vy-dash-kpi.is-feature` L673) on Dashboard, Reservation-detail, Maintenance-detail, Owner-detail, Payout-run, Guest-profile.
- WHAT'S OFF: the dark hero panels sit flat on the page (no elevation) because the shadow token they reference isn't in `:root`.
- PROTOTYPE SAYS: the dark panels are elevated with a soft drop shadow.
- BUILDABLE? Presentation — declare `--vy-shadow-dark`.

**D4 — `.vy-card--accent` undefined → accent cards render plain** · P2 · XS · ✅ **DONE (Batch 1)** — defined `.vy-card--accent { border-color: var(--vy-accent-border) }` in the operator layout (mirrors owner); "Record a payment"/"Actions"/"Edit profile" cards regain the accent border (verified).
- WHERE: Operator · "Record a payment" (`reservation-detail`), "Actions" (`maintenance-ticket-detail`), "Edit profile" (`guest-profile`).
- WHAT'S OFF: cards meant to carry an accent treatment fall back to a plain `.vy-card`.
- PROTOTYPE SAYS: these action cards are accent-tinted to stand out.
- BUILDABLE? Presentation — define the `.vy-card--accent` modifier.

**D5 — `.vy-field` / `.vy-field-label` undefined in the owner system** · P2 · XS · ✅ **DONE (Batch 1)** — defined `.vy-field` + `.vy-field-label` (+ `.vy-fl-note`/`.vy-fl-right`) in the owner layout (mirrors operator); labels on block-calendar/payout-method/tax-center/rates/add-unit now render styled uppercase (verified).
- WHERE: Owner · block-calendar, payout-method, tax-center, rates, add-unit form labels.
- WHAT'S OFF: form labels render with browser defaults (unstyled) — the class exists in the operator/public shells but was never ported to `layouts/owner.blade.php`.
- PROTOTYPE SAYS: consistent styled field labels.
- BUILDABLE? Presentation — add the two classes to the owner layout (copy the operator definition).

**D6 — Bare `.vy-flash` renders unstyled** · P2 · XS · ✅ **DONE (Batch 1)** — gave the base `.vy-flash` a neutral glass tone (bg `--vy-glass` + hairline border + ink) so bare notices on billing/checkout/insights/portfolio render styled; `.is-ok`/`.is-err` still override (verified).
- WHERE: Operator · billing, billing-checkout, insights (multi-currency notice), portfolio — use `.vy-flash` **without** `.is-ok`/`.is-err`, and the base rule sets only layout (no bg/tone).
- WHAT'S OFF: these notices show as untinted text with no card/background.
- PROTOTYPE SAYS: notices are tinted status banners.
- BUILDABLE? Presentation — give `.vy-flash` a base tone, or add the tone class at each call site.

**D7 — Owner Booking-Detail NET-TO-YOU rail is light, not dark** · P2 · S · ✅ **DONE (Batch 3)** — swapped `.vy-card--accent` → the shared `.vy-card--dark` (grad-dark bg + white text + `--vy-shadow-dark`) + a light divider; the real net figure (€442.80) renders white on dark, content untouched (#17). Browser-verified.
- WHERE: Owner · `booking-detail.blade.php` L109–124 (`.vy-card--accent`, light).
- WHAT'S OFF: the earnings hero rail is a light accent card; the prototype (and the owner Dashboard/Payouts next-payout panels) use a **dark** panel. (Register-noted as deferred — "needs per-child text-color surgery".)
- PROTOTYPE SAYS: a dark NET-TO-YOU hero panel.
- BUILDABLE? Presentation — swap to `.vy-card--dark` + invert child text colours.

**D8 — Owner Tax-Center "About your tax" is light; banner icon missing** · P3 · S · ✅ **DONE (Batch 3)** — "About your tax" → the shared `.vy-card--dark` + a leading info icon; the held-payout banner gained the warning icon (triangle in a filled accent circle). Copy/figures untouched. Browser-verified.
- WHERE: Owner · `tax-center.blade.php` L126–129 (light card); the held-payout banner omits the warning icon.
- PROTOTYPE SAYS: a dark "About your tax" panel + a warning icon on the release banner. (Register-noted, deferred.)
- BUILDABLE? Presentation — dark swap + add the icon.

**D9 — Hardcoded hex / off-token radius instead of design tokens** · P3 · S–M · ✅ **DONE (Batch 3, targeted pages)** — tokenized the flagged pages: **public Room** exact-match hexes → tokens (`#2B2320`→`--vy-ink`, `#8A8079`→`--vy-muted`, `#A69A92`→`--vy-faint`, `#E8703A`→`--vy-accent-3`, `#C2410C`→`--vy-accent`, `#F6EBE2`→`--vy-wash`) + `9px`→`--vy-r-sm` (zero visual change); **guest pay-error** + **operator Schedule** error banner off-palette brick-red (`#C2453A`/`#8A2F27`) → `--vy-accent` (on-palette correction, matching the operator error convention); Schedule `#F6EBE2`→`--vy-wash`. **Left intentionally:** the roster's bespoke leave/conflict status tints (`#B0322A`, `#FBE9E7`, `#FBF3E4`, `#F4E7CC`, `#8A6E2F`) are semantic status colours with no exact palette token — a candidate for a future status-token set, not magic-value drift; and the Room one-offs `#4A4038`/`#6b6b6b`/`22px` (no exact token). Browser-verified render on the tokens.
- WHERE: Operator · **Schedule** (worst — `#FBE9E7/#8A6E2F/#B0322A/#C2453A` inline, roster grid not on the design system) + Unit-requests (status via inline `color-mix`, not `.vy-tag`) + Notifications (unread wash `rgba(217,102,46,.05)` hardcoded). Guest · payments pay-error `#C2453A/#8A2F27`. Public · `booking/room.blade.php` (`border-radius:22px` & `:9px` vs `--vy-r-pill`/`--vy-r-sm`; inline `#E8703A/#2B2320/#8A8079`) + browse rating badge. Owner · team/block-calendar/tax-center error `#b8860b`/`#C2453A` instead of `--vy-amber`.
- WHAT'S OFF: colours/radii drift from the token system; the same concept renders slightly differently per page.
- PROTOTYPE SAYS: one consistent palette/radius scale.
- BUILDABLE? Presentation — replace literals with `--vy-*` tokens + `--vy-r-*` radii.

**D10 — Bar-chart drift (owner)** · P3 · S–M · ⏳ **ASSESSED, deferred (Batch 5)** — most charts already use the shared `<x-charts.bars>` (unit-detail, operator + owner Insights "nights that fill first", operator Insights). The remaining hand-rolled ones each have a reason the shared component isn't a clean drop-in: the owner **Dashboard** earnings chart is an **interactive 3M/6M/12M** variant (the component is static), and the shared component renders **per-bar value labels** the hand-rolled month charts omit — so a swap changes the look and needs a focused design decision to match the prototype's exact bar labels. Left as a **P3 design-consolidation follow-up** rather than a risky visual change in the micro-interaction pass.
- WHERE: Owner · dashboard, archive, insights-revenue hand-roll bar markup (different radius/gradient/no `.vy-bar`), while unit-detail + insights-weekday correctly use the shared `<x-charts.bars>`.
- WHAT'S OFF: two bar-chart styles coexist; they'll drift.
- BUILDABLE? Presentation/component — route all bars through `<x-charts.bars>`.

**D11 — owner-settings/owner-security roll their own inline typography** · P3 · S
- WHERE: Owner · `owner-settings` + `owner-security` define local `$h2/$lbl/$sub/$btn/$ghost` PHP style-vars instead of `.vy-card-title`/`.vy-eyebrow`/`.vy-btn`.
- WHAT'S OFF: heading/label/button styling forks from the design system (drift risk).
- BUILDABLE? Presentation — use the shared classes.

**D12 — `.vy-input` defined twice in the operator layout** · P3 · S
- WHERE: Operator · `layouts/operator.blade.php` (folio ctx L652 vs modal ctx L776) with differing padding/width.
- WHAT'S OFF: input sizing depends on cascade order — a latent inconsistency.
- BUILDABLE? Presentation — reconcile to one definition.

**D13 — Public inputs lack the soft focus glow the auth inputs have** · P3 · XS
- WHERE: Public storefront search/checkout/guest-detail inputs (`public/shell.blade.php` — only a `:focus-visible` outline, no `.vy-input:focus` box-shadow), vs the auth screens' terracotta focus glow.
- WHAT'S OFF: storefront fields are visually flatter on focus than the login screen — inconsistent within one product.
- BUILDABLE? Presentation — add the `.vy-input:focus` rule to the public shell.

**D14 — Auth-screen micro-inconsistencies** · P3 · S
- WHERE: Public/auth · `onboarding` + `invite-invalid` `.vy-btn` omit the `:hover translateY` that login/forgot/reset/set/2FA have; `invite-invalid` also omits the card `vyFadeUp` + drift; `set-password` lacks the password-strength meter that `reset-password` has.
- WHAT'S OFF: the auth family isn't uniform.
- BUILDABLE? Presentation — align hover/entrance + port the strength meter.

**D15 — Dead/undefined modifier classes** · P3 · XS
- WHERE: `.vy-btn--accent` (guest payments), `.vy-card--flush` (owner messages), `.vy-line-sub` (operator schedule/unit-requests/notifications), `.vy-lead` (owner reviews) — used but undefined; harmless fallbacks, but dead.
- BUILDABLE? Presentation — define or remove.

### 2C · FEATURE-DEPTH
*(Almost every prototype feature the app omits is already an intentional divergence — fabricated content refused, account-gated, or KIND-3 — see the appendix. The register triaged these well, so genuine buildable-real omissions are thin.)*

**F1 — Owner Expenses "By category" is a plain list, not proportional bars** · P3 · S
- WHERE: Owner · `expenses.blade.php` right-rail By-category.
- WHAT'S OFF: the prototype shows proportional category bars; the app lists plain totals. (Register-noted "low value at 1-expense" — real once category spread exists.)
- PROTOTYPE SAYS: proportional horizontal bars (the operator Maintenance-costs bar treatment already exists to reuse).
- BUILDABLE? A real feature (reuse existing bar treatment).

**F2 (recorded KIND-3, not a fresh gap)** — Owner Booking-Detail Timeline + "Download confirmation PDF" need event data / a doc not built. Left as the register recorded it; note, don't chase.

### 2D · MICRO-INTERACTION / FEEL

**M1 — KPI/stat numbers don't count-up ✔** · P3 · M · ✅ **DONE (Batch 5)** — ONE shared parse-from-text count-up (`partials/count-up`, 800ms cubic ease-out, prototype-matched) consolidates the 4 duplicated owner `@script` blocks and animates every KPI/hero value (`.vy-dk-val`/`.vy-kpi-val`/`.vy-hero-figure`/`.vy-dark-figure`) across the operator + owner realms. It **lands on the EXACT server-rendered value** (captured up front, restored on the final frame — money to the cent #17, no fabrication #39), **skips** deferred (`.is-deferred`)/non-numeric/zero values, and is **graceful** (JS-off/reduced-motion → the real number already shows). Browser-verified: operator Insights €46.97→**€116.84** exact; owner hero €337.88→**€885.60** exact.
- WHERE: all realms. The prototype animates KPI/hero numbers with an 800ms cubic ease-out (`requestAnimationFrame`, verified). App: operator has **no** count-up; owner animates only 4 hero/dark figures (dashboard/statement/payouts/archive via `vyAcctUp`) — **KPI tiles never count up**; guest/public none.
- PROTOTYPE SAYS: numbers count up on load.
- BUILDABLE? Micro (small shared JS) — generalise owner's `vyAcctUp` into a `[data-countup]` helper applied to KPI number nodes across realms.

**M2 — No hover on card/row tap targets** · P2 · S · ✅ **DONE (Batch 2)** — a shared `.vy-card--tappable` (lift `translateY(-2px)` + elevated shadow, prototype-matched) defined once in all four design systems + a guest `.vy-row--tappable` (warm tint) + scoped `a.vy-owner-row:hover`; applied opt-in to the interactive tap-targets (guest trip cards, storefront room cards, owner unit cards, operator property cards, guest message rows, operator owner rows). **Scoped to interactive only** — the static "List a new property · Soon" placeholder and the trip-card reused as a Reservation-detail header deliberately do NOT get it (browser-verified 0 tappable). Real hover verified firing on guest + operator; owner computed-confirmed. Remaining lower-value row hovers (guest payments/saved inner rows) roll into a later micro-pass.
- WHERE: **Guest** — the whole portal (trip cards, payment rows, message-thread list rows, saved rows) is static. **Public** — `.vy-room-card`, `.vy-teaser-row` (the primary booking targets feel inert). **Operator** — `.vy-table` rows (Reports/Portfolio/Unit-requests), `.vy-owner-row` (clickable links, no tint), Inbox clickable KPI tiles, `.vy-prop-card`, `.vy-hk-card`, `.vy-bill-tier`. **Owner** — `.vy-unit-card` (clickable, no lift), review/team cards. (Operator *list* tables `.vy-utable` DO hover — it's the card grids + a few `.vy-table`s + the guest/public tap targets that don't.)
- PROTOTYPE SAYS: interactive cards/rows tint or lift on hover.
- BUILDABLE? Presentation — add `:hover` rules to the card/row classes.

**M3 — No loading/busy/skeleton state anywhere; `vyShimmer` never wired** · P3 · M · ✅ **RESOLVED honestly (Batch 5, no fake skeletons)** — the busy-state half was fixed in Batch 4 (the double-submit disable + "Processing…" label). For loading skeletons: the app is **fully server-rendered** — no `wire:loading`, no `#[Lazy]`, no `wire:navigate` — so there is **no genuine in-page async gap** to attach a content skeleton to (page-nav loading is covered by the NProgress bar; form submits by the Batch-4 busy state). The existing `.vy-skeleton` usages are **deferred "not yet available" placeholders** (occupancy, #39) — animating them would be a **fake perpetual shimmer** that falsely implies loading, so per the honesty rule they are left **static**. `vyShimmer` stays a reserved decorative keyframe (unused). **No fake skeleton was added anywhere** — the honest resolution.
- WHERE: all realms — every mutation is a full POST→PRG (several auto-submit selects/file inputs: housekeeping assign, schedule shift, units photo upload) with no on-submit feedback. `vyShimmer` is **defined in both operator + owner layouts but never referenced**, so `.vy-skeleton` placeholders are static.
- PROTOTYPE SAYS: in-flight feedback; shimmering skeletons for deferred data.
- BUILDABLE? Micro — wire `vyShimmer` to `.vy-skeleton`; add disable-on-submit (pairs with I3).

**M4 — Guest portal `.vy-btn` lifts without a transition** · P3 · XS
- WHERE: Guest · `components/guest/shell.blade.php` `.vy-btn:hover { transform: translateY(-1px) }` with **no `transition`** → the lift snaps (operator/owner/public buttons all declare transitions).
- BUILDABLE? Presentation — add `transition: transform .15s`.

**M5 — Guest portal inputs have no focus ring (internal-consistency/a11y)** · P3 · S
- WHERE: Guest · portal-shell pages (manage/wallet/messages/thread/saved/review use inline-styled inputs; the shell defines no `.vy-input:focus`). The guest **auth** layer, operator, and owner all have focus rings.
- NOTE: the *prototype itself ships zero focus rules*, so this is **not** a prototype deviation — it's an internal-consistency + accessibility nicety (the app is otherwise ahead of the prototype on focus). Flagged low.
- BUILDABLE? Presentation — add a shared `.vy-input:focus` to the guest shell.

**M6 — Missing entrance/celebration micro-touches** · P3 · S
- WHERE: Public booking confirmation check-badge fades in but has no pop/draw; owner `reviews` + `team` section cards omit the `.vy-fade` entrance every sibling card has.
- BUILDABLE? Presentation — add `.vy-fade` to the two owner cards; a small badge pop on confirmation.

---

## 3 · Per-page matrix (page × 4 dimensions)

Legend: **✓** matches / no genuine gap · **finding-id** = genuine gap on that page · **✻** intentional-divergence only (recorded, not a gap). Dimensions: **In**teraction · **De**sign · **Fe**ature · **Mi**cro.

### Operator (`/app`)
| Page | In | De | Fe | Mi | Notes |
|---|---|---|---|---|---|
| Shell / sidebar | ✓ | ✓ | ✓ | ✓ | at-parity (regrouped, role-filtered) |
| Dashboard | ✓ | D3 | ✓ | M1 | dark "Tonight" no shadow; KPIs no count-up |
| Reservation Rack | ✓ | ✓ | ✻ | ✓ | Gantt = recorded KIND-3 |
| Front Desk | ✓ | ✓ | ✻ | M3 | tabs/timeline = KIND-3; no busy on check-in/out |
| Reservations List | ✓ | ✓ | ✓ | M1 | KPI count-up |
| Reservation Detail | ✓ | D3,D4 | ✓ | M1 | dark balance panel no shadow; "Record a payment" plain |
| Refund | I3 | ✓ | ✓ | M3 | no busy on refund POST |
| Guests CRM | ✓ | ✓ | ✓ | M1 | — |
| Guest Profile | ✓ | D3,D4 | ✓ | ✓ | dark billed tile no shadow; "Edit profile" plain |
| Housekeeping | ✓ | ✓ | ✻ | M2,M3 | cards no hover; assign auto-submit no busy; List-mode KIND-3 |
| Maintenance Board | ✓ | ✓ | ✻ | M2 | kanban cards no hover; rail = KIND-2 |
| Maintenance Work-order | ✓ | D3,D4 | ✓ | ✓ | dark status panel no shadow; "Actions" plain |
| Maintenance Costs | ✓ | ✓ | ✓ | M1,M2 | KPI count-up; bar rows no hover |
| Maintenance Settings | ✓ | ✓ | ✓ | ✓ | at-parity |
| Units | ✓ | ✓ | ✓ | M3 | photo upload auto-submit no busy |
| Inbox / Messages | ✓ | ✓ | ✻ | M2 | clickable KPI tiles no hover; channels KIND-2 |
| Inbox Conversation | ✓ | ✓ | ✻ | ✓ | composer textarea no focus rule |
| Reports | ✓ | **D2**,D6 | ✓ | M1,M2 | active pill no highlight; `.vy-table` rows no hover |
| Insights | ✓ | **D2**,D6 | ✓ | M1 | active pill no highlight; bars static |
| Portfolio | ✓ | D6 | ✓ | M2 | prop cards + comparison table no hover; unstyled flash |
| Owners | ✓ | ✓ | ✓ | M2 | `.vy-owner-row` links no hover |
| Owner Detail | ✓ | D3 | ✓ | ✓ | dark balance tile no shadow |
| Payout Run | ✓ | D3 | ✻ | ✓ | dark tile no shadow; Connect = #45 |
| Rates & availability | ✓ | ✓ | ✻ | ✓ | channel-sync/per-unit = KIND-2/3 |
| Search | ✓ | D9 | ✓ | ✓ | `.vys-tab` no hover/focus |
| New Reservation | ✓ | D9 | ✓ | ✓ | tab buttons no hover/focus |
| Group Booking | ✓ | D9 | ✓ | ✓ | unit/opt pills no hover tint |
| Unit Requests | ✓ | D9 | ✓ | M2 | inline `color-mix` status; table no hover |
| Schedule | ✓ | **D9** | ✻ | M2 | heavy inline hex; roster off-system; stats/drag = KIND-3 |
| Roles | ✓ | ✓ | ✻ | ✓ | editable catalog refused (#30) |
| Settings | ✓ | ✓ | ✻ | ✓ | focused subset (divergence) |
| Profile | ✓ | ✓ | ✻ | ✓ | focused subset |
| Security | ✓ | ✓ | ✻ | ✓ | at-parity; sessions "Soon" |
| Notifications feed | ✓ | D9 | ✓ | ✓ | rows inline-styled (unread wash hardcoded) |
| Notification prefs | ✓ | ✓ | ✻ | ✓ | Push/SMS = KIND-2 |
| Billing / Paywall | ✓ | D6 | ✻ | M2 | unstyled flash; tier cards no hover; #41 |
| Subscription Checkout | I3 | D6 | ✻ | ✓ | unstyled flash; #45/#41 |

### Owner (`/owner`) — primary + sub-user (capability views = same shell + route/nav gating, no separate design)
| Page | In | De | Fe | Mi | Notes |
|---|---|---|---|---|---|
| Dashboard | ✓ | ✓ | ✻ | M1 | KPI count-up (hero has it); action-centre = KIND-2 |
| Bookings | ✓ | ✓ | ✓ | M1 | solid |
| Booking Detail | ✓ | **D7** | ✻ | M1 | NET-TO-YOU rail light not dark; Net hero no count-up; Timeline KIND-3 |
| My Units | ✓ | ✓ | ✓ | M2 | `.vy-unit-card` clickable no lift |
| Unit Detail | ✓ | ✓ | ✻ | ✓ | exemplar (shared charts); rest = #28/#39 |
| Statement | ✓ | ✓ | ✻ | ✓ | at-parity; occupancy skeleton static (deferred) |
| Balance | ✓ | ✓ | ✻ | M1 | withdraw = #45 |
| Payouts | ✓ | D13-ish | ✻ | ✓ | search input bypasses `.vy-input` (no ring) |
| Block Calendar | ✓ | **D5** | ✻ | ✓ | field labels unstyled; hex error tone; local `.vyc-*` off-system |
| Rates | ✓ | **D5** | ✻ | ✓ | field labels unstyled; direct-edit model locked |
| Payout Method | ✓ | **D5** | ✻ | ✓ | field labels unstyled; Connect = #45 |
| Tax Center | ✓ | **D5**,D8 | ✻ | ✓ | field labels unstyled; "About" light not dark; banner icon |
| Expenses | ✓ | ✓ | **F1** | M1 | By-category plain list vs bars; search no ring |
| Documents | ✓ | ✓ | ✓ | ✓ | at-parity |
| Reports | ✓ | ✓ | ✓ | M1 | KPI count-up |
| Insights | ✓ | D10 | ✻ | M1 | revenue bars hand-rolled vs shared component |
| Reviews | ✓ | D1,D15 | ✻ | M6 | `--vy-wash`/`.vy-lead` undefined; cards omit `.vy-fade`; no hover |
| Messages | ✓ | D15 | ✻ | ✓ | `.vy-card--flush` undefined; 2-pane = KIND-3 |
| Message Thread | ✓ | ✓ | ✓ | ✓ | uses the good `.vy-flash` |
| Notifications | ✓ | ✓ | ✓ | ✓ | mirrors the bell panel (just built) |
| Notification prefs | ✓ | ✓ | ✻ | ✓ | checkbox focus = default; SMS = KIND-2 |
| Team | ✓ | D1,D5,D9 | ✻ | M6 | `--vy-wash` + `.vy-field-label` undefined; hex error; cards omit `.vy-fade`; activity log = KIND-2 |
| Owner Settings | ✓ | D11 | ✻ | ✓ | inline typography vars (drift) |
| Owner Security | ✓ | D11 | ✻ | ✓ | inline typography vars; sessions real, rest "Soon" |
| Archive | ✓ | D10 | ✻ | ✓ | per-year bars off shared component; search no ring |
| Agreement | ✓ | ✓ | ✻ | ✓ | at-parity (dark card correct) |
| Add Unit | ✓ | **D5** | ✻ | ✓ | field labels unstyled; request-model divergence |
| Help | ✓ | ✓ | ✻ | ✓ | `<details>` default disclosure; AI/ticketing = KIND-2 |

### Guest (`/guest`)
| Page | In | De | Fe | Mi | Notes |
|---|---|---|---|---|---|
| Login / Claim / Verify / Reset | ✓ | ✓ | ✻ | I3-ish | auth layer rich; no submit-loading; property-hero/social = divergence |
| Home (landing) | ✓ | ✓ | ✓ | ✓ | minimal stub (auth layout) |
| Trips | ✓ | ✓ | ✻ | **M2** | trip cards no hover; hero stats/countdowns = KIND-2 |
| Reservation | I2 | ✓ | ✻ | M2 | "Settle · Soon" vs live pay; door-code stays on Arrival (#33) |
| Manage Booking | ✓ | ✓ | ✻ | M3,M5 | no focus rings; no busy on 4 request actions; calendar = KIND-3 |
| Check-in | ✓ | D1 | ✻ | M3 | `--vy-wash` ID icon flat; no busy on confirm; KYC = #45 |
| Arrival Info | ✓ | D1 | ✻ | ✓ | `--vy-wash` door/lock chips flat; copy-btn has feedback (good); recs = KIND-2 |
| Payments | I3 | D9,D15 | ✻ | M2 | no busy on Stripe pay; hex error; `.vy-btn--accent` dead; rows no hover |
| Receipt | I2 | ✓ | ✻ | ✓ | "Settle · Soon" vs live pay; PDFs real |
| Wallet | ✓ | ✓ | ✻ | M3,M5 | no focus rings; no busy on Redeem/Apply; loyalty = KIND-2 |
| Messages (list) | ✓ | ✓ | ✻ | M2,M5 | thread rows no hover; inputs no focus ring |
| Message Thread | ✓ | **D1** | ✻ | M3,M5 | **inbound bubbles no background (verified)**; no busy on Send |
| Saved | ✓ | ✓ | ✻ | M2,M5 | rows no hover; form inputs no focus ring |
| Review | ✓ | D1 | ✓ | M5 | `--vy-wash` chips flat; star/chip inputs no visible focus |
| Account | **I1** | ✓ | ✻ | M5 | **drops the portal nav (verified)**; own-PII allowed |

### Public storefront + onboarding/billing
| Page | In | De | Fe | Mi | Notes |
|---|---|---|---|---|---|
| Browse / Storefront | ✓ | D9,D13 | ✻ | **M2** | room cards no hover; inline hex badge; no soft focus; multi-property = divergence |
| Room / Villa Detail | ✓ | **D9**,D13 | ✻ | M2 | off-token radius + inline hex; no hover on spec/amenity/review; media = P3-2 |
| Checkout | **I3** | D13 | ✻ | ✓ | no busy on Confirm (writes inventory); error box styled; #45 |
| Search Results | ✓ | ✓ | ✻ | M2 | per-property scope divergence |
| Signup Wizard | I3,I4 | D14 | ✓ | ✓ | no busy on provision; hard step-cut; `.vy-btn` no hover |
| Paywall / Billing | ✓ | D6 | ✻ | M2 | unstyled flash; tier cards no hover; #41 |
| Subscription Checkout | I3 | D6 | ✻ | ✓ | unstyled flash; seg no hover; #45/#41 |
| Invite an owner | ✓ | ✓ | ✓ | ✓ | matches exactly |
| Operator Login/Reset/Set-pw | ✓ | D14 | ✓ | I3-ish | invite-invalid/onboarding hover+fade; set-pw no strength meter; no submit-loading |
| Two-factor challenge | ✓ | ✓ | ✓ | I3-ish | no submit-loading |

---

## 4 · Quick wins (XS/S · presentation-only · batchable · high-visible-impact)

These are the notification-square-tile class of fix — low-risk CSS, big perceived polish.

**QW-A · "Define the missing tokens & classes" (one CSS batch, XS each):**
- `--vy-wash` in each shell's `:root` → fixes **D1** across guest chat/arrival/checkin/review + owner reviews/team.
- `--vy-shadow-dark` in the operator `:root` → **D3** (all dark panels regain elevation).
- `.vy-card--accent` in the operator layout → **D4**.
- `.vy-pill.is-on` in the operator layout → **D2** (active range visible).
- `.vy-field` / `.vy-field-label` in the owner layout → **D5** (5 owner forms).
- base tone on `.vy-flash` (or add `.is-ok`/`.is-err` at the 4 call sites) → **D6**.
- `.vy-input:focus` glow in the public shell (**D13**) + the guest portal shell (**M5**).
- `transition` on the guest `.vy-btn` (**M4**).
- define-or-remove the dead modifiers `.vy-btn--accent`/`.vy-card--flush`/`.vy-line-sub`/`.vy-lead` (**D15**).

**QW-B · "Hover on tap targets" (S):** add `:hover` to the guest portal cards/rows, public `.vy-room-card`/`.vy-teaser-row`, operator `.vy-table` rows + `.vy-owner-row` + card grids, owner `.vy-unit-card`/review/team cards → **M2**.

**QW-C · "Dark-panel swaps" (S):** owner Booking-Detail NET-TO-YOU rail (**D7**) + Tax-Center "About your tax" + banner icon (**D8**).

**QW-D · "Tokenize the stragglers" (S):** operator Schedule + Unit-requests + guest pay-error + public Room hex/radius → tokens (**D9**); owner error-hex → `--vy-amber`.

## 5 · Real interaction / feature work (investigate → build; larger than a CSS tweak)

- **RIF-1 (I1) — Wrap the guest Account page in the portal shell** so it keeps the nav (verified stranded today). S.
- **RIF-2 (I2) — Reconcile the guest "Settle · Soon" stub with the live Payments hub** (link it, or drop it). S.
- **RIF-3 (I3 + M3) — Loading/disable-on-submit on the slow, consequential actions** — public Checkout Confirm, signup provision, guest Stripe pay, operator check-in/out & refund POSTs — and wire `vyShimmer` to `.vy-skeleton`. S–M. (This is the closest sibling to the notification-panel/profile-rebuild class of work.)
- **RIF-4 (M1) — Shared count-up helper** — generalise owner's `vyAcctUp` into a `[data-countup]` directive on KPI number nodes across realms. M.
- **RIF-5 (D10) — Consolidate owner bar-charts** onto `<x-charts.bars>` (dashboard/archive/insights-revenue). S–M.
- **RIF-6 (D11/D12) — De-fork the typography/input duplications** (owner-settings/security inline vars → classes; operator `.vy-input` double-definition). S.
- **RIF-7 (F1) — Owner Expenses proportional bars** (reuse the Maintenance-costs bar treatment). S.
- **RIF-8 (I4/D14/M6) — Auth + onboarding uniformity** — step cross-fade, invite-invalid/onboarding hover+fade, set-password strength meter, confirmation-badge pop. S.

## 6 · Suggested polish order (batched by leverage)

1. **Batch 1 — Missing tokens & classes (QW-A).** XS CSS, one commit. Clears D1,D2,D3,D4,D5,D6,D13,D15,M4,M5 across all four realms. **Highest impact-per-effort — do this first** (it's the same lesson as the notification tiles: define the thing the app already references).
2. **Batch 2 — Hover on tap targets (QW-B / M2).** S CSS. The guest portal + storefront stop feeling inert.
3. **Batch 3 — Dark-panel swaps + tokenize (QW-C + QW-D / D7,D8,D9).** S. Removes the last obvious per-page look drift.
4. **Batch 4 — Real interaction fixes (RIF-1,2,8 / I1,I2,I4,D14,M6).** S. Account-in-shell, Settle-stub, auth uniformity.
5. **Batch 5 — Micro-interaction pass (RIF-3,4,5,6 / I3,M1,M3,D10,D11,D12).** M. Loading/skeleton states, count-up, chart consolidation, de-fork. Investigate→build, gated like the notification panel.
6. **Batch 6 — Feature depth (RIF-7 / F1).** S, when category spread exists.

Each batch is independently shippable, presentation-first (Batches 1–4), then the micro/JS pass (5), then the one real feature (6) — mirroring the gated, suite-green, per-commit discipline of the notification rebuild.

---

## 7 · Appendix — confirmed-matching + intentional divergences (NOT gaps)

### Confirmed matching the prototype (no change needed)
- **Shared animation vocabulary:** `vyFadeUp` card entrances + `vyDrift` ambient blob are present in operator/owner/guest/public and match the prototype's.
- **List-table row hover:** `.vy-utable`/`.vy-table tbody tr:hover` (operator), `.vy-table tbody tr:hover`/`.vy-convo-row:hover translateX` (owner) tint on hover as the prototype does.
- **Button/pill/input transitions** on shared controls; **`:focus-visible` rings** on `.vy-btn`/`.vy-nav-item`/`.vy-input` — the app **exceeds** the prototype here (the prototype ships **0** `:focus` rules).
- **NProgress top loading bar** on navigation (a loading micro the app owns).
- **Reduced-motion floor** (`prefers-reduced-motion` kills animation, forces `.vy-fade` visible) in every realm.
- **Empty states** styled throughout (`.vy-empty` + honest per-section messages).
- **The notification bell → panel** (built 2026-07-26) — both shells, real feed, square tile + unread wash.
- **Invite-an-owner modal, operator Login/Reset/Set-password, Owner Documents/Agreement/Message-thread, Guest Review** — at parity.

### Intentional divergences (recorded in the register — deliberately different, with a principled reason)
- **KIND-2 fabricated content, REFUSED (honesty rule / #39):** invented occupancy %/trends/benchmarks, fabricated names (Marco Reyes / Hansen family / K. Novak / invented reviewers), channel-sync ribbons & "Connected" panels (dead `channels.status`), technician rosters, labour/parts splits, fabricated arrivals, multi-year history, comp-set/market data, rate-proposal/pricing engines, "vs last year" deltas, "Worth a look"/"Suggested next step" narratives, the "Channels"/"Housekeeping" fake notification pills, fake security scores, loyalty/referral/membership, welcome-credit promos, fabricated trial-recap stats. The app shows the **real/empty** state.
- **Security refusals:** editable role/permission catalogs (#30), guest contact/PII cards & message bodies on owner/operator surfaces (#28), door-code/wifi kept on the gated in-window Arrival Info only (#33).
- **Account-gated (Subhan's track, not polish gaps):** live Stripe card charge / Apple Pay / stored cards (#45), owner Stripe-Connect payout rail + KYC (#45), subscription live charge (#41), Channex live OTA sync, WhatsApp/OTA inbox channels, SMS, guest ID verification, passkeys/OAuth.
- **Recorded KIND-3 (needs data-shape or JS the presentation-only rule forbids):** reservation-rack Gantt, Front-Desk tabs/timeline, Rates per-unit grid, guest calendars/wizards/2-pane chat, owner 2-pane master-detail messages, Housekeeping List-mode + drag-assign, Team Schedule drag/coverage/hours, Booking-Detail timeline/PDF.
- **Scope/model divergences (locked decisions):** per-property (not multi-property brand-collection) storefront + search, booking-tied guest claim (not open self-signup), owner Rates direct-edit (not a proposal/approval workflow), owner Add-Unit request (not a self-serve wizard), placeholder subscription prices, pay-on-arrival public checkout, placeholder room photos (deferred media pass P3-2).

**None of the above are counted as findings.** The 26 findings in §2 are exclusively genuine, presentation-first deviations.
