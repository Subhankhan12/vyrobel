{{-- The Security kit's sign-in rows that BOTH pages show (2026-10-24, Part 3 of the founder's design): login alerts and the
     session timeout. $alerts = SecurityOverview's ['key', 'on', 'always_on', 'email']; $timeout = ['minutes', 'options'];
     $routes = ['alerts' => route name|null, 'timeout' => route name]. --}}
@props(['alerts', 'timeout', 'routes'])

{{-- LOGIN ALERTS — the person's own sign-ins from a device their account has not used (NewDeviceSignIn) --}}
<div class="vy-sx-block" id="sx-alerts">
    <div class="vy-sx-row">
        <div class="vy-sx-row-main">
            <span class="vy-sx-row-title">Login alerts
                @if ($alerts['always_on'])
                    <span class="vy-sx-chip">Always on</span>
                @endif
            </span>
            <span class="vy-sx-row-meta">
                A sign-in from a device your account hasn’t used shows in your bell{{ $alerts['email'] ? ' and by email' : '' }}.
                @unless ($alerts['email']) Email isn’t being sent from Vyrobel yet, so for now it’s the bell only. @endunless
            </span>
        </div>
        @unless ($alerts['always_on'])
            <form method="POST" action="{{ route($routes['alerts']) }}">
                @csrf
                <input type="hidden" name="enabled" value="{{ $alerts['on'] ? '0' : '1' }}">
                <button type="submit" class="vy-sx-switch" role="switch" aria-checked="{{ $alerts['on'] ? 'true' : 'false' }}" aria-label="Login alerts"></button>
            </form>
        @endunless
    </div>
</div>

{{-- SESSION TIMEOUT — sign out after this long without using Vyrobel (SessionTimeout); background refreshes don't count --}}
<div class="vy-sx-block" id="sx-timeout">
    <div class="vy-sx-row is-stack">
        <div class="vy-sx-row-main">
            <span class="vy-sx-row-title">Session timeout</span>
            <span class="vy-sx-row-meta">Sign me out after this long without using Vyrobel. A page refreshing itself doesn’t count as you.</span>
        </div>
        <form method="POST" action="{{ route($routes['timeout']) }}" class="vy-sx-timeout">
            @csrf
            <label class="vy-sx-sr" for="vy-sx-timeout">Session timeout</label>
            <select id="vy-sx-timeout" name="minutes" class="vy-sx-select" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                <option value="" @selected($timeout['minutes'] === null)>{{ \App\Support\Security\SessionTimeout::label(null) }}</option>
                @foreach (\App\Support\Security\SessionTimeout::OPTIONS as $m)
                    <option value="{{ $m }}" @selected($timeout['minutes'] === $m)>{{ \App\Support\Security\SessionTimeout::label($m) }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm">Save</button></noscript>
        </form>
    </div>
</div>
