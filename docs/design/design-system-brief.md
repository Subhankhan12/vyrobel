# Vyrobel — Design System Foundation · Claude Design Brief (Phase 0)

Paste this into **Claude Design**. Goal: establish the Vyrobel design system (tokens + components + motion) and render the **Owner Statement** screen as the living reference that demonstrates it. Every later screen inherits these tokens.

---

## Product & vibe
Vyrobel is a hospitality SaaS. This is the **owner portal** — where a property owner sees their statements and payouts. The aesthetic is **warm glassmorphism**: an apricot→warm-gray gradient backdrop with soft light-streaks, frosted translucent glass panels, big confident typography, layered depth, and one dark "hero" panel for contrast. Think premium hospitality-fintech — warm, modern, tactile. Fully warm palette: **apricot / terracotta-orange / charcoal — no green, no blue.**

Desktop-first (operators/owners on laptops). Real `backdrop-filter` glass, soft warm shadows, tasteful motion.

---

## Design tokens (use these exact values)

### Background (the app canvas)
```css
background: linear-gradient(135deg, #F6C89B 0%, #F1B896 32%, #EBD9CF 62%, #D9D6D2 100%);
```
Plus 1–2 soft light-streaks over it (very subtle, ~30–40% opacity, blurred):
- a radial white glow, top-right: `radial-gradient(circle, rgba(255,255,255,.35), transparent 68%)`
- a diagonal warm sheen, mid-left: `linear-gradient(120deg, rgba(255,240,220,.5), transparent)` rotated ~28°

### Glass panel (the primary surface)
```css
background: rgba(255,251,247,.60);
backdrop-filter: blur(22px) saturate(1.15);
border: 1px solid rgba(255,255,255,.70);
border-radius: 20px;
box-shadow: 0 8px 30px rgba(150,90,50,.10);
```

### Dark hero panel (accent contrast — for "Next payout", highlights)
```css
background: linear-gradient(160deg, #3A2E27 0%, #251C17 100%);
border-radius: 20px;
box-shadow: 0 10px 30px rgba(60,30,10,.28);
/* text: #FFFFFF headline, #B9ABA0 muted */
```

### Color roles
- **Charcoal text:** primary `#2B2320` · secondary `#8A8079` · muted `#A69A92` · uppercase labels `#9C9089`
- **Terracotta accent:** solid `#C2410C` · bright `#E8703A` · gradient `linear-gradient(140deg,#E8703A,#C2410C)`
- **Accent tint (badges/pills):** bg `rgba(217,102,46,.13)`, text `#B4400F`
- **Avatar gradient:** `linear-gradient(140deg,#F0A57A,#D9662E)`, white initials
- **Hairlines on glass:** `rgba(120,70,40,.10)` rows · `rgba(120,70,40,.16)` emphasis dividers

### Typography
- **Display / headings & big numbers:** a bold geometric sans — use **Sora** or **Poppins** (Google Fonts). Weight 600 for hero numbers and page titles, 500 for section headings. Big and confident (hero figures 40–48px, page title 28–32px).
- **Body / labels:** **Inter** (or the same family), weight 400 body / 500 emphasis.
- Two weights only. Sentence case everywhere. Tight letter-spacing on big numbers (`-.02em`).

### Radius & spacing
- Radius: panels/cards `20px`, pills/badges `30px+` (fully round), small controls `12px`.
- Generous whitespace; panel padding ~18–22px; comfortable row rhythm.

### Shared tokens & component classes (formalised — defined once per realm's `:root` / design system)
These are the named `--vy-*` tokens and `.vy-*` classes the app implements the above with. **Polish Batch 1 (2026-07-26)** defined the ones that were previously referenced but undefined (so they no longer fall back to transparent/plain/unstyled); every realm that uses one now declares it:
- **`--vy-wash: #F6EBE2`** — the faint warm-cream tint used for incoming chat bubbles + tag/badge chips + soft washes. (This is the value the app already rendered everywhere via the `var(--vy-wash, #F6EBE2)` fallback; only the guest portal pages lacked the fallback, so they rendered transparent until this token was declared.)
- **`--vy-shadow-dark: 0 10px 30px rgba(60,30,10,.28)`** — the drop shadow on the dark hero/"Tonight"/feature panels (brief-exact, see "Dark hero panel" above).
- **`.vy-card--accent`** — a card with an accent border (`border-color: var(--vy-accent-border)`), for action cards ("Record a payment", "Actions", "Edit profile", owner NET-TO-YOU).
- **`.vy-pill.is-on`** — the SELECTED state of a range/filter pill: gradient fill + white text + accent shadow (matches the prototype's selected pills and the segmented `.vy-seg-btn.is-on`).
- **`.vy-field` / `.vy-field-label`** — a form field wrapper + its uppercase, letter-spaced, faint label (7px-below, `--vy-faint`).
- **`.vy-flash`** — a notice/toast banner: a neutral glass base tone by default; `.is-ok` (green) / `.is-err` (amber/terracotta) modifiers for success/error.

**Hover / tap-target feedback (Polish Batch 2, 2026-07-26)** — interactive cards/rows respond on hover so nothing feels inert; **applied opt-in to genuinely-clickable elements only** (a static/placeholder card must never get a misleading hover):
- **`.vy-card--tappable`** — mark an interactive card. Adds `cursor: pointer` + on `:hover` a lift `transform: translateY(-2px)` and an elevated shadow `0 18px 44px rgba(150,90,50,.18)` (guest uses a slightly softer `0 16px 40px rgba(150,90,50,.16)`). Prototype-matched (the prototype's card hover is a small lift + soft elevated shadow). Used on: guest trip cards, storefront room cards, owner unit cards, operator property cards.
- **`.vy-row--tappable`** — mark an interactive list row. On `:hover` a subtle warm tint `background: rgba(120,70,40,.05)` + `border-radius`. Used on the guest message-list rows.
- Clickable rows that are anchors are scoped by tag where the class is shared with a static variant, e.g. `a.vy-owner-row:hover { background: rgba(255,255,255,.5) }` (the static `<div>` owner rows are untouched).

---

## Components to define

1. **Sidebar shell** — translucent (glass, or over the gradient). Logo: rounded-square terracotta-gradient tile with white "V" + wordmark "Vyrobel". A small uppercase section label ("Owner portal"). Nav items with a leading outline icon; the **active item is a white frosted pill** with terracotta text/icon and a soft shadow; hover = subtle white wash. (For the later operator admin panel, the sidebar gains grouped sections — "Main menu", "Management" — like a full dashboard; keep the structure ready.)

2. **Glass card** — the primary content surface (spec above). Used for the statement, breakdown, metric panels.

3. **Dark hero panel** — the payout/highlight block (spec above). One per screen, for contrast.

4. **Metric / stat block** — big number (display font, 30–44px), small muted label above, optional small terracotta delta ("+6%").

5. **Status pill** — rounded, accent-tint bg + `#B4400F` text, small leading icon (e.g. check for "Paid"). Also a "Carried forward" style chip.

6. **Buttons** — primary: terracotta-gradient fill, white text, soft shadow, lifts on hover. Secondary: frosted glass (white translucent) with charcoal text. Rounded.

7. **Avatar** — circular, terracotta-gradient bg, white initials; the top-right user chip is a frosted rounded-pill (name + avatar).

8. **Breakdown / ledger rows** — label left (with small muted subtext), amount right. Credits in charcoal, deductions in muted gray with a `−` sign (calm, NOT red — this is normal accounting). Emphasized "Net payable" total row with a top divider and the figure in terracotta.

9. **Mini bar chart** — thin rounded bars in accent tints, the peak bar in the terracotta gradient. Lightweight, decorative-but-real.

---

## Motion / animation language (tasteful, not flashy)
- **On load:** panels fade-in-up (translateY 12px → 0, opacity 0 → 1), **staggered** ~60ms per panel. Easing `cubic-bezier(.22,.61,.36,1)`, ~450ms.
- **Hover:** cards and buttons lift slightly (`translateY(-2px)`) with a touch more shadow; ~140ms.
- **Active nav:** smooth pill background transition ~160ms.
- **Hero number:** a subtle count-up on load (e.g. €0 → €10,370 over ~800ms) — optional but nice.
- **Very subtle** ambient shimmer on the gradient is welcome; keep it slow and faint. No bouncy/gimmicky effects.

---

## Render this screen (the reference implementation): Owner Statement

Layout, desktop width:
- **Left sidebar** (~200px): logo + "Owner portal" + nav [Overview, **Statements** (active), Payouts, My units].
- **Main area:**
  - Top row: page title "Statement" (big display) + a frosted user chip top-right ("Amara Okafor" + AO avatar).
  - Below, two columns:
    - **Left (wide):** a glass **hero summary card** — "September 2025 · Seaside Villas · 3 units", a "Paid" status pill, and a large **€10,370.00 net payable** figure. Under it, a glass **Breakdown card**:
      - Gross room revenue (64 nights · 3 units) +€12,400.00
      - Management commission · 15% −€1,860.00
      - Management fee −€150.00
      - Cleaning fees +€480.00
      - OTA commission · Booking.com −€620.00
      - Prior-period adjustment [Carried forward chip] +€120.00
      - **Net payable €10,370.00** (emphasized, terracotta, top divider)
    - **Right (~210px):** the **dark hero "Next payout" panel** (€10,370, "Est. 3 Nov 2025", a bank-account chip in terracotta), and below it a glass **Occupancy** card (78%, +6%, a mini bar chart with the peak bar in terracotta gradient).

The numbers must reconcile: 12,400 − 1,860 − 150 + 480 − 620 + 120 = **10,370**.

---

## Constraints
- Real `backdrop-filter` blur on glass (this is the whole look — don't fake it with flat fills).
- Warm palette only — apricot/terracotta/charcoal. No green, no blue.
- Accessible contrast: charcoal `#2B2320` on glass, white on the dark panel and on terracotta fills.
- This is the FOUNDATION. Define the tokens/components cleanly (CSS custom properties) so the next screens — owner dashboard, payouts, units, and later the full operator admin panel with its role views — reuse them without redefining.

Deliver: the design system (tokens + the components above) and the rendered Owner Statement screen demonstrating it.
