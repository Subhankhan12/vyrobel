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
    'titleLg' => false,   // Owner-Overview exact-match — render the <h1> at the wireframe's 30px greeting size.
    'breadcrumb' => [],   // opt-in (like $subtitle): list<array{label:string,url:?string}> above the title.
    'viewer' => ['name' => __('components/owner/shell.default_viewer'), 'initials' => 'O'],
])
@php
    // Phase 7c · SG2 — each nav item carries the capability required to REACH it (5th slot). The gate reads
    // the SAME OwnerContext the route middleware ({@see \App\Http\Middleware\EnsureOwnerCapability}) reads, so
    // the sidebar can never show a sub-user a link that would 403. '' = always visible (financials / self /
    // static); 'team' = the primary owner's alone. The primary owner acts as co_owner, so they see everything.
    $R = \App\Support\Owner\OwnerTeamRole::class;
    $ownerCtx = app(\App\Support\Owner\OwnerContext::class);
    $navGroups = [
        [__('components/owner/shell.group_owner_portal'), [
            ['overview', __('components/owner/shell.nav_overview'), 'M3 10.5 12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5', route('owner.dashboard'), ''],
            ['units', __('components/owner/shell.nav_units'), 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z', route('owner.units'), $R::CAP_CALENDAR],
            ['bookings', __('components/owner/shell.nav_bookings'), 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', route('owner.bookings'), $R::CAP_CALENDAR],
            ['calendar', __('components/owner/shell.nav_calendar'), 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4', route('owner.calendar'), $R::CAP_CALENDAR],
            ['add-unit', __('components/owner/shell.nav_add_unit'), 'M12 5v14M5 12h14', route('owner.add-unit'), $R::CAP_ACTIONS],
        ]],
        [__('components/owner/shell.group_earnings'), [
            ['statements', __('components/owner/shell.nav_statements'), 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', route('owner.archive'), ''],
            ['payouts', __('components/owner/shell.nav_payouts'), 'M3 7h18v12H3zM3 11h18M15 15h3', route('owner.payouts'), ''],
            ['payout-method', __('components/owner/shell.nav_payout_method'), 'M3 7h18v10H3zM3 11h18M7 15h4', route('owner.payout-method'), $R::CAP_PAYOUT],
            ['balance', __('components/owner/shell.nav_balance'), 'M12 3v18M6 21h12M5 8h14l-3 5H8z', route('owner.balance'), ''],
            ['expenses', __('components/owner/shell.nav_expenses'), 'M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6', route('owner.expenses'), ''],
            ['documents', __('components/owner/shell.nav_documents'), 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', route('owner.documents'), $R::CAP_DOCUMENTS],
            ['tax', __('components/owner/shell.nav_tax'), 'M6 6h.01M18 18h.01M6 18 18 6', route('owner.tax'), $R::CAP_DOCUMENTS],
        ]],
        [__('components/owner/shell.group_manage'), [
            ['reports', __('components/owner/shell.nav_reports'), 'M4 20V4M4 20h16M8 16v-4M12 16V8M16 16v-6', route('owner.reports'), ''],
            ['insights', __('components/owner/shell.nav_insights'), 'M4 15l4-5 4 3 6-8', route('owner.insights'), ''],
            ['rates', __('components/owner/shell.nav_rates'), 'M4 4h9l7 7-9 9-7-7zM8 8h.01', route('owner.rates'), $R::CAP_ACTIONS],
            ['reviews', __('components/owner/shell.nav_reviews'), 'M12 4l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.6-4.8 2.6.9-5.4-3.9-3.8 5.4-.8z', route('owner.reviews'), ''],
            ['agreement', __('components/owner/shell.nav_agreement'), 'M5 4h11l3 3v13H5zM8 12c3-1 4 2 6 0M8 16h8', route('owner.agreement'), ''],
            ['messages', __('components/owner/shell.nav_messages'), 'M4 5h16v11H9l-5 4z', route('owner.messages'), ''],
        ]],
        [__('components/owner/shell.group_account'), [
            ['notifications', __('components/owner/shell.nav_notifications'), 'M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6M10 20a2 2 0 0 0 4 0', route('owner.notifications'), ''],
            ['settings', __('components/owner/shell.nav_settings'), 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c0-4 4-6 8-6s8 2 8 6', route('owner.settings'), ''],
            ['security', __('components/owner/shell.nav_security'), 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z', route('owner.security'), ''],
            ['team', __('components/owner/shell.nav_team'), 'M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM2 20c0-3 3-5 6-5s6 2 6 5M16 11a3 3 0 1 0 0-6M22 20c0-3-3-5-6-5', route('owner.team'), 'team'],
            ['help', __('components/owner/shell.nav_help'), 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM9.6 9a2.5 2.5 0 0 1 4 2c0 1.6-2 2-2 3.4M12 17h.01', route('owner.help'), ''],
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

        <div class="vy-nav">
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
                            <span class="vy-nav-item" aria-disabled="true" title="{{ __('components/owner/shell.coming_soon') }}">
                                <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                                {{ $label }}
                            </span>
                        @endif
                    @endforeach
                </nav>
            @endforeach
        </div>
    </aside>
    {{-- The owner portal scrolls the menu INSIDE the sidebar (.vy-nav), not the sidebar itself. --}}
    <x-sidebar-position for=".vy-side .vy-nav" key="owner" />

    <div class="vy-main">
        <div class="vy-topbar">
            <div>
                {{-- Opt-in breadcrumb (rendered only when given, so the screens that pass none are unchanged). --}}
                @if ($breadcrumb !== [])
                    <nav class="vy-crumbs" aria-label="{{ __('components/owner/shell.breadcrumb') }}">
                        @foreach ($breadcrumb as $i => $crumb)
                            @if ($i > 0)<span class="vy-crumb-sep" aria-hidden="true">/</span>@endif
                            @if (($crumb['url'] ?? null) !== null)
                                <a class="vy-crumb" href="{{ $crumb['url'] }}">{{ $i === 0 ? '‹ ' : '' }}{{ $crumb['label'] }}</a>
                            @else
                                <span class="vy-crumb is-current" aria-current="page">{{ $crumb['label'] }}</span>
                            @endif
                        @endforeach
                    </nav>
                @endif
                <h1 @class(['is-greeting' => $titleLg])>{{ $title }}</h1>
                {{-- Wave 3 — the owner shell gained a subtitle to match <x-operator.shell>'s. Rendered only
                     when given, so the ten screens that pass none are byte-for-byte unchanged. --}}
                @if ($subtitle !== '')
                    <div class="vy-topbar-sub">{{ $subtitle }}</div>
                @endif
            </div>
            <div class="vy-topbar-actions">
                <x-notifications.bell :open-all-url="route('owner.notifications')" />

                {{-- Account menu — a dropdown (Profile / Security / Sign out), replacing the static chip +
                     separate logout icon. Sign-out is preserved inside the menu (no capability lost). Alpine
                     popover (the Livewire-4 Alpine already loaded — no new dependency), mirrors the bell. --}}
                <div class="vy-acct" x-data="{ open: false }" @keydown.escape.window="open = false">
                    <button type="button" class="vy-acct-btn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-haspopup="true" aria-label="{{ __('components/owner/shell.account_menu') }}">
                        <span class="vy-acct-name">{{ $viewer['name'] }}</span>
                        <span class="vy-avatar">{{ $viewer['initials'] }}</span>
                        <svg class="vy-acct-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms @click.outside="open = false" class="vy-acct-menu" role="menu" aria-label="{{ __('components/owner/shell.account') }}">
                        <div class="vy-acct-head">
                            <div class="n">{{ $viewer['name'] }}</div>
                            <div class="r">{{ __('components/owner/shell.owner_portal') }}</div>
                        </div>
                        <a class="vy-acct-item" href="{{ route('owner.settings') }}" role="menuitem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                            {{ __('components/owner/shell.profile_account') }}
                        </a>
                        <a class="vy-acct-item" href="{{ route('owner.security') }}" role="menuitem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/></svg>
                            {{ __('components/owner/shell.nav_security') }}
                        </a>
                        <div class="vy-acct-sep"></div>
                        <form method="POST" action="{{ route('logout') }}" style="margin:0">
                            @csrf
                            <button type="submit" class="vy-acct-item is-danger" role="menuitem">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                                {{ __('common.sign_out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{ $slot }}
    </div>
</div>
