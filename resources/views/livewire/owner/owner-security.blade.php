{{-- Owner "SECURITY" — the acting login's OWN security (per USER, so accountant/view-only sub-users manage their own). Real
     state only: password, two-factor with its recovery codes, passkeys, login approval, trusted devices, the REAL sessions
     table, connected apps (OAuth #72), a data export; the ONE primary-owner-only action is Deactivate (#42). Every write is a
     controller POST, never a Livewire action.

     ⚠️⚠️ 2026-10-24 — REBUILT ON HIS DESIGN, and SHARED with the back office's page: the score is
     App\Support\Security\SecurityScore (one rule for both pages — this page scored three protections and the other none), the
     kit is x-security.*, and what both pages show about the person is App\Support\Security\SecurityOverview.
     🖥 Before: one long column; every password box 184 px wide, 70% white with a white border on a white card; one wrong
     password printed under all six password fields (D-OWNER-SEC-ERRORS). Now each password form opens in its own dialog,
     marked with `_form`, and its error shows there, once.
     ⚠️ Login approval switches ON only while email really goes out: its approval link is an email, and a sign-in held for a
     link nobody receives cannot be finished. Switching it OFF is always allowed.

     ⚠️ Every dialog sits at this root, outside the cards: a card's backdrop blur makes it the box a fixed dialog is placed in.
     RAW-BLADE RULE: every `@` follows a space, newline, tag, quote or bracket. --}}
@php
    $tiles = [
        ['icon' => 'shield', 'n' => $score['on'].'/'.$score['total'], 'label' => 'Protections on', 'href' => '#sx-boost'],
        ['icon' => 'plus', 'n' => $score['available'], 'label' => 'Points available', 'href' => '#sx-boost'],
        ['icon' => 'device', 'n' => count($trusted), 'label' => 'Trusted devices', 'href' => '#sx-approval'],
        ['icon' => 'apps', 'n' => count($connectedApps), 'label' => 'Connected apps', 'href' => '#sx-apps'],
    ];
    $pwFirst = ! $hasPassword && session()->has('password_status');
@endphp
<div class="vy-sx" x-data="{ pwOpen: @js($errored === 'password' || $pwFirst), tfOff: @js($errored === '2fa-off'), pkRemove: @js($pkErrored), signOutOpen: @js($errored === 'signout'), revokeId: @js($erroredDevice), appRevoke: @js($erroredApp), approvalOpen: @js($errored === 'approval'), deactivateOpen: @js($errored === 'deactivate') }">
    <x-owner.shell active="security" title="Security" :viewer="$viewer">
        <x-security.styles />

        @foreach (['owner_notice', 'password_status', 'security_notice'] as $flash)
            @if (session($flash))
                <div class="vy-sx-flash is-ok" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach
        @if (session('security_error'))
            <div class="vy-sx-flash is-err" role="alert">{{ session('security_error') }}</div>
        @endif
        @if ($errors->any() && $errored === null)
            <div class="vy-sx-flash is-err" role="alert">{{ $errors->first() }}</div>
        @endif

        <x-security.hero :score="$score" :tiles="$tiles" />

        <div class="vy-sx-cols">
            {{-- ══ LEFT — signing in ══════════════════════════════════════════════════════════════════════════════ --}}
            <div class="vy-sx-col">
                <x-security.problems :problems="$problems" seen-route="owner.security.alerts.seen" secure="pwOpen = true" />

                <section class="vy-sx-card vy-sx-in" id="sx-signin" aria-labelledby="vy-sx-signin-t">
                    <h2 class="vy-sx-card-title" id="vy-sx-signin-t">Signing in</h2>
                    <p class="vy-sx-card-sub">How you prove it’s you. @if ($lastLogin) Last sign-in {{ $lastLogin->diffForHumans() }}. @endif</p>

                    {{-- ⚠️⚠️ THE SET-A-PASSWORD PATH IS THIS SAME ROW: with no password there is nothing to confirm with, so the
                         dialog omits the current-password field only then, and the controller enforces the same split. --}}
                    <div class="vy-sx-block" id="sx-password">
                        <div class="vy-sx-row">
                            <div class="vy-sx-row-main">
                                <h3 class="vy-sx-row-title">Password</h3>
                                <span class="vy-sx-row-meta">{{ $hasPassword ? 'A long passphrase you don’t use anywhere else.' : 'No password yet — set one to sign in with your email address.' }}</span>
                            </div>
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="pwOpen = true">{{ $hasPassword ? 'Change' : 'Set password' }}</button>
                        </div>
                    </div>

                    @include('livewire.partials.two-factor-panel')

                    {{-- PASSKEYS — the shared card (D24-2): one ceremony for both realms; only the route names differ --}}
                    <x-auth.passkeys :passkeys="$passkeys" options-route="owner.security.passkeys.options" store-route="owner.security.passkeys.store" />
                </section>
            </div>

            {{-- ══ MIDDLE — the score, your data, deactivate ═════════════════════════════════════════════════════════ --}}
            <div class="vy-sx-col">
                <x-security.boost :score="$score" />

                {{-- YOUR DATA — a REAL owner-scoped export (#28) --}}
                <section class="vy-sx-card vy-sx-in" id="sx-data" aria-labelledby="vy-sx-data-t">
                    <h2 class="vy-sx-card-title" id="vy-sx-data-t">Data &amp; privacy</h2>
                    <p class="vy-sx-card-sub">Download a copy of your data — your profile plus the statements, payouts and units your account covers.</p>
                    <div class="vy-sx-alert-acts">
                        <a href="{{ route('owner.security.export') }}" class="vy-sx-btn vy-sx-btn--soft"><x-security.icon name="download" /> Request my data</a>
                    </div>
                </section>

                {{-- DEACTIVATE — PRIMARY-OWNER-ONLY, reversible and non-destructive --}}
                @if ($isPrimaryOwner)
                    <section class="vy-sx-card vy-sx-in" id="sx-deactivate" aria-labelledby="vy-sx-deact-t">
                        <h2 class="vy-sx-card-title" id="vy-sx-deact-t">Deactivate account</h2>
                        <p class="vy-sx-card-sub">Pause your portal access. Your statements and payout history are <strong>kept</strong> — nothing is deleted. To reactivate, contact your management team.</p>
                        <div class="vy-sx-alert-acts">
                            <button type="button" class="vy-sx-btn vy-sx-btn--line" @click="deactivateOpen = true"><x-security.icon name="pause" /> Deactivate</button>
                        </div>
                    </section>
                @endif
            </div>

            {{-- ══ RIGHT — sign-in protection, sessions, apps ═══════════════════════════════════════════════════════ --}}
            <div class="vy-sx-col">
                <section class="vy-sx-card vy-sx-in" id="sx-approval" aria-labelledby="vy-sx-prot-t">
                    <h2 class="vy-sx-card-title" id="vy-sx-prot-t">Sign-in protection</h2>

                    {{-- LOGIN APPROVAL — a REAL hold on new devices; switching it re-asks for the password --}}
                    <div class="vy-sx-row">
                        <div class="vy-sx-row-main">
                            <h3 class="vy-sx-row-title">Login approval requests</h3>
                            <span class="vy-sx-row-meta">A new device waits until you approve it from a link we email you. A trusted device skips this.</span>
                        </div>
                        @if (! $approval['on'] && ! $approval['available'])
                            <button type="button" class="vy-sx-switch" role="switch" aria-checked="false" aria-label="Login approval requests" aria-disabled="true" aria-describedby="vy-sx-approval-why"></button>
                        @else
                            <button type="button" class="vy-sx-switch" role="switch" aria-checked="{{ $approval['on'] ? 'true' : 'false' }}" aria-label="Login approval requests" @click="approvalOpen = true"></button>
                        @endif
                    </div>
                    @if (! $approval['available'])
                        <p class="vy-sx-note is-warn" id="vy-sx-approval-why">
                            @if ($approval['on'])
                                Email isn’t being sent from Vyrobel yet, so a new device can’t be approved. Turn this off, or sign in only from a trusted device.
                            @else
                                Email isn’t being sent from Vyrobel yet, so an approval link couldn’t reach you. This stays off until it is.
                            @endif
                        </p>
                    @endif

                    {{-- TRUSTED DEVICES — REAL skip-2FA devices; remove re-asks for the password --}}
                    <div class="vy-sx-row">
                        <div class="vy-sx-row-main">
                            <h3 class="vy-sx-row-title">Trusted devices</h3>
                            <span class="vy-sx-row-meta">Skip the two-factor code at sign-in. Tick “Trust this device” at the code screen to add the one you’re on.</span>
                        </div>
                    </div>
                    <ul class="vy-sx-sub-rows">
                        @forelse ($trusted as $d)
                            <li class="vy-sx-item">
                                <span class="vy-sx-ico"><x-security.icon name="device" /></span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $d['label'] }}@if ($d['current']) <span class="vy-sx-chip is-ok">This device</span>@endif</span>
                                    <span class="vy-sx-row-meta">{{ $d['ip'] !== '' ? $d['ip'].' · ' : '' }}last used {{ $d['last_used'] ?? '—' }} · trust expires {{ $d['expires'] ?? '—' }}</span>
                                </div>
                                <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="revokeId = '{{ $d['id'] }}'">Remove</button>
                            </li>
                        @empty
                            <li class="vy-sx-empty">
                                <strong>No trusted devices</strong>
                                {{ $twoFactorOn ? 'Every sign-in asks for your code.' : 'Two-factor is off, so no device skips it.' }}
                            </li>
                        @endforelse
                    </ul>
                </section>

                {{-- ACTIVE SESSIONS — the REAL sessions table; "sign out others" re-asks for the password --}}
                <section class="vy-sx-card vy-sx-in" id="sx-sessions" aria-labelledby="vy-sx-sess-t">
                    <h2 class="vy-sx-card-title" id="vy-sx-sess-t">Active sessions</h2>
                    <p class="vy-sx-card-sub">Where you’re signed in right now.</p>
                    <ul class="vy-sx-rows">
                        @forelse ($sessions as $s)
                            <li class="vy-sx-row">
                                <span class="vy-sx-ico"><x-security.icon name="monitor" /></span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $s['device'] }}@if ($s['current']) <span class="vy-sx-chip is-ok">This device</span>@endif</span>
                                    <span class="vy-sx-row-meta"><span class="vy-sx-mono">{{ $s['ip'] }}</span> · active {{ $s['last_active']->diffForHumans() }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="vy-sx-empty"><strong>No active sessions recorded</strong></li>
                        @endforelse
                    </ul>
                    <div class="vy-sx-alert-acts">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="signOutOpen = true">Sign out all other sessions</button>
                    </div>
                </section>

                {{-- CONNECTED APPS — REAL (OAuth #72): third-party apps you authorized, their scopes, a re-authed revoke --}}
                <section class="vy-sx-card vy-sx-in" id="sx-apps" aria-labelledby="vy-sx-apps-t">
                    <h2 class="vy-sx-card-title" id="vy-sx-apps-t">Connected apps</h2>
                    <p class="vy-sx-card-sub">Apps you’ve given read-only access — never your guests’ contact details, door codes or Wi-Fi, and only your own data.</p>
                    <ul class="vy-sx-rows">
                        @forelse ($connectedApps as $app)
                            <li class="vy-sx-row">
                                <span class="vy-sx-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($app['name'], 0, 2)) }}</span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $app['name'] }}</span>
                                    <span class="vy-sx-row-meta">
                                        @foreach ($app['scopes'] as $i => $scopeId)<span class="vy-sx-chip" title="{{ $app['scope_labels'][$i] ?? '' }}">{{ $scopeId }}</span> @endforeach
                                        @if ($app['authorized_at']) · authorized {{ $app['authorized_at'] }}@endif
                                    </span>
                                </div>
                                <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="appRevoke = @js(['id' => $app['client_id'], 'name' => $app['name']])">Revoke</button>
                            </li>
                        @empty
                            <li class="vy-sx-empty"><strong>No connected apps</strong> Third-party apps you authorize will appear here.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </div>

        {{-- ══ DIALOGS ══════════════════════════════════════════════════════════════════════════════════════════════ --}}

        {{-- Change / set password --}}
        <div class="vy-sx-scrim" x-cloak x-show="pwOpen" x-transition.opacity @keydown.escape.window="pwOpen = false" @click.self="pwOpen = false">
            <div class="vy-sx-dialog" x-show="pwOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sx-pw-t" tabindex="-1" x-trap.noscroll="pwOpen">
                <div class="vy-sx-dialog-head">
                    <span class="vy-sx-ico"><x-security.icon name="key" :size="18" /></span>
                    <div class="vy-sx-dialog-text">
                        <h3 class="vy-sx-dialog-title" id="vy-sx-pw-t">@if ($hasPassword) Change password @else Set a password @endif</h3>
                        <p class="vy-sx-dialog-sub">{{ $hasPassword ? 'Other devices with “remember me” are signed out.' : 'You’ll be asked for it when confirming sensitive changes.' }}</p>
                    </div>
                    <button type="button" class="vy-sx-x" @click="pwOpen = false" aria-label="Close"><x-security.icon name="x" /></button>
                </div>
                <form method="POST" action="{{ route('owner.security.password') }}">
                    @csrf
                    <input type="hidden" name="_form" value="password">
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'password' && $errors->any())
                            <div class="vy-sx-errors" role="alert">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                        @if ($hasPassword)
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sx-cur">Current password</label>
                                <x-password-field name="current_password" id="vy-sx-cur" autocomplete="current-password" :required="true" label="current password" autofocus />
                            </div>
                        @endif
                        <div class="vy-sx-pair">
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sx-new">New password</label>
                                <x-password-field name="password" id="vy-sx-new" autocomplete="new-password" :required="true" label="new password" :autofocus="! $hasPassword" />
                            </div>
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sx-conf">Confirm new password</label>
                                <x-password-field name="password_confirmation" id="vy-sx-conf" autocomplete="new-password" :required="true" label="password confirmation" />
                            </div>
                        </div>
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
        <x-auth.passkey-remove-dialog remove-route="owner.security.passkeys.remove" />

        {{-- Login approval on / off — re-asks for the password --}}
        <div class="vy-sx-scrim" x-cloak x-show="approvalOpen" x-transition.opacity @keydown.escape.window="approvalOpen = false" @click.self="approvalOpen = false">
            <div class="vy-sx-dialog" x-show="approvalOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sx-appr-t" tabindex="-1" x-trap.noscroll="approvalOpen">
                <form method="POST" action="{{ route('owner.security.login-approval') }}">
                    @csrf
                    <input type="hidden" name="_form" value="approval">
                    <input type="hidden" name="enabled" value="{{ $approval['on'] ? '0' : '1' }}">
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-sx-dialog-head">
                        <span class="vy-sx-ico"><x-security.icon name="user-check" :size="18" /></span>
                        <div class="vy-sx-dialog-text">
                            <h3 class="vy-sx-dialog-title" id="vy-sx-appr-t">{{ $approval['on'] ? 'Turn off login approval' : 'Turn on login approval' }}</h3>
                            <p class="vy-sx-dialog-sub">{{ $approval['on'] ? 'New devices will sign in without waiting for your approval.' : 'A new device will wait until you approve it from the link we email you.' }}</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="approvalOpen = false" aria-label="Close"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'approval' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sx-appr-pw">Your password</label>
                            <x-password-field name="current_password" id="vy-sx-appr-pw" autocomplete="current-password" :required="true" label="password" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="approvalOpen = false">Cancel</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--grad">{{ $approval['on'] ? 'Turn off' : 'Turn on' }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sign out all other sessions --}}
        <div class="vy-sx-scrim" x-cloak x-show="signOutOpen" x-transition.opacity @keydown.escape.window="signOutOpen = false" @click.self="signOutOpen = false">
            <div class="vy-sx-dialog" x-show="signOutOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sx-so-t" tabindex="-1" x-trap.noscroll="signOutOpen">
                <form method="POST" action="{{ route('owner.security.sessions.signout') }}">
                    @csrf
                    <input type="hidden" name="_form" value="signout">
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-sx-dialog-head">
                        <span class="vy-sx-ico is-danger"><x-security.icon name="logout" :size="18" /></span>
                        <div class="vy-sx-dialog-text">
                            <h3 class="vy-sx-dialog-title" id="vy-sx-so-t">Sign out all other sessions</h3>
                            <p class="vy-sx-dialog-sub">Every session but this one ends now. Confirm with your password.</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="signOutOpen = false" aria-label="Close"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'signout' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sx-so-pw">Your password</label>
                            <x-password-field name="current_password" id="vy-sx-so-pw" autocomplete="current-password" :required="true" label="password" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="signOutOpen = false">Cancel</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--danger">Sign out others</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Remove a trusted device --}}
        <div class="vy-sx-scrim" x-cloak x-show="revokeId !== null" x-transition.opacity @keydown.escape.window="revokeId = null" @click.self="revokeId = null">
            <div class="vy-sx-dialog" x-show="revokeId !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sx-rv-t" tabindex="-1" x-trap.noscroll="revokeId !== null">
                <form method="POST" action="{{ route('owner.security.trusted-devices.revoke') }}">
                    @csrf
                    <input type="hidden" name="_form" value="revoke">
                    <input type="hidden" name="device_id" :value="revokeId">
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-sx-dialog-head">
                        <span class="vy-sx-ico is-danger"><x-security.icon name="device" :size="18" /></span>
                        <div class="vy-sx-dialog-text">
                            <h3 class="vy-sx-dialog-title" id="vy-sx-rv-t">Remove trusted device</h3>
                            <p class="vy-sx-dialog-sub">It will be asked for a two-factor code next time. Confirm with your password.</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="revokeId = null" aria-label="Close"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'revoke' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sx-rv-pw">Your password</label>
                            <x-password-field name="current_password" id="vy-sx-rv-pw" autocomplete="current-password" :required="true" label="password" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="revokeId = null">Cancel</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--danger">Remove</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Revoke a connected app — immediate; re-asks for the password --}}
        <div class="vy-sx-scrim" x-cloak x-show="appRevoke !== null" x-transition.opacity @keydown.escape.window="appRevoke = null" @click.self="appRevoke = null">
            <div class="vy-sx-dialog" x-show="appRevoke !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sx-app-t" tabindex="-1" x-trap.noscroll="appRevoke !== null">
                <form method="POST" action="{{ route('owner.security.connected-apps.revoke') }}">
                    @csrf
                    <input type="hidden" name="_form" value="app-revoke">
                    <input type="hidden" name="client_id" :value="appRevoke ? appRevoke.id : ''">
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-sx-dialog-head">
                        <span class="vy-sx-ico is-danger"><x-security.icon name="apps" :size="18" /></span>
                        <div class="vy-sx-dialog-text">
                            <h3 class="vy-sx-dialog-title" id="vy-sx-app-t">Revoke <span x-text="appRevoke ? appRevoke.name : ''"></span>?</h3>
                            <p class="vy-sx-dialog-sub">Its access stops immediately. Confirm with your password.</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="appRevoke = null" aria-label="Close"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'app-revoke' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sx-app-pw">Your password</label>
                            <x-password-field name="current_password" id="vy-sx-app-pw" autocomplete="current-password" :required="true" label="password" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="appRevoke = null">Cancel</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--danger">Revoke</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($isPrimaryOwner)
            {{-- Deactivate — reversible, nothing deleted; re-asks for the password (it was the browser's confirm()) --}}
            <div class="vy-sx-scrim" x-cloak x-show="deactivateOpen" x-transition.opacity @keydown.escape.window="deactivateOpen = false" @click.self="deactivateOpen = false">
                <div class="vy-sx-dialog" x-show="deactivateOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sx-de-t" tabindex="-1" x-trap.noscroll="deactivateOpen">
                    <form method="POST" action="{{ route('owner.security.deactivate') }}">
                        @csrf
                        <input type="hidden" name="_form" value="deactivate">
                        <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                        <div class="vy-sx-dialog-head">
                            <span class="vy-sx-ico is-danger"><x-security.icon name="pause" :size="18" /></span>
                            <div class="vy-sx-dialog-text">
                                <h3 class="vy-sx-dialog-title" id="vy-sx-de-t">Deactivate your account?</h3>
                                <p class="vy-sx-dialog-sub">You’ll be signed out and can’t sign in until it’s reactivated. Your statements and payout history are kept.</p>
                            </div>
                            <button type="button" class="vy-sx-x" @click="deactivateOpen = false" aria-label="Close"><x-security.icon name="x" /></button>
                        </div>
                        <div class="vy-sx-dialog-body">
                            @if ($errored === 'deactivate' && $errors->any())
                                <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                            @endif
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sx-de-pw">Your password</label>
                                <x-password-field name="current_password" id="vy-sx-de-pw" autocomplete="current-password" :required="true" label="password" autofocus />
                            </div>
                        </div>
                        <div class="vy-sx-dialog-foot">
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="deactivateOpen = false">Keep my account</button>
                            <button type="submit" class="vy-sx-btn vy-sx-btn--danger">Deactivate</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </x-owner.shell>
</div>
