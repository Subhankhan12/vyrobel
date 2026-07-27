# Owner "My Units" + "Add Unit" — Wireframe-Exact Audit (2026-07-27)

**Goal:** make the LIVE owner **My Units** (`/owner/units`, `App\Livewire\Owner\OwnerMyUnits`) and **Add Unit**
(`/owner/add-unit`, `App\Livewire\Owner\OwnerAddUnit`) pages match their decoded wireframes
(`docs/design/raw/Owner My Units.html`, `Owner Add Unit.html`) **EXACTLY** and wire every interaction to a
**REAL** owner-scoped backend (no stubs, no fabrication). **Phase 1 read-only gap report — no code changed.**

Both wireframes were decoded from their `dc-runtime` bundles (JSON.parse the `__bundler/template` → the `<x-dc>`
markup + inline-style tokens + `{{ }}` placeholders). The shared owner chrome (peach background, ambient
orbs/streaks, frosted terracotta-active sidebar, greeting/bell/account-menu) already exists from the Owner-Overview
work — **REUSE it** (both live pages already inherit it). The bar is now **exact match**; prior deferrals in
`docs/design/DIVERGENCE-REGISTER.md` (My Units "AT PARITY"; Add Unit "INTENTIONAL DIVERGENCE") are **re-opened** —
but the honesty rule (#39) and the guest-PII perimeter (#28) still bind (see the decisions in §4).

---

## 0. The operational-reality map (from a full backend inventory)

| Control | Real backend TODAY? | Where |
|---|---|---|
| Stats (units · avg occupancy · earned this month) | ✅ REAL | `OwnerOccupancy` · `OwnerUnitAnalytics` · `OwnerLedgerAttribution` |
| Per-unit occupancy / nights / revenue / nightly rate | ✅ REAL | `OwnerMyUnits::unitCards` (Batch B) |
| Best performer | ✅ REAL | `OwnerMyUnits::bestPerformer` |
| Attention items | ✅ REAL (3 signals, **links only**) | `OwnerAttention` (tax-hold · payout-method · agreement-renewal) |
| **Edit pricing** | ✅ REAL — but **per-ROOM-TYPE, exclusive-owned only** | `OwnerRateService::apply` → `owner.rates.apply` (POST, CAP_ACTIONS) |
| **Block / unblock dates** | ✅ REAL — **per-unit** | `OwnerBlockService::block/release` → `owner.calendar.block/release` |
| View unit | ✅ REAL | `owner.units.show` |
| Add unit (submit) | ✅ REAL — creates a `UnitRequest` (**pending**), NOT a `Unit` | `OwnerUnitRequestService::submit` → `owner.add-unit.submit` |
| **Message guest** | ❌ NO backend — owner↔guest channel does not exist | only owner↔operator-staff (`OwnerMessageActionController`) |
| **Action-center approve / decline / snooze** | ❌ NO backend — no owner "decision" entity anywhere | attention items are informational links (#39) |
| Export calendar CSV · Sort/filter units | ❌ NO backend | (both small/buildable) |
| Add-Unit **photo upload** (owner) | ❌ NO owner route (operator room-type pattern exists) | `RoomTypePhotoController` is the secure pattern to reuse |

**Owner write discipline (confirmed):** every owner write is a **controller POST/DELETE gated by
`EnsureOwnerCapability`** (never `/livewire/update` — the scope-bypass boundary, invariant #20), audited to
`audit_events`. Interactive filters/steppers must be **client-side (Alpine)** over server-rendered real data (the
Overview pattern). Capabilities: `CAP_ACTIONS` (block/rates/requests) + `CAP_PAYOUT` = **co-owner only**;
`CAP_CALENDAR` = co-owner + view-only.

---

## PAGE A — MY UNITS

The live page **already inherits the new shell chrome** and already renders the REAL stats / per-unit metrics /
best-performer / attention items. The gaps are: (1) the header/add-unit placement, (2) the **rich collapsible
action-center** (entirely missing), (3) the **unit-card interactions** (edit-pricing stepper, message-guest panel —
disabled stubs), (4) the **interactive block calendar** (day-detail, per-unit filter, block/export — stubs), and
(5) the exact card/calendar visual treatment.

### §A1 Header + stats
- **DESIGN:** wireframe top row = `<h1>My units</h1>` (Sora 600 30px) + a gradient **"Add unit"** pill (→ Add Unit)
  + the bell + account menu, all inline. Live: the "Add unit" button is a **disabled "coming soon" stub** rendered
  BELOW the topbar (misplaced). Stats band = 3 glass cards (Units · Avg occupancy `78% +6%` · Earned this month) —
  live matches closely (real Units / `3%` occupancy / `€0.00` earned), but the wireframe's occupancy shows a `+6%`
  delta and the card is plain-glass (no sub-line).
- **OPERATIONAL:** stats REAL ✓. The "Add unit" button just needs to become a **real link** to `owner.add-unit`
  (@ownercan(CAP_ACTIONS)). The occupancy `+6%` delta = real MoM (like the Overview) or omit honestly.

### §A2 Action center ("Needs your attention") — the big interactive piece
- **DESIGN (entirely missing):** wireframe = a **collapsible** section (header with a pinging badge + count +
  subtitle + Expand/Collapse chevron; `vyPing`/`vyMenuIn` animations; grid-rows reveal). Expanded: **Needs-attention
  / Snoozed tabs** (counts); **Unit** + **Status** filter menus (Approvals/Payouts/Tasks, with counts + check);
  **bulk** Approve-all (→ confirm "Approve N items?") / Snooze-all / Mark-all-read; **filter chips** ("Filtered by" +
  remove + Clear all); item rows (icon · title · tag · sub · unread dot) with either a **link** action or a
  **decision** (Decline / Approve → confirm-decline + Snooze menu: Tomorrow / 1 week / Custom date); **Undo**; an
  **empty "all caught up"** state; a **Snoozed panel** (Unsnooze). Live = a plain, always-open list of the 2 real
  attention items with fix-it buttons — none of the collapsible/tabs/filters/decisions/snooze.
- **OPERATIONAL — the honesty crux (Decision D1):** the REAL content is the **3 `OwnerAttention` signals**, which are
  **fix-it LINKS**, not approve/decline decisions. **There is NO owner "approve/decline" entity anywhere** — the
  prototype's rate-change "proposals" are explicitly unbuilt (#39). So the approve/decline/bulk-approve/confirm
  workflow **has no real backend** and would be fabrication to fake. **Snooze/dismiss + mark-read ARE buildable**
  (persist per-owner-per-signal). **Plan (honest):** reproduce the action-center's exact design (collapsible, tabs,
  filters, chips, snooze, undo, empty, snoozed panel), populate it with the **real attention items as link items**,
  build **REAL snooze/dismiss + mark-read + undo** (a new owner-scoped `owner_attention_states` store + controller
  routes, audited), and **honest-empty the approve/decline** (no fabricated decisions). See D1.

### §A3 Unit cards
- **DESIGN:** wireframe card = thumbnail band (gradient) · name (Sora 500 16px) · sub · **Active** pill (terracotta)
  · Occupancy label + % + gradient bar + "This month" · a 2-col Nights-sold / Revenue block · Nightly rate (+ a
  "Custom" badge) · **Edit pricing** + **Message guest** pill buttons · **View unit** link. Live: has the thumbnail
  (currently a **broken/garbled cover image** overlay — a real bug), name/sub, Active + ★Best badges, occupancy bar,
  nights/revenue, nightly rate, and **disabled** Edit-pricing / Message-guest (`title="Coming soon"`). → fix the
  thumbnail, match the exact card layout, and make the two buttons real (below).
- **OPERATIONAL:**
  - **Edit pricing** — REAL via `OwnerRateService::apply` (→ `owner.rates.apply`, CAP_ACTIONS). Build the inline
    stepper (− / `€ draftVal` / +, Reset-to-base, Cancel/Save) as a **controller POST** (not Livewire). ⚠️ **Caveat:**
    owner rates are **per-ROOM-TYPE and only for exclusively-owned room types** — a unit sharing a room type with
    operator/other-owner inventory is **not** editable. For a non-editable unit, show an honest disabled/explained
    state (never a fake save). The "base rate" is the current `rate_calendar` nightly for the room's primary plan.
  - **Message guest** — **NO owner→guest channel** (Decision D2). Direct owner→guest would breach #28. The real
    existing channel is owner↔operator-staff. Honest options in D2.
  - **View unit** — REAL (`owner.units.show`) ✓.
  - The end-of-grid **"Add a unit"** dashed card → link to `owner.add-unit` (currently "List a new property · Soon").
  - **Sort + per-unit filter** — client-side (Alpine), over the real cards.

### §A4 Block calendar
- **DESIGN:** wireframe = month nav (`calMonthLabel`, prev/next) · **Block dates** + **Export** + a **per-unit
  filter** menu · a 7-col day grid with **booked-intensity** cells (0..N units booked → colour buckets) · a legend
  (Booked buckets / Free) · a summary line (`79 nights · 85% occupancy · €12,805`) · and a **day-detail** on click
  (date · booked units w/ guest initials + names · revenue · check-in/out chips · "Free day"). Live = a real month
  grid (real booked days) + **disabled** Block-dates / Export, no per-unit filter, no day-detail.
- **OPERATIONAL:**
  - **Block dates** — REAL via `OwnerBlockService::block/release` (→ `owner.calendar.block`/`.release`, CAP_ACTIONS,
    per-unit). Wire a real block/unblock flow. (The existing `owner.calendar` page — `OwnerBlockCalendar` — already
    does per-unit booked/blocked/open; the My Units heatmap can offer inline block on a real controller POST.)
  - **Day-detail** — REAL from the owner-scoped bookings (guest **name-only** #28, revenue owner-net). Buildable.
  - **Export CSV** — no backend → build a real owner-scoped month CSV (small) or honest-defer (recommend build).
  - **Per-unit filter** — client-side.

### §A5 Best performer
- REAL ✓ (matches the wireframe's dark halo panel). Minor visual alignment only.

---

## PAGE B — ADD UNIT

- **DESIGN (total rebuild):** wireframe = a **4-step wizard** (progress rail) — **The basics** (name · property-type
  chips · location chips · street address; "Name your unit to continue") → **Space & capacity** (bed/bath/sleeps
  steppers · amenity toggle chips) → **Photos** (cover drop-zone + more · "professional shoot" note) → **Rate &
  terms** (base rate/night · cleaning fee · min-nights stepper · a dark "Managed by … 15% commission / You keep
  ≈{net}/night" panel · **Submit for review**) — plus a **sticky live-preview card** (cover · "Pending review" badge
  · name/sub · sleeps/bd/ba · amenities · rate) reflecting the entered data, and a **Done** state (check badge ·
  "Thanks, {name}. … within two business days." · a 3-item checklist · Back to My units / Add another / Message the
  team). Live = a **single 3-field form** (name · type dropdown · optional details) + a "Your requests" list. **No
  wizard, no steps, no capacity/amenities/photos/rate, no live preview, no done state.**
- **OPERATIONAL — Decision D3:** the live submit already creates a **REAL `UnitRequest` (pending)** row (owner-scoped,
  audited) — but **no real `Unit`** (a `Unit` needs an operator-chosen property + room_type the owner can't supply;
  there's no `Unit` lifecycle/status column). The register previously **refused** the wizard's live-preview + €/night
  as KIND-2 fabrication ("an owner can't price a unit that doesn't exist"). **Honest resolution:** build the wizard
  UI + capture ALL entered fields (kind · location · address · beds/baths/sleeps · amenities · requested rate ·
  cleaning · min-nights · photos) into a **rich, real `UnitRequest` (pending-review)** — the "live preview" then
  reflects the owner's **own entered data** honestly labelled "Pending review" (their proposed values on a pending
  request, NOT a live priced unit), and the Done state confirms the **real** request (which appears in "Your
  requests" + the operator `unit-requests` screen). The requested rate is a **proposed** figure on the request, not a
  live `rate_calendar` write. **Photo upload** = a new owner-scoped controller reusing the `RoomTypePhotoController`
  secure pattern, attached to the request. Whether the submission auto-becomes a live `Unit` needs the operator's
  manual creation step (existing design) — see D3.

---

## 4. DECISIONS for the founder (before Phase 2)

- **D1 — Action-center approve/decline/snooze (recommend: exact design + real items + real snooze; honest-empty the
  decisions).** There is **no real owner approve/decline backend** — faking approvals violates #39. Reproduce the
  action-center's exact design and interactions, populate with the **real `OwnerAttention` items** (fix-it links),
  build **real snooze/dismiss + mark-read + undo** (persisted, audited), and render the approve/decline **decision**
  UI only when a real decision entity exists (today: none → honest-empty). *Alternative:* build a whole new owner
  "approvals" subsystem (e.g. operator→owner rate/expense proposals the owner approves) — a large net-new feature the
  wireframe implies but the product doesn't have. **Recommend the honest-real-subset.**
- **D2 — "Message guest" (recommend: relay to the operator team, name-only).** No owner→guest channel exists;
  direct owner→guest would breach **#28**. Options: **(a)** wire the compose to the **real owner↔operator-staff**
  channel (`OwnerMessageActionController::start`) — the owner messages the management team *about* that unit/guest
  (name-only), the real honest path; **(b)** honest "no direct guest messaging — message your team" copy; **(c)**
  omit the button. **Recommend (a)** — a real, audited message on the existing channel, framed as "message the team
  about this unit's guest" (never owner→guest directly).
- **D3 — Add Unit submission (recommend: rich real `UnitRequest` + real photo upload; live Unit stays operator-made).**
  Build the full wizard; submitting creates a **rich, real, pending `UnitRequest`** (all wizard fields + uploaded
  photos), owner-scoped + audited; the live-preview shows the owner's entered data (honest "Pending review"); the
  Done state confirms the real request. The request **becomes a live `Unit` via the operator's manual step** (the
  existing "operator confirms" pattern — a `Unit` can't be created from owner input alone). *Alternative:* also build
  the operator approve→create-`Unit` transition now (a larger operator-side build). **Recommend the rich-request core;
  the operator auto-create is optional/later.**

---

## 5. Build plan (Phase 2 — gated per page)

- **⛔ GATE 1 — My Units exact design** (visual layer; extend `.vy-ov-*`/shared tokens like the Overview): header +
  add-unit link, stats band, the **full action-center layout** (collapsible + tabs + filters + chips + decision/link
  rows + snooze menu + undo + empty + snoozed panel), the exact **unit-card** layout (fix the thumbnail, occupancy
  bar, edit-pricing + message-guest panels, view-unit), the **calendar** (booked-intensity grid + day-detail + unit
  filter + block/export controls), best-performer. Build markup to receive real data. Suite green; commit.
- **⛔ GATE 2 — My Units real backends:** stats (reuse) · attention items + **real snooze/dismiss/mark-read/undo**
  (new owner-scoped store + controller routes, honest-empty decisions per D1) · **Edit pricing** → `OwnerRateService`
  (per-room-type, honest disabled for shared) · **Message guest** per D2 · **Block dates** → `OwnerBlockService` +
  day-detail (name-only #28) · **Export CSV** · client-side sort/filter. Every write a controller POST, audited,
  owner-scoped (#28), integer money (#17). New tests; suite green; commit.
- **⛔ GATE 3 — Add Unit exact wizard + real submission:** the 4-step wizard + live preview + done state, exactly;
  extend `UnitRequest` to capture the wizard fields + a real owner photo-upload; submit creates the **real pending
  request** (per D3); server-authoritative validation; audited. New tests; suite green; consolidate docs; commit.
- **⛔ GATE 4 — Prove it end-to-end:** via the browser, complete the wizard + upload a photo + submit → confirm the
  real `UnitRequest` exists in the DB (owner-scoped, pending) + shows in "Your requests" (and the operator screen);
  then exercise a My Units action (edit a nightly rate / block a date) and confirm it really persists. Commit + PUSH.

---

*Phase 1 complete — no code changed. Awaiting the go + the D1/D2/D3 calls before GATE 1.*
