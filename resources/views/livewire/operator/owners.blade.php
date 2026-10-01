{{-- Operator OWNERS — rebuilt 2026-10-01 (the founder: "on Add owner page, modal design and buttons are mess, cancel and send
     invite buttons look bad, confirm with your password and its box is not well aligned … make it more creative and
     operational and easy to use and fully functional, the same way we worked on the last pages").

     A list like Guests and Units (search, chips that count, sortable columns, a row menu, an export — the shared list script
     swaps the chips and the results in place; every link and form works without it), the invites waiting with Resend and
     Cancel, and the side panel saying only what the system does. Every row comes from App\Support\Owner\OwnerRows.

     Writes: the invite (InviteOwnerController → OwnerInviteService), resend and cancel (OwnerInviteActionController → the
     same service), pause/resume payouts (OwnerManageController). Each a POST through every middleware (#20); each offered
     only where its route would accept it — `users.manage`, and not while the workspace is paused.

     ⚠️ Every dialog sits on the shared centred backdrop and every display is a CLASS — x-show erases an inline display.
     ⚠️ RAW-BLADE RULE: never `word@directive`. Alpine handlers use `x-on:` / `x-bind:`. --}}
<div>
    <x-operator.shell active="owners" title="Owners" subtitle="Owners, their statements and management agreements" :viewer="$viewer">
        @php
            // Which dialog a refused write reopens: the form says which it was (`_form`), and its typed values come back.
            $vyForm = old('_form');
            $vyInviteRetry = $canWrite && $vyForm === 'invite' && ($errors->any() || session('owners_error'));
            $vyResendRetry = $canWrite && $vyForm === 'resend' && $errors->any() ? (string) old('_invite') : '';
            $vyResendInvite = null;
            if ($vyResendRetry !== '') {
                foreach ($invites as $vyInvite) {
                    if ($vyInvite['id'] === $vyResendRetry) {
                        $vyResendInvite = ['id' => $vyInvite['id'], 'email' => $vyInvite['email'], 'name' => $vyInvite['name']];
                    }
                }
                // an expired invite is not in the waiting list — its owner's row holds it
                foreach ($rowsById as $vyRow) {
                    if ($vyResendInvite === null && ($vyRow['invite']['id'] ?? null) === $vyResendRetry) {
                        $vyResendInvite = ['id' => $vyResendRetry, 'email' => $vyRow['email'], 'name' => $vyRow['name']];
                    }
                }
            }
            // In the invite dialog each field says its own refusal; the box at its top carries only what has no field.
            $vyInviteFields = ['name', 'email', 'unit_ids', 'commission_pct', 'current_password'];
            $vyInviteTop = $vyInviteRetry ? collect($errors->getMessages())->except($vyInviteFields)->flatten()->all() : [];
            // Focus goes where the person has to act: the first field refused, or the name on a fresh dialog.
            $vyInviteFocus = 'vy-lbl-owners-name';
            foreach (['current_password' => 'invite_pw', 'name' => 'vy-lbl-owners-name', 'email' => 'vy-lbl-owners-email'] as $vyKey => $vyId) {
                if ($vyInviteRetry && $errors->has($vyKey)) {
                    $vyInviteFocus = $vyId;
                    break;
                }
            }
            // a refusal no dialog can show (the dialog's row has gone) is shown at the top instead
            $vyErrorsOnTop = $errors->any() && ! $vyInviteRetry && $vyResendInvite === null;
            $vySent = session('owner_invite_sent');
        @endphp

        {{-- Flashes — a success is a status, a refusal an alert. A refusal that reopens its dialog is shown in the dialog. --}}
        @if (session('owners_notice') && ! $vySent)
            <div class="vy-flash is-ok vy-fade" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                {{ session('owners_notice') }}
            </div>
        @endif
        @if ((session('owners_error') && ! $vyInviteRetry) || $vyErrorsOnTop)
            <div class="vy-flash is-err vyo-flash vy-fade" role="alert">
                @if (session('owners_error') && ! $vyInviteRetry)
                    <div>{{ session('owners_error') }}</div>
                @endif
                @if ($vyErrorsOnTop)
                    @foreach ($errors->all() as $vyError)
                        <div>{{ $vyError }}</div>
                    @endforeach
                @endif
            </div>
        @endif

        {{-- The pay-status chips. ⚠️ CONTRAST-PINNED (DataConditionalContrastTest, AXIS 3): --hold/--due on the accent ink,
             --draft on the amber ink, --paid left at its measured #1F6E4B. Change a colour here only with a new measurement. --}}
        <style>
            .vy-paystatus { display: inline-flex; align-items: center; font-size: 11px; font-weight: 600; line-height: 1.5; padding: 2px 9px; border-radius: 20px; white-space: nowrap; background: var(--vy-hairline); color: var(--vy-muted); }
            .vy-paystatus--paid { background: rgba(31,110,75,.14); color: #1F6E4B; }
            .vy-paystatus--due { background: rgba(217,102,46,.14); color: var(--vy-accent-text); }
            .vy-paystatus--hold { background: rgba(180,64,15,.14); color: var(--vy-accent-text); }
            .vy-paystatus--draft { background: rgba(224,146,30,.16); color: var(--vy-amber-text); }
            .vy-paystatus--paused { background: rgba(120,70,40,.10); color: var(--vy-on-grad); }
            .vy-paystatus--invited { background: rgba(232,112,58,.10); color: var(--vy-accent-text); }
        </style>
        @can('users.manage')
            {{-- ⚠️ CONTRAST-PINNED (RoleCoverageContrastTest): the dialog's labels on the muted token — and only the roles that
                 receive the dialog receive this rule. --}}
            <style>
                .vy-inv-lbl { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px; }
            </style>
        @endcan

        <div class="vy-gl-root vyo-root" x-data="vyOwners(@js($boot))">

            {{-- ── Four figures that agree with the rows below ────────────────────────────────────────────────── --}}
            <div class="vy-dash-kpis vy-fade">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Owners</div>
                    <div class="vy-dk-val">{{ $kpis['owners'] }}</div>
                    <div class="vy-dk-sub">{{ $kpis['owners_sub'] }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Ready to pay</div>
                    <div class="vy-dk-val">{{ $kpis['ready'][0] }}</div>
                    <div class="vy-dk-sub">{{ count($kpis['ready']) > 1 ? implode(' · ', array_slice($kpis['ready'], 1)).' · ' : '' }}{{ $kpis['ready_sub'] }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">On hold</div>
                    <div class="vy-dk-val">{{ $kpis['hold'] }}</div>
                    <div class="vy-dk-sub">{{ $kpis['hold_sub'] }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Needs review</div>
                    <div class="vy-dk-val">{{ $kpis['review'] }}</div>
                    <div class="vy-dk-sub">{{ $kpis['review_sub'] }}</div>
                </div>
            </div>

            {{-- ── The toolbar — search · Export · Add owner, then the chips ─────────────────────────────────────── --}}
            <div class="vy-gl-bar vyo-bar vy-fade">
                {{-- Without script this is a plain GET form that keeps the other settings (the hidden fields); with it, the
                     results follow the typing. --}}
                <form class="vy-gl-search" method="GET" action="{{ route('app.owners') }}" role="search" x-on:submit.prevent="search()">
                    @foreach ($keep as $keepName => $keepValue)
                        <input type="hidden" name="{{ $keepName }}" value="{{ $keepValue }}">
                    @endforeach
                    <svg class="vy-gl-search-ico" x-show="! loading" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    <span class="vy-gl-spinner" x-show="loading" x-cloak aria-hidden="true"></span>
                    <label class="vy-sr-only" for="vyo-q">Search owners</label>
                    <input id="vyo-q" class="vy-gl-search-in" type="search" name="q" value="{{ $query }}" x-model="q"
                           x-on:input.debounce.300ms="search()" placeholder="Search owner, email or unit" maxlength="80" autocomplete="off" spellcheck="false">
                    <button type="button" class="vy-gl-clear" x-show="q !== ''" x-cloak x-on:click="clearSearch()" aria-label="Clear search">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </form>

                <div class="vy-gl-tools">
                    {{-- each control gated on the permission its route requires ("never invite a 403") --}}
                    @if ($total > 0)
                        {{-- ⚠️ outside the swapped regions, so it takes the view's address when clicked — a filter or sort changed in
                             place would otherwise export the view the page was LOADED with --}}
                        <a class="vy-btn vy-btn--ghost" href="{{ $exportUrl }}" data-export-base="{{ route('app.owners.export') }}"
                           x-on:click="$el.href = $el.dataset.exportBase + location.search">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>
                            Export
                        </a>
                    @endif
                    @if ($canWrite)
                        <button type="button" class="vy-btn vy-btn--accent" x-on:click="openInvite()">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            Add owner
                        </button>
                    @endif
                </div>

                <div class="vy-gl-chips vyo-chips" data-gl-region="chips" role="group" aria-label="Show">
                    @foreach ($chips as $chip)
                        <a class="vy-gl-chip {{ $chip['active'] ? 'is-on' : '' }}" href="{{ $chip['href'] }}" data-gl-key="chip-{{ $chip['key'] }}" @if ($chip['active']) aria-current="page" @endif>{{ $chip['label'] }}<span class="vy-gl-count">{{ $chip['count'] }}</span></a>
                    @endforeach
                </div>
            </div>

            {{-- Said after every change, for screen readers — the new position in the list. --}}
            <p class="vy-sr-only" aria-live="polite" x-text="live"></p>

            <div class="vyo-layout">
                <div class="vyo-main">
                    {{-- ── The owners — swapped in place by the script, and a whole page without it ──────────────────── --}}
                    <div class="vy-gl-results" data-gl-region="results" x-bind:class="loading && 'is-loading'">
                        @if ($total === 0)
                            <div class="vy-card vy-gl-empty vy-fade">
                                <span class="vy-gl-empty-ico" aria-hidden="true">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.4"/><path d="M2.5 20c.8-3.2 3.4-5 6.5-5c1 0 2 .2 2.8.5"/><path d="M17 8.5v6M14 11.5h6"/></svg>
                                </span>
                                <h2 class="vy-gl-empty-title">No owners yet</h2>
                                <p class="vy-gl-empty-text">Invite the people who own units here. They set a password, then see their statements and payouts in their own portal.</p>
                                @if ($canWrite)
                                    <button type="button" class="vy-btn vy-btn--accent" x-on:click="openInvite()">Add owner</button>
                                @endif
                            </div>
                        @elseif ($rows->total() === 0)
                            <div class="vy-card vy-gl-empty vy-fade">
                                <span class="vy-gl-empty-ico" aria-hidden="true">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                                </span>
                                <h2 class="vy-gl-empty-title">
                                    @if ($query !== '') No owners match “{{ $query }}” @else No owners in this view @endif
                                </h2>
                                <p class="vy-gl-empty-text">Search looks at the owner’s name and email and the units they own. {{ $total }} {{ \Illuminate\Support\Str::plural('owner', $total) }} in all.</p>
                                <a class="vy-btn vy-btn--ghost" href="{{ route('app.owners') }}" data-gl-key="clear-all">Clear search and filters</a>
                            </div>
                        @else
                            <div class="vy-card vy-gl-card vy-fade">
                                <div class="vy-gl-progress" aria-hidden="true"></div>
                                <div class="vy-gl-scroll">
                                    <table class="vy-gl-table vyo-table">
                                        <caption class="vy-sr-only">Owners, {{ $sortedBy }}</caption>
                                        <thead>
                                            <tr>
                                                <th aria-sort="{{ $sorts['name']['aria'] }}">
                                                    <a class="vy-gl-sort {{ $sorts['name']['active'] ? 'is-on is-'.$sorts['name']['dir'] : '' }}" href="{{ $sorts['name']['href'] }}" data-gl-key="sort-name">Owner<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                                </th>
                                                <th class="is-num" aria-sort="{{ $sorts['units']['aria'] }}">
                                                    <a class="vy-gl-sort {{ $sorts['units']['active'] ? 'is-on is-'.$sorts['units']['dir'] : '' }}" href="{{ $sorts['units']['href'] }}" data-gl-key="sort-units">Units<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                                </th>
                                                <th>Commission</th>
                                                <th class="is-num" aria-sort="{{ $sorts['net']['aria'] }}">
                                                    <a class="vy-gl-sort {{ $sorts['net']['active'] ? 'is-on is-'.$sorts['net']['dir'] : '' }}" href="{{ $sorts['net']['href'] }}" data-gl-key="sort-net">Net payable<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                                </th>
                                                <th aria-sort="{{ $sorts['status']['aria'] }}">
                                                    <a class="vy-gl-sort {{ $sorts['status']['active'] ? 'is-on is-'.$sorts['status']['dir'] : '' }}" href="{{ $sorts['status']['href'] }}" data-gl-key="sort-status">Status<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                                </th>
                                                <th class="vy-gl-actcol"><span class="vy-sr-only">Actions</span></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($rows as $row)
                                                <tr data-gl-id="{{ $row['id'] }}" class="{{ $row['active'] ? '' : 'is-invited' }}">
                                                    <td class="vyo-ownercell">
                                                        <div class="vyo-owner">
                                                            <span class="vy-owner-avatar vyo-avatar" aria-hidden="true">{{ $row['initials'] }}</span>
                                                            <div class="vyo-who">
                                                                <a class="vy-gl-name" href="{{ route('app.owners.show', $row['id']) }}">{{ $row['name'] }}</a>
                                                                @if ($row['email'] !== '')
                                                                    <div class="vy-gl-contact"><span>{{ $row['email'] }}</span></div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="is-num">
                                                        <span class="vy-gl-fig">{{ $row['units'] }}</span>
                                                        @if ($row['units'] > 0)
                                                            <div class="vyo-units" title="{{ implode(', ', $row['unit_labels']) }}">{{ implode(', ', array_slice($row['unit_labels'], 0, 3)) }}{{ $row['units'] > 3 ? ' +'.($row['units'] - 3) : '' }}</div>
                                                        @endif
                                                    </td>
                                                    <td><span class="vy-gl-fig">{{ $row['commission'] }}</span></td>
                                                    {{-- what the latest closed statement leaves them, and which month it is --}}
                                                    <td class="is-num">
                                                        <span class="vy-gl-fig vyo-net">{{ $row['net'] }}</span>
                                                        @if ($row['period'] !== null)
                                                            <div class="vyo-period">{{ $row['period'] }} statement</div>
                                                        @endif
                                                    </td>
                                                    <td class="vyo-statuscell">
                                                        <span class="vy-paystatus vy-paystatus--{{ $row['pay_key'] }}">{{ $row['pay_label'] }}</span>
                                                        @if ($row['reason'])
                                                            <div class="vyo-reason">{{ $row['reason'] }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="vy-gl-actcol">
                                                        <button type="button" class="vy-gl-more" aria-label="Actions for {{ $row['name'] }}" aria-controls="vyo-menu"
                                                                x-bind:aria-expanded="menuFor === '{{ $row['id'] }}' ? 'true' : 'false'" x-on:click="openMenu($event, row('{{ $row['id'] }}'))">
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5.5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="18.5" cy="12" r="1.7"/></svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="vy-gl-foot" data-gl-foot>
                                    <p class="vy-gl-summary" data-gl-summary tabindex="-1"><strong>{{ $rows->firstItem() }}–{{ $rows->lastItem() }}</strong> of <strong>{{ $rows->total() }}</strong> {{ \Illuminate\Support\Str::plural('owner', $rows->total()) }}<span class="vy-gl-sorted"> · {{ $sortedBy }}</span></p>
                                    {{-- the page size matters only once there is more than one page of the smallest size --}}
                                    @if ($rows->total() > \App\Support\Owner\OwnerListParams::PER_PAGE)
                                        <div class="vy-gl-paging">
                                            <span class="vy-gl-rows-label" id="vyo-rows">Rows</span>
                                            <div class="vy-gl-sizes" role="group" aria-labelledby="vyo-rows">
                                                @foreach ($sizes as $size)
                                                    <a class="vy-gl-size {{ $size['active'] ? 'is-on' : '' }}" href="{{ $size['href'] }}" data-gl-key="per-{{ $size['size'] }}" @if ($size['active']) aria-current="true" @endif>{{ $size['size'] }}</a>
                                                @endforeach
                                            </div>
                                            @if ($rows->lastPage() > 1)
                                                <nav class="vy-gl-pages" aria-label="Pages">
                                                    @if ($pages['prev'] !== null)
                                                        <a class="vy-gl-page" href="{{ $pages['prev'] }}" data-gl-key="page-prev" rel="prev" aria-label="Previous page">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                                                        </a>
                                                    @else
                                                        <span class="vy-gl-page is-off" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></span>
                                                    @endif
                                                    <span class="vy-gl-pagepos">Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}</span>
                                                    @if ($pages['next'] !== null)
                                                        <a class="vy-gl-page" href="{{ $pages['next'] }}" data-gl-key="page-next" rel="next" aria-label="Next page">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                                                        </a>
                                                    @else
                                                        <span class="vy-gl-page is-off" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></span>
                                                    @endif
                                                </nav>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        {{-- The rows on screen, by id — the row menu and the dialogs read them; swapped with the table. --}}
                        <script type="application/json" data-ow-rows>@json($rowsById)</script>
                    </div>

                    {{-- ── Invites waiting — owners AND staff, since the dialog sends both ──────────────────────────── --}}
                    @if (count($invites) > 0)
                        <section class="vy-card vyo-invites vy-fade" id="vyo-invites" aria-labelledby="vyo-invites-t">
                            <div class="vyo-section-head">
                                <h2 class="vy-card-title" id="vyo-invites-t">Invites waiting <span class="vyo-count">{{ count($invites) }}</span></h2>
                                <p class="vyo-section-sub">Each person sets a password from the link in their email. Until then the link can be sent again or cancelled.</p>
                            </div>
                            @foreach ($invites as $invite)
                                <div class="vyo-invite" x-data="{ confirm: false }">
                                    <span class="vy-owner-avatar vyo-avatar" aria-hidden="true">{{ $invite['initials'] }}</span>
                                    <div class="vyo-invite-main">
                                        <div class="vyo-invite-name">{{ $invite['name'] }} <span class="vyo-role">{{ $invite['role'] }}</span></div>
                                        <div class="vyo-invite-meta">
                                            <span>{{ $invite['email'] }}</span>
                                            <span>Sent {{ $invite['sent'] }}</span>
                                            <span class="{{ $invite['expired'] ? 'is-late' : '' }}">{{ $invite['expired'] ? 'Link expired' : ($invite['expires'] ? 'expires '.$invite['expires'] : 'pending') }}</span>
                                            @if ($invite['units'] > 0)<span>{{ $invite['units'] }} {{ \Illuminate\Support\Str::plural('unit', $invite['units']) }}</span>@endif
                                            @if ($invite['commission'])<span>{{ $invite['commission'] }} commission</span>@endif
                                        </div>
                                    </div>
                                    @if ($canWrite)
                                        <div class="vyo-invite-actions" x-show="! confirm">
                                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="openResend({{ \Illuminate\Support\Js::from(['id' => $invite['id'], 'email' => $invite['email'], 'name' => $invite['name']]) }})">Resend</button>
                                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm is-danger" x-on:click="confirm = true">Cancel</button>
                                        </div>
                                        {{-- Cancelling takes access away, so it asks once, here, and needs no password. --}}
                                        <form method="POST" action="{{ $invite['cancel'] }}" class="vyo-invite-actions vyo-confirmcancel" x-show="confirm" x-cloak x-on:submit="stamp($event.target)" data-guard-submit>
                                            @csrf
                                            <input type="hidden" name="return" value="list">
                                            <input type="hidden" name="return_query" value="">
                                            <span class="vyo-ask">Cancel this invite?</span>
                                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="confirm = false">Keep</button>
                                            <button type="submit" class="vy-btn vy-btn--danger vyo-sm">Cancel invite</button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </section>
                    @endif
                </div>

                {{-- ── The side panel — money in motion, how owners are paid, the portal ───────────────────────────── --}}
                <aside class="vyo-rail" aria-label="Payouts and the owner portal">
                    <div class="vy-tonight vyo-payout vy-fade">
                        <div class="vy-dk-label vyo-payout-label">Payout run · this period</div>
                        <div class="vyo-payout-val">{{ $payout['ready'][0] }}</div>
                        <div class="vyo-payout-sub">{{ $payout['due'] }} {{ \Illuminate\Support\Str::plural('owner', $payout['due']) }} ready{{ $payout['hold'] > 0 ? ' · '.$payout['hold'].' on hold' : '' }}{{ $payout['paused'] > 0 ? ' · '.$payout['paused'].' paused' : '' }}</div>
                        @can('payouts.manage')
                            <a class="vyo-payout-link" href="{{ route('app.payouts.run') }}">Open the payout run</a>
                        @endcan
                    </div>

                    {{-- ⚠️ ONLY WHAT THE SYSTEM DOES. This card said "Statements finalize 1st of the month" (nothing does) and "Payouts
                         sent 3rd business day" (only while the automatic run is on — it is off unless PAYOUT_ENGINE_ENABLED). --}}
                    <section class="vy-card vyo-howpaid vy-fade" aria-labelledby="vyo-howpaid-t">
                        <h2 class="vy-card-title" id="vyo-howpaid-t">How owners are paid</h2>
                        <dl class="vyo-facts">
                            <div>
                                <dt>Statements</dt>
                                <dd>Close when you press <strong>Close period</strong> on an owner — nothing closes them on its own.</dd>
                            </div>
                            <div>
                                <dt>Payouts</dt>
                                @if ($howPaid['engine'])
                                    <dd>Sent automatically on the <strong>{{ $howPaid['schedule'] }}</strong>{{ $howPaid['own'] > 0 ? ' · '.$howPaid['own'].' '.\Illuminate\Support\Str::plural('owner', $howPaid['own']).' set their own schedule' : '' }}.</dd>
                                @else
                                    <dd>Sent by your team from the payout run — the automatic run is off.</dd>
                                @endif
                            </div>
                            <div>
                                <dt>Commission</dt>
                                @if ($howPaid['commission'] === '—')
                                    <dd>No agreement yet — set a rate when you invite an owner, or later on their page.</dd>
                                @elseif ($howPaid['commission'] === 'varies')
                                    <dd><strong>Varies by owner</strong> — each owner’s agreement sets their share of gross booking value.</dd>
                                @else
                                    <dd><strong>{{ $howPaid['commission'] }} of gross</strong> booking value, from each owner’s agreement.</dd>
                                @endif
                            </div>
                        </dl>
                    </section>

                    <section class="vy-card vyo-portal vy-fade" aria-labelledby="vyo-portal-t">
                        <h2 class="vy-dk-label" id="vyo-portal-t">Owner portal</h2>
                        <div class="vyo-portal-figure">
                            <strong>{{ $portal['active'] }} of {{ $portal['total'] }}</strong>
                            <span>{{ \Illuminate\Support\Str::plural('owner', $portal['total']) }} active</span>
                        </div>
                        <div class="vyo-portal-track" role="img" aria-label="{{ $portal['active'] }} of {{ $portal['total'] }} owners active in the portal">
                            <span class="vyo-portal-fill" style="width: {{ $portal['total'] > 0 ? (int) round($portal['active'] / $portal['total'] * 100) : 0 }}%;"></span>
                        </div>
                        <p class="vyo-portal-sub">{{ count($invites) }} {{ \Illuminate\Support\Str::plural('invite', count($invites)) }} waiting · owners see a statement and its payout the moment it closes.</p>
                        @can('users.manage')
                            <a class="vyo-portal-link" href="{{ route('app.owners.invite.preview') }}" target="_blank" rel="noopener">See the invite email</a>
                        @endcan
                    </section>
                </aside>
            </div>

            {{-- ── The one row menu ──────────────────────────────────────────────────────────────────────────────── --}}
            <div class="vy-gl-menu" id="vyo-menu" x-show="menu.open" x-cloak x-bind:class="menu.up && 'is-up'"
                 x-bind:style="{ left: menu.x + 'px', top: menu.y + 'px' }" role="menu" aria-label="Owner actions"
                 x-on:keydown.escape.prevent="closeMenu(true)" x-on:focusout="leaveMenu($event)">
                <a class="vy-gl-menu-item" role="menuitem" x-bind:href="menu.row ? link(urls.show, menu.row.id) : '#'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    Open owner
                </a>
                <button type="button" class="vy-gl-menu-item" role="menuitem" x-show="menu.row && menu.row.email" x-on:click="copyEmail()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                    Copy email
                </button>
                <button type="button" class="vy-gl-menu-item" role="menuitem" x-show="can('resend')" x-on:click="menuAct('resend')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
                    Send the invite again
                </button>
                <span class="vy-gl-menu-sep" x-show="can('pause') || can('resume')" aria-hidden="true"></span>
                <button type="button" class="vy-gl-menu-item is-danger" role="menuitem" x-show="can('pause')" x-on:click="menuAct('pause')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/></svg>
                    Pause payouts
                </button>
                <button type="button" class="vy-gl-menu-item" role="menuitem" x-show="can('resume')" x-on:click="menuAct('resume')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 8.5l5 3.5-5 3.5z"/></svg>
                    Resume payouts
                </button>
            </div>
            <p class="vyo-copied" x-show="copied" x-cloak role="status" x-text="copied"></p>

            @if ($canWrite)
                {{-- ══ ADD OWNER — the founder's report: the password on its own row, Cancel and Send invite together ══════
                     The #80 invite flow, unchanged behind it. A refusal reopens this with what was typed (`_form=invite`). --}}
                <div class="vy-modal-backdrop" x-cloak x-show="inviteOpen" x-transition.opacity x-on:click.self="inviteOpen = false" x-on:keydown.escape.window="inviteOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg" x-show="inviteOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-invite-title" tabindex="-1" x-trap.noscroll="inviteOpen">
                        <form method="POST" action="{{ route('app.owners.invite') }}" x-on:submit="stamp($event.target)" data-guard-submit>
                            @csrf
                            <input type="hidden" name="_form" value="invite">
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <input type="hidden" name="commission_pct" x-bind:value="commissionValue()">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.4"/><path d="M2.5 20c.8-3.2 3.4-5 6.5-5c1 0 2 .2 2.8.5"/><path d="M17 8.5v6M14 11.5h6"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vy-invite-title" x-text="roleTitle()">Invite an owner</h3>
                                    <p class="vyo-dlg-sub">They get an email with a link to set a password. Nothing is shared until they do.</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="inviteOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>

                            <div class="vy-modal-body">
                                @if ($vyInviteRetry && (session('owners_error') || $vyInviteTop !== []))
                                    <div class="vyo-errors" role="alert">
                                        @if (session('owners_error'))
                                            <div>{{ session('owners_error') }}</div>
                                        @endif
                                        @foreach ($vyInviteTop as $vyError)
                                            <div>{{ $vyError }}</div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- who they are --}}
                                <fieldset class="vy-choices vyo-roles">
                                    <legend class="vy-inv-lbl">They are</legend>
                                    <div class="vy-choice-row">
                                        <label class="vy-choice">
                                            <input type="radio" name="role" value="owner" x-model="role">
                                            <span class="vy-choice-face">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9.5 21v-5h5v5"/></svg>
                                                <span class="vy-choice-text">Owner</span>
                                                <span class="vyo-choice-sub">Owns units · sees statements and payouts</span>
                                            </span>
                                        </label>
                                        <label class="vy-choice">
                                            <input type="radio" name="role" value="manager" x-model="role">
                                            <span class="vy-choice-face">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5.5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2V7"/></svg>
                                                <span class="vy-choice-text">Property manager</span>
                                                <span class="vyo-choice-sub">Your staff · runs bookings and the calendar</span>
                                            </span>
                                        </label>
                                        <label class="vy-choice">
                                            <input type="radio" name="role" value="accountant" x-model="role">
                                            <span class="vy-choice-face">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 3v18l2-1 2 1 2-1 2 1 2-1 2 1 2-1V3l-2 1-2-1-2 1-2-1-2 1-2-1z"/><path d="M8 8h8M8 12h5"/></svg>
                                                <span class="vy-choice-text">Accountant</span>
                                                <span class="vyo-choice-sub">Reads and exports statements and payouts</span>
                                            </span>
                                        </label>
                                    </div>
                                </fieldset>

                                {{-- name and email — one row, one height --}}
                                <div class="vyo-pair">
                                    <div class="vy-field">
                                        <label class="vy-inv-lbl" for="vy-lbl-owners-name">Name</label>
                                        <input id="vy-lbl-owners-name" class="vy-input" type="text" name="name" value="{{ old('name') }}" maxlength="120" required @if ($vyInviteFocus === 'vy-lbl-owners-name') autofocus @endif
                                               x-bind:placeholder="role === 'owner' ? 'Person or company' : 'Their full name'" autocomplete="off">
                                        @error('name')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-inv-lbl" for="vy-lbl-owners-email">Email</label>
                                        <input id="vy-lbl-owners-email" class="vy-input" type="email" name="email" value="{{ old('email') }}" maxlength="190" required @if ($vyInviteFocus === 'vy-lbl-owners-email') autofocus @endif
                                               placeholder="Where the invite is sent" autocomplete="off">
                                        @error('email')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                {{-- OWNER ONLY: the units (a unit that has an owner is shown, never offered) and the agreement --}}
                                <div x-show="role === 'owner'">
                                    @if (count($inviteUnits) > 0)
                                        <fieldset class="vyo-unitfield">
                                            <legend class="vy-inv-lbl">Their units <span class="vyo-optional">optional</span></legend>
                                            <div class="vyo-units-pick">
                                                @foreach ($inviteUnits as $u)
                                                    <label class="vyo-unitchip {{ $u['owner'] !== null ? 'is-taken' : '' }}" @if ($u['owner'] !== null) title="A unit’s owner decides who is paid for it — it can’t be moved through an invite" @endif>
                                                        <input type="checkbox" name="unit_ids[]" value="{{ $u['id'] }}" x-model="units" @disabled($u['owner'] !== null)>
                                                        <span class="vyo-unitchip-label">{{ $u['label'] }}</span>
                                                        @if ($u['owner'] !== null)
                                                            <span class="vyo-unitchip-owner">Owned by {{ $u['owner'] }}</span>
                                                        @endif
                                                    </label>
                                                @endforeach
                                            </div>
                                            <p class="vyo-hint" x-text="units.length === 0 ? 'None chosen — you can give units later from the owner’s page.' : units.length + (units.length === 1 ? ' unit' : ' units') + ' — they become this owner’s when the invite is accepted.'"></p>
                                            @error('unit_ids')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        </fieldset>
                                    @endif

                                    <fieldset class="vy-choices vy-choices--segmented vyo-commission">
                                        <legend class="vy-inv-lbl">Management commission</legend>
                                        <div class="vy-choice-row">
                                            @foreach ($commissionChoices as $vyRate)
                                                <label class="vy-choice"><input type="radio" value="{{ $vyRate }}" x-model="commission"><span class="vy-choice-face"><span class="vy-choice-text">{{ $vyRate }}%</span></span></label>
                                            @endforeach
                                            <label class="vy-choice"><input type="radio" value="other" x-model="commission"><span class="vy-choice-face"><span class="vy-choice-text">Other</span></span></label>
                                            <label class="vy-choice"><input type="radio" value="later" x-model="commission"><span class="vy-choice-face"><span class="vy-choice-text">Later</span></span></label>
                                        </div>
                                    </fieldset>
                                    <div class="vyo-other" x-show="commission === 'other'">
                                        <label class="vy-sr-only" for="vyo-commission-other">Commission, in percent</label>
                                        <input id="vyo-commission-other" class="vy-input" type="text" inputmode="decimal" x-model="commissionOther" placeholder="e.g. 20" maxlength="6">
                                        <span aria-hidden="true">%</span>
                                    </div>
                                    <p class="vyo-hint" x-text="commissionHint()"></p>
                                    @error('commission_pct')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                </div>

                                <div class="vyo-access" x-show="role === 'manager'">
                                    <strong>Full operations, no owner money</strong>
                                    <span>Bookings, calendar, guest messages and maintenance — not statements or payouts.</span>
                                </div>
                                <div class="vyo-access" x-show="role === 'accountant'">
                                    <strong>Read and export</strong>
                                    <span>Statements, tax documents and payouts. No commission applies.</span>
                                </div>

                                {{-- ⚠️ RE-AUTH: an invite changes who can reach the organisation. Its own row — it shared one with both buttons. --}}
                                <div class="vyo-confirm">
                                    {{-- the password-manager pattern: which account this password is for — unnamed, so never posted, and hidden from everyone --}}
                                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly hidden aria-hidden="true" tabindex="-1">
                                    <label class="vy-inv-lbl" for="invite_pw">Confirm it’s you</label>
                                    <input id="invite_pw" class="vy-input" type="password" name="current_password" autocomplete="current-password" required @if ($vyInviteFocus === 'invite_pw') autofocus @endif
                                           placeholder="Your password">
                                    @error('current_password')
                                        <div class="vyo-fielderr">{{ $message }}</div>
                                    @else
                                        <p class="vyo-hint">An invite gives someone a way into your workspace, so your password confirms it’s you.</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="inviteOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
                                    Send invite
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ══ INVITE SENT — on the real PRG (session `owner_invite_sent`) ══════════════════════════════════════ --}}
                <div class="vy-modal-backdrop" x-cloak x-show="sentOpen" x-transition.opacity x-on:click.self="sentOpen = false" x-on:keydown.escape.window="sentOpen = false">
                    <div class="vy-modal vy-modal-card vyo-dlg" x-show="sentOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyo-sent-t" tabindex="-1" x-trap.noscroll="sentOpen">
                        <div class="vyo-sent">
                            <span class="vyo-sent-ico" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg></span>
                            <h3 class="vy-modal-title" id="vyo-sent-t">Invite sent</h3>
                            <p class="vyo-sent-sub">We emailed <strong>{{ $vySent['email'] ?? '' }}</strong> a link to set their password. They show as <strong>Invited</strong> until they do.</p>
                            <p class="vyo-sent-note">The link works until {{ $vySent['expires'] ?? 'in 7 days' }}. Send it again or cancel it from <strong>Invites waiting</strong>.</p>
                        </div>
                        <div class="vy-modal-foot">
                            <a class="vy-btn vy-btn--ghost" href="{{ route('app.owners.invite.preview') }}" target="_blank" rel="noopener">Preview the email</a>
                            <button type="button" class="vy-btn vy-btn--accent" x-on:click="sentOpen = false" autofocus>Done</button>
                        </div>
                    </div>
                </div>

                {{-- ══ RESEND — a fresh link is a way in, so it asks for your password, as sending did ════════════════════ --}}
                <div class="vy-modal-backdrop" x-cloak x-show="resendOpen" x-transition.opacity x-on:click.self="resendOpen = false" x-on:keydown.escape.window="resendOpen = false">
                    <div class="vy-modal vy-modal-card vyo-dlg" x-show="resendOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyo-resend-t" tabindex="-1" x-trap.noscroll="resendOpen">
                        <form method="POST" x-bind:action="link(urls.resend, resend.id)" x-on:submit="stamp($event.target)" data-guard-submit>
                            @csrf
                            <input type="hidden" name="_form" value="resend">
                            <input type="hidden" name="_invite" x-bind:value="resend.id">
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyo-resend-t">Send the invite again?</h3>
                                    <p class="vyo-dlg-sub" x-text="'A fresh link goes to ' + resend.email + '. The old one stops working.'"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="resendOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vyo-confirm">
                                    {{-- the password-manager pattern: which account this password is for — unnamed, so never posted, and hidden from everyone --}}
                                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly hidden aria-hidden="true" tabindex="-1">
                                    <label class="vy-inv-lbl" for="vyo-resend-pw">Confirm it’s you</label>
                                    <input id="vyo-resend-pw" class="vy-input" type="password" name="current_password" autocomplete="current-password" required placeholder="Your password" autofocus>
                                    @if ($vyResendRetry !== '')
                                        @error('current_password')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="resendOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Send again</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ══ PAUSE / RESUME PAYOUTS ══════════════════════════════════════════════════════════════════════════════
                     ⚠️ ONLY THE AUTOMATIC RUN READS A PAUSE (PayoutRunService::dueOwnerIds). A payout created by hand on the payout
                     run page is not stopped by it — so the dialog says exactly that, and never "the payout run skips them". --}}
                <div class="vy-modal-backdrop" x-cloak x-show="pauseOpen" x-transition.opacity x-on:click.self="pauseOpen = false" x-on:keydown.escape.window="pauseOpen = false">
                    <div class="vy-modal vy-modal-card vyo-dlg" x-show="pauseOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyo-pause-t" tabindex="-1" x-trap.noscroll="pauseOpen">
                        <form method="POST" x-bind:action="link(urls.pause, pause.id)" x-on:submit="stamp($event.target)" data-guard-submit>
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <input type="hidden" name="paused" x-bind:value="pause.resume ? '0' : '1'">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" x-bind:class="! pause.resume && 'is-danger'" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyo-pause-t" x-text="(pause.resume ? 'Resume payouts to ' : 'Pause payouts to ') + pause.name + '?'">Pause payouts?</h3>
                                    <p class="vyo-dlg-sub" x-text="pause.resume ? 'The automatic payout run includes them again.' : 'The automatic payout run skips them until you resume.'"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="pauseOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                <ul class="vyo-factlist">
                                    <li x-show="! pause.resume">Nothing they are owed is lost — each closed statement stays owed until it is paid.</li>
                                    <li x-show="! pause.resume">Statements still close as usual.</li>
                                    @if ($howPaid['engine'])
                                        <li x-show="pause.resume">The next automatic run pays every closed statement still owed.</li>
                                    @else
                                        <li>The automatic payout run is off, so this takes effect when it is turned on.</li>
                                    @endif
                                    <li>A payout created by hand on the payout run page is not stopped by a pause.</li>
                                    <li>Recorded in the audit log.</li>
                                </ul>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="pauseOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn" x-bind:class="pause.resume ? 'vy-btn--accent' : 'vy-btn--danger'" x-text="pause.resume ? 'Resume payouts' : 'Pause payouts'">Pause payouts</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </x-operator.shell>

    {{-- The page's controller — the shared list behaviour (the row menu, search and the smooth links) plus this page's own
         dialogs. Every link and form works without it. --}}
    @include('partials.operator-list-script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vyOwners', (boot) => Object.assign(window.vyListBehaviour(boot, { empty: 'No owners in this view' }), {
                inviteOpen: @js($vyInviteRetry),
                sentOpen: @js($vySent !== null && $canWrite),
                resendOpen: false, resend: { id: '', email: '', name: '' },
                pauseOpen: false, pause: { id: '', name: '', resume: false },
                role: @js(in_array(old('role'), ['owner', 'manager', 'accountant'], true) ? old('role') : 'owner'),
                commission: 'later', commissionOther: '',
                units: @js(array_values(array_map('strval', (array) old('unit_ids', [])))),
                copied: '',

                start() {
                    // the rate typed or chosen before a refusal; otherwise the organisation's own
                    const before = @js(old('commission_pct'));
                    const choices = @js($commissionChoices);
                    if (before === null) this.commission = @js($commissionDefault);
                    else if (before === '') this.commission = 'later';
                    else if (choices.includes(String(before))) this.commission = String(before);
                    else { this.commission = 'other'; this.commissionOther = String(before); }
                    // a refused resend (a wrong password) comes back to its own dialog, for the same invite
                    const retry = @js($vyResendInvite);
                    if (retry) { this.resend = retry; this.resendOpen = true; }
                },
                roleTitle() {
                    return this.role === 'owner' ? 'Invite an owner' : (this.role === 'manager' ? 'Invite a property manager' : 'Invite an accountant');
                },
                commissionValue() {
                    if (this.role !== 'owner' || this.commission === 'later') return '';
                    return this.commission === 'other' ? (this.commissionOther || '').trim() : this.commission;
                },
                commissionHint() {
                    if (this.commission === 'later') return 'Set it when their agreement is ready — on the owner’s page.';
                    const v = this.commissionValue();
                    return (v ? v + '% of gross booking value' : 'A percentage of gross booking value') + ', taken from each statement. You can change it later.';
                },

                // This page's rows, read from the JSON beside the table — swapped with it, so it describes the rows on screen.
                row(id) {
                    const island = this.find('[data-ow-rows]');
                    if (! island) return null;
                    try { return JSON.parse(island.textContent)[id] || null; } catch (e) { return null; }
                },
                can(action) { return !! (this.menu.row && this.menu.row.actions && this.menu.row.actions.indexOf(action) !== -1); },
                // A form started here comes back to the page as it is now (the server rebuilds this from an allow-list).
                stamp(form) { const field = form.querySelector('input[name="return_query"]'); if (field) field.value = location.search.slice(1); },

                openInvite() { this.closeMenu(false); this.inviteOpen = true; },
                openResend(invite) { this.resend = invite; this.resendOpen = true; },
                menuAct(action) {
                    const row = this.menu.row;
                    this.closeMenu(true);
                    if (! row) return;
                    if (action === 'pause' || action === 'resume') {
                        this.pause = { id: row.id, name: row.name, resume: action === 'resume' };
                        this.pauseOpen = true;
                    } else if (action === 'resend' && row.invite) {
                        this.openResend({ id: row.invite.id, email: row.email, name: row.name });
                    }
                },
                async copyEmail() {
                    const row = this.menu.row;
                    this.closeMenu(true);
                    if (! row || ! row.email) return;
                    try { await navigator.clipboard.writeText(row.email); this.copied = 'Copied ' + row.email; }
                    catch (e) { this.copied = row.email; }
                    setTimeout(() => { this.copied = ''; }, 2500);
                },
            }));
        });
    </script>
</div>

@once
    <style>
        [x-cloak] { display: none !important; }
    </style>
@endonce
