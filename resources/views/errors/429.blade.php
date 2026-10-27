{{-- 429 — the rate limiter fired (the per-credential login throttle, the per-IP net, or the booking-engine
     limit). Reassuring for a legitimate user who simply retried too fast; uninformative to an attacker. --}}
@include('errors.layout', [
    'code' => 429,
    'title' => __('errors/429.title'),
    'message' => __('errors/429.message'),
])
