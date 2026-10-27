{{-- SUPER ADMIN · GATE 0 — the one-time recovery codes, shown ONCE right after enrollment is confirmed. --}}
<x-admin.auth :title="__('admin/auth/two-factor-recovery.title')" :heading="__('admin/auth/two-factor-recovery.heading')" :sub="__('admin/auth/two-factor-recovery.sub')">
    <div class="ad-well" style="padding: 16px 18px; margin-bottom: 16px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 18px; font-family: ui-monospace, monospace; font-size: 13.5px; letter-spacing: .06em; color: var(--ad-ink);">
            @foreach ($codes as $code)
                <div>{{ $code }}</div>
            @endforeach
        </div>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="ad-btn" style="text-decoration: none;">{{ __('admin/auth/two-factor-recovery.saved_continue') }}</a>
    <p class="ad-note">{{ __('admin/auth/two-factor-recovery.shown_once_note') }}</p>
</x-admin.auth>
