{{-- VY-13 (audit of 2026-11-24) — CHECK-IN IS REFUSED: the stay has no room, or its room is closed.
     ⚠️ A REFUSAL, NOT A WARNING. Unlike the early check-in page there is no "check in anyway": the action itself refuses
     (CheckInReservation), so a button here could only fail. The page names each problem and offers the way out.
     ⚠️ RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-layouts.operator :title="__('operator/check-in-blocked.page_title')">
{{-- The shell's title is the SHORT one: a long heading runs under the top bar's search on a narrow window (seen in a real browser,
     2026-11-24). The full sentence is the card's own title, where it has the room. --}}
<x-operator.shell active="front-desk" :title="__('operator/check-in-blocked.page_title')" subtitle="{{ $reservation->reference }}">

    <div class="vy-card is-warn" role="alert">
        <h2 class="vy-card-title">{{ __('operator/check-in-blocked.heading') }}</h2>
        <ul style="margin: 10px 0 0; padding-inline-start: 20px;">
            @foreach ($problems as $problem)
                <li>{{ $problem }}</li>
            @endforeach
        </ul>
    </div>

    <div class="vy-card">
        <h2 class="vy-card-title">{{ __('operator/check-in-blocked.what_to_do_heading') }}</h2>
        <p>{{ __('operator/check-in-blocked.what_to_do') }}</p>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
        <a class="vy-btn vy-btn--accent" href="{{ route('app.reservations.show', $reservation->getKey()) }}">{{ __('operator/check-in-blocked.open_reservation') }}</a>
        <a class="vy-btn vy-btn--ghost" href="{{ route('app.front-desk') }}">{{ __('operator/check-in-blocked.go_back') }}</a>
    </div>

</x-operator.shell>
</x-layouts.operator>
