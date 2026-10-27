{{-- ADMIN 01 · PHASE 1 — the platform ADMINS: roles, live sessions, invite / role / revoke / restore / sign-out.
     Owner-only to change; Analyst may look. 2FA is mandatory for every admin — there is no toggle for it. --}}
@php
    $me = auth('admin')->user();
    $canManage = $me?->may('admins.manage') ?? false;
    // I18N-01 — an admin's stored status code in words; an unknown code reads as before (capitalised).
    $statusLabel = fn (string $st): string => match ($st) {
        'active' => __('admin/admins/index.status_active'),
        'invited' => __('admin/admins/index.status_invited'),
        'disabled' => __('admin/admins/index.status_disabled'),
        default => ucfirst($st),
    };
@endphp
<x-admin.shell active="admins" :title="__('admin/admins/index.page_title')"
    :subtitle="__('admin/admins/index.page_subtitle')">


    @if (session('invite_link'))
        {{-- ⚠️ SHOWN ONCE. Only its hash is stored — this page cannot show it again. Nothing was emailed: production
             mail is not delivering, and an "invitation sent" message would be untrue. --}}
        <div class="ad-once" role="status">
            {!! __('admin/admins/index.invite_link_once', ['name' => e(session('invite_for')), 'hours' => e(\App\Support\Platform\PlatformAdminInvites::EXPIRES_HOURS)]) !!}
            <code>{{ session('invite_link') }}</code>
        </div>
    @endif

    <div class="ad-card" style="padding: 8px 10px; overflow-x: auto;">
        <table class="ad-table">
            <thead><tr><th>{{ __('admin/admins/index.col_admin') }}</th><th>{{ __('admin/admins/index.col_role') }}</th><th>{{ __('common.status') }}</th><th>{{ __('admin/admins/index.col_2fa') }}</th><th>{{ __('admin/admins/index.col_last_sign_in') }}</th><th>{{ __('admin/admins/index.col_live_sessions') }}</th></tr></thead>
            <tbody>
                @foreach ($admins as $a)
                    @php $sessions = $activeSessions[$a->id]; @endphp
                    <tr>
                        <td>
                            @if ($me && $a->is($me)){!! __('admin/admins/index.name_you', ['name' => e($a->name)]) !!}@else<strong>{{ $a->name }}</strong>@endif
                            <div style="font-size: 12px; color: var(--ad-faint);">{{ $a->email }}</div>
                        </td>
                        <td>{{ $a->roleEnum()?->label() ?? __('admin/admins/index.role_none') }}</td>
                        <td>
                            <span class="ad-pill {{ $a->status === 'active' ? 'ad-pill--active' : ($a->status === 'invited' ? 'ad-pill--trialing' : 'ad-pill--suspended') }}">{{ $statusLabel((string) $a->status) }}</span>
                            @if ($a->status === 'invited' && $a->invite_expires_at)
                                <div style="font-size: 11.5px; color: var(--ad-faint); margin-top: 3px;">
                                    {{ $a->invite_expires_at->isPast() ? __('admin/admins/index.link_expired') : __('admin/admins/index.link_expires') }} <x-admin.time :at="$a->invite_expires_at" />
                                </div>
                            @endif
                        </td>
                        <td>{{ $a->hasTwoFactorEnabled() ? __('admin/admins/index.two_factor_enrolled') : __('admin/admins/index.two_factor_not_yet') }}</td>
                        <td><x-admin.time :at="$a->last_login_at" :empty="__('admin/admins/index.never')" /></td>
                        <td>
                            @forelse ($sessions as $session)
                                <div style="font-size: 12px; margin-bottom: 3px;">
                                    {{ __('admin/admins/index.session_seen', ['ip' => $session->ip ?? __('admin/admins/index.unknown_ip')]) }} <x-admin.time :at="$session->last_seen_at" :empty="__('admin/admins/index.never_lower')" />
                                    @if ($currentHash && $session->session_key_hash === $currentHash) <strong>{{ __('admin/admins/index.this_session') }}</strong> @endif
                                </div>
                            @empty
                                <span style="color: var(--ad-faint); font-size: 12px;">{{ __('common.none') }}</span>
                            @endforelse
                        </td>
                    </tr>
                    @if ($canManage)
                        <tr>
                            <td colspan="6" style="border-top: none; padding-top: 0;">
                                <details>
                                    <summary style="cursor: pointer; font-size: 12.5px; color: var(--ad-accent-text); font-weight: 600;">{{ __('admin/admins/index.manage_admin', ['name' => $a->name]) }}</summary>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin-top: 12px;">
                                        @if ($a->status === 'active')
                                            <form method="POST" action="{{ route('admin.admins.role', $a->id) }}" data-guard-submit>
                                                @csrf
                                                <label class="ad-label">{{ __('admin/admins/index.change_role') }}</label>
                                                <select aria-label="{{ __('admin/admins/index.col_role') }}" name="role" class="ad-input" required>
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->value }}" @selected($a->role === $role->value)>{{ __('admin/admins/index.role_option', ['role' => $role->label(), 'description' => $role->description()]) }}</option>
                                                    @endforeach
                                                </select>
                                                <input aria-label="{{ __('admin/admins/index.reason') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/admins/index.reason') }}">
                                                <input class="ad-input" type="password" name="current_password" required placeholder="{{ __('admin/admins/index.your_password') }}" autocomplete="current-password" aria-label="{{ __('admin/admins/index.your_password') }}">
                                                <button class="ad-btn ad-btn--sm" type="submit" style="margin-top: 8px;">{{ __('admin/admins/index.change_role_button') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.admins.sign-out', $a->id) }}" data-guard-submit>
                                                @csrf
                                                {{-- SESSIONS 01 — on your own row this ends your OTHER sessions only; the controller enforces it. --}}
                                                <label class="ad-label">{{ $me && $a->is($me) ? __('admin/admins/index.end_other_sessions') : __('admin/admins/index.end_every_session') }}</label>
                                                <input aria-label="{{ __('admin/admins/index.reason') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/admins/index.reason') }}">
                                                <button class="ad-btn ad-btn--ghost ad-btn--sm" type="submit">{{ $me && $a->is($me) ? __('admin/admins/index.sign_out_other_sessions') : __('admin/admins/index.force_sign_out') }}</button>
                                            </form>
                                            @unless ($me && $a->is($me))
                                                <form method="POST" action="{{ route('admin.admins.revoke', $a->id) }}" data-guard-submit>
                                                    @csrf
                                                    <label class="ad-label">{{ __('admin/admins/index.revoke_access') }}</label>
                                                    <input aria-label="{{ __('admin/admins/index.reason') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/admins/index.reason') }}">
                                                    <input class="ad-input" type="password" name="current_password" required placeholder="{{ __('admin/admins/index.your_password') }}" autocomplete="current-password" aria-label="{{ __('admin/admins/index.your_password') }}">
                                                    <button class="ad-btn ad-btn--danger ad-btn--sm" type="submit" style="margin-top: 8px;">{{ __('admin/admins/index.revoke') }}</button>
                                                </form>
                                            @endunless
                                        @elseif ($a->status === 'disabled')
                                            <form method="POST" action="{{ route('admin.admins.restore', $a->id) }}" data-guard-submit>
                                                @csrf
                                                <label class="ad-label">{{ __('admin/admins/index.restore_access') }}</label>
                                                <input aria-label="{{ __('admin/admins/index.reason') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/admins/index.reason') }}">
                                                <input class="ad-input" type="password" name="current_password" required placeholder="{{ __('admin/admins/index.your_password') }}" autocomplete="current-password" aria-label="{{ __('admin/admins/index.your_password') }}">
                                                <button class="ad-btn ad-btn--sm" type="submit" style="margin-top: 8px;">{{ __('admin/admins/index.restore') }}</button>
                                            </form>
                                        @elseif ($a->status === 'invited')
                                            <form method="POST" action="{{ route('admin.admins.reissue', $a->id) }}" data-guard-submit>
                                                @csrf
                                                <label class="ad-label">{{ __('admin/admins/index.new_invitation_link') }}</label>
                                                <input aria-label="{{ __('admin/admins/index.reason') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/admins/index.reason') }}">
                                                <button class="ad-btn ad-btn--ghost ad-btn--sm" type="submit">{{ __('admin/admins/index.reissue_link') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.admins.revoke', $a->id) }}" data-guard-submit>
                                                @csrf
                                                <label class="ad-label">{{ __('admin/admins/index.cancel_the_invitation') }}</label>
                                                <input aria-label="{{ __('admin/admins/index.reason') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/admins/index.reason') }}">
                                                <input class="ad-input" type="password" name="current_password" required placeholder="{{ __('admin/admins/index.your_password') }}" autocomplete="current-password" aria-label="{{ __('admin/admins/index.your_password') }}">
                                                <button class="ad-btn ad-btn--danger ad-btn--sm" type="submit" style="margin-top: 8px;">{{ __('admin/admins/index.cancel_invitation') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($canManage)
        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">{{ __('admin/admins/index.invite_heading') }}</h2>
            <p class="ad-stat-sub" style="margin: -8px 0 14px;">{{ __('admin/admins/index.invite_help') }}</p>
            <form method="POST" action="{{ route('admin.admins.invite') }}" data-guard-submit>
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                    <div><label class="ad-label" for="inv-name">{{ __('common.name') }}</label><input class="ad-input" id="inv-name" name="name" required maxlength="120" value="{{ old('name') }}"></div>
                    <div><label class="ad-label" for="inv-email">{{ __('common.email') }}</label><input class="ad-input" id="inv-email" type="email" name="email" required maxlength="190" value="{{ old('email') }}"></div>
                    <div>
                        <label class="ad-label" for="inv-role">{{ __('admin/admins/index.col_role') }}</label>
                        <select class="ad-input" id="inv-role" name="role" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected(old('role', 'analyst') === $role->value)>{{ __('admin/admins/index.role_option', ['role' => $role->label(), 'description' => $role->description()]) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <label class="ad-label" for="inv-reason" style="margin-top: 12px;">{{ __('admin/admins/index.reason') }}</label>
                <input class="ad-input" id="inv-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/admins/index.invite_reason_placeholder') }}">
                <label class="ad-label" for="inv-pw" style="margin-top: 12px;">{{ __('admin/admins/index.your_password') }}</label>
                <input class="ad-input" id="inv-pw" type="password" name="current_password" required autocomplete="current-password" style="max-width: 320px;">
                <button class="ad-btn" type="submit" style="margin-top: 14px;">{{ __('admin/admins/index.create_invitation_link') }}</button>
            </form>
        </div>
    @endif
</x-admin.shell>
