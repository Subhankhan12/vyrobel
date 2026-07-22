{{-- Owner Booking Detail (Phase 2 · Sub-gate 1) — ONE owner-scoped reservation. The controller has already
     404'd anything that doesn't touch one of THIS owner's units. Guest NAME + count only (no email/phone/
     country/message — invariant #28; owner↔guest messaging doesn't exist). Earnings + "Net to you" via the ONE
     shared OwnerLedgerAttribution rule. No fabricated pre-arrival-email timeline; no OTA brand.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="bookings"
               :title="$booking['guest'] ?? $booking['reference']"
               :subtitle="collect($booking['units'])->pluck('label')->implode(', ').' · '.$booking['nights'].' '.\Illuminate\Support\Str::plural('night', $booking['nights']).' · '.$booking['source_label']"
               :viewer="$viewer">

    <div style="margin-bottom:16px">
        <a href="{{ route('owner.bookings') }}" class="vy-line-sub" style="color:var(--vy-accent);font-size:12.5px">← Back to bookings</a>
    </div>

    <div class="vy-cols" style="display:grid;grid-template-columns:1.6fr 1fr;gap:18px;align-items:start">

        {{-- LEFT — reservation facts + the earnings breakdown --}}
        <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">

            <section class="vy-card vy-fade">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
                    <div>
                        <span class="vy-eyebrow">Reservation</span>
                        <h2 class="vy-card-title" style="margin-top:2px">{{ $booking['reference'] }}</h2>
                    </div>
                    <span class="vy-status vy-status--{{ $booking['stage_badge']['tone'] }}"><span class="vy-dot"></span>{{ $booking['stage_badge']['label'] }}</span>
                </div>

                <div class="vy-kpi-grid vy-kpi-grid--4" style="margin-top:16px">
                    <div class="vy-kpi">
                        <div class="vy-kpi-label">Check-in</div>
                        <div class="vy-kpi-val" style="font-size:16px">{{ $booking['check_in'] }}</div>
                    </div>
                    <div class="vy-kpi">
                        <div class="vy-kpi-label">Check-out</div>
                        <div class="vy-kpi-val" style="font-size:16px">{{ $booking['check_out'] }}</div>
                    </div>
                    <div class="vy-kpi">
                        <div class="vy-kpi-label">Nights</div>
                        <div class="vy-kpi-val">{{ $booking['nights'] }}</div>
                    </div>
                    <div class="vy-kpi">
                        <div class="vy-kpi-label">Guests</div>
                        <div class="vy-kpi-val">{{ $booking['guest_count'] > 0 ? $booking['guest_count'] : '—' }}</div>
                    </div>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:20px;margin-top:16px;font-size:13px">
                    <div>
                        <div class="vy-kpi-label">Unit</div>
                        @foreach ($booking['units'] as $unit)
                            <a href="{{ route('owner.units.show', $unit['id']) }}" style="color:var(--vy-accent);font-weight:600">{{ $unit['label'] }}</a>@if (! $loop->last), @endif
                        @endforeach
                    </div>
                    <div>
                        <div class="vy-kpi-label">Source</div>
                        <div>{{ $booking['source_label'] }}</div>
                    </div>
                    @if ($booking['booked_on'])
                        <div>
                            <div class="vy-kpi-label">Booked on</div>
                            <div>{{ $booking['booked_on'] }}</div>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Your earnings — the gross→net breakdown, per currency, via the shared rule --}}
            <section class="vy-card vy-fade" style="animation-delay:.04s">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px">
                    <h2 class="vy-card-title">Your earnings</h2>
                    <span class="vy-line-sub" style="font-size:12px">gross → net</span>
                </div>

                @forelse ($earnings as $block)
                    <div style="margin-top:14px">
                        @if (count($earnings) > 1)
                            <div class="vy-eyebrow" style="margin-bottom:6px">{{ $block['currency'] }}</div>
                        @endif
                        @foreach ($block['credits'] as $line)
                            <div class="vy-list-row" style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--vy-hairline)">
                                <span>{{ $line['label'] }}</span>
                                <span class="vy-mono">{{ $line['display'] }}</span>
                            </div>
                        @endforeach
                        @foreach ($block['deductions'] as $line)
                            <div class="vy-list-row" style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--vy-hairline)">
                                <span>{{ $line['label'] }}</span>
                                <span class="vy-mono vy-net">{{ $line['display'] }}</span>
                            </div>
                        @endforeach
                        <div class="vy-list-row" style="display:flex;justify-content:space-between;padding:11px 0 2px;font-weight:700">
                            <span>Net to you</span>
                            <span class="vy-mono">{{ $block['net_display'] }}</span>
                        </div>
                    </div>
                @empty
                    <div class="vy-empty" style="margin-top:12px">
                        Not yet attributed.
                        <div class="vy-line-sub" style="margin-top:6px">Your earnings appear here once this booking is attributed to your ledger — usually after check-out.</div>
                    </div>
                @endforelse
            </section>
        </div>

        {{-- RIGHT — net headline, payout status, actions --}}
        <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">

            <section class="vy-card vy-card--accent vy-fade">
                <span class="vy-eyebrow">Net to you</span>
                @if ($attributed)
                    @foreach ($earnings as $block)
                        <div class="vy-hero-figure" style="font-size:{{ count($earnings) === 1 ? '38px' : '28px' }}">{{ $block['net_display'] }}@if (count($earnings) > 1) <span class="vy-line-sub" style="font-size:11px">{{ $block['currency'] }}</span>@endif</div>
                    @endforeach
                @else
                    <div class="vy-hero-figure" style="font-size:38px;color:var(--vy-faint)">—</div>
                @endif
                <div class="vy-line-sub" style="margin-top:6px">for this {{ $booking['nights'] }}-night stay</div>

                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--vy-hairline)">
                    <span class="vy-status vy-status--{{ $payout['tone'] }}"><span class="vy-dot"></span>{{ $payout['label'] }}</span>
                    <div class="vy-line-sub" style="margin-top:8px;font-size:12px">{{ $payout['note'] }}</div>
                </div>
            </section>

            <section class="vy-card vy-fade" style="animation-delay:.04s">
                <div class="vy-card-title" style="margin-bottom:10px">Actions</div>
                @foreach ($booking['units'] as $unit)
                    <a href="{{ route('owner.units.show', $unit['id']) }}" class="vy-btn vy-btn--ghost" style="width:100%;justify-content:center;margin-bottom:8px">View {{ $unit['label'] }}</a>
                @endforeach
                <div class="vy-line-sub" style="font-size:11.5px;margin-top:4px">Guest contact details stay with the management team — you see the booking and your earnings.</div>
            </section>
        </div>
    </div>
</x-owner.shell>
