{{-- 404 — the page genuinely does not exist, OR a tenant/owner/guest scope resolved it to nothing. The
     copy is deliberately identical either way: telling an actor "this exists but is not yours" would leak
     the existence of another organisation's record (#38 answers foreign ids with 404, not 403, for exactly
     this reason). --}}
@include('errors.layout', [
    'code' => 404,
    'title' => __('errors/404.title'),
    'message' => __('errors/404.message'),
])
