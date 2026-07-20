{{-- Owner Add a Unit (Phase 2 · Sub-gate 6). An owner REQUESTS a unit — they don't create billable inventory.
     The request is owner-scoped + self-only; the operator reviews it and creates the real unit as their action.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="add-unit" title="Add a unit" :subtitle="'Request a new unit'" :viewer="$viewer">

    @if (session('unit_request_notice'))
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
            <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('unit_request_notice') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber, #b8860b);margin-bottom:16px">
            <div style="font-size:13px;color:var(--vy-ink)">{{ $errors->first() }}</div>
        </div>
    @endif

    @unless ($linked)
        <div class="vy-card vy-fade"><div class="vy-empty">No owner account is linked to your login yet.</div></div>
    @else
        <p class="vy-line-sub" style="font-size:13px;margin-bottom:18px">
            Tell your management team about a unit you’d like to add. They’ll review the details, set it up in the system, and confirm — a new unit affects availability and billing, so it’s created on their side.
        </p>

        <div class="vy-cols" style="display:grid;grid-template-columns:1.3fr 1fr;gap:18px;align-items:start">

            {{-- LEFT — the request form --}}
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="margin-bottom:12px">Request a unit</h2>
                <form method="POST" action="{{ route('owner.add-unit.submit') }}">
                    @csrf

                    <div class="vy-field" style="margin-bottom:10px">
                        <label class="vy-field-label" for="requested_name">Unit name</label>
                        <input id="requested_name" type="text" name="requested_name" value="{{ old('requested_name') }}" placeholder="e.g. Villa Marisol" class="vy-input" required>
                    </div>

                    <div class="vy-field" style="margin-bottom:10px">
                        <label class="vy-field-label" for="requested_kind">Type</label>
                        <select id="requested_kind" name="requested_kind" class="vy-input" required>
                            @foreach ($kinds as $value => $label)
                                <option value="{{ $value }}" @selected(old('requested_kind') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="vy-field" style="margin-bottom:14px">
                        <label class="vy-field-label" for="notes">Details (optional)</label>
                        <textarea id="notes" name="notes" rows="4" placeholder="Address, bedrooms, anything your manager should know…" class="vy-input">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">Send request</button>
                </form>
                <p class="vy-line-sub" style="font-size:11.5px;margin-top:10px">This sends a request to your management team — it doesn’t create the unit itself.</p>
            </section>

            {{-- RIGHT — the owner's own requests --}}
            <section class="vy-card vy-fade" style="animation-delay:.04s">
                <h2 class="vy-card-title" style="margin-bottom:4px">Your requests</h2>
                <p class="vy-line-sub" style="font-size:12px;margin:0 0 12px">Requests you’ve sent and where they stand.</p>

                @forelse ($requests as $r)
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 0;border-bottom:1px solid var(--vy-hairline)">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $r['name'] }}</div>
                            <div class="vy-line-sub" style="font-size:12px">{{ $r['kind'] }}@if ($r['when']) · {{ $r['when'] }}@endif</div>
                        </div>
                        <span class="vy-status vy-status--{{ $r['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $r['status_meta']['label'] }}</span>
                    </div>
                @empty
                    <div class="vy-empty">You haven’t requested any units yet.</div>
                @endforelse
            </section>
        </div>
    @endunless
</x-owner.shell>
