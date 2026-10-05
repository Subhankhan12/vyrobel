{{-- Operator SECURITY (Auth) — exact-match to Operator_Security.html on REAL data (#104b): change-password + 2FA/TOTP +
     passkeys + sign-in accounts, active sessions [ActiveSessions] + trusted devices [TrustedDeviceService] (the SAME the owner
     has), a REAL IP allowlist (enforced by EnforceIpAllowlist), the honest "No apps connected" (no staff OAuth), and a
     self-scoped data export.

     ⚠️⚠️ 2026-10-05 — REBUILT ON HIS REPORT ("this page design is not good. Fix it."). 🖥 Before: a 560 px column of cards
     beside full-width ones with the right half of the page empty, "Current password" labelled twice, four dialogs on an
     undefined `vyp-scrim` class with inline positioning, 101 inline styles and flash colours under AA. Now: one header (the
     state, derived from two-factor, and four real counts), then two named groups of equal cards — Signing in, and Devices
     and access — and your data. Every dialog is the shared solid card. Every password form carries `_form`, so an error
     shows in the form it came from, once (the key `current_password` is shared by five forms here), and its dialog reopens.

     RAW-BLADE RULE: every `@` follows a space, newline, tag, quote or bracket. --}}
@php
    $vyState = match ($twoFactor['state']) {
        'enabled' => ['label' => 'Protected', 'tone' => 'ok'],
        'pending' => ['label' => 'Finish two-factor setup', 'tone' => 'warn'],
        default => ['label' => 'Two-factor off', 'tone' => 'warn'],
    };
@endphp
<div class="vy-sec" x-data="{ ipOpen: @js($errored === 'ip'), ipRemove: null, exportOpen: false, signOutOpen: @js($errored === 'signout'), revokeId: @js($erroredDevice) }">
    <x-operator.shell active="security" title="Security" :viewer="$viewer">

        {{-- ⚠️ F-1, BOTH HALVES. `password_status` carries TWO messages to this page: the passwordless gate's interruption
             ("set a password and you'll be brought back") AND the confirmation that a password change SUCCEEDED. --}}
        @if (session('password_status'))
            <div class="vy-flash is-ok vy-fade" role="status">{{ session('password_status') }}</div>
        @endif
        @if (session('security_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">{{ session('security_notice') }}</div>
        @endif
        @if (session('security_error'))
            <div class="vy-flash is-err vy-fade" role="alert">{{ session('security_error') }}</div>
        @endif
        {{-- an error that belongs to no form here (each form shows its own, in place) --}}
        @if ($errors->any() && $errored === null)
            <div class="vy-flash is-err vy-fade" role="alert">{{ $errors->first() }}</div>
        @endif

        {{-- ── THE HEADER — the state, DERIVED from two-factor (never a fixed "Protected"), and four real counts ───────── --}}
        {{-- ⚠️ The badge used to be a hardcoded green "Protected" shown to an account with two-factor OFF — on an auth page
             that is a safety claim, not decoration. The state is carried by a WORD as well as colour (WCAG 1.4.1). --}}
        <section class="vy-card vy-sec-hero vy-fade" aria-labelledby="vy-sec-title">
            <div class="vy-sec-hero-id">
                <span class="vy-sec-shield is-{{ $vyState['tone'] }}" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5c0 4.6 3.2 8.4 7.5 10 4.3-1.6 7.5-5.4 7.5-10V6z"/>@if ($vyState['tone'] === 'ok')<path d="M9 12l2 2 4-4.5"/>@else<path d="M12 8.5v4M12 15.5h.01"/>@endif</svg>
                </span>
                <div class="vy-sec-hero-text">
                    <span class="vy-sec-state is-{{ $vyState['tone'] }}"><span class="vy-sec-state-dot" aria-hidden="true"></span>{{ $vyState['label'] }}</span>
                    <h2 class="vy-sec-title" id="vy-sec-title">Account security</h2>
                    <p class="vy-sec-sub">How your account is protected, and where it is signed in.</p>
                </div>
            </div>
            <div class="vy-sec-tiles">
                <a class="vy-sec-tile" href="#sec-sessions">
                    <span class="vy-sec-tile-ico" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg></span>
                    <span class="vy-sec-tile-n">{{ $counts['sessions'] }}</span><span class="vy-sec-tile-l">Active sessions</span>
                </a>
                <a class="vy-sec-tile" href="#sec-apps">
                    <span class="vy-sec-tile-ico" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><path d="M16.5 13v7M13 16.5h7"/></svg></span>
                    <span class="vy-sec-tile-n">{{ $counts['apps'] }}</span><span class="vy-sec-tile-l">Connected apps</span>
                </a>
                <a class="vy-sec-tile" href="#sec-trusted">
                    <span class="vy-sec-tile-ico" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M10 6.5h4M9.5 13.5l2 2 3-3.5"/></svg></span>
                    <span class="vy-sec-tile-n">{{ $counts['trusted'] }}</span><span class="vy-sec-tile-l">Trusted devices</span>
                </a>
                <a class="vy-sec-tile" href="#sec-ip">
                    <span class="vy-sec-tile-ico" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg></span>
                    <span class="vy-sec-tile-n">{{ $counts['ip'] }}</span><span class="vy-sec-tile-l">IP rules</span>
                </a>
            </div>
        </section>

        {{-- ══ SIGNING IN ═══════════════════════════════════════════════════════════════════════════════════════ --}}
        <section class="vy-sec-group" aria-labelledby="vy-sec-signin-t">
            <h2 class="vy-sec-group-title" id="vy-sec-signin-t">Signing in</h2>
            <div class="vy-sec-grid vy-sec-grid--signin">

                {{-- ── PASSWORD ─────────────────────────────────────────────────────────────────────────────────── --}}
                {{-- ⚠️⚠️ THE SET-A-PASSWORD PATH IS THIS SAME CARD. An account with no password has nothing to confirm with
                     (`Hash::check(x, null)` is false), so the current-password field is omitted only then; the controller
                     enforces the same split. 🖥 Its label was printed twice (once outside this check). --}}
                <section class="vy-card vy-sec-card vy-fade" id="sec-password" aria-labelledby="vy-sec-pw-t">
                    <h3 class="vy-sec-card-title" id="vy-sec-pw-t">@if ($hasPassword) Change password @else Set a password @endif</h3>
                    <p class="vy-sec-card-sub">{{ $hasPassword ? 'Use a long passphrase you don’t use anywhere else. You stay signed in here.' : 'Your account has no password yet. Set one to sign in with your email address.' }}</p>
                    @if ($errored === 'password' && $errors->any())
                        <div class="vy-gl-errors" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    <form method="POST" action="{{ route('app.security.password') }}" class="vy-sec-form">
                        @csrf
                        <input type="hidden" name="_form" value="password">
                        {{-- a hidden USERNAME so a password manager files the new password under the right account — no
                             `name`, so it is never sent --}}
                        <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                        @if ($hasPassword)
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-sec-cur">Current password</label>
                                <input type="password" name="current_password" id="vy-sec-cur" class="vy-input" autocomplete="current-password" required>
                            </div>
                        @endif
                        <div class="vy-gl-fields">
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-sec-new">New password</label>
                                <input type="password" name="password" id="vy-sec-new" class="vy-input" autocomplete="new-password" required>
                            </div>
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-sec-conf">Confirm new password</label>
                                <input type="password" name="password_confirmation" id="vy-sec-conf" class="vy-input" autocomplete="new-password" required>
                            </div>
                        </div>
                        {{-- ⚠️ DO13-4: the rule it enforces, stated — a refusal the person cannot comply with teaches nothing --}}
                        <p class="vy-gl-note vy-sec-hint">{{ \App\Support\Auth\PasswordPolicy::hint() }}</p>
                        <button type="submit" class="vy-btn vy-btn--accent">@if ($hasPassword) Update password @else Set password @endif</button>
                    </form>
                </section>

                {{-- ── TWO-FACTOR — the shared panel (owner and operator), laid into this grid --}}
                @include('livewire.partials.two-factor-panel', ['tfCard' => ''])

                {{-- ── PASSKEYS — the SAME <x-auth.passkeys> the owner page renders, on the SAME PasskeyController/Service.
                     Only the route names differ; the ceremony, the self-only remove-with-re-auth and the empty state are one
                     implementation (§N.8f). --}}
                <x-auth.passkeys
                    :passkeys="$passkeys"
                    options-route="app.security.passkeys.options"
                    store-route="app.security.passkeys.store"
                    remove-route="app.security.passkeys.remove"
                    card=""
                    h2="font-family:var(--vy-display);font-size:15px;font-weight:600;margin:0 0 4px;color:var(--vy-ink);"
                    sub="font-size:12.5px;color:var(--vy-on-grad);margin:0;line-height:1.5;"
                    ghost="background:transparent;border:1px solid var(--vy-glass-border);"
                    err="font-size:12px;color:#8F3110;display:block;margin-top:6px;" />

                {{-- ── SIGN-IN ACCOUNTS — this account's own linked providers. ⚠️ The unlink refusal (the lockout floor) is
                     enforced in the route; the disabled button only explains it. A provider not switched on here offers no
                     Connect at all (its routes 404 then). --}}
                <section class="vy-card vy-sec-card vy-fade" id="sec-connections" aria-labelledby="vy-sec-conn-t">
                    <h3 class="vy-sec-card-title" id="vy-sec-conn-t">Sign-in accounts</h3>
                    <p class="vy-sec-card-sub">Sign in to Vyrobel with an account you already have.</p>
                    @if (session('connections_status'))
                        <p class="vy-sec-inline is-ok" role="status">{{ session('connections_status') }}</p>
                    @endif
                    @if (session('connections_error'))
                        <p class="vy-sec-inline is-err" role="alert">{{ session('connections_error') }}</p>
                    @endif
                    <ul class="vy-sec-list">
                        @forelse ($connections as $connection)
                            <li class="vy-sec-row">
                                <div class="vy-sec-row-text">
                                    <span class="vy-sec-row-title">{{ $connection['label'] }}</span>
                                    <span class="vy-sec-row-meta">{{ $connection['email'] !== '' ? $connection['email'].' · ' : '' }}last used {{ $connection['last_used'] }}</span>
                                </div>
                                @if ($connection['can_unlink'])
                                    <form method="POST" action="{{ route('app.security.connections.unlink', $connection['id']) }}">
                                        @csrf
                                        <button type="submit" class="vy-btn vy-btn--ghost vy-sec-small">Disconnect</button>
                                    </form>
                                @else
                                    <div class="vy-sec-why">
                                        <button type="button" class="vy-btn vy-btn--ghost vy-sec-small" disabled aria-disabled="true" aria-describedby="vy-unlink-why-{{ $connection['id'] }}">Disconnect</button>
                                        <p id="vy-unlink-why-{{ $connection['id'] }}">{{ \App\Support\Auth\SignInCredentials::unlinkRefusalReason() }}</p>
                                    </div>
                                @endif
                            </li>
                        @empty
                            <li class="vy-sec-empty">
                                <span class="vy-sec-row-title">None connected</span>
                                <span class="vy-sec-row-meta">{{ $connectable === [] ? 'Signing in with Google or X isn’t switched on here yet.' : 'Connect one to sign in without typing your password.' }}</span>
                            </li>
                        @endforelse
                    </ul>
                    @if ($connectable !== [])
                        <div class="vy-sec-actions">
                            @foreach ($connectable as $provider)
                                <form method="POST" action="{{ route($provider['route']) }}">
                                    @csrf
                                    <button type="submit" class="vy-btn vy-btn--ghost vy-sec-small">Connect {{ $provider['label'] }}</button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </section>

        {{-- ══ DEVICES AND ACCESS ═══════════════════════════════════════════════════════════════════════════════ --}}
        <section class="vy-sec-group" aria-labelledby="vy-sec-access-t">
            <h2 class="vy-sec-group-title" id="vy-sec-access-t">Devices and access</h2>
            <div class="vy-sec-grid">

                {{-- ── ACTIVE SESSIONS — each named by a one-way key, never its session id (a session id IS a sign-in) ─────── --}}
                <section class="vy-card vy-sec-card vy-fade" id="sec-sessions" aria-labelledby="vy-sec-sess-t">
                    <h3 class="vy-sec-card-title" id="vy-sec-sess-t">Active sessions &amp; devices</h3>
                    <p class="vy-sec-card-sub">Where your account is signed in now.</p>
                    <ul class="vy-sec-list">
                        @forelse ($sessions as $sess)
                            <li class="vy-sec-row">
                                <div class="vy-sec-row-text">
                                    <span class="vy-sec-row-title">{{ $sess['device'] }}@if ($sess['current']) <span class="vy-tag tone-confirmed vy-sec-this">This device</span>@endif</span>
                                    <span class="vy-sec-row-meta">{{ $sess['ip'] }} · {{ $sess['current'] ? 'active now' : 'last active '.$sess['last_active']->diffForHumans() }}</span>
                                </div>
                                @unless ($sess['current'])
                                    <form method="POST" action="{{ route('app.security.sessions.sign-out') }}">
                                        @csrf
                                        <input type="hidden" name="session" value="{{ $sess['key'] }}">
                                        <button type="submit" class="vy-btn vy-btn--ghost vy-sec-small">Sign out</button>
                                    </form>
                                @endunless
                            </li>
                        @empty
                            <li class="vy-sec-empty"><span class="vy-sec-row-title">No sessions recorded</span></li>
                        @endforelse
                    </ul>
                    @if (count($sessions) > 1)
                        <div class="vy-sec-actions">
                            <button type="button" class="vy-btn vy-btn--ghost vy-sec-small" @click="signOutOpen = true">Sign out of all other devices</button>
                        </div>
                    @endif
                </section>

                {{-- ── TRUSTED DEVICES — skip the two-factor code for 30 days; revoke re-asks for the password ─────────────── --}}
                <section class="vy-card vy-sec-card vy-fade" id="sec-trusted" aria-labelledby="vy-sec-trust-t">
                    <h3 class="vy-sec-card-title" id="vy-sec-trust-t">Trusted devices</h3>
                    <p class="vy-sec-card-sub">Devices that skip the two-factor code for 30 days. Revoke any you don’t recognise.</p>
                    <ul class="vy-sec-list">
                        @forelse ($trusted as $d)
                            <li class="vy-sec-row">
                                <div class="vy-sec-row-text">
                                    <span class="vy-sec-row-title">{{ $d['label'] }}@if ($d['current']) <span class="vy-tag tone-confirmed vy-sec-this">This device</span>@endif</span>
                                    @if ($d['last_used'])<span class="vy-sec-row-meta">Last used {{ $d['last_used'] }}</span>@endif
                                </div>
                                <button type="button" class="vy-btn vy-btn--ghost vy-sec-small" @click="revokeId = '{{ $d['id'] }}'">Revoke</button>
                            </li>
                        @empty
                            {{-- ⚠️ 2026-10-05 — it said "Every sign-in will ask for your 2FA code" while two-factor was OFF --}}
                            <li class="vy-sec-empty">
                                <span class="vy-sec-row-title">No trusted devices</span>
                                <span class="vy-sec-row-meta">{{ $twoFactorOn ? 'Every sign-in asks for your code.' : 'Two-factor is off, so no device skips it.' }}</span>
                            </li>
                        @endforelse
                    </ul>
                </section>

                {{-- ── IP ALLOWLIST — org-wide, genuinely enforced; changing it needs `settings.manage` ─────────────────────── --}}
                <section class="vy-card vy-sec-card vy-fade" id="sec-ip" aria-labelledby="vy-sec-ip-t">
                    <div class="vy-sec-card-top">
                        <div>
                            <h3 class="vy-sec-card-title" id="vy-sec-ip-t">IP allowlist</h3>
                            <p class="vy-sec-card-sub">When set, only these addresses can reach the back office — for everyone in this workspace.</p>
                        </div>
                        @if ($canManageIp)
                            <button type="button" class="vy-btn vy-btn--ghost vy-sec-small" @click="ipOpen = true">Add IP</button>
                        @endif
                    </div>
                    <ul class="vy-sec-list">
                        @forelse ($ipRules as $r)
                            <li class="vy-sec-row">
                                <div class="vy-sec-row-text">
                                    <span class="vy-sec-row-title vy-sec-mono">{{ $r['ip'] }}</span>
                                    @if ($r['label'] !== '')<span class="vy-sec-row-meta">{{ $r['label'] }}</span>@endif
                                </div>
                                @if ($canManageIp)
                                    <button type="button" class="vy-btn vy-btn--ghost vy-sec-small" @click="ipRemove = @js(['id' => $r['id'], 'ip' => $r['ip']])">Remove</button>
                                @endif
                            </li>
                        @empty
                            <li class="vy-sec-empty">
                                <span class="vy-sec-row-title">No IP restrictions</span>
                                <span class="vy-sec-row-meta">Anyone with a valid sign-in can reach the back office from any network.</span>
                            </li>
                        @endforelse
                    </ul>
                    @unless ($canManageIp)
                        <p class="vy-gl-note">Changing the allowlist needs the “manage settings” permission.</p>
                    @endunless
                </section>

                {{-- ── CONNECTED APPS — the honest empty state: there is no third-party access for staff accounts ────────── --}}
                <section class="vy-card vy-sec-card vy-fade" id="sec-apps" aria-labelledby="vy-sec-apps-t">
                    <h3 class="vy-sec-card-title" id="vy-sec-apps-t">Connected apps</h3>
                    <p class="vy-sec-card-sub">Third-party apps you’ve allowed into your account.</p>
                    <ul class="vy-sec-list">
                        <li class="vy-sec-empty">
                            <span class="vy-sec-row-title">No apps connected</span>
                            <span class="vy-sec-row-meta">Third-party app access isn’t available for staff accounts yet.</span>
                        </li>
                    </ul>
                </section>
            </div>
        </section>

        {{-- ══ YOUR DATA — a self-scoped export: your account and the actions you took (never guests or bookings) ══════ --}}
        <section class="vy-card vy-sec-data vy-fade" aria-labelledby="vy-sec-data-t">
            <div>
                <h2 class="vy-sec-card-title" id="vy-sec-data-t">Export your data</h2>
                <p class="vy-sec-card-sub">Your account details and the actions you’ve taken, downloaded to this device.</p>
            </div>
            <button type="button" class="vy-btn vy-btn--ghost" @click="exportOpen = true">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>
                Export…
            </button>
        </section>

        {{-- ══ DIALOGS — the shared solid card, centred by class (an inline display beside x-show is erased on the first
             hide). Each traps focus with the expression that shows it, and Escape closes it. ═══════════════════════ --}}
        @if ($canManageIp)
            {{-- Add IP --}}
            <div class="vy-modal-backdrop" x-cloak x-show="ipOpen" x-transition.opacity @keydown.escape.window="ipOpen = false" @click.self="ipOpen = false">
                <div class="vy-modal vy-modal-card vy-sec-dlg" x-show="ipOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-operator-security-ipopen-4" tabindex="-1" x-trap.noscroll="ipOpen">
                    <form method="POST" action="{{ route('app.security.ip-rules.add') }}">
                        @csrf
                        <input type="hidden" name="_form" value="ip">
                        <div class="vy-modal-head">
                            <span class="vy-modal-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg></span>
                            <div class="vy-gl-head-text">
                                <h3 class="vy-modal-title" id="vy-dlg-operator-security-ipopen-4">Add to the allowlist</h3>
                                <p class="vy-modal-sub">One address, or a block of them. Your address now is <strong>{{ $currentIp }}</strong>.</p>
                            </div>
                            <button type="button" class="vy-modal-x" @click="ipOpen = false" aria-label="Close"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                        </div>
                        <div class="vy-modal-body">
                            @if ($errored === 'ip' && $errors->any())
                                <div class="vy-gl-errors" role="alert">{{ $errors->first() }}</div>
                            @endif
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-lbl-operator-security-ip-address">Address</label>
                                <input id="vy-lbl-operator-security-ip-address" type="text" name="ip_address" class="vy-input vy-sec-mono" placeholder="203.0.113.4 or 203.0.113.0/24" value="{{ old('ip_address', $currentIp) }}" required autocomplete="off" spellcheck="false">
                            </div>
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-lbl-operator-security-label">Label <span class="vy-fl-note">· optional</span></label>
                                <input id="vy-lbl-operator-security-label" type="text" name="label" class="vy-input" maxlength="80" placeholder="Office" value="{{ old('label') }}" autocomplete="off">
                            </div>
                            <p class="vy-gl-note">When this is the first rule, your own address is added too, so you are never locked out.</p>
                        </div>
                        <div class="vy-modal-foot">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="ipOpen = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent">Add to allowlist</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Remove IP — 📖 it was the browser's confirm() --}}
            <div class="vy-modal-backdrop" x-cloak x-show="ipRemove !== null" x-transition.opacity @keydown.escape.window="ipRemove = null" @click.self="ipRemove = null">
                <div class="vy-modal vy-modal-card vy-sec-dlg" x-show="ipRemove !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-sec-ipremove-t" tabindex="-1" x-trap.noscroll="ipRemove !== null">
                    <form method="POST" :action="ipRemove ? @js(route('app.security.ip-rules.remove', ['rule' => '__ID__'])).replace('__ID__', ipRemove.id) : '#'">
                        @csrf
                        @method('DELETE')
                        <div class="vy-modal-head">
                            <span class="vy-modal-ico is-danger"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg></span>
                            <div class="vy-gl-head-text">
                                <h3 class="vy-modal-title" id="vy-dlg-sec-ipremove-t">Remove <span x-text="ipRemove ? ipRemove.ip : ''"></span>?</h3>
                                <p class="vy-modal-sub">Devices at this address lose access unless another rule covers them.</p>
                            </div>
                            <button type="button" class="vy-modal-x" @click="ipRemove = null" aria-label="Close"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                        </div>
                        <div class="vy-modal-body">
                            <p class="vy-gl-note">If it is the only rule that lets your own address in, it is kept — removing it would lock you out. Removing the last rule turns the allowlist off.</p>
                        </div>
                        <div class="vy-modal-foot">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="ipRemove = null">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--danger">Remove</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Export your data — choice tiles, not native dropdowns whose look each browser decides --}}
        <div class="vy-modal-backdrop" x-cloak x-show="exportOpen" x-transition.opacity @keydown.escape.window="exportOpen = false" @click.self="exportOpen = false">
            <div class="vy-modal vy-modal-card vy-sec-dlg" x-show="exportOpen" x-transition x-data="{ range: 'all', format: 'json' }" role="dialog" aria-modal="true" aria-labelledby="vy-dlg-operator-security-exportopen-3" tabindex="-1" x-trap.noscroll="exportOpen">
                <form method="GET" action="{{ route('app.security.export') }}">
                    <div class="vy-modal-head">
                        <span class="vy-modal-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg></span>
                        <div class="vy-gl-head-text">
                            <h3 class="vy-modal-title" id="vy-dlg-operator-security-exportopen-3">Export your data</h3>
                            <p class="vy-modal-sub">Your account details and the actions you’ve taken. Downloads to this device.</p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="exportOpen = false" aria-label="Close"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                    <div class="vy-modal-body">
                        <div class="vy-field">
                            <x-operator.choices name="format" label="Format" :options="[['value' => 'json', 'label' => 'JSON'], ['value' => 'csv', 'label' => 'CSV']]" model="format" variant="segmented" />
                        </div>
                        <div class="vy-field">
                            <x-operator.choices name="range" label="Date range" :options="[['value' => 'all', 'label' => 'All time'], ['value' => 'year', 'label' => 'This year'], ['value' => 'custom', 'label' => 'Custom']]" model="range" variant="segmented" />
                        </div>
                        <div class="vy-gl-fields" x-show="range === 'custom'" x-cloak>
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-sec-from">From</label>
                                <input type="date" name="from" id="vy-sec-from" class="vy-input">
                            </div>
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-sec-to">To</label>
                                <input type="date" name="to" id="vy-sec-to" class="vy-input">
                            </div>
                        </div>
                    </div>
                    <div class="vy-modal-foot">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="exportOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Download</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sign out of all other devices — re-asks for the password; comes back here (`return`) --}}
        <div class="vy-modal-backdrop" x-cloak x-show="signOutOpen" x-transition.opacity @keydown.escape.window="signOutOpen = false" @click.self="signOutOpen = false">
            <div class="vy-modal vy-modal-card vy-sec-dlg" x-show="signOutOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-operator-security-signoutopen-2" tabindex="-1" x-trap.noscroll="signOutOpen">
                <form method="POST" action="{{ route('app.profile.sign-out-others') }}">
                    @csrf
                    <input type="hidden" name="return" value="security">
                    <input type="hidden" name="_form" value="signout">
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-modal-head">
                        <span class="vy-modal-ico is-danger"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H4"/></svg></span>
                        <div class="vy-gl-head-text">
                            <h3 class="vy-modal-title" id="vy-dlg-operator-security-signoutopen-2">Sign out of all other devices</h3>
                            <p class="vy-modal-sub">Every session but this one ends now. Confirm with your password.</p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="signOutOpen = false" aria-label="Close"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                    <div class="vy-modal-body">
                        @if ($errored === 'signout' && $errors->any())
                            <div class="vy-gl-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-field vy-ul-last">
                            <label class="vy-field-label" for="vy-sec-so-pw">Your password</label>
                            <input type="password" id="vy-sec-so-pw" name="current_password" class="vy-input" autocomplete="current-password" required>
                        </div>
                    </div>
                    <div class="vy-modal-foot">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="signOutOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--danger">Sign out others</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Revoke a trusted device — re-asks for the password --}}
        <div class="vy-modal-backdrop" x-cloak x-show="revokeId !== null" x-transition.opacity @keydown.escape.window="revokeId = null" @click.self="revokeId = null">
            <div class="vy-modal vy-modal-card vy-sec-dlg" x-show="revokeId !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-revoke-t" tabindex="-1" x-trap.noscroll="revokeId !== null">
                <form method="POST" action="{{ route('app.security.trusted.revoke') }}">
                    @csrf
                    <input type="hidden" name="_form" value="revoke">
                    <input type="hidden" name="device_id" :value="revokeId">
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-modal-head">
                        <span class="vy-modal-ico is-danger"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M10 6.5h4M9.5 12.5l5 5M14.5 12.5l-5 5"/></svg></span>
                        <div class="vy-gl-head-text">
                            <h3 class="vy-modal-title" id="vy-dlg-revoke-t">Revoke trusted device</h3>
                            <p class="vy-modal-sub">It will be asked for a two-factor code next time. Confirm with your password.</p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="revokeId = null" aria-label="Close"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                    <div class="vy-modal-body">
                        @if ($errored === 'revoke' && $errors->any())
                            <div class="vy-gl-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-field vy-ul-last">
                            <label class="vy-field-label" for="vy-sec-rv-pw">Your password</label>
                            <input type="password" id="vy-sec-rv-pw" name="current_password" class="vy-input" autocomplete="current-password" required>
                        </div>
                    </div>
                    <div class="vy-modal-foot">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="revokeId = null">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--danger">Revoke</button>
                    </div>
                </form>
            </div>
        </div>

    </x-operator.shell>
</div>
