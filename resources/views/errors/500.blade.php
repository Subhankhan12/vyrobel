{{-- 500 — something failed on our side. ⚠️ It must NEVER surface the exception, the trace, the SQL or a file
     path; the shared layout never prints them. With APP_DEBUG=true Laravel shows its own debug page instead,
     so developers lose no detail. --}}
@include('errors.layout', [
    'code' => 500,
    'title' => 'Something went wrong',
    'message' => 'That’s on us, not you. The error has been logged. Nothing you were working on has been lost.',
])
