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
        ['icon' => 'shield', 'n' => $score['on'].'/'.$score['total'], 'label' => __('livewire/owner/owner-security.tile_protections_on'), 'href' => '#sx-boost'],
        ['icon' => 'plus', 'n' => $score['available'], 'label' => __('livewire/owner/owner-security.tile_points_available'), 'href' => '#sx-boost'],
        ['icon' => 'device', 'n' => count($trusted), 'label' => __('livewire/owner/owner-security.trusted_devices'), 'href' => '#sx-approval'],
        ['icon' => 'apps', 'n' => count($connectedApps), 'label' => __('livewire/owner/owner-security.connected_apps'), 'href' => '#sx-apps'],
    ];
    $pwFirst = ! $hasPassword && session()->has('password_status');
    // the revoke dialog's app name, filled in by Alpine
    $revokeAppNameHtml = '<span x-text="appRevoke ? appRevoke.name : \'\'"></span>';
@endphp
<div class="vy-sx" x-data="{ pwOpen: @js($errored === 'password' || $pwFirst), tfOff: @js($errored === '2fa-off'), pkRemove: @js($pkErrored), signOutOpen: @js($errored === 'signout'), revokeId: @js($erroredDevice), appRevoke: @js($erroredApp), approvalOpen: @js($errored === 'approval'), deactivateOpen: @js($errored === 'deactivate'), deleteOpen: @js($errored === 'deletion') }">
    <x-owner.shell active="security" :title="__('livewire/owner/owner-security.page_title')" :viewer="$viewer">
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
                    <h2 class="vy-sx-card-title" id="vy-sx-signin-t">{{ __('livewire/owner/owner-security.signing_in_heading') }}</h2>
                    <p class="vy-sx-card-sub">@if ($lastLogin) {{ __('livewire/owner/owner-security.signing_in_sub_last', ['when' => $lastLogin->diffForHumans()]) }} @else {{ __('livewire/owner/owner-security.signing_in_sub') }} @endif</p>

                    {{-- ⚠️⚠️ THE SET-A-PASSWORD PATH IS THIS SAME ROW: with no password there is nothing to confirm with, so the
                         dialog omits the current-password field only then, and the controller enforces the same split. --}}
                    <div class="vy-sx-block" id="sx-password">
                        <div class="vy-sx-row">
                            <div class="vy-sx-row-main">
                                <h3 class="vy-sx-row-title">{{ __('livewire/owner/owner-security.password_heading') }}</h3>
                                <span class="vy-sx-row-meta">@if (! $hasPassword) {{ __('livewire/owner/owner-security.password_none') }} @elseif ($passwordChanged) {{ __('livewire/owner/owner-security.password_changed', ['when' => $passwordChanged]) }} @else {{ __('livewire/owner/owner-security.password_not_recorded') }} @endif</span>
                            </div>
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="pwOpen = true">{{ $hasPassword ? __('livewire/owner/owner-security.change_button') : __('livewire/owner/owner-security.set_password_button') }}</button>
                        </div>
                    </div>

                    @include('livewire.partials.two-factor-panel')

                    {{-- PASSKEYS — the shared card (D24-2): one ceremony for both realms; only the route names differ --}}
                    <x-auth.passkeys :passkeys="$passkeys" options-route="owner.security.passkeys.options" store-route="owner.security.passkeys.store" />

                    {{-- LOGIN ALERTS (always on for an owner) · SESSION TIMEOUT (Part 3) — the same rows the back office shows --}}
                    <x-security.protection-rows :alerts="$alerts" :timeout="$timeout" :routes="['alerts' => null, 'timeout' => 'owner.security.session-timeout']" />
                </section>
            </div>

            {{-- ══ MIDDLE — the score, your data, deactivate ═════════════════════════════════════════════════════════ --}}
            <div class="vy-sx-col">
                <x-security.boost :score="$score" />

                {{-- YOUR DATA — a REAL owner-scoped export (#28) --}}
                <section class="vy-sx-card vy-sx-in" id="sx-data" aria-labelledby="vy-sx-data-t">
                    <h2 class="vy-sx-card-title" id="vy-sx-data-t">{{ __('livewire/owner/owner-security.data_heading') }}</h2>
                    <p class="vy-sx-card-sub">{{ __('livewire/owner/owner-security.data_sub') }}</p>
                    <div class="vy-sx-alert-acts">
                        <a href="{{ route('owner.security.export') }}" class="vy-sx-btn vy-sx-btn--soft"><x-security.icon name="download" /> {{ __('livewire/owner/owner-security.request_my_data') }}</a>
                    </div>
                </section>

                {{-- DEC-6 — REQUEST DELETION of this account (every owner-portal user, not only the primary owner): files a Privacy-desk request, erases nothing --}}
                <x-security.deletion-card :request="$deletionRequest" />

                {{-- DEACTIVATE — PRIMARY-OWNER-ONLY, reversible and non-destructive --}}
                @if ($isPrimaryOwner)
                    <section class="vy-sx-card vy-sx-in" id="sx-deactivate" aria-labelledby="vy-sx-deact-t">
                        <h2 class="vy-sx-card-title" id="vy-sx-deact-t">{{ __('livewire/owner/owner-security.deactivate_heading') }}</h2>
                        <p class="vy-sx-card-sub">{!! __('livewire/owner/owner-security.deactivate_sub') !!}</p>
                        <div class="vy-sx-alert-acts">
                            <button type="button" class="vy-sx-btn vy-sx-btn--line" @click="deactivateOpen = true"><x-security.icon name="pause" /> {{ __('livewire/owner/owner-security.deactivate_button') }}</button>
                        </div>
                    </section>
                @endif
            </div>

            {{-- ══ RIGHT — sign-in protection, sessions, apps ═══════════════════════════════════════════════════════ --}}
            <div class="vy-sx-col">
                <section class="vy-sx-card vy-sx-in" id="sx-approval" aria-labelledby="vy-sx-prot-t">
                    <h2 class="vy-sx-card-title" id="vy-sx-prot-t">{{ __('livewire/owner/owner-security.protection_heading') }}</h2>

                    {{-- LOGIN APPROVAL — a REAL hold on new devices; switching it re-asks for the password --}}
                    <div class="vy-sx-row">
                        <div class="vy-sx-row-main">
                            <h3 class="vy-sx-row-title">{{ __('livewire/owner/owner-security.login_approval') }}</h3>
                            <span class="vy-sx-row-meta">{{ __('livewire/owner/owner-security.login_approval_meta') }}</span>
                        </div>
                        @if (! $approval['on'] && ! $approval['available'])
                            <button type="button" class="vy-sx-switch" role="switch" aria-checked="false" aria-label="{{ __('livewire/owner/owner-security.login_approval') }}" aria-disabled="true" aria-describedby="vy-sx-approval-why"></button>
                        @else
                            <button type="button" class="vy-sx-switch" role="switch" aria-checked="{{ $approval['on'] ? 'true' : 'false' }}" aria-label="{{ __('livewire/owner/owner-security.login_approval') }}" @click="approvalOpen = true"></button>
                        @endif
                    </div>
                    @if (! $approval['available'])
                        <p class="vy-sx-note is-warn" id="vy-sx-approval-why">
                            @if ($approval['on'])
                                {{ __('livewire/owner/owner-security.approval_no_email_on') }}
                            @else
                                {{ __('livewire/owner/owner-security.approval_no_email_off') }}
                            @endif
                        </p>
                    @endif

                    <x-security.approvals :waiting="$waiting" route="owner.security.login-approval.decide" />

                    {{-- TRUSTED DEVICES — REAL skip-2FA devices; remove re-asks for the password --}}
                    <div class="vy-sx-row">
                        <div class="vy-sx-row-main">
                            <h3 class="vy-sx-row-title">{{ __('livewire/owner/owner-security.trusted_devices') }}</h3>
                            <span class="vy-sx-row-meta">{{ __('livewire/owner/owner-security.trusted_devices_meta') }}</span>
                        </div>
                    </div>
                    <ul class="vy-sx-sub-rows">
                        @forelse ($trusted as $d)
                            <li class="vy-sx-item">
                                <span class="vy-sx-ico"><x-security.icon name="device" /></span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $d['label'] }}@if ($d['current']) <span class="vy-sx-chip is-ok">{{ __('livewire/owner/owner-security.this_device') }}</span>@endif</span>
                                    <span class="vy-sx-row-meta">{{ $d['ip'] !== '' ? __('livewire/owner/owner-security.trusted_meta_ip', ['ip' => $d['ip'], 'last_used' => $d['last_used'] ?? '—', 'expires' => $d['expires'] ?? '—']) : __('livewire/owner/owner-security.trusted_meta', ['last_used' => $d['last_used'] ?? '—', 'expires' => $d['expires'] ?? '—']) }}</span>
                                </div>
                                <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="revokeId = '{{ $d['id'] }}'">{{ __('common.remove') }}</button>
                            </li>
                        @empty
                            <li class="vy-sx-empty">
                                <strong>{{ __('livewire/owner/owner-security.no_trusted_devices') }}</strong>
                                {{ $twoFactorOn ? __('livewire/owner/owner-security.no_trusted_2fa_on') : __('livewire/owner/owner-security.no_trusted_2fa_off') }}
                            </li>
                        @endforelse
                    </ul>
                </section>

                {{-- ACTIVE SESSIONS — the REAL sessions table; "sign out others" re-asks for the password --}}
                <section class="vy-sx-card vy-sx-in" id="sx-sessions" aria-labelledby="vy-sx-sess-t">
                    <h2 class="vy-sx-card-title" id="vy-sx-sess-t">{{ __('livewire/owner/owner-security.sessions_heading') }}</h2>
                    <p class="vy-sx-card-sub">{{ __('livewire/owner/owner-security.sessions_sub') }}</p>
                    <ul class="vy-sx-rows">
                        @forelse ($sessions as $s)
                            <li class="vy-sx-row">
                                <span class="vy-sx-ico"><x-security.icon name="monitor" /></span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $s['device'] }}@if ($s['current']) <span class="vy-sx-chip is-ok">{{ __('livewire/owner/owner-security.this_device') }}</span>@endif</span>
                                    <span class="vy-sx-row-meta">{!! __('livewire/owner/owner-security.session_meta', ['ip' => e($s['ip']), 'when' => e($s['last_active']->diffForHumans())]) !!}</span>
                                </div>
                            </li>
                        @empty
                            <li class="vy-sx-empty"><strong>{{ __('livewire/owner/owner-security.no_sessions') }}</strong></li>
                        @endforelse
                    </ul>
                    <div class="vy-sx-alert-acts">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="signOutOpen = true">{{ __('livewire/owner/owner-security.sign_out_others_all') }}</button>
                    </div>
                </section>

                {{-- CONNECTED APPS — REAL (OAuth #72): third-party apps you authorized, their scopes, a re-authed revoke --}}
                <section class="vy-sx-card vy-sx-in" id="sx-apps" aria-labelledby="vy-sx-apps-t">
                    <h2 class="vy-sx-card-title" id="vy-sx-apps-t">{{ __('livewire/owner/owner-security.connected_apps') }}</h2>
                    <p class="vy-sx-card-sub">{{ __('livewire/owner/owner-security.apps_sub') }}</p>
                    <ul class="vy-sx-rows">
                        @forelse ($connectedApps as $app)
                            <li class="vy-sx-row">
                                <span class="vy-sx-avatar" aria-hidden="true">{{ \App\Support\I18n\LocalText::upper(mb_substr($app['name'], 0, 2)) }}</span>
                                <div class="vy-sx-row-main">
                                    <span class="vy-sx-row-title">{{ $app['name'] }}</span>
                                    <span class="vy-sx-row-meta">
                                        @foreach ($app['scopes'] as $i => $scopeId)<span class="vy-sx-chip" title="{{ $app['scope_labels'][$i] ?? '' }}">{{ $scopeId }}</span> @endforeach
                                        @if ($app['authorized_at']) {{ __('livewire/owner/owner-security.authorized_at', ['when' => $app['authorized_at']]) }}@endif
                                    </span>
                                </div>
                                <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="appRevoke = @js(['id' => $app['client_id'], 'name' => $app['name']])">{{ __('livewire/owner/owner-security.revoke') }}</button>
                            </li>
                        @empty
                            <li class="vy-sx-empty"><strong>{{ __('livewire/owner/owner-security.no_connected_apps') }}</strong> {{ __('livewire/owner/owner-security.no_connected_apps_hint') }}</li>
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
                        <h3 class="vy-sx-dialog-title" id="vy-sx-pw-t">@if ($hasPassword) {{ __('livewire/owner/owner-security.change_password_title') }} @else {{ __('livewire/owner/owner-security.set_password_title') }} @endif</h3>
                        <p class="vy-sx-dialog-sub">{{ $hasPassword ? __('livewire/owner/owner-security.change_password_sub') : __('livewire/owner/owner-security.set_password_sub') }}</p>
                    </div>
                    <button type="button" class="vy-sx-x" @click="pwOpen = false" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
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
                                <label class="vy-sx-label" for="vy-sx-cur">{{ __('livewire/owner/owner-security.current_password') }}</label>
                                <x-password-field name="current_password" id="vy-sx-cur" autocomplete="current-password" :required="true" :label="__('livewire/owner/owner-security.field_current_password')" autofocus />
                            </div>
                        @endif
                        <div class="vy-sx-pair">
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sx-new">{{ __('livewire/owner/owner-security.new_password') }}</label>
                                <x-password-field name="password" id="vy-sx-new" autocomplete="new-password" :required="true" :label="__('livewire/owner/owner-security.field_new_password')" :autofocus="! $hasPassword" />
                            </div>
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sx-conf">{{ __('livewire/owner/owner-security.confirm_new_password') }}</label>
                                <x-password-field name="password_confirmation" id="vy-sx-conf" autocomplete="new-password" :required="true" :label="__('livewire/owner/owner-security.field_password_confirmation')" />
                            </div>
                        </div>
                        <p class="vy-sx-hint">{{ \App\Support\Auth\PasswordPolicy::hint() }}</p>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="pwOpen = false">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--grad">@if ($hasPassword) {{ __('livewire/owner/owner-security.update_password') }} @else {{ __('livewire/owner/owner-security.set_password_button') }} @endif</button>
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
                            <h3 class="vy-sx-dialog-title" id="vy-sx-appr-t">{{ $approval['on'] ? __('livewire/owner/owner-security.approval_off_title') : __('livewire/owner/owner-security.approval_on_title') }}</h3>
                            <p class="vy-sx-dialog-sub">{{ $approval['on'] ? __('livewire/owner/owner-security.approval_off_sub') : __('livewire/owner/owner-security.approval_on_sub') }}</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="approvalOpen = false" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'approval' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sx-appr-pw">{{ __('livewire/owner/owner-security.your_password') }}</label>
                            <x-password-field name="current_password" id="vy-sx-appr-pw" autocomplete="current-password" :required="true" :label="__('livewire/owner/owner-security.field_password')" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="approvalOpen = false">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--grad">{{ $approval['on'] ? __('livewire/owner/owner-security.turn_off') : __('livewire/owner/owner-security.turn_on') }}</button>
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
                            <h3 class="vy-sx-dialog-title" id="vy-sx-so-t">{{ __('livewire/owner/owner-security.sign_out_others_all') }}</h3>
                            <p class="vy-sx-dialog-sub">{{ __('livewire/owner/owner-security.sign_out_sub') }}</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="signOutOpen = false" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'signout' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sx-so-pw">{{ __('livewire/owner/owner-security.your_password') }}</label>
                            <x-password-field name="current_password" id="vy-sx-so-pw" autocomplete="current-password" :required="true" :label="__('livewire/owner/owner-security.field_password')" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="signOutOpen = false">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--danger">{{ __('livewire/owner/owner-security.sign_out_others') }}</button>
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
                            <h3 class="vy-sx-dialog-title" id="vy-sx-rv-t">{{ __('livewire/owner/owner-security.remove_device_title') }}</h3>
                            <p class="vy-sx-dialog-sub">{{ __('livewire/owner/owner-security.remove_device_sub') }}</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="revokeId = null" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'revoke' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sx-rv-pw">{{ __('livewire/owner/owner-security.your_password') }}</label>
                            <x-password-field name="current_password" id="vy-sx-rv-pw" autocomplete="current-password" :required="true" :label="__('livewire/owner/owner-security.field_password')" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="revokeId = null">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--danger">{{ __('common.remove') }}</button>
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
                            <h3 class="vy-sx-dialog-title" id="vy-sx-app-t">{!! __('livewire/owner/owner-security.revoke_app_title', ['app' => $revokeAppNameHtml]) !!}</h3>
                            <p class="vy-sx-dialog-sub">{{ __('livewire/owner/owner-security.revoke_app_sub') }}</p>
                        </div>
                        <button type="button" class="vy-sx-x" @click="appRevoke = null" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                    </div>
                    <div class="vy-sx-dialog-body">
                        @if ($errored === 'app-revoke' && $errors->any())
                            <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sx-app-pw">{{ __('livewire/owner/owner-security.your_password') }}</label>
                            <x-password-field name="current_password" id="vy-sx-app-pw" autocomplete="current-password" :required="true" :label="__('livewire/owner/owner-security.field_password')" autofocus />
                        </div>
                    </div>
                    <div class="vy-sx-dialog-foot">
                        <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="appRevoke = null">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-sx-btn vy-sx-btn--danger">{{ __('livewire/owner/owner-security.revoke') }}</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($deletionRequest === null)
            <x-security.deletion-dialog :action="route('owner.security.deletion-request')" :has-password="$deletionHasPassword" :errored="$errored" />
        @endif

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
                                <h3 class="vy-sx-dialog-title" id="vy-sx-de-t">{{ __('livewire/owner/owner-security.deactivate_title') }}</h3>
                                <p class="vy-sx-dialog-sub">{{ __('livewire/owner/owner-security.deactivate_dialog_sub') }}</p>
                            </div>
                            <button type="button" class="vy-sx-x" @click="deactivateOpen = false" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                        </div>
                        <div class="vy-sx-dialog-body">
                            @if ($errored === 'deactivate' && $errors->any())
                                <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                            @endif
                            <div class="vy-sx-field">
                                <label class="vy-sx-label" for="vy-sx-de-pw">{{ __('livewire/owner/owner-security.your_password') }}</label>
                                <x-password-field name="current_password" id="vy-sx-de-pw" autocomplete="current-password" :required="true" :label="__('livewire/owner/owner-security.field_password')" autofocus />
                            </div>
                        </div>
                        <div class="vy-sx-dialog-foot">
                            <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="deactivateOpen = false">{{ __('livewire/owner/owner-security.keep_my_account') }}</button>
                            <button type="submit" class="vy-sx-btn vy-sx-btn--danger">{{ __('livewire/owner/owner-security.deactivate_button') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </x-owner.shell>
</div>
