{{-- 403 — authenticated, but the permission gate said no (invariant #30). Names the cause honestly rather
     than pretending the page is missing: the actor exists and their role is simply not permitted. --}}
@include('errors.layout', [
    'code' => 403,
    'title' => 'You don’t have access to this',
    'message' => 'Your role doesn’t include this area. If you need it, ask an administrator on your team to grant the permission.',
])
