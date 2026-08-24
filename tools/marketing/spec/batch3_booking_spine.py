"""⚠️ BATCH 3 — THE BOOKING SPINE. The consequence here is PHYSICAL: a wrong sentence ends with a guest at a
door that will not open, or turned away at a desk.

⚠️⚠️ THE REGISTRY WAS SWAPPED. `guide-checkin` is "Check-in, check-out and the folio"; `guide-arrival` is
"Online check-in and arrival information". The corrections gate's hand-written URL_TO_KEY had these two
crossed from the day it was written, so it compared each page against the OTHER page's claims and reported
all 35 as UNMATCHED — indistinguishable from the audit paraphrasing, which is why it survived three passes.
⚠️ Fixed there; recorded here because these transforms only make sense against the right page.

SUBSYSTEMS VERIFIED ONCE (§N.8f), applied across all three:
  · ⚠️ #33 ARRIVAL SECRETS — `ArrivalPack::for()` releases from `checkIn->subDays(LEAD_DAYS)` through
    check-out, and withholds for a cancelled booking. ⚠️ IT IS GATED ON A DATE, NOT AN EVENT: not the guest
    checking in, not payment, not an ID on file, not an operator confirming anything. LEAD_DAYS is
    INTERPOLATED below, never typed.
  · stay times — `properties.check_in_time` / `check_out_time` are NULLABLE with no default. ⚠️ No hour is
    typed anywhere in this file; the copy routes to the property's own setting instead.
  · guest vs operator check-in — the guest wizard stamps `guest_checked_in_at`; it does NOT move
    `reservations.status`. The operator's check-in is the authoritative one.
  · availability — `AvailabilityService` maintains a MATERIALISED `availability` table (one row per
    room_type/date with total/sold/out_of_order/blocked). The no-double-booking guarantee is the
    transaction + `SELECT … FOR UPDATE` in `AssignmentService`, not the absence of a counter.
  · ID capture — `GuestIdDocumentService` writes `verification_status = 'pending'`, commented "captured,
    NEVER a fabricated 'verified'". There is no OCR, no name match, no verification of any kind.
  · pre-arrival mail — `SendPreArrivalEmails` selects on `whereDate('check_in', $target)` and never
    consults `guest_checked_in_at`.

⚠️ ALREADY TRUE, SO CORRECTED NOTHING: the audit says no operator UI writes `door_code`/`wifi_*`. One exists
now — `ArrivalInfoService` behind `app.inventory.units.arrival-info` (+ `/reveal`), built in this arc
(cluster E). The audit is a snapshot and the product moved under it.
"""

RESERVATION = {
    # ⚠️ Availability is computed from the DATES ONLY. Party size is collected and stored but never narrows
    # the offer, and `units.max_occupancy` filters nothing — a 9-guest party can be booked into a studio.
    'availability-from-party': {
        'before': 'Pick arrival and departure, then the party size. Availability is calculated from these '
                  'before you see a single unit, so nothing offered to you is unbookable.',
        'after': 'Pick arrival and departure, then the party size. Availability is calculated from the '
                 'dates, so every unit offered is free for the whole range.',
    },
    'capacity-filter': {
        'before': 'A guest count above a unit’s capacity removes that unit from the list rather than '
                  'warning you later.',
        'after': 'Party size is recorded on the booking. Check it against the unit’s capacity yourself — '
                 'it does not remove a unit from the list.',
    },
    # ⚠️ There is no per-night breakdown and no rate override in the wizard at all. Where a per-unit
    # override does exist (the Rates screen) it records no actor — so "recorded against your user" is false
    # about the one feature that is real. ⚠️ Removing this leaves step 3 shorter; what remains is true.
    'per-night-override': {
        'before': 'The per-night breakdown shows the rate plan applied to each night, including weekend '
                  'uplift and any season change mid-stay. Override a night if you need to; the override is '
                  'recorded against your user.',
        'after': 'The price shown is the stay total for the dates and unit you picked, with the rate plan, '
                 'any weekend uplift and any season change already applied. Change a night’s price from '
                 'Rates rather than here.',
    },
    # ⚠️ HALF TRUE, and the true half stays. There IS a stored counter — the materialised `availability`
    # table. What actually prevents a double booking is the row lock in AssignmentService.
    'no-stored-counter': {
        'before': 'Availability is derived from the reservation set, not stored as a separate number that '
                  'can drift out of step. A unit is available for a date when no confirmed booking, owner '
                  'block or out-of-service ticket covers it.',
        'after': 'A unit is available for a date when no confirmed booking, owner block or out-of-service '
                 'ticket covers it. Two people cannot take the last unit at once because every booking '
                 'locks the dates it is claiming before it writes.',
    },
    # ⚠️ Neither is the wizard: the walk-in is a small Front-desk modal, and a group booking is its own
    # screen. Both POST to the same certified service, which is the part worth saying.
    'same-wizard': {
        'before': 'Both are the same wizard with a shortcut.',
        'after': 'Both have their own screen, and both write through the same booking service as the '
                 'wizard.',
    },
    # ⚠️ The DEFAULT is one folio per member; a shared folio happens only when the operator chooses it.
    'group-one-folio': {
        'before': 'They share one folio and one guest contact, but each unit keeps its own housekeeping '
                  'and check-in state so the floor is not confused by a block of nine.',
        'after': 'Each unit keeps its own folio, housekeeping and check-in state by default, so the floor '
                 'is not confused by a block of nine. Choose a single shared folio at booking time if you '
                 'would rather bill the group together.',
    },
    # ⚠️ There is no group management surface at all — only create. So splitting is NOT possible either;
    # the sentence is false in the half that promises a capability.
    'group-split': {
        'before': 'Splitting a group later is possible; merging separate bookings into a group is not.',
        'after': 'A group is set up when it is booked. There is no split or merge afterwards.',
    },
    # ⚠️ NOTHING charges a balance on a schedule. The only pre-arrival action is a reminder EMAIL. ⚠️ A
    # money claim that describes an automatic collection is the kind an operator plans around.
    'auto-collection': {
        'before': 'If the guest paid a deposit, the balance shows on the folio and can be collected at '
                  'check-in or automatically before arrival, depending on the policy attached to the rate '
                  'plan.',
        'after': 'If the guest paid a deposit, the balance shows on the folio and is collected when you '
                 'take it — at check-in, or earlier if you ask for it. Nothing charges the balance on a '
                 'schedule; the pre-arrival email is a reminder, not a payment.',
    },
}

CHECKIN = {
    # ⚠️ The check-in panel renders NO housekeeping information — no state, no assignee, no elapsed time,
    # and there is no "mark as waiting" control anywhere. The Housekeeping board's waiting chip is DERIVED.
    # ⚠️ Removing this empties step 1 of its instructions, so it states what the panel actually does.
    'panel-housekeeping': {
        'before': 'The panel shows the room’s housekeeping state live. If it is not inspected yet, you can '
                  'see who is in it and how long they have been there rather than phoning to ask.',
        'after': 'Check the room is ready on the housekeeping board — the check-in panel does not show its '
                 'state.',
    },
    'mark-waiting': {
        'before': 'If the guest is waiting, mark them as waiting — the room pins to the top of the '
                  'housekeeping board immediately.',
        'after': 'A room whose guest is already due shows as waiting on the housekeeping board on its own, '
                 'from the arrival date.',
    },
    # ⚠️ NOT VERIFIED — `verification_status` is written 'pending', never 'verified', and there is no name
    # match or OCR. ⚠️ And the operator panel has no capture fields: two checkboxes and a key-card counter.
    'id-verified': {
        'before': 'If the guest completed online check-in, their details and ID are already here and '
                  'verified. If not, capture them now; the fields are the same either way.',
        'after': 'If the guest completed online check-in, what they submitted is on file — captured, not '
                 'verified: nothing checks an ID against the name on the booking. Confirm it yourself and '
                 'tick it off here.',
    },
    # ⚠️ A checked-in reservation STILL appears on the arrivals list for its own date — arrivals() is keyed
    # on the date with status IN ('confirmed','checked_in').
    'stops-appearing': {
        'before': 'Confirming check-in moves the booking to in-house, marks the room occupied, and stops '
                  'it appearing on tomorrow’s arrivals list.',
        'after': 'Confirming check-in moves the booking to in-house and marks the room occupied. It stays '
                 'on today’s arrivals list, shown as arrived.',
    },
    # ⚠️ Room revenue posts as a SINGLE lump line for the whole stay, not per night.
    'folio-per-night': {
        'before': 'Every booking has one folio: the room revenue per night, add-ons, city tax, payments '
                  'taken and the running balance.',
        'after': 'Every booking has one folio: the room revenue for the stay, add-ons, city tax, payments '
                 'taken and the running balance.',
    },
    # ⚠️ ALL THREE EXAMPLES ARE FICTITIOUS — the check-in panel posts no add-ons, there is no late-checkout
    # charge, and housekeeping posts nothing to a folio.
    'auto-charges': {
        'before': 'Charges post automatically as they happen — a transfer booked at check-in, a late '
                  'checkout, a minibar item added by housekeeping.',
        'after': 'Extras chosen at booking time post to the folio with the room. Anything added during the '
                 'stay is a line you post yourself.',
    },
    # ⚠️ THERE IS NO BALANCE GUARD ON CHECKOUT and no "mark as owed" concept anywhere. ⚠️ A page promising
    # friction that does not exist is how an unpaid balance walks out of the building.
    'checkout-guard': {
        # ⚠️ RE-CORRECTED IN THE SAME PASS AS THE BLOCK — and this is the FIRST time it is actually due.
        #    Batch 3 correctly said nothing stopped an unpaid checkout; last pass correctly did NOT write
        #    this, because the block was built and reverted. It ships now, so the page changes now.
        'before': 'The panel refuses to complete a checkout with money outstanding unless you explicitly '
                  'mark it as owed.',
        # ⚠️ SIXTH correction to this page, and the third in three passes — the block, then the exemption,
        #    now the override. ⚠️ IT MUST SAY THE OVERRIDE DOES NOT SETTLE THE DEBT: a reader who believes
        #    otherwise is exactly how an override becomes a write-off.
        'after': 'A checkout is refused while money is owed. Take the payment, or — if a company settles '
                 'later — bill the balance to their account and the departure goes through with the money '
                 'still owed by them. A settled folio and one in credit both check out normally. A manager '
                 'can override the refusal, choosing from a fixed list of reasons so the overrides can be '
                 'counted; doing so releases the room but settles nothing — the balance stays owed exactly '
                 'as it was.',
    },
    # ⚠️ NO early-checkout / stay-shortening feature exists, and there is no late-checkout add-on: the only
    # 'late_checkout' in the codebase is a guest PREFERENCE label.
    'early-late': {
        'before': 'Checking out early shortens the stay and recalculates the folio; the nights released '
                  'become available immediately. A late checkout is an add-on charge rather than a date '
                  'change, so the calendar stays honest.',
        'after': 'To end a stay early, change the booking’s dates — checking out does not shorten it or '
                 'recalculate the folio. For a late checkout, post the charge to the folio yourself.',
    },
    # ⚠️ No luggage-storage feature, no waiting action, and the note cannot be attached where the page says.
    # ⚠️⚠️ A REAL PRODUCT DEFECT, CORRECTED IN PROSE AND REPORTED. Owner attribution runs ONCE, at booking
    # creation, and `AssignmentService` never re-runs it — so moving a booking between two owners' units
    # leaves the money attributed to the ORIGINAL owner. The page promised the opposite.
    'unit-move-statements': {
        'before': 'The change keeps their history, their rate and their folio, and the owner statements on '
                  'both units reflect it correctly.',
        # ⚠️ RE-CORRECTED IN THE SAME PASS AS THE PRODUCT FIX. Batch 3 wrote "the earnings stay with the
        #    unit that was originally booked" — true that morning, FALSE the moment the re-attribution
        #    shipped. ⚠️ THIRD TIME THIS PATTERN HAS RUN (refunds → borne-by → here), and leaving it would
        #    have this arc create the very defect it exists to remove, by its own hand.
        #    ⚠️ The mid-stay case is stated honestly because the product genuinely cannot split it: no
        #    record says when the guest actually moved.
        # ⚠️ RE-CORRECTED AGAIN, IN THE SAME PASS AS THE PRODUCT CHANGE — the FOURTH time this page has
        #    needed it (refunds → borne-by → re-attribution → the split). The pattern is now the rule: a
        #    doc sentence describing a limitation goes stale the moment the limitation is lifted.
        #    ⚠️ THE BOUNDARY IS WORDED IDENTICALLY HERE, IN THE FORM AND IN THE AUDIT: the first night the
        #    guest slept in the new unit. Two people reading "the move date" differently mis-attributes
        #    money silently and repeatedly.
        'after': 'The change keeps their history, their rate and their folio, and the earnings follow the '
                 'unit. Move it before the guest arrives and the whole stay goes to the new unit\u2019s '
                 'owner. Move it mid-stay and you state which night they first slept in the new unit — '
                 'nights before it stay with the old unit, that night and after move with the guest. The '
                 'date you state is recorded alongside the moment you stated it.',
    },
    'waiting-luggage': {
        'before': 'Mark the guest as waiting so the floor knows, store their luggage, and note it on the '
                  'booking — the note appears to whoever handles them next, including the night shift.',
        'after': 'Add a note to the booking so whoever handles them next sees it, including the night '
                 'shift.',
    },
}

ARRIVAL = {
    # ⚠️ The collected set is HARDCODED (phone, arrival_window, arrival_note, id_type, id_photo). There is
    # no date-of-birth field and nothing is configurable.
    'configurable-fields': {
        'before': 'Guest names, dates of birth and ID are legally required in many jurisdictions. Collect '
                  'what the law where you operate requires and nothing beyond it — every extra field costs '
                  'completion and creates data you then have to protect.',
        'after': 'The guest confirms their contact details, uploads an ID and gives an arrival time. The '
                 'set is fixed — you cannot add fields to it, which also means you cannot collect more '
                 'than you need.',
    },
    # ⚠️ No name matching of any kind, and the wizard tells the guest "Only the lead guest checks in
    # online" — so per-adult capture is structurally impossible, not merely unconfigured.
    'id-name-check': {
        'before': 'Photograph or upload, with a check against the name on the booking. You can require it '
                  'for the lead guest only, or for every adult in the party.',
        'after': 'Photograph or upload. The lead guest is the one who checks in online, and what they '
                 'submit is captured for you to check — nothing matches it against the name on the '
                 'booking automatically.',
    },
    # ⚠️ There is no house-rules step in the guest wizard (Details → ID → Arrival). `house_rules_ack_at` is
    # written by the OPERATOR's reservation-create path, not by a guest accepting anything.
    'house-rules-step': {
        # ⚠️ The heading and the body are separate elements in the master, so this is two needles.
        'before': 'Write the house rules',
        'after': 'Record that the rules were accepted',
    },
    'house-rules-body': {
        'before': 'Shown as a step the guest accepts, with the acceptance timestamped against their '
                  'booking. This is what you refer to later if something goes wrong.',
        'after': 'The acceptance is timestamped against the booking when you take it. The guest wizard '
                 'does not show a house-rules step.',
    },
    # ⚠️ The arrival step is a window picker and a free-text note. No transfer offer appears in the flow.
    'arrival-transfers': {
        'before': 'Ask for an arrival time and offer transfers if you run them.',
        'after': 'Ask for an arrival time. Add-ons such as a transfer are chosen at booking time, not '
                 'here. The time they give is stored on the booking — the arrivals board shows the '
                 'expected arrival you set, not the guest’s answer.',
    },
    # ⚠️ The captured ID is WRITE-ONLY: `GuestDocument` has no operator or owner surface, and its own
    # docblock says so. The page implies the form is replaced by something you can look at.
    'passport-form': {
        'before': 'Without it, a guest arrives, you copy their passport details onto a form, and someone '
                  'drives across town with a key.',
        'after': 'Without it, a guest arrives, you copy their passport details onto a form, and someone '
                 'drives across town with a key. With it the ID is captured and held for the retention '
                 'period rather than sitting in a drawer — it is stored, not shown back to you.',
    },
    # ⚠️⚠️ THE #33 SENTENCE. The pack is gated on the DATE WINDOW and the booking not being cancelled —
    # NOT on an ID being on file, NOT on a paid balance, NOT on the guest checking in. LEAD_DAYS is
    # INTERPOLATED: a docblock in this codebase once said "48h" against a constant reading 2 days, and a
    # typed number on a doc page is that defect with a wider audience.
    'release-preconditions': {
        'before': 'Released the day before, to a guest whose ID you have and whose balance is paid, it is '
                  'a much smaller thing to have given away.',
        'after': 'Released only in the last {{ \\App\\Support\\Guest\\ArrivalPack::LEAD_DAYS }} days before '
                 'arrival, and never for a cancelled booking, it is a much smaller thing to have given '
                 'away.',
    },
    # ⚠️ `SendPreArrivalEmails` selects on the check-in DATE alone and never consults `guest_checked_in_at`
    # — so a guest who has not checked in receives the same email, carrying the code.
    'reminder-not-code': {
        'before': 'If a guest has not completed check-in, they get a reminder rather than the code.',
        'after': 'The email goes to every confirmed booking on that date, whether or not the guest has '
                 'completed check-in.',
    },
    'email-timing': {
        'before': 'The pre-arrival email goes out two days before check-in to every confirmed booking.',
        'after': 'The pre-arrival email goes out {{ \\App\\Support\\Guest\\ArrivalPack::LEAD_DAYS }} days '
                 'before check-in, to every confirmed booking arriving that day.',
    },
    # ⚠️ There is ONE email, not two, and its link requires the guest to be signed in — it does not resume
    # anything on its own.
    'two-emails': {
        'before': 'Both emails carry a single link that resumes wherever they stopped.',
        'after': 'The email carries a link to check-in, which asks the guest to sign in first.',
    },
    # ⚠️ Channel messaging does not work: every channel class extends DeferredChannel, whose send()
    # unconditionally throws. Email is the only real path.
    'channel-messaging': {
        'before': 'The invitation goes through the channel’s messaging where the window allows it, and by '
                  'email where you have an address.',
        'after': 'The invitation goes out by email, so a marketplace booking needs an address on it.',
    },
    # ⚠️ NO statutory-retention period exists as a setting, config value or column. What is real: a 30-day
    # ID purge and guest-requested deletion after a grace period. ⚠️ Describing a retention rule the
    # product does not apply is the legal exposure pointed the other way.
    'statutory-retention': {
        'before': 'Registration data is retained for the statutory period in your jurisdiction and then '
                  'anonymised automatically',
        'after': 'ID images are deleted on their own schedule, and a guest who asks to be erased is '
                 'anonymised after a grace period. Any longer statutory period is yours to apply',
    },
}
