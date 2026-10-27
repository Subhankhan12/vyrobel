{{-- SUPER ADMIN · BUILD 2 · GATE 2 — manage one channel's master credential. 🔒 Write-only: the stored secret is
     NEVER echoed — only "configured" is shown; entering a value ROTATES it. --}}
@php $configured = $integration && $integration->isConfigured(); @endphp
<x-admin.shell active="integrations" :title="$label"
    :subtitle="$configured
        ? __('admin/integrations/edit.subtitle_configured')
        : __('admin/integrations/edit.subtitle_none', ['channel' => $label])"
    :back="route('admin.integrations.index')" :back-label="__('admin/integrations/edit.back_label')">


    <form method="POST" action="{{ route('admin.integrations.update', $channel) }}" style="max-width: 560px;" data-guard-submit>
        @csrf
        @method('PUT')
        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/integrations/edit.master_credential') }}</h2>
            <div class="ad-field" style="margin-bottom: 14px;">
                <label class="ad-label" for="api_key">{{ __('admin/integrations/edit.api_key') }}</label>
                <input class="ad-input" type="password" id="api_key" name="api_key" autocomplete="off"
                       placeholder="{{ $configured ? __('admin/integrations/edit.api_key_placeholder_configured') : __('admin/integrations/edit.api_key_placeholder') }}">
            </div>
            <div class="ad-field" style="margin-bottom: 14px;">
                <label class="ad-label" for="api_secret">{!! __('admin/integrations/edit.api_secret') !!}</label>
                <input class="ad-input" type="password" id="api_secret" name="api_secret" autocomplete="off"
                       placeholder="{{ $configured ? __('admin/integrations/edit.api_secret_placeholder_configured') : __('admin/integrations/edit.api_secret_placeholder') }}">
            </div>
            <label style="display: flex; align-items: center; gap: 9px; font-size: 13.5px; color: var(--ad-muted); cursor: pointer;">
                <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $integration?->is_enabled))>
                {{ __('admin/integrations/edit.enabled') }}
            </label>
            <p class="ad-stat-sub" style="margin-top: 14px;">{{ __('admin/integrations/edit.stored_encrypted') }}</p>
        </div>

        @include('admin.partials.reason', ['placeholder' => __('admin/integrations/edit.reason_placeholder')])

        <div style="display: flex; gap: 10px; margin-top: 18px;">
            <button type="submit" class="ad-btn">{{ __('admin/integrations/edit.save_credential') }}</button>
            <a href="{{ route('admin.integrations.index') }}" class="ad-btn ad-btn--ghost">{{ __('common.cancel') }}</a>
        </div>
    </form>

    @if ($configured)
        {{-- Separate form so it POSTs (not the update form's PUT). The real channel API is account-gated. --}}
        <form method="POST" action="{{ route('admin.integrations.test', $channel) }}" style="max-width: 560px; margin-top: 12px;" data-guard-submit>
            @csrf
            <input class="ad-input" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/integrations/edit.test_reason_placeholder') }}" aria-label="{{ __('admin/integrations/edit.test_reason_aria') }}" style="max-width: 260px; margin-bottom: 8px;">
            <button type="submit" class="ad-btn ad-btn--ghost">{{ __('admin/integrations/edit.test_connection') }}</button>
            <span class="ad-stat-sub" style="margin-left: 8px;">{{ __('admin/integrations/edit.test_hint', ['channel' => $label]) }}</span>
        </form>
    @endif
</x-admin.shell>
