{{-- SUPER ADMIN · BUILD 2 · GATE 2 — manage one channel's master credential. 🔒 Write-only: the stored secret is
     NEVER echoed — only "configured" is shown; entering a value ROTATES it. --}}
@php $configured = $integration && $integration->isConfigured(); @endphp
<x-admin.shell active="integrations" :title="'Integration · '.$label">
    <a href="{{ route('admin.integrations.index') }}" class="ad-back">← All integrations</a>
    <div class="ad-eyebrow">Integration</div>
    <h1 class="ad-h1">{{ $label }}</h1>
    <p class="ad-lead">
        @if ($configured)
            A master credential is configured (encrypted). Enter a new value below only to rotate it.
        @else
            No credential yet. Add your platform master credential for {{ $label }}.
        @endif
    </p>

    @if ($errors->any())
        <div class="ad-flash" style="background: rgba(245,158,158,.1); border-color: rgba(245,158,158,.34); color: #FBD5D5;">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.integrations.update', $channel) }}" style="max-width: 560px;" data-guard-submit>
        @csrf
        @method('PUT')
        <div class="ad-card">
            <h2 class="ad-panel-title">Master credential</h2>
            <div class="ad-field" style="margin-bottom: 14px;">
                <label class="ad-label" for="api_key">API key</label>
                <input class="ad-input" type="password" id="api_key" name="api_key" autocomplete="off"
                       placeholder="{{ $configured ? '•••••••• (enter a new value to rotate)' : 'Enter the API key' }}">
            </div>
            <div class="ad-field" style="margin-bottom: 14px;">
                <label class="ad-label" for="api_secret">API secret <span style="text-transform: none; letter-spacing: 0; color: var(--ad-faint);">· optional</span></label>
                <input class="ad-input" type="password" id="api_secret" name="api_secret" autocomplete="off"
                       placeholder="{{ $configured ? '•••••••• (leave blank to keep)' : 'Enter the API secret' }}">
            </div>
            <label style="display: flex; align-items: center; gap: 9px; font-size: 13.5px; color: var(--ad-muted); cursor: pointer;">
                <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $integration?->is_enabled))>
                Enabled
            </label>
            <p class="ad-stat-sub" style="margin-top: 14px;">🔒 Stored encrypted at rest. The secret is never displayed again — you can only replace it.</p>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 18px;">
            <button type="submit" class="ad-btn">Save credential</button>
            <a href="{{ route('admin.integrations.index') }}" class="ad-btn ad-btn--ghost">Cancel</a>
        </div>
    </form>

    @if ($configured)
        {{-- Separate form so it POSTs (not the update form's PUT). The real channel API is account-gated. --}}
        <form method="POST" action="{{ route('admin.integrations.test', $channel) }}" style="max-width: 560px; margin-top: 12px;" data-guard-submit>
            @csrf
            <button type="submit" class="ad-btn ad-btn--ghost">Test connection</button>
            <span class="ad-stat-sub" style="margin-left: 8px;">Verifies a credential is present; the live test runs when the {{ $label }} account is connected.</span>
        </form>
    @endif
</x-admin.shell>
