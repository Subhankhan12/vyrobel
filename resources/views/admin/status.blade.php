{{-- ⚠️ ADMIN 01 · PHASE 5 — THE PUBLIC STATUS PAGE'S INCIDENTS, written by hand. ⚠️ EVERYTHING POSTED HERE IS PUBLIC. --}}
@php
    use App\Models\StatusIncident;
    $me = auth('admin')->user();
    $canPost = $me?->may('tenants.manage') ?? false;
@endphp
<x-admin.shell active="status" :title="__('admin/status.title')" :subtitle="__('admin/status.subtitle')">


    <div class="ad-card" style="margin-bottom: 18px;">
        <p class="ad-stat-sub" style="margin: 0; line-height: 1.6;">
            ⚠️ {!! __('admin/status.public_warning', ['url' => e(route('status'))]) !!}
            {{ __('admin/status.shows_only_posted') }}
        </p>
    </div>

    <div class="ad-card" style="overflow-x: auto;">
        @if ($incidents->isEmpty())
            <div class="ad-empty">{{ __('admin/status.none_ever_posted') }}</div>
        @else
            @foreach ($incidents as $i)
                <div style="padding: 12px 0; border-bottom: 1px solid var(--ad-border);">
                    <strong>{{ $i->title }}</strong> · {{ StatusIncident::impactLabel((string) $i->impact) }} · {{ StatusIncident::statusLabel((string) $i->status) }}
                    <div style="font-size: 12px; color: var(--ad-muted);">{{ __('admin/status.started') }} <x-admin.time :at="$i->started_at" />@if ($i->resolved_at) · {{ __('admin/status.resolved') }} <x-admin.time :at="$i->resolved_at" />@endif</div>
                    <p class="ad-stat-sub" style="margin: 6px 0;">{{ $i->message }}</p>
                    @if ($canPost && $i->isOpen())
                        <form method="POST" action="{{ route('admin.status.update', $i->id) }}" data-guard-submit style="display: grid; grid-template-columns: 180px minmax(0, 1fr); gap: 8px;">
                            @csrf
                            <select aria-label="{{ __('admin/status.status_aria') }}" name="status" class="ad-input">@foreach (array_keys(StatusIncident::STATUSES) as $k)<option value="{{ $k }}" @selected($i->status === $k)>{{ StatusIncident::statusLabel($k) }}</option>@endforeach</select>
                            <input aria-label="{{ __('admin/status.public_message_aria') }}" class="ad-input" name="message" required maxlength="2000" placeholder="{{ __('admin/status.public_update_placeholder') }}">
                            <input aria-label="{{ __('admin/status.reason_aria') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/status.internal_reason_placeholder') }}" style="grid-column: 1 / -1;">
                            <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm" style="grid-column: 1 / -1; justify-self: start;">{{ __('admin/status.post_update') }}</button>
                        </form>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    @if ($canPost)
        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">{{ __('admin/status.post_incident_heading') }}</h2>
            <form method="POST" action="{{ route('admin.status.store') }}" data-guard-submit>
                @csrf
                <label class="ad-label" for="si-title">{{ __('admin/status.public_title') }}</label>
                <input id="si-title" class="ad-input" name="title" required maxlength="120">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                    <div><label class="ad-label" for="si-impact">{{ __('admin/status.impact') }}</label><select id="si-impact" name="impact" class="ad-input">@foreach (array_keys(StatusIncident::IMPACTS) as $k)<option value="{{ $k }}">{{ StatusIncident::impactLabel($k) }}</option>@endforeach</select></div>
                    <div><label class="ad-label" for="si-status">{{ __('common.status') }}</label><select id="si-status" name="status" class="ad-input">@foreach (['investigating', 'identified', 'monitoring'] as $k)<option value="{{ $k }}">{{ StatusIncident::statusLabel($k) }}</option>@endforeach</select></div>
                </div>
                <label class="ad-label" for="si-msg" style="margin-top: 10px;">{{ __('admin/status.public_message') }}</label>
                <textarea id="si-msg" class="ad-input" name="message" required maxlength="2000" rows="3"></textarea>
                <label class="ad-label" for="si-reason" style="margin-top: 10px;">{{ __('admin/status.internal_reason_label') }}</label>
                <input id="si-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500">
                <button type="submit" class="ad-btn" style="margin-top: 12px;">{{ __('admin/status.post_publicly') }}</button>
            </form>
        </div>
    @endif
</x-admin.shell>
