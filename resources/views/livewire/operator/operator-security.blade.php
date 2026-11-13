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
        ['icon' => 'shield', 'n' => $score['on'].'/'.$score['total'], 'label' => __('livewire/operator/operator-security.tile_protections_on'), 'href' => '#sx-boost'],
        ['icon' => 'plus', 'n' => $score['available'], 'label' => __('livewire/operator/operator-security.tile_points_available'), 'href' => '#sx-boost'],
        ['icon' => 'device', 'n' => count($trusted), 'label' => __('livewire/operator/operator-security.trusted_devices'), 'href' => '#sec-trusted'],
        ['icon' => 'apps', 'n' => 0, 'label' => __('livewire/operator/operator-security.connected_apps'), 'href' => '#sec-apps'],
    ];
    // the passwordless gate sends people here to set a password: open the form for them
    $pwFirst = ! $hasPassword && session()->has('password_status');
    // the address in the remove-IP dialog's title, filled in by Alpine (a static span, no data)
    $ipRemoveHtml = '<span x-text="ipRemove ? ipRemove.ip : \'\'"></span>';
@endphp
<div class="vy-sx" x-data="{ approvalOpen: @js($errored === 'approval'), pwOpen: @js($errored === 'password' || $pwFirst), tfOff: @js($errored === '2fa-off'), pkRemove: @js($pkErrored), ipOpen: @js($errored === 'ip'), ipRemove: null, exportOpen: false, deleteOpen: @js($errored === 'deletion'), signOutOpen: @js($errored === 'signout'), revokeId: @js($erroredDevice) }">
    <x-operator.shell active="security" :title="__('livewire/operator/operator-security.page_title')" :viewer="$viewer">
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
                    <h2 class="vy-sx-card-title" id="vy-sx-signin-t">{{ __('livewire/operator/operator-security.signing_in_heading') }}</h2>
                    <p class="vy-sx-card-sub">{{ __('livewire/operator/operator-security.signing_in_sub') }}</p>

                    {{-- ⚠️⚠️ THE SET-A-PASSWORD PATH IS THIS SAME ROW. An account with no password has nothing to confirm with
                         (`Hash::check(x, null)` is false), so the dialog omits the current-password field only then; the
                         controller enforces the same split. --}}
                    <div class="vy-sx-block" id="sx-password">
                        <div class="vy-sx-row">
                            <div class="vy-sx-row-main">
                                <h3 class="vy-sx-row-title">{{ __('livewire/operator/operator-security.password_heading') }}</h3>
                                <span class="vy-sx-row-meta">@if (! $hasPassword) {{ __('livewire/operator/operator-security.password_none') }} @elseif ($passwordChanged) {{ __('livewire/operator/operator-security.password_changed', ['when' => $passwordChanged]) }} @else {{ __('livewire/operator/operator-security.password_not_recorded') }} @endif</span>
                            </div>
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="pwOpen = true">{{ $hasPassword ? __('livewire/operator/operator-security.change_button') : __('livewire/operator/operator-security.set_password_button') }}</button>
                        </div>
                    </div>

                    {{-- TWO-FACTOR — the shared panel (owner and operator); its turn-off dialog is at this root --}}
                    @include('livewire.partials.two-factor-panel')

                    {{-- PASSKEYS — the SAME x-auth.passkeys the owner page renders, on the SAME PasskeyController/Service. Only
                         the route names differ; the ceremony and the empty state are one implementation (§N.8f). --}}
                    <x-auth.passkeys :passkeys="$passkeys" options-route="app.security.passkeys.options" store-route="app.security.passkeys.store" />

                    {{-- LOGIN ALERTS · SESSION TIMEOUT (Part 3) — the same rows the owner page shows --}}
                    <x-security.protection-rows :alerts="$alerts" :timeout="$timeout" :routes="['alerts' => 'app.security.login-alerts', 'timeout' => 'app.security.session-timeout']" />
                </section>

                {{-- SIGN-IN ACCOUNTS — this account's own linked providers. ⚠️ The unlink refusal (the lockout floor) is enforced
                     in the route; the disabled button only explains it. A provider not switched on here offers no Connect. --}}
                <section class="vy-sx-card vy-sx-in" id="sec-connections" aria-labelledby="vy-sec-conn-t">
                    <h2 class="vy-sx-card-title" id="vy-sec-conn-t">{{ __('livewire/operator/operator-security.accounts_heading') }}</h2>
                    <p class="vy-sx-card-sub">{{ __('livewire/operator/operator-security.accounts_sub') }}</p>
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
                                    <span class="vy-sx-row-meta">{{ $connection['email'] !== '' ? __('livewire/operator/operator-security.connection_meta_email', ['email' => $connection['email'], 'when' => $connection['last_used']]) : __('livewire/operator/operator-security.connection_meta', ['when' => $connection['last_used']]) }}</span>
                                </div>
                                @if ($connection['can_unlink'])
                                    <form method="POST" action="{{ route('app.security.connections.unlink', $connection['id']) }}">
                                        @csrf
                                        <button type="submit" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm">{{ __('livewire/operator/operator-security.disconnect') }}</button>
                                    </form>
                                @else
                                    <div class="vy-sx-row-act">
                                        <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" disabled aria-disabled="true" aria-describedby="vy-unlink-why-{{ $connection['id'] }}">{{ __('livewire/operator/operator-security.disconnect') }}</button>
                                    </div>
                                @endif
                            </li>
                            @unless ($connection['can_unlink'])
                                <li class="vy-sx-note" id="vy-unlink-why-{{ $connection['id'] }}">{{ \App\Support\Auth\SignInCredentials::unlinkRefusalReason() }}</li>
                            @endunless
                        @empty
                            <li class="vy-sx-empty">
                                <strong>{{ __('livewire/operator/operator-security.none_connected') }}</strong>
                                {{ $connectable === [] ? __('livewire/operator/operator-security.accounts_off') : __('livewire/operator/operator-security.accounts_connect_hint') }}
                            </li>
                        @endforelse
                    </ul>
                    @if ($connectable !== [])
                        <div class="vy-sx-alert-acts">
                            @foreach ($connectable as $provider)
                                <form method="POST" action="{{ route($provider['route']) }}">
                                    @csrf
                                    <button type="submit" class="vy-sx-btn vy-sx-btn--line vy-sx-btn--sm">{{ __('livewire/operator/operator-security.connect_provider', ['provider' => $provider['label']]) }}</button>
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
                            <h2 class="vy-sx-card-title" id="vy-sec-sess-t">{{ __('livewire/operator/operator-security.sessions_heading') }}</h2>
                            <p class="vy-sx-card-sub">{{ __('livewire/operator/operator-security.sessions_sub') }}</p>
                        </div>
                    </div>
                    <ul class="vy-sx-rows">
                        @forelse ($sessions as $sess)
                            <li class="vy-sx-row">
                                <span class="vy-sx-ico"><x-security.icon name="monitor" /></span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $sess['device'] }}@if ($sess['current']) <span class="vy-sx-chip is-ok">{{ __('livewire/operator/operator-security.this_device') }}</span>@endif</span>
                                    <span class="vy-sx-row-meta">{!! $sess['current'] ? __('livewire/operator/operator-security.session_meta_now', ['ip' => e($sess['ip'])]) : __('livewire/operator/operator-security.session_meta_last', ['ip' => e($sess['ip']), 'when' => e($sess['last_active']->diffForHumans())]) !!}</span>
                                </div>
                                @unless ($sess['current'])
                                    <form method="POST" action="{{ route('app.security.sessions.sign-out') }}">
                                        @csrf
                                        <input type="hidden" name="session" value="{{ $sess['key'] }}">
                                        <button type="submit" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm">{{ __('livewire/operator/operator-security.sign_out_session') }}</button>
                                    </form>
                                @endunless
                            </li>
                        @empty
                            <li class="vy-sx-empty"><strong>{{ __('livewire/operator/operator-security.no_sessions') }}</strong></li>
                        @endforelse
                    </ul>
                    @if (count($sessions) > 1)
                        <div class="vy-sx-alert-acts">
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="signOutOpen = true">{{ __('livewire/operator/operator-security.sign_out_all_others') }}</button>
                        </div>
                    @endif
                </section>

                {{-- YOUR DATA — a self-scoped export: your account and the actions you took (never guests or bookings) --}}
                <section class="vy-sx-card vy-sx-in" id="sx-data" aria-labelledby="vy-sec-data-t">
                    <h2 class="vy-sx-card-title" id="vy-sec-data-t">{{ __('livewire/operator/operator-security.data_heading') }}</h2>
                    <p class="vy-sx-card-sub">{{ __('livewire/operator/operator-security.data_sub') }}</p>
                    <div class="vy-sx-alert-acts">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="exportOpen = true"><x-security.icon name="download" /> {{ __('livewire/operator/operator-security.export_my_data') }}</button>
                    </div>
                </section>

                {{-- DEC-6 — REQUEST DELETION of this account: files a Privacy-desk request for a person to answer; erases nothing --}}
                <x-security.deletion-card :request="$deletionRequest" />
            </div>

            {{-- ══ RIGHT — sign-in protection, the allowlist, apps ═══════════════════════════════════════════════════ --}}
            <div class="vy-sx-col">
                {{-- TRUSTED DEVICES — skip the two-factor code for 30 days; revoke re-asks for the password --}}
                <section class="vy-sx-card vy-sx-in" id="sec-trusted" aria-labelledby="vy-sec-trust-t">
                    <h2 class="vy-sx-card-title" id="vy-sec-trust-t">{{ __('livewire/operator/operator-security.protection_heading') }}</h2>

                    {{-- LOGIN APPROVAL (Part 3 — as the owner page has it) — a REAL hold on new devices; ON only while email is sent --}}
                    <div class="vy-sx-row" id="sx-approval">
                        <div class="vy-sx-row-main">
                            <h3 class="vy-sx-row-title">{{ __('livewire/operator/operator-security.login_approval') }}</h3>
                            <span class="vy-sx-row-meta">{{ __('livewire/operator/operator-security.login_approval_meta') }}</span>
                        </div>
                        @if (! $approval['on'] && ! $approval['available'])
                            <button type="button" class="vy-sx-switch" role="switch" aria-checked="false" aria-label="{{ __('livewire/operator/operator-security.login_approval') }}" aria-disabled="true" aria-describedby="vy-sx-approval-why"></button>
                        @else
                            <button type="button" class="vy-sx-switch" role="switch" aria-checked="{{ $approval['on'] ? 'true' : 'false' }}" aria-label="{{ __('livewire/operator/operator-security.login_approval') }}" @click="approvalOpen = true"></button>
                        @endif
                    </div>
                    @if (! $approval['available'])
                        <p class="vy-sx-note is-warn" id="vy-sx-approval-why">
                            @if ($approval['on'])
                                {{ __('livewire/operator/operator-security.approval_no_email_on') }}
                            @else
                                {{ __('livewire/operator/operator-security.approval_no_email_off') }}
                            @endif
                        </p>
                    @endif
                    <x-security.approvals :waiting="$waiting" route="app.security.login-approval.decide" />

                    <div class="vy-sx-row">
                        <div class="vy-sx-row-main">
                            <h3 class="vy-sx-row-title">{{ __('livewire/operator/operator-security.trusted_devices') }}</h3>
                            <span class="vy-sx-row-meta">{{ __('livewire/operator/operator-security.trusted_devices_meta') }}</span>
                        </div>
                    </div>
                    <ul class="vy-sx-sub-rows">
                        @forelse ($trusted as $d)
                            <li class="vy-sx-item">
                                <span class="vy-sx-ico"><x-security.icon name="device" /></span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $d['label'] }}@if ($d['current']) <span class="vy-sx-chip is-ok">{{ __('livewire/operator/operator-security.this_device') }}</span>@endif</span>
                                    @if ($d['last_used'])<span class="vy-sx-row-meta">{{ __('livewire/operator/operator-security.trusted_last_used', ['when' => $d['last_used']]) }}</span>@endif
                                </div>
                                <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="revokeId = '{{ $d['id'] }}'">{{ __('livewire/operator/operator-security.revoke') }}</button>
                            </li>
                        @empty
                            {{-- ⚠️ 2026-10-05 — it said "Every sign-in will ask for your 2FA code" while two-factor was OFF --}}
                            <li class="vy-sx-empty">
                                <strong>{{ __('livewire/operator/operator-security.no_trusted_devices') }}</strong>
                                {{ $twoFactorOn ? __('livewire/operator/operator-security.no_trusted_2fa_on') : __('livewire/operator/operator-security.no_trusted_2fa_off') }}
                            </li>
                        @endforelse
                    </ul>
                </section>

                {{-- IP ALLOWLIST — org-wide, genuinely enforced; changing it needs `settings.manage` --}}
                <section class="vy-sx-card vy-sx-in" id="sec-ip" aria-labelledby="vy-sec-ip-t">
                    <div class="vy-sx-card-head">
                        <div>
                            <h2 class="vy-sx-card-title" id="vy-sec-ip-t">{{ __('livewire/operator/operator-security.ip_heading') }}</h2>
                            <p class="vy-sx-card-sub">{{ __('livewire/operator/operator-security.ip_sub') }}</p>
                        </div>
                        @if ($canManageIp)
                            <button type="button" class="vy-sx-btn vy-sx-btn--line vy-sx-btn--sm" @click="ipOpen = true">{{ __('livewire/operator/operator-security.add_ip') }}</button>
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
                                    <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="ipRemove = @js(['id' => $r['id'], 'ip' => $r['ip']])">{{ __('common.remove') }}</button>
                                @endif
                            </li>
                        @empty
                            <li class="vy-sx-empty">
                                <strong>{{ __('livewire/operator/operator-security.no_ip_rules') }}</strong>
                                {{ __('livewire/operator/operator-security.no_ip_rules_hint') }}
                            </li>
                        @endforelse
                    </ul>
                    @unless ($canManageIp)
                        <p class="vy-sx-note">{{ __('livewire/operator/operator-security.ip_needs_permission') }}</p>
                    @endunless
                </section>

                {{-- CONNECTED APPS — the honest empty state: there is no third-party access for staff accounts --}}
                <section class="vy-sx-card vy-sx-in" id="sec-apps" aria-labelledby="vy-sec-apps-t">
                    <h2 class="vy-sx-card-title" id="vy-sec-apps-t">{{ __('livewire/operator/operator-security.connected_apps') }}</h2>
                    <p class="vy-sx-card-sub">{{ __('livewire/operator/operator-security.apps_sub') }}</p>
                    <ul class="vy-sx-rows">
                        <li class="vy-sx-empty">
                            <strong>{{ __('livewire/operator/operator-security.no_apps') }}</strong>
                            {{ __('livewire/operator/operator-security.no_apps_hint') }}
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
                        <h3 class="vy-sx-dialog-title" id="vy-sec-pw-t">@if ($hasPassword) {{ __('livewire/operator/operator-security.change_password_title') }} @else {{ __('livewire/operator/operator-security.set_password_title') }} @endif</h3>
                        <p class="vy-sx-dialog-sub">{{ $hasPassword ? __('livewire/operator/operator-security.change_password_sub') : __('livewire/operator/operator-security.set_password_sub') }}</p>
                    </div>
                    <button type="button" class="vy-sx-x" @click="pwOpen = false" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
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
                                <label class="vy-sx-label" for="vy-sec-cur">{{ __('livewire/operator/operator-security.current_password') }}</label>
                                <x-password-field name="current_password" id="vy-sec-cur" autocomplete="current-password" :required="true" :label="__('livewire/operator/operator-security.field_current_password')" autofocus />
                            </div>
                        @endif
                        <div class="vy-sx-pair">
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sec-new">{{ __('livewire/operator/operator-security.new_password') }}</label>
                                <x-password-field name="password" id="vy-sec-new" autocomplete="new-password" :required="true" :label="__('livewire/operator/operator-security.field_new_password')" :autofocus="! $hasPassword" />
                            </div>
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sec-conf">{{ __('livewire/operator/operator-security.confirm_new_password') }}</label>
                                <x-password-field name="password_confirmation" id="vy-sec-conf" autocomplete="new-password" :required="true" :label="__('livewire/operator/operator-security.field_password_confirmation')" />
                            </div>
                        </div>
                        {{-- ⚠️ DO13-4: the rule it enforces, stated — a refusal the person cannot comply with teaches nothing --}}
                        <p class="vy-sx-hint">{{ \App\Support\Auth\PasswordPolicy::hint() }}</p>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="pwOpen = false">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--grad">@if ($hasPassword) {{ __('livewire/operator/operator-security.update_password') }} @else {{ __('livewire/operator/operator-security.set_password_button') }} @endif</button>
                    </div>
                </form>
            </div>
        </div>

        @include('livewire.partials.two-factor-off-dialog')
        <x-auth.passkey-remove-dialog remove-route="app.security.passkeys.remove" />

        {{-- Login approval on / off — re-asks for the password (the owner page's dialog, the shared controller) --}}
        <div class="vy-sx-scrim" x-cloak x-show="approvalOpen" x-transition.opacity @keydown.escape.window="approvalOpen = false" @click.self="approvalOpen = false">
            <div class="vy-sx-dialog" x-show="approvalOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sx-appr-t" tabindex="-1" x-trap.noscroll="approvalOpen">
                <form method="POST" action="{{ route('app.security.login-approval') }}">
                    @csrf
                    <input type="hidden" name="_form" value="approval">
                    <input type="hidden" name="enabled" value="{{ $approval['on'] ? '0' : '1' }}">
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                    <div class="vy-sx-dialog-head">
                        <span class="vy-sx-ico"><x-security.icon name="user-check" :size="18" /></span>
                        <div class="vy-sx-dialog-text">
                            <h3 class="vy-sx-dialog-title" id="vy-sx-appr-t">{{ $approval['on'] ? __('livewire/operator/operator-security.approval_off_title') : __('livewire/operator/operator-security.approval_on_title') }}</h3>
                            <p class="vy-sx-dialog-sub">{{ $approval['on'] ? __('livewire/operator/operator-security.approval_off_sub') : __('livewire/operator/operator-security.approval_on_sub') }}</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="approvalOpen = false" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'approval' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sx-appr-pw">{{ __('livewire/operator/operator-security.your_password') }}</label>
                            <x-password-field name="current_password" id="vy-sx-appr-pw" autocomplete="current-password" :required="true" :label="__('livewire/operator/operator-security.field_password')" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="approvalOpen = false">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--grad">{{ $approval['on'] ? __('livewire/operator/operator-security.turn_off') : __('livewire/operator/operator-security.turn_on') }}</button>
                    </div>
                </form>
            </div>
        </div>

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
                                <h3 class="vy-sx-dialog-title" id="vy-dlg-operator-security-ipopen-4">{{ __('livewire/operator/operator-security.add_ip_title') }}</h3>
                                <p class="vy-sx-dialog-sub">{!! __('livewire/operator/operator-security.add_ip_sub', ['ip' => e($currentIp)]) !!}</p>
                            </div>
                            <button type="button" class="vy-sx-x" @click="ipOpen = false" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                        </div>
                        <div class="vy-sx-dialog-body">
                            @if ($errored === 'ip' && $errors->any())
                                <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                            @endif
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-lbl-operator-security-ip-address">{{ __('livewire/operator/operator-security.ip_address_label') }}</label>
                                <input id="vy-lbl-operator-security-ip-address" autofocus type="text" name="ip_address" class="vy-input vy-sx-mono" placeholder="{{ __('livewire/operator/operator-security.ip_address_placeholder') }}" value="{{ old('ip_address', $currentIp) }}" required autocomplete="off" spellcheck="false">
                            </div>
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-lbl-operator-security-label">{{ __('livewire/operator/operator-security.ip_label_label') }}</label>
                                <input id="vy-lbl-operator-security-label" type="text" name="label" class="vy-input" maxlength="80" placeholder="{{ __('livewire/operator/operator-security.ip_label_placeholder') }}" value="{{ old('label') }}" autocomplete="off">
                            </div>
                            <p class="vy-sx-note">{{ __('livewire/operator/operator-security.add_ip_note') }}</p>
                        </div>
                        <div class="vy-sx-dialog-foot">
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="ipOpen = false">{{ __('common.cancel') }}</button>
                            <button type="submit" class="vy-sx-btn vy-sx-btn--grad">{{ __('livewire/operator/operator-security.add_to_allowlist') }}</button>
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
                                <h3 class="vy-sx-dialog-title" id="vy-dlg-sec-ipremove-t">{!! __('livewire/operator/operator-security.remove_ip_title', ['ip' => $ipRemoveHtml]) !!}</h3>
                                <p class="vy-sx-dialog-sub">{{ __('livewire/operator/operator-security.remove_ip_sub') }}</p>
                            </div>
                            <button type="button" class="vy-sx-x" @click="ipRemove = null" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                        </div>
                        <div class="vy-sx-dialog-body">
                            <p class="vy-sx-note">{{ __('livewire/operator/operator-security.remove_ip_note') }}</p>
                        </div>
                        <div class="vy-sx-dialog-foot">
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="ipRemove = null" autofocus>{{ __('common.cancel') }}</button>
                            <button type="submit" class="vy-sx-btn vy-sx-btn--danger">{{ __('common.remove') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Export your data — choice tiles, not native dropdowns whose look each browser decides --}}
        @if ($deletionRequest === null)
            <x-security.deletion-dialog :action="route('app.security.deletion-request')" :has-password="$hasPassword" :errored="$errored" />
        @endif

        <div class="vy-sx-scrim" x-cloak x-show="exportOpen" x-transition.opacity @keydown.escape.window="exportOpen = false" @click.self="exportOpen = false">
            <div class="vy-sx-dialog" x-show="exportOpen" x-transition x-data="{ range: 'all', format: 'json' }" role="dialog" aria-modal="true" aria-labelledby="vy-dlg-operator-security-exportopen-3" tabindex="-1" x-trap.noscroll="exportOpen">
                <form method="GET" action="{{ route('app.security.export') }}">
                    <div class="vy-sx-dialog-head">
                        <span class="vy-sx-ico"><x-security.icon name="download" :size="18" /></span>
                        <div class="vy-sx-dialog-text">
                            <h3 class="vy-sx-dialog-title" id="vy-dlg-operator-security-exportopen-3">{{ __('livewire/operator/operator-security.export_title') }}</h3>
                            <p class="vy-sx-dialog-sub">{{ __('livewire/operator/operator-security.export_sub') }}</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="exportOpen = false" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        <div class="vy-sx-field">
                            <x-operator.choices name="format" :label="__('livewire/operator/operator-security.export_format')" :options="[['value' => 'json', 'label' => 'JSON'], ['value' => 'csv', 'label' => 'CSV']]" model="format" variant="segmented" />
                        </div>
                        <div class="vy-sx-field">
                            <x-operator.choices name="range" :label="__('livewire/operator/operator-security.export_range')" :options="[['value' => 'all', 'label' => __('livewire/operator/operator-security.range_all_time')], ['value' => 'year', 'label' => __('livewire/operator/operator-security.range_this_year')], ['value' => 'custom', 'label' => __('livewire/operator/operator-security.range_custom')]]" model="range" variant="segmented" />
                        </div>
                        <div class="vy-sx-pair" x-show="range === 'custom'" x-cloak>
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sec-from">{{ __('livewire/operator/operator-security.range_from') }}</label>
                                <input type="date" name="from" id="vy-sec-from" class="vy-input">
                            </div>
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sec-to">{{ __('livewire/operator/operator-security.range_to') }}</label>
                                <input type="date" name="to" id="vy-sec-to" class="vy-input">
                            </div>
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="exportOpen = false">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--grad">{{ __('common.download') }}</button>
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
                            <h3 class="vy-sx-dialog-title" id="vy-dlg-operator-security-signoutopen-2">{{ __('livewire/operator/operator-security.sign_out_all_others') }}</h3>
                            <p class="vy-sx-dialog-sub">{{ __('livewire/operator/operator-security.sign_out_sub') }}</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="signOutOpen = false" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'signout' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sec-so-pw">{{ __('livewire/operator/operator-security.your_password') }}</label>
                            <x-password-field name="current_password" id="vy-sec-so-pw" autocomplete="current-password" :required="true" :label="__('livewire/operator/operator-security.field_password')" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="signOutOpen = false">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--danger">{{ __('livewire/operator/operator-security.sign_out_others') }}</button>
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
                            <h3 class="vy-sx-dialog-title" id="vy-dlg-revoke-t">{{ __('livewire/operator/operator-security.revoke_device_title') }}</h3>
                            <p class="vy-sx-dialog-sub">{{ __('livewire/operator/operator-security.revoke_device_sub') }}</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="revokeId = null" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'revoke' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sec-rv-pw">{{ __('livewire/operator/operator-security.your_password') }}</label>
                            <x-password-field name="current_password" id="vy-sec-rv-pw" autocomplete="current-password" :required="true" :label="__('livewire/operator/operator-security.field_password')" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="revokeId = null">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--danger">{{ __('livewire/operator/operator-security.revoke') }}</button>
                    </div>
                </form>
            </div>
        </div>

    </x-operator.shell>
</div>
