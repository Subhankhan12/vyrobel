# Click handlers no keyboard can reach — the class, not the two instances

**2026-09-04.** Proven in `chrome-headless-shell` over CDP against `php artisan serve`, logged in as
**Demo Staff** (`demo-staff@vyrobel-demo.test`, landed `/app/dashboard`) and **Demo Owner**
(`demo-owner@vyrobel-demo.test`, landed `/owner`) — each identity asserted before measuring.

## ⚠️ The brief named two surfaces. Sweeping the class found the real set is five, and three needed fixing.

| # | surface | what the click DOES | therefore | shipped |
|---|---|---|---|---|
| 1 | `maintenance-list.blade.php:114` | sets `detail`, which **opens a modal dialog** | a `<button>` | a real opener on the title |
| 2 | `owner/units.blade.php:60` | **expands a disclosure in place** | a disclosure control | the Expand/Collapse pill made a real `<button>` |
| 3 | `housekeeping-board.blade.php:174` | **collapses/expands a lane** | a disclosure control | ⚠️ **not reported by anyone** — found by the sweep; the whole header became a native `<button>` |
| 4 | `owner/units.blade.php:450` | **selects a day** in a 30-cell occupancy heat-map | a grid needing a keyboard model | ⚠️ **still unreachable — reported, not built** (below) |
| 5 | `maintenance-ticket-detail.blade.php:356, :429` | dismisses an overlay | a backdrop shortcut | legitimate; each overlay has a real close `<button>` inside |

Four further hits were **not** defects and the guard now says so in its own terms: three `<form>` elements
carrying a bare `@click.stop` — a modifier with **no expression**, which performs no action at all and
exists only to stop a click reaching a backdrop.

## What each one actually is, because the element depends entirely on it

**The work-order row opens a dialog.** `@click` sets `detail`, and `detail` drives the panel at `:305`
which already carries `role="dialog"`, `aria-modal="true"` and `tabindex` — so this is an opener for an
existing contract dialog, and **no thirty-eighth dialog was created**.

**The action centre and the lane header are disclosures.** Both expand content in place; neither navigates
and neither opens a dialog.

## ⚠️ `<details>`/`<summary>` was checked first, and does not fit — one sentence, as asked

Alpine already owns the action centre's `open`, four elements read it (the toggle, the Collapse/Expand
label, the chevron's rotation and the panel's `is-open` class), and the panel animates
`grid-template-rows: 0fr → 1fr`, which requires the content to be **rendered while collapsed** — something
a native `<details>` deliberately does not do — so a native disclosure would need both the state and the
animation mirrored, and two sources for one state is exactly the drift this codebase keeps removing.

## A path was added; none was taken away

Every row and header **keeps its whole-area click**. That was the constraint that mattered: clicking
anywhere on a row is a convenience people use all day on a busy desk, and replacing a whole-row target with
a small link fixes one group and annoys another.

- **The work-order row** could not become a `<button>` — it contains an `<input type="checkbox">` and an
  "Assign" `<button>`, and a button may not contain interactive content. **The earlier pass was right to
  refuse.** So the title became the real control, and it mirrors the row exactly *including select mode*:
  in `selecting` the row toggles selection rather than opening, and so does the title, or the keyboard and
  the mouse would do different things from the same place.
- **The action centre** could not become a `<button>` either — its header wraps an `<h2>` and block `<div>`s.
  But the Expand/Collapse control inside it was **already a button in everything except its tag**: a pill
  with a hover lift reading "Expand"/"Collapse". Only the element was wrong.
- **The lane header CAN be the native element**, and that is the shape to prefer when the content permits:
  it holds only spans and an svg, so a `<button>` wraps it and the entire header stays the target it was.
  Nothing is traded away.

⚠️ **No stretched link was used**, so its checkbox-swallowing trap does not arise.

## The checkbox stays independent — tested both directions by keyboard

It drives a **bulk selection**, so an accidental activation is a real consequence.

| by keyboard | result |
|---|---|
| Tab ×44 → the checkbox, **Space** | row marked selected · "1 selected" · **the work order did NOT open** · focus stayed on the checkbox |
| Tab ×39 → the title, **Enter** | the dialog opened 440×600 with `aria-modal="true"`, its title read *"Burst pipe under bathroom sink"*, and **focus moved into it** (onto the ✕) · **nothing was ticked** |

**The checkbox already has its own name** — `aria-label="Select {title}"`. It is not one of the 42 unnamed
controls, and needed nothing.

### ⚠️ One thing the keyboard test found that the mouse hides

**Space leaves the checkbox visually unticked while the row is genuinely selected.** A mouse click on the
same box ticks it (`checked: true`); Space does not (`checked: false`), though both select the row, mark it
`is-sel` and update the count to 1. The cause is pre-existing `@click.stop.prevent` — `preventDefault`
suppresses the native toggle and the two input paths end in different DOM states.

**Reported, not fixed.** Selection still works and is still visible (the row highlights, the counter moves),
and removing `.prevent` is what stops the row handler double-toggling — a change on a bulk-selection path
that deserves its own pass rather than a side-effect of this one.

## ⚠️ The occupancy day cell is still unreachable — and the calendar does NOT share it

`owner/units.blade.php:450`: **30 day cells, all `<div>`, `tabindex` null, `role` null, 0 focusable.**
Unchanged and unreachable.

**But `/owner/calendar` is fine.** Its open cells are `<button type="button" class="vy-cal-cell vy-cal-open">`
and its blocked cells are `<button type="submit">`; only *booked* cells are `<div>`s, and those carry no
click handler — they are not controls. So this is **one grid, not a defect across every calendar**, which
also means the overflow pass's refusal to scroll `/owner/calendar` ("every day cell is a control") was
about cells that are genuinely reachable.

**What it would need** (specified, deliberately not built): a roving-tabindex grid — the grid takes **one**
tab stop, the selected cell holds `tabindex="0"` and every other holds `tabindex="-1"`, arrow keys move
selection within the grid and update which cell is tabbable, Home/End reach the row ends, and the grid
carries `role="grid"` with `role="gridcell"` children. Making each cell a plain `<button>` instead would
insert **31 tab stops** between the calendar and everything after it — technically reachable, practically
unusable, and worse than what is there now for anyone who tabs past this card.

## The focus ring reaches the new controls, and is visible

⚠️ **Neither shell has a universal focus rule.** Both name specific classes and reach nothing else — which
is exactly how the app audit found 3 of 35 stops focusable but invisible. Every class added here was added
**to the existing rule**, not given its own, so the ring keeps one definition.

Measured on the focused control in a browser: `outline: 2px solid rgb(194, 65, 12)` against the real painted
background `rgb(255, 252, 248)` — **5.06:1**, above the 3:1 minimum for non-text contrast.

## The guard

`tests/Support/keyboard_helpers.php` + `tests/Feature/KeyboardReachabilityContractTest.php`, joining the
shared-helper family (`heading_helpers` · `dialog_helpers` · `label_helpers` · `overflow_helpers`).

⚠️ **The exemptions carry reasons, and most carry a backing string.** Where an entry's reason is "a real
control inside does this job", it names that control — so **deleting the control deletes the exemption** and
the row goes red. The exemption cannot outlive the fix it describes. The day cell's entry is written as an
**open deferral, not a pass**.

⚠️ **The first tag walker was wrong, and it is worth keeping written down.** It found each `@click` and
walked *backward* to the owning `<`, stopping at any `>`. That breaks on
`<div title="{{ $a > $b ? … }}" @click="go()">` — the `>` inside the quoted Blade expression sits between
the handler and its tag, so the element was never scanned. A guard blind to the markup this codebase writes
most would have reported "nothing unreachable" and read exactly like success. It is now a forward tokeniser
with quote tracking, and a probe in the test pins that specific case.

### Mutations

| mutation | landed | result |
|---|---|---|
| the work-order title button removed | ✅ | **RED** |
| the action-centre button reverted to a `<span>` | ✅ | **RED** |
| the checkbox's `.stop` removed (ticking could open the row) | ✅ | **RED** |
| the focus rule no longer names the new classes | ✅ | **RED** |
| a new unreachable `@click` div added (census 5 → 6) | ✅ | **RED** |
| the same handler added **inside a Blade comment** (census stayed 5) | ✅ | **GREEN — and that is the correct answer** |

That last row is the honest one: the strip works, so a handler that only exists in prose is correctly
ignored, and the mutation proving it can only come back green.

## ⚠️ What I cannot prove

**What a screen reader announces.** Everything above is DOM state, computed style, dispatched key events and
measured contrast. That `aria-expanded` flips from `"true"` to `"false"` is measured; that a screen reader
says *"collapsed"* is not, and no instrument here can establish it.

Two instrument faults were found and corrected mid-pass rather than reported as app behaviour: `offsetParent`
is null for `position: fixed`, so the first dialog check read a visible dialog as hidden; and a CDP
`rawKeyDown` without a `text` payload does not produce a button's activation click, so **every Enter looked
inert** until the dispatch was corrected — which would have read as "the control does not work" when the
instrument was the thing that did not.
