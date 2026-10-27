<?php

// resources/views/livewire/owner/unit-detail.blade.php — the owner Unit Detail page (one unit's calendar, pricing, analytics).

return [
    'breadcrumb_units' => 'My units',
    // the header status pill (also the listing row and the read-only switch stand-in)
    'status_active' => 'Active',
    'status_not_listed' => 'Not listed',
    // shown only when this unit leads the owner's units this month; :count is always 2 or more
    'best_badge' => '[0,*] ★ Best performer of your :count units · :month',
    'edit_pricing' => 'Edit pricing',
    'sync_managed' => 'Channel sync is managed by your operator',
    // KPIs
    'kpi_occupancy' => 'Occupancy',
    'kpi_nights_sold' => 'Nights sold',
    'kpi_net' => 'Net · :month',
    'kpi_attributed' => 'Attributed to you',
    // availability calendar
    'availability_heading' => 'Availability · :month',
    'tap_to_block' => 'Tap an open day to block',
    'calendar_counts' => ':booked booked · :blocked blocked · :open open',
    // a day's state read out by a screen reader, e.g. "3 October 2026 — open. Select to block this day."
    'day_open_aria' => ':date — open. Select to block this day.',
    'day_booked_aria' => ':date — booked. Show detail.',
    'day_blocked_aria' => ':date — blocked. Show detail.',
    'day_past_sr' => '— past',
    'legend_booked' => 'Booked',
    'legend_open' => 'Open',
    'legend_blocked' => 'Blocked',
    // the day-detail popover; :reference / :net / :reason are filled in on the page
    'detail_reference' => 'Ref :reference',
    'detail_your_net' => 'Your net: :net',
    'detail_blocked' => 'Blocked · :reason',
    // the block composer, e.g. "Block 2026-10-03 as [reason]"
    'block_day_as' => 'Block :date as',
    'block_day_button' => 'Block day',
    'blocked_days_note' => 'Blocked days hold the unit off-sale. Manage all blocks on your <a href=":url" style="color:var(--vy-accent-text)">Calendar</a>.',
    // pricing & availability
    'pricing_heading' => 'Pricing & availability',
    'open_for_bookings' => 'Open for bookings',
    'paused' => 'Paused',
    'pause_hint' => 'Pause to stop taking new bookings — your existing stays are kept.',
    'paused_hint' => 'This unit isn’t taking new bookings. Resume when you’re ready.',
    'nightly_rate' => 'Nightly rate',
    'not_set' => 'Not set',
    'weekend_uplift' => 'Weekend uplift',
    'minimum_stay' => 'Minimum stay',
    'nights' => '{1} :count night|[0,*] :count nights',
    'rate_shared_note' => 'This room type is shared with other inventory, so its rate is managed by your operator.',
    'nightly_rate_label' => 'Nightly rate (:symbol)',
    'weekend_uplift_label' => 'Weekend uplift (%)',
    'weekend_uplift_placeholder' => 'Optional — e.g. 20',
    'minimum_stay_label' => 'Minimum stay (nights)',
    // the live preview; both prices are worked out on the page as the owner types
    'weekend_preview' => 'Fri & Sat nights become :weekend_price / night; weekdays stay :weekday_price.',
    'applies_note' => 'Applies from today across the next year. It’s live immediately.',
    // revenue · 6 months
    'revenue_heading' => 'Revenue · 6 months',
    'revenue_sub' => 'Net to you, per month',
    'revenue_empty' => 'No revenue in the last 6 months yet.',
    // occupancy · 12 months
    'occupancy_heading' => 'Occupancy · 12 months',
    'occupancy_summary' => 'avg :avg% · peak :peak% in :month',
    // attribution
    'attribution_heading' => 'Attribution · :month',
    'attribution_sub' => 'What this unit earned you, and what was deducted.',
    'net' => 'Net',
    'attribution_empty' => 'No activity attributed this month yet.',
    // stays
    'stays_heading' => 'Stays · :month',
    'stays_sub' => 'Bookings on this unit that touch this month',
    'col_reference' => 'Reference',
    'col_stay' => 'Stay',
    'col_nights' => 'Nights',
    'col_source' => 'Source',
    'col_your_net' => 'Your net',
    'not_yet_attributed' => 'Not yet attributed',
    'stays_empty' => 'No stays touch this month.',
    // reviews
    'reviews_heading' => 'Reviews & rating',
    'reviews_published' => '{1} :count review · published|[0,*] :count reviews · published',
    'review_stars_aria' => ':rating out of 5 stars',
    'reviews_empty' => 'No published reviews for this unit yet.',
    // unit details rail
    'unit_details' => 'Unit details',
    'detail_code' => 'Code',
    'detail_room_type' => 'Room type',
    'detail_type' => 'Type',
    'detail_sleeps' => 'Sleeps',
    'detail_listing' => 'Listing',
    'listing_off' => 'Off',
    // commission rail; :terms is "Portfolio-wide terms" or "Terms for this unit"
    'commission' => 'Commission',
    'agreement_link' => 'Agreement →',
    'terms_in_effect' => ':terms, in effect today.',
    // channel mix rail
    'channel_mix_heading' => 'Channel mix · :month',
    'channel_nights' => '{1} :count night · :pct%|[0,*] :count nights · :pct%',
    'channel_mix_note' => 'Share of this unit\'s room-nights by booking source.',
    'channel_mix_empty' => 'No bookings this month.',
    // cleaning schedule rail
    'cleaning_heading' => 'Cleaning schedule',
    'cleaning_date_upcoming' => ':date · upcoming',
    'cleaning_note' => 'Coordinated by your management team — message them below to arrange anything.',
    'cleaning_empty' => 'No cleanings on record for this unit yet. Turnovers are coordinated by your management team — message them below to arrange anything.',
    // message your management team rail
    'message_heading' => 'Message your management team',
    'message_sub' => 'About this unit — bookings and guest contact stay with your operator.',
    // the new thread's subject, e.g. "About Villa Mar"
    'message_subject' => 'About :unit',
    'message_placeholder' => 'Ask your management team about this unit…',
    'send_message' => 'Send message',
    'open_inbox' => 'Open inbox →',
    // your money rail
    'your_money' => 'Your money',
    'balance' => 'Balance',
    'payouts' => 'Payouts',
    'open_link' => 'Open →',
    'payouts_note' => 'Payouts are made per statement across your whole portfolio, not per unit.',
];
