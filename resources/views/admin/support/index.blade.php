{{-- ADMIN 01 · PHASE 3 — SUPPORT TOOLS: look up a Vyrobel ACCOUNT HOLDER by email (never a guest), and help them in.
     ⚠️ While mail is `log`, the two email actions say plainly that nothing reaches anyone — never a silent success. --}}
<x-admin.shell active="support" title="Support" subtitle="Find an account holder by email, and help them back in. Every action needs a reason.">


    @if ($mailDiscards)
        <div class="ad-card" style="margin-bottom: 16px; display: flex; gap: 12px; align-items: flex-start;">
            <span class="ad-hpill ad-hpill--red">Mail: {{ $mailer }}</span>
            <p class="ad-stat-sub" style="margin: 0; line-height: 1.6;">
                <strong>Mail is not being delivered.</strong> “Resend verification” and “Send password reset” below run the
                real code, and every email they produce is <strong>discarded</strong> — nobody receives anything. To let
                someone in today, confirm who they are another way and use <strong>Mark email verified</strong>.
            </p>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.support.index') }}" class="ad-filters">
        <label class="vy-sr-only" for="support-email">Email address</label>
        <input id="support-email" type="email" name="email" value="{{ $email }}" placeholder="name@example.com" required style="min-width: 280px;">
        <button type="submit" class="ad-btn ad-btn--sm">Look up</button>
    </form>

    @if ($searched && $result === null)
        <div class="ad-card"><div class="ad-empty">No account holder has that email address.</div></div>
    @elseif ($result)
        @php $u = $result['user']; @endphp
        <div class="ad-grid-2">
            <div class="ad-card">
                <h2 class="ad-panel-title">{{ $u->name }}</h2>
                <dl class="ad-dl">
                    <dt>Email</dt><dd>{{ $u->email }}</dd>
                    <dt>Status</dt><dd>{{ ucfirst((string) $u->status) }}</dd>
                    <dt>Email verified</dt><dd><x-admin.time :at="$u->email_verified_at" empty="No" /></dd>
                    <dt>Password</dt><dd>{{ $result['has_password'] ? 'Set' : 'None (signs in with a provider)' }}</dd>
                    <dt>Two-factor</dt><dd>{{ $result['two_factor'] ? 'On' : 'Off' }}</dd>
                    <dt>Last sign-in</dt><dd><x-admin.time :at="$u->last_login_at" empty="Never" /></dd>
                    <dt>Created</dt><dd><x-admin.time :at="$u->created_at" date /></dd>
                </dl>
            </div>

            <div class="ad-card">
                <h2 class="ad-panel-title">Organisations</h2>
                @forelse ($result['memberships'] as $m)
                    <div class="ad-row">
                        <span class="ad-row-k"><a class="ad-cell-link" href="{{ route('admin.customers.show', $m['organization_id']) }}">{{ $m['organization'] }}</a>{{ $m['archived'] ? ' (archived)' : '' }}</span>
                        <span class="ad-row-v" style="font-weight: 400; color: var(--ad-muted);">{{ $m['roles'] === [] ? 'no role' : implode(', ', $m['roles']) }} · {{ $m['status'] }}</span>
                    </div>
                @empty
                    <div class="ad-empty">Not a member of any organisation.</div>
                @endforelse
            </div>
        </div>

        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">Sign-in providers</h2>
            @forelse ($result['identities'] as $i)
                <div class="ad-row" style="align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                    <span class="ad-row-k" style="color: var(--ad-ink);">
                        <strong>{{ $i['provider'] === 'twitter' ? 'X' : ucfirst($i['provider']) }}</strong>
                        <div style="font-size: 12px; color: var(--ad-faint);">{{ $i['provider_email'] ?? 'no email from the provider' }} · last used <x-admin.time :at="$i['last_used_at']" empty="never" /></div>
                    </span>
                    @if ($i['may_unlink'])
                        <form method="POST" action="{{ route('admin.support.unlink', [$u->id, $i['id']]) }}" data-guard-submit style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @csrf
                            <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why unlink it?" style="margin: 0; min-width: 220px;">
                            <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">Unlink</button>
                        </form>
                    @else
                        <span class="ad-stat-sub" style="margin: 0; max-width: 360px;">Can’t be unlinked — it is their only way in (no password, no other provider).</span>
                    @endif
                </div>
            @empty
                <div class="ad-empty">No provider linked — password sign-in only.</div>
            @endforelse
        </div>

        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">Help them in</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                @if (! $u->email_verified_at)
                    <form method="POST" action="{{ route('admin.support.verify-email', $u->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="verify-reason">Mark email verified</label>
                        <input id="verify-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="How did you confirm who they are?">
                        <button type="submit" class="ad-btn ad-btn--sm" style="margin-top: 8px;">Mark verified</button>
                        <p class="ad-reason-hint" style="margin: 6px 0 0;">Exactly what their emailed link would do — and read back from the database afterwards.</p>
                    </form>
                    <form method="POST" action="{{ route('admin.support.resend-verification', $u->id) }}" data-guard-submit>
                        @csrf
                        <label class="ad-label" for="resend-reason">Resend verification email</label>
                        <input id="resend-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason">
                        <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm" style="margin-top: 8px;">Resend</button>
                        @if ($mailDiscards)
                            <p class="ad-reason-hint" style="margin: 6px 0 0; color: var(--ad-danger);">Mail is “{{ $mailer }}” — this will be discarded.</p>
                        @endif
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.support.password-reset', $u->id) }}" data-guard-submit>
                    @csrf
                    <label class="ad-label" for="reset-reason">Send a password reset</label>
                    <input id="reset-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason">
                    <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm" style="margin-top: 8px;">Send reset</button>
                    @if ($mailDiscards)
                        <p class="ad-reason-hint" style="margin: 6px 0 0; color: var(--ad-danger);">Mail is “{{ $mailer }}” — this will be discarded.</p>
                    @endif
                </form>
            </div>
        </div>
    @endif

    @if ($breakGlassEnabled)
        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">Break-glass</h2>
            <p class="ad-stat-sub" style="margin: 0 0 10px;">A read-only, time-boxed, audited look inside one organisation’s back office — the organisation is told afterwards.</p>
            <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.break-glass.index') }}">Open break-glass</a>
        </div>
    @endif
</x-admin.shell>
