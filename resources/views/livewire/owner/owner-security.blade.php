{{-- Owner "SECURITY" (owner account area · Gate 2). Per-USER (auth-resolved) — password, 2FA, real active
     sessions, data export; the ONE primary-owner-only action is Deactivate (#42). Real state only — the
     protection list is DERIVED (no fake score); deferred items are honest "Soon". Writes are controller POSTs.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@php
    $lbl = 'display:block;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-faint);margin-bottom:7px;';
    $h2 = 'font-family:var(--vy-display);font-size:15px;font-weight:600;margin:0 0 4px;';
    $sub = 'font-size:12.5px;color:var(--vy-muted);margin:0 0 16px;';
    $err = 'font-size:11.5px;color:var(--vy-accent);display:block;margin-top:5px;';
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

        {{-- ══ Account protection — a REAL checklist derived from true state (NOT a fabricated score) ═══ --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <h2 style="{{ $h2 }}">Account protection</h2>
            <p style="{{ $sub }}">A quick, honest read of what’s protecting your account — no scores, just what’s on.</p>
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach ($checklist as $item)
                    <div style="display:flex;align-items:flex-start;gap:12px;">
                        @if ($item['done'])
                            <span style="flex:none;width:22px;height:22px;border-radius:50%;display:grid;place-items:center;background:rgba(34,120,60,.12);color:#227a3c;font-size:13px;">✓</span>
                        @else
                            <span style="flex:none;width:22px;height:22px;border-radius:50%;display:grid;place-items:center;background:rgba(224,146,30,.14);color:var(--vy-amber);font-size:13px;">!</span>
                        @endif
                        <div>
                            <div style="font-size:13.5px;font-weight:500;color:var(--vy-ink);">{{ $item['label'] }}</div>
                            <div style="font-size:12px;color:var(--vy-muted);">{{ $item['hint'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
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
                        <span style="font-size:11px;color:var(--vy-faint);">At least 8 characters.</span>
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
        @include('livewire.partials.two-factor-panel')

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
                                @if ($s['current']) <span style="font-size:10.5px;font-weight:600;color:#227a3c;background:rgba(34,120,60,.12);border-radius:20px;padding:2px 9px;margin-left:6px;">This device</span> @endif
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
                @foreach (['Passkeys (Face / Touch ID)', 'Trusted locations & login approval', 'Connected apps', 'Full sign-in history', 'New-device email alerts', 'Session timeout & theme'] as $soon)
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
                <h2 style="{{ $h2 }}color:var(--vy-accent);">Deactivate account</h2>
                <p style="{{ $sub }}">Pause your portal access. You’ll be signed out and won’t be able to sign in until it’s reactivated. Your statements and payout history are <strong>kept</strong> — nothing is deleted. To reactivate, contact your management team.</p>
                <form method="POST" action="{{ route('owner.security.deactivate') }}" onsubmit="return confirm('Pause your account? You will be signed out until it is reactivated.');" style="display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                    @csrf
                    <div>
                        <label style="{{ $lbl }}" for="de_pw">Confirm your password</label>
                        <input id="de_pw" type="password" name="current_password" class="vy-input" autocomplete="current-password" style="max-width:240px;">
                        @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="vy-btn" style="background:none;border:1px solid var(--vy-accent);color:var(--vy-accent);padding:10px 18px;margin-top:26px;">Deactivate</button>
                </form>
            </div>
        @endif

    </x-owner.shell>
</div>
