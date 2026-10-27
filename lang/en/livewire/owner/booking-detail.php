<?php

// resources/views/livewire/owner/booking-detail.blade.php — the owner's one-booking page.

return [
    'breadcrumb_overview' => 'Overview',
    'breadcrumb_bookings' => 'Bookings',
    // shown in place of the guest's name when the guest has been anonymized
    'guest_fallback' => 'Guest',
    'reservation' => 'Reservation',
    'check_in' => 'Check-in',
    'nights_count' => '{1} :count night|[0,*] :count nights',
    'check_out' => 'Check-out',
    'unit' => 'Unit',
    'guests' => 'Guests',
    'nights' => 'Nights',
    'nightly_rate' => 'Nightly rate',
    'your_earnings' => 'Your earnings',
    'gross_to_net' => 'gross → net',
    'gross_booking_value' => 'Gross booking value',
    'net_to_you' => 'Net to you',
    'refunds_we_covered' => 'Refunds we covered',
    'refunds_we_covered_note' => 'Refunded to the guest and charged to us, not to you. Your earnings above are unchanged.',
    'not_yet_attributed' => 'Not yet attributed.',
    'not_yet_attributed_note' => 'Your earnings appear here once this booking is attributed to your ledger — usually after check-out.',
    // the heading of the guest card
    'guest_heading' => 'Guest',
    'guest_contact_note' => 'Guest contact details are handled by your management team — you see the booking and your earnings.',
    'message_your_team' => 'Message your team',
    'timeline' => 'Timeline',
    // under "Net to you": "for this 3-night stay"
    'for_night_stay' => 'for this :nights-night stay',
    'booking_details' => 'Booking details',
    'confirmation' => 'Confirmation',
    'source' => 'Source',
    'channel' => 'Channel',
    'booked_on' => 'Booked on',
    'download_confirmation' => 'Download confirmation',
    'download_unavailable' => 'Available to co-owners and accountants',
    'view_unit' => 'View :unit',
];
