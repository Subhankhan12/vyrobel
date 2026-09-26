{{-- ⚠️⚠️ ADMIN 01 · PHASE 3 — BREAK-GLASS: the one audited way into an organisation's back office. Read-only, 30 minutes,
     bannered on every page, audited page by page, and the organisation is told afterwards — who, when, why. --}}
<x-admin.shell active="support" title="Break-glass" subtitle="A read-only, time-boxed look inside one organisation — reason, 2FA, and the organisation is told."
    :back="route('admin.support.index')" back-label="Support">


    @if ($current)
        <div class="ad-card" style="margin-bottom: 18px;">
            <h2 class="ad-panel-title">Your session is open</h2>
            <p class="ad-stat-sub" style="margin: 0 0 12px;">
                {{ $current->organization?->name }} · ends at {{ $current->expires_at->utc()->format('H:i') }} UTC · {{ $current->pages_viewed }} page{{ $current->pages_viewed === 1 ? '' : 's' }} viewed.
            </p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a class="ad-btn ad-btn--sm" href="{{ route('app.dashboard') }}">Go to their back office</a>
                <form method="POST" action="{{ route('admin.break-glass.end') }}">
                    @csrf
                    <button type="submit" class="ad-btn ad-btn--danger ad-btn--sm">End session now</button>
                </form>
            </div>
        </div>
    @else
        <div class="ad-card" style="margin-bottom: 18px;">
            <h2 class="ad-panel-title">Open a session</h2>
            <ul class="ad-stat-sub" style="margin: 0 0 14px; padding-left: 18px; line-height: 1.7;">
                <li><strong>Read-only.</strong> Every change is refused — forms, buttons, anything that saves.</li>
                <li><strong>{{ $minutes }} minutes</strong>, then it ends by itself.</li>
                <li>You see what their most senior verified back-office user sees — guests included. Look only at what the reason needs.</li>
                <li>Every page you open is recorded, and their team is told afterwards: your name, the time, and this reason.</li>
            </ul>
            <form method="POST" action="{{ route('admin.break-glass.start') }}" data-guard-submit>
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;">
                    <div>
                        <label class="ad-label" for="bg-org">Organisation</label>
                        <select id="bg-org" name="organization_id" class="ad-input" required data-searchable>
                            <option value="">Choose…</option>
                            @foreach ($organizations as $id => $name)
                                <option value="{{ $id }}" @selected(old('organization_id') === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ad-label" for="bg-code">Your 2FA code (now)</label>
                        <input id="bg-code" class="ad-input" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="10">
                    </div>
                </div>
                <label class="ad-label" for="bg-reason" style="margin-top: 12px;">Reason — they will read this</label>
                <input id="bg-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" value="{{ old('reason') }}" placeholder="e.g. Ticket #123 — the owner reports a missing booking">
                <button type="submit" class="ad-btn ad-btn--danger" style="margin-top: 12px;">Open read-only session</button>
            </form>
        </div>
    @endif

    <div class="ad-card" style="overflow-x: auto;">
        <h2 class="ad-panel-title">Recent sessions</h2>
        @if ($recent->isEmpty())
            <div class="ad-empty">No break-glass session has ever been opened.</div>
        @else
            <table class="ad-table">
                <thead><tr><th>Admin</th><th>Organisation</th><th>Reason</th><th>Started</th><th>Ended</th><th class="ad-num">Pages</th><th>Told</th></tr></thead>
                <tbody>
                    @foreach ($recent as $s)
                        <tr>
                            <td>{{ $s->platformAdmin?->name ?? '—' }}</td>
                            <td>{{ $s->organization?->name ?? '—' }}</td>
                            <td style="max-width: 280px;">{{ $s->reason }}</td>
                            <td><x-admin.time :at="$s->started_at" /></td>
                            <td>{{ $s->ended_at ? $s->ended_at->utc()->format('H:i').' · '.$s->ended_reason : ($s->isOpen() ? 'open' : 'due — not yet swept') }}</td>
                            <td class="ad-num">{{ $s->pages_viewed }}</td>
                            <td>{{ $s->tenant_notified_at ? 'Yes' : 'Not yet' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-admin.shell>
