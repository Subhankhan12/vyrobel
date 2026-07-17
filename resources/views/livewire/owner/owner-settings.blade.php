{{-- Owner SETTINGS (Wave 4 · Sub-gate 3) — the owner's OWN account details, on the owner design system.
     Only the BACKED self-profile (name + encrypted email/phone) is editable; the payout account/IBAN,
     notification preferences, team and 2FA are net-new SUBSYSTEMS, stubbed "Soon", never a fake save.
     The write is guarded by user_id (Owner is not BelongsToOwner). See App\Livewire\Owner\OwnerSettings.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@php $lbl = 'display:block;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-faint);margin-bottom:7px;'; @endphp
<div>
    <x-owner.shell active="settings" title="Settings" :viewer="$viewer">

        @if (session('owner_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('owner_notice') }}</div>
            </div>
        @endif

        {{-- ── Your details — BACKED (owners.name + encrypted email/phone), user_id-guarded ─────────── --}}
        <div class="vy-card vy-fade" style="margin-bottom: 16px;">
            <h2 style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; margin: 0 0 4px;">Your details</h2>
            <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 16px;">How your management company reaches you. Only you can change these.</p>

            <form method="POST" action="{{ route('owner.settings.update') }}">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div>
                        <label style="{{ $lbl }}" for="o_name">Name</label>
                        <input id="o_name" type="text" name="name" class="vy-input" value="{{ old('name', $profile['name']) }}" required>
                        @error('name') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="{{ $lbl }}" for="o_email">Email <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                        <input id="o_email" type="email" name="email" class="vy-input" value="{{ old('email', $profile['email']) }}">
                        @error('email') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="{{ $lbl }}" for="o_phone">Phone <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                        <input id="o_phone" type="text" name="phone" class="vy-input" value="{{ old('phone', $profile['phone']) }}">
                        @error('phone') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="vy-btn" style="background: var(--vy-grad); color: #fff; padding: 11px 22px;">Save my details</button>
                </div>
            </form>
        </div>

        {{-- ── Change password — current password verified server-side; same guard + shared reset path as
             the operator side. Owner is resolved from auth(), never a request id. ───────────────────────── --}}
        @if (session('password_status'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('password_status') }}</div>
            </div>
        @endif
        <div class="vy-card vy-fade" style="margin-bottom: 16px;">
            <h2 style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; margin: 0 0 4px;">Change password</h2>
            <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 16px;">Enter your current password to confirm it’s you. Other devices with “remember me” are signed out.</p>
            <form method="POST" action="{{ route('owner.settings.password') }}">
                @csrf
                <div style="display: grid; gap: 14px; max-width: 380px;">
                    <div>
                        <label style="{{ $lbl }}" for="cur_pw">Current password</label>
                        <input id="cur_pw" type="password" name="current_password" class="vy-input" autocomplete="current-password" required>
                        @error('current_password') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="{{ $lbl }}" for="new_pw">New password</label>
                        <input id="new_pw" type="password" name="password" class="vy-input" autocomplete="new-password" required>
                        @error('password') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                        <span style="font-size: 11px; color: var(--vy-faint);">At least 8 characters.</span>
                    </div>
                    <div>
                        <label style="{{ $lbl }}" for="conf_pw">Confirm new password</label>
                        <input id="conf_pw" type="password" name="password_confirmation" class="vy-input" autocomplete="new-password" required>
                    </div>
                </div>
                <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="vy-btn" style="background: var(--vy-grad); color: #fff; padding: 11px 22px;">Update password</button>
                </div>
            </form>
        </div>

        {{-- ── Two-factor authentication — REAL TOTP (google2fa + bacon QR). Shared panel. ─────────────── --}}
        @include('livewire.partials.two-factor-panel')

        {{-- ── STUBBED — net-new subsystems, honestly "Soon" (NOT a fake save) ────────────────────────── --}}
        <div class="vy-card vy-fade">
            <h2 style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; margin: 0 0 12px;">More</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                @foreach (['Payout account & bank details', 'Notification preferences', 'Team & access'] as $stub)
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 11px 13px; border: 1px solid var(--vy-hairline); border-radius: 10px;">
                        <span style="font-size: 13px; color: var(--vy-muted);">{{ $stub }}</span>
                        <span style="font-size: 10.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-faint); background: rgba(0,0,0,.04); padding: 3px 9px; border-radius: 20px;">Soon</span>
                    </div>
                @endforeach
            </div>
            <p style="font-size: 11.5px; color: var(--vy-faint); margin: 12px 0 0;">These are net-new subsystems, not wired yet — shown so nothing here pretends to save.</p>
        </div>

    </x-owner.shell>
</div>
