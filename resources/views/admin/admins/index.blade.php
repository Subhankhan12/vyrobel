{{-- ADMIN 01 · PHASE 1 — the platform ADMINS: roles, live sessions, invite / role / revoke / restore / sign-out.
     Owner-only to change; Analyst may look. 2FA is mandatory for every admin — there is no toggle for it. --}}
@php
    $me = auth('admin')->user();
    $canManage = $me?->may('admins.manage') ?? false;
@endphp
<x-admin.shell active="admins" title="Admins"
    subtitle="Who can reach this console, with which role — and who is signed in right now.">

    @if (session('admin_notice'))
        <div class="ad-flash">{{ session('admin_notice') }}</div>
    @endif
    @if ($errors->any())
        <div class="ad-flash ad-flash--error">{{ $errors->first() }}</div>
    @endif

    @if (session('invite_link'))
        {{-- ⚠️ SHOWN ONCE. Only its hash is stored — this page cannot show it again. Nothing was emailed: production
             mail is not delivering, and an "invitation sent" message would be untrue. --}}
        <div class="ad-once" role="status">
            <strong>Invitation link for {{ session('invite_for') }} — shown only this once.</strong>
            Nothing was emailed (mail is not being delivered). Copy it and send it to them yourself, somewhere private.
            It works once and expires in {{ \App\Support\Platform\PlatformAdminInvites::EXPIRES_HOURS }} hours.
            <code>{{ session('invite_link') }}</code>
        </div>
    @endif

    <div class="ad-card" style="padding: 8px 10px; overflow-x: auto;">
        <table class="ad-table">
            <thead><tr><th>Admin</th><th>Role</th><th>Status</th><th>2FA</th><th>Last sign-in</th><th>Live sessions</th></tr></thead>
            <tbody>
                @foreach ($admins as $a)
                    @php $sessions = $activeSessions[$a->id]; @endphp
                    <tr>
                        <td>
                            <strong>{{ $a->name }}</strong>{{ $me && $a->is($me) ? ' (you)' : '' }}
                            <div style="font-size: 12px; color: var(--ad-faint);">{{ $a->email }}</div>
                        </td>
                        <td>{{ $a->roleEnum()?->label() ?? 'None — can do nothing' }}</td>
                        <td>
                            <span class="ad-pill {{ $a->status === 'active' ? 'ad-pill--active' : ($a->status === 'invited' ? 'ad-pill--trialing' : 'ad-pill--suspended') }}">{{ ucfirst($a->status) }}</span>
                            @if ($a->status === 'invited' && $a->invite_expires_at)
                                <div style="font-size: 11.5px; color: var(--ad-faint); margin-top: 3px;">
                                    {{ $a->invite_expires_at->isPast() ? 'Link expired' : 'Link expires '.$a->invite_expires_at->diffForHumans() }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $a->hasTwoFactorEnabled() ? 'Enrolled' : 'Not yet — forced at first sign-in' }}</td>
                        <td>{{ $a->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td>
                            @forelse ($sessions as $session)
                                <div style="font-size: 12px; margin-bottom: 3px;">
                                    {{ $session->ip ?? 'unknown IP' }} · seen {{ $session->last_seen_at?->diffForHumans() }}
                                    @if ($currentHash && $session->session_key_hash === $currentHash) <strong>(this session)</strong> @endif
                                </div>
                            @empty
                                <span style="color: var(--ad-faint); font-size: 12px;">None</span>
                            @endforelse
                        </td>
                    </tr>
                    @if ($canManage)
                        <tr>
                            <td colspan="6" style="border-top: none; padding-top: 0;">
                                <details>
                                    <summary style="cursor: pointer; font-size: 12.5px; color: var(--ad-accent-text); font-weight: 600;">Manage {{ $a->name }}</summary>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin-top: 12px;">
                                        @if ($a->status === 'active')
                                            <form method="POST" action="{{ route('admin.admins.role', $a->id) }}" data-guard-submit>
                                                @csrf
                                                <label class="ad-label">Change role</label>
                                                <select aria-label="Role" name="role" class="ad-input" required>
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->value }}" @selected($a->role === $role->value)>{{ $role->label() }} — {{ $role->description() }}</option>
                                                    @endforeach
                                                </select>
                                                <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason">
                                                <input class="ad-input" type="password" name="current_password" required placeholder="Your password" autocomplete="current-password" aria-label="Your password">
                                                <button class="ad-btn ad-btn--sm" type="submit" style="margin-top: 8px;">Change role</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.admins.sign-out', $a->id) }}" data-guard-submit>
                                                @csrf
                                                <label class="ad-label">End every session</label>
                                                <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason">
                                                <button class="ad-btn ad-btn--ghost ad-btn--sm" type="submit">Force sign-out</button>
                                            </form>
                                            @unless ($me && $a->is($me))
                                                <form method="POST" action="{{ route('admin.admins.revoke', $a->id) }}" data-guard-submit>
                                                    @csrf
                                                    <label class="ad-label">Revoke access</label>
                                                    <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason">
                                                    <input class="ad-input" type="password" name="current_password" required placeholder="Your password" autocomplete="current-password" aria-label="Your password">
                                                    <button class="ad-btn ad-btn--danger ad-btn--sm" type="submit" style="margin-top: 8px;">Revoke</button>
                                                </form>
                                            @endunless
                                        @elseif ($a->status === 'disabled')
                                            <form method="POST" action="{{ route('admin.admins.restore', $a->id) }}" data-guard-submit>
                                                @csrf
                                                <label class="ad-label">Restore access</label>
                                                <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason">
                                                <input class="ad-input" type="password" name="current_password" required placeholder="Your password" autocomplete="current-password" aria-label="Your password">
                                                <button class="ad-btn ad-btn--sm" type="submit" style="margin-top: 8px;">Restore</button>
                                            </form>
                                        @elseif ($a->status === 'invited')
                                            <form method="POST" action="{{ route('admin.admins.reissue', $a->id) }}" data-guard-submit>
                                                @csrf
                                                <label class="ad-label">New invitation link</label>
                                                <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason">
                                                <button class="ad-btn ad-btn--ghost ad-btn--sm" type="submit">Re-issue link</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.admins.revoke', $a->id) }}" data-guard-submit>
                                                @csrf
                                                <label class="ad-label">Cancel the invitation</label>
                                                <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason">
                                                <input class="ad-input" type="password" name="current_password" required placeholder="Your password" autocomplete="current-password" aria-label="Your password">
                                                <button class="ad-btn ad-btn--danger ad-btn--sm" type="submit" style="margin-top: 8px;">Cancel invitation</button>
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
            <h2 class="ad-panel-title">Invite an admin</h2>
            <p class="ad-stat-sub" style="margin: -8px 0 14px;">They choose their own password from the link, then must set up two-factor at first sign-in. Nobody else ever knows their password.</p>
            <form method="POST" action="{{ route('admin.admins.invite') }}" data-guard-submit>
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                    <div><label class="ad-label" for="inv-name">Name</label><input class="ad-input" id="inv-name" name="name" required maxlength="120" value="{{ old('name') }}"></div>
                    <div><label class="ad-label" for="inv-email">Email</label><input class="ad-input" id="inv-email" type="email" name="email" required maxlength="190" value="{{ old('email') }}"></div>
                    <div>
                        <label class="ad-label" for="inv-role">Role</label>
                        <select class="ad-input" id="inv-role" name="role" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected(old('role', 'analyst') === $role->value)>{{ $role->label() }} — {{ $role->description() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <label class="ad-label" for="inv-reason" style="margin-top: 12px;">Reason</label>
                <input class="ad-input" id="inv-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why does this person need console access?">
                <label class="ad-label" for="inv-pw" style="margin-top: 12px;">Your password</label>
                <input class="ad-input" id="inv-pw" type="password" name="current_password" required autocomplete="current-password" style="max-width: 320px;">
                <button class="ad-btn" type="submit" style="margin-top: 14px;">Create invitation link</button>
            </form>
        </div>
    @endif
</x-admin.shell>
