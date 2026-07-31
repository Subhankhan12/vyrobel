{{-- Operator SECURITY (Auth · Wave 4 follow-on) — change-password ONLY, reachable by every back-office user
     (behind auth + #26, no fine-grained gate). The design's 2FA / sessions / connected-apps / IP-rules are
     unbacked subsystems (docs/AUTH-INVENTORY.md) — shown "Soon", never faked. See App\Livewire\Operator\OperatorSecurity.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="security" title="Security" subtitle="Your account" :viewer="$viewer">

        @if (session('password_status'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('password_status') }}</div>
            </div>
        @endif

        {{-- ── Change password — the one real feature. Current password verified server-side. ─────────── --}}
        <div class="vy-card vy-fade" style="margin-bottom: 16px; max-width: 560px;">
            <h2 class="vy-card-title" style="margin-bottom: 4px;">Change password</h2>
            <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 16px;">Enter your current password to confirm it’s you. Other devices with “remember me” are signed out.</p>

            <form method="POST" action="{{ route('app.security.password') }}">
                @csrf
                <div class="vy-field">
                    <label class="vy-field-label" for="cur">Current password</label>
                    <input id="cur" type="password" name="current_password" class="vy-input" autocomplete="current-password" required>
                    @error('current_password') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                </div>
                <div class="vy-field">
                    <label class="vy-field-label" for="new">New password</label>
                    <input id="new" type="password" name="password" class="vy-input" autocomplete="new-password" required>
                    @error('password') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                    <span style="font-size: 11px; color: var(--vy-faint);">At least 8 characters.</span>
                </div>
                <div class="vy-field">
                    <label class="vy-field-label" for="conf">Confirm new password</label>
                    <input id="conf" type="password" name="password_confirmation" class="vy-input" autocomplete="new-password" required>
                </div>
                <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                    <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Update password</button>
                </div>
            </form>
        </div>

        {{-- ── Two-factor authentication — REAL TOTP (google2fa + bacon QR). Shared panel. ─────────────── --}}
        @include('livewire.partials.two-factor-panel')

        {{-- #104 GATE 3b — REAL active sessions & devices (the SAME ActiveSessions the Profile page ships; it was
             honestly "Soon" here while live one page over). Real `sessions` rows; sign-out-others reuses the
             existing password-confirmed route. --}}
        <div class="vy-card vy-fade" style="max-width: 560px;" x-data="{ signOutOpen: false }">
            <h2 class="vy-card-title" style="margin-bottom: 12px;">Active sessions &amp; devices</h2>
            @forelse ($sessions as $sess)
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 9px 0; border-top: 1px solid var(--vy-hairline); font-size: 13px;">
                    <div><strong>{{ $sess['device'] }}</strong>@if ($sess['current']) <span class="vy-tag tone-confirmed" style="font-size: 10px;">This device</span>@endif<br><span class="vy-muted" style="font-size:12px">{{ $sess['ip'] }} · {{ $sess['last_active']->diffForHumans() }}</span></div>
                </div>
            @empty
                <div class="vy-empty" style="padding: 14px 0;">No other active sessions.</div>
            @endforelse
            @if (count($sessions) > 1)
                <button type="button" class="vy-btn vy-btn--ghost" style="margin-top: 12px; width: 100%;" x-on:click="signOutOpen = true">Sign out of all other devices</button>
            @endif

            <div x-show="signOutOpen" x-cloak class="vyp-scrim" @click.self="signOutOpen = false" style="position:fixed;inset:0;background:rgba(30,20,15,.4);display:flex;align-items:center;justify-content:center;z-index:50">
                <div class="vyp-modal vy-card" style="max-width:380px;width:90%">
                    <h2 class="vy-card-title">Sign out of all other devices</h2>
                    <p style="font-size: 12.5px; color: var(--vy-muted); margin: 4px 0 12px;">This ends every session except this one. Confirm your password.</p>
                    <form method="POST" action="{{ route('app.profile.sign-out-others') }}">
                        @csrf
                        <input type="password" name="current_password" class="vy-input" placeholder="Current password" autocomplete="current-password" required>
                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                            <button type="button" class="vy-btn vy-btn--ghost" x-on:click="signOutOpen = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent">Sign out others</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Honest "Soon" — genuinely net-new subsystems (no fake toggle). --}}
        <div class="vy-card vy-fade" style="max-width: 560px;">
            <h2 class="vy-card-title" style="margin-bottom: 12px;">More security</h2>
            <div style="display: grid; gap: 10px;">
                @foreach (['Connected apps', 'Trusted devices', 'IP allow-list'] as $stub)
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 11px 13px; border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-sm);">
                        <span style="font-size: 13px; color: var(--vy-muted);">{{ $stub }}</span>
                        <span class="vy-btn is-soon" aria-disabled="true" style="padding: 5px 11px; font-size: 11.5px;">Soon</span>
                    </div>
                @endforeach
            </div>
            <p style="font-size: 11.5px; color: var(--vy-faint); margin: 12px 0 0;">These are net-new subsystems, not wired yet — shown so nothing here pretends to work.</p>
        </div>

    </x-operator.shell>
</div>
