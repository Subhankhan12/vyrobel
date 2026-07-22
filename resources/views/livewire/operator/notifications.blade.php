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
                @php
                    // The prototype leads each row with a category icon in a soft circle. Keyed to the REAL
                    // category (finance/front_desk/maintenance/reputation/account/system); paths are constants.
                    $catIcon = match ($item['category']) {
                        'finance' => '<path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
                        'front_desk' => '<path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/><path d="M9 7h.01M15 7h.01M9 11h.01M15 11h.01M9 15h6"/>',
                        'maintenance' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z"/>',
                        'reputation' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
                        'account' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                        'system' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
                        default => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
                    };
                @endphp
                <div style="display:flex;align-items:flex-start;gap:12px;padding:13px 4px;border-bottom:1px solid var(--vy-hairline)">
                    <span aria-hidden="true" style="flex:none;width:34px;height:34px;border-radius:50%;background:rgba(232,112,58,.12);color:var(--vy-accent);display:flex;align-items:center;justify-content:center;margin-top:1px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $catIcon !!}</svg>
                    </span>
                    <a href="{{ $item['url'] ?? '#' }}" style="flex:1;text-decoration:none;color:inherit">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline">
                            <span style="font-size:13.5px;font-weight:{{ $item['read'] ? '500' : '700' }};color:var(--vy-ink)">{{ $item['title'] }}</span>
                            <span style="display:flex;align-items:center;gap:8px;flex:none">
                                <span style="font-size:10.5px;text-transform:uppercase;letter-spacing:.04em;color:var(--vy-muted)">{{ ucwords(str_replace('_', ' ', $item['category'])) }}</span>
                                @unless ($item['read'])<span style="flex:none;width:8px;height:8px;border-radius:50%;background:var(--vy-accent, #b5654a)" title="Unread"></span>@endunless
                            </span>
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
            Choose which of these reach you in <a href="{{ route('app.notification-preferences') }}" style="color:var(--vy-accent)">Notification preferences</a>.
        </p>

    </x-operator.shell>
</div>
