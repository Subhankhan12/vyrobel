# Remaining horizontal overflow at 375px — diagnosis, then fix

**Measured 2026-09-04** with `chrome-headless-shell` over CDP against `php artisan serve`, logged in as the
real demo operator and the real demo owner (each realm's landing path asserted before measuring).
Metric: `documentElement.scrollWidth − documentElement.clientWidth` at a 375×800 mobile viewport.

## ⚠️ The brief said thirteen screens. There are TWELVE, and one of the three "operator screens" is not a screen.

`/app/schedule/pdf` **is a real PDF download**, not a page:

```
status 200 · content-type: application/pdf · %PDF-1.7 · 881,663 bytes
content-disposition: attachment; filename=schedule-…-week-0.pdf
```

`ScheduleController::pdf()` renders through `PdfService` and returns it as an attachment. A browser told to
navigate there **downloads the file and leaves the previous page on screen** — so a measurement of
"`/app/schedule/pdf` = 95px" is a measurement of `/app/schedule`, which is why the two numbers and their
offending elements were byte-identical. It is a phantom row, not a defect.

## The BEFORE table (all twelve, each diagnosed to a named site)

| # | screen | over | cause | site |
|---|---|---|---|---|
| 1 | `/app/portfolio` | 98px | a 434px `<table class="vy-table">` with **no scroll wrapper** | `livewire/operator/portfolio.blade.php:158` |
| 2 | `/app/schedule` | 95px | inline `grid-template-columns:1fr 1fr` — bare `1fr`, no breakpoint | `livewire/operator/schedule.blade.php:408` |
| 3 | `/owner` | 14px | `.vy-ov-payout-halo` decoration bleeds `left/right:-30px`, unclipped | `layouts/owner.blade.php:523` |
| 4 | `/owner/units` | 14px | the same halo | `layouts/owner.blade.php:523` |
| 5 | `/owner/expenses` | 159px | `.vy-kpi-grid` base is `repeat(3, 1fr)` — bare `1fr`, **no breakpoint at all** | `layouts/owner.blade.php:371` |
| 6 | `/owner/balance` | 157px | the same rule | `layouts/owner.blade.php:371` |
| 7 | `/owner/payouts` | 195px | the same rule | `layouts/owner.blade.php:371` |
| 8 | `/owner/reports` | 113px | `.vy-rep-kpis` `repeat(3,1fr)` **and** `.vy-rep-urow` `1.5fr .8fr .9fr 1fr 1.1fr` | `livewire/owner/reports.blade.php:54, :80` |
| 9 | `/owner/rates` | 232px | two inline `grid-template-columns:1fr 1fr 1fr` field rows | `livewire/owner/rates.blade.php:243, :253` |
| 10 | `/owner/messages` | 106px | `.vy-msg-rail` is `width:320px; flex-shrink:0` inside a `.vy-msg-wrap` that never wraps | `livewire/owner/messages.blade.php:10, :11` |
| 11 | `/owner/calendar` | 121px | `.vy-cal-main { min-width:480px }`, plus `.vy-cal-grid` `repeat(7,1fr)` | `livewire/owner/block-calendar.blade.php:24, :37` |
| 12 | `/owner/notification-preferences` | 36px | `.vy-topbar` never wraps and `.vy-topbar-actions` computes `min-width:auto` | `layouts/owner.blade.php:176, :179` |

⚠️ **Row 12 was not in the brief's list** — it was found by sweeping every owner GET route rather than the
ten the brief named. The brief's tenth owner screen is this one under a name I was not given.

## ⚠️ They are not twelve problems. Eight of the twelve are ONE defect, and it already has a name here.

A bare `1fr` is `minmax(auto, 1fr)`, and that `auto` is a **min-content floor the track cannot shrink below**.
`operator.blade.php:1635-1645` already documents this, calls it "the third instance in this codebase", and
fixes it for the operator shell. Rows 2, 5, 6, 7, 8, 9 are the same defect in six new places; rows 10 and 11
are the same *shape* written as an explicit `width`/`min-width` floor instead of a grid track.

**And the owner shell got half the operator's fix.** `owner.blade.php:799-818` is a verbatim copy of the
operator's `≤860px` block **down to the comment** — but it stops after `.vy-shell` / `.vy-side` / `.vy-main`.
The operator block continues for another 45 lines (topbar wrapping, the decoration containment, the
inline-scroller rule, `.vy-stack { min-width: 0 }`) and **none of that was carried across**. Rows 3, 4 and 12
are precisely the rules that were left behind.

## ⚠️ The withdrawn fix, and why row 1 is not it returning

`operator.blade.php:1694-1701` records a fix that was applied and then **deliberately withdrawn**:

> A `.vy-card:has(> .vy-table) { overflow-x: auto }` rule cleared `/app/portfolio`'s 98px — but turning a card
> into a horizontal scroller can push a card's ACTION BUTTON off-screen inside it […] A fix that hides a
> control is worse than the overflow it removes. […] **THE DURABLE FIX IS A VIEW CHANGE: wrap that table in
> the house `.vy-utable-wrap`.**

That rule is **not** being restored. What the note asked for is done instead, and the question it could not
answer within its own pass is answered here by reading the view: the portfolio table's five columns are
Property · Occupancy · ADR · RevPAR · **Room revenue**, and its `<tbody>` contains **no `<button>`, no `<a>`,
no control of any kind** — the far edge is a number. Nothing can be pushed out of reach because there is
nothing there to push.

---

## The fixes

### The owner shell got the other half of the operator's fix

`owner.blade.php:799` copied the operator's `≤860px` block down to `.vy-main` and stopped. The four rules
that were left behind are now carried across, each with the operator's own reasoning attached:
`.vy-topbar { flex-wrap: wrap }` and `.vy-topbar-actions { min-width: 0; flex-wrap: wrap }` (row 12),
`.vy-stack { min-width: 0 }`, the inline-scroller rule `[style*="overflow-x:auto"] { min-width: 0 }`, and
`.vy-ov-payout-halo { left: 0; right: 0 }` — the decoration containment `.vy-tonight-glow` already has
(rows 3 and 4). Separately `.vy-kpi-grid` gained `minmax(0, …)` and the two breakpoints its own `--4` and
`--2` modifiers already had (rows 5, 6, 7); placed **above** the modifiers so a `--4` or `--2` band still
matches its own later rule and is unchanged.

### Nothing was put behind a gesture, and two things were deliberately not scrolled

The withdrawn fix's lesson governs every choice here: **a fix that hides a control is worse than the
overflow it removes.**

| screen | what sits at the far edge | so it | why |
|---|---|---|---|
| `/app/portfolio` | Room revenue — a figure, no control in the `<tbody>` | **scrolls** in `.vy-utable-wrap` | a row of metrics is one unit of meaning; the card's own "Full report ›" is above the wrapper and does not move |
| `/app/schedule` | a time-off request form · an approve/decline queue | **stacks** | both panels are made of controls |
| `/owner/rates` | six inputs, one of which sets a real nightly rate | **stacks** | three date/number fields at ~105px are not fillable |
| `/owner/calendar` | every day cell **is** a control (click to block, click to release) | **shrinks** | a scroller would put Saturday and Sunday behind a gesture; seven columns that shrink keep the week visible and every day reachable |
| `/owner/messages` | the conversation list, the thread, the composer | **stacks** | its fixed `100vh` height had to go with it, or two stacked panes share one screen |
| `/owner/reports` | month labels and money figures on a bar chart | **steps down a size** | clipping would cut a digit off a money figure |

⚠️ **Checked, not assumed.** Every `button`, `a[href]`, `input`, `select` and `textarea` on the nine
changed screens was measured at 375px and asked whether it falls outside the viewport and, if so, whether a
horizontal scroller can bring it back. **Nothing is unreachable.** Forty controls sit outside the viewport
on two screens — the roster's day links and the payout history's Statement/Remittance links — and both of
those scrollers **pre-date this pass**; on `/owner/payouts` those links previously pushed the whole page
195px sideways instead, so they moved from scrolling the page to scrolling their own table.

### `/owner/reports` took three passes, and the second made it worse

Worth recording because the mechanism is the one this codebase keeps relearning. 113px → 13px (the KPI
band) → **2px** (the hero glow, the third instance of that bleeding-decoration shape) → back up to **13px**.
The step backwards was mine: padding added to the chart to give its centred labels room narrowed each
column until the bar columns' own `min-width: auto` floor started to bite. `owner.blade.php:800` already
says why — *"`minmax(0, …)` AND `min-width: 0` — BOTH, AND THEY FIX DIFFERENT THINGS"* — and I had fixed
the container and not the items. `/owner/calendar` was the same mistake in the same session: the track was
relaxed, the day cells were not, and it moved 121px → 112px.

## After — measured the same way

| screen | before | after | | screen | before | after |
|---|---|---|---|---|---|---|
| `/app/portfolio` | 98px | **0** | | `/owner/reports` | 113px | **0** |
| `/app/schedule` | 95px | **0** | | `/owner/balance` | 157px | **0** |
| `/owner` | 14px | **0** | | `/owner/payouts` | 195px | **0** |
| `/owner/units` | 14px | **0** | | `/owner/rates` | 232px | **0** |
| `/owner/expenses` | 159px | **0** | | `/owner/messages` | 106px | **0** |
| `/owner/calendar` | 121px | **0** | | `/owner/notification-preferences` | 36px | **0** |

**All twelve at zero.** A wider sweep then re-measured **62 screens** — 7 marketing/auth, 24 operator, 26
owner, 5 guest, each realm logged in as its own real demo account with the landing path asserted — and
found **zero horizontal overflow at 375px anywhere, and no regression on a screen that was already clean.**

## What guards it

`tests/Support/overflow_helpers.php` + `tests/Feature/HorizontalOverflowContractTest.php`, extending the
existing shared-helper family (`heading_helpers` · `dialog_helpers` · `label_helpers`) rather than starting
a fourth pattern. It holds three things: the specific declarations that were fixed still carry their floor;
the portfolio table keeps its wrapper **and** the withdrawn `.vy-card:has(> .vy-table)` rule has not
returned; and the count of bare `fr` tracks does not grow.

⚠️ **The ceiling is 236 and it is not a target.** 236 sites across the views still write a bare `fr` track.
They are not all defects — a track only overflows when its content is wider than the room it is given, and
most of these never are. Sweeping them is separate work with its own measurement, and calling a page
"fixed" that nobody has measured would be a fabrication. The guard holds the direction, not a clean sheet.

⚠️ **And it is a source contract, not a measurement.** Whether a page scrolls sideways can only be answered
by a browser at a real viewport. A green here means the rules are still written, not that the pages were
re-measured.
