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
    $navGroups = [
        ['OWNER PORTAL', [
            ['overview', 'Overview', 'M3 10.5 12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5', route('owner.dashboard')],
            ['units', 'My units', 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z', route('owner.units')],
            ['bookings', 'Bookings', 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', route('owner.bookings')],
            ['calendar', 'Calendar', 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4', route('owner.calendar')],
        ]],
        ['EARNINGS', [
            ['statements', 'Statements', 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', route('owner.archive')],
            ['payouts', 'Payouts', 'M3 7h18v12H3zM3 11h18M15 15h3', route('owner.payouts')],
            ['payout-method', 'Payout method', 'M3 7h18v10H3zM3 11h18M7 15h4', route('owner.payout-method')],
            ['balance', 'Balance', 'M12 3v18M6 21h12M5 8h14l-3 5H8z', route('owner.balance')],
            ['expenses', 'Expenses', 'M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6', route('owner.expenses')],
            ['documents', 'Documents', 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', route('owner.documents')],
            ['tax', 'Tax center', 'M6 6h.01M18 18h.01M6 18 18 6', route('owner.tax')],
        ]],
        ['MANAGE', [
            ['reports', 'Reports', 'M4 20V4M4 20h16M8 16v-4M12 16V8M16 16v-6', null],
            ['insights', 'Insights', 'M4 15l4-5 4 3 6-8', null],
            ['rates', 'Rates', 'M4 4h9l7 7-9 9-7-7zM8 8h.01', route('owner.rates')],
            ['reviews', 'Reviews', 'M12 4l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.6-4.8 2.6.9-5.4-3.9-3.8 5.4-.8z', null],
            ['agreement', 'Agreement', 'M5 4h11l3 3v13H5zM8 12c3-1 4 2 6 0M8 16h8', route('owner.agreement')],
            ['messages', 'Messages', 'M4 5h16v11H9l-5 4z', route('owner.messages')],
        ]],
        ['ACCOUNT', [
            ['settings', 'Settings', 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2', route('owner.settings')],
            ['security', 'Security', 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z', null],
            ['team', 'Team & access', 'M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM2 20c0-3 3-5 6-5s6 2 6 5M16 11a3 3 0 1 0 0-6M22 20c0-3-3-5-6-5', null],
            ['help', 'Help & support', 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM9.6 9a2.5 2.5 0 0 1 4 2c0 1.6-2 2-2 3.4M12 17h.01', null],
        ]],
    ];
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
                @foreach ($items as [$key, $label, $icon, $href])
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
                <span class="vy-bell" title="Notifications — coming soon" aria-label="Notifications">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6M10 20a2 2 0 0 0 4 0"/></svg>
                </span>
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
