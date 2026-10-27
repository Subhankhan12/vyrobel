{{-- ADMIN 01 · PHASE 1 — one neutral page for an unknown, expired or spent invitation link, so a link cannot be
     probed for whether it ever existed. --}}
<x-admin.auth :title="__('admin/auth/invite-invalid.title')" :heading="__('admin/auth/invite-invalid.heading')" :sub="$message">
    <p class="ad-note"><a class="ad-link" href="{{ route('admin.login') }}">{{ __('admin/auth/invite-invalid.go_to_sign_in') }}</a></p>
</x-admin.auth>
