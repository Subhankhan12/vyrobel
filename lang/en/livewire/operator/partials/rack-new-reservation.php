<?php

// resources/views/livewire/operator/partials/rack-new-reservation.blade.php — the Calendar's New reservation dialog (book a free
// unit, set a missing price, or apply a saved season). Amounts, dates and names are filled in on the page.

return [
    'nights' => '{1} :count night|[0,*] :count nights',
    'open_full_form' => 'Open the full form',
    'price_err_invalid' => 'Enter the price a night, e.g. 120 or 120.50.',
    'apply_season_title' => 'Apply this season?',
    'title' => 'New reservation',
    'subtitle' => ':property · a quick booking — extras and payment are in the full form',
    'no_rooms_title' => ':property has no rooms yet',
    'no_rooms_body' => 'A booking needs a unit to stay in. Add this property’s units, then book them here.',
    'none_bookable_title' => 'None of :property’s units can take a booking',
    'none_bookable_body' => 'They’re all paused or out of service. Reopen one on the Units page, then book it here.',
    'book_other_property' => 'Book into another property',
    'rooms' => '{1} :count room|[0,*] :count rooms',
    'go_to_units' => 'Go to Units',
    // follows the message of a price set or a season applied from the dialog
    'unit_picked' => ':unit is picked — confirm to book it.',
    'pick_unit_below' => 'Pick a unit below to continue.',
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'name_missing' => 'Enter the guest’s first or last name.',
    'check_in' => 'Check-in',
    'check_out' => 'Check-out',
    'adults' => 'Adults',
    'fewer_adults' => 'Fewer adults',
    'more_adults' => 'More adults',
    'dates_changed' => 'The dates changed — check the rack again to see what’s free.',
    'check_rack' => 'Check rack',
    // :dates is "12 Oct → 15 Oct", :nights the "nights" phrase above
    'whats_free' => 'What’s free · :dates · :nights',
    'sleeps' => 'sleeps :guests',
    // a room type with free units but no price: :units is how many units are free; the count is the stay's nights
    'no_price_all' => '{1} :units free · No price for this night yet.|[0,*] :units free · No price for these nights yet.',
    'no_price_some' => ':units free · No price for :unpriced of these :count nights yet.',
    'ask_rates_manager_price' => 'Ask someone who manages rates to set one.',
    // a saved season never applied; the count is the stay's nights with no price
    'season_saved_all' => '{1} <strong>:season · :nightly</strong> · :from → :to — saved but not applied yet, so this night has no price.|[0,*] <strong>:season · :nightly</strong> · :from → :to — saved but not applied yet, so these nights have no price.',
    'season_saved_some' => '<strong>:season · :nightly</strong> · :from → :to — saved but not applied yet. It would price :prices of the :count nights that have none.',
    'apply_season_button' => 'Apply :season…',
    'manager_can_apply_then_book' => 'Someone who manages rates can apply it on the Rates page — its units can be booked here once they have.',
    'manager_can_apply' => 'Someone who manages rates can apply it on the Rates page.',
    // the hidden part names the room type for screen readers; the count is the stay's nights
    'price_label_or' => '{1} Or set a price for this night only<span class="vy-sr-only"> for :room_type</span>|[0,*] Or set a price for these nights only<span class="vy-sr-only"> for :room_type</span>',
    'price_label' => 'Price a night<span class="vy-sr-only"> for :room_type</span>',
    'price_placeholder' => 'e.g. 120',
    'set_price_for' => 'Set price for :room_type',
    'setting' => 'Setting…',
    'set_price' => 'Set price',
    'price_goes_one_plan' => 'The price goes on the night of :date on the :plan plan — a price already there is replaced. Change it any time on the Rates page.',
    'price_goes_one' => 'The price goes on the night of :date — a price already there is replaced. Change it any time on the Rates page.',
    'price_goes_many_plan' => 'The same price goes on each night from :from to :to on the :plan plan — a price already on one of them is replaced. Change it any time on the Rates page.',
    'price_goes_many' => 'The same price goes on each night from :from to :to — a price already on one of them is replaced. Change it any time on the Rates page.',
    'no_rate_plan' => ':units free · It has no rate plan yet, so no price.',
    'add_rate_plan_link' => 'Add a rate plan on the Rates page',
    'manager_can_add_plan' => 'Someone who manages rates can add one.',
    'nothing_free' => 'Nothing is free for these dates — try other dates.',
    'source' => 'Source',
    'pick_unit_above' => 'Pick a unit above',
    'room_only' => 'Room only — the property’s taxes and fees are added on the folio.',
    // :link is the "Open the full form" link
    'books_straight_away' => 'Books it straight away, as a new guest — no extras, payment or email from here; if a stay rule such as a minimum stay applies, the full form opens to sort it out. :link for those.',
    'booking_busy' => 'Booking…',
    'confirm_reservation' => 'Confirm reservation',
    // shown under the season disclosure; the count is the stay's nights with no price
    'for_this_booking' => '{1} For this booking: it prices :prices of the :count night of the stay that has no price.|[0,*] For this booking: it prices :prices of the :count nights of the stay that have no price.',
    'apply_and_overwrite' => 'Apply and overwrite those nights',
];
