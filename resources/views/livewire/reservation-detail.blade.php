{{-- Operator Reservation Detail — reservation + folio (design pass · Operator Screen 4). Styled on
     the shared operator design system via <x-operator.shell>. The folio is rendered as the real
     APPEND-ONLY ledger; totals reconcile to the cent. The page READS; record-payment/refund post to
     the real FolioService action (append-only) and persist. Presentation only otherwise. --}}
<div class="vy-det">
    {{-- The design titles this page with the GUEST'S NAME and puts the reference in the sub-line. The name
         is already shown on the page — this is presentation, not a widening of the #28 perimeter. --}}
    <x-operator.shell active="reservations" :title="$reservation['guest'] ?: $reservation['reference']"
        :subtitle="$reservation['reference'] . ' · ' . $reservation['status_label']" :viewer="$viewer">

        {{-- ⚠️ ONE component decides refusal-vs-success (audit D4). This view used to hardcode a tick, so
             a refused refund or a lost inventory race rendered as a receipt. --}}
        @if (session('folio_notice'))
            <x-operator.flash-notice :message="session('folio_notice')" :refused="(bool) session('folio_refused', false)" />
        @endif

        <div class="vy-detail-cols vy-fade">
            {{-- MAIN: terminal state (when real) + stay summary + units + folio ledger --}}
            <div class="vy-stack">
                {{-- TERMINAL STATE — rendered ONLY when the real status is cancelled/no_show. Every value
                     comes from a column AssignmentService::terminate() genuinely wrote. `cancelled_at` is
                     labelled per state (a no-show stamps the same shared column); the reason row is OMITTED
                     when there is none (a no-show has none) rather than faked with a dash. --}}
                @if ($terminal !== null)
                    {{-- The design states a terminal reservation as a TINTED BANNER, keyed by which terminal
                         state it is — rose for a cancellation, amber for a no-show. The state is read from
                         the real at_label the component already derives, not from a second status read. --}}
                    @php $vyTerm = $terminal['is_no_show'] ? 'is-no-show' : 'is-cancelled'; @endphp
                    <div class="vy-card vy-term {{ $vyTerm }}">
                        <div class="vy-detail-head">
                            <span class="vy-term-head">
                                <span class="vy-term-ico" aria-hidden="true">
                                    @if ($vyTerm === 'is-no-show')
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                                    @else
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6"/><path d="M15 9l-6 6"/></svg>
                                    @endif
                                </span>
                                <span class="vy-eyebrow">{{ $terminal['label'] }}</span>
                            </span>
                            <span class="vy-tag tone-{{ $terminal['stage_tone'] }}"><span class="vy-dot"></span>{{ $terminal['stage_label'] }}</span>
                        </div>

                        <div class="vy-facts" style="margin-top: 14px;">
                            @if ($terminal['at'])
                                <div><div class="vy-fact-label">{{ $terminal['at_label'] }}</div><div class="vy-fact-val">{{ $terminal['at'] }}</div></div>
                            @endif
                            @if ($terminal['reason'])
                                <div><div class="vy-fact-label">{{ __('livewire/reservation-detail.reason') }}</div><div class="vy-fact-val">{{ $terminal['reason'] }}</div></div>
                            @endif
                        </div>

                        {{-- REAL + automatic: terminate() → releaseLine() frees `sold`. Claims nothing more. --}}
                        <div class="vy-callout is-oos" style="margin-top: 14px;">
                            <div class="vy-callout-title">{{ __('livewire/reservation-detail.inventory_released') }}</div>
                            {{ $terminal['released'] }}
                        </div>

                        {{-- DEFERRED (locked decision 5): there is no fee engine. A label, never a figure. --}}
                        <div class="vy-action-list" style="margin-top: 12px;">
                            <div class="vy-defer-item"><span>{{ $terminal['fee_label'] }}</span><span class="vy-soon-tag">{{ __('livewire/reservation-detail.soon') }}</span></div>
                        </div>
                        <div class="vy-recon" style="margin-top: 6px;">{{ __('livewire/reservation-detail.no_fee_calculated') }}</div>
                    </div>
                @endif

                <div class="vy-card">
                    <div class="vy-detail-head">
                        <span class="vy-detail-ref">{{ $reservation['reference'] }}</span>
                        <span class="vy-tag tone-{{ $reservation['tone'] }}"><span class="vy-dot"></span>{{ $reservation['status_label'] }}</span>
                    </div>
                    <div class="vy-facts" style="margin-top: 14px;">
                        <div><div class="vy-fact-label">{{ __('livewire/reservation-detail.guest') }}</div><div class="vy-fact-val">{{ $reservation['guest'] ?? '—' }}@if (($reservation['guest_id'] ?? null) && auth()->user()?->can('guests.view'))<a href="{{ route('app.guests.show', $reservation['guest_id']) }}" style="margin-left:8px;font-size:12px;font-weight:600;color:var(--vy-accent)">{{ __('livewire/reservation-detail.view_profile') }}</a>@endif</div></div>
                        <div><div class="vy-fact-label">{{ __('livewire/reservation-detail.source') }}</div><div class="vy-fact-val">{{ $reservation['source'] }}</div></div>
                        <div><div class="vy-fact-label">{{ __('livewire/reservation-detail.check_in_out') }}</div><div class="vy-fact-val">{{ $reservation['check_in'] }} → {{ $reservation['check_out'] }}</div></div>
                        <div><div class="vy-fact-label">{{ __('livewire/reservation-detail.nights_guests') }}</div><div class="vy-fact-val">{{ __('livewire/reservation-detail.nights_and_guests', ['nights' => trans_choice('livewire/reservation-detail.nights_count', $reservation['nights'], ['count' => $reservation['nights']]), 'guests' => trans_choice('livewire/reservation-detail.guests_count', $reservation['guests'], ['count' => $reservation['guests']])]) }}</div></div>
                    </div>
                </div>

                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 10px;">{{ __('livewire/reservation-detail.units') }}</h2>
                    @foreach ($units as $unit)
                        <div class="vy-unit-row">
                            <div>
                                <div class="vy-um-name">{{ $unit['room_type'] }} · {{ $unit['unit'] ? __('livewire/reservation-detail.unit_code', ['code' => $unit['unit']]) : __('livewire/reservation-detail.unassigned') }}</div>
                                <div class="vy-um-sub">{{ $unit['check_in'] }} → {{ $unit['check_out'] }} · {{ $unit['status_label'] }}</div>
                            </div>
                            <div class="vy-ledger-amt tone-charge">{{ $unit['price'] }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- STATUS TIMELINE — the REAL `reservation_status_history` append-only audit trail. Both
                     sides of every transition are the real status enum via reservationStatusMeta(); the
                     actor is the real `changed_by` user, or "System" where it is genuinely null (an OTA
                     webhook cancel has no authenticated user). Nothing is invented. --}}
                @if ($timeline !== [])
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 6px;">{{ __('livewire/reservation-detail.activity') }}</h2>
                        <div class="vy-timeline">
                            @foreach ($timeline as $event)
                                <div class="vy-tl-item">
                                    <div class="vy-tl-rail"><span class="vy-tl-dot" @if (!empty($event['is_money'])) style="background:var(--vy-accent)" @endif></span><span class="vy-tl-line"></span></div>
                                    <div class="vy-tl-body">
                                        <div class="vy-tl-label">{{ $event['label'] }}</div>
                                        <div class="vy-tl-meta">{{ $event['actor'] }} · {{ $event['at'] }}</div>
                                        @if ($event['reason'])
                                            <div class="vy-tl-meta">{{ $event['reason'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 6px;">{{ __('livewire/reservation-detail.folio') }}</h2>

                    {{-- ⚠️⚠️ THE BILLED STATE, AND ITS UNDO. Billing a folio to a company account is recorded on
                         the folio (`billed_to_account_id`), so this is where it is read — and until 2026-09-22
                         there was no way to read it and no way to take it back: `unbill-from-account` had no
                         view, no component, no URI and **no test**.
                         ⚠️ A BILLING ACTION WITH NO UNDO IS A ONE-WAY DOOR ON A MONEY PATH — the same lesson
                         R7 and R9 cost two passes to learn about check-in.
                         ⚠️ It says plainly what billing did and did NOT do: the money is still owed. --}}
                    @if ($billed_to !== null)
                        <div class="vy-recon" style="margin-bottom:10px">
                            {!! __('livewire/reservation-detail.billed_to', ['account' => e($billed_to)]) !!}
                        </div>
                        @if ($can_unbill_from_account)
                            <form method="POST" action="{{ route('app.reservations.unbill-from-account', $reservation['id']) }}" style="margin-bottom:12px"
                                onsubmit="return confirm(@js(__('livewire/reservation-detail.unbill_confirm')));">
                                @csrf
                                <button type="submit" class="vy-act vy-act--ghost">{{ __('livewire/reservation-detail.unbill_button') }}</button>
                            </form>
                        @endif
                    @endif

                    @if ($folio === null)
                        <div class="vy-empty">{{ __('livewire/reservation-detail.no_folio') }}</div>
                    @else
                        <div class="vy-ledger">
                            @forelse ($folio['ledger'] as $line)
                                {{-- The design leads each folio line with its DATE, then the description,
                                     then the amount. Same real line, same real figure — order only. --}}
                                <div class="vy-ledger-row">
                                    <span class="vy-ledger-date">{{ $line['date'] }}</span>
                                    <div class="vy-ledger-main">
                                        <div class="vy-ledger-label">
                                            {{ $line['label'] }}
                                            @if ($line['flag'])<span class="vy-ledger-flag">{{ $line['flag'] }}</span>@endif
                                        </div>
                                        <div class="vy-ledger-sub">{{ $line['desc'] }}</div>
                                    </div>
                                    <span class="vy-ledger-amt tone-{{ $line['tone'] }}">{{ $line['amount'] }}</span>
                                    {{-- `can` as well as `refundable`: the route is gated on payments.refund
                                         (2026-07-17), which Hotel Manager and Front Desk deliberately do NOT
                                         hold. A button that 403s is the authorization twin of the #27 bug —
                                         the UI must never invite a rejection. --}}
                                    @if ($line['refundable'] && auth()->user()?->can('payments.refund'))
                                        {{-- WIRED: a full refund is a NEW offsetting entry (append-only) via FolioService. --}}
                                        <form method="POST" action="{{ route('app.reservations.refund', $reservation['id']) }}" class="vy-refund-btn">
                                            @csrf
                                            <input type="hidden" name="payment" value="{{ $line['payment_id'] }}">
                                            <button type="submit" class="vy-act vy-act--ghost">{{ __('livewire/reservation-detail.refund_button') }}</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <div class="vy-empty">{{ __('livewire/reservation-detail.no_folio_lines') }}</div>
                            @endforelse
                        </div>

                        {{-- The design sets the three totals SIDE BY SIDE with the balance carrying the
                             emphasis. Same three real figures from FolioTotals — layout only. --}}
                        <div class="vy-totals">
                            <div class="vy-total-row"><span class="vy-t-label">{{ __('livewire/reservation-detail.charges') }}</span><span class="vy-t-val">{{ $folio['charges'] }}</span></div>
                            <div class="vy-total-row"><span class="vy-t-label">{{ __('livewire/reservation-detail.paid') }}</span><span class="vy-t-val">{{ $folio['paid'] }}</span></div>
                            <div class="vy-total-row is-balance @unless ($folio['has_balance']) is-settled @endunless">
                                <span class="vy-t-label">{{ __('livewire/reservation-detail.balance_due') }}</span><span class="vy-t-val">{{ $folio['balance'] }}</span>
                            </div>
                        </div>
                        {{-- "Paid" is NET of refunds by construction (paid = received − refunded). A refund is
                             never hidden: it posts its OWN offsetting ledger line above (correct-by-reversal),
                             so the net figure is always explained by a visible row (the WAVE-1 lesson). --}}

                        @if ($folio['reconciles'])
                            <span class="vy-recon">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                {{ __('livewire/reservation-detail.reconciled') }}
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            {{-- RIGHT RAIL: balance-due panel (design) + lifecycle (wired) + policy + record payment (wired) + deferred actions --}}
            <div class="vy-stack">
                {{-- BALANCE DUE — the design's prominent dark balance panel. REAL folio figures (reconciled
                     to the ledger); an honest Settled/Due state, no fabricated progress ratio. Reuses the
                     shared dark stat panel (.vy-tonight) introduced on the Dashboard. --}}
                @if ($folio !== null)
                    <div class="vy-tonight">
                        <span class="vy-eyebrow">{{ __('livewire/reservation-detail.balance_due') }}</span>
                        <div class="vy-tonight-val">{{ $folio['balance'] }}</div>
                        <div class="vy-tonight-sub">{{ __('livewire/reservation-detail.of_total', ['amount' => $folio['charges']]) }}</div>
                        <div class="vy-tonight-row">
                            <div class="vy-tonight-cell"><div class="vy-tc-k">{{ __('livewire/reservation-detail.paid') }}</div><div class="vy-tc-v">{{ $folio['paid'] }}</div></div>
                            <div class="vy-tonight-cell"><div class="vy-tc-k">{{ __('common.status') }}</div><div class="vy-tc-v">{{ $folio['has_balance'] ? __('livewire/reservation-detail.due') : __('livewire/reservation-detail.settled') }}</div></div>
                        </div>
                    </div>
                @endif

                {{-- WIRED: cancel + mark-no-show POST through ReservationLifecycleController →
                     AssignmentService (the single inventory write path). Each is offered ONLY when the REAL
                     current status allows it — the gates are the controller's own constants, so the UI can
                     never offer a transition the write path refuses. --}}
                @if ($can_cancel || $can_no_show || $can_confirm_hold || $can_reverse_check_in || $can_reverse_check_out)
                    <div class="vy-card">
                        <span class="vy-eyebrow">{{ __('livewire/reservation-detail.lifecycle') }}</span>

                        {{-- ⚠️ R5 (F-60) — a TENTATIVE group hold becomes a real stay. It is offered FIRST
                             because it is the only forward move from a hold, and a desk facing the guest
                             needs it before the two closing actions. The nights are ALREADY held, so this
                             converts the hold and takes no second one. --}}
                        @if ($can_confirm_hold)
                            <form method="POST" action="{{ route('app.reservations.confirm', $reservation['id']) }}" style="margin-top: 12px;"
                                onsubmit="return confirm(@js(__('livewire/reservation-detail.confirm_hold_confirm')));">
                                @csrf
                                <button type="submit" class="vy-act">{{ __('livewire/reservation-detail.confirm_hold_button') }}</button>
                            </form>
                            <div class="vy-recon" style="margin-top: 8px;">{{ __('livewire/reservation-detail.confirm_hold_note') }}</div>
                        @endif

                        @if ($can_no_show)
                            <form method="POST" action="{{ route('app.reservations.no-show', $reservation['id']) }}" style="margin-top: 12px;"
                                onsubmit="return confirm(@js(__('livewire/reservation-detail.no_show_confirm')));">
                                @csrf
                                <button type="submit" class="vy-act vy-act--ghost">{{ __('livewire/reservation-detail.no_show_button') }}</button>
                            </form>
                            <div class="vy-recon" style="margin-top: 8px;">{{ __('livewire/reservation-detail.no_show_note') }}</div>
                        @endif

                        @if ($can_cancel)
                            <form method="POST" action="{{ route('app.reservations.cancel', $reservation['id']) }}" style="margin-top: 14px;"
                                onsubmit="return confirm(@js(__('livewire/reservation-detail.cancel_confirm')));">
                                @csrf
                                <label class="vy-field-label" for="cancel-reason">{{ __('livewire/reservation-detail.cancel_reason_label') }}</label>
                                <div class="vy-input-group">
                                    <input class="vy-input" type="text" name="reason" id="cancel-reason" maxlength="255" placeholder="{{ __('livewire/reservation-detail.cancel_reason_placeholder') }}">
                                    <button type="submit" class="vy-act vy-act--accent">{{ __('livewire/reservation-detail.cancel_button') }}</button>
                                </div>
                            </form>
                            <div class="vy-recon" style="margin-top: 8px;">{{ __('livewire/reservation-detail.cancel_note') }}</div>
                        @endif

                        {{-- ⚠️⚠️ R9 / R3 — THE TWO REVERSALS. ⚠️ THESE ARE LINKS (GET), NOT SUBMIT BUTTONS, AND THAT
                             IS THE DESIGN RATHER THAN A SHORTCUT: each one leads to the action's own confirmation
                             page, which renders that action's preview() — what will be reversed and, more
                             importantly, WHAT WILL NOT. ⚠️ **A one-click reverse that skipped the disclosure would
                             be a regression dressed as a convenience**, and for R3 it would be a real one: a
                             checkout fans out (departure clean, dirty unit, notifications, a review email that
                             cannot be recalled) and the page is where an operator learns which of those stand.
                             ⚠️ Gated on the SAME permission as the route it leads to, so the UI never invites a
                             403 — and on the status, so it never offers a transition the action would refuse. --}}
                        @if ($can_reverse_check_in)
                            <a href="{{ route('app.front-desk.reverse-checkin', $reservation['id']) }}"
                               class="vy-act vy-act--ghost" style="margin-top: 14px; display:block; text-align:center; text-decoration:none;">{{ __('livewire/reservation-detail.reverse_check_in') }}</a>
                            <div class="vy-recon" style="margin-top: 8px;">{{ __('livewire/reservation-detail.reverse_check_in_note') }}</div>
                        @endif

                        @if ($can_reverse_check_out)
                            <a href="{{ route('app.front-desk.reverse-checkout', $reservation['id']) }}"
                               class="vy-act vy-act--ghost" style="margin-top: 14px; display:block; text-align:center; text-decoration:none;">{{ __('livewire/reservation-detail.reverse_check_out') }}</a>
                            <div class="vy-recon" style="margin-top: 8px;">{{ __('livewire/reservation-detail.reverse_check_out_note') }}</div>
                        @endif
                    </div>
                @endif

                {{-- NOTES & REQUESTS (wireframe-parity WAVE 2 · S2) — REAL, read-only.
                     These two columns were WRITE-ONLY: online check-in and New Reservation both wrote them and
                     no view rendered either, so a guest's "please leave a cot" reached no member of staff.
                     🪤 Only genuine notes render — the wireframe's sample rows and its "Add note" button are
                     deliberately absent (reservations have no note-write path; an inert button would assert a
                     capability that does not exist). No notes ⇒ no card, never an empty shell. --}}
                @if (count($notes) > 0)
                    <div class="vy-card">
                        <span class="vy-eyebrow">{{ __('livewire/reservation-detail.notes_requests') }}</span>
                        @foreach ($notes as $note)
                            <div style="margin-top: 12px;">
                                <div class="vy-field-label">{{ $note['label'] }}</div>
                                <div class="vy-fact-val" style="white-space: pre-line; font-weight: 400;">{{ $note['body'] }}</div>
                            </div>
                        @endforeach
                        <div class="vy-recon" style="margin-top: 12px;">{{ __('livewire/reservation-detail.notes_captured') }}</div>
                    </div>
                @endif

                {{-- REAL, read-only: the property's CONFIGURED policy (`property_policies.cancellation`).
                     CONTEXT ONLY — no fee is computed against this stay; the copy says so explicitly. --}}
                @if ($policy !== null)
                    <div class="vy-card">
                        <span class="vy-eyebrow">{{ __('livewire/reservation-detail.cancellation_policy') }}</span>
                        <div class="vy-fact-val" style="margin-top: 10px;">{{ $policy['summary'] }}</div>
                        <div class="vy-recon" style="margin-top: 6px;">{{ __('livewire/reservation-detail.policy_note') }}</div>
                    </div>
                @endif

                @can('payments.record')
                <div class="vy-card vy-card--accent">
                    <span class="vy-eyebrow">{{ __('livewire/reservation-detail.record_payment') }}</span>
                    {{-- WIRED: posts through FolioService::recordPayment (append-only, integer #17). Batch C ·
                         GATE 3 — a per-submit idempotency nonce records a double-click ONCE; a method selector
                         (Cash / Bank transfer = real manual now · Card = the Stripe test-mode seam, deferred). --}}
                    <form method="POST" action="{{ route('app.reservations.payment', $reservation['id']) }}" style="margin-top: 12px;"
                          data-guard-submit>
                        @csrf
                        <input type="hidden" name="idempotency_key" value="{{ $payment_nonce }}">
                        <label class="vy-field-label" for="pay_method">{{ __('livewire/reservation-detail.method') }}</label>
                        <select class="vy-input" name="method" id="pay_method" style="margin-bottom: 8px;">
                            <option value="cash">{{ __('livewire/reservation-detail.method_cash') }}</option>
                            <option value="bank_transfer">{{ __('livewire/reservation-detail.method_bank_transfer') }}</option>
                            <option value="card">{{ __('livewire/reservation-detail.method_card') }}</option>
                        </select>
                        <label class="vy-field-label" for="amount">{{ __('livewire/reservation-detail.amount_currency', ['currency' => $reservation['currency']]) }}</label>
                        <div class="vy-input-group">
                            <label class="vy-field-label" for="amount">0.00</label>
                            <input class="vy-input" type="number" inputmode="decimal" step="0.01" min="0" name="amount" id="amount" placeholder="0.00">
                            <x-busy-btn class="vy-btn vy-btn--accent" :busy-label="__('livewire/reservation-detail.recording')">{{ __('livewire/reservation-detail.record_button') }}</x-busy-btn>
                        </div>
                    </form>
                    <div class="vy-recon" style="margin-top: 12px;">{{ __('livewire/reservation-detail.payment_note') }}</div>
                </div>
                @endcan

                <div class="vy-card">
                    <span class="vy-eyebrow">{{ __('livewire/reservation-detail.more_actions') }}</span>

                    {{-- Batch C · GATE 2 — MOVE ROOM: only SAME-room-type units free for the dates (Q5). The
                         write goes through AssignmentService::assignUnit (availability lock + no-overlap +
                         same-type), never a raw update — so the picker offers free units and the service is
                         the authority under the lock. No free same-type unit → an honest note, never a fake. --}}
                    @if ($can_move)
                        @foreach ($units as $u)
                            @if ($u['status'] === 'active' && count($u['move_targets']) > 0)
                                <form method="POST" action="{{ route('app.reservations.move', $reservation['id']) }}" style="margin-top: 12px;"
                                      data-guard-submit>
                                    @csrf
                                    <input type="hidden" name="reservation_unit_id" value="{{ $u['id'] }}">
                                    <label class="vy-field-label">{{ $u['unit'] ? __('livewire/reservation-detail.move_unit', ['code' => $u['unit']]) : __('livewire/reservation-detail.move_room_type', ['room_type' => $u['room_type']]) }}</label>
                                    <div class="vy-input-group">
                                        <select name="unit_id" class="vy-input" aria-label="{{ __('livewire/reservation-detail.move_to_unit') }}">
                                            @foreach ($u['move_targets'] as $t)
                                                <option value="{{ $t['id'] }}">{{ __('livewire/reservation-detail.unit_code', ['code' => $t['code']]) }}</option>
                                            @endforeach
                                        </select>
                                        <x-busy-btn class="vy-btn vy-btn--ghost" :busy-label="__('livewire/reservation-detail.moving')">{{ __('livewire/reservation-detail.move_room_button') }}</x-busy-btn>
                                    </div>

                                    {{-- ⚠️ MID-STAY: WHICH NIGHT DID THE GUEST FIRST SLEEP IN THE NEW UNIT?
                                         Shown only once the stay has started — before arrival the whole stay
                                         goes to the new unit's owner and there is nothing to split.
                                         ⚠️ DEFAULTS TO TODAY so the honest case is one click; changing it is a
                                         deliberate act, and the date you state is recorded alongside the moment
                                         you stated it. ⚠️ Same wording as the audit and the guide, on purpose:
                                         two people reading "the move date" differently mis-attributes money. --}}
                                    @if ($stayStarted ?? false)
                                        <label class="vy-field-label" style="margin-top:10px">{{ __('livewire/reservation-detail.first_night_new_unit') }}</label>
                                        <input type="date" name="effective_date" class="vy-input"
                                               value="{{ $today ?? '' }}" max="{{ $today ?? '' }}">
                                        <p style="font-size:11.5px;color:var(--vy-muted);margin:4px 0 0">
                                            {{ __('livewire/reservation-detail.first_night_help') }}
                                        </p>
                                    @endif
                                </form>
                            @elseif ($u['status'] === 'active')
                                <div class="vy-defer-item" style="margin-top: 12px;"><span>{{ $u['unit'] ? __('livewire/reservation-detail.move_unit', ['code' => $u['unit']]) : __('livewire/reservation-detail.move_room_type', ['room_type' => $u['room_type']]) }}</span><span class="vy-soon-tag" title="{{ __('livewire/reservation-detail.no_free_units_title') }}">{{ __('livewire/reservation-detail.no_free_units') }}</span></div>
                            @endif
                        @endforeach
                    @endif

                    {{-- Batch C · GATE 2 — ADD CHARGE: a new folio line via FolioService::postCharge (#17
                         integer, append-only). Gated `folios.manage`. --}}
                    @can('folios.manage')
                        <form method="POST" action="{{ route('app.reservations.charge', $reservation['id']) }}" style="margin-top: 14px;"
                              data-guard-submit>
                            @csrf
                            {{-- Server-side idempotency: a duplicate submission (double-click / raced retry) records ONCE. --}}
                            <input type="hidden" name="idempotency_key" value="{{ $charge_nonce }}">
                            <label class="vy-field-label">{{ __('livewire/reservation-detail.add_charge') }}</label>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px;">
                                <select name="type" class="vy-input" style="flex: 0 0 auto;" aria-label="{{ __('livewire/reservation-detail.charge_type') }}">
                                    @foreach ($charge_types as $ct)
                                        <option value="{{ $ct['value'] }}">{{ $ct['label'] }}</option>
                                    @endforeach
                                </select>
                                <label class="vy-field-label" for="vy-lbl-reservation-detail-description">{{ __('livewire/reservation-detail.description') }}</label>
                                <input id="vy-lbl-reservation-detail-description" class="vy-input" type="text" name="description" maxlength="120" placeholder="{{ __('livewire/reservation-detail.description') }}" style="flex: 1 1 110px;">
                                <input class="vy-input" type="number" inputmode="decimal" step="0.01" min="0" name="amount" placeholder="0.00" style="flex: 0 0 88px;">
                                <x-busy-btn class="vy-btn vy-btn--ghost" :busy-label="__('livewire/reservation-detail.adding')">{{ __('common.add') }}</x-busy-btn>
                            </div>
                        </form>
                    @endcan

                    {{-- Still deferred — need more UI / a policy subsystem; honest "Soon", never faked. Cancel
                         is wired in the Lifecycle card above; a full refund is inline per settled payment. --}}
                    <div class="vy-action-list" style="margin-top: 14px;">
                        <div class="vy-defer-item"><span>{{ __('livewire/reservation-detail.void') }}</span><span class="vy-soon-tag">{{ __('livewire/reservation-detail.soon') }}</span></div>
                        <div class="vy-defer-item"><span>{{ __('livewire/reservation-detail.modify_dates_guests') }}</span><span class="vy-soon-tag">{{ __('livewire/reservation-detail.soon') }}</span></div>
                    </div>
                    {{-- WIRED (Wave 2 · sub-gate 3): the dedicated refund screen — a real payment picker that
                         shows each payment's REMAINING refundable and caps a partial refund at it. The inline
                         per-payment full refund above still works and is unchanged. --}}
                    @can('payments.refund')
                    <a href="{{ route('app.reservations.refund-screen', $reservation['id']) }}" class="vy-btn vy-btn--ghost" style="margin-top:12px;width:100%;justify-content:center" title="{{ __('livewire/reservation-detail.partial_refund_title') }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/></svg>
                        {{ __('livewire/reservation-detail.partial_refund') }}
                    </a>
                    @endcan
                    {{-- REAL folio PDF export (tenant-scoped, reconciles to the cent). CSV export stays deferred. --}}
                    <a href="{{ route('app.reservations.folio-pdf', $reservation['id']) }}" class="vy-btn vy-btn--accent" style="margin-top:12px;width:100%;justify-content:center" title="{{ __('livewire/reservation-detail.export_pdf_title') }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                        {{ __('livewire/reservation-detail.export_pdf') }}
                    </a>
                    <div class="vy-defer-item" style="margin-top:8px;"><span>{{ __('livewire/reservation-detail.export_csv_print') }}</span><span class="vy-soon-tag">{{ __('livewire/reservation-detail.soon') }}</span></div>
                    <div class="vy-recon" style="margin-top: 6px;">{{ __('livewire/reservation-detail.room_moves_note') }}</div>
                </div>
            </div>
        </div>

    </x-operator.shell>
</div>
