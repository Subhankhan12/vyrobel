{{-- 429 — the rate limiter fired (the per-credential login throttle, the per-IP net, or the booking-engine
     limit). Reassuring for a legitimate user who simply retried too fast; uninformative to an attacker. --}}
@include('errors.layout', [
    'code' => 429,
    'title' => 'Too many attempts',
    'message' => 'For safety we’ve paused requests from here for a moment. Wait a minute and try again.',
])
