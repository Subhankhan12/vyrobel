{{-- APPLY 01 #2 (founder, 2026-09-22) — the early check-in confirmation.
     ⚠️⚠️ A WARNING, NOT A CONTROL. This page REFUSES NOTHING: the primary button completes the check-in, and
     no permission gates it. It exists because `checked_in` is expensive to reach by mistake, not because
     early check-in is wrong — a front desk meets real early arrivals and this product must not argue with them.
     ⚠️ RAW-BLADE RULE: every `@` follows a space, newline or tag (a directive after a word character ships
     raw source to the user and assertSee sails past it). --}}
<x-layouts.operator :title="__('operator/early-check-in.page_title')">
<x-operator.shell active="front-desk" :title="__('operator/early-check-in.heading')" subtitle="{{ $reservation->reference }}">

    <div class="vy-card is-warn">
        <h2 class="vy-card-title">{{ trans_choice('operator/early-check-in.arrival_days_away', $daysEarly, ['count' => $daysEarly]) }}</h2>
        <p>
            {!! __('operator/early-check-in.booked_to_arrive', ['reference' => e($reservation->reference), 'date' => e(\Carbon\CarbonImmutable::parse((string) $reservation->check_in)->translatedFormat('D j M Y'))]) !!}
        </p>
        <p>
            {{ __('operator/early-check-in.different_booking') }}
        </p>
    </div>

    {{-- ⚠️⚠️ CORRECTED 2026-09-22 IN THE SAME CHANGE THAT BUILT R9 — the previous version of this card said
         "there is no way to reverse a check-in in the product today", which was true when it was written and
         became false the moment ReverseCheckIn shipped. ⚠️ The comment above it had said exactly this must
         happen in the same change; it did. **A warning that outlives the limit it describes is how a screen
         starts lying**, and this card existed to tell the truth about a cost, not to discourage the action. --}}
    <div class="vy-card">
        <h2 class="vy-card-title">{{ __('operator/early-check-in.wrong_booking_heading') }}</h2>
        <p>
            {!! __('operator/early-check-in.wrong_booking_body') !!}
        </p>
    </div>

    <form method="POST" action="{{ route('app.front-desk.check-in', $reservation->getKey()) }}">
        @csrf
        <input type="hidden" name="confirm_early" value="1">
        <button type="submit" class="vy-btn is-primary">{{ __('operator/early-check-in.check_in_anyway') }}</button>
        <a class="vy-btn" href="{{ route('app.front-desk') }}">{{ __('operator/early-check-in.go_back') }}</a>
    </form>

</x-operator.shell>
</x-layouts.operator>
