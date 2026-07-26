{{-- SUPER ADMIN · GATE 0 — the one-time recovery codes, shown ONCE right after enrollment is confirmed. --}}
<x-admin.auth title="Recovery codes" heading="Save your recovery codes" sub="Store these somewhere safe — each works once if you lose your device.">
    <div style="background: rgba(9,14,24,.5); border: 1px solid var(--ad-border); border-radius: 12px; padding: 16px 18px; margin-bottom: 16px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 18px; font-family: ui-monospace, monospace; font-size: 13.5px; letter-spacing: .06em; color: var(--ad-ink);">
            @foreach ($codes as $code)
                <div>{{ $code }}</div>
            @endforeach
        </div>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="ad-btn" style="text-decoration: none;">I've saved them — continue</a>
    <p class="ad-note">These are shown only once. If you lose them, you can regenerate a new set from your account later.</p>
</x-admin.auth>
