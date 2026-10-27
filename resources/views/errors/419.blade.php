{{-- 419 — CSRF token expired (a form left open past the session lifetime). The most common "scary" error a
     real operator hits; the copy tells them the fix rather than the mechanism. --}}
@include('errors.layout', [
    'code' => 419,
    'title' => __('errors/419.title'),
    'message' => __('errors/419.message'),
])
