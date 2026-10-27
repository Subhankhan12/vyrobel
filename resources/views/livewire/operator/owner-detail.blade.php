{{-- Operator OWNER'S PAGE — rebuilt 2026-10-03 (the founder: "modal design and buttons are mess … confirm with your password and
     its box is not well aligned … all the sort of work you have done in the previous pages … in this module … make sure
     everything works very well").

     The operator's TENANT-scoped lens on ONE owner (App\Livewire\Operator\OperatorOwnerDetail holds the full REAL / DERIVED /
     NOT OFFERED map): who they are and how to reach them, the actions on them in one bar, the money (net to owner, statements,
     payouts), the agreement, their units and rate proposals — and beside it how they are paid, their portal access and the
     team's notes.

     Writes, each a POST through every middleware (#20), each offered only where its route would accept it: the commission
     (CommissionChange — the latest change wins), pause / resume payouts, close a period, remove (an archive: the owner's name
     and your password), a note; a waiting invite sent again or cancelled; a rate proposal. ⚠️ A refused write reopens its own
     dialog with the reason beside the field (`_form` names it) — before 2026-10-03 it closed and the page said nothing.

     ⚠️ Every dialog is the shared centred one and every display is a CLASS — x-show erases an inline display. ⚠️ No control a
     role may not use reaches that role's page: a Hotel Manager reads this page and gets no owner write — only the rate proposal
     controls its `rates.manage` allows.
     ⚠️ RAW-BLADE RULE: never `word@directive`. Alpine handlers use `x-on:` / `x-bind:`. --}}
<div>
    {{-- The page heading is the kind of page, as on a work order; the owner's name heads the card below it, once. --}}
    <x-operator.shell active="owners" :title="__('livewire/operator/owner-detail.page_title')" :subtitle="__('livewire/operator/owner-detail.page_subtitle')" :viewer="$viewer">
        @php
            // Which dialog a refused write reopens: the form says which it was (`_form`), and what was typed comes back.
            $vyForm = (string) old('_form');
            $vyRefused = $errors->any() || session('owners_error') || session('rate_error');
            $vyAllowed = array_values(array_filter([
                $canManage ? 'commission' : null, $canPayout ? 'close' : null, $canManage ? 'remove' : null,
                $canManage && $invite !== null ? 'resend' : null, count($roomTypes) > 0 ? 'propose' : null,
            ]));
            $vyOpen = $vyRefused && in_array($vyForm, $vyAllowed, true) ? $vyForm : '';
            // A dialog that reopens shows its own refusal; the page-level flash then stays quiet.
            $vyOwnersErrorTop = session('owners_error') && ! in_array($vyOpen, ['commission', 'close', 'remove', 'resend'], true);
            $vyRateErrorTop = session('rate_error') && $vyOpen !== 'propose';
            $vyNoteError = $vyForm === 'note' ? $errors->first('body') : '';
            $vyStrayErrors = $vyOpen === '' && $vyForm !== 'note' ? $errors->all() : [];
        @endphp

        @if (session('owners_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                {{ session('owners_notice') }}
            </div>
        @endif
        @if ($vyOwnersErrorTop || $vyStrayErrors !== [])
            <div class="vy-flash is-err vyo-flash vy-fade" role="alert">
                @if ($vyOwnersErrorTop)
                    <div>{{ session('owners_error') }}</div>
                @endif
                @foreach ($vyStrayErrors as $vyError)
                    <div>{{ $vyError }}</div>
                @endforeach
            </div>
        @endif

        <div class="vyo-root vyo-ownerpage" x-data="vyOwnerPage({
                commissionOpen: @js($vyOpen === 'commission'), pauseOpen: false, closeOpen: @js($vyOpen === 'close'),
                removeOpen: @js($vyOpen === 'remove'), resendOpen: @js($vyOpen === 'resend'), cancelOpen: false,
                proposeOpen: @js($vyOpen === 'propose'),
                start: @js((string) old('period_start', $closePeriod['start'])), end: @js((string) old('period_end', $closePeriod['end'])),
            })">

            <a class="vy-convo-back vyo-back" href="{{ route('app.owners') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                {{ __('livewire/operator/owner-detail.all_owners') }}
            </a>

            {{-- ══ WHO — and every action on them, in one bar ══════════════════════════════════════════════════════════════ --}}
            <section class="vy-card vyo-hero vy-fade" aria-labelledby="vyod-name">
                <div class="vyo-hero-id">
                    <span class="vy-owner-avatar vyo-hero-avatar" aria-hidden="true">{{ $owner['initials'] }}</span>
                    <div class="vyo-hero-who">
                        <h2 class="vyo-hero-name" id="vyod-name">{{ $owner['name'] }}</h2>
                        @if ($owner['legal_name'])
                            <div class="vyo-hero-legal">{{ $owner['legal_name'] }}</div>
                        @endif
                        <div class="vyo-hero-tags">
                            <span class="vy-tag tone-{{ $owner['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $owner['status_meta']['label'] }}</span>
                            <span class="vyo-hero-fact">{{ $owner['type_label'] }}</span>
                            <span class="vyo-hero-fact">{{ __('livewire/operator/owner-detail.paid_in', ['currency' => $owner['payout_currency']]) }}</span>
                            @if ($payoutPaused)
                                <span class="vyo-tag-paused">{{ __('livewire/operator/owner-detail.payouts_paused') }}</span>
                            @endif
                        </div>
                        {{-- The operator's own record of a counterparty they invited (tax id and address are never read). --}}
                        <div class="vyo-hero-contact">
                            @if ($owner['email'])
                                <a class="vyo-hero-link" href="mailto:{{ $owner['email'] }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>
                                    <span>{{ $owner['email'] }}</span>
                                </a>
                            @else
                                <span class="vyo-hero-none">{{ __('livewire/operator/owner-detail.no_email') }}</span>
                            @endif
                            @if ($owner['phone'])
                                <a class="vyo-hero-link" href="tel:{{ preg_replace('/[^0-9+]/', '', $owner['phone']) }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/></svg>
                                    <span class="vyo-num">{{ $owner['phone'] }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($canManage || $canPayout)
                    <div class="vyo-hero-actions">
                        @if ($canManage)
                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="commissionOpen = true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 5L5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                                {{ __('livewire/operator/owner-detail.change_commission') }}
                            </button>
                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="pauseOpen = true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">@if ($payoutPaused)<path d="M8 5l11 7-11 7z"/>@else<path d="M9 5v14M15 5v14"/>@endif</svg>
                                {{ $payoutPaused ? __('livewire/operator/owner-detail.resume_payouts') : __('livewire/operator/owner-detail.pause_payouts') }}
                            </button>
                        @endif
                        @if ($canPayout)
                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="closeOpen = true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M9 15l2 2 4-4"/></svg>
                                {{ __('livewire/operator/owner-detail.close_a_period') }}
                            </button>
                        @endif
                        @if ($canManage)
                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm is-danger" x-on:click="removeOpen = true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg>
                                {{ __('livewire/operator/owner-detail.remove_owner') }}
                            </button>
                        @endif
                    </div>
                @endif
            </section>

            {{-- ══ The figures — DERIVED from the real ledger and payouts, per currency, never summed across (#17) ═════════ --}}
            <div class="vy-dash-kpis vyo-kpis vy-fade">
                <div class="vy-dash-kpi is-feature">
                    <div class="vy-dk-label">{{ __('livewire/operator/owner-detail.kpi_balance') }}</div>
                    @foreach ($kpis['balance'] as $line)
                        <div class="vy-dk-val {{ $loop->first ? '' : 'vyo-kpi-extra' }}">{{ $line }}</div>
                    @endforeach
                    <div class="vy-dk-sub">{{ $kpis['multi_currency'] ? __('livewire/operator/owner-detail.balance_sub_multi') : __('livewire/operator/owner-detail.balance_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/owner-detail.kpi_paid_out') }}</div>
                    @foreach ($kpis['paid'] as $line)
                        <div class="vy-dk-val {{ $loop->first ? '' : 'vyo-kpi-extra' }}">{{ $line }}</div>
                    @endforeach
                    <div class="vy-dk-sub">{{ __('livewire/operator/owner-detail.kpi_paid_out_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/owner-detail.kpi_waiting') }}</div>
                    @foreach ($kpis['pending'] as $line)
                        <div class="vy-dk-val {{ $loop->first ? '' : 'vyo-kpi-extra' }}">{{ $line }}</div>
                    @endforeach
                    <div class="vy-dk-sub">{{ __('livewire/operator/owner-detail.kpi_waiting_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/owner-detail.kpi_paid_year') }}</div>
                    <div class="vy-dk-val">{{ $paidYear['display'] }}</div>
                    <div class="vy-dk-sub">{{ trans_choice('livewire/operator/owner-detail.kpi_paid_year_sub', $paidYear['count'], ['count' => $paidYear['count'], 'occupancy' => $occupancy]) }}</div>
                </div>
            </div>

            <div class="vyo-layout">
                <div class="vyo-main">

                    {{-- Net to owner — the latest finalised statement's REAL breakdown; credited − deducted == net (#17). --}}
                    @if ($netToOwner)
                        <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-net-t">
                            <div class="vy-detail-head vyo-card-head">
                                <h2 class="vy-card-title" id="vyod-net-t">{{ __('livewire/operator/owner-detail.net_to_owner_heading', ['period' => $netToOwner['period']]) }}</h2>
                                <span class="vy-eyebrow">{{ __('livewire/operator/owner-detail.reconciles') }}</span>
                            </div>
                            <div class="vyo-net-grid">
                                <div>
                                    <div class="vyo-net-label">{{ __('livewire/operator/owner-detail.credited') }}</div>
                                    @foreach ($netToOwner['credits'] as $c)
                                        <div class="vyo-net-row"><span>{{ $c['label'] }}</span><span class="vy-u-code">{{ $c['display'] }}</span></div>
                                    @endforeach
                                    <div class="vyo-net-row is-sum"><span>{{ __('livewire/operator/owner-detail.credited') }}</span><span class="vy-u-code">{{ $netToOwner['credited_display'] }}</span></div>
                                </div>
                                <div>
                                    <div class="vyo-net-label">{{ __('livewire/operator/owner-detail.deducted') }} <span class="vyo-net-note">{{ __('livewire/operator/owner-detail.incl_commission', ['pct' => $netToOwner['commission_pct'] ?? '—']) }}</span></div>
                                    @foreach ($netToOwner['deductions'] as $d)
                                        <div class="vyo-net-row"><span>{{ $d['label'] }}</span><span class="vy-u-code">{{ $d['display'] }}</span></div>
                                    @endforeach
                                    <div class="vyo-net-row is-sum"><span>{{ __('livewire/operator/owner-detail.deducted') }}</span><span class="vy-u-code">{{ $netToOwner['deducted_display'] }}</span></div>
                                </div>
                            </div>
                            <div class="vyo-net-total"><span>{{ __('livewire/operator/owner-detail.net_to_owner') }}</span><span class="vy-u-code">{{ $netToOwner['net_display'] }}</span></div>
                        </section>
                    @endif

                    {{-- Statements — REAL rows. No link: the statement page is the OWNER's, and an operator would get a 404. --}}
                    <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-st-t">
                        <div class="vy-detail-head vyo-card-head">
                            <h2 class="vy-card-title" id="vyod-st-t">{{ __('livewire/operator/owner-detail.statements') }} <span class="vyo-count">{{ count($statements) }}</span></h2>
                            {{-- ⚠️ AUDIT H-2 — the front door for `app.owners.close-period`, gated on the route's own permission. --}}
                            @if ($canPayout)
                                <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="closeOpen = true">{{ __('livewire/operator/owner-detail.close_a_period') }}</button>
                            @endif
                        </div>
                        @if (count($statements) > 0)
                            <div class="vy-utable-wrap">
                                <table class="vy-utable">
                                    <thead>
                                        <tr>
                                            <th>{{ __('livewire/operator/owner-detail.th_number') }}</th>
                                            <th>{{ __('livewire/operator/owner-detail.th_period') }}</th>
                                            <th>{{ __('livewire/operator/owner-detail.th_gross') }}</th>
                                            <th>{{ __('livewire/operator/owner-detail.th_commission') }}</th>
                                            <th>{{ __('livewire/operator/owner-detail.th_net_payable') }}</th>
                                            <th>{{ __('common.status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($statements as $st)
                                            <tr>
                                                <td class="vy-u-code">{{ $st['sequence_no'] ?? '—' }}</td>
                                                <td>
                                                    <div>{{ $st['period'] }}</div>
                                                    @if ($st['finalized'])
                                                        <div class="vy-u-sub">{{ __('livewire/operator/owner-detail.finalized_on', ['date' => $st['finalized']]) }}</div>
                                                    @endif
                                                </td>
                                                <td class="vyo-num">{{ $st['gross'] }}</td>
                                                <td class="vyo-num">{{ $st['commission'] }}</td>
                                                <td class="vy-u-code">{{ $st['net'] }}</td>
                                                <td>
                                                    <span class="vy-tag tone-{{ $st['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $st['status_meta']['label'] }}</span>
                                                    @if ($st['has_unverified'] || $st['locked'])
                                                        <div class="vyo-flags">
                                                            @if ($st['has_unverified'])<span class="vy-ledger-flag">{{ __('livewire/operator/owner-detail.unverified_lines') }}</span>@endif
                                                            @if ($st['locked'])<span class="vy-ledger-flag">{{ __('livewire/operator/owner-detail.locked') }}</span>@endif
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="vy-empty vyo-empty">
                                {{ __('livewire/operator/owner-detail.no_statements') }}
                                @if ($canPayout)
                                    <p class="vyo-empty-sub">{{ __('livewire/operator/owner-detail.no_statements_hint') }}</p>
                                @endif
                            </div>
                        @endif
                    </section>

                    {{-- Payouts — REAL rows; the method from the SHARED RailLabel map, a failure with its real reason. --}}
                    <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-po-t">
                        <div class="vy-detail-head vyo-card-head">
                            <h2 class="vy-card-title" id="vyod-po-t">{{ __('livewire/operator/owner-detail.payouts') }} <span class="vyo-count">{{ count($payouts) }}</span></h2>
                            @if ($canPayout)
                                <a class="vyo-card-link" href="{{ route('app.payouts.run') }}">{{ __('livewire/operator/owner-detail.payout_run') }}</a>
                            @endif
                        </div>
                        <div class="vy-ledger">
                            @forelse ($payouts as $p)
                                <div class="vy-ledger-row">
                                    <div class="vy-ledger-main">
                                        <div class="vy-ledger-label">
                                            {{ $p['method'] }}
                                            <span class="vy-tag tone-{{ $p['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $p['status_meta']['label'] }}</span>
                                        </div>
                                        <div class="vy-ledger-sub">
                                            @if ($p['paid_at'])
                                                {{ __('livewire/operator/owner-detail.paid_on', ['date' => $p['paid_at']]) }}
                                            @elseif ($p['failed_at'])
                                                {{ __('livewire/operator/owner-detail.failed_on', ['date' => $p['failed_at']]) }}
                                            @else
                                                {{ __('livewire/operator/owner-detail.not_paid_yet') }}
                                            @endif
                                            @if ($p['external_ref'])
                                                <span class="vy-sep">·</span> {{ __('livewire/operator/owner-detail.payout_ref', ['ref' => $p['external_ref']]) }}
                                            @endif
                                        </div>
                                        @if ($p['failure_reason'])
                                            <div class="vy-ledger-sub">{{ $p['failure_reason'] }}</div>
                                        @endif
                                    </div>
                                    <div class="vy-ledger-amt tone-{{ $p['status_meta']['tone'] === 'confirmed' ? 'paid' : 'muted' }}">{{ $p['amount'] }}</div>
                                </div>
                            @empty
                                <div class="vy-empty vyo-empty">{{ __('livewire/operator/owner-detail.no_payouts') }}</div>
                            @endforelse
                        </div>
                    </section>

                    {{-- Management agreement — the terms in effect today (the model's certified effectiveOn); "Not set", never "0%". --}}
                    <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-ag-t">
                        <div class="vy-detail-head vyo-card-head">
                            <h2 class="vy-card-title" id="vyod-ag-t">{{ __('livewire/operator/owner-detail.management_agreement') }}</h2>
                            @if ($agreement)
                                <span class="vy-tag tone-{{ $agreement['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $agreement['status_meta']['label'] }}</span>
                            @endif
                        </div>
                        @if ($agreement)
                            <div class="vy-eyebrow vyo-sublabel">{{ __('livewire/operator/owner-detail.in_effect_today') }}</div>
                            <div class="vy-facts">
                                <div>
                                    <div class="vy-fact-label">{{ __('livewire/operator/owner-detail.commission') }}</div>
                                    <div class="vy-fact-val">{{ $agreement['commission_display'] ?? __('livewire/operator/owner-detail.not_set') }}</div>
                                </div>
                                <div>
                                    <div class="vy-fact-label">{{ __('livewire/operator/owner-detail.management_fee') }}</div>
                                    <div class="vy-fact-val">{{ $agreement['fee_display'] && $agreement['fee_interval'] ? __('livewire/operator/owner-detail.fee_with_interval', ['fee' => $agreement['fee_display'], 'interval' => $agreement['fee_interval']]) : ($agreement['fee_display'] ?? __('livewire/operator/owner-detail.not_set')) }}</div>
                                </div>
                                <div>
                                    <div class="vy-fact-label">{{ __('livewire/operator/owner-detail.payout_schedule') }}</div>
                                    <div class="vy-fact-val">{{ $agreement['schedule'] ?? __('livewire/operator/owner-detail.not_set') }}</div>
                                </div>
                                <div>
                                    <div class="vy-fact-label">{{ __('livewire/operator/owner-detail.payout_currency') }}</div>
                                    <div class="vy-fact-val">{{ $agreement['payout_currency'] ?? __('livewire/operator/owner-detail.not_set') }}</div>
                                    @if ($agreement['payout_currency_source'])
                                        {{-- ⚠️ `.vy-um-sub` is sized only inside a unit row; here it printed at the body size --}}
                                        <div class="vyo-fact-note">{{ $agreement['payout_currency_source'] }}</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="vy-fact-label">{{ __('livewire/operator/owner-detail.scope') }}</div>
                                    <div class="vy-fact-val">{{ $agreement['scope_label'] }}</div>
                                </div>
                                <div>
                                    <div class="vy-fact-label">{{ __('livewire/operator/owner-detail.effective') }}</div>
                                    <div class="vy-fact-val">{{ __('livewire/operator/owner-detail.effective_range', ['from' => $agreement['from_display'] ?? '—', 'to' => $agreement['to_display'] ?? __('livewire/operator/owner-detail.open_ended')]) }}</div>
                                </div>
                            </div>
                        @else
                            <div class="vy-empty vyo-empty">{{ __('livewire/operator/owner-detail.no_agreement') }}</div>
                        @endif

                        {{-- Other and earlier terms — REAL rows; one not in effect carries a factual DATE note, never an invented status. --}}
                        @if (count($agreementHistory) > 0)
                            <div class="vy-eyebrow vyo-sublabel is-spaced">{{ __('livewire/operator/owner-detail.other_terms') }}</div>
                            <div class="vy-utable-wrap">
                                <table class="vy-utable vyo-terms-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('livewire/operator/owner-detail.scope') }}</th>
                                            <th>{{ __('livewire/operator/owner-detail.commission') }}</th>
                                            <th>{{ __('livewire/operator/owner-detail.th_fee') }}</th>
                                            <th>{{ __('livewire/operator/owner-detail.effective') }}</th>
                                            <th>{{ __('common.status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($agreementHistory as $h)
                                            <tr>
                                                <td>
                                                    <div class="vy-u-code">{{ $h['scope_label'] }}</div>
                                                    @if ($h['timing_note'])
                                                        <div class="vy-u-sub">{{ $h['timing_note'] }}</div>
                                                    @elseif ($h['in_effect'])
                                                        <div class="vy-u-sub">{{ __('livewire/operator/owner-detail.in_effect_today') }}</div>
                                                    @endif
                                                </td>
                                                <td>{{ $h['commission_display'] ?? __('livewire/operator/owner-detail.not_set') }}</td>
                                                <td>{{ $h['fee_display'] ?? __('livewire/operator/owner-detail.not_set') }}</td>
                                                <td>{{ $h['never_applied'] ? __('livewire/operator/owner-detail.never_applied') : __('livewire/operator/owner-detail.effective_range', ['from' => $h['from_display'] ?? '—', 'to' => $h['to_display'] ?? __('livewire/operator/owner-detail.open_ended')]) }}</td>
                                                <td><span class="vy-tag tone-{{ $h['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $h['status_meta']['label'] }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>

                    {{-- Units — REAL, filtered by owner_id --}}
                    <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-un-t">
                        <div class="vy-detail-head vyo-card-head">
                            <h2 class="vy-card-title" id="vyod-un-t">{{ __('livewire/operator/owner-detail.units') }} <span class="vyo-count">{{ count($units) }}</span></h2>
                        </div>
                        @forelse ($units as $u)
                            <div class="vy-unit-row">
                                <div>
                                    <div class="vy-um-name">{{ $u['code'] }}{{ $u['name'] ? ' · ' . $u['name'] : '' }}</div>
                                    <div class="vy-um-sub">
                                        {{ $u['kind_label'] }}
                                        @if ($u['room_type'])
                                            <span class="vy-sep">·</span> {{ $u['room_type'] }}
                                        @endif
                                        {{-- units.max_occupancy is NOT NULL (default 2) — always a real fact. --}}
                                        <span class="vy-sep">·</span> {{ trans_choice('livewire/operator/owner-detail.sleeps', $u['sleeps'], ['count' => $u['sleeps']]) }}
                                    </div>
                                </div>
                                @unless ($u['active'])
                                    <span class="vy-u-inactive">{{ __('livewire/operator/owner-detail.unit_inactive') }}</span>
                                @endunless
                            </div>
                        @empty
                            <div class="vy-empty vyo-empty">{{ __('livewire/operator/owner-detail.no_units') }}</div>
                        @endforelse
                    </section>

                    {{-- Rate proposals — propose a change; the owner approves, counters or declines it in their portal. Money is
                         parsed on the server (#17); the owner must own the room type outright (the service refuses a shared one). --}}
                    @can('rates.manage')
                        <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-rp-t">
                            <div class="vy-detail-head vyo-card-head">
                                <h2 class="vy-card-title" id="vyod-rp-t">{{ __('livewire/operator/owner-detail.rate_proposals') }}</h2>
                                @if (count($roomTypes) > 0)
                                    <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="proposeOpen = true">{{ __('livewire/operator/owner-detail.propose_a_change') }}</button>
                                @endif
                            </div>
                            @if (session('rate_notice'))
                                <div class="vyo-inline-note is-ok" role="status">{{ session('rate_notice') }}</div>
                            @endif
                            @if ($vyRateErrorTop)
                                <div class="vyo-inline-note is-err" role="alert">{{ session('rate_error') }}</div>
                            @endif

                            @forelse ($proposals as $p)
                                <div class="vy-unit-row">
                                    <div>
                                        <div class="vy-um-name">{{ $p['room_type'] }} · {{ $p['summary'] }}</div>
                                        {{-- ⚠️⚠️ THE COUNTER REACHES THE SCREEN (audit M-14 · F1) — the owner's figure is the headline, the
                                             operator's own ask kept beside it. --}}
                                        <div class="vy-um-sub">
                                            {{ __('livewire/operator/owner-detail.proposal_status', ['status' => $p['state_label']]) }}
                                            @if ($p['countered'])
                                                {!! __('livewire/operator/owner-detail.owner_countered', ['counter' => e($p['counter']), 'proposed' => e($p['proposed'])]) !!}
                                            @endif
                                        </div>
                                    </div>
                                    @if ($p['pending'])
                                        <form method="POST" action="{{ route('app.rate-proposals.withdraw', $p['id']) }}" class="vyo-inline-form" data-guard-submit>
                                            @csrf
                                            <button type="submit" class="vy-btn vy-btn--ghost vyo-sm">{{ __('livewire/operator/owner-detail.withdraw') }}</button>
                                        </form>
                                    @elseif ($p['countered'])
                                        {{-- ⚠️⚠️ MONEY PATH: accepting applies the OWNER'S rate through the certified
                                             RateProposalService::acceptCounter(). The amount is on the button. --}}
                                        <form method="POST" action="{{ route('app.rate-proposals.accept-counter', $p['id']) }}" class="vyo-inline-form" data-guard-submit>
                                            @csrf
                                            <button type="submit" class="vy-btn vy-btn--accent vyo-sm">{{ __('livewire/operator/owner-detail.accept_counter', ['amount' => $p['counter']]) }}</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <div class="vy-empty vyo-empty">{{ __('livewire/operator/owner-detail.no_proposals') }}</div>
                            @endforelse
                        </section>
                    @endcan
                </div>

                {{-- ══ RAIL — how they're paid · the owner portal · notes ═══════════════════════════════════════════════════ --}}
                <aside class="vyo-rail">
                    <section class="vy-card vyo-terms vy-fade" aria-labelledby="vyod-pay-t">
                        <h2 class="vy-card-title" id="vyod-pay-t">{{ __('livewire/operator/owner-detail.how_paid') }}</h2>
                        <dl class="vyo-facts">
                            <div>
                                <dt>{{ __('livewire/operator/owner-detail.commission') }}</dt>
                                <dd>
                                    <strong class="vyo-terms-big">{{ $commission['now'] ?? __('livewire/operator/owner-detail.not_set') }}</strong>
                                    @if ($commission['next'])
                                        <span class="vyo-terms-next">{{ __('livewire/operator/owner-detail.commission_changes_to', ['rate' => $commission['next']['display'], 'date' => $commission['next']['from']]) }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt>{{ __('livewire/operator/owner-detail.payouts') }}</dt>
                                @if ($payoutPaused)
                                    {{-- DEC-n (2026-10-02): a pause stops EVERY payout — the payout service refuses a paused owner. --}}
                                    <dd>{!! __('livewire/operator/owner-detail.payouts_paused_detail') !!}</dd>
                                @else
                                    <dd>{{ __('livewire/operator/owner-detail.payouts_not_paused') }}</dd>
                                @endif
                            </div>
                            <div>
                                <dt>{{ __('livewire/operator/owner-detail.schedule') }}</dt>
                                <dd>{{ __('livewire/operator/owner-detail.schedule_in_currency', ['schedule' => $agreement['schedule'] ?? __('livewire/operator/owner-detail.not_set'), 'currency' => $agreement['payout_currency'] ?? $owner['payout_currency']]) }}</dd>
                            </div>
                            {{-- MASKED, payouts.manage only, never the full number, never entered here (#28). --}}
                            @if ($canPayout)
                                <div>
                                    <dt>{{ __('livewire/operator/owner-detail.payout_account') }}</dt>
                                    @if ($ibanMasked)
                                        <dd class="vyo-num">{!! __('livewire/operator/owner-detail.iban_on_file', ['iban' => e($ibanMasked)]) !!}</dd>
                                    @else
                                        <dd>{{ __('livewire/operator/owner-detail.no_payout_account') }}</dd>
                                    @endif
                                </div>
                            @endif
                        </dl>
                    </section>

                    {{-- Portal access — the REAL state: a live invite is the pending invite it is; "active" needs an active login. --}}
                    <section class="vy-card vyo-portalcard vy-fade" aria-labelledby="vyod-portal-t">
                        <div class="vy-detail-head vyo-card-head">
                            <h2 class="vy-card-title" id="vyod-portal-t">{{ __('livewire/operator/owner-detail.owner_portal') }}</h2>
                            <span class="vy-tag tone-{{ $portal['tone'] }}"><span class="vy-dot"></span>{{ $portal['label'] }}</span>
                        </div>
                        @if ($portal['detail'])
                            <p class="vyo-card-sub">{{ $portal['detail'] }}</p>
                        @endif
                        @if ($canManage && $invite !== null)
                            <p class="vyo-card-sub">{!! __('livewire/operator/owner-detail.invite_went_to', ['email' => e($invite['email'])]) !!}</p>
                            <div class="vyo-portal-actions">
                                <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="resendOpen = true">{{ __('livewire/operator/owner-detail.send_invite_again') }}</button>
                                @if ($invite['cancellable'])
                                    <button type="button" class="vy-btn vy-btn--ghost vyo-sm is-danger" x-on:click="cancelOpen = true">{{ __('livewire/operator/owner-detail.cancel_invite') }}</button>
                                @endif
                            </div>
                        @else
                            <a class="vyo-card-link" href="{{ route('app.owners') }}">{{ __('livewire/operator/owner-detail.all_owners_and_invites') }}</a>
                        @endif
                    </section>

                    {{-- Notes — the team's own timeline, never shown in the owner portal. --}}
                    <section class="vy-card vyo-notes vy-fade" aria-labelledby="vyod-notes-t">
                        <h2 class="vy-card-title" id="vyod-notes-t">{{ __('common.notes') }}</h2>
                        @if ($owner['notes'])
                            <div class="vyo-note vyo-note-legacy">{{ $owner['notes'] }}</div>
                        @endif
                        @foreach ($noteEntries as $n)
                            <div class="vyo-note">
                                <div class="vyo-note-body">{{ $n['body'] }}</div>
                                <div class="vyo-note-meta">{{ $n['author'] }} · {{ $n['when'] }}</div>
                            </div>
                        @endforeach
                        @if (! $owner['notes'] && count($noteEntries) === 0)
                            <p class="vyo-card-sub">{{ __('livewire/operator/owner-detail.no_notes') }}</p>
                        @endif
                        @if ($canManage)
                            <form method="POST" action="{{ route('app.owners.note', $ownerId) }}" class="vyo-note-form" data-guard-submit>
                                @csrf
                                <input type="hidden" name="_form" value="note">
                                <label class="vy-sr-only" for="vyod-note">{{ __('livewire/operator/owner-detail.note_label') }}</label>
                                <textarea id="vyod-note" name="body" rows="2" class="vy-input" placeholder="{{ __('livewire/operator/owner-detail.note_placeholder') }}" maxlength="2000" required>{{ $vyForm === 'note' ? old('body') : '' }}</textarea>
                                @if ($vyNoteError !== '')
                                    <div class="vyo-fielderr">{{ $vyNoteError }}</div>
                                @endif
                                <div class="vyo-note-foot">
                                    <span class="vyo-hint">{{ __('livewire/operator/owner-detail.notes_hint') }}</span>
                                    <button type="submit" class="vy-btn vy-btn--ghost vyo-sm">{{ __('livewire/operator/owner-detail.add_note') }}</button>
                                </div>
                            </form>
                        @endif
                    </section>
                </aside>
            </div>

            {{-- ══════════════════════════════════════════ THE DIALOGS ══════════════════════════════════════════════════════ --}}
            @if ($canManage)
                {{-- ══ CHANGE THE COMMISSION — the ONE rule in CommissionChange: from the first of next month, the latest wins ══ --}}
                <div class="vy-modal-backdrop" x-cloak x-show="commissionOpen" x-transition.opacity x-on:click.self="commissionOpen = false" x-on:keydown.escape.window="commissionOpen = false">
                    <div class="vy-modal vy-modal-card vyo-dlg" x-show="commissionOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-commission-t" tabindex="-1" x-trap.noscroll="commissionOpen">
                        <form method="POST" action="{{ route('app.owners.update', $ownerId) }}" data-guard-submit>
                            @csrf
                            <input type="hidden" name="_form" value="commission">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 5L5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyod-commission-t">{{ __('livewire/operator/owner-detail.commission_dialog_title') }}</h3>
                                    <p class="vyo-dlg-sub">{{ __('livewire/operator/owner-detail.commission_dialog_sub', ['name' => $owner['name'], 'rate' => $commission['now'] ?? __('livewire/operator/owner-detail.not_set_lower')]) }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="commissionOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($vyOpen === 'commission' && session('owners_error'))
                                    <div class="vyo-errors" role="alert">{{ session('owners_error') }}</div>
                                @endif
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vyod-rate">{{ $commission['starts_today'] ? __('livewire/operator/owner-detail.commission_from_today') : __('livewire/operator/owner-detail.commission_from_date', ['date' => $commission['starts_on']]) }}</label>
                                    <div class="vyo-rate">
                                        <input id="vyod-rate" class="vy-input" type="text" inputmode="decimal" name="commission_pct" maxlength="6" required autofocus
                                               value="{{ $vyForm === 'commission' ? old('commission_pct') : $commission['prefill'] }}" placeholder="{{ __('livewire/operator/owner-detail.example_placeholder', ['value' => '18']) }}">
                                        <span class="vyo-rate-unit" aria-hidden="true">%</span>
                                    </div>
                                    @if ($vyForm === 'commission')
                                        @error('commission_pct')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <ul class="vyo-factlist">
                                    @if ($commission['starts_today'])
                                        <li>{{ __('livewire/operator/owner-detail.starts_today_no_agreement', ['name' => $owner['name']]) }}</li>
                                    @else
                                        <li>{{ __('livewire/operator/owner-detail.starts_on', ['date' => $commission['starts_on']]) }}</li>
                                    @endif
                                    {{-- ⚠️ "worked out", not "booked" — earnings are worked out when a booking is made, later for one imported
                                         without them, and again when a booking moves to another owner's unit (the review) --}}
                                    <li>{{ __('livewire/operator/owner-detail.commission_applies_to') }}</li>
                                    @if ($commission['next'] && $commission['next']['replaced_by_save'])
                                        <li>{{ __('livewire/operator/owner-detail.replaces_waiting_change', ['rate' => $commission['next']['display'], 'date' => $commission['next']['from']]) }}</li>
                                    @endif
                                    @if ($commission['starts_today'])
                                        {{-- CommissionChange's defaults when there is no agreement to carry terms from --}}
                                        <li>{{ __('livewire/operator/owner-detail.new_agreement_note', ['currency' => $owner['payout_currency']]) }}</li>
                                    @else
                                        <li>{{ __('livewire/operator/owner-detail.other_terms_stay') }}</li>
                                    @endif
                                </ul>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="commissionOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/owner-detail.save_commission') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ══ PAUSE / RESUME PAYOUTS — DEC-n (2026-10-02): a pause stops EVERY payout; the payout service refuses a paused
                     owner, so the dialog can say so plainly. ════════════════════════════════════════════════════════════════ --}}
                <div class="vy-modal-backdrop" x-cloak x-show="pauseOpen" x-transition.opacity x-on:click.self="pauseOpen = false" x-on:keydown.escape.window="pauseOpen = false">
                    <div class="vy-modal vy-modal-card vyo-dlg" x-show="pauseOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-pause-t" tabindex="-1" x-trap.noscroll="pauseOpen">
                        <form method="POST" action="{{ route('app.owners.pause-payouts', $ownerId) }}" data-guard-submit>
                            @csrf
                            <input type="hidden" name="paused" value="{{ $payoutPaused ? '0' : '1' }}">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico {{ $payoutPaused ? '' : 'is-danger' }}" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyod-pause-t">{{ $payoutPaused ? __('livewire/operator/owner-detail.resume_payouts_to', ['name' => $owner['name']]) : __('livewire/operator/owner-detail.pause_payouts_to', ['name' => $owner['name']]) }}</h3>
                                    <p class="vyo-dlg-sub">{{ $payoutPaused ? __('livewire/operator/owner-detail.resume_sub') : __('livewire/operator/owner-detail.pause_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="pauseOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                <ul class="vyo-factlist">
                                    @if ($payoutPaused)
                                        <li>{{ $engineOn ? __('livewire/operator/owner-detail.resume_fact_engine') : __('livewire/operator/owner-detail.resume_fact') }}</li>
                                    @else
                                        <li>{{ __('livewire/operator/owner-detail.pause_fact_none') }}</li>
                                        <li>{{ __('livewire/operator/owner-detail.pause_fact_owed') }}</li>
                                        <li>{{ __('livewire/operator/owner-detail.pause_fact_statements') }}</li>
                                    @endif
                                    <li>{{ __('livewire/operator/owner-detail.recorded_in_audit_log') }}</li>
                                </ul>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="pauseOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn {{ $payoutPaused ? 'vy-btn--accent' : 'vy-btn--danger' }}">{{ $payoutPaused ? __('livewire/operator/owner-detail.resume_payouts') : __('livewire/operator/owner-detail.pause_payouts') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ══ REMOVE OWNER — an ARCHIVE, not a delete: the owner's name and your password (G2) ══════════════════════════
                     The founder's report: the password box sat under a shouted label with no border, and Keep and Remove were two
                     sizes. Now the password has its own row and both buttons share the foot at one height. --}}
                <div class="vy-modal-backdrop" x-cloak x-show="removeOpen" x-transition.opacity x-on:click.self="removeOpen = false" x-on:keydown.escape.window="removeOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg" x-show="removeOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-remove-t" tabindex="-1" x-trap.noscroll="removeOpen">
                        <form method="POST" action="{{ route('app.owners.remove', $ownerId) }}" data-guard-submit>
                            @csrf
                            <input type="hidden" name="_form" value="remove">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico is-danger" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyod-remove-t">{{ __('livewire/operator/owner-detail.remove_title', ['name' => $owner['name']]) }}</h3>
                                    <p class="vyo-dlg-sub">{{ __('livewire/operator/owner-detail.remove_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="removeOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($vyOpen === 'remove' && session('owners_error'))
                                    <div class="vyo-errors" role="alert">{{ session('owners_error') }}</div>
                                @endif
                                <ul class="vyo-factlist vyo-factlist--spaced">
                                    <li>@if (count($removeUnitNames)){!! __('livewire/operator/owner-detail.remove_units_named', ['units' => e(implode(', ', $removeUnitNames))]) !!}@else{!! __('livewire/operator/owner-detail.remove_units_generic') !!}@endif</li>
                                    {{-- ⚠️ Removal cascades to the owner's team (2026-08-31) — so the claim is true, and spelled out. --}}
                                    <li>@if ($removeTeamCount > 0){!! trans_choice('livewire/operator/owner-detail.remove_portal_ends_team', $removeTeamCount, ['count' => $removeTeamCount, 'name' => e($owner['name'])]) !!}@else{!! __('livewire/operator/owner-detail.remove_portal_ends', ['name' => e($owner['name'])]) !!}@endif</li>
                                    {{-- ⚠️ OwnerLoginLifecycle spares a person who still holds a back-office role — the owner too. --}}
                                    <li>{!! __('livewire/operator/owner-detail.remove_keeps_login', ['name' => e($owner['name'])]) !!}</li>
                                    <li>{!! __('livewire/operator/owner-detail.remove_records_archived') !!}</li>
                                    <li>{{ __('livewire/operator/owner-detail.remove_cannot_undo') }}</li>
                                </ul>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vyod-remove-name">{{ __('livewire/operator/owner-detail.type_name_to_confirm', ['name' => $owner['name']]) }}</label>
                                    <input id="vyod-remove-name" class="vy-input" type="text" name="confirm_name" value="{{ $vyForm === 'remove' ? old('confirm_name') : '' }}"
                                           autocomplete="off" required @if ($vyOpen === 'remove' && ! $errors->has('current_password')) autofocus @endif>
                                    @if ($vyForm === 'remove')
                                        @error('confirm_name')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                {{-- ⚠️ RE-AUTH (G2): the name is printed on this page, so a hijacked session can read it; the password is the
                                     factor such a session may not have. Its own row — the founder's report. --}}
                                <div class="vyo-confirm">
                                    {{-- the password-manager pattern: which account this password is for — unnamed, so never posted, and hidden from everyone --}}
                                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly hidden aria-hidden="true" tabindex="-1">
                                    <label class="vy-field-label" for="vyod-remove-pw">{{ __('livewire/operator/owner-detail.confirm_its_you') }}</label>
                                    <input id="vyod-remove-pw" class="vy-input" type="password" name="current_password" autocomplete="current-password" required placeholder="{{ __('livewire/operator/owner-detail.password_placeholder') }}"
                                           @if ($vyOpen === 'remove' && $errors->has('current_password')) autofocus @endif>
                                    @if ($vyForm === 'remove' && $errors->has('current_password'))
                                        <div class="vyo-fielderr">{{ $errors->first('current_password') }}</div>
                                    @else
                                        <p class="vyo-hint">{{ __('livewire/operator/owner-detail.remove_password_hint') }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="removeOpen = false">{{ __('livewire/operator/owner-detail.keep_owner') }}</button>
                                <button type="submit" class="vy-btn vy-btn--danger">{{ __('livewire/operator/owner-detail.remove_owner') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($invite !== null)
                    {{-- ══ SEND THE INVITE AGAIN — a fresh link is a way in, so it asks for your password, as sending did ══════════ --}}
                    <div class="vy-modal-backdrop" x-cloak x-show="resendOpen" x-transition.opacity x-on:click.self="resendOpen = false" x-on:keydown.escape.window="resendOpen = false">
                        <div class="vy-modal vy-modal-card vyo-dlg" x-show="resendOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-resend-t" tabindex="-1" x-trap.noscroll="resendOpen">
                            <form method="POST" action="{{ route('app.owners.invites.resend', $invite['id']) }}" data-guard-submit>
                                @csrf
                                <input type="hidden" name="_form" value="resend">
                                <input type="hidden" name="return" value="owner">
                                <div class="vy-modal-head">
                                    <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg></span>
                                    <div class="vyo-dlg-head">
                                        <h3 class="vy-modal-title" id="vyod-resend-t">{{ __('livewire/operator/owner-detail.resend_title') }}</h3>
                                        <p class="vyo-dlg-sub">{{ __('livewire/operator/owner-detail.resend_sub', ['email' => $invite['email']]) }}</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="resendOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body">
                                    @if ($vyOpen === 'resend' && session('owners_error'))
                                        <div class="vyo-errors" role="alert">{{ session('owners_error') }}</div>
                                    @endif
                                    <div class="vyo-confirm">
                                        <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly hidden aria-hidden="true" tabindex="-1">
                                        <label class="vy-field-label" for="vyod-resend-pw">{{ __('livewire/operator/owner-detail.confirm_its_you') }}</label>
                                        <input id="vyod-resend-pw" class="vy-input" type="password" name="current_password" autocomplete="current-password" required placeholder="{{ __('livewire/operator/owner-detail.password_placeholder') }}" autofocus>
                                        @if ($vyForm === 'resend')
                                            @error('current_password')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="resendOpen = false">{{ __('common.cancel') }}</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/owner-detail.send_again') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    @if ($invite['cancellable'])
                        {{-- ══ CANCEL THE INVITE — takes access away, so no password; an owner never activated leaves the list ═════ --}}
                        <div class="vy-modal-backdrop" x-cloak x-show="cancelOpen" x-transition.opacity x-on:click.self="cancelOpen = false" x-on:keydown.escape.window="cancelOpen = false">
                            <div class="vy-modal vy-modal-card vyo-dlg" x-show="cancelOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-cancel-t" tabindex="-1" x-trap.noscroll="cancelOpen">
                                <form method="POST" action="{{ route('app.owners.invites.cancel', $invite['id']) }}" data-guard-submit>
                                    @csrf
                                    <input type="hidden" name="return" value="owner">
                                    <div class="vy-modal-head">
                                        <span class="vy-modal-ico is-danger" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg></span>
                                        <div class="vyo-dlg-head">
                                            <h3 class="vy-modal-title" id="vyod-cancel-t">{{ __('livewire/operator/owner-detail.cancel_invite_title') }}</h3>
                                            <p class="vyo-dlg-sub">{{ __('livewire/operator/owner-detail.cancel_invite_sub', ['email' => $invite['email']]) }}</p>
                                        </div>
                                        <button type="button" class="vy-modal-x" x-on:click="cancelOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                    </div>
                                    <div class="vy-modal-body">
                                        <ul class="vyo-factlist">
                                            @if ($inviteCancelRemoves)
                                                <li>{{ __('livewire/operator/owner-detail.cancel_invite_removes', ['name' => $owner['name']]) }}</li>
                                            @endif
                                            <li>{{ __('livewire/operator/owner-detail.cancel_invite_again_later') }}</li>
                                        </ul>
                                    </div>
                                    <div class="vy-modal-foot">
                                        <button type="button" class="vy-btn vy-btn--ghost" x-on:click="cancelOpen = false">{{ __('livewire/operator/owner-detail.keep_invite') }}</button>
                                        <button type="submit" class="vy-btn vy-btn--danger">{{ __('livewire/operator/owner-detail.cancel_invite') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                @endif
            @endif

            @if ($canPayout)
                {{-- ══ CLOSE A PERIOD (audit H-2) ══════════════════════════════════════════════════════════════════════════════
                     ⚠️ EVERY CLAIM BELOW WAS VERIFIED AGAINST THE CODE: finalize() seals the statement; PeriodLockGuard makes a later
                     line dated inside a sealed period a prior-period adjustment on the NEXT statement; the collection gate leaves
                     uncollected revenue for a later one; closing pays no one. No timing is promised and no email is claimed. --}}
                <div class="vy-modal-backdrop" x-cloak x-show="closeOpen" x-transition.opacity x-on:click.self="closeOpen = false" x-on:keydown.escape.window="closeOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg" x-show="closeOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-close-t" tabindex="-1" x-trap.noscroll="closeOpen">
                        <form method="POST" action="{{ route('app.owners.close-period', $ownerId) }}" data-guard-submit>
                            @csrf
                            <input type="hidden" name="_form" value="close">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M9 15l2 2 4-4"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyod-close-t">{{ __('livewire/operator/owner-detail.close_title', ['name' => $owner['name']]) }}</h3>
                                    <p class="vyo-dlg-sub">{{ __('livewire/operator/owner-detail.close_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="closeOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($vyOpen === 'close' && session('owners_error'))
                                    <div class="vyo-errors" role="alert">{{ session('owners_error') }}</div>
                                @endif
                                @if ($closePeriod['lockedThroughWords'])
                                    <p class="vyo-closed-upto">{{ __('livewire/operator/owner-detail.closed_up_to', ['date' => $closePeriod['lockedThroughWords']]) }}</p>
                                @endif
                                <div class="vyo-pair">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyod-start">{{ __('livewire/operator/owner-detail.first_day') }}</label>
                                        <input id="vyod-start" class="vy-input" type="date" name="period_start" x-model="start" value="{{ old('period_start', $closePeriod['start']) }}"
                                               @if ($closePeriod['minStart']) min="{{ $closePeriod['minStart'] }}" @endif required>
                                        @if ($vyForm === 'close')
                                            @error('period_start')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyod-end">{{ __('livewire/operator/owner-detail.last_day') }}</label>
                                        <input id="vyod-end" class="vy-input" type="date" name="period_end" x-model="end" value="{{ old('period_end', $closePeriod['end']) }}"
                                               @if ($closePeriod['minStart']) min="{{ $closePeriod['minStart'] }}" @endif required>
                                        @if ($vyForm === 'close')
                                            @error('period_end')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                </div>
                                <p class="vyo-span" x-text="span()" aria-live="polite"></p>
                                <ul class="vyo-factlist">
                                    <li>{!! __('livewire/operator/owner-detail.close_fact_sealed') !!}</li>
                                    <li>{!! __('livewire/operator/owner-detail.close_fact_adjustment') !!}</li>
                                    <li>{!! __('livewire/operator/owner-detail.close_fact_uncollected') !!}</li>
                                    <li>{{ __('livewire/operator/owner-detail.close_fact_portal', ['name' => $owner['name']]) }}</li>
                                    <li>{!! __('livewire/operator/owner-detail.close_fact_nothing_paid') !!}</li>
                                </ul>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="closeOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/owner-detail.close_period') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if (count($roomTypes) > 0)
                @can('rates.manage')
                    {{-- ══ PROPOSE A RATE CHANGE — the owner approves, counters or declines it; nothing changes until they do ═══════ --}}
                    <div class="vy-modal-backdrop" x-cloak x-show="proposeOpen" x-transition.opacity x-on:click.self="proposeOpen = false" x-on:keydown.escape.window="proposeOpen = false">
                        <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg" x-show="proposeOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-propose-t" tabindex="-1" x-trap.noscroll="proposeOpen">
                            <form method="POST" action="{{ route('app.rate-proposals.store') }}" data-guard-submit>
                                @csrf
                                <input type="hidden" name="_form" value="propose">
                                <input type="hidden" name="owner_id" value="{{ $ownerId }}">
                                <div class="vy-modal-head">
                                    <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg></span>
                                    <div class="vyo-dlg-head">
                                        <h3 class="vy-modal-title" id="vyod-propose-t">{{ __('livewire/operator/owner-detail.propose_title') }}</h3>
                                        <p class="vyo-dlg-sub">{{ __('livewire/operator/owner-detail.propose_sub', ['name' => $owner['name']]) }}</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="proposeOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body">
                                    @if ($vyOpen === 'propose' && session('rate_error'))
                                        <div class="vyo-errors" role="alert">{{ session('rate_error') }}</div>
                                    @endif
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyod-rt">{{ __('livewire/operator/owner-detail.room_type') }}</label>
                                        <select id="vyod-rt" name="room_type_id" class="vy-input" required>
                                            @foreach ($roomTypes as $rt)
                                                <option value="{{ $rt['id'] }}" @selected($vyForm === 'propose' && old('room_type_id') === $rt['id'])>{{ $rt['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="vyo-trio">
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyod-prate">{{ __('livewire/operator/owner-detail.nightly_rate', ['symbol' => $rateCurrencySymbol]) }}</label>
                                            <input id="vyod-prate" class="vy-input" type="text" inputmode="decimal" name="rate" maxlength="20" required placeholder="185" autofocus
                                                   value="{{ $vyForm === 'propose' ? old('rate') : '' }}">
                                            @if ($vyForm === 'propose')
                                                @error('rate')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                            @endif
                                        </div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyod-uplift">{!! __('livewire/operator/owner-detail.weekend_uplift_label') !!}</label>
                                            <input id="vyod-uplift" class="vy-input" type="text" inputmode="decimal" name="weekend_uplift" maxlength="6" placeholder="{{ __('livewire/operator/owner-detail.example_placeholder', ['value' => '10']) }}"
                                                   value="{{ $vyForm === 'propose' ? old('weekend_uplift') : '' }}">
                                            @if ($vyForm === 'propose')
                                                @error('weekend_uplift')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                            @endif
                                        </div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyod-minstay">{!! __('livewire/operator/owner-detail.min_stay_label') !!}</label>
                                            <input id="vyod-minstay" class="vy-input" type="text" inputmode="numeric" name="min_stay" maxlength="3" placeholder="{{ __('livewire/operator/owner-detail.example_placeholder', ['value' => '2']) }}"
                                                   value="{{ $vyForm === 'propose' ? old('min_stay') : '' }}">
                                            @if ($vyForm === 'propose')
                                                @error('min_stay')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                            @endif
                                        </div>
                                    </div>
                                    <div class="vyo-pair">
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyod-pfrom">{{ __('livewire/operator/owner-detail.first_night') }}</label>
                                            <input id="vyod-pfrom" class="vy-input" type="date" name="from_date" required
                                                   value="{{ $vyForm === 'propose' ? old('from_date') : now()->toDateString() }}">
                                            @if ($vyForm === 'propose')
                                                @error('from_date')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                            @endif
                                        </div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyod-pto">{{ __('livewire/operator/owner-detail.last_night') }}</label>
                                            <input id="vyod-pto" class="vy-input" type="date" name="to_date" required
                                                   value="{{ $vyForm === 'propose' ? old('to_date') : now()->addYear()->subDay()->toDateString() }}">
                                            @if ($vyForm === 'propose')
                                                @error('to_date')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                            @endif
                                        </div>
                                    </div>
                                    <div class="vy-field vyo-field-last">
                                        <label class="vy-field-label" for="vyod-pnote">{!! __('livewire/operator/owner-detail.note_to_label', ['name' => e($owner['name'])]) !!}</label>
                                        <input id="vyod-pnote" class="vy-input" type="text" name="note" maxlength="500" value="{{ $vyForm === 'propose' ? old('note') : '' }}">
                                        @if ($vyForm === 'propose')
                                            @error('note')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="proposeOpen = false">{{ __('common.cancel') }}</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/owner-detail.send_proposal') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endcan
            @endif
        </div>
    </x-operator.shell>

    {{-- The page's dialogs open and close here, so they need this script; the note and the rate-proposal row forms post without
         it. The period line reads the two dates in words. --}}
    <script>
        document.addEventListener('alpine:init', () => {
            // I18N-01: the month names, the error and the "N days" plural are all worked out on the server (CONVERSION.md rules 5, 12)
            const months = @js(collect(range(1, 12))->map(fn (int $m): string => \Carbon\CarbonImmutable::create(2024, $m, 1)->translatedFormat('M'))->all());
            const dayWords = @js(collect(range(1, 366))->mapWithKeys(fn (int $n): array => [$n => trans_choice('livewire/operator/owner-detail.span_days', $n, ['count' => $n])])->all());
            const dayWordsMany = @js(trans_choice('livewire/operator/owner-detail.span_days', 1000, ['count' => ':n']));
            const spanText = @js(__('livewire/operator/owner-detail.span', ['from' => ':from', 'to' => ':to', 'days' => ':days']));
            const day = (iso) => {
                const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
                return m ? { y: +m[1], m: +m[2] - 1, d: +m[3], t: Date.UTC(+m[1], +m[2] - 1, +m[3]) } : null;
            };
            Alpine.data('vyOwnerPage', (state) => Object.assign({
                span() {
                    const a = day(this.start), b = day(this.end);
                    if (! a || ! b) return '';
                    if (b.t < a.t) return @js(__('livewire/operator/owner-detail.span_backwards'));
                    const days = Math.round((b.t - a.t) / 86400000) + 1;
                    const from = a.d + ' ' + months[a.m] + (a.y !== b.y ? ' ' + a.y : '');
                    const to = b.d + ' ' + months[b.m] + ' ' + b.y;
                    const words = dayWords[days] || dayWordsMany.replace(':n', days);
                    return spanText.replace(':from', from).replace(':to', to).replace(':days', words);
                },
            }, state));
        });
    </script>
</div>
