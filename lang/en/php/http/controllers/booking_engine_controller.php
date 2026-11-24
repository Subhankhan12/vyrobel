<?php

// app/Http/Controllers/BookingEngineController.php — the public storefront (browse, room, checkout, confirmation).

return [
    // the signed-in guest's name in the storefront header when the account has no first name
    'guest_fallback_name' => 'You',
    // one line the total folds in: ":amount :name" → "€58.65 VAT"
    'total_part' => ':amount :name',
    'total_includes' => 'incl. :items',
    'nightly_excludes' => 'excl. :tax',
    // room card chips
    'sleeps' => 'Sleeps :count',
    'studio' => 'Studio',
    'bedrooms' => '{1} :count bedroom|[0,*] :count bedrooms',
    'checkout_title' => 'Checkout — :property',
    'room_just_taken' => 'That room was just booked by someone else — please pick another room or dates.',
    // the confirmation page's payment state
    'payment_received' => 'Payment received',
    'payment_received_note' => 'Paid in full.',
    'payment_processing' => 'Payment processing',
    'payment_processing_note' => 'We’re confirming your card payment. Refresh this page in a moment to see it land.',
    'pay_on_arrival' => 'Pay on arrival',
    'pay_on_arrival_note' => 'Settle at the property when you check in.',
    'confirmed_title' => 'Booking confirmed — :property',
    // room kinds
    'kind_room' => 'Room',
    'kind_apartment' => 'Apartment',
    'kind_villa' => 'Villa',
    'kind_dorm' => 'Dorm',
    // property types
    'type_hotel' => 'Hotel',
    'type_aparthotel' => 'Aparthotel',
    'type_serviced_apartment' => 'Serviced Apartment',
    'type_apartment_building' => 'Apartment Building',
    'type_apartment' => 'Apartment',
    'type_villa' => 'Villa',
    'type_hostel' => 'Hostel',
    'type_guest_house' => 'Guest House',
    'type_resort' => 'Resort',
    'type_mixed' => 'Mixed',
    'type_portfolio' => 'Portfolio',
    'terms_title' => 'Booking terms — :property',
    'privacy_title' => 'Privacy notice — :property',
    'terms_required' => 'Please accept the booking terms and the privacy notice to continue.',
    'terms_changed' => 'The property has updated its booking terms. Please read them again and accept to continue.',
    'checkout_page_stale' => 'This page was open for a while and is out of date. Please check your details and confirm again.',
    'deposit_unavailable' => 'This property asks for a deposit by card when you book, and it cannot take card payments online yet. Please contact the property to book.',
    'deposit_received' => 'Deposit received',
    'deposit_received_note' => 'The rest, :amount, is paid at the property.',
    'released' => 'Booking not kept',
    'released_note' => 'The card payment was not completed in time, so this booking was released. To stay here, please book again.',
    'payment_not_completed' => 'Payment not completed',
    'payment_not_completed_note' => 'Your card payment was not completed, so this booking is not confirmed. If it is not paid, it is released shortly. To stay here, you can book again.',
];
