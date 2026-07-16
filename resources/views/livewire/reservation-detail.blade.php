{{-- Operator Reservation Detail — reservation + folio (design pass · Operator Screen 4). Styled on
     the shared operator design system via <x-operator.shell>. The folio is rendered as the real
     APPEND-ONLY ledger; totals reconcile to the cent. The page READS; record-payment/refund post to
     the real FolioService action (append-only) and persist. Presentation only otherwise. --}}
<div>
    <x-operator.shell active="reservations" title="Reservation"
        :subtitle="$reservation['reference'] . ' · ' . $reservation['status_label']" :viewer="$viewer">

        @if (session('folio_notice'))
            <div class="vy-toast vy-fade" role="status">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('folio_notice') }}
            </div>
        @endif

        <div class="vy-detail-cols vy-fade">
            {{-- MAIN: terminal state (when real) + stay summary + units + folio ledger --}}
            <div class="vy-stack">
                {{-- TERMINAL STATE — rendered ONLY when the real status is cancelled/no_show. Every value
                     comes from a column AssignmentService::terminate() genuinely wrote. `cancelled_at` is
                     labelled per state (a no-show stamps the same shared column); the reason row is OMITTED
                     when there is none (a no-show has none) rather than faked with a dash. --}}
                @if ($terminal !== null)
                    <div class="vy-card">
                        <div class="vy-detail-head">
                            <span class="vy-eyebrow">{{ $terminal['label'] }}</span>
                            <span class="vy-tag tone-{{ $terminal['stage_tone'] }}"><span class="vy-dot"></span>{{ $terminal['stage_label'] }}</span>
                        </div>

                        <div class="vy-facts" style="margin-top: 14px;">
                            @if ($terminal['at'])
                                <div><div class="vy-fact-label">{{ $terminal['at_label'] }}</div><div class="vy-fact-val">{{ $terminal['at'] }}</div></div>
                            @endif
                            @if ($terminal['reason'])
                                <div><div class="vy-fact-label">Reason</div><div class="vy-fact-val">{{ $terminal['reason'] }}</div></div>
                            @endif
                        </div>

                        {{-- REAL + automatic: terminate() → releaseLine() frees `sold`. Claims nothing more. --}}
                        <div class="vy-callout is-oos" style="margin-top: 14px;">
                            <div class="vy-callout-title">Inventory released</div>
                            {{ $terminal['released'] }}
                        </div>

                        {{-- DEFERRED (locked decision 5): there is no fee engine. A label, never a figure. --}}
                        <div class="vy-action-list" style="margin-top: 12px;">
                            <div class="vy-defer-item"><span>{{ $terminal['fee_label'] }}</span><span class="vy-soon-tag">Soon</span></div>
                        </div>
                        <div class="vy-recon" style="margin-top: 6px;">No fee was calculated or charged — cancellation fees need a policy engine that isn’t built.</div>
                    </div>
                @endif

                <div class="vy-card">
                    <div class="vy-detail-head">
                        <span class="vy-detail-ref">{{ $reservation['reference'] }}</span>
                        <span class="vy-tag tone-{{ $reservation['tone'] }}"><span class="vy-dot"></span>{{ $reservation['status_label'] }}</span>
                    </div>
                    <div class="vy-facts" style="margin-top: 14px;">
                        <div><div class="vy-fact-label">Guest</div><div class="vy-fact-val">{{ $reservation['guest'] ?? '—' }}</div></div>
                        <div><div class="vy-fact-label">Source</div><div class="vy-fact-val">{{ $reservation['source'] }}</div></div>
                        <div><div class="vy-fact-label">Check-in → out</div><div class="vy-fact-val">{{ $reservation['check_in'] }} → {{ $reservation['check_out'] }}</div></div>
                        <div><div class="vy-fact-label">Nights · guests</div><div class="vy-fact-val">{{ $reservation['nights'] }} {{ \Illuminate\Support\Str::plural('night', $reservation['nights']) }} · {{ $reservation['guests'] }} {{ \Illuminate\Support\Str::plural('guest', $reservation['guests']) }}</div></div>
                    </div>
                </div>

                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 10px;">Units</h2>
                    @foreach ($units as $unit)
                        <div class="vy-unit-row">
                            <div>
                                <div class="vy-um-name">{{ $unit['room_type'] }} · {{ $unit['unit'] ? 'Unit '.$unit['unit'] : 'Unassigned' }}</div>
                                <div class="vy-um-sub">{{ $unit['check_in'] }} → {{ $unit['check_out'] }} · {{ ucfirst($unit['status']) }}</div>
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
                        <h2 class="vy-card-title" style="margin-bottom: 6px;">Status history</h2>
                        <div class="vy-timeline">
                            @foreach ($timeline as $event)
                                <div class="vy-tl-item">
                                    <div class="vy-tl-rail"><span class="vy-tl-dot"></span><span class="vy-tl-line"></span></div>
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
                    <h2 class="vy-card-title" style="margin-bottom: 6px;">Folio</h2>
                    @if ($folio === null)
                        <div class="vy-empty">No folio opened yet — record a payment or post a charge to open one.</div>
                    @else
                        <div class="vy-ledger">
                            @forelse ($folio['ledger'] as $line)
                                <div class="vy-ledger-row">
                                    <div class="vy-ledger-main">
                                        <div class="vy-ledger-label">
                                            {{ $line['label'] }}
                                            @if ($line['flag'])<span class="vy-ledger-flag">{{ $line['flag'] }}</span>@endif
                                        </div>
                                        <div class="vy-ledger-sub">{{ $line['desc'] }}</div>
                                    </div>
                                    <span class="vy-ledger-date">{{ $line['date'] }}</span>
                                    <span class="vy-ledger-amt tone-{{ $line['tone'] }}">{{ $line['amount'] }}</span>
                                    @if ($line['refundable'])
                                        {{-- WIRED: a full refund is a NEW offsetting entry (append-only) via FolioService. --}}
                                        <form method="POST" action="{{ route('app.reservations.refund', $reservation['id']) }}" class="vy-refund-btn">
                                            @csrf
                                            <input type="hidden" name="payment" value="{{ $line['payment_id'] }}">
                                            <button type="submit" class="vy-act vy-act--ghost">Refund</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <div class="vy-empty">No folio lines yet.</div>
                            @endforelse
                        </div>

                        <div class="vy-totals">
                            <div class="vy-total-row"><span class="vy-t-label">Charges</span><span class="vy-t-val">{{ $folio['charges'] }}</span></div>
                            <div class="vy-total-row"><span class="vy-t-label">Paid</span><span class="vy-t-val">{{ $folio['paid'] }}</span></div>
                            <div class="vy-total-row is-balance @unless ($folio['has_balance']) is-settled @endunless">
                                <span class="vy-t-label">Balance due</span><span class="vy-t-val">{{ $folio['balance'] }}</span>
                            </div>
                        </div>

                        @if ($folio['reconciles'])
                            <span class="vy-recon">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                Reconciled to the folio ledger
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            {{-- RIGHT RAIL: lifecycle (wired) + policy + record payment (wired) + deferred actions --}}
            <div class="vy-stack">
                {{-- WIRED: cancel + mark-no-show POST through ReservationLifecycleController →
                     AssignmentService (the single inventory write path). Each is offered ONLY when the REAL
                     current status allows it — the gates are the controller's own constants, so the UI can
                     never offer a transition the write path refuses. --}}
                @if ($can_cancel || $can_no_show)
                    <div class="vy-card">
                        <span class="vy-eyebrow">Lifecycle</span>

                        @if ($can_no_show)
                            <form method="POST" action="{{ route('app.reservations.no-show', $reservation['id']) }}" style="margin-top: 12px;"
                                onsubmit="return confirm('Mark this reservation as a no-show? The held nights are released back to the calendar.');">
                                @csrf
                                <button type="submit" class="vy-act vy-act--ghost">Mark no-show</button>
                            </form>
                            <div class="vy-recon" style="margin-top: 8px;">Frees the held nights. No reason is recorded — the service stores none for a no-show.</div>
                        @endif

                        @if ($can_cancel)
                            <form method="POST" action="{{ route('app.reservations.cancel', $reservation['id']) }}" style="margin-top: 14px;"
                                onsubmit="return confirm('Cancel this reservation? The held nights are released back to the calendar. This cannot be undone.');">
                                @csrf
                                <label class="vy-field-label" for="cancel-reason">Cancel · reason (optional)</label>
                                <div class="vy-input-group">
                                    <input class="vy-input" type="text" name="reason" id="cancel-reason" maxlength="255" placeholder="Why is it being cancelled?">
                                    <button type="submit" class="vy-act vy-act--accent">Cancel</button>
                                </div>
                            </form>
                            <div class="vy-recon" style="margin-top: 8px;">Cancelling is final — there is no reinstate, and the freed nights can be resold immediately.</div>
                        @endif
                    </div>
                @endif

                {{-- REAL, read-only: the property's CONFIGURED policy (`property_policies.cancellation`).
                     CONTEXT ONLY — no fee is computed against this stay; the copy says so explicitly. --}}
                @if ($policy !== null)
                    <div class="vy-card">
                        <span class="vy-eyebrow">Cancellation policy</span>
                        <div class="vy-fact-val" style="margin-top: 10px;">{{ $policy['summary'] }}</div>
                        <div class="vy-recon" style="margin-top: 6px;">The policy on file for this property. Nothing has been calculated against this booking.</div>
                    </div>
                @endif

                <div class="vy-card vy-card--accent">
                    <span class="vy-eyebrow">Record a payment</span>
                    {{-- WIRED: posts through FolioService (append-only). Manual/offline under the default gateway. --}}
                    <form method="POST" action="{{ route('app.reservations.payment', $reservation['id']) }}" style="margin-top: 12px;">
                        @csrf
                        <label class="vy-field-label" for="amount">Amount ({{ $reservation['currency'] }})</label>
                        <div class="vy-input-group">
                            <input class="vy-input" type="number" inputmode="decimal" step="0.01" min="0" name="amount" id="amount" placeholder="0.00">
                            <button type="submit" class="vy-btn vy-btn--accent">Record</button>
                        </div>
                    </form>
                    <div class="vy-recon" style="margin-top: 12px;">Offline / manual payment. Card &amp; 3DS capture is wired at the gateway, not this screen.</div>
                </div>

                <div class="vy-card">
                    <span class="vy-eyebrow">More actions</span>
                    {{-- DEFERRED / NO-BACKEND: real operations that need more UI or the guarded write path —
                         shown honestly, never faked. A refund is available inline per settled payment above. --}}
                    <div class="vy-action-list" style="margin-top: 10px;">
                        <div class="vy-defer-item"><span>Add charge</span><span class="vy-soon-tag">Soon</span></div>
                        <div class="vy-defer-item"><span>Partial / custom refund</span><span class="vy-soon-tag">Soon</span></div>
                        <div class="vy-defer-item"><span>Void</span><span class="vy-soon-tag">Soon</span></div>
                        <div class="vy-defer-item"><span>Move room</span><span class="vy-soon-tag">Soon</span></div>
                        {{-- Cancel is no longer deferred — it is wired for real in the Lifecycle card above. --}}
                        <div class="vy-defer-item"><span>Modify dates · guests</span><span class="vy-soon-tag">Soon</span></div>
                    </div>
                    {{-- REAL folio PDF export (tenant-scoped, reconciles to the cent). CSV export stays deferred. --}}
                    <a href="{{ route('app.reservations.folio-pdf', $reservation['id']) }}" class="vy-btn vy-btn--accent" style="margin-top:12px;width:100%;justify-content:center" title="Download the folio as a PDF">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                        Export folio (PDF)
                    </a>
                    <div class="vy-defer-item" style="margin-top:8px;"><span>Export CSV · print</span><span class="vy-soon-tag">Soon</span></div>
                    <div class="vy-recon" style="margin-top: 6px;">Move room routes through the guarded AssignmentService — deferred until it persists safely from here.</div>
                </div>
            </div>
        </div>

    </x-operator.shell>
</div>
