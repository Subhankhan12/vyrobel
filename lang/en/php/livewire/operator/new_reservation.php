<?php

// app/Livewire/Operator/NewReservation.php — the operator back office's New reservation wizard component (/app/reservations/create).

return [
    // the wizard's step names (Extras only where the property has some)
    'step_stay' => 'Stay',
    'step_guest' => 'Guest',
    'step_extras' => 'Extras',
    'step_confirm' => 'Confirm',
    // the Booking source choices on the Confirm step
    'source_direct' => 'Direct',
    'source_walk_in' => 'Walk-in',
    'source_corporate' => 'Corporate',
    'source_agent' => 'Travel agent',
    // the done screen: who scans the check-in code, when the guest has no name (starts a sentence, lower case as before)
    'the_guest' => 'the guest',
    // a room type's nightly price over the stay — one price, or the lowest–highest when the nights differ
    'a_night' => ':amount a night',
    'a_night_range' => ':low–:high a night',
    // the stay's dates and length, e.g. "2 Oct → 5 Oct 2026 · 3 nights"
    'dates_label' => '{1} :from → :to · :count night|[0,*] :from → :to · :count nights',
    // a refused booking's restrictions, in plain words (:date like "Wed 2 Sep")
    'restrict_min_stay' => '{1} Arriving :date, a stay must be at least :count night|[0,*] Arriving :date, a stay must be at least :count nights',
    'restrict_max_stay' => '{1} Arriving :date, a stay can be at most :count night|[0,*] Arriving :date, a stay can be at most :count nights',
    'restrict_no_arrivals' => 'No arrivals on :date',
    'restrict_no_departures' => 'No departures on :date',
    'restrict_stop_sell' => ':date is closed to new bookings',
    // any other restriction: its code in words, then the date
    'restrict_other' => ':restriction on :date',
    // how the guest gets directions and the door code (:count = days before check-in the pre-arrival email goes)
    'arrival_mail_off' => 'Email isn’t being sent from Vyrobel yet — give the guest directions and the door code yourself.',
    'arrival_pre_arrival_off' => 'The pre-arrival email is switched off for your properties — give the guest directions and the door code yourself.',
    'arrival_too_soon' => 'This stay starts too soon for the pre-arrival email, which goes :count days before check-in — give the guest directions and the door code yourself.',
    'arrival_by_email' => 'Directions, and the door code where the unit has one, reach the guest by email :count days before check-in — when they have an email address. The door code is never shown here.',
    // the house rules' cancellation line
    'cancel_not_free' => 'Not free for this stay — it ended :date',
    'cancel_free_until_check_in' => 'Free until check-in',
    'cancel_free_until_date' => 'Free until :date',
    'cancel_free_until_days' => '{1} Free until :count day before check-in|[0,*] Free until :count days before check-in',
    // the house rules' minimum stay line
    'min_stay_none' => 'No minimum',
    'min_stay_nights' => '{1} :count night|[0,*] :count nights',
    // a returning guest in the search with no name on file, and one with no stays
    'guest_no_name' => 'Guest',
    'no_stays_yet' => 'No stays yet',
];
