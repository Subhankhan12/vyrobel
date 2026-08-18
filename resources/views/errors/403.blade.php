{{-- 403 — authenticated, but the permission gate said no (invariant #30). Names the cause honestly rather
     than pretending the page is missing: the actor exists and their role is simply not permitted.

     ⚠️ THE BODY IS REALM-BRANCHED (guest fix pass, F5). This view used to hardcode one operator-framed
     message — "your role… ask an administrator on your team" — which was shown to guests and owners, who
     have neither. The branch reads the SAME derivation the layout uses for the back-link, so the two halves
     of this page can never describe different realms. --}}
@include('errors.layout', [
    'code' => 403,
    'title' => 'You don’t have access to this',
    'message' => \App\Support\ErrorRealm::forbiddenMessage(),
])
