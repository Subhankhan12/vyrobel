{{-- 500 — something failed on our side. ⚠️ It must NEVER surface the exception, the trace, the SQL or a file
     path; the shared layout never prints them. With APP_DEBUG=true Laravel shows its own debug page instead,
     so developers lose no detail. --}}
@include('errors.layout', [
    'code' => 500,
    'title' => __('errors/500.title'),
    'message' => __('errors/500.message'),
])
