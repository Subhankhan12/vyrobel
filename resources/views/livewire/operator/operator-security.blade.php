{{-- Operator SECURITY — the back office's own account security, on REAL data: password, two-factor (TOTP) with its recovery
     codes, passkeys, sign-in accounts, active sessions [ActiveSessions], trusted devices [TrustedDeviceService], the REAL IP
     allowlist (enforced by EnforceIpAllowlist), the honest "No apps connected" (no staff OAuth), and a self-scoped export.

     ⚠️⚠️ 2026-10-24 — REBUILT ON HIS DESIGN ("this is what is missing inside our security page … color and most of important
     things"), and SHARED with the owner page: a score derived from real settings (App\Support\Security\SecurityScore — the owner
     page reads the same rule), the warm cards, three columns, compact rows whose forms open in dialogs, real sign-in problems
     from the sign-in log. The kit is x-security.* (one stylesheet, one icon set, the header, Boost, the problems card); what
     the two pages both show about the person is App\Support\Security\SecurityOverview.
     2026-10-05 before it: two named groups of equal cards, every password form marked (`_form`) so its error shows in its own
     form, once (the key `current_password` is shared by every password form here), and its dialog reopens with it.

     ⚠️ Every dialog sits at this root, outside the cards: a card's backdrop blur makes it the box a fixed dialog is placed in.
     RAW-BLADE RULE: every `@` follows a space, newline, tag, quote or bracket. --}}
@php
    $tiles = [
        ['icon' => 'shield', 'n' => $score['on'].'/'.$score['total'], 'label' => 'Protections on', 'href' => '#sx-boost'],
        ['icon' => 'plus', 'n' => $score['available'], 'label' => 'Points available', 'href' => '#sx-boost'],
        ['icon' => 'device', 'n' => count($trusted), 'label' => 'Trusted devices', 'href' => '#sec-trusted'],
        ['icon' => 'apps', 'n' => 0, 'label' => 'Connected apps', 'href' => '#sec-apps'],
    ];
    // the passwordless gate sends people here to set a password: open the form for them
    $pwFirst = ! $hasPassword && session()->has('password_status');
@endphp
<div class="vy-sx" x-data="{ pwOpen: @js($errored === 'password' || $pwFirst), tfOff: @js($errored === '2fa-off'), pkRemove: @js($pkErrored), ipOpen: @js($errored === 'ip'), ipRemove: null, exportOpen: false, signOutOpen: @js($errored === 'signout'), revokeId: @js($erroredDevice) }">
    <x-operator.shell active="security" title="Security" :viewer="$viewer">
        <x-security.styles />

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
        {{-- an error that belongs to no form here (each form shows its own, in its dialog) --}}
        @if ($errors->any() && $errored === null)
            <div class="vy-flash is-err vy-fade" role="alert">{{ $errors->first() }}</div>
        @endif

        <x-security.hero :score="$score" :tiles="$tiles" />

        <div class="vy-sx-cols">
            {{-- ══ LEFT — signing in ══════════════════════════════════════════════════════════════════════════════ --}}
            <div class="vy-sx-col">
                <x-security.problems :problems="$problems" seen-route="app.security.alerts.seen" secure="pwOpen = true" />

                <section class="vy-sx-card vy-sx-in" id="sx-signin" aria-labelledby="vy-sx-signin-t">
                    <h2 class="vy-sx-card-title" id="vy-sx-signin-t">Signing in</h2>
                    <p class="vy-sx-card-sub">How you prove it’s you.</p>

                    {{-- ⚠️⚠️ THE SET-A-PASSWORD PATH IS THIS SAME ROW. An account with no password has nothing to confirm with
                         (`Hash::check(x, null)` is false), so the dialog omits the current-password field only then; the
                         controller enforces the same split. --}}
                    <div class="vy-sx-block" id="sx-password">
                        <div class="vy-sx-row">
                            <div class="vy-sx-row-main">
                                <h3 class="vy-sx-row-title">Password</h3>
                                <span class="vy-sx-row-meta">{{ $hasPassword ? 'A long passphrase you don’t use anywhere else.' : 'No password yet — set one to sign in with your email address.' }}</span>
                            </div>
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="pwOpen = true">{{ $hasPassword ? 'Change' : 'Set password' }}</button>
                        </div>
                    </div>

                    {{-- TWO-FACTOR — the shared panel (owner and operator); its turn-off dialog is at this root --}}
                    @include('livewire.partials.two-factor-panel')

                    {{-- PASSKEYS — the SAME x-auth.passkeys the owner page renders, on the SAME PasskeyController/Service. Only
                         the route names differ; the ceremony and the empty state are one implementation (§N.8f). --}}
                    <x-auth.passkeys :passkeys="$passkeys" options-route="app.security.passkeys.options" store-route="app.security.passkeys.store" />
                </section>

                {{-- SIGN-IN ACCOUNTS — this account's own linked providers. ⚠️ The unlink refusal (the lockout floor) is enforced
                     in the route; the disabled button only explains it. A provider not switched on here offers no Connect. --}}
                <section class="vy-sx-card vy-sx-in" id="sec-connections" aria-labelledby="vy-sec-conn-t">
                    <h2 class="vy-sx-card-title" id="vy-sec-conn-t">Sign-in accounts</h2>
                    <p class="vy-sx-card-sub">Sign in to Vyrobel with an account you already have.</p>
                    @if (session('connections_status'))
                        <p class="vy-sx-note" role="status">{{ session('connections_status') }}</p>
                    @endif
                    @if (session('connections_error'))
                        <p class="vy-sx-note is-warn" role="alert">{{ session('connections_error') }}</p>
                    @endif
                    <ul class="vy-sx-rows">
                        @forelse ($connections as $connection)
                            <li class="vy-sx-row">
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $connection['label'] }}</span>
                                    <span class="vy-sx-row-meta">{{ $connection['email'] !== '' ? $connection['email'].' · ' : '' }}last used {{ $connection['last_used'] }}</span>
                                </div>
                                @if ($connection['can_unlink'])
                                    <form method="POST" action="{{ route('app.security.connections.unlink', $connection['id']) }}">
                                        @csrf
                                        <button type="submit" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm">Disconnect</button>
                                    </form>
                                @else
                                    <div class="vy-sx-row-act">
                                        <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" disabled aria-disabled="true" aria-describedby="vy-unlink-why-{{ $connection['id'] }}">Disconnect</button>
                                    </div>
                                @endif
                            </li>
                            @unless ($connection['can_unlink'])
                                <li class="vy-sx-note" id="vy-unlink-why-{{ $connection['id'] }}">{{ \App\Support\Auth\SignInCredentials::unlinkRefusalReason() }}</li>
                            @endunless
                        @empty
                            <li class="vy-sx-empty">
                                <strong>None connected</strong>
                                {{ $connectable === [] ? 'Signing in with Google or X isn’t switched on here yet.' : 'Connect one to sign in without typing your password.' }}
                            </li>
                        @endforelse
                    </ul>
                    @if ($connectable !== [])
                        <div class="vy-sx-alert-acts">
                            @foreach ($connectable as $provider)
                                <form method="POST" action="{{ route($provider['route']) }}">
                                    @csrf
                                    <button type="submit" class="vy-sx-btn vy-sx-btn--line vy-sx-btn--sm">Connect {{ $provider['label'] }}</button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            {{-- ══ MIDDLE — the score, your sessions, your data ══════════════════════════════════════════════════════ --}}
            <div class="vy-sx-col">
                <x-security.boost :score="$score" />

                {{-- ACTIVE SESSIONS — each named by a one-way key, never its session id (a session id IS a sign-in) --}}
                <section class="vy-sx-card vy-sx-in" id="sec-sessions" aria-labelledby="vy-sec-sess-t">
                    <div class="vy-sx-card-head">
                        <div>
                            <h2 class="vy-sx-card-title" id="vy-sec-sess-t">Active sessions &amp; devices</h2>
                            <p class="vy-sx-card-sub">Where your account is signed in now.</p>
                        </div>
                    </div>
                    <ul class="vy-sx-rows">
                        @forelse ($sessions as $sess)
                            <li class="vy-sx-row">
                                <span class="vy-sx-ico"><x-security.icon name="monitor" /></span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $sess['device'] }}@if ($sess['current']) <span class="vy-sx-chip is-ok">This device</span>@endif</span>
                                    <span class="vy-sx-row-meta"><span class="vy-sx-mono">{{ $sess['ip'] }}</span> · {{ $sess['current'] ? 'active now' : 'last active '.$sess['last_active']->diffForHumans() }}</span>
                                </div>
                                @unless ($sess['current'])
                                    <form method="POST" action="{{ route('app.security.sessions.sign-out') }}">
                                        @csrf
                                        <input type="hidden" name="session" value="{{ $sess['key'] }}">
                                        <button type="submit" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm">Sign out</button>
                                    </form>
                                @endunless
                            </li>
                        @empty
                            <li class="vy-sx-empty"><strong>No sessions recorded</strong></li>
                        @endforelse
                    </ul>
                    @if (count($sessions) > 1)
                        <div class="vy-sx-alert-acts">
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="signOutOpen = true">Sign out of all other devices</button>
                        </div>
                    @endif
                </section>

                {{-- YOUR DATA — a self-scoped export: your account and the actions you took (never guests or bookings) --}}
                <section class="vy-sx-card vy-sx-in" id="sx-data" aria-labelledby="vy-sec-data-t">
                    <h2 class="vy-sx-card-title" id="vy-sec-data-t">Data &amp; privacy</h2>
                    <p class="vy-sx-card-sub">Download a copy of your account details and the actions you’ve taken.</p>
                    <div class="vy-sx-alert-acts">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="exportOpen = true"><x-security.icon name="download" /> Export my data</button>
                    </div>
                </section>
            </div>

            {{-- ══ RIGHT — sign-in protection, the allowlist, apps ═══════════════════════════════════════════════════ --}}
            <div class="vy-sx-col">
                {{-- TRUSTED DEVICES — skip the two-factor code for 30 days; revoke re-asks for the password --}}
                <section class="vy-sx-card vy-sx-in" id="sec-trusted" aria-labelledby="vy-sec-trust-t">
                    <h2 class="vy-sx-card-title" id="vy-sec-trust-t">Sign-in protection</h2>
                    <div class="vy-sx-row">
                        <div class="vy-sx-row-main">
                            <h3 class="vy-sx-row-title">Trusted devices</h3>
                            <span class="vy-sx-row-meta">Skip the two-factor code for 30 days. Revoke any you don’t recognise.</span>
                        </div>
                    </div>
                    <ul class="vy-sx-sub-rows">
                        @forelse ($trusted as $d)
                            <li class="vy-sx-item">
                                <span class="vy-sx-ico"><x-security.icon name="device" /></span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $d['label'] }}@if ($d['current']) <span class="vy-sx-chip is-ok">This device</span>@endif</span>
                                    @if ($d['last_used'])<span class="vy-sx-row-meta">Last used {{ $d['last_used'] }}</span>@endif
                                </div>
                                <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="revokeId = '{{ $d['id'] }}'">Revoke</button>
                            </li>
                        @empty
                            {{-- ⚠️ 2026-10-05 — it said "Every sign-in will ask for your 2FA code" while two-factor was OFF --}}
                            <li class="vy-sx-empty">
                                <strong>No trusted devices</strong>
                                {{ $twoFactorOn ? 'Every sign-in asks for your code.' : 'Two-factor is off, so no device skips it.' }}
                            </li>
                        @endforelse
                    </ul>
                </section>

                {{-- IP ALLOWLIST — org-wide, genuinely enforced; changing it needs `settings.manage` --}}
                <section class="vy-sx-card vy-sx-in" id="sec-ip" aria-labelledby="vy-sec-ip-t">
                    <div class="vy-sx-card-head">
                        <div>
                            <h2 class="vy-sx-card-title" id="vy-sec-ip-t">IP allowlist</h2>
                            <p class="vy-sx-card-sub">When set, only these addresses can reach the back office — for everyone in this workspace.</p>
                        </div>
                        @if ($canManageIp)
                            <button type="button" class="vy-sx-btn vy-sx-btn--line vy-sx-btn--sm" @click="ipOpen = true">+ Add IP</button>
                        @endif
                    </div>
                    <ul class="vy-sx-rows">
                        @forelse ($ipRules as $r)
                            <li class="vy-sx-row">
                                <span class="vy-sx-ico"><x-security.icon name="globe" /></span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title vy-sx-mono">{{ $r['ip'] }}</span>
                                    @if ($r['label'] !== '')<span class="vy-sx-row-meta">{{ $r['label'] }}</span>@endif
                                </div>
                                @if ($canManageIp)
                                    <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="ipRemove = @js(['id' => $r['id'], 'ip' => $r['ip']])">Remove</button>
                                @endif
                            </li>
                        @empty
                            <li class="vy-sx-empty">
                                <strong>No IP restrictions</strong>
                                Anyone with a valid sign-in can reach the back office from any network.
                            </li>
                        @endforelse
                    </ul>
                    @unless ($canManageIp)
                        <p class="vy-sx-note">Changing the allowlist needs the “manage settings” permission.</p>
                    @endunless
                </section>

                {{-- CONNECTED APPS — the honest empty state: there is no third-party access for staff accounts --}}
                <section class="vy-sx-card vy-sx-in" id="sec-apps" aria-labelledby="vy-sec-apps-t">
                    <h2 class="vy-sx-card-title" id="vy-sec-apps-t">Connected apps</h2>
                    <p class="vy-sx-card-sub">Third-party apps you’ve allowed into your account.</p>
                    <ul class="vy-sx-rows">
                        <li class="vy-sx-empty">
                            <strong>No apps connected</strong>
                            Third-party app access isn’t available for staff accounts yet.
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        {{-- ══ DIALOGS — the security kit's solid card, centred by class (an inline display beside x-show is erased on the
             first hide). Each traps focus with the expression that shows it, and Escape closes it. ═══════════════════ --}}

        {{-- Change / set password — its errors sit under the dialog's title, inside the dialog they came from --}}
        <div class="vy-sx-scrim" x-cloak x-show="pwOpen" x-transition.opacity @keydown.escape.window="pwOpen = false" @click.self="pwOpen = false">
            <div class="vy-sx-dialog" x-show="pwOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sec-pw-t" tabindex="-1" x-trap.noscroll="pwOpen">
                <div class="vy-sx-dialog-head">
                    <span class="vy-sx-ico"><x-security.icon name="key" :size="18" /></span>
                    <div class="vy-sx-dialog-text">
                        <h3 class="vy-sx-dialog-title" id="vy-sec-pw-t">@if ($hasPassword) Change password @else Set a password @endif</h3>
                        <p class="vy-sx-dialog-sub">{{ $hasPassword ? 'You stay signed in here.' : 'Set one to sign in with your email address.' }}</p>
                    </div>
                    <button type="button" class="vy-sx-x" @click="pwOpen = false" aria-label="Close"><x-security.icon name="x" /></button>
                </div>
                @if ($errored === 'password' && $errors->any())
                    <div class="vy-sx-dialog-body vy-sx-pre">
                        <div class="vy-sx-errors" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <form method="POST" action="{{ route('app.security.password') }}">
                    @csrf
                    <input type="hidden" name="_form" value="password">
                    {{-- a hidden USERNAME so a password manager files the new password under the right account — no `name`, so it
                         is never sent --}}
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-sx-dialog-body">
                        @if ($hasPassword)
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sec-cur">Current password</label>
                                <x-password-field name="current_password" id="vy-sec-cur" autocomplete="current-password" :required="true" label="current password" autofocus />
                            </div>
                        @endif
                        <div class="vy-sx-pair">
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sec-new">New password</label>
                                <x-password-field name="password" id="vy-sec-new" autocomplete="new-password" :required="true" label="new password" :autofocus="! $hasPassword" />
                            </div>
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sec-conf">Confirm new password</label>
                                <x-password-field name="password_confirmation" id="vy-sec-conf" autocomplete="new-password" :required="true" label="password confirmation" />
                            </div>
                        </div>
                        {{-- ⚠️ DO13-4: the rule it enforces, stated — a refusal the person cannot comply with teaches nothing --}}
                        <p class="vy-sx-hint">{{ \App\Support\Auth\PasswordPolicy::hint() }}</p>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="pwOpen = false">Cancel</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--grad">@if ($hasPassword) Update password @else Set password @endif</button>
                    </div>
                </form>
            </div>
        </div>

        @include('livewire.partials.two-factor-off-dialog')
        <x-auth.passkey-remove-dialog remove-route="app.security.passkeys.remove" />

        @if ($canManageIp)
            {{-- Add IP --}}
            <div class="vy-sx-scrim" x-cloak x-show="ipOpen" x-transition.opacity @keydown.escape.window="ipOpen = false" @click.self="ipOpen = false">
                <div class="vy-sx-dialog" x-show="ipOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-operator-security-ipopen-4" tabindex="-1" x-trap.noscroll="ipOpen">
                    <form method="POST" action="{{ route('app.security.ip-rules.add') }}">
                        @csrf
                        <input type="hidden" name="_form" value="ip">
                        <div class="vy-sx-dialog-head">
                            <span class="vy-sx-ico"><x-security.icon name="globe" :size="18" /></span>
                            <div class="vy-sx-dialog-text">
                                <h3 class="vy-sx-dialog-title" id="vy-dlg-operator-security-ipopen-4">Add to the allowlist</h3>
                                <p class="vy-sx-dialog-sub">One address, or a block of them. Your address now is <strong>{{ $currentIp }}</strong>.</p>
                            </div>
                            <button type="button" class="vy-sx-x" @click="ipOpen = false" aria-label="Close"><x-security.icon name="x" /></button>
                        </div>
                        <div class="vy-sx-dialog-body">
                            @if ($errored === 'ip' && $errors->any())
                                <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                            @endif
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-lbl-operator-security-ip-address">Address</label>
                                <input id="vy-lbl-operator-security-ip-address" autofocus type="text" name="ip_address" class="vy-input vy-sx-mono" placeholder="203.0.113.4 or 203.0.113.0/24" value="{{ old('ip_address', $currentIp) }}" required autocomplete="off" spellcheck="false">
                            </div>
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-lbl-operator-security-label">Label · optional</label>
                                <input id="vy-lbl-operator-security-label" type="text" name="label" class="vy-input" maxlength="80" placeholder="Office" value="{{ old('label') }}" autocomplete="off">
                            </div>
                            <p class="vy-sx-note">When this is the first rule, your own address is added too, so you are never locked out.</p>
                        </div>
                        <div class="vy-sx-dialog-foot">
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="ipOpen = false">Cancel</button>
                            <button type="submit" class="vy-sx-btn vy-sx-btn--grad">Add to allowlist</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Remove IP — 📖 it was the browser's confirm() --}}
            <div class="vy-sx-scrim" x-cloak x-show="ipRemove !== null" x-transition.opacity @keydown.escape.window="ipRemove = null" @click.self="ipRemove = null">
                <div class="vy-sx-dialog" x-show="ipRemove !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-sec-ipremove-t" tabindex="-1" x-trap.noscroll="ipRemove !== null">
                    <form method="POST" :action="ipRemove ? @js(route('app.security.ip-rules.remove', ['rule' => '__ID__'])).replace('__ID__', ipRemove.id) : '#'">
                        @csrf
                        @method('DELETE')
                        <div class="vy-sx-dialog-head">
                            <span class="vy-sx-ico is-danger"><x-security.icon name="trash" :size="18" /></span>
                            <div class="vy-sx-dialog-text">
                                <h3 class="vy-sx-dialog-title" id="vy-dlg-sec-ipremove-t">Remove <span x-text="ipRemove ? ipRemove.ip : ''"></span>?</h3>
                                <p class="vy-sx-dialog-sub">Devices at this address lose access unless another rule covers them.</p>
                            </div>
                            <button type="button" class="vy-sx-x" @click="ipRemove = null" aria-label="Close"><x-security.icon name="x" /></button>
                        </div>
                        <div class="vy-sx-dialog-body">
                            <p class="vy-sx-note">If it is the only rule that lets your own address in, it is kept — removing it would lock you out. Removing the last rule turns the allowlist off.</p>
                        </div>
                        <div class="vy-sx-dialog-foot">
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="ipRemove = null" autofocus>Cancel</button>
                            <button type="submit" class="vy-sx-btn vy-sx-btn--danger">Remove</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Export your data — choice tiles, not native dropdowns whose look each browser decides --}}
        <div class="vy-sx-scrim" x-cloak x-show="exportOpen" x-transition.opacity @keydown.escape.window="exportOpen = false" @click.self="exportOpen = false">
            <div class="vy-sx-dialog" x-show="exportOpen" x-transition x-data="{ range: 'all', format: 'json' }" role="dialog" aria-modal="true" aria-labelledby="vy-dlg-operator-security-exportopen-3" tabindex="-1" x-trap.noscroll="exportOpen">
                <form method="GET" action="{{ route('app.security.export') }}">
                    <div class="vy-sx-dialog-head">
                        <span class="vy-sx-ico"><x-security.icon name="download" :size="18" /></span>
                        <div class="vy-sx-dialog-text">
                            <h3 class="vy-sx-dialog-title" id="vy-dlg-operator-security-exportopen-3">Export your data</h3>
                            <p class="vy-sx-dialog-sub">Your account details and the actions you’ve taken. Downloads to this device.</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="exportOpen = false" aria-label="Close"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        <div class="vy-sx-field">
                            <x-operator.choices name="format" label="Format" :options="[['value' => 'json', 'label' => 'JSON'], ['value' => 'csv', 'label' => 'CSV']]" model="format" variant="segmented" />
                        </div>
                        <div class="vy-sx-field">
                            <x-operator.choices name="range" label="Date range" :options="[['value' => 'all', 'label' => 'All time'], ['value' => 'year', 'label' => 'This year'], ['value' => 'custom', 'label' => 'Custom']]" model="range" variant="segmented" />
                        </div>
                        <div class="vy-sx-pair" x-show="range === 'custom'" x-cloak>
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sec-from">From</label>
                                <input type="date" name="from" id="vy-sec-from" class="vy-input">
                            </div>
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sec-to">To</label>
                                <input type="date" name="to" id="vy-sec-to" class="vy-input">
                            </div>
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="exportOpen = false">Cancel</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--grad">Download</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sign out of all other devices — re-asks for the password; comes back here (`return`) --}}
        <div class="vy-sx-scrim" x-cloak x-show="signOutOpen" x-transition.opacity @keydown.escape.window="signOutOpen = false" @click.self="signOutOpen = false">
            <div class="vy-sx-dialog" x-show="signOutOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-operator-security-signoutopen-2" tabindex="-1" x-trap.noscroll="signOutOpen">
                <form method="POST" action="{{ route('app.profile.sign-out-others') }}">
                    @csrf
                    <input type="hidden" name="return" value="security">
                    <input type="hidden" name="_form" value="signout">
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-sx-dialog-head">
                        <span class="vy-sx-ico is-danger"><x-security.icon name="logout" :size="18" /></span>
                        <div class="vy-sx-dialog-text">
                            <h3 class="vy-sx-dialog-title" id="vy-dlg-operator-security-signoutopen-2">Sign out of all other devices</h3>
                            <p class="vy-sx-dialog-sub">Every session but this one ends now. Confirm with your password.</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="signOutOpen = false" aria-label="Close"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'signout' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sec-so-pw">Your password</label>
                            <x-password-field name="current_password" id="vy-sec-so-pw" autocomplete="current-password" :required="true" label="password" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="signOutOpen = false">Cancel</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--danger">Sign out others</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Revoke a trusted device — re-asks for the password --}}
        <div class="vy-sx-scrim" x-cloak x-show="revokeId !== null" x-transition.opacity @keydown.escape.window="revokeId = null" @click.self="revokeId = null">
            <div class="vy-sx-dialog" x-show="revokeId !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-revoke-t" tabindex="-1" x-trap.noscroll="revokeId !== null">
                <form method="POST" action="{{ route('app.security.trusted.revoke') }}">
                    @csrf
                    <input type="hidden" name="_form" value="revoke">
                    <input type="hidden" name="device_id" :value="revokeId">
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-sx-dialog-head">
                        <span class="vy-sx-ico is-danger"><x-security.icon name="device" :size="18" /></span>
                        <div class="vy-sx-dialog-text">
                            <h3 class="vy-sx-dialog-title" id="vy-dlg-revoke-t">Revoke trusted device</h3>
                            <p class="vy-sx-dialog-sub">It will be asked for a two-factor code next time. Confirm with your password.</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="revokeId = null" aria-label="Close"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'revoke' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sec-rv-pw">Your password</label>
                            <x-password-field name="current_password" id="vy-sec-rv-pw" autocomplete="current-password" :required="true" label="password" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="revokeId = null">Cancel</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--danger">Revoke</button>
                    </div>
                </form>
            </div>
        </div>

    </x-operator.shell>
</div>
