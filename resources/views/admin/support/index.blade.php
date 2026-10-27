{{-- ADMIN 01 · PHASE 3 — SUPPORT TOOLS: look up a Vyrobel ACCOUNT HOLDER by email (never a guest), and help them in.
     ⚠️ While mail is `log`, the two email actions say plainly that nothing reaches anyone — never a silent success. --}}
<x-admin.shell active="support" :title="__('admin/support/index.title')" :subtitle="__('admin/support/index.subtitle')">


    @if ($mailDiscards)
        <div class="ad-card" style="margin-bottom: 16px; display: flex; gap: 12px; align-items: flex-start;">
            <span class="ad-hpill ad-hpill--red">{{ __('admin/support/index.mail_pill', ['mailer' => $mailer]) }}</span>
            <p class="ad-stat-sub" style="margin: 0; line-height: 1.6;">
                {!! __('admin/support/index.mail_not_delivered') !!}
            </p>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.support.index') }}" class="ad-filters">
        <label class="vy-sr-only" for="support-email">{{ __('admin/support/index.email_address') }}</label>
        <input id="support-email" type="email" name="email" value="{{ $email }}" placeholder="{{ __('admin/support/index.email_placeholder') }}" required style="min-width: 280px;">
        <button type="submit" class="ad-btn ad-btn--sm">{{ __('admin/support/index.look_up') }}</button>
    </form>

    @if ($searched && $result === null)
        <div class="ad-card"><div class="ad-empty">{{ __('admin/support/index.no_account_holder') }}</div></div>
    @elseif ($result)
        @php $u = $result['user']; @endphp
        <div class="ad-grid-2">
            <div class="ad-card">
                <h2 class="ad-panel-title">{{ $u->name }}</h2>
                <dl class="ad-dl">
                    <dt>{{ __('common.email') }}</dt><dd>{{ $u->email }}</dd>
                    <dt>{{ __('common.status') }}</dt><dd>{{ match ((string) $u->status) { 'active' => __('admin/support/index.user_status_active'), 'disabled' => __('admin/support/index.user_status_disabled'), 'expired' => __('admin/support/index.user_status_expired'), default => ucfirst((string) $u->status) } }}</dd>
                    <dt>{{ __('admin/support/index.email_verified') }}</dt><dd><x-admin.time :at="$u->email_verified_at" :empty="__('common.no')" /></dd>
                    <dt>{{ __('admin/support/index.password') }}</dt><dd>{{ $result['has_password'] ? __('admin/support/index.password_set') : __('admin/support/index.password_none') }}</dd>
                    <dt>{{ __('admin/support/index.two_factor') }}</dt><dd>{{ $result['two_factor'] ? __('admin/support/index.two_factor_on') : __('admin/support/index.two_factor_off') }}</dd>
                    <dt>{{ __('admin/support/index.last_sign_in') }}</dt><dd><x-admin.time :at="$u->last_login_at" :empty="__('admin/support/index.never')" /></dd>
                    <dt>{{ __('admin/support/index.created') }}</dt><dd><x-admin.time :at="$u->created_at" date /></dd>
                </dl>
            </div>

            <div class="ad-card">
                <h2 class="ad-panel-title">{{ __('admin/support/index.organisations') }}</h2>
                @forelse ($result['memberships'] as $m)
                    <div class="ad-row">
                        <span class="ad-row-k"><a class="ad-cell-link" href="{{ route('admin.customers.show', $m['organization_id']) }}">{{ $m['organization'] }}</a>{{ $m['archived'] ? __('admin/support/index.archived_suffix') : '' }}</span>
                        <span class="ad-row-v" style="font-weight: 400; color: var(--ad-muted);">{{ __('admin/support/index.roles_and_status', [
                            'roles' => $m['roles'] === [] ? __('admin/support/index.no_role') : implode(', ', $m['roles']),
                            'status' => match ($m['status']) {
                                'invited' => __('admin/support/index.membership_status_invited'),
                                'active' => __('admin/support/index.membership_status_active'),
                                'disabled' => __('admin/support/index.membership_status_disabled'),
                                default => $m['status'],
                            },
                        ]) }}</span>
                    </div>
                @empty
                    <div class="ad-empty">{{ __('admin/support/index.not_a_member') }}</div>
                @endforelse
            </div>
        </div>

        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">{{ __('admin/support/index.sign_in_providers') }}</h2>
            @forelse ($result['identities'] as $i)
                <div class="ad-row" style="align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                    <span class="ad-row-k" style="color: var(--ad-ink);">
                        <strong>{{ $i['provider'] === 'twitter' ? 'X' : ucfirst($i['provider']) }}</strong>
                        <div style="font-size: 12px; color: var(--ad-faint);">{{ $i['provider_email'] ?? __('admin/support/index.no_provider_email') }} · {{ __('admin/support/index.last_used') }} <x-admin.time :at="$i['last_used_at']" :empty="__('admin/support/index.never_lower')" /></div>
                    </span>
                    @if ($i['may_unlink'])
                        <form method="POST" action="{{ route('admin.support.unlink', [$u->id, $i['id']]) }}" data-guard-submit style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @csrf
                            <input aria-label="{{ __('admin/support/index.reason_aria') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/support/index.unlink_placeholder') }}" style="margin: 0; min-width: 220px;">
                            <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">{{ __('admin/support/index.unlink') }}</button>
                        </form>
                    @else
                        <span class="ad-stat-sub" style="margin: 0; max-width: 360px;">{{ __('admin/support/index.cannot_unlink') }}</span>
                    @endif
                </div>
            @empty
                <div class="ad-empty">{{ __('admin/support/index.no_provider_linked') }}</div>
            @endforelse
        </div>

        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">{{ __('admin/support/index.help_them_in') }}</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                @if (! $u->email_verified_at)
                    <form method="POST" action="{{ route('admin.support.verify-email', $u->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="verify-reason">{{ __('admin/support/index.mark_email_verified') }}</label>
                        <input id="verify-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/support/index.verify_placeholder') }}">
                        <button type="submit" class="ad-btn ad-btn--sm" style="margin-top: 8px;">{{ __('admin/support/index.mark_verified') }}</button>
                        <p class="ad-reason-hint" style="margin: 6px 0 0;">{{ __('admin/support/index.verify_hint') }}</p>
                    </form>
                    <form method="POST" action="{{ route('admin.support.resend-verification', $u->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="resend-reason">{{ __('admin/support/index.resend_verification_email') }}</label>
                        <input id="resend-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/support/index.reason_placeholder') }}">
                        <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm" style="margin-top: 8px;">{{ __('admin/support/index.resend') }}</button>
                        @if ($mailDiscards)
                            <p class="ad-reason-hint" style="margin: 6px 0 0; color: var(--ad-danger);">{{ __('admin/support/index.mail_will_be_discarded', ['mailer' => $mailer]) }}</p>
                        @endif
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.support.password-reset', $u->id) }}" data-guard-submit>
                    @csrf
                    <label class="ad-label" for="reset-reason">{{ __('admin/support/index.send_password_reset') }}</label>
                    <input id="reset-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/support/index.reason_placeholder') }}">
                    <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm" style="margin-top: 8px;">{{ __('admin/support/index.send_reset') }}</button>
                    @if ($mailDiscards)
                        <p class="ad-reason-hint" style="margin: 6px 0 0; color: var(--ad-danger);">{{ __('admin/support/index.mail_will_be_discarded', ['mailer' => $mailer]) }}</p>
                    @endif
                </form>
            </div>
        </div>
    @endif

    @if ($breakGlassEnabled)
        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">{{ __('admin/support/index.break_glass_heading') }}</h2>
            <p class="ad-stat-sub" style="margin: 0 0 10px;">{{ __('admin/support/index.break_glass_explainer') }}</p>
            <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.break-glass.index') }}">{{ __('admin/support/index.open_break_glass') }}</a>
        </div>
    @endif
</x-admin.shell>
