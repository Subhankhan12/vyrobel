"""⚠️ BATCH 4 — THE RATES PAGE. ⚠️ The channels page is NOT in here: its subject was established first and
that finding is reported rather than corrected (see DECISIONS).

⚠️⚠️ THE CONSEQUENCE ON THIS PAGE IS OVERSELLING AND UNSELLABLE NIGHTS. A wrong precedence is invisible to a
reader and expensive to an operator; a wrong minimum-stay description produces nights nobody can book.

SUBSYSTEMS VERIFIED ONCE (§N.8f):
  · ⚠️ PRICE RESOLUTION IS TWO LEVELS, NOT FOUR. `NightlyRate::forUnit()` returns a `unit_rate_overrides`
    row for (plan, unit, date) if one exists, otherwise `forType()` → the `rate_calendar` price for
    (plan, date). Seasons and the weekend uplift are not resolution passes: they WRITE into `rate_calendar`
    when applied, so the calendar already carries whatever they last set.
  · ⚠️ SEASONS ARE NOT OVERLAP-VALIDATED. `SeasonActionController::store()` validates the fields and nothing
    about collisions with an existing season.
  · ⚠️ NO OWNER APPROVAL ON A RATE SAVE. `RateActionController::apply()` → `RateAndRestrictionService::setRate()`
    writes immediately, with no ownership check and no proposal. (Rate PROPOSALS exist as their own
    operator→owner flow — a different thing from saving a rate.)
  · ⚠️⚠️ `restrictions.min_stay` IS WRITTEN AND DISPLAYED BUT NEVER ENFORCED — the booking path reads no
    restrictions at all. Reported as a product defect; not fixed here.
  · ⚠️ THERE IS NO ORPHAN-NIGHT AUTO-DROP and NO LEAD-TIME RESTRICTION — no model, column or code path.
"""

RATES = {
    # ⚠️ THE PRECEDENCE CLAIM. Four passes with "the last one that applies wins" describes a system this
    # product does not have, and an operator reasoning from it will mis-predict every price.
    'four-passes': {
        'before': 'For any given night, Vyrobel resolves the price in four passes: the unit’s base rate, '
                  'then the season covering that date, then a weekend uplift if the day qualifies, then '
                  'any per-night override. The last one that applies wins.',
        'after': 'For any given night, Vyrobel checks two things: an override set on that unit for that '
                 'night, and otherwise the rate plan’s price for that night. Seasons and weekend uplift '
                 'are not separate passes — applying one writes its price into the calendar, so the '
                 'calendar already holds whatever was set last.',
    },
    # ⚠️ Both halves false: there is no season editor of the kind described, and nothing validates overlap.
    'season-overlap': {
        'before': 'Seasons cannot overlap. The editor will refuse rather than silently pick one.',
        'after': 'Nothing stops two seasons covering the same dates. Check the calendar after you apply '
                 'one — the price you see there is the one that will be charged.',
    },
    # ⚠️ There is no drag-select. The grid's own instruction is click-a-cell → a From/To range form.
    'drag-select': {
        'before': 'Drag across the rate grid to set several nights at once.',
        'after': 'Click a cell on the rate grid and set the range you want in the form it opens.',
    },
    # ⚠️ A rate save writes immediately: no ownership check, no proposal, no approval.
    'owner-approval': {
        'before': 'Changes propagate to connected channels on save. Owner-owned units send the change to '
                  'the owner for approval first if their agreement requires it.',
        'after': 'A rate you save applies immediately, on an owner’s unit as much as your own. If you want '
                 'an owner to agree a rate first, send them a rate proposal instead of saving it here.',
    },
    # ⚠️⚠️ THE DANGEROUS ONE. The page frames a minimum stay as costing bookings — which is the right
    # warning for a system that ENFORCES it. This one does not: the value is stored and shown, and the
    # booking path never reads it. ⚠️ Left as a plain statement of what the field does, because implying it
    # protects inventory it does not protect is the overselling shape pointed the other way.
    'min-stay-lever': {
        'before': 'Restrictions live alongside rates but behave differently: a rate that is too high loses '
                  'margin, a minimum stay that is too long loses the entire booking.',
        # ⚠️ RE-CORRECTED IN THE SAME PASS AS THE ENFORCEMENT. This sentence was written one pass ago and
        #    was true then: the minimum was displayed and never read. It is enforced now, so the original
        #    warning — a too-long minimum costs you the booking — is the right one again.
        'after': 'Restrictions live alongside rates but behave differently: a rate that is too high loses '
                 'margin, a minimum stay that is too long refuses the booking outright. A stay shorter '
                 'than the minimum on its arrival date is turned away, wherever it was booked.',
    },
    # ⚠️ NO ORPHAN-NIGHT AUTO-DROP EXISTS. ⚠️ A nearly-right description here is what produces unsellable
    # nights: an operator who believes the minimum relaxes on its own will not go and relax it.
    'orphan-drop': {
        # ⚠️ CONFIRM-FIRST CUT BOTH WAYS. The auto-drop sentence was ALREADY corrected by the G5 pass to
        #    "A minimum stay applies as set, including across a short gap between two bookings" — which is
        #    TRUE, so it is left alone. ⚠️ Only the dangling clause before it is corrected: "then let it
        #    relax" still implies something relaxes the minimum on its own, which nothing does, and an
        #    operator who believes it will not go and lower it.
        'before': 'Set the minimum you want while the calendar is open, then let it relax.',
        'after': 'Set the minimum you want while the calendar is open, and lower it yourself as the dates '
                 'approach.',
    },
    # ⚠️ There is no lead-time restriction: `setRestriction()` accepts room type, rate plan, min/max stay,
    # closed-to-arrival, closed-to-departure and stop-sell, and nothing else.
    'lead-time-window': {
        'before': 'You can also set a shorter minimum inside a lead-time window. A two-night booker ten '
                  'days out is not displacing a seven-night one who was never coming.',
        'after': 'There is no lead-time rule, so a minimum applies to every booking for those nights '
                 'however far ahead it is made. Lower it on the nights themselves when you want the short '
                 'stays.',
    },
}
