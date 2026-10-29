{{-- SUPER ADMIN · ACCOUNT — the platform admin's own account (name · login email [re-verified] · password ·
     2FA recovery). Self-only; sensitive changes re-auth (current password); all audited. Control-room design. --}}
<x-admin.shell active="account" :title="__('admin/account/index.page_title')"
    :subtitle="__('admin/account/index.page_subtitle')">


    {{-- One-time recovery codes, shown right after a regeneration. --}}
    @if ($recoveryCodes)
        <div class="ad-card" style="margin-bottom: 18px; border-color: var(--ad-accent);">
            <h2 class="ad-panel-title">{{ __('admin/account/index.new_recovery_codes') }}</h2>
            <p class="ad-stat-sub" style="margin: -8px 0 14px;">{{ __('admin/account/index.new_recovery_codes_help') }}</p>
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
                <h2 class="ad-panel-title">{{ __('admin/account/index.profile') }}</h2>
                <form method="POST" action="{{ route('admin.account.profile') }}" data-guard-submit>
                    @csrf
                    @method('PUT')
                    <div class="ad-field" style="margin-bottom: 14px;">
                        <label class="ad-label" for="name">{{ __('common.name') }}</label>
                        <input class="ad-input" type="text" id="name" name="name" value="{{ old('name', $admin->name) }}" required>
                    </div>
                    <div class="ad-field" style="margin-bottom: 14px;">
                        <label class="ad-label" for="locale">{{ __('admin/account/index.language') }}</label>
                        <select class="ad-input" id="locale" name="locale">@foreach (\App\Support\I18n\Locales::offeredOptions() as $code => $native)<option value="{{ $code }}" @selected(old('locale', $admin->locale ?? 'en') === $code)>{{ $native }}</option>@endforeach</select>
                    </div>
                    <button type="submit" class="ad-btn">{{ __('admin/account/index.save_profile') }}</button>
                </form>
            </div>

            {{-- Login email (re-verified) --}}
            <div class="ad-card" style="margin-top: 18px;">
                <h2 class="ad-panel-title">{{ __('admin/account/index.login_email') }}</h2>
                <dl class="ad-dl" style="grid-template-columns: 120px 1fr; margin-bottom: 16px;">
                    <dt>{{ __('admin/account/index.current') }}</dt><dd>{{ $admin->email }}</dd>
                    @if ($admin->pending_email)
                        <dt>{{ __('admin/account/index.pending') }}</dt><dd>{{ $admin->pending_email }} <span style="color: var(--ad-faint);">{{ __('admin/account/index.awaiting_confirmation') }}</span></dd>
                    @endif
                </dl>
                @if ($admin->pending_email)
                    <p class="ad-stat-sub" style="margin: 0 0 12px;">{{ __('admin/account/index.pending_email_help') }}</p>
                    <form method="POST" action="{{ route('admin.account.email.cancel') }}" data-guard-submit>
                        @csrf @method('DELETE')
                        <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">{{ __('admin/account/index.cancel_pending_change') }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.account.email.request') }}" data-guard-submit>
                        @csrf
                        <div class="ad-field" style="margin-bottom: 12px;">
                            <label class="ad-label" for="email">{{ __('admin/account/index.new_email') }}</label>
                            <input class="ad-input" type="email" id="email" name="email" autocomplete="off" required>
                        </div>
                        <div class="ad-field" style="margin-bottom: 12px;">
                            <label class="ad-label" for="email_current_password">{{ __('admin/account/index.current_password') }}</label>
                            <input class="ad-input" type="password" id="email_current_password" name="current_password" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="ad-btn">{{ __('admin/account/index.change_email') }}</button>
                        <p class="ad-stat-sub" style="margin-top: 10px;">{{ __('admin/account/index.change_email_help') }}</p>
                    </form>
                @endif
            </div>
        </div>

        <div>
            {{-- Password --}}
            <div class="ad-card">
                <h2 class="ad-panel-title">{{ __('admin/account/index.change_password') }}</h2>
                <form method="POST" action="{{ route('admin.account.password') }}" data-guard-submit>
                    @csrf
                    @method('PUT')
                    <div class="ad-field" style="margin-bottom: 12px;">
                        <label class="ad-label" for="current_password">{{ __('admin/account/index.current_password') }}</label>
                        <input class="ad-input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="ad-field" style="margin-bottom: 12px;">
                        <label class="ad-label" for="password">{{ __('admin/account/index.new_password') }}</label>
                        <input class="ad-input" type="password" id="password" name="password" autocomplete="new-password" required>
                    </div>
                    <div class="ad-field" style="margin-bottom: 14px;">
                        <label class="ad-label" for="password_confirmation">{{ __('admin/account/index.confirm_new_password') }}</label>
                        <input class="ad-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="ad-btn">{{ __('admin/account/index.update_password') }}</button>
                    <p class="ad-stat-sub" style="margin-top: 10px;">{{ __('admin/account/index.password_help', ['hint' => \App\Support\Platform\PlatformAdminPasswordPolicy::hint()]) }}</p>
                </form>
            </div>

            {{-- Two-factor --}}
            <div class="ad-card" style="margin-top: 18px;">
                <h2 class="ad-panel-title">{{ __('admin/account/index.two_factor') }}</h2>
                <div class="ad-row" style="border: none; padding-top: 0;">
                    <span class="ad-row-k">{{ __('common.status') }}</span>
                    <span class="ad-row-v">
                        @if ($admin->hasTwoFactorEnabled())
                            <span class="ad-pill ad-pill--active">{{ __('admin/account/index.two_factor_active') }}</span>
                        @else
                            <span class="ad-pill ad-pill--suspended">{{ __('admin/account/index.two_factor_not_set') }}</span>
                        @endif
                    </span>
                </div>
                <p class="ad-stat-sub" style="margin: 10px 0 14px;">{{ __('admin/account/index.two_factor_help') }}</p>
                @if ($admin->hasTwoFactorEnabled())
                    <form method="POST" action="{{ route('admin.account.recovery-codes') }}" data-guard-submit>
                        @csrf
                        <div class="ad-field" style="margin-bottom: 12px;">
                            <label class="ad-label" for="rc_current_password">{{ __('admin/account/index.current_password') }}</label>
                            <input class="ad-input" type="password" id="rc_current_password" name="current_password" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="ad-btn ad-btn--ghost">{{ __('admin/account/index.regenerate_recovery_codes') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-admin.shell>
