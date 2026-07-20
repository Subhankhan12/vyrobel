{{-- Operator notification feed (Phase 3 · Sub-gate 1) — the AUTHENTICATED user's OWN notifications (per-user
     Notifiable relation). Each row links to the real screen; mark-read / mark-all-read are POST → controller.
     `?filter=` (category) rides GET. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="notifications" title="Notifications" :subtitle="$feed['unread'].' unread'" :viewer="$viewer">

        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px">
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="{{ route('app.notifications') }}" class="vy-btn {{ $filter === 'all' ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:6px 12px;font-size:12.5px">All</a>
                @foreach ($feed['categories'] as $cat)
                    <a href="{{ route('app.notifications', ['filter' => $cat]) }}" class="vy-btn {{ $filter === $cat ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:6px 12px;font-size:12.5px">{{ ucwords(str_replace('_', ' ', $cat)) }}</a>
                @endforeach
            </div>
            @if ($feed['unread'] > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px">Mark all read</button>
                </form>
            @endif
        </div>

        <div class="vy-card vy-fade">
            @forelse ($feed['items'] as $item)
                <div style="display:flex;align-items:flex-start;gap:12px;padding:13px 4px;border-bottom:1px solid var(--vy-hairline)">
                    @unless ($item['read'])<span style="flex:none;width:8px;height:8px;border-radius:50%;background:var(--vy-accent, #b5654a);margin-top:6px" title="Unread"></span>@else<span style="flex:none;width:8px;height:8px;margin-top:6px"></span>@endunless
                    <a href="{{ $item['url'] ?? '#' }}" style="flex:1;text-decoration:none;color:inherit">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline">
                            <span style="font-size:13.5px;font-weight:{{ $item['read'] ? '500' : '700' }};color:var(--vy-ink)">{{ $item['title'] }}</span>
                            <span style="font-size:10.5px;text-transform:uppercase;letter-spacing:.04em;color:var(--vy-muted)">{{ ucwords(str_replace('_', ' ', $item['category'])) }}</span>
                        </div>
                        <div style="font-size:12.5px;color:var(--vy-muted);margin-top:2px">{{ $item['context'] }}</div>
                        <div style="font-size:11px;color:var(--vy-faint);margin-top:3px">{{ $item['when'] }}</div>
                    </a>
                    @unless ($item['read'])
                        <form method="POST" action="{{ route('notifications.read', $item['id']) }}" style="margin:0;flex:none">
                            @csrf
                            <button type="submit" class="vy-btn vy-btn--ghost" style="padding:4px 9px;font-size:11px" title="Mark read">✓</button>
                        </form>
                    @endunless
                </div>
            @empty
                <div class="vy-empty">All caught up — no notifications.</div>
            @endforelse
        </div>

        <p class="vy-line-sub" style="font-size:12px;margin-top:14px;color:var(--vy-muted)">
            Per-event email preferences arrive next.
        </p>

    </x-operator.shell>
</div>
