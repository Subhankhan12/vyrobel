{{-- Owner-portal shell (design pass) — the shared sidebar + topbar chrome, extracted from Screen 1 so
     every owner screen inherits ONE nav/topbar. Presentation only.

     Props:
       active  — the nav key to highlight ('overview' | 'statements' | …)
       title    — the topbar <h1>
       subtitle — a small line under the title (Wave 3; optional, mirrors <x-operator.shell>)
       viewer   — ['name' => string, 'initials' => string] (the authenticated user, for the chip)

     Nav honesty: only items with a real route navigate; the active item is highlighted (non-link);
     every other item is a non-interactive placeholder for a screen not built yet (never a fake link). --}}
@props([
    'active' => '',
    'title' => '',
    'subtitle' => '',
    'viewer' => ['name' => 'Owner', 'initials' => 'O'],
])
@php
    // Phase 7c · SG2 — each nav item carries the capability required to REACH it (5th slot). The gate reads
    // the SAME OwnerContext the route middleware ({@see \App\Http\Middleware\EnsureOwnerCapability}) reads, so
    // the sidebar can never show a sub-user a link that would 403. '' = always visible (financials / self /
    // static); 'team' = the primary owner's alone. The primary owner acts as co_owner, so they see everything.
    $R = \App\Support\Owner\OwnerTeamRole::class;
    $ownerCtx = app(\App\Support\Owner\OwnerContext::class);
    $navGroups = [
        ['OWNER PORTAL', [
            ['overview', 'Overview', 'M3 10.5 12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5', route('owner.dashboard'), ''],
            ['units', 'My units', 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z', route('owner.units'), $R::CAP_CALENDAR],
            ['bookings', 'Bookings', 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', route('owner.bookings'), $R::CAP_CALENDAR],
            ['calendar', 'Calendar', 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4', route('owner.calendar'), $R::CAP_CALENDAR],
            ['add-unit', 'Add a unit', 'M12 5v14M5 12h14', route('owner.add-unit'), $R::CAP_ACTIONS],
        ]],
        ['EARNINGS', [
            ['statements', 'Statements', 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', route('owner.archive'), ''],
            ['payouts', 'Payouts', 'M3 7h18v12H3zM3 11h18M15 15h3', route('owner.payouts'), ''],
            ['payout-method', 'Payout method', 'M3 7h18v10H3zM3 11h18M7 15h4', route('owner.payout-method'), $R::CAP_PAYOUT],
            ['balance', 'Balance', 'M12 3v18M6 21h12M5 8h14l-3 5H8z', route('owner.balance'), ''],
            ['expenses', 'Expenses', 'M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6', route('owner.expenses'), ''],
            ['documents', 'Documents', 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', route('owner.documents'), $R::CAP_DOCUMENTS],
            ['tax', 'Tax center', 'M6 6h.01M18 18h.01M6 18 18 6', route('owner.tax'), $R::CAP_DOCUMENTS],
        ]],
        ['MANAGE', [
            ['reports', 'Reports', 'M4 20V4M4 20h16M8 16v-4M12 16V8M16 16v-6', route('owner.reports'), ''],
            ['insights', 'Insights', 'M4 15l4-5 4 3 6-8', route('owner.insights'), ''],
            ['rates', 'Rates', 'M4 4h9l7 7-9 9-7-7zM8 8h.01', route('owner.rates'), $R::CAP_ACTIONS],
            ['reviews', 'Reviews', 'M12 4l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.6-4.8 2.6.9-5.4-3.9-3.8 5.4-.8z', route('owner.reviews'), ''],
            ['agreement', 'Agreement', 'M5 4h11l3 3v13H5zM8 12c3-1 4 2 6 0M8 16h8', route('owner.agreement'), ''],
            ['messages', 'Messages', 'M4 5h16v11H9l-5 4z', route('owner.messages'), ''],
        ]],
        ['ACCOUNT', [
            ['notifications', 'Notifications', 'M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6M10 20a2 2 0 0 0 4 0', route('owner.notifications'), ''],
            ['settings', 'Settings', 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2', route('owner.settings'), ''],
            ['security', 'Security', 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z', null, ''],
            ['team', 'Team & access', 'M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM2 20c0-3 3-5 6-5s6 2 6 5M16 11a3 3 0 1 0 0-6M22 20c0-3-3-5-6-5', null, 'team'],
            ['help', 'Help & support', 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM9.6 9a2.5 2.5 0 0 1 4 2c0 1.6-2 2-2 3.4M12 17h.01', route('owner.help'), ''],
        ]],
    ];
    // Drop items the acting role can't reach; drop a whole group if nothing survives.
    $navGroups = array_values(array_filter(array_map(function ($grp) use ($ownerCtx) {
        [$label, $items] = $grp;
        $items = array_values(array_filter($items, fn ($it) => $it[4] === ''
            || ($it[4] === 'team' ? $ownerCtx->isPrimaryOwner() : $ownerCtx->can($it[4]))));

        return [$label, $items];
    }, $navGroups), fn ($grp) => $grp[1] !== []));
@endphp

<div class="vy-shell">
    <aside class="vy-side">
        <div class="vy-brand">
            <span class="vy-brand-mark">V</span>
            <span class="vy-brand-name">Vyrobel</span>
        </div>

        @foreach ($navGroups as [$group, $items])
            <nav class="vy-nav-group" aria-label="{{ $group }}">
                <span class="vy-nav-label">{{ $group }}</span>
                @foreach ($items as [$key, $label, $icon, $href, $cap])
                    @php $isActive = $key === $active; @endphp
                    @if ($isActive)
                        <span class="vy-nav-item is-active" aria-current="page">
                            <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </span>
                    @elseif ($href)
                        <a class="vy-nav-item" href="{{ $href }}">
                            <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </a>
                    @else
                        <span class="vy-nav-item" aria-disabled="true" title="Coming soon">
                            <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </span>
                    @endif
                @endforeach
            </nav>
        @endforeach
    </aside>

    <div class="vy-main">
        <div class="vy-topbar">
            <div>
                <h1>{{ $title }}</h1>
                {{-- Wave 3 — the owner shell gained a subtitle to match <x-operator.shell>'s. Rendered only
                     when given, so the ten screens that pass none are byte-for-byte unchanged. --}}
                @if ($subtitle !== '')
                    <div class="vy-topbar-sub">{{ $subtitle }}</div>
                @endif
            </div>
            <div class="vy-topbar-actions">
                @php $unread = auth()->user()?->unreadNotifications()->count() ?? 0; @endphp
                <a href="{{ route('owner.notifications') }}" class="vy-bell" title="Notifications" aria-label="Notifications" style="position:relative;text-decoration:none;color:inherit">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6M10 20a2 2 0 0 0 4 0"/></svg>
                    @if ($unread > 0)<span aria-label="{{ $unread }} unread" style="position:absolute;top:-3px;right:-3px;min-width:15px;height:15px;padding:0 3px;border-radius:999px;background:var(--vy-accent);color:#fff;font-size:9.5px;font-weight:700;display:grid;place-items:center">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
                </a>
                <span class="vy-chip">
                    <span class="vy-avatar">{{ $viewer['initials'] }}</span>
                    <span class="vy-chip-name">{{ $viewer['name'] }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="vy-icobtn" title="Sign out" aria-label="Sign out">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    </button>
                </form>
            </div>
        </div>

        {{ $slot }}
    </div>
</div>
