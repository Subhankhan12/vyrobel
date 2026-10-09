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
            {{-- ⚠️ Initials, not a photo. The design shows a camera badge over the avatar; `users.avatar_path`
                 exists and the OWNER realm has a real upload route, but the OPERATOR realm has none — so a
                 camera badge here would be a control that does nothing. Logged, not faked. --}}
            <span class="vyp-av" aria-hidden="true">{{ $viewer['initials'] }}</span>
            <div class="vyp-ident">
                <div class="vyp-nameline">
                    <span class="vyp-name">{{ $user->name }}</span>
                    @if ($roles !== '')<span class="vyp-role">{{ $roles }}</span>@endif
                    {{-- Status carries its WORD, never colour alone (1.4.1). --}}
                    <span class="vyp-status"><span class="vyp-dot" aria-hidden="true"></span>{{ ucfirst((string) $user->status) }}</span>
                </div>
                @if ($personal['preferred_name'])<div class="vyp-called">Goes by “{{ $personal['preferred_name'] }}”</div>@endif
                <div class="vyp-meta">
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
                        {{-- The new select (2026-10-09, his request — this page first, the rest of the app after his approval). --}}
                        <div class="vy-field" style="margin: 0;"><label class="vy-field-label" id="locale-label" for="locale">Language</label>
                            <x-select name="locale" id="locale" labelledby="locale-label" :options="$languageOptions" :value="old('locale', $personal['locale'])" />
                        </div>
                        <div class="vy-field" style="margin: 0;"><label class="vy-field-label" id="tz-label" for="tz">Timezone</label>
                            <x-select name="timezone" id="tz" labelledby="tz-label" :options="$timezoneOptions" :value="old('timezone', $personal['timezone'])"
                                      search-placeholder="Search a city, region or offset" empty-text="No timezone matches that — try a city or an offset like +4."
                                      :device-timezone="true" :min-width="340" />
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                        <a class="vy-btn vy-btn--ghost" href="{{ route('app.profile') }}">Cancel</a>
                        <button type="submit" class="vy-btn vy-btn--accent">Save changes</button>
                    </div>
                </form>

                {{-- ── Preferences (real, stored + applied) ───────────────────────────────────────── --}}
                <form method="POST" action="{{ route('app.profile.preferences') }}" class="vy-card">
                    @csrf
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">Preferences</h2>
                    <label class="vyp-pref"><span><strong>Compact density</strong><br><span class="vy-muted">Tighter spacing to fit more on screen.</span></span><input type="checkbox" class="vyp-switch" name="compact_density" value="1" @checked($prefs['compact_density'])></label>
                    <label class="vyp-pref"><span><strong>Show onboarding tips</strong><br><span class="vy-muted">Contextual hints on new features.</span></span><input type="checkbox" class="vyp-switch" name="onboarding_tips" value="1" @checked($prefs['onboarding_tips'])></label>
                    <label class="vyp-pref"><span><strong>Reduce motion</strong><br><span class="vy-muted">Turn off the ambient background animation and transitions.</span></span><input type="checkbox" class="vyp-switch" name="reduce_motion" value="1" @checked($prefs['reduce_motion'])></label>
                    <div class="vyp-pref" style="border-bottom: none;">
                        <span><strong>Date format</strong><br><span class="vy-muted">How dates appear across the workspace.</span></span>
                        <span class="vyp-seg">
                            @foreach (['DD/MM/YYYY', 'MM/DD/YYYY'] as $fmt)
                                <label class="vyp-seg-opt">
                                    <input type="radio" name="date_format" value="{{ $fmt }}" @checked($prefs['date_format'] === $fmt)>
                                    <span>{{ $fmt }}</span>
                                </label>
                            @endforeach
                        </span>
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
        <div x-show="signOutOpen" x-cloak class="vyp-scrim" @click.self="signOutOpen = false" x-on:keydown.escape.window="signOutOpen = false">
            <div class="vyp-modal" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="signOutOpen" aria-labelledby="vy-dlg-operator-profile-signoutopen-1">
                <h2 class="vy-card-title" id="vy-dlg-operator-profile-signoutopen-1">Sign out of all other devices</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 4px 0 12px;">This ends every session except this one. Confirm your password.</p>
                <form method="POST" action="{{ route('app.profile.sign-out-others') }}">
                    @csrf
                    <label class="vy-sr-only" for="vy-lbl-operator-profile-current-password">Current password</label>
                    <input id="vy-lbl-operator-profile-current-password" type="password" name="current_password" class="vy-input" placeholder="Current password" autocomplete="current-password" required>
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
        .vyp-av { width: 68px; height: 68px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 23px; font-weight: 600; display: grid; place-items: center; flex: none; }
        .vyp-ident { min-width: 0; }
        .vyp-nameline { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
        .vyp-name { font-size: 20px; font-weight: 700; color: var(--vy-ink); font-family: var(--vy-display, inherit); }
        .vyp-called { font-size: 12.5px; color: var(--vy-muted); margin-top: 2px; }
        .vyp-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px; font-size: 12px; }
        /* ⚠️ --vy-accent-text: as a chip at 11px the plain accent measured 4.23. */
        .vyp-role { color: var(--vy-accent-text); font-weight: 600; font-size: 11px; background: rgba(232,112,58,.12); border-radius: 999px; padding: 3px 10px; }
        .vyp-status { display: inline-flex; align-items: center; gap: 5px; color: var(--vy-muted); font-size: 12px; }
        .vyp-dot { width: 7px; height: 7px; border-radius: 50%; background: #1F6E4B; flex: none; }

        /* The design's switches. ⚠️ real, FOCUSABLE checkboxes — appearance reset, never display:none (P30). */
        .vyp-switch { appearance: none; -webkit-appearance: none; margin: 0; flex: none; width: 42px; height: 24px; border-radius: 999px; background: var(--vy-hairline); position: relative; cursor: pointer; transition: background .15s; }
        .vyp-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2); transition: transform .15s; }
        .vyp-switch:checked { background: var(--vy-accent); }
        .vyp-switch:checked::after { transform: translateX(18px); }
        .vyp-switch:focus-visible { outline: 2px solid var(--vy-accent-text); outline-offset: 2px; }

        /* The design's date-format segment — real radios, so it stays keyboard-operable and named. */
        .vyp-seg { display: inline-flex; background: rgba(255,255,255,.6); border: 1px solid var(--vy-glass-border); border-radius: 999px; padding: 3px; flex: none; }
        .vyp-seg-opt input { position: absolute; opacity: 0; width: 0; height: 0; }
        .vyp-seg-opt span { display: block; padding: 6px 13px; border-radius: 999px; font-size: 12px; color: var(--vy-muted); cursor: pointer; white-space: nowrap; }
        .vyp-seg-opt input:checked + span { background: #fff; color: var(--vy-ink); font-weight: 600; box-shadow: var(--vy-shadow-sm); }
        .vyp-seg-opt input:focus-visible + span { outline: 2px solid var(--vy-accent-text); outline-offset: 2px; }
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
