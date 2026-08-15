{{-- Owner "SECURITY" (owner account area · Gate 2). Per-USER (auth-resolved) — password, 2FA, real active
     sessions, data export; the ONE primary-owner-only action is Deactivate (#42). Real state only — the
     score + protection list are a REAL /100 DERIVED from true protections (never fabricated); deferred items are honest "Soon". Writes are controller POSTs.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@php
    $lbl = 'display:block;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-faint);margin-bottom:7px;';
    $h2 = 'font-family:var(--vy-display);font-size:15px;font-weight:600;margin:0 0 4px;';
    $sub = 'font-size:12.5px;color:var(--vy-muted);margin:0 0 16px;';
    $err = 'font-size:11.5px;color:var(--vy-accent-text);display:block;margin-top:5px;';
    $btn = 'background:var(--vy-grad);color:#fff;padding:11px 22px;';
    $ghost = 'background:rgba(255,255,255,.6);border:1px solid var(--vy-glass-border);color:var(--vy-ink);padding:9px 16px;';
@endphp
<div>
    <x-owner.shell active="security" title="Security" :viewer="$viewer">

        @if (session('owner_notice'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px;">
                <div style="font-size:13.5px;color:var(--vy-ink);">{{ session('owner_notice') }}</div>
            </div>
        @endif
        @if (session('password_status'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px;">
                <div style="font-size:13.5px;color:var(--vy-ink);">{{ session('password_status') }}</div>
            </div>
        @endif

        {{-- ══ Account security score — a REAL score DERIVED from true protections (password 40 + 2FA 40 +
             recovery 20 = /100), never a fabricated number. The 4 stats + Boost are all real (#39). ═══════ --}}
        @php $circ = 326.726; $ringOffset = $circ * (1 - min(100, max(0, $score['num'])) / 100); @endphp
        <div class="vy-card vy-fade" style="margin-bottom:16px;display:flex;align-items:center;gap:22px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:18px;flex:1;min-width:280px;">
                <div style="position:relative;width:112px;height:112px;flex-shrink:0;">
                    <svg width="112" height="112" viewBox="0 0 120 120">
                        <defs><linearGradient id="vysecGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#F0A57A"/><stop offset="1" stop-color="#C2410C"/></linearGradient></defs>
                        <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(120,70,40,.12)" stroke-width="10"/>
                        <circle cx="60" cy="60" r="52" fill="none" stroke="url(#vysecGrad)" stroke-width="10" stroke-linecap="round" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $ringOffset }}" transform="rotate(-90 60 60)"/>
                    </svg>
                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <span style="font-family:var(--vy-display);font-weight:600;font-size:29px;line-height:1;color:var(--vy-ink);font-variant-numeric:tabular-nums;">{{ $score['num'] }}</span>
                        <span style="font-size:9px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--vy-faint);margin-top:3px;">of 100</span>
                    </div>
                </div>
                <div style="min-width:0;">
                    <div style="display:inline-flex;align-items:center;gap:7px;padding:3px 11px 3px 9px;border-radius:30px;background:linear-gradient(rgba(217,102,46,.12),rgba(217,102,46,.12)),rgba(255,252,248,.92);margin-bottom:8px;"><span style="width:7px;height:7px;border-radius:50%;background:var(--vy-accent);box-shadow:0 0 0 3px rgba(232,112,58,.18);"></span><span style="font-size:11px;font-weight:600;color:#B4400F;">{{ $score['tier'] }}</span></div>
                    <div style="font-family:var(--vy-display);font-weight:600;font-size:20px;letter-spacing:-.02em;line-height:1.15;">Account security</div>
                    <div style="font-size:12.5px;color:var(--vy-muted);margin-top:3px;max-width:340px;line-height:1.45;">{{ $score['caption'] }}</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;flex:1.4;min-width:320px;">
                @php $stat = 'display:flex;flex-direction:column;align-items:flex-start;padding:13px 14px;border-radius:15px;background:rgba(255,255,255,.55);border:1px solid var(--vy-hairline);box-sizing:border-box;'; $statv = 'font-family:var(--vy-display);font-weight:600;font-size:22px;letter-spacing:-.02em;font-variant-numeric:tabular-nums;line-height:1;'; $statl = 'font-size:11px;color:var(--vy-muted);margin-top:4px;'; @endphp
                <div style="{{ $stat }}"><span style="{{ $statv }}">{{ $score['on'] }}/{{ $score['total'] }}</span><span style="{{ $statl }}">Protections on</span></div>
                <div style="{{ $stat }}"><span style="{{ $statv }}">{{ $score['to_full'] }}</span><span style="{{ $statl }}">Points to full</span></div>
                <div style="{{ $stat }}"><span style="{{ $statv }}">{{ $score['sessions'] }}</span><span style="{{ $statl }}">Active sessions</span></div>
                <div style="{{ $stat }}"><span style="{{ $statv }}">{{ $score['two_factor'] ? 'On' : 'Off' }}</span><span style="{{ $statl }}">Two-factor</span></div>
            </div>
        </div>

        {{-- ══ Boost your score — the REAL remaining protections; "Enable" jumps to the real control ══════ --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <h2 style="{{ $h2 }}">Boost your score</h2>
            <p style="{{ $sub }}">{{ $score['on'] }} of {{ $score['total'] }} protections on.</p>
            @if (count($score['boost']) > 0)
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach ($score['boost'] as $b)
                        <div style="display:flex;align-items:center;gap:12px;padding:11px 13px;border-radius:14px;background:rgba(255,255,255,.5);border:1px solid var(--vy-glass-border);">
                            <span style="flex:1;min-width:0;"><span style="display:block;font-size:13px;font-weight:500;color:var(--vy-ink);">{{ $b['label'] }}</span><span style="display:block;font-size:11.5px;color:var(--vy-muted);margin-top:1px;">{{ $b['desc'] }}</span></span>
                            <span style="font-size:11.5px;font-weight:600;color:#B4400F;flex-shrink:0;">{{ $b['pts'] }}</span>
                            <a href="#two-factor" class="vy-btn" style="{{ $btn }}padding:7px 14px;font-size:12px;text-decoration:none;flex-shrink:0;">Enable</a>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="padding:13px 16px;border-radius:14px;background:rgba(217,102,46,.1);border:1px solid rgba(232,112,58,.28);display:flex;align-items:center;gap:11px;">
                    <span style="width:30px;height:30px;border-radius:50%;background:var(--vy-grad);color:#fff;display:grid;place-items:center;flex-shrink:0;">✓</span>
                    <span style="font-size:13px;font-weight:500;color:#B4400F;">All protections are on — your account is fully secured.</span>
                </div>
            @endif
        </div>

        {{-- ══ Change password — self-only, current-password-verified ══════════════════════════════════ --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <h2 style="{{ $h2 }}">Change password</h2>
            <p style="{{ $sub }}">Enter your current password to confirm it’s you. Other devices with “remember me” are signed out.</p>
            <form method="POST" action="{{ route('owner.security.password') }}">
                @csrf
                <div style="display:grid;gap:14px;max-width:380px;">
                    <div>
                        <label style="{{ $lbl }}" for="cur_pw">Current password</label>
                        <input id="cur_pw" type="password" name="current_password" class="vy-input" autocomplete="current-password" required>
                        @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="{{ $lbl }}" for="new_pw">New password</label>
                        <input id="new_pw" type="password" name="password" class="vy-input" autocomplete="new-password" required>
                        @error('password') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        <span style="font-size:11px;color:var(--vy-faint);">{{ \App\Support\Auth\PasswordPolicy::hint() }}</span>
                    </div>
                    <div>
                        <label style="{{ $lbl }}" for="conf_pw">Confirm new password</label>
                        <input id="conf_pw" type="password" name="password_confirmation" class="vy-input" autocomplete="new-password" required>
                    </div>
                </div>
                <div style="margin-top:16px;display:flex;justify-content:flex-end;">
                    <button type="submit" class="vy-btn" style="{{ $btn }}">Update password</button>
                </div>
            </form>
        </div>

        {{-- ══ Two-factor — REAL TOTP (shared panel; posts to owner.security.2fa.*) ═════════════════════ --}}
        <div id="two-factor">
            @include('livewire.partials.two-factor-panel')
        </div>

        {{-- ══ Active sessions — the REAL sessions table; "sign out others" re-auths with the password ══ --}}
        <div class="vy-card vy-fade" style="margin:16px 0;">
            <h2 style="{{ $h2 }}">Active sessions</h2>
            <p style="{{ $sub }}">Where you’re signed in right now.
                @if ($lastLogin) Last sign-in {{ $lastLogin->diffForHumans() }}. @endif
            </p>
            <div style="display:flex;flex-direction:column;gap:2px;margin-bottom:16px;">
                @forelse ($sessions as $s)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid var(--vy-hairline);">
                        <div>
                            <div style="font-size:13.5px;font-weight:500;color:var(--vy-ink);">
                                {{ $s['device'] }}
                                @if ($s['current']) <span style="font-size:10.5px;font-weight:600;color:var(--vy-green-text);background:rgba(34,120,60,.12);border-radius:20px;padding:2px 9px;margin-left:6px;">This device</span> @endif
                            </div>
                            <div style="font-size:11.5px;color:var(--vy-muted);">{{ $s['ip'] }} · active {{ $s['last_active']->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div style="font-size:13px;color:var(--vy-muted);">No active sessions recorded.</div>
                @endforelse
            </div>
            <form method="POST" action="{{ route('owner.security.sessions.signout') }}" style="display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                @csrf
                <div>
                    <label style="{{ $lbl }}" for="so_pw">Confirm your password to sign out other sessions</label>
                    <input id="so_pw" type="password" name="current_password" class="vy-input" autocomplete="current-password" style="max-width:240px;">
                    @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="vy-btn" style="{{ $ghost }}margin-top:26px;">Sign out all other sessions</button>
            </form>
        </div>

        {{-- ══ Trusted devices (Wave B · GATE 4) — REAL skip-2FA devices; remove re-auths with the password ══ --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <h2 style="{{ $h2 }}">Trusted devices</h2>
            <p style="{{ $sub }}">Browsers you’ve chosen to trust skip the two-factor code at sign-in. Remove any you don’t recognise — it’ll need a code again next time. Tick “Trust this device” at the code screen to add the one you’re on.</p>
            @if (count($trustedDevices) > 0)
                <div style="display:flex;flex-direction:column;gap:2px;margin-top:6px;">
                    @foreach ($trustedDevices as $d)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid var(--vy-hairline);">
                            <div>
                                <div style="font-size:13.5px;font-weight:500;color:var(--vy-ink);">
                                    {{ $d['label'] }}
                                    @if ($d['current']) <span style="font-size:10.5px;font-weight:600;color:var(--vy-green-text);background:rgba(34,120,60,.12);border-radius:20px;padding:2px 9px;margin-left:6px;">This device</span> @endif
                                </div>
                                <div style="font-size:11.5px;color:var(--vy-muted);">@if ($d['ip']){{ $d['ip'] }} · @endif last used {{ $d['last_used'] }} · trust expires {{ $d['expires'] }}</div>
                            </div>
                            <form method="POST" action="{{ route('owner.security.trusted-devices.revoke') }}" x-data="{ open: false }" style="flex-shrink:0;">
                                @csrf
                                <input type="hidden" name="device_id" value="{{ $d['id'] }}">
                                <button type="button" @click="open = !open" class="vy-btn" style="{{ $ghost }}font-size:12px;padding:6px 12px;">Remove</button>
                                <div x-show="open" x-cloak style="display:flex;align-items:center;gap:8px;margin-top:8px;">
                                    <input type="password" name="current_password" class="vy-input" autocomplete="current-password" placeholder="Your password" style="max-width:180px;font-size:12.5px;">
                                    <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12px;padding:6px 12px;">Confirm</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
                @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
            @else
                <div style="font-size:13px;color:var(--vy-muted);margin-top:6px;">No trusted devices — every sign-in asks for a two-factor code.</div>
            @endif
        </div>

        {{-- ══ Passkeys (Wave B · GATE 4) — REAL WebAuthn; register runs the browser ceremony, remove re-auths ══ --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;" x-data="vyPasskeys()">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div style="max-width:60ch;">
                    <h2 style="{{ $h2 }}">Passkeys</h2>
                    <p style="{{ $sub }}">Sign in with Face ID, Touch ID or your device PIN instead of a password — a passkey never leaves your device. Add one per device you use.</p>
                </div>
                <button type="button" class="vy-btn" @click="register()" x-bind:disabled="busy" style="flex-shrink:0;">
                    <span x-show="!busy">Add a passkey</span><span x-show="busy" x-cloak>Working…</span>
                </button>
            </div>
            <p x-show="msg" x-cloak x-text="msg" style="font-size:12.5px;margin-top:10px;color:var(--vy-accent-text);"></p>
            @if (count($passkeys) > 0)
                <div style="display:flex;flex-direction:column;gap:2px;margin-top:10px;">
                    @foreach ($passkeys as $p)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid var(--vy-hairline);">
                            <div>
                                <div style="font-size:13.5px;font-weight:500;color:var(--vy-ink);">{{ $p['label'] }}</div>
                                <div style="font-size:11.5px;color:var(--vy-muted);">Last used {{ $p['last_used'] }}</div>
                            </div>
                            <form method="POST" action="{{ route('owner.security.passkeys.remove', $p['id']) }}" x-data="{ open: false }" style="flex-shrink:0;">
                                @csrf
                                <button type="button" @click="open = !open" class="vy-btn" style="{{ $ghost }}font-size:12px;padding:6px 12px;">Remove</button>
                                <div x-show="open" x-cloak style="display:flex;align-items:center;gap:8px;margin-top:8px;">
                                    <input type="password" name="current_password" class="vy-input" autocomplete="current-password" placeholder="Your password" style="max-width:180px;font-size:12.5px;">
                                    <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12px;padding:6px 12px;">Confirm</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
                @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
            @else
                <div style="font-size:13px;color:var(--vy-muted);margin-top:6px;">No passkeys yet. Add one to sign in without a password on this device.</div>
            @endif
        </div>
        <script>
            function vyPasskeys() {
                return {
                    busy: false, msg: '',
                    token: document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value,
                    async register() {
                        if (!window.PublicKeyCredential) { this.msg = 'This browser doesn’t support passkeys.'; return; }
                        this.busy = true; this.msg = '';
                        try {
                            const optRes = await fetch('{{ route('owner.security.passkeys.options') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': this.token, 'Accept': 'application/json' } });
                            if (!optRes.ok) throw new Error('options');
                            const options = PublicKeyCredential.parseCreationOptionsFromJSON(await optRes.json());
                            const cred = await navigator.credentials.create({ publicKey: options });
                            const res = await fetch('{{ route('owner.security.passkeys.store') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': this.token, 'Content-Type': 'application/json', 'X-Passkey-Label': (navigator.userAgentData?.platform || 'This device') },
                                body: JSON.stringify(cred.toJSON()),
                            });
                            if (res.ok) { location.reload(); } else { this.msg = 'That passkey could not be registered. Try again.'; }
                        } catch (e) { this.msg = 'Passkey registration was cancelled or failed.'; }
                        this.busy = false;
                    },
                };
            }
        </script>

        {{-- ══ Login approval (Wave B · GATE 4) — REAL email approval for new devices; toggle re-auths ══ --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div style="max-width:60ch;">
                    <h2 style="{{ $h2 }}">Login approval for new devices</h2>
                    <p style="{{ $sub }}">When on, signing in from a device you haven’t trusted is held until you approve it from a link we email you. A trusted device skips this, and you can turn it off here — so it can’t lock you out.</p>
                </div>
                <span style="font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;border-radius:20px;padding:4px 11px;{{ $loginApprovalOn ? 'color:var(--vy-green-text);background:rgba(34,120,60,.12);' : 'color:var(--vy-faint);background:rgba(0,0,0,.04);' }}">{{ $loginApprovalOn ? 'On' : 'Off' }}</span>
            </div>
            <form method="POST" action="{{ route('owner.security.login-approval') }}" style="display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-top:12px;">
                @csrf
                <input type="hidden" name="enabled" value="{{ $loginApprovalOn ? '0' : '1' }}">
                <div>
                    <label style="{{ $lbl }}" for="la_pw">Confirm your password to {{ $loginApprovalOn ? 'turn off' : 'turn on' }}</label>
                    <input id="la_pw" type="password" name="current_password" class="vy-input" autocomplete="current-password" style="max-width:240px;">
                    @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="vy-btn" style="{{ $loginApprovalOn ? $ghost : '' }}margin-top:26px;">{{ $loginApprovalOn ? 'Turn off' : 'Turn on login approval' }}</button>
            </form>
        </div>

        {{-- ══ Connected apps — REAL (Wave D · OAuth): third-party apps you authorized + their scopes + revoke ══ --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <h2 style="{{ $h2 }}">Connected apps</h2>
            <p style="{{ $sub }}">Third-party apps you’ve given read-only access to your account. Each can read only the data listed — never your guests’ contact details, door codes or Wi-Fi — and only your own. Revoke any anytime; access stops immediately.</p>

            @forelse ($connectedApps as $app)
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:12px 0;border-bottom:1px solid var(--vy-hairline);">
                    <div style="min-width:0;">
                        <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink);">{{ $app['name'] }}</div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin:7px 0 5px;">
                            @foreach ($app['scopes'] as $i => $scopeId)
                                <span title="{{ $app['scope_labels'][$i] ?? '' }}" style="font-size:10.5px;font-weight:600;color:var(--vy-accent-text);background:rgba(194,65,12,.08);border:1px solid var(--vy-accent-border,rgba(194,65,12,.2));border-radius:20px;padding:2px 9px;font-family:ui-monospace,monospace;">{{ $scopeId }}</span>
                            @endforeach
                        </div>
                        <div style="font-size:11.5px;color:var(--vy-muted);">@if ($app['authorized_at']) Authorized {{ $app['authorized_at'] }} @else Authorized @endif</div>
                    </div>
                    <form method="POST" action="{{ route('owner.security.connected-apps.revoke') }}" x-data="{ open: false }" style="flex-shrink:0;">
                        @csrf
                        <input type="hidden" name="client_id" value="{{ $app['client_id'] }}">
                        <button type="button" @click="open = !open" class="vy-btn" style="{{ $ghost }}font-size:12px;padding:6px 12px;">Revoke</button>
                        <div x-show="open" x-cloak style="display:flex;align-items:center;gap:8px;margin-top:8px;">
                            <input type="password" name="current_password" class="vy-input" autocomplete="current-password" placeholder="Your password" style="max-width:180px;font-size:12.5px;">
                            <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12px;padding:6px 12px;">Confirm</button>
                        </div>
                    </form>
                </div>
            @empty
                <div style="font-size:12.5px;color:var(--vy-muted);padding:6px 0;">No connected apps — third-party apps you authorize will appear here.</div>
            @endforelse
            @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
        </div>

        {{-- ══ Your data — a REAL owner-scoped export ══════════════════════════════════════════════════ --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <h2 style="{{ $h2 }}">Your data</h2>
            <p style="{{ $sub }}">Download a copy of your data — your profile plus the statements, payouts and units your account covers.</p>
            <a href="{{ route('owner.security.export') }}" class="vy-btn" style="{{ $ghost }}display:inline-block;text-decoration:none;">Download my data (JSON)</a>
        </div>

        {{-- ══ Coming soon — honest, never a fake toggle ═══════════════════════════════════════════════ --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <h2 style="{{ $h2 }}margin-bottom:12px;">Coming soon</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                @foreach (['New-device email alerts', 'Full sign-in history', 'Session timeout'] as $soon)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 14px;border:1px solid var(--vy-hairline);border-radius:10px;">
                        <span style="font-size:12.5px;color:var(--vy-muted);">{{ $soon }}</span>
                        <span style="font-size:10.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--vy-faint);background:rgba(0,0,0,.04);padding:3px 9px;border-radius:20px;">Soon</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══ Deactivate — PRIMARY-OWNER-ONLY, reversible + non-destructive ═══════════════════════════ --}}
        @if ($isPrimaryOwner)
            <div class="vy-card vy-fade" style="border:1px solid rgba(194,65,12,.25);">
                <h2 style="{{ $h2 }}color:var(--vy-accent-text);">Deactivate account</h2>
                <p style="{{ $sub }}">Pause your portal access. You’ll be signed out and won’t be able to sign in until it’s reactivated. Your statements and payout history are <strong>kept</strong> — nothing is deleted. To reactivate, contact your management team.</p>
                <form method="POST" action="{{ route('owner.security.deactivate') }}" onsubmit="return confirm('Pause your account? You will be signed out until it is reactivated.');" style="display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                    @csrf
                    <div>
                        <label style="{{ $lbl }}" for="de_pw">Confirm your password</label>
                        <input id="de_pw" type="password" name="current_password" class="vy-input" autocomplete="current-password" style="max-width:240px;">
                        @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="vy-btn" style="background:none;border:1px solid var(--vy-accent);color:var(--vy-accent-text);padding:10px 18px;margin-top:26px;">Deactivate</button>
                </form>
            </div>
        @endif

    </x-owner.shell>
</div>
