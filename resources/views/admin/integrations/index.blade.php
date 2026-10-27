{{-- SUPER ADMIN · BUILD 2 · GATE 2 — the channel-integration catalog. Master platform credentials, encrypted at
     rest; the real sync is account-gated (labelled honestly). --}}
@php use App\Models\PlatformIntegration; @endphp
<x-admin.shell active="integrations" :title="__('admin/integrations/index.title')"
    :subtitle="__('admin/integrations/index.subtitle')">


    <div class="ad-card" style="padding: 8px 10px;">
        <table class="ad-table">
            <thead>
                <tr><th>{{ __('admin/integrations/index.col_channel') }}</th><th>{{ __('admin/integrations/index.col_credential') }}</th><th>{{ __('common.status') }}</th><th>{{ __('admin/integrations/index.col_last_tested') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach (PlatformIntegration::CHANNELS as $key => $label)
                    @php $i = $integrations->get($key); @endphp
                    <tr>
                        <td><a class="ad-cell-link" href="{{ route('admin.integrations.edit', $key) }}">{{ $label }}</a></td>
                        <td>
                            @if ($i && $i->isConfigured())
                                <span style="font-family: ui-monospace, monospace; letter-spacing: .1em; color: var(--ad-muted);">••••••••</span>
                            @else
                                <span style="color: var(--ad-faint);">{{ __('admin/integrations/index.not_set') }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($i && $i->status === 'tested')
                                <span class="ad-pill ad-pill--active">{{ __('admin/integrations/index.status_tested') }}</span>
                            @elseif ($i && $i->isConfigured())
                                <span class="ad-pill ad-pill--trialing">{{ __('admin/integrations/index.status_configured') }}</span>
                            @else
                                <span class="ad-pill ad-pill--cancelled">{{ __('admin/integrations/index.status_not_configured') }}</span>
                            @endif
                            @if ($i && $i->is_enabled)
                                <span class="ad-pill ad-pill--active" style="margin-left: 4px;">{{ __('admin/integrations/index.enabled') }}</span>
                            @endif
                        </td>
                        <td style="color: var(--ad-muted);"><x-admin.time :at="$i?->last_tested_at" /></td>
                        <td style="text-align: right;"><a href="{{ route('admin.integrations.edit', $key) }}" class="ad-btn ad-btn--ghost ad-btn--sm">{{ __('admin/integrations/index.manage') }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="ad-card" style="margin-top: 18px;">
        <h2 class="ad-panel-title">{{ __('admin/integrations/index.how_customers_connect') }}</h2>
        <p class="ad-stat-sub" style="line-height: 1.65;">
            {!! __('admin/integrations/index.how_customers_connect_body') !!}
        </p>
    </div>
</x-admin.shell>
