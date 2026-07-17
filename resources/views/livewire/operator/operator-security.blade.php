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

        {{-- ── Honest "Soon" — these are net-new subsystems, not wired (never a fake toggle). ──────────── --}}
        <div class="vy-card vy-fade" style="max-width: 560px;">
            <h2 class="vy-card-title" style="margin-bottom: 12px;">More security</h2>
            <div style="display: grid; gap: 10px;">
                @foreach (['Two-factor authentication', 'Active sessions & devices', 'Connected apps', 'IP allow-list'] as $stub)
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
