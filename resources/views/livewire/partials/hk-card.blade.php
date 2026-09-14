{{-- One housekeeping room card (board lane). Real task data; clicking opens the detail drawer.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
{{-- GATE 8.1 — the card is DRAGGABLE only when it can actually advance, and that condition is the EXACT
     one its forward button uses, so a drag can never do what the button cannot.
     ⚠️ F-16 — THE ASSIGNEE CLAUSE WAS REMOVED FROM BOTH, TOGETHER. It read `($card['assignee'] ||
     $card['status'] !== 'pending')` and enforced a rule THE SERVER DOES NOT HAVE: 🔬 `assertTransition()`
     bounds STATUS only, nothing in the status path reads `assigned_to`, and the List view has always
     started unassigned tasks successfully. So this board was stricter than the server AND told the user a
     false reason ("Assign to start"). ⚠️ The rule is now stated in ONE place — the server — and every
     surface simply offers what it permits, rather than three copies that can drift apart again.
     ⚠️ Whether a task SHOULD require an assignee before starting is a founder ruling, not a view's to make;
     it is tabulated with the others in docs/STATUS-LADDERS.md. --}}
@php $hkDraggable = ($canManage ?? $canAssign) && $card['next'] !== []; @endphp
<div class="vy-card @if ($hkDraggable) vy-hk-drag @endif" style="padding: 11px; margin-bottom: 10px; border-left: 3px solid var(--vy-accent);"
     :class="density === 'compact' && 'vy-hk-compact'"
     @if ($hkDraggable) draggable="true" @dragstart="hkStart($event, '{{ $card['id'] }}', '{{ $card['status'] }}')" @dragend="hkEnd()" @endif>
    <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 8px;">
        <button type="button" class="vy-u-code" style="border: none; background: none; cursor: pointer; padding: 0; font-weight: 700;"
                @click="detail = {{ \Illuminate\Support\Js::from($card) }}">{{ $card['unit'] }}</button>
        @if ($card['turnover']) <span class="vy-tag tone-vip" style="padding: 1px 7px;"><span class="vy-dot"></span>Turnover</span> @endif
    </div>
    <div class="vy-u-sub" style="margin-top: 2px;">{{ $card['type'] }}</div>

    {{-- Urgency + guest flags (name-free) --}}
    @if ($card['urgency'] || $card['guest_vip'] || $card['guest_preferences'] !== [])
        <div style="display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-top: 6px;">
            @if ($card['urgency'])
                <span class="vy-tag tone-{{ $card['urgency']['tone'] === 'red' ? 'risk' : 'confirmed' }}"><span class="vy-dot"></span>{{ $card['urgency']['label'] }}</span>
            @endif
            @if ($card['guest_vip']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>VIP</span> @endif
            @foreach ($card['guest_preferences'] as $pref) <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ $pref }}</span> @endforeach
        </div>
    @endif

    {{-- Housekeeper or Assign --}}
    <div style="margin-top: 8px;">
        @if ($card['assignee'])
            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--vy-ink);">
                <span class="vy-avatar" style="width: 22px; height: 22px; font-size: 10px;">{{ $card['assignee_initials'] }}</span>{{ $card['assignee'] }}
            </span>
        @elseif ($canManage ?? $canAssign)
            <button type="button" class="vy-btn vy-btn--ghost" style="padding: 3px 10px; font-size: 12px;"
                    @click="assign = {{ \Illuminate\Support\Js::from(['id' => $card['id'], 'unit' => $card['unit'], 'type' => $card['type']]) }}">+ Assign</button>
        @else
            <span class="vy-muted" style="font-size: 12px;">Unassigned</span>
        @endif
    </div>

    {{-- Elapsed (in-progress only) --}}
    @if ($card['elapsed'])
        <div style="margin-top: 8px;">
            <div style="display: flex; justify-content: space-between; font-size: 11px; color: {{ $card['elapsed']['over'] ? 'var(--vy-accent)' : 'var(--vy-muted)' }};">
                <span>Elapsed</span><span>{{ $card['elapsed']['minutes'] }}m / {{ $card['elapsed']['expected'] }}m</span>
            </div>
            <div style="height: 4px; border-radius: 2px; background: var(--vy-hairline); margin-top: 3px; overflow: hidden;">
                <div style="height: 100%; width: {{ min(100, (int) round($card['elapsed']['minutes'] / max(1, $card['elapsed']['expected']) * 100)) }}%; background: {{ $card['elapsed']['over'] ? 'var(--vy-accent)' : '#E0A63A' }};"></div>
            </div>
        </div>
    @endif

    {{-- Action / done --}}
    <div style="margin-top: 10px; padding-top: 9px; border-top: 1px solid var(--vy-hairline); display: flex; align-items: center; justify-content: space-between; gap: 8px;">
        @if ($card['status'] === 'inspected')
            <span class="vy-tag tone-in-house"><span class="vy-dot"></span>Guest-ready</span>
        @elseif (($canManage ?? $canAssign) && $card['next'] !== [])
            <form method="POST" action="{{ route('app.housekeeping.status', $card['id']) }}" style="display: inline;">
                @csrf
                <input type="hidden" name="status" value="{{ $card['next'][0]['to'] }}">
                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 5px 14px; font-size: 12px;">{{ $card['next'][0]['label'] }}</button>
            </form>
        @endif
        @if ($card['completed_label'])
            <span class="vy-u-sub">{{ $card['completed_label'] }}</span>
        @elseif ($card['started_label'])
            <span class="vy-u-sub">started {{ $card['started_label'] }}</span>
        @endif
    </div>
</div>
