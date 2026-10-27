{{-- The Security kit's sign-in rows that BOTH pages show (2026-10-24, Part 3 of the founder's design): login alerts and the
     session timeout. $alerts = SecurityOverview's ['key', 'on', 'always_on', 'email']; $timeout = ['minutes', 'options'];
     $routes = ['alerts' => route name|null, 'timeout' => route name]. --}}
@props(['alerts', 'timeout', 'routes'])

{{-- LOGIN ALERTS — the person's own sign-ins from a device their account has not used (NewDeviceSignIn) --}}
<div class="vy-sx-block" id="sx-alerts">
    <div class="vy-sx-row">
        <div class="vy-sx-row-main">
            <span class="vy-sx-row-title">{{ __('components/security/protection-rows.login_alerts') }}
                @if ($alerts['always_on'])
                    <span class="vy-sx-chip">{{ __('components/security/protection-rows.always_on') }}</span>
                @endif
            </span>
            <span class="vy-sx-row-meta">
                {{ $alerts['email'] ? __('components/security/protection-rows.alerts_meta_bell_and_email') : __('components/security/protection-rows.alerts_meta_bell') }}
                @unless ($alerts['email']) {{ __('components/security/protection-rows.alerts_email_not_sent') }} @endunless
            </span>
        </div>
        @unless ($alerts['always_on'])
            <form method="POST" action="{{ route($routes['alerts']) }}">
                @csrf
                <input type="hidden" name="enabled" value="{{ $alerts['on'] ? '0' : '1' }}">
                <button type="submit" class="vy-sx-switch" role="switch" aria-checked="{{ $alerts['on'] ? 'true' : 'false' }}" aria-label="{{ __('components/security/protection-rows.login_alerts') }}"></button>
            </form>
        @endunless
    </div>
</div>

{{-- SESSION TIMEOUT — sign out after this long without using Vyrobel (SessionTimeout); background refreshes don't count --}}
<div class="vy-sx-block" id="sx-timeout">
    <div class="vy-sx-row is-stack">
        <div class="vy-sx-row-main">
            <span class="vy-sx-row-title">{{ __('components/security/protection-rows.session_timeout') }}</span>
            <span class="vy-sx-row-meta">{{ __('components/security/protection-rows.session_timeout_meta') }}</span>
        </div>
        <form method="POST" action="{{ route($routes['timeout']) }}" class="vy-sx-timeout">
            @csrf
            <label class="vy-sx-sr" for="vy-sx-timeout">{{ __('components/security/protection-rows.session_timeout') }}</label>
            <select id="vy-sx-timeout" name="minutes" class="vy-sx-select" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                <option value="" @selected($timeout['minutes'] === null)>{{ \App\Support\Security\SessionTimeout::label(null) }}</option>
                @foreach (\App\Support\Security\SessionTimeout::OPTIONS as $m)
                    <option value="{{ $m }}" @selected($timeout['minutes'] === $m)>{{ \App\Support\Security\SessionTimeout::label($m) }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm">{{ __('common.save') }}</button></noscript>
        </form>
    </div>
</div>
