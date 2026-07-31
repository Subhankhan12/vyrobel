# Operator Visual-Fidelity Audit (task #104)

**Scope:** every built operator page vs its decoded wireframe. Read-only diagnosis (PHASE 1). Method: 3 parallel code-audit agents + a browser side-by-side of the Dashboard vs `proto-op-dashboard.png`.
**Date:** 2026-07-31. Status: PHASE 1 complete; PHASE 2 gated fixes pending founder decisions.

---

## The honest headline

The premise — "operator pages didn't match with the owner pages' exactness" — is **mostly not borne out at the content level.** Verified across all 42 operator blades:

- **The design tokens are byte-identical to the owner's.** `--vy-glass: rgba(255,252,248,.58)`, `backdrop-filter: blur(22px) saturate(1.15)`, `--vy-shadow: 0 14px 40px rgba(150,90,50,.16), inset 0 1px 0 rgba(255,255,255,.65)`, radii 30/20/12/10 + 50%, Sora/Inter, accent `#C2410C`, `--vy-grad` — all present in `layouts/operator.blade.php` and identical to `layouts/owner.blade.php`.
- **Every page uses the shared `<x-operator.shell>` + the glass `.vy-card` + the `vyFadeUp` entrance.** No page uses a flatter card, no page injects a competing background, none re-defines the tokens. The wireframes contain **no** count-up animation, so "missing count-ups" is not a real gap.
- Most content pages are **already at exact-match or a faithful match with deliberate honest simplifications** (deferred payments, GDPR additions, feature-gating) — the product of the recent per-page exact-match rebuilds (#89–#102).

**So this is NOT a 30-page pixel-rebuild.** The real gap is concentrated in two places: (1) **the shared shell CHROME**, and (2) a small set of **targeted content nits** + two genuinely-restructured pages.

---

## ROOT CAUSE — the operator shell chrome was never reconciled to its wireframe

The tokens match, but the operator **shell composition** (`components/operator/shell.blade.php` + the sidebar/background CSS in `layouts/operator.blade.php:76–138`) is an **earlier design pass** that predates the "exact-match to the decoded wireframe" reconciliation the OWNER shell received. Critically, the decoded operator wireframe's chrome is the **same frosted-floating, terracotta-active, peach→grey design the owner shell already ships** — so the operator chrome diverges from BOTH the owner chrome AND its own wireframe. The concrete deltas (all presentation-only):

| # | Chrome property | Wireframe (= owner) | Operator BUILT | File |
|---|---|---|---|---|
| 1 | **Active nav item colour** | terracotta `#C2410C`, weight 500, `.72` bg, `blur(10px)`, `0 4px 14px` | **espresso `--vy-ink`**, weight 600, `.9` bg, no blur | `operator.blade.php:133–136` |
| 2 | **Page background** | `linear-gradient(135deg,#F6C89B→#EBD9CF→#D9D6D2)` (peach→grey) | **cream** `#FBF3EC→#F6EBE2` + radial glows | `operator.blade.php:76–80` |
| 3 | **Sidebar surface** | floats on the gradient (no bg/border) | **opaque bordered panel** `rgba(255,253,250,.5)` + `blur(10px)` + `border-right` | `operator.blade.php:111–113` |
| 4 | **Brand** | plain "V Vyrobel", name 17px, mark radius 10px | adds an **"OPERATOR" sublabel**, name 16px, mark radius 12px | `shell.blade.php:91–96`, `operator.blade.php:118,122` |
| 5 | **Property switcher** | inside the sidebar (below the brand) | in the topbar | `shell.blade.php:140` |
| 6 | **Nav-label type** | `10.5px/500/.08em/#9C9089` | `9.5px/600/.12em/faint` | `operator.blade.php:125` |
| 7 | **Nav-item metrics** | `padding 9px 12px`, radius 12px, icon 16px | `padding 8px 10px`, radius 10px, icon 15px/opacity .8 | `operator.blade.php:126–131` |
| 8 | **Ambient background** | owner-style radial streaks | a single drifting blob | `operator.blade.php:84–95` |

**The fix is systemic + contained:** bring these 8 shell properties to the values the OWNER shell already uses (which match the wireframe). This one change — mostly in `layouts/operator.blade.php` — brings the entire operator realm to owner-parity chrome. Nothing is invented; the target values already exist in the owner layout.

---

## Content pages — verdict per page

| Verdict | Pages |
|---|---|
| ✅ **Already exact** | Owner Detail · Property Settings · Billing · Payout Run · Housekeeping · Maintenance board · Guests · Guest Profile · Notifications |
| 🔧 **Minor content nits** (labels / a styling nit / honest simplifications) | Dashboard · Calendar · Owners · Rates · Group Booking · Maintenance Costs · Maintenance Settings · New Reservation · Insights · Channels · Messages · Notification Preferences · Portfolio · Profile · Roles · Search · Email Templates · Email Editor |
| 🔨 **Genuinely restructured** (bigger, presentational) | **Front Desk** (the wireframe's "Today's timeline" time-of-day visualization → the built 3-column list board) · **Reports** (the wireframe's owner-statements block + revenue-by-channel table + revenue-by-month chart are absent) |
| 🚫 **Intentional / honest divergences — DO NOT "fix"** (would re-introduce fabrication) | Refund (the #27 per-payment picker; no fake "Visa ····4242" — no last4 stored) · Rates (no "Smart pricing" panel — no pricing engine) · Channels (no fake "Synced" — no live OTA rail) · Roles (fixed catalogue — new/delete-role deferred, #26) · New Reservation (no card fields — Stripe-hosted) · Email Templates (fixed transactional set) |

**The one repeatable styling nit:** the Dashboard KPI stat tiles (`.vy-dash-kpi`, `operator.blade.php:706–711`) use radius 12px + `shadow-sm` + no hover-lift, vs the wireframe's stat cards at radius 20px + the full warm shadow + a `translateY(-2px)` hover. **The Dashboard also lacks the wireframe's personalized "Good morning, {name}" H1 + inline search bar** (it shows the shell's plain "Dashboard" title).

---

## ⚠️ DECISIONS (founder approval before PHASE 2)

**F1 — the scope.** The honest gap is the **shared shell chrome** (systemic, high-impact) + a **small set of targeted content nits** — NOT a page-by-page rebuild of 30 already-matching pages. Recommended plan: **GATE 1** = the shell-chrome fix (the 8 deltas above) + the Dashboard as the reference page. **GATE 2** = the targeted content nits (Dashboard greeting/KPI-tiles + cheap label/structure alignments where they don't re-introduce fabrication) + consolidate + push. *(A full 30-page pass would mostly re-touch already-matching pages for no visible gain.)*

**F2 — the property switcher.** The wireframe places the property switcher **in the sidebar** (below the brand); the built app has it **in the topbar** (a real functional element alongside the bell + account). Move it to the sidebar (exact wireframe match) **vs** keep it in the topbar (functional, documented divergence).

**F3 — the two restructured pages (Front Desk, Reports).** These are bigger, genuinely-structural rebuilds (the Front Desk timeline visualization; the Reports owner-statements/channel/chart sections — some of which live honestly in other realms). Include them in this pass, **vs** defer them as separate structural tasks (this pass stays a chrome + fidelity pass).

**F4 — the intentional divergences stay.** Refund / Rates / Channels / Roles / New Reservation / Email Templates diverge from the wireframe **on purpose** (honesty — no fabricated card, sync, pricing engine, or role catalogue). These will NOT be "fixed" back into fabrication (#39). Confirm.

---

## Build plan (gated)

- **GATE 1 — the shared operator shell chrome** (presentation-only): the 8 deltas → the wireframe/owner values, in `layouts/operator.blade.php` (+ `shell.blade.php` brand/switcher). Browser-verified side-by-side vs the wireframe; the suite stays green (no behavioural change).
- **GATE 2 — targeted content fidelity** (the Dashboard greeting + KPI-tile styling, and the agreed nits) + docs consolidation + push. Intentional divergences preserved.

🔒 VISUAL/CSS ONLY — no backend/logic/data/route/behaviour change (the green suite is the proof); browser-verified side-by-side; the intentional honest divergences (#39) are preserved.

---

## FOUNDER DECISIONS (2026-07-31 — approved)

- **F1 → TARGETED scope.** GATE 1 = the shared shell-chrome fix (the 8 deltas). GATE 2 = the targeted content nits (Dashboard greeting + KPI-tile styling + cheap alignments). No full 30-page pass (content is largely at-parity).
- **F2 → MOVE the property switcher into the sidebar** (below the brand), matching the wireframe. Presentation-only (the same Livewire switcher, relocated); the topbar keeps bell + account.
- **F3 → DEFER Front Desk + Reports** as separate structural tasks (the timeline visualization + the Reports sections are structural, some honestly cross-realm). Documented here as known divergences.
- **F4 → intentional divergences STAY** (Refund #27 picker · Rates no-smart-pricing · Channels no-fake-sync · Roles fixed-catalogue · New Reservation no-card · Email fixed-set) — never re-introduced as fabrication (#39).
- **VISUAL/CSS ONLY** — no backend/logic/data/route/behaviour change; the green suite is the proof; browser-verified side-by-side.
