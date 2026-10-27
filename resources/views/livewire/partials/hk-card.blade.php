{{-- One housekeeping room card (board lane). Real task data; clicking the room opens its detail.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
{{-- GATE 8.1 — the card is DRAGGABLE only when it can actually advance, and that condition is the EXACT one its forward button uses,
     so a drag can never do what the button cannot. ⚠️ F-16 — the old assignee clause enforced a rule THE SERVER DOES NOT HAVE
     (`assertTransition()` bounds status only), so it was removed from both together; whether a start should need an assignee is a
     founder ruling, tabulated in docs/STATUS-LADDERS.md.
     ⚠️ 2026-10-05 — classes, not 18 inline styles: the room's name and its sub-line were unstyled outside the list table (they
     rendered in the browser's button font), the tag row could not wrap, and the day the room is for was never shown. --}}
@php $hkDraggable = $canManage && $card['next'] !== []; @endphp
<div class="vy-card vy-hk-card @if ($hkDraggable) vy-hk-drag @endif"
     @if ($hkDraggable) draggable="true" @dragstart="hkStart($event, '{{ $card['id'] }}', '{{ $card['status'] }}')" @dragend="hkEnd()" @endif>
    <div class="vy-hk-card-top">
        <button type="button" class="vy-hk-room" @click="detail = {{ \Illuminate\Support\Js::from($card) }}">{{ $card['unit'] }}</button>
        <span class="vy-hk-day is-{{ $card['day_tone'] }}">{{ $card['day'] }}</span>
    </div>
    <div class="vy-hk-sub">{{ $card['type'] }}</div>

    {{-- Turnover, urgency and the arriving guest's flags (name-free) --}}
    @if ($card['turnover'] || $card['urgency'] || $card['guest_vip'] || $card['guest_preferences'] !== [])
        <div class="vy-hk-tags">
            @if ($card['turnover']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>{{ __('livewire/partials/hk-card.turnover') }}</span> @endif
            @if ($card['urgency'])
                <span class="vy-tag tone-{{ $card['urgency']['tone'] === 'red' ? 'risk' : 'confirmed' }}"><span class="vy-dot"></span>{{ $card['urgency']['label'] }}</span>
            @endif
            @if ($card['guest_vip']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>{{ __('livewire/partials/hk-card.vip') }}</span> @endif
            @foreach ($card['guest_preferences'] as $pref) <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ $pref }}</span> @endforeach
        </div>
    @endif

    {{-- Housekeeper or Assign --}}
    <div class="vy-hk-card-who">
        @if ($card['assignee'])
            <span class="vy-hk-who"><span class="vy-avatar vy-hk-avatar is-sm">{{ $card['assignee_initials'] }}</span>{{ $card['assignee'] }}</span>
        @elseif ($canAssign)
            <button type="button" class="vy-btn vy-btn--ghost vy-hk-small"
                    @click="assign = {{ \Illuminate\Support\Js::from(['id' => $card['id'], 'unit' => $card['unit'], 'type' => $card['type']]) }}">{{ __('livewire/partials/hk-card.assign_button') }}</button>
        @else
            <span class="vy-hk-none">{{ __('livewire/partials/hk-card.unassigned') }}</span>
        @endif
    </div>

    {{-- Elapsed (in-progress only) --}}
    @if ($card['elapsed'])
        <div class="vy-hk-elapsed @if ($card['elapsed']['over']) is-over @endif">
            <div class="vy-hk-elapsed-row"><span>{{ __('livewire/partials/hk-card.elapsed') }}</span><span>{{ __('livewire/partials/hk-card.elapsed_of_expected', ['minutes' => $card['elapsed']['minutes'], 'expected' => $card['elapsed']['expected']]) }}</span></div>
            <div class="vy-hk-elapsed-bar"><span style="width: {{ min(100, (int) round($card['elapsed']['minutes'] / max(1, $card['elapsed']['expected']) * 100)) }}%;"></span></div>
        </div>
    @endif

    {{-- Action / done --}}
    <div class="vy-hk-card-foot">
        @if ($card['status'] === 'inspected')
            <span class="vy-tag tone-in-house"><span class="vy-dot"></span>{{ __('livewire/partials/hk-card.guest_ready') }}</span>
        @elseif ($canManage && $card['next'] !== [])
            <form method="POST" action="{{ route('app.housekeeping.status', $card['id']) }}" class="vy-hk-inline">
                @csrf
                <input type="hidden" name="status" value="{{ $card['next'][0]['to'] }}">
                <button type="submit" class="vy-btn vy-btn--accent vy-hk-small">{{ $card['next'][0]['label'] }}</button>
            </form>
        @endif
        @if ($card['completed_label'])
            <span class="vy-hk-sub">{{ $card['completed_label'] }}</span>
        @elseif ($card['started_label'])
            <span class="vy-hk-sub">{{ __('livewire/partials/hk-card.started_at', ['time' => $card['started_label']]) }}</span>
        @endif
    </div>
</div>
