{{-- SUPER ADMIN · ACCOUNT — the platform admin's own account (name · login email [re-verified] · password ·
     2FA recovery). Self-only; sensitive changes re-auth (current password); all audited. Control-room design. --}}
<x-admin.shell active="account" title="Your account"
    subtitle="Manage your platform-admin profile and sign-in credentials.">

    @if (session('admin_notice'))
        <div class="ad-flash">{{ session('admin_notice') }}</div>
    @endif
    @if ($errors->any())
        <div class="ad-flash ad-flash--error">{{ $errors->first() }}</div>
    @endif

    {{-- One-time recovery codes, shown right after a regeneration. --}}
    @if ($recoveryCodes)
        <div class="ad-card" style="margin-bottom: 18px; border-color: var(--ad-accent);">
            <h2 class="ad-panel-title">Your new recovery codes</h2>
            <p class="ad-stat-sub" style="margin: -8px 0 14px;">Save these somewhere safe — each works once, and they replace any previous set. Shown only now.</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 18px; font-family: ui-monospace, monospace; font-size: 13.5px; letter-spacing: .06em; color: var(--ad-ink);">
                @foreach ($recoveryCodes as $code)
                    <div>{{ $code }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="ad-grid-2">
        <div>
            {{-- Profile --}}
            <div class="ad-card">
                <h2 class="ad-panel-title">Profile</h2>
                <form method="POST" action="{{ route('admin.account.profile') }}" data-guard-submit>
                    @csrf
                    @method('PUT')
                    <div class="ad-field" style="margin-bottom: 14px;">
                        <label class="ad-label" for="name">Name</label>
                        <input class="ad-input" type="text" id="name" name="name" value="{{ old('name', $admin->name) }}" required>
                    </div>
                    <button type="submit" class="ad-btn">Save profile</button>
                </form>
            </div>

            {{-- Login email (re-verified) --}}
            <div class="ad-card" style="margin-top: 18px;">
                <h2 class="ad-panel-title">Login email</h2>
                <dl class="ad-dl" style="grid-template-columns: 120px 1fr; margin-bottom: 16px;">
                    <dt>Current</dt><dd>{{ $admin->email }}</dd>
                    @if ($admin->pending_email)
                        <dt>Pending</dt><dd>{{ $admin->pending_email }} <span style="color: var(--ad-faint);">· awaiting confirmation</span></dd>
                    @endif
                </dl>
                @if ($admin->pending_email)
                    <p class="ad-stat-sub" style="margin: 0 0 12px;">We emailed a confirmation link to your new address. Your email stays the same until you open it.</p>
                    <form method="POST" action="{{ route('admin.account.email.cancel') }}" data-guard-submit>
                        @csrf @method('DELETE')
                        <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">Cancel pending change</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.account.email.request') }}" data-guard-submit>
                        @csrf
                        <div class="ad-field" style="margin-bottom: 12px;">
                            <label class="ad-label" for="email">New email</label>
                            <input class="ad-input" type="email" id="email" name="email" autocomplete="off" required>
                        </div>
                        <div class="ad-field" style="margin-bottom: 12px;">
                            <label class="ad-label" for="email_current_password">Current password</label>
                            <input class="ad-input" type="password" id="email_current_password" name="current_password" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="ad-btn">Change email</button>
                        <p class="ad-stat-sub" style="margin-top: 10px;">🔒 We'll email a confirmation link to the new address — your email only changes once you open it.</p>
                    </form>
                @endif
            </div>
        </div>

        <div>
            {{-- Password --}}
            <div class="ad-card">
                <h2 class="ad-panel-title">Change password</h2>
                <form method="POST" action="{{ route('admin.account.password') }}" data-guard-submit>
                    @csrf
                    @method('PUT')
                    <div class="ad-field" style="margin-bottom: 12px;">
                        <label class="ad-label" for="current_password">Current password</label>
                        <input class="ad-input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="ad-field" style="margin-bottom: 12px;">
                        <label class="ad-label" for="password">New password</label>
                        <input class="ad-input" type="password" id="password" name="password" autocomplete="new-password" required>
                    </div>
                    <div class="ad-field" style="margin-bottom: 14px;">
                        <label class="ad-label" for="password_confirmation">Confirm new password</label>
                        <input class="ad-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="ad-btn">Update password</button>
                    <p class="ad-stat-sub" style="margin-top: 10px;">🔒 {{ \App\Support\Platform\PlatformAdminPasswordPolicy::hint() }} Your current password is required.</p>
                </form>
            </div>

            {{-- Two-factor --}}
            <div class="ad-card" style="margin-top: 18px;">
                <h2 class="ad-panel-title">Two-factor authentication</h2>
                <div class="ad-row" style="border: none; padding-top: 0;">
                    <span class="ad-row-k">Status</span>
                    <span class="ad-row-v">
                        @if ($admin->hasTwoFactorEnabled())
                            <span class="ad-pill ad-pill--active">Active</span>
                        @else
                            <span class="ad-pill ad-pill--suspended">Not set</span>
                        @endif
                    </span>
                </div>
                <p class="ad-stat-sub" style="margin: 10px 0 14px;">Two-factor is required for platform access. You can regenerate your one-time recovery codes below.</p>
                @if ($admin->hasTwoFactorEnabled())
                    <form method="POST" action="{{ route('admin.account.recovery-codes') }}" data-guard-submit>
                        @csrf
                        <div class="ad-field" style="margin-bottom: 12px;">
                            <label class="ad-label" for="rc_current_password">Current password</label>
                            <input class="ad-input" type="password" id="rc_current_password" name="current_password" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="ad-btn ad-btn--ghost">Regenerate recovery codes</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-admin.shell>
