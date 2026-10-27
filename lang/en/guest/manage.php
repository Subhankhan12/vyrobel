<?php

// resources/views/guest/manage.blade.php — the guest Manage Booking page (date change, extras, cancellation requests).

return [
    'page_title' => 'Manage :reference',
    'back_to_booking' => '← Back to booking',
    'heading' => 'Manage your booking',
    'subheading' => ':property · :check_in → :check_out. Changes are confirmed by the property — we’ll message you the moment they are.',
    'pending_requests' => 'Pending requests',
    'pending_badge' => 'Pending',
    'pending_note' => 'Nothing has changed on your booking yet — a request is confirmed by the property first.',
    'pick_new_dates' => 'Pick new dates',
    'currently' => 'Currently :check_in → :check_out. Pick new dates to see the price difference, then request the change.',
    'new_check_in' => 'New check-in',
    'new_check_out' => 'New check-out',
    'see_price_difference' => 'See price difference',
    'new_stay' => 'New stay',
    'current' => 'Current',
    'difference' => 'Difference',
    'estimate_note' => 'Estimate only — subject to the property’s confirmation. Any payment is handled after they confirm.',
    'unpriced_note' => 'Those dates don’t have a published price yet — request the change and the property will confirm the price.',
    'request_date_change' => 'Request date change',
    'add_to_your_stay' => 'Add to your stay',
    'add_to_your_stay_sub' => 'Add breakfast, transfers and more — everything settles to your booking on confirm.',
    'browse_extras' => 'Browse extras',
    'cancellation_policy' => 'Cancellation policy',
    // :date is wrapped in <strong>; printed with {!! !!}, the date escaped
    'free_until' => 'Free cancellation until <strong>:date</strong> — a request cancelled before then is refunded in full.',
    'policy_by_property' => 'The property confirms your cancellation and handles any refund per their policy.',
    'cancel_confirm' => 'Send a cancellation request for this booking? The property reviews it and handles any refund per their policy.',
    'reason' => 'Reason',
    'choose_reason' => 'Choose a reason…',
    'request_cancellation' => 'Request cancellation',
    'closed_heading' => 'This booking has closed',
    'closed_body' => 'There is nothing left to change on it — but your dates, your stay details and anything you paid are still here whenever you need them.',
];
