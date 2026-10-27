<?php

// app/Http/Controllers/Operator/EmailPreviewController.php — the operator's Email previews gallery and its test send.

return [
    'booking_confirmation_title' => 'Booking confirmation',
    'booking_confirmation_status' => 'Live · sends on booking confirmed',
    'cancellation_title' => 'Cancellation',
    'cancellation_status' => 'Live · sends on booking cancelled',
    'refund_confirmation_title' => 'Refund confirmation',
    'refund_confirmation_status' => 'Live · sends on refund processed',
    'payment_reminder_title' => 'Payment reminder',
    'payment_reminder_status' => 'Live · daily emails:send-payment-reminders',
    'pre_arrival_title' => 'Pre-arrival',
    'pre_arrival_status' => 'Live · daily emails:send-pre-arrival (48h before)',
    'review_request_title' => 'Review request',
    'review_request_status' => 'Live · sends on check-out',
    'waitlist_notification_title' => 'Waitlist notification',
    'waitlist_notification_status' => 'Template ready · trigger deferred (Phase 4 · Waitlist)',
    'winback_title' => 'Winback',
    'winback_status' => 'Template ready · trigger deferred (scheduler)',
    'verify_email_title' => 'Verify email',
    'verify_email_status' => 'Live · sends on booking-tied account claim',
    'no_sample_reservation' => 'No sample reservation exists in this organization yet — create a booking first.',
    'no_email_address' => 'Your account has no email address to send a test to.',
    'test_sent' => 'Test email sent to :email.',
    // the sample's property name when the reservation has none
    'sample_property_fallback' => 'Your property',
    // the sample cancellation reason the preview shows
    'sample_cancellation_reason' => 'Guest requested cancellation',
];
