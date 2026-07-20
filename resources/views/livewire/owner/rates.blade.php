{{-- Owner Rates (Phase 2 · Sub-gate 3) — the owner sets nightly rates on their OWN (exclusively-owned) room
     types, DIRECTLY (no operator sign-off — the recorded divergence from the design's approval model). The money
     write goes POST → OwnerRateController → OwnerRateService → the certified RateAndRestrictionService. Only
     exclusive room types appear (a shared room type would move another party's price). Integer money is parsed
     server-side (MoneyInput). No fabricated proposals / guardrails / comp-set / Base-Weekend-Peak tiers.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="rates" :title="'Rates & pricing'" :subtitle="'Set your own nightly rates'" :viewer="$viewer">

    @if (session('rate_notice'))
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
            <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('rate_notice') }}</div>
        </div>
    @endif
    @if (session('rate_error'))
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber, #b8860b);margin-bottom:16px">
            <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('rate_error') }}</div>
        </div>
    @endif

    @if ($cards === [])
        <div class="vy-card vy-fade">
            <div class="vy-empty">
                You don’t have any room types to price here.
                <div class="vy-line-sub" style="margin-top:6px">Rates are set per room type. Where your unit shares a room type with the operator’s or another owner’s units, your management team sets the rate — this page shows only room types that are entirely yours.</div>
            </div>
        </div>
    @else
        <p class="vy-line-sub" style="font-size:13px;margin-bottom:18px">
            You set these rates directly and they go live immediately — no sign-off step. A rate applies to every unit in the room type.
        </p>

        <div class="vy-cols" style="display:grid;grid-template-columns:1.5fr 1fr;gap:18px;align-items:start">

            {{-- LEFT — current rates (real, as of the reference date) --}}
            <section class="vy-card vy-fade">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px">
                    <h2 class="vy-card-title">Current rates</h2>
                    <span class="vy-line-sub" style="font-size:12px">as of {{ \Illuminate\Support\Carbon::parse($from)->format('j M Y') }}</span>
                </div>
                <div style="overflow-x:auto;margin-top:10px">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>Room type</th>
                                <th>Units</th>
                                <th class="vy-num">Nightly rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cards as $card)
                                <tr>
                                    <td>
                                        <div class="vy-row-month">{{ $card['name'] }}</div>
                                        @unless ($card['has_plan'])<div class="vy-row-sub" style="color:var(--vy-faint)">No rate plan</div>@endunless
                                    </td>
                                    <td>{{ implode(', ', $card['units']) }}</td>
                                    <td class="vy-num vy-mono">
                                        @if ($card['rate_display'])
                                            {{ $card['rate_display'] }}
                                        @else
                                            <span class="vy-line-sub">Not set</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- RIGHT — the direct edit form --}}
            <section class="vy-card vy-fade" style="animation-delay:.04s">
                <h2 class="vy-card-title" style="margin-bottom:4px">Set a rate</h2>
                <p class="vy-line-sub" style="font-size:12px;margin:0 0 12px">Pick a room type and a date range, then the nightly rate to charge.</p>

                <form method="POST" action="{{ route('owner.rates.apply') }}">
                    @csrf

                    <div class="vy-field" style="margin-bottom:10px">
                        <label class="vy-field-label" for="room_type_id">Room type</label>
                        <select id="room_type_id" name="room_type_id" class="vy-input" required>
                            @foreach ($cards as $card)
                                <option value="{{ $card['id'] }}" @selected($selectedId === $card['id'])>{{ $card['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                        <div class="vy-field" style="margin:0">
                            <label class="vy-field-label" for="start_date">From</label>
                            <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $from) }}" class="vy-input" required>
                        </div>
                        <div class="vy-field" style="margin:0">
                            <label class="vy-field-label" for="end_date">To</label>
                            <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $to) }}" class="vy-input" required>
                        </div>
                    </div>

                    <div class="vy-field" style="margin-bottom:10px">
                        <label class="vy-field-label" for="rate">Nightly rate (€)</label>
                        <input id="rate" type="text" inputmode="decimal" name="rate" value="{{ old('rate') }}" placeholder="e.g. 175 or 175.50" class="vy-input" required>
                    </div>

                    <div class="vy-field" style="margin-bottom:14px">
                        <label class="vy-field-label" for="min_stay">Minimum stay (optional)</label>
                        <input id="min_stay" type="text" inputmode="numeric" name="min_stay" value="{{ old('min_stay') }}" placeholder="nights, or blank" class="vy-input">
                    </div>

                    <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">Update rate</button>
                </form>
                <p class="vy-line-sub" style="font-size:11.5px;margin-top:10px">The same rate is applied to every night in the range, for every unit in the room type.</p>
            </section>
        </div>
    @endif
</x-owner.shell>
