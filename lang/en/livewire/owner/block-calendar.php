<?php

// resources/views/livewire/owner/block-calendar.blade.php — the owner's Block calendar (hold and release dates).

return [
    'title' => 'Block calendar',
    'subtitle' => 'Hold dates for personal stays, maintenance or seasonal closures — blocked nights won’t accept guest bookings.',
    'no_units' => 'No units are linked to your account yet.',
    'unit_label' => 'Unit',
    'previous_month' => 'Previous month',
    'next_month' => 'Next month',
    // the legend
    'legend_booked' => 'Booked',
    'legend_owner_block' => 'Owner block',
    'legend_selecting' => 'Selecting',
    'legend_open' => 'Open',
    // a day cell's accessible name; :date is the spoken date, e.g. "14 October 2026"
    'cell_open_aria' => ':date — open. Select to block this day.',
    // :guest is the booking guest's name
    'cell_booked_title' => 'Booked · :guest',
    'cell_booked_sr' => ':date — booked',
    'cell_blocked_title' => 'Owner block — click to release',
    'cell_blocked_aria' => ':date — blocked. Release this block.',
    'cell_past_sr' => ':date — past',
    'grid_help' => 'Click a day, or press and drag across several, to block them. By keyboard, press Enter on a day to start, then on a second day to extend the range. Activate a blocked day to release it.',
    // the composer
    'block_dates_heading' => 'Block dates',
    'selected' => 'Selected',
    // a selected range's start and end, e.g. "3 Oct – 5 Oct 2026" (filled in by the page's script)
    'date_range' => ':start – :end',
    // the selected length and the unit, e.g. "3 nights · Sea View Apartment"
    'selected_nights' => '{1} :count night · :unit|[0,*] :count nights · :unit',
    'reason_label' => 'Reason',
    'note_placeholder' => 'Add a note (optional)',
    'note_aria' => 'Block note (optional)',
    'repeat_toggle' => 'Repeat this block',
    'repeat_on' => 'On',
    'repeat_off' => 'Off',
    'freq_weekly' => 'Weekly',
    'freq_fortnightly' => '2 weeks',
    'freq_monthly' => 'Monthly',
    'occurrences' => 'Occurrences',
    'fewer_occurrences' => 'Fewer occurrences',
    'more_occurrences' => 'More occurrences',
    // :count is the total number of blocks the repeat creates (2 or more)
    'repeat_summary_weekly' => '{1} Repeats weekly · :count block in total|[0,*] Repeats weekly · :count blocks in total',
    'repeat_summary_fortnightly' => '{1} Repeats every 2 weeks · :count block in total|[0,*] Repeats every 2 weeks · :count blocks in total',
    'repeat_summary_monthly' => '{1} Repeats monthly · :count block in total|[0,*] Repeats monthly · :count blocks in total',
    'block_button' => 'Block these dates',
    'empty_selection' => 'Select one or more open days on the calendar to create a block.',
    // the scheduled-blocks list
    'scheduled_heading' => 'Scheduled blocks',
    'blocks_count' => '{1} :count block|[0,*] :count blocks',
    'block_nights' => '{1} :count night|[0,*] :count nights',
    // a repeat series and how many blocks it holds
    'series_count' => 'Series · :count',
    'remove_aria' => 'Remove block',
    'keep' => 'Keep',
    'no_blocks' => 'No upcoming blocks for this unit.',
    'undo' => 'Undo',
];
