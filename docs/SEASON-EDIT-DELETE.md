# Season edit, delete — and why undo cannot exist

**2026-09-04.** Seasonal pricing could create, preview and apply. It now edits and deletes. ⚠️ **It does not
undo, and this document is mostly about why that is the honest answer rather than a missing feature.**

> ⚠️⚠️ **2026-09-12 — THE SENTENCE ABOVE WAS TRUE OF THE BACKEND AND FALSE OF THE PRODUCT FOR EIGHT DAYS.**
> The routes, the controller and the guards described below were all correct and were proven by direct
> invocation (audit Phase 11). But the Edit and Delete **dialogs** sat outside the Alpine scope that declares
> `editSeason` / `deleteSeason`, so `/app/rates` threw `editSeason is not defined` on **every load** and
> neither dialog could ever open — for any role. **No operator could reach edit or delete at all** (F-20).
>
> Fixed 2026-09-12 by lifting that scope onto a plain wrapper enclosing the season card *and* both dialogs.
> Two guards now cover it (`SeasonEditDeleteTest`): one asserts each dialog's variable is declared by an
> **ancestor** `x-data`, the other that the declaring element is not a `.vy-fade` card — whose animation ends
> on a `transform`, which would clamp a `position: fixed` dialog to the card's own box instead of the
> viewport. ⚠️ **A guard that asserted the dialog markup merely EXISTS was green throughout the defect** —
> measured side by side at the time of the fix — which is why both guards assert reachability, not presence.

## #1 · What applying actually writes — measured

`SeasonalRateService::apply()` calls `RateAndRestrictionService::setRate()`, which loops every night and
does `RateCalendar::updateOrCreate(['rate_plan_id','date'], ['price_minor' => …])`, then stamps
`applied_at` on the season.

**So the price comes from the WRITTEN ROWS, not from the season.** The season is not consulted at price
time; `SeasonPrecedence::winnerOn()` decides which season *owns* a night for display and for write order,
but the number a guest is quoted is the `rate_calendar` row. **Therefore deleting a season changes nothing
already written** — proven, not reasoned:

```
grid 2026-09-03 before apply   200.00
grid 2026-09-03 after apply    333.00
season deleted                 333.00   ← unchanged
```

## #2 · ⚠️⚠️ Nothing records the previous value, so undo cannot be built honestly

`updateOrCreate` overwrites the price. The audit row written alongside it records **what was written** and
its `before` is literally `null` — asserted by a test, so if that ever changes this conclusion is revisited.

**A false undo would be worse than none**, because an operator who believes they are back where they started
stops checking. So none is offered, and — ⚠️ **already true before this pass** — the apply dialog states it
outright: *"The prices it replaces are not stored anywhere. **This cannot be undone.**"*

**What a real undo would cost:** a per-apply snapshot of the nights it is about to overwrite — a table of
`(season_id, date, previous_price_minor, previous_currency)` written inside the same transaction as the
apply. That is the only shape that could restore a manually-set price or an owner's negotiated rate rather
than a base rate. It is a migration plus a write path plus a restore path, and it is **not this pass**.

## #3 · Edit — the two-step survives, and an applied season is refused

**A draft season can be edited; an applied one cannot.** That is a conclusion, not a limitation of effort:
editing an applied season is two operations — un-apply the old shape, apply the new — and **the first is
impossible**, because there is no "before" to restore. An "edit" would imply the old rates came back.

**So an applied season is SUPERSEDED, never edited** — authoring a new season over the same nights is
honest, because it writes what it says it writes.

⚠️ **Editing does not apply.** It saves the draft and returns to the preview, exactly as `store()` does. The
create path is two-step so the blast radius is shown *before* it lands, and an edit that wrote immediately
would destroy precisely that. The edit dialog states it: *"Saving updates the season only. **No prices are
written** until you apply it."* The existing preview and `SeasonPrecedence` are reused — one definition.

## #4 · Delete is not undo, and the screen says so

Deleting removes the season record. For an **applied** season the dialog reads:

> This season has been applied. Deleting it removes the season only — **the nightly prices it wrote stay as
> they are**, and the prices they replaced are not stored anywhere.

For an unapplied one it says no prices change. **A control may not imply more than it does.**

⚠️ **Hard delete, deliberately.** `RateSeason` carries no `SoftDeletes`, and adding it would repeat a
recorded defect exactly: a soft-deleted **plan** kept selling because a `withoutGlobalScopes()` call dropped
the soft-delete scope along with the tenant one. A test asserts both that a deleted season is gone even from
a scope-dropping query **and** that `SoftDeletes` has not been added.

## #5 · ⚠️ A booked stay does NOT move — the reassuring answer

A season **can** write over a date that already has a reservation, and the grid does change. **But the
reservation keeps the price it was booked at**, because `reservation_units.price_minor` snapshots it at
booking — the same freezing discipline the billing side uses for subscription terms.

```
booked stay price   100.00 → 100.00   (unchanged across the apply)
grid for the night  200.00 → 333.00
night outside range 200.00 → 200.00
```

**So a season changes what is OFFERED, never what was AGREED.** `owner_ledger` is built on earned revenue
recorded from those snapshots and is likewise untouched. This is the finding the brief said would be bigger
than the missing edit — and it is bigger, in the other direction: **the protection already exists.**

## #6 · The negotiated-rate collision

⚠️ **Not ruled and not changed** — the precedence is the founder's decision. The preview already computes
and shows the blast radius (`changing`, `losing_nights`, `losing_to`, `rewritten_nights`), and the apply
dialog already states that overlapping applied seasons will be re-written. **Whether an OWNER's negotiated
rate is separately called out is a disclosure change to the preview's own computation** — worth doing, but
it belongs with the ruling rather than ahead of it, so it is reported here and not built.

## #7 · The gate — and it matches, unlike the last pass's finding

`seasons.update` and `seasons.destroy` carry **`can:rates.manage`**, the same gate as `store` and `apply`.
⚠️ **Checked for the shape the previous pass found** (`taxes.manage` granted in the matrix while the route
enforced `properties.manage`): here the permission the matrix grants **is** the permission the route
enforces. Proven in **both directions** — a Housekeeping role is refused 403 on both routes, and a Hotel
Manager succeeds. Nothing was granted.

## #8 · Mutations — every one landed, and one found a gap in my own tests

| mutation | landed | result |
|---|---|---|
| **wrong fix** — edit applies immediately (two-step destroyed) | ✅ | **RED** |
| allow editing an applied season | ✅ | **RED** |
| delete dialog claims prices are restored | ✅ | **RED** |
| offer Edit on an applied season | ✅ | **RED** |
| the row loses `update_url` (dialog posts nowhere) | ✅ | ⚠️ **GREEN, then RED** |

⚠️ **The `update_url` mutation was green** because every other test POSTs to the route directly and never
checked that the *rendered row* carries the URL its dialog submits to — a control wired to nothing, which is
exactly the class this project keeps finding. Closed with a probe that reads the component's own view data,
property-scoped (the grid is per-property, so an unscoped probe would have read a different property's
seasons) and with a non-vacuity floor, since an empty list would satisfy any per-row assertion.

⚠️ One earlier mutation attempt **did not land** (a bad shell escape) and its green was discarded rather than
counted.

## ⚠️ What is reported, not decided

1. **A per-apply price snapshot** — the only thing that would make undo honest. Costed above.
2. **The negotiated-rate disclosure** in the preview — disclosure, but it belongs with the ruling.
3. **`Apply again` on an already-applied season** remains available and is unchanged; it re-writes the same
   nights, which is what it says.

## ⚠️ Where this brief was wrong

It warned that a season touching a booked date would be "a finding bigger than the missing edit". It can
touch the date — but **the reservation is already insulated by its own price snapshot**, so the guest's
agreed price cannot move. The protection the brief hoped for exists.
