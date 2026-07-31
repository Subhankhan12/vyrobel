{{-- Operator PROFILE (#96 exact-match) — the operator's own account: personal details + UI preferences (real,
     self-only POST, audited), a READ-ONLY display of the REAL role capabilities (spatie, never fabricated), and
     Sign-in & security reusing the #66 features (password/2FA on the Security page) + REAL sessions. RAW-BLADE
     RULE: every `@` follows a space, newline or tag. --}}
@php use App\Support\DateFormat; @endphp
<div x-data="{ signOutOpen: false }">
    <x-operator.shell active="profile" title="Your profile" :subtitle="'Your account'" :viewer="$viewer">

        @if (session('profile_notice')) <div class="vy-flash is-ok vy-fade">{{ session('profile_notice') }}</div> @endif
        @if ($errors->any()) <div class="vy-flash is-err vy-fade">{{ $errors->first() }}</div> @endif

        {{-- Identity header (read-only) --}}
        <div class="vy-card vy-fade vyp-head" style="margin-bottom: 16px;">
            <span class="vyp-av">{{ $viewer['initials'] }}</span>
            <div>
                <div class="vyp-name">{{ $user->name }}@if ($personal['preferred_name']) <span class="vy-muted" style="font-size: 13px; font-weight: 400;">· “{{ $personal['preferred_name'] }}”</span>@endif</div>
                <div class="vyp-meta">
                    @if ($roles !== '')<span class="vyp-role">{{ $roles }}</span>@endif
                    <span class="vyp-status">{{ ucfirst((string) $user->status) }}</span>
                    @if ($propertyLabel)<span class="vyp-since">{{ $propertyLabel }}</span>@endif
                    @if ($memberSince)<span class="vyp-since">Member since {{ $memberSince }}</span>@endif
                </div>
            </div>
        </div>

        <div class="vy-ins-grid vy-fade">
            <div class="vy-stack">
                {{-- ── Personal details (real, editable, audited) ─────────────────────────────────── --}}
                <form method="POST" action="{{ route('app.profile.update') }}" class="vy-card">
                    @csrf
                    <h2 class="vy-card-title" style="margin-bottom: 4px;">Personal details</h2>
                    <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">Only your own profile — you can’t change anyone else’s here.</p>
                    <div class="vyp-grid2">
                        <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="name">Full name</label><input id="name" type="text" name="name" class="vy-input" value="{{ old('name', $personal['name']) }}" required></div>
                        <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="pn">Preferred name</label><input id="pn" type="text" name="preferred_name" class="vy-input" value="{{ old('preferred_name', $personal['preferred_name']) }}"></div>
                        <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="email">Work email</label><input id="email" type="email" name="email" class="vy-input" value="{{ old('email', $personal['email']) }}" required></div>
                        <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="phone">Phone</label><input id="phone" type="text" name="phone" class="vy-input" value="{{ old('phone', $personal['phone']) }}"></div>
                        <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="locale">Language</label>
                            <select id="locale" name="locale" class="vy-input">
                                @foreach ($localeOptions as $code => $label)<option value="{{ $code }}" @selected($personal['locale'] === $code)>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="tz">Timezone</label>
                            <select id="tz" name="timezone" class="vy-input">
                                @foreach (['Europe/Lisbon', 'Europe/Madrid', 'Europe/London', 'UTC', 'America/New_York'] as $tz)<option value="{{ $tz }}" @selected($personal['timezone'] === $tz)>{{ $tz }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; margin-top: 16px;"><button type="submit" class="vy-btn vy-btn--accent">Save changes</button></div>
                </form>

                {{-- ── Preferences (real, stored + applied) ───────────────────────────────────────── --}}
                <form method="POST" action="{{ route('app.profile.preferences') }}" class="vy-card">
                    @csrf
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">Preferences</h2>
                    <label class="vyp-pref"><span><strong>Compact density</strong><br><span class="vy-muted">Tighter spacing to fit more on screen.</span></span><input type="checkbox" name="compact_density" value="1" @checked($prefs['compact_density'])></label>
                    <label class="vyp-pref"><span><strong>Show onboarding tips</strong><br><span class="vy-muted">Contextual hints on new features.</span></span><input type="checkbox" name="onboarding_tips" value="1" @checked($prefs['onboarding_tips'])></label>
                    <label class="vyp-pref"><span><strong>Interface motion</strong><br><span class="vy-muted">Ambient background animation and transitions.</span></span><input type="checkbox" name="reduce_motion" value="1" @checked($prefs['reduce_motion'])> <span class="vy-muted" style="font-size: 11px;">reduce</span></label>
                    <div class="vyp-pref" style="border-bottom: none;">
                        <span><strong>Date format</strong><br><span class="vy-muted">How dates appear across the workspace.</span></span>
                        <select name="date_format" class="vy-input" style="width: auto;">
                            @foreach (['DD/MM/YYYY', 'MM/DD/YYYY'] as $fmt)<option value="{{ $fmt }}" @selected($prefs['date_format'] === $fmt)>{{ $fmt }}</option>@endforeach
                        </select>
                    </div>
                    <div style="display: flex; justify-content: flex-end; margin-top: 14px;"><button type="submit" class="vy-btn vy-btn--ghost">Save preferences</button></div>
                </form>
            </div>

            <div class="vy-stack">
                {{-- ── Role & access — REAL caps, read-only ───────────────────────────────────────── --}}
                <div class="vy-card">
                    <div class="vy-detail-head" style="margin-bottom: 4px;"><h2 class="vy-card-title">Role &amp; access</h2>@if ($roles !== '')<span class="vy-tag tone-confirmed">{{ $roles }}</span>@endif</div>
                    <div class="vy-nr-sub" style="margin: 10px 0 6px;">Key permissions</div>
                    @foreach ($keyCaps as $cap)
                        <div class="vyp-cap {{ $cap['granted'] ? 'is-on' : 'is-off' }}">
                            <span>{{ $cap['granted'] ? '✓' : '—' }}</span> {{ $cap['label'] }}
                        </div>
                    @endforeach
                    <p style="font-size: 11.5px; color: var(--vy-faint); margin: 12px 0 0;">Access is managed by your workspace admin.</p>
                    <details style="margin-top: 8px;">
                        <summary style="font-size: 12.5px; color: var(--vy-accent); cursor: pointer;">View full permissions</summary>
                        <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 8px;">
                            @forelse ($allPermissions as $perm)<span class="vy-pill" style="font-size: 10.5px;">{{ $perm }}</span>@empty<span class="vy-muted" style="font-size: 12px;">Full access (workspace owner).</span>@endforelse
                        </div>
                    </details>
                </div>

                {{-- ── Sign-in & security — REUSE #66 (password/2FA) + REAL sessions ──────────────── --}}
                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 10px;">Sign-in &amp; security</h2>
                    <div class="vyp-sec"><div><strong>Password</strong><br><span class="vy-muted">Change it on the Security page.</span></div><a class="vy-btn vy-btn--ghost" href="{{ route('app.security') }}">Change</a></div>
                    <div class="vyp-sec"><div><strong>Two-factor auth</strong><br><span class="vy-muted">{{ $twoFactorEnabled ? 'On · Authenticator app' : 'Off — add an extra layer' }}</span></div><a class="vy-btn vy-btn--ghost" href="{{ route('app.security') }}">Manage</a></div>
                    <div class="vyp-sec"><div><strong>Last sign-in</strong><br><span class="vy-muted">{{ $lastLogin ? DateFormat::forUser($user, $lastLogin).' · '.\Carbon\CarbonImmutable::parse($lastLogin)->format('H:i') : 'This session' }}</span></div></div>

                    <div class="vy-nr-sub" style="margin: 12px 0 4px;">Active sessions</div>
                    @foreach ($sessions as $sess)
                        <div class="vyp-sec">
                            <div><strong>{{ $sess['device'] }}</strong>@if ($sess['current']) <span class="vy-tag tone-confirmed" style="font-size: 10px;">This device</span>@endif<br><span class="vy-muted">{{ $sess['ip'] }} · {{ $sess['last_active']->diffForHumans() }}</span></div>
                        </div>
                    @endforeach

                    <button type="button" class="vy-btn vy-btn--ghost" style="margin-top: 12px; width: 100%;" x-on:click="signOutOpen = true">Sign out of all other devices</button>
                </div>
            </div>
        </div>

        {{-- Sign-out-others confirm (current password — a real session revocation) --}}
        <div x-show="signOutOpen" x-cloak class="vyp-scrim" @click.self="signOutOpen = false">
            <div class="vyp-modal">
                <h2 class="vy-card-title">Sign out of all other devices</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 4px 0 12px;">This ends every session except this one. Confirm your password.</p>
                <form method="POST" action="{{ route('app.profile.sign-out-others') }}">
                    @csrf
                    <input type="password" name="current_password" class="vy-input" placeholder="Current password" autocomplete="current-password" required>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                        <button type="button" class="vy-btn vy-btn--ghost" x-on:click="signOutOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Sign out others</button>
                    </div>
                </form>
            </div>
        </div>

    </x-operator.shell>

    <style>
        [x-cloak] { display: none !important; }
        .vyp-head { display: flex; align-items: center; gap: 14px; }
        .vyp-av { width: 52px; height: 52px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 18px; font-weight: 600; display: grid; place-items: center; flex: none; }
        .vyp-name { font-size: 18px; font-weight: 700; color: var(--vy-ink); font-family: var(--vy-display, inherit); }
        .vyp-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; font-size: 12px; }
        .vyp-role { color: var(--vy-accent); font-weight: 600; }
        .vyp-status { color: var(--vy-muted); }
        .vyp-since { color: var(--vy-faint); }
        .vyp-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .vyp-pref { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; cursor: pointer; }
        .vyp-cap { font-size: 12.5px; padding: 5px 0; }
        .vyp-cap.is-on { color: var(--vy-ink); }
        .vyp-cap.is-off { color: var(--vy-faint); }
        .vyp-cap span { font-weight: 700; margin-right: 6px; }
        .vyp-cap.is-on span { color: #1F6E4B; }
        .vyp-sec { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; }
        .vyp-sec:last-of-type { border-bottom: none; }
        .vyp-scrim { position: fixed; inset: 0; z-index: 60; background: rgba(43,35,32,.42); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 20px; }
        .vyp-modal { width: 420px; max-width: 100%; background: var(--vy-surface, #FFFCF8); border-radius: 16px; padding: 22px; box-shadow: 0 24px 60px rgba(150,90,50,.24); }
        @media (max-width: 720px) { .vyp-grid2 { grid-template-columns: 1fr; } }
    </style>
</div>
