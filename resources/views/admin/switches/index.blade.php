{{-- ADMIN 01 · PHASE 1 — the platform KILL SWITCHES. Stored in the database (never .env — L14), so a flip takes
     effect on the next request without a deploy. A missing row is ON. ⚠️ None of them touches /admin. --}}
@php
    $admin = auth('admin')->user();
    $canFlip = $admin?->may('switches.manage') ?? false;
@endphp
<x-admin.shell active="switches" :title="__('admin/switches/index.title')"
    :subtitle="__('admin/switches/index.subtitle')">


    <div class="ad-card" style="margin-bottom: 18px;">
        <p class="ad-stat-sub" style="margin: 0; line-height: 1.6;">
            {!! __('admin/switches/index.explainer') !!}
        </p>
    </div>

    <div style="display: grid; gap: 14px;">
        @foreach ($catalogue as $key => $meta)
            @php $state = $states[$key]; @endphp
            <div class="ad-card" style="padding: 18px 22px;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div style="min-width: 0; flex: 1 1 320px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h2 class="ad-panel-title" style="margin: 0;">{{ $meta['label'] }}</h2>
                            <span class="ad-hpill {{ $state['on'] ? 'ad-hpill--green' : 'ad-hpill--amber' }}">{{ $state['on'] ? __('admin/switches/index.state_on') : __('admin/switches/index.state_off') }}</span>
                        </div>
                        <p class="ad-stat-sub" style="margin: 6px 0 0;">{{ $meta['controls'] }}</p>
                        <p class="ad-reason-hint" style="margin: 6px 0 0;">
                            @if ($state['updated_at'])
                                {{ $state['updated_by'] && isset($updaters[$state['updated_by']])
                                    ? __('admin/switches/index.last_changed_by', ['time' => $state['updated_at'], 'name' => $updaters[$state['updated_by']]])
                                    : __('admin/switches/index.last_changed', ['time' => $state['updated_at']]) }}
                            @else
                                {{ __('admin/switches/index.never_changed') }}
                            @endif
                        </p>
                        @if (isset($lockouts[$key]))
                            <p class="ad-stat-sub" style="margin: 8px 0 0;">
                                @if ($lockouts[$key] === 0)
                                    {{ __('admin/switches/index.no_account_relies') }}
                                @else
                                    {!! trans_choice('admin/switches/index.would_lock_out', $lockouts[$key], ['count' => e((string) $lockouts[$key])]) !!}
                                @endif
                            </p>
                        @endif
                    </div>

                    @if ($canFlip)
                        <form method="POST" action="{{ route('admin.switches.update', $key) }}" style="flex: 1 1 300px; max-width: 420px;" data-guard-submit>
                            @csrf
                            <input type="hidden" name="on" value="{{ $state['on'] ? '0' : '1' }}">
                            <label class="ad-label" for="reason-{{ $loop->index }}">{{ __('admin/switches/index.reason') }}</label>
                            <input aria-label="{{ __('admin/switches/index.reason') }}" class="ad-input" id="reason-{{ $loop->index }}" name="reason" required minlength="5" maxlength="500"
                                   placeholder="{{ $state['on'] ? __('admin/switches/index.why_turn_off') : __('admin/switches/index.why_turn_on') }}">
                            @if ($state['on'] && isset($lockouts[$key]))
                                <label style="display: flex; gap: 8px; align-items: flex-start; font-size: 12.5px; margin: 10px 0 0; color: var(--ad-ink);">
                                    <input type="checkbox" name="confirm_lockout" value="1" required style="margin-top: 2px;">
                                    {{-- ⚠️ ADMIN 03 · F20 — still required at zero (the count can change between loading this page and
                                         pressing the button — SwitchController), but it no longer asks him to "understand" locking out 0. --}}
                                    @if ($lockouts[$key] === 0)
                                        {{ __('admin/switches/index.confirm_lockout_zero') }}
                                    @else
                                        {{ trans_choice('admin/switches/index.confirm_lockout', $lockouts[$key], ['count' => $lockouts[$key]]) }}
                                    @endif
                                </label>
                            @endif
                            <button type="submit" class="ad-btn {{ $state['on'] ? 'ad-btn--danger' : '' }} ad-btn--sm" style="margin-top: 10px;">
                                {{ $state['on'] ? __('admin/switches/index.turn_off') : __('admin/switches/index.turn_on') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-admin.shell>
