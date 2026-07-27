# Owner Overview — Wireframe-Exact Audit (2026-07-27)

**Goal:** make the LIVE owner Overview (`/owner`, `App\Livewire\Owner\OwnerDashboard`) match the decoded
`docs/design/raw/Owner Dashboard.html` wireframe **EXACTLY** (every design detail) **AND** wire every section to
**REAL owner-scoped data** (no stubs, no fabrication). This is the **Phase 1 read-only gap report** — no code changed.

The bar is now **exact match**. Items the 2026-07-25 design pass deferred as intentional divergence
(`docs/design/DIVERGENCE-REGISTER.md` line 126 — "Owner Dashboard · AT PARITY" with KIND-2 refusals) are **re-opened
and in scope** — but the honesty rule (#39) and the guest-PII perimeter (#28) still bind: where the wireframe shows
*fabricated* data (a €9k goal, an estimated payout date, invented occupancy trends), the real build renders the
**real value or an honest empty state**, never the fabrication.

---

## 0. How the wireframe was decoded (dc-runtime bundle)

`Owner Dashboard.html` is a `dc-runtime` bundle, not plain HTML: a `<script type="__bundler/template">` (a
JSON-encoded HTML shell containing the real `<x-dc>…</x-dc>` markup with inline-style design tokens) + a
`<script type="__bundler/manifest">` (base64/gzip assets — the Inter/Sora woff2 fonts + the runtime JS). Decode =
`JSON.parse` the template, extract the `<x-dc>` body (65,938 chars of exact markup), read the inline styles as the
design tokens and the `{{ … }}` placeholders as the fields the real backend must supply. Rendered live at
`http://127.0.0.1:8899/Owner%20Dashboard.html` (the project's raw-design server) for the visual side-by-side. The
decoded reference matches CLAUDE.md's stated palette exactly (warm-glass, `#C2410C` accent, Inter+Sora, dark rail).

**Live files under audit:** `app/Livewire/Owner/OwnerDashboard.php` · `resources/views/livewire/owner/dashboard.blade.php`
· the shared `resources/views/components/owner/shell.blade.php` + `resources/views/components/layouts/owner.blade.php`
(the owner design system — a shell change touches **every** owner page, so it is proven safe by the whole suite
staying green).

---

## 1. GLOBAL / SHELL design gaps (shared — affects every owner page)

These live in the shared owner layout/shell. Fixing them to the wireframe changes the sidebar/background/header on
all owner pages (desired: one consistent system; blast radius proven safe by the green suite).

| # | Element | Wireframe (decoded token) | Live (`layouts/owner.blade.php` / `owner/shell.blade.php`) | Gap |
|---|---|---|---|---|
| G1 | **Background** | `linear-gradient(135deg,#F6C89B 0%,#F1B896 32%,#EBD9CF 62%,#D9D6D2 100%)` — a saturated **peach→grey diagonal** | pale cream: radial(white)+radial(255,224,200)+linear(120deg cream)+linear(180deg `#FBF3EC→#F6EBE2`) | **Different.** Live is far paler; wireframe is a peachy diagonal. |
| G2 | **Color orbs** | 3 absolute orbs `#E8703A / #F0A57A / #FBE3CE`, `blur(70px)`, `opacity .55`, positioned; orb 1 animated `vyDrift 14s` (gated on `{{ motion }}`) | none (only one `body::before` drifting highlight) | **Missing** — the orbs "make the frost visible". |
| G3 | **Light streaks** | 2: top-right `radial(white .35)` `blur(10px)` + a rotated `linear(120deg,255,240,220 .5)` `rotate(28deg) blur(24px)` animated `vyShimmer 9s` | none | **Missing** the shimmer streak. |
| G4 | **Fonts** | Inter (body) + Sora (headings), weights 400/500 (Inter), 500/600 (Sora) | Inter + Sora via Google Fonts CDN | ✅ same families (CDN vs embedded — functionally equal). |
| G5 | **Sidebar width** | `200px`, `padding 26px 16px`, `gap 26px`, no border-right (frost floats), `vyFadeUp` | `232px` grid col, `padding 22px 16px`, `gap 20px`, **border-right hairline**, sticky | width, padding, gap, the border-right. |
| G6 | **Sidebar active item** | color **`#C2410C` (terracotta)**, bg `rgba(255,255,255,.72)`, `backdrop-filter blur(10px)`, shadow `0 4px 14px rgba(150,90,50,.14)`, border `rgba(255,255,255,.8)` | color **`--vy-ink` (dark)**, bg `rgba(255,255,255,.9)`, `--vy-shadow-sm` | **Active text color wrong** (dark vs terracotta); bg/blur differ. |
| G7 | **Sidebar item** | `13.5px/500`, `padding 9px 12px`, radius `12px`, icon `16px`, inactive `#8A8079`, hover `rgba(255,255,255,.35)` | `13.5px/500`, `padding 8px 10px`, radius `10px` (`--vy-r-sm`), icon `15px`, inactive `--vy-muted`, hover `rgba(255,255,255,.6)` | padding, radius, icon size, hover opacity. |
| G8 | **Nav group label** | `10.5px/500`, `letter-spacing .08em`, color `#9C9089`, `padding 0 12px 6px` | `9.5px/600`, `.12em`, `--vy-faint`, `padding 8px 10px 4px` | size, weight, spacing, color. |
| G9 | **Brand mark** | `34px`, radius `10px`, Sora 600 17px | `34px`, radius `12px` (`--vy-r-md`), Sora 700 18px | radius, weight, size. |
| G10 | **Sidebar icons** | each nav item has a **specific** SVG (grid/home/calendar/doc/card/etc. — decoded, all captured) | different generic `<path>` per item | icon glyphs differ per item. |

**Sidebar nav SET divergence (a decision, see §5-D2):** the wireframe sidebar lists a **reduced** set — Owner portal
(*Overview · My units · Calendar*), Earnings (*Statements · Payouts · Balance · Expenses · Tax center*), Manage
(*Reports · Insights · Rates · Reviews · Agreement · Messages*), Account (*Settings · Security · Team & access ·
Help & support*). The **live** shell adds 5 more **real, working** items: **Bookings**, **Add a unit** (Owner portal),
**Payout method**, **Documents** (Earnings), **Notifications** (Account). Matching the wireframe *exactly* would
remove 5 working destinations from **every** owner page.

---

## 2. HEADER design + operational gaps

| # | Element | Wireframe | Live | Gap |
|---|---|---|---|---|
| H1 | **Greeting** | `<h1>` **"Good morning, Amara"** — Sora 600 **30px**, time-aware + owner first name | `<h1>` **"Overview"** — Sora 600 **24px**, static page title | **Design + OPERATIONAL:** needs a real time-aware greeting + the owner's first name; 30px not 24px. |
| H2 | **Notification bell** | `42px` glass circle, blur(22px) saturate(1.15), gradient unread badge `{{ unreadCount }}`, the panel | `<x-notifications.bell>` — `38px` (`.vy-bell`), the REAL panel already wired | **Design:** 38→42px + glass treatment. **Operational: already REAL** (reuse — don't rebuild). |
| H3 | **Account menu** | a **dropdown** account menu — "Amara Okafor · AO ▾" (`dc-import "Owner Account Menu"`, 190×44) | a **static** chip (avatar + name) + a **separate** logout icon button | **Design + OPERATIONAL:** the wireframe is a real account dropdown (→ profile / security / sign out); live is a static chip. |

---

## 3. SECTION-BY-SECTION (the 11 wireframe sections)

For each: **DESIGN gaps** (wireframe token → live) + **OPERATIONAL gaps** (real backend source · exists/build) from
the verified backend inventory.

### §1 Sidebar — see §1 (G5–G10) + the nav-set decision (§5-D2).

### §2 Header — see §2 (H1–H3).

### §3 Attention bar — "Needs your attention"
- **DESIGN:** live renders a single `.vy-card--accent` **"Action centre · Soon"** stub (alert icon + one line + "Soon"
  tag). Wireframe = a full section: glass `rgba(255,252,248,.6)` + border `rgba(232,112,58,.28)` radius 20; a `38px`
  gradient icon square (alert-triangle when items / check when empty); `<h2>` "Needs your attention" Sora 600 16.5px +
  a gradient **count** badge `{{ attnCount }}`; a `{{ attnSubtitle }}`; **"Open Action center" →** link; a row of
  chips (each `min-width 240px`, `34px` icon tile, title 13px/600, sub 11.5px, a status **tag pill** `{{ c.tag }}`);
  an optional **"+N more"**. Empty state = green check + honest "caught up".
- **OPERATIONAL — REAL, exists:** `OwnerMyUnits::attentionItems()` (`app/Livewire/Owner/OwnerMyUnits.php:226-280`)
  already builds the REAL signals: **tax-hold** ("Add tax details to release payout" → `owner.tax`, when a finalized
  statement exists and `!owner->hasTaxDetails()`), **missing payout method** (→ `owner.payout-method`), **agreement
  renewing ≤30d** (→ `owner.agreement`). It lives **inline** in one component → **extract to a shared
  `App\Support\Owner\OwnerAttention` service** (anti-drift) and read it from both. Empty → honest "caught up".
  **REFUSE (#39):** the wireframe's "Rate change proposal · Approve" chip is a fabricated signal with no backend
  (`OwnerMyUnits.php:218-223` says so) — not rendered.

### §4 The four action buttons — Quick actions
- **DESIGN:** live = a "Quick actions" eyebrow + 4 **disabled** `.vy-pill.is-soon` buttons (no icons) + "Soon" tag.
  Wireframe = 4 **working** glass pill buttons (`height 40`, radius 30, `rgba(255,252,248,.62)` blur(22px), border,
  shadow, `13px/500`), each with a `16px` `#C2410C` icon: **View calendar · Message guests · Download statement ·
  Contact support** (that order; no "Quick actions" label; no "Soon").
- **OPERATIONAL — REAL, all routes exist:** View calendar → `owner.calendar`; Message guests → `owner.messages`;
  **Download statement** → `owner.statements.pdf` for `statements()->first()` (the latest), **gated
  `@ownercan(CAP_EXPORTS)`** (co-owner/accountant) — render as a plain link for roles without export, or hide;
  Contact support → `owner.help`. All live routes (backend inventory §8, §10).

### §5 Hero "This month" card
- **DESIGN:** wireframe hero is **plain glass** (border `rgba(255,255,255,.75)`, **not** accent), eyebrow "This month ·
  September 2025", figure Sora 600 **52px** tabular-nums, a **"+8% vs August"** pill `bg rgba(217,102,46,.13)` color
  `#B4400F` (terracotta) with an up-arrow. Live: `.vy-card--accent` (accent border), figure **44px**, delta uses
  `.vy-delta--up` **green** / `.vy-delta--down` amber (different tone). → drop accent border, 52px, terracotta delta pill.
- **OPERATIONAL — REAL, already wired:** this-month net + the **MoM delta** are already computed
  (`OwnerDashboard::hero()` lines 103-138 — statement[0] vs statement[1]; `null` when <2 statements → honestly no pill).
  The "vs August" label is real. ✅ No new backend; the delta already exists.

### §6 Stats — Total units · Occupancy · Earned YTD
- **DESIGN:** wireframe = 3 **glass cards** (radius **20**, padding 18/20, full `--vy-shadow`), each a **link** (Units→My
  Units, Occupancy→Reports, Earned→Reports) with a **↗ arrow icon** top-right; value Sora **600 32px**; **no** sub-line.
  The **Occupancy** card additionally shows **"78%"** + **"+6%"** (color `#C2410C`) + a **94×32 sparkline SVG**. Live =
  `.vy-kpi` cards (radius **12**, padding 16/18, `--vy-shadow-sm`, value 30px/**700**, a **sub** line, **not** links, no
  arrow), and Occupancy is a **deferred "—" / "Not yet available"**.
  → radius 20, links, ↗ arrow, 32px/600, drop the sub-line (or keep honest), real occupancy card with sparkline.
- **OPERATIONAL:**
  - **Total units** — REAL (`units()->count()`). ✅
  - **Earned YTD** — REAL (`metrics()` Σ statement net in the latest year; + paid-to-date). ✅
  - **Occupancy % + delta + sparkline** — the **primitive now EXISTS** (`OwnerOccupancy::forMonth($ownerUnitIds,$month)['pct']`
    — genuinely owner-unit-scoped, `app/Support/Owner/OwnerOccupancy.php:48-90`; this was DEFERRED on the dashboard only
    because the old `OwnerPortalService::occupancy()` was room-type-wide — that limitation is **superseded** by Batch B).
    The **delta** = this-month vs last-month `OwnerOccupancy`; the **sparkline** = `PerformanceMetrics::monthlyTrendForOwnerUnits()`
    (`PerformanceMetrics.php:151-173`) — all **REAL owner aggregates**. Thin/no data → honest empty (no fabricated 78%).

### §7 Recent statements
- **DESIGN:** wireframe title Sora **500 16px** + **"View all"** real link (→ Statements); rows `grid 1fr auto auto`
  (month/sub · status pill `{{ st.pillBg/pillColor }}` · amount Sora 500 14.5px + chevron). Live: `.vy-card-title`
  (600/15px) + **"View all · Soon"** stub tag (not a link); rows `.vy-list-row` with the amount in **accent** color.
  → title 16/500, "View all" → `owner.archive` (the Statements nav target), amount neutral (pill carries the tone).
- **OPERATIONAL — REAL:** `recentStatements()` (take 6, each links `owner.statements.show`). ✅ Only the "View all"
  needs to become a real link.

### §8 Earnings chart
- **DESIGN:** wireframe period toggle = a **segmented control** in a pill container (`bg rgba(120,70,40,.06)` border);
  bars are **full-width** (radius `6/6/0/0`), each a **link to the statement**, with a styled **dark hover tooltip**
  (`{{ m.full }}` + `{{ m.exact }}`); a dashed **goal line** + `{{ goalLabel }}`. Live = 3 separate `.vy-pill`
  toggles; `.vy-bar` max-width 44px radius `8/8/4/4`; native `title=` tooltip; **no** goal line; the 3M/6M/12M Alpine
  windowing is correct. → segmented toggle container, full-width bars + radius, a real hover tooltip card.
- **OPERATIONAL — REAL:** `earningsSeries()` (last 12 finalized statements) already drives the bars. ✅
  **REFUSE (#39):** the **"€9k goal"** dashed line has **no backing column** (`OwnerBookings.php:48-50`) → **omit it**
  (not a fabricated target).

### §9 Next-payout dark rail
- **DESIGN:** wireframe = a **halo glow** (`radial(ellipse, rgba(232,112,58,.38))` blur(26px)) behind a dark panel
  `linear-gradient(160deg,#3A2E27,#251C17)` radius 20 shadow, `20px` pad. **Active:** "Next payout" + amount Sora 600
  30px white + "Est. 3 Nov 2025" + an **"IBAN ···4821"** gradient pill. **Paused:** pause-icon tile + "Paused" + copy +
  a **"Resume payouts"** link. Live = `.vy-card--dark` (amount + a status badge + "Payout method setup is coming
  soon."), **no** halo, **no** IBAN, **no** date, **no** paused/resume. → add halo, IBAN pill, the paused/active branch.
- **OPERATIONAL:**
  - **amount** — REAL (`hero()['next_payout_display']`). ✅
  - **masked IBAN** — REAL (`Iban::masked($m->iban_last4)` via `OwnerPayoutMethodService::methods()` default-first,
    `OwnerPayoutMethod.php:62`). **CAP_PAYOUT-sensitive** (co-owner only) — render only when the acting role can see it
    + a method exists; honest empty otherwise.
  - **paused state** — DERIVABLE REAL: pending payout **AND** `!owner->hasTaxDetails()` (the tax-hold —
    `OwnerPayoutService.php:113-116`) is the ONLY real "paused". Show "Resume/settle" → `owner.tax`/`owner.payouts`.
  - **estimated date** — **NO backend** (`OwnerPayouts.php:39-41`) → **omit "Est. 3 Nov 2025"** (never fabricate a date).

### §10 My units — per-unit occupancy
- **DESIGN:** wireframe rows = `{{ u.name }}` + `{{ u.pct }}` + a filled **progress bar** (gradient 90deg
  `#F0A57A→#E8703A`, width `{{ u.pct }}`). Live = code + room_type + a **static `.vy-skeleton`** (deferred).
- **OPERATIONAL — REAL, exists:** per-unit occupancy via `OwnerOccupancy::forMonth()` `per_unit` (the exact logic
  `OwnerMyUnits::unitCards()` already uses, `OwnerMyUnits.php:141-163`). Reuse it → real % + filled bar. Honest "—"
  when a unit has no room-nights.

### §11 Upcoming stays
- **DESIGN:** wireframe = "Upcoming stays" + "Next 30 days"; a **sort** toggle (Earliest/…) + a **Filter** dropdown
  (**Source** options w/ counts + **Status** options); booking rows (`{{ b.dot }}` `{{ b.guest }}` `{{ b.value }}` /
  `{{ b.detail }}` `{{ b.statusLabel }}` `{{ b.date }}`); an empty "No stays match these filters" + Clear; a footer
  "Gross booking value" + **"View all bookings" →**. Live = a single `.vy-deferred` **"Not yet available … never shown
  on the owner portal."** (fully stubbed).
- **OPERATIONAL — REAL & PERMITTED (name-only), the biggest build:** reuse `OwnerBookings::buildRow()` filtered to
  `ReservationStage::UPCOMING` and **clipped to `check_in ≤ today+30`** (no pre-built 30-day method — build a small
  owner-scoped query). Each row already exposes **guest NAME** (name-only, #28-safe — `OwnerBookings.php:140,188-197`),
  **value** (owner net via `OwnerLedgerAttribution`; future stays honestly "Not yet attributed"), **status** badge, and
  **date**. Working **Source/Status filters** (Livewire) + a real **gross booking value** footer + **View all** →
  `owner.bookings`. **⚠️ #28 posture — see §4.**

---

## 4. THE #28 PERIMETER — definitive (the load-bearing security finding)

- **Invariant #28 permits guest NAME on owner surfaces (name-only); it forbids email/phone/contact.** Proven by the
  live owner **Bookings** page, which renders the guest name and is locked by a test:
  `tests/Feature/OwnerBookingsTest.php` "renders the guest name but never their contact details" — `assertSee('Lars
  Andersen')` + `assertDontSee` the email + phone. Booking **value** (as owner **net**) is allowed.
- **BUT the *current dashboard* takes a stricter, dashboard-specific stance:** the Upcoming-stays stub says *"Guest
  personal details stay private and are never shown on the owner portal,"* and `tests/Feature/OwnerDashboardViewTest.php:43-58`
  asserts a guest name is **NOT** shown on the dashboard. **That is a dashboard choice, not invariant #28.**
- **Consequence:** rendering real Upcoming stays with guest name (as the wireframe shows and the founder directs —
  "guest names only, no PII beyond the existing perimeter") is **within #28** and consistent with owner Bookings, but
  it **reverses the dashboard's own prior copy** and **requires updating `OwnerDashboardViewTest`** (assert name shown,
  email/phone never). Email/phone stay forbidden; value = owner net. **This is Decision D1 (§5).**

---

## 5. DECISIONS for the founder (before Phase 2 build)

- **D1 — Guest names on Upcoming stays (recommend: YES, name-only).** The founder's brief directs real upcoming
  bookings with "guest names only". This is within #28 (owner Bookings already does it). It **reverses the dashboard's
  current "never shown on the owner portal" copy** and updates `OwnerDashboardViewTest`. Email/phone stay forbidden;
  value = owner net (not guest-paid gross). **Proceed name-only unless you object.**
- **D2 — Sidebar nav set (recommend: match the visual EXACTLY, KEEP the 5 extra real items).** The wireframe sidebar
  omits Bookings / Add a unit / Payout method / Documents / Notifications — all **real, working** owner pages. The shell
  is shared, so removing them strands those destinations on **every** owner page (a functional regression, and it
  disturbs existing pages — against the standing rule). **Recommend:** reproduce the wireframe's exact glass/active/
  hover/label/icon treatment, but keep the additional real nav items in their groups. (Alternative: match the reduced
  set exactly and reach the 5 pages only via in-page links — a functionality loss. Your call.)
- **D3 — Shared-shell blast radius (informational).** G1–G10 (background, orbs, sidebar tokens, header) live in the
  shared owner layout/shell → they change **all** owner pages, not just Overview. That is the intended "one system"
  outcome; the whole suite staying green is the proof nothing broke. The **section** work (§3–§11) is dashboard-local.

---

## 6. Honesty guardrails (MUST hold — no fabrication, #39/#28/#17)

Render the real value or an honest empty state; **never** the wireframe's demo fill:
- **Omit** the "€9k goal" chart line (no goal column). **Omit** the "Est. 3 Nov 2025" payout date (no scheduled-date
  field). **No** "Scheduled/On hold" payout state — only the real **tax-hold** counts as "paused".
- **No fabricated occupancy** — real `OwnerOccupancy` aggregate or honest "—"/"Not yet available" when thin.
- **Guest email/phone NEVER**; guest **name only**; value = **owner net** (future stays "Not yet attributed", not €0).
- **REFUSE** the fabricated "Rate change proposal" attention chip (no backend). Real attention items only.
- **Integer money (#17)** throughout; every figure **owner-scoped (#28)** via `OwnerScope`/`OwnerContext`.
- **Reuse, don't rebuild:** the notification bell/panel (`<x-notifications.bell>`, already in the shell), the shared
  chart, `OwnerOccupancy`, `OwnerBookings` row logic, `Iban::masked`, `FormatsOwnerData`. Extract the attention feed to
  ONE shared `OwnerAttention` service (anti-drift) read by both the dashboard and My Units.

---

## 7. Cross-reference — `docs/design/DIVERGENCE-REGISTER.md` (line 126)

The 2026-07-25 pass classified the Owner Dashboard **"AT PARITY"** and **refused** (KIND-2): the attention task-list
(→ "Soon"), the occupancy 78%/+6% sparkline + the +8%/€9k-goal trends (#39 → "Not yet available"), the multi-month
statements history, and the Upcoming-stays guest names + amounts (then read as a #28 breach). **This audit re-opens all
but the genuinely-unbacked ones:** the attention feed, occupancy, upcoming stays, and the MoM delta are now **REAL**
(their backends shipped in Batch B / Phase 2 *after* that pass); the €9k goal + estimated date stay **refused** (still
no backend); the guest-name refusal is **corrected** — #28 permits name-only (D1). The register row will be updated to
**RESTRUCTURED (exact-match) + operational** at the end of Phase 2.

---

## 8. Build plan (Phase 2 — gated)

**⛔ GATE 1 — exact design match (presentation).** Extend the shared `--vy-*` system (add the missing tokens like Batch
1 did — the peach-diagonal background var, the orb/streak layers, the terracotta-active nav state, `52px` hero, the
`↗` KPI-link cards, the segmented chart toggle, the IBAN pill + halo, the account-menu dropdown). Build the markup to
**receive real data** (not hardcoded). Honor `prefers-reduced-motion` (gate the orbs, like the wireframe's `{{ motion }}`).
Verify side-by-side vs `:8899`; suite green + PHPStan L5 + Pint clean; commit.

**⛔ GATE 2 — real backends (operational).** Wire every section to the real sources in §3 (extract `OwnerAttention`;
wire `OwnerOccupancy` KPI + per-unit + sparkline; the upcoming-stays query; the masked IBAN + paused-state; the
download-statement + account-menu actions). Honest empty states; no stubs; no fabrication. New tests (owner-scoped,
reconciling, #28 name-only asserted, the honesty omissions asserted); update `OwnerDashboardViewTest`. Suite green +
PHPStan L5 + Pint + `migrate:fresh --seed` clean. Browser-verify design **and** real data. Commit + push. Consolidate
docs (this file → gaps-fixed, DECISIONS.md, DIVERGENCE-REGISTER.md row, PROJECT-STATE.md).

---

*Phase 1 complete — no code changed. Awaiting the go / the D1+D2 calls before the GATE 1 build.*
