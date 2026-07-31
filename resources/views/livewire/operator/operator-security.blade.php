{{-- Operator SECURITY (Auth) — #104b GATE 1 exact-match to Operator_Security.html on REAL data. Reuses the #66
     security (change-password + 2FA/TOTP + Active sessions [ActiveSessions] + Trusted devices [TrustedDeviceService],
     the SAME the owner has), plus a REAL IP-allowlist (genuinely enforced by EnforceIpAllowlist), an honest
     "No apps connected" (no operator OAuth catalog), and a self-scoped data export. RAW-BLADE RULE: every `@`
     follows a space, newline or tag. --}}
<div x-data="{ ipOpen: false, exportOpen: false, signOutOpen: false, revokeId: null }">
    <x-operator.shell active="security" title="Security" :viewer="$viewer">

        @if (session('security_notice'))
            <div class="vy-flash" style="margin-bottom:14px;background:rgba(26,127,75,.10);color:#1a7f4b;border:1px solid rgba(26,127,75,.2);border-radius:12px;padding:11px 15px;font-size:13px;font-weight:500">{{ session('security_notice') }}</div>
        @endif
        @if (session('security_error') || $errors->any())
            <div class="vy-flash is-err" style="margin-bottom:14px;border-radius:12px;padding:11px 15px;font-size:13px;font-weight:500">{{ session('security_error') ?? $errors->first() }}</div>
        @endif

        {{-- ── OVERVIEW — "Account security · Protected" + the four real counts ─────────────────────────── --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <h2 class="vy-card-title" style="margin:0;">Account security</h2>
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#1a7f4b;background:rgba(26,127,75,.10);border-radius:999px;padding:3px 11px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
                    Protected
                </span>
            </div>
            <div class="vy-dash-kpis" style="margin-top:14px;">
                <a href="#sec-sessions" class="vy-dash-kpi" style="text-decoration:none;color:inherit;"><div class="vy-dk-label">Active sessions</div><div class="vy-dk-val">{{ $counts['sessions'] }}</div></a>
                <a href="#sec-apps" class="vy-dash-kpi" style="text-decoration:none;color:inherit;"><div class="vy-dk-label">Connected apps</div><div class="vy-dk-val">{{ $counts['apps'] }}</div></a>
                <a href="#sec-trusted" class="vy-dash-kpi" style="text-decoration:none;color:inherit;"><div class="vy-dk-label">Trusted devices</div><div class="vy-dk-val">{{ $counts['trusted'] }}</div></a>
                <a href="#sec-ip" class="vy-dash-kpi" style="text-decoration:none;color:inherit;"><div class="vy-dk-label">IP rules</div><div class="vy-dk-val">{{ $counts['ip'] }}</div></a>
            </div>
        </div>

        {{-- ── CHANGE PASSWORD (real #66) ──────────────────────────────────────────────────────────────── --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;max-width:560px;">
            <h2 class="vy-card-title" style="margin-bottom:4px;">Change password</h2>
            <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 12px;">Use a long, unique passphrase. You’ll stay signed in on this device.</p>
            <form method="POST" action="{{ route('app.security.password') }}">
                @csrf
                <input type="password" name="current_password" class="vy-input" placeholder="Current password" autocomplete="current-password" required style="margin-bottom:8px;">
                <input type="password" name="password" class="vy-input" placeholder="New password" autocomplete="new-password" required style="margin-bottom:8px;">
                <input type="password" name="password_confirmation" class="vy-input" placeholder="Confirm new password" autocomplete="new-password" required style="margin-bottom:12px;">
                <button type="submit" class="vy-btn vy-btn--accent">Update password</button>
            </form>
        </div>

        {{-- ── TWO-FACTOR (real #66 shared panel) ──────────────────────────────────────────────────────── --}}
        @include('livewire.partials.two-factor-panel')

        {{-- ── IP ALLOWLIST (real — EnforceIpAllowlist genuinely restricts /app) ───────────────────────── --}}
        <div id="sec-ip" class="vy-card vy-fade" style="margin-bottom:16px;max-width:560px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <h2 class="vy-card-title" style="margin:0;">IP allowlist</h2>
                @can('settings.manage')
                    <button type="button" class="vy-btn vy-btn--ghost" style="font-size:12px;padding:5px 12px;" @click="ipOpen = true">Add IP</button>
                @endcan
            </div>
            <p class="vy-line-sub" style="font-size:12px;margin:4px 0 10px;">When set, only these addresses can reach the admin panel. Leave empty for no restriction.</p>
            @forelse ($ipRules as $r)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 0;border-top:1px solid var(--vy-hairline);font-size:13px;">
                    <div><strong>{{ $r['ip'] }}</strong>@if ($r['label'] !== '')<span class="vy-muted" style="font-size:12px;"> · {{ $r['label'] }}</span>@endif</div>
                    @can('settings.manage')
                        <form method="POST" action="{{ route('app.security.ip-rules.remove', $r['id']) }}" onsubmit="return confirm('Remove {{ $r['ip'] }} from the allowlist?');" style="margin:0;">
                            @csrf @method('DELETE')
                            <button type="submit" class="vy-btn vy-btn--ghost" style="font-size:12px;padding:4px 10px;">Remove</button>
                        </form>
                    @endcan
                </div>
            @empty
                <div style="padding:10px 0;border-top:1px solid var(--vy-hairline);">
                    <div style="font-size:13.5px;color:var(--vy-muted);">No IP restrictions</div>
                    <div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px;">Anyone with a valid login can reach the admin panel from any network.</div>
                </div>
            @endforelse
        </div>

        {{-- ── ACTIVE SESSIONS (real #66) ──────────────────────────────────────────────────────────────── --}}
        <div id="sec-sessions" class="vy-card vy-fade" style="margin-bottom:16px;max-width:560px;">
            <h2 class="vy-card-title" style="margin-bottom:6px;">Active sessions &amp; devices</h2>
            @forelse ($sessions as $sess)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 0;border-top:1px solid var(--vy-hairline);font-size:13px;">
                    <div><strong>{{ $sess['device'] }}</strong>@if ($sess['current']) <span class="vy-tag tone-confirmed" style="font-size:10px;">This device</span>@endif<br><span class="vy-muted" style="font-size:12px;">{{ $sess['ip'] }} · {{ $sess['last_active']->diffForHumans() }}</span></div>
                </div>
            @empty
                <div class="vy-empty" style="padding:14px 0;">No other active sessions.</div>
            @endforelse
            @if (count($sessions) > 1)
                <button type="button" class="vy-btn vy-btn--ghost" style="margin-top:12px;width:100%;" @click="signOutOpen = true">Sign out of all other devices</button>
            @endif
        </div>

        {{-- ── TRUSTED DEVICES (real #66 — skip-2FA for 30 days; revoke) ────────────────────────────────── --}}
        <div id="sec-trusted" class="vy-card vy-fade" style="margin-bottom:16px;max-width:560px;">
            <h2 class="vy-card-title" style="margin-bottom:4px;">Trusted devices</h2>
            <p class="vy-line-sub" style="font-size:12px;margin:0 0 10px;">Devices that skip the 2FA prompt for 30 days. Revoke any you don’t recognise.</p>
            @forelse ($trusted as $d)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 0;border-top:1px solid var(--vy-hairline);font-size:13px;">
                    <div><strong>{{ $d['label'] }}</strong>@if ($d['current']) <span class="vy-tag tone-confirmed" style="font-size:10px;">This device</span>@endif@if ($d['last_used'])<br><span class="vy-muted" style="font-size:12px;">Last used {{ $d['last_used'] }}</span>@endif</div>
                    <button type="button" class="vy-btn vy-btn--ghost" style="font-size:12px;padding:4px 10px;" @click="revokeId = '{{ $d['id'] }}'">Revoke</button>
                </div>
            @empty
                <div style="padding:10px 0;border-top:1px solid var(--vy-hairline);">
                    <div style="font-size:13.5px;color:var(--vy-muted);">No trusted devices</div>
                    <div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px;">Every sign-in will ask for your 2FA code.</div>
                </div>
            @endforelse
        </div>

        {{-- ── CONNECTED APPS — HONEST (no operator OAuth catalog; never a fabricated app) ──────────────── --}}
        <div id="sec-apps" class="vy-card vy-fade" style="margin-bottom:16px;max-width:560px;">
            <h2 class="vy-card-title" style="margin-bottom:4px;">Connected apps</h2>
            <div style="padding:10px 0;border-top:1px solid var(--vy-hairline);">
                <div style="font-size:13.5px;color:var(--vy-muted);">No apps connected</div>
                <div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px;">Third-party app access isn’t available for staff accounts yet.</div>
            </div>
        </div>

        {{-- ── DATA EXPORT — self-scoped account + own activity (JSON/CSV, date range) ──────────────────── --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;max-width:560px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <div>
                    <h2 class="vy-card-title" style="margin:0;">Data export</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:3px 0 0;">Download your account details and your own activity.</p>
                </div>
                <button type="button" class="vy-btn vy-btn--ghost" style="font-size:12.5px;" @click="exportOpen = true">Request export</button>
            </div>
        </div>

        {{-- ── MODALS ──────────────────────────────────────────────────────────────────────────────────── --}}
        {{-- Add IP --}}
        <div x-show="ipOpen" x-cloak class="vyp-scrim" @click.self="ipOpen = false" style="position:fixed;inset:0;background:rgba(30,20,15,.4);display:flex;align-items:center;justify-content:center;z-index:50;">
            <div class="vy-card" style="max-width:400px;width:90%;">
                <h2 class="vy-card-title">Add to allowlist</h2>
                <p style="font-size:12.5px;color:var(--vy-muted);margin:4px 0 12px;">An exact IP or a CIDR block. Your current address is <strong>{{ $currentIp }}</strong>.</p>
                <form method="POST" action="{{ route('app.security.ip-rules.add') }}">
                    @csrf
                    <input type="text" name="ip_address" class="vy-input" placeholder="203.0.113.4 or 203.0.113.0/24" value="{{ $currentIp }}" required style="margin-bottom:8px;">
                    <input type="text" name="label" class="vy-input" maxlength="80" placeholder="Label (e.g. Office)" style="margin-bottom:12px;">
                    <div style="display:flex;justify-content:flex-end;gap:10px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="ipOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Add to allowlist</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Data export --}}
        <div x-show="exportOpen" x-cloak class="vyp-scrim" @click.self="exportOpen = false" style="position:fixed;inset:0;background:rgba(30,20,15,.4);display:flex;align-items:center;justify-content:center;z-index:50;">
            <div class="vy-card" style="max-width:400px;width:90%;" x-data="{ range: 'all', format: 'json' }">
                <h2 class="vy-card-title">Export your data</h2>
                <p style="font-size:12.5px;color:var(--vy-muted);margin:4px 0 12px;">Your account details and the actions you’ve taken. Downloads to this device.</p>
                <form method="GET" action="{{ route('app.security.export') }}">
                    <label class="vy-field-label">Format</label>
                    <select name="format" x-model="format" class="vy-input" style="margin-bottom:10px;"><option value="json">JSON</option><option value="csv">CSV</option></select>
                    <label class="vy-field-label">Date range</label>
                    <select name="range" x-model="range" class="vy-input" style="margin-bottom:10px;"><option value="all">All time</option><option value="year">This year</option><option value="custom">Custom</option></select>
                    <div x-show="range === 'custom'" x-cloak style="display:flex;gap:10px;margin-bottom:10px;">
                        <input type="date" name="from" class="vy-input" aria-label="From">
                        <input type="date" name="to" class="vy-input" aria-label="To">
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:6px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="exportOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Download</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sign out all others (re-auth) --}}
        <div x-show="signOutOpen" x-cloak class="vyp-scrim" @click.self="signOutOpen = false" style="position:fixed;inset:0;background:rgba(30,20,15,.4);display:flex;align-items:center;justify-content:center;z-index:50;">
            <div class="vy-card" style="max-width:380px;width:90%;">
                <h2 class="vy-card-title">Sign out of all other devices</h2>
                <p style="font-size:12.5px;color:var(--vy-muted);margin:4px 0 12px;">This ends every session except this one. Confirm your password.</p>
                <form method="POST" action="{{ route('app.profile.sign-out-others') }}">
                    @csrf
                    <input type="password" name="current_password" class="vy-input" placeholder="Current password" autocomplete="current-password" required>
                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="signOutOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Sign out others</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Revoke trusted device (re-auth) — one modal, the target id bound via revokeId --}}
        <div x-show="revokeId !== null" x-cloak class="vyp-scrim" @click.self="revokeId = null" style="position:fixed;inset:0;background:rgba(30,20,15,.4);display:flex;align-items:center;justify-content:center;z-index:50;">
            <div class="vy-card" style="max-width:380px;width:90%;">
                <h2 class="vy-card-title">Revoke trusted device</h2>
                <p style="font-size:12.5px;color:var(--vy-muted);margin:4px 0 12px;">It will need a 2FA code next time. Confirm your password.</p>
                <form method="POST" action="{{ route('app.security.trusted.revoke') }}">
                    @csrf
                    <input type="hidden" name="device_id" :value="revokeId">
                    <input type="password" name="current_password" class="vy-input" placeholder="Current password" autocomplete="current-password" required>
                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="revokeId = null">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Revoke</button>
                    </div>
                </form>
            </div>
        </div>

    </x-operator.shell>
</div>
