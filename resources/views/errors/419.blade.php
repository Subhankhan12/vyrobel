{{-- 419 — CSRF token expired (a form left open past the session lifetime). The most common "scary" error a
     real operator hits; the copy tells them the fix rather than the mechanism. --}}
@include('errors.layout', [
    'code' => 419,
    'title' => 'That page expired',
    'message' => 'You were away long enough for the form to expire, so nothing was submitted. Go back and try again — your data is untouched.',
])
