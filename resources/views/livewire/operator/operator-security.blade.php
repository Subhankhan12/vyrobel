{{-- Operator SECURITY (Auth) — #104b GATE 1 exact-match to Operator_Security.html on REAL data. Reuses the #66
     security (change-password + 2FA/TOTP + Active sessions [ActiveSessions] + Trusted devices [TrustedDeviceService],
     the SAME the owner has), plus a REAL IP-allowlist (genuinely enforced by EnforceIpAllowlist), an honest
     "No apps connected" (no operator OAuth catalog), and a self-scoped data export. RAW-BLADE RULE: every `@`
     follows a space, newline or tag. --}}
<div x-data="{ ipOpen: false, exportOpen: false, signOutOpen: false, revokeId: null }">
    <x-operator.shell active="security" title="Security" :viewer="$viewer">

        {{-- ⚠️ F-1, BOTH HALVES. `password_status` carries TWO messages to this page and this view read
             neither: the passwordless gate's interruption ("set a password and you'll be brought back") AND
             the confirmation that a password change SUCCEEDED. Phase 3 proved the second by changing a real
             password and watching the screen say nothing. ⚠️ This is a NAMING MISMATCH, not a missing region —
             the region below already worked; it simply never read this key. The owner realm's own security
             view has rendered `password_status` all along, which is why only operators were silent. --}}
        @if (session('password_status'))
            <div class="vy-flash" role="status" style="margin-bottom:14px;background:rgba(26,127,75,.10);color:#1a7f4b;border:1px solid rgba(26,127,75,.2);border-radius:12px;padding:11px 15px;font-size:13px;font-weight:500">{{ session('password_status') }}</div>
        @endif
        @if (session('security_notice'))
            <div class="vy-flash" style="margin-bottom:14px;background:rgba(26,127,75,.10);color:#1a7f4b;border:1px solid rgba(26,127,75,.2);border-radius:12px;padding:11px 15px;font-size:13px;font-weight:500">{{ session('security_notice') }}</div>
        @endif
        @if (session('security_error') || $errors->any())
            <div class="vy-flash is-err" style="margin-bottom:14px;border-radius:12px;padding:11px 15px;font-size:13px;font-weight:500">{{ session('security_error') ?? $errors->first() }}</div>
        @endif

        {{-- ── OVERVIEW — "Account security · Protected" + the four real counts ─────────────────────────── --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            {{-- ⚠️ THIS BADGE IS DERIVED FROM THE REAL 2FA STATE — it used to be a hardcoded green
                 "Protected" that rendered unconditionally, so an account with two-factor OFF was still told
                 it was protected. On an auth page that is a SAFETY claim, not decoration: it tells someone
                 they are covered when the strongest control this page offers is switched off. Same class as
                 the hardcoded "Verified" badge the finish-to-100 pass removed from Owners, and the reason the
                 owner realm's equivalent was built as a DERIVED checklist rather than a score.
                 `$twoFactor['state']` is the authoritative enabled|pending|disabled flag. The state is always
                 carried by a WORD as well as colour (WCAG 1.4.1). --}}
            @php
                // ⚠️ The inks are MEASURED against each badge's own composited wash, not eyeballed. The
                // original hardcoded green (#1a7f4b on its 10% wash) measured 3.95 — under AA — and the
                // obvious amber (--vy-amber = rgb(178,106,8) on a 12% wash) measured 3.30. Darkened until
                // both clear 4.5 with headroom: green 5.75, amber 5.39.
                $__sec = match ($twoFactor['state']) {
                    'enabled' => ['label' => 'Protected', 'ink' => 'rgb(18, 99, 58)', 'wash' => 'rgba(26,127,75,.10)'],
                    'pending' => ['label' => 'Finish two-factor setup', 'ink' => 'rgb(133, 76, 4)', 'wash' => 'rgba(178,106,8,.12)'],
                    default   => ['label' => 'Two-factor off', 'ink' => 'rgb(133, 76, 4)', 'wash' => 'rgba(178,106,8,.12)'],
                };
            @endphp
            <div style="display:flex;align-items:center;gap:10px;">
                <h2 class="vy-card-title" style="margin:0;">Account security</h2>
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:{{ $__sec['ink'] }};background:{{ $__sec['wash'] }};border-radius:999px;padding:3px 11px;">
                    @if ($twoFactor['state'] === 'enabled')
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
                    @else
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/><path d="M12 9v4M12 16h.01"/></svg>
                    @endif
                    {{ $__sec['label'] }}
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
                {{-- A hidden USERNAME field so a password manager can associate the credential with the right
                     account. Chrome advises this on every password form ("Password forms should have
                     (optionally hidden) username fields") and it was the only console output this page
                     produced. It is display-only: `autocomplete` + `readonly`, no `name`, so it is NEVER
                     submitted and the change-password payload is byte-identical. --}}
                {{-- ⚠️⚠️ D3 — THE TRUE PLACEHOLDER-ONLY SHAPE, and the one that bites a SIGHTED user too.
                     Three password boxes that look identical, each named only by a placeholder that VANISHES the
                     moment you type. Halfway through, "which box am I in?" has no answer left on screen.
                     ⚠️ VISIBLE labels, not hidden ones or `aria-label`: the house pattern is
                     `<label class="vy-field-label" for="id">` (131 sites), and an invisible fix would leave the
                     sighted half of the defect in place. The placeholders stay as hints.
                     ⚠️ NO POSTED KEY MOVED — `name=` untouched; only `id`/`for` added.
                     ⚠️ The `aria-hidden` username field below is the CORRECT password-manager pattern and is
                     deliberately left unnamed — it is hidden from assistive tech on purpose. --}}
                <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly
                       aria-hidden="true" tabindex="-1" style="display:none;">
                <label class="vy-field-label" for="vy-sec-cur">Current password</label>
                {{-- ⚠️⚠️ THE SET-A-PASSWORD PATH IS THIS SAME CARD. An account with no password has nothing to
                     confirm with — `Hash::check(x, null)` is false — so demanding a current password would make
                     setting a FIRST one impossible and the lockout permanent. The field is omitted only in that
                     case; for every account that HAS a password it is required exactly as before, and the
                     controller enforces the same split rather than trusting this view. --}}
                @if (auth()->user()?->hasPassword())
                    <label class="vy-field-label" for="vy-sec-cur">Current password</label>
                    <input type="password" name="current_password" id="vy-sec-cur" class="vy-input" placeholder="Current password" autocomplete="current-password" required style="margin-bottom:8px;">
                @else
                    <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 10px;">Your account does not have a password yet. Set one below — you will be asked for it when confirming sensitive changes.</p>
                @endif
                <label class="vy-field-label" for="vy-sec-new">New password</label>
                <input type="password" name="password" id="vy-sec-new" class="vy-input" placeholder="New password" autocomplete="new-password" required style="margin-bottom:8px;">
                <label class="vy-field-label" for="vy-sec-conf">Confirm new password</label>
                <input type="password" name="password_confirmation" id="vy-sec-conf" class="vy-input" placeholder="Confirm new password" autocomplete="new-password" required style="margin-bottom:12px;">
                {{-- ⚠️ DO13-4: this form enforced a composition rule and stated NOTHING, so a rejection gave the
                     operator no way to comply. It was also the LAST screen found — a copy-sweep for "8
                     characters" could not see it precisely because it never made the claim (§N.8f: a search
                     finds only what you name; the route sweep found it). --}}
                <p style="font-size:11.5px;color:var(--vy-on-grad-soft);margin:-6px 0 12px">{{ \App\Support\Auth\PasswordPolicy::hint() }}</p>
                <button type="submit" class="vy-btn vy-btn--accent">Update password</button>
            </form>
        </div>

        {{-- ── TWO-FACTOR (real #66 shared panel) ──────────────────────────────────────────────────────── --}}
        {{-- The 560px column is THIS page's layout (every card here uses it), so it is set on the include
             rather than inside the shared partial, which also renders full-width on the owner page. --}}
        <div style="max-width:560px;">
            @include('livewire.partials.two-factor-panel')
        </div>

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
        {{-- ══ D24-2 · PASSKEYS — the SAME <x-auth.passkeys> card the owner Security page renders, on the
             SAME PasskeyController and the SAME PasskeyService. Only the route names differ; the ceremony,
             the self-only remove-with-re-auth and the empty state are one implementation (§N.8f).
             ⚠️ Sign-IN needed no wiring: the usernameless flow is already on the shared `web` guard and
             redirects through `RedirectsByRole`, so an operator passkey already lands on /app. --}}
        <x-auth.passkeys
            :passkeys="$passkeys"
            options-route="app.security.passkeys.options"
            store-route="app.security.passkeys.store"
            remove-route="app.security.passkeys.remove"
            h2="font-size:15px;font-weight:600;margin:0 0 4px;color:var(--vy-ink);"
            sub="font-size:12.5px;color:var(--vy-muted);margin:0;"
            ghost="background:transparent;border:1px solid var(--vy-glass-border);"
            err="font-size:12px;color:#B0322A;display:block;margin-top:6px;" />

        {{-- ══ CONNECTED SIGN-IN ACCOUNTS ═══════════════════════════════════════════════════════════════
             ⚠️ REAL rows only — this account's own `user_identities`. Nothing is shown when Google is not
             configured, because the routes 404 in that state and a control that leads to an error page is
             worse than no control. ⚠️ The unlink refusal is enforced in the route; the disabled state below
             merely EXPLAINS it, and says why rather than going quiet. --}}
        <section class="vy-card" id="sec-connections" style="margin-top:14px;">
            <h2 class="vy-card-title" style="margin-bottom:4px;">Connected sign-in accounts</h2>
            <p style="font-size:12.5px;color:var(--vy-muted);margin:0 0 12px;">Sign in to Vyrobel with an account you already have.</p>

            @if (session('connections_status'))
                <p style="font-size:12.5px;color:#1E7A4B;margin:0 0 10px;">{{ session('connections_status') }}</p>
            @endif
            @if (session('connections_error'))
                <p style="font-size:12.5px;color:#B0322A;margin:0 0 10px;">{{ session('connections_error') }}</p>
            @endif

            @forelse ($connections as $connection)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-top:1px solid var(--vy-glass-border);">
                    <div>
                        <div style="font-size:13.5px;color:var(--vy-ink);font-weight:600;">{{ ucfirst($connection['provider']) }}</div>
                        <div style="font-size:12px;color:var(--vy-muted);">{{ $connection['email'] }} · last used {{ $connection['last_used'] }}</div>
                    </div>
                    @if ($connection['can_unlink'])
                        <form method="POST" action="{{ route('app.security.connections.unlink', $connection['id']) }}">
                            @csrf
                            <button type="submit" class="vy-btn" style="background:transparent;border:1px solid var(--vy-glass-border);color:var(--vy-ink);">Disconnect</button>
                        </form>
                    @else
                        {{-- ⚠️ Disabled AND explained — the footer "Join" pattern. A control that simply stops
                             working teaches nothing; this one says exactly what to do to make it work. --}}
                        <div style="text-align:right;max-width:280px;">
                            <button type="button" class="vy-btn" disabled aria-disabled="true" aria-describedby="vy-unlink-why-{{ $connection['id'] }}" style="background:transparent;border:1px solid var(--vy-glass-border);color:var(--vy-muted);cursor:not-allowed;">Disconnect</button>
                            <p id="vy-unlink-why-{{ $connection['id'] }}" style="font-size:11.5px;color:var(--vy-muted);margin:6px 0 0;">{{ \App\Support\Auth\SignInCredentials::unlinkRefusalReason() }}</p>
                        </div>
                    @endif
                </div>
            @empty
                <div style="font-size:13.5px;color:var(--vy-muted);">No sign-in accounts connected</div>
            @endforelse

            @if ($socialEnabled && collect($connections)->where('provider', 'google')->isEmpty())
                <form method="POST" action="{{ route('app.security.connections.link') }}" style="margin-top:12px;">
                    @csrf
                    <button type="submit" class="vy-btn" style="background:transparent;border:1px solid var(--vy-glass-border);color:var(--vy-ink);">Connect Google</button>
                </form>
            @endif
        </section>

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
        <div x-show="ipOpen" x-cloak class="vyp-scrim" @click.self="ipOpen = false" style="position:fixed;inset:0;background:rgba(30,20,15,.4);display:flex;align-items:center;justify-content:center;z-index:50;" @keydown.escape.window="ipOpen = false">
            <div class="vy-card" style="max-width:400px;width:90%;" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="ipOpen" aria-labelledby="vy-dlg-operator-security-ipopen-4">
                <h2 class="vy-card-title" id="vy-dlg-operator-security-ipopen-4">Add to allowlist</h2>
                <p style="font-size:12.5px;color:var(--vy-muted);margin:4px 0 12px;">An exact IP or a CIDR block. Your current address is <strong>{{ $currentIp }}</strong>.</p>
                <form method="POST" action="{{ route('app.security.ip-rules.add') }}">
                    @csrf
                    <label class="vy-sr-only" for="vy-lbl-operator-security-ip-address">203.0.113.4 or 203.0.113.0/24</label>
                    <input id="vy-lbl-operator-security-ip-address" type="text" name="ip_address" class="vy-input" placeholder="203.0.113.4 or 203.0.113.0/24" value="{{ $currentIp }}" required style="margin-bottom:8px;">
                    <label class="vy-sr-only" for="vy-lbl-operator-security-label">Label (e.g. Office)</label>
                    <input id="vy-lbl-operator-security-label" type="text" name="label" class="vy-input" maxlength="80" placeholder="Label (e.g. Office)" style="margin-bottom:12px;">
                    <div style="display:flex;justify-content:flex-end;gap:10px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="ipOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Add to allowlist</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Data export --}}
        <div x-show="exportOpen" x-cloak class="vyp-scrim" @click.self="exportOpen = false" style="position:fixed;inset:0;background:rgba(30,20,15,.4);display:flex;align-items:center;justify-content:center;z-index:50;" @keydown.escape.window="exportOpen = false">
            <div class="vy-card" style="max-width:400px;width:90%;" x-data="{ range: 'all', format: 'json' }" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="exportOpen" aria-labelledby="vy-dlg-operator-security-exportopen-3">
                <h2 class="vy-card-title" id="vy-dlg-operator-security-exportopen-3">Export your data</h2>
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
        <div x-show="signOutOpen" x-cloak class="vyp-scrim" @click.self="signOutOpen = false" style="position:fixed;inset:0;background:rgba(30,20,15,.4);display:flex;align-items:center;justify-content:center;z-index:50;" @keydown.escape.window="signOutOpen = false">
            <div class="vy-card" style="max-width:380px;width:90%;" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="signOutOpen" aria-labelledby="vy-dlg-operator-security-signoutopen-2">
                <h2 class="vy-card-title" id="vy-dlg-operator-security-signoutopen-2">Sign out of all other devices</h2>
                <p style="font-size:12.5px;color:var(--vy-muted);margin:4px 0 12px;">This ends every session except this one. Confirm your password.</p>
                <form method="POST" action="{{ route('app.profile.sign-out-others') }}">
                    @csrf
                    {{-- Hidden username for password managers (see the change-password form above). --}}
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly
                           aria-hidden="true" tabindex="-1" style="display:none;">
                    <input type="password" name="current_password" class="vy-input" placeholder="Current password" autocomplete="current-password" required>
                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="signOutOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Sign out others</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Revoke trusted device (re-auth) — one modal, the target id bound via revokeId --}}
        {{-- ⚠️⚠️ D2 — THE DIALOG CONTRACT. `x-trap` gives all three focus properties at once: focus moves IN
     on open, is TRAPPED while open, and is RETURNED to the trigger on close. ⚠️ IT NEEDS NO NEW
     DEPENDENCY — Livewire already bundles Alpine's focus plugin (`directive("trap")` /
     `createFocusTrap` in livewire.esm.js); it was simply never used anywhere in 243 files.
     ⚠️ `.noscroll` also stops the page behind scrolling. ⚠️ It does NOT make the rest of the page
     INERT — without `inert` support or a dependency, focus is trapped and no more, and that is
     stated rather than implied. ⚠️ Focus lands on the panel itself (`tabindex="-1"`), never on the
     destructive button. --}}
                <div x-show="revokeId !== null" x-cloak class="vyp-scrim" @click.self="revokeId = null" style="position:fixed;inset:0;background:rgba(30,20,15,.4);display:flex;align-items:center;justify-content:center;z-index:50;" @keydown.escape.window="revokeId = null">
            <div class="vy-card" style="max-width:380px;width:90%;" role="dialog" aria-modal="true" aria-labelledby="vy-dlg-revoke-t" tabindex="-1" x-trap.noscroll="revokeId !== null">
                <h2 class="vy-card-title" id="vy-dlg-revoke-t">Revoke trusted device</h2>
                <p style="font-size:12.5px;color:var(--vy-muted);margin:4px 0 12px;">It will need a 2FA code next time. Confirm your password.</p>
                <form method="POST" action="{{ route('app.security.trusted.revoke') }}">
                    @csrf
                    <input type="hidden" name="device_id" :value="revokeId">
                    {{-- Hidden username for password managers (see the change-password form above). --}}
                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly
                           aria-hidden="true" tabindex="-1" style="display:none;">
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
