{{-- ADMIN 01 · PHASE 1 — one neutral page for an unknown, expired or spent invitation link, so a link cannot be
     probed for whether it ever existed. --}}
<x-admin.auth title="Invitation" heading="This link can't be used" :sub="$message">
    <p class="ad-note"><a class="ad-link" href="{{ route('admin.login') }}">Go to the admin sign-in</a></p>
</x-admin.auth>
